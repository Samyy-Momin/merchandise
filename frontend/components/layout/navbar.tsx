"use client";

import Link from "next/link";
import { useAuthStore } from "@/lib/stores/auth-store";
import { useUIStore } from "@/lib/stores/ui-store";
import { ThemeToggle } from "./theme-toggle";
import { Menu, User } from "lucide-react";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { kcLogout } from "@/lib/keycloak";

type NavbarProps = {
  /** Title shown in the header */
  title?: string;
  /** Extra right-side content (e.g. cart/wishlist icons for buyer) */
  actions?: React.ReactNode;
  /** Extra left-side content (e.g. search bar) */
  center?: React.ReactNode;
};

export function Navbar({ title, actions, center }: NavbarProps) {
  const user = useAuthStore((s) => s.user);
  const roles = useAuthStore((s) => s.roles);
  const collapsed = useUIStore((s) => s.sidebarCollapsed);
  const setSidebarOpen = useUIStore((s) => s.setSidebarOpen);

  const username = user?.preferred_username || user?.name || "User";
  const role = roles.find((r) => ["buyer", "admin", "vendor", "approver"].includes(r)) || "buyer";
  const profileHref = `/${role}${role === "buyer" ? "/addresses" : ""}`;

  return (
    <header className="bg-background border-b sticky top-0 z-20">
      <div className="max-w-[1400px] mx-auto px-6 h-14 flex items-center gap-4">
        {/* Mobile menu button */}
        <button
          className="md:hidden p-1.5 rounded hover:bg-accent transition-colors"
          aria-label="Open menu"
          onClick={() => setSidebarOpen(true)}
        >
          <Menu className="size-5" />
        </button>

        {/* Title (mobile only or when no center) */}
        {title && !center && (
          <span className="text-base font-semibold tracking-tight">{title}</span>
        )}

        {/* Center slot (e.g. search) */}
        {center && <div className="w-full max-w-xl">{center}</div>}

        {/* Spacer to push right-side controls */}
        <div className="flex-1" />

        {/* Right side */}
        <div className="flex items-center gap-3 text-sm">
          {actions}
          <ThemeToggle />
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <button className="inline-flex items-center gap-2 rounded-md border px-2.5 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground">
                <User className="size-4" />
                <span className="hidden sm:inline">{username}</span>
              </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem asChild>
                <Link href={profileHref}>Profile</Link>
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => kcLogout()}>
                Logout
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </header>
  );
}
