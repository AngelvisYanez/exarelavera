<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/* 
* Descripción: Permite registrar los tipos de activos
* Fecha de actualización:	2011-04-01
* Desarrollador1:	Fierro Mauricio
* Fecha de actualización:	2013-04-23
* Desarrollador1:	Didimo Zamora
* Fecha de actualización:	2016-06-06
* Desarrollador1:	José Ambuludí
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_tipo_activ.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/**
* Objeto de Conexion de Activos fijos
*/
$obBD_conexion = new Class_Log_Conexion_Act($Ses_Dat_Dis);
/**
* Objeto de Acceso a Datos de Activos fijos	
*/
$obBD_con1 = new Class_Log_Datos_Act;
	
/**
 * Creación del objeto para evitar el reenvio
 */
$thisPost = new Post_Block;
/**
 * Lleva control de los registros Invalidados y sirve para crear la leyenda
 */
$anulada = 0; 
$nivel_actual = 0;		// El nivel actual en que se encuentran navegando
$nivel_superior = 0;	// El nivel anterior al actual
$cadena_codigo = "";	// Cadena del siguiente codigo a registrarse

/**
 * Recibimos la cadena de codigo contable para su tratamiento	
 */
if(isset($_POST['cdc_nivel']))
{
	$cadena_codigo = $_POST['cdc_nivel'];
}
/**
 * Si existe la variable back obtenemos el codigo y el nombre del nivel anterior de la base de datos.
 */
if(!isset($_POST['back']))
{
	$nivel_superior = 0;
}
else
{
	/**
	* Consulta los tipos de activos por empresa
	*/
	$rs_nivel_s = $obBD_con1->getRowConsulta(606,$_POST['back'],$obBD_conexion);	
	$nivel_superior = $rs_nivel_s['Tia_Rec'];
	/**
	* Consulta la descripción del tipo de activo
	*/
	
	$rs_nivel_s = $obBD_con1->getArrayConsulta(607,$nivel_superior.'*'.$Ses_Emp_Cod,$obBD_conexion);
	$nombre_nivel = $rs_nivel_s['Tia_Des'];
}
	
/**
 * Obtenemos el nivel actual que se carga con la pagina. OJO: No el codigo de la pagina desde donde se redirigio a la actual.
 */
if(isset($_POST['nivel_actual']))
{
	$nivel_actual = $_POST['nivel_actual'];
	unset($_POST['nivel_actual']);
}
else
{
	if(!isset($_POST['back']))
		$nivel_actual = 0;
	else
		$nivel_actual = $nivel_superior;
}
	
/** 
 * Si el nivel nombre del nivel existe, y si no estamos haciendo un BACK, cargamos el nombre de nivel en una variable.
 */
if(isset($_POST['nombre_nivel']))
{
	if(!isset($_POST['back']))
		$nombre_nivel = $_POST['nombre_nivel'];
		
}
if(isset($_POST['back']))
{
	unset($_POST['hdd']);
}

/**
 * Si existe la variable hdd grabamos los datos obtenidos enviados aqui por POST.
 */
if(isset($_POST['hdd']))
{
	if ($thisPost->postBlock($_POST['postID'])) 
	{				
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		$obBD_con1->operacionobBD(601,$_POST['Tia_Cdc']."*".$_POST['Tia_Des']."*".
									  $_POST['Tia_Dep']."*".$_POST['Tia_Obs']."*".
									  $_POST['Tia_Tip']."*".$nivel_actual."*".$Ses_Emp_Cod,$obBD_conexion);													
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}
}
	
/**
 * Si existe la variable edit editamos los datos obtenidos enviados aqui por POST
 */
if(isset($_POST['edit']))
{
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		$obBD_con1->operacionobBD(602,$_POST['Tia_Des']."*".$_POST['Tia_Dep']."*".
									  $_POST['Tia_Obs']."*".$_POST['Tia_Tip']."*".$nivel_actual,$obBD_conexion);
		$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
}
/**
 * Realizamos una consulta de todos los registros del nivel actual para listarlos.
 * Tambien obtenemos el numero de registros para uso de la cadena contable.
 */
