<?php

namespace App\Modules\LeadGen\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LeadGen\Models\LeadChannel;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadChannelAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LeadChannel::query()->orderBy('sort_order')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $s = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'ilike', $s)
                  ->orWhere('name_uz', 'ilike', $s)
                  ->orWhere('code', 'ilike', $s)
                  ->orWhere('category', 'ilike', $s);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $channels = $query->get();

        return ApiResponse::success([
            'channels' => $channels,
            'total'    => $channels->count(),
            'active'   => $channels->where('is_active', true)->count(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $channel = LeadChannel::findOrFail($id);
        return ApiResponse::success($channel);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'                   => ['required', 'string', 'max:50', 'unique:lead_channels,code'],
            'name'                   => ['required', 'string', 'max:255'],
            'name_uz'                => ['nullable', 'string', 'max:255'],
            'category'               => ['required', 'string', 'in:messenger,advertising,web,content_seo,partnership,api_automation'],
            'icon'                   => ['nullable', 'string', 'max:10'],
            'short_description'      => ['nullable', 'string', 'max:500'],
            'short_description_uz'   => ['nullable', 'string', 'max:500'],
            'full_description'       => ['nullable', 'string'],
            'full_description_uz'    => ['nullable', 'string'],
            'how_it_works'           => ['nullable', 'string'],
            'how_it_works_uz'        => ['nullable', 'string'],
            'when_to_use'            => ['nullable', 'string'],
            'when_not_to_use'        => ['nullable', 'string'],
            'use_cases'              => ['nullable', 'string'],
            'effectiveness'          => ['nullable', 'string', 'in:low,medium,high,very_high'],
            'effectiveness_factors'  => ['nullable', 'string'],
            'complexity'             => ['nullable', 'string', 'in:easy,medium,hard'],
            'launch_speed'           => ['nullable', 'string', 'in:instant,fast,medium,slow'],
            'requires_budget'        => ['nullable', 'boolean'],
            'requires_api'           => ['nullable', 'string', 'in:no,optional,required'],
            'enterprise_only'        => ['nullable', 'boolean'],
            'min_plan'               => ['nullable', 'string', 'in:trial,starter,pro,enterprise'],
            'required_preparation'   => ['nullable', 'string'],
            'expected_result'        => ['nullable', 'string'],
            'risks'                  => ['nullable', 'string'],
            'best_practices'         => ['nullable', 'string'],
            'trends'                 => ['nullable', 'string'],
            'recommended_for'        => ['nullable', 'string'],
            'cta_actions'            => ['nullable', 'array'],
            'sort_order'             => ['nullable', 'integer'],
            'is_active'              => ['nullable', 'boolean'],
            'coming_soon'            => ['nullable', 'boolean'],
        ]);

        $channel = LeadChannel::create($data);

        return ApiResponse::success($channel, 'Канал создан.', 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $channel = LeadChannel::findOrFail($id);

        $data = $request->validate([
            'code'                   => ['sometimes', 'string', 'max:50', 'unique:lead_channels,code,' . $id],
            'name'                   => ['sometimes', 'string', 'max:255'],
            'name_uz'                => ['nullable', 'string', 'max:255'],
            'category'               => ['sometimes', 'string', 'in:messenger,advertising,web,content_seo,partnership,api_automation'],
            'icon'                   => ['nullable', 'string', 'max:10'],
            'short_description'      => ['nullable', 'string', 'max:500'],
            'short_description_uz'   => ['nullable', 'string', 'max:500'],
            'full_description'       => ['nullable', 'string'],
            'full_description_uz'    => ['nullable', 'string'],
            'how_it_works'           => ['nullable', 'string'],
            'how_it_works_uz'        => ['nullable', 'string'],
            'when_to_use'            => ['nullable', 'string'],
            'when_not_to_use'        => ['nullable', 'string'],
            'use_cases'              => ['nullable', 'string'],
            'effectiveness'          => ['nullable', 'string', 'in:low,medium,high,very_high'],
            'effectiveness_factors'  => ['nullable', 'string'],
            'complexity'             => ['nullable', 'string', 'in:easy,medium,hard'],
            'launch_speed'           => ['nullable', 'string', 'in:instant,fast,medium,slow'],
            'requires_budget'        => ['nullable', 'boolean'],
            'requires_api'           => ['nullable', 'string', 'in:no,optional,required'],
            'enterprise_only'        => ['nullable', 'boolean'],
            'min_plan'               => ['nullable', 'string', 'in:trial,starter,pro,enterprise'],
            'required_preparation'   => ['nullable', 'string'],
            'expected_result'        => ['nullable', 'string'],
            'risks'                  => ['nullable', 'string'],
            'best_practices'         => ['nullable', 'string'],
            'trends'                 => ['nullable', 'string'],
            'recommended_for'        => ['nullable', 'string'],
            'cta_actions'            => ['nullable', 'array'],
            'sort_order'             => ['nullable', 'integer'],
            'is_active'              => ['nullable', 'boolean'],
            'coming_soon'            => ['nullable', 'boolean'],
        ]);

        $channel->update($data);

        return ApiResponse::success($channel->fresh(), 'Канал обновлён.');
    }

    public function toggle(string $id): JsonResponse
    {
        $channel = LeadChannel::findOrFail($id);
        $channel->update(['is_active' => !$channel->is_active]);

        return ApiResponse::success([
            'id'        => $channel->id,
            'is_active' => $channel->is_active,
        ], $channel->is_active ? 'Канал включён.' : 'Канал отключён. На странице агентства будет показано «СКОРО».');
    }

    public function destroy(string $id): JsonResponse
    {
        $channel = LeadChannel::findOrFail($id);
        $channel->delete();

        return ApiResponse::success(null, 'Канал удалён.');
    }
}
