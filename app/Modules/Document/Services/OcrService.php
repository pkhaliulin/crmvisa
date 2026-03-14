<?php

namespace App\Modules\Document\Services;

use App\Modules\Document\DTOs\PassportData;
use App\Modules\Document\Models\AiUsageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OCR Provider Cost Comparison (per passport scan):
 * 1. Google Cloud Vision: ~$0.0015/scan (cheapest, but needs MRZ parsing)
 * 2. Claude Sonnet:       ~$0.004/scan  (good accuracy, structured output)
 * 3. GPT-4o-mini:         ~$0.005/scan  (good accuracy, structured output)
 * 4. Mindee:              ~$0.10/scan   (best accuracy, expensive) / 250 free/month
 * Recommendation: Start with Claude or Google Vision, switch to cheapest that works well
 */
class OcrService
{
    private const EXTRACTION_PROMPT = <<<'PROMPT'
Extract identity document data from this image. Return JSON with these fields:

Required fields:
- document_type: "foreign_passport", "id_card", or "internal_passport"
  (foreign_passport = international travel passport with MRZ zone;
   id_card = biometric ID card;
   internal_passport = domestic/internal passport without MRZ)
- first_name, last_name, middle_name (as written in the document)
- first_name_latin, last_name_latin (transliterated to Latin if original is Cyrillic)
- first_name_cyrillic, last_name_cyrillic (if document contains Cyrillic text)
- script_type: "latin", "cyrillic", or "mixed" (what script the name fields are in)
- passport_number (document number)
- nationality (ISO 3-letter code, e.g. UZB, RUS, KAZ, TUR)
- date_of_birth (YYYY-MM-DD)
- date_of_expiry (YYYY-MM-DD)
- date_of_issue (YYYY-MM-DD)
- gender (M or F)
- place_of_birth
- issuing_authority
- pnfl (Personal Number / JSHSHIR / PINFL if visible on document, null otherwise)
- mrz_line1, mrz_line2 (only for documents with MRZ zone)
- confidence (0.0 to 1.0, your estimate of extraction accuracy)

If the image is not a valid identity document, return:
{"document_type": null, "error": "description of what was uploaded instead", "confidence": 0.0}

If the image shows wrong side of document or is too blurry:
{"document_type": null, "error": "wrong_side" or "too_blurry", "confidence": 0.0}

Return ONLY valid JSON, no explanation.
PROMPT;

    /**
     * Извлечь данные паспорта из изображения.
     *
     * @param string $imagePath Абсолютный путь к файлу изображения на диске
     */
    private ?string $agencyId = null;
    private ?string $userId = null;

    public function setContext(?string $agencyId, ?string $userId = null): self
    {
        $this->agencyId = $agencyId;
        $this->userId = $userId;
        return $this;
    }

    public function extractPassport(string $imagePath): PassportData
    {
        $provider = config('services.ocr.provider', 'claude');

        Log::info('OCR passport extraction started', [
            'provider' => $provider,
            'path'     => $imagePath,
        ]);

        $start = microtime(true);

        try {
            $result = match ($provider) {
                'claude' => $this->extractViaClaude($imagePath),
                'openai' => $this->extractViaOpenAI($imagePath),
                'google' => $this->extractViaGoogleVision($imagePath),
                'mindee' => $this->extractViaMindee($imagePath),
                default  => throw new \RuntimeException("Unknown OCR provider: {$provider}"),
            };

            $durationMs = (int) ((microtime(true) - $start) * 1000);

            Log::info('OCR passport extraction completed', [
                'provider'   => $provider,
                'confidence' => $result->confidence,
                'has_mrz'    => $result->mrzLine1 !== null,
            ]);

            // Логируем usage если есть данные
            if (!empty($this->lastUsage)) {
                AiUsageLog::log(
                    service: 'ocr_passport',
                    provider: $this->lastUsage['provider'],
                    model: $this->lastUsage['model'],
                    usage: $this->lastUsage['tokens'],
                    durationMs: $durationMs,
                    agencyId: $this->agencyId,
                    userId: $this->userId,
                );
            }

            return $result;
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $start) * 1000);

