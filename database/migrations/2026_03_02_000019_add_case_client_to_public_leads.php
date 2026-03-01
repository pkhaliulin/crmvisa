<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_leads', function (Blueprint $table) {
            $table->foreignUuid('case_id')->nullable()->constrained('cases')->nullOnDelete()->after('assigned_agency_id');
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete()->after('case_id');
        });

        // Добавить поле lat/lon для агентств (для карты)
        Schema::table('agencies', function (Blueprint $table) {
            $table->decimal('latitude',  10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('public_leads', function (Blueprint $table) {
            $table->dropForeign(['case_id']);
            $table->dropForeign(['client_id']);
            $table->dropColumn(['case_id', 'client_id']);
        });
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
