<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(8),
            'price' => $this->faker->randomFloat(2, 50, 5000),
            'category' => $this->faker->randomElement(['Office', 'IT', 'Logistics', 'Misc']),
            'category_id' => Category::factory(),
            'image_url' => $this->faker->optional()->imageUrl(200, 200, 'product'),
            'stock' => $this->faker->numberBetween(0, 500),
        ];
    }
}

