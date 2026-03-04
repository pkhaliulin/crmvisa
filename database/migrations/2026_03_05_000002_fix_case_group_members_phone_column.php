<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // phone хранится зашифрованным (encrypted cast) — base64 JSON ~200 символов
        // VARCHAR(20) слишком мал, нужен TEXT
        // Также убираем индексы на phone — encrypted данные не индексируются
        Schema::table('case_group_members', function (Blueprint $table) {
            $table->dropUnique(['group_id', 'phone']);
            $table->dropIndex(['phone']);
        });

        Schema::table('case_group_members', function (Blueprint $table) {
            $table->text('phone')->change();
        });
    }

    public function down(): void
    {
        Schema::table('case_group_members', function (Blueprint $table) {
            $table->string('phone', 20)->change();
            $table->index('phone');
            $table->unique(['group_id', 'phone']);
        });
    }
};
