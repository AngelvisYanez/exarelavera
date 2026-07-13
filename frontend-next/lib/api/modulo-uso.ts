import { api } from "@/lib/api-client";
import type { ModuloUsoResponse, ModuloUsoFiltros } from "@/lib/api-types";

export const moduloUsoApi = {
  obtenerStats(filtros: ModuloUsoFiltros = {}): Promise<ModuloUsoResponse> {
    const body: Record<string, unknown> = {};
    if (filtros.fecha_desde) body.fecha_desde = filtros.fecha_desde;
    if (filtros.fecha_hasta) body.fecha_hasta = filtros.fecha_hasta;
    if (filtros.ruc_cliente) body.ruc_cliente = filtros.ruc_cliente;
    return api.post<ModuloUsoResponse>("/admin/modulo-uso", body);
  },
};
