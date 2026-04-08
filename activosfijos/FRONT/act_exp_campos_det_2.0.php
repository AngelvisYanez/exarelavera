<?php 
/**  
 *Descripci�n: Permite la impresi�n en pantalla de activo fijo
 *Desarrollador: Didimo Zamora
 *Fecha de actualizaci�n:	2011-05-21  
 *Fecha de actualizaci�n:	2013-05-21
 * Fecha de actualizaci�n:	2013-08-13
  */
 //require_once('../../administrador/LOGICA/seguridad.php'); 
 require_once('../../Librerias/procedimientos/almacenados_standar.php');
 require_once('../LOGICA/act_log_campos_det.php');
 include_once '../../Librerias/PHPExcel/PHPExcel.php';
 
header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=activos_export.xls");
header("Pragma: no-cache");
header("Expires: 0");

/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");

/**
 * Consulta las Sucursales 
 */
$rs_suc_act = $obBD_con1->getRowConsulta(422, $Ses_Emp_Cod, $obBD_conexion);
$total_rs_suc_act = count($rs_suc_act);

/** 
 * Consulta los Estados 
 */
$rs_est_act = $obBD_con1->getArrayConsulta(423,'',$obBD_conexion);
$total_rs_est_act = count($rs_est_act);

/**
 * Consultar Departamentos por C�digo
 */
 $rs_Depar= $obBD_con1->getRowConsulta(666,$txt_bus,$obBD_conexion);
 

if (isset($txt_bus)){
	/**
	 * Consulta de la cabecera del reporte 
	 */
	$rs_institucion = $obBD_con1->getRowConsulta(134,$Ses_Suc_Cod, $obBD_conexion);
	$total_rs_institucion = count($rs_institucion);
	$row_rs_institucion = $rs_institucion;		
  }
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

