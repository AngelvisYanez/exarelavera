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
import { toast } from "sonner";
import { bodegaApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import { useConfirm } from "@/lib/hooks/use-confirm";
import type { StockRow } from "@/lib/api-types";

interface BodegaItem {
  Bod_Cod: number;
  Bod_Nom: string;
  Bod_Dir?: string;
  Bod_Tip?: string;
  Bod_Est?: string;
  Suc_Cod?: number;
}

function BodegasTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => bodegaApi.bodegas(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<BodegaItem>>({
    Bod_Cod: 0,
    Bod_Nom: "",
    Bod_Dir: "",
    Bod_Tip: "B",
    Bod_Est: "A",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as BodegaItem[]) : [];
  const filtered = items.filter(
    (b) => !search || b.Bod_Nom?.toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Bod_Cod: 0, Bod_Nom: "", Bod_Dir: "", Bod_Tip: "B", Bod_Est: "A" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (bod: BodegaItem) => {
    setFormData(bod);
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
        res = await bodegaApi.modificarBodega(payload);
      } else {
        res = await bodegaApi.crearBodega(payload);
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

  const handleDelete = async (bod: BodegaItem) => {
    if (!(await confirm(`¿Eliminar la bodega "${bod.Bod_Nom}"?`))) return;
    try {
      const res = await bodegaApi.eliminarBodega(bod.Bod_Cod);
      if (res.success) {
        refetch();
        toast.success("Bodega eliminada");
      }
    } catch {
      toast.error("Error al eliminar la bodega");
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Bodegas</CardTitle>
          <CardDescription>
            Administración de bodegas del sistema.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Bodega
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar bodega..."
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
                    <TableHead>Nombre</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((b: BodegaItem, i: number) => (
                    <TableRow key={b.Bod_Cod || i}>
                      <TableCell className="font-semibold text-primary">
                        {b.Bod_Cod || "-"}
                      </TableCell>
                      <TableCell className="font-medium">{b.Bod_Nom}</TableCell>
                      <TableCell>{b.Bod_Est || "-"}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleOpenEdit(b)}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(b)}
                        >
                          <Trash2 className="h-4 w-4 text-error" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={4} className="text-center h-24">
                        No se encontraron bodegas.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Bodegas */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Bodega" : "Nueva Bodega"}
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
                  value={formData.Bod_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Bod_Cod: Number(e.target.value) })
                  }
                  placeholder="Código de bodega"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Nombre *
                </label>
                <Input
                  required
                  value={formData.Bod_Nom || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Bod_Nom: e.target.value })
                  }
                  placeholder="Nombre de la bodega"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Dirección
                </label>
                <Input
                  value={formData.Bod_Dir || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Bod_Dir: e.target.value })
                  }
                  placeholder="Dirección de la bodega"
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
                  {isEdit ? "Guardar Cambios" : "Crear Bodega"}
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

function StockTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => bodegaApi.stock(), {
    auto: true,
  });
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as (StockRow & Record<string, unknown>)[]) : [];
  const filtered = items.filter(
    (s) => !search || (s.Pro_Des as string)?.toLowerCase().includes(search.toLowerCase()),
  );

  const handleDelete = async (stk: StockRow & Record<string, unknown>) => {
    if (!(await confirm(`¿Eliminar el stock de "${stk.Pro_Des as string}" en "${stk.Bod_Des as string}"?`))) return;
    try {
      const res = await bodegaApi.eliminarStock(stk.Pro_Cod);
      if (res.success) {
        refetch();
        toast.success("Stock eliminado");
      }
    } catch {
      toast.error("Error al eliminar el stock");
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Stock</CardTitle>
          <CardDescription>
            Inventario actual por bodega. El stock se gestiona vía movimientos.
          </CardDescription>
        </div>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar producto..."
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
                    <TableHead>Producto</TableHead>
                    <TableHead>Bodega</TableHead>
                    <TableHead>Cantidad</TableHead>
                    <TableHead>Sucursal</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((s: StockRow & Record<string, unknown>, i: number) => (
                    <TableRow key={`${s.Pro_Cod}-${s.Suc_Cod}-${i}`}>
                      <TableCell className="font-medium">{(s.Pro_Des as string) || "-"}</TableCell>
                      <TableCell>{(s.Bod_Des as string) || "-"}</TableCell>
                      <TableCell className="font-mono">{String(s.Stk_Can ?? 0)}</TableCell>
                      <TableCell>{String(s.Suc_Cod || "-")}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(s)}
                        >
                          <Trash2 className="h-4 w-4 text-error" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center h-24">
                        No se encontraron registros de stock.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>
      {ConfirmDialog}
    </Card>
  );
}

interface MovimientoItem {
  Mov_Cod?: number;
  Mov_Fec?: string;
  Bod_Ori?: number;
  Bod_Des?: number;
  Mov_Est?: string;
  Mov_Obs?: string;
  [key: string]: unknown;
}

function MovimientosTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => bodegaApi.movimientos(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<MovimientoItem>>({
    Mov_Cod: 0,
    Mov_Fec: "",
    Bod_Ori: 0,
    Bod_Des: 0,
    Mov_Est: "A",
    Mov_Obs: "",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as MovimientoItem[]) : [];
  const filtered = items.filter(
    (m) => !search || (m.Pro_Des as string)?.toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Mov_Cod: 0, Mov_Fec: "", Bod_Ori: 0, Bod_Des: 0, Mov_Est: "A", Mov_Obs: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (mov: MovimientoItem) => {
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
        res = await bodegaApi.modificarMovimiento(payload);
      } else {
        res = await bodegaApi.crearMovimiento(payload);
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

  const handleDelete = async (mov: MovimientoItem) => {
    if (!(await confirm(`¿Eliminar este movimiento?`))) return;
    try {
      const res = await bodegaApi.eliminarMovimiento(mov.Mov_Cod!);
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
            Registro de entradas, salidas y transferencias de bodega.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Movimiento
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por producto..."
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
                    <TableHead>Fecha</TableHead>
                    <TableHead>Producto</TableHead>
                    <TableHead>Bodega</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead>Cantidad</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((m: MovimientoItem, i: number) => (
                    <TableRow key={m.Mov_Cod || i}>
                      <TableCell className="font-medium">{String(m.Mov_Fec || "-")}</TableCell>
                      <TableCell>{(m.Pro_Des as string) || "-"}</TableCell>
                      <TableCell>{(m.Bod_Nom as string) || "-"}</TableCell>
                      <TableCell>{(m.Mov_Est as string) || "-"}</TableCell>
                      <TableCell className="font-mono">{String(m.Mov_Obs ?? "-")}</TableCell>
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
                  {filtered.length === 0 && (
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
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Bodega Origen *
                  </label>
                  <Input
                    required
                    type="number"
                    value={formData.Bod_Ori || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Bod_Ori: Number(e.target.value) })
                    }
                    placeholder="Bod_Ori"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Bodega Destino *
                  </label>
                  <Input
                    required
                    type="number"
                    value={formData.Bod_Des || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Bod_Des: Number(e.target.value) })
                    }
                    placeholder="Bod_Des"
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Fecha *
                </label>
                <Input
                  required
                  type="date"
                  value={formData.Mov_Fec || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Mov_Fec: e.target.value })
                  }
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Estado
                  </label>
                  <Input
                    value={formData.Mov_Est || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Mov_Est: e.target.value })
                    }
                    placeholder="A / I"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Observaciones
                  </label>
                  <Input
                    value={formData.Mov_Obs || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Mov_Obs: e.target.value })
                    }
                    placeholder="Observaciones del movimiento"
                  />
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

export default function BodegaPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">Bodega</h2>
        <p className="text-muted-foreground mt-1">
          Gestión de bodegas, stock y movimientos.
        </p>
      </div>
      <Tabs defaultValue="bodegas">
        <TabsList>
          <TabsTab value="bodegas">Bodegas</TabsTab>
          <TabsTab value="stock">Stock</TabsTab>
          <TabsTab value="movimientos">Movimientos</TabsTab>
        </TabsList>
        <TabsPanel value="bodegas">
          <BodegasTab />
        </TabsPanel>
        <TabsPanel value="stock">
          <StockTab />
        </TabsPanel>
        <TabsPanel value="movimientos">
          <MovimientosTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
