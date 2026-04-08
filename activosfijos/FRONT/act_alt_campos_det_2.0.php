<?php 
/** 
 * Descripción: Permite el ingreso del detalle de los tipos de activos.
 * Desarrollador:	Fabian Gallardo.
 * Fecha de actualización:	2011-04-21.
 * Desarrollador:	Didimo Zamora.
 * Fecha de actualización:	12-06-2013.
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
 * Creación del objeto para evitar el reenvio 
 */
$thisPost = new Post_Block;  

/** 
 * Consulta de los Peritos 
 */
$rs_pri_act = $obBD_con1->getArrayConsulta(421,$Ses_Emp_Cod, $obBD_conexion);
$total_rs_pri_act = count($rs_pri_act);
/**
 * Consulta las Sucursales 
 */
$rs_suc_act = $obBD_con1->getArrayConsulta(422, $Ses_Emp_Cod, $obBD_conexion);
$total_rs_suc_act = count($rs_suc_act);
/**
 * Consulta los Estados 
 */
$rs_est_act = $obBD_con1->getArrayConsulta(423,'', $obBD_conexion);
$total_rs_est_act = count($rs_est_act);
/** 
 * Consulta los Proveedores 
 */
$rs_prv_act = $obBD_con1->getArrayConsulta(424, $Ses_Emp_Cod, $obBD_conexion);
$total_rs_prv_act =  count($rs_prv_act);

/**
 * Consulta los Custodios 
 */
$rs_cus_act = $obBD_con1->getArrayConsulta(425,$Ses_Emp_Cod, $obBD_conexion);
$total_rs_cus_act = count($rs_cus_act);

/** 
 * Consulta de los tipos de activos 
 */
$rs_tip_act = $obBD_con1->getArrayConsulta(413,$Ses_Emp_Cod, $obBD_conexion);
$total_rs_tip_act = count($rs_tip_act);

/**
 * Consulta el detalle de las secciones 
 */
