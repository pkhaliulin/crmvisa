<?php

namespace App\Modules\Document\Services;

use App\Modules\Case\Models\VisaCase;
use Illuminate\Support\Carbon;

class CrossDocumentValidatorService
{
    /**
     * Validate cross-document consistency for a visa case.
     * Returns array of mismatches and warnings.
     */
    public function validate(VisaCase $case, array $documentsData): array
    {
        $mismatches = [];

        // 0. Profile vs documents — client profile must match passport data
        $mismatches = array_merge($mismatches, $this->checkProfileConsistency($case, $documentsData));

        // 1. Name consistency — all documents must match passport name
        $mismatches = array_merge($mismatches, $this->checkNameConsistency($documentsData));

        // 2. Date consistency — travel dates across documents
        $mismatches = array_merge($mismatches, $this->checkDateConsistency($documentsData, $case));

        // 3. Financial consistency — income matches bank statement
        $mismatches = array_merge($mismatches, $this->checkFinancialConsistency($documentsData));

        // 4. Student consistency — enrollment matches study plan, tuition, dates
        $mismatches = array_merge($mismatches, $this->checkStudentConsistency($documentsData));

        // 5. Business consistency — invitation matches trip, employer
        $mismatches = array_merge($mismatches, $this->checkBusinessConsistency($documentsData));

        return $mismatches;
    }

    /**
     * Calculate case-level risk score based on all documents.
     */
    public function calculateCaseRisk(VisaCase $case, array $documentsAnalysis): array
    {
        $totalWeight = 0;
        $riskScore = 0;
        $criticalMissing = [];
        $lowConfidenceDocs = [];
        $stopFactorsFound = [];

        foreach ($documentsAnalysis as $slug => $analysis) {
            $weight = $analysis['weight'] ?? 1;
            $totalWeight += $weight;

            // Document-level risk
            $docRisk = 0;
            if (($analysis['status'] ?? '') === 'missing' && ($analysis['required'] ?? false)) {
                $docRisk = 100;
                $criticalMissing[] = $slug;
            } elseif (isset($analysis['confidence'])) {
                $docRisk = max(0, 100 - $analysis['confidence']);
            }

            // Stop factors
            if (!empty($analysis['stop_factors'])) {
                $stopFactorsFound = array_merge($stopFactorsFound, $analysis['stop_factors']);
                $docRisk = max($docRisk, 90);
            }

            // Low confidence
            if (isset($analysis['confidence']) && $analysis['confidence'] < 50) {
                $lowConfidenceDocs[] = $slug;
            }

            $riskScore += $docRisk * $weight;
        }

        $overallRisk = $totalWeight > 0 ? (int) ($riskScore / $totalWeight) : 0;

        // Completeness
        $totalRequired = count(array_filter($documentsAnalysis, fn($a) => $a['required'] ?? false));
        $uploadedRequired = count(array_filter($documentsAnalysis, fn($a) => ($a['required'] ?? false) && ($a['status'] ?? '') !== 'missing'));
        $completeness = $totalRequired > 0 ? (int) (($uploadedRequired / $totalRequired) * 100) : 100;

        return [
            'overall_risk_score'   => min(100, $overallRisk),
            'risk_level'           => $this->riskLevel($overallRisk),
            'completeness_percent' => $completeness,
            'critical_missing'     => $criticalMissing,
            'low_confidence_docs'  => $lowConfidenceDocs,
            'stop_factors'         => $stopFactorsFound,
            'submission_ready'     => empty($criticalMissing) && empty($stopFactorsFound) && $completeness >= 80,
        ];
    }

    private function riskLevel(int $score): string
    {
        return match (true) {
            $score >= 70 => 'critical',
            $score >= 40 => 'medium',
            $score >= 15 => 'low',
            default      => 'minimal',
        };
    }

