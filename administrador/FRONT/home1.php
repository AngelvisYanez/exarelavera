<?php 
/**
Descripción: Página de inicio del sistema informático EXA
Fecha de creación:	2016-12-28
Desarrollador:	Erik Niebla
*/
require_once('../../Librerias/procedimientos/almacenados_standar.php'); 
require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/logica.php');
//require_once('../LOGICA/adm_log_login.php');

if(!isset($_SESSION['Ses_Lis_Per'])) header('Location: '.'../index.php');
if(isset($heartBeatChat)){ include("adm_con_online_2.0.php"); echo json_encode($response); exit(); }
if(isset($historyChat)){
    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);   
    $obBD_con1 =  new Class_Log_Datos_Adm;
    $response['history']=$obBD_con1->getArrayConsulta(216,filter_input_array(INPUT_POST), $obBD_conexion);
    $response['success']=true; echo json_encode($response); exit();
}
if (isset($signalChat)){
    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);   
    $obBD_con1 =  new Class_Log_Datos_Adm;   
    $obBD_con1->grabarv_registros(sentencias_adm(219, filter_input_array(INPUT_POST)),$obBD_conexion->conexion);	
    $response['success']=true;  echo json_encode($response); exit();
}
if (isset($ClientGuid)){
    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);   
    $obBD_con1 =  new Class_Log_Datos_Adm;   
    $obBD_con1->grabarv_registros(sentencias_adm(215, filter_input_array(INPUT_POST)),$obBD_conexion->conexion);	
    $response['success']=true; echo json_encode($response); exit();
}
if(isset($loginAjax)){    
    require('../LOGICA/adm_log_control.php');
    $obBD_conexionMaster = new Class_Log_Conexion_Cnt; // Creacion del Objeto de Conexion 
    $obBD_con =  new Class_Log_Datos_Cnt; // Creacion del Objeto de Datos    
    $row_data = $obBD_con->getRowConsulta(2, $Emp_Cod.'*'.trim($user_name), $obBD_conexionMaster); //Consulta que realiza la autenticacion del usuario     
    $obBD_conexion = new Class_Log_Conexion_Cnt($row_data['Dat_Dis']); // Conexion a la base de datos distribuida, dinamica    
    $row_rs_control = $obBD_con->getArrayConsulta(16, trim($user_name).'*'.trim($encryptor).'*'.$Emp_Cod.'*'.$Suc_Cod, $obBD_conexion); //Consulta que realiza la autenticacion del usuario 
    //var_dump($row_rs_control);
    foreach($row_rs_control AS $rowControl)
            if($rowControl['Suc_Cod']==$Suc_Cod||strtoupper($rowControl['Suc_Des'])=='MATRIZ')
                $row_rs_control=$rowControl;       
    if(isset($row_rs_control['Suc_Cod'])){
	$rs_perfiles = $obBD_con->getArrayConsulta(21, $row_rs_control["Usu_Cod"], $obBD_conexion); /* Consulta los perfiles asignados al usuario */
        foreach($rs_perfiles as $v0){
            $lperf[]=$v0["Per_Cod"];
            $Per_Des[]=$v0["Per_Des"];
	}
        /* Variables de Sesion del usuario  */	
	$_SESSION['Ses_Usu_Cod']=$row_rs_control['Usu_Cod']; 	
	$_SESSION['Ses_Usu_Ced']=$row_rs_control['Usu_Ced'];
        $_SESSION['Ses_Usu_Tip']=$row_rs_control['Usu_Tip'];
	$_SESSION['Ses_Usu_Est']=$row_rs_control['Usu_Est'];
	$_SESSION['Ses_Usu_Cad']=$row_rs_control['Usu_Cad'];
	$_SESSION['Ses_Usu_Men']=$row_rs_control['Usu_Men'];
        $_SESSION['Ses_Per_Cod']=$row_rs_control['Per_Cod'];
        /* Variable para definir la sucursal y empresa */	
	$_SESSION['Ses_Suc_Cod']=$row_rs_control['Suc_Cod'];
	$_SESSION['Ses_Suc_Nom']=$row_rs_control['Suc_Des'];	
	$_SESSION['Ses_Emp_Cod']=$row_rs_control['Emp_Cod'];
	$_SESSION['Ses_Emp_Nom']=$row_rs_control['Emp_Nom'];
	$_SESSION['Ses_Emp_Cor']=$row_rs_control['Emp_Cor'];	
	$_SESSION['Ses_Suc_Web']=$row_rs_control['Suc_Web'];	
	$_SESSION['Ses_Emp_Log']=$row_rs_control['Emp_Log'];
        /* Variables del Perfil del usuario */	
	$_SESSION['Ses_Lis_Per']=$lperf;
	$_SESSION['Ses_Per_Des']=$Per_Des; //Descripciï¿½n del perfil
        /* Variable para la base de datos del sistema local */
	$_SESSION['Ses_Dat_Dis'] = $row_data['Dat_Dis']; //Base de datos distribuida local
	$_SESSION['Ses_Dat_Aut'] = $row_data['Dat_Aut']; //Base de datos auditoria
	$_SESSION['Ses_Dat_Stg'] = $row_data['Dat_Stg']; //Base de datos storage
        $responce['success']=true;
    }else{ $responce['success']=false; }
    echo json_encode($responce); exit();
}
$apellido = explode(' ', $_SESSION['Ses_Prs_Ape']); 
$nombre = explode(' ', $_SESSION['Ses_Prs_Nom']); 

