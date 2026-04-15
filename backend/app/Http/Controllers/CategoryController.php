<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\CategoryResource;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            $t0 = microtime(true);
            try {
                $cacheKey = 'categories:all';
                $wasPresent = Cache::has($cacheKey);
                $data = Cache::remember($cacheKey, 1800, function () {
                    $t1 = microtime(true);
                    $cats = Category::orderBy('name')->get(['id', 'name', 'slug']);
                    $t2 = microtime(true);
                    $payload = CategoryResource::collection($cats)->resolve();
                    $t3 = microtime(true);
                    \Log::info('perf.categories.index', [
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
                    \Log::info('perf.categories.index', [
                        'cache_hit' => true,
                        'durations_ms' => [ 'total' => round(($tAfter-$t0)*1000) ],
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Categories cache unavailable, falling back', ['error' => $e->getMessage()]);
                $cats = Category::orderBy('name')->get(['id', 'name', 'slug']);
                $data = CategoryResource::collection($cats)->resolve();
            }
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('GET /api/categories failed', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Server error fetching categories'], 500);
        }
    }
}
