"use client";

import { useEffect, useState } from "react";
import { apiFetch } from "@/lib/api";
import type { Order } from "@/types";

export default function ApproverDashboard() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;
    apiFetch("/api/orders")
      .then((res) => {
        const list = Array.isArray(res?.data) ? (res.data as Order[]) : (res as Order[]);
        // eslint-disable-next-line no-console
        console.log("[Approver] /api/orders:", list);
        if (mounted) setOrders(list);
      })
      .catch((e: any) => {
        // eslint-disable-next-line no-console
        console.error("[Approver] load orders failed:", e);
        if (mounted) setError(e?.message || "Failed to load orders");
      })
      .finally(() => mounted && setLoading(false));
    return () => { mounted = false; };
  }, []);

  const pendingCount = orders.filter((o) => o.status === "pending_approval").length;
  const [stats, setStats] = useState<{approved:number; rejected:number; partial:number}>({approved:0,rejected:0,partial:0});

  useEffect(() => {
    apiFetch('/api/approvals/stats')
      .then((res) => {
        // eslint-disable-next-line no-console
        console.log('[Approver] approvals stats:', res);
        setStats(res as any);
      })
      .catch((e) => {
        // eslint-disable-next-line no-console
        console.error('[Approver] approvals stats failed:', e);
      });
  }, []);

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold tracking-tight">Approvals Dashboard</h1>
      {error && <div className="text-sm text-red-600">{error}</div>}
      <div className="space-y-2 text-sm">
        <div>Pending approvals: {loading ? "…" : pendingCount}</div>
        <div>Approved by me: {stats.approved} | Rejected by me: {stats.rejected} | Partial: {stats.partial} &nbsp; <a className="underline" href="/approver/decisions">View</a></div>
      </div>
    </div>
  );
}
