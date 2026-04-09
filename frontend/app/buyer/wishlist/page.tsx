"use client";

import { useEffect, useMemo, useState } from "react";
import { useWishlist } from "@/lib/wishlist";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Heart, ShoppingCart } from "lucide-react";
import { useCart } from "@/lib/cart-context";
import { useRouter } from "next/navigation";

export default function WishlistPage() {
  const { items, isLiked, toggleProduct, remove, clear } = useWishlist();
  const { add } = useCart();
  const router = useRouter();
  const products = items; // already structured and persisted

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Wishlist</h1>
        <p className="text-sm text-muted-foreground">Your saved items.</p>
      </div>
      {products.length === 0 ? (
        <div className="text-sm text-muted-foreground">No items in wishlist.</div>
      ) : (
        <>
        <div className="flex justify-end">
          <Button variant="outline" size="sm" onClick={() => clear()}>Clear all</Button>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {products.map((p) => {
            const liked = isLiked(p.id);
            const desc = "This is a sample product description";
            return (
              <Card
                key={p.id}
                className="p-0 overflow-hidden hover:shadow-md transition cursor-pointer"
                onClick={() => router.push(`/buyer/products/${p.id}`)}
              >
                <img
                  src={p.image || "https://via.placeholder.com/300"}
                  alt={p.name}
                  className="w-full h-36 object-cover"
                />
                <div className="p-3 space-y-2">
                  <div className="flex items-center justify-between">
                    <div className="text-lg font-semibold">₹ {Number(p.price).toFixed(2)}</div>
                    <div className="flex items-center gap-2">
                      <button
                        className={`p-1 rounded hover:bg-muted ${liked ? 'text-red-500' : 'text-muted-foreground'}`}
                        aria-label="Wishlist"
                        onClick={(e) => { e.stopPropagation(); toggleProduct(p as any); }}
                      >
                        <Heart className="size-4" fill={liked ? 'currentColor' : 'none'} />
                      </button>
                      <button
                        className="p-1 rounded hover:bg-muted text-foreground"
                        aria-label="Add to Cart"
                        onClick={(e) => { e.stopPropagation(); add(p as any, 1); }}
                      >
                        <ShoppingCart className="size-4" />
                      </button>
                    </div>
                  </div>
                  <div className="font-medium leading-snug">{p.name}</div>
                  <div className="text-xs text-muted-foreground truncate">{desc}</div>
                </div>
              </Card>
            );
          })}
        </div>
        </>
      )}
    </div>
  );
}
