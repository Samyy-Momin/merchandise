import { useMutation, useQueryClient } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api";

type ApproveOrderPayload = {
  orderId: number;
  items?: { order_item_id: number; qty_approved: number }[];
};

export function useApproveOrder() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ orderId, items }: ApproveOrderPayload) => {
      const res = await apiFetch(`/api/orders/${orderId}/approve`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ items }),
      });
      return res;
    },
    onSuccess: (_data, variables) => {
      queryClient.invalidateQueries({ queryKey: ["orders"] });
      queryClient.invalidateQueries({ queryKey: ["approvals"] });
      queryClient.invalidateQueries({ queryKey: ["approval-stats"] });
      queryClient.invalidateQueries({ queryKey: ["order-detail", variables.orderId] });
    },
  });
}
