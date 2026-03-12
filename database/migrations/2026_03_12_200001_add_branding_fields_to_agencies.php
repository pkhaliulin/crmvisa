<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'primary_color')) {
                $table->string('primary_color', 7)->nullable()->after('logo_url');
            }
            if (! Schema::hasColumn('agencies', 'secondary_color')) {
                $table->string('secondary_color', 7)->nullable()->after('primary_color');
            }
            if (! Schema::hasColumn('agencies', 'custom_domain')) {
                $table->string('custom_domain')->nullable()->after('secondary_color');
            }
            if (! Schema::hasColumn('agencies', 'favicon_url')) {
                $table->string('favicon_url')->nullable()->after('custom_domain');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'secondary_color', 'custom_domain', 'favicon_url']);
        });
    }
};
