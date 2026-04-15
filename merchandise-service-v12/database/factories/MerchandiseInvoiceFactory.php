<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\MerchandiseInvoice;
use App\Models\MerchandiseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchandiseInvoiceFactory extends Factory
{
    protected $model = MerchandiseInvoice::class;

    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(10000, 500000);

        return [
            'company_id' => 1,
            'invoice_number' => 'MINV-'.now()->format('Ym').'-'.str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT),
            'order_id' => MerchandiseOrder::factory()->invoiceGenerated(),
            'customer_id' => $this->faker->numberBetween(1, 100),
            'status' => InvoiceStatus::PaymentPending,
            'subtotal_cents' => $subtotal,
            'tax_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => $subtotal,
            'amount_paid_cents' => 0,
            'due_date' => now()->addDays(15)->toDateString(),
        ];
    }

    public function paid(): static
    {
        return $this->state(function (array $attrs) {
            return [
                'status' => InvoiceStatus::Paid,
                'amount_paid_cents' => $attrs['total_cents'],
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state([
            'status' => InvoiceStatus::Overdue,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);
    }
}
