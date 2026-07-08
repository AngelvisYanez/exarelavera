"use client";

import { useState } from "react";
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

// Datos semilla de GlitchTip y Uptime Kuma para simulación en tiempo real
const INITIAL_INCIDENTS: Incident[] = [
  {
    id: "1",
    title: "TypeError: Cannot read properties of undefined (reading 'Emp_Cod')",
    culprit: "components/layout/sidebar.tsx in Object.map",
    level: "fatal",
    count: 34,
    usersAffected: 8,
    lastSeen: "Hace 3 minutos",
    status: "unresolved",
    browser: "Chrome 125.0.0",
    os: "Windows 11",
    stackTrace: [
      "at Sidebar (components/layout/sidebar.tsx:57:19)",
      "at renderWithHooks (node_modules/react-dom/cjs/react-dom.development.js:15486:18)",
      "at mountIndeterminateComponent (node_modules/react-dom/cjs/react-dom.development.js:18075:13)",
      "at beginWork (node_modules/react-dom/cjs/react-dom.development.js:19240:16)",
    ],
  },
  {
    id: "2",
    title: "Database connection timeout (max_connections exceeded)",
    culprit: "classes/db.php in DB::connect",
    level: "fatal",
    count: 142,
    usersAffected: 23,
    lastSeen: "Hace 12 minutos",
    status: "unresolved",
    browser: "Server-Side (PHP 8.3.4)",
    os: "Linux (Ubuntu 22.04)",
    stackTrace: [
      "PDOException: SQLSTATE[HY000] [1040] Too many connections in /var/www/exa/classes/db.php:48",
      "Stack trace:",
      "#0 /var/www/exa/classes/db.php(48): PDO->__construct('mysql:host=127....', 'root', '***')",
      "#1 /var/www/exa/index.php(14): DB::connect()",
      "#2 {main}",
    ],
  },
  {
    id: "3",
    title:
      "Failed to fetch: API route /api/facturacion/crear returned 502 Bad Gateway",
    culprit: "lib/api-client.ts in ApiClient.post",
    level: "error",
    count: 18,
    usersAffected: 5,
    lastSeen: "Hace 45 minutos",
    status: "unresolved",
    browser: "Safari 17.4",
    os: "macOS Sonoma",
    stackTrace: [
      "Error: Failed to fetch: API route /api/facturacion/crear returned 502 Bad Gateway",
      "at ApiClient.post (lib/api-client.ts:32:15)",
      "at async handleCrearFactura (app/dashboard/facturacion/page.tsx:120:9)",
    ],
  },
  {
    id: "4",
    title:
      "Warning: React Hook useEffect has a missing dependency: 'fetchEmpresas'",
    culprit: "app/login/page.tsx in LoginPage",
    level: "warning",
    count: 512,
    usersAffected: 89,
    lastSeen: "Hace 2 horas",
    status: "unresolved",
    browser: "Edge 124.0.0",
    os: "Windows 10",
    stackTrace: [
      "at LoginPage (app/login/page.tsx:43:6)",
      "at renderWithHooks (node_modules/react-dom/cjs/react-dom.development.js:15486:18)",
    ],
  },
];

