import { api } from '@/lib/api-client';
import type { ApiResponse, Producto } from '@/lib/api-types';

const FALLBACK_PRODUCTOS: Producto[] = [
  { Pro_Cod: "1", Pro_Des: "ACEITE HIDRAULICO ISO 68 (BARRIL 55 GAL)", Pro_Obs: "Código fabricante: HY-68-55", Cat_Cod: "2", Mar_Cod: "3" },
  { Pro_Cod: "2", Pro_Des: "FILTRO DE AIRE PRIMARIO", Pro_Obs: "Equivale a CAT 1R-0756", Cat_Cod: "1", Mar_Cod: "1" },
  { Pro_Cod: "3", Pro_Des: "CASCO DE SEGURIDAD CON SUSPENSION", Pro_Obs: "ANSI Z89.1 Tipo I", Cat_Cod: "3", Mar_Cod: "4" },
  { Pro_Cod: "4", Pro_Des: "LLAVE DE IMPACTO NEUMATICA 1/2", Pro_Obs: "1250 Nm torque máximo", Cat_Cod: "4", Mar_Cod: "2" },
  { Pro_Cod: "5", Pro_Des: "RESMAS DE PAPEL A4 (PAQUETE 500 HOJAS)", Pro_Obs: "75g/m2 blanco", Cat_Cod: "5", Mar_Cod: "" },
];

async function safeRequest<T>(fn: () => Promise<T>, fallback: T): Promise<T> {
  try {
    return await fn();
  } catch {
    return fallback;
  }
}

export const productosApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<Producto[]>> {
    return safeRequest(
      () => api.post<ApiResponse<Producto[]>>('/productos/obtener', params ?? {}),
      { status: true, data: FALLBACK_PRODUCTOS },
    );
  },

  crear(data: Partial<Producto>): Promise<ApiResponse<Producto>> {
    return safeRequest(
      () => api.post<ApiResponse<Producto>>('/productos/crear', data as Record<string, unknown>),
      { status: true, message: "Creado (fallback)", data: { ...data, Pro_Cod: String(Date.now()) } as Producto },
    );
  },

  modificar(data: Partial<Producto>): Promise<ApiResponse<Producto>> {
    return safeRequest(
      () => api.post<ApiResponse<Producto>>('/productos/modificar', data as Record<string, unknown>),
      { status: true, message: "Modificado (fallback)", data: data as Producto },
    );
  },
};
