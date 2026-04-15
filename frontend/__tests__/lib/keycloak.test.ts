/**
 * Tests for Keycloak utility functions.
 */

// Mock keycloak-js to avoid actual SSO initialization
jest.mock("keycloak-js", () => {
  return jest.fn().mockImplementation(() => ({
    init: jest.fn().mockResolvedValue(true),
    login: jest.fn(),
    logout: jest.fn(),
    token: "mock-token-header.eyJzdWIiOiJ1c2VyLTEiLCJwcmVmZXJyZWRfdXNlcm5hbWUiOiJ0ZXN0IiwicmVzb3VyY2VfYWNjZXNzIjp7Im1lcmNoYW5kaXNlIjp7InJvbGVzIjpbImJ1eWVyIl19fSwicmVhbG1fYWNjZXNzIjp7InJvbGVzIjpbXX19.sig",
    tokenParsed: {
      sub: "user-1",
      preferred_username: "test",
      resource_access: {
        merchandise: { roles: ["buyer"] },
      },
      realm_access: { roles: [] },
    },
    onTokenExpired: null,
    updateToken: jest.fn().mockResolvedValue(true),
  }));
});

import { getToken, getRoles, hasAnyRole, roleToPath, getStoredRoles } from "@/lib/keycloak";

describe("Keycloak Utilities", () => {
  describe("roleToPath", () => {
    test("admin maps to /admin", () => {
      expect(roleToPath(["admin"])).toBe("/admin");
    });

    test("super_admin maps to /admin", () => {
      expect(roleToPath(["super_admin"])).toBe("/admin");
    });

    test("buyer maps to /buyer", () => {
      expect(roleToPath(["buyer"])).toBe("/buyer");
    });

    test("approver maps to /approver", () => {
      expect(roleToPath(["approver"])).toBe("/approver");
    });

    test("vendor maps to /vendor", () => {
      expect(roleToPath(["vendor"])).toBe("/vendor");
    });

    test("empty roles maps to /", () => {
      expect(roleToPath([])).toBe("/");
    });

    test("admin takes priority over buyer", () => {
      expect(roleToPath(["buyer", "admin"])).toBe("/admin");
    });

    test("buyer takes priority over approver", () => {
      expect(roleToPath(["approver", "buyer"])).toBe("/buyer");
    });
  });

  describe("hasAnyRole", () => {
    // hasAnyRole depends on getRoles which reads from keycloak instance
    // Since keycloak isn't initialized in these tests, it returns []
    // We test the logic separately
    test("returns false when no roles match", () => {
      // Without initializing keycloak, getRoles returns []
      expect(hasAnyRole(["admin"])).toBe(false);
    });
  });

  describe("getStoredRoles", () => {
    test("returns empty array when no token stored", () => {
      localStorage.removeItem("kc_token");
      expect(getStoredRoles()).toEqual([]);
    });

    test("returns empty for invalid token format", () => {
      localStorage.setItem("kc_token", "not-a-jwt");
      expect(getStoredRoles()).toEqual([]);
    });

    test("parses roles from a valid JWT payload", () => {
      // Create a fake JWT with buyer role
      const payload = {
        sub: "user-1",
        resource_access: {
          merchandise: { roles: ["buyer"] },
        },
        realm_access: { roles: ["offline_access"] },
      };
      const b64 = btoa(JSON.stringify(payload));
      const fakeJwt = `header.${b64}.signature`;
      localStorage.setItem("kc_token", fakeJwt);

      const roles = getStoredRoles();
      expect(roles).toContain("buyer");
      expect(roles).toContain("offline_access");
    });
  });
});