$rs_nivel = $obBD_con1->getArrayConsulta(604,$nivel_actual,$obBD_conexion);
$total_nivel =  count($rs_nivel);
	/*
	 * Este proceso crea la cadena contable.
	 * 
	 * Toma el nivel actual. Si es 0 la cadena es igual al total de registros en el nivel mas uno.
	 * Ejm: Si hay 5 registros en el nivel 0, el nuevo codigo contable seria 6
	 * 
	 * En caso que no sea el nivel 0, se concatena a la subcadena de la cadena anterior, hasta el ultimo indice en que aparezca
	 * un punto, otro punto y el total de registros en este nivel mas uno.
	 * Ejm: 
	 * 
	 * Nivel anterior: 1.2.5
	 * Nivel nuevo: 1.2 + . + 5 + 1 -> 1.2.6
	 * 
	 * En caso que se este regresando atras, se hace el mismo proceso pero haciendo dos subcadenas.
	 * */
	if($nivel_actual == 0)
	{
		$cadena_codigo = $total_nivel + 1;
	}
	else
	{
		if(!isset($_POST['back']))
		{
			if(!strrpos($cadena_codigo, "."))
			 $cadena_codigo = $cadena_codigo . ".";
	  		 $cadena_codigo = substr($cadena_codigo, 0, strrpos($cadena_codigo, ".")) . "." . ($total_nivel + 1);	
		}
		else
		{
			$helper = substr($cadena_codigo, 0, strrpos($cadena_codigo, "."));
			$cadena_codigo = substr($helper, 0, strrpos($helper, ".")) . "." . ($total_nivel + 1);
			unset($_POST['back']);
		}
		
	}

?>
<html>
<head>
	<?php require_once "../../mascaras/model1/estilos/estilos.php";?>	
    							
	<script type="text/javascript" src="../VALIDACIONES/Validaciones.js"></script>
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>    
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<title><?php echo $Ses_Sys_Nom; ?></title>
</head>
<body>
<div id="set1">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr class="BarraTitulo">
    <td>&raquo; Registrar Tipo de Activos</td>
  </tr>
</table>
<!-- Tabla que muestra los niveles. Comienza en el cero y se carga con datos diferentes conforme se navega los niveles -->	

<?php 
		if($nivel_actual!=0)	
		{
			echo "<b>" . $nombre_nivel . "</b><br />";
		}
		else
		{  
			$nombre_nivel = "Ra&iacute;z";
			echo "<b>" . $nombre_nivel . "</b><br />";
		}
	?>	
    
    
	<table border="1" cellpadding="0" cellspacing="0" width="100%" class="fixedHeader01">
    <thead>
		<tr>
			<th style="width: 10%">C&oacute;d. Int.</th>
			<th>Categor&iacute;a</th>
			<th>Descripci&oacute;n</th>
			<th width="2%">&nbsp;</th>
		</tr>
     </thead>   
		<?php
			if($total_nivel<=0)
			{ ?>
				<tr><td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td><?Php echo error_alerta('!No hay registros que mostrar!', 1); ?></td>
				  <td>&nbsp;</td>
				</tr>
	<?php	}
			else
			{
				foreach($rs_nivel as $row_nivel)
				{
					if($row_nivel['Tia_Est']=='I')
					{
						$rojo = '#FF0000';
						$anulada++;
					}
					else
						$rojo='';
					?>
                    <tbody>
                    <tr>
                    	<td align="center"> <?php echo $row_nivel['Tia_Cod']; ?> 
                        </td>
						<td  > <?php echo $row_nivel['Tia_Cdc'];?> </td>
						<td  > <?php echo $row_nivel['Tia_Des'];?> </td>
						<td> 
                        
                        <form method="post" id="editar" name="editar" action="con_mod_tipo_activ.php"  >
                        		<input type="hidden" name="Tia_Obs" value="<?Php echo $row_nivel['Tia_Obs'];?> ">
                                <input type="hidden" name="Tia_Dep" value="<?Php echo $row_nivel['Tia_Dep'];?> ">
                                <input type="hidden" name="Tia_Tip" value="<?Php echo $row_nivel['Tia_Tip'];?> ">
                                <input type="hidden" name="nivel_actual" value="<?Php echo $row_nivel['Tia_Cod'];?> ">
                                <input type="hidden" name="nombre_nivel" value="<?Php echo $row_nivel['Tia_Des'];?> ">
                                <input type="hidden" name="cdc_nivel" value="<?Php echo $row_nivel['Tia_Cdc'];?> ">
                                <input type="hidden" name="nivel_superior" value="<?Php echo $row_nivel['Tia_Rec'];?>">
                        </form>
                        <?Php
						if($row_nivel['Tia_Tip']=='G')
						{
							if($row_nivel['Tia_Est']!='I')
								{
                        ?>
                        <form method="post" id="nav" name="nav" action="<?Php echo $_SERVER['PHP_SELF'];?>" >
                        		<input type="hidden" name="nivel_actual" value="<?Php echo $row_nivel['Tia_Cod'];?>" />
								<input type="hidden" name="nombre_nivel" value="<?Php echo $row_nivel['Tia_Des']; ?>"/>
								<input type="hidden" name="cdc_nivel" value="<?Php echo $row_nivel['Tia_Cdc'];?>"/>
								<input type="hidden" name="nivel_superior" value="<?Php echo $row_nivel['Tia_Rec'];?>" />
<button type="button" name="imageField" class='btn btn-success btn-mini' title="Seleccionar" onClick="this.form.submit()">
                                <i class='icon-arrow-right icon-white'></i> 
                                </button>
                        
                        </form>
                        <?Php
								}
								else
								{
									echo "&nbsp;";
								}						 							
						}
						else
						{
							echo "&nbsp";
						}
                        ?>
                    </td>
                    </tr>
      </tbody>   
                    <?php	
					
				}//fin 	foreach($rs_nivel as $row_nivel)
			} //fin if($total_nivel<=0)
			?>
	</table>
    
    	
	<?php 
		echo barra_estado($total_nivel);	
		/**
		 * Check if this thing is anulada
		 */
		if ($anulada > 0)
		{		
			$com_leyenda[1]=$anulada;
		}
		//echo "<br />";
		require_once('../../componentes/FRONT/com_con_leyenda.php');
	?>
    
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="form" id="form">
		<fieldset>
			<legend class="Titulos2">Datos a Registrar</legend>
			<?php  
				/**
				 * Creacion del campo REPOST
				 */
				$thisPost->startPost(); 
				echo mensaje_requerido(); 
			?>
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td width="14%" class="Etiqueta1">Categor&iacute;a:</td>
						<td width="86%">&nbsp;<input type="text" name="Tia_Cdc" id="Tia_Cdc" value="<?php echo $cadena_codigo; ?>" readonly="readonly"/>
						</td>
					</tr>
					<tr>
						<td width="14%" class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
						<td width="86%">&nbsp;<input name="Tia_Des" type="text" id="Tia_Des" size="65" maxlength="200"/></td>
					</tr>
					<tr>
						<td width="14%" class="Etiqueta1"><span class="Asterisco">*</span> Depreciable:</td>
						<td width="86%">&nbsp;<select id="Tia_Dep" name="Tia_Dep">
								<option value="S">S&iacute;</option>
								<option value="N">No</option>
							</select>
						</td>
					</tr>
					<tr>
						<td width="14%" class="Etiqueta1">Observaci&oacute;n:</td>
						<td width="86%">&nbsp;<textarea cols="50" name="Tia_Obs" id="Tia_Obs"></textarea></td>
					</tr>
					<tr>
						<td width="14%" class="Etiqueta1">Tipo:</td>
