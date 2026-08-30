"""Resolución de CAPTCHA para el portal SRI."""


class CaptchaSolver:
    """
    Resuelve CAPTCHA reCAPTCHA v2 Enterprise del SRI.
    
    Estrategias (en orden de prioridad):
    1. Buster extension (gratis, resuelve audio challenges)
    2. API key externa (Anti-Captcha, Scrapeless, etc.)
    3. Sin resolver (fallback)
    """

    def __init__(self, page, api_key=None, provider='anticaptcha'):
        self.page = page
        self.api_key = api_key
        self.provider = provider

    def solve(self, action='consulta_cel_recibidos'):
        """
        Intenta resolver el CAPTCHA.
        Retorna True si se resolvió, False si no.
        """
        # 1. Intentar Buster extension
        if self._try_buster():
            return True

        # 2. Intentar API externa
        if self.api_key:
            if self.provider == 'anticaptcha':
                return self._solve_anticaptcha(action)
            elif self.provider == 'scrapeless':
                return self._solve_scrapeless(action)

        # 3. Sin resolver
        return False

    def _try_buster(self):
        """Intenta resolver con la extensión Buster (anti-captcha audio)."""
        try:
            for frame in self.page.frames:
                if 'api2/bframe' in frame.url or frame.name.startswith('c-'):
                    btn = frame.locator('#solver-button')
                    if btn.is_visible():
                        btn.click()
                        self.page.wait_for_timeout(5000)
                        return True
        except Exception:
            pass
        return False

    def _solve_anticaptcha(self, action):
        """Resuelve con la API de Anti-Captcha."""
        try:
            import requests
            import time as _time

            current_url = self.page.url

            # Crear tarea
            resp = requests.post(
                'https://api.anti-captcha.com/createTask',
                json={
                    'clientKey': self.api_key,
                    'task': {
                        'type': 'RecaptchaV2EnterpriseTaskProxyless',
                        'websiteURL': current_url,
                        'websiteKey': '6LdukTQsAAAAAIcciM4GZq4ibeyplUhmWvlScuQE',
                        'websiteAction': action,
                    },
                },
                timeout=30,
            )
            data = resp.json()
            task_id = data.get('taskId')
            if not task_id:
                print(f"[CAPTCHA] Anti-Captcha: error creando tarea: {data}")
                return False

            # Esperar solución
            for _ in range(30):
                _time.sleep(3)
                result = requests.post(
                    'https://api.anti-captcha.com/getTaskResult',
                    json={'clientKey': self.api_key, 'taskId': task_id},
                    timeout=30,
                ).json()

                if result.get('status') == 'ready':
                    token = result['solution']['gRecaptchaResponse']
                    return self._inject_token(token)

            print("[CAPTCHA] Anti-Captcha: timeout esperando solución")
            return False

        except Exception as e:
            print(f"[CAPTCHA] Anti-Captcha error: {e}")
            return False

    def _solve_scrapeless(self, action):
        """Resuelve con la API de Scrapeless."""
        try:
            import requests
            import time as _time

            current_url = self.page.url

            resp = requests.post(
                'https://api.scrapeless.com/api/v1/createTask',
                headers={
                    'Content-Type': 'application/json',
                    'x-api-token': self.api_key,
                },
                json={
                    'actor': 'captcha.recaptcha',
                    'input': {
                        'version': 'v2',
                        'pageURL': current_url,
                        'siteKey': '6LdukTQsAAAAAIcciM4GZq4ibeyplUhmWvlScuQE',
                        'pageAction': action,
                    },
                },
                timeout=30,
            )
            data = resp.json()
            task_id = data.get('taskId')
            if not task_id:
                return False

            for _ in range(30):
                _time.sleep(2)
                result = requests.get(
                    f'https://api.scrapeless.com/api/v1/getTaskResult/{task_id}',
                    headers={'x-api-token': self.api_key},
                    timeout=30,
                ).json()

                if result.get('status') == 'ready' and result.get('success'):
                    token = result['data']['solution']['gRecaptchaResponse']
                    return self._inject_token(token)

            return False

        except Exception as e:
            print(f"[CAPTCHA] Scrapeless error: {e}")
            return False

    def _inject_token(self, token):
        """Inyecta el token resuelto en la página."""
        self.page.evaluate("""(token) => {
            // Inyectar en textarea
            const ta = document.getElementById('g-recaptcha-response');
            if (ta) ta.value = token;

            // Mock de grecaptcha
            if (!window.grecaptcha) window.grecaptcha = {};
            const g = window.grecaptcha;
            if (!g.enterprise) g.enterprise = {};
            g.enterprise.execute = () => Promise.resolve(token);
            g.enterprise.ready = (cb) => { if (typeof cb === 'function') cb(); };

            // Sobrescribir executeRecaptcha
            window.executeRecaptcha = (_action, _source) => {
                const ta2 = document.getElementById('g-recaptcha-response');
                if (ta2) ta2.value = token;
                return token;
            };
        }""", token)
        return True
