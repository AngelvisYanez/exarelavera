import { api } from '@/lib/api-client';
import type { AuthResponse } from '@/lib/api-types';

export const authApi = {
  getEmpresas(username: string): Promise<AuthResponse> {
    return api.postRaw<AuthResponse>('/auth/empresas', { username });
  },

  login(username: string, password: string, empresa: string): Promise<AuthResponse> {
    return api.postRaw<AuthResponse>('/auth/login', { username, password, empresa });
  },
};
