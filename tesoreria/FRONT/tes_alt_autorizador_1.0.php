<?php 
	/* 
		Alias:	Registrar
		Descripcion: Permite el ingreso de los datos personales de los Receptores
		Desarrollador:	Mauricio Antonio Fierro Maldonado
		Fecha de Creacion:	2011-05-20
		Fecha de Ultima Modificacion: 2011-07-14
		/***          2                                
		Desarrollador:	Fabian Alberto Gallardo Gonzaga
		Fecha de Ultima Modificacion: 2011-12-20
	*/
	
	require_once '../../administrador/LOGICA/seguridad.php';
	require_once('../LOGICA/tes_log_autorizador.php');  	  
	require_once '../../Librerias/procedimientos/almacenados_standar.php';	
	require_once '../../Librerias/postclass.php';	  
	
	// Creacion del Objeto de conexion 
	$obBD_conexion = new Class_Log_Conexion_Tes;
	
	// Cracion del objeto mysql para las consultas 
	$obBD_con1 =  new Class_Log_Datos_Tes;
	
	// Inicializa el evento
	
	if(!isset($evento))
	{
		$evento = 0;
	} 
	
	// Llamado de la libreria para evitar el reenvio de datos 
	$thisPost = new Post_Block;
	
	// Control para volver atras donde no hay variable que pasar 
	if (isset($atras))
	{
		unset($hdd_comprobar);
	}

// Comprobacion de la existencia de persona - estudiante 
	if(isset($codigo))
	{
		$rs_persona = $obBD_con1->consulta(sentencias_tes(119, $obBD_con1->parametros(trim($codigo))), $obBD_conexion->conexion);
		$row_rs_persona = $obBD_con1->registros();
		$total_rs_persona = $obBD_con1->numregistros();	
		
		/* Cuando es igual a cero, equivale a ingresar los datos de persona - estudiante 
		por primera vez */
		
		// Consulta de la existencia de la persona en la tabla receptor
		$rs_comprobar = $obBD_con1->consulta(sentencias_tes(624, $obBD_con1->parametros($row_rs_persona['Prs_Ced'])), $obBD_conexion->conexion);
		$total_rs_comprobar = $obBD_con1->numregistros();
		
		// Consultar la existencia de la persona en el distributivo
		$rs_distributi = $obBD_con1->consulta(sentencias_tes(634, array($row_rs_persona['Prs_Ced'])), $obBD_conexion->conexion);
		$total_rs_distributi = $obBD_con1->numregistros();
		
		// Inicia el evento 
		$evento = 0;
		
		// Control para saber si se muestra o no el formulario
		if ($total_rs_comprobar > 0 )
		{
			$evento = 1;
		}	  
		else
		{
			// Registra recpetor 
			if ($total_rs_distributi > 0)
			{
				$evento = 2;
			}else{
				$evento = 3;	
			}
		}
		
	}
	
	/* Parametro enviado en la variable $evento para los siguientes casos 
		1 = insercion en persona - receptor y cedula 0
		2 = insercion en receptor */
	
		
	/* Evitar el reenvio de formularios */
	if ($thisPost->postBlock($_POST['postID']))
	{ 
	  if (isset($hdd_save) && !isset($hdd_volver))
	  {
			// Creacion del objeto mysql para las inserciones
			$obBD_ins1 = new Class_Log_Datos_Tes;
			
			// Inicio de la transaccion
			$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
						
			// Insercion del Receptor
			// TODO Cambiar el valor default 1 a el Sys_Sem_Cod
			$param = array(trim($Prs_Cod),$_SESSION['Ses_Emp_Cod']);
			//$param = array(trim($Prs_Cod),1);
			$obBD_ins1->grabarv_registros(sentencias_tes(625, $param), $obBD_conexion->conexion);	
	
			unset($hdd_comprobar);												
			$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
			$evento = 0;
	
	  }//Fin del if (isset($hdd_save))
	}//Fin del if ($thisPost->postBlock($_POST['postID']))
	
	// Consulta de las ciudades 
	$rs_ciudad = $obBD_con1->consulta(sentencias_tes(224,''), $obBD_conexion->conexion);
	$row_rs_ciudad = $obBD_con1->registros();
	$total_rs_ciudad = $obBD_con1->numregistros();
	?>
		
