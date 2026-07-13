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
import { bananeroApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import { toast } from "sonner";
import { useConfirm } from "@/lib/hooks/use-confirm";

function ProductoresTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => bananeroApi.productores(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({});
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (p) =>
      !search ||
      String(p.Prd_Nom || "").toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Prd_Nom: "", Prv_Cod: 0, Prd_Est: "A" });
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
        res = await bananeroApi.modificarProductor(payload);
      } else {
        res = await bananeroApi.crearProductor(payload);
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
    if (!(await confirm("�Est� seguro de eliminar este productor?"))) return;
    try {
      const res = await bananeroApi.eliminarProductor(id);
      if (res.success) {
        refetch();
        toast.success("Eliminado correctamente");
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
          <CardDescription>Productores de banano registrados.</CardDescription>
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
                    <TableHead>Nombre</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((p, i) => (
                    <TableRow key={(p.Prd_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(p.Prd_Cod ?? "-")}
                      </TableCell>
                      <TableCell className="font-medium">{String(p.Prd_Nom ?? "-")}</TableCell>
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
                          onClick={() => handleDelete(p.Prd_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={3} className="text-center h-24">
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
                  Nombre *
                </label>
                <Input
                  required
                  value={String(formData.Prd_Nom || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Prd_Nom: e.target.value })
                  }
                  placeholder="Nombre del productor"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Proveedor
                </label>
                <Input
                  type="number"
                  value={String(formData.Prv_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Prv_Cod: Number(e.target.value) })
                  }
                  placeholder="C�digo de proveedor"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Estado
                </label>
                <select
                  className="w-full border rounded-md px-3 py-2 text-sm"
                  value={String(formData.Prd_Est || "A")}
                  onChange={(e) =>
                    setFormData({ ...formData, Prd_Est: e.target.value })
                  }
                >
                  <option value="A">Activo</option>
                  <option value="I">Inactivo</option>
                </select>
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

function LiquidacionesTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => bananeroApi.liquidaciones(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({});
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];

  const filtered = items.filter(
    (l) =>
      !search ||
      String(l.Lib_Fec || "").toLowerCase().includes(search.toLowerCase()) ||
      String(l.productor_nombre || "").toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Prd_Cod: "", Bam_Cod: "", Lib_Fec: "", Lib_Est: "A" });
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
        res = await bananeroApi.modificarLiquidacion(payload);
      } else {
        res = await bananeroApi.crearLiquidacion(payload);
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
    if (!(await confirm("�Est� seguro de eliminar esta liquidaci�n?"))) return;
    try {
      const res = await bananeroApi.eliminarLiquidacion(id);
      if (res.success) {
        refetch();
        toast.success("Eliminado correctamente");
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
          <CardDescription>Liquidaciones de productores de banano.</CardDescription>
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
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((l, i) => (
                    <TableRow key={(l.Lib_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(l.Lib_Cod ?? "-")}
                      </TableCell>
                      <TableCell className="font-medium">{String(l.productor_nombre ?? "-")}</TableCell>
                      <TableCell>{String(l.Lib_Fec ?? "-").slice(0, 10)}</TableCell>
                      <TableCell>{String(l.Lib_Est ?? "-")}</TableCell>
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
                          onClick={() => handleDelete(l.Lib_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center h-24">
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
                  value={String(formData.Prd_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Prd_Cod: Number(e.target.value) })
                  }
                  placeholder="C�digo del productor"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  C�digo Marca
                </label>
                <Input
                  type="number"
                  value={String(formData.Bam_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Bam_Cod: Number(e.target.value) })
                  }
                  placeholder="C�digo de marca"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Fecha *
                </label>
                <Input
                  type="date"
                  required
                  value={String(formData.Lib_Fec || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Lib_Fec: e.target.value })
                  }
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Estado
                </label>
                <select
                  className="w-full border rounded-md px-3 py-2 text-sm"
                  value={String(formData.Lib_Est || "A")}
                  onChange={(e) =>
                    setFormData({ ...formData, Lib_Est: e.target.value })
                  }
                >
                  <option value="A">Activo</option>
                  <option value="I">Inactivo</option>
                </select>
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

function ExportacionesTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => bananeroApi.exportaciones(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({});
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];

  const filtered = items.filter(
    (e) =>
      !search ||
      String(e.Exc_Fec || "").toLowerCase().includes(search.toLowerCase()) ||
      String(e.Exc_Con || "").toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Exc_Fec: "", Exc_Con: "", Exc_Est: "A" });
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
        res = await bananeroApi.modificarExportacion(payload);
      } else {
        res = await bananeroApi.crearExportacion(payload);
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
    if (!(await confirm("�Est� seguro de eliminar esta exportaci�n?"))) return;
    try {
      const res = await bananeroApi.eliminarExportacion(id);
      if (res.success) {
        refetch();
        toast.success("Eliminado correctamente");
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
          <CardTitle>Exportaciones</CardTitle>
          <CardDescription>Env�os de banano al exterior.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Exportaci�n
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por fecha o container..."
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
                    <TableHead>Fecha</TableHead>
                    <TableHead>Container</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((e, i) => (
                    <TableRow key={(e.Exc_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(e.Exc_Cod ?? "-")}
                      </TableCell>
                      <TableCell>{String(e.Exc_Fec ?? "-").slice(0, 10)}</TableCell>
                      <TableCell className="font-medium">{String(e.Exc_Con ?? "-")}</TableCell>
                      <TableCell>{String(e.Exc_Est ?? "-")}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleOpenEdit(e)}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(e.Exc_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center h-24">
                        No se encontraron exportaciones.
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
                {isEdit ? "Editar Exportaci�n" : "Nueva Exportaci�n"}
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
                  Fecha *
                </label>
                <Input
                  type="date"
                  required
                  value={String(formData.Exc_Fec || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Exc_Fec: e.target.value })
                  }
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Container
                </label>
                <Input
                  value={String(formData.Exc_Con || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Exc_Con: e.target.value })
                  }
                  placeholder="N�mero de container"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Estado
                </label>
                <select
                  className="w-full border rounded-md px-3 py-2 text-sm"
                  value={String(formData.Exc_Est || "A")}
                  onChange={(e) =>
                    setFormData({ ...formData, Exc_Est: e.target.value })
                  }
                >
                  <option value="A">Activo</option>
                  <option value="I">Inactivo</option>
                </select>
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
                  {isEdit ? "Guardar Cambios" : "Crear Exportaci�n"}
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

function NavierasTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => bananeroApi.navieras(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({});
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (n) =>
      !search ||
      String(n.Nav_Nom || "").toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Nav_Cod: "", Nav_Nom: "", Nav_Est: "A", Nav_Tip: "" });
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
        res = await bananeroApi.modificarNaviera(payload);
      } else {
        res = await bananeroApi.crearNaviera(payload);
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
    if (!(await confirm("�Est� seguro de eliminar esta naviera?"))) return;
    try {
      const res = await bananeroApi.eliminarNaviera(id);
      if (res.success) {
        refetch();
        toast.success("Eliminado correctamente");
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
          <CardTitle>Navieras</CardTitle>
          <CardDescription>Empresas navieras para exportaci�n.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Naviera
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar naviera..."
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
                    <TableHead>C�digo</TableHead>
                    <TableHead>Nombre</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((n, i) => (
                    <TableRow key={(n.Nav_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(n.Nav_Cod ?? "-")}
                      </TableCell>
                      <TableCell className="font-medium">{String(n.Nav_Nom ?? "-")}</TableCell>
                      <TableCell>{String(n.Nav_Est ?? "-")}</TableCell>
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
                          onClick={() => handleDelete(n.Nav_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={4} className="text-center h-24">
                        No se encontraron navieras.
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
                {isEdit ? "Editar Naviera" : "Nueva Naviera"}
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
                  C�digo *
                </label>
                <Input
                  type="number"
                  required
                  value={String(formData.Nav_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Nav_Cod: e.target.value })
                  }
                  placeholder="C�digo de naviera"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Nombre *
                </label>
                <Input
                  required
                  value={String(formData.Nav_Nom || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Nav_Nom: e.target.value })
                  }
                  placeholder="Nombre de la naviera"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Estado
                </label>
                <select
                  className="w-full border rounded-md px-3 py-2 text-sm"
                  value={String(formData.Nav_Est || "A")}
                  onChange={(e) =>
                    setFormData({ ...formData, Nav_Est: e.target.value })
                  }
                >
                  <option value="A">Activo</option>
                  <option value="I">Inactivo</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Tipo
                </label>
                <Input
                  value={String(formData.Nav_Tip || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Nav_Tip: e.target.value })
                  }
                  placeholder="Tipo de naviera"
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
                  {isEdit ? "Guardar Cambios" : "Crear Naviera"}
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

export default function BananeroPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">Bananero</h2>
        <p className="text-muted-foreground mt-1">
          Gesti�n de productores, liquidaciones, exportaciones y navieras.
        </p>
      </div>
      <Tabs defaultValue="productores">
        <TabsList>
          <TabsTab value="productores">Productores</TabsTab>
          <TabsTab value="liquidaciones">Liquidaciones</TabsTab>
          <TabsTab value="exportaciones">Exportaciones</TabsTab>
          <TabsTab value="navieras">Navieras</TabsTab>
        </TabsList>
        <TabsPanel value="productores">
          <ProductoresTab />
        </TabsPanel>
        <TabsPanel value="liquidaciones">
          <LiquidacionesTab />
        </TabsPanel>
        <TabsPanel value="exportaciones">
          <ExportacionesTab />
        </TabsPanel>
        <TabsPanel value="navieras">
          <NavierasTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
