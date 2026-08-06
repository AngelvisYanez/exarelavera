from unittest.mock import MagicMock, patch

import pytest

from src.services.markdown_service import MarkdownService, build_openai_client
from src.services.providers import (
    CreateChatClientError,
    GeminiCompatibleProvider,
    LocalOpenAICompatProvider,
    OpenAIProvider,
)


class TestProviders:
    def test_openai_provider(self):
        provider = OpenAIProvider(api_key="sk-test")
        client = provider.create_chat_client()
        assert client.api_key == "sk-test"
        assert "openai.com" in str(client.base_url)

    def test_openai_provider_missing_key(self):
        with pytest.raises(CreateChatClientError, match="Falta la API Key"):
            OpenAIProvider(api_key="")

    def test_gemini_provider(self):
        provider = GeminiCompatibleProvider(api_key="gemini-key", model="gemini-2.5-flash")
        client = provider.create_chat_client()
        assert client.api_key == "gemini-key"
        assert "googleapis" in str(client.base_url)

    def test_gemini_provider_missing_key(self):
        with pytest.raises(CreateChatClientError, match="Falta la API Key"):
            GeminiCompatibleProvider(api_key="")

    def test_local_provider(self):
        provider = LocalOpenAICompatProvider()
        client = provider.create_chat_client()
        assert client.api_key == "ollama"
        assert "localhost" in str(client.base_url)

    def test_local_provider_custom(self):
        provider = LocalOpenAICompatProvider(
            api_key="custom", base_url="http://my-server:8080/v1", model="mistral"
        )
        client = provider.create_chat_client()
        assert client.api_key == "custom"
        assert "my-server:8080" in str(client.base_url)


class TestBuildOpenaiClient:
    @patch.dict("os.environ", {}, clear=True)
    def test_no_key_raises(self):
        with pytest.raises(Exception, match="No se proporcionó"):
            build_openai_client(api_key=None)

    @patch.dict("os.environ", {"GEMINI_API_KEY": "env-key"})
    def test_from_env_var(self):
        client = build_openai_client(api_key=None)
        assert client.api_key == "env-key"

    def test_from_argument(self):
        client = build_openai_client(api_key="direct-key")
        assert client.api_key == "direct-key"


class TestMarkdownService:
    def test_convert_calls_markitdown(self):
        mock_client = MagicMock()
        service = MarkdownService(openai_client=mock_client)

        with patch("markitdown.MarkItDown") as MockMarkItDown:
            mock_md_instance = MagicMock()
            mock_result = MagicMock()
            mock_result.text_content = "# Converted Content"
            mock_md_instance.convert.return_value = mock_result
            MockMarkItDown.return_value = mock_md_instance

            result = service.convert("test.pdf", model="gemini-2.5-flash")

            MockMarkItDown.assert_called_once_with(
                llm_client=mock_client, llm_model="gemini-2.5-flash"
            )
            mock_md_instance.convert.assert_called_once_with("test.pdf")
            assert result == "# Converted Content"

    def test_edit_strips_code_fences(self):
        mock_client = MagicMock()
        mock_response = MagicMock()
        mock_choice = MagicMock()
        mock_choice.message.content = "```markdown\n# New Content\n```"
        mock_response.choices = [mock_choice]
        mock_client.chat.completions.create.return_value = mock_response

        service = MarkdownService(openai_client=mock_client)
        result = service.edit("# Old", "change it")

        assert result == "# New Content"

    def test_edit_plain_response(self):
        mock_client = MagicMock()
        mock_response = MagicMock()
        mock_choice = MagicMock()
        mock_choice.message.content = "# New Content\n\nUpdated."
        mock_response.choices = [mock_choice]
        mock_client.chat.completions.create.return_value = mock_response

        service = MarkdownService(openai_client=mock_client)
        result = service.edit("# Old", "change it")

        assert result == "# New Content\n\nUpdated."

    def test_edit_empty_response(self):
        mock_client = MagicMock()
        mock_response = MagicMock()
        mock_choice = MagicMock()
        mock_choice.message.content = ""
        mock_response.choices = [mock_choice]
        mock_client.chat.completions.create.return_value = mock_response

        service = MarkdownService(openai_client=mock_client)
        result = service.edit("# Old", "change it")

        assert result == ""

    def test_edit_system_prompt_includes_instructions(self):
        mock_client = MagicMock()
        mock_response = MagicMock()
        mock_choice = MagicMock()
        mock_choice.message.content = "# Result"
        mock_response.choices = [mock_choice]
        mock_client.chat.completions.create.return_value = mock_response

        service = MarkdownService(openai_client=mock_client)
        service.edit("# Old\n\ncontent", "add more")

        call_args = mock_client.chat.completions.create.call_args
        messages = call_args[1]["messages"]
        assert messages[0]["role"] == "system"
        assert "Markdown" in messages[0]["content"]
        assert messages[1]["role"] == "user"
        assert "# Old" in messages[1]["content"]
        assert "add more" in messages[1]["content"]
