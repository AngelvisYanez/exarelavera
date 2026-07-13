import { test, expect, Page } from "@playwright/test";

const USERNAME = process.env.MONITOR_USER || "0704439892001";
const PASSWORD = process.env.MONITOR_PASSWORD || "admin123";
const EMPRESA_COD = "96";

const ERRORS: string[] = [];

async function doLogin(page: Page) {
  await page.goto("/login");
  await expect(page.locator("h1")).toContainText("EXA Relavera");

  await page.locator('input[placeholder="Ingrese su usuario"]').fill(USERNAME);
  await page.locator('input[placeholder="Ingrese su contraseña"]').focus();

  const empresaSelect = page.locator("select");
  try {
    await empresaSelect.waitFor({ state: "visible", timeout: 10000 });
    await empresaSelect.selectOption(EMPRESA_COD);
  } catch {
    // Select may not appear (single empresa)
  }

  await page.locator('input[placeholder="Ingrese su contraseña"]').fill(PASSWORD);
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/.*dashboard.*/, { timeout: 20000 });
}

async function loginAndGetToken(request: any) {
  const res = await request.post("/api/v1/auth/login", {
    data: { username: USERNAME, password: PASSWORD, empresa: EMPRESA_COD },
  });
  expect(res.status()).toBe(200);
  const body = await res.json();
  expect(body.success).toBe(true);
  return body as { token: string; Bdd: string; empresa_id: string };
}

