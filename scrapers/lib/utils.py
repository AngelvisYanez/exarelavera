"""Utilidades generales del scraper SRI."""
import re
from datetime import datetime


def extract_ruc(text):
    """Extrae un RUC de 13 dígitos de un texto."""
    if not text:
        return None
    match = re.search(r'\b\d{13}\b', text)
    return match.group(0) if match else None


def extract_clave_acceso(text):
    """Extrae una clave de acceso de 49 dígitos."""
    if not text:
        return None
    match = re.search(r'\d{49}', text)
    return match.group(0) if match else None


def clean_razon_social(text):
    """Limpia la razón social de espacios y caracteres raros."""
    if not text:
        return None
    text = re.sub(r'\s+', ' ', text).strip()
    return text if text else None


def parse_sri_float(text):
    """Parsea un float del formato SRI (1.234,56 → 1234.56)."""
    if not text:
        return None
    text = text.strip().replace(' ', '')
    text = text.replace('.', '').replace(',', '.')
    try:
        return float(text)
    except ValueError:
        return None


def parse_sri_date(date_str):
    """Parsea fecha del SRI (dd/mm/yyyy → yyyy-mm-dd)."""
    if not date_str:
        return None
    match = re.search(r'(\d{2})/(\d{2})/(\d{4})', date_str)
    if match:
        return f'{match.group(3)}-{match.group(2)}-{match.group(1)}'
    return None


def extract_serie(clave_acceso):
    """Extrae la serie de la clave de acceso (posiciones 24-31)."""
    if not clave_acceso or len(clave_acceso) < 31:
        return None
    return clave_acceso[24:32]


def extract_secuencial(clave_acceso):
    """Extrae el secuencial de la clave de acceso (posiciones 32-41)."""
    if not clave_acceso or len(clave_acceso) < 41:
        return None
    return clave_acceso[32:41]


def generate_periods(fecha_desde, fecha_hasta):
    """
    Genera lista de períodos para buscar.
    Si el rango es un mes completo → 1 búsqueda por mes.
    Si no → 1 búsqueda por día.
    """
    start = datetime.strptime(fecha_desde, '%Y-%m-%d')
    end = datetime.strptime(fecha_hasta, '%Y-%m-%d')
    periods = []
    current = start

    while current <= end:
        # Verificar si es mes completo
        last_day_of_month = datetime(
            current.year,
            current.month + 1 if current.month < 12 else 1,
            1
        ).replace(day=1) - datetime.timedelta(days=1)

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
                'label': f"mes completo {MESES.get(current.month, '')} {current.year}",
            })
            # Saltar al siguiente mes
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
            current += datetime.timedelta(days=1)

    return periods
