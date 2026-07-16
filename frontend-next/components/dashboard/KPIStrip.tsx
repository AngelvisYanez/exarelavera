"use client";

import { useEffect, useState, useRef } from "react";
import { TrendingUp, TrendingDown, Users, DollarSign, FileText, Receipt } from "lucide-react";
import { dashboardApi } from "@/lib/api";
import type { DashboardStats, ResumenMes } from "@/lib/api";

interface KPIData {
  label: string;
  value: string;
  rawValue: number;
  icon: React.ElementType;
  trend?: { value: number; positive: boolean; label: string };
  color: string;
  bgColor: string;
}

function useCountUp(target: number, duration = 1200): number {
  const [current, setCurrent] = useState(0);
  const rafRef = useRef<number | null>(null);

  useEffect(() => {
    if (target === 0) {
      setCurrent(0);
      return;
    }

    const startTime = performance.now();

    function animate(now: number) {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      // Ease-out cubic
      const eased = 1 - Math.pow(1 - progress, 3);
      setCurrent(Math.round(eased * target));

      if (progress < 1) {
        rafRef.current = requestAnimationFrame(animate);
      }
    }

    rafRef.current = requestAnimationFrame(animate);
    return () => {
      if (rafRef.current) cancelAnimationFrame(rafRef.current);
    };
  }, [target, duration]);

  return current;
}

function KPICard({ kpi, index }: { kpi: KPIData; index: number }) {
  const animatedValue = useCountUp(kpi.rawValue);
  const Icon = kpi.icon;

  const displayValue = kpi.rawValue > 0
    ? kpi.value.includes("$")
      ? `$${animatedValue.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
      : animatedValue.toLocaleString()
    : kpi.value;

  return (
    <div
      className={`animate-fade-in-up stagger-${index + 1} group relative bg-card rounded-xl border border-border/60 p-4 sm:p-5 transition-all duration-300 hover:shadow-card-hover hover:-translate-y-0.5 overflow-hidden`}
    >
      {/* Subtle accent bar at top */}
      <div
        className="absolute top-0 left-0 right-0 h-[2px] opacity-60 transition-opacity duration-300 group-hover:opacity-100"
        style={{ background: `var(--gradient-primary)` }}
        aria-hidden="true"
      />

      <div className="flex items-start justify-between">
        <div className="flex-1 min-w-0">
          <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">
            {kpi.label}
          </p>
          <p className="kpi-number text-2xl sm:text-3xl font-bold text-dark" role="status">
            {displayValue}
          </p>
          {kpi.trend && (
            <div className="flex items-center gap-1.5 mt-2">
              {kpi.trend.positive ? (
                <TrendingUp className="h-3.5 w-3.5 text-success" aria-hidden="true" />
              ) : (
                <TrendingDown className="h-3.5 w-3.5 text-destructive" aria-hidden="true" />
              )}
              <span
                className={`text-xs font-semibold ${kpi.trend.positive ? "text-success" : "text-destructive"}`}
              >
                {kpi.trend.positive ? "+" : ""}{kpi.trend.value}%
              </span>
              <span className="text-xs text-muted-foreground">{kpi.trend.label}</span>
            </div>
          )}
        </div>
        <div className={`h-11 w-11 rounded-xl ${kpi.bgColor} flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-110`}>
          <Icon className={`h-5 w-5 ${kpi.color}`} aria-hidden="true" />
        </div>
      </div>

      {/* Mini decorative sparkline */}
      <svg
        className="absolute bottom-0 left-0 right-0 h-8 w-full opacity-[0.04] pointer-events-none"
        viewBox="0 0 200 32"
        preserveAspectRatio="none"
        aria-hidden="true"
      >
        <path
          d="M0 28 Q25 20, 50 22 T100 16 T150 20 T200 12 L200 32 L0 32 Z"
          fill="currentColor"
          className={kpi.color.replace("text-", "text-")}
        />
      </svg>
    </div>
  );
}

export default function KPIStrip() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [resumen, setResumen] = useState<ResumenMes | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      dashboardApi.stats(),
      dashboardApi.resumenMes(),
    ]).then(([statsRes, resumenRes]) => {
      if (statsRes.success && statsRes.data) setStats(statsRes.data);
      if (resumenRes.success && resumenRes.data) setResumen(resumenRes.data);
    }).finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6" aria-busy="true">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="h-[120px] rounded-xl bg-card border border-border/60 animate-shimmer" />
        ))}
      </div>
    );
  }

  const ingresos = resumen?.ingresos ?? 0;
  const variacion = resumen?.variacion ?? 0;

  const kpis: KPIData[] = [
    {
      label: "Total Clientes",
      value: (stats?.totalClientes ?? 0).toLocaleString(),
      rawValue: stats?.totalClientes ?? 0,
      icon: Users,
      color: "text-success",
      bgColor: "bg-lightsuccess",
    },
    {
      label: "Ingresos del Mes",
      value: `$${ingresos.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      rawValue: ingresos,
      icon: DollarSign,
      trend: resumen ? { value: variacion, positive: variacion >= 0, label: "vs mes anterior" } : undefined,
      color: "text-primary",
      bgColor: "bg-lightprimary",
    },
    {
      label: "Manifiestos",
      value: (stats?.totalManifiestos ?? 0).toLocaleString(),
      rawValue: stats?.totalManifiestos ?? 0,
      icon: FileText,
      color: "text-info",
      bgColor: "bg-lightinfo",
    },
    {
      label: "Facturas",
      value: (stats?.totalFacturas ?? 0).toLocaleString(),
      rawValue: stats?.totalFacturas ?? 0,
      icon: Receipt,
      color: "text-warning",
      bgColor: "bg-lightwarning",
    },
  ];

  return (
    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      {kpis.map((kpi, i) => (
        <KPICard key={kpi.label} kpi={kpi} index={i} />
      ))}
    </div>
  );
}
