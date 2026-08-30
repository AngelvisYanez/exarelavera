<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	
/* 
Alias:	Registrar
Descripci�n: Permite registrar las cuentas del plan de cuentas
Desarrollador:	Sam :)
Fecha de actualizaci�n:	2012-10-15
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_estado.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php');	 

/**
 *Creacion del Objeto de conexion
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
  
if(isset($grabacta)){               
	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);       
		$obBD_con1->grabarv_registros(sentencias_est(320,$obBD_con1->parametros($PldCod.'*'.$PecCod.'*'.$Tipo)), $obBD_conexion->conexion);                
                $obBD_con1->grabarv_registros(sentencias_est(321,$obBD_con1->parametros($PldCod.'*'.$PecCod.'*'.$Tipo)), $obBD_conexion->conexion);                
        		           
        $obBD_con1->fin_transaccion($obBD_conexion->conexion);       
	exit();
}
if(isset($anula)){         
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);        
        $obBD_con1->grabarv_registros(sentencias_est(320,$obBD_con1->parametros($PldCod.'*'.$PecCod.'*'.$Tipo)), $obBD_conexion->conexion);                
        		           
        $obBD_con1->fin_transaccion($obBD_conexion->conexion);       
	exit();
}
if (isset($ajax_buscod1))
{	
	if ($op_opciones=='d')
	{
		/**
		* Cargado de los resultados de la busqueda por descripcion de la cuenta
		*/
		$row_rs_buscta = $obBD_con1->getArrayConsulta(319, $buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod, $obBD_conexion);
	}
	if ($op_opciones=='c')
	{
		/** 
		* Cargado de los resultados de la busqueda por codigo de la cuenta
		*/
		$row_rs_buscta = $obBD_con1->getArrayConsulta(319, $buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod, $obBD_conexion);
	}//Fin del if ($op_opciones=='d')

	if ($op_opciones=='g')
	{
		/** 
		* Cargado de los resultados de la busqueda por grupo
		*/
		$row_rs_buscta = $obBD_con1->getArrayConsulta(329, $buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod, $obBD_conexion);
	}//Fin del if ($op_opciones=='d')
	?>
	<br>
	<table width="100%" border="1" cellpadding="0" cellspacing="0">
	  <tr class="Cabecera1">
	    <td width="6%">C&oacute;d. Int.</td>
            <td width="10%"><strong>C&oacute;digo</strong></td>
		<td width="24%"><strong>Cuenta</strong></td>
		<td width="20%"><strong>Grupo</strong></td>
		<td width="10%"><strong>Tipo</strong></td>
		<!--<td width="10%"><strong>Estado</strong></td>-->
		<td width="15%">&nbsp;</td>								
		</tr>
      <tbody>  
	  <?php
	  if (count($row_rs_buscta) > 0) {
	  foreach ($row_rs_buscta as $row)
	  { 
		/**
		* Consulta del detallete de la CUENTA 
		*/
		$row_rs_recur = $obBD_con1->getRowConsulta(318, $row['Pld_Rec'], $obBD_conexion);
		/**
		* Consulta del detallete de la CUENTA (OTRO) 
		*/
		$row_rs_grupo = $obBD_con1->getRowConsulta(318, $row_rs_recur['Pld_Rec'], $obBD_conexion);
	  ?>
	  <tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo"); ?> class="Fondo">
	    <td><?php echo $row['Pld_Cod']; ?></td>
		<td><div align="left"><?php echo $row['Pld_Cdc']; ?></div></td>


		<!--td><div align="left"><?Php echo marcar_cadena($buscod, $row['Pld_Des'],'#FFFF00', 1);?></div></td-->
		<td><div align="left"><?php echo mb_convert_encoding(marcar_cadena($buscod, $row['Pld_Des'],'#FFFF00', 1), 'UTF-8', 'ISO-8859-1');?></div></td>



		<td><div align="center"><?php if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']." <strong>(".$row_rs_grupo['Pld_Des'].")</strong>"; }else{ echo "&nbsp;"; } ?></div></td>
		<td align="center"><div align="center"><?php echo $row['Pld_Tip']; ?></div></td>
		<!--<td align="center"><div align="center"><?php echo $row['Pld_Est']; ?></div></td>-->
		<td align="center">
                <!--<button type="button" class="btn btn-success btn-mini" title="Agregar Utilidad" onClick="agregarUti('<?php echo $row['Pld_Cod']; ?>','<?php echo $Pec_Cod; ?>')">
                        <i class=" icon-arrow-right icon-white"></i>          
                </button>        -->
                <?php
                	if ($op_opciones!='g'){
                ?>
                    <button type="button" class="btn btn-success btn-mini" title="Agregar Cta Perdidas" onClick="agregarUti('<?php echo $row['Pld_Cod']; ?>','<?php echo $Pec_Cod; ?>','P')">
                        P          
                    </button>
                    <button type="button" class="btn btn-success btn-mini" title="Agregar Cta Ganancias" onClick="agregarUti('<?php echo $row['Pld_Cod']; ?>','<?php echo $Pec_Cod; ?>','G')">
                        G          
                    </button>
                <?php
                	}
                	else{
                ?>
                    <button type="button" class="btn btn-success btn-mini" title="Participacion Impuestos" onClick="agregarUtiParImp('<?php echo $row['Pld_Cod']; ?>','<?php echo $Pec_Cod; ?>','I')">
                        PI         
                    </button>
                <?php
                	}
                ?>
                </td>					
	  </tr>
	  <?php } //FIn del foreach ($row as $row)
	  } else { ?>
		<tr><td colspan="8" class="Alertas"><?php echo error_alerta("!No hay resultados que mostrar!", 1); ?></td>
		</tr>
	  <?php }//Fin del if ($total_rs_buscta > 0)
	  ?>
      </tbody>
	</table>
