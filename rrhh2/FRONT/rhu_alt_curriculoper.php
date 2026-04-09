<?php 
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/logica.php');	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Rhu;
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Rhu;
/* Llamado de la libreria para evitar el reenvio de datos */
$thisPost = new Post_Block; 

/* Consulta de las nacionalidades */
$rs_pais=$obBD_con1->consulta(sentencias_rhu(643, ''), $obBD_conexion->conexion);
$row_rs_pais = $obBD_con1->registros();
$total_rs_pais = $obBD_con1->numregistros();  

$hoy = date("Y/m/d");
$hora = date("H:i:s");
//Control para ingresar al menu titulos sin dar clic en opcion op=1
if (!(isset($op)))
{
	$op=1;
}

if ($thisPost->postBlock($_POST['postID']))
{
if (isset($hdd_save))/*if para guerdar datos del curriculo*/
	{
	/* Cracion del objeto mysql para las inserciones */
		$obBD_ins1 =  new Class_Log_Datos_Rhu;
		/***********************************************/
		/****************Inicio de la transaccion***********************/
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		/***************************************************************/									
		if($Cur_Cod=="")
		{
			/* Consulta  de datos del personal */
			$rs_per = $obBD_con1->consulta(sentencias_rhu(97, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);
			$row_rs_per = $obBD_con1->registros();
			$per_cod=$row_rs_per['Per_Cod'];
		
			/*Inserción del curriculo*/
			$obBD_ins1->grabarv_registros(sentencias_rhu(616, $obBD_ins1->parametros($per_cod.'*'.$hoy.'*'.$hora)),$obBD_conexion->conexion);	
			$Cur_Cod = $obBD_ins1->insercionid($obBD_conexion->conexion);	
			
		}//Fin del if($Cur_Cod=="")
		
	    switch ($op)
		{
			case 1:
			if(isset($hdd_elim))
			{
				/* Elimina los titulos de los estudiantes */
				$obBD_ins1->grabarv_registros(sentencias_rhu(630, $obBD_ins1->parametros($Cur_Int.'*'.$Cur_Cod.'*'.$hdd_Nia_Cod.'*'.$Pas_Cod)),$obBD_conexion->conexion);
			}//Fin del if(isset($hdd_elim))
			
			if($hdd_actualizar==1)
			{
				if($Cur_Def=='D')
				{
					/* Selecciona el titulo activo de un curriculo */
					$rs_def=$obBD_con1->consulta(sentencias_rhu(641, $obBD_con1->parametros($Cur_Cod)), $obBD_conexion->conexion);
					$row_rs_def = $obBD_con1->registros();
					$total_rs_def = $obBD_con1->numregistros();  
					
					/* Si es mayor a cero significa que hay un titulo definido como PRINCIPAL */
					if( $total_rs_def > 0)
					{
						/* Actualiza el titulo Principal en NO PRINCIPAL */
						$obBD_ins1->grabarv_registros(sentencias_rhu(628, $obBD_ins1->parametros('N'.'*'.$row_rs_def['Cur_Int'].'*'.$Cur_Cod)),
											$obBD_conexion->conexion);
					}//Fin del if( $total_rs_def > 0)										
				}/*fin del if($Cur_Def=='D')*/
						/* Actualiza el titulo seleccionado */
						$obBD_ins1->grabarv_registros(sentencias_rhu(625, $obBD_ins1->parametros($Pas_Cod.'*'.$Nia_Cod.'*'.trim($Cur_Ins).'*'.trim($Cur_Tit)
											.'*'.$Cur_Ini.'*'.$Cur_Fin.'*'.trim($Cur_Obs).'*'.$Cur_Int.'*'.$Cur_Def.'*'.$Cur_Cod.'*'.trim($Cur_Reg))), $obBD_conexion->conexion);				
			} /*fin del if($hdd_actualizar==1) */
			else{
				if( !(isset($hdd_elim)) )
				{
					/* Consulta para obtner el maximo Cur_Int del curriculo */
					$rs_cons=$obBD_con1->consulta(sentencias_rhu( 638, $obBD_con1->parametros($Cur_Cod)), $obBD_conexion->conexion);
					$row_rs_cons = $obBD_con1->registros();
					$total_rs_cons = $obBD_con1->numregistros(); 
						
					if($total_rs_cons==0)
					{
						$Cur_Int= 1;
					}// fin del if($total_rs_cons==0)
					else{
						$Cur_Int= $row_rs_cons['maximo']+1;
					}// fin del else
					
					if($Cur_Def=='D')
					{
						/* Selecciona el titulo activo de un curriculo */						
						$rs_def=$obBD_con1->consulta(sentencias_rhu(641, $obBD_con1->parametros($Cur_Cod)), $obBD_conexion->conexion);
						$row_rs_def = $obBD_con1->registros();
						$total_rs_def = $obBD_con1->numregistros();  
						
						/* Si es mayor a cero significa que hay un titulo definido como PRINCIPAL */
						if( $total_rs_def > 0)
						{
							/* Actualiza el titulo Principal en NO PRINCIPAL */
							$obBD_ins1->grabarv_registros(sentencias_rhu(628, $obBD_ins1->parametros('N'.'*'.$row_rs_def['Cur_Int'].'*'.$Cur_Cod)),
												$obBD_conexion->conexion);
						}//Fin del if( $total_rs_def > 0)	
					}/*if($Cur_Def=='D')*/
					/* Inserta un nuevo titulo */					
					$obBD_ins1->grabarv_registros(sentencias_rhu(617, $obBD_ins1->parametros( $Pas_Cod.'*'.$Cur_Cod.'*'.$Nia_Cod.'*'.trim($Cur_Ins).'*'.
									trim($Cur_Tit).'*'.$Cur_Ini.'*'.$Cur_Fin.'*'.trim($Cur_Obs).'*'.$Cur_Int.'*'.$Cur_Def.'*'.trim($Cur_Reg))),$obBD_conexion->conexion);												
				}// fin del if( !(isset($hdd_elim)) )
			}// fin del else
			break;
			case 2:
			if(isset($hdd_elim))
			{
				/* Elimina la experiencia laboral */
				$obBD_ins1->grabarv_registros(sentencias_rhu(631, $obBD_ins1->parametros($Cur_Int.'*'.$Cur_Cod.'*'.$Pas_Cod)),$obBD_conexion->conexion);
			}// fin del if(isset($hdd_elim))
			if($hdd_actualizar==1)
			{
				/* Actualiza la experiencia laboral */
				$obBD_ins1->grabarv_registros(sentencias_rhu(626, $obBD_ins1->parametros($Pas_Cod.'*'.trim($Cur_Ins).'*'.trim($Cur_Car).'*'.$Cur_Ini.'*'.
								$Cur_Fin.'*'.trim($Cur_Obs).'*'.$Cur_Int.'*'.$Cur_Cod)),$obBD_conexion->conexion);
			}// fin del if($hdd_actualizar==1)
			else
			{
				if( ($hdd_actualizar!=1) && !(isset($hdd_elim)))
				{
					/* consulta el maximo Cur_Int de la experiencia laboral */
					$rs_cons=$obBD_con1->consulta(sentencias_rhu(639, $obBD_con1->parametros($Cur_Cod)), $obBD_conexion->conexion);
					$row_rs_cons = $obBD_con1->registros();
					$total_rs_cons = $obBD_con1->numregistros(); 	
					
					if($total_rs_cons==0)
					{
						$Cur_Int= 1;
					}//Fin del if($total_rs_cons==0)
					else
					{
						$Cur_Int= $row_rs_cons['maximo'] + 1;
					}//Fin del else if($total_rs_cons==0)
					/* Inserta una nueva experiencia laboral */
					$obBD_ins1->grabarv_registros(sentencias_rhu(623, $obBD_ins1->parametros($Pas_Cod.'*'.$Cur_Cod.'*'.trim($Cur_Car).'*'.trim($Cur_Ins).'*'.
										$Cur_Ini.'*'.$Cur_Fin.'*'.trim($Cur_Obs).'*'.$Cur_Int)),$obBD_conexion->conexion);				
				}// fin del if( ($hdd_actualizar!=1) && !(isset($hdd_elim)))
			}//Fin del else if($hdd_actualizar==1)
			break;
			case 3:
			if(isset($hdd_elim))
			{
				/* Elimina las capacitaciones */
				$obBD_ins1->grabarv_registros(sentencias_rhu(632, $obBD_ins1->parametros($Cur_Int.'*'.$Cur_Cod.'*'.$hdd_Tca_Cod.'*'.$Pas_Cod)),
										$obBD_conexion->conexion);
			}
			if($hdd_actualizar==1)
			{
				$obBD_ins1->grabarv_registros(sentencias_rhu(627, $obBD_ins1->parametros($Pas_Cod.'*'.$Tca_Cod.'*'.trim($Cur_Ins).'*'.trim($Cur_Tit).'*'.
										$Cur_Ini.'*'.$Cur_Fin.'*'.trim($Cur_Obs).'*'.$Cur_Int.'*'.$Cur_Cod)),$obBD_conexion->conexion);
			}//Fin del if($hdd_actualizar==1)
			else
			{
				if( ($hdd_actualizar!=1) &&!(isset($hdd_elim)))
				{				
					$rs_cons=$obBD_con1->consulta(sentencias_rhu( 640, $obBD_con1->parametros($Cur_Cod)), $obBD_conexion->conexion);
					$row_rs_cons = $obBD_con1->registros();
					$total_rs_cons = $obBD_con1->numregistros(); 	
				
					if($total_rs_cons==0)
					{
						$Cur_Int= 1;
					}//Fin del if($total_rs_cons==0)
					else
					{
						$Cur_Int= $row_rs_cons['maximo']+1;
					}//Fin del else if($total_rs_cons==0)
					/* Inserta nuevas cursos de capacitacion */
						$obBD_ins1->grabarv_registros(sentencias_rhu(624, $obBD_ins1->parametros($Pas_Cod.'*'.$Cur_Cod.'*'.$Tca_Cod.'*'.trim($Cur_Ins).'*'.
											trim($Cur_Tit).'*'.$Cur_Ini.'*'.$Cur_Fin.'*'.trim($Cur_Obs).'*'.$Cur_Int)),$obBD_conexion->conexion);				
				}//Fin del if( ($hdd_actualizar!=1) &&!(isset($hdd_elim)))
			}
			break;
		}/*fin del case*/ 	 
	$obBD_ins1->fin_transaccion($obBD_conexion->conexion);// fin de la transaccion	
}// fin del if if (isset($hdd_save))
}//fin del if ($thisPost->postBlock($_POST['postID']))

if ($txt_busqueda != "")
{   
	if ($op_opciones == "d")
	{
		$rs_buscar = $obBD_con1->consulta(sentencias_rhu(96, $obBD_con1->parametros(trim($txt_busqueda))), $obBD_conexion->conexion);		
	}//Fin del if ($op_opciones == "d")
    else 
    {
	   $rs_buscar = $obBD_con1->consulta(sentencias_rhu(93, $obBD_con1->parametros(trim($txt_busqueda))), $obBD_conexion->conexion);				
	}//Fin del else if ($op_opciones == "d")	 		  
	   $row_rs_buscar = $obBD_con1->registros();
	   $total_rs_buscar = $obBD_con1->numregistros();
}//Fin del if ($txt_busqueda != "" ) 
else
{
	if (isset($codigo))
	{
		/* Consulta los datos personales del personal */
		$rs_consulta = $obBD_con1->consulta(sentencias_rhu(642, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);		
		$row_rs_consulta = $obBD_con1->registros();
		$total_rs_consulta = $obBD_con1->numregistros();
		/* Consulta la cabecera del curriculo */
		$rs_curri=$obBD_con1->consulta(sentencias_rhu(618, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);
		$row_rs_curri = $obBD_con1->registros();
		$total_rs_curri = $obBD_con1->numregistros(); 
		$Cur_Cod=$row_rs_curri['Cur_Cod'];		
	}//Fin del if (isset($codigo))
}//Fin del else if ($txt_busqueda != "")
?>	
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">	
		<link href="../../mascaras/model1/estilos/estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/interfaz.css" rel="stylesheet" type="text/css">						
		<link rel="stylesheet" type="text/css" media="all" href="../../Librerias/jscalendar/calendar-win2k-cold-1.css" title="win2k-cold-1" />					
		<link href="../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../../Librerias/jscalendar/calendar.js"></script>
		<script type="text/javascript" src="../../Librerias/jscalendar/lang/calendar-es.js"></script>
		<script type="text/javascript" src="../../Librerias/jscalendar/calendar-setup.js"></script>	
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">		
	</HEAD>
<BODY>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
	<tr class="Titulos1">
	  <td class="BarraTitulo">&raquo;Registro del curriculo vitae del personal </td>
	</tr>
	<tr>
	<td>
<form action="<?Php echo $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
        <?Php include("../../componentes/FRONT/com_con_persona.php"); ?>
</form>
<?php 
if(isset($hdd_buscar))
{ ?>
	<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Resultados de la búsqueda</label>
		</LEGEND>
	<table width="100%" border="1" cellpadding="0" cellspacing="0">
	  <tr class="Cabecera1">
		<td width="9%">C&oacute;d. Int. </td>
		<td width="10%">C&eacute;dula</td>
		<td width="38%">Apellidos</td>
		<td width="41%">Nombres</td>
		<td width="2%">&nbsp;</td>
	  </tr>
	  <?Php 
	 if($total_rs_buscar > 0)
	 {	  
	  do { //Abrir el } while ($row_rs_buscar = mysqli_fetch_assoc($rs_buscar) 
	  
		 echo '<form name=form6 method=post  action='.$_SERVER['PHP_SELF'].'>';
		 ?>	
	  <tr class="Fondo">
		<td align="center"><?Php echo $row_rs_buscar['Prs_Cod']; ?></td>
		<td align="center"><?Php echo $row_rs_buscar['Prs_Ced']; ?></td>
		<td align="center"><?Php echo $row_rs_buscar['Prs_Ape']; ?></td>
		<td align="center"><?Php echo $row_rs_buscar['Prs_Nom']; ?></td>
		<td align="center" width="2%"><?Php echo $row_rs_buscar['Prs_Est'] ?>
			<input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Cod'];?>">
			<input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
			<input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">
			<input type="image" name="imageField" src="../../mascaras/model1/imagenes/forward.png" width="22" height="22">
			<input name="hdd_distri" id="hdd_distri" type="hidden" value="1">
			<input name="hdd_ape" id="hdd_ape" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Ape']; ?>">
			<input name="hdd_nom" id="hdd_nom" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Nom']; ?>">
			<input name="hdd_prs" id="hdd_prs" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Cod']; ?>">
			<?Php
		?>
		</td>
	  </tr><?Php echo "</form>";  
		} while ($row_rs_buscar = mysqli_fetch_assoc ($rs_buscar)); 
	}//Fin del if($total_rs_buscar != 0)
	else
	{ ?>
	  <tr>
		<td colspan="5" align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
	  </tr>
	  <?Php	 }//Fin del else if($total_rs_buscar != 0) ?>
	</table>
	</FIELDSET>
<?Php  
} /*fin del if(isset($hdd_buscar) )*/ ?> 


<?php 
if(isset($codigo) && isset($op))
{ ?>
		<?Php 
			$descripcion = "Acad&eacute;mico*Laboral*Capacitaci&oacute;n";
			$pag1= $_SERVER['PHP_SELF']."?op=1&codigo=".$codigo;
			$pag2= $_SERVER['PHP_SELF']."?op=2&codigo=".$codigo;
			$pag3= $_SERVER['PHP_SELF']."?op=3&codigo=".$codigo;
			tabs(3,$descripcion, $pag1.'*'.$pag2.'*'.$pag3, $op);
	?>

	<div id="ContTabul">
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos del empleado </label>
	</LEGEND>	
	<table width="59%"  border="0">
		  <tr>
			<td width="13%" class="Etiqueta1">C&eacute;dula:</td>
			<td colspan="3" class="LetraNegra"><span class="Texto_Reporte"><?Php echo $row_rs_consulta['Prs_Ced']; ?></span></td>
			<?php $Cedula= $Usu_Ced;?>
		</tr>
		  <tr>
			<td class="Etiqueta1">Apellidos:</td>
			<td width="32%" class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Ape']; ?></td>
		    <td width="9%" class="LetraNegra"><span class="Etiqueta1">Nombres:</span></td>
		    <td width="46%" class="LetraNegra"><span class="Texto_Reporte"><?Php echo $row_rs_consulta['Prs_Nom']; /*echo $Ses_Prs_Cod;*/?></span></td>
		  </tr>
	  </table>
	  </FIELDSET>
	<?Php switch ($op)
	{
	case 1:
	/* Datos de academicos */
	$rs_curriculo=$obBD_con1->consulta(sentencias_rhu(619, $obBD_con1->parametros($Cur_Cod)), $obBD_conexion->conexion);
	$row_rs_curriculo = $obBD_con1->registros();
	$total_rs_curriculo = $obBD_con1->numregistros();  
	?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos Acad&eacute;micos </label>
	</LEGEND>		
	<table width="100%" border="1" cellpadding="0" cellspacing="0">
    <tr class="Cabecera1">
    <td width="20%">Nivel</td>
    <td width="25%">T&iacute;tulo</td>
    <td width="25%">Instituci&oacute;n</td>
    <td width="10%">Pa&iacute;s</td>
    <td width="10%">T&iacute;tulo principal </td>
    <td width="4%">&nbsp; </td>
    <td width="4%">&nbsp;</td>
  </tr>
  <?php
  if($total_rs_curriculo>0)
  {
  $i=0;
  $j=0;
  do{
  $i++;
  $j++;
  ?>
  <tr class="Fondo">
    <td <?Php if ($row_rs_curriculo['Cur_Def']=='D'){ echo 'bgcolor="#66FF33"'; } ?>> 
      <?php echo $row_rs_curriculo['Nia_Des'];?></td>
    <td <?Php if ($row_rs_curriculo['Cur_Def']=='D'){ echo 'bgcolor="#66FF33"'; } ?>>
      <?php echo $row_rs_curriculo['Cur_Tit'];?>    </td>
    <td <?Php if ($row_rs_curriculo['Cur_Def']=='D'){ echo 'bgcolor="#66FF33"'; } ?>>
      <?php echo $row_rs_curriculo['Cur_Ins'];?>    </td>
    <td <?Php if ($row_rs_curriculo['Cur_Def']=='D'){ echo 'bgcolor="#66FF33"'; } ?>>
      <?php echo $row_rs_curriculo['Pas_Nom'];?>    </td>
	
	
    <td align="center" <?Php if ($row_rs_curriculo['Cur_Def']=='D'){ echo 'bgcolor="#66FF33"'; } ?>><?php if ($row_rs_curriculo['Cur_Def']=='D'){ $cur="Principal";} 
	else{  $cur="&nbsp;"; } echo $cur;?></td>
	    
	 <form method="post" name="form4" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
	<td align="center" <?Php if ($row_rs_curriculo['Cur_Def']=='D'){ echo 'bgcolor="#66FF33"'; } ?>>
	<input type="image" name="imageField" src="../../mascaras/model1/imagenes/editar.png" border="0" title="Editar" width="22" height="22">
	<input name="hdd_agregar" type="hidden" value="1" id="hdd_agregar">	<input name="hdd_modificar" type="hidden" value="1" id="hdd_modificar">
	<input name="Cur_Cod" type="hidden" value="<?Php echo $row_rs_curriculo['Cur_Cod'];?>" id="Cur_Cod">
	<input name="Cur_Int" type="hidden" value="<?Php echo $row_rs_curriculo['Cur_Int'];?>" id="Cur_Int">
	<input name="hdd_nia" type="hidden" value="<?Php echo $row_rs_curriculo['Nia_Cod'];?>" id="hdd_nia">
	<input name="hdd_pai" type="hidden" value="<?Php echo $row_rs_curriculo['Pas_Cod'];?>" id="hdd_pai">
	<input name="hdd_tit" type="hidden" value="<?Php echo $row_rs_curriculo['Cur_Tit'];?>" id="hdd_tit">
	<input name="hdd_reg" type="hidden" value="<?Php echo $row_rs_curriculo['Cur_Reg'];?>" id="hdd_reg">	
	<input name="hdd_ins" type="hidden" value="<?Php echo $row_rs_curriculo['Cur_Ins'];?>" id="hdd_ins">
	<input name="hdd_ini" type="hidden" value="<?Php echo $row_rs_curriculo['Cur_Ini'];?>" id="hdd_ini">
	<input name="hdd_fin" type="hidden" value="<?Php echo $row_rs_curriculo['Cur_Fin'];?>" id="hdd_fin">
	<input name="hdd_obs" type="hidden" value="<?Php echo $row_rs_curriculo['Cur_Obs'];?>" id="hdd_obs">
	<input name="hdd_def" type="hidden" value="<?Php echo $row_rs_curriculo['Cur_Def'];?>" id="hdd_def">
	<input type="hidden" name="op" id="op" value="1">
	<input name="hdd_actualizar" type="hidden" value="1" id="hdd_actualizar">
	<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">	
	</td>
	</form>
	<form method="post" name='form5[<?Php echo $f; ?>]' id="form5[<?Php echo $f; ?>]" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
	<td align="center" 
	<?Php if ($row_rs_curriculo['Cur_Def']=='D'){ echo 'bgcolor="#66FF33"'; } ?>>	
	<img src="../../mascaras/model1/imagenes/eliminar.jpg" width="22" height="22" border="0" title="Eliminar" style="cursor:pointer" onClick="confirmacion2(document.getElementById('form5[<?Php echo $f; ?>]'))">
	<input name="Cur_Cod" type="hidden" value="<?Php echo $row_rs_curriculo['Cur_Cod'];?>" id="Cur_Cod">	
	<input type="hidden" name="Cur_Int" id="Cur_Int" value="<?Php echo $row_rs_curriculo['Cur_Int']; ?>">
	<input name="hdd_Nia_Cod" type="hidden" value="<?Php echo $row_rs_curriculo['Nia_Cod'];?>" id="hdd_Nia_Cod">
	<input name="Pas_Cod" type="hidden" value="<?Php  echo $row_rs_curriculo['Pas_Cod']; ?>" id="Pas_Cod">
	<input type="hidden" name="hdd_elim" id="hdd_elim" value="1">
	<input type="hidden" name="op" id="op" value="<?Php echo $op; ?>">
    <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">	
	<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">		  
		</td>	
	</form>
  </tr>
  <?php }while($row_rs_curriculo = $obBD_con1->fetch_assoc ($rs_curriculo)); ?>
  <?php 
  }/*fin del if(total_rs_curriuclo==0)*/
  else{?>
  		<tr>
		<td colspan="9"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
		</tr>
		<?php }?>
</table>
</FIELDSET>
	<br>
<table width="115"  border="0">
  <tr>
     <form method='post' name='form5' action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
      <td width="197"><input name="agregar" type="submit" class="Boton_Agregar" title="Agregar item" value="Rubros">
          <input type="hidden" name="hdd_agregar" id="hdd_agregar">
          <input type="hidden" name="op" id="op" value="1">
          <input type="hidden" name="hdd_agrega" id="hdd_agrega" value="1">
		<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">		  
		  </td>
    </form>
  </tr>
</table>
<br>
<fieldset>
          <legend>
          <label class="Titulos2">Leyenda:</label>
          </legend>
          <table width="161" border="1" cellpadding="0" cellspacing="0">
            <tr>
              <td width="101" class="Cuerpo_ajax"><div align="right" class="inactivo">
                  <div align="left"><strong>T&iacute;tulo   principal = </strong></div>
              </div></td>
              <td width="54" bgcolor="#66FF33">&nbsp;</td>
            </tr>
      </table>
</fieldset>
<?php 
break;// fin del case 1
case 2:
/* Consulta de la informacion laboral */
$rs_laboral=$obBD_con1->consulta(sentencias_rhu(620, $obBD_con1->parametros($Cur_Cod)), $obBD_conexion->conexion);
$row_rs_laboral = $obBD_con1->registros();
$total_rs_laboral = $obBD_con1->numregistros();  
?>
<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Experiencia Laboral  </label>
	</LEGEND>		
	<table width="100%" border="1" cellpadding="0" cellspacing="0">
  <tr class="Cabecera1">
    <td width="30%">Cargo</td>
    <td width="30%">Institución</td>
    <td width="10%">A&ntilde;o</td>
    <td width="10%">País</td>
    <td width="4%">&nbsp;</td>
    <td width="4%">&nbsp;</td>
  </tr>
  <?php
  if($total_rs_laboral>0){
  $i=0;
  $j=0;
  do{
  $i++;
  $j++;
  ?>
  <tr class="Fondo">
    <td>
      <?php echo $row_rs_laboral['Cur_Car'];?>    </td>
    <td>
      <?php echo $row_rs_laboral['Cur_Ins'];?>    </td>
    <td align="center">
      <?php if ($row_rs_laboral['Ann_Ini'] != 0){ echo $row_rs_laboral['Ann_Ini']; }else{ echo "&nbsp;";} ?> </td> 
    <td>
      <?php echo $row_rs_laboral['Pas_Nom'];?>    </td>
	 <form method='post' name='form4' action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
	<td align="center"><input type="image" name="imageField5" src="../../mascaras/model1/imagenes/editar.png" border="0" title="Editar" width="22" height="22">
	  <input name="hdd_agregar" type="hidden" value="1" id="hdd_agregar">	<input name="hdd_modificar" type="hidden" value="1" id="hdd_modificar">
	<input name="Cur_Cod" type="hidden" value="<?Php echo $row_rs_laboral['Cur_Cod'];?>" id="Cur_Cod">
	<input name="Cur_Int" type="hidden" value="<?Php echo $row_rs_laboral['Cur_Int'];?>" id="Cur_Int">
	<input name="hdd_pai" type="hidden" value="<?Php echo $row_rs_laboral['Pas_Cod'];?>" id="hdd_pai">
	<input name="hdd_car" type="hidden" value="<?Php echo $row_rs_laboral['Cur_Car'];?>" id="hdd_car">
	<input name="hdd_ins" type="hidden" value="<?Php echo $row_rs_laboral['Cur_Ins'];?>" id="hdd_ins">
	<input name="hdd_ini" type="hidden" value="<?Php echo $row_rs_laboral['Cur_Ini'];?>" id="hdd_ini">
	<input name="hdd_fin" type="hidden" value="<?Php echo $row_rs_laboral['Cur_Fin'];?>" id="hdd_fin">
	<input name="hdd_obs" type="hidden" value="<?Php echo $row_rs_laboral['Cur_Obs'];?>" id="hdd_obs">
	<input type="hidden" name="op" id="op" value="2">
	<input name="hdd_actualizar" type="hidden" value="1" id="hdd_actualizar">
	<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">		  
	</td>
	</form>
	 <form method='post' name='form5[<?Php echo $f; ?>]' id="form5[<?Php echo $f; ?>]" action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
	   <td align="center"><img src="../../mascaras/model1/imagenes/eliminar.jpg" width="22" height="22" border="0" title="Eliminar" style="cursor:pointer" onClick="confirmacion2(document.getElementById('form5[<?Php echo $f; ?>]'))">
	     <input name="Cur_Cod" type="hidden" value="<?Php echo $row_rs_laboral['Cur_Cod'];?>" id="Cur_Cod">
	     <input name="Cur_Int" type="hidden" value="<?Php echo $row_rs_laboral['Cur_Int'];?>" id="Cur_Int">
	     <input name="Pas_Cod" type="hidden" value="<?Php echo $row_rs_laboral['Pas_Cod'];?>" id="Pas_Cod">
	     <input type="hidden" name="op" id="op" value="<?Php echo $op; ?>">
	     <input type="hidden" name="hdd_elim" id="hdd_elim" value="1">
	     <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
		 <input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">		  
		 </td></form>
  </tr>
  <?php }while($row_rs_laboral = $obBD_con1->fetch_assoc ($rs_laboral)); ?>
  <?php 
  }/*fin del if  if($total_rs_laboral>0)*/
  else{?>
  		<tr>
		<td colspan="9"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
		</tr>
	<?php }// fin del else?>
</table>
		
    </FIELDSET>
<br>
<table width="115"  border="0">
  <tr>
     <form method='post' name='form5' action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
     <td width="197"><input name="agregar" type="submit" class="Boton_Agregar" title="Agregar item" value="">
     <input type="hidden" name="hdd_agregar" id="hdd_agregar">
     <input type="hidden" name="op" id="op" value="2">
	<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">	 
	 </td>
    </form>
  </tr>
</table>
<?php 
/***********/	
 break;// fin del case 2
 case 3:
/* Consulta los datos las capacitaciones */
$rs_capacitacion=$obBD_con1->consulta(sentencias_rhu(621, $obBD_con1->parametros($Cur_Cod)), $obBD_conexion->conexion);
$row_rs_capacitacion = $obBD_con1->registros();
$total_rs_capacitacion = $obBD_con1->numregistros();  	
?>
 <FIELDSET>
	<LEGEND>
	<label class="Titulos2">Capacitaciones</label>
	</LEGEND>		
	<table width="100%" border="1" cellpadding="0" cellspacing="0">
  <tr class="Cabecera1">
    <td width="10%">Tipo</td>
    <td width="30%">Institución</td>
    <td width="20%">Título</td>
    <td width="10%">A&ntilde;o</td>
    <td width="10%">País</td>
    <td width="4%">&nbsp; </td>
    <td width="4%">&nbsp;</td>
  </tr>
  <?php
  if($total_rs_capacitacion>0){
  $i=0;
  $j=0;
  do{
  $i++;
  $j++;
  ?>
  <tr class="Fondo">
    <td>
      <?php echo $row_rs_capacitacion['Tca_Des'];?>    </td>
    <td>
      <?php echo $row_rs_capacitacion['Cur_Ins'];?>    </td>
    <td>
      <?php echo $row_rs_capacitacion['Cur_Tit'];?>    </td>
    <td align="center"><?php if ($row_rs_capacitacion['Ann_Ini'] != 0){ echo $row_rs_capacitacion['Ann_Ini']; }else{ echo "&nbsp;";} ?></td>
    <td>
      <?php echo $row_rs_capacitacion['Pas_Nom'];?>    </td>
	 <form method='post' name='form4' action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
	<td align="center"><input type="image" name="imageField52" src="../../mascaras/model1/imagenes/editar.png" border="0" title="Editar" width="22" height="22">
	  <input name="hdd_agregar" type="hidden" value="1" id="hdd_agregar">	<input name="hdd_modificar" type="hidden" value="1" id="hdd_modificar">
	<input name="Cur_Cod" type="hidden" value="<?Php echo $row_rs_capacitacion['Cur_Cod'];?>" id="Cur_Cod">
	<input name="hdd_tip" type="hidden" value="<?Php echo $row_rs_capacitacion['Tca_Cod'];?>" id="hdd_tip">
	<input name="Cur_Int" type="hidden" value="<?Php echo $row_rs_capacitacion['Cur_Int'];?>" id="Cur_Int">
	<input name="hdd_pai" type="hidden" value="<?Php echo $row_rs_capacitacion['Pas_Cod'];?>" id="hdd_pai">
	<input name="hdd_tit" type="hidden" value="<?Php echo $row_rs_capacitacion['Cur_Tit'];?>" id="hdd_tit">
	<input name="hdd_ins" type="hidden" value="<?Php echo $row_rs_capacitacion['Cur_Ins'];?>" id="hdd_ins">
	<input name="hdd_ini" type="hidden" value="<?Php echo $row_rs_capacitacion['Cur_Ini'];?>" id="hdd_ini">
	<input name="hdd_fin" type="hidden" value="<?Php echo $row_rs_capacitacion['Cur_Fin'];?>" id="hdd_fin">
	<input name="hdd_obs" type="hidden" value="<?Php echo $row_rs_capacitacion['Cur_Obs'];?>" id="hdd_obs">	
	<input name="hdd_actualizar" type="hidden" value="1" id="hdd_actualizar">
	<input type="hidden" name="op" id="op" value="3">	
	<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">		  
	</td>
	</form>
	<form method='post' name='form5[<?Php echo $f; ?>]' id="form5[<?Php echo $f; ?>]" action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
	  <td align="center"><img src="../../mascaras/model1/imagenes/eliminar.jpg" width="22" height="22" border="0" title="Eliminar" style="cursor:pointer" onClick="confirmacion2(document.getElementById('form5[<?Php echo $f; ?>]'))">
	    <input name="Cur_Cod" type="hidden" value="<?Php echo $row_rs_capacitacion['Cur_Cod'];?>" id="Cur_Cod">
	    <input name="Cur_Int" type="hidden" value="<?Php echo $row_rs_capacitacion['Cur_Int'];?>" id="Cur_Int">
	    <input name="hdd_Tca_Cod" type="hidden" value="<?Php echo $row_rs_capacitacion['Tca_Cod'];?>" id="hdd_Tca_Cod">
	    <input name="Pas_Cod" type="hidden" value="<?Php echo $row_rs_capacitacion['Pas_Cod'];?>" id="Pas_Cod">
	    <input type="hidden" name="hdd_elim" id="hdd_elim" value="1">
	    <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
	    <input type="hidden" name="op" id="op" value="<?Php echo $op; ?>">
		<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">		  
		</td></form>
  </tr>
  <?php }while($row_rs_capacitacion = $obBD_con1->fetch_assoc($rs_capacitacion)); ?>
  <?php 
  }/*fin del  if($total_rs_capacitacion>0)*/
  else{?>
  		<tr>
		<td colspan="10"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
		</tr>
	<?php }// fin del else?>
</table>
		
    </FIELDSET>
	
 <br>
 <table width="115"  border="0">
   <tr>
      <form method='post' name='form5' action='<?Php echo $_SERVER['PHP_SELF']; ?>'>
       <td width="197"><input name="agregar" type="submit" class="Boton_Agregar" title="Agregar item" value="">
           <input type="hidden" name="hdd_agregar" id="hdd_agregar">
           <input type="hidden" name="op" id="op" value="3">
		   <input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">		  
		   </td>
     </form>
   </tr>
 </table>
 <?php 
 /*******/ 
 break;// fin del case 3
}/*fin de case*/ ?>
 <br>
 <font class="Alertas3"> <strong>NOTA: La informaci&oacute;n ingresada deber&aacute; ser respalda con documentos f&iacute;sicos debidamente certificados.</strong></font> 
 <form method="post" name="form2" action="<?Php $_SERVER['PHP_SELF'] ?>">
  <?php if(isset($hdd_agregar))
	{
		/* Campo din&aacute;mico para evitar el reenvio del formulario */
		$thisPost->startPost();
	switch ($op){
		case 1:
			/* Consulta para cargar los niveles academicos en el combo box */
			$rs_niveles = $obBD_con1->consulta(sentencias_rhu(615, ''), $obBD_conexion->conexion);
			$row_rs_niveles = $obBD_con1->registros();
			$total_rs_niveles = $obBD_con1->numregistros();  		
		?>
     <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Datos a registrar</label>             
            </LEGEND>
			 <?Php
			 echo mensaje_requerido();
			 ?>
			 <input type="hidden" name="Cur_Cod" id="Cur_Cod" value="<?Php echo $Cur_Cod; ?>">
			 <table width="80%" border="0">
               <tr>
                 <td width="23%" class="Etiqueta1"><span class="Asterisco">*</span> Nivel acad&eacute;mico: </td>
                 <td class="LetraNegra">
				 <select name="Nia_Cod" id="Nia_Cod">
                     <option></option>
                     <?php
				do {  
			?>
                     <option <?php if($row_rs_niveles['Nia_Cod'] == $hdd_nia)
			 { echo "selected";}?> value="<?php echo $row_rs_niveles['Nia_Cod']?>"><?php echo $row_rs_niveles['Nia_Des']?></option>
                     <?php
				} while ($row_rs_niveles = $obBD_con1->fetch_assoc($rs_niveles));
		?>
                 </select></td>
               </tr>
               <tr>
                 <td class="Etiqueta1"><span class="Asterisco">*</span> T&iacute;tulo:</td>
                 <td><span class="LetraNegra">
                   <textarea name="Cur_Tit" cols="75" rows="3" id="Cur_Tit" style="text-transform:uppercase"><?php echo $hdd_tit;?></textarea>
                 </span></td>
               </tr>
               <tr>
                 <td class="Etiqueta1">No. de registro: </td>
                 <td><span class="LetraNegra">
                   <input name="Cur_Reg" type="text" id="Cur_Reg" style="text-transform:uppercase" value="<?php echo $hdd_reg;?>" size="20" maxlength="20">
                 </span></td>
               </tr>
               <tr>
                 <td class="Etiqueta1"><span class="Asterisco">*</span> Titulo principal: </td>
                 <td>
				  <select name="Cur_Def" id="Cur_Def">
				
				 <option <?php if($hdd_def=='N')
			 { echo "selected";}?> value= "N">No principal</option>
				
				  <option <?php if($hdd_def=='D')
			 { echo "selected";}?> value= "D">Principal</option> 
				 </select>			     </td>
               </tr>
               <tr>
                 <td class="Etiqueta1"><span class="Asterisco">*</span> Instituci&oacute;n:</td>
                 <td><span class="LetraNegra">
                   <input name="Cur_Ins" type="text" id="Cur_Ins" style="text-transform:uppercase" value="<?php echo $hdd_ins;?>" size="100" maxlength="100">
                 </span></td>
               </tr>
               <tr>
                 <td class="Etiqueta1"><span class="Asterisco">*</span> Pa&iacute;s:</td>
                 <td><span class="LetraNegra">
                   <select name="Pas_Cod" id="Pas_Cod">
                     <?php
			do {  
		?>
                     <option <?php if($row_rs_pais['Pas_Cod'] == $hdd_pai)
			 { echo "selected";}?> value="<?php echo $row_rs_pais['Pas_Cod']?>"><?php echo $row_rs_pais['Pas_Nom']?></option>
                     <?php
			} while ($row_rs_pais = $obBD_con1->fetch_assoc($rs_pais));
			/* Mueve el puntero a la posicion indicada */
	?>
                   </select>
                 </span></td>
               </tr>
               <tr bgcolor="#FFFFFF">
                 <td class="Etiqueta1"> Fecha de Inicio  : </td>
                 <td><input name="Cur_Ini" type="text" id="Cur_Ini" value="<?php if(isset($hdd_modificar) and $hdd_ini != '0000-00-00'){ echo $hdd_ini;} else { /* echo date("Y-m-d" ); */} ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="if (trim(this.value) != ''){ validar_fecha2(this); fecha_mayor(this, document.getElementById('Cur_Fin')) }">
                   <img src="../../mascaras/model1/imagenes/calendario.jpg" alt="Ver calendario" style="cursor:pointer" name="calendario" width= "25" height="17" border="0" align="absmiddle" id="calendario">
                     <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Cur_Ini",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		       </script></td></tr>
               <tr bgcolor="#FFFFFF">
                 <td class="Etiqueta1">Fecha de Fin:</td>
                 <td><input name="Cur_Fin" type="text" id="Cur_Fin" value="<?php if(isset($hdd_modificar) and $hdd_ini != '0000-00-00'){ echo $hdd_fin;} else { /* echo date("Y-m-d") ;*/}  ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="if (trim(this.value) != ''){ validar_fecha2(this); fecha_mayor(document.getElementById('Cur_Ini'),this) }">
                     <img src="../../mascaras/model1/imagenes/calendario.jpg" alt="Ver calendario" style="cursor:pointer" name="calendario2" width= "25" height="17" border="0" align="absmiddle" id="calendario2">
                     <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Cur_Fin",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario2",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		       </script></td>
               </tr>
               <tr>
                 <td class="Etiqueta1">Observaci&oacute;n:</td>
                 <td><textarea name="Cur_Obs" cols="38" rows="5" id="Cur_Obs" style="text-transform:uppercase"><?php echo $hdd_obs; ?></textarea></td>
               </tr>
             </table>
		</FIELDSET>			 
		     <br>
        <table width="17%" border="0" class="Azul">
          <tr>
            <td width="100%"><input name="button" type="button" class="Boton_Guardar" title="Guardar" onClick="validar_requeridos(this.form, 'Nia_Cod*Cur_Tit*Cur_Def*Cur_Ins*Pas_Cod', 1)"	value="Guardar">
                <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
                <input type="hidden" name="op" id="op" value="<?php echo $op; ?>">
				<input name="hdd_actualizar" type="hidden" value="<?php echo $hdd_actualizar; ?>" id="hdd_actualizar">
				<input name="Cur_Int" type="hidden" value="<?php echo $Cur_Int; ?>" id="Cur_Int">
				<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">		  
			</td>
          </tr>
        </table>		
	<?Php			
	break;// fin del case 1
	case 2:?>
	 <FIELDSET>
		<LEGEND>
		<label class="Titulos2">Datos a registrar</label>             
		</LEGEND>
		 <?Php
		 echo mensaje_requerido();
		 ?>
		 <input type="hidden" name="Cur_Cod" id="Cur_Cod" value="<?Php echo $Cur_Cod; ?>">
		 <table width="80%" border="0">
           <tr>
             <td width="23%" class="Etiqueta1"><span class="Asterisco">*</span> Cargo: </td>
             <td class="LetraNegra"><input name="Cur_Car" type="text" id="Cur_Car" style="text-transform:uppercase" value="<?php echo $hdd_car;?>" size="100" maxlength="100"></td>
            </tr>
           <tr>
             <td class="Etiqueta1"><span class="Asterisco">* </span>Instituci&oacute;n:</td>
             <td><span class="LetraNegra">
               <input name="Cur_Ins" type="text" id="Cur_Ins" style="text-transform:uppercase" value="<?php echo $hdd_ins;?>" size="100" maxlength="100">
             </span></td>
           </tr>
           <tr bgcolor="#FFFFFF">
             <td class="Etiqueta1"><span class="Asterisco">* </span>Pa&iacute;s:</td>
             <td><span class="LetraNegra">
               <select name="Pas_Cod" id="Pas_Cod">
                 <?php
			do {  
		?>
                 <option <?php if($row_rs_pais['Pas_Cod'] == $hdd_pai)
			 { echo "selected";}?> value="<?php echo $row_rs_pais['Pas_Cod']?>"><?php echo $row_rs_pais['Pas_Nom']?></option>
                 <?php
			} while ($row_rs_pais = $obBD_con1->fetch_assoc($rs_pais));
			/* Mueve el puntero a la posicion indicada */
	?>
               </select>
             </span></td>
           </tr>
           <tr bgcolor="#FFFFFF">
             <td class="Etiqueta1">Fecha de Inicio  : </td>
             <td><input name="Cur_Ini" type="text" id="Cur_Ini" value="<?php if(isset($hdd_modificar) and $hdd_ini != '0000-00-00'){ echo $hdd_ini;} else { /* echo date("Y-m-d"); */ } ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="if (trim(this.value) != ''){ validar_fecha2(this); fecha_mayor(this, document.getElementById('Cur_Fin')) }">
                 <img src="../../imagenes/calendario.jpg" alt="Ver calendario" style="cursor:pointer" name="calendario" width= "25" height="17" border="0" align="absmiddle" id="calendario">
                 <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Cur_Ini",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		    </script></td>
           </tr>
           <tr bgcolor="#FFFFFF">
             <td class="Etiqueta1">Fecha de Fin:</td>
             <td><input name="Cur_Fin" type="text" id="Cur_Fin" value="<?php if(isset($hdd_modificar) and $hdd_ini != '0000-00-00'){ echo $hdd_fin;} else { /* echo date("Y-m-d"); */ }  ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="if (trim(this.value) != ''){ validar_fecha2(this); fecha_mayor(document.getElementById('Cur_Ini'),this) }">
                 <img src="../../imagenes/calendario.jpg" alt="Ver calendario" style="cursor:pointer" name="calendario2" width= "25" height="17" border="0" align="absmiddle" id="calendario2">
                 <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Cur_Fin",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario2",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		    </script></td>
           </tr>
           
           <tr>
             <td class="Etiqueta1">Observaci&oacute;n:</td>
             <td><textarea name="Cur_Obs" cols="38" rows="5" id="Cur_Obs" style="text-transform:uppercase"><?php echo $hdd_obs?></textarea></td>
           </tr>
         </table>
		 </FIELDSET>
		 <br>
        <table width="26%" border="0" class="Azul">
          <tr>
            <td width="100%"><input name="button" type="button" class="Boton_Guardar" title="Guardar" onClick="validar_requeridos(this.form, 'Cur_Car*Cur_Ins*Pas_Cod', 1)"	value="Guardar">
                <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
                <input type="hidden" name="op" id="op" value="<?php echo $op; ?>">
				<input name="hdd_actualizar" type="hidden" value="<?php echo $hdd_actualizar; ?>" id="hdd_actualizar">
				<input name="Cur_Int" type="hidden" value="<?php echo $Cur_Int; ?>" id="Cur_Int">
				<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">		  
			</td>
          </tr>
        </table>		
	<?php 
	break;
	
	case 3:
		/****consulta del tipo de capacitación*****/
		$rs_tipcurria=$obBD_con1->consulta(sentencias_rhu(622, ''), $obBD_conexion->conexion);
		$row_rs_tipcurria = $obBD_con1->registros();
		$total_rs_tipcurria = $obBD_con1->numregistros();  	
	?>
    <fieldset>        
		<LEGEND>
		<label class="Titulos2">Datos a registrar</label>             
		</LEGEND>
		 <?Php
		 echo mensaje_requerido();
		 ?>
	     <input type="hidden" name="Cur_Cod" id="Cur_Cod" value="<?Php echo $Cur_Cod; ?>">
	     <table width="80%" border="0">
          <tr>
            <td width="23%" class="Etiqueta1"><span class="Asterisco">*</span> Tipo de capacitaci&oacute;n : </td>
            <td class="LetraNegra"><select name="Tca_Cod" id="Tca_Cod">
                <option></option>
                <?php
				do {  
			?>
                <option <?php if($row_rs_tipcurria['Tca_Cod'] == $hdd_tip)
			 { echo "selected";}?> value="<?php echo $row_rs_tipcurria['Tca_Cod']?>"><?php echo $row_rs_tipcurria['Tca_Des']?></option>
                <?php
				} while ($row_rs_tipcurria = $obBD_con1->fetch_assoc($rs_tipcurria));
		?>
            </select></td>
            </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> T&iacute;tulo:</td>
            <td><span class="LetraNegra">
              <textarea name="Cur_Tit" cols="75" rows="3" id="Cur_Tit" style="text-transform:uppercase"><?php echo $hdd_tit;?></textarea>
            </span></td>
          </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Instituci&oacute;n:</td>
            <td><span class="LetraNegra">
              <input name="Cur_Ins" type="text" id="Cur_Ins" style="text-transform:uppercase" value="<?php echo $hdd_ins;?>" size="100" maxlength="100">
            </span></td>
          </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Pa&iacute;s:</td>
            <td><span class="LetraNegra">
              <select name="Pas_Cod" id="Pas_Cod">
                <?php
			do {  
		?>
                <option <?php if($row_rs_pais['Pas_Cod'] == $hdd_pai)
			 { echo "selected";}?> value="<?php echo $row_rs_pais['Pas_Cod']?>"><?php echo $row_rs_pais['Pas_Nom']?></option>
                <?php
			} while ($row_rs_pais = $obBD_con1->fetch_assoc($rs_pais));
			/* Mueve el puntero a la posicion indicada */
	?>
              </select>
            </span></td>
          </tr>
          <tr bgcolor="#FFFFFF">
            <td class="Etiqueta1"><span class="Asterisco"> *</span> Fecha de Inicio  : </td>
            <td><input name="Cur_Ini" type="text" id="Cur_Ini" value="<?php if(isset($hdd_modificar) and $hdd_ini != '0000-00-00'){ echo $hdd_ini;} else { /*echo date("Y-m-d"); */ } ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this)">
                <img src="../../imagenes/calendario.jpg" alt="Ver calendario" style="cursor:pointer" name="calendario" width= "25" height="17" border="0" align="absmiddle" id="calendario">
                <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Cur_Ini",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		  </script></td>
          </tr>
          <tr bgcolor="#FFFFFF">
            <td class="Etiqueta1"><span class="Asterisco">* </span>Fecha de Fin:</td>
            <td><input name="Cur_Fin" type="text" id="Cur_Fin" value="<?php if(isset($hdd_modificar) and $hdd_ini != '0000-00-00'){ echo $hdd_fin;} else { /* echo date("Y-m-d"); */ }  ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this)">
                <img src="../../imagenes/calendario.jpg" alt="Ver calendario" style="cursor:pointer" name="calendario2" width= "25" height="17" border="0" align="absmiddle" id="calendario2">
                <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Cur_Fin",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario2",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		  </script></td>
          </tr>
          <tr>
            <td class="Etiqueta1">Observaci&oacute;n:</td>
            <td><textarea name="Cur_Obs" cols="38" rows="5" id="Cur_Obs" style="text-transform:uppercase"><?php echo $hdd_obs?></textarea></td>
          </tr>
        </table>
		</FIELDSET>
		<br>
        <table width="23%" border="0" class="Azul">
          <tr>
            <td width="100%"><input name="button" type="button" class="Boton_Guardar" title="Guardar" onClick="validar_requeridos(this.form, 'Tca_Cod*Cur_Tit*Cur_Ins*Pas_Cod', 1)"	value="Guardar">
                <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
                <input type="hidden" name="op" id="op" value="<?php echo $op; ?>">
				<input name="hdd_actualizar" type="hidden" value="<?php echo $hdd_actualizar; ?>" id="hdd_actualizar">
				<input name="Cur_Int" type="hidden" value="<?php echo $Cur_Int; ?>" id="Cur_Int">
				<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">		  
				</td>
          </tr>
        </table>
	<?php break;
}//fin del case
}/*fin del if(isset($hdd_agregar))*/
?>
</form>		
	</div> 
<?Php
}//Fin del if(!isset($hdd_buscar) && isset($op))
?>	
	 </td>
	</tr>
</table>	  
</BODY>
</HTML>
<?php
@$obBD_con1->free_result($rs_personales);
@$obBD_con1->free_result($rs_tipcurria);
@$obBD_con1->free_result($rs_niveles);
@$obBD_con1->free_result($rs_curri);
@$obBD_con1->free_result($rs_curriculo);
@$obBD_con1->free_result($rs_pais);
@$obBD_con1->free_result($rs_laboral);
@$obBD_con1->free_result($rs_capacitacion);
/*********** Cierro las conexiones **********************/
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
/********************************************************/
?>