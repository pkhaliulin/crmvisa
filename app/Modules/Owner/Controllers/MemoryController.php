<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class MemoryController extends Controller
{
    /**
     * Возвращает все файлы памяти проекта.
     * Читает .md файлы из resources/memory/ в реальном времени.
     */
    public function index(): JsonResponse
    {
        $dir = resource_path('memory');

        if (! File::isDirectory($dir)) {
            return ApiResponse::success(['files' => []]);
        }

        $files = collect(File::files($dir))
            ->filter(fn ($file) => $file->getExtension() === 'md')
            ->map(fn ($file) => [
                'name'       => $file->getFilenameWithoutExtension(),
                'filename'   => $file->getFilename(),
                'content'    => File::get($file->getPathname()),
                'size'       => $file->getSize(),
                'updated_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ])
            ->sortBy('name')
            ->values()
            ->all();

        return ApiResponse::success([
            'files'      => $files,
            'total'      => count($files),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
