<?php

namespace App\Modules\Document\DTOs;

class DocumentAnalysisResult
{
    public function __construct(
        public readonly string $status, // 'analyzed', 'skipped', 'failed'
        public readonly array $extractedData = [],
        public readonly array $validationResults = [],
        public readonly int $confidence = 0,
        public readonly array $riskIndicators = [],
        public readonly array $stopFactors = [],
        public readonly array $successFactors = [],
        public readonly ?string $errorMessage = null,
        public readonly ?array $rawResponse = null,
    ) {}

    public static function skipped(string $reason): self
    {
        return new self(status: 'skipped', errorMessage: $reason);
    }

    public static function failed(string $error): self
    {
        return new self(status: 'failed', errorMessage: $error);
    }

    public function hasStopFactors(): bool
    {
        return !empty($this->stopFactors);
    }

    public function hasCriticalRisks(): bool
    {
        return collect($this->riskIndicators)->contains(fn($r) => $r['level'] === 'critical');
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'extracted_data' => $this->extractedData,
            'validation_results' => $this->validationResults,
            'confidence' => $this->confidence,
            'risk_indicators' => $this->riskIndicators,
            'stop_factors' => $this->stopFactors,
            'success_factors' => $this->successFactors,
            'error_message' => $this->errorMessage,
        ];
    }
}
