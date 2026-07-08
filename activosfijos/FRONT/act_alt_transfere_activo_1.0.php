<?php 
/* 
 * Alias: Consulta de Custodio
 * Descripción: Permite la consulta de custodio 
 * Desarrollador: Didimo Zamora
 * Fecha de actualización:	2013/06/11
 * Fecha de actualización:	2013/08/20
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_transfere_activo.php');	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

/**
 * Objeto de Conexion de Contabilidad
 */
$obBD_conexion = new Class_Log_Conexion_Cch($Ses_Dat_Dis);
/**
 * Objeto de Acceso a Datos de Contabilidad	
 */
$obBD_con1 = new Class_Log_Datos_Cch;
/**
 * Creación del objeto para evitar el reenvio 
 */
$thisPost = new Post_Block;  

/**
 * Consulta de la cabecera del reporte 
 */

$rs_institucion = $obBD_con1->getRowConsulta(134,$Ses_Suc_Cod,$obBD_conexion);
$total_rs_institucion = count($rs_institucion);

$hoy = date("Y-m-d");

 if ($thisPost->postBlock($_POST['postID'])) 
 { 
 if($txt_busqueda != "")
	 { 
	 	if ($op_opciones == 1)
		{
			/**
			 * Busqueda de los scustodios x el Apellido
			 */
			$rs_buscar = $obBD_con1->getArrayConsulta(137,$Ses_Emp_Cod.'*'.$txt_busqueda, $obBD_conexion);		
		}
		if ($op_opciones == 2)
		{
			/**
			 * Busqueda de los scustodios x la Cedula
			 */
			$rs_buscar = $obBD_con1->getArrayConsulta(5,$Ses_Emp_Cod."*".$txt_busqueda, $obBD_conexion);		
		}		
			$total_rs_buscar = count($rs_buscar);
	 }
	 else{
			  if (isset($codigo)){
				$rs_consultar = $obBD_con1->getArrayConsulta(135,$codigo.'*'.$Ses_Emp_Cod, $obBD_conexion);
				$total_rs_consultar = count($rs_consultar);
			  }			  
	     }
     }	 
	 
	 if(isset($hh_save)){
		/**
		 * Se  Guarda la transferencia.
		 */ 
		 if (isset($arr)){	
		 		 
		 	
	 		$cant_act = explode('*',$arr);
		 	if(count($cant_act)>1){		 
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			
			$Sec_Cod=1;
			for ($j=1; $j<= count($cant_act)-1; $j++)
				{						
						/**
						 * Consultar el orden para una nueva asignación.
						 */
						$rs_conOrd = $obBD_con1->getRowConsulta(144,$Cus_CodN.'*'.$cant_act[$j], $obBD_conexion);
						$Ord_Default = $rs_conOrd['Orden'];
						$Ord_Default++;						
						//Inserto en asignacion activo fijo y custodio nuevo
						$obBD_con1->operacionobBD(149, $Cus_CodN.'*'.$cant_act[$j].'*'.date("Y-m-d").'*'.date("H:i:s").'*'.$Sec_Cod.'*'.$Ord_Default.'*'.$razon_tranf,$obBD_conexion);	
						/**
						 * Actualizacion del estado de la asignacion para custudio que entrega.
						 */	
						$obBD_con1->operacionobBD(151, $codigo.'*'.$cant_act[$j],$obBD_conexion);
						
								
						 
				}				
			 $obBD_con1->fin_transaccion($obBD_conexion->conexion);	
			}
		 }	 	 
		 unset($arr);
		 unset($txt_busqueda);
		 unset($codigo);
		 /**
		  * Inserción de los activos seleccionados 
		  */
	 }	 
