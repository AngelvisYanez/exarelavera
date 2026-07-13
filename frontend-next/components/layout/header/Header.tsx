"use client";

import { useState, useEffect, useCallback } from "react";
import { usePathname, useRouter } from "next/navigation";
import { Menu, Search, X } from "lucide-react";
import { cn } from "@/lib/utils";
import { buttonVariants } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { Sidebar } from "@/components/layout/sidebar/Sidebar";
import { FullLogo } from "@/components/layout/shared/logo/FullLogo";
import { Notifications } from "./Notifications";
import { Profile } from "./Profile";

interface HeaderProps {
  user?: { usuario: string; Bdd: string };
  onLogout: () => void;
}

const SEARCH_ROUTES: Record<string, string> = {
  dashboard: "/dashboard",
  manifiesto: "/dashboard/manifiestos",
  manifiestos: "/dashboard/manifiestos",
  actor: "/dashboard/actores",
  actores: "/dashboard/actores",
  inventario: "/dashboard/inventario",
  categoria: "/dashboard/inventario",
  categorias: "/dashboard/inventario",
  marca: "/dashboard/inventario",
  marcas: "/dashboard/inventario",
  producto: "/dashboard/inventario",
  productos: "/dashboard/inventario",
  facturación: "/dashboard/facturacion",
  facturacion: "/dashboard/facturacion",
  comprobante: "/dashboard/facturacion",
  comprobantes: "/dashboard/facturacion",
  incidencia: "/dashboard/incidencias",
  incidencias: "/dashboard/incidencias",
  tarea: "/dashboard/tareas",
  tareas: "/dashboard/tareas",
  contabilidad: "/dashboard/contabilidad",
  rrhh: "/dashboard/rrhh",
  personal: "/dashboard/rrhh",
  compras: "/dashboard/compras",
  activos: "/dashboard/activos-fijos",
  bodega: "/dashboard/bodega",
  caja: "/dashboard/caja-chica",
  transporte: "/dashboard/transporte",
  bananero: "/dashboard/bananero",
  camaronera: "/dashboard/camaronera",
  tesoreria: "/dashboard/tesoreria",
  admin: "/dashboard/admin",
  conexión: "/dashboard/admin/conexion",
  conexion: "/dashboard/admin/conexion",
};

export function Header({ user, onLogout }: HeaderProps) {
  const pathname = usePathname();
  const router = useRouter();
  const [isSticky, setIsSticky] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [showSearch, setShowSearch] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");

  useEffect(() => {
    const handleScroll = () => {
      setIsSticky(window.scrollY > 50);
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  const handleSearch = useCallback(() => {
    const q = searchQuery.trim().toLowerCase();
    if (!q) return;
    for (const [key, route] of Object.entries(SEARCH_ROUTES)) {
      if (q.includes(key)) {
        router.push(route);
        setSearchQuery("");
        setShowSearch(false);
        return;
      }
    }
    router.push("/dashboard");
    setSearchQuery("");
    setShowSearch(false);
  }, [searchQuery, router]);

  const handleSearchKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "Enter") handleSearch();
    if (e.key === "Escape") setShowSearch(false);
  };

  const pageTitle = {
    "/dashboard": "Dashboard",
    "/dashboard/manifiestos": "Manifiestos",
    "/dashboard/actores": "Actores",
    "/dashboard/inventario": "Inventario",
    "/dashboard/facturacion": "Facturación",
    "/dashboard/facturacion/emitir": "Emitir",
    "/dashboard/incidencias": "Incidencias",
    "/dashboard/tareas": "Tareas",
    "/dashboard/contabilidad": "Contabilidad",
    "/dashboard/rrhh": "RRHH",
    "/dashboard/compras": "Compras",
    "/dashboard/activos-fijos": "Activos Fijos",
    "/dashboard/bodega": "Bodega",
    "/dashboard/caja-chica": "Caja Chica",
    "/dashboard/transporte": "Transporte Carga",
    "/dashboard/bananero": "Bananero",
    "/dashboard/camaronera": "Camaronera",
    "/dashboard/tesoreria": "Tesorería",
    "/dashboard/admin": "Admin",
  }[pathname] || "Dashboard";

  return (
    <header
      className={cn(
        "sticky top-0 z-30 w-full border-b border-border bg-white transition-all duration-200",
        isSticky && "shadow-sm",
      )}
    >
      {showSearch ? (
        <div className="flex h-[65px] items-center gap-3 px-4">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Buscar módulo..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onKeyDown={handleSearchKeyDown}
              className="pl-9 h-9 bg-lightgray border-0"
              autoFocus
            />
          </div>
          <button
            onClick={() => setShowSearch(false)}
            className={cn(buttonVariants({ variant: "ghost", size: "icon" }), "shrink-0")}
          >
            <X className="h-5 w-5" />
          </button>
        </div>
      ) : (
        <div className="flex h-[65px] items-center gap-3 px-4 sm:px-6">
          {/* Mobile menu */}
          <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
            <SheetTrigger asChild className="xl:hidden">
              <button className={cn(buttonVariants({ variant: "ghost", size: "icon" }), "shrink-0")}>
                <Menu className="h-5 w-5" />
              </button>
            </SheetTrigger>
            <SheetContent side="left" className="w-[270px] p-0">
              <Sidebar onClose={() => setMobileOpen(false)} inSheet />
            </SheetContent>
          </Sheet>

          {/* Mobile logo */}
          <div className="xl:hidden shrink-0">
            <FullLogo />
          </div>

          {/* Page title - desktop */}
          <div className="hidden xl:flex flex-col min-w-0">
            <h2 className="text-lg font-semibold text-dark truncate">{pageTitle}</h2>
            <p className="text-xs text-muted-foreground">Panel de Administración</p>
          </div>

          <div className="flex-1 min-w-0" />

          {/* Search trigger - mobile */}
          <button
            onClick={() => setShowSearch(true)}
            className={cn(buttonVariants({ variant: "ghost", size: "icon" }), "xl:hidden shrink-0 rounded-full")}
          >
            <Search className="h-5 w-5 text-link" />
          </button>

          {/* Search - desktop */}
          <div className="hidden xl:relative xl:flex xl:w-64 2xl:w-72">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Buscar módulo..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onKeyDown={handleSearchKeyDown}
              className="pl-9 h-9 bg-lightgray border-0"
            />
          </div>

          {/* Notifications */}
          <Notifications />

          {/* Profile */}
          <Profile user={user} onLogout={onLogout} />
        </div>
      )}
    </header>
  );
}
