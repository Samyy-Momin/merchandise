"use client";

export default function AdminDashboard() {
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold tracking-tight">Admin Dashboard</h1>
      <p className="text-sm text-muted-foreground">Quick access to orders, vendor orders, products and categories.</p>
      <ul className="list-disc text-sm pl-6 space-y-1">
        <li>Use the sidebar to navigate.</li>
      </ul>
    </div>
  );
}
