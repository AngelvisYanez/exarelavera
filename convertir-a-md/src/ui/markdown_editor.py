from pathlib import Path

import streamlit as st

from src.services.markdown_service import MarkdownService


def render_editor(service: MarkdownService, current_file_name: str) -> None:
    if st.session_state.get("md_content") is None:
        return

    file_stem = Path(current_file_name).stem

    st.markdown(f"""
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.5rem;">
        <h3 style="margin:0; font-size:1.15rem;">📝 Vista Previa: {file_stem}.md</h3>
    </div>
    """, unsafe_allow_html=True)

    tab_preview, tab_edit = st.tabs(["👁️ Vista previa", "✏️ Editor"])

    with tab_preview:
        st.markdown(st.session_state.md_content)

    with tab_edit:
        edited_text = st.text_area(
            "Editor de Markdown",
            value=st.session_state.md_content,
            height=450,
            label_visibility="collapsed",
        )
        st.session_state.md_content = edited_text

    col_dl, col_status = st.columns([1, 2])
    with col_dl:
        st.download_button(
            label="📥 Descargar archivo .md",
            data=st.session_state.md_content,
            file_name=f"{file_stem}.md",
            mime="text/markdown",
            type="primary",
            use_container_width=True,
        )
    with col_status:
        word_count = len(st.session_state.md_content.split())
        char_count = len(st.session_state.md_content)
        st.markdown(f"""
        <div style="display:flex; gap:1.5rem; align-items:center; height:100%; padding-left:1rem;">
            <span style="color:rgba(255,255,255,0.5); font-size:0.85rem;">📊 {word_count} palabras</span>
            <span style="color:rgba(255,255,255,0.5); font-size:0.85rem;">📏 {char_count} caracteres</span>
        </div>
        """, unsafe_allow_html=True)

    render_chat_editor(service)


def render_chat_editor(service: MarkdownService) -> None:
    st.divider()

    with st.expander("🤖 Asistente de Edición con IA", expanded=False):
        st.markdown("""
        <p style="color:rgba(255,255,255,0.7); font-size:0.9rem; margin-bottom:0.75rem;">
            ¿Necesitas cambios en el documento? Pedile al asistente que lo haga por vos.
            Por ejemplo: <em>"Traducí al inglés"</em>, <em>"Hacé un resumen"</em>,
            <em>"Mejorá el formato de tablas"</em>, <em>"Eliminá la primera sección"</em>.
        </p>
        """, unsafe_allow_html=True)

        if st.session_state.get("chat_history"):
            st.markdown("##### Historial")
            for msg in st.session_state.chat_history:
                with st.chat_message(msg["role"]):
                    st.markdown(msg["content"])

        user_prompt = st.chat_input("Escribí los ajustes o cambios que deseas aplicar...")

        if not user_prompt:
            return

        api_key = st.session_state.get("api_key")
        if not api_key:
            st.error("🔑 Necesitás configurar la API Key en el menú lateral para usar el asistente.")
            return

        with st.chat_message("user"):
            st.markdown(user_prompt)

        st.session_state.chat_history.append({"role": "user", "content": user_prompt})

        with st.chat_message("assistant"):
            with st.spinner("🤔 Aplicando cambios al documento..."):
                try:
                    openai_client = service.openai_client
                    if getattr(openai_client.api_key, "strip", lambda: "")() != api_key:
                        from src.services.markdown_service import build_openai_client
                        openai_client = build_openai_client(api_key)

                    editor = MarkdownService(openai_client=openai_client)
                    new_content = editor.edit(st.session_state.md_content, user_prompt)
                    st.session_state.md_content = new_content

                    assistant_reply = (
                        f"✅ **Cambios aplicados:** *{user_prompt}*\n\n"
                        "El documento fue actualizado. Revisá la vista previa arriba."
                    )
                    st.markdown(assistant_reply)
                    st.session_state.chat_history.append(
                        {"role": "assistant", "content": assistant_reply}
                    )
                    st.rerun()
                except Exception as e:
                    error_msg = f"❌ Error al contactar al asistente: {str(e)}"
                    st.error(error_msg)
                    st.session_state.chat_history.append(
                        {"role": "assistant", "content": error_msg}
                    )
