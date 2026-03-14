<?php

namespace App\Modules\Document\DTOs;

readonly class PassportData
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $middleName = null,
        public ?string $passportNumber = null,
        public ?string $nationality = null,       // ISO 3-letter code (UZB, RUS, etc.)
        public ?string $dateOfBirth = null,        // Y-m-d
        public ?string $dateOfExpiry = null,       // Y-m-d
        public ?string $dateOfIssue = null,        // Y-m-d
        public ?string $gender = null,             // M / F
        public ?string $placeOfBirth = null,
        public ?string $issuingAuthority = null,
        public ?string $mrzLine1 = null,
        public ?string $mrzLine2 = null,
        public float $confidence = 0.0,
        public ?string $rawResponse = null,
        public ?string $provider = null,
        public ?string $documentType = null,    // foreign_passport, id_card, internal_passport
        public ?string $scriptType = null,      // latin, cyrillic, mixed
        public ?string $firstNameLatin = null,
        public ?string $lastNameLatin = null,
        public ?string $firstNameCyrillic = null,
        public ?string $lastNameCyrillic = null,
        public ?string $pnfl = null,
    ) {}

    public static function fromArray(array $data, ?string $provider = null, ?string $rawResponse = null): self
    {
        $mrzLine2 = $data['mrz_line2'] ?? null;

        // ПИНФЛ: сначала из OCR, если невалиден — извлечь из MRZ line 2
        $pnfl = self::validatePnfl($data['pnfl'] ?? null);
        if (!$pnfl && $mrzLine2) {
            $pnfl = self::extractPnflFromMrz($mrzLine2);
        }

        return new self(
            firstName:        $data['first_name'] ?? null,
            lastName:         $data['last_name'] ?? null,
            middleName:       $data['middle_name'] ?? null,
            passportNumber:   $data['passport_number'] ?? null,
            nationality:      $data['nationality'] ?? null,
            dateOfBirth:      $data['date_of_birth'] ?? null,
            dateOfExpiry:     $data['date_of_expiry'] ?? null,
            dateOfIssue:      $data['date_of_issue'] ?? null,
            gender:             $data['gender'] ?? null,
            placeOfBirth:       $data['place_of_birth'] ?? null,
            issuingAuthority:   $data['issuing_authority'] ?? null,
            mrzLine1:           $data['mrz_line1'] ?? null,
            mrzLine2:           $mrzLine2,
            confidence:         (float) ($data['confidence'] ?? 0.0),
            rawResponse:        $rawResponse,
            provider:           $provider,
            documentType:       $data['document_type'] ?? null,
            scriptType:         $data['script_type'] ?? null,
            firstNameLatin:     $data['first_name_latin'] ?? null,
            lastNameLatin:      $data['last_name_latin'] ?? null,
            firstNameCyrillic:  $data['first_name_cyrillic'] ?? null,
            lastNameCyrillic:   $data['last_name_cyrillic'] ?? null,
            pnfl:               $pnfl,
        );
    }

    /**
     * ПИНФЛ = ровно 14 цифр. Всё остальное (MRZ, мусор) — отбрасываем.
     */
    private static function validatePnfl(?string $value): ?string
    {
        if ($value === null) return null;
        $digits = preg_replace('/\D/', '', $value);
        return strlen($digits) === 14 ? $digits : null;
    }

    /**
     * Извлечь ПИНФЛ из MRZ line 2 (TD3 формат, 44 символа).
     *
     * Формат MRZ line 2:
     *   [0-8]   Номер паспорта (9 символов)
     *   [9]     Контрольная цифра номера
     *   [10-12] Гражданство (3 буквы)
     *   [13-18] Дата рождения YYMMDD (6 цифр)
     *   [19]    Контрольная цифра DOB
     *   [20]    Пол (M/F/<)
     *   [21-26] Дата истечения YYMMDD (6 цифр)
     *   [27]    Контрольная цифра даты истечения
     *   [28-41] Персональный номер = ПИНФЛ (14 цифр)
     *   [42]    Контрольная цифра перс. номера
     *   [43]    Общая контрольная цифра
     *
     * ПИНФЛ (14 цифр): S + DDMMYY + RRRR + NN + C
     *   S = код пола+века (3=муж 1900-1999, 4=жен 1900-1999, 5=муж 2000+, 6=жен 2000+)
     *   DDMMYY = дата рождения
     *   RRRR = код региона
     *   NN = порядковый номер
     *   C = контрольная цифра
     */
    private static function extractPnflFromMrz(string $mrzLine2): ?string
    {
        // MRZ TD3 = ровно 44 символа
        if (strlen($mrzLine2) !== 44) return null;

        // Позиции 28-41 = персональный номер (ПИНФЛ)
        $personalNumber = substr($mrzLine2, 28, 14);

        // Должно быть ровно 14 цифр
        if (!preg_match('/^\d{14}$/', $personalNumber)) return null;

        // Валидация структуры: первая цифра — код пола+века (1-6)
        $sexCentury = (int) $personalNumber[0];
        if ($sexCentury < 1 || $sexCentury > 6) return null;

        // Валидация: DDMMYY в позициях 1-6 должна быть реальной датой
        $dd = (int) substr($personalNumber, 1, 2);
        $mm = (int) substr($personalNumber, 3, 2);
        if ($dd < 1 || $dd > 31 || $mm < 1 || $mm > 12) return null;

        return $personalNumber;
    }

    public function toArray(): array
    {
        return [
            'first_name'        => $this->firstName,
            'last_name'         => $this->lastName,
            'middle_name'       => $this->middleName,
            'passport_number'   => $this->passportNumber,
            'nationality'       => $this->nationality,
            'date_of_birth'     => $this->dateOfBirth,
            'date_of_expiry'    => $this->dateOfExpiry,
            'date_of_issue'     => $this->dateOfIssue,
            'gender'            => $this->gender,
            'place_of_birth'    => $this->placeOfBirth,
            'issuing_authority' => $this->issuingAuthority,
            'mrz_line1'         => $this->mrzLine1,
            'mrz_line2'         => $this->mrzLine2,
            'confidence'          => $this->confidence,
            'provider'            => $this->provider,
            'document_type'       => $this->documentType,
            'script_type'         => $this->scriptType,
            'first_name_latin'    => $this->firstNameLatin,
            'last_name_latin'     => $this->lastNameLatin,
            'first_name_cyrillic' => $this->firstNameCyrillic,
            'last_name_cyrillic'  => $this->lastNameCyrillic,
            'pnfl'                => $this->pnfl,
        ];
    }
}
