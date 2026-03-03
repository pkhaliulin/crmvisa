<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Owner\Models\FeatureFlag;
use App\Support\Facades\Feature;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureFlagController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            FeatureFlag::orderBy('key')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key'              => 'required|string|max:100|unique:feature_flags,key',
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'enabled'          => 'boolean',
            'rollout_percent'  => 'integer|min:0|max:100',
            'plans'            => 'nullable|array',
            'plans.*'          => 'string|in:trial,starter,pro,enterprise',
            'metadata'         => 'nullable|array',
        ]);

        $flag = FeatureFlag::create($data);

        return ApiResponse::created($flag);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $flag = FeatureFlag::findOrFail($id);

        $data = $request->validate([
            'name'             => 'sometimes|string|max:255',
            'description'      => 'nullable|string',
            'enabled'          => 'boolean',
            'rollout_percent'  => 'integer|min:0|max:100',
            'plans'            => 'nullable|array',
            'plans.*'          => 'string|in:trial,starter,pro,enterprise',
            'metadata'         => 'nullable|array',
        ]);

        $flag->update($data);

        Feature::flush($flag->key);

        return ApiResponse::success($flag->fresh());
    }

    public function destroy(string $id): JsonResponse
    {
        $flag = FeatureFlag::findOrFail($id);

        Feature::flush($flag->key);

        $flag->delete();

        return ApiResponse::success(null, 'Feature flag deleted.');
    }
}
