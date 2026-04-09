"use client";

import { Button } from "@/components/ui/button";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/table";
import { apiFetch } from "@/lib/api";
import { useCart } from "@/lib/cart-context";
import { useWishlist } from "@/lib/wishlist";
import type { Address } from "@/types";
import { useEffect, useRef, useState } from "react";
import { Spinner } from "@/components/ui/spinner";
import { useRouter } from "next/navigation";

export default function BuyerCart() {
  const { cart, setQty, remove, clear, total } = useCart();
  const { toggleProduct } = useWishlist();
  const router = useRouter();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [addressId, setAddressId] = useState<number | null>(null);

  useEffect(() => {
    apiFetch("/api/addresses")
      .then((res) => {
        const list: Address[] = Array.isArray(res) ? res : [];
        setAddresses(list);
        if (list.length > 0) setAddressId(list[0].id);
      })
      .catch(() => {/* ignore */});
  }, []);

  const onPlaceOrder = async () => {
    setSubmitting(true);
    setError(null);
    try {
      const items = cart.map((l) => ({ product_id: l.product.id, qty: l.qty }));
      if (items.length === 0) {
        setError("Your cart is empty.");
        setSubmitting(false);
        return;
      }
      if (!addressId) {
        setError("Please select a delivery address.");
        setSubmitting(false);
        return;
      }
      const res = await apiFetch("/api/orders", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ items, address_id: addressId }),
      });
      clear();
      router.replace("/buyer/dashboard");
    } catch (e: any) {
      setError(e?.message || "Failed to place order");
    } finally {
      setSubmitting(false);
    }
  };

  const cartTotal = total;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Cart</h1>
        <p className="text-sm text-muted-foreground">Review items before placing your order.</p>
      </div>
      <div className="grid md:grid-cols-[1fr_360px] gap-6 items-start">
        {/* Left: Items list */}
        <div className="space-y-3">
          {cart.length === 0 ? (
            <div className="flex flex-col items-center justify-center rounded-[12px] border bg-white p-8 text-center text-sm text-muted-foreground">
              Your cart is empty.
            </div>
          ) : (
            <Table>
              <Thead>
                <Tr>
                  <Th>Product</Th>
                  <Th className="text-right">Price</Th>
                  <Th className="text-right">Qty</Th>
                  <Th className="text-right">Subtotal</Th>
                  <Th className="text-right">Actions</Th>
                </Tr>
              </Thead>
              <Tbody>
                {cart.map((l) => (
                  <Tr key={l.product.id}>
                    <Td className="font-medium">
                      <div className="flex items-center gap-3">
                        <img src={l.product.image_url || 'https://via.placeholder.com/48'} alt="" className="size-10 rounded object-cover" />
                        <div className="leading-tight">
                          <div className="font-medium text-sm">{l.product.name}</div>
                          <div className="text-xs text-muted-foreground">SKU #{l.product.id}</div>
                        </div>
                      </div>
                    </Td>
                    <Td className="text-right">₹ {Number(l.product.price).toFixed(2)}</Td>
                    <Td className="text-right">
                      <div className="inline-flex items-center gap-1">
                        <Button size="xs" variant="outline" onClick={() => setQty(l.product.id, Math.max(1, l.qty - 1))}>-</Button>
                        <QtyInput value={l.qty} min={1} max={9999} onCommit={(n) => setQty(l.product.id, n)} />
                        <Button size="xs" variant="outline" onClick={() => setQty(l.product.id, Math.min(9999, l.qty + 1))}>+</Button>
                      </div>
                    </Td>
                    <Td className="text-right">₹ {(Number(l.product.price) * l.qty).toFixed(2)}</Td>
                    <Td className="text-right space-x-2">
                      <Button size="xs" variant="ghost" onClick={() => { toggleProduct(l.product as any); remove(l.product.id); }}>Move to Wishlist</Button>
                      <Button size="xs" variant="outline" onClick={() => remove(l.product.id)}>Remove</Button>
                    </Td>
                  </Tr>
                ))}
              </Tbody>
            </Table>
          )}
        </div>

        {/* Right: Summary */}
        <aside className="space-y-4 sticky top-16">
          <div className="text-sm">
            <div className="flex items-center justify-between">
              <div className="font-medium">Delivery Address</div>
              <a href="/buyer/addresses" className="underline">Manage</a>
            </div>
            
            {addresses.length === 0 ? (
              <div className="text-muted-foreground">No addresses found. Please add one.</div>
            ) : (
              <div className="mt-2 space-y-2">
                {addresses.map((a) => (
                  <label key={a.id} className="flex items-center gap-2">
                    <input type="radio" name="address" checked={addressId === a.id} onChange={() => setAddressId(a.id)} />
                    <span>{a.name} — {a.phone} — {a.address_line}, {a.city}</span>
                  </label>
                ))}
              </div>
            )}
          </div>
          <div className="border rounded p-4 bg-white space-y-2 shadow-card">
            <div className="flex items-center justify-between text-sm">
              <span>Subtotal</span>
              <span>₹ {total.toFixed(2)}</span>
            </div>
            <div className="flex items-center justify-between text-sm text-muted-foreground">
              <span>Shipping</span>
              <span>—</span>
            </div>
            <div className="pt-2 flex items-center justify-between text-base font-semibold">
              <span>Total</span>
              <span>₹ {total.toFixed(2)}</span>
            </div>
            {error && <div className="text-xs text-red-600">{error}</div>}
            <Button
              variant="primary"
              className="w-full disabled:bg-muted disabled:text-muted-foreground disabled:hover:bg-muted disabled:hover:text-muted-foreground"
              onClick={onPlaceOrder}
              disabled={submitting || !addressId || cart.length === 0}
            >
              {submitting ? (
                <span className="inline-flex items-center justify-center gap-2">
                  <Spinner size={16} thickness={2} className="text-white" />
                  Placing…
                </span>
              ) : (
                "Place Order"
              )}
            </Button>
          </div>
        </aside>
      </div>
      {/* Single, clean layout: one items section, one summary panel */}
    </div>
  );
}

