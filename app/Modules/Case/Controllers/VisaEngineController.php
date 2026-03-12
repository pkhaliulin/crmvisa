<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Engines\EngineRegistry;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class VisaEngineController extends Controller
{
    /**
     * GET /visa-engine/countries
     *
     * Список поддерживаемых стран с engine и их статусом.
     */
    public function countries(): JsonResponse
    {
        $countries = EngineRegistry::supportedCountries();

        return ApiResponse::success($countries);
    }

    /**
     * GET /visa-engine/{code}/visa-types
     *
     * Типы виз для указанной страны.
     */
    public function visaTypes(string $code): JsonResponse
    {
        $engine = EngineRegistry::resolve($code);

        return ApiResponse::success([
            'country_code' => $engine->getCountryCode(),
            'country_name' => $engine->getCountryName(),
            'schengen'     => $engine->isSchengen(),
            'status'       => $engine->getStatus(),
            'visa_types'   => $engine->getVisaTypes(),
            'embassy'      => $engine->getEmbassyInfo(),
            'submission_url' => $engine->getExternalSubmissionUrl(),
        ]);
    }

    /**
     * GET /visa-engine/{code}/requirements/{visaType}
     *
     * Документы и шаги формы для указанного типа визы.
     */
    public function requirements(string $code, string $visaType): JsonResponse
    {
        $engine = EngineRegistry::resolve($code);

        // Проверить что тип визы существует для данной страны
        $validTypes = array_column($engine->getVisaTypes(), 'code');
        if (! in_array($visaType, $validTypes)) {
            return ApiResponse::error(
                "Тип визы '{$visaType}' не поддерживается для страны {$engine->getCountryName()}.",
                ['valid_types' => $validTypes],
                422
            );
        }

        return ApiResponse::success([
            'country_code'    => $engine->getCountryCode(),
            'country_name'    => $engine->getCountryName(),
            'visa_type'       => $visaType,
            'status'          => $engine->getStatus(),
            'estimated_days'  => $engine->getEstimatedDays($visaType),
            'documents'       => $engine->getRequiredDocuments($visaType),
            'form_steps'      => $engine->getFormSteps($visaType),
            'stages'          => $engine->getProcessingStages(),
            'embassy'         => $engine->getEmbassyInfo(),
            'submission_url'  => $engine->getExternalSubmissionUrl(),
        ]);
    }
}
