<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AcknowledgementStatus;
use App\Models\MerchandiseAcknowledgement;
use App\Models\MerchandiseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchandiseAcknowledgementFactory extends Factory
{
    protected $model = MerchandiseAcknowledgement::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'order_id' => MerchandiseOrder::factory()->acknowledged(),
            'acknowledged_by' => $this->faker->numberBetween(1, 100),
            'acknowledged_at' => now(),
            'notes' => $this->faker->optional()->sentence(),
            'status' => AcknowledgementStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status' => AcknowledgementStatus::Approved,
            'reviewed_by' => $this->faker->numberBetween(1, 50),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => AcknowledgementStatus::Rejected,
            'reviewed_by' => $this->faker->numberBetween(1, 50),
            'reviewed_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }
}
