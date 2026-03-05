<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_users', function (Blueprint $table) {
            $table->string('education_level', 30)->nullable()->after('employed_years');
        });
    }

    public function down(): void
    {
        Schema::table('public_users', function (Blueprint $table) {
            $table->dropColumn('education_level');
        });
    }
};
