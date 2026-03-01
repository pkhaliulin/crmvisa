<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('agency_work_countries', 'deleted_at')) {
            Schema::table('agency_work_countries', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('agency_work_countries', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
