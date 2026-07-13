import { api } from '@/lib/api-client';
import type { ApiResponse } from '@/lib/api-types';

export const cajaChicaApi = {
  cajas(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/caja-chica/cajas', {});
  },

  movimientos(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/caja-chica/movimientos', {});
  },

  reposiciones(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/caja-chica/reposiciones', {});
  },

  crearCaja(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/caja-chica/cajas/crear', data);
  },
  obtenerCaja(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/caja-chica/cajas/obtener-por-id', { id_field: id });
  },
  modificarCaja(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/caja-chica/cajas/modificar', data);
  },
  eliminarCaja(id: number | string): Promise<{ success: boolean }> {
    return api.post('/caja-chica/cajas/eliminar', { id_field: id });
  },

  crearMovimiento(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/caja-chica/movimientos/crear', data);
  },
  obtenerMovimiento(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/caja-chica/movimientos/obtener-por-id', { id_field: id });
  },
  modificarMovimiento(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/caja-chica/movimientos/modificar', data);
  },
  eliminarMovimiento(id: number | string): Promise<{ success: boolean }> {
    return api.post('/caja-chica/movimientos/eliminar', { id_field: id });
  },

  crearReposicion(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/caja-chica/reposiciones/crear', data);
  },
  obtenerReposicion(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/caja-chica/reposiciones/obtener-por-id', { id_field: id });
  },
  modificarReposicion(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/caja-chica/reposiciones/modificar', data);
  },
  eliminarReposicion(id: number | string): Promise<{ success: boolean }> {
    return api.post('/caja-chica/reposiciones/eliminar', { id_field: id });
  },
};
