import os
import tempfile
from pathlib import Path

import streamlit as st

from src.domain.convert import UnsupportedFileTypeError
from src.services.markdown_service import MarkdownService
from src.services.providers import GeminiCompatibleProvider, LocalOpenAICompatProvider, OpenAIProvider
from src.ui.markdown_editor import render_editor

st.set_page_config(
    page_title="Convertir a .MD",
    page_icon="📄",
    layout="wide",
    initial_sidebar_state="expanded",
)

PROVIDER_DEFAULTS = {
    "Gemini": {
        "key_hint": "GEMINI_API_KEY",
        "default_model": "gemini-2.5-flash",
        "base_url": "https://generativelanguage.googleapis.com/v1beta/openai/",
        "needs_key": True,
        "icon": "🔮",
    },
    "OpenAI": {
        "key_hint": "OPENAI_API_KEY",
        "default_model": "gpt-4o-mini",
        "base_url": "https://api.openai.com/v1",
        "needs_key": True,
        "icon": "⚡",
    },
    "Local (Ollama/OpenAI-compatible)": {
        "key_hint": None,
        "default_model": "llama3",
        "base_url": "http://localhost:11434/v1",
        "needs_key": False,
        "icon": "🖥️",
    },
}


def _load_env_key(name: str) -> str:
    return os.environ.get(name, "") or ""


def get_session_provider() -> str:
    return st.session_state.get("provider") or "Gemini"


def get_session_model() -> str:
    provider_cfg = PROVIDER_DEFAULTS.get(get_session_provider(), {})
    return st.session_state.get("model") or provider_cfg.get("default_model", "")


def get_session_base_url() -> str:
    provider_cfg = PROVIDER_DEFAULTS.get(get_session_provider(), {})
    return st.session_state.get("base_url") or provider_cfg.get("base_url", "")


def build_client_for_provider(provider_name: str, api_key: str):
    base_url = get_session_base_url()
    model = get_session_model()

    if provider_name == "Gemini":
        return GeminiCompatibleProvider(
            api_key=api_key,
            base_url=base_url,
            model=model,
        ).create_chat_client()
    if provider_name == "OpenAI":
        return OpenAIProvider(api_key=api_key).create_chat_client()
    if provider_name == "Local (Ollama/OpenAI-compatible)":
        return LocalOpenAICompatProvider(
            api_key=api_key or "ollama",
            base_url=base_url,
            model=model,
        ).create_chat_client()
    raise ValueError(f"Proveedor no soportado: {provider_name}")


defaults = {
    "md_content": None,
    "chat_history": [],
    "current_file": None,
    "provider": "Gemini",
    "model": PROVIDER_DEFAULTS["Gemini"]["default_model"],
    "base_url": PROVIDER_DEFAULTS["Gemini"]["base_url"],
    "api_key": "",
}
for key, value in defaults.items():
    if key not in st.session_state:
        st.session_state[key] = value

