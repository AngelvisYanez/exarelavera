<?Php
/**********************************************************************
 * Alias: Control de Custodia de Activos Fijos                        *
 * Descripción: Permite verificar los activos dados al personal       *
 * Desarrollador: Didimo Zamora                                       *
 * Fecha de actualización:	2013/07/09                                *
 *********************************************************************/

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
 * Consulta de la tabla estado de activos fijos.
 */
$rs_estado = $obBD_con1->getArrayConsulta(4,'',$obBD_conexion);

/**
 * Consulta de la cabecera del reporte 
 */

$rs_institucion = $obBD_con1->getRowConsulta(5,$Ses_Suc_Cod,$obBD_conexion);
$total_rs_institucion = count($rs_institucion);

$hoy = date("Y-m-d");


	if($txt_busqueda != ""){ 
		if ($op_opciones == 1){
			/**
			 * Busqueda de los scustodios x el Apellido
			 */
			$rs_buscar = $obBD_con1->getArrayConsulta(2,$Ses_Emp_Cod.'*'.$txt_busqueda, $obBD_conexion);		
		}
		if ($op_opciones == 2){
			/**
			 * Busqueda de los scustodios x la Cedula
			 */
			$rs_buscar = $obBD_con1->getArrayConsulta(1,$Ses_Emp_Cod."*".$txt_busqueda, $obBD_conexion);		
		}		
			$total_rs_buscar = count($rs_buscar);
	 }
	 else{
			 if(isset($hdd_volver)){
					if ($op_opciones == 1){
						/**
						 * Busqueda de los scustodios x el Apellido
						 */
						$rs_buscar = $obBD_con1->getArrayConsulta(2,$Ses_Emp_Cod.'*'.$txt_busqueda, $obBD_conexion);		
					}
					if ($op_opciones == 2){
						/**
						 * Busqueda de los scustodios x la Cedula
						 */
						$rs_buscar = $obBD_con1->getArrayConsulta(1,$Ses_Emp_Cod."*".$txt_busqueda, $obBD_conexion);		
					}		
						$total_rs_buscar = count($rs_buscar);
				 
			 }else{
				  
				  if (isset($codigo1)){
					$rs_consultar = $obBD_con1->getArrayConsulta(3,$codigo1.'*'.$Ses_Emp_Cod, $obBD_conexion);
					$total_rs_consultar = count($rs_consultar);
				  }
			 }
			 	  
	     }
/**
 * Controla el reenvio de la página
 */
if ($thisPost->postBlock($_POST['postID'])) { 
//Proceso de guardar auditoria de tenencia de activos.
	if(isset($hdd_save)){		
		$cant_act = explode('*',$strAct);
		/**
		 * Inicio de transaccion..
		 */
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			
		/**
		 * Guarda la cabecera de la auditoria.
		 */
		$obBD_con1->operacionobBD(6,$Cus_Cod.'*'.$Aud_Cod.'*'.$hoy,$obBD_conexion);		
		$id_Aud=$obBD_con1->insercionid($obBD_conexion->conexion);
		/**
		 * For para save el detalle de la auditoria de Tenencia de activos.
		 */	
		for ($j=1; $j<= count($cant_act)-1; $j++){
			$obBD_con1->operacionobBD(7,$id_Aud.'*'.$cant_act[$j].'*'.$Est_Cod[$j].'*'.trim($Con_Obs[$j]).'*'.$Est_Act[$j],$obBD_conexion);					
		}		
		/**
		 * Fin de la Transacción
		 */
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}
}		
	
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
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
<?Php 
/**
 * Si auditor existe y es correcto
 */
