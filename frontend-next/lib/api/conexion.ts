import { api } from "@/lib/api-client";
import type {
  EstadoConexion,
  PerfilesResponse,
  AccionResponse,
  ResultadoTestConexion,
  ConexionPerfilCompleto,
} from "@/lib/api-types";

export const conexionApi = {
  estado(): Promise<EstadoConexion> {
    return api.get<EstadoConexion>("/admin/conexion/estado");
  },

  perfiles(): Promise<PerfilesResponse> {
    return api.get<PerfilesResponse>("/admin/conexion/perfiles");
  },

  guardar(
    perfil: ConexionPerfilCompleto,
  ): Promise<AccionResponse> {
    return api.post<AccionResponse>("/admin/conexion/guardar", perfil as unknown as Record<string, unknown>);
  },

  activar(nombre: string): Promise<AccionResponse> {
    return api.post<AccionResponse>("/admin/conexion/activar", { nombre });
  },

  eliminar(nombre: string): Promise<AccionResponse> {
    return api.post<AccionResponse>("/admin/conexion/eliminar", { nombre });
  },

  test(datos: ConexionPerfilCompleto): Promise<ResultadoTestConexion> {
    return api.post<ResultadoTestConexion>(
      "/admin/conexion/test",
      datos as unknown as Record<string, unknown>,
    );
  },
};
