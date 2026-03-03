<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('category', 50)->index();   // lead_source, employment_type, etc.
            $table->string('code', 50);                 // direct, referral, employed, etc.
            $table->string('label_ru', 255);
            $table->string('label_uz', 255)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();       // extra config per item
            $table->timestamps();

            $table->unique(['category', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_items');
    }
};
