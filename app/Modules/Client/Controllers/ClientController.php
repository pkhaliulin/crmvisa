<?php

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Requests\StoreClientRequest;
use App\Modules\Client\Requests\UpdateClientRequest;
use App\Modules\Client\Resources\ClientResource;
use App\Modules\Client\Services\ClientService;
use App\Modules\Document\Services\OcrService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function __construct(
        protected ClientService $service,
        protected OcrService $ocrService,
    ) {}

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
     * Загрузка фото паспорта и OCR-распознавание данных.
     */
    public function parsePassport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $agencyId = $request->user()->agency_id;
        $file     = $request->file('file');
        $path     = $file->store("agencies/{$agencyId}/passports", 'documents');
        $fullPath = Storage::disk('documents')->path($path);

        try {
            $passportData = $this->ocrService->extractPassport($fullPath);

            return ApiResponse::success([
                'file_path'           => $path,
                'ocr_status'          => 'completed',
                'name'                => trim(($passportData->firstName ?? '') . ' ' . ($passportData->lastName ?? '')),
                'first_name'          => $passportData->firstName,
                'last_name'           => $passportData->lastName,
                'middle_name'         => $passportData->middleName,
                'date_of_birth'       => $passportData->dateOfBirth,
                'passport_number'     => $passportData->passportNumber,
                'passport_expires_at' => $passportData->dateOfExpiry,
                'nationality'         => $passportData->nationality,
                'gender'              => $passportData->gender,
                'confidence'          => $passportData->confidence,
                'provider'            => $passportData->provider,
            ]);
        } catch (\Throwable $e) {
            Log::warning('parsePassport OCR failed, returning empty fields', [
                'agency_id' => $agencyId,
                'error'     => $e->getMessage(),
            ]);

            return ApiResponse::success([
                'file_path'           => $path,
                'ocr_status'          => 'failed',
                'ocr_error'           => $e->getMessage(),
                'name'                => null,
                'date_of_birth'       => null,
                'passport_number'     => null,
                'passport_expires_at' => null,
                'nationality'         => null,
            ], 'Файл загружен, но OCR-распознавание не удалось. Заполните поля вручную.');
        }
    }
}
