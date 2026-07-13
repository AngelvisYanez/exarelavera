"use client";

import { useState, useEffect, useCallback } from "react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Tabs, TabsList, TabsTab, TabsPanel } from "@/components/ui/tabs";
import {
  ClipboardList,
  Plus,
  RefreshCw,
  Loader2,
  AlertCircle,
  Trash2,
  UserPlus,
  UserX,
  BarChart3,
  CheckCircle2,
  Clock,
  AlertTriangle,
  Target,
  Edit,
  Users,
  FileText,
} from "lucide-react";
import { tareasApi } from "@/lib/api";
import { useConfirm } from "@/lib/hooks/use-confirm";
import type {
  Tarea,
  Empleado,
  Asignacion,
  Indicador,
  TareaAtencion,
  MetricaRendimiento,
} from "@/lib/api-types";

const ESTADO_OPTIONS = ["Pendiente", "Asignado", "En Proceso", "Finalizada"];
const PRIORIDAD_OPTIONS = ["Alta", "Media", "Baja"];

const ESTADO_COLOR: Record<string, string> = {
  Pendiente: "text-warning font-semibold",
  Asignado: "text-primary font-semibold",
  "En Proceso": "text-primary font-semibold",
  Finalizada: "text-success font-semibold",
};

function getAvanceColor(pct: number | null | undefined): string {
  if (pct === null || pct === undefined) return "bg-muted";
  if (pct < 30) return "bg-error";
  if (pct < 70) return "bg-warning";
  return "bg-primary";
}

function getAvanceTextColor(pct: number | null | undefined): string {
  if (pct === null || pct === undefined) return "text-muted-foreground";
  if (pct < 30) return "text-error";
  if (pct < 70) return "text-warning";
  return "text-success";
}

