const { chromium } = require('playwright-extra');
const stealth = require('puppeteer-extra-plugin-stealth')();
chromium.use(stealth);
const path = require('path');

async function main() {
    const userDataDir = path.resolve(__dirname, 'chrome_profile');
    console.log('===========================================================');
    console.log('Abriendo el perfil del bot para iniciar sesión en Google...');
    console.log('Por favor, inicia sesión con tu cuenta de Google en la ventana que se abrirá.');
    console.log('Cuando hayas terminado y estés en la página principal de Google, cierra esta ventana de terminal o presiona Ctrl+C.');
    console.log('===========================================================');

    const context = await chromium.launchPersistentContext(userDataDir, {
        channel: 'chrome',
        headless: false,
        viewport: { width: 1366, height: 768 },
        args: ['--no-sandbox']
    });

    const page = context.pages()[0];
    await page.goto('https://accounts.google.com/');
}

main().catch(console.error);
