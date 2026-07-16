import { api } from '@/lib/api-client';
import type { ApiResponse, Proveedor } from '@/lib/api-types';

export const proveedoresApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<Proveedor[]>> {
    return api.post<ApiResponse<Proveedor[]>>('/proveedores/obtener', params ?? {});
  },

  crear(data: Partial<Proveedor>): Promise<ApiResponse<Proveedor>> {
    return api.post<ApiResponse<Proveedor>>('/proveedores/crear', data as Record<string, unknown>);
  },

  modificar(data: Partial<Proveedor>): Promise<ApiResponse<Proveedor>> {
    return api.post<ApiResponse<Proveedor>>('/proveedores/modificar', data as Record<string, unknown>);
  },

  eliminar(Prv_Cod: string): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/proveedores/eliminar', { Prv_Cod });
  },
};
