# ============================================================================
#  start-local.ps1
#  Levanta el proyecto "EXA API + Panel" en local con PHP 8.2 + nginx.
#
#  Arquitectura (sesión compartida, para que la herramienta de tokens NO pida
#  credenciales cuando ya estás logueado en el panel):
#    - nginx escucha en 127.0.0.1:8080 (misma origin para panel y API)
#        /    -> panel del repositorio
#        /v1  -> API Slim (carpeta api/ del repositorio)
#    - php-cgi 8.2 escucha en 127.0.0.1:9000 (backend fastcgi de nginx)
#    - panel y API comparten la cookie de sesión (PHPSESSID) y session.save_path
#
#  Uso:
#    powershell -ExecutionPolicy Bypass -File .\start-local.ps1
# ============================================================================

$ErrorActionPreference = "Stop"

# ---------------------------------------------------------------------------
# Rutas configurables (ajusta aquí si tus instalaciones cambian)
# ---------------------------------------------------------------------------
$RepoRoot   = Split-Path -Parent $MyInvocation.MyCommand.Path          # raíz del repo (donde está este script)
$Php82Dir   = "C:\Users\ismaa\scoop\apps\php82\current"                # binario PHP 8.2
$NginxDir   = "C:\Users\ismaa\scoop\apps\nginx\current"                # binario nginx
$SessionDir = "$env:TEMP\opencode\sessions"                            # donde PHP guarda las sesiones

$env:PHPRC = $Php82Dir
$env:PHP_INI_SCAN_DIR = ""

$ApiPort    = 8080
$FastCgiPort = 9000

$Php82    = Join-Path $Php82Dir "php.exe"
$PhpCgi   = Join-Path $Php82Dir "php-cgi.exe"
$NginxExe = Join-Path $NginxDir "nginx.exe"
$PhpIni   = Join-Path $Php82Dir "php.ini"

$NginxErl = Join-Path $NginxDir "logs\exa-api-error.log"
$NginxPid = Join-Path $NginxDir "logs\exa-api.pid"

# La API Slim vive en <repo>/api; ese es el "document root" lógico de la API.
$ApiFolder = Join-Path $RepoRoot "api"

function Write-Step($m) { Write-Host "`n==> $m" -ForegroundColor Cyan }
function Write-OK($m)   { Write-Host "    OK: $m" -ForegroundColor Green }
function Write-Warn($m) { Write-Host "    AVISO: $m" -ForegroundColor Yellow }

# ---------------------------------------------------------------------------
# 0) Comprobaciones previas
# ---------------------------------------------------------------------------
Write-Step "Comprobando instalaciones (PHP 8.2 y nginx)"
if (-not (Test-Path $Php82))   { throw "No se encuentra PHP 8.2 en: $Php82" }
if (-not (Test-Path $PhpCgi))  { throw "No se encuentra php-cgi.exe en: $PhpCgi" }
if (-not (Test-Path $NginxExe)){ throw "No se encuentra nginx.exe en: $NginxExe" }
if (-not (Test-Path $ApiFolder)){ throw "No se encuentra la carpeta api/ del repo: $ApiFolder" }
Write-OK "php82, php-cgi, nginx y api/ presentes"

# ---------------------------------------------------------------------------
# 1) Directorio de sesiones + php.ini
# ---------------------------------------------------------------------------
Write-Step "Preparando session.save_path: $SessionDir"
if (-not (Test-Path $SessionDir)) { New-Item -ItemType Directory -Path $SessionDir -Force | Out-Null }
Write-OK "Directorio de sesiones listo"

Write-Step "Garantizando php.ini activo para PHP 8.2"
if (-not (Test-Path $PhpIni)) {
    $dev = Join-Path $Php82Dir "php.ini-development"
    if (-not (Test-Path $dev)) { throw "No existe php.ini-development en $Php82Dir" }
    Copy-Item $dev $PhpIni
    Write-OK "Creado php.ini desde php.ini-development"
} else {
    Write-OK "php.ini ya existe"
}