<?php
 echo barra_estado(count($row_rs_buscta));
exit();
}//if (isset($buscod))
if(isset($_GET['opx']))
{
	if($Est_Cod == 0)
	{
	?>
	<input name="hdd_save" type="hidden" id="hdd_save" value="1">
            <input type="hidden" name="codpla" value="<?php echo $codpla; ?>" />         
	<?php
	}
	else
	{
	?>
	<input name="hdd_save" type="hidden" id="hdd_save" value="2">
            <input type="hidden" name="codpla" value="<?php echo $codpla; ?>" />         
	<?php
	}
	?>
  <fieldset>
		<legend>
			<label class="Titulos2" >Datos a registrar:</label>
		</legend>
		 <?php mensaje_requerido();?>
			<table border="0">
				  <tr>
					<td width="107" class="Etiqueta1"><span class="Asterisco">* </span>Tipo de Balance:</td>
					<td class="LetraNegra">
					<?php $row_combo = $obBD_con1->getArrayConsulta(316,$codpla, $obBD_conexion); 
					//print_r($row_combo);
					 if($Est_Cod == 0)
					{
					?>
					  <select name="cmb_opciones" id="cmb_opciones">
	                    <option value="">Seleccione...</option>
    			         <?php	foreach($row_combo as $rows1)
	                      {  ?>
					     <option value="<?php echo $rows1['Est_Cod']; ?>"> <?php    echo '['.$rows1['Est_Cod'].']&nbsp;'.$rows1['Est_Des'];   ?> </option>
			 	        <?php } ?>
										 </select>
					<?php
					}
					else
					{
					 $row = $obBD_con1->getRowConsulta(7,$Est_Cod, $obBD_conexion);
					 echo "<b>".$row['Est_Des']."</b>"; 
	 		        ?> <input type="hidden" name="cmb_opciones" id="cmb_opciones" value="<?php echo $Est_Cod; ?>"> <?php
					}
					?>
					</td>
				  </tr>
			</table>
	    	<?php $Arr_Campos = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod.'*'.$codpla, $obBD_conexion);	?> 
				<table width="100%">
					<?php	if(count($Arr_Campos) > 0)
						{
						   /**
						    * valor del indice del arreglo
						    */
						   $i = 0;
							while($i < count($Arr_Campos))
								{
								?>
								<tr>
								<?php 
									while($i < count($Arr_Campos))
									{
									?>
						 			<td width="33%">
									<?php 
						 			 if(isset($Arr_Campos[$i]['Pld_Des']))
										{
										$count = $obBD_con1->getRowConsulta(9, $Arr_Campos[$i]['Pld_Cod'].'*'.$Est_Cod, $obBD_conexion);
										$ck = ($count['count'] > 0)? "checked" : "";
									  	  echo '<input type="checkbox" name="tipos[]" id="tipos[]" value="'.$Arr_Campos[$i]['Pld_Cod'].'" '.$ck.' >&nbsp;';
										  echo $Arr_Campos[$i]['Pld_Des'];
										}else{ 
							 			  echo "&nbsp;";
									}
									$i++;
									?>
									</td>
										<?php 	if($i % 3 == 0)
											{
							 				 break;
											}
									}	?>
								</tr>
								<?php
								}
						}   ?>
					</table>
						</fieldset>  
 <?php
  exit();
}
if (isset($ajax_codigo))
{
	$row_rs_vercodigo = $obBD_con1->getRowConsulta(1, $codpla.'*'.$cod_cuenta.'*'.$Ses_Emp_Cod, $obBD_conexion);
	if (count($row_rs_vercodigo) > 0)
	{ 
	?>
     <input name="cod_cuenta" type="text" id="cod_cuenta" value="" onBlur="parametro_x(this, '.') 
              ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_codigo&codpla=<?php echo $codpla; ?>&cod_cuenta='+this.value, 'div_existe')">	  
    <font color="#FF0000">El código de cuenta <?php echo $cod_cuenta; ?> ya existe</font>
    <?php	
	}
	else
	{ ?>
     <input name="cod_cuenta" type="text" id="cod_cuenta" value="<?php echo $cod_cuenta; ?>" onBlur="validar_cuentas(this.form, this); 
                  ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_codigo&codpla=<?php echo $codpla; ?>&cod_cuenta='+this.value, 'div_existe')">	 		
   <?php
	}
	exit();
}
/** 
 * Control para iniciar el buscador al inicio de los planes de cuentas
 */
