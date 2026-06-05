<?php
/** 
Descripci�n: P�gina de inicio del sistema
Fecha de actualizaci�n:	2012-11-25
Desarrollador:	Lewis Chimarro 
Fecha de actualizaci�n:	2013-08-12
Desarrollador:	Lewis Chimarro 
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

if($Browser == "WML") 
{
    header("Location: ".$wmlredirect);
    exit;
}//Fin del if($Browser == "WML") 
/**
* Ajax para identificar el numero de empresas 
*/
if (isset($ajax_empresas2))
{
	/**
	* Creacion del Objeto de conexion 
	*/
	$obBD_conexion = new Class_Log_Conexion_Log;
	/**
	* Creaci�n del objeto mysql para las consultas 
	*/
	$obBD_con1 =  new Class_Log_Datos_Log;	  
        
	$rs_empresas = $obBD_con1->getArrayConsulta(1, trim($ajax_username), $obBD_conexion); 
	utf8_encode_deep($rs_empresas);
	$conteo=count($rs_empresas); $html='';
	if($conteo>0){
		if ($conteo > 1)
		{ 
            $html=$html."<option value=''></option>";
			foreach($rs_empresas as $row_rs_empresas){  
				$html=$html."<option value='".$row_rs_empresas['Emp_Cod']."' data-Emp_Nom='".($row_rs_empresas['Emp_Nom'])."' data--suc_-cod='$row_rs_empresas[Suc_Cod]'>".($row_rs_empresas['Emp_Cor']).' ('.($row_rs_empresas['Suc_Des']).")</option>";
			}
		}
		else{
			//var_dump($rs_empresas);
			$html=$html."<option value='".$rs_empresas[0]['Emp_Cod']."' selected='selected'  data-Emp_Nom='".$rs_empresas[0]['Emp_Nom']."' data--suc_-cod='$row_rs_empresas[Suc_Cod]'>".$rs_empresas[0]['Emp_Cor']."</option>";
		}//Fin del if ($total_rs_empresas > 1)
    }
		$obBD_conexion->cerrar();	    
		$res=array('success'=>true,'conteo'=>$conteo,'html'=>$html);
        echo json_encode($res);
	 exit();
}//Fin del if (isset($ajax_empresas))
if(isset($_SESSION) && !( !isset($_SESSION['Ses_Lis_Per'])||!isset($_SESSION['Ses_Emp_Cod'])||!isset($_SESSION['Ses_Usu_Ced']) )) header('Location: '.'./administrador/FRONT/home.php');
?>
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->

