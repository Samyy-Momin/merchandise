"use client";

import * as React from "react";

type IconButtonProps = React.ButtonHTMLAttributes<HTMLButtonElement> & {
  "aria-label": string;
  title?: string;
  variant?: "neutral" | "destructive";
  size?: number; // px for square size; defaults to 40
};

export function IconButton({
  children,
  variant = "neutral",
  size = 40,
  className = "",
  ...rest
}: IconButtonProps) {
  const base = `inline-flex items-center justify-center rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#261CC1]`;
  const color = variant === "destructive"
    ? "text-red-600 hover:text-red-700"
    : "text-gray-600 hover:text-gray-800";
  const style: React.CSSProperties = { width: size, height: size };
  return (
    <button
      type="button"
      className={`${base} ${color} ${className}`}
      style={style}
      {...rest}
    >
      {children}
    </button>
  );
}

