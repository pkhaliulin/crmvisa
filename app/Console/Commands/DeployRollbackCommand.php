<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class DeployRollbackCommand extends Command
{
    protected $signature = 'app:rollback {--commit= : SHA коммита для отката}';
    protected $description = 'Откат деплоя на указанный коммит (или HEAD~1). Выполняет git checkout, composer install, миграции и кеширование.';

    private string $logFile;

    public function handle(): int
    {
        $this->logFile = storage_path('logs/deploy.log');
        $commit = $this->option('commit') ?: 'HEAD~1';

        $currentSha = $this->git('rev-parse HEAD');
        if (!$currentSha) {
            $this->err('Не удалось определить текущий коммит');
            return 1;
        }

        $this->log("=== ROLLBACK START ===");
        $this->log("Текущий коммит: {$currentSha}");
        $this->log("Откат на: {$commit}");
        $this->info("Текущий коммит: {$currentSha}");
        $this->info("Откат на: {$commit}");

        // 1. Проверяем что целевой коммит существует
        $targetSha = $this->git("rev-parse {$commit}");
        if (!$targetSha) {
            $this->err("Коммит {$commit} не найден");
            return 1;
        }
        $this->log("Целевой SHA: {$targetSha}");
        $this->info("Целевой SHA: {$targetSha}");

        if ($currentSha === $targetSha) {
            $this->warn('Текущий коммит совпадает с целевым — откат не требуется');
            $this->log("Откат не требуется — коммиты совпадают");
            return 0;
        }

        // 2. git checkout
        $this->step('git checkout');
        if (!$this->exec("git checkout {$targetSha} --force")) {
            $this->err("git checkout не удался");
            return 1;
        }

        // 3. composer install --no-dev
        $this->step('composer install --no-dev');
        if (!$this->exec('composer install --no-dev --no-interaction --optimize-autoloader')) {
            $this->err("composer install не удался");
            return 1;
        }

        // 4. Миграции (если есть новые)
        $this->step('php artisan migrate --force');
        if (!$this->exec('php artisan migrate --force')) {
            $this->warn('Миграция завершилась с ошибкой — возможно, нет pending миграций');
        }

        // 5. Кеширование
        $this->step('Кеширование конфигов, маршрутов, вьюх');
        $this->exec('php artisan config:cache');
        $this->exec('php artisan route:cache');
        $this->exec('php artisan view:cache');

        // 6. Результат
        $newSha = $this->git('rev-parse HEAD');
        $this->log("Откат завершён. Новый HEAD: {$newSha}");
        $this->log("=== ROLLBACK END ===");
        $this->newLine();
        $this->info("Откат завершён успешно.");
        $this->info("Предыдущий коммит: {$currentSha}");
        $this->info("Текущий коммит:    {$newSha}");
        $this->line("Лог: {$this->logFile}");

        return 0;
    }

    private function git(string $cmd): ?string
    {
        $result = Process::path(base_path())->run("git {$cmd}");

        if (!$result->successful()) {
            return null;
        }

        return trim($result->output());
    }

    private function exec(string $cmd): bool
    {
        $result = Process::path(base_path())
            ->timeout(300)
            ->run($cmd);

        $output = trim($result->output() . "\n" . $result->errorOutput());

        if (!$result->successful()) {
            $this->log("[FAIL] {$cmd}");
            if ($output) {
                $this->log($output);
            }
            $this->line("  <fg=red>FAIL</> {$cmd}");
            if ($output) {
                $this->line("  " . str_replace("\n", "\n  ", $output));
            }
            return false;
        }

        $this->log("[OK] {$cmd}");
        $this->line("  <fg=green>OK</> {$cmd}");
        return true;
    }

    private function step(string $name): void
    {
        $this->newLine();
        $this->info("[*] {$name}");
        $this->log("[STEP] {$name}");
    }

    private function err(string $msg): void
    {
        $this->error($msg);
        $this->log("[ERROR] {$msg}");
        $this->log("=== ROLLBACK FAILED ===");
    }

    private function log(string $message): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $line = "[{$timestamp}] {$message}\n";

        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
