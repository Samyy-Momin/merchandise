"use client";

import { Card } from "@/components/ui/card";

export default function AdminMonitoring() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">System Monitoring</h1>
        <p className="text-sm text-muted-foreground">Monitor system health and performance.</p>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4">
          <div className="text-sm text-muted-foreground">API Status</div>
          <div className="mt-2 text-2xl font-semibold text-green-600">Healthy</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-muted-foreground">Response Time</div>
          <div className="mt-2 text-2xl font-semibold">—</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-muted-foreground">Uptime</div>
          <div className="mt-2 text-2xl font-semibold">—</div>
        </Card>
      </div>
      <Card className="p-6 text-center text-muted-foreground">
        <p>Detailed monitoring with charts (recharts) will be added in a future phase.</p>
      </Card>
    </div>
  );
}
