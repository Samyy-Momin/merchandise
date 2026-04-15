<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MerchandiseInvoice;
use App\Models\MerchandisePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchandisePaymentFactory extends Factory
{
    protected $model = MerchandisePayment::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'invoice_id' => MerchandiseInvoice::factory(),
            'amount_cents' => $this->faker->numberBetween(1000, 100000),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'cheque', 'cash']),
            'reference' => $this->faker->optional()->bothify('REF-########'),
            'paid_at' => now(),
        ];
    }
}
