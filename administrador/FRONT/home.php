<?php

/*
Descripción: P�gina de inicio del sistema inform�tico EXA
Fecha de creaci�n:	2016-12-28
Desarrollador:	Erik Niebla
 */
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/logica.php');
//require_once('../LOGICA/adm_log_login.php');

if (isset($keepAlive)) {
    if(class_exists('\DebugBar'))DebugBar::setDebugBar(null);
    echo json_encode(array('success'=>true));
    exit();
}
if (isset($heartBeatChat)) {
    include("adm_con_online_2.0.php");
    echo json_encode($response);
    exit();
}

if (isset($getReportsExa)) {
    $obBDr = new MysqlDatosContab(true);
    $obBDr->setReports(isset($title) ? $title : ' ', isset($subTitle) ? $subTitle : ' ');
    exit();
}
if (isset($historyChat)) {
    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);
    $obBD_con1 =  new Class_Log_Datos_Adm;
    $response['history'] = $obBD_con1->getArrayConsulta(216, filter_input_array(INPUT_POST), $obBD_conexion);
    $response['success'] = true;
    echo json_encode($response);
    exit();
}
if (isset($signalChat)) {
    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);
    $obBD_con1 =  new Class_Log_Datos_Adm;
    $obBD_con1->grabarv_registros(sentencias_adm(219, filter_input_array(INPUT_POST)), $obBD_conexion->conexion);
    $response['success'] = true;
    echo json_encode($response);
    exit();
}
if (isset($ClientGuid)) {
    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);
    $obBD_con1 =  new Class_Log_Datos_Adm;
    $obBD_con1->grabarv_registros(sentencias_adm(215, filter_input_array(INPUT_POST)), $obBD_conexion->conexion);
    $response['success'] = true;
    echo json_encode($response);
    exit();
}
if (isset($loginAjax)) {
    require('../LOGICA/adm_log_control.php');
    $obBD_conexionMaster = new Class_Log_Conexion_Cnt; // Creacion del Objeto de Conexion
    $obBD_con =  new Class_Log_Datos_Cnt; // Creacion del Objeto de Datos
    $row_data = $obBD_con->getRowConsulta(2, $Emp_Cod . '*' . trim($user_name), $obBD_conexionMaster); //Consulta que realiza la autenticacion del usuario
    $obBD_conexion = new Class_Log_Conexion_Cnt($row_data['Dat_Dis']); // Conexion a la base de datos distribuida, dinamica
    $row_rs_control = $obBD_con->getArrayConsulta(16, trim($user_name) . '*' . trim($encryptor) . '*' . $Emp_Cod . '*' . "$Suc_Cod", $obBD_conexion); //Consulta que realiza la autenticacion del usuario
    //var_dump($row_rs_control);
    foreach ($row_rs_control as $rowControl)
        if ($rowControl['Suc_Cod'] == $Suc_Cod || strtoupper($rowControl['Suc_Des']) == 'MATRIZ')
            $row_rs_control = $rowControl;
    if (isset($row_rs_control['Suc_Cod'])) {
        $rs_perfiles = $obBD_con->getArrayConsulta(21, $row_rs_control["Usu_Cod"], $obBD_conexion); /* Consulta los perfiles asignados al usuario */
        $lperf = array();
        $Per_Des = array();
        foreach ($rs_perfiles as $v0) {
            $lperf[] = $v0["Per_Cod"];
            $Per_Des[] = $v0["Per_Des"];
        }
       
        //Sesion del usuario
        $_SESSION['Ses_Prs_Cod'] = $row_rs_control['Prs_Cod']; 
        /* Variables de Sesion del usuario  */
        $_SESSION['Ses_Usu_Cod'] = $row_rs_control['Usu_Cod'];
        $_SESSION['Ses_Usu_Ced'] = $row_rs_control['Usu_Ced'];
        $_SESSION['Ses_Usu_Tip'] = isset($row_rs_control['Usu_Tip']) ? $row_rs_control['Usu_Tip'] : '';
        $_SESSION['Ses_Usu_Est'] = $row_rs_control['Usu_Est'];
        $_SESSION['Ses_Usu_Cad'] = $row_rs_control['Usu_Cad'];
        $_SESSION['Ses_Usu_Men'] = $row_rs_control['Usu_Men'];
        $_SESSION['Ses_Per_Cod'] = isset($row_rs_control['Per_Cod']) ? $row_rs_control['Per_Cod'] : '';
        /* Variable para definir la sucursal y empresa */
        $_SESSION['Ses_Suc_Cod'] = $row_rs_control['Suc_Cod'];
        $_SESSION['Ses_Suc_Nom'] = $row_rs_control['Suc_Des'];
        $_SESSION['Ses_Emp_Cod'] = $row_rs_control['Emp_Cod'];
        $_SESSION['Ses_Emp_Nom'] = $row_rs_control['Emp_Nom'];
        $_SESSION['Ses_Emp_Cor'] = $row_rs_control['Emp_Cor'];
        $_SESSION['Ses_Suc_Web'] = $row_rs_control['Suc_Web'];
        $_SESSION['Ses_Emp_Log'] = $row_rs_control['Emp_Log'];
        /* Variables del Perfil del usuario */
        $_SESSION['Ses_Lis_Per'] = $lperf;
        $_SESSION['Ses_Per_Des'] = $Per_Des; //Descripción del perfil
        /* Variable para la base de datos del sistema local */
        $_SESSION['Ses_Dat_Dis'] = $row_data['Dat_Dis']; //Base de datos distribuida local
        $_SESSION['Ses_Dat_Aut'] = $row_data['Dat_Aut']; //Base de datos auditoria
        $_SESSION['Ses_Dat_Stg'] = $row_data['Dat_Stg']; //Base de datos storage
        $responce['success'] = true;
    } else {
        $responce['success'] = false;
    }
    echo json_encode($responce);
    exit();
}
if (isset($setSucu)) {
    require('../LOGICA/adm_log_control.php');
    $obBD_con =  new Class_Log_Datos_Cnt; // Creacion del Objeto de Datos
    $obBD_conexion = new Class_Log_Conexion_Cnt($Ses_Dat_Dis);

    $row_rs_control = $obBD_con->getRowConsulta(22, trim($user_name) . '*' . $Ses_Emp_Cod . '*' . $Suc_Cod, $obBD_conexion); //Consulta que realiza la autenticacion del usuario
    if (!isset($row_rs_control['Usu_Cod']) || empty($row_rs_control['Usu_Cod'])) {
        echo json_encode(array('success' => false, 'ver' => null));
        exit();
    }
    $_SESSION['Ses_Suc_Cod'] = $Suc_Cod;
    $_SESSION['Ses_Suc_Nom'] = $Suc_Nom;
    $_SESSION['Ses_Usu_Cod'] = $row_rs_control['Usu_Cod'];
    $_SESSION['Ses_Usu_Ced'] = $row_rs_control['Usu_Ced'];
    $_SESSION['Ses_Usu_Tip'] = isset($row_rs_control['Usu_Tip']) ? $row_rs_control['Usu_Tip'] : '';
    $_SESSION['Ses_Usu_Est'] = $row_rs_control['Usu_Est'];
    $_SESSION['Ses_Usu_Cad'] = $row_rs_control['Usu_Cad'];
    $_SESSION['Ses_Usu_Men'] = $row_rs_control['Usu_Men'];
    $_SESSION['Ses_Per_Cod'] = isset($row_rs_control['Per_Cod']) ? $row_rs_control['Per_Cod'] : '';
    //var_dump($row_rs_control);
    echo json_encode(array('success' => true, 'ver' => $row_rs_control));
    exit();
}




if (!isset($_SESSION) || (!isset($_SESSION['Ses_Lis_Per']) || !isset($_SESSION['Ses_Emp_Cod']) || !isset($_SESSION['Ses_Usu_Ced']))) header('Location: ' . '../index.php');
$apellido = explode(' ', $_SESSION['Ses_Prs_Ape']);
$nombre = explode(' ', $_SESSION['Ses_Prs_Nom']);

$obBD_conexion1 = new Class_Log_Conexion_Adm; //Creacion del Objeto de conexion
$obBD_con1 =  new Class_Log_Datos_Adm; //Creacion del objeto mysql para las consultas
$rs_empresas = $obBD_con1->getArrayConsulta(222, trim($Ses_Usu_Ced), $obBD_conexion1); //consulta empresas
$rs_sucursales = $obBD_con1->getArrayConsulta(214, $Ses_Emp_Cod . '*' . $Ses_Usu_Ced, $obBD_conexion1);

$bd = $obBD_con1->getRowConsulta(223, $Ses_Emp_Cod, $obBD_conexion1);
$bd_nombre = $bd['Dat_Dis'];
$obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);


