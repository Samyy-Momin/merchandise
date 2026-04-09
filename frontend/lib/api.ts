"use client";

import { getToken } from "@/lib/keycloak";

const API_BASE = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000";

export async function apiFetch(path: string, init?: RequestInit) {
  if (!path) {
    // eslint-disable-next-line no-console
    console.error('[apiFetch] missing path', { path });
    throw new Error('apiFetch: path is required');
  }
  const token = getToken();
  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(init?.headers as Record<string, string>),
  };

  if (token) headers["Authorization"] = `Bearer ${token}`;

  const p = typeof path === 'string' ? path : String(path);
  const url = `${API_BASE}${p.startsWith('/') ? p : `/${p}`}`;
  const res = await fetch(url, {
    ...init,
    headers,
    cache: "no-store",
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`API ${res.status}: ${text}`);
  }

  const contentType = res.headers.get("content-type") || "";
  return contentType.includes("application/json") ? res.json() : res.text();
}
