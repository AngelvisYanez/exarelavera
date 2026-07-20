<?Php
/*
Alias:	jqgrid5
Descripción: Agrupa los estilos del plugin jQGrid, JQguery, JQuery-UI, Highlight
Fecha de Creacion:	2015-07-01
Desarrollador:	Erik Niebla
*/
?>
<meta charset="iso-8859-1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- <link rel="shortcut icon" type="image/x-icon" href="../../mascaras/model1/img/logo/exa-ico-2.png" /> logo anterior -->
    <link rel="shortcut icon" type="image/x-icon" href="../../imagenes/ingresar/favicon.png" /> <!-- logo actual -->

    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/jquery.ui/jquery-ui-1.11.4/jquery-ui.min.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/jqgrid/jqgrid-5.1.1/css/ui.jqgrid.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap-theme.min.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/jqgrid/jqgrid-5.1.1/css/ui.fix.custom.css?x=11" />
    <!--[if lte IE 8]>
    <script src="../../framework/plugins/compatibility/html5shiv/html5shiv-3.7.3.js"></script>
    <script src="../../framework/plugins/compatibility/respond/respond-1.4.2.js"></script>
    <![endif]-->
    <script type="text/ecmascript" src="../../framework/jquery/jquery.min/jquery-2.1.4.min.js"></script>
    <script type="text/ecmascript" src="../../framework/jquery/bootstrap/bootstrap-3.3.5/js/bootstrap.custom.min.js"></script>
    <script type="text/ecmascript" src="../../framework/jquery/jquery.ui/jquery-ui-1.11.4/jquery-ui.min.js"></script>
    <script type="text/ecmascript" src="../../framework/jquery/jquery.ui/jquery-ui-1.11.4/datepicker-es.js"></script>
    <script type="text/ecmascript" src="../../framework/jquery/jqgrid/jqgrid-5.1.1/js/i18n/grid.locale-es.js"></script>
    <script type="text/ecmascript" src="../../framework/jquery/jqgrid/jqgrid-5.1.1/js/jquery.jqGrid.min.js?x=12"></script>
    <script type="text/ecmascript" src="../../framework/jquery/jquery.basics-1.0.js?x=16"></script>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/plugins/fonts/font-awesome/font-awesome-4.4.0/css/font-awesome.min.css" />
    <!--[if !IE]> -->
    <link type="text/css" rel="stylesheet" href="../../framework/plugins/animate/animate-3.4.0.min.css" />
    <!-- <![endif]-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <script type="text/ecmascript" src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript">
    var socketVentanas;
    var UrlSaveJson="<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", Ses_Emp_Cod=<?php echo isset($_SESSION['Ses_Emp_Cod'])?$_SESSION['Ses_Emp_Cod']:"''"; ?>, Ses_Suc_Cod=<?php echo isset($_SESSION['Ses_Suc_Cod'])?$_SESSION['Ses_Suc_Cod']:"''"; ?>, Ses_Usu_Cod=<?php echo isset($_SESSION['Ses_Usu_Cod'])?$_SESSION['Ses_Usu_Cod']:"''"; ?>, Ses_Prs_Cod=<?php echo isset($_SESSION['Ses_Prs_Cod'])?$_SESSION['Ses_Prs_Cod']:"''"; ?>;
    $(document).ready(function () {
        //$.jgrid.defaults.styleUI='Bootstrap';
        $.jgrid.defaults.regional="es";
        $.jgrid.defaults.mtype="POST";
        $.jgrid.defaults.datatype="json";
        $(window).on('load',function(){$('.ui-pg-table table.navtable').find('td.ui-pg-button.ui-corner-all').unbind('mouseenter mouseleave').removeClass('ui-pg-button').addClass('btn btn-xs btn-success').find('.ui-pg-div span').removeClass('ui-icon').addClass('glyphicon');});
        // socketVentanas =new SocketVentanas();
        // if(!$.isEmpty(Ses_Emp_Cod)) socketVentanas.connectDefault();
     });
    </script>
    <style></style>
    <!-- INICIO J Q U E R Y    L O A D E R -->
    <link rel="stylesheet" href="../../framework/jquery/jquery.plugins/loader/jquery.loader.css" />
    <script>document.write('<div id="loader"></div>');</script>
    <script>$(document).ready(function(){jQuery("#loader").fadeOut("slow");});</script>
    <!-- <script src="/framework/php/ventanasSocket/socketExaVentanas.js"></script> -->
