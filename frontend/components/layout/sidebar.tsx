"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import type { LucideIcon } from "lucide-react";
import { kcLogout } from "@/lib/keycloak";
import { LogOut, PanelLeftClose, PanelLeft } from "lucide-react";
import { useUIStore } from "@/lib/stores/ui-store";

export type NavItem = {
  href: string;
  label: string;
  icon?: LucideIcon;
};

type SidebarProps = {
  title?: string;
  navItems: NavItem[];
  /** Extra content below nav items (e.g. cart/wishlist for buyer) */
  footer?: React.ReactNode;
};

export function Sidebar({ title = "Merchandise", navItems, footer }: SidebarProps) {
  const pathname = usePathname();
  const collapsed = useUIStore((s) => s.sidebarCollapsed);
  const setSidebarCollapsed = useUIStore((s) => s.setSidebarCollapsed);

  return (
    <aside
      className={`hidden md:flex fixed left-0 top-0 h-screen overflow-hidden bg-sidebar text-sidebar-foreground transition-all duration-200 z-30 flex-col ${
        collapsed ? "w-[64px]" : "w-[240px]"
      }`}
    >
      {/* Header */}
      <div className={`flex items-center h-14 px-4 ${collapsed ? "justify-center" : "justify-between"}`}>
        {!collapsed && (
          <span className="text-base font-semibold tracking-tight truncate">{title}</span>
        )}
        <button
          aria-label={collapsed ? "Expand sidebar" : "Collapse sidebar"}
          onClick={() => setSidebarCollapsed(!collapsed)}
          className="p-1.5 rounded hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition-colors"
        >
          {collapsed ? <PanelLeft className="size-4" /> : <PanelLeftClose className="size-4" />}
        </button>
      </div>

      {/* Navigation */}
      <nav className="flex-1 flex flex-col text-sm px-2 space-y-0.5 overflow-y-auto">
        {navItems.map((item) => {
          const rootExactRoutes = ['/buyer', '/admin', '/vendor', '/approver'];
          const isRootRoute = rootExactRoutes.includes(item.href);
          const active = isRootRoute
            ? pathname === item.href
            : pathname?.startsWith(item.href);
          const Icon = item.icon;
          return (
            <Link
              key={item.href}
              href={item.href}
              title={collapsed ? item.label : undefined}
              className={`flex items-center gap-3 px-3 py-2 rounded transition-colors ${
                active
                  ? "bg-sidebar-primary text-sidebar-primary-foreground"
                  : "text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
              } ${collapsed ? "justify-center" : ""}`}
            >
              {Icon && <Icon className="size-4 shrink-0" />}
              {!collapsed && <span className="truncate">{item.label}</span>}
            </Link>
          );
        })}
      </nav>

      {/* Footer */}
      <div className="px-2 pb-4 space-y-1">
        {footer}
        <button
          onClick={() => kcLogout()}
          className={`flex items-center gap-3 w-full px-3 py-2 rounded text-sm transition-colors text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground ${
            collapsed ? "justify-center" : ""
          }`}
        >
          <LogOut className="size-4 shrink-0" />
          {!collapsed && <span>Logout</span>}
        </button>
      </div>
    </aside>
  );
}
