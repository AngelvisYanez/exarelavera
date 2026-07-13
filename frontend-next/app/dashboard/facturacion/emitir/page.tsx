"use client";

import { useState, useCallback } from "react";
import Link from "next/link";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  ArrowLeft,
  Search,
  Loader2,
  Plus,
  Trash2,
  Cloud,
  CheckCircle2,
  XCircle,
  PackagePlus,
} from "lucide-react";
import { clientesApi } from "@/lib/api";
import { facturacionApi, emitirApi } from "@/lib/api";
import type { Cliente } from "@/lib/api-types";
import { toast } from "sonner";

interface ProductResult {
  Pro_Cod: string;
  Pro_Des: string;
  Pro_Obs: string;
  Pro_Ide: string;
  Iva_Cod: string;
  Pro_Bar: string;
  Pro_Est: string;
}

interface LineItem {
  key: number;
  Pro_Cod: number;
  Pro_Des: string;
  Vet_Can: number;
  Vet_Pru: number;
  Iva_Cod: number;
}

export default function EmitirPage() {
  const [cliCod, setCliCod] = useState("");
  const [cliNom, setCliNom] = useState("");
  const [cliRuc, setCliRuc] = useState("");
  const [cliSearch, setCliSearch] = useState("");
  const [cliResults, setCliResults] = useState<Cliente[]>([]);
  const [cliLoading, setCliLoading] = useState(false);

  const [vetFec, setVetFec] = useState(() => new Date().toISOString().slice(0, 10));
  const [sucCod, setSucCod] = useState("1");
  const [punCod, setPunCod] = useState("1");
  const [vndCod, setVndCod] = useState("1");
  const [ciuCod, setCiuCod] = useState("1");
  const [vetObs, setVetObs] = useState("");

  const [items, setItems] = useState<LineItem[]>([]);
  const [nextKey, setNextKey] = useState(0);

  const [proSearch, setProSearch] = useState("");
  const [proResults, setProResults] = useState<ProductResult[]>([]);
  const [proLoading, setProLoading] = useState(false);
  const [showProSearch, setShowProSearch] = useState(false);

  const [showCreatePro, setShowCreatePro] = useState(false);
  const [createProName, setCreateProName] = useState("");
  const [createProPrice, setCreateProPrice] = useState("");
  const [createProLoading, setCreateProLoading] = useState(false);

  const [emitLoading, setEmitLoading] = useState(false);
  const [result, setResult] = useState<{
    success: boolean;
    message: string;
    Vet_Cod?: number;
    Vet_Num?: string;
    claveAcceso?: string;
    numeroAutorizacion?: string;
  } | null>(null);

  const searchClient = useCallback(async () => {
    if (!cliSearch.trim()) return;
    setCliLoading(true);
    try {
      const res = await clientesApi.obtener({ search: cliSearch.trim() });
      setCliResults(res.status && res.data ? res.data : []);
    } catch {
      setCliResults([]);
    } finally {
      setCliLoading(false);
    }
  }, [cliSearch]);

  const selectClient = useCallback((c: Cliente) => {
    setCliCod(c.Cli_Cod || "");
    setCliNom(c.Cli_Nom || "");
    setCliRuc(c.Cli_Ced || "");
    setCliSearch(c.Cli_Nom || "");
    setCliResults([]);
  }, []);

  const searchProduct = useCallback(async (q: string) => {
    setProSearch(q);
    if (!q.trim()) { setProResults([]); return; }
    setProLoading(true);
    try {
      const res = await emitirApi.buscarProductos({ search: q.trim() });
      if (res.status && res.data) {
        setProResults(res.data as ProductResult[]);
      } else {
        setProResults([]);
      }
    } catch {
      setProResults([]);
    } finally {
      setProLoading(false);
    }
  }, []);

  const addItem = useCallback((p: ProductResult) => {
    setItems((prev) => [
      ...prev,
      {
        key: nextKey,
        Pro_Cod: parseInt(p.Pro_Cod || "0"),
        Pro_Des: p.Pro_Des || p.Pro_Obs || "",
        Vet_Can: 1,
        Vet_Pru: 0,
        Iva_Cod: parseInt(p.Iva_Cod || "1"),
      },
    ]);
    setNextKey((k) => k + 1);
    setProSearch("");
    setProResults([]);
    setShowProSearch(false);
  }, [nextKey]);

  const updateItem = useCallback((key: number, field: keyof LineItem, value: string | number) => {
    setItems((prev) =>
      prev.map((it) =>
        it.key === key
          ? { ...it, [field]: typeof value === "string" ? parseFloat(value) || 0 : value }
          : it
      )
    );
  }, []);

  const removeItem = useCallback((key: number) => {
    setItems((prev) => prev.filter((it) => it.key !== key));
  }, []);

  const total = items.reduce((sum, it) => sum + it.Vet_Can * it.Vet_Pru, 0);

  const handleCreateProduct = useCallback(async () => {
    if (!createProName.trim()) return;
    setCreateProLoading(true);
    try {
      const res = await emitirApi.crearProducto({
        Pro_Des: createProName.trim(),
        Pro_Obs: createProName.trim(),
        Pre_Pvp: parseFloat(createProPrice) || 0,
      });
      if (res.status && res.data?.Pro_Cod) {
        const newProd: ProductResult = {
          Pro_Cod: String(res.data.Pro_Cod),
          Pro_Des: createProName.trim(),
          Pro_Obs: createProName.trim(),
          Pro_Ide: res.data.Pro_Ide || "",
          Iva_Cod: String(res.data.Iva_Cod || 1),
          Pro_Bar: "",
          Pro_Est: "A",
        };
        addItem(newProd);
        setShowCreatePro(false);
        setCreateProName("");
        setCreateProPrice("");
      } else {
        toast.error(res.error || "Error al crear producto");
      }
    } catch (e) {
      toast.error("Error de conexión: " + (e instanceof Error ? e.message : String(e)));
    } finally {
      setCreateProLoading(false);
    }
  }, [createProName, createProPrice, addItem]);

  const handleEmitir = async () => {
    if (!cliCod) { toast.warning("Seleccione un cliente"); return; }
    if (items.length === 0) { toast.warning("Agregue al menos un producto"); return; }
    setEmitLoading(true);
    setResult(null);
    try {
      const payload: Record<string, unknown> = {
        Suc_Cod: parseInt(sucCod) || 1,
        Pun_Cod: parseInt(punCod) || 1,
        Cli_Cod: parseInt(cliCod),
        Ciu_Cod: parseInt(ciuCod) || 1,
        Vnd_Cod: parseInt(vndCod) || 1,
        Tic_Cod: 1,
        Vet_Fec: vetFec,
        Vet_Obs: vetObs,
        items: items.map((it) => ({
          Pro_Cod: it.Pro_Cod,
          Iva_Cod: it.Iva_Cod,
          Vet_Can: it.Vet_Can,
          Vet_Pru: it.Vet_Pru,
        })),
        pagos: [
          { Pag_Cod: 1, Vet_Tot: total, Bak_Cod: 1 },
        ],
      };
      const res = await facturacionApi.emitirComprobante(payload as Record<string, unknown>);
      if (res.status && res.data?.success) {
        setResult({
          success: true,
          message: res.data.message || "Comprobante emitido correctamente",
          Vet_Cod: res.data.Vet_Cod,
          Vet_Num: res.data.Vet_Num?.toString(),
          claveAcceso: res.data.claveAcceso,
          numeroAutorizacion: res.data.numeroAutorizacion,
        });
      } else {
        setResult({
          success: false,
          message: res.error || res.data?.message || "Error al emitir comprobante",
        });
      }
    } catch (e) {
      setResult({
        success: false,
        message: "Error de conexión: " + (e instanceof Error ? e.message : String(e)),
      });
    } finally {
      setEmitLoading(false);
    }
  };

  return (
    <div className="space-y-6 lg:space-y-8">
      <div className="flex items-center gap-4">
        <Link
          href="/dashboard/facturacion"
          className="inline-flex items-center justify-center rounded-md h-9 w-9 hover:bg-accent"
        >
          <ArrowLeft className="h-5 w-5" />
        </Link>
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-dark">Emitir Comprobante Electrónico</h1>
          <p className="text-sm text-muted-foreground mt-1">
            Cree un nuevo comprobante, fírmelo y envíelo al SRI
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Cliente</CardTitle>
            </CardHeader>
            <CardContent>
              {cliCod ? (
                <div className="flex items-center justify-between">
                  <div>
                    <p className="font-medium">{cliNom}</p>
                    <p className="text-sm text-muted-foreground">{cliRuc}</p>
                  </div>
                  <Button variant="ghost" size="sm" onClick={() => { setCliCod(""); setCliNom(""); setCliRuc(""); setCliSearch(""); }}>
                    Cambiar
                  </Button>
                </div>
              ) : (
                <div className="relative">
                  <div className="flex gap-2">
                    <div className="relative flex-1">
                      <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                      <Input
                        placeholder="Buscar cliente por nombre o cédula..."
                        value={cliSearch}
                        onChange={(e) => setCliSearch(e.target.value)}
                        onKeyDown={(e) => { if (e.key === "Enter") searchClient(); }}
                        className="pl-8"
                      />
                    </div>
                    <Button variant="outline" onClick={searchClient} disabled={cliLoading}>
                      {cliLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Search className="h-4 w-4" />}
                    </Button>
                  </div>
                  {cliResults.length > 0 && (
                    <div className="absolute z-10 mt-1 w-full bg-card border rounded-md shadow-lg max-h-60 overflow-y-auto">
                      {cliResults.map((c) => (
                        <button
                          key={c.Cli_Cod}
                          onClick={() => selectClient(c)}
                          className="w-full text-left px-3 py-2 hover:bg-accent text-sm"
                        >
                          <span className="font-medium">{c.Cli_Nom}</span>
                          <span className="ml-2 text-muted-foreground">{c.Cli_Ced}</span>
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Productos</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {!showProSearch ? (
                <Button variant="outline" onClick={() => setShowProSearch(true)}>
                  <Plus className="h-4 w-4 mr-2" /> Agregar Producto
                </Button>
              ) : (
                <div>
                  <div className="relative">
                    <Input
                      placeholder="Buscar producto por nombre..."
                      value={proSearch}
                      onChange={(e) => searchProduct(e.target.value)}
                      autoFocus
                    />
                    {proLoading && <Loader2 className="absolute right-2.5 top-2.5 h-4 w-4 animate-spin" />}
                  </div>
                  {proResults.length > 0 && (
                    <div className="mt-1 border rounded-md shadow-lg max-h-60 overflow-y-auto">
                      {proResults.map((p) => (
                        <button
                          key={p.Pro_Cod}
                          onClick={() => addItem(p)}
                          className="w-full text-left px-3 py-2 hover:bg-accent text-sm flex items-center justify-between"
                        >
                          <span className="font-medium">{p.Pro_Des || p.Pro_Obs}</span>
                          <span className="text-muted-foreground text-xs">#{p.Pro_Cod}</span>
                        </button>
                      ))}
                    </div>
                  )}
                  {!proLoading && proSearch && proResults.length === 0 && (
                    <div className="mt-2 flex items-center gap-2">
                      <p className="text-xs text-muted-foreground">No se encontraron productos.</p>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => { setCreateProName(proSearch); setShowCreatePro(true); }}
                      >
                        <PackagePlus className="h-3 w-3 mr-1" /> Crear &quot;{proSearch}&quot;
                      </Button>
                    </div>
                  )}
                </div>
              )}

              {items.length > 0 && (
                <div className="rounded-md border overflow-x-auto">
                  <div className="min-w-[700px]">
                    <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Producto</TableHead>
                        <TableHead className="w-20 text-right">Cant.</TableHead>
                        <TableHead className="w-28 text-right">Precio</TableHead>
                        <TableHead className="w-24 text-right">Subtotal</TableHead>
                        <TableHead className="w-10"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {items.map((it) => (
                        <TableRow key={it.key}>
                          <TableCell className="font-medium">{it.Pro_Des}</TableCell>
                          <TableCell>
                            <Input
                              type="number"
                              min="0"
                              step="0.01"
                              value={it.Vet_Can}
                              onChange={(e) => updateItem(it.key, "Vet_Can", e.target.value)}
                              className="h-8 text-right"
                            />
                          </TableCell>
                          <TableCell>
                            <Input
                              type="number"
                              min="0"
                              step="0.01"
                              value={it.Vet_Pru}
                              onChange={(e) => updateItem(it.key, "Vet_Pru", e.target.value)}
                              className="h-8 text-right"
                            />
                          </TableCell>
                          <TableCell className="text-right font-mono">
                            ${(it.Vet_Can * it.Vet_Pru).toFixed(2)}
                          </TableCell>
                          <TableCell>
                            <button
                              onClick={() => removeItem(it.key)}
                              className="inline-flex items-center justify-center rounded-md h-8 w-8 hover:bg-destructive/10 text-destructive"
                            >
                              <Trash2 className="h-4 w-4" />
                            </button>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                    </Table>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Datos del Comprobante</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div>
                <label className="text-xs font-medium text-muted-foreground">Fecha</label>
                <Input type="date" value={vetFec} onChange={(e) => setVetFec(e.target.value)} />
              </div>
              <div>
                <label className="text-xs font-medium text-muted-foreground">Sucursal</label>
                <Input value={sucCod} onChange={(e) => setSucCod(e.target.value)} />
              </div>
              <div>
                <label className="text-xs font-medium text-muted-foreground">Punto de Emisión</label>
                <Input value={punCod} onChange={(e) => setPunCod(e.target.value)} />
              </div>
              <div>
                <label className="text-xs font-medium text-muted-foreground">Vendedor</label>
                <Input value={vndCod} onChange={(e) => setVndCod(e.target.value)} />
              </div>
              <div>
                <label className="text-xs font-medium text-muted-foreground">Ciudad</label>
                <Input value={ciuCod} onChange={(e) => setCiuCod(e.target.value)} />
              </div>
              <div>
                <label className="text-xs font-medium text-muted-foreground">Observación</label>
                <Input value={vetObs} onChange={(e) => setVetObs(e.target.value)} />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Totales</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Subtotal</span>
                  <span className="font-mono">${total.toFixed(2)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">IVA</span>
                  <span className="font-mono">$0.00</span>
                </div>
                <div className="border-t pt-2 flex justify-between font-bold">
                  <span>Total</span>
                  <span className="font-mono">${total.toFixed(2)}</span>
                </div>
              </div>
            </CardContent>
          </Card>

          <Button
            className="w-full"
            size="lg"
            onClick={handleEmitir}
            disabled={emitLoading || !cliCod || items.length === 0}
          >
            {emitLoading ? (
              <>
                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                Emitiendo...
              </>
            ) : (
              <>
                <Cloud className="h-4 w-4 mr-2" />
                Emitir y Autorizar
              </>
            )}
          </Button>

          {result && (
            <Card className={result.success ? "border-green-200" : "border-red-200"}>
              <CardContent className="pt-4">
                <div className="flex items-start gap-3">
                  {result.success ? (
                    <CheckCircle2 className="h-5 w-5 text-primary mt-0.5 shrink-0" />
                  ) : (
                    <XCircle className="h-5 w-5 text-error mt-0.5 shrink-0" />
                  )}
                  <div className="text-sm">
                    <p className={result.success ? "text-success font-medium" : "text-error font-medium"}>
                      {result.success ? "Comprobante emitido exitosamente" : "Error"}
                    </p>
                    <p className="text-muted-foreground mt-1">{result.message}</p>
                    {result.success && (
                      <div className="mt-3 space-y-1 text-xs font-mono">
                        <p><span className="text-muted-foreground">Vet_Cod:</span> {result.Vet_Cod}</p>
                        <p><span className="text-muted-foreground">Número:</span> {result.Vet_Num}</p>
                        <p><span className="text-muted-foreground">Clave de Acceso:</span> {result.claveAcceso}</p>
                        <p><span className="text-muted-foreground">Autorización:</span> {result.numeroAutorizacion}</p>
                      </div>
                    )}
                    {!result.success && (
                      <Button variant="outline" size="sm" className="mt-3" onClick={() => setResult(null)}>
                        Intentar de nuevo
                      </Button>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </div>

      {/* Modal: Crear Producto */}
      {showCreatePro && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40" onClick={() => setShowCreatePro(false)}>
          <div className="bg-card rounded-lg shadow-boxShadow max-w-md w-full mx-4 p-6" onClick={(e) => e.stopPropagation()}>
            <h3 className="text-lg font-semibold mb-4">Crear Producto</h3>
            <div className="space-y-3">
              <div>
                <label className="text-xs font-medium text-muted-foreground">Nombre del Producto *</label>
                <Input
                  value={createProName}
                  onChange={(e) => setCreateProName(e.target.value)}
                  placeholder="Ej: Servicio de consultoría"
                />
              </div>
              <div>
                <label className="text-xs font-medium text-muted-foreground">Precio de Venta</label>
                <Input
                  type="number"
                  min="0"
                  step="0.01"
                  value={createProPrice}
                  onChange={(e) => setCreateProPrice(e.target.value)}
                  placeholder="0.00"
                />
              </div>
            </div>
            <div className="flex gap-3 justify-end mt-6">
              <Button variant="outline" size="sm" onClick={() => setShowCreatePro(false)}>
                Cancelar
              </Button>
              <Button
                size="sm"
                onClick={handleCreateProduct}
                disabled={createProLoading || !createProName.trim()}
              >
                {createProLoading && <Loader2 className="h-4 w-4 animate-spin" />}
                {createProLoading ? "Creando..." : "Crear y Agregar"}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
