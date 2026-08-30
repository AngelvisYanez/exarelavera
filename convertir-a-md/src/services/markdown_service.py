import os
from pathlib import Path

from dotenv import load_dotenv
from openai import OpenAI

from src.domain.convert import MissingApiKeyError


def build_openai_client(api_key: str | None = None) -> OpenAI:
    """Crea un cliente OpenAI compatible con la API de Google Gemini."""
    if not api_key:
        load_dotenv()
        api_key = os.environ.get("GEMINI_API_KEY")

    if not api_key:
        raise MissingApiKeyError(
            "No se proporcionó una GEMINI_API_KEY. "
            "Configúrala en el .env o pásala como argumento."
        )

    return OpenAI(
        api_key=api_key,
        base_url="https://generativelanguage.googleapis.com/v1beta/openai/",
    )


class MarkdownService:
    """Servicio encargado de convertir archivos a Markdown y editar Markdown."""

    def __init__(self, openai_client: OpenAI) -> None:
        self.openai_client = openai_client

    def convert(
        self,
        file_path: str,
        model: str = "gemini-2.5-flash",
        stream_info=None,
    ) -> str:
        from markitdown import MarkItDown

        md = MarkItDown(llm_client=self.openai_client, llm_model=model)
        result = md.convert(file_path, stream_info=stream_info)
        return result.text_content

    def edit(
        self,
        current_markdown: str,
        instruction: str,
        model: str = "gemini-2.5-flash",
    ) -> str:
        system_prompt = (
            "Eres un asistente experto en edición de documentos Markdown.\n"
            "Tu tarea es aplicar los cambios solicitados por el usuario "
            "al documento Markdown actual.\n"
            "IMPORTANTE: Debes devolver ÚNICAMENTE el documento Markdown actualizado en tu respuesta. "
            "No incluyas saludos, explicaciones, ni etiquetas de bloques de código (```markdown) "
            "alrededor de toda tu respuesta. Simplemente devuelve el contenido puro."
        )

        response = self.openai_client.chat.completions.create(
            model=model,
            messages=[
                {"role": "system", "content": system_prompt},
                {
                    "role": "user",
                    "content": (
                        "Aquí está el documento actual:\n\n"
                        f"{current_markdown}\n\n"
                        f"Instrucción de cambio: {instruction}\n\n"
                        "Aplica los cambios y devuelve solo el nuevo Markdown."
                    ),
                },
            ],
            temperature=0.2,
        )

        new_content = (response.choices[0].message.content or "").strip()

        if new_content.startswith("```markdown"):
            new_content = new_content[len("```markdown"):].strip()
        if new_content.startswith("```"):
            new_content = new_content[len("```"):].strip()
        if new_content.endswith("```"):
            new_content = new_content[:-3].strip()

        return new_content
