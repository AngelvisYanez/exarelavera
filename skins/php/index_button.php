<?php
// Iniciar sesión si no está iniciada
if (!isset($_SESSION)) {
    session_start();
}

// Verificar si el usuario tiene el perfil "Tecnico" y está en la empresa 620
$mostrarBotonTecnico = false;

if (isset($_SESSION['Ses_Per_Des']) && isset($_SESSION['Ses_Emp_Cod'])) {
    // Verificar si alguno de los perfiles del usuario contiene "Tecnico"
    $perfiles = $_SESSION['Ses_Per_Des'];
    $esTecnico = false;
    
    if (is_array($perfiles)) {
        foreach ($perfiles as $perfil) {
            if (stripos($perfil, 'Tecnico') !== false) {
                $esTecnico = true;
                break;
            }
        }
    }
    
    // Verificar si la empresa es 620
    $esEmpresa620 = ($_SESSION['Ses_Emp_Cod'] == 620);
    
    // Mostrar botón técnico solo si cumple ambas condiciones
    $mostrarBotonTecnico = $esTecnico && $esEmpresa620;
}
?>
<html>
<head>
<title>ACERCA DE EXA</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!-- <link rel="stylesheet" href="../../skins/css/bootstrap.min.css" /> -->
<link rel="stylesheet" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/tooltip.min.css" />
<link rel="stylesheet" href="../../framework/plugins/fonts/font-awesome/font-awesome-4.4.0/css/font-awesome.min.css" />
<link rel="stylesheet" href="../../skins/fonts/fontelo/fontello.css?x=0" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
<!-- text fonts -->
<link rel="stylesheet" href="../../skins/css/ace-fonts.css" />
<!-- exa styles -->
<link id="pagestyle" href="../../skins/css/exa3.css" rel="stylesheet" />
<script type="text/javascript">
    window.jQuery || document.write("<script src='../../skins/js/jquery.js'>" + "<" + "/script>");
