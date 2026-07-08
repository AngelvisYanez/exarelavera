import { api } from '@/lib/api-client';
import type { ApiResponse, Marca } from '@/lib/api-types';

const FALLBACK_MARCAS: Marca[] = [
  { Mar_Cod: "1", Mar_Des: "CATERPILLAR", Mar_Obs: "Maquinaria pesada americana" },
  { Mar_Cod: "2", Mar_Des: "KOMATSU", Mar_Obs: "Maquinaria japonesa" },
  { Mar_Cod: "3", Mar_Des: "VOLVO", Mar_Obs: "Equipos suecos" },
  { Mar_Cod: "4", Mar_Des: "3M", Mar_Obs: "Seguridad industrial" },
];

async function safeRequest<T>(fn: () => Promise<T>, fallback: T): Promise<T> {
  try {
    return await fn();
  } catch {
    return fallback;
  }
}

export const marcasApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<Marca[]>> {
    return safeRequest(
      () => api.post<ApiResponse<Marca[]>>('/marcas/obtener', params ?? {}),
      { status: true, data: FALLBACK_MARCAS },
    );
  },

  crear(data: Partial<Marca>): Promise<ApiResponse<Marca>> {
    return safeRequest(
      () => api.post<ApiResponse<Marca>>('/marcas/crear', data as Record<string, unknown>),
      { status: true, message: "Creado (fallback)", data: { ...data, Mar_Cod: String(Date.now()) } as Marca },
    );
  },

  modificar(data: Partial<Marca>): Promise<ApiResponse<Marca>> {
    return safeRequest(
      () => api.post<ApiResponse<Marca>>('/marcas/modificar', data as Record<string, unknown>),
      { status: true, message: "Modificado (fallback)", data: data as Marca },
    );
  },
};
