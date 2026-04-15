import type { Workspace } from "@/types/auth";

/**
 * Mapping of SSO/profile roles to workspace buckets.
 * Values are compared case-insensitively.
 */
export const WORKSPACE_ROLE_MAP: Record<Workspace, string[]> = {
  buyer: ["buyer", "customer"],
  approver: ["approver", "seniormanager", "admin", "superadmin"],
  vendor: ["vendor", "supplier", "orgvendor"],
  admin: ["admin", "superadmin", "orgadmin"],
};

/**
 * Client IDs from which roles should be considered when deriving company access.
 * Defaults to the main Keycloak client id used by this app.
 */
export const RELEVANT_CLIENT_IDS: string[] = [
  process.env.NEXT_PUBLIC_KEYCLOAK_CLIENT_ID || "merchandise",
];
