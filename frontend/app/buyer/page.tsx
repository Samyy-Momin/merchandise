"use client";

import { useEffect, useState } from "react";
import { apiFetch, getCategoriesCached } from "@/lib/api";
import type { Category } from "@/types";
import { useRouter } from "next/navigation";
import { Card, CardContent } from "@/components/ui/card";
import { CardGridSkeleton } from "@/components/ui/page-skeleton";

export default function BuyerHome() {
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;
    getCategoriesCached()
      .then((list) => {
        if (!mounted) return;
        setCategories(list);
      })
      .catch((e: any) => {
        if (!mounted) return;
        setError(e?.message || "Failed to load categories");
      })
      .finally(() => mounted && setLoading(false));
    return () => { mounted = false; };
  }, []);

  if (loading) return <CardGridSkeleton count={8} />;
  if (error) return <div className="text-sm text-red-600">{error}</div>;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Merchandise Portal</h1>
        <p className="text-sm text-muted-foreground">Choose a category to browse products.</p>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        {categories.map((c) => (
          <Card
            key={c.id}
            className="p-0 cursor-pointer transition hover:shadow-md hover:-translate-y-0.5"
            onClick={() => router.push(`/buyer/skus?category=${c.id}`)}
          >
            <img
              src="https://via.placeholder.com/300"
              alt={c.name}
              className="w-full h-32 object-cover"
            />
            <CardContent className="py-3">
              <div className="font-medium text-sm">{c.name}</div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
