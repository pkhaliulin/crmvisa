<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->uuid('public_user_id')->nullable()->after('user_id');
            $table->foreign('public_user_id')->references('id')->on('public_users')->nullOnDelete();
            $table->index('public_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['public_user_id']);
            $table->dropColumn('public_user_id');
        });
    }
};
