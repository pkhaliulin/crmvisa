<?php

namespace App\Modules\Group\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Group\Models\CaseGroup;
use App\Modules\Group\Models\CaseGroupMember;
use App\Modules\Group\Services\GroupService;
use App\Modules\Payment\Services\ClientPaymentService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicGroupController extends Controller
{
    public function __construct(
        private GroupService $groupService,
        private ClientPaymentService $paymentService,
    ) {}

    /**
     * GET /public/me/groups — мои группы (инициатор + участник).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        $groups = CaseGroup::where('initiator_public_user_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('public_user_id', $user->id))
            ->with('members:id,group_id,name,role,status')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (CaseGroup $g) => [
                'id'               => $g->id,
                'name'             => $g->name,
                'country_code'     => $g->country_code,
                'visa_type'        => $g->visa_type,
                'payment_strategy' => $g->payment_strategy,
                'status'           => $g->status,
                'members_count'    => $g->members->count(),
                'is_initiator'     => $g->initiator_public_user_id === $user->id,
                'created_at'       => $g->created_at->toDateString(),
            ]);

        return ApiResponse::success($groups);
    }

    /**
     * POST /public/me/groups — создать группу.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        $data = $request->validate([
            'country_code'     => 'required|string|size:2',
            'visa_type'        => 'required|string|max:50',
            'name'             => 'nullable|string|max:255',
            'payment_strategy' => 'sometimes|string|in:individual,initiator_pays',
            'existing_case_id' => 'nullable|uuid',
        ]);

        $group = $this->groupService->createGroup(
            initiator: $user,
            countryCode: $data['country_code'],
            visaType: $data['visa_type'],
            name: $data['name'] ?? null,
            paymentStrategy: $data['payment_strategy'] ?? 'individual',
            existingCaseId: $data['existing_case_id'] ?? null,
        );

        return ApiResponse::created(
            $this->groupService->getGroupProgress($group),
            'Группа создана.'
        );
    }

    /**
     * GET /public/me/groups/{id} — детали + прогресс.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user  = $request->get('_public_user');
        $group = $this->findGroupForUser($id, $user->id);

        return ApiResponse::success(
            $this->groupService->getGroupProgress($group)
        );
    }

    /**
     * POST /public/me/groups/{id}/members — добавить участника.
     */
    public function addMember(Request $request, string $id): JsonResponse
    {
        $user  = $request->get('_public_user');
        $group = CaseGroup::where('id', $id)
            ->where('initiator_public_user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'name'  => 'nullable|string|max:255',
        ]);

        try {
            $member = $this->groupService->addMember(
                group: $group,
                phone: $data['phone'],
                name: $data['name'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::created([
            'id'     => $member->id,
            'name'   => $member->name,
            'status' => $member->status,
            'role'   => $member->role,
        ], 'Участник добавлен.');
    }

    /**
     * DELETE /public/me/groups/{id}/members/{mid} — удалить участника.
     */
    public function removeMember(Request $request, string $id, string $mid): JsonResponse
    {
        $user  = $request->get('_public_user');
        $group = CaseGroup::where('id', $id)
            ->where('initiator_public_user_id', $user->id)
            ->firstOrFail();

        try {
            $this->groupService->removeMember($group, $mid, $user);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(null, 'Участник удалён.');
    }

    /**
     * POST /public/me/groups/{id}/agency — выбрать агентство.
     */
    public function setAgency(Request $request, string $id): JsonResponse
    {
        $user  = $request->get('_public_user');
        $group = CaseGroup::where('id', $id)
            ->where('initiator_public_user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'agency_id' => 'required|uuid|exists:agencies,id',
        ]);

        $this->groupService->setGroupAgency($group, $data['agency_id']);

        return ApiResponse::success(null, 'Агентство выбрано для группы.');
    }

    /**
     * POST /public/me/groups/{id}/pay — оплатить за всех.
     */
    public function payForGroup(Request $request, string $id): JsonResponse
    {
        $user  = $request->get('_public_user');
        $group = CaseGroup::where('id', $id)
            ->where('initiator_public_user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'provider' => 'required|string|in:click,payme,uzum',
        ]);

        $payment    = $this->groupService->initiateGroupPayment($group, $user, $data['provider']);
        $paymentUrl = $this->paymentService->getPaymentUrl($payment);

        return ApiResponse::success([
            'payment_id'  => $payment->id,
            'amount'      => $payment->amount,
            'currency'    => $payment->currency,
            'payment_url' => $paymentUrl,
        ], 'Платёж создан.');
    }

    /**
     * GET /public/me/groups/{id}/agencies — агентства для страны/визы группы.
     */
    public function agencies(Request $request, string $id): JsonResponse
    {
        $user   = $request->get('_public_user');
        $locale = $request->input('lang') ?? $request->header('X-Locale', 'ru');
        $locale = in_array($locale, ['uz', 'ru']) ? $locale : 'ru';

        $group = $this->findGroupForUser($id, $user->id);

        $cc       = $group->country_code;
        $visaType = $group->visa_type;

        $agencies = Agency::where('is_active', true)
            ->whereNull('blocked_at')
            ->whereHas('workCountries', fn ($wq) =>
                $wq->where('country_code', $cc)->where('is_active', true)
            )
            ->with(['packages' => function ($q) use ($cc, $visaType) {
                $q->where('is_active', true);
                if ($cc)       $q->where('country_code', $cc);
                if ($visaType) $q->where('visa_type', $visaType);
            }])
            ->select([
                'id', 'name', 'city', 'rating', 'reviews_count',
                'description', 'description_uz',
                'experience_years', 'logo_url', 'is_verified',
            ])
            ->orderByDesc('rating')
            ->get()
            ->filter(fn ($a) => $a->packages->isNotEmpty())
            ->map(fn ($a) => [
                'id'               => $a->id,
                'name'             => $a->name,
                'city'             => $a->city,
                'rating'           => $a->rating,
                'reviews_count'    => $a->reviews_count,
                'experience_years' => $a->experience_years,
                'logo_url'         => $a->logo_url,
                'is_verified'      => $a->is_verified,
                'description'      => $locale === 'uz' && $a->description_uz ? $a->description_uz : $a->description,
                'package'          => $a->packages->first() ? [
                    'id'              => $a->packages->first()->id,
                    'name'            => $locale === 'uz' && $a->packages->first()->name_uz
                        ? $a->packages->first()->name_uz
                        : $a->packages->first()->name,
                    'price'           => $a->packages->first()->price,
                    'currency'        => $a->packages->first()->currency ?? 'USD',
                    'processing_days' => $a->packages->first()->processing_days,
                ] : null,
            ])
            ->values();

        return ApiResponse::success(['agencies' => $agencies]);
    }

    /**
     * GET /public/me/groups/{id}/members/{memberId}/case — детали заявки участника.
     */
    public function memberCaseDetail(Request $request, string $id, string $memberId): JsonResponse
    {
        $user  = $request->get('_public_user');
        $group = CaseGroup::where('id', $id)
            ->where('initiator_public_user_id', $user->id)
            ->firstOrFail();

        $member = CaseGroupMember::where('group_id', $group->id)
            ->where('id', $memberId)
            ->firstOrFail();

        $case = $member->case_id ? VisaCase::find($member->case_id) : null;

        $checklist = [];
        if ($case) {
            $checklist = CaseChecklist::where('case_id', $case->id)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($item) => [
                    'id'        => $item->id,
                    'name'      => $item->document_name,
                    'status'    => $item->status,
                    'file_url'  => $item->file_url,
                ]);
        }

        return ApiResponse::success([
            'member' => [
                'id'     => $member->id,
                'name'   => $member->name,
                'role'   => $member->role,
                'status' => $member->status,
            ],
            'case' => $case ? [
                'id'             => $case->id,
                'stage'          => $case->stage,
                'public_status'  => $case->public_status,
                'payment_status' => $case->payment_status ?? 'unpaid',
                'country_code'   => $case->country_code,
                'visa_type'      => $case->visa_type,
                'created_at'     => $case->created_at->toDateString(),
            ] : null,
            'checklist' => $checklist,
        ]);
    }

    /**
     * Найти группу, к которой пользователь имеет доступ.
     */
    private function findGroupForUser(string $groupId, string $userId): CaseGroup
    {
        return CaseGroup::where('id', $groupId)
            ->where(function ($q) use ($userId) {
                $q->where('initiator_public_user_id', $userId)
                  ->orWhereHas('members', fn ($mq) => $mq->where('public_user_id', $userId));
            })
            ->firstOrFail();
    }
}
