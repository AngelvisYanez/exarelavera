<?php 
/**
Descripci�n: P�gina de inicio del sistema inform�tico
Fecha de creaci�n:	2013-01-19
Desarrollador:	Lewis Chimarro 
*/
require_once('../../Librerias/procedimientos/almacenados_standar.php'); 
require_once('../LOGICA/seguridad_session.php'); 
require_once('../LOGICA/adm_log_ckeditor.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Adm
 */
$obBD_conexion = new Class_Log_Conexion_Log($Ses_Emp_Cod);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Adm
 */
$obBD_con1 =  new Class_Log_Datos_Log;

/**
* Ajax que actualiza la hora o fecha de conexion al sistema 
*/
if (isset($ajaxCx)) 
{
	$dif = difenciaTimeDate(date('Y-m-d H:i:s'), $Ses_Sys_Tim,0); 	
		echo '<font class="letra_sub_index">'.$dif.'</font>';
		include_once("adm_mod_online_1.0.php");
		exit();
}//Fin del if (isset($ajaxCx))

if (isset($ajaxOnline))
{
	include_once("adm_con_online_1.0.php");
	exit();
}

/**
* Consulta los datos del +empresa
*/
$row_empresa = $obBD_con1->getRowConsulta(1, $Ses_Emp_Cod,$obBD_conexion);
?>
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->

<!-- Mirrored from wbpreview.com/previews/WB0164888/ by HTTrack Website Copier/3.x [XR&CO'2010], Tue, 23 Oct 2012 00:38:24 GMT -->
<head>
	<title><?Php echo $Ses_Sys_Nom; ?> - Instituci�n</title>	
	<meta charset=iso-8859-1>	
	<meta name="description" content="">
	<meta name="author" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">	
	<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Open+Sans:400,600,800">
	<link rel="stylesheet" href="../../mascaras/model1/css/font-awesome.css">	
	<link rel="stylesheet" href="../../mascaras/model1/css/bootstrap.css">
	<link rel="stylesheet" href="../../mascaras/model1/css/bootstrap-responsive.css">
	<link rel="stylesheet" href="../../mascaras/model1/css/ui-lightness/jquery-ui-1.8.21.custom.css">	
	<link rel="stylesheet" href="../../mascaras/model1/css/application.css">
	<link rel="stylesheet" href="../../mascaras/model1/css/pages/dashboard.css">
	<script src="../../mascaras/model1/js/libs/modernizr-2.5.3.min.js"></script>
	<script src="../LOGICA/TreeMenu.js" language="JavaScript" type="text/javascript"></script>
	<script src="../../Librerias/validaciones/validacion.js" language="javascript" type="text/javascript"></script>
	<script type="text/javascript" type="text/javascript">
        /**
		* Funcion para mantener la sesion activa 
		*/
        function MantenerSesionAbierta()
        {
           ajax_classic('<?Php echo $_SERVER['PHP_SELF']; ?>?ajaxCx=1','ajaxConexion');
			/**
			* Ajax de los usuarios en linea 
			*/
		   ajax_classic('<?Php echo $_SERVER['PHP_SELF']; ?>?ajaxOnline=1','div_online');
        }
        </script>	
</head>

<body <?php if ($_SESSION['Ses_Usu_Cad'] == 'N'){ ?> onLoad="timer('MantenerSesionAbierta()',1)" <?Php } ?>>
	
<div id="wrapper">
	
<div id="topbar">
	
	<div class="container">
		
		<a href="javascript:;" id="menu-trigger" class="dropdown-toggle" data-toggle="dropdown" data-target="#">
			<i class="icon-cog"></i>
		</a>
	
		<div id="top-nav">
			
			<ul>
				<li class="dropdown">
					<a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown">
						<?Php  echo $_SESSION['Ses_Emp_Nom']." [".$_SESSION['Ses_Suc_Nom']."]"; ?>
						<b class="caret"></b>
					</a>
					
					<ul class="dropdown-menu pull-right">
						<li><a href="<?php echo $Ses_Suc_Web; ?>" target="_blank"><?php echo $Ses_Suc_Web; ?></a></li>
						<li><a href="https://www.facebook.com/">https://www.facebook.com/</a></li>
						<li><a href="https://twitter.com/">https://twitter.com/</a></li>                        
					</ul> 
				</li>
			</ul>
			
			<ul class="pull-right">
            <?Php 
			$apellido = explode(' ', $_SESSION['Ses_Prs_Ape']);
			$nombre = explode(' ', $_SESSION['Ses_Prs_Nom']); ?>
				<li><a href="javascript:;"><i class="icon-user"></i> Conectado como <?Php echo $nombre[0].' '.$apellido[0]; ?></a></li>
			<?php 
			/**
			* Verifica si caduca o no la session 
			*/
			if ($_SESSION['Ses_Usu_Cad'] == 'N'){ ?>
			<li>              
            <a href="javascript:;"><span class="badge badge-primary">Tiempo            
		<span id="ajaxConexion">	
				<?Php echo "Procesando..."; ?></span>
            </span> </a></li>
        <?php 
		}//Fin del if ($_SESSION['Ses_Usu_Cad'] == 'N') 
		?> 				
			  <li><a href="../LOGICA/logout.php">Cerrar Sesi&oacute;n</a></li>
			</ul>
			
		</div> <!-- /#top-nav -->
		
	</div> <!-- /.container -->
	
</div> <!-- /#topbar -->

	
	
<div id="header">
	
	<div class="container">
		
		<a href="index-2.html" class="brand">Dashboard Admin</a>
		
		<a href="javascript:;" class="btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        	<i class="icon-reorder"></i>
      	</a>
	
<div class="nav-collapse">
			<ul id="main-nav" class="nav pull-right">
				<li class="nav-icon">
					<a href="home.php">
						<i class="icon-home"></i>
						<span>Home</span>        					
					</a>
				</li>
				
				<li class="dropdown active">				
					<a href="institucion.php" class="dropdown-toggle" data-toggle="dropdown">
						<i class="icon-flag"></i>
						<span>+ <?Php echo $_SESSION['Ses_Emp_Cor'] ?></span> 
					</a>					
							
	      </li>
				
				<li class="dropdown">					
					<a href="acerca.php" class="dropdown-toggle">
						<i class=" icon-info-sign"></i>
						<span>Acerca de Exa</span> 
				  </a>	
				
					 				
			  </li>
			</ul>
			
		</div> <!-- /.nav-collapse -->

	</div> <!-- /.container -->
	
</div> <!-- /#header -->


<div id="masthead"><!-- /.container -->	
	
  <div class="container">
    <div class="masthead-pad">
      <div class="masthead-text">
        <h2></h2>
        <?Php 
		echo htmlspecialchars_decode($row_empresa['Emp_Who']);
		?>
      </div>
      <!-- /.masthead-text -->
    </div>
  </div>
</div> <!-- /#masthead -->


<div id="content">
	

</div> <!-- /#content -->

</div> <!-- /#wrapper -->


<div id="footer">
		
	<div class="container">
		
		<div class="row">
			
			<div class="span6">
				Copyright &copy; <?Php echo date("Y"); ?>. All rights reserved.<br>
  &nbsp;Designed by <img src="../../mascaras/model1/img/logo/ingenium.png"></div> <!-- /span6 -->
			
			<div id="builtby" class="span6">
								
			</div> <!-- /.span6 -->
			
		</div> <!-- /row -->
		
	</div> <!-- /container -->
	
</div> <!-- /#footer -->

<script src="../../mascaras/model1/js/libs/jquery-1.7.2.min.js"></script>
<script src="../../mascaras/model1/js/libs/jquery-ui-1.8.21.custom.min.js"></script>
<script src="../../mascaras/model1/js/libs/jquery.ui.touch-punch.min.js"></script>

<script src="../../mascaras/model1/js/libs/bootstrap/bootstrap.min.js"></script>

<script>

$(function () {
	
	Theme.init ();
	
});

</script>

</body>

<!-- Mirrored from wbpreview.com/previews/WB0164888/ by HTTrack Website Copier/3.x [XR&CO'2010], Tue, 23 Oct 2012 00:38:43 GMT -->
</html>