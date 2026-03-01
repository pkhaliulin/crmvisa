<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'managers_see_all_cases')) {
                $table->boolean('managers_see_all_cases')->default(false);
            }
            if (! Schema::hasColumn('agencies', 'lead_assignment_mode')) {
                $table->string('lead_assignment_mode', 20)->default('manual');
            }
            if (! Schema::hasColumn('agencies', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'experience_years')) {
                $table->smallInteger('experience_years')->unsigned()->nullable();
            }
            if (! Schema::hasColumn('agencies', 'website_url')) {
                $table->string('website_url')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'city')) {
                $table->string('city')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'rating')) {
                $table->decimal('rating', 3, 2)->default(0);
            }
            if (! Schema::hasColumn('agencies', 'reviews_count')) {
                $table->integer('reviews_count')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'managers_see_all_cases',
                'lead_assignment_mode',
                'description',
                'experience_years',
                'website_url',
                'address',
                'city',
                'rating',
                'reviews_count',
            ]);
        });
    }
};
