import { api } from '@/lib/api-client';
import type { ApiResponse, BodegaRow, StockRow } from '@/lib/api-types';

export const bodegaApi = {
  bodegas(): Promise<ApiResponse<BodegaRow[]>> {
    return api.post<ApiResponse<BodegaRow[]>>('/bodega/bodegas', {});
  },

  kardex(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/bodega/kardex', {});
  },

  stock(): Promise<ApiResponse<StockRow[]>> {
    return api.post<ApiResponse<StockRow[]>>('/bodega/stock', {});
  },

  movimientos(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/bodega/movimientos', {});
  },

  crearBodega(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/bodega/bodegas/crear', data);
  },
  obtenerBodega(id: number): Promise<ApiResponse<BodegaRow>> {
    return api.post('/bodega/bodegas/obtener-por-id', { id_field: id });
  },
  modificarBodega(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/bodega/bodegas/modificar', data);
  },
  eliminarBodega(id: number): Promise<{ success: boolean }> {
    return api.post('/bodega/bodegas/eliminar', { id_field: id });
  },

  crearKardex(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/bodega/kardex/crear', data);
  },
  obtenerKardex(id: number): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/bodega/kardex/obtener-por-id', { id_field: id });
  },
  modificarKardex(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/bodega/kardex/modificar', data);
  },
  eliminarKardex(id: number): Promise<{ success: boolean }> {
    return api.post('/bodega/kardex/eliminar', { id_field: id });
  },

  crearStock(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/bodega/stock/crear', data);
  },
  obtenerStock(id: number): Promise<ApiResponse<StockRow>> {
    return api.post('/bodega/stock/obtener-por-id', { id_field: id });
  },
  modificarStock(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/bodega/stock/modificar', data);
  },
  eliminarStock(id: number): Promise<{ success: boolean }> {
    return api.post('/bodega/stock/eliminar', { id_field: id });
  },

  crearMovimiento(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/bodega/movimientos/crear', data);
  },
  obtenerMovimiento(id: number): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/bodega/movimientos/obtener-por-id', { id_field: id });
  },
  modificarMovimiento(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/bodega/movimientos/modificar', data);
  },
  eliminarMovimiento(id: number): Promise<{ success: boolean }> {
    return api.post('/bodega/movimientos/eliminar', { id_field: id });
  },
};
