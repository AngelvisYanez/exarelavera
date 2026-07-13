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
import { rrhhApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import { useConfirm } from "@/lib/hooks/use-confirm";
import type { PersonalRow, ContratoRow } from "@/lib/api-types";

interface PersonalForm {
  Prs_Ced: string;
  Prs_Nom: string;
  Prs_Ape: string;
  Prs_Tel: string;
  Prs_Cel: string;
  Prs_Cor: string;
  Car_Cod: number;
  Dep_Cod: number;
}

interface ContratoForm {
  Per_Cod: number;
  Tic_Cod: number;
  Con_Ini: string;
  Con_Fin: string;
  Con_Est: string;
}

interface DepartamentoForm {
  Dep_Cod: number;
  Dep_Des: string;
}

function PersonalTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => rrhhApi.personal(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<PersonalForm>({
    Prs_Ced: "",
    Prs_Nom: "",
    Prs_Ape: "",
    Prs_Tel: "",
    Prs_Cel: "",
    Prs_Cor: "",
    Car_Cod: 0,
    Dep_Cod: 0,
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as PersonalRow[]) : [];
  const filtered = items.filter(
    (p) =>
      !search ||
      p.Prs_Nom?.toLowerCase().includes(search.toLowerCase()) ||
      p.Prs_Ape?.toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({
      Prs_Ced: "",
      Prs_Nom: "",
      Prs_Ape: "",
      Prs_Tel: "",
      Prs_Cel: "",
      Prs_Cor: "",
      Car_Cod: 0,
      Dep_Cod: 0,
    });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (item: PersonalRow) => {
    setFormData({
      Prs_Ced: item.Prs_Ced || "",
      Prs_Nom: item.Prs_Nom || "",
      Prs_Ape: item.Prs_Ape || "",
      Prs_Tel: "",
      Prs_Cel: "",
      Prs_Cor: "",
      Car_Cod: 0,
      Dep_Cod: 0,
    });
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (item: PersonalRow) => {
    if (!await confirm(`¿Eliminar a ${item.Prs_Nom} ${item.Prs_Ape}?`)) return;
    try {
      const res = await rrhhApi.eliminarPersonal(item.Per_Cod);
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
        res = await rrhhApi.modificarPersonal(payload);
      } else {
        res = await rrhhApi.crearPersonal(payload);
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
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Personal</CardTitle>
          <CardDescription>
            Registro de empleados y personal de la empresa.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Personal
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por nombre o apellido..."
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
                <TableHead>Cédula</TableHead>
                <TableHead>Nombre</TableHead>
                <TableHead>Apellido</TableHead>
                <TableHead>Cargo</TableHead>
                <TableHead>Departamento</TableHead>
                <TableHead className="text-center">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((p: PersonalRow, i: number) => (
                <TableRow key={p.Per_Cod || i}>
                  <TableCell className="font-semibold text-primary">
                    {p.Prs_Ced || "-"}
                  </TableCell>
                  <TableCell className="font-medium">{p.Prs_Nom || "-"}</TableCell>
                  <TableCell>{p.Prs_Ape || "-"}</TableCell>
                  <TableCell>{String((p as unknown as Record<string, unknown>).Car_Des || "-")}</TableCell>
                  <TableCell>{String((p as unknown as Record<string, unknown>).Dep_Des || "-")}</TableCell>
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
                  <TableCell colSpan={6} className="text-center h-24">
                    No se encontró personal.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Personal */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Personal" : "Nuevo Personal"}
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
                  Cédula
                </label>
                <Input
                  value={formData.Prs_Ced}
                  onChange={(e) =>
                    setFormData({ ...formData, Prs_Ced: e.target.value })
                  }
                  placeholder="Número de cédula"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Nombre *
                  </label>
                  <Input
                    required
                    value={formData.Prs_Nom}
                    onChange={(e) =>
                      setFormData({ ...formData, Prs_Nom: e.target.value })
                    }
                    placeholder="Nombre"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Apellido *
                  </label>
                  <Input
                    required
                    value={formData.Prs_Ape}
                    onChange={(e) =>
                      setFormData({ ...formData, Prs_Ape: e.target.value })
                    }
                    placeholder="Apellido"
                  />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Teléfono
                  </label>
                  <Input
                    value={formData.Prs_Tel}
                    onChange={(e) =>
                      setFormData({ ...formData, Prs_Tel: e.target.value })
                    }
                    placeholder="Teléfono fijo"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Celular
                  </label>
                  <Input
                    value={formData.Prs_Cel}
                    onChange={(e) =>
                      setFormData({ ...formData, Prs_Cel: e.target.value })
                    }
                    placeholder="Celular"
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Correo Electrónico
                </label>
                <Input
                  type="email"
                  value={formData.Prs_Cor}
                  onChange={(e) =>
                    setFormData({ ...formData, Prs_Cor: e.target.value })
                  }
                  placeholder="correo@ejemplo.com"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Código Cargo
                  </label>
                  <Input
                    type="number"
                    value={formData.Car_Cod || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Car_Cod: Number(e.target.value) })
                    }
                    placeholder="Código del cargo"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Código Departamento
                  </label>
                  <Input
                    type="number"
                    value={formData.Dep_Cod || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Dep_Cod: Number(e.target.value) })
                    }
                    placeholder="Código del departamento"
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
                  {isEdit ? "Guardar Cambios" : "Crear Personal"}
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

function ContratosTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => rrhhApi.contratos(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<ContratoForm>({
    Per_Cod: 0,
    Tic_Cod: 0,
    Con_Ini: "",
    Con_Fin: "",
    Con_Est: "A",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as ContratoRow[]) : [];
  const filtered = items.filter(
    (c) =>
      !search ||
      [c.Prs_Nom, c.Prs_Ape]
        .filter(Boolean)
        .join(" ")
        .toLowerCase()
        .includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({
      Per_Cod: 0,
      Tic_Cod: 0,
      Con_Ini: "",
      Con_Fin: "",
      Con_Est: "A",
    });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (item: ContratoRow) => {
    setFormData({
      Per_Cod: item.Per_Cod,
      Tic_Cod: item.Tic_Cod || 0,
      Con_Ini: item.Con_Ini || "",
      Con_Fin: item.Con_Fin || "",
      Con_Est: item.Con_Est || "A",
    });
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (item: ContratoRow) => {
    if (!await confirm(`¿Eliminar el contrato de ${item.Prs_Nom} ${item.Prs_Ape}?`)) return;
    try {
      const res = await rrhhApi.eliminarContrato(item.Con_Cod);
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
        res = await rrhhApi.modificarContrato(payload);
      } else {
        res = await rrhhApi.crearContrato(payload);
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
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Contratos</CardTitle>
          <CardDescription>
            Contratos laborales de los empleados.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Contrato
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por empleado..."
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
                <TableHead>Empleado</TableHead>
                <TableHead>Tipo</TableHead>
                <TableHead>Fecha Inicio</TableHead>
                <TableHead>Fecha Fin</TableHead>
                <TableHead className="text-center">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((c: ContratoRow, i: number) => (
                <TableRow key={c.Con_Cod || i}>
                  <TableCell className="font-semibold text-primary">
                    {c.Con_Cod || "-"}
                  </TableCell>
                  <TableCell className="font-medium">
                    {[c.Prs_Nom, c.Prs_Ape].filter(Boolean).join(" ") || "-"}
                  </TableCell>
                  <TableCell>{c.Tic_Des || "-"}</TableCell>
                  <TableCell>{c.Con_Ini}</TableCell>
                  <TableCell>{c.Con_Fin || "-"}</TableCell>
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
                    No se encontraron contratos.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Contratos */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Contrato" : "Nuevo Contrato"}
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
                  Código Personal *
                </label>
                <Input
                  required
                  type="number"
                  value={formData.Per_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Per_Cod: Number(e.target.value) })
                  }
                  placeholder="Código del empleado"
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
                    value={formData.Con_Ini}
                    onChange={(e) =>
                      setFormData({ ...formData, Con_Ini: e.target.value })
                    }
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Fecha Fin
                  </label>
                  <Input
                    type="date"
                    value={formData.Con_Fin}
                    onChange={(e) =>
                      setFormData({ ...formData, Con_Fin: e.target.value })
                    }
                  />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Tipo Contrato
                  </label>
                  <Input
                    type="number"
                    value={formData.Tic_Cod || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Tic_Cod: Number(e.target.value) })
                    }
                    placeholder="Código tipo contrato"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Estado
                  </label>
                  <Input
                    value={formData.Con_Est}
                    onChange={(e) =>
                      setFormData({ ...formData, Con_Est: e.target.value })
                    }
                    placeholder="A (activo) o I (inactivo)"
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
                  {isEdit ? "Guardar Cambios" : "Crear Contrato"}
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

function DepartamentosTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => rrhhApi.departamentos(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<DepartamentoForm>({
    Dep_Cod: 0,
    Dep_Des: "",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data)
    ? (data.data as Record<string, unknown>[])
    : [];
  const filtered = items.filter(
    (d) =>
      !search ||
      String(d.Dep_Des || "")
        .toLowerCase()
        .includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Dep_Cod: 0, Dep_Des: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (item: Record<string, unknown>) => {
    setFormData({
      Dep_Cod: Number(item.Dep_Cod) || 0,
      Dep_Des: String(item.Dep_Des || ""),
    });
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (item: Record<string, unknown>) => {
    if (!await confirm(`¿Eliminar el departamento "${item.Dep_Des}"?`)) return;
    try {
      const res = await rrhhApi.eliminarDepartamento(item.Dep_Cod as number);
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
        res = await rrhhApi.modificarDepartamento(payload);
      } else {
        res = await rrhhApi.crearDepartamento(payload);
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
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Departamentos</CardTitle>
          <CardDescription>
            Estructura organizacional de la empresa.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Departamento
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar departamento..."
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
            <div className="min-w-[500px]">
              <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Código</TableHead>
                <TableHead>Nombre</TableHead>
                <TableHead className="text-center">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((d: Record<string, unknown>, i: number) => (
                <TableRow key={String(d.Dep_Cod || i)}>
                  <TableCell className="font-semibold text-primary">
                    {String(d.Dep_Cod || "-")}
                  </TableCell>
                  <TableCell className="font-medium">
                    {String(d.Dep_Des || "-")}
                  </TableCell>
                  <TableCell className="text-center">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleOpenEdit(d)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleDelete(d)}
                    >
                      <Trash2 className="h-4 w-4 text-error" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
              {filtered.length === 0 && (
                <TableRow>
                  <TableCell colSpan={3} className="text-center h-24">
                    No se encontraron departamentos.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Departamentos */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Departamento" : "Nuevo Departamento"}
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
                  value={formData.Dep_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Dep_Cod: Number(e.target.value) })
                  }
                  placeholder="Código del departamento"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Nombre *
                </label>
                <Input
                  required
                  value={formData.Dep_Des}
                  onChange={(e) =>
                    setFormData({ ...formData, Dep_Des: e.target.value })
                  }
                  placeholder="Ej: Recursos Humanos, Contabilidad..."
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
                  {isEdit ? "Guardar Cambios" : "Crear Departamento"}
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

export default function RRHHPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">Recursos Humanos</h2>
        <p className="text-muted-foreground mt-1">
          Gestión de personal, contratos y departamentos.
        </p>
      </div>
      <Tabs defaultValue="personal">
        <TabsList>
          <TabsTab value="personal">Personal</TabsTab>
          <TabsTab value="contratos">Contratos</TabsTab>
          <TabsTab value="departamentos">Departamentos</TabsTab>
        </TabsList>
        <TabsPanel value="personal">
          <PersonalTab />
        </TabsPanel>
        <TabsPanel value="contratos">
          <ContratosTab />
        </TabsPanel>
        <TabsPanel value="departamentos">
          <DepartamentosTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
