<?php  
	require_once('../../administrador/LOGICA/seguridad.php');
  	require_once('../LOGICA/logica.php');	  
       require_once('../../Librerias/procedimientos/almacenados_standar.php');	
	require_once('../../Librerias/postclass.php'); 
//* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Rhu;
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Rhu;
/* Llamado de la libreria para evitar el reenvio de datos */
$thisPost = new Post_Block;
//echo "cedula=";
$Cedula =$Usu_Ced;
/*Consulta de personal por cedulA	*/
$rs_personales=$obBD_con1->consulta(sentencias_rhu(104, $obBD_con1->parametros($Ses_Prs_Cod)), $obBD_conexion->conexion);
$row_rs_personales = $obBD_con1->registros();
$total_rs_personales= $obBD_con1->numregistros(); 
/*Consulta de personal por cedulA	*/
$rs_person=$obBD_con1->consulta(sentencias_rhu(97, $obBD_con1->parametros($Ses_Prs_Cod)), $obBD_conexion->conexion);
$row_rs_person = $obBD_con1->registros();
$total_rs_person= $obBD_con1->numregistros(); 
$Per_Cod=$row_rs_person['Per_Cod'];
//echo "codigo";
//echo $Per_Cod;
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
/* Consulta si existe curriculo */
$rs_curri=$obBD_con1->consulta(sentencias_rhu(634, $obBD_con1->parametros($Per_Cod)), $obBD_conexion->conexion);
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
//************************************************************************************************************
?>				

<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
<script language="javascript" src="../Librerias/validaciones/validacion.js"></script>
<link href="../../mascaras/model1/estilos/estilo1.css" rel="stylesheet" type="text/css">
<link href="../../mascaras/model1/estilos/interfaz.css" rel="stylesheet" type="text/css">
<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
</head>

