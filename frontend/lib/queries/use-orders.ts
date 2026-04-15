import { useQuery } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api";
import type { Order } from "@/types";

export function useOrders() {
  return useQuery<Order[]>({
    queryKey: ["orders"],
    queryFn: async () => {
      const res = await apiFetch("/api/orders");
      const anyRes = res as any;
      return Array.isArray(anyRes?.data) ? anyRes.data : Array.isArray(res) ? res : [];
    },
  });
}
