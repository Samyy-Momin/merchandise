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
            try {
                $data = Cache::remember('categories:all', 1800, function () {
                    $cats = Category::orderBy('name')->get(['id', 'name', 'slug']);
                    return CategoryResource::collection($cats)->resolve();
                });
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
