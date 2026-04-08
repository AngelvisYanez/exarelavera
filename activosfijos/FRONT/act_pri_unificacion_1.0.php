<?php 
/** 
 *Alias:	Insetar
 *Descripción: Permite el ingreso del detalle de los tipos de activos
 *Desarrollador:	Fabian Gallardo
 *Fecha de actualización:	2011-04-21
 ***********************************
 *Desarrollador:	Fabian Gallardo
 *Fecha de actualización:	2011-11-08 
 ************************************
 *Desarrollador:	Didimo Zamora
 *Fecha de actualización:	2013-05-27
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_campos_det.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once("../../mascaras/model1/estilos/estilos.php"); 
	 
/**
 * Creacion del Objeto de conexion 
 */
  $obBD_conexion = new Class_Log_Conexion_Con;
/**
 * Cracion del objeto mysql para las consultas 
 */
  $obBD_con1 =  new Class_Log_Datos_Con;
/***********************************************/
$hoy = date("Y-m-d");
	  
/**
 * Consulta las Sucursales 
 */
$rs_suc_act = $obBD_con1->getArrayConsulta(422,$Ses_Emp_Cod,$obBD_conexion);
$total_rs_suc_act =  count($rs_suc_act);

/**
 * Consulta los Estados 
 */
$rs_est_act = $obBD_con1->getArrayConsulta(423,'', $obBD_conexion);
$total_rs_est_act =  count($rs_est_act);
	  
		/**
		 * Consulta de la cabecera del reporte 
		 */
		$rs_institucion = $obBD_con1->getRowConsulta(134, $Ses_Suc_Cod, $obBD_conexion);
		$total_rs_institucion =  count($rs_institucion);
		$row_rs_institucion = $rs_institucion;

?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
<!--
.Estilo1 {font-size: 16px}
.Estilo2 {font-size: 18px}
-->
</style>
</head>
<body class="Cuerpo" marginheight="50%">

	    
<div class="paginado" >
   		  <table width="80%" border="0" cellpadding="0" cellspacing="0" align="center">
   		    <tr align="center">
   		      <td width="9%" rowspan="5" valign="top"><img src="<?php echo $row_rs_institucion['Emp_Log']; ?>" alt="" width="83" height="67"></td>
   		      <td colspan="4" class="TITULO_REPORTE Estilo1"><?Php echo $row_rs_institucion['Emp_Nom'].' - '.$row_rs_institucion['Suc_Des']; ?></td>
	        </tr>
   		    <tr align="center">
   		      <td width="26%"valign="top" class="Texto_Reporte"><div align="right"><strong>R.U.C.:</strong></div></td>
   		      <td width="24%" align="left" valign="top" class="Texto_Reporte"><div align="left"> &nbsp;<?php echo $row_rs_institucion['Emp_Ruc']; ?></div></td>
   		      <td width="10%" align="left" valign="top" class="Texto_Reporte"><div align="right"><strong>TELEFONO:</strong></div></td>
   		      <td width="31%" align="left" valign="top" class="Texto_Reporte"><div align="left">&nbsp;<?php echo $row_rs_institucion['Suc_Te1']; ?></div></td>
	        </tr>
   		    <tr align="center">
   		      <td align="left" valign="top" class="Texto_Reporte"><div align="right"><strong>DIRECCION:</strong></div></td>
   		      <td colspan="3" align="left" valign="top" class="Texto_Reporte"><div align="left"> &nbsp;<?php echo $row_rs_institucion['Suc_Dir']; ?></div></td>
	        </tr>
   		    <tr align="center">
   		      <td align="left" valign="top" class="Texto_Reporte"><div align="right"><strong>E-MAIL:</strong></div></td>
   		      <td colspan="3" align="left" valign="top" class="Texto_Reporte"><div align="left"> &nbsp;<?php echo $row_rs_institucion['Suc_Cor']; ?></div></td>
	        </tr>
   		    <tr align="center">
   		      <td colspan="4" align="center" valign="top" class="Texto_Reporte"><div align="center"><?Php echo $row_rs_institucion['Ciu_Des']." - EL ORO - ECUADOR";?></div></td>
	        </tr>
            <tr align="center">
        <td colspan="5" valign="top"><HR></td>
      </tr>
      <tr align="center">
        <td colspan="5" valign="top" class="Texto_Reporte"><strong><div align="center">UNIFICACION DE TOTALES POR DEPARTAMENTOS</div></strong></td>
        </tr>
        <tr align="right" >
        <td colspan="5" valign="top" align="right" class="Texto_Reporte"><span>Fecha de impresi&oacute;n: <? echo $hoy;?></span></td>
        </tr>
	      </table>
   <p>
      <?
					$rs_dep = $obBD_con1->getArrayConsulta(445,'', $obBD_conexion);
					$total_rs_dep = count($rs_dep);
					
					$act_val = 0;
					$act_res = 0;
					
				
                  ?>
  </p>
  <table width="80%" border="1" cellpadding="0" cellspacing="0" align="center">
