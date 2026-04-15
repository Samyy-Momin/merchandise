"use client";

import { getRoles, getStoredRoles } from "@/lib/keycloak";
import type { Workspace } from "@/types/auth";

/**
 * Returns current JWT roles (client + realm). Prefers live Keycloak token when available,
 * falls back to decoding the stored JWT to avoid spinner on back/refresh.
 */
export function getJwtRoles(): string[] {
  try {
    const live = getRoles();
    if (Array.isArray(live) && live.length > 0) return live;
  } catch {
    /* ignore */
  }
  try {
    const cached = getStoredRoles();
    return Array.isArray(cached) ? cached : [];
  } catch {
    return [];
  }
}

/** Map JWT roles to allowed workspaces. Admins get access to all workspaces. */
export function rolesToWorkspaces(roles: string[]): Workspace[] {
  const set = new Set<string>(roles || []);
  // Admin bypass → all workspaces
  if (set.has("admin") || set.has("super_admin")) return ["buyer", "approver", "vendor", "admin"];

  const list: Workspace[] = [];
  if (set.has("buyer")) list.push("buyer");
  if (set.has("approver")) list.push("approver");
  if (set.has("vendor")) list.push("vendor");
  if (set.has("admin")) list.push("admin"); // handled above but keep for completeness
  return list;
}

/** Does these roles satisfy the workspace requirements? Admins always satisfy. */
export function rolesSatisfyWorkspace(workspace: Workspace, roles: string[]): boolean {
  const set = new Set<string>(roles || []);
  if (set.has("admin") || set.has("super_admin")) return true;
  switch (workspace) {
    case "buyer":
      return set.has("buyer");
    case "approver":
      return set.has("approver");
    case "vendor":
      return set.has("vendor");
    case "admin":
      return set.has("admin");
  }
}

/**
 * Derive workspaces for selection screens strictly from explicit roles.
 * Admin/super_admin do NOT imply other workspaces here, to avoid confusing multi-choice when only admin is intended.
 */
export function rolesToWorkspacesForSelection(roles: string[]): Workspace[] {
  const set = new Set<string>(roles || []);
  const list: Workspace[] = [];
  if (set.has("buyer")) list.push("buyer");
  if (set.has("approver")) list.push("approver");
  if (set.has("vendor")) list.push("vendor");
  if (set.has("admin") || set.has("super_admin")) list.push("admin");
  return list;
}