$obBD_conexion1 = new Class_Log_Conexion_Adm; //Creacion del Objeto de conexion 
$obBD_con1 =  new Class_Log_Datos_Adm; //Creación del objeto mysql para las consultas 
$rs_empresas = $obBD_con1->getArrayConsulta(213, trim($Ses_Usu_Ced), $obBD_conexion1); //consulta empresas
$rs_sucursales = $obBD_con1->getArrayConsulta(214, $Ses_Emp_Cod.'*'.$Ses_Usu_Ced, $obBD_conexion1);
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
                                            <small style="font-size: 14px;"><?php echo '<span>'.ucwords(strtolower($Ses_Emp_Nom)).'</span>'.(count($rs_sucursales)==1?' <b>['.strtoupper($Ses_Suc_Nom).']</b>':'');?></small>
                                            <?php if(count($rs_sucursales)>1){ ?>
                                                <div class="dropdown" style="display: inline">
                                                        <a id="dLabel"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="empresa"><?php echo ' ['.strtoupper($Ses_Suc_Nom).']'; ?><span class="caret"></span></a>
                                                        <ul class="dropdown-menu" aria-labelledby="dLabel"><?php foreach ($rs_sucursales as $r) {  ?>
                                                          <li><a tabindex="-1" href="#<?php echo $rowSuc['Suc_Cod']; ?>"><?php echo $rowSuc['Suc_Des']; ?></a></li>
                                                        <?php } ?> </ul>
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
						<!-- #section:basics/navbar.user_menu -->
                                                <li class="red-social r5" data-tooltip="tooltip" data-placement="bottom" title="Facebook"><a href="https://fb.com/Ofsercont/" target="blank"><i class="ace-icon fa fa-facebook"></i></a></li>
                                                <li class="red-social r4" data-tooltip="tooltip" data-placement="bottom" title="Instagram"><a href="https://www.instagram.com/ofsercontsa/" target="blank"><i class="ace-icon fa fa-instagram"></i></a></li>
                                                <li class="red-social r3" data-tooltip="tooltip" data-placement="bottom" title="Youtube"><a href="https://www.youtube.com/channel/UCM85YafBv-1PZZ5FkzTxZbw?disable_polymer=true" target="blank"><i class="ace-icon fa fa-youtube"></i></a></li>
                                                <li class="red-social r2" data-tooltip="tooltip" data-placement="bottom" title="Twitter"><a href="https://twitter.com/OFSERCONTSA1?lang=es" target="blank"><i class="ace-icon fa fa-twitter"></i></a></li>
                                                <li class="red-social r1 grey" data-tooltip="tooltip" data-placement="bottom" title="¿Quienes somos?"><a href="../../skins/html/ACERCA-DE-EXA1.html" target="contenido"><i class="ace-icon fa fa-suitcase"></i></a></li>
						<li class="light-blue user-links">
							<a data-toggle="dropdown" href="#" class="dropdown-toggle"><!--<img class="nav-user-photo" src="../../skins/avatars/user.jpg" alt="Jason's Photo" />-->
								<span class="user-info"><small>Bienvenido,</small> <?Php echo $nombre[0].' '.$apellido[0]; ?></span><i class="ace-icon fa fa-caret-down"></i>
							</a>
							<ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
								<li><a class="ace-settings-btn" ><i class="ace-icon fa fa-cog"></i>Configuración</a></li>
                                                                <li><a href="./adm_pas_usuarios_1.0.php" target="contenido" class="menu-link"><i class="ace-icon fa fa-key"></i>Cambiar Clave</a></li>
                                                                <?php if(count($rs_empresas)>1){ ?><li><a data-toggle="modal" data-target="#myModal"><i class="ace-icon fa fa-user"></i>Cambiar Empresa</a></li><?php } ?>
								<li class="divider"></li>
								<li><a href="../LOGICA/logout.php"><i class="ace-icon fa fa fa-sign-out"></i>Cerrar Sesión</a></li>
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
                                <?php //if(count($rs_empresas)>1){ ?>
				<div class="sidebar-shortcuts" id="sidebar-shortcuts">
					<div class="sidebar-shortcuts-large" id="sidebar-shortcuts-large">
                                            <button class="btn btn-success" style="display:none;"><i class="ace-icon fa fa-signal"></i></button>
                                            <button class="btn btn-info" <?php if(count($rs_empresas)==1){ ?>style="display:none;"<?php } ?> data-toggle="modal" data-target="#myModal" data-tooltip="tooltip" data-placement="right" title="Cambiar Empresa"><i class="ace-icon fa fa-sign-in"></i></button>
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
                                <?php //} ?>
                                <?php 
                                if($_SESSION['Ses_Usu_Men'] == 'B'){ ?>
                                    <script src="../LOGICA/TreeMenu.js" language="JavaScript" type="text/javascript"></script>
                                    <div class="nav nav-list" style="background:url('../../mascaras/model1/imagenes/system/main-back.png');"><div id="nav-tree" style="overflow-x: hidden;"><?php require_once("adm_con_treemenu.php"); ?></div></div>
                                    <style>.scroll-white .scroll-bar { background-color: transparent; background-color: rgba(153, 149, 215, 0.82); filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#55FFFFFF', endColorstr='#55FFFFFF',GradientType=0 ); }</style>
                                    <script type="text/javascript">if(ace.cookie.get('ace_tree')!=='true'){ ace.cookie.set('ace_compact',false); ace.cookie.set('ace_tree',true); }</script>
                                 <?php
                                }else{
                                    require_once("../LOGICA/adm_log_menu_tree.php");
                                    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);   
                                    $obBD_con1 =  new Class_Sys_Menu;                                                   
                                    echo($obBD_con1->menuToHtml(1,$obBD_con1->getMenuContainer($_SESSION['Ses_Lis_Per'],$obBD_conexion),'nav nav-list',(!isset($_COOKIE['ace_hover'])||$_COOKIE['ace_hover']=='true'||$_COOKIE['ace_compact']=='true'?'hover':'')));
                                    ?><script type="text/javascript">if(ace.cookie.get('ace_tree')==='true'){ ace.cookie.set('ace_compact',true); ace.cookie.remove('ace_tree'); }</script><?php    
                                }?>
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
										<label class="lbl" for="ace-settings-compact"> Menú Compacto</label>
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
                                                <iframe align="left" name="contenido" height="100%" width="100%" id="contenido"  frameborder="0" class="contenido" src="../../skins/html/index.html"  style="height: 100%;padding-top:5px;"></iframe>
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
		<!-- inline scripts related to this page -->
                <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
                <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
                <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
                <style>.chosen-container-single .chosen-search:after{content:''}.chosen-single.form-control{border-radius: 0 !important;}  </style>		
            <?php 
            if($Ses_Usu_Tip!='C'){ 
                $soloUsers=true; 
                require_once("../../mascaras/model1/estilos/jqueryChat.php");
                include("adm_con_online_2.0.php"); ?>
                <link rel="stylesheet" href="../../framework/jquery/ChatJs/css/jquery.chatjs.css?x=0"/>
                <style>.chat-window-title.decored {    background: -webkit-linear-gradient(top, #87add4 0%,#1d354d 100%); background: linear-gradient(to bottom, #87add4 0%,#1d354d 100%);} .chat-window-title { color: #eab2b2;text-shadow: #6d2020 1px 1px 1px;}</style>
                <script type="text/javascript">
                   var adapter=new DemoAdapter('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>');
                   DemoAdapterConstants.DEFAULT_ROOM_ID='<?php echo '1'; //$Ses_Emp_Cod; ?>';
                   DemoAdapterConstants.DEFAULT_ROOM_NAME='<?php echo $Ses_Emp_Nom; ?>';
                   DemoAdapterConstants.CURRENT_USER.Id='<?php echo $Ses_Prs_Cod; ?>';
                   DemoAdapterConstants.CURRENT_USER.Name = "<?Php echo $nombre[0].' '.$apellido[0]; ?>";
                   DemoAdapterConstants.CURRENT_USERS_ONLINE =<?php echo json_encode($response['users']); ?>;
                    $(function () {  
                        $.chat({
                            userId:'<?php echo $Ses_Prs_Cod; ?>',// your user information
                            roomId: '<?php echo '1';//$Ses_Emp_Cod; ?>',// id of the room. The friends list is based on the room Id
                            chatJsContentPath: '/chatjs/ChatJs/',// the adapter you are using
                            adapter: adapter
                        });
                    });
                    </script>
            <?php } ?>   
                    
             <div id="myModal" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <h4 class="modal-title">Cambiar Empresa</h4>
                    </div>
                    <div class="modal-body">
                      <form class="form-horizontal" id="loginChange">
                        <!-- Prepended text-->
                        <div class="form-group">
                          <label class="col-sm-2 control-label" for="Usu_Ced">C&eacute;dula:</label>
                          <div class="col-sm-5">
                              <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                <input id="Usu_Ced" name="user_name" value="<?php echo $Ses_Usu_Ced; ?>" class="form-control" placeholder="" type="text" readonly="readonly">                                
                              </div>
                          </div>                         
                        </div>
                        <!-- Prepended text-->
                        <div class="form-group">
                            <!--<input type="hidden" id="Emp_Cod" name="Emp_Cod" />-->
                            <input type="hidden" id="Suc_Cod" name="Suc_Cod" />
                          <label class="col-sm-2 control-label" for="Emp_Des">Empresa:</label>
                          <div class="col-sm-10">
                              <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-home"></i></span>
                                <select id="Emp_Cod" name="Emp_Cod" class="form-control" data-placeholder="Seleccione Empresa...">
                                    <option value=""></option>
                                    <?php foreach($rs_empresas as $row_rs_empresas){  
                                            if($row_rs_empresas['Emp_Cod']!==$Ses_Emp_Cod) echo '<option value="'.$row_rs_empresas['Emp_Cod'].'" data-Emp_Nom="'.$row_rs_empresas['Emp_Nom'].'">'.$row_rs_empresas['Emp_Cor'].'</option>';               
                                    } ?>
                                </select>
                                <!--<input id="Emp_Des" name="Emp_Des" class="form-control" placeholder="" type="text" readonly="readonly">                                -->
                              </div>
                          </div> 
                        </div>
                        <!-- Prepended text-->
                        <div class="form-group">
                          <label class="col-sm-2 control-label" for="Usu_Pas">Contraseña:</label>
                          <div class="col-sm-5">
                              <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                <input id="Usu_Pas" onkeypress="if (event.keyCode===13){loginAjax();return false;}" name="encryptor" class="form-control" placeholder="" type="password" required="true" autofocus="true">
                              </div>
                          </div>                             
                        </div>
                        <div id="msgAlert" style="height: 38px;"></div>
                       </form>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancelar</button>
                      <button type="button" onclick="loginAjax()" class="btn btn-xs btn-primary">Cambiar Empresa</button>
                    </div>
                  </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
              </div><!-- /.modal -->              
              <script type="text/javascript">        
                // Cambiado x Erik xq lo anterior mo era funcional
                <?php if ($_SESSION['Ses_Usu_Cad'] == 'N'){ ?>
                var Ses_Sys_Tim='0<?php echo strtotime(date('Y-m-d H:i:s'))-strtotime($Ses_Sys_Tim); ?>'*1;
                setInterval(function(){
                    var s=Ses_Sys_Tim, h=Math.floor(s/3600), m=Math.floor(s/60)-(h*60); s=Math.floor(s-(h*3600)-(m*60));
                    $('#ajaxConexion').html('<b>Online:</b> '+Math.abs(h)+'hrs '+Math.abs(m)+'min '+Math.abs(s)+'seg');Ses_Sys_Tim += 1;
                }, 1000); 
                <?Php } ?>
                function resizeMain(){ $('#contenido').css('min-height',( window.innerHeight-50)+'px'); }   $(window).on('resize' ,resizeMain);
                function loginAjax(){   
                    public $msg;
                    $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{loginAjax:true,Emp_Cod:$('#Emp_Cod').val(),Suc_Cod:$('#Suc_Cod').val(),user_name:$('#Usu_Ced').val(),encryptor:md5($('#Usu_Pas').val())}, function( response ) {
                        if(response['success']===true){
                             $msg='<div class="alert alert-success fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[SISTEMA]</strong> &nbsp;&nbsp;Login Correcto. Direccionando....</div>';
                             setTimeout(function (){window.location.href ="<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>";},2500);
                        }else{ $msg='<div class="alert alert-warning fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[ERROR]</strong> &nbsp;&nbsp;Contrase&ntilde;a Incorrecta.</div>';}                                   
                     },'json').fail(function(error) { $msg='<div class="alert alert-danger fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[ERROR]</strong> &nbsp;&nbsp;El Servidor ha fallado en responder!.</div>'; })
                         .always(function() {$('#msgAlert').html($msg);$('#msgAlert .alert').hide();$('#msgAlert .alert').show();setTimeout(function (){$('#msgAlert .alert').removeClass('in').addClass('out');},4000);});
                }
                $(document).ready(function(){
                    $('[data-tooltip="tooltip"]').tooltip({container: 'body'});
                    $('#Emp_Cod').chosenDesc({width:'100%',template:function (t,d){ return '<div class="over"><b>'+t+'</b></div><div class="over desc">'+d['emp_nom']+'</div>';}});
                    $("#Emp_Cod_chosen").addClass('bs-chosen').find('.chosen-single').addClass('form-control');    
                    if(ace.cookie.get('ace_tree')==='true'){ 
                        $('.ace-settings-con').hide(); $('#sidebar').css({'border-right-width':'1px','border-right-style':'solid'});  
                        $('#nav-tree .treeMenuDefault nobr:first-child').on('mousedown',function (){ $('.sidebar[data-sidebar-scroll=true]').ace_sidebar_scroll('reset'); });
                    };
                }); ace.vars['base'] = '..';
             </script>   
             <script src="../../skins/js/ace/ace.settings.js"></script>
             <script src="../../skins/js/ace/ace.settings-skin.js"></script>
             <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
            <?php //var_dump($rs_sucursales); ?>
	</body>
</html>