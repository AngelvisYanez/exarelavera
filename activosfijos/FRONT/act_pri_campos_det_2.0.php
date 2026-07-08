<?php 
/**  
 *Descripción: Permite la impresión en pantalla de activo fijo
 *Desarrollador:	Fabian Gallardo
 					Didimo Zamora
 *Fecha de actualización:	2011-05-21  
 *Fecha de actualización:	2013-05-21
  */
	
 require_once('../../Librerias/procedimientos/almacenados_standar.php');
 require_once('../LOGICA/act_log_campos_det.php');  	  
	 
/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Con;
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
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?><style type="text/css">
<!--
.Estilo1 {font-size: 16px}
.Estilo2 {font-size: 18px}
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
            $rs_bus = $obBD_con1->getArrayConsulta(439,$txt_bus,$obBD_conexion);
            $total_rs_bus = count($rs_bus);
     ?>
    <?php if ($total_rs_bus > 0 )
		{
			foreach($rs_bus as $row_rs_bus){	
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
        <td colspan="5" valign="top" class="TITULO_REPORTE_2"><span class="Estilo2">Inventario de Activos Fijos</td>
    </tr>
    <tr align="right" >
        <td colspan="5" valign="top" align="right" class="Texto_Reporte"></td>
    </tr>
  </table>
      <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr >
            <td class="Texto_Reporte">Sección: <?php echo $row_rs_bus["Sec_Des"];?></td>
        </tr>
        <tr >
          <td><?php //Custodio: xyz?></td>
        </tr>
      </table>
      <?php
    /**
    * Consulta tipo de activo x su seccion 
    */
    $rs_tip = $obBD_con1->getArrayConsulta(440,$row_rs_bus['Sec_Cod'],$obBD_conexion);
    $total_rs_tip = count($rs_tip);
    if($total_rs_tip > 0){
        foreach($rs_tip as $row_rs_tip){
            $cantidad = 0;
            $td = 0;
			/** 
			 * Buscar el subgrupo de este  tipo de activo
			 */
			$rs_SugTipAct = $obBD_con1->getRowConsulta(656,$row_rs_tip["Tia_Rec"], $obBD_conexion);
			$total_rs_SugTipAct = count($rs_SugTipAct);
      ?>                
       
          <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr >
                    <td class="Texto_Reporte">Grupo: <?php echo $row_rs_tip["Tia_Des"];?></td>
                </tr>
                <tr >
                 <td class="Texto_Reporte">Subgrupo: <?php echo $rs_SugTipAct["Tia_Des"];?></td>
                 </tr>
          </table>                                             
             <?php
		$rs_act = $obBD_con1->getArrayConsulta(441,$row_rs_bus['Sec_Cod'].'*'.$row_rs_tip["Tia_Cod"], $obBD_conexion);
		$total_rs_act = count($rs_act);
        
        /**
		 * seleccionar toodos los campos 
		 */
        $rs_camp = $obBD_con1->getArrayConsulta(419,$row_rs_tip['Tia_Cod'], $obBD_conexion);
        $total_rs_camp = count($rs_camp);
                
            ?>
            <table width="100%" border="1" cellpadding="0" cellspacing="0">
            <tr class="Cabecera1">
                <td class="Texto_Reporte" >C&oacute;d. Int.</td>
                <td class="Texto_Reporte">Secuencial</td>
                <td class="Texto_Reporte">Descripción</td>
                <td class="Texto_Reporte">Fecha Adquisición</td>
               
                <td  class="Texto_Reporte" width="50">Vida Util(años)</td>
                <td  class="Texto_Reporte" width="80">Observación</td>
                
                <?php if($total_rs_camp > 0){
                        foreach($rs_camp as $row_rs_camp){
                    ?>
                        <td class="Texto_Reporte"><?php echo $row_rs_camp['Cam_Cor']; $td +=1;?></td>
                <?php 	}
                }?>
                <td class="Texto_Reporte">Cantidad</td>
             </tr>
		 <?php if($total_rs_act > 0){
                    foreach($rs_act as $row_rs_act){
                ?>
            <tr>
                <td class="Texto_Reporte" align="center"  ><?php echo $row_rs_act['Act_Cod'];?></td>
                <td class="Texto_Reporte" align="center"><?php echo $row_rs_act['Act_Cdc'];?></td>
                <td class="Texto_Reporte"  ><?php echo $row_rs_act['Act_Des'];?></td>
                <td class="Texto_Reporte" ><?php echo $row_rs_act['Act_Fec'];?></td>
                <td class="Texto_Reporte" align="center"><?php echo $row_rs_act['Act_Ann'];?></td>
                <td class="Texto_Reporte" ><?php if($row_rs_act['Act_Obs'] != ''){ echo $row_rs_act['Act_Obs'];}else{ echo "&nbsp;";}?>											
                </td>
               <?php
                //$row_rs_est_act = first_last($rs_est_act, $row_rs_est_act, 0);
                foreach($rs_camp as $row_rs_camp){
                    
                    $rs_det_camp = $obBD_con1->getRowConsulta(430,$row_rs_camp['Cam_Cod'].'*'.$row_rs_act["Act_Cod"], $obBD_conexion);
                    $total_rs_det_camp = count($rs_det_camp);
                    $row_rs_det_camp = $rs_det_camp;
                    
                if($total_rs_det_camp > 0){?>
                <td class="Texto_Reporte" ><?php if($row_rs_det_camp['Act_Val'] != ''){ echo $row_rs_det_camp['Act_Val'];}else{ echo "&nbsp;";}?></td><?php }?>
    <?php 	}//fin  foreach($rs_camp as $row_rs_camp){
        //$row_rs_camp = first_last($rs_camp, $row_rs_camp, 0);
    ?>
        		<td class="Texto_Reporte" align="center"><?php echo $row_rs_act['Act_Can']; $cantidad += $row_rs_act['Act_Can'];?></td>
           </tr>
        <?php 	}/// fin  foreach($row_rs_act as $rs_act)
        }?>
           <tr class="Fondo" >
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                
                <?php if($td > 0 ){
                    for($i = 0 ; $i < $td ; $i++){
                        echo "<td >&nbsp;</td>";	
                    }
                    $td = 0;
                   }?>
                <td>&nbsp;</td>
                <td width="150" align="left">&nbsp;</td>
                <td width="80">&nbsp;</td>
               
                <td align="right">Total :</td>
                
                <td class="Texto_Reporte" align="center"><?php echo $cantidad; $cantidad = 0;?></td>
             </tr>
           </table>
           <br>
           <br>
<?php 
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
		<?php		
			}//  Fin foreach($row_rs_bus  as $rs_bus);
		}?>       
<?php	
	}// fin de if(isset($txt_bus))
?>	
</body>
</html>
<?Php 
$obBD_conexion->cerrar();
?>