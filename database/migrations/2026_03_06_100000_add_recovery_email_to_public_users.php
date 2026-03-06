<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_users', function (Blueprint $table) {
            $table->string('recovery_email', 255)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('public_users', function (Blueprint $table) {
            $table->dropColumn('recovery_email');
        });
    }
};