<body class="Cuerpo">
<table width="100%"  height="292" border="0" align="center">
  <tr>
    <td  valign="top">	  
	<table width="100%" border="0" align="center" class="Titulos3">
      <tr align="center">
        <td width="106" rowspan="3" valign="top"><img src="../../imagenes/logo.jpg" width="128" height="100"></td>
        <td  align="center">UNIVERSIDAD TECNOLOGICA &quot;SAN ANTONIO DE MACHALA&quot; </td>
        </tr>
      <tr align="center">
        <td align="center">CURRICULUM VITAE </td>
        </tr>

      <tr align="center" valign="top">
        <td height="14">&nbsp;</td>
        </tr>
    </table></td>
  </tr>
  <tr valign="top">
    <td height="161" valign="top"><table width="100%" border="1" align="center" bordercolor="#000000">
      <tr>
        <td colspan="2" class="TITULO_REPORTE"><div align="left">DATOS PERSONALES </div></td>
        </tr>
      <tr>
        <td colspan="2"><table width="100%" border="0">
            <tr>
              <td width="176" height="19" class="TITULO_REPORTE"><div align="left">APELLIDOS:</div></td>
              <td width="591" class="Texto_Reporte"><?Php echo $row_rs_personales['Prs_Nom']; ?></td>
              <td width="224" rowspan="5">
			  <table width="112" height="106" class="curriculo" bordercolor="#000000">
			  <tr>
			  <td width="138">&nbsp;</td>
			  
			  </tr>
			  </table>
			  
			  </td>
            </tr>
            <tr>
              <td class="TITULO_REPORTE"><div align="left">NOMBRES:</div></td>
              <td  class="Texto_Reporte"><?Php echo $row_rs_personales['Prs_Ape'];?></td>
              </tr>
            <tr>
              <td class="TITULO_REPORTE"><div align="left">N&ordm;. CEDULA:</div></td>
              <td  class="Texto_Reporte"><?Php echo $row_rs_personales['Prs_Ced']; ?></td>
              </tr>
            <tr>
              <td class="TITULO_REPORTE"><div align="left">estado civil: </div></td>
              <td  class="Texto_Reporte"><?Php echo $row_rs_personales['Prs_Esc']; ?></td>
              </tr>
            <tr>
              <td class="TITULO_REPORTE"><div align="left">edad:</div></td>
			  <td  class="Texto_Reporte"><?Php echo $an1.' años '?></td>
			  <?Php 
    $anio=explode('-',$row_rs_personales['Prs_Fec']);
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
              </tr>
            <tr>
              <td class="TITULO_REPORTE"><div align="left">DIRECCION:</div></td>
              <td class="Texto_Reporte"><?PHP  echo $row_rs_personales['Prs_Dir'];  ?></td>
              <td class="Texto_Reporte">&nbsp;</td>
            </tr>
            <tr>
              <td class="TITULO_REPORTE"><div align="left">CIUDAD:</div></td>
              <td class="Texto_Reporte"><?Php echo $row_rs_personales['Ciu_Des'];?></td>
              <td class="Texto_Reporte">&nbsp;</td>
            </tr>
            <tr>
              <td class="TITULO_REPORTE"><div align="left">TELEFONO</div></td>
              <td class="Texto_Reporte"><?Php echo $row_rs_personales['Prs_Tel']; ?></td>
              <td class="Texto_Reporte">&nbsp;</td>
            </tr>
            <tr>
              <td class="TITULO_REPORTE"><div align="left">E-MAIL</div></td>
              <td class="Texto_Reporte"><?Php echo $row_rs_personales['Prs_Cor']; ?></td>
              <td class="Texto_Reporte">&nbsp;</td>
            </tr>
            <tr>
              <td class="TITULO_REPORTE">&nbsp;</td>
              <td class="TITULO_REPORTE">&nbsp;</td>
              <td class="TITULO_REPORTE">&nbsp;</td>
            </tr>
            <tr>
              <td colspan="3" class="Texto_Reporte"></td>
            </tr>
          </table>          </td>
        </tr>
      <tr>
        <td colspan="2" class="TITULO_REPORTE"><div align="left">TITULOS ACADEMICOS </div></td>
        </tr>
      <tr class="TITULO_REPORTE">
        <td colspan="2"><table width="100%" border="0">

          <tr>
            <td colspan="2" class="TITULO_REPORTE"><div align="left">PRIMARIA:</div></td>
            <td width="738" class="Texto_Reporte"> </td>
          </tr>
		  <tr>
       <td width="65" class="TEXTO_REPORTE">&nbsp;</td>
            <td width="142" class="TEXTO_REPORTE"><?Php do {if($row_rs_curriculo['Nia_Cod']==1){ $inip=explode('-',$row_rs_curriculo['Cur_Ini']);$finp=explode('-',$row_rs_curriculo['Cur_Fin']); echo $inip[0]; echo "-"; echo $finp[0]; }}while($row_rs_curriculo = mysqli_fetch_assoc ($rs_curriculo)); $row_rs_curriculo=first_last($rs_curriculo, $row_rs_curriculo, 0);?></td>
            <td class="Texto_Reporte"><?php do {if($row_rs_curriculo['Nia_Cod']== 1){ echo $row_rs_curriculo['Cur_Tit'];}}while($row_rs_curriculo = mysqli_fetch_assoc ($rs_curriculo)); $row_rs_curriculo=first_last($rs_curriculo, $row_rs_curriculo, 0);?></td>
          </tr>
		 
          <tr>
            <td colspan="2" class="TITULO_REPORTE"><div align="left">SECUNDARIA:</div></td>
            <td class="Texto_Reporte">&nbsp;</td>
          </tr>
          <tr>
            <td class="TEXTO_REPORTE">&nbsp;</td>
            <td class="TEXTO_REPORTE"><?Php do {if($row_rs_curriculo['Nia_Cod']==2){ $ini=explode('-',$row_rs_curriculo['Cur_Ini']);$fin=explode('-',$row_rs_curriculo['Cur_Fin']); echo $ini[0]; echo "-"; echo $fin[0]; }}while($row_rs_curriculo = mysqli_fetch_assoc ($rs_curriculo)); $row_rs_curriculo=first_last($rs_curriculo, $row_rs_curriculo, 0);?></td>
            <td class="Texto_Reporte"><?Php do {if($row_rs_curriculo['Nia_Cod']==2){ echo $row_rs_curriculo['Cur_Tit']; }}while($row_rs_curriculo = mysqli_fetch_assoc ($rs_curriculo)); $row_rs_curriculo=first_last($rs_curriculo, $row_rs_curriculo, 0);?></td>
          </tr>
          <tr>
            <td colspan="2" class="TITULO_REPORTE"><div align="left">SUPERIOR:</div></td>
            <td class="Texto_Reporte">&nbsp;</td>
          </tr>
          <tr>
            <td class="Texto_REPORTE">&nbsp;</td>
            <td class="Texto_REPORTE"><?Php do {if($row_rs_curriculo['Nia_Cod']==3){ $init=explode('-',$row_rs_curriculo['Cur_Ini']);$fint=explode('-',$row_rs_curriculo['Cur_Fin']); echo $init[0]; echo "-"; echo $fint[0]; }}while($row_rs_curriculo = mysqli_fetch_assoc ($rs_curriculo)); $row_rs_curriculo=first_last($rs_curriculo, $row_rs_curriculo, 0);?></td>
            <td class="Texto_Reporte"><?Php  do { if($row_rs_curriculo['Nia_Cod']==3){ echo $row_rs_curriculo['Cur_Tit']; echo "<br>";}}while($row_rs_curriculo = mysqli_fetch_assoc ($rs_curriculo)); $row_rs_curriculo=first_last($rs_curriculo, $row_rs_curriculo, 0);?></td>
          </tr>
          <tr>
            <td colspan="2" class="TITULO_REPORTE"><div align="left">POSTGRADO:</div></td>
            <td class="Texto_Reporte">&nbsp;</td>
          </tr>
          <tr>
            <td class="Texto_REPORTE">&nbsp;</td>
            <td class="Texto_REPORTE"><?Php do {if($row_rs_curriculo['Nia_Cod']==4){ $inic=explode('-',$row_rs_curriculo['Cur_Ini']);$finc=explode('-',$row_rs_curriculo['Cur_Fin']); echo $inic[0]; echo "-"; echo $finc[0]; echo "<br>" ; echo "<br>";}}while($row_rs_curriculo = mysqli_fetch_assoc ($rs_curriculo)); $row_rs_curriculo=first_last($rs_curriculo, $row_rs_curriculo, 0);?></td>
            <td class="TEXTO_REPORTE"><span class="Texto_Reporte">
              <?Php  do { if($row_rs_curriculo['Nia_Cod']==4){ echo $row_rs_curriculo['Cur_Tit']; echo "<br>";echo $row_rs_curriculo['Cur_Ins'];echo "<br>"; }}while($row_rs_curriculo = mysqli_fetch_assoc ($rs_curriculo));?>
            </span></td>
          </tr>
          
          <tr>
            <td colspan="3" class="Texto_Reporte"></td>
          </tr>
        </table></td>
        </tr>
      
      
      
      <tr valign="top" class="TITULO_REPORTE">
        <td colspan="2"><div align="left">EXPERIENCIA LABORAL </div></td>
        </tr>
      <tr class="TITULO_REPORTE">
        <td colspan="2">
		<table width="100%" border="0">
          
         <?php do {?>
		  <tr class="Texto_Reporte">
		<td width="15%" class="TEXTO_REPORTE">
		<?php $annio=explode('-',$row_rs_laboral['Cur_Ini']) ; $mesl=mes($annio[1],1); echo $mesl;  echo "&nbsp;"; echo $annio[0];?>		</td>
            <td width="15%" class="TEXTO_REPORTE"><?php $anniosal=explode('-',$row_rs_laboral['Cur_Fin'] ); $meslf=mes($anniosal[1],1); echo $meslf;echo "&nbsp;"; echo $anniosal[0];?></td>
            <td width="30%" class="TEXTO_REPORTE"><?PHP  echo $row_rs_laboral['Cur_Car'];  ?></td>
            <td width="20%" class="TEXTO_REPORTE"><?PHP  echo $row_rs_laboral['Cur_Ins'];  ?></td>
			 <td width="20%" class="TEXTO_REPORTE"><?PHP  echo $row_rs_laboral['Cur_Dur']; echo "&nbsp;";echo "meses"; ?></td>
          </tr>

          <tr>
            <td colspan="4" class="Texto_Reporte"></td>
          </tr>
		  <?php }while($row_rs_laboral = mysqli_fetch_assoc ($rs_laboral));?>
        </table></td>
      </tr>
      
      <tr class="TITULO_REPORTE">
        <td colspan="2"> <div align="left">SEMINARIOS Y CAPACITACIONES </div></td>
        </tr>
      <tr valign="top" class="TITULO_REPORTE">
        <td >
		<table width="100%" border="0">
          <?php do {?>
          <tr class="Texto_Reporte">
            <td width="10%" class="TEXTO_REPORTE"><?php $annios=explode('-',$row_rs_capacitacion['Cur_Ini']); echo $annios[0]; ?>        </td>
			<td width="15%" class="TEXTO_REPORTE"><?php echo $row_rs_capacitacion['Tca_Des']; ?>        </td>
            <td width="30%" class="TEXTO_REPORTE"><?php echo $row_rs_capacitacion['Cur_Tit'];  ?></td>
            <td width="30%" class="TEXTO_REPORTE"><?PHP  echo $row_rs_capacitacion['Cur_Ins'];  ?></td>
            <td width="15%" class="TEXTO_REPORTE"><?PHP  echo $row_rs_capacitacion['Cur_Dur']; echo "&nbsp;";echo "horas"; ?></td>
            
          </tr>
          <tr>
            <td colspan="4" class="Texto_Reporte"></td>
          </tr>
          <?php }while($row_rs_capacitacion = mysqli_fetch_assoc ($rs_capacitacion));?>
        </table></td>
        
      </tr>
      <tr class="TITULO_REPORTE">
        <td colspan="2">&nbsp;</td>
        </tr>
      
    </table></td>
  </tr>
  <tr>
    <td height="21"><table width="550" border="0" align="center">
      <tr>
        <td width="403" class="Mayusculas"><?Php echo date("Y/M/d"); ?></td>
        <td width="137" class="Mayusculas">DPTO. ACADEMICO </td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="21">&nbsp;</td>
  </tr>
  <tr>
    <td height="21">&nbsp;</td>
  </tr>
</table>
</body>
</html>
<?php
$obBD_con1->free_result($rs_personales);
$obBD_con1->free_result($rs_tipcurria);
$obBD_con1->free_result($rs_niveles);
$obBD_con1->free_result($rs_curri);
$obBD_con1->free_result($rs_curriculo);
$obBD_con1->free_result($rs_pais);
$obBD_con1->free_result($rs_laboral);
$obBD_con1->free_result($rs_capacitacion);
$obBD_con1->free_result($rs_person);
/*********** Cierro las conexiones **********************/
  		$obBD_con1->liberar();
	  	$obBD_conexion->cerrar();
/********************************************************/
?>