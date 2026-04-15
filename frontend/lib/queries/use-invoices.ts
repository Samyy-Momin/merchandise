import { useQuery } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api";

export type Invoice = {
  id: number;
  order_id: number;
  invoice_number: string;
  total_amount: number;
  status: string;
  created_at: string;
};

export function useInvoices() {
  return useQuery<Invoice[]>({
    queryKey: ["invoices"],
    queryFn: async () => {
      const res = await apiFetch("/api/invoices");
      const anyRes = res as any;
      return Array.isArray(anyRes?.data) ? anyRes.data : Array.isArray(res) ? res : [];
    },
  });
}

export function useInvoicesByOrder(orderId: number | null) {
  return useQuery<Invoice[]>({
    queryKey: ["invoices", "by-order", orderId],
    queryFn: async () => {
      const res = await apiFetch(`/api/invoices/by-order/${orderId}`);
      const anyRes = res as any;
      return Array.isArray(anyRes?.data) ? anyRes.data : Array.isArray(res) ? res : [];
    },
    enabled: orderId !== null && orderId > 0,
  });
}
