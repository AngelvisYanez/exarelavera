<?php 
/**
* @abstract Reporte de ajuste de productos
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualizaci�n: 2012-07-08
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_aju.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;
	 	 
if (isset($Aju_Cod))
{	
	/**
	* Consulta datos de los clientes
	*/
	$row_rs_cliente = $obBD_con1->getRowConsulta(1063, $Aju_Cod, $obBD_conexion);
	
	/**
	* Consulta de los tipos de pago 
	*/
	$row_rs_detalle = $obBD_con1->getArrayConsulta(1064, $Aju_Cod, $obBD_conexion);

	/**
	* Consulta del vendedor en base al codigo de la persona
	*/
	$row_rs_vendedor = $obBD_con1->getRowConsulta(1066, $row_rs_cliente['Vnd_Cod'], $obBD_conexion);

	list($ann, $mes, $dia) = explode('-', $row_rs_cliente['Aju_Fec']); 
        $resultados=0;
}

?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
</head>
<body>
<table width="100%"  height="100%" border="0" align="left" cellpadding="0" cellspacing="0">
      <td width="100%"  height="178" colspan="4" valign="top"><table width="100%" border="0" align="left">
      <tr align="center">
        <td  class="Texto_Reporte" align="center"><table width="80%" border="0" cellpadding="0" cellspacing="0" align="center">
          <tr align="center">
            <td colspan="5" valign="top">&nbsp;
              <?php
			   if($row_rs_cliente['Tia_Tra']=='I')
				{
					$etiqueta = ' INGRESO';
				}
				elseif($row_rs_cliente['Tia_Tra']=='E')
				{	
					$etiqueta = ' EGRESO';
				}
		$num = $row_rs_cliente['Aju_Sec'];
		$titulo = "<span class='TITULO_REPORTE_2'>Comprobante de $etiqueta N</span><span class='TITULO_REPORTE'>o</span><span class='TITULO_REPORTE_2'> $num</span>";
		$subtitulo = "<span class='TITULO_REPORTE_2'>".$row_rs_cliente['Tia_Des']."</samp>";
		$obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, $subtitulo, $obBD_conexion); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr align="center">
        <td class="Texto_Reporte"><table width="100%" border="0" bgcolor="" cellpadding="0" cellspacing="0">
          <tr>
            <td>
    <table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
	  <tr align="center">
	    <td width="81" height="15" class="Texto_Reporte"><strong>NOMBRE:&nbsp;</strong></td>
	    <td height="15" align="left" valign="bottom" class="Texto_Reporte">
							<?php echo $row_rs_cliente['Prs_Nom'].' '.$row_rs_cliente['Prs_Ape'];
		    ?></td>
	    <td width="52" align="left" valign="bottom" class="Texto_Reporte"><strong>FECHA:&nbsp;</strong></td>
	    <td width="154" height="15" align="left" valign="bottom" class="Texto_Reporte"><?php echo $row_rs_cliente['Aju_Fec']; ?></td>
	  </tr>
	  <tr align="center">
        <td   class="Texto_Reporte"><strong>CI/RUC:&nbsp;</strong></td>
        <td width="446" align="left" valign="bottom" class="Texto_Reporte"><?php echo $row_rs_cliente['Prs_Ced'];  ?></td>
        <td align="left" class="Texto_Reporte"><strong>&nbsp;</strong></td>
        <td align="left" class="Texto_Reporte">&nbsp;</td>
        </tr>
      <tr align="center">
        <td align="left" class="Texto_Reporte"><strong>DIRECCI&Oacute;N:&nbsp;</strong></td>
        <td align="left" class="Texto_Reporte"><?php echo $row_rs_cliente['Prs_Dir'];  ?></td>
        <td align="left" class="Texto_Reporte">&nbsp;</td>
        <td align="left" class="Texto_Reporte">&nbsp;</td>
      </tr>
      <tr align="center">
        <td align="left"  class="Texto_Reporte"><strong>DETALLE:&nbsp;</strong></td>
        <td colspan="3" align="left" class="Texto_Reporte"><?php echo $row_rs_cliente['Aju_Det'];  ?></td>
        </tr>
      <tr align="center">
        <td align="left"  class="Texto_Reporte"><strong>CONCEPTO:&nbsp;</strong></td>
        <td colspan="3" align="left" class="Texto_Reporte"><?php echo $row_rs_cliente['Aju_Obs'];  ?></td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td align="center"><table width="100%" border="0" align="left" cellpadding="2" cellspacing="0">
              <tr>
                <td colspan="2" valign="top" align="center"><br>
     <table width="80%" border="1" style="border-collapse:collapse" cellpadding="0" cellspacing="0" align="center">
                    <tr>
                      <th width="113"  bgcolor="#CCCCCC" class="TITULO_REPORTE">Cant.</th>
                      <th width="419"  bgcolor="#CCCCCC" class="TITULO_REPORTE">Item</th>
                      <th width="113" align="center"  bgcolor="#CCCCCC" class="TITULO_REPORTE">Precio</th>
                      <th width="111" align="center"  bgcolor="#CCCCCC" class="TITULO_REPORTE">Importe</th>
                    </tr>
                    <?Php
					foreach ($row_rs_detalle as $row)
					{
					?>
                        <tr class="Fondo">
                            <td width="113" class="Texto_Reporte" align="center"><div align="right"><?Php echo $row['Aju_Can']?></div></td>
                            <td width="419" class="Texto_Reporte"><?Php echo $row['Ite_Lar']?></td>
                            <td width="113" align="right" class="Texto_Reporte"><div align="right"><?Php echo formato_numero($row['Aju_Pru'], 6, 2); ?></div></td>
                            <td width="111" align="right" class="Texto_Reporte"><div align="right"><?Php echo formato_numero($row['Aju_Imp'], 2, 2); 
                            $resultados=$resultados+$row['Aju_Imp'];	?></div></td>
                        </tr>                    			
                    <?Php 	
					}//Fin del foreach ($row as $row)
					?>
					<tr>
                      <td class="Texto_Reporte">&nbsp;</td>
                      <td class="Texto_Reporte">&nbsp;</td>
                      <td class="Texto_Reporte" align="center"><span class="Nro_Fac_Com">Total</span></td>
                      <td align="right" class="Texto_Reporte"><span class="Nro_Fac_Com"><div align="right"><?Php echo formato_numero($resultados, 2, 1); ?></div></span></td>
                  	</tr>
                </table>
                </td>
              </tr>
              
            </table></td>
          </tr>
          <tr>
            <td height="102"><table width="100%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="50%" align="center" class="Texto_bloques"><br><br>
                  <div align="center">__________________________</div></td>
                <td width="50%" align="center" class="Texto_bloques"><br><br>
                <div align="center">__________________________</div>
                  <div align="center"></div></td>
              </tr>
              <tr>
                <td class="Texto_bloques"><div align="center">Elaborado por </div></td>
                <td class="Texto_bloques"><div align="center">Recib&iacute; conforme.</div>                  </td>
              </tr>
              <tr>
                <td class="Texto_bloques"><div align="center" ><?Php echo $row_rs_vendedor['Prs_Nom'].' '.$row_rs_vendedor['Prs_Ape']; ?></div></td>
                <td class="Texto_bloques"><div align="center"><?php echo $row_rs_cliente['Prs_Nom'].' '.$row_rs_cliente['Prs_Ape'];
		    ?></div></td>
              </tr>
              <tr>
                <td class="Texto_bloques">&nbsp;</td>
                <td class="Texto_bloques">&nbsp;</td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table>
</table>
  </tr>
</table>
</body>
</html>
<?Php
/**
* Cierra la conexion 
*/
@$obBD_conexion->cerrar();
?>