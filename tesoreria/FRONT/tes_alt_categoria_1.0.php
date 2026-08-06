<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* Descripci�n: Permite registrar los tipos de categorias
* Fecha de actualizaci�n:	2011-04-01
* Desarrollador:	Mauricio Fierro
* Fecha de actualizaci�n:	2012-06-11
* Desarrollador:	Lewis Chimarro
*/	
require_once('../../administrador/LOGICA/seguridad.php');
require_once '../LOGICA/tes_log_categoria.php';
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/** 
* Creaci�n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;
/** 
* Creacion del Objeto de conexion 
*/  
$obBD_conexion = new Class_Log_Conexion_Tes;
/**
* Creaci�n del Objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Tes; 

/**
* Lleva control de los registros Invalidados y sirve para crear la leyenda
*/
$anulada = 0; 
/**
* El nivel actual en que se encuentran navegando
*/
$nivel_actual = 0;		
/**
* El nivel anterior al actual
*/
$nivel_superior = 0;		
/**
* Cadena del siguiente codigo a registrarse
*/
$cadena_codigo = "";	
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
		* Devuelve un directorio
		*/
		$rs_nivel_s = $obBD_con1->consulta(sentencias_tes(1024,$obBD_con1->parametros("".$_POST['back'])),$obBD_conexion->conexion);
		$row = $obBD_con1->fetch_assoc($rs_nivel_s);
		
		$nivel_superior = $row['Cat_Rec'];
		/**
		* Devuelve la descripci�n de un directorio superior
		*/
		$rs_nivel_s = $obBD_con1->consulta(sentencias_tes(1025,$obBD_con1->parametros("".$nivel_superior)),$obBD_conexion->conexion);
		$row = $obBD_con1->fetch_assoc($rs_nivel_s);
		
		$nombre_nivel = $row['Cat_Des'];
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
	/**
	* Si existe la variable hdd grabamos los datos obtenidos enviados aqui por POST.
	*/
	if(isset($_POST['hdd']))
	{
		if ($thisPost->postBlock($_POST['postID'])) 
		{				
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			$obBD_con1->grabarv_registros(sentencias_tes(1026,$obBD_con1->parametros($_POST['Cat_Cdc']."*".$_POST['Cat_Des']."*".
																					$_POST['Cat_Tip']."*".$nivel_actual."*".
																					$Ses_Emp_Cod)),$obBD_conexion->conexion);
			$obBD_con1->fin_transaccion($obBD_conexion->conexion);
		}
	}	
	/**
	* Si existe la variable edit editamos los datos obtenidos enviados aqui por POST
	*/
	if(isset($_POST['edit']))
	{
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			$obBD_con1->grabarv_registros(sentencias_tes(1027,$obBD_con1->parametros($_POST['Cat_Cod']."*".$_POST['Cat_Des']."*".
																					$_POST['Cat_Tip']."*".$nivel_actual)),$obBD_conexion->conexion);
			$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}
	/**
	* Realizamos una consulta de todos los registros del nivel actual para listarlos.
	*/
	/**
	* Tambien obtenemos el numero de registros para uso de la cadena contable.
	*/
	$rs_nivel = $obBD_con1->consulta(sentencias_tes(1028,$obBD_con1->parametros("".$nivel_actual.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);
	$row_nivel = $obBD_con1->registros();
	$total_nivel = $obBD_con1->numregistros();
	
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
		$cadena_codigo = $total_nivel + 1;
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<?php require_once "../../mascaras/model1/estilos/estilos.php"; ?>								
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
<div class="BarraTitulo">&raquo; Registrar  Categorias</div>
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
			<th width="14%">C&oacute;d. Int.</th>
			<th width="14%">Secuencia</th>
			<th width="42%">Descripci&oacute;n</th>
			<th width="20%">Tipo</th>
			<th width="10%">&nbsp;</th>
		</tr>
        </thead>
        <tbody>
		<?php
			if($total_nivel<=0)
			{ ?>
				<tr>
                	<td>&nbsp;</td>
                	<td>&nbsp;</td>
                	<td><?Php echo error_alerta('!No hay registros que mostrar!', 1); ?></td>
                	<td>&nbsp;</td>
                	<td>&nbsp;</td>
                </tr>
		<?Php
        	}
			else
			{
				do
				{
					if($row_nivel['Cat_Est']=='I')
					{
						$rojo = '#FF0000';
						$anulada++;
					}
					else
						$rojo=''; ?>						
					<tr>
						<td align='center'><FONT COLOR='<?Php echo $rojo; ?>'><?Php echo $row_nivel["Cat_Cod"]; ?></FONT></td>
						<td><FONT COLOR="<?Php echo $rojo; ?>"><?Php echo $row_nivel["Cat_Cdc"]; ?></FONT></td>
						<td><FONT COLOR='<?Php echo $rojo; ?>'><?php echo $row_nivel["Cat_Des"]; ?></FONT></td>
						<td align="center"><font color='<?Php echo $rojo; ?>'><?php if ($row_nivel["Cat_Tip"]=='G'){ echo "GRUPO"; }else{ echo "Detalle"; } ?></font></td>
						<td align="center">					
							<!--<form method='post' id='editar' name='editar' action='<?Php //echo $_SERVER['PHP_SELF']; ?>'>
							<input type='hidden' name='Cat_Tip' value='<?Php //echo $row_nivel['Cat_Tip']; ?>'/>
							<input type='hidden' name='nivel_actual' value='<?Php //echo $row_nivel['Cat_Cod']; ?>'/>
							<input type='hidden' name='nombre_nivel' value='<?Php //echo $row_nivel['Cat_Des']; ?>'/>
							<input type='hidden' name='cdc_nivel' value='<?Php //echo $row_nivel['Cat_Cdc']; ?>'/>
							<input type='hidden' name='nivel_superior' value'<?Php //echo $row_nivel['Cat_Rec']; ?>'/>
							<input type='image' name='imageField' src='../../mascaras/model1/imagenes/32x32/forward.png' width='22' height='22' title='Editar'>
							</form>-->
					<?Php
					if($row_nivel['Cat_Tip']=='G')
					{
						if($row_nivel['Cat_Est']!='I')
						{ ?>
							<form method='post' id='nav' name='nav' action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
								<input type='hidden' name='nivel_actual' value='<?Php echo $row_nivel['Cat_Cod']; ?>'/>
								<input type='hidden' name='nombre_nivel' value='<?Php echo $row_nivel['Cat_Des']; ?>'/>
								<input type="hidden" name="cdc_nivel" value="<?Php echo $row_nivel['Cat_Cdc'].'.0'; ?>"/>
								<input type='hidden' name='nivel_superior' value'<?php echo $row_nivel['Cat_Rec']; ?>' />
                                <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
        	<i class=" icon-arrow-right icon-white"></i>
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
					} ?>
					</td>
				</tr>
                <?Php
				}while($row_nivel = $obBD_con1->fetch_assoc($rs_nivel));
			} ?>
        </tbody>    
	</table>	
  <br /> 	
	<!-- Boton para regresar a los niveles anteriores -->
	<?php
	echo barra_estado($total_nivel+0);
	
		if($nivel_actual != 0)
		{ ?>
			<form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post'>
			<input type='hidden' id='back' name='back' value='<?php echo $nivel_actual; ?>' />
			<input type='hidden' id='cdc_nivel' name='cdc_nivel' value='<?Php echo $cadena_codigo; ?>' />			
            <button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="this.form.submit()">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atras&nbsp;&nbsp;</span>
       		 </button>
			</form>
      <?Php      
		}		
		/**
		* Check if this thing is anulada
		*/		
		if ($anulada > 0)
		{		
			$com_leyenda[1]=$anulada;
		}		
		echo "<br />";		
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
						<td width="14%" class="Etiqueta1">Secuencia:</td>
						<td width="86%">&nbsp;<input type="text" name="Cat_Cdc" id="Cat_Cdc" value="<?php echo $cadena_codigo; ?>" readonly="readonly"/>						</td>
					</tr>
					<tr>
						<td width="14%" class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
						<td width="86%">&nbsp;<input type="text" id="Cat_Des" name="Cat_Des"/></td>
					</tr>
					
					<tr>
						<td width="14%" class="Etiqueta1">Tipo:</td>
						<td width="86%">&nbsp;<select id="Cat_Tip" name="Cat_Tip">
								<option value="G">Grupo</option>
								<option value="D">Detalle</option>
							</select>						</td>
					</tr>
				</table>
		</fieldset>
		<br />
		<input type="hidden" id="hdd" name="hdd" value="save" />
		<input type="hidden" id="nivel_actual" name="nivel_actual" value="<?php echo $nivel_actual ?>" />
		<input type="hidden" id="nombre_nivel" name="nombre_nivel" value="<?php echo $nombre_nivel ?>" />
		<input type="hidden" id="cdc_nivel" name="cdc_nivel" value="<?php echo $cadena_codigo ?>" />
		<input type="hidden" id="nivel_superior" name="nivel_superior" value="<?php echo $nivel_superior ?>" />
        
        <button type="button" class="btn btn-primary start" title="Guardar" onClick="validar_requeridos(this.form,'Cat_Des',1)">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
    </button> 
	</form>
</div>
<script type="text/javascript" src="../VALIDACIONES/tes_par_categoria.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>    
</body>
</html>
<?php
@$obBD_con1->free_result($rs_nivel_s);
@$obBD_con1->free_result($rs_nivel);
/* cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* fin cierre las conexiones */
?>