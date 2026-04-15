"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { initKeycloak } from "@/lib/keycloak";
import { getJwtRoles, rolesToWorkspacesForSelection } from "@/lib/auth/roles";
import { setWorkspaceCookie } from "@/lib/workspace-cookie";
import type { Workspace } from "@/types/auth";
import { WORKSPACE_PATH } from "@/types/auth";
import { Card } from "@/components/ui/card";
import { fetchProfileCompanies } from "@/lib/auth/profile-client";

export default function SelectWorkspaceClient() {
  const router = useRouter();
  const params = useSearchParams();
  const [loading, setLoading] = useState(true);
  const [effective, setEffective] = useState<Workspace[]>([]);

  useEffect(() => {
    let mounted = true;
    initKeycloak(true).then(async () => {
      if (!mounted) return;
      const r = getJwtRoles();
      setLoading(false);
      const base = rolesToWorkspacesForSelection(r);
      let eff: Workspace[] = base;
      try {
        const companies = await fetchProfileCompanies();
        const tmp: Workspace[] = [] as Workspace[];
        for (const ws of base) {
          if (ws === "buyer") tmp.push(ws);
          else if ((companies[ws] || []).length > 0) tmp.push(ws);
        }
        eff = tmp;
        // eslint-disable-next-line no-console
        console.log("[SelectWorkspace] roles", r, "base", base, "effective", eff);
      } catch (e) {
        // eslint-disable-next-line no-console
        console.warn("[SelectWorkspace] profile companies fetch failed", e);
      }
      setEffective(eff);
      if (eff.length === 1) {
        const only = eff[0];
        setWorkspaceCookie(only);
        router.replace(WORKSPACE_PATH[only]);
      }
    });
    return () => {
      mounted = false;
    };
  }, [router]);

  useEffect(() => {
    // Optional deep link: ?workspace=buyer|approver|vendor|admin
    const w = params.get("workspace");
    if (!w) return;
    if (["buyer", "approver", "vendor", "admin"].includes(w)) {
      const ws = w as Workspace;
      // Validate against available workspaces
      if (rolesToWorkspacesForSelection(getJwtRoles()).includes(ws)) {
        setWorkspaceCookie(ws);
        router.replace(WORKSPACE_PATH[ws]);
      }
    }
  }, [params, router]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-sm text-muted-foreground animate-pulse">Loading…</div>
      </div>
    );
  }

  const available = effective;

  if (available.length === 0) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-sm text-red-600">No available workspaces for your account.</div>
      </div>
    );
  }

  const choose = (ws: Workspace) => {
    setWorkspaceCookie(ws);
    router.replace(WORKSPACE_PATH[ws]);
  };

  return (
    <div className="min-h-screen flex items-center justify-center p-6">
      <div className="w-full max-w-xl">
        <h1 className="text-2xl font-semibold mb-4">Select a workspace</h1>
        <div className="grid grid-cols-2 gap-4">
          {available.includes("buyer") && (
            <Card className="p-6 cursor-pointer hover:shadow" onClick={() => choose("buyer")}>Buyer</Card>
          )}
          {available.includes("approver") && (
            <Card className="p-6 cursor-pointer hover:shadow" onClick={() => choose("approver")}>Approver</Card>
          )}
          {available.includes("vendor") && (
            <Card className="p-6 cursor-pointer hover:shadow" onClick={() => choose("vendor")}>Vendor</Card>
          )}
          {available.includes("admin") && (
            <Card className="p-6 cursor-pointer hover:shadow" onClick={() => choose("admin")}>Admin</Card>
          )}
        </div>
      </div>
    </div>
  );
}

