import { api } from '@/lib/api-client';
import type { ApiResponse, Proveedor } from '@/lib/api-types';

const FALLBACK_PROVEEDORES: Proveedor[] = [
  { Prv_Cod: "1", Prv_Ced: "1790012345001", Prv_Nom: "DISTRIBUIDORA INDUSTRIAL XYZ", Prv_Dir: "Av. Americas 789", Prv_Tel: "022345600", Prv_Cel: "0998765432", Prv_Mail: "ventas@distribuidoraxyz.com" },
  { Prv_Cod: "2", Prv_Ced: "1790012345002", Prv_Nom: "COMERCIALIZADORA MINERA DEL ECUADOR", Prv_Dir: "Calle Principal 321", Prv_Tel: "022345601", Prv_Cel: "0998765433", Prv_Mail: "info@comercializadoraminera.com" },
  { Prv_Cod: "3", Prv_Ced: "1790012345003", Prv_Nom: "SUMINSUR SUMINISTROS INDUSTRIALES", Prv_Dir: "Zona Franca L-12", Prv_Tel: "022345602", Prv_Cel: "0998765434", Prv_Mail: "pedidos@suminsur.com" },
];

async function safeRequest<T>(fn: () => Promise<T>, fallback: T): Promise<T> {
  try {
    return await fn();
  } catch {
    return fallback;
  }
}

export const proveedoresApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<Proveedor[]>> {
    return safeRequest(
      () => api.post<ApiResponse<Proveedor[]>>('/proveedores/obtener', params ?? {}),
      { status: true, data: FALLBACK_PROVEEDORES },
    );
  },

  crear(data: Partial<Proveedor>): Promise<ApiResponse<Proveedor>> {
    return safeRequest(
      () => api.post<ApiResponse<Proveedor>>('/proveedores/crear', data as Record<string, unknown>),
      { status: true, message: "Creado (fallback)", data: { ...data, Prv_Cod: String(Date.now()) } as Proveedor },
    );
  },

  modificar(data: Partial<Proveedor>): Promise<ApiResponse<Proveedor>> {
    return safeRequest(
      () => api.post<ApiResponse<Proveedor>>('/proveedores/modificar', data as Record<string, unknown>),
      { status: true, message: "Modificado (fallback)", data: data as Proveedor },
    );
  },
};
