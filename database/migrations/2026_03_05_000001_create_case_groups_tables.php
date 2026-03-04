<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('initiator_public_user_id');
            $table->string('name', 255)->nullable();
            $table->string('country_code', 2);
            $table->string('visa_type', 50);
            $table->uuid('agency_id')->nullable();
            $table->string('payment_strategy', 20)->default('individual');
            $table->string('status', 20)->default('forming');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('initiator_public_user_id')->references('id')->on('public_users');
            $table->foreign('agency_id')->references('id')->on('agencies')->nullOnDelete();
            $table->index('initiator_public_user_id');
            $table->index('status');
        });

        Schema::create('case_group_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('group_id');
            $table->string('phone', 20);
            $table->uuid('public_user_id')->nullable();
            $table->uuid('case_id')->nullable();
            $table->uuid('client_id')->nullable();
            $table->string('name', 255)->nullable();
            $table->string('role', 20)->default('member');
            $table->string('status', 20)->default('invited');
            $table->boolean('payment_covered')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('group_id')->references('id')->on('case_groups')->cascadeOnDelete();
            $table->foreign('public_user_id')->references('id')->on('public_users')->nullOnDelete();
            $table->foreign('case_id')->references('id')->on('cases')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->index('group_id');
            $table->index('phone');
            $table->index('public_user_id');
            $table->unique(['group_id', 'phone']);
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->uuid('group_id')->nullable()->after('agency_id');
            $table->foreign('group_id')->references('id')->on('case_groups')->nullOnDelete();
            $table->index('group_id');
        });

        Schema::table('client_payments', function (Blueprint $table) {
            $table->uuid('group_id')->nullable()->after('case_id');
            $table->foreign('group_id')->references('id')->on('case_groups')->nullOnDelete();
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('client_payments', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropIndex(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropIndex(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::dropIfExists('case_group_members');
        Schema::dropIfExists('case_groups');
    }
};
