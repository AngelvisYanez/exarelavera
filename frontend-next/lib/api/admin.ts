import { api } from '@/lib/api-client';
import type { ApiResponse, UsuarioRow, Perfil, Sucursal } from '@/lib/api-types';

export const adminApi = {
  usuarios(): Promise<ApiResponse<UsuarioRow[]>> {
    return api.post<ApiResponse<UsuarioRow[]>>('/admin/usuarios', {});
  },

  crearUsuario(data: Record<string, unknown>): Promise<ApiResponse<{ Usu_Cod: number }>> {
    return api.post<ApiResponse<{ Usu_Cod: number }>>('/admin/usuarios/crear', data);
  },

  modificarUsuario(data: Record<string, unknown>): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/admin/usuarios/modificar', data);
  },

  eliminarUsuario(Usu_Cod: number): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/admin/usuarios/eliminar', { Usu_Cod });
  },

  perfiles(): Promise<ApiResponse<Perfil[]>> {
    return api.post<ApiResponse<Perfil[]>>('/admin/perfiles', {});
  },

  crearPerfil(data: Record<string, unknown>): Promise<ApiResponse<{ Per_Cod: number }>> {
    return api.post<ApiResponse<{ Per_Cod: number }>>('/admin/perfiles/crear', data);
  },

  modificarPerfil(data: Record<string, unknown>): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/admin/perfiles/modificar', data);
  },

  eliminarPerfil(Per_Cod: number): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/admin/perfiles/eliminar', { Per_Cod });
  },

  tickets(params?: Record<string, unknown>): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/admin/tickets', params ?? {});
  },

  crearTicket(data: Record<string, unknown>): Promise<ApiResponse<{ Tic_Cod: number }>> {
    return api.post<ApiResponse<{ Tic_Cod: number }>>('/admin/tickets/crear', data);
  },

  cerrarTicket(Tic_Cod: number): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/admin/tickets/cerrar', { Tic_Cod });
  },

  configuracion(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/admin/configuracion', {});
  },

  sucursales(): Promise<ApiResponse<Sucursal[]>> {
    return api.post<ApiResponse<Sucursal[]>>('/admin/sucursales', {});
  },

  crearSucursal(data: Record<string, unknown>): Promise<ApiResponse<{ Suc_Cod: number }>> {
    return api.post<ApiResponse<{ Suc_Cod: number }>>('/admin/sucursales/crear', data);
  },

  modificarSucursal(data: Record<string, unknown>): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/admin/sucursales/modificar', data);
  },

  eliminarSucursal(Suc_Cod: number): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/admin/sucursales/eliminar', { Suc_Cod });
  },

  logActividad(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/admin/log-actividad', {});
  },
};
