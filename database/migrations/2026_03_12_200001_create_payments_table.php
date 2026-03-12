<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->uuid('subscription_id')->nullable();
            $table->string('provider', 20); // click, payme, uzum
            $table->string('external_id')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('UZS');
            $table->string('status', 20)->default('pending'); // pending, processing, completed, failed, cancelled, refunded
            $table->jsonb('metadata')->default('{}');
            $table->timestamp('paid_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subscription_id')
                  ->references('id')
                  ->on('agency_subscriptions')
                  ->nullOnDelete();

            $table->index(['agency_id', 'status']);
            $table->index(['agency_id', 'created_at']);
        });

        // RLS policy для payments (мультитенант)
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE payments ENABLE ROW LEVEL SECURITY');
            DB::statement("
                CREATE POLICY payments_tenant_isolation ON payments
                USING (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                )
            ");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS payments_tenant_isolation ON payments');
        }
        Schema::dropIfExists('payments');
    }
};
