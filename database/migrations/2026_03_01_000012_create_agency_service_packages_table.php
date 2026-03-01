<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_service_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id');
            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
            $table->char('country_code', 2)->nullable();
            $table->string('visa_type', 50)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->integer('processing_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agency_service_package_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('package_id');
            $table->foreign('package_id')->references('id')->on('agency_service_packages')->cascadeOnDelete();
            $table->uuid('service_id');
            $table->foreign('service_id')->references('id')->on('global_services')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_service_package_items');
        Schema::dropIfExists('agency_service_packages');
    }
};