    /**
     * Сравнить данные профиля клиента с данными из паспорта (AI-извлечение).
     */
    private function checkProfileConsistency(VisaCase $case, array $docs): array
    {
        $mismatches = [];

        $case->loadMissing('client');
        $client = $case->client;
        if (! $client) {
            return $mismatches;
        }

        $passport = $docs['foreign_passport']['extracted'] ?? [];
        if (empty($passport)) {
            return $mismatches;
        }

        // ФИО
        $clientName = $client->name;
        $aiSurname = $passport['surname'] ?? '';
        $aiGiven = $passport['given_names'] ?? '';
        $aiFullName = trim("{$aiSurname} {$aiGiven}");
        if ($clientName && $aiFullName) {
            $normalClient = $this->normalizeName($clientName);
            $normalAi = $this->normalizeName($aiFullName);
            if ($normalClient !== $normalAi && ! str_contains($normalClient, $this->normalizeName($aiSurname ?: '---'))) {
                $mismatches[] = [
                    'type'     => 'profile_mismatch',
                    'level'    => 'critical',
                    'source'   => 'client_profile',
                    'field'    => 'name',
                    'expected' => $aiFullName,
                    'found'    => $clientName,
                    'description' => "ФИО в профиле ({$clientName}) не совпадает с паспортом ({$aiFullName})",
                ];
            }
        }

        // Дата рождения
        $clientDob = $client->date_of_birth;
        $aiDob = $passport['date_of_birth'] ?? null;
        if ($clientDob && $aiDob) {
            try {
                $clientDate = Carbon::parse($clientDob)->format('Y-m-d');
                $aiDate = Carbon::parse($aiDob)->format('Y-m-d');
                if ($clientDate !== $aiDate) {
                    $mismatches[] = [
                        'type'     => 'profile_mismatch',
                        'level'    => 'critical',
                        'source'   => 'client_profile',
                        'field'    => 'date_of_birth',
                        'expected' => $aiDate,
                        'found'    => $clientDate,
                        'description' => "Дата рождения в профиле ({$clientDate}) не совпадает с паспортом ({$aiDate})",
                    ];
                }
            } catch (\Throwable) {}
        }

        // Номер паспорта
        $clientPassport = $client->passport_number;
        $aiPassport = $passport['passport_number'] ?? null;
        if ($clientPassport && $aiPassport) {
            $cleanClient = preg_replace('/[\s\-]/', '', mb_strtoupper($clientPassport));
            $cleanAi = preg_replace('/[\s\-]/', '', mb_strtoupper($aiPassport));
            if ($cleanClient !== $cleanAi) {
                $mismatches[] = [
                    'type'     => 'profile_mismatch',
                    'level'    => 'critical',
                    'source'   => 'client_profile',
                    'field'    => 'passport_number',
                    'expected' => $aiPassport,
                    'found'    => $clientPassport,
                    'description' => "Номер паспорта в профиле ({$clientPassport}) не совпадает с документом ({$aiPassport})",
                ];
            }
        }

        // Срок паспорта
        $clientExpiry = $client->passport_expires_at;
        $aiExpiry = $passport['expiry_date'] ?? null;
        if ($clientExpiry && $aiExpiry) {
            try {
                $clientDate = Carbon::parse($clientExpiry)->format('Y-m-d');
                $aiDate = Carbon::parse($aiExpiry)->format('Y-m-d');
                if ($clientDate !== $aiDate) {
                    $mismatches[] = [
                        'type'     => 'profile_mismatch',
                        'level'    => 'warning',
                        'source'   => 'client_profile',
                        'field'    => 'passport_expires_at',
                        'expected' => $aiDate,
                        'found'    => $clientDate,
                        'description' => "Срок паспорта в профиле ({$clientDate}) не совпадает с документом ({$aiDate})",
                    ];
                }
            } catch (\Throwable) {}
        }

        return $mismatches;
    }

