import os

os.environ["KMP_DUPLICATE_LIB_OK"] = "TRUE"
os.environ["OMP_NUM_THREADS"] = "1"
os.environ["ORT_DISABLE_DUPLICATE_INIT"] = "1"

import tempfile
import threading
from pathlib import Path

import customtkinter as ctk
from tkinter import filedialog, messagebox

from src.domain.convert import UnsupportedFileTypeError
from src.services.markdown_service import MarkdownService
from src.services.providers import GeminiCompatibleProvider, LocalOpenAICompatProvider, OpenAIProvider

ctk.set_appearance_mode("dark")
ctk.set_default_color_theme("blue")

PROVIDER_DEFAULTS = {
    "Gemini": {
        "key_hint": "GEMINI_API_KEY",
        "default_model": "gemini-2.5-flash",
        "base_url": "https://generativelanguage.googleapis.com/v1beta/openai/",
        "needs_key": True,
    },
    "OpenAI": {
        "key_hint": "OPENAI_API_KEY",
        "default_model": "gpt-4o-mini",
        "base_url": "https://api.openai.com/v1",
        "needs_key": True,
    },
    "Local (Ollama)": {
        "key_hint": None,
        "default_model": "llama3",
        "base_url": "http://localhost:11434/v1",
        "needs_key": False,
    },
}


def _load_env_key(name):
    return os.environ.get(name, "") or ""


