<?php 
/* 
 * Alias: Consulta de Custodio
 *Descripción: Permite la consulta de custodio 
 *Desarrollador: Fabian Gallardo
 				 Didimo Zamora
 *Fecha de actualización:	2013/06/11
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_custodio.php');	  
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
	 }else{
 		if (isset($codigo))
		{
			$rs_consultar = $obBD_con1->getArrayConsulta(135,$codigo.'*'.$Ses_Emp_Cod, $obBD_conexion);
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
		<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Custodia de Activos Fijos</td>
  </tr>
	<tr>
	  	<td valign="top">		
    <fieldset>
  <LEGEND>
    <label class="Titulos2">Buscar por:&nbsp;</label>
   </LEGEND><form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']."?op=1"?>">
           <table width="620" height="36" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td colspan="2">
                <table width="481" border="0">
                      <tr>
                        <td width="205"><input name="op_opciones" type="radio" value="1" onClick="setfocus(this.form.txt_busqueda)" checked>
                            <span class="LetraNegra">Apellidos</span></td>
                        <td width="266"><input type="radio" name="op_opciones" value="2" onClick="setfocus(this.form.txt_busqueda)">
                            <span class="LetraNegra">Cédula</span></td>
                  </tr>
                  </table>
                </td>
            </tr>
            <tr>
              <td width="125" height="28"class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Busqueda: </div></td>
              <td width="354" class="BarraBusqueda">&nbsp;<input name="txt_busqueda" type="text" id="txt_busqueda" size="40">
              </td>
              <td width="141"><div align="center">
                <button name="btn_aceptar" type="submit" class="btn btn-success fileinput-button" id="btn_aceptar" value="Aceptar">
                <i class="icon-ok icon-white"></i>
                <span>&nbsp;&nbsp;Aceptar&nbsp;&nbsp;</span>   
                </button>
              </div></td>
            </tr>
          </table>
  </form>
<?php if (isset($txt_busqueda))
{
?>
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
    </LEGEND>
        <table width="99%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
          <thead>
            <th width="5%">Cód. Int.</th>
              <th width="18%">C&eacute;dula</th>
              <th width="68%">Custodio</th>
              <th>&nbsp;</th>
          
          </thead>
          <tbody>
          <?Php 
          if ($total_rs_buscar > 0){  		
            foreach($rs_buscar as $row_rs_buscar){ 
               if($row_rs_buscar['Cus_Est']=='I')
               { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
          ?>
          <tr >
          <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row_rs_buscar['Cus_Cod'];?></FONT></td>
          <td><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Prs_Ced'];?></FONT></td>
         <td><p><FONT COLOR="<?php echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Nombre'],'#FFFF00', 1);?>    
         </FONT></p>
         </td>
          <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "frml" id="forml">
          <td align="center" width="12%">
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
             <td><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
              <td></td>
            </tr>
          <?php } // fin del if ($total_rs_buscar > 0)?>
          </tbody>
        </table>
    <?Php
	/**
	 *  Muestra la barra de estados con la cantidad de registros encontrados 
	 */
		echo barra_estado($total_rs_buscar+0);
	?>
	</FIELDSET>

