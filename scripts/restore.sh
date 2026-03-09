#!/bin/bash
# Restore PostgreSQL from encrypted backup
# Usage: ./restore.sh /var/backups/crmvisa/db_20260309_030000.sql.gz.enc

set -euo pipefail

if [ -z "${1:-}" ]; then
  echo "Usage: $0 <backup_file>"
  echo "Available backups:"
  ls -la /var/backups/crmvisa/db_*.sql.gz.enc 2>/dev/null || echo "No backups found"
  exit 1
fi

BACKUP_FILE="$1"
DB_NAME="${DB_DATABASE:-crmvisa}"
DB_USER="${DB_USERNAME:-crmvisa}"

if [ ! -f "$BACKUP_FILE" ]; then
  echo "Error: Backup file not found: $BACKUP_FILE"
  exit 1
fi

echo "WARNING: This will overwrite the database '$DB_NAME'!"
echo "Backup file: $BACKUP_FILE"
read -p "Type 'RESTORE' to confirm: " confirm

if [ "$confirm" != "RESTORE" ]; then
  echo "Cancelled."
  exit 1
fi

echo "[$(date)] Starting restore..."

openssl enc -d -aes-256-cbc -pbkdf2 -pass env:BACKUP_ENCRYPTION_KEY -in "$BACKUP_FILE" \
  | gunzip \
  | PGPASSWORD="${DB_PASSWORD}" psql -h localhost -U "$DB_USER" "$DB_NAME"

echo "[$(date)] Restore completed successfully"
echo "Run 'php artisan migrate --force' to apply any pending migrations"
