import { api } from '@/lib/api-client';
import type { ApiResponse, Producto } from '@/lib/api-types';

export const productosApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<Producto[]>> {
    return api.post<ApiResponse<Producto[]>>('/productos/obtener', params ?? {});
  },

  crear(data: Partial<Producto>): Promise<ApiResponse<Producto>> {
    return api.post<ApiResponse<Producto>>('/productos/crear', data as Record<string, unknown>);
  },

  modificar(data: Partial<Producto>): Promise<ApiResponse<Producto>> {
    return api.post<ApiResponse<Producto>>('/productos/modificar', data as Record<string, unknown>);
  },

  eliminar(Pro_Cod: string): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/productos/eliminar', { Pro_Cod });
  },
};
