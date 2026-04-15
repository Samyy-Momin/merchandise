<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MerchandiseDispatch;
use App\Models\MerchandiseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchandiseDispatchFactory extends Factory
{
    protected $model = MerchandiseDispatch::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'order_id' => MerchandiseOrder::factory()->dispatched(),
            'dispatched_by' => $this->faker->numberBetween(1, 50),
            'courier' => $this->faker->company(),
            'tracking_number' => strtoupper($this->faker->bothify('TRK-########')),
            'dispatched_at' => now(),
            'estimated_delivery_at' => now()->addDays(3),
        ];
    }
}
