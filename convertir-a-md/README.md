# Convertir a .MD

Una herramienta ligera para convertir distintos tipos de archivos (PDFs, Office, Imágenes) a formato Markdown (`.md`).
Esta herramienta utiliza **la librería oficial de Microsoft MarkItDown** para procesar los archivos, pero está **impulsada por la API de Google Gemini 2.5 Flash** para resolver tareas complejas (como la descripción inteligente de imágenes) manteniendo un costo extremadamente bajo.

## Características
- **Poder Híbrido:** Utiliza el eficiente parseo local de `markitdown` de Microsoft para extraer el texto directamente de los documentos, evitando gastar tokens innecesarios.
- **Impulsado por Gemini:** Cuando el documento contiene imágenes que requieren descripción u OCR, la herramienta redirige la consulta inteligentemente al modelo `gemini-2.5-flash` mediante la compatibilidad de API de OpenAI que ofrece Google.
- **Bajo Costo:** Al usar el modelo Flash de Google, el uso de tokens para lectura y análisis de imágenes es rápido y muy económico.

## Instalación

1. Clona o descarga este repositorio.
2. Crea y activa un entorno virtual:
   ```bash
   python -m venv venv
   # En Windows:
   venv\Scripts\activate
   # En Linux/Mac:
   source venv/bin/activate
   ```
3. Instala las dependencias necesarias:
   ```bash
   pip install -r requirements.txt
   ```

## Configuración

Crea un archivo `.env` en la raíz de este directorio y añade tu clave API de Google Gemini (puedes conseguir una en [Google AI Studio](https://aistudio.google.com/)):

```env
GEMINI_API_KEY=tu_clave_api_aqui
```

## Uso

### 1. Interfaz Gráfica (Recomendado)
Para una experiencia visual e interactiva donde puedas arrastrar, soltar y descargar los archivos, arranca la aplicación de Streamlit:

```bash
streamlit run app.py
```
*Se abrirá automáticamente una pestaña en tu navegador web. Puedes ingresar tu API Key directamente en la interfaz si no deseas usar el archivo `.env`.*

### 2. Uso por Consola
Si prefieres automatizar o usar la consola, ejecuta el script pasándole como argumento la ruta del archivo que quieres convertir:

```bash
python convert.py ruta/a/tu/archivo.pdf
```
o
```bash
python convert.py ruta/a/tu/imagen.png
```

El script generará automáticamente un nuevo archivo en el mismo directorio con el formato `.md` listo para usarse como contexto en prompts para otras IAs.
