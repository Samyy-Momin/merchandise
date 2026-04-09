"use client";

import type { Product } from "@/types";
import { Heart, ShoppingCart } from "lucide-react";

export type ProductCardProps = {
  product: Product;
  liked?: boolean;
  onToggleWishlist?: (p: Product) => void;
  onAddToCart?: (p: Product) => void;
  onClick?: (p: Product) => void;
  className?: string;
};

export function ProductCard({ product: p, liked, onToggleWishlist, onAddToCart, onClick, className }: ProductCardProps) {
  const categoryName = (() => {
    const c: any = (p as any).category;
    if (c && typeof c === "object" && "name" in c) return String(c.name || "Uncategorized");
    return String(c || "Uncategorized");
  })();
  const desc = (p.description && String(p.description).trim()) || "This is a sample product description";

  return (
    <div
      className={`p-0 overflow-hidden shadow-card hover:shadow-card-hover hover:-translate-y-1 transition cursor-pointer bg-white border rounded-[12px] h-full flex flex-col ${className||''}`}
      onClick={() => onClick?.(p)}
    >
      <div className="w-full h-44 bg-white flex items-center justify-center overflow-hidden">
        <img src={p.image_url || "https://via.placeholder.com/300"} alt={p.name} className="max-h-full max-w-full object-contain" />
      </div>
      <div className="p-4 space-y-2 flex-1">
        <div className="flex items-center justify-between">
          <div className="text-[18px] font-bold">₹ {Number(p.price).toFixed(2)}</div>
          <div className="flex items-center gap-2">
            <button
              className={`p-1 rounded hover:bg-[#BDE8F5] hover:text-black transition ${liked ? 'text-red-500' : 'text-muted-foreground'}`}
              aria-label="Wishlist"
              onClick={(e) => { e.stopPropagation(); onToggleWishlist?.(p); }}
            >
              <Heart className="size-4" fill={liked ? 'currentColor' : 'none'} />
            </button>
            <button
              className="p-1 rounded hover:bg-[#BDE8F5] hover:text-black text-foreground transition"
              aria-label="Add to Cart"
              onClick={(e) => { e.stopPropagation(); onAddToCart?.(p); }}
            >
              <ShoppingCart className="size-4" />
            </button>
          </div>
        </div>
        <div className="text-[16px] font-semibold leading-snug">{p.name}</div>
        <div className="text-[12px] text-muted-foreground">{categoryName}</div>
        <div className="text-[12px] text-muted-foreground truncate">{desc}</div>
      </div>
    </div>
  );
}
