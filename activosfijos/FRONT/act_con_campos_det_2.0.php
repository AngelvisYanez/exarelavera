<?php 
/** 
 *Alias:	Consulta
 *Descripci�n: Permite el ingreso del detalle de los tipos de activos
 *Desarrollador:	Didimo Zamora
 *Fecha de actualizaci�n:	2013-05-27
 ************************************
 *Desarrollador:	Didimo Zamora
 *Fecha de actualizaci�n:	2013-08-13
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_campos_det.php');  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php');	 	  

/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis); 
/**
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Con;
/**
 * Creaci�n del objeto para evitar el reenvio 
 */
$thisPost = new Post_Block;  
/**
 * Muestra modal para dar de alta el mantenimiento de activo.
 */
if (isset($ajax_mod_Act))
{	
	?> 
   <fieldset>
   <LEGEND>
    <label class="Titulos2"><?php echo $Act_Des; ?></label>
    </LEGEND>
    <br>
	<table  align="center" border="0" cellpadding="0" cellspacing="0">       
      <tr>     	
        <td align="center">
        <fieldset><img name="img1" src="<?php echo $Act_Fot; ?>" width="640" height="640" style="max-width: 310px; max-height: 310px"   /></fieldset>
        </td>
      </tr>   
	</table>	     
      <br>
	</fieldset>   
<?Php
exit();
}

