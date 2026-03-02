<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->string('public_status', 30)->default('submitted')->after('stage');
        });

        // Для уже существующих записей выставим draft если нет agency_id, иначе submitted
        DB::statement("UPDATE cases SET public_status = CASE WHEN agency_id IS NULL THEN 'draft' ELSE 'submitted' END");

        // Делаем agency_id nullable
        Schema::table('cases', function (Blueprint $table) {
            $table->uuid('agency_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn('public_status');
            $table->uuid('agency_id')->nullable(false)->change();
        });
    }
};
