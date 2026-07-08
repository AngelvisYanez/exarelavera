<?php 
/* 
* Alias:	Modificar
* Descripcion: Permite modificar los tipos de activos
* Fecha de actualizacion:	2011-04-01
* Desarrollador:	Fierro Mauricio
* MULTIEMPRESAS = 
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once '../LOGICA/fac_log_categoria.php';
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');
		
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
$obBD_con1 =  new Class_Log_Datos_Tes; 
	
/* Creacion del objeto para evitar el reenvio*/
$thisPost = new Post_Block;

// Lleva control de los registros Invalidados y sirve para crear la leyenda
$anulada = 0;

$nivel_actual = 0;		// El nivel actual en que se encuentran navegando
$nivel_superior = 0;		// El nivel anterior al actual
$cadena_codigo = "";	// Cadena del siguiente codigo a registrarse

$descripcion = "";
$estado = '';

if(isset($_POST['delete']))
{
	$descripcion = $_POST['nombre_nivel'];
	$codigo = $_POST['cdc_nivel'];
	$estado = $_POST['Cat_Est'];
}

// Recibimos la cadena de codigo contable para su tratamiento	
if(isset($_POST['cdc_nivel']))
{
	$cadena_codigo = $_POST['cdc_nivel'];
}
	
	// Si existe la variable back obtenemos el codigo y el nombre del nivel anterior de la base de datos.
	if(!isset($_POST['back']))
	{
		$nivel_superior = 0;
	}
	else
	{
		$rs_nivel_s = $obBD_con1->consulta(sentencias_tes(1024,$obBD_con1->parametros("".$_POST['back'])),$obBD_conexion->conexion);
		$row = $obBD_con1->fetch_assoc($rs_nivel_s);
		
		$nivel_superior = $row['Cat_Rec'];
		
		$rs_nivel_s = $obBD_con1->consulta(sentencias_tes(1025,$obBD_con1->parametros("".$nivel_superior)),$obBD_conexion->conexion);
		$row = $obBD_con1->fetch_assoc($rs_nivel_s);
		
		$nombre_nivel = $row['Cat_Des'];
	}
	
	// Obtenemos el nivel actual que se carga con la pagina. OJO: No el codigo de la pagina desde donde se redirigio a la actual.
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
	
	// Si el nivel nombre del nivel existe, y si no estamos haciendo un BACK, cargamos el nombre de nivel en una variable.
	if(isset($_POST['nombre_nivel']))
	{
		if(!isset($_POST['back']))
			$nombre_nivel = $_POST['nombre_nivel'];
			
	}
	
	// Si existe la variable delete ihabilitamos los datos obtenidos enviados aqui por POST
	if(isset($_POST['hdd']))
	{
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			$obBD_con1->grabarv_registros(sentencias_tes( 1029,$obBD_con1->parametros($_POST['Cat_Est']."*".$_POST['Cat_Cod']."*")),$obBD_conexion->conexion);
			$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}
	
	// Realizamos una consulta de todos los registros del nivel actual para listarlos.
	// Tambien obtenemos el numero de registros para uso de la cadena contable.
	$rs_nivel = $obBD_con1->consulta(sentencias_tes(1028,$obBD_con1->parametros($nivel_actual.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);
	$row_nivel = $obBD_con1->registros();
	$total_nivel = $obBD_con1->numregistros();
	
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<?php require_once "../../mascaras/model1/estilos/estilos.php"; ?>								
	<script type="text/javascript" src="../VALIDACIONES/Validaciones.js"></script>
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<title><?php echo $Ses_Sys_Nom; ?></title>
</head>
<body>
<table width="100%">
	<tr class="BarraTitulo">
    	<td>
			&raquo; Anular  Categorias
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
	<table border="1" cellpadding="0" cellspacing="0" width="100%">
		<tr class="Cabecera1">
			<th style="width: 10%;">C&oacute;d. Int.</th>
			<th>Categoria</th>
			<th>Descripci&oacute;n</th>
			<th width="2%">&nbsp;</th>
			<th width="2%">&nbsp;</th>
		</tr>
		<?php
			if($total_nivel<=0)
			{
				echo "<tr><td colspan='4'>".error_alerta('!No hay registros que mostrar!', 1)."</td></tr>";
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
						$rojo='';
						
					echo "<tr ".focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo")." class='Fondo'>";
					echo "<td align='center'><FONT COLOR='$rojo'>" . $row_nivel["Cat_Cod"] . "<FONT></td>";
					echo "<td><FONT COLOR='$rojo'>" . $row_nivel["Cat_Cdc"] . "<FONT></td>";
					echo "<td><FONT COLOR='$rojo'>" . $row_nivel["Cat_Des"] . "<FONT></td>";
					echo "<td>";
					
					echo "<form method='post' id='delete' name='delete' action='".$_SERVER['PHP_SELF']."'>" . 
							"<input type='hidden' name='Cat_Est' value='" . $row_nivel['Cat_Est'] . "'/>" .
							"<input type='hidden' name='nombre_nivel' value='" . $row_nivel['Cat_Des'] . "'/>" .
							"<input type='hidden' name='cdc_nivel' value='" . $row_nivel['Cat_Cdc'] . "'/>" .
							"<input type='hidden' name='Cat_Cod' value='" . $row_nivel['Cat_Cod'] . "'/>" .
							"<input type='hidden' name='nivel_actual' value='" . $nivel_actual . "' />" .
							"<input type='hidden' name='nivel_superior' value'" . $nivel_superior . "' />" .
							"<input type='hidden' name='delete' value='delete'/>" .
							"<input type='image' name='imageField' src='../../mascaras/model1/imagenes/32x32/eliminar.jpg' width='22' height='22' title='Anular'></form></td>";

					if($row_nivel['Cat_Tip']=='G')
					{
						if($row_nivel['Cat_Est']!='I')
						{
							echo "<td><form method='post' id='nav' name='nav' action='" . $_SERVER['PHP_SELF'] . "'>" . 
								"<input type='hidden' name='nivel_actual' value='" . $row_nivel['Cat_Cod'] . "'/>" .
								"<input type='hidden' name='nombre_nivel' value='" . $row_nivel['Cat_Des'] . "'/>" .
								"<input type='hidden' name='cdc_nivel' value='" . $row_nivel['Cat_Cdc'] . ".0'/>" .
								"<input type='hidden' name='nivel_superior' value'" . $row_nivel['Cat_Rec'] . "' />" .
								"<input type='image' name='imageField' src='../../mascaras/model1/imagenes/32x32/forward.png' width='22' height='22' title='Editar'></form>";
						}
						else
						{
							echo "<td>&nbsp;";
						}
						 							
					}
					else
					{
						echo "<td>&nbsp;";
					}
					
					echo "</td>";					
					echo "</tr>";
				}while($row_nivel = $obBD_con1->fetch_assoc($rs_nivel));
			}
		?>
	</table>	
	<br /> 	
	<!-- Boton para regresar a los niveles anteriores -->
	<?php
	echo barra_estado($total_nivel);
	
		if($nivel_actual != 0)
		{
			echo "<form action='" . $_SERVER['PHP_SELF'] . "' method='post'>";
			echo "<input type='hidden' id='back' name='back' value='$nivel_actual' />";
			echo "<input type='hidden' id='cdc_nivel' name='cdc_nivel' value='$cadena_codigo' />";
			echo "<input name='atras' type='submit' class='Boton_Atras' title='Atr&aacute;s' id='atras' value='Atras'>";
			echo "</form>";
		}
	?>	
	
	<?php 
		// Check if this thing is anulada
		
		if ($anulada > 0)
		{		
			$com_leyenda[1]=$anulada;
		}
		
		echo "<br />";
		
		require_once('../../componentes/FRONT/com_con_leyenda.php');
	?>

	<?php 
		if(isset($_POST['delete']))
		{
	?>
	<form method="post" action="<?php $_SERVER['PHP_SELF']; ?>" name="form" id="form">
		<fieldset>
			<legend class="Titulos2">Registro a Eliminar</legend>
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td width="14%" class="Etiqueta1">Categoria:</td>
						<td width="86%">&nbsp;<input type="text" name="Cat_Cdc" id="Cat_Cdc" value="<?php echo $codigo; ?>" readonly="readonly"/>
						</td>
					</tr>

					<tr>
						<td width="14%" class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
						<td width="86%">&nbsp;<input type="text" id="Cat_Des" name="Cat_Des" value="<?php echo $descripcion ?>" readonly="readonly"/></td>
					</tr>
					<tr>
						<td width="14%" class="Etiqueta1"><span class="Asterisco">*</span> Estado:</td>
						<td width="86%">&nbsp;
							<select id="Cat_Est" name="Cat_Est">
								<?php 
									if($estado == 'A')
									{
										echo "<option value='A' selected='selected'>Activo</option>";
										echo "<option value='I'>Inactivo</option>";
									}
									else
									{
										echo "<option value='A'>Activo</option>";
										echo "<option value='I' selected='selected'>Inactivo</option>";
									}
								?>
							</select>
						</td>
					</tr>
				</table>
		</fieldset>
		<br />
		<input type="hidden" id="hdd" name="hdd" value="delete" />
		<input type="hidden" id="nombre_nivel" name="nombre_nivel" value="<?php echo $nombre_nivel ?>" />
		<input type="hidden" id="nivel_actual" name="nivel_actual" value="<?php echo $nivel_actual ?>" />
		<input type="hidden" id="cdc_nivel" name="cdc_nivel" value="<?php echo $cadena_codigo ?>" />
		<input type="hidden" id="nivel_superior" name="nivel_superior" value="<?php echo $nivel_superior ?>" />
		<input type="hidden" id="Cat_Cod" name="Cat_Cod" value="<?php echo $_POST['Cat_Cod'] ?>" />
		<input type="submit" id="guardar" class="Boton_Guardar" value="guardar" onClick="validar_requeridos(this.form,'Cat_Des*Cat_Est',1)"/>
	</form>
	<?php 
		}
	?>
</body>
<?php
@$obBD_con1->free_result($rs_areas);
@$obBD_con1->free_result($rs_nodos);
@$obBD_con1->result($rs_direc);
@$obBD_con1->result($rs_cargos);
@$obBD_con1->result($rs_direca);
/* cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* fin cierre las conexiones */
?>