?> 
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
			<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
            <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
            <script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
            <script type="text/javascript" src="../VALIDACIONES/act_val_transfere_activo.js"></script>
            <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>  
            <script type="text/javascript"> 
			  $(function() {
					$('#set1 *').tooltip({showURL: false});
			  });              			
			</script>                 
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" >
	<tr class="BarraTitulo">
	  <td colspan="3" height="10">&raquo; Transferencia de Activos Fijos</td>
    </tr>
	<tr>
	  	<td valign="top" colspan="3">		
          <fieldset>
          <LEGEND>
            <label class="Titulos2">Buscar Custodio por:&nbsp;</label>
           </LEGEND><form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']."?op=1"?>">
           <table width="100%" height="70" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td colspan="2">
                <table width="317" border="0">
                  <tr>
                    <td width="205"><input name="op_opciones" type="radio" value="1" onClick="setfocus(this.form.txt_busqueda)" checked>
                        <span class="LetraNegra">Apellidos</span></td>
                    <td width="313"><input type="radio" name="op_opciones" value="2" onClick="setfocus(this.form.txt_busqueda)">
                        <span class="LetraNegra">Cédula</span>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td width="95" height="42"class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Busqueda: </div></td>
              <td width="293" class="BarraBusqueda">&nbsp;<input name="txt_busqueda" type="text" id="txt_busqueda" size="40">
              </td>
              <td class="BarraBusqueda" width="135" valign="middle">
                <button title="Buscar" name="btn_aceptar" type="submit" class="btn btn-success fileinput-button" id="btn_aceptar" value="Aceptar">
                <i class="icon-search icon-white"></i>
                <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>   
                </button>
              </td>
              <td width="437">&nbsp;</td>
            </tr>
          </table>
          </form>
          </FIELDSET>
          
