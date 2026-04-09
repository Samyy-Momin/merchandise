"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { initKeycloak } from "@/lib/keycloak";

export default function ApproverRoot() {
  const router = useRouter();
  useEffect(() => {
    initKeycloak(true).then(() => router.replace("/approver/orders"));
  }, [router]);
  return <div className="p-6 text-sm">Loading…</div>;
}

