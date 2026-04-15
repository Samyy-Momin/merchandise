<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Resources\CategoryResource;

class AdminCategoryController extends Controller
{
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => ['required','string','max:255'],
                'slug' => ['nullable','string','max:255'],
            ]);
            $slug = $data['slug'] ?? Str::slug($data['name']);
            $base = $slug; $i = 1;
            while (Category::where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i; $i++;
            }
            $category = Category::create(['name' => $data['name'], 'slug' => $slug]);
            return (new CategoryResource($category))->response()->setStatusCode(200);
        } catch (\Throwable $e) {
            Log::error('Admin create category failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create category'], 422);
        }
    }
}
