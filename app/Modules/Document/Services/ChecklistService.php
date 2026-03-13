<?php

namespace App\Modules\Document\Services;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Document\Models\CountryVisaRequirement;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Models\DocumentRequirement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChecklistService
{
    /**
     * Авто-создать чек-лист для заявки при её создании.
     * Использует новую архитектуру: CountryVisaRequirement → DocumentTemplate.
     * Вызывается из CaseService после создания case.
     */
    public function createForCase(VisaCase $case): void
    {
        $visaType = $case->visa_type ?? '*';

        $requirements = CountryVisaRequirement::active()
            ->forCountry($case->country_code, $visaType)
            ->with('template')
            ->orderBy('display_order')
            ->get();

        // Фолбэк: старая таблица document_requirements (если новая пуста)
        if ($requirements->isEmpty()) {
            $this->createForCaseLegacy($case);
            return;
        }

        $items = [];
        foreach ($requirements as $req) {
            $tpl = $req->template;
            if (! $tpl || ! $tpl->is_active) {
                continue;
            }

            $baseItem = [
                'id'                     => (string) \Illuminate\Support\Str::uuid(),
                'agency_id'              => $case->agency_id,
                'case_id'                => $case->id,
                'country_requirement_id' => $req->id,
                'type'                   => $tpl->type,
                'name'                   => $tpl->name,
                'description'            => $req->notes ?? $tpl->description,
                'is_required'            => $req->isRequired(),
                'responsibility'         => $tpl->default_responsibility ?? 'client',
                'requirement_level'      => $req->requirement_level,
                'metadata'               => $req->effectiveMetadata() ? json_encode($req->effectiveMetadata()) : null,
                'document_id'            => null,
                'is_checked'             => false,
                'status'                 => 'pending',
                'notes'                  => null,
                'sort_order'             => $req->display_order,
                'created_at'             => now(),
                'updated_at'             => now(),
            ];

            // Repeatable-документ (метрика ребёнка): добавляем 1 слот с запасом
            $items[] = $baseItem;
        }

        if (! empty($items)) {
            DB::table('case_checklist')->insert($items);
        }
    }

    /**
     * Фолбэк: создать чек-лист из старой таблицы document_requirements.
     * Используется пока новые сидеры не запущены.
     */
    private function createForCaseLegacy(VisaCase $case): void
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
                              ->with([
                                  'document:id,original_name,file_path,mime_type,size,status,created_at',
                                  'countryRequirement.template:id,manager_instructions,ai_enabled,ai_extraction_schema,ai_validation_rules,ai_stop_factors,ai_success_factors,ai_risk_indicators,max_age_days,confidence_criteria,translation_required',
                              ])
                              ->orderBy('sort_order')
                              ->get();

        // Добавляем подсказки из шаблона в каждый элемент чек-листа
        $items->each(function ($item) {
            $tpl = $item->countryRequirement?->template;
            $item->setAttribute('manager_instructions', $tpl?->manager_instructions);
            $item->setAttribute('ai_enabled', $tpl?->ai_enabled ?? false);
            $item->setAttribute('ai_extraction_schema', $tpl?->ai_extraction_schema);
            $item->setAttribute('ai_validation_rules', $tpl?->ai_validation_rules);
            $item->setAttribute('ai_stop_factors', $tpl?->ai_stop_factors);
            $item->setAttribute('ai_success_factors', $tpl?->ai_success_factors);
            $item->setAttribute('ai_risk_indicators', $tpl?->ai_risk_indicators);
            $item->setAttribute('max_age_days', $tpl?->max_age_days);
            $item->setAttribute('translation_required', $tpl?->translation_required ?? false);
            // Не отдаём вложенные relations в JSON
            $item->unsetRelation('countryRequirement');
        });

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
                    Storage::disk('documents')->delete($old->file_path);
                    $old->forceDelete();
                }
            }

            $path = $file->store("agencies/{$case->agency_id}/cases/{$case->id}", 'documents');

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
     * Менеджер: проверить документ — одобрить / отклонить / на перевод.
     * review_status: approved | rejected | needs_translation
     */
    public function reviewSlot(CaseChecklist $item, string $reviewStatus, ?string $notes = null, ?int $translationPages = null): CaseChecklist
    {
        $updateData = [
            'review_status' => $reviewStatus,
            'review_notes'  => $notes,
            'reviewed_by'   => Auth::id(),
            'reviewed_at'   => now(),
        ];

        // Маппинг review_status → status
        $statusMap = [
            'approved'          => 'approved',
            'rejected'          => 'rejected',
            'needs_translation' => 'needs_translation',
        ];
        $updateData['status'] = $statusMap[$reviewStatus] ?? $item->status;
        $updateData['notes']  = $notes;

        if ($reviewStatus === 'needs_translation' && $translationPages) {
            $updateData['translation_pages'] = $translationPages;
            // Расчёт стоимости перевода из настроек агентства
            $case = \App\Modules\Case\Models\VisaCase::find($item->case_id);
            if ($case?->agency_id) {
                $pricePerPage = DB::table('agencies')
                    ->where('id', $case->agency_id)
                    ->value('translation_price_per_page');
                if ($pricePerPage) {
                    $updateData['translation_price'] = $pricePerPage * $translationPages;
                }
            }
        }

        if ($reviewStatus === 'rejected') {
            // Сброс загруженного файла — клиент должен перезагрузить
            $updateData['document_id'] = null;
        }

        $item->update($updateData);

        // Синхронизируем статус документа
        if ($item->document_id) {
            $docStatus = match ($reviewStatus) {
                'approved', 'needs_translation' => 'approved',
                'rejected' => 'rejected',
                default    => 'pending',
            };
            Document::where('id', $item->document_id)->update(['status' => $docStatus]);
        }

        return $item->fresh(['document']);
    }

    /**
     * Переводчик/менеджер: загрузить перевод документа.
     */
    public function uploadTranslation(CaseChecklist $item, \Illuminate\Http\UploadedFile $file, \App\Modules\Case\Models\VisaCase $case): CaseChecklist
    {
        return DB::transaction(function () use ($item, $file, $case) {
            // Удалить старый перевод если был
            if ($item->translation_document_id) {
                $old = Document::find($item->translation_document_id);
                if ($old) {
                    Storage::disk('documents')->delete($old->file_path);
                    $old->forceDelete();
                }
            }

            $path = $file->store("agencies/{$case->agency_id}/cases/{$case->id}/translations", 'documents');

            $document = Document::create([
                'agency_id'     => $case->agency_id,
                'case_id'       => $case->id,
                'client_id'     => $case->client_id,
                'uploaded_by'   => Auth::id(),
                'type'          => 'translation_' . $item->name,
                'original_name' => $file->getClientOriginalName(),
                'file_path'     => $path,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);

            $item->update([
                'translation_document_id' => $document->id,
                'translated_by'           => Auth::id(),
                'translated_at'           => now(),
                'status'                  => 'translated',
            ]);

            return $item->fresh(['document']);
        });
    }

    /**
     * Менеджер: одобрить перевод.
     */
    public function approveTranslation(CaseChecklist $item): CaseChecklist
    {
        $item->update(['status' => 'translation_approved']);
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
                Storage::disk('documents')->delete($doc->file_path);
                $doc->forceDelete();
            }
        }

        $item->delete();
    }
}
