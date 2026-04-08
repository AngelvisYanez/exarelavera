<?php 
/**
Descripciï¿½n: Pï¿½gina de inicio del sistema informï¿½tico
Fecha de creaciï¿½n:	2013-01-19
Desarrollador:	Lewis Chimarro 
*/
require_once('../../Librerias/procedimientos/almacenados_standar.php'); 
require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/logica.php');
//require_once('../LOGICA/adm_log_login.php');

if (isset($heartBeatChat))
{
    include("adm_con_online_2.0.php");
    echo json_encode($response);
    exit();
}
if (isset($historyChat))
{
    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);   
    $obBD_con1 =  new Class_Log_Datos_Adm;
    $response['history']=$obBD_con1->getArrayConsulta(216,filter_input_array(INPUT_POST), $obBD_conexion);
    $response['success']=true;
    echo json_encode($response);
    exit();
}
if (isset($signalChat))
{
    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);   
    $obBD_con1 =  new Class_Log_Datos_Adm;   
    $obBD_con1->grabarv_registros(sentencias_adm(219, filter_input_array(INPUT_POST)),$obBD_conexion->conexion);	
    $response['success']=true;
    echo json_encode($response);
    exit();
}
if (isset($ClientGuid))
{
    $obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);   
    $obBD_con1 =  new Class_Log_Datos_Adm;   
    $obBD_con1->grabarv_registros(sentencias_adm(215, filter_input_array(INPUT_POST)),$obBD_conexion->conexion);	
    $response['success']=true;
    echo json_encode($response);
    exit();
}		  
if(isset($loginAjax)){
    
    require('../LOGICA/adm_log_control.php');
    
    /**
    * Creacion del Objeto de Conexion 
    */
    $obBD_conexion = new Class_Log_Conexion_Cnt;
    /**
    * Creacion del Objeto de Datos
    */
    $obBD_con =  new Class_Log_Datos_Cnt;
    /**
    * Consulta que realiza la autenticacion del usuario 
    */
    $row_data = $obBD_con->getRowConsulta(2, $Emp_Cod.'*'.trim($user_name), $obBD_conexion);
    /**
    * Conexion a la base de datos distribuida, dinamica
    */
    $obBD_conexion = new Class_Log_Conexion_Cnt($row_data['Dat_Dis']);
    /**
    * Consulta que realiza la autenticacion del usuario 
    */
    $row_rs_control = $obBD_con->getArrayConsulta(16, trim($user_name).'*'.trim($encryptor).'*'.$Emp_Cod.'*'.$Suc_Cod, $obBD_conexion);
    //var_dump($row_rs_control);
    foreach($row_rs_control AS $rowControl)
            if($rowControl['Suc_Cod']==$Suc_Cod)
                $row_rs_control=$rowControl;       
    if(isset($row_rs_control['Suc_Cod'])){        

        /**
	* Consulta los perfiles asignados al usuario 
	*/
	$rs_perfiles = $obBD_con->getArrayConsulta(21, $row_rs_control["Usu_Cod"], $obBD_conexion); 
        foreach($rs_perfiles as $v0)
	{
		$lperf[]=$v0["Per_Cod"];
		$Per_Des[]=$v0["Per_Des"];
	}
        /*
        * Variables de Sesion del usuario 
	*/	
	$_SESSION['Ses_Usu_Cod']=$row_rs_control['Usu_Cod']; 	
	$_SESSION['Ses_Usu_Ced']=$row_rs_control['Usu_Ced'];
        $_SESSION['Ses_Usu_Tip']=$row_rs_control['Usu_Tip'];
	$_SESSION['Ses_Usu_Est']=$row_rs_control['Usu_Est'];
	$_SESSION['Ses_Usu_Cad']=$row_rs_control['Usu_Cad'];
	$_SESSION['Ses_Usu_Men']=$row_rs_control['Usu_Men'];
        $_SESSION['Ses_Per_Cod']=$row_rs_control['Per_Cod'];
        /**
	* Variable para definir la sucursal y empresa
	*/	
	$_SESSION['Ses_Suc_Cod']=$row_rs_control['Suc_Cod'];
	$_SESSION['Ses_Suc_Nom']=$row_rs_control['Suc_Des'];	
	$_SESSION['Ses_Emp_Cod']=$row_rs_control['Emp_Cod'];
	$_SESSION['Ses_Emp_Nom']=$row_rs_control['Emp_Nom'];
	$_SESSION['Ses_Emp_Cor']=$row_rs_control['Emp_Cor'];	
	$_SESSION['Ses_Suc_Web']=$row_rs_control['Suc_Web'];	
	$_SESSION['Ses_Emp_Log']=$row_rs_control['Emp_Log'];
        /**
	* Variables del Perfil del usuario 
	*/	
	$_SESSION['Ses_Lis_Per']=$lperf;
	$_SESSION['Ses_Per_Des']=$Per_Des; //Descripciï¿½n del perfil
        /**
	* Variable para la base de datos del sistema local
	*/
	$_SESSION['Ses_Dat_Dis'] = $row_data['Dat_Dis']; //Base de datos distribuida local
	$_SESSION['Ses_Dat_Aut'] = $row_data['Dat_Aut']; //Base de datos auditoria
	$_SESSION['Ses_Dat_Stg'] = $row_data['Dat_Stg']; //Base de datos storage
        $responce['success']=true;
    }else{
        $responce['success']=false;
    }
    echo json_encode($responce);
    exit();
}
        /**
	* Creacion del Objeto de conexion 
	*/
	$obBD_conexion1 = new Class_Log_Conexion_Adm;
	/**
	* Creaciï¿½n del objeto mysql para las consultas 
	*/
	$obBD_con1 =  new Class_Log_Datos_Adm;
        
	$rs_empresas = $obBD_con1->getArrayConsulta(213, trim($Ses_Usu_Ced), $obBD_conexion1); 
        
        //var_dump($Ses_Dat_Dis);
