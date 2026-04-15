import { useQuery } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api";
import type { Address } from "@/types";

export function useAddresses() {
  return useQuery<Address[]>({
    queryKey: ["addresses"],
    queryFn: async () => {
      const res = await apiFetch("/api/addresses");
      return Array.isArray(res) ? res : [];
    },
  });
}
