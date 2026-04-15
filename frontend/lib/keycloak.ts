"use client";

import Keycloak, { KeycloakInstance } from "keycloak-js";

let keycloak: KeycloakInstance | null = null;
let initPromise: Promise<KeycloakInstance> | null = null;

export function getKeycloak(): KeycloakInstance | null {
  return keycloak;
}

export function getToken(): string | null {
  return keycloak?.token ?? (typeof window !== "undefined" ? localStorage.getItem("kc_token") : null);
}

export function getRoles(): string[] {
  const parsed: any = (keycloak as any)?.tokenParsed;
  const clientId = process.env.NEXT_PUBLIC_KEYCLOAK_CLIENT_ID || "merchandise";
  const clientRoles = parsed?.resource_access?.[clientId]?.roles;
  const realmRoles = parsed?.realm_access?.roles;

  const list: string[] = [
    ...(Array.isArray(clientRoles) ? clientRoles : []),
    ...(Array.isArray(realmRoles) ? realmRoles : []),
  ];
  const unique = Array.from(new Set(list));
  return unique;
}

export async function initKeycloak(loginRequired = true): Promise<KeycloakInstance> {
  if (keycloak) return keycloak;
  if (initPromise) return initPromise;

  const url = process.env.NEXT_PUBLIC_KEYCLOAK_URL!;
  const realm = process.env.NEXT_PUBLIC_KEYCLOAK_REALM!;
  const clientId = process.env.NEXT_PUBLIC_KEYCLOAK_CLIENT_ID!;

  keycloak = new Keycloak({ url, realm, clientId });

  initPromise = keycloak
    .init({ onLoad: loginRequired ? "login-required" : undefined, checkLoginIframe: false })
    .then((authenticated) => {
      // Avoid forcing another login when using login-required.
      if (!authenticated && !loginRequired) {
        keycloak!.login();
      }

      if (keycloak!.token) {
        localStorage.setItem("kc_token", keycloak!.token);
      }

      keycloak!.onTokenExpired = () => {
        keycloak!
          .updateToken(30)
          .then((refreshed) => {
            if (refreshed && keycloak!.token) {
              localStorage.setItem("kc_token", keycloak!.token);
            }
          })
          .catch(() => keycloak!.login());
      };

      // Debug: log parsed roles
      try {
        const roles = getRoles();
        // eslint-disable-next-line no-console
        console.log("[Keycloak] roles:", roles);
      } catch (e) {
        // eslint-disable-next-line no-console
        console.warn("[Keycloak] unable to parse roles", e);
      }

      return keycloak!;
    });

  return initPromise;
}

export function hasAnyRole(required: string[]): boolean {
  const roles = getRoles();
  return required.some((r) => roles.includes(r));
}

export function roleToPath(roles: string[]): string {
  // Priority order: admin > buyer > approver > vendor (adjust as needed)
  if (roles.includes("admin") || roles.includes("super_admin")) return "/admin";
  if (roles.includes("buyer")) return "/buyer";
  if (roles.includes("approver")) return "/approver";
  if (roles.includes("vendor")) return "/vendor";
  return "/";
}

export function kcLogout(redirectUri?: string) {
  try {
    if (!keycloak) return;
    const url = redirectUri || (typeof window !== "undefined" ? window.location.origin : undefined);
    keycloak.logout(url ? { redirectUri: url } : undefined);
  } catch (e) {
    // eslint-disable-next-line no-console
    console.error("[Keycloak] logout error", e);
  }
}

// Fast role check from stored JWT to avoid spinner on back navigation
export function getStoredRoles(): string[] {
  try {
    if (typeof window === "undefined") return [];
    const token = localStorage.getItem("kc_token");
    if (!token) return [];
    const parts = token.split(".");
    if (parts.length !== 3) return [];
    let b64 = parts[1];
    const pad = b64.length % 4;
    if (pad) b64 += "=".repeat(4 - pad);
    const json = JSON.parse(atob(b64.replace(/-/g, "+").replace(/_/g, "/")));
    const clientId = process.env.NEXT_PUBLIC_KEYCLOAK_CLIENT_ID || "merchandise";
    const clientRoles = Array.isArray(json?.resource_access?.[clientId]?.roles) ? json.resource_access[clientId].roles : [];
    const realmRoles = Array.isArray(json?.realm_access?.roles) ? json.realm_access.roles : [];
    return Array.from(new Set([...(clientRoles as string[]), ...(realmRoles as string[])]));
  } catch {
    return [];
  }
}
