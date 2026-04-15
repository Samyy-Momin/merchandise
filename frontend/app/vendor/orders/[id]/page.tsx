"use client";

import { useEffect, useRef, useState } from "react";
import { apiFetch } from "@/lib/api";
import { openInvoicePdf, downloadInvoiceExcel } from "@/lib/invoice";
import type { Order, OrderItem } from "@/types";
import { useParams } from "next/navigation";
import { useAuthStore } from "@/lib/stores/auth-store";

export default function VendorOrderDetail() {
  const params = useParams<{ id: string }>();
  const id = Number(params?.id);
  const hydrated = useAuthStore((s) => s.hydrated);

  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [invoiceExists, setInvoiceExists] = useState<boolean>(false);

  const [deliveredMap, setDeliveredMap] = useState<Record<number, number>>({});
  const [ack, setAck] = useState<{
    employee_code?: string;
    receiver_name?: string;
    rating?: number;
    remarks?: string;
  } | null>(null);

  const [confirmOpen, setConfirmOpen] = useState(false);
  const [deliveryReason, setDeliveryReason] = useState("");

  const [tracking, setTracking] = useState({ tracking_number: "", courier_name: "", estimated_delivery_date: "" });
  const nativeDateRef = useRef<HTMLInputElement | null>(null);

  const load = () => {
    setLoading(true);
    setError(null);
    apiFetch(`/api/orders/${id}`)
      .then((res) => setOrder(res as Order))
      .catch((e: any) => setError(e?.message || "Failed to load"))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    if (!hydrated) return;
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id, hydrated]);

  useEffect(() => {
    if (!order) return;
    if (order.status === "delivered" || order.status === "completed") {
      (async () => {
        try { await apiFetch(`/api/invoices/by-order/${id}`); setInvoiceExists(true); }
        catch { setInvoiceExists(false); }
      })();
    } else setInvoiceExists(false);
  }, [id, order?.status]);

  useEffect(() => {
    if (!order) { setDeliveredMap({}); return; }
    const next: Record<number, number> = {};
    (order.items || []).forEach((it: any) => { next[it.id] = Number(it.qty_approved ?? 0); });
    setDeliveredMap(next);
  }, [order?.id]);

  useEffect(() => {
    if (!order) { setAck(null); return; }
    if (order.status === "delivered" || order.status === "completed") {
      apiFetch(`/api/orders/${id}/full-details`).then((d: any) => setAck(d?.acknowledgement || null)).catch(() => setAck(null));
    } else setAck(null);
  }, [id, order?.status]);

  const startProcessing = async () => {
    try { await apiFetch(`/api/vendor/orders/${id}/process`, { method: "POST" }); load(); }
    catch (e:any) { setError(e?.message || "Failed"); }
  };
  const doDispatch = async () => {
    try {
      if (!tracking.tracking_number || !tracking.courier_name) throw new Error("Tracking number and courier are required");
      await apiFetch(`/api/vendor/orders/${id}/dispatch`, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({
        tracking_number: tracking.tracking_number,
        courier_name: tracking.courier_name,
        estimated_delivery_date: tracking.estimated_delivery_date || undefined,
      })});
      load();
    } catch (e:any) { setError(e?.message || "Failed to dispatch"); }
  };
  const markTransit = async () => { try { await apiFetch(`/api/vendor/orders/${id}/transit`, { method: "POST" }); load(); } catch (e:any) { setError(e?.message || "Failed"); } };

  const startDeliver = () => setConfirmOpen(true);
  const confirmDeliver = async () => {
    try {
      const itemsPayload = items.map((it) => ({ order_item_id: it.id, delivered_qty: Math.max(0, Math.min(Number(it.qty_approved ?? 0), Number(deliveredMap[it.id] ?? 0))) }));
      await apiFetch(`/api/vendor/orders/${id}/deliver`, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ items: itemsPayload }) });
      setConfirmOpen(false); setDeliveryReason(""); load();
    } catch (e:any) { setError(e?.message || "Failed"); }
  };

  if (loading || !hydrated) return <div className="p-6 text-sm">Loading…</div>;
  if (error) return <div className="p-6 text-sm text-red-600">{error}</div>;
  if (!order) return <div className="p-6 text-sm">Not found</div>;

  const items = (order.items || []) as OrderItem[];
  const shippedFor = (it: OrderItem) => {
    const approved = Number(it.qty_approved ?? 0);
    if (["dispatched", "in_transit", "delivered", "completed"].includes(order.status)) return approved;
    return 0;
  };
  const remainingFor = (it: OrderItem) => Math.max(0, Number(it.qty_approved ?? 0) - shippedFor(it));
  const differences = items.map((it) => {
    const approved = Number(it.qty_approved ?? 0);
    const delivered = Math.max(0, Math.min(approved, Number(deliveredMap[it.id] ?? 0)));
    return { id: it.id, name: it.product?.name || `Item ${it.id}`, approved, delivered, short: delivered < approved };
  });
  const anyShort = differences.some((d) => d.short);

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-2xl font-semibold tracking-tight">Order #{order.id}</h1>
        <div className="text-sm">Status: {order.status}</div>
      </div>

      <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
        <div className="mb-2 font-medium">Delivery Address</div>
        {order.address ? (
          <div className="space-y-1">
            <div className="font-medium">{order.address.name || "—"}</div>
            <div>{order.address.address_line}</div>
            <div>{order.address.city}, {order.address.state} {order.address.pincode}</div>
            <div className="text-muted-foreground">Phone: {order.address.phone || "—"}</div>
          </div>
        ) : (
          <div className="text-muted-foreground">No address available.</div>
        )}
      </section>

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

      {(() => {
        if (order.status === "approved" || order.status === "partially_approved") {
          return (
            <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
              <div className="mb-2 font-medium">Next Action</div>
              <button className="border px-3 py-1" onClick={startProcessing}>Start Processing</button>
            </section>
          );
        }
        if (order.status === "processing") {
          return (
            <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
              <div className="mb-2 font-medium">Next Action</div>
              <button className="border px-3 py-1" onClick={doDispatch}>Dispatch</button>
            </section>
          );
        }
        if (order.status === "dispatched") {
          return (
            <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
              <div className="mb-2 font-medium">Next Action</div>
              <button className="border px-3 py-1" onClick={markTransit}>Mark In Transit</button>
            </section>
          );
        }
        if (order.status === "in_transit") {
          return (
            <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm space-y-2">
              <div className="mb-1 font-medium">Delivered Quantities</div>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left p-2">Product</th>
                      <th className="text-right p-2">Approved</th>
                      <th className="text-right p-2">To Deliver</th>
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
                            onChange={(e) => setDeliveredMap((prev) => ({ ...prev, [it.id]: Math.max(0, Math.min(Number(it.qty_approved ?? 0), parseInt((e.target.value||'0').replace(/[^0-9]/g,'')))) }))}
                          />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <div className="pt-1"><button className="border px-3 py-1" onClick={startDeliver}>Mark Delivered</button></div>
            </section>
          );
        }
        return null;
      })()}

      {confirmOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
          <div className="bg-white rounded-md shadow-md w-full max-w-lg p-4 text-sm">
            <div className="font-medium mb-2">Confirm Delivery</div>
            <div className="mb-2 text-xs text-muted-foreground">Review approved vs. delivered quantities</div>
            <div className="overflow-x-auto max-h-60 border rounded">
              <table className="w-full text-sm">
                <thead><tr className="border-b"><th className="text-left p-2">Product</th><th className="text-right p-2">Approved</th><th className="text-right p-2">To Deliver</th></tr></thead>
                <tbody>
                  {items.map((it) => {
                    const approved = Number(it.qty_approved ?? 0);
                    const delivered = Math.max(0, Math.min(approved, Number(deliveredMap[it.id] ?? 0)));
                    const short = delivered < approved;
                    return (
                      <tr key={it.id} className="border-b">
                        <td className="p-2">{it.product?.name || `Item ${it.id}`}</td>
                        <td className="p-2 text-right">{approved}</td>
                        <td className={"p-2 text-right " + (short ? 'text-amber-700' : '')}>{delivered}{short ? ' (Short)' : ''}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
            {items.some((it) => Math.max(0, Math.min(Number(it.qty_approved ?? 0), Number(deliveredMap[it.id] ?? 0))) < Number(it.qty_approved ?? 0)) && (
              <div className="mt-3">
                <div className="text-xs text-muted-foreground mb-1">Comment (required since quantity is less than approved)</div>
                <textarea className="w-full border rounded p-2" rows={3} value={deliveryReason} onChange={(e) => setDeliveryReason(e.target.value)} />
              </div>
            )}
            <div className="mt-3 flex justify-end gap-2">
              <button className="border px-3 py-1" onClick={() => { setConfirmOpen(false); setDeliveryReason(""); }}>Cancel</button>
              <button className="border px-3 py-1 bg-black text-white disabled:opacity-50" disabled={items.some((it) => Math.max(0, Math.min(Number(it.qty_approved ?? 0), Number(deliveredMap[it.id] ?? 0))) < Number(it.qty_approved ?? 0)) && deliveryReason.trim().length <= 3} onClick={confirmDeliver}>Confirm</button>
            </div>
          </div>
        </div>
      )}

      {invoiceExists && (
        <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
          <div className="mb-2 font-medium">Invoice</div>
          <div className="space-x-2">
            <button className="underline" onClick={() => openInvoicePdf(order.id)}>Download Invoice (PDF)</button>
            <button className="underline" onClick={() => downloadInvoiceExcel(order.id)}>Download Invoice (Excel)</button>
            <a className="underline" href={`/vendor/invoices/${order.id}`}>View Invoice Details</a>
          </div>
        </section>
      )}

      {["delivered", "completed"].includes(order.status) && (() => {
        const hasMeaning = !!(ack && (ack.employee_code || ack.receiver_name || (typeof ack.rating === "number") || (ack.remarks && ack.remarks.trim())));
        if (hasMeaning) {
          return (
            <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
              <div className="font-medium mb-1">Acknowledgement Summary</div>
              <div>Employee Code: {ack?.employee_code || "—"}</div>
              <div>Receiver: {ack?.receiver_name || "—"}</div>
              <div>Rating: {typeof ack?.rating === "number" ? ack?.rating : "—"}</div>
              <div>Remarks: {ack?.remarks || "—"}</div>
              <div className="text-xs text-muted-foreground mt-2">Vendor acknowledgement review is backend-driven in this build; once approved, the invoice appears automatically.</div>
            </section>
          );
        }
        return <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm text-muted-foreground">Awaiting buyer acknowledgement</section>;
      })()}
    </div>
  );
}

