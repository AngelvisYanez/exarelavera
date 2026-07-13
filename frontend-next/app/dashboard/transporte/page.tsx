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
import { transporteCargaApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import { toast } from "sonner";
import { useConfirm } from "@/lib/hooks/use-confirm";

function ViajesTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => transporteCargaApi.viajes(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({
    Cli_Cod: "",
    Veh_Cod: "",
    Via_Fec: "",
    Via_Tra: "N",
    Via_Uni: "",
    Via_Est: "A",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (v) =>
      !search ||
      String(v.Via_Fec || "")
        .toLowerCase()
        .includes(search.toLowerCase()) ||
      [String(v.cliente_nombre || ""), String(v.cliente_apellido || "")]
        .join(" ")
        .toLowerCase()
        .includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({
      Cli_Cod: "",
      Veh_Cod: "",
      Via_Fec: "",
      Via_Tra: "N",
      Via_Uni: "",
      Via_Est: "A",
    });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (viaje: Record<string, unknown>) => {
    setFormData(viaje);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (id: number | string) => {
    if (!(await confirm("¿Está seguro de eliminar este viaje?"))) return;
    try {
      const res = await transporteCargaApi.eliminarViaje(id);
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
        res = await transporteCargaApi.modificarViaje(payload);
      } else {
        res = await transporteCargaApi.crearViaje(payload);
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
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Viajes</CardTitle>
          <CardDescription>Gestión de viajes de transporte de carga.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Viaje
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por fecha o cliente..."
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
                    <TableHead>Cliente</TableHead>
                    <TableHead>Vehículo</TableHead>
                    <TableHead>Traslado</TableHead>
                    <TableHead>Unidades</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((v: Record<string, unknown>, i: number) => (
                    <TableRow key={(v.Via_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(v.Via_Cod || "-")}
                      </TableCell>
                      <TableCell>{String(v.Via_Fec || "-")}</TableCell>
                      <TableCell className="font-medium">
                        {[String(v.cliente_nombre || ""), String(v.cliente_apellido || "")]
                          .filter(Boolean)
                          .join(" ") || "-"}
                      </TableCell>
                      <TableCell>{String(v.Veh_Cod || "-")}</TableCell>
                      <TableCell>{String(v.Via_Tra || "-")}</TableCell>
                      <TableCell>{String(v.Via_Uni || "-")}</TableCell>
                      <TableCell>{String(v.Via_Est || "-")}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleOpenEdit(v)}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(v.Via_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={8} className="text-center h-24">
                        No se encontraron viajes.
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
                {isEdit ? "Editar Viaje" : "Nuevo Viaje"}
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
                  Fecha del Viaje *
                </label>
                <Input
                  type="date"
                  required
                  value={String(formData.Via_Fec || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Via_Fec: e.target.value })
                  }
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Código Cliente
                  </label>
                  <Input
                    type="number"
                    value={String(formData.Cli_Cod || "")}
                    onChange={(e) =>
                      setFormData({ ...formData, Cli_Cod: e.target.value })
                    }
                    placeholder="Código del cliente"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Código Vehículo
                  </label>
                  <Input
                    type="number"
                    value={String(formData.Veh_Cod || "")}
                    onChange={(e) =>
                      setFormData({ ...formData, Veh_Cod: e.target.value })
                    }
                    placeholder="Código del vehículo"
                  />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Via Traslado *
                  </label>
                  <Input
                    value={String(formData.Via_Tra || "N")}
                    onChange={(e) =>
                      setFormData({ ...formData, Via_Tra: e.target.value })
                    }
                    placeholder="N o S"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Unidades
                  </label>
                  <Input
                    value={String(formData.Via_Uni || "")}
                    onChange={(e) =>
                      setFormData({ ...formData, Via_Uni: e.target.value })
                    }
                    placeholder="Unidades transportadas"
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Estado *
                </label>
                <Input
                  value={String(formData.Via_Est || "A")}
                  onChange={(e) =>
                    setFormData({ ...formData, Via_Est: e.target.value })
                  }
                  placeholder="A o I"
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
                  {isEdit ? "Guardar Cambios" : "Crear Viaje"}
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

function VehiculosTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => transporteCargaApi.vehiculos(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({
    Veh_Cod: "",
    Veh_Mar: "",
    Veh_Pla: "",
    Veh_Col: "",
    Veh_Est: "A",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (v) =>
      !search ||
      String(v.Veh_Pla || "")
        .toLowerCase()
        .includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Veh_Cod: "", Veh_Mar: "", Veh_Pla: "", Veh_Col: "", Veh_Est: "A" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (veh: Record<string, unknown>) => {
    setFormData(veh);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (id: number | string) => {
    if (!(await confirm("¿Está seguro de eliminar este vehículo?"))) return;
    try {
      const res = await transporteCargaApi.eliminarVehiculo(id);
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
        res = await transporteCargaApi.modificarVehiculo(payload);
      } else {
        res = await transporteCargaApi.crearVehiculo(payload);
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
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Vehículos</CardTitle>
          <CardDescription>Flota de vehículos de transporte.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Vehículo
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por placa..."
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
                    <TableHead>Placa</TableHead>
                    <TableHead>Marca</TableHead>
                    <TableHead>Color</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((v: Record<string, unknown>, i: number) => (
                    <TableRow key={(v.Veh_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(v.Veh_Cod || "-")}
                      </TableCell>
                      <TableCell className="font-medium">
                        {String(v.Veh_Pla || "-")}
                      </TableCell>
                      <TableCell>{String(v.Veh_Mar || "-")}</TableCell>
                      <TableCell>{String(v.Veh_Col || "-")}</TableCell>
                      <TableCell>{String(v.Veh_Est || "-")}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleOpenEdit(v)}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(v.Veh_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center h-24">
                        No se encontraron vehículos.
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
                {isEdit ? "Editar Vehículo" : "Nuevo Vehículo"}
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
                  Código del Vehículo *
                </label>
                <Input
                  type="number"
                  required
                  value={String(formData.Veh_Cod || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Veh_Cod: e.target.value })
                  }
                  placeholder="Código único del vehículo"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Placa *
                </label>
                <Input
                  required
                  value={String(formData.Veh_Pla || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Veh_Pla: e.target.value })
                  }
                  placeholder="Ej: ABC-1234"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Marca *
                </label>
                <Input
                  required
                  value={String(formData.Veh_Mar || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Veh_Mar: e.target.value })
                  }
                  placeholder="Marca del vehículo"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Color
                </label>
                <Input
                  value={String(formData.Veh_Col || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Veh_Col: e.target.value })
                  }
                  placeholder="Color del vehículo"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Estado *
                </label>
                <Input
                  required
                  value={String(formData.Veh_Est || "A")}
                  onChange={(e) =>
                    setFormData({ ...formData, Veh_Est: e.target.value })
                  }
                  placeholder="A o I"
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
                  {isEdit ? "Guardar Cambios" : "Crear Vehículo"}
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

function TicketsTab() {
  const { confirm, ConfirmDialog } = useConfirm();
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => transporteCargaApi.tickets(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Record<string, unknown>>({
    Tic_Des: "",
    Tic_Fec_Cre: "",
    Tic_Est: "A",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const items = Array.isArray(data?.data) ? (data.data as Record<string, unknown>[]) : [];
  const filtered = items.filter(
    (t) =>
      !search ||
      String(t.Tic_Cod || "")
        .toLowerCase()
        .includes(search.toLowerCase()) ||
      String(t.Tic_Des || "")
        .toLowerCase()
        .includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Tic_Des: "", Tic_Fec_Cre: "", Tic_Est: "A" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (ticket: Record<string, unknown>) => {
    setFormData(ticket);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (id: number | string) => {
    if (!(await confirm("¿Está seguro de eliminar este ticket?"))) return;
    try {
      const res = await transporteCargaApi.eliminarTicket(id);
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
        res = await transporteCargaApi.modificarTicket(payload);
      } else {
        res = await transporteCargaApi.crearTicket(payload);
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
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Tickets</CardTitle>
          <CardDescription>Tickets asociados a viajes de transporte.</CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Ticket
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por código o descripción..."
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
                    <TableHead>Fecha Creación</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-center">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((t: Record<string, unknown>, i: number) => (
                    <TableRow key={(t.Tic_Cod as number) || i}>
                      <TableCell className="font-semibold text-primary">
                        {String(t.Tic_Cod || "-")}
                      </TableCell>
                      <TableCell>{String(t.Tic_Des || "-")}</TableCell>
                      <TableCell>{String(t.Tic_Fec_Cre || "-")}</TableCell>
                      <TableCell>{String(t.Tic_Est || "-")}</TableCell>
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
                          onClick={() => handleDelete(t.Tic_Cod as number)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {filtered.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center h-24">
                        No se encontraron tickets.
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
                {isEdit ? "Editar Ticket" : "Nuevo Ticket"}
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
                  Descripción *
                </label>
                <Input
                  required
                  value={String(formData.Tic_Des || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Tic_Des: e.target.value })
                  }
                  placeholder="Descripción del ticket"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Fecha de Creación
                </label>
                <Input
                  type="datetime-local"
                  value={String(formData.Tic_Fec_Cre || "")}
                  onChange={(e) =>
                    setFormData({ ...formData, Tic_Fec_Cre: e.target.value })
                  }
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Estado *
                </label>
                <Input
                  required
                  value={String(formData.Tic_Est || "A")}
                  onChange={(e) =>
                    setFormData({ ...formData, Tic_Est: e.target.value })
                  }
                  placeholder="A o I"
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
                  {isEdit ? "Guardar Cambios" : "Crear Ticket"}
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

export default function TransportePage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">
          Transporte de Carga
        </h2>
        <p className="text-muted-foreground mt-1">
          Gestión de viajes, vehículos y tickets.
        </p>
      </div>
      <Tabs defaultValue="viajes">
        <TabsList>
          <TabsTab value="viajes">Viajes</TabsTab>
          <TabsTab value="vehiculos">Vehículos</TabsTab>
          <TabsTab value="tickets">Tickets</TabsTab>
        </TabsList>
        <TabsPanel value="viajes">
          <ViajesTab />
        </TabsPanel>
        <TabsPanel value="vehiculos">
          <VehiculosTab />
        </TabsPanel>
        <TabsPanel value="tickets">
          <TicketsTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
