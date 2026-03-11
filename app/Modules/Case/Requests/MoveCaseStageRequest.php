<?php

namespace App\Modules\Case\Requests;

use App\Modules\Case\Services\CaseService;
use Illuminate\Foundation\Http\FormRequest;

class MoveCaseStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', 'string', 'in:' . implode(',', array_keys(config('stages')))],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $case = $this->route('case');
            if (! $case) {
                return;
            }

            $newStage = $this->input('stage');
            $currentStage = $case->stage;
            $allowed = CaseService::ALLOWED_TRANSITIONS[$currentStage] ?? [];

            if (! in_array($newStage, $allowed)) {
                $validator->errors()->add(
                    'stage',
                    "Переход из «{$currentStage}» в «{$newStage}» невозможен."
                );
            }

            // Нельзя двигать заявку без назначенного менеджера
            if (! $case->assigned_to) {
                $validator->errors()->add(
                    'assigned_to',
                    'Невозможно переместить заявку без назначенного менеджера. Сначала назначьте ответственного.'
                );
            }
        });
    }
}
