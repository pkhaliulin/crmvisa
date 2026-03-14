<?php

namespace App\Support\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditLog
{
    /**
     * Логировать действие в файл И в БД.
     *
     * @param string $action       Действие (finance.payment_recorded, case.cancelled и т.д.)
     * @param array  $context      Контекст (case_id, amount, reason и т.д.)
     * @param object|null $model   Модель, к которой привязано действие (для morph relation)
     */
    public static function log(string $action, array $context = [], $model = null): void
    {
        $user = Auth::user();
        $ip = request()->ip();
        $userAgent = request()->userAgent();

        // Файловый лог (как раньше)
        Log::channel('audit')->info($action, array_merge($context, [
            'ip'         => $ip,
            'user_agent' => $userAgent,
            'timestamp'  => now()->toIso8601String(),
        ]));

        // БД лог (для UI и аналитики)
        try {
            DB::table('audit_logs')->insert([
                'id'             => Str::uuid()->toString(),
                'action'         => $action,
                'user_id'        => $user?->id ?? ($context['user_id'] ?? null),
                'agency_id'      => $user?->agency_id ?? ($context['agency_id'] ?? null),
                'user_role'      => $user?->role,
                'user_name'      => $user?->name,
                'auditable_type' => $model ? get_class($model) : ($context['auditable_type'] ?? null),
                'auditable_id'   => $model?->id ?? ($context['case_id'] ?? $context['auditable_id'] ?? null),
                'context'        => json_encode($context),
                'changes'        => isset($context['changes']) ? json_encode($context['changes']) : null,
                'ip'             => $ip,
                'user_agent'     => mb_substr($userAgent ?? '', 0, 500),
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            // Не блокируем основную операцию если аудит-лог не записался
            Log::warning('AuditLog DB write failed: ' . $e->getMessage());
        }
    }
}
