"use client";

import type { Workspace } from "@/types/auth";

const COOKIE_NAME = "company_context";

type CompanyMap = Partial<Record<Workspace, string>>;

function parseCookieValue(value: string | undefined): CompanyMap {
  if (!value) return {};
  try {
    const decoded = decodeURIComponent(value);
    const obj = JSON.parse(decoded);
    if (obj && typeof obj === "object") return obj as CompanyMap;
    return {};
  } catch {
    return {};
  }
}

function readCookieRaw(name: string): string | undefined {
  if (typeof document === "undefined") return undefined;
  const pairs = document.cookie ? document.cookie.split("; ") : [];
  for (const p of pairs) {
    const [k, v] = p.split("=");
    if (k === name) return v;
  }
  return undefined;
}

function writeCookie(name: string, value: string, maxAgeDays = 7) {
  if (typeof document === "undefined") return;
  const maxAge = maxAgeDays * 24 * 60 * 60;
  const parts = [
    `${name}=${encodeURIComponent(value)}`,
    `Path=/`,
    `Max-Age=${maxAge}`,
    `SameSite=Lax`,
  ];
  try {
    if (typeof window !== "undefined" && window.location.protocol === "https:") {
      parts.push("Secure");
    }
  } catch {
    /* ignore */
  }
  document.cookie = parts.join("; ");
}

export function getCompanyCookie(workspace: Workspace): string | null {
  const raw = readCookieRaw(COOKIE_NAME);
  const map = parseCookieValue(raw);
  const key = map[workspace];
  return typeof key === "string" && key.length > 0 ? key : null;
}

export function setCompanyCookie(workspace: Workspace, company_key: string): void {
  const raw = readCookieRaw(COOKIE_NAME);
  const map = parseCookieValue(raw);
  map[workspace] = company_key;
  writeCookie(COOKIE_NAME, JSON.stringify(map));
}

export function clearCompanyCookie(workspace?: Workspace): void {
  if (typeof document === "undefined") return;
  if (!workspace) {
    // Clear entire cookie
    document.cookie = `${COOKIE_NAME}=; Path=/; Max-Age=0; SameSite=Lax`;
    return;
  }
  const raw = readCookieRaw(COOKIE_NAME);
  const map = parseCookieValue(raw);
  if (workspace in map) {
    delete map[workspace];
    writeCookie(COOKIE_NAME, JSON.stringify(map));
  }
}

