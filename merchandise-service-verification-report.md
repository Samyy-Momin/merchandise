# Merchandise Service v12 — Verification Report

**Date:** 2026-04-03
**Service:** `services/merchandise-service-v12`
**Branch:** `DevAtif`
**Result:** All phases complete — 211/211 tests passing (520 assertions, 0 failures)

---

## Phase-by-Phase Verification

### Phase 1 — Infrastructure & Models

| Deliverable | PRD Spec | Status |
|---|---|---|
| Migrations | 7 required | 7 created (skus, orders, order_items, dispatches, acknowledgements, invoices, payments) |
| `OrderStatus` enum | 13 cases + `canTransitionTo()` | All 13 cases present, guard implemented |
| `AcknowledgementStatus` enum | pending, approved, rejected | Present |
| `InvoiceStatus` enum | draft, sent, payment_pending, paid, overdue | Present |
| Models | 7 required | 7 (Sku, Order, OrderItem, Dispatch, Acknowledgement, Invoice, Payment) |
| `BelongsToCompany` | All 7 models | Confirmed on all 7 |
| Custom timestamps | All 7 models | `created_date`/`updated_date` on all 7 |
| Factories | 7 required | 7 created |
| Unit tests | 3 enum + 4 model files | 7 test files, all passing |

### Phase 2 — SKU Catalogue

| Deliverable | Status |
|---|---|
| `SkuRepositoryInterface` + `SkuRepository` | Present — `list()`, `create()`, `update()`, `deactivate()`, `hasActiveOrders()`, `findOrFail()` |
| `SkuServiceInterface` + `SkuService` | Present |
| `SkuController` (index, show, store, update, destroy) | Present |
| `CreateSkuRequest` / `UpdateSkuRequest` | Present — per-tenant unique `sku_code` validation |
| RBAC: write ops require `role:super_admin,vendor` | Confirmed in routes |
| Tests | 8 unit + 19 feature — all passing |

### Phase 3 — Order Placement

| Deliverable | Status |
|---|---|
| Price snapshot at order time (`sku_code`, `sku_name`, `unit_price_cents`) | Confirmed in `OrderService::placeOrder()` |
| Stock decrement on place / restore on cancel | Implemented in `OrderRepository` |
| `InsufficientStockException` | Present |
| `MORD-YYYYMMDD-NNNN` order ref format | Generated in `OrderService` |
| `PlaceOrderRequest` | Present |
| `OrderController` (index, show, store, cancel) | Present |
| Tests | 10 unit + 16 feature — all passing |

### Phase 4 — Approval Workflow (Partial Approval)

| Deliverable | Status |
|---|---|
| Full approval → status `approved` | Implemented |
| Partial approval (any item reduced) → status `partially_approved` | Implemented |
| Per-item `approved_quantity` set; `line_total_cents` recalculated | Implemented in `ApprovalService` |
| `ApproveOrderRequest` with `items: [{item_id, approved_quantity}]` payload | Present |
| Reject with reason stored in `rejected_reason` | Implemented |
| `InvalidOrderTransitionException` guard on non-`pending_approval` orders | Present |
| `OrderApprovalController` | Present |
| Tests | 5 unit + 10 feature — all passing |

### Phase 5 — Dispatch

| Deliverable | Status |
|---|---|
| `DispatchRepositoryInterface` + `DispatchRepository` | Present |
| `DispatchServiceInterface` + `DispatchService` | Present |
| Guard: only `approved`/`partially_approved`/`processing` → `dispatched` | Implemented |
| `DispatchController` (dispatch, index) | Present |
| `MerchandiseOrderDispatched` event fired after commit | Confirmed |
| Tests | 3 unit + 9 feature — all passing |

### Phase 6 — Acknowledgement Workflow

| Deliverable | Status |
|---|---|
| `AcknowledgementRepositoryInterface` + `AcknowledgementRepository` | Present — `create()`, `findOrFail()`, `approve()`, `reject()` |
| `AcknowledgementServiceInterface` + `AcknowledgementService` | Present |
| `acknowledge()` — creates record with status `pending`, order → `acknowledged` | Implemented |
| `approveAcknowledgement()` — triggers `InvoiceService::createInvoice()` after commit | Confirmed |
| `rejectAcknowledgement()` — reverts order to `dispatched` for re-submission | Implemented |
| `AcknowledgementController` (acknowledge, approve, reject) | Present |
| Tests | 6 unit + 13 feature — all passing |

### Phase 7 — Invoice & Payment Tracking

| Deliverable | Status |
|---|---|
| `InvoiceRepositoryInterface` + `InvoiceRepository` | Present |
| Invoice creation gated on acknowledgement approval | Enforced — only reachable via `AcknowledgementService::approveAcknowledgement()` |
| `due_date = now() + 15 days` (BRD rule) | Confirmed — `Carbon::now()->addDays(15)` |
| `MINV-YYYYMM-NNNN` invoice number format | Generated in `InvoiceService` |
| Partial payment accumulation | `amount_paid_cents` incremented on each payment |
| Full payment → invoice `paid`, order → `completed` | Implemented |
| Late payment → invoice `overdue`, order → `overdue` | Implemented (checks `due_date` in `recordPayment`) |
| `InvoiceController` (create, show, index, recordPayment, download) | Present |
| Tests | 7 unit + 21 feature — all passing |

