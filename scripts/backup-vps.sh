#!/usr/bin/env bash
# =============================================================================
# backup-vps.sh — Backup de la BD de Gestión de Expensas + descarga a la PC
# =============================================================================
# Ejecutar desde Windows (Git Bash) o Linux/Mac.
#
# USO:
#   ./scripts/backup-vps.sh                    # backup puntual
#   ./scripts/backup-vps.sh --keep 7           # backup + conservar solo 7 días en VPS
#   VPS_USER=otro ./scripts/backup-vps.sh      # override usuario
#
# Configuración por variables de entorno (con defaults):
#   VPS_HOST=169.58.133.43
#   VPS_USER=richy
#   VPS_PROJECT_DIR=GestionExpensas2026        # relativo a $HOME del VPS
#   LOCAL_BACKUP_DIR=H:/BACK-UPS-expensas      # dónde guardar en la PC
#
# NOTA: si no tenés SSH key configurada, el script pide la password UNA SOLA
# VEZ (gracias a SSH ControlMaster) y la reutiliza para todos los comandos.
# =============================================================================
set -e

# --- Configuración (editable vía env vars) ---
VPS_HOST="${VPS_HOST:-169.58.133.43}"
VPS_USER="${VPS_USER:-richy}"
VPS_PROJECT_DIR="${VPS_PROJECT_DIR:-GestionExpensas2026}"
LOCAL_BACKUP_DIR="${LOCAL_BACKUP_DIR:-H:/BACK-UPS-expensas}"
KEEP_DAYS="${KEEP_DAYS:-0}"   # 0 = no borrar backups viejos en el VPS

# Procesar --keep N
while [[ $# -gt 0 ]]; do
    case "$1" in
        --keep)
            KEEP_DAYS="$2"
            shift 2
            ;;
        --help|-h)
            sed -n '2,18p' "$0"
            exit 0
            ;;
        *)
            echo "Argumento no reconocido: $1"
            exit 1
            ;;
    esac
done

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="backup_expensas_${TIMESTAMP}.sql.gz"
VPS_PATH="${VPS_PROJECT_DIR}/${BACKUP_FILE}"

# --- ControlMaster para pedir password UNA SOLA VEZ ---
# Esto abre una conexión SSH persistente en background y la reutiliza
# para todos los ssh/scp subsiguientes (sin volver a pedir password).
CM_DIR="/tmp/ssh-cm-expensas"
mkdir -p "${CM_DIR}"
CM_SOCKET="${CM_DIR}/cm-$$"
# ControlPersist=300 = la conexión queda abierta 5 min después de cerrar el master
SSH_OPTS=(-o ControlMaster=auto -o ControlPath="${CM_SOCKET}" -o ControlPersist=300
          -o StrictHostKeyChecking=accept-new)

cleanup() {
    # Cerrar el master limpiamente si quedó abierto
    ssh -o ControlPath="${CM_SOCKET}" -O exit "${VPS_USER}@${VPS_HOST}" 2>/dev/null || true
    rm -f "${CM_SOCKET}"*
}
trap cleanup EXIT

echo "================================================================"
echo "💾 Backup de Gestión de Expensas"
echo "================================================================"
echo "VPS:        ${VPS_USER}@${VPS_HOST}"
echo "Archivo:    ${BACKUP_FILE}"
echo "Destino:    ${LOCAL_BACKUP_DIR}/"
echo ""

# --- 1. Abrir ControlMaster (acá pide password UNA vez, si no hay SSH key) ---
echo "🔐 Estableciendo conexión SSH (pedirá password si no hay SSH key)..."
# Con -fN abre la conexión, se autentica, y se va a background.
# Los comandos siguientes reutilizan esta conexión y NO vuelven a pedir password.
ssh "${SSH_OPTS[@]}" -fN "${VPS_USER}@${VPS_HOST}"
echo "✓ Conexión SSH establecida (reutilizable por 5 minutos)"
echo ""

# --- 2. Crear directorio local ---
mkdir -p "${LOCAL_BACKUP_DIR}"

# --- 3. Ejecutar mysqldump en el VPS vía SSH (sin volver a pedir password) ---
echo "🔄 Generando backup en el VPS..."
ssh "${SSH_OPTS[@]}" "${VPS_USER}@${VPS_HOST}" \
    "cd ~/${VPS_PROJECT_DIR} && \
     docker compose exec -T mysql mysqldump \
         -u root -p\"\$(grep DB_ROOT_PASSWORD .env | cut -d= -f2)\" \
         --single-transaction --quick --routines --triggers \
         expensas_db | gzip > '${BACKUP_FILE}' && \
     ls -lh '${BACKUP_FILE}' | awk '{print \"   Tamaño VPS: \" \$5}'"

# --- 4. Verificar integridad del gzip en el VPS ---
echo "✓ Verificando integridad del gzip en el VPS..."
ssh "${SSH_OPTS[@]}" "${VPS_USER}@${VPS_HOST}" \
    "cd ~/${VPS_PROJECT_DIR} && gunzip -t '${BACKUP_FILE}'" \
    && echo "   Backup OK en el VPS" \
    || { echo "❌ Backup corrupto en el VPS, abortando."; exit 1; }

# --- 5. Descargar al PC (sin volver a pedir password) ---
echo "⬇️  Descargando a tu PC..."
scp "${SSH_OPTS[@]}" "${VPS_USER}@${VPS_HOST}:${VPS_PATH}" "${LOCAL_BACKUP_DIR}/"

# --- 6. Verificar gzip local ---
echo "✓ Verificando integridad local..."
gunzip -t "${LOCAL_BACKUP_DIR}/${BACKUP_FILE}" \
    && echo "   Backup local OK" \
    || { echo "❌ Backup local corrupto."; exit 1; }

# --- 7. Limpieza opcional en el VPS ---
if [ "${KEEP_DAYS}" != "0" ]; then
    echo "🧹 Limpiando backups de más de ${KEEP_DAYS} días en el VPS..."
    ssh "${SSH_OPTS[@]}" "${VPS_USER}@${VPS_HOST}" \
        "cd ~/${VPS_PROJECT_DIR} && \
         find . -maxdepth 1 -name 'backup_expensas_*.sql.gz' -mtime +${KEEP_DAYS} -delete && \
         echo '   Backups restantes:' && ls -1 backup_expensas_*.sql.gz 2>/dev/null | wc -l"
fi

# --- 8. Resumen ---
LOCAL_SIZE=$(ls -lh "${LOCAL_BACKUP_DIR}/${BACKUP_FILE}" 2>/dev/null | awk '{print $5}')
echo ""
echo "================================================================"
echo "✅ Backup completado"
echo "================================================================"
echo "Archivo:  ${LOCAL_BACKUP_DIR}/${BACKUP_FILE}"
echo "Tamaño:   ${LOCAL_SIZE}"
echo ""
echo "Para restaurar:"
echo "  scp ${LOCAL_BACKUP_DIR}/${BACKUP_FILE} ${VPS_USER}@${VPS_HOST}:${VPS_PATH}"
echo "  ssh ${VPS_USER}@${VPS_HOST}"
echo "  cd ~/${VPS_PROJECT_DIR}"
echo "  gunzip < ${BACKUP_FILE} | docker compose exec -T mysql mysql -u root -p\"\$(grep DB_ROOT_PASSWORD .env | cut -d= -f2)\" expensas_db"
