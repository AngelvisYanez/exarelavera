import pytest

from app import PROVIDER_DEFAULTS, build_client_for_provider, get_session_base_url, get_session_model, get_session_provider

# Note: streamlit uses session_state which requires a running Streamlit context.
# We test the pure functions that don't depend on st.session_state directly.


class TestProviderDefaults:
    def test_all_providers_have_required_keys(self):
        required = {"key_hint", "default_model", "base_url", "needs_key"}
        for name, cfg in PROVIDER_DEFAULTS.items():
            assert required.issubset(cfg.keys()), f"{name} missing keys"
            assert isinstance(cfg["needs_key"], bool)

    def test_gemini_defaults(self):
        cfg = PROVIDER_DEFAULTS["Gemini"]
        assert cfg["default_model"] == "gemini-2.5-flash"
        assert cfg["needs_key"] is True
        assert "googleapis" in cfg["base_url"]

    def test_openai_defaults(self):
        cfg = PROVIDER_DEFAULTS["OpenAI"]
        assert cfg["default_model"] == "gpt-4o-mini"
        assert cfg["needs_key"] is True

    def test_local_defaults(self):
        cfg = PROVIDER_DEFAULTS["Local (Ollama/OpenAI-compatible)"]
        assert cfg["default_model"] == "llama3"
        assert cfg["needs_key"] is False
        assert "localhost" in cfg["base_url"]

    def test_local_key_hint_is_none(self):
        cfg = PROVIDER_DEFAULTS["Local (Ollama/OpenAI-compatible)"]
        assert cfg["key_hint"] is None


class TestBuildClientForProvider:
    def test_gemini_client(self):
        client = build_client_for_provider("Gemini", "test-key")
        assert client.api_key == "test-key"

    def test_openai_client(self):
        client = build_client_for_provider("OpenAI", "sk-test")
        assert client.api_key == "sk-test"

    def test_local_client(self):
        client = build_client_for_provider("Local (Ollama/OpenAI-compatible)", "")
        assert client.api_key == "ollama"

    def test_invalid_provider(self):
        with pytest.raises(ValueError, match="Proveedor no soportado"):
            build_client_for_provider("Invalid", "key")
