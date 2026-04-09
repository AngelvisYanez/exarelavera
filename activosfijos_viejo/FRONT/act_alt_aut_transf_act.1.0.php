<?php 
/************************************************************************** 
 * Alias: Autorización de Activos a Custodio.							  *
 * Descripción: Permite la confirmación final de los activos a un custodio*
 * Desarrollador:  Didimo Zamora						                  *
 * Fecha de actualización:	2013/06/11	
 * Fecha de actualización:	2013/09/06					                  *
 **************************************************************************/

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
 * Consulta de los custodios activos
 */
$rs_Custodios = $obBD_con1->getArrayConsulta(2002,$Ses_Emp_Cod, $obBD_conexion);
$total_rs_Custodios = count($rs_Custodios);

/**
 * Modal muestra lista de activos por confirmar
 */
if (isset($ajax_alt_Autor))
{	
?>
<? 
 /** 
  * Creacion del campo REPOST
  */
$thisPost->startPost();

$Ses_Emp_Cod =1;
	/**
	 * Consulta de los datos del custodio
	 */ 
	$rs_custodio = $obBD_con1->getRowConsulta(136,$codigo.'*'.$Ses_Emp_Cod, $obBD_conexion);
?>

 <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3" id= "form3" >  

 	<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
 	<tr>
  		<td>
            <FIELDSET>
                <LEGEND>
                <label class="Titulos2">Datos del Custodio:</label>
                </LEGEND>
                <table   width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="15%" ><span  class="Etiqueta1">Custodio: </span></td>
                    <td width="35%"> <span class="LetraNegra"><? echo  $rs_custodio['Nombre'];?></span></td>
                     <td width="20%" ><span  class="Etiqueta1">Departamento: </span></td>
                      <td width="30%" ><span  class="Etiqueta1"> Area: Departamento</span></td>
                  </tr> 
                  <tr>
                    <td width="15%" ><span class="Etiqueta1">CI: </span></td>
                    <td width="35%"><span class="LetraNegra"><? echo  $rs_custodio['Prs_Ced'];?></span></td>
                     <td width="20%" ><span  class="Etiqueta1"></span></td>
                      <td width="30%" ><span  class="Etiqueta1"> </span></td>
                   </tr>    
                </table>
             </FIELDSET>
		</td>
  </tr>
  <tr>
      	<td>     
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Lista de Activos  por confirmar</label>
            </LEGEND>
                    <table width="100%" border="0"   cellspacing="0" class="fixedHeader01" >
                       <thead> 
                            <th width="6%"> Cod.Int</th>
                            <th width="80%">Activo</th>    
                            <th width="14%">Estado</th>            			 
                       </thead>
                       <tbody> 
                       <?Php 
                        /**
                         * Consulta de los activos no cofirmados (Asignaciones), por  custodio y que esten activos
                         */
                        $rs_Asigna = $obBD_con1->getArrayConsulta(2001,$codigo, $obBD_conexion);
                        $total_rs_Asigna = count($rs_Asigna);
                        $str="";
                        if($total_rs_Asigna>0){
                            foreach($rs_Asigna as $row_rs_Asigna){
								$str.= "*".$row_rs_Asigna['Act_Cod'];
                       ?>                                         
                            <tr>
                                <td class="LetraNegra" align="center" ><? echo $row_rs_Asigna['Act_Cod'];?></td>
                                <td class="LetraNegra"><? echo $row_rs_Asigna['Act_Des'];?></td>      
                                <td class="LetraNegra" align="center"><? echo $row_rs_Asigna['Est_Des'];?></td>              
                            </tr>  
                        <?Php 
                            }
                        }
                        else{
                        ?>
                            <tr>
                                <td>&nbsp;</td>
                                <td align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?>  </td>
                                <td class="LetraNegra">&nbsp;</td> 
                            </tr>     
                        <?Php 
                        }
                        ?>    
                            </tbody>
                        </table>
           </FIELDSET>
	<?Php
	/**
	 *  Muestra la barra de estados con la cantidad de registros encontrados 
	 */
		echo barra_estado($total_rs_Asigna+0);
	?>  
       </td>
   </tr>
   <tr>
   	<td>
       <table width="100%" border="0" cellpadding="0" cellspacing="0">
       	<tr>
         <td width="91%">        
                 
                 <button name="boton_guardar" id="boton_guardar" title="Guardar" type="button" class="btn btn-primary fileinput-button" value="Guardar" onClick="validar_requeridos(this.form,'Cadena',1)">
                 <i class=" icon-book icon-white"></i>
                 <span>&nbsp;Confirmar&nbsp;</span>
                 </button>               
                 <input name="codigo" id="codigo" value="<?Php echo $codigo;?>" type="hidden">  
                 <input name="cadena" id="Cadena" value="<?Php echo $str;?>" type="hidden">
                 <input name="hh_save" type="hidden" id="hh_save" value="0"> 				                            
         </td>                	 
       	</tr>
       </table>       
   </td> 
   </tr> 
</table>
</form>
  <?  
  exit();
  }// Fin if(isset($codigo)){
	    
