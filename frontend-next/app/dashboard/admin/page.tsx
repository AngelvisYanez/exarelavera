"use client";

import { useState } from "react";
import {
  Card,
  CardContent,
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
} from "lucide-react";
import { useQuery } from "@/lib/use-query";
import { toast } from "sonner";
import { useConfirm } from "@/lib/hooks/use-confirm";
import { adminApi } from "@/lib/api";
import type { UsuarioRow, Perfil, Sucursal } from "@/lib/api-types";

function UsuariosTab() {
  const [search, setSearch] = useState("");
  const { data, loading } = useQuery(() => adminApi.usuarios(), { auto: true });

  const usuarios = (Array.isArray(data?.data) ? data!.data : []).filter(
    (u) =>
      !search ||
      (u.Prs_Nom || "").toLowerCase().includes(search.toLowerCase()) ||
      (u.Prs_Ape || "").toLowerCase().includes(search.toLowerCase())
  );

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg">Listado de Usuarios</CardTitle>
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
                        <span className="text-xs text-muted-foreground italic">
                          Gestionado por el sistema
                        </span>
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

  const { data, loading } = useQuery(() => adminApi.perfiles(), { auto: true });

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
      const method = isEdit ? "PUT" : "POST";
      const res = await fetch("/api/perfiles", {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });
      if (res.ok) {
        setShowModal(false);
        window.location.reload();
      } else {
        setModalError("Error al guardar el perfil.");
      }
    } catch {
      setModalError("Error de red al guardar perfil.");
    } finally {
      setModalLoading(false);
    }
  };

  const handleDelete = async (perCod: number) => {
    const ok = await confirm("¿Eliminar este perfil?");
    if (!ok) return;
    try {
      const res = await fetch(`/api/perfiles/${perCod}`, { method: "DELETE" });
      if (res.ok) {
        toast.success("Eliminado correctamente");
        window.location.reload();
      }
    } catch {
      // silent
    }
  };

  return (
    <>
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center justify-between text-lg">
          <span>Perfiles</span>
          <Button size="sm" onClick={openCreate}>
            <Plus className="h-4 w-4 mr-1" /> Nuevo Perfil
          </Button>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="mb-4 relative max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por descripción..."
            className="pl-9"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
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
                          <Button variant="ghost" size="icon-sm" onClick={() => handleDelete(p.Per_Cod)} title="Eliminar">
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
                <label className="text-sm font-medium">Código *</label>
                <Input
                  type="number"
                  value={formData.Per_Cod || ""}
                  onChange={(e) => setFormData({ ...formData, Per_Cod: parseInt(e.target.value) || 0 })}
                  placeholder="Código del perfil"
                  disabled={isEdit}
                />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Descripción *</label>
                <Input
                  value={formData.Per_Des}
                  onChange={(e) => setFormData({ ...formData, Per_Des: e.target.value })}
                  placeholder="Nombre del perfil"
                />
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
  const { data, loading } = useQuery(() => adminApi.sucursales(), { auto: true });

  const sucursales = (Array.isArray(data?.data) ? data!.data : []).filter(
    (s) => !search || (s.Suc_Des || "").toLowerCase().includes(search.toLowerCase())
  );

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg">Listado de Sucursales</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="mb-4 relative max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por nombre..."
            className="pl-9"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
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
                        <span className="text-xs text-muted-foreground italic">
                          Gestionado por el sistema
                        </span>
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
          <p className="text-sm text-muted-foreground">Gestión de usuarios, perfiles y sucursales</p>
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
      </Tabs>
    </div>
  );
}
