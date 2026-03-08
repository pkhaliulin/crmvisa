<?php

namespace App\Modules\Case\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CaseCollection extends ResourceCollection
{
    /**
     * Каждый элемент коллекции оборачивается в CaseResource.
     */
    public $collects = CaseResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }

    /**
     * Мета-данные пагинации добавляются в ответ.
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'current_page' => $paginated['current_page'],
                'last_page'    => $paginated['last_page'],
                'per_page'     => $paginated['per_page'],
                'total'        => $paginated['total'],
            ],
        ];
    }
}
