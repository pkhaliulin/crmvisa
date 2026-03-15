<?php

namespace App\Support\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Trait для Job-классов, которым нужен tenant context.
 *
 * При dispatch сохраняет agency_id, при handle устанавливает
 * SET app.current_tenant_id для RLS-политик PostgreSQL.
 *
 * Использование:
 *   use HasTenantJob;
 *   public function __construct(Model $model) {
 *       $this->captureTenant($model->agency_id);
 *   }
 *   public function handle(): void {
 *       $this->setTenantContext();
 *       // ... бизнес-логика
 *   }
 */
trait HasTenantJob
{
    public ?string $tenantId = null;

    protected function captureTenant(?string $agencyId): void
    {
        $this->tenantId = $agencyId;
    }

    protected function setTenantContext(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if ($this->tenantId) {
            DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$this->tenantId]);
        } else {
            // Без tenant — superadmin mode для глобальных jobs
            DB::statement("SELECT set_config('app.is_superadmin', 'true', false)");
        }
    }

    protected function clearTenantContext(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("SELECT set_config('app.current_tenant_id', '', false)");
        DB::statement("SELECT set_config('app.is_superadmin', 'false', false)");
    }
}
