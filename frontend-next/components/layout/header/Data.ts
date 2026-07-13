import { User, Settings, LogOut } from "lucide-react";

export const profileDD = [
  { icon: User, title: "Mi Perfil", url: "#" },
  { icon: Settings, title: "Configuración BD", url: "/dashboard/incidencias" },
  { icon: LogOut, title: "Cerrar Sesión", url: "#" },
];

export interface NotificationItem {
  title: string;
  subtitle: string;
  color?: string;
}

export const notifications: NotificationItem[] = [
  { title: "Nuevo manifiesto registrado", subtitle: "Hace 5 min", color: "bg-primary" },
  { title: "Factura #001-002 autorizada", subtitle: "Hace 15 min", color: "bg-success" },
  { title: "Cliente actualizado: Minera XYZ", subtitle: "Hace 1 hora", color: "bg-warning" },
  { title: "Conexión BD estable", subtitle: "Hace 2 horas", color: "bg-info" },
];
