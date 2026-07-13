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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  FileText,
  Plus,
  Search,
  Eye,
  Edit,
  Loader2,
  X,
  AlertCircle,
} from "lucide-react";
import { manifiestosApi, clientesApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import type { ManifiestoGridRow } from "@/lib/api";
import type { Cliente } from "@/lib/api-types";

const ESTADO_MAP: Record<string, string> = {
  P: "Pendiente",
  GE: "Garita In",
  A: "Aprobado",
  GS: "Garita Out",
  F: "Facturado",
  R: "Rechazado",
};

const ESTADO_COLOR: Record<string, string> = {
  P: "bg-lightwarning text-warning",
  GE: "bg-lightprimary text-primary",
  A: "bg-lightsuccess text-success",
  GS: "bg-lightinfo text-info",
  F: "bg-muted text-muted-foreground",
  R: "bg-lighterror text-error",
};

export default function ManifiestosPage() {
  const [searchTerm, setSearchTerm] = useState("");
  const [filtroEstado, setFiltroEstado] = useState("");
  const [manifiestos, setManifiestos] = useState<ManifiestoGridRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [totalRecords, setTotalRecords] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  // Estados de modales
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [selectedManifiesto, setSelectedManifiesto] =
    useState<ManifiestoGridRow | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [detailData, setDetailData] = useState<any>(null);

  const [showCreateModal, setShowCreateModal] = useState(false);
  const [createLoading, setCreateLoading] = useState(false);
  const [createError, setCreateError] = useState<string | null>(null);
  const { data: clientesData } = useQuery(() => clientesApi.obtener(), {
    auto: true,
  });
  const clientes = Array.isArray(clientesData?.data)
    ? (clientesData.data as Cliente[])
    : [];

  const [formData, setFormData] = useState({
    ManNum: "",
    Man_Pes: "",
    Cli_Cod: "",
    Pla_Nom: "",
    Veh_Pla: "",
    chofer: "",
    Tde_Des: "Relaves Mineros",
    Man_Obs: "",
  });

  const fetchManifiestos = useCallback(async () => {
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
        page: currentPage,
        rows: 50,
      };

      if (searchTerm.trim()) {
        params.search = searchTerm.trim();
        params.op_opciones = "p";
      }

      if (filtroEstado) {
        params.filtro_estado = filtroEstado;
      }

      const res = await manifiestosApi.obtener(params);

      if (res.status && res.data) {
        setManifiestos(res.data.rows || []);
        setTotalRecords(res.data.records || 0);
        setTotalPages(res.data.total || 1);
      } else {
        setManifiestos([]);
        setTotalRecords(0);
      }
    } catch {
      setManifiestos([]);
      setTotalRecords(0);
    } finally {
      setLoading(false);
    }
  }, [searchTerm, filtroEstado, currentPage]);

  useEffect(() => {
    fetchManifiestos();
  }, [fetchManifiestos]);

  const getEstadoLabel = (manifiesto: ManifiestoGridRow): string => {
    const tip = manifiesto.Man_Tip || "";
    return ESTADO_MAP[tip] || manifiesto.estado || "Desconocido";
  };

  const getEstadoColor = (manifiesto: ManifiestoGridRow): string => {
    const tip = manifiesto.Man_Tip || "";
    return ESTADO_COLOR[tip] || "bg-muted text-muted-foreground";
  };

  const formatFecha = (fecha: string, hora: string): string => {
    if (!fecha) return "-";
    return `${fecha} ${hora || ""}`;
  };

  const handleOpenDetail = async (manifiesto: ManifiestoGridRow) => {
    setSelectedManifiesto(manifiesto);
    setShowDetailModal(true);
    setDetailLoading(true);
    try {
      const res = await manifiestosApi.obtenerDetalle(manifiesto.Man_Cod);
      if (res.status && res.data) {
        setDetailData(res.data);
      } else {
        setDetailData(null);
      }
    } catch {
      setDetailData(null);
    } finally {
      setDetailLoading(false);
    }
  };

  const handleOpenCreate = () => {
    setFormData({
      ManNum: "",
      Man_Pes: "",
      Cli_Cod: "",
      Pla_Nom: "",
      Veh_Pla: "",
      chofer: "",
      Tde_Des: "Relaves Mineros",
      Man_Obs: "",
    });
    setCreateError(null);
    setShowCreateModal(true);
  };

  const handleCreateSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setCreateLoading(true);
    setCreateError(null);
    try {
      const userInfoStr = localStorage.getItem("user_info");
      let bdd = "";
      let empCod = "";
      let usuCod = "";
      if (userInfoStr) {
        const userInfo = JSON.parse(userInfoStr);
        bdd = userInfo.Bdd || "";
        empCod = userInfo.empresa_id || "";
        usuCod = userInfo.usuario_id || "";
      }

      // En Slim, no definimos una ruta explícita para crear manifiesto, pero en caso de que esté implementada en el backend
      // enviamos la solicitud. Si no, simulamos un éxito local y refrescamos.
      // El ERP usualmente los inserta en su módulo tradicional.
      const payload = {
        ...formData,
        Bdd: bdd,
        Emp_Cod: empCod,
        Usu_Cod: usuCod,
      };

      const res = await fetch("/api/v1/manifiestos/crear", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      }).then((r) => r.json());

      if (res.status) {
        setShowCreateModal(false);
        fetchManifiestos();
      } else {
        setCreateError(
          res.error || "Ocurrió un error al guardar el manifiesto",
        );
      }
    } catch (err) {
      setCreateError(
        err instanceof Error ? err.message : "Error al guardar el manifiesto",
      );
    } finally {
      setCreateLoading(false);
    }
  };

  const startRecord = (currentPage - 1) * 50 + 1;
  const endRecord = Math.min(currentPage * 50, totalRecords);

  return (
    <div className="space-y-6 lg:space-y-8">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 className="text-3xl font-bold tracking-tight text-dark">
            Manifiestos Electrónicos
          </h2>
          <p className="text-muted-foreground mt-1">
            Gestión, trazabilidad y control de transporte de relaves mineros.
          </p>
        </div>
        <Button className="flex items-center gap-2" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4" /> Nuevo Manifiesto
        </Button>
      </div>

      <Card>
        <CardContent className="p-4 flex gap-4 items-center">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              type="text"
              placeholder="Buscar por Nro, Chofer o Vehículo..."
              className="pl-9"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          <Select
            value={filtroEstado}
            onValueChange={(v) => setFiltroEstado(v ?? "")}
          >
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="Estado" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">Todos</SelectItem>
              <SelectItem value="P">Pendiente</SelectItem>
              <SelectItem value="GE">Garita In</SelectItem>
              <SelectItem value="A">Aprobado</SelectItem>
              <SelectItem value="GS">Garita Out</SelectItem>
              <SelectItem value="F">Facturado</SelectItem>
              <SelectItem value="R">Rechazado</SelectItem>
            </SelectContent>
          </Select>
        </CardContent>
      </Card>

      <Card>
        {loading ? (
          <div className="flex items-center justify-center h-48">
            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <div className="min-w-[700px]">
                <Table>
                  <TableHeader>
                <TableRow>
                  <TableHead>Nro. Manifiesto</TableHead>
                  <TableHead>Fecha Ingreso</TableHead>
                  <TableHead>Cliente</TableHead>
                  <TableHead>Origen</TableHead>
                  <TableHead>Vehículo</TableHead>
                  <TableHead>Chofer</TableHead>
                  <TableHead>Tipo Desecho</TableHead>
                  <TableHead className="text-right">Peso (Ton)</TableHead>
                  <TableHead className="text-center">Estado</TableHead>
                  <TableHead className="text-center">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {manifiestos.map((item) => (
                  <TableRow key={item.Man_Cod}>
                    <TableCell className="font-medium text-primary">
                      {item.ManNum || item.Man_Cod}
                    </TableCell>
                    <TableCell>
                      {formatFecha(item.Man_Fec, item.Man_Hor)}
                    </TableCell>
                    <TableCell>{item.cliente || "-"}</TableCell>
                    <TableCell>{item.Pla_Nom || "-"}</TableCell>
                    <TableCell>{item.Veh_Pla || "-"}</TableCell>
                    <TableCell>{item.chofer || "-"}</TableCell>
                    <TableCell>{item.Tde_Des || "-"}</TableCell>
                    <TableCell className="text-right">
                      {item.Man_Pes || "0"}
                    </TableCell>
                    <TableCell className="text-center">
                      <span
                        className={`px-2.5 py-1 rounded-full text-xs font-medium ${getEstadoColor(item)}`}
                      >
                        {getEstadoLabel(item)}
                      </span>
                    </TableCell>
                    <TableCell className="text-center">
                      <Button
                        variant="ghost"
                        size="icon"
                        title="Ver detalles"
                        onClick={() => handleOpenDetail(item)}
                      >
                        <Eye className="h-4 w-4 text-muted-foreground hover:text-primary" />
                      </Button>
                      <Button variant="ghost" size="icon" title="Editar">
                        <Edit className="h-4 w-4 text-muted-foreground hover:text-primary" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
                {manifiestos.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={10} className="h-24 text-center">
                      No se encontraron manifiestos.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
              </div>
            </div>
            <div className="p-4 border-t flex items-center justify-between text-sm text-muted-foreground">
              <div>
                {totalRecords > 0
                  ? `Mostrando ${startRecord} a ${endRecord} de ${totalRecords} registros`
                  : "Sin registros"}
              </div>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={currentPage <= 1}
                  onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                >
                  Anterior
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={currentPage >= totalPages}
                  onClick={() => setCurrentPage((p) => p + 1)}
                >
                  Siguiente
                </Button>
              </div>
            </div>
          </>
        )}
      </Card>

      {/* Modal Ver Detalles de Manifiesto */}
      {showDetailModal && selectedManifiesto && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-2xl animate-fade-in flex flex-col max-h-[90vh]">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <div>
                <h3 className="text-xl font-bold text-dark">
                  Manifiesto Electrónico Nro:{" "}
                  {selectedManifiesto.ManNum || selectedManifiesto.Man_Cod}
                </h3>
                <p className="text-xs text-muted-foreground mt-0.5">
                  Código Único Sistema: {selectedManifiesto.Man_Cod}
                </p>
              </div>
              <button
                onClick={() => setShowDetailModal(false)}
                className="text-muted-foreground hover:text-foreground"
              >
                <X className="h-6 w-6" />
              </button>
            </div>
            <div className="overflow-y-auto flex-1 p-6 space-y-6">
              {detailLoading ? (
                <div className="flex flex-col items-center justify-center py-12 gap-2">
                  <Loader2 className="h-8 w-8 animate-spin text-primary" />
                  <span className="text-sm text-muted-foreground">
                    Cargando detalles de la base de datos...
                  </span>
                </div>
              ) : (
                <div className="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                  <div className="col-span-2 sm:col-span-1 border-b pb-2">
                    <span className="block text-xs font-semibold text-muted-foreground uppercase">
                      Cliente
                    </span>
                    <span className="font-medium text-dark">
                      {selectedManifiesto.cliente || "-"}
                    </span>
                  </div>
                  <div className="col-span-2 sm:col-span-1 border-b pb-2">
                    <span className="block text-xs font-semibold text-muted-foreground uppercase">
                      Identificación (RUC)
                    </span>
                    <span className="font-medium text-dark">
                      {selectedManifiesto.Cli_Ced || "-"}
                    </span>
                  </div>
                  <div className="col-span-2 sm:col-span-1 border-b pb-2">
                    <span className="block text-xs font-semibold text-muted-foreground uppercase">
                      Planta de Origen
                    </span>
                    <span className="font-medium text-dark">
                      {selectedManifiesto.Pla_Nom || "-"}
                    </span>
                  </div>
                  <div className="col-span-2 sm:col-span-1 border-b pb-2">
                    <span className="block text-xs font-semibold text-muted-foreground uppercase">
                      Tipo de Residuo
                    </span>
                    <span className="font-medium text-dark">
                      {selectedManifiesto.Tde_Des || "-"}
                    </span>
                  </div>
                  <div className="col-span-2 sm:col-span-1 border-b pb-2">
                    <span className="block text-xs font-semibold text-muted-foreground uppercase">
                      Vehículo / Placa
                    </span>
                    <span className="font-medium text-dark">
                      {selectedManifiesto.Veh_Pla || "-"}
                    </span>
                  </div>
                  <div className="col-span-2 sm:col-span-1 border-b pb-2">
                    <span className="block text-xs font-semibold text-muted-foreground uppercase">
                      Chofer Autorizado
                    </span>
                    <span className="font-medium text-dark">
                      {selectedManifiesto.chofer || "-"}
                    </span>
                  </div>
                  <div className="col-span-2 sm:col-span-1 border-b pb-2">
                    <span className="block text-xs font-semibold text-muted-foreground uppercase">
                      Peso Total Recibido
                    </span>
                    <span className="font-medium text-primary font-bold">
                      {selectedManifiesto.Man_Pes || "0.00"} Toneladas
                    </span>
                  </div>
                  <div className="col-span-2 sm:col-span-1 border-b pb-2">
                    <span className="block text-xs font-semibold text-muted-foreground uppercase">
                      Fecha / Hora de Registro
                    </span>
                    <span className="font-medium text-dark">
                      {formatFecha(
                        selectedManifiesto.Man_Fec,
                        selectedManifiesto.Man_Hor,
                      )}
                    </span>
                  </div>
                  <div className="col-span-2 sm:col-span-1 border-b pb-2">
                    <span className="block text-xs font-semibold text-muted-foreground uppercase">
                      Estado de la Carga
                    </span>
                    <span
                      className={`inline-block px-2.5 py-1 rounded-full text-xs font-medium mt-1 ${getEstadoColor(selectedManifiesto)}`}
                    >
                      {getEstadoLabel(selectedManifiesto)}
                    </span>
                  </div>
                  <div className="col-span-2 sm:col-span-1 border-b pb-2">
                    <span className="block text-xs font-semibold text-muted-foreground uppercase">
                      Usuario Creador
                    </span>
                    <span className="font-medium text-dark">
                      {selectedManifiesto.usuario_creador || "-"}
                    </span>
                  </div>
                </div>
              )}
            </div>
            <div className="p-6 border-t bg-muted/50 flex justify-end gap-3 rounded-b-lg">
              <Button type="button" onClick={() => setShowDetailModal(false)}>
                Cerrar Detalles
              </Button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Nuevo Manifiesto */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-lg animate-fade-in flex flex-col max-h-[90vh]">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-xl font-bold text-dark">
                Nuevo Manifiesto Electrónico
              </h3>
              <button
                onClick={() => setShowCreateModal(false)}
                className="text-muted-foreground hover:text-foreground"
              >
                <X className="h-6 w-6" />
              </button>
            </div>
            <form
              onSubmit={handleCreateSubmit}
              className="overflow-y-auto flex-1 p-6 space-y-4"
            >
              {createError && (
                <div className="bg-lighterror text-error p-3 rounded-md flex items-start gap-2 text-sm">
                  <AlertCircle className="h-5 w-5 shrink-0" />
                  <span>{createError}</span>
                </div>
              )}
              <div className="grid grid-cols-2 gap-4">
                <div className="col-span-2 sm:col-span-1">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Nro. Manifiesto Físico *
                  </label>
                  <Input
                    required
                    value={formData.ManNum}
                    onChange={(e) =>
                      setFormData({ ...formData, ManNum: e.target.value })
                    }
                    placeholder="Ej: 001-4563"
                  />
                </div>
                <div className="col-span-2 sm:col-span-1">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Peso (Toneladas) *
                  </label>
                  <Input
                    required
                    type="number"
                    step="0.01"
                    value={formData.Man_Pes}
                    onChange={(e) =>
                      setFormData({ ...formData, Man_Pes: e.target.value })
                    }
                    placeholder="0.00"
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Cliente *
                  </label>
                  <select
                    required
                    value={formData.Cli_Cod}
                    onChange={(e) =>
                      setFormData({ ...formData, Cli_Cod: e.target.value })
                    }
                    className="w-full px-3 py-2 border border-border rounded-md text-sm text-foreground bg-background"
                  >
                    <option value="">-- Seleccione Cliente --</option>
                    {clientes.map((c) => (
                      <option key={c.Cli_Cod} value={c.Cli_Cod}>
                        {c.Cli_Nom}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Planta de Origen *
                  </label>
                  <Input
                    required
                    value={formData.Pla_Nom}
                    onChange={(e) =>
                      setFormData({ ...formData, Pla_Nom: e.target.value })
                    }
                    placeholder="Nombre de la mina o planta generadora"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Placa del Vehículo *
                  </label>
                  <Input
                    required
                    value={formData.Veh_Pla}
                    onChange={(e) =>
                      setFormData({ ...formData, Veh_Pla: e.target.value })
                    }
                    placeholder="Ej: ABA-1234"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Chofer Autorizado *
                  </label>
                  <Input
                    required
                    value={formData.chofer}
                    onChange={(e) =>
                      setFormData({ ...formData, chofer: e.target.value })
                    }
                    placeholder="Nombre del chofer"
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Tipo de Desecho / Residuo
                  </label>
                  <Input
                    required
                    value={formData.Tde_Des}
                    onChange={(e) =>
                      setFormData({ ...formData, Tde_Des: e.target.value })
                    }
                    placeholder="Relaves, lodos, etc."
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Observación
                  </label>
                  <Input
                    value={formData.Man_Obs}
                    onChange={(e) =>
                      setFormData({ ...formData, Man_Obs: e.target.value })
                    }
                    placeholder="Detalles sobre el transporte o carga"
                  />
                </div>
              </div>
              <div className="border-t pt-4 flex justify-end gap-3 bg-card sticky bottom-0">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setShowCreateModal(false)}
                >
                  Cancelar
                </Button>
                <Button type="submit" disabled={createLoading}>
                  {createLoading ? (
                    <Loader2 className="h-4 w-4 animate-spin mr-1" />
                  ) : null}
                  Emitir Manifiesto
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
