import { api } from '@/lib/api-client';
import type { ApiResponse, PlanCuenta, PeriodoContable, ComprobanteContable, TipoComprobante } from '@/lib/api-types';

export type { PlanCuenta, PeriodoContable, ComprobanteContable as ComprobanteContRow, TipoComprobante };

export const contabilidadApi = {
  planCuentas(): Promise<ApiResponse<PlanCuenta[]>> {
    return api.post<ApiResponse<PlanCuenta[]>>('/contabilidad/plan-cuentas', {});
  },

  periodos(): Promise<ApiResponse<PeriodoContable[]>> {
    return api.post<ApiResponse<PeriodoContable[]>>('/contabilidad/periodos', {});
  },

  comprobantes(params?: Record<string, unknown>): Promise<ApiResponse<ComprobanteContable[]>> {
    return api.post<ApiResponse<ComprobanteContable[]>>('/contabilidad/comprobantes', params ?? {});
  },

  detallesComprobante(Com_Cod: number): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/contabilidad/detalles-comprobante', { Com_Cod });
  },

  tiposComprobante(): Promise<ApiResponse<TipoComprobante[]>> {
    return api.post<ApiResponse<TipoComprobante[]>>('/contabilidad/tipos-comprobante', {});
  },

  balanceComprobacion(Pec_Cod: number): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/contabilidad/balance-comprobacion', { Pec_Cod });
  },

  mayorCuenta(Pla_Cod: number, Pec_Cod: number): Promise<ApiResponse<Record<string, unknown>[]>> {
    return api.post<ApiResponse<Record<string, unknown>[]>>('/contabilidad/mayor-cuenta', { Pla_Cod, Pec_Cod });
  },

  // Plan de Cuentas CRUD
  crearPlanCuenta(data: Record<string, unknown>): Promise<{ success: boolean; id?: number; message?: string }> {
    return api.post('/contabilidad/plan-cuentas/crear', data);
  },
  obtenerPlanCuenta(Pla_Cod: number | string): Promise<ApiResponse<PlanCuenta>> {
    return api.post('/contabilidad/plan-cuentas/obtener-por-id', { Pla_Cod });
  },
  modificarPlanCuenta(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/contabilidad/plan-cuentas/modificar', data);
  },
  eliminarPlanCuenta(Pla_Cod: number | string): Promise<{ success: boolean; message?: string }> {
    return api.post('/contabilidad/plan-cuentas/eliminar', { Pla_Cod });
  },

  // Periodos Contables CRUD
  crearPeriodo(data: Record<string, unknown>): Promise<{ success: boolean; id?: number; message?: string }> {
    return api.post('/contabilidad/periodos/crear', data);
  },
  obtenerPeriodo(Pec_Cod: number | string): Promise<ApiResponse<PeriodoContable>> {
    return api.post('/contabilidad/periodos/obtener-por-id', { Pec_Cod });
  },
  modificarPeriodo(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/contabilidad/periodos/modificar', data);
  },
  eliminarPeriodo(Pec_Cod: number | string): Promise<{ success: boolean; message?: string }> {
    return api.post('/contabilidad/periodos/eliminar', { Pec_Cod });
  },

  // Comprobantes Contables CRUD
  crearComprobante(data: Record<string, unknown>): Promise<{ success: boolean; id?: number; message?: string }> {
    return api.post('/contabilidad/comprobantes/crear', data);
  },
  obtenerComprobante(Com_Cod: number | string): Promise<ApiResponse<ComprobanteContable>> {
    return api.post('/contabilidad/comprobantes/obtener-por-id', { Com_Cod });
  },
  modificarComprobante(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/contabilidad/comprobantes/modificar', data);
  },
  eliminarComprobante(Com_Cod: number | string): Promise<{ success: boolean; message?: string }> {
    return api.post('/contabilidad/comprobantes/eliminar', { Com_Cod });
  },

  // Detalles Comprobante CRUD
  crearDetalleComprobante(data: Record<string, unknown>): Promise<{ success: boolean; id?: number; message?: string }> {
    return api.post('/contabilidad/detalles-comprobante/crear', data);
  },
  obtenerDetalleComprobante(Det_Cod: number | string): Promise<ApiResponse<Record<string, unknown>>> {
    return api.post('/contabilidad/detalles-comprobante/obtener-por-id', { Det_Cod });
  },
  modificarDetalleComprobante(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/contabilidad/detalles-comprobante/modificar', data);
  },
  eliminarDetalleComprobante(Det_Cod: number | string): Promise<{ success: boolean; message?: string }> {
    return api.post('/contabilidad/detalles-comprobante/eliminar', { Det_Cod });
  },

  // Tipos Comprobante CRUD
  crearTipoComprobante(data: Record<string, unknown>): Promise<{ success: boolean; id?: number; message?: string }> {
    return api.post('/contabilidad/tipos-comprobante/crear', data);
  },
  obtenerTipoComprobante(Tia_Cod: number | string): Promise<ApiResponse<TipoComprobante>> {
    return api.post('/contabilidad/tipos-comprobante/obtener-por-id', { Tia_Cod });
  },
  modificarTipoComprobante(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/contabilidad/tipos-comprobante/modificar', data);
  },
  eliminarTipoComprobante(Tia_Cod: number | string): Promise<{ success: boolean; message?: string }> {
    return api.post('/contabilidad/tipos-comprobante/eliminar', { Tia_Cod });
  },
};
