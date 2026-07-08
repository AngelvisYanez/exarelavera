<?php	
/*
* Descripción: Reporte de la opción Totales, Detalle y Puntos de Impresión
* Fecha de actualización: 2012-05-26
* Desarrollador: Lewis Chimarro
* Fecha de actualización: 2013-03-22
* Desarrollador: Lewis Chimarro
* Descripcion: Se agrego 2 columnas, donde se muestra el descuento y el valor neto pagado
*/	
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_retencion.php');	  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');		

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Ret($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Ret;	 	 	 

if($Chk_Fec!=1)
{		
	if($cmb_mes!='T')
	{
		$ini=$cmb_anio.'-'.str_pad($cmb_mes, 2, "0", STR_PAD_LEFT).'-'.'01'; 
		$fin=$cmb_anio.'-'.str_pad($cmb_mes, 2, "0", STR_PAD_LEFT).'-'.'31';
	}else{
		$ini=$cmb_anio.'-01-01'; 
		$fin=$cmb_anio.'-12-31';			
	}
}

if($Ren_Cod=='T')
{
	$rs_DatosCodigo=$obBD_con1->getArrayConsulta(560,$Ses_Emp_Cod,$obBD_conexion);			
}else{
	$rs_DatosCodigo=$obBD_con1->getArrayConsulta(558,$Ren_Cod,$obBD_conexion);
}
$totRegistro=0;
$totBase=0;
$totRet=0;

?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
     <table width="100%" border="0" align="center">
	 <tr align="center">
	  <td width="100%" valign="top" align="center">
      <?php
		   if (($optest) == "A")
		   {
				$estado = 'Activas'; 
		   } else 
		   {
				$estado = 'Anuladas';
		   }//Fin del if (($optest) == "A")
               
		$tip = isset($row_rs_cabcomp)?$row_rs_cabcomp['Tia_Ini']:'';
		$num = isset($row_rs_cabcomp)?$row_rs_cabcomp['Com_Num']:'';
		$titulo = "<strong><span class='TITULO_REPORTE_2'>RESUMEN DE RETENCIONES POR C&Oacute;DIGO</span></strong>";
		$subtitulo = "<strong><span class='TITULO_REPORTE'>Desde el ".$ini." Hasta el ".$fin." </span></strong>";
		 $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, $subtitulo, $obBD_conexion); ?></td>
    </tr>	
    <tr>
        <td valign="top">        
        <table id="Tbl_Codigos" width="100%" border="1" cellspacing="0" cellpadding="0" style="border-collapse:collapse; table-layout:fixed;">
          <tr class="Texto_normal_10">
            <td width="9%" height="24"><strong>&nbsp;# RETENC.</strong></td>
            <td width="8%"><strong>FECHA</strong></td>
            <td width="29%"><strong>PROVEEDOR</strong></td>
            <td width="30%"><strong>COMPRO. DE COMPRA</strong></td>
            <td width="8%"><strong>EMISI&Oacute;N</strong></td>
            <td width="8%"><strong>BASE</strong></td>
            <td width="8%"><strong>MONTO</strong></td>
          </tr>      
          <?php foreach($rs_DatosCodigo as $codigo){  	     
             if($codigo['Ren_Sri']!='332'){
                $rs_DatosCompra=$obBD_con1->getArrayConsulta(557,$Ses_Emp_Cod.'*'.$codigo['Ren_Cod'].'*'.$ini.'*'.$fin.'*'.$optest,$obBD_conexion);
             }else{
                $rs_DatosCompra=$obBD_con1->getArrayConsulta(559,$Ses_Emp_Cod.'*'.$ini.'*'.$fin.'*'.$optest,$obBD_conexion);			
             }//fin if($codigo['Ren_Sri']=='332')
             $rs_DatosCompra_total=count($rs_DatosCompra);
             ?>
          <tr>
            <td colspan="7" bgcolor="#FFFF99" class="Texto_normal_10">&nbsp; <strong><?php echo $codigo['Ren_Sri']." &nbsp;&nbsp;&nbsp;".$codigo['Ren_Con'];?></strong></td>
          </tr>
           <?php 
            if($rs_DatosCompra_total!=0){
               $totRegistro=$totRegistro+$rs_DatosCompra_total;
               $totBase=0;	   
               $totRet=0;
               
               foreach($rs_DatosCompra as $compra){
                $nomDocComp=isset($compra['Tic_Des'])?substr($compra['Tic_Des'],0,19):'';
                $totBase=$totBase+$compra['Ret_Bas'];	
                $totRet=$totRet+$compra['Ren_Ret'];
           ?>   
              <tr class="Texto_normal_9">
                <td align="center" style="mso-number-format:'@'"><?php if($compra['Ret_Num']!='-'){echo str_pad($compra['Ret_Num'], 9, "0", STR_PAD_LEFT);}else{echo "-";}?></td>
                <td align="center"><?php echo $compra['Ret_Fec'];?></td>
                <td style="white-space:nowrap;overflow:hidden;">&nbsp;<?php echo $compra['Prs_Ape'].' '.$compra['Prs_Nom'];?></td>
                <td style="white-space:nowrap;overflow:hidden;">&nbsp;<?php echo $nomDocComp.' &nbsp;&nbsp;'.$compra['Cop_Num'];?></td>
                <td align="center">&nbsp;<?php echo $compra['Cop_Fec'];?></td>
                <td align="right"><?php echo $compra['Ret_Bas'];?>&nbsp;</td>
                <td align="right"><?php echo $compra['Ren_Ret'];?>&nbsp;</td>
              </tr>
                   
          <?php }?>  
              <tr class="letra10">
                <td colspan="5" align="right" style="mso-number-format:'@'"><strong class="Texto_normal_10">Totales</strong>&nbsp;</td>
                <td align="right"><strong class="Texto_normal_10"><?php echo formato_numero($totBase,2,2);?></strong>&nbsp;</td>
                <td align="right"><strong class="Texto_normal_10"><?php echo formato_numero($totRet,2,2);?></strong>&nbsp;</td>
              </tr> 
          <?php	}else{
          ?>    
            <tr class="letra10">
                <td align="center">&nbsp;</td>
                <td align="center">&nbsp;</td>
                <td colspan="2" align="center" title="<?php echo isset($nomPro)?$nomPro:'';?>"><div class="Texto_normal_10"><?php $msg_mes = explode('-', $cmb_mes); echo " No hay resultados que mostrar para el codigo ".strtoupper($codigo['Ren_Sri']);
                 ?></div></td>
              <td align="center">&nbsp;</td>
                <td align="right">&nbsp;</td>
                <td align="right">&nbsp;</td>
              </tr>
          <?php }
          } ?>
        </table>        
      </td>
  </tr>
    <tr>
      <td align="center"><div align="center"><?Php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></div></td>
    </tr>
</table>	  
</BODY></HTML>
<?php 
@$obBD_con1->free_result($rs_buscar);
@$obBD_con1->free_result($rs_cliente);
@$obBD_con1->free_result($rs_ciudad);
@$obBD_con1->free_result($rs_buscarcarrera);
@$obBD_con1->free_result($rs_carrera);
@$obBD_con1->free_result($rs_detalle);
@$obBD_con1->free_result($rs_semestre);
@$obBD_con1->free_result($rs_vendedor);
$obBD_conexion->cerrar();
$obBD_con1->liberar();
?>