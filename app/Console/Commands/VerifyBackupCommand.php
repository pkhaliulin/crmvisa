<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifyBackupCommand extends Command
{
    protected $signature = 'app:verify-backup';
    protected $description = 'Verify latest backup exists and is recent enough';

    public function handle(): int
    {
        $backupDir = '/var/backups/crmvisa';

        if (!is_dir($backupDir)) {
            $this->error("Backup directory not found: {$backupDir}");
            return 1;
        }

        $files = glob("{$backupDir}/db_*.sql.gz.enc");
        if (empty($files)) {
            $this->error('No database backups found!');
            return 1;
        }

        sort($files);
        $latest = end($files);
        $mtime = filemtime($latest);
        $age = time() - $mtime;
        $ageHours = round($age / 3600, 1);
        $size = filesize($latest);

        $this->info("Latest backup: " . basename($latest));
        $this->info("Size: " . number_format($size / 1024 / 1024, 2) . " MB");
        $this->info("Age: {$ageHours} hours");

        if ($age > 86400 + 3600) { // >25 hours (allowing 1h margin)
            $this->error("Backup is too old ({$ageHours}h)! RPO violated.");
            return 1;
        }

        if ($size < 1024) { // Less than 1KB - probably empty/corrupt
            $this->error("Backup is suspiciously small ({$size} bytes)!");
            return 1;
        }

        $totalBackups = count($files);
        $this->info("Total backups: {$totalBackups}");
        $this->info('Backup verification: OK');

        return 0;
    }
}
