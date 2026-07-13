import { api } from '@/lib/api-client';
import type { ApiResponse } from '@/lib/api-types';

export const transporteCargaApi = {
  viajes(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/transportecarga/viajes', {});
  },

  vehiculos(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/transportecarga/vehiculos', {});
  },

  tickets(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/transportecarga/tickets', {});
  },

  facturasViaje(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/transportecarga/facturas-viaje', {});
  },

  clientesVehiculo(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/transportecarga/clientes-vehiculo', {});
  },

  crearViaje(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/transportecarga/viajes/crear', data);
  },
  obtenerViaje(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/transportecarga/viajes/obtener-por-id', { id_field: id });
  },
  modificarViaje(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/transportecarga/viajes/modificar', data);
  },
  eliminarViaje(id: number | string): Promise<{ success: boolean }> {
    return api.post('/transportecarga/viajes/eliminar', { id_field: id });
  },

  crearVehiculo(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/transportecarga/vehiculos/crear', data);
  },
  obtenerVehiculo(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/transportecarga/vehiculos/obtener-por-id', { id_field: id });
  },
  modificarVehiculo(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/transportecarga/vehiculos/modificar', data);
  },
  eliminarVehiculo(id: number | string): Promise<{ success: boolean }> {
    return api.post('/transportecarga/vehiculos/eliminar', { id_field: id });
  },

  crearTicket(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/transportecarga/tickets/crear', data);
  },
  obtenerTicket(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/transportecarga/tickets/obtener-por-id', { id_field: id });
  },
  modificarTicket(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/transportecarga/tickets/modificar', data);
  },
  eliminarTicket(id: number | string): Promise<{ success: boolean }> {
    return api.post('/transportecarga/tickets/eliminar', { id_field: id });
  },

  crearFacturaViaje(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/transportecarga/facturas-viaje/crear', data);
  },
  obtenerFacturaViaje(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/transportecarga/facturas-viaje/obtener-por-id', { id_field: id });
  },
  modificarFacturaViaje(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/transportecarga/facturas-viaje/modificar', data);
  },
  eliminarFacturaViaje(id: number | string): Promise<{ success: boolean }> {
    return api.post('/transportecarga/facturas-viaje/eliminar', { id_field: id });
  },

  crearClienteVehiculo(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/transportecarga/clientes-vehiculo/crear', data);
  },
  obtenerClienteVehiculo(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/transportecarga/clientes-vehiculo/obtener-por-id', { id_field: id });
  },
  modificarClienteVehiculo(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/transportecarga/clientes-vehiculo/modificar', data);
  },
  eliminarClienteVehiculo(id: number | string): Promise<{ success: boolean }> {
    return api.post('/transportecarga/clientes-vehiculo/eliminar', { id_field: id });
  },
};