if(count($rs_auditore)>0){
	//almaceno  variable  de codigo de auditoria
?>
	<table width="100%" border="0">
		<tr class="BarraTitulo">
        	<td align="left">&raquo; Control de Tenencia de Activos Fijos</td>
      	</tr>
      	<tr>
    		<td>
  		<fieldset>
      	<LEGEND>
        	<label class="Titulos2">Buscar Custodio por:&nbsp;</label>
      	</LEGEND>
   			<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
           	<table width="504" height="64" border="0" cellpadding="0" cellspacing="0">
            <tr>
            	<td colspan="2">
                	<table width="342" border="0">
                	<tr>
                    	<td width="153"><input name="op_opciones" type="radio" value="1" onClick="setfocus(this.form.txt_busqueda)" checked>
                            <span class="LetraNegra">Apellidos</span></td>
                        <td width="179"><input type="radio" name="op_opciones" value="2" <?Php if($op_opciones==2){ echo "checked";}?> onClick="setfocus(this.form.txt_busqueda)">
                            <span class="LetraNegra">Cédula</span></td>
                  	</tr>
                  	</table>
                </td>
            </tr>
            <tr>
              	<td width="84" height="36"class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Busqueda: </div></td>
              	<td width="274" class="BarraBusqueda">&nbsp;<input name="txt_busqueda" type="text" id="txt_busqueda" size="40">
              	</td>
              	<td  width="128" class="BarraBusqueda"><div align="left">
                <button name="btn_aceptar" class="btn btn-success fileinput-button" id="btn_aceptar" title="Mostrar Custodios de Activos Fijos" onClick="validar_requeridos(this.form,'$txt_busqueda',0)">
                <i class="icon-search icon-white"></i>
                <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>   
                </button>
              </div></td>
            </tr>
          	</table>
 	 		</form>
  		</FIELDSET>
<?
	if (isset($txt_busqueda)){?>
    	<FIELDSET>
        <LEGEND>
	    	<label class="Titulos2">Resultados de la busqueda</label>
        </LEGEND>
        <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
      	<thead>
              <th width="15%">Cód. Int.</th>
              <th width="14%">C&eacute;dula</th>
              <th width="67%">Custodio</th>
              <th>&nbsp;</th>         
        </thead>
        <tbody>
          <?Php 
          if ($total_rs_buscar > 0){ 
			 	
            foreach($rs_buscar as $row_rs_buscar){ 
               if($row_rs_buscar['Cus_Est']=='I')
               { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
          ?>
        <tr>
            <td align="center"><FONT COLOR="<? echo $rojo;?>"><?php echo $row_rs_buscar['Cus_Cod'];?></FONT></td>
            <td><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Prs_Ced'];?></FONT></td>
            <td><p><FONT COLOR="<? echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Nombre'],'#FFFF00', 1);?>    
            </FONT></p>
            </td>
            <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post">
            <td align="center" width="4%">
<?Php
				if($row_rs_buscar['Cus_Est']=='A'){?>
                    <input name="codigo1" type="hidden" id="codigo1" value="<?php echo $row_rs_buscar['Cus_Cod'];?>"> 
                    <input name="custodio" type="hidden" id="custodio" value="<?php echo $row_rs_buscar['Nombre'];?>"> 
                    <input name="ci" type="hidden" id="ci" value="<?php echo $row_rs_buscar['Prs_Ced'];?>"> 
                    <input name="op_opciones" type="hidden" id="op_opciones" value="<?php echo $op_opciones;?>"> 
                    <input name="txt_busqueda1" type="hidden" id="txt_busqueda" value="<?php echo $txt_busqueda;?>"> 
                  <button type="button" name="imageField" width="22" height="22" title="Seleccionar Custodio"  class='btn btn-success btn-mini' onClick="submit();">	
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
          <?Php } //fin foreach($rs_buscar as $row_rs_buscar){       
          }
		  else{
          ?>
        <tr>
            <td></td>
            <td>&nbsp;</td>
            <td align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
            <td> </td>
        </tr>
<?
			} // fin del if ($total_rs_buscar > 0)
?>
      	</tbody>
        </table> 
         
<? 
			echo barra_estado($total_rs_buscar);
		} // fin if (isset($txt_busqueda)){
?>
		</FIELDSET>  
		</td>
	</tr>
	</table>
<?		
	if (isset($codigo1)){
	/**
	 * Consulto los activos del custodio
	 */	
?>
<p>
	<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Datos del Control</label>
	</LEGEND>
	<form name="form4" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
    
    <? 
  /** 
   * Creacion del campo REPOST
   */
	$thisPost->startPost();
	?>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" >      
		<tr>
			<td width="14%" class="Etiqueta1">Institución:</td>
			<td width="27%" >&nbsp;<span class="Etiqueta1"><? echo $rs_instituc['Emp_Nom'];?></span></td>
			<td width="10%" class="Etiqueta1"  > </td>  
			<td width="49%" class="Etiqueta1"  > </td>  
		</tr>
		<tr>
			<td width="14%" class="Etiqueta1">Fecha de Control:</td>
			<td width="27%" align="left" >&nbsp;<span class="Etiqueta1"><?Php echo $hoy;?></span></td>
			<td width="10%" > </td>    	  
			<td width="49%" > </td>  
		</tr> 
		<tr>
			<td width="14%" class="Etiqueta1">Custodio:</td>
			<td width="27%" align="left" >&nbsp;<span class="Etiqueta1"><?Php echo $custodio;?></span></td>  
			<td width="10%" align="left" class="Etiqueta1">C&eacute;dula:</td>   	
			<td width="49%" align="left">&nbsp;<span class="Etiqueta1"><?Php echo $ci;?></span></td>    
		</tr> 
	</table>
    
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">      
	<thead>
		<th width="4%" align="center">Ord.</th>
		<th width="13%" align="center"> Cod. Int.</th>
		<th width="24%" align="center">Descripción del Activo</th>     	  
		<th width="13%" align="center"> Estado</th> 
		<th width="8%" align="center"> Auditor Estado</th>                  
		<th width="32%" align="center" lign="center" >Observaciones</th>            
	<tbody>
<?Php 
		/**
		* Recorrido del bucle de activos por codigo del custodio
		*/
		if ($total_rs_consultar>0){
		  $i=0;
			foreach($rs_consultar as $row_rs_consultar){ 
			  $i++;			  
			  $str1.= "*".$row_rs_consultar['Act_Cod'];	
?>
	<tr>
		<td><?Php echo $i;?> </td>
		<td align="center"><?Php echo $row_rs_consultar['Act_Cod'];?>
			<input id="Act_Cod[<?Php echo $i;?>]" name="Act_Cod[<?Php echo $i;?>]" value="<?Php echo $row_rs_consultar['Act_Cod'];?>" type="hidden">
		</td>
		<td><?Php echo $row_rs_consultar['Act_Des'];?></td>
		<td align="center"><?Php echo $row_rs_consultar['Est_Des'];?>
        	<input name="Est_Act[<?Php echo $i;?>]" id="Est_Act[<?Php echo $i;?>]" type="hidden" value="<?Php echo $row_rs_consultar['Est_Cod'];?>">
        </td>
		<td>      
		<select name="Est_Cod[<?Php echo $i;?>]" id="Est_Cod[<?Php echo $i;?>]">
			<option value="">Selecc..</option>
<?php
			foreach($rs_estado as $row_rs_estado){  
?>
				<option <?Php if($row_rs_consultar['Est_Des']==$row_rs_estado['Est_Des']){ echo 'selected';}?>  value="<?php echo $row_rs_estado['Est_Cod']?>"><?Php echo $row_rs_estado['Est_Des']?> </option>
<?php
			}
?>     
		</select>         
		</td>     
		<td> <textarea name="Con_Obs[<?Php echo $i;?>]"id="Con_Obs[<?Php echo $i;?>]" cols="60" rows="2"> </textarea></td>
	</tr>  
<?Php 
			}// fin  de foreach($rs_buscar as $row_rs_buscar){ 
		}// fin de if ($total_rs_buscar>0){
	  else{
?>
	<tr>
		<td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1) ?> </td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
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
		<input name="Cus_Cod" type="hidden" id="Cus_Cod" value="<?Php echo $codigo1;?>">
		<input name="strAct" type="hidden" id="strAct" value="<?Php echo $str1;?>">
		<input name="hdd_save" type="hidden" id="hdd_save">
		<input name="Aud_Cod" type="hidden" id="Aud_Cod" value="<?Php echo $rs_auditore['Aud_Cod'];?>">    
	</form>  
 </FIELDSET>
  <p>
  <?Php
 if ($total_rs_consultar>0){
?>
  		<table  width="226">
     		<tr>
                <td width="44%" align="left" >
                	 <form name="form6" method="post" action="<? echo $_SERVER['PHP_SELF'];?>">
                    	<button type="button" name="btn_atras" id="btn_atras" value="Enviar" class="btn btn-inverse fileinput-button" title="Atr&aacute;s"
                        onClick="campos_hide(this.form, '<?Php echo "op_opciones*txt_busqueda*hdd_volver"; ?>','<?Php echo $op_opciones.'*'.$txt_busqueda1.'*'.$hdd_volver;?>')">
                         <i class="icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
                        </button>
                        <input id="op_opciones" name="op_opciones" type="hidden" value="<?php echo $op_opciones;?>">
                        <input id="txt_busqueda" name="txt_busqueda" type="hidden" value="<?php echo $txt_busqueda1;?>">
                        <input id="hdd_volver" name="hdd_volver" type="hidden" value="0">
                    </form>
                </td>            
                <td width="56%" >              
                <form name="form3" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
                    <button name="boton_guardar" id="boton_guardar" type="button"  class="btn btn-primary fileinput-button" title="Guardar" value="Guardar" onClick="validar_requeridos(form4,'Cus_Cod*strAct*Aud_Cod',1)"> 
                    <i class="icon-book icon-white"></i>
                    <span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span>
                    </button>               
                </form>             
                </td>
                        
         </tr> 
     </table>     
   <?Php
  } // Fin de if ($total_rs_consultar>0){
	?>     

 
 
 
  
 <?Php 

}// Fin  de if(count($rs_auditore)>0)
else{
?>
<table width="100%" border="0" >
		<tr class="BarraTitulo">
			<td align="left">&raquo;Control de Tenencia de Activos Fijos</td>
		</tr>
		<tr>
			<td align="center"><input  name="img" id="img" type="image" src="../../mascaras/model1/imagenes/32x32/advertencia.PNG"><span class="LetraNegra">
		Ud. no está autorizado.</span></td>
		</tr>
	</table>   
<?Php
	}
?>
</div>
	<script type="text/javascript" src="../VALIDACIONES/act_par_activo_aud.js"></script>
	<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</div>   
</BODY></HTML>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>