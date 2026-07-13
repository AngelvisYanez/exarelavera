import { api } from '@/lib/api-client';
import type { ApiResponse, UsuarioRow, Perfil, Sucursal } from '@/lib/api-types';

export const adminApi = {
  usuarios(): Promise<ApiResponse<UsuarioRow[]>> {
    return api.post<ApiResponse<UsuarioRow[]>>('/admin/usuarios', {});
  },

  perfiles(): Promise<ApiResponse<Perfil[]>> {
    return api.post<ApiResponse<Perfil[]>>('/admin/perfiles', {});
  },

  tickets(params?: Record<string, unknown>): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/admin/tickets', params ?? {});
  },

  configuracion(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/admin/configuracion', {});
  },

  sucursales(): Promise<ApiResponse<Sucursal[]>> {
    return api.post<ApiResponse<Sucursal[]>>('/admin/sucursales', {});
  },

  logActividad(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/admin/log-actividad', {});
  },
};
