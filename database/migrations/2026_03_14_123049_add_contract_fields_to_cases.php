<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->timestamp('contract_accepted_at')->nullable();
            $table->string('contract_number', 30)->nullable();
            $table->integer('refund_amount')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by', 20)->nullable(); // agent, client
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['contract_accepted_at', 'contract_number', 'refund_amount', 'cancel_reason', 'cancelled_at', 'cancelled_by']);
        });
    }
};
