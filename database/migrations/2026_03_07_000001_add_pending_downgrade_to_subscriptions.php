<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agency_subscriptions', function (Blueprint $table) {
            $table->string('pending_plan_slug')->nullable()->after('discount_amount');
            $table->string('pending_billing_period')->nullable()->after('pending_plan_slug');
            $table->timestamp('pending_change_at')->nullable()->after('pending_billing_period');
        });
    }

    public function down(): void
    {
        Schema::table('agency_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['pending_plan_slug', 'pending_billing_period', 'pending_change_at']);
        });
    }
};
