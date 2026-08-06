"""
Convertidor HTML/Texto a Markdown - Servidor Web Python
Interfaz web para convertir contenido HTML a formato Markdown.
"""
import http.server
import json
import re
import html
import os
import datetime
import urllib.parse

PORT = 5051

def html_to_markdown(html_text):
    """Convierte HTML basico a Markdown."""
    text = html_text
    
    # Script y style
    text = re.sub(r'<script[^>]*>.*?</script>', '', text, flags=re.DOTALL|re.IGNORECASE)
    text = re.sub(r'<style[^>]*>.*?</style>', '', text, flags=re.DOTALL|re.IGNORECASE)
    
    # Headers
    for i in range(6, 0, -1):
        prefix = '#' * i
        text = re.sub(rf'<h{i}[^>]*>(.*?)</h{i}>', rf'\n{prefix} \1\n', text, flags=re.DOTALL|re.IGNORECASE)
    
    # Bold y italic
    text = re.sub(r'<(?:strong|b)[^>]*>(.*?)</(?:strong|b)>', r'**\1**', text, flags=re.DOTALL|re.IGNORECASE)
    text = re.sub(r'<(?:em|i)[^>]*>(.*?)</(?:em|i)>', r'*\1*', text, flags=re.DOTALL|re.IGNORECASE)
    
    # Links
    text = re.sub(r'<a[^>]*href="([^"]*)"[^>]*>(.*?)</a>', r'[\2](\1)', text, flags=re.DOTALL|re.IGNORECASE)
    
    # Images
    text = re.sub(r'<img[^>]*src="([^"]*)"[^>]*alt="([^"]*)"[^>]*/?>', r'![\2](\1)', text, flags=re.IGNORECASE)
    text = re.sub(r'<img[^>]*src="([^"]*)"[^>]*/?>', r'![](\1)', text, flags=re.IGNORECASE)
    
    # Code blocks
    text = re.sub(r'<pre[^>]*><code[^>]*>(.*?)</code></pre>', r'\n```\n\1\n```\n', text, flags=re.DOTALL|re.IGNORECASE)
    text = re.sub(r'<code[^>]*>(.*?)</code>', r'`\1`', text, flags=re.DOTALL|re.IGNORECASE)
    
    # Lists
    text = re.sub(r'<li[^>]*>(.*?)</li>', r'- \1\n', text, flags=re.DOTALL|re.IGNORECASE)
    text = re.sub(r'</?[ou]l[^>]*>', '\n', text, flags=re.IGNORECASE)
    
    # Blockquote
    text = re.sub(r'<blockquote[^>]*>(.*?)</blockquote>', lambda m: '\n> ' + m.group(1).strip().replace('\n', '\n> ') + '\n', text, flags=re.DOTALL|re.IGNORECASE)
    
    # Tables basicas
    text = re.sub(r'<th[^>]*>(.*?)</th>', r'| \1 ', text, flags=re.DOTALL|re.IGNORECASE)
    text = re.sub(r'<td[^>]*>(.*?)</td>', r'| \1 ', text, flags=re.DOTALL|re.IGNORECASE)
    text = re.sub(r'<tr[^>]*>(.*?)</tr>', r'\1|\n', text, flags=re.DOTALL|re.IGNORECASE)
    
    # Paragraphs y line breaks
    text = re.sub(r'<br\s*/?>', '\n', text, flags=re.IGNORECASE)
    text = re.sub(r'<p[^>]*>', '\n\n', text, flags=re.IGNORECASE)
    text = re.sub(r'</p>', '\n', text, flags=re.IGNORECASE)
    text = re.sub(r'<hr\s*/?>', '\n---\n', text, flags=re.IGNORECASE)
    text = re.sub(r'<div[^>]*>', '\n', text, flags=re.IGNORECASE)
    text = re.sub(r'</div>', '\n', text, flags=re.IGNORECASE)
    
    # Quitar tags restantes
    text = re.sub(r'<[^>]+>', '', text)
    
    # Decodificar entidades HTML
    text = html.unescape(text)
    
    # Limpiar lineas vacias multiple
    text = re.sub(r'\n{3,}', '\n\n', text)
    text = text.strip()
    
    return text


