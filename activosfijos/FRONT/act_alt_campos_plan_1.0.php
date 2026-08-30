<?php 
/** 
 * Alias:	Insetar
 * Descripci�n: Permite el ingreso de los campos de los tipos de activos
 * Desarrollador:	Fabian Gallardo
					Didimo Zamora 
 * Fecha de actualizaci�n:	2013-06-03
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_campos_det.php');	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php');

/**
 * Creacion del Objeto de conexion 
 */
	$obBD_conexion = new Class_Log_Conexion_Con;
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
if (isset($ajax_alt_PlanCam))
{	
	if(isset($Tia_Des))
	  { 
	 	$rs_con_tip_act = $obBD_con1->getRowConsulta(416,$Tia_Des, $obBD_conexion);
?>
<form method="post" name= "form2" action="<?php echo $_SERVER['PHP_SELF'];?>">
    <label class="Titulos2">Tipo de Activo: <span class="LetraNegra"><?php echo $rs_con_tip_act["Tia_Des"];?></span></label>
    <fieldset>
    <LEGEND>
        <label class="Titulos2">Datos a registrar&nbsp;</label>
    </LEGEND>
<?php 
	/**
	 * Creacion del campo REPOST
	 */
	$thisPost->startPost();
?>
	<table width="100%" cellpadding="0" cellspacing="0" border="0">
    	<tr>
         <td></td>
		 <td width="93%"></td>
    	</tr>
		<tr>
		  <td colspan="2" class="Etiqueta1"></td>
		  </tr>
		<tr>
		  <td width="20%" align="right" class="Etiqueta1">Campos:</td>		 
          <td width="80%"></td>
		</tr>
		<tr>
		  <td width="5%" class="Etiqueta1">&nbsp;</td>
          <td>
          <table width="95%" border="1" cellpadding="1" cellspacing="1">
            <?php  
			/** 
			 * seleccionar toodos los campos 
			 */
			$rs_con_camp = $obBD_con1->getArrayConsulta(414,$obBD_con1->parametros(''), $obBD_conexion);
			$total_rs_con_camp = count($rs_con_camp);
			$i =0;
			$nam = 0;
			/**
			 * Bucle para  mostrar los campos 
			 */
			foreach($rs_con_camp as $row_rs_con_camp ){
			?>
			  <tr>
			  <?php
			 	$cont = 0;
				/**
				 * seleccionar el ckeck del campo
				 */
				$rs_con_camp_act = $obBD_con1->getRowConsulta(415,$Tia_Des.'*'.$row_rs_con_camp['Cam_Cod'], $obBD_conexion);
				$total_rs_con_camp_act = count($rs_con_camp_act);
				$i++;
			  ?>
                <td><?php if($row_rs_con_camp['Cam_Est'] == 'I'){ $rojo='#FF0000'; $isact ='F';}else{$rojo=''; $isact ='T';} ?>
				<input name="cam[<?php echo $i ?>]" type="checkbox" id="cam[<?php echo $i; ?>]" value="<?php echo $row_rs_con_camp['Cam_Cod']; ?>" <?Php if($row_rs_con_camp['Cam_Cod'] == $rs_con_camp_act['Cam_Cod']){echo "checked style='background-color:#0F0'"; }?> <?php if($isact =='F'){echo "disabled";}?>>
                <span class="LetraNegra"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row_rs_con_camp['Cam_Lar'];?></FONT></span>
                <td><select name="sel[<?php echo $i; ?>]" id="sel[<?php echo $i; ?>]" <?php if($isact =='F'){echo "disabled ";}?> >
          			<option  <?php if ($rs_con_camp_act['Cam_Req']== 'N'){ echo "selected"; } ?> value="N" style="color:<?php echo $rojo;?>">No requerido</option>
                    <option  <?php if ($rs_con_camp_act['Cam_Req']== 'R'){ echo "selected"; } ?> value="R" style="color:<?php echo $rojo;?>">Requerido</option>
			    </select></td>
                <?php
				$cont++;
				$nam++;	
			 	?>
              </tr>
			  <?php
				/** 
				 * Fin de foreach($rs_con_camp as $row_rs_con_camp ){ 
				 */			  		
			  } 
			  ?>
            </table>
            </td>
	      </tr>
    </table> 
	</fieldset>    
 
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td  height="23"><button name="boton_guardar" id="boton_guardar" type="button" class="btn btn-primary fileinput-button" value="Guardar" title="Guardar" onClick="count_check(this.form,<?php echo $nam;?> , 'cam', 1)">
      <i class=" icon-book icon-white"></i>
	  <span>&nbsp;Guardar&nbsp;</span>
      </button>
      </td>
    </tr>
    </table>
    <input name="Tia_Des" type="hidden" id="Tia_Des" value="<?php echo $Tia_Des; ?>">
    <input name="txt_busqueda1" type="hidden" id="txt_busqueda1" value="<?php echo $txt_busqueda; ?>">
    <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
    <input name="volver_opciones1" type="hidden" id="volver_opciones1" value="<?php echo $volver_opciones; ?>">
    
    <input name="i" type="hidden" id="i" value="<?php echo $i; ?>">
</form>
	


<?Php
	  }
exit();
}

