import { Suspense } from "react";
import SelectWorkspaceClient from "./select-workspace-client";

export default function SelectWorkspacePage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen flex items-center justify-center">
          <div className="text-sm text-muted-foreground animate-pulse">Loading…</div>
        </div>
      }
    >
      <SelectWorkspaceClient />
    </Suspense>
  );
}
