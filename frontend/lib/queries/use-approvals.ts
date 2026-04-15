import { useQuery } from "@tanstack/react-query";
import { apiFetch } from "@/lib/api";

export type Approval = {
  id: number;
  order_id: number;
  status: string;
  created_at: string;
  [key: string]: unknown;
};

export type ApprovalStats = {
  pending: number;
  approved: number;
  rejected: number;
  total: number;
  [key: string]: unknown;
};

export function useApprovals() {
  return useQuery<Approval[]>({
    queryKey: ["approvals"],
    queryFn: async () => {
      const res = await apiFetch("/api/approvals");
      const anyRes = res as any;
      return Array.isArray(anyRes?.data) ? anyRes.data : Array.isArray(res) ? res : [];
    },
  });
}

export function useApprovalStats() {
  return useQuery<ApprovalStats>({
    queryKey: ["approval-stats"],
    queryFn: () => apiFetch("/api/approvals/stats"),
  });
}

export function useApprovalDetail(id: number | null) {
  return useQuery<Approval>({
    queryKey: ["approval", id],
    queryFn: () => apiFetch(`/api/approvals/${id}`),
    enabled: id !== null && id > 0,
  });
}
