"""
Servidor HTTP simple en Python para demostración de módulos externos.
Escucha en el puerto 5050 y responde con información del sistema.
"""
import http.server
import json
import socket
import platform
import datetime
import os

PORT = 5050

class Handler(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        data = {
            "status": "running",
            "module": "Hello Python Server",
            "version": "1.0.0",
            "timestamp": datetime.datetime.now().isoformat(),
            "hostname": socket.gethostname(),
            "python_version": platform.python_version(),
            "os": platform.system() + " " + platform.release(),
            "pid": os.getpid(),
            "message": "Modulo externo Python funcionando correctamente desde ERP Relavera"
        }
        
        self.send_response(200)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Access-Control-Allow-Origin", "*")
        self.end_headers()
        self.wfile.write(json.dumps(data, indent=2, ensure_ascii=False).encode("utf-8"))
    
    def log_message(self, format, *args):
        print(f"[{datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] {args[0]}")

if __name__ == "__main__":
    with http.server.HTTPServer(("127.0.0.1", PORT), Handler) as server:
        print(f"Servidor Python corriendo en http://127.0.0.1:{PORT}")
        print(f"PID: {os.getpid()}")
        print("Presiona Ctrl+C para detener")
        server.serve_forever()
