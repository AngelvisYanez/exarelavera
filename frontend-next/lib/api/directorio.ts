import { api } from '@/lib/api-client';
import type { ApiResponse } from '@/lib/api-types';

export interface DirectorioModulo {
  Dir_Cod: number;
  Dir_Nom: string;
  Dir_Rut: string;
  Dir_Tip: string;
  Dir_Est: string;
  Dir_Des: string;
  Dir_Ver: string;
  Dir_Aut: string;
  Emp_Cod: number;
}

export interface ProcesoSistema {
  Pcs_Cod: number;
  Pcs_Lin: string;
  Pcs_Det: string;
  Pcs_Tip: string;
  Pcs_Est: string;
}

export const directorioApi = {
  obtener(): Promise<ApiResponse<DirectorioModulo[]>> {
    return api.post<ApiResponse<DirectorioModulo[]>>('/admin/directorio/obtener', {});
  },

  crear(data: Partial<DirectorioModulo>): Promise<ApiResponse<{ Dir_Cod: number }>> {
    return api.post<ApiResponse<{ Dir_Cod: number }>>('/admin/directorio/crear', data as Record<string, unknown>);
  },

  modificar(data: Partial<DirectorioModulo>): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/admin/directorio/modificar', data as Record<string, unknown>);
  },

  eliminar(Dir_Cod: number): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/admin/directorio/eliminar', { Dir_Cod });
  },
};

export const procesosApi = {
  obtener(): Promise<ApiResponse<ProcesoSistema[]>> {
    return api.post<ApiResponse<ProcesoSistema[]>>('/admin/procesos/obtener', {});
  },

  crear(data: Partial<ProcesoSistema>): Promise<ApiResponse<{ Pcs_Cod: number }>> {
    return api.post<ApiResponse<{ Pcs_Cod: number }>>('/admin/procesos/crear', data as Record<string, unknown>);
  },

  modificar(data: Partial<ProcesoSistema>): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/admin/procesos/modificar', data as Record<string, unknown>);
  },

  eliminar(Pcs_Cod: number): Promise<ApiResponse<void>> {
    return api.post<ApiResponse<void>>('/admin/procesos/eliminar', { Pcs_Cod });
  },
};
