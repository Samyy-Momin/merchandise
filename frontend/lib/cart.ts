"use client";

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

export function getCart(): Cart {
  return read();
}

export function addToCart(product: Product, qty = 1) {
  const cart = read();
  const idx = cart.findIndex((l) => l.product.id === product.id);
  if (idx >= 0) cart[idx].qty += qty;
  else cart.push({ product, qty });
  write(cart);
}

export function updateQty(productId: number, qty: number) {
  const cart = read();
  const line = cart.find((l) => l.product.id === productId);
  if (line) line.qty = qty;
  write(cart);
}

export function removeItem(productId: number) {
  const cart = read().filter((l) => l.product.id !== productId);
  write(cart);
}

export function clearCart() {
  write([]);
}

export function total(cart: Cart): number {
  return cart.reduce((sum, l) => sum + l.product.price * l.qty, 0);
}

