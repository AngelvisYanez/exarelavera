"use client";

import { useState, useEffect, useCallback } from "react";
import dynamic from "next/dynamic";
import { moduloUsoApi } from "@/lib/api/modulo-uso";
import type { ModuloUsoData, ModuloUsoFiltros } from "@/lib/api-types";
import {
  BarChart3,
  RefreshCw,
  Search,
  Calendar,
  Users,
  Puzzle,
  MousePointerClick,
  AlertCircle,
} from "lucide-react";

const Chart = dynamic(() => import("react-apexcharts"), { ssr: false });

const COLORS = [
  "#00A1FF", "#16CDC7", "#FFB900", "#FF6692", "#7C3AED",
  "#F59E0B", "#10B981", "#EF4444", "#8B5CF6", "#EC4899",
];

export default function ModuleUsageCharts() {
  const [data, setData] = useState<ModuloUsoData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [fechaDesde, setFechaDesde] = useState(
    new Date(Date.now() - 30 * 86400000).toISOString().slice(0, 10)
  );
  const [fechaHasta, setFechaHasta] = useState(
    new Date().toISOString().slice(0, 10)
  );
  const [rucCliente, setRucCliente] = useState("");

  const cargar = useCallback(async (filtros: ModuloUsoFiltros) => {
    setLoading(true);
    setError(null);
    try {
      const res = await moduloUsoApi.obtenerStats(filtros);
      if (res.success && res.data) {
        setData(res.data);
      } else {
        setError(res.error || "Error al cargar datos de uso");
      }
    } catch (e: unknown) {
      const err = e as { message?: string };
      setError(err?.message || "Error de conexión");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargar({ fecha_desde: fechaDesde, fecha_hasta: fechaHasta });
  }, [cargar, fechaDesde, fechaHasta]);

  const handleAplicar = () => {
    const filtros: ModuloUsoFiltros = {
      fecha_desde: fechaDesde,
      fecha_hasta: fechaHasta,
    };
    if (rucCliente.trim()) filtros.ruc_cliente = rucCliente.trim();
    cargar(filtros);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "Enter") handleAplicar();
  };

  if (loading && !data) {
    return (
      <div className="flex items-center justify-center py-20">
        <div className="text-center space-y-3">
          <div className="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto" />
          <p className="text-sm text-muted-foreground font-medium">
            Cargando estadísticas de uso de módulos...
          </p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-lighterror border-l-4 border-error text-error p-4 rounded-r-md text-sm flex items-start gap-3">
        <AlertCircle className="w-5 h-5 shrink-0 mt-0.5" />
        <div>
          <strong>Error:</strong> {error}
        </div>
      </div>
    );
  }

  if (!data || data.resumen.totalAcciones === 0) {
    return (
      <div className="space-y-4">
        <FiltrosBar
          fechaDesde={fechaDesde}
          fechaHasta={fechaHasta}
          rucCliente={rucCliente}
          onFechaDesdeChange={setFechaDesde}
          onFechaHastaChange={setFechaHasta}
          onRucClienteChange={setRucCliente}
          onAplicar={handleAplicar}
          onKeyDown={handleKeyDown}
          loading={loading}
        />
        <div className="bg-muted/50 rounded-xl border border-dashed border-border text-center py-16">
          <MousePointerClick className="w-14 h-14 text-muted-foreground mx-auto mb-3" />
          <p className="font-semibold text-muted-foreground">
            No hay datos de uso en el período seleccionado
          </p>
          <p className="text-xs text-muted-foreground mt-1">
            No se encontraron registros de actividad en la base de datos
          </p>
        </div>
      </div>
    );
  }

  const topModules = data.porModulo.slice(0, 10);
  const topModulesNames = topModules.map((m) => m.nombre);
  const topModulesValues = topModules.map((m) => m.total);

  const barOptions: ApexCharts.ApexOptions = {
    chart: {
      type: "bar",
      height: 350,
      toolbar: { show: false },
      fontFamily: "inherit",
    },
    colors: [COLORS[0]],
    plotOptions: {
      bar: {
        borderRadius: 4,
        horizontal: true,
        barHeight: "70%",
      },
    },
    dataLabels: {
      enabled: true,
      formatter: (val: number) => `${val}`,
      style: { fontSize: "11px", colors: ["#fff"] },
    },
    xaxis: {
      categories: topModulesNames,
      labels: { style: { colors: "#5A6A85", fontSize: "11px" } },
    },
    yaxis: {
      labels: {
        style: { colors: "#5A6A85", fontSize: "11px" },
        maxWidth: 180,
      },
    },
    grid: {
      borderColor: "#ebf1f6",
      strokeDashArray: 5,
    },
    tooltip: {
      theme: "light",
      y: { formatter: (val: number) => `${val} acciones` },
    },
  };

  const donutLabels = data.porModulo.slice(0, 8).map((m) => m.nombre);
  const donutSeries = data.porModulo.slice(0, 8).map((m) => m.total);

  const donutOptions: ApexCharts.ApexOptions = {
    chart: { type: "donut", height: 300, fontFamily: "inherit" },
    colors: COLORS,
    labels: donutLabels,
    dataLabels: { enabled: false },
    legend: {
      position: "bottom",
      fontSize: "11px",
      labels: { colors: "#5A6A85" },
      itemMargin: { horizontal: 8 },
    },
    plotOptions: {
      pie: {
        donut: {
          size: "55%",
          labels: {
            show: true,
            total: {
              label: "Total",
              color: "#5A6A85",
              formatter: () => `${data.resumen.totalAcciones}`,
            },
          },
        },
      },
    },
    stroke: { show: false },
    tooltip: {
      theme: "light",
      y: { formatter: (val: number) => `${val} acciones` },
    },
  };

  const topUsers = data.porUsuario.slice(0, 5);
  const userSeries = topUsers.map((u) => ({
    name: u.usuario,
    data: u.modulos.slice(0, 6).map((m) => m.total),
  }));
  const userCategories = topUsers[0]?.modulos.slice(0, 6).map((m) => m.modulo) || [];

  const userBarOptions: ApexCharts.ApexOptions = {
    chart: {
      type: "bar",
      height: 300,
      toolbar: { show: false },
      fontFamily: "inherit",
      stacked: false,
    },
    colors: COLORS.slice(0, topUsers.length),
    plotOptions: {
      bar: {
        borderRadius: 3,
        columnWidth: "75%",
      },
    },
    dataLabels: { enabled: false },
    xaxis: {
      categories: userCategories,
      labels: {
        style: { colors: "#5A6A85", fontSize: "10px" },
        trim: true,
        maxHeight: 60,
      },
    },
    yaxis: {
      labels: { style: { colors: "#5A6A85", fontSize: "11px" } },
    },
    grid: {
      borderColor: "#ebf1f6",
      strokeDashArray: 5,
    },
    legend: {
      position: "bottom",
      fontSize: "11px",
      labels: { colors: "#5A6A85" },
      itemMargin: { horizontal: 6 },
    },
    tooltip: {
      theme: "light",
      y: { formatter: (val: number) => `${val} acciones` },
    },
  };

  const trendDates = data.tendencia.map((t) => {
    const d = new Date(t.fecha + "T12:00:00");
    return d.toLocaleDateString("es-ES", { day: "2-digit", month: "short" });
  });
  const trendValues = data.tendencia.map((t) => t.total);

  const trendOptions: ApexCharts.ApexOptions = {
    chart: {
      type: "area",
      height: 250,
      toolbar: { show: false },
      fontFamily: "inherit",
      zoom: { enabled: false },
    },
    colors: ["#7C3AED"],
    dataLabels: { enabled: false },
    stroke: { curve: "smooth", width: 2 },
    fill: {
      type: "gradient",
      gradient: { shadeIntensity: 0, opacityFrom: 0.15, opacityTo: 0 },
    },
    xaxis: {
      categories: trendDates,
      labels: {
        style: { colors: "#5A6A85", fontSize: "10px" },
        rotate: -45,
        rotateAlways: trendDates.length > 20,
      },
      tickAmount: 10,
    },
    yaxis: {
      labels: { style: { colors: "#5A6A85", fontSize: "11px" } },
    },
    grid: {
      borderColor: "#ebf1f6",
      strokeDashArray: 5,
    },
    tooltip: {
      theme: "light",
      y: { formatter: (val: number) => `${val} acciones` },
    },
  };

  return (
    <div className="space-y-6 animate-fade-in">
      <FiltrosBar
        fechaDesde={fechaDesde}
        fechaHasta={fechaHasta}
        rucCliente={rucCliente}
        onFechaDesdeChange={setFechaDesde}
        onFechaHastaChange={setFechaHasta}
        onRucClienteChange={setRucCliente}
        onAplicar={handleAplicar}
        onKeyDown={handleKeyDown}
        loading={loading}
      />

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div className="bg-card p-4 rounded-xl shadow-sm border border-border">
          <div className="flex items-center justify-between">
            <div>
              <span className="text-xs text-muted-foreground font-semibold uppercase tracking-wider">
                Total Acciones
              </span>
              <p className="text-2xl font-black text-dark mt-1">
                {data.resumen.totalAcciones.toLocaleString()}
              </p>
            </div>
            <div className="p-2.5 bg-lightprimary text-primary rounded-xl">
              <MousePointerClick className="w-5 h-5" />
            </div>
          </div>
        </div>
        <div className="bg-card p-4 rounded-xl shadow-sm border border-border">
          <div className="flex items-center justify-between">
            <div>
              <span className="text-xs text-muted-foreground font-semibold uppercase tracking-wider">
                Usuarios Activos
              </span>
              <p className="text-2xl font-black text-dark mt-1">
                {data.resumen.totalUsuarios}
              </p>
            </div>
            <div className="p-2.5 bg-lightsuccess text-success rounded-xl">
              <Users className="w-5 h-5" />
            </div>
          </div>
        </div>
        <div className="bg-card p-4 rounded-xl shadow-sm border border-border">
          <div className="flex items-center justify-between">
            <div>
              <span className="text-xs text-muted-foreground font-semibold uppercase tracking-wider">
                Módulos Distintos
              </span>
              <p className="text-2xl font-black text-dark mt-1">
                {data.resumen.totalModulos}
              </p>
            </div>
            <div className="p-2.5 bg-lightsecondary text-secondary rounded-xl">
              <Puzzle className="w-5 h-5" />
            </div>
          </div>
        </div>
        <div className="bg-card p-4 rounded-xl shadow-sm border border-border">
          <div className="flex items-center justify-between">
            <div>
              <span className="text-xs text-muted-foreground font-semibold uppercase tracking-wider">
                Top Módulo
              </span>
              <p className="text-lg font-black text-dark mt-1 truncate max-w-[140px]">
                {data.porModulo[0]?.nombre || "—"}
              </p>
            </div>
            <div className="p-2.5 bg-lightwarning text-warning rounded-xl">
              <BarChart3 className="w-5 h-5" />
            </div>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-card p-5 rounded-xl shadow-sm border border-border">
          <h3 className="font-bold text-dark text-sm mb-4 flex items-center gap-2">
            <BarChart3 className="w-4 h-4 text-primary" />
            Top 10 Módulos Más Usados
          </h3>
          {topModules.length > 0 ? (
            <Chart options={barOptions} series={[{ name: "Acciones", data: topModulesValues }]} type="bar" height={350} />
          ) : (
            <p className="text-sm text-muted-foreground text-center py-8">Sin datos</p>
          )}
        </div>
        <div className="bg-card p-5 rounded-xl shadow-sm border border-border">
          <h3 className="font-bold text-dark text-sm mb-4 flex items-center gap-2">
            <Puzzle className="w-4 h-4 text-secondary" />
            Distribución por Módulo
          </h3>
          {donutSeries.length > 0 ? (
            <Chart options={donutOptions} series={donutSeries} type="donut" height={300} />
          ) : (
            <p className="text-sm text-muted-foreground text-center py-8">Sin datos</p>
          )}
        </div>
      </div>

      {topUsers.length > 0 && (
        <div className="bg-card p-5 rounded-xl shadow-sm border border-border">
          <h3 className="font-bold text-dark text-sm mb-4 flex items-center gap-2">
            <Users className="w-4 h-4 text-success" />
            Uso por Usuario (Top {topUsers.length})
          </h3>
          <Chart options={userBarOptions} series={userSeries} type="bar" height={300} />
        </div>
      )}

      <div className="bg-card p-5 rounded-xl shadow-sm border border-border">
        <h3 className="font-bold text-dark text-sm mb-4 flex items-center gap-2">
          <Calendar className="w-4 h-4 text-primary" />
          Tendencia de Uso ({data.tendencia.length} días)
        </h3>
        {trendValues.length > 0 ? (
          <Chart options={trendOptions} series={[{ name: "Acciones", data: trendValues }]} type="area" height={250} />
        ) : (
          <p className="text-sm text-muted-foreground text-center py-8">Sin datos</p>
        )}
      </div>
    </div>
  );
}

