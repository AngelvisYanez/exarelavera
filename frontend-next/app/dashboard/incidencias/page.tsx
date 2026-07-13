"use client";

import { useState, useEffect, useCallback } from "react";
import Link from "next/link";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { conexionApi } from "@/lib/api/conexion";
import { useConfirm } from "@/lib/hooks/use-confirm";
import ModuleUsageCharts from "@/components/dashboard/ModuleUsageCharts";
import { moduloUsoApi } from "@/lib/api/modulo-uso";
import type {
  ConexionPerfil,
  EstadoConexion,
  ConexionPerfilCompleto,
} from "@/lib/api-types";
import {
  ShieldAlert,
  Activity,
  CheckCircle,
  Clock,
  Server,
  Database,
  Cpu,
  Globe,
  RefreshCw,
  ArrowRight,
  Terminal,
  Lock,
  User,
  KeyRound,
  FileCode,
  Bug,
  Sparkles,
  Layers,
  ExternalLink,
  ClipboardList,
  Settings,
  Wifi,
  WifiOff,
  Trash2,
  Plug,
  Save,
  Plus,
  BarChart3,
} from "lucide-react";

// Tipado de incidencias de GlitchTip
interface Incident {
  id: string;
  title: string;
  culprit: string;
  level: "fatal" | "error" | "warning" | "info";
  count: number;
  usersAffected: number;
  lastSeen: string;
  status: "unresolved" | "resolved" | "ignored";
  stackTrace: string[];
  browser: string;
  os: string;
}

// Datos iniciales vacíos - se cargan desde GlitchTip/Uptime Kuma en producción
const INITIAL_INCIDENTS: Incident[] = [];

