"use client";

import { useMemo } from "react";
import type { Workspace } from "@/types/auth";
import type { CompanySummary } from "@/types/sso";
import { setCompanyCookie } from "@/lib/company-cookie";
import { Button } from "@/components/ui/button";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Building2 } from "lucide-react";

type Props = {
  workspace: Workspace;
  companies: CompanySummary[] | null | undefined;
  selectedKey: string | null;
  onChanged?: (company_key: string) => void;
};

export function CompanySwitcher({ workspace, companies, selectedKey, onChanged }: Props) {
  const selectedName = useMemo(() => {
    const list = companies || [];
    const found = list.find((c) => c.companykey === selectedKey);
    return found?.companyname || (selectedKey ? selectedKey : "None");
  }, [companies, selectedKey]);

  const label = `${workspace.toUpperCase()} · ${selectedName}`;

  const onPick = (key: string) => {
    setCompanyCookie(workspace, key);
    if (onChanged) onChanged(key);
    else {
      // Default action: refresh current page so data can revalidate with new header
      try { window.location.reload(); } catch { /* noop */ }
    }
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" size="sm" className="gap-2">
          <Building2 className="size-4" />
          <span className="hidden sm:inline">{label}</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="min-w-[260px]">
        <div className="px-2 pt-2 pb-1 text-xs uppercase tracking-wide text-muted-foreground">Company</div>
        {Array.isArray(companies) && companies.length > 0 ? (
          companies.map((c) => (
            <DropdownMenuItem key={c.companykey} onClick={() => onPick(c.companykey)}>
              {c.companyname}
            </DropdownMenuItem>
          ))
        ) : (
          <div className="px-2 py-1.5 text-sm text-muted-foreground">No companies</div>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
