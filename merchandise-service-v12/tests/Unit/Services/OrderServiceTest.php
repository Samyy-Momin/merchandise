<?php

declare(strict_types=1);

use App\DTOs\OrderDTO;
use App\DTOs\OrderItemDTO;
use App\Enums\OrderStatus;
use App\Events\MerchandiseOrderPlaced;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\UnauthorizedOrderCancellationException;
use App\Models\MerchandiseOrder;
use App\Models\MerchandiseSku;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\SkuRepositoryInterface;
use App\Services\Order\OrderService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->orderRepo = Mockery::mock(OrderRepositoryInterface::class);
    $this->skuRepo = Mockery::mock(SkuRepositoryInterface::class);
    $this->service = new OrderService($this->orderRepo, $this->skuRepo);
    Event::fake();
});

afterEach(fn () => Mockery::close());

// ---------- placeOrder ----------

test('placeOrder creates order and snapshots prices from SKU', function () {
    $skuData = new MerchandiseSku(['unit_price_cents' => 50000, 'stock_quantity' => 20, 'sku_code' => 'SKU-001', 'name' => 'A4 Paper', 'is_active' => true]);
    $skuData->forceFill(['id' => 1]);
    $dto = new OrderDTO(
        customerId: 10,
        companyId: 1,
        items: [new OrderItemDTO(skuId: 1, requestedQuantity: 3)],
        notes: null,
    );

    $order = new MerchandiseOrder(['status' => OrderStatus::PendingApproval]);
    $order->forceFill(['id' => 1]);

    $this->skuRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($skuData);
    $this->orderRepo->shouldReceive('create')->once()->andReturn($order);

    $result = $this->service->placeOrder($dto);

    expect($result)->toBeInstanceOf(MerchandiseOrder::class);
    expect($result->status)->toBe(OrderStatus::PendingApproval);
});

test('placeOrder fires MerchandiseOrderPlaced event after commit', function () {
    $skuData = new MerchandiseSku(['unit_price_cents' => 20000, 'stock_quantity' => 10, 'sku_code' => 'SKU-002', 'name' => 'Pen', 'is_active' => true]);
    $skuData->forceFill(['id' => 1]);
    $dto = new OrderDTO(
        customerId: 5,
        companyId: 1,
        items: [new OrderItemDTO(skuId: 1, requestedQuantity: 2)],
        notes: null,
    );
    $order = new MerchandiseOrder([]);
    $order->forceFill(['id' => 1]);

    $this->skuRepo->shouldReceive('findOrFail')->andReturn($skuData);
    $this->orderRepo->shouldReceive('create')->andReturn($order);

    $this->service->placeOrder($dto);

    Event::assertDispatched(MerchandiseOrderPlaced::class);
});

test('placeOrder throws InsufficientStockException when stock is too low', function () {
    $skuData = new MerchandiseSku(['unit_price_cents' => 10000, 'stock_quantity' => 1, 'sku_code' => 'SKU-003', 'name' => 'Stapler', 'is_active' => true]);
    $skuData->forceFill(['id' => 1]);
    $dto = new OrderDTO(
        customerId: 5,
        companyId: 1,
        items: [new OrderItemDTO(skuId: 1, requestedQuantity: 5)],
        notes: null,
    );

    $this->skuRepo->shouldReceive('findOrFail')->andReturn($skuData);

    expect(fn () => $this->service->placeOrder($dto))->toThrow(InsufficientStockException::class);
});

test('placeOrder calculates line_total_cents as quantity x unit_price_cents', function () {
    // Price snapshot: 50000 cents x 3 qty = 150000 cents
    $skuData = new MerchandiseSku(['unit_price_cents' => 50000, 'stock_quantity' => 10, 'sku_code' => 'SKU-001', 'name' => 'Paper', 'is_active' => true]);
    $skuData->forceFill(['id' => 1]);
    $dto = new OrderDTO(
        customerId: 1,
        companyId: 1,
        items: [new OrderItemDTO(skuId: 1, requestedQuantity: 3)],
        notes: null,
    );

    $this->skuRepo->shouldReceive('findOrFail')->andReturn($skuData);
    $this->orderRepo->shouldReceive('create')
        ->once()
        ->withArgs(function ($orderData, $items) {
            return $items[0]['line_total_cents'] === 150000
                && $items[0]['unit_price_cents'] === 50000  // price snapshot
                && $orderData['total_cents'] === 150000;
        })
        ->andReturn(tap(new MerchandiseOrder([]), fn (MerchandiseOrder $order): MerchandiseOrder => $order->forceFill(['id' => 1])));

    $this->service->placeOrder($dto);
});

// ---------- cancelOrder ----------

test('cancelOrder succeeds for submitted status', function () {
    $order = new MerchandiseOrder(['id' => 1, 'customer_id' => 10, 'status' => OrderStatus::Submitted]);

    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);
    $this->orderRepo->shouldReceive('cancel')->once()->with($order);

    $this->service->cancelOrder(1, 10);
});

test('cancelOrder succeeds for pending_approval status', function () {
    $order = new MerchandiseOrder(['id' => 1, 'customer_id' => 10, 'status' => OrderStatus::PendingApproval]);

    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);
    $this->orderRepo->shouldReceive('cancel')->once()->with($order);

    $this->service->cancelOrder(1, 10);
});

test('cancelOrder throws InvalidOrderTransitionException for approved order', function () {
    $order = new MerchandiseOrder(['id' => 1, 'customer_id' => 10, 'status' => OrderStatus::Approved]);

    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);

    expect(fn () => $this->service->cancelOrder(1, 10))
        ->toThrow(InvalidOrderTransitionException::class);
});

test('cancelOrder throws exception if customer_id does not match', function () {
    $order = new MerchandiseOrder(['id' => 1, 'customer_id' => 10, 'status' => OrderStatus::Submitted]);

    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);

    expect(fn () => $this->service->cancelOrder(1, 99))
        ->toThrow(UnauthorizedOrderCancellationException::class);
});

// ---------- listOrders ----------

test('listOrders returns paginator', function () {
    $paginator = new LengthAwarePaginator([], 0, 15);
    $this->orderRepo->shouldReceive('list')->once()->with([])->andReturn($paginator);

    expect($this->service->listOrders([]))->toBeInstanceOf(LengthAwarePaginator::class);
});

// ---------- findOrFail ----------

test('findOrFail throws OrderNotFoundException when not found', function () {
    $this->orderRepo->shouldReceive('findOrFail')->once()->with(999)->andThrow(new OrderNotFoundException(999));

    expect(fn () => $this->service->findOrFail(999))->toThrow(OrderNotFoundException::class);
});