$esAdministradorSistemas = false;
if (isset($_SESSION['Ses_Per_Des'])) {
    $perfilesArray = is_array($_SESSION['Ses_Per_Des']) ? $_SESSION['Ses_Per_Des'] : array($_SESSION['Ses_Per_Des']);
    foreach ($perfilesArray as $perfil) {
        if (stripos($perfil, 'Administrador de sistemas') !== false || strtoupper(trim($perfil)) === 'ADMINISTRADOR' || stripos($perfil, 'Sistemas') !== false) {
            $esAdministradorSistemas = true;
            break;
        }
    }
}
if (isset($_SESSION['Ses_Lis_Per'])) {
    $perfilesCod = is_array($_SESSION['Ses_Lis_Per']) ? $_SESSION['Ses_Lis_Per'] : array($_SESSION['Ses_Lis_Per']);
    if (in_array(1, $perfilesCod)) {
        $esAdministradorSistemas = true;
    }
}

$data_tickets = array();
$Cantidad_tickets = array("TOTAL" => 0);

if ($esAdministradorSistemas) {
    //Traer 10 notificaciones (WB)
    $data_tickets = $obBD_con1->getArrayConsulta(224, $Ses_Emp_Cod, $obBD_conexion);
    //var_dump($data_tickets);
    //Contar las notificaciones que no se han atendidos
    $Cantidad_tickets = $obBD_con1->getRowConsulta(225, $Ses_Emp_Cod, $obBD_conexion);
    //echo ($Cantidad_tickets["TOTAL"]);
}
//Traer documentos sin autorizar
$data_documentos =   array();//     $obBD_con1->getArrayConsulta(226, $Ses_Emp_Cod, $obBD_conexion1);

// Verificar si el usuario tiene una planta asignada en manifiesto_usuario
$isPlanta = false;
$plantName = "";
if (isset($_SESSION['Ses_Usu_Cod'])) {
    // Consulta para obtener el nombre de la planta asignada al usuario
    $sqlPlanta = "SELECT mp.Pla_Nom 
                  FROM manifiesto_usuario mu 
                  INNER JOIN manifiesto_plantas mp ON mu.Pla_Cod = mp.Pla_Cod 
                  WHERE mu.Usu_Cod = " . $_SESSION['Ses_Usu_Cod'];
    $resPlanta = $obBD_con1->consulta($sqlPlanta, $obBD_conexion->conexion);
    if ($rowPlanta = $obBD_con1->fetch_assoc($resPlanta)) {
        $plantName = $rowPlanta['Pla_Nom'];
        $isPlanta = true;
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>
        <?php echo $Ses_Sys_Nom; ?>
    </title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="iso8859-1" />
    <meta name="description" content="overview &amp; stats" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <!-- <link rel="shortcut icon" type="image/x-icon" href="../../mascaras/model1/img/logo/exa-ico-2.png" /> Anterior Logo-->
    <link rel="shortcut icon" type="image/x-icon" href="../../imagenes/ingresar/favicon.png" /> <!-- Nuevo Logo -->
    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/tooltip.min.css" />
    <link rel="stylesheet" href="../../framework/plugins/fonts/font-awesome/font-awesome-4.4.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../skins/fonts/fontelo/fontello.css?x=0" />
    <!-- text fonts -->
    <link rel="stylesheet" href="../../skins/css/ace-fonts.css" />
    <!-- ace styles -->
    <link rel="stylesheet" href="../../skins/css/ace.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="../../skins/css/ace-skins.css" type="text/css" id="ace-skins-stylesheet">
    <link rel="stylesheet" href="../../skins/css/ace-fixes.css" type="text/css" id="ace-skins-stylesheet">
    <!--[if lte IE 9]><link rel="stylesheet" href="../../skins/css/ace-part2.css" class="ace-main-stylesheet" /><![endif]-->
    <!--[if lte IE 9]><link rel="stylesheet" href="../../skins/css/ace-ie.css" /><![endif]-->
    <!-- ace settings handler -->
    <script src="../../skins/js/ace-extra.js"></script>
    <!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->
    <!--[if lte IE 8]>
		<script src="../../framework/plugins/compatibility/html5shiv/html5shiv-3.7.3.js"></script>
		<script src="../../framework/plugins/compatibility/respond-1.4.2.js"></script>
		<![endif]-->
    <!--[if !IE]> -->
    <script type="text/javascript">
        window.jQuery || document.write("<script src='../../skins/js/jquery.js'>" + "<" + "/script>");
    </script><!-- <![endif]-->
    <!--[if IE]><script type="text/javascript">window.jQuery || document.write("<script src='../../skins/js/jquery1x.js'>"+"<"+"/script>");</script><![endif]-->
</head>
<style>
    .modal_notificacion {
        display: none;
    }

    .modal_documentos {
        display: none;
    }

    /* Variables y Estilos del Modal Cambiar Empresa - Tema Relavera (Verde) */
    :root {
        --brand-color: #1b5e37;
        --brand-bg-light: #eaf4ee;
        --brand-color-dark: #124328;
        --brand-border-color: #1b5e37;
    }

    #myModal .modal-dialog {
        max-width: 640px;
        width: 95%;
        margin: 30px auto;
    }

    #myModal .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        position: relative;
        background-color: #ffffff;
    }

    #myModal .brand-top-bar {
        display: none;
    }

    #myModal .modal-header {
        background: #ffffff;
        border-bottom: 3px solid var(--brand-color);
        padding: 18px 24px 14px 24px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        position: relative;
    }

    #myModal .header-left-content {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    #myModal .header-icon-box {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 12px;
        background-color: var(--brand-bg-light);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--brand-color);
        font-size: 24px;
    }

    #myModal .header-title-box h4 {
        margin: 0 0 2px 0;
        font-size: 1.6rem;
        font-weight: 600;
        color: #1a1a1a;
    }

    #myModal .header-title-box p {
        margin: 0;
        font-size: 1rem;
        color: #6c757d;
    }

    #myModal .btn-close-custom {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #f4f2ee;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #555;
        font-size: 18px;
        cursor: pointer;
        transition: background-color 0.2s ease, color 0.2s ease;
        padding: 0;
        outline: none;
        margin-top: -4px;
    }

    #myModal .btn-close-custom:hover {
        background-color: #e5e2dc;
        color: #000;
    }

    #myModal .modal-body {
        padding: 16px 24px 16px 24px;
    }

    #myModal .section-label-brand {
        font-size: 0.92rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--brand-color);
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    #myModal .user-profile-box {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    #myModal .user-avatar {
        width: 36px;
        height: 36px;
        min-width: 36px;
        border-radius: 50%;
        background-color: var(--brand-bg-light);
        color: var(--brand-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    #myModal .user-info-text .user-name {
        font-weight: 600;
        color: #212529;
        font-size: 1.12rem;
        line-height: 1.2;
    }

    #myModal .user-info-text .user-id {
        font-size: 0.9rem;
        color: #6c757d;
    }

    #myModal .divider-subtle {
        border-top: 1px solid #e9ecef;
        margin: 10px 0;
    }

    #myModal .company-card-active {
        background-color: var(--brand-bg-light);
        border-radius: 8px;
        padding: 8px 12px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    #myModal .company-card-active i {
        color: var(--brand-color);
        font-size: 20px;
        margin-top: 2px;
    }

    #myModal .company-card-active .company-card-name {
        font-weight: 600;
        font-size: 1.12rem;
        color: #212529;
        line-height: 1.3;
    }

    #myModal .company-card-active .company-card-sub {
        font-size: 0.9rem;
        color: #6c757d;
        margin-top: 2px;
    }

    #myModal .field-label {
        font-size: 1.12rem;
        font-weight: 600;
        color: #333333;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    #myModal .field-label i {
        color: #6c757d;
        font-size: 18px;
    }

    /* Estilos Select y Select2 */
    #myModal select.form-control {
        height: 42px;
        font-size: 1.1rem;
        border-radius: 8px;
        padding: 6px 12px;
    }

    #myModal .select2-container--default .select2-selection--single {
        border: 1px solid #ced4da;
        border-radius: 8px;
        height: 42px;
        display: flex;
        align-items: center;
        font-size: 1.1rem;
    }
    #myModal .select2-container--default.select2-container--focus .select2-selection--single,
    #myModal .select2-container--default.select2-container--open .select2-selection--single {
        border-color: var(--brand-color);
        box-shadow: 0 0 0 3px rgba(27, 94, 55, 0.2);
    }
    #myModal .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #212529;
        line-height: normal;
        padding-left: 12px;
    }
    #myModal .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
        right: 8px;
    }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: var(--brand-color) !important;
        color: white !important;
    }
    .select2-container--default .select2-results__option {
        font-size: 1.1rem;
        padding: 8px 12px;
    }

    #myModal .password-group {
        border: 1px solid #ced4da;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        align-items: center;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background: #fff;
        height: 42px;
    }

    #myModal .password-group:focus-within {
        border-color: var(--brand-color) !important;
        box-shadow: 0 0 0 3px rgba(27, 94, 55, 0.2) !important;
    }

    #myModal .password-group .form-control {
        border: none !important;
        box-shadow: none !important;
        outline: none !important;
        padding: 8px 12px;
        font-size: 1.1rem;
        height: 100%;
    }

    #myModal .password-group .btn-toggle-pwd {
        background: transparent;
        border: none;
        padding: 8px 12px;
        color: #6c757d;
        cursor: pointer;
        display: flex;
        align-items: center;
        font-size: 18px;
    }

    #myModal .password-group .btn-toggle-pwd:hover {
        color: var(--brand-color);
    }

    #msgAlert .alert {
        margin-top: 10px;
        margin-bottom: 0;
        padding: 8px 12px;
        font-size: 0.92rem;
    }

    #myModal .modal-footer {
        border-top: 1px solid #f0f0f0;
        padding: 12px 24px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background-color: #ffffff;
    }

    #myModal .btn-brand-cancel {
        background-color: #6c757d !important;
        border: 1px solid #6c757d !important;
        color: #ffffff !important;
        border-radius: 8px;
        padding: 8px 20px;
        font-weight: 500;
        font-size: 1.05rem;
        transition: all 0.2s ease;
    }

    #myModal .btn-brand-cancel:hover {
        background-color: #5a6268 !important;
        border-color: #545b62 !important;
        color: #ffffff !important;
    }

    #myModal .btn-brand-submit {
        background-color: var(--brand-color) !important;
        border: 1px solid var(--brand-color) !important;
        color: #ffffff !important;
        border-radius: 8px;
        padding: 8px 20px;
        font-weight: 500;
        font-size: 1.05rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.2s ease;
    }

    #myModal .btn-brand-submit:hover, #myModal .btn-brand-submit:focus {
        background-color: #124328 !important;
        border-color: #124328 !important;
        color: #ffffff !important;
        box-shadow: 0 0 0 3px rgba(27, 94, 55, 0.3) !important;
    }
