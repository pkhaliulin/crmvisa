<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            $table->string('referral_embassy_country', 100)->nullable()->after('embassy_rules');
            $table->string('referral_embassy_city', 100)->nullable()->after('referral_embassy_country');
            $table->string('referral_embassy_name', 255)->nullable()->after('referral_embassy_city');
            $table->string('referral_embassy_address', 500)->nullable()->after('referral_embassy_name');
            $table->string('referral_embassy_website', 500)->nullable()->after('referral_embassy_address');
            $table->string('submission_procedure', 50)->nullable()->after('referral_embassy_website');
            $table->text('no_embassy_notes')->nullable()->after('submission_procedure');
        });
    }

    public function down(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            $table->dropColumn([
                'referral_embassy_country',
                'referral_embassy_city',
                'referral_embassy_name',
                'referral_embassy_address',
                'referral_embassy_website',
                'submission_procedure',
                'no_embassy_notes',
            ]);
        });
    }
};
