<?php 
/* 
 @Descripci�n: Permite el ingreso del detalle de los tipos de activos
 @Desarrollador:	Fabian Gallardo
 @Fecha de actualizaci�n:	2011-11-08
 @Desarrollador:	Didimo Zamora
 @Fecha de actualizaci�n:	2013/04/26
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

/**
 * Consulta de los Peritos 
 */
$rs_pri_act = $obBD_con1->getArrayConsulta(421,$Ses_Emp_Cod,$obBD_conexion);
$total_rs_pri_act = count($rs_pri_act);
/**
 * Consulta las Sucursales 
 */
$rs_suc_act = $obBD_con1->getArrayConsulta(422,$Ses_Emp_Cod, $obBD_conexion); 
$total_rs_suc_act =  count($rs_suc_act);
/**
 * Consulta los Estados 
 */
$rs_est_act = $obBD_con1->getArrayConsulta(423,'', $obBD_conexion);
$total_rs_est_act = count($rs_est_act);

/**
 * Consulta los Proveedores 
 */
$rs_prv_act = $obBD_con1->getArrayConsulta(424,$Ses_Emp_Cod, $obBD_conexion);
$total_rs_prv_act = count($rs_prv_act);

/**
 * Consulta el detalle de las secciones 
 */
$rs_seccion = $obBD_con1->getArrayConsulta(437, $Ses_Emp_Cod, $obBD_conexion);
$total_rs_seccion =  count($rs_seccion);

 if ($thisPost->postBlock($_POST['postID'])) 
 { 
 	
	
$rs_Max_act = $obBD_con1->getRowConsulta(659,'', $obBD_conexion);			 
	 
		
		if (isset($hdd_save) && !isset($hdd_volver)) { 
			$Act_var ='';
			$Act_gen ='';			
			if($Act_Gen==1)
			{
				switch ( strlen($Act_Cod)) {
					case 1:
					 $Act_var="00000000000".$Act_Cod;
					break;
					case 2:
					 $Act_var="0000000000".$Act_Cod;
					break;
					case 3:
					 $Act_var="000000000".$Act_Cod;
					break;
					case 4:
					 $Act_var="00000000".$Act_Cod;
					break;
					case 5:
					 $Act_var="0000000".$Act_Cod;
					break;
					case 6:
					 $Act_var="000000".$Act_Cod;
					break;
					case 7:
					 $Act_var="00000".$Act_Cod;
					break;
					case 8:
					 $Act_var="0000".$Act_Cod;
					break;
					case 9:
					 $Act_var="000".$Act_Cod;
					break;
					case 10:
					 $Act_var="00".$Act_Cod;
					break;
					case 11:
					$Act_var="0".$Act_Cod;
					break;
				}
					$Act_Bar = $Act_var;
					$Act_gen = 'G';
				}
				else
				{
					$Act_gen = 'M';
				}
			/**
			 * Eliminacion de los campos  registrados actualmente
			 */
						
			/**
			 * Modificacion del activo
			 */
			 
			 if(trim($archivo)<>''){			 
			 /**
			  *Variable que me indica si la imagen se subio correctacmente		
			  */		  
				$flag=upLoadImg($archivo,$Act_Cod,51200,"fotos/");
				if($flag!="0" )
				{	
					$Act_Fot =$flag;
				}
				
			 }
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			$obBD_con1->operacionobBD(433,$Pri_Cod.'*'.$Suc_Cod.'*'.$Est_Cod.'*'.$Prv_Cod.'*'.$Act_Des.'*'.$Act_Obs.'*'.$Act_Cdc.'*'.$Act_Can.'*'.$Act_Bar.'*'.$Act_gen.'*'.$Act_Cod.'*'.$Act_Val.'*'.$Act_Res.'*'.$Act_Ann.'*'.$Act_Fec.'*'.$Act_Gar.'*'.$Act_Fot,$obBD_conexion); 			
			
				if($confir == 'N'){
					/** 
					 * Modificacion de la asignacion
					 */	
					$obBD_con1->operacionobBD(470,$Sec_Cod.'*'.$Cus_Cod.'*'.$Act_Cod,$obBD_conexion); 
				}
			for ($j=1; $j<= $r; $j++)
			{			
				if (isset($cam_r[$j]))
				{		
					$rs_con_camp = $obBD_con1->getArrayConsulta(430,$cam_rc[$j].'*'.$Act_Cod, $obBD_conexion);
					$total_rs_con_camp = count($rs_con_camp);
					if($total_rs_con_camp > 0){
					/**
					 * Inserci�n de cada campo
					 */					
						$obBD_con1->operacionobBD(434,$cam_r[$j].'*'.$cam_rc[$j].'*'.$Act_Cod, $obBD_conexion);
					}else{
						/**
						 * Inserci�n de cada campo
						 */
						$obBD_con1->operacionobBD(420,$Act_Cod.'*'.$cam_rc[$j].'*'.trim($cam_r[$j]), $obBD_conexion);
					}
				}
			}
			for ($k=1; $k<= $i; $k++)
			{			
				if (isset($cam[$k]))
				{		
					$rs_con_camp = $obBD_con1->getArrayConsulta(430,$cam_c[$k].'*'.$Act_Cod, $obBD_conexion);
					$total_rs_con_camp = count($rs_con_camp);
					if($total_rs_con_camp > 0){
					  /** 
					   * Inserci�n de cada campo
					   */
						$obBD_con1->operacionobBD(434,$cam[$k].'*'.$cam_c[$k].'*'.$Act_Cod, $obBD_conexion);
					}else{
						/** 
						 * Inserci�n de cada campo
						 */
						$obBD_con1->operacionobBD(420,$Act_Cod.'*'.$cam_c[$k].'*'.trim($cam[$k]), $obBD_conexion);
					}
				}
			}
		 $obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'],$Ses_Usu_Cod, $obBD_conexion);
		 $obBD_con1->fin_transaccion($obBD_conexion->conexion);		 
	} 
	  /**
	   *
	   */
	 if($txt_busqueda != "")
	 { 
	 	if ($op_opciones == "d")
		{
			/**
			 * Busqueda del activo x medio de la descripcio
			 */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(429,$txt_busqueda, $obBD_conexion);
		}
		if ($op_opciones == "cs")
		{
			/**
 			 * Busqueda del activo x medio del codigo secuencial
			 */
			$rs_buscar = $obBD_con1->getArrayConsulta(435,$txt_busqueda, $obBD_conexion);		
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
			 * Busqueda del activo x medio del codigo de barra
			 */
			$rs_buscar = $obBD_con1->getArrayConsulta(436,$bar_busq, $obBD_conexion);		
		}
		
		if ($op_opciones == "ns")
		{
			if (isset($Cam_Cod)){
			 /**
			  * Busqueda del activo x medio del codigo de barra
			  */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(644,$Cam_Cod.'*'.$txt_busqueda, $obBD_conexion);		
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
				$row_rs_consultar = $obBD_con1->getRowConsulta(431,$codigo,$obBD_conexion);	
				$total_rs_consultar = count($row_rs_consultar);
				
				$rs_asig = $obBD_con1->getRowConsulta(469,$codigo,$obBD_conexion);
				$total_rs_asig =  count($rs_asig);
				$row_rs_asig  = $rs_asig;
			}
		}	
}
?> 
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript" src="../VALIDACIONES/act_val_campos_det.js"></script>  
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
		
		<!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
        <script>
		
	    $(function() { 
			/* Campo 1 */
			$( "#Act_Fec" ).datepicker({
				changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});
		 });
        </script> 
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Modificar Activo Fijo </td>
  </tr>
	<tr>
	  	<td valign="top">
  
  <fieldset>
  <LEGEND>
    <label class="Titulos2">Buscar por:&nbsp;</label>
   </LEGEND><form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
   <table width="633" height="62" border="0" cellpadding="0" cellspacing="0">
    <tr>
		<td colspan="2">
		<table width="630" border="0">
             <tr>
                <td width="130"><input name="op_opciones" type="radio" value="d" checked   onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">
                    <span class="LetraNegra">Descripci�n</span>
                    	<input name="op_cam" id="op_cam" type="hidden" value="d">
                    </td>
                <td width="141"><input type="radio" name="op_opciones" value="cb" onClick="document.getElementById('op_cam').value=this.value;setfocus(this.form.txt_busqueda); busquedaCampos();">
                
                    <span class="LetraNegra">C�digo de Barra</span></td>
				<td width="148"><input type="radio" name="op_opciones" value="cs" onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">
                    <span class="LetraNegra">C�digo Secuencial</span></td>
             
              <td width="305"><input type="radio" name="op_opciones" value="ns" onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.Cam_Cod);">
                    <span class="LetraNegra">Por Campos</span>
                    <?Php
					/**
					 * consulto los campos que esten definidos como busqueda 
					 */
					$rs_campos=$obBD_con1->getArrayConsulta(643, '', $obBD_conexion);
				?>
                    <select name="Cam_Cod" id="Cam_Cod" onChange="setfocus(this.form.txt_busqueda);">
                            <?Php foreach($rs_campos as $row_rs_campos){?>  
                              <option  value="<?php echo $row_rs_campos['Cam_Cod'];?>"><?PHP  echo $row_rs_campos['Cam_Cor'];?></option>
                             <?Php 
                                } //fin foreach($rs_campos as $row_rs_campos){
                              ?> 
                    </select>
                   </td>
                    	
                 </tr> 
            </table>
		</td>
	</tr>
	<tr>
      <td width="111" height="34"class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Activo: </div></td>
      <td width="522" class="BarraBusqueda">&nbsp;<input name="txt_busqueda" type="text" id="txt_busqueda" size="50">&nbsp;&nbsp;
        <button name="btn_aceptar" type="submit"  class="btn btn-success fileinput-button" id="btn_aceptar" value="Aceptar" title="Aceptar">
          <i class="icon-ok icon-white"></i>
  <span>&nbsp;&nbsp;Aceptar&nbsp;&nbsp;</span>
        </button>
      </td>
      
      </tr>
  </table>
   <script> 
  	ShowHide('Cam_Cod');
  </script>
  
  </form>
  
