<?php

namespace App\Modules\Service\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Service\Models\AgencyServicePackage;
use App\Modules\Service\Models\AgencyServicePackageItem;
use App\Modules\Service\Models\GlobalService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceCatalogController extends Controller
{
    // -------------------------------------------------------------------------
    // Глобальный каталог
    // -------------------------------------------------------------------------

    public function index(): JsonResponse
    {
        $services = GlobalService::active()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success($services);
    }

    // Superadmin: создать глобальную услугу
    public function storeGlobal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug'          => ['required', 'string', 'max:100', 'unique:global_services,slug'],
            'name'          => ['required', 'string', 'max:255'],
            'category'      => ['required', 'in:consultation,documents,translation,visa_center,financial,other'],
            'description'   => ['sometimes', 'nullable', 'string'],
            'is_combinable' => ['sometimes', 'boolean'],
            'is_optional'   => ['sometimes', 'boolean'],
            'sort_order'    => ['sometimes', 'integer'],
            'is_active'     => ['sometimes', 'boolean'],
        ]);

        $service = GlobalService::create($data);

        return ApiResponse::created($service);
    }

    // Superadmin: обновить глобальную услугу
    public function updateGlobal(Request $request, string $id): JsonResponse
    {
        $service = GlobalService::findOrFail($id);

        $data = $request->validate([
            'slug'          => ['sometimes', 'string', 'max:100', "unique:global_services,slug,{$id}"],
            'name'          => ['sometimes', 'string', 'max:255'],
            'category'      => ['sometimes', 'in:consultation,documents,translation,visa_center,financial,other'],
            'description'   => ['sometimes', 'nullable', 'string'],
            'is_combinable' => ['sometimes', 'boolean'],
            'is_optional'   => ['sometimes', 'boolean'],
            'sort_order'    => ['sometimes', 'integer'],
            'is_active'     => ['sometimes', 'boolean'],
        ]);

        $service->update($data);

        return ApiResponse::success($service);
    }

    // Superadmin: удалить глобальную услугу
    public function destroyGlobal(string $id): JsonResponse
    {
        GlobalService::findOrFail($id)->delete();

        return ApiResponse::success(null, 'Service deleted.');
    }

    // -------------------------------------------------------------------------
    // Пакеты агентства
    // -------------------------------------------------------------------------

    public function myPackages(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $packages = AgencyServicePackage::where('agency_id', $agencyId)
            ->with(['items.service'])
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success($packages);
    }

    public function store(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $data = $request->validate([
            'country_code'    => ['sometimes', 'nullable', 'string', 'size:2'],
            'visa_type'       => ['sometimes', 'nullable', 'string', 'max:50'],
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['sometimes', 'nullable', 'string'],
            'price'           => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currency'        => ['sometimes', 'string', 'size:3'],
            'processing_days' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_active'       => ['sometimes', 'boolean'],
            'sort_order'      => ['sometimes', 'integer'],
            'service_ids'     => ['sometimes', 'array'],
            'service_ids.*'   => ['uuid', 'exists:global_services,id'],
        ]);

        $serviceIds = $data['service_ids'] ?? [];
        unset($data['service_ids']);
        $data['agency_id'] = $agencyId;

        $package = DB::transaction(function () use ($data, $serviceIds) {
            $package = AgencyServicePackage::create($data);

            foreach ($serviceIds as $i => $serviceId) {
                AgencyServicePackageItem::create([
                    'package_id' => $package->id,
                    'service_id' => $serviceId,
                    'sort_order' => $i,
                ]);
            }

            return $package->load('items.service');
        });

        return ApiResponse::created($package);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $package = AgencyServicePackage::where('agency_id', $agencyId)->findOrFail($id);

        $data = $request->validate([
            'country_code'    => ['sometimes', 'nullable', 'string', 'size:2'],
            'visa_type'       => ['sometimes', 'nullable', 'string', 'max:50'],
            'name'            => ['sometimes', 'string', 'max:255'],
            'description'     => ['sometimes', 'nullable', 'string'],
            'price'           => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currency'        => ['sometimes', 'string', 'size:3'],
            'processing_days' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_active'       => ['sometimes', 'boolean'],
            'sort_order'      => ['sometimes', 'integer'],
            'service_ids'     => ['sometimes', 'array'],
            'service_ids.*'   => ['uuid', 'exists:global_services,id'],
        ]);

        $serviceIds = $data['service_ids'] ?? null;
        unset($data['service_ids']);

        DB::transaction(function () use ($package, $data, $serviceIds) {
            $package->update($data);

            if ($serviceIds !== null) {
                $package->items()->delete();
                foreach ($serviceIds as $i => $serviceId) {
                    AgencyServicePackageItem::create([
                        'package_id' => $package->id,
                        'service_id' => $serviceId,
                        'sort_order' => $i,
                    ]);
                }
            }
        });

        return ApiResponse::success($package->fresh('items.service'));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        AgencyServicePackage::where('agency_id', $agencyId)->findOrFail($id)->delete();

        return ApiResponse::success(null, 'Package deleted.');
    }
}
