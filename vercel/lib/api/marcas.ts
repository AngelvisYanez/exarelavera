import { api } from '@/lib/api-client';
import type { ApiResponse, Marca } from '@/lib/api-types';

export const marcasApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<Marca[]>> {
    return api.post<ApiResponse<Marca[]>>('/marcas/obtener', params ?? {});
  },

  crear(data: Partial<Marca>): Promise<ApiResponse<Marca>> {
    return api.post<ApiResponse<Marca>>('/marcas/crear', data as Record<string, unknown>);
  },

  modificar(data: Partial<Marca>): Promise<ApiResponse<Marca>> {
    return api.post<ApiResponse<Marca>>('/marcas/modificar', data as Record<string, unknown>);
  },
};