<?php if (isset($txt_busqueda))
{
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table class="fixedHeader01" cellpadding="0" cellspacing="0" width="100%">
    <thead>
	  <tr>
		  <th width="11%">C�d. Int.</th>
          <th width="23%">Tipo de Activo  </th>
		  <th width="31%">Descripci&oacute;n </th>
		  <th width="30%">Secuencial</th>
		  <th width="5%">&nbsp;</th>
      </tr>
      </thead>
      <tbody>
	  <?Php 
	  if ($total_rs_buscar > 0){  		
	  foreach($rs_buscar as $row_rs_buscar)
		{
		  if($row_rs_buscar['Act_Est']=='Inactivo')
	  	   { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
	  ?>
	  <tr>
	  <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row_rs_buscar['Act_Cod'];?></FONT></td>
	  <td><FONT COLOR="<?php echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Tia_Des'],'#FFFF00', 1);?></FONT></td>
	 <td><FONT COLOR="<?php echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Act_Des'],'#FFFF00', 1);?></FONT></td>
	 <td><FONT COLOR="<?php echo $rojo;?>"><?php echo $row_rs_buscar['Act_Cdc'];?></FONT></td>
	  <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "frml" id="forml">
	  <td align="center" width="3%"> 
      <?Php if($row_rs_buscar['Act_Est']=='Activo')
		  { ?>
            <button type="button" class="btn btn-primary btn-mini"  title = "editar" onClick="this.form.submit()" >
            	<i class=" icon-edit icon-white"></i>
			</button>			
            <input type="hidden" name="codigo" id="codigo" value="<?Php echo $row_rs_buscar['Act_Cod'];?>"/>
            <input type="hidden" name="hdd_aux" id="hdd_aux" value="1">
			<input type="hidden" name="volver_busqueda" id="volver_busqueda" value="<?Php echo $txt_busqueda;?>"/>
			<input type="hidden" name="volver_opciones" id="volver_opciones" value="<?php echo $op_opciones?>">
            <input type="hidden" name="Cam_Cod" id="Cam_Cod" value="<?Php echo $Cam_Cod;?>">
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
	  <?Php } 
  	  }
	  else
	  {
  	  ?>
      	<tr><td>&nbsp;</td>
      	  <td>&nbsp;</td>
      	  <td><?Php echo error_alerta("�No hay resultados que mostrar!", 1) ?></td>
      	  <td>&nbsp;</td>
      	  <td>&nbsp;</td>
      	</tr>
      <?php 
	  } // fin del if ($total_rs_buscar > 0)?>
      </tbody>
	</table>
    <?Php
	/**
	 * Muestra la barra de estados con la cantidad de registros encontrados 
	 */
	echo barra_estado($total_rs_buscar+0);
	?>
</FIELDSET>
<?php }
if ($hdd_aux==1) { ?>
<form method="post" name= "form2" action="<?php echo $_SERVER['PHP_SELF'];?>"  enctype="multipart/form-data"  >
<?php /** 
    * Creacion del campo REPOST
	*/
	$thisPost->startPost();
?>
<fieldset>
  <LEGEND>
    <label class="Titulos2">Datos a registrar&nbsp;</label>
</LEGEND>
  	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td colspan="3"><?Php echo mensaje_requerido(); ?></td>
	</tr>
	<tr>
		<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span> C�digo Activo:</td>
		<td>&nbsp;<input name="Act_Cdc" type="text" id="Act_Cdc" value="<?php echo $row_rs_consultar["Act_Cdc"]?>" size="40"></td>
		<td></td>
	</tr>
	<tr>
	  <td class="Etiqueta1"><span class="Asterisco">*</span> Tipo de Activo:</td>
	  <td>&nbsp;
	    <label class="Titulos2"> <span class="LetraNegra"><?php echo $row_rs_consultar["Tia_Des"];?></span></label></td>
	  <td></td>
	  </tr>
	<tr>
	  <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span> Descripci�n:</td>
	  <td>&nbsp;<input name="Act_Des" size="50" type="text" id="Act_Des" value="<?php echo $row_rs_consultar["Act_Des"]?>"></td>
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
    <td>&nbsp;<select name="Suc_Cod" id="Suc_Cod"> 
        <?php
			
			foreach($rs_suc_act as $row_rs_suc_act){	  
		?>
        <option <?php if($row_rs_suc_act['Suc_Cod'] == $row_rs_consultar["Suc_Cod"] ){echo "selected";}?> value="<?php echo $row_rs_suc_act['Suc_Cod']?>"><?Php echo $row_rs_suc_act['Suc_Des']?> </option>
        <?php
			} 
	?>
      </select></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
      <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Proveedor :</td>
	  <td>&nbsp;<select name="Prv_Cod" id="Prv_Cod">
        <?php  
			foreach($rs_prv_act as $row_rs_prv_act){
		?>
        <option <?php if($row_rs_prv_act['Prv_Cod'] == $row_rs_consultar["Prv_Cod"] ){echo "selected";}?>  value="<?php echo $row_rs_prv_act['Prv_Cod']?>"><?Php if (!empty($row_rs_prv_act['Prv_Com'])){ echo $row_rs_prv_act['Prv_Com'].":: "; } echo $row_rs_prv_act['Nombre']; ?>  </option>
        <?php
			} 
	?>
      </select></td>
	  <td></td>
  </tr>
  <tr>
    <td width="20%"  class="Etiqueta1"><span class="Asterisco">*</span> Perito :</td>
    <td width="52%">&nbsp;<select name="Pri_Cod" id="Pri_Cod">
        <?php
			foreach($rs_pri_act as $row_rs_pri_act){  
		?>
        <option <?php if($row_rs_pri_act['Pri_Cod'] == $row_rs_consultar["Pri_Cod"] ){echo "selected";}?> value="<?php echo $row_rs_pri_act['Pri_Cod']?>"><?Php echo $row_rs_pri_act['Prs_Ape'].' '.$row_rs_pri_act['Prs_Nom']?></option>
        <?php
			} 
	?>
      </select>
    </td>
    <td width="33%">&nbsp;</td>
  </tr>
  <tr>
      <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Custodio :</td>
	  <td class="LetraNegra">&nbsp;<?php 
	  
	  	if($row_rs_asig['Asg_Con'] == 'N' or 1==1){
			/** 
			 * Consulta el  Custodio 
			 */
			$rs_cus_act = $obBD_con1->getArrayConsulta(425,$Ses_Emp_Cod, $obBD_conexion);
			?> <select name="Cus_Cod" id="Cus_Cod">
            <option value="">Seleccione...</option>
            <?php
			foreach($rs_cus_act as $row_rs_cus_act){	  
			?>
            <option value="<?php echo $row_rs_cus_act['Cus_Cod'];?>" 
			<?php if($row_rs_cus_act['Cus_Cod'] == $row_rs_asig['Cus_Cod']){ echo "selected"; } ?> ><?Php echo $row_rs_cus_act['Nombre'];?> </option>
            <?php
			} 
			?>
        </select> <?php
		}
		else{
			/**
			 * Consulta el  Custodio 
			 */
				$rs_cus_act = $obBD_con1->getRowConsulta(1,$row_rs_asig['Cus_Cod'],$obBD_conexion);
				echo $row_rs_cus_act["Nombre"];
		}
	  	?></td>
	  <td></td>
  </tr>
  <tr>
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Secci�n:</td>
        <td colspan="2" class="LetraNegra">&nbsp;<?php 
        if($row_rs_asig['Asg_Con'] == 'N' or 1==1){ ?>
          <select name="Sec_Cod" id="Sec_Cod">
            <option value="">Seleccione...</option>
            <?php
			foreach($rs_seccion as $row_rs_seccion ){	  
			?>
            <option value="<?php echo $row_rs_seccion['Sec_Cod'];?>" 
			<?php if($row_rs_seccion['Sec_Cod'] == $row_rs_asig['Sec_Cod']){ echo "selected"; } ?> ><?Php echo $row_rs_seccion['Dep_Des'].' - '.$row_rs_seccion['Sec_Des']?></option>
            <?php
			} 
			?>
          </select>
          <?php }else{
			/** 
			 * Consulta la  seccion 
			 */
			$rs_sec = $obBD_con1->consulta(sentencias_con(465,$obBD_con1->parametros($row_rs_asig['Sec_Cod'])), $obBD_conexion->conexion);
			$row_rs_sec = $obBD_con1->registros();
			 echo $row_rs_sec["Dep_Des"].' - '.$row_rs_sec["Sec_Des"];
			}?></td>
        </tr>
  <tr>
      <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Estado :</td>
	  <td>&nbsp;<select name="Est_Cod" id="Est_Cod">
        <?php
			foreach($rs_est_act as $row_rs_est_act){  
		?>
        <option  <?php if($row_rs_est_act['Est_Cod'] == $row_rs_consultar["Est_Cod"] ){echo "selected";}?> value="<?php echo $row_rs_est_act['Est_Cod']?>"><?Php echo $row_rs_est_act['Est_Des']?> </option>
        <?php
			} 
		?>
      </select></td>
	  <td></td>
  </tr>
  <tr>
      <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Observaciones :</td>
	  <td>&nbsp;<textarea name="Act_Obs" id="Act_Obs" ><?php echo $row_rs_consultar["Act_Obs"]?></textarea></td>
	  <td></td>
  </tr>
  <tr>
      <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Cantidad :</td>
	  <td>&nbsp;<input name="Act_Can" type="text" id="Act_Can" onKeyPress="return validar_numeric(event)" value="<?php echo $row_rs_consultar["Act_Can"]?>" ></td>
	  <td></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> C&oacute;digo de barra:</td>
    <td colspan="5" class="LetraNegra"><table width="559" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="123">&nbsp;<input <?Php if($row_rs_consultar['Act_Gen']=="G"){echo "disabled='disabled'"; } ?> name="Act_Bar" type="text" id="Act_Bar" size="12" maxlength="12" value="<?php echo $row_rs_consultar['Act_Bar']; ?>" /></td>
          <td width="22"><input name="Act_Gen" type="checkbox" id="Act_Gen" onClick="check_generar()"  value="<?Php  if($row_rs_consultar['Act_Gen']=="G"){ echo $Act_Gen=1;}else{ echo $Act_Gen=0;} ?>"  <?Php if($row_rs_consultar['Act_Gen']=="G"){echo "checked"; } ?>>          </td>
          <td width="414"><div class="Cuerpo_ajax" id='contenedorcheck'><?Php  if($row_rs_consultar['Act_Gen']=="G"){ echo "Genera el c�digo del producto";}else{ echo "Ingrese el c�digo de barra del producto";} ?></div></td>
        </tr>
		
      </table></td>
	</tr>
  	<tr class="Etiqueta1">
		<td colspan="3" class="Etiqueta1"><div align="center"><?php 
	 		$varcode = $row_rs_consultar['Act_Bar'];
	  include("../../Librerias/barcode/generadorbarras.php") ?></div></td>
	</tr>
   </table>
  </fieldset>
<fieldset>
  <LEGEND>
    <label class="Titulos2">Contables</label>
   </LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="16%" align="right" class="Etiqueta1">Fecha adquisici�n :</td>
        <td width="84%" colspan="2">&nbsp;<input name="Act_Fec" type="text" id="Act_Fec" value="<?php echo $row_rs_consultar["Act_Fec"];?>" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);">  
        </td>
        </tr>
        <tr>
    	  <td align="right" class="Etiqueta1">Garant&iacute;a:</td>
    	  <td colspan="2">&nbsp;<select name="Act_Gar" id="Act_Gar">
          <?php
		  for ($i=0;$i<=60;$i++)
		  {
		  ?>
    	    <option value="<?Php echo $i; ?>"><?Php echo $i; ?> Mes(es)</option>
         <?Php
		  }
		 ?>
          </select></td>
  	  </tr>
      <tr>
      <tr>
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Valor Actual :</td>
        <td width="84%" colspan="2">&nbsp;<input name="Act_Val" type="text" id="Act_Val" value="<?php echo $row_rs_consultar["Act_Val"]; ?>" size="10" onKeyPress = "return validar_decimal(event)"></td>
        </tr>
		 <tr>
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Valor Residual :</td>
        <td width="84%" colspan="2">&nbsp;<input name="Act_Res" type="text" id="Act_Res" value="<?php echo $row_rs_consultar["Act_Res"]; ?>" size="10" onKeyPress = "return validar_decimal(event)"></td>
        </tr>
		 <tr>
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Vida �til :</td>
        <td width="84%" colspan="2">&nbsp;<input name="Act_Ann" type="text" id="Act_Ann" value="<?php echo $row_rs_consultar["Act_Ann"]; ?>" size="10" maxlength="3" onKeyPress="return validar_numeric(event)" ><span class="Etiqueta1"> A�os </span></td>
        </tr>
	</table>
	</fieldset>
     <fieldset>
  <LEGEND>
    <label class="Titulos2">Foto del Activo</label>
    </LEGEND>
    	<table width="100%" cellpadding="0" cellspacing="0" border="0">
    	<tr>
            <td width="8%" class="Etiqueta1">Elegir Foto:&nbsp;</td>
        	<td width="36%"  ><input name="archivo" type="file" class="Boton" id="archivo" />
            </td>
            <td width="56%"> 
            	<table width="50" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td><fieldset><img name="img" src="<?php echo $row_rs_consultar["Act_Fot"]?>" width="110" height="110" style="max-width: 90px; max-height: 100px; cursor:pointer;"  onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_Act=1&Act_Fot=<?php echo$row_rs_consultar["Act_Fot"];?>&Act_Des=<?php echo $row_rs_consultar["Act_Des"]?>','ajax_modal');" title="Ampliar imagen"  /></fieldset></td>
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
		 <td width="93%"></td>
    	</tr>
		<tr>
		  <td colspan="2" class="Etiqueta1"></td>
		  </tr>
		<tr>
		  <td width="5%" class="Etiqueta1">&nbsp;</td>
            <td><table width="75%" border="0" cellpadding="0" cellspacing="0">
            <?php  
				/**
				 * seleccionar toodos los campos 
				 */
				$rs_con_camp = $obBD_con1->getArrayConsulta(419,$row_rs_consultar["Tia_Cod"], $obBD_conexion);
				$total_rs_con_camp = count($rs_con_camp);
				$i = 1;
				$r = 1;
				$str ="";
				$nam = 0;
		foreach($rs_con_camp as $row_rs_con_camp){
				?>
			  <tr>
			  <?php
			 $cont = 0;
			while($nam < $total_rs_con_camp && $cont < 1){
			  ?>
                <td width="20%" class="Etiqueta1">
                 <?php "Es requeriodo :".$row_rs_con_camp['Cam_Req']; if($row_rs_con_camp['Cam_Est'] == 'I'){ 
				 		$rojo='#FF0000'; $isact ='F';
						}else{
						$rojo=''; $isact ='T';} ?>
				<?php if($row_rs_con_camp['Cam_Req'] == 'R'){
						echo "<span class=\"Asterisco\">* </span>";  $str = $str.'cam_r['.$r.']*';?>
				  <?php echo $row_rs_con_camp['Cam_Cor']." :"; ?>	
                <td width="80%">&nbsp;<input name="cam_rc[<?php echo $r;  ?>]" type="hidden" id="cam_rc[<?php echo $r; ?>]" value="<?php echo $row_rs_con_camp['Cam_Cod']; ?>">
					<?php $tipo = $row_rs_con_camp['Cam_Tip'];?>
                  <?php if($tipo != 'TX')
				  		{
					  		?>
                  			<input name="cam_r[<?php echo $r; ?>]" type="hidden" id="cam_r[<?php echo $r; ?>]"
				  			<?php if($tipo == 'NE'){echo "onKeyPress=\"return validar_numeric(event)\"";}
				  					if($tipo == 'ND'){echo "onKeyPress=\"return validar_decimal(event)\"";}
										$rs_det_camp = $obBD_con1->getRowConsulta(430,$row_rs_con_camp['Cam_Cod'].'*'.$row_rs_consultar["Act_Cod"], $obBD_conexion);	
										$total_rs_det_camp =  count($rs_det_camp);
							?>  value="<?php echo $rs_det_camp["Act_Val"]; ?>" >
                  <?php }else //caso contrario: if($tipo != 'TX')
				  		{
						$rs_det_camp1 = $obBD_con1->getRowConsulta(430,$row_rs_con_camp['Cam_Cod'].'*'.$row_rs_consultar["Act_Cod"], $obBD_conexion);
						$total_rs_det_camp1 = count($rs_det_camp1);  		
				  	?>
						<textarea name="cam_r[<?php echo $r ?>]"id="cam_r[<?php echo $r; ?>]" >
					<?php echo $rs_det_camp1["Act_Val"];?>
</textarea> 
						<?php 	
						}
						?> 
				</td>
                <?php
				$r++;
				}
				else{
					//si no es requerido el campo
					echo $row_rs_con_camp['Cam_Cor']." :"; ?>	
                	<td width="80%">&nbsp;<input name="cam_c[<?php echo $i ?>]" type="hidden" id="cam_c[<?php echo $i; ?>]" value="<?php echo $row_rs_con_camp['Cam_Cod']; ?>">
					<?php $tipo = $row_rs_con_camp['Cam_Tip'];?>
                  	<?php if($tipo != 'TX'){?>
                  	<input name="cam[<?php echo $i ?>]" type="hidden" id="cam[<?php echo $i; ?>]"
				    <?php if($tipo == 'NE'){echo "onKeyPress=\"return validar_numeric(event)\"";}
				  	if($tipo == 'ND'){echo "onKeyPress=\"return validar_decimal(event)\"";}
					$rs_det_camp2 = $obBD_con1->getRowConsulta(430,$row_rs_con_camp['Cam_Cod'].'*'.$row_rs_consultar["Act_Cod"], $obBD_conexion);
					$total_rs_det_camp2 = count($rs_det_camp2);
				  ?> value="<?php echo $rs_det_camp2["Act_Val"];?>" >
				    <?php }else{
				  $rs_det_camp3 = $obBD_con1->getRowConsulta(430,$row_rs_con_camp['Cam_Cod'].'*'.$row_rs_consultar["Act_Cod"], $obBD_conexion);
				  $total_rs_det_camp3 = count($rs_det_camp3);
				  ?>
				<textarea name="cam[<?php echo $i ?>]"id="cam[<?php echo $i; ?>]" >
				<?php echo $rs_det_camp3["Act_Val"];?>
				</textarea><?php }?></td>
				<?php
				$i++;
				}
				$cont++;
				$nam++;	
				}
			 	?>
              </tr>
			  <?php
			  }
			 // echo $i;
			  ?>
            </table></td>
	      </tr>
    </table> 
</fieldset>
 </fieldset>

<br> 
<table width="276" border="0" cellpadding="0" cellspacing="0">
    <tr>
	  <td width="112">
      <button type="button" name="btn_atras" id="btn_atras"  value="Enviar"  class="btn btn-inverse fileinput-button" title="Atr&aacute;s"
  onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver"; ?>','<?Php echo $volver_busqueda.'*'.$volver_opciones.'*'.'1'; ?>')">
  <i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span> 
  </button>
  </td>
      <td width="164" height="23">

	  	  <button name="boton_guardar" id="boton_guardar" type="button"  class="btn btn-primary fileinput-button" value="Guardar" <?php if($str != ""){ ?> onClick="validar_requeridos(this.form,'Act_Cdc*Act_Des*Suc_Cod*Prv_Cod*Pri_Cod*Est_Cod*Act_Can*<?php echo substr($str, 0, -1); ?>',1)" <?php }else{?>onClick="validar_requeridos(this.form,'Act_Cdc*Act_Des*Suc_Cod*Prv_Cod*Pri_Cod*Est_Cod*Act_Can*Act_Val*Act_Res*Act_Ann',1)"<?php }?>  title="Guardar">
          <i class=" icon-book icon-white"></i>
<span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span>
          </button>      
 </td>
    </tr>
  </table>
  <input name="Cam_Cod" type="hidden" id="Cam_Cod" value="<?php echo $Cam_Cod;?>">
<input name="Act_Cod" type="hidden" id="Act_Cod" value="<?php echo $row_rs_consultar["Act_Cod"]; ?>">
<input name="Act_Fot" type="hidden" id="Act_Fot" value="<?php echo $row_rs_consultar["Act_Fot"]; ?>">
<input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
<input name="i" type="hidden" id="i" value="<?php echo $i; ?>">
<input name="r" type="hidden" id="r" value="<?php echo $r; ?>">
<input type="hidden" name="txt_busqueda" id="volver_busqueda" value="<?Php echo $volver_busqueda; ?>"/>
<input type="hidden" name="op_opciones" id="volver_opciones" value="<?php echo $volver_opciones; ?>">
<input type="hidden" name="confir" id="confir" value="<?php echo $row_rs_asig['Asg_Con']; ?>">
<?Php 
if ($anulada > 0)
{		
	$com_leyenda[1]=$anulada;
}//Fin del if ($anulada > 0)
?>
<br/>
<?php
require_once('../../componentes/FRONT/com_con_leyenda.php');?>    
</form>
</fieldset>
	</td>
  </tr>
</table>
<?php } ?>
</div>	    

	<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
	  	<div id="bgmodal"  class="bgmodal" style="display:none" >
	 		<div id="ajax_modal">
				<div id="muestra"></div>
	 		</div>
	 	</div>   
<script type="text/javascript" src="../VALIDACIONES/act_par_campos_det.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	
</BODY></HTML>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>