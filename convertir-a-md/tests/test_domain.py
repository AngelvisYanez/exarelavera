import pytest

from src.domain.convert import ConversionError, ConversionInput, ConversionResult, MissingApiKeyError, UnsupportedFileTypeError
from src.domain.converters import Converter
from src.domain.providers import CreateChatClientError, LLMProvider


class TestConversionExceptions:
    def test_conversion_error(self):
        err = ConversionError("error")
        assert isinstance(err, Exception)
        assert str(err) == "error"

    def test_missing_api_key_error(self):
        err = MissingApiKeyError("no key")
        assert isinstance(err, ConversionError)
        assert str(err) == "no key"

    def test_unsupported_file_type_error(self):
        err = UnsupportedFileTypeError("bad type")
        assert isinstance(err, ConversionError)
        assert str(err) == "bad type"


class TestConversionInput:
    def test_path_stored(self):
        inp = ConversionInput(path="/tmp/doc.pdf")
        assert inp.path == "/tmp/doc.pdf"

    def test_immutable(self):
        inp = ConversionInput(path="/tmp/doc.pdf")
        with pytest.raises(AttributeError):
            inp.path = "/other"


class TestConversionResult:
    def test_markdown_stored(self):
        res = ConversionResult(markdown="# Hello")
        assert res.markdown == "# Hello"

    def test_immutable(self):
        res = ConversionResult(markdown="# Hello")
        with pytest.raises(AttributeError):
            res.markdown = "# Changed"


class TestConverterProtocol:
    def test_protocol(self):
        assert hasattr(Converter, "convert")

    def test_concrete_implementation(self):
        class MyConverter:
            def convert(self, input: ConversionInput) -> ConversionResult:
                return ConversionResult(markdown=f"# {input.path}")

        c = MyConverter()
        result = c.convert(ConversionInput(path="test.pdf"))
        assert result.markdown == "# test.pdf"


class TestLLMProvider:
    def test_abstract_class(self):
        with pytest.raises(TypeError):
            LLMProvider()

    def test_concrete_implementation(self):
        class MyProvider(LLMProvider):
            name = "test"

            def create_chat_client(self):
                return "client"

        p = MyProvider()
        assert p.name == "test"
        assert p.create_chat_client() == "client"


class TestCreateChatClientError:
    def test_error(self):
        err = CreateChatClientError("client error")
        assert isinstance(err, Exception)
        assert str(err) == "client error"