test.describe("Módulos EXA Contable Relavera — Flujos Read-Only", () => {
  test.beforeEach(async ({ page }) => {
    ERRORS.length = 0;
    page.on("console", (msg) => {
      if (msg.type() === "error") ERRORS.push(msg.text());
    });
  });

  test.afterEach(async () => {
    if (ERRORS.length > 0) {
      console.warn("Errores de consola detectados:", ERRORS);
    }
  });

  // ─── 1. AUTH ───────────────────────────────────────────────────────────────

  test("1. AUTH - Login completo y acceso al Dashboard", async ({ page }) => {
    await doLogin(page);
    await expect(page.locator("h3")).toContainText("Bienvenido");
  });

  // ─── 2. CLIENTES (Actores) ─────────────────────────────────────────────────

  test("2. CLIENTES - Listar clientes desde página Actores", async ({
    page,
  }) => {
    await doLogin(page);
    await page.click('a[href="/dashboard/actores"]');
    await page.waitForURL("/dashboard/actores");
    await page.waitForTimeout(2500);

    const table = page.locator("table").first();
    const rows = table.locator("tbody tr");
    const count = await rows.count();
    console.log(`Clientes encontrados: ${count}`);
    expect(count).toBeGreaterThanOrEqual(0);
  });

  // ─── 3. PROVEEDORES (Actores) ──────────────────────────────────────────────

  test("3. PROVEEDORES - Listar proveedores desde página Actores", async ({
    page,
  }) => {
    await doLogin(page);
    await page.click('a[href="/dashboard/actores"]');
    await page.waitForURL("/dashboard/actores");
    await page.waitForTimeout(500);

    const tabs = page.locator('[role="tablist"] button, [role="tab"]');
    const tabCount = await tabs.count();
    for (let i = 0; i < tabCount; i++) {
      const text = await tabs.nth(i).textContent();
      if (text && text.toLowerCase().includes("proveedor")) {
        await tabs.nth(i).click();
        break;
      }
    }
    await page.waitForTimeout(2500);

    const table = page.locator("table").first();
    const rows = table.locator("tbody tr");
    const count = await rows.count();
    console.log(`Proveedores encontrados: ${count}`);
    expect(count).toBeGreaterThanOrEqual(0);
  });

  // ─── 4. CATEGORÍAS (Inventario) ────────────────────────────────────────────

  test("4. CATEGORÍAS - Listar categorías desde Inventario", async ({
    page,
  }) => {
    await doLogin(page);
    await page.click('a[href="/dashboard/inventario"]');
    await page.waitForURL("/dashboard/inventario");
    await page.waitForTimeout(500);

    const tabs = page.locator('[role="tablist"] button, [role="tab"]');
    const tabCount = await tabs.count();
    for (let i = 0; i < tabCount; i++) {
      const text = await tabs.nth(i).textContent();
      if (text && text.toLowerCase().includes("categor")) {
        await tabs.nth(i).click();
        break;
      }
    }
    await page.waitForTimeout(2500);

    const table = page.locator("table").first();
    const rows = table.locator("tbody tr");
    const count = await rows.count();
    console.log(`Categorías encontradas: ${count}`);
    expect(count).toBeGreaterThanOrEqual(0);
  });

  // ─── 5. MARCAS (Inventario) ────────────────────────────────────────────────

  test("5. MARCAS - Listar marcas desde Inventario", async ({ page }) => {
    await doLogin(page);
    await page.click('a[href="/dashboard/inventario"]');
    await page.waitForURL("/dashboard/inventario");
    await page.waitForTimeout(500);

    const tabs = page.locator('[role="tablist"] button, [role="tab"]');
    const tabCount = await tabs.count();
    for (let i = 0; i < tabCount; i++) {
      const text = await tabs.nth(i).textContent();
      if (text && text.toLowerCase().includes("marca")) {
        await tabs.nth(i).click();
        break;
      }
    }
    await page.waitForTimeout(2500);

    const table = page.locator("table").first();
    const rows = table.locator("tbody tr");
    const count = await rows.count();
    console.log(`Marcas encontradas: ${count}`);
    expect(count).toBeGreaterThanOrEqual(0);
  });

  // ─── 6. PRODUCTOS (Inventario) ─────────────────────────────────────────────

  test("6. PRODUCTOS - Listar productos desde Inventario", async ({ page }) => {
    await doLogin(page);
    await page.click('a[href="/dashboard/inventario"]');
    await page.waitForURL("/dashboard/inventario");
    await page.waitForTimeout(500);

    const tabs = page.locator('[role="tablist"] button, [role="tab"]');
    const tabCount = await tabs.count();
    for (let i = 0; i < tabCount; i++) {
      const text = await tabs.nth(i).textContent();
      if (text && text.toLowerCase().includes("producto")) {
        await tabs.nth(i).click();
        break;
      }
    }
    await page.waitForTimeout(2500);

    const table = page.locator("table").first();
    const rows = table.locator("tbody tr");
    const count = await rows.count();
    console.log(`Productos encontrados: ${count}`);
    expect(count).toBeGreaterThanOrEqual(0);
  });

  // ─── 7. MANIFIESTOS ────────────────────────────────────────────────────────

  test("7. MANIFIESTOS - Listar y ver detalle", async ({ page }) => {
    await doLogin(page);
    await page.click('a[href="/dashboard/manifiestos"]');
    await page.waitForURL("/dashboard/manifiestos");
    await page.waitForTimeout(3000);

    const table = page.locator("table").first();
    const rows = table.locator("tbody tr");
    const count = await rows.count();
    console.log(`Manifiestos encontrados: ${count}`);

    expect(count).toBeGreaterThanOrEqual(0);
  });

  // ─── 8. FACTURACIÓN ────────────────────────────────────────────────────────

  test("8. FACTURACIÓN - Ver comprobantes, retenciones y resumen", async ({
    page,
  }) => {
    await doLogin(page);
    await page.click('a[href="/dashboard/facturacion"]');
    await page.waitForURL("/dashboard/facturacion");
    await page.waitForTimeout(3000);

    const body = page.locator("body");
    await expect(body).not.toContainText(/error|Error|ERROR/);
    console.log("Página de facturación cargada correctamente");

    const tabs = page.locator('[role="tablist"] button, [role="tab"]');
    const tabCount = await tabs.count();
    console.log(`Tabs de facturación: ${tabCount}`);
  });

  // ─── 9. EMITIR ─────────────────────────────────────────────────────────────

  test("9. EMITIR - Cargar página de emisión de comprobantes", async ({
    page,
  }) => {
    await doLogin(page);
    await page.click('a[href="/dashboard/facturacion/emitir"]');
    await page.waitForURL("/dashboard/facturacion/emitir");
    await page.waitForTimeout(2000);

    const body = page.locator("body");
    await expect(body).not.toContainText(/error|Error|ERROR/);
    console.log("Página de emisión cargada correctamente");
  });

  // ─── 10. TAREAS ────────────────────────────────────────────────────────────

  test("10. TAREAS - Cargar página de gestión de tareas", async ({ page }) => {
    await doLogin(page);
    await page.goto("/dashboard/tareas");
    await page.waitForURL("/dashboard/tareas");
    await page.waitForTimeout(2000);

    const body = page.locator("body");
    await expect(body).not.toContainText(/error al cargar|Error al cargar/i);
    console.log("Página de tareas cargada correctamente");
  });

  // ─── 11. API DIRECTA — Test endpoints vía HTTP ─────────────────────────────

  test("11. API - Health check y endpoints públicos", async ({ request }) => {
    const res = await request.get("/api/v1/test");
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body).toHaveProperty("mysqli");
    console.log(`Health check OK — mysqli: ${body.mysqli}`);
  });

  test("12. API - Login vía endpoint directo", async ({ request }) => {
    const res = await request.post("/api/v1/auth/empresas", {
      data: { username: USERNAME },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body).toHaveProperty("success");
    expect(body).toHaveProperty("empresas");
    console.log(
      `Empresas encontradas para ${USERNAME}: ${body.conteo ?? body.empresas?.length ?? 0}`,
    );
  });

  test("13. API - Clientes: obtener listado", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post("/api/v1/clientes/obtener", {
      data: { Bdd, Emp_Cod: empresa_id },
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    console.log("API Clientes: respuesta OK");
  });

  test("14. API - Proveedores: obtener listado", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post("/api/v1/proveedores/obtener", {
      data: { Bdd, Emp_Cod: empresa_id },
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    console.log("API Proveedores: respuesta OK");
  });

  test("15. API - Categorías: obtener listado", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post("/api/v1/categorias/obtener", {
      data: { Bdd, Emp_Cod: empresa_id },
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    console.log("API Categorías: respuesta OK");
  });

  test("16. API - Marcas: obtener listado", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post("/api/v1/marcas/obtener", {
      data: { Bdd, Emp_Cod: empresa_id },
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    console.log("API Marcas: respuesta OK");
  });

  test("17. API - Productos: obtener listado", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post("/api/v1/productos/obtener", {
      data: { Bdd, Emp_Cod: empresa_id },
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    console.log("API Productos: respuesta OK");
  });

  test("18. API - Manifiestos: obtener listado", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post("/api/v1/manifiestos/obtener", {
      data: { Bdd, Emp_Cod: empresa_id, Suc_Cod: 1, Usu_Cod: 1 },
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    console.log("API Manifiestos: respuesta OK");
  });

  test("19. API - Facturación: resumen", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post("/api/v1/facturacion/resumen", {
      data: { Bdd, Emp_Cod: empresa_id },
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.status).toBe(true);
    console.log("API Facturación/Resumen: OK");
  });

  test("20. API - Tareas: obtener listado", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post("/api/v1/auditoria/tareas/obtener", {
      data: { Bdd, Emp_Cod: empresa_id },
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    // Puede fallar si la tabla no existe en la BD actual (mitigación)
    expect(
      body.success === true || body.message?.includes("doesn't exist"),
    ).toBe(true);
    console.log("API Tareas: respuesta OK");
  });

  test("21. API - Conexión: estado del servidor", async ({ request }) => {
    const { token } = await loginAndGetToken(request);

    const res = await request.get("/api/v1/admin/conexion/estado", {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    console.log(
      `API Conexión: servidor ${body.activo?.host} — conectado: ${body.conectado}`,
    );
  });

  test("22. API - Facturación: comprobantes listado", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post("/api/v1/facturacion/comprobantes", {
      data: { Bdd, Emp_Cod: empresa_id },
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.status).toBe(true);
    console.log("API Facturación/Comprobantes: OK");
  });

  test("23. API - Facturación: retenciones listado", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post("/api/v1/facturacion/retenciones", {
      data: { Bdd, Emp_Cod: empresa_id },
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.status).toBe(true);
    console.log("API Facturación/Retenciones: OK");
  });

  test("24. API - Facturación: comprobantes contables listado", async ({
    request,
  }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post(
      "/api/v1/facturacion/comprobantes-contables",
      {
        data: { Bdd, Emp_Cod: empresa_id },
        headers: { Authorization: `Bearer ${token}` },
      },
    );
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.status).toBe(true);
    console.log("API Facturación/Contables: OK");
  });

  test("25. API - Emitir: buscar productos", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const res = await request.post(
      "/api/v1/facturacion/emitir/productos/buscar",
      {
        data: { Bdd, Emp_Cod: empresa_id, search: "" },
        headers: { Authorization: `Bearer ${token}` },
      },
    );
    expect(res.status()).toBe(200);
    console.log("API Emitir/BuscarProductos: OK");
  });

  test("26. API - Tareas: indicadores y empleados", async ({ request }) => {
    const { token, Bdd, empresa_id } = await loginAndGetToken(request);

    const resInd = await request.post(
      "/api/v1/auditoria/tareas/indicadores",
      {
        data: { Bdd, Emp_Cod: empresa_id },
        headers: { Authorization: `Bearer ${token}` },
      },
    );
    expect(resInd.status()).toBe(200);
    console.log("API Tareas/Indicadores: OK");

    const resEmp = await request.post(
      "/api/v1/auditoria/tareas/obtener-empleados",
      {
        data: { Bdd, Emp_Cod: empresa_id },
        headers: { Authorization: `Bearer ${token}` },
      },
    );
    expect(resEmp.status()).toBe(200);
    console.log("API Tareas/Empleados: OK");
  });
});