            AiUsageLog::log(
                service: 'ocr_passport',
                provider: $provider,
                model: $provider === 'openai' ? 'gpt-4o-mini' : ($provider === 'claude' ? 'claude-sonnet-4-20250514' : $provider),
                usage: $this->lastUsage['tokens'] ?? [],
                status: 'error',
                error: mb_substr($e->getMessage(), 0, 500),
                durationMs: $durationMs,
                agencyId: $this->agencyId,
                userId: $this->userId,
            );

            Log::error('OCR passport extraction failed', [
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private ?array $lastUsage = null;

    // ---------------------------------------------------------------
    //  Claude (Anthropic) — claude-sonnet-4-20250514
    // ---------------------------------------------------------------

    private function extractViaClaude(string $imagePath): PassportData
    {
        $apiKey = config('services.ocr.anthropic_key');
        if (empty($apiKey)) {
            throw new \RuntimeException('ANTHROPIC_API_KEY is not configured');
        }

        $base64  = $this->imageToBase64($imagePath);
        $mime    = $this->imageMimeType($imagePath);

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-sonnet-4-20250514',
            'max_tokens' => 1024,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mime,
                                'data'       => $base64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => self::EXTRACTION_PROMPT,
                        ],
                    ],
                ],
            ],
        ]);

        $response->throw();

        $body  = $response->json();
        $text  = $body['content'][0]['text'] ?? '';
        $usage = $body['usage'] ?? [];

        $this->lastUsage = [
            'provider' => 'claude',
            'model'    => 'claude-sonnet-4-20250514',
            'tokens'   => [
                'prompt_tokens'     => $usage['input_tokens'] ?? 0,
                'completion_tokens' => $usage['output_tokens'] ?? 0,
                'total_tokens'      => ($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0),
            ],
        ];

        return $this->parseJsonResponse($text, 'claude', json_encode($body));
    }

    // ---------------------------------------------------------------
    //  OpenAI — gpt-4o-mini
    // ---------------------------------------------------------------

    private function extractViaOpenAI(string $imagePath): PassportData
    {
        $apiKey = config('services.ocr.openai_key');
        if (empty($apiKey)) {
            throw new \RuntimeException('OPENAI_API_KEY is not configured');
        }

        $base64 = $this->imageToBase64($imagePath);
        $mime   = $this->imageMimeType($imagePath);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model'       => 'gpt-4o-mini',
            'max_tokens'  => 1024,
            'messages'    => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'      => 'image_url',
                            'image_url' => [
                                'url'    => "data:{$mime};base64,{$base64}",
                                'detail' => 'high',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => self::EXTRACTION_PROMPT,
                        ],
                    ],
                ],
            ],
        ]);

        $response->throw();

        $body = $response->json();
        $text = $body['choices'][0]['message']['content'] ?? '';

        $this->lastUsage = [
            'provider' => 'openai',
            'model'    => 'gpt-4o-mini',
            'tokens'   => $body['usage'] ?? [],
        ];

        return $this->parseJsonResponse($text, 'openai', json_encode($body));
    }

    // ---------------------------------------------------------------
    //  Google Cloud Vision — TEXT_DETECTION + MRZ parsing
    // ---------------------------------------------------------------

    private function extractViaGoogleVision(string $imagePath): PassportData
    {
        $credentialsPath = config('services.ocr.google_credentials_path');
        if (empty($credentialsPath) || !file_exists($credentialsPath)) {
            throw new \RuntimeException('GOOGLE_APPLICATION_CREDENTIALS is not configured or file not found');
        }

        $accessToken = $this->getGoogleAccessToken($credentialsPath);
        $base64      = $this->imageToBase64($imagePath);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type'  => 'application/json',
        ])->timeout(60)->post('https://vision.googleapis.com/v1/images:annotate', [
            'requests' => [
                [
                    'image'    => ['content' => $base64],
                    'features' => [
                        ['type' => 'TEXT_DETECTION', 'maxResults' => 1],
                    ],
                ],
            ],
        ]);

        $response->throw();

        $body     = $response->json();
        $fullText = $body['responses'][0]['textAnnotations'][0]['description'] ?? '';

        // Парсим MRZ из распознанного текста
        return $this->parseMrzFromText($fullText, json_encode($body));
    }

    /**
     * Получить access token из service account JSON.
     */
    private function getGoogleAccessToken(string $credentialsPath): string
    {
        $credentials = json_decode(file_get_contents($credentialsPath), true);

        $now    = time();
        $header = self::base64urlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = self::base64urlEncode(json_encode([
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-vision',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $signatureInput = "{$header}.{$claims}";
        openssl_sign($signatureInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = "{$signatureInput}." . self::base64urlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        $response->throw();

        return $response->json('access_token');
    }

    /**
     * Парсинг MRZ строк для паспорта (TD3 формат — 2 строки по 44 символа).
     */
    private function parseMrzFromText(string $text, string $rawResponse): PassportData
    {
        // Ищем MRZ строки (начинаются с P<)
        preg_match_all('/[P][A-Z<][A-Z<]{3}[A-Z<]{39}/', $text, $matches1);
        preg_match_all('/[A-Z0-9<]{44}/', $text, $matches2);

        $mrzLine1 = $matches1[0][0] ?? null;
        $mrzLine2 = null;

        // Вторая строка MRZ (содержит цифры и <)
        foreach ($matches2[0] ?? [] as $line) {
            if ($line !== $mrzLine1 && preg_match('/\d/', $line)) {
                $mrzLine2 = $line;
                break;
            }
        }

        $data = [
            'mrz_line1' => $mrzLine1,
            'mrz_line2' => $mrzLine2,
            'confidence' => ($mrzLine1 && $mrzLine2) ? 0.7 : 0.3,
        ];

        // Парсим данные из MRZ
        if ($mrzLine1) {
            // P<UTOSURNAME<<FIRSTNAME<MIDDLENAME<<<<<<<<<<
            $namePart    = substr($mrzLine1, 5);
            $nameParts   = explode('<<', $namePart, 2);
            $data['last_name']  = str_replace('<', ' ', trim($nameParts[0] ?? ''));
            $givenNames         = str_replace('<', ' ', trim($nameParts[1] ?? ''));
            $givenParts         = preg_split('/\s+/', $givenNames, 2);
            $data['first_name'] = $givenParts[0] ?? null;
            $data['middle_name'] = $givenParts[1] ?? null;
            $data['nationality'] = str_replace('<', '', substr($mrzLine1, 2, 3));
        }

        if ($mrzLine2 && strlen($mrzLine2) >= 44) {
            $data['passport_number'] = str_replace('<', '', substr($mrzLine2, 0, 9));
            $data['nationality']     = $data['nationality'] ?? str_replace('<', '', substr($mrzLine2, 10, 3));
            $data['date_of_birth']   = $this->parseMrzDate(substr($mrzLine2, 13, 6));
            $data['gender']          = match (substr($mrzLine2, 20, 1)) {
                'M' => 'M', 'F' => 'F', default => null,
            };
            $data['date_of_expiry'] = $this->parseMrzDate(substr($mrzLine2, 21, 6));
        }

        return PassportData::fromArray($data, 'google', $rawResponse);
    }

    /**
     * YYMMDD -> YYYY-MM-DD
     */
    private function parseMrzDate(string $raw): ?string
    {
        if (strlen($raw) !== 6 || !ctype_digit($raw)) {
            return null;
        }

        $yy = (int) substr($raw, 0, 2);
        $mm = substr($raw, 2, 2);
        $dd = substr($raw, 4, 2);

        // Если год > 30, считаем 19xx, иначе 20xx
        $year = $yy > 30 ? 1900 + $yy : 2000 + $yy;

        return sprintf('%04d-%s-%s', $year, $mm, $dd);
    }

    // ---------------------------------------------------------------
    //  Mindee — Passport API v1
    // ---------------------------------------------------------------

    private function extractViaMindee(string $imagePath): PassportData
    {
        $apiKey = config('services.ocr.mindee_key');
        if (empty($apiKey)) {
            throw new \RuntimeException('MINDEE_API_KEY is not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => "Token {$apiKey}",
        ])->timeout(60)->attach(
            'document', file_get_contents($imagePath), basename($imagePath)
        )->post('https://api.mindee.net/v1/products/mindee/passport/v1/predict');

        $response->throw();

        $body       = $response->json();
        $prediction = $body['document']['inference']['prediction'] ?? [];

        $data = [
            'first_name'        => $this->mindeeValue($prediction, 'given_names'),
            'last_name'         => $this->mindeeValue($prediction, 'surname'),
            'passport_number'   => $this->mindeeValue($prediction, 'id_number'),
            'nationality'       => $this->mindeeValue($prediction, 'country'),
            'date_of_birth'     => $this->mindeeValue($prediction, 'birth_date'),
            'date_of_expiry'    => $this->mindeeValue($prediction, 'expiry_date'),
            'date_of_issue'     => $this->mindeeValue($prediction, 'issuance_date'),
            'gender'            => $this->mindeeValue($prediction, 'gender'),
            'place_of_birth'    => $this->mindeeValue($prediction, 'birth_place'),
            'mrz_line1'         => $prediction['mrz1']['value'] ?? null,
            'mrz_line2'         => $prediction['mrz2']['value'] ?? null,
            'confidence'        => 0.9, // Mindee обычно имеет высокую точность
        ];

        // given_names в Mindee — это массив объектов
        if (isset($prediction['given_names']) && is_array($prediction['given_names'])) {
            $names = array_map(fn ($n) => $n['value'] ?? '', $prediction['given_names']);
            $data['first_name']  = $names[0] ?? null;
            $data['middle_name'] = $names[1] ?? null;
        }

        return PassportData::fromArray($data, 'mindee', json_encode($body));
    }

    private function mindeeValue(array $prediction, string $key): ?string
    {
        $field = $prediction[$key] ?? null;
        if ($field === null) {
            return null;
        }

        if (is_array($field) && isset($field['value'])) {
            return $field['value'] ?: null;
        }

        return null;
    }

    // ---------------------------------------------------------------
    //  Helpers
    // ---------------------------------------------------------------

    private function imageToBase64(string $imagePath): string
    {
        if (!file_exists($imagePath)) {
            throw new \RuntimeException("Image file not found: {$imagePath}");
        }

        return base64_encode(file_get_contents($imagePath));
    }

    private function imageMimeType(string $imagePath): string
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'gif'         => 'image/gif',
            'pdf'         => 'application/pdf',
            default       => 'image/jpeg',
        };
    }

    /**
     * Парсим JSON из ответа LLM (Claude / OpenAI).
     * Поддерживает ответы с markdown-обёрткой ```json ... ```
     */
    private function parseJsonResponse(string $text, string $provider, string $rawResponse): PassportData
    {
        // Убираем возможную markdown-обёртку
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/s', '', $text);
            $text = preg_replace('/\s*```$/s', '', $text);
        }

        $data = json_decode($text, true);
        if (!is_array($data)) {
            Log::warning('OCR: failed to parse JSON response', [
                'provider' => $provider,
                'text'     => mb_substr($text, 0, 500),
            ]);

            return new PassportData(
                rawResponse: $rawResponse,
                provider:    $provider,
            );
        }

        return PassportData::fromArray($data, $provider, $rawResponse);
    }

    /**
     * Base64url encode (RFC 4648) без padding.
     */
    private static function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
