#!/bin/bash
# Setup del motor de scraping Python
# Ejecutar: bash scrapers/setup.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$SCRIPT_DIR/venv"

echo "[setup] Creando entorno virtual..."
python3 -m venv "$VENV_DIR"

echo "[setup] Instalando dependencias..."
"$VENV_DIR/bin/pip" install --upgrade pip
"$VENV_DIR/bin/pip" install -r "$SCRIPT_DIR/requirements.txt"

echo "[setup] Instalando Chromium para Playwright..."
"$VENV_DIR/bin/playwright" install chromium
"$VENV_DIR/bin/playwright" install-deps chromium 2>/dev/null || true

echo "[setup] Listo. Usar: $VENV_DIR/bin/python3 sri_scraper.py"
