<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('country_visa_type_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_stay_days')->nullable()->after('buffer_days');
        });
    }

    public function down(): void
    {
        Schema::table('country_visa_type_settings', function (Blueprint $table) {
            $table->dropColumn('max_stay_days');
        });
    }
};
