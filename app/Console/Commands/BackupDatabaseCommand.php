<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'app:backup
        {--upload-s3 : Загрузить бэкап на S3 после создания}';

    protected $description = 'Создание зашифрованного бэкапа PostgreSQL (аналог scripts/backup.sh)';

    private string $backupDir = '/var/backups/crmvisa';
    private int $retentionDays = 30;

    public function handle(): int
    {
        $this->info('=== Бэкап базы данных ===');

        $encryptionKey = env('BACKUP_ENCRYPTION_KEY');
        if (empty($encryptionKey)) {
            $this->err('BACKUP_ENCRYPTION_KEY не задан в окружении');
            return 1;
        }

        $dbName = config('database.connections.pgsql.database');
        $dbUser = config('database.connections.pgsql.username');
        $dbPassword = config('database.connections.pgsql.password');
        $dbHost = config('database.connections.pgsql.host', 'localhost');

        @mkdir($this->backupDir, 0755, true);

        $date = date('Ymd_His');
        $backupFile = "{$this->backupDir}/db_{$date}.sql.gz.enc";

        // Создание бэкапа: pg_dump | gzip | openssl encrypt
        $this->info('Создание дампа БД...');

        $cmd = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -U %s %s --no-owner --no-privileges | gzip | openssl enc -aes-256-cbc -salt -pbkdf2 -pass env:BACKUP_ENCRYPTION_KEY > %s 2>&1',
            escapeshellarg($dbPassword),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbName),
            escapeshellarg($backupFile)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->err('pg_dump завершился с ошибкой: ' . implode("\n", $output));
            @unlink($backupFile);
            return 1;
        }

        if (!file_exists($backupFile) || filesize($backupFile) < 1024) {
            $this->err('Бэкап файл не создан или подозрительно мал');
            @unlink($backupFile);
            return 1;
        }

        $size = number_format(filesize($backupFile) / 1024 / 1024, 2);
        $this->ok("Бэкап создан: {$backupFile} ({$size} MB)");

        // Проверка расшифровки
        $this->info('Верификация бэкапа...');
        $verifyCmd = sprintf(
            'openssl enc -d -aes-256-cbc -pbkdf2 -pass env:BACKUP_ENCRYPTION_KEY -in %s | gunzip | head -c 1024 > /dev/null 2>&1',
            escapeshellarg($backupFile)
        );
        exec($verifyCmd, $verifyOutput, $verifyExit);

        if ($verifyExit !== 0) {
            $this->err('Верификация не пройдена! Бэкап может быть поврежден.');
            return 1;
        }
        $this->ok('Верификация пройдена');

        // Загрузка на S3
        if ($this->option('upload-s3')) {
            $this->uploadToS3($backupFile);
        }

        // Очистка старых бэкапов
        $this->cleanOldBackups();

        $this->info('');
        $this->info('=== Бэкап завершен успешно ===');

        return 0;
    }

    private function uploadToS3(string $backupFile): void
    {
        $bucket = env('BACKUP_S3_BUCKET');
        if (empty($bucket)) {
            $this->warn('BACKUP_S3_BUCKET не задан — пропуск загрузки на S3');
            return;
        }

        $this->info('Загрузка на S3...');

        $cmd = sprintf(
            'aws s3 cp %s s3://%s/db/ --quiet 2>&1',
            escapeshellarg($backupFile),
            escapeshellarg($bucket)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->warn('Загрузка на S3 не удалась: ' . implode("\n", $output));
        } else {
            $this->ok("Загружено на S3: s3://{$bucket}/db/" . basename($backupFile));
        }
    }

    private function cleanOldBackups(): void
    {
        $files = glob("{$this->backupDir}/db_*.sql.gz.enc");
        $deleted = 0;
        $cutoff = time() - ($this->retentionDays * 86400);

        foreach ($files as $file) {
            // Не удаляем safety-бэкапы
            if (str_contains(basename($file), 'safety')) {
                continue;
            }
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->ok("Удалено старых бэкапов: {$deleted} (старше {$this->retentionDays} дней)");
        }
    }

    private function ok(string $msg): void
    {
        $this->line("  <fg=green>OK</> {$msg}");
    }

    private function err(string $msg): void
    {
        $this->line("  <fg=red>ERR</> {$msg}");
    }
}
