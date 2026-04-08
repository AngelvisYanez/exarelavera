<?Php
/************************************************************************************
 * Alias: Consulta de Control de Custodia de Activos Fijos                          *
 * Descripción: Permite consultar los controles de custodia de activos fijos.       *
 * Desarrollador: Didimo Zamora                                                     *
 * Fecha de actualización:	2013/07/24                                              *
 ************************************************************************************/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_activo_aud.php');	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');


/**
 * Objeto de Conexion de Control de Tenencia de Activos
 */
$obBD_conexion = new Class_Log_Conexion_Cch($Ses_Dat_Dis);
/**
 * Objeto de Acceso a Datos de Control de Tenencia de Activos
 */
$obBD_con1 = new Class_Log_Datos_ActAu;
/**
 * Creación del objeto para evitar el reenvio 
 */
$thisPost = new Post_Block; 

/**
 * Consulta del  auditor
 */
$rs_auditore = $obBD_con1->getRowConsulta(8,$Ses_Prs_Cod,$obBD_conexion);	

/**
 *Consulta de los datos de la Empresa
 */
$rs_instituc = $obBD_con1->getRowConsulta(5001,$Ses_Suc_Cod.'*'.$Ses_Emp_Cod,$obBD_conexion);

/**
 * Consulta de la tabla estado de activos fijos esta es una tabla generica para todas las empresas.
 */
$rs_estado = $obBD_con1->getArrayConsulta(4,'',$obBD_conexion);
$total_rs_estado = count($rs_estado);
$hoy = date("Y-m-d");
$FecI = date('Y-m').'-01';
$FecF = date('Y-m-d');


	if(isset($hdd_volver) || isset($ok) || isset($codigo1) ){ 
		$FecI=$Fec1;
		$FecF=$Fec2;
		/**
		 * Busqueda de los las auditorias de activos por fecha
		 */
		$rs_buscar = $obBD_con1->getArrayConsulta(9,$FecI.'*'.$FecF.'*'.$Ses_Emp_Cod.'*'.$rs_auditore['Aud_Cod'], $obBD_conexion);					
		$total_rs_buscar = count($rs_buscar);
 	}



?>
<html>
<head>
<title><?Php echo $Ses_Sys_Nom;?></title>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>     
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
		<script>
		$(function() { 
			$( "#Fec1" ).datepicker({
				changeMonth:true, 
				changeYear:true, 
				dateFormat: "yy-mm-dd"
				});
			$( "#Fec2" ).datepicker({
					changeMonth:true, 
					changeYear:true, 
					dateFormat: "yy-mm-dd"
					});
		});
        </script>
        <script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<div id="set1">
<?Php 
/**
 * Si auditor existe y es correcto
 */
