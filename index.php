<?php

/**
 * Descripción: Página de inicio del sistema
 * Fecha de actualización: 2012-11-25
 * Desarrollador: Lewis Chimarro
 * Fecha de actualización: 2013-08-12
 * Desarrollador: Lewis Chimarro
 */
require_once('Librerias/config.php/register_globals.php');
require_once('Librerias/procedimientos/almacenados_standar.php');
require_once('administrador/LOGICA/adm_log_login.php');

/**
 * Variable el tipo de navegador 
 */
$Browser = detectar_acceso();
/**
 * URL de acceso al sistema WAP 
 */
$wmlredirect = "../movil/FRONT/mov_pag_inicial.php"; // URL ABSOLUTO para su archivo VML  

if ($Browser == "WML") {
    header("Location: " . $wmlredirect);
    exit;
} //Fin del if($Browser == "WML") 
/* Ajax para identificar el numero de empresas */
if (isset($ajax_empresas2)) {
    /* Creacion del Objeto de conexion */
    $obBD_conexion = new Class_Log_Conexion_Log;
    /* Creacion del objeto mysql para las consultas */
    $obBD_con1 =  new Class_Log_Datos_Log;
    $rs_empresas = $obBD_con1->getArrayConsulta(1, trim($ajax_username), $obBD_conexion);
    utf8_encode_deep($rs_empresas);
    $conteo = count($rs_empresas);
    $html = '';
    if ($conteo > 0) {
        if ($conteo > 1) {
            $html = $html . "<option value=''></option>";
            foreach ($rs_empresas as $row_rs_empresas) {
                $sucBr = isset($row_rs_empresas['Suc_Cod']) ? (int) $row_rs_empresas['Suc_Cod'] : 0;
                $html = $html . "<option value='" . (int) $row_rs_empresas['Emp_Cod'] . "' data-Emp_Nom='" . ($row_rs_empresas['Emp_Nom']) . "' data-suc-cod='" . $sucBr . "'> " . ($row_rs_empresas['Emp_Cor']) . ' (' . ($row_rs_empresas['Suc_Des']) . ") </option>";
            }
        } else {
            $sucBr0 = isset($rs_empresas[0]['Suc_Cod']) ? (int) $rs_empresas[0]['Suc_Cod'] : 0;
            $html = $html . "<option value='" . (int) $rs_empresas[0]['Emp_Cod'] . "' selected='selected' data-Emp_Nom='" . ($rs_empresas[0]['Emp_Nom']) . "' data-suc-cod='" . $sucBr0 . "'>" . $rs_empresas[0]['Emp_Cor'] . "</option>";
        } //Fin del if ($total_rs_empresas > 1)
    }
    $obBD_conexion->cerrar();
    $res = array('success' => true, 'conteo' => $conteo, 'html' => $html);
    echo json_encode($res);
    exit();
} //Fin del if (isset($ajax_empresas))
// if (isset($_SESSION) && !(!isset($_SESSION['Ses_Lis_Per']) || !isset($_SESSION['Ses_Emp_Cod']) || !isset($_SESSION['Ses_Usu_Ced']))) header('Location: ' . './administrador/FRONT/home.php');

/* AJAX para cambio de contraseña obligatorio */
if (isset($ajax_change_pass)) {
    $obBD_conexion = new Class_Log_Conexion_Log($_SESSION['Ses_Dat_Dis']);
    $obBD_con1 = new Class_Log_Datos_Log;
    $res = array('success' => false);
    
    // Validar clave actual
    $check = $obBD_con1->getRowConsultaSql("SELECT COUNT(Usu_Cod) as contador FROM usuarios WHERE Usu_Pal = MD5('$old_pass') AND Usu_Cod = " . $_SESSION['Ses_Usu_Cod'], $obBD_conexion);
    
    if ($check['contador'] > 0) {
        $obBD_con1->inicio_transaccion($obBD_conexion);
        $obBD_con1->consulta("UPDATE usuarios SET Usu_Pal = MD5('$new_pass') WHERE Usu_Cod = " . $_SESSION['Ses_Usu_Cod'], $obBD_conexion);
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
            $res['success'] = true;
            $res['message'] = "¡Contraseña actualizada! Por favor, inicie sesión con su nueva clave.";
            @session_destroy();
        } else {
            $res['message'] = "Error interno al guardar los cambios.";
        }
    } else {
        $res['message'] = "La clave actual no es correcta.";
    }
    echo json_encode($res);
    exit();
}
$isDefaultPass = false;
if (isset($_SESSION) && !(!isset($_SESSION['Ses_Lis_Per']) || !isset($_SESSION['Ses_Emp_Cod']) || !isset($_SESSION['Ses_Usu_Ced']))) {
    // Verificación de seguridad: contraseña por defecto "123456"
    if (isset($_SESSION['Ses_Usu_Cod']) && isset($_SESSION['Ses_Dat_Dis'])) {
        $obBD_conexion_check = new Class_Log_Conexion_Log($_SESSION['Ses_Dat_Dis']);
        $obBD_con_check = new Class_Log_Datos_Log;
        $checkPass = $obBD_con_check->getRowConsultaSql("SELECT Usu_Cod FROM usuarios WHERE Usu_Cod = " . $_SESSION['Ses_Usu_Cod'] . " AND Usu_Pal = MD5('123456')", $obBD_conexion_check);
        if ($checkPass) {
            $isDefaultPass = true;
        }
    }
    
    if (!$isDefaultPass) {
        header('Location: ' . './administrador/FRONT/home.php');
        exit();
    }
}

$http_host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
$host_base = preg_replace('/:\d+$/', '', $http_host);
$es_localhost = in_array($host_base, array('localhost', '127.0.0.1', '::1'), true);
$relavera_por_host = (strpos($http_host, 'relavera.erpexa') !== false) || $es_localhost;
if (isset($_GET['portal']) && $_GET['portal'] === 'exa') {
    $es_portal_relavera = false;
} elseif (isset($_GET['portal']) && $_GET['portal'] === 'relavera') {
    $es_portal_relavera = true;
} else {
    $es_portal_relavera = $relavera_por_host;
}

