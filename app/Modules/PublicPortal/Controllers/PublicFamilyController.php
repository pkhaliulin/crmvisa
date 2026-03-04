<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\CaseFamilyMember;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\PublicPortal\Models\PublicUserFamilyMember;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicFamilyController extends Controller
{
    /**
     * GET /public/me/family
     * Список членов семьи в профиле.
     */
    public function index(Request $request): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $members = PublicUserFamilyMember::where('public_user_id', $publicUser->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => $this->formatMember($m));

        return ApiResponse::success($members);
    }

    /**
     * POST /public/me/family
     * Добавить члена семьи в профиль.
     */
    public function store(Request $request): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $data = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'relationship'       => ['required', Rule::in(['child', 'spouse', 'parent', 'sibling', 'other'])],
            'dob'                => ['nullable', 'date', 'before:today'],
            'gender'             => ['nullable', Rule::in(['M', 'F'])],
            'citizenship'        => ['nullable', 'string', 'size:2'],
            'passport_number'    => ['nullable', 'string', 'max:20'],
            'passport_expires_at'=> ['nullable', 'date'],
        ]);

        $data['public_user_id'] = $publicUser->id;

        $member = PublicUserFamilyMember::create($data);

        return ApiResponse::created($this->formatMember($member));
    }

    /**
     * PATCH /public/me/family/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $member = PublicUserFamilyMember::where('public_user_id', $publicUser->id)
            ->findOrFail($id);

        $data = $request->validate([
            'name'               => ['sometimes', 'string', 'max:255'],
            'relationship'       => ['sometimes', Rule::in(['child', 'spouse', 'parent', 'sibling', 'other'])],
            'dob'                => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender'             => ['sometimes', 'nullable', Rule::in(['M', 'F'])],
            'citizenship'        => ['sometimes', 'nullable', 'string', 'size:2'],
            'passport_number'    => ['sometimes', 'nullable', 'string', 'max:20'],
            'passport_expires_at'=> ['sometimes', 'nullable', 'date'],
        ]);

        $member->update($data);

        return ApiResponse::success($this->formatMember($member->fresh()));
    }

    /**
     * DELETE /public/me/family/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $member = PublicUserFamilyMember::where('public_user_id', $publicUser->id)
            ->findOrFail($id);

        $member->delete();

        return ApiResponse::success(null, 'Удалён');
    }

    /**
     * GET /public/me/cases/{id}/family
     * Члены семьи, привязанные к заявке.
     */
    public function caseMembers(Request $request, string $id): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->findOrFail($id);

        $caseMembers = CaseFamilyMember::where('case_id', $case->id)
            ->with('familyMember')
            ->get();

        $result = $caseMembers->map(function ($cm) use ($case) {
            $member = $cm->familyMember;

            // Документы этого члена семьи
            $docs = CaseChecklist::where('case_id', $case->id)
                ->where('family_member_id', $member->id)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($item) => [
                    'id'          => $item->id,
                    'name'        => $item->name,
                    'description' => $item->description,
                    'is_required' => $item->is_required,
                    'status'      => $item->status,
                    'notes'       => $item->notes,
                ]);

            return [
                ...$this->formatMember($member),
                'case_family_member_id' => $cm->id,
                'checklist' => $docs,
            ];
        });

        return ApiResponse::success($result);
    }

    /**
     * POST /public/me/cases/{id}/family
     * Привязать члена семьи к заявке + создать чеклист документов.
     */
    public function attachToCase(Request $request, string $id): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $data = $request->validate([
            'family_member_id' => ['required', 'uuid'],
        ]);

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->findOrFail($id);

        $member = PublicUserFamilyMember::where('public_user_id', $publicUser->id)
            ->findOrFail($data['family_member_id']);

        // Проверка: уже привязан?
        $exists = CaseFamilyMember::where('case_id', $case->id)
            ->where('family_member_id', $member->id)
            ->exists();

        if ($exists) {
            return ApiResponse::error('Этот член семьи уже добавлен в заявку.', null, 409);
        }

        DB::transaction(function () use ($case, $member) {
            CaseFamilyMember::create([
                'case_id'          => $case->id,
                'family_member_id' => $member->id,
            ]);

            // Создать базовый чеклист документов для члена семьи
            $this->createFamilyMemberChecklist($case, $member);
        });

        return ApiResponse::created(null, 'Член семьи добавлен в заявку');
    }

    /**
     * DELETE /public/me/cases/{id}/family/{fid}
     * Открепить члена семьи от заявки.
     */
    public function detachFromCase(Request $request, string $id, string $fid): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->findOrFail($id);

        $caseMember = CaseFamilyMember::where('case_id', $case->id)
            ->where('family_member_id', $fid)
            ->firstOrFail();

        DB::transaction(function () use ($case, $fid, $caseMember) {
            // Удалить чеклист этого члена семьи
            CaseChecklist::where('case_id', $case->id)
                ->where('family_member_id', $fid)
                ->delete();

            $caseMember->delete();
        });

        return ApiResponse::success(null, 'Член семьи откреплён');
    }

    /**
     * Создать базовый чеклист документов для члена семьи.
     */
    private function createFamilyMemberChecklist(VisaCase $case, PublicUserFamilyMember $member): void
    {
        $isMinor = $member->dob && $member->dob->age < 18;

        $items = [
            [
                'name'        => "Копия паспорта — {$member->name}",
                'description' => 'Сканы всех заполненных страниц паспорта',
                'is_required' => true,
                'sort_order'  => 1,
            ],
            [
                'name'        => "Фото 3.5x4.5 — {$member->name}",
                'description' => 'Фото на белом фоне, без очков и головных уборов',
                'is_required' => true,
                'sort_order'  => 2,
            ],
        ];

        if ($isMinor) {
            $items[] = [
                'name'        => "Свидетельство о рождении — {$member->name}",
                'description' => 'Копия свидетельства о рождении',
                'is_required' => true,
                'sort_order'  => 3,
            ];
            $items[] = [
                'name'        => "Согласие на выезд — {$member->name}",
                'description' => 'Нотариальное согласие от обоих родителей (если едет с одним)',
                'is_required' => true,
                'sort_order'  => 4,
            ];
        }

        if ($member->relationship === 'spouse') {
            $items[] = [
                'name'        => "Свидетельство о браке — {$member->name}",
                'description' => 'Копия свидетельства о заключении брака',
                'is_required' => true,
                'sort_order'  => 3,
            ];
        }

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'id'              => Str::uuid()->toString(),
                'agency_id'       => $case->agency_id,
                'case_id'         => $case->id,
                'family_member_id'=> $member->id,
                'type'            => 'upload',
                'name'            => $item['name'],
                'description'     => $item['description'],
                'is_required'     => $item['is_required'],
                'status'          => 'pending',
                'sort_order'      => $item['sort_order'],
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        DB::table('case_checklist')->insert($rows);
    }

    private function formatMember(PublicUserFamilyMember $m): array
    {
        return [
            'id'                 => $m->id,
            'name'               => $m->name,
            'relationship'       => $m->relationship,
            'dob'                => $m->dob?->toDateString(),
            'gender'             => $m->gender,
            'citizenship'        => $m->citizenship,
            'passport_number'    => $m->passport_number,
            'passport_expires_at'=> $m->passport_expires_at?->toDateString(),
            'is_minor'           => $m->isMinor(),
            'created_at'         => $m->created_at?->toDateString(),
        ];
    }
}
