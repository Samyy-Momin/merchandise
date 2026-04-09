"use client";

import { useEffect, useState } from "react";
import { apiFetch } from "@/lib/api";
import type { Category, Product } from "@/types";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { IconButton } from "@/components/ui/icon-button";
import { Eye, Pencil, Trash2, X } from "lucide-react";

export default function AdminProducts() {
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [detail, setDetail] = useState<Product | null>(null);
  const [mode, setMode] = useState<null | 'view' | 'edit'>(null);

  const [form, setForm] = useState({ name: "", price: "", category_id: "", description: "", stock: "", image_url: "" });
  const onChange = (k: string, v: string) => setForm((f) => ({ ...f, [k]: v }));
  const [editForm, setEditForm] = useState({ name: "", price: "", category_id: "", description: "", stock: "0", image_url: "" });
  const onEditChange = (k: string, v: string) => setEditForm((f) => ({ ...f, [k]: v }));
  const [creating, setCreating] = useState(false);
  const [updating, setUpdating] = useState(false);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [showRestorePrompt, setShowRestorePrompt] = useState(false);

  // Draft persistence for create form
  const DRAFT_KEY = 'admin:product:draft:create';
  // Show restore prompt when opening the form and draft exists
  useEffect(() => {
    if (showForm) {
      try {
        const raw = localStorage.getItem(DRAFT_KEY);
        if (raw) setShowRestorePrompt(true);
      } catch { /* no-op */ }
    } else {
      setShowRestorePrompt(false);
    }
  }, [showForm]);

  // Save draft on changes (debounced) when form open
  useEffect(() => {
    if (!showForm) return;
    const hasAny = Object.values(form).some((v) => (v ?? '').toString().trim() !== '');
    const t = setTimeout(() => {
      try {
        if (hasAny) localStorage.setItem(DRAFT_KEY, JSON.stringify(form));
        else localStorage.removeItem(DRAFT_KEY);
      } catch { /* no-op */ }
    }, 400);
    return () => clearTimeout(t);
  }, [form, showForm]);

  // beforeunload guard when draft exists
  useEffect(() => {
    const handler = (e: BeforeUnloadEvent) => {
      try { if (localStorage.getItem(DRAFT_KEY)) { e.preventDefault(); e.returnValue = ''; } } catch { /* no-op */ }
    };
    window.addEventListener('beforeunload', handler);
    return () => window.removeEventListener('beforeunload', handler);
  }, []);

  useEffect(() => {
    let mounted = true;
    Promise.all([
      apiFetch('/api/products'),
      apiFetch('/api/categories'),
    ]).then(([p, c]) => {
      if (!mounted) return;
      const list = Array.isArray(p?.data) ? (p.data as Product[]) : (p as Product[]);
      setProducts(list);
      setCategories(Array.isArray(c) ? (c as Category[]) : []);
    }).catch((e:any) => setError(e?.message || 'Failed to load')).finally(() => setLoading(false));
    return () => { mounted = false; };
  }, []);

  const submit = async () => {
    setError(null);
    try {
      setCreating(true);
      const payload = {
        name: form.name.trim(),
        price: Number(form.price || 0),
        category_id: Number(form.category_id),
        description: form.description.trim() || undefined,
        stock: Number(form.stock || 0),
        image_url: form.image_url.trim() || undefined,
      } as any;
      const res = await apiFetch('/api/admin/products', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      setProducts((prev) => [res?.data || (res as any), ...prev]);
      setShowForm(false);
      setForm({ name: "", price: "", category_id: "", description: "", stock: "", image_url: "" });
      try { localStorage.removeItem(DRAFT_KEY); } catch { /* no-op */ }
    } catch (e:any) {
      setError(e?.message || 'Failed to create product');
    } finally {
      setCreating(false);
    }
  };

  const refreshProductDetail = async (id: number) => {
    const p = await apiFetch(`/api/products/${id}`);
    setDetail(p as Product);
    return p as Product;
  };

  const viewProduct = async (id: number) => {
    setSelectedId(id);
    setMode('view');
    setError(null);
    try { await refreshProductDetail(id); } catch (e:any) { setError(e?.message || 'Failed to load product'); }
  };

  const editProduct = async (id: number) => {
    setSelectedId(id);
    setMode('edit');
    setError(null);
    try {
      const p = await refreshProductDetail(id);
      const categoryId = (() => {
        const c: any = (p as any).category;
        if (c && typeof c === 'object' && 'id' in c) return String((c as any).id);
        return '';
      })();
      setEditForm({
        name: p.name || '',
        price: String(p.price ?? ''),
        category_id: categoryId,
        description: (p.description as any) || '',
        stock: String((p as any).stock ?? '0'),
        image_url: p.image_url || '',
      });
    } catch (e:any) {
      setError(e?.message || 'Failed to load product');
    }
  };

  const updateProduct = async () => {
    if (!selectedId) return;
    setError(null);
    try {
      setUpdating(true);
      const payload: any = {
        name: editForm.name.trim(),
        price: Number(editForm.price || 0),
        category_id: editForm.category_id ? Number(editForm.category_id) : undefined,
        description: editForm.description.trim() || undefined,
        stock: Number(editForm.stock || 0),
        image_url: editForm.image_url.trim() || undefined,
      };
      const res = await apiFetch(`/api/admin/products/${selectedId}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      // Update in list
      setProducts((prev) => prev.map((p) => (p.id === selectedId ? { ...p, ...res?.data || res } : p)));
      setMode('view');
      await refreshProductDetail(selectedId);
    } catch (e:any) {
      setError(e?.message || 'Failed to update product');
    } finally {
      setUpdating(false);
    }
  };

  const deleteProduct = async (id: number) => {
    if (!window.confirm('Delete this product? This cannot be undone.')) return;
    setError(null);
    try {
      setDeletingId(id);
      await apiFetch(`/api/admin/products/${id}`, { method: 'DELETE' });
      setProducts((prev) => prev.filter((p) => p.id !== id));
      if (selectedId === id) { setSelectedId(null); setDetail(null); setMode(null); }
    } catch (e:any) {
      setError(e?.message || 'Failed to delete product');
    } finally {
      setDeletingId(null);
    }
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold tracking-tight">Products</h1>
        <Button size="sm" onClick={() => setShowForm((s) => !s)}>{showForm ? 'Close' : 'Add Product'}</Button>
      </div>
      {error && <div className="text-sm text-red-600">{error}</div>}
      {showForm && (
        <div className="border p-4 space-y-3 rounded-[12px] bg-white shadow-card">
          {showRestorePrompt && (
            <div className="flex items-center justify-between rounded-md border p-2 text-xs">
              <div>Restore draft?</div>
              <div className="space-x-2">
                <Button size="xs" variant="outline" onClick={() => {
                  try { const raw = localStorage.getItem(DRAFT_KEY); if (raw) setForm(JSON.parse(raw)); } catch {}
                  setShowRestorePrompt(false);
                }}>Restore</Button>
                <Button size="xs" variant="ghost" onClick={() => { try { localStorage.removeItem(DRAFT_KEY); } catch {}; setShowRestorePrompt(false); }}>Discard</Button>
              </div>
            </div>
          )}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            <Input placeholder="Name" value={form.name} onChange={(e) => onChange('name', e.target.value)} />
            <Input placeholder="Price" inputMode="numeric" value={form.price} onChange={(e) => onChange('price', e.target.value)} />
            <select className="h-8 rounded-lg border px-2.5 text-sm" value={form.category_id} onChange={(e) => onChange('category_id', e.target.value)}>
              <option value="">Select category</option>
              {categories.map((c) => (<option key={c.id} value={c.id}>{c.name}</option>))}
            </select>
            <Input placeholder="Stock" inputMode="numeric" value={form.stock} onChange={(e) => onChange('stock', e.target.value)} />
            <Input placeholder="Image URL" value={form.image_url} onChange={(e) => onChange('image_url', e.target.value)} />
            <Input placeholder="Description" value={form.description} onChange={(e) => onChange('description', e.target.value)} />
          </div>
          <div className="flex justify-end">
            <Button size="sm" onClick={submit} disabled={creating}>{creating ? 'Creating…' : 'Create'}</Button>
          </div>
        </div>
      )}
      {loading ? (
        <div className="text-sm">Loading…</div>
      ) : (
        <ul className="text-sm divide-y">
          {products.map((p) => {
            const isOpen = selectedId === p.id;
            return (
              <li key={p.id} className="py-2">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <img src={p.image_url || 'https://via.placeholder.com/48'} alt="" className="size-8 object-cover rounded" />
                    <div>
                      <div className="font-medium"><a className="underline" href={`/admin/products/${p.id}`}>{p.name}</a></div>
                      <div className="text-xs text-muted-foreground">₹ {Number(p.price).toFixed(2)}</div>
                    </div>
                  </div>
                  <div className="flex items-center gap-1">
                    <IconButton aria-label="View product" title="View" onClick={() => viewProduct(p.id)}>
                      <Eye size={18} />
                    </IconButton>
                    <IconButton aria-label="Edit product" title="Edit" onClick={() => editProduct(p.id)}>
                      <Pencil size={18} />
                    </IconButton>
                    <IconButton aria-label="Delete product" title="Delete" variant="destructive" onClick={() => deleteProduct(p.id)} disabled={deletingId === p.id}>
                      <Trash2 size={18} />
                    </IconButton>
                  </div>
                </div>
                {isOpen && (
                  <div className="mt-3 rounded-[12px] border bg-white p-3">
                    <div className="flex items-center justify-between mb-2">
                      <div className="text-sm font-medium">Product details</div>
                      <IconButton
                        aria-label="Close details"
                        title="Close"
                        onClick={() => { setSelectedId(null); setDetail(null); setMode(null); }}
                      >
                        <X size={18} />
                      </IconButton>
                    </div>
                    {mode === 'view' && (
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div className="md:col-span-2 space-y-1">
                          <div className="text-sm"><span className="text-muted-foreground">Name:</span> {detail?.name}</div>
                          <div className="text-sm"><span className="text-muted-foreground">Price:</span> ₹ {Number(detail?.price || 0).toFixed(2)}</div>
                          <div className="text-sm"><span className="text-muted-foreground">Category:</span> {(() => { const c: any = (detail as any)?.category; return (c && typeof c === 'object' && 'name' in c) ? c.name : (typeof c === 'string' ? c : '—'); })()}</div>
                          <div className="text-sm"><span className="text-muted-foreground">Stock:</span> {(detail as any)?.stock ?? '—'}</div>
                          <div className="text-sm"><span className="text-muted-foreground">Description:</span> {detail?.description || '—'}</div>
                          {detail?.image_url && (
                            <div className="pt-2"><a className="text-[#261CC1] underline" href={detail.image_url} target="_blank" rel="noreferrer">Image URL</a></div>
                          )}
                        </div>
                        <div className="md:col-span-1">
                          <img src={detail?.image_url || 'https://via.placeholder.com/300'} alt="" className="w-full h-40 object-contain bg-white" />
                        </div>
                      </div>
                    )}
                    {mode === 'edit' && (
                      <div className="space-y-3">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                          <Input placeholder="Name" value={editForm.name} onChange={(e) => onEditChange('name', e.target.value)} />
                          <Input placeholder="Price" inputMode="numeric" value={editForm.price} onChange={(e) => onEditChange('price', e.target.value)} />
                          <select className="h-8 rounded-lg border px-2.5 text-sm" value={editForm.category_id} onChange={(e) => onEditChange('category_id', e.target.value)}>
                            <option value="">Select category</option>
                            {categories.map((c) => (<option key={c.id} value={c.id}>{c.name}</option>))}
                          </select>
                          <Input placeholder="Stock" inputMode="numeric" value={editForm.stock} onChange={(e) => onEditChange('stock', e.target.value)} />
                          <Input placeholder="Image URL" value={editForm.image_url} onChange={(e) => onEditChange('image_url', e.target.value)} />
                          <Input placeholder="Description" value={editForm.description} onChange={(e) => onEditChange('description', e.target.value)} />
                        </div>
                        <div className="flex justify-end gap-2">
                          <Button size="sm" variant="ghost" onClick={() => setMode('view')} disabled={updating}>Cancel</Button>
                          <Button size="sm" onClick={updateProduct} disabled={updating}>{updating ? 'Saving…' : 'Save'}</Button>
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}
