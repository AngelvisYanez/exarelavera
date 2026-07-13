"use client";

import { useState } from "react";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
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
import { Tabs, TabsList, TabsTab, TabsPanel } from "@/components/ui/tabs";
import { Plus, Search, Pencil, Trash2, Loader2, X, AlertCircle } from "lucide-react";
import { contabilidadApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import type { PlanCuenta, PeriodoContable, ComprobanteContable } from "@/lib/api-types";
import { toast } from "sonner";
import { useConfirm } from "@/lib/hooks/use-confirm";

interface PeriodoForm {
  Pec_Cod: number;
  Pec_Fei: string;
  Pec_Fef: string;
  Pla_Cod: number;
}

interface ComprobanteForm {
  Pec_Cod: number;
  Tia_Cod: number;
  Com_Fec: string;
  Com_Num: number;
  Com_Con: string;
}

function PlanCuentasTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => contabilidadApi.planCuentas(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<PlanCuenta>>({
    Pla_Cod: 0,
    Pla_Fec: "",
    Pla_Obs: "",
    Pla_Est: "A",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as PlanCuenta[]) : [];
  const filtered = items.filter(
    (c) => !search || c.Pla_Obs?.toLowerCase().includes(search.toLowerCase()) || String(c.Pla_Cod).includes(search),
  );

  const handleOpenCreate = () => {
    setFormData({ Pla_Cod: 0, Pla_Fec: "", Pla_Obs: "", Pla_Est: "A" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (item: PlanCuenta) => {
    setFormData(item);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (item: PlanCuenta) => {
    if (!(await confirm(`¿Eliminar la cuenta "${item.Pla_Obs}" (${item.Pla_Cod})?`))) return;
    try {
      const res = await contabilidadApi.eliminarPlanCuenta(item.Pla_Cod);
      if (res.success) {
        refetch();
        toast.success("Eliminado correctamente");
      } else {
        toast.error(res.message || "Error al eliminar");
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Error de conexión");
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setModalLoading(true);
    setModalError(null);
    try {
      const payload = { ...formData };

      let res;
      if (isEdit) {
        res = await contabilidadApi.modificarPlanCuenta(payload);
      } else {
        res = await contabilidadApi.crearPlanCuenta(payload);
      }

      if (res.success) {
        setShowModal(false);
        refetch();
      } else {
        setModalError(res.message || "Error al procesar la solicitud");
      }
    } catch (err) {
      setModalError(err instanceof Error ? err.message : "Error de conexión");
    } finally {
      setModalLoading(false);
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Plan de Cuentas</CardTitle>
          <CardDescription>
            Catálogo de cuentas contables del sistema.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Cuenta
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar cuenta..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        {loading ? (
          <div className="flex justify-center py-8">
            <Loader2 className="h-6 w-6 animate-spin" />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <div className="min-w-[700px]">
              <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Código</TableHead>
                <TableHead>Observaciones</TableHead>
                <TableHead>Estado</TableHead>
                <TableHead className="text-center">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((c: PlanCuenta, i: number) => (
                <TableRow key={c.Pla_Cod || i}>
                  <TableCell className="font-semibold text-primary">
                    {c.Pla_Cod || "-"}
                  </TableCell>
                  <TableCell className="font-medium">{c.Pla_Obs || "-"}</TableCell>
                  <TableCell>{c.Pla_Est === "A" ? "Activa" : "Inactiva"}</TableCell>
                  <TableCell className="text-center">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleOpenEdit(c)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleDelete(c)}
                    >
                      <Trash2 className="h-4 w-4 text-error" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
              {filtered.length === 0 && (
                <TableRow>
                  <TableCell colSpan={4} className="text-center h-24">
                    No se encontraron cuentas.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Plan de Cuentas */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Cuenta" : "Nueva Cuenta"}
              </h3>
              <button
                onClick={() => setShowModal(false)}
                className="text-muted-foreground hover:text-dark"
              >
                <X className="h-5 w-5" />
              </button>
            </div>
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              {modalError && (
                <div className="bg-lighterror text-error p-3 rounded-md flex items-start gap-2 text-sm">
                  <AlertCircle className="h-5 w-5 shrink-0" />
                  <span>{modalError}</span>
                </div>
              )}
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Código *
                </label>
                <Input
                  required
                  type="number"
                  value={formData.Pla_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Pla_Cod: Number(e.target.value) })
                  }
                  placeholder="Ej: 1101"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Observaciones *
                </label>
                <Input
                  required
                  value={formData.Pla_Obs || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Pla_Obs: e.target.value })
                  }
                  placeholder="Ej: Caja, Banco, Clientes..."
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Fecha *
                  </label>
                  <Input
                    required
                    type="date"
                    value={formData.Pla_Fec || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Pla_Fec: e.target.value })
                    }
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Estado *
                  </label>
                  <select
                    value={formData.Pla_Est || "A"}
                    onChange={(e) =>
                      setFormData({ ...formData, Pla_Est: e.target.value })
                    }
                    className="w-full px-3 py-2 border border-input bg-background rounded-md text-sm text-black"
                  >
                    <option value="A">Activa</option>
                    <option value="I">Inactiva</option>
                  </select>
                </div>
              </div>
              <div className="border-t pt-4 flex justify-end gap-3">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setShowModal(false)}
                >
                  Cancelar
                </Button>
                <Button type="submit" disabled={modalLoading}>
                  {modalLoading ? (
                    <Loader2 className="h-4 w-4 animate-spin mr-1" />
                  ) : null}
                  {isEdit ? "Guardar Cambios" : "Crear Cuenta"}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
      {ConfirmDialog}
    </Card>
  );
}

function PeriodosTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => contabilidadApi.periodos(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<PeriodoForm>({
    Pec_Cod: 0,
    Pec_Fei: "",
    Pec_Fef: "",
    Pla_Cod: 0,
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as PeriodoContable[]) : [];
  const filtered = items.filter(
    (p) =>
      !search ||
      String(p.Pec_Cod).includes(search) ||
      String(p.Pla_Cod).includes(search),
  );

  const handleOpenCreate = () => {
    setFormData({ Pec_Cod: 0, Pec_Fei: "", Pec_Fef: "", Pla_Cod: 0 });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (item: PeriodoContable) => {
    setFormData({ Pec_Cod: item.Pec_Cod, Pec_Fei: item.Pec_Fei, Pec_Fef: item.Pec_Fef, Pla_Cod: item.Pla_Cod });
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (item: PeriodoContable) => {
    if (!(await confirm(`¿Eliminar el periodo ${item.Pec_Cod}?`))) return;
    try {
      const res = await contabilidadApi.eliminarPeriodo(item.Pec_Cod);
      if (res.success) {
        refetch();
        toast.success("Eliminado correctamente");
      } else {
        toast.error(res.message || "Error al eliminar");
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Error de conexión");
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setModalLoading(true);
    setModalError(null);
    try {
      const payload = { ...formData };

      let res;
      if (isEdit) {
        res = await contabilidadApi.modificarPeriodo(payload);
      } else {
        res = await contabilidadApi.crearPeriodo(payload);
      }

      if (res.success) {
        setShowModal(false);
        refetch();
      } else {
        setModalError(res.message || "Error al procesar la solicitud");
      }
    } catch (err) {
      setModalError(err instanceof Error ? err.message : "Error de conexión");
    } finally {
      setModalLoading(false);
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Periodos Contables</CardTitle>
          <CardDescription>
            Periodos fiscales y fechas de apertura/cierre.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Periodo
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar periodo..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        {loading ? (
          <div className="flex justify-center py-8">
            <Loader2 className="h-6 w-6 animate-spin" />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <div className="min-w-[700px]">
              <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Periodo</TableHead>
                <TableHead>Fecha Inicio</TableHead>
                <TableHead>Fecha Fin</TableHead>
                <TableHead>Plan Cuenta</TableHead>
                <TableHead className="text-center">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((p: PeriodoContable, i: number) => (
                <TableRow key={p.Pec_Cod || i}>
                  <TableCell className="font-semibold text-primary">
                    {p.Pec_Cod || "-"}
                  </TableCell>
                  <TableCell>{p.Pec_Fei}</TableCell>
                  <TableCell>{p.Pec_Fef}</TableCell>
                  <TableCell>{p.Pla_Cod || "-"}</TableCell>
                  <TableCell className="text-center">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleOpenEdit(p)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleDelete(p)}
                    >
                      <Trash2 className="h-4 w-4 text-error" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
              {filtered.length === 0 && (
                <TableRow>
                  <TableCell colSpan={5} className="text-center h-24">
                    No se encontraron periodos.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Periodos */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Periodo" : "Nuevo Periodo"}
              </h3>
              <button
                onClick={() => setShowModal(false)}
                className="text-muted-foreground hover:text-dark"
              >
                <X className="h-5 w-5" />
              </button>
            </div>
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              {modalError && (
                <div className="bg-lighterror text-error p-3 rounded-md flex items-start gap-2 text-sm">
                  <AlertCircle className="h-5 w-5 shrink-0" />
                  <span>{modalError}</span>
                </div>
              )}
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Código Periodo *
                </label>
                <Input
                  required
                  type="number"
                  value={formData.Pec_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Pec_Cod: Number(e.target.value) })
                  }
                  placeholder="Ej: 2025"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Fecha Inicio *
                  </label>
                  <Input
                    required
                    type="date"
                    value={formData.Pec_Fei}
                    onChange={(e) =>
                      setFormData({ ...formData, Pec_Fei: e.target.value })
                    }
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Fecha Fin *
                  </label>
                  <Input
                    required
                    type="date"
                    value={formData.Pec_Fef}
                    onChange={(e) =>
                      setFormData({ ...formData, Pec_Fef: e.target.value })
                    }
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Código Plan Cuenta *
                </label>
                <Input
                  required
                  type="number"
                  value={formData.Pla_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Pla_Cod: Number(e.target.value) })
                  }
                  placeholder="Código del plan de cuentas"
                />
              </div>
              <div className="border-t pt-4 flex justify-end gap-3">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setShowModal(false)}
                >
                  Cancelar
                </Button>
                <Button type="submit" disabled={modalLoading}>
                  {modalLoading ? (
                    <Loader2 className="h-4 w-4 animate-spin mr-1" />
                  ) : null}
                  {isEdit ? "Guardar Cambios" : "Crear Periodo"}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
      {ConfirmDialog}
    </Card>
  );
}

function ComprobantesTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => contabilidadApi.comprobantes(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<ComprobanteForm>({
    Pec_Cod: 0,
    Tia_Cod: 0,
    Com_Fec: "",
    Com_Num: 0,
    Com_Con: "",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as ComprobanteContable[]) : [];
  const filtered = items.filter(
    (c) => !search || String(c.Com_Num).includes(search),
  );

  const handleOpenCreate = () => {
    setFormData({ Pec_Cod: 0, Tia_Cod: 0, Com_Fec: "", Com_Num: 0, Com_Con: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (item: ComprobanteContable) => {
    setFormData({
      Pec_Cod: item.Pec_Cod,
      Tia_Cod: item.Tia_Cod,
      Com_Fec: item.Com_Fec,
      Com_Num: item.Com_Num,
      Com_Con: item.Com_Con || "",
    });
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (item: ComprobanteContable) => {
    if (!(await confirm(`¿Eliminar el comprobante #${item.Com_Num}?`))) return;
    try {
      const res = await contabilidadApi.eliminarComprobante(item.Com_Cod);
      if (res.success) {
        refetch();
        toast.success("Eliminado correctamente");
      } else {
        toast.error(res.message || "Error al eliminar");
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Error de conexión");
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setModalLoading(true);
    setModalError(null);
    try {
      const payload = { ...formData };

      let res;
      if (isEdit) {
        res = await contabilidadApi.modificarComprobante(payload);
      } else {
        res = await contabilidadApi.crearComprobante(payload);
      }

      if (res.success) {
        setShowModal(false);
        refetch();
      } else {
        setModalError(res.message || "Error al procesar la solicitud");
      }
    } catch (err) {
      setModalError(err instanceof Error ? err.message : "Error de conexión");
    } finally {
      setModalLoading(false);
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Comprobantes</CardTitle>
          <CardDescription>
            Comprobantes contables registrados.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Comprobante
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar número..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        {loading ? (
          <div className="flex justify-center py-8">
            <Loader2 className="h-6 w-6 animate-spin" />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <div className="min-w-[700px]">
              <Table>
            <TableHeader>
              <TableRow>
                <TableHead>#</TableHead>
                <TableHead>Fecha</TableHead>
                <TableHead>Concepto</TableHead>
                <TableHead className="text-center">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((c: ComprobanteContable, i: number) => (
                <TableRow key={c.Com_Cod || i}>
                  <TableCell className="font-semibold text-primary">
                    {c.Com_Num || "-"}
                  </TableCell>
                  <TableCell>{c.Com_Fec}</TableCell>
                  <TableCell className="font-medium">{c.Com_Con || "-"}</TableCell>
                  <TableCell className="text-center">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleOpenEdit(c)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleDelete(c)}
                    >
                      <Trash2 className="h-4 w-4 text-error" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
              {filtered.length === 0 && (
                <TableRow>
                  <TableCell colSpan={4} className="text-center h-24">
                    No se encontraron comprobantes.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Comprobantes */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Comprobante" : "Nuevo Comprobante"}
              </h3>
              <button
                onClick={() => setShowModal(false)}
                className="text-muted-foreground hover:text-dark"
              >
                <X className="h-5 w-5" />
              </button>
            </div>
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              {modalError && (
                <div className="bg-lighterror text-error p-3 rounded-md flex items-start gap-2 text-sm">
                  <AlertCircle className="h-5 w-5 shrink-0" />
                  <span>{modalError}</span>
                </div>
              )}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Periodo *
                  </label>
                  <Input
                    required
                    type="number"
                    value={formData.Pec_Cod || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Pec_Cod: Number(e.target.value) })
                    }
                    placeholder="Código periodo"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Tipo Comprobante *
                  </label>
                  <Input
                    required
                    type="number"
                    value={formData.Tia_Cod || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Tia_Cod: Number(e.target.value) })
                    }
                    placeholder="Código tipo"
                  />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Fecha *
                  </label>
                  <Input
                    required
                    type="date"
                    value={formData.Com_Fec}
                    onChange={(e) =>
                      setFormData({ ...formData, Com_Fec: e.target.value })
                    }
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Número *
                  </label>
                  <Input
                    required
                    type="number"
                    value={formData.Com_Num || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Com_Num: Number(e.target.value) })
                    }
                    placeholder="Ej: 001"
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Concepto
                </label>
                <Input
                  value={formData.Com_Con}
                  onChange={(e) =>
                    setFormData({ ...formData, Com_Con: e.target.value })
                  }
                  placeholder="Descripción del comprobante"
                />
              </div>
              <div className="border-t pt-4 flex justify-end gap-3">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setShowModal(false)}
                >
                  Cancelar
                </Button>
                <Button type="submit" disabled={modalLoading}>
                  {modalLoading ? (
                    <Loader2 className="h-4 w-4 animate-spin mr-1" />
                  ) : null}
                  {isEdit ? "Guardar Cambios" : "Crear Comprobante"}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
      {ConfirmDialog}
    </Card>
  );
}

export default function ContabilidadPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">Contabilidad</h2>
        <p className="text-muted-foreground mt-1">
          Plan de cuentas, periodos y comprobantes contables.
        </p>
      </div>
      <Tabs defaultValue="plan-cuentas">
        <TabsList>
          <TabsTab value="plan-cuentas">Plan de Cuentas</TabsTab>
          <TabsTab value="periodos">Periodos</TabsTab>
          <TabsTab value="comprobantes">Comprobantes</TabsTab>
        </TabsList>
        <TabsPanel value="plan-cuentas">
          <PlanCuentasTab />
        </TabsPanel>
        <TabsPanel value="periodos">
          <PeriodosTab />
        </TabsPanel>
        <TabsPanel value="comprobantes">
          <ComprobantesTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