### Wire-up — Routes, Providers, Service Client

| Deliverable | Status |
|---|---|
| `routes/api.php` | 23 routes, all under `keycloak.jwt` + `tenant` middleware |
| RBAC middleware | 17 `role:` guards across routes |
| `AppServiceProvider` DI bindings | 11 bindings (5 repo + 6 service interfaces), `AcknowledgementService` via closure |
| `EventServiceProvider` | Present |
| `MerchandiseServiceClient` | `packages/service-clients/src/Merchandise/MerchandiseServiceClient.php` |
| `MerchandiseException` | `packages/service-clients/src/Merchandise/Exceptions/MerchandiseException.php` |
| `ServiceClientsServiceProvider` registration | Singleton added, import confirmed |
| `package.json` for Turborepo discovery | Present, matches pattern of other PHP services |

---

## Architectural Invariants Verified

| Invariant | Verified |
|---|---|
| All prices in cents (`*_cents` integer columns) | All monetary columns |
| Custom timestamps on all models | All 7 models — `created_date` / `updated_date` |
| `BelongsToCompany` trait on all models | All 7 models |
| `$txStarted` guard in all write services | 5 write services confirmed |
| Events fired after `DB::commit()` | Confirmed in all services |
| Interface + implementation for every service and repository | 5 repo + 6 service pairs, 11 DI bindings |
| `DatabaseMigrations` trait in Feature tests (not `RefreshDatabase`) | Confirmed in `tests/Pest.php` |

---

## Test Results

```
Tests\Unit\Enums\AcknowledgementStatusTest      PASS
Tests\Unit\Enums\InvoiceStatusTest              PASS
Tests\Unit\Enums\OrderStatusTest                PASS
Tests\Unit\Models\MerchandiseAcknowledgementTest PASS
Tests\Unit\Models\MerchandiseInvoiceTest         PASS
Tests\Unit\Models\MerchandiseOrderTest           PASS
Tests\Unit\Models\MerchandiseSkuTest             PASS
Tests\Unit\Services\AcknowledgementServiceTest  PASS
Tests\Unit\Services\ApprovalServiceTest         PASS
Tests\Unit\Services\DispatchServiceTest         PASS
Tests\Unit\Services\InvoiceServiceTest          PASS
Tests\Unit\Services\OrderServiceTest            PASS
Tests\Unit\Services\SkuServiceTest              PASS

Tests\Feature\Api\V2\AcknowledgementControllerTest  PASS (13 tests)
Tests\Feature\Api\V2\DispatchControllerTest         PASS  (9 tests)
Tests\Feature\Api\V2\HealthControllerTest           PASS  (1 test)
Tests\Feature\Api\V2\InvoiceControllerTest          PASS (21 tests)
Tests\Feature\Api\V2\OrderApprovalControllerTest    PASS (10 tests)
Tests\Feature\Api\V2\OrderControllerTest            PASS (16 tests)
Tests\Feature\Api\V2\SkuControllerTest              PASS (19 tests)

Unit:     123 passed  (304 assertions)
Feature:   88 passed  (216 assertions)
─────────────────────────────────────
Total:    211 passed  (520 assertions) — 0 failures
```

---

## End-to-End Happy Path (BRD Workflow)

```
1. POST /api/v2/merchandise/skus
   → vendor creates SKU (e.g. branded pens, 200 units @ 500 cents each)

2. POST /api/v2/merchandise/orders
   → branch places order for 10 units, price snapshotted at 500 cents
   → order_ref: MORD-20260403-7284, status: pending_approval

3. POST /api/v2/merchandise/orders/{id}/approve
   body: { items: [{item_id: 1, approved_quantity: 8}] }
   → approved_quantity reduced from 10 → 8 (partial approval)
   → line_total_cents = 8 × 500 = 4000, status: partially_approved

4. POST /api/v2/merchandise/orders/{id}/dispatch
   → vendor records courier + tracking, status: dispatched

5. POST /api/v2/merchandise/orders/{id}/acknowledge
   → branch confirms receipt with optional notes, status: acknowledged
   → acknowledgement created with status: pending

6. POST /api/v2/merchandise/acknowledgements/{id}/approve
   → vendor confirms acknowledgement
   → invoice auto-generated: MINV-202604-5531, due_date = today + 15 days
   → status: invoice_generated → payment_pending

7. POST /api/v2/merchandise/invoices/{id}/payments
   body: { amount_cents: 2000, payment_method: "bank_transfer" }
   → partial payment recorded, amount_paid_cents = 2000

8. POST /api/v2/merchandise/invoices/{id}/payments
   body: { amount_cents: 2000, payment_method: "bank_transfer" }
   → full payment settled, invoice status: paid, order status: completed

9. GET /api/v2/merchandise/invoices/{id}
   → { status: "paid", amount_paid_cents: 4000, total_cents: 4000 }
```

---

## Related Documents

- [Merchandise Service Implementation Plan](2026-04-03;Plan:%20Merchandise_Service_v12.md)
- [NextJS Merchandise Portal PRD](NEXTJS_MERCHANDISE_PORTAL_PRD.md) — frontend counterpart
- [NextJS Merchandise Portal Backlog](NEXTJS_MERCHANDISE_PORTAL_IMPLEMENTATION_BACKLOG.md)
- [Microservices Architecture](microservices_architecture.md)
