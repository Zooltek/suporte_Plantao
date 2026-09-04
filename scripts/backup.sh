#!/usr/bin/env bash
# ──────────────────────────────────────────────────────────────────────────────
# scripts/backup.sh
#
# Dump do MySQL + snapshot de storage/app para uso em cron do host.
#
# Uso:
#   scripts/backup.sh [destino]
#
# Variáveis (env):
#   BACKUP_DIR              — destino padrão (default: /var/backups/suporte)
#   BACKUP_RETENTION_DAYS   — dias de retenção (default: 14)
#   MYSQL_CONTAINER         — nome do container do banco (default: suporte12_mysql)
#
# Exemplo de cron (host, diário às 03:10):
#   10 3 * * * /caminho/do/projeto/scripts/backup.sh >> /var/log/suporte-backup.log 2>&1
# ──────────────────────────────────────────────────────────────────────────────
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEST="${1:-${BACKUP_DIR:-/var/backups/suporte}}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"
MYSQL_CONTAINER="${MYSQL_CONTAINER:-suporte12_mysql}"
TS="$(date +%Y%m%d-%H%M%S)"

log() { printf '[backup %s] %s\n' "$(date +%H:%M:%S)" "$*"; }

if ! docker inspect -f '{{.State.Running}}' "$MYSQL_CONTAINER" 2>/dev/null | grep -q true; then
  log "ERRO: container $MYSQL_CONTAINER não está rodando."
  exit 1
fi

mkdir -p "$DEST"
chmod 700 "$DEST" 2>/dev/null || true

log "destino: $DEST (retenção: ${RETENTION_DAYS} dias)"

# 1. Dump do MySQL (usa as variáveis do próprio container)
DB_FILE="$DEST/db-$TS.sql.gz"
log "dumping MySQL → $(basename "$DB_FILE")"
docker exec "$MYSQL_CONTAINER" sh -c '
  exec mysqldump \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --default-character-set=utf8mb4 \
    -uroot -p"$MYSQL_ROOT_PASSWORD" \
    "$MYSQL_DATABASE"
' | gzip > "$DB_FILE"

if [ ! -s "$DB_FILE" ]; then
  log "ERRO: dump vazio — abortando antes de limpar retenção."
  rm -f "$DB_FILE"
  exit 2
fi

# 2. Snapshot de storage/app (uploads, arquivos de usuário)
STORAGE_FILE="$DEST/storage-$TS.tar.gz"
log "arquivando storage/app → $(basename "$STORAGE_FILE")"
tar -czf "$STORAGE_FILE" -C "$PROJECT_ROOT" storage/app

# 3. Retenção
log "removendo backups com mais de ${RETENTION_DAYS} dias..."
find "$DEST" -maxdepth 1 -type f \
  \( -name 'db-*.sql.gz' -o -name 'storage-*.tar.gz' \) \
  -mtime +"${RETENTION_DAYS}" -delete

log "concluído. Tamanhos:"
ls -lh "$DB_FILE" "$STORAGE_FILE" | awk '{print "  " $5 "  " $9}'
