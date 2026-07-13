"use client";

import { useEffect, useState } from "react";
import { TrendingUp, TrendingDown, DollarSign } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { dashboardApi } from "@/lib/api";
import type { ResumenMes } from "@/lib/api";

export default function TotalIncome() {
  const [data, setData] = useState<ResumenMes | null>(null);

  useEffect(() => {
    dashboardApi.resumenMes().then((res) => {
      if (res.success && res.data) setData(res.data);
    });
  }, []);

  const ingresos = data?.ingresos ?? 0;
  const variacion = data?.variacion ?? 0;
  const isPositive = variacion >= 0;

  return (
    <Card className="shadow-boxShadow border-ld">
      <CardContent className="p-6">
        <div className="flex items-start justify-between">
          <div>
            <p className="text-sm font-medium text-muted-foreground">Ingresos del Mes</p>
            <h3 className="text-3xl font-bold text-dark mt-1">
              {data ? `$${ingresos.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : "---"}
            </h3>
            {data && (
              <div className="flex items-center gap-1 mt-2 text-sm">
                {isPositive ? (
                  <TrendingUp className="h-4 w-4 text-success" />
                ) : (
                  <TrendingDown className="h-4 w-4 text-destructive" />
                )}
                <span className={`font-semibold ${isPositive ? "text-success" : "text-destructive"}`}>
                  {isPositive ? "+" : ""}{variacion}%
                </span>
                <span className="text-muted-foreground">vs mes anterior</span>
              </div>
            )}
          </div>
          <div className="h-12 w-12 rounded-full bg-lightprimary flex items-center justify-center">
            <DollarSign className="h-6 w-6 text-primary" />
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
