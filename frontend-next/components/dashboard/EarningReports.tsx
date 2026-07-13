"use client";

import { useEffect, useState } from "react";
import dynamic from "next/dynamic";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { dashboardApi } from "@/lib/api";
import type { DistribucionIngreso } from "@/lib/api";

const Chart = dynamic(() => import("react-apexcharts"), { ssr: false });

const COLORS = ["#00A1FF", "#16CDC7", "#FFB900", "#FF6692", "#7C3AED", "#F59E0B"];

export default function EarningReports() {
  const [data, setData] = useState<DistribucionIngreso[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    dashboardApi.distribucionIngresos().then((res) => {
      if (res.success && res.data) setData(res.data);
    }).finally(() => setLoading(false));
  }, []);

  const totalIngresos = data.reduce((sum, d) => sum + d.ingresos, 0);
  const labels = data.map((d) => d.nombre || "Sin tipo");
  const series = data.map((d) => d.ingresos);

  const options: ApexCharts.ApexOptions = {
    chart: { type: "donut", height: 280, fontFamily: "inherit" },
    colors: COLORS,
    labels,
    dataLabels: { enabled: false },
    legend: { position: "bottom", fontSize: "13px", labels: { colors: "#5A6A85" } },
    plotOptions: {
      pie: {
        donut: {
          size: "55%",
          labels: {
            show: true,
            total: {
              label: "Total",
              color: "#5A6A85",
              formatter: () => `$${(totalIngresos / 1000).toFixed(0)}K`,
            },
          },
        },
      },
    },
    stroke: { show: false },
    tooltip: { theme: "light" },
  };

  return (
    <Card className="shadow-boxShadow border-ld">
      <CardHeader>
        <CardTitle className="card-title">Distribución de Ingresos</CardTitle>
      </CardHeader>
      <CardContent>
        {loading ? (
          <div className="h-[280px] flex items-center justify-center">
            <div className="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin" />
          </div>
        ) : series.length > 0 && series.some((s) => s > 0) ? (
          <Chart options={options} series={series} type="donut" height={280} />
        ) : (
          <div className="h-[280px] flex items-center justify-center text-muted-foreground text-sm">
            Sin datos de facturación
          </div>
        )}
      </CardContent>
    </Card>
  );
}
