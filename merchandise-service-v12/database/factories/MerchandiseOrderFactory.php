<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\MerchandiseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchandiseOrderFactory extends Factory
{
    protected $model = MerchandiseOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'order_ref' => 'MORD-'.now()->format('Ymd').'-'.str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT),
            'customer_id' => $this->faker->numberBetween(1, 100),
            'order_kind' => 'standard',
            'buyer_store_id' => null,
            'vendor_store_id' => null,
            'fulfillment_store_id' => null,
            'status' => OrderStatus::Submitted,
            'subtotal_cents' => 0,
            'total_cents' => 0,
            'notes' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_reason' => null,
            'approval_request_id' => null,
        ];
    }

    public function pendingApproval(): static
    {
        return $this->state(['status' => OrderStatus::PendingApproval]);
    }

    public function approved(): static
    {
        return $this->state(['status' => OrderStatus::Approved]);
    }

    public function partiallyApproved(): static
    {
        return $this->state(['status' => OrderStatus::PartiallyApproved]);
    }

    public function processing(): static
    {
        return $this->state(['status' => OrderStatus::Processing]);
    }

    public function dispatched(): static
    {
        return $this->state(['status' => OrderStatus::Dispatched]);
    }

    public function acknowledged(): static
    {
        return $this->state(['status' => OrderStatus::Acknowledged]);
    }

    public function invoiceGenerated(): static
    {
        return $this->state(['status' => OrderStatus::InvoiceGenerated]);
    }
}
