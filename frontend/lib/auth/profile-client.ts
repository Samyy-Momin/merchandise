"use client";

import { getToken } from "@/lib/keycloak";
import type { CompaniesByWorkspace } from "@/types/sso";

export async function fetchProfileCompanies(): Promise<CompaniesByWorkspace> {
  const token = getToken();
  if (!token) throw new Error("No access token available");

  const res = await fetch("/api/auth/profile", {
    method: "GET",
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
    cache: "no-store",
  });
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`Profile API ${res.status}: ${text}`);
  }
  const json = (await res.json()) as { companiesByWorkspace: CompaniesByWorkspace };
  return json.companiesByWorkspace;
}

