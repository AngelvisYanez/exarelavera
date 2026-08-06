<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* Descripci�n: Permite modificar las ubicaciones
* Fecha de actualizaci�n:	2013-09-10
* Desarrollador:	Fabian Gallardo G.
*/	
require_once('../../administrador/LOGICA/seguridad.php');
require_once '../LOGICA/fac_log_ubicacion.php';
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/** 
* Creaci�n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;
/** 
* Creacion del Objeto de conexion 
*/  
$obBD_conexion = new Class_Log_Conexion_Ubi($Ses_Dat_Dis);
/**
* Creaci�n del Objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Ubi; 

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
		$row = $obBD_con1->getRowConsulta(10,"".$_POST['back'],$obBD_conexion);
		$nivel_superior = $row['Ubi_Rec'];
		
		/**
		* Devuelve la descripci�n de un directorio superior
		*/
		$row = $obBD_con1->getRowConsulta(20,"".$nivel_superior,$obBD_conexion);
		$nombre_nivel = $row['Ubi_Des'];
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
			/*
			* Inicio de la transaccion
			*/
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			/**
			*	Definicion de parametros
			*/
			$parametros = $_POST['Ubi_Des']."*".$_POST['Ubi_Obs']."*".$_POST['Ubi_Cod'];
			/** 
			* Se guarda la el tipo de precio 
			*/
			$obBD_con1->operacionobBD(50, $parametros, $obBD_conexion);
			/*
			* Fin del la transaccion
			*/
			$obBD_con1->fin_transaccion($obBD_conexion->conexion);
		}
	}	
	
	/**
	* Tambien obtenemos el numero de registros para uso de la cadena contable.
	*/
	$rs_nivel = $obBD_con1->getArrayConsulta(30,$nivel_actual.'*'.$Ses_Emp_Cod,$obBD_conexion);
	$total_nivel = count($rs_nivel);
	
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
	 * 
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
	}*/
	

if(isset($_POST['edit']))
{
	$descripcion = $_POST['nombre_nivel'];
	$observacion = $_POST['obs_nivel'];
	$codigo = $_POST['Ubi_Cod'];
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
<table width="100%">
	<tr class="BarraTitulo">
    	<td>
			&raquo; Modificar  Ubicaci&oacute;n
		</td>
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
			<th width="10%">C&oacute;d. Int.</th>
			<th width="35%">Descripci&oacute;n</th>
            <th width="35%">Observaci&oacute;n</th>
			<th width="10%">&nbsp;</th>
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
				foreach($rs_nivel as $row_nivel)
				{
					if($row_nivel['Ubi_Est']=='I')
					{
						$rojo = '#FF0000';
						$anulada++;
					}
					else
						$rojo=''; ?>						
					<tr>
						<td align='center'><FONT COLOR='<?Php echo $rojo; ?>'><?Php echo $row_nivel["Ubi_Cod"]; ?></FONT></td>
						<td><FONT COLOR='<?Php echo $rojo; ?>'><?php echo $row_nivel["Ubi_Des"]; ?></FONT></td>
                        <td><FONT COLOR='<?Php echo $rojo; ?>'><?php echo $row_nivel["Ubi_Obs"]; ?></FONT></td>
						<td align="center">
					<?Php
				if($row_nivel['Ubi_Est']!='I')
				{		?>			
					<form method='post' id='editar' name='editar' action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
							<input type='hidden' name='Ubi_Cod' value='<?Php echo $row_nivel['Ubi_Cod']; ?>'/>
							<input type='hidden' name='nombre_nivel' value='<?Php echo $row_nivel['Ubi_Des']; ?>'/>
                            <input type='hidden' name='obs_nivel' value='<?Php echo $row_nivel['Ubi_Obs']; ?>'/>
							<input type='hidden' name='nivel_actual' value='<?Php echo $nivel_actual; ?>' />
							<input type='hidden' name='edit' value='edit'/>							
                            <button type="button" class="btn btn-primary btn-mini" title="Editar" onclick="this.form.submit()" >
		           					<i class="icon-edit icon-white"></i>
		           			</button>
                    </form>
                  <?Php          
					}
					else
					{
						echo "&nbsp;";
					} ?>
					</td>
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
					if($row_nivel['Ubi_Est']!='I')
					{ ?>
						<form method='post' id='nav' name='nav' action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
							<input type='hidden' name='nivel_actual' value='<?Php echo $row_nivel['Ubi_Cod']; ?>'/>
							<input type='hidden' name='nombre_nivel' value='<?Php echo $row_nivel['Ubi_Des']; ?>'/>
							<input type='hidden' name='nivel_superior' value'<?php echo $row_nivel['Ubi_Rec']; ?>' />
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
					?>
					</td>
				</tr>
                <?Php
				}
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
            <button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="this.form.submit()">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
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
	<?php if($total_nivel > 0){?>
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
						<td width="14%" class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
						<td width="86%">&nbsp;<input type="text" id="Ubi_Des" name="Ubi_Des" value="<?php echo $descripcion;?>" style="text-transform:uppercase"/></td>
					</tr>
					<tr>
						<td width="14%" class="Etiqueta1">&nbsp;Observaci&oacute;n:</td>
						<td width="86%">&nbsp;<textarea id="Ubi_Obs" name="Ubi_Obs" cols="20" style="text-transform:uppercase"><?php echo $observacion;?></textarea></td>
					</tr>
				</table>
		</fieldset>
		<br />
		<input type="hidden" id="hdd" name="hdd" value="save" />
		<input type="hidden" id="nivel_actual" name="nivel_actual" value="<?php echo $nivel_actual; ?>" />
		<input type="hidden" id="Ubi_Cod" name="Ubi_Cod" value="<?php echo $codigo; ?>" />
		<input type="hidden" id="nivel_superior" name="nivel_superior" value="<?php echo $nivel_superior;?>" />
        
        <button type="button" class="btn btn-primary start" title="Guardar" onClick="validar_requeridos(this.form,'Ubi_Des',1)">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
    </button> 
	</form>
    <?php }?>
</div>
<script type="text/javascript" src="../VALIDACIONES/fac_par_categoria.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>    
</body>
</html>
<?php
/**
 * Cierro las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/**
 * fin cierre las conexiones 
 */
?>