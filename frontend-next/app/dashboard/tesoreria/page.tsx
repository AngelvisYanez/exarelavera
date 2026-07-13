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
import { tesoreriaApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import { toast } from "sonner";
import { useConfirm } from "@/lib/hooks/use-confirm";

function BancosTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => tesoreriaApi.bancos(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({
    Bak_Cod: "",
    Bak_Des: "",
    Bak_Est: "A",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (b) =>
      !search ||
      String(b.Bak_Des || "")
        .toLowerCase()
        .includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Bak_Cod: "", Bak_Des: "", Bak_Est: "A" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (banco: Record<string, unknown>) => {
    setFormData(banco);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (id: number | string) => {
    if (!(await confirm("¿Está seguro de eliminar este banco?"))) return;
    try {
      const res = await tesoreriaApi.eliminarBanco(id);
      if (res.success) {
        refetch();
        toast.success("Eliminado correctamente");
      }
    } catch {
      // ignore
    }
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
        res = await tesoreriaApi.modificarBanco(payload);
      } else {
        res = await tesoreriaApi.crearBanco(payload);
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

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Bancos</CardTitle>
          <CardDescription>Registro de bancos del sistema.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Banco
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar banco..."
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
                  {filtered.map((b: Record<string, unknown>, i: number) => (
                    <TableRow key={(b.Bak_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(b.Bak_Cod || "-")}
                      </TableCell>
                      <TableCell className="font-medium">
                        {String(b.Bak_Des || "-")}
                      </TableCell>
                      <TableCell>{String(b.Bak_Est || "-")}</TableCell>
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
                          onClick={() => handleDelete(b.Bak_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={4} className="text-center h-24">
                        No se encontraron bancos.
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
                {isEdit ? "Editar Banco" : "Nuevo Banco"}
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
                  Nombre del Banco *
                </label>
                <Input
                  required
                  value={String(formData.Bak_Des || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Bak_Des: e.target.value })
                  }
                  placeholder="Ej: Pichincha, Guayaquil, Pacífico..."
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Estado (A/I)
                </label>
                <Input
                  required
                  value={String(formData.Bak_Est || "A")}
                  onChange={(e) =>
                    setFormData({ ...formData, Bak_Est: e.target.value })
                  }
                  placeholder="A = Activo, I = Inactivo"
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
                  {isEdit ? "Guardar Cambios" : "Crear Banco"}
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

function CuentasBancoTab() {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Cuentas Bancarias</CardTitle>
        <CardDescription>Módulo no disponible — la tabla cuenta_banco no existe en la base de datos.</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
          <AlertCircle className="h-10 w-10 mb-3 opacity-50" />
          <p className="text-sm">Este módulo no está disponible actualmente.</p>
        </div>
      </CardContent>
    </Card>
  );
}

function ChequesTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => tesoreriaApi.cheques(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({
    Bak_Cod: "",
    Che_Fec: "",
    Che_Num: "",
    Che_Cli: "",
    Che_Val: "",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (ch) =>
      !search ||
      String(ch.Che_Num || "")
        .toLowerCase()
        .includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Bak_Cod: "", Che_Fec: "", Che_Num: "", Che_Cli: "", Che_Val: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (cheque: Record<string, unknown>) => {
    setFormData(cheque);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (id: number | string) => {
    if (!(await confirm("¿Está seguro de eliminar este cheque?"))) return;
    try {
      const res = await tesoreriaApi.eliminarCheque(id);
      if (res.success) {
        refetch();
        toast.success("Eliminado correctamente");
      }
    } catch {
      // ignore
    }
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
        res = await tesoreriaApi.modificarCheque(payload);
      } else {
        res = await tesoreriaApi.crearCheque(payload);
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

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Cheques</CardTitle>
          <CardDescription>Registro de cheques emitidos.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Cheque
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por número de cheque..."
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
                    <TableHead>Código</TableHead>
                    <TableHead>Número</TableHead>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Cliente</TableHead>
                    <TableHead>Valor</TableHead>
                    <TableHead>Cod. Banco</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((ch: Record<string, unknown>, i: number) => (
                    <TableRow key={(ch.Che_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(ch.Che_Cod || "-")}
                      </TableCell>
                      <TableCell className="font-medium">
                        {String(ch.Che_Num || "-")}
                      </TableCell>
                      <TableCell>{String(ch.Che_Fec || "-")}</TableCell>
                      <TableCell>{String(ch.Che_Cli || "-")}</TableCell>
                      <TableCell>{String(ch.Che_Val || "-")}</TableCell>
                      <TableCell>{String(ch.Bak_Cod || "-")}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleOpenEdit(ch)}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(ch.Che_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center h-24">
                        No se encontraron cheques.
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
                {isEdit ? "Editar Cheque" : "Nuevo Cheque"}
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
                  Código de Banco *
                </label>
                <Input
                  type="number"
                  required
                  value={String(formData.Bak_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Bak_Cod: e.target.value })
                  }
                  placeholder="Código del banco"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Fecha *
                </label>
                <Input
                  type="date"
                  required
                  value={String(formData.Che_Fec || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Che_Fec: e.target.value })
                  }
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Número de Cheque *
                </label>
                <Input
                  type="number"
                  required
                  value={String(formData.Che_Num || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Che_Num: e.target.value })
                  }
                  placeholder="Número del cheque"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Cliente
                </label>
                <Input
                  value={String(formData.Che_Cli || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Che_Cli: e.target.value })
                  }
                  placeholder="Nombre del cliente"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Valor
                </label>
                <Input
                  type="number"
                  value={String(formData.Che_Val || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Che_Val: e.target.value })
                  }
                  placeholder="Valor del cheque"
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
                  {isEdit ? "Guardar Cambios" : "Crear Cheque"}
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

function ConciliacionTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => tesoreriaApi.conciliacion(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({
    Pec_Cod: "",
    Ban_Cod: "",
    Usu_Cod: "",
    Cob_Fec: "",
    Cob_Dis: "",
    Cob_Obs: "",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as unknown as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (c) =>
      !search ||
      String(c.Cob_Cod || "")
        .toLowerCase()
        .includes(search.toLowerCase()) ||
      String(c.Cob_Obs || "")
        .toLowerCase()
        .includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Pec_Cod: "", Ban_Cod: "", Usu_Cod: "", Cob_Fec: "", Cob_Dis: "", Cob_Obs: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (conc: Record<string, unknown>) => {
    setFormData(conc);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (id: number | string) => {
    if (!(await confirm("¿Está seguro de eliminar este registro de conciliación?"))) return;
    try {
      const res = await tesoreriaApi.eliminarConciliacion(id);
      if (res.success) {
        refetch();
        toast.success("Eliminado correctamente");
      }
    } catch {
      // ignore
    }
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
        res = await tesoreriaApi.modificarConciliacion(payload);
      } else {
        res = await tesoreriaApi.crearConciliacion(payload);
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

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Conciliación Bancaria</CardTitle>
          <CardDescription>Registros de conciliación bancaria.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Conciliación
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por código u observaciones..."
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
                    <TableHead>Código</TableHead>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Disponible</TableHead>
                    <TableHead>Observaciones</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((c: Record<string, unknown>, i: number) => (
                    <TableRow key={(c.Cob_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(c.Cob_Cod || "-")}
                      </TableCell>
                      <TableCell>{String(c.Cob_Fec || "-")}</TableCell>
                      <TableCell>{String(c.Cob_Dis || "-")}</TableCell>
                      <TableCell className="font-medium">
                        {String(c.Cob_Obs || "-")}
                      </TableCell>
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
                          onClick={() => handleDelete(c.Cob_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center h-24">
                        No se encontraron registros de conciliación.
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
                {isEdit ? "Editar Conciliación" : "Nueva Conciliación"}
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
                  Código de Periodo (Pec_Cod) *
                </label>
                <Input
                  type="number"
                  required
                  value={String(formData.Pec_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Pec_Cod: e.target.value })
                  }
                  placeholder="Código del periodo contable"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Código de Banco (Ban_Cod) *
                </label>
                <Input
                  type="number"
                  required
                  value={String(formData.Ban_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Ban_Cod: e.target.value })
                  }
                  placeholder="Código del banco"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Código de Usuario (Usu_Cod) *
                </label>
                <Input
                  type="number"
                  required
                  value={String(formData.Usu_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Usu_Cod: e.target.value })
                  }
                  placeholder="Código del usuario"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Fecha *
                </label>
                <Input
                  type="date"
                  required
                  value={String(formData.Cob_Fec || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Cob_Fec: e.target.value })
                  }
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Disponible
                </label>
                <Input
                  type="number"
                  value={String(formData.Cob_Dis || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Cob_Dis: e.target.value })
                  }
                  placeholder="Valor disponible"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Observaciones
                </label>
                <Input
                  value={String(formData.Cob_Obs || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Cob_Obs: e.target.value })
                  }
                  placeholder="Observaciones de la conciliación"
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
                  {isEdit ? "Guardar Cambios" : "Crear Conciliación"}
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

export default function TesoreriaPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">
          Tesorería
        </h2>
        <p className="text-muted-foreground mt-1">
          Bancos, cuentas bancarias, cheques y conciliación.
        </p>
      </div>
      <Tabs defaultValue="bancos">
        <TabsList>
          <TabsTab value="bancos">Bancos</TabsTab>
          <TabsTab value="cuentas">Cuentas Banco</TabsTab>
          <TabsTab value="cheques">Cheques</TabsTab>
          <TabsTab value="conciliacion">Conciliación</TabsTab>
        </TabsList>
        <TabsPanel value="bancos">
          <BancosTab />
        </TabsPanel>
        <TabsPanel value="cuentas">
          <CuentasBancoTab />
        </TabsPanel>
        <TabsPanel value="cheques">
          <ChequesTab />
        </TabsPanel>
        <TabsPanel value="conciliacion">
          <ConciliacionTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
