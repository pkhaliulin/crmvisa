<?php

namespace App\Modules\Document\Services;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\Document\Models\AiUsageLog;
use App\Modules\PublicPortal\Models\PublicUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис генерации документов через AI (Claude API).
 *
 * Типы документов:
 * - cover_letter — сопроводительное письмо для консульства
 * - employer_reference — справка с места работы
 * - sponsor_letter — спонсорское письмо
 * - travel_plan — план поездки
 *
 * Роли: owner, manager (не client).
 */
class DocumentGenerationService
{
    private ?string $agencyId = null;
    private ?string $userId = null;

    public function setContext(string $agencyId, ?string $userId = null): self
    {
        $this->agencyId = $agencyId;
        $this->userId = $userId;
        return $this;
    }

    /**
     * Сгенерировать документ.
     */
    public function generate(VisaCase $case, string $docType, array $params = []): array
    {
        $client = $case->client;
        $publicUser = $client?->publicUser;

        $prompt = $this->buildPrompt($docType, $case, $client, $publicUser, $params);
        $startTime = microtime(true);

        try {
            $result = $this->callClaude($prompt);
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            AiUsageLog::log(
                service: 'doc_generation',
                provider: 'anthropic',
                model: 'claude-haiku-4-5-20251001',
                usage: $result['usage'] ?? [],
                status: 'success',
                durationMs: $durationMs,
                agencyId: $this->agencyId,
                userId: $this->userId,
            );

            return [
                'type'     => $docType,
                'content'  => $result['text'],
                'language' => $params['language'] ?? 'ru',
                'metadata' => [
                    'case_number'  => $case->case_number,
                    'country_code' => $case->country_code,
                    'visa_type'    => $case->visa_type,
                    'generated_at' => now()->toIso8601String(),
                ],
            ];
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            AiUsageLog::log(
                service: 'doc_generation',
                provider: 'anthropic',
                model: 'claude-haiku-4-5-20251001',
                usage: [],
                status: 'error',
                error: $this->sanitizeError($e->getMessage()),
                durationMs: $durationMs,
                agencyId: $this->agencyId,
                userId: $this->userId,
            );

            Log::error('Document generation failed', [
                'doc_type' => $docType,
                'case_id'  => $case->id,
                'error'    => $this->sanitizeError($e->getMessage()),
            ]);

            throw $e;
        }
    }

    /**
     * Список доступных типов документов для генерации.
     */
    public static function availableTypes(): array
    {
        return [
            [
                'type'        => 'cover_letter',
                'name_ru'     => 'Сопроводительное письмо',
                'name_uz'     => 'Yo\'ldosh xat',
                'description' => 'Письмо для консульства с обоснованием цели поездки',
                'requires'    => ['travel_purpose'],
            ],
            [
                'type'        => 'employer_reference',
                'name_ru'     => 'Справка с места работы',
                'name_uz'     => 'Ish joyidan ma\'lumotnoma',
                'description' => 'Подтверждение трудоустройства, должности и дохода',
                'requires'    => ['employer_name', 'position', 'monthly_income'],
            ],
            [
                'type'        => 'sponsor_letter',
                'name_ru'     => 'Спонсорское письмо',
                'name_uz'     => 'Homiy xati',
                'description' => 'Письмо от спонсора, покрывающего расходы поездки',
                'requires'    => ['sponsor_name', 'sponsor_relation'],
            ],
            [
                'type'        => 'travel_plan',
                'name_ru'     => 'План поездки',
                'name_uz'     => 'Sayohat rejasi',
                'description' => 'Детальный маршрут и план пребывания',
                'requires'    => ['travel_date', 'return_date'],
            ],
        ];
    }

    // =========================================================================

