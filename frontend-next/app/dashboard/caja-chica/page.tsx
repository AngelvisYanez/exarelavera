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
import { cajaChicaApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import { toast } from "sonner";
import { useConfirm } from "@/lib/hooks/use-confirm";

interface CajaItem {
  Cch_Cod: number;
  Usu_Cod?: number;
  Emp_Cod?: number;
  Cch_Val?: number;
  Cch_Fec?: string;
  Cch_Est?: string;
  Cch_Obs?: string;
}

interface MovimientoCajaItem {
  Cch_Cod: number;
  Usu_Cod?: number;
  Emp_Cod?: number;
  Cch_Val?: number;
  Cch_Fec?: string;
  Cch_Est?: string;
  Cch_Obs?: string;
}

interface ReposicionItem {
  Rep_Cod?: number;
  Cch_Cod?: number;
  Usu_Cod?: number;
  Rep_Num?: number;
  Rep_Fec?: string;
  Rep_Obs?: string;
  Rep_Est?: string;
  Rep_Tip?: string;
}

function CajasTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => cajaChicaApi.cajas(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<CajaItem>>({
    Cch_Cod: 0,
    Cch_Val: 0,
    Cch_Fec: "",
    Cch_Obs: "",
    Cch_Est: "A",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as unknown as CajaItem[]) : [];
  const filtered = items.filter(
    (c) => !search || c.Cch_Obs?.toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Cch_Cod: 0, Cch_Val: 0, Cch_Fec: "", Cch_Obs: "", Cch_Est: "A" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (caja: CajaItem) => {
    setFormData(caja);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setModalLoading(true);
    setModalError(null);
    try {
      const userInfoStr = localStorage.getItem("user_info");
      let bdd = "";
      if (userInfoStr) {
        const userInfo = JSON.parse(userInfoStr);
        bdd = userInfo.Bdd || "";
      }

      const payload = {
        ...formData,
        Bdd: bdd,
      };

      let res;
      if (isEdit) {
        res = await cajaChicaApi.modificarCaja(payload);
      } else {
        res = await cajaChicaApi.crearCaja(payload);
      }

      if (res.success) {
        setShowModal(false);
        refetch();
      } else {
        setModalError("Error al procesar la solicitud");
      }
    } catch (err) {
      setModalError(err instanceof Error ? err.message : "Error de conexión");
    } finally {
      setModalLoading(false);
    }
  };

  const handleDelete = async (caja: CajaItem) => {
    if (!(await confirm(`¿Eliminar la caja "${caja.Cch_Obs}"?`))) return;
    try {
      const res = await cajaChicaApi.eliminarCaja(caja.Cch_Cod);
      if (res.success) {
        refetch();
        toast.success("Caja eliminada");
      }
    } catch {
      toast.error("Error al eliminar la caja");
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Cajas Chicas</CardTitle>
          <CardDescription>
            Administración de cajas chicas de la empresa.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Caja
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar caja..."
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
                    <TableHead>Valor</TableHead>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Observaciones</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((c: CajaItem, i: number) => (
                    <TableRow key={c.Cch_Cod || i}>
                      <TableCell className="font-semibold text-primary">
                        {c.Cch_Cod || "-"}
                      </TableCell>
                      <TableCell className="font-mono">{c.Cch_Val ?? 0}</TableCell>
                      <TableCell>{c.Cch_Fec || "-"}</TableCell>
                      <TableCell>{c.Cch_Obs || "-"}</TableCell>
                      <TableCell>{c.Cch_Est || "-"}</TableCell>
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
                      <TableCell colSpan={6} className="text-center h-24">
                        No se encontraron cajas.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Cajas */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Caja" : "Nueva Caja"}
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
                  value={formData.Cch_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Cch_Cod: Number(e.target.value) })
                  }
                  placeholder="Código de la caja"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Valor *
                  </label>
                  <Input
                    required
                    type="number"
                    value={formData.Cch_Val || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Cch_Val: Number(e.target.value) })
                    }
                    placeholder="Valor de la caja"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Fecha
                  </label>
                  <Input
                    type="date"
                    value={formData.Cch_Fec || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Cch_Fec: e.target.value })
                    }
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Observaciones
                </label>
                <Input
                  value={formData.Cch_Obs || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Cch_Obs: e.target.value })
                  }
                  placeholder="Observaciones de la caja"
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
                  {isEdit ? "Guardar Cambios" : "Crear Caja"}
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