if(!isset($op))
{
	$op =1;	
}
switch($op){
	case 2:	
	/**
	 * Consulta el detalle de los departamentos 
	 */
	$rs_depar = $obBD_con1->getArrayConsulta(438,'', $obBD_conexion);
	$total_rs_depar = count($rs_depar);	
	/** 
	 * Consulta los Estados 
	 */
	$rs_est_act = $obBD_con1->getArrayConsulta(423,'', $obBD_conexion);
	$total_rs_est_act =  count($rs_est_act);

	break;	
}

 if ($thisPost->postBlock($_POST['postID'])) 
 { 
 	 if($txt_busqueda != "")
	 { 
		/**
		* Filtrado por tipo de activo
		*/	
		if ($Tia_Cod == 'T') 
		 $tipo_a = " ";
		else
		 $tipo_a = " AND activo.Tia_Cod = ".$Tia_Cod; 
		
	 	if ($op_opciones == "d")
		{			
			if ($Tip_Bus == 'L')//Busqueda por coincidencia
			{
				/**
				 * Busqueda del activo x medio de la descripcion (like)
				 */
				$rs_buscar = $obBD_con1->getArrayConsulta(429,strtoupper(trim($txt_busqueda)).'*'.$tipo_a, $obBD_conexion);		
			}
			else //Busqueda exacta
			{
				/**
				 * Busqueda del activo x medio de la descripcion (igual)
				 */
				$rs_buscar = $obBD_con1->getArrayConsulta(664,strtoupper(trim($txt_busqueda)).'*'.$tipo_a, $obBD_conexion);						
			}
		}
		if ($op_opciones == "cs")
		{
			/**
			 * Busqueda del activo x medio del codigo secuencial
			 */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(435,$txt_busqueda.'*'.$tipo_a, $obBD_conexion);		
		}
		if ($op_opciones == "cb")
		{
			/*****************************************
			* Permite transformar al codigo original*
			*****************************************/
			$cant_text= strlen($txt_busqueda);
			$bar_busq = cortar_cadena(0, $cant_text-2, $txt_busqueda);			
			$bar_busq = "0".$bar_busq;
			/** 
			 *Busqueda del activo x medio del codigo de barra
			 */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(436,$bar_busq.'*'.$tipo_a, $obBD_conexion);		
		}
		if ($op_opciones == "ns")
		{
			if (isset($Cam_Cod)){
			 /**
			  * Busqueda del activo x medio del codigo de barra
			  */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(644,$Cam_Cod.'*'.$txt_busqueda.'*'.$tipo_a, $obBD_conexion);		
			}
		}
		$total_rs_buscar = count($rs_buscar);	
	 }else
		{
		/**
		 * Consulta realizada en base al c�digo seleccionado 
		 */
			if (isset($codigo))
			{
				$rs_consultar = $obBD_con1->getRowConsulta(431,$codigo, $obBD_conexion);
				$total_rs_consultar = count($rs_consultar);
			}
		}	
}
?> 
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script language="javascript" src="../VALIDACIONES/act_val_campos_det.js"></script> 
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
        <script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
        
        <!--<script type="text/javascript" src="../../Librerias/exportar/jquery-1.3.2.min.js"></script>
	    
	    <script language="javascript">
			$(document).ready(function() {
				/* LLamado a la class del boton exportar */
				$("#Boton_Excel").click(function(event) {
					$("#datos_a_enviar").val( $("<div>").append( $("#Exportar_a_Excel").eq(0).clone()).html());
					$("#FormularioExportacion").submit();
			});
			});
		</script>-->

		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Consultar Activo Fijo </td>
  </tr>
	<tr>
	  	<td valign="top">
		<?
		
		$descripcion = "Individual*General*Resumen*Detallado";
  		$pag1= $_SERVER['PHP_SELF']."?op=1";
		$pag2= $_SERVER['PHP_SELF']."?op=2";
		$pag3= $_SERVER['PHP_SELF']."?op=3";
		$pag4= $_SERVER['PHP_SELF']. "?op=4";
		tabs(3,$descripcion, $pag1.'*'.$pag2.'*'.$pag3.'*'.$pag4, $op);
	?>
 <!--<div id="ContTabul">-->
	<?
	if(!isset($op)){$op = 1;}
	
		if (($op==1 || $op==2 || $op==3 || $op==4)) {
		switch($op) {
		case 1: 	
	?>
  <fieldset>
  <LEGEND>
    <label class="Titulos2">Buscar por:&nbsp;</label>
   </LEGEND><form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']."?op=1"?>">
   <table width="709" border="0">
              <tr>
                <td width="121"><input name="op_opciones" type="radio" value="d" checked   onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">
                    <span class="LetraNegra">Descripci&oacute;n</span>
                    	<input name="op_cam" id="op_cam" type="hidden" value="d">
                </td>
                <td width="120"><input type="radio" name="op_opciones" value="cb" onClick="document.getElementById('op_cam').value=this.value;setfocus(this.form.txt_busqueda); busquedaCampos();">
                
                    <span class="LetraNegra">C&oacute;digo de Barra</span></td>
				<td width="152"><input type="radio" name="op_opciones" value="cs" onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">
                    <span class="LetraNegra">C&oacute;digo Secuencial</span></td>
             
              <td width="225"><input type="radio" name="op_opciones" value="ns" onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.Cam_Cod);">
                    <span class="LetraNegra">Por Campos</span>
                    <?Php
					/**
					 * consulto los campos que esten definidos como busqueda 
					 */
					$rs_campos=$obBD_con1->getArrayConsulta(643, '', $obBD_conexion);
				?>
                    <select name="Cam_Cod" id="Cam_Cod" onChange="setfocus(this.form.txt_busqueda);">
                            <?Php foreach($rs_campos as $row_rs_campos){?>  
                              <option  value="<? echo $row_rs_campos['Cam_Cod'];?>"><?PHP  echo $row_rs_campos['Cam_Cor'];?></option>
                             <?Php 
                                } //fin foreach($rs_campos as $row_rs_campos){
                              ?> 
                    </select>
                </td>
             
                    	
            </tr>
              <tr>
                <td class="LetraNegra">Tipo de activo:</td>
                <td colspan="2">
                <?Php
				$row_tipo_activo = $obBD_con1->getArrayConsulta(665, $Ses_Emp_Cod, $obBD_conexion);				
				?>
                <select name="Tia_Cod" id="Tia_Cod">
                <option value="T">Todos</option>
                <?php
				foreach($row_tipo_activo as $row)
				{
				?>
                	<option <?Php if ($row['Tia_Cod']==$Tia_Cod){ echo "selected"; }?> value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des']; ?></option>
                <?Php
				}
				?>
                </select>
                
                
                </td>
                <td><span class="LetraNegra">Tipo busqueda: 
                  <select name="Tip_Bus2" id="Tip_Bus2">
                    <option <?Php if ($Tip_Bus == 'L'){ echo "selected"; }?> value="L">Coincidencia</option>
                    <option <?Php if ($Tip_Bus == 'I'){ echo "selected"; }?> value="I">Exacta</option>
                  </select>
                </span></td>
                
              </tr>    
            </table>
   
   <table width="945" height="39" border="0" cellpadding="0" cellspacing="0">
	<tr>
      <td width="64" height="39"class="BarraBusqueda"><div align="left"><span class="Asterisco">*</span> Activo: </div></td>
      <td width="541" class="BarraBusqueda">&nbsp;<input size="50" name="txt_busqueda" type="text" id="txt_busqueda">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<button name="btn_aceptar" type="submit" class="btn btn-success fileinput-button" id="btn_aceptar" value="Aceptar" title="Aceptar" onClick="validar_requeridos(this.form, 'txt_busqueda', 0)" >
	        <i class="icon-ok icon-white"></i>
	  		<span>&nbsp;&nbsp;Aceptar&nbsp;&nbsp;</span>
        </button>     
	  </td>
      <td width="340"></td>
    </tr>
  </table>
  <script> 
  	ShowHide('Cam_Cod');
  </script>
  
  </form>
<? if (isset($txt_busqueda))
{
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
	  <thead>
      <tr>
		  <th width="5%">C�d. Int. </th>
          <th width="17%">SubGrupo</th>
		  <th width="32%">Descripci&oacute;n </th>
		  <th width="12%">Secuencial</th>
          <?Php 
		  	 /**
			 * seleccionar toodos los campos de busqueda
			 */
			$td=0;
			$rs_camp = $obBD_con1->getArrayConsulta(660,'', $obBD_conexion);
			$total_rs_camp =  count($rs_camp); 
		  	if($total_rs_camp > 0){									
				foreach($rs_camp as $row_rs_camp){
				?>
					<th width="11%">
					<? echo $row_rs_camp['Cam_Cor']; $td +=1; ?>
                    </th>
			   <? }//if($total_rs_camp > 0){
			}?>
          
          <th width="4%">&nbsp;</th>
          </tr>
      </thead>
      <tbody>
	  <?Php 
	 if ($total_rs_buscar > 0){  		
	  foreach($rs_buscar as $row_rs_buscar){
	  ?>
	  <tr>
          <td align="center"><?php echo $row_rs_buscar['Act_Cod'];?></td>
          <td><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Tia_Des'],'#FFFF00', 1);?></td>
          <td><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Act_Des'],'#FFFF00', 1);?></td>
          <td><?php echo  $row_rs_buscar['Act_Cdc'];?></td>	          
          <?php 		
			$rs_camp = $obBD_con1->getArrayConsulta(660,'', $obBD_conexion);
			$total_rs_camp =  count($rs_camp);
			if ($total_rs_camp> 0){
				foreach($rs_camp as $row_rs_camp){
						$rs_val_Camp =  $obBD_con1->getRowConsulta(661, $row_rs_buscar['Act_Cod'].'*'. $row_rs_camp['Cam_Cod'],$obBD_conexion);
					?>
					<td align="center" width="16%">
						<?Php echo $rs_val_Camp['Act_Val'] ?>                
					</td>
					<?
				}
		 	 }
		  ?>
          
          <td align="center" width="4%">
            <form action="<? echo $_SERVER['PHP_SELF']."?op=1";?>" method="post" name= "frml" id="forml">
              <button type="image" name="imageField"  class="btn btn-success btn-mini" width="22" height="22" title="Ver">
                <i class="icon-arrow-right icon-white"></i>
                </button>					
              <input type="hidden" name="codigo" id="codigo" value="<?Php echo $row_rs_buscar['Act_Cod'];?>"/>
              <input type="hidden" name="hdd_aux" id="hdd_aux" value="1">
               <input type="hidden" name="Cam_Cod" id="Cam_Cod" value="<?Php echo $Cam_Cod;?>">
              <input type="hidden" name="volver_busqueda" id="volver_busqueda" value="<?Php echo $txt_busqueda;?>"/>
              <input type="hidden" name="volver_opciones" id="volver_opciones" value="<? echo $op_opciones?>">
              </form>
          </td>		
	  </tr>
	  <?Php } //fin foreach($rs_buscar as $row_rs_buscar){     
  	  }else{
  	  ?>
      	<tr>
        	<td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td><?Php echo error_alerta("�No hay resultados que mostrar!", 1)?></td>
            <?php
            if($td > 0){
				?>
				 <td>&nbsp;</td>
                <?Php
			}
			?>
           
           	<td>&nbsp;</td>
        </tr>
      <? } // fin del if ($total_rs_buscar > 0)?>
      </tbody>
	</table> 
    <?Php
	/**
	 * Muestra la barra de estados con la cantidad de registros encontrados 
	 */
	echo barra_estado($total_rs_buscar+0);
	?>
</FIELDSET>

<p>
<form method="post" name= "form10" action="<? echo 'act_pri_campos_det_fil_1.0.php';?>" target="_blank">
  <button  name="boton_imprimir" id="boton_imprimir" type="submit" class="btn btn-primary start" value="Imprimir" >
                  <i class='icon-print icon-white'></i> <span>Imprimir</span>
    </button>
    <input type="hidden" name="txt_busqueda_pri" id="txt_busqueda_pri" value="<?Php echo $txt_busqueda;?>"/>
    <input type="hidden" name="Tip_Bus" id="Tip_Bus" value="<?Php echo $Tip_Bus;?>"/>
	<input type="hidden" name="tipo_a" id="tipo_a" value="<?Php echo $tipo_a;?>"/>    
    
</form>      
<? }
if ($hdd_aux==1) { ?>

<fieldset>
  <LEGEND>
    <label class="Titulos2">Datos a Imprimir&nbsp;</label>
</LEGEND>
  	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td colspan="3"><?Php echo mensaje_requerido(); ?></td>
	</tr>
	<tr>
		<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span> C�digo Activo:</td>
		<td class="LetraNegra">&nbsp;<?php echo $rs_consultar["Act_Cdc"]?></td>
		<td></td>
	</tr>
	<tr>
	  <td class="Etiqueta1"><span class="Asterisco">*</span> Tipo de Activo:</td>
	  <td>
	    <label class="LetraNegra">&nbsp;<?php echo $rs_consultar["Tia_Des"];?></label></td>
	  <td></td>
	  </tr>
	<tr>
	  <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span> Descripci�n:</td>
	  <td class="LetraNegra">&nbsp;<?php echo $rs_consultar["Act_Des"]?></td>
	  <td></td>
	  </tr>
	</table>
  <fieldset>
  <LEGEND>
    <label class="Titulos2">Generales</label>
	</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
    <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Sucursal :</td>
    <td class="LetraNegra">&nbsp;
        <?php 
		/**
	 * Consulta las Sucursales 
	 */
	$rs_suc_act = $obBD_con1->getRowConsulta(645,$rs_consultar["Suc_Cod"], $obBD_conexion);
	echo $rs_suc_act['Suc_Des'];
		?>
      </td>
    <td>&nbsp;</td>
  </tr>
  <tr>
      <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Proveedor :</td>
	  <td class="LetraNegra">&nbsp;
	  <?php 
	  /**
	   * Consulta los Proveedores por codigo del proveedor
	   */
		$rs_prv_act = $obBD_con1->getRowConsulta(646,$Ses_Emp_Cod.'*'.$rs_consultar["Prv_Cod"], $obBD_conexion);
		echo $rs_prv_act['Nombre'];
	  ?>
      </td>
	  <td></td>
  </tr>
  <tr>
    <td width="20%"  class="Etiqueta1"><span class="Asterisco">*</span> Perito :</td>
    <td width="48%" class="LetraNegra">&nbsp;
      <?php 
			/**
			* Consulta de los Peritos por codigo del perito 
			*/				
				$rs_pri_act = $obBD_con1->getRowConsulta(647,$Ses_Emp_Cod.'*'.$rs_consultar["Pri_Cod"], $obBD_conexion);
				echo $rs_pri_act['Pri_Esp'];
				
	?></td>
    <td width="32%">&nbsp;</td>
  </tr>
  <tr>
      <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Custodio :</td>
	  <td class="LetraNegra">&nbsp;<? 
	  	
	   /** 
	    * Consulta el  Custodio 
		*/
		$rs_cus_act = $obBD_con1->getRowConsulta(432,$rs_consultar["Act_Cod"], $obBD_conexion);
		//$total_rs_cus_act = count($rs_cus_act);
	 	echo $rs_cus_act["Nombre"]; 
		
		?></td>
	  <td></td>
  </tr>
  <tr>
      <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Estado :</td>
	  <td class="LetraNegra">&nbsp;<?php	  
			/** 
			 * Consulta los Estados por codigo del estado 
			 */
			$rs_est_act = $obBD_con1->getRowConsulta(648,$rs_consultar["Est_Cod"], $obBD_conexion);
			echo $rs_est_act['Est_Des'];
	?>
      </td>
	  <td></td>
  </tr>
  <tr>
      <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Observaciones :</td>
	  <td class="LetraNegra">&nbsp;<?php echo $rs_consultar["Act_Obs"]?></td>
	  <td></td>
  </tr>
  <tr>
      <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Cantidad :</td>
	  <td class="LetraNegra">&nbsp;<?php echo $rs_consultar["Act_Can"];?></td>
	  <td></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> C&oacute;digo de barra:</td>
    <td colspan="5" class="LetraNegra"><table width="559" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="123" class="LetraNegra">&nbsp;<?php echo $rs_consultar['Act_Bar']; ?></td>
          <td width="22">&nbsp;</td>
          <td width="414"></td>
        </tr>
		
      </table></td>
	</tr>
  	<tr class="Etiqueta1">
		<td colspan="3" class="Etiqueta1"><div align="center"><? 
	 		$varcode = $rs_consultar['Act_Bar'];
	  include("../../Librerias/barcode/generadorbarras.php");
	  ?></div></td>
	</tr>
   </table>
  </fieldset>
<fieldset>
  <LEGEND>
    <label class="Titulos2">Contables</label>
    </LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
      <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Fecha Adquisici�n :</td>
	  <td width="66%" class="LetraNegra">&nbsp;<?php echo $rs_consultar["Act_Fec"]?></td>
	  <td width="14%"></td>
  	</tr>
      <tr>
        <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Valor Actual :</td>
        <td colspan="2" class="LetraNegra">&nbsp;<? echo $rs_consultar["Act_Val"]; ?></td>
        </tr>
		 <tr>
        <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Valor Residual :</td>
        <td colspan="2" class="LetraNegra">&nbsp;<? echo $rs_consultar["Act_Res"]; ?></td>
        </tr>
		 <tr>
        <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Vida �til :</td>
        <td colspan="2" class="LetraNegra">&nbsp;<? echo $rs_consultar["Act_Ann"]; ?>&nbsp;&nbsp;A�os </td>
        </tr>
	</table>	
	</fieldset>
    
  <fieldset>
  <LEGEND>
    <label class="Titulos2">Foto del Activo</label>
    </LEGEND>
    	<table width="100%" cellpadding="0" cellspacing="0" border="0">
    	<tr>
            <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span> Foto: </td>
        	<td width="2%" ></td>
            <td width="78%"> 
            	<table width="50" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td><fieldset><img name="img" src="<?php echo $rs_consultar["Act_Fot"]?>" width="110" height="110" style="max-width: 90px; max-height: 100px; cursor:pointer;"  onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_Act=1&Act_Fot=<?php echo $rs_consultar["Act_Fot"];?>&Act_Des=<?php echo $rs_consultar["Act_Des"]?>','ajax_modal');" title="Ampliar imagen"  /></fieldset>
                    </td>
                </tr>
		        </table>
            </td>
    	</tr>		
        </table>
    </fieldset>
   
 <fieldset>
  <LEGEND>
    <label class="Titulos2">T�cnicos</label>
	</LEGEND>
  <table width="100%" cellpadding="0" cellspacing="0" border="0">
    	<tr>
         <td></td>
		 <td width="97%"></td>
    	</tr>
		<tr>
		  <td colspan="2" class="Etiqueta1"></td>
		  </tr>
		<tr>
		  <td width="3%" class="Etiqueta1">&nbsp;</td>
            <td>
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <?php  
			/**
			 * seleccionar toodos los campos 
			 */
			$rs_con_camp = $obBD_con1->getArrayConsulta(419,$rs_consultar["Tia_Cod"], $obBD_conexion);
			$total_rs_con_camp = count($rs_con_camp);
				
			$i = 1;
			$r = 1;
			$str ="";
			$nam = 0;
			
			foreach($rs_con_camp as $row_rs_con_camp){?>
			<tr>
			 <?php
				 $cont = 0;
				while($nam < $total_rs_con_camp && $cont < 1){
			 ?>
                    <td width="17%" class="Etiqueta1"><?php if($row_rs_con_camp['Cam_Est'] == 'I'){ $rojo='#FF0000'; $isact ='F';}else{$rojo=''; $isact ='T';} ?><?php if($row_rs_con_camp['Cam_Req'] == 'R'){
                    echo "<span class=\"Asterisco\">* </span>";  $str = $str.'cam_r['.$r.']*';?>
                    <?php echo $row_rs_con_camp['Cam_Cor'].":"; ?>	
                    <td width="54%" class="LetraNegra">&nbsp; <? 
				/** 
				 * Consulta los campos del activo.
				 */
				$rs_det_camp = $obBD_con1->getRowConsulta(430,$row_rs_con_camp['Cam_Cod'].'*'.$rs_consultar["Act_Cod"], $obBD_conexion);
				$total_rs_det_camp = count($rs_det_camp);
				$row_rs_det_camp = $rs_det_camp;
				
				echo $row_rs_det_camp["Act_Val"]; ?></td>
                <?php
					}else{
					$rs_det_camp = $obBD_con1->getRowConsulta(430,$row_rs_con_camp['Cam_Cod'].'*'.$rs_consultar["Act_Cod"], $obBD_conexion);
					$total_rs_det_camp = count($rs_det_camp);
					$row_rs_det_camp = $rs_det_camp;
					echo $row_rs_con_camp['Cam_Cor']." :"; ?>	
					<td width="29%" class="LetraNegra">&nbsp;<?php echo $row_rs_det_camp["Act_Val"];?></td>
				<?
					$i++;
				}
					$cont++;
					$nam++;	
					$row_rs_det_camp = $rs_det_camp;
					//$row_rs_con_camp = first_last($rs_con_camp, $row_rs_con_camp, $nam);	
				}
			 	?>
              </tr>
			  <?php
			  	$row_rs_con_camp  = $rs_con_camp ;
			  }//Fin Foreach($rs_con_camp as $row_rs_con_camp ) {
			  ?>
            </table></td>
	      </tr>
    </table> 
</fieldset>
 </fieldset>

<br> 
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
	  <td width="9%">
	  <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1"> 
	  <button type="button" name="btn_atras" id="btn_atras" value="Enviar" class="btn btn-inverse fileinput-button" title="Atr&aacute;s"
  onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver*Cam_Cod"; ?>','<?Php echo $volver_busqueda.'*'.$volver_opciones.'*'.'1'.'*'.$Cam_Cod; ?>')">
  <i class="icon-arrow-left icon-white"></i>
    <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
  </button>
   
  </form></td>
      <td width="91%" height="38">
		<form method="post" name= "form3" action="act_pri_campos_indv_2.0.php" target="_blank"> 
	  	  <button name="boton_imprimir" id="boton_imprimir" type="submit" class="btn btn-primary start" value="Imprimir" title="Imprimir" >
          <i class="icon-print icon-white"></i>
           <span>Imprimir</span>
          </button>
		   <input name="codigo" type="hidden" id="codigo" value="<?Php echo $codigo; ?>">
		 </form> </td>
    </tr>
  </table>
<input name="Act_Cod" type="hidden" id="Act_Cod" value="<? echo $rs_consultar["Act_Cod"]; ?>">

</fieldset>
	</td>
  </tr>
</table>
<? } ?>
	    
</BODY>
</HTML>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();

	break;
	
	case 2:
?>
  <fieldset>
  <LEGEND>
    <label class="Titulos2">Buscar por:&nbsp;&nbsp;</label>
   </LEGEND>
   <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']."?op=2";?>">
     <table height="35" width="680" border="0" cellpadding="0" cellspacing="0">
       <tr>
         <td  class="BarraBusqueda"><div align="left"><span class="Asterisco">*</span> Departamento: </div></td>
         <td  class="BarraBusqueda">
             <select name="txt_bus" id="txt_bus" >
            <?php 
				foreach($rs_depar as $row_rs_depar){?>
               <option <? if($row_rs_depar['Dep_Cod'] == $txt_bus){echo " selected";}?> value="<?php echo $row_rs_depar['Dep_Cod']?>"  ><?Php echo $row_rs_depar['Dep_Des']?>
               </option>
               <?php
				} // Fin  foreach($rs_depar as $row_rs_depar) 
				?>
           </select>          
          </td>
          <td class="BarraBusqueda" align="left">
                     <div align="center"> 
                       <button  name="btn_aceptar" type="submit" class="btn btn-success fileinput-button" id="btn_aceptar" value="Aceptar" title="Aceptar">
                         <i class="icon-search icon-white"></i>
                         <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>
                       </button>
                     </div>
         </td>
       </tr>
     </table>
   </form>
</fieldset>	
<?php 
	if(isset($txt_bus))
	{ 
	
		/**
		 * Consultar Departamentos por C�digo
		 */
		 $rs_Depar= $obBD_con1->getRowConsulta(666,$txt_bus,$obBD_conexion);
?>
<!--<div id="Exportar_a_Excel">-->
<fieldset>
  <LEGEND>
    <label class="LetraNegra">Activos ubicados en: <? echo $rs_Depar['Dep_Des']?></label>
</LEGEND>
  	
    <? //if ($total_rs_bus > 0 )
		//{
				//foreach($rs_bus as $row_rs_bus){
				$cantidad = 0;
				$td = 0;
					/** 
					 * Consulta tipo de activo x su departamento
					 */
					$rs_tip = $obBD_con1->getArrayConsulta(440,$txt_bus,$obBD_conexion);
					$total_rs_tip = count($rs_tip);
					
					if($total_rs_tip > 0){ ?>
						
					<?
						foreach($rs_tip as $row_rs_tip){
							// buscar el subgrupo de este  tipo de activo
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
                        /**
                         * seleccionar toodos los campos 
                         */
                        $rs_camp = $obBD_con1->getArrayConsulta(419,$row_rs_tip['Tia_Cod'], $obBD_conexion);
                        $total_rs_camp =  count($rs_camp);
                        $btn_print = true; 
                      ?>
                    <table width="100%" border="1" cellpadding="0" cellspacing="0" >
                            <tr class="Cabecera1">
                                 <td>C&oacute;d. Int.</td>
                                 <td>Secuencial</td>
                                 <td>Descripci�n</td>
                                 <td>Fecha Adqusici�n</td>   
                                 <td>Vida Util(a�os)</td>
                                 <td align="left">Observaci�n</td>
                                
                                <? if($total_rs_camp > 0){									
										foreach($rs_camp as $row_rs_camp){
										?>
											<td ><? echo $row_rs_camp['Cam_Cor']; $td +=1; ?></td>
									   <? }//if($total_rs_camp > 0){
									 }?>
								<td align="center">Cantidad</td>
                             </tr>  
                            
                             <? if($total_rs_act > 0){
								 		$act_val = 0;
										$act_res = 0;
									foreach($rs_act as $row_rs_act){
									?>
                                   	
                                		<tr <?Php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
                                        	<td align="center"><? echo $row_rs_act['Act_Cod'];?></td>
                                            <td align="center"><? echo $row_rs_act['Act_Cdc'];?></td>
                                            <td align="left"><? echo $row_rs_act['Act_Des'];?></td>
                                            <td align="center"><? echo $row_rs_act['Act_Fec'];?></td>
                                            <td align="center"><? echo $row_rs_act['Act_Ann'];?></td>
                                            <td><? echo $row_rs_act['Act_Obs'];?></td>                                            
                                            <?Php
											foreach($rs_camp as $row_rs_camp){								
												$rs_det_camp = $obBD_con1->getRowConsulta(430,$row_rs_camp['Cam_Cod'].'*'.$row_rs_act["Act_Cod"], $obBD_conexion);
												$total_rs_det_camp = count($rs_det_camp);	
												$row_rs_det_camp  = $rs_det_camp;			
												if($total_rs_det_camp > 0){
													 echo "<td align='left'>" .$row_rs_det_camp['Act_Val']."</td>";										
												}
												else
												{
													 echo "<td align='left'> &nbsp; </td>";
												}
												?>
										<? }// fin foreach( $rs_camp as $row_rs_camp);
									?>
								<td align="center" ><? echo $row_rs_act['Act_Can']; $cantidad += $row_rs_act['Act_Can']; ?></td>
								</tr>		
									<? }// fin foreach( $rs_act as $row_rs_act);
								} //if($total_rs_act > 0){   
								?>
                              <tr class="Fondo">
                             
                                 <td align="right" colspan="<? echo $td+6;?>">Totales: </td>
                                 <td align="center" colspan="<? echo $td+6;?>"><? echo $cantidad; $cantidad = 0;?></td>
                                <? if($td > 0 ){
									$td = 0;
								   }
								?>
                               
                             </tr>                            
                         </table>
          				<p>
                       <? 
						}//foreach($rs_tip as $row_rs_tip)
					} //if($total_rs_tip > 0)
					else{
					?>
						<label class="Titulos2">No se han encontrado registros de Activos!!</label>
				<?	}	// fin if($total_rs_tip > 0)
			//}//while($row_rs_bus = $obBD_con1-> fetch_assoc($rs_bus));
	   //}
	    //else { ?>
              
          <? //} ?>
</fieldset>     
<!--</div> -->    
<?Php
	if($btn_print == true){ ?>
    <table>
    	<tr>
        	<td>
            <form method="post" name= "form3" action="act_pri_campos_det_2.0.php" target="_blank">   
				  <button name="bnt_print" type="submit" class="btn btn-primary start" id="bnt_print" value="Imprimir" title="Imprimir">
			      	 <i class="icon-print icon-white"></i>
			           <span>Imprimir</span>
			      </button>
	  				<input name="txt_bus" type="hidden" id="txt_bus" value="<?Php echo $txt_bus; ?>">
 			</form>   
            </td>
            <td>
            <form action="act_exp_campos_det_2.0.php" name="form15" method="post" target="_blank" id="FormularioExportacion">
			  <button name="bnt_exp" type="submit" class="btn btn-primary start" id="bnt_expor" value="Exportar" title="Exportar">
			      	 <i class="icon-upload icon-white"></i>
			           <span>Exportar Excel</span>
			      </button>
	  				
			 
			 <!-- <input name="Boton_Excel" type="button" class="Boton_Excel" id="Boton_Excel"   value="Imprimir" title="Exportar a excel">-->
			<input name="txt_bus" type="hidden" id="txt_bus" value="<?Php echo $txt_bus; ?>">
			</form>
            </td>
        </tr>
    </table>
    
  <?php 
	 }
}// fin de if(isset($txt_bus))
?>
<br/> 
</p>
</BODY>
</HTML>
<?php

/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();

	break;
	
	case 3:	
		$rs_dep = $obBD_con1->getArrayConsulta(445,'', $obBD_conexion);
		$total_rs_dep = count($rs_dep);
		
		$act_val = 0;
		$act_res = 0;
 ?>
  </p>
   <p>&nbsp; </p>
  <table width="100%" border="1"  align="center" class="fixedHeader01">
		<thead>
		  <th>Departamento</th>
          <th align="center">Valor Actual</th>
		  <th align="center">Valor Residual</th>
        </thead>
	  <?Php 
	  if ($total_rs_dep > 0){  		
		  foreach($rs_dep as $row_rs_dep){
	  ?>
      <tbody>
	  <tr class="Fondo">
	 	 <td><?php echo strtoupper($row_rs_dep['Dep_Des']);?></td>
	  	 <td align="right"><?Php echo formato_numero($row_rs_dep['Act_Val'],2,2); $act_val += $row_rs_dep['Act_Val'];?></td>
		 <td align="right"><?Php echo formato_numero($row_rs_dep['Act_Res'],2,2); $act_res += $row_rs_dep['Act_Res'];?></td>
	  </tr>
	  <?Php }// while ($row_rs_dep = $obBD_con1->fetch_assoc($rs_dep)); 
	  ?>
      <tr class="Fondo">
		  <td align="right">TOTAL :</td>
          <td align="right"><b><? echo formato_numero($act_val,2,2);?></b></td>
		  <td align="right"><b><? echo formato_numero($act_res,2,2);?></b></td>
      </tr>
      <?
  	  }else{
  	  ?>
      	<tr>
      		<td><?Php echo error_alerta("�No hay resultados que mostrar!", 1) ?></td>
      		<td>&nbsp;</td>  
      		<td>&nbsp;</td>  		
      	</tr>
      <? } // fin del if ($total_rs_buscar > 0)?>
       </tbody>
	</table>
	<table>
    	<tr>
        	<td>
            <form method="post" name= "form3" action="act_pri_unificacion_1.0.php" target="_blank">   
		  		<button name="bnt_print" type="submit"  class="btn btn-primary start" id="bnt_print" value="Imprimir" title="Imprimir">
	                <i class="icon-print icon-white"></i>
	                <span>Imprimir</span>
	            </button>
		  	</form>   
            </td>
         </tr>   
    </table>
 
	
</body>
</html>
<?Php 

/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();

	break;
	
	case 4:

			$Ses_Dat_Dis ="ecu911";
		    /**
			 * Creacion del Objeto de conexion 
			 */
			$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis); 
			/**
			 * Cracion del objeto mysql para las consultas 
			 */
			$obBD_con1=  new Class_Log_Datos_Con;
	$hoy = date("Y-m-d");
	
	/**
	 * Consulta las Sucursales 
	 */
	$rs_suc_act = $obBD_con1->getArrayConsulta(422,$Ses_Emp_Cod,$obBD_conexion);
	//$row_rs_suc_act = $obBD_con1->registros();
	$total_rs_suc_act = $obBD_con1->numregistros();
	
	/*
	 * Consulta de la cabecera del reporte 
	 */
	$rs_institucion = $obBD_con1->getRowConsulta(134,$Ses_Suc_Cod, $obBD_conexion);
	//$row_rs_institucion= $obBD_con1->registros();
	//$total_rs_institucion = $obBD_con1->numregistros();
	  	  
	function sumar_nodos($cod,$np, $obBD_con1, $obBD_conexion, $suma)
	  {
		    $Ses_Dat_Dis ="ecu911";
		    /**
			 * Creacion del Objeto de conexion 
			 */
			$obBD_conexion2 = new Class_Log_Conexion_Con($Ses_Dat_Dis); 
			/**
			 * Cracion del objeto mysql para las consultas 
			 */
			$obBD_con2 =  new Class_Log_Datos_Con;
			
		  
			$rs_nodosrep = $obBD_con2->getArrayConsulta(473,$cod.'*'.$np, $obBD_conexion2);
			//$row_rs_nodosrep = $obBD_con1->fetch_assoc($rs_nodosrep);
			//$total_rs_nodosrep = $obBD_con1->num_rows($rs_nodosrep);
			$total_rs_nodosrep =  count($rs_nodosrep);
			
			if ($total_rs_nodosrep > 0)
			{
					//do{
						foreach($rs_nodosrep as $row_rs_nodosrep){
							if ($row_rs_nodosrep['Tia_Tip']=='D')
							{
								$rs_val = $obBD_con2->getRowConsulta(474, $row_rs_nodosrep['Tia_Cod'], $obBD_conexion2);
								//$row_rs_val = $obBD_con1->fetch_assoc($rs_val);
								//$total_rs_val = $obBD_con1->num_rows($rs_val);	
								$total_rs_val = count($rs_val);							
							}
							                               
						$suma += sumar_nodos($cod,$row_rs_nodosrep['Tia_Cod'], $obBD_con2, $obBD_conexion2,$rs_val['Act_Val']);
												
					} //while($row_rs_nodosrep=$obBD_con1->fetch_assoc($rs_nodosrep));
			}
			return $suma;
	  }//function cargar_nodos($cod,$np)
	  
	  function cargar_nodos($cod,$np, $obBD_con1, $obBD_conexion)
	  {
		 
		   $Ses_Dat_Dis ="ecu911";
		    /**
			 * Creacion del Objeto de conexion 
			 */
			$obBD_conexion3 = new Class_Log_Conexion_Con($Ses_Dat_Dis); 
			/**
			 * Cracion del objeto mysql para las consultas 
			 */
			$obBD_con3 =  new Class_Log_Datos_Con;
		  
		  
			//$j=$j+1;
			//$espacios=str_repeat("&nbsp;", strlen($cuenta));
			$rs_nodosrep = $obBD_con3->getArrayConsulta(473,$cod.'*'.$np,$obBD_conexion3);
			//$row_rs_nodosrep = $obBD_con1->fetch_assoc($rs_nodosrep);
			//$total_rs_nodosrep = $obBD_con1->num_rows($rs_nodosrep);
			$total_rs_nodosrep =count($rs_nodosrep);
			
			if ($np == 0)
			{
			?>
			<table width="100%" cellpadding="0" cellspacing="0" border="0" class="LetraNegra">
				<tr class="Cabecera1">
					<td width="10%" align="left">C&oacute;digo</td>
					<td width="50%" align="center">Descripci�n</td>
					<td width="40%" align="center">Valor Actual</td>
				</tr>	
			<?Php
			}
			
			if ($total_rs_nodosrep > 0)
			{
				//$espace = 0;
					//do{
						foreach($rs_nodosrep as $row_rs_nodosrep){
						?>
						<tr>
						<?
							/* Control para agregar cero a las cuentas de detalle */
							if ($row_rs_nodosrep['Tia_Tip']=='D')
							{
								$cuenta = mascara_cuenta($row_rs_nodosrep['Tia_Cdc']);
							}
							else
							{
								$cuenta = $row_rs_nodosrep['Tia_Cdc'];						
							}	
							/******************************************************/
						?>
                      		<td><?Php echo $cuenta; ?></td>
							<td><?Php 
								$espacios1=str_repeat("&nbsp;", strlen($cuenta));
								$espacios=str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", strlen($cuenta));
								echo $espacios1.$row_rs_nodosrep['Tia_Des'];?>
                            </td>
							<?php if ($row_rs_nodosrep['Tia_Tip'] == 'D'){ //echo "Detalle"; 
								$rs_val = $obBD_con3->getRowConsulta(474, $row_rs_nodosrep['Tia_Cod'], $obBD_conexion3);
								//$row_rs_val = $obBD_con1->fetch_assoc($rs_val);
								//$total_rs_val = $obBD_con1->num_rows($rs_val);
								$total_rs_val = count($rs_val);
								}else{ 
								  	if ($row_rs_nodosrep['Tia_Tip'] == 'G'){ echo "GRUPO";								
									}
								}
							//if($ban == true){
							?>	
                        	 <td align="right" >
								<? //echo $row_rs_val['Act_Val']; 
									$suma = sumar_nodos(0,$row_rs_nodosrep['Tia_Cod'], $obBD_con3, $obBD_conexion3, 0);
									$sumatotal = sumar_nodos(0,$row_rs_nodosrep['Tia_Cod'], $obBD_con3, $obBD_conexion3, $sumatotal);
									//$espacios = str_repeat("&nbsp;", strlen($suma));
									if($suma != 0){
										echo $suma.$espacios;
										//$espace++;
									}else{
											echo $rs_val['Act_Val'].$espacios;										
										}
									//$espacios = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $espace);

								?>
                        	</td>
                       <?
					  	//}
						//echo $row_rs_nodosrep['Tia_Des']."<br>";	.
						//$sum = cargar_valores($cod,$row_rs_nodosrep['Tia_Cod'], $obBD_con1, $obBD_conexion, 0).'<br>';
						//echo $sum;
//						echo ''.sumar_nodos(0,$row_rs_nodosrep['Tia_Cod'], $obBD_con1, $obBD_conexion, 0);
						
						cargar_nodos($cod,$row_rs_nodosrep['Tia_Cod'], $obBD_con3, $obBD_conexion3);
						?>
                        	</tr>
                        <?
					} //while($row_rs_nodosrep=$obBD_con1->fetch_assoc($rs_nodosrep));
			}
			else
			{
				
			}
			
			if ($np == 0)
			{
				
			?>
            <tr>
                <td></td>
                <td align="right"><strong><? echo "TOTAL :" ?></strong></td>
                <td align="right"><? echo $sumatotal.$espacios; ?></td>
			</tr>	
             
			</table>
			<?Php				
			}//Fin del if ($np == 0)
	  }//function cargar_nodos($cod,$np)
	  
	  /*if (isset($codigo))
	  {
	  		// Cargado de la cabecera del Reporte del Plan de Cuenta
			$rs_cabplan = consultas_con(314,$codigo.'*'.$codemp);
			$row_rs_cabplan = mysqli_fetch_assoc($rs_cabplan);
			$total_rs_cabplan = mysqli_num_rows($rs_cabplan);
	  } else { //header  ("Location: index.php");*/
	  //}
	  
	  
	  /** 
	   * Consultamos los datos de los Tipos de Activos
	   */
	   
	   $rs_tiposAct = $obBD_con1->getArrayConsulta(1010,$Ses_Emp_Cod,$obBD_conexion);
	   
	   
	       
						
	   
	   
	  