</style>

<body class="<?php echo (isset($_COOKIE['ace_skin']) ? $_COOKIE['ace_skin'] : 'no-skin') ?>" onLoad="resizeMain()">
    <!-- #section:basics/navbar.layout -->
    <div id="navbar" class="navbar navbar-default navbar-fixed-top">
        <script type="text/javascript">
            try {
                ace.settings.check('navbar', 'fixed');
            } catch (e) {}
        </script>
        <div class="navbar-container" id="navbar-container">
            <!-- #section:basics/sidebar.mobile.toggle -->
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <!-- /section:basics/sidebar.mobile.toggle -->
            <div class="navbar-header pull-left">
                <!-- #section:basics/navbar.layout.brand -->
                <!-- <div class="navbar-brand" style="padding-top:2px;padding-bottom:2px;padding-right:0;">
                    <img style="height:40px; display: inline" src="../../skins/img/logoexa.png" data-tooltip="tooltip" data-placement="right" title="EXA - Software Contable" /> Anterior -->
                <div class="navbar-brand" style="padding-top:2px;padding-bottom:2px;padding-right:0;margin-top: 5px;">
                    <img id="logo-exa-img" style="height:28px; display: inline; margin-left: -10px; margin-right: 10px; cursor:pointer;" src="../../skins/img/newlogo.png" onclick="activarTab('contenido');" data-tooltip="tooltip" data-placement="right" title="EXA - Software Contable" />
                    <!-- PREPARAR BLOQUE DE CODIGO PARA CAMBIO DE LOGO EN BASE AL TEMA -->
                    <!-- <script>
                        // Cambia el logo según la opción seleccionada (skin-3 = #D0D0D0)
                        document.addEventListener('DOMContentLoaded', function () {
                            var picker = document.getElementById('skin-colorpicker');
                            var logo = document.getElementById('logo-exa-img');
                            var empr = document.getElementById('Empr');
                            var sucur = document.getElementById('dLabel');
                            var icono = document.getElementById('icoEmp');
                            if (!picker || !logo) return;

                            function updateLogoAndIcons() {
                                var skin = picker.options[picker.selectedIndex]?.getAttribute('data-skin');
                                var isSkin3 = (picker.value === "#D0D0D0" || skin === 'skin-3');
                                logo.src = isSkin3 ? '../../skins/img/newlogo.png' : '../../skins/img/newlogo_white.png';

                                // Cambia el color de los iconos en la barra de navegación
                                document.querySelectorAll('.ace-nav > li > a > .ace-icon').forEach(function(icon) {
                                    icon.style.color = isSkin3 ? '' : '#fff';
                                });

                                // Cambia el color del texto de #Empr y #Sucur según el skin
                                if (empr) empr.style.color = isSkin3 ? '#000' : '#fff';
                                if (sucur) sucur.style.color = isSkin3 ? '#000' : '#fff';
                                if (icono) icono.style.color = isSkin3 ? '#e3e3e3' : '#fff';
                            }

                            picker.addEventListener('change', updateLogoAndIcons);
                            document.addEventListener('ace.settings.skin', updateLogoAndIcons);
                            if (window.jQuery) $(picker).on('change', updateLogoAndIcons);
                            updateLogoAndIcons();
                        });
                    </script> -->
                    <!--Ace Admin-->
                    <small id="Empr" style="font-size: 16px; color: #000; text-align: center;">
                        <i id="icoEmp" class="fa fa-building" style="color: #000;"></i>
                        <!-- <?php echo '<span style="margin-top: 10px;">' . ucwords(strtolower($Ses_Emp_Nom)) . '</span>' . (count($rs_sucursales) == 1 ? ' <b>[' . strtoupper($Ses_Suc_Nom) . ']</b>' : ''); ?> -->
                        <?php 
                            if ($isPlanta && !empty($plantName)) {
                                echo '<span style="align-content: center;margin-top: 2px;height: 20px;">' . strtoupper($plantName) . '</span>';
                            } else {
                                echo '<span style="align-content: center;margin-top: 2px;height: 20px;">' . ucwords(strtolower($Ses_Emp_Nom)) . '</span>' . (count($rs_sucursales) == 1 ? ' <b>[' . strtoupper($Ses_Suc_Nom) . ']</b>' : ''); 
                            }
                        ?>
                    </small>
                    <?php if (count($rs_sucursales) > 1 && !$isPlanta) { ?>
                        <div class="dropdown" style="display: inline">
                            <a id="dLabel" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="empresa"><?php echo ' [' . strtoupper($Ses_Suc_Nom) . ']'; ?><span class="caret"></span></a>
                            <ul id="Sucur" class="dropdown-menu" aria-labelledby="dLabel">
                                <?php foreach ($rs_sucursales as $rowSuc) {  ?>
                                    <?php if ($Ses_Suc_Cod !== $rowSuc['Suc_Cod']) { ?>
                                        <li>
                                            <a tabindex="-1" href="javascript:setSucu('<?php echo $rowSuc['Suc_Cod']; ?>','<?php echo $rowSuc['Suc_Des']; ?>');"><?php echo $rowSuc['Suc_Des']; ?></a>
                                        </li>
                                    <?php } else { ?>
                                        <li>
                                            <a style="background: #1d354d; color: lightgray;"><?php echo $rowSuc['Suc_Des']; ?></a>
                                        </li>
                                    <?php } ?>
                                <?php } ?>
                            </ul>
                        </div>
                    <?php } ?>
                </div>
                <!-- /section:basics/navbar.layout.brand -->
                <!-- #section:basics/navbar.toggle -->
                <!-- /section:basics/navbar.toggle -->
            </div>
            <!-- #section:basics/navbar.dropdown -->
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <?php
                    //Nuevo item para verificar si existen tickets activos
                    $message_tickets = ($Cantidad_tickets["TOTAL"] != 0) ? "tickets que requieren tu atenci&oacute;n" : "tickets";
                    $docs_pendientes = (count($data_documentos) != 0) ? "documentos sin autorizar que requieren tu atenci&oacute;n" : "documentos sin autorizar"; ?>
                    <!-- oculta ciertos iconos para el mobil a una resolucion - cambiar a futuro -->
                    <style>
                        @media only screen and (max-width: 425px) {
                            #notificaciones_trigger,
                            #documentos_trigger,
                            #support,
                            #SRI,
                            #IESS,
                            #Who {
                                display: none !important;
                            }
                        }
                    </style>

                    <?php if ($esAdministradorSistemas) { //Solo administrador de sistemas puede ver este apartado 
                    ?>
                        <li data-tooltip="tooltip" data-placement="bottom" title="Verificar tikets" id="notificaciones_trigger">
                            <a>
                                <i style="font-size: 18px;" class="ace-icon fa fa-bell icon-animated-bell" aria-hidden="true"></i>
                                <span class="badge badge-important">
                                    <?php echo $Cantidad_tickets["TOTAL"]; ?>
                                </span>
                            </a>
                        </li>
                    <?php

                    } else { ?> <!--Nuevo item para verificar si existen tickets de documentos por autorizar-->
                        <li data-tooltip="tooltip" data-placement="bottom" title="Verificar Documentos sin autorizar" id="documentos_trigger">
                            <a>
                                <i style="font-size: 18px; margin-top: 15px;" class="ace-icon fa fa-bell icon-animated-bell" aria-hidden="true"></i>
                                <span class="badge badge-important">
                                    <?=count($data_documentos); ?>
                                </span>
                            </a>
                        </li>
                    <?php  }
                    ?>

                    <!-- Modal para cargar los tickets -->
                    <div id="modal_notificacion" class="modal_notificacion" style=" position: absolute; margin-top: 48px;margin-left: -241px;">
                        <div style="background: white; width:300px; height: 300px;overflow: auto;border-radius:3px;">
                            <div style="padding:10px;background:#2e6589;color:#ffffff">Tienes <?php if(isset($cantidad_tickets)) echo $cantidad_tickets . " " . $message_tickets; ?> </div>
                            <?php if(isset($data_tickets)) foreach ($data_tickets  as $row) { ?>
                                <a style="text-decoration: none;color:#a3a3a3;" href="/administrador/FRONT/adm_gst_soporte.php">
                                    <div style="padding:0 10px;margin:0; border-bottom:1px solid;display:flex;align-items: center;">
                                        <span> <i style="font-size: 18px;" class="ace-icon fa fa-bell" aria-hidden="true"></i> </span>
                                        <span style="margin-left: 15px;">
                                            <h6 style="text-decoration: none;color:#585858;margin-bottom:0"><?= utf8_encode($row['Tic_Tem']); ?></h6>
                                            <span> <?php echo $row['Tic_Fec_Cre'] ?></span>
                                        </span>
                                    </div>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <!-- fin de modal para cargar los tickets -->

                    <!-- Modal para cargar los verificar documentos -->
                    <div id="modal_documentos" class="modal_documentos" style=" position: absolute; margin-top: 48px;margin-left: -241px;">
                        <div style="background: white; width:300px; height: 300px;overflow: auto;border-radius:3px;">
                            <div style="padding:10px;background:#2e6589;color:#ffffff">Tienes <?php echo count($data_documentos) . " " . $docs_pendientes; ?> </div>
                            <?php foreach ($data_documentos  as $row) { ?> <!-- llama todos los documentos sin autorizar -->
                                <a style="text-decoration: none;color:#a3a3a3;" href="/facturacion/FRONT/fac_alt_aut_sri_1.php">
                                    <div style="padding:0 10px;margin:0; border-bottom:1px solid;display:flex;align-items: center;">
                                        <span> <i style="font-size: 18px;" class="ace-icon fa fa-bell" aria-hidden="true"></i> </span>
                                        <span style="margin-left: 15px;">
                                            <h6 style="text-decoration: none;color:#585858;margin-bottom:0"><?php echo "Documento de " . utf8_encode($row['Tipo']) . " #" . utf8_encode($row['Doc_Num']); ?></h6>
                                            <span> <?php echo $row['Doc_Fec'] ?></span>
                                        </span>
                                    </div>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <!-- fin de modal para cargar los verifica -->

                    <!-- #section:basics/navbar.user_menu -->
                    <style>
                        .red-social.r2 a:hover,
                        .red-social.r4 a:hover {
                            background: #e6f2ff;
                        }
                        .red-social.r2 a,
                        .red-social.r4 a {
                            box-sizing: border-box;
                        }
                        /* 📱 SOLO ENTRE 767 y 822 */
                        @media (min-width: 424px) and (max-width: 1096px) {
                            #support,
                            #SRI,
                            #IESS,
                            #Who {
                                display: none !important;
                            }
                        }
                    </style>
                    <li id="support" data-tooltip="tooltip" data-placement="bottom" title="Solicita soporte t&eacute;cnico aqu&iacute;">
                        <a href="/administrador/FRONT/adm_gst_tickets.php" target="_blank" style="display: flex; align-items: center; justify-content: center; height: 100%;">
                            <i class="ace-icon fa fa-headphones" style="font-size: 28px;"></i>
                        </a>
                    </li>
                    <li id="SRI" class="red-social r2" data-tooltip="tooltip" data-placement="bottom" title="Ir a SRI" style="display: flex; align-items: center; justify-content: center;">
                        <a href="https://srienlinea.sri.gob.ec/" target="blank" style="display: flex; align-items: center; justify-content: center; height: 100%; width: 100%; padding: 0; transition: background 0.2s;">
                            <img src="../../imagenes/iconos/SRI.png" alt="SRI" style="height:40px; vertical-align:middle; display: block; margin: 0 auto;">
                        </a>
                    </li>
                    <li id="IESS" class="red-social r4" data-tooltip="tooltip" data-placement="bottom" title="Ir al IESS" style="display: flex; align-items: center; justify-content: center;">
                        <a href="https://www.iess.gob.ec/" target="blank" style="display: flex; align-items: center; justify-content: center; height: 100%; width: 100%; padding: 0; transition: background 0.2s;">
                            <img src="../../imagenes/iconos/IESS.png" alt="IESS" style="height:32px; width: 40px; vertical-align:middle; display: block; margin: 0 auto;">
                        </a>
                    </li>
                    <li id="Who"class="red-social r1" data-tooltip="tooltip" data-placement="bottom" title="&iquest;Qui&eacute;nes somos?" style="display: flex; align-items: center;">
                        <a href="../../skins/html/ACERCA-DE-EXA1.html" target="contenido" style="display: flex; align-items: center; justify-content: center; height: 100%;">
                            <i class="ace-icon fa fa-users" style="font-size: 22px; display: block; margin: 0 auto;"></i>
                        </a>
                    </li>
                    <?php
                    require_once("../LOGICA/adm_log_notifications.php");
                    $obBD_Notif =  new Class_Sys_Notifications($obBD_conexion);
                    echo $obBD_Notif->renderNotifications();
                    ?>
                    <li class="light-blue user-links">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <!--<img class="nav-user-photo" src="../../skins/avatars/user.jpg" alt="Jason's Photo" />-->
                            <i class="ace-icon1 fa fa-user nav-user-icon" style="font-size: 22px; margin-right: 6px;"></i>
                            <span class="user-info"style="text-align: right;"><small>Bienvenido,</small>
                                <?php echo /*utf8_decode*/ ($nombre[0] . ' ' . $apellido[0]); ?>
                            </span><i class="ace-icon1 fa fa-caret-down" style="margin-left: 5px;"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a class="ace-settings-btn">
                                <i class="ace-icon fa fa-cog"></i>Configuraci&oacute;n
                            </a></li>
                            <li><a href="./adm_pas_usuarios_2.0.php" target="contenido">
                                <i class="ace-icon fa fa-key"></i>Cambiar clave
                            </a></li>
                            <?php if (count($rs_empresas) > 1) { ?>
                                <li><a data-toggle="modal" data-target="#myModal">
                                    <i class="ace-icon fa fa-user"></i>Cambiar empresa
                                </a></li><?php } ?>
                            <li><a onclick="$('#modalAlertPrinter').modal('show');">
                                <i class="ace-icon glyphicon glyphicon-print"></i>Impresoras
                            </a></li>
                            <li class="divider"></li>
                            <li><a href="../LOGICA/logout.php">
                                <i class="ace-icon fa fa fa-sign-out"></i>Cerrar Sesi&oacute;n
                            </a></li>
                        </ul>
                    </li><!-- /section:basics/navbar.user_menu -->
                </ul>
            </div><!-- /section:basics/navbar.dropdown -->
        </div><!-- /.navbar-container -->
    </div><!-- /section:basics/navbar.layout -->
    <div class="main-container" id="main-container">
        <script type="text/javascript">
            try {
                ace.settings.check('main-container', 'fixed');
            } catch (e) {}
        </script>
        <!-- #section:basics/sidebar -->
        <div id="sidebar" class="sidebar responsive sidebar-fixed sidebar-scroll <?php echo (!isset($_COOKIE['ace_compact']) || $_COOKIE['ace_compact'] == 'true' || $_SESSION['Ses_Usu_Men'] != 'T' ? 'compact' : ''); ?>">
            <script type="text/javascript">
                try {
                    ace.settings.check('sidebar', 'fixed');
                } catch (e) {}
            </script>
            <?php //if(count($rs_empresas)>1){ 
            ?>
            <div class="sidebar-shortcuts" id="sidebar-shortcuts">
                <div class="sidebar-shortcuts-large" id="sidebar-shortcuts-large">
                    <button class="btn btn-success" style="display:none;"><i class="ace-icon fa fa-signal"></i></button>
                    <button class="btn btn-info" <?php if (count($rs_empresas) == 1) { ?>style="display:none;" <?php } ?> data-toggle="modal" data-target="#myModal" data-tooltip="tooltip" data-placement="right" title="Cambiar Empresa"><i class="ace-icon fa fa-sign-in"></i></button>
                    <a href="./adm_pas_usuarios_1.0.php" style="display:none;" target="contenido" class="btn btn-warning" data-tooltip="tooltip" data-placement="right" title="Cambiar Clave"><i class="ace-icon fa fa-key"></i></a>
                    <a href="../LOGICA/logout.php" class="btn btn-danger" data-tooltip="tooltip" data-placement="right" title="Cerrar Sesion"><i class="ace-icon fa fa-sign-out"></i></a>
                </div>
                <div class="sidebar-shortcuts-mini" id="sidebar-shortcuts-mini">
                    <span class="btn btn-success"></span>
                    <span class="btn btn-info"></span>
                    <span class="btn btn-warning"></span>
                    <span class="btn btn-danger"></span>
                </div>
            </div><!-- /.sidebar-shortcuts -->
            <?php //} 
            ?>
            <?php
            if ($_SESSION['Ses_Usu_Men'] == 'B') { ?>
                <script src="../LOGICA/TreeMenu.js" language="JavaScript" type="text/javascript"></script>
                <div class="nav nav-list" style="background:url('../../mascaras/model1/imagenes/system/main-back.png');">
                    <div id="nav-tree" style="overflow-x: hidden;"><?php require_once("adm_con_treemenu.php"); ?></div>
                </div>
                <style>
                    .scroll-white .scroll-bar {
                        background-color: transparent;
                        background-color: rgba(153, 149, 215, 0.82);
                        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#55FFFFFF', endColorstr='#55FFFFFF', GradientType=0);
                    }
                </style>
                <script type="text/javascript">
                    if (ace.cookie.get('ace_tree') !== 'true') {
                        ace.cookie.set('ace_compact', false);
                        ace.cookie.set('ace_tree', true);
                    }
                </script>
            <?php
            } else {
                \DebugBar::startMeasure('MenuSidebar', 'Menu Sidebar');
                require_once("../LOGICA/adm_log_menu_tree.php");
                $obBD_con1 =  new Class_Sys_Menu;
                echo ($obBD_con1->menuToHtml(1, $obBD_con1->getMenuContainer2($_SESSION['Ses_Lis_Per'], $obBD_conexion), 'nav nav-list', (!isset($_COOKIE['ace_hover']) || $_COOKIE['ace_hover'] == 'true' || $_COOKIE['ace_compact'] == 'true' ? 'hover' : '')));
                \DebugBar::stopMeasure('MenuSidebar');
            ?><script type="text/javascript">
                    if (ace.cookie.get('ace_tree') === 'true') {
                        ace.cookie.set('ace_compact', true);
                        ace.cookie.remove('ace_tree');
                    }
                </script><?php
                        } ?>
            <!-- #section:basics/sidebar.layout.minimize -->
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse"><i class="ace-icon fa fa-angle-double-left " data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i></div>
            <!-- /section:basics/sidebar.layout.minimize -->
            <script type="text/javascript">
                try {
                    ace.settings.check('sidebar', 'collapsed');
                } catch (e) {}
            </script>
        </div>
        <!-- /section:basics/sidebar -->
        <div class="main-content">
            <div class="main-content-inner">
                <div class="page-content" style="padding:0">
                    <!-- #section:settings.box -->
                    <div class="ace-settings-container" id="ace-settings-container">
                        <div class="ace-settings-box clearfix" id="ace-settings-box">
                            <div class="pull-left width-50">
                                <!-- #section:settings.skins -->
                                <div class="ace-settings-item">
                                    <div class="pull-left">
                                        <select id="skin-colorpicker" class="hide">
                                            <!-- <option data-skin="no-skin" value="#f63232">#438EB9</option> --> <!-- Rojo -->
                                            <!-- <option data-skin="skin-1" value="#19a6db">#222A2D</option> --> <!-- Azul -->
                                            <option data-skin="skin-3" value="#D0D0D0">#D0D0D0</option> <!-- Plomo -->
                                            <!-- <option data-skin="skin-2" value="#3cbe5e">#C6487E</option> --> <!-- Verde -->
                                        </select>
                                    </div>
                                    <span>&nbsp; Escoge Tema</span>
                                </div>
                                <div class="ace-settings-item ace-settings-con">
                                    <input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-hover" checked="" style="margin-right: 6px;margin-left: 4px;" />
                                    <label class="lbl" for="ace-settings-hover"> Submenu al Pasar</label>
                                </div>

                                <div class="ace-settings-item ace-settings-con">
                                    <input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-compact" checked="" style="margin-right: 6px;margin-left: 4px;" />
                                    <label class="lbl" for="ace-settings-compact"> Men&uacute; Compacto</label>
                                </div>

                                <div class="ace-settings-item ace-settings-con" style="display:none;">
                                    <input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-highlight" style="margin-right: 6px;margin-left: 4px;" />
                                    <label class="lbl" for="ace-settings-highlight"> Mostrar M. Activo</label>
                                </div>
                                <div class="ace-settings-item center" style="min-height: 35px;">
                                    <button class="btn btn-xs btn-info ace-settings-btn"><i class="glyphicon glyphicon-time"> Cerrar</i></button>
                                </div>
                                <!-- /section:settings.container -->
                            </div><!-- /.pull-left -->
                        </div><!-- /.ace-settings-box -->
                    </div><!-- /.ace-settings-container -->
                    <!--<div class="row">
							<div class="col-xs-12">
								<-- PAGE CONTENT BEGINS -->
                    <!--iframe align="left" name="contenido" height="100%" width="100%" id="contenido" frameborder="0" class="contenido" src="../../skins/html/index.html" scrolling="auto" allowfullscreen style="height:100%;/* padding-top:5px;*/ display:block;margin-left:auto; margin-right:auto; max-width:100%; -webkit-transform:translate3d(0,0,0);"></iframe-->

                    <div class="container-fluid" style="padding: 0; margin-left:0;">
                        <ul id="tabs" class="nav nav-tabs" style="align-items: center; padding: 2px;">
                            <li class="nav-item" style="display: flex; align-items: center; justify-content: center; height: 20px; ">
                                <a class="nav-link active" id="tab_home" style="margin: 1px; padding: 0px 3px; background:#f8f8f8; font-size:10px; margin-top: 2px; display: flex; align-items: center; gap: 5px; border-radius: 3px !important;" href="#" onclick="activarTab('contenido');">
                                    <i class="fa fa-home" style="font-size: 20px; background: transparent; color: #800020; margin: 0;"></i>
                                    <span style="color: black; font-weight: bold; line-height: 20px; display: flex; align-items: center;">Home</span>
                                </a>
                            </li>
                        </ul>
                        <div id="iframes" style="width: 100%; height: calc(100% - 40px);">
                            <!-- <iframe align="left" name="contenido" height="100%" width="100%" id="contenido" frameborder="0" class="contenido" src="../../skins/html/index.html" scrolling="auto" allowfullscreen style="height:100%;/* padding-top:5px;*/ display:block;margin-left:auto; margin-right:auto; max-width:100%; -webkit-transform:translate3d(0,0,0);"></iframe> -->
                            <iframe align="left" name="contenido" height="100%" width="100%" id="contenido" frameborder="0" class="contenido" src="../../skins/php/index_button.php" scrolling="auto" allowfullscreen style="height:100%;/* padding-top:5px;*/ display:block;margin-left:auto; margin-right:auto; max-width:100%; -webkit-transform:translate3d(0,0,0);"></iframe>
                            <!-- <iframe align="left" name="contenido" height="100%" width="100%" id="contenido" frameborder="0" class="contenido" src="../../skins/php/indexHome.php" scrolling="auto" allowfullscreen style="height:100%;/* padding-top:5px;*/ display:block;margin-left:auto; margin-right:auto; max-width:100%; -webkit-transform:translate3d(0,0,0);"></iframe> -->
                        </div>
                    </div>

                    <style>
                        html, body {
                            overflow: hidden !important;
                            height: 100% !important;
                        }
                    </style>

                    <script>
                        // Espera a que el iframe cargue completamente
                        document.getElementById('contenido').addEventListener('load', function () {
                            try {
                                const iframe = document.getElementById('contenido');
                                // Delegación para todos los clicks en el body del iframe
                                iframe.contentWindow.document.body.addEventListener('click', function (e) {
                                    let el = e.target;
                                    // Busca hacia arriba hasta encontrar un <a> o <button> con data-url o href
                                    while (el && el !== this) {
                                        if (el.tagName === 'A' && el.hasAttribute('data-url')) {
                                            e.preventDefault();
                                            const url = el.getAttribute('data-url');
                                            const titulo = el.textContent.trim() || 'Sin título';
                                            window.parent.abrirFormularioEnTab(titulo, url);
                                            break;
                                        }
                                        if (el.tagName === 'A' && el.hasAttribute('href') && el.getAttribute('href') !== '#') {
                                            // Si es un enlace normal, lo abrimos en tab
                                            e.preventDefault();
                                            const url = el.getAttribute('href');
                                            const titulo = el.textContent.trim() || 'Sin título';
                                            window.parent.abrirFormularioEnTab(titulo, url);
                                            break;
                                        }
                                        if (el.tagName === 'BUTTON' && el.hasAttribute('data-url')) {
                                            e.preventDefault();
                                            const url = el.getAttribute('data-url');
                                            const titulo = el.textContent.trim() || 'Sin título';
                                            window.parent.abrirFormularioEnTab(titulo, url);
                                            break;
                                        }
                                        el = el.parentElement;
                                    }
                                }, true);
                            } catch (err) {
                                // Puede fallar por CORS si el contenido no es local
                                console.log('No se pudo agregar el listener al iframe:', err);
                            }
                        });
                    </script>

                    <div id='ajaxConexion' style="position: fixed; bottom: 0; font-size: 10px; padding-left: 10px; background-color: rgba(240, 248, 255, 0.75);">
                    </div>
                    <!-- PAGE CONTENT ENDS --
							</div>
						</div><-- /.row -->
                </div><!-- /.page-content -->
            </div>
        </div><!-- /.main-content -->

    </div><!-- /.main-container -->
    <!-- basic scripts -->
    <script type="text/javascript">
        if ('ontouchstart' in document.documentElement) document.write(
            "<script src='../../skins/js/jquery.mobile.custom.js'>" + "<" + "/script>");
    </script>
    <script src="../../framework/jquery/bootstrap/bootstrap-3.3.5/js/bootstrap.custom.min.js"></script>
    <script src="../../framework/jquery/bootstrap/bootstrap-3.3.5/js/tooltip.js"></script>
    <script src="../../framework/jquery/bootstrap/bootstrap-3.3.5/js/popover.js"></script>
    <!--[if lte IE 8]><script src="../../framework/plugins/compatibility/excanvas/excanvas.js"></script><![endif]-->
    <script src="../../skins/js/jquery-ui.custom.js"></script>
    <script src="../../skins/js/jquery.ui.touch-punch.js"></script>
    <!-- ace scripts -->
    <script src="../../skins/js/ace/ace.js"></script>
    <script src="../../skins/js/ace/ace-elements.js"></script>
    <!-- inline scripts related to this page -->
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> 
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> 
    <style>
        .chosen-container-single .chosen-search:after {
            content: ''
        }

        .chosen-single.form-control {
            border-radius: 0 !important;
        }
    </style>
    <script>
    $(()=>{
      const keepAlive=()=>{setTimeout(()=>{$.post('<?php //echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',{keepAlive:true},r=>{},'json').always(r=>{keepAlive();});},60000);};
      keepAlive();
    });
    </script>
    <?php
    if ($Ses_Usu_Tip != 'C') {
        $soloUsers = true;
        //require_once("../../mascaras/model1/estilos/jqueryChat.php");
        //include("adm_con_online_2.0.php"); ?>
        <!--<link rel="stylesheet" href="../../framework/jquery/ChatJs/css/jquery.chatjs.css" />
        <style>
            .chat-window-title.decored {
                background: -webkit-linear-gradient(top, #87add4 0%, #1d354d 100%);
                background: linear-gradient(to bottom, #87add4 0%, #1d354d 100%);
            }

            .chat-window-title {
                color: #eab2b2;
                text-shadow: #6d2020 1px 1px 1px;
            }
        </style>
        <script type="text/javascript">
            var adapter = new DemoAdapter('<?php //echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>');
            DemoAdapterConstants.DEFAULT_ROOM_ID = '<?php //echo '1'; //$Ses_Emp_Cod; ?>';
            DemoAdapterConstants.DEFAULT_ROOM_NAME = '<?php //echo $Ses_Emp_Nom; ?>';
            DemoAdapterConstants.CURRENT_USER.Id = '<?php //echo $Ses_Prs_Cod; ?>';
            DemoAdapterConstants.CURRENT_USER.Name = "<?php //echo (isset($nombre) && isset($nombre[0]) ? $nombre[0] : '') . ' ' . (isset($apellido) && isset($apellido[0]) ? $apellido[0] : ''); ?>";
            DemoAdapterConstants.CURRENT_USERS_ONLINE = <?php //echo json_encode($response['users']); ?>;
            $(function() {
                $.chat({
                    userId: '<?php //echo $Ses_Prs_Cod; ?>', // your user information
                    roomId: '<?php //echo '1'; //$Ses_Emp_Cod; ?>', // id of the room. The friends list is based on the room Id
                    chatJsContentPath: '/chatjs/ChatJs/', // the adapter you are using
                    adapter: adapter
                });
            });
        </script>-->
    <?php } ?>
    <div id="modalAlert" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="border-top-left-radius:12px; border-top-right-radius:12px;">
                <div class="modal-header" style="background: #a02525;border-top-left-radius:12px; border-top-right-radius:12px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" 
                        style="background: #fff; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border: none; padding: 0;">
                        <span aria-hidden="true" style="color: #fff; background: #000; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: bold;">&times;</span>
                    </button>
                    <h4 class="modal-title white"><i class="ace-icon fa fa-exclamation-triangle"></i>&nbsp;&nbsp;<b id="alertTitle">Alerta</b></h4>
                </div>
                <div class="modal-body">
                    <h4 id="alertBody" class="bolder blue">probar</h4>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-xs btn-info" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <div id="modalAlertPrinter" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="border-top-left-radius:12px; border-top-right-radius:12px;">
                <div class="modal-header" style="background: #a02525; border-top-left-radius:12px; border-top-right-radius:12px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title white">
                        <i class="ace-icon glyphicon glyphicon-print"></i>&nbsp;&nbsp;
                        <b>IP/Puerto Servidor de Impresoras</b>
                    </h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal">
                        <!-- Prepended text-->
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="Usu_Ced">IP:</label>
                            <div class="col-sm-5">
                                <input id="Ip_Printer" value="127.0.0.1" class="form-control" placeholder="" type="text">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="Usu_Ced">IP:</label>
                            <div class="col-sm-5">
                                <input id="Port_Printer" value="80" class="form-control" placeholder="" type="text">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="setPrintersIp($('#Ip_Printer').val(),$('#Port_Printer').val())" data-dismiss="modal" class="btn btn-xs btn-primary">Guardar</button>
                    <button type="button" class="btn btn-xs btn-danger" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- ENCABEZADO -->
                <div class="modal-header">
                    <div class="header-left-content">
                        <div class="header-icon-box">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="header-title-box">
                            <h4>Cambiar empresa</h4>
                            <p>Seleccione la empresa con la que desea trabajar.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close-custom" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x"></i>
                    </button>
                </div>

                <!-- CUERPO -->
                <div class="modal-body">
                    <form id="loginChange" autocomplete="off">
                        <!-- Campo oculto Cédula -->
                        <input type="hidden" id="Usu_Ced" name="user_name" value="<?php echo $Ses_Usu_Ced; ?>" />
                        <!-- Campo oculto Sucursal -->
                        <input type="hidden" id="Suc_Cod" name="Suc_Cod" value="<?php echo $Ses_Suc_Cod; ?>" />

                        <div class="row">
                            <!-- COLUMNA IZQUIERDA -->
                            <div class="col-12 col-md-4 mb-3 mb-md-0" style="padding-right: 15px;">
                                <!-- 1. Label Usuario actual -->
                                <div class="section-label-brand">
                                    <i class="bi bi-person"></i> Usuario actual
                                </div>
                                <!-- 2. Avatar + Nombre + Cédula -->
                                <div class="user-profile-box">
                                    <div class="user-avatar">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div class="user-info-text">
                                        <div class="user-name"><?php echo (isset($nombre[0]) ? $nombre[0] : '') . ' ' . (isset($apellido[0]) ? $apellido[0] : ''); ?></div>
                                        <div class="user-id"><?php echo $Ses_Usu_Ced; ?></div>
                                    </div>
                                </div>

                                <!-- 3. Separador -->
                                <div class="divider-subtle"></div>

                                <!-- 4. Label Empresa actual -->
                                <div class="section-label-brand">
                                    <i class="bi bi-building"></i> Empresa actual
                                </div>
                                <!-- 5. Card Empresa Actual -->
                                <div class="company-card-active">
                                    <i class="bi bi-building-fill"></i>
                                    <div>
                                        <div class="company-card-name"><?php echo $Ses_Emp_Nom; ?></div>
                                        <div class="company-card-sub"><?php echo isset($Ses_Suc_Nom) ? strtoupper($Ses_Suc_Nom) : 'Matriz'; ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- COLUMNA DERECHA -->
                            <div class="col-12 col-md-8" style="padding-left: 15px;">
                                <!-- 1. form-label Empresa -->
                                <div class="field-label mb-1">
                                    <i class="bi bi-building"></i> Empresa
                                </div>
                                <!-- Select con select2 -->
                                <select id="Emp_Cod" name="Emp_Cod" class="form-control" style="width: 100%;">
                                    <option value="" disabled selected>Seleccione la empresa...</option>
                                    <?php foreach ($rs_empresas as $row_rs_empresas) {
                                        $isCurrent = ($row_rs_empresas['Emp_Cod'] == $Ses_Emp_Cod && $row_rs_empresas['Suc_Cod'] == $Ses_Suc_Cod);
                                        if ($isCurrent) {
                                            continue; // Excluir la empresa actualmente logueada
                                        }
                                        echo '<option value="' . $row_rs_empresas['Emp_Cod'] . '" data-suc_cod="' . $row_rs_empresas['Suc_Cod'] . '">' . htmlspecialchars($row_rs_empresas['Emp_Cor']) . ' (' . utf8_encode($row_rs_empresas['Suc_Des']) . ')</option>';
                                    } ?>
                                </select>

                                <!-- 4. form-label Contraseña -->
                                <div class="field-label mb-1 mt-3" style="margin-top: 14px;">
                                    <i class="bi bi-lock"></i> Contrase&ntilde;a
                                </div>
                                <!-- 5. input-group contraseña + ojo toggle -->
                                <div class="password-group">
                                    <input id="Usu_Pas" name="encryptor" class="form-control" type="password" placeholder="••••••••" required="true" autofocus="true" onkeypress="if (event.keyCode===13){loginAjax();return false;}">
                                    <button type="button" class="btn-toggle-pwd" id="toggle_password_btn" title="Mostrar/ocultar contraseña">
                                        <i class="bi bi-eye" id="toggle_password_icon"></i>
                                    </button>
                                </div>

                                <div id="msgAlert" style="margin-top: 8px; min-height: 20px;"></div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-cancel" data-dismiss="modal" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" onclick="loginAjax()" class="btn btn-brand-submit">
                        <i class="bi bi-arrow-repeat"></i> Cambiar empresa
                    </button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <script type="text/javascript">
        var socketVentanas;
        var Ses_Emp_Cod = <?php echo $Ses_Emp_Cod; ?>,
            Ses_Suc_Cod = <?php echo $Ses_Suc_Cod; ?>,
            Ses_Usu_Cod = <?php echo $Ses_Usu_Cod; ?>,
            Ses_Prs_Cod = <?php echo $Ses_Prs_Cod; ?>,
            Ses_Bd_Nom = <?php echo "'" . $bd_nombre . "'"; ?>;

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

        function setPrintersIp(ip, port) {
            var local = $.getLocalStore("printers") || {};
            local['ip_printers'] = ip || "127.0.0.1";
            local['port_printers'] = port || "80";
            $.setLocalStore("printers", local);
            loadPrinters();
        }

        function loadPrinters() {
            var local = $.getLocalStore("printers") || {};
            var ip_printers = local['ip_printers'] || "127.0.0.1",
                port_printers = local['port_printers'] || '80';
            var link = "http://" + ip_printers + ":" + port_printers + "/exa/printers/getPrinters.php";
            $.setLocalStore('printers', undefined);
            /* 
            // Comentado temporalmente por error CORS y falta de uso
            $.get(link, function(data) {
                if (data.success === true) {
                    if (Ses_Prs_Cod === 1) //console.log(data);
                    $.setLocalStore('printers', {
                        has_printers: data.printers.length > 0,
                        ip_printers: ip_printers,
                        port_printers: port_printers,
                        printers: data.printers
                    });
                }
            }, 'json');
            */
        }
    </script>
    <script type="text/javascript">
        // Cambiado x Erik xq lo anterior mo era funcional
        <?php if ($_SESSION['Ses_Usu_Cad'] == 'N') { ?>
            var Ses_Sys_Tim = '0<?php echo strtotime(date('Y-m-d H:i:s')) - strtotime($Ses_Sys_Tim); ?>' * 1;
            setInterval(function() {
                var s = Ses_Sys_Tim,
                    h = Math.floor(s / 3600),
                    m = Math.floor(s / 60) - (h * 60);
                s = Math.floor(s - (h * 3600) - (m * 60));
                $('#ajaxConexion').html('<b>Online:</b> ' + Math.abs(h) + 'hrs ' + Math.abs(m) + 'min ' + Math.abs(s) +
                    'seg');
                Ses_Sys_Tim += 1;
            }, 1000);
        <?php
        } ?>

        function openAlert(title, body) {
            $('#alertTitle').html(title);
            $('#alertBody').html(body);
            $('#modalAlert').modal("show");
        }

        function resizeMain() {
            $('#contenido').css('min-height', (window.innerHeight - 50 + 5) + 'px');
        }
        $(window).on('resize', resizeMain);

        function setSucu(Suc_Cod, Suc_Nom) {
            $.post("<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", {
                setSucu: true,
                Suc_Cod: Suc_Cod,
                Suc_Nom: Suc_Nom,
                user_name: $('#Usu_Ced').val()
            }, function(response) {
                if (response['success'] === true) {
                    window.location.href =
                        "<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>";
                } else {
                    openAlert('ERROR SISTEMA: Usuarios',
                        'No se logro cambiar de <b class="green">SUCURSAL</b>!<br/><br/><span class="grey">Revise el acceso de su usuario a la Sucursal ' +
                        Suc_Nom + ".</span>"
                    ); /*$msg='<div class="alert alert-warning fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[ERROR]</strong> &nbsp;&nbsp;Contrase&ntilde;a Incorrecta.</div>';*/
                }
            }, 'json').fail(function(error) {
                openAlert('ERROR SISTEMA', 'No se logro conectar con el <b class="green">SERVIDOR</b>!');
            }).always(function() {});
        }

        // function loginAjax() {
        //     var $msg;
        //     $.post("<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", {
        //             loginAjax: true,
        //             Emp_Cod: $('#Emp_Cod').val(),
        //             Suc_Cod: $('#Emp_Cod option:selected').data('Suc_Cod'),
        //             user_name: $('#Usu_Ced').val(),
        //             encryptor: md5($('#Usu_Pas').val())
        //         }, function(response) {
        //             if (response['success'] === true) {
        //                 $msg =
        //                     '<div class="alert alert-success fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[SISTEMA]</strong> &nbsp;&nbsp;Login Correcto. Direccionando....</div>';
        //                 setTimeout(function() {
        //                     window.location.href =
        //                         "<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>";
        //                 }, 2500);
        //             } else {
        //                 $msg =
        //                     '<div class="alert alert-warning fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[ERROR]</strong> &nbsp;&nbsp;Contrase&ntilde;a Incorrecta.</div>';
        //             }
        //         }, 'json').fail(function(error) {
        //             $msg =
        //                 '<div class="alert alert-danger fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[ERROR]</strong> &nbsp;&nbsp;El Servidor ha fallado en responder!.</div>';
        //         })
        //         .always(function() {
        //             $('#msgAlert').html($msg);
        //             $('#msgAlert .alert').hide();
        //             $('#msgAlert .alert').show();
        //             setTimeout(function() {
        //                 $('#msgAlert .alert').removeClass('in').addClass('out');
        //             }, 4000);
        //         });
        // }
        // $(document).ready(function() {
        //     $('[data-tooltip="tooltip"]').tooltip({
        //         container: 'body'
        //     });
        //     $('#Emp_Cod').chosenDesc({
        //         width: '100%',
        //         template: function(t, d) {
        //             return '<div class="over"><b>' + t + '</b></div><div class="over desc">' + d[
        //                 'emp_nom'] + '</div>';
        //         }
        //     });
        //     $("#Emp_Cod_chosen").addClass('bs-chosen').find('.chosen-single').addClass('form-control');
        //     if (ace.cookie.get('ace_tree') === 'true') {
        //         $('.ace-settings-con').hide();
        //         $('#sidebar').css({
        //             'border-right-width': '1px',
        //             'border-right-style': 'solid'
        //         });
        //         $('#nav-tree .treeMenuDefault nobr:first-child').on('mousedown', function() {
        //             $('.sidebar[data-sidebar-scroll=true]').ace_sidebar_scroll('reset');
        //         });
        //     };
        //     $('.menu-link').on('click', function() {
        //         $('.menu-link').parent().removeClass('active');
        //         $('ul.highlight').removeClass('highlight');
        //         $(this).parent().addClass('active').parent().addClass('highlight');
        //     });
        //     loadPrinters();
        //     /* socketVentanas = new SocketVentanas();
        //     socketVentanas.setMain();
        //     socketVentanas.connectDefault();
        //     setTimeout(function() {
        //         socketVentanas.send('login');
        //     }, 1000); */
        // });
        function loginAjax() {
            var empCod = $('#Emp_Cod').val();
            var $msg;
            if (!empCod) {
                $msg = '<div class="alert alert-warning fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[ADVERTENCIA]</strong> &nbsp;&nbsp;Por favor seleccione la empresa a la que desea cambiar.</div>';
                $('#msgAlert').html($msg);
                $('#msgAlert .alert').hide().fadeIn();
                setTimeout(function() {
                    $('#msgAlert .alert').removeClass('in').addClass('out');
                }, 4000);
                return;
            }
            $.post("<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", {
                    loginAjax: true,
                    Emp_Cod: empCod,
                    Suc_Cod: $('#Emp_Cod option:selected').data('suc_cod') || $('#Emp_Cod option:selected').data('Suc_Cod'),
                    user_name: $('#Usu_Ced').val(),
                    encryptor: md5($('#Usu_Pas').val())
                }, function(response) {
            if (response['success'] === true) {
                $msg =
                '<div class="alert alert-success fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[SISTEMA]</strong> &nbsp;&nbsp;Login Correcto. Direccionando....</div>';
                setTimeout(function() {
                window.location.href =
                    "<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>";
                }, 2500);
            } else {
                $msg =
                '<div class="alert alert-warning fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[ERROR]</strong> &nbsp;&nbsp;Contrase&ntilde;a Incorrecta.</div>';
            }
            }, 'json').fail(function(error) {
            $msg =
                '<div class="alert alert-danger fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[ERROR]</strong> &nbsp;&nbsp;El Servidor ha fallado en responder!.</div>';
            })
            .always(function() {
                $('#msgAlert').html($msg);
                $('#msgAlert .alert').hide();
                $('#msgAlert .alert').show();
                setTimeout(function() {
                $('#msgAlert .alert').removeClass('in').addClass('out');
                }, 4000);
            });
        }
        $(document).ready(function() {
            $('[data-tooltip="tooltip"]').tooltip({
            container: 'body'
            });

            // Cambia chosen por select2
            $('#Emp_Cod').select2({
            width: '100%',
            placeholder: 'Seleccione Empresa...',
            dropdownParent: $('#myModal'), // <-- Esto es importante para modales Bootstrap
            templateResult: function (data) {
                if (!data.id) return data.text;
                var empNom = $(data.element).data('emp_nom');
                if (empNom) {
                return $('<span><b>' + data.text + '</b><br><span style="font-size:11px;color:black;">' + empNom + '</span></span>');
                }
                return data.text;
            },
            templateSelection: function (data) {
                return data.text;
            }
            });

            // Solución para el bug de select2 en modales Bootstrap: NO forzar el foco en el input de búsqueda
            $('#myModal').on('shown.bs.modal', function () {
            // Ya no se abre automáticamente el select2
            // Si quieres abrirlo manualmente, puedes hacerlo con $('#Emp_Cod').select2('open');
            });

            if (ace.cookie.get('ace_tree') === 'true') {
            $('.ace-settings-con').hide();
            $('#sidebar').css({
                'border-right-width': '1px',
                'border-right-style': 'solid'
            });
            $('#nav-tree .treeMenuDefault nobr:first-child').on('mousedown', function() {
                $('.sidebar[data-sidebar-scroll=true]').ace_sidebar_scroll('reset');
            });
            };
            $('.menu-link').on('click', function() {
            $('.menu-link').parent().removeClass('active');
            $('ul.highlight').removeClass('highlight');
            $(this).parent().addClass('active').parent().addClass('highlight');
            });
            loadPrinters();
            /* socketVentanas = new SocketVentanas();
            socketVentanas.setMain();
            socketVentanas.connectDefault();
            setTimeout(function() {
            socketVentanas.send('login');
            }, 1000); */
        });
        ace.vars['base'] = '..';

        //NUEVAS FUNCIONES PARA APARECER EL MODAL DE TICKETS (WB)

        /* document.getElementById('notificaciones_trigger').addEventListener('click', function() {
             var modal = document.getElementById('modal_notificacion');
             if (modal.style.display === 'none' || modal.style.display === '') {
                 modal.style.display = 'block';
             } else {
                 modal.style.display = 'none';
             }
         });*/

        <?php if ($esAdministradorSistemas) { //Solo administrador de sistemas puede ver este apartado 

        ?>
            // //NUEVAS FUNCIONES PARA APARECER EL MODAL DE TICKETS (WB)
            document.getElementById('notificaciones_trigger').addEventListener('click', function() {
                var modal = document.getElementById('modal_notificacion');
                if (modal.style.display === 'none' || modal.style.display === '') {
                    modal.style.display = 'block';
                } else {
                    modal.style.display = 'none';
                }
            })
        <?php
        } else {
        ?> //-Nuevo item para verificar si existen tickets de documentos por autorizar-- >
            // //NUEVAS FUNCIONES PARA APARECER EL MODAL DE DOCUMENTOS POR AUTORIZAR(WB)
            document.getElementById('documentos_trigger').addEventListener('click', function() {
                var modal = document.getElementById('modal_documentos');
                if (modal.style.display === 'none' || modal.style.display === '') {
                    modal.style.display = 'block';
                } else {
                    modal.style.display = 'none';
                }
            });
        <?php  }
        // 
        ?>
    </script>
    <script src="../../skins/js/ace/ace.settings.js"></script>
    <script src="../../skins/js/ace/ace.settings-skin.js"></script>
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <!-- <script src="../../framework/php/ventanasSocket/socketExaVentanas.js"></script> -->
    <?php //var_dump($rs_sucursales); 
    ?>
</body>

</html>


<!-- Aqui el nuevo codigo -->
<!-- <script>

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
</script> -->

<!-- Aqui el nuevo codigo -->
<script>
    // Permite arrastrar y soltar los tabs para reordenarlos, excepto el primero (index.html)
    $(function () {
        $("#tabs").sortable({
            axis: "x",
            items: "> li:not(:first-child)", // No permite mover la primera pestaña
            tolerance: "pointer",
            start: function (event, ui) {
                // Evita que el tab de Home sea arrastrado
                if (ui.item.index() === 0) {
                    return false;
                }
            }
        });
    });

    // Evita que se agreguen pestañas antes de la de inicio
    function abrirFormularioEnTab(titulo, url) {
        const tabId = 'tab_' + btoa(url).replace(/=/g, '');
        if (document.getElementById(tabId)) {
            activarTab(tabId);
            return;
        }
        // Crear la pestaña
        const li = document.createElement('li');
        li.className = 'nav-item';
        li.innerHTML = `<a style="border-radius: 3px !important;margin: 0 1px;padding: 1px 3px;background:#f8f8f8;border:1px solid #8db2e3;font-size:11px" class="nav-link d-flex align-items-center justify-content-between active" href="#" 
        onclick="activarTab('${tabId}')"> <i class="glyphicon glyphicon-modal-window"></i>   <span style="color:#585858;">
        </i>  ${titulo}</span> <i class="fa fa-times ms-2 text-primary" onclick="cerrarTab('${tabId}'); 
        event.stopPropagation();" style="cursor:pointer;font-size: 10px; border:0px solid; padding: 2px; border-radius: 3px;  color:#999999; background: #f8f8f8;"></i></a>`;
        // Insertar después de la última pestaña (al final)
        document.getElementById('tabs').appendChild(li);

        // Crear el iframe
        const iframe = document.createElement('iframe');
        iframe.id = tabId;
        iframe.src = url;
        // iframe.style = 'width:100%; height:700px; border:none; display:none;';
        // document.getElementById('iframes').appendChild(iframe);
        
        // Hacemos que el iframe ocupe todo el espacio disponible y permita scroll
        iframe.style = 'width:100%; height:100%; border:none; display:none; overflow:auto;';
        iframe.setAttribute('scrolling', 'auto');
        iframe.setAttribute('allowfullscreen', 'true');
        iframe.className = 'tab-iframe';
        document.getElementById('iframes').appendChild(iframe);

        ajustarAlturaIframes();
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
        ajustarAlturaIframes();
    }

    function cerrarTab(tabId) {
        document.getElementById(tabId)?.remove();
        // Buscar el <li> correspondiente a la pestaña y eliminarlo
        const tabLi = [...document.querySelectorAll('#tabs li')].find(li => li.innerHTML.includes(tabId));
        if (tabLi) tabLi.remove();
        // Activar la última pestaña (siempre dejando la de inicio como primera)
        const iframes = document.querySelectorAll('#iframes iframe');
        if (iframes.length) {
            activarTab(iframes[iframes.length - 1].id);
        } else {
            activarTab('contenido'); // Si no hay más, activa la de inicio
        }
        ajustarAlturaIframes();
    }

    // Ajusta la altura de los iframes para que siempre ocupen el espacio disponible y permitan scroll
    function ajustarAlturaIframes() {
        const container = document.getElementById('iframes');
        if (!container) return;
        // Calcula la altura disponible restando la altura de los tabs
        const tabs = document.getElementById('tabs');
        let tabsHeight = tabs ? tabs.offsetHeight : 0;
        let windowHeight = window.innerHeight;
        // Ajusta según el layout de tu sistema, aquí restamos 55px por el navbar y paddings
        let disponible = windowHeight - tabsHeight - 55;
        if (disponible < 300) disponible = 300;
        document.querySelectorAll('#iframes iframe').forEach(iframe => {
            iframe.style.height = disponible + 'px';
            iframe.style.overflow = 'auto';
        });
        container.style.height = disponible + 'px';
        container.style.overflow = 'auto';
    }

    window.addEventListener('resize', ajustarAlturaIframes);
    document.addEventListener('DOMContentLoaded', ajustarAlturaIframes);

    // Manejo de clicks en enlaces del menú para abrir en pestaña
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
</script>

<style>
    #iframes {
        width: 100%;
        height: calc(100% - 40px);
        overflow: auto;
        position: relative;
    }
    #iframes iframe.tab-iframe {
        width: 100% !important;
        min-height: 300px;
        border: none;
        background: #fff;
        overflow: auto;
        display: none;
    }
    #iframes iframe.tab-iframe[style*="display: block"] {
        display: block !important;
    }
    #tabs .nav-link.active {
        background-color: #d9edf7 !important;
        border-color: #31708f !important;
        color: #31708f !important;
        font-weight: bold;
    }
    #tabs .nav-link span {
        transition: color 0.3s ease;
    }
    #tabs .nav-link.active span {
        color: #31708f !important;
    }
</style>

