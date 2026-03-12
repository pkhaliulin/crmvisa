<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Services\OcrService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OcrController extends Controller
{
    public function __construct(protected OcrService $ocrService) {}

    /**
     * POST /api/v1/ocr/passport
     *
     * Загрузка фото паспорта и извлечение данных через OCR.
     * Accept: multipart/form-data, поле "image".
     */
    public function passport(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $file     = $request->file('image');
        $agencyId = $request->user()->agency_id;

        // Сохраняем файл
        $storedPath = $file->store("agencies/{$agencyId}/passports", 'documents');
        $fullPath   = Storage::disk('documents')->path($storedPath);

        try {
            $passportData = $this->ocrService->extractPassport($fullPath);

            return ApiResponse::success([
                'file_path' => $storedPath,
                'ocr_status' => 'completed',
                ...$passportData->toArray(),
            ]);
        } catch (\Throwable $e) {
            Log::error('OCR passport endpoint failed', [
                'agency_id' => $agencyId,
                'error'     => $e->getMessage(),
            ]);

            // Файл загружен, но OCR не удался — возвращаем partial result
            return ApiResponse::success([
                'file_path'  => $storedPath,
                'ocr_status' => 'failed',
                'ocr_error'  => $e->getMessage(),
            ], 'Файл загружен, но OCR-распознавание не удалось. Заполните поля вручную.');
        }
    }
}
