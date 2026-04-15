import { useQuery } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api";

export type OrderFullDetails = {
  order: { id: number; status: string; total_amount: number };
  items: {
    product_name: string;
    requested_qty: number;
    approved_qty: number;
    delivered_qty: number;
    received_qty: number;
    status?: string | null;
    comment?: string | null;
  }[];
  acknowledgement: {
    employee_code?: string;
    receiver_name?: string;
    rating?: number;
    remarks?: string;
  } | null;
  issues: { id: number; status: string; description: string }[];
};

export function useOrderDetail(orderId: number | null) {
  return useQuery<OrderFullDetails>({
    queryKey: ["order-detail", orderId],
    queryFn: () => apiFetch(`/api/orders/${orderId}/full-details`),
    enabled: orderId !== null && orderId > 0,
  });
}

export function useOrderTracking(orderId: number | null) {
  return useQuery({
    queryKey: ["order-tracking", orderId],
    queryFn: () => apiFetch(`/api/orders/${orderId}/tracking`),
    enabled: orderId !== null && orderId > 0,
  });
}

export function useOrderIssues(orderId: number | null) {
  return useQuery({
    queryKey: ["order-issues", orderId],
    queryFn: () => apiFetch(`/api/orders/${orderId}/issues`),
    enabled: orderId !== null && orderId > 0,
  });
}
