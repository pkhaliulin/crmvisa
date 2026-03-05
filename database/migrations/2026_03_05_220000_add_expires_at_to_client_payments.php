<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_payments', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('paid_at');
        });

        // Проставить expires_at для существующих pending платежей (created_at + 5 дней)
        DB::statement("UPDATE client_payments SET expires_at = created_at + INTERVAL '5 days' WHERE status = 'pending' AND expires_at IS NULL");
    }

    public function down(): void
    {
        Schema::table('client_payments', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
