"use client";

import { useEffect, useMemo, useState } from "react";
import { apiFetch } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Spinner } from "@/components/ui/spinner";
import type { Address } from "@/types";

type Me = { user?: any };

const LS = {
  labelsKey: (uid: string) => `addr_labels:${uid}`,
  defaultKey: (uid: string) => `addr_default:${uid}`,
  readLabels(uid: string): Record<string, string> {
    try { return JSON.parse(localStorage.getItem(this.labelsKey(uid)) || '{}'); } catch { return {}; }
  },
  writeLabels(uid: string, map: Record<string, string>) { localStorage.setItem(LS.labelsKey(uid), JSON.stringify(map)); },
  readDefault(uid: string): number | null { const v = localStorage.getItem(this.defaultKey(uid)); return v ? Number(v) : null; },
  writeDefault(uid: string, id: number) { localStorage.setItem(this.defaultKey(uid), String(id)); },
};

export default function BuyerAddresses() {
  const [me, setMe] = useState<Me | null>(null);
  const uid = me?.user?.sub || "anon";
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showAdd, setShowAdd] = useState(false);
  const [creating, setCreating] = useState(false);
  const [defaultId, setDefaultId] = useState<number | null>(null);
  const [labels, setLabels] = useState<Record<string, string>>({});

  const [form, setForm] = useState({
    name: "",
    phone: "",
    address_line: "",
    city: "",
    state: "",
    pincode: "",
    label: "Home" as "Home" | "Work",
  });

  useEffect(() => {
    // Load profile and addresses
    let mounted = true;
    Promise.all([
      apiFetch('/api/me'),
      apiFetch('/api/addresses'),
    ]).then(([m, a]) => {
      if (!mounted) return;
      setMe(m as Me);
      const list = Array.isArray(a) ? (a as Address[]) : [];
      setAddresses(list);
    }).catch((e:any) => {
      setError(e?.message || 'Failed to load');
    }).finally(() => setLoading(false));
    return () => { mounted = false; };
  }, []);

  // Load labels/default after we know uid
  useEffect(() => {
    if (!uid) return;
    setLabels(LS.readLabels(uid));
    setDefaultId(LS.readDefault(uid));
  }, [uid]);

  const profile = useMemo(() => {
    const u: any = me?.user || {};
    const name = u.name || [u.given_name, u.family_name].filter(Boolean).join(' ') || u.preferred_username || '—';
    const email = u.email || '—';
    const phone = u.phone_number || u.phone || (u.attributes && (u.attributes.phone?.[0] || u.attributes.mobile?.[0])) || '—';
    return { name, email, phone };
  }, [me]);

  const setDefault = (id: number) => {
    setDefaultId(id);
    if (uid) LS.writeDefault(uid, id);
  };

  const updateLabel = (id: number, label: string) => {
    const next = { ...labels, [String(id)]: label };
    setLabels(next);
    if (uid) LS.writeLabels(uid, next);
  };

  const onCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    try {
      setCreating(true);
      const payload = { ...form } as any;
      delete (payload as any).label; // backend does not persist label
      const res = await apiFetch('/api/addresses', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      const addr = res as Address;
      setAddresses((prev) => [addr, ...prev]);
      updateLabel((addr as any).id, form.label);
      if (!defaultId) setDefault(addr.id);
      setShowAdd(false);
      setForm({ name: '', phone: '', address_line: '', city: '', state: '', pincode: '', label: 'Home' });
    } catch (e:any) {
      setError(e?.message || 'Failed to add address');
    } finally {
      setCreating(false);
    }
  };

  const onSave = async (id: number, data: Partial<Address>) => {
    setError(null);
    try {
      const payload = { ...data } as any;
      delete (payload as any).label;
      const res = await apiFetch(`/api/addresses/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      const upd = res as Address;
      setAddresses((prev) => prev.map((a) => (a.id === id ? upd : a)));
    } catch (e:any) {
      setError(e?.message || 'Failed to update address');
    }
  };

  const onDelete = async (id: number) => {
    if (!window.confirm('Delete this address?')) return;
    setError(null);
    try {
      await apiFetch(`/api/addresses/${id}`, { method: 'DELETE' });
      setAddresses((prev) => prev.filter((a) => a.id !== id));
      const next = { ...labels }; delete next[String(id)]; setLabels(next); if (uid) LS.writeLabels(uid, next);
      if (defaultId === id) setDefaultId(null);
    } catch (e:any) {
      setError(e?.message || 'Failed to delete address');
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Profile & Address</h1>
        <p className="text-sm text-muted-foreground">Manage your profile and delivery addresses.</p>
      </div>

      {/* Profile Card */}
      <section className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="rounded-[12px] border bg-white p-4 shadow-card">
          <div className="mb-3 text-sm font-medium">Profile</div>
          <div className="space-y-1 text-sm">
            <div><span className="text-muted-foreground">Name:</span> {profile.name}</div>
            <div><span className="text-muted-foreground">Email:</span> {profile.email}</div>
            <div><span className="text-muted-foreground">Phone:</span> {profile.phone}</div>
          </div>
        </div>

        {/* Add Address Card */}
        <div className="rounded-[12px] border bg-white p-4 shadow-card">
          <div className="mb-3 flex items-center justify-between">
            <div className="text-sm font-medium">Add Address</div>
            <Button size="sm" variant="outline" onClick={() => setShowAdd((s) => !s)}>{showAdd ? 'Close' : 'Add New'}</Button>
          </div>
          {showAdd && (
            <form onSubmit={onCreate} className="grid grid-cols-1 md:grid-cols-2 gap-3">
              <Input required placeholder="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
              <Input required placeholder="Phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
              <Input required placeholder="Address Line" className="md:col-span-2" value={form.address_line} onChange={(e) => setForm({ ...form, address_line: e.target.value })} />
              <Input required placeholder="City" value={form.city} onChange={(e) => setForm({ ...form, city: e.target.value })} />
              <Input required placeholder="State" value={form.state} onChange={(e) => setForm({ ...form, state: e.target.value })} />
              <Input required placeholder="Pincode" value={form.pincode} onChange={(e) => setForm({ ...form, pincode: e.target.value })} />
              <div className="md:col-span-2">
                <div className="text-xs text-muted-foreground mb-1">Label</div>
                <div className="inline-flex gap-2">
                  <Button type="button" size="sm" variant={form.label === 'Home' ? 'primary' : 'outline'} onClick={() => setForm({ ...form, label: 'Home' })}>Home</Button>
                  <Button type="button" size="sm" variant={form.label === 'Work' ? 'primary' : 'outline'} onClick={() => setForm({ ...form, label: 'Work' })}>Work</Button>
                </div>
              </div>
              <div className="md:col-span-2 flex justify-end gap-2">
                <Button type="button" size="sm" variant="ghost" onClick={() => setShowAdd(false)} disabled={creating}>Cancel</Button>
                <Button type="submit" size="sm" disabled={creating}>
                  {creating ? <span className="inline-flex items-center gap-2"><Spinner size={14} thickness={2} /> Saving…</span> : 'Save'}
                </Button>
              </div>
            </form>
          )}
        </div>
      </section>

      {/* Address Book */}
      <section className="space-y-3">
        <div className="text-sm font-medium">Address Book</div>
        {loading ? (
          <div className="py-8 flex items-center justify-center"><Spinner className="text-[#261CC1]" size={20} thickness={3} /></div>
        ) : addresses.length === 0 ? (
          <div className="text-sm text-muted-foreground">No addresses yet.</div>
        ) : (
          <ul className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {addresses.map((a) => (
              <AddressCard
                key={a.id}
                address={a}
                label={labels[String(a.id)] || 'Home'}
                isDefault={defaultId === a.id}
                onSetDefault={() => setDefault(a.id)}
                onUpdate={(payload, label) => { onSave(a.id, payload); updateLabel(a.id, label); }}
                onDelete={() => onDelete(a.id)}
              />
            ))}
          </ul>
        )}
      </section>

      {error && <div className="text-sm text-red-600">{error}</div>}
    </div>
  );
}

function AddressCard({ address, label, isDefault, onSetDefault, onUpdate, onDelete }: {
  address: Address;
  label: string;
  isDefault?: boolean;
  onSetDefault: () => void;
  onUpdate: (payload: Partial<Address>, label: string) => void;
  onDelete: () => void;
}) {
  const [editing, setEditing] = useState(false);
  const [form, setForm] = useState({
    name: address.name || '',
    phone: address.phone || '',
    address_line: address.address_line || '',
    city: address.city || '',
    state: address.state || '',
    pincode: address.pincode || '',
    label: (label as 'Home' | 'Work') || 'Home',
  });
  return (
    <li className={`rounded-[12px] border bg-white p-4 shadow-card ${isDefault ? 'ring-1 ring-[#261CC1]/30' : ''}`}>
      <div className="flex items-start justify-between gap-3">
        <div className="space-y-1">
          <div className="flex items-center gap-2">
            <span className="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs">{form.label}</span>
            {isDefault && <span className="inline-flex items-center rounded-full bg-[#BDE8F5] text-black px-2 py-0.5 text-xs">Default</span>}
          </div>
          {!editing ? (
            <>
              <div className="text-sm font-medium">{address.name} — {address.phone}</div>
              <div className="text-xs text-muted-foreground">{address.address_line}, {address.city}, {address.state} — {address.pincode}</div>
            </>
          ) : (
            <div className="grid grid-cols-1 gap-2">
              <div className="grid grid-cols-2 gap-2">
                <Input placeholder="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
                <Input placeholder="Phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
              </div>
              <Input placeholder="Address Line" value={form.address_line} onChange={(e) => setForm({ ...form, address_line: e.target.value })} />
              <div className="grid grid-cols-3 gap-2">
                <Input placeholder="City" value={form.city} onChange={(e) => setForm({ ...form, city: e.target.value })} />
                <Input placeholder="State" value={form.state} onChange={(e) => setForm({ ...form, state: e.target.value })} />
                <Input placeholder="Pincode" value={form.pincode} onChange={(e) => setForm({ ...form, pincode: e.target.value })} />
              </div>
              <div className="inline-flex gap-2">
                <Button type="button" size="xs" variant={form.label === 'Home' ? 'primary' : 'outline'} onClick={() => setForm({ ...form, label: 'Home' })}>Home</Button>
                <Button type="button" size="xs" variant={form.label === 'Work' ? 'primary' : 'outline'} onClick={() => setForm({ ...form, label: 'Work' })}>Work</Button>
              </div>
            </div>
          )}
        </div>
        <div className="flex flex-col items-end gap-2">
          <label className="inline-flex items-center gap-2 text-xs cursor-pointer select-none">
            <input type="radio" name="defaultAddress" checked={!!isDefault} onChange={onSetDefault} />
            <span>Use as default</span>
          </label>
          <div className="flex items-center gap-2">
            {!editing ? (
              <>
                <Button size="xs" variant="outline" onClick={() => setEditing(true)}>Edit</Button>
                <Button size="xs" variant="destructive" onClick={onDelete}>Delete</Button>
              </>
            ) : (
              <>
                <Button size="xs" variant="ghost" onClick={() => setEditing(false)}>Cancel</Button>
                <Button size="xs" onClick={() => { onUpdate({ name: form.name, phone: form.phone, address_line: form.address_line, city: form.city, state: form.state, pincode: form.pincode }, form.label); setEditing(false); }}>Save</Button>
              </>
            )}
          </div>
        </div>
      </div>
    </li>
  );
}
