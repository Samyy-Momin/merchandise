# Next.js 16 Merchandise Procurement Portal — Product Requirements Document

**Document Version:** 1.0  
**Date:** April 2, 2026  
**Target Platforms:** Web (primary: desktop, secondary: tablet and mobile responsive)  
**Framework:** Next.js 16.x (App Router, React 19, Turbopack)  
**Backend:** `merchandise-service-v12` via Kong API Gateway / BFF proxy routes  
**Source Backend Plan:** `/home/rabinder-sharma/.claude/plans/floating-questing-wadler.md`  
**Reference Frontend Patterns:** `docs/NEXTJS_CUSTOMER_APP_PRD.md`, `docs/deployment-guides/NEXTJS_FRONTEND_DEVELOPER_GUIDE.md`  
**Status:** Approved for Frontend UI Development

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Product Goals and Scope](#2-product-goals-and-scope)
3. [Users, Roles, and Permissions](#3-users-roles-and-permissions)
4. [Business Workflow and Lifecycle](#4-business-workflow-and-lifecycle)
5. [Frontend Architecture](#5-frontend-architecture)
6. [Information Architecture and Navigation](#6-information-architecture-and-navigation)
7. [Core Modules and Screens](#7-core-modules-and-screens)
8. [Design System and UX Direction](#8-design-system-and-ux-direction)
9. [State Management and Data Contracts](#9-state-management-and-data-contracts)
10. [Validation, Errors, and Empty States](#10-validation-errors-and-empty-states)
11. [Security, Tenant Context, and Auditability](#11-security-tenant-context-and-auditability)
12. [Performance, Accessibility, and Quality](#12-performance-accessibility-and-quality)
13. [Delivery Plan](#13-delivery-plan)
14. [Acceptance Criteria](#14-acceptance-criteria)

---

## 1. Executive Summary

OneFoodDialer requires a dedicated Next.js 16 web frontend for the planned `merchandise-service-v12`. This application supports bulk procurement of merchandise such as stationery, housekeeping items, and branded materials across a multi-party approval and fulfilment workflow.

The frontend is a single web application with role-aware workspaces for:

- `customer` for branch or buyer operations
- `admin` and `senior_manager` for approval operations
- `vendor` for fulfilment and invoicing
- `super_admin` for oversight and governance

This is not a customer commerce storefront. The product is an internal and partner-facing procurement portal optimized for desktop business users, dense data tables, multi-step approvals, and auditable operational actions.

The frontend must preserve backend workflow terminology and business rules exactly as defined in the backend plan. The UI may improve usability, but it must not reinterpret order states, invoice rules, or acknowledgement behavior.

---

## 2. Product Goals and Scope

### 2.1 Goals

- Enable buyers to submit merchandise requisitions with clear SKU, quantity, and price visibility.
- Enable approvers to fully approve, partially approve, or reject requests with line-level control.
- Enable vendors to process approved requests, dispatch goods, review delivery acknowledgements, and manage invoicing.
- Enable super admins to monitor system health, stuck workflow states, overdue payments, and tenant-level operations.
- Provide a coherent, auditable, role-based interface in one Next.js app.

### 2.2 Non-Goals

- No direct browser-to-service calls.
- No standalone mobile-first app in this scope.
- No redesign of backend business rules.
- No replacement of the wider OneFoodDialer auth or gateway model.
- No frontend-driven workflow shortcuts that bypass required lifecycle states.

### 2.3 Success Metrics

- Buyers can submit and track requisitions without support intervention.
- Approvers can complete partial approvals at line-item granularity.
- Vendors can complete dispatch and acknowledgement review workflows without manual off-system coordination.
- Invoice creation is blocked in the UI until acknowledgement approval conditions are met.
- Role leakage is prevented across all protected workspaces.

---

## 3. Users, Roles, and Permissions

### 3.1 Role Mapping

| BRD Role | OneFoodDialer Role | Primary Responsibility |
|---|---|---|
| Branch (buyer) | `customer` | Browse SKUs, submit orders, acknowledge delivery, view invoices |
| MHF (approver) | `admin`, `senior_manager` | Review orders, partially approve, reject, audit decisions |
| Vendor (supplier) | `vendor` | Process approved orders, dispatch items, review acknowledgements, create invoices |
| Platform admin | `super_admin` | Cross-tenant oversight, SKU governance, exception monitoring |

### 3.2 Permission Model

The UI must implement role-aware routing, navigation, and action visibility.

| Capability | Customer | Admin / Senior Manager | Vendor | Super Admin |
|---|---:|---:|---:|---:|
| View SKU catalogue | Yes | Yes | Yes | Yes |
| Create order | Yes | No | No | No |
| Cancel own order before approval finalization | Yes | No | No | No |
| View own order timeline | Yes | Limited to scope | Limited to scope | Yes |
| Approve / partially approve / reject order | No | Yes | No | Yes |
| Create dispatch | No | No | Yes | Yes |
| Submit acknowledgement | Yes | No | No | No |
| Approve / reject acknowledgement | No | No | Yes | Yes |
| Create invoice | No | No | Yes | Yes |
| Record payment | No | No | Yes | Yes |
| Download invoice | Yes | No | Yes | Yes |
| Cross-tenant monitoring | No | No | No | Yes |

### 3.3 UX Implications

- Navigation must only show role-relevant modules.
- Page actions must be hidden or disabled when permissions are missing.
- API authorization failures must degrade cleanly to permission-denied states, not broken screens.

---

## 4. Business Workflow and Lifecycle

### 4.1 Canonical Order Lifecycle

The frontend must use the exact backend lifecycle values:

```text
submitted
pending_approval
approved
partially_approved
rejected
processing
dispatched
acknowledged
invoice_generated
payment_pending
completed
overdue
cancelled
```

### 4.2 Critical Business Rules

- Partial approvals are allowed and occur at line-item level.
- Prices are locked at order time and must be displayed as snapshots on order detail views.
- Invoice generation is only allowed after vendor approval of the branch acknowledgement.
- Payment due date is 15 days from invoice creation logic defined by the backend workflow.
- A rejected acknowledgement returns the order to a dispatch-related follow-up path rather than silently completing the order.

### 4.3 UI Lifecycle Representation

Every order detail page must include:

- a status timeline
- current owner of action
- next valid action block
- immutable audit summary
- item table showing requested quantity, approved quantity, unit price snapshot, and line totals

### 4.4 Lifecycle Ownership

| Stage | Primary Actor | UI Focus |
|---|---|---|
| `submitted` / `pending_approval` | Buyer, Approver | Request visibility and approval queue |
| `approved` / `partially_approved` / `processing` | Approver, Vendor | Decision summary and fulfilment handoff |
| `dispatched` | Vendor, Buyer | Dispatch proof and receipt readiness |
| `acknowledged` | Buyer, Vendor | Acknowledgement review |
| `invoice_generated` / `payment_pending` | Vendor, Buyer | Invoice visibility and payment progress |
| `completed` / `overdue` | Vendor, Super Admin | Closure, exceptions, collections visibility |

---

## 5. Frontend Architecture

### 5.1 Technical Stack

- Next.js 16 App Router
- TypeScript
- Tailwind CSS
- `shadcn/ui`
- TanStack React Query
- React Hook Form + Zod
- Lucide icons

### 5.2 Architectural Principles

- Single application, role-based workspace model
- Backend-for-frontend proxy routes under `src/app/api/`
- Secure cookie-based auth and server-side token forwarding
- Tenant and role context resolved before rendering protected routes
- Server Components for shell rendering and initial data fetch
- Client Components for interactive tables, filters, forms, and mutations

### 5.3 Recommended App Structure

```text
src/
├── app/
│   ├── (public)/
│   │   ├── sign-in/
│   │   ├── access-denied/
│   │   └── tenant-select/
│   ├── (workspace)/
│   │   ├── buyer/
│   │   ├── approvals/
│   │   ├── vendor/
│   │   └── admin/
│   ├── api/
│   │   └── v2/
│   │       └── merchandise/
│   │           └── [...path]/route.ts
│   ├── layout.tsx
│   ├── loading.tsx
│   ├── error.tsx
│   └── not-found.tsx
├── components/
│   ├── ui/
│   ├── layouts/
│   ├── tables/
│   ├── forms/
│   ├── timelines/
│   └── states/
├── features/
│   ├── merchandise-catalogue/
│   ├── order-management/
│   ├── approvals/
│   ├── dispatch/
│   ├── acknowledgements/
│   ├── invoicing/
│   └── admin-oversight/
├── lib/
│   ├── api/
│   ├── auth/
│   ├── tenant/
│   └── permissions/
└── types/
    ├── merchandise.ts
    ├── api.ts
    ├── auth.ts
    └── enums.ts
```

### 5.4 BFF Pattern

The web app must not expose raw gateway URLs or service credentials in the browser. All backend interactions should flow through proxy route handlers that:

- forward auth context securely
- attach tenant headers where required
- normalize response envelopes
- map backend errors into frontend-friendly shapes
- enforce cache and revalidation rules per route type

---

## 6. Information Architecture and Navigation

### 6.1 Navigation Model

The app is desktop-first and should use:

- a persistent left sidebar for primary workspace navigation
- a top header with tenant switcher, search, notifications, and user menu
- contextual secondary tabs on detail-heavy pages
- sticky action bars on forms and long tables

### 6.2 Primary Route Groups

| Route Group | Audience | Purpose |
|---|---|---|
| `(public)` | All | Sign-in, tenant selection, access error states |
| `buyer/*` | `customer` | Catalogue, cart, orders, acknowledgements, invoices |
| `approvals/*` | `admin`, `senior_manager`, `super_admin` | Approval queue and decision workflows |
| `vendor/*` | `vendor`, `super_admin` | Processing, dispatch, acknowledgement review, invoicing |
| `admin/*` | `super_admin` | Cross-tenant reporting, exception monitoring, governance |

### 6.3 Suggested Route Inventory

#### Public

- `/sign-in`
- `/tenant-select`
- `/access-denied`

#### Buyer Workspace

- `/buyer/dashboard`
- `/buyer/skus`
- `/buyer/skus/[id]`
- `/buyer/cart`
- `/buyer/orders`
- `/buyer/orders/[id]`
- `/buyer/acknowledgements`
- `/buyer/invoices`
- `/buyer/invoices/[id]`

#### Approver Workspace

- `/approvals/dashboard`
- `/approvals/queue`
- `/approvals/orders/[id]`
- `/approvals/history`

#### Vendor Workspace

- `/vendor/dashboard`
- `/vendor/processing`
- `/vendor/orders/[id]`
- `/vendor/dispatches`
- `/vendor/acknowledgements`
- `/vendor/invoices`
- `/vendor/invoices/[id]`

#### Super Admin Workspace

- `/admin/dashboard`
- `/admin/tenants`
- `/admin/orders/exceptions`
- `/admin/invoices/overdue`
- `/admin/skus`
- `/admin/audit`

### 6.4 Global Navigation Rules

- Users with one role land directly in their workspace dashboard.
- Users with multiple roles land on the highest-priority workspace or a workspace chooser.
- Breadcrumbs are required for all detail pages.
- List pages must preserve filters and pagination in URL search params.

---

## 7. Core Modules and Screens

## 7.1 Buyer Module

### Goal

Allow branch users to create merchandise orders and track them through completion.

### Required Screens

- buyer dashboard
- SKU catalogue list
- SKU detail
- cart / draft order builder
- order submission review
- order history
- order detail timeline
- acknowledgement form
- invoice list and detail

### Key Behaviors

- Catalogue table supports search, category filtering, and stock visibility.
- Cart supports line-item quantity edits and notes before submission.
- Submission review must show price snapshots and estimated totals.
- Order detail must surface approval results, especially reduced quantities.
- Acknowledgement form is only available after dispatch exists.
- Invoice screens must show due date, paid amount, and outstanding amount.

## 7.2 Approval Module

### Goal

Allow approvers to process pending orders efficiently with high-confidence review tools.

### Required Screens

- approval dashboard
- approval queue
- order review detail
- decision history

### Key Behaviors

- Queue defaults to `pending_approval`.
- Decision screen shows requested vs approved quantities per line item.
- Partial approval editor must support reducing quantities but not increasing beyond requested quantity.
- Reject flow requires a reason.
- Decision confirmation must show downstream effect before submission.

## 7.3 Vendor Module

### Goal

Allow vendors to fulfil approved orders, manage dispatch, review acknowledgements, and generate invoices.

### Required Screens

- vendor dashboard
- processing queue
- order fulfilment detail
- dispatch creation form
- acknowledgement review queue
- invoice list
- invoice detail
- payment recording flow

### Key Behaviors

- Vendor queue prioritizes `approved`, `partially_approved`, and `processing`.
- Dispatch form captures courier, tracking number, dispatch timestamp, and estimated delivery.
- Acknowledgement review page supports approve and reject actions with audit output.
- Invoice creation CTA remains unavailable until acknowledgement approval state is satisfied.
- Payment recording updates status and visible balance summary.

## 7.4 Super Admin Module

### Goal

Provide cross-tenant operational oversight and governance.

### Required Screens

- operational dashboard
- tenant overview
- stuck order exceptions
- overdue invoice list
- SKU governance list
- audit activity view

### Key Behaviors

- Exceptions view groups rejected acknowledgements, overdue invoices, and stalled processing.
- Dashboard surfaces counts by status and tenant.
- Admin views may be read-heavy with selective mutation capability where backend allows it.

## 7.5 Shared Components

The following shared components must be defined early:

- workspace layout shell
- role-aware sidebar
- data table with filters, sorting, pagination, and bulk row context
- order status badge system
- order timeline component
- approval matrix table
- money display component for `*_cents` values
- audit metadata block
- printable invoice layout
- empty / error / access-denied states

---

## 8. Design System and UX Direction

### 8.1 Visual Direction

The UI should feel operational, trustworthy, and dense rather than consumer-oriented. It should favor fast scanning, strong information hierarchy, and explicit state communication.

### 8.2 Layout Principles

- Desktop-first with 1280px and 1440px usage as primary design widths
- Tables and split panes are first-class layouts
- Detail pages should keep the lifecycle and action panel visible near the top
- Use sticky headers or side summaries for long approval and invoice pages

### 8.3 Data Visualization

- Use status chips with strong semantic colors
- Use compact KPI cards for dashboard counts
- Use timelines for order progression
- Use badges or row highlighting for exception states like `overdue` or rejected acknowledgement

### 8.4 Form UX

- All destructive or irreversible actions require confirmation
- Required fields must be explicit
- Rejection and override actions require reason capture where backend expects it
- Disable action buttons during in-flight mutations and surface inline result feedback

### 8.5 Responsive Behavior

- Tablet must remain fully usable
- Mobile must support read and light action flows, but dense approval workflows may collapse into stacked cards
- Desktop remains the optimization target for primary operations

---

## 9. State Management and Data Contracts

### 9.1 Server State

React Query is the primary layer for:

- list fetching
- detail views
- status-based dashboards
- mutations with cache invalidation
- optimistic updates where failure recovery is clear

### 9.2 Client State

Use lightweight client state only for:

- sidebar state
- draft cart editing before submit
- local table preferences
- modal and confirmation state

### 9.3 Query and URL Rules

- Filters, search, sort, and pagination belong in URL search params
- Workspace context should remain stable across refreshes and deep links
- Detail pages must be directly linkable

### 9.4 Contract Assumptions

The PRD assumes the merchandise backend will expose versioned routes under `/v2/merchandise` similar to the backend plan, including:

- `GET /skus`
- `GET /skus/{id}`
- `POST /orders`
- `GET /orders`
- `GET /orders/{id}`
- `POST /orders/{id}/approve`
- `POST /orders/{id}/reject`
- `POST /orders/{id}/dispatch`
- `POST /orders/{id}/acknowledge`
- `POST /acknowledgements/{id}/approve`
- `POST /acknowledgements/{id}/reject`
- `GET /invoices`
- `GET /invoices/{id}`
- `POST /orders/{id}/invoice`
- `POST /invoices/{id}/payments`
- `GET /invoices/{id}/download`

If final backend path names differ, the BFF layer may adapt path mapping. UI workflow semantics must remain unchanged.

### 9.5 Frontend Domain Types

The frontend should define stable types for:

- `MerchandiseSku`
- `MerchandiseOrder`
- `MerchandiseOrderItem`
- `MerchandiseDispatch`
- `MerchandiseAcknowledgement`
- `MerchandiseInvoice`
- `MerchandisePayment`
- `OrderStatus`
- `AcknowledgementStatus`
- `InvoiceStatus`

These types must preserve backend enum values exactly.

---

## 10. Validation, Errors, and Empty States

### 10.1 Validation Rules

- Quantity fields must reject zero or negative values.
- Approved quantity cannot exceed requested quantity.
- Rejection requires a reason where the backend requires one.
- Payment amount must be positive and must not exceed backend constraints.

### 10.2 Error Handling

Each page must support:

- inline field validation errors
- section-level mutation errors
- full-page fatal fetch states
- permission-denied state
- tenant-context-missing state
- stale-state conflict state when the order changed before the user submitted

### 10.3 Empty States

Design explicit empty states for:

- no SKUs available
- no pending approvals
- no dispatches awaiting review
- no overdue invoices
- no orders found after filters

Empty states should include the next useful action or filter reset.

---

## 11. Security, Tenant Context, and Auditability

### 11.1 Authentication

- Follow OneFoodDialer’s secure web auth model with protected BFF routes.
- Do not expose raw access tokens to the browser if avoidable.
- Support role-aware route protection before page render.

### 11.2 Tenant Context

- Every protected request must include tenant context expected by the backend.
- Tenant switching must be explicit and visible in the UI.
- Cross-tenant leakage is a critical failure.

### 11.3 Auditability

High-impact screens must surface:

- actor name or identifier
- action timestamp
- action result
- notes or rejection reason where present

The UI should make audit history easy to inspect during approvals, acknowledgement disputes, and payment tracking.

---

## 12. Performance, Accessibility, and Quality

### 12.1 Performance

- Prioritize fast first render for list dashboards and order detail pages.
- Use streaming and loading states where beneficial.
- Paginate large operational tables.
- Avoid over-fetching on dashboard pages by separating KPI and table queries.

### 12.2 Accessibility

- All actions must be keyboard accessible.
- Tables need meaningful column headers and row focus management.
- Status indicators must not rely on color alone.
- Dialogs and confirmations must trap focus correctly.

### 12.3 Quality Standards

- Shared UI primitives should be reused across workspaces.
- BDD-style frontend acceptance coverage is required for critical workflows.
- Visual regressions should be tracked for role dashboards, order detail pages, and invoice print layouts.

---

## 13. Delivery Plan

### Phase 1: Foundation

- app shell
- auth and tenant resolution
- role-aware routing
- shared table, badge, timeline, and money components
- BFF merchandise proxy layer

### Phase 2: Buyer Experience

- SKU catalogue
- cart and order submission
- order history and detail
- acknowledgement submission
- invoice viewing

### Phase 3: Approvals Experience

- approval queue
- order review
- partial approval UX
- rejection flow
- decision history

### Phase 4: Vendor Experience

- processing queue
- dispatch flow
- acknowledgement review
- invoice creation
- payment recording

### Phase 5: Super Admin Experience

- tenant monitoring
- exceptions dashboard
- overdue invoice views
- SKU governance
- audit visibility

### Phase 6: Hardening

- accessibility improvements
- loading and error polish
- responsive refinements
- print layout verification
- end-to-end acceptance coverage

---

## 14. Acceptance Criteria

### 14.1 Buyer

- A buyer can browse SKUs, add multiple items, submit an order, and see a persisted order timeline.
- A buyer can view exact approved quantities after partial approval.
- A buyer cannot acknowledge delivery before a dispatch exists.
- A buyer can download their invoice when available.

### 14.2 Approver

- An approver can review a pending order and approve, partially approve, or reject it.
- Partial approval supports line-level quantity changes and clearly displays the impact.
- Rejecting an order requires a reason and is visible in order history.

### 14.3 Vendor

- A vendor can view approved work, create a dispatch, and review buyer acknowledgement.
- A vendor cannot generate an invoice until acknowledgement approval conditions are satisfied.
- A vendor can record partial and full payments and see invoice state updates.

### 14.4 Super Admin

- A super admin can monitor exception states including overdue invoices and acknowledgement failures.
- A super admin can inspect cross-tenant operational status without navigating into each tenant manually.

### 14.5 Security and Integrity

- Users cannot access routes or actions outside their role permissions.
- Tenant context remains correct across navigation, refresh, and deep links.
- Backend enum values are reflected consistently in filters, badges, timelines, and action rules.
- The UI never offers invalid lifecycle transitions.

---

## Appendix A: Workflow-to-Screen Mapping

| Workflow Step | Primary Screen |
|---|---|
| Browse merchandise | Buyer SKU catalogue |
| Submit requisition | Buyer cart and review |
| Review pending orders | Approval queue |
| Partial approval | Approval order detail |
| Dispatch goods | Vendor dispatch form |
| Acknowledge receipt | Buyer order detail |
| Review acknowledgement | Vendor acknowledgement queue |
| Generate invoice | Vendor invoice action panel |
| Record payment | Vendor invoice detail |
| Monitor overdue | Super admin exceptions dashboard |

## Appendix B: Implementation Defaults

- One Next.js 16 app, not multiple separate portals
- Desktop-first business UI
- Shared BFF route family for merchandise APIs
- URL-driven filters and pagination
- Shared design primitives for tables, statuses, and timelines
- No deviation from backend lifecycle naming without an explicit backend contract change
