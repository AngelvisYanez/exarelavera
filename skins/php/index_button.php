<?php
if (!isset($_SESSION)) {
    session_start();
}

$empresaNombre = isset($_SESSION['Ses_Emp_Nom']) && !empty($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : 'EXA ERP';
$usuarioNombre = isset($_SESSION['Ses_Prs_Nom']) ? $_SESSION['Ses_Prs_Nom'] . ' ' . (isset($_SESSION['Ses_Prs_Ape']) ? $_SESSION['Ses_Prs_Ape'] : '') : 'Usuario';
$perfilNombre  = isset($_SESSION['Ses_Per_Des']) && is_array($_SESSION['Ses_Per_Des']) ? implode(', ', $_SESSION['Ses_Per_Des']) : 'Administrador';
$sucursalNombre = isset($_SESSION['Ses_Suc_Nom']) ? $_SESSION['Ses_Suc_Nom'] : 'MATRIZ';
$bddNombre = isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : 'ecoparkmining';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Inicio · EXA ERP</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="../../skins/js/jquery.js"></script>
    <style>
        :root {
            --primary: #800020;
            --primary-dark: #5c0017;
            --primary-light: #a31535;
            --bg-gradient: linear-gradient(135deg, #1e2433 0%, #11141d 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-dark: #2d3748;
            --text-muted: #718096;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            color: var(--text-dark);
            min-height: 100vh;
            padding: 24px 20px;
        }

        .dashboard-container {
            max-width: 1240px;
            margin: 0 auto;
        }

        /* Banner Superior */
        .welcome-banner {
            background: linear-gradient(135deg, #800020 0%, #4a0614 100%);
            border-radius: 16px;
            padding: 28px 32px;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(128, 0, 32, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 28px;
        }

        .welcome-info h1 {
            font-size: 1.65rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .welcome-info p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin: 0;
        }

        .badge-empresa {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .badge-status {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px #10b981;
        }

        /* Título de sección */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--primary);
        }

        /* Grid de Módulos */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .module-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 22px 20px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--card-color, var(--primary));
            transition: width 0.2s ease;
        }

        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -3px rgba(0, 0, 0, 0.09);
            border-color: #d1d5db;
        }

        .module-card:hover::before {
            width: 6px;
        }

        .card-top {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 12px;
        }

        .card-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: var(--icon-bg, #f3e8eb);
            color: var(--icon-color, var(--primary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .card-meta h3 {
            font-size: 1.05rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 3px;
        }

        .card-meta span {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--card-color, var(--primary));
        }

        .card-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.4;
            margin-bottom: 14px;
            flex-grow: 1;
        }

        .card-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--card-color, var(--primary));
            padding-top: 10px;
            border-top: 1px solid #f3f4f6;
        }

        .card-action i {
            transition: transform 0.2s ease;
        }

        .module-card:hover .card-action i {
            transform: translateX(4px);
        }

        /* Variantes de color por módulo */
        .card-api { --card-color: #2563eb; --icon-bg: #eff6ff; --icon-color: #2563eb; }
        .card-docs { --card-color: #059669; --icon-bg: #ecfdf5; --icon-color: #059669; }
        .card-ventas { --card-color: #d97706; --icon-bg: #fffbeb; --icon-color: #d97706; }
        .card-clientes { --card-color: #7c3aed; --icon-bg: #f5f3ff; --icon-color: #7c3aed; }
        .card-mineria { --card-color: #800020; --icon-bg: #fdf2f4; --icon-color: #800020; }
        .card-sri { --card-color: #0284c7; --icon-bg: #f0f9ff; --icon-color: #0284c7; }
        .card-contab { --card-color: #4b5563; --icon-bg: #f3f4f6; --icon-color: #4b5563; }
        .card-tokens { --card-color: #db2777; --icon-bg: #fdf2f8; --icon-color: #db2777; }

        @media (max-width: 768px) {
            body { padding: 14px 10px; }
            .welcome-banner { padding: 20px; }
            .welcome-info h1 { font-size: 1.35rem; }
            .modules-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Banner Bienvenida -->
        <div class="welcome-banner">
            <div class="welcome-info">
                <h1>
                    <i class="fa-solid fa-layer-group"></i>
                    Panel Principal EXA ERP
                </h1>
                <p>Bienvenido, <strong><?php echo htmlspecialchars($usuarioNombre); ?></strong> (<?php echo htmlspecialchars($perfilNombre); ?>)</p>
            </div>
            <div class="badge-empresa">
                <span class="badge-status"></span>
                <span><?php echo htmlspecialchars($empresaNombre); ?> &middot; <?php echo htmlspecialchars($sucursalNombre); ?></span>
            </div>
        </div>

        <!-- Módulos de Operación & API -->
        <div class="section-header">
            <div class="section-title">
                <i class="fa-solid fa-star"></i>
                Accesos Directos y Módulos Activos
            </div>
        </div>

        <div class="modules-grid">
            <!-- Gestor de Tokens API -->
            <a class="module-card card-tokens action-link" href="/administrador/FRONT/adm_frm_api_tokens.php" data-title="Gestor Tokens API">
                <div class="card-top">
                    <div class="card-icon"><i class="fa-solid fa-key"></i></div>
                    <div class="card-meta">
                        <span>Seguridad & API</span>
                        <h3>Tokens de Acceso</h3>
                    </div>
                </div>
                <div class="card-desc">Administra, genera y revoca credenciales de API para integración con ERP Locator.</div>
                <div class="card-action"><span>Abrir Gestor</span><i class="fa-solid fa-arrow-right"></i></div>
            </a>

            <!-- Documentación Swagger -->
            <a class="module-card card-docs action-link" href="/v1/docs" data-title="Documentación API">
                <div class="card-top">
                    <div class="card-icon"><i class="fa-solid fa-book-bookmark"></i></div>
                    <div class="card-meta">
                        <span>OpenAPI 3.0</span>
                        <h3>Documentación Swagger</h3>
                    </div>
                </div>
                <div class="card-desc">Explorador interactivo de endpoints, schemas y pruebas directas de la API REST.</div>
                <div class="card-action"><span>Ver Documentación</span><i class="fa-solid fa-arrow-right"></i></div>
            </a>

            <!-- Clientes & Contactos -->
            <a class="module-card card-clientes action-link" href="/tesoreria/FRONT/tes_alt_cliente_1.0.php" data-title="Directorio de Contactos">
                <div class="card-top">
                    <div class="card-icon"><i class="fa-solid fa-address-book"></i></div>
                    <div class="card-meta">
                        <span>Tesorería</span>
                        <h3>Clientes y Contactos</h3>
                    </div>
                </div>
                <div class="card-desc">Registro y mantenimiento de directorio de clientes, personas y contactos operativos.</div>
                <div class="card-action"><span>Administrar Contactos</span><i class="fa-solid fa-arrow-right"></i></div>
            </a>

            <!-- Módulo Relavera / Minas / Campo -->
            <a class="module-card card-mineria action-link" href="/relavera/FRONT/man_tec_camp_1.0.php" data-title="Operaciones Relavera">
                <div class="card-top">
                    <div class="card-icon"><i class="fa-solid fa-mountain-sun"></i></div>
                    <div class="card-meta">
                        <span>Minería & Plantas</span>
                        <h3>Técnico de Campo</h3>
                    </div>
                </div>
                <div class="card-desc">Control de plantas de beneficio, viajes de relaves, choferes y despacho en campo.</div>
                <div class="card-action"><span>Abrir Operaciones</span><i class="fa-solid fa-arrow-right"></i></div>
            </a>

            <!-- Registrar Ventas -->
            <a class="module-card card-ventas action-link" href="/facturacion/FRONT/fac_alt_fac_ven_3.2.php" data-title="Registro de Ventas">
                <div class="card-top">
                    <div class="card-icon"><i class="fa-solid fa-receipt"></i></div>
                    <div class="card-meta">
                        <span>Facturación</span>
                        <h3>Registro de Ventas</h3>
                    </div>
                </div>
                <div class="card-desc">Emisión y facturación de servicios, ventas directas y control comercial.</div>
                <div class="card-action"><span>Nueva Venta</span><i class="fa-solid fa-arrow-right"></i></div>
            </a>

            <!-- Comprobantes SRI -->
            <a class="module-card card-sri action-link" href="/facturacion/FRONT/fac_alt_aut_sri_1.php" data-title="Comprobantes SRI">
                <div class="card-top">
                    <div class="card-icon"><i class="fa-solid fa-file-shield"></i></div>
                    <div class="card-meta">
                        <span>Facturación Electrónica</span>
                        <h3>Documentos SRI</h3>
                    </div>
                </div>
                <div class="card-desc">Consulta de comprobantes autorizados, firmas digitales y envíos al SRI.</div>
                <div class="card-action"><span>Consultar SRI</span><i class="fa-solid fa-arrow-right"></i></div>
            </a>

            <!-- Contabilidad & Autorizaciones -->
            <a class="module-card card-contab action-link" href="/contabilidad/FRONT/con_alt_autorizaciusu_2.0.php" data-title="Autorizaciones">
                <div class="card-top">
                    <div class="card-icon"><i class="fa-solid fa-user-check"></i></div>
                    <div class="card-meta">
                        <span>Contabilidad</span>
                        <h3>Autorizaciones</h3>
                    </div>
                </div>
                <div class="card-desc">Aprobación de transacciones contables, roles de usuario y cierres de período.</div>
                <div class="card-action"><span>Gestionar</span><i class="fa-solid fa-arrow-right"></i></div>
            </a>
        </div>
    </div>

    <!-- Integración con pestañas padre del ERP -->
    <script>
        $(document).on('click', '.action-link', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const title = $(this).data('title') || $(this).find('h3').text() || 'Módulo';
            
            if (window.parent && typeof window.parent.abrirFormularioEnTab === 'function') {
                window.parent.abrirFormularioEnTab(title, url);
            } else {
                window.location.href = url;
            }
        });
    </script>
</body>
</html>
