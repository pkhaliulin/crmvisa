# Disaster Recovery Plan

## RPO / RTO
- **RPO (Recovery Point Objective):** <= 1 hour for database, <= 7 days for documents
- **RTO (Recovery Time Objective):** <= 4 hours

## Backup Schedule
- **Database:** Daily at 03:00 UTC, encrypted AES-256-CBC, retained 30 days
- **Documents:** Weekly (Sundays), encrypted, retained 90 days
- **Verification:** Daily at 04:00 via `php artisan app:verify-backup`

## Backup Location
- Primary: /var/backups/crmvisa/ on production server
- TODO: Offsite copy to S3-compatible storage

## Encryption
- Algorithm: AES-256-CBC with PBKDF2 key derivation
- Key: BACKUP_ENCRYPTION_KEY environment variable
- Key MUST NOT be stored on the same server as backups

## Restore Procedure
1. SSH to server: `ssh root@185.76.15.44`
2. List available backups: `ls -la /var/backups/crmvisa/db_*.sql.gz.enc`
3. Set environment: `source /var/www/crmvisa/.env`
4. Run restore: `/var/www/crmvisa/scripts/restore.sh <backup_file>`
5. Run migrations: `cd /var/www/crmvisa && php artisan migrate --force`
6. Verify: `php artisan app:health-check`
7. Clear caches: `php artisan cache:clear && php artisan config:cache`

## Restore Test Schedule
- Monthly restore drill (first Monday of each month)
- Log results in this document

## Contacts
- Primary: Platform owner
- Escalation: DevOps team
