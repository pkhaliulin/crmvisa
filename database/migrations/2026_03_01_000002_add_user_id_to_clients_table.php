<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Связь клиента с аккаунтом пользователя для личного кабинета
            $table->foreignUuid('user_id')->nullable()->unique()->after('id')
                  ->constrained('users')->nullOnDelete();
            $table->string('telegram_chat_id')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'telegram_chat_id']);
        });
    }
};
