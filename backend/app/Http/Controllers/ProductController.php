<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::query()->with(['categoryRelation:id,name']);

            // Support both 'search' and legacy 'q' param
            $search = $request->query('search', $request->query('q'));
            if (!empty($search)) {
                $query->where('name', 'like', "%{$search}%");
            }

            if ($categoryId = $request->query('category')) {
                $query->where('category_id', $categoryId);
            }

            $min = $request->query('min');
            $max = $request->query('max');
            if ($min !== null && $max !== null) {
                $query->whereBetween('price', [(float)$min, (float)$max]);
            } elseif ($min !== null) {
                $query->where('price', '>=', (float)$min);
            } elseif ($max !== null) {
                $query->where('price', '<=', (float)$max);
            }

            $products = $query->orderBy('id', 'desc')->paginate(10);

            // Cache per filter set for 5 minutes
            $cacheKey = 'products:' . md5(json_encode([
                'category' => $request->query('category'),
                'search' => $search,
                'min' => $request->query('min'),
                'max' => $request->query('max'),
                'page' => $request->query('page', 1),
            ]));

            try {
                $data = Cache::remember($cacheKey, 300, function () use ($products) {
                    return ProductResource::collection($products)->response()->getData(true);
                });
            } catch (\Throwable $e) {
                Log::warning('Products cache unavailable, falling back', [
                    'error' => $e->getMessage(),
                ]);
                $data = ProductResource::collection($products)->response()->getData(true);
            }

            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('GET /api/products failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Server error fetching products'], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $product = Product::with(['categoryRelation:id,name'])->findOrFail($id);
            // Keep full product for detail view compatibility
            return response()->json($product);
        } catch (\Throwable $e) {
            Log::error('GET /api/products/{id} failed', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Product not found'], 404);
        }
    }
}
