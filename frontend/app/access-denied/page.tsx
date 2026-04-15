"use client";

import { Button } from "@/components/ui/button";
import { kcLogout } from "@/lib/keycloak";

export default function AccessDeniedPage() {
  return (
    <div className="min-h-screen flex items-center justify-center p-6">
      <div className="max-w-md text-center space-y-4">
        <div>
          <h1 className="text-2xl font-semibold mb-2">Access denied</h1>
          <p className="text-sm text-muted-foreground">
            You don’t have permission to access this workspace. Try choosing a different role.
          </p>
        </div>
        <div>
          <Button onClick={() => kcLogout()} className="mt-2">Log out</Button>
        </div>
      </div>
    </div>
  );
}
