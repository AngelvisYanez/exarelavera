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