function QtyInput({ value, min = 1, max = 9999, onCommit }: { value: number; min?: number; max?: number; onCommit: (n: number) => void }) {
  const [text, setText] = useState<string>(String(value));
  const [focused, setFocused] = useState(false);
  const ref = useRef<HTMLInputElement | null>(null);

  // Sync with external changes when not editing
  useEffect(() => {
    if (!focused) {
      const id = window.setTimeout(() => setText(String(value)), 0);
      return () => window.clearTimeout(id);
    }
  }, [value, focused]);

  const commit = () => {
    const digits = (text || '').replace(/[^0-9]/g, '');
    const n = Math.min(max, Math.max(min, parseInt(digits || String(min))));
    setText(String(n));
    onCommit(n);
  };

  return (
    <input
      ref={ref}
      type="text"
      inputMode="numeric"
      pattern="[0-9]*"
      className="w-16 border px-2 py-1 text-right"
      value={text}
      onFocus={(e) => {
        setFocused(true);
        // select all so first key replaces existing value
        requestAnimationFrame(() => {
          try { e.currentTarget.select(); } catch {}
        });
      }}
      onBlur={() => { setFocused(false); commit(); }}
      onChange={(e) => {
        const raw = e.target.value;
        // Allow empty while typing; only digits otherwise
        const cleaned = raw === '' ? '' : raw.replace(/[^0-9]/g, '');
        // Optional: limit length to 4 digits
        const limited = cleaned.slice(0, 4);
        setText(limited);
      }}
      onKeyDown={(e) => {
        if (e.key === 'Enter') { e.preventDefault(); (e.target as HTMLInputElement).blur(); }
      }}
    />
  );
}
