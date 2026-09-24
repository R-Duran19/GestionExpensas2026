#!/usr/bin/env bash
# =============================================================================
# restore-db.sh — Restaurar un backup (se ejecuta EN el VPS)
# =============================================================================
# Uso:
#   ./scripts/restore-db.sh /var/backups/gestion-expensas/daily_expensas_20260923_030001.sql.gz.gpg
#   ./scripts/restore-db.sh ./backup_expensas_20260923.sql.gz   # tambien acepta .gz plano
#
# Que hace:
#   1. Backup de SEGURIDAD del estado actual antes de tocar nada (pre-restore)
#   2. Pide confirmacion escribiendo RESTAURAR
#   3. Descifra (si termina en .gpg), descomprime e importa en el contenedor mysql
#
# IMPORTANTE:
#   - La PII esta CIFRADA con la APP_KEY. Restaurar en un servidor con otra
#     APP_KEY deja ci/telefono/email ilegibles. Verificar .env antes.
#   - Despues de restaurar: docker compose exec app php artisan config:clear
#
# Documentacion completa: docs/BACKUPS-CIFRADOS.md
# =============================================================================
set -euo pipefail

BACKUP_FILE="${1:-}"
[ -z "$BACKUP_FILE" ] && { echo "Uso: $0 <archivo_backup.sql.gz[.gpg]>"; exit 1; }
[ -f "$BACKUP_FILE" ] || { echo "No existe: $BACKUP_FILE"; exit 1; }

PASS_FILE="${BACKUP_PASS_FILE:-/root/.gestion-expensas-backup.pass}"

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

echo "==============================================================="
echo " RESTAURACION DE BASE DE DATOS - Gestion de Expensas"
echo " Origen: $BACKUP_FILE"
echo "==============================================================="

# -----------------------------------------------------------------------------
# 1. Backup de seguridad del estado actual
# -----------------------------------------------------------------------------
echo "[1/3] Backup de seguridad del estado actual..."
SAFETY="/tmp/pre_restore_$(date +%Y%m%d_%H%M%S).sql.gz"
docker compose exec -T mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --quick --routines --triggers --events --hex-blob "$MYSQL_DATABASE"' | gzip > "$SAFETY"
gzip -t "$SAFETY"
echo "      Seguridad guardada en: $SAFETY"

# -----------------------------------------------------------------------------
# 2. Confirmacion explicita
# -----------------------------------------------------------------------------
echo "[2/3] Esto REEMPLAZA TODA la base de datos actual."
read -r -p "      Escriba RESTAURAR para continuar (cualquier otra cosa cancela): " CONFIRM
if [ "$CONFIRM" != "RESTAURAR" ]; then
  echo "Cancelado. No se modifico nada."
  exit 1
fi

# -----------------------------------------------------------------------------
# 3. Descifrar + descomprimir + importar
# -----------------------------------------------------------------------------
echo "[3/3] Importando..."
case "$BACKUP_FILE" in
  *.gpg)
    [ -f "$PASS_FILE" ] || { echo "ERROR: hace falta $PASS_FILE para descifrar"; exit 1; }
    gpg --batch --quiet --decrypt --passphrase-file "$PASS_FILE" "$BACKUP_FILE" \
      | gunzip \
      | docker compose exec -T mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
    ;;
  *)
    gunzip -c "$BACKUP_FILE" \
      | docker compose exec -T mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
    ;;
esac

echo ""
echo "Restauracion completada."
echo "  - Backup de seguridad: $SAFETY"
echo "  - Ejecute: docker compose exec app php artisan config:clear"
echo "  - Verifique login y un reporte antes de dar el sistema por sano."
