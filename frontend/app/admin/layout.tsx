"use client";

import { AppShell } from "@/components/layout/app-shell";
import type { NavItem } from "@/components/layout/sidebar";
import { LayoutDashboard, ShoppingCart, Truck, Package, FolderTree, BarChart3, Activity } from "lucide-react";

const navItems: NavItem[] = [
  { href: "/admin/dashboard", label: "Dashboard", icon: LayoutDashboard },
  { href: "/admin/orders", label: "Orders", icon: ShoppingCart },
  { href: "/admin/vendor-orders", label: "Vendor Orders", icon: Truck },
  { href: "/admin/products", label: "Products", icon: Package },
  { href: "/admin/categories", label: "Categories", icon: FolderTree },
  { href: "/admin/reports", label: "Reports", icon: BarChart3 },
  { href: "/admin/monitoring", label: "Monitoring", icon: Activity },
];

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  return (
    <AppShell workspace="admin" requiredRoles={["admin", "super_admin"]} navItems={navItems} title="Admin">
      {children}
    </AppShell>
  );
}
