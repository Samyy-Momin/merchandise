"use client";

import { useEffect, useMemo, useState } from "react";
import { apiFetch } from "@/lib/api";
import type { Order, OrderItem } from "@/types";
import { useParams, useRouter } from "next/navigation";
import { openInvoicePdf, downloadInvoiceExcel } from "@/lib/invoice";
import { PageState } from "@/components/ui/page-state";

type Row = { item_id: number; qty_requested: number; approved_so_far: number; qty_more: number; selected: boolean };
type AckSummary = { employee_code?: string; receiver_name?: string; rating?: number; remarks?: string } | null;

export default function ApproverOrderDetail() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [rows, setRows] = useState<Row[]>([]);
  const [comments, setComments] = useState<string>("");
  const [invoiceExists, setInvoiceExists] = useState<boolean>(false);
  const [ack, setAck] = useState<AckSummary>(null);
  const [showReject, setShowReject] = useState(false);
  const [rejectReason, setRejectReason] = useState("");
  const [rejectSubmitting, setRejectSubmitting] = useState(false);
  const [rejectError, setRejectError] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);
  const id = Number(params?.id);

  const load = () => {
    setLoading(true);
    setError(null);
    apiFetch(`/api/orders/${id}`)
      .then((res) => {
        const ord = res as Order;
        setOrder(ord);
        const r = (ord.items || []).map((it: OrderItem) => ({ item_id: it.id, qty_requested: it.qty_requested, approved_so_far: it.qty_approved ?? 0, qty_more: 0, selected: true }));
        setRows(r);
    apiFetch(`/api/invoices/by-order/${id}`).then(() => setInvoiceExists(true)).catch(() => setInvoiceExists(false));
        if (ord.status === 'delivered' || ord.status === 'completed') {
          apiFetch(`/api/orders/${id}/full-details`).then((d: unknown) => {
            const anyD = d as any;
            setAck((anyD && anyD.acknowledgement) ? (anyD.acknowledgement as AckSummary) : null);
          }).catch(() => setAck(null));
        } else setAck(null);
      })
      .catch((e: unknown) => setError(e instanceof Error ? e.message : String(e)))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  const setQty = (itemId: number, qty: number) => {
    setRows((prev) => prev.map((r) => {
      if (r.item_id !== itemId) return r;
      const remaining = Math.max(0, r.qty_requested - r.approved_so_far);
      const clamped = Math.min(remaining, Math.max(0, Math.floor(qty || 0)));
      return { ...r, qty_more: clamped };
    }));
  };

  const setSelected = (itemId: number, selected: boolean) => setRows((prev) => prev.map((r) => r.item_id === itemId ? { ...r, selected, qty_more: selected ? r.qty_more : 0 } : r));

  const approveAll = () => setRows((prev) => prev.map((r) => ({ ...r, selected: true, qty_more: Math.max(0, r.qty_requested - r.approved_so_far) })));
  const rejectAll = () => setRows((prev) => prev.map((r) => ({ ...r, selected: false, qty_more: 0 })));

  const onSubmit = async () => {
    try {
      const payload = { items: rows.map((r) => ({ item_id: r.item_id, qty_approved: r.qty_more })), comments };
      // eslint-disable-next-line no-console
      console.log("[Approver] submit:", payload);
      const res = await apiFetch(`/api/orders/${id}/approve`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      // eslint-disable-next-line no-console
      console.log("[Approver] approve result:", res);
      router.replace("/approver/orders");
    } catch (e: any) {
      // eslint-disable-next-line no-console
      console.error("[Approver] approve failed:", e);
      setError(e?.message || "Failed to approve order");
    }
  };

  const partial = useMemo(() => {
    const anyMore = rows.some((r) => r.qty_more > 0);
    const anyNotFull = rows.some((r) => r.qty_more < Math.max(0, r.qty_requested - r.approved_so_far));
    return anyMore && anyNotFull;
  }, [rows]);

  if (loading) return <div className="p-6"><PageState variant="loading" title="Loading order" description="Fetching order details…" /></div>;
  if (error) {
    const forbidden = /\b403\b/.test(error);
    return <div className="p-6"><PageState variant={forbidden? 'forbidden':'error'} title={forbidden? 'Access denied' : 'Failed to load order'} description={error} /></div>;
  }
  if (!order) return <div className="p-6"><PageState variant="empty" title="Order not found" /></div>;

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="space-y-1">
        <h1 className="text-2xl font-semibold tracking-tight">Order #{order.id}</h1>
        <div className="text-sm">Status: {order.status}</div>
        {/* Audit strip */}
        <div className="text-xs text-muted-foreground flex flex-wrap items-center gap-3">
          {(() => {
            const s = (order as any).status as string;
            let owner = '—'; let next = '—';
            if (s === 'submitted' || s === 'pending_approval') { owner = 'Approver'; next = 'Awaiting approval'; }
            else if (s === 'approved' || s === 'partially_approved' || s === 'processing') { owner = 'Vendor'; next = 'Awaiting dispatch'; }
            else if (s === 'dispatched') { owner = 'Buyer'; next = 'Awaiting acknowledgement'; }
            else if (s === 'acknowledged') { owner = 'Vendor'; next = 'Review acknowledgement'; }
            else if (s === 'invoice_generated' || s === 'payment_pending') { owner = 'Vendor'; next = 'Payment processing'; }
            else if (s === 'completed' || s === 'cancelled') { owner = '—'; next = 'Closed'; }
            else if (s === 'rejected') { owner = 'Buyer'; next = 'Adjust or re-submit'; }
            else if (s === 'overdue') { owner = 'Vendor'; next = 'Collections'; }
            const updated = (order as any).updated_at || (order as any).created_at;
            return (
              <>
                <span>Current owner: <span className="font-medium text-foreground">{owner}</span></span>
                <span>Next action: <span className="font-medium text-foreground">{next}</span></span>
                {updated ? <span>Last updated: {new Date(updated).toISOString()}</span> : null}
              </>
            );
          })()}
        </div>
        {successMsg && <div className="text-xs text-green-700">{successMsg}</div>}
        {invoiceExists && (
          <div className="space-x-2 text-xs text-muted-foreground">
            <button className="underline" onClick={() => openInvoicePdf(order.id)}>Invoice (PDF)</button>
            <button className="underline" onClick={() => downloadInvoiceExcel(order.id)}>Invoice (Excel)</button>
          </div>
        )}
      </div>

      {/* Buyer Information */}
      <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
        <div className="mb-2 font-medium">Buyer Information</div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-2">
          <div><span className="text-muted-foreground">Name:</span> {order.address?.name || '—'}</div>
          <div><span className="text-muted-foreground">Email:</span> {'—'}</div>
          <div><span className="text-muted-foreground">Phone:</span> {order.address?.phone || '—'}</div>
        </div>
      </section>

      {/* Buyer Address */}
      <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
        <div className="mb-2 font-medium">Delivery Address</div>
        {order.address ? (
          <div className="space-y-1">
            <div>{order.address.address_line}</div>
            <div>{order.address.city}, {order.address.state} {order.address.pincode}</div>
          </div>
        ) : (
          <div className="text-muted-foreground">No address on file.</div>
        )}
      </section>

      {/* Single Approval Table */}
      <section className="rounded-[12px] border bg-white p-4 shadow-card">
        <div className="font-medium text-sm mb-2">Approve Items</div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b">
                <th className="p-2 w-8 text-left">Select</th>
                <th className="text-left p-2">Product</th>
                <th className="text-right p-2">Requested</th>
                <th className="text-right p-2">Approved so far</th>
                <th className="text-right p-2">Remaining</th>
                <th className="text-right p-2">Approve more</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => {
                const item = order.items?.find((i) => i.id === r.item_id);
                const name = item?.product?.name;
                const remaining = Math.max(0, r.qty_requested - r.approved_so_far);
                const disabled = !r.selected || remaining === 0;
                return (
                  <tr key={r.item_id} className={`border-b ${!r.selected ? 'opacity-60' : ''}`}>
                    <td className="p-2 align-middle">
                      <input
                        type="checkbox"
                        checked={r.selected}
                        onChange={(e) => setSelected(r.item_id, e.target.checked)}
                      />
                    </td>
                    <td className="p-2">{name}</td>
                    <td className="p-2 text-right">{r.qty_requested}</td>
                    <td className="p-2 text-right">{r.approved_so_far}</td>
                    <td className="p-2 text-right">{remaining}</td>
                    <td className="p-2 text-right">
                      <input
                        className={`border px-2 py-1 w-20 text-right ${disabled ? 'bg-gray-50 text-gray-400' : ''}`}
                        type="number"
                        min={0}
                        max={remaining}
                        inputMode="numeric"
                        pattern="[0-9]*"
                        placeholder="qty"
                        disabled={disabled}
                        value={r.qty_more === 0 ? '' : r.qty_more}
                        onKeyDown={(e) => { const allow=["Backspace","Delete","ArrowLeft","ArrowRight","Tab"]; if(allow.includes(e.key)) return; if(!/^[0-9]$/.test(e.key)) e.preventDefault(); }}
                        onChange={(e) => setQty(r.item_id, parseInt((e.target.value || '').replace(/[^0-9]/g, '')))}
                        onBlur={(e) => setQty(r.item_id, parseInt((e.target.value || '0').replace(/[^0-9]/g, '')))}
                      />
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {/* Action area */}
        <div className="mt-3 space-x-2 text-sm">
          <button className="border px-3 py-1" onClick={approveAll}>Approve All</button>
          <button className="border px-3 py-1" onClick={rejectAll}>Reject All</button>
          <input className="border px-2 py-1 w-full md:w-1/2" placeholder="Comments (optional)" value={comments} onChange={(e) => setComments(e.target.value)} />
          <button className="border px-4 py-2" onClick={onSubmit}>{partial ? 'Submit Partial' : 'Submit'}</button>
          <button className="border px-3 py-1 text-red-700" onClick={() => { setRejectReason(''); setRejectError(null); setShowReject(true); }}>Reject Order</button>
        </div>
      </section>

      {/* Acknowledgement Summary (only after delivery) */}
      {ack && (order.status === 'delivered' || order.status === 'completed') && (
        <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
          <div className="font-medium mb-1">Acknowledgement Summary</div>
          <div>Employee Code: {ack?.employee_code || '—'}</div>
          <div>Receiver: {ack?.receiver_name || '—'}</div>
          <div>Rating: {ack?.rating ?? '—'}</div>
          <div>Remarks: {ack?.remarks || '—'}</div>
        </section>
      )}

      {/* Reject Modal */}
      {showReject && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
          <div className="bg-white rounded-md shadow-md w-full max-w-md p-4 text-sm">
            <div className="font-medium mb-2">Reject order</div>
            <div className="space-y-2">
              <label className="block text-xs text-muted-foreground">Reason (required)</label>
              <textarea
                className="w-full border rounded p-2"
                rows={4}
                value={rejectReason}
                onChange={(e) => setRejectReason(e.target.value)}
              />
              {rejectError && <div className="text-xs text-red-600">{rejectError}</div>}
            </div>
            <div className="mt-3 flex justify-end gap-2">
              <button className="border px-3 py-1" onClick={() => { setShowReject(false); setRejectError(null); }}>Cancel</button>
              <button
                className="border px-3 py-1 text-white bg-red-600 disabled:opacity-60"
                disabled={rejectSubmitting || !rejectReason.trim()}
                onClick={async () => {
                  try {
                    setRejectSubmitting(true);
                    setRejectError(null);
                    await apiFetch(`/api/orders/${id}/reject`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ reason: rejectReason.trim() }) });
                    setShowReject(false);
                    setSuccessMsg('Order rejected successfully');
                    load();
                  } catch (e: unknown) {
                    setRejectError(e instanceof Error ? e.message : String(e));
                  } finally {
                    setRejectSubmitting(false);
                  }
                }}
              >{rejectSubmitting ? 'Rejecting…' : 'Confirm reject'}</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
