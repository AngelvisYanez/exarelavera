<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/* 
* Descripcion: Permite modificar las categorias de productos
* Fecha de actualizacion:	2011-04-01
* Desarrollador:	Fierro Mauricio
* Fecha de actualizacion:	2012-06-13
* Desarrollador:	Lewis Chimarro
*/
require_once '../../administrador/LOGICA/seguridad.php';
require_once '../LOGICA/tes_log_categoria.php';
require_once '../../Librerias/procedimientos/almacenados_standar.php';	
require_once '../../Librerias/postclass.php';
	
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
$descripcion = "";
$tipo = '';
	
if(isset($_POST['edit']))
{
	$descripcion = $_POST['nombre_nivel'];
	$codigo = $_POST['cdc_nivel'];
	$tipo = $_POST['Cat_Tip'];
}
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
	* Si existe la variable edit editamos los datos obtenidos enviados aqui por POST
	*/
	if(isset($_POST['hdd']))
	{
		if ($thisPost->postBlock($_POST['postID'])) 
		{				
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		/**
		* Actualiza la categoria
		*/
		$obBD_con1->grabarv_registros(sentencias_tes(1031,$obBD_con1->parametros($_POST['Cat_Cod']."*".$_POST['Cat_Des']."*".																			$_POST['Cat_Tip'])),$obBD_conexion->conexion);
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
		}
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
<div class="BarraTitulo">&raquo; Modificar  Categorias </div>
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
			<th width="42%">Tipo</th>
			<th width="20%">&nbsp;</th>
			<th width="10%">&nbsp;</th>
		</tr>
        </thead>
        <tbody>
		<?php
			if($total_nivel<=0)
			{ ?>
				<tr><td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td><?Php echo error_alerta('!No hay registros que mostrar!', 1); ?></td>
				  <td>&nbsp;</td>
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
						<td><FONT COLOR='<?Php echo $rojo; ?>'><?Php echo $row_nivel["Cat_Cdc"];  ?></FONT></td>
						<td><FONT COLOR='<?Php echo $rojo; ?>'><?Php echo $row_nivel["Cat_Des"]; ?></FONT></td>
						<td align="center"><?php if ($row_nivel["Cat_Tip"]=='G'){ echo "GRUPO"; }else{ echo "Detalle"; } ?></td>
						<td align="center">
					<?Php
				if($row_nivel['Cat_Est']!='I')
				{		?>			
					<form method='post' id='editar' name='editar' action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
							<input type='hidden' name='Cat_Tip' value='<?Php echo $row_nivel['Cat_Tip']; ?>'/>
							<input type='hidden' name='Cat_Cod' value='<?Php echo $row_nivel['Cat_Cod']; ?>'/>
							<input type='hidden' name='nombre_nivel' value='<?Php echo $row_nivel['Cat_Des']; ?>'/>
							<input type='hidden' name='cdc_nivel' value='<?Php echo $row_nivel['Cat_Cdc']; ?>'/>
							<input type='hidden' name='nivel_actual' value='<?Php echo $nivel_actual; ?>' />
							<input type='hidden' name='nivel_superior' value'<?Php echo $nivel_superior; ?>' />                            
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
                    <?Php
					if($row_nivel['Cat_Tip']=='G')
					{
						if($row_nivel['Cat_Est']!='I')
						{ ?>                        
							<form method='post' id='nav' name='nav' action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
								<input type='hidden' name='nivel_actual' value='<?Php echo $row_nivel['Cat_Cod']; ?>'/>
								<input type='hidden' name='nombre_nivel' value='<?Php echo $row_nivel['Cat_Des']; ?>'/>
								<input type='hidden' name='cdc_nivel' value='<?Php echo $row_nivel['Cat_Cdc'].'.0'; ?>'/>
								<input type='hidden' name='nivel_superior' value='<?Php echo $row_nivel['Cat_Rec']; ?>' />                                
								<button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>	</form>
                      <?Php
						}
						else 
						{
							echo "&nbsp;";
						}
						 							
					}
					else
					{
						echo "&nbsp;";
					} ?>
					</td>			
				</tr>
                <?Php
				}while($row_nivel = $obBD_con1->fetch_assoc($rs_nivel));
			}
		?>
        </tbody>
	</table>	
<br /> 	
	<!-- Boton para regresar a los niveles anteriores -->
	<?php
	echo barra_estado($total_nivel);
	
		if($nivel_actual != 0)
		{ ?>
			<form action='<?Php echo $_SERVER['PHP_SELF']; ?>' method='post'>
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
		* Revisar si hay algun registro anulada
		*/
		if ($anulada > 0)
		{		
			$com_leyenda[1]=$anulada;
		}		
		echo "<br />";
		
		require_once('../../componentes/FRONT/com_con_leyenda.php');

		if(isset($_POST['edit']))
		{ ?>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="form" id="form">
		<fieldset>
			<legend class="Titulos2">Registro a Editar</legend>
			<?php  
				/**
				* Creacion del campo REPOST
				*/
				$thisPost->startPost(); 
				echo mensaje_requerido(); 
			?>
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
				  <tr>
						<td width="14%" class="Etiqueta1">Categoria:</td>
						<td width="86%">&nbsp;<input type="text" name="Cat_Cdc" id="Cat_Cdc" value="<?php echo $codigo; ?>" readonly="readonly"/>						
                        </td>
					</tr>
					<tr>
						<td width="14%" class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
						<td width="86%">&nbsp;<input type="text" id="Cat_Des"
			name="Cat_Des" value="<?php echo $descripcion ?>" /></td>
					</tr>					
					<tr>
						<td width="14%" class="Etiqueta1">Tipo:</td>
						<td width="86%">&nbsp;
                        <?Php
						$row_dentro = $obBD_con1->getRowConsulta(1, $Cat_Cod, $obBD_conexion);

						if (count($row_dentro) == 0)
						{
						?>
						<select id="Cat_Tip" name="Cat_Tip">
								<?php 								
									if($tipo=='G')
									{
										echo "<option value='G' selected='selected'>Grupo</option>";
										echo "<option value='D'>Detalle</option>";	
									}
									else
									{
										echo "<option value='G'>Grupo</option>";
										echo "<option value='D' selected='selected'>Detalle</option>";
									}
								?>
							</select>	
                      <?Php      					
						}
						else
						{
							if ($tipo=='D'){
								echo "Detalle";
							}
							else
							{
								echo 'Grupo 
								<img src="../../mascaras/model1/imagenes/32x32/advertencia.png" width="20" height="20" /><font color="#FF0000"> No se cambiar el tipo porque posee sub-categorias</font>';
							}
						}
                      ?>                                  
                            </td>
					</tr>
				</table>
		</fieldset>
		<br />
		<input type="hidden" id="hdd" name="hdd" value="edit" />
		<input type="hidden" id="nombre_nivel" name="nombre_nivel" value="<?php echo $nombre_nivel ?>" />
		<input type="hidden" id="nivel_actual" name="nivel_actual" value="<?php echo $nivel_actual ?>" />
		<input type="hidden" id="cdc_nivel" name="cdc_nivel" value="<?php echo $cadena_codigo ?>" />
		<input type="hidden" id="nivel_superior" name="nivel_superior" value="<?php echo $nivel_superior ?>" />
		<input type="hidden" id="Cat_Cod" name="Cat_Cod" value="<?php echo htmlspecialchars($_POST['Cat_Cod'], ENT_QUOTES, 'UTF-8') ?>" />
        <button type="button" class="btn btn-primary start" title="Guardar" onClick="validar_requeridos(this.form,'Cat_Des',1)">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
    </button>         
</form>
	<?php 
		}
	?>
</div>
<script type="text/javascript" src="../VALIDACIONES/tes_par_categoria.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>    
</body>
<?php 
@$obBD_con1->free_result($rs_nivel_s);
@$obBD_con1->free_result($rs_nivel);
/**
* cierro las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/**
* fin cierre las conexiones 
*/
?>