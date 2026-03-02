<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_users', function (Blueprint $table) {
            $table->boolean('had_deportation')->default(false)->after('had_overstay');
            $table->smallInteger('visas_obtained_count')->default(0)->after('had_deportation');
            $table->smallInteger('refusals_count')->default(0)->after('visas_obtained_count');
            $table->text('refusal_countries')->nullable()->after('refusals_count'); // JSON array
            $table->smallInteger('last_refusal_year')->nullable()->after('refusal_countries');
            $table->smallInteger('employed_years')->default(0)->after('last_refusal_year');
        });
    }

    public function down(): void
    {
        Schema::table('public_users', function (Blueprint $table) {
            $table->dropColumn([
                'had_deportation',
                'visas_obtained_count',
                'refusals_count',
                'refusal_countries',
                'last_refusal_year',
                'employed_years',
            ]);
        });
    }
};
