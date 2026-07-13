export interface AuthResponse {
  success: boolean;
  token?: string;
  Bdd?: string;
  usuario?: string;
  empresa_id?: string;
  message?: string;
  error?: string;
  conteo?: number;
  empresas?: Empresa[];
}

export interface Empresa {
  Emp_Cod: string;
  Emp_Cor: string;
  Suc_Des: string;
  Suc_Cod?: string;
}

export interface ApiResponse<T = unknown> {
  status: boolean;
  message?: string;
  data?: T;
  error?: string;
}

export interface Cliente {
  Cli_Cod?: string;
  Cli_Ced: string;
  Cli_Nom: string;
  Cli_Dir?: string;
  Cli_Tel?: string;
  Cli_Cel?: string;
  Cli_Mail?: string;
  Cli_Obs?: string;
}

export interface Proveedor {
  Prv_Cod?: string;
  Prv_Ced: string;
  Prv_Nom: string;
  Prv_Dir?: string;
  Prv_Tel?: string;
  Prv_Cel?: string;
  Prv_Mail?: string;
}

export interface Categoria {
  Cat_Cod?: string;
  Cat_Des: string;
  Cat_Obs?: string;
}

export interface Marca {
  Mar_Cod?: string;
  Mar_Des: string;
  Mar_Obs?: string;
}

export interface Producto {
  Pro_Cod?: string;
  Pro_Des: string;
  Pro_Obs?: string;
  Cat_Cod?: string;
  Mar_Cod?: string;
}

export interface Tarea {
  Tar_Cod: number;
  Tar_Titulo: string;
  Tar_Descripcion: string;
  Tar_Prioridad: string;
  Tar_Fecha_Inicio: string;
  Tar_Fecha_Fin: string;
  Tar_Estado: string;
  Tar_Fecha_Culminacion?: string;
  Usu_Creador?: number;
  Emp_Cod?: number;
}

export interface Empleado {
  Per_Cod: number;
  Prs_Ced: string;
  Nombre: string;
}

export interface Avance {
  Ava_Cod: number;
  Tar_Cod: number;
  Usu_Cod?: number;
  Usuario_Nombre?: string;
  Ava_Descripcion: string;
  Ava_Porcentaje: number;
  Ava_Fecha: string;
}

export interface Asignacion {
  Tas_Cod: number;
  Tar_Cod: number;
  Tar_Titulo: string;
  Tar_Descripcion?: string;
  Tar_Estado: string;
  Tar_Fecha_Inicio?: string;
  Tar_Fecha_Fin?: string;
  Tar_Fecha_Culminacion?: string;
  Per_Cod: number;
  Empleado_Nombre: string;
  Tas_Fecha_Asignacion: string;
  Ava_Porcentaje?: number | null;
}

export interface Indicador {
  Total_Tareas: number;
  Completadas: number;
  Atrasadas: number;
  Pct_Completadas: number;
  Pct_Atrasadas: number;
  Rendimiento_Promedio: number;
}

export interface TareaAtencion {
  Tar_Cod: number;
  Tar_Titulo: string;
  Tar_Estado: string;
  Tar_Fecha_Inicio?: string;
  Tar_Fecha_Fin?: string;
  Tar_Fecha_Culminacion?: string;
  Empleado_Nombre: string;
  Tipo_Atencion: string;
  Ava_Porcentaje?: number | null;
}

export interface ConexionPerfil {
  nombre: string;
  host: string;
  port: number;
  database: string;
}

export interface ConexionPerfilCompleto extends ConexionPerfil {
  user: string;
  pass: string;
}

export interface EstadoConexion {
  success: boolean;
  activo: {
    nombre: string;
    host: string;
    port: number;
  } | null;
  conectado: boolean;
  server_info?: string;
  error?: string;
}

export interface ResultadoTestConexion {
  success: boolean;
  message?: string;
  error?: string;
  server_info?: string;
  version?: string;
}

export interface PerfilesResponse {
  success: boolean;
  perfiles: ConexionPerfil[];
}

export interface AccionResponse {
  success: boolean;
  message?: string;
  error?: string;
  conexion_ok?: boolean;
  conexion_error?: string;
}

export interface MetricaRendimiento {
  Per_Cod: number;
  Nombre: string;
  Total_Tareas: number;
  Tareas_Completadas: number;
  Tareas_Atrasadas: number;
  Rendimiento_Porcentaje: number;
}

// ─── CONTABILIDAD ────────────────────────────────────────────────────────────
export interface PlanCuenta {
  Pla_Cod: number;
  Pla_Fec: string;
  Pla_Obs?: string;
  Pla_Est: string;
  Emp_Cod: number;
  Pla_Sys?: string;
}

export interface PeriodoContable {
  Pec_Cod: number;
  Pec_Fei: string;
  Pec_Fef: string;
  Pec_Est?: string;
  Pla_Cod: number;
}

export interface ComprobanteContable {
  Com_Cod: number;
  Com_Num: number;
  Com_Fec: string;
  Tia_Cod: number;
  Pec_Cod: number;
  Emp_Cod: number;
  Com_Con?: string;
  Com_Est?: string;
}

