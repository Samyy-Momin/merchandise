"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import type { Product } from "@/types";

export type CartLine = { product: Product; qty: number };
export type Cart = CartLine[];

const KEY = "buyer_cart";

function read(): Cart {
  if (typeof window === "undefined") return [];
  try {
    const raw = localStorage.getItem(KEY);
    return raw ? (JSON.parse(raw) as Cart) : [];
  } catch {
    return [];
  }
}

function write(cart: Cart) {
  if (typeof window === "undefined") return;
  localStorage.setItem(KEY, JSON.stringify(cart));
}

function calcTotal(cart: Cart): number {
  return cart.reduce((sum, l) => sum + Number(l.product.price) * l.qty, 0);
}

type CartContextValue = {
  cart: Cart;
  total: number;
  count: number;
  add(product: Product, qty?: number): void;
  setQty(productId: number, qty: number): void;
  remove(productId: number): void;
  clear(): void;
  reload(): void;
};

const CartContext = createContext<CartContextValue | null>(null);

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [cart, setCart] = useState<Cart>(() => read());

  const reload = useCallback(() => setCart(read()), []);

  useEffect(() => {
    const onStorage = (e: StorageEvent) => {
      if (e.key === KEY) reload();
    };
    window.addEventListener("storage", onStorage);
    return () => window.removeEventListener("storage", onStorage);
  }, [reload]);

  const add = useCallback((product: Product, qty = 1) => {
    // eslint-disable-next-line no-console
    console.log('[Cart] add', { productId: product.id, qty });
    setCart((prev) => {
      const next = [...prev];
      const idx = next.findIndex((l) => l.product.id === product.id);
      if (idx >= 0) next[idx].qty += qty;
      else next.push({ product, qty });
      write(next);
      return next;
    });
  }, []);

  const setQty = useCallback((productId: number, qty: number) => {
    // eslint-disable-next-line no-console
    console.log('[Cart] setQty', { productId, qty });
    setCart((prev) => {
      const next = prev.map((l) => (l.product.id === productId ? { ...l, qty: Math.max(1, Math.floor(qty || 1)) } : l));
      write(next);
      return next;
    });
  }, []);

  const remove = useCallback((productId: number) => {
    // eslint-disable-next-line no-console
    console.log('[Cart] remove', { productId });
    setCart((prev) => {
      const next = prev.filter((l) => l.product.id !== productId);
      write(next);
      return next;
    });
  }, []);

  const clear = useCallback(() => {
    // eslint-disable-next-line no-console
    console.log('[Cart] clear');
    write([]);
    setCart([]);
  }, []);

  const value = useMemo<CartContextValue>(() => ({
    cart,
    total: calcTotal(cart),
    count: cart.reduce((c, l) => c + l.qty, 0),
    add,
    setQty,
    remove,
    clear,
    reload,
  }), [cart, add, setQty, remove, clear, reload]);

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart() {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error("useCart must be used within CartProvider");
  return ctx;
}
