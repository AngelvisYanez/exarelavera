# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: modules.spec.ts >> Monitoreo Sintético - Portal Exa Contable Relavera >> Chequeo de Disponibilidad de la API Pública de Autenticación
- Location: tests\monitoring\modules.spec.ts:69:7

# Error details

```
Error: expect(received).toBeLessThan(expected)

Expected: < 500
Received:   500
```

# Page snapshot

```yaml
- generic [active] [ref=e1]:
  - generic [ref=e3]:
    - generic [ref=e4]:
      - heading "EXA Relavera" [level=1] [ref=e5]
      - paragraph [ref=e6]: Sistema de Gestión y Trazabilidad
    - generic [ref=e7]:
      - generic [ref=e8]:
        - generic [ref=e9]: Usuario
        - textbox "Ingrese su usuario" [ref=e10]
      - generic [ref=e11]:
        - generic [ref=e12]: Contraseña
        - textbox "Ingrese su contraseña" [ref=e13]
      - button "Ingresar al Portal" [ref=e14]
  - button "Open Next.js Dev Tools" [ref=e20] [cursor=pointer]:
    - img [ref=e21]
  - alert [ref=e24]
```

# Test source

```ts
  1  | import { test, expect } from '@playwright/test';
  2  | 
  3  | const USERNAME = process.env.MONITOR_USER || 'test_monitor';
  4  | const PASSWORD = process.env.MONITOR_PASSWORD || 'secure_password';
  5  | 
  6  | test.describe('Monitoreo Sintético - Portal Exa Contable Relavera', () => {
  7  | 
  8  |   test.beforeEach(async ({ page }) => {
  9  |     // 1. Navegar a la página de login
  10 |     await page.goto('/login');
  11 | 
  12 |     // Verificar que la página de login cargó correctamente
  13 |     await expect(page.locator('h1')).toContainText('EXA Relavera');
  14 |   });
  15 | 
  16 |   test('Flujo de Autenticación Completo y Acceso al Dashboard', async ({ page }) => {
  17 |     // 2. Rellenar el campo de usuario
  18 |     const userInput = page.locator('input[placeholder*="Ingrese su usuario"]');
  19 |     await userInput.fill(USERNAME);
  20 | 
  21 |     // 3. Quitar el foco del input para disparar 'onBlur' y cargar las empresas asociadas
  22 |     const passwordInput = page.locator('input[placeholder*="Ingrese su contraseña"]');
  23 |     await passwordInput.focus();
  24 | 
  25 |     // Esperar a que carguen las empresas si el usuario es válido
  26 |     // Si aparece el desplegable de empresas, seleccionamos la primera disponible
  27 |     const empresaSelect = page.locator('select');
  28 |     try {
  29 |       // Damos un tiempo de espera opcional para ver si carga el selector de empresas
  30 |       await empresaSelect.waitFor({ state: 'visible', timeout: 5000 });
  31 |       // Selecciona la primera opción válida (índice 1 ya que el índice 0 es '-- Seleccione --')
  32 |       await empresaSelect.selectOption({ index: 1 });
  33 |     } catch (e) {
  34 |       console.log('Información: No se requirió o no cargó selección de empresa (usuario único o sin empresas asociadas).');
  35 |     }
  36 | 
  37 |     // 4. Rellenar contraseña
  38 |     await passwordInput.fill(PASSWORD);
  39 | 
  40 |     // Escuchar errores de consola del navegador para capturar posibles anomalías de JS
  41 |     const consoleErrors: string[] = [];
  42 |     page.on('console', msg => {
  43 |       if (msg.type() === 'error') consoleErrors.push(msg.text());
  44 |     });
  45 | 
  46 |     // Escuchar respuestas de red fallidas (códigos 5xx)
  47 |     const failedRequests: string[] = [];
  48 |     page.on('response', response => {
  49 |       if (response.status() >= 500) {
  50 |         failedRequests.push(`Fallo en red: [${response.status()}] ${response.url()}`);
  51 |       }
  52 |     });
  53 | 
  54 |     // 5. Enviar el formulario
  55 |     await page.click('button[type="submit"]');
  56 | 
  57 |     // 6. Validar que redirija exitosamente al dashboard
  58 |     await expect(page).toHaveURL(/.*dashboard.*/, { timeout: 15000 });
  59 | 
  60 |     // Informar de posibles fallos no bloqueantes pero sospechosos
  61 |     if (consoleErrors.length > 0) {
  62 |       console.warn('Advertencia: Se detectaron errores de consola durante el login:', consoleErrors);
  63 |     }
  64 |     if (failedRequests.length > 0) {
  65 |       console.warn('Advertencia: Hubo respuestas de red fallidas:', failedRequests);
  66 |     }
  67 |   });
  68 | 
  69 |   test('Chequeo de Disponibilidad de la API Pública de Autenticación', async ({ request }) => {
  70 |     // Realizamos un chequeo directo de salud (health check) a las dependencias o APIs principales
  71 |     const response = await request.post('/api/auth', {
  72 |       data: {
  73 |         username: 'ping',
  74 |         password: 'ping'
  75 |       }
  76 |     });
  77 | 
  78 |     // Esperamos cualquier respuesta que no sea un error interno del servidor (500)
> 79 |     expect(response.status()).toBeLessThan(500);
     |                               ^ Error: expect(received).toBeLessThan(expected)
  80 |   });
  81 | });
  82 | 
```