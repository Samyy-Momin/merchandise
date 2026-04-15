import { useMutation, useQueryClient } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api";

type VendorAction = "process" | "dispatch" | "transit" | "deliver";

type VendorActionPayload = {
  orderId: number;
  action: VendorAction;
  body?: Record<string, unknown>;
};

export function useVendorAction() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ orderId, action, body }: VendorActionPayload) => {
      const res = await apiFetch(`/api/vendor/orders/${orderId}/${action}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        ...(body ? { body: JSON.stringify(body) } : {}),
      });
      return res;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["vendor-orders"] });
      queryClient.invalidateQueries({ queryKey: ["orders"] });
    },
  });
}
