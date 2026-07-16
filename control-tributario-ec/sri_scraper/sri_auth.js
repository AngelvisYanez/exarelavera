// c:/xampp/htdocs/control-tributario-ec/sri_scraper/sri_auth.js
/**
 * Módulo nativo de Autenticación para SRI en Línea
 * Portado y adaptado para ejecución 100% local en control-tributario-ec
 */

async function ensureSession(page, ruc, password, logFn) {
    logFn("Verificando sesión en portal SRI en Línea...");
    await page.goto('https://srienlinea.sri.gob.ec/sri-en-linea/contribuyente/perfil', {
        waitUntil: 'domcontentloaded',
        timeout: 90000
    });

    // Esperar suficiente tiempo para que redirija a login si no hay sesión
    await page.waitForTimeout(4000);
    const currentUrl = page.url();
    const userSelectorStr = 'input:not([type="hidden"])#usuario, input:not([type="hidden"])#username, input:not([type="hidden"])[name="username"], input:not([type="hidden"])[name="usuario"]';
    const hasInput = await page.$(userSelectorStr).catch(() => null) !== null;
    let needsLogin = currentUrl.includes('login') || currentUrl.includes('openid-connect/auth') || hasInput;

    if (needsLogin) {
        logFn("Iniciando sesión con RUC: " + ruc);
        const userSelector = await page.waitForSelector(userSelectorStr, { state: 'visible', timeout: 45000 }).catch(() => null);
        if (userSelector) {
            await page.waitForTimeout(300);
            await page.locator(userSelectorStr).first().fill(ruc).catch(async () => {
                await userSelector.type(ruc, { delay: 40 }).catch(() => null);
            });
        }

        const passSelector = await page.$('input#password, input[name="password"]').catch(() => null);
        if (passSelector) {
            await page.locator('input#password, input[name="password"]').first().fill(password).catch(async () => {
                await passSelector.type(password, { delay: 40 }).catch(() => null);
            });
        }

        logFn("Haciendo clic en el botón de ingreso...");
        const submitBtn = await page.$('button[type="submit"], input[type="submit"], button#kc-login, .btn-primary, input#kc-login').catch(() => null);
        if (submitBtn) {
            await submitBtn.click().catch(() => {});
        } else {
            await page.keyboard.press('Enter').catch(() => {});
        }

        let loggedIn = false;
        // Aumentar intentos para dar tiempo suficiente a que pase login
        for (let attempt = 0; attempt < 50; attempt++) {
            await page.waitForTimeout(500);
            const url = page.url();
            if (!url.includes('login') && !url.includes('openid-connect/auth')) {
                loggedIn = true;
                break;
            }
        }

        if (!loggedIn) {
            const errMsg = await page.evaluate(() => {
                const alert = document.querySelector('.alert-error, .alert-danger, #input-error');
                return alert ? alert.innerText.trim() : null;
            }).catch(() => null);
            if (errMsg) {
                throw new Error("Error en login SRI: " + errMsg);
            }
            throw new Error("No se pudo iniciar sesión en el SRI. Verifica tu RUC y contraseña o inténtalo más tarde.");
        }
        logFn("Sesión iniciada exitosamente en SRI en Línea.");
        await page.waitForTimeout(300);
        const espereText = await page.evaluate(() => document.body?.innerText?.includes('Espere por favor') || false).catch(() => false);
        if (espereText) {
            logFn("⚠️ Página del portal SRI se quedó en 'Espere por favor'. Deteniendo carga (X) tras 2 segundos y recargando...");
            await page.evaluate(() => window.stop()).catch(() => {});
            await page.waitForTimeout(300);
            await page.reload({ waitUntil: 'domcontentloaded', timeout: 35000 }).catch(() => {});
            await page.waitForTimeout(300);
        }
    } else {
        logFn("Sesión previa detectada activa.");
    }
    return true;
}

module.exports = { ensureSession };

