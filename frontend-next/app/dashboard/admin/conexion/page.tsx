"use client";

import { useState } from "react";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Loader2, Database, Save, Plug } from "lucide-react";
import { toast } from "sonner";

export default function ConexionBDPage() {
  const [loading, setLoading] = useState(false);
  const [testing, setTesting] = useState(false);
  const [formData, setFormData] = useState({
    host: "localhost",
    port: "3306",
    database: "",
    username: "",
    password: "",
  });

  const handleTest = async () => {
    setTesting(true);
    try {
      const res = await fetch("/api/v1/admin/db/test", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });
      const data = await res.json();
      if (data.success) {
        toast.success("Conexión exitosa");
      } else {
        toast.error(data.error || "Error al conectar");
      }
    } catch {
      toast.error("Error de red al probar conexión");
    } finally {
      setTesting(false);
    }
  };

  const handleSave = async () => {
    if (!formData.host || !formData.database || !formData.username) {
      toast.error("Host, base de datos y usuario son obligatorios");
      return;
    }
    setLoading(true);
    try {
      const res = await fetch("/api/v1/admin/db/config", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });
      const data = await res.json();
      if (data.success) {
        toast.success("Configuración guardada");
      } else {
        toast.error(data.error || "Error al guardar");
      }
    } catch {
      toast.error("Error de red al guardar");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-2">
        <h1 className="text-3xl font-bold tracking-tight text-[#111827]">
          Conexión a Base de Datos
        </h1>
        <p className="text-[#6B7280]">
          Configura los parámetros de conexión al servidor de base de datos.
        </p>
      </div>

      <Card className="border-[#E5E7EB] bg-[#FFFFFF] shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-[16px] max-w-2xl">
        <CardHeader className="border-b border-[#E5E7EB] pb-4">
          <CardTitle className="flex items-center gap-2 text-[#111827]">
            <Database className="h-5 w-5" /> Configuración de Conexión
          </CardTitle>
          <CardDescription className="text-[#6B7280]">
            Parámetros de conexión MySQL/MariaDB
          </CardDescription>
        </CardHeader>
        <CardContent className="pt-6 space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="host" className="text-[#374151]">
                Host *
              </Label>
              <Input
                id="host"
                placeholder="localhost"
                value={formData.host}
                onChange={(e) =>
                  setFormData({ ...formData, host: e.target.value })
                }
                className="border-[#E5E7EB] focus-visible:ring-[#EF4444]"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="port" className="text-[#374151]">
                Puerto
              </Label>
              <Input
                id="port"
                placeholder="3306"
                value={formData.port}
                onChange={(e) =>
                  setFormData({ ...formData, port: e.target.value })
                }
                className="border-[#E5E7EB] focus-visible:ring-[#EF4444]"
              />
            </div>
          </div>
          <div className="space-y-2">
            <Label htmlFor="database" className="text-[#374151]">
              Base de Datos *
            </Label>
            <Input
              id="database"
              placeholder="Nombre de la base de datos"
              value={formData.database}
              onChange={(e) =>
                setFormData({ ...formData, database: e.target.value })
              }
              className="border-[#E5E7EB] focus-visible:ring-[#EF4444]"
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="username" className="text-[#374151]">
                Usuario *
              </Label>
              <Input
                id="username"
                placeholder="Usuario de BD"
                value={formData.username}
                onChange={(e) =>
                  setFormData({ ...formData, username: e.target.value })
                }
                className="border-[#E5E7EB] focus-visible:ring-[#EF4444]"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="password" className="text-[#374151]">
                Contraseña
              </Label>
              <Input
                id="password"
                type="password"
                placeholder="Contraseña de BD"
                value={formData.password}
                onChange={(e) =>
                  setFormData({ ...formData, password: e.target.value })
                }
                className="border-[#E5E7EB] focus-visible:ring-[#EF4444]"
              />
            </div>
          </div>
          <div className="flex gap-3 pt-4 border-t border-[#E5E7EB]">
            <Button
              variant="outline"
              onClick={handleTest}
              disabled={testing}
              className="border-[#E5E7EB] hover:bg-[#F3F4F6] text-[#4B5563]"
            >
              {testing ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : (
                <Plug className="mr-2 h-4 w-4" />
              )}
              Probar Conexión
            </Button>
            <Button
              onClick={handleSave}
              disabled={loading}
              className="bg-[#EF4444] hover:bg-[#DC2626] text-[#FFFFFF] shadow-[0_4px_14px_0_rgba(239,68,68,0.39)] rounded-[8px]"
            >
              {loading ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : (
                <Save className="mr-2 h-4 w-4" />
              )}
              Guardar Configuración
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
