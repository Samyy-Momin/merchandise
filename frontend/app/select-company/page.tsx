"use client";

import React, { Suspense, useEffect, useMemo, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { initKeycloak } from "@/lib/keycloak";
import { getJwtRoles, rolesToWorkspaces } from "@/lib/auth/roles";
import type { Workspace } from "@/types/auth";
import { WORKSPACE_PATH } from "@/types/auth";
import type { CompanySummary, CompaniesByWorkspace } from "@/types/sso";
import { fetchProfileCompanies } from "@/lib/auth/profile-client";
import { getWorkspaceCookie, setWorkspaceCookie } from "@/lib/workspace-cookie";
import { getCompanyCookie, setCompanyCookie } from "@/lib/company-cookie";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

export default function SelectCompanyPage() {
  return (
    <Suspense fallback={<div className="min-h-screen flex items-center justify-center"><div className="text-sm text-muted-foreground animate-pulse">Resolving companies…</div></div>}>
      <SelectCompanyClient />
    </Suspense>
  );
}

function SelectCompanyClient() {
  const router = useRouter();
  const params = useSearchParams();
  const [loading, setLoading] = useState(true);
  const [workspace, setWorkspace] = useState<Workspace | null>(null);
  const [companies, setCompanies] = useState<CompaniesByWorkspace | null>(null);

  useEffect(() => {
    let mounted = true;
    (async () => {
      await initKeycloak(true);
      if (!mounted) return;
      const roles = getJwtRoles();
      const allowed = rolesToWorkspaces(roles);
      const q = params.get("workspace");
      let ws: Workspace | null = null;
      if (q && ["buyer", "approver", "vendor", "admin"].includes(q)) ws = q as Workspace;
      if (!ws) ws = getWorkspaceCookie();
      if (!ws || !allowed.includes(ws)) {
        router.replace("/select-workspace");
        return;
      }
      setWorkspace(ws);
      try {
        const c = await fetchProfileCompanies();
        if (!mounted) return;
        setCompanies(c);
        const list = c[ws] || [];
        if (ws !== "buyer") {
          if (list.length === 0) {
            setLoading(false);
            return; // show empty state below
          }
          if (list.length === 1) {
            setCompanyCookie(ws, list[0].companykey);
            router.replace(WORKSPACE_PATH[ws]);
            return;
          }
          // Multiple companies → require selection
          setLoading(false);
        } else {
          // Buyer: optional selection; no auto-set in header flow
          setLoading(false);
        }
      } catch (e) {
        setLoading(false);
      }
    })();
    return () => { mounted = false; };
  }, [params, router]);

  const wsCompanies: CompanySummary[] = useMemo(() => {
    if (!companies || !workspace) return [];
    return companies[workspace] || [];
  }, [companies, workspace]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-sm text-muted-foreground animate-pulse">Resolving companies…</div>
      </div>
    );
  }

  if (!workspace) return null;

  const current = getCompanyCookie(workspace);

  if (workspace !== "buyer" && wsCompanies.length === 0) {
    return (
      <div className="min-h-screen flex items-center justify-center p-6">
        <div className="max-w-lg text-center space-y-3">
          <h1 className="text-2xl font-semibold">No companies available</h1>
          <p className="text-sm text-muted-foreground">You don’t have any companies for the {workspace} workspace.</p>
          <Button onClick={() => router.replace("/select-workspace")}>Back to workspace selection</Button>
        </div>
      </div>
    );
  }

  const choose = (key: string) => {
    setCompanyCookie(workspace, key);
    setWorkspaceCookie(workspace);
    router.replace(WORKSPACE_PATH[workspace]);
  };

  return (
    <div className="min-h-screen flex items-center justify-center p-6">
      <div className="w-full max-w-xl">
        <h1 className="text-2xl font-semibold mb-2">Select a company</h1>
        <p className="text-sm text-muted-foreground mb-4">Workspace: {workspace.toUpperCase()}</p>
        {wsCompanies.length <= 1 ? (
          <div className="text-sm text-muted-foreground">{current ? "Company is selected." : "No selection required."}</div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {wsCompanies.map((c) => (
              <Card key={c.companykey} className="p-4 cursor-pointer hover:shadow" onClick={() => choose(c.companykey)}>
                <div className="font-medium">{c.companyname}</div>
                <div className="text-xs text-muted-foreground">{c.companykey}</div>
              </Card>
            ))}
          </div>
        )}
        {workspace === "buyer" && wsCompanies.length > 1 && (
          <div className="mt-4">
            <Button variant="outline" onClick={() => router.replace(WORKSPACE_PATH[workspace])}>Skip for now</Button>
          </div>
        )}
      </div>
    </div>
  );
}
