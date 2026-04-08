<?
/*
* Descripci�n: Reporte agupado por tipoa de pago: Efectivo, Cheque, Tarjeta... etc
* Fecha de actualizaci�n: 2016-1028
* Desarrollador: Jose Cumbicos
*/	
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_aper_caja.php');	  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');		

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;	 	 	 
$optest= "A";
$row_ventas=$obBD_con1->getArrayConsulta(28, $Caj_Cod, $obBD_conexion);
$total_ventas=count($row_ventas);



$row_retenciones=$obBD_con1->getArrayConsulta(33, $Caj_Cod, $obBD_conexion);
$total_retenciones=count($row_retenciones);
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
		$tip = $row_rs_cabcomp['Tia_Ini'];
		$num = $row_rs_cabcomp['Com_Num'];
		$titulo = "<strong><span class='TITULO_REPORTE_2'>Resumen de cierre de caja</span></strong>";
		$subtitulo = "<strong><span class='TITULO_REPORTE'>Agrupados por tipo de pago</span></strong>";
		 $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, $subtitulo, $obBD_conexion); ?></td>
    </tr>
    <tr>
    	<td align="left"><br>
            <table width="647" border="0" cellpadding="0" cellspacing="0" >
            <tr>
              <td width="132" align="left" class="Texto_Reporte"><strong>Fecha de Apertura:</strong></td>
              <td width="236" class="Texto_Reporte">&nbsp;<?Php echo $row_ventas[0]['Caj_Fec'].' '.$row_ventas[0]['Caj_Hoi']; ?></td>
              <td width="139" class="Texto_Reporte"><strong>Caja:</strong></td>
              <td width="140" class="Texto_Reporte"><? echo $row_ventas[0]['Pun_Des'];?></td>
            </tr>
            <tr>
              <td width="132" class="Texto_Reporte"><strong>Fecha de Cierre:</strong></td>
              <td width="236" class="Texto_Reporte">&nbsp;<?Php echo $row_ventas[0]['Caj_Fef'].' '.$row_ventas[0]['Caj_Hof']; ?></td>
              <td width="139" class="Texto_Reporte"><strong>Estado de la Caja:</strong></td>
              <td width="140" class="Texto_Reporte">&nbsp;<?Php echo $row_ventas[0]['Caj_Est'];?></td>
            </tr>
            </table>
        </td>
    </tr>	
    <tr>
      <td valign="top"><?Php
	
			$resultados_total = explode('*',$obBD_con1->calculosConsultaVentas($Caj_Cod, $optest, $obBD_conexion));
	

			?>
		<table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
				  <tr class="TablaRepCompr">
					<td width="46%" align="left" bgcolor="#CCCCCC" class="TablaRepCompr">&nbsp;TIPO DE PAGO</td>
                    <td width="9%" align="center" bgcolor="#CCCCCC" class="TablaRepCompr"># VENTAS</td>
					<td width="9%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">SUB 0%</td>
					<td width="9%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">SUB IVA</td>
					<td width="9%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">DESCUENTO</td>
                    <td width="9%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">IVA</td>
					<td width="9%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">TOTAL</td>
				  </tr>
				  <?php
				  if($total_ventas!=0){
            
					  foreach($row_ventas as $datos){
					  ?>
					  <tr class="Texto_Reporte">					
						<td align="left">&nbsp; <?php echo strtoupper($datos['Pag_Des']);  ?></td>                                       
						<td align="center"><?Php echo $datos['conteo'];?></td>
						<td align="right"><?Php echo formato_numero($datos['Sub0'],2,2); ?></td>
						<td align="right"><?Php echo formato_numero($datos['SubIva'],2,2); ?></td>
						<td align="right"><?Php echo formato_numero($datos['Descuento'] ,2,2); ?></td>
						<td align="right"><?Php echo formato_numero($datos['Iva'] ,2,2); ?></td>

          
            <td align="right"><?Php echo formato_numero($datos['total'],2,2  ); ?></td>
						<!--td align="right"><?Php echo formato_numero(($datos['Sub0'] + $datos['SubIva']+ $datos['Iva'])-$datos['Descuento'],2,2); ?></td-->
					
          
          </tr>				 
					  <?Php }
				  }else{
					  ?>
                   <tr class="Texto_Reporte">
				    <td align="left">-</td>
				    <td align="center">-</td>
				    <td align="right">0.00</td>
				    <td align="right">0.00</td>
				    <td align="right">0.00</td>
				    <td align="right">0.00</td>
				    <td align="right">0.00</td>
			      </tr>
                  <? }?>


         <!-- retenciones en agregadas a las ventas en la fecha de inicio de caja -->

          <?php
           $totalRetenciones = 0;
           if($total_retenciones!=0){
            foreach($row_retenciones as $datos){
          ?>
            <tr class="Texto_Reporte">          
            <td align="left">&nbsp; <?php echo "RETENCIONES"?></td>                                       
            <td align="center"><?Php echo $datos['Cantidad'];?></td>
            <td align="right"><?Php echo '-'; ?></td>
            <td align="right"><?Php echo '-'; ?></td>
            <td align="right"><?Php '-'; ?></td>
            <td align="right"><?Php '-'; ?></td>
            <td align="right"><?Php echo formato_numero($datos['Total'],2,2); ?></td>
            </tr>        
            
          <?Php
              $totalRetenciones += $datos['Total'];
              }
            }
          ?>


			    </table>
	    <table width="100%"  border="0" cellpadding="0" cellspacing="0">
          <tr class="Texto_Reporte">
            <td width="84%">                
            <td width="11%"><strong>Subtotal:
            </strong>
            <td width="5%" align="right"><?php echo formato_numero($resultados_total[0],2,2); ?></td>
          </tr>
          <tr class="Texto_Reporte">
            <td>&nbsp;</td>
            <td><strong>Sub. 0%:</strong></td>
            <td align="right"><?Php echo formato_numero($resultados_total[1],2,2); ?></td>
          </tr>
          <tr class="Texto_Reporte">
            <td>&nbsp;</td>
            <td><strong>Sub Iva: </strong></td>
            <td align="right"><?Php echo formato_numero($resultados_total[2],2,2); ?></td>
          </tr>
          <tr class="Texto_Reporte">
            <td>&nbsp;</td>
            <td><strong>IVA:</strong></td>
            <td align="right"><?Php echo formato_numero($resultados_total[3],2,2); ?></td>
          </tr>
          <tr class="Texto_Reporte">
            <td>&nbsp;</td>
            <td><strong>Descuento:</strong></td>
            <td align="right"><?Php echo formato_numero($resultados_total[4],2,2); ?></td>
          </tr>
          <tr class="Texto_Reporte">
            <td>&nbsp;</td>
            <td><strong>Monto inicial caja:</strong></td>
            <td align="right"><? echo formato_numero($row_ventas[0]['Caj_Exi']+0,2,2);?></td>
          </tr>

          <tr class="Texto_Reporte">
            <td>&nbsp;</td>
            <td><strong>Retenciones:</strong></td>
            <td align="right"><? echo formato_numero($totalRetenciones,2,2);?></td>
          </tr>

          <tr class="Texto_Reporte">
            <td>&nbsp;</td>
            <td><strong>Total:</strong></td>
            <td align="right"><?php echo formato_numero($resultados_total[5]+$row_ventas[0]['Caj_Exi']-$totalRetenciones,2,2); ?></td>
          </tr>
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