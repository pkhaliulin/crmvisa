<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->text('description_uz')->nullable()->after('description');
        });

        Schema::table('agency_service_packages', function (Blueprint $table) {
            $table->string('name_uz')->nullable()->after('name');
            $table->text('description_uz')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn('description_uz');
        });

        Schema::table('agency_service_packages', function (Blueprint $table) {
            $table->dropColumn(['name_uz', 'description_uz']);
        });
    }
};
