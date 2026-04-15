<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MerchandiseSku;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchandiseSkuFactory extends Factory
{
    protected $model = MerchandiseSku::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'sku_code' => strtoupper($this->faker->bothify('SKU-####')),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['Stationery', 'Housekeeping', 'Branded']),
            'unit_price_cents' => $this->faker->numberBetween(100, 100000),
            'stock_quantity' => $this->faker->numberBetween(0, 500),
            'images' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
