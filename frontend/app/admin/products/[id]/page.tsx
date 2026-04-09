"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { apiFetch } from "@/lib/api";
import type { Category, Product } from "@/types";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Spinner } from "@/components/ui/spinner";

export default function AdminProductDetail() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const idNum = Number(params?.id);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [product, setProduct] = useState<Product | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [form, setForm] = useState({ name: "", price: "", category_id: "", description: "", stock: "0", image_url: "" });
  const onChange = (k: string, v: string) => setForm((f) => ({ ...f, [k]: v }));
  const [showRestorePrompt, setShowRestorePrompt] = useState(false);
  const DRAFT_KEY = `admin:product:draft:edit:${idNum}`;

  useEffect(() => {
    let mounted = true;
    setLoading(true);
    Promise.all([
      apiFetch(`/api/products/${idNum}`),
      apiFetch('/api/categories'),
    ])
      .then(([p, c]) => {
        if (!mounted) return;
        setProduct(p as Product);
        setCategories(Array.isArray(c) ? (c as Category[]) : []);
        const categoryId = (() => {
          const cat: any = (p as any).category;
          if (cat && typeof cat === 'object' && 'id' in cat) return String(cat.id);
          return '';
        })();
        const next = {
          name: (p as Product).name || '',
          price: String((p as Product).price ?? ''),
          category_id: categoryId,
          description: ((p as any).description ?? '') as string,
          stock: String(((p as any).stock ?? '0') as any),
          image_url: ((p as any).image_url ?? '') as string,
        };
        setForm(next);
        try { if (localStorage.getItem(DRAFT_KEY)) setShowRestorePrompt(true); } catch {}
      })
      .catch((e:any) => setError(e?.message || 'Failed to load'))
      .finally(() => setLoading(false));
    return () => { mounted = false; };
  }, [idNum]);

  // Persist draft when editing and form changes (debounced)
  useEffect(() => {
    if (!editing) return;
    const t = setTimeout(() => {
      try {
        const hasAny = Object.values(form).some((v) => (v ?? '').toString().trim() !== '');
        if (hasAny) localStorage.setItem(DRAFT_KEY, JSON.stringify(form));
        else localStorage.removeItem(DRAFT_KEY);
      } catch { /* no-op */ }
    }, 400);
    return () => clearTimeout(t);
  }, [form, editing]);

  // beforeunload guard when draft exists
  useEffect(() => {
    const handler = (e: BeforeUnloadEvent) => {
      try { if (localStorage.getItem(DRAFT_KEY)) { e.preventDefault(); e.returnValue = ''; } } catch {}
    };
    window.addEventListener('beforeunload', handler);
    return () => window.removeEventListener('beforeunload', handler);
  }, [DRAFT_KEY]);

  const save = async () => {
    if (!product) return;
    setError(null);
    try {
      setSaving(true);
      const payload: any = {
        name: form.name.trim(),
        price: Number(form.price || 0),
        category_id: form.category_id ? Number(form.category_id) : undefined,
        description: form.description.trim() || undefined,
        stock: Number(form.stock || 0),
        image_url: form.image_url.trim() || undefined,
      };
      const res = await apiFetch(`/api/admin/products/${product.id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      setProduct(res?.data || res);
      setEditing(false);
      try { localStorage.removeItem(DRAFT_KEY); } catch {}
    } catch (e:any) {
      setError(e?.message || 'Failed to update product');
    } finally {
      setSaving(false);
    }
  };

  const remove = async () => {
    if (!product) return;
    if (!window.confirm('Delete this product? This cannot be undone.')) return;
    setError(null);
    try {
      setDeleting(true);
      await apiFetch(`/api/admin/products/${product.id}`, { method: 'DELETE' });
      router.replace('/admin/products');
    } catch (e:any) {
      setError(e?.message || 'Failed to delete product');
    } finally {
      setDeleting(false);
    }
  };

  if (loading) return <div className="p-6 text-sm">Loading…</div>;
  if (error) return <div className="p-6 text-sm text-red-600">{error}</div>;
  if (!product) return <div className="p-6 text-sm">Not found</div>;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold tracking-tight">Product #{product.id}</h1>
        <div className="flex items-center gap-2">
          {!editing && <Button size="sm" variant="outline" onClick={() => setEditing(true)} disabled={deleting}>Edit</Button>}
          <Button size="sm" variant="destructive" onClick={remove} disabled={deleting}>{deleting ? 'Deleting…' : 'Delete'}</Button>
        </div>
      </div>

      {!editing ? (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="md:col-span-2 space-y-2 border rounded-[12px] bg-white p-4">
            <div className="text-sm"><span className="text-muted-foreground">Name:</span> {product.name}</div>
            <div className="text-sm"><span className="text-muted-foreground">Price:</span> ₹ {Number(product.price).toFixed(2)}</div>
            <div className="text-sm"><span className="text-muted-foreground">Category:</span> {(() => { const c: any = (product as any).category; return (c && typeof c === 'object' && 'name' in c) ? c.name : (typeof c === 'string' ? c : '—'); })()}</div>
            <div className="text-sm"><span className="text-muted-foreground">Stock:</span> {(product as any).stock ?? '—'}</div>
            <div className="text-sm"><span className="text-muted-foreground">Description:</span> {product.description || '—'}</div>
            {product.image_url && (
              <div className="pt-2"><a className="text-[#261CC1] underline" href={product.image_url} target="_blank" rel="noreferrer">Image URL</a></div>
            )}
          </div>
          <div className="border rounded-[12px] bg-white p-4 flex items-center justify-center">
            <img src={product.image_url || 'https://via.placeholder.com/300'} alt="" className="w-full h-48 object-contain" />
          </div>
        </div>
      ) : (
        <div className="space-y-3 border rounded-[12px] bg-white p-4">
          {showRestorePrompt && (
            <div className="flex items-center justify-between rounded-md border p-2 text-xs">
              <div>Restore draft?</div>
              <div className="space-x-2">
                <Button size="xs" variant="outline" onClick={() => { try { const raw = localStorage.getItem(DRAFT_KEY); if (raw) setForm(JSON.parse(raw)); } catch {}; setShowRestorePrompt(false); }}>Restore</Button>
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
          <div className="flex justify-end gap-2">
            <Button size="sm" variant="ghost" onClick={() => setEditing(false)} disabled={saving}>Cancel</Button>
            <Button size="sm" variant="outline" onClick={() => { try { localStorage.removeItem(DRAFT_KEY); } catch {}; setShowRestorePrompt(false); }}>Discard draft</Button>
            <Button size="sm" onClick={save} disabled={saving}>{saving ? <span className="inline-flex items-center gap-2"><Spinner size={14} thickness={2} /> Saving…</span> : 'Save'}</Button>
          </div>
        </div>
      )}
    </div>
  );
}
