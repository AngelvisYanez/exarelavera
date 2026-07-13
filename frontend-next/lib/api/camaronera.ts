import { api } from '@/lib/api-client';
import type { ApiResponse, ProductorCamaronera } from '@/lib/api-types';

export const camaroneraApi = {
  productores(): Promise<ApiResponse<ProductorCamaronera[]>> {
    return api.post<ApiResponse<ProductorCamaronera[]>>('/camaronera/productores', {});
  },

  negociaciones(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/camaronera/negociaciones', {});
  },

  liquidaciones(): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/camaronera/liquidaciones', {});
  },

  crearProductor(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/camaronera/productores/crear', data);
  },
  obtenerProductor(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/camaronera/productores/obtener-por-id', { id_field: id });
  },
  modificarProductor(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/camaronera/productores/modificar', data);
  },
  eliminarProductor(id: number | string): Promise<{ success: boolean }> {
    return api.post('/camaronera/productores/eliminar', { id_field: id });
  },

  crearNegociacion(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/camaronera/negociaciones/crear', data);
  },
  obtenerNegociacion(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/camaronera/negociaciones/obtener-por-id', { id_field: id });
  },
  modificarNegociacion(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/camaronera/negociaciones/modificar', data);
  },
  eliminarNegociacion(id: number | string): Promise<{ success: boolean }> {
    return api.post('/camaronera/negociaciones/eliminar', { id_field: id });
  },

  crearLiquidacion(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/camaronera/liquidaciones/crear', data);
  },
  obtenerLiquidacion(id: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/camaronera/liquidaciones/obtener-por-id', { id_field: id });
  },
  modificarLiquidacion(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/camaronera/liquidaciones/modificar', data);
  },
  eliminarLiquidacion(id: number | string): Promise<{ success: boolean }> {
    return api.post('/camaronera/liquidaciones/eliminar', { id_field: id });
  },
};
