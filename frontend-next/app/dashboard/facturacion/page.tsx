"use client";

import { useState, useEffect, useCallback } from "react";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Tabs, TabsList, TabsTab, TabsPanel } from "@/components/ui/tabs";
import {
  FileText,
  Search,
  Loader2,
  Receipt,
  BookOpen,
  FileCheck,
  Download,
  ArrowUpDown,
  RefreshCw,
  Cloud,
  FileDown,
} from "lucide-react";
import { toast } from "sonner";
import { facturacionApi } from "@/lib/api";
import type {
  ComprobanteRow,
  RetencionRow,
  ComprobanteContableRow,
  ResumenData,
} from "@/lib/api";
interface DownloadError {
  open: boolean;
  title: string;
  message: string;
  type: "xml" | "ride";
  vetCod: string;
}

async function downloadFile(
  url: string,
  filename: string,
  vetCod: string,
  type: "xml" | "ride",
  onError: (err: DownloadError) => void
) {
  try {
    const res = await fetch(url);
    if (!res.ok) {
      const text = await res.text();
      let errorCode = "";
      try { const j = JSON.parse(text); errorCode = j.error_code || ""; } catch {}
      if (errorCode === "XML_NOT_FOUND" || errorCode === "RIDE_NOT_FOUND") {
        onError({
          open: true,
          title: type === "xml" ? "XML no encontrado" : "RIDE no encontrado",
          message:
            type === "xml"
              ? "El archivo XML de este comprobante no está disponible. Sincronice con el SRI para obtenerlo."
              : "El archivo PDF (RIDE) de este comprobante no está disponible. Sincronice con el SRI para generarlo.",
          type,
          vetCod,
        });
      } else {
        let msg = `Error ${res.status}`;
        try { const j = JSON.parse(text); msg = j.error || msg; } catch {}
        toast.error(msg);
      }
      return;
    }
    const blob = await res.blob();
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(a.href);
  } catch (e) {
    toast.error("Error al descargar: " + (e instanceof Error ? e.message : e));
  }
}

const TIP_COM_MAP: Record<string, string> = {
  I: "Ingreso",
  E: "Egreso",
  A: "Ajuste",
};

const TIP_COM_COLOR: Record<string, string> = {
  I: "bg-lightsuccess text-success",
  E: "bg-lighterror text-error",
  A: "bg-lightprimary text-primary",
};

function formatDate(dateStr: string | null) {
  if (!dateStr) return "-";
  const d = new Date(dateStr);
  return d.toLocaleDateString("es-EC", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });
}