def text_to_markdown(text):
    """Formatea texto plano como Markdown limpio."""
    lines = text.split('\n')
    result = []
    for line in lines:
        stripped = line.strip()
        if not stripped:
            result.append('')
            continue
        result.append(stripped)
    
    text = '\n'.join(result)
    text = re.sub(r'\n{3,}', '\n\n', text)
    return text.strip()


class Handler(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        page = """<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Convertidor a Markdown</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1a1a2e; color: #e0e0e0; height: 100vh; display: flex; flex-direction: column; }
        .header { background: #16213e; padding: 15px 25px; border-bottom: 2px solid #0f3460; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 20px; color: #e94560; }
        .header .info { font-size: 12px; color: #888; }
        .toolbar { background: #16213e; padding: 8px 25px; border-bottom: 1px solid #0f3460; display: flex; gap: 10px; }
        .toolbar button { background: #0f3460; color: #e0e0e0; border: 1px solid #1a1a2e; padding: 6px 16px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .toolbar button:hover { background: #e94560; border-color: #e94560; }
        .toolbar select { background: #0f3460; color: #e0e0e0; border: 1px solid #1a1a2e; padding: 6px 10px; border-radius: 4px; }
        .container { display: flex; flex: 1; overflow: hidden; }
        .panel { flex: 1; display: flex; flex-direction: column; border-right: 1px solid #0f3460; }
        .panel:last-child { border-right: none; }
        .panel-header { background: #16213e; padding: 8px 15px; font-size: 13px; font-weight: bold; color: #e94560; border-bottom: 1px solid #0f3460; }
        .panel textarea { flex: 1; width: 100%; background: #0d1117; color: #c9d1d9; border: none; padding: 15px; font-family: 'Cascadia Code', 'Fira Code', Consolas, monospace; font-size: 14px; line-height: 1.6; resize: none; outline: none; }
        .panel .preview { flex: 1; padding: 20px; overflow-y: auto; background: #0d1117; line-height: 1.8; }
        .preview h1, .preview h2, .preview h3 { color: #e94560; margin: 15px 0 8px 0; }
        .preview h1 { font-size: 24px; border-bottom: 1px solid #333; padding-bottom: 5px; }
        .preview h2 { font-size: 20px; }
        .preview h3 { font-size: 17px; }
        .preview code { background: #161b22; padding: 2px 6px; border-radius: 3px; font-size: 13px; color: #e94560; }
        .preview pre { background: #161b22; padding: 12px; border-radius: 5px; overflow-x: auto; margin: 10px 0; }
        .preview pre code { background: none; padding: 0; }
        .preview blockquote { border-left: 3px solid #e94560; padding-left: 15px; color: #888; margin: 10px 0; }
        .preview table { border-collapse: collapse; width: 100%%; margin: 10px 0; }
        .preview th, .preview td { border: 1px solid #333; padding: 8px 12px; text-align: left; }
        .preview th { background: #16213e; color: #e94560; }
        .preview a { color: #58a6ff; }
        .preview hr { border: none; border-top: 1px solid #333; margin: 15px 0; }
        .preview ul, .preview ol { padding-left: 25px; margin: 8px 0; }
        .preview li { margin: 4px 0; }
        .status-bar { background: #16213e; padding: 5px 25px; font-size: 11px; color: #888; border-top: 1px solid #0f3460; display: flex; justify-content: space-between; }
        .samples { margin-left: auto; }
        .samples button { background: transparent; color: #888; border: 1px solid #333; padding: 2px 8px; border-radius: 3px; cursor: pointer; font-size: 11px; margin-left: 5px; }
        .samples button:hover { color: #e94560; border-color: #e94560; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Convertidor a Markdown</h1>
            <div class="info">Modulo externo Python - v1.0.0 | Puerto: """ + str(PORT) + """</div>
        </div>
        <div class="info" id="timestamp"></div>
    </div>
    <div class="toolbar">
        <button onclick="convert()">&#9654; Convertir</button>
        <button onclick="copyOutput()">&#128203; Copiar MD</button>
        <button onclick="clearAll()">&#128465; Limpiar</button>
        <select id="mode" onchange="convert()">
            <option value="auto">Auto-detectar</option>
            <option value="html">HTML a Markdown</option>
            <option value="text">Texto plano</option>
        </select>
        <div class="samples">
            <span>Ejemplos:</span>
            <button onclick="loadSample('html')">HTML</button>
            <button onclick="loadSample('table')">Tabla</button>
            <button onclick="loadSample('code')">Codigo</button>
        </div>
    </div>
    <div class="container">
        <div class="panel">
            <div class="panel-header">Entrada (HTML / Texto)</div>
            <textarea id="input" placeholder="Pega tu HTML o texto aqui..."></textarea>
        </div>
        <div class="panel">
            <div class="panel-header">Salida (Markdown)</div>
            <div class="preview" id="output"><em style="color:#666">El resultado Markdown aparecera aqui...</em></div>
        </div>
    </div>
    <div class="status-bar">
        <span id="stats">Caracteres: 0 | Lineas: 0</span>
        <span>Python """ + __import__('platform').python_version() + """ | PID: """ + str(os.getpid()) + """</span>
    </div>
    <script>
        document.getElementById('timestamp').textContent = new Date().toLocaleString();
        document.getElementById('input').addEventListener('input', function() {
            document.getElementById('stats').textContent = 'Caracteres: ' + this.value.length + ' | Lineas: ' + this.value.split('\\n').length;
        });
        
        function convert() {
            var input = document.getElementById('input').value;
            var mode = document.getElementById('mode').value;
            fetch('/convert', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({text: input, mode: mode})
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.getElementById('output').innerHTML = '<pre style="white-space:pre-wrap;font-size:14px;line-height:1.6;color:#c9d1d9">' + data.markdown.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</pre>';
            });
        }
        
        function copyOutput() {
            var el = document.getElementById('output');
            var text = el.innerText || el.textContent;
            navigator.clipboard.writeText(text).then(function() { alert('Markdown copiado!'); });
        }
        
        function clearAll() {
            document.getElementById('input').value = '';
            document.getElementById('output').innerHTML = '<em style="color:#666">El resultado Markdown aparecera aqui...</em>';
        }
        
        function loadSample(type) {
            var samples = {
                html: '<h1>Título Principal</h1>\\n<p>Este es un <strong>ejemplo</strong> de conversión <em>HTML a Markdown</em>.</p>\\n<ul>\\n  <li>Elemento 1</li>\\n  <li>Elemento 2</li>\\n  <li>Elemento 3</li>\\n</ul>\\n<p>Visita <a href="https://example.com">nuestro sitio</a> para más info.</p>',
                table: '<table>\\n  <tr><th>Nombre</th><th>Tipo</th><th>Precio</th></tr>\\n  <tr><td>Modulo A</td><td>Node.js</td><td>$100</td></tr>\\n  <tr><td>Modulo B</td><td>Python</td><td>$250</td></tr>\\n  <tr><td>Modulo C</td><td>Shell</td><td>$50</td></tr>\\n</table>',
                code: '<h2>Instalación</h2>\\n<p>Para instalar el modulo ejecuta:</p>\\n<pre><code>pip install markdown-converter\\npython server.py --port 5051</code></pre>\\n<blockquote>Nota: Requiere Python 3.8+</blockquote>'
            };
            document.getElementById('input').value = samples[type];
            convert();
        }
    </script>
</body>
</html>"""
        self.send_response(200)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.end_headers()
        self.wfile.write(page.encode("utf-8"))

    def do_POST(self):
        if self.path == '/convert':
            length = int(self.headers.get('Content-Length', 0))
            body = self.rfile.read(length).decode('utf-8')
            data = json.loads(body)
            
            text = data.get('text', '')
            mode = data.get('mode', 'auto')
            
            if mode == 'html' or (mode == 'auto' and ('<' in text and '>' in text)):
                md = html_to_markdown(text)
            else:
                md = text_to_markdown(text)
            
            response = json.dumps({"markdown": md, "chars": len(md)}, ensure_ascii=False)
            self.send_response(200)
            self.send_header("Content-Type", "application/json; charset=utf-8")
            self.end_headers()
            self.wfile.write(response.encode("utf-8"))
        else:
            self.send_response(404)
            self.end_headers()
    
    def log_message(self, format, *args):
        print(f"[{datetime.datetime.now().strftime('%H:%M:%S')}] {args[0]}")


if __name__ == "__main__":
    with http.server.HTTPServer(("127.0.0.1", PORT), Handler) as server:
        print(f"Convertidor Markdown corriendo en http://127.0.0.1:{PORT}")
        print(f"PID: {os.getpid()}")
        server.serve_forever()
