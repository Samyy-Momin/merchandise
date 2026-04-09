"use client";

import { useEffect, useState } from "react";
import { apiFetch } from "@/lib/api";

type ItemView = {
  product_name: string;
  requested_qty: number;
  approved_qty: number;
  delivered_qty: number;
  received_qty: number;
  status?: string | null;
  comment?: string | null;
};

type FullDetails = {
  order: { id: number; status: string; total_amount: number };
  items: ItemView[];
  acknowledgement: { employee_code?: string; receiver_name?: string; rating?: number; remarks?: string } | null;
  issues: any[];
};

export function OrderDetails({ orderId }: { orderId: number }) {
  const [data, setData] = useState<FullDetails | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;
    apiFetch(`/api/orders/${orderId}/full-details`)
      .then((res) => mounted && setData(res as FullDetails))
      .catch((e:any) => setError(e?.message || 'Failed to load'))
      .finally(() => setLoading(false));
    return () => { mounted = false; };
  }, [orderId]);

  if (loading) return <div className="text-sm">Loading details…</div>;
  if (error) return <div className="text-sm text-red-600">{error}</div>;
  if (!data) return null;

  return (
    <div className="space-y-3 text-sm border p-3">
      <div className="font-medium">Order Details</div>
      <div>Order #{data.order.id} — Status: {data.order.status}</div>
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b">
              <th className="text-left p-2">Product</th>
              <th className="text-right p-2">Requested</th>
              <th className="text-right p-2">Approved</th>
              <th className="text-right p-2">Delivered</th>
              <th className="text-right p-2">Received</th>
              <th className="text-left p-2">Status</th>
              <th className="text-left p-2">Comment</th>
            </tr>
          </thead>
          <tbody>
            {data.items.map((it, idx) => {
              const mismatch = it.received_qty < it.delivered_qty;
              const hasComment = !!(it.comment && it.comment.trim());
              return (
                <tr key={idx} className="border-b">
                  <td className="p-2">{it.product_name}</td>
                  <td className="p-2 text-right">{it.requested_qty}</td>
                  <td className="p-2 text-right">{it.approved_qty}</td>
                  <td className="p-2 text-right">{it.delivered_qty}</td>
                  <td className={"p-2 text-right " + (mismatch ? "text-red-600" : "")}>
                    {it.received_qty}{mismatch ? " (Partial/Issue)" : ""}
                  </td>
                  <td className="p-2">{it.status || "—"}</td>
                  <td className={"p-2 " + (hasComment ? "italic" : "")}>{it.comment || "—"}</td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
      {data.acknowledgement && (
        <div className="space-y-1">
          <div className="font-medium">Acknowledgement Summary</div>
          <div>Employee Code: {data.acknowledgement.employee_code || '—'}</div>
          <div>Receiver: {data.acknowledgement.receiver_name || '—'}</div>
          <div>Rating: {data.acknowledgement.rating ?? '—'}</div>
          <div>Remarks: {data.acknowledgement.remarks || '—'}</div>
        </div>
      )}
      {Array.isArray(data.issues) && data.issues.length > 0 && (
        <div className="space-y-1">
          <div className="font-medium">Issues</div>
          <ul className="list-disc pl-6">
            {data.issues.map((it:any) => (
              <li key={it.id}><span className="capitalize">[{it.status}]</span> {it.description}</li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}

