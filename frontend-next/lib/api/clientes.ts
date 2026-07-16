import { api } from '@/lib/api-client';
import type { ApiResponse, Cliente } from '@/lib/api-types';

export const clientesApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<Cliente[]>> {
    return api.post<ApiResponse<Cliente[]>>('/clientes/obtener', params ?? {});
  },

  crear(data: Partial<Cliente>): Promise<ApiResponse<Cliente>> {
    return api.post<ApiResponse<Cliente>>('/clientes/crear', data as Record<string, unknown>);
  },

  modificar(data: Partial<Cliente>): Promise<ApiResponse<Cliente>> {
    return api.post<ApiResponse<Cliente>>('/clientes/modificar', data as Record<string, unknown>);
  },

  eliminar(Cli_Cod: string): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/clientes/eliminar', { Cli_Cod });
  },
};
