<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 10);
            $table->string('path', 500);
            $table->smallInteger('status_code');
            $table->integer('response_time_ms');
            $table->string('ip', 45);
            $table->string('user_agent', 500)->nullable();
            $table->integer('request_body_size')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('status_code');
            $table->index(['method', 'path']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