export interface TipoComprobante {
  Tic_Cod: number;
  Tic_Des: string;
  Tic_Sri?: string;
  Tic_Est?: string;
}

// ─── RRHH ────────────────────────────────────────────────────────────────────
export interface PersonalRow {
  Per_Cod: number;
  Prs_Cod: number;
  Prs_Ced?: string;
  Prs_Nom?: string;
  Prs_Ape?: string;
  Prs_Tel?: string;
  Prs_Cel?: string;
  Prs_Cor?: string;
  Per_Car?: string;
  Per_Est?: string;
  Per_Tip?: string;
  Emp_Cod: number;
}

export interface ContratoRow {
  Con_Cod: number;
  Per_Cod: number;
  Tic_Cod?: number;
  Prs_Nom?: string;
  Prs_Ape?: string;
  Tic_Des?: string;
  Con_Ini?: string;
  Con_Fin?: string;
  Con_Est?: string;
}

export interface DepartamentoRow {
  Dep_Cod: number;
  Are_Cod?: number;
  Dep_Des: string;
  Dep_Est?: string;
  Dep_Rec?: string;
  Dep_Cdc?: string;
  Emp_Cod: number;
}

export interface CargoRow {
  Tic_Cod: number;
  Dep_Cod?: number;
  Tic_Des: string;
  Tic_Est?: string;
}

export interface RolPagoRow {
  Rol_Cod: number;
  Are_Cod?: number;
  Pec_Cod: number;
  Rol_Tip?: string;
  Rol_Num?: string;
  Rol_Fei?: string;
  Rol_Fef?: string;
  Rol_Mes?: string;
  Rol_Est?: string;
}

// ─── ACTIVOS FIJOS ───────────────────────────────────────────────────────────
export interface ActivoFijo {
  Act_Cod: number;
  Tia_Cod: number;
  Suc_Cod?: number;
  Est_Cod?: number;
  Act_Des?: string;
  Act_Val?: number;
  Act_Gar?: number;
  Act_Est?: string;
  Act_Fec?: string;
}

export interface TipoActivo {
  Tia_Cod: number;
  Tia_Des?: string;
  Tia_Est?: string;
  Tia_Rec?: number;
  Emp_Cod: number;
}

export interface Mantenimiento {
  Man_Cod: number;
  Tma_Cod: number;
  Act_Cod: number;
  Ema_Cod?: number;
  Est_Cod?: number;
  Man_Des?: string;
  Man_Fec: string;
  Man_Est?: string;
}

export interface TipoMantenimiento {
  Tma_Cod: number;
  Tma_Des?: string;
  Tma_Est?: string;
}

export interface Depreciacion {
  Com_Cod: number;
  Act_Cod: number;
  Acd_Fpd?: string;
  Acd_Tip?: string;
  Acd_Est?: string;
}

// ─── BODEGA ──────────────────────────────────────────────────────────────────
export interface BodegaRow {
  Bod_Cod: number;
  Bod_Nom: string;
  Bod_Dir?: string;
  Bod_Tip?: string;
  Bod_Est?: string;
  Suc_Cod?: number;
}

export interface StockRow {
  Pro_Cod: number;
  Suc_Cod: number;
  Stk_Can: number;
  Stk_Prp?: number;
  Stk_Min?: number;
  Stk_Max?: number;
}

export interface MovimientoBodega {
  Mov_Cod: number;
  Mov_Fec: string;
  Bod_Ori?: number;
  Bod_Des?: number;
  Mov_Est?: string;
  Mov_Obs?: string;
}

// ─── BANANERO ────────────────────────────────────────────────────────────────
export interface ProductorBanano {
  Prd_Cod: number;
  Prd_Nom: string;
  Prv_Cod?: number;
  Prd_Est?: string;
  Prd_Mag?: string;
}

export interface LiquidacionBanana {
  Lib_Cod: number;
  Prd_Cod: number;
  Bam_Cod?: number;
  Lib_Fec?: string;
  Lib_Est?: string;
  Lib_Num?: string;
  Lib_Int?: string;
  Lib_Ano?: string;
  Lib_Sem?: string;
  productor_nombre?: string;
}

export interface ExportacionContainer {
  Exc_Cod: number;
  Emp_Cod: number;
  Exc_Fec?: string;
  Exc_Con?: string;
  Exc_Est?: string;
  Exc_Ano?: string;
  Exc_Sem?: string;
  Exc_Vap?: string;
}

export interface MarcaBanano {
  Bam_Cod: number;
  Emp_Cod?: number;
  Bam_Nom: string;
  Bam_Des?: string;
  Bam_Tam?: string;
  Bam_Est?: string;
}

export interface Naviera {
  Nav_Cod: number;
  Emp_Cod?: number;
  Nav_Nom: string;
  Nav_Tip?: string;
  Nav_Est?: string;
}

// ─── CAMARONERA ──────────────────────────────────────────────────────────────
export interface ProductorCamaronera {
  Prod_Cod: number;
  Prv_Cod: number;
  Tip_Prod?: string;
}

