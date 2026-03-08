<?php

namespace App\Modules\Case\Services;

use App\Modules\Case\Models\VisaCase;

class DashboardHintsService
{
    /**
     * @param string $agencyId
     * @param array $context Keys: overdue, critical, unassigned, totalActive, managerLoad,
     *                       stageAnalytics, repeatClients, totalClients, byLeadSource,
     *                       avgProcessingHours, unassignedPct, overduePct,
     *                       topPopular, trendingPopular, workCodes
     */
    public function generate(string $agencyId, array $context): array
    {
        $overdue = $context['overdue'];
        $critical = $context['critical'];
        $unassigned = $context['unassigned'];
        $totalActive = $context['totalActive'];
        $managerLoad = $context['managerLoad'];
        $stageAnalytics = $context['stageAnalytics'];
        $repeatClients = $context['repeatClients'];
        $totalClients = $context['totalClients'];
        $byLeadSource = $context['byLeadSource'];
        $avgProcessingHours = $context['avgProcessingHours'];
        $unassignedPct = $context['unassignedPct'];
        $overduePct = $context['overduePct'];
        $topPopular = $context['topPopular'] ?? null;
        $trendingPopular = $context['trendingPopular'] ?? null;
        $workCodes = $context['workCodes'] ?? [];

        $hints = [];

        if ($overdue > 0) {
            $hints[] = [
                'type'   => 'warning',
                'key'    => 'overdue',
                'params' => ['n' => $overdue],
                'action' => '/app/cases?status=overdue',
            ];
        }

        if ($unassigned > 0) {
            $hints[] = [
                'type'   => 'info',
                'key'    => 'unassigned',
                'params' => ['n' => $unassigned, 'pct' => $unassignedPct],
                'action' => '/app/cases?assigned_to=unassigned',
            ];
        }

        if ($critical > 3) {
            $hints[] = [
                'type'   => 'warning',
                'key'    => 'critical',
                'params' => ['n' => $critical],
                'action' => '/app/cases?status=critical',
            ];
        }

        // Перегрузка менеджера
        $maxLoad = $managerLoad->max('active_cases');
        $minLoad = $managerLoad->min('active_cases');
        if ($managerLoad->count() > 1 && $maxLoad > 0 && $maxLoad > $minLoad * 3) {
            $busiest = $managerLoad->firstWhere('active_cases', $maxLoad);
            $hints[] = [
                'type'   => 'tip',
                'key'    => 'imbalance',
                'params' => ['name' => $busiest->name, 'n' => $maxLoad],
            ];
        }

        // Низкий SLA compliance
        foreach ($stageAnalytics as $stage => $data) {
            if ($data['sla_compliance'] < 70 && $data['total_transitions'] >= 5) {
                $hints[] = [
                    'type'   => 'warning',
                    'key'    => 'lowSla',
                    'params' => ['stage' => $stage, 'pct' => $data['sla_compliance']],
                ];
                break;
            }
        }

        // Высокое среднее время
        if ($avgProcessingHours && $avgProcessingHours > 240) { // > 10 дней
            $hints[] = [
                'type'   => 'tip',
                'key'    => 'slowProcessing',
                'params' => ['days' => round($avgProcessingHours / 24, 1)],
            ];
        }

        // Низкая доля внешних каналов
        $totalLeads = $byLeadSource->sum('count');
        $directCount = $byLeadSource->firstWhere('source', 'direct')?->count ?? 0;
        if ($totalLeads > 10 && $directCount / $totalLeads > 0.7) {
            $hints[] = [
                'type'   => 'tip',
                'key'    => 'lowExternalLeads',
                'params' => ['pct' => round($directCount / $totalLeads * 100)],
            ];
        }

        // Мало повторных клиентов
        if ($totalClients >= 10 && $repeatClients === 0) {
            $hints[] = [
                'type'   => 'tip',
                'key'    => 'noRepeatClients',
                'params' => [],
            ];
        }

        // Повторные клиенты (позитив)
        if ($totalClients > 0 && $repeatClients > 0) {
            $pct = round($repeatClients / $totalClients * 100, 1);
            if ($pct >= 10) {
                $hints[] = [
                    'type'   => 'success',
                    'key'    => 'repeatClients',
                    'params' => ['n' => $repeatClients, 'pct' => $pct],
                ];
            }
        }

        // Высокая просрочка
        if ($overduePct > 20 && $overdue > 2) {
            $hints[] = [
                'type'   => 'warning',
                'key'    => 'highOverdueRate',
                'params' => ['pct' => $overduePct],
            ];
        }

        // Этапы, где скапливаются заявки
        if ($totalActive > 5) {
            foreach ($stageAnalytics as $stage => $data) {
                if ($data['avg_hours'] !== null && $data['sla_norm_hours'] !== null && $data['deviation'] !== null && $data['deviation'] > $data['sla_norm_hours'] * 0.5) {
                    $hints[] = [
                        'type'   => 'warning',
                        'key'    => 'stageBottleneck',
                        'params' => ['stage' => $stage, 'deviation' => round($data['deviation'], 1)],
                    ];
                    break;
                }
            }
        }

        // Популярные страны, с которыми агентство не работает
        if ($topPopular) {
            $missedCountries = $topPopular->filter(fn ($c) => !$c->agency_works)->take(3);
            if ($missedCountries->count() > 0) {
                $names = $missedCountries->pluck('name')->implode(', ');
                $hints[] = [
                    'type'   => 'tip',
                    'key'    => 'missedCountries',
                    'params' => ['countries' => $names, 'n' => $missedCountries->count()],
                    'action' => '/app/countries',
                ];
            }
        }

        // Слабое покрытие популярных направлений
        if ($topPopular && $topPopular->count() >= 5) {
            $covered = $topPopular->filter(fn ($c) => $c->agency_works)->count();
            if ($covered < 2) {
                $hints[] = [
                    'type'   => 'warning',
                    'key'    => 'lowCoverage',
                    'params' => ['covered' => $covered, 'total' => $topPopular->count()],
                    'action' => '/app/countries',
                ];
            }
        }

        // Один канал доминирует — риск
        if ($totalLeads > 10) {
            $topSource = $byLeadSource->first();
            if ($topSource && $topSource->count / $totalLeads > 0.6) {
                $hints[] = [
                    'type'   => 'tip',
                    'key'    => 'singleChannel',
                    'params' => ['source' => $topSource->source, 'pct' => round($topSource->count / $totalLeads * 100)],
                    'action' => '/app/leadgen',
                ];
            }
        }

        // Instagram высокий интерес, низкая конверсия
        $igLeads = $byLeadSource->firstWhere('source', 'instagram');
        if ($igLeads && $totalLeads > 10 && $igLeads->count / $totalLeads > 0.15) {
            $igCases = VisaCase::where('agency_id', $agencyId)->where('lead_source', 'instagram')->where('stage', 'result')->count();
            $igConversion = $igLeads->count > 0 ? round($igCases / $igLeads->count * 100) : 0;
            if ($igConversion < 30) {
                $hints[] = [
                    'type'   => 'tip',
                    'key'    => 'igLowConversion',
                    'params' => ['pct' => $igConversion],
                    'action' => '/app/leadgen',
                ];
            }
        }

        // Telegram качественные заявки
        $tgLeads = $byLeadSource->firstWhere('source', 'telegram');
        if ($tgLeads && $totalLeads > 5 && $tgLeads->count >= 3) {
            $tgCases = VisaCase::where('agency_id', $agencyId)->where('lead_source', 'telegram')->where('stage', 'result')->where('result_type', 'approved')->count();
            $tgConversion = $tgLeads->count > 0 ? round($tgCases / $tgLeads->count * 100) : 0;
            if ($tgConversion > 50) {
                $hints[] = [
                    'type'   => 'success',
                    'key'    => 'tgHighConversion',
                    'params' => ['pct' => $tgConversion],
                    'action' => '/app/leadgen',
                ];
            }
        }

        // Мало рекомендаций
        $referralCount = $byLeadSource->firstWhere('source', 'referral')?->count ?? 0;
        if ($totalLeads > 10 && $referralCount / $totalLeads < 0.05) {
            $hints[] = [
                'type'   => 'tip',
                'key'    => 'lowReferrals',
                'params' => [],
                'action' => '/app/leadgen',
            ];
        }

        // Все OK
        if ($totalActive === 0 && $overdue === 0) {
            $hints[] = [
                'type'   => 'success',
                'key'    => 'allClear',
                'params' => [],
            ];
        }

        return $hints;
    }
}