# Habilitar extensiones necesarias y session.save_path (idempotente)
$ini = Get-Content -Raw $PhpIni
$ini = $ini -replace ";extension=mysqli",       "extension=mysqli"
$ini = $ini -replace ";extension=pdo_mysql",    "extension=pdo_mysql"
$ini = $ini -replace ";extension=mbstring",     "extension=mbstring"
$ini = $ini -replace ";extension=openssl",      "extension=openssl"
$ini = $ini -replace ";extension=curl",         "extension=curl"
$ini = $ini -replace ";extension=fileinfo",     "extension=fileinfo"
$ini = $ini -replace ';?extension_dir\s*=\s*"ext"', "extension_dir = `"$Php82Dir\ext`""
$ini = $ini -replace ';?extension_dir\s*=\s*"\./"', ';extension_dir = "./"'
# session.save_path: si ya hay una línea, la reemplaza; si no, la añade
$sp = "`"$($SessionDir -replace '\\','/')`""
if ($ini -match "session.save_path\s*=") {
    $ini = $ini -replace "session.save_path\s*=.*", "session.save_path = $sp"
} else {
    $ini += "`nsession.save_path = $sp`n"
}
Set-Content -Path $PhpIni -Value $ini -Encoding Ascii
Write-OK "php.ini configurado (extensiones + session.save_path)"

# ---------------------------------------------------------------------------
# 2) Detener servidores PHP duplicados / ocupantes del puerto
# ---------------------------------------------------------------------------
Write-Step "Liberando puertos $ApiPort y $FastCgiPort (matando ocupantes php)"
$portsToFree = @($ApiPort, $FastCgiPort, 8000, 8082)
foreach ($pr in $portsToFree) {
    Get-NetTCPConnection -LocalPort $pr -State Listen -ErrorAction SilentlyContinue |
        Select-Object -ExpandProperty OwningProcess -Unique |
        ForEach-Object {
            $p = Get-CimInstance Win32_Process -Filter "ProcessId=$_" -ErrorAction SilentlyContinue
            if ($p -and $p.Name -match "php") {
                Stop-Process -Id $_ -Force -ErrorAction SilentlyContinue
                Write-Warn "Detenido PHP PID $_ en puerto $pr"
            }
        }
}
Start-Sleep -Seconds 1

# Frenar cualquier nginx exa-api previo
Get-CimInstance Win32_Process -Filter "Name='nginx.exe'" -ErrorAction SilentlyContinue |
    ForEach-Object {
        if ($_.CommandLine -match "exa-api") {
            & $NginxExe -p $NginxDir -c "conf\exa-api.conf" -s stop 2>$null
            Write-Warn "Detenido nginx exa-api previo"
        }
    }

