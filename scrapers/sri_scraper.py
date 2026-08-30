#!/usr/bin/env python3
"""
sri_scraper.py - Entry point de descarga masiva SRI.
Lee params de un archivo JSON, escribe progreso a progress.json.
"""
import sys
import os
import json
import argparse
import traceback
import time
from datetime import datetime, timedelta

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from lib.browser import Sribrowser
from lib.login import Srilogin
from lib.search import Srisearch
from lib.download import Sridownload


class ProgressWriter:
    """Escribe progreso a un archivo JSON que PHP puede leer."""

    def __init__(self, output_dir):
        self.path = os.path.join(output_dir, 'progress.json')
        self.data = {
            'status': 'running',
            'progress_msg': 'Iniciando...',
            'total_found': 0,
            'xmls_downloaded': 0,
            'pdfs_downloaded': 0,
            'events': [],
        }
        self._save()

    def update(self, **kwargs):
        self.data.update(kwargs)
        event = {'time': time.time()}
        event.update(kwargs)
        self.data['events'].append(event)
        if len(self.data['events']) > 200:
            self.data['events'] = self.data['events'][-100:]
        self._save()

    def set_progress(self, msg):
        self.data['progress_msg'] = msg
        self.update(progress_msg=msg)

    def add_found(self):
        self.data['total_found'] = self.data.get('total_found', 0) + 1
        self._save()

    def add_xml(self):
        self.data['xmls_downloaded'] = self.data.get('xmls_downloaded', 0) + 1
        self._save()

    def add_pdf(self):
        self.data['pdfs_downloaded'] = self.data.get('pdfs_downloaded', 0) + 1
        self._save()

    def mark_completed(self):
        self.data['status'] = 'completed'
        self.data['completed_at'] = datetime.now().isoformat()
        self._save()

    def mark_error(self, msg):
        self.data['status'] = 'failed'
        self.data['error'] = msg
        self.data['completed_at'] = datetime.now().isoformat()
        self._save()

    def _save(self):
        tmp = self.path + '.tmp'
        with open(tmp, 'w', encoding='utf-8') as f:
            json.dump(self.data, f, ensure_ascii=False, indent=2)
        if os.path.exists(self.path):
            os.replace(tmp, self.path)
        else:
            os.rename(tmp, self.path)


def generate_periods(fecha_desde, fecha_hasta):
    start = datetime.strptime(fecha_desde, '%Y-%m-%d')
    end = datetime.strptime(fecha_hasta, '%Y-%m-%d')
    periods = []
    current = start

    meses = {
        1: 'Enero', 2: 'Febrero', 3: 'Marzo', 4: 'Abril',
        5: 'Mayo', 6: 'Junio', 7: 'Julio', 8: 'Agosto',
        9: 'Septiembre', 10: 'Octubre', 11: 'Noviembre', 12: 'Diciembre',
    }

    while current <= end:
        last_day_of_month = (
            current.replace(day=28) + timedelta(days=4)
        ).replace(day=1) - timedelta(days=1)

        is_whole_month = (
            current.day == 1
            and last_day_of_month.day >= end.day
            and current.month == end.month
            and current.year == end.year
        )

        if is_whole_month:
            periods.append({
                'year': current.year,
                'month': current.month,
                'day': 0,
                'label': f"mes completo {meses.get(current.month, '')} {current.year}",
            })
            if current.month == 12:
                current = current.replace(year=current.year + 1, month=1, day=1)
            else:
                current = current.replace(month=current.month + 1, day=1)
        else:
            periods.append({
                'year': current.year,
                'month': current.month,
                'day': current.day,
                'label': current.strftime('%d/%m/%Y'),
            })
            current += timedelta(days=1)

    return periods


def main():
    parser = argparse.ArgumentParser(description='SRI Mass Download Scraper')
    parser.add_argument('--params-file', required=True, help='Archivo JSON con parámetros')
    parser.add_argument('--output-dir', required=True, help='Directorio de salida')
    args = parser.parse_args()

    with open(args.params_file, 'r', encoding='utf-8') as f:
        params = json.load(f)

    output_dir = args.output_dir
    os.makedirs(output_dir, exist_ok=True)

    progress = ProgressWriter(output_dir)
    browser = None

    try:
        progress.set_progress("Iniciando navegador Playwright...")
        browser = Sribrowser(headless=True)
        browser.launch()

        progress.set_progress("Iniciando sesion en SRI...")
        login = Srilogin(browser)
        success = login.do_login(params['ruc'], params['clave'])
        if not success:
            progress.mark_error("No se pudo iniciar sesion en el SRI")
            sys.exit(1)
        progress.set_progress("Sesion iniciada correctamente")

        search = Srisearch(browser)
        downloader = Sridownload(browser, output_dir)

        total_xmls = 0
        total_pdfs = 0
        total_found = 0
        tipo = params.get('tipo_comprobante', 'todos')

        periods = generate_periods(params['fecha_desde'], params['fecha_hasta'])

        for period in periods:
            progress.set_progress(
                f"Buscando tipo {tipo} para {period['label']}...")

            results = search.search_period(
                period['year'], period['month'], period['day'], tipo)

            progress.set_progress(
                f"Encontrados {len(results)} comprobantes en {period['label']}")

            for comp in results:
                total_found += 1
                progress.data['total_found'] = total_found
                progress.update(
                    progress_msg=f"Descargando {comp['clave']}...",
                    total_found=total_found)

                xml_path = downloader.download_xml(comp)
                if xml_path:
                    total_xmls += 1
                    progress.data['xmls_downloaded'] = total_xmls

                pdf_path = downloader.download_pdf(comp)
                if pdf_path:
                    total_pdfs += 1
                    progress.data['pdfs_downloaded'] = total_pdfs

                progress._save()

        progress.data['total_found'] = total_found
        progress.data['xmls_downloaded'] = total_xmls
        progress.data['pdfs_downloaded'] = total_pdfs
        progress.mark_completed()

    except Exception as e:
        progress.mark_error(str(e))
        print(f"ERROR: {e}", file=sys.stderr)
        traceback.print_exc()
        sys.exit(1)
    finally:
        if browser:
            browser.close()


if __name__ == '__main__':
    main()
