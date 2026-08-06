from __future__ import annotations

from dataclasses import dataclass


class ConversionError(Exception):
    """Error genérico de conversión."""


class MissingApiKeyError(ConversionError):
    """Falta la API key de Gemini."""


class UnsupportedFileTypeError(ConversionError):
    """Tipo de archivo no soportado."""


@dataclass(frozen=True)
class ConversionInput:
    """Input estandarizado para convertir un archivo."""

    path: str


@dataclass(frozen=True)
class ConversionResult:
    """Resultado de la conversión."""

    markdown: str