    private function checkNameConsistency(array $docs): array
    {
        $mismatches = [];
        $passportName = $docs['foreign_passport']['extracted']['full_name'] ?? null;

        if (!$passportName) {
            return $mismatches;
        }

        $nameFields = [
            'income_certificate'       => 'employee_name',
            'bank_balance_certificate' => 'account_holder',
            'bank_statement'           => 'account_holder',
            'employment_certificate'   => 'employee_name',
            'hotel_booking'            => 'guest_name',
            'air_tickets'              => 'passenger_name',
            'travel_insurance'         => 'insured_name',
            'university_acceptance'    => 'student_name',
        ];

        $normalizedPassport = $this->normalizeName($passportName);

        foreach ($nameFields as $docSlug => $field) {
            $docName = $docs[$docSlug]['extracted'][$field] ?? null;
            if ($docName && $this->normalizeName($docName) !== $normalizedPassport) {
                $mismatches[] = [
                    'type'     => 'name_mismatch',
                    'level'    => 'warning',
                    'source'   => $docSlug,
                    'field'    => $field,
                    'expected' => $passportName,
                    'found'    => $docName,
                    'message'  => "Имя в {$docSlug} ({$docName}) не совпадает с паспортом ({$passportName})",
                ];
            }
        }

        return $mismatches;
    }

    private function checkDateConsistency(array $docs, VisaCase $case): array
    {
        $mismatches = [];

        $travelDate = $case->travel_date;
        $returnDate = $case->return_date;

        if (!$travelDate) return $mismatches;

        // Hotel check-in should match travel date
        $hotelCheckIn = $docs['hotel_booking']['extracted']['check_in_date'] ?? null;
        if ($hotelCheckIn) {
            try {
                $checkIn = Carbon::parse($hotelCheckIn);
                if ($checkIn->diffInDays($travelDate) > 2) {
                    $mismatches[] = [
                        'type'    => 'date_mismatch',
                        'level'   => 'warning',
                        'source'  => 'hotel_booking',
                        'message' => "Дата заселения ({$hotelCheckIn}) не совпадает с датой поездки ({$travelDate->format('Y-m-d')})",
                    ];
                }
            } catch (\Exception $e) {}
        }

        // Flight departure should match travel date
        $flightDate = $docs['air_tickets']['extracted']['departure_date'] ?? null;
        if ($flightDate) {
            try {
                $departure = Carbon::parse($flightDate);
                if ($departure->diffInDays($travelDate) > 2) {
                    $mismatches[] = [
                        'type'    => 'date_mismatch',
                        'level'   => 'warning',
                        'source'  => 'air_tickets',
                        'message' => "Дата вылета ({$flightDate}) не совпадает с датой поездки ({$travelDate->format('Y-m-d')})",
                    ];
                }
            } catch (\Exception $e) {}
        }

        // Insurance must cover travel period
        $insuranceStart = $docs['travel_insurance']['extracted']['start_date'] ?? null;
        $insuranceEnd = $docs['travel_insurance']['extracted']['end_date'] ?? null;
        if ($insuranceStart && $insuranceEnd && $returnDate) {
            try {
                $insStart = Carbon::parse($insuranceStart);
                $insEnd = Carbon::parse($insuranceEnd);
                if ($insStart->isAfter($travelDate)) {
                    $mismatches[] = [
                        'type'    => 'date_mismatch',
                        'level'   => 'critical',
                        'source'  => 'travel_insurance',
                        'message' => "Страховка начинается ({$insuranceStart}) позже даты поездки ({$travelDate->format('Y-m-d')})",
                    ];
                }
                if ($insEnd->isBefore($returnDate)) {
                    $mismatches[] = [
                        'type'    => 'date_mismatch',
                        'level'   => 'critical',
                        'source'  => 'travel_insurance',
                        'message' => "Страховка заканчивается ({$insuranceEnd}) раньше даты возврата ({$returnDate->format('Y-m-d')})",
                    ];
                }
            } catch (\Exception $e) {}
        }

        // Passport expiry check
        $passportExpiry = $docs['foreign_passport']['extracted']['expiry_date'] ?? null;
        if ($passportExpiry && $returnDate) {
            try {
                $expiry = Carbon::parse($passportExpiry);
                $monthsAfterReturn = $expiry->diffInMonths($returnDate, false);
                if ($monthsAfterReturn < 3) {
                    $mismatches[] = [
                        'type'    => 'validity_issue',
                        'level'   => 'critical',
                        'source'  => 'foreign_passport',
                        'message' => "Паспорт истекает менее чем через 3 мес. после возврата ({$passportExpiry})",
                    ];
                }
            } catch (\Exception $e) {}
        }

        return $mismatches;
    }

