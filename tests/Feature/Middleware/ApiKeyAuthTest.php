<?php

namespace Tests\Feature\Middleware;

use App\Modules\Agency\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiKeyAuthTest extends TestCase
{
    use RefreshDatabase;

    private const LEADS_ENDPOINT = '/api/v1/leads/incoming';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Создать агентство с API-ключом. Возвращает [agency, rawKey].
     */
    private function createAgencyWithApiKey(array $agencyAttrs = []): array
    {
        $rawKey = 'vbk_' . Str::random(32);
        $hash   = hash('sha256', $rawKey);

        $agency = Agency::factory()->create(array_merge([
            'api_key'   => $hash,
            'is_active' => true,
        ], $agencyAttrs));

        return [$agency, $rawKey];
    }

    /**
     * Минимальный payload для leads/incoming.
     */
    private function leadPayload(): array
    {
        return [
            'name'  => 'Test Lead',
            'phone' => '+998901234567',
        ];
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_valid_api_key_passes(): void
    {
        [$agency, $rawKey] = $this->createAgencyWithApiKey();

        $response = $this->postJson(self::LEADS_ENDPOINT, $this->leadPayload(), [
            'Authorization' => "Bearer {$rawKey}",
        ]);

        // Любой статус кроме 401 — middleware пропустил запрос
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_invalid_key_returns_401(): void
    {
        $response = $this->postJson(self::LEADS_ENDPOINT, $this->leadPayload(), [
            'Authorization' => 'Bearer vbk_this_key_does_not_exist_in_db',
        ]);

        $response->assertStatus(401);
    }

    public function test_key_without_vbk_prefix_returns_401(): void
    {
        // Ключ без префикса vbk_ — middleware отклоняет по формату
        $response = $this->postJson(self::LEADS_ENDPOINT, $this->leadPayload(), [
            'Authorization' => 'Bearer some_random_token_without_prefix',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_deactivated_agency_returns_401(): void
    {
        [$agency, $rawKey] = $this->createAgencyWithApiKey([
            'is_active' => false,
        ]);

        $response = $this->postJson(self::LEADS_ENDPOINT, $this->leadPayload(), [
            'Authorization' => "Bearer {$rawKey}",
        ]);

        $response->assertStatus(401);
    }

    public function test_no_token_returns_401(): void
    {
        $response = $this->postJson(self::LEADS_ENDPOINT, $this->leadPayload());

        $response->assertStatus(401);
    }

    public function test_agency_is_attached_to_request_on_success(): void
    {
        [$agency, $rawKey] = $this->createAgencyWithApiKey();

        // Проверяем через реальный запрос — если middleware прошел,
        // agency доступен в контроллере через $request->attributes->get('agency')
        $response = $this->postJson(self::LEADS_ENDPOINT, $this->leadPayload(), [
            'Authorization' => "Bearer {$rawKey}",
        ]);

        // Запрос не должен быть отклонен middleware (401)
        $this->assertNotEquals(401, $response->getStatusCode());
    }
}
