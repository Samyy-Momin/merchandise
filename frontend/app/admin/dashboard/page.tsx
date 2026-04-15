"use client";

import { useEffect, useMemo, useState } from "react";
import { apiFetch } from "@/lib/api";
import { PageState } from "@/components/ui/page-state";

type Paged<T> = { data: T[]; total?: number } | T[];
type Order = { id:number; status:string; created_at:string; updated_at?:string };
type Invoice = { id:number; order_id:number; status:string; created_at:string };

function extract(res: any): { rows: any[]; total?: number } {
  if (Array.isArray(res)) return { rows: res, total: undefined };
  const rows = Array.isArray(res?.data) ? res.data as any[] : [];
  const total = typeof res?.total === 'number' ? res.total : undefined;
  return { rows, total };
}

export default function AdminDashboard() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Orders by lifecycle slices
  const [ordersByKey, setOrdersByKey] = useState<Record<string, { rows: Order[]; total?: number }>>({});
  // Invoices
  const [invoiceRows, setInvoiceRows] = useState<Invoice[]>([]);

  useEffect(() => {
    let mounted = true;
    (async () => {
      try {
        setLoading(true); setError(null);
        const keys = ['ready','processing','dispatched','in_transit','delivered','completed'];
        const orderRes = await Promise.all(keys.map((k) => apiFetch(`/api/vendor/orders?filter=${k}`).catch(() => ({ data: [] }))));
        if (!mounted) return;
        const map: Record<string, { rows: Order[]; total?: number }> = {};
        orderRes.forEach((res, idx) => { const { rows, total } = extract(res); map[keys[idx]] = { rows: rows as Order[], total }; });
        setOrdersByKey(map);
        try {
          const invRes = await apiFetch('/api/invoices');
          if (!mounted) return;
          const { rows } = extract(invRes);
          setInvoiceRows(rows as Invoice[]);
        } catch {/* ignore */}
      } catch (e:any) {
        setError(e?.message || 'Failed to load dashboard');
      } finally {
        setLoading(false);
      }
    })();
    return () => { mounted = false; };
  }, []);

  const orderCounts = useMemo(() => {
    const out: Record<string, number> = {};
    for (const [k, v] of Object.entries(ordersByKey)) out[k] = v.total ?? v.rows.length;
    return out;
  }, [ordersByKey]);

  const invoiceCounts = useMemo(() => {
    const counts: Record<string, number> = {};
    invoiceRows.forEach((r) => { counts[r.status] = (counts[r.status] ?? 0) + 1; });
    return counts;
  }, [invoiceRows]);

  const deliveredRows = ordersByKey['delivered']?.rows ?? [];
  const stuckDelivered = useMemo(() => {
    const now = Date.now();
    return deliveredRows.filter((o) => {
      const t = new Date(o.updated_at || o.created_at).getTime();
      return (now - t) > 3*24*60*60*1000;
    });
  }, [deliveredRows]);

  if (loading) return <div className="p-6"><PageState variant="loading" title="Loading admin dashboard" description="Fetching summaries…" /></div>;
  if (error) return <div className="p-6"><PageState variant="error" title="Failed to load dashboard" description={error} /></div>;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Admin Dashboard</h1>
        <p className="text-sm text-muted-foreground">Combined reporting and operational monitoring</p>
      </div>

      {/* Order Status Summaries */}
      <section>
        <div className="text-sm font-medium mb-2">Order Status Summaries</div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {['ready','processing','dispatched','in_transit','delivered','completed'].map((k) => (
            <div key={k} className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
              <div className="text-muted-foreground capitalize">Orders — {k.replace('_',' ')}</div>
              <div className="mt-1 text-2xl font-semibold">{orderCounts[k] ?? 0}</div>
            </div>
          ))}
        </div>
      </section>

      {/* Invoice Status Summaries */}
      <section>
        <div className="text-sm font-medium mb-2">Invoice Status Summaries</div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {Object.keys(invoiceCounts).length === 0 ? (
            <div className="text-sm text-muted-foreground">No invoices visible or page-limited results.</div>
          ) : (
            Object.entries(invoiceCounts).map(([k, v]) => (
              <div key={k} className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
                <div className="text-muted-foreground capitalize">Invoices — {k.replace('_',' ')}</div>
                <div className="mt-1 text-2xl font-semibold">{v}</div>
              </div>
            ))
          )}
        </div>
        <div className="text-xs text-muted-foreground mt-2">Counts may be limited to the current page due to API pagination.</div>
      </section>

      {/* Operational Alerts */}
      <section className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
          <div className="font-medium mb-2">Potentially Stuck Deliveries (&gt; 3 days)</div>
          {stuckDelivered.length === 0 ? (
            <div className="text-muted-foreground">None detected.</div>
          ) : (
            <ul className="space-y-1">
              {stuckDelivered.slice(0, 20).map((o) => (
                <li key={o.id} className="flex items-center justify-between">
                  <div>Order #{o.id}</div>
                  <a className="underline" href={`/admin/vendor-orders/${o.id}`}>Investigate</a>
                </li>
              ))}
            </ul>
          )}
          <div className="text-xs text-muted-foreground mt-2">Heuristic based on timestamps; refine when richer metrics are available.</div>
        </div>
        <div className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
          <div className="font-medium mb-2">In Progress Overview</div>
          <ul className="space-y-1">
            <li>Ready for Processing: {orderCounts['ready'] ?? 0}</li>
            <li>Processing: {orderCounts['processing'] ?? 0}</li>
            <li>Dispatched: {orderCounts['dispatched'] ?? 0}</li>
            <li>In Transit: {orderCounts['in_transit'] ?? 0}</li>
          </ul>
        </div>
      </section>
    </div>
  );
}
