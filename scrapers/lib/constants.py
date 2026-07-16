"""Constantes del scraper SRI."""

SRI_BASE = 'https://srienlinea.sri.gob.ec'
RECAPTCHA_SITE_KEY = '6LdukTQsAAAAAIcciM4GZq4ibeyplUhmWvlScuQE'

TIPO_COMPROBANTE = {
    '1': 'FACTURA',
    '2': 'LIQUIDACION DE COMPROBANTE DE VENTA',
    '3': 'NOTA DE CREDITO',
    '4': 'NOTA DE DEBITO',
    '6': 'COMPROBANTE DE RETENCION',
}

TIPO_MAP_REVERSE = {v: k for k, v in TIPO_COMPROBANTE.items()}

MESES = {
    1: 'Enero', 2: 'Febrero', 3: 'Marzo', 4: 'Abril',
    5: 'Mayo', 6: 'Junio', 7: 'Julio', 8: 'Agosto',
    9: 'Septiembre', 10: 'Octubre', 11: 'Noviembre', 12: 'Diciembre',
}

NAVIGATION_TIMEOUT = 60000
ACTION_TIMEOUT = 30000
