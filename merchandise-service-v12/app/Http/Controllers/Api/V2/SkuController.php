<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\DTOs\SkuDTO;
use App\Http\Requests\Sku\CreateSkuRequest;
use App\Http\Requests\Sku\UpdateSkuRequest;
use App\Services\Sku\SkuServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SkuController extends Controller
{
    public function __construct(
        private readonly SkuServiceInterface $skuService,
    ) {}

    public function index(): JsonResponse
    {
        $skus = $this->skuService->listSkus(request()->only(['is_active', 'category']));

        return response()->json([
            'data' => $skus->items(),
            'meta' => [
                'total' => $skus->total(),
                'per_page' => $skus->perPage(),
                'current_page' => $skus->currentPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $sku = $this->skuService->findOrFail($id);

        return response()->json(['data' => $sku]);
    }

    public function store(CreateSkuRequest $request): JsonResponse
    {
        $companyId = (int) $request->attributes->get('company_id');
        $dto = SkuDTO::fromArray($request->validated(), $companyId);
        $sku = $this->skuService->createSku($dto);

        return response()->json(['data' => $sku], 201);
    }

    public function update(UpdateSkuRequest $request, int $id): JsonResponse
    {
        $companyId = (int) $request->attributes->get('company_id');
        $existingSku = $this->skuService->findOrFail($id);
        $dto = SkuDTO::fromArray(array_merge([
            'sku_code' => $existingSku->sku_code,
            'name' => $existingSku->name,
            'unit_price_cents' => $existingSku->unit_price_cents,
            'stock_quantity' => $existingSku->stock_quantity,
            'description' => $existingSku->description,
            'category' => $existingSku->category,
            'images' => $existingSku->images ?? [],
            'is_active' => $existingSku->is_active,
        ], $request->validated()), $companyId);
        $sku = $this->skuService->updateSku($id, $dto);

        return response()->json(['data' => $sku]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->skuService->deleteSku($id);

        return response()->json(['message' => 'SKU deactivated.']);
    }
}