$rs_seccion = $obBD_con1->getArrayConsulta(437, $Ses_Emp_Cod, $obBD_conexion);
$total_rs_seccion = count($rs_seccion);
		
 if ($thisPost->postBlock($_POST['postID'])) 
 { 
 	if (isset($hdd_save) && !isset($hdd_atras)) 
	{ 
			/**
			 * Inicio de la transacción
			 */
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		
			/**
			 * Consulta el ultimo activo insertado para el código manual
			 */
			$rs_ult_act = $obBD_con1->getRowConsulta(426,$Tia_Des, $obBD_conexion);
			$Act_Cdc = $rs_ult_act["Cod"] + 1;			
			/**
			 * Inserción del activo
			 */
			$Act_Cdc = $Tia_Cdc.'.'.$Act_Cdc;
			$Act_var ='';
			$Act_gen ='';	
			/**
			* Asignación del tipo para código de barras
			*/
			if($Act_Gen==1)
			{
				$Act_gen = 'G'; //Cuando el codigo de barras es generado de forma automatica
			}
			else
			{
				$Act_gen = 'M'; //Cuando el codigo de barras es ingresado de forma manual
			}				
				
			$obBD_con1->operacionobBD(427,$Tia_Des.'*'.$Pri_Cod.'*'.$Suc_Cod.'*'.$Est_Cod.'*'.$Prv_Cod.'*'.$Act_Des.'*'.$Act_Obs.'*'.$Act_Cdc.'*'.$Act_Can.'*'.$Act_Bar.'*'.$Act_gen.'*'.$Act_Val.'*'.$Act_Res.'*'.$Act_Ann.'*'.$Act_Fec.'*'.$Act_Gar.'*'.$flag,$obBD_conexion);		
			$Act_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
				
				if($Act_Gen==1)
				{
					switch ( strlen($Act_Cod)) 
					{
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
				//}
			/** 
			 * Actualiza el codigo de barra con id del activo
			 */
			$obBD_con1->operacionobBD(641, $Act_Bar.'*'.$Act_Cod, $obBD_conexion);		
		
		/** 
		 * Inserción de la ubicacion
		 */
		 $Ord_Default = 1;
		$obBD_con1->operacionobBD(428, $Cus_Cod.'*'.$Act_Cod.'*'.date("Y-m-d").'*'.date("H:i:s").'*'.$Sec_Cod.'*'.$Ord_Default,$obBD_conexion);
		 
			if(isset($cam_r))
			{
				for ($j=1; $j<= $r; $j++)
				{			
					if (isset($cam_r[$j]))
					{		
						/**
						 * Inserción de cada campo
						 */
						 $obBD_con1->operacionobBD(420,$Act_Cod.'*'.$cam_rc[$j].'*'.trim($cam_r[$j]),$obBD_conexion);
					}
				}
			}
			if(isset($cam))
			{
				for ($k=1; $k<= $i; $k++)
				{			
					if (isset($cam[$k]))
					{		
						/** 
						 * Inserción de cada campo
						 */
						 $obBD_con1->operacionobBD(420,$Act_Cod.'*'.$cam_c[$k].'*'.trim($cam[$k]), $obBD_conexion);
					}
				}
			}

			/**
			* Evalua si se envia la foto
			*/
			if (trim($archivo)<>'')
			{
				 /**
				  *Variable que me indica si la imagen se subio correctacmente		
				  */
				$flag=upLoadImg($archivo,$Act_Cod,51200,"fotos/");
				
				if($flag!="0")
				{	
					$obBD_con1->operacionobBD(659, $flag.'*'.$Act_Cod, $obBD_conexion); 	
					
					$obBD_con1->fin_transaccion($obBD_conexion->conexion);			
				}
				else
				{
					$obBD_con1->rollBack($obBD_conexion->conexion);
				}
			}//fin del if (isset($archivo))
			else
			{
				$obBD_con1->fin_transaccion($obBD_conexion->conexion);			
			}
	 

	  }
	}
}
?> 
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script language="javascript" src="../VALIDACIONES/act_val_campos_det.js"></script>        
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
        <script>
		$(function() { 
		$( "#Act_Fec" ).datepicker({changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});
		 });
        </script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
                			
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Registrar Activos Fijos </td>
    </tr>
 </table>
<form name="Buscador" id="Buscador" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
				<FIELDSET>
					<LEGEND>
						<label class="Titulos2">Buscar:</label>
					</LEGEND>
					<?Php
					/**
					 * Muestra el mensaje de requerido
					 */
					mensaje_requerido(); 
					?>
					<table width="572" border="0" cellpadding="0" cellspacing="0">
                     <tr>
                        <td>
                          <table width="497" height="36" border="0" cellpadding="0" cellspacing="0">
         	 <tr> 
             	<td width="115"> 
                	<input name="op_opciones" type="radio" value="g" <?Php if($op_opciones =='g'){ echo "checked";}?>  onClick="setfocus(this.form.txt_busqueda);">
      <span class="LetraNegra">Grupo</span>
                </td>          
                 <td width="128"><input name="op_opciones" type="radio" value="s" <?Php if($op_opciones =='s'){ echo "checked";}?>  onClick="setfocus(this.form.txt_busqueda);">
      <span class="LetraNegra">SubGrupo</span>
			     </td>
                  <td width="322"><input name="op_opciones" type="radio" value="n" <?Php if($op_opciones =='n' || ($op_opciones <> 'g' && $op_opciones <>'s')) { echo "checked";}?>   onClick="setfocus(this.form.txt_busqueda);">
      <span class="LetraNegra">Nombre</span>
                  </td>
             </tr>
     	</table>                
          </td>
          </tr>
          <tr>
              <td height="42" class="BarraBusqueda"><span class="Asterisco">*</span> Tipo de Activo:
                <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="45">&nbsp;&nbsp;
                <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form,'txt_busqueda',0);">
                    <i class="icon-search icon-white"></i>
                    <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>
                </button>
              </td>
            </tr>
        </table>
    </FIELDSET>