?>				

<table width="80%" border="0" cellpadding="0" cellspacing="0" align="center">
   		<tr>
        <td colspan="5" valign="top">
            <table width="70%" border="0" align="center" cellpadding="0" cellspacing="0" class="LetraPlan">
          <tr>
            <td>&nbsp;</td>
            <td></td>
            <td></td>
            <td>&nbsp;</td>
          </tr>
          <?Php 
		  foreach($rs_tiposAct as $row_rs_tiposAct){
			  
			   $cant_text= strlen($row_rs_tiposAct['Tia_Cdc']);
			   $bar_busq = cortar_cadena($cant_text, $cant_text, $txt_busqueda);
		  ?>
          <tr>
            <td> <?Php echo $row_rs_tiposAct['Tia_Cdc'];?></td>
            <td><?Php echo $row_rs_tiposAct['Tia_Des'];?></td>
            <td><?Php echo $row_rs_tiposAct['Tia_Tip'];?></td>
          </tr>
          <?Php 
		  }
		  ?>
          <tr class="Fondo">
            <td colspan="4"> <? //cargar_nodos($codigo,0, $obBD_con1, $obBD_conexion, ' ');?></td>
          </tr>
        </table>
        </td>
   </table>
   
   <br><br>

<table>
    	<tr>
        	<td><?Php echo " CODIGO :".$codigo;?>
            <form method="post" name= "form3" action="con_pri_act_1.0.php" target="_blank">   
	  			<input name="bnt_print" type="submit" class="Boton_Imprimir" id="bnt_print" value="Imprimir">
		  	</form>   
            </td>
            
    </table>

<?
	break; 
	}
}
?>

</td>
</tr>
</table>
</div>
<script type="text/javascript" src="../VALIDACIONES/act_par_campos_det.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	

</body>
</html>

</div>	    

	<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
	  	<div id="bgmodal"  class="bgmodal" style="display:none" >
	 		<div id="ajax_modal">
				<div id="muestra"></div>
	 		</div>
	 	</div>   

<?Php 	
/**
* cierro las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/**
* fin cierre las conexiones 
*/
?>