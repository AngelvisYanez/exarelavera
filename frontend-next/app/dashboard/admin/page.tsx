"use client";

import { useState, useEffect } from "react";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
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
import { Label } from "@/components/ui/label";
import { DataTable } from "@/components/ui/data-table";
import { SriScraperColumns } from "./sri-scraper-columns";
import {
  Plus,
  Search,
  Pencil,
  Trash2,
  Loader2,
  X,
  AlertCircle,
  Users,
  Shield,
  Building2,
  Play,
  RefreshCw,
  Bot,
  Headphones,
  FolderOpen,
  Cpu,
} from "lucide-react";
import { useQuery } from "@/lib/use-query";
import { toast } from "sonner";
import { useConfirm } from "@/lib/hooks/use-confirm";
import { adminApi } from "@/lib/api";
import { directorioApi, procesosApi } from "@/lib/api/directorio";
import type { DirectorioModulo, ProcesoSistema } from "@/lib/api/directorio";
import { sriScraperApi, JobState } from "@/lib/api/sri-scraper";
import type { UsuarioRow, Perfil, Sucursal } from "@/lib/api-types";

function UsuariosTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => adminApi.usuarios(), { auto: true });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const [formData, setFormData] = useState({
    Usu_Cod: 0,
    Prs_Ced: "",
    Prs_Nom: "",
    Prs_Ape: "",
    Prs_Dir: "",
    Prs_Tel: "",
    Prs_Cel: "",
    Prs_Cor: "",
    Usu_Con: "",
    Per_Cod: 1,
    Suc_Cod: 1,
  });
  const { confirm, ConfirmDialog } = useConfirm();
  const { data: perfilesData } = useQuery(() => adminApi.perfiles(), { auto: true });
  const { data: sucursalesData } = useQuery(() => adminApi.sucursales(), { auto: true });

  const usuarios = (Array.isArray(data?.data) ? data!.data : []).filter(
    (u) =>
      !search ||
      (u.Prs_Nom || "").toLowerCase().includes(search.toLowerCase()) ||
      (u.Prs_Ape || "").toLowerCase().includes(search.toLowerCase())
  );

  const perfiles = Array.isArray(perfilesData?.data) ? perfilesData!.data : [];
  const sucursales = Array.isArray(sucursalesData?.data) ? sucursalesData!.data : [];

  const openCreate = () => {
    setIsEdit(false);
    setFormData({ Usu_Cod: 0, Prs_Ced: "", Prs_Nom: "", Prs_Ape: "", Prs_Dir: "", Prs_Tel: "", Prs_Cel: "", Prs_Cor: "", Usu_Con: "", Per_Cod: 1, Suc_Cod: 1 });
    setModalError(null);
    setShowModal(true);
  };

  const openEdit = (u: UsuarioRow) => {
    setIsEdit(true);
    setFormData({
      Usu_Cod: u.Usu_Cod,
      Prs_Ced: u.Usu_Ced || "",
      Prs_Nom: u.Prs_Nom || "",
      Prs_Ape: u.Prs_Ape || "",
      Prs_Dir: "",
      Prs_Tel: "",
      Prs_Cel: "",
      Prs_Cor: "",
      Usu_Con: "",
      Per_Cod: 1,
      Suc_Cod: 1,
    });
    setModalError(null);
    setShowModal(true);
  };

  const handleSubmit = async () => {
    if (!formData.Prs_Nom.trim() || !formData.Prs_Ape.trim()) {
      setModalError("Nombre y apellido son obligatorios.");
      return;
    }
    setModalLoading(true);
    setModalError(null);
    try {
      let res;
      if (isEdit) {
        res = await adminApi.modificarUsuario(formData as unknown as Record<string, unknown>);
      } else {
        res = await adminApi.crearUsuario(formData as unknown as Record<string, unknown>);
      }
      if (res.status) {
        toast.success(isEdit ? "Usuario modificado" : "Usuario creado");
        setShowModal(false);
        refetch();
      } else {
        setModalError(res.error || "Error al guardar el usuario");
      }
    } catch {
      setModalError("Error de red al guardar usuario.");
    } finally {
      setModalLoading(false);
    }
  };

  const handleDelete = async (u: UsuarioRow) => {
    const ok = await confirm("¿Eliminar este usuario?");
    if (!ok) return;
    try {
      const res = await adminApi.eliminarUsuario(u.Usu_Cod);
      if (res.status) {
        toast.success("Usuario eliminado");
        refetch();
      } else {
        toast.error(res.error || "Error al eliminar");
      }
    } catch {
      toast.error("Error de conexión");
    }
  };

  return (
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle className="text-lg">Listado de Usuarios</CardTitle>
        <Button size="sm" onClick={openCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Usuario
        </Button>
      </CardHeader>
      <CardContent>
        <div className="mb-4 relative max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por nombre o apellido..."
            className="pl-9"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <div className="overflow-x-auto">
          <div className="min-w-[700px]">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Código</TableHead>
                  <TableHead>Cédula</TableHead>
                  <TableHead>Nombre</TableHead>
                  <TableHead>Apellido</TableHead>
                  <TableHead>Perfil</TableHead>
                  <TableHead className="text-center">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center py-6">
                      <Loader2 className="h-5 w-5 animate-spin mx-auto" />
                    </TableCell>
                  </TableRow>
                ) : usuarios.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center text-muted-foreground py-6">
                      No hay usuarios registrados
                    </TableCell>
                  </TableRow>
                ) : (
                  usuarios.map((u) => (
                    <TableRow key={u.Usu_Cod}>
                      <TableCell className="font-medium">{u.Usu_Cod}</TableCell>
                      <TableCell>{u.Usu_Ced}</TableCell>
                      <TableCell>{u.Prs_Nom}</TableCell>
                      <TableCell>{u.Prs_Ape}</TableCell>
                      <TableCell>{u.Per_Des}</TableCell>
                      <TableCell className="text-center">
                        <div className="flex items-center justify-center gap-1">
                          <Button variant="ghost" size="icon-sm" onClick={() => openEdit(u)} title="Editar">
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon-sm" onClick={() => handleDelete(u)} title="Eliminar">
                            <Trash2 className="h-4 w-4 text-error" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </div>
      </CardContent>

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="bg-card rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-bold">{isEdit ? "Editar Usuario" : "Nuevo Usuario"}</h3>
              <Button variant="ghost" size="icon-xs" onClick={() => setShowModal(false)}>
                <X className="h-4 w-4" />
              </Button>
            </div>
            {modalError && (
              <div className="flex items-center gap-2 p-3 bg-lighterror border border-error rounded-lg text-error text-sm">
                <AlertCircle className="h-4 w-4 flex-shrink-0" />
                <span>{modalError}</span>
              </div>
            )}
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Cédula *</label>
                <Input value={formData.Prs_Ced} onChange={(e) => setFormData({ ...formData, Prs_Ced: e.target.value })} placeholder="Cédula" />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Nombre *</label>
                <Input value={formData.Prs_Nom} onChange={(e) => setFormData({ ...formData, Prs_Nom: e.target.value })} placeholder="Nombre" />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Apellido *</label>
                <Input value={formData.Prs_Ape} onChange={(e) => setFormData({ ...formData, Prs_Ape: e.target.value })} placeholder="Apellido" />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Teléfono</label>
                <Input value={formData.Prs_Tel} onChange={(e) => setFormData({ ...formData, Prs_Tel: e.target.value })} placeholder="Teléfono" />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Celular</label>
                <Input value={formData.Prs_Cel} onChange={(e) => setFormData({ ...formData, Prs_Cel: e.target.value })} placeholder="Celular" />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Email</label>
                <Input type="email" value={formData.Prs_Cor} onChange={(e) => setFormData({ ...formData, Prs_Cor: e.target.value })} placeholder="Email" />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Perfil</label>
                <select value={formData.Per_Cod} onChange={(e) => setFormData({ ...formData, Per_Cod: parseInt(e.target.value) })} className="w-full px-3 py-2 border border-input bg-background rounded-md text-sm">
                  {perfiles.map((p) => (
                    <option key={p.Per_Cod} value={p.Per_Cod}>{p.Per_Des}</option>
                  ))}
                </select>
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Sucursal</label>
                <select value={formData.Suc_Cod} onChange={(e) => setFormData({ ...formData, Suc_Cod: parseInt(e.target.value) })} className="w-full px-3 py-2 border border-input bg-background rounded-md text-sm">
                  {sucursales.map((s) => (
                    <option key={s.Suc_Cod} value={s.Suc_Cod}>{s.Suc_Des}</option>
                  ))}
                </select>
              </div>
              {!isEdit && (
                <div className="col-span-2 space-y-1.5">
                  <label className="text-sm font-medium">Contraseña *</label>
                  <Input type="password" value={formData.Usu_Con} onChange={(e) => setFormData({ ...formData, Usu_Con: e.target.value })} placeholder="Contraseña" />
                </div>
              )}
            </div>
            <div className="flex justify-end gap-2 pt-2 border-t">
              <Button variant="outline" onClick={() => setShowModal(false)}>Cancelar</Button>
              <Button onClick={handleSubmit} disabled={modalLoading}>
                {modalLoading && <Loader2 className="h-4 w-4 mr-1 animate-spin" />}
                {isEdit ? "Actualizar" : "Crear"}
              </Button>
            </div>
          </div>
        </div>
      )}
    </Card>
    {ConfirmDialog}
    </>
  );
}

