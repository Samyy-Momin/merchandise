"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

type SpinnerProps = React.HTMLAttributes<HTMLDivElement> & {
  size?: number | string; // e.g. 16 | "1rem"
  thickness?: number; // border thickness in px
};

export function Spinner({ className, size = 18, thickness = 2, ...props }: SpinnerProps) {
  const dim = typeof size === "number" ? `${size}px` : size;
  return (
    <div
      role="status"
      aria-label="Loading"
      className={cn("inline-block animate-spin rounded-full border-t-transparent", className)}
      style={{ width: dim, height: dim, borderWidth: thickness }}
      {...props}
    />
  );
}

