<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_visa_types', function (Blueprint $table) {
            $table->string('name_uz', 100)->nullable()->after('name_ru');
        });
    }

    public function down(): void
    {
        Schema::table('portal_visa_types', function (Blueprint $table) {
            $table->dropColumn('name_uz');
        });
    }
};
