import { test, expect } from '@playwright/test';

const USERNAME = process.env.MONITOR_USER || 'test_monitor';
const PASSWORD = process.env.MONITOR_PASSWORD || 'secure_password';

test.describe('Monitoreo Sintético - Portal Exa Contable Relavera', () => {

  test.beforeEach(async ({ page }) => {
    // 1. Navegar a la página de login
    await page.goto('/login');

    // Verificar que la página de login cargó correctamente
    await expect(page.locator('h1')).toContainText('EXA Relavera');
  });

  test('Flujo de Autenticación Completo y Acceso al Dashboard', async ({ page }) => {
    // 2. Rellenar el campo de usuario
    const userInput = page.locator('input[placeholder*="Ingrese su usuario"]');
    await userInput.fill(USERNAME);

    // 3. Quitar el foco del input para disparar 'onBlur' y cargar las empresas asociadas
    const passwordInput = page.locator('input[placeholder*="Ingrese su contraseña"]');
    await passwordInput.focus();

    // Esperar a que carguen las empresas si el usuario es válido
    // Si aparece el desplegable de empresas, seleccionamos la primera disponible
    const empresaSelect = page.locator('select');
    try {
      // Damos un tiempo de espera opcional para ver si carga el selector de empresas
      await empresaSelect.waitFor({ state: 'visible', timeout: 5000 });
      // Selecciona la primera opción válida (índice 1 ya que el índice 0 es '-- Seleccione --')
      await empresaSelect.selectOption({ index: 1 });
    } catch (e) {
      console.log('Información: No se requirió o no cargó selección de empresa (usuario único o sin empresas asociadas).');
    }

    // 4. Rellenar contraseña
    await passwordInput.fill(PASSWORD);

    // Escuchar errores de consola del navegador para capturar posibles anomalías de JS
    const consoleErrors: string[] = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    // Escuchar respuestas de red fallidas (códigos 5xx)
    const failedRequests: string[] = [];
    page.on('response', response => {
      if (response.status() >= 500) {
        failedRequests.push(`Fallo en red: [${response.status()}] ${response.url()}`);
      }
    });

    // 5. Enviar el formulario
    await page.click('button[type="submit"]');

    // 6. Validar que redirija exitosamente al dashboard
    await expect(page).toHaveURL(/.*dashboard.*/, { timeout: 15000 });

    // Informar de posibles fallos no bloqueantes pero sospechosos
    if (consoleErrors.length > 0) {
      console.warn('Advertencia: Se detectaron errores de consola durante el login:', consoleErrors);
    }
    if (failedRequests.length > 0) {
      console.warn('Advertencia: Hubo respuestas de red fallidas:', failedRequests);
    }
  });

  test('Chequeo de Disponibilidad de la API Pública de Autenticación', async ({ request }) => {
    // Realizamos un chequeo directo de salud (health check) a las dependencias o APIs principales
    const response = await request.post('/api/auth', {
      data: {
        username: 'ping',
        password: 'ping'
      }
    });

    // Esperamos cualquier respuesta que no sea un error interno del servidor (500)
    expect(response.status()).toBeLessThan(500);
  });
});