function formatDateTime(dateStr: string | null) {
  if (!dateStr) return "-";
  const d = new Date(dateStr);
  return d.toLocaleDateString("es-EC", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function Badge({
  label,
  colorClass,
}: {
  label: string;
  colorClass: string;
}) {
  return (
    <span
      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass}`}
    >
      {label}
    </span>
  );
}

function SortHeader({ label, field, currentField, currentOrder, onSort }: {
  label: string;
  field: string;
  currentField: string;
  currentOrder: string;
  onSort: (field: string) => void;
}) {
  const active = currentField === field;
  return (
    <TableHead className="cursor-pointer select-none" onClick={() => onSort(field)}>
      <div className="flex items-center gap-1">
        {label}
        {active && (
          <ArrowUpDown className={`h-3 w-3 transition-transform ${currentOrder === 'ASC' ? 'rotate-180' : ''}`} />
        )}
      </div>
    </TableHead>
  );
}

function ComprobantesTab() {
  const [search, setSearch] = useState("");
  const [filtroEstado, setFiltroEstado] = useState("");
  const [data, setData] = useState<ComprobanteRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [records, setRecords] = useState(0);
  const [sortField, setSortField] = useState("");
  const [sortOrder, setSortOrder] = useState("DESC");
  const [dlg, setDlg] = useState<DownloadError>({ open: false, title: "", message: "", type: "xml", vetCod: "" });
  const [syncing, setSyncing] = useState(false);

  const handleSort = useCallback((field: string) => {
    setSortField((prev) => {
      if (prev === field) {
        setSortOrder((o) => (o === "ASC" ? "DESC" : "ASC"));
        return prev;
      }
      setSortOrder("DESC");
      return field;
    });
    setPage(1);
  }, []);

  const handleSync = useCallback(async () => {
    setSyncing(true);
    try {
      const userInfoStr = localStorage.getItem("user_info");
      let empCod = "";
      if (userInfoStr) {
        const userInfo = JSON.parse(userInfoStr);
        empCod = userInfo.empresa_id || "";
      }
      const body: Record<string, unknown> = {};
      const bdd = localStorage.getItem("bdd_activa");
      if (bdd) body.Bdd = bdd;
      if (empCod) body.Emp_Cod = empCod;

      const res = await fetch(`/api/v1/facturacion/comprobantes/${dlg.vetCod}/autorizar`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
      const json = await res.json();
      if (json.status && json.data?.success) {
        setDlg((p) => ({ ...p, open: false }));
        fetchData();
        // descargar ambos después de sincronizar
        const base = `/api/v1/facturacion/comprobantes/${dlg.vetCod}`;
        downloadFile(`${base}/xml`, `${dlg.vetCod}.xml`, dlg.vetCod, "xml", setDlg);
        downloadFile(`${base}/ride`, `${dlg.vetCod}.pdf`, dlg.vetCod, "ride", setDlg);
      } else {
        toast.error(json.error || "Error al sincronizar con el SRI");
      }
    } catch (e) {
      toast.error("Error de conexión: " + (e instanceof Error ? e.message : e));
    } finally {
      setSyncing(false);
    }
  }, [dlg.vetCod]);

  const fetchData = useCallback(async () => {
    setLoading(true);
    try {
      const userInfoStr = localStorage.getItem("user_info");
      let empCod = "";
      if (userInfoStr) {
        const userInfo = JSON.parse(userInfoStr);
        empCod = userInfo.empresa_id || "";
      }
      const params: Record<string, unknown> = {
        Emp_Cod: empCod,
        page,
        rows: 50,
      };
      if (search.trim()) params.search = search.trim();
      if (filtroEstado) params.estado = filtroEstado;
      if (sortField) { params.sort_field = sortField; params.sort_order = sortOrder; }

      const res = await facturacionApi.getComprobantes(params);
      if (res.status && res.data) {
        setData(res.data.rows || []);
        setRecords(res.data.records || 0);
        setTotalPages(res.data.total || 1);
      } else {
        setData([]);
        setRecords(0);
      }
    } catch {
      setData([]);
      setRecords(0);
    } finally {
      setLoading(false);
    }
  }, [search, filtroEstado, page, sortField, sortOrder]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const getElectColor = (val: string | null) => {
    if (val === "S") return "bg-lightsuccess text-success";
    return "bg-muted text-muted-foreground";
  };

  return (
    <div>
      <div className="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
        <div className="relative flex-1 min-w-[200px]">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por cliente o número..."
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            className="pl-8"
          />
        </div>
        <select
          value={filtroEstado}
          onChange={(e) => { setFiltroEstado(e.target.value); setPage(1); }}
          className="h-9 rounded-lg border border-input bg-background px-3 text-sm"
        >
          <option value="">Todos</option>
          <option value="electronicos">Electrónicos</option>
          <option value="no_electronicos">No Electrónicos</option>
        </select>
        <span className="text-sm text-muted-foreground whitespace-nowrap">
          {records} registros
        </span>
      </div>

      <div className="rounded-md border overflow-x-auto">
        <div className="min-w-[700px]">
          <Table>
          <TableHeader>
            <TableRow>
              <SortHeader label="#" field="Vet_Num" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Cliente" field="Prs_Nom" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Identificación" field="Prs_Ced" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Fecha" field="Vet_Sys" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Electrónico" field="Vet_Aut" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Autorización" field="Aut_Sri" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <TableHead>Observación</TableHead>
              <TableHead className="text-center">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
              {loading ? (
              <TableRow>
                <TableCell colSpan={8} className="text-center py-8">
                  <Loader2 className="h-6 w-6 animate-spin inline-block text-muted-foreground" />
                </TableCell>
              </TableRow>
            ) : data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                  No se encontraron comprobantes
                </TableCell>
              </TableRow>
            ) : (
              data.map((row) => (
                <TableRow key={row.Vet_Cod}>
                  <TableCell className="font-medium">{row.Vet_Num}</TableCell>
                  <TableCell>{row.Prs_Nom || "-"}</TableCell>
                  <TableCell className="font-mono text-xs">{row.Prs_Ced || "-"}</TableCell>
                  <TableCell>{formatDateTime(row.Vet_Sys)}</TableCell>
                  <TableCell>
                    <Badge
                      label={row.Vet_Aut === "S" ? "Sí" : "No"}
                      colorClass={getElectColor(row.Vet_Aut)}
                    />
                  </TableCell>
                  <TableCell className="font-mono text-xs max-w-[160px] truncate" title={row.Vet_Sri || ""}>
                    {row.Vet_Sri || "-"}
                  </TableCell>
                  <TableCell className="max-w-[200px] truncate" title={row.Vet_Obs || ""}>
                    {row.Vet_Obs || "-"}
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-1 justify-center">
                      <button
                        onClick={() => downloadFile(
                          `/api/v1/facturacion/comprobantes/${row.Vet_Cod}/xml`,
                          `${row.Vet_Num || row.Vet_Cod}.xml`,
                          row.Vet_Cod,
                          "xml",
                          setDlg
                        )}
                        className="inline-flex items-center justify-center rounded-md text-sm font-medium h-8 w-8 hover:bg-accent hover:text-accent-foreground"
                        title="Descargar XML"
                      >
                        <Download className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => downloadFile(
                          `/api/v1/facturacion/comprobantes/${row.Vet_Cod}/ride`,
                          `${row.Vet_Num || row.Vet_Cod}.pdf`,
                          row.Vet_Cod,
                          "ride",
                          setDlg
                        )}
                        className="inline-flex items-center justify-center rounded-md text-sm font-medium h-8 w-8 hover:bg-accent hover:text-accent-foreground"
                        title="Descargar RIDE (PDF)"
                      >
                        <FileText className="h-4 w-4" />
                      </button>
                      {row.Vet_Aut !== "S" && (
                        <button
                          onClick={() => {
                            setDlg({
                              open: true,
                              title: "Emitir Comprobante",
                              message: "El comprobante no está autorizado. ¿Desea emitirlo (generar XML, firmar y enviar al SRI)?",
                              type: "xml",
                              vetCod: row.Vet_Cod,
                            });
                          }}
                          className="inline-flex items-center justify-center rounded-md text-sm font-medium h-8 w-8 hover:bg-accent hover:text-accent-foreground"
                          title="Emitir (generar XML + autorizar SRI)"
                        >
                          <Cloud className="h-4 w-4" />
                        </button>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
          </Table>
        </div>
      </div>

      {totalPages > 1 && (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-2 mt-4">
          <span className="text-sm text-muted-foreground">
            Página {page} de {totalPages}
          </span>
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
            >
              Anterior
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={page >= totalPages}
              onClick={() => setPage((p) => p + 1)}
            >
              Siguiente
            </Button>
          </div>
        </div>
      )}

      {dlg.open && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40" onClick={() => setDlg((p) => ({ ...p, open: false }))}>
          <div className="bg-card rounded-lg shadow-boxShadow max-w-md w-full mx-4 p-6" onClick={(e) => e.stopPropagation()}>
            <h3 className="text-lg font-semibold text-dark mb-2">{dlg.title}</h3>
            <p className="text-sm text-muted-foreground mb-6">{dlg.message}</p>
            <div className="flex gap-3 justify-end">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setDlg((p) => ({ ...p, open: false }))}
              >
                Cerrar
              </Button>
              <Button
                onClick={handleSync}
                disabled={syncing}
                size="sm"
              >
                {syncing && <Loader2 className="h-4 w-4 animate-spin" />}
                {syncing ? "Sincronizando..." : "Sincronizar con el SRI"}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function RetencionesTab() {
  const [search, setSearch] = useState("");
  const [filtroEstado, setFiltroEstado] = useState("");
  const [data, setData] = useState<RetencionRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [records, setRecords] = useState(0);
  const [sortField, setSortField] = useState("");
  const [sortOrder, setSortOrder] = useState("DESC");
  const [dlgRet, setDlgRet] = useState<DownloadError>({ open: false, title: "", message: "", type: "xml", vetCod: "" });
  const [syncingRet, setSyncingRet] = useState(false);

  const handleSort = useCallback((field: string) => {
    setSortField((prev) => {
      if (prev === field) {
        setSortOrder((o) => (o === "ASC" ? "DESC" : "ASC"));
        return prev;
      }
      setSortOrder("DESC");
      return field;
    });
    setPage(1);
  }, []);

  const handleSyncRet = useCallback(async () => {
    setSyncingRet(true);
    try {
      const body = { ...getBddToken() };
      const res = await fetch(`/api/v1/facturacion/retenciones/${dlgRet.vetCod}/autorizar`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
      const json = await res.json();
      if (json.status && json.data?.success) {
        setDlgRet((p) => ({ ...p, open: false }));
        fetchData();
        const base = `/api/v1/facturacion/retenciones/${dlgRet.vetCod}`;
        downloadFile(`${base}/xml`, `${dlgRet.vetCod}.xml`, dlgRet.vetCod, "xml", setDlgRet);
        downloadFile(`${base}/ride`, `${dlgRet.vetCod}.pdf`, dlgRet.vetCod, "ride", setDlgRet);
      } else {
        toast.error(json.error || "Error al sincronizar con el SRI");
      }
    } catch (e) {
      toast.error("Error de conexión: " + (e instanceof Error ? e.message : e));
    } finally {
      setSyncingRet(false);
    }
  }, [dlgRet.vetCod]);

  const fetchData = useCallback(async () => {
    setLoading(true);
    try {
      const userInfoStr = localStorage.getItem("user_info");
      let empCod = "";
      if (userInfoStr) {
        const userInfo = JSON.parse(userInfoStr);
        empCod = userInfo.empresa_id || "";
      }
      const params: Record<string, unknown> = {
        Emp_Cod: empCod,
        page,
        rows: 50,
      };
      if (search.trim()) params.search = search.trim();
      if (filtroEstado) params.estado = filtroEstado;
      if (sortField) { params.sort_field = sortField; params.sort_order = sortOrder; }

      const res = await facturacionApi.getRetenciones(params);
      if (res.status && res.data) {
        setData(res.data.rows || []);
        setRecords(res.data.records || 0);
        setTotalPages(res.data.total || 1);
      } else {
        setData([]);
        setRecords(0);
      }
    } catch {
      setData([]);
      setRecords(0);
    } finally {
      setLoading(false);
    }
  }, [search, filtroEstado, page, sortField, sortOrder]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  function getBddToken() {
    const bdd = localStorage.getItem('bdd_activa') || '';
    const userInfo = localStorage.getItem('user_info');
    let empCod = '';
    if (userInfo) {
      try { empCod = JSON.parse(userInfo).empresa_id || ''; } catch {}
    }
    return { Bdd: bdd, Emp_Cod: empCod };
  }

  return (
    <div>
      <div className="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
        <div className="relative flex-1 min-w-[200px]">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por concepto o número..."
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            className="pl-8"
          />
        </div>
        <select
          value={filtroEstado}
          onChange={(e) => { setFiltroEstado(e.target.value); setPage(1); }}
          className="h-9 rounded-lg border border-input bg-background px-3 text-sm"
        >
          <option value="">Todas</option>
          <option value="electronicos">Electrónicas</option>
          <option value="no_electronicos">No Electrónicas</option>
        </select>
        <span className="text-sm text-muted-foreground whitespace-nowrap">
          {records} registros
        </span>
      </div>

      <div className="rounded-md border overflow-x-auto">
        <div className="min-w-[700px]">
          <Table>
          <TableHeader>
            <TableRow>
              <SortHeader label="#" field="Ret_Num" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Fecha" field="Ret_Fec" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Proveedor" field="Prs_Nom" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Concepto" field="Ret_Con" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Electrónico" field="Ret_Aut" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Autorización SRI" field="Ret_Sri" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Registro" field="Ret_Sys" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <TableHead className="text-center">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow>
                <TableCell colSpan={8} className="text-center py-8">
                  <Loader2 className="h-6 w-6 animate-spin inline-block text-muted-foreground" />
                </TableCell>
              </TableRow>
            ) : data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                  No se encontraron retenciones
                </TableCell>
              </TableRow>
            ) : (
              data.map((row) => (
                <TableRow key={row.Ret_Cod}>
                  <TableCell className="font-medium">{row.Ret_Num}</TableCell>
                  <TableCell>{formatDate(row.Ret_Fec)}</TableCell>
                  <TableCell>{row.Prs_Nom || "-"}</TableCell>
                  <TableCell className="max-w-[200px] truncate" title={row.Ret_Con || ""}>
                    {row.Ret_Con || "-"}
                  </TableCell>
                  <TableCell>
                    <Badge
                      label={row.Ret_Aut === "S" ? "Sí" : "No"}
                      colorClass={row.Ret_Aut === "S" ? "bg-lightsuccess text-success" : "bg-muted text-muted-foreground"}
                    />
                  </TableCell>
                  <TableCell className="font-mono text-xs max-w-[140px] truncate" title={row.Ret_Sri || ""}>
                    {row.Ret_Sri || "-"}
                  </TableCell>
                  <TableCell>{formatDateTime(row.Ret_Sys)}</TableCell>
                  <TableCell>
                    <div className="flex items-center gap-1 justify-center">
                      <button
                        onClick={() => downloadFile(
                          `/api/v1/facturacion/retenciones/${row.Ret_Cod}/xml`,
                          `${row.Ret_Num || row.Ret_Cod}.xml`,
                          row.Ret_Cod,
                          "xml",
                          setDlgRet
                        )}
                        className="inline-flex items-center justify-center rounded-md text-sm font-medium h-8 w-8 hover:bg-accent hover:text-accent-foreground"
                        title="Descargar XML"
                      >
                        <FileDown className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => downloadFile(
                          `/api/v1/facturacion/retenciones/${row.Ret_Cod}/ride`,
                          `${row.Ret_Num || row.Ret_Cod}.pdf`,
                          row.Ret_Cod,
                          "ride",
                          setDlgRet
                        )}
                        className="inline-flex items-center justify-center rounded-md text-sm font-medium h-8 w-8 hover:bg-accent hover:text-accent-foreground"
                        title="Descargar RIDE (PDF)"
                      >
                        <FileText className="h-4 w-4" />
                      </button>
                      {row.Ret_Aut !== "S" && (
                        <button
                          onClick={() => {
                            setDlgRet({
                              open: true,
                              title: "Sincronizar Retención",
                              message: "La retención no está autorizada. ¿Desea sincronizarla con el SRI?",
                              type: "xml",
                              vetCod: row.Ret_Cod,
                            });
                          }}
                          className="inline-flex items-center justify-center rounded-md text-sm font-medium h-8 w-8 hover:bg-accent hover:text-accent-foreground"
                          title="Sincronizar con SRI"
                        >
                          <Cloud className="h-4 w-4" />
                        </button>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
          </Table>
        </div>
      </div>

      {totalPages > 1 && (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-2 mt-4">
          <span className="text-sm text-muted-foreground">
            Página {page} de {totalPages}
          </span>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
              Anterior
            </Button>
            <Button variant="outline" size="sm" disabled={page >= totalPages} onClick={() => setPage((p) => p + 1)}>
              Siguiente
            </Button>
          </div>
        </div>
      )}

      {dlgRet.open && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40" onClick={() => setDlgRet((p) => ({ ...p, open: false }))}>
          <div className="bg-card rounded-lg shadow-boxShadow max-w-md w-full mx-4 p-6" onClick={(e) => e.stopPropagation()}>
            <h3 className="text-lg font-semibold text-dark mb-2">{dlgRet.title}</h3>
            <p className="text-sm text-muted-foreground mb-6">{dlgRet.message}</p>
            <div className="flex gap-3 justify-end">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setDlgRet((p) => ({ ...p, open: false }))}
              >
                Cerrar
              </Button>
              <Button
                onClick={handleSyncRet}
                disabled={syncingRet}
                size="sm"
              >
                {syncingRet && <Loader2 className="h-4 w-4 animate-spin" />}
                {syncingRet ? "Sincronizando..." : "Sincronizar con el SRI"}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function ComprobantesContablesTab() {
  const [search, setSearch] = useState("");
  const [data, setData] = useState<ComprobanteContableRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [records, setRecords] = useState(0);
  const [sortField, setSortField] = useState("");
  const [sortOrder, setSortOrder] = useState("DESC");

  const handleSort = useCallback((field: string) => {
    setSortField((prev) => {
      if (prev === field) {
        setSortOrder((o) => (o === "ASC" ? "DESC" : "ASC"));
        return prev;
      }
      setSortOrder("DESC");
      return field;
    });
    setPage(1);
  }, []);

  const fetchData = useCallback(async () => {
    setLoading(true);
    try {
      const userInfoStr = localStorage.getItem("user_info");
      let empCod = "";
      if (userInfoStr) {
        const userInfo = JSON.parse(userInfoStr);
        empCod = userInfo.empresa_id || "";
      }
      const params: Record<string, unknown> = {
        Emp_Cod: empCod,
        page,
        rows: 50,
      };
      if (search.trim()) params.search = search.trim();
      if (sortField) { params.sort_field = sortField; params.sort_order = sortOrder; }

      const res = await facturacionApi.getComprobantesContables(params);
      if (res.status && res.data) {
        setData(res.data.rows || []);
        setRecords(res.data.records || 0);
        setTotalPages(res.data.total || 1);
      } else {
        setData([]);
        setRecords(0);
      }
    } catch {
      setData([]);
      setRecords(0);
    } finally {
      setLoading(false);
    }
  }, [search, page, sortField, sortOrder]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const formatVal = (val: string) => {
    const num = parseFloat(val);
    if (isNaN(num)) return "$0.00";
    return "$" + num.toLocaleString("es-EC", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  return (
    <div>
      <div className="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
        <div className="relative flex-1 min-w-[200px]">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por concepto o número..."
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            className="pl-8"
          />
        </div>
        <span className="text-sm text-muted-foreground whitespace-nowrap">
          {records} registros
        </span>
      </div>

      <div className="rounded-md border overflow-x-auto">
        <div className="min-w-[700px]">
          <Table>
          <TableHeader>
            <TableRow>
              <SortHeader label="#" field="Com_Num" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Fecha" field="Com_Fec" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Tipo" field="Com_Tip" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Concepto" field="Com_Con" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <SortHeader label="Valor" field="Com_Val" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
              <TableHead>Cliente / Proveedor</TableHead>
              <SortHeader label="Módulo" field="Com_Mod" currentField={sortField} currentOrder={sortOrder} onSort={handleSort} />
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center py-8">
                  <Loader2 className="h-6 w-6 animate-spin inline-block text-muted-foreground" />
                </TableCell>
              </TableRow>
            ) : data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                  No se encontraron comprobantes contables
                </TableCell>
              </TableRow>
            ) : (
              data.map((row) => (
                <TableRow key={row.Com_Cod}>
                  <TableCell className="font-medium">{row.Com_Num}</TableCell>
                  <TableCell>{formatDate(row.Com_Fec)}</TableCell>
                  <TableCell>
                    <Badge
                      label={TIP_COM_MAP[row.Com_Tip] || row.Com_Tip}
                      colorClass={TIP_COM_COLOR[row.Com_Tip] || "bg-muted text-muted-foreground"}
                    />
                  </TableCell>
                  <TableCell className="max-w-[300px] truncate" title={row.Com_Con || ""}>
                    {row.Com_Con || "-"}
                  </TableCell>
                  <TableCell className="font-mono text-sm">{formatVal(row.Com_Val)}</TableCell>
                  <TableCell>{row.Cli_Nom || row.Prv_Nom || "-"}</TableCell>
                  <TableCell className="text-xs">{row.Com_Mod || row.Tia_Abr || "-"}</TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
          </Table>
        </div>
      </div>

      {totalPages > 1 && (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-2 mt-4">
          <span className="text-sm text-muted-foreground">
            Página {page} de {totalPages}
          </span>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
              Anterior
            </Button>
            <Button variant="outline" size="sm" disabled={page >= totalPages} onClick={() => setPage((p) => p + 1)}>
              Siguiente
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}

export default function FacturacionPage() {
  const [resumen, setResumen] = useState<ResumenData | null>(null);

  useEffect(() => {
    const loadResumen = async () => {
      try {
        const userInfoStr = localStorage.getItem("user_info");
        let empCod = "";
        if (userInfoStr) {
          const userInfo = JSON.parse(userInfoStr);
          empCod = userInfo.empresa_id || "";
        }
        const res = await facturacionApi.getResumen({ Emp_Cod: empCod });
        if (res.status && res.data) {
          setResumen(res.data);
        }
      } catch {
        // ignore
      }
    };
    loadResumen();
  }, []);

  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-dark">Facturación Electrónica</h1>
        <p className="text-sm text-muted-foreground mt-1">
          Comprobantes electrónicos, retenciones y comprobantes contables
        </p>
      </div>

      {resumen && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Facturas</CardTitle>
              <FileText className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{resumen.facturas.total}</div>
              <p className="text-xs text-muted-foreground">
                {resumen.facturas.electronicos} electrónicas
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Retenciones</CardTitle>
              <Receipt className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{resumen.retenciones.total}</div>
              <p className="text-xs text-muted-foreground">
                {resumen.retenciones.electronicos} electrónicas
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Comprobantes Contables</CardTitle>
              <BookOpen className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{resumen.comprobantes.total}</div>
              <p className="text-xs text-muted-foreground">Totales</p>
            </CardContent>
          </Card>
        </div>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Listado de Documentos</CardTitle>
          <CardDescription>
            Explore y consulte los documentos electrónicos del sistema
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Tabs defaultValue="comprobantes">
            <TabsList>
              <TabsTab value="comprobantes">
                <FileCheck className="h-4 w-4 mr-2" />
                Facturas Electrónicas
              </TabsTab>
              <TabsTab value="retenciones">
                <Receipt className="h-4 w-4 mr-2" />
                Retenciones
              </TabsTab>
              <TabsTab value="contables">
                <BookOpen className="h-4 w-4 mr-2" />
                Comprobantes Contables
              </TabsTab>
            </TabsList>
            <TabsPanel value="comprobantes">
              <ComprobantesTab />
            </TabsPanel>
            <TabsPanel value="retenciones">
              <RetencionesTab />
            </TabsPanel>
            <TabsPanel value="contables">
              <ComprobantesContablesTab />
            </TabsPanel>
          </Tabs>
        </CardContent>
      </Card>
    </div>
  );
}
