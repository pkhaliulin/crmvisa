<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'user_id')) {
                $table->foreignUuid('user_id')->nullable()->unique()->after('id')
                      ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('clients', 'telegram_chat_id')) {
                $table->string('telegram_chat_id')->nullable()->after('notes');
            }
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
