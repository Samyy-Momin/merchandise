"use client";

import { hasAnyRole, initKeycloak, kcLogout, getStoredRoles } from "@/lib/keycloak";
import { useRouter, usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import Link from "next/link";
import { User } from "lucide-react";

export default function VendorLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [ok, setOk] = useState<boolean>(false);

  useEffect(() => {
    let mounted = true;
    try { const roles = getStoredRoles(); if (roles.includes('vendor')) setOk(true); } catch {}
    initKeycloak(true).then(() => {
      if (!mounted) return;
      if (hasAnyRole(["vendor"])) setOk(true);
      else router.replace("/");
    });
    return () => { mounted = false; };
  }, [router]);

  if (!ok) return <div className="p-6 text-sm">Checking access…</div>;

  return (
    <div className="min-h-screen bg-[#f1f5f9] text-[#0f172a]">
      <aside className="hidden md:flex fixed left-0 top-0 h-screen w-[240px] overflow-hidden bg-[#1e3a8a] text-white p-4">
        <nav className="flex flex-col text-sm w-full">
          <div className="text-base font-semibold tracking-tight mb-3">Merchandise</div>
          <Link className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/vendor/dashboard')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`} href="/vendor/dashboard">Dashboard</Link>
          <Link className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/vendor/orders')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`} href="/vendor/orders">Orders</Link>
          <Link className={`px-3 py-2 rounded transition-colors ${pathname?.startsWith('/vendor/invoices')?'bg-[#2563eb] text-white':'text-white/70 hover:bg-[#1d4ed8] hover:text-white'}`} href="/vendor/invoices">Invoices</Link>
        </nav>
      </aside>
      <div className="ml-[240px]">
        <header className="border-b bg-white sticky top-0 z-20">
          <div className="max-w-[1400px] mx-auto px-6 h-14 grid grid-cols-[1fr_auto] items-center gap-4">
            <div className="text-base font-semibold tracking-tight">Vendor</div>
            <div className="flex items-center justify-end gap-4 text-sm">
              <div className="hidden md:flex items-center gap-2 text-[#0f172a] opacity-80">
                <User className="size-6" />
                <span>Vendor</span>
              </div>
              <button onClick={() => kcLogout()} className="underline text-[#261CC1]">Logout</button>
            </div>
          </div>
        </header>
        <main className="max-w-[1400px] w-full px-6 py-6">{children}</main>
      </div>
    </div>
  );
}
