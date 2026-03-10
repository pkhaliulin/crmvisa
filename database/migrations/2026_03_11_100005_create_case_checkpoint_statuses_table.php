<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_checkpoint_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->uuid('checkpoint_id');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->uuid('completed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('case_id')->references('id')->on('cases')->cascadeOnDelete();
            $table->foreign('checkpoint_id')->references('id')->on('visa_case_checkpoints')->cascadeOnDelete();
            $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['case_id', 'checkpoint_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_checkpoint_statuses');
    }
};
