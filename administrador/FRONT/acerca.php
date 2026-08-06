<?php 
/**
Descripci�n: P�gina de inicio del sistema inform�tico
Fecha de creaci�n:	2013-01-19
Desarrollador:	Lewis Chimarro 
*/
require_once('../../Librerias/procedimientos/almacenados_standar.php'); 
require_once('../LOGICA/seguridad_session.php'); 

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
?>
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->

<!-- Mirrored from wbpreview.com/previews/WB0164888/ by HTTrack Website Copier/3.x [XR&CO'2010], Tue, 23 Oct 2012 00:38:24 GMT -->
<head>
	<title><?Php echo $Ses_Sys_Nom; ?> - Acerca de</title>	
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
			  <li><a href="../LOGICA/logout.php">Cerrar Sesi�n</a></li>
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
				
				<li class="dropdown">				
					<a href="institucion.php" class="dropdown-toggle">
						<i class="icon-flag"></i>
						<span>+ <?Php echo $_SESSION['Ses_Emp_Cor'] ?></span> 
					</a>					
							
	      </li>
				
				<li class="dropdown active">					
					<a href="acerca.php" class="dropdown-toggle" data-toggle="dropdown">
						<i class=" icon-info-sign"></i>
						<span>Acerca de Exa</span> 
				  </a>	
				
					 				
			  </li>
			</ul>
			
		</div> <!-- /.nav-collapse -->

	</div> <!-- /.container -->
	
</div> <!-- /#header -->


<div id="masthead"><!-- /.container -->	
	
  
<div id="content">

	<div class="container">
		
		
		<div class="row">
			
			<div class="span8">
				
						
			  <ol class="faqList">
					
					<li>
							<h4>&iquest;Por qu&eacute; Macros?</h4>
                      <div align="center">      
                            <img src="../../mascaras/model1/img/logo/logo.png" width="180" height="143"></div>
							<p align="justify">En el a&ntilde;o  2007 se empieza a desarrollar un nuevo sistema inform&aacute;tico para la web llamado  Account (cuentas), ya que su fin era de tipo contable.<br>
Account durante  m&aacute;s de un a&ntilde;o, fue incrementando sus m&oacute;dulos desarrollados abarcando procesos  como: Facturaci&oacute;n, matriculas y calificaciones. Lo cual hizo que el nombre de  Account, ya no pueda ser un identificativo ideal y abarcador para sus procesos autom&aacute;tizados.<br>
Es necesario un  nuevo nombre y tras una sesi&oacute;n de lluvia de ideas, en el a&ntilde;o 2011 se decide por  el nombre Macros, haciendo un juego de palabras con el t&eacute;rmino &quot;macro&quot;,  utilidad de la aplicaci&oacute;n de Microsoft Excel, tomando la idea de que una &ldquo;macro&rdquo;  es una rutina de c&oacute;digo fuente para un fin espec&iacute;fico, adicional a esto la  palabra &ldquo;macro&rdquo; significa grande, por lo tanto el nombre del sistema inform&aacute;tico  Macros, nos da a entender su significado como: &ldquo;Grandes rutinas de c&oacute;digo  fuente para fines espec&iacute;ficos&rdquo;.&nbsp; <br>
La elecci&oacute;n  del t&eacute;rmino se basa en el objetivo del fundador, de entregar a sus clientes  rutinas de c&oacute;digo fuente para solucionar problemas espec&iacute;ficos en sus instituciones.					</p><br>
					</li>
					
					<li>
							<h4>Tecnolog&iacute;a </h4>
							<p align="justify">Macros es 100% OpenSource y utiliza como base fundamental: &nbsp;<strong>L</strong>inux,  &nbsp;<strong>A</strong>pache, <strong>M</strong>ysql, <strong>P</strong>hp, estos se los conoce con  el t&eacute;rmino LAMP,  plataforma para desarrollo y ejecuci&oacute;n de aplicaciones web  de alta perfomance dada por el uso en conjunto de Linux, Apache, MySQL y PHP.  El nombre LAMP se debe justamente a las iniciales de estas 4 tecnolog&iacute;as.<br>
LAMP est&aacute; considerada como una de las mejores herramientas para desarrollar y  ejecutar aplicaciones web, sus  componentes por separado son de  alt&iacute;sima calidad y en conjunto sus virtudes se multiplican. A diferencia de otras tecnolog&iacute;as de uso similar, LAMP no tiene publicidad propia,  no hay una corporaci&oacute;n detr&aacute;s de su desarrollo, sino una comunidad de  entusiastas y programadores de primer nivel que trabajan con la calidad del producto.  Tanto Linux como Apache, MySQL y PHP son Software de c&oacute;digo abierto por lo que  si usted escucha hablar de LAMP ser&aacute; verdaderamente por sus virtudes, no por  publicidad.<br><br>
 <strong>LINUX</strong>&nbsp;<br>
Sistema operativo.&nbsp;<br>
<div align="center"><img src="../../mascaras/model1/img/logo/linux.png" width="150" height="125"><img src="../../mascaras/model1/img/logo/centos.png" width="300" height="150"></div><br>
Es muy potente, muy estable, adaptable y robusto; Linux tiene una posici&oacute;n  predominante en el &aacute;mbito web y su uso se est&aacute; expandiendo cada vez m&aacute;s a los  distintos &aacute;mbitos.
<br><br>
					  <strong>APACHE</strong>&nbsp;<br>
					    Servidor web<br>
		<div align="center"><img src="../../mascaras/model1/img/logo/apache.png" width="174" height="127">
        </div>
        <br>De entre todos los servidores web disponibles Apache es sin duda de lo mejor,  es el m&aacute;s utilizado por muy lejos (a Octubre del 2004, el 70% de los servers  son Apache), muy estable, de c&oacute;digo abierto.&nbsp;<br><br>
					    <strong>MySQL</strong>&nbsp;<br>
					    Motor de Bases de Datos<br>
				<div align="center">	    <img src="../../mascaras/model1/img/logo/mysql.png" width="200" height="100"></div><br>Es un robusto sistema de bases de datos relacionales, le otorgar&aacute; a nuestras  aplicaciones la capacidad de guardar y acceder a informaci&oacute;n en forma r&aacute;pida y  precisa. Se caracteriza por su velocidad, escalabilidad y fiabilidad.<br><br>
					    <strong>PHP</strong>&nbsp;<br>
					    Lenguaje de programaci&oacute;n<br>
                        <div align="center"><img src="../../mascaras/model1/img/logo/php.png" width="200" height="100"></div><br>
					    PHP es m&aacute;s r&aacute;pido que los dem&aacute;s lenguajes del tipo, multiplataforma, muy  robusto y trabaja a la perfecci&oacute;n en conjunto con Apache y MySQL
				  </p><br>
				  </li>
					<li>
					  <h4>&iquest;Qui&eacute;nes somos?</h4>
<div align="center"><img src="../../mascaras/model1/img/logo/ingenium.logo.png"></div><br><p align="justify">
							Somos una empresa joven nacida en la ciudad de Machala, con el prop&oacute;sito de dar a conocer nuevas soluciones tecnol&oacute;gicas y herramientas que aporten el desarrollo de peque&ntilde;as, medianas y grandes empresas.</p>
					  <p align="justify">Nuestra principal actividad es el&nbsp;<strong>desarrollo de software</strong>, realizando todo el proceso, desde el estudio de los requisitos, pasando por el an&aacute;lisis, dise&ntilde;o, desarrollo hasta el posterior mantenimiento del producto software que necesite su empresa.</p>
<p align="justify">&nbsp;</p>
				  </li>
					
					<li>
							<h4>Desarrolladores				  </h4>
				  </li>
				</ol>
			  <table width="80%" border="0" align="center" cellpadding="0" cellspacing="0" style="border:ridge">
			    <tr>
    <td colspan="3" align="center" valign="top"><strong><u>DATOS PERSONALES</u></strong></td>
    </tr>
  <tr>
    <td width="174" valign="top"><p><strong>NOMBRES&nbsp; Y APELLIDOS:</strong></p></td>
    <td width="244" valign="top"><p>V&iacute;ctor Lewis Chimarro Chipantiza</p></td>
    <td width="78" rowspan="5" valign="top"><img src="desarrolladores/lewis.chimarro.jpg" width="78" height="104"></td>
    </tr>
  <tr>
    <td width="174" valign="top"><p><strong>CEDULA    DE IDENTIDAD: </strong></p></td>
    <td width="244" valign="top"><p>0703703413</p></td>
    </tr>
  <tr>
    <td width="174" valign="top"><p><strong>EDAD: </strong></p></td>
    <td width="244" valign="top"><p>32 a&ntilde;os</p></td>
    </tr>
  <tr>
    <td width="174" valign="top"><p><strong>ESTADO&nbsp; CIVIL:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong></p></td>
    <td width="244" valign="top"><p>Casado</p></td>
    </tr>
  <tr>
    <td width="174" valign="top"><p><strong>LUGAR&nbsp; DE&nbsp;    NACIMIENTO:</strong></p></td>
    <td width="244" valign="top"><p>Machala </p></td>
    </tr>
  <tr>
    <td width="174" valign="top"><p><strong>FECHA    DE NACIMIENTO:</strong></p></td>
    <td width="244" valign="top"><p>28 de Septiembre de 1980</p></td>
    <td width="78" valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="3" align="center" valign="top"><strong><u>ESTUDIOS&nbsp;&nbsp; REALIZADOS</u></strong></td>
    </tr>
  <tr>
    <td valign="top"><strong>INSTRUCCI&Oacute;N&nbsp; SUPERIOR:</strong></td>
    <td colspan="2" valign="top" align="justify">Universidad    Tecnol&oacute;gica &ldquo;San Antonio de&nbsp; Machala&rdquo;, Escuela de Inform&aacute;tica.<br>
      Escuela Superior Polit&eacute;cnica    del Ejercito</td>
    </tr>
  <tr>
    <td valign="top"><strong>INSTRUCCI&Oacute;N&nbsp; SECUNDARIA:</strong></td>
    <td colspan="2" valign="top" align="justify">Colegio Nacional    Nueve de Octubre &ldquo;Ciclo b&aacute;sico&rdquo;<br>
      Colegio Nacional    Sim&oacute;n Bol&iacute;var. &ldquo;Ciclo diversificado&rdquo; Especialidad: INFORM&Aacute;TICA.&nbsp;&nbsp;&nbsp;</td>
    </tr>
  <tr>
    <td valign="top"><strong>INSTRUCCI&Oacute;N&nbsp; PRIMARIA:</strong></td>
    <td colspan="2" valign="top" align="justify">Escuela Particular &quot;Manuel Isaac Encala Z&uacute;&ntilde;iga&quot;</td>
    </tr>
  <tr>
    <td colspan="3" align="center" valign="top"><strong><u>TITULOS OBTENIDOS</u></strong></td>
    </tr>
  <tr>
    <td valign="top"><strong>SECUNDARIA:</strong></td>
    <td colspan="2" valign="top" align="justify">Bachiller en Ciencias Especializaci&oacute;n Inform&aacute;tica</td>
    </tr>
  <tr>
    <td valign="top"><strong>PRE-GRADO:</strong></td>
    <td colspan="2" valign="top" align="justify">Ingeniero en Sistemas</td>
    </tr>
  <tr>
    <td valign="top"><strong>POST-GRADO:</strong></td>
    <td colspan="2" valign="top" align="justify">Maestr&iacute;a  en Ingenier&iacute;a del Software tercera edici&oacute;n 2010-2012, Escuela Superior  Polit&eacute;cnica del Ejercito (Egresado en Proceso de Tesis).</td>
    </tr>
  <tr>
    <td colspan="3" align="center" valign="top"><strong><u>ESTUDIOS EN  CURSO</u></strong></td>
    </tr>
  <tr>
    <td valign="top"><strong>SUFICIENCIA EN INGLES</strong></td>
    <td colspan="2" valign="top" align="justify">Cursando  en la ESPE</td>
  </tr>
  <tr>
    <td colspan="3" align="center" valign="top"><strong><u>CARGO EN MACROS</u></strong></td>
    </tr>
  <tr>
    <td valign="top">&nbsp;</td>
    <td colspan="2" valign="top" align="justify">Gestor de proyectos, administrador de sistemas, desarrollador</td>
  </tr>
              </table>
              <br>
              <br>
			  <table width="80%" border="0" align="center" cellpadding="0" cellspacing="0" style="border:ridge">
			    <tr>
			      <td colspan="3" align="center" valign="top"><strong><u>DATOS PERSONALES</u></strong></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>NOMBRES&nbsp; Y APELLIDOS:</strong></p></td>
			      <td width="244" valign="top"><p>Jenniffer Valeria Rivero Bazan</p></td>
			      <td width="78" rowspan="5" valign="top"><img width="78" height="104"></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>CEDULA    DE IDENTIDAD: </strong></p></td>
			      <td width="244" valign="top"><p>0704869551</p></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>EDAD: </strong></p></td>
			      <td width="244" valign="top"><p> 27 a&ntilde;os</p></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>ESTADO&nbsp; CIVIL:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong></p></td>
			      <td width="244" valign="top"><p>Casada</p></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>LUGAR&nbsp; DE&nbsp;    NACIMIENTO:</strong></p></td>
			      <td width="244" valign="top"><p>Machala </p></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>FECHA    DE NACIMIENTO:</strong></p></td>
			      <td width="244" valign="top"><p>16 de Abril de 1985</p></td>
			      <td width="78" valign="top">&nbsp;</td>
		        </tr>
			    <tr>
			      <td colspan="3" align="center" valign="top"><strong><u>ESTUDIOS&nbsp;&nbsp; REALIZADOS</u></strong></td>
		        </tr>
			    <tr>
			      <td valign="top"><strong>INSTRUCCI&Oacute;N&nbsp; SUPERIOR:</strong></td>
			      <td colspan="2" valign="top" align="justify">Universidad    Tecnol&oacute;gica &ldquo;San Antonio de&nbsp; Machala&rdquo;, Escuela de Dise&ntilde;o.</td>
		        </tr>
			    <tr>
			      <td valign="top"><strong>INSTRUCCI&Oacute;N&nbsp; SECUNDARIA:</strong></td>
			      <td colspan="2" valign="top" align="justify"><p>Unidad Educativa &quot;Julio Mar&iacute;a Matovelle&quot;</p>
			        <p>Especialidad: F&Iacute;SICO MATEM&Aacute;TICO.&nbsp;&nbsp;&nbsp;</p></td>
		        </tr>
			    <tr>
			      <td valign="top"><strong>INSTRUCCI&Oacute;N&nbsp; PRIMARIA:</strong></td>
			      <td colspan="2" valign="top" align="justify">Escuela &quot;Hector Encalada Sanchez&quot;</td>
		        </tr>
			    <tr>
			      <td colspan="3" align="center" valign="top"><strong><u>TITULOS OBTENIDOS</u></strong></td>
		        </tr>
			    <tr>
			      <td valign="top"><strong>SECUNDARIA:</strong></td>
			      <td colspan="2" valign="top" align="justify">Bachiller en Ciencias F&iacute;sico Matem&aacute;tico</td>
		        </tr>
			    <tr>
			      <td valign="top"><strong>PRE-GRADO:</strong></td>
			      <td colspan="2" valign="top" align="justify">Licencia en Dise&ntilde;o Gr&aacute;fico</td>
		        </tr>
			    <tr>
			      <td colspan="3" align="center" valign="top"><strong><u>CARGO EN MACROS</u></strong></td>
		        </tr>
			    <tr>
			      <td valign="top">&nbsp;</td>
			      <td colspan="2" valign="top" align="justify">Dise&ntilde;ador gr&aacute;fico, comunicaci&oacute;n visual, publicidad, marketing</td>
		        </tr>
		      </table>
<br>
              <br>
			  <table width="80%" border="0" align="center" cellpadding="0" cellspacing="0" style="border:ridge">
			    <tr>
			      <td colspan="3" align="center" valign="top"><strong><u>DATOS PERSONALES</u></strong></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>NOMBRES&nbsp; Y APELLIDOS:</strong></p></td>
			      <td width="244" valign="top"><p>Juan Carlos Le&oacute;n Ruiz</p></td>
			      <td width="78" rowspan="5" valign="top"><img width="78" height="104"><img src="desarrolladores/carlos.leon.jpg" width="78" height="104"></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>CEDULA    DE IDENTIDAD: </strong></p></td>
			      <td width="244" valign="top"><p>0704824846</p></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>EDAD: </strong></p></td>
			      <td width="244" valign="top"><p> 24 a&ntilde;os</p></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>ESTADO&nbsp; CIVIL:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong></p></td>
			      <td width="244" valign="top"><p>Soltero</p></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>LUGAR&nbsp; DE&nbsp;    NACIMIENTO:</strong></p></td>
			      <td width="244" valign="top"><p>Machala </p></td>
		        </tr>
			    <tr>
			      <td width="174" valign="top"><p><strong>FECHA    DE NACIMIENTO:</strong></p></td>
			      <td width="244" valign="top"><p>13 de Agosto 1988</p></td>
			      <td width="78" valign="top">&nbsp;</td>
		        </tr>
			    <tr>
			      <td colspan="3" align="center" valign="top"><strong><u>ESTUDIOS&nbsp;&nbsp; REALIZADOS</u></strong></td>
		        </tr>
			    <tr>
			      <td valign="top"><strong>INSTRUCCI&Oacute;N&nbsp; SUPERIOR:</strong></td>
			      <td colspan="2" valign="top" align="justify">Universidad    Tecnol&oacute;gica &ldquo;San Antonio de&nbsp; Machala&rdquo;, Escuela de Inform&aacute;tica.</td>
		        </tr>
			    <tr>
			      <td valign="top"><strong>INSTRUCCI&Oacute;N&nbsp; SECUNDARIA:</strong></td>
			      <td colspan="2" valign="top" align="justify"><p>Colegio Particular Eloy Alfaro</p>
		          <p>Especialidad: INFORM&Aacute;TICA.&nbsp;&nbsp;&nbsp;</p></td>
		        </tr>
			    <tr>
			      <td valign="top"><strong>INSTRUCCI&Oacute;N&nbsp; PRIMARIA:</strong></td>
			      <td colspan="2" valign="top" align="justify">Escuela &quot;Esp&iacute;ritu Santo&quot;</td>
		        </tr>
			    <tr>
			      <td colspan="3" align="center" valign="top"><strong><u>TITULOS OBTENIDOS</u></strong></td>
		        </tr>
			    <tr>
			      <td valign="top"><strong>SECUNDARIA:</strong></td>
			      <td colspan="2" valign="top" align="justify">Bachiller en Ciencias Especializaci&oacute;n Inform&aacute;tica</td>
		        </tr>
			    <tr>
			      <td valign="top"><strong>PRE-GRADO:</strong></td>
			      <td colspan="2" valign="top" align="justify">Ingeniero en Sistemas</td>
		        </tr>
			    <tr>
			      <td colspan="3" align="center" valign="top"><strong><u>CARGO EN MACROS</u></strong></td>
		        </tr>
			    <tr>
			      <td valign="top">&nbsp;</td>
			      <td colspan="2" valign="top" align="justify">Desarrollador, Investigaci&oacute;n de tecnolog&iacute;a</td>
		        </tr>
		      </table>
            </div> 
			<!-- /.span8 -->
			
			
			<div class="span4">
				
						
				
				
				
				<a href="javascript:;" class="btn btn-large btn-tertiary btn-block btn-big-block">Contacto</a>
				
				
				<div class="well">
					
					<table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
					  <tr>
					    <td width="174" valign="top"><p><strong>CELULAR:</strong></p></td>
					    <td width="244" valign="top"><p>0988624767</p></td>
				      </tr>
					  <tr>
					    <td width="174" valign="top"><p><strong>E-MAIL:</strong></p></td>
					    <td width="244" valign="top"><p><a href="mailto:ginus_system@hotmail.com">ginus_system@hotmail.com</a>, <a href="mailto:soporte.tecnico@macros.com.ec">soporte.tecnico@macros.com.ec</a></p></td>
				      </tr>
					  <tr>
					    <td valign="top"><strong>SKYPE:</strong></td>
					    <td valign="top">lewis.chimarro</td>
				      </tr>
				  </table>
					<p>&nbsp;</p>						
				</div> 
				<!-- /.well -->
						
				
			</div> <!-- /.span4 -->
			
		</div> <!-- /.row -->
		
	
	</div> <!-- /.container -->

</div> <!-- /#content -->
  
  
  
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

<script src="../../mascaras/model1/js/plugins/faq/faq.js"></script>

<script src="../../mascaras/model1/js/libs/bootstrap/bootstrap.min.js"></script>

<script src="../../mascaras/model1/js/Theme.js"></script>

<script>

$(function () {
	
	Theme.init ();
	
	$('.faqList').goFaq ();
	
});

</script>

</body>

<!-- Mirrored from wbpreview.com/previews/WB0164888/ by HTTrack Website Copier/3.x [XR&CO'2010], Tue, 23 Oct 2012 00:38:43 GMT -->
</html>