# ---------------------------------------------------------------------------
# 3) Levantar php-cgi 8.2 en 127.0.0.1:9000
# ---------------------------------------------------------------------------
Write-Step "Arrancando php-cgi 8.2 en 127.0.0.1:$FastCgiPort"
$cgiOut  = Join-Path $NginxDir "logs\php-cgi-exa.out.log"
$cgiErr  = Join-Path $NginxDir "logs\php-cgi-exa.err.log"
$cgiProc = Start-Process -FilePath $PhpCgi `
    -ArgumentList @("-b", "127.0.0.1:$FastCgiPort", "-c", $PhpIni) `
    -WindowStyle Hidden -PassThru `
    -RedirectStandardOutput $cgiOut -RedirectStandardError $cgiErr
Start-Sleep -Seconds 2
if (-not (Get-NetTCPConnection -LocalPort $FastCgiPort -State Listen -ErrorAction SilentlyContinue)) {
    throw "php-cgi no quedó escuchando en $FastCgiPort. Revisa $cgiErr"
}
Write-OK "php-cgi corriendo (PID $($cgiProc.Id)) en :$FastCgiPort"

# ---------------------------------------------------------------------------
# 4) Generar config de nginx (respalda la existente)
# ---------------------------------------------------------------------------
Write-Step "Generando config nginx: conf\exa-api.conf"
$confFile = Join-Path $NginxDir "conf\exa-api.conf"
$backup   = Join-Path $NginxDir "conf\exa-api.conf.bak"
if (Test-Path $confFile) {
    Copy-Item $confFile $backup -Force
    Write-Warn "Respaldo previo en exa-api.conf.bak"
}

$panelRoot = $RepoRoot.Replace("\", "/")
$apiRoot   = $ApiFolder.Replace("\", "/")
$scriptPath = "$apiRoot/router.php"

$conf = @"
# Generado por start-local.ps1
worker_processes  1;
error_log  logs/exa-api-error.log;
pid        logs/exa-api.pid;

events {
    worker_connections  256;
}

http {
    include       mime.types;
    default_type  application/octet-stream;
    sendfile      on;
    keepalive_timeout  65;
    client_max_body_size 64m;
    fastcgi_read_timeout 300;

    # Un solo host (misma origin) => panel y API comparten la cookie de sesion.
    server {
        listen       127.0.0.1:$ApiPort;
        server_name  127.0.0.1 localhost;
        root         $panelRoot;
        index        index.php index.html;

        # ############## PANEL ##############
        location / {
            try_files `$uri `$uri/ =404;
        }

        # ############## API (Slim bajo /v1) ##############
        # Las rutas /v1/* son virtuales de Slim => caen a router.php
        location ^~ /v1 {
            alias $apiRoot/;
            index router.php;
            try_files `$uri `$uri/ @apifallback;
        }

        # Fallback: enviar a router.php (front controller que deriva PATH_INFO)
        location @apifallback {
            rewrite ^ /v1/router.php last;
        }

        # Ejecutar router.php de la API con fastcgi
        location = /v1/router.php {
            fastcgi_pass   127.0.0.1:$FastCgiPort;
            include        fastcgi_params;
            fastcgi_param  SCRIPT_FILENAME $scriptPath;
            fastcgi_param  REQUEST_URI `$request_uri;
            fastcgi_param  QUERY_STRING `$query_string;
        }

        # ############## PHP del panel ##############
        location ~ \.php`$ {
            try_files `$uri =404;
            include        fastcgi_params;
            fastcgi_pass   127.0.0.1:$FastCgiPort;
            fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME `$document_root`$fastcgi_script_name;
        }
    }
}
"@
Set-Content -Path $confFile -Value $conf -Encoding Ascii
Write-OK "Config escrita en $confFile"

# ---------------------------------------------------------------------------
# 5) Arrancar nginx
# ---------------------------------------------------------------------------
Write-Step "Arrancando nginx (exa-api.conf)"
Start-Process -FilePath $NginxExe -ArgumentList @("-p", $NginxDir, "-c", "conf\exa-api.conf") -WorkingDirectory $NginxDir -WindowStyle Hidden
Start-Sleep -Seconds 2

# ---------------------------------------------------------------------------
# 6) Verificación
# ---------------------------------------------------------------------------
Write-Step "Verificando endpoints"
$testUrl = "http://127.0.0.1:$ApiPort/v1/test"
try {
    $r = Invoke-WebRequest -Uri $testUrl -UseBasicParsing -TimeoutSec 15
    Write-OK "GET /v1/test -> $($r.StatusCode) $($r.Content)"
} catch {
    throw "Falla al consultar $testUrl : $($_.Exception.Message)"
}

Write-Host ""
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "  Proyecto LOCAL listo:  http://127.0.0.1:$ApiPort" -ForegroundColor Green
Write-Host "  nginx  -> panel (/) y API (/v1) - misma origin (sesion compartida)" -ForegroundColor Green
Write-Host "  php-cgi 8.2 -> 127.0.0.1:$FastCgiPort" -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Herramienta de tokens:  http://127.0.0.1:$ApiPort/administrador/FRONT/adm_api_tokens.php" -ForegroundColor Yellow
Write-Host "Demo (aplicar permisos):  http://127.0.0.1:$ApiPort/v1/api-tokens-demo" -ForegroundColor Yellow
Write-Host "Probar token:              http://127.0.0.1:$ApiPort/v1/api-tokens-probar" -ForegroundColor Yellow
Write-Host ""
Write-Host "Para DETENER:  powershell -Command `"& nginx -p '$NginxDir' -c 'conf\exa-api.conf' -s stop`" ; luego mata php-cgi." -ForegroundColor DarkGray