</form>        
<?php
	if ($_POST['txt_busqueda1'])
	{
		if($op_opciones=='g'){
			/**
			 * Busqueda de atipo de activo por el Grupo
			 */
			 
			
			$Arr_Busqueda = $obBD_con1->getArrayConsulta(663, trim($_POST['txt_busqueda1']).'*'.$Ses_Emp_Cod, $obBD_conexion);				
		}
		
		if($op_opciones=='s'){
			/**
			 * Busqueda de atipo de activo por el SubGrupo
			 */
			 
			$Arr_Busqueda = $obBD_con1->getArrayConsulta(664, trim($_POST['txt_busqueda1']).'*'.$Ses_Emp_Cod, $obBD_conexion);				
		}
		
		if($op_opciones=='n'){
			/**
			 * Buscar tipo de activos
			 */
			 
		 	$Arr_Busqueda = $obBD_con1->getArrayConsulta(642, trim($_POST['txt_busqueda1']).'*'.$Ses_Emp_Cod, $obBD_conexion);
		}
		
		
	}


  	if(isset($_POST['txt_busqueda'])){
		
		if($op_opciones=='g'){
			/**
			 * Busqueda de atipo de activo por el Grupo
			 */
			 			 
			$Arr_Busqueda = $obBD_con1->getArrayConsulta(663, trim($_POST['txt_busqueda']).'*'.$Ses_Emp_Cod, $obBD_conexion);				
		}
		if($op_opciones=='s'){
			/**
			 * Busqueda de atipo de activo por el SubGrupo
			 */
			 
			$Arr_Busqueda = $obBD_con1->getArrayConsulta(664, trim($_POST['txt_busqueda']).'*'.$Ses_Emp_Cod, $obBD_conexion);				
		}
		
		if($op_opciones=='n'){
			/**
			 * Busqueda de atipo de activo por el SubGrupo
			 */
			
			$Arr_Busqueda = $obBD_con1->getArrayConsulta(642, trim($_POST['txt_busqueda']).'*'.$Ses_Emp_Cod, $obBD_conexion);				
		}
		
		
  ?>
  <FIELDSET>
	<LEGEND>
					  	<label class="Titulos2">Resultados de la busqueda</label>
	  </LEGEND>
	  <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
	     <thead>
	        <tr>
			  <th width="5%">Cód. Int. </th>
              <th width="25%">Grupo</th>
			  <th width="25%">SubGrupo</th>
			  <th width="27%">Nombre</th>
			  <th width="15%">Secuencial</th>
			  <th width="3%" >&nbsp;</th>
	        </tr>
          </thead> 
		  <tbody>
			<?php 
			
			
			foreach($Arr_Busqueda as $row){
							if($row['Pyo_Est'] == 'I'){
								$rojo = 'style="color: red;"';
								if(!isset($com_leyenda[1]))$com_leyenda[1]=1;
							}else{
								$rojo='';
							}
							?>
		     <tr>
		       <td align="center" <?php echo $rojo;?>><?php echo $row['Tia_Cod'];?></td>
               <td align="center" <?php echo $rojo;?>><?php echo $row['Grupo'];?></td>
			   <td align="left" <?php echo $rojo;?>><?php echo $row['Subgrupo']?></td>
			   <td align="center" <?php echo $rojo;?>><?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Tia_Des'],'#FFFF00', 1);?></td>
					   <td align="center" <?php echo $rojo;?>><?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Tia_Cdc'],'#FFFF00', 1);?></td>
			   <td align="center">
			 	  <?php 
					if($row['Tia_Est'] == 'A'){
					?>
				  <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
					  <button type='button' class='btn btn-success btn-mini' title="Elegir" onClick="this.form.submit();"><i class='icon-arrow-right icon-white'></i></button>
					  <input type="hidden" name="pag" value="1">
					  <input name="Tia_Des" type="hidden" id="Tia_Des" value="<?php echo $row['Tia_Cod']; ?>">
					  <input type="hidden" name="volver_txt_busqueda" value="<?php echo $_POST['txt_busqueda'];?>">
                      <input type="hidden" name="volver_opciones" value="<?php echo $_POST['op_opciones'];?>">
                      
                      
			      </form>
				  <?php 
								}else{
									?>
					  <img src="../../mascaras/model1/imagenes/32x32/encrypted.png" width="25" height="25" title="Proyecto Anulado">
					  <?php
								}
								?>
		       </td>
	         </tr>
		    <?php }
						 if(count($Arr_Busqueda)==0){
						 	?>
		 	  <tr>
			 	  <td>&nbsp;</td>
  				  <td>&nbsp;</td>
  				  <td><?Php echo error_alerta("No hay resultados que mostrar", 1) ?></td>
	        <td>&nbsp;</td>
				  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                <?Php
						 }
					?>
	       </tbody>
       </table>
    </FIELDSET>
				<?php
				echo barra_estado(count($Arr_Busqueda)).'<br>';
				require_once('../../componentes/FRONT/com_con_leyenda.php');
  			}
			
			

			 ?>
