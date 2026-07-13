import { api } from '@/lib/api-client';
import type { ApiResponse } from '@/lib/api-types';

export const comprasApi = {
  requisiciones(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/compras/requisiciones', {});
  },

  requisitores(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/compras/requisitores', {});
  },

  crearRequisicion(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/compras/requisiciones/crear', data);
  },
  obtenerRequisicion(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/compras/requisiciones/obtener-por-id', { id_field: id });
  },
  modificarRequisicion(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/compras/requisiciones/modificar', data);
  },
  eliminarRequisicion(id: number | string): Promise<{ success: boolean }> {
    return api.post('/compras/requisiciones/eliminar', { id_field: id });
  },

  crearRequisitor(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/compras/requisitores/crear', data);
  },
  obtenerRequisitor(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/compras/requisitores/obtener-por-id', { id_field: id });
  },
  modificarRequisitor(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/compras/requisitores/modificar', data);
  },
  eliminarRequisitor(id: number | string): Promise<{ success: boolean }> {
    return api.post('/compras/requisitores/eliminar', { id_field: id });
  },
};
