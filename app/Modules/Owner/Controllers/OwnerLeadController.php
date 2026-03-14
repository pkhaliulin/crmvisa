<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Owner\Services\OwnerLeadService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerLeadController extends Controller
{
    public function __construct(
        private readonly OwnerLeadService $leadService,
    ) {}

    public function leads(Request $request): JsonResponse
    {
        return ApiResponse::success($this->leadService->listLeads($request));
    }

    public function leadUpdate(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status'              => 'sometimes|in:new,assigned,converted,rejected',
            'assigned_agency_id'  => 'sometimes|nullable|uuid',
        ]);

        $this->leadService->updateLead($id, $data);

        return ApiResponse::success(null, 'Лид обновлён');
    }
}
