<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
* Descripcion: Permite imprimir el balance de comprobacion
* Fecha de actualizacion:	2012-10-09
* Desarrollador:	Lewis Chimarro 
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_estbalanc.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;

$cmb_sucursal = (isset($_POST['cmb_sucursal']) ? $_POST['cmb_sucursal'] : (isset($_GET['cmb_sucursal']) ? $_GET['cmb_sucursal'] : ''));
?>
<HTML>
	<HEAD>
		<TITLE><?php echo $Ses_Sys_Nom; ?></TITLE>
		<?php require_once("../../mascaras/model1/estilos/print.php"); ?>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <style type="text/css">
          .LetraNegra { font: normal 11px "Trebuchet MS", Arial, Helvetica, sans-serif; color: #000000; }
          .LetraPie { font:Verdana, Geneva, sans-serif; font-size: 8px; color:#333; }
          
          .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            margin-top: 10px;
          }
          .modern-table th, .modern-table td {
            border: 1px solid #000;
            border-bottom: 2px solid #e2e8f0;
            padding: 4px;
          }
          .modern-table thead th {
            background-color: #eee;
            text-align: center;
          }
          .modern-table .cell-number {
            text-align: right;
          }
          .modern-table .cell-code {
            text-align: left;
            white-space: nowrap;
          }
          .modern-table .cell-detail {
            text-align: center;
          }
          .modern-table .th-empty {
            border: none;
            border-bottom: 1px solid #000;
          }
          .modern-table .code-text {
            font-weight: bold;
          }
          .modern-table .row-totales td {
            font-weight: bold;
            background-color: #eee;
          }
          .th-left { text-align: left !important; }
          .th-center { text-align: center !important; }
        </style>        
	</HEAD>
  <BODY>
      <table width="590" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr class="Titulos3">
          <td width="472" height="10" align="center">
            <?php 	      
              $titulo = "<strong><span class='TITULO_REPORTE_2'>Balance de Sumas y Saldos</span></strong>";
              $sucursal_nombre = "";
              if ($cmb_sucursal != '') {
                $row_suc_info = $obBD_con1->getRowConsulta(126, $cmb_sucursal, $obBD_conexion);
                $sucursal_nombre = " - Sucursal: " . $row_suc_info['Suc_Des'];
              }
              $subtitulo = "<strong><span class='TITULO_REPORTE'>Desde el ".$txt_fec_ini." Hasta el ".$txt_fec_fin.$sucursal_nombre." </span></strong>";
              $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, $subtitulo, $obBD_conexion);
            ?>
          </td>
        </tr>
        <tr class="Titulos3">
          <td height="3" align="center">&nbsp;</td>
        </tr>
    </table>
    <table width="100%" border="0" cellpadding="0" cellspacing="0">   
      <tr>
        <td valign="top">
          <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr class="LetraNegra">
              <td colspan="4">
                <?php
                  /* Carga los nodos del plan de cuentas */
                  $obBD_con1->cargarNodosComprobacion($Pla_Cod,0, $txt_fec_ini, $txt_fec_fin, $obBD_conexion, 3, $Pec_Cod, 0, $utilidad, 0, $Max_Niv, 2, $cmb_sucursal); 
                ?>
              </td>
            </tr>
    
            <tr class="LetraNegra">
              <td colspan="4">
                <table width="100%" border="0" align="center" cellpadding="2" cellspacing="0" class="Texto_Reporte">
                  <tr>
                    <td valign="top" align="left">
                      <?php 
                        $obBD_con1->fechaImpresion($Ses_Suc_Cod, $obBD_conexion); 
                        $infoFirmas=$obBD_con1->getRowConsulta(5,$Ses_Emp_Cod,$obBD_conexion);
                      ?>
                    </td>
                    <td align="center" valign="top">&nbsp;</td>
                  </tr>
                  <tr>
                    <td valign="top" align="center">&nbsp;</td>
                    <td align="center" valign="top">&nbsp;</td>
                  </tr>
                  <tr>
                    <td valign="top" align="center">&nbsp;</td>
                    <td align="center" valign="top">&nbsp;</td>
                  </tr>
                  <tr>
                    <td valign="top" align="left">__________________</td>
                    <td align="left" valign="top">__________________<br /></td>
                  </tr>
                  <tr>
                    <td height="9" align="left" valign="top">GERENTE
                      <p></p>
                      <p style="margin:-1.5% 0;"><?php echo $infoFirmas['Emp_Ren'];?></p>
                      <p></p>
                      <p style="margin:-1.5% 0;">CI:&nbsp;<?php echo $infoFirmas['Emp_Rre'];?></p>
                    </td>
                    <td align="left" valign="top">CONTADOR
                      <p></p>
                      <p style="margin:-1.5% 0;"><?php echo $infoFirmas['Emp_Con'];?></p>
                      <p></p>
                      <p style="margin:-1.5% 0;">RUC:&nbsp;<?php echo $infoFirmas['Emp_Rco'];?></p>
                    </td>
                  </tr>
                  <tr>
                    <td height="10" colspan="2" align="left" valign="top"><hr /></td>
                  </tr>
                  <tr>
                    <td height="10" align="left" valign="top"><span class="LetraPie">CORPROINFO - OFSERCONT - EXA SISTEMA CONTABLE</span></td>
                    <td align="left" valign="top">&nbsp;</td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>	  
  </BODY>
</HTML>
<?php 
  @$obBD_con1->free_result($rs_empresa);
  @$obBD_con1->free_result($rs_cuenta_manual);
  @$obBD_con1->liberar();
  $obBD_conexion->cerrar();
?>