<?php

namespace App\Modules\Document\Services;

use App\Modules\Document\DTOs\DocumentAnalysisResult;
use App\Modules\Document\Models\DocumentTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentAiAnalyzerService
{
    /**
     * Analyze a document image/PDF using AI.
     * Returns extracted data, validation results, confidence score, and risk indicators.
     */
    public function analyze(UploadedFile|string $file, DocumentTemplate $template, array $context = []): DocumentAnalysisResult
    {
        if (!$template->ai_enabled) {
            return DocumentAnalysisResult::skipped('AI отключен для этого типа документа');
        }

        $extractionSchema = $template->ai_extraction_schema ?? [];
        $validationRules = $template->ai_validation_rules ?? [];
        $stopFactors = $template->ai_stop_factors ?? [];
        $successFactors = $template->ai_success_factors ?? [];

        if (empty($extractionSchema)) {
            return DocumentAnalysisResult::skipped('Нет схемы извлечения для этого документа');
        }

        try {
            $imageData = $this->prepareImage($file);
            $prompt = $this->buildPrompt($template, $extractionSchema, $validationRules, $context);

            $rawResult = $this->callAi($imageData, $prompt);
            $extracted = $this->parseExtraction($rawResult, $extractionSchema);
            $validation = $this->validateExtracted($extracted, $validationRules, $context);
            $risks = $this->assessRisks($extracted, $validation, $stopFactors, $successFactors, $template);
            $confidence = $this->calculateConfidence($extracted, $validation, $extractionSchema);

            return new DocumentAnalysisResult(
                status: 'analyzed',
                extractedData: $extracted,
                validationResults: $validation,
                confidence: $confidence,
                riskIndicators: $risks['indicators'],
                stopFactors: $risks['stops'],
                successFactors: $risks['successes'],
                rawResponse: $rawResult,
            );
        } catch (\Throwable $e) {
            Log::error('Document AI analysis failed', [
                'template' => $template->slug,
                'error' => $e->getMessage(),
            ]);
            return DocumentAnalysisResult::failed($e->getMessage());
        }
    }

    private function prepareImage(UploadedFile|string $file): string
    {
        if ($file instanceof UploadedFile) {
            return base64_encode(file_get_contents($file->getRealPath()));
        }
        // Path to stored file
        $path = storage_path('app/' . $file);
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$file}");
        }
        return base64_encode(file_get_contents($path));
    }

    private function buildPrompt(DocumentTemplate $template, array $schema, array $rules, array $context): string
    {
        $fields = collect($schema)->map(fn($type, $key) => "- {$key} ({$type})")->implode("\n");

        $validationText = '';
        if (!empty($rules)) {
            $validationText = "\n\nВалидация:\n" . collect($rules)->map(fn($rule, $key) => "- {$key}: {$rule}")->implode("\n");
        }

        $contextText = '';
        if (!empty($context)) {
            if (isset($context['travel_date'])) {
                $contextText .= "\nДата поездки: {$context['travel_date']}";
            }
            if (isset($context['country_code'])) {
                $contextText .= "\nСтрана: {$context['country_code']}";
            }
            if (isset($context['visa_type'])) {
                $contextText .= "\nТип визы: {$context['visa_type']}";
            }
        }

        return <<<PROMPT
Ты — AI-аналитик визовых документов. Проанализируй загруженный документ "{$template->name}".

Извлеки следующие данные:
{$fields}
{$validationText}
{$contextText}

Верни JSON:
{
  "extracted": { ... все поля из схемы ... },
  "issues": [ ... массив найденных проблем ... ],
  "quality": "high" | "medium" | "low",
  "notes": "дополнительные заметки"
}

Если поле не удалось извлечь — верни null.
Если документ нечитаемый или не соответствует типу — укажи в issues.
PROMPT;
    }

    private function callAi(string $imageBase64, string $prompt): array
    {
        $provider = config('services.ocr.provider', 'openai');

        if ($provider === 'openai') {
            return $this->callOpenAi($imageBase64, $prompt);
        }

        return $this->callClaude($imageBase64, $prompt);
    }

    private function callOpenAi(string $imageBase64, string $prompt): array
    {
        $response = Http::withToken(config('services.ocr.openai_key'))
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => [
                                'url' => "data:image/jpeg;base64,{$imageBase64}",
                            ]],
                        ],
                    ],
                ],
                'max_tokens' => 2000,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        return json_decode($content, true) ?? [];
    }

    private function callClaude(string $imageBase64, string $prompt): array
    {
        $response = Http::withHeaders([
            'x-api-key' => config('services.ocr.claude_key'),
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 2000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image', 'source' => [
                            'type' => 'base64',
                            'media_type' => 'image/jpeg',
                            'data' => $imageBase64,
                        ]],
                        ['type' => 'text', 'text' => $prompt],
                    ],
                ],
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Claude API error: ' . $response->body());
        }

        $content = $response->json('content.0.text');
        return json_decode($content, true) ?? [];
    }

    private function parseExtraction(array $rawResult, array $schema): array
    {
        $extracted = $rawResult['extracted'] ?? [];
        $result = [];

        foreach ($schema as $key => $type) {
            $value = $extracted[$key] ?? null;
            $result[$key] = $this->castValue($value, $type);
        }

        return $result;
    }

    private function castValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'date' => $value, // Keep as string, frontend will parse
            'integer', 'int' => (int) $value,
            'float', 'decimal', 'money' => (float) $value,
            'boolean', 'bool' => (bool) $value,
            default => (string) $value,
        };
    }

    private function validateExtracted(array $extracted, array $rules, array $context): array
    {
        $results = [];

        foreach ($rules as $field => $rule) {
            $value = $extracted[$field] ?? null;
            $results[$field] = $this->applyRule($field, $value, $rule, $context);
        }

        return $results;
    }

    private function applyRule(string $field, mixed $value, string $rule, array $context): array
    {
        if ($value === null) {
            return ['status' => 'warning', 'message' => 'Не удалось извлечь значение'];
        }

        if ($rule === 'must_be_future') {
            try {
                $date = \Carbon\Carbon::parse($value);
                $ok = $date->isFuture();
                if (isset($context['travel_date'])) {
                    $travel = \Carbon\Carbon::parse($context['travel_date']);
                    $monthsLeft = $date->diffInMonths($travel, false);
                    if ($monthsLeft < 3) {
                        return ['status' => 'critical', 'message' => "Срок действия менее 3 мес. до поездки ({$value})"];
                    }
                    if ($monthsLeft < 6) {
                        return ['status' => 'warning', 'message' => "Срок действия менее 6 мес. до поездки ({$value})"];
                    }
                }
                return $ok
                    ? ['status' => 'ok', 'message' => "Дата в будущем ({$value})"]
                    : ['status' => 'critical', 'message' => "Документ просрочен ({$value})"];
            } catch (\Exception $e) {
                return ['status' => 'warning', 'message' => 'Не удалось разобрать дату'];
            }
        }

        if (str_starts_with($rule, '>=')) {
            $min = (float) substr($rule, 2);
            $numValue = (float) $value;
            return $numValue >= $min
                ? ['status' => 'ok', 'message' => "Сумма {$numValue} >= {$min}"]
                : ['status' => 'critical', 'message' => "Сумма {$numValue} < {$min} (минимум)"];
        }

        if ($rule === 'not_empty') {
            return !empty($value)
                ? ['status' => 'ok', 'message' => 'Заполнено']
                : ['status' => 'warning', 'message' => 'Пустое значение'];
        }

        if ($rule === 'must_cover_travel_period') {
            return ['status' => 'info', 'message' => 'Требуется ручная проверка покрытия дат'];
        }

        if (str_starts_with($rule, 'max_age_days:')) {
            $maxDays = (int) substr($rule, strlen('max_age_days:'));
            try {
                $date = \Carbon\Carbon::parse($value);
                $age = $date->diffInDays(now());
                return $age <= $maxDays
                    ? ['status' => 'ok', 'message' => "Документ от {$value} ({$age} дней назад)"]
                    : ['status' => 'critical', 'message' => "Документ устарел: {$age} дней (макс. {$maxDays})"];
            } catch (\Exception $e) {
                return ['status' => 'warning', 'message' => 'Не удалось определить дату документа'];
            }
        }

        return ['status' => 'info', 'message' => "Правило '{$rule}' не реализовано"];
    }

    private function assessRisks(array $extracted, array $validation, array $stopFactors, array $successFactors, DocumentTemplate $template): array
    {
        $indicators = [];
        $stops = [];
        $successes = [];

        foreach ($stopFactors as $factor) {
            $triggered = $this->checkStopFactor($factor, $extracted, $validation);
            if ($triggered) {
                $stops[] = $triggered;
            }
        }

        foreach ($successFactors as $factor) {
            $triggered = $this->checkSuccessFactor($factor, $extracted, $validation);
            if ($triggered) {
                $successes[] = $triggered;
            }
        }

        foreach ($validation as $field => $result) {
            if ($result['status'] === 'critical') {
                $indicators[] = [
                    'level' => 'critical',
                    'field' => $field,
                    'message' => $result['message'],
                ];
            } elseif ($result['status'] === 'warning') {
                $indicators[] = [
                    'level' => 'warning',
                    'field' => $field,
                    'message' => $result['message'],
                ];
            }
        }

        if ($template->max_age_days && isset($extracted['issue_date'])) {
            try {
                $issueDate = \Carbon\Carbon::parse($extracted['issue_date']);
                $age = $issueDate->diffInDays(now());
                if ($age > $template->max_age_days) {
                    $indicators[] = [
                        'level' => 'critical',
                        'field' => 'document_age',
                        'message' => "Документ выдан {$age} дней назад (макс. {$template->max_age_days})",
                    ];
                }
            } catch (\Exception $e) {
                // ignore
            }
        }

        return [
            'indicators' => $indicators,
            'stops' => $stops,
            'successes' => $successes,
        ];
    }

    private function checkStopFactor(string $factor, array $extracted, array $validation): ?array
    {
        return match ($factor) {
            'passport_expired' => isset($validation['expiry_date']) && $validation['expiry_date']['status'] === 'critical'
                ? ['code' => 'passport_expired', 'message' => 'Паспорт просрочен — подача невозможна', 'severity' => 'blocker']
                : null,
            'insufficient_funds' => isset($validation['balance']) && $validation['balance']['status'] === 'critical'
                ? ['code' => 'insufficient_funds', 'message' => 'Недостаточно средств на счёте', 'severity' => 'blocker']
                : null,
            'insurance_expired' => isset($validation['end_date']) && $validation['end_date']['status'] === 'critical'
                ? ['code' => 'insurance_expired', 'message' => 'Страховка не покрывает период поездки', 'severity' => 'blocker']
                : null,
            'document_too_old' => collect($validation)->contains(fn($v) => str_contains($v['message'] ?? '', 'устарел'))
                ? ['code' => 'document_too_old', 'message' => 'Документ просрочен (старше допустимого)', 'severity' => 'blocker']
                : null,
            'coverage_insufficient' => isset($validation['coverage_amount']) && $validation['coverage_amount']['status'] === 'critical'
                ? ['code' => 'coverage_insufficient', 'message' => 'Сумма покрытия страховки ниже минимума', 'severity' => 'blocker']
                : null,
            'no_return_ticket' => isset($extracted['return_date']) && empty($extracted['return_date'])
                ? ['code' => 'no_return_ticket', 'message' => 'Нет обратного билета', 'severity' => 'warning']
                : null,
            default => null,
        };
    }

    private function checkSuccessFactor(string $factor, array $extracted, array $validation): ?array
    {
        return match ($factor) {
            'high_balance' => isset($extracted['balance']) && (float) ($extracted['balance'] ?? 0) > 5000
                ? ['code' => 'high_balance', 'message' => 'Высокий остаток на счёте (> $5000)', 'impact' => 'positive']
                : null,
            'stable_income' => isset($extracted['monthly_income']) && (float) ($extracted['monthly_income'] ?? 0) > 2000
                ? ['code' => 'stable_income', 'message' => 'Стабильный доход выше среднего', 'impact' => 'positive']
                : null,
            'previous_visas' => isset($extracted['previous_visas_count']) && (int) ($extracted['previous_visas_count'] ?? 0) > 0
                ? ['code' => 'previous_visas', 'message' => 'Есть визовая история', 'impact' => 'positive']
                : null,
            'property_owner' => isset($extracted['property_type']) && !empty($extracted['property_type'])
                ? ['code' => 'property_owner', 'message' => 'Владеет недвижимостью (привязка к стране)', 'impact' => 'strong_positive']
                : null,
            'long_employment' => isset($extracted['employment_years']) && (int) ($extracted['employment_years'] ?? 0) >= 3
                ? ['code' => 'long_employment', 'message' => 'Стаж работы 3+ лет (стабильность)', 'impact' => 'positive']
                : null,
            'family_ties' => true
                ? ['code' => 'family_ties', 'message' => 'Семья в стране проживания (привязка)', 'impact' => 'positive']
                : null,
            default => null,
        };
    }

    private function calculateConfidence(array $extracted, array $validation, array $schema): int
    {
        if (empty($schema)) {
            return 0;
        }

        $totalFields = count($schema);
        $extractedFields = collect($extracted)->filter(fn($v) => $v !== null)->count();
        $criticalIssues = collect($validation)->filter(fn($v) => ($v['status'] ?? '') === 'critical')->count();
        $warnings = collect($validation)->filter(fn($v) => ($v['status'] ?? '') === 'warning')->count();

        $extractionRate = $extractedFields / $totalFields;
        $baseConfidence = (int) ($extractionRate * 100);

        $penalty = ($criticalIssues * 30) + ($warnings * 10);

        return max(0, min(100, $baseConfidence - $penalty));
    }
}
