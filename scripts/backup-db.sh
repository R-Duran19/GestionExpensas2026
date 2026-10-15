#!/usr/bin/env bash
# =============================================================================
# backup-db.sh — Backup diario/mensual CIFRADO de la BD (se ejecuta EN el VPS)
# =============================================================================
# Uso:
#   ./scripts/backup-db.sh daily     # backup diario (default), conservar 30
#   ./scripts/backup-db.sh monthly   # backup mensual (dia 1), conservar 12
#
# - Dump consistente con --single-transaction (InnoDB, sin bloquear tablas)
# - Cifrado GPG simetrico AES256 si existe el archivo de passphrase
#   (/root/.gestion-expensas-backup.pass, chmod 600). Sin passphrase deja el
#   .gz SIN cifrar y lo advierte en el log (fail-safe: respalda igual).
# - Destino FUERA del directorio del proyecto: /var/backups/gestion-expensas
# - Verificacion: tamano minimo + gzip -t antes de reportar OK
# - Rotacion: conserva los ultimos 30 diarios / 12 mensuales
# - Log: /var/log/gestion-expensas-backup.log
#
# Instalacion (una sola vez en el VPS):
#   sudo apt-get install -y gnupg
#   openssl rand -base64 32 | sudo tee /root/.gestion-expensas-backup.pass
#   sudo chmod 600 /root/.gestion-expensas-backup.pass
#   crontab -e:
#     0 3 * * *  /root/GestionExpensas2026/scripts/backup-db.sh daily
#     30 3 1 * * /root/GestionExpensas2026/scripts/backup-db.sh monthly
#
# Documentacion completa: docs/BACKUPS-CIFRADOS.md
# =============================================================================
set -euo pipefail

TIPO="${1:-daily}"
KEEP_DAILY=30
KEEP_MONTHLY=12
DEST="${BACKUP_DEST:-/var/backups/gestion-expensas}"
PASS_FILE="${BACKUP_PASS_FILE:-/root/.gestion-expensas-backup.pass}"
LOG_FILE="${BACKUP_LOG:-/var/log/gestion-expensas-backup.log}"
MIN_SIZE=1024   # un dump real de este sistema siempre supera 1KB

case "$TIPO" in
  daily)   PREFIX="daily";   KEEP=$KEEP_DAILY ;;
  monthly) PREFIX="monthly"; KEEP=$KEEP_MONTHLY ;;
  *)
    echo "Uso: $0 daily|monthly"
    exit 1
    ;;
esac

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> "$LOG_FILE"; }

# Resolver el directorio del proyecto (para que funcione desde cron con cualquier cwd)
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

mkdir -p "$DEST"

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BASE="$DEST/${PREFIX}_expensas_${TIMESTAMP}.sql.gz"
FINAL="$BASE"

log "=== Inicio backup $TIPO ==="

# -----------------------------------------------------------------------------
# 1. Dump consistente + compresion
#    Credenciales del CONTENEDOR (env vars de docker-compose), nunca hardcoded.
# -----------------------------------------------------------------------------
if ! docker compose exec -T mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --quick --routines --triggers --events --hex-blob "$MYSQL_DATABASE"' | gzip > "$BASE"; then
  log "ERROR: mysqldump fallo"
  rm -f "$BASE"
  exit 1
fi

# -----------------------------------------------------------------------------
# 2. Verificacion de integridad (tamano + gzip -t)
# -----------------------------------------------------------------------------
SIZE=$(stat -c%s "$BASE" 2>/dev/null || echo 0)
if [ "$SIZE" -lt "$MIN_SIZE" ]; then
  log "ERROR: dump sospechosamente pequeno ($SIZE bytes)"
  rm -f "$BASE"
  exit 1
fi
if ! gzip -t "$BASE"; then
  log "ERROR: archivo gzip corrupto"
  rm -f "$BASE"
  exit 1
fi

# -----------------------------------------------------------------------------
# 3. Cifrado AES256 (opcional pero recomendado)
# -----------------------------------------------------------------------------
if [ -f "$PASS_FILE" ] && command -v gpg >/dev/null 2>&1; then
  if gpg --batch --yes --quiet --cipher-algo AES256 --symmetric \
        --passphrase-file "$PASS_FILE" --output "$BASE.gpg" "$BASE"; then
    rm -f "$BASE"
    FINAL="$BASE.gpg"
    log "Cifrado AES256 OK"
  else
    log "WARN: fallo el cifrado GPG, se conserva SOLO el .gz sin cifrar"
  fi
else
  log "WARN: sin passphrase ($PASS_FILE) o sin gpg instalado => backup SIN cifrar"
fi

# -----------------------------------------------------------------------------
# 4. Rotacion: conservar los ultimos $KEEP del tipo correspondiente
# -----------------------------------------------------------------------------
ls -1t "$DEST/${PREFIX}_expensas_"* 2>/dev/null | tail -n +$((KEEP + 1)) | while read -r f; do
  rm -f "$f"
  log "Rotado: $f"
done

log "OK backup $TIPO: $FINAL ($(du -h "$FINAL" | cut -f1))"
echo "Backup listo: $FINAL"
