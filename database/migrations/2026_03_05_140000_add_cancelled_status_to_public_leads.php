<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // Удаляем старый CHECK constraint и создаём новый с добавлением 'cancelled'
            DB::statement('ALTER TABLE public_leads DROP CONSTRAINT IF EXISTS public_leads_status_check');
            DB::statement("ALTER TABLE public_leads ADD CONSTRAINT public_leads_status_check CHECK (status IN ('new', 'contacted', 'assigned', 'converted', 'cancelled'))");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE public_leads DROP CONSTRAINT IF EXISTS public_leads_status_check');
            DB::statement("ALTER TABLE public_leads ADD CONSTRAINT public_leads_status_check CHECK (status IN ('new', 'contacted', 'assigned', 'converted'))");
        }
    }
};
