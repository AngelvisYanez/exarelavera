@echo off
echo ========================================================
echo  Servidor de Desarrollo PHP 5.6 (misma version que Produccion) + MariaDB Local
echo ========================================================
cd /d "%~dp0"
rem Forzar config de PHP 5.6: ignora el php.ini de php82 (PHPRC / PHP_INI_SCAN_DIR)
set "PHPRC=C:\Users\ismaa\scoop\apps\php56\current"
set "PHP_INI_SCAN_DIR="
echo Servidor activo en: http://127.0.0.1:8000
echo PHP 5.6.40 acorde a produccion. Presiona Ctrl+C para detener.
"C:\Users\ismaa\scoop\apps\php56\current\php.exe" -c "C:\Users\ismaa\scoop\apps\php56\current\php.ini" -S 127.0.0.1:8000