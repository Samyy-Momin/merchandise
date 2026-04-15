"use client";

import { AppShell } from "@/components/layout/app-shell";
import type { NavItem } from "@/components/layout/sidebar";
import { LayoutDashboard, ShoppingCart, CheckSquare, FileText } from "lucide-react";

const navItems: NavItem[] = [
  { href: "/approver/dashboard", label: "Dashboard", icon: LayoutDashboard },
  { href: "/approver/orders", label: "Orders", icon: ShoppingCart },
  { href: "/approver/decisions", label: "My Decisions", icon: CheckSquare },
  { href: "/approver/invoices", label: "Invoices", icon: FileText },
];

export default function ApproverLayout({ children }: { children: React.ReactNode }) {
  return (
    <AppShell workspace="approver" requiredRoles={["approver"]} navItems={navItems} title="Approver">
      {children}
    </AppShell>
  );
}
