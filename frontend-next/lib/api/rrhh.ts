import { api } from '@/lib/api-client';
import type { ApiResponse, PersonalRow, ContratoRow, RolPagoRow } from '@/lib/api-types';

export const rrhhApi = {
  personal(): Promise<ApiResponse<PersonalRow[]>> {
    return api.post<ApiResponse<PersonalRow[]>>('/rrhh/personal', {});
  },

  contratos(): Promise<ApiResponse<ContratoRow[]>> {
    return api.post<ApiResponse<ContratoRow[]>>('/rrhh/contratos', {});
  },

  rolesPago(params?: Record<string, unknown>): Promise<ApiResponse<RolPagoRow[]>> {
    return api.post<ApiResponse<RolPagoRow[]>>('/rrhh/roles-pago', params ?? {});
  },

  departamentos(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/rrhh/departamentos', {});
  },

  cargos(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/rrhh/cargos', {});
  },

  // Departamentos CRUD
  crearDepartamento(data: Record<string, unknown>): Promise<{ success: boolean; id?: number; message?: string }> {
    return api.post('/rrhh/departamentos/crear', data);
  },
  obtenerDepartamento(Dep_Cod: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/rrhh/departamentos/obtener-por-id', { Dep_Cod });
  },
  modificarDepartamento(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/rrhh/departamentos/modificar', data);
  },
  eliminarDepartamento(Dep_Cod: number | string): Promise<{ success: boolean; message?: string }> {
    return api.post('/rrhh/departamentos/eliminar', { Dep_Cod });
  },

  // Cargos CRUD
  crearCargo(data: Record<string, unknown>): Promise<{ success: boolean; id?: number; message?: string }> {
    return api.post('/rrhh/cargos/crear', data);
  },
  obtenerCargo(Car_Cod: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/rrhh/cargos/obtener-por-id', { Car_Cod });
  },
  modificarCargo(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/rrhh/cargos/modificar', data);
  },
  eliminarCargo(Car_Cod: number | string): Promise<{ success: boolean; message?: string }> {
    return api.post('/rrhh/cargos/eliminar', { Car_Cod });
  },

  // Personal CRUD
  crearPersonal(data: Record<string, unknown>): Promise<{ success: boolean; id?: number; message?: string }> {
    return api.post('/rrhh/personal/crear', data);
  },
  obtenerPersonal(Per_Cod: number | string): Promise<ApiResponse<PersonalRow>> {
    return api.post('/rrhh/personal/obtener-por-id', { Per_Cod });
  },
  modificarPersonal(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/rrhh/personal/modificar', data);
  },
  eliminarPersonal(Per_Cod: number | string): Promise<{ success: boolean; message?: string }> {
    return api.post('/rrhh/personal/eliminar', { Per_Cod });
  },

  // Contratos CRUD
  crearContrato(data: Record<string, unknown>): Promise<{ success: boolean; id?: number; message?: string }> {
    return api.post('/rrhh/contratos/crear', data);
  },
  obtenerContrato(Con_Cod: number | string): Promise<ApiResponse<ContratoRow>> {
    return api.post('/rrhh/contratos/obtener-por-id', { Con_Cod });
  },
  modificarContrato(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/rrhh/contratos/modificar', data);
  },
  eliminarContrato(Con_Cod: number | string): Promise<{ success: boolean; message?: string }> {
    return api.post('/rrhh/contratos/eliminar', { Con_Cod });
  },

  // Roles de Pago CRUD
  crearRolPago(data: Record<string, unknown>): Promise<{ success: boolean; id?: number; message?: string }> {
    return api.post('/rrhh/roles-pago/crear', data);
  },
  obtenerRolPago(Rol_Cod: number | string): Promise<ApiResponse<RolPagoRow>> {
    return api.post('/rrhh/roles-pago/obtener-por-id', { Rol_Cod });
  },
  modificarRolPago(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/rrhh/roles-pago/modificar', data);
  },
  eliminarRolPago(Rol_Cod: number | string): Promise<{ success: boolean; message?: string }> {
    return api.post('/rrhh/roles-pago/eliminar', { Rol_Cod });
  },
};
