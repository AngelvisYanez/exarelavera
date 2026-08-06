from __future__ import annotations

from typing import Protocol, runtime_checkable


@runtime_checkable
class ChatClient(Protocol):
    """Interfaz mínima compatible con `openai.ChatCompletions`."""

    @property
    def api_key(self) -> str: ...

    class chat:
        @staticmethod
        def completions() -> Protocol: ...
