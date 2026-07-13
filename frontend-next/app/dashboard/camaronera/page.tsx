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
import { camaroneraApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import { useConfirm } from "@/lib/hooks/use-confirm";

function ProductoresTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => camaroneraApi.productores(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({});
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (p) =>
      !search ||
      String(p.Prod_Cod || "").toLowerCase().includes(search.toLowerCase()) ||
      String(p.Prv_Cod || "").toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Prv_Cod: "", Tip_Prod: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (row: Record<string, unknown>) => {
    setFormData(row);
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

      const payload = { ...formData, Bdd: bdd };

      let res;
      if (isEdit) {
        res = await camaroneraApi.modificarProductor(payload);
      } else {
        res = await camaroneraApi.crearProductor(payload);
      }

      if (res.success) {
        setShowModal(false);
        refetch();
      } else {
        setModalError("Error al procesar la solicitud");
      }
    } catch (err) {
      setModalError(err instanceof Error ? err.message : "Error de conexi�n");
    } finally {
      setModalLoading(false);
    }
  };

  const handleDelete = async (id: number | string) => {
    if (!(await confirm("\u00bfEst\u00e1 seguro de eliminar este productor?"))) return;
    try {
      const res = await camaroneraApi.eliminarProductor(id);
      if (res.success) {
        toast.success("Eliminado correctamente");
        refetch();
      }
    } catch {
      // silent
    }
  };

  return (
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Productores</CardTitle>
          <CardDescription>Productores de camar�n registrados.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Productor
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar productor..."
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
            <div className="min-w-[800px]">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>C�digo</TableHead>
                    <TableHead>Proveedor</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((p, i) => (
                    <TableRow key={(p.Prod_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(p.Prod_Cod ?? "-")}
                      </TableCell>
                      <TableCell className="font-medium">{String(p.Prv_Cod ?? "-")}</TableCell>
                      <TableCell>{String(p.Tip_Prod ?? "-")}</TableCell>
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
                          onClick={() => handleDelete(p.Prod_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={4} className="text-center h-24">
                        No se encontraron productores.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>

      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Productor" : "Nuevo Productor"}
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
                  C�digo Proveedor *
                </label>
                <Input
                  type="number"
                  required
                  value={String(formData.Prv_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Prv_Cod: e.target.value })
                  }
                  placeholder="C�digo del proveedor"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Tipo Productor
                </label>
                <Input
                  value={String(formData.Tip_Prod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Tip_Prod: e.target.value })
                  }
                  placeholder="Tipo de productor"
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
                  {isEdit ? "Guardar Cambios" : "Crear Productor"}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </Card>
    {ConfirmDialog}
    </>
  );
}

function NegociacionesTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => camaroneraApi.negociaciones(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({});
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (n) =>
      !search ||
      String(n.Fec_Neg || "").toLowerCase().includes(search.toLowerCase()) ||
      String(n.productor_nombre || "").toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Prod_Cod: "", Fec_Neg: "", Neg_Des: "", Neg_Tot: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (row: Record<string, unknown>) => {
    setFormData(row);
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

      const payload = { ...formData, Bdd: bdd };

      let res;
      if (isEdit) {
        res = await camaroneraApi.modificarNegociacion(payload);
      } else {
        res = await camaroneraApi.crearNegociacion(payload);
      }

      if (res.success) {
        setShowModal(false);
        refetch();
      } else {
        setModalError("Error al procesar la solicitud");
      }
    } catch (err) {
      setModalError(err instanceof Error ? err.message : "Error de conexi�n");
    } finally {
      setModalLoading(false);
    }
  };

  const handleDelete = async (id: number | string) => {
    if (!(await confirm("\u00bfEst\u00e1 seguro de eliminar esta negociaci\u00f3n?"))) return;
    try {
      const res = await camaroneraApi.eliminarNegociacion(id);
      if (res.success) {
        toast.success("Eliminado correctamente");
        refetch();
      }
    } catch {
      // silent
    }
  };

  return (
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Negociaciones</CardTitle>
          <CardDescription>Negociaciones con productores de camar�n.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Negociaci�n
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por fecha o productor..."
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
            <div className="min-w-[900px]">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>C�digo</TableHead>
                    <TableHead>Productor</TableHead>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Descripci�n</TableHead>
                    <TableHead>Valor</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((n, i) => (
                    <TableRow key={(n.Cod_Neg as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(n.Cod_Neg ?? "-")}
                      </TableCell>
                      <TableCell className="font-medium">{String(n.productor_nombre ?? "-")}</TableCell>
                      <TableCell>{n.Fec_Neg ? String(String(n.Fec_Neg).slice(0, 10)) : "-"}</TableCell>
                      <TableCell>{String(n.Neg_Des ?? "-")}</TableCell>
                      <TableCell>{String(n.Neg_Tot ?? "-")}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleOpenEdit(n)}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(n.Cod_Neg as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center h-24">
                        No se encontraron negociaciones.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>

      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Negociaci�n" : "Nueva Negociaci�n"}
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
                  C�digo Productor *
                </label>
                <Input
                  type="number"
                  required
                  value={String(formData.Prod_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Prod_Cod: e.target.value })
                  }
                  placeholder="C�digo del productor"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Fecha *
                </label>
                <Input
                  type="date"
                  required
                  value={String(formData.Fec_Neg || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Fec_Neg: e.target.value })
                  }
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Descripci�n
                </label>
                <Input
                  value={String(formData.Neg_Des || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Neg_Des: e.target.value })
                  }
                  placeholder="Descripci�n de la negociaci�n"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Valor Total
                </label>
                <Input
                  type="number"
                  step="0.01"
                  value={String(formData.Neg_Tot || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Neg_Tot: e.target.value })
                  }
                  placeholder="Valor total"
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
                  {isEdit ? "Guardar Cambios" : "Crear Negociaci�n"}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </Card>
    {ConfirmDialog}
    </>
  );
}

function LiquidacionesTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => camaroneraApi.liquidaciones(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({});
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (l) =>
      !search ||
      String(l.Liq_Fecha || "").toLowerCase().includes(search.toLowerCase()) ||
      String(l.productor_nombre || "").toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Prod_Cod: "", Liq_Fecha: "", Cod_Neg: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (row: Record<string, unknown>) => {
    setFormData(row);
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

      const payload = { ...formData, Bdd: bdd };

      let res;
      if (isEdit) {
        res = await camaroneraApi.modificarLiquidacion(payload);
      } else {
        res = await camaroneraApi.crearLiquidacion(payload);
      }

      if (res.success) {
        setShowModal(false);
        refetch();
      } else {
        setModalError("Error al procesar la solicitud");
      }
    } catch (err) {
      setModalError(err instanceof Error ? err.message : "Error de conexi�n");
    } finally {
      setModalLoading(false);
    }
  };

  const handleDelete = async (id: number | string) => {
    if (!(await confirm("\u00bfEst\u00e1 seguro de eliminar esta liquidaci\u00f3n?"))) return;
    try {
      const res = await camaroneraApi.eliminarLiquidacion(id);
      if (res.success) {
        toast.success("Eliminado correctamente");
        refetch();
      }
    } catch {
      // silent
    }
  };

  return (
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Liquidaciones</CardTitle>
          <CardDescription>Liquidaciones de productores de camar�n.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Liquidaci�n
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por fecha o productor..."
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
            <div className="min-w-[900px]">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>C�digo</TableHead>
                    <TableHead>Productor</TableHead>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead>Negociaci�n</TableHead>
                    <TableHead>Peso Remitido</TableHead>
                    <TableHead>Peso Planta</TableHead>
                    <TableHead>Peso Neto</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((l, i) => (
                    <TableRow key={(l.Liq_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(l.Liq_Cod ?? "-")}
                      </TableCell>
                      <TableCell className="font-medium">{String(l.productor_nombre ?? "-")}</TableCell>
                      <TableCell>{l.Liq_Fecha ? String(String(l.Liq_Fecha).slice(0, 10)) : "-"}</TableCell>
                      <TableCell>{String(l.Est_Liq ?? "-")}</TableCell>
                      <TableCell>{String(l.Cod_Neg ?? "-")}</TableCell>
                      <TableCell>{String(l.Peso_Rem ?? "-")}</TableCell>
                      <TableCell>{String(l.Peso_Planta ?? "-")}</TableCell>
                      <TableCell>{String(l.Peso_Net ?? "-")}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleOpenEdit(l)}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(l.Liq_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={9} className="text-center h-24">
                        No se encontraron liquidaciones.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>

      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Liquidaci�n" : "Nueva Liquidaci�n"}
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
                  C�digo Productor *
                </label>
                <Input
                  type="number"
                  required
                  value={String(formData.Prod_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Prod_Cod: e.target.value })
                  }
                  placeholder="C�digo del productor"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Fecha *
                </label>
                <Input
                  type="date"
                  required
                  value={String(formData.Liq_Fecha || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Liq_Fecha: e.target.value })
                  }
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  C�digo Negociaci�n
                </label>
                <Input
                  type="number"
                  value={String(formData.Cod_Neg || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Cod_Neg: e.target.value })
                  }
                  placeholder="C�digo de la negociaci�n"
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
                  {isEdit ? "Guardar Cambios" : "Crear Liquidaci�n"}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </Card>
    {ConfirmDialog}
    </>
  );
}

export default function CamaroneraPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">Camaronera</h2>
        <p className="text-muted-foreground mt-1">
          Gesti�n de productores, negociaciones y liquidaciones.
        </p>
      </div>
      <Tabs defaultValue="productores">
        <TabsList>
          <TabsTab value="productores">Productores</TabsTab>
          <TabsTab value="negociaciones">Negociaciones</TabsTab>
          <TabsTab value="liquidaciones">Liquidaciones</TabsTab>
        </TabsList>
        <TabsPanel value="productores">
          <ProductoresTab />
        </TabsPanel>
        <TabsPanel value="negociaciones">
          <NegociacionesTab />
        </TabsPanel>
        <TabsPanel value="liquidaciones">
          <LiquidacionesTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
