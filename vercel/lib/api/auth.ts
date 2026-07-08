import type { AuthResponse } from '@/lib/api-types';

export const authApi = {
  async getEmpresas(username: string): Promise<AuthResponse> {
    const res = await fetch('/api/auth/empresas', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username }),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({ error: 'Error de conexión' }));
      throw new Error(err.error || `HTTP ${res.status}`);
    }
    return res.json();
  },

  async login(username: string, password: string, empresa: string): Promise<AuthResponse> {
    const res = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password, empresa }),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({ error: 'Error de conexión' }));
      throw new Error(err.error || `HTTP ${res.status}`);
    }
    return res.json();
  },
};
