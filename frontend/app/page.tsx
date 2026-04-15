"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { initKeycloak } from "@/lib/keycloak";
import { getJwtRoles, rolesToWorkspacesForSelection } from "@/lib/auth/roles";
import { getWorkspaceCookie, setWorkspaceCookie } from "@/lib/workspace-cookie";
import { WORKSPACE_PATH, type Workspace } from "@/types/auth";
import { fetchProfileCompanies } from "@/lib/auth/profile-client";

export default function Home() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    initKeycloak(true)
      .then(async () => {
        if (!mounted) return;
        const roles = getJwtRoles();
        // eslint-disable-next-line no-console
        console.log("[Home] JWT roles", roles);
        let baseWorkspaces = rolesToWorkspacesForSelection(roles);
        // eslint-disable-next-line no-console
        console.log("[Home] baseWorkspaces", baseWorkspaces);

        // Derive effective workspaces using companies (non-buyer must have companies)
        let effective: Workspace[] = baseWorkspaces;
        try {
          const companies = await fetchProfileCompanies();
          const eff: Workspace[] = [] as Workspace[];
          for (const ws of baseWorkspaces) {
            if (ws === "buyer") {
              eff.push(ws);
            } else if ((companies[ws] || []).length > 0) {
              eff.push(ws);
            }
          }
          effective = eff;
          // eslint-disable-next-line no-console
          console.log("[Home] companiesByWorkspace counts", {
            buyer: companies.buyer.length,
            approver: companies.approver.length,
            vendor: companies.vendor.length,
            admin: companies.admin.length,
          });
        } catch (e) {
          // eslint-disable-next-line no-console
          console.warn("[Home] profile companies fetch failed", e);
        }
        // eslint-disable-next-line no-console
        console.log("[Home] effectiveWorkspaces", effective);

        // If user already selected a workspace and it's still valid, go there
        const cookieWs = getWorkspaceCookie();
        if (cookieWs && effective.includes(cookieWs)) {
          router.replace(WORKSPACE_PATH[cookieWs]);
          return;
        }

        if (effective.length === 0) {
          router.replace("/access-denied");
          return;
        }
        if (effective.length === 1) {
          const only = effective[0] as Workspace;
          setWorkspaceCookie(only);
          router.replace(WORKSPACE_PATH[only]);
          return;
        }
        // Multi-role: go choose
        router.replace("/select-workspace");
      })
      .finally(() => mounted && setLoading(false));
    return () => {
      mounted = false;
    };
  }, [router]);

  return (
    <div style={{ padding: 24 }}>
      {loading ? <p>Signing you in…</p> : <p>Redirecting…</p>}
    </div>
  );
}
