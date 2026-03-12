<?php

namespace App\Modules\Document\Models;

use App\Support\Abstracts\BaseModel;

class AiUsageLog extends BaseModel
{
    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'agency_id',
        'case_id',
        'user_id',
        'service',
        'provider',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost_usd',
        'status',
        'error_message',
        'duration_ms',
    ];

    protected $casts = [
        'prompt_tokens'     => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens'      => 'integer',
        'cost_usd'          => 'float',
        'duration_ms'       => 'integer',
    ];

    // Стоимость за 1M токенов (input/output) по моделям
    private const PRICING = [
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],    // $/1M tokens
        'gpt-4o'      => ['input' => 2.50, 'output' => 10.00],
        'claude-haiku-4-5-20251001' => ['input' => 0.80, 'output' => 4.00],
        'claude-sonnet-4-20250514'  => ['input' => 3.00, 'output' => 15.00],
    ];

    /**
     * Рассчитать стоимость на основе токенов и модели.
     */
    public static function calculateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        $pricing = self::PRICING[$model] ?? ['input' => 0.50, 'output' => 2.00];

        return ($promptTokens * $pricing['input'] + $completionTokens * $pricing['output']) / 1_000_000;
    }

    /**
     * Быстрая запись лога использования AI.
     */
    public static function log(
        string  $service,
        string  $provider,
        string  $model,
        array   $usage,
        string  $status = 'success',
        ?string $error = null,
        ?int    $durationMs = null,
        ?string $agencyId = null,
        ?string $caseId = null,
        ?string $userId = null,
    ): self {
        $promptTokens     = $usage['prompt_tokens'] ?? 0;
        $completionTokens = $usage['completion_tokens'] ?? 0;
        $totalTokens      = $usage['total_tokens'] ?? ($promptTokens + $completionTokens);

        return self::create([
            'agency_id'         => $agencyId,
            'case_id'           => $caseId,
            'user_id'           => $userId,
            'service'           => $service,
            'provider'          => $provider,
            'model'             => $model,
            'prompt_tokens'     => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens'      => $totalTokens,
            'cost_usd'          => self::calculateCost($model, $promptTokens, $completionTokens),
            'status'            => $status,
            'error_message'     => $error,
            'duration_ms'       => $durationMs,
        ]);
    }
}
