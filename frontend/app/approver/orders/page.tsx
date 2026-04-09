"use client";

import { useEffect, useState } from "react";
import { apiFetch } from "@/lib/api";
import type { Order } from "@/types";
import { PageState } from "@/components/ui/page-state";

export default function ApproverOrders() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<string>("");

  useEffect(() => {
    let mounted = true;
    const q = filter ? `?filter=${filter}` : "";
    apiFetch(`/api/orders${q}`)
      .then((res: unknown) => {
        const anyRes = res as any;
        const list = Array.isArray(anyRes?.data) ? (anyRes.data as Order[]) : (res as Order[]);
        if (mounted) setOrders(list);
      })
      .catch((e: unknown) => {
        // eslint-disable-next-line no-console
        console.error("[Approver] list failed:", e);
        const msg = e instanceof Error ? e.message : String(e);
        if (mounted) setError(msg || "Failed to load orders");
      })
      .finally(() => mounted && setLoading(false));
    return () => { mounted = false; };
  }, [filter]);

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold tracking-tight">Orders Pending Approval</h1>
      {error && <div />}
      <div className="text-sm space-x-2">
        {[
          {k:"", t:"All"},
          {k:"pending", t:"Pending"},
          {k:"approved", t:"Approved"},
          {k:"in_progress", t:"In Progress"},
          {k:"delivered", t:"Delivered"},
          {k:"completed", t:"Completed"},
          {k:"issues", t:"Issues"},
        ].map((b) => (
          <button key={b.k} className={`border px-3 py-1 ${filter===b.k?'font-semibold':''}`} onClick={() => setFilter(b.k)}>{b.t}</button>
        ))}
      </div>
      {loading ? (
        <PageState variant="loading" title="Loading orders" description="Fetching approval queue…" />
      ) : error ? (
        <PageState variant={/\b403\b/.test(error)? 'forbidden':'error'} title={/\b403\b/.test(error)? 'Access denied' : 'Failed to load orders'} description={error} />
      ) : orders.length === 0 ? (
        <PageState variant="empty" title="No pending orders" description="You have no orders to review right now." />
      ) : (
        <ul className="text-sm space-y-2">
          {orders.map((o) => (
            <li key={o.id} className="border p-3 flex items-center justify-between">
              <div>Order #{o.id} — {o.status}</div>
              <div className="space-x-3">
                <a className="underline" href={`/approver/orders/${o.id}`}>Review</a>
                <a className="underline" href={`/approver/orders/${o.id}`}>View Details</a>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