function PerfilesTab() {
  const [search, setSearch] = useState("");
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const [formData, setFormData] = useState({ Per_Cod: 0, Per_Des: "" });
  const { confirm, ConfirmDialog } = useConfirm();

  const { data, loading, refetch } = useQuery(() => adminApi.perfiles(), { auto: true });

  const perfiles = (Array.isArray(data?.data) ? data!.data : []).filter(
    (p) => !search || (p.Per_Des || "").toLowerCase().includes(search.toLowerCase())
  );

  const openCreate = () => {
    setIsEdit(false);
    setFormData({ Per_Cod: 0, Per_Des: "" });
    setModalError(null);
    setShowModal(true);
  };

  const openEdit = (p: Perfil) => {
    setIsEdit(true);
    setFormData({ Per_Cod: p.Per_Cod, Per_Des: p.Per_Des });
    setModalError(null);
    setShowModal(true);
  };

  const handleSubmit = async () => {
    if (!formData.Per_Des.trim()) {
      setModalError("La descripción es obligatoria.");
      return;
    }
    setModalLoading(true);
    setModalError(null);
    try {
      let res;
      if (isEdit) {
        res = await adminApi.modificarPerfil(formData as unknown as Record<string, unknown>);
      } else {
        res = await adminApi.crearPerfil(formData as unknown as Record<string, unknown>);
      }
      if (res.status) {
        toast.success(isEdit ? "Perfil modificado" : "Perfil creado");
        setShowModal(false);
        refetch();
      } else {
        setModalError(res.error || "Error al guardar el perfil");
      }
    } catch {
      setModalError("Error de red al guardar perfil.");
    } finally {
      setModalLoading(false);
    }
  };

  const handleDelete = async (p: Perfil) => {
    const ok = await confirm("¿Eliminar este perfil?");
    if (!ok) return;
    try {
      const res = await adminApi.eliminarPerfil(p.Per_Cod);
      if (res.status) {
        toast.success("Perfil eliminado");
        refetch();
      } else {
        toast.error(res.error || "Error al eliminar");
      }
    } catch {
      toast.error("Error de conexión");
    }
  };

  return (
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle className="text-lg">Perfiles</CardTitle>
        <Button size="sm" onClick={openCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Perfil
        </Button>
      </CardHeader>
      <CardContent>
        <div className="mb-4 relative max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input placeholder="Buscar por descripción..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
        </div>
        <div className="overflow-x-auto">
          <div className="min-w-[500px]">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Código</TableHead>
                  <TableHead>Descripción</TableHead>
                  <TableHead className="text-center">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  <TableRow>
                    <TableCell colSpan={3} className="text-center py-6">
                      <Loader2 className="h-5 w-5 animate-spin mx-auto" />
                    </TableCell>
                  </TableRow>
                ) : perfiles.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={3} className="text-center text-muted-foreground py-6">
                      No hay perfiles registrados
                    </TableCell>
                  </TableRow>
                ) : (
                  perfiles.map((p) => (
                    <TableRow key={p.Per_Cod}>
                      <TableCell className="font-medium">{p.Per_Cod}</TableCell>
                      <TableCell>{p.Per_Des}</TableCell>
                      <TableCell className="text-center">
                        <div className="flex items-center justify-center gap-1">
                          <Button variant="ghost" size="icon-sm" onClick={() => openEdit(p)} title="Editar">
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon-sm" onClick={() => handleDelete(p)} title="Eliminar">
                            <Trash2 className="h-4 w-4 text-error" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </div>
      </CardContent>

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="bg-card rounded-xl shadow-xl w-full max-w-md mx-4 p-6 space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-bold">{isEdit ? "Editar Perfil" : "Nuevo Perfil"}</h3>
              <Button variant="ghost" size="icon-xs" onClick={() => setShowModal(false)}>
                <X className="h-4 w-4" />
              </Button>
            </div>
            {modalError && (
              <div className="flex items-center gap-2 p-3 bg-lighterror border border-error rounded-lg text-error text-sm">
                <AlertCircle className="h-4 w-4 flex-shrink-0" />
                <span>{modalError}</span>
              </div>
            )}
            <div className="space-y-3">
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Descripción *</label>
                <Input value={formData.Per_Des} onChange={(e) => setFormData({ ...formData, Per_Des: e.target.value })} placeholder="Nombre del perfil" />
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={() => setShowModal(false)}>Cancelar</Button>
              <Button onClick={handleSubmit} disabled={modalLoading}>
                {modalLoading && <Loader2 className="h-4 w-4 mr-1 animate-spin" />}
                {isEdit ? "Actualizar" : "Crear"}
              </Button>
            </div>
          </div>
        </div>
      )}
    </Card>
    {ConfirmDialog}
    </>
  );
}

function SucursalesTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => adminApi.sucursales(), { auto: true });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const [formData, setFormData] = useState({ Suc_Cod: 0, Suc_Des: "" });
  const { confirm, ConfirmDialog } = useConfirm();

  const sucursales = (Array.isArray(data?.data) ? data!.data : []).filter(
    (s) => !search || (s.Suc_Des || "").toLowerCase().includes(search.toLowerCase())
  );

  const openCreate = () => {
    setIsEdit(false);
    setFormData({ Suc_Cod: 0, Suc_Des: "" });
    setModalError(null);
    setShowModal(true);
  };

  const openEdit = (s: Sucursal) => {
    setIsEdit(true);
    setFormData({ Suc_Cod: s.Suc_Cod, Suc_Des: s.Suc_Des });
    setModalError(null);
    setShowModal(true);
  };

  const handleSubmit = async () => {
    if (!formData.Suc_Des.trim()) {
      setModalError("El nombre es obligatorio.");
      return;
    }
    setModalLoading(true);
    setModalError(null);
    try {
      let res;
      if (isEdit) {
        res = await adminApi.modificarSucursal(formData as unknown as Record<string, unknown>);
      } else {
        res = await adminApi.crearSucursal(formData as unknown as Record<string, unknown>);
      }
      if (res.status) {
        toast.success(isEdit ? "Sucursal modificada" : "Sucursal creada");
        setShowModal(false);
        refetch();
      } else {
        setModalError(res.error || "Error al guardar la sucursal");
      }
    } catch {
      setModalError("Error de red al guardar sucursal.");
    } finally {
      setModalLoading(false);
    }
  };

  const handleDelete = async (s: Sucursal) => {
    const ok = await confirm("¿Eliminar esta sucursal?");
    if (!ok) return;
    try {
      const res = await adminApi.eliminarSucursal(s.Suc_Cod);
      if (res.status) {
        toast.success("Sucursal eliminada");
        refetch();
      } else {
        toast.error(res.error || "Error al eliminar");
      }
    } catch {
      toast.error("Error de conexión");
    }
  };

  return (
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle className="text-lg">Sucursales</CardTitle>
        <Button size="sm" onClick={openCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Sucursal
        </Button>
      </CardHeader>
      <CardContent>
        <div className="mb-4 relative max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input placeholder="Buscar por nombre..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
        </div>
        <div className="overflow-x-auto">
          <div className="min-w-[500px]">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Código</TableHead>
                  <TableHead>Nombre</TableHead>
                  <TableHead>Empresa</TableHead>
                  <TableHead className="text-center">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  <TableRow>
                    <TableCell colSpan={4} className="text-center py-6">
                      <Loader2 className="h-5 w-5 animate-spin mx-auto" />
                    </TableCell>
                  </TableRow>
                ) : sucursales.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={4} className="text-center text-muted-foreground py-6">
                      No hay sucursales registradas
                    </TableCell>
                  </TableRow>
                ) : (
                  sucursales.map((s) => (
                    <TableRow key={s.Suc_Cod}>
                      <TableCell className="font-medium">{s.Suc_Cod}</TableCell>
                      <TableCell>{s.Suc_Des}</TableCell>
                      <TableCell>{s.Emp_Cod}</TableCell>
                      <TableCell className="text-center">
                        <div className="flex items-center justify-center gap-1">
                          <Button variant="ghost" size="icon-sm" onClick={() => openEdit(s)} title="Editar">
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon-sm" onClick={() => handleDelete(s)} title="Eliminar">
                            <Trash2 className="h-4 w-4 text-error" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </div>
      </CardContent>

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="bg-card rounded-xl shadow-xl w-full max-w-md mx-4 p-6 space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-bold">{isEdit ? "Editar Sucursal" : "Nueva Sucursal"}</h3>
              <Button variant="ghost" size="icon-xs" onClick={() => setShowModal(false)}>
                <X className="h-4 w-4" />
              </Button>
            </div>
            {modalError && (
              <div className="flex items-center gap-2 p-3 bg-lighterror border border-error rounded-lg text-error text-sm">
                <AlertCircle className="h-4 w-4 flex-shrink-0" />
                <span>{modalError}</span>
              </div>
            )}
            <div className="space-y-3">
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Nombre *</label>
                <Input value={formData.Suc_Des} onChange={(e) => setFormData({ ...formData, Suc_Des: e.target.value })} placeholder="Nombre de la sucursal" />
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={() => setShowModal(false)}>Cancelar</Button>
              <Button onClick={handleSubmit} disabled={modalLoading}>
                {modalLoading && <Loader2 className="h-4 w-4 mr-1 animate-spin" />}
                {isEdit ? "Actualizar" : "Crear"}
              </Button>
            </div>
          </div>
        </div>
      )}
    </Card>
    {ConfirmDialog}
    </>
  );
}

function TicketsTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => adminApi.tickets(), { auto: true });
  const [showModal, setShowModal] = useState(false);
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const [formData, setFormData] = useState({ Tic_Tit: "", Tic_Des: "" });
  const { confirm, ConfirmDialog } = useConfirm();

  const tickets = (Array.isArray(data?.data) ? data!.data : []).filter(
    (t: any) => !search || (t.Tic_Tit || "").toLowerCase().includes(search.toLowerCase()) || (t.Tic_Des || "").toLowerCase().includes(search.toLowerCase())
  );

  const handleSubmit = async () => {
    if (!formData.Tic_Tit.trim()) {
      setModalError("El título es obligatorio.");
      return;
    }
    setModalLoading(true);
    setModalError(null);
    try {
      const res = await adminApi.crearTicket(formData);
      if (res.status) {
        toast.success("Ticket creado");
        setShowModal(false);
        setFormData({ Tic_Tit: "", Tic_Des: "" });
        refetch();
      } else {
        setModalError(res.error || "Error al crear el ticket");
      }
    } catch {
      setModalError("Error de red al crear ticket.");
    } finally {
      setModalLoading(false);
    }
  };

  const handleClose = async (ticket: any) => {
    const ok = await confirm("¿Cerrar este ticket?");
    if (!ok) return;
    try {
      const res = await adminApi.cerrarTicket(ticket.Tic_Cod);
      if (res.status) {
        toast.success("Ticket cerrado");
        refetch();
      } else {
        toast.error(res.error || "Error al cerrar");
      }
    } catch {
      toast.error("Error de conexión");
    }
  };

  const getEstadoBadge = (est: string) => {
    if (est === "A") return <span className="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Abierto</span>;
    if (est === "C") return <span className="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Cerrado</span>;
    return <span className="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">{est}</span>;
  };

  return (
    <>
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle className="text-lg">Tickets de Soporte</CardTitle>
        <Button size="sm" onClick={() => { setFormData({ Tic_Tit: "", Tic_Des: "" }); setModalError(null); setShowModal(true); }}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Ticket
        </Button>
      </CardHeader>
      <CardContent>
        <div className="mb-4 relative max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input placeholder="Buscar ticket..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
        </div>
        <div className="overflow-x-auto">
          <div className="min-w-[600px]">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Código</TableHead>
                  <TableHead>Título</TableHead>
                  <TableHead>Descripción</TableHead>
                  <TableHead>Fecha</TableHead>
                  <TableHead>Estado</TableHead>
                  <TableHead className="text-center">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center py-6">
                      <Loader2 className="h-5 w-5 animate-spin mx-auto" />
                    </TableCell>
                  </TableRow>
                ) : tickets.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center text-muted-foreground py-6">
                      No hay tickets registrados
                    </TableCell>
                  </TableRow>
                ) : (
                  tickets.map((t: any) => (
                    <TableRow key={t.Tic_Cod}>
                      <TableCell className="font-medium">{t.Tic_Cod}</TableCell>
                      <TableCell>{t.Tic_Tit || "-"}</TableCell>
                      <TableCell className="max-w-[200px] truncate">{t.Tic_Des || "-"}</TableCell>
                      <TableCell>{t.Tic_Fec || "-"}</TableCell>
                      <TableCell>{getEstadoBadge(t.Tic_Est)}</TableCell>
                      <TableCell className="text-center">
                        {t.Tic_Est === "A" && (
                          <Button variant="ghost" size="sm" onClick={() => handleClose(t)}>
                            Cerrar
                          </Button>
                        )}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </div>
      </CardContent>

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="bg-card rounded-xl shadow-xl w-full max-w-md mx-4 p-6 space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-bold">Nuevo Ticket</h3>
              <Button variant="ghost" size="icon-xs" onClick={() => setShowModal(false)}>
                <X className="h-4 w-4" />
              </Button>
            </div>
            {modalError && (
              <div className="flex items-center gap-2 p-3 bg-lighterror border border-error rounded-lg text-error text-sm">
                <AlertCircle className="h-4 w-4 flex-shrink-0" />
                <span>{modalError}</span>
              </div>
            )}
            <div className="space-y-3">
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Título *</label>
                <Input value={formData.Tic_Tit} onChange={(e) => setFormData({ ...formData, Tic_Tit: e.target.value })} placeholder="Asunto del ticket" />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Descripción</label>
                <textarea
                  className="w-full px-3 py-2 border border-input bg-background rounded-md text-sm min-h-[80px]"
                  value={formData.Tic_Des}
                  onChange={(e) => setFormData({ ...formData, Tic_Des: e.target.value })}
                  placeholder="Detalle del problema o solicitud"
                />
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={() => setShowModal(false)}>Cancelar</Button>
              <Button onClick={handleSubmit} disabled={modalLoading}>
                {modalLoading && <Loader2 className="h-4 w-4 mr-1 animate-spin" />}
                Crear Ticket
              </Button>
            </div>
          </div>
        </div>
      )}
    </Card>
    {ConfirmDialog}
    </>
  );
}

