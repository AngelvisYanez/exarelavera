<?php 
/**
 * Alias:	codigo barras activos
 * Descripciï¿½n: Reporte de codigo de barras para los Activos por departamento
 * Fecha de actualizaciï¿½n:	2011-08-2
 * Desarrollador:	Didimo Zamora
 * Fecha de actualización: 04/05/2013--------19-07-2013
 * MULTIEMPRESA : 
*/	

require_once '../../administrador/LOGICA/seguridad.php';
require_once('../LOGICA/act_log_campos_det.php');
require_once '../../Librerias/procedimientos/almacenados_standar.php';	

/**
 * Creacion del Objeto de conexion 
 */  
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
 * Creacion del Objeto de datos 
 */  
$obBD_con1 =  new Class_Log_Datos_Con; 



?>

<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
<table width="100%"  height="232" border="0" align="center">
  <tr>
    <td height="78" align="center" valign="top"><br>
	<table width="516" border="0" align="center">
      <tr>
        <td valign="top" align="center">
	<? 			
		if($tipo=="1"){
			
		/**
		 * consulto todos los registros
		 */
		$rs_ite_pro1= $obBD_con1->getArrayConsulta(637,$cadena,$obBD_conexion);
		$total_rs_ite_pro1 =  count($rs_ite_pro1);
	
    	/** 
		 * consulto todos los registros paginados
		 */
		$rs_ite_pro= $obBD_con1->getArrayConsulta(638,$cadena.'*'.$inicio.'*'.$registros,$obBD_conexion);
		$total_rs_ite_pro =  count($rs_ite_pro);
		
		$resultados = $rs_ite_pro;
		$total_registros =$total_rs_ite_pro1; 
		$indicadorfila=0;
		
		/**
		 * Consulto  el departamento del custodio.
		 */
		 $rs_DeparCust=$obBD_con1->getRowConsulta(1000,$cadena,$obBD_conexion);
		 $rs_totolDep=count($rs_DeparCust);
		
		}
		else
		{		
			//Si es por  Descripción del Activo
			if($op_opciones =="d"){
				/**
				 * consulto todos los registros
				 */
				$rs_ite_pro1= $obBD_con1->getArrayConsulta(1002,$txt_busqueda2.'*'.$Ses_Emp_Cod,$obBD_conexion);
				$total_rs_ite_pro1 =  count($rs_ite_pro1);
		
				/** 
				 * consulto todos los registros paginados
				 */
				$rs_ite_pro= $obBD_con1->getArrayConsulta(1003,$txt_busqueda2.'*'.$inicio.'*'.$registros.'*'.$Ses_Emp_Cod,$obBD_conexion);
				$total_rs_ite_pro =  count($rs_ite_pro);
			}
			if($op_opciones =="ta")// Si es po el Tipo de Activo
			{
				
				 /**
				 * consulto todos los registros
				 */
				$rs_ite_pro1= $obBD_con1->getArrayConsulta(1004,$Tia_Cod.'*'.$Ses_Emp_Cod,$obBD_conexion);
				$total_rs_ite_pro1 =  count($rs_ite_pro1);
		
				/** 
				 * consulto todos los registros paginados
				 */
				$rs_ite_pro= $obBD_con1->getArrayConsulta(1005,$Tia_Cod.'*'.$inicio.'*'.$registros.'*'.$Ses_Emp_Cod,$obBD_conexion);
				$total_rs_ite_pro =  count($rs_ite_pro);				
			}	
			
			if($op_opciones =="cs")// Si es po el Tipo de Activo
			{				
				 /**
				 * consulto todos los registros
				 */
				$rs_ite_pro1= $obBD_con1->getArrayConsulta(1008,$txt_busqueda2.'*'.$Ses_Emp_Cod,$obBD_conexion);
				$total_rs_ite_pro1 =  count($rs_ite_pro1);
		
				/** 
				 * consulto todos los registros paginados
				 */
				$rs_ite_pro= $obBD_con1->getArrayConsulta(1009,$txt_busqueda2.'*'.$inicio.'*'.$registros.'*'.$Ses_Emp_Cod,$obBD_conexion);
				$total_rs_ite_pro =  count($rs_ite_pro);				
			}
								
			$resultados = $rs_ite_pro;
			$total_registros =$total_rs_ite_pro1; 
			$indicadorfila=0;		
		}	
			?>
	<form name="form2" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
	  <table width="275" border="1"  bordercolor="#000000"  cellspacing="3">
        <tr>
          <?  
			  foreach($rs_ite_pro as $row_rs_ite_pro)
			  {
				/**
				 * Consulto  el departamento del custodio por cod custodio.
				 */
				 if($tipo<>"1"){
				 	$rs_DeparCust=$obBD_con1->getRowConsulta(1000,$row_rs_ite_pro['Cus_Cod'],$obBD_conexion);
				 	$rs_totolDep=count($rs_DeparCust);
				 }
		   ?>
          <td width="250">
          		<table width="266" border="0" cellpadding="0" cellspacing="2">
              	<tr>
                	<td colspan="2" align="center"><? $varcode=$row_rs_ite_pro["Act_Bar"];; include("../../Librerias/barcode/generadorbarras.php"); ?></td>
              	</tr>
              	<tr>
              	  <td  width="84" class="Etiqueta1"><div align="left">Departamento:</div></td>
              	  <td width="175" class="LetraNegra">&nbsp;<? echo $rs_DeparCust['Dep_Des'];?></td>
            	</tr>              	
              	  <td width="84" class="Etiqueta1"><div align="left">Activo:</div></td>
              	  <td class="LetraNegra">&nbsp;<? echo $row_rs_ite_pro['Act_Des']; ?></td>
            	</tr>
              	<tr>
                	<td class="Etiqueta1"><div align="left">Secuencial:</div></td>
                	<td class="LetraNegra">&nbsp;<? echo $row_rs_ite_pro['Act_Sec']; ?></td>
              	</tr>
          </table></td>
          <? 
		   if($indicadorfila==4)
		   {
		   echo "</tr>";
		   echo "<tr>";
		   $indicadorfila=0;
		   }
		   else
		   {
		   $indicadorfila=$indicadorfila+1;	
		   }
		   }
		    ?>
        </tr>
      </table>
	</form>
	</td>
</tr>
</table></td>
  </tr>
</table>
	</table></td>
      </tr>
      </table>
</body>
</html>
<?Php

@$obBD_conexion->cerrar();
@$obBD_con1->liberar();
?>
