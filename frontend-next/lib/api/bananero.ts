import { api } from '@/lib/api-client';
import type { ApiResponse, ProductorBanano, Naviera } from '@/lib/api-types';

export const bananeroApi = {
  productores(): Promise<ApiResponse<ProductorBanano[]>> {
    return api.post<ApiResponse<ProductorBanano[]>>('/bananero/productores', {});
  },

  liquidaciones(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/bananero/liquidaciones', {});
  },

  exportaciones(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/bananero/exportaciones', {});
  },

  labores(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/bananero/labores', {});
  },

  viajesExportacion(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/bananero/viajes-exportacion', {});
  },

  marcas(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/bananero/marcas', {});
  },

  navieras(): Promise<ApiResponse<Naviera[]>> {
    return api.post<ApiResponse<Naviera[]>>('/bananero/navieras', {});
  },

  crearProductor(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/bananero/productores/crear', data);
  },
  obtenerProductor(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/bananero/productores/obtener-por-id', { id_field: id });
  },
  modificarProductor(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/bananero/productores/modificar', data);
  },
  eliminarProductor(id: number | string): Promise<{ success: boolean }> {
    return api.post('/bananero/productores/eliminar', { id_field: id });
  },

  crearMarca(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/bananero/marcas/crear', data);
  },
  obtenerMarca(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/bananero/marcas/obtener-por-id', { id_field: id });
  },
  modificarMarca(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/bananero/marcas/modificar', data);
  },
  eliminarMarca(id: number | string): Promise<{ success: boolean }> {
    return api.post('/bananero/marcas/eliminar', { id_field: id });
  },

  crearNaviera(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/bananero/navieras/crear', data);
  },
  obtenerNaviera(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/bananero/navieras/obtener-por-id', { id_field: id });
  },
  modificarNaviera(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/bananero/navieras/modificar', data);
  },
  eliminarNaviera(id: number | string): Promise<{ success: boolean }> {
    return api.post('/bananero/navieras/eliminar', { id_field: id });
  },

  crearLiquidacion(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/bananero/liquidaciones/crear', data);
  },
  obtenerLiquidacion(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/bananero/liquidaciones/obtener-por-id', { id_field: id });
  },
  modificarLiquidacion(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/bananero/liquidaciones/modificar', data);
  },
  eliminarLiquidacion(id: number | string): Promise<{ success: boolean }> {
    return api.post('/bananero/liquidaciones/eliminar', { id_field: id });
  },

  crearExportacion(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/bananero/exportaciones/crear', data);
  },
  obtenerExportacion(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/bananero/exportaciones/obtener-por-id', { id_field: id });
  },
  modificarExportacion(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/bananero/exportaciones/modificar', data);
  },
  eliminarExportacion(id: number | string): Promise<{ success: boolean }> {
    return api.post('/bananero/exportaciones/eliminar', { id_field: id });
  },

  crearLabor(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/bananero/labores/crear', data);
  },
  obtenerLabor(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/bananero/labores/obtener-por-id', { id_field: id });
  },
  modificarLabor(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/bananero/labores/modificar', data);
  },
  eliminarLabor(id: number | string): Promise<{ success: boolean }> {
    return api.post('/bananero/labores/eliminar', { id_field: id });
  },

  crearViajeExportacion(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/bananero/viajes-exportacion/crear', data);
  },
  obtenerViajeExportacion(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/bananero/viajes-exportacion/obtener-por-id', { id_field: id });
  },
  modificarViajeExportacion(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/bananero/viajes-exportacion/modificar', data);
  },
  eliminarViajeExportacion(id: number | string): Promise<{ success: boolean }> {
    return api.post('/bananero/viajes-exportacion/eliminar', { id_field: id });
  },
};