function FiltrosBar({
  fechaDesde,
  fechaHasta,
  rucCliente,
  onFechaDesdeChange,
  onFechaHastaChange,
  onRucClienteChange,
  onAplicar,
  onKeyDown,
  loading,
}: {
  fechaDesde: string;
  fechaHasta: string;
  rucCliente: string;
  onFechaDesdeChange: (v: string) => void;
  onFechaHastaChange: (v: string) => void;
  onRucClienteChange: (v: string) => void;
  onAplicar: () => void;
  onKeyDown: (e: React.KeyboardEvent) => void;
  loading: boolean;
}) {
  return (
    <div className="bg-card p-4 rounded-xl shadow-sm border border-border flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-wrap">
      <div className="flex items-center gap-2">
        <Calendar className="w-4 h-4 text-muted-foreground" />
        <input
          type="date"
          value={fechaDesde}
          onChange={(e) => onFechaDesdeChange(e.target.value)}
          className="px-2.5 py-1.5 text-sm border border-border rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-card"
        />
        <span className="text-muted-foreground text-sm">a</span>
        <input
          type="date"
          value={fechaHasta}
          onChange={(e) => onFechaHastaChange(e.target.value)}
          className="px-2.5 py-1.5 text-sm border border-border rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-card"
        />
      </div>
      <div className="flex items-center gap-2 flex-1 min-w-[180px]">
        <Search className="w-4 h-4 text-muted-foreground shrink-0" />
        <input
          type="text"
          value={rucCliente}
          onChange={(e) => onRucClienteChange(e.target.value)}
          onKeyDown={onKeyDown}
          placeholder="RUC o nombre de usuario..."
          className="w-full px-2.5 py-1.5 text-sm border border-border rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-card"
        />
      </div>
      <button
        onClick={onAplicar}
        disabled={loading}
        className="flex items-center gap-1.5 px-4 py-1.5 text-sm font-semibold bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition disabled:opacity-50 shrink-0"
      >
        {loading ? (
          <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
        ) : (
          <RefreshCw className="w-4 h-4" />
        )}
        {loading ? "Cargando..." : "Aplicar Filtros"}
      </button>
    </div>
  );
}
