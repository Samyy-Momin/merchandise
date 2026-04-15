import { useQuery } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api";

export type VendorOrder = {
  id: number;
  status: string;
  total_amount: number;
  items?: { id: number; product_id: number; qty_requested: number; price: number; product?: { name: string } }[];
  [key: string]: unknown;
};

export function useVendorOrders() {
  return useQuery<VendorOrder[]>({
    queryKey: ["vendor-orders"],
    queryFn: async () => {
      const res = await apiFetch("/api/vendor/orders");
      const anyRes = res as any;
      return Array.isArray(anyRes?.data) ? anyRes.data : Array.isArray(res) ? res : [];
    },
  });
}
