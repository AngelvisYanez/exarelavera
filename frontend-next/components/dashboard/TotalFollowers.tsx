"use client";

import { useEffect, useState } from "react";
import { TrendingUp, Users } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { dashboardApi } from "@/lib/api";

export default function TotalFollowers() {
  const [total, setTotal] = useState<number | null>(null);

  useEffect(() => {
    dashboardApi.stats().then((res) => {
      if (res.success && res.data) setTotal(res.data.totalClientes);
    }).catch(() => setTotal(0));
  }, []);

  return (
    <Card className="shadow-boxShadow border-ld">
      <CardContent className="p-6">
        <div className="flex items-start justify-between">
          <div>
            <p className="text-sm font-medium text-muted-foreground">Total Clientes</p>
            <h3 className="text-3xl font-bold text-dark mt-1">
              {total !== null ? total.toLocaleString() : "---"}
            </h3>
            <div className="flex items-center gap-1 mt-2 text-sm">
              <TrendingUp className="h-4 w-4 text-success" />
              <span className="text-muted-foreground">registrados en el sistema</span>
            </div>
          </div>
          <div className="h-12 w-12 rounded-full bg-lightsuccess flex items-center justify-center">
            <Users className="h-6 w-6 text-success" />
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
