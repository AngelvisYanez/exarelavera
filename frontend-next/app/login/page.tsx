"use client";

import { useState, useCallback, useEffect } from "react";
import { useRouter } from "next/navigation";
import { z } from "zod";
import { authApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Loader2, User, Lock, Building2, Eye, EyeOff, AlertCircle } from "lucide-react";
import type { Empresa } from "@/lib/api-types";

const loginSchema = z.object({
  username: z
    .string()
    .min(3, "El usuario debe tener al menos 3 caracteres")
    .max(50, "El usuario no puede exceder 50 caracteres"),
  password: z
    .string()
    .min(6, "La contraseña debe tener al menos 6 caracteres")
    .max(100, "La contraseña no puede exceder 100 caracteres"),
});

type LoginFormData = z.infer<typeof loginSchema>;

export default function LoginPage() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [empresas, setEmpresas] = useState<Empresa[]>([]);
  const [selectedEmpresa, setSelectedEmpresa] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Partial<Record<keyof LoginFormData, string>>>({});
  const [rememberMe, setRememberMe] = useState(false);
  const [isLoaded, setIsLoaded] = useState(false);
  const router = useRouter();
  const { login } = useAuth();

  useEffect(() => {
    setIsLoaded(true);
    const savedUsername = localStorage.getItem("remembered_username");
    if (savedUsername) {
      setUsername(savedUsername);
      setRememberMe(true);
    }
  }, []);

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

  const handleUsernameBlur = () => {
    if (username.trim()) {
      fetchEmpresas(username);
    }
  };

  const validateField = (field: keyof LoginFormData, value: string) => {
    const result = loginSchema.shape[field].safeParse(value);
    if (!result.success) {
      setFieldErrors((prev) => ({ ...prev, [field]: result.error.issues[0].message }));
    } else {
      setFieldErrors((prev) => {
        const { [field]: _, ...rest } = prev;
        return rest;
      });
    }
  };

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    const result = loginSchema.safeParse({ username, password });
    if (!result.success) {
      const errors: Partial<Record<keyof LoginFormData, string>> = {};
      result.error.issues.forEach((err) => {
        if (err.path[0]) {
          errors[err.path[0] as keyof LoginFormData] = err.message;
        }
      });
      setFieldErrors(errors);
      return;
    }

    if (empresas.length > 0 && !selectedEmpresa) {
      setError("Por favor selecciona una empresa");
      return;
    }

    setLoading(true);
    try {
      if (rememberMe) {
        localStorage.setItem("remembered_username", username);
      } else {
        localStorage.removeItem("remembered_username");
      }

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
    <div className="relative min-h-screen flex items-center justify-center overflow-hidden bg-background">
      <div className="absolute inset-0 bg-gradient-to-br from-lightprimary via-background to-lightsecondary opacity-60" />

      <div
        className={`relative bg-card p-8 rounded-2xl shadow-boxShadow w-full max-w-md mx-4 border border-border transition-all duration-500 ease-out ${
          isLoaded ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
        }`}
      >
        <div className="text-center mb-8">
          <div className="flex justify-center mb-4">
            <div className="w-16 h-16 rounded-full bg-lightprimary flex items-center justify-center transition-transform duration-300 hover:scale-105">
              <span className="text-primary text-2xl font-bold">ER</span>
            </div>
          </div>
          <h1 className="text-2xl font-bold text-dark">EXA Relavera</h1>
          <p className="text-sm text-muted-foreground mt-1">
            Sistema de Gestión y Trazabilidad
          </p>
        </div>

        {error && (
          <div className="flex items-center gap-2 bg-lighterror text-error p-3 rounded-xl mb-4 text-sm border border-error/20 animate-in fade-in slide-in-from-top-1 duration-200">
            <AlertCircle className="h-4 w-4 flex-shrink-0" />
            <span>{error}</span>
          </div>
        )}

        <form onSubmit={handleLogin} className="space-y-5">
          <div className="space-y-2">
            <Label htmlFor="username" className="text-dark">
              Usuario
            </Label>
            <div className="relative">
              <User className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                id="username"
                type="text"
                value={username}
                onChange={(e) => {
                  setUsername(e.target.value);
                  if (fieldErrors.username) {
                    validateField("username", e.target.value);
                  }
                }}
                onBlur={handleUsernameBlur}
                placeholder="Ingrese su usuario"
                className={`pl-10 h-11 ${fieldErrors.username ? "border-destructive focus-visible:ring-destructive/20" : ""}`}
                aria-invalid={!!fieldErrors.username}
                aria-describedby={fieldErrors.username ? "username-error" : undefined}
              />
            </div>
            {fieldErrors.username && (
              <p id="username-error" className="text-xs text-destructive mt-1 animate-in fade-in duration-150">
                {fieldErrors.username}
              </p>
            )}
          </div>

          {empresas.length > 0 && (
            <div className="space-y-2">
              <Label htmlFor="empresa" className="text-dark">
                Empresa
              </Label>
              <div className="relative">
                <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground z-10" />
                <Select
                  value={selectedEmpresa}
                  onValueChange={(value) => setSelectedEmpresa(value ?? "")}
                >
                  <SelectTrigger className="pl-10 h-11 w-full">
                    <SelectValue placeholder="Seleccione una empresa" />
                  </SelectTrigger>
                  <SelectContent>
                    {empresas.map((emp, index) => (
                      <SelectItem
                        key={`${emp.Emp_Cod}-${index}`}
                        value={emp.Emp_Cod}
                      >
                        {emp.Emp_Cor} ({emp.Suc_Des || "Sede Principal"})
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="password" className="text-dark">
              Contraseña
            </Label>
            <div className="relative">
              <Lock className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                id="password"
                type={showPassword ? "text" : "password"}
                value={password}
                onChange={(e) => {
                  setPassword(e.target.value);
                  if (fieldErrors.password) {
                    validateField("password", e.target.value);
                  }
                }}
                placeholder="Ingrese su contraseña"
                className={`pl-10 pr-10 h-11 ${fieldErrors.password ? "border-destructive focus-visible:ring-destructive/20" : ""}`}
                aria-invalid={!!fieldErrors.password}
                aria-describedby={fieldErrors.password ? "password-error" : undefined}
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-dark transition-colors"
                tabIndex={-1}
              >
                {showPassword ? (
                  <EyeOff className="h-4 w-4" />
                ) : (
                  <Eye className="h-4 w-4" />
                )}
              </button>
            </div>
            {fieldErrors.password && (
              <p id="password-error" className="text-xs text-destructive mt-1 animate-in fade-in duration-150">
                {fieldErrors.password}
              </p>
            )}
          </div>

          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-2">
              <Checkbox
                id="remember"
                checked={rememberMe}
                onCheckedChange={(checked) => setRememberMe(checked as boolean)}
              />
              <Label
                htmlFor="remember"
                className="text-sm text-muted-foreground cursor-pointer"
              >
                Recordar usuario
              </Label>
            </div>
            <button
              type="button"
              className="text-sm text-primary hover:text-primary/80 transition-colors"
              onClick={() => {
                setError("Función de recuperación de contraseña no disponible aún");
              }}
            >
              ¿Olvidaste tu contraseña?
            </button>
          </div>

          <Button
            type="submit"
            disabled={loading}
            className="w-full h-11 font-medium transition-all duration-200 hover:shadow-lg hover:shadow-primary/20"
          >
            {loading && <Loader2 className="h-4 w-4 animate-spin mr-2" />}
            {loading ? "Ingresando..." : "Ingresar al Portal"}
          </Button>
        </form>

        <div className="mt-6 pt-4 border-t border-border">
          <p className="text-center text-xs text-muted-foreground">
            Sistema protegido. El acceso no autorizado está prohibido.
          </p>
        </div>
      </div>
    </div>
  );
}
