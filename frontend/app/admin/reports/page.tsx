"use client";

import { Card } from "@/components/ui/card";

export default function AdminReports() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Reports</h1>
        <p className="text-sm text-muted-foreground">View and export procurement reports.</p>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card className="p-4">
          <div className="text-sm font-medium">Order Summary</div>
          <p className="mt-1 text-sm text-muted-foreground">Overview of order volumes by status and time period.</p>
        </Card>
        <Card className="p-4">
          <div className="text-sm font-medium">Spending Report</div>
          <p className="mt-1 text-sm text-muted-foreground">Breakdown of procurement spending by category.</p>
        </Card>
        <Card className="p-4">
          <div className="text-sm font-medium">Vendor Performance</div>
          <p className="mt-1 text-sm text-muted-foreground">Vendor fulfilment rates and delivery timelines.</p>
        </Card>
        <Card className="p-4">
          <div className="text-sm font-medium">Invoice Reconciliation</div>
          <p className="mt-1 text-sm text-muted-foreground">Invoice vs order matching and discrepancy tracking.</p>
        </Card>
      </div>
      <Card className="p-6 text-center text-muted-foreground">
        <p>Detailed reports with data tables and export functionality will be added in a future phase.</p>
      </Card>
    </div>
  );
}
