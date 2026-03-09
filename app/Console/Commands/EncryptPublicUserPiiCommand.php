<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptPublicUserPiiCommand extends Command
{
    protected $signature = 'public-users:encrypt-pii {--dry-run : Show what would be encrypted without making changes}';
    protected $description = 'Encrypt existing plaintext PII data in public_users and documents tables';

    private array $publicUserFields = ['passport_number', 'passport_expires_at', 'dob', 'ocr_raw_data'];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
        }

        $this->encryptPublicUsers($dryRun);
        $this->encryptDocuments($dryRun);

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function encryptPublicUsers(bool $dryRun): void
    {
        $rows = DB::table('public_users')->get(['id', ...$this->publicUserFields]);
        $bar = $this->output->createProgressBar($rows->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% public_users');
        $encrypted = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $updates = [];

            foreach ($this->publicUserFields as $field) {
                $value = $row->{$field};

                if ($value === null || $value === '') {
                    continue;
                }

                if ($this->isAlreadyEncrypted($value)) {
                    $skipped++;
                    continue;
                }

                $updates[$field] = Crypt::encryptString($value);
            }

            if (! empty($updates) && ! $dryRun) {
                DB::table('public_users')->where('id', $row->id)->update($updates);
                $encrypted++;
            } elseif (! empty($updates)) {
                $encrypted++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("public_users: {$encrypted} rows encrypted, {$skipped} fields already encrypted.");
    }

    private function encryptDocuments(bool $dryRun): void
    {
        $rows = DB::table('documents')
            ->whereNotNull('extracted_data')
            ->whereNull('deleted_at')
            ->get(['id', 'extracted_data']);

        $bar = $this->output->createProgressBar($rows->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% documents');
        $encrypted = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $value = $row->extracted_data;

            if ($value === null || $value === '') {
                $bar->advance();
                continue;
            }

            if ($this->isAlreadyEncrypted($value)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (! $dryRun) {
                DB::table('documents')->where('id', $row->id)->update([
                    'extracted_data' => Crypt::encryptString($value),
                ]);
            }

            $encrypted++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("documents: {$encrypted} rows encrypted, {$skipped} fields already encrypted.");
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        return str_starts_with($value, 'eyJ');
    }
}
