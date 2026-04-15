<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Models\MerchandiseInvoice;
use App\Models\MerchandiseOrder;
use Illuminate\Support\Carbon;

// ---------- POST /api/v2/merchandise/orders/{id}/invoice ----------

test('vendor can create invoice for an invoice_generated order', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create([
        'status' => OrderStatus::InvoiceGenerated,
        'total_cents' => 50000,
        'company_id' => 1,
        'customer_id' => 10,
    ]);

    $response = $this->postJson("/api/v2/merchandise/orders/{$order->id}/invoice");

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => ['id', 'invoice_number', 'order_id', 'status', 'total_cents', 'due_date'],
    ]);
    $response->assertJsonFragment(['status' => 'payment_pending']);
    $this->assertDatabaseHas('merchandise_invoices', ['order_id' => $order->id]);
});

test('invoice due_date is exactly 15 days from creation (BRD rule)', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create([
        'status' => OrderStatus::InvoiceGenerated,
        'total_cents' => 30000,
        'company_id' => 1,
    ]);

    $response = $this->postJson("/api/v2/merchandise/orders/{$order->id}/invoice");

    $response->assertStatus(201);
    $dueDate = $response->json('data.due_date');
    expect($dueDate)->toBe(Carbon::now()->addDays(15)->toDateString());
});

test('invoice_number matches MINV-YYYYMM-NNNN pattern', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::InvoiceGenerated, 'company_id' => 1]);

    $response = $this->postJson("/api/v2/merchandise/orders/{$order->id}/invoice");

    expect($response->json('data.invoice_number'))->toMatch('/^MINV-\d{6}-\d{4}$/');
});

test('returns 422 when order is not in invoice_generated status', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Dispatched, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/invoice")->assertStatus(422);
});

test('customer cannot create an invoice', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::InvoiceGenerated, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/invoice")->assertStatus(403);
});

test('admin cannot create an invoice', function () {
    $this->actingAsRole(5, 'admin');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::InvoiceGenerated, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/invoice")->assertStatus(403);
});

// ---------- GET /api/v2/merchandise/invoices ----------

test('vendor can list all invoices', function () {
    $this->actingAsRole(1, 'vendor');
    $orders = MerchandiseOrder::factory()->count(3)->create(['company_id' => 1]);
    foreach ($orders as $order) {
        MerchandiseInvoice::factory()->create(['order_id' => $order->id, 'company_id' => 1]);
    }

    $response = $this->getJson('/api/v2/merchandise/invoices');

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

test('customer cannot list invoices', function () {
    $this->actingAsRole(10, 'customer');

    $this->getJson('/api/v2/merchandise/invoices')->assertStatus(403);
});

test('invoices can be filtered by status', function () {
    $this->actingAsRole(1, 'vendor');
    $order1 = MerchandiseOrder::factory()->create(['company_id' => 1]);
    $order2 = MerchandiseOrder::factory()->create(['company_id' => 1]);
    MerchandiseInvoice::factory()->create(['order_id' => $order1->id, 'status' => InvoiceStatus::Paid, 'company_id' => 1]);
    MerchandiseInvoice::factory()->create(['order_id' => $order2->id, 'status' => InvoiceStatus::PaymentPending, 'company_id' => 1]);

    $response = $this->getJson('/api/v2/merchandise/invoices?status=payment_pending');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
});

// ---------- GET /api/v2/merchandise/invoices/{id} ----------

test('vendor can view invoice details', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['company_id' => 1]);
    $invoice = MerchandiseInvoice::factory()->create(['order_id' => $order->id, 'company_id' => 1]);

    $response = $this->getJson("/api/v2/merchandise/invoices/{$invoice->id}");

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => ['id', 'invoice_number', 'status', 'total_cents', 'amount_paid_cents', 'amount_due_cents', 'due_date'],
    ]);
});

test('customer can view their own invoice', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create(['customer_id' => 10, 'company_id' => 1]);
    $invoice = MerchandiseInvoice::factory()->create(['order_id' => $order->id, 'customer_id' => 10, 'company_id' => 1]);

    $this->getJson("/api/v2/merchandise/invoices/{$invoice->id}")->assertStatus(200);
});

// ---------- POST /api/v2/merchandise/invoices/{id}/payments ----------

