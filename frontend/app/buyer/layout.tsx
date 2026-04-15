"use client";

import { AppShell } from "@/components/layout/app-shell";
import type { NavItem } from "@/components/layout/sidebar";
import { CartProvider, useCart } from "@/lib/cart-context";
import { WishlistProvider, useWishlist } from "@/lib/wishlist";
import { ToastProvider } from "@/lib/toast";
import { Heart, ShoppingCart, LayoutDashboard, Package, Grid3X3, FileText, User } from "lucide-react";
import Link from "next/link";
import { Input } from "@/components/ui/input";
import { Search } from "lucide-react";
import { useRouter } from "next/navigation";

const navItems: NavItem[] = [
  { href: "/buyer/dashboard", label: "Dashboard", icon: LayoutDashboard },
  { href: "/buyer/skus", label: "Products", icon: Package },
  { href: "/buyer", label: "Categories", icon: Grid3X3 },
  { href: "/buyer/invoices", label: "Orders", icon: FileText },
  { href: "/buyer/cart", label: "Cart", icon: ShoppingCart },
  { href: "/buyer/wishlist", label: "Wishlist", icon: Heart },
  { href: "/buyer/addresses", label: "Profile", icon: User },
];

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

function BuyerNavActions() {
  return (
    <>
      <NavWishlistIcon />
      <NavCartIcon />
    </>
  );
}

function BuyerSearchBar() {
  const router = useRouter();
  return (
    <div className="flex items-center gap-2 w-full">
      <Search className="size-5 text-primary shrink-0" />
      <Input
        placeholder="Search products..."
        className="h-9"
        onKeyDown={(e) => {
          if (e.key === "Enter") {
            const v = (e.target as HTMLInputElement).value;
            const term = v.trim();
            if (!term) return router.push("/buyer/skus");
            router.push(`/buyer/skus?search=${encodeURIComponent(term)}`);
          }
        }}
      />
    </div>
  );
}

export default function BuyerLayout({ children }: { children: React.ReactNode }) {
  return (
    <AppShell
      workspace="buyer"
      requiredRoles={["buyer"]}
      navItems={navItems}
      title="Buyer"
      providers={[CartProvider, WishlistProvider, ToastProvider]}
      navActions={<BuyerNavActions />}
      navCenter={<BuyerSearchBar />}
    >
      {children}
    </AppShell>
  );
}
