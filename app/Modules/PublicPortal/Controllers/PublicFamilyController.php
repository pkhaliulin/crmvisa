<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\CaseFamilyMember;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\PublicPortal\Models\PublicUserFamilyMember;
use App\Modules\Payment\Models\ClientPayment;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'dob'                => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'gender'             => ['nullable', Rule::in(['M', 'F'])],
            'citizenship'        => ['nullable', 'string', 'size:2'],
            'passport_number'    => ['nullable', 'string', 'regex:/^[A-Z]{2}[0-9]{7}$/'],
            'passport_expires_at'=> ['nullable', 'date', 'after:today'],
        ]);

        // Валидация возраста для child: до 18 лет (#17)
        if ($data['relationship'] === 'child') {
            $age = Carbon::parse($data['dob'])->age;
            if ($age >= 18) {
                return ApiResponse::error('Ребёнок должен быть младше 18 лет.', null, 422);
            }
        }

        // Максимум 1 spouse (#17)
        if ($data['relationship'] === 'spouse') {
            $existingSpouse = PublicUserFamilyMember::where('public_user_id', $publicUser->id)
                ->where('relationship', 'spouse')
                ->exists();
            if ($existingSpouse) {
                return ApiResponse::error('Супруг(а) уже добавлен(а).', null, 422);
            }
        }

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
            'passport_number'    => ['sometimes', 'nullable', 'string', 'regex:/^[A-Z]{2}[0-9]{7}$/'],
            'passport_expires_at'=> ['sometimes', 'nullable', 'date', 'after:today'],
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

        // Загрузить ВСЕ чеклисты одним запросом и сгруппировать по family_member_id
        $memberIds = $caseMembers->pluck('family_member_id')->filter()->values();
        $allChecklists = CaseChecklist::where('case_id', $case->id)
            ->whereIn('family_member_id', $memberIds)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('family_member_id');

        $result = $caseMembers->map(function ($cm) use ($allChecklists) {
            $member = $cm->familyMember;

            $docs = ($allChecklists[$member->id] ?? collect())->map(fn ($item) => [
                'id'            => $item->id,
                'name'          => $item->name,
                'description'   => $item->description,
                'type'          => $item->type ?? 'upload',
                'is_required'   => $item->is_required,
                'is_checked'    => (bool) $item->is_checked,
                'is_repeatable' => (bool) ($item->is_repeatable ?? false),
                'responsibility'=> $item->responsibility ?? 'client',
                'document_id'   => $item->document_id,
                'status'        => $item->status,
                'notes'         => $item->notes,
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

        DB::transaction(function () use ($case, $member, $publicUser) {
            CaseFamilyMember::create([
                'case_id'          => $case->id,
                'family_member_id' => $member->id,
            ]);

            // Создать базовый чеклист документов для члена семьи
            $this->createFamilyMemberChecklist($case, $member);

            // Пересчитать платёж с учётом нового члена семьи
            $this->recalculatePayment($case, $publicUser);
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

        DB::transaction(function () use ($case, $fid, $caseMember, $publicUser) {
            // Удалить чеклист этого члена семьи
            CaseChecklist::where('case_id', $case->id)
                ->where('family_member_id', $fid)
                ->delete();

            $caseMember->delete();

            // Пересчитать платёж без этого члена семьи
            $this->recalculatePayment($case, $publicUser);
        });

        return ApiResponse::success(null, 'Член семьи откреплён');
    }

    /**
     * Создать чеклист документов для члена семьи через ChecklistService.
     * Использует CountryVisaRequirement + target_audience из шаблонов суперадмина.
     */
    private function createFamilyMemberChecklist(VisaCase $case, PublicUserFamilyMember $member): void
    {
        app(ChecklistService::class)->createForFamilyMember($case, $member);
    }

    /**
     * Пересчитать pending-платёж при изменении состава семьи.
     */
    private function recalculatePayment(VisaCase $case, $publicUser): void
    {
        $payment = ClientPayment::where('case_id', $case->id)
            ->where('status', 'pending')
            ->first();

        if (! $payment) return;

        \App\Modules\Payment\Services\ClientPaymentService::recalculatePaymentAmount($payment);
    }

    private function formatMember(PublicUserFamilyMember $m): array
    {
        return [
            'id'                 => $m->id,
            'name'               => $m->name,
            'relationship'       => $m->relationship,
            'dob'                => $this->toDateStr($m->dob),
            'gender'             => $m->gender,
            'citizenship'        => $m->citizenship,
            'passport_number'    => $m->passport_number,
            'passport_expires_at'=> $this->toDateStr($m->passport_expires_at),
            'is_minor'           => $m->isMinor(),
            'created_at'         => $m->created_at?->toDateString(),
        ];
    }

    private function toDateStr(mixed $val): ?string
    {
        if ($val === null) return null;
        if ($val instanceof \DateTimeInterface) return $val->format('Y-m-d');
        return is_string($val) ? $val : (string) $val;
    }
}
