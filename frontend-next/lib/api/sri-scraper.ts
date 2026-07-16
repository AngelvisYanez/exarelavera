import { api } from '../api-client';

export interface CreateJobParams {
  ruc: string;
  clave: string;
  fecha_desde: string;
  fecha_hasta: string;
  tipo_comprobante?: string;
  flow?: string;
  Emp_Cod?: string;
  Bdd?: string;
}

export interface JobResult {
  success: boolean;
  job_id?: string;
  error?: string;
}

export interface JobState {
  id: string;
  status: string;
  progress: number;
  message: string;
  files_downloaded: number;
  total_files: number;
  created_at: string;
  updated_at: string;
}

export interface JobStateResponse {
  success: boolean;
  job?: JobState;
  error?: string;
}

export interface ScraperJobHistory {
  success: boolean;
  jobs: JobState[];
}

export const sriScraperApi = {
  // Crear un nuevo job
  createJob: (data: CreateJobParams) => {
    return api.post<JobResult>('/facturacion/sri-scraper/jobs', data);
  },

  // Consultar historial de jobs
  getJobs: () => {
    return api.get<ScraperJobHistory>('/facturacion/sri-scraper/jobs');
  },

  // Consultar el estado de un job específico (polling)
  getJobStatus: (id: string) => {
    return api.get<JobStateResponse>(`/facturacion/sri-scraper/jobs/${id}`);
  },

  // Cancelar un job
  cancelJob: (id: string) => {
    return api.delete<{ success: boolean; message?: string }>(`/facturacion/sri-scraper/jobs/${id}`);
  },
};
