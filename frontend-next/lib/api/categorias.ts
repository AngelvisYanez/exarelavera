import { api } from '@/lib/api-client';
import type { ApiResponse, Categoria } from '@/lib/api-types';

export const categoriasApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<Categoria[]>> {
    return api.post<ApiResponse<Categoria[]>>('/categorias/obtener', params ?? {});
  },

  obtenerDetalles(params?: Record<string, unknown>): Promise<ApiResponse<Categoria[]>> {
    return api.post<ApiResponse<Categoria[]>>('/categorias/obtener-detalles', params ?? {});
  },

  crear(data: Partial<Categoria>): Promise<ApiResponse<Categoria>> {
    return api.post<ApiResponse<Categoria>>('/categorias/crear', data as Record<string, unknown>);
  },

  modificar(data: Partial<Categoria>): Promise<ApiResponse<Categoria>> {
    return api.post<ApiResponse<Categoria>>('/categorias/modificar', data as Record<string, unknown>);
  },

  eliminar(Cat_Cod: string): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/categorias/eliminar', { Cat_Cod });
  },
};
