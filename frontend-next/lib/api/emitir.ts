import { api } from '@/lib/api-client';
import type { ApiResponse } from '@/lib/api-types';

export const emitirApi = {
  buscarProductos(params?: Record<string, unknown>): Promise<ApiResponse> {
    return api.post<ApiResponse>('/facturacion/emitir/productos/buscar', params ?? {});
  },

  crearProducto(data: Record<string, unknown>): Promise<ApiResponse & { data?: { Pro_Cod: number; Pro_Des: string; Pro_Ide: string; Iva_Cod: number } }> {
    return api.post('/facturacion/emitir/productos/crear', data);
  },
};
