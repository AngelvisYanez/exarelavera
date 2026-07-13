'use client';

import { createContext, useContext, useEffect, useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { authApi } from '@/lib/api';
import type { AuthResponse, Empresa } from '@/lib/api-types';

interface UserInfo {
  usuario: string;
  Bdd: string;
  empresa_id: string;
  token: string;
}

interface AuthContextType {
  user: UserInfo | null;
  loading: boolean;
  login: (username: string, password: string, empresa: string) => Promise<void>;
  logout: () => void;
  isAuthenticated: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<UserInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const router = useRouter();

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    const bdd = localStorage.getItem('bdd_activa');
    const info = localStorage.getItem('user_info');
    if (token && bdd && info) {
      try {
        const parsed = JSON.parse(info);
        setUser({ ...parsed, token, Bdd: bdd });
      } catch {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('bdd_activa');
        localStorage.removeItem('user_info');
        setUser(null);
      }
    }
    setLoading(false);
  }, []);

  const clearSession = useCallback(() => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('bdd_activa');
    localStorage.removeItem('user_info');
    setUser(null);
  }, []);

  const logout = useCallback(() => {
    clearSession();
    router.push('/login');
  }, [clearSession, router]);

  const login = useCallback(async (username: string, password: string, empresa: string) => {
    const data = await authApi.login(username, password, empresa);
    if (!data.success || !data.token || !data.Bdd) {
      throw new Error(data.error || 'Credenciales inválidas');
    }
    const userInfo: UserInfo = {
      usuario: data.usuario || username,
      Bdd: data.Bdd,
      empresa_id: data.empresa_id || empresa,
      token: data.token,
    };
    localStorage.setItem('auth_token', data.token);
    localStorage.setItem('bdd_activa', data.Bdd);
    localStorage.setItem('user_info', JSON.stringify(userInfo));
    setUser(userInfo);
  }, []);

  return (
    <AuthContext.Provider value={{
      user,
      loading,
      login,
      logout,
      isAuthenticated: !!user,
    }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextType {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth debe usarse dentro de AuthProvider');
  return ctx;
}
