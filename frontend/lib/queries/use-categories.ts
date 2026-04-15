import { useQuery } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api";
import type { Category } from "@/types";

export function useCategories() {
  return useQuery<Category[]>({
    queryKey: ["categories"],
    queryFn: async () => {
      const res = await apiFetch("/api/categories");
      return Array.isArray(res) ? res : [];
    },
    staleTime: 5 * 60_000, // categories change rarely — cache 5 min
  });
}
