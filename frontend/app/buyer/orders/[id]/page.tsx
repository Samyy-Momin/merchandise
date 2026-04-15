"use client";

import { useEffect, useState } from "react";
import { apiFetch } from "@/lib/api";
import type { Order, OrderItem } from "@/types";
import { useParams } from "next/navigation";
import { OrderDetails } from "@/components/order-details";
import { openInvoicePdf, downloadInvoiceExcel } from "@/lib/invoice";
import { PageState } from "@/components/ui/page-state";
import { useAuthStore } from "@/lib/stores/auth-store";

export default function BuyerOrderDetail() {
  const params = useParams<{ id: string }>();
  const id = Number(params?.id);
  const [order, setOrder] = useState<Order | null>(null);
  const [shipment, setShipment] = useState<any>(null);
  const [issues, setIssues] = useState<any[]>([]);
  const [invoiceExists, setInvoiceExists] = useState<boolean>(false);
  const [fullDetails, setFullDetails] = useState<any | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [ackSubmitting, setAckSubmitting] = useState(false);
  const [issueText, setIssueText] = useState("");
  const [issueSubmitting, setIssueSubmitting] = useState(false);
  const [loading, setLoading] = useState(true);

  const hydrated = useAuthStore((s) => s.hydrated);

  useEffect(() => {
    if (!hydrated) return; // wait for auth/token hydration to avoid 401/loops
    let mounted = true;
    (async () => {
      try {
        setLoading(true); setError(null);
        const [o, s, is, details] = await Promise.all([
          apiFetch(`/api/orders/${id}`),
          apiFetch(`/api/orders/${id}/tracking`).catch(() => null),
          apiFetch(`/api/orders/${id}/issues`).catch(() => []),
          apiFetch(`/api/orders/${id}/full-details`).catch(() => null),
        ]);
        if (!mounted) return;
        setOrder(o as Order);
        setShipment(s);
        setIssues(Array.isArray(is) ? is : []);
        setFullDetails(details);
        initAck(o as Order);
        try {
          await apiFetch(`/api/invoices/by-order/${(o as any).id}`);
          if (mounted) setInvoiceExists(true);
        } catch {
          if (mounted) setInvoiceExists(false);
        }
      } catch (e:any) {
        if (mounted) setError(e?.message || 'Failed to load');
      } finally {
        if (mounted) setLoading(false);
      }
    })();
    return () => { mounted = false; };
  }, [id, hydrated]);

  // Acknowledgement state
  const [ackItems, setAckItems] = useState<{ order_item_id:number; received_qty:number; status:'received'|'not_received'; comment:string; delivered_qty:number; product_name:string; ordered_qty:number; approved_qty:number }[]>([]);
  const [employee_code, setEmployeeCode] = useState("");
  const [receiver_name, setReceiverName] = useState("");
  const [branch_manager_name, setBranchManagerName] = useState("");
  const [remarks, setRemarks] = useState("");
  const [rating, setRating] = useState<number>(5);
  const [confirmAll, setConfirmAll] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  function initAck(ord: Order) {
    const rows = (ord.items || []).map((it: any) => ({
      order_item_id: it.id,
      received_qty: Number(it.qty_approved ?? 0),
      status: 'received' as const,
      comment: '',
      delivered_qty: Number(it.qty_approved ?? 0),
      product_name: it.product?.name ?? '',
      ordered_qty: Number(it.qty_requested ?? 0),
      approved_qty: Number(it.qty_approved ?? 0),
    }));
    setAckItems(rows);
  }

  const setItem = (id:number, patch: Partial<{received_qty:number; status:'received'|'not_received'; comment:string}>) => {
    setAckItems((prev) => prev.map((r) => r.order_item_id === id ? { ...r, ...patch, received_qty: Math.min(r.delivered_qty, Math.max(0, patch.received_qty ?? r.received_qty)) } : r));
  };

  if (loading) return <div className="p-6"><PageState variant="loading" title="Loading order" description="Fetching order details…" /></div>;
  if (error) {
    const forbidden = /\b403\b/.test(error);
    return <div className="p-6"><PageState variant={forbidden? 'forbidden':'error'} title={forbidden? 'Access denied' : 'Failed to load order'} description={error} /></div>;
  }
  if (!order) return <div className="p-6"><PageState variant="loading" title="Loading order" description="Fetching order details…" /></div>;
  const ack = fullDetails?.acknowledgement || null;
  const hasAckMeaningful = !!(ack && (ack.employee_code || ack.receiver_name || ack.branch_manager_name || (typeof ack.rating === 'number') || (ack.remarks && String(ack.remarks).trim())));
  const ackItemsView = Array.isArray(fullDetails?.items)
    ? (fullDetails!.items as any[]).filter((it:any) => typeof it.received_qty === 'number')
    : [];
  // Consider acknowledgement existing only if there is meaningful received data (>0) or summary fields
  const ackItemsHaveSignal = ackItemsView.some((it:any) => {
    const rec = Number(it.received_qty ?? 0);
    const st = (it.status || '').toString().trim();
    return rec > 0 || (st.length > 0 && st !== 'not_received');
  });
  const ackExists = hasAckMeaningful || ackItemsHaveSignal;
  const beforeSubmit = order.status === 'delivered' && !ackExists;

  // Form validation helper
  const hasErrors = beforeSubmit && ackItems.some((r) => (
    r.received_qty > r.delivered_qty ||
    ((r.status === 'not_received' || r.received_qty < r.delivered_qty) && !r.comment.trim())
  ));

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold tracking-tight">Order #{order.id}</h1>
      <div className="text-sm">{beforeSubmit ? 'Status: Delivered — Awaiting Acknowledgement' : `Status: ${order.status}`}</div>
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
      {(() => {
        const submittedAt = (ack && (ack.submitted_at || ack.created_at || ack.updated_at)) as string | undefined;
        return (!beforeSubmit && submittedAt) ? (
          <div className="text-xs text-muted-foreground">Acknowledgement submitted: {submittedAt}</div>
        ) : null;
      })()}
      {beforeSubmit && (
        <div className="rounded-[12px] border bg-white p-3 text-sm">
          <div className="font-medium mb-1">Delivery Summary</div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-2">
            <div><span className="text-muted-foreground">Courier:</span> {shipment?.courier_name || '—'}</div>
            <div><span className="text-muted-foreground">Tracking:</span> {shipment?.tracking_number || '—'}</div>
            <div><span className="text-muted-foreground">Delivered/ETA:</span> {shipment?.delivered_at || shipment?.estimated_delivery_date || '—'}</div>
          </div>
        </div>
      )}
      {beforeSubmit && (
        <>
          <details className="text-sm">
            <summary className="border px-3 py-1 inline-block cursor-pointer select-none">View full details</summary>
            <div className="mt-2"><OrderDetails orderId={order.id} /></div>
          </details>
          {invoiceExists && (
            <div className="space-x-2 text-xs text-muted-foreground">
              <button className="underline" onClick={() => openInvoicePdf(order.id)}>Download Invoice (PDF)</button>
              <button className="underline" onClick={() => downloadInvoiceExcel(order.id)}>Download Invoice (Excel)</button>
            </div>
          )}
        </>
      )}
      
      
      {beforeSubmit && (
        <div className="space-y-3 text-sm border p-3">
          <div className="font-medium">Acknowledgement</div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b">
                  <th className="text-left p-2">Product</th>
                  <th className="text-right p-2">Delivered Qty</th>
                  <th className="text-right p-2">Received Qty</th>
                  <th className="text-left p-2">Status</th>
                  <th className="text-left p-2">Comment</th>
                </tr>
              </thead>
              <tbody>
                {ackItems.map((r) => {
                  const needsComment = (r.status === 'not_received' || r.received_qty < r.delivered_qty);
                  const commentMissing = needsComment && !r.comment.trim();
                  const qtyInvalid = r.received_qty > r.delivered_qty;
                  return (
                  <tr key={r.order_item_id} className="border-b">
                    <td className="p-2">{r.product_name}</td>
                    <td className="p-2 text-right">{r.delivered_qty}</td>
                    <td className="p-2 text-right">
                      <input
                        type="number"
                        min={0}
                        max={r.delivered_qty}
                        value={r.received_qty === 0 ? '' : r.received_qty}
                        inputMode="numeric"
                        pattern="[0-9]*"
                        className={`border px-2 py-1 w-24 text-right ${qtyInvalid ? 'border-red-500 ring-1 ring-red-300' : ''}`}
                        title={`Enter a value between 0 and ${r.delivered_qty}`}
                        onKeyDown={(e) => { const allow=["Backspace","Delete","ArrowLeft","ArrowRight","Tab"]; if(allow.includes(e.key)) return; if(!/^[0-9]$/.test(e.key)) e.preventDefault(); }}
                        onChange={(e) => {
                          const raw = (e.target.value || '').replace(/[^0-9]/g, '');
                          const num = raw === '' ? 0 : parseInt(raw, 10);
                          setItem(r.order_item_id, { received_qty: num });
                        }}
                        onBlur={(e) => {
                          const raw = (e.target.value || '').replace(/[^0-9]/g, '');
                          const num = raw === '' ? 0 : parseInt(raw, 10);
                          setItem(r.order_item_id, { received_qty: num });
                        }}
                      />
                      {qtyInvalid && <div className="text-xs text-red-600 mt-1">Cannot exceed delivered qty</div>}
                    </td>
                    <td className="p-2">
                      <select
                        className="border px-2 py-1"
                        value={r.status}
                        onChange={(e) => setItem(r.order_item_id, { status: e.target.value as any })}
                      >
                        <option value="received">Received</option>
                        <option value="not_received">Not received</option>
                      </select>
                    </td>
                    <td className="p-2">
                      <input
                        className={`border px-2 py-1 w-full ${commentMissing ? 'border-red-500 ring-1 ring-red-300' : ''}`}
                        placeholder={needsComment ? 'Required (not received/short)' : 'Optional'}
                        value={r.comment}
                        onChange={(e) => setItem(r.order_item_id, { comment: e.target.value })}
                      />
                      {commentMissing && <div className="text-xs text-red-600 mt-1">Comment is required</div>}
                    </td>
                  </tr>
                );})}
              </tbody>
            </table>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            <input className="border px-2 py-1" placeholder="Employee code" value={employee_code} onChange={(e) => setEmployeeCode(e.target.value)} />
            <input className="border px-2 py-1" placeholder="Receiver name" value={receiver_name} onChange={(e) => setReceiverName(e.target.value)} />
            <input className="border px-2 py-1" placeholder="Branch manager name" value={branch_manager_name} onChange={(e) => setBranchManagerName(e.target.value)} />
          <label className="text-xs text-muted-foreground">Rating (1-5)</label>
          <select className="border px-2 py-1" value={rating} onChange={(e) => setRating(parseInt(e.target.value))}>
            {[1,2,3,4,5].map((n) => <option key={n} value={n}>{n}</option>)}
          </select>
            <textarea className="border p-2 md:col-span-2" placeholder="Remarks (optional)" value={remarks} onChange={(e) => setRemarks(e.target.value)} />
          </div>
          <label className="flex items-center gap-2">
            <input type="checkbox" checked={confirmAll} onChange={(e) => setConfirmAll(e.target.checked)} />
            <span>I confirm that I have reviewed all items</span>
          </label>
          <div>
            <button
              className="border px-4 py-2"
              disabled={submitting || !confirmAll || !employee_code || !receiver_name || !branch_manager_name || hasErrors}
              onClick={async () => {
                try {
                  setSubmitting(true);
                  const payload = {
                    employee_code, receiver_name, branch_manager_name, remarks, rating,
                    items: ackItems.map((r) => ({ order_item_id: r.order_item_id, received_qty: r.received_qty, status: r.status, comment: r.comment || undefined })),
                  };
                  await apiFetch(`/api/orders/${id}/acknowledge`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                  const fresh = await apiFetch(`/api/orders/${id}`);
                  setOrder(fresh as Order);
                  const det = await apiFetch(`/api/orders/${id}/full-details`).catch(() => null);
                  setFullDetails(det);
                } catch (e:any) {
                  setError(e?.message || 'Failed to submit acknowledgement');
                } finally {
                  setSubmitting(false);
                }
              }}
            >{submitting ? 'Submitting…' : 'Submit Acknowledgement'}</button>
            {hasErrors && <div className="text-xs text-red-600 mt-2">Please resolve highlighted fields</div>}
          </div>
        </div>
      )}
      {!ackExists && order.status !== 'delivered' && issues.length > 0 && (
        <div className="space-y-2 text-sm border p-3">
          <div className="font-medium">Reported Issues</div>
          <ul className="list-disc pl-6">
            {issues.map((it: any) => (
              <li key={it.id}>
                <span className="capitalize">[{it.status}]</span> {it.description}
              </li>
            ))}
          </ul>
        </div>
      )}

      {ackExists && (
        <div className="space-y-3 text-sm">
          {hasAckMeaningful && (
            <div className="rounded-[12px] border bg-white p-3">
              <div className="font-medium mb-1">Acknowledgement Summary</div>
              <div className="space-y-1">
                {ack?.employee_code ? (<div>Employee Code: {ack.employee_code}</div>) : null}
                {ack?.receiver_name ? (<div>Receiver: {ack.receiver_name}</div>) : null}
                {ack?.branch_manager_name ? (<div>Branch Manager: {ack.branch_manager_name}</div>) : null}
                {typeof ack?.rating === 'number' ? (<div>Rating: {ack.rating}</div>) : null}
                {ack?.remarks ? (<div>Remarks: {ack.remarks}</div>) : null}
              </div>
            </div>
          )}

          {ackItemsView.length > 0 && (
            <div className="rounded-[12px] border bg-white p-3">
              <div className="font-medium mb-2">Acknowledged Items</div>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left p-2">Product</th>
                      <th className="text-right p-2">Delivered</th>
                      <th className="text-right p-2">Received</th>
                      <th className="text-left p-2">Status</th>
                      <th className="text-left p-2">Comment</th>
                    </tr>
                  </thead>
                  <tbody>
                    {ackItemsView.map((it:any, idx:number) => {
                      const del = Number(it.delivered_qty ?? 0);
                      const rec = Number(it.received_qty ?? 0);
                      const raw = (it.status || '').toString();
                      const isShort = rec < del;
                      const label = raw === 'not_received' ? 'Not Received' : (isShort || raw === 'partially_received') ? 'Partially Received' : 'Received';
                      return (
                        <tr key={idx} className="border-b">
                          <td className="p-2">{it.product_name}</td>
                          <td className="p-2 text-right">{del}</td>
                          <td className="p-2 text-right">{rec}</td>
                          <td className="p-2">{label}</td>
                          <td className="p-2 italic">{it.comment || '—'}</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {(() => {
            const discrepancies = ackItemsView.filter((it:any) => {
              const del = Number(it.delivered_qty ?? 0);
              const rec = Number(it.received_qty ?? 0);
              return rec < del || (it.status === 'not_received');
            });
            if (discrepancies.length === 0) {
              return (
                <div className="rounded-[12px] border bg-white p-3 text-sm text-green-700">All items acknowledged in full.</div>
              );
            }
            return (
              <div className="rounded-[12px] border bg-white p-3">
                <div className="font-medium mb-1">Acknowledgement Discrepancies</div>
                <ul className="list-disc pl-6 space-y-1">
                  {discrepancies.map((it:any, idx:number) => {
                    const del = Number(it.delivered_qty ?? 0);
                    const rec = Number(it.received_qty ?? 0);
                    const status = it.status === 'not_received' ? 'Not Received' : 'Partially Received';
                    return (
                      <li key={idx}>
                        {it.product_name} — {status}; Received {rec} of {del}{it.comment ? `; Comment: ${it.comment}` : ''}
                      </li>
                    );
                  })}
                </ul>
              </div>
            );
          })()}

          {/* Delivery/Tracking Summary (compact) */}
          <div className="rounded-[12px] border bg-white p-3">
            <div className="font-medium mb-1">Delivery Summary</div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-2">
              <div><span className="text-muted-foreground">Courier:</span> {shipment?.courier_name || '—'}</div>
              <div><span className="text-muted-foreground">Tracking:</span> {shipment?.tracking_number || '—'}</div>
              <div><span className="text-muted-foreground">Delivered/ETA:</span> {shipment?.delivered_at || shipment?.estimated_delivery_date || '—'}</div>
            </div>
          </div>

          {/* Secondary actions */}
          {invoiceExists && (
            <div className="space-x-2 text-xs text-muted-foreground">
              <button className="underline" onClick={() => openInvoicePdf(order.id)}>Invoice (PDF)</button>
              <button className="underline" onClick={() => downloadInvoiceExcel(order.id)}>Invoice (Excel)</button>
            </div>
          )}
          <details className="text-sm">
            <summary className="border px-3 py-1 inline-block cursor-pointer select-none">View full details</summary>
            <div className="mt-2"><OrderDetails orderId={order.id} /></div>
          </details>

          {/* Optional: Reported Issues (only if API has records) */}
          {issues.length > 0 && (
            <div className="space-y-2 text-sm border p-3">
              <div className="font-medium">Reported Issues</div>
              <ul className="list-disc pl-6">
                {issues.map((it: any) => (
                  <li key={it.id}><span className="capitalize">[{it.status}]</span> {it.description}</li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
