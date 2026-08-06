from __future__ import annotations

import os

from openai import OpenAI

from src.domain.providers import CreateChatClientError, LLMProvider


class OpenAIProvider(LLMProvider):
    name = "openai"

    def __init__(self, api_key: str) -> None:
        if not api_key:
            raise CreateChatClientError("Falta la API Key de OpenAI.")

        self.api_key = api_key
        self.base_url = None
        self.model = "gpt-4o-mini"

    def create_chat_client(self) -> OpenAI:
        return OpenAI(api_key=self.api_key, base_url=self.base_url or "https://api.openai.com/v1")


class GeminiCompatibleProvider(LLMProvider):
    name = "gemini_compatible"

    def __init__(
        self,
        api_key: str,
        base_url: str = "https://generativelanguage.googleapis.com/v1beta/openai/",
        model: str = "gemini-2.5-flash",
    ) -> None:
        if not api_key:
            raise CreateChatClientError("Falta la API Key de Gemini.")

        self.api_key = api_key
        self.base_url = base_url
        self.model = model

    def create_chat_client(self) -> OpenAI:
        return OpenAI(api_key=self.api_key, base_url=self.base_url)


class LocalOpenAICompatProvider(LLMProvider):
    name = "local_openai_compat"

    def __init__(
        self,
        api_key: str = "ollama",
        base_url: str = "http://localhost:11434/v1",
        model: str = "llama3",
    ) -> None:
        self.api_key = api_key
        self.base_url = base_url
        self.model = model

    def create_chat_client(self) -> OpenAI:
        return OpenAI(api_key=self.api_key, base_url=self.base_url)