/** 
 * Consulta de los tipos de activos 
 */
	$rs_tip_act = $obBD_con1->getArrayConsulta(413,'', $obBD_conexion);
	$total_rs_tip_act = count($rs_tip_act);

 if ($thisPost->postBlock($_POST['postID'])) 
 { 
 	if (isset($hdd_save)) { 
		/**
		 * Inicio de transaccion 
		 */
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
	   	/**
		 * Eliminacion de los campos  registrados actualmente
		 */
		$obBD_con1->operacionobBD(417,$Tia_Des, $obBD_conexion);
		/**
		 * Ingreso de los campos 
		 */
		for ($j=1; $j<= $i; $j++)
		{			
			if (isset($cam[$j]))
			{		
				/**
				 * Inserci�n de cada campo
				 */
				$obBD_con1->operacionobBD(418, $cam[$j].'*'.$Tia_Des.'*'.$j.'*'.$sel[$j], $obBD_conexion);
				/**
				 * Graba auditoria 
				 */
				 $obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
			}
		}
		/**
		 * Fin de transaccion 
		 */
	 $obBD_con1->fin_transaccion($obBD_conexion->conexion);
	 }
}
?> 
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../VALIDACIONES/Validaciones.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
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
	  <td height="10">&raquo; Configuraci&oacute;n de campos </td>
  </tr>
	<tr>
	  	<td valign="top"> 
  <fieldset>
  <LEGEND>
    <label class="Titulos2">Buscar por:&nbsp;</label>
   </LEGEND>
    <?Php
		/**
		 * Muestra el mensaje de requerido
		 */
		mensaje_requerido(); 
	?>
 <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">  
   <table width="565" height="74" border="0" cellpadding="0" cellspacing="0">
   <tr> 
   	 <td colspan="3">
	
     	 <table width="565" height="36" border="0" cellpadding="0" cellspacing="0">
         	 <tr> 
             	<td width="107"> 
                	<input name="op_opciones" type="radio" value="g" <?Php if($op_opciones =='g'){ echo "checked";}?>  onClick="setfocus(this.form.txt_busqueda);">
      <span class="LetraNegra">Grupo</span>
                </td>          
                 <td width="99"><input name="op_opciones" type="radio" value="s" <?Php if($op_opciones =='s'){ echo "checked";}?>  onClick="setfocus(this.form.txt_busqueda);">
      <span class="LetraNegra">SubGrupo</span>
			     </td>
                  <td width="359"><input name="op_opciones" type="radio" value="n" <?Php if($op_opciones =='n' || ($op_opciones <> 'g' && $op_opciones <>'s')) { echo "checked";}?>   onClick="setfocus(this.form.txt_busqueda);">
      <span class="LetraNegra">Nombre</span>
     			  </td>
             </tr>
     	</table>	
     </td>
   </tr>
    <tr>
      <td width="106" height="38" class="BarraBusqueda">&nbsp;<span class="Asterisco">*</span> Tipo de Activo:</td>
      <td width="353" class="BarraBusqueda"> 
		<input   name="txt_busqueda" type="text" id="txt_busqueda" value="<?Php echo $_POST['txt_busqueda'];?>" size="50">&nbsp;&nbsp;
	  </td>       
         <td width="106" align="center" class="BarraBusqueda">&nbsp;
             <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form,'txt_busqueda',0);">
                <i class="icon-search icon-white"></i>
                <span>&nbsp;Buscar&nbsp;</span>
             </button>    
         </td>
     </tr>
  </table>
  </form>
