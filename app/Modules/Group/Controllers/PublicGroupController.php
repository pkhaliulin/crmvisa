<?php

namespace App\Modules\Group\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Group\Models\CaseGroup;
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