/**
 * Consulta de la cabecera del reporte 
 */

$rs_institucion = $obBD_con1->getRowConsulta(134,$Ses_Suc_Cod,$obBD_conexion);
$total_rs_institucion = count($rs_institucion);

$hoy = date("Y-m-d");
	 
	 if(isset($hh_save)){
		/**
		 * Se  Guarda la transferencia 
		 */ 
		 if (isset($arr)){	
	 		$cant_act = explode('*',$arr);	
		 	if(count($cant_act)>1){		 
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);	 	
			//$Ord_Default = 1;
			//$Sec_Cod=1;
			for ($j=1; $j<= count($cant_act)-1; $j++)
				{				
					//Actualiza estado de confirmación del activo fijo para el custodio.
						
							$obBD_con1->operacionobBD(2004, $codigo.'*'.$cant_act[$j],$obBD_conexion);						
				}				
			 $obBD_con1->fin_transaccion($obBD_conexion->conexion);	
			}
		 }	 	 
		 unset($cadena);
		 unset($codigo);
		 /**
		  * Inserción de los activos seleccionados 
		  */
	 }	 
?> 
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
        <script type="text/javascript" src="../VALIDACIONES/act_val_transfere_activo.js"></script>
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

<table width="100%" border="0" cellpadding="0" cellspacing="0" >	
	<tr class="BarraTitulo">
	  	<td height="10">Autorizar Transferencia de Activos Fijos</td>
  	</tr>
    <?Php 
	/**
	 * Si no esta codigo seleccionado entonces lista de asiganciones
	 */
	if(!isset($codigo)){
	?>
  	<tr>
  		<td>     
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Lista de Asignaciones por confirmar</label>
            </LEGEND>  
		<? 
         /** 
          * Creacion del campo REPOST
          */
        $thisPost->startPost();
        ?> 
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01" >
          <thead>
              <th width="10%">Cod. Int</th>
              <th width="85%">Custodio</th>                         
              <th width="5%">&nbsp;</th>
          </thead>
          <tbody>
			  <?Php
              $cont=0;
              if($total_rs_Custodios>0){
                foreach($rs_Custodios as $row_rs_Custodios){                  
                    /**
                     * Consultar las asignaciones  no confirmadas para cada custodio 
                     */	
                    $rs_asig_NoConf= $obBD_con1->getArrayConsulta(2003,$row_rs_Custodios['Cus_Cod'],$obBD_conexion);
                    $total_rs_asig_NoConf= count($rs_asig_NoConf);	
                    if($total_rs_asig_NoConf>0){
                    $cont++;
              ?>
             	<tr>
                  <td align="center"><?Php echo $row_rs_Custodios['Cus_Cod'];?></td>
                  <td><?Php echo $row_rs_Custodios['Nombres'];?></td>
                  <td align="center">
                  <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1" id= "form1" > 
                      <button  type="button" name="btnAutor" width="22" height="22" title="Seleccionar"  class='btn btn-success btn-mini' onClick="form.submit()" >	
            <i class='icon-arrow-right icon-white'></i>
                      </button> 
                      <input name="codigo" id="codigo" type="hidden" value="<?Php  echo $row_rs_Custodios['Cus_Cod'];?>"> 					</form>
                  </td>                       
                </tr>                                         
                        <?Php 	
						}// Fin de  if($total_rs_asig_NoConf>0){
						
                    } // Fin de foreach($rs_Custodios as $row_rs_Custodios){
						
						if($cont==0){
					?>                   
                   			<tr>                               
                                <td> </td>
                                <td align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
                                <td> </td>                               
                            </tr>          
                    <?	
						} //Fin if($cont>0){
						
                  }// fin if($total_rs_Custodios>0){ 
				  else
				  {
					?>
                        <tr>                               
                            <td> </td>
                            <td align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
                            <td> </td>                                
                        </tr>
                    <?  
				  }
                  ?>
            </tbody>   
        </table>
       </FIELDSET> 
        <?php
			echo barra_estado($cont).'<br>';
		?>
    </td> 
  </tr>
  <? }?>
  
  <tr>