?>
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<!-- Mirrored from wbpreview.com/previews/WB0164888/ by HTTrack Website Copier/3.x [XR&CO'2010], Tue, 23 Oct 2012 00:38:24 GMT -->
<head>
	<title><?Php echo $Ses_Sys_Nom; ?></title>	
	<meta charset="iso-8859-1" />
	<meta name="description" content="">
	<meta name="author" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
        <link rel="shortcut icon" type="image/x-icon" href="../../mascaras/model1/img/logo/exa-ico-2.png" />
	<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Open+Sans:400,600,800">
	<link rel="stylesheet" href="../../mascaras/model1/css/font-awesome.css">	
	<link rel="stylesheet" href="../../mascaras/model1/css/bootstrap.css">
	<link rel="stylesheet" href="../../mascaras/model1/css/bootstrap-responsive.css">
	<link rel="stylesheet" href="../../mascaras/model1/css/ui-lightness/jquery-ui-1.8.21.custom.css">	
	<link rel="stylesheet" href="../../mascaras/model1/css/application.css">
        <!--<link rel="stylesheet" href="../../mascaras/model1/css/application-black-orange.css">-->
	<link rel="stylesheet" href="../../mascaras/model1/css/pages/dashboard.css">
        
        <link rel="stylesheet" href="../../framework/jquery/ChatJs/css/jquery.chatjs.css"/>
        
        
        
        <style>#loginChange{margin: 0;}#loginChange .add-on{padding-right: 7px;}.add-on i:before{font-size: 22px;padding-top: 4px;} .dropdown-menu li > a {padding-right: 25px;}.dropdown-menu a {line-height: 13px;}.dropdown-submenu > a:after{margin-top: 1px;}.dropdown-submenu > a::after{margin-right: -15px;}.alert{text-align: center;margin-bottom: 0;}
            #content {margin-top: 13px;margin-bottom: 0;}
            .scrollable-tree {
            height: auto;
            max-height: 500px;
            overflow-x: hidden;
        }
         .scrollable-tree::-webkit-scrollbar {
                -webkit-appearance: none;
                width: 4px;        
            }    
            .scrollable-tree::-webkit-scrollbar-thumb {
                border-radius: 3px;
                background-color: lightgray;
                -webkit-box-shadow: 0 0 1px rgba(255,255,255,.75);        
            }  
        </style>    
	<script src="../../mascaras/model1/js/libs/modernizr-2.5.3.min.js"></script>
	<script src="../LOGICA/TreeMenu.js" language="JavaScript" type="text/javascript"></script>
	<script src="../../Librerias/validaciones/validacion.js" language="javascript" type="text/javascript"></script>
    <script language="javascript" type="text/javascript">
	/**
	* Funciï¿½n para amplicar el alto de un IFRAME
	*/
	function iframesize(id)
	/**
	* Probada en Mozilla Firefox, Netscape e IE en sus ï¿½ltimas versiones
	*/
	{
		tam_ventana=this.document.body.scrollHeight;
		$('#'+id).css('min-height',tam_ventana-186+"px");
                $('.scrollable-tree').css('max-height',tam_ventana-286+"px");//246
                $('#maxHeight').css('min-height',tam_ventana-186+"px");
	}
	</script>
</head>

<body onLoad="iframesize(&#39;contenido&#39;)">
	<div id="wrapper">
	
<div id="topbar">
	
	<div class="container">
		
		<a href="javascript:;" id="menu-trigger" class="dropdown-toggle" data-toggle="dropdown" data-target="#">
			<i class="icon-cog"></i>
		</a>
	
		<div id="top-nav">
			
			<ul>
				<li class="dropdown">
					<a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown"><?Php  echo $_SESSION['Ses_Emp_Nom']." [".$_SESSION['Ses_Suc_Nom']."]"; ?><b class="caret"></b></a>
                                        
                                    <ul class="dropdown-menu scrollable-menu pull-right">
					<?Php
					foreach($rs_empresas as $row)
					{					
                                                    $rs_sucursales = $obBD_con1->getArrayConsulta(214, $row['Emp_Cod'].'*'.$Ses_Usu_Ced, $obBD_conexion1);
                                                    if(count($rs_sucursales)>1){
                                                        echo '<li class="dropdown-submenu"><a>'.$row['Emp_Nom'].'</a><ul class="dropdown-menu">';
                                                        foreach($rs_sucursales as $rowSuc)
                                                        {
                                                            echo "<li".($Ses_Suc_Cod==$rowSuc['Suc_Cod']?' class="disabled" ':'')."><a".($Ses_Suc_Cod!=$rowSuc['Suc_Cod']?"  href=\"javascript:changeSuc('$row[Emp_Cod]','$rowSuc[Suc_Cod]','$rowSuc[Emp_Cor] [$rowSuc[Suc_Des]]')\" ":'').">$rowSuc[Emp_Cor] [$rowSuc[Suc_Des]]</a></li>";
                                                        } echo '</ul></li>';
                                                    }
                                                    else{
                                                        if(count($rs_sucursales)==1)
                                                        {
                                                            $rowSuc=$rs_sucursales[0];
                                                            echo "<li".($Ses_Suc_Cod==$rowSuc['Suc_Cod']?' class="disabled" ':'')."><a".($Ses_Suc_Cod!=$rowSuc['Suc_Cod']?"  href=\"javascript:changeSuc('$row[Emp_Cod]','$rowSuc[Suc_Cod]','$rowSuc[Emp_Cor] [$rowSuc[Suc_Des]]')\" ":'').">$row[Emp_Nom] [$rowSuc[Suc_Des]]</a></li>";
                                                        }
                                                    }                                                    
					}
				      ?> 
                                    </ul>
				</li>
			</ul>
                        <script>
                            function changeSuc(Emp,Suc,Des){
                                $('#Emp_Cod').val(Emp);
                                $('#Suc_Cod').val(Suc);
                                $('#Emp_Des').val(Des);
                                $('#Usu_Pas').val('');
                                $('#myModal').modal('show');
                            }
                            function loginAjax(){
                                var $msg;
                                $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{loginAjax:true,Emp_Cod:$('#Emp_Cod').val(),Suc_Cod:$('#Suc_Cod').val(),user_name:$('#Usu_Ced').val(),encryptor:md5($('#Usu_Pas').val())}, function( response ) {
                                    if(response['success']===true){
                                         $msg='<div class="alert alert-success fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[SISTEMA]</strong> &nbsp;&nbsp;Login Correcto. Direccionando....</div>';
                                         setTimeout(function (){window.location.href ="<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>";},2500);
                                    }else{ $msg='<div class="alert alert-error"><button type="button" class="close" data-dismiss="alert">x</button><strong>[ERROR]</strong> &nbsp;&nbsp;Usuario o Contrase&ntilde;a Incorrectos.</div>';}                                   
                                 },'json').fail(function(error) { $msg='<div class="alert alert-error fade in"><button type="button" class="close" data-dismiss="alert">x</button><strong>[ERROR]</strong> &nbsp;&nbsp;El Servidor ha fallado en responder!.</div>'; })
                                     .always(function() {$('#msgAlert').html($msg);$('#msgAlert .alert').hide();$('#msgAlert .alert').show('bounce',{},350);setTimeout(function (){$('#msgAlert .alert').hide('pulsate',{},250);},4000);});
                            }
                        </script>
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
                             <?php //if ($_SESSION['Ses_Usu_Cad'] == 'N'){
                             echo difenciaTimeDate(date('Y-m-d H:i:s'), $Ses_Sys_Tim,0);//}else{echo 'CADUCADO';} ?></span>
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
		
		<a href="home.php" class="brand">Macros</a>
		
		<a href="javascript:;" class="btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        	<i class="icon-reorder"></i>
      	</a>
	
