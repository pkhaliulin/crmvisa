<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Owner\Services\MonitoringService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function __construct(
        private readonly MonitoringService $monitoring
    ) {}

    public function health(): JsonResponse
    {
        return ApiResponse::success($this->monitoring->health());
    }

    public function errors(Request $request): JsonResponse
    {
        $period = $request->input('period', '24h');

        return ApiResponse::success($this->monitoring->errors($period));
    }

    public function activity(Request $request): JsonResponse
    {
        $period = $request->input('period', '24h');

        return ApiResponse::success($this->monitoring->activity($period));
    }

    public function metrics(Request $request): JsonResponse
    {
        $period = $request->input('period', '24h');

        return ApiResponse::success($this->monitoring->metrics($period));
    }

    public function alerts(): JsonResponse
    {
        return ApiResponse::success($this->monitoring->alerts());
    }

    public function queue(): JsonResponse
    {
        return ApiResponse::success($this->monitoring->queue());
    }

    public function retryJob(string $id): JsonResponse
    {
        $job = DB::table('failed_jobs')->where('id', $id)->first();

        if (! $job) {
            return ApiResponse::notFound('Job not found.');
        }

        \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => [$job->uuid]]);

        return ApiResponse::success(null, 'Job queued for retry.');
    }

    public function deleteJob(string $id): JsonResponse
    {
        $deleted = DB::table('failed_jobs')->where('id', $id)->delete();

        if (! $deleted) {
            return ApiResponse::notFound('Job not found.');
        }

        return ApiResponse::success(null, 'Job deleted.');
    }
}
