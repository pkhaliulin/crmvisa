<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_checklist', function (Blueprint $table) {
            $table->string('responsibility', 10)->default('client')->after('is_required');
        });

        Schema::table('document_templates', function (Blueprint $table) {
            $table->string('default_responsibility', 10)->default('client')->after('is_required');
        });
    }

    public function down(): void
    {
        Schema::table('case_checklist', function (Blueprint $table) {
            $table->dropColumn('responsibility');
        });

        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn('default_responsibility');
        });
    }
};
