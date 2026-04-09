"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { initKeycloak, hasAnyRole } from "@/lib/keycloak";

export default function AdminRoot() {
  const router = useRouter();
  useEffect(() => {
    initKeycloak(true).then(() => {
      if (hasAnyRole(["admin","super_admin"])) router.replace("/admin/dashboard");
      else router.replace("/");
    });
  }, [router]);
  return <div className="p-6 text-sm">Redirecting…</div>;
}
