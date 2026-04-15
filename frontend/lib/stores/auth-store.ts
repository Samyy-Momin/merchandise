"use client";

import { create } from "zustand";

type AuthUser = {
  preferred_username?: string;
  email?: string;
  name?: string;
  sub?: string;
  [key: string]: unknown;
};

type AuthState = {
  user: AuthUser | null;
  roles: string[];
  isAuthenticated: boolean;
  hydrated: boolean;
};

type AuthActions = {
  setUser: (user: AuthUser | null) => void;
  setRoles: (roles: string[]) => void;
  setAuthenticated: (v: boolean) => void;
  setHydrated: (v: boolean) => void;
  logout: () => void;
};

export const useAuthStore = create<AuthState & AuthActions>((set) => ({
  user: null,
  roles: [],
  isAuthenticated: false,
  hydrated: false,

  setUser: (user) => set({ user }),
  setRoles: (roles) => set({ roles }),
  setAuthenticated: (isAuthenticated) => set({ isAuthenticated }),
  setHydrated: (hydrated) => set({ hydrated }),
  logout: () => set({ user: null, roles: [], isAuthenticated: false }),
}));

/** Convenience selectors */
export const selectUser = (s: AuthState) => s.user;
export const selectRoles = (s: AuthState) => s.roles;
export const selectIsAuthenticated = (s: AuthState) => s.isAuthenticated;

export function hasRole(roles: string[], required: string[]): boolean {
  return required.some((r) => roles.includes(r));
}