</fieldset>	
<?php
	$Arr_Busqueda = array();
	if ($_POST['txt_busqueda1'])
	{
		if($_POST['volver_opciones1']=='g'){
			/**
			 * Busqueda de atipo de activo por el Grupo
			 */
			$Arr_Busqueda = $obBD_con1->getArrayConsulta(663, trim($_POST['txt_busqueda1']).'*'.$Ses_Emp_Cod, $obBD_conexion);				
		}
		
		if($_POST['volver_opciones1']=='s'){
			/**
			 * Busqueda de atipo de activo por el SubGrupo
			 */			 
			$Arr_Busqueda = $obBD_con1->getArrayConsulta(664, trim($_POST['txt_busqueda1']).'*'.$Ses_Emp_Cod, $obBD_conexion);				
		}
		
		if($_POST['volver_opciones1']=='n'){
			/**
			 * Buscar tipo de activos
			 */			 
		 	$Arr_Busqueda = $obBD_con1->getArrayConsulta(642, trim($_POST['txt_busqueda1']).'*'.$Ses_Emp_Cod, $obBD_conexion);
		}	
	}
	
	if($_POST['txt_busqueda']){
		
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
			 * Buscar tipo de activos
			 */			 
		 	$Arr_Busqueda = $obBD_con1->getArrayConsulta(642, trim($_POST['txt_busqueda']).'*'.$Ses_Emp_Cod, $obBD_conexion);
		}
		$total_Arr_Busqueda = count($Arr_Busqueda);
	}	
	if (count($Arr_Busqueda) > 0){	
  	?>
  <FIELDSET>
	<LEGEND>
		<label class="Titulos2">Resultados de la busqueda</label>
	  	</LEGEND>
	  <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
	     <thead>
			  <th width="5%" >C�d. Int. </th>
              <th width="25%">Grupo</th>
			  <th width="30%">SubGrupo</th>
			  <th width="25%">Nombre</th>
			  <th width="12%">Secuencial</th>
			  <th width="3%" >&nbsp;</th>
          </thead> 
		  <tbody>
			<?php	
			if (count($Arr_Busqueda) > 0 ){
				
			 foreach($Arr_Busqueda as $row){
							if($row['Pyo_Est'] == 'I'){
								$rojo = 'style="color: red;"';
								if(!isset($com_leyenda[1]))$com_leyenda[1]=1;
							}else{
								$rojo='';
							}							
			?>
		     <tr>
		       <td align="center" <?php echo $rojo;?>><?php echo $row['Tia_Cod'] ;?></td>
               <td align="left" <?php echo $rojo;?>><?php echo marcar_cadena($_POST['txt_busqueda'], $row['Grupo'],'#FFFF00', 1); ?></td>
			   <td align="left" <?php echo $rojo;?>><?php echo marcar_cadena($_POST['txt_busqueda'], $row['Subgrupo'],'#FFFF00', 1);?></td>
			   <td align="center" <?php echo $rojo;?>><?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Tia_Des'],'#FFFF00', 1);?></td>
			   <td align="center" <?php echo $rojo;?>><?Php echo $row['Tia_Cdc'];?></td>
			   <td align="center">
			 	<?php 
					if($row['Tia_Est'] == 'A'){ 						 
					?>
				  <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
					  <button type='button' class='btn btn-success btn-mini' title="Elegir" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_alt_PlanCam=1&Tia_Des=<?php echo $row['Tia_Cod'];?>&txt_busqueda=<?php echo $_POST['txt_busqueda'];?>&volver_opciones=<?Php echo $op_opciones; ?>','ajax_modal');"><i class='icon-arrow-right icon-white'></i></button>
					  <input type="hidden" name="pag" value="1">
					  <input name="Tia_Des" type="hidden" id="Tia_Des" value="<?php echo $row['Tia_Cod']; ?>">
					  <input type="hidden" name="volver_txt_busqueda" value="<?php echo htmlspecialchars($_POST['txt_busqueda'], ENT_QUOTES, 'UTF-8');?>">
			      </form>
				  <?php 						
					}else{
					  ?>
					  <img src="../../mascaras/model1/imagenes/32x32/encrypted.png" width="25" height="25" title="Registro Anulado">
				  <?php	}?>
		       </td>
	         </tr>
		    <?php }
			}
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
	</td>
  </tr>
</table>    
</div>
<script type="text/javascript" src="../VALIDACIONES/act_par_campos_det.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>

<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
	  	<div id="bgmodal"  class="bgmodal" style="display:none" >
	 		<div id="ajax_modal">
			<div id="muestra"></div>
	 	</div>
</div>   
</BODY></HTML>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>