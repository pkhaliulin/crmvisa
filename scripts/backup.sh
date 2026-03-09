#!/bin/bash
# Automated PostgreSQL backup with encryption
# Run daily via cron: 0 3 * * * /var/www/crmvisa/scripts/backup.sh

set -euo pipefail

BACKUP_DIR="/var/backups/crmvisa"
RETENTION_DAYS=30
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="${DB_DATABASE:-crmvisa}"
DB_USER="${DB_USERNAME:-crmvisa}"
BACKUP_FILE="${BACKUP_DIR}/db_${DATE}.sql.gz.enc"
DOCUMENTS_BACKUP="${BACKUP_DIR}/docs_${DATE}.tar.gz.enc"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Database backup (compressed + encrypted with openssl)
PGPASSWORD="${DB_PASSWORD}" pg_dump -h localhost -U "$DB_USER" "$DB_NAME" \
  --no-owner --no-privileges \
  | gzip \
  | openssl enc -aes-256-cbc -salt -pbkdf2 -pass env:BACKUP_ENCRYPTION_KEY \
  > "$BACKUP_FILE"

echo "[$(date)] DB backup created: $BACKUP_FILE ($(du -h "$BACKUP_FILE" | cut -f1))"

# Documents backup (weekly - only on Sundays)
if [ "$(date +%u)" = "7" ]; then
  tar czf - -C /var/www/crmvisa/storage/app/documents . 2>/dev/null \
    | openssl enc -aes-256-cbc -salt -pbkdf2 -pass env:BACKUP_ENCRYPTION_KEY \
    > "$DOCUMENTS_BACKUP"
  echo "[$(date)] Docs backup created: $DOCUMENTS_BACKUP ($(du -h "$DOCUMENTS_BACKUP" | cut -f1))"
fi

# Cleanup old backups
find "$BACKUP_DIR" -name "db_*.sql.gz.enc" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "docs_*.tar.gz.enc" -mtime +90 -delete

echo "[$(date)] Backup completed successfully"
