import { api } from '@/lib/api-client';
import type { ApiResponse, Banco } from '@/lib/api-types';

export const tesoreriaApi = {
  bancos(): Promise<ApiResponse<Banco[]>> {
    return api.post<ApiResponse<Banco[]>>('/tesoreria/bancos', {});
  },

  cuentasBanco(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/tesoreria/cuentas-banco', {});
  },

  cheques(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/tesoreria/cheques', {});
  },

  conciliacion(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/tesoreria/conciliacion', {});
  },

  cccp(params?: Record<string, unknown>): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/tesoreria/cccp', params ?? {});
  },

  crearBanco(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/tesoreria/bancos/crear', data);
  },
  obtenerBanco(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/tesoreria/bancos/obtener-por-id', { id_field: id });
  },
  modificarBanco(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/tesoreria/bancos/modificar', data);
  },
  eliminarBanco(id: number | string): Promise<{ success: boolean }> {
    return api.post('/tesoreria/bancos/eliminar', { id_field: id });
  },

  crearCuentaBanco(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/tesoreria/cuentas-banco/crear', data);
  },
  obtenerCuentaBanco(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/tesoreria/cuentas-banco/obtener-por-id', { id_field: id });
  },
  modificarCuentaBanco(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/tesoreria/cuentas-banco/modificar', data);
  },
  eliminarCuentaBanco(id: number | string): Promise<{ success: boolean }> {
    return api.post('/tesoreria/cuentas-banco/eliminar', { id_field: id });
  },

  crearCheque(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/tesoreria/cheques/crear', data);
  },
  obtenerCheque(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/tesoreria/cheques/obtener-por-id', { id_field: id });
  },
  modificarCheque(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/tesoreria/cheques/modificar', data);
  },
  eliminarCheque(id: number | string): Promise<{ success: boolean }> {
    return api.post('/tesoreria/cheques/eliminar', { id_field: id });
  },

  crearConciliacion(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/tesoreria/conciliacion/crear', data);
  },
  obtenerConciliacion(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/tesoreria/conciliacion/obtener-por-id', { id_field: id });
  },
  modificarConciliacion(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/tesoreria/conciliacion/modificar', data);
  },
  eliminarConciliacion(id: number | string): Promise<{ success: boolean }> {
    return api.post('/tesoreria/conciliacion/eliminar', { id_field: id });
  },

  crearCccp(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/tesoreria/cccp/crear', data);
  },
  obtenerCccp(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/tesoreria/cccp/obtener-por-id', { id_field: id });
  },
  modificarCccp(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/tesoreria/cccp/modificar', data);
  },
  eliminarCccp(id: number | string): Promise<{ success: boolean }> {
    return api.post('/tesoreria/cccp/eliminar', { id_field: id });
  },
};
