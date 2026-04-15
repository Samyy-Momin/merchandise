import { NextResponse } from "next/server";
import { headers, cookies } from "next/headers";
import type { CompaniesByWorkspace, SsoProfileResponse, GroupAccessEntry, CompanySummary } from "@/types/sso";
import type { Workspace } from "@/types/auth";
import { WORKSPACE_ROLE_MAP, RELEVANT_CLIENT_IDS } from "@/lib/auth/role-company-map";

export const dynamic = "force-dynamic";

async function pickAuthToken(): Promise<string | null> {
  try {
    const h = await headers();
    const auth = h.get("authorization") || h.get("Authorization");
    if (auth && auth.toLowerCase().startsWith("bearer ")) {
      return auth.slice(7).trim();
    }
  } catch {}
  try {
    const c = await cookies();
    const fromCookie =
      c.get("kc-token")?.value ||
      c.get("kc_token")?.value ||
      c.get("access-token")?.value ||
      c.get("access_token")?.value ||
      c.get("keycloak_token")?.value ||
      c.get("oidc_token")?.value ||
      null;
    if (fromCookie) return fromCookie;
  } catch {}
  return null;
}

function normalize(str: string): string {
  return (str || "").toLowerCase().replace(/[^a-z0-9]/g, "");
}

function deriveCompaniesByWorkspace(entries: GroupAccessEntry[]): CompaniesByWorkspace {
  const result: CompaniesByWorkspace = {
    buyer: [],
    approver: [],
    vendor: [],
    admin: [],
  };

  const clientAllow = RELEVANT_CLIENT_IDS.map(normalize);

  for (const entry of entries || []) {
    if (!entry || !entry.companykey) continue;
    const company: CompanySummary = {
      companykey: entry.companykey,
      companyname: entry.companyname || entry.companykey,
    };

    // Aggregate roles from relevant clients for this company
    const roleSet = new Set<string>();
    for (const client of entry.clients || []) {
      const cid = normalize(client.clientid);
      if (clientAllow.length > 0 && !clientAllow.includes(cid)) continue;
      for (const r of client.roles || []) {
        roleSet.add(normalize(r));
      }
    }

    // If no client roles were found under the allowlist, we can optionally fall back to considering all clients
    if (roleSet.size === 0 && (entry.clients || []).length) {
      for (const client of entry.clients || []) {
        for (const r of client.roles || []) {
          roleSet.add(normalize(r));
        }
      }
    }

    // Assign company to workspaces where at least one mapped role matches
    const pushUnique = (w: Workspace) => {
      const arr = result[w];
      if (!arr.some((c) => c.companykey === company.companykey)) arr.push(company);
    };

    for (const [workspace, acceptedRoles] of Object.entries(WORKSPACE_ROLE_MAP) as [Workspace, string[]][]) {
      const hasMatch = acceptedRoles.some((ar) => roleSet.has(normalize(ar)));
      if (hasMatch) pushUnique(workspace);
    }
  }

  return result;
}

export async function GET() {
  console.log("[/api/auth/profile] start");
  const token = await pickAuthToken();
  if (!token) {
    console.warn("[/api/auth/profile] missing token");
    return NextResponse.json({ message: "Missing access token (profile API)" }, { status: 401 });
  }
  // Decode token payload for debugging issuer/client
  try {
    const parts = token.split(".");
    if (parts.length === 3) {
      const b64 = parts[1].replace(/-/g, "+").replace(/_/g, "/");
      const pad = b64.length % 4 ? "=".repeat(4 - (b64.length % 4)) : "";
      const json = JSON.parse(Buffer.from(b64 + pad, "base64").toString("utf8"));
      console.log("[/api/auth/profile] token claims", {
        iss: json.iss,
        aud: json.aud,
        azp: json.azp,
        preferred_username: json.preferred_username,
        client_roles: json.resource_access?.[process.env.NEXT_PUBLIC_KEYCLOAK_CLIENT_ID || "merchandise"]?.roles,
        has_groupAccess: !!json.groupAccess,
      });
    }
  } catch {}

  const configured = process.env.SSO_PROFILE_API_URL || process.env.NEXT_PUBLIC_SSO_PROFILE_API_URL;
  const url = (configured && configured.trim().length > 0)
    ? configured
    : "https://ssomigration.cubeone.in/api/users/profile";
  console.log("[/api/auth/profile] calling", url);
  let data: SsoProfileResponse;
  try {
    const res = await fetch(url, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
      cache: "no-store",
    });
    if (!res.ok) {
      const text = await res.text();
      console.error("[/api/auth/profile] upstream error", res.status, text);
      return NextResponse.json({ message: "SSO profile fetch failed", status: res.status, body: text }, { status: 502 });
    }
    data = (await res.json()) as SsoProfileResponse;
    console.log("[/api/auth/profile] raw response keys", Object.keys(data || {}));
  } catch (e: any) {
    console.error("[/api/auth/profile] request exception", e?.message || e);
    return NextResponse.json({ message: "SSO profile request error", error: String(e?.message || e) }, { status: 502 });
  }

  const container: any = (data as any)?.data || data;
  const raw = container?.group_access ?? container?.groupAccess ?? container?.groupaccess;
  let entries: GroupAccessEntry[] = [];
  if (Array.isArray(raw)) {
    // Already normalized array? Coerce keys to our camel-lite format
    entries = (raw as any[]).map((e) => ({
      companykey: e.companykey ?? e.company_key ?? e.companyKey ?? e.key ?? "",
      companyname: e.companyname ?? e.company_name ?? e.companyName ?? e.name ?? e.companykey ?? e.company_key ?? "",
      clients: Array.isArray(e.clients)
        ? e.clients.map((c: any) => ({
            clientid: c.clientid ?? c.client_id ?? c.clientId,
            clientname: c.clientname ?? c.client_name ?? c.clientName,
            roles: Array.isArray(c.roles) ? c.roles : c.roles ? [c.roles] : [],
          }))
        : [],
    })) as GroupAccessEntry[];
  } else if (raw && typeof raw === "object") {
    // Map form: { C_*: { company_name, clients: { [clientId]: roleOrRoles } } }
    entries = Object.entries(raw).map(([key, val]: [string, any]) => {
      const v = val || {};
      const clientsArray: any[] = [];
      const clientsObj = v.clients || v.clientAccess || {};
      if (clientsObj && typeof clientsObj === "object") {
        for (const [cid, roleVal] of Object.entries(clientsObj)) {
          const rolesArr = Array.isArray(roleVal) ? (roleVal as string[]) : roleVal ? [roleVal as string] : [];
          clientsArray.push({ clientid: cid, roles: rolesArr });
        }
      }
      return {
        companykey: key,
        companyname: v.company_name || v.companyName || key,
        clients: clientsArray,
      } as GroupAccessEntry;
    });
  } else {
    entries = [];
  }
  console.log("[/api/auth/profile] normalized group_access entries", entries.length);

  const companiesByWorkspace = deriveCompaniesByWorkspace(entries as GroupAccessEntry[]);
  console.log("[/api/auth/profile] companies by workspace counts", {
    buyer: companiesByWorkspace.buyer.length,
    approver: companiesByWorkspace.approver.length,
    vendor: companiesByWorkspace.vendor.length,
    admin: companiesByWorkspace.admin.length,
  });
  return NextResponse.json({ companiesByWorkspace });
}