<?php if (isset($txt_busqueda)){?>
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
    </LEGEND>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
      <thead>
          <th width="8%">Cód Int</th>
          <th width="20%">C&eacute;dula</th>
          <th width="64%">Custodio</th>
          <th width="8%">&nbsp;</th>         
      </thead>
         <tbody>
          <?Php 
          if ($total_rs_buscar > 0){  		
            foreach($rs_buscar as $row_rs_buscar){ 
               if($row_rs_buscar['Cus_Est']=='I')
               { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
          ?>
          <tr>
          <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row_rs_buscar['Cus_Cod'];?></FONT></td>
          <td><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Prs_Ced'];?></FONT></td>
          <td><p><FONT COLOR="<?php echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Nombre'],'#FFFF00', 1);?>    
         </FONT></p>
         </td>
          <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "frml" id="forml">
          <td align="center" width="8%">
          <?Php if($row_rs_buscar['Cus_Est']=='A')
             { 
                  ?>         	
                <button type="image" name="imageField" width="22" height="22" title="Seleccionar"  class='btn btn-success btn-mini'>	
                <i class='icon-arrow-right icon-white'></i>
                </button>            				
                    <input type="hidden" name="codigo" id="codigo" value="<?Php echo $row_rs_buscar['Cus_Cod'];?>"/>
                    <input type="hidden" name="hdd_aux" id="hdd_aux" value="1">
                    <input type="hidden" name="volver_busqueda" id="volver_busqueda" value="<?Php echo $txt_busqueda;?>"/>
                    <input type="hidden" name="volver_opciones" id="volver_opciones" value="<?php echo $op_opciones?>">
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
          <?Php } //fin foreach($rs_buscar as $row_rs_buscar){       
          }else{
          ?>
            <tr>
              <td></td>
              <td>&nbsp;</td>
              <td align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
              <td> </td>
            </tr>
          <?php } // fin del if ($total_rs_buscar > 0)?>
          </tbody>
        </table> 
        <br>
	<?php 
	/**
	 *  Muestra la barra de estados con la cantidad de registros encontrados 
	 */
		echo barra_estado($total_rs_buscar+0);
} 
if(isset($codigo)){	
	/**
	 * Consulta de los datos del custodio
	 */ 
	$rs_custodio = $obBD_con1->getRowConsulta(136,$codigo.'*'.$Ses_Emp_Cod, $obBD_conexion);
?>
<?php 
 /** 
  * Creacion del campo REPOST
  */
$thisPost->startPost();?>
 <table   width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
 <tr>
  <td width="51%" valign="top">
<FIELDSET>
    <LEGEND>
    <label class="Titulos2">Se Transfiere de:</label>
    </LEGEND>
	<table width="100%" border="0" align="left" cellpadding="0" cellspacing="0">
      <tr>
        <td width="11%" class="Etiqueta1" ><span  class="Etiqueta1">Custodio:</span></td>
        <td width="89%">&nbsp;<span class="LetraNegra"><?php echo  $rs_custodio['Nombre'];?></span></td>
      </tr> 
      <tr>
        <td width="11%" height="23" class="Etiqueta1" ><span class="Etiqueta1">Cédula: </span></td>
        <td width="89%">&nbsp;<span class="LetraNegra"><?php echo  $rs_custodio['Prs_Ced'];?></span></td>
       </tr>    
       <tr>
        <td width="11%" height="25"  class="Etiqueta1"><span class="Etiqueta1">&Aacute;rea: </span></td>
        <td width="89%">&nbsp;<span class="LetraNegra"><?php echo $rs_custodio['Dep_Des'];?></span></td>
       </tr> 
       <tr>
        <td width="11%" >&nbsp;</td>
        <td width="89%">&nbsp;</td>
       </tr> 
    </table>
    <p>
  </FIELDSET>
</td>

<td width="49%" valign="top">
<FIELDSET>
    <LEGEND>
    <label class="Titulos2">Al Destino:</label>
    </LEGEND>   
    <table width="100%" border="0" align="left" cellpadding="0" cellspacing="0">
     <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "formdes">
     <tr>
     
        <td width="24%" class="Etiqueta1"><span class="Asterisco">*</span>Custodio : </td>
        <td width="76%">
        <?Php 
			/**
			 * Consulta los Custodios 
			 */
			$rs_cus_act = $obBD_con1->getArrayConsulta(143,$Ses_Emp_Cod, $obBD_conexion);
			$total_rs_cus_act = count($rs_cus_act);
		?>
        <select name="Cus_CodN" id="Cus_CodN" onChange="document.getElementById('raz').value= this.value">
        	 <option value="" >Seleccione...</option>
            <?php  
			foreach($rs_cus_act as $row_rs_cus_act){
			 if ($rs_custodio['Cus_Cod']<>$row_rs_cus_act['Cus_Cod']){	
			 ?>
               <option value="<?php echo $row_rs_cus_act['Cus_Cod']?>"><?Php echo $row_rs_cus_act['Nombre']?> </option>
     		<?php 
			 }
	  		} ?>
        </select>  
        <input name="codigo" type="hidden" id="codigo" value="<?Php echo $codigo; ?>"> 
        <input name="Cus_CodNew" type="hidden" id="Cus_CodNew" value="<?Php echo $Cus_CodN; ?>">
        <input name="hh_save" type="hidden" id="hh_save" value="0">                    
        <input  size="50" name="arr" type="hidden" id="arr" value="">      
        </td>           			      
      </tr>   
      <tr>
        <td width="24%" class="Etiqueta1"><span class="Asterisco">*</span> Razón de Traslado :</td>
        <td width="76%"><textarea name="razon_tranf" cols="37" id="razon_tranf" rows="3"></textarea>  </td>
      </tr> 
       
       </form>
       
</table> 
  
  </FIELDSET>
  </td>
  </tr>
   <tr>
      <td colspan="2">
      
      <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Lista de Activos del Custodio Actual</label>
    </LEGEND>
    	<table width="100%" border="0"   cellspacing="0" class="fixedHeader03" >
      	<thead> 
              <th width="4%"> Ord.</th>
              <th width="14%"> Cod. Activo</th>
              <th width="31%">Descripci&oacute;n del Activo</th>     
              <th width="13%">Selecci&oacute;n</th> 
              <th width="38%">Activo seleccionado</th>           			 
       </thead>
       <tbody>
                <?php if( $total_rs_consultar > 0){
                    $j= 0;
                    //print_r($rs_consultar);
                    foreach($rs_consultar as $row_rs_consultar){
                    $j++;
                ?>                                             
            <tr>
                <td class="LetraNegra" align="center"><?php echo $j;?></td>
                <td class="LetraNegra" align="center"><?php echo $row_rs_consultar['Act_Cod'];?></td>
                <td class="LetraNegra">
                    <label id="Ac[<?php echo $j;?>]"> <?php echo $row_rs_consultar['Act_Des'];?> </label>    
                    <input name="Act_Cod[<?php echo $j?>]" type="hidden" id="Act_Cod[<?php echo $j?>]" value="<?php echo $row_rs_consultar['Act_Cod']?>">       	
                </td>           
                <td class="LetraNegra" align="center">
                    <input name="activo[<?php echo $j?>]" type="hidden" id="activo[<?php echo $j?>]" value="0">            
                    <button type="image"  name="selecback[<?php echo $j;?>]" id="selecback[<?php echo $j?>]" width="22" height="22" title="No transferir activo"  class='btn btn-danger btn-mini' onClick="document.getElementById('activo[<?php echo $j;?>]').value=0;Regresar_Activo(<?php echo $j?>,<?php echo $total_rs_consultar?>)">	
                    <i class='icon-arrow-left icon-white'></i>
                    </button>  
                            
                    <button type="image" name="selec[<?php echo $j?>]" id="selec[<?php echo $j?>]"  width="22" height="22" title="Transferir activo"  class='btn btn-success btn-mini' onClick="document.getElementById('activo[<?php echo $j;?>]').value=1;Pasar_Activo(<?php echo $j?>,<?php echo $total_rs_consultar?>)">	
                    <i class='icon-arrow-right icon-white'>
                    </i>
                    </button>
                </td>
                <td class="LetraNegra" align="center">
                <label id="AcOld[<?php echo $j;?>]"> <?php echo $row_rs_consultar['Act_Des'];?> </label>
                </td>       
            </tr>  
         <?php 	
              }//fin foreach($rs_consultar as $row_rs_consultar){
            }//Fin if( $total_rs_consultar > 0){
            else
            {
         ?>
            <tr>
                <td>  </td>
                 <td>  </td>
                <td align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?>  </td>
                <td>  </td>
                <td>  </td>
            </tr>  
     <?Php  } ?>
		</tbody>
		</table>
        </FIELDSET>
<?Php
	/**
	 *  Muestra la barra de estados con la cantidad de registros encontrados 
	 */
		echo barra_estado($total_rs_consultar+0);
	?>  
       </td>
   </tr>
   <tr>
   	<td colspan="2">
   			<table width="100%" border="0" cellpadding="0" cellspacing="0">
           	<tr>
             	<td width="9%" >
                 <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1"> 
                        <button type="button" name="btn_atras" id="btn_atras" value="Enviar" class="btn btn-inverse fileinput-button" title="Atr&aacute;s"
                        onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver"; ?>','<?Php echo $volver_busqueda.'*'.$volver_opciones.'*'.'1'; ?>')">
                         <i class="icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
                        </button>           
                 </form>                
             	</td> 
             	<td width="91%">
         
                     <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form10" id= "form10" >     
                                 <button name="boton_guardar" id="boton_guardar" title="Guardar" type="button" class="btn btn-primary fileinput-button" value="Guardar" onClick="validar_transfe(formdes,'Cus_CodN*razon_tranf*arr',1)">
                                 <i class=" icon-book icon-white"></i>
                                 <span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span>
                                </button>               
                       </form> 
         		</td>                	 
         	</tr>
        	</table> 
    </td> 
   </tr>
</table>
  <?php }// Fin if(isset($codigo)){?>
  </fieldset>                
</td>
</tr>
</table>
 </div>
<script type="text/javascript" src="../VALIDACIONES/act_par_transfere_activo.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js?fdf"></script>

<?Php for ($i = 0; $i<=$j; $i++){ ?> 
	<script> 
		ShowHide('AcOld[<?php echo $i;?>]');
		ShowHide('selecback[<?php echo $i;?>]');
	</script>     
<?Php }?>

</BODY>
</HTML>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
 
?>