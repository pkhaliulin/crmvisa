<?php

namespace App\Modules\Document\Services;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Models\DocumentRequirement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChecklistService
{
    /**
     * Авто-создать чек-лист для заявки при её создании.
     * Вызывается из CaseService после создания case.
     */
    public function createForCase(VisaCase $case): void
    {
        $requirements = DocumentRequirement::forCase($case->country_code, $case->visa_type ?? '*');

        if ($requirements->isEmpty()) {
            return;
        }

        $items = $requirements->map(fn ($req) => [
            'id'             => (string) \Illuminate\Support\Str::uuid(),
            'agency_id'      => $case->agency_id,
            'case_id'        => $case->id,
            'requirement_id' => $req->id,
            'type'           => $req->type ?? 'upload',
            'name'           => $req->name,
            'description'    => $req->description,
            'is_required'    => $req->is_required,
            'document_id'    => null,
            'is_checked'     => false,
            'status'         => 'pending',
            'notes'          => null,
            'sort_order'     => $req->sort_order,
            'created_at'     => now(),
            'updated_at'     => now(),
        ])->toArray();

        DB::table('case_checklist')->insert($items);
    }

    /**
     * Список чек-листа для заявки с прогрессом.
     */
    public function getForCase(string $caseId): array
    {
        $items = CaseChecklist::where('case_id', $caseId)
                              ->with('document:id,original_name,file_path,mime_type,size,status,created_at')
                              ->orderBy('sort_order')
                              ->get();

        $total    = $items->count();
        $uploaded = $items->whereIn('status', ['uploaded', 'approved'])->count();
        $approved = $items->where('status', 'approved')->count();

        return [
            'items'    => $items,
            'progress' => [
                'total'    => $total,
                'uploaded' => $uploaded,
                'approved' => $approved,
                'percent'  => $total > 0 ? (int) round($uploaded / $total * 100) : 0,
            ],
        ];
    }

    /**
     * Загрузить файл в слот чек-листа.
     */
    public function uploadToSlot(
        CaseChecklist $item,
        UploadedFile  $file,
        VisaCase      $case,
        ?string       $uploadedBy = null
    ): CaseChecklist {
        return DB::transaction(function () use ($item, $file, $case, $uploadedBy) {
            // Если уже был файл — удаляем старый
            if ($item->document_id) {
                $old = Document::find($item->document_id);
                if ($old) {
                    Storage::disk('public')->delete($old->file_path);
                    $old->forceDelete();
                }
            }

            $path = $file->store("agencies/{$case->agency_id}/cases/{$case->id}", 'public');

            $document = Document::create([
                'agency_id'     => $case->agency_id,
                'case_id'       => $case->id,
                'client_id'     => $case->client_id,
                'uploaded_by'   => $uploadedBy ?? Auth::id(),
                'type'          => $item->name,         // название слота = тип документа
                'original_name' => $file->getClientOriginalName(),
                'file_path'     => $path,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);

            $item->markUploaded($document->id);

            return $item->fresh(['document']);
        });
    }

    /**
     * Менеджер: одобрить / отклонить документ в слоте.
     */
    public function reviewSlot(CaseChecklist $item, string $status, ?string $notes = null): CaseChecklist
    {
        $item->update(['status' => $status, 'notes' => $notes]);

        // Синхронизируем статус самого документа
        if ($item->document_id) {
            $docStatus = match ($status) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                default    => 'pending',
            };
            Document::where('id', $item->document_id)->update(['status' => $docStatus]);
        }

        return $item->fresh(['document']);
    }

    /**
     * Добавить произвольный слот вручную (менеджер).
     */
    public function addCustomSlot(VisaCase $case, string $name, ?string $description = null, bool $isRequired = false): CaseChecklist
    {
        $maxOrder = CaseChecklist::where('case_id', $case->id)->max('sort_order') ?? 0;

        return CaseChecklist::create([
            'agency_id'   => $case->agency_id,
            'case_id'     => $case->id,
            'name'        => $name,
            'description' => $description,
            'is_required' => $isRequired,
            'sort_order'  => $maxOrder + 1,
        ]);
    }

    /**
     * Отметить checkbox-слот как выполненный (клиент или менеджер).
     */
    public function toggleCheck(CaseChecklist $item, bool $checked): CaseChecklist
    {
        $item->update([
            'is_checked' => $checked,
            'status'     => $checked ? 'uploaded' : 'pending',
        ]);

        return $item->fresh();
    }

    /**
     * Удалить слот (только кастомные, не из справочника).
     */
    public function removeSlot(CaseChecklist $item): void
    {
        if ($item->document_id) {
            $doc = Document::find($item->document_id);
            if ($doc) {
                Storage::disk('public')->delete($doc->file_path);
                $doc->forceDelete();
            }
        }

        $item->delete();
    }
}
