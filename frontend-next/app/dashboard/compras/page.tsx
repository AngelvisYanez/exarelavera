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
import { comprasApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import { toast } from "sonner";
import { useConfirm } from "@/lib/hooks/use-confirm";

function RequisicionesTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => comprasApi.requisiciones(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({});
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (r) =>
      !search ||
      String(r.Req_Fec || "").toLowerCase().includes(search.toLowerCase()) ||
      String(r.Prs_Nom || "").toLowerCase().includes(search.toLowerCase()) ||
      String(r.Prs_Ape || "").toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Req_Fec: "", Per_Cod: "", Req_Des: "", Req_Est: "P" });
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
        res = await comprasApi.modificarRequisicion(payload);
      } else {
        res = await comprasApi.crearRequisicion(payload);
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
    const ok = await confirm("¿Está seguro de eliminar esta requisición?");
    if (!ok) return;
    try {
      const res = await comprasApi.eliminarRequisicion(id);
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
          <CardTitle>Requisiciones</CardTitle>
          <CardDescription>Solicitudes de compra almac�n o servicio.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Requisici�n
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por fecha o empleado..."
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
                    <TableHead>Fecha</TableHead>
                    <TableHead>Empleado</TableHead>
                    <TableHead>Departamento</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((r, i) => (
                    <TableRow key={(r.Req_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(r.Req_Cod ?? "-")}
                      </TableCell>
                      <TableCell>{String(r.Req_Fec ?? "-").slice(0, 10)}</TableCell>
                      <TableCell className="font-medium">
                        {String(r.Prs_Nom ?? "-")} {String(r.Prs_Ape ?? "")}
                      </TableCell>
                      <TableCell>{String(r.Dep_Des ?? "-")}</TableCell>
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
                          onClick={() => handleDelete(r.Req_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center h-24">
                        No se encontraron requisiciones.
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
                {isEdit ? "Editar Requisici�n" : "Nueva Requisici�n"}
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
                  value={String(formData.Req_Fec || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Req_Fec: e.target.value })
                  }
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  C�digo Empleado
                </label>
                <Input
                  type="number"
                  value={String(formData.Per_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Per_Cod: e.target.value })
                  }
                  placeholder="C�digo del empleado"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Descripci�n
                </label>
                <Input
                  value={String(formData.Req_Des || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Req_Des: e.target.value })
                  }
                  placeholder="Descripci�n de la requisici�n"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Estado
                </label>
                <select
                  value={String(formData.Req_Est || "P")}
                  onChange={(e) =>
                    setFormData({ ...formData, Req_Est: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-input bg-background rounded-md text-sm text-black"
                >
                  <option value="P">Pendiente</option>
                  <option value="A">Aprobada</option>
                  <option value="R">Rechazada</option>
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
                  {isEdit ? "Guardar Cambios" : "Crear Requisici�n"}
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

function RequisitoresTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => comprasApi.requisitores(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({});
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (r) =>
      !search ||
      String(r.Req_Nom || "").toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Req_Nom: "" });
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
        res = await comprasApi.modificarRequisitor(payload);
      } else {
        res = await comprasApi.crearRequisitor(payload);
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
    const ok = await confirm("¿Está seguro de eliminar este requisitor?");
    if (!ok) return;
    try {
      const res = await comprasApi.eliminarRequisitor(id);
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
          <CardTitle>Requisitores</CardTitle>
          <CardDescription>Personas autorizadas para solicitar compras.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Requisitor
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar requisitor..."
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
                  {filtered.map((r, i) => (
                    <TableRow key={(r.Req_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(r.Req_Cod ?? "-")}
                      </TableCell>
                      <TableCell className="font-medium">{String(r.Req_Nom ?? "-")}</TableCell>
                      <TableCell>{String(r.Req_Est ?? "-")}</TableCell>
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
                          onClick={() => handleDelete(r.Req_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={4} className="text-center h-24">
                        No se encontraron requisitores.
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
                {isEdit ? "Editar Requisitor" : "Nuevo Requisitor"}
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
                  value={String(formData.Req_Nom || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Req_Nom: e.target.value })
                  }
                  placeholder="Nombre del requisitor"
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
                  {isEdit ? "Guardar Cambios" : "Crear Requisitor"}
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

export default function ComprasPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">Compras</h2>
        <p className="text-muted-foreground mt-1">
          Gesti�n de requisiciones y requisitores.
        </p>
      </div>
      <Tabs defaultValue="requisiciones">
        <TabsList>
          <TabsTab value="requisiciones">Requisiciones</TabsTab>
          <TabsTab value="requisitores">Requisitores</TabsTab>
        </TabsList>
        <TabsPanel value="requisiciones">
          <RequisicionesTab />
        </TabsPanel>
        <TabsPanel value="requisitores">
          <RequisitoresTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
