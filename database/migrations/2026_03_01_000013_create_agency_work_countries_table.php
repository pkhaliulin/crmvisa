<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_work_countries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id');
            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
            $table->char('country_code', 2);
            $table->jsonb('visa_types')->default('[]');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['agency_id', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_work_countries');
    }
};
