<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            $table->string('embassy_name', 255)->nullable()->after('commission_rate');
            $table->string('embassy_address', 500)->nullable()->after('embassy_name');
            $table->string('embassy_city', 100)->nullable()->after('embassy_address');
            $table->string('embassy_phone', 50)->nullable()->after('embassy_city');
            $table->string('embassy_email', 255)->nullable()->after('embassy_phone');
            $table->string('submission_type', 20)->default('visa_center')->after('embassy_email');
            $table->string('visa_center_name', 255)->nullable()->after('submission_type');
            $table->string('visa_center_address', 500)->nullable()->after('visa_center_name');
            $table->string('visa_center_phone', 50)->nullable()->after('visa_center_address');
            $table->string('visa_center_website', 500)->nullable()->after('visa_center_phone');
            $table->decimal('latitude', 10, 7)->nullable()->after('visa_center_website');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('name_uz', 100)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            $table->dropColumn([
                'embassy_name', 'embassy_address', 'embassy_city',
                'embassy_phone', 'embassy_email', 'submission_type',
                'visa_center_name', 'visa_center_address', 'visa_center_phone',
                'visa_center_website', 'latitude', 'longitude', 'name_uz',
            ]);
        });
    }
};