if (isset($hdd_volver))
{
	unset($np);	
}
if (!isset($np))
{
	/**
	 * Caso 1 nuevo, Caso 2 modificar.
	 */	 
	$row_rs_planes = $obBD_con1->getArrayConsulta(302, $Ses_Emp_Cod ,$obBD_conexion);
	//var_dump($row_rs_planes);
 switch($hdd_save)
   {
     case 1:
	  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
	  $Chk_Campo = $_POST['tipos'];
	  $obBD_con1->operacionobBD(6, $cmb_opciones.'*'.$codpla, $obBD_conexion);
	  foreach ($Chk_Campo as $row)
	  {
				$obBD_con1->operacionobBD(5, $cmb_opciones.'*'.$row, $obBD_conexion);
	  }
	 $obBD_con1->fin_transaccion($obBD_conexion->conexion);
	break;
	 
	case 2:
      $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
	  $Chk_Campo = $_POST['tipos'];
	  $obBD_con1->operacionobBD(6, $cmb_opciones.'*'.$codpla, $obBD_conexion);
	  foreach ($Chk_Campo as $row)
	  {
				$obBD_con1->operacionobBD(5, $cmb_opciones.'*'.$row, $obBD_conexion);
	  }
	    $obBD_con1->fin_transaccion($obBD_conexion->conexion);
	 break;
   }
}
else
{
	/**
	 * Grabado de una cuenta nueva en algun nodo del plan
     */
	if (isset($ncuenta) && !isset($hdd_volver))
		{
			$row_rs_vercodigo = $obBD_con1->getRowConsulta(1, $codpla.'*'.$cod_cuenta.'*'.$Ses_Emp_Cod, $obBD_conexion);
			if (count($row_rs_vercodigo) > 0)
			{ ?>
            	<script type="text/javascript">
					alert('El código de cuenta <?php echo $cod_cuenta; ?> ya existe');
				</script>
			<?php
			} 
			else 
			{
				$Tipos = $_POST['tipos'];
				if(count($Tipos)>0)
				{
					$obBD_con1->inicio_transaccion($obBD_conexion->conexion);  
					foreach($Tipos as $una)
					{
						$obBD_con1->operacionobBD(304,$_POST['cmb_opciones'].'*'.$una,$obBD_conexion);
					}
					$obBD_con1->fin_transaccion($obBD_conexion->conexion);			
   			   }
			}
		}
	/**
     * Cargado de los nodos - Codigo Empresa, Codigo Plan de Cuenta, Nodo Padre	
	 */
	if (isset($np))
		{
			
			/** 
			 *Cargado del plan de cuenta de una empresa en especifico.
			 */
			$row_rs_plan = $obBD_con1->getRowConsulta(310, $codpla, $obBD_conexion);
                        $Pec_Cod=$row_rs_plan['Pec_Cod'];
		}
}//Fin del if (!isset($np))
?>
<HTML>
	<HEAD>
    <!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?php echo "Param. Estado Financiero [EXA]"; ?></TITLE>
    <meta charset= "UTF-8">
		<?php require_once("../../mascaras/model1/estilos/estilos.php"); ?>  
  
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../VALIDACIONES/con_val_estado.js?x=5"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
                
          });              			
		</script>
	
	</HEAD>
