<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_code', 2);
            $table->string('visa_type', 50);
            $table->unsignedSmallInteger('min_days');
            $table->unsignedSmallInteger('max_days');
            $table->unsignedSmallInteger('warning_days')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['country_code', 'visa_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_rules');
    }
};