<?php } 
if(isset($codigo)){	
			/**
			 * Consulta de los datos del custodio
			 */ 
			$rs_custodio = $obBD_con1->getRowConsulta(136,$codigo.'*'.$Ses_Emp_Cod, $obBD_conexion);
?>
<table width="80%" border="0" align="left" cellpadding="0" cellspacing="0">
      <tr>
        <td width="14%" ><span class="Etiqueta1">Institución :</span></td>
        <td width="26%"><span class="LetraNegra"><?Php echo $rs_institucion['Emp_Nom'].' - '.$rs_institucion['Suc_Des']; ?></span></td>
        <td width="6%">&nbsp;</td>
        <td width="54%">&nbsp;</td>
      </tr>
      <tr>
        <td><span class="Etiqueta1">Fecha de Emisi&oacute;n :</span></td>
        <td><span class="LetraNegra"><?php echo $hoy ?></span></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td><span class="Etiqueta1">Nombre del Custodio :</span></td>
        <td><span class="LetraNegra"><?php echo  $rs_custodio['Nombre']; ?></span></td>
        <td><span class="Etiqueta1">Cédula :</span></td>
        <td><span class="LetraNegra"><?php echo $rs_custodio['Prs_Ced']; ?></span></td>
      </tr>
    </table>
    
<p><br/>
</p>
<p>&nbsp;</p>
   <table width="80%" border="0" align="center" cellpadding="0" cellspacing="0" class="fixedHeader02" >
      <thead>
            <th width="4%"> Ord.</th>
            <th width="5%">Cod. Art.</th>
            <th width="10%">Departamento</th>
            <th width="15%">Nombre del Art&iacute;culo</th>
           
            <?Php 
		  	 /**
			 * seleccionar toodos los campos de busqueda
			 */
			$td=0;
			$rs_camp = $obBD_con1->getArrayConsulta(140,'', $obBD_conexion);
			$total_rs_camp =  count($rs_camp); 
		  	if($total_rs_camp > 0){									
				foreach($rs_camp as $row_rs_camp){
				?>
					<th width="10%">
					<?php echo $row_rs_camp['Cam_Cor']; $td +=1; ?>
                    </th>
			   <?php }//if($total_rs_camp > 0){
			}?>
            
            <th width="6%">Cant.</th>
            <th width="9%">Estado</th>    
            <th width="5%">Costo</th>   
        </thead>
        <tbody>
         <?php if( $total_rs_consultar > 0){
			 	$Total=0;
                foreach($rs_consultar as $row_rs_consultar){
                $i++;
            ?>                     
        <tr>
            <td class="LetraNegra" align="left"><?php echo $i;?></td>
            <td class="LetraNegra" align="left"><?php echo $row_rs_consultar['Act_Cod'];?></td>
            <td class="LetraNegra" align="left"><?php echo $row_rs_consultar['Dep_Des'];?></td>
            <td class="LetraNegra"><?php echo $row_rs_consultar['Act_Des'];?></td>
             
           
              <?php 		
			$rs_camp = $obBD_con1->getArrayConsulta(140,'', $obBD_conexion);
			$total_rs_camp =  count($rs_camp);
			if ($total_rs_camp> 0){
				foreach($rs_camp as $row_rs_camp){
						$rs_val_Camp =  $obBD_con1->getRowConsulta(141, $row_rs_buscar['Act_Cod'].'*'. $row_rs_camp['Cam_Cod'],$obBD_conexion);
					?>
					<td align="center" width="16%">
						<?Php echo $rs_val_Camp['Act_Val'] ?>                
					</td>
					<?php
				}
		 	 }
		  ?>
            <td class="LetraNegra" align="center"><?php echo $row_rs_consultar['Act_Can'];?></td>
            <td class="LetraNegra" align="center"><?php echo $row_rs_consultar['Est_Des'];?></td>
            <td class="LetraNegra" align="right"><?php if($row_rs_consultar['Act_Val']==0){ echo "0.00";}else{
				echo formato_numero($row_rs_consultar['Act_Val'], 2, 1); $Total= $Total+ $row_rs_consultar['Act_Val'];}?>
             </td>
        </tr>
       <tbody>
        <?php 	  }//fin foreach($rs_consultar as $row_rs_consultar){
			?>
         <tfoot>   
             <tr>
         <td class="LetraNegra" align="left">&nbsp;</td>
            <td class="LetraNegra" align="left">&nbsp;</td>
            <td class="LetraNegra" align="left">&nbsp;</td>
            <td class="LetraNegra">&nbsp;</td>
        	 <?Php
        if ($total_rs_camp> 0){
				foreach($rs_camp as $row_rs_camp){
						$rs_val_Camp =  $obBD_con1->getRowConsulta(141, $row_rs_buscar['Act_Cod'].'*'. $row_rs_camp['Cam_Cod'],$obBD_conexion);
					?>
					<td align="center" width="16%">&nbsp;</td>
					<?php
				}
		 	 }
		  ?>
          	<td class="LetraNegra" align="center">&nbsp;</td>
            <td  style="font-size:14px"  class="LetraNegra" align="right"><strong>Total</strong></td>
            <td  style="font-size:14px"  class="LetraNegra" align="right"><?Php echo formato_numero($Total, 2, 4);?></td>
        </tr>
        </tfoot>
            <?php
            }//Fin if( $total_rs_consultar > 0){?>         
	</table>
     <?Php
	/**
	 *  Muestra la barra de estados con la cantidad de registros encontrados 
	 */
		echo barra_estado($total_rs_consultar+0);
	?>
    
	
<br> 
    <table width="215" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="110">
              <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1"> 
              <button type="button" name="btn_atras" id="btn_atras" value="Enviar" class="btn btn-inverse fileinput-button" title="Atr&aacute;s"
            onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver"; ?>','<?Php echo $volver_busqueda.'*'.$volver_opciones.'*'.'1'; ?>')">
             <i class="icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
            </button>
            </form>
            </td>          
              <td width="110" height="23">            	
                <form method="post" name= "form3" action="<?php echo 'act_pri_custodio_3.0.php';?>" target="_blank">
                 <button  name="boton_imprimir" id="boton_imprimir" type="submit" class="btn btn-primary start" value="Imprimir" >
                  <i class='icon-print icon-white'></i> <span>Imprimir</span>
                  </button>
                   <input name="codigo" type="hidden" id="codigo" value="<?Php echo $codigo; ?>">
                </form> </td>
            </tr>
        </table> 
            <?php
		 }// Fin if(isset($codigo)){	
	?>   
 </fieldset>
   	                 
</td>
</tr>
</table>
    
</BODY>
</HTML>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
 
?>