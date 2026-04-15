"use client";

import { Moon, Sun } from "lucide-react";
import { useTheme } from "next-themes";
import { useEffect, useState } from "react";

export function ThemeToggle() {
  const { resolvedTheme, theme, setTheme } = useTheme();
  const [mounted, setMounted] = useState(false);

  useEffect(() => setMounted(true), []);

  if (!mounted) return <div className="size-8" />;

  return (
    <button
      aria-label="Toggle dark mode"
      className="inline-flex items-center justify-center size-8 rounded-md hover:bg-accent transition-colors"
      onClick={() => setTheme((resolvedTheme || theme) === "dark" ? "light" : "dark")}
    >
      {(resolvedTheme || theme) === "dark" ? <Sun className="size-4" /> : <Moon className="size-4" />}
    </button>
  );
}
