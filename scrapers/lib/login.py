"""Login en el portal SRI con manejo de CAPTCHA."""
import time
from .constants import SRI_BASE


class Srilogin:
    """Maneja autenticación en srienlinea.sri.gob.ec."""

    def __init__(self, browser):
        self.browser = browser
        self.page = browser.page

    def do_login(self, ruc, clave, max_retries=3):
        """Intenta iniciar sesión. Retorna True si tiene éxito."""
        for attempt in range(max_retries):
            try:
                if self._try_login(ruc, clave):
                    return True
                if attempt < max_retries - 1:
                    time.sleep(3 * (attempt + 1))
            except Exception as e:
                print(f"[Login] Error intento {attempt + 1}: {e}")
                if attempt < max_retries - 1:
                    time.sleep(3 * (attempt + 1))
        return False

    def _try_login(self, ruc, clave):
        """Un solo intento de login."""
        page = self.page

        # 1. Navegar al portal SRI
        page.goto(
            f'{SRI_BASE}/sri-en-linea/contribuyente/perfil',
            wait_until='domcontentloaded',
            timeout=60000,
        )
        page.wait_for_timeout(5000)

        # 2. Verificar si ya hay sesión activa
        if not self._needs_login(page):
            return True

        # 3. Llenar formulario de usuario
        user_input = page.locator(
            'input#usuario:not([type="hidden"]), '
            'input[name="usuario"]:not([type="hidden"])'
        ).first
        user_input.wait_for(state='visible', timeout=30000)
        user_input.click()
        user_input.fill('')  # Limpiar autocompletado
        user_input.press_sequentially(ruc, delay=50)
        page.wait_for_timeout(800)

        # 4. Llenar contraseña
        pass_input = page.locator(
            'input#password:not([type="hidden"]), '
            'input[name="password"]:not([type="hidden"])'
        ).first
        pass_input.wait_for(state='visible', timeout=10000)
        pass_input.click()
        pass_input.fill('')
        pass_input.press_sequentially(clave, delay=50)
        page.wait_for_timeout(800)

        # 5. Resolver CAPTCHA si existe
        self._attempt_captcha(page)

        # 6. Hacer clic en Ingresar
        submit_btn = page.locator(
            'button[type="submit"], input[type="submit"], '
            'button#kc-login, .btn-primary, input#kc-login'
        ).first
        try:
            submit_btn.wait_for(timeout=10000)
            submit_btn.click(force=True)
        except Exception:
            page.evaluate("""() => {
                const btn = document.querySelector(
                    'button[type="submit"], input[type="submit"], button#kc-login'
                );
                if (btn) btn.click();
            }""")

        # 7. Esperar redirección post-login
        return self._wait_for_redirect(page, max_wait=30)

    def _needs_login(self, page):
        """Verifica si se necesita login (no hay sesión activa)."""
        return page.evaluate("""() => {
            const url = window.location.href;
            if (url.includes('login') || url.includes('openid-connect')) return true;
            const input = document.querySelector(
                'input#usuario:not([type="hidden"]), input[name="usuario"]:not([type="hidden"])'
            );
            return input !== null;
        }""")

    def _wait_for_redirect(self, page, max_wait=30):
        """Espera a que el login redirija al portal."""
        for _ in range(max_wait):
            time.sleep(1)
            try:
                url = page.url
                if not any(x in url for x in ['login', 'openid-connect', 'keycloak']):
                    page.wait_for_timeout(3000)
                    return True
            except Exception:
                pass
        return False

    def _attempt_captcha(self, page):
        """
        Intenta resolver CAPTCHA reCAPTCHA v2.
        Si no hay solver configurado, intenta con Buster extension.
        """
        # Verificar si hay reCAPTCHA visible
        has_captcha = page.evaluate("""() => {
            return document.querySelector('.g-recaptcha') !== null ||
                   document.querySelector('iframe[src*="recaptcha"]') !== null ||
                   document.getElementById('g-recaptcha-response') !== null;
        }""")

        if not has_captcha:
            return

        # Intentar con Buster extension (si está instalada)
        for frame in page.frames:
            if 'api2/bframe' in frame.url or frame.name.startswith('c-'):
                try:
                    btn = frame.locator('#solver-button')
                    if btn.is_visible():
                        btn.click()
                        page.wait_for_timeout(5000)
                        return
                except Exception:
                    pass

        # Sin solver: esperar a que el usuario resuelva manualmente
        # (en modo headless esto fallará, pero es el fallback)
        print("[Login] CAPTCHA detectado sin solver. Esperando...")