<td>
<? 
if(isset($codigo)){

 /** 
  * Creacion del campo REPOST
  */
$thisPost->startPost();

	/**
	 * Consulta de los datos del custodio
	 */ 
	$rs_custodio = $obBD_con1->getRowConsulta(136,$codigo.'*'.$Ses_Emp_Cod, $obBD_conexion);
?>

 <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3" id= "form3" >  

 	<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
 	<tr>
  		<td>
            <FIELDSET>
                <LEGEND>
                <label class="Titulos2">Datos del Custodio:</label>
                </LEGEND>
                    <table   width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                      <tr>
                        <td width="15%" class="Etiqueta1" ><span  class="Etiqueta1">Custodio: </span></td>
                        <td width="22%">&nbsp;<span class="LetraNegra"><? echo  $rs_custodio['Nombre'];?></span></td>
                         <td width="13%" class="Etiqueta1" ><span  class="Etiqueta1">Departamento:</span></td>
                          <td width="50%" >&nbsp;<span class=" LetraNegra"><? echo  $rs_custodio['Dep_Des'];?></span></td>
                      </tr> 
                      <tr>
                        <td width="15%" class="Etiqueta1" ><span class="Etiqueta1">Cédula:</span></td>
                        <td width="22%">&nbsp;<span class="LetraNegra"><? echo  $rs_custodio['Prs_Ced'];?></span></td>
                         <td width="13%" ><span  class="Etiqueta1"></span></td>
                          <td width="50%" ><span  class="Etiqueta1"> </span></td>
                      </tr>    
                    </table>
             </FIELDSET>
		</td>
  </tr>
  <tr>
      	<td>     
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Lista de Activos  por confirmar</label>
            </LEGEND>
                    <table width="100%" border="0"   cellspacing="0" class="fixedHeader01" >
                       <thead> 
                      		<th width="5%"> Cod.Int</th>
                            <th width="25%">Activo</th>    
                            <th width="8%">Fecha</th> 
                            <th width="19%">Origen</th>
                            <th width="29%">Observación</th>
                            <th width="6%">Estado</th> 
                            <th width="8%">
                            <input title="Seleccionar/deseleccionar todos" type="checkbox" name="todos" id="todos" onClick="seleccionar_todos(document.getElementById('cant').value,'todos')"><samp class="LetraNegra">Todos</samp></th> 
                                        			 
                    	</thead>
                       <tbody> 
                       <?Php 
                        /**
                         * Consulta de los activos no cofirmados (Asignaciones), por  custodio y que esten activos
                         */
                        $rs_Asigna = $obBD_con1->getArrayConsulta(2001,$codigo, $obBD_conexion);
                        $total_rs_Asigna = count($rs_Asigna);
                        $str="";
						$i=0;
                        if($total_rs_Asigna>0){
                            foreach($rs_Asigna as $row_rs_Asigna){
								$i++;
								//$str.= "*".$row_rs_Asigna['Act_Cod'];
								$rs_custOld= $obBD_con1->getArrayConsulta(2005,$Ses_Emp_Cod.'*'.$row_rs_Asigna['Act_Cod'],$obBD_conexion);							
                       ?>                                         
                            <tr>
                                <td class="LetraNegra" align="center" ><? echo $row_rs_Asigna['Act_Cod'];?>
                                <input type="hidden" name="Act_Cod[<? $i;?>]" id="Act_Cod[<? echo $i;?>]" value="<? echo $row_rs_Asigna['Act_Cod'];?>"></td>
                                <td class="LetraNegra"><? echo $row_rs_Asigna['Act_Des'];?></td>           
                                <td class="LetraNegra" align="center"><? echo $row_rs_Asigna['Asg_Fec'];?></td>
                                <td class="LetraNegra" align="left"><? echo $rs_custOld[0]['Nombres'];?></td>
                                <td class="LetraNegra" align="left"><? echo $row_rs_Asigna['Act_Obs'];?></td>          
                                <td class="LetraNegra" align="center"><? echo $row_rs_Asigna['Est_Des'];?></td>
                                <td align="center"><input  type="checkbox" name="sel[<? echo $i;?>]" id="sel[<? echo $i;?>]" onClick="crear_lista_activos_check(<? echo $total_rs_Asigna?>)">
                                 <input type="hidden" name="Asg_Ord[<? echo $row_rs_Asigna['Asg_Ord'];?>]" id="Act_Cod[<? echo $row_rs_Asigna['Asg_Ord'];?>]" value="<? echo $row_rs_Asigna['Asg_Ord'];?>"></td>
                            </tr>  
                        <?Php 
                            }
                        }
                        else{
                        ?>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?>  </td>
                                <td class="LetraNegra">&nbsp;</td> 
                                <td>&nbsp;</td> 
                                <td>&nbsp;</td> 
                                <td>&nbsp;</td>  
                            </tr>     
                        <?Php 
                        }
                        ?>    
                      </tbody>
                </table>
           </FIELDSET>
	<?Php
	/**
	 *  Muestra la barra de estados con la cantidad de registros encontrados 
	 */
		echo barra_estado($total_rs_Asigna+0);
	?>  
       </td>
   </tr>
   <tr>
   	<td>
            
   </td> 
   </tr> 
</table>
</form>
<table width="257" border="0" cellpadding="0" cellspacing="0">
       	<tr>
        	<td width="88">
            	
            		<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
                    <button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onClick="this.form.submit();"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
                    <input type="hidden" name="hdd_volver" value="1">
                   </form>
                
            
            
            </td>
        
         	<td width="169">        
                 <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
                 <button name="boton_guardar" id="boton_guardar" title="Guardar" type="button" class="btn btn-primary fileinput-button" value="Guardar" onClick="validar_transfe(this.form,'arr',1)">
                 <i class=" icon-book icon-white"></i>
                 <span>&nbsp;Confirmar&nbsp;</span>
                 </button>               
                 <input name="codigo" id="codigo" value="<?Php echo $codigo;?>" type="hidden">  
                 <input name="arr" id="arr" value="<?Php echo $str;?>" type="hidden">
                 <input name="hh_save" type="hidden" id="hh_save" value="0"> 	
                 <input name="cant" type="hidden" id="cant" value="<?Php echo $total_rs_Asigna;?>"> 
                  
                 </form>			                            
         	</td>                	 
       	</tr>
       </table>  



<?Php 
}
?>
    </td>
  </tr>
</table>

</div>

<script type="text/javascript" src="../VALIDACIONES/act_par_transfere_activo.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>

<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
	  	<div id="bgmodal"  class="bgmodal" style="display:none" >
	 		<div id="ajax_modal">
			<div id="muestra"></div>
	 	</div>
</div> 

</BODY>
</HTML>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
 
?>