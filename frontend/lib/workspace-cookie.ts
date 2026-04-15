"use client";

import type { Workspace } from "@/types/auth";

const COOKIE_NAME = "workspace_context";

function serializeCookie(name: string, value: string, maxAgeDays = 7): string {
  const maxAge = maxAgeDays * 24 * 60 * 60; // seconds
  const attrs = [
    `${name}=${encodeURIComponent(value)}`,
    `Path=/`,
    `Max-Age=${maxAge}`,
    `SameSite=Lax`,
  ];
  // Best effort secure flag when served over https
  try {
    if (typeof window !== "undefined" && window.location.protocol === "https:") {
      attrs.push("Secure");
    }
  } catch {
    /* ignore */
  }
  return attrs.join("; ");
}

export function setWorkspaceCookie(workspace: Workspace): void {
  if (typeof document === "undefined") return;
  document.cookie = serializeCookie(COOKIE_NAME, workspace);
}

export function clearWorkspaceCookie(): void {
  if (typeof document === "undefined") return;
  // Expire immediately
  document.cookie = `${COOKIE_NAME}=; Path=/; Max-Age=0; SameSite=Lax`;
}

export function getWorkspaceCookie(): Workspace | null {
  if (typeof document === "undefined") return null;
  const cookies = document.cookie ? document.cookie.split("; ") : [];
  for (const c of cookies) {
    const [k, v] = c.split("=");
    if (k === COOKIE_NAME) {
      const value = decodeURIComponent(v || "");
      if (value === "buyer" || value === "approver" || value === "vendor" || value === "admin") {
        return value as Workspace;
      }
      return null;
    }
  }
  return null;
}

