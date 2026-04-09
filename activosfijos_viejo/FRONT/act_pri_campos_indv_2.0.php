<?php 
/**  
 *Descripción: Permite la impresión en pantalla de activo fijo
 *Desarrollador:	Fabian Gallardo
 					Didimo Zamora
 *Fecha de actualización:	2011-05-21  
 *Fecha de actualización:	2013-05-21
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
/**
 * Creación del objeto para evitar el reenvio 
 */

$hoy = date("Y-m-d");

/**
 * Consulta las Sucursales 
 */
$rs_suc_act = $obBD_con1->getArrayConsulta(422,$Ses_Emp_Cod, $obBD_conexion);
$total_rs_suc_act = count($rs_suc_act);

	  if (isset($codigo))
	  {
		/** 
		 * Consulta de la cabecera del reporte 
		 */
		$rs_institucion = $obBD_con1->getRowConsulta(134,$Ses_Suc_Cod,$obBD_conexion);
		$total_rs_institucion = count($rs_institucion);
		$row_rs_institucion = $rs_institucion;
		
		$rs_consultar = $obBD_con1->getRowConsulta(431,$codigo,$obBD_conexion);
		$total_rs_consultar = count($rs_consultar);
		$row_rs_consultar = $rs_consultar;
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
<body class="Cuerpo" marginheight="50%">

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
        <td colspan="5" valign="top" align="right" class="Texto_Reporte">&nbsp;</td>
        </tr>
		
	      </table>
	<table width="80%"  align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td colspan="3"></td>
	</tr>
	<tr>
	  <td align="left" class="Texto_Reporte">Fecha de impresi&oacute;n:</td>
	  <td width="75%" class="Texto_Reporte"><? echo $hoy;?></td>
	  <td width="10%"></td>
	  </tr>
	<tr>
		<td width="15%" align="left" class="Texto_Reporte"> Código Activo:</td>
		<td class="Texto_Reporte">&nbsp;<?php echo $row_rs_consultar["Act_Cdc"]?></td>
		<td></td>
	</tr>
	<tr>
		<td width="15%"  align="left" class="Texto_Reporte"> Descripción:</td>
		<td class="Texto_Reporte">&nbsp;<?php echo $row_rs_consultar["Act_Des"]?></td>
		<td></td>
	</tr>
	<tr>
		<td width="15%"  align="left" class="Texto_Reporte"> SubGrupo:</td>
		<td class="Texto_Reporte">&nbsp; <?php echo $row_rs_consultar["Tia_Des"];?></td>
		<td></td>
	</tr>
	</table>
   
    
	
	<table width="80%"  align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
	  <td colspan="3" align="left">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="3" align="left"><label class="Titulos2">Generales</label></td>
	</tr>
	<tr>
    <td width="15%" align="left" class="Texto_Reporte" > Sucursal :</td>
    <td class="Texto_Reporte">&nbsp;
        <?php
		
			foreach($rs_suc_act as $row_rs_suc_act ){  
			 	if($row_rs_suc_act['Suc_Cod'] == $row_rs_consultar["Suc_Cod"] ){ echo $row_rs_suc_act['Suc_Des']; }
			} 
	?>      </td>
    <td>&nbsp;</td>
  </tr>
  <tr>
      <td width="15%" align="left" class="Texto_Reporte" > Proveedor :</td>
	  <td class="Texto_Reporte">&nbsp;<?php
	  
	  /**
	   * Consulta los Proveedores por codigo del proveedor
	   */
		$rs_prv_act = $obBD_con1->getRowConsulta(646,$Ses_Emp_Cod.'*'.$rs_consultar["Prv_Cod"], $obBD_conexion);
		echo $rs_prv_act['Nombre'];
	?>     </td>
	  <td></td>
  </tr>
  <tr>
    <td width="15%"   align="left" class="Texto_Reporte"> Perito :</td>
    <td width="50%" class="Texto_Reporte">&nbsp;
      <?php  
	   /**
		* Consulta de los Peritos por codigo del perito 
		*/				
			$rs_pri_act = $obBD_con1->getRowConsulta(647,$Ses_Emp_Cod.'*'.$rs_consultar["Pri_Cod"], $obBD_conexion);
			echo $rs_pri_act['Pri_Esp'];
	?></td>
    <td width="35%">&nbsp;</td>
  </tr>
  <tr>
      <td width="15%" align="left" class="Texto_Reporte"> Custodio :</td>
	  <td class="Texto_Reporte">&nbsp;<? 
	   /** 
	    * Consulta el  Custodio 
		*/
		$rs_cus_act = $obBD_con1->getRowConsulta(432,$rs_consultar["Act_Cod"], $obBD_conexion);
	 	echo $rs_cus_act["Nombre"];?></td>
	  <td></td>
  </tr>
  <tr>
      <td width="15%" align="left" class="Texto_Reporte" > Estado :</td>
	  <td class="Texto_Reporte">&nbsp;<?php
			/** 
			 * Consulta los Estados por codigo del estado 
			 */
			$rs_est_act = $obBD_con1->getRowConsulta(648,$rs_consultar["Est_Cod"], $obBD_conexion);
			echo $rs_est_act['Est_Des'];
	?>      </td>
	  <td></td>
  </tr>
  <tr>
      <td width="15%" align="left" class="Texto_Reporte"> Observaciones :</td>
	  <td class="Texto_Reporte">&nbsp;<?php echo $row_rs_consultar["Act_Obs"]?></td>
	  <td></td>
  </tr>
  <tr>
      <td width="15%" align="left" class="Texto_Reporte" > Cantidad :</td>
	  <td class="Texto_Reporte">&nbsp;<?php echo $row_rs_consultar["Act_Can"];?></td>
	  <td></td>
  </tr>
  <tr>
    <td  align="left" class="Texto_Reporte"> C&oacute;digo de barra:</td>
    <td colspan="5" class="Texto_Reporte"><table width="559" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="123" class="Texto_Reporte">&nbsp;<?php echo $row_rs_consultar['Act_Bar']; ?></td>
          <td width="22">&nbsp;</td>
          <td width="414"></td>
        </tr>		
      </table>
      </td>
	</tr>
  	<tr class="Etiqueta1">
		<td></td>
		<td colspan="2" ><div align="left"><? 
	 		$varcode = $row_rs_consultar['Act_Bar'];
	  include("../../Librerias/barcode/generadorbarras.php") ?></div></td>
	</tr>
   </table>
    
	<table align="center" width="80%" border="0" cellpadding="0" cellspacing="0">
      <tr>
		<td colspan="3" align="left"><label class="Titulos2">Contables</label></td>
	</tr>
    <tr>
        <td width="15%" align="left" class="Texto_Reporte"> Fecha Adquisición :</td>
        <td width="85%" colspan="2" class="Texto_Reporte">&nbsp;<? echo $row_rs_consultar["Act_Fec"]; ?></td>
      </tr>
	  <tr>
        <td width="15%" align="left" class="Texto_Reporte"> Valor Actual :</td>
        <td width="85%" colspan="2" class="Texto_Reporte">&nbsp;<? echo $row_rs_consultar["Act_Val"]; ?></td>
      </tr>
		 <tr>
        <td width="15%" align="left" class="Texto_Reporte"> Valor Residual :</td>
        <td width="85%" colspan="2" class="Texto_Reporte">&nbsp;<? echo $row_rs_consultar["Act_Res"]; ?></td>
        </tr>
		 <tr>
        <td width="15%" align="left" class="Texto_Reporte" > Vida Útil :</td>
        <td width="85%" colspan="2" class="Texto_Reporte">&nbsp;<? echo $row_rs_consultar["Act_Ann"]; ?>&nbsp;&nbsp;Años </td>
        </tr>
	</table>
   
    	<table align="center" width="80%" cellpadding="0" cellspacing="0" border="0">
        <tr>
	  		<td colspan="2" align="left">&nbsp;</td>
		</tr>
         <tr>
		<td colspan="2" align="left"><label class="Titulos2">Foto del Activo</label></td>
	    </tr>
    	<tr>
            <td width="15%"  class="Texto_Reporte"> Foto: </td>
            <td width="85%"  class="Texto_Reporte"> 
            	<table width="50" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td><fieldset><img name="img" src="<?php echo $row_rs_consultar["Act_Fot"]?>" width="110" height="110" style="max-width: 90px; max-height: 100px;" /></fieldset>
                    </td>
                     <td width="85%"  class="Texto_Reporte"> </td>
                </tr>
		        </table>
            </td>
    	</tr>		
        </table>
        
	<table width="80%"  align="center" cellpadding="0" cellspacing="0" border="0">
    	<tr>
    	  <td colspan="3" align="left">&nbsp;</td>
  	  </tr>
    	<tr>
		<td colspan="3" align="left">	<label class="Titulos2">Técnicos</label></td>
	</tr>
		<tr>
         <td></td>
		 <td width="93%"></td>
    	</tr>
		<tr>
		  <td colspan="3" ></td>
	  </tr>
		<tr>
		   <td>
           <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <?php  
				/**
				 * seleccionar toodos los campos 
				 */
				$rs_con_camp = $obBD_con1->getArrayConsulta(419,$row_rs_consultar["Tia_Cod"],$obBD_conexion);
				$total_rs_con_camp =  count($rs_con_camp);
				
				$i = 1;
				$r = 1;
				$str ="";
				$nam = 0;
			
			foreach($rs_con_camp as $row_rs_con_camp ){?>
			  <tr>
			  <?php
			 $cont = 0;
			while($nam < $total_rs_con_camp && $cont < 1){
			  ?>
                <td width="100%" class="Texto_Reporte"><?php if($row_rs_con_camp['Cam_Est'] == 'I'){ $rojo='#FF0000'; $isact ='F';}else{$rojo=''; $isact ='T';} ?><?php if($row_rs_con_camp['Cam_Req'] == 'R'){ echo $row_rs_con_camp['Cam_Cor']." :"; ?>	
                <td width="100%" class="Texto_Reporte"><? 
				
				$rs_det_camp = $obBD_con1->getRowConsulta(430,$row_rs_con_camp['Cam_Cod'].'*'.$row_rs_consultar["Act_Cod"], $obBD_conexion);
				$total_rs_det_camp = count($rs_det_camp);
				$row_rs_det_camp = $rs_det_camp;
				
				echo $row_rs_det_camp["Act_Val"]; ?></td>
                <?php
					}else{
						
					$rs_det_camp = $obBD_con1->getRowConsulta(430,$row_rs_con_camp['Cam_Cod'].'*'.$row_rs_consultar["Act_Cod"], $obBD_conexion);					
					$total_rs_det_camp = count($rs_det_camp);
					$row_rs_det_camp  =$rs_det_camp;					
					echo $row_rs_con_camp['Cam_Cor']." :"; ?>	
                <td width="80%" class="Texto_Reporte">&nbsp;<?php echo $row_rs_det_camp["Act_Val"];?></td>
				<?
					$i++;
				}
					$cont++;
					$nam++;	
				}
			 	?>
              </tr>
			  <?php
				$row_rs_det_camp = $rs_det_camp;
			  }// fin foreach($rs_con_camp  as $row_rs_con_camp )
			  ?>
          </table></td>
      </tr>
    </table>
</body>
</html>
<?Php 
$obBD_conexion->cerrar();
?>