    private function checkFinancialConsistency(array $docs): array
    {
        $mismatches = [];

        // Income from certificate vs bank statement regular deposits
        $certIncome = (float) ($docs['income_certificate']['extracted']['monthly_income'] ?? 0);
        $bankAvgIncome = (float) ($docs['bank_statement']['extracted']['avg_monthly_income'] ?? 0);

        if ($certIncome > 0 && $bankAvgIncome > 0) {
            $ratio = $bankAvgIncome / $certIncome;
            if ($ratio < 0.5) {
                $mismatches[] = [
                    'type'    => 'financial_mismatch',
                    'level'   => 'warning',
                    'source'  => 'bank_statement',
                    'message' => "Среднемесячный доход по выписке ({$bankAvgIncome}) значительно ниже зарплаты из справки ({$certIncome})",
                ];
            }
        }

        // Employer from certificate matches bank statement source
        $certEmployer = $docs['income_certificate']['extracted']['employer_name'] ?? null;
        $empCertEmployer = $docs['employment_certificate']['extracted']['employer_name'] ?? null;
        if ($certEmployer && $empCertEmployer && $this->normalizeName($certEmployer) !== $this->normalizeName($empCertEmployer)) {
            $mismatches[] = [
                'type'    => 'employer_mismatch',
                'level'   => 'warning',
                'source'  => 'employment_certificate',
                'message' => "Работодатель в справке о доходах ({$certEmployer}) не совпадает с работодателем в справке с работы ({$empCertEmployer})",
            ];
        }

        return $mismatches;
    }

    private function checkStudentConsistency(array $docs): array
    {
        $mismatches = [];

        $acceptance = $docs['university_acceptance'] ?? null;
        $studyPlan = $docs['study_plan'] ?? null;
        $tuition = $docs['tuition_payment'] ?? null;

        // University name consistency
        $acceptanceUni = $acceptance['extracted']['university_name'] ?? null;
        $tuitionUni = $tuition['extracted']['university_name'] ?? null;
        if ($acceptanceUni && $tuitionUni && $this->normalizeName($acceptanceUni) !== $this->normalizeName($tuitionUni)) {
            $mismatches[] = [
                'type'    => 'student_mismatch',
                'level'   => 'warning',
                'source'  => 'tuition_payment',
                'message' => "Университет в квитанции об оплате ({$tuitionUni}) не совпадает с letter of acceptance ({$acceptanceUni})",
            ];
        }

        // Course start date vs travel date
        $courseStart = $acceptance['extracted']['start_date'] ?? null;
        if ($courseStart) {
            try {
                $start = Carbon::parse($courseStart);
                if ($start->isPast()) {
                    $mismatches[] = [
                        'type'    => 'student_mismatch',
                        'level'   => 'critical',
                        'source'  => 'university_acceptance',
                        'message' => "Дата начала курса ({$courseStart}) уже прошла",
                    ];
                }
            } catch (\Exception $e) {}
        }

        return $mismatches;
    }

    private function checkBusinessConsistency(array $docs): array
    {
        $mismatches = [];

        $invitation = $docs['business_invitation'] ?? null;
        $conference = $docs['conference_invitation'] ?? null;

        // Business invitation dates vs trip dates
        if ($invitation) {
            $visitStart = $invitation['extracted']['visit_start'] ?? null;
            $visitEnd = $invitation['extracted']['visit_end'] ?? null;

            // Check against air tickets
            $flightDate = $docs['air_tickets']['extracted']['departure_date'] ?? null;
            if ($visitStart && $flightDate) {
                try {
                    $vs = Carbon::parse($visitStart);
                    $fd = Carbon::parse($flightDate);
                    if (abs($vs->diffInDays($fd)) > 3) {
                        $mismatches[] = [
                            'type'    => 'business_mismatch',
                            'level'   => 'warning',
                            'source'  => 'business_invitation',
                            'message' => "Даты в приглашении ({$visitStart}) не совпадают с авиабилетами ({$flightDate})",
                        ];
                    }
                } catch (\Exception $e) {}
            }
        }

        return $mismatches;
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    }
}
