"use client";

import { useEffect, useState } from "react";
import { apiFetch } from "@/lib/api";
import { openInvoicePdf, downloadInvoiceExcel } from "@/lib/invoice";

type Inv = { id:number; order_id:number; invoice_number:string; total_amount:number; status:string; created_at:string };

export default function VendorInvoices() {
  const [rows, setRows] = useState<Inv[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;
    apiFetch('/api/invoices')
      .then((res) => {
        const list = Array.isArray(res?.data) ? res.data : res;
        if (mounted) setRows(list);
      })
      .catch((e:any) => setError(e?.message || 'Failed to load invoices'))
      .finally(() => setLoading(false));
    return () => { mounted = false; };
  }, []);

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold tracking-tight">Invoices</h1>
      {error && <div className="text-sm text-red-600">{error}</div>}
      {loading ? <div className="text-sm">Loading…</div> : (
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b">
                <th className="text-left p-2">Invoice #</th>
                <th className="text-left p-2">Order ID</th>
                <th className="text-left p-2">Date</th>
                <th className="text-right p-2">Total</th>
                <th className="text-left p-2">Status</th>
                <th className="text-left p-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r: any) => (
                <tr key={r.id} className="border-b">
                  <td className="p-2">{r.invoice_number}</td>
                  <td className="p-2">{r.order_id}</td>
                  <td className="p-2">{new Date(r.created_at).toLocaleDateString()}</td>
                  <td className="p-2 text-right">₹ {Number(r.total_amount).toFixed(2)}</td>
                  <td className="p-2">{r.status}</td>
                  <td className="p-2 space-x-2">
                    <button className="underline" onClick={() => openInvoicePdf(r.order_id)}>PDF</button>
                    <button className="underline" onClick={() => downloadInvoiceExcel(r.order_id)}>Excel</button>
                    <a className="underline" href={`/vendor/orders/${r.order_id}`}>View Details</a>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
