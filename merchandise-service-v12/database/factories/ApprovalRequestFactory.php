<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => 1,
            'entity_type' => 'merchandise_order',
            'entity_id' => fake()->numberBetween(1, 2000),
            'status' => 'draft',
            'buyer_store_id' => fake()->optional()->numberBetween(100, 999),
            'vendor_store_id' => fake()->optional()->numberBetween(100, 999),
            'approver_role' => 'admin',
            'requested_by' => fake()->numberBetween(1, 100),
            'submitted_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'decision_reason' => null,
        ];
    }
}
