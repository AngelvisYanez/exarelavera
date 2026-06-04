import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import {
  LayoutDashboard,
  FileText,
  Users,
  Package,
  Building2,
  Truck,
  ShieldAlert,
} from "lucide-react";

interface SidebarProps {
  className?: string;
}

export function Sidebar({ className }: SidebarProps) {
  const pathname = usePathname();

  const routes = [
    {
      label: "Inicio",
      icon: LayoutDashboard,
      href: "/dashboard",
      color: "text-sky-500",
    },
    {
      label: "Manifiestos",
      icon: FileText,
      href: "/dashboard/manifiestos",
      color: "text-violet-500",
    },
    {
      label: "Actores",
      icon: Users,
      href: "/dashboard/actores",
      color: "text-orange-700",
    },
    {
      label: "Inventario",
      icon: Package,
      href: "/dashboard/inventario",
      color: "text-emerald-500",
    },
    {
      label: "Incidencias",
      icon: ShieldAlert,
      href: "/dashboard/incidencias",
      color: "text-red-500",
    },
  ];

  return (
    <div
      className={cn(
        "pb-12 min-h-screen bg-white border-r flex flex-col",
        className,
      )}
    >
      <div className="px-3 py-6 flex-1">
        <Link href="/dashboard" className="flex items-center pl-3 mb-14">
          <div className="relative w-8 h-8 mr-4 bg-primary text-primary-foreground flex items-center justify-center rounded-lg font-bold">
            EX
          </div>
          <h1 className="text-2xl font-bold tracking-tight">Relavera</h1>
        </Link>
        <div className="space-y-1">
          {routes.map((route) => (
            <Link
              key={route.href}
              href={route.href}
              className={cn(
                "text-sm group flex p-3 w-full justify-start font-medium cursor-pointer hover:text-primary hover:bg-primary/10 rounded-lg transition",
                pathname === route.href
                  ? "text-primary bg-primary/10"
                  : "text-zinc-500",
              )}
            >
              <div className="flex items-center flex-1">
                <route.icon className={cn("h-5 w-5 mr-3", route.color)} />
                {route.label}
              </div>
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
}