function DirectorioTab() {
  const [search, setSearch] = useState("");
  const [activeSubTab, setActiveSubTab] = useState("modulos");
  const { data: modulosData, loading: loadingModulos, refetch: refetchModulos } = useQuery(() => directorioApi.obtener(), { auto: true });
  const { data: procesosData, loading: loadingProcesos, refetch: refetchProcesos } = useQuery(() => procesosApi.obtener(), { auto: true });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [editingType, setEditingType] = useState<"modulo" | "proceso">("modulo");
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const [formDataModulo, setFormDataModulo] = useState({ Dir_Cod: 0, Dir_Nom: "", Dir_Rut: "", Dir_Tip: "modulo", Dir_Des: "", Dir_Ver: "", Dir_Aut: "N" });
  const [formDataProceso, setFormDataProceso] = useState({ Pcs_Cod: 0, Pcs_Lin: "", Pcs_Det: "", Pcs_Tip: "api" });
  const { confirm, ConfirmDialog } = useConfirm();

  const modulos = (Array.isArray(modulosData?.data) ? modulosData!.data : []).filter(
    (m) => !search || (m.Dir_Nom || "").toLowerCase().includes(search.toLowerCase()) || (m.Dir_Rut || "").toLowerCase().includes(search.toLowerCase())
  );
  const procesos = (Array.isArray(procesosData?.data) ? procesosData!.data : []).filter(
    (p) => !search || (p.Pcs_Lin || "").toLowerCase().includes(search.toLowerCase()) || (p.Pcs_Det || "").toLowerCase().includes(search.toLowerCase())
  );

  const openCreateModulo = () => {
    setIsEdit(false);
    setEditingType("modulo");
    setFormDataModulo({ Dir_Cod: 0, Dir_Nom: "", Dir_Rut: "", Dir_Tip: "modulo", Dir_Des: "", Dir_Ver: "", Dir_Aut: "N" });
    setModalError(null);
    setShowModal(true);
  };

  const openEditModulo = (m: DirectorioModulo) => {
    setIsEdit(true);
    setEditingType("modulo");
    setFormDataModulo({ Dir_Cod: m.Dir_Cod, Dir_Nom: m.Dir_Nom, Dir_Rut: m.Dir_Rut, Dir_Tip: m.Dir_Tip, Dir_Des: m.Dir_Des || "", Dir_Ver: m.Dir_Ver || "", Dir_Aut: m.Dir_Aut || "N" });
    setModalError(null);
    setShowModal(true);
  };

  const openCreateProceso = () => {
    setIsEdit(false);
    setEditingType("proceso");
    setFormDataProceso({ Pcs_Cod: 0, Pcs_Lin: "", Pcs_Det: "", Pcs_Tip: "api" });
    setModalError(null);
    setShowModal(true);
  };

  const openEditProceso = (p: ProcesoSistema) => {
    setIsEdit(true);
    setEditingType("proceso");
    setFormDataProceso({ Pcs_Cod: p.Pcs_Cod, Pcs_Lin: p.Pcs_Lin, Pcs_Det: p.Pcs_Det || "", Pcs_Tip: p.Pcs_Tip || "api" });
    setModalError(null);
    setShowModal(true);
  };

  const handleSubmitModulo = async () => {
    if (!formDataModulo.Dir_Nom.trim() || !formDataModulo.Dir_Rut.trim()) {
      setModalError("Nombre y ruta son obligatorios.");
      return;
    }
    setModalLoading(true);
    setModalError(null);
    try {
      let res;
      if (isEdit) {
        res = await directorioApi.modificar(formDataModulo as unknown as Record<string, unknown>);
      } else {
        res = await directorioApi.crear(formDataModulo as unknown as Record<string, unknown>);
      }
      if (res.status) {
        toast.success(isEdit ? "Módulo modificado" : "Módulo registrado");
        setShowModal(false);
        refetchModulos();
      } else {
        setModalError(res.error || "Error al guardar");
      }
    } catch {
      setModalError("Error de red");
    } finally {
      setModalLoading(false);
    }
  };

  const handleSubmitProceso = async () => {
    if (!formDataProceso.Pcs_Lin.trim()) {
      setModalError("El nombre del proceso es obligatorio.");
      return;
    }
    setModalLoading(true);
    setModalError(null);
    try {
      let res;
      if (isEdit) {
        res = await procesosApi.modificar(formDataProceso as unknown as Record<string, unknown>);
      } else {
        res = await procesosApi.crear(formDataProceso as unknown as Record<string, unknown>);
      }
      if (res.status) {
        toast.success(isEdit ? "Proceso modificado" : "Proceso registrado");
        setShowModal(false);
        refetchProcesos();
      } else {
        setModalError(res.error || "Error al guardar");
      }
    } catch {
      setModalError("Error de red");
    } finally {
      setModalLoading(false);
    }
  };

  const handleDeleteModulo = async (m: DirectorioModulo) => {
    const ok = await confirm("¿Eliminar este módulo del directorio?");
    if (!ok) return;
    try {
      const res = await directorioApi.eliminar(m.Dir_Cod);
      if (res.status) {
        toast.success("Módulo eliminado");
        refetchModulos();
      } else {
        toast.error(res.error || "Error al eliminar");
      }
    } catch {
      toast.error("Error de conexión");
    }
  };

  const handleDeleteProceso = async (p: ProcesoSistema) => {
    const ok = await confirm("¿Eliminar este proceso?");
    if (!ok) return;
    try {
      const res = await procesosApi.eliminar(p.Pcs_Cod);
      if (res.status) {
        toast.success("Proceso eliminado");
        refetchProcesos();
      } else {
        toast.error(res.error || "Error al eliminar");
      }
    } catch {
      toast.error("Error de conexión");
    }
  };

  const getTipoBadge = (tip: string) => {
    const map: Record<string, string> = {
      modulo: "bg-blue-100 text-blue-700",
      api: "bg-green-100 text-green-700",
      script: "bg-yellow-100 text-yellow-700",
      servicio: "bg-purple-100 text-purple-700",
    };
    return <span className={`px-2 py-0.5 text-xs font-medium rounded-full ${map[tip] || "bg-gray-100 text-gray-600"}`}>{tip}</span>;
  };

  return (
    <>
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="flex items-center gap-2 text-lg">
            <FolderOpen className="h-5 w-5" /> Directorio de Módulos
          </CardTitle>
          <Button size="sm" onClick={openCreateModulo}>
            <Plus className="h-4 w-4 mr-1" /> Nuevo
          </Button>
        </CardHeader>
        <CardContent>
          <div className="mb-3 relative max-w-sm">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input placeholder="Buscar módulo..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
          </div>
          <div className="overflow-x-auto">
            <div className="min-w-[400px]">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Nombre</TableHead>
                    <TableHead>Ruta</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {loadingModulos ? (
                    <TableRow><TableCell colSpan={4} className="text-center py-6"><Loader2 className="h-5 w-5 animate-spin mx-auto" /></TableCell></TableRow>
                  ) : modulos.length === 0 ? (
                    <TableRow><TableCell colSpan={4} className="text-center text-muted-foreground py-6">No hay módulos registrados</TableCell></TableRow>
                  ) : (
                    modulos.map((m) => (
                      <TableRow key={m.Dir_Cod}>
                        <TableCell className="font-medium">{m.Dir_Nom}</TableCell>
                        <TableCell className="text-xs text-muted-foreground font-mono">{m.Dir_Rut}</TableCell>
                        <TableCell>{getTipoBadge(m.Dir_Tip)}</TableCell>
                        <TableCell className="text-right">
                          <div className="flex items-center justify-end gap-1">
                            <Button variant="ghost" size="icon-sm" onClick={() => openEditModulo(m)} title="Editar"><Pencil className="h-4 w-4" /></Button>
                            <Button variant="ghost" size="icon-sm" onClick={() => handleDeleteModulo(m)} title="Eliminar"><Trash2 className="h-4 w-4 text-error" /></Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="flex items-center gap-2 text-lg">
            <Cpu className="h-5 w-5" /> Procesos del Sistema
          </CardTitle>
          <Button size="sm" onClick={openCreateProceso}>
            <Plus className="h-4 w-4 mr-1" /> Nuevo
          </Button>
        </CardHeader>
        <CardContent>
          <div className="mb-3 relative max-w-sm">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input placeholder="Buscar proceso..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
          </div>
          <div className="overflow-x-auto">
            <div className="min-w-[400px]">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Código</TableHead>
                    <TableHead>Nombre</TableHead>
                    <TableHead>Detalle</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {loadingProcesos ? (
                    <TableRow><TableCell colSpan={5} className="text-center py-6"><Loader2 className="h-5 w-5 animate-spin mx-auto" /></TableCell></TableRow>
                  ) : procesos.length === 0 ? (
                    <TableRow><TableCell colSpan={5} className="text-center text-muted-foreground py-6">No hay procesos registrados</TableCell></TableRow>
                  ) : (
                    procesos.map((p) => (
                      <TableRow key={p.Pcs_Cod}>
                        <TableCell className="font-mono text-xs">{p.Pcs_Cod}</TableCell>
                        <TableCell className="font-medium">{p.Pcs_Lin}</TableCell>
                        <TableCell className="text-xs text-muted-foreground">{p.Pcs_Det || "-"}</TableCell>
                        <TableCell>{getTipoBadge(p.Pcs_Tip)}</TableCell>
                        <TableCell className="text-right">
                          <div className="flex items-center justify-end gap-1">
                            <Button variant="ghost" size="icon-sm" onClick={() => openEditProceso(p)} title="Editar"><Pencil className="h-4 w-4" /></Button>
                            <Button variant="ghost" size="icon-sm" onClick={() => handleDeleteProceso(p)} title="Eliminar"><Trash2 className="h-4 w-4 text-error" /></Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    {showModal && (
      <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div className="bg-card rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 space-y-4 max-h-[90vh] overflow-y-auto">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-bold">
              {isEdit ? "Editar" : "Nuevo"} {editingType === "modulo" ? "Módulo" : "Proceso"}
            </h3>
            <Button variant="ghost" size="icon-xs" onClick={() => setShowModal(false)}>
              <X className="h-4 w-4" />
            </Button>
          </div>
          {modalError && (
            <div className="flex items-center gap-2 p-3 bg-lighterror border border-error rounded-lg text-error text-sm">
              <AlertCircle className="h-4 w-4 flex-shrink-0" />
              <span>{modalError}</span>
            </div>
          )}
          {editingType === "modulo" ? (
            <div className="space-y-3">
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Nombre del Módulo *</label>
                  <Input value={formDataModulo.Dir_Nom} onChange={(e) => setFormDataModulo({ ...formDataModulo, Dir_Nom: e.target.value })} placeholder="Ej: facturacion" />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Tipo</label>
                  <select value={formDataModulo.Dir_Tip} onChange={(e) => setFormDataModulo({ ...formDataModulo, Dir_Tip: e.target.value })} className="w-full px-3 py-2 border border-input bg-background rounded-md text-sm">
                    <option value="modulo">Módulo</option>
                    <option value="api">API</option>
                    <option value="script">Script</option>
                    <option value="servicio">Servicio</option>
                  </select>
                </div>
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Ruta / Directorio *</label>
                <Input value={formDataModulo.Dir_Rut} onChange={(e) => setFormDataModulo({ ...formDataModulo, Dir_Rut: e.target.value })} placeholder="Ej: /v1/facturacion o /api/v1/clientes" className="font-mono text-sm" />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Descripción</label>
                <Input value={formDataModulo.Dir_Des} onChange={(e) => setFormDataModulo({ ...formDataModulo, Dir_Des: e.target.value })} placeholder="Descripción del módulo" />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Versión</label>
                  <Input value={formDataModulo.Dir_Ver} onChange={(e) => setFormDataModulo({ ...formDataModulo, Dir_Ver: e.target.value })} placeholder="Ej: 1.0.0" />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Requiere Auth</label>
                  <select value={formDataModulo.Dir_Aut} onChange={(e) => setFormDataModulo({ ...formDataModulo, Dir_Aut: e.target.value })} className="w-full px-3 py-2 border border-input bg-background rounded-md text-sm">
                    <option value="S">Sí</option>
                    <option value="N">No</option>
                  </select>
                </div>
              </div>
            </div>
          ) : (
            <div className="space-y-3">
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Nombre / Línea *</label>
                  <Input value={formDataProceso.Pcs_Lin} onChange={(e) => setFormDataProceso({ ...formDataProceso, Pcs_Lin: e.target.value })} placeholder="Ej: login, facturar, etc." />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Tipo</label>
                  <select value={formDataProceso.Pcs_Tip} onChange={(e) => setFormDataProceso({ ...formDataProceso, Pcs_Tip: e.target.value })} className="w-full px-3 py-2 border border-input bg-background rounded-md text-sm">
                    <option value="api">API</option>
                    <option value="script">Script</option>
                    <option value="evento">Evento</option>
                    <option value="manual">Manual</option>
                  </select>
                </div>
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Detalle / Descripción</label>
                <Input value={formDataProceso.Pcs_Det} onChange={(e) => setFormDataProceso({ ...formDataProceso, Pcs_Det: e.target.value })} placeholder="Descripción del proceso" />
              </div>
            </div>
          )}
          <div className="flex justify-end gap-2 pt-2 border-t">
            <Button variant="outline" onClick={() => setShowModal(false)}>Cancelar</Button>
            <Button onClick={editingType === "modulo" ? handleSubmitModulo : handleSubmitProceso} disabled={modalLoading}>
              {modalLoading && <Loader2 className="h-4 w-4 mr-1 animate-spin" />}
              {isEdit ? "Actualizar" : "Crear"}
            </Button>
          </div>
        </div>
      </div>
    )}
    {ConfirmDialog}
    </>
  );
}

function SriScraperTab() {
  const [jobs, setJobs] = useState<JobState[]>([]);
  const [loading, setLoading] = useState(true);
  const [creating, setCreating] = useState(false);

  const [formData, setFormData] = useState({
    ruc: "",
    clave: "",
    fecha_desde: "",
    fecha_hasta: "",
  });

  const fetchJobs = async () => {
    try {
      const res = await sriScraperApi.getJobs();
      if (res.success) {
        setJobs(res.jobs || []);
      }
    } catch (error: any) {
      toast.error(error.message || "Error al cargar trabajos del scraper");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchJobs();
    const interval = setInterval(fetchJobs, 5000);
    return () => clearInterval(interval);
  }, []);

  const handleStartJob = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.ruc || !formData.clave || !formData.fecha_desde || !formData.fecha_hasta) {
      toast.error("Por favor, completa todos los campos.");
      return;
    }
    setCreating(true);
    try {
      const res = await sriScraperApi.createJob(formData);
      if (res.success) {
        toast.success("Trabajo de descarga iniciado");
        setFormData({ ...formData, clave: "" });
        fetchJobs();
      } else {
        toast.error(res.error || "Error al iniciar el trabajo");
      }
    } catch (error: any) {
      toast.error(error.message || "Error al iniciar el trabajo");
    } finally {
      setCreating(false);
    }
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
      <Card className="md:col-span-1">
        <CardHeader>
          <CardTitle>Nueva Descarga</CardTitle>
          <CardDescription>Configura los parámetros para iniciar la descarga masiva.</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleStartJob} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="ruc">RUC</Label>
              <Input id="ruc" placeholder="Ej: 1790000000001" value={formData.ruc} onChange={(e) => setFormData({ ...formData, ruc: e.target.value })} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="clave">Clave SRI</Label>
              <Input id="clave" type="password" placeholder="Clave de acceso al SRI" value={formData.clave} onChange={(e) => setFormData({ ...formData, clave: e.target.value })} required />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="fecha_desde">Desde</Label>
                <Input id="fecha_desde" type="date" value={formData.fecha_desde} onChange={(e) => setFormData({ ...formData, fecha_desde: e.target.value })} required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="fecha_hasta">Hasta</Label>
                <Input id="fecha_hasta" type="date" value={formData.fecha_hasta} onChange={(e) => setFormData({ ...formData, fecha_hasta: e.target.value })} required />
              </div>
            </div>
            <Button type="submit" className="w-full" disabled={creating}>
              {creating ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Play className="mr-2 h-4 w-4" />}
              {creating ? "Iniciando..." : "Iniciar Descarga"}
            </Button>
          </form>
        </CardContent>
      </Card>

      <Card className="md:col-span-2">
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>Trabajos Recientes</CardTitle>
            <CardDescription>Monitorea el progreso de las descargas masivas.</CardDescription>
          </div>
          <Button variant="outline" size="icon" onClick={fetchJobs} disabled={loading}>
            <RefreshCw className={`h-4 w-4 ${loading ? "animate-spin" : ""}`} />
          </Button>
        </CardHeader>
        <CardContent className="pt-6">
          {loading && jobs.length === 0 ? (
            <div className="flex justify-center p-8">
              <Loader2 className="h-8 w-8 animate-spin" />
            </div>
          ) : (
            <DataTable columns={SriScraperColumns(fetchJobs)} data={jobs} searchKey="status" searchPlaceholder="Buscar por estado..." />
          )}
        </CardContent>
      </Card>
    </div>
  );
}

export default function AdminPage() {
  const [activeTab, setActiveTab] = useState("usuarios");

  return (
    <div className="space-y-6 lg:space-y-8">
      <div className="flex items-center gap-3">
        <div className="p-2 bg-lightprimary rounded-lg">
          <Users className="h-6 w-6 text-primary" />
        </div>
        <div>
          <h2 className="text-2xl font-bold text-dark">Panel de Administración</h2>
          <p className="text-sm text-muted-foreground">Gestión de usuarios, perfiles, sucursales, tickets, directorios y scraper SRI</p>
        </div>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTab value="usuarios">
            <Users className="h-4 w-4 mr-1.5" /> Usuarios
          </TabsTab>
          <TabsTab value="perfiles">
            <Shield className="h-4 w-4 mr-1.5" /> Perfiles
          </TabsTab>
          <TabsTab value="sucursales">
            <Building2 className="h-4 w-4 mr-1.5" /> Sucursales
          </TabsTab>
          <TabsTab value="tickets">
            <Headphones className="h-4 w-4 mr-1.5" /> Tickets
          </TabsTab>
          <TabsTab value="scraper">
            <Bot className="h-4 w-4 mr-1.5" /> Scraper SRI
          </TabsTab>
          <TabsTab value="directorio">
            <FolderOpen className="h-4 w-4 mr-1.5" /> Directorios
          </TabsTab>
        </TabsList>

        <TabsPanel value="usuarios">
          <UsuariosTab />
        </TabsPanel>

        <TabsPanel value="perfiles">
          <PerfilesTab />
        </TabsPanel>

        <TabsPanel value="sucursales">
          <SucursalesTab />
        </TabsPanel>

        <TabsPanel value="tickets">
          <TicketsTab />
        </TabsPanel>

        <TabsPanel value="scraper">
          <SriScraperTab />
        </TabsPanel>

        <TabsPanel value="directorio">
          <DirectorioTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
