"use client";

import { create } from "zustand";
import { persist } from "zustand/middleware";

type ViewPreference = "grid" | "table";

type UIState = {
  sidebarOpen: boolean;
  sidebarCollapsed: boolean;
  viewPreference: ViewPreference;
};

type UIActions = {
  toggleSidebar: () => void;
  setSidebarOpen: (open: boolean) => void;
  setSidebarCollapsed: (collapsed: boolean) => void;
  setViewPreference: (pref: ViewPreference) => void;
};

export const useUIStore = create<UIState & UIActions>()(
  persist(
    (set) => ({
      sidebarOpen: true,
      sidebarCollapsed: false,
      viewPreference: "grid" as ViewPreference,

      toggleSidebar: () => set((s) => ({ sidebarOpen: !s.sidebarOpen })),
      setSidebarOpen: (sidebarOpen) => set({ sidebarOpen }),
      setSidebarCollapsed: (sidebarCollapsed) => set({ sidebarCollapsed }),
      setViewPreference: (viewPreference) => set({ viewPreference }),
    }),
    {
      name: "merch-ui-prefs",
      partialize: (state) => ({
        sidebarCollapsed: state.sidebarCollapsed,
        viewPreference: state.viewPreference,
      }),
    },
  ),
);
