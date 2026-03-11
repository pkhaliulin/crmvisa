<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptAllPiiCommand extends Command
{
    protected $signature = 'pii:encrypt-all {--dry-run : Show what would be encrypted without making changes}';
    protected $description = 'Encrypt all remaining plaintext PII data across all tables';

    private array $tables = [
        'public_users' => [
            'fields'    => ['recovery_email', 'dob', 'passport_expires_at', 'passport_number'],
            'softDelete' => false,
        ],
        'clients' => [
            'fields'    => ['phone', 'passport_number', 'date_of_birth', 'passport_expires_at'],
            'softDelete' => true,
        ],
        'public_user_family_members' => [
            'fields'    => ['passport_number', 'passport_expires_at', 'dob'],
            'softDelete' => false,
        ],
        'marketplace_leads' => [
            'fields'    => ['client_name', 'client_phone', 'client_email'],
            'softDelete' => false,
        ],
        'client_profiles' => [
            'fields'    => ['employer_name'],
            'softDelete' => false,
        ],
        'case_group_members' => [
            'fields'    => ['name'],
            'softDelete' => true,
        ],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
        }

        $totalEncrypted = 0;
        $totalSkipped = 0;

        foreach ($this->tables as $table => $config) {
            [$encrypted, $skipped] = $this->encryptTable($table, $config['fields'], $config['softDelete'], $dryRun);
            $totalEncrypted += $encrypted;
            $totalSkipped += $skipped;
        }

        $this->newLine();
        $this->info("Total: {$totalEncrypted} rows encrypted, {$totalSkipped} fields already encrypted.");

        return self::SUCCESS;
    }

    private function encryptTable(string $table, array $fields, bool $softDelete, bool $dryRun): array
    {
        $query = DB::table($table);

        if ($softDelete) {
            $query->whereNull('deleted_at');
        }

        $rows = $query->get(['id', ...$fields]);
        $encrypted = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($rows->count());
        $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%% {$table}");

        foreach ($rows as $row) {
            $updates = [];

            foreach ($fields as $field) {
                $value = $row->{$field};

                if ($value === null || $value === '') {
                    continue;
                }

                if ($this->isAlreadyEncrypted($value)) {
                    $skipped++;
                    continue;
                }

                $updates[$field] = Crypt::encryptString((string) $value);
            }

            if (! empty($updates) && ! $dryRun) {
                DB::table($table)->where('id', $row->id)->update($updates);
                $encrypted++;
            } elseif (! empty($updates)) {
                $encrypted++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$table}: {$encrypted} rows encrypted, {$skipped} fields already encrypted.");

        return [$encrypted, $skipped];
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        return str_starts_with($value, 'eyJ');
    }
}
