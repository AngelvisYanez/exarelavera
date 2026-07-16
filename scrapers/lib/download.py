"""Descarga de archivos XML y PDF del portal SRI."""
import os
import time


class Sridownload:
    """Descarga comprobantes XML y RIDE/PDF del portal SRI."""

    def __init__(self, browser, output_dir):
        self.browser = browser
        self.page = browser.page
        self.output_dir = output_dir

        # Crear subdirectorios
        self.xml_dir = os.path.join(output_dir, 'xml')
        self.pdf_dir = os.path.join(output_dir, 'pdf')
        os.makedirs(self.xml_dir, exist_ok=True)
        os.makedirs(self.pdf_dir, exist_ok=True)

    def download_xml(self, comp):
        """
        Descarga el XML de un comprobante.
        Retorna la ruta del archivo o None si falló.
        """
        clave = comp['clave']
        target_path = os.path.join(self.xml_dir, f'{clave}.xml')

        # Si ya existe, no descargar de nuevo
        if os.path.exists(target_path):
            return target_path

        # Buscar botón de descarga XML en la fila
        col = self._find_column(comp['row_index'], 'xml')
        if col is None:
            return None

        return self._download_from_cell(comp['row_index'], col, target_path, 'XML', clave)

    def download_pdf(self, comp):
        """
        Descarga el PDF/RIDE de un comprobante.
        Retorna la ruta del archivo o None si falló.
        """
        clave = comp['clave']
        target_path = os.path.join(self.pdf_dir, f'{clave}.pdf')

        if os.path.exists(target_path):
            return target_path

        col = self._find_column(comp['row_index'], 'pdf')
        if col is None:
            return None

        return self._download_from_cell(comp['row_index'], col, target_path, 'PDF', clave)

    def _find_column(self, row_index, file_type):
        """
        Encuentra la columna que contiene el botón de descarga
        para XML o PDF en una fila específica.
        """
        page = self.page
        return page.evaluate("""(params) => {
            const {rowIndex, fileType} = params;
            const rows = Array.from(document.querySelectorAll(
                '#frmPrincipal\\\\:tablaCompRecibidos tr, [id*="tablaCompRecibidos"] tr'
            )).filter(tr => tr.innerText.match(/\\d{49}/));

            if (rowIndex >= rows.length) return null;
            const cells = rows[rowIndex].querySelectorAll('td');

            for (let col = 0; col < cells.length; col++) {
                const cell = cells[col];
                const els = cell.querySelectorAll('a, input[type="image"], button');
                for (const el of els) {
                    const info = (
                        (el.getAttribute('src') || '') +
                        (el.getAttribute('id') || '') +
                        (el.getAttribute('title') || '') +
                        (el.getAttribute('onclick') || '') +
                        (el.textContent || '') +
                        (el.href || '')
                    ).toLowerCase();

                    if (fileType === 'xml' && (info.includes('xml') || info.includes('comprobante'))) {
                        return col;
                    }
                    if (fileType === 'pdf' && (info.includes('pdf') || info.includes('ride'))) {
                        return col;
                    }
                }
            }
            return null;
        }""", {'rowIndex': row_index, 'fileType': file_type})

    def _download_from_cell(self, row_index, col, target_path, label, clave):
        """
        Descarga un archivo haciendo clic en una celda de la tabla.
        Maneja descarga directa, respuesta HTTP y apertura de nueva pestaña.
        """
        page = self.page
        timeout = 25000

        for attempt in range(2):
            try:
                # Preparar listeners de descarga
                download_p = page.expect_download(timeout=timeout)

                # Hacer clic en el elemento de la celda
                page.evaluate("""(params) => {
                    const {rowIndex, col} = params;
                    const rows = Array.from(document.querySelectorAll(
                        '#frmPrincipal\\\\:tablaCompRecibidos tr, [id*="tablaCompRecibidos"] tr'
                    )).filter(tr => tr.innerText.match(/\\d{49}/));
                    if (rowIndex >= rows.length) return;
                    const cells = rows[rowIndex].querySelectorAll('td');
                    if (col >= cells.length) return;
                    const clickable = cells[col].querySelector('a, input[type="image"], button');
                    if (clickable) clickable.click();
                }""", {'rowIndex': row_index, 'col': col})

                # Esperar el evento de descarga
                try:
                    download = download_p.value
                    download.save_as(target_path)
                    print(f"[Download] {label} descargado: {clave}")
                    return target_path
                except Exception:
                    pass

                # Fallback: intentar leer de nueva pestaña
                try:
                    new_page = page.context.wait_for_event('page', timeout=5000)
                    new_page.wait_for_load_state('domcontentloaded', timeout=10000)
                    new_page.wait_for_timeout(2000)

                    # Intentar fetch del contenido
                    content = new_page.evaluate("""async () => {
                        try {
                            const r = await fetch(window.location.href);
                            const buf = await r.arrayBuffer();
                            return Array.from(new Uint8Array(buf));
                        } catch { return null; }
                    }""")

                    if content and len(content) > 100:
                        with open(target_path, 'wb') as f:
                            f.write(bytes(content))
                        print(f"[Download] {label} descargado (nueva pestaña): {clave}")
                        new_page.close()
                        return target_path

                    new_page.close()
                except Exception:
                    pass

            except Exception:
                pass

            page.wait_for_timeout(2000)

        print(f"[Download] {label} falló: {clave}")
        return None