$path_logo_rcet = __DIR__ . DIRECTORY_SEPARATOR . 'imagenes' . DIRECTORY_SEPARATOR . 'ingresar' . DIRECTORY_SEPARATOR . 'logo-rcet.png';
$tiene_logo_rcet = is_file($path_logo_rcet);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $es_portal_relavera ? 'RCET · Portal Relavera · Acceso' : 'EXA [Software Contable] - Iniciar Sesión'; ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="imagenes/ingresar/favicon.png" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Saira:wght@400;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS & Icons with Local Fallback -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" 
          onerror="this.onerror=null;this.href='framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap.min.css';">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="framework/plugins/fonts/font-awesome/font-awesome-4.4.0/css/font-awesome.min.css" />
    <!--Agregar items-->
    <link type="text/css" rel="stylesheet" href="./framework/plugins/animate/animate-3.4.0.min.css" />
    <link rel="stylesheet" href="./Librerias/tooltip/jquery.tooltip.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />

    <style>
        :root {
            --login-accent: <?php echo $es_portal_relavera ? '#1b7a4a' : '#A02525'; ?>;
            --login-accent-hover: <?php echo $es_portal_relavera ? '#145a36' : '#161616'; ?>;
            --login-footer-bg: <?php echo $es_portal_relavera ? '#0d1f14' : '#161616'; ?>;
        }

        body {
            color: #ffffff;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: url('/imagenes/ingresar/bg.png') no-repeat center center fixed;
            background-size: cover;
            background-color: #161616;
        }

        body.theme-relavera {
            background: linear-gradient(118deg, rgba(10, 42, 28, 0.60) 0%, rgba(6, 28, 18, 0.58) 45%, rgba(8, 35, 22, 0.59) 100%),
                url('/imagenes/ingresar/bg.png') no-repeat center center fixed;
            background-size: cover;
            overflow-x: hidden;
        }

        /* Evita scroll vertical: 100vh + padding del contenedor > ventana */
        body.theme-relavera .container.px-3.py-3.position-relative {
            padding-top: 0.65rem;
            padding-bottom: 0.65rem;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Saira', sans-serif;
        }

        .row {
            position: relative;
            z-index: 2;
            height: 100%;
        }

        .carousel-item {
            height: calc(100vh - 3.35rem);
            min-height: 300px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: transform 0.6s ease-in-out, opacity 0.6s ease-in-out;
        }

        @supports (height: 100svh) {
            .carousel-item {
                height: calc(100svh - 3.35rem);
            }
        }

        .theme-relavera .carousel-item {
            height: calc(100vh - 3.65rem);
            min-height: 280px;
        }

        @supports (height: 100svh) {
            .theme-relavera .carousel-item {
                height: calc(100svh - 3.65rem);
            }
        }

        @media (min-width: 769px) {
            body.theme-relavera .login-area-col.pb-5 {
                padding-bottom: 1rem !important;
            }
        }

        option {
            width: 100%;
        }

        .carousel-caption {
            background-color: transparent;
            padding: 20px;
            border-radius: 10px;
            position: absolute;
            right: 15%;
            bottom: 10rem;
            left: 15%;
            padding-top: 1.25rem;
            padding-bottom: 1.25rem;
            color: #fff;
            text-align: center;

        }

        .carousel-indicators button {
            background-color: #ffffff;
        }

        .carousel-indicators button.active {
            background-color: var(--login-accent);
        }

        .login-section {
            width: 100%;
            max-width: 328px;
            background: #ffffff;
            border-radius: 16px;
            padding: 0;
            color: #161616;
            box-shadow: 0 0 18px rgba(0, 0, 0, 0.09);
            overflow: visible;
        }

        .login-section.card .card-body {
            padding: 1.15rem 1rem 1.3rem;
            overflow: visible;
        }

        .theme-relavera .login-section.card .card-body {
            padding-top: 1.28rem;
            padding-bottom: 1.45rem;
        }

        .theme-relavera .login-section .mb-3 {
            margin-bottom: 0.72rem !important;
        }

        /* Select2 abierto en body: la tarjeta no debe mostrar scroll interno */
        #div_empresas {
            overflow: visible !important;
        }

        .select2-container--default.select2-container--open {
            z-index: 2050 !important;
        }

        .login-area-col {
            overflow: visible;
        }

        .login-section .form-control,
        .login-section .btn {
            border-radius: 9px !important;
            height: 40px !important;
            font-size: 0.9rem;
        }

        .login-section .form-control {
            padding-left: 38px !important;
            display: flex;
            align-items: center;
            /* border: 1px solid #A02525 !important;*/
        }

        .login-section .form-control-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--login-accent);
            font-size: 0.95rem;
        }

        .login-section h4 {
            font-size: 1.1rem;
        }

        .theme-relavera .login-section h4 {
            font-size: 1.32rem;
            font-weight: 700;
        }

        .login-section .mb-4 {
            margin-bottom: 0.75rem !important;
        }

        .login-section .mb-3 {
            margin-bottom: 0.6rem !important;
        }

        .login-section .small.text-secondary {
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }

        .theme-relavera .login-section .small.text-secondary {
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* .over {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }*/
        .over.desc {
            font-size: 10px !important;
        }

        .logo img {
            width: 178px;
            max-width: 100%;
        }

        .homepage-btn {
            position: absolute;
            top: 20px;
            left: 0;
            margin-left: -2rem;
            z-index: 10;
            background-color: var(--login-accent);
            border: none;
            border-radius: 50px;
            padding: 10px 15px;
            font-size: 16px;
            transition: background-color 0.3s ease-in-out;
        }

        .homepage-btn:hover {
            background-color: var(--login-accent-hover);
        }

        .theme-relavera .homepage-btn {
            padding: 6px 11px;
            font-size: 13px;
        }

        .theme-relavera .homepage-btn .bi {
            font-size: 0.95em;
        }

        .theme-relavera .homepage-dropdown .dropdown-menu {
            min-width: 10.5rem;
            padding: 0.3rem 0;
        }

        .theme-relavera .homepage-dropdown .dropdown-item {
            padding: 0.45rem 0.85rem;
            font-size: 0.82rem;
        }

        .homepage-btn-right {
            left: auto;
            right: 20px;
        }

        .homepage-dropdown {
            position: absolute;
            top: 20px;
            left: 0;
            margin-left: -2rem;
            z-index: 1050;
        }

        .homepage-dropdown .homepage-btn {
            position: static;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .homepage-dropdown .homepage-btn.dropdown-toggle::after {
            margin-left: 0.35rem;
        }

        .homepage-dropdown .dropdown-menu.homepage-fan-menu {
            border-radius: 14px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.16);
            border: 1px solid rgba(0, 0, 0, 0.06);
            min-width: 12.5rem;
            padding: 0.45rem 0;
            overflow: visible;
            transform-origin: top left;
        }

        .homepage-fan-menu li {
            list-style: none;
            transform-origin: 20% 0;
        }

        .homepage-fan-menu:not(.show) li .dropdown-item {
            opacity: 0;
            transform: rotate(-14deg) translate(-10px, -6px);
        }

        .homepage-fan-menu.show li .dropdown-item {
            opacity: 0;
            animation-duration: 0.48s;
            animation-timing-function: cubic-bezier(0.22, 1.1, 0.36, 1);
            animation-fill-mode: forwards;
        }

        .homepage-fan-menu.show li:nth-child(1) .dropdown-item {
            animation-name: homepageFanIn1;
            animation-delay: 0.02s;
        }

        .homepage-fan-menu.show li:nth-child(2) .dropdown-item {
            animation-name: homepageFanIn2;
            animation-delay: 0.1s;
        }

        @keyframes homepageFanIn1 {
            0% {
                opacity: 0;
                transform: rotate(-28deg) translate(-18px, -14px) scale(0.94);
            }
            100% {
                opacity: 1;
                transform: rotate(-4deg) translate(0, 0) scale(1);
            }
        }

        @keyframes homepageFanIn2 {
            0% {
                opacity: 0;
                transform: rotate(28deg) translate(18px, -14px) scale(0.94);
            }
            100% {
                opacity: 1;
                transform: rotate(4deg) translate(0, 0) scale(1);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .homepage-fan-menu:not(.show) li .dropdown-item,
            .homepage-fan-menu.show li .dropdown-item {
                animation: none !important;
                opacity: 1;
                transform: none;
                transition: none;
            }
        }

        .homepage-dropdown .dropdown-item {
            font-weight: 600;
            color: #1766af;
            padding: 0.55rem 1rem;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .homepage-dropdown .dropdown-item:hover,
        .homepage-dropdown .dropdown-item:focus {
            background-color: rgba(23, 102, 175, 0.1);
            color: #145a94;
        }

        .btn-primary {
            background-color: var(--login-accent);
            border: none;
            height: 40px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease-in-out;
        }

        .btn-primary:hover {
            background-color: var(--login-accent-hover);
        }

        .carousel-indicators {
            bottom: 50px;
        }

        footer {
            text-align: center;
            padding: 4px 10px;
            line-height: 1.25;
            background-color: var(--login-footer-bg);
            color: #ffffff;
            font-size: 12px;
            position: fixed;
            bottom: 0;
            width: 100%;
            z-index: 2;
        }

        .text-rojo {
            color: var(--login-accent);
        }

        .theme-relavera .carousel-caption-relavera {
            max-width: 420px;
            margin: 0 auto;
            bottom: 9.75rem;
        }

        .theme-relavera #loginCarousel .carousel-indicators {
            bottom: 2rem;
        }

        .theme-relavera .carousel-caption-relavera h2 {
            font-family: 'Saira', sans-serif;
            font-weight: 700;
            font-size: clamp(1.1rem, 2.4vw, 1.65rem);
            line-height: 1.2;
            color: #fff;
            text-shadow: 0 0 1px #0a2a1c, 0 2px 0 #0d3d28, 0 0 24px rgba(46, 204, 113, 0.35);
            margin-bottom: 0.75rem;
        }

        .theme-relavera .carousel-caption-relavera p {
            font-size: clamp(0.82rem, 1.5vw, 0.95rem);
            color: rgba(255, 255, 255, 0.92);
            margin: 0;
            line-height: 1.45;
        }

        .theme-relavera .login-section {
            border: 1px solid rgba(27, 122, 74, 0.25);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
        }

        .theme-relavera .portal-relavera-badge {
            font-size: 0.82rem;
            letter-spacing: 0.11em;
            text-transform: uppercase;
            color: var(--login-accent);
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .theme-relavera .portal-relavera-title {
            font-family: 'Saira', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            color: #1a3326;
            line-height: 1.3;
            margin-bottom: 0.25rem;
        }

        .theme-relavera .portal-relavera-sub {
            font-size: 0.8rem;
            color: #5c6b63;
            margin-bottom: 1rem;
        }

        .theme-relavera .logo-relavera-stack {
            margin-bottom: 0.65rem !important;
        }

        .theme-relavera .logo-relavera-principal {
            max-width: min(100%, 236px);
            width: 100%;
            height: auto;
            display: block;
            margin: 0 auto 0.25rem;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            transform: translateZ(0);
        }

        .theme-relavera .logo-rcet-fallback {
            max-width: min(100%, 236px);
            margin: 0 auto 0.35rem;
            padding: 1rem 1.1rem 1.15rem;
            border-radius: 14px;
            background: linear-gradient(145deg, #1b7a4a 0%, #145a36 55%, #0f4a2d 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
            color: #fff;
            text-align: center;
        }

        .theme-relavera .logo-rcet-fallback strong {
            font-family: 'Saira', sans-serif;
            font-size: 1.12rem;
            letter-spacing: 0.06em;
            display: block;
            line-height: 1.2;
        }

        .theme-relavera .logo-rcet-fallback span {
            font-size: 0.72rem;
            opacity: 0.95;
            display: block;
            margin-top: 0.35rem;
            line-height: 1.35;
        }

        .theme-relavera .login-exa-fuera {
            margin-top: 0.7rem;
            padding: 0.4rem 0.75rem 0.5rem;
            max-width: 328px;
            width: 100%;
            background: rgba(255, 255, 255, 0.94);
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .theme-relavera .logo-exa-label {
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #8a9a91;
            display: block;
            margin-bottom: 0.35rem;
        }

        .theme-relavera .logo-exa-secundario {
            max-width: 92px;
            width: 100%;
            height: auto;
            opacity: 0.95;
        }

        .aviso-flotante {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 20px;
            z-index: 1000;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            display: none;
            /* Ocultar inicialmente el aviso */
        }

        /* Estilos para el botón de cerrar */
        .cerrar-aviso {
            position: absolute;
            top: 5px;
            right: 5px;
            cursor: pointer;
            font-size: 20px;
            color: white;
            background: red;
            border-radius: 100%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Estilos para la imagen dentro del aviso */
        .aviso-flotante img {
            width: 600px;
            height: auto;
        }

        @media (max-width: 768px) {
            .carousel-item {
                height: 50vh;
                min-height: 300px;
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                transition: transform 0.6s ease-in-out, opacity 0.6s ease-in-out;
            }

            .carousel-caption {
                padding: 5px;
                padding: 20px;
                position: absolute;
                right: 15%;
                bottom: 5rem;
                left: 15%;
                padding-top: 1.25rem;
                padding-bottom: 1.25rem;
                color: #fff;
                text-align: center;

            }

            .login-section {
                margin-top: 50px;
            }

            .carousel {
                margin-top: 20px;
            }

            .homepage-btn {
                position: static;
                display: block;
                margin: 0 auto 20px;
                width: fit-content;
            }

            .homepage-dropdown {
                position: static;
                display: block;
                margin: 0 auto 20px;
                width: fit-content;
            }

            .homepage-dropdown .homepage-btn {
                margin-left: auto;
                margin-right: auto;
            }

            .aviso-flotante img {
                width: 325px;
            }
        }

        /* Estilos para personalizar Select2 */
        .select2-container--default .select2-selection--single {
            padding-left: 38px !important;
            border-radius: 9px !important;
            height: 40px !important;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        /* Borde y sombra celeste al estar enfocado o seleccionado */
        .select2-container--default .select2-selection--single:focus,
        .select2-container--default .select2-selection--single.select2-selection--focus,
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color:rgba(34, 144, 223, 0.71) !important;
            box-shadow: 0 0 0 4px rgba(28, 156, 253, 0.34) !important;
            outline: none !important;
        }

        .select2-container--open .select2-dropdown--below {
            background-color: #ffffff;
            color: #161616;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 32px;
            padding-top: 4px;
            padding-left: 6px;
            font-size: 0.9rem;
            color: #161616 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 32px;
        }

        .select2-container--default .select2-results__option--highlighted {
            background-color: #0d6efd;
            color: #fff !important;
        }

        /* Dropdown en body: no heredar color blanco del body */
        .select2-dropdown.select2-rcet-empresa {
            z-index: 2051 !important;
            color: #161616;
            background-color: #fff;
        }

        /* Lista de empresas al buscar (dropdown en body, clase select2-rcet-empresa) */
        .select2-dropdown.select2-rcet-empresa .select2-search__field {
            font-size: 0.85rem !important;
            color: #161616 !important;
        }

        .select2-dropdown.select2-rcet-empresa .select2-results__option {
            padding: 0.4rem 0.65rem;
            font-size: 0.8rem;
            line-height: 1.3;
            color: #161616 !important;
        }

        .select2-dropdown.select2-rcet-empresa .select2-option-title {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            line-height: 1.25;
            text-transform: none;
            letter-spacing: normal;
            color: #161616 !important;
        }

        .select2-dropdown.select2-rcet-empresa .select2-results__option--highlighted {
            background-color: #0d6efd !important;
            color: #fff !important;
        }

        .select2-dropdown.select2-rcet-empresa .select2-results__option--highlighted .select2-option-title {
            color: #fff !important;
        }

        .select2-dropdown.select2-rcet-empresa .select2-results__option--highlighted .text-muted {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        /* Estilo para la descripción de las opciones */
        .select2-results__option .text-muted {
            font-size: 0.7rem;
            color: rgb(99, 99, 99);
            display: block;
            margin-top: 2px;
            line-height: 1.2;
            font-weight: 400;
        }

    </style>
</head>

<body class="<?php echo $es_portal_relavera ? 'theme-relavera' : ''; ?>">
    <div class="container px-3 py-3 position-relative">
        <?php if ($es_portal_relavera) { ?>
        <div class="dropdown homepage-dropdown">
            <button class="btn homepage-btn text-white dropdown-toggle" type="button" id="homepagePortalDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true">
                <i class="bi bi-house-door-fill" aria-hidden="true"></i> Home Page
            </button>
            <ul class="dropdown-menu homepage-fan-menu" aria-labelledby="homepagePortalDropdown" data-bs-popper="static">
                <li>
                    <a class="dropdown-item" href="https://exacontable.com" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-house-door-fill me-2" aria-hidden="true"></i>EXA Contable
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="https://rcet.ec" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-house-door-fill me-2" aria-hidden="true"></i>Sitio RCET
                    </a>
                </li>
            </ul>
        </div>
        <?php } else { ?>
        <a href="https://exacontable.com" target="_blank" rel="noopener noreferrer" class="btn homepage-btn text-white">
            <i class="bi bi-house-door-fill"></i> Homepage
        </a>
        <?php } ?>
        <div class="row">
            <!-- Carousel Section -->
            <div class="col-md-6 col-lg-7 d-flex align-items-center justify-content-center order-md-1 order-2 p-0">
                <?php
                // Cargar flayers personalizados
                $flayersFile = 'administrador/config/login_flayers.json';
                $customFlayers = array();
                if (file_exists($flayersFile)) {
                    $allFlayers = json_decode(file_get_contents($flayersFile), true);
                    if (is_array($allFlayers)) {
                        foreach ($allFlayers as $f) {
                            if (isset($f['activo']) && $f['activo']) {
                                $customFlayers[] = $f;
                            }
                        }
                    }
                }
                ?>
                <div id="loginCarousel" class="carousel slide w-100" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php 
                        // Prioridad a los flayers personalizados
                        if (!empty($customFlayers)) { 
                            foreach ($customFlayers as $idx => $flayer) { 
                                $imgNum = isset($flayer['imagen']) ? $flayer['imagen'] : '1';
                                $imgSrc = !empty($flayer['ruta_imagen']) ? $flayer['ruta_imagen'] : ($imgNum !== '0' ? 'imagenes/ingresar/' . $imgNum . '.png' : '');
                                ?>
                            <div class="carousel-item <?php echo $idx === 0 ? 'active' : ''; ?>">
                                
                                <?php 
                                // Detección de contenido
                                $hasText = !empty($flayer['titulo']) || !empty($flayer['descripcion']);
                                // Si no hay texto, permitimos que la imagen sea mucho más grande
                                $maxImgHeight = $hasText ? ($es_portal_relavera ? '200px' : '240px') : '80vh';
                                ?>

                                <?php if ($es_portal_relavera) { ?>
                                    <!-- Estilo Relavera -->
                                    <div class="carousel-caption carousel-caption-relavera d-flex flex-column align-items-center justify-content-center text-center" style="top: 0; bottom: 0; height: 100%; left: 5%; right: 5%; padding: 0;">
                                        <div class="content-wrapper" style="max-width: 90%; width: 100%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word;">
                                            <?php if (!empty($flayer['titulo'])) { ?>
                                                <h2 style="margin-bottom: 5px; font-size: 1.8rem;"><?php echo $flayer['titulo']; ?></h2>
                                            <?php } ?>
                                            <?php if (!empty($flayer['descripcion'])) { ?>
                                                <p style="margin-bottom: 15px; font-size: 1rem; line-height: 1.3;"><?php echo $flayer['descripcion']; ?></p>
                                            <?php } ?>

                                            <!-- Imagen Personalizada o Predefinida -->
                                            <?php if (!empty($imgSrc)) { ?>
                                                <div style="perspective: 1000px;">
                                                    <img src="<?php echo $imgSrc; ?>" alt="" class="img-fluid animate__animated animate__zoomIn" 
                                                        style="max-height: <?php echo $maxImgHeight; ?>; width: auto; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1);">
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <!-- Estilo EXA -->
                                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center" style="top: 0; bottom: 0; height: 100%; left: 10%; right: 10%; padding: 0;">
                                        <div class="content-wrapper" style="max-width: 85%; width: 100%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word;">
                                            <?php if (!empty($flayer['titulo'])) { ?>
                                                <h1 class="fw-bold animate__animated animate__fadeInDown" style="font-size: 2.2rem; color: #fff; margin-bottom: 5px; text-shadow: 0 2px 10px rgba(0,0,0,0.5);"><?php echo $flayer['titulo']; ?></h1>
                                            <?php } ?>
                                            <?php if (!empty($flayer['descripcion'])) { ?>
                                                <p class="fs-5 animate__animated animate__fadeIn" style="color: #eee; margin-bottom: 20px; text-shadow: 0 2px 5px rgba(0,0,0,0.5); line-height: 1.3;"><?php echo $flayer['descripcion']; ?></p>
                                            <?php } ?>

                                            <?php if (!empty($imgSrc)) { ?>
                                                <img src="<?php echo $imgSrc; ?>" alt="" class="img-fluid animate__animated animate__fadeInUp" 
                                                    style="max-height: <?php echo $maxImgHeight; ?>; width: auto; border-radius: 15px; box-shadow: 0 15px 45px rgba(0,0,0,0.7); border: 2px solid rgba(255,255,255,0.2);">
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } 
                        } else if ($es_portal_relavera) { ?>
                            <!-- Fallback Relavera Estático -->
                            <div class="carousel-item active">
                                <div class="carousel-caption carousel-caption-relavera d-flex flex-column align-items-center justify-content-center text-center" style="height:60%;">
                                    <h2>Disposici&oacute;n final autorizada</h2>
                                    <p>Gesti&oacute;n de relaves alineada con la normativa ambiental y el control de las autoridades.</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <div class="carousel-caption carousel-caption-relavera d-flex flex-column align-items-center justify-content-center text-center" style="height:60%;">
                                    <h2>Certificados y trazabilidad</h2>
                                    <p>Portal para clientes: consulte documentaci&oacute;n y el seguimiento de sus entregas de forma segura.</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <div class="carousel-caption carousel-caption-relavera d-flex flex-column align-items-center justify-content-center text-center" style="height:60%;">
                                    <h2>Relavera Comunitaria El Tabl&oacute;n</h2>
                                    <p>Soluci&oacute;n autorizada para el distrito minero Zaruma &ndash; Portovelo. Operaci&oacute;n bajo licencia ambiental.</p>
                                </div>
                            </div>
                        <?php } else { ?>
                            <!-- Fallback EXA Estático -->
                            <div class="carousel-item active">
                                <div class="carousel-caption d-flex flex-column align-items-center justify-content-center" style="height:60%;">
                                    <img src="imagenes/ingresar/1.png" alt="" class="img-fluid" style="max-width: 100%; max-height: 400px; display: block; margin: 0 auto;">
                                </div>
                            </div>
                            <div class="carousel-item">
                                <div class="carousel-caption d-flex flex-column align-items-center justify-content-center" style="height:60%;">
                                    <img src="imagenes/ingresar/2.png" alt="" class="img-fluid" style="max-width: 100%; max-height: 400px; display: block; margin: 0 auto;">
                                </div>
                            </div>
                            <div class="carousel-item">
                                <div class="carousel-caption d-flex flex-column align-items-center justify-content-center" style="height:60%;">
                                    <img src="imagenes/ingresar/3.png" alt="" class="img-fluid" style="max-width: 100%; max-height: 400px; display: block; margin: 0 auto;">
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#loginCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#loginCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <div class="carousel-indicators">
                        <?php 
                        $totalFlayers = !empty($customFlayers) ? count($customFlayers) : 3;
                        for ($i = 0; $i < $totalFlayers; $i++) { ?>
                            <button type="button" data-bs-target="#loginCarousel" data-bs-slide-to="<?php echo $i; ?>" 
                                    class="<?php echo $i === 0 ? 'active' : ''; ?>" 
                                    aria-current="<?php echo $i === 0 ? 'true' : 'false'; ?>" 
                                    aria-label="Slide <?php echo $i + 1; ?>"></button>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Login Section -->
            <!--div class="col-md-6 col-lg-5 d-flex align-items-center justify-content-center order-md-2 order-1"-->
            <div class="col-md-6 col-lg-5 d-flex flex-column align-items-center justify-content-center order-md-2 order-1 pb-5 login-area-col">
                <div class="login-section card">
                    <div class="card-body">
                        <?php if ($es_portal_relavera) { ?>
                        <div class="logo-relavera-stack text-center mb-4">
                            <div class="portal-relavera-badge mb-2">Portal operativo</div>
                            <?php if ($tiene_logo_rcet) { ?>
                            <img src="imagenes/ingresar/logo-rcet.png" alt="RCET &middot; Relavera Comunitaria El Tabl&oacute;n" class="logo-relavera-principal img-fluid">
                            <?php } else { ?>
                            <div class="logo-rcet-fallback" aria-hidden="true">
                                <strong>RCET</strong>
                                <span>Relavera Comunitaria El Tabl&oacute;n<br>Proyecto ambiental asociativo</span>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } else { ?>
                        <div class="text-center logo mb-4">
                            <img src="imagenes/ingresar/logo.png" alt="EXA Logo" class="img-fluid">
                        </div>
                        <?php } ?>
                        <h4 class="text-center mb-4 fw-bold"><?php echo $es_portal_relavera ? 'Iniciar sesi&oacute;n' : 'Iniciar Sesión'; ?></h4>
                        <p class="small text-secondary">Inicie sesi&oacute;n con su cuenta registrada.</p>
                        <form action="administrador/FRONT/adm_con_control_1.2.php" method="post" name="acceso" id="acceso">
                            <div class="login-fields">
                                <div class="form-group position-relative mb-3">
                                    <input class="form-control" type="text" id="user_name" name="user_name" value="" placeholder="Usuario" class="login username-field"
                                        onBlur="if(trim(this.value) !== ''){ loadEmp(this.value); }" />
                                    <i class="bi bi-person-fill form-control-icon"></i>
                                </div>

                                <div class="form-group position-relative mb-3">
                                    <input class="form-control" type="password" id="password" name="password" value="" placeholder="Contraseña" class="login password-field" oncontextmenu="return false"
                                        onKeyPress="if (event.keyCode===13){ var o=document.querySelector('#Emp_Cod option:checked'); document.getElementById('Suc_Cod').value=o? (o.getAttribute('data-suc-cod')||''):''; document.getElementById('encryptor').value = md5(document.getElementById('password').value); this.form.submit();}else{return  validar_injections(event);}" />
                                    <i class="bi bi-lock-fill form-control-icon"></i>
                                </div>
                                <div class="form-group position-relative mb-3" id="div_empresas" style="display: none;">
                                    <select class="form-select form-control" name="Emp_Cod" id="Emp_Cod" data-placeholder="Seleccione Empresa...">
                                        <option selected></option>
                                    </select>
                                    <i class="bi bi-building form-control-icon"></i>
                                </div>
                            </div>

                            <!-- /login-fields -->
                            <div class="form-group login-actions">
                                <input type="hidden" name="encryptor" id="encryptor" />
                                <input type="hidden" name="Suc_Cod" id="Suc_Cod" />
                                <button class="btn btn-primary w-100" type="button"
                                    onClick="handleLogin();">
                                    Entrar <i class="bi bi-box-arrow-in-right"></i>
                                </button>
                            </div>
                            <?php if ((isset($_GET["errorusuario"]) && $_GET["errorusuario"] == "si") || (isset($_GET["errorsistema"]) && $_GET["errorsistema"] == "si") || (isset($_GET["errordispositivo"]) && $_GET["errordispositivo"] == "si")) {
                                $errorMsg = 'Error del Sistema';
                                if (isset($_GET["errorusuario"])) $errorMsg = 'Datos incorrectos';
                                if (isset($_GET["errordispositivo"])) $errorMsg = 'Acceso denegado: dispositivo no autorizado. Este usuario solo puede ingresar desde equipos previamente registrados.';
                                echo '<div class="alert alert-danger mt-3 py-2 small text-center" style="border-radius: 8px;"><span>' . $errorMsg . '</span></div>';
                            } ?>
                        </form>
                    </div>
                </div>

                <!-- Segundo Ambiente: Cambio de Contraseña -->
                <div class="login-section card animate__animated animate__fadeIn" id="change-pass-section" style="display: none;">
                    <div class="card-body">
                        <div class="logo-relavera-stack text-center mb-4">
                            <div class="portal-relavera-badge mb-2">Seguridad de cuenta</div>
                            <h4 class="fw-bold" style="color: var(--login-accent);">Nueva Contraseña</h4>
                        </div>
                        <p class="small text-secondary mb-4">Por favor, actualice sus credenciales para continuar de forma segura.</p>
                        
                        <div id="change-pass-alert"></div>

                        <form id="form-change-pass">
                            <div class="form-group position-relative mb-3">
                                <input class="form-control" type="password" id="old_pass" placeholder="Clave Actual" required />
                                <i class="bi bi-key-fill form-control-icon"></i>
                            </div>
                            <div class="form-group position-relative mb-3">
                                <input class="form-control" type="password" id="new_pass" placeholder="Nueva Clave" required />
                                <i class="bi bi-shield-lock-fill form-control-icon"></i>
                            </div>
                            <div class="form-group position-relative mb-3">
                                <input class="form-control" type="password" id="conf_pass" placeholder="Confirmar Clave" required />
                                <i class="bi bi-check-circle-fill form-control-icon"></i>
                            </div>
                            
                            <div class="login-actions mt-4">
                                <button class="btn btn-primary w-100" type="button" id="btnSavePass" onclick="saveNewPass()">
                                    Actualizar Contraseña <i class="bi bi-save-fill ms-1"></i>
                                </button>
                                <button class="btn btn-link w-100 mt-2 text-decoration-none small" type="button" onclick="location.href='administrador/LOGICA/logout.php'" style="color: #666;">
                                    Cancelar y Salir
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($es_portal_relavera) { ?>
                <div class="login-exa-fuera text-center">
                    <span class="logo-exa-label">Plataforma</span>
                    <img src="imagenes/ingresar/logo.png" alt="EXA" class="logo-exa-secundario img-fluid mx-auto d-block">
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div align="center" style="text-align:center" class=""><span class="span6"> &copy; <?php date_default_timezone_set('UTC');
                                                                                            echo date("Y"); ?>. <?php echo $es_portal_relavera ? 'RCET · Relavera Comunitaria El Tabl&oacute;n · Plataforma EXA' : 'EXA Sistema Contable - Todos los derechos reservados'; ?>.</span></div>
    </footer>

    <!-- Bootstrap & jQuery JS with Local Fallback -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>window.jQuery || document.write('<script src="skins/js/jquery.js"><\/script>')</script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>window.bootstrap || document.write('<script src="mascaras/model1/js/bootstrap.min.js"><\/script>')</script>

    <script src="mascaras/model1/js/libs/modernizr-2.5.3.min.js"></script>
    <script src="framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script src="mascaras/model2/js/signin.js"></script>
    <script language="javascript" src="Librerias/validaciones/validacion.js"></script>
    
    <!-- Select2 CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <script type="text/javascript">
        // metodos funcionales pero con el limitante de chosen
        // $(document).ready(function() {
        //     $('#browser *').tooltip({
        //         showURL: false
        //     });
        //     $('#Emp_Cod').chosenDesc({
        //         width: '100%',
        //         template: function(t, d) {
        //             return '<div class="over"><b>' + t + '</b></div><div class="over desc">' + d['emp_nom'] + '</div>';
        //         }
        //     });

        //     $("#Emp_Cod_chosen").addClass('bs-chosen').find('.chosen-single').addClass('form-select form-control');
        //     $("#Emp_Cod_chosen").find(".chosen-search").find('input').addClass('text rounded');


        //     //$("#Emp_Cod_chosen").find(".chosen-search").find('input').addClass('text form-control rounded');
        //     // $("#Emp_Cod_chosen").find(".chosen-search").find('input').addClass('form-select form-control');
        // });


        // function loadEmp(ced) {
        //     $.post('', {
        //         ajax_empresas2: true,
        //         ajax_username: ced
        //     }, function(r) {
        //         $('#Emp_Cod').html(r['html']);
        //         if (r['success'] && r['conteo'] > 1) {
        //             $('#div_empresas').show();
        //         } else {
        //             $('#div_empresas').hide();
        //         }
        //         $('#Emp_Cod').trigger('chosen:updated');
        //     }, 'json').fail(function() {
        //         $('#Emp_Cod').html('<option value=""></option>');
        //         $('#div_empresas').hide();
        //         $('#Emp_Cod').trigger('chosen:updated');
        //     });
        // }
        // nuevo bloque de codigo para Select2
        $(document).ready(function() {
            // 1. Inicialización de Select2
            $('#Emp_Cod').select2({
            width: '100%',
            placeholder: "Seleccione Empresa...",
            templateResult: formatOption,
            templateSelection: formatSelection,
            escapeMarkup: function(m) { return m; },
            minimumInputLength: 0,
            dropdownParent: $('body'),
            dropdownCssClass: 'select2-dropdown-below select2-rcet-empresa'
            });

            // 2. Sobrescribir posición del dropdown (siempre abajo)
            $.fn.select2.amd.require(['select2/dropdown'], function(Dropdown) {
            var originalPosition = Dropdown.prototype._positionDropdown;
            
            Dropdown.prototype._positionDropdown = function() {
                originalPosition.apply(this, arguments);
                this.$dropdown
                .removeClass('select2-dropdown--above')
                .addClass('select2-dropdown--below')
                .css({
                    'top': this.$container.outerHeight(),
                    'bottom': 'auto', 'left': 0
                });
            };
            });

            // 3. Habilitar búsqueda instantánea al enfocar
            $('#Emp_Cod').on('select2:opening', function() {
            $(this).data('search-focus', true);
            });

            $('#Emp_Cod').on('select2:open', function() {
            if ($(this).data('search-focus')) {
                setTimeout(function() {
                var $search = $('.select2-search__field');
                $search.focus();

                // Asegura que el padre sea relativo para posicionar el icono
                $search.parent().css('position', 'relative');

                // Elimina iconos previos para evitar duplicados
                $search.parent().find('.search-icon').remove();

                // Agrega el icono de lupa dentro del input, pegado a la derecha
                var accentColor = (getComputedStyle(document.documentElement).getPropertyValue('--login-accent') || '#A02525').trim();
                var icon = $('<i class="bi bi-search search-icon"></i>').css({
                    position: 'absolute',
                    right: '10px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    color: accentColor,
                    'pointer-events': 'none',
                    'font-size': '1.2em'
                });
                $search.after(icon);

                // Ajusta el padding del input para que no tape el texto
                // $search.css('padding-right', '2em', 'border-radius', '10px');
                $search.css({ 'padding-right': '2.2em', 'border-radius': '10px' });

                // Manejar entrada directa de texto
                $search.on('input', function() {
                    if ($(this).val().length > 0) {
                    $('.select2-results__options').scrollTop(0);
                    }
                });
                }, 100);
            }
            });

            // 4. Abrir dropdown al hacer focus en el select
            $('.select2-selection').on('focus', function() {
            $('#Emp_Cod').select2('open');
            });
        });

        // Formatear cómo se muestran las opciones en el dropdown
        function formatOption(data) {
            if (!data.id) return data.text;
            
            var $option = $(data.element);
            var empNom = $option.data('empNom') || $option.data('emp_nom') || $option.attr('data-emp-nom') || '';
            return $(
                '<div class="select2-option-wrap">' +
                '<span class="select2-option-title">' + data.text + '</span>' +
                (empNom ? '<div class="text-muted">' + empNom + '</div>' : '') +
                '</div>'
            );
        }

        // Formatear cómo se muestra la opción seleccionada
        function formatSelection(data) {
            if (!data.id) return data.text;
            return data.text;
        }

        // Función para cargar empresas (modificada para Select2)
        function loadEmp(ced) {
            $.post('', {
                ajax_empresas2: true,
                ajax_username: ced
            }, function(r) {
                $('#Emp_Cod').html(r['html']);
                if (r['success'] && r['conteo'] > 1) {
                    $('#div_empresas').show();
                    // Actualizar Select2 después de cambiar las opciones
                    $('#Emp_Cod').trigger('change.select2');
                } else {
                    $('#div_empresas').hide();
                }
            }, 'json').fail(function() {
                $('#Emp_Cod').html('<option value=""></option>');
                $('#div_empresas').hide();
                $('#Emp_Cod').trigger('change.select2');
            });
        }
    </script>

    <script>
        var socketVentanas, Ses_Usu_Cod = 0,
            Ses_Emp_Cod = 0;
        $.isUnd = function(v) {
            return v === undefined;
        };
        $.varValid = $.vv = function(v) {
            return (v !== null && !$.isUnd(v));
        };
        $.isObject = $.isObj = function(v) {
            return $.vv(v) && !$.isArray(v) && typeof v === 'object';
        };
        $.jsonParser = function(v) {
            if ($.isArray(v) || $.isObj(v)) {
                return JSON.stringify(v);
            } else {
                try {
                    return JSON.parse(v);
                } catch (e) {
                    return v;
                }
            }
        };
        $.setLocalStore = function(name, data) {
            localStorage.setItem(name, $.jsonParser(data));
            if ($.isUnd(data)) localStorage.removeItem(name);
        };
        $.getLocalStore = function(name) {
            var data = localStorage.getItem(name);
            if ($.varValid(data)) return $.jsonParser(data);
        };
        $.getCookie = function(cname) {
            var na = cname + "=",
                dc = decodeURIComponent(document.cookie),
                ca = dc.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(na) === 0) {
                    return c.substring(na.length, c.length);
                }
            }
            return "";
        };

        (function($) {
            $.fn.chosenDesc = function(options) {
                return this.each(function() {
                    options = (typeof options !== 'undefined' ? options : {});
                    var $select = $(this),
                        descMap = {},
                        template = (typeof options['template'] !== 'undefined' ? options['template'] : function(text, templateData) {
                            return text;
                        });
                    $select.find('option').filter(function() {
                        return $(this).text();
                    }).each(function(i) {
                        $(this).attr('data-numero', i);
                        descMap[i] = $(this).data();
                        descMap[i]['opttxtsaved'] = $(this).text();
                    });
                    $select.chosen(options);
                    $chosen = $select.next().addClass('chosenDesc-container');
                    $select.bind('chosen:searchready', function() {
                        setTimeout(function() {
                            $chosen.find('.chosen-results li').each(function(i) {
                                $li = $(this), index = $li.attr('data-option-array-index') - 1;
                                $li.html(template($li.html(), descMap[index]));
                            });
                        }, 0);
                    });
                    $select.bind('chosen:showing_dropdown chosen:activate', function() {
                        setTimeout(function() {
                            $chosen.find('.chosen-results li').each(function(i) {
                                $li = $(this);
                                $li.html(template(descMap[i]['opttxtsaved'], descMap[i]));
                            });
                        }, 0);
                    });
                    $select.bind('chosen:updated', function() {
                        descMap = {};
                        $select.find('option').filter(function() {
                            return $(this).text();
                        }).each(function(i) {
                            $(this).attr('data-numero', i);
                            descMap[i] = $(this).data();
                            descMap[i]['opttxtsaved'] = $(this).text();
                        });
                    });
                });
            };
        })(jQuery);
    </script>
    <!-- <div class="aviso-flotante" id="avisoFlotante">
        <span class="cerrar-aviso" onclick="cerrarAviso()">&times;</span>
        <img src="mascaras\model1\img\logo\CARGA PAG.png" alt="Aviso" id="imagenAviso">
    </div>


    <script>
        window.addEventListener('load', function() {
            var avisoFlotante = document.getElementById('avisoFlotante');
            avisoFlotante.style.display = 'block'; // Mostrar el aviso flotante al cargar la página
        });

        // Función para cerrar el aviso flotante
        function cerrarAviso() {
            var avisoFlotante = document.getElementById('avisoFlotante');
            avisoFlotante.style.display = 'none'; // Ocultar el aviso flotante al hacer clic en el botón de cerrar
        }
    </script> -->
    <!-- Modal de cambio de contraseña obligatorio -->
    <div class="modal fade" id="modalDefaultPass" tabindex="-1" role="dialog" aria-labelledby="modalLabel" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 9999;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 16px; box-shadow: 0 15px 50px rgba(0,0,0,0.3); border: none; overflow: hidden;">
                <div class="modal-header" style="background: var(--login-accent); color: #fff; border: none; padding: 20px 25px;">
                    <h5 class="modal-title fw-bold" id="modalLabel" style="font-family: 'Saira', sans-serif;">
                        <i class="bi bi-shield-lock-fill me-2"></i> ALERTA DE SEGURIDAD
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center" style="padding: 45px 35px; background: #fff;">
                    <div class="mb-4">
                        <i class="bi bi-key-fill" style="font-size: 64px; color: var(--login-accent); opacity: 0.9;"></i>
                    </div>
                    <p class="mb-4" style="font-size: 1.1rem; color: #444; line-height: 1.6;">
                        Su cuenta est&aacute; usando una contrase&ntilde;a gen&eacute;rica.<br>
                        <strong style="color: var(--login-accent);">Se le recomienda cambiar su contrase&ntilde;a</strong> para continuar.
                    </p>
                    <div class="py-3">
                        <a href="javascript:void(0)" class="fs-5 fw-bold"
                            style="color: var(--login-accent); text-decoration: underline; transition: all 0.2s;"
                            onclick="switchToChangePass()">
                            Cambiar Contrase&ntilde;a
                        </a>
                    </div>
                    <p class="text-secondary small mt-3">
                        De lo contrario,no podr&aacute; acceder al sistema.
                    </p>
                </div>
                <div class="modal-footer justify-content-center" style="background: #fdfdfd; border-top: 1px solid #f0f0f0; padding: 15px;">
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Esta es una pol&iacute;tica de seguridad obligatoria.</small>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        function handleLogin() {
            var user = $('#user_name').val();
            var pass = $('#password').val();
            var o = document.querySelector('#Emp_Cod option:checked');
            var emp = $('#Emp_Cod').val();
            var suc = o ? (o.getAttribute('data-suc-cod') || '') : '';
            
            if (!user || !pass || !emp) {
                // Dejar que la validación nativa del form o el controlador maneje campos vacíos
                document.getElementById('acceso').submit();
                return;
            }

            // Preparar encriptación para el controlador
            document.getElementById('encryptor').value = md5(pass);
            document.getElementById('Suc_Cod').value = suc;
            
            // Verificación silenciosa
            var formData = $('#acceso').serialize() + '&ajax_check=1';
            
            $.post('administrador/FRONT/adm_con_control_1.2.php', formData, function(r) {
                if (r.success) {
                    if (r.insecure) {
                        // Deniega el acceso al dashboard y muestra el modal inmediatamente
                        $('#modalDefaultPass').modal('show');
                    } else {
                        // Login seguro, proceder al dashboard
                        document.getElementById('acceso').submit();
                    }
                } else {
                    // Datos incorrectos, recargar con error
                    if (r.error_type === 'device') {
                        window.location.href = 'index.php?errordispositivo=si';
                    } else {
                        window.location.href = 'index.php?errorusuario=si';
                    }
                }
            }, 'json').fail(function() {
                // Fallback a login tradicional
                document.getElementById('acceso').submit();
            });
        }

        function switchToChangePass() {
            // El modal se cierra y pasamos al formulario de cambio de clave
            // La sesión ya fue creada por el silent check de handleLogin()
            $('#modalDefaultPass').modal('hide');
            $('.login-section:not(#change-pass-section)').fadeOut(300, function() {
                $('#change-pass-section').fadeIn(400);
            });
            $('.login-exa-fuera').fadeOut(300);
        }

        function saveNewPass() {
            var oldP = $('#old_pass').val();
            var newP = $('#new_pass').val();
            var confP = $('#conf_pass').val();

            if (!oldP || !newP || !confP) {
                showAlert('Todos los campos son obligatorios.', 'danger');
                return;
            }

            if (newP !== confP) {
                showAlert('La nueva clave y su confirmación no coinciden.', 'danger');
                return;
            }

            if (newP.length < 6) {
                showAlert('La nueva clave debe tener al menos 6 caracteres.', 'warning');
                return;
            }

            // Validación alfanumérica
            var alphaRegex = /^[a-z0-9]+$/i;
            if (!alphaRegex.test(newP)) {
                showAlert('La nueva clave solo debe contener letras y números (sin espacios ni caracteres especiales).', 'warning');
                return;
            }

            $('#btnSavePass').prop('disabled', true).html('Guardando... <span class="spinner-border spinner-border-sm"></span>');

            $.post('index.php', {
                ajax_change_pass: true,
                old_pass: oldP,
                new_pass: newP
            }, function(r) {
                if (r.success) {
                    showAlert(r.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2500);
                } else {
                    showAlert(r.message, 'danger');
                    $('#btnSavePass').prop('disabled', false).html('Actualizar Contraseña <i class="bi bi-save-fill ms-1"></i>');
                }
            }, 'json').fail(function() {
                showAlert('Error de conexión con el servidor.', 'danger');
                $('#btnSavePass').prop('disabled', false).html('Actualizar Contraseña <i class="bi bi-save-fill ms-1"></i>');
            });
        }

        function showAlert(msg, type) {
            var html = '<div class="alert alert-' + type + ' py-2 small animate__animated animate__shakeX">' + msg + '</div>';
            $('#change-pass-alert').html(html);
        }

        function soloAlfanumerico(e) {
            // Esta función ya no se usa en tiempo real por petición del usuario, 
            // la validación se realiza al pulsar "Guardar".
            return true; 
        }
    </script>
    <style>
        body.modal-open .container {
            filter: blur(4px);
            transition: filter 0.3s ease;
        }
        #change-pass-section .form-control {
            border: 1px solid #ddd;
        }
        #change-pass-section .form-control:focus {
            border-color: var(--login-accent);
            box-shadow: 0 0 0 0.25rem rgba(27, 122, 74, 0.15);
        }
    </style>
</body>

</html> 