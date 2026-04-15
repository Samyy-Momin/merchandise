import { useQuery } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api";
import type { Product } from "@/types";

export type ProductFilters = {
  category?: string;
  search?: string;
  min?: string;
  max?: string;
  page?: number;
  per_page?: number;
};

export type ProductsResponse = {
  data: Product[];
  current_page: number;
  last_page: number;
  next_page_url?: string | null;
  prev_page_url?: string | null;
  total?: number;
  per_page?: number;
};

export function useProducts(filters: ProductFilters = {}) {
  return useQuery<ProductsResponse>({
    queryKey: ["products", filters],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (filters.category) params.set("category", filters.category);
      if (filters.search) params.set("search", filters.search);
      if (filters.min) params.set("min", filters.min);
      if (filters.max) params.set("max", filters.max);
      params.set("page", String(filters.page ?? 1));
      params.set("per_page", String(filters.per_page ?? 12));

      const res = await apiFetch(`/api/products?${params.toString()}`);
      // Normalize response shape
      const anyRes = res as any;
      const data = Array.isArray(anyRes?.data)
        ? anyRes.data
        : Array.isArray(res)
          ? res
          : [];
      return {
        data,
        current_page: Number(anyRes?.current_page ?? filters.page ?? 1),
        last_page: Number(anyRes?.last_page ?? 1),
        next_page_url: anyRes?.next_page_url ?? null,
        prev_page_url: anyRes?.prev_page_url ?? null,
        total: anyRes?.total,
        per_page: anyRes?.per_page,
      };
    },
  });
}

export function useProduct(id: number | null) {
  return useQuery<Product>({
    queryKey: ["product", id],
    queryFn: () => apiFetch(`/api/products/${id}`),
    enabled: id !== null && id > 0,
  });
}