if(count($rs_auditore)>0){
?>
	<table width="100%" border="0">
		<tr class="BarraTitulo"> 
			<td align="left">&raquo; Consulta de Control de Tenencia de Activos Fijos</td>
		</tr>
		<tr>
			<td>
				<fieldset>
				<LEGEND>
					<label class="Titulos2">Buscar por fecha:&nbsp;</label>
				</LEGEND>
					<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
						<table width="100%" border="0">
								<tr>
									<td width="155"><span class="LetraNegra">Fecha Inicio:</span> <input name="Fec1" type="text" id="Fec1" value="<?Php echo $FecI;?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
								</td>
								<td width="142"><span class="LetraNegra">Fecha Fin:</span><input name="Fec2" type="text" id="Fec2" value="<?Php echo $FecF;?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
								</td>
								<td width="547">
    <button name="btn_aceptar" class="btn btn-success fileinput-button" id="btn_aceptar" title="Mostrar Controles de Tenencia de Activos Fijos" onClick="validar_requeridos(this.form,'$txt_busqueda',0)">
									<i class="icon-search icon-white"></i>
									<span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>   
									</button>
									<input name="ok" id="ok" type="hidden" value="">
								</td>
							</tr>
						</table>                
					</form>
				</FIELDSET>
<? if(isset($ok) || isset($hdd_volver)){?>
			<FIELDSET>
				<LEGEND>
					<label class="Titulos2">Resultados de la Consulta</label>
				</LEGEND>
					<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
						<thead>
					  <th width="8%">C&oacute;d. Int.</th>
							<th width="10%">C&eacute;dula</th>
							<th width="67%">Custodio</th>
							<th width="15%">Fecha Control</th>
							<th>&nbsp;</th>         
						</thead>
						<tbody>
<?Php 
	if ($total_rs_buscar > 0){ 	
		foreach($rs_buscar as $row_rs_buscar){ 
			if($row_rs_buscar['Aud_Est']=='I')
				{ $rojo='#FF0000'; $anulada++; }else{$rojo='';}
?>
						<tr>
							<td align="center"><FONT COLOR="<? echo $rojo;?>"><?php echo $row_rs_buscar['Aud_Int'];?></FONT></td>
							<td align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Prs_Ced'];?></FONT></td>
							<td><p><FONT COLOR="<? echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Custodio'],'#FFFF00', 1);?>    
							</FONT></p>
							</td>
							<td align="center" ><?Php echo $row_rs_buscar['Aud_Fec'];?></td>
         
					<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post">
							<td align="center">
						<?Php if($row_rs_buscar['Aud_Est']=='A'){?>
						<input name="codigo1" type="hidden" id="codigo1" value="<?php echo $row_rs_buscar['Aud_Int'];?>"> 
						<input name="custodio" type="hidden" id="custodio" value="<?php echo $row_rs_buscar['Custodio'];?>">  
						<input id="Aud_Cod" name="Aud_Cod" type="hidden" value="<?php echo $row_rs_buscar['Aud_Cod'];?>">
						<input id="Aud_Fec" name="Aud_Fec" type="hidden" value="<?php echo $row_rs_buscar['Aud_Fec'];?>">
						<input name="ci" type="hidden" id="ci" value="<?php echo $row_rs_buscar['Prs_Ced'];?>"> 
                        <input name="Fec1" type="hidden" id="Fec1_Vol" value="<?php echo $Fec1;?>">
                        <input name="Fec2" type="hidden" id="Fec2_Vol" value="<?php echo $Fec2;?>">
						<button type="button" name="imageField" width="22" height="22" title="Seleccionar Control de Tenencia"  class='btn btn-success btn-mini' onClick="submit();">	
						<i class='icon-arrow-right icon-white'></i>
						</button>        	                     				
						<?Php
						}
						else
						{
						echo "&nbsp;";
						}
						?>	
							</td>
					</form>             
						</tr>
<?Php
			} //fin foreach($rs_buscar as $row_rs_buscar){       
		}	// Fin de if ($total_rs_buscar > 0){
		else{
?>
						<tr>
							<td></td>
							<td>&nbsp;</td>
							<td align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
<?
			} // fin del if ($total_rs_buscar > 0)?>
						</tbody>
					</table>  
<? 
} //  fin de if(isset($ok)){
?>
			  </FIELDSET>  
			</td>
		</tr>
	</table>
<?		
	if (isset($codigo1))
	{
/**
* Consulto los activos  del control por cod de audioria
*/	
$rs_consultar = $obBD_con1->getArrayConsulta(10,$codigo1, $obBD_conexion);
$total_rs_consultar = count($rs_consultar);
/**
* Consulta del  auditor o responsable del control
*/
$rs_auditore = $obBD_con1->getRowConsulta(11,$Aud_Cod,$obBD_conexion);	
?>
<p>
  <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Datos del Control</label>
		</LEGEND>
		<form name="form4" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
		<table width="100%" border="0" cellpadding="0" cellspacing="0" >      
			<tr>
				<td width="8%" class="Etiqueta1" >Instituci&oacute;n:</td>
				<td width="18%"  align="left" > &nbsp;<span class="LetraNegra"><? echo $rs_instituc['Emp_Nom'];?></span></td>
				<td width="2%"  align="left" class="Etiqueta1" > </td>  
				<td width="72%" align="left"  class="Etiqueta1"> </td>  
			</tr>
			<tr>
				<td width="8%" class="Etiqueta1" >Fecha de Control:</td>
				<td width="18%" align="left" >&nbsp;<span class="LetraNegra"><?Php echo $Aud_Fec;?></span></td>
				<td width="2%" > </td>    	  
				<td width="72%" > </td>  
			</tr> 
			<tr>
				<td width="8%" class="Etiqueta1">Custodio:</td>
				<td width="18%" align="left" >&nbsp;<span class="LetraNegra"><?Php echo $custodio;?></span></td>  
				<td width="2%" align="left" class="Etiqueta1" >Cédula:</td>   	
				<td width="72%" align="left">&nbsp;<span class="LetraNegra"><?Php echo $ci;?></span></td>    
			</tr> 
		</table>
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">      
			<thead>
		  <th width="5%" align="center">Ord.</th>
				<th width="8%" align="center"> Cod. Int.</th>
				<th width="30%" align="center">Descripci&oacute;n del Activo</th>     	  
				<th width="8%" align="center"> Estado</th> 
				<th width="10%" align="center"> Auditor Estado</th>                  
				<th width="39%" align="center" lign="center" >Observaciones</th>           
			</thead> 
			<tbody>
<?Php 
	/**
	* Recorrido del bucle de activos por codigo del custodio
	*/
			if ($total_rs_consultar>0){
				$i=0;
				foreach($rs_consultar as $row_rs_consultar){ 
					$i++;			  	
?>
			<tr>
				<td align="center"><?Php echo $i;?> </td>
				<td align="center"><?Php echo $row_rs_consultar['Act_Cod'];?></td>
				<td><?Php echo $row_rs_consultar['Act_Des'];?></td>
				<td align="center">
                	<?Php
                    	$rs_Estado1 = $obBD_con1->getRowConsulta(14,$row_rs_consultar['Est_Act'], $obBD_conexion);
						echo $rs_Estado1['Est_Des'];
					?>
                </td>
				<td align="center"><?Php echo $row_rs_consultar['Est_Des'];?></td>     
				<td><?Php echo $row_rs_consultar['Aud_Obs'];?></td>
               
			</tr>  
<?Php 
				}// fin  de foreach($rs_buscar as $row_rs_buscar){ 
			}// fin de if ($total_rs_buscar>0){
			else{
?>
			<tr>
	            <td>&nbsp;</td>
                <td>&nbsp;</td>
				<td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1) ?> </td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
			</tr>          
<?Php
			}
?> 
			</tbody>
		</table>  
<?
			echo barra_estado($total_rs_consultar);		
	}
