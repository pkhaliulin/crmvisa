<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('legal_name', 500)->nullable();
            $table->string('legal_form', 30)->nullable();
            $table->string('inn', 20)->nullable();
            $table->text('legal_address')->nullable();
            $table->string('bank_account', 30)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_mfo', 10)->nullable();
            $table->string('director_name', 255)->nullable();
            $table->string('director_basis', 100)->nullable();
            $table->string('stamp_url', 500)->nullable();
            $table->jsonb('default_refund_policy')->nullable();
            $table->jsonb('default_payment_terms')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name',
                'legal_form',
                'inn',
                'legal_address',
                'bank_account',
                'bank_name',
                'bank_mfo',
                'director_name',
                'director_basis',
                'stamp_url',
                'default_refund_policy',
                'default_payment_terms',
            ]);
        });
    }
};
