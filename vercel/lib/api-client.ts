function isBrowser(): boolean {
  return typeof window !== 'undefined';
}

function getToken(): string | null {
  if (!isBrowser()) return null;
  return localStorage.getItem('auth_token');
}

function getBdd(): string | null {
  if (!isBrowser()) return null;
  return localStorage.getItem('bdd_activa');
}

function getEmpresaId(): string | null {
  if (!isBrowser()) return null;
  try {
    const info = localStorage.getItem('user_info');
    if (!info) return null;
    const parsed = JSON.parse(info);
    return parsed.empresa_id || null;
  } catch {
    return null;
  }
}

function clearSession(): void {
  if (!isBrowser()) return;
  localStorage.removeItem('auth_token');
  localStorage.removeItem('bdd_activa');
  localStorage.removeItem('user_info');
}

export class ApiError extends Error {
  constructor(
    message: string,
    public status?: number,
    public body?: unknown
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

async function request<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `/api/v1${endpoint}`;

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string>),
  };

  const token = getToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const res = await fetch(url, {
    ...options,
    headers,
  });

  // 401 → sesión inválida/expirada, redirigir al login
  if (res.status === 401) {
    clearSession();
    if (isBrowser() && !window.location.pathname.startsWith('/login')) {
      window.location.href = '/login';
    }
    throw new ApiError('Sesión expirada o inválida', 401);
  }

  if (!res.ok) {
    let body = '';
    try { body = await res.text(); } catch {}
    throw new ApiError(
      `HTTP ${res.status}: ${body.substring(0, 300)}`,
      res.status,
      body
    );
  }

  const text = await res.text();

  if (!text) {
    return {} as T;
  }

  try {
    return JSON.parse(text) as T;
  } catch {
    throw new ApiError(
      `Invalid JSON response: ${text.substring(0, 500)}`,
      res.status,
      text
    );
  }
}

function buildBody(data: Record<string, unknown>): Record<string, unknown> {
  const bdd = getBdd();
  const empId = getEmpresaId();
  const result = { ...data };
  if (bdd) result.Bdd = bdd;
  if (empId) result.Emp_Cod = empId;
  return result;
}

export const api = {
  get<T>(endpoint: string): Promise<T> {
    return request<T>(endpoint, { method: 'GET' });
  },

  post<T>(endpoint: string, data: Record<string, unknown> = {}): Promise<T> {
    return request<T>(endpoint, {
      method: 'POST',
      body: JSON.stringify(buildBody(data)),
    });
  },

  postRaw<T>(endpoint: string, data: Record<string, unknown> = {}): Promise<T> {
    return request<T>(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },
};
