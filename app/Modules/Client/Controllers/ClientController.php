<?php

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Requests\StoreClientRequest;
use App\Modules\Client\Requests\UpdateClientRequest;
use App\Modules\Client\Resources\ClientResource;
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

            return ApiResponse::success(ClientResource::collection($clients));
        }

        $clients = $this->service->paginate(20);

        return ApiResponse::paginated($clients, 'Success', ClientResource::class);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $data = $request->validated();

        $client = $this->service->create($data);

        return ApiResponse::created(new ClientResource($client));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $client = $this->service->findOrFail($id);
        $this->authorize('view', $client);

        return ApiResponse::success(
            (new ClientResource($client->load(['cases' => fn ($q) => $q->with('assignee:id,name')->orderBy('created_at', 'desc')])))
                ->withPii()
        );
    }

    public function update(UpdateClientRequest $request, string $id): JsonResponse
    {
        $client = $this->service->findOrFail($id);
        $this->authorize('update', $client);

        $client = $this->service->update($id, $request->validated());

        return ApiResponse::success(new ClientResource($client));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $client = $this->service->findOrFail($id);
        $this->authorize('delete', $client);

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

        $path = $request->file('file')->store('passports', 'documents');

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
