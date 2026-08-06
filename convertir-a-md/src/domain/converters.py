from __future__ import annotations

from typing import Protocol

from src.domain.convert import ConversionInput, ConversionResult


class Converter(Protocol):
    def convert(self, input: ConversionInput) -> ConversionResult: ...
