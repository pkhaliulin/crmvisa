<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('name_uz', 150)->nullable();
            $table->string('category', 50);
            $table->string('icon', 50)->nullable();
            $table->text('short_description');
            $table->text('short_description_uz')->nullable();
            $table->text('full_description')->nullable();
            $table->text('full_description_uz')->nullable();
            $table->text('how_it_works')->nullable();
            $table->text('how_it_works_uz')->nullable();
            $table->text('when_to_use')->nullable();
            $table->text('when_not_to_use')->nullable();
            $table->text('use_cases')->nullable();
            $table->string('effectiveness', 20)->default('medium');
            $table->text('effectiveness_factors')->nullable();
            $table->string('complexity', 20)->default('medium');
            $table->string('launch_speed', 20)->default('1_week');
            $table->boolean('requires_budget')->default(false);
            $table->string('requires_api', 20)->default('no');
            $table->boolean('enterprise_only')->default(false);
            $table->string('min_plan', 20)->default('starter');
            $table->text('required_preparation')->nullable();
            $table->text('expected_result')->nullable();
            $table->text('risks')->nullable();
            $table->text('best_practices')->nullable();
            $table->text('trends')->nullable();
            $table->string('recommended_for', 30)->default('all');
            $table->text('cta_actions')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('coming_soon')->default(false);
            $table->timestamps();
        });

        Schema::create('lead_channel_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id');
            $table->uuid('user_id');
            $table->uuid('channel_id');
            $table->string('action', 30);
            $table->timestamps();

            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
            $table->foreign('channel_id')->references('id')->on('lead_channels')->cascadeOnDelete();
            $table->index(['channel_id', 'action']);
            $table->index(['agency_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_channel_views');
        Schema::dropIfExists('lead_channels');
    }
};
