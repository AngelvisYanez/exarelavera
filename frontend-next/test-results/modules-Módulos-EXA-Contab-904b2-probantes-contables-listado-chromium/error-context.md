# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: modules.spec.ts >> Módulos EXA Contable Relavera — Flujos Read-Only >> 24. API - Facturación: comprobantes contables listado
- Location: tests\monitoring\modules.spec.ts:406:7

# Error details

```
Error: expect(received).toBe(expected) // Object.is equality

Expected: true
Received: false
```

# Test source

```ts
  1   | import { test, expect, Page } from "@playwright/test";
  2   | 
  3   | const USERNAME = process.env.MONITOR_USER || "0704439892001";
  4   | const PASSWORD = process.env.MONITOR_PASSWORD || "admin123";
  5   | const EMPRESA_COD = "96";
  6   | 
  7   | const ERRORS: string[] = [];
  8   | 
  9   | async function doLogin(page: Page) {
  10  |   await page.goto("/login");
  11  |   await expect(page.locator("h1")).toContainText("EXA Relavera");
  12  | 
  13  |   await page.locator('input[placeholder="Ingrese su usuario"]').fill(USERNAME);
  14  |   await page.locator('input[placeholder="Ingrese su contraseña"]').focus();
  15  | 
  16  |   const empresaSelect = page.locator("select");
  17  |   try {
  18  |     await empresaSelect.waitFor({ state: "visible", timeout: 10000 });
  19  |     await empresaSelect.selectOption(EMPRESA_COD);
  20  |   } catch {
  21  |     // Select may not appear (single empresa)
  22  |   }
  23  | 
  24  |   await page.locator('input[placeholder="Ingrese su contraseña"]').fill(PASSWORD);
  25  |   await page.click('button[type="submit"]');
  26  |   await expect(page).toHaveURL(/.*dashboard.*/, { timeout: 20000 });
  27  | }
  28  | 
  29  | async function loginAndGetToken(request: any) {
  30  |   const res = await request.post("/api/v1/auth/login", {
  31  |     data: { username: USERNAME, password: PASSWORD, empresa: EMPRESA_COD },
  32  |   });
  33  |   expect(res.status()).toBe(200);
  34  |   const body = await res.json();
> 35  |   expect(body.success).toBe(true);
      |                        ^ Error: expect(received).toBe(expected) // Object.is equality
  36  |   return body as { token: string; Bdd: string; empresa_id: string };
  37  | }
  38  | 
  39  | test.describe("Módulos EXA Contable Relavera — Flujos Read-Only", () => {
  40  |   test.beforeEach(async ({ page }) => {
  41  |     ERRORS.length = 0;
  42  |     page.on("console", (msg) => {
  43  |       if (msg.type() === "error") ERRORS.push(msg.text());
  44  |     });
  45  |   });
  46  | 
  47  |   test.afterEach(async () => {
  48  |     if (ERRORS.length > 0) {
  49  |       console.warn("Errores de consola detectados:", ERRORS);
  50  |     }
  51  |   });
  52  | 
  53  |   // ─── 1. AUTH ───────────────────────────────────────────────────────────────
  54  | 
  55  |   test("1. AUTH - Login completo y acceso al Dashboard", async ({ page }) => {
  56  |     await doLogin(page);
  57  |     await expect(page.locator("h3")).toContainText("Bienvenido");
  58  |   });
  59  | 
  60  |   // ─── 2. CLIENTES (Actores) ─────────────────────────────────────────────────
  61  | 
  62  |   test("2. CLIENTES - Listar clientes desde página Actores", async ({
  63  |     page,
  64  |   }) => {
  65  |     await doLogin(page);
  66  |     await page.click('a[href="/dashboard/actores"]');
  67  |     await page.waitForURL("/dashboard/actores");
  68  |     await page.waitForTimeout(2500);
  69  | 
  70  |     const table = page.locator("table").first();
  71  |     const rows = table.locator("tbody tr");
  72  |     const count = await rows.count();
  73  |     console.log(`Clientes encontrados: ${count}`);
  74  |     expect(count).toBeGreaterThanOrEqual(0);
  75  |   });
  76  | 
  77  |   // ─── 3. PROVEEDORES (Actores) ──────────────────────────────────────────────
  78  | 
  79  |   test("3. PROVEEDORES - Listar proveedores desde página Actores", async ({
  80  |     page,
  81  |   }) => {
  82  |     await doLogin(page);
  83  |     await page.click('a[href="/dashboard/actores"]');
  84  |     await page.waitForURL("/dashboard/actores");
  85  |     await page.waitForTimeout(500);
  86  | 
  87  |     const tabs = page.locator('[role="tablist"] button, [role="tab"]');
  88  |     const tabCount = await tabs.count();
  89  |     for (let i = 0; i < tabCount; i++) {
  90  |       const text = await tabs.nth(i).textContent();
  91  |       if (text && text.toLowerCase().includes("proveedor")) {
  92  |         await tabs.nth(i).click();
  93  |         break;
  94  |       }
  95  |     }
  96  |     await page.waitForTimeout(2500);
  97  | 
  98  |     const table = page.locator("table").first();
  99  |     const rows = table.locator("tbody tr");
  100 |     const count = await rows.count();
  101 |     console.log(`Proveedores encontrados: ${count}`);
  102 |     expect(count).toBeGreaterThanOrEqual(0);
  103 |   });
  104 | 
  105 |   // ─── 4. CATEGORÍAS (Inventario) ────────────────────────────────────────────
  106 | 
  107 |   test("4. CATEGORÍAS - Listar categorías desde Inventario", async ({
  108 |     page,
  109 |   }) => {
  110 |     await doLogin(page);
  111 |     await page.click('a[href="/dashboard/inventario"]');
  112 |     await page.waitForURL("/dashboard/inventario");
  113 |     await page.waitForTimeout(500);
  114 | 
  115 |     const tabs = page.locator('[role="tablist"] button, [role="tab"]');
  116 |     const tabCount = await tabs.count();
  117 |     for (let i = 0; i < tabCount; i++) {
  118 |       const text = await tabs.nth(i).textContent();
  119 |       if (text && text.toLowerCase().includes("categor")) {
  120 |         await tabs.nth(i).click();
  121 |         break;
  122 |       }
  123 |     }
  124 |     await page.waitForTimeout(2500);
  125 | 
  126 |     const table = page.locator("table").first();
  127 |     const rows = table.locator("tbody tr");
  128 |     const count = await rows.count();
  129 |     console.log(`Categorías encontradas: ${count}`);
  130 |     expect(count).toBeGreaterThanOrEqual(0);
  131 |   });
  132 | 
  133 |   // ─── 5. MARCAS (Inventario) ────────────────────────────────────────────────
  134 | 
  135 |   test("5. MARCAS - Listar marcas desde Inventario", async ({ page }) => {
```