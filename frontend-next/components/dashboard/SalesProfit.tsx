"use client";

import { useEffect, useState } from "react";
import dynamic from "next/dynamic";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { dashboardApi } from "@/lib/api";
import type { IngresoMensual } from "@/lib/api";

const Chart = dynamic(() => import("react-apexcharts"), { ssr: false });

export default function SalesProfit() {
  const [data, setData] = useState<IngresoMensual[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    dashboardApi.ingresos().then((res) => {
      if (res.success && res.data) setData(res.data);
    }).finally(() => setLoading(false));
  }, []);

  const categories = data.map((d) => d.label);
  const values = data.map((d) => d.ingresos);

  const options: ApexCharts.ApexOptions = {
    chart: { type: "area", height: 350, toolbar: { show: false }, fontFamily: "inherit" },
    colors: ["#00A1FF"],
    dataLabels: { enabled: false },
    stroke: { curve: "smooth", width: 2 },
    fill: {
      type: "gradient",
      gradient: { shadeIntensity: 0, opacityFrom: 0.15, opacityTo: 0 },
    },
    xaxis: {
      categories,
      labels: { style: { colors: "#5A6A85", fontSize: "12px" } },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      labels: {
        style: { colors: "#5A6A85", fontSize: "12px" },
        formatter: (v: number) => `$${v.toLocaleString()}`,
      },
    },
    grid: { borderColor: "#ebf1f6", strokeDashArray: 5, xaxis: { lines: { show: false } } },
    tooltip: { theme: "light" },
  };

  return (
    <Card className="shadow-boxShadow border-ld">
      <CardHeader>
        <CardTitle className="card-title">Ingresos del Año</CardTitle>
      </CardHeader>
      <CardContent>
        {loading ? (
          <div className="h-[350px] flex items-center justify-center">
            <div className="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin" />
          </div>
        ) : values.length > 0 && values.some((v) => v > 0) ? (
          <Chart options={options} series={[{ name: "Ingresos", data: values }]} type="area" height={350} />
        ) : (
          <div className="h-[350px] flex items-center justify-center text-muted-foreground text-sm">
            Sin datos de facturación para este año
          </div>
        )}
      </CardContent>
    </Card>
  );
}
