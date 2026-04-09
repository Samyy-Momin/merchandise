"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { initKeycloak } from "@/lib/keycloak";

export default function VendorRoot() {
  const router = useRouter();
  useEffect(() => { initKeycloak(true).then(() => router.replace("/vendor/orders")); }, [router]);
  return <div className="p-6 text-sm">Loading…</div>;
}

