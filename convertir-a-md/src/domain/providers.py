from __future__ import annotations

from abc import ABC, abstractmethod


class LLMProvider(ABC):
    """Interfaz de proveedor LLM."""

    name: str = ""

    @abstractmethod
    def create_chat_client(self):
        """Devuelve un cliente compatible con la API de chat usada por servicio."""


class CreateChatClientError(Exception):
    """Error al crear el cliente de chat."""


