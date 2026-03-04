<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->uuid('public_user_id');
            $table->uuid('agency_id');
            $table->uuid('package_id')->nullable();
            $table->integer('amount');
            $table->string('currency', 3)->default('UZS');
            $table->string('provider', 20);
            $table->string('provider_transaction_id', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('case_id')->references('id')->on('cases');
            $table->foreign('public_user_id')->references('id')->on('public_users');
            $table->foreign('agency_id')->references('id')->on('agencies');
            $table->foreign('package_id')->references('id')->on('agency_service_packages');

            $table->index('case_id');
            $table->index('public_user_id');
            $table->index('agency_id');
            $table->index('status');
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('unpaid')->after('public_status');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });

        Schema::dropIfExists('client_payments');
    }
};
