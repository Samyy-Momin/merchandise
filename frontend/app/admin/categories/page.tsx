"use client";

import { useEffect, useState } from "react";
import { apiFetch } from "@/lib/api";
import type { Category } from "@/types";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { IconButton } from "@/components/ui/icon-button";
import { Pencil, Trash2, Check, X } from "lucide-react";

export default function AdminCategories() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [name, setName] = useState("");
  const [slug, setSlug] = useState("");
  const [editingId, setEditingId] = useState<number | null>(null);
  const [editName, setEditName] = useState("");
  const [editSlug, setEditSlug] = useState("");
  const [deletingId, setDeletingId] = useState<number | null>(null);

  const load = () => {
    setLoading(true);
    apiFetch('/api/categories').then((res) => {
      setCategories(Array.isArray(res) ? res as Category[] : []);
    }).catch((e:any) => setError(e?.message || 'Failed to load')).finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const submit = async () => {
    setError(null);
    try {
      const payload: any = { name: name.trim() };
      if (slug.trim()) payload.slug = slug.trim();
      await apiFetch('/api/admin/categories', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      setName(""); setSlug("");
      load();
    } catch (e:any) {
      setError(e?.message || 'Failed to create category');
    }
  };

  const startEdit = (c: Category) => {
    setEditingId(c.id);
    setEditName(c.name || "");
    // @ts-ignore
    setEditSlug((c as any).slug || "");
  };

  const cancelEdit = () => {
    setEditingId(null);
    setEditName(""); setEditSlug("");
  };

  const saveEdit = async (id: number) => {
    setError(null);
    try {
      const payload: any = { name: editName.trim() };
      if (editSlug.trim()) payload.slug = editSlug.trim();
      await apiFetch(`/api/admin/categories/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      cancelEdit();
      load();
    } catch (e:any) {
      setError(e?.message || 'Failed to update category');
    }
  };

  const deleteCategory = async (id: number) => {
    if (!window.confirm('Delete this category? Products linked to it may block deletion.')) return;
    setError(null);
    try {
      setDeletingId(id);
      await apiFetch(`/api/admin/categories/${id}`, { method: 'DELETE' });
      // Do not optimistically remove; reload on success
      load();
    } catch (e:any) {
      // Keep row; show error from server (e.g., category in use)
      setError(e?.message || 'Failed to delete category');
    } finally {
      setDeletingId(null);
    }
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold tracking-tight">Categories</h1>
      </div>
      {error && <div className="text-sm text-red-600">{error}</div>}
      <div className="border p-4 space-y-3">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
          <Input placeholder="Name" value={name} onChange={(e) => setName(e.target.value)} />
          <Input placeholder="Slug (optional)" value={slug} onChange={(e) => setSlug(e.target.value)} />
          <Button size="sm" onClick={submit}>Add</Button>
        </div>
      </div>
      {loading ? (
        <div className="text-sm">Loading…</div>
      ) : (
        <ul className="text-sm divide-y">
          {categories.map((c) => {
            const isEditing = editingId === c.id;
            return (
              <li key={c.id} className="py-2">
                <div className="flex items-center justify-between">
                  {!isEditing ? (
                    <>
                      <div className="flex items-center gap-3">
                        <div>{c.name}</div>
                        <div className="text-xs text-muted-foreground">{(c as any).slug || ''}</div>
                      </div>
                      <div className="flex items-center gap-1">
                        <IconButton aria-label="Edit category" title="Edit" onClick={() => startEdit(c)}>
                          <Pencil size={18} />
                        </IconButton>
                        <IconButton aria-label="Delete category" title="Delete" variant="destructive" onClick={() => deleteCategory(c.id)} disabled={deletingId === c.id}>
                          <Trash2 size={18} />
                        </IconButton>
                      </div>
                    </>
                  ) : (
                    <div className="w-full grid grid-cols-1 md:grid-cols-3 gap-2 items-center">
                      <Input placeholder="Name" value={editName} onChange={(e) => setEditName(e.target.value)} />
                      <Input placeholder="Slug (optional)" value={editSlug} onChange={(e) => setEditSlug(e.target.value)} />
                      <div className="flex items-center gap-1 justify-end">
                        <IconButton aria-label="Save" title="Save" onClick={() => saveEdit(c.id)}>
                          <Check size={18} />
                        </IconButton>
                        <IconButton aria-label="Cancel" title="Cancel" onClick={cancelEdit}>
                          <X size={18} />
                        </IconButton>
                      </div>
                    </div>
                  )}
                </div>
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}
