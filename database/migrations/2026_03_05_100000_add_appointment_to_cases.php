<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->date('appointment_date')->nullable()->after('travel_date');
            $table->string('appointment_time', 10)->nullable()->after('appointment_date');
            $table->string('appointment_location', 255)->nullable()->after('appointment_time');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['appointment_date', 'appointment_time', 'appointment_location']);
        });
    }
};
