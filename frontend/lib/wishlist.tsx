"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import type { Product } from "@/types";

export type WishlistItem = { id: number; name: string; price: number; image?: string | null };

type WishlistContextValue = {
  items: WishlistItem[];
  liked: Set<number>;
  isLiked(id: number): boolean;
  toggleProduct(p: Product): void;
  remove(id: number): void;
  clear(): void;
};

const KEY = "wishlist_items";
const LEGACY_KEY = "buyer_wishlist"; // migrate ids-only if present

function read(): WishlistItem[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = localStorage.getItem(KEY);
    if (raw) return JSON.parse(raw) as WishlistItem[];
  } catch {/* ignore */}
  try {
    // migrate legacy ids to structured items (best-effort minimal fields)
    const legacy = localStorage.getItem(LEGACY_KEY);
    if (legacy) {
      const ids = JSON.parse(legacy) as number[];
      return ids.map((id) => ({ id, name: `Product ${id}`, price: 0, image: null }));
    }
  } catch {/* ignore */}
  return [];
}

function write(items: WishlistItem[]) {
  if (typeof window === "undefined") return;
  localStorage.setItem(KEY, JSON.stringify(items));
}

const WishlistContext = createContext<WishlistContextValue | null>(null);

export function WishlistProvider({ children }: { children: React.ReactNode }) {
  const [items, setItems] = useState<WishlistItem[]>(() => read());

  const liked = useMemo(() => new Set(items.map((i) => i.id)), [items]);
  const isLiked = useCallback((id: number) => liked.has(id), [liked]);

  const toggleProduct = useCallback((p: Product) => {
    setItems((prev) => {
      const exists = prev.some((i) => i.id === p.id);
      const img = (p as unknown as { image_url?: string | null }).image_url ?? null;
      const next = exists
        ? prev.filter((i) => i.id !== p.id)
        : [...prev, { id: p.id, name: p.name, price: Number(p.price), image: img }];
      write(next);
      return next;
    });
  }, []);

  const remove = useCallback((id: number) => {
    setItems((prev) => {
      const next = prev.filter((i) => i.id !== id);
      write(next);
      return next;
    });
  }, []);

  const clear = useCallback(() => {
    setItems([]);
    write([]);
  }, []);

  const value = useMemo<WishlistContextValue>(() => ({ items, liked, isLiked, toggleProduct, remove, clear }), [items, liked, isLiked, toggleProduct, remove, clear]);

  return <WishlistContext.Provider value={value}>{children}</WishlistContext.Provider>;
}

export function useWishlist() {
  const ctx = useContext(WishlistContext);
  if (!ctx) throw new Error("useWishlist must be used within WishlistProvider");
  return ctx;
}
