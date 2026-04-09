"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { apiFetch } from "@/lib/api";
import { useCart } from "@/lib/cart-context";
import { useWishlist } from "@/lib/wishlist";
import type { Product } from "@/types";
import { Button } from "@/components/ui/button";
import { Heart, ShoppingCart } from "lucide-react";
import { useToast } from "@/lib/toast";

export default function ProductDetail() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const { add } = useCart();
  const { isLiked, toggleProduct: toggleWishlist } = useWishlist();
  const { show } = useToast();
  const [product, setProduct] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;
    const id = Number(params?.id);
    if (!id || Number.isNaN(id)) {
      setError("Invalid product id");
      setLoading(false);
      return;
    }
    apiFetch(`/api/products/${id}`)
      .then((res) => {
        // eslint-disable-next-line no-console
        console.log("/api/products/:id response:", res);
        if (mounted) setProduct(res as Product);
      })
      .catch((e: any) => {
        // eslint-disable-next-line no-console
        console.error("Failed to load product:", e);
        if (mounted) setError(e?.message || "Failed to load product");
      })
      .finally(() => mounted && setLoading(false));
    return () => { mounted = false; };
  }, [params?.id]);

  if (loading) return <div className="p-6">Loading product…</div>;
  if (error) return <div className="p-6 text-sm text-red-600">{error}</div>;
  if (!product) return <div className="p-6">Product not found.</div>;

  const categoryName = (() => {
    const c: any = (product as any).category;
    if (c && typeof c === "object" && "name" in c) return String(c.name || "—");
    const rel: any = (product as any).categoryRelation;
    if (rel && typeof rel === 'object' && 'name' in rel) return String(rel.name || '—');
    return String(c || "—");
  })();

  const desc = (product.description && String(product.description).trim()) || "This is a sample product description";
  const liked = isLiked(product.id);

  return (
    <div className="space-y-6">
      <button className="underline text-sm" onClick={() => router.back()}>&larr; Back</button>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Left: Image */}
        <div className="border p-2 md:col-span-1">
          <img
            src={product.image_url || "https://via.placeholder.com/300"}
            alt={product.name}
            className="w-full h-auto object-contain"
          />
        </div>
        {/* Right: Details */}
        <div className="md:col-span-2 space-y-4">
          <div className="flex items-start justify-between">
            <h1 className="text-2xl font-semibold tracking-tight mr-4">{product.name}</h1>
            <button
              className={`p-1 rounded hover:bg-muted ${liked ? 'text-red-500' : 'text-muted-foreground'}`}
              aria-label="Wishlist"
              onClick={() => toggleWishlist(product)}
            >
              <Heart className="size-5" fill={liked ? 'currentColor' : 'none'} />
            </button>
          </div>
          <div className="text-sm text-muted-foreground">Category: {categoryName}</div>
          <div>
            <div className="text-sm text-muted-foreground">Price</div>
            <div className="text-3xl font-semibold">₹ {Number(product.price).toFixed(2)}</div>
          </div>
          <div className="text-sm leading-relaxed whitespace-pre-line">{desc}</div>
          <div className="flex items-center gap-3">
            <Button onClick={() => { add(product, 1); show(`Added ${product.name} to cart`); }}>
              <ShoppingCart className="mr-1.5" /> Add to Cart
            </Button>
            <Button variant="outline" onClick={() => toggleWishlist(product)}>
              {liked ? 'Remove from Wishlist' : 'Add to Wishlist'}
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}
