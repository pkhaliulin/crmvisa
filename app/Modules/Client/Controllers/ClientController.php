<?php

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Services\ClientService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(protected ClientService $service) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->filled('q')) {
            $clients = $this->service->search($request->q);

            return ApiResponse::success($clients);
        }

        $clients = $this->service->paginate(20);

        return ApiResponse::paginated($clients);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['nullable', 'email', 'max:255'],
            'phone'               => ['required', 'string', 'max:30'],
            'telegram_chat_id'    => ['nullable', 'string', 'max:50'],
            'passport_number'     => ['nullable', 'string', 'max:30'],
            'nationality'         => ['nullable', 'string', 'size:3'],
            'date_of_birth'       => ['nullable', 'date'],
            'passport_expires_at' => ['nullable', 'date'],
            'source'              => ['nullable', 'in:direct,referral,marketplace,other'],
            'notes'               => ['nullable', 'string'],
        ]);

        $client = $this->service->create($data);

        return ApiResponse::created($client);
    }

    public function show(string $id): JsonResponse
    {
        $client = $this->service->findOrFail($id);

        return ApiResponse::success($client->load(['cases' => fn ($q) => $q->with('assignee:id,name')->orderBy('created_at', 'desc')]));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name'                => ['sometimes', 'string', 'max:255'],
            'email'               => ['sometimes', 'nullable', 'email'],
            'phone'               => ['sometimes', 'nullable', 'string', 'max:30'],
            'telegram_chat_id'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'passport_number'     => ['sometimes', 'nullable', 'string', 'max:30'],
            'nationality'         => ['sometimes', 'nullable', 'string', 'size:3'],
            'date_of_birth'       => ['sometimes', 'nullable', 'date'],
            'passport_expires_at' => ['sometimes', 'nullable', 'date'],
            'source'              => ['sometimes', 'in:direct,referral,marketplace,other'],
            'notes'               => ['sometimes', 'nullable', 'string'],
        ]);

        $client = $this->service->update($id, $data);

        return ApiResponse::success($client);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return ApiResponse::success(null, 'Client deleted.');
    }

    /**
     * POST /clients/parse-passport
     * Загрузка фото паспорта. Сохраняет файл и возвращает распознанные поля.
     * Реальный OCR — TODO (будет через Google Document AI / queue).
     */
    public function parsePassport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $path = $request->file('file')->store('passports', 'public');

        // TODO: dispatch OCR job — пока возвращаем пустые поля
        return ApiResponse::success([
            'file_path'           => $path,
            'ocr_status'          => 'pending',
            'name'                => null,
            'date_of_birth'       => null,
            'passport_number'     => null,
            'passport_expires_at' => null,
            'nationality'         => null,
        ], 'Паспорт загружен. Распознавание данных будет добавлено в ближайшем обновлении.');
    }
}