export default function TareasPage() {
  const [activeTab, setActiveTab] = useState("dashboard");
  const { confirm, ConfirmDialog } = useConfirm();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // ---- Dashboard indicators ----
  const [indicadores, setIndicadores] = useState<Indicador | null>(null);
  const [tareasAtencion, setTareasAtencion] = useState<TareaAtencion[]>([]);
  const [filtroPeriodo, setFiltroPeriodo] = useState("");

  // ---- Task list ----
  const [tareas, setTareas] = useState<Tarea[]>([]);
  const [tareasLoading, setTareasLoading] = useState(false);

  // ---- Task form ----
  const [formTarea, setFormTarea] = useState({
    Tar_Cod: 0,
    Tar_Titulo: "",
    Tar_Descripcion: "",
    Tar_Prioridad: "Media",
    Tar_Fecha_Inicio: "",
    Tar_Fecha_Fin: "",
    Tar_Estado: "Pendiente",
  });
  const [formTareaSaving, setFormTareaSaving] = useState(false);

  // ---- Employees ----
  const [empleados, setEmpleados] = useState<Empleado[]>([]);

  // ---- Assignment ----
  const [asignaciones, setAsignaciones] = useState<Asignacion[]>([]);
  const [selTareaAsig, setSelTareaAsig] = useState("");
  const [selEmpleadoAsig, setSelEmpleadoAsig] = useState("");

  // ---- Performance ----
  const [metricas, setMetricas] = useState<MetricaRendimiento[]>([]);

  // ---- Tarea disponible para asignación (sin asignación activa) ----
  const [tareasDisponibles, setTareasDisponibles] = useState<Tarea[]>([]);

  const getEmpCod = useCallback(() => {
    try {
      const info = localStorage.getItem("user_info");
      if (info) {
        const parsed = JSON.parse(info);
        return parsed.empresa_id || "";
      }
    } catch {}
    return "";
  }, []);

  const getUsuCod = useCallback(() => {
    try {
      const info = localStorage.getItem("user_info");
      if (info) {
        const parsed = JSON.parse(info);
        return parsed.usuario_id || 0;
      }
    } catch {}
    return 0;
  }, []);

  // ---- Load dashboard indicators ----
  const loadIndicadores = useCallback(async () => {
    try {
      let fecIni = "";
      let fecFin = "";
      const hoy = new Date();
      if (filtroPeriodo === "semana") {
        const d = new Date(hoy);
        d.setDate(d.getDate() - d.getDay());
        fecIni = d.toISOString().slice(0, 10);
        fecFin = hoy.toISOString().slice(0, 10);
      } else if (filtroPeriodo === "mes") {
        fecIni = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().slice(0, 10);
        fecFin = hoy.toISOString().slice(0, 10);
      } else if (filtroPeriodo === "anio") {
        fecIni = `${hoy.getFullYear()}-01-01`;
        fecFin = hoy.toISOString().slice(0, 10);
      }
      const params: Record<string, unknown> = {};
      if (fecIni) params.Fecha_Ini = fecIni;
      if (fecFin) params.Fecha_Fin = fecFin;
      const res = await tareasApi.indicadores(params);
      setIndicadores(res);
    } catch (e) {
      console.error("Error loading indicadores", e);
    }
  }, [filtroPeriodo]);

  const loadTareasAtencion = useCallback(async () => {
    try {
      const res = await tareasApi.tareasAtencion();
      setTareasAtencion(Array.isArray(res.data) ? res.data : []);
    } catch (e) {
      console.error("Error loading tareas atencion", e);
    }
  }, []);

  // ---- Load tasks list ----
  const loadTareas = useCallback(async () => {
    setTareasLoading(true);
    try {
      const res = await tareasApi.obtener({ Emp_Cod: getEmpCod() });
      setTareas(Array.isArray(res.data) ? res.data : []);
    } catch (e) {
      console.error("Error loading tareas", e);
    } finally {
      setTareasLoading(false);
    }
  }, [getEmpCod]);

  // ---- Load available tasks + employees for assignment ----
  const loadAsignacionData = useCallback(async () => {
    try {
      const [empRes, asigRes] = await Promise.all([
        tareasApi.obtenerEmpleados({ Emp_Cod: getEmpCod() }),
        tareasApi.listarAsignaciones({ Emp_Cod: getEmpCod() }),
      ]);
      setEmpleados(Array.isArray(empRes.data) ? empRes.data : []);
      setAsignaciones(Array.isArray(asigRes.data) ? asigRes.data : []);
    } catch (e) {
      console.error("Error loading asignacion data", e);
    }
  }, [getEmpCod]);

  // ---- Load performance metrics ----
  const loadMetricas = useCallback(async () => {
    try {
      const res = await tareasApi.metricasRendimiento({ Emp_Cod: getEmpCod() });
      setMetricas(Array.isArray(res.data) ? res.data : []);
    } catch (e) {
      console.error("Error loading metricas", e);
    }
  }, [getEmpCod]);

  // ---- Initial loads ----
  useEffect(() => {
    loadIndicadores();
    loadTareasAtencion();
    loadTareas();
    loadAsignacionData();
    loadMetricas();
  }, [loadIndicadores, loadTareasAtencion, loadTareas, loadAsignacionData, loadMetricas]);

  // ---- Task form handlers ----
  const resetFormTarea = () => {
    setFormTarea({
      Tar_Cod: 0,
      Tar_Titulo: "",
      Tar_Descripcion: "",
      Tar_Prioridad: "Media",
      Tar_Fecha_Inicio: "",
      Tar_Fecha_Fin: "",
      Tar_Estado: "Pendiente",
    });
  };

  const handleEditTarea = async (tarCod: number) => {
    try {
      const res = await tareasApi.obtenerPorId(tarCod);
      if (res.data?.row) {
        setFormTarea({
          Tar_Cod: res.data.row.Tar_Cod,
          Tar_Titulo: res.data.row.Tar_Titulo || "",
          Tar_Descripcion: res.data.row.Tar_Descripcion || "",
          Tar_Prioridad: res.data.row.Tar_Prioridad || "Media",
          Tar_Fecha_Inicio: res.data.row.Tar_Fecha_Inicio || "",
          Tar_Fecha_Fin: res.data.row.Tar_Fecha_Fin || "",
          Tar_Estado: res.data.row.Tar_Estado || "Pendiente",
        });
      }
    } catch (e) {
      console.error("Error loading tarea", e);
    }
  };

  const handleSaveTarea = async () => {
    if (!formTarea.Tar_Titulo.trim() || !formTarea.Tar_Fecha_Inicio) {
      setError("Título y fecha de inicio son obligatorios.");
      return;
    }
    setFormTareaSaving(true);
    setError(null);
    try {
      const data: Record<string, unknown> = {
        ...formTarea,
        Emp_Cod: getEmpCod(),
        Usu_Creador: getUsuCod(),
      };
      let res;
      if (formTarea.Tar_Cod > 0) {
        res = await tareasApi.modificar(data);
      } else {
        res = await tareasApi.crear(data);
      }
      if (res.success) {
        resetFormTarea();
        loadTareas();
        loadIndicadores();
      } else {
        setError(res.message || "Error al guardar tarea.");
      }
    } catch (e) {
      setError("Error de red al guardar tarea.");
    } finally {
      setFormTareaSaving(false);
    }
  };

  const handleDeleteTarea = async (tarCod: number) => {
    if (!(await confirm("¿Eliminar esta tarea?"))) return;
    try {
      const res = await tareasApi.eliminar(tarCod);
      if (res.success) {
        loadTareas();
        loadIndicadores();
        loadAsignacionData();
        toast.success("Tarea eliminada correctamente");
      } else {
        toast.error(res.message || "Error al eliminar tarea.");
      }
    } catch {
      toast.error("Error de red al eliminar tarea.");
    }
  };

  // ---- Assignment handlers ----
  const handleAsignar = async () => {
    const tarCod = parseInt(selTareaAsig);
    const perCod = parseInt(selEmpleadoAsig);
    if (!tarCod || !perCod) {
      toast.error("Seleccione tarea y empleado.");
      return;
    }
    setError(null);
    try {
      const res = await tareasApi.asignar(tarCod, perCod);
      if (res.success) {
        setSelTareaAsig("");
        setSelEmpleadoAsig("");
        loadAsignacionData();
        loadTareasAtencion();
        loadMetricas();
        toast.success("Tarea asignada correctamente");
      } else {
        toast.error(res.message || "Error al asignar.");
      }
    } catch {
      toast.error("Error de red al asignar.");
    }
  };

  const handleQuitarAsignacion = async (tasCod: number) => {
    if (!(await confirm("¿Quitar esta asignación?"))) return;
    try {
      const res = await tareasApi.eliminarAsignacion(tasCod);
      if (res.success) {
        loadAsignacionData();
        loadMetricas();
        toast.success("Asignación eliminada correctamente");
      } else {
        toast.error(res.message || "Error al quitar asignación.");
      }
    } catch {
      toast.error("Error de red al quitar asignación.");
    }
  };

  return (
    <div className="space-y-6 lg:space-y-8">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-lightprimary rounded-lg">
            <ClipboardList className="h-6 w-6 text-primary" />
          </div>
          <div>
            <h2 className="text-2xl font-bold text-dark">Gestión de Tareas</h2>
            <p className="text-sm text-muted-foreground">Control de personal y seguimiento de tareas</p>
          </div>
        </div>
        <Button variant="outline" size="sm" onClick={() => { loadIndicadores(); loadTareasAtencion(); loadTareas(); loadAsignacionData(); loadMetricas(); }}>
          <RefreshCw className="h-4 w-4 mr-1" /> Actualizar
        </Button>
      </div>

      {error && (
        <div className="flex items-center gap-2 p-3 bg-lighterror border border-error rounded-lg text-error text-sm">
          <AlertCircle className="h-4 w-4 flex-shrink-0" />
          <span>{error}</span>
          <Button variant="ghost" size="icon-xs" className="ml-auto" onClick={() => setError(null)}>X</Button>
        </div>
      )}

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTab value="dashboard">
            <BarChart3 className="h-4 w-4 mr-1.5" /> Dashboard General
          </TabsTab>
          <TabsTab value="gestion">
            <ClipboardList className="h-4 w-4 mr-1.5" /> Gestión de Tareas
          </TabsTab>
          <TabsTab value="asignacion">
            <Users className="h-4 w-4 mr-1.5" /> Asignación
          </TabsTab>
          <TabsTab value="reportes">
            <FileText className="h-4 w-4 mr-1.5" /> Reportes
          </TabsTab>
        </TabsList>

        {/* ---- Tab: Dashboard General ---- */}
        <TabsPanel value="dashboard">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center justify-between text-lg">
                <span>Indicadores Generales</span>
                <div className="flex items-center gap-2">
                  <Select value={filtroPeriodo} onValueChange={(v) => v && setFiltroPeriodo(v)}>
                    <SelectTrigger className="w-36 h-8 text-sm">
                      <SelectValue placeholder="Período" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">Todo</SelectItem>
                      <SelectItem value="semana">Esta semana</SelectItem>
                      <SelectItem value="mes">Este mes</SelectItem>
                      <SelectItem value="anio">Este año</SelectItem>
                    </SelectContent>
                  </Select>
                  <Button variant="outline" size="sm" onClick={loadIndicadores}>
                    <RefreshCw className="h-3 w-3 mr-1" /> Actualizar
                  </Button>
                </div>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div className="bg-lightprimary border border-primary rounded-xl p-4 text-center">
                  <div className="text-xs font-semibold text-primary uppercase tracking-wide mb-1">Total Tareas</div>
                  <div className="text-3xl font-bold text-primary">{indicadores?.Total_Tareas ?? 0}</div>
                </div>
                <div className="bg-lightsuccess border border-success rounded-xl p-4 text-center">
                  <div className="text-xs font-semibold text-success uppercase tracking-wide mb-1">Completadas</div>
                  <div className="text-3xl font-bold text-success">{indicadores?.Pct_Completadas ?? 0}%</div>
                </div>
                <div className="bg-lighterror border border-error rounded-xl p-4 text-center">
                  <div className="text-xs font-semibold text-error uppercase tracking-wide mb-1">Atrasadas</div>
                  <div className="text-3xl font-bold text-error">{indicadores?.Pct_Atrasadas ?? 0}%</div>
                </div>
                <div className="bg-lightprimary border border-primary rounded-xl p-4 text-center">
                  <div className="text-xs font-semibold text-primary uppercase tracking-wide mb-1">Rendimiento</div>
                  <div className="text-3xl font-bold text-primary">{indicadores?.Rendimiento_Promedio ?? 0}%</div>
                </div>
              </div>

              <h4 className="font-semibold text-sm mb-3 flex items-center gap-2">
                <AlertTriangle className="h-4 w-4 text-warning" />
                Tareas que requieren atención
              </h4>
              <div className="overflow-x-auto">
                <div className="min-w-[700px]">
                  <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tarea</TableHead>
                    <TableHead>Empleado</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead>Fin tentativa</TableHead>
                    <TableHead>Avance</TableHead>
                    <TableHead>Tipo</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {tareasAtencion.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center text-muted-foreground py-6">
                        No hay tareas que requieran atención
                      </TableCell>
                    </TableRow>
                  ) : (
                    tareasAtencion.map((t) => (
                      <TableRow key={t.Tar_Cod}>
                        <TableCell className="font-medium">{t.Tar_Titulo}</TableCell>
                        <TableCell>{t.Empleado_Nombre}</TableCell>
                        <TableCell>
                          <span className={ESTADO_COLOR[t.Tar_Estado] || ""}>{t.Tar_Estado}</span>
                        </TableCell>
                        <TableCell>{t.Tar_Fecha_Fin || "-"}</TableCell>
                        <TableCell>
                          <span className={getAvanceTextColor(t.Ava_Porcentaje)}>
                            {t.Ava_Porcentaje !== null && t.Ava_Porcentaje !== undefined ? `${t.Ava_Porcentaje}%` : "-"}
                          </span>
                        </TableCell>
                        <TableCell>
                          <span className={`inline-block px-2 py-0.5 rounded text-xs font-semibold ${
                            t.Tipo_Atencion === "atrasada" ? "bg-lighterror text-error" :
                            t.Tipo_Atencion === "proxima" ? "bg-lightwarning text-warning" : "bg-muted text-muted-foreground"
                          }`}>
                            {t.Tipo_Atencion === "atrasada" ? "Atrasada" : t.Tipo_Atencion === "proxima" ? "Próxima" : "Normal"}
                          </span>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                  </TableBody>
                  </Table>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsPanel>

        {/* ---- Tab: Gestión de Tareas ---- */}
        <TabsPanel value="gestion">
          <Card className="mb-6">
            <CardHeader>
              <CardTitle className="text-lg">
                {formTarea.Tar_Cod > 0 ? "Editar Tarea" : "Nueva Tarea"}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Título *</label>
                  <Input
                    value={formTarea.Tar_Titulo}
                    onChange={(e) => setFormTarea({ ...formTarea, Tar_Titulo: e.target.value })}
                    placeholder="Nombre de la tarea"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Prioridad</label>
                  <Select
                    value={formTarea.Tar_Prioridad}
                    onValueChange={(v) => setFormTarea({ ...formTarea, Tar_Prioridad: v ?? "" })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {PRIORIDAD_OPTIONS.map((p) => (
                        <SelectItem key={p} value={p}>{p}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Estado</label>
                  <Select
                    value={formTarea.Tar_Estado}
                    onValueChange={(v) => setFormTarea({ ...formTarea, Tar_Estado: v ?? "" })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {ESTADO_OPTIONS.map((e) => (
                        <SelectItem key={e} value={e}>{e}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Fecha Inicio *</label>
                  <Input
                    type="date"
                    value={formTarea.Tar_Fecha_Inicio}
                    onChange={(e) => setFormTarea({ ...formTarea, Tar_Fecha_Inicio: e.target.value })}
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Fecha Fin (tentativa)</label>
                  <Input
                    type="date"
                    value={formTarea.Tar_Fecha_Fin}
                    onChange={(e) => setFormTarea({ ...formTarea, Tar_Fecha_Fin: e.target.value })}
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Descripción</label>
                  <Input
                    value={formTarea.Tar_Descripcion}
                    onChange={(e) => setFormTarea({ ...formTarea, Tar_Descripcion: e.target.value })}
                    placeholder="Descripción opcional"
                  />
                </div>
              </div>
              <div className="flex gap-2 mt-4">
                <Button onClick={handleSaveTarea} disabled={formTareaSaving}>
                  {formTareaSaving && <Loader2 className="h-4 w-4 mr-1 animate-spin" />}
                  {formTarea.Tar_Cod > 0 ? "Actualizar" : "Guardar"}
                </Button>
                {formTarea.Tar_Cod > 0 && (
                  <Button variant="outline" onClick={resetFormTarea}>
                    Cancelar
                  </Button>
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Listado de Tareas</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto">
                <div className="min-w-[700px]">
                  <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Título</TableHead>
                    <TableHead>Prioridad</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead>Inicio</TableHead>
                    <TableHead>Fin</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {tareasLoading ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center py-6">
                        <Loader2 className="h-5 w-5 animate-spin mx-auto" />
                      </TableCell>
                    </TableRow>
                  ) : tareas.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center text-muted-foreground py-6">
                        No hay tareas registradas
                      </TableCell>
                    </TableRow>
                  ) : (
                    tareas.map((t) => (
                      <TableRow key={t.Tar_Cod}>
                        <TableCell className="font-medium">{t.Tar_Titulo}</TableCell>
                        <TableCell>{t.Tar_Prioridad}</TableCell>
                        <TableCell>
                          <span className={ESTADO_COLOR[t.Tar_Estado] || ""}>{t.Tar_Estado}</span>
                        </TableCell>
                        <TableCell>{t.Tar_Fecha_Inicio}</TableCell>
                        <TableCell>{t.Tar_Fecha_Fin || "-"}</TableCell>
                        <TableCell className="text-center">
                          <div className="flex items-center justify-center gap-1">
                            <Button variant="ghost" size="icon-sm" onClick={() => handleEditTarea(t.Tar_Cod)} title="Editar">
                              <Edit className="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="icon-sm" onClick={() => handleDeleteTarea(t.Tar_Cod)} title="Eliminar">
                              <Trash2 className="h-4 w-4 text-error" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                  </TableBody>
                  </Table>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsPanel>

        {/* ---- Tab: Asignación ---- */}
        <TabsPanel value="asignacion">
          <Card className="mb-6">
            <CardHeader>
              <CardTitle className="text-lg">Asignar Tarea a Empleado</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Tarea</label>
                  <Select value={selTareaAsig} onValueChange={(v) => v && setSelTareaAsig(v)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Seleccionar tarea..." />
                    </SelectTrigger>
                    <SelectContent>
                      {tareas
                        .filter((t) => t.Tar_Estado !== "Finalizada")
                        .map((t) => (
                          <SelectItem key={t.Tar_Cod} value={String(t.Tar_Cod)}>
                            {t.Tar_Titulo}
                          </SelectItem>
                        ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Empleado</label>
                  <Select value={selEmpleadoAsig} onValueChange={(v) => v && setSelEmpleadoAsig(v)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Seleccionar empleado..." />
                    </SelectTrigger>
                    <SelectContent>
                      {empleados.map((e) => (
                        <SelectItem key={e.Per_Cod} value={String(e.Per_Cod)}>
                          {e.Nombre}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <Button onClick={handleAsignar}>
                  <UserPlus className="h-4 w-4 mr-1" /> Asignar
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Asignaciones Actuales</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto">
                <div className="min-w-[700px]">
                  <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tarea</TableHead>
                    <TableHead>Empleado</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead>Avance</TableHead>
                    <TableHead>Fecha Asignación</TableHead>
                    <TableHead className="text-center">Acción</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {asignaciones.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center text-muted-foreground py-6">
                        No hay asignaciones
                      </TableCell>
                    </TableRow>
                  ) : (
                    asignaciones.map((a) => (
                      <TableRow key={a.Tas_Cod}>
                        <TableCell className="font-medium">{a.Tar_Titulo}</TableCell>
                        <TableCell>{a.Empleado_Nombre}</TableCell>
                        <TableCell>
                          <span className={ESTADO_COLOR[a.Tar_Estado] || ""}>{a.Tar_Estado}</span>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <div className="w-20 h-2 bg-muted rounded-full overflow-hidden">
                              <div
                                className={`h-full rounded-full ${getAvanceColor(a.Ava_Porcentaje)}`}
                                style={{ width: `${a.Ava_Porcentaje || 0}%` }}
                              />
                            </div>
                            <span className={`text-xs font-semibold ${getAvanceTextColor(a.Ava_Porcentaje)}`}>
                              {a.Ava_Porcentaje !== null && a.Ava_Porcentaje !== undefined ? `${a.Ava_Porcentaje}%` : "0%"}
                            </span>
                          </div>
                        </TableCell>
                        <TableCell className="text-sm">{a.Tas_Fecha_Asignacion}</TableCell>
                        <TableCell className="text-center">
                            <Button variant="ghost" size="icon-sm" onClick={() => handleQuitarAsignacion(a.Tas_Cod)} title="Quitar asignación">
                              <UserX className="h-4 w-4 text-error" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                  </TableBody>
                  </Table>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsPanel>

        {/* ---- Tab: Reportes ---- */}
        <TabsPanel value="reportes">
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Rendimiento por Empleado</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto">
                <div className="min-w-[700px]">
                  <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Empleado</TableHead>
                    <TableHead className="text-center">Total Tareas</TableHead>
                    <TableHead className="text-center">Completadas</TableHead>
                    <TableHead className="text-center">Atrasadas</TableHead>
                    <TableHead className="text-center">Rendimiento</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {metricas.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center text-muted-foreground py-6">
                        No hay datos de rendimiento disponibles
                      </TableCell>
                    </TableRow>
                  ) : (
                    metricas.map((m, i) => (
                      <TableRow key={m.Per_Cod || i}>
                        <TableCell className="font-medium">{m.Nombre}</TableCell>
                        <TableCell className="text-center">{m.Total_Tareas}</TableCell>
                        <TableCell className="text-center">{m.Tareas_Completadas}</TableCell>
                        <TableCell className="text-center">{m.Tareas_Atrasadas}</TableCell>
                        <TableCell className="text-center">
                          <div className="flex items-center justify-center gap-2">
                            <div className="w-24 h-2 bg-muted rounded-full overflow-hidden">
                              <div
                                className={`h-full rounded-full ${m.Rendimiento_Porcentaje >= 70 ? "bg-primary" : m.Rendimiento_Porcentaje >= 40 ? "bg-warning" : "bg-error"}`}
                                style={{ width: `${Math.min(m.Rendimiento_Porcentaje || 0, 100)}%` }}
                              />
                            </div>
                            <span className={`text-sm font-semibold ${m.Rendimiento_Porcentaje >= 70 ? "text-success" : m.Rendimiento_Porcentaje >= 40 ? "text-warning" : "text-error"}`}>
                              {m.Rendimiento_Porcentaje ?? 0}%
                            </span>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                  </TableBody>
                  </Table>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsPanel>
      </Tabs>
      {ConfirmDialog}
    </div>
  );
}
