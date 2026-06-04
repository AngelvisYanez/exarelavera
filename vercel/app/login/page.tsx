"use client";

import { useState, useCallback } from "react";
import { useRouter } from "next/navigation";
import { authApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import type { Empresa } from "@/lib/api-types";

export default function LoginPage() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [empresas, setEmpresas] = useState<Empresa[]>([]);
  const [selectedEmpresa, setSelectedEmpresa] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const router = useRouter();
  const { login } = useAuth();

  const fetchEmpresas = useCallback(async (user: string) => {
    if (!user.trim()) return;
    setLoading(true);
    setError(null);
    try {
      const data = await authApi.getEmpresas(user);

      if (data.success && data.empresas && data.empresas.length > 0) {
        setEmpresas(data.empresas);
        if (data.empresas.length === 1) {
          setSelectedEmpresa(data.empresas[0].Emp_Cod);
        }
      } else {
        setEmpresas([]);
        setError("No se encontraron empresas para este usuario");
      }
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Error de conexión al servidor",
      );
      setEmpresas([]);
    } finally {
      setLoading(false);
    }
  }, []);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!username || !password || (!selectedEmpresa && empresas.length > 0)) {
      setError("Por favor completa todos los campos");
      return;
    }

    setLoading(true);
    setError(null);
    try {
      await login(
        username,
        password,
        selectedEmpresa || (empresas[0]?.Emp_Cod ?? ""),
      );
      router.push("/dashboard");
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Error de conexión al servidor",
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100">
      <div className="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-gray-800">EXA Relavera</h1>
          <p className="text-gray-500 mt-2">
            Sistema de Gestión y Trazabilidad
          </p>
        </div>

        {error && (
          <div className="bg-red-50 text-red-600 p-3 rounded-md mb-4 text-sm text-center">
            {error}
          </div>
        )}

        <form onSubmit={handleLogin} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Usuario
            </label>
            <input
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              onBlur={() => fetchEmpresas(username)}
              className="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-black"
              placeholder="Ingrese su usuario"
              required
            />
          </div>

          {empresas.length > 0 && (
            <div className="animate-fade-in">
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Empresa
              </label>
              <select
                value={selectedEmpresa}
                onChange={(e) => setSelectedEmpresa(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-black"
                required
              >
                <option value="">-- Seleccione --</option>
                {empresas.map((emp, index) => (
                  <option
                    key={`${emp.Emp_Cod}-${emp.Suc_Des || index}`}
                    value={emp.Emp_Cod}
                  >
                    {emp.Emp_Cor} ({emp.Suc_Des || "Sede Principal"})
                  </option>
                ))}
              </select>
            </div>
          )}

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Contraseña
            </label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-black"
              placeholder="Ingrese su contraseña"
              required
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700 transition disabled:bg-blue-300"
          >
            {loading ? "Cargando..." : "Ingresar al Portal"}
          </button>
        </form>
      </div>
    </div>
  );
}
