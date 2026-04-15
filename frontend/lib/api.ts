"use client";

import { getToken } from "@/lib/keycloak";
import { getWorkspaceCookie } from "@/lib/workspace-cookie";
import { getCompanyCookie } from "@/lib/company-cookie";
import type { Category } from "@/types";

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

  // Attach workspace/company context headers for protected routes
  try {
    const ws = getWorkspaceCookie();
    if (ws) headers["X-Workspace-Context"] = ws;
    const compKey = ws ? getCompanyCookie(ws) : null;
    if (compKey) headers["X-Company-Key"] = compKey;
  } catch {
    /* ignore */
  }

  const p = typeof path === 'string' ? path : String(path);
  const url = `${API_BASE}${p.startsWith('/') ? p : `/${p}`}`;
  // Forward AbortSignal if provided via init.signal; keep explicit no-store
  const debug = typeof window !== 'undefined' && (process.env.NEXT_PUBLIC_API_DEBUG_TIMINGS === '1' || process.env.NEXT_PUBLIC_API_DEBUG_TIMINGS === 'true');
  const t0 = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
  let res: Response;
  try {
    res = await fetch(url, {
      ...init,
      headers,
      cache: "no-store",
    });
  } catch (e: any) {
    if (debug && e?.name === 'AbortError') {
      const t1 = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
      // eslint-disable-next-line no-console
      console.debug('[apiFetch] aborted', { path: p, ms: Math.round(t1 - t0) });
    }
    throw e;
  }

  if (debug) {
    const t1 = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
    // eslint-disable-next-line no-console
    console.debug('[apiFetch] done', { path: p, status: res.status, ms: Math.round(t1 - t0) });
  }

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`API ${res.status}: ${text}`);
  }

  const contentType = res.headers.get("content-type") || "";
  return contentType.includes("application/json") ? res.json() : res.text();
}

// Tiny in-memory cache for categories to avoid re-fetching across pages
let CATEGORIES_CACHE: Category[] | null = null;

export async function getCategoriesCached(): Promise<Category[]> {
  if (Array.isArray(CATEGORIES_CACHE)) return CATEGORIES_CACHE;
  const res = await apiFetch('/api/categories');
  const list = Array.isArray(res) ? (res as Category[]) : [];
  CATEGORIES_CACHE = list;
  return list;
}