<div class="nav-collapse">
			<ul id="main-nav" class="nav pull-right">
				<li class="nav-icon active">
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
				
				<li class="dropdown">					
					<a href="comunidad.php" class="dropdown-toggle">
						<i class="icon-user"></i>
						<span>Comunidad</span> 
				  </a>	
					 				
	      </li>
				
				<li class="dropdown">					
					<a href="acerca.php" class="dropdown-toggle">
						<i class=" icon-info-sign"></i>
						<span>Acerca de Macros</span> 
                                        </a>				
                                </li>
			</ul>
			
	  </div> <!-- /.nav-collapse -->

	</div> <!-- /.container -->
	
</div> <!-- /#header -->


<!--<div id="masthead">-->
			<table style="width: 100%;height:100%;margin-bottom: 16px;" border="0" style="vertical-align:top">                            
			        <tr>			         
                                  <td background="../../mascaras/model1/imagenes/system/main-back.png" width="16%" valign="top" style="border-right: 1px solid #A1B2CB;">
                                     <div id="maxHeight" style="min-height: 550px;">  
                                      <div class="scrollable-tree" style="padding-left: 5px;padding-top: 10px;padding-bottom: 20px;">
					  <?php 
					if($_SESSION['Ses_Usu_Men'] == 'B'){ 
						require_once("adm_con_dcdrilldown.php");
						
					}else{
						require_once("adm_con_treemenu.php");						
					}?>
                                      </div>    
                                    <?php if($Ses_Usu_Tip!='C'){ ?>
                                    <div style="background:#e6e6e6;border-bottom: 1px solid #A1B2CB;" > 
                                      <div id="div_online" style="border-top: 1px solid #A1B2CB;border-bottom: 1px solid #A1B2CB;">    
                                           <table width="100%" border="0" cellspacing="0" cellpadding="0">      
                                               <tr class="letra_index"><td class="banner_logeo" align="center" style="padding-top: 5px;padding-bottom: 5px;"><b id="uOnline"><?php echo $response['room']['UsersOnline']; ?></b>&nbsp; Usuario(s) Online
                                                   <img style="margin-top: -4px; margin-left: 10px;" src="../../framework/jquery/ChatJs/images/users.png"></td></tr> 
                                           </table>                                     
                                       </div>
                                        <div id="verq" style="background:#fff;">
                                            
                                        </div> 
                                   </div>
                                   <?php } ?>      
                                </div>          
                      </td>
                      <td width="83%" valign="top"><iframe align="left" name="contenido" height="100%" width="100%" id="contenido"  frameborder="0" class="contenido" src=""  style="height: 100%;"></iframe></td>
	          </tr>
	        </table>	
    
	<!--<div class="container">
		<div class="masthead-pad">
		  <div class="masthead-text">		    
			</div> -->
			<!-- /.masthead-text -->			
	<!--	</div>		
	</div> -->
    <!-- /.container -->	
	