with st.sidebar:
    st.markdown("""
    <style>
        [data-testid="stSidebar"] {
            background: linear-gradient(180deg, #0f0c29 0%, #1a1a2e 50%, #16213e 100%);
        }
        [data-testid="stSidebar"] .sidebar-header {
            text-align: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }
        [data-testid="stSidebar"] .sidebar-header h1 {
            font-size: 1.3rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }
        [data-testid="stSidebar"] .sidebar-header p {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.5);
            margin: 0.25rem 0 0 0;
        }
        div[data-testid="stSidebarNav"] {display: none;}
        section[data-testid="stSidebar"] > div:first-child {padding-top: 0;}
    </style>
    <div class="sidebar-header">
        <h1>⚙️ Configuración</h1>
        <p>Proveedor & modelo LLM</p>
    </div>
    """, unsafe_allow_html=True)

    provider = st.selectbox(
        "Proveedor",
        list(PROVIDER_DEFAULTS.keys()),
        index=list(PROVIDER_DEFAULTS.keys()).index(get_session_provider()),
        label_visibility="collapsed",
        format_func=lambda p: f"{PROVIDER_DEFAULTS[p]['icon']}  {p}",
    )

    if provider != get_session_provider():
        provider_cfg = PROVIDER_DEFAULTS.get(provider, {})
        st.session_state.model = provider_cfg.get("default_model", "")
        st.session_state.base_url = provider_cfg.get("base_url", "")

    st.session_state.provider = provider

    with st.container(border=True):
        st.caption("Modelo")
        model_default = PROVIDER_DEFAULTS[provider]["default_model"]
        st.session_state.model = st.text_input(
            "Modelo",
            value=get_session_model() or model_default,
            label_visibility="collapsed",
        )

        if provider == "Local (Ollama/OpenAI-compatible)":
            st.caption("Base URL")
            st.session_state.base_url = st.text_input(
                "Base URL",
                value=get_session_base_url(),
                label_visibility="collapsed",
            )

        key_name = PROVIDER_DEFAULTS[provider]["key_hint"]
        env_key = _load_env_key(key_name) if key_name else ""
        api_key_value = st.session_state.api_key or env_key
        st.caption("API Key")
        st.session_state.api_key = st.text_input(
            "API Key",
            value=api_key_value,
            type="password",
            label_visibility="collapsed",
            help=f"Ingresá tu API Key de {provider}. También se puede definir vía env var {key_name or 'N/A'}.",
        )

    st.markdown("---")
    st.markdown(
        """
        <div style="text-align:center; color: rgba(255,255,255,0.4); font-size:0.7rem;">
            Convertir a .MD v1.0<br>
            Powered by MarkItDown + LLM
        </div>
        """,
        unsafe_allow_html=True,
    )

st.markdown("""
<style>
    /* ---- GLOBAL ---- */
    .main-header {
        text-align: center;
        padding: 0.5rem 0 1.5rem 0;
    }
    .main-header h1 {
        font-size: 2.2rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin: 0;
    }
    .main-header p {
        color: rgba(255,255,255,0.6);
        margin: 0.25rem 0 0 0;
        font-size: 0.95rem;
    }
    .step-card {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.2s ease;
    }
    .step-card:hover {
        background: rgba(255,255,255,0.08);
        border-color: rgba(102,126,234,0.3);
    }
    .step-number {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        flex-shrink: 0;
    }
    .step-text {
        color: rgba(255,255,255,0.85);
        font-size: 0.9rem;
    }
    .file-drop-zone {
        border: 2px dashed rgba(102,126,234,0.4);
        border-radius: 16px;
        padding: 2rem 1rem;
        text-align: center;
        transition: all 0.3s ease;
        background: rgba(102,126,234,0.05);
    }
    .file-drop-zone:hover {
        border-color: #667eea;
        background: rgba(102,126,234,0.1);
    }
    .file-info {
        background: rgba(255,255,255,0.04);
        border-radius: 10px;
        padding: 0.75rem 1rem;
        margin: 0.75rem 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .file-info .name {
        font-weight: 600;
        color: rgba(255,255,255,0.9);
    }
    .file-info .size {
        color: rgba(255,255,255,0.5);
        font-size: 0.85rem;
    }
    .stButton > button[kind="primary"] {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .stButton > button[kind="primary"]:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 20px rgba(102,126,234,0.4);
    }
    div[data-testid="stExpander"] {
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 10px;
    }
    .stTextArea textarea {
        font-family: 'SF Mono', 'Fira Code', monospace;
        font-size: 0.85rem;
    }
</style>
""", unsafe_allow_html=True)

st.markdown("""
<div class="main-header">
    <h1>📄 Convertir Documentos a Markdown</h1>
    <p>Convertí PDFs, imágenes y documentos Office a Markdown con inteligencia artificial</p>
</div>
""", unsafe_allow_html=True)

steps_col1, steps_col2, steps_col3 = st.columns(3)
with steps_col1:
    st.markdown("""
    <div class="step-card">
        <div class="step-number">1</div>
        <div class="step-text">Elegí proveedor y modelo en la barra lateral</div>
    </div>
    """, unsafe_allow_html=True)
