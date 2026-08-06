import os
import sys
import time
from pathlib import Path

from dotenv import load_dotenv
from markitdown import MarkItDown
from openai import OpenAI


def get_markitdown_client(api_key=None):
    """Inicializa y devuelve el cliente de MarkItDown configurado."""
    if not api_key:
        load_dotenv()
        api_key = os.environ.get("GEMINI_API_KEY")

    if not api_key:
        raise ValueError(
            "No se proporcionó una GEMINI_API_KEY. Configúrala en el .env o pásala como argumento."
        )

    # Usamos el cliente de OpenAI pero lo apuntamos a la API de Google Gemini.
    client = OpenAI(
        api_key=api_key,
        base_url="https://generativelanguage.googleapis.com/v1beta/openai/",
    )

    # Inicializamos la librería oficial de Microsoft MarkItDown con Gemini 2.5 Flash
    md = MarkItDown(llm_client=client, llm_model="gemini-2.5-flash")
    return md


def convert_to_markdown(file_path, api_key=None, max_retries=3):
    path = Path(file_path)
    if not path.exists():
        raise FileNotFoundError(f"El archivo {file_path} no existe.")

    print(f"Iniciando conversión de '{file_path}' usando MarkItDown + Gemini...")

    md = get_markitdown_client(api_key)

    retries = 0
    while True:
        try:
            result = md.convert(str(path))
            break
        except Exception as e:
            error_msg = str(e)
            # Retrying on 503 (Service Unavailable) or 429 (Too Many Requests)
            if ("503" in error_msg or "429" in error_msg) and retries < max_retries:
                retries += 1
                wait_time = 2**retries  # Exponential backoff: 2, 4, 8 seconds
                print(
                    f"Servidor ocupado (Error en la API). Reintentando ({retries}/{max_retries}) en {wait_time} segundos..."
                )
                time.sleep(wait_time)
            else:
                raise e

    output_path = path.with_suffix(".md")
    with open(output_path, "w", encoding="utf-8") as f:
        f.write(result.text_content)

    print(f"¡Éxito! Archivo convertido y guardado en: {output_path}")
    return output_path, result.text_content


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Uso: python convert.py <ruta_del_archivo>")
        print("Ejemplo: python convert.py documento.pdf")
        sys.exit(1)

    archivo = sys.argv[1]
    try:
        convert_to_markdown(archivo)
    except Exception as e:
        print(f"Ocurrió un error: {e}")
