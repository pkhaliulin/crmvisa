<?php

namespace App\Modules\Group\Services;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\Group\Models\CaseGroup;
use App\Modules\Group\Models\CaseGroupMember;
use App\Modules\Payment\Models\ClientPayment;
use App\Modules\PublicPortal\Models\PublicUser;
use App\Modules\PublicPortal\Services\SmsService;
use App\Support\Traits\NormalizesPhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GroupService
{
    public function __construct(
        private SmsService $sms,
        private ChecklistService $checklist,
    ) {}

    /**
     * Создать группу + добавить инициатора как первого участника.
     */
    public function createGroup(
        PublicUser $initiator,
        string $countryCode,
        string $visaType,
        ?string $name,
        string $paymentStrategy,
        ?string $existingCaseId = null,
    ): CaseGroup {
        return DB::transaction(function () use ($initiator, $countryCode, $visaType, $name, $paymentStrategy, $existingCaseId) {
            $group = CaseGroup::create([
                'initiator_public_user_id' => $initiator->id,
                'name'                     => $name,
                'country_code'             => strtoupper($countryCode),
                'visa_type'                => $visaType,
                'payment_strategy'         => $paymentStrategy,
                'status'                   => 'forming',
            ]);

            // Инициатор как первый участник
            $client = Client::withoutTenant()
                ->where('public_user_id', $initiator->id)
                ->whereNull('agency_id')
                ->first();

            if (! $client) {
                $clientId = Str::uuid()->toString();
                DB::table('clients')->insert([
                    'id'             => $clientId,
                    'agency_id'      => null,
                    'public_user_id' => $initiator->id,
                    'name'           => $initiator->name ?? ('Клиент ' . $initiator->phone),
                    'phone'          => app('encrypter')->encrypt($initiator->phone),
                    'source'         => 'marketplace',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                $client = Client::find($clientId);
            }

            // Привязать существующий кейс или создать новый
            if ($existingCaseId) {
                $case = VisaCase::where('id', $existingCaseId)
                    ->where('client_id', $client->id)
                    ->first();
                if ($case) {
                    $case->update(['group_id' => $group->id]);
                }
            }

            if (! isset($case) || ! $case) {
                $case = VisaCase::create([
                    'agency_id'     => null,
                    'group_id'      => $group->id,
                    'client_id'     => $client->id,
                    'country_code'  => strtoupper($countryCode),
                    'visa_type'     => $visaType,
                    'stage'         => 'lead',
                    'public_status' => 'draft',
                    'priority'      => 'normal',
                ]);
                $this->checklist->createForCase($case);
            }

            CaseGroupMember::create([
                'group_id'       => $group->id,
                'phone'          => $initiator->phone,
                'public_user_id' => $initiator->id,
                'case_id'        => $case->id,
                'client_id'      => $client->id,
                'name'           => $initiator->name,
                'role'           => 'initiator',
                'status'         => 'joined',
            ]);

            return $group->load('members');
        });
    }

    /**
     * Добавить участника по телефону.
     */
    public function addMember(CaseGroup $group, string $phone, ?string $name): CaseGroupMember
    {
        $phone = NormalizesPhone::normalizePhone($phone);

        // Проверка: не свой номер
        $initiator = $group->initiator;
        if ($initiator && NormalizesPhone::normalizePhone($initiator->phone) === $phone) {
            throw new \InvalidArgumentException('Нельзя добавить свой номер.');
        }

        // Проверка: лимит 10
        if ($group->members()->count() >= 10) {
            throw new \InvalidArgumentException('Максимум 10 участников в группе.');
        }

        // Проверка: дубль (phone зашифрован через encrypted cast — сравниваем через accessor)
        $existing = CaseGroupMember::where('group_id', $group->id)
            ->get()
            ->first(fn ($m) => $m->phone === $phone);

        if ($existing) {
            throw new \InvalidArgumentException('Участник с таким номером уже в группе.');
        }

        return DB::transaction(function () use ($group, $phone, $name) {
            // Ищем PublicUser по телефону
            $publicUser = PublicUser::where('phone', $phone)->first();

            // Создаём Client (без agency)
            $clientId = Str::uuid()->toString();
            DB::table('clients')->insert([
                'id'             => $clientId,
                'agency_id'      => null,
                'public_user_id' => $publicUser?->id,
                'name'           => $name ?? ($publicUser?->name ?? ('Участник ' . substr($phone, -4))),
                'phone'          => app('encrypter')->encrypt($phone),
                'source'         => 'group_invite',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $client = Client::find($clientId);

            // Создаём VisaCase (draft)
            $case = VisaCase::create([
                'agency_id'     => $group->agency_id,
                'group_id'      => $group->id,
                'client_id'     => $client->id,
                'country_code'  => $group->country_code,
                'visa_type'     => $group->visa_type,
                'stage'         => 'lead',
                'public_status' => 'draft',
                'priority'      => 'normal',
            ]);

            $this->checklist->createForCase($case);

            $member = CaseGroupMember::create([
                'group_id'       => $group->id,
                'phone'          => $phone,
                'public_user_id' => $publicUser?->id,
                'case_id'        => $case->id,
                'client_id'      => $client->id,
                'name'           => $name,
                'role'           => 'member',
                'status'         => $publicUser ? 'joined' : 'invited',
            ]);

            // SMS приглашение
            $groupName = $group->name ?? 'группу';
            $message = "Вас пригласили в {$groupName} на VisaBor. Войдите по номеру {$phone}: https://visabor.uz";
            $this->sms->send($phone, $message);

            return $member;
        });
    }

    /**
     * Привязать ожидающие приглашения при авторизации пользователя.
     */
    public function linkPendingInvitations(PublicUser $user): void
    {
        $phone = NormalizesPhone::normalizePhone($user->phone);

        // Ищем все CaseGroupMember где phone совпадает и status=invited
        $pendingMembers = CaseGroupMember::where('status', 'invited')
            ->get()
            ->filter(fn ($m) => $m->phone === $phone);

        foreach ($pendingMembers as $member) {
            $member->update([
                'public_user_id' => $user->id,
                'status'         => 'joined',
            ]);

            // Обновляем Client.public_user_id
            if ($member->client_id) {
                Client::withoutTenant()
                    ->where('id', $member->client_id)
                    ->whereNull('public_user_id')
                    ->update(['public_user_id' => $user->id]);
            }
        }
    }

    /**
     * Удалить участника (только инициатор).
     */
    public function removeMember(CaseGroup $group, string $memberId, PublicUser $initiator): void
    {
        if ($group->initiator_public_user_id !== $initiator->id) {
            throw new \InvalidArgumentException('Только инициатор может удалять участников.');
        }

        $member = CaseGroupMember::where('group_id', $group->id)
            ->where('id', $memberId)
            ->where('role', '!=', 'initiator')
            ->firstOrFail();

        DB::transaction(function () use ($member) {
            // Отмена кейса вместо удаления (#16)
            if ($member->case_id) {
                $case = VisaCase::find($member->case_id);
                if ($case && !in_array($case->public_status, ['cancelled', 'completed', 'rejected'])) {
                    $case->update([
                        'public_status' => 'cancelled',
                        'notes'         => ($case->notes ? $case->notes . "\n\n" : '') . 'Отмена: участник удалён из группы',
                    ]);

                    // Отменить pending платежи
                    ClientPayment::where('case_id', $case->id)
                        ->where('status', 'pending')
                        ->each(function (ClientPayment $p) {
                            $p->update([
                                'status'   => 'cancelled',
                                'metadata' => array_merge($p->metadata ?? [], [
                                    'cancelled_reason' => 'member_removed',
                                    'cancelled_at'     => now()->toDateTimeString(),
                                ]),
                            ]);
                        });
                }
            }
            $member->update(['status' => 'removed']);
        });
    }

    /**
     * Установить агентство для группы и всех кейсов.
     */
    public function setGroupAgency(CaseGroup $group, string $agencyId): void
    {
        DB::transaction(function () use ($group, $agencyId) {
            // Superadmin для RLS: кейсы с agency_id=NULL недоступны через tenant_id
            DB::statement("SET LOCAL app.is_superadmin = 'true'");

            $group->update([
                'agency_id' => $agencyId,
                'status'    => 'active',
            ]);

            VisaCase::where('group_id', $group->id)->update([
                'agency_id'     => $agencyId,
                'public_status' => 'awaiting_payment',
            ]);

            // Обновить agency_id у всех клиентов группы
            $clientIds = CaseGroupMember::where('group_id', $group->id)
                ->whereNotNull('client_id')
                ->pluck('client_id');

            if ($clientIds->isNotEmpty()) {
                Client::withoutTenant()
                    ->whereIn('id', $clientIds)
                    ->update(['agency_id' => $agencyId]);
            }
        });
    }

    /**
     * Прогресс группы: участники, документы, оплата.
     */
    public function getGroupProgress(CaseGroup $group): array
    {
        $group->load(['members.visaCase', 'agency:id,name,city,logo_url,is_verified,rating']);

        // Один запрос для статистики документов всех кейсов группы
        $caseIds = $group->members->pluck('case_id')->filter()->values();
        $docsStats = collect();
        if ($caseIds->isNotEmpty()) {
            $docsStats = CaseChecklist::whereIn('case_id', $caseIds)
                ->selectRaw("case_id, COUNT(*) as total, SUM(CASE WHEN status IN ('uploaded', 'approved') THEN 1 ELSE 0 END) as uploaded")
                ->groupBy('case_id')
                ->get()
                ->keyBy('case_id');
        }

        $members = $group->members->map(function (CaseGroupMember $member) use ($docsStats) {
            $case = $member->visaCase;
            $docsTotal = 0;
            $docsUploaded = 0;

            if ($case && isset($docsStats[$case->id])) {
                $docsTotal = (int) $docsStats[$case->id]->total;
                $docsUploaded = (int) $docsStats[$case->id]->uploaded;
            }

            $maskedPhone = $member->phone
                ? substr($member->phone, 0, 4) . '****' . substr($member->phone, -2)
                : null;

            return [
                'id'              => $member->id,
                'name'            => $member->name,
                'phone_masked'    => $maskedPhone,
                'role'            => $member->role,
                'status'          => $member->status,
                'payment_covered' => $member->payment_covered,
                'case_id'         => $member->case_id,
                'docs_total'      => $docsTotal,
                'docs_uploaded'   => $docsUploaded,
                'payment_status'  => $case?->payment_status ?? 'unpaid',
                'public_status'   => $case?->public_status ?? 'draft',
            ];
        });

        return [
            'group' => [
                'id'               => $group->id,
                'name'             => $group->name,
                'country_code'     => $group->country_code,
                'visa_type'        => $group->visa_type,
                'payment_strategy' => $group->payment_strategy,
                'status'           => $group->status,
                'agency'           => $group->agency,
                'created_at'       => $group->created_at->toDateString(),
            ],
            'members' => $members,
            'stats'   => [
                'total_members' => $members->count(),
                'joined'        => $members->where('status', 'joined')->count(),
                'invited'       => $members->where('status', 'invited')->count(),
                'all_paid'      => $members->every(fn ($m) => $m['payment_status'] === 'paid'),
            ],
        ];
    }

    /**
     * Групповая оплата (инициатор платит за всех).
     */
    public function initiateGroupPayment(CaseGroup $group, PublicUser $payer, string $provider): ClientPayment
    {
        return DB::transaction(function () use ($group, $payer, $provider) {
            // Считаем сумму за все неоплаченные кейсы группы
            $cases = VisaCase::where('group_id', $group->id)
                ->where(function ($q) {
                    $q->whereNull('payment_status')
                      ->orWhere('payment_status', 'unpaid');
                })
                ->get();

            $totalAmount = 0;
            $currency = 'USD';

            foreach ($cases as $case) {
                if (! $case->agency_id) continue;

                $package = DB::table('agency_service_packages')
                    ->where('agency_id', $case->agency_id)
                    ->where('country_code', $case->country_code)
                    ->where('visa_type', $case->visa_type)
                    ->where('is_active', true)
                    ->first();

                if ($package) {
                    $totalAmount += $package->price ?? 0;
                    $currency = $package->currency ?? 'USD';
                }
            }

            $payment = ClientPayment::create([
                'case_id'        => $cases->first()?->id,
                'group_id'       => $group->id,
                'public_user_id' => $payer->id,
                'agency_id'      => $group->agency_id,
                'amount'         => $totalAmount,
                'currency'       => $currency,
                'provider'       => $provider,
                'status'         => 'pending',
                'expires_at'     => now()->addDays(5),
                'metadata'       => [
                    'group_payment'  => true,
                    'all_case_ids'   => $cases->pluck('id')->values()->toArray(),
                    'cases_count'    => $cases->count(),
                ],
            ]);

            // Проставляем pending у всех кейсов
            VisaCase::where('group_id', $group->id)->update([
                'payment_status' => 'pending',
            ]);

            return $payment;
        });
    }

    /**
     * Обработка успешной групповой оплаты.
     */
    public function handleGroupPaymentSuccess(ClientPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $cases = VisaCase::where('group_id', $payment->group_id)->get();

            foreach ($cases as $case) {
                $case->update([
                    'payment_status' => 'paid',
                    'public_status'  => 'submitted',
                    'stage'          => 'lead',
                ]);

                // Создать запись в case_stages (SLA + история)
                $hasStageEntry = \App\Modules\Case\Models\CaseStage::where('case_id', $case->id)
                    ->where('stage', 'lead')
                    ->exists();
                if (! $hasStageEntry) {
                    $stageEntry = \App\Modules\Case\Models\CaseStage::create([
                        'case_id'    => $case->id,
                        'user_id'    => null,
                        'stage'      => 'lead',
                        'entered_at' => now(),
                        'notes'      => 'Групповая оплата подтверждена',
                    ]);
                    app(\App\Modules\Workflow\Services\SlaService::class)->applyStageSla($stageEntry, $case);
                }

                // Авто-назначение менеджера по стратегии агентства (round_robin, by_workload, by_country)
                if ($case->agency_id) {
                    $agency = \App\Modules\Agency\Models\Agency::find($case->agency_id);
                    if ($agency && $agency->lead_assignment_mode !== 'manual') {
                        $agencyService = app(\App\Modules\Agency\Services\AgencyService::class);
                        $manager = $agencyService->assignLead($case, $agency);
                        if ($manager) {
                            $case->update([
                                'assigned_to'   => $manager->id,
                                'public_status' => 'manager_assigned',
                            ]);
                            app(\App\Modules\Case\Services\CaseService::class)
                                ->moveToStageSystem($case->fresh(), 'qualification', 'Менеджер назначен автоматически');
                        }
                    }
                }
            }

            CaseGroupMember::where('group_id', $payment->group_id)
                ->update(['payment_covered' => true]);
        });
    }
}
