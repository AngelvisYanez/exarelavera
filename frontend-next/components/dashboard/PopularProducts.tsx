"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { dashboardApi } from "@/lib/api";
import type { ProductoPopular } from "@/lib/api";

export default function PopularProducts() {
  const [products, setProducts] = useState<ProductoPopular[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    dashboardApi.productosPopulares(5).then((res) => {
      if (res.success && res.data) setProducts(res.data);
    }).finally(() => setLoading(false));
  }, []);

  const maxIngresos = Math.max(...products.map((p) => p.ingresos), 1);

  const getStatus = (ingresos: number): string => {
    const pct = ingresos / maxIngresos;
    if (pct >= 0.6) return "Alta";
    if (pct >= 0.3) return "Media";
    return "Baja";
  };

  return (
    <Card className="shadow-boxShadow border-ld">
      <CardHeader>
        <CardTitle className="card-title">Productos Más Vendidos</CardTitle>
      </CardHeader>
      <CardContent>
        {loading ? (
          <div className="space-y-4">
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="h-12 rounded-lg bg-gray-100 animate-pulse" />
            ))}
          </div>
        ) : products.length > 0 ? (
          <div className="space-y-4">
            {products.map((p, i) => {
              const status = getStatus(p.ingresos);
              return (
                <div key={i} className="flex items-center justify-between py-2 border-b border-border last:border-0">
                  <div className="flex items-center gap-3">
                    <div className="h-9 w-9 rounded-lg bg-lightprimary flex items-center justify-center text-primary font-bold text-sm">
                      {i + 1}
                    </div>
                    <div>
                      <p className="text-sm font-medium text-dark">{p.nombre}</p>
                      <p className="text-xs text-muted-foreground">
                        ${p.ingresos.toLocaleString()} · {p.total_ventas} ventas
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <Badge variant={status === "Alta" ? "default" : "secondary"} className="text-[10px]">
                      {status}
                    </Badge>
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className="flex items-center justify-center py-12 text-muted-foreground text-sm">
            Sin datos de ventas disponibles
          </div>
        )}
      </CardContent>
    </Card>
  );
}
