"use client";

import { useEffect, useState } from "react";
import { apiFetch } from "@/lib/api";
import type { Order } from "@/types";

export default function VendorOrders() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<string>("");

  useEffect(() => {
    let mounted = true;
    const q = filter ? `?filter=${filter}` : "";
    apiFetch(`/api/vendor/orders${q}`)
      .then((res) => {
        const list = Array.isArray(res?.data) ? (res.data as Order[]) : (res as Order[]);
        if (mounted) setOrders(list);
      })
      .catch((e:any) => setError(e?.message || 'Failed to load'))
      .finally(() => setLoading(false));
    return () => { mounted = false; };
  }, [filter]);

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold tracking-tight">Vendor Orders</h1>
      {error && <div className="text-sm text-red-600">{error}</div>}
      <div className="text-sm space-x-2">
        {[
          {k:"", t:"All"},
          {k:"ready", t:"Ready"},
          {k:"processing", t:"Processing"},
          {k:"dispatched", t:"Dispatched"},
          {k:"in_transit", t:"In Transit"},
          {k:"delivered", t:"Delivered"},
          {k:"completed", t:"Completed"},
        ].map((b) => (
          <button key={b.k} className={`border px-3 py-1 ${filter===b.k?'font-semibold':''}`} onClick={() => setFilter(b.k)}>{b.t}</button>
        ))}
      </div>
      {loading ? <div className="text-sm">Loading…</div> : orders.length === 0 ? (
        <div className="text-sm text-muted-foreground">No orders ready to process.</div>
      ) : (
        <ul className="space-y-2 text-sm">
          {orders.map((o) => (
            <li key={o.id} className="border p-3 flex items-center justify-between">
              <div>Order #{o.id} — {o.status}</div>
              <a className="underline" href={`/vendor/orders/${o.id}`}>View Details</a>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
