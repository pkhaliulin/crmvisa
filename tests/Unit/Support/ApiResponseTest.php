<?php

namespace Tests\Unit\Support;

use App\Support\Helpers\ApiResponse;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_success_returns_200(): void
    {
        $response = ApiResponse::success(['id' => 1]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['data']['id']);
    }

    public function test_created_returns_201(): void
    {
        $response = ApiResponse::created(['id' => 1]);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_error_returns_correct_status_code(): void
    {
        // Bug: ApiResponse::error("msg", 422) передавал 422 как $errors, не $statusCode
        $response = ApiResponse::error('fail', null, 422);
        $this->assertEquals(422, $response->getStatusCode());

        $response = ApiResponse::error('fail', null, 503);
        $this->assertEquals(503, $response->getStatusCode());
    }

    public function test_error_default_is_400(): void
    {
        $response = ApiResponse::error('fail');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test_error_with_errors_and_status(): void
    {
        $response = ApiResponse::error('Validation', ['name' => 'required'], 422);
        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals(['name' => 'required'], $data['errors']);
    }

    public function test_not_found_returns_404(): void
    {
        $response = ApiResponse::notFound();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_unauthorized_returns_401(): void
    {
        $response = ApiResponse::unauthorized();
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_forbidden_returns_403(): void
    {
        $response = ApiResponse::forbidden();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_validation_error_returns_422(): void
    {
        $response = ApiResponse::validationError(['phone' => 'invalid']);
        $this->assertEquals(422, $response->getStatusCode());
    }
}
