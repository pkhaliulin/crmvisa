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
            mrzLine2:           $data['mrz_line2'] ?? null,
            confidence:         (float) ($data['confidence'] ?? 0.0),
            rawResponse:        $rawResponse,
            provider:           $provider,
            documentType:       $data['document_type'] ?? null,
            scriptType:         $data['script_type'] ?? null,
            firstNameLatin:     $data['first_name_latin'] ?? null,
            lastNameLatin:      $data['last_name_latin'] ?? null,
            firstNameCyrillic:  $data['first_name_cyrillic'] ?? null,
            lastNameCyrillic:   $data['last_name_cyrillic'] ?? null,
            pnfl:               $data['pnfl'] ?? null,
        );
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
