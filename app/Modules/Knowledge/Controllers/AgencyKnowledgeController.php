<?php

namespace App\Modules\Knowledge\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Knowledge\Models\AgencyKnowledgeNote;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyKnowledgeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AgencyKnowledgeNote::with('author:id,name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at');

        if ($request->filled('country_code')) {
            $query->byCountry($request->country_code);
        }
        if ($request->filled('visa_type')) {
            $query->where('visa_type', $request->visa_type);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', $search)
                  ->orWhere('content', 'ilike', $search);
            });
        }

        return ApiResponse::paginated($query->paginate($request->integer('per_page', 20)));
    }

    public function show(string $id): JsonResponse
    {
        $note = AgencyKnowledgeNote::with('author:id,name')->findOrFail($id);

        return ApiResponse::success($note);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'visa_type'    => ['nullable', 'string', 'max:50'],
            'category'     => ['nullable', 'string', 'in:' . implode(',', AgencyKnowledgeNote::categories())],
            'title'        => ['required', 'string', 'max:500'],
            'content'      => ['required', 'string'],
            'is_pinned'    => ['sometimes', 'boolean'],
        ]);

        $data['country_code'] = strtoupper($data['country_code']);
        $data['created_by']   = $request->user()->id;

        $note = AgencyKnowledgeNote::create($data);

        return ApiResponse::created($note->load('author:id,name'));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $note = AgencyKnowledgeNote::findOrFail($id);

        $data = $request->validate([
            'country_code' => ['sometimes', 'string', 'size:2'],
            'visa_type'    => ['nullable', 'string', 'max:50'],
            'category'     => ['nullable', 'string', 'in:' . implode(',', AgencyKnowledgeNote::categories())],
            'title'        => ['sometimes', 'string', 'max:500'],
            'content'      => ['sometimes', 'string'],
            'is_pinned'    => ['sometimes', 'boolean'],
        ]);

        if (! empty($data['country_code'])) {
            $data['country_code'] = strtoupper($data['country_code']);
        }

        $note->update($data);

        return ApiResponse::success($note->fresh()->load('author:id,name'));
    }

    public function destroy(string $id): JsonResponse
    {
        AgencyKnowledgeNote::findOrFail($id)->delete();

        return ApiResponse::success(null, 'Заметка удалена.');
    }

    public function togglePin(string $id): JsonResponse
    {
        $note = AgencyKnowledgeNote::findOrFail($id);
        $note->update(['is_pinned' => ! $note->is_pinned]);

        return ApiResponse::success($note->fresh());
    }

    public function share(string $id): JsonResponse
    {
        $note = AgencyKnowledgeNote::findOrFail($id);
        $note->update([
            'is_shared'         => true,
            'moderation_status' => 'pending',
        ]);

        return ApiResponse::success($note->fresh(), 'Заметка отправлена на модерацию.');
    }
}
