import { api } from '@/lib/api-client';
import type { ApiResponse, Tarea, Empleado, Avance, Asignacion, Indicador, TareaAtencion, MetricaRendimiento } from '@/lib/api-types';

export const tareasApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<Tarea[]>> {
    return api.post<ApiResponse<Tarea[]>>('/auditoria/tareas/obtener', params ?? {});
  },

  crear(data: Record<string, unknown>): Promise<{ success: boolean; Tar_Cod?: number; message?: string }> {
    return api.post('/auditoria/tareas/crear', data);
  },

  modificar(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/auditoria/tareas/modificar', data);
  },

  eliminar(Tar_Cod: number): Promise<{ success: boolean; message?: string }> {
    return api.post('/auditoria/tareas/eliminar', { Tar_Cod });
  },

  obtenerPorId(Tar_Cod: number): Promise<ApiResponse<{ row: Tarea | null }>> {
    return api.post('/auditoria/tareas/obtener-por-id', { Tar_Cod });
  },

  obtenerEmpleados(params?: Record<string, unknown>): Promise<ApiResponse<Empleado[]>> {
    return api.post<ApiResponse<Empleado[]>>('/auditoria/tareas/obtener-empleados', params ?? {});
  },

  asignar(Tar_Cod: number, Per_Cod: number): Promise<{ success: boolean; message?: string }> {
    return api.post('/auditoria/tareas/asignar', { Tar_Cod, Per_Cod });
  },

  eliminarAsignacion(Tas_Cod: number): Promise<{ success: boolean; message?: string }> {
    return api.post('/auditoria/tareas/eliminar-asignacion', { Tas_Cod });
  },

  listarAsignaciones(params?: Record<string, unknown>): Promise<ApiResponse<Asignacion[]>> {
    return api.post<ApiResponse<Asignacion[]>>('/auditoria/tareas/listar-asignaciones', params ?? {});
  },

  guardarAvance(data: Record<string, unknown>): Promise<{ success: boolean; message?: string }> {
    return api.post('/auditoria/tareas/guardar-avance', data);
  },

  obtenerAvances(Tar_Cod: number): Promise<ApiResponse<Avance[]>> {
    return api.post<ApiResponse<Avance[]>>('/auditoria/tareas/obtener-avances', { Tar_Cod });
  },

  obtenerMiAvance(Tar_Cod: number): Promise<ApiResponse<{ row: Avance | null }>> {
    return api.post('/auditoria/tareas/obtener-mi-avance', { Tar_Cod });
  },

  indicadores(params?: Record<string, unknown>): Promise<Indicador & { status: boolean }> {
    return api.post('/auditoria/tareas/indicadores', params ?? {});
  },

  tareasAtencion(params?: Record<string, unknown>): Promise<ApiResponse<TareaAtencion[]>> {
    return api.post<ApiResponse<TareaAtencion[]>>('/auditoria/tareas/tareas-atencion', params ?? {});
  },

  metricasRendimiento(params?: Record<string, unknown>): Promise<ApiResponse<MetricaRendimiento[]>> {
    return api.post<ApiResponse<MetricaRendimiento[]>>('/auditoria/tareas/metricas-rendimiento', params ?? {});
  },

  misTareas(): Promise<{ status: boolean; data: Asignacion[]; sin_vinculo?: boolean }> {
    return api.post('/auditoria/tareas/mis-tareas', {});
  },

  listarAdjuntos(Tar_Cod: number): Promise<ApiResponse<Array<{ Adj_Cod: number; Tar_Cod: number; Adj_Nombre: string; Adj_Ruta: string; Adj_Fecha: string }>>> {
    return api.post('/auditoria/tareas/listar-adjuntos', { Tar_Cod });
  },

  reporte(params?: Record<string, unknown>): Promise<ApiResponse<Asignacion[]>> {
    return api.post<ApiResponse<Asignacion[]>>('/auditoria/tareas/reporte', params ?? {});
  },
};
