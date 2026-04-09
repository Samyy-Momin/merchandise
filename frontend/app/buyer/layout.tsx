"use client";

import { hasAnyRole, initKeycloak, kcLogout, getStoredRoles } from "@/lib/keycloak";
import { apiFetch } from "@/lib/api";
import { useRouter, usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import Link from "next/link";
import { CartProvider, useCart } from "@/lib/cart-context";
import { WishlistProvider, useWishlist } from "@/lib/wishlist";
import { Heart, ShoppingCart, User, Search } from "lucide-react";
import { ToastProvider } from "@/lib/toast";
import { Input } from "@/components/ui/input";

function NavCartIcon() {
  const { count, total } = useCart();
  return (
    <Link href="/buyer/cart" className="inline-flex items-center gap-2 justify-center text-sm">
      <span className="relative inline-flex">
        <ShoppingCart className="size-5" />
        {count > 0 && (
          <span className="absolute -top-1 -right-1 bg-primary text-primary-foreground text-[10px] leading-none px-1.5 py-0.5 rounded-full">
            {count}
          </span>
        )}
      </span>
      <span className="hidden sm:inline">Cart ₹{Number(total).toFixed(0)}</span>
    </Link>
  );
}

function NavWishlistIcon() {
  const { liked } = useWishlist();
  const count = liked.size;
  return (
    <Link href="/buyer/wishlist" className="inline-flex items-center gap-2 justify-center text-sm">
      <Heart className="size-5" />
      <span className="hidden sm:inline">Wishlist ({count})</span>
    </Link>
  );
}

function Shell({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [ok, setOk] = useState<boolean>(false);
  const [user, setUser] = useState<any>(null);

  useEffect(() => {
    let mounted = true;
    // Fast path using stored token roles so back navigation doesn't flash
    // Fast check from stored token roles; set state after mount to avoid hydration mismatch
    try { const roles = getStoredRoles(); if (roles.includes('buyer')) setOk(true); } catch {}
    initKeycloak(true).then(async () => {
      if (!mounted) return;
      if (hasAnyRole(["buyer"])) {
        setOk(true);
        try {
          const me = await apiFetch("/api/me");
          setUser(me?.user ?? null);
        } catch { /* ignore */ }
      } else router.replace("/");
    });
    return () => {
      mounted = false;
    };
  }, [router]);

  if (!ok) return <div className="p-6 text-sm">Checking access…</div>;

  return (
    <div className="min-h-screen bg-[#f1f5f9] text-[#0f172a]">
      {/* Fixed Sidebar */}
      <aside className="hidden md:flex fixed left-0 top-0 h-screen w-[240px] overflow-hidden bg-[#1e3a8a] text-white p-4">
        <nav className="flex flex-col text-sm w-full">
          <div className="text-base font-semibold tracking-tight mb-3">Merchandise</div>
          <Link className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/buyer/dashboard')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`} href="/buyer/dashboard">Dashboard</Link>
          <Link className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/buyer/skus')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`} href="/buyer/skus">Products</Link>
          <Link className={`px-3 py-2 rounded transition-colors ${pathname === '/buyer'?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`} href="/buyer">Categories</Link>
          <Link className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/buyer/invoices')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`} href="/buyer/invoices">Orders</Link>
          <Link className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/buyer/cart')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`} href="/buyer/cart">Cart</Link>
          <Link className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/buyer/wishlist')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`} href="/buyer/wishlist">Wishlist</Link>
          <Link className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/buyer/addresses')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`} href="/buyer/addresses">Profile</Link>
        </nav>
      </aside>

      {/* Main area (browser scroll only) */}
      <div className="ml-[240px]">
              {/* Header */}
        <header className="bg-white border-b border-[#e2e8f0] sticky top-0 z-20">
          <div className="max-w-[1400px] mx-auto px-6 h-14 flex items-center justify-between gap-6">
            <div />
            <div className="flex-1 max-w-xl flex items-center gap-2">
              <Search className="size-5 text-[#261CC1]" />
              <Input
                placeholder="Search products..."
                className="h-9"
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    const v = (e.target as HTMLInputElement).value;
                    const term = v.trim();
                    if (!term) return router.push('/buyer/skus');
                    router.push(`/buyer/skus?search=${encodeURIComponent(term)}`);
                  }
                }}
              />
            </div>
            <div className="flex items-center justify-end gap-5 text-sm">
              <div className="hidden md:flex items-center gap-2 text-muted-foreground">
                <User className="size-6" />
                <span>Hello, {user?.preferred_username || 'Buyer'}</span>
              </div>
              <NavWishlistIcon />
              <NavCartIcon />
              <details className="relative">
                <summary className="list-none cursor-pointer flex items-center gap-2">
                  <div className="size-7 flex items-center justify-center border bg-white text-black">{user?.preferred_username?.slice(0,1)?.toUpperCase() || 'U'}</div>
                </summary>
                <div className="absolute right-0 mt-2 w-44 border bg-white p-2 text-sm">
                  <Link className="block px-2 py-1 hover:underline" href="/buyer/addresses">Profile</Link>
                  <button onClick={() => kcLogout()} className="block w-full text-left px-2 py-1 hover:underline">Logout</button>
                </div>
              </details>
            </div>
          </div>
        </header>
        <main className="max-w-[1400px] mx-auto w-full px-6 py-6">{children}</main>
      </div>
    </div>
  );
}

export default function BuyerLayout({ children }: { children: React.ReactNode }) {
  return (
    <CartProvider>
      <WishlistProvider>
        <ToastProvider>
          <Shell>{children}</Shell>
        </ToastProvider>
      </WishlistProvider>
    </CartProvider>
  );
}
