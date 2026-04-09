<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\ProductResource;

class AdminProductController extends Controller
{
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => ['required','string','max:255'],
                'price' => ['required','numeric','min:0'],
                'category_id' => ['required','integer','exists:categories,id'],
                'description' => ['nullable','string'],
                'stock' => ['nullable','integer','min:0'],
                'image_url' => ['nullable','string','max:2048'],
            ]);

            $product = Product::create([
                'name' => $data['name'],
                'price' => $data['price'],
                'category_id' => $data['category_id'],
                'description' => $data['description'] ?? null,
                'stock' => $data['stock'] ?? 0,
                'image_url' => $data['image_url'] ?? null,
            ]);

            return (new ProductResource($product->load('categoryRelation:id,name')))->response();
        } catch (\Throwable $e) {
            Log::error('Admin create product failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create product'], 422);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $product = Product::findOrFail($id);
            $data = $request->validate([
                'name' => ['sometimes','required','string','max:255'],
                'price' => ['sometimes','required','numeric','min:0'],
                'category_id' => ['sometimes','required','integer','exists:categories,id'],
                'description' => ['nullable','string'],
                'stock' => ['nullable','integer','min:0'],
                'image_url' => ['nullable','string','max:2048'],
            ]);

            $product->fill($data);
            $product->save();
            return (new ProductResource($product->load('categoryRelation:id,name')))->response();
        } catch (\Throwable $e) {
            Log::error('Admin update product failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update product'], 422);
        }
    }

    public function destroy(int $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('Admin delete product failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete product'], 422);
        }
    }
}
