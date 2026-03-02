<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agency_reviews', function (Blueprint $table) {
            $table->uuid('case_id')->nullable()->after('public_user_id');
            $table->foreign('case_id')->references('id')->on('cases')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agency_reviews', function (Blueprint $table) {
            $table->dropForeign(['case_id']);
            $table->dropColumn('case_id');
        });
    }
};
