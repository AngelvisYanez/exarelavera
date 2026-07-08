import { api } from '@/lib/api-client';
import type { ApiResponse, Cliente } from '@/lib/api-types';

const FALLBACK_CLIENTES: Cliente[] = [
  { Cli_Cod: "1", Cli_Ced: "1712345678001", Cli_Nom: "MINERA RELAVERA S.A.", Cli_Dir: "Av. Principal Km 15", Cli_Tel: "022345678", Cli_Cel: "0991234567", Cli_Mail: "info@minerarelavera.com", Cli_Obs: "Cliente principal" },
  { Cli_Cod: "2", Cli_Ced: "1712345678002", Cli_Nom: "CONSTRUCTORA ANDINA CIA. LTDA.", Cli_Dir: "Calle Secundaria OE-123", Cli_Tel: "022345679", Cli_Cel: "0991234568", Cli_Mail: "contacto@constructoraandina.com", Cli_Obs: "" },
  { Cli_Cod: "3", Cli_Ced: "1712345678003", Cli_Nom: "TRANSPORTES PESADOS DEL SUR", Cli_Dir: "Panamericana Sur Km 8", Cli_Tel: "022345680", Cli_Cel: "0991234569", Cli_Mail: "logistica@tpsur.com", Cli_Obs: "Pago a 30 días" },
  { Cli_Cod: "4", Cli_Ced: "1712345678004", Cli_Nom: "INDUSTRIAS METALURGICAS LOJA", Cli_Dir: "Zona Industrial L-4", Cli_Tel: "022345681", Cli_Cel: "0991234570", Cli_Mail: "compras@industriasloja.com", Cli_Obs: "" },
  { Cli_Cod: "5", Cli_Ced: "1712345678005", Cli_Nom: "SERVICIOS AMBIENTALES ECOLOGICOS", Cli_Dir: "Via a la Costa 456", Cli_Tel: "022345682", Cli_Cel: "0991234571", Cli_Mail: "contacto@serviciosambientales.com", Cli_Obs: "Descuento 5%" },
];

async function safeRequest<T>(fn: () => Promise<T>, fallback: T): Promise<T> {
  try {
    return await fn();
  } catch {
    return fallback;
  }
}

export const clientesApi = {
  obtener(params?: Record<string, unknown>): Promise<ApiResponse<Cliente[]>> {
    return safeRequest(
      () => api.post<ApiResponse<Cliente[]>>('/clientes/obtener', params ?? {}),
      { status: true, data: FALLBACK_CLIENTES },
    );
  },

  crear(data: Partial<Cliente>): Promise<ApiResponse<Cliente>> {
    return safeRequest(
      () => api.post<ApiResponse<Cliente>>('/clientes/crear', data as Record<string, unknown>),
      { status: true, message: "Creado (fallback)", data: { ...data, Cli_Cod: String(Date.now()) } as Cliente },
    );
  },

  modificar(data: Partial<Cliente>): Promise<ApiResponse<Cliente>> {
    return safeRequest(
      () => api.post<ApiResponse<Cliente>>('/clientes/modificar', data as Record<string, unknown>),
      { status: true, message: "Modificado (fallback)", data: data as Cliente },
    );
  },
};
