<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptClientPiiCommand extends Command
{
    protected $signature = 'clients:encrypt-pii {--dry-run : Show what would be encrypted without making changes}';
    protected $description = 'Encrypt existing plaintext PII data in clients and users tables';

    private array $clientFields = ['phone', 'passport_number', 'date_of_birth', 'passport_expires_at'];
    private array $userFields = ['phone'];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
        }

        $this->encryptTable('clients', $this->clientFields, $dryRun);
        $this->encryptTable('users', $this->userFields, $dryRun);

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function encryptTable(string $table, array $fields, bool $dryRun): void
    {
        $rows = DB::table($table)->whereNull('deleted_at')->get(['id', ...$fields]);
        $encrypted = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $updates = [];

            foreach ($fields as $field) {
                $value = $row->{$field};

                if ($value === null || $value === '') {
                    continue;
                }

                // Пропускаем уже зашифрованные данные (base64-json формат Laravel)
                if ($this->isAlreadyEncrypted($value)) {
                    $skipped++;
                    continue;
                }

                $updates[$field] = Crypt::encryptString($value);
            }

            if (! empty($updates)) {
                if (! $dryRun) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
                $encrypted++;
            }
        }

        $this->info("{$table}: {$encrypted} rows encrypted, {$skipped} fields already encrypted.");
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        // Laravel encrypted values start with eyJ (base64 JSON)
        return str_starts_with($value, 'eyJ');
    }
}
