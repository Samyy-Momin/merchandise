# Next.js Merchandise Portal Implementation Backlog (PDF-Aligned)

**Source of Truth:** `docs/Merchanise_Client_UI_Reference/` PDFs  
**Scaffold Entry Point:** `frontend-shadcn/src/app/(microfrontend-v2)/merchandise-service-v12/`

## Overview

This backlog is revised to match the approved PDF flows exactly for the current implementation cycle. Engineering should implement Requester and Approver experiences first, while preserving route scaffolding for Vendor and Super Admin until approved designs exist.

## Epic 1: Requester Ordering Foundation

- Build Requester home/dashboard aligned to PDF navigation intent.
- Implement product catalogue experience with category segmentation (Stationery, Housekeeping).
- Support product detail reveal and quantity increments from the catalogue.
- Ensure cart entry points and CTA wording align with PDF language.

## Epic 2: Requester Cart and Checkout

- Implement cart review with quantity updates and item removal.
- Capture approver selection and billing/shipping selection before checkout.
- Submit order and show post-placement confirmation state.
- Trigger confirmation state handling consistent with PDF process expectations.

## Epic 3: Approver Pending Approvals

- Build Pending Approvals queue surface with order drill-in.
- Implement line-level quantity modification prior to decision.
- Implement approve flow supporting selected-line approval and implicit unselected-line rejection behavior.
- Implement full-order reject flow with reason capture.
- Provide post-decision confirmation view for approved orders.

## Epic 4: Requester E-Acknowledgement

- Build requester order section entry to acknowledgement actions.
- Implement Add Acknowledgement flow with per-line received/not-received states.
- Require discrepancy comments for not-received paths.
- Persist acknowledgement submission and reflect updated order status.

## Epic 5: Invoice Visibility and Download

- Implement invoice list for Requester and Approver workspaces using `All Invoice` UI label.
- Implement invoice detail preview metadata needed for download actions.
- Implement PDF invoice download action.
- Implement Excel invoice download action.

## Epic 6: Experience Hardening

- Add loading, empty, and error states for all implemented Requester and Approver routes.
- Improve accessibility semantics and keyboard behavior on table/form interactions.
- Verify responsive behavior for common desktop/laptop widths represented by the design references.
- Add acceptance-level BDD scenarios for requester and approver critical journeys.

## Deferred (Design-Blocked)

- Keep `/vendor/*` routes as placeholders only; do not implement operational flows without approved vendor designs.
- Keep `/admin/*` routes as placeholders only; do not implement governance dashboards without approved admin designs.
- Mark all vendor/admin backlog items with `design-blocked` tag until new UI references are approved.

## API and Data Priorities

- Prioritize endpoints required for products, cart/order placement, approvals, acknowledgements, invoices, and invoice downloads.
- Implement partial-approval payload contract with line-level quantities and selections.
- Defer vendor dispatch/payment and super-admin monitoring endpoints in this cycle.

## Definition of Done for This Cycle

- Requester can complete browse -> cart -> checkout with PDF-aligned UI behavior.
- Approver can process pending approvals including partial line-level decisions.
- Requester can submit acknowledgement with discrepancy path.
- Requester and Approver can view and download both PDF and Excel invoices.
- Vendor/Admin placeholders are present but non-operational and explicitly marked as design-pending.
