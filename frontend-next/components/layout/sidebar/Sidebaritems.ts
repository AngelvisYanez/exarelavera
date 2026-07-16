import {
  LayoutDashboard,
  FileText,
  Users,
  Package,
  Receipt,
  Send,
  ShieldAlert,
  ClipboardList,
  LogIn,
  Settings,
  BookOpen,
  Briefcase,
  ShoppingCart,
  Activity,
  Warehouse,
  Wallet,
  Truck,
  Tractor,
  Fish,
  Landmark,
  UserCog,
  Bot,
  ClipboardCheck,
  Database,
} from "lucide-react";

export interface SidebarItem {
  title: string;
  href?: string;
  children?: SidebarItem[];
  badge?: string;
  badgeColor?: string;
  pro?: boolean;
  iconComponent?: React.ComponentType<{ className?: string }>;
}

export const sidebarItems: (SidebarItem | { subheading: string })[] = [
  { subheading: "PRINCIPAL" },
  {
    title: "Dashboard",
    href: "/dashboard",
    iconComponent: LayoutDashboard,
  },
  {
    title: "Manifiestos",
    href: "/dashboard/manifiestos",
    iconComponent: FileText,
  },
  {
    title: "Actores",
    href: "/dashboard/actores",
    iconComponent: Users,
  },
  { subheading: "GESTIÓN" },
  {
    title: "Inventario",
    iconComponent: Package,
    children: [
      { title: "Categorías", href: "/dashboard/inventario" },
      { title: "Marcas", href: "/dashboard/inventario" },
      { title: "Productos", href: "/dashboard/inventario" },
    ],
  },
  {
    title: "Facturación",
    iconComponent: Receipt,
    children: [
      { title: "Comprobantes", href: "/dashboard/facturacion" },
      { title: "Emitir", href: "/dashboard/facturacion/emitir" },
    ],
  },
  { subheading: "MONITOREO" },
  {
    title: "Incidencias",
    href: "/dashboard/incidencias",
    iconComponent: ShieldAlert,
  },
  {
    title: "Tareas",
    href: "/dashboard/tareas",
    iconComponent: ClipboardList,
  },
  {
    title: "Auditorías",
    href: "/auditorias/tareas",
    iconComponent: ClipboardCheck,
  },
  { subheading: "MÓDULOS" },
  {
    title: "Contabilidad",
    href: "/dashboard/contabilidad",
    iconComponent: BookOpen,
  },
  {
    title: "RRHH",
    href: "/dashboard/rrhh",
    iconComponent: Briefcase,
  },
  {
    title: "Compras",
    href: "/dashboard/compras",
    iconComponent: ShoppingCart,
  },
  {
    title: "Activos Fijos",
    href: "/dashboard/activos-fijos",
    iconComponent: Activity,
  },
  {
    title: "Bodega",
    href: "/dashboard/bodega",
    iconComponent: Warehouse,
  },
  {
    title: "Caja Chica",
    href: "/dashboard/caja-chica",
    iconComponent: Wallet,
  },
  {
    title: "Transporte",
    href: "/dashboard/transporte",
    iconComponent: Truck,
  },
  {
    title: "Bananero",
    href: "/dashboard/bananero",
    iconComponent: Tractor,
  },
  {
    title: "Camaronera",
    href: "/dashboard/camaronera",
    iconComponent: Fish,
  },
  {
    title: "Tesorería",
    href: "/dashboard/tesoreria",
    iconComponent: Landmark,
  },
  {
    title: "Admin",
    href: "/dashboard/admin",
    iconComponent: UserCog,
  },
  { subheading: "SISTEMA" },
  {
    title: "SRI Scraper",
    href: "/dashboard/sri-scraper",
    iconComponent: Bot,
  },
  {
    title: "Conexión BD",
    href: "/dashboard/admin/conexion",
    iconComponent: Database,
  },
  {
    title: "Login",
    href: "/login",
    iconComponent: LogIn,
  },
];