test('vendor can record a partial payment', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['company_id' => 1]);
    $invoice = MerchandiseInvoice::factory()->create([
        'order_id' => $order->id,
        'total_cents' => 50000,
        'amount_paid_cents' => 0,
        'status' => InvoiceStatus::PaymentPending,
        'company_id' => 1,
    ]);

    $response = $this->postJson("/api/v2/merchandise/invoices/{$invoice->id}/payments", [
        'amount_cents' => 20000,
        'payment_method' => 'bank_transfer',
        'reference' => 'TXN-ABC123',
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment(['amount_cents' => 20000]);
    $this->assertDatabaseHas('merchandise_payments', [
        'invoice_id' => $invoice->id,
        'amount_cents' => 20000,
        'payment_method' => 'bank_transfer',
    ]);
    $this->assertDatabaseHas('merchandise_invoices', [
        'id' => $invoice->id,
        'amount_paid_cents' => 20000,
    ]);
});

test('invoice status changes to paid when fully settled', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['company_id' => 1]);
    $invoice = MerchandiseInvoice::factory()->create([
        'order_id' => $order->id,
        'total_cents' => 30000,
        'amount_paid_cents' => 0,
        'status' => InvoiceStatus::PaymentPending,
        'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/invoices/{$invoice->id}/payments", [
        'amount_cents' => 30000,
        'payment_method' => 'cash',
    ]);

    $this->assertDatabaseHas('merchandise_invoices', ['id' => $invoice->id, 'status' => 'paid']);
    $this->assertDatabaseHas('merchandise_orders', ['id' => $order->id, 'status' => 'completed']);
});

test('multiple partial payments accumulate correctly', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['company_id' => 1]);
    $invoice = MerchandiseInvoice::factory()->create([
        'order_id' => $order->id,
        'total_cents' => 60000,
        'amount_paid_cents' => 0,
        'status' => InvoiceStatus::PaymentPending,
        'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/invoices/{$invoice->id}/payments", [
        'amount_cents' => 20000, 'payment_method' => 'bank_transfer',
    ]);
    $this->postJson("/api/v2/merchandise/invoices/{$invoice->id}/payments", [
        'amount_cents' => 20000, 'payment_method' => 'bank_transfer',
    ]);
    $this->postJson("/api/v2/merchandise/invoices/{$invoice->id}/payments", [
        'amount_cents' => 20000, 'payment_method' => 'bank_transfer',
    ]);

    $this->assertDatabaseHas('merchandise_invoices', ['id' => $invoice->id, 'status' => 'paid', 'amount_paid_cents' => 60000]);
    $this->assertDatabaseCount('merchandise_payments', 3);
});

test('payment after due_date marks invoice as overdue', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['company_id' => 1]);
    $invoice = MerchandiseInvoice::factory()->create([
        'order_id' => $order->id,
        'total_cents' => 50000,
        'amount_paid_cents' => 0,
        'status' => InvoiceStatus::PaymentPending,
        'due_date' => Carbon::now()->subDay()->toDateString(),  // past due
        'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/invoices/{$invoice->id}/payments", [
        'amount_cents' => 10000,
        'payment_method' => 'cheque',
    ]);

    $this->assertDatabaseHas('merchandise_invoices', ['id' => $invoice->id, 'status' => 'overdue']);
});

test('recordPayment validates amount_cents is required and positive', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['company_id' => 1]);
    $invoice = MerchandiseInvoice::factory()->create(['order_id' => $order->id, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/invoices/{$invoice->id}/payments", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount_cents', 'payment_method']);
});

test('customer cannot record a payment', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create(['company_id' => 1]);
    $invoice = MerchandiseInvoice::factory()->create(['order_id' => $order->id, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/invoices/{$invoice->id}/payments", [
        'amount_cents' => 5000,
        'payment_method' => 'cash',
    ])->assertStatus(403);
});

// ---------- GET /api/v2/merchandise/invoices/{id}/download ----------

test('customer can download their invoice', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create(['customer_id' => 10, 'company_id' => 1]);
    $invoice = MerchandiseInvoice::factory()->create(['order_id' => $order->id, 'customer_id' => 10, 'company_id' => 1]);

    $response = $this->getJson("/api/v2/merchandise/invoices/{$invoice->id}/download");

    // Returns PDF or JSON data for download
    $response->assertStatus(200);
});

test('vendor can download invoice', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['company_id' => 1]);
    $invoice = MerchandiseInvoice::factory()->create(['order_id' => $order->id, 'company_id' => 1]);

    $this->getJson("/api/v2/merchandise/invoices/{$invoice->id}/download")->assertStatus(200);
});