<!--</div> -->
<!-- /#masthead -->


<div id="content">
	
</div> <!-- /#content -->

</div> <!-- /#wrapper -->


<div id="footer" style="padding: 8px 0;margin-top: -66px;">
		
	<div class="container">
		
		<div class="row">
			
			<div class="span6">				
  <!--&nbsp;Designed by--> 
			<img src="../../mascaras/model1/img/logo/ingenium.png"></div> 
			
                        <div id="builtby" class="span6" style="padding-top:15px;">
					Copyright &copy; <?Php echo date("Y"); ?>. All rights reserved.<br>			
			</div> 
			
		</div> 
		
	</div> 
	
</div> 
<p><!-- /#footer -->
  
  <script src="../../mascaras/model1/js/libs/jquery-1.7.2.min.js"></script>
  <script src="../../mascaras/model1/js/libs/jquery-ui-1.8.21.custom.min.js"></script>
  <script src="../../mascaras/model1/js/libs/jquery.ui.touch-punch.min.js"></script>
  
  <script src="../../mascaras/model1/js/libs/bootstrap/bootstrap.min.js"></script>
  
  <script src="../../mascaras/model1/js/Theme.js"></script>
  

<script>$(function () {	Theme.init ();});</script>
  <div id="myModal" class="modal hide fade">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3>Cambiar Empresas/Sucursales</h3>
  </div>
  <div class="modal-body">
    <form class="form-horizontal" id="loginChange">
