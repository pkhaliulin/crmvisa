<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('year');
            $table->smallInteger('month')->nullable();
            $table->unsignedInteger('target_clients')->default(0);
            $table->unsignedInteger('target_revenue')->default(0);
            $table->unsignedInteger('target_cases')->default(0);
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['agency_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_goals');
    }
};