export default function IncidenciasPage() {
  // Estado de Autenticación del Superadmin (Iniciado perezosamente para evitar renders extra)
  const [isSuperAdmin, setIsSuperAdmin] = useState<boolean>(() => {
    if (typeof window !== "undefined") {
      const savedSession = localStorage.getItem("exa_superadmin_session");
      return !!savedSession && savedSession.length > 0;
    }
    return false;
  });
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [authError, setAuthError] = useState<string | null>(null);
  const [authLoading, setAuthLoading] = useState(false);

  const { confirm, ConfirmDialog } = useConfirm();

  // Estados del Monitor
  const [incidents, setIncidents] = useState<Incident[]>(INITIAL_INCIDENTS);
  const [selectedIncident, setSelectedIncident] = useState<Incident | null>(
    null,
  );
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [filter, setFilter] = useState<"all" | "fatal" | "error" | "warning">(
    "all",
  );
  const [systemUptime, setSystemUptime] = useState<number | null>(null);
  const [activeTab, setActiveTab] = useState<
    "glitchtip" | "kuma" | "playwright" | "conexion" | "modulos"
  >("glitchtip");

  // Estados para la ejecución de Playwright On-Demand
  const [playwrightReportUrl, setPlaywrightReportUrl] = useState<string | null>(
    null,
  );
  const [isPlayingTest, setIsPlayingTest] = useState(false);
  const [playwrightMessage, setPlaywrightMessage] = useState<{
    type: "success" | "error";
    text: string;
  } | null>(null);

  // Estados para el Agente AI Gemini Auto-Fixer
  const [isAnalyzingError, setIsAnalyzingError] = useState(false);
  const [aiFixResult, setAiFixResult] = useState<{
    explanation: string;
    suggestedFix: string;
    fullCorrectedCode: string;
    diff: string;
    warning?: string;
  } | null>(null);
  const [isApplyingFix, setIsApplyingFix] = useState(false);
  const [fixSuccessMessage, setFixSuccessMessage] = useState<string | null>(
    null,
  );
  const [aiError, setAiError] = useState<string | null>(null);

  // Estados para la gestión de conexión a base de datos
  const [connEstado, setConnEstado] = useState<EstadoConexion | null>(null);
  const [connPerfiles, setConnPerfiles] = useState<ConexionPerfil[]>([]);
  const [connLoading, setConnLoading] = useState(false);
  const [connError, setConnError] = useState<string | null>(null);
  const [connSuccess, setConnSuccess] = useState<string | null>(null);
  const [connTesting, setConnTesting] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [formNombre, setFormNombre] = useState("");
  const [formHost, setFormHost] = useState("");
  const [formPort, setFormPort] = useState("3306");
  const [formUser, setFormUser] = useState("");
  const [formPass, setFormPass] = useState("");
  const [formDb, setFormDb] = useState("");
  const [formTestResult, setFormTestResult] = useState<{
    success: boolean;
    message?: string;
    error?: string;
  } | null>(null);
  const [formTesting, setFormTesting] = useState(false);

  const loadConexionData = useCallback(async () => {
    setConnLoading(true);
    setConnError(null);
    try {
      const [estado, perfiles] = await Promise.all([
        conexionApi.estado(),
        conexionApi.perfiles(),
      ]);
      setConnEstado(estado);
      setConnPerfiles(perfiles.perfiles);
    } catch (e: unknown) {
      const err = e as { message?: string };
      setConnError(err?.message || "Error al cargar datos de conexión");
    } finally {
      setConnLoading(false);
    }
  }, []);

  // --- CAMBIO: Se eliminó el useEffect automático para tener control manual ---

  // Lanzar el agente Gemini para analizar y sugerir una solución
  const handleRunAiAgentFix = async (incident: Incident) => {
    setIsAnalyzingError(true);
    setAiError(null);
    setAiFixResult(null);
    setFixSuccessMessage(null);

    try {
      const response = await fetch("/api/admin/ai-fix", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "suggest",
          title: incident.title,
          culprit: incident.culprit,
          stackTrace: incident.stackTrace,
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        setAiFixResult(data);
      } else {
        setAiError(
          data.error || "Ocurrió un error al contactar al Agente de IA.",
        );
      }
    } catch (error) {
      setAiError("Error de red: No se pudo contactar con el Agente de IA.");
    } finally {
      setIsAnalyzingError(false);
    }
  };

  // Aplicar el fix de manera física escribiendo el archivo en disco
  const handleApplyAiPatch = async (incident: Incident) => {
    if (!aiFixResult) return;
    setIsApplyingFix(true);
    setAiError(null);

    try {
      const response = await fetch("/api/admin/ai-fix", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "apply",
          culprit: incident.culprit,
          suggestedCode: aiFixResult.fullCorrectedCode,
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        setFixSuccessMessage(data.message);
        // Marcamos la incidencia como resuelta en la UI local
        resolveIncident(incident.id);
      } else {
        setAiError(
          data.error || "No se pudo aplicar la corrección de forma automática.",
        );
      }
    } catch (error) {
      setAiError(
        "Error de red: No se pudo aplicar el parche en el archivo de origen.",
      );
    } finally {
      setIsApplyingFix(false);
    }
  };

  // Lanzar ejecución de Playwright desde el propio Dashboard
  const handleRunPlaywrightTest = async () => {
    setIsPlayingTest(true);
    setPlaywrightMessage(null);
    setPlaywrightReportUrl(null);

    try {
      const response = await fetch("/api/admin/run-test", {
        method: "POST",
      });
      const data = await response.json();

      if (response.ok && data.success) {
        setPlaywrightMessage({ type: "success", text: data.message });
        setPlaywrightReportUrl(data.reportUrl + "?t=" + Date.now());
      } else {
        setPlaywrightMessage({
          type: "error",
          text: data.error || "Se reportaron anomalías durante las pruebas.",
        });
        if (data.reportUrl) {
          setPlaywrightReportUrl(data.reportUrl + "?t=" + Date.now());
        }
      }
    } catch (error) {
      setPlaywrightMessage({
        type: "error",
        text: "Error de red: no se pudo establecer conexión para ejecutar las pruebas.",
      });
    } finally {
      setIsPlayingTest(false);
    }
  };

  // Función de Login Superadmin
  const handleSuperAdminLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setAuthLoading(true);
    setAuthError(null);

    try {
      const response = await fetch("/api/admin/verify", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password }),
      });

      const data = await response.json();

      if (data.success) {
        localStorage.setItem("exa_superadmin_session", data.token);
        setIsSuperAdmin(true);
      } else {
        setAuthError(data.error || "Credenciales inválidas");
      }
    } catch (err) {
      setAuthError("Error de conexión con el servicio de autenticación");
    } finally {
      setAuthLoading(false);
    }
  };

  const handleLogoutSuperadmin = () => {
    localStorage.removeItem("exa_superadmin_session");
    setIsSuperAdmin(false);
    setUsername("");
    setPassword("");
  };

  // Refresco de datos desde GlitchTip/Uptime Kuma
  const handleRefresh = async () => {
    setIsRefreshing(true);
    try {
      const response = await fetch("/api/admin/incidents");
      if (response.ok) {
        const data = await response.json();
        if (data.incidents) setIncidents(data.incidents);
        if (data.uptime !== undefined) setSystemUptime(data.uptime);
      }
    } catch {
      // Silenciar errores de refresco silencioso
    } finally {
      setIsRefreshing(false);
    }
  };

  // Resolver incidencia
  const resolveIncident = (id: string) => {
    setIncidents(
      incidents.map((inc) =>
        inc.id === id ? { ...inc, status: "resolved" } : inc,
      ),
    );
    if (selectedIncident?.id === id) {
      setSelectedIncident(null);
    }
  };

  // Filtrado de incidencias
  const filteredIncidents = incidents.filter((inc) => {
    if (filter === "all") return inc.status === "unresolved";
    return inc.level === filter && inc.status === "unresolved";
  });

  // --- VISTA GATED (LOGIN DE SUPERADMIN) ---
  if (!isSuperAdmin) {
    return (
      <div className="min-h-[80vh] flex items-center justify-center px-4 bg-muted/50">
        <div className="bg-card p-8 rounded-xl shadow-lg border border-border w-full max-w-md">
          <div className="text-center mb-6">
            <div className="mx-auto w-16 h-16 bg-lighterror text-error rounded-full flex items-center justify-center mb-4">
              <ShieldAlert className="w-9 h-9" />
            </div>
            <h2 className="text-2xl font-bold text-dark">
              Acceso Restringido
            </h2>
            <p className="text-sm text-muted-foreground mt-2">
              Este panel contiene reportes críticos del sistema y métricas en
              tiempo real. Por favor, identifíquese como{" "}
              <strong>Superadmin</strong>.
            </p>
          </div>

          {authError && (
            <div className="bg-lighterror border-l-4 border-error text-error p-3 rounded-r-md mb-4 text-xs">
              <div className="font-semibold">Error de autenticación</div>
              <div>{authError}</div>
            </div>
          )}

          <form onSubmit={handleSuperAdminLogin} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">
                Usuario de Soporte
              </label>
              <div className="relative">
                <span className="absolute inset-y-0 left-0 pl-3 flex items-center text-muted-foreground">
                  <User className="w-5 h-5" />
                </span>
                <input
                  type="text"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-border rounded-lg focus:ring-ring focus:border-error text-foreground text-sm"
                  placeholder="ej. exacontable"
                  required
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">
                Contraseña Especial
              </label>
              <div className="relative">
                <span className="absolute inset-y-0 left-0 pl-3 flex items-center text-muted-foreground">
                  <KeyRound className="w-5 h-5" />
                </span>
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-border rounded-lg focus:ring-ring focus:border-error text-foreground text-sm"
                  placeholder="••••••••"
                  required
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={authLoading}
              className="w-full mt-2 bg-destructive hover:bg-destructive/90 text-white font-bold py-2.5 px-4 rounded-lg shadow-md transition disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <Lock className="w-4 h-4" />
              {authLoading ? "Verificando..." : "Desbloquear Monitor"}
            </button>
          </form>

          <div className="mt-6 border-t pt-4 text-center">
            <p className="text-xs text-muted-foreground">
              EXA Contable Relavera Security Panel • 2026
            </p>
          </div>
        </div>
      </div>
    );
  }

  // --- DASHBOARD DE CONTROL DE INCIDENCIAS ---
  return (
    <div className="space-y-6 lg:space-y-8">
      {/* Encabezado Principal */}
      <div className="bg-card p-6 rounded-xl shadow-sm border border-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <div className="p-1.5 bg-lighterror text-error rounded-lg">
              <ShieldAlert className="w-6 h-6" />
            </div>
            <h1 className="text-2xl font-bold text-dark">
              Monitor de Incidencias & Disponibilidad
            </h1>
          </div>
          <p className="text-sm text-muted-foreground mt-1">
            Visualización integrada de fallas en producción con{" "}
            <span className="font-semibold text-secondary">GlitchTip</span> y
            latencias con{" "}
            <span className="font-semibold text-success">Uptime Kuma</span>.
          </p>
        </div>

        <div className="flex items-center gap-2 self-start md:self-auto">
          <button
            onClick={handleRefresh}
            disabled={isRefreshing}
            className="flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold border rounded-lg text-muted-foreground hover:bg-accent hover:text-accent-foreground transition disabled:opacity-50"
          >
            <RefreshCw
              className={`w-4 h-4 ${isRefreshing ? "animate-spin" : ""}`}
            />
            {isRefreshing ? "Actualizando..." : "Refrescar"}
          </button>
          <button
            onClick={handleLogoutSuperadmin}
            className="px-3 py-1.5 text-sm font-semibold bg-muted hover:bg-muted text-dark rounded-lg transition"
          >
            Salir de Superadmin
          </button>
        </div>
      </div>

      {/* Selector de Herramientas de Monitoreo */}
      <div className="flex border-b border-border">
        <button
          onClick={() => setActiveTab("glitchtip")}
          className={`pb-3 px-6 font-semibold text-sm flex items-center gap-2 border-b-2 transition ${
            activeTab === "glitchtip"
              ? "border-secondary text-secondary"
              : "border-transparent text-muted-foreground hover:text-dark"
          }`}
        >
          <Bug className="w-4 h-4" />
          GlitchTip Exceptions (Errores de Código)
          <span className="px-2 py-0.5 bg-lighterror text-error rounded-full text-xs font-bold">
            {incidents.filter((i) => i.status === "unresolved").length}
          </span>
        </button>
        <button
          onClick={() => setActiveTab("kuma")}
          className={`pb-3 px-6 font-semibold text-sm flex items-center gap-2 border-b-2 transition ${
            activeTab === "kuma"
              ? "border-success text-success"
              : "border-transparent text-muted-foreground hover:text-dark"
          }`}
        >
          <Activity className="w-4 h-4" />
          Uptime Kuma (Módulos & APIs)
          <span className="w-2.5 h-2.5 bg-primary rounded-full animate-pulse" />
        </button>
        <button
          onClick={() => setActiveTab("playwright")}
          className={`pb-3 px-6 font-semibold text-sm flex items-center gap-2 border-b-2 transition ${
            activeTab === "playwright"
              ? "border-error text-error"
              : "border-transparent text-muted-foreground hover:text-dark"
          }`}
        >
          <Sparkles className="w-4 h-4" />
          Playwright On-Demand
          {isPlayingTest && (
            <span className="w-2 h-2 bg-lighterror0 rounded-full animate-ping" />
          )}
        </button>
        <button
          onClick={() => { setActiveTab("conexion"); loadConexionData(); }}
          className={`pb-3 px-6 font-semibold text-sm flex items-center gap-2 border-b-2 transition ${
            activeTab === "conexion"
              ? "border-primary text-primary"
              : "border-transparent text-muted-foreground hover:text-dark"
          }`}
        >
          <Database className="w-4 h-4" />
          Conexión BD
          {connEstado && (
            <span className={`w-2 h-2 rounded-full ${connEstado.conectado ? "bg-primary" : "bg-lighterror0"}`} />
          )}
        </button>
        <button
          onClick={() => setActiveTab("modulos")}
          className={`pb-3 px-6 font-semibold text-sm flex items-center gap-2 border-b-2 transition ${
            activeTab === "modulos"
              ? "border-primary text-primary"
              : "border-transparent text-muted-foreground hover:text-dark"
          }`}
        >
          <BarChart3 className="w-4 h-4" />
          Uso de Módulos
        </button>
        <Link
          href="/dashboard/tareas"
          className={`pb-3 px-6 font-semibold text-sm flex items-center gap-2 border-b-2 transition border-transparent text-muted-foreground hover:text-primary hover:border-primary`}
        >
          <ClipboardList className="w-4 h-4" />
          Gestión de Tareas
        </Link>
      </div>

      {/* --- PESTAÑA GLITCHTIP: EXCEPCIONES Y ERRORES --- */}
      {activeTab === "glitchtip" && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Listado de Excepciones */}
          <div className="lg:col-span-2 space-y-4">
            <div className="bg-card p-4 rounded-xl shadow-sm border border-border">
              <div className="flex items-center justify-between mb-4">
                <h3 className="font-bold text-dark">
                  Incidentes no resueltos
                </h3>
                <div className="flex items-center gap-1.5 text-xs">
                  <span className="text-muted-foreground font-medium">
                    Filtrar por:
                  </span>
                  {(["all", "fatal", "error", "warning"] as const).map(
                    (lvl) => (
                      <button
                        key={lvl}
                        onClick={() => setFilter(lvl)}
                        className={`capitalize px-2 py-1 rounded font-semibold border transition ${
                          filter === lvl
                            ? "bg-lightsecondary text-secondary border-secondary/30"
                            : "bg-card hover:bg-accent hover:text-accent-foreground text-muted-foreground"
                        }`}
                      >
                        {lvl === "all" ? "Todos" : lvl}
                      </button>
                    ),
                  )}
                </div>
              </div>

              {filteredIncidents.length === 0 ? (
                <div className="text-center py-12 bg-muted/50 rounded-lg border border-dashed border-border">
                  <CheckCircle className="w-12 h-12 text-success mx-auto mb-2" />
                  <p className="font-semibold text-dark">
                    ¡Excelente! Cero incidencias críticas
                  </p>
                  <p className="text-xs text-muted-foreground mt-1">
                    Todas las alertas de GlitchTip han sido resueltas o
                    silenciadas.
                  </p>
                </div>
              ) : (
                <div className="space-y-3">
                  {filteredIncidents.map((inc) => (
                    <div
                      key={inc.id}
                      onClick={() => setSelectedIncident(inc)}
                      className={`p-4 rounded-lg border transition cursor-pointer flex flex-col md:flex-row md:items-center justify-between gap-4 ${
                        selectedIncident?.id === inc.id
                          ? "border-secondary bg-lightsecondary/20 shadow-sm"
                          : "border-border hover:border-border hover:bg-accent/50 hover:text-accent-foreground bg-card"
                      }`}
                    >
                      <div className="space-y-1.5 flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                          <span
                            className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                              inc.level === "fatal"
                                ? "bg-lighterror text-error border border-error/30"
                                : inc.level === "error"
                                  ? "bg-lightwarning text-warning border border-warning/30"
                                  : "bg-lightwarning text-warning border border-warning/30"
                            }`}
                          >
                            {inc.level}
                          </span>
                          <span className="text-xs text-muted-foreground font-medium">
                            {inc.culprit}
                          </span>
                        </div>
                        <h4 className="font-bold text-dark text-sm truncate leading-snug">
                          {inc.title}
                        </h4>
                        <div className="flex items-center gap-3 text-xs text-muted-foreground">
                          <span className="flex items-center gap-1">
                            <Clock className="w-3.5 h-3.5" />
                            {inc.lastSeen}
                          </span>
                          <span>
                            <strong>{inc.count}</strong> Eventos
                          </span>
                          <span>
                            <strong>{inc.usersAffected}</strong> Usuarios
                          </span>
                        </div>
                      </div>
                      <div className="flex items-center self-end md:self-auto">
                        <ArrowRight className="w-5 h-5 text-muted-foreground group-hover:text-secondary transition" />
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* Panel Lateral de Detalle de Incidencia Seleccionada */}
          <div className="space-y-4">
            {selectedIncident ? (
              <div className="bg-card p-5 rounded-xl shadow-sm border border-border space-y-4 sticky top-6">
                <div className="flex items-center justify-between">
                  <h3 className="font-bold text-dark flex items-center gap-1.5 text-sm">
                    <Terminal className="w-4 h-4 text-secondary" />
                    Detalle de Excepción
                  </h3>
                  <button
                    onClick={() => resolveIncident(selectedIncident.id)}
                    className="text-xs font-semibold text-success hover:text-success/80 bg-lightsuccess border border-success/30 px-2 py-1 rounded"
                  >
                    Marcar Resuelto
                  </button>
                </div>

                <div className="space-y-2">
                  <h4 className="text-base font-extrabold text-error font-mono break-words bg-lighterror/50 p-2 rounded">
                    {selectedIncident.title}
                  </h4>
                  <p className="text-xs text-muted-foreground font-semibold">
                    Localización:{" "}
                    <span className="font-mono text-dark">
                      {selectedIncident.culprit}
                    </span>
                  </p>
                </div>

                {/* Datos del Cliente Afectado */}
                <div className="grid grid-cols-2 gap-3 p-3 bg-muted/50 rounded-lg text-xs">
                  <div>
                    <span className="text-muted-foreground block font-medium">
                      Navegador del Cliente:
                    </span>
                    <span className="text-dark font-bold">
                      {selectedIncident.browser}
                    </span>
                  </div>
                  <div>
                    <span className="text-muted-foreground block font-medium">
                      Sistema Operativo:
                    </span>
                    <span className="text-dark font-bold">
                      {selectedIncident.os}
                    </span>
                  </div>
                </div>

                {/* Stack Trace */}
                <div className="space-y-1.5">
                  <span className="text-xs font-bold text-muted-foreground block">
                    Trazas de Pila (Stack Trace):
                  </span>
                  <div className="bg-dark p-3 rounded-lg overflow-x-auto max-h-48 text-[11px] font-mono text-success leading-normal border border-dark/80">
                    {selectedIncident.stackTrace.map((line, i) => (
                      <div key={i} className="whitespace-nowrap">
                        {line}
                      </div>
                    ))}
                  </div>
                </div>

                {/* Agente de Autocorrección de Inteligencia Artificial (Gemini Agent) */}
                <div className="p-4 bg-gradient-to-br from-gray-900 to-slate-950 border border-secondary/40 rounded-lg space-y-3 shadow-lg text-white">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-1.5">
                      <Sparkles className="w-4 h-4 text-secondary animate-pulse" />
                      <span className="text-xs font-bold text-secondary">
                        Agente Auto-Fixer
                      </span>
                    </div>
                    {aiFixResult && (
                      <button
                        onClick={() => {
                          setAiFixResult(null);
                          setFixSuccessMessage(null);
                        }}
                        className="text-[10px] text-muted-foreground hover:text-white underline font-semibold"
                      >
                        Limpiar Diagnóstico
                      </button>
                    )}
                  </div>

                  {!aiFixResult && !isAnalyzingError && !fixSuccessMessage && (
                    <>
                      <p className="text-[11px] text-muted-foreground leading-relaxed">
                        Ejecuta nuestro agente de IA basado en{" "}
                        <strong>Gemini</strong> para analizar la traza,
                        identificar la línea exacta de código bugueada en tu
                        archivo{" "}
                        <span className="font-mono text-secondary">
                          {selectedIncident.culprit.split(" ")[0]}
                        </span>
                        , y generar un parche de autocorrección automatizado.
                      </p>
                      <button
                        onClick={() => handleRunAiAgentFix(selectedIncident)}
                        className="w-full bg-gradient-to-r bg-secondary hover:bg-secondary/90 text-white font-bold py-2 px-3 rounded text-xs transition shadow-md flex items-center justify-center gap-1.5"
                      >
                        <Bug className="w-3.5 h-3.5 animate-bounce" />
                        Ejecutar Diagnóstico & Proponer Fix
                      </button>
                    </>
                  )}

                  {isAnalyzingError && (
                    <div className="py-4 text-center space-y-2.5">
                      <div className="w-6 h-6 border-2 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto" />
                      <div className="space-y-0.5">
                        <p className="text-xs font-bold text-secondary">
                          Agente IA analizando código fuente...
                        </p>
                        <p className="text-[10px] text-muted-foreground max-w-xs mx-auto">
                          Escanenado el workspace, localizando el archivo y
                          diagnosticando la raíz del bug con Gemini.
                        </p>
                      </div>
                    </div>
                  )}

                  {aiError && (
                    <div className="bg-lighterror border border-error/50 text-error/80 p-2.5 rounded text-xs leading-relaxed">
                      <strong>Fallo del Agente:</strong> {aiError}
                    </div>
                  )}

                  {aiFixResult && !fixSuccessMessage && (
                    <div className="space-y-3 text-xs border-t border-secondary/50 pt-3 animate-fade-in">
                      {aiFixResult.warning && (
                        <div className="bg-lightwarning border border-warning/40 text-warning p-2 rounded text-[10px] leading-relaxed">
                          ⚠️ <strong>Nota:</strong> {aiFixResult.warning}
                        </div>
                      )}

                      <div className="space-y-1">
                        <span className="font-bold text-secondary block text-[11px] uppercase tracking-wider">
                          Causa Raíz Diagnosticada:
                        </span>
                        <p className="text-muted-foreground text-[11px] leading-relaxed">
                          {aiFixResult.explanation}
                        </p>
                      </div>

                      <div className="space-y-1">
                        <span className="font-bold text-secondary block text-[11px] uppercase tracking-wider">
                          Cambio Sugerido:
                        </span>
                        <p className="text-muted-foreground text-[11px] leading-relaxed font-semibold">
                          {aiFixResult.suggestedFix}
                        </p>
                      </div>

                      {/* Unified Diff View */}
                      <div className="space-y-1">
                        <span className="font-bold text-secondary block text-[11px] uppercase tracking-wider">
                          Parche Propuesto (Git Diff):
                        </span>
                        <div className="bg-dark/90 p-2 rounded text-[10px] font-mono leading-normal overflow-x-auto max-h-32 text-muted-foreground border border-dark/50">
                          {aiFixResult.diff.split("\n").map((line, i) => {
                            let colorClass = "text-muted-foreground";
                            if (line.startsWith("+"))
                              colorClass = "text-success bg-lightsuccess/20";
                            else if (line.startsWith("-"))
                              colorClass = "text-error bg-lighterror/20";
                            return (
                              <div
                                key={i}
                                className={`px-1 rounded-sm ${colorClass}`}
                              >
                                {line}
                              </div>
                            );
                          })}
                        </div>
                      </div>

                      {/* Botón de Autocorrección en disco */}
                      <div className="pt-1.5 flex gap-2">
                        <button
                          onClick={() => handleApplyAiPatch(selectedIncident)}
                          disabled={isApplyingFix}
                          className="flex-1 bg-primary text-primary-foreground py-2 px-3 rounded text-xs transition shadow-sm flex items-center justify-center gap-1.5"
                        >
                          {isApplyingFix ? (
                            <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                          ) : (
                            <FileCode className="w-3.5 h-3.5" />
                          )}
                          {isApplyingFix
                            ? "Aplicando Parche..."
                            : "🛠️ Aplicar Corrección en Disco"}
                        </button>
                        <button
                          onClick={() => {
                            setAiFixResult(null);
                            setFixSuccessMessage(null);
                          }}
                          className="px-3 bg-dark/80 hover:bg-dark/70 text-muted-foreground font-bold rounded text-xs transition border border-dark/70"
                        >
                          Cancelar
                        </button>
                      </div>
                    </div>
                  )}

                  {fixSuccessMessage && (
                    <div className="bg-lightsuccess border border-success/30 text-success p-3 rounded-lg text-xs space-y-2 animate-fade-in text-center py-4">
                      <CheckCircle className="w-8 h-8 text-success mx-auto" />
                      <div className="space-y-0.5">
                        <span className="font-bold text-sm block">
                          ¡Archivo Corregido!
                        </span>
                        <p className="text-[11px] text-success leading-relaxed">
                          {fixSuccessMessage}
                        </p>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            ) : (
              <div className="bg-muted/50 p-8 rounded-xl border border-dashed border-border text-center py-20">
                <Layers className="w-12 h-12 text-muted-foreground mx-auto mb-2" />
                <p className="font-semibold text-muted-foreground text-sm">
                  Ninguna incidencia seleccionada
                </p>
                <p className="text-xs text-muted-foreground mt-1">
                  Selecciona una incidencia del listado para ver sus trazas y
                  solucionarla inmediatamente.
                </p>
              </div>
            )}
          </div>
        </div>
      )}

      {/* --- PESTAÑA UPTIME KUMA: ESTADOS DE SERVIDORES Y SERVICIOS --- */}
      {activeTab === "kuma" && (
        <div className="space-y-6 animate-fade-in">
          {/* Tarjetas de Métricas Globales */}
          <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div className="bg-card p-5 rounded-xl shadow-sm border border-border flex items-center justify-between">
              <div>
                <span className="text-xs text-muted-foreground block font-semibold uppercase tracking-wider">
                  Estado de Servicios
                </span>
                <span className="text-lg font-bold text-success mt-1 block flex items-center gap-1">
                  <CheckCircle className="w-5 h-5" /> Todo Activo
                </span>
              </div>
              <div className="p-3 bg-lightsuccess text-success rounded-xl">
                <Server className="w-6 h-6" />
              </div>
            </div>

            <div className="bg-card p-5 rounded-xl shadow-sm border border-border flex items-center justify-between">
              <div>
                <span className="text-xs text-muted-foreground block font-semibold uppercase tracking-wider">
                  Uptime Total (30d)
                </span>
                <span className="text-2xl font-black text-dark mt-1 block">
                  {systemUptime}%
                </span>
              </div>
              <div className="p-3 bg-lightprimary text-primary rounded-xl">
                <Globe className="w-6 h-6" />
              </div>
            </div>

            <div className="bg-card p-5 rounded-xl shadow-sm border border-border flex items-center justify-between">
              <div>
                <span className="text-xs text-muted-foreground block font-semibold uppercase tracking-wider">
                  Latencia Promedio
                </span>
                <span className="text-2xl font-black text-dark mt-1 block">
                  32 ms
                </span>
              </div>
              <div className="p-3 bg-lightsecondary text-secondary rounded-xl">
                <Cpu className="w-6 h-6" />
              </div>
            </div>

            <div className="bg-card p-5 rounded-xl shadow-sm border border-border flex items-center justify-between">
              <div>
                <span className="text-xs text-muted-foreground block font-semibold uppercase tracking-wider">
                  Conexión DB
                </span>
                <span className="text-lg font-bold text-success mt-1 block flex items-center gap-1">
                  <CheckCircle className="w-5 h-5" /> Estable (30%)
                </span>
              </div>
              <div className="p-3 bg-lightwarning text-warning rounded-xl">
                <Database className="w-6 h-6" />
              </div>
            </div>
          </div>

          {/* Tabla de Endpoints de Uptime Kuma */}
          <div className="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
            <div className="p-5 border-b border-border flex items-center justify-between">
              <h3 className="font-bold text-dark">
                Endpoints en Monitoreo Sintético Activo
              </h3>
              <span className="text-xs text-muted-foreground font-medium">
                Intervalo de chequeo: cada 60s
              </span>
            </div>

            <div className="divide-y divide-gray-150">
              {/* Endpoint 1 */}
              <div className="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-accent/50 hover:text-accent-foreground transition">
                <div className="flex items-center gap-3">
                  <div className="w-2.5 h-2.5 bg-primary rounded-full animate-pulse" />
                  <div>
                    <h4 className="font-bold text-dark text-sm">
                      API Gateway Principal (PHP Backend)
                    </h4>
                    <span className="text-xs font-mono text-muted-foreground">
                      https://exa-relavera.com/api/v1
                    </span>
                  </div>
                </div>
                <div className="flex items-center gap-6 text-xs text-muted-foreground">
                  <div>
                    <span className="block text-right font-medium">
                      Latencia
                    </span>
                    <span className="font-bold text-dark">42ms</span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">
                      SSL Expira
                    </span>
                    <span className="font-bold text-success">
                      En 245 días
                    </span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">Estado</span>
                    <span className="px-2 py-0.5 bg-lightsuccess text-success rounded font-bold uppercase text-[10px]">
                      OPERATIVO
                    </span>
                  </div>
                </div>
              </div>

              {/* Endpoint 2 */}
              <div className="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-accent/50 hover:text-accent-foreground transition">
                <div className="flex items-center gap-3">
                  <div className="w-2.5 h-2.5 bg-primary rounded-full animate-pulse" />
                  <div>
                    <h4 className="font-bold text-dark text-sm">
                      Portal Web Exa (Next.js SSR)
                    </h4>
                    <span className="text-xs font-mono text-muted-foreground">
                      https://exa-relavera.com/dashboard
                    </span>
                  </div>
                </div>
                <div className="flex items-center gap-6 text-xs text-muted-foreground">
                  <div>
                    <span className="block text-right font-medium">
                      Latencia
                    </span>
                    <span className="font-bold text-dark">22ms</span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">
                      SSL Expira
                    </span>
                    <span className="font-bold text-success">
                      En 245 días
                    </span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">Estado</span>
                    <span className="px-2 py-0.5 bg-lightsuccess text-success rounded font-bold uppercase text-[10px]">
                      OPERATIVO
                    </span>
                  </div>
                </div>
              </div>

              {/* Endpoint 3 */}
              <div className="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-accent/50 hover:text-accent-foreground transition">
                <div className="flex items-center gap-3">
                  <div className="w-2.5 h-2.5 bg-primary rounded-full animate-pulse" />
                  <div>
                    <h4 className="font-bold text-dark text-sm">
                      Servidor de Envíos de Correo (SMTP / SSL)
                    </h4>
                    <span className="text-xs font-mono text-muted-foreground">
                      smtp.exa-relavera.com:465
                    </span>
                  </div>
                </div>
                <div className="flex items-center gap-6 text-xs text-muted-foreground">
                  <div>
                    <span className="block text-right font-medium">
                      Respuesta
                    </span>
                    <span className="font-bold text-dark">12ms</span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">
                      Certificado
                    </span>
                    <span className="font-bold text-success">Válido</span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">Estado</span>
                    <span className="px-2 py-0.5 bg-lightsuccess text-success rounded font-bold uppercase text-[10px]">
                      OPERATIVO
                    </span>
                  </div>
                </div>
              </div>

              {/* Endpoint 4 */}
              <div className="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-accent/50 hover:text-accent-foreground transition">
                <div className="flex items-center gap-3">
                  <div className="w-2.5 h-2.5 bg-warning rounded-full" />
                  <div>
                    <h4 className="font-bold text-dark text-sm">
                      Integración API Externa SRI (Facturación Electrónica)
                    </h4>
                    <span className="text-xs font-mono text-muted-foreground">
                      https://sri.gob.ec/comprobantes-electronicos
                    </span>
                  </div>
                </div>
                <div className="flex items-center gap-6 text-xs text-muted-foreground">
                  <div>
                    <span className="block text-right font-medium">
                      Latencia
                    </span>
                    <span className="font-bold text-warning">890ms</span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">
                      Certificado
                    </span>
                    <span className="font-bold text-warning">
                      Expiración no disponible
                    </span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">Estado</span>
                    <span className="px-2 py-0.5 bg-lightwarning text-warning rounded font-bold uppercase text-[10px]">
                      DEGRADADO
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Información Adicional de Uptime Kuma */}
          <div className="p-5 bg-gradient-to-r from-success to-primary rounded-xl text-white flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
            <div className="space-y-1">
              <h4 className="font-bold text-base flex items-center gap-1.5">
                <Activity className="w-5 h-5" />
                ¿Quieres integrar tu servidor real de Uptime Kuma?
              </h4>
              <p className="text-xs text-white/80 leading-relaxed max-w-2xl">
                Puedes enlazar la API o el JSON de tu servidor auto-alojado de
                Uptime Kuma directamente en esta sección. Esto te permitirá
                centralizar tus métricas de infraestructura sin necesidad de
                abrir paneles externos.
              </p>
            </div>
            <a
              href="https://github.com/louislam/uptime-kuma"
              target="_blank"
              rel="noreferrer"
              className="px-4 py-2 bg-card text-success font-bold rounded-lg text-xs hover:bg-lightsuccess transition shrink-0 flex items-center gap-1.5"
            >
              Documentación Kuma
              <ExternalLink className="w-3.5 h-3.5" />
            </a>
          </div>
        </div>
      )}

      {/* --- PESTAÑA PLAYWRIGHT: PRUEBAS BAJO DEMANDA Y REPORTE INTERACTIVO --- */}
      {activeTab === "playwright" && (
        <div className="space-y-6 animate-fade-in">
          <div className="bg-card p-6 rounded-xl shadow-sm border border-border space-y-4">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div className="space-y-1">
                <h3 className="font-bold text-lg text-dark flex items-center gap-2">
                  <Sparkles className="w-5 h-5 text-error" />
                  Consola de Diagnóstico Sintético (Playwright Engine)
                </h3>
                <p className="text-sm text-muted-foreground">
                  Ejecuta y simula flujos interactivos de usuarios en vivo
                  directamente en tus servidores de prueba para verificar login,
                  navegación y APIs en tiempo real.
                </p>
              </div>

              <button
                onClick={handleRunPlaywrightTest}
                disabled={isPlayingTest}
                className="bg-destructive hover:bg-destructive/90 text-white font-bold py-2.5 px-5 rounded-lg transition shadow-md disabled:bg-lighterror flex items-center gap-2 self-start md:self-auto"
              >
                <RefreshCw
                  className={`w-4 h-4 ${isPlayingTest ? "animate-spin" : ""}`}
                />
                {isPlayingTest
                  ? "Ejecutando Pruebas..."
                  : "⚡ Lanzar Diagnóstico Ahora"}
              </button>
            </div>

            {/* Mensajes de Resultado */}
            {playwrightMessage && (
              <div
                className={`p-4 rounded-lg border flex items-start gap-2.5 text-sm ${
                  playwrightMessage.type === "success"
                    ? "bg-lightsuccess text-success border-success/30"
                    : "bg-lightwarning text-warning border-warning/30"
                }`}
              >
                <CheckCircle
                  className={`w-5 h-5 shrink-0 ${
                    playwrightMessage.type === "success"
                      ? "text-success"
                      : "text-warning"
                  }`}
                />
                <div className="space-y-1">
                  <span className="font-bold">
                    {playwrightMessage.type === "success"
                      ? "¡Todo en orden!"
                      : "¡Diagnóstico Completado!"}
                  </span>
                  <p>{playwrightMessage.text}</p>
                </div>
              </div>
            )}

            {/* Estado de Carga Animado */}
            {isPlayingTest && (
              <div className="p-8 bg-muted/50 rounded-xl border border-dashed border-border text-center space-y-4">
                <div className="w-12 h-12 border-4 border-error border-t-transparent rounded-full animate-spin mx-auto" />
                <div className="space-y-1 max-w-md mx-auto">
                  <p className="font-bold text-dark">
                    El motor de Playwright se está ejecutando...
                  </p>
                  <p className="text-xs text-muted-foreground">
                    Esto abrirá un navegador virtual sin cabeza (headless), se
                    autenticará con el usuario de monitoreo y verificará el
                    estado de las interfaces de facturación, contabilidad y
                    RRHH. Tardará unos 15 segundos.
                  </p>
                </div>
              </div>
            )}

            {/* Iframe del Reporte HTML de Playwright */}
            {playwrightReportUrl && (
              <div className="border border-border rounded-xl overflow-hidden shadow-md">
                <div className="bg-muted p-3.5 border-b font-semibold text-sm text-dark flex justify-between items-center">
                  <span className="flex items-center gap-1.5 font-bold">
                    <FileCode className="w-4 h-4 text-secondary" />
                    Reporte Interactivo de Ejecución de Playwright
                  </span>
                  <div className="flex items-center gap-2">
                    <span className="text-xs bg-lightinfo text-info font-bold px-2 py-0.5 rounded-full">
                      En Vivo
                    </span>
                    <button
                      onClick={() => setPlaywrightReportUrl(null)}
                      className="text-xs text-error hover:text-error font-semibold"
                    >
                      Cerrar Reporte
                    </button>
                  </div>
                </div>
                {/* Cargamos el reporte HTML generado en Next.js public directory */}
                <iframe
                  src={playwrightReportUrl}
                  className="w-full h-[650px] border-none bg-card"
                />
              </div>
            )}
          </div>
        </div>
      )}

      {/* --- PESTAÑA USO DE MÓDULOS: ESTADÍSTICAS DE USO POR MÓDULO, USUARIO Y EMPRESA --- */}
      {activeTab === "modulos" && (
        <ModuleUsageCharts />
      )}

      {/* --- PESTAÑA CONEXIÓN BD: GESTIÓN DE CONEXIONES A BASE DE DATOS --- */}
      {activeTab === "conexion" && (
        <div className="space-y-6 animate-fade-in">
          {/* Estado Actual */}
          <div className="bg-card p-6 rounded-xl shadow-sm border border-border">
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-bold text-dark flex items-center gap-2">
                <Database className="w-5 h-5 text-primary" />
                Estado de Conexión
              </h3>
              <button
                onClick={loadConexionData}
                disabled={connLoading}
                className="flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold border rounded-lg text-muted-foreground hover:bg-accent hover:text-accent-foreground transition disabled:opacity-50"
              >
                <RefreshCw className={`w-4 h-4 ${connLoading ? "animate-spin" : ""}`} />
                {connLoading ? "Cargando..." : "Refrescar"}
              </button>
            </div>

            {connLoading && !connEstado ? (
              <div className="py-8 text-center text-sm text-muted-foreground">
                <div className="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
                Cargando estado de conexión...
              </div>
            ) : connError ? (
              <div className="bg-lighterror border-l-4 border-error text-error p-4 rounded-r-md text-sm">
                <strong>Error:</strong> {connError}
              </div>
            ) : connEstado ? (
              <div className="space-y-3">
                <div className="flex items-center gap-3 p-4 bg-muted/50 rounded-lg">
                  <div className={`w-3 h-3 rounded-full ${connEstado.conectado ? "bg-primary" : "bg-lighterror0"}`} />
                  <div>
                    <span className="font-semibold text-dark">
                      {connEstado.conectado ? "Conectado" : "Desconectado"}
                    </span>
                    <span className="text-sm text-muted-foreground ml-2">
                      a {connEstado.activo?.host ?? "—"}:{connEstado.activo?.port ?? 3306}
                    </span>
                  </div>
                </div>
                {connEstado.server_info && (
                  <p className="text-sm text-muted-foreground">
                    Servidor: <span className="font-mono text-dark">{connEstado.server_info}</span>
                  </p>
                )}
                {connEstado.error && (
                  <p className="text-sm text-error">{connEstado.error}</p>
                )}
                <p className="text-sm text-muted-foreground">
                  Perfil activo: <span className="font-semibold text-dark">{connEstado.activo?.nombre ?? "Ninguno"}</span>
                </p>
              </div>
            ) : null}
          </div>

          {/* Perfiles Guardados */}
          <div className="bg-card p-6 rounded-xl shadow-sm border border-border">
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-bold text-dark flex items-center gap-2">
                <Settings className="w-5 h-5 text-muted-foreground" />
                Perfiles Guardados
              </h3>
              <button
                onClick={() => { setShowForm(!showForm); setFormTestResult(null); }}
                className="flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition"
              >
                <Plus className="w-4 h-4" />
                {showForm ? "Cancelar" : "Agregar Perfil"}
              </button>
            </div>

            {connPerfiles.length === 0 ? (
              <div className="py-8 text-center text-sm text-muted-foreground bg-muted/50 rounded-lg border border-dashed border-border">
                <Database className="w-8 h-8 mx-auto mb-2 text-muted-foreground" />
                No hay perfiles guardados. Crea uno nuevo.
              </div>
            ) : (
              <div className="space-y-2">
                {connPerfiles.map((perfil) => {
                  const isActive = connEstado?.activo?.nombre === perfil.nombre && connEstado?.activo?.host === perfil.host;
                  return (
                    <div
                      key={perfil.nombre}
                      className={`flex items-center justify-between p-4 rounded-lg border transition ${
                        isActive
                          ? "border-blue-300 bg-lightprimary"
                          : "border-border hover:border-border bg-card"
                      }`}
                    >
                      <div className="flex items-center gap-3">
                        <div className={`w-2 h-2 rounded-full ${isActive ? "bg-primary" : "bg-muted"}`} />
                        <div>
                          <span className="font-semibold text-dark">{perfil.nombre}</span>
                          <span className="text-sm text-muted-foreground ml-2">{perfil.host}:{perfil.port}</span>
                          {perfil.database && (
                            <span className="text-xs text-muted-foreground ml-2">/ {perfil.database}</span>
                          )}
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        {isActive ? (
                          <span className="px-2 py-0.5 bg-lightsuccess text-success rounded-full text-xs font-bold">
                            ACTIVO
                          </span>
                        ) : (
                          <button
                            onClick={async () => {
                              setConnError(null);
                              setConnSuccess(null);
                              try {
                                const res = await conexionApi.activar(perfil.nombre);
                                if (res.success) {
                                  setConnSuccess(res.message || "Perfil activado");
                                  if (res.conexion_ok) {
                                    setTimeout(() => window.location.reload(), 1500);
                                  }
                                  await loadConexionData();
                                } else {
                                  setConnError(res.error || "Error al activar perfil");
                                }
                              } catch (e: unknown) {
                                const err = e as { message?: string };
                                setConnError(err?.message || "Error al conectar");
                              }
                            }}
                            className="px-3 py-1 text-xs font-semibold bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition"
                          >
                            Activar
                          </button>
                        )}
                        {perfil.nombre !== "Local" && (
                          <button
                            onClick={async () => {
                              setConnError(null);
                              setConnSuccess(null);
                              if (!await confirm(`¿Eliminar perfil "${perfil.nombre}"?`)) return;
                              try {
                                const res = await conexionApi.eliminar(perfil.nombre);
                                if (res.success) {
                                  setConnSuccess(res.message || "Perfil eliminado");
                                  await loadConexionData();
                                  toast.success("Eliminado correctamente");
                                } else {
                                  setConnError(res.error || "Error al eliminar perfil");
                                }
                              } catch (e: unknown) {
                                const err = e as { message?: string };
                                setConnError(err?.message || "Error al eliminar");
                              }
                            }}
                            className="p-1.5 text-muted-foreground hover:text-error transition"
                            title="Eliminar perfil"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            )}

            {connSuccess && (
              <div className="mt-3 bg-lightsuccess border-l-4 border-emerald-500 text-success p-3 rounded-r-md text-sm flex items-center gap-2">
                <CheckCircle className="w-4 h-4 shrink-0" />
                <span>{connSuccess}</span>
                {connSuccess.includes("activado") && (
                  <span className="text-xs text-success ml-2">Recargando...</span>
                )}
              </div>
            )}
            {connError && (
              <div className="mt-3 bg-lighterror border-l-4 border-error text-error p-3 rounded-r-md text-sm">
                {connError}
              </div>
            )}
          </div>

          {/* Formulario Nuevo Perfil */}
          {showForm && (
            <div className="bg-card p-6 rounded-xl shadow-sm border border-border">
              <h3 className="font-bold text-dark flex items-center gap-2 mb-4">
                <Plug className="w-5 h-5 text-primary" />
                {connPerfiles.find((p) => p.nombre === formNombre) ? "Editar Perfil" : "Nuevo Perfil"}
              </h3>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">
                    Nombre del Perfil
                  </label>
                  <input
                    type="text"
                    value={formNombre}
                    onChange={(e) => setFormNombre(e.target.value)}
                    className="w-full px-3 py-2 border border-border rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="ej. Producción Plesk"
                  />
                </div>
                <div className="grid grid-cols-3 gap-2">
                  <div className="col-span-2">
                    <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">
                      Host / Servidor
                    </label>
                    <input
                      type="text"
                      value={formHost}
                      onChange={(e) => setFormHost(e.target.value)}
                      className="w-full px-3 py-2 border border-border rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                      placeholder="ej. 104.36.166.126"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">
                      Puerto
                    </label>
                    <input
                      type="number"
                      value={formPort}
                      onChange={(e) => setFormPort(e.target.value)}
                      className="w-full px-3 py-2 border border-border rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                      placeholder="3306"
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">
                    Usuario
                  </label>
                  <input
                    type="text"
                    value={formUser}
                    onChange={(e) => setFormUser(e.target.value)}
                    className="w-full px-3 py-2 border border-border rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="ej. user_relavera"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">
                    Contraseña
                  </label>
                  <input
                    type="password"
                    value={formPass}
                    onChange={(e) => setFormPass(e.target.value)}
                    className="w-full px-3 py-2 border border-border rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="••••••••"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">
                    Base de Datos (opcional)
                  </label>
                  <input
                    type="text"
                    value={formDb}
                    onChange={(e) => setFormDb(e.target.value)}
                    className="w-full px-3 py-2 border border-border rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="ej. exa_master"
                  />
                </div>
              </div>

              <div className="flex items-center gap-3">
                <button
                  onClick={async () => {
                    setFormTesting(true);
                    setFormTestResult(null);
                    try {
                      const res = await conexionApi.test({
                        nombre: formNombre || "test",
                        host: formHost,
                        port: parseInt(formPort) || 3306,
                        user: formUser,
                        pass: formPass,
                        database: formDb,
                      });
                      setFormTestResult(res);
                    } catch (e: unknown) {
                      const err = e as { message?: string };
                      setFormTestResult({
                        success: false,
                        error: err?.message || "Error de red",
                      });
                    } finally {
                      setFormTesting(false);
                    }
                  }}
                  disabled={formTesting || !formHost || !formUser}
                  className="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold bg-muted text-dark rounded-lg hover:bg-muted transition disabled:opacity-50"
                >
                  {formTesting ? (
                    <div className="w-4 h-4 border-2 border-muted border-t-transparent rounded-full animate-spin" />
                  ) : (
                    <Wifi className="w-4 h-4" />
                  )}
                  {formTesting ? "Probando..." : "Probar Conexión"}
                </button>

                <button
                  onClick={async () => {
                    setConnError(null);
                    setConnSuccess(null);
                    if (!formNombre || !formHost || !formUser) {
                      setConnError("Nombre, host y usuario son requeridos");
                      return;
                    }
                    try {
                      const res = await conexionApi.guardar({
                        nombre: formNombre,
                        host: formHost,
                        port: parseInt(formPort) || 3306,
                        user: formUser,
                        pass: formPass,
                        database: formDb,
                      });
                      if (res.success) {
                        setConnSuccess(res.message || "Perfil guardado");
                        setFormNombre("");
                        setFormHost("");
                        setFormPort("3306");
                        setFormUser("");
                        setFormPass("");
                        setFormDb("");
                        setFormTestResult(null);
                        setShowForm(false);
                        await loadConexionData();
                      } else {
                        setConnError(res.error || "Error al guardar perfil");
                      }
                    } catch (e: unknown) {
                      const err = e as { message?: string };
                      setConnError(err?.message || "Error al guardar");
                    }
                  }}
                  disabled={!formNombre || !formHost || !formUser}
                  className="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition disabled:opacity-50"
                >
                  <Save className="w-4 h-4" />
                  Guardar Perfil
                </button>
              </div>

              {formTestResult && (
                <div className={`mt-3 p-3 rounded-lg text-sm border ${
                  formTestResult.success
                    ? "bg-lightsuccess border-success/30 text-success"
                    : "bg-lighterror border-red-200 text-error"
                }`}>
                  <div className="flex items-center gap-2">
                    {formTestResult.success ? (
                      <CheckCircle className="w-4 h-4 text-success" />
                    ) : (
                      <WifiOff className="w-4 h-4 text-error" />
                    )}
                    <span className="font-semibold">
                      {formTestResult.success ? "Conexión exitosa" : "Error de conexión"}
                    </span>
                  </div>
                  {formTestResult.message && (
                    <p className="text-xs mt-1">{formTestResult.message}</p>
                  )}
                  {formTestResult.error && (
                    <p className="text-xs mt-1 font-mono">{formTestResult.error}</p>
                  )}
                </div>
              )}
            </div>
          )}
        </div>
      )}
      {ConfirmDialog}
    </div>
  );
}
