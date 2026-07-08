<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php 
  /**
	* pagina de listado de precios para imprimir (tes_pri_producto_1.0.php) :)
	* @author Jose Cumbicos
	* Ultima Actualizacion: 28-05-2014
	* Permite visualizar los datos de productos y su visualizacion de imprecion
	* @package tesoreria
	*/
	
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/cch_log_reposicion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* objeto conexion */
$obBD_conexion = new Class_Log_Conexion_Cch($Ses_Dat_Dis);
/* objeto para extraer datos */
$obBD_con1 =  new Class_Log_Datos_Cch;

$rs_busCajaCh = $obBD_con1->getRowConsulta(1, $Ses_Emp_Cod, $obBD_conexion);
if($opn=='C'){
	$rs_datosRepos = $obBD_con1->getRowConsulta(28, $Rep_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
}else{
	$rs_datosRepos = $obBD_con1->getRowConsulta(34, $Rep_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
}
$anio=explode("-",$rs_datosRepos['Rep_Fec']);
/**
*   Variables para Encabezado
*/
$Titulo="REPOSICI&Oacute;N DE CAJA CHICA # ".$rs_datosRepos['Rep_Num'].'-'.$anio[0];
$Subtitulo="";
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body class="Cuerpo">
<?php /* Consulta de la cabecera del reporte */
	$row_institucion= $obBD_con1->getRowConsulta(15, $Ses_Suc_Cod, $obBD_conexion);//GetRowConsulta(5,$Ses_Cod_Suc);
?>
<table width="100%" height="897" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td height="48" valign="top"><table width="100%" height="18" border="0" cellpadding="0" cellspacing="0">
      <tr align="center">&nbsp;
        <td height="18" align="center" ><?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod,$Titulo,$Subtitulo,$obBD_conexion)?></td>
      </tr>
    </table></td>
  </tr>
  <tr valign="top">
    <td valign="top"><table width="100%" height="233"  border="0" cellpadding="0" cellspacing="0">
      <tr class="Texto_normal_10">
        <td height="13" align="left" class="Texto_normal_10">&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td width="35%">&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <?php if($opn == 'C'){?>
	  <tr class="Texto_normal_10">
        <td height="13" align="left" class="Texto_normal_10"><strong>NUMERO DE CHEQUE:</strong></td>
        <td><?php echo $rs_datosRepos['Che_Num'];?></td>
        <td align="right"><strong>BANCO:</strong></td>
        <td>&nbsp;&nbsp;<?php echo strtoupper($rs_datosRepos['Pld_Des']);?></td>
        <td>&nbsp;</td>
      </tr>
	  <?php } ?>
      <tr class="Texto_normal_10">
        <td width="11%" height="13" align="right"><strong>MONTO DE CAJA CHICA:</strong></td>
        <td width="7%" align="left" >&nbsp;<?php echo $rs_busCajaCh['Cch_Val'];?></td>
		<?php if($opn=='C'){?>
        <td width="10%" align="right"><strong>CHEQUE EMITIDO A:</strong></td>
        <td>&nbsp;&nbsp;<?php echo $rs_datosRepos['Prs_Ape'].' '.$rs_datosRepos['Prs_Nom'];?></td>
        <?php }?>
        <td width="37%">&nbsp;</td>
      </tr>
      <tr>
        <td height="29" colspan="5" valign="top"><div align="center">		        
          <table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
            <thead>
              <tr class="TITULO_REPORTE">
                <th colspan="5" bgcolor="#CCCCCC">EGRESOS</th>
                </tr>
              <tr class="Texto_normal_10">
                <th width="10%" align="center" bgcolor="#CCCCCC">FECHA</th>
                <th width="38%" bgcolor="#CCCCCC">&nbsp;PROVEEDOR</th>
                <th width="21%" align="center" bgcolor="#CCCCCC">TIPO</th>
                <th width="18%" align="center" bgcolor="#CCCCCC">DOCUMENTO</th>
                <th width="13%" align="right" bgcolor="#CCCCCC">VALOR&nbsp;</th>
                </tr>
              </thead>
            <tbody>
              <tr class="Texto_Reporte">
                <td colspan="5" class="LetraNegra"><strong><span class="Texto_normal_10">GASTOS DEDUCIBLES</span></strong></td>
                </tr>
              <?php 

		  $sumaGen=0;
		  /*compras con retenciones NO ASUMIDAS*/
		  $comDeducibles= $obBD_con1->getArrayConsulta(20, $Ses_Emp_Cod.'*'.$Rep_Cod.'*'.'N', $obBD_conexion);
		  $totDeduc=count($comDeducibles);
		  if ($totDeduc!=0){
			  foreach($comDeducibles as $row){ ?>
              <tr class="Texto_normal_10">
                <td align="center" class="LetraNegra">&nbsp;<?php echo $row['Cop_Fec'];?></td>
                <td class="LetraNegra">&nbsp;<?php echo $row['provee'];?></td>
                <td align="center" class="LetraNegra">&nbsp;<?php echo $row['Tic_Des'];?></td>
                <td align="center" class="LetraNegra">&nbsp;<?php echo $row['Cop_Num'];?></td>
                <td class="LetraNegra" align="right">
                  <!-- <?php if($row['asu']=='N'){
                      echo $row['total']-$row['ret'];
                      $sumaGen+=$row['total']-$row['ret'];
                    }else{
                      echo $row['total'];
                      $sumaGen+=$row['total'];
                    }
                  ?>&nbsp; -->
                  <?php
                    echo formato_numero($row['total'], 2, 1);
                    $sumaGen += $row['total'];
                  ?>&nbsp;
                  </td>
                </tr>
              <?php }
		  }else{
			?>
              <tr class="Texto_Reporte">
                <td>&nbsp;</td> 
                <td class="Texto_Listados">No hay registros</td> 
                <td>&nbsp;</td> 
                <td>&nbsp;</td> 
                <td>&nbsp;</td> 
                </tr>
              <?php }?>
              <tr class="Texto_Reporte">
                <td colspan="5" class="Texto_normal_10"><strong>GASTOS DEDUCIBLES</strong></td>
                </tr><?php
          /*compras con retenciones ASUMIDAS*/
		  $comNoDeducibles= $obBD_con1->getArrayConsulta(20, $Ses_Emp_Cod.'*'.$Rep_Cod.'*'.'S', $obBD_conexion);
		  $totNoDeduc=count($comNoDeducibles);
		  if ($totNoDeduc!=0){
			  foreach($comNoDeducibles as $row){ ?>
              <tr class="Texto_normal_10">
                <td align="center" class="LetraNegra">&nbsp;<?php echo $row['Cop_Fec'];?></td>
                <td class="LetraNegra">&nbsp;<?php echo $row['provee'];?></td>
                <td align="center" class="LetraNegra">&nbsp;<?php echo $row['Tic_Des'];?></td>
                <td align="center" class="LetraNegra">&nbsp;<?php echo $row['Cop_Num'];?></td>
                <td class="LetraNegra" align="right">&nbsp;
                  <!-- <?php if($row['asu']=='S'){
                    echo formato_numero($row['total'],2,1);
                    $sumaGen+=$row['total'];
                  }else{
                    echo formato_numero($row['total']-$row['ret'],2,1);
                    $sumaGen+=$row['total']-$row['ret'];
                  }
                  ?>&nbsp; -->
                  <?php
                    echo formato_numero($row['total'], 2, 1);
                    $sumaGen += $row['total'];
                  ?>&nbsp;
                  </td>
                </tr>
              <?php }
		  }else{
			?>
              <tr class="Texto_Reporte">
                <td>&nbsp;</td> 
                <td><span class="Texto_normal_9">No hay registros</span></td> 
                <td>&nbsp;</td> 
                <td>&nbsp;</td> 
                <td>&nbsp;</td> 
                </tr>
              <?php }?>
              <tr class="Texto_normal_10">
                <td colspan="4" align="right" ><strong>REPOSICI&Oacute;N TOTAL:</strong></td> 
                <td align="right"><strong><?php echo $sumaGen;?></strong>&nbsp;</td> 
                </tr>
              <tr class="Texto_normal_10">
                <td colspan="4" align="right" ><strong>SALDO DE CAJA:</strong></td> 
                <td align="right"><strong><?php echo $rs_busCajaCh['Cch_Val']-$sumaGen;?></strong>&nbsp;</td> 
                </tr>
              </tbody>
            </table>
          <br>
        </div></td>
        </tr>
      <tr>
        <td height="29" colspan="5" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
          </tr>
          <tr>
            <td align="center">____________________</td>
            <td align="center">____________________</td>
            <td align="center">____________________</td>
          </tr>
          <tr class="Texto_normal_10">
            <td align="center">ELABORADO POR:</td>
            <td align="center">REVISADO POR:</td>
            <td align="center">APROBADO POR:</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="19" colspan="5" align="center"><?php $obBD_con1->pieReporteStandar($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion)?></td>
      </tr>
  </table>
    
	</td>
  </tr>
</table>

</body>
</html>
<?php 
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>