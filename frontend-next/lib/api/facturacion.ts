import { api } from '@/lib/api-client';
import type { ApiResponse } from '@/lib/api-types';

export interface ComprobanteRow {
  Vet_Cod: string;
  Vet_Num: string;
  Vet_Aut: string | null;
  Vet_Xml: string | null;
  Vet_Sri: string | null;
  Vet_Obs: string | null;
  Vet_Sys: string;
  Vet_Hor: string | null;
  Vet_Est: string;
  Prs_Nom: string | null;
  Prs_Ced: string | null;
  Aut_Cod: string;
  Pun_Sri: string | null;
  Aut_Sri_Num: string | null;
  Aut_Tem: string | null;
  Tic_Des: string | null;
  Tic_Sri: string | null;
}

export interface RetencionRow {
  Ret_Cod: string;
  Ret_Num: string;
  Ret_Fec: string;
  Ret_Con: string | null;
  Ret_Est: string;
  Ret_Aut: string | null;
  Ret_Xml: string | null;
  Ret_Sri: string | null;
  Ret_Sys: string;
  Aut_Cod: string | null;
  Pun_Sri: string | null;
  Aut_Sri_Num: string | null;
  Tic_Des: string | null;
  Tic_Sri: string | null;
  Prs_Nom: string | null;
  Prs_Ced: string | null;
}

export interface ComprobanteContableRow {
  Com_Cod: string;
  Com_Num: string;
  Com_Fec: string;
  Com_Con: string | null;
  Com_Tip: string;
  Com_Val: string;
  Com_Obs: string | null;
  Com_Est: string;
  Com_Gen: string;
  Com_Mod: string | null;
  Com_Doc: string | null;
  Num_Doc: string | null;
  Com_Sys: string;
  Pec_Cod: string;
  Pec_Fei: string;
  Pec_Fef: string;
  Tia_Des: string | null;
  Tia_Abr: string | null;
  Cli_Nom: string | null;
  Prv_Nom: string | null;
}

export interface GridResponse<T> {
  rows: T[];
  page: number;
  total: number;
  records: number;
  success: boolean;
}

export interface ResumenData {
  facturas: { total: number; electronicos: number };
  retenciones: { total: number; electronicos: number };
  comprobantes: { total: number };
}

export interface SyncResult {
  Vet_Cod?: number;
  Vet_Num?: number;
  Ret_Cod?: number;
  success: boolean;
  numeroAutorizacion?: string;
  message?: string;
  estado?: string;
  claveAcceso?: string;
  fechaAutorizacion?: string;
  informacionAdicional?: string;
  reintentar?: boolean;
  error?: string;
}

export interface EstadoSriResult {
  Vet_Cod?: number;
  Ret_Cod?: number;
  claveAcceso?: string;
  estado_sri: string;
  numeroAutorizacion?: string;
  fechaAutorizacion?: string;
  success: boolean;
  message?: string;
}

export interface BatchResult {
  total: number;
  procesados: number;
  errores: number;
  resultados: Array<{
    codigo: number;
    tipo?: string;
    claveAcceso?: string;
    estado?: string;
    success: boolean;
    numeroAutorizacion?: string;
    message?: string;
  }>;
}

export const facturacionApi = {
  getComprobantes(params?: Record<string, unknown>): Promise<ApiResponse<GridResponse<ComprobanteRow>>> {
    return api.post<ApiResponse<GridResponse<ComprobanteRow>>>('/facturacion/comprobantes', params ?? {});
  },

  getRetenciones(params?: Record<string, unknown>): Promise<ApiResponse<GridResponse<RetencionRow>>> {
    return api.post<ApiResponse<GridResponse<RetencionRow>>>('/facturacion/retenciones', params ?? {});
  },

  getComprobantesContables(params?: Record<string, unknown>): Promise<ApiResponse<GridResponse<ComprobanteContableRow>>> {
    return api.post<ApiResponse<GridResponse<ComprobanteContableRow>>>('/facturacion/comprobantes-contables', params ?? {});
  },

  getResumen(params?: Record<string, unknown>): Promise<ApiResponse<ResumenData>> {
    return api.post<ApiResponse<ResumenData>>('/facturacion/resumen', params ?? {});
  },

  // ── Comprobantes SRI ──

  autorizarComprobante(Vet_Cod: number): Promise<{ status: boolean; data?: SyncResult; error?: string }> {
    return api.post(`/facturacion/comprobantes/${Vet_Cod}/autorizar`);
  },

  estadoSriComprobante(Vet_Cod: number): Promise<{ status: boolean; data?: EstadoSriResult; error?: string }> {
    return api.post(`/facturacion/comprobantes/${Vet_Cod}/estado-sri`);
  },

  reAutorizarComprobante(Vet_Cod: number): Promise<{ status: boolean; data?: SyncResult; error?: string }> {
    return api.post(`/facturacion/comprobantes/${Vet_Cod}/re-autorizar`);
  },

  // ── Retenciones SRI ──

  autorizarRetencion(Ret_Cod: number): Promise<{ status: boolean; data?: SyncResult; error?: string }> {
    return api.post(`/facturacion/retenciones/${Ret_Cod}/autorizar`);
  },

  estadoSriRetencion(Ret_Cod: number): Promise<{ status: boolean; data?: EstadoSriResult; error?: string }> {
    return api.post(`/facturacion/retenciones/${Ret_Cod}/estado-sri`);
  },

  reAutorizarRetencion(Ret_Cod: number): Promise<{ status: boolean; data?: SyncResult; error?: string }> {
    return api.post(`/facturacion/retenciones/${Ret_Cod}/re-autorizar`);
  },

  // ── Emission ──

  emitirComprobante(data: Record<string, unknown>): Promise<{ status: boolean; data?: SyncResult; error?: string }> {
    return api.post('facturacion/emitir/comprobante', data);
  },

  // ── Batch ──

  sincronizarLote(tipo: 'comprobantes' | 'retenciones', codigos: number[]): Promise<{ status: boolean; data?: BatchResult; error?: string }> {
    return api.post('facturacion/sincronizar-lote', { tipo, codigos });
  },
};
