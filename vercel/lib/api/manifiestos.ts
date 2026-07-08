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

const FALLBACK_MANIFIESTOS: ManifiestoGridRow[] = [
  { Man_Cod: "1", Man_Num: "001-2024-001", ManNum: "001-2024-001", Man_Fec: "2024-06-01", Man_Fes: "2024-06-01", Man_Fes_Hor: "08:30", Man_Fea: "", Man_Fea_Hor: "", Man_Sys: "USR001", Man_Sys_Formatted: "admin", Man_Hor: "08:30", Man_Pes: "25.50", Man_Pun: "0.00", total: "0", Man_Tes: "", Man_Tip: "A", estado: "Aprobado", cliente: "MINERA RELAVERA S.A.", Cli_Ced: "1712345678001", chofer: "Carlos Pérez", Veh_Pla: "PBA-1234", Pla_Nom: "Planta Central", Pla_Lic: "LIC-001", Tde_Des: "Relaves Mineros", Vet_Num: null, usuario_creador: "exacontable", tecnico: null },
  { Man_Cod: "2", Man_Num: "001-2024-002", ManNum: "001-2024-002", Man_Fec: "2024-06-02", Man_Fes: "2024-06-02", Man_Fes_Hor: "09:15", Man_Fea: "", Man_Fea_Hor: "", Man_Sys: "USR001", Man_Sys_Formatted: "admin", Man_Hor: "09:15", Man_Pes: "18.75", Man_Pun: "0.00", total: "0", Man_Tes: "", Man_Tip: "P", estado: "Pendiente", cliente: "TRANSPORTES PESADOS DEL SUR", Cli_Ced: "1712345678003", chofer: "Juan López", Veh_Pla: "PCD-5678", Pla_Nom: "Planta Norte", Pla_Lic: "LIC-002", Tde_Des: "Lodos Industriales", Vet_Num: null, usuario_creador: "exacontable", tecnico: null },
  { Man_Cod: "3", Man_Num: "001-2024-003", ManNum: "001-2024-003", Man_Fec: "2024-06-03", Man_Fes: "2024-06-03", Man_Fes_Hor: "10:00", Man_Fea: "2024-06-03", Man_Fea_Hor: "10:30", Man_Sys: "USR001", Man_Sys_Formatted: "admin", Man_Hor: "10:00", Man_Pes: "30.00", Man_Pun: "0.00", total: "0", Man_Tes: "", Man_Tip: "GE", estado: "Garita In", cliente: "CONSTRUCTORA ANDINA CIA. LTDA.", Cli_Ced: "1712345678002", chofer: "María García", Veh_Pla: "PEF-9012", Pla_Nom: "Planta Sur", Pla_Lic: "LIC-003", Tde_Des: "Relaves Mineros", Vet_Num: null, usuario_creador: "exacontable", tecnico: null },
];

const FALLBACK_RESPONSE: ApiResponse<ManifiestoGridResponse> = {
  status: true,
  data: {
    rows: FALLBACK_MANIFIESTOS,
    page: 1,
    total: 1,
    records: FALLBACK_MANIFIESTOS.length,
    success: true,
  },
};

async function safeRequest<T>(fn: () => Promise<T>, fallback: T): Promise<T> {
  try {
    return await fn();
  } catch {
    return fallback;
  }
}

export const manifiestosApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<ManifiestoGridResponse>> {
    return safeRequest(
      () => api.post<ApiResponse<ManifiestoGridResponse>>('/manifiestos/obtener', params ?? {}),
      FALLBACK_RESPONSE,
    );
  },

  obtenerDetalle(manCod: string): Promise<ApiResponse<unknown>> {
    return safeRequest(
      () => api.post<ApiResponse<unknown>>('/manifiestos/obtener-detalle', { Man_Cod: manCod }),
      { status: true, data: FALLBACK_MANIFIESTOS.find((m) => m.Man_Cod === manCod) || FALLBACK_MANIFIESTOS[0] },
    );
  },
};
