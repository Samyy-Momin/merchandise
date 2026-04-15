<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApprovalAction;
use App\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalAction>
 */
class ApprovalActionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'approval_request_id' => ApprovalRequest::factory(),
            'action' => fake()->randomElement(['created', 'submitted', 'approved', 'rejected']),
            'actor_id' => fake()->optional()->numberBetween(1, 100),
            'actor_role' => fake()->optional()->randomElement(['customer', 'admin', 'senior_manager', 'super_admin']),
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
