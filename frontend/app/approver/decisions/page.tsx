"use client";

import { useEffect, useState } from "react";
import { apiFetch } from "@/lib/api";
import { PageState } from "@/components/ui/page-state";

type Approval = {
  id: number;
  order_id: number;
  approver_id: string;
  status: "approved" | "rejected" | "partial";
  comments?: string | null;
  created_at: string;
  order?: any;
};

export default function ApproverDecisions() {
  const [status, setStatus] = useState<"approved" | "rejected" | "partial">("approved");
  const [list, setList] = useState<Approval[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = () => {
    setLoading(true);
    setError(null);
    apiFetch(`/api/approvals?status=${status}`)
      .then((res: unknown) => {
        const hasDataArray = (v: unknown): v is { data: Approval[] } =>
          typeof v === 'object' && v !== null && Array.isArray((v as any).data);

        const rows = hasDataArray(res)
          ? res.data
          : Array.isArray(res)
            ? (res as Approval[])
            : [];
        // eslint-disable-next-line no-console
        console.log('[Approver] approvals list:', rows);
        setList(rows);
      })
      .catch((e: unknown) => {
        // eslint-disable-next-line no-console
        console.error('[Approver] approvals list failed:', e);
        setError(e instanceof Error ? e.message : String(e));
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    const id = window.setTimeout(() => load(), 0);
    return () => window.clearTimeout(id);
  }, [status]);

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold tracking-tight">My Decisions</h1>
        <div className="text-sm space-x-2">
          <button className={`border px-3 py-1 ${status==='approved'?'font-semibold':''}`} onClick={() => setStatus('approved')}>Approved</button>
          <button className={`border px-3 py-1 ${status==='rejected'?'font-semibold':''}`} onClick={() => setStatus('rejected')}>Rejected</button>
          <button className={`border px-3 py-1 ${status==='partial'?'font-semibold':''}`} onClick={() => setStatus('partial')}>Partial</button>
        </div>
      </div>
      {error && <div />}
      {loading ? (
        <PageState variant="loading" title="Loading decisions" description="Fetching your decisions…" />
      ) : error ? (
        <PageState variant={/\b403\b/.test(error)? 'forbidden':'error'} title={/\b403\b/.test(error)? 'Access denied' : 'Failed to load decisions'} description={error} />
      ) : list.length === 0 ? (
        <PageState variant="empty" title="No records" description="You have no approvals yet." />
      ) : (
        <ul className="text-sm space-y-2">
          {list.map((a) => (
            <li key={a.id} className="border p-3 flex items-center justify-between">
              <div>
                <div>Order #{a.order_id} — {a.status}</div>
                {a.comments && <div className="text-xs text-muted-foreground">Comment: {a.comments}</div>}
                <div className="text-xs text-muted-foreground">{new Date(a.created_at).toLocaleString()}</div>
              </div>
              <a className="underline" href={`/approver/decisions/${a.id}`}>View</a>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
