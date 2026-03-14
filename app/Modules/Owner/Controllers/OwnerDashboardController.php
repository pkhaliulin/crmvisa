<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Owner\Services\OwnerDashboardService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class OwnerDashboardController extends Controller
{
    public function __construct(
        private readonly OwnerDashboardService $dashboardService,
    ) {}

    public function dashboard(): JsonResponse
    {
        return ApiResponse::success($this->dashboardService->getStats());
    }
}
