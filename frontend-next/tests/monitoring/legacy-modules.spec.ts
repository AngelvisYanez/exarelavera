import { test, expect } from "@playwright/test";

const USERNAME = process.env.MONITOR_USER || "0704439892001";
const PASSWORD = process.env.MONITOR_PASSWORD || "admin123";
const EMPRESA_COD = "96";

interface AuthResult {
  token: string;
  Bdd: string;
  empresa_id: string;
  success: boolean;
  [key: string]: unknown;
}

async function loginAndGetToken(request: any): Promise<AuthResult> {
  const res = await request.post("/api/v1/auth/login", {
    data: { username: USERNAME, password: PASSWORD, empresa: EMPRESA_COD },
  });
  expect(res.status()).toBe(200);
  const body = await res.json();
  expect(body.success).toBe(true);
  return body as AuthResult;
}

function headers(token: string) {
  return { Authorization: `Bearer ${token}` };
}

test.describe("Módulos Legacy — APIs Read-Only (11 módulos)", () => {
  let auth: AuthResult;

  test.beforeAll(async ({ request }) => {
    auth = await loginAndGetToken(request);
    console.log(`Auth OK — Bdd: ${auth.Bdd}, empresa: ${auth.empresa_id}`);
  });

  // ─── 1. DATA API GENÉRICA ────────────────────────────────────────────────
  test("1. DATA - Listar tablas", async ({ request }) => {
    const res = await request.post("/api/v1/data/tables", {
      data: { Bdd: auth.Bdd },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(Array.isArray(body.tables)).toBe(true);
    console.log(`Tablas encontradas: ${body.tables.length}`);
  });

  test("2. DATA - Query SQL read-only", async ({ request }) => {
    const res = await request.post("/api/v1/data/query", {
      data: { Bdd: auth.Bdd, sql: "SELECT 1 AS test" },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(String(body.data[0].test)).toBe("1");
  });

  test("3. DATA - Describe tabla", async ({ request }) => {
    const res = await request.post("/api/v1/data/describe", {
      data: { Bdd: auth.Bdd, table: "plan_cuenta" },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(Array.isArray(body.columns)).toBe(true);
    console.log(`Columnas en plan_cuenta: ${body.columns.length}`);
  });

  test("4. DATA - Listar registros", async ({ request }) => {
    const res = await request.post("/api/v1/data/list", {
      data: { Bdd: auth.Bdd, table: "plan_cuenta", where: { Emp_Cod: auth.empresa_id }, limit: 5 },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Registros en plan_cuenta: ${body.total}`);
  });

  // ─── 2. CONTABILIDAD ──────────────────────────────────────────────────────
  test("5. CONTABILIDAD - Plan de cuentas", async ({ request }) => {
    const res = await request.post("/api/v1/contabilidad/plan-cuentas", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Cuentas contables: ${body.data?.length ?? 0}`);
  });

  test("6. CONTABILIDAD - Periodos contables", async ({ request }) => {
    const res = await request.post("/api/v1/contabilidad/periodos", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Periodos: ${body.data?.length ?? 0}`);
  });

  test("7. CONTABILIDAD - Tipos de comprobante", async ({ request }) => {
    const res = await request.post("/api/v1/contabilidad/tipos-comprobante", {
      data: { Bdd: auth.Bdd },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Tipos comprobante: ${body.data?.length ?? 0}`);
  });

  test("8. CONTABILIDAD - Comprobantes", async ({ request }) => {
    const res = await request.post("/api/v1/contabilidad/comprobantes", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Comprobantes: ${body.data?.length ?? 0}`);
  });

  // ─── 3. RRHH ──────────────────────────────────────────────────────────────
  test("9. RRHH - Personal", async ({ request }) => {
    const res = await request.post("/api/v1/rrhh/personal", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Personal: ${body.data?.length ?? 0}`);
  });

  test("10. RRHH - Departamentos y cargos", async ({ request }) => {
    const [resDep, resCar] = await Promise.all([
      request.post("/api/v1/rrhh/departamentos", { data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id }, headers: headers(auth.token) }),
      request.post("/api/v1/rrhh/cargos", { data: { Bdd: auth.Bdd }, headers: headers(auth.token) }),
    ]);
    expect(resDep.status()).toBe(200);
    expect(resCar.status()).toBe(200);
    const dep = await resDep.json();
    const car = await resCar.json();
    console.log(`Departamentos: ${dep.data?.length ?? 0}, Cargos: ${car.data?.length ?? 0}`);
  });

  test("11. RRHH - Contratos", async ({ request }) => {
    const res = await request.post("/api/v1/rrhh/contratos", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Contratos: ${body.data?.length ?? 0}`);
  });

  test("12. RRHH - Roles de pago", async ({ request }) => {
    const res = await request.post("/api/v1/rrhh/roles-pago", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Roles de pago: ${body.data?.length ?? 0}`);
  });

  // ─── 4. COMPRAS ────────────────────────────────────────────────────────────
  test("13. COMPRAS - Requisiciones", async ({ request }) => {
    const res = await request.post("/api/v1/compras/requisiciones", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Requisiciones: ${body.data?.length ?? 0}`);
  });

  // ─── 5. ACTIVOS FIJOS ──────────────────────────────────────────────────────
  test("14. ACTIVOS FIJOS - Activos y tipos", async ({ request }) => {
    const [resAct, resTip] = await Promise.all([
      request.post("/api/v1/activosfijos/activos", { data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id }, headers: headers(auth.token) }),
      request.post("/api/v1/activosfijos/tipos-activo", { data: { Bdd: auth.Bdd }, headers: headers(auth.token) }),
    ]);
    expect(resAct.status()).toBe(200);
    expect(resTip.status()).toBe(200);
    const act = await resAct.json();
    const tip = await resTip.json();
    console.log(`Activos: ${act.data?.length ?? 0}, Tipos: ${tip.data?.length ?? 0}`);
  });

  // ─── 6. BODEGA ─────────────────────────────────────────────────────────────
  test("15. BODEGA - Bodegas y stock", async ({ request }) => {
    const [resBod, resStk] = await Promise.all([
      request.post("/api/v1/bodega/bodegas", { data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id }, headers: headers(auth.token) }),
      request.post("/api/v1/bodega/stock", { data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id }, headers: headers(auth.token) }),
    ]);
    expect(resBod.status()).toBe(200);
    expect(resStk.status()).toBe(200);
    const bod = await resBod.json();
    const stk = await resStk.json();
    console.log(`Bodegas: ${bod.data?.length ?? 0}, Stock items: ${stk.data?.length ?? 0}`);
  });

  // ─── 7. CAJA CHICA ─────────────────────────────────────────────────────────
  test("16. CAJA CHICA - Cajas y movimientos", async ({ request }) => {
    const res = await request.post("/api/v1/caja-chica/cajas", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Cajas chicas: ${body.data?.length ?? 0}`);
  });

  // ─── 8. TRANSPORTE CARGA ───────────────────────────────────────────────────
  test("17. TRANSPORTE CARGA - Vehículos", async ({ request }) => {
    const res = await request.post("/api/v1/transportecarga/vehiculos", {
      data: { Bdd: auth.Bdd },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Vehículos: ${body.data?.length ?? 0}`);
  });

  test("18. TRANSPORTE CARGA - Viajes", async ({ request }) => {
    const res = await request.post("/api/v1/transportecarga/viajes", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Viajes: ${body.data?.length ?? 0}`);
  });

  // ─── 9. BANANERO ───────────────────────────────────────────────────────────
  test("19. BANANERO - Productores", async ({ request }) => {
    const res = await request.post("/api/v1/bananero/productores", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Productores banano: ${body.data?.length ?? 0}`);
  });

  test("20. BANANERO - Navieras y marcas", async ({ request }) => {
    const [resNav, resMar] = await Promise.all([
      request.post("/api/v1/bananero/navieras", { data: { Bdd: auth.Bdd }, headers: headers(auth.token) }),
      request.post("/api/v1/bananero/marcas", { data: { Bdd: auth.Bdd }, headers: headers(auth.token) }),
    ]);
    expect(resNav.status()).toBe(200);
    expect(resMar.status()).toBe(200);
    const nav = await resNav.json();
    const mar = await resMar.json();
    console.log(`Navieras: ${nav.data?.length ?? 0}, Marcas: ${mar.data?.length ?? 0}`);
  });

  // ─── 10. CAMARONERA ────────────────────────────────────────────────────────
  test("21. CAMARONERA - Productores", async ({ request }) => {
    const res = await request.post("/api/v1/camaronera/productores", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Productores camaronera: ${body.data?.length ?? 0}`);
  });

  test("22. CAMARONERA - Negociaciones", async ({ request }) => {
    const res = await request.post("/api/v1/camaronera/negociaciones", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Negociaciones: ${body.data?.length ?? 0}`);
  });

  // ─── 11. TESORERÍA EXTENDIDA ───────────────────────────────────────────────
  test("23. TESORERÍA - Bancos y cuentas", async ({ request }) => {
    const [resBan, resCue] = await Promise.all([
      request.post("/api/v1/tesoreria/bancos", { data: { Bdd: auth.Bdd }, headers: headers(auth.token) }),
      request.post("/api/v1/tesoreria/cuentas-banco", { data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id }, headers: headers(auth.token) }),
    ]);
    expect(resBan.status()).toBe(200);
    expect(resCue.status()).toBe(200);
    const ban = await resBan.json();
    const cue = await resCue.json();
    console.log(`Bancos: ${ban.data?.length ?? 0}, Cuentas: ${cue.data?.length ?? 0}`);
  });

  test("24. TESORERÍA - CCCP", async ({ request }) => {
    const res = await request.post("/api/v1/tesoreria/cccp", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`CCCP: ${body.data?.length ?? 0}`);
  });

  // ─── 12. ADMIN / SOPORTE ───────────────────────────────────────────────────
  test("25. ADMIN - Sucursales", async ({ request }) => {
    const res = await request.post("/api/v1/admin/sucursales", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Sucursales: ${body.data?.length ?? 0}`);
  });

  test("26. ADMIN - Perfiles", async ({ request }) => {
    const res = await request.post("/api/v1/admin/perfiles", {
      data: { Bdd: auth.Bdd },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Perfiles: ${body.data?.length ?? 0}`);
  });

  test("27. ADMIN - Usuarios", async ({ request }) => {
    const res = await request.post("/api/v1/admin/usuarios", {
      data: { Bdd: auth.Bdd },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Usuarios: ${body.data?.length ?? 0}`);
  });

  test("28. ADMIN - Config del sistema", async ({ request }) => {
    const res = await request.post("/api/v1/admin/configuracion", {
      data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
      headers: headers(auth.token),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(`Configs: ${body.data?.length ?? 0}`);
  });
});