<HTML>
		<HEAD>
			<TITLE><?php echo $Ses_Sys_Nom; ?></TITLE>
			<?php require_once "../../mascaras/model1/estilos/estilos.php"; ?>	
			<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
			<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		</HEAD>
	<BODY>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	  <tr class="Titulos1">
		<td height="10" class="BarraTitulo">&raquo; registrar Autorizador</td>
	  </tr>
	  <tr>
		<td valign="top">
 
	<?php
	/* Entra cuando niega un evento */
	if (!($evento > 0)) 
	{ 
	?>
    <form method="post" name= "formbus" action="<?php echo $_SERVER['PHP_SELF'];?>">
      <?php require_once '../../componentes/FRONT/com_con_persona.php'; ?>
    </form>
     <?php
     if(isset($txt_busqueda)){?>
     <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Resultados de la busqueda</label>
        </LEGEND>
<?php
		
		$param = $txt_busqueda;
		
		if($op_opciones == 'd')// consulta los datos de receptor x apellido
			$rs_persona = $obBD_con1->consulta(sentencias_tes(220, $obBD_con1->parametros(trim($param))), $obBD_conexion->conexion);
		else //consulta los datos de receptor x cedula
			$rs_persona = $obBD_con1->consulta(sentencias_tes(222, $obBD_con1->parametros(trim($param))), $obBD_conexion->conexion);
		
		$row_rs_persona = $obBD_con1->registros();
		$total_rs_persona = $obBD_con1->numregistros();
			
		if($total_rs_persona == 0){
			// Valida si es cedula 
			if (strlen(trim($param)) == 10)
			{
				$param = $param."001"; 
			}/* Valida si es RUC */
			elseif((strlen(trim($param)) == 13))
			{
				$param = substr($param,0,10);
			}
			
			$rs_persona = $obBD_con1->consulta(sentencias_tes(222, $obBD_con1->parametros(trim($param))), $obBD_conexion->conexion);
			$row_rs_persona=$obBD_con1->registros();
			$total_rs_persona=$obBD_con1->numregistros();
			//$Prs_Cod = $row_rs_persona['Prs_Cod'];
		}
		
		// Si el total de custodios recibidos es mayor que 0 presentamos los datos
			if($total_rs_persona > 0)
			{
	?>
				<table border=1 width="100%" cellspacing="0" cellpadding="0">
					<tr class="Cabecera1"> 
						<td width="5%">Cod. Int.</td>
						<td width="20%">C&eacute;dula</td>
						<td width="70%">Nombres</td>
						<td width="5%">&nbsp;</td>
					</tr>
	<?php		
				do
				{
					if($row_rs_persona['Prs_Est']=='I')
					{
						$rojo = "#FF0000";
						$anulada++;
					}
					else
						$rojo = '';
	?>
					<tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo"); ?> class="Fondo">
						<td align="center"><FONT COLOR="<?php echo $rojo; ?>"> <?php echo marcar_cadena($_POST['txt_busqueda'], $row_rs_persona['Prs_Cod'], "#FFFF00", 1); ?> </FONT></td>
						<td ><FONT COLOR="<?php echo $rojo; ?>"> <?php echo marcar_cadena($_POST['txt_busqueda'], $row_rs_persona['Prs_Ced'], "#FFFF00", 1); ?> </FONT></td>
						<td ><FONT COLOR="<?php echo $rojo; ?>"> <?php echo marcar_cadena($_POST['txt_busqueda'], $row_rs_persona['Prs_Ape'] . " " . $row_rs_persona['Prs_Nom'], "#FFFF00", 1); ?> </FONT></td>
						<form id="blanco" name="blanco" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <td align="center">
                        <?php if($row_rs_persona['Prs_Est']=='A'){?>
								<input type="image" id="editar_img" name="editar_img"  src="../../mascaras/model1/imagenes/32x32/forward.png" width="18" height="18" title="Elegir" />
								<input type="hidden" id="Prs_Ced" name="Prs_Ced" value="<?php echo $row_rs_persona['Prs_Ced']; ?>" />
								<input type="hidden" id="codigo" name="codigo" value="<?php echo $row_rs_persona['Prs_Cod']; ?>"/>
                                <input type="hidden" name="volver_busqueda" id="volver_busqueda" value="<?Php echo $txt_busqueda;?>"/>
								<input type="hidden" name="volver_opciones" id="volver_opciones" value="<?php echo $op_opciones?>">
                        <?php } else{
							echo "&nbsp;";
						 }?>
						</td>
                        </form>
					</tr>
	<?php 
				}while($row_rs_persona = $obBD_con1->fetch_assoc($rs_persona));
	?>
				</table>
	<?php 
				echo barra_estado($total_rs_persona); 
				
				if($anulada > 0)
					$com_leyenda[1] = $anulada;
					
				require_once '../../componentes/FRONT/com_con_leyenda.php';
			}
			else
				echo error_alerta("No hay resultados que mostrar!", 1); ?>
		</FIELDSET>
		<?php }?>
        
	<br />
	
	<?php
	}//Fin del if ($evento > 0)
	
	/* Solo entra cuando hay que insertar en persona - estudiante � estudiante */
	if ($evento > 0 )
	{ 
	?>
	
	<table width="100%"  border="0" cellpadding="0" cellspacing="0">
	<tr>
	  <td>         <form method="post" name= "form1" id="form1" action="<?php echo $_SERVER['PHP_SELF'];?>">
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos a registrar</label>
	</LEGEND>
	
	<?php 
		echo mensaje_requerido(); //Muestra el mensaje de requerido  

		$thisPost->startPost(); // Creacion del campo repost 
	?>  

	<input name="evento" type="hidden" id="evento" value="<?php echo $evento; ?>">
	
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td class="Etiqueta1"><span class="Asterisco">*</span> C&eacute;dula/R.U.C.:&nbsp;</td>
		<td class="LetraNegra"><input name="Prs_Ced" type="text" id="Prs_Ced" onBlur="validarDocumento(this)" value="<?php echo $Prs_Ced ?>" size="13" maxlength="13"
		  <?php if ($evento != 0){ echo "readonly='true' style='border:none'"; } ?>>
			<?php
		if ($total_rs_persona >0)
		{ ?>
			<input name="Prs_Cod" type="hidden" id="Prs_Cod" value="<?php echo $row_rs_persona['Prs_Cod']; ?>">
			<?php
		} 	
		?>    </td>
	  </tr>
	  <tr>
		<td class="Etiqueta1"><span class="Asterisco">*</span> Nombres:&nbsp;</td>
		<td class="LetraNegra">
		<?php
		if ($total_rs_persona == 0)
		{ ?>	
		<input name="Prs_Nom" type="text" id="Prs_Nom" style="text-transform:uppercase" value="" size="50" maxlength="50">
		<?php
		} else { echo $row_rs_persona['Prs_Nom']; } ?>	</td>
		</tr>
	  <tr>
		<td class="Etiqueta1"><span class="Asterisco">* </span>Apellidos:&nbsp;</td>
		<td class="LetraNegra">
		<?php
		if ($total_rs_persona == 0)
		{ ?>		
		  <input name="Prs_Ape" type="text" id="Prs_Ape" style="text-transform:uppercase" value="" size="50" maxlength="50">
		<?php
		} else { echo $row_rs_persona['Prs_Ape']; } ?>	          </td>
		</tr>
	  <tr>
		<td class="Etiqueta1"><span class="Asterisco">*</span>G&eacute;nero:&nbsp;</td>
		<td class="LetraNegra">
		<?php
		if ($total_rs_persona == 0)
		{ ?>			
		<select name="Prs_Sex" id="Prs_Sex">
			<option value="">Seleccione..</option>
			<option value="M">Masculino</option>
			<option value="F">Femenino</option>		
		</select>	
		<?php
		} else { echo $row_rs_persona['Prs_Sex']; }
		?>	</td>
		</tr>
	<?php
	if ($total_rs_persona == 0)
	{ ?>  	
	  <tr>
		<td class="Etiqueta1">Tipo de Sangre:&nbsp; </td>
		<td class="LetraNegra"><input name="Prs_San" type="text" id="Prs_San" style="text-transform:uppercase" value="<?php echo $row_rs_persona['Prs_San']; ?>" size="4" maxlength="4"></td>
		</tr>
	<?php
	}//Fin del if ($total_rs_persona == 0)
	?>
	  <tr>
		<td class="Etiqueta1">  <span class="Asterisco">*</span> Fecha de nacimiento:&nbsp; </td>
		<td class="LetraNegra">
		<?php
		if ($total_rs_persona == 0)
		{ ?>				
		A&ntilde;o
			<select name="ann_ini" id="ann_ini" onChange="asignaDias(this.form.dia_ini, this.form.mes_ini, this.form.ann_ini)">
			  <option></option>
			  <?php   
				for ($i=date("Y")-70; $i<= date("Y")-2; $i++)
				{ ?>
			  <option value="<?php echo $i ?>"><?php echo $i ?> </option>
			  <?php
				}//Fin del for ($i=date("Y")-70; $i<= date("Y")-2; $i++)
			?>
			</select>
	mes
	<select name="mes_ini" id="mes_ini" onChange="asignaDias(this.form.dia_ini, this.form.mes_ini, this.form.ann_ini)">
	<option></option>
	<?php 
			/*Iniciacion del arreglo de meses*/
				$row_rs_des = array ("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", 
						"Octubre", "Noviembre", "Diciembre");
				for ($i=1; $i<=12;$i++)
				{ ?>
	<option value="<?php echo $i; ?>"> <?php echo $row_rs_des[$i-1] ?> </option>
	<?php
				}//Fin del for ($i=1; $i<=12;$i++) ?>
	</select>
	d&iacute;a <span class="Label1">
	<select name="dia_ini" id="dia_ini">
	<option></option>
	<?php
				for ($i=1; $i<=31;$i++)
				{
				?>
	<option value="<?php echo $i; ?>"><?php echo $i; ?> </option>
	<?php
				}//Fin del for ($i=1; $i<=31;$i++)
			   ?>
	</select>
	</span> 
		<?php
		} else { echo $row_rs_persona['Prs_Fec']; }
		?>		</td>
		</tr>
	  <tr></tr>
	  <tr>
		<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span> Estado Civil:&nbsp;</td>
		<td class="LetraNegra"><?php
		if ($total_rs_persona == 0)
		{ ?>
		  <select name="Prs_Esc" id="Prs_Esc">
			<?php 
				$row_rs_cod = array ("S","C","D","V","U");
				$row_rs_des = array ("Soltero/a", "Casado/a", "Divorciado/a", "Viudo/a", "Uni&oacute;n libre");
				for ($i=0;$i<count($row_rs_cod);$i++) 
				{ ?>
			<option value="<?php echo $row_rs_cod[$i]?>" <?php if ($row_rs_cod[$i]==$row_rs_persona['Prs_Esc']) { echo "selected"; } ?>><?php echo $row_rs_des[$i]?></option>
			<?php
				}//Fin del for ($i=0;$i<count($row_rs_cod);$i++) ?>
		  </select>
		<?php
		} else { echo $row_rs_persona['Prs_Esc']; }
		?>		
		</td>
	  </tr>
	  <tr>
		<td class="Etiqueta1"><span class="Asterisco">*</span> Direcci&oacute;n domiciliaria:&nbsp;</td>
		<td class="LetraNegra">
		<?php
		if ($total_rs_persona == 0)
		{ ?>			
		<input name="Prs_Dir" type="text" style="text-transform:uppercase" id="Prs_Dir" value="" size="50" maxlength="50" onKeyUp="document.getElementById('Pad_Dir').value = this.value; document.getElementById('Mad_Dir').value = this.value; document.getElementById('Rep_Dir').value = this.value">
		<?php
		} else { echo $row_rs_persona['Prs_Dir']; }
		?>	</td>
		</tr>
	  <tr>
		<td class="Etiqueta1"><span class="Asterisco">*</span> Ciudad domiciliaria:&nbsp;</td>
		<td class="LetraNegra">
		  <?php
		if ($total_rs_persona == 0)
		{ ?>
		  <select name="Ciu_Cod" id="Ciu_Cod">
			<option></option>
			<?php
				do {  
			?>
			<option value="<?php echo $row_rs_ciudad['Ciu_Cod']?>"><?php echo $row_rs_ciudad['Ciu_Des']?></option>
			<?php
				} while ($row_rs_ciudad = $obBD_con1->fetch_assoc($rs_ciudad));
		?>
			</select>
		  <?php
		} else { echo $row_rs_persona['Ciu_Des']; }
		?>		</td></tr>
	  <?php
	if ($total_rs_persona == 0)
	{ ?>  
	  <tr>
		<td class="Etiqueta1"> Tel&eacute;fono 1:&nbsp;</td>
		<td class="LetraNegra">
			  
		  <input name="Prs_Tel" type="text" id="Prs_Tel" onKeyPress="return validar_numeric(event)" onKeyUp="document.getElementById('Pad_Tel').value = this.value; document.getElementById('Mad_Tel').value = this.value; document.getElementById('Rep_Tel').value = this.value" value="" size="15" maxlength="15">
		  &nbsp;<span class="Etiqueta1">Tel&eacute;fono 2 :
			<input name="Prs_Te2" type="text" id="Prs_Te2" onKeyPress="return validar_numeric(event)" value="" size="15" maxlength="15">
			&nbsp;&nbsp;Celular:
			<input name="Prs_Cel" type="text" id="Prs_Cel" onKeyPress="return validar_numeric(event)" value="" size="15" maxlength="15">
			</span></td></tr>
	<?php
	}//Fin del if ($total_rs_persona == 0)
	?>  
	  <tr>
		<td class="Etiqueta1">Correo Electr&oacute;nico&nbsp;</td>
		<td class="LetraNegra"><?php
		if ($total_rs_persona == 0)
		{ ?>
		<input name="Prs_Cor" type="text" id="Prs_Cor" onBlur="correo(this)" value="" size="50" maxlength="50">
	<?php
		} else { echo $row_rs_persona['Prs_Cor']; }
		?>	</td>
		</tr>
		<!-- <td colspan="2">
		<br>
		</td>
		</tr> -->
	</table>
	</FIELDSET>
	<br>  
	
	<?php 
		if($evento == 3)
		{
			 echo error_alerta("La persona no posee contrato alguno en la instituci&oacute;n. No puede ser Receptor.",1);
		}
	?>
	<?php 
		if($evento == 1)
		{
			echo error_alerta("La persona ya esta ingresado como Receptor.",1);
		}
	?>
	</form> 
		  </td>
		</tr>      
	</table> 
     <form name="form3" id="form3" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">   
		<table border="0" cellpadding="0" cellspacing="0" class="Azul">
		  <tr>
          	<td>
            <input name="btn_atras" type="button" class="Boton_Atras"
			  onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver"; ?>','<?Php echo $volver_busqueda.'*'.$volver_opciones.'*'.'1'; ?>')"><?php if ($evento == 2) { ?><input type="button" class="Boton_Guardar" title="Guardar" onClick="submit()"	value="Guardar"><input name="hdd_save" type="hidden" id="hdd_save" value="insertar" /><input name="Prs_Cod" type="hidden" id="Prs_Cod" value="<?php echo $codigo;?>" /><?php }?></td>
          </tr>
          </table>
        </form>
	<?php
	}elseif($evento == 1){ //Fin del if ($evento > 0)
			echo error_alerta("La persona ya esta ingresado como Receptor.",1);
    }?> 
		</td>
	  </tr>
	</table>
</BODY>
	</HTML>
<?php	
	// Libero los recordsets
	@$obBD_con1->free_result ($rs_persona);
	@$obBD_con1->free_result ($rs_comprobar);
	@$obBD_con1->free_result($rs_receptor);
	@$obBD_con1->free_result($rs_ciudad);
	@$obBD_con1->free_result($rs_distributi);
	
	// Cierra las conexiones
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();	
?>