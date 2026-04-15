export type Workspace = 'buyer' | 'approver' | 'vendor' | 'admin';

export const WORKSPACE_PATH: Record<Workspace, string> = {
  buyer: '/buyer',
  approver: '/approver',
  vendor: '/vendor',
  admin: '/admin',
};

export const ALL_WORKSPACES: Workspace[] = ['buyer', 'approver', 'vendor', 'admin'];

