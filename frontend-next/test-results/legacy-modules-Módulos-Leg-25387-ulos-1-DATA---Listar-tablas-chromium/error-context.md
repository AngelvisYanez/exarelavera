# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: legacy-modules.spec.ts >> Módulos Legacy — APIs Read-Only (11 módulos) >> 1. DATA - Listar tablas
- Location: tests\monitoring\legacy-modules.spec.ts:38:7

# Error details

```
Error: expect(received).toBe(expected) // Object.is equality

Expected: true
Received: false
```

# Test source

```ts
  1   | import { test, expect } from "@playwright/test";
  2   | 
  3   | const USERNAME = process.env.MONITOR_USER || "0704439892001";
  4   | const PASSWORD = process.env.MONITOR_PASSWORD || "admin123";
  5   | const EMPRESA_COD = "96";
  6   | 
  7   | interface AuthResult {
  8   |   token: string;
  9   |   Bdd: string;
  10  |   empresa_id: string;
  11  |   success: boolean;
  12  |   [key: string]: unknown;
  13  | }
  14  | 
  15  | async function loginAndGetToken(request: any): Promise<AuthResult> {
  16  |   const res = await request.post("/api/v1/auth/login", {
  17  |     data: { username: USERNAME, password: PASSWORD, empresa: EMPRESA_COD },
  18  |   });
  19  |   expect(res.status()).toBe(200);
  20  |   const body = await res.json();
> 21  |   expect(body.success).toBe(true);
      |                        ^ Error: expect(received).toBe(expected) // Object.is equality
  22  |   return body as AuthResult;
  23  | }
  24  | 
  25  | function headers(token: string) {
  26  |   return { Authorization: `Bearer ${token}` };
  27  | }
  28  | 
  29  | test.describe("Módulos Legacy — APIs Read-Only (11 módulos)", () => {
  30  |   let auth: AuthResult;
  31  | 
  32  |   test.beforeAll(async ({ request }) => {
  33  |     auth = await loginAndGetToken(request);
  34  |     console.log(`Auth OK — Bdd: ${auth.Bdd}, empresa: ${auth.empresa_id}`);
  35  |   });
  36  | 
  37  |   // ─── 1. DATA API GENÉRICA ────────────────────────────────────────────────
  38  |   test("1. DATA - Listar tablas", async ({ request }) => {
  39  |     const res = await request.post("/api/v1/data/tables", {
  40  |       data: { Bdd: auth.Bdd },
  41  |       headers: headers(auth.token),
  42  |     });
  43  |     expect(res.status()).toBe(200);
  44  |     const body = await res.json();
  45  |     expect(body.success).toBe(true);
  46  |     expect(Array.isArray(body.tables)).toBe(true);
  47  |     console.log(`Tablas encontradas: ${body.tables.length}`);
  48  |   });
  49  | 
  50  |   test("2. DATA - Query SQL read-only", async ({ request }) => {
  51  |     const res = await request.post("/api/v1/data/query", {
  52  |       data: { Bdd: auth.Bdd, sql: "SELECT 1 AS test" },
  53  |       headers: headers(auth.token),
  54  |     });
  55  |     expect(res.status()).toBe(200);
  56  |     const body = await res.json();
  57  |     expect(body.success).toBe(true);
  58  |     expect(String(body.data[0].test)).toBe("1");
  59  |   });
  60  | 
  61  |   test("3. DATA - Describe tabla", async ({ request }) => {
  62  |     const res = await request.post("/api/v1/data/describe", {
  63  |       data: { Bdd: auth.Bdd, table: "plan_cuenta" },
  64  |       headers: headers(auth.token),
  65  |     });
  66  |     expect(res.status()).toBe(200);
  67  |     const body = await res.json();
  68  |     expect(body.success).toBe(true);
  69  |     expect(Array.isArray(body.columns)).toBe(true);
  70  |     console.log(`Columnas en plan_cuenta: ${body.columns.length}`);
  71  |   });
  72  | 
  73  |   test("4. DATA - Listar registros", async ({ request }) => {
  74  |     const res = await request.post("/api/v1/data/list", {
  75  |       data: { Bdd: auth.Bdd, table: "plan_cuenta", where: { Emp_Cod: auth.empresa_id }, limit: 5 },
  76  |       headers: headers(auth.token),
  77  |     });
  78  |     expect(res.status()).toBe(200);
  79  |     const body = await res.json();
  80  |     expect(body.success).toBe(true);
  81  |     console.log(`Registros en plan_cuenta: ${body.total}`);
  82  |   });
  83  | 
  84  |   // ─── 2. CONTABILIDAD ──────────────────────────────────────────────────────
  85  |   test("5. CONTABILIDAD - Plan de cuentas", async ({ request }) => {
  86  |     const res = await request.post("/api/v1/contabilidad/plan-cuentas", {
  87  |       data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
  88  |       headers: headers(auth.token),
  89  |     });
  90  |     expect(res.status()).toBe(200);
  91  |     const body = await res.json();
  92  |     expect(body.success).toBe(true);
  93  |     console.log(`Cuentas contables: ${body.data?.length ?? 0}`);
  94  |   });
  95  | 
  96  |   test("6. CONTABILIDAD - Periodos contables", async ({ request }) => {
  97  |     const res = await request.post("/api/v1/contabilidad/periodos", {
  98  |       data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
  99  |       headers: headers(auth.token),
  100 |     });
  101 |     expect(res.status()).toBe(200);
  102 |     const body = await res.json();
  103 |     expect(body.success).toBe(true);
  104 |     console.log(`Periodos: ${body.data?.length ?? 0}`);
  105 |   });
  106 | 
  107 |   test("7. CONTABILIDAD - Tipos de comprobante", async ({ request }) => {
  108 |     const res = await request.post("/api/v1/contabilidad/tipos-comprobante", {
  109 |       data: { Bdd: auth.Bdd },
  110 |       headers: headers(auth.token),
  111 |     });
  112 |     expect(res.status()).toBe(200);
  113 |     const body = await res.json();
  114 |     expect(body.success).toBe(true);
  115 |     console.log(`Tipos comprobante: ${body.data?.length ?? 0}`);
  116 |   });
  117 | 
  118 |   test("8. CONTABILIDAD - Comprobantes", async ({ request }) => {
  119 |     const res = await request.post("/api/v1/contabilidad/comprobantes", {
  120 |       data: { Bdd: auth.Bdd, Emp_Cod: auth.empresa_id },
  121 |       headers: headers(auth.token),
```