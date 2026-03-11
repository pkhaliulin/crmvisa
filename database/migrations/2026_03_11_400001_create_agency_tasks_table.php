<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id');
            $table->uuid('created_by');
            $table->uuid('assigned_to')->nullable();
            $table->uuid('case_id')->nullable();

            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('medium');   // low, medium, high, urgent
            $table->string('status', 20)->default('new');        // new, accepted, completed, verified, closed, deferred, cancelled
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('completed_by')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Повторяющиеся задачи
            $table->string('recurrence_rule', 30)->nullable();   // daily, weekly, monthly, weekdays, mon, tue, wed, thu, fri
            $table->uuid('recurrence_parent_id')->nullable();    // ID оригинальной задачи (для созданных копий)

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('case_id')->references('id')->on('cases')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['agency_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['due_date']);
            $table->index(['recurrence_parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_tasks');
    }
};
