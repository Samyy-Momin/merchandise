<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MerchandiseOrder;
use App\Models\MerchandiseOrderItem;
use App\Models\MerchandiseSku;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchandiseOrderItemFactory extends Factory
{
    protected $model = MerchandiseOrderItem::class;

    public function definition(): array
    {
        $qty = $this->faker->numberBetween(1, 20);
        $price = $this->faker->numberBetween(500, 50000);

        return [
            'company_id' => 1,
            'order_id' => MerchandiseOrder::factory(),
            'sku_id' => MerchandiseSku::factory(),
            'sku_code' => strtoupper($this->faker->bothify('SKU-####')),
            'sku_name' => $this->faker->words(3, true),
            'requested_quantity' => $qty,
            'approved_quantity' => null,
            'unit_price_cents' => $price,
            'line_total_cents' => 0,
        ];
    }
}