<fieldset>

<!-- Form Name -->
<legend>Login</legend>

<!-- Prepended text-->
<div class="control-group">
  <label class="control-label" for="Usu_Ced">C&eacute;dula:</label>
  <div class="controls">
    <div class="input-prepend">
      <span class="add-on"><i class="icon-user"></i></span>
      <input id="Usu_Ced" name="user_name" value="<?php echo $Ses_Usu_Ced; ?>" class="input-medium" placeholder="" type="text" readonly="readonly">
    </div>
    
  </div>
</div>


<!-- Prepended text-->
<div class="control-group">
    <input type="hidden" id="Emp_Cod" name="Emp_Cod" />
    <input type="hidden" id="Suc_Cod" name="Suc_Cod" />
  <label class="control-label" for="Emp_Des">Empresa:</label>
  <div class="controls">
    <div class="input-prepend">
      <span class="add-on"><i class="icon-home"></i></span>
      <input id="Emp_Des" name="Emp_Des" class="input-xlarge" placeholder="" type="text" readonly="readonly">
    </div>
    
  </div>
</div>

<!-- Prepended text-->
<div class="control-group">
  <label class="control-label" for="Usu_Pas">Contraseña:</label>
  <div class="controls">
    <div class="input-prepend">
      <span class="add-on"><i class="icon-lock"></i></span>
      <input id="Usu_Pas" onKeyPress="if (event.keyCode===13){loginAjax();return false;}" name="encryptor" class="input-medium" placeholder="" type="password" required="true" autofocus="true">
    </div>
    
  </div>
</div>
<div id="msgAlert" style="height: 38px;"></div>
</fieldset>
</form>
      
  </div>
  <div class="modal-footer">
    <a href="#" data-dismiss="modal" class="btn">Cancelar</a>
    <a href="javascript:loginAjax()" class="btn btn-loginExa">Cambiar Empresa/Sucursal</a>
  </div>
</div>
<script>
       // Cambiado x Erik xq lo anterior mo era funcional
        <?php if ($_SESSION['Ses_Usu_Cad'] == 'N'){ ?>
            var Ses_Sys_Tim='0<?php echo strtotime(date('Y-m-d H:i:s'))-strtotime($Ses_Sys_Tim); ?>'*1;
            setInterval(function(){ 
                var html="";
                var s=Ses_Sys_Tim;
                var h=Math.floor(s/3600);
                var m=Math.floor(s/60)-(h*60);
                s=Math.floor(s-(h*3600)-(m*60));
                $('#ajaxConexion').html(Math.abs(h)+'hrs '+Math.abs(m)+'min '+Math.abs(s)+'seg');Ses_Sys_Tim += 1;
            }, 1000); 
        <?Php } ?>
</script>
<?php 
if($Ses_Usu_Tip!='C'){ 
    $soloUsers=true; 
    require_once("../../mascaras/model1/estilos/jqueryChat.php");
    include("adm_con_online_2.0.php"); ?>
<script type="text/javascript">
   var adapter=new DemoAdapter('<?Php echo $_SERVER['PHP_SELF']; ?>');
   DemoAdapterConstants.DEFAULT_ROOM_ID='<?php echo '1'; //$Ses_Emp_Cod; ?>';
   DemoAdapterConstants.DEFAULT_ROOM_NAME='<?php echo $Ses_Emp_Nom; ?>';
   DemoAdapterConstants.CURRENT_USER.Id='<?php echo $Ses_Prs_Cod; ?>';
   DemoAdapterConstants.CURRENT_USER.Name = "<?Php echo $nombre[0].' '.$apellido[0]; ?>";

   DemoAdapterConstants.CURRENT_USERS_ONLINE =<?php echo json_encode($response['users']); ?>;
        $(function () {  
            $.chat({
                // your user information
                userId:'<?php echo $Ses_Prs_Cod; ?>',
                // id of the room. The friends list is based on the room Id
                roomId: '<?php echo '1';//$Ses_Emp_Cod; ?>',                
                // the adapter you are using
                chatJsContentPath: '/chatjs/ChatJs/',
                adapter: adapter
            });
        });
    </script>
<?php } ?>      
    
</body>
</html>