class ConvertirApp(ctk.CTk):
    def __init__(self):
        super().__init__()

        self.title("Convertir a .MD")
        self.geometry("1100x700")
        self.minsize(900, 600)

        self.selected_file = None
        self.md_content = ""
        self.chat_history = []
        self.current_provider = "Gemini"
        self._service = None

        self._build_ui()
        self._apply_icon()

        self.after(2000, self._test_imports)

    def _test_imports(self):
        try:
            from markitdown import MarkItDown
            m = MarkItDown()
            import numpy
            _ = numpy.__version__
            self.status_var.set("✅ Módulos de conversión OK")
        except Exception as e:
            self.status_var.set(f"⚠️ Error en imports: {e}")

    def _apply_icon(self):
        try:
            icon_path = Path(__file__).resolve().parent / "assets" / "icon.ico"
            if icon_path.exists():
                self.iconbitmap(str(icon_path))
        except Exception:
            pass

    def _build_ui(self):
        self.grid_columnconfigure(0, weight=1)
        self.grid_rowconfigure(0, weight=0)
        self.grid_rowconfigure(1, weight=1)

        self._build_top_bar()
        self._build_main_area()
        self._build_status_bar()

    def _build_top_bar(self):
        top = ctk.CTkFrame(self, corner_radius=0, height=50)
        top.grid(row=0, column=0, sticky="nsew", padx=0, pady=0)
        top.grid_columnconfigure(3, weight=1)
        top.grid_propagate(False)

        ctk.CTkLabel(top, text="📄  Convertir a .MD", font=("Segoe UI", 18, "bold")).grid(
            row=0, column=0, padx=(15, 10), pady=10, sticky="w"
        )

        prov_cfg = PROVIDER_DEFAULTS[self.current_provider]
        ctk.CTkLabel(top, text="Proveedor:", font=("Segoe UI", 12)).grid(
            row=0, column=1, padx=(5, 2), pady=10, sticky="e"
        )
        self.provider_var = ctk.StringVar(value=self.current_provider)
        self.provider_menu = ctk.CTkOptionMenu(
            top,
            values=list(PROVIDER_DEFAULTS.keys()),
            variable=self.provider_var,
            command=self._on_provider_change,
            width=130,
        )
        self.provider_menu.grid(row=0, column=2, padx=(0, 10), pady=10, sticky="w")

        ctk.CTkLabel(top, text="Modelo:", font=("Segoe UI", 12)).grid(
            row=0, column=3, padx=(5, 2), pady=10, sticky="e"
        )
        self.model_entry = ctk.CTkEntry(top, width=160, placeholder_text=prov_cfg["default_model"])
        self.model_entry.grid(row=0, column=4, padx=(0, 10), pady=10, sticky="w")
        self.model_entry.insert(0, prov_cfg["default_model"])

        ctk.CTkLabel(top, text="API Key:", font=("Segoe UI", 12)).grid(
            row=0, column=5, padx=(5, 2), pady=10, sticky="e"
        )
        self.api_key_entry = ctk.CTkEntry(top, width=200, placeholder_text="Ingresá tu API Key", show="*")
        self.api_key_entry.grid(row=0, column=6, padx=(0, 15), pady=10, sticky="w")
        env_key = _load_env_key(prov_cfg["key_hint"]) if prov_cfg["key_hint"] else ""
        if env_key:
            self.api_key_entry.insert(0, env_key)

    def _build_main_area(self):
        main = ctk.CTkFrame(self)
        main.grid(row=1, column=0, sticky="nsew", padx=10, pady=(0, 5))
        main.grid_columnconfigure(0, weight=0, minsize=280)
        main.grid_columnconfigure(1, weight=1)
        main.grid_rowconfigure(0, weight=1)

        self._build_left_panel(main)
        self._build_right_panel(main)

    def _build_left_panel(self, parent):
        left = ctk.CTkFrame(parent, corner_radius=10)
        left.grid(row=0, column=0, sticky="nsew", padx=(0, 8), pady=5)
        left.grid_rowconfigure(2, weight=0)
        left.grid_rowconfigure(3, weight=0)

        ctk.CTkLabel(left, text="Archivo", font=("Segoe UI", 14, "bold")).grid(
            row=0, column=0, padx=15, pady=(15, 5), sticky="w"
        )

        self.file_label = ctk.CTkLabel(
            left, text="Ningún archivo seleccionado",
            font=("Segoe UI", 11), text_color=("gray60", "gray40"),
        )
        self.file_label.grid(row=1, column=0, padx=15, pady=(0, 10), sticky="w")

        self.select_btn = ctk.CTkButton(
            left, text="📂  Seleccionar archivo",
            command=self._select_file, height=36,
        )
        self.select_btn.grid(row=2, column=0, padx=15, pady=(0, 8), sticky="ew")

        self.convert_btn = ctk.CTkButton(
            left, text="🔄  Convertir a Markdown",
            command=self._convert_file, height=40,
            state="disabled",
            fg_color="#667eea", hover_color="#5a6fd6",
        )
        self.convert_btn.grid(row=3, column=0, padx=15, pady=(0, 8), sticky="ew")

        self.progress_bar = ctk.CTkProgressBar(left, mode="indeterminate")
        self.progress_bar.grid(row=4, column=0, padx=15, pady=(0, 8), sticky="ew")
        self.progress_bar.grid_remove()

        self.progress_label = ctk.CTkLabel(left, text="", font=("Segoe UI", 10))
        self.progress_label.grid(row=5, column=0, padx=15, pady=(0, 15), sticky="w")

        left.grid_columnconfigure(0, weight=1)

    def _build_right_panel(self, parent):
        right = ctk.CTkFrame(parent, corner_radius=10)
        right.grid(row=0, column=1, sticky="nsew", padx=(8, 0), pady=5)
        right.grid_rowconfigure(1, weight=1)
        right.grid_columnconfigure(0, weight=1)

        self.tab_view = ctk.CTkTabview(right, corner_radius=8)
        self.tab_view.grid(row=0, column=0, sticky="nsew", padx=10, pady=(10, 0))

        self.tab_preview = self.tab_view.add("👁️  Vista Previa")
        self.tab_edit = self.tab_view.add("✏️  Editor")
        self.tab_chat = self.tab_view.add("🤖  Asistente")

        self._build_preview_tab()
        self._build_edit_tab()
        self._build_chat_tab()

        self.download_btn = ctk.CTkButton(
            right, text="📥  Descargar .md",
            command=self._download_file, height=34,
            fg_color="#764ba2", hover_color="#6a4292",
            state="disabled",
        )
        self.download_btn.grid(row=1, column=0, padx=10, pady=(8, 10), sticky="ew")

        self.info_label = ctk.CTkLabel(right, text="", font=("Segoe UI", 10))
        self.info_label.grid(row=2, column=0, padx=10, pady=(0, 5), sticky="w")

    def _build_preview_tab(self):
        self.tab_preview.grid_rowconfigure(0, weight=1)
        self.tab_preview.grid_columnconfigure(0, weight=1)
        self.preview_text = ctk.CTkTextbox(self.tab_preview, wrap="word", font=("Consolas", 12))
        self.preview_text.grid(row=0, column=0, sticky="nsew", padx=5, pady=5)
        self.preview_text.insert("1.0", "Subí un archivo y presioná Convertir para ver el resultado aquí.")
        self.preview_text.configure(state="disabled")

    def _build_edit_tab(self):
        self.tab_edit.grid_rowconfigure(0, weight=1)
        self.tab_edit.grid_columnconfigure(0, weight=1)
        self.edit_text = ctk.CTkTextbox(self.tab_edit, wrap="word", font=("Consolas", 12))
        self.edit_text.grid(row=0, column=0, sticky="nsew", padx=5, pady=5)

    def _build_chat_tab(self):
        self.tab_chat.grid_rowconfigure(0, weight=1)
        self.tab_chat.grid_columnconfigure(0, weight=1)
        self.tab_chat.grid_rowconfigure(1, weight=0)

        self.chat_display = ctk.CTkTextbox(self.tab_chat, wrap="word", state="disabled", font=("Segoe UI", 11))
        self.chat_display.grid(row=0, column=0, sticky="nsew", padx=5, pady=5)

        input_frame = ctk.CTkFrame(self.tab_chat)
        input_frame.grid(row=1, column=0, sticky="ew", padx=5, pady=(0, 8))
        input_frame.grid_columnconfigure(0, weight=1)

        self.chat_entry = ctk.CTkEntry(input_frame, placeholder_text="Escribí los cambios que deseas aplicar...")
        self.chat_entry.grid(row=0, column=0, sticky="ew", padx=(5, 5), pady=5)
        self.chat_entry.bind("<Return>", lambda e: self._send_chat())

        self.chat_send_btn = ctk.CTkButton(
            input_frame, text="Enviar", command=self._send_chat, width=80,
        )
        self.chat_send_btn.grid(row=0, column=1, padx=(0, 5), pady=5)

    def _build_status_bar(self):
        status = ctk.CTkFrame(self, corner_radius=0, height=28)
        status.grid(row=2, column=0, sticky="nsew", padx=0, pady=0)
        status.grid_columnconfigure(0, weight=1)
        status.grid_propagate(False)

        self.status_var = ctk.StringVar(value="Listo")
        ctk.CTkLabel(status, textvariable=self.status_var, font=("Segoe UI", 10)).grid(
            row=0, column=0, padx=12, pady=2, sticky="w"
        )

    def _on_provider_change(self, choice):
        self.current_provider = choice
        cfg = PROVIDER_DEFAULTS[choice]
        self.model_entry.delete(0, "end")
        self.model_entry.insert(0, cfg["default_model"])
        if not cfg["needs_key"]:
            self.api_key_entry.delete(0, "end")
            self.api_key_entry.configure(placeholder_text="No requiere key")
        else:
            env_key = _load_env_key(cfg["key_hint"]) if cfg["key_hint"] else ""
            self.api_key_entry.delete(0, "end")
            self.api_key_entry.insert(0, env_key)
            self.api_key_entry.configure(placeholder_text="Ingresá tu API Key")

    def _select_file(self):
        filetypes = [
            ("Documentos", "*.pdf *.docx *.xlsx *.pptx *.html *.csv"),
            ("Imágenes", "*.png *.jpg *.jpeg"),
            ("Todos", "*.*"),
        ]
        path = filedialog.askopenfilename(title="Seleccionar archivo", filetypes=filetypes)
        if not path:
            return
        self.selected_file = Path(path)
        size_kb = self.selected_file.stat().st_size / 1024
        self.file_label.configure(text=f"{self.selected_file.name}  ({size_kb:.1f} KB)", text_color=("white", "white"))
        self.convert_btn.configure(state="normal")
        self.status_var.set(f"Archivo seleccionado: {self.selected_file.name}")

    def _convert_file(self):
        if not self.selected_file:
            return

        api_key = self.api_key_entry.get().strip()
        cfg = PROVIDER_DEFAULTS[self.current_provider]
        if cfg["needs_key"] and not api_key:
            messagebox.showerror("Error", "Completá la API Key del proveedor.")
            return

        self.convert_btn.configure(state="disabled")
        self.select_btn.configure(state="disabled")
        self.progress_bar.grid()
        self.progress_bar.start()
        self.progress_label.configure(text="Iniciando conversión...")
        self.status_var.set("Convirtiendo...")

        threading.Thread(target=self._do_convert, args=(api_key,), daemon=True).start()

    def _do_convert(self, api_key):
        try:
            from markitdown._stream_info import StreamInfo

            self._update_status(10, "Guardando archivo temporal...")
            with tempfile.TemporaryDirectory() as temp_dir:
                temp_path = Path(temp_dir) / self.selected_file.name
                temp_path.write_bytes(self.selected_file.read_bytes())

                self._update_status(30, "Conectando con el proveedor LLM...")
                client = self._build_client(api_key)
                service = MarkdownService(openai_client=client)

                self._update_status(50, "Procesando con MarkItDown + LLM...")
                stream_info = StreamInfo(extension=self.selected_file.suffix.lstrip("."))
                model = self.model_entry.get().strip()
                md_text = service.convert(str(temp_path), model=model, stream_info=stream_info)

            self.md_content = md_text
            self.after(0, self._on_convert_success, md_text)
        except UnsupportedFileTypeError:
            self.after(0, self._on_convert_error, "Tipo de archivo no soportado por MarkItDown.")
        except Exception as e:
            self.after(0, self._on_convert_error, str(e))

    def _build_client(self, api_key):
        base_url = PROVIDER_DEFAULTS[self.current_provider]["base_url"]
        model = self.model_entry.get().strip()
        if self.current_provider == "Gemini":
            return GeminiCompatibleProvider(api_key=api_key, base_url=base_url, model=model).create_chat_client()
        if self.current_provider == "OpenAI":
            return OpenAIProvider(api_key=api_key).create_chat_client()
        if self.current_provider == "Local (Ollama)":
            return LocalOpenAICompatProvider(api_key=api_key or "ollama", base_url=base_url, model=model).create_chat_client()
        raise ValueError(f"Proveedor no soportado: {self.current_provider}")

    def _update_status(self, progress, text):
        self.after(0, lambda: self.progress_label.configure(text=text))

    def _on_convert_success(self, md_text):
        self.progress_bar.stop()
        self.progress_bar.grid_remove()
        self.progress_label.configure(text="¡Conversión completada!")
        self.convert_btn.configure(state="normal")
        self.select_btn.configure(state="normal")
        self.download_btn.configure(state="normal")

        self.preview_text.configure(state="normal")
        self.preview_text.delete("1.0", "end")
        self.preview_text.insert("1.0", md_text)
        self.preview_text.configure(state="disabled")

        self.edit_text.delete("1.0", "end")
        self.edit_text.insert("1.0", md_text)

        self.chat_history = []
        self.chat_display.configure(state="normal")
        self.chat_display.delete("1.0", "end")
        self.chat_display.configure(state="disabled")

        self.tab_view.set("👁️  Vista Previa")
        self.status_var.set("Conversión lista")

    def _on_convert_error(self, msg):
        self.progress_bar.stop()
        self.progress_bar.grid_remove()
        self.progress_label.configure(text="Error en la conversión")
        self.convert_btn.configure(state="normal")
        self.select_btn.configure(state="normal")
        messagebox.showerror("Error", msg)
        self.status_var.set(f"Error: {msg}")

    def _download_file(self):
        if not self.md_content:
            return
        content = self.edit_text.get("1.0", "end-1c")
        if not content.strip():
            content = self.md_content
        stem = self.selected_file.stem if self.selected_file else "documento"
        filetypes = [("Markdown", "*.md")]
        path = filedialog.asksaveasfilename(
            title="Guardar como",
            defaultextension=".md",
            filetypes=filetypes,
            initialfile=f"{stem}.md",
        )
        if path:
            Path(path).write_text(content, encoding="utf-8")
            self.status_var.set(f"Archivo guardado: {Path(path).name}")
            messagebox.showinfo("Guardado", f"Documento guardado en:\n{path}")

    def _send_chat(self):
        msg = self.chat_entry.get().strip()
        if not msg:
            return
        if not self.md_content:
            messagebox.showinfo("Info", "Primero convertí un archivo a Markdown.")
            return

        api_key = self.api_key_entry.get().strip()
        if not api_key:
            messagebox.showerror("Error", "Configurá la API Key para usar el asistente.")
            return

        self.chat_entry.delete(0, "end")
        self.chat_send_btn.configure(state="disabled")

        self._append_chat("user", msg)
        threading.Thread(target=self._do_chat, args=(msg, api_key), daemon=True).start()

    def _do_chat(self, msg, api_key):
        try:
            client = self._build_client(api_key)
            service = MarkdownService(openai_client=client)
            current_md = self.edit_text.get("1.0", "end-1c") or self.md_content
            new_content = service.edit(current_md, msg)

            self.md_content = new_content
            self.after(0, self._on_chat_success, msg, new_content)
        except Exception as e:
            self.after(0, self._on_chat_error, str(e))

    def _on_chat_success(self, msg, new_content):
        self.preview_text.configure(state="normal")
        self.preview_text.delete("1.0", "end")
        self.preview_text.insert("1.0", new_content)
        self.preview_text.configure(state="disabled")

        self.edit_text.delete("1.0", "end")
        self.edit_text.insert("1.0", new_content)

        self._append_chat("assistant", f"✅ Cambios aplicados: {msg}")
        self.chat_send_btn.configure(state="normal")
        self.status_var.set("Cambios aplicados por el asistente")

    def _on_chat_error(self, msg):
        self._append_chat("assistant", f"❌ Error: {msg}")
        self.chat_send_btn.configure(state="normal")
        self.status_var.set(f"Error del asistente: {msg}")

    def _append_chat(self, role, text):
        prefix = "🧑  Tú" if role == "user" else "🤖  Asistente"
        self.chat_display.configure(state="normal")
        self.chat_display.insert("end", f"\n{prefix}:\n{text}\n\n")
        self.chat_display.see("end")
        self.chat_display.configure(state="disabled")


if __name__ == "__main__":
    app = ConvertirApp()
    app.mainloop()
