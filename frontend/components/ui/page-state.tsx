"use client";

import React from "react";

type Variant = "loading" | "empty" | "error" | "forbidden";

export function PageState({
  variant,
  title,
  description,
  children,
}: {
  variant: Variant;
  title?: string;
  description?: string;
  children?: React.ReactNode;
}) {
  const base = "rounded-[12px] border bg-white p-4 text-sm";
  const content = (
    <div className="space-y-1">
      {title && <div className="font-medium">{title}</div>}
      {description && <div className="text-muted-foreground">{description}</div>}
      {children}
    </div>
  );
  if (variant === "loading") return <div className={base}>{content || <div>Loading…</div>}</div>;
  if (variant === "empty") return <div className={base}>{content || <div>No data.</div>}</div>;
  if (variant === "forbidden") return <div className={base + " border-red-200"}>{content || <div>Access denied.</div>}</div>;
  return <div className={base + " border-red-200"}>{content || <div>Error</div>}</div>;
}