?>
	<p>
<?Php
			if ($total_rs_consultar>0){
?>
		</form>  
  </FIELDSET> 
  <p>
			<table  width="247">
				<tr>
                	<td width="37%" align="left" >
                    <form name="form6" method="post" action="<? echo $_SERVER['PHP_SELF'];?>">
                    	<button type="button" name="btn_atras" id="btn_atras" value="Enviar" class="btn btn-inverse fileinput-button" title="Atr&aacute;s"
                        onClick="campos_hide(this.form, '<?Php echo "Fec1*Fec2*hdd_volver"; ?>','<?Php echo $Fec1.'*'.$Fec2.'*'.$hdd_volver;?>')">
                         <i class="icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
                        </button>
                        <input id="Fec1" name="Fec1" type="hidden" value="<?php echo $Fec1;?>">
                        <input id="Fec2" name="Fec2" type="hidden" value="<?php echo $Fec2;?>">
                        <input id="hdd_volver" name="hdd_volver" type="hidden" value="0">
                    </form>
                    </td>
					<td width="63%" >              
						<form name="form3" method="post" action="<? echo 'act_pri_activo_aud_1.0.php';?>" target="_blank">
							<button name="boton_imprimir" id="boton_imprimir" type="submit" class="btn btn-primary start" title="Imprimir" value="Imprimir"> 
							<i class="icon-print icon-white"></i>
							<span>&nbsp;&nbsp;Imprimir&nbsp;&nbsp;</span>
							</button>   
							<input type="hidden" id="Aud_Int" name="Aud_Int" value="<?Php echo $codigo1;?>">            
						</form>             
					</td>
					
				</tr> 
			</table>     
<?Php
			}
?>     
	
<?Php 
}// Fin  de if(count($rs_auditore)>0)
else
{
?>
	<table width="100%" border="0" >
		<tr>
			<td align="left" class="BarraTitulo">&raquo;Control de Tenencia de Activos Fijos</td>
		</tr>
		<tr>
			<td align="center"><input  name="img" id="img" type="image" src="../../mascaras/model1/imagenes/32x32/advertencia.PNG"><span class="LetraNegra">
		Ud. no est&aacute; autorizado.</span></td>
		</tr>
  </table>   
<?Php
}
?>
</div>
	<script type="text/javascript" src="../VALIDACIONES/act_par_activo_aud.js"></script>
    <script type="text/javascript" src="../../Librerias/textbox/main.js"></script>   
</body>
</html>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>