<?php  
	  require_once('../../administrador/LOGICA/seguridad.php');
	  require_once('../LOGICA/logica.php');
	  require_once('../../Librerias/postclass.php');
	  require_once('../../Librerias/procedimientos/almacenados_standar.php');	
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Rhu;
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Rhu;
/* Llamado de la libreria para evitar el reenvio de datos */
$thisPost = new Post_Block;
/*Consulta de personal por cedulA	*/
$rs_personales=$obBD_con1->consulta(sentencias_rhu(104, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);
$row_rs_personales = $obBD_con1->registros();
$total_rs_personales= $obBD_con1->numregistros(); 
/* Consulta de las nacionalidades */
$rs_pais=$obBD_con1->consulta(sentencias_rhu(606, $obBD_con1->parametros('')), $obBD_conexion->conexion);
$row_rs_pais = $obBD_con1->registros();
$total_rs_pais = $obBD_con1->numregistros();  
/****consulta del tipo de capacitación*****/
$rs_tipcurria=$obBD_con1->consulta(sentencias_rhu(622, $obBD_con1->parametros('')), $obBD_conexion->conexion);
$row_rs_tipcurria = $obBD_con1->registros();
$total_rs_tipcurria = $obBD_con1->numregistros();  
/*Consulta para cargar los niveles academicos en el combo box*/
$rs_niveles = $obBD_con1->consulta(sentencias_rhu(615, $obBD_con1->parametros('')), $obBD_conexion->conexion);
$row_rs_niveles = $obBD_con1->registros();
$total_rs_niveles = $obBD_con1->numregistros();  


 if (isset($codigo))
{
	$rs_consulta = $obBD_con1->consulta(sentencias_rhu(112, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);		
	$row_rs_consulta = $obBD_con1->registros();
	$total_rs_consulta = $obBD_con1->numregistros();
	if(isset($hdd_distri))
		{
		/*Consulta de personal por cedulA	*/
		
		/* Consulta si existe curriculo */
		$rs_curri=$obBD_con1->consulta(sentencias_rhu(634, $obBD_con1->parametros($codigo)), $obBD_conexion->conexion);
		$row_rs_curri = $obBD_con1->registros();
		$total_rs_curri = $obBD_con1->numregistros(); 
		$Cur_Cod=$row_rs_curri['Cur_Cod'];
		//echo $Cur_Cod;
		/**************/
		$rs_curriculo=$obBD_con1->consulta(sentencias_rhu(619, $obBD_con1->parametros($Cur_Cod)), $obBD_conexion->conexion);
		$row_rs_curriculo = $obBD_con1->registros();
		$total_rs_curriculo = $obBD_con1->numregistros();  
		/**************/
		$rs_laboral=$obBD_con1->consulta(sentencias_rhu(620, $obBD_con1->parametros($Cur_Cod)), $obBD_conexion->conexion);
		$row_rs_laboral = $obBD_con1->registros();
		$total_rs_laboral = $obBD_con1->numregistros();  
		/**************/
		$rs_capacitacion=$obBD_con1->consulta(sentencias_rhu(621, $obBD_con1->parametros($Cur_Cod)), $obBD_conexion->conexion);
		$row_rs_capacitacion = $obBD_con1->registros();
		$total_rs_capacitacion = $obBD_con1->numregistros(); 
		}
}
 
?>	  

<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../../Librerias/fecha.js"></script>
		<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">				
		<script language="javascript" src="../../Librerias/java.js"></script>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"> 
	<link href="../../mascaras/model1/estilos/interfaz.css" rel="stylesheet" type="text/css">
	</HEAD>
<BODY>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; consulta de curriculos de personal </td>
  </tr>
  <tr>
   <td valign="top">
  <form action="<?Php $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
        <?Php include("../../componentes/FRONT/com_con_persona.php"); ?>
 </form>
   
 <?Php  if ($txt_busqueda != ""  )
	    {   if ($op_opciones == "d")
			{
				$rs_buscar = $obBD_con1->consulta(sentencias_rhu(96, $obBD_con1->parametros($txt_busqueda)), $obBD_conexion->conexion);		
						
			}
	  	    else 
			  {
				 $rs_buscar = $obBD_con1->consulta(sentencias_rhu(93, $obBD_con1->parametros($txt_busqueda)), $obBD_conexion->conexion);				
			  }	 
		  
		   $row_rs_buscar = $obBD_con1->registros();
		   $total_rs_buscar = $obBD_con1->numregistros();
		 
	  } 
	     
 ?>       
 <?php if(isset($hdd_buscar))
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
        <td width="4%">&nbsp;</td>
      </tr>
      <?Php 
	 if($total_rs_buscar > 0)
	 {	  
	  do { //Abrir el } while ($row_rs_buscar = mysqli_fetch_assoc($rs_buscar) ?>
      
	<form name="form6" method="post"  action="<?php $_SERVER['PHP_SELF']?>">
		 	
      <tr class="Fondo">
        <td align="center"><?Php echo $row_rs_buscar['Prs_Cod']; ?></td>
        <td align="center"><?Php echo $row_rs_buscar['Prs_Ced']; ?></td>
        <td align="center"><?Php echo $row_rs_buscar['Prs_Ape']; ?></td>
        <td align="center"><?Php echo $row_rs_buscar['Prs_Nom']; ?></td>
        <td align="center"><?Php echo $row_rs_buscar['Prs_Est'] ?>
            <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Per_Cod'];?>">
            <input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
            <input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">
			  <input name="op" id="op" type="hidden" value="1">
            <input type="image" name="imageField" src="../../mascaras/model1/imagenes/forward.png" width="22" height="22">
			<input name="hdd_distri" id="hdd_distri" type="hidden" value="1">
			<input name="hdd_ape" id="hdd_ape" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Ape']; ?>">
			<input name="hdd_nom" id="hdd_nom" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Nom']; ?>">
            <?Php
		?>
        </td>
      </tr>
	  </form>  
       <?php  } while ($row_rs_buscar = mysqli_fetch_assoc ($rs_buscar)); 
  	}//Fin del if($total_rs_buscar != 0)
	else
	{ ?>
      <tr>
        <td colspan="5" align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
      </tr>
      <?Php	 }//Fin del else if($total_rs_buscar != 0) ?>
    </table>
    </FIELDSET>
<?Php  } /*fin del if(isset($hdd_buscar) )*/?> 


  

<?Php if (isset($hdd_distri)) 
{ ?>

<FIELDSET>
<LEGEND>
<label class="Titulos2">Curriculo</label>
</LEGEND>
<!---->
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos Personales</label>
</LEGEND>

<table width="100%" border="0">
  <tr>
    <td width="16%" class="Etiqueta1">C&eacute;dula:</td>
    <td width="84%" class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Ced'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Tipo de documento: </td>
    <td class="LetraNegra"><?Php echo $row_rs_consulta['Ide_Des'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Nombres:</td>
    <td class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Nom'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Edad:</td>
   <?Php 
    $anio=explode('-',$row_rs_consulta['Prs_Fec']);
	 ?>    
  
    <?php 
	/*** Cálculo de la edad **************/  
	$anio_actual=date('Y-m-d');
	$anio_actual=explode('-',$anio_actual);
	if($anio_actual[1]>=$anio[1])
		{
			$mss=$anio_actual[1]-$anio[1];			
			$aaux=0;
		}
	else{
			$mss_sum=$anio_actual[1]+12;
			$mss=$mss_sum-$anio[1];
			$aaux=1;
		}
	$an1=$anio_actual[0]+$aaux;
	$an1=$anio_actual[0]-$anio[0];
	
	?>    
	<td class="LetraNegra"><?Php echo $an1.' años '?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Apellidos: </td>
    <td class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Ape'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1"> Sexo: </td>
    <td class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Sex'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1"> Estado civil: </td>
    <td class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Esc'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Direcci&oacute;n domiciliaria:</td>
    <td class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Dir'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Ciudad:</td>
    <td class="LetraNegra"><?Php echo $row_rs_consulta['Ciu_Des'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Tel&eacute;fono casa: </td>
    <td class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Tel'] ?></td>
  </tr>
  
  <tr>
    <td class="Etiqueta1">Celular:</td>
    <td class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Cel'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Correo electr&oacute;nico: </td>
    <td class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Cor'] ?></td>
  </tr>
</table>
</FIELDSET>
<!---->
<!---->
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos Academicos</label>
</LEGEND>

<table width="100%" border="1" cellpadding="0" cellspacing="0">
  <tr class="Cabecera1">
    <td width="20%">Nivel</td>
    <td width="30%">Titulo</td>
    <td width="30%">Instituci&oacute;n</td>
    <td width="20%">Pais</td>
    </tr>
  <?php
  if($total_rs_curriculo>0)
  {
  $i=0;
  do{
  $i++;?>
  <tr class="Fondo">
    <td><?php echo $row_rs_curriculo['Nia_Des'];?> </td>
    <td><?php echo $row_rs_curriculo['Cur_Tit'];?> </td>
    <td><?php echo $row_rs_curriculo['Cur_Ins'];?> </td>
    <td><?php echo $row_rs_curriculo['Pas_Nom'];?> </td>
   
  </tr>
  <?php }while($row_rs_curriculo = mysqli_fetch_assoc ($rs_curriculo)); ?>
  <?php 
 } /*fin del if(total_rs_curriuclo==0)*/
  else{?>
  <tr>
    <td colspan="6"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
  </tr>
  <?php }?>
</table>
</FIELDSET>
<!---->
<!---->
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos Laborales</label>
</LEGEND>
<table width="100%" border="1" cellpadding="0" cellspacing="0">
  <tr class="Cabecera1">
    <td width="20%"> Descripción</td>
    <td width="30%">Institución</td>
    <td width="30%">Duración</td>
    <td width="20%">País</td>
    </tr>
  <?php
  if($total_rs_laboral>0)
  {
  $i=0;
  do{
  $i++;?>
  <tr class="Fondo">
    <td>
      <?php echo $row_rs_laboral['Cur_Car'];?>    </td>
    <td>
      <?php echo $row_rs_laboral['Cur_Ins'];?>    </td>
    <td>
      <?php echo $row_rs_laboral['Cur_Dur'].' '. "MESES";?>    </td>
    <td>
      <?php echo $row_rs_laboral['Pas_Nom'];?>    </td>
	
  </tr>
  <?php }while($row_rs_laboral = mysqli_fetch_assoc ($rs_laboral)); ?>
  <?php 
 } /*fin del if  if($total_rs_laboral>0)*/
  else{?>
  		<tr>
				<td colspan="7"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
			</tr>
		<?php }?>
</table>
</FIELDSET>
<!---->
<!---->
<FIELDSET>
<LEGEND>
<label class="Titulos2">Seminarios y Capacitaciones</label>
</LEGEND>

<table width="100%" border="1" cellpadding="0" cellspacing="0">
  <tr class="Cabecera1">
    <td width="20%">Descripción </td>
    <td width="30%">Institucion</td>
    <td width="30%">Titulo</td>
    <td width="20%">Pais</td>
    </tr>
  <?php
  if($total_rs_capacitacion>0){
  $i=0;
  do{
  $i++;?>
  <tr class="Fondo">
    <td><?php echo $row_rs_capacitacion['Tca_Des'];?> </td>
    <td><?php echo $row_rs_capacitacion['Cur_Ins'];?> </td>
    <td><?php echo $row_rs_capacitacion['Cur_Tit'];?> </td>
    <td><?php echo $row_rs_capacitacion['Pas_Nom'];?> </td>
  
  </tr>
  <?php }while($row_rs_capacitacion = mysqli_fetch_assoc ($rs_capacitacion)); ?>
  <?php 
  }/*fin del  if($total_rs_capacitacion>0)*/
  else{?>
  <tr>
    <td colspan="7"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
  </tr>
  <?php }?>
</table>
</FIELDSET>
<!---->
<table width="100" border="0" class="Azul">
  <tr>
	<td width="100%" align="center" >
	 <form name='frmop1' method='post' action='rhu_pri_curriculo.php' target='_blank' >
	 <input type="submit" name="Submit" value="Enviar" class="Boton_Imprimir" >
	 <input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo; ?>">
     </form>	
	

 	
	</td>
  </tr>
</table>
</FIELDSET>

<?php } ?>
<br>


   </td>
  </tr>

</table>	   
</BODY></HTML>
<?Php
@mysqli_free_result($rs_buscar);
@mysqli_free_result($rs_consulta); 

?>