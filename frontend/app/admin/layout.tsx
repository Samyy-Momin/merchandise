"use client";

import { hasAnyRole, initKeycloak, kcLogout, getStoredRoles } from "@/lib/keycloak";
import Link from "next/link";
import { useEffect, useState } from "react";
import { usePathname, useRouter } from "next/navigation";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [ok, setOk] = useState(false);

  useEffect(() => {
    let mounted = true;
    try {
      const roles = getStoredRoles();
      if (roles.includes("admin") || roles.includes("super_admin")) setOk(true);
    } catch {}
    initKeycloak(true).then(() => {
      if (!mounted) return;
      if (hasAnyRole(["admin", "super_admin"])) setOk(true);
      else router.replace("/");
    });
    return () => { mounted = false; };
  }, [router]);

  if (!ok) return <div className="p-6 text-sm">Checking access…</div>;

  const NavLink = ({ href, label }: { href: string; label: string }) => (
    <Link href={href} className={pathname?.startsWith(href) ? "font-semibold" : "hover:underline"}>{label}</Link>
  );

  return (
    <div className="min-h-screen bg-[#f1f5f9] text-[#0f172a]">
      <aside className="hidden md:flex fixed left-0 top-0 h-screen w-[240px] overflow-hidden bg-[#1e3a8a] text-white p-4">
        <nav className="flex flex-col text-sm w-full space-y-1">
          <div className="text-base font-semibold tracking-tight mb-3">Merchandise</div>
          <Link href="/admin/dashboard" className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/admin/dashboard')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`}>Dashboard</Link>
          <Link href="/admin/orders" className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/admin/orders')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`}>Orders</Link>
          <Link href="/admin/vendor-orders" className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/admin/vendor-orders')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`}>Vendor Orders</Link>
          <Link href="/admin/products" className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/admin/products')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`}>Products</Link>
          <Link href="/admin/categories" className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/admin/categories')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`}>Categories</Link>
          <button onClick={() => kcLogout()} className="text-left text-xs underline mt-4">Logout</button>
        </nav>
      </aside>
      <div className="ml-[240px]">
        <header className="bg-white border-b border-[#e2e8f0] sticky top-0 z-20">
          <div className="max-w-[1400px] mx-auto px-6 h-14 flex items-center">Admin</div>
        </header>
        <main className="max-w-[1400px] mx-auto w-full px-6 py-6">{children}</main>
      </div>
    </div>
  );
}