with steps_col2:
    st.markdown("""
    <div class="step-card">
        <div class="step-number">2</div>
        <div class="step-text">Subí un PDF, imagen o documento Office</div>
    </div>
    """, unsafe_allow_html=True)
with steps_col3:
    st.markdown("""
    <div class="step-card">
        <div class="step-number">3</div>
        <div class="step-text">Descargá el .md y editá el contenido con el asistente</div>
    </div>
    """, unsafe_allow_html=True)

st.markdown("<br>", unsafe_allow_html=True)

col_left, col_right = st.columns([1, 2], gap="large")

with col_left:
    st.markdown('<div class="file-drop-zone">', unsafe_allow_html=True)
    uploaded_file = st.file_uploader(
        "Archivo",
        type=["pdf", "png", "jpg", "jpeg", "docx", "xlsx", "pptx", "html", "csv"],
        label_visibility="collapsed",
    )
    st.markdown("</div>", unsafe_allow_html=True)

    if uploaded_file is not None:
        if st.session_state.current_file != uploaded_file.name:
            st.session_state.current_file = uploaded_file.name
            st.session_state.md_content = None
            st.session_state.chat_history = []

        ext = Path(uploaded_file.name).suffix.lower()
        ext_icons = {
            ".pdf": "📕", ".png": "🖼️", ".jpg": "🖼️", ".jpeg": "🖼️",
            ".docx": "📘", ".xlsx": "📗", ".pptx": "📙", ".html": "🌐", ".csv": "📊",
        }
        icon = ext_icons.get(ext, "📄")

        st.markdown(f"""
        <div class="file-info">
            <div><span class="name">{icon} {uploaded_file.name[:50]}</span></div>
            <div><span class="size">{uploaded_file.size / 1024:.1f} KB</span></div>
        </div>
        """, unsafe_allow_html=True)

        if st.button(
            "🔄 Convertir a Markdown",
            type="primary",
            use_container_width=True,
        ):
            if PROVIDER_DEFAULTS[provider]["needs_key"] and not st.session_state.api_key:
                st.error("❌ Completá la API Key del proveedor en la barra lateral.")
            else:
                progress_bar = st.progress(0, text="Iniciando conversión...")
                try:
                    with tempfile.TemporaryDirectory() as temp_dir:
                        progress_bar.progress(10, text="Guardando archivo temporal...")
                        temp_path = Path(temp_dir) / uploaded_file.name
                        temp_path.write_bytes(uploaded_file.getbuffer())

                        progress_bar.progress(30, text="Conectando con el proveedor LLM...")
                        client = build_client_for_provider(provider, st.session_state.api_key)
                        service = MarkdownService(openai_client=client)

                        progress_bar.progress(50, text="Procesando con MarkItDown + LLM...")
                        md_text = service.convert(str(temp_path), model=get_session_model())

                        progress_bar.progress(90, text="Finalizando...")
                    st.session_state.md_content = md_text
                    st.session_state.chat_history = []
                    progress_bar.progress(100, text="¡Conversión completada!")
                    st.success("✅ Conversión lista — podés editar el resultado en el panel derecho.")
                except UnsupportedFileTypeError:
                    st.error("❌ Tipo de archivo no soportado por MarkItDown.")
                except Exception as e:
                    st.error(f"❌ Error al convertir: {str(e)}")
                finally:
                    progress_bar.empty()

with col_right:
    if st.session_state.current_file:
        render_editor(
            MarkdownService(openai_client=build_client_for_provider(provider, st.session_state.api_key)),
            st.session_state.current_file,
        )
    else:
        st.markdown("""
        <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; 
                    height:400px; color:rgba(255,255,255,0.3); text-align:center;">
            <div style="font-size:4rem; margin-bottom:1rem;">📂</div>
            <div style="font-size:1.1rem; font-weight:500;">Subí un archivo para comenzar</div>
            <div style="font-size:0.85rem; margin-top:0.5rem;">
                Formatos soportados: PDF, PNG, JPG, DOCX, XLSX, PPTX, HTML, CSV
            </div>
        </div>
        """, unsafe_allow_html=True)
