<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferenceController extends Controller
{
    /**
     * Все категории справочников с количеством элементов.
     */
    public function categories(): JsonResponse
    {
        $cats = DB::table('reference_items')
            ->selectRaw('category, COUNT(*) as items_count')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        return ApiResponse::success($cats);
    }

    /**
     * Элементы конкретной категории.
     */
    public function index(string $category): JsonResponse
    {
        $items = DB::table('reference_items')
            ->where('category', $category)
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success($items);
    }

    /**
     * Все элементы всех категорий (для фронта — один запрос).
     */
    public function all(): JsonResponse
    {
        $items = DB::table('reference_items')
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return ApiResponse::success($items);
    }

    /**
     * Создать элемент справочника.
     */
    public function store(Request $request, string $category): JsonResponse
    {
        $data = $request->validate([
            'code'        => 'required|string|max:50|regex:/^[a-z_]+$/',
            'label_ru'    => 'required|string|max:255',
            'label_uz'    => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'sort_order'  => 'sometimes|integer|min:0',
            'metadata'    => 'sometimes|nullable|array',
        ]);

        // Проверка уникальности
        $exists = DB::table('reference_items')
            ->where('category', $category)
            ->where('code', $data['code'])
            ->exists();

        if ($exists) {
            return ApiResponse::error("Код '{$data['code']}' уже существует в справочнике '$category'.", 422);
        }

        $id = Str::uuid()->toString();
        DB::table('reference_items')->insert(array_merge($data, [
            'id'         => $id,
            'category'   => $category,
            'is_active'  => true,
            'metadata'   => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $item = DB::table('reference_items')->where('id', $id)->first();

        return ApiResponse::created($item, 'Элемент добавлен');
    }

    /**
     * Обновить элемент.
     */
    public function update(Request $request, string $category, string $id): JsonResponse
    {
        $data = $request->validate([
            'label_ru'    => 'sometimes|string|max:255',
            'label_uz'    => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'sort_order'  => 'sometimes|integer|min:0',
            'is_active'   => 'sometimes|boolean',
            'metadata'    => 'sometimes|nullable|array',
        ]);

        if (isset($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }

        $data['updated_at'] = now();

        DB::table('reference_items')
            ->where('id', $id)
            ->where('category', $category)
            ->update($data);

        $item = DB::table('reference_items')->where('id', $id)->first();

        return ApiResponse::success($item);
    }

    /**
     * Удалить элемент.
     */
    public function destroy(string $category, string $id): JsonResponse
    {
        DB::table('reference_items')
            ->where('id', $id)
            ->where('category', $category)
            ->delete();

        return ApiResponse::success(null, 'Элемент удалён');
    }
}
