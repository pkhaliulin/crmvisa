<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_score_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_user_id');
            $table->string('country_code', 2);
            $table->decimal('score', 5, 2);
            $table->jsonb('breakdown')->nullable();
            $table->string('model_version', 20)->default('1.0');
            $table->decimal('red_flag_multiplier', 4, 2)->default(1.00);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->foreign('public_user_id')
                  ->references('id')
                  ->on('public_users')
                  ->cascadeOnDelete();

            $table->index(['public_user_id', 'country_code']);
            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_score_history');
    }
};
