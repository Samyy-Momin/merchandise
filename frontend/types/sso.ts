import type { Workspace } from "@/types/auth";

// Canonical company summary in our app
export type CompanySummary = {
  companykey: string;   // canonical C_* identifier
  companyname: string;  // display-only label
};

export type CompaniesByWorkspace = Record<Workspace, CompanySummary[]>;

// SSO Profile API response may vary; keep it permissive
export type SsoProfileResponse = {
  data?: {
    groupaccess?: GroupAccessEntry[] | Record<string, any>;
    groupAccess?: GroupAccessEntry[] | Record<string, any>;
    [key: string]: unknown;
  };
  groupaccess?: GroupAccessEntry[] | Record<string, any>;
  groupAccess?: GroupAccessEntry[] | Record<string, any>;
  [key: string]: unknown;
};

// Normalized group access entry used in derivation
export type GroupAccessEntry = {
  companykey: string;
  companyname: string;
  clients: Array<{
    clientid: string;
    clientname?: string;
    roles: string[];
  }>;
};
