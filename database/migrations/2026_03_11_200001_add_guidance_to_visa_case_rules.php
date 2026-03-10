<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visa_case_rules', function (Blueprint $table) {
            $table->jsonb('guidance')->nullable()->after('workflow_steps');
        });
    }

    public function down(): void
    {
        Schema::table('visa_case_rules', function (Blueprint $table) {
            $table->dropColumn('guidance');
        });
    }
};
