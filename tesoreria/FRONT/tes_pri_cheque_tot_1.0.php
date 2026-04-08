<?	
	require_once('../../administrador/LOGICA/seguridad.php');
	require_once('../LOGICA/tes_log_cheque.php');
	require_once('../../Librerias/procedimientos/almacenados_standar.php');
	require_once('../../Librerias/postclass.php');	
	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
	
/** 
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Che;
/***********************************************/


/* Consulta de la cabecera del reporte */
$row_rs_empresa = $obBD_con1->getRowConsulta(207,$Ses_Suc_Cod,$obBD_conexion);

$hoy = date("Y-m-d");

/* Consulta del detalle de la mayorizacion */
//$rs_tot_cheques = $obBD_con1->consulta(sentencias_tes(163, $obBD_con1->parametros($ini.'*'.$fin.'*'.$opt_option)), 
$rs_tot_cheques = $obBD_con1->getArrayConsulta(375,$ini.'*'.$fin.'*'.$opt_option.'*'.$Ses_Emp_Cod.'*'.$opt_est,$obBD_conexion);
$total_rs_tot_cheques = count($rs_tot_cheques);	

?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
	 <tr  class="Titulos3" align="center">
	  <td height="10"><?Php echo $row_rs_empresa['Emp_Nom']; ?></td>
    </tr>
	 <tr  class="Titulos3" align="center">
	   <td height="10">LISTADO DE CHEQUES <?Php if ($opt_option == 'A'){ echo "Emitidos"; }
												else { echo "Anulados";}	 ?> DESDE <?Php echo $ini; ?> HASTA <?Php echo $fin; ?> </td>
	   </tr>
	 <tr>
	   <td height="10">&nbsp;</td>
	   </tr>
	<tr>
        <td align="center" valign="top"><table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse; table-layout:fixed;">
  <tr class="Texto_normal_10">
    <td width="4%" align="center" bgcolor="#CCCCCC"><strong>N&ordm; Compr </strong></td>
    <td width="20%" align="center" bgcolor="#CCCCCC"><strong>Proveedor</strong></td>
    <td width="15%" align="center" bgcolor="#CCCCCC"><strong>Banco</strong></td>
    <td width="5%" align="center" bgcolor="#CCCCCC"><strong>N&ordm; Cheq</strong></td>
    <td width="5%" align="center" bgcolor="#CCCCCC"><strong>Fecha</strong></td>
    <td width="31%" align="center" bgcolor="#CCCCCC"><strong>Concepto</strong></td>
    <td width="8%" align="center" bgcolor="#CCCCCC"><strong>Valor</strong></td>
  </tr>
  <? 
	if ($total_rs_tot_cheques!=0) {
			$total = 0;
			foreach($rs_tot_cheques as $row_rs_tot_cheques) { 
				$total = $total + $row_rs_tot_cheques['Che_Val'];
			?>
  <tr class="Texto_normal_10">
    <td ><? echo $row_rs_tot_cheques['Tia_Abr'].'-'.$mes.'-'.$row_rs_tot_cheques['Com_Num']; ?> </td>
    <td><? echo $row_rs_tot_cheques['Prs_Ape'].' '.$row_rs_tot_cheques['Prs_Nom']; ?> </td>
    <td><? echo $row_rs_tot_cheques['Pld_Des']; ?> </td>
    <td align="right"><? echo $row_rs_tot_cheques['Che_Num']; ?> </td>
    <td width="15%" align="center"><? echo $row_rs_tot_cheques['Che_Fec']; ?> </td>
    <td width="15%" align="left"><? echo $row_rs_tot_cheques['Com_Con']; ?></td>
    <td align="right"><? echo "$".''.number_format($row_rs_tot_cheques['Che_Val'],2,'.',''); ?> </td>
  </tr>
  <?  } 
  }//Fin del if ($total_rs_tot_cheques) ?>
  <tr>
    <td colspan="6" class="Texto_normal_10"><div align="right"><strong>TOTAL</strong></div></td>
    <td class="Texto_normal_10" align="right"><div align="right"><strong><?php echo number_format($total,2,'.',''); ?></strong></div></td>
  </tr>
</table>
</td>
  </tr>
</table>	  
</BODY></HTML>
<?Php 
@mysqli_free_result($rs_empresa);
@mysqli_free_result($rs_tot_cheques);
$obBD_conexion->cerrar();
@$obBD_con1->liberar();
?>