<BODY>
<div id="set1">
 <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table"> 
	<tr>
	  <td class="BarraTitulo">&raquo; Configurar Balances</td>
    </tr>
	<tr>
      <td align="left" valign="top"> 
 <?php
 if (!isset($np))
 {
 ?>         
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de la Busqueda</label>
	</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
	<thead>
      <tr>
        <th width="8%"><strong>C&oacute;d. Int.</strong></th>
        <th width="88%"><strong>Descripci&oacute;n</strong></th>
        <th width="4%">&nbsp;</th>
      </tr>
	</thead>
    <tbody>
      <?php
	  /* Evalua si existen planes de cuenta creados */
	  if (count($row_rs_planes) > 0) 
	  {
		foreach($row_rs_planes as $row)
		{
	 		 if($row['Pla_Est']=='Inactivo')
	  		  { 
					$rojo='#FF0000'; $anulada++; 			
					?>
				  <tr>
					<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Pla_Cod']; ?></FONT></td>
					<td><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Pla_Obs']; ?></FONT></td>
					<td align="center">
					<form name='form1' method='post'  action='<?php echo $_SERVER['PHP_SELF'];?>'>
					     <img src="../../mascaras/model1/imagenes/32x32/encrypted.png" width="25" height="25">
					<input type="hidden" id="codpla" name="codpla" value="<?php echo $row['Pla_Cod']; ?>" />         
					<input type="hidden" id="np" name="np" value="0" />   
                                        <input type="hidden" name="op" value="B" alt="" />
				   </form> 
					</td>
				  </tr>
			  <?php
	   		  }
			  else
	          {
	            $rojo='';
				   ?>
				   <tr>
				<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Pla_Cod']; ?></FONT></td>
				<td><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Pla_Obs']; ?></FONT></td>
				<td align="center">
				<form name='form1' method='post'  action='<?php echo $_SERVER['PHP_SELF'];?>'>
				<button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
					<i class=" icon-arrow-right icon-white"></i>
				</button>
				<input type="hidden" id="codpla" name="codpla" value="<?php echo $row['Pla_Cod']; ?>" />         
				<input type="hidden" id="np" name="np" value="0" />  
                                <input type="hidden" name="op" value="B" alt="" />
			   </form> 
				</td>
			  </tr>
			<?php 
	    	 } 
		 }//foreach($row_rs_planes as $row)
	   }//Fin del if ($total_rs_planes > 0)  
	  else 
	  { ?>
	  	<tr><td>&nbsp;</td>
	  	  <td><?php echo error_alerta("!No hay resultados que mostrar!", 1) ?></td>
	  	  <td>&nbsp;</td>
	  	</tr>
 <?php }//Fin del else if ($total_rs_planes > 0)  ?> 
 		</tbody>        
    </table>   
  	</FIELDSET>	
	<?php
   if ($anulada > 0)
        {		
            $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?php
	echo barra_estado(count($row_rs_planes));
    require_once('../../componentes/FRONT/com_con_leyenda.php');?>  
 <?php
 }//FIn del if (!isset($np))
/**
 * Selecci�n de un plan de cuentas para la inserci�n de cuentas contables
 */
if (isset($np)) 
{ 
                        $pag1= $_SERVER['PHP_SELF']."?op=B&codpla=$codpla&np=$np";
			$pag2= $_SERVER['PHP_SELF']."?op=U&codpla=$codpla&np=$np";			
			switch($op) {
				case "B": $activo = 1; break;
				case "U": $activo = 2; break;
                                default: $activo = 1; break;
                        }
			tabs(2,"Balances*Utilidad", $pag1.'*'.$pag2, $activo);
    ?>
	<br>
   <?php if($op=="B"){?>
            <?php
            /** 
			 *Cargado de las cuentas del plan
			 */
			$row_rs_nodos = $obBD_con1->getArrayConsulta(303,$codpla, $obBD_conexion);
            ?>
	<FIELDSET>
	<LEGEND><?php //var_dump($row_rs_plan); ?>
	<label class="Titulos2"><?php echo $row_rs_plan['Pla_Obs']; ?></label>
	</LEGEND>
	<table width="516" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="123" class="Etiqueta1">Usted esta editando: </td>
		<td width="393" class="LetraNegra"><strong>&nbsp;
		  <?php
		if ($np==0) 
		{
			echo "INICIO del Plan de Cuentas";
			$separador='';
		} 
		else 
		{
			$row_rs_direc = $obBD_con1->getRowConsulta(305, $np, $obBD_conexion);
			echo $row_rs_direc['Pld_Cdc'].".-  ".$row_rs_direc['Pld_Des'];
			$separador='.';	
 
		}//Fin del if ($np==0) 
		?>
		</strong></td>
	  </tr>
	</table>
	<table width="100%" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
		<th width="8%" class="Cabecera"><strong>C&oacute;d. Int.</strong></th>
		<th  width="84%" class="Cabecera"><strong>Tipo de Balance</strong></th>
		<th width="8%" class="Cabecera">&nbsp;</th>
		</tr>
    </thead>
    <tbody>
<?php
  if (count($row_rs_nodos) > 0) 
  {
	foreach($row_rs_nodos as $row)
	{
		if ($row['Pld_Est'] == 'Inactivo') 
		   { 
				$color_d = '#FF0000'; 
				$anulada++;
			} 
			else
			{
				$color_d = '';	
			} ?>
	  <tr>
		<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Est_Cod']; ?></font></td>
		<td><font color="<?php echo $color_d; ?>"><?php echo $row['Est_Des']; ?></font></td>
		<td align="center">
        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form33">
			<button type="button" class="btn btn-primary btn-mini" title="Editar" onclick="modalAparecer();ajax_datos('<?php echo $_SERVER['PHP_SELF'].'?Est_Cod='.$row['Est_Cod'].'&Est_Des='.$row['Est_Des'].'&codpla='.$codpla.'&opx=1';?>','asp')">
	        <i class="icon-edit icon-white"></i>		</button>
        </form>
        </td>
		</tr>
	  <?php } //Fin del foreach($row_rs_nodos as $row)
	  } else { ?>
	  	<tr><td>&nbsp;</td>
	  	  <td><?php echo error_alerta("No hay ninguna cuenta creada", 1) ?></td>
	  	  <td>&nbsp;</td>
	  	</tr>
	  <?php }?>
      </tbody>
	</table>
	<table width="210" border="0" cellpadding="0" cellspacing="0">
      <tr>
   <?php	
	if ($np!=0) 
	{
		/**
		 * Link para volver atr�s
		 */
		$row_rs_direca = $obBD_con1->getRowConsulta(306, $np, $obBD_conexion); ?>                
        <?php
	} //Fin del if ($np!=0) ?>        
     </tr>
    </table>            
	</FIELDSET>
  <?php    
	if ($anulada > 0)
        {		
         $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?php
    require_once('../../componentes/FRONT/com_con_leyenda.php');?>  
        <div style="padding-left: 6px;"> <?php echo barra_estado(count($row_rs_nodos)); ?></div>
        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form5" style="padding: 6px;">
           
       <button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onClick="this.form.submit()">
       <i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span> </button>
	   <button type="button" class="btn btn-success start" title="Guardar"  onclick="modalAparecer();ajax_datos('<?php echo $_SERVER['PHP_SELF'].'?Est_Cod='.'0'.'&Est_Des='.$Est_Des.'&codpla='.$codpla.'&opx=1';?>','asp')">
        <i class="icon-plus icon-white"></i><span>Agregar</span></button>
    </form>
 	<?php } ?>   
   <?php if($op=="U"){?>
        
        <FIELDSET>
	<LEGEND>
	<label class="Titulos2"><?php echo $row_rs_plan['Pla_Obs']; ?></label>
	</LEGEND>
	<table width="516" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="123" class="Etiqueta1">Usted esta editando: </td>
		<td width="393" class="LetraNegra"><strong>&nbsp;
		  <?php
		if ($np==0) 
		{
			echo "INICIO del Plan de Cuentas";
			$separador='';
		} 
		else 
		{
			$row_rs_direc = $obBD_con1->getRowConsulta(305, $np, $obBD_conexion);
			echo $row_rs_direc['Pld_Cdc'].".-  ".$row_rs_direc['Pld_Des'];
			$separador='.';	
 
		}//Fin del if ($np==0) 
		?>
		</strong></td>
	  </tr>
	</table>
            
    
        <?php
            /** 
			 *Cargado de las cuentas del plan
			 */
			$row_rs_nodos = $obBD_con1->getArrayConsulta(317,$codpla, $obBD_conexion);
            ?>
        
        
        
    <table width="100%" cellpadding="0" cellspacing="0" class="fixedHeader01" id="tabla">
    <thead>
	  <tr>
		<th width="8%" class="Cabecera"><strong>C&oacute;d. Int.</strong></th>
		<th  width="74%" class="Cabecera"><strong>Cuenta</strong></th>
                <th  width="10%" class="Cabecera"><strong>Tipo</strong></th>
		<th width="8%" class="Cabecera">&nbsp;</th>
		</tr>
    </thead>
    <tbody>
<?php
  if (count($row_rs_nodos) > 0) 
  {
	foreach($row_rs_nodos as $row)
	{
		if ($row['Pld_Est'] == 'Inactivo') 
		   { 
				$color_d = '#FF0000'; 
				$anulada++;
			} 
			else
			{
				$color_d = '';	
			} ?>
	  <tr>
		<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Cod']; ?></font></td>
		<!--td><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Des']; ?></font></td-->
		<td><font color="<?php echo $color_d; ?>"><?php echo mb_convert_encoding($row['Pld_Des'], 'UTF-8', 'ISO-8859-1'); ?></font></td>

                <td align="center"><?php echo ($row['Uti_Tip']=='G'?'Ganancias':($row['Uti_Tip']=='P'?'Perdidas':'Part. Impuestos')); ?></td>
		<td align="center">
        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form34">
			<button type="button" class="btn btn-danger btn-mini" title="Editar" onclick="anulaUtili('<?php echo $row['Pld_Cod']; ?>','<?php echo $row['Pec_Cod']; ?>','<?php echo $row['Uti_Tip']; ?>')">
	        <i class="icon-trash icon-white"></i>		</button>
        </form>
        </td>
		</tr>
	  <?php } //Fin del foreach($row_rs_nodos as $row)
	  } else { ?>
	  	<tr><td>&nbsp;</td>
	  	  <td><?php echo error_alerta("No hay ninguna cuenta creada", 1) ?></td>
	  	  <td>&nbsp;</td>
	  	</tr>
	  <?php }?>
      </tbody>
	</table>
        
        
        
        
          </FIELDSET>
        <?php    
	if ($anulada > 0)
        {		
         $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?php
    require_once('../../componentes/FRONT/com_con_leyenda.php');?>  
        <div style="padding-left: 6px;"> <?php echo barra_estado(count($row_rs_nodos)); ?></div>
        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form5" style="padding: 6px;">
           
       <button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onClick="this.form.submit()">
       <i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span> </button>
	   <button type="button" class="btn btn-success start" title="Guardar"  onclick="modalAparecer();">
        <i class="icon-plus icon-white"></i><span>Agregar</span></button>
    </form>
   <?php } ?>
 <?php } ?>
    </td>
  </tr>
</table>
   
 <div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
  <div id="bgmodal"  class="bgmodal" style="display:none" >
	<div id="ajax_modal">
<?php if(isset($op)){if($op=="B"){?>
	<form action="<?php echo $_SERVER['PHP_SELF']?>" method="post" name="form10" id="form10" >
		  <?php $thisPost->startPost();?>
			<div id="asp"></div>
	</form>
		<form action="#" method="post" name="form3" id="form3" >
	   		<label class="LetraNegra">Marcar/Desmarcar Todos:&nbsp;</label><input type="checkbox" name="chkTodos" id="chkTodos" onclick="Marcar(this,form10);">
		</form>
		<table width="100%" border="0">
			<tr>
			  <td width="106" height="23">
				<a type="button" class="btn btn-primary start" title="Guardar" onClick="if(validar(form10)){validar_requeridos(form10, 'cmb_opciones', 1);}" >
			    <i class="icon-book icon-white"></i>  <span>Guardar</span>  </a>
		      </td>
			</tr>
	    </table>
<?php }if($op=="U"){?>
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2"> 	
<table width="100%" border="0" cellspacing="0" cellpadding="0">
 <tr>
    <td>	
    <FIELDSET>
	<LEGEND>
	<label class="Titulos2">B&uacute;squeda de Cuentas</label>
	</LEGEND>
	<table width="481" border="0" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="205"><input id="op_opciones" name="op_opciones" type="radio" checked="checked" value="d" onClick="document.getElementById('op_opciones').value='d'; setfocus(this.form.buscta)">
			<span class="LetraNegra"><strong>Descripci&oacute;n</strong></span></td>
		  <td width="266"><input id="op_opciones" name="op_opciones" type="radio" value="c" onClick="document.getElementById('op_opciones').value='c'; setfocus(this.form.buscta)">
                          <span class="LetraNegra"><strong>C&oacute;digo</strong></span><input type="text" style="display:none;" /></td>
          <td width="266"><input id="op_opciones" name="op_opciones" type="radio" value="g" onClick="document.getElementById('op_opciones').value='g'; setfocus(this.form.buscta)">
                          <span class="LetraNegra"><strong>Grupos</strong></span><input type="text" style="display:none;" /></td>
		</tr>
	</table>
	<table width="600" height="36" border="0" cellpadding="0" cellspacing="0">
	<tbody id="tbusqueda">
      <tr>
        <td width="80" height="28" class="BarraBusqueda"><div align="right"><strong>Descripci&oacute;n:</strong></div></td>
        <td width="387" class="BarraBusqueda"><input name="buscta" type="text" id="buscta" size="50" maxlength="50" style="text-transform:uppercase" onKeyUp="parametro_injection(this)" onKeyPress="if (trim(document.getElementById('buscta').value) != ''){ enter_ajax('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_buscod1=1&buscod='+document.getElementById('buscta').value+'&op_opciones='+document.getElementById('op_opciones').value+'&Pec_Cod=<?php echo $Pec_Cod; ?>&Pla_Cod=<?php echo $codpla; ?>','busqueda')}
		"></td>
        <td width="109" align="center">
        <button type="button" class="btn btn-success fileinput-button" title="Buscar cuenta" onClick="if (trim(document.getElementById('buscta').value) != ''){ ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_buscod1=1&buscod='+document.getElementById('buscta').value+'&op_opciones='+document.getElementById('op_opciones').value+'&Pec_Cod=<?php echo $Pec_Cod; ?>&Pla_Cod=<?php echo $codpla; ?>','busqueda') }">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
           </button></td>
      </tr>
	</tbody>
    </table>
	<div id="busqueda"></div>
	</FIELDSET>
   </td>
  </tr>
 </table>
</form>
<?php }}; ?>
	</div>
  </div>


</div>
<script>
<?php if(isset($op)){if($op=="U"){?>
 function anulaUtili(asi,che,tipo)
    {
            var op= confirm("Est\u00E1 seguro de realizar esta operaci\u00F3n?");

            if (op === true)
            {
                   //alert(asi+" "+che);
                   $.post( "<?php echo $_SERVER['PHP_SELF']; ?>",{anula:true,PldCod:asi,PecCod:che,Tipo:tipo}, function( response ) {
                        $("body").append(response);                        
                       //setTimeout(function () {
                            document.location.href='<?php echo $pag2; ?>';
                       //     },1500);
                   }).fail(function(error) { alert("El Servidor ha fallado en responder!"); });
            }
    }
function agregarUti(asi,che,tipo){
    var op= confirm("Est\u00E1 seguro de realizar esta operaci\u00F3n?");

    if (op === true)
    {
           	$.post( "<?php echo $_SERVER['PHP_SELF']; ?>",{grabacta:true,PldCod:asi,PecCod:che,Tipo:tipo}, function( response ) {
                $("body").append(response);                        
               //setTimeout(function () {
                    document.location.href='<?php echo $pag2; ?>';
               //     },1500);
           }).fail(function(error) { alert("El Servidor ha fallado en responder!"); });

    }
}

function agregarUtiParImp(asi,che,tipo){
    var op= confirm("Est\u00E1 seguro de realizar esta operaci\u00F3n?");

    if (op === true)
    {

    	var tb = document.getElementById("tabla");
    	var cells = tb.getElementsByTagName("td");
    	var validar = true;

	  	for(var i = 0; i < cells.length; ++i){
		    if(cells[i].textContent.toLowerCase() === 'part. impuestos'){
		    	validar = false;
		    }
		}

       if(validar){
           	$.post( "<?php echo $_SERVER['PHP_SELF']; ?>",{grabacta:true,PldCod:asi,PecCod:che,Tipo:tipo}, function( response ) {
                $("body").append(response);                        
               //setTimeout(function () {
                    document.location.href='<?php echo $pag2; ?>';
               //     },1500);
           }).fail(function(error) { alert("El Servidor ha fallado en responder!"); });
       }
       else{
           alert('Ya existe una cuenta parametrizada para Participacion e Impuestos!');
    	}
    }
}

<?php }}; ?>
</script>
<script type="text/javascript" src="../VALIDACIONES/con_par_estado.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY></HTML>
<?php
$obBD_conexion->cerrar();
?>