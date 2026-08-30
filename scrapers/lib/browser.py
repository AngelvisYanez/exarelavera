"""Motor de navegador Playwright con anti-detección."""
from playwright.sync_api import sync_playwright
from .constants import NAVIGATION_TIMEOUT


class Sribrowser:
    """Wrapper de Playwright para scraping SRI."""

    def __init__(self, headless=True, proxy_url=None):
        self.headless = headless
        self.proxy_url = proxy_url
        self.playwright = None
        self.browser = None
        self.context = None
        self.page = None

    def launch(self):
        """Lanza el navegador Chromium con configuración anti-detección."""
        self.playwright = sync_playwright().start()

        launch_args = [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-web-security',
            '--disable-blink-features=AutomationControlled',
            '--disable-features=IsolateOrigins,site-per-process',
            '--hide-scrollbars',
        ]

        self.browser = self.playwright.chromium.launch(
            headless=self.headless,
            args=launch_args,
        )

        context_options = {
            'viewport': {'width': 1366, 'height': 768},
            'user_agent': (
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                'AppleWebKit/537.36 (KHTML, like Gecko) '
                'Chrome/131.0.0.0 Safari/537.36'
            ),
            'locale': 'es-EC',
            'timezone_id': 'America/Guayaquil',
            'extra_http_headers': {
                'Accept-Language': 'es-EC,es;q=0.9',
            },
        }

        if self.proxy_url:
            from urllib.parse import urlparse
            p = urlparse(self.proxy_url)
            context_options['proxy'] = {
                'server': f'{p.scheme}://{p.hostname}:{p.port}',
                'username': p.username or None,
                'password': p.password or None,
            }

        self.context = self.browser.new_context(**context_options)
        self.page = self.context.new_page()

        # Anti-detección: ocultar huellas de webdriver
        self.page.add_init_script("""
            Object.defineProperty(navigator, 'webdriver', {get: () => false});
            Object.defineProperty(navigator, 'plugins', {get: () => [1,2,3,4,5]});
            Object.defineProperty(navigator, 'languages', {get: () => ['es-EC','es','en']});
            Object.defineProperty(navigator, 'hardwareConcurrency', {get: () => 8});
        """)

    def new_page(self):
        """Crea una nueva pestaña en el mismo contexto."""
        return self.context.new_page()

    def close(self):
        """Cierra el navegador y libera recursos."""
        try:
            if self.context:
                self.context.close()
        except Exception:
            pass
        try:
            if self.browser:
                self.browser.close()
        except Exception:
            pass
        try:
            if self.playwright:
                self.playwright.stop()
        except Exception:
            pass
