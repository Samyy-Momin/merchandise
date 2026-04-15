"use client";

import { useEffect, useMemo, useState } from "react";
import { useParams } from "next/navigation";
import { apiFetch } from "@/lib/api";
import { openInvoicePdf, downloadInvoiceExcel } from "@/lib/invoice";
import { PageState } from "@/components/ui/page-state";

type Inv = { id:number; order_id:number; invoice_number:string; total_amount:number; status:string; created_at:string };

export default function VendorInvoiceDetail() {
  const params = useParams<{ id: string }>();
  const orderId = Number(params?.id);
  const [row, setRow] = useState<Inv | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!orderId || Number.isNaN(orderId)) { setError('Invalid invoice id'); setLoading(false); return; }
    let mounted = true;
    apiFetch(`/api/invoices/by-order/${orderId}`)
      .then((res) => { if (mounted) setRow(res as any as Inv); })
      .catch((e:any) => setError(e?.message || 'Invoice not found'))
      .finally(() => setLoading(false));
    return () => { mounted = false; };
  }, [orderId]);

  const meta = useMemo(() => {
    if (!row) return null;
    const created = new Date(row.created_at);
    const due = new Date(created.getTime() + 15*24*60*60*1000); // PRD: due = created + 15 days
    const paid = row.status?.toLowerCase?.() === 'paid';
    return {
      createdAt: created,
      dueDate: due,
      isPaid: paid,
      total: Number(row.total_amount || 0),
      amountPaid: paid ? Number(row.total_amount || 0) : null, // No API for partial payments in this codebase
      outstanding: paid ? 0 : null, // Unknown without payment API; keep null to render '—'
    };
  }, [row]);

  if (loading) return <div className="p-6"><PageState variant="loading" title="Loading invoice" description="Fetching invoice details…" /></div>;
  if (error) return <div className="p-6"><PageState variant={/\b403\b/.test(error)? 'forbidden':'error'} title={/\b403\b/.test(error)? 'Access denied' : 'Failed to load invoice'} description={error} /></div>;
  if (!row || !meta) return <div className="p-6"><PageState variant="empty" title="Invoice not found" /></div>;

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-2xl font-semibold tracking-tight">Invoice {row.invoice_number}</h1>
        <div className="text-sm">Order #{row.order_id} — Status: {row.status}</div>
      </div>

      <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
        <div className="grid sm:grid-cols-2 gap-3">
          <div><span className="text-muted-foreground">Created:</span> {meta.createdAt.toLocaleDateString()}</div>
          <div><span className="text-muted-foreground">Due date:</span> {meta.dueDate.toLocaleDateString()}</div>
          <div><span className="text-muted-foreground">Total:</span> ₹ {meta.total.toFixed(2)}</div>
          <div><span className="text-muted-foreground">Amount paid:</span> {meta.amountPaid !== null ? `₹ ${meta.amountPaid.toFixed(2)}` : '—'}</div>
          <div><span className="text-muted-foreground">Outstanding:</span> {meta.outstanding !== null ? `₹ ${meta.outstanding.toFixed(2)}` : '—'}</div>
          <div><span className="text-muted-foreground">Payment status:</span> {meta.isPaid ? 'Paid' : 'Open'}</div>
        </div>
        <div className="mt-3 space-x-2">
          <button className="underline" onClick={() => openInvoicePdf(row.order_id)}>Download PDF</button>
          <button className="underline" onClick={() => downloadInvoiceExcel(row.order_id)}>Download Excel</button>
          <a className="underline" href={`/vendor/orders/${row.order_id}`}>View Order</a>
        </div>
        <div className="mt-3 text-xs text-muted-foreground">
          Note: Detailed payment history and partial payments are not exposed by the current API. Display is read-only.
        </div>
      </section>

      {/* Optional: payment history table (read-only placeholder) */}
      <section className="rounded-[12px] border bg-white p-4 shadow-card text-sm">
        <div className="font-medium mb-2">Payment History</div>
        <div className="text-muted-foreground">No payment records available via API.</div>
      </section>
    </div>
  );
}

