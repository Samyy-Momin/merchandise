"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { initKeycloak } from "@/lib/keycloak";
import { apiFetch } from "@/lib/api";
import { useAuthStore } from "@/lib/stores/auth-store";
import { useUIStore } from "@/lib/stores/ui-store";
import { Sidebar, type NavItem } from "./sidebar";
import { Navbar } from "./navbar";
import type { Workspace } from "@/types/auth";
import { getJwtRoles, rolesSatisfyWorkspace, rolesToWorkspaces } from "@/lib/auth/roles";
import { clearWorkspaceCookie, getWorkspaceCookie, setWorkspaceCookie } from "@/lib/workspace-cookie";
import { fetchProfileCompanies } from "@/lib/auth/profile-client";
import { getCompanyCookie, setCompanyCookie, clearCompanyCookie } from "@/lib/company-cookie";
import type { CompaniesByWorkspace, CompanySummary } from "@/types/sso";
import { CompanySwitcher } from "@/components/layout/company-switcher";

type AppShellProps = {
  /** Required role(s) to access this shell */
  requiredRoles: string[];
  /** Workspace that this shell represents */
  workspace: Workspace;
  /** Navigation items for the sidebar */
  navItems: NavItem[];
  /** Title shown in navbar */
  title?: string;
  /** Extra navbar actions (right side, e.g. cart/wishlist icons) */
  navActions?: React.ReactNode;
  /** Extra navbar center content (e.g. search bar) */
  navCenter?: React.ReactNode;
  /** Extra sidebar footer (above logout) */
  sidebarFooter?: React.ReactNode;
  /** Additional providers to wrap children (e.g. CartProvider for buyer) */
  providers?: React.ComponentType<{ children: React.ReactNode }>[];
  children: React.ReactNode;
};

export function AppShell({
  requiredRoles,
  workspace,
  navItems,
  title,
  navActions,
  navCenter,
  sidebarFooter,
  providers = [],
  children,
}: AppShellProps) {
  const router = useRouter();
  const [ok, setOk] = useState(false);
  const [guardMsg, setGuardMsg] = useState<string | null>(null);
  const [companies, setCompanies] = useState<CompaniesByWorkspace | null>(null);
  const { setUser, setRoles, setAuthenticated, setHydrated } = useAuthStore();
  const collapsed = useUIStore((s) => s.sidebarCollapsed);

  useEffect(() => {
    let mounted = true;
    initKeycloak(true).then(async () => {
      if (!mounted) return;

      const jwtRoles = getJwtRoles();
      setRoles(jwtRoles);

      // Compute available workspaces for the user
      // Selection is handled on / and /select-workspace. Here, if workspace cookie is missing, set it to the current route workspace.

      // Workspace persistence & auto-selection
      const cookieWs = getWorkspaceCookie();
      if (!cookieWs) setWorkspaceCookie(workspace);

      // If a cookie exists but points to a different workspace, and user is allowed for this route's workspace,
      // align the cookie with the route
      const currentCookie = getWorkspaceCookie();
      if (currentCookie && currentCookie !== workspace && rolesSatisfyWorkspace(workspace, jwtRoles)) {
        setWorkspaceCookie(workspace);
      }

      // Role guard for this workspace (admin/super_admin bypass allowed by rolesSatisfyWorkspace)
      if (!rolesSatisfyWorkspace(workspace, jwtRoles)) {
        // Invalid cookie for this route → clear it to avoid loops
        clearWorkspaceCookie();
        router.replace("/access-denied");
        return;
      }

      // Load company lists for header and guards (skip buyer marketplace)
      try {
        if (workspace !== "buyer") {
          const comp = await fetchProfileCompanies();
          setCompanies(comp);
          const list = comp[workspace] || [];

        // Validate company selection per workspace
        {
          const selected = getCompanyCookie(workspace);
          if (!selected) {
            if (list.length === 0) {
              // No companies → show empty state
              setGuardMsg(`No companies available for the ${workspace} workspace.`);
              setOk(false);
              setAuthenticated(true);
              setHydrated(true);
              setUser(null);
              return;
            }
            if (list.length === 1) {
              setCompanyCookie(workspace, list[0].companykey);
            } else {
              router.replace(`/select-company?workspace=${workspace}`);
              return;
            }
          } else {
            // Ensure selected is valid for this workspace
            const valid = list.some((c) => c.companykey === selected);
            if (!valid) {
              clearCompanyCookie(workspace);
              if (list.length <= 1) {
                if (list.length === 1) setCompanyCookie(workspace, list[0].companykey);
              } else {
                router.replace(`/select-company?workspace=${workspace}`);
                return;
              }
            }
          }
        }
        }
      } catch {
        // Profile fetch failed; allow route but no company switcher
      }

      setOk(true);
      setAuthenticated(true);
      setHydrated(true);
      try {
        const me = await apiFetch("/api/me");
        if (mounted) {
          setUser(me?.user ?? null);
        }
      } catch {
        /* ignore */
      }
      // eslint-disable-next-line no-console
      console.log("[AppShell] workspace", workspace, {
        jwtRoles,
        cookieWorkspace: getWorkspaceCookie(),
        selectedCompany: getCompanyCookie(workspace),
        companiesCount: companies ? (companies[workspace] || []).length : "n/a",
      });
    });

    return () => {
      mounted = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [router]);

  if (!ok) {
    if (guardMsg) {
      return (
        <div className="min-h-screen flex items-center justify-center p-6">
          <div className="max-w-lg text-center space-y-3">
            <h1 className="text-2xl font-semibold">Company selection required</h1>
            <p className="text-sm text-muted-foreground">{guardMsg}</p>
            <div className="text-sm text-muted-foreground">Use the workspace/company selection screens to proceed.</div>
          </div>
        </div>
      );
    }
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-sm text-muted-foreground animate-pulse">Checking access…</div>
      </div>
    );
  }

  // Wrap the ENTIRE shell with providers so navActions (cart/wishlist icons) can access context
  let actionsCombined = navActions;
  let companiesForWs: CompanySummary[] | null = companies ? companies[workspace] || [] : null;
  const selectedKey = getCompanyCookie(workspace);
  if (workspace !== "buyer" && Array.isArray(companiesForWs) && companiesForWs.length > 0) {
    try {
      actionsCombined = (
        <div className="flex items-center gap-2">
          {navActions}
          <CompanySwitcher workspace={workspace} companies={companiesForWs} selectedKey={selectedKey} />
        </div>
      );
    } catch {}
  }

  let shell = (
    <div className="min-h-screen bg-background text-foreground">
      <Sidebar title="Merchandise" navItems={navItems} footer={sidebarFooter} />
      <div
        className={`transition-all duration-200 ${collapsed ? "md:ml-[64px]" : "md:ml-[240px]"}`}
      >
        <Navbar title={title} actions={actionsCombined} center={navCenter} />
        <main className="max-w-[1400px] mx-auto w-full px-6 py-6">{children}</main>
      </div>
    </div>
  );

  // Wrap outermost so that navActions, sidebarFooter, and children all share the same context
  for (let i = providers.length - 1; i >= 0; i--) {
    const Provider = providers[i];
    shell = <Provider>{shell}</Provider>;
  }

  return shell;
}
