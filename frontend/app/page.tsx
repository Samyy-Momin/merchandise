"use client";

import { useEffect, useState } from "react";
import { initKeycloak, getRoles, roleToPath } from "@/lib/keycloak";
import { useRouter } from "next/navigation";

export default function Home() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    initKeycloak(true)
      .then(() => {
        if (!mounted) return;
        const roles = getRoles();
        // Debug: log roles at redirect time
        // eslint-disable-next-line no-console
        console.log("[Redirect] roles:", roles);
        const destination = roleToPath(roles);
        router.replace(destination);
      })
      .finally(() => mounted && setLoading(false));
    return () => {
      mounted = false;
    };
  }, [router]);

  return (
    <div style={{ padding: 24 }}>
      {loading ? <p>Signing you in…</p> : <p>Redirecting…</p>}
    </div>
  );
}
