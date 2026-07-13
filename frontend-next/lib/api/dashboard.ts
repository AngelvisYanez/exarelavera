import { api } from '@/lib/api-client';

export interface DashboardStats {
  totalClientes: number;
  totalProductos: number;
  totalFacturas: number;
  totalProveedores: number;
  totalManifiestos: number;
  totalTareas: number;
}

export interface IngresoMensual {
  label: string;
  mes: number;
  facturas: number;
  ingresos: number;
}

export interface ResumenMes {
  ingresos: number;
  facturas: number;
  variacion: number;
}

export interface ProductoPopular {
  nombre: string;
  total_ventas: number;
  ingresos: number;
}

export interface DistribucionIngreso {
  nombre: string;
  total: number;
  ingresos: number;
}

export const dashboardApi = {
  stats(): Promise<{ success: boolean; data?: DashboardStats; error?: string }> {
    return api.post('/admin/dashboard/stats');
  },

  ingresos(): Promise<{ success: boolean; data?: IngresoMensual[]; error?: string }> {
    return api.post('/admin/dashboard/ingresos');
  },

  resumenMes(): Promise<{ success: boolean; data?: ResumenMes; error?: string }> {
    return api.post('/admin/dashboard/resumen-mes');
  },

  productosPopulares(limit = 5): Promise<{ success: boolean; data?: ProductoPopular[]; error?: string }> {
    return api.post('/admin/dashboard/productos-populares', { limit });
  },

  distribucionIngresos(): Promise<{ success: boolean; data?: DistribucionIngreso[]; error?: string }> {
    return api.post('/admin/dashboard/distribucion-ingresos');
  },
};