export interface NegociacionCamaron {
  Cod_Neg: number;
  Prod_Cod: number;
  Fec_Neg: string;
  Neg_Tot?: number;
  Neg_Des?: string;
  Est_Neg?: string;
  Emp_Cod: number;
}

export interface LiquidacionCamaron {
  Liq_Cod: number;
  Prod_Cod: number;
  Liq_Fecha: string;
  Emp_Cod: number;
  Cod_Neg?: number;
  Est_Liq?: string;
  Peso_Rem?: number;
  Peso_Planta?: number;
  Peso_Net?: number;
}

// ─── TESORERÍA ───────────────────────────────────────────────────────────────
export interface Banco {
  Bak_Cod: number;
  Bak_Des: string;
  Bak_Est?: string;
  Bak_Abr?: string;
}

export interface ChequeExt {
  Che_Cod: number;
  Bak_Cod: number;
  Cli_Cod?: number;
  Che_Cta?: string;
  Che_Num: number;
  Che_Fec: string;
  Che_Val: number;
  Che_Est?: string;
  Che_Cli?: string;
}

export interface ConciliacionBancaria {
  Cob_Cod: number;
  Pec_Cod: number;
  Ban_Cod: number;
  Usu_Cod: number;
  Cob_Fec: string;
  Cob_Dis: number;
  Cob_Obs?: string;
  Cob_Est?: string;
}

export interface CCCobrar {
  Cpc_Cod: number;
  Com_Cod: number;
  Vet_Cod?: number;
  Cpc_Ven?: string;
  Cpc_Obs?: string;
}

// ─── CAJA CHICA ──────────────────────────────────────────────────────────────
export interface CajaChica {
  Cch_Cod: number;
  Usu_Cod: number;
  Emp_Cod: number;
  Cch_Val: number;
  Cch_Fec?: string;
  Cch_Est?: string;
  Cch_Obs?: string;
}

export interface ReposicionCaja {
  Rep_Cod: number;
  Cch_Cod?: number;
  Usu_Cod?: number;
  Rep_Num?: number;
  Rep_Fec: string;
  Rep_Obs?: string;
  Rep_Est?: string;
  Rep_Tip?: string;
}

// ─── TRANSPORTE ──────────────────────────────────────────────────────────────
export interface Vehiculo {
  Veh_Cod: number;
  Emp_Cod?: number;
  Veh_Mar?: string;
  Veh_Pla?: string;
  Veh_Col?: string;
  Veh_Est?: string;
}

export interface Viaje {
  Via_Cod: number;
  Cli_Cod?: number;
  Veh_Cod?: number;
  Via_Fec?: string;
  Via_Est?: string;
  Via_Tra?: string;
  Via_Uni?: string;
}

export interface Ticket {
  Tic_Cod: number;
  Tic_Des: string;
  Tic_Fec_Cre?: string;
  Tic_Fec_Ter?: string;
  Emp_Cod: number;
  Tic_Est?: string;
}

// ─── ADMIN ───────────────────────────────────────────────────────────────────
export interface UsuarioRow {
  Usu_Cod: number;
  Usu_Ced: string;
  Prs_Nom?: string;
  Prs_Ape?: string;
  Per_Des?: string;
}

export interface Sucursal {
  Suc_Cod: number;
  Suc_Des: string;
  Emp_Cod: number;
}

export interface Perfil {
  Per_Cod: number;
  Per_Des: string;
}

// ─── DATA API ────────────────────────────────────────────────────────────────
export interface DataApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  count?: number;
  total?: number;
  error?: string;
}

export interface TableInfo {
  Field: string;
  Type: string;
  Null: string;
  Key: string;
  Default: string | null;
  Extra: string;
}

export interface TablesResponse {
  success: boolean;
  tables: string[];
}

// ─── MÓDULO USO ──────────────────────────────────────────────────────────────
export interface ModuloUsoModulo {
  rank: number;
  nombre: string;
  total: number;
  porcentaje: number;
}

export interface ModuloUsoUsuarioModulo {
  modulo: string;
  total: number;
  porcentaje: number;
}

export interface ModuloUsoUsuario {
  usuario: string;
  ruc: string;
  total: number;
  modulos: ModuloUsoUsuarioModulo[];
}

export interface ModuloUsoTendenciaDia {
  fecha: string;
  total: number;
  modulos: Record<string, number>;
}

export interface ModuloUsoResumen {
  totalAcciones: number;
  totalUsuarios: number;
  totalModulos: number;
}

export interface ModuloUsoData {
  porModulo: ModuloUsoModulo[];
  porUsuario: ModuloUsoUsuario[];
  tendencia: ModuloUsoTendenciaDia[];
  resumen: ModuloUsoResumen;
}

export interface ModuloUsoResponse {
  success: boolean;
  data?: ModuloUsoData;
  error?: string;
}

export interface ModuloUsoFiltros {
  fecha_desde?: string;
  fecha_hasta?: string;
  ruc_cliente?: string;
}