<?php if(isset($Tia_Des))
	  { 
	  	/**
		* Consulta la descripción del tipo de activo especifico
		*/
	 	$row_rs_con_tip_act = $obBD_con1->getRowConsulta(416,$Tia_Des, $obBD_conexion);
?>
<form method="post" name= "form2" id="form2" action="<? echo $_SERVER['PHP_SELF'];?>"   enctype="multipart/form-data">
<? 
  /** 
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
		<td colspan="2"><?Php echo mensaje_requerido(); ?></td>
	</tr>
	<tr>
	  <td class="Etiqueta1">Tipo de Activo:</td>
	  <td width="83%" class="LetraNegra">&nbsp;<?php echo $row_rs_con_tip_act["Tia_Des"];?> 
	    <input type="hidden" name="Tia_Cdc" id="Tia_Cdc" value="<? echo $row_rs_con_tip_act['Tia_Cdc'];?>">
	 	</td>
	  </tr>
	<!--<tr>
		<td width="17%" class="Etiqueta1"><span class="Asterisco">*</span> Código Activo:</td>
		<td>&nbsp;<input name="Act_Cdc" type="text" id="Act_Cdc" value="<?php //echo $row_rs_ult_act["Cod"] + 1;?>" size="30" readonly></td>
		</tr>-->
	<tr>
	  <td width="17%" class="Etiqueta1"><span class="Asterisco">*</span> Nombre:</td>
	  <td>&nbsp;<input name="Act_Des" type="text" id="Act_Des" value="<?php echo $row_rs_con_tip_act["Tia_Des"];?>" maxlength="100" size="60" ></td>
	  </tr>
	</table>
  <fieldset>
  <LEGEND>
    <label class="Titulos2">Generales</label>
	</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Sucursal:</td>
        <td width="84%" colspan="2">&nbsp;<select name="Suc_Cod" id="Suc_Cod">
        <?php
			foreach( $rs_suc_act as $row_rs_suc_act) {  
		?>
        <option value="<?php echo $row_rs_suc_act['Suc_Cod']?>"><?Php echo $row_rs_suc_act['Suc_Des']?> </option>
        <?php
        	}  
        ?>
        </select>
        </td>
       </tr>
      <tr>
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Proveedor:</td>
        <td colspan="2">&nbsp;<select name="Prv_Cod" id="Prv_Cod">
            <option value="">Seleccione...</option>
            <?php
			foreach( $rs_prv_act as $row_rs_prv_act ) 
			{
		?> 
            <option value="<?php echo $row_rs_prv_act['Prv_Cod']?>"><?Php if (!empty($row_rs_prv_act['Prv_Com'])){ echo $row_rs_prv_act['Prv_Com'].":: "; } echo $row_rs_prv_act['Nombre']; ?> </option>
            <?php
			} 
	?>
        </select></td>
        </tr>
      <tr>
        <td width="16%"  class="Etiqueta1"><span class="Asterisco">*</span> Perito:</td>
        <td colspan="2">&nbsp;<select name="Pri_Cod" id="Pri_Cod">
        <?Php
		if (count($rs_pri_act)>1)
		{
		?>
            <option value="">Seleccione...</option>
            <?php
		}
			foreach($rs_pri_act as $row_rs_pri_act ){	  
		?>
            <option title="<?Php echo $row_rs_pri_act['Per_Esp']; ?>" value="<?php echo $row_rs_pri_act['Pri_Cod']?>"><?Php echo $row_rs_pri_act['Prs_Ape'].' '.$row_rs_pri_act['Prs_Nom']?> </option>
            <?php
			} 	?>
        </select></td>
        </tr>
      <tr>
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Custodio:</td>
        <td colspan="2">&nbsp;<select name="Cus_Cod" id="Cus_Cod">
            <option value="">Seleccione...</option>
            <?php  
			foreach($rs_cus_act as $row_rs_cus_act){
		?>
            <option value="<?php echo $row_rs_cus_act['Cus_Cod']?>"><?Php echo $row_rs_cus_act['Nombre']?> </option>
            <?php
			} 	?>
        </select></td>
        </tr>
        <tr>
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Sección:</td>
        <td colspan="2">&nbsp;<select name="Sec_Cod" id="Sec_Cod">
        <?Php
		if (count($rs_seccion)>1)
		{?>
            <option value="">Seleccione...</option>
            <?php
		}
			foreach( $rs_seccion as $row_rs_seccion ){
		?>
            <option value="<?php echo $row_rs_seccion['Sec_Cod']?>"><?Php echo $row_rs_seccion['Sec_Des'].' - '.$row_rs_seccion['Dep_Des']?> </option>
            <?php
			} 	
			?>
        </select></td>
        </tr>
      <tr>
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Estado:</td>
        <td colspan="2">&nbsp;<select name="Est_Cod" id="Est_Cod">
        	<!--<option value="">Seleccione...</option>-->
            <?php 
			 foreach($rs_est_act as $row_rs_est_act){
		?>
            <option value="<?php echo $row_rs_est_act['Est_Cod']?>"><?Php echo $row_rs_est_act['Est_Des']?> </option>
            <?php
			} 
			?>
        </select></td>
        </tr>
      <tr>
        <td width="16%" align="right" class="Etiqueta1"> Observaciones:</td>
        <td colspan="2">&nbsp;<textarea name="Act_Obs"id="Act_Obs" cols="60" rows="3"></textarea></td>
      </tr>
   <tr>
    <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Cantidad:</td>
    <td colspan="2">&nbsp;<input name="Act_Can" type="text" id="Act_Can" style="text-align:right" maxlength="5" size="5" onKeyPress="return validar_numeric(event)" value="1" readonly ></td>
    </tr>
   <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> C&oacute;digo de barra: </td>
    <td>
      <table width="559" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="123"><span class="LetraNegra">&nbsp;<input name="Act_Bar"  type="text" disabled id="Act_Bar" value="" size="12" maxlength="12" />
          </span></td>
          <td width="22"><span class="LetraNegra">
            <input name="Act_Gen" type="checkbox" id="Act_Gen" onClick="check_generar()"  value="1" checked>
          </span></td>
          <td width="414"><div class="Cuerpo_ajax" id='contenedorcheck'> Generar c&oacute;digo automaticamente</div></td>
        </tr>
      </table>      
      </td>
  </tr>  
    </table>
  </fieldset>
  <fieldset>
  <LEGEND>
    <label class="Titulos2">Contables</label>
    </LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
    	<tr>
        <td width="16%" align="right" class="Etiqueta1">Fecha adquisición :</td>
        <td width="84%" colspan="2">&nbsp;<input name="Act_Fec" type="text" id="Act_Fec" value="" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
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
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Valor Actual :</td>
        <td width="84%" colspan="2">&nbsp;<input name="Act_Val" type="text" id="Act_Val" value="0" size="10" onKeyPress = "return validar_decimal(event)" style="text-align:right"></td>
        </tr>
		 <tr>
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Valor Residual :</td>
        <td width="84%" colspan="2">&nbsp;<input name="Act_Res" type="text" id="Act_Res" value="0" size="10" onKeyPress = "return validar_decimal(event)" style="text-align:right"></td>
        </tr>
		 <tr>
        <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Vida Útil :</td>
        <td width="84%" colspan="2">&nbsp;<input name="Act_Ann" type="text" id="Act_Ann" value="0" size="10" maxlength="3" onKeyPress="return validar_numeric(event)" style="text-align:right"><span class="Etiqueta1"> Años </span></td>
        </tr>
	</table>
	</fieldset>
   <fieldset>
  <LEGEND>
    <label class="Titulos2">Foto del Activo</label>
    </LEGEND>
    	<table width="100%" cellpadding="0" cellspacing="0" border="0">
    	<tr>
            <td width="16%" class="Etiqueta1">Elegir Foto:&nbsp;</td>
        	<td width="84%"  ><input name="archivo" type="file" class="Boton" id="archivo" /></td>
    	</tr>
		
        </table>
    </fieldset> 
    
    
  <fieldset>
  <LEGEND>
    <label class="Titulos2">Técnicos</label>
	</LEGEND>
  <table width="100%" cellpadding="0" cellspacing="0" border="0">
    	<tr>
         <td width="93%"></td>
    	</tr>
		<tr>
		  <td class="Etiqueta1"></td>
		  </tr>
		<tr>
		  <td align="left" width="17%"><table width="75%" border="0" cellpadding="0" cellspacing="0">
		    <?php  
				/**
				 * Seleccionar toodos los campos 
				 */
				$rs_con_camp = $obBD_con1->getArrayConsulta(419,$Tia_Des, $obBD_conexion);
				$total_rs_con_camp = count($rs_con_camp);
				$i = 1;
				$r = 1;
				$str ="";
				$nam = 0;
				foreach($rs_con_camp as $row_rs_con_camp)
				{
				?>
		    <tr>
		      <?php
			 $cont = 0;
			  ?>
		      <td width="12%" class="Etiqueta1"><?php if($row_rs_con_camp['Cam_Est'] == 'I'){ $rojo='#FF0000'; $isact ='F';}else{$rojo=''; $isact ='T';} ?><?php if($row_rs_con_camp['Cam_Req'] == 'R'){
				echo "<span class=\"Asterisco\">* </span>";  $str = $str.'cam_r['.$r.']*';?>
		        <?php echo $row_rs_con_camp['Cam_Cor'].": "; ?>
		      <td width="40%">&nbsp;<input name="cam_rc[<? echo $r;  ?>]" type="hidden" id="cam_rc[<? echo $r; ?>]" value="<? echo $row_rs_con_camp['Cam_Cod']; ?>">
		          <?php $tipo = $row_rs_con_camp['Cam_Tip'];?>
		          <?php if($tipo != 'TX'){?>
		          <input name="cam_r[<? echo $r; ?>]2" type="text" id="cam_r[<? echo $r; ?>]"
				  <?php if($tipo == 'NE'){echo "onKeyPress=\"return validar_numeric(event)\"";}
				  	if($tipo == 'ND'){echo "onKeyPress=\"return validar_decimal(event)\"";}
				  ?>
				  >
		          <?php }else{?>
  <textarea name="cam_r[<? echo $r ?>]"id="cam_r[<? echo $r; ?>]" cols="60" rows="3"></textarea><? }?></td>
		      <?php
				$r++;
				}else{
				echo $row_rs_con_camp['Cam_Cor']." :"; ?>	
		      <td width="48%">&nbsp;<input name="cam_c[<? echo $i ?>]" type="hidden" id="cam_c[<? echo $i; ?>]" value="<? echo $row_rs_con_camp['Cam_Cod']; ?>">
		        <?php $tipo = $row_rs_con_camp['Cam_Tip'];?>
		        <?php if($tipo != 'TX'){?>
		        <input name="cam[<? echo $i ?>]" type="text" id="cam[<? echo $i; ?>]"
				   <?php if($tipo == 'NE'){echo "onKeyPress=\"return validar_numeric(event)\"";}
				  	if($tipo == 'ND'){echo "onKeyPress=\"return validar_decimal(event)\"";}
				  ?>
				  ><?php }else{?>
		        <textarea name="cam[<? echo $i ?>]"id="cam[<? echo $i; ?>]" cols="60" rows="3"></textarea><? }?></td>
		      <?
				$i++;
				}
				$cont++;
				$nam++;	
			 	?>
		      </tr>
		    <?php
			  }
			  ?>
		    </table></td>
	      </tr>
    </table> 
</fieldset>
 </fieldset>
   <input name="Tia_Des" type="hidden" id="Tia_Des" value="<? echo $Tia_Des; ?>">
	<input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
    <input name="i" type="hidden" id="i" value="<? echo $i; ?>">
	<input name="r" type="hidden" id="r" value="<? echo $r; ?>">    
    <input type="hidden" name="volver_txt_busqueda" value="<?php echo $_POST['volver_txt_busqueda'];?>">
 </form>
    <br>
    <table width="247" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="111">
	<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
		<button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onClick="this.form.submit();"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
        <input type="hidden" name="hdd_volver" value="1">
		<input type="hidden" name="txt_busqueda" value="<?php echo $_POST['volver_txt_busqueda'];?>">
        <input type="hidden" name="op_opciones" value="<?php echo $_POST['volver_opciones'];?>">
	</form>	      
      </td>
      <td width="136">
      <form enctype="multipart/form-data">
	  <button name="boton_guardar" id="boton_guardar" title="Guardar" type="button" class="btn btn-primary fileinput-button" value="Guardar" <? if($str != ""){ ?> onClick="validar_requeridos(document.getElementById('form2'),'Act_Des*Suc_Cod*Prv_Cod*Pri_Cod*Cus_Cod*Est_Cod*Act_Can*<?php echo substr($str, 0, -1); ?>',1)" <? }else{?>onClick="validar_requeridos(document.getElementById('form2'),'Act_Des*Suc_Cod*Prv_Cod*Pri_Cod*Cus_Cod*Sec_Cod*Est_Cod*Act_Can*Act_Val*Act_Res*Act_Ann',1)"<? }?> >
     <i class=" icon-book icon-white"></i>
<span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span>
      </button>     
      </form>
      </td>
    </tr>
  </table>	
</fieldset>
<?php 
	}// fin de if(isset($Tia_Des))
?>
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