<!-- Mirrored from wbpreview.com/previews/WB0164888/login.html by HTTrack Website Copier/3.x [XR&CO'2010], Tue, 23 Oct 2012 00:39:01 GMT -->
<head>
	<meta http-equiv="Content-Type" content="text/html;">
	<title>Iniciar Sesi&oacute;n</title>
    <meta charset= "UTF-8">
	<meta name="description" content="">
	<meta name="author" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<!--<link rel="icon" type="image/png" href="mascaras/model1/img/logo/exa_web.gif"/>-->
    <link rel="shortcut icon" type="image/x-icon" href="mascaras/model1/img/logo/exa-ico-2.png" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,800">    
	<link rel="stylesheet" href="mascaras/model1/css/font-awesome.css">
	<link rel="stylesheet" href="mascaras/model1/css/bootstrap.css">
	<link rel="stylesheet" href="mascaras/model1/css/bootstrap-responsive.css">
	<link rel="stylesheet" href="mascaras/model1/css/ui-lightness/jquery-ui-1.8.21.custom.css">	
	<link rel="stylesheet" href="mascaras/model1/css/application.css">
	<script src="mascaras/model1/js/libs/modernizr-2.5.3.min.js"></script>
	<!--[if !IE]> -->
		<link type="text/css" rel="stylesheet" href="./framework/plugins/animate/animate-3.4.0.min.css" />			
	<!-- <![endif]-->
	<link rel="stylesheet" href="./Librerias/tooltip/jquery.tooltip.css" />
        <link rel="stylesheet" type="text/css" media="screen" href="framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    
        <style>
                /* Estilos para el aviso flotante */
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
            display: none; /* Ocultar inicialmente el aviso */
        }
        /* Estilos para el botón de cerrar */
        .cerrar-aviso {
            position: absolute;
            top: 5px;
            right: 5px;
            cursor: pointer;
            font-size:30px;
            color:white;
            background:red;
            border-radius:50%;
            padding:6px;
        }
        /* Estilos para la imagen dentro del aviso */
        .aviso-flotante img {
           /* max-width: 100%;*/
            width: 400px;
            height: auto;
        }
        body {
          background-color: #000000;
            /*margin: 0;
            overflow: hidden;
            background-color: #f7f6f6;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;*/
        }

        canvas {
            position: fixed;
            top: 0;
            left: 0;
            z-index: -1;
        }

        .container {
            width: 30%;
            text-align: center;
            background-color: #000000;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 0, 0, 0.1);
            position: relative;
            margin-top: 50px;
        }
    </style>

    <style type="text/css">
		#logo{position:relative;top:70px;}
		#browser {/*position:relative;*/bottom: 0px;width:100%;}
		.repeat2{-webkit-animation-iteration-count: 2 !important;animation-iteration-count: 2 !important;}        
		.delay1s{-webkit-animation-delay: 0.2s !important;animation-delay: 0.2s !important;} 
		.delay2s{-webkit-animation-delay: 0.6s !important;animation-delay: 0.6s !important;}
                .over{white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
                .over.desc{font-size:9px !important;}
                .form-control.chosen-single{height: 40px !important; font-size:13px !important; padding-top: 11px !important;padding-bottom: 10px;}
                .chosen-container .chosen-results li.active-result,.chosen-container-single .chosen-single span{font-family: 'Open Sans';text-align: left;text-shadow: none;}
                .chosen-container .chosen-results li.active-result{padding-top: 3px;padding-bottom: 3px;}
                .bs-chosen.chosen-container-single .chosen-drop{margin-top: -4px !important;}
                .form-control.chosen-single div {top: 9px;}    
                .chosen-container .chosen-results{max-height: 120px;}

	</style>        
</head>
<body class="login">

<div id="logo" align="center" class="animated rubberBand delay1s" style="height:190px;top: 10px;"><img src="mascaras/model1/img/logo/logo2.png?x=1"  /></div>

<!-- /account-container -->
<div class="login-extra animated pulse repeat2" style="width:382px;">
  <div class="account-container login stacked" style="margin-top: 25px;">
    <div class="content clearfix">
      <form action="administrador/FRONT/adm_con_control_1.2.php" method="post" name="acceso" id="acceso">
        <h1>Iniciar Sesi&oacute;n</h1>
        <div class="login-fields">
          <p>Inicie sesi&oacute;n con su cuenta registrada:</p>
          <div class="field">
            <label for="user_name">Usuario:</label>
            <input type="text" id="user_name" name="user_name" value="" placeholder="Usuario" class="login username-field" onBlur="if(trim(this.value) !== ''){ loadEmp(this.value);/*ajax_classic('<?Php echo isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : ''; ?>?ajax_empresas=1&ajax_username='+this.value,'div_empresas');*/ }" />
          </div>
          <!-- /field -->          
          <div class="field">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" value="" placeholder="Contraseña" class="login password-field" oncontextmenu="return false" 
                  		onKeyPress="if (event.keyCode===13){document.getElementById('encryptor').value = md5(document.getElementById('password').value); this.form.submit();}else{return  validar_injections(event);}"/>
          </div>
          <!-- /password -->
          <div id="div_empresas" style="margin-bottom: 5px;display: none;"><img src="mascaras/model1/img/signin/empresa.png">&nbsp;<select name="Emp_Cod" id="Emp_Cod" data-placeholder="Seleccione Empresa...">
                  <option value=""></option></select></div>
          <!-- /field -->
        </div>
        <!-- /login-fields -->
        <div class="login-actions">
          <input type="hidden" name="encryptor" id="encryptor" />
		  <input type="hidden" name="Suc_Cod" id="Suc_Cod" />
          <button class="button btn btn-loginExa btn-large" type="button" onClick="document.getElementById('Suc_Cod').value=$('#Emp_Cod option:selected').data('Suc_Cod'); document.getElementById('encryptor').value = md5(document.getElementById('password').value); this.form.submit();">Entrar</button>
        </div>        
        <?php if ((isset($_GET["errorusuario"])&&$_GET["errorusuario"]=="si")||(isset($_GET["errorsistema"])&&$_GET["errorsistema"]=="si")){ echo '<div style="float: left;width:100%;padding-bottom: 0px;"><div class="alert alert-error" style="margin-bottom:0px;"><span>'.(isset($_GET["errorusuario"])?'Datos incorrectos':'Error del Sistema').'</span></div></div> '; } ?>        
        <!-- .actions -->
      </form>             
    </div>
    <!-- /content -->
  </div>  
</div>          
<!-- Text Under Box -->
<!--<div class="login-extra">Recuperar <a href="#">Contraseña</a>
</div> --><!-- /login-extra -->

<script src="mascaras/model2/js/libs/jquery-1.7.2.min.js"></script>
<script src="mascaras/model2/js/libs/jquery-ui-1.8.21.custom.min.js"></script>
<script src="mascaras/model2/js/libs/jquery.ui.touch-punch.min.js"></script>
<script src="mascaras/model2/js/libs/bootstrap/bootstrap.min.js"></script>
<script src="mascaras/model2/js/signin.js"></script>


	<script language="javascript" src="Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript">
                    $(document).ready(function() { 
                        $('#browser *').tooltip({showURL: false});
                        $('#Emp_Cod').chosenDesc({width:'88%',
                            template:function (t,d){ return '<div class="over"><b>'+t+'</b></div><div class="over desc">'+d['emp_nom']+'</div>';}
                        });
                        $("#Emp_Cod_chosen").addClass('bs-chosen').find('.chosen-single').addClass('form-control empresas');
                        $("#Emp_Cod_chosen").find(".chosen-search").find('input').addClass('text');
                    });
                    
                    function loadEmp(ced){
                        $.post('',{ajax_empresas2:true,ajax_username:ced},function(r){
                            $('#Emp_Cod').html(r['html']);
                            if(r['success']&&r['conteo']>1) {$('#div_empresas').show();}
                            else $('#div_empresas').hide();
							$('#Emp_Cod').trigger('chosen:updated');
                        },'json').fail(function (){
                            $('#Emp_Cod').html('<option value=""></option>');
                            $('#div_empresas').hide();
							$('#Emp_Cod').trigger('chosen:updated');
                        });
                    } 
                </script>    

<div id="">
    <div align="center" style="text-align:right" class=""><span class="span6">Copyright &copy; <?Php date_default_timezone_set('UTC');echo date("Y"); ?>. All rights reserved.<br>&nbsp;Designed by CorproInfo S.A.</span></div>  
    <div id="browser" align="center" style="text-align:center"  class="">
            <span class="span6">Aplicaci&oacute;n optimizada para &nbsp;<img src="mascaras/model1/imagenes/32x32/chrome-2-32.png" width="32" height="32" title="Google Chrome">  <img src="mascaras/model1/imagenes/32x32/firefox-32.png" width="32" height="32" title="Firefox"> <img src=	"mascaras/model1/imagenes/32x32/ie-32.png" width="32" height="32" title="Internet Explorer"></span>
    </div>
</div>
<script src="framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
<!-- <script src="./framework/php/ventanasSocket/socketExaVentanas.js"></script> -->
<script>
var socketVentanas, Ses_Usu_Cod=0, Ses_Emp_Cod=0;
$.isUnd=function(v){ return v===undefined; };
$.varValid=$.vv=function(v){return (v!==null && !$.isUnd(v));};
$.isObject=$.isObj=function(v){return $.vv(v)&&!$.isArray(v)&&typeof v==='object';};
$.jsonParser=function(v){if($.isArray(v)||$.isObj(v)){return JSON.stringify(v);}else{try{return JSON.parse(v);}catch(e){return v;}}};                
$.setLocalStore=function(name,data){ localStorage.setItem(name, $.jsonParser(data)); if($.isUnd(data)) localStorage.removeItem(name); };
$.getLocalStore=function(name){ var data=localStorage.getItem(name); if($.varValid(data)) return $.jsonParser(data); };
$.getCookie=function(cname){ var na=cname+"=",dc=decodeURIComponent(document.cookie),ca=dc.split(';');for(var i=0;i<ca.length;i++){ var c=ca[i]; while(c.charAt(0)===' '){c=c.substring(1);} if(c.indexOf(na)===0){return c.substring(na.length, c.length);} } return ""; };
(function ($) {
    $.fn.chosenDesc = function (options) {
        return this.each(function () { options=(typeof options!=='undefined'?options:{});
            var $select = $(this),descMap = {},template=(typeof options['template']!=='undefined'?options['template']:function (text, templateData){return text;});
            $select.find('option').filter(function () { return $(this).text(); }).each(function (i) {$(this).attr('data-numero',i); descMap[i]=$(this).data(); descMap[i]['opttxtsaved']=$(this).text();});            
            $select.chosen(options); $chosen = $select.next().addClass('chosenDesc-container');            
            $select.bind('chosen:searchready', function () { setTimeout(function () {$chosen.find('.chosen-results li').each(function (i) { $li=$(this),index=$li.attr('data-option-array-index')-1; $li.html(template($li.html(),descMap[index]));});}, 0); });
            $select.bind('chosen:showing_dropdown chosen:activate', function () { setTimeout(function () {$chosen.find('.chosen-results li').each(function (i) { $li=$(this);$li.html(template(descMap[i]['opttxtsaved'],descMap[i])); });}, 0); });
            $select.bind('chosen:updated', function () { descMap = {};$select.find('option').filter(function () {return $(this).text();}).each(function (i) {$(this).attr('data-numero',i);descMap[i] = $(this).data();descMap[i]['opttxtsaved']=$(this).text();}); });
        });
    };
    /* socketVentanas = new SocketVentanas();
    socketVentanas.setMain();
    socketVentanas.reloadWS=false;
    socketVentanas.reloadPage=false;
    socketVentanas.extraInit=function (){         
        setTimeout(function(){ socketVentanas.send('logout'); },1000);
    };
    socketVentanas.connectDefault(); */
    
    //socketVentanas.quit();
})(jQuery);

</script> 

<!-- <div class="aviso-flotante" id="avisoFlotante">
        <span class="cerrar-aviso" onclick="cerrarAviso()">&times;</span> 
        <img src="mascaras\model1\img\logo\AvisoNewYear.jpeg" alt="Aviso" id="imagenAviso">
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
        </script>
         </div> -->
</body>

<!-- Mirrored from wbpreview.com/previews/WB0164888/login.html by HTTrack Website Copier/3.x [XR&CO'2010], Tue, 23 Oct 2012 00:39:01 GMT -->
</html>