export default function IncidenciasPage() {
  // Estado de Autenticación del Superadmin (Iniciado perezosamente para evitar renders extra)
  const [isSuperAdmin, setIsSuperAdmin] = useState<boolean>(() => {
    if (typeof window !== "undefined") {
      const savedSession = localStorage.getItem("exa_superadmin_session");
      return savedSession === "superadmin_session_token_2026_success";
    }
    return false;
  });
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [authError, setAuthError] = useState<string | null>(null);
  const [authLoading, setAuthLoading] = useState(false);

  // Estados del Monitor
  const [incidents, setIncidents] = useState<Incident[]>(INITIAL_INCIDENTS);
  const [selectedIncident, setSelectedIncident] = useState<Incident | null>(
    null,
  );
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [showResolved, setShowResolved] = useState(false);
  const [filter, setFilter] = useState<"all" | "fatal" | "error" | "warning">(
    "all",
  );
  const [systemUptime, setSystemUptime] = useState(99.85);
  const [activeTab, setActiveTab] = useState<
    "glitchtip" | "kuma" | "playwright"
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
    isSimulated?: boolean;
    warning?: string;
  } | null>(null);
  const [isApplyingFix, setIsApplyingFix] = useState(false);
  const [fixSuccessMessage, setFixSuccessMessage] = useState<string | null>(
    null,
  );
  const [aiError, setAiError] = useState<string | null>(null);

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

  // Refresco real: obtiene incidencias de la DB y ejecuta monitores
  const handleRefresh = async () => {
    setIsRefreshing(true);
    try {
      const [incidentsRes, playwrightRes] = await Promise.allSettled([
        fetch("/api/incidents"),
        fetch("/api/admin/run-test", { method: "POST" }),
      ]);

      if (incidentsRes.status === "fulfilled") {
        const data = await incidentsRes.value.json();
        if (Array.isArray(data)) {
          setIncidents(data);
        } else if (data.data && Array.isArray(data.data)) {
          setIncidents(data.data);
        }
      }

      if (playwrightRes.status === "fulfilled") {
        const data = await playwrightRes.value.json();
        setPlaywrightMessage({
          type: data.success ? "success" : "error",
          text: data.message || data.error || "Diagnóstico completado",
        });
        if (data.reportUrl) {
          setPlaywrightReportUrl(data.reportUrl + "?t=" + Date.now());
        }
      }
    } catch {
      // fallback silencioso
    } finally {
      setIsRefreshing(false);
    }
  };

  // Simulación de resolver incidencia
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
    if (!showResolved && inc.status === "resolved") return false;
    if (filter === "all") return true;
    return inc.level === filter;
  });
  const resolvedCount = incidents.filter((i) => i.status === "resolved").length;

  // --- VISTA GATED (LOGIN DE SUPERADMIN) ---
  if (!isSuperAdmin) {
    return (
      <div className="min-h-[80vh] flex items-center justify-center px-4 bg-gray-50">
        <div className="bg-white p-8 rounded-xl shadow-lg border border-gray-200 w-full max-w-md">
          <div className="text-center mb-6">
            <div className="mx-auto w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mb-4">
              <ShieldAlert className="w-9 h-9" />
            </div>
            <h2 className="text-2xl font-bold text-gray-800">
              Acceso Restringido
            </h2>
            <p className="text-sm text-gray-500 mt-2">
              Este panel contiene reportes críticos del sistema y métricas en
              tiempo real. Por favor, identifíquese como{" "}
              <strong>Superadmin</strong>.
            </p>
          </div>

          {authError && (
            <div className="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded-r-md mb-4 text-xs">
              <div className="font-semibold">Error de autenticación</div>
              <div>{authError}</div>
            </div>
          )}

          <form onSubmit={handleSuperAdminLogin} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-1">
                Usuario de Soporte
              </label>
              <div className="relative">
                <span className="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                  <User className="w-5 h-5" />
                </span>
                <input
                  type="text"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 text-black text-sm"
                  placeholder="ej. exacontable"
                  required
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-1">
                Contraseña Especial
              </label>
              <div className="relative">
                <span className="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                  <KeyRound className="w-5 h-5" />
                </span>
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 text-black text-sm"
                  placeholder="••••••••"
                  required
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={authLoading}
              className="w-full mt-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-2.5 px-4 rounded-lg shadow-md transition disabled:bg-gray-400 flex items-center justify-center gap-2"
            >
              <Lock className="w-4 h-4" />
              {authLoading ? "Verificando..." : "Desbloquear Monitor"}
            </button>
          </form>

          <div className="mt-6 border-t pt-4 text-center">
            <p className="text-xs text-gray-400">
              EXA Contable Relavera Security Panel • 2026
            </p>
          </div>
        </div>
      </div>
    );
  }

  // --- DASHBOARD DE CONTROL DE INCIDENCIAS ---
  return (
    <div className="space-y-6">
      {/* Encabezado Principal */}
      <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <div className="p-1.5 bg-red-100 text-red-600 rounded-lg">
              <ShieldAlert className="w-6 h-6" />
            </div>
            <h1 className="text-2xl font-bold text-gray-900">
              Monitor de Incidencias & Disponibilidad
            </h1>
          </div>
          <p className="text-sm text-gray-500 mt-1">
            Visualización integrada de fallas en producción con{" "}
            <span className="font-semibold text-purple-600">GlitchTip</span> y
            latencias con{" "}
            <span className="font-semibold text-emerald-600">Uptime Kuma</span>.
          </p>
        </div>

        <div className="flex items-center gap-2 self-start md:self-auto">
          <button
            onClick={handleRefresh}
            disabled={isRefreshing}
            className="flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold border rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50"
          >
            <RefreshCw
              className={`w-4 h-4 ${isRefreshing ? "animate-spin" : ""}`}
            />
            {isRefreshing ? "Actualizando..." : "Refrescar"}
          </button>
          <button
            onClick={handleLogoutSuperadmin}
            className="px-3 py-1.5 text-sm font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition"
          >
            Salir de Superadmin
          </button>
        </div>
      </div>

      {/* Selector de Herramientas de Monitoreo */}
      <div className="flex border-b border-gray-200">
        <button
          onClick={() => setActiveTab("glitchtip")}
          className={`pb-3 px-6 font-semibold text-sm flex items-center gap-2 border-b-2 transition ${
            activeTab === "glitchtip"
              ? "border-purple-600 text-purple-600"
              : "border-transparent text-gray-500 hover:text-gray-800"
          }`}
        >
          <Bug className="w-4 h-4" />
          GlitchTip Exceptions (Errores de Código)
          <span className="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-bold">
            {incidents.filter((i) => i.status === "unresolved").length}
          </span>
          {resolvedCount > 0 && (
            <span className="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
              {resolvedCount} resueltos
            </span>
          )}
        </button>
        <button
          onClick={() => setActiveTab("kuma")}
          className={`pb-3 px-6 font-semibold text-sm flex items-center gap-2 border-b-2 transition ${
            activeTab === "kuma"
              ? "border-emerald-600 text-emerald-600"
              : "border-transparent text-gray-500 hover:text-gray-800"
          }`}
        >
          <Activity className="w-4 h-4" />
          Uptime Kuma (Módulos & APIs)
          <span className="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse" />
        </button>
        <button
          onClick={() => setActiveTab("playwright")}
          className={`pb-3 px-6 font-semibold text-sm flex items-center gap-2 border-b-2 transition ${
            activeTab === "playwright"
              ? "border-red-600 text-red-600"
              : "border-transparent text-gray-500 hover:text-gray-800"
          }`}
        >
          <Sparkles className="w-4 h-4" />
          Playwright On-Demand
          {isPlayingTest && (
            <span className="w-2 h-2 bg-red-500 rounded-full animate-ping" />
          )}
        </button>
      </div>

      {/* --- PESTAÑA GLITCHTIP: EXCEPCIONES Y ERRORES --- */}
      {activeTab === "glitchtip" && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Listado de Excepciones */}
          <div className="lg:col-span-2 space-y-4">
            <div className="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
              <div className="flex items-center justify-between mb-4">
                <h3 className="font-bold text-gray-800">
                  {showResolved ? "Historial de incidencias" : "Incidentes no resueltos"}
                </h3>
                <div className="flex items-center gap-1.5 text-xs flex-wrap">
                  <span className="text-gray-500 font-medium">
                    Filtrar por:
                  </span>
                  {(["all", "fatal", "error", "warning"] as const).map(
                    (lvl) => (
                      <button
                        key={lvl}
                        onClick={() => setFilter(lvl)}
                        className={`capitalize px-2 py-1 rounded font-semibold border transition ${
                          filter === lvl
                            ? "bg-purple-50 text-purple-700 border-purple-200"
                            : "bg-white hover:bg-gray-50 text-gray-600"
                        }`}
                      >
                        {lvl === "all" ? "Todos" : lvl}
                      </button>
                    ),
                  )}
                  <span className="w-px h-4 bg-gray-300 mx-1" />
                  <button
                    onClick={() => setShowResolved(!showResolved)}
                    className={`flex items-center gap-1 px-2 py-1 rounded font-semibold border transition ${
                      showResolved
                        ? "bg-emerald-50 text-emerald-700 border-emerald-200"
                        : "bg-white hover:bg-gray-50 text-gray-600"
                    }`}
                  >
                    <CheckCircle className="w-3 h-3" />
                    Resueltos
                    {resolvedCount > 0 && (
                      <span className="px-1 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">
                        {resolvedCount}
                      </span>
                    )}
                  </button>
                </div>
              </div>

              {filteredIncidents.length === 0 ? (
                <div className="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                  {showResolved ? (
                    <>
                      <Clock className="w-12 h-12 text-gray-300 mx-auto mb-2" />
                      <p className="font-semibold text-gray-700">
                        No hay incidencias resueltas
                      </p>
                      <p className="text-xs text-gray-400 mt-1">
                        Aún no se ha resuelto ninguna incidencia.
                      </p>
                    </>
                  ) : (
                    <>
                      <CheckCircle className="w-12 h-12 text-emerald-500 mx-auto mb-2" />
                      <p className="font-semibold text-gray-700">
                        ¡Excelente! Cero incidencias críticas
                      </p>
                      <p className="text-xs text-gray-400 mt-1">
                        Todas las alertas de GlitchTip han sido resueltas o
                        silenciadas.
                      </p>
                    </>
                  )}
                </div>
              ) : (
                <div className="space-y-3">
                  {filteredIncidents.map((inc) => (
                    <div
                      key={inc.id}
                      onClick={() => { if (inc.status !== "resolved") setSelectedIncident(inc); }}
                      className={`p-4 rounded-lg border transition cursor-pointer flex flex-col md:flex-row md:items-center justify-between gap-4 ${
                        inc.status === "resolved"
                          ? "border-emerald-200 bg-emerald-50/30 opacity-70 hover:opacity-100"
                          : selectedIncident?.id === inc.id
                            ? "border-purple-500 bg-purple-50/20 shadow-sm"
                            : "border-gray-150 hover:border-gray-300 hover:bg-gray-50/50 bg-white"
                      }`}
                    >
                      <div className="space-y-1.5 flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                          {inc.status === "resolved" ? (
                            <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                              <CheckCircle className="w-3 h-3" /> resuelto
                            </span>
                          ) : (
                            <span
                              className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                                inc.level === "fatal"
                                  ? "bg-red-100 text-red-700 border border-red-200"
                                  : inc.level === "error"
                                    ? "bg-orange-100 text-orange-700 border border-orange-200"
                                    : "bg-yellow-100 text-yellow-700 border border-yellow-200"
                              }`}
                            >
                              {inc.level}
                            </span>
                          )}
                          <span className="text-xs text-gray-400 font-medium">
                            {inc.culprit}
                          </span>
                        </div>
                        <h4 className={`font-bold text-sm truncate leading-snug ${inc.status === "resolved" ? "text-gray-500 line-through" : "text-gray-800"}`}>
                          {inc.title}
                        </h4>
                        <div className="flex items-center gap-3 text-xs text-gray-500">
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
                        {inc.status === "resolved" ? (
                          <CheckCircle className="w-5 h-5 text-emerald-400" />
                        ) : (
                          <ArrowRight className="w-5 h-5 text-gray-400 group-hover:text-purple-600 transition" />
                        )}
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
              <div className="bg-white p-5 rounded-xl shadow-sm border border-gray-150 space-y-4 sticky top-6">
                <div className="flex items-center justify-between">
                  <h3 className="font-bold text-gray-800 flex items-center gap-1.5 text-sm">
                    <Terminal className="w-4 h-4 text-purple-600" />
                    Detalle de Excepción
                  </h3>
                  <button
                    onClick={() => resolveIncident(selectedIncident.id)}
                    className="text-xs font-semibold text-emerald-600 hover:text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded"
                  >
                    Marcar Resuelto
                  </button>
                </div>

                <div className="space-y-2">
                  <h4 className="text-base font-extrabold text-red-700 font-mono break-words bg-red-50/50 p-2 rounded">
                    {selectedIncident.title}
                  </h4>
                  <p className="text-xs text-gray-500 font-semibold">
                    Localización:{" "}
                    <span className="font-mono text-gray-700">
                      {selectedIncident.culprit}
                    </span>
                  </p>
                </div>

                {/* Datos del Cliente Afectado */}
                <div className="grid grid-cols-2 gap-3 p-3 bg-gray-50 rounded-lg text-xs">
                  <div>
                    <span className="text-gray-400 block font-medium">
                      Navegador del Cliente:
                    </span>
                    <span className="text-gray-700 font-bold">
                      {selectedIncident.browser}
                    </span>
                  </div>
                  <div>
                    <span className="text-gray-400 block font-medium">
                      Sistema Operativo:
                    </span>
                    <span className="text-gray-700 font-bold">
                      {selectedIncident.os}
                    </span>
                  </div>
                </div>

                {/* Stack Trace */}
                <div className="space-y-1.5">
                  <span className="text-xs font-bold text-gray-600 block">
                    Trazas de Pila (Stack Trace):
                  </span>
                  <div className="bg-gray-950 p-3 rounded-lg overflow-x-auto max-h-48 text-[11px] font-mono text-green-400 leading-normal border border-gray-800">
                    {selectedIncident.stackTrace.map((line, i) => (
                      <div key={i} className="whitespace-nowrap">
                        {line}
                      </div>
                    ))}
                  </div>
                </div>

                {/* Agente de Autocorrección de Inteligencia Artificial (Gemini Agent) */}
                <div className="p-4 bg-gradient-to-br from-gray-900 to-slate-950 border border-purple-900/40 rounded-lg space-y-3 shadow-lg text-white">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-1.5">
                      <Sparkles className="w-4 h-4 text-purple-400 animate-pulse" />
                      <span className="text-xs font-bold text-purple-300">
                        Agente Auto-Fixer
                      </span>
                    </div>
                    {aiFixResult && (
                      <button
                        onClick={() => {
                          setAiFixResult(null);
                          setFixSuccessMessage(null);
                        }}
                        className="text-[10px] text-gray-400 hover:text-white underline font-semibold"
                      >
                        Limpiar Diagnóstico
                      </button>
                    )}
                  </div>

                  {!aiFixResult && !isAnalyzingError && !fixSuccessMessage && (
                    <>
                      <p className="text-[11px] text-gray-300 leading-relaxed">
                        Ejecuta nuestro agente de IA basado en{" "}
                        <strong>Gemini</strong> para analizar la traza,
                        identificar la línea exacta de código bugueada en tu
                        archivo{" "}
                        <span className="font-mono text-purple-300">
                          {selectedIncident.culprit.split(" ")[0]}
                        </span>
                        , y generar un parche de autocorrección automatizado.
                      </p>
                      <button
                        onClick={() => handleRunAiAgentFix(selectedIncident)}
                        className="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold py-2 px-3 rounded text-xs transition shadow-md flex items-center justify-center gap-1.5"
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
                        <p className="text-xs font-bold text-purple-300">
                          Agente IA analizando código fuente...
                        </p>
                        <p className="text-[10px] text-gray-400 max-w-xs mx-auto">
                          Escanenado el workspace, localizando el archivo y
                          diagnosticando la raíz del bug con Gemini.
                        </p>
                      </div>
                    </div>
                  )}

                  {aiError && (
                    <div className="bg-red-950/50 border border-red-800 text-red-200 p-2.5 rounded text-xs leading-relaxed">
                      <strong>Fallo del Agente:</strong> {aiError}
                    </div>
                  )}

                  {aiFixResult && !fixSuccessMessage && (
                    <div className="space-y-3 text-xs border-t border-purple-950/50 pt-3 animate-fade-in">
                      {aiFixResult.warning && (
                        <div className="bg-yellow-950/40 border border-yellow-800/40 text-yellow-300 p-2 rounded text-[10px] leading-relaxed">
                          ⚠️ <strong>Nota:</strong> {aiFixResult.warning}
                        </div>
                      )}

                      <div className="space-y-1">
                        <span className="font-bold text-purple-300 block text-[11px] uppercase tracking-wider">
                          Causa Raíz Diagnosticada:
                        </span>
                        <p className="text-gray-300 text-[11px] leading-relaxed">
                          {aiFixResult.explanation}
                        </p>
                      </div>

                      <div className="space-y-1">
                        <span className="font-bold text-purple-300 block text-[11px] uppercase tracking-wider">
                          Cambio Sugerido:
                        </span>
                        <p className="text-gray-300 text-[11px] leading-relaxed font-semibold">
                          {aiFixResult.suggestedFix}
                        </p>
                      </div>

                      {/* Unified Diff View */}
                      <div className="space-y-1">
                        <span className="font-bold text-purple-300 block text-[11px] uppercase tracking-wider">
                          Parche Propuesto (Git Diff):
                        </span>
                        <div className="bg-slate-900 p-2 rounded text-[10px] font-mono leading-normal overflow-x-auto max-h-32 text-gray-300 border border-slate-800">
                          {aiFixResult.diff.split("\n").map((line, i) => {
                            let colorClass = "text-gray-400";
                            if (line.startsWith("+"))
                              colorClass = "text-emerald-400 bg-emerald-950/20";
                            else if (line.startsWith("-"))
                              colorClass = "text-red-400 bg-red-950/20";
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
                          className="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-3 rounded text-xs transition shadow-sm flex items-center justify-center gap-1.5"
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
                          className="px-3 bg-gray-800 hover:bg-gray-700 text-gray-300 font-bold rounded text-xs transition border border-gray-700"
                        >
                          Cancelar
                        </button>
                      </div>
                    </div>
                  )}

                  {fixSuccessMessage && (
                    <div className="bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 p-3 rounded-lg text-xs space-y-2 animate-fade-in text-center py-4">
                      <CheckCircle className="w-8 h-8 text-emerald-400 mx-auto" />
                      <div className="space-y-0.5">
                        <span className="font-bold text-sm block">
                          ¡Archivo Corregido!
                        </span>
                        <p className="text-[11px] text-emerald-200 leading-relaxed">
                          {fixSuccessMessage}
                        </p>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            ) : (
              <div className="bg-gray-50 p-8 rounded-xl border border-dashed border-gray-200 text-center py-20">
                <Layers className="w-12 h-12 text-gray-300 mx-auto mb-2" />
                <p className="font-semibold text-gray-500 text-sm">
                  Ninguna incidencia seleccionada
                </p>
                <p className="text-xs text-gray-400 mt-1">
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
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
              <div>
                <span className="text-xs text-gray-400 block font-semibold uppercase tracking-wider">
                  Estado de Servicios
                </span>
                <span className="text-lg font-bold text-emerald-600 mt-1 block flex items-center gap-1">
                  <CheckCircle className="w-5 h-5" /> Todo Activo
                </span>
              </div>
              <div className="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <Server className="w-6 h-6" />
              </div>
            </div>

            <div className="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
              <div>
                <span className="text-xs text-gray-400 block font-semibold uppercase tracking-wider">
                  Uptime Total (30d)
                </span>
                <span className="text-2xl font-black text-gray-800 mt-1 block">
                  {systemUptime}%
                </span>
              </div>
              <div className="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <Globe className="w-6 h-6" />
              </div>
            </div>

            <div className="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
              <div>
                <span className="text-xs text-gray-400 block font-semibold uppercase tracking-wider">
                  Latencia Promedio
                </span>
                <span className="text-2xl font-black text-gray-800 mt-1 block">
                  32 ms
                </span>
              </div>
              <div className="p-3 bg-purple-50 text-purple-600 rounded-xl">
                <Cpu className="w-6 h-6" />
              </div>
            </div>

            <div className="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
              <div>
                <span className="text-xs text-gray-400 block font-semibold uppercase tracking-wider">
                  Conexión DB
                </span>
                <span className="text-lg font-bold text-emerald-600 mt-1 block flex items-center gap-1">
                  <CheckCircle className="w-5 h-5" /> Estable (30%)
                </span>
              </div>
              <div className="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <Database className="w-6 h-6" />
              </div>
            </div>
          </div>

          {/* Tabla de Endpoints de Uptime Kuma */}
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div className="p-5 border-b border-gray-150 flex items-center justify-between">
              <h3 className="font-bold text-gray-800">
                Endpoints en Monitoreo Sintético Activo
              </h3>
              <span className="text-xs text-gray-400 font-medium">
                Intervalo de chequeo: cada 60s
              </span>
            </div>

            <div className="divide-y divide-gray-150">
              {/* Endpoint 1 */}
              <div className="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/50 transition">
                <div className="flex items-center gap-3">
                  <div className="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse" />
                  <div>
                    <h4 className="font-bold text-gray-800 text-sm">
                      API Gateway Principal (PHP Backend)
                    </h4>
                    <span className="text-xs font-mono text-gray-400">
                      https://exa-relavera.com/api/v1
                    </span>
                  </div>
                </div>
                <div className="flex items-center gap-6 text-xs text-gray-500">
                  <div>
                    <span className="block text-right font-medium">
                      Latencia
                    </span>
                    <span className="font-bold text-gray-700">42ms</span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">
                      SSL Expira
                    </span>
                    <span className="font-bold text-emerald-600">
                      En 245 días
                    </span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">Estado</span>
                    <span className="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold uppercase text-[10px]">
                      OPERATIVO
                    </span>
                  </div>
                </div>
              </div>

              {/* Endpoint 2 */}
              <div className="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/50 transition">
                <div className="flex items-center gap-3">
                  <div className="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse" />
                  <div>
                    <h4 className="font-bold text-gray-800 text-sm">
                      Portal Web Exa (Next.js SSR)
                    </h4>
                    <span className="text-xs font-mono text-gray-400">
                      https://exa-relavera.com/dashboard
                    </span>
                  </div>
                </div>
                <div className="flex items-center gap-6 text-xs text-gray-500">
                  <div>
                    <span className="block text-right font-medium">
                      Latencia
                    </span>
                    <span className="font-bold text-gray-700">22ms</span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">
                      SSL Expira
                    </span>
                    <span className="font-bold text-emerald-600">
                      En 245 días
                    </span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">Estado</span>
                    <span className="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold uppercase text-[10px]">
                      OPERATIVO
                    </span>
                  </div>
                </div>
              </div>

              {/* Endpoint 3 */}
              <div className="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/50 transition">
                <div className="flex items-center gap-3">
                  <div className="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse" />
                  <div>
                    <h4 className="font-bold text-gray-800 text-sm">
                      Servidor de Envíos de Correo (SMTP / SSL)
                    </h4>
                    <span className="text-xs font-mono text-gray-400">
                      smtp.exa-relavera.com:465
                    </span>
                  </div>
                </div>
                <div className="flex items-center gap-6 text-xs text-gray-500">
                  <div>
                    <span className="block text-right font-medium">
                      Respuesta
                    </span>
                    <span className="font-bold text-gray-700">12ms</span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">
                      Certificado
                    </span>
                    <span className="font-bold text-emerald-600">Válido</span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">Estado</span>
                    <span className="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold uppercase text-[10px]">
                      OPERATIVO
                    </span>
                  </div>
                </div>
              </div>

              {/* Endpoint 4 */}
              <div className="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/50 transition">
                <div className="flex items-center gap-3">
                  <div className="w-2.5 h-2.5 bg-yellow-500 rounded-full" />
                  <div>
                    <h4 className="font-bold text-gray-800 text-sm">
                      Integración API Externa SRI (Facturación Electrónica)
                    </h4>
                    <span className="text-xs font-mono text-gray-400">
                      https://sri.gob.ec/comprobantes-electronicos
                    </span>
                  </div>
                </div>
                <div className="flex items-center gap-6 text-xs text-gray-500">
                  <div>
                    <span className="block text-right font-medium">
                      Latencia
                    </span>
                    <span className="font-bold text-yellow-600">890ms</span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">
                      Certificado
                    </span>
                    <span className="font-bold text-yellow-600">
                      Expiración no disponible
                    </span>
                  </div>
                  <div>
                    <span className="block text-right font-medium">Estado</span>
                    <span className="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded font-bold uppercase text-[10px]">
                      DEGRADADO
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Información Adicional de Uptime Kuma */}
          <div className="p-5 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl text-white flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
            <div className="space-y-1">
              <h4 className="font-bold text-base flex items-center gap-1.5">
                <Activity className="w-5 h-5" />
                ¿Quieres integrar tu servidor real de Uptime Kuma?
              </h4>
              <p className="text-xs text-emerald-100 leading-relaxed max-w-2xl">
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
              className="px-4 py-2 bg-white text-emerald-700 font-bold rounded-lg text-xs hover:bg-emerald-50 transition shrink-0 flex items-center gap-1.5"
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
          <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div className="space-y-1">
                <h3 className="font-bold text-lg text-gray-800 flex items-center gap-2">
                  <Sparkles className="w-5 h-5 text-red-500" />
                  Consola de Diagnóstico Sintético (Playwright Engine)
                </h3>
                <p className="text-sm text-gray-500">
                  Ejecuta y simula flujos interactivos de usuarios en vivo
                  directamente en tus servidores de prueba para verificar login,
                  navegación y APIs en tiempo real.
                </p>
              </div>

              <button
                onClick={handleRunPlaywrightTest}
                disabled={isPlayingTest}
                className="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-5 rounded-lg transition shadow-md disabled:bg-red-300 flex items-center gap-2 self-start md:self-auto"
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
                    ? "bg-emerald-50 text-emerald-800 border-emerald-200"
                    : "bg-amber-50 text-amber-800 border-amber-200"
                }`}
              >
                <CheckCircle
                  className={`w-5 h-5 shrink-0 ${
                    playwrightMessage.type === "success"
                      ? "text-emerald-600"
                      : "text-amber-500"
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
              <div className="p-8 bg-gray-50 rounded-xl border border-dashed border-gray-200 text-center space-y-4">
                <div className="w-12 h-12 border-4 border-red-500 border-t-transparent rounded-full animate-spin mx-auto" />
                <div className="space-y-1 max-w-md mx-auto">
                  <p className="font-bold text-gray-700">
                    El motor de Playwright se está ejecutando...
                  </p>
                  <p className="text-xs text-gray-400">
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
              <div className="border border-gray-200 rounded-xl overflow-hidden shadow-md">
                <div className="bg-gray-100 p-3.5 border-b font-semibold text-sm text-gray-700 flex justify-between items-center">
                  <span className="flex items-center gap-1.5 font-bold">
                    <FileCode className="w-4 h-4 text-purple-600" />
                    Reporte Interactivo de Ejecución de Playwright
                  </span>
                  <div className="flex items-center gap-2">
                    <span className="text-xs bg-purple-100 text-purple-800 font-bold px-2 py-0.5 rounded-full">
                      En Vivo
                    </span>
                    <button
                      onClick={() => setPlaywrightReportUrl(null)}
                      className="text-xs text-red-500 hover:text-red-700 font-semibold"
                    >
                      Cerrar Reporte
                    </button>
                  </div>
                </div>
                {/* Cargamos el reporte HTML generado en Next.js public directory */}
                <iframe
                  src={playwrightReportUrl}
                  className="w-full h-[650px] border-none bg-white"
                />
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
