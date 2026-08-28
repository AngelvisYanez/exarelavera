<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EXA Contable API - Documentación Swagger</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .topbar { display: none; }
        
        /* Custom Header Bar */
        .exa-docs-header {
            background: linear-gradient(135deg, #581825 0%, #801326 100%);
            color: #ffffff;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .exa-docs-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.15rem;
            font-weight: 600;
        }
        .exa-docs-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.35);
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .exa-docs-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .exa-docs-label {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .exa-docs-select {
            background: #ffffff;
            color: #333;
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 7px 14px;
            font-size: 0.88rem;
            font-family: inherit;
            font-weight: 500;
            outline: none;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .exa-docs-select:focus {
            border-color: #801326;
            box-shadow: 0 0 0 3px rgba(128, 19, 38, 0.25);
        }
        .exa-docs-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .exa-docs-btn:hover {
            background: rgba(255, 255, 255, 0.28);
            color: #fff;
        }
        
        .swagger-ui .info .title {
            color: #581825;
            font-family: 'Poppins', sans-serif;
        }
        .swagger-ui .opblock.opblock-get {
            border-color: #61affe;
            background: rgba(97, 175, 254, .1);
        }
    </style>
</head>
<body>
    <header class="exa-docs-header">
        <div class="exa-docs-title">
            <span>EXA Contable API REST</span>
            <span class="exa-docs-badge" id="mode-badge">Solo Contacto GET</span>
        </div>
        <div class="exa-docs-controls">
            <label class="exa-docs-label" for="doc-selector">Vista de documentación:</label>
            <select id="doc-selector" class="exa-docs-select" onchange="changeDocView(this.value)">
                <option value="contactos" selected>📌 Solo Contacto GET (Por Defecto)</option>
                <option value="full">🌐 Todos los Módulos (Completo)</option>
                <optgroup label="Filtrar por Módulo Individual">
                    <option value="modulo:contabilidad">Contabilidad</option>
                    <option value="modulo:facturacion">Facturación</option>
                    <option value="modulo:tesoreria">Tesorería</option>
                    <option value="modulo:inventario">Inventario</option>
                    <option value="modulo:adquisiciones">Adquisiciones</option>
                    <option value="modulo:compras">Compras</option>
                    <option value="modulo:rrhh">RRHH</option>
                    <option value="modulo:bodega">Bodega</option>
                    <option value="modulo:caja-chica">Caja Chica</option>
                    <option value="modulo:transporte">Transporte</option>
                    <option value="modulo:bananero">Bananero</option>
                    <option value="modulo:camaronera">Camaronera</option>
                    <option value="modulo:relavera">Relavera</option>
                    <option value="modulo:flujo">Workflows / Flujo</option>
                    <option value="modulo:auditoria">Auditoría</option>
                    <option value="modulo:admin">Administración</option>
                    <option value="modulo:data">Data API</option>
                </optgroup>
            </select>
            <a href="guia" class="exa-docs-btn" title="Ver Guía de Consumo">📖 Guía</a>
        </div>
    </header>

    <div id="swagger-ui"></div>

    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
        var swaggerInstance = null;
        var basePath = window.location.pathname.replace(/\/$/, '');

        function getInitialView() {
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('full') || urlParams.has('all') || urlParams.get('view') === 'all' || urlParams.get('mode') === 'full') {
                return 'full';
            }
            if (urlParams.has('modulo')) {
                return 'modulo:' + urlParams.get('modulo');
            }
            if (urlParams.has('tag')) {
                return 'modulo:' + urlParams.get('tag');
            }
            return 'contactos';
        }

        function loadSwaggerSpec(viewKey) {
            var specUrl = basePath + '/openapi.json';
            var badgeText = 'Solo Contacto GET';

            if (viewKey === 'full') {
                specUrl += '?full=1';
                badgeText = 'Todos los Módulos (Completo)';
            } else if (viewKey && viewKey.indexOf('modulo:') === 0) {
                var mod = viewKey.substring(7);
                specUrl += '?modulo=' + encodeURIComponent(mod);
                badgeText = 'Módulo: ' + mod.toUpperCase();
            }

            document.getElementById('mode-badge').textContent = badgeText;

            swaggerInstance = SwaggerUIBundle({
                url: specUrl,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "BaseLayout",
                persistAuthorization: true
            });
        }

        function changeDocView(viewKey) {
            loadSwaggerSpec(viewKey);
            // Actualizar URL sin recargar para poder compartir el enlace
            var newUrl = window.location.pathname;
            if (viewKey === 'full') {
                newUrl += '?view=all';
            } else if (viewKey.indexOf('modulo:') === 0) {
                newUrl += '?modulo=' + encodeURIComponent(viewKey.substring(7));
            }
            window.history.replaceState({}, '', newUrl);
        }

        window.onload = function() {
            var initial = getInitialView();
            document.getElementById('doc-selector').value = initial;
            loadSwaggerSpec(initial);
        };
    </script>
</body>
</html>
