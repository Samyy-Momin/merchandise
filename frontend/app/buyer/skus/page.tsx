"use client";

import { apiFetch, getCategoriesCached } from "@/lib/api";
import type { Product, Category } from "@/types";
import { useEffect, useMemo, useState } from "react";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { useCart } from "@/lib/cart-context";
import { useWishlist } from "@/lib/wishlist";
import { Heart, ShoppingCart } from "lucide-react";
import { useRouter, useSearchParams } from "next/navigation";
import { useDebounce } from "@/lib/hooks";
import { ProductCard } from "@/components/product/ProductCard";
import { useToast } from "@/lib/toast";
import { Spinner } from "@/components/ui/spinner";
import { PageState } from "@/components/ui/page-state";

type PageMeta = {
  current_page: number;
  last_page: number;
  next_page_url?: string | null;
  prev_page_url?: string | null;
  total?: number;
  per_page?: number;
};

export default function BuyerSkus() {
  const router = useRouter();
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const { add } = useCart();
  const { isLiked, toggleProduct: toggleWishlist } = useWishlist();
  const { show } = useToast();
  const [message, setMessage] = useState<string | null>(null);

  const [view, setView] = useState<"grid" | "table">("grid");

  // Filters
  const [search, setSearch] = useState<string>("");
  const [categoryId, setCategoryId] = useState<string | "" | null>(null);
  const [min, setMin] = useState<string>("");
  const [max, setMax] = useState<string>("");
  const [page, setPage] = useState<number>(1);

  // Dropdown categories
  const [categories, setCategories] = useState<Category[]>([]);

  const searchParams = useSearchParams();
  const [initialized, setInitialized] = useState(false);

  // Initialize filters from URL once on mount to avoid duplicate initial fetch
  useEffect(() => {
    const cat = searchParams?.get("category") || "";
    const s = searchParams?.get("search") || "";
    const pRaw = searchParams?.get("page") || "1";
    const pNum = Number.parseInt(pRaw, 10);
    const minRaw = searchParams?.get("min") || "";
    const maxRaw = searchParams?.get("max") || "";
    setCategoryId(cat);
    setSearch(s);
    setMin(minRaw);
    setMax(maxRaw);
    setPage(Number.isFinite(pNum) && pNum > 0 ? pNum : 1);
    setInitialized(true);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Load categories for dropdown (cached in-memory)
  useEffect(() => {
    let mounted = true;
    getCategoriesCached()
      .then((list) => { if (mounted) setCategories(list); })
      .catch(() => { /* ignore */ });
    return () => { mounted = false; };
  }, []);

  // Debounced filters (500ms)
  const debouncedSearch = useDebounce(search.trim(), 500);
  const debouncedMin = useDebounce(min, 500);
  const debouncedMax = useDebounce(max, 500);

  // Fetch products with filters (with request cancellation)
  useEffect(() => {
    if (!initialized) return;
    let active = true;
    const controller = new AbortController();
    setLoading(true);
    setError(null);
    const params = new URLSearchParams();
    if (categoryId) params.set("category", String(categoryId));
    if (debouncedSearch) params.set("search", debouncedSearch);
    // Use debounced numeric values for price filters
    const dMin = debouncedMin?.trim?.() ?? "";
    const dMax = debouncedMax?.trim?.() ?? "";
    const nMin = dMin !== "" && !Number.isNaN(Number(dMin)) ? String(Number(dMin)) : "";
    const nMax = dMax !== "" && !Number.isNaN(Number(dMax)) ? String(Number(dMax)) : "";
    if (nMin) params.set("min", nMin);
    if (nMax) params.set("max", nMax);
    params.set("page", String(page));
    params.set("per_page", "12");
    const url = `/api/products?${params.toString()}`;
    apiFetch(url, { signal: controller.signal })
      .then((res: unknown) => {
        if (!active) return;
        const anyRes = res as any;
        const list = Array.isArray(anyRes?.data) ? anyRes.data : (Array.isArray(res) ? res : []);
        setProducts(list);
        // read pagination meta when present
        const meta: PageMeta = {
          current_page: Number((anyRes?.current_page ?? page ?? 1)),
          last_page: Number(anyRes?.last_page ?? 1),
          next_page_url: anyRes?.next_page_url ?? null,
          prev_page_url: anyRes?.prev_page_url ?? null,
          total: anyRes?.total,
          per_page: anyRes?.per_page,
        };
        setPagination(meta);
      })
      .catch((e: unknown) => {
        // Ignore abort errors
        if ((e as any)?.name === 'AbortError') return;
        setError(e instanceof Error ? e.message : String(e));
      })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; controller.abort(); };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initialized, categoryId, debouncedSearch, debouncedMin, debouncedMax, page]);

  const [pagination, setPagination] = useState<PageMeta>({ current_page: 1, last_page: 1 });

  // Sorting
  type SortKey = 'price_asc' | 'price_desc' | 'name_asc' | 'name_desc' | '';
  const [sort, setSort] = useState<SortKey>('');

  // Keep previous data while loading; show inline loader instead

  const categoryName = (p: Product): string => {
    const c: any = (p as any).category;
    if (c && typeof c === "object" && "name" in c) return String(c.name || "Uncategorized");
    return String(c || "Uncategorized");
  };

  const visible = useMemo(() => {
    const arr = [...products];
    switch (sort) {
      case 'price_asc':
        arr.sort((a, b) => Number(a.price) - Number(b.price));
        break;
      case 'price_desc':
        arr.sort((a, b) => Number(b.price) - Number(a.price));
        break;
      case 'name_asc':
        arr.sort((a, b) => a.name.localeCompare(b.name));
        break;
      case 'name_desc':
        arr.sort((a, b) => b.name.localeCompare(a.name));
        break;
    }
    return arr;
  }, [products, sort]);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between sticky top-14 z-10 bg-[#f1f5f9] py-2">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">SKUs</h1>
          <p className="text-sm text-muted-foreground">Browse products and add to cart.</p>
        </div>
        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant={view === "grid" ? "primary" : "outline"}
            onClick={() => setView("grid")}
          >
            Grid View
          </Button>
          <Button
            size="sm"
            variant={view === "table" ? "primary" : "outline"}
            onClick={() => setView("table")}
          >
            Table View
          </Button>
          <select
            className="ml-2 h-8 rounded-lg border border-input bg-transparent px-2.5 text-sm"
            value={sort}
            onChange={(e) => setSort(e.target.value as SortKey)}
          >
            <option value="">Sort</option>
            <option value="price_asc">Price: Low → High</option>
            <option value="price_desc">Price: High → Low</option>
            <option value="name_asc">Name: A → Z</option>
            <option value="name_desc">Name: Z → A</option>
          </select>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Sidebar */}
        <div className="lg:col-span-1">
          <Card className="p-4 sticky top-[72px] z-20">
          <div className="space-y-4">
            <div className="space-y-2">
              <div className="text-sm font-medium">Search</div>
              <div className="relative">
                <Input
                  value={search}
                  onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                  placeholder="Search products"
                  className="pr-8"
                />
                {search && (
                  <button
                    aria-label="Clear search"
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-[#1e40af] hover:text-[#1d4ed8]"
                    onClick={() => { setSearch(""); setPage(1); router.replace('/buyer/skus'); }}
                  >
                    ×
                  </button>
                )}
              </div>
            </div>
            <div className="space-y-2">
              <div className="text-sm font-medium">Category</div>
              <select
                className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 text-sm"
                value={categoryId || ""}
                onChange={(e) => {
                  const v = e.target.value;
                  setCategoryId(v);
                  setPage(1);
                  // Keep URL in sync for shareability
                  const u = new URL(window.location.href);
                  if (v) u.searchParams.set("category", v); else u.searchParams.delete("category");
                  router.replace(u.pathname + (u.search ? u.search : ""));
                }}
              >
                <option value="">All</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <div className="text-sm font-medium">Price Range</div>
              <div className="grid grid-cols-2 gap-2">
                <Input
                  type="number"
                  inputMode="numeric"
                  placeholder="Min"
                  value={min}
                  onChange={(e) => { setMin(e.target.value); setPage(1); }}
                />
                <Input
                  type="number"
                  inputMode="numeric"
                  placeholder="Max"
                  value={max}
                  onChange={(e) => { setMax(e.target.value); setPage(1); }}
                />
              </div>
              <div className="flex justify-end pt-1">
                <Button
                  size="xs"
                  variant="ghost"
                  onClick={() => {
                    setMin("");
                    setMax("");
                    setPage(1);
                  }}
                >
                  Clear
                </Button>
              </div>
            </div>
          </div>
          </Card>
        </div>

        {/* Content */}
        <div className="lg:col-span-3 space-y-4">
          {loading && (
            <PageState variant="loading" title="Loading products" description="Fetching catalogue…" />
          )}
          {!loading && error && (
            <PageState variant={/\b403\b/.test(error)? 'forbidden':'error'} title={/\b403\b/.test(error)? 'Access denied' : 'Failed to load products'} description={error} />
          )}
          {view === "grid" ? (
            <div>
              {!loading && visible.length === 0 ? (
                <div className="text-sm text-muted-foreground">No products found.</div>) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                  {visible.map((p) => (
                    <ProductCard
                      key={p.id}
                      product={p}
                      liked={isLiked(p.id)}
                      onToggleWishlist={() => toggleWishlist(p)}
                      onAddToCart={() => { add(p,1); show(`Added ${p.name} to cart`); }}
                      onClick={() => router.push(`/buyer/products/${p.id}`)}
                    />
                  ))}
                </div>
              )}
            </div>
          ) : (
            <Card className="p-0">
            <div className="p-4">
              {!loading && visible.length === 0 && (
                <div className="text-sm text-muted-foreground">No products found.</div>
              )}
              <Table>
                  <Thead>
                    <Tr>
                      <Th>ID</Th>
                      <Th>Name</Th>
                      <Th>Category</Th>
                      <Th className="text-right">Price</Th>
                      <Th className="text-right">Actions</Th>
                    </Tr>
                  </Thead>
                  <Tbody>
                    {visible.map((p) => (
                      <Tr key={p.id}>
                        <Td>{p.id}</Td>
                        <Td className="font-medium">
                          <a className="underline" href={`/buyer/products/${p.id}`}>{p.name}</a>
                        </Td>
                        <Td>{categoryName(p) || "—"}</Td>
                        <Td className="text-right">₹ {Number(p.price).toFixed(2)}</Td>
                        <Td className="text-right">
                          <Button
                            disabled={loading}
                            onClick={() => {
                              // eslint-disable-next-line no-console
                              console.log('Add to Cart clicked', p);
                              add(p, 1);
                              show(`Added ${p.name} to cart`);
                            }}
                            variant="primary"
                            size="sm"
                          >
                            Add to Cart
                          </Button>
                        </Td>
                      </Tr>
                    ))}
                  </Tbody>
                </Table>
              </div>
            </Card>
          )}

          {/* Pagination */}
          <div className="flex items-center justify-center gap-3 pt-4">
            <div className="text-xs text-muted-foreground">
              Page {pagination.current_page} of {pagination.last_page}
            </div>
            <div className="flex items-center gap-2">
              <Button
                size="sm"
                variant="outline"
                disabled={pagination.current_page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                Previous
              </Button>
              <Button
                size="sm"
                variant="outline"
                disabled={pagination.current_page >= pagination.last_page}
                onClick={() => setPage((p) => Math.min(pagination.last_page, p + 1))}
              >
                Next
              </Button>
            </div>
          </div>
        </div>
      </div>

      {message && (
        <div className="text-sm text-foreground">{message}</div>
      )}
    </div>
  );
}
