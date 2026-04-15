<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::query()
                ->select(['id','name','price','image_url','category_id'])
                ->with(['categoryRelation:id,name']);

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

            // Respect per_page (cap to 50) and include in cache key
            $perPage = (int) $request->query('per_page', 10);
            if ($perPage < 1) { $perPage = 10; }
            if ($perPage > 50) { $perPage = 50; }

            // Cache per filter set for 5 minutes
            $cacheKey = 'products:' . md5(json_encode([
                'category' => $request->query('category'),
                'search' => $search,
                'min' => $request->query('min'),
                'max' => $request->query('max'),
                'page' => $request->query('page', 1),
                'per_page' => $perPage,
            ]));

            try {
                $t0 = microtime(true);
                $wasPresent = Cache::has($cacheKey);
                // Build data inside the cache closure to avoid DB work on cache hit
                $data = Cache::remember($cacheKey, 300, function () use ($query, $perPage) {
                    $t1 = microtime(true);
                    $products = (clone $query)->orderBy('id', 'desc')->paginate($perPage);
                    $t2 = microtime(true);
                    // Map collection to expected shape while keeping paginator meta at top-level
                    $mapped = $products->getCollection()->map(function ($p) {
                        $cat = $p->categoryRelation;
                        return [
                            'id' => $p->id,
                            'name' => $p->name,
                            'price' => (float) $p->price,
                            'image_url' => $p->image_url,
                            'category' => $cat ? ['id' => $cat->id, 'name' => $cat->name] : null,
                        ];
                    });
                    $products->setCollection($mapped);
                    $payload = $products->toArray();
                    $t3 = microtime(true);
                    \Log::info('perf.products.index', [
                        'cache_hit' => false,
                        'durations_ms' => [
                            'query' => round(($t2-$t1)*1000),
                            'serialize' => round(($t3-$t2)*1000),
                            'total_closure' => round(($t3-$t1)*1000),
                        ],
                    ]);
                    return $payload;
                });
                if ($wasPresent) {
                    $tAfter = microtime(true);
                    \Log::info('perf.products.index', [
                        'cache_hit' => true,
                        'durations_ms' => [ 'total' => round(($tAfter-$t0)*1000) ],
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Products cache unavailable, falling back', [
                    'error' => $e->getMessage(),
                ]);
                $products = $query->orderBy('id', 'desc')->paginate($perPage);
                $mapped = $products->getCollection()->map(function ($p) {
                    $cat = $p->categoryRelation;
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => (float) $p->price,
                        'image_url' => $p->image_url,
                        'category' => $cat ? ['id' => $cat->id, 'name' => $cat->name] : null,
                    ];
                });
                $products->setCollection($mapped);
                $data = $products->toArray();
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
