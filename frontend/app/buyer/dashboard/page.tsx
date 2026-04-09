"use client";

import { useEffect, useMemo, useState } from "react";
import { apiFetch } from "@/lib/api";
import type { Order } from "@/types";
import { Card } from "@/components/ui/card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/table";

export default function BuyerDashboard() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;
    apiFetch("/api/orders")
      .then((res) => {
        const list = Array.isArray(res?.data) ? (res.data as Order[]) : (res as Order[]);
        // eslint-disable-next-line no-console
        console.log("/api/orders response:", res);
        if (mounted) setOrders(list);
      })
      .catch((e: any) => {
        // eslint-disable-next-line no-console
        console.error("Failed to load orders:", e);
        if (mounted) setError(e?.message || "Failed to load orders");
      })
      .finally(() => mounted && setLoading(false));
    return () => {
      mounted = false;
    };
  }, []);

  const summary = useMemo(() => {
    const total = orders.length;
    const pending = orders.filter((o) => o.status === "pending_approval").length;
    const approved = orders.filter((o) => o.status === "approved").length;
    const amount = orders.reduce((s, o) => s + (Number(o.total_amount) || 0), 0);
    return { total, pending, approved, amount };
  }, [orders]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Procurement Dashboard</h1>
        <p className="text-sm text-muted-foreground">Welcome back. Review recent activity and pending actions.</p>
      </div>

      {/* Summary cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4">
          <div className="text-sm text-muted-foreground">Total Orders</div>
          <div className="mt-2 text-2xl font-semibold">{loading ? "—" : summary.total}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-muted-foreground">Pending Approval</div>
          <div className="mt-2 text-2xl font-semibold">{loading ? "—" : summary.pending}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-muted-foreground">Approved</div>
          <div className="mt-2 text-2xl font-semibold">{loading ? "—" : summary.approved}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-muted-foreground">Total Value</div>
          <div className="mt-2 text-2xl font-semibold">{loading ? "—" : `₹ ${summary.amount.toFixed(2)}`}</div>
        </Card>
      </div>

      {/* Recent orders */}
      <Card className="p-0">
        <div className="px-4 py-3 border-b">
          <h2 className="text-sm font-medium">Recent Orders</h2>
        </div>
        {error ? (
          <div className="p-4 text-sm text-red-600">{error}</div>
        ) : (
          <div className="p-4">
            <Table>
              <Thead>
              <Tr>
                <Th>ID</Th>
                <Th>Status</Th>
                <Th className="text-right">Total</Th>
                <Th className="text-right">Items</Th>
                <Th className="text-right">Actions</Th>
              </Tr>
              </Thead>
              <Tbody>
                {loading ? (
                  <Tr>
                    <Td colSpan={4}>Loading…</Td>
                  </Tr>
                ) : orders.length === 0 ? (
                  <Tr>
                    <Td colSpan={4} className="text-muted-foreground">No recent orders.</Td>
                  </Tr>
                ) : (
                  orders.slice(0, 10).map((o) => (
                    <Tr key={o.id}>
                      <Td>#{o.id}</Td>
                      <Td className="capitalize">{o.status}</Td>
                      <Td className="text-right">₹ {Number(o.total_amount).toFixed(2)}</Td>
                      <Td className="text-right">{o.items?.length ?? 0}</Td>
                      <Td className="text-right"><a className="underline" href={`/buyer/orders/${o.id}`}>View</a></Td>
                    </Tr>
                  ))
                )}
              </Tbody>
            </Table>
          </div>
        )}
      </Card>
    </div>
  );
}
