import { api } from '@/lib/api-client';
import type { ApiResponse, ActivoFijo } from '@/lib/api-types';

export const activosFijosApi = {
  activos(): Promise<ApiResponse<ActivoFijo[]>> {
    return api.post<ApiResponse<ActivoFijo[]>>('/activosfijos/activos', {});
  },

  tiposActivo(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/activosfijos/tipos-activo', {});
  },

  depreciaciones(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/activosfijos/depreciaciones', {});
  },

  custodios(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/activosfijos/custodios', {});
  },

  mantenimientos(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/activosfijos/mantenimientos', {});
  },

  crearActivo(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/activosfijos/activos/crear', data);
  },
  obtenerActivo(id: number): Promise<ApiResponse<ActivoFijo>> {
    return api.post('/activosfijos/activos/obtener-por-id', { id_field: id });
  },
  modificarActivo(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/activosfijos/activos/modificar', data);
  },
  eliminarActivo(id: number): Promise<{ success: boolean }> {
    return api.post('/activosfijos/activos/eliminar', { id_field: id });
  },

  crearTipoActivo(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/activosfijos/tipos-activo/crear', data);
  },
  obtenerTipoActivo(id: number): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/activosfijos/tipos-activo/obtener-por-id', { id_field: id });
  },
  modificarTipoActivo(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/activosfijos/tipos-activo/modificar', data);
  },
  eliminarTipoActivo(id: number): Promise<{ success: boolean }> {
    return api.post('/activosfijos/tipos-activo/eliminar', { id_field: id });
  },

  crearDepreciacion(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/activosfijos/depreciaciones/crear', data);
  },
  obtenerDepreciacion(id: number): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/activosfijos/depreciaciones/obtener-por-id', { id_field: id });
  },
  modificarDepreciacion(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/activosfijos/depreciaciones/modificar', data);
  },
  eliminarDepreciacion(id: number): Promise<{ success: boolean }> {
    return api.post('/activosfijos/depreciaciones/eliminar', { id_field: id });
  },

  crearCustodio(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/activosfijos/custodios/crear', data);
  },
  obtenerCustodio(id: number): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/activosfijos/custodios/obtener-por-id', { id_field: id });
  },
  modificarCustodio(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/activosfijos/custodios/modificar', data);
  },
  eliminarCustodio(id: number): Promise<{ success: boolean }> {
    return api.post('/activosfijos/custodios/eliminar', { id_field: id });
  },

  crearMantenimiento(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/activosfijos/mantenimientos/crear', data);
  },
  obtenerMantenimiento(id: number): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/activosfijos/mantenimientos/obtener-por-id', { id_field: id });
  },
  modificarMantenimiento(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/activosfijos/mantenimientos/modificar', data);
  },
  eliminarMantenimiento(id: number): Promise<{ success: boolean }> {
    return api.post('/activosfijos/mantenimientos/eliminar', { id_field: id });
  },
};
