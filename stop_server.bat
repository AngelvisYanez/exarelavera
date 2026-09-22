@echo off
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8000 ^| findstr LISTENING') do taskkill /f /pid %%a
echo Servidor en puerto 8000 detenido.
