<?php 
/**
Descripci�n: P�gina de inicio del sistema inform�tico EXA
Fecha de creaci�n:	2016-12-28
Desarrollador:	Erik Niebla
*/
require_once('../../Librerias/procedimientos/almacenados_standar.php'); 
require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/fac_log_electronica.php');

if(!isset($Prs_Cod) || !isset($Emp_Cod) ) header('Location: '.'../index.php');

$obBD_conexion1 = new Class_Log_Conexion_Elect; //Creacion del Objeto de conexion 
$obBD_con1 =  new Class_Log_Datos_Elect; //Creaci�n del objeto mysql para las consultas 
$rs_empresas = $obBD_con1->getRowConsulta(1, trim($Emp_Cod), $obBD_conexion1); //consulta base de datos

if( !isset($rs_empresas['Emp_Cod']) || empty($rs_empresas['Emp_Cod']) ) header('Location: '.'../index.php');
$obBD_conexion1 = new Class_Log_Conexion_Elect($rs_empresas['Dat_Dis']);
$rs_persona = $obBD_con1->getRowConsulta(7, trim($Prs_Cod), $obBD_conexion1); //consulta base de datos
$rs_cliente = $obBD_con1->getRowConsulta(2, trim($Prs_Cod).'*'.trim($Emp_Cod), $obBD_conexion1); //consulta base de datos
$rs_proveed = $obBD_con1->getRowConsulta(6, trim($Prs_Cod).'*'.trim($Emp_Cod), $obBD_conexion1); //consulta base de datos
$rs_empresa = $obBD_con1->getRowConsulta(5, trim($Emp_Cod), $obBD_conexion1);
$apellido = explode(' ', $rs_persona['Prs_Ape']); 
$nombre = explode(' ', $rs_persona['Prs_Nom']); 
$link="Emp_Cod=$Emp_Cod&Cli_Cod=$rs_cliente[Cli_Cod]&";
$_SESSION['Ses_Cli_Prs_Cod']=$Prs_Cod;
$_SESSION['Ses_Cli_Emp_Cod']=$Emp_Cod;
$_SESSION['Ses_Cli_Emp_Log']=$rs_empresa['Emp_Log'];
$_SESSION['Ses_Cli_Dat_Dis']=$rs_empresas['Dat_Dis'];
$_SESSION['Ses_Cli_Cod']=$rs_cliente['Cli_Cod'];
$_SESSION['Ses_Prv_Cod']=$rs_proveed['Prv_Cod'];
$_SESSION['Ses_Sys_Nom']='EXA - Software Contable';

