"use client";

import { useState, useEffect } from "react";
import { sriScraperApi, JobState } from "@/lib/api/sri-scraper";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Progress } from "@/components/ui/progress";
import { Loader2, Play, RefreshCw, XCircle, Download } from "lucide-react";

import { DataTable } from "@/components/ui/data-table";
import { columns } from "./columns";

export default function SriScraperPage() {
  const [jobs, setJobs] = useState<JobState[]>([]);
  const [loading, setLoading] = useState(true);
  const [creating, setCreating] = useState(false);

  const [formData, setFormData] = useState({
    ruc: "",
    clave: "",
    fecha_desde: "",
    fecha_hasta: "",
  });

  const fetchJobs = async () => {
    try {
      const res = await sriScraperApi.getJobs();
      if (res.success) {
        setJobs(res.jobs || []);
      }
    } catch (error: any) {
      toast.error(error.message || "Error al cargar trabajos del scraper");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchJobs();
    const interval = setInterval(() => {
      fetchJobs();
    }, 5000); // Poll every 5s
    return () => clearInterval(interval);
  }, []);

  const handleStartJob = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.ruc || !formData.clave || !formData.fecha_desde || !formData.fecha_hasta) {
      toast.error("Por favor, completa todos los campos.");
      return;
    }
    setCreating(true);
    try {
      const res = await sriScraperApi.createJob(formData);
      if (res.success) {
        toast.success("Trabajo de descarga iniciado");
        setFormData({ ...formData, clave: "" }); // clear password for security
        fetchJobs();
      } else {
        toast.error(res.error || "Error al iniciar el trabajo");
      }
    } catch (error: any) {
      toast.error(error.message || "Error al iniciar el trabajo");
    } finally {
      setCreating(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-2">
        <h1 className="text-3xl font-bold tracking-tight text-[#111827]">SRI Scraper</h1>
        <p className="text-[#6B7280]">
          Descarga masiva de comprobantes electrónicos desde el SRI.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card className="md:col-span-1 border-[#E5E7EB] bg-[#FFFFFF] shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all duration-500 h-fit rounded-[16px]">
          <CardHeader>
            <CardTitle className="text-[#111827]">Nueva Descarga</CardTitle>
            <CardDescription className="text-[#6B7280]">
              Configura los parámetros para iniciar la descarga masiva.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleStartJob} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="ruc" className="text-[#374151]">RUC</Label>
                <Input
                  id="ruc"
                  placeholder="Ej: 1790000000001"
                  value={formData.ruc}
                  onChange={(e) => setFormData({ ...formData, ruc: e.target.value })}
                  required
                  className="border-[#E5E7EB] focus-visible:ring-[#EF4444]"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="clave" className="text-[#374151]">Clave SRI</Label>
                <Input
                  id="clave"
                  type="password"
                  placeholder="Clave de acceso al SRI"
                  value={formData.clave}
                  onChange={(e) => setFormData({ ...formData, clave: e.target.value })}
                  required
                  className="border-[#E5E7EB] focus-visible:ring-[#EF4444]"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="fecha_desde" className="text-[#374151]">Desde</Label>
                  <Input
                    id="fecha_desde"
                    type="date"
                    value={formData.fecha_desde}
                    onChange={(e) => setFormData({ ...formData, fecha_desde: e.target.value })}
                    required
                    className="border-[#E5E7EB] focus-visible:ring-[#EF4444]"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="fecha_hasta" className="text-[#374151]">Hasta</Label>
                  <Input
                    id="fecha_hasta"
                    type="date"
                    value={formData.fecha_hasta}
                    onChange={(e) => setFormData({ ...formData, fecha_hasta: e.target.value })}
                    required
                    className="border-[#E5E7EB] focus-visible:ring-[#EF4444]"
                  />
                </div>
              </div>
              <Button type="submit" className="w-full bg-[#EF4444] hover:bg-[#DC2626] text-[#FFFFFF] shadow-[0_4px_14px_0_rgba(239,68,68,0.39)] rounded-[8px]" disabled={creating}>
                {creating ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Play className="mr-2 h-4 w-4" />}
                {creating ? "Iniciando..." : "Iniciar Descarga"}
              </Button>
            </form>
          </CardContent>
        </Card>

        <Card className="md:col-span-2 border-[#E5E7EB] bg-[#FFFFFF] shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all duration-500 rounded-[16px]">
          <CardHeader className="flex flex-row items-center justify-between border-b border-[#E5E7EB] pb-4">
            <div>
              <CardTitle className="text-[#111827]">Trabajos Recientes</CardTitle>
              <CardDescription className="text-[#6B7280]">
                Monitorea el progreso de las descargas masivas mediante TanStack Table.
              </CardDescription>
            </div>
            <Button variant="outline" size="icon" onClick={fetchJobs} disabled={loading} className="border-[#E5E7EB] hover:bg-[#F3F4F6] text-[#4B5563]">
              <RefreshCw className={`h-4 w-4 ${loading ? "animate-spin" : ""}`} />
            </Button>
          </CardHeader>
          <CardContent className="pt-6">
            {loading && jobs.length === 0 ? (
              <div className="flex justify-center p-8">
                <Loader2 className="h-8 w-8 animate-spin text-[#EF4444]" />
              </div>
            ) : (
              <DataTable columns={columns(fetchJobs)} data={jobs} searchKey="status" searchPlaceholder="Buscar por estado..." />
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