<td width="86%">&nbsp;<select id="Tia_Tip" name="Tia_Tip">
								<option value="G">Grupo</option>
								<option value="D">Detalle</option>
							</select>
					  </td>
					</tr>
				</table>
		</fieldset>
		<br />
        	<table> 
            <tr>
            <td>
            <input type="hidden" id="hdd" name="hdd" value="save" />
            <input type="hidden" id="nivel_actual" name="nivel_actual" value="<?php echo $nivel_actual; ?>" />
            <input type="hidden" id="nombre_nivel" name="nombre_nivel" value="<?php echo $nombre_nivel; ?>" />
            <input type="hidden" id="cdc_nivel" name="cdc_nivel" value="<?php echo $cadena_codigo; ?>" />
            <input type="hidden" id="nivel_superior" name="nivel_superior" value="<?php echo $nivel_superior; ?>" />  
            </td>
            </tr>
            </table>         
	</form>
			<form action="<?Php echo $_SERVER['PHP_SELF'];?>" method="post" name= "didi">
            <table width="287" border="0" cellpadding="0" cellspacing="0">
            	<tr><?Php
                    if($nivel_actual != 0)
                    { 
                    ?>
            		<td width="110">
                    <input type="hidden" id="back" name="back" value="<?php echo $nivel_actual;?>" />
                    <input type="hidden" id="cdc_nivel" name="cdc_nivel" value="<?Php echo $cadena_codigo;?>" />
                    <button name="atras" type="submit" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" id="atras" value="Atras">
                <i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>                
                    </button>
            		</td>         
                    <?Php
                    }
                    ?>                    
            		<td width="177">
                     <button type="button" id="guardar" class="btn btn-primary fileinput-button" value="guardar" onClick="validar_requeridos(document.getElementById('form'),'Tia_Des*Tia_Dep',1)">
                    <i class="icon-book icon-white"></i>   
            <span>Guardar</span>   
                    </button>          
            		</td>            
            	</tr>
            </table>
			</form>
</div> 
<script type="text/javascript" src="../VALIDACIONES/act_par_tipo_activ.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>           
</body>
<?php
/**
 * cierro las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/**  
 * fin cierre las conexiones 
 */
?>