.Titulo_rpt {
	font-size: 11px;
	font-family: Tahoma, Geneva, sans-serif;
	font-style: normal;
	line-height: normal;
	text-transform: uppercase;
	font-weight: bold;
}
-->
</style>
</head>
<body class="Cuerpo">

	  <?php 
        if(isset($txt_bus))
        { 
            /**
			 * Consulta el activo x su seccion 
			 */
     ?>
    <? 
			//foreach($rs_bus as $row_rs_bus){	
	?>
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
        <td colspan="5" valign="top" class="TITULO_REPORTE_2"><span class="Estilo2">Inventario de Activos Fijos [<?php echo $rs_Depar["Dep_Des"];?>]</td>
    </tr>
    <tr align="right" >
        <td colspan="5" valign="top" align="right" class="Texto_Reporte"></td>
    </tr>
  </table>
      <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr >
            <td class="Texto_Reporte"> </td>
        </tr>
        <tr >
          <td><? //Custodio: xyz?></td>
        </tr>
      </table>
      <?
    /**
    * Consulta tipo de activo x su  departamento
    */
    $rs_tip = $obBD_con1->getArrayConsulta(440,$txt_bus,$obBD_conexion);
    $total_rs_tip = count($rs_tip);
    if($total_rs_tip > 0){
        foreach($rs_tip as $row_rs_tip){
            $cantidad = 0;
			$td=0; 
           
			/** 
			 * Buscar el subgrupo de este  tipo de activo
			 */
			$rs_SugTipAct = $obBD_con1->getRowConsulta(656,$row_rs_tip["Tia_Rec"], $obBD_conexion);
			$total_rs_SugTipAct = count($rs_SugTipAct);
      ?>                
       
          <table border="0" cellpadding="0" cellspacing="0" >
            <tr>
                <td class="Etiqueta1">Grupo:&nbsp;</td>
                <td><span class="LetraNegra"><?php echo $row_rs_tip["Tia_Des"];?></span></td>                                 
            </tr> 
            <tr>
                <td class="Etiqueta1">Sub Grupo:&nbsp;</td>
                <td><span class="LetraNegra"> <?php echo $rs_SugTipAct["Tia_Des"];?></span> </td>
            </tr>
         </table>                                             
        <?
		
		$rs_act = $obBD_con1->getArrayConsulta(441,$txt_bus.'*'.$row_rs_tip["Tia_Cod"], $obBD_conexion);
		
		$total_rs_act = count($rs_act);
        $total_rs_camp =0;
        /**
		 * seleccionar toodos los campos 
		 */
        $rs_camp = $obBD_con1->getArrayConsulta(419,$row_rs_tip['Tia_Cod'], $obBD_conexion);
        $total_rs_camp = count($rs_camp); 
		
        ?>
            <table width="100%" border="1" cellpadding="0" cellspacing="0">
            <tr class="Texto_Listados">
                <td width="5" align="center" bgcolor="#CCCCCC" ><strong>C&oacute;d. Int.</strong></td>
              <td width="10" align="center" bgcolor="#CCCCCC" ><strong>Secuencial</strong></td>
              <td width="25" align="center" bgcolor="#CCCCCC" ><strong>Descripci&oacute;n</strong></td>
              <td width="6" align="center" bgcolor="#CCCCCC" ><strong>Fecha Adquisici&oacute;n</strong></td>
              <td width="6" align="center" bgcolor="#CCCCCC"  ><strong>Vida Util(a&ntilde;s)</strong></td>
              <td width="10" align="center" bgcolor="#CCCCCC"><strong>Observaci&oacute;n</strong></td>
                
                <? if($total_rs_camp > 0){
						$ancho = 50/$total_rs_camp;
                        foreach($rs_camp as $row_rs_camp){
                    ?>
                        <td width="<?Php echo $ancho; ?>" align="center" bgcolor="#CCCCCC"><strong>
						<? echo $row_rs_camp['Cam_Cor']; $td +=1;?></strong>
                        </td>
                <? 		}
					
                	}
				?>
              <td width="8" align="center" bgcolor="#CCCCCC"><strong>Cantidad</strong></td>
             </tr>
		 <? if($total_rs_act > 0){
               foreach($rs_act as $row_rs_act){
                ?>
            <tr>
                <td class="Texto_normal_9" align="center"><div align="center"><? echo $row_rs_act['Act_Cod'];?></div></td>
                <td class="Texto_normal_9" align="center"><div align="center"><? echo $row_rs_act['Act_Cdc'];?></div></td>
                <td class="Texto_normal_9" align="left" ><div align="left"><? echo $row_rs_act['Act_Des'];?></div></td>
                <td class="Texto_normal_9" align="center" ><div align="center"><? echo $row_rs_act['Act_Fec'];?></div></td>
                <td class="Texto_normal_9" align="center"><div align="center"><? echo $row_rs_act['Act_Ann'];?></div></td>
                <td class="Texto_normal_9" align="left"><div align="left"><? echo $row_rs_act['Act_Obs'];?></div>										
                </td>
               <?
                //$row_rs_est_act = first_last($rs_est_act, $row_rs_est_act, 0);
                foreach($rs_camp as $row_rs_camp){  
					$total_rs_det_camp=0;                  
                    $rs_det_camp = $obBD_con1->getRowConsulta(430,$row_rs_camp['Cam_Cod'].'*'.$row_rs_act["Act_Cod"], $obBD_conexion);
                    $total_rs_det_camp = count($rs_det_camp);
                    $row_rs_det_camp = $rs_det_camp;                   
                	if($total_rs_det_camp > 0){?>
                		<td class="Texto_normal_9" align="right" ><div align="left">
					<? 	if($row_rs_det_camp['Act_Val'] != ''){
                            echo $row_rs_det_camp['Act_Val'];
                        }
                        else{
                         echo "&nbsp;";
                        }
					?>
                    </div>
                		</td>
				<? 
					}    
				else{
					echo "<td align='right'>&nbsp;</td>";					
					}	
					 	
				}//fin  foreach($rs_camp as $row_rs_camp){
        //$row_rs_camp = first_last($rs_camp, $row_rs_camp, 0);
    		?>
        		<td align="center" class="Texto_normal_9"><div align="center"><? echo $row_rs_act['Act_Can']; $cantidad += $row_rs_act['Act_Can'];?>
            	</div></td>
           </tr>
        <? 	}/// fin  foreach($row_rs_act as $rs_act)
        }?>
           <tr class="Fondo" >
           		  <td  class="Texto_normal_9" align="right" colspan="<? echo $td+6;?>"><div align="right">Totales:</div></td>
                  <td colspan="<? echo $td+6;?>" align="center" bgcolor="#CCCCCC" class="Texto_normal_9"><div align="center"><? echo $cantidad; $cantidad = 0;?></div></td>     
<? 			if($td > 0 ){
                    $td = 0;
            }
?>                             
             </tr>
           </table>
     <br>
           <br>
<? 
			}// fin  foreach($row_rs_tip as $rs_tip) 
		}// fin if($total_rs_tip > 0)?>
                            <br>
                            <br>
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
              <td height="19" align="center" valign="top">EMITIDO POR</td>
              <td align="center" valign="top">APROBADO POR</td>
              <td align="center" valign="top">AUTORIZADO POR</td>
            </tr>
         </table>                    
          </div>
		<?		
		//	}//  Fin foreach($row_rs_bus  as $rs_bus);
	}// fin de if(isset($txt_bus))
?>	





</body>
</html>
<?Php 
$obBD_conexion->cerrar();
?>