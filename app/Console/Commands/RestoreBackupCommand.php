<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RestoreBackupCommand extends Command
{
    protected $signature = 'app:restore-backup
        {--file= : Путь к конкретному файлу бэкапа}
        {--latest : Использовать последний бэкап}
        {--dry-run : Расшифровать и проверить без восстановления}
        {--force : Игнорировать проверку давности бэкапа (>7 дней)}';

    protected $description = 'Восстановление базы данных из зашифрованного бэкапа';

    private string $logChannel = 'restore';
    private string $backupDir = '/var/backups/crmvisa';

    public function handle(): int
    {
        $this->setupLogging();
        $this->log('=== Начало процесса восстановления ===');

        // Определяем файл бэкапа
        $backupFile = $this->resolveBackupFile();
        if (!$backupFile) {
            return 1;
        }

        // Проверяем ключ шифрования
        $encryptionKey = env('BACKUP_ENCRYPTION_KEY');
        if (empty($encryptionKey)) {
            $this->err('BACKUP_ENCRYPTION_KEY не задан в окружении');
            return 1;
        }

        // Информация о файле
        $fileSize = filesize($backupFile);
        $fileMtime = filemtime($backupFile);
        $fileAge = time() - $fileMtime;
        $fileAgeDays = round($fileAge / 86400, 1);

        $this->info('Файл бэкапа: ' . basename($backupFile));
        $this->info('Размер: ' . number_format($fileSize / 1024 / 1024, 2) . ' MB');
        $this->info('Дата создания: ' . date('Y-m-d H:i:s', $fileMtime));
        $this->info('Возраст: ' . $fileAgeDays . ' дней');
        $this->log("Файл: {$backupFile}, размер: {$fileSize}, возраст: {$fileAgeDays} дней");

        // Проверка давности
        if ($fileAgeDays > 7 && !$this->option('force')) {
            $this->err("Бэкап старше 7 дней ({$fileAgeDays} дн.). Используйте --force для принудительного восстановления.");
            return 1;
        }

        // Проверка минимального размера
        if ($fileSize < 1024) {
            $this->err("Бэкап подозрительно мал ({$fileSize} байт) — возможно поврежден.");
            return 1;
        }

        // Проверка расшифровки
        $this->info('');
        $this->info('Проверка расшифровки...');
        if (!$this->verifyDecryption($backupFile)) {
            $this->err('Не удалось расшифровать бэкап. Проверьте BACKUP_ENCRYPTION_KEY.');
            return 1;
        }
        $this->ok('Бэкап успешно расшифрован и распакован');

        if ($this->option('dry-run')) {
            $this->info('');
            $this->info('[DRY-RUN] Проверка завершена. Восстановление не выполнялось.');
            $this->log('[DRY-RUN] Проверка завершена успешно.');
            return 0;
        }

        // Создаем safety backup текущей БД перед восстановлением
        $this->info('');
        $this->info('Создание safety backup текущей БД...');
        $safetyFile = $this->createSafetyBackup();
        if (!$safetyFile) {
            $this->err('Не удалось создать safety backup. Восстановление отменено.');
            return 1;
        }
        $this->ok("Safety backup создан: {$safetyFile}");

        // Восстановление
        $this->info('');
        $this->info('Восстановление базы данных...');
        if (!$this->restoreDatabase($backupFile)) {
            $this->err('Ошибка восстановления базы данных!');
            $this->warn("Safety backup доступен: {$safetyFile}");
            return 1;
        }
        $this->ok('База данных восстановлена');

        // Применяем миграции
        $this->info('');
        $this->info('Применение новых миграций...');
        $this->log('Запуск миграций...');
        $exitCode = $this->call('migrate', ['--force' => true]);
        if ($exitCode === 0) {
            $this->ok('Миграции применены');
            $this->log('Миграции применены успешно.');
        } else {
            $this->warn('Миграции завершились с ошибками (код: ' . $exitCode . ')');
            $this->log("Миграции завершились с кодом: {$exitCode}");
        }

        // Очистка кэша
        $this->info('');
        $this->info('Очистка кэша...');
        $this->call('cache:clear');
        $this->call('config:cache');
        $this->ok('Кэш очищен и пересоздан');
        $this->log('Кэш очищен и пересоздан.');

        $this->info('');
        $this->info('=== Восстановление завершено успешно ===');
        $this->log('=== Восстановление завершено успешно ===');

        return 0;
    }

    private function resolveBackupFile(): ?string
    {
        if ($file = $this->option('file')) {
            if (!file_exists($file)) {
                $this->err("Файл не найден: {$file}");
                return null;
            }
            return $file;
        }

        if ($this->option('latest')) {
            return $this->findLatestBackup();
        }

        // Если ни один флаг не указан — ищем последний
        $this->warn('Не указан --file и --latest, используется последний бэкап.');
        return $this->findLatestBackup();
    }

    private function findLatestBackup(): ?string
    {
        if (!is_dir($this->backupDir)) {
            $this->err("Директория бэкапов не найдена: {$this->backupDir}");
            return null;
        }

        $files = glob("{$this->backupDir}/db_*.sql.gz.enc");
        if (empty($files)) {
            $this->err('Бэкапы не найдены в ' . $this->backupDir);
            return null;
        }

        sort($files);
        return end($files);
    }

    private function verifyDecryption(string $file): bool
    {
        $cmd = sprintf(
            'openssl enc -d -aes-256-cbc -pbkdf2 -pass env:BACKUP_ENCRYPTION_KEY -in %s | gunzip | head -c 1024 > /dev/null 2>&1',
            escapeshellarg($file)
        );

        exec($cmd, $output, $exitCode);
        return $exitCode === 0;
    }

    private function createSafetyBackup(): ?string
    {
        $dbName = config('database.connections.pgsql.database');
        $dbUser = config('database.connections.pgsql.username');
        $dbPassword = config('database.connections.pgsql.password');
        $dbHost = config('database.connections.pgsql.host', 'localhost');

        $date = date('Ymd_His');
        $safetyFile = "{$this->backupDir}/db_safety_{$date}.sql.gz.enc";

        @mkdir($this->backupDir, 0755, true);

        $encryptionKey = env('BACKUP_ENCRYPTION_KEY');
        $cmd = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -U %s %s --no-owner --no-privileges | gzip | openssl enc -aes-256-cbc -salt -pbkdf2 -pass env:BACKUP_ENCRYPTION_KEY > %s 2>&1',
            escapeshellarg($dbPassword),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbName),
            escapeshellarg($safetyFile)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($safetyFile) || filesize($safetyFile) < 1024) {
            @unlink($safetyFile);
            $this->log('Safety backup не удался: ' . implode("\n", $output));
            return null;
        }

        $this->log("Safety backup создан: {$safetyFile}");
        return $safetyFile;
    }

    private function restoreDatabase(string $backupFile): bool
    {
        $dbName = config('database.connections.pgsql.database');
        $dbUser = config('database.connections.pgsql.username');
        $dbPassword = config('database.connections.pgsql.password');
        $dbHost = config('database.connections.pgsql.host', 'localhost');

        $cmd = sprintf(
            'openssl enc -d -aes-256-cbc -pbkdf2 -pass env:BACKUP_ENCRYPTION_KEY -in %s | gunzip | PGPASSWORD=%s psql -h %s -U %s %s 2>&1',
            escapeshellarg($backupFile),
            escapeshellarg($dbPassword),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbName)
        );

        $this->log("Выполняю восстановление из: {$backupFile}");

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->log('Ошибка восстановления: ' . implode("\n", $output));
            return false;
        }

        $this->log('Восстановление БД выполнено, код: ' . $exitCode);
        return true;
    }

    private function setupLogging(): void
    {
        // Логируем в файл storage/logs/restore.log
        config(['logging.channels.restore' => [
            'driver' => 'single',
            'path' => storage_path('logs/restore.log'),
            'level' => 'debug',
        ]]);
    }

    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] {$message}";

        // Пишем напрямую в файл, т.к. канал может быть не зарегистрирован полностью
        $logFile = storage_path('logs/restore.log');
        file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function ok(string $msg): void
    {
        $this->line("  <fg=green>OK</> {$msg}");
    }

    private function err(string $msg): void
    {
        $this->line("  <fg=red>ERR</> {$msg}");
        $this->log("ERR: {$msg}");
    }
}
