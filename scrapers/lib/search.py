"""Búsqueda de comprobantes en el portal SRI."""
import time
from .constants import SRI_BASE


class Srisearch:
    """Busca comprobantes recibidos/emitidos en srienlinea.sri.gob.ec."""

    def __init__(self, browser):
        self.browser = browser
        self.page = browser.page

    def navigate_to_comprobantes(self, flow='recibidos'):
        """Navega a la página de comprobantes recibidos o emitidos."""
        page = self.page
        redirect_code = '56' if flow == 'emitidos' else '57'

        page.goto(
            f'{SRI_BASE}/tuportal-internet/accederAplicacion.jspa'
            f'?redireccion={redirect_code}&idGrupo=55',
            wait_until='domcontentloaded',
            timeout=60000,
        )
        page.wait_for_timeout(3000)

        # Verificar si el formulario de búsqueda está visible
        has_form = page.locator(
            'select[id*="ano"], select[name*="ano"], form[id*="frmPrincipal"]'
        ).first.is_visible(timeout=10000)

        if not has_form:
            # Intentar seleccionar opción RUC
            ruc_radio = page.locator('input[id*="opciones:0"], input[value="ruc"]').first
            if ruc_radio.is_visible():
                ruc_radio.click()
                page.wait_for_timeout(2000)
            page.wait_for_timeout(3000)

        # Verificar que no nos redirigió al login
        if 'login' in page.url:
            return False

        return True

    def search_period(self, year, month, day, tipo_comprobante='todos'):
        """
        Busca comprobantes para un período específico.
        Retorna lista de dicts con info de cada comprobante encontrado.
        """
        page = self.page

        # Navegar a comprobantes recibidos
        if not self.navigate_to_comprobantes('recibidos'):
            return []

        page.wait_for_timeout(2000)

        # Seleccionar año
        self._select_option(page, 'select[id*="ano"]', str(year))
        # Seleccionar mes
        self._select_option(page, 'select[id*="mes"]', str(month))
        # Seleccionar día (0 = mes completo)
        self._select_option(page, 'select[id*="dia"]', str(day))

        # Seleccionar tipo de comprobante
        if tipo_comprobante != 'todos':
            self._select_option(page, 'select[id*="cmbTipoComprobante"]', tipo_comprobante)

        # Cerrar mensajes previos
        page.evaluate("""() => {
            document.querySelectorAll('.ui-messages-close, [class*="close"], .rf-msg-close')
                .forEach(el => el.click());
        }""")

        # Hacer clic en Consultar
        self._click_consultar(page)

        # Esperar resultado
        search_result = self._wait_for_result(page, timeout_ms=60000)

        if search_result == 'no_results':
            return []
        if search_result != 'table':
            return []

        # Procesar tabla de resultados
        return self._process_table(page)

    def _select_option(self, page, selector, value):
        """Selecciona un valor en un <select> con reintentos."""
        locator = page.locator(selector).first
        for attempt in range(3):
            try:
                locator.wait_for(state='attached', timeout=5000)
                locator.select_option(value)
                page.wait_for_timeout(500)
                actual = locator.input_value()
                if actual == value:
                    return
            except Exception:
                pass
            page.wait_for_timeout(1000)

    def _click_consultar(self, page):
        """Hace clic en el botón Consultar."""
        # Intentar con executeRecaptcha si existe
        has_func = page.evaluate(
            "() => typeof window.executeRecaptcha === 'function'"
        )
        if has_func:
            page.evaluate("() => window.executeRecaptcha('consulta_cel_recibidos', 'SI')")
            page.wait_for_timeout(2000)
            return

        # IntentarVarious selectores de botón
        selectors = [
            'input[type="submit"][id*="btnConsultar"], input[type="submit"][id*="btnBuscar"]',
            'button[id*="btnConsultar"], button[id*="btnBuscar"], button[id*="Consultar"]',
            'a[id*="btnConsultar"], a[id*="btnBuscar"]',
            'input[type="submit"][value*="Consultar"]',
        ]
        for sel in selectors:
            btn = page.locator(sel).first
            if btn.is_visible():
                btn.scroll_into_view_if_needed()
                page.wait_for_timeout(300)
                btn.click(force=True)
                page.wait_for_timeout(2000)
                return

        # Fallback: PrimeFaces
        page.evaluate("""() => {
            const prime = window.PrimeFaces;
            if (prime && prime.ab) {
                prime.ab({source: 'frmPrincipal:btnBuscar'});
            }
        }""")
        page.wait_for_timeout(2000)

    def _wait_for_result(self, page, timeout_ms=60000):
        """Espera a que aparezcan resultados de búsqueda."""
        page.wait_for_timeout(3000)
        start = time.time()
        elapsed_ms = 0

        while elapsed_ms < timeout_ms:
            try:
                result = page.evaluate("""() => {
                    const text = document.body ? document.body.innerText : '';
                    const hasTable = document.querySelector(
                        '#frmPrincipal\\\\:tablaCompRecibidos tr, [id*="tablaCompRecibidos"] tr'
                    ) !== null;
                    if (hasTable && text.match(/\\d{49}/)) return 'table';
                    const noResults = (
                        text.includes('No se encontraron') ||
                        text.includes('No existen') ||
                        text.includes('No se encontraron registros')
                    ) && !text.match(/\\d{49}/);
                    if (noResults) return 'no_results';
                    const loading = document.querySelector(
                        '.rf-msg-wait, .ui-loading, .ajax-loader, [class*="loading"]'
                    ) !== null;
                    if (loading) return null;
                    return null;
                }""")

                if result:
                    return result
            except Exception:
                pass
            page.wait_for_timeout(500)
            elapsed_ms = (time.time() - start) * 1000

        return 'timeout'

    def _process_table(self, page):
        """
        Extrae datos de la tabla de resultados.
        Retorna lista de dicts con info de cada comprobante.
        """
        # Detectar columnas
        col_idx = self._detect_columns(page)
        if not col_idx:
            return []

        # Extraer todas las filas
        rows = page.evaluate("""(colIdx) => {
            const allRows = Array.from(document.querySelectorAll(
                '#frmPrincipal\\\\:tablaCompRecibidos tr, [id*="tablaCompRecibidos"] tr'
            ));
            return allRows
                .filter(tr => tr.innerText.match(/\\d{49}/))
                .map((tr, idx) => {
                    const cells = tr.querySelectorAll('td');
                    const textos = Array.from(cells).map(c => c.innerText.trim());
                    return {index: idx, textos: textos};
                });
        }""", col_idx)

        results = []
        tipo_map = {
            'FACTURA': '01',
            'LIQUIDACIÓN': '03', 'LIQUIDACION': '03',
            'NOTA DE CRÉDITO': '04', 'NOTA DE CREDITO': '04',
            'NOTA DE DÉBITO': '05', 'NOTA DE DEBITO': '05',
            'COMPROBANTE DE RETENCIÓN': '07', 'COMPROBANTE DE RETENCION': '07',
        }

        for row in rows:
            textos = row['textos']

            # Extraer clave de acceso
            raw_clave = textos[col_idx['clave']] if col_idx['clave'] < len(textos) else ''
            import re
            clave_match = re.search(r'\d{49}', raw_clave)
            if not clave_match:
                continue
            clave_acceso = clave_match.group(0)

            # Extraer tipo
            raw_tipo = textos[col_idx['tipo']].upper() if col_idx['tipo'] < len(textos) else ''
            tipo_code = '01'
            for k, v in tipo_map.items():
                if k in raw_tipo:
                    tipo_code = v
                    break

            # Extraer emisor
            emisor = textos[col_idx['emisor']] if col_idx['emisor'] < len(textos) else ''

            results.append({
                'clave': clave_acceso,
                'tipo': tipo_code,
                'emisor': emisor,
                'row_index': row['index'],
            })

        return results

    def _detect_columns(self, page):
        """Detecta los índices de columnas en la tabla de resultados."""
        return page.evaluate("""() => {
            const selectors = [
                '#frmPrincipal\\\\:tablaCompRecibidos thead th',
                '#frmPrincipal\\\\:tablaCompRecibidos tr.rf-dt-shdr th',
                '#frmPrincipal\\\\:tablaCompRecibidos .rf-dt-shdr th',
            ];
            for (const sel of selectors) {
                const ths = Array.from(document.querySelectorAll(sel));
                if (ths.length < 3) continue;
                const texts = ths.map(th => th.innerText.trim().toUpperCase());
                const hasRuc = texts.some(t => t.includes('RUC'));
                const hasClave = texts.some(t => t.includes('CLAVE') || t.includes('ACCESO'));
                if (!hasRuc && !hasClave) continue;

                return {
                    tipo: texts.findIndex(t => t.includes('TIPO') || t.includes('COMPROBANTE')),
                    rucEmisor: texts.findIndex(t => t.includes('RUC')),
                    emisor: texts.findIndex(t => t.includes('RAZON') || t.includes('NOMBRE')),
                    clave: texts.findIndex(t => t.includes('CLAVE') || t.includes('ACCESO')),
                };
            }
            // Fallback
            return {tipo: 1, rucEmisor: 2, emisor: 3, clave: 4};
        }""")