<tr class="Cabecera1">
		  <td class="Texto_Reporte"><div align="center">Departamento</div></td>
          <td class="Texto_Reporte"><div align="center">Valor Actual</div></td>
		  <td class="Texto_Reporte"><div align="center">Valor Residual</div></td>
      </tr>
	  
	  <?Php 
	  if ($total_rs_dep > 0){  		
	  //do {
		  foreach($rs_dep as $row_rs_dep){
	  ?>
	  <tr class="Fondo">
	 	 <td class="Texto_Reporte" ><?php echo strtoupper($row_rs_dep['Dep_Des']);?></td>
	  	 <td class="Texto_Reporte"><div align="right"><?Php echo formato_numero($row_rs_dep['Act_Val'],2,2); $act_val += $row_rs_dep['Act_Val'];?></div></td>
		 <td  class="Texto_Reporte"><div align="right"><?Php echo formato_numero($row_rs_dep['Act_Res'],2,2); $act_res += $row_rs_dep['Act_Res'];?></div></td>
	  </tr>
	  <?Php } //Fin foreach($row_rs_dep as $rs_dep) 
	  ?>
      <tr class="Fondo">
		  <td class="Texto_Reporte"  align="right">TOTAL :</td>
          <td class="Texto_Reporte"><div align="right"><b><? echo formato_numero($act_val,2,2);?></b></div></td>
		  <td class="Texto_Reporte"><div align="right"><b><? echo formato_numero($act_res,2,2);?></b></div></td>
      </tr>
      <?
  	  }else{
  	  ?>
      	<tr>
            <td></td>
            <td></td>
            <td><div align="right"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></div></td>
        </tr>
      <? } // fin del if ($total_rs_buscar > 0)?>
	</table>
    						
<br><br>
               <table width="100%" border="0" align="center" cellpadding="2" cellspacing="0" class="Texto_Reporte">
                <tr>
                  <td valign="top" align="center">&nbsp;</td>
                  <td valign="top" align="center">&nbsp;</td>
                  <td valign="top" align="center">&nbsp;</td>
                  </tr>
                <tr>
                  <td valign="top" align="center">&nbsp;</td>
                  <td valign="top" align="center">&nbsp;</td>
                  <td valign="top" align="center">&nbsp;</td>
                  </tr>
                <tr>
            <td valign="top" align="center">__________________<br>    </td>
            <td valign="top" align="center">__________________<br>    </td>
            <td valign="top" align="center">__________________<br>    </td>
            </tr>
                <tr>
                  <td height="19" align="center" valign="top"><p>ELABORADO POR</p>
                  <p></p></td>
                  <td align="center" valign="top"><p>REVISADO POR</p>
                  <p> </p></td>
                  <td align="center" valign="top"><p>APROBADO POR</p>
                  <p></p></td>
                  </tr>
            </table>                    
  </div>
	
</body>
</html>
<?Php 

$obBD_conexion->cerrar();
?>