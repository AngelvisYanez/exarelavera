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
import { Plus, Search, Pencil, Loader2, X, AlertCircle } from "lucide-react";
import { clientesApi, proveedoresApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import type { Cliente, Proveedor } from "@/lib/api-types";

function ClientesTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => clientesApi.obtener(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<Cliente>>({
    Cli_Ced: "",
    Cli_Nom: "",
    Cli_Dir: "",
    Cli_Tel: "",
    Cli_Cel: "",
    Cli_Mail: "",
    Cli_Obs: "",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const filtered = Array.isArray(data?.data)
    ? data.data.filter(
        (c: Cliente) =>
          !search ||
          c.Cli_Nom?.toLowerCase().includes(search.toLowerCase()) ||
          c.Cli_Ced?.includes(search),
      )
    : [];

  const handleOpenCreate = () => {
    setFormData({
      Cli_Ced: "",
      Cli_Nom: "",
      Cli_Dir: "",
      Cli_Tel: "",
      Cli_Cel: "",
      Cli_Mail: "",
      Cli_Obs: "",
    });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (cliente: Cliente) => {
    setFormData(cliente);
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
        res = await clientesApi.modificar(payload);
      } else {
        res = await clientesApi.crear(payload);
      }

      if (res.status) {
        setShowModal(false);
        refetch();
      } else {
        setModalError(res.error || "Ocurrió un error al procesar la solicitud");
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
          <CardTitle>Clientes</CardTitle>
          <CardDescription>
            Listado y creación de clientes de la base de datos.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Cliente
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por nombre o cédula..."
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
                <TableHead>Cédula</TableHead>
                <TableHead>Nombre</TableHead>
                <TableHead>Dirección</TableHead>
                <TableHead>Teléfono</TableHead>
                <TableHead>Celular</TableHead>
                <TableHead>Email</TableHead>
                <TableHead className="text-center">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((c: Cliente, i: number) => (
                <TableRow key={c.Cli_Cod || i}>
                  <TableCell>{c.Cli_Ced}</TableCell>
                  <TableCell className="font-medium">{c.Cli_Nom}</TableCell>
                  <TableCell>{c.Cli_Dir || "-"}</TableCell>
                  <TableCell>{c.Cli_Tel || "-"}</TableCell>
                  <TableCell>{c.Cli_Cel || "-"}</TableCell>
                  <TableCell>{c.Cli_Mail || "-"}</TableCell>
                  <TableCell className="text-center">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleOpenEdit(c)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
              {filtered.length === 0 && (
                <TableRow>
                  <TableCell colSpan={7} className="text-center h-24">
                    No se encontraron clientes.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal interactivo de Clientes */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-lg animate-fade-in flex flex-col max-h-[90vh]">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-xl font-bold text-dark">
                {isEdit ? "Editar Cliente" : "Nuevo Cliente"}
              </h3>
              <button
                onClick={() => setShowModal(false)}
                className="text-muted-foreground hover:text-foreground"
              >
                <X className="h-6 w-6" />
              </button>
            </div>
            <form
              onSubmit={handleSubmit}
              className="overflow-y-auto flex-1 p-6 space-y-4"
            >
              {modalError && (
                <div className="bg-lighterror text-error p-3 rounded-md flex items-start gap-2 text-sm">
                  <AlertCircle className="h-5 w-5 shrink-0" />
                  <span>{modalError}</span>
                </div>
              )}
              <div className="grid grid-cols-2 gap-4">
                <div className="col-span-2 sm:col-span-1">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Cédula / RUC *
                  </label>
                  <Input
                    required
                    value={formData.Cli_Ced}
                    onChange={(e) =>
                      setFormData({ ...formData, Cli_Ced: e.target.value })
                    }
                    placeholder="Identificación"
                  />
                </div>
                <div className="col-span-2 sm:col-span-1">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Nombre Completo *
                  </label>
                  <Input
                    required
                    value={formData.Cli_Nom}
                    onChange={(e) =>
                      setFormData({ ...formData, Cli_Nom: e.target.value })
                    }
                    placeholder="Nombres / Razón Social"
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Dirección
                  </label>
                  <Input
                    value={formData.Cli_Dir || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Cli_Dir: e.target.value })
                    }
                    placeholder="Calle, ciudad..."
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Teléfono
                  </label>
                  <Input
                    value={formData.Cli_Tel || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Cli_Tel: e.target.value })
                    }
                    placeholder="Convencional"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Celular
                  </label>
                  <Input
                    value={formData.Cli_Cel || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Cli_Cel: e.target.value })
                    }
                    placeholder="Móvil"
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Email
                  </label>
                  <Input
                    type="email"
                    value={formData.Cli_Mail || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Cli_Mail: e.target.value })
                    }
                    placeholder="correo@ejemplo.com"
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Observación
                  </label>
                  <Input
                    value={formData.Cli_Obs || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Cli_Obs: e.target.value })
                    }
                    placeholder="Detalles opcionales"
                  />
                </div>
              </div>
              <div className="border-t pt-4 flex justify-end gap-3 bg-card sticky bottom-0">
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
                  {isEdit ? "Guardar Cambios" : "Crear Cliente"}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </Card>
  );
}

function ProveedoresTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => proveedoresApi.obtener(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<Proveedor>>({
    Prv_Ced: "",
    Prv_Nom: "",
    Prv_Dir: "",
    Prv_Tel: "",
    Prv_Cel: "",
    Prv_Mail: "",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  const filtered = Array.isArray(data?.data)
    ? data.data.filter(
        (p: Proveedor) =>
          !search ||
          p.Prv_Nom?.toLowerCase().includes(search.toLowerCase()) ||
          p.Prv_Ced?.includes(search),
      )
    : [];

  const handleOpenCreate = () => {
    setFormData({
      Prv_Ced: "",
      Prv_Nom: "",
      Prv_Dir: "",
      Prv_Tel: "",
      Prv_Cel: "",
      Prv_Mail: "",
    });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (proveedor: Proveedor) => {
    setFormData(proveedor);
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
        res = await proveedoresApi.modificar(payload);
      } else {
        res = await proveedoresApi.crear(payload);
      }

      if (res.status) {
        setShowModal(false);
        refetch();
      } else {
        setModalError(res.error || "Ocurrió un error al procesar la solicitud");
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
          <CardTitle>Proveedores</CardTitle>
          <CardDescription>
            Listado y creación de proveedores autorizados del ERP.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Proveedor
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar por nombre o cédula..."
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
                <TableHead>Cédula/RUC</TableHead>
                <TableHead>Nombre</TableHead>
                <TableHead>Dirección</TableHead>
                <TableHead>Teléfono</TableHead>
                <TableHead>Celular</TableHead>
                <TableHead>Email</TableHead>
                <TableHead className="text-center">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((p: Proveedor, i: number) => (
                <TableRow key={p.Prv_Cod || i}>
                  <TableCell>{p.Prv_Ced}</TableCell>
                  <TableCell className="font-medium">{p.Prv_Nom}</TableCell>
                  <TableCell>{p.Prv_Dir || "-"}</TableCell>
                  <TableCell>{p.Prv_Tel || "-"}</TableCell>
                  <TableCell>{p.Prv_Cel || "-"}</TableCell>
                  <TableCell>{p.Prv_Mail || "-"}</TableCell>
                  <TableCell className="text-center">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleOpenEdit(p)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
              {filtered.length === 0 && (
                <TableRow>
                  <TableCell colSpan={7} className="text-center h-24">
                    No se encontraron proveedores.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal interactivo de Proveedores */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-lg animate-fade-in flex flex-col max-h-[90vh]">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-xl font-bold text-dark">
                {isEdit ? "Editar Proveedor" : "Nuevo Proveedor"}
              </h3>
              <button
                onClick={() => setShowModal(false)}
                className="text-muted-foreground hover:text-foreground"
              >
                <X className="h-6 w-6" />
              </button>
            </div>
            <form
              onSubmit={handleSubmit}
              className="overflow-y-auto flex-1 p-6 space-y-4"
            >
              {modalError && (
                <div className="bg-lighterror text-error p-3 rounded-md flex items-start gap-2 text-sm">
                  <AlertCircle className="h-5 w-5 shrink-0" />
                  <span>{modalError}</span>
                </div>
              )}
              <div className="grid grid-cols-2 gap-4">
                <div className="col-span-2 sm:col-span-1">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    RUC / Cédula *
                  </label>
                  <Input
                    required
                    value={formData.Prv_Ced}
                    onChange={(e) =>
                      setFormData({ ...formData, Prv_Ced: e.target.value })
                    }
                    placeholder="Identificación"
                  />
                </div>
                <div className="col-span-2 sm:col-span-1">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Nombre Proveedor *
                  </label>
                  <Input
                    required
                    value={formData.Prv_Nom}
                    onChange={(e) =>
                      setFormData({ ...formData, Prv_Nom: e.target.value })
                    }
                    placeholder="Nombre o Razón Social"
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Dirección
                  </label>
                  <Input
                    value={formData.Prv_Dir || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Prv_Dir: e.target.value })
                    }
                    placeholder="Establecimiento principal"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Teléfono
                  </label>
                  <Input
                    value={formData.Prv_Tel || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Prv_Tel: e.target.value })
                    }
                    placeholder="Convencional"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Celular
                  </label>
                  <Input
                    value={formData.Prv_Cel || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Prv_Cel: e.target.value })
                    }
                    placeholder="Móvil"
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-muted-foreground mb-1">
                    Email
                  </label>
                  <Input
                    type="email"
                    value={formData.Prv_Mail || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Prv_Mail: e.target.value })
                    }
                    placeholder="correo@proveedor.com"
                  />
                </div>
              </div>
              <div className="border-t pt-4 flex justify-end gap-3 bg-card sticky bottom-0">
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
                  {isEdit ? "Guardar Cambios" : "Crear Proveedor"}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </Card>
  );
}

export default function ActoresPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">Actores</h2>
        <p className="text-muted-foreground mt-1">
          Gestión de clientes y proveedores.
        </p>
      </div>
      <Tabs defaultValue="clientes">
        <TabsList>
          <TabsTab value="clientes">Clientes</TabsTab>
          <TabsTab value="proveedores">Proveedores</TabsTab>
        </TabsList>
        <TabsPanel value="clientes">
          <ClientesTab />
        </TabsPanel>
        <TabsPanel value="proveedores">
          <ProveedoresTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
