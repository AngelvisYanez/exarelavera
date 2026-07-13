import { api } from '@/lib/api-client';
import type { ApiResponse } from '@/lib/api-types';

export interface ManifiestoGridRow {
  Man_Cod: string;
  Man_Num: string;
  ManNum: string;
  Man_Fec: string;
  Man_Fes: string;
  Man_Fes_Hor: string;
  Man_Fea: string;
  Man_Fea_Hor: string;
  Man_Sys: string;
  Man_Sys_Formatted: string;
  Man_Hor: string;
  Man_Pes: string;
  Man_Pun: string;
  total: string;
  Man_Tes: string;
  Man_Tip: string;
  estado: string;
  cliente: string;
  Cli_Ced: string;
  chofer: string;
  Veh_Pla: string;
  Pla_Nom: string;
  Pla_Lic: string;
  Tde_Des: string;
  Vet_Num: string | null;
  usuario_creador: string;
  tecnico: string | null;
}

export interface ManifiestoGridResponse {
  rows: ManifiestoGridRow[];
  page: number;
  total: number;
  records: number;
  success: boolean;
}

export const manifiestosApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<ManifiestoGridResponse>> {
    return api.post<ApiResponse<ManifiestoGridResponse>>('/manifiestos/obtener', params ?? {});
  },

  obtenerDetalle(manCod: string): Promise<ApiResponse<unknown>> {
    return api.post<ApiResponse<unknown>>('/manifiestos/obtener-detalle', { Man_Cod: manCod });
  },

  crearManifiesto(data: Record<string, unknown>): Promise<{ success: boolean; id?: number }> {
    return api.post('/manifiestos/crear', data);
  },
  obtenerManifiestoPorId(manCod: string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/manifiestos/obtener-por-id-api', { Man_Cod: manCod });
  },
  modificarManifiesto(data: Record<string, unknown>): Promise<{ success: boolean }> {
    return api.post('/manifiestos/modificar', data);
  },
  eliminarManifiesto(manCod: string): Promise<{ success: boolean }> {
    return api.post('/manifiestos/eliminar', { Man_Cod: manCod });
  },
};
