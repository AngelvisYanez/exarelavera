"use client";

import { useEffect, useState } from "react";

interface Tarea {
  id: number;
  titulo: string;
  estado: string;
  asignado: string;
}

export default function TareasPage() {
  const [tareas, setTareas] = useState<Tarea[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Nota: Necesitarás obtener tu token de autenticación real aquí
    const token = "TU_TOKEN_AQUI";

    fetch("/api/v1/auditoria/tareas", {
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
      },
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setTareas(data.data);
        }
        setLoading(false);
      })
      .catch((err) => {
        console.error("Error fetching tasks:", err);
        setLoading(false);
      });
  }, []);

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-6">Tareas del Equipo de Trabajo</h1>
      {loading ? (
        <p>Cargando tareas...</p>
      ) : (
        <div className="grid grid-cols-3 gap-4">
          {["pendiente", "en-proceso", "finalizada"].map((estado) => (
            <div key={estado} className="bg-gray-100 p-4 rounded-lg">
              <h2 className="font-semibold mb-3 capitalize">
                {estado.replace("-", " ")}
              </h2>
              <div className="space-y-2">
                {tareas
                  .filter((t) => t.estado === estado)
                  .map((tarea) => (
                    <div
                      key={tarea.id}
                      className="bg-white p-3 rounded shadow-sm border"
                    >
                      {tarea.titulo}
                      <div className="text-xs text-gray-500 mt-1">
                        Asignado: {tarea.asignado}
                      </div>
                    </div>
                  ))}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
