"use client";

import { useEffect, useState } from "react";
import { FileText, Activity, Users, Package, Receipt, ClipboardList } from "lucide-react";
import Link from "next/link";
import { Card, CardContent } from "@/components/ui/card";
import { dashboardApi } from "@/lib/api";
import type { DashboardStats } from "@/lib/api";

export default function ModuleCards() {
  const [stats, setStats] = useState<DashboardStats | null>(null);

  useEffect(() => {
    dashboardApi.stats().then((res) => {
      if (res.success && res.data) setStats(res.data);
    });
  }, []);

  const modules = [
    {
      title: "Manifiestos",
      desc: "Registra y haz seguimiento al movimiento de vehículos.",
      icon: FileText,
      href: "/dashboard/manifiestos",
      color: "bg-lightprimary text-primary",
      count: stats?.totalManifiestos,
    },
    {
      title: "Productos",
      desc: "Catálogo de productos, categorías y marcas.",
      icon: Package,
      href: "/dashboard/inventario",
      color: "bg-lightsuccess text-success",
      count: stats?.totalProductos,
    },
    {
      title: "Facturación",
      desc: "Comprobantes electrónicos y emisión de facturas.",
      icon: Receipt,
      href: "/dashboard/facturacion",
      color: "bg-lightwarning text-warning",
      count: stats?.totalFacturas,
    },
    {
      title: "Clientes",
      desc: "Administración de clientes y proveedores.",
      icon: Users,
      href: "/dashboard/actores",
      color: "bg-lightsecondary text-secondary",
      count: stats?.totalClientes,
    },
    {
      title: "Tareas",
      desc: "Auditoría, asignación y seguimiento de tareas.",
      icon: ClipboardList,
      href: "/dashboard/tareas",
      color: "bg-lighterror text-error",
      count: stats?.totalTareas,
    },
    {
      title: "Control Técnico",
      desc: "Gestiona el tratamiento del material y celdas.",
      icon: Activity,
      href: "/dashboard/manifiestos",
      color: "bg-lightsuccess text-success",
    },
  ];

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
      {modules.map((mod) => (
        <Link key={mod.title} href={mod.href}>
          <Card className="shadow-boxShadow border-ld hover:shadow-md transition-all duration-200 cursor-pointer">
            <CardContent className="p-6">
              <div className="flex flex-col items-center text-center">
                <div className={`h-14 w-14 rounded-full ${mod.color} flex items-center justify-center mb-4`}>
                  <mod.icon className="h-7 w-7" />
                </div>
                <h4 className="font-semibold text-dark text-lg">{mod.title}</h4>
                <p className="text-sm text-muted-foreground mt-2">{mod.desc}</p>
                {mod.count !== undefined && (
                  <p className="text-xs font-bold text-primary mt-2">
                    {mod.count.toLocaleString()} registros
                  </p>
                )}
              </div>
            </CardContent>
          </Card>
        </Link>
      ))}
    </div>
  );
}
