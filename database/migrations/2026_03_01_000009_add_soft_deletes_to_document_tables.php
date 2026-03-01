<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('document_templates', 'deleted_at')) {
            Schema::table('document_templates', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('country_visa_requirements', 'deleted_at')) {
            Schema::table('country_visa_requirements', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('country_visa_requirements', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
