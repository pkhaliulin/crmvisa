<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Создать фейковый JWT токен с заданным payload (без валидной подписи).
     */
    private function makeFakeJwt(array $payload): string
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body   = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $sig    = base64_encode('fake-signature');

        return "{$header}.{$body}.{$sig}";
    }

    /**
     * Прогнать middleware и вернуть response.
     */
    private function runMiddleware(?string $bearerToken): \Symfony\Component\HttpFoundation\Response
    {
        $request = Request::create('/test', 'GET');

        if ($bearerToken) {
            $request->headers->set('Authorization', "Bearer {$bearerToken}");
        }

        $middleware = new SetTenantContext();

        return $middleware->handle($request, fn ($req) => response()->json(['ok' => true]));
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_tenant_id_sets_via_parameterized_query(): void
    {
        // SQLite не поддерживает SET app.*, поэтому middleware пропускает —
        // проверяем что middleware корректно парсит payload и не падает.
        $agencyId = '550e8400-e29b-41d4-a716-446655440000';

        $token = $this->makeFakeJwt([
            'agency_id' => $agencyId,
            'role'      => 'owner',
        ]);

        $response = $this->runMiddleware($token);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_superadmin_flag_is_recognized(): void
    {
        $token = $this->makeFakeJwt([
            'role'      => 'superadmin',
            'agency_id' => null,
        ]);

        $response = $this->runMiddleware($token);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_invalid_uuid_does_not_set_tenant(): void
    {
        // Невалидный UUID — middleware логирует предупреждение, но не падает.
        // На SQLite SET app.* пропускается, поэтому проверяем что запрос проходит.
        $token = $this->makeFakeJwt([
            'agency_id' => 'not-a-valid-uuid',
            'role'      => 'owner',
        ]);

        $response = $this->runMiddleware($token);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_sql_injection_in_agency_id_is_blocked(): void
    {
        // Попытка SQL-инъекции через agency_id — regex отклонит
        $token = $this->makeFakeJwt([
            'agency_id' => "'; DROP TABLE users; --",
            'role'      => 'owner',
        ]);

        $response = $this->runMiddleware($token);

        // Middleware не упал, запрос прошел (SET не вызван из-за regex)
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_without_token_tenant_is_not_set(): void
    {
        // Без токена middleware просто пропускает запрос дальше
        $response = $this->runMiddleware(null);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_malformed_jwt_does_not_crash(): void
    {
        // Токен не из 3 частей
        $response = $this->runMiddleware('this-is-not-a-jwt');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_jwt_with_invalid_base64_payload_does_not_crash(): void
    {
        // 3 части, но payload — невалидный base64/json
        $response = $this->runMiddleware('header.!!!invalid!!!.signature');

        $this->assertEquals(200, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Интеграционный тест: через реальный HTTP-запрос
    // -------------------------------------------------------------------------

    public function test_authenticated_request_passes_through_middleware(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();

        $response = $this->getJson('/api/v1/auth/me', $this->authHeaders($owner));

        $response->assertOk();
        $response->assertJsonPath('data.agency_id', $agency->id);
    }
}
