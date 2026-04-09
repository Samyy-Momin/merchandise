"use client";

import { useEffect, useState } from "react";
import { apiFetch } from "@/lib/api";
import { useParams } from "next/navigation";

type Approval = {
  id: number;
  order_id: number;
  approver_id: string;
  status: "approved" | "rejected" | "partial";
  comments?: string | null;
  created_at: string;
  order?: any;
};

export default function ApproverDecisionDetail() {
  const params = useParams<{ id: string }>();
  const [approval, setApproval] = useState<Approval | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const id = Number(params?.id);

  useEffect(() => {
    let mounted = true;
    apiFetch(`/api/approvals/${id}`)
      .then((res) => {
        // eslint-disable-next-line no-console
        console.log('[Approver] approval detail:', res);
        if (mounted) setApproval(res as Approval);
      })
      .catch((e: any) => {
        // eslint-disable-next-line no-console
        console.error('[Approver] approval detail failed:', e);
        if (mounted) setError(e?.message || 'Failed to load');
      })
      .finally(() => mounted && setLoading(false));
    return () => { mounted = false; };
  }, [id]);

  if (loading) return <div className="p-6 text-sm">Loading…</div>;
  if (error) return <div className="p-6 text-sm text-red-600">{error}</div>;
  if (!approval) return <div className="p-6 text-sm">Not found</div>;

  const order = approval.order;
  const items = order?.items || [];

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold tracking-tight">Decision #{approval.id}</h1>
      <div className="text-sm">Status: {approval.status}</div>
      {approval.comments && <div className="text-sm">Comment: {approval.comments}</div>}
      <div className="text-xs text-muted-foreground">{new Date(approval.created_at).toLocaleString()}</div>
      <div className="border p-3">
        <div className="font-medium text-sm mb-2">Order #{order?.id}</div>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b">
              <th className="text-left p-2">Name</th>
              <th className="text-right p-2">Requested</th>
              <th className="text-right p-2">Approved</th>
            </tr>
          </thead>
          <tbody>
            {items.map((it: any) => (
              <tr key={it.id} className="border-b">
                <td className="p-2">{it.product?.name}</td>
                <td className="p-2 text-right">{it.qty_requested}</td>
                <td className="p-2 text-right">{it.qty_approved ?? 0}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
