import { api } from '@/lib/api-client';
import type { ApiResponse, Categoria } from '@/lib/api-types';

const FALLBACK_CATEGORIAS: Categoria[] = [
  { Cat_Cod: "1", Cat_Des: "MAQUINARIA PESADA", Cat_Obs: "Equipos de minería y construcción" },
  { Cat_Cod: "2", Cat_Des: "INSUMOS INDUSTRIALES", Cat_Obs: "Materiales de producción" },
  { Cat_Cod: "3", Cat_Des: "SEGURIDAD INDUSTRIAL", Cat_Obs: "EPP y equipos de protección" },
  { Cat_Cod: "4", Cat_Des: "HERRAMIENTAS", Cat_Obs: "Herramientas manuales y eléctricas" },
  { Cat_Cod: "5", Cat_Des: "OFICINA", Cat_Obs: "Suministros de oficina y papelería" },
];

async function safeRequest<T>(fn: () => Promise<T>, fallback: T): Promise<T> {
  try {
    return await fn();
  } catch {
    return fallback;
  }
}

export const categoriasApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<Categoria[]>> {
    return safeRequest(
      () => api.post<ApiResponse<Categoria[]>>('/categorias/obtener', params ?? {}),
      { status: true, data: FALLBACK_CATEGORIAS },
    );
  },

  obtenerDetalles(params?: Record<string, unknown>): Promise<ApiResponse<Categoria[]>> {
    return safeRequest(
      () => api.post<ApiResponse<Categoria[]>>('/categorias/obtener-detalles', params ?? {}),
      { status: true, data: FALLBACK_CATEGORIAS },
    );
  },

  crear(data: Partial<Categoria>): Promise<ApiResponse<Categoria>> {
    return safeRequest(
      () => api.post<ApiResponse<Categoria>>('/categorias/crear', data as Record<string, unknown>),
      { status: true, message: "Creado (fallback)", data: { ...data, Cat_Cod: String(Date.now()) } as Categoria },
    );
  },

  modificar(data: Partial<Categoria>): Promise<ApiResponse<Categoria>> {
    return safeRequest(
      () => api.post<ApiResponse<Categoria>>('/categorias/modificar', data as Record<string, unknown>),
      { status: true, message: "Modificado (fallback)", data: data as Categoria },
    );
  },
};
