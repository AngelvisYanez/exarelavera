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
            display: none; /* Oculto al público por defecto */
            align-items: center;
            gap: 10px;
        }
        .exa-docs-controls.visible {
            display: flex !important;
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
            <span class="exa-docs-badge" id="mode-badge">Directorio Operativo</span>
        </div>
        <div class="exa-docs-controls" id="admin-controls">
            <select id="doc-selector" class="exa-docs-select" onchange="changeDocView(this.value)">
                <option value="default" selected>Directorio Operativo</option>
                <option value="full">Todos los Módulos (Completo)</option>
                <optgroup label="Módulos">
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
            // Si el usuario incluye parámetros administrativos/desarrollador, mostrar los controles
            if (urlParams.has('admin') || urlParams.has('dev') || urlParams.has('full') || urlParams.has('all') || urlParams.has('view') || urlParams.has('modulo')) {
                var ctrl = document.getElementById('admin-controls');
                if (ctrl) ctrl.classList.add('visible');
            }

            if (urlParams.has('full') || urlParams.has('all') || urlParams.get('view') === 'all' || urlParams.get('mode') === 'full') {
                return 'full';
            }
            if (urlParams.has('modulo')) {
                return 'modulo:' + urlParams.get('modulo');
            }
            if (urlParams.has('tag')) {
                return 'modulo:' + urlParams.get('tag');
            }
            return 'default';
        }

        function getSpecUrl(viewValue) {
            var url = '/openapi.json';
            if (viewValue === 'full') {
                url += '?full=1';
            } else if (viewValue.startsWith('modulo:')) {
                var mod = viewValue.replace('modulo:', '');
                url += '?modulo=' + encodeURIComponent(mod);
            }
            return url;
        }

        function updateBadge(viewValue) {
            var badge = document.getElementById('mode-badge');
            if (!badge) return;
            if (viewValue === 'full') {
                badge.innerText = 'Vista Completa (Todos los Módulos)';
            } else if (viewValue.startsWith('modulo:')) {
                var mod = viewValue.replace('modulo:', '');
                badge.innerText = 'Módulo: ' + mod.toUpperCase();
            } else {
                badge.innerText = 'Directorio Operativo';
            }
        }

        function initSwagger(specUrl) {
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
                docExpansion: "list",
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                displayRequestDuration: true,
                persistAuthorization: true
            });
        }

        function changeDocView(val) {
            updateBadge(val);
            var specUrl = getSpecUrl(val);
            initSwagger(specUrl);
        }

        window.onload = function() {
            var initialView = getInitialView();
            var sel = document.getElementById('doc-selector');
            if (sel) sel.value = initialView;
            updateBadge(initialView);
            initSwagger(getSpecUrl(initialView));
        };
    </script>
</body>
</html>
