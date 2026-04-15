"use client";

import { AppShell } from "@/components/layout/app-shell";
import type { NavItem } from "@/components/layout/sidebar";
import { LayoutDashboard, ShoppingCart, FileText } from "lucide-react";

const navItems: NavItem[] = [
  { href: "/vendor/dashboard", label: "Dashboard", icon: LayoutDashboard },
  { href: "/vendor/orders", label: "Orders", icon: ShoppingCart },
  { href: "/vendor/invoices", label: "Invoices", icon: FileText },
];

export default function VendorLayout({ children }: { children: React.ReactNode }) {
  return (
    <AppShell workspace="vendor" requiredRoles={["vendor"]} navItems={navItems} title="Vendor">
      {children}
    </AppShell>
  );
}