    private function buildPrompt(string $docType, VisaCase $case, ?Client $client, ?PublicUser $pu, array $params): string
    {
        $lang = $params['language'] ?? 'ru';
        $langName = $lang === 'uz' ? 'узбекском' : ($lang === 'en' ? 'английском' : 'русском');

        $clientName = $pu?->name ?? $client?->name ?? $params['applicant_name'] ?? 'Заявитель';
        $country = $case->country_code ?? 'XX';
        $visaType = $case->visa_type ?? 'tourist';
        $travelDate = $case->travel_date?->format('d.m.Y') ?? $params['travel_date'] ?? '—';
        $returnDate = $params['return_date'] ?? '—';

        $base = "Сгенерируй официальный документ на {$langName} языке.\n";
        $base .= "Заявитель: {$clientName}\n";
        $base .= "Страна: {$country}, тип визы: {$visaType}\n";
        $base .= "Дата поездки: {$travelDate}\n";

        if ($pu) {
            if ($pu->employment_type) $base .= "Занятость: {$pu->employment_type}\n";
            if ($pu->monthly_income_usd) $base .= "Доход: \${$pu->monthly_income_usd}/мес\n";
            if ($pu->marital_status) $base .= "Семейное положение: {$pu->marital_status}\n";
        }

        return match ($docType) {
            'cover_letter' => $base . "\n" . $this->coverLetterPrompt($params, $travelDate, $returnDate, $country),
            'employer_reference' => $base . "\n" . $this->employerReferencePrompt($params, $pu),
            'sponsor_letter' => $base . "\n" . $this->sponsorLetterPrompt($params, $clientName),
            'travel_plan' => $base . "\n" . $this->travelPlanPrompt($params, $country, $travelDate, $returnDate),
            default => throw new \InvalidArgumentException("Unknown document type: {$docType}"),
        };
    }

    private function coverLetterPrompt(array $params, string $travelDate, string $returnDate, string $country): string
    {
        $purpose = $params['travel_purpose'] ?? 'туризм';
        return <<<PROMPT
Напиши сопроводительное письмо для подачи на визу.
Цель поездки: {$purpose}
Даты: {$travelDate} — {$returnDate}
Страна: {$country}

Требования:
- Формальный деловой стиль
- Обоснование цели поездки
- Подтверждение намерения вернуться на родину
- Указание финансовой состоятельности
- Краткое, 1 страница
- Формат: готовый текст без placeholder-ов
PROMPT;
    }

    private function employerReferencePrompt(array $params, ?PublicUser $pu): string
    {
        $employer = $params['employer_name'] ?? 'Компания';
        $position = $params['position'] ?? $pu?->employment_type ?? 'сотрудник';
        $income = $params['monthly_income'] ?? $pu?->monthly_income_usd ?? '—';
        $years = $params['years_employed'] ?? $pu?->employed_years ?? '—';

        return <<<PROMPT
Напиши справку с места работы для визового консульства.
Работодатель: {$employer}
Должность: {$position}
Стаж: {$years} лет
Зарплата: \${$income}/мес

Требования:
- Официальный бланк (текст)
- Подтверждение трудоустройства и должности
- Указание зарплаты и стажа
- Подтверждение предоставления отпуска на период поездки
- Обязательство сохранения рабочего места
- Формат: готовый текст
PROMPT;
    }

    private function sponsorLetterPrompt(array $params, string $clientName): string
    {
        $sponsorName = $params['sponsor_name'] ?? 'Спонсор';
        $relation = $params['sponsor_relation'] ?? 'родственник';

        return <<<PROMPT
Напиши спонсорское письмо для визового консульства.
Спонсор: {$sponsorName}
Отношение к заявителю: {$relation}
Заявитель: {$clientName}

Требования:
- Официальный стиль
- Обязательство покрыть все расходы (проживание, питание, транспорт, медстраховка)
- Указание финансовой состоятельности спонсора
- Формат: готовый текст
PROMPT;
    }

    private function travelPlanPrompt(array $params, string $country, string $travelDate, string $returnDate): string
    {
        $cities = $params['cities'] ?? '';
        $purpose = $params['travel_purpose'] ?? 'туризм';

        return <<<PROMPT
Составь детальный план поездки для визового консульства.
Страна: {$country}
Даты: {$travelDate} — {$returnDate}
Цель: {$purpose}
Города: {$cities}

Требования:
- По дням: дата, город, активность, размещение
- Реалистичный маршрут
- Табличный формат
- Без выдуманных названий отелей (использовать "Hotel в городе X")
PROMPT;
    }

    private function callClaude(string $prompt): array
    {
        $apiKey = config('services.ocr.anthropic_key');
        if (!$apiKey) {
            throw new \RuntimeException('ANTHROPIC_API_KEY not configured');
        }

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 2000,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Claude API error: ' . $response->status());
        }

        $data = $response->json();

        return [
            'text'  => $data['content'][0]['text'] ?? '',
            'usage' => [
                'prompt_tokens'     => $data['usage']['input_tokens'] ?? 0,
                'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
                'total_tokens'      => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
            ],
        ];
    }

    private function sanitizeError(string $message): string
    {
        $message = preg_replace('/(?:key|token|api_key|secret|authorization)[=:]\s*["\']?[\w\-\.]{10,}["\']?/i', '[REDACTED]', $message);
        $message = preg_replace('/Bearer\s+[\w\-\.]+/i', 'Bearer [REDACTED]', $message);
        return mb_substr($message, 0, 500);
    }
}