</script><!-- <![endif]-->
</head>
<!-- <body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" style="text-align:center; background: url('../img/Fondo-Exa-1.gif') no-repeat;background-size: cover;"> -->
    <body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" class="home-body">
    <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 text-center home-welcome-header" style="display: flex; align-items: center; justify-content: center; margin-top: 40px;">
        <img src="../../imagenes/ingresar/favicon.png" alt="" class="img-fluid" width="50" style="margin-top: -12px; margin-right: 12px;">
        <h1 class="mb-3 home-welcome-title" style="margin: 0; font-size: clamp(1.25rem, 4vw, 2.5rem); line-height: 1.2;">
            Sistema Integral de Gesti&oacute;n Operativa y Contable
        </h1>
    </div>

    <style>
        .home-body {
            text-align: center;
            background: url('../../mascaras/model1/img/logo/backgroundHome.png') no-repeat;
            background-size: cover;
            background-position: center top;
            background-attachment: fixed;
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }
        @media (max-width: 768px) {
            .home-body {
                background-position: center 20% !important;
                background-attachment: scroll;
                background-size: contain !important;
                background-repeat: no-repeat !important;
                background-color: #f0f4f8;
            }
            .custom-btn {
                width: 80% !important;
                margin: 0 auto 12px auto !important;
            }
        }
        /* Título: sans geométrica en negrita (estilo similar a piezas promocionales) + azul #1766af */
        .home-welcome-title {
            color: #1766af !important;
            font-family: 'Montserrat', 'Segoe UI', 'Helvetica Neue', Arial, sans-serif !important;
            font-weight: 800 !important;
            letter-spacing: 0.03em;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.14);
        }
        .custom-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 350px;
            padding: 14px 0;
            background: #e3e3e3;
            color: #000000 !important;
            border: none;
            border-radius: 8px;
            font-size: 1.15rem;
            font-family: 'Segoe UI', 'Arial', sans-serif;
            font-weight: 600;
            box-shadow: 0 4px 16px rgba(78, 84, 200, 0.15);
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            margin: 0 10px 16px 10px;
            max-width: 98vw;
        }
        .custom-btn:hover {
            background: linear-gradient(90deg, #2746a2 0%, #12188e 100%);
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 24px rgba(78, 84, 200, 0.22);
            color: #fff !important;
            text-decoration: none;
        }
        .custom-btn i {
            margin-right: 8px;
            font-size: 1.2em;
        }
        @media (max-width: 900px) {
            .col-xl-12.text-center {
                flex-direction: column !important;
                align-items: center !important;
            }
            .col-xl-12.text-center h1 {
                font-size: 28px !important;
                margin-top: 10px;
            }
        }
        @media (max-width: 600px) {
            .row.menu-btns {
                flex-direction: column !important;
                align-items: center !important;
                gap: 0 !important;
            }
            .custom-btn {
                width: 90vw;
                min-width: 180px;
                max-width: 98vw;
                font-size: 1rem;
                margin: 0 0 14px 0;
            }
            .col-xl-12.text-center h1 {
                font-size: 20px !important;
            }
            .col-xl-12.text-center img {
                margin-right: 0 !important;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="row menu-btns" style="display: flex; justify-content: center; margin-top: 40px; gap: 28px; flex-wrap: wrap;">
            <script>
                $(document).on('click', '.menu-link', function(e) {
                    e.preventDefault();
                    const url = $(this).data('url') || $(this).attr('href');
                    $.get(url, function(response) {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(response, 'text/html');
                        const pageTitle = doc.querySelector('title')?.textContent || 'Sin título';
                        abrirFormularioEnTab(pageTitle, url);
                    });
                });

                function abrirFormularioEnTab(titulo, url) {
                    const tabId = 'tab_' + btoa(url).replace(/=/g, '');
                    if (document.getElementById(tabId)) {
                        activarTab(tabId);
                        return;
                    }
                    const li = document.createElement('li');
                    li.className = 'nav-item';
                    li.innerHTML = `<a style="border-radius: 3px !important;margin: 0 1px;padding: 1px 3px;background:#f8f8f8;border:1px solid #8db2e3;font-size:11px"  id="btnisactive"  class="nav-link d-flex align-items-center justify-content-between active" href="#" 
                    onclick="activarTab('${tabId}')"> <i class="glyphicon glyphicon-modal-window"></i>   <span style="color:#585858;">
                    </i>  ${titulo}</span> <i class="fa fa-times ms-2 text-primary" onclick="cerrarTab('${tabId}'); 
                    event.stopPropagation();" style="cursor:pointer;font-size: 10px; border:0px solid; padding: 2px; border-radius: 3px;  color:#999999; background: #f8f8f8;"></i></a>`;
                    document.getElementById('tabs').appendChild(li);
                    const iframe = document.createElement('iframe');
                    iframe.id = tabId;
                    iframe.src = url;
                    iframe.style = 'width:100%; height:700px; border:none; display:none;';
                    document.getElementById('iframes').appendChild(iframe);
                    activarTab(tabId);
                }

                function activarTab(tabId) {
                    document.querySelectorAll('#iframes iframe').forEach(el => el.style.display = 'none');
                    document.querySelectorAll('#tabs .nav-link').forEach(el => el.classList.remove('active'));
                    const iframe = document.getElementById(tabId);
                    if (iframe) iframe.style.display = 'block';
                    const tabs = document.querySelectorAll('#tabs .nav-link');
                    tabs.forEach(tab => {
                        if (tab.getAttribute('onclick')?.includes(tabId)) {
                            tab.classList.add('active');
                        }
                    });
                }

                function cerrarTab(tabId) {
                    document.getElementById(tabId)?.remove();
                    const tabLi = [...document.querySelectorAll('#tabs li')].find(li => li.innerHTML.includes(tabId));
                    if (tabLi) tabLi.remove();
                    const iframes = document.querySelectorAll('#iframes iframe');
                    if (iframes.length) activarTab(iframes[iframes.length - 1].id);
                }
            </script>

            <?php if ($mostrarBotonTecnico): ?>
                <!-- Botón para Técnico de Campo -->
                <div class="col-xl-3 col-sm-6 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                    <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="/relavera/FRONT/man_tec_camp_1.0.php">
                        <i class="fa fa-user text-primary"></i> Tecnico Campo
                    </a>
                </div>
            <?php else: ?>
                <!-- Botones estándar -->
                <div class="col-xl-3 col-sm-5 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                    <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="/facturacion/FRONT/fac_alt_fac_ven_3.2.php" id="ventas-link">
                        <i class="fa fa-credit-card text-primary"></i> Registrar Ventas
                    </a>
                </div>
                <div class="col-xl-3 col-sm-6 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                    <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="/tesoreria/FRONT/tes_alt_cliente_1.0.php">
                        <i class="fa fa-user text-primary"></i> Registrar Clientes
                    </a>
                </div>
                <div class="col-xl-3 col-sm-6 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                    <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="/contabilidad/FRONT/con_alt_autorizaciusu_2.0.php">
                        <i class="fa fa-archive text-primary"></i> Registrar Autorizaciones
                    </a>
                </div>
                <div class="col-xl-3 col-sm-6 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                    <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="../../facturacion/FRONT/fac_alt_aut_sri_1.php">
                        <i class="fa fa-send text-primary"></i>  Documentos Autorizados
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
