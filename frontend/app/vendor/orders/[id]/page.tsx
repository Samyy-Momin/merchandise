"use client";

import { useEffect, useRef, useState } from "react";
import { SimpleCalendar } from "@/components/ui/simple-calendar";
import { apiFetch } from "@/lib/api";
import { openInvoicePdf, downloadInvoiceExcel } from "@/lib/invoice";
import type { Order, OrderItem } from "@/types";
import { useParams } from "next/navigation";

export default function VendorOrderDetail() {
  const params = useParams<{ id: string }>();
  const id = Number(params?.id);
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [invoiceExists, setInvoiceExists] = useState<boolean>(false);
  const [tracking, setTracking] = useState({ tracking_number: '', courier_name: '', estimated_delivery_date: '' });
  const nativeDateRef = useRef<HTMLInputElement | null>(null);
  const [showCal, setShowCal] = useState(false);
  // Per-item delivered quantities for the Mark Delivered action
  const [deliveredMap, setDeliveredMap] = useState<Record<number, number>>({});
  // Acknowledgement (shown post-delivery only if meaningful)
  const [ack, setAck] = useState<{ employee_code?: string; receiver_name?: string; rating?: number; remarks?: string } | null>(null);

  const toIsoFromDDMMYYYY = (v: string): string | null => {
    const m = /^([0-3]?\d)\/(0?\d|1[0-2])\/(\d{4})$/.exec(v.trim());
    if (!m) return null;
    const dd = parseInt(m[1], 10);
    const mm = parseInt(m[2], 10);
    const yyyy = parseInt(m[3], 10);
    if (mm < 1 || mm > 12) return null;
    if (dd < 1 || dd > 31) return null;
    const iso = `${yyyy}-${String(mm).padStart(2,'0')}-${String(dd).padStart(2,'0')}`;
    return iso;
  };

  const toDDMMYYYY = (iso: string): string => {
    // iso expected as YYYY-MM-DD
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso);
    if (!m) return iso;
    const [_, y, mo, d] = m;
    return `${d}/${mo}/${y}`;
  };

  const load = () => {
    setLoading(true);
    setError(null);
    apiFetch(`/api/orders/${id}`)
      .then((res) => setOrder(res as Order))
      .catch((e:any) => setError(e?.message || 'Failed to load'))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, [id]);
  // Only check invoice existence when order is delivered/completed
  useEffect(() => {
    if (!order) return;
    if (order.status === 'delivered' || order.status === 'completed') {
      (async () => { try { await apiFetch(`/api/invoices/by-order/${id}`); setInvoiceExists(true); } catch { setInvoiceExists(false); } })();
    } else {
      setInvoiceExists(false);
    }
  }, [id, order?.status]);

  // Initialize deliveredMap whenever order changes
  useEffect(() => {
    if (!order) { setDeliveredMap({}); return; }
    const next: Record<number, number> = {};
    (order.items || []).forEach((it: any) => { next[it.id] = Number(it.qty_approved ?? 0); });
    setDeliveredMap(next);
  }, [order?.id]);

  // Load acknowledgement summary after delivery/completion only
  useEffect(() => {
    if (!order) { setAck(null); return; }
    if (order.status === 'delivered' || order.status === 'completed') {
      apiFetch(`/api/orders/${id}/full-details`).then((d: any) => setAck(d?.acknowledgement || null)).catch(() => setAck(null));
    } else {
      setAck(null);
    }
  }, [id, order?.status]);

  const startProcessing = async () => {
    try { await apiFetch(`/api/vendor/orders/${id}/process`, { method: 'POST' }); load(); } catch (e:any) { setError(e?.message || 'Failed'); }
  };
  const doDispatch = async () => {
    try {
      if (!tracking.tracking_number || !tracking.courier_name) throw new Error('Tracking number and courier are required');
      let etaIso: string | null = null;
      if (tracking.estimated_delivery_date) {
        etaIso = toIsoFromDDMMYYYY(tracking.estimated_delivery_date);
        if (!etaIso) throw new Error('Estimated delivery date must be dd/mm/yyyy');
      }
      await apiFetch(`/api/vendor/orders/${id}/dispatch`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({
        tracking_number: tracking.tracking_number,
        courier_name: tracking.courier_name,
        estimated_delivery_date: etaIso || undefined,
      }) });
      load();
    } catch (e:any) { setError(e?.message || 'Failed to dispatch'); }
  };
  const markTransit = async () => { try { await apiFetch(`/api/vendor/orders/${id}/transit`, { method: 'POST' }); load(); } catch (e:any) { setError(e?.message || 'Failed'); } };
  const markDelivered = async () => {
    try {
      const itemsPayload = items.map((it) => ({ order_item_id: it.id, delivered_qty: Math.max(0, Math.min(Number(it.qty_approved ?? 0), Number(deliveredMap[it.id] ?? 0))) }));
      await apiFetch(`/api/vendor/orders/${id}/deliver`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ items: itemsPayload }) });
      load();
    } catch (e:any) { setError(e?.message || 'Failed'); }
  };

  if (loading) return <div className="p-6 text-sm">Loading…</div>;
  if (error) return <div className="p-6 text-sm text-red-600">{error}</div>;
  if (!order) return <div className="p-6 text-sm">Not found</div>;

  const items = (order.items || []) as OrderItem[];
  const s = (order as any).shipment;
  const shippedFor = (it: OrderItem) => {
    const approved = Number(it.qty_approved ?? 0);
    const st = order.status;
    if (st === 'dispatched' || st === 'in_transit' || st === 'delivered' || st === 'completed') return approved;
    return 0;
  };
  const remainingFor = (it: OrderItem) => {
    const approved = Number(it.qty_approved ?? 0);
    return Math.max(0, approved - shippedFor(it));
  };


  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="space-y-1">
        <h1 className="text-2xl font-semibold tracking-tight">Order #{order.id}</h1>
        <div className="text-sm">Status: {order.status}</div>
      </div>

      {/* Delivery Address */}
      <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
        <div className="mb-2 font-medium">Delivery Address</div>
        {order.address ? (
          <div className="space-y-1">
            <div className="font-medium">{order.address.name || '—'}</div>
            <div>{order.address.address_line}</div>
            <div>{order.address.city}, {order.address.state} {order.address.pincode}</div>
            <div className="text-muted-foreground">Phone: {order.address.phone || '—'}</div>
          </div>
        ) : (
          <div className="text-muted-foreground">No address available.</div>
        )}
      </section>

      {/* Approved Items */}
      <section className="rounded-[12px] border bg-white p-4 shadow-card">
        <div className="font-medium text-sm mb-2">Approved Items</div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b">
                <th className="text-left p-2">Product</th>
                <th className="text-right p-2">Approved Qty</th>
                <th className="text-right p-2">Shipped Qty</th>
                <th className="text-right p-2">Remaining Qty</th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr><td className="p-2 text-sm text-muted-foreground" colSpan={4}>No items.</td></tr>
              ) : items.map((it) => (
                <tr key={it.id} className="border-b">
                  <td className="p-2">{it.product?.name}</td>
                  <td className="p-2 text-right">{Number(it.qty_approved ?? 0)}</td>
                  <td className="p-2 text-right">{shippedFor(it)}</td>
                  <td className="p-2 text-right">{remainingFor(it)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      {/* Shipment (state-based) */}
      {order.status === 'processing' && (
        <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
          <div className="mb-2 font-medium">Shipment Setup</div>
          <div className="space-y-2">
            <input className="border px-2 py-1 w-full md:w-1/2" placeholder="Tracking number" value={tracking.tracking_number} onChange={(e) => setTracking({ ...tracking, tracking_number: e.target.value })} />
            <input className="border px-2 py-1 w-full md:w-1/2" placeholder="Courier name" value={tracking.courier_name} onChange={(e) => setTracking({ ...tracking, courier_name: e.target.value })} />
            <div className="flex items-start gap-2">
              <input
                className="border px-2 py-1 w-full md:w-1/2"
                placeholder="Estimated delivery (dd/mm/yyyy)"
                inputMode="numeric"
                value={tracking.estimated_delivery_date}
                onKeyDown={(e) => {
                  const allow = ["Backspace","Delete","ArrowLeft","ArrowRight","Tab","/"]; if (allow.includes(e.key)) return; if (!/^[0-9]$/.test(e.key)) e.preventDefault();
                }}
                onChange={(e) => {
                  let v = e.target.value.replace(/[^0-9/]/g, '');
                  v = v.replace(/^(\d{2})(\d)/, '$1/$2').replace(/^(\d{2}\/\d{2})(\d)/, '$1/$2');
                  setTracking({ ...tracking, estimated_delivery_date: v.slice(0, 10) });
                }}
              />
              <button type="button" className="border px-3 py-1" onClick={() => setShowCal((s) => !s)}>Pick Date</button>
              {showCal && (
                <div className="relative z-10">
                  <SimpleCalendar
                    selected={(() => { const iso = tracking.estimated_delivery_date && toIsoFromDDMMYYYY(tracking.estimated_delivery_date); return iso ? new Date(iso) : null; })()}
                    onSelect={(d) => {
                      const iso = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                      const toDD = (is: string) => { const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(is); if(!m) return is; const [_, y, mo, dd] = m; return `${dd}/${mo}/${y}`; };
                      setTracking({ ...tracking, estimated_delivery_date: toDD(iso) });
                      setShowCal(false);
                    }}
                  />
                </div>
              )}
            </div>
          </div>
        </section>
      )}

      {['dispatched','in_transit','delivered','completed'].includes(order.status) && (
        <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
          <div className="mb-2 font-medium">Shipment Summary</div>
          {!s ? (
            <div className="text-muted-foreground">No shipment details available.</div>
          ) : (
            <div className="space-y-1">
              <div>Courier: {s.courier_name || '—'}</div>
              <div>Tracking: {s.tracking_number || '—'}</div>
              <div>Status: {s.status}</div>
              <div>ETA: {s.estimated_delivery_date || '—'}</div>
            </div>
          )}
        </section>
      )}

      {/* Next Action */}
      {(() => {
        if (order.status === 'approved' || order.status === 'partially_approved') {
          return (
            <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
              <div className="mb-2 font-medium">Next Action</div>
              <button className="border px-3 py-1" onClick={startProcessing}>Start Processing</button>
            </section>
          );
        }
        if (order.status === 'processing') {
          return (
            <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
              <div className="mb-2 font-medium">Next Action</div>
              <button className="border px-3 py-1" onClick={doDispatch}>Dispatch</button>
            </section>
          );
        }
        if (order.status === 'dispatched') {
          return (
            <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
              <div className="mb-2 font-medium">Next Action</div>
              <button className="border px-3 py-1" onClick={markTransit}>Mark In Transit</button>
            </section>
          );
        }
        if (order.status === 'in_transit') {
          return (
            <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm space-y-2">
              <div className="mb-1 font-medium">Delivered Quantities</div>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left p-2">Product</th>
                      <th className="text-right p-2">Approved</th>
                      <th className="text-right p-2">Delivered</th>
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((it) => (
                      <tr key={it.id} className="border-b">
                        <td className="p-2">{it.product?.name}</td>
                        <td className="p-2 text-right">{Number(it.qty_approved ?? 0)}</td>
                        <td className="p-2 text-right">
                          <input
                            type="number"
                            min={0}
                            max={Number(it.qty_approved ?? 0)}
                            value={deliveredMap[it.id] ?? 0}
                            inputMode="numeric"
                            className="border px-2 py-1 w-24 text-right"
                            onChange={(e) => setDeliveredMap((prev) => ({ ...prev, [it.id]: Math.min(Number(it.qty_approved ?? 0), Math.max(0, parseInt((e.target.value||'0').replace(/[^0-9]/g,'')))) }))}
                          />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <div className="pt-1"><button className="border px-3 py-1" onClick={markDelivered}>Mark Delivered</button></div>
            </section>
          );
        }
        return null;
      })()}

      {/* Invoice (post-delivery) */}
      {invoiceExists && (
        <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
          <div className="mb-2 font-medium">Invoice</div>
          <div className="space-x-2">
            <button className="underline" onClick={() => openInvoicePdf(order.id)}>Download Invoice (PDF)</button>
            <button className="underline" onClick={() => downloadInvoiceExcel(order.id)}>Download Invoice (Excel)</button>
          </div>
        </section>
      )}

      {/* Acknowledgement Summary (post-delivery only if meaningful) */}
      {(['delivered','completed'].includes(order.status)) && (() => {
        const hasMeaning = !!(ack && (ack.employee_code || ack.receiver_name || (typeof ack.rating === 'number') || (ack.remarks && ack.remarks.trim())));
        if (hasMeaning) {
          return (
            <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
              <div className="font-medium mb-1">Acknowledgement Summary</div>
              <div>Employee Code: {ack?.employee_code || '—'}</div>
              <div>Receiver: {ack?.receiver_name || '—'}</div>
              <div>Rating: {typeof ack?.rating === 'number' ? ack?.rating : '—'}</div>
              <div>Remarks: {ack?.remarks || '—'}</div>
            </section>
          );
        }
        return (
          <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm text-muted-foreground">
            Awaiting buyer acknowledgement
          </section>
        );
      })()}
    </div>
  );
}
