<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\FakesKeycloakToken;
use App\Models\Category;
use App\Models\Product;

class CategoryProductTest extends TestCase
{
    use RefreshDatabase, FakesKeycloakToken;

    // ──────────────────────────────────────────────────
    // Categories
    // ──────────────────────────────────────────────────

    public function test_list_categories_returns_json_array(): void
    {
        Category::factory()->create(['name' => 'Stationery', 'slug' => 'stationery']);
        Category::factory()->create(['name' => 'Housekeeping', 'slug' => 'housekeeping']);

        $response = $this->withKeycloakToken(['buyer'])->getJson('/api/categories');
        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Stationery']);
    }

    public function test_categories_require_auth(): void
    {
        $this->getJson('/api/categories')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────
    // Products — Index
    // ──────────────────────────────────────────────────

    public function test_products_index_returns_paginated(): void
    {
        $cat = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $cat->id]);

        $response = $this->withKeycloakToken(['buyer'])->getJson('/api/products');
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'name', 'price', 'image_url', 'category']],
            'current_page',
            'last_page',
        ]);
    }

    public function test_products_filter_by_category(): void
    {
        $cat1 = Category::factory()->create(['name' => 'A']);
        $cat2 = Category::factory()->create(['name' => 'B']);
        Product::factory()->count(2)->create(['category_id' => $cat1->id]);
        Product::factory()->count(3)->create(['category_id' => $cat2->id]);

        $response = $this->withKeycloakToken(['buyer'])->getJson("/api/products?category={$cat1->id}");
        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_products_filter_by_search(): void
    {
        $cat = Category::factory()->create();
        Product::factory()->create(['name' => 'Red Pen', 'category_id' => $cat->id]);
        Product::factory()->create(['name' => 'Blue Notebook', 'category_id' => $cat->id]);

        $response = $this->withKeycloakToken(['buyer'])->getJson('/api/products?search=pen');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Red Pen', $response->json('data.0.name'));
    }

    public function test_products_filter_by_price_range(): void
    {
        $cat = Category::factory()->create();
        Product::factory()->create(['price' => 50, 'category_id' => $cat->id]);
        Product::factory()->create(['price' => 150, 'category_id' => $cat->id]);
        Product::factory()->create(['price' => 300, 'category_id' => $cat->id]);

        $response = $this->withKeycloakToken(['buyer'])->getJson('/api/products?min=100&max=200');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_products_per_page_caps_at_50(): void
    {
        $cat = Category::factory()->create();
        Product::factory()->count(60)->create(['category_id' => $cat->id]);

        $response = $this->withKeycloakToken(['buyer'])->getJson('/api/products?per_page=100');
        $response->assertOk();
        $this->assertLessThanOrEqual(50, count($response->json('data')));
    }

    // ──────────────────────────────────────────────────
    // Products — Show
    // ──────────────────────────────────────────────────

    public function test_product_show_returns_detail(): void
    {
        $cat = Category::factory()->create(['name' => 'Stationery']);
        $product = Product::factory()->create(['category_id' => $cat->id, 'name' => 'Pencil']);

        $response = $this->withKeycloakToken(['buyer'])->getJson("/api/products/{$product->id}");
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Pencil']);
    }

    public function test_product_show_returns_404_for_missing(): void
    {
        $response = $this->withKeycloakToken(['buyer'])->getJson('/api/products/999999');
        $response->assertStatus(404);
    }

    // ──────────────────────────────────────────────────
    // Admin — Categories
    // ──────────────────────────────────────────────────

    public function test_admin_can_create_category(): void
    {
        $response = $this->withKeycloakToken(['admin'])->postJson('/api/admin/categories', [
            'name' => 'Electronics',
        ]);
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Electronics']);
        $this->assertDatabaseHas('categories', ['name' => 'Electronics']);
    }

    public function test_buyer_cannot_create_category(): void
    {
        $response = $this->withKeycloakToken(['buyer'])->postJson('/api/admin/categories', [
            'name' => 'Electronics',
        ]);
        $response->assertStatus(403);
    }

    // ──────────────────────────────────────────────────
    // Admin — Products
    // ──────────────────────────────────────────────────

    public function test_admin_can_create_product(): void
    {
        $cat = Category::factory()->create();
        $response = $this->withKeycloakToken(['admin'])->postJson('/api/admin/products', [
            'name' => 'New Product',
            'price' => 99.50,
            'category_id' => $cat->id,
        ]);
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'New Product']);
    }

    public function test_admin_can_update_product(): void
    {
        $cat = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $cat->id, 'name' => 'Old']);

        $response = $this->withKeycloakToken(['admin'])->putJson("/api/admin/products/{$product->id}", [
            'name' => 'Updated',
        ]);
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Updated']);
    }

    public function test_admin_can_delete_product(): void
    {
        $cat = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $cat->id]);

        $response = $this->withKeycloakToken(['admin'])->deleteJson("/api/admin/products/{$product->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