function MovimientosTab() {
  const { data, loading, refetch } = useQuery(() => cajaChicaApi.movimientos(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<MovimientoCajaItem>>({
    Cch_Cod: 0,
    Cch_Fec: "",
    Cch_Val: 0,
    Cch_Obs: "",
    Cch_Est: "A",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items: MovimientoCajaItem[] = Array.isArray(data?.data) ? (data.data as unknown as MovimientoCajaItem[]) : [];

  const handleOpenCreate = () => {
    setFormData({ Cch_Cod: 0, Cch_Fec: "", Cch_Val: 0, Cch_Obs: "", Cch_Est: "A" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (mov: MovimientoCajaItem) => {
    setFormData(mov);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setModalLoading(true);
    setModalError(null);
    try {
      const userInfoStr = localStorage.getItem("user_info");
      let bdd = "";
      if (userInfoStr) {
        const userInfo = JSON.parse(userInfoStr);
        bdd = userInfo.Bdd || "";
      }

      const payload = {
        ...formData,
        Bdd: bdd,
      };

      let res;
      if (isEdit) {
        res = await cajaChicaApi.modificarMovimiento(payload);
      } else {
        res = await cajaChicaApi.crearMovimiento(payload);
      }

      if (res.success) {
        setShowModal(false);
        refetch();
      } else {
        setModalError("Error al procesar la solicitud");
      }
    } catch (err) {
      setModalError(err instanceof Error ? err.message : "Error de conexión");
    } finally {
      setModalLoading(false);
    }
  };

  const handleDelete = async (mov: MovimientoCajaItem) => {
    if (!(await confirm(`¿Eliminar este movimiento de caja?`))) return;
    try {
      const res = await cajaChicaApi.eliminarMovimiento(mov.Cch_Cod!);
      if (res.success) {
        refetch();
        toast.success("Movimiento eliminado");
      }
    } catch {
      toast.error("Error al eliminar el movimiento");
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Movimientos</CardTitle>
          <CardDescription>
            Ingresos y egresos de las cajas chicas.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Movimiento
        </Button>
      </CardHeader>
      <CardContent>
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
                    <TableHead>Valor</TableHead>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Observaciones</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((m: MovimientoCajaItem, i: number) => (
                    <TableRow key={m.Cch_Cod || i}>
                      <TableCell className="font-semibold text-primary">
                        {m.Cch_Cod || "-"}
                      </TableCell>
                      <TableCell className="font-mono">{m.Cch_Val ?? 0}</TableCell>
                      <TableCell>{m.Cch_Fec || "-"}</TableCell>
                      <TableCell>{m.Cch_Obs || "-"}</TableCell>
                      <TableCell>{m.Cch_Est || "-"}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleOpenEdit(m)}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(m)}
                        >
                          <Trash2 className="h-4 w-4 text-error" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {items.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center h-24">
                        No se encontraron movimientos.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Movimientos */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Movimiento" : "Nuevo Movimiento"}
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
                  Código Caja *
                </label>
                <Input
                  required
                  type="number"
                  value={formData.Cch_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Cch_Cod: Number(e.target.value) })
                  }
                  placeholder="Código de la caja"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Valor *
                  </label>
                  <Input
                    required
                    type="number"
                    value={formData.Cch_Val || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Cch_Val: Number(e.target.value) })
                    }
                    placeholder="Valor de la caja"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Fecha
                  </label>
                  <Input
                    type="date"
                    value={formData.Cch_Fec || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Cch_Fec: e.target.value })
                    }
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Observaciones
                </label>
                <Input
                  value={formData.Cch_Obs || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Cch_Obs: e.target.value })
                  }
                  placeholder="Observaciones del movimiento"
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
                  {isEdit ? "Guardar Cambios" : "Crear Movimiento"}
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

function ReposicionesTab() {
  const { data, loading, refetch } = useQuery(() => cajaChicaApi.reposiciones(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<ReposicionItem>>({
    Cch_Cod: 0,
    Rep_Fec: "",
    Rep_Num: 0,
    Rep_Obs: "",
    Rep_Tip: "E",
    Rep_Est: "A",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as ReposicionItem[]) : [];

  const handleOpenCreate = () => {
    setFormData({ Cch_Cod: 0, Rep_Fec: "", Rep_Num: 0, Rep_Obs: "", Rep_Tip: "E", Rep_Est: "A" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (rep: ReposicionItem) => {
    setFormData(rep);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setModalLoading(true);
    setModalError(null);
    try {
      const userInfoStr = localStorage.getItem("user_info");
      let bdd = "";
      if (userInfoStr) {
        const userInfo = JSON.parse(userInfoStr);
        bdd = userInfo.Bdd || "";
      }

      const payload = {
        ...formData,
        Bdd: bdd,
      };

      let res;
      if (isEdit) {
        res = await cajaChicaApi.modificarReposicion(payload);
      } else {
        res = await cajaChicaApi.crearReposicion(payload);
      }

      if (res.success) {
        setShowModal(false);
        refetch();
      } else {
        setModalError("Error al procesar la solicitud");
      }
    } catch (err) {
      setModalError(err instanceof Error ? err.message : "Error de conexión");
    } finally {
      setModalLoading(false);
    }
  };

  const handleDelete = async (rep: ReposicionItem) => {
    if (!(await confirm(`¿Eliminar esta reposición?`))) return;
    try {
      const res = await cajaChicaApi.eliminarReposicion(rep.Rep_Cod!);
      if (res.success) {
        refetch();
        toast.success("Reposición eliminada");
      }
    } catch {
      toast.error("Error al eliminar la reposición");
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Reposiciones</CardTitle>
          <CardDescription>
            Reposiciones de fondos a las cajas chicas.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Reposición
        </Button>
      </CardHeader>
      <CardContent>
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
                    <TableHead>Número</TableHead>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead>Observaciones</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((r: ReposicionItem, i: number) => (
                    <TableRow key={r.Rep_Cod || i}>
                      <TableCell className="font-semibold text-primary">
                        {r.Rep_Cod || "-"}
                      </TableCell>
                      <TableCell className="font-mono">{r.Rep_Num ?? 0}</TableCell>
                      <TableCell>{r.Rep_Fec || "-"}</TableCell>
                      <TableCell>{r.Rep_Tip || "-"}</TableCell>
                      <TableCell>{r.Rep_Obs || "-"}</TableCell>
                      <TableCell>{r.Rep_Est || "-"}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleOpenEdit(r)}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(r)}
                        >
                          <Trash2 className="h-4 w-4 text-error" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {items.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center h-24">
                        No se encontraron reposiciones.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Reposiciones */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Reposición" : "Nueva Reposición"}
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
                  Código Caja *
                </label>
                <Input
                  required
                  type="number"
                  value={formData.Cch_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Cch_Cod: Number(e.target.value) })
                  }
                  placeholder="Código de la caja"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Número *
                  </label>
                  <Input
                    required
                    type="number"
                    value={formData.Rep_Num || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Rep_Num: Number(e.target.value) })
                    }
                    placeholder="Número de reposición"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Fecha *
                  </label>
                  <Input
                    required
                    type="date"
                    value={formData.Rep_Fec || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Rep_Fec: e.target.value })
                    }
                  />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Tipo *
                  </label>
                  <Input
                    required
                    value={formData.Rep_Tip || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Rep_Tip: e.target.value })
                    }
                    placeholder="E: Egreso"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Estado
                  </label>
                  <Input
                    value={formData.Rep_Est || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Rep_Est: e.target.value })
                    }
                    placeholder="A: Activo, I: Inactivo"
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Observaciones
                </label>
                <Input
                  value={formData.Rep_Obs || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Rep_Obs: e.target.value })
                  }
                  placeholder="Observaciones de la reposición"
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
                  {isEdit ? "Guardar Cambios" : "Crear Reposición"}
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

export default function CajaChicaPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">Caja Chica</h2>
        <p className="text-muted-foreground mt-1">
          Gestión de cajas chicas, movimientos y reposiciones.
        </p>
      </div>
      <Tabs defaultValue="cajas">
        <TabsList>
          <TabsTab value="cajas">Cajas</TabsTab>
          <TabsTab value="movimientos">Movimientos</TabsTab>
          <TabsTab value="reposiciones">Reposiciones</TabsTab>
        </TabsList>
        <TabsPanel value="cajas">
          <CajasTab />
        </TabsPanel>
        <TabsPanel value="movimientos">
          <MovimientosTab />
        </TabsPanel>
        <TabsPanel value="reposiciones">
          <ReposicionesTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
