"use client";

// Reuse vendor order detail view for admin investigation purposes.
// Admin may not perform vendor-only actions; backend will 403 on such calls.
// This page is primarily for read-only investigation.

import VendorOrderDetail from "@/app/vendor/orders/[id]/page";

export default function AdminVendorOrderDetail() {
  return <VendorOrderDetail />;
}

