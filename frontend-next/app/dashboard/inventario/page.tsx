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
import { categoriasApi, marcasApi, productosApi } from "@/lib/api";
import { useQuery } from "@/lib/use-query";
import { toast } from "sonner";
import { useConfirm } from "@/lib/hooks/use-confirm";
import type { Categoria, Marca, Producto } from "@/lib/api-types";

function CategoriasTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => categoriasApi.obtener(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<Categoria>>({
    Cat_Des: "",
    Cat_Obs: "",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as Categoria[]) : [];
  const filtered = items.filter(
    (c) => !search || c.Cat_Des?.toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Cat_Des: "", Cat_Obs: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (cat: Categoria) => {
    setFormData(cat);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (cat: Categoria) => {
    const ok = await confirm("¿Estás seguro de eliminar esta categoría?");
    if (!ok) return;
    try {
      const res = await categoriasApi.eliminar(cat.Cat_Cod!);
      if (res.status) {
        toast.success("Categoría eliminada correctamente");
        refetch();
      } else {
        toast.error(res.error || "Error al eliminar la categoría");
      }
    } catch {
      toast.error("Error de conexión al eliminar");
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

      const payload = {
        ...formData,
        Bdd: bdd,
      };

      let res;
      if (isEdit) {
        res = await categoriasApi.modificar(payload);
      } else {
        res = await categoriasApi.crear(payload);
      }

      if (res.status) {
        setShowModal(false);
        refetch();
      } else {
        setModalError(res.error || "Error al procesar la solicitud");
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
          <CardTitle>Categorías de Inventario</CardTitle>
          <CardDescription>
            Agrupación del stock por familias o tipos de artículos.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Categoría
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar categoría..."
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
                <TableHead>Observación</TableHead>
                <TableHead className="text-center">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((c: Categoria, i: number) => (
                <TableRow key={c.Cat_Cod || i}>
                  <TableCell className="font-semibold text-primary">
                    {c.Cat_Cod || "-"}
                  </TableCell>
                  <TableCell className="font-medium">{c.Cat_Des}</TableCell>
                  <TableCell>{c.Cat_Obs || "-"}</TableCell>
                  <TableCell className="text-center">
                    <div className="flex items-center justify-center gap-1">
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
                    </div>
                  </TableCell>
                </TableRow>
              ))}
              {filtered.length === 0 && (
                <TableRow>
                  <TableCell colSpan={4} className="text-center h-24">
                    No se encontraron categorías.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Categorías */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Categoría" : "Nueva Categoría"}
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
                  Nombre / Descripción *
                </label>
                <Input
                  required
                  value={formData.Cat_Des}
                  onChange={(e) =>
                    setFormData({ ...formData, Cat_Des: e.target.value })
                  }
                  placeholder="Ej: Repuestos, Maquinaria, Suministros..."
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Observación
                </label>
                <Input
                  value={formData.Cat_Obs || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Cat_Obs: e.target.value })
                  }
                  placeholder="Detalles de la categoría"
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
                  {isEdit ? "Guardar Cambios" : "Crear Categoría"}
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

function MarcasTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => marcasApi.obtener(), {
    auto: true,
  });
  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<Marca>>({
    Mar_Des: "",
    Mar_Obs: "",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as Marca[]) : [];
  const filtered = items.filter(
    (m) => !search || m.Mar_Des?.toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Mar_Des: "", Mar_Obs: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (mar: Marca) => {
    setFormData(mar);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (mar: Marca) => {
    const ok = await confirm("¿Estás seguro de eliminar esta marca?");
    if (!ok) return;
    try {
      const res = await marcasApi.eliminar(mar.Mar_Cod!);
      if (res.status) {
        toast.success("Marca eliminada correctamente");
        refetch();
      } else {
        toast.error(res.error || "Error al eliminar la marca");
      }
    } catch {
      toast.error("Error de conexión al eliminar");
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

      const payload = {
        ...formData,
        Bdd: bdd,
      };

      let res;
      if (isEdit) {
        res = await marcasApi.modificar(payload);
      } else {
        res = await marcasApi.crear(payload);
      }

      if (res.status) {
        setShowModal(false);
        refetch();
      } else {
        setModalError(res.error || "Error al procesar la solicitud");
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
          <CardTitle>Marcas</CardTitle>
          <CardDescription>
            Control de las marcas de los artículos del inventario.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nueva Marca
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar marca..."
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
                <TableHead>Observación</TableHead>
                <TableHead className="text-center">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((m: Marca, i: number) => (
                <TableRow key={m.Mar_Cod || i}>
                  <TableCell className="font-semibold text-primary">
                    {m.Mar_Cod || "-"}
                  </TableCell>
                  <TableCell className="font-medium">{m.Mar_Des}</TableCell>
                  <TableCell>{m.Mar_Obs || "-"}</TableCell>
                  <TableCell className="text-center">
                    <div className="flex items-center justify-center gap-1">
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
                    </div>
                  </TableCell>
                </TableRow>
              ))}
              {filtered.length === 0 && (
                <TableRow>
                  <TableCell colSpan={4} className="text-center h-24">
                    No se encontraron marcas.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Marcas */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Marca" : "Nueva Marca"}
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
                  Nombre de la Marca *
                </label>
                <Input
                  required
                  value={formData.Mar_Des}
                  onChange={(e) =>
                    setFormData({ ...formData, Mar_Des: e.target.value })
                  }
                  placeholder="Ej: Caterpillar, Toyota, 3M..."
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Observación
                </label>
                <Input
                  value={formData.Mar_Obs || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Mar_Obs: e.target.value })
                  }
                  placeholder="Detalles adicionales"
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
                  {isEdit ? "Guardar Cambios" : "Crear Marca"}
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

function ProductosTab() {
  const [search, setSearch] = useState("");
  const { data, loading, refetch } = useQuery(() => productosApi.obtener(), {
    auto: true,
  });
  const { data: categoriasData } = useQuery(() => categoriasApi.obtener(), {
    auto: true,
  });
  const { data: marcasData } = useQuery(() => marcasApi.obtener(), {
    auto: true,
  });

  const [showModal, setShowModal] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [formData, setFormData] = useState<Partial<Producto>>({
    Pro_Des: "",
    Pro_Obs: "",
    Cat_Cod: "",
    Mar_Cod: "",
  });
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const { confirm, ConfirmDialog } = useConfirm();

  const items = Array.isArray(data?.data) ? (data.data as Producto[]) : [];
  const categorias = Array.isArray(categoriasData?.data)
    ? (categoriasData.data as Categoria[])
    : [];
  const marcas = Array.isArray(marcasData?.data)
    ? (marcasData.data as Marca[])
    : [];

  const getCategoriaNombre = (catCod?: string) => {
    if (!catCod) return "-";
    const cat = categorias.find((c) => String(c.Cat_Cod) === String(catCod));
    return cat ? cat.Cat_Des : catCod;
  };

  const getMarcaNombre = (marCod?: string) => {
    if (!marCod) return "-";
    const mar = marcas.find((m) => String(m.Mar_Cod) === String(marCod));
    return mar ? mar.Mar_Des : marCod;
  };

  const filtered = items.filter(
    (p) => !search || p.Pro_Des?.toLowerCase().includes(search.toLowerCase()),
  );

  const handleOpenCreate = () => {
    setFormData({ Pro_Des: "", Pro_Obs: "", Cat_Cod: "", Mar_Cod: "" });
    setIsEdit(false);
    setModalError(null);
    setShowModal(true);
  };

  const handleOpenEdit = (prod: Producto) => {
    setFormData(prod);
    setIsEdit(true);
    setModalError(null);
    setShowModal(true);
  };

  const handleDelete = async (prod: Producto) => {
    const ok = await confirm("¿Estás seguro de eliminar este producto?");
    if (!ok) return;
    try {
      const res = await productosApi.eliminar(prod.Pro_Cod!);
      if (res.status) {
        toast.success("Producto eliminado correctamente");
        refetch();
      } else {
        toast.error(res.error || "Error al eliminar el producto");
      }
    } catch {
      toast.error("Error de conexión al eliminar");
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

      const payload = {
        ...formData,
        Bdd: bdd,
      };

      let res;
      if (isEdit) {
        res = await productosApi.modificar(payload);
      } else {
        res = await productosApi.crear(payload);
      }

      if (res.status) {
        setShowModal(false);
        refetch();
      } else {
        setModalError(res.error || "Error al procesar la solicitud");
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
          <CardTitle>Productos</CardTitle>
          <CardDescription>
            Catálogo general de productos y artículos del ERP.
          </CardDescription>
        </div>
        <Button size="sm" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-1" /> Nuevo Producto
        </Button>
      </CardHeader>
      <CardContent>
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            placeholder="Buscar producto..."
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
                <TableHead>Observación</TableHead>
                <TableHead>Categoría</TableHead>
                <TableHead>Marca</TableHead>
                <TableHead className="text-center">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((p: Producto, i: number) => (
                <TableRow key={p.Pro_Cod || i}>
                  <TableCell className="font-semibold text-primary">
                    {p.Pro_Cod || "-"}
                  </TableCell>
                  <TableCell className="font-medium">{p.Pro_Des}</TableCell>
                  <TableCell>{p.Pro_Obs || "-"}</TableCell>
                  <TableCell>{getCategoriaNombre(p.Cat_Cod)}</TableCell>
                  <TableCell>{getMarcaNombre(p.Mar_Cod)}</TableCell>
                  <TableCell className="text-center">
                    <div className="flex items-center justify-center gap-1">
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
                    </div>
                  </TableCell>
                </TableRow>
              ))}
              {filtered.length === 0 && (
                <TableRow>
                  <TableCell colSpan={6} className="text-center h-24">
                    No se encontraron productos.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
            </div>
          </div>
        )}
      </CardContent>

      {/* Modal Productos */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card rounded-lg shadow-boxShadow w-full max-w-md animate-fade-in flex flex-col">
            <div className="p-6 border-b flex justify-between items-center bg-muted/50 rounded-t-lg">
              <h3 className="text-lg font-bold text-dark">
                {isEdit ? "Editar Producto" : "Nuevo Producto"}
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
                  Nombre / Descripción del Artículo *
                </label>
                <Input
                  required
                  value={formData.Pro_Des}
                  onChange={(e) =>
                    setFormData({ ...formData, Pro_Des: e.target.value })
                  }
                  placeholder="Ej: Aceite de motor, Pala minera, Casco protector..."
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Categoría
                  </label>
                  <select
                    value={formData.Cat_Cod || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Cat_Cod: e.target.value })
                    }
                    className="w-full px-3 py-2 border border-input bg-background rounded-md text-sm text-black"
                  >
                    <option value="">-- Ninguna --</option>
                    {categorias.map((c) => (
                      <option key={c.Cat_Cod} value={c.Cat_Cod}>
                        {c.Cat_Des}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-dark mb-1">
                    Marca
                  </label>
                  <select
                    value={formData.Mar_Cod || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, Mar_Cod: e.target.value })
                    }
                    className="w-full px-3 py-2 border border-input bg-background rounded-md text-sm text-black"
                  >
                    <option value="">-- Ninguna --</option>
                    {marcas.map((m) => (
                      <option key={m.Mar_Cod} value={m.Mar_Cod}>
                        {m.Mar_Des}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-dark mb-1">
                  Observación
                </label>
                <Input
                  value={formData.Pro_Obs || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, Pro_Obs: e.target.value })
                  }
                  placeholder="Especificaciones o códigos"
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
                  {isEdit ? "Guardar Cambios" : "Crear Producto"}
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

export default function InventarioPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <div>
        <h2 className="text-3xl font-bold tracking-tight text-dark">Inventario</h2>
        <p className="text-muted-foreground mt-1">
          Gestión de categorías, marcas y productos.
        </p>
      </div>
      <Tabs defaultValue="categorias">
        <TabsList>
          <TabsTab value="categorias">Categorías</TabsTab>
          <TabsTab value="marcas">Marcas</TabsTab>
          <TabsTab value="productos">Productos</TabsTab>
        </TabsList>
        <TabsPanel value="categorias">
          <CategoriasTab />
        </TabsPanel>
        <TabsPanel value="marcas">
          <MarcasTab />
        </TabsPanel>
        <TabsPanel value="productos">
          <ProductosTab />
        </TabsPanel>
      </Tabs>
    </div>
  );
}
