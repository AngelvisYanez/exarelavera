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
import { activosFijosApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import { toast } from "sonner";
import { useConfirm } from "@/lib/hooks/use-confirm";
interface ActivoFijoItem {
  Act_Cod: number;
  Tia_Cod: number;
  Suc_Cod: number;
  Est_Cod: number;
  Act_Des: string;
  Act_Val: number;
  Act_Gar: number;
  Act_Est: string;
  Act_Fec: string;
}

interface TipoActivoItem {
  Tia_Cod: number;
  Tia_Des: string;
  Tia_Est: string;
  Tia_Rec: number;
  Emp_Cod: number;
}

interface MantenimientoItem {
  Man_Cod?: number;
  Tma_Cod?: number;
  Act_Cod?: number;
  Act_Des?: string;
  Ema_Cod?: number;
  Est_Cod?: number;
  Man_Des?: string;
  Man_Fec?: string;
  Man_Est?: string;
}

function ActivosTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => activosFijosApi.activos(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<ActivoFijoItem>>({
    Act_Cod: 0,
    Tia_Cod: 0,
    Suc_Cod: 0,
    Est_Cod: 0,
    Act_Des: "",
    Act_Val: 0,
    Act_Gar: 0,
    Act_Est: "A",
    Act_Fec: "",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as ActivoFijoItem[]) : [];
  const filtered = items.filter(
    (a) => !search || a.Act_Des?.toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Act_Cod: 0, Tia_Cod: 0, Suc_Cod: 0, Est_Cod: 0, Act_Des: "", Act_Val: 0, Act_Gar: 0, Act_Est: "A", Act_Fec: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (act: ActivoFijoItem) => {
    setFormData(act);
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
        res = await activosFijosApi.modificarActivo(payload);
      } else {
        res = await activosFijosApi.crearActivo(payload);
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

  const handleDelete = async (act: ActivoFijoItem) => {
    if (!(await confirm(`¿Eliminar el activo "${act.Act_Des}"?`))) return;
    try {
      const res = await activosFijosApi.eliminarActivo(act.Act_Cod);
      if (res.success) {
        toast.success("Activo eliminado");
        refetch();
      }
    } catch {
      toast.error("Error al eliminar el activo");
    }
  };

  return (
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Activos Fijos</CardTitle>
          <CardDescription>
            Registro y control de activos fijos de la empresa.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Activo
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar activo..."
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
                    <TableHead>Descripción</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead>Valor</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((a: ActivoFijoItem, i: number) => (
                    <TableRow key={a.Act_Cod || i}>
                      <TableCell className="font-semibold text-primary">
                        {a.Act_Cod || "-"}
                      </TableCell>
                      <TableCell className="font-medium">{a.Act_Des}</TableCell>
                      <TableCell>{a.Tia_Cod || "-"}</TableCell>
                      <TableCell>{a.Act_Val || "-"}</TableCell>
                      <TableCell>{a.Act_Est || "-"}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleOpenEdit(a)}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(a)}
                        >
                          <Trash2 className="h-4 w-4 text-error" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center h-24">
                        No se encontraron activos.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Activos */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Activo" : "Nuevo Activo"}
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
                  value={formData.Act_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Act_Cod: Number(e.target.value) })
                  }
                  placeholder="Código del activo"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Descripción *
                </label>
                <Input
                  required
                  value={formData.Act_Des || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Act_Des: e.target.value })
                  }
                  placeholder="Nombre o descripción del activo"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Fecha de Adquisición
                </label>
                <Input
                  type="date"
                  value={formData.Act_Fec || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Act_Fec: e.target.value })
                  }
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Tipo de Activo *
                  </label>
                  <Input
                    required
                    type="number"
                    value={formData.Tia_Cod || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Tia_Cod: Number(e.target.value) })
                    }
                    placeholder="Código del tipo"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Estado *
                  </label>
                  <Input
                    required
                    value={formData.Act_Est || "A"}
                    onChange={(e) =>
                      setFormData({ ...formData, Act_Est: e.target.value })
                    }
                    placeholder="A o I"
                  />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Valor
                  </label>
                  <Input
                    type="number"
                    value={formData.Act_Val || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Act_Val: Number(e.target.value) })
                    }
                    placeholder="Valor del activo"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Garantía *
                  </label>
                  <Input
                    required
                    type="number"
                    value={formData.Act_Gar ?? 0}
                    onChange={(e) =>
                      setFormData({ ...formData, Act_Gar: Number(e.target.value) })
                    }
                    placeholder="Garantía (meses)"
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
                  {isEdit ? "Guardar Cambios" : "Crear Activo"}
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

function TiposActivoTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const { data, loading, refetch } = useQuery(() => activosFijosApi.tiposActivo(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<TipoActivoItem>>({
    Tia_Cod: 0,
    Tia_Des: "",
    Tia_Est: "A",
    Tia_Rec: 0,
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as unknown as TipoActivoItem[]) : [];

  const handleOpenCreate = () => {
    setFormData({ Tia_Cod: 0, Tia_Des: "", Tia_Est: "A", Tia_Rec: 0 });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (tipo: TipoActivoItem) => {
    setFormData(tipo);
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
        res = await activosFijosApi.modificarTipoActivo(payload);
      } else {
        res = await activosFijosApi.crearTipoActivo(payload);
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

  const handleDelete = async (tipo: TipoActivoItem) => {
    if (!(await confirm(`¿Eliminar el tipo de activo "${tipo.Tia_Des}"?`))) return;
    try {
      const res = await activosFijosApi.eliminarTipoActivo(tipo.Tia_Cod);
      if (res.success) {
        toast.success("Tipo de activo eliminado");
        refetch();
      }
    } catch {
      toast.error("Error al eliminar el tipo de activo");
    }
  };

  return (
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Tipos de Activo</CardTitle>
          <CardDescription>
            Categorías y clasificaciones de activos fijos.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Tipo
        </Button>
      </CardHeader>
      <CardContent>
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
                  {items.map((t: TipoActivoItem, i: number) => (
                    <TableRow key={t.Tia_Cod || i}>
                      <TableCell className="font-semibold text-primary">
                        {t.Tia_Cod || "-"}
                      </TableCell>
                      <TableCell className="font-medium">{t.Tia_Des}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleOpenEdit(t)}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(t)}
                        >
                          <Trash2 className="h-4 w-4 text-error" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {items.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={3} className="text-center h-24">
                        No se encontraron tipos de activo.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Tipos de Activo */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Tipo de Activo" : "Nuevo Tipo de Activo"}
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
                  value={formData.Tia_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Tia_Cod: Number(e.target.value) })
                  }
                  placeholder="Código del tipo"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Nombre / Descripción *
                </label>
                <Input
                  required
                  value={formData.Tia_Des || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Tia_Des: e.target.value })
                  }
                  placeholder="Ej: Muebles, Maquinaria, Vehículos..."
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Estado
                  </label>
                  <Input
                    value={formData.Tia_Est || "A"}
                    onChange={(e) =>
                      setFormData({ ...formData, Tia_Est: e.target.value })
                    }
                    placeholder="A o I"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Recurso
                  </label>
                  <Input
                    type="number"
                    value={formData.Tia_Rec ?? 0}
                    onChange={(e) =>
                      setFormData({ ...formData, Tia_Rec: Number(e.target.value) })
                    }
                    placeholder="Recurso"
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
                  {isEdit ? "Guardar Cambios" : "Crear Tipo"}
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

function MantenimientosTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const { data, loading, refetch } = useQuery(() => activosFijosApi.mantenimientos(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<MantenimientoItem>>({
    Act_Cod: 0,
    Tma_Cod: 0,
    Ema_Cod: 0,
    Est_Cod: 0,
    Man_Des: "",
    Man_Fec: "",
    Man_Est: "A",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as MantenimientoItem[]) : [];

  const handleOpenCreate = () => {
    setFormData({ Act_Cod: 0, Tma_Cod: 0, Ema_Cod: 0, Est_Cod: 0, Man_Des: "", Man_Fec: "", Man_Est: "A" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (man: MantenimientoItem) => {
    setFormData(man);
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
        res = await activosFijosApi.modificarMantenimiento(payload);
      } else {
        res = await activosFijosApi.crearMantenimiento(payload);
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

  const handleDelete = async (man: MantenimientoItem) => {
    if (!(await confirm(`¿Eliminar este mantenimiento?`))) return;
    try {
      const res = await activosFijosApi.eliminarMantenimiento(man.Man_Cod!);
      if (res.success) {
        toast.success("Mantenimiento eliminado");
        refetch();
      }
    } catch {
      toast.error("Error al eliminar el mantenimiento");
    }
  };

  return (
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Mantenimientos</CardTitle>
          <CardDescription>
            Registro de mantenimientos realizados a los activos fijos.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Mantenimiento
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
                    <TableHead>Activo</TableHead>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((m: MantenimientoItem, i: number) => (
                    <TableRow key={m.Man_Cod || i}>
                      <TableCell className="font-semibold text-primary">
                        {m.Man_Cod || "-"}
                      </TableCell>
                      <TableCell className="font-medium">{m.Act_Des || "-"}</TableCell>
                      <TableCell>{m.Man_Fec || "-"}</TableCell>
                      <TableCell>{m.Man_Est || "-"}</TableCell>
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
                      <TableCell colSpan={5} className="text-center h-24">
                        No se encontraron mantenimientos.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Mantenimientos */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Mantenimiento" : "Nuevo Mantenimiento"}
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
                  Código Activo *
                </label>
                <Input
                  required
                  type="number"
                  value={formData.Act_Cod || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Act_Cod: Number(e.target.value) })
                  }
                  placeholder="Código del activo"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Fecha *
                </label>
                <Input
                  required
                  type="date"
                  value={formData.Man_Fec || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Man_Fec: e.target.value })
                  }
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Descripción
                </label>
                <Input
                  value={formData.Man_Des || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Man_Des: e.target.value })
                  }
                  placeholder="Descripción del mantenimiento"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Tipo de Mantenimiento
                  </label>
                  <Input
                    type="number"
                    value={formData.Tma_Cod || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Tma_Cod: Number(e.target.value) })
                    }
                    placeholder="Tma_Cod"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Estado del Mantenimiento
                  </label>
                  <Input
                    value={formData.Man_Est || "A"}
                    onChange={(e) =>
                      setFormData({ ...formData, Man_Est: e.target.value })
                    }
                    placeholder="A o I"
                  />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Ema_Cod
                  </label>
                  <Input
                    type="number"
                    value={formData.Ema_Cod ?? 0}
                    onChange={(e) =>
                      setFormData({ ...formData, Ema_Cod: Number(e.target.value) })
                    }
                    placeholder="Ema_Cod"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Est_Cod
                  </label>
                  <Input
                    type="number"
                    value={formData.Est_Cod ?? 0}
                    onChange={(e) =>
                      setFormData({ ...formData, Est_Cod: Number(e.target.value) })
                    }
                    placeholder="Est_Cod"
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
                  {isEdit ? "Guardar Cambios" : "Crear Mantenimiento"}
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

export default function ActivosFijosPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">Activos Fijos</h2>
        <p className="text-muted-foreground mt-1">
          Gestión de activos fijos, tipos y mantenimientos.
        </p>
      </div>
      <Tabs defaultValue="activos">
        <TabsList>
          <TabsTab value="activos">Activos</TabsTab>
          <TabsTab value="tipos">Tipos de Activo</TabsTab>
          <TabsTab value="mantenimientos">Mantenimientos</TabsTab>
        </TabsList>
        <TabsPanel value="activos">
          <ActivosTab />
        </TabsPanel>
        <TabsPanel value="tipos">
          <TiposActivoTab />
        </TabsPanel>
        <TabsPanel value="mantenimientos">
          <MantenimientosTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
