import { NextResponse } from "next/server";
import prisma from "@/lib/prisma";

// Datos semilla de GlitchTip y Uptime Kuma para simulación
const INITIAL_INCIDENTS = [
  {
    title: "TypeError: Cannot read properties of undefined (reading 'Emp_Cod')",
    culprit: "components/layout/sidebar.tsx in Object.map",
    level: "fatal",
    count: 34,
    usersAffected: 8,
    lastSeen: "Hace 3 minutos",
    status: "unresolved",
    browser: "Chrome 125.0.0",
    os: "Windows 11",
    stackTrace: [
      "at Sidebar (components/layout/sidebar.tsx:57:19)",
      "at renderWithHooks (node_modules/react-dom/cjs/react-dom.development.js:15486:18)",
      "at mountIndeterminateComponent (node_modules/react-dom/cjs/react-dom.development.js:18075:13)",
      "at beginWork (node_modules/react-dom/cjs/react-dom.development.js:19240:16)",
    ],
  },
  {
    title: "Database connection timeout (max_connections exceeded)",
    culprit: "classes/db.php in DB::connect",
    level: "fatal",
    count: 142,
    usersAffected: 23,
    lastSeen: "Hace 12 minutos",
    status: "unresolved",
    browser: "Server-Side (PHP 8.3.4)",
    os: "Linux (Ubuntu 22.04)",
    stackTrace: [
      "PDOException: SQLSTATE[HY000] [1040] Too many connections in /var/www/exa/classes/db.php:48",
      "Stack trace:",
      "#0 /var/www/exa/classes/db.php(48): PDO->__construct('mysql:host=127....', 'root', '***')",
      "#1 /var/www/exa/index.php(14): DB::connect()",
      "#2 {main}",
    ],
  },
  {
    title:
      "Failed to fetch: API route /api/facturacion/crear returned 502 Bad Gateway",
    culprit: "lib/api-client.ts in ApiClient.post",
    level: "error",
    count: 18,
    usersAffected: 5,
    lastSeen: "Hace 45 minutos",
    status: "unresolved",
    browser: "Safari 17.4",
    os: "macOS Sonoma",
    stackTrace: [
      "Error: Failed to fetch: API route /api/facturacion/crear returned 502 Bad Gateway",
      "at ApiClient.post (lib/api-client.ts:32:15)",
      "at async handleCrearFactura (app/dashboard/facturacion/page.tsx:120:9)",
    ],
  },
  {
    title:
      "Warning: React Hook useEffect has a missing dependency: 'fetchEmpresas'",
    culprit: "app/login/page.tsx in LoginPage",
    level: "warning",
    count: 512,
    usersAffected: 89,
    lastSeen: "Hace 2 horas",
    status: "unresolved",
    browser: "Edge 124.0.0",
    os: "Windows 10",
    stackTrace: [
      "at LoginPage (app/login/page.tsx:43:6)",
      "at renderWithHooks (node_modules/react-dom/cjs/react-dom.development.js:15486:18)",
    ],
  },
];

export async function GET() {
  try {
    // Intentamos obtener las incidencias de Postgres
    let incidents = await prisma.incident.findMany({
      orderBy: { createdAt: "desc" },
    });

    // Si la base de datos está vacía, la autosembramos para que haya datos en la previsualización
    if (incidents.length === 0) {
      console.log(
        "La tabla de incidencias está vacía. Auto-sembrando datos iniciales...",
      );
      await prisma.incident.createMany({
        data: INITIAL_INCIDENTS,
      });
      incidents = await prisma.incident.findMany({
        orderBy: { createdAt: "desc" },
      });
    }

    return NextResponse.json(incidents);
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Error desconocido";
    console.error("Error al obtener incidencias:", error);
    // Si la DB no está configurada o hay un error, devolvemos los datos estáticos para evitar que falle
    // de manera catastrófica, brindando resiliencia.
    return NextResponse.json(
      {
        error:
          "Error en la conexión a la base de datos, usando fallback estático.",
        details: errorMessage,
        fallback: true,
        data: INITIAL_INCIDENTS.map((inc, index) => ({
          id: String(index + 1),
          ...inc,
        })),
      },
      { status: 200 },
    ); // Retornamos 200 para que la UI cargue incluso con fallbacks si no hay DATABASE_URL configurada todavía
  }
}

export async function POST(request: Request) {
  try {
    const data = await request.json();

    const newIncident = await prisma.incident.create({
      data: {
        title: data.title,
        culprit: data.culprit,
        level: data.level,
        count: data.count || 1,
        usersAffected: data.usersAffected || 1,
        lastSeen: data.lastSeen || "Hace 1 segundo",
        status: data.status || "unresolved",
        browser: data.browser || "N/A",
        os: data.os || "N/A",
        stackTrace: data.stackTrace || [],
      },
    });

    return NextResponse.json({ success: true, incident: newIncident });
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Error desconocido";
    console.error("Error al crear incidencia:", error);
    return NextResponse.json(
      { success: false, error: errorMessage },
      { status: 500 },
    );
  }
}

export async function PATCH(request: Request) {
  try {
    const { id, status } = await request.json();

    if (!id || !status) {
      return NextResponse.json(
        { success: false, error: "Faltan parámetros" },
        { status: 400 },
      );
    }

    const updatedIncident = await prisma.incident.update({
      where: { id },
      data: { status },
    });

    return NextResponse.json({ success: true, incident: updatedIncident });
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Error desconocido";
    console.error("Error al actualizar incidencia:", error);
    return NextResponse.json(
      { success: false, error: errorMessage },
      { status: 500 },
    );
  }
}