//$obBD_con1->echoLog($_SESSION);
?>
<!DOCTYPE html>
<html lang="es">
	<head>		
		<title><?Php echo $Ses_Sys_Nom; ?></title>
                <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="iso8859-1" />
		
		<meta name="description" content="overview &amp; stats" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
		<link rel="shortcut icon" type="image/x-icon" href="../../mascaras/model1/img/logo/exa-ico-2.png" />
		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap.min.css" />
                <link rel="stylesheet" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/tooltip.min.css" />
		<link rel="stylesheet" href="../../framework/plugins/fonts/font-awesome/font-awesome-4.4.0/css/font-awesome.min.css" />
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
                <style>
                    .no-skin .nav-list > li.active:hover > a, .no-skin .nav-list .open > a, .no-skin .nav-list .open > a:hover, .no-skin .nav-list .open > a:focus{color:#2b7dbc;}
                </style>
	</head>
	<body class="<?php echo (isset($_COOKIE['ace_skin'])?$_COOKIE['ace_skin']:'no-skin') ?>" onLoad="resizeMain()">
		<!-- #section:basics/navbar.layout -->
		<div id="navbar" class="navbar navbar-default navbar-fixed-top">
			<script type="text/javascript">try{ace.settings.check('navbar' , 'fixed');}catch(e){}</script>
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
					<div class="navbar-brand" style="padding-top:2px;padding-bottom:2px;padding-right:0;">
                                            <img style="height:40px; display: inline" src="../../skins/img/logoexa.png" data-tooltip="tooltip" data-placement="right" title="EXA - Software Contable"/><!--Ace Admin-->
                                            <small style="font-size: 14px;"><?php echo '<span>'.ucwords(strtolower($rs_empresas['Emp_Nom'])).'</span>';?></small>
                                            
					</div> 
					<!-- /section:basics/navbar.layout.brand -->
					<!-- #section:basics/navbar.toggle -->
					<!-- /section:basics/navbar.toggle -->
				</div>
				<!-- #section:basics/navbar.dropdown -->
				<div class="navbar-buttons navbar-header pull-right" role="navigation">
					<ul class="nav ace-nav">		
						<!-- #section:basics/navbar.user_menu -->
                                                <li class="red-social r5" data-tooltip="tooltip" data-placement="bottom" title="Facebook"><a href="https://fb.com/Ofsercont/" target="blank"><i class="ace-icon fa fa-facebook"></i></a></li>
                                                <li class="red-social r4" data-tooltip="tooltip" data-placement="bottom" title="Instagram"><a href="https://www.instagram.com/ofsercontsa/" target="blank"><i class="ace-icon fa fa-instagram"></i></a></li>
                                                <li class="red-social r3" data-tooltip="tooltip" data-placement="bottom" title="Youtube"><a href="https://www.youtube.com/channel/UCM85YafBv-1PZZ5FkzTxZbw?disable_polymer=true" target="blank"><i class="ace-icon fa fa-youtube"></i></a></li>
                                                <li class="red-social r2" data-tooltip="tooltip" data-placement="bottom" title="Twitter"><a href="https://twitter.com/OFSERCONTSA1?lang=es" target="blank"><i class="ace-icon fa fa-twitter"></i></a></li>
                                                <li class="red-social r1 grey" data-tooltip="tooltip" data-placement="bottom" title="�Quienes somos?"><a href="../../skins/html/ACERCA-DE-EXA1.html" target="contenido"><i class="ace-icon fa fa-suitcase"></i></a></li>
						<li class="light-blue user-links">
							<a data-toggle="dropdown" href="#" class="dropdown-toggle"><!--<img class="nav-user-photo" src="../../skins/avatars/user.jpg" alt="Jason's Photo" />-->
								<span class="user-info"><small>Bienvenido,</small> <?Php echo $nombre[0].' '.$apellido[0]; ?></span><i class="ace-icon fa fa-caret-down"></i>
							</a>
							<ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
								<li><a class="ace-settings-btn" ><i class="ace-icon fa fa-cog"></i>Configuraci�n</a></li>                                                                                                                                
								<li class="divider"></li>
								<li><a href="../../administrador/LOGICA/logout.php"><i class="ace-icon fa fa fa-sign-out"></i>Cerrar Sesi�n</a></li>
							</ul>
						</li><!-- /section:basics/navbar.user_menu -->
					</ul>
				</div><!-- /section:basics/navbar.dropdown -->
			</div><!-- /.navbar-container -->
		</div><!-- /section:basics/navbar.layout -->
		<div class="main-container" id="main-container">
			<script type="text/javascript">try{ace.settings.check('main-container' , 'fixed');}catch(e){}</script>
			<!-- #section:basics/sidebar -->
			<div id="sidebar" class="sidebar responsive sidebar-fixed sidebar-scroll <?php echo (!isset($_COOKIE['ace_compact'])||$_COOKIE['ace_compact']=='true'||$_SESSION['Ses_Usu_Men'] != 'T'?'compact':''); ?>">
				<script type="text/javascript">try{ace.settings.check('sidebar' , 'fixed');}catch(e){}</script>                                
                                <div class="sidebar-shortcuts" id="sidebar-shortcuts"></div>
                                
                                
                                <ul class="nav nav-list">
<li  class="hover " >
    <a class="menu-link"  target="contenido" href="<?php echo (isset($Doc_Cod)&&isset($type))?"../FRONT/doc_elect.php?{$link}Doc_Cod={$Doc_Cod}&type=$type":"../../skins/html/index.html"; ?>">
        <i class="menu-icon fa fa-tachometer"></i>
        <span class="menu-text">Inicio</span>
        <b class="arrow fa fa-angle-down"></b>
    </a>
 </li>  
 <li>
    <a class="menu-link"  target="contenido" href="<?php ?>../FRONT/documentos.php?type=VENTAS">
        <i class="menu-icon fa fa-file-text-o"></i>
        <span class="menu-text">Facturas</span>
        <b class="arrow fa fa-angle-down"></b>
    </a>
 </li> 
 <li>
    <a class="menu-link"  target="contenido" href="<?php ?>../FRONT/documentos.php?type=RETENC">
        <i class="menu-icon fa fa-list-alt"></i>
        <span class="menu-text">Retenciones</span>
        <b class="arrow fa fa-angle-down"></b>
    </a>
 </li>   
 <li>
    <a class="menu-link"  target="contenido" href="<?php ?>../FRONT/documentos.php?type=NOTASC">
        <i class="menu-icon fa fa-random"></i>
        <span class="menu-text">N. Credito</span>
        <b class="arrow fa fa-angle-down"></b>
    </a>
 </li> 
 <li>
    <a class="menu-link"  target="contenido" href="<?php ?>../FRONT/documentos.php?type=NOTASD">
        <i class="menu-icon fa fa-bars"></i>
        <span class="menu-text">N. Debito</span>
        <b class="arrow fa fa-angle-down"></b>
    </a>
 </li>
 <li>
    <a class="menu-link"  target="contenido" href="<?php ?>../FRONT/documentos.php?type=GUIAS">
        <i class="menu-icon fa fa-truck"></i>
        <span class="menu-text">G. Remisi�n</span>
        <b class="arrow fa fa-angle-down"></b>
    </a>    
</li>    </ul>
    

				<!-- #section:basics/sidebar.layout.minimize -->
				<div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse"><i class="ace-icon fa fa-angle-double-left " data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i></div>
				<!-- /section:basics/sidebar.layout.minimize -->
				<script type="text/javascript">try{ace.settings.check('sidebar' , 'collapsed');}catch(e){}</script>
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
												<option data-skin="no-skin" value="#f63232">#438EB9</option>
												<option data-skin="skin-1" value="#19a6db">#222A2D</option>
                                                                                                <option data-skin="skin-3" value="#D0D0D0">#D0D0D0</option>
												<option data-skin="skin-2" value="#3cbe5e">#C6487E</option>
											</select>
										</div>
										<span>&nbsp; Escoge Tema</span>
									</div>
                                                                        <div class="ace-settings-item ace-settings-con">
                                                                                <input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-hover" checked=""  style="margin-right: 6px;margin-left: 4px;"/>
										<label class="lbl" for="ace-settings-hover"> Submenu al Pasar</label>
									</div>

									<div class="ace-settings-item ace-settings-con">
										<input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-compact" checked=""  style="margin-right: 6px;margin-left: 4px;"/>
										<label class="lbl" for="ace-settings-compact"> Men� Compacto</label>
									</div>

                                                                        <div class="ace-settings-item ace-settings-con" style="display:none;">
										<input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-highlight"  style="margin-right: 6px;margin-left: 4px;"/>
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
								<!-- PAGE CONTENT BEGINS -->
                                                <iframe align="left" name="contenido" height="100%" width="100%" id="contenido"  frameborder="0" class="contenido" src="<?php echo (isset($Doc_Cod)&&isset($type))?"../FRONT/doc_elect.php?{$link}Doc_Cod={$Doc_Cod}&type=$type":"../../skins/html/index.html"; ?>"  style="height: 100%;padding-top:5px;"></iframe>
                                                <div id='ajaxConexion' style="position: fixed; bottom: 0; font-size: 10px; padding-left: 10px; background-color: rgba(240, 248, 255, 0.75);"></div>
								<!-- PAGE CONTENT ENDS --
							</div>
						</div><!-- /.row -->
					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

		</div><!-- /.main-container -->
                <!--[if !IE]> --><script type="text/javascript">window.jQuery || document.write("<script src='../../skins/js/jquery.js'>"+"<"+"/script>");</script><!-- <![endif]-->
		<!--[if IE]><script type="text/javascript">window.jQuery || document.write("<script src='../../skins/js/jquery1x.js'>"+"<"+"/script>");</script><![endif]-->
		<!-- basic scripts -->
		<script type="text/javascript">if('ontouchstart' in document.documentElement) document.write("<script src='../../skins/js/jquery.mobile.custom.js'>"+"<"+"/script>");</script>
		<script src="../../framework/jquery/bootstrap/bootstrap-3.3.5/js/bootstrap.custom.min.js"></script>
                <script src="../../framework/jquery/bootstrap/bootstrap-3.3.5/js/tooltip.js"></script>
                <script src="../../framework/jquery/bootstrap/bootstrap-3.3.5/js/popover.js"></script>
		<!--[if lte IE 8]><script src="../../framework/plugins/compatibility/excanvas/excanvas.js"></script><![endif]-->
		<script src="../../skins/js/jquery-ui.custom.js"></script>
		<script src="../../skins/js/jquery.ui.touch-punch.js"></script>
		<!-- ace scripts -->
		<script src="../../skins/js/ace/ace.js"></script>
                <script src="../../skins/js/ace/ace-elements.js"></script>
                          
              <script type="text/javascript">   
                function resizeMain(){ $('#contenido').css('min-height',( window.innerHeight-50)+'px'); }   $(window).on('resize' ,resizeMain);
                $(document).ready(function(){
                    $('[data-tooltip="tooltip"]').tooltip({container: 'body'});                    
                    if(ace.cookie.get('ace_tree')==='true'){ 
                        $('.ace-settings-con').hide(); $('#sidebar').css({'border-right-width':'1px','border-right-style':'solid'});  
                        $('#nav-tree .treeMenuDefault nobr:first-child').on('mousedown',function (){ $('.sidebar[data-sidebar-scroll=true]').ace_sidebar_scroll('reset'); });
                    };
                    $('.menu-link').on('click',function (){ $('.menu-link').parent().removeClass('active'); $('ul.highlight').removeClass('highlight'); $(this).parent().addClass('active').parent().addClass('highlight'); });
                }); ace.vars['base'] = '..';
             </script>   
             <script src="../../skins/js/ace/ace.settings.js"></script>
             <script src="../../skins/js/ace/ace.settings-skin.js"></script>
             <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
            <?php //var_dump($rs_sucursales); ?>
	</body>
</html>