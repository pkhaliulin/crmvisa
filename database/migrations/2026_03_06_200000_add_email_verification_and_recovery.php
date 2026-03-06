<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Поле подтверждения email
        Schema::table('public_users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('recovery_email');
        });

        // Коды верификации email (4 цифры)
        Schema::create('public_email_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_user_id');
            $table->string('email', 255);
            $table->string('code', 4);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('public_user_id')->references('id')->on('public_users')->cascadeOnDelete();
            $table->index(['public_user_id', 'code', 'expires_at']);
        });

        // Токены восстановления доступа (recovery flow)
        Schema::create('public_recovery_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_user_id');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('public_user_id')->references('id')->on('public_users')->cascadeOnDelete();
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_recovery_tokens');
        Schema::dropIfExists('public_email_verifications');
        Schema::table('public_users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
