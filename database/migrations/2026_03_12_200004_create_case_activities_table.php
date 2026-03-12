<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->uuid('user_id')->nullable();

            $table->string('type', 50); // stage_change, document_upload, document_approved, payment, message, note
            $table->text('description');
            $table->jsonb('metadata')->nullable();
            $table->boolean('is_internal')->default(false); // internal notes not visible to client

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('case_id')->references('id')->on('cases')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['case_id', 'created_at']);
            $table->index(['case_id', 'is_internal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_activities');
    }
};
