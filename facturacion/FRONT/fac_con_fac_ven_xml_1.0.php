<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
* Descripci�n: Consulta de facturas electronicas
* Fecha de actualizaci�n:	16-11-2014 
* Desarrollador:	Jose Cumbicos
*/	  

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven_xml.php');  	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;	 	 	 
/**
* Llamado de la libreria para evitar el reenvio de datos 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");

if(!isset($op))
{
	$op =1;	
}


if ($txt_busqueda != ""  )
{   if ($op_opciones == "d")
	{
		$rs_buscar = $obBD_con1->getArrayConsulta(21,$txt_busqueda."*".$Ses_Emp_Cod,$obBD_conexion);						
	}
	else 
    {
	    $rs_buscar = $obBD_con1->getArrayConsulta(22,$txt_busqueda."*".$Ses_Emp_Cod,$obBD_conexion);				
    }	 
   $total_rs_buscar = count($rs_buscar);
}

if(isset($ajx_det)){
		/**
		* Resultado de la tabla detalle de ajustes
		*/
		$rs_detalle = $obBD_con1->getArrayConsulta(1057, $ajx_det, $obBD_conexion);
		$Total=0;
		
		?>
		<fieldset>
			<LEGEND>
				<label class="Titulos2">Detalle : <?php echo $aju_det; ?></label>
			</LEGEND>
		 <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
			<thead>    
				  <th width="10%" align="center"><strong>Cant.</strong></th>
				  <th width="30%"><strong>Descripci&oacute;n</strong></th>
				  <th width="33%" align="right"><strong>P. Unitario</strong></th>
				  <th width="27%" align="right"><strong>Importe</strong></th>
				</tr>
			   <tbody>
				<?php foreach($rs_detalle as $row_detalle){?>
				<tr >     
				  <td align="center" ><?php echo $row_detalle['Aju_Can']; ?></td>
				  <td>&nbsp;<?php echo $row_detalle['Ite_Lar']; ?></td>
				  <td align="right"><?php echo formato_numero($row_detalle['Aju_Pru'],2,1); ?>&nbsp;</td>
				  <td align="right"><?php echo formato_numero($row_detalle['Aju_Imp'],2,1); $Total=$Total+$row_detalle['Rcb_Imp'];?>&nbsp;</td>
				</tr>
			<?php } ?>
				</tbody>
			</table>	
		</fieldset>
	<?php
			exit();	
	}
	
switch($op){
	case 1:
	
	break; 
	case 2:

	break;

}
		
?>
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>		
		<script type="text/javascript" src="../VALIDACIONES/fac_val_aju.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>         
        <script>
    $(function() { 
        /* Campo 1 */					
        $( "#ini" ).datepicker( "option", "showAnim", "show" ); $.datepicker.setDefaults( $.datepicker.regional[ "es" ] ); 
        $( "#ini" ).datepicker({ altField: "#ini", altFormat: "yy-mm-dd" });	
        /* Campo 2 */			
        $( "#fin" ).datepicker( "option", "showAnim", "show" ); $.datepicker.setDefaults( $.datepicker.regional[ "es" ] ); 
        $( "#fin" ).datepicker({ altField: "#fin", altFormat: "yy-mm-dd" });						
    }); 		
        </script>    
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>		
		
		<script type="text/javascript">
			function downloadURI(directorio,aux,obj) 
			{   
				//var frm = document.getElementById("form2");
				var lista = obj.ficheros;						
				if (aux>1) 	//verificamos si existe mas de dos check para hacer el recorrido como vertor		
				{ 
					for (var i=0; i<lista.length; i++) 				
					{		
						if (lista[i].checked==true) {									
							var link = document.createElement("a");													
							link.download = lista[i].value;						
							link.href = directorio +"/"+ lista[i].value;						
							link.click();
							lista[i].checked=false;												
						}
					}		
				}else{ // accedemos al check de forma normal NO como arreglo
					if (aux!=0) 				
					{	
						if (lista.checked==true) {																
							var link = document.createElement("a");						
							link.download = lista.value;						
							link.href = directorio +"/"+ lista.value;						
							link.click();
							lista.checked=false;												
						}
					}
				}
			}
		</script>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
	  <td height="10">&raquo; Consultar Xml de Ventas</td>
</tr>
<tr>
 <td align="left" valign="top" height="400">
  <?php

	    $descripcion = "Todos*Individual";
  		$pag1= $_SERVER['PHP_SELF']."?op=1";
		$pag2= $_SERVER['PHP_SELF']."?op=2";
		tabs(1,$descripcion, $pag1.'*'.$pag2, $op);
	?>

<?php
if(!isset($op)){$op = 1;}

if ( $op==1 || $op==2 ) 
{
  switch($op) {
	case 1:
		$rs_consulta = $obBD_con1->getArrayConsulta(2,$Ses_Suc_Cod,$obBD_conexion);	
		$rs_consultaGuiaRemi = $obBD_con1->getArrayConsulta(23,$Ses_Suc_Cod,$obBD_conexion);
		$rs_consultaRetencion = $obBD_con1->getArrayConsulta(24,$Ses_Suc_Cod,$obBD_conexion);
		
		$total_consultaRetencion = count($rs_consultaRetencion);	
		$total_rs_consultaGuiaRemi = count($rs_consultaGuiaRemi);			
		$total_rs_consulta = count($rs_consulta);
	?>	
   	<form method="post" name="form3" id="form3" action="<?php echo $_SERVER['PHP_SELF'];?> ">
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Facturas pendientes de enviar</label>
    </LEGEND>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01" style="table-layout:fixed;">
          <thead>
          <tr>
            <th width="6%">C&oacute;d. Int. </th>
            <th width="8%">Emisi&oacute;n</th>
            <th width="9%">C&eacute;dula/R.U.C</th>
            <th width="17%">Cliente</th>
            <th width="11%">No. Documento</th>
            <th width="11%">Tipo</th>
            <th width="30%">Clave de Acceso</th>  
            <th width="5%">Importe</th>
            <th width="3%"><input type="checkbox" value="" onclick="checkear_xml(this,<?php echo $total_rs_consulta ?>,this.form)" name="todos"/></th>
            </tr>
          </thead>
          <tbody>        
          <?php
			  $a=0;
			  foreach($rs_consulta as $datos)
			  {    //echo "<br>".strlen($datos['Prs_Ape']." ".$datos['Prs_Nom']);
				  $rs_tipCompro = $obBD_con1->getRowConsulta(7,$datos['Tic_Cod'],$obBD_conexion);				 
			  ?>
              
              <tr>
                <td align="center"><?php echo $datos['Vet_Cod']?></td>
                <td align="center"><?php echo $datos['Caj_Fec']?></td>
                <td align="center"><?php echo $datos['Prs_Ced']?></td>
                <td align="left" style="white-space: nowrap; overflow: hidden;"><?php echo $datos['Prs_Ape']." ".$datos['Prs_Nom'];?></td>
                <td align="center"><?php echo $datos['Suc_Sri'].'-'.$datos['Pun_Sri'].'-'.str_pad($datos['Vet_Num'], 9, "0", STR_PAD_LEFT)?></td>
                <td align="center"><?php echo $rs_tipCompro['Tic_Des'];?></td>
                <td align="center"><?php echo $datos['Vet_Xml'];?></td>
                <td align="center">
                <?php 
					if ($datos['Vet_Des']==0)
					{
						$impIva= ($datos['Imp_Iva']*12)/100;
						echo formato_numero($datos['total']+$impIva,2,1);
                    }else{
						$descuento= ($datos['total']*$datos['Vet_Des'])/100;
						$impIva= ($datos['Imp_Iva']*12)/100;
						echo formato_numero(($datos['total']-$descuento)+$impIva,2,1);						
					}
                ?>
                </td>
                <td align="center">
                <input type="checkbox" value="<?php echo $datos['Vet_Xml'].'.xml'?>" name="ficheros" id="ficheros"/></td>
              </tr>
              
          <?php  }
		  if($total_rs_consultaGuiaRemi!=0)
		  {
		  	foreach($rs_consultaGuiaRemi as $datosRemi)
			{   $a++;			  				
			?>
				<tr>
                <td align="center"><?php echo $datosRemi['Gui_Cod']?></td>
                <td align="center"><?php echo $datosRemi['Gui_Fec']?></td>
                <td align="center"><?php echo $datosRemi['Prs_Ced']?></td>
                <td align="left" style="white-space: nowrap; overflow: hidden;"><?php echo $datosRemi['Prs_Ape']." ".$datosRemi['Prs_Nom']?></td>
                <td align="center"><?php echo $datosRemi['Suc_Sri'].'-'.$datosRemi['Pun_Sri'].'-'.str_pad($datosRemi['Gui_Num'], 9, "0", STR_PAD_LEFT)?></td>
                <td align="center">GU&Iacute;A REMISI&Oacute;N</td>
                <td align="center"><?php echo $datosRemi['Gui_Xml'];?></td>
                <td align="center">-</td>
                <td align="center">
                <input type="checkbox" value="<?php echo $datosRemi['Gui_Xml'].'.xml'?>" name="ficheros" id="ficheros"/></td>
              </tr>
		  <?php }
		  }
		   if($total_consultaRetencion!=0)
		  {
		  	foreach($rs_consultaRetencion as $datosReten)
			{   $a++;
			    $totRet=0;
				$rs_detRetencion = $obBD_con1->getArrayConsulta(25,$datosReten['Ret_Cod'],$obBD_conexion);
				foreach($rs_detRetencion as $datosDetReten)
				{
					$totRet=$totRet+$datosDetReten['ret'];
				}
				unset($datosDetReten);
		?>
				<tr>
                <td align="center"><?php echo $datosReten['Ret_Cod']?></td>
                <td align="center"><?php echo $datosReten['Ret_Fec']?></td>
                <td align="center"><?php echo $datosReten['Prs_Ced']?></td>
                <td align="left" style="white-space: nowrap; overflow: hidden;"><?php echo $datosReten['Prs_Ape']." ".$datosReten['Prs_Nom']?></td>
                <td align="center"><?php echo $datosReten['Suc_Sri'].'-'.$datosReten['Pun_Sri'].'-'.str_pad($datosReten['Ret_Num'], 9, "0", STR_PAD_LEFT)?></td>
                <td align="center">RETENCI&Oacute;N</td>
                <td align="center"><?php echo $datosReten['Ret_Xml'];?></td>
                <td align="center"><?php echo formato_numero($totRet,2,1);?></td>
                <td align="center">
                <input type="checkbox" value="<?php echo $datosReten['Ret_Xml'].'.xml'?>" name="ficheros" id="ficheros"/></td>
              </tr>
		  <?php						
			}
		  }
		  
		  if($total_rs_consulta==0 && $total_rs_consultaGuiaRemi==0 && $total_consultaRetencion==0){?>
          <tr>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
          </tr>
          <?php }?>
		 </tbody>
        </table>
        <?php echo barra_estado($total_rs_consulta+$total_rs_consultaGuiaRemi+$total_consultaRetencion);?>
    </FIELDSET>
    <br />
    <table width="299" border="0" cellpadding="0" cellspacing="0" class="Azul">
     
     <td width="149">     
        <button type="button" class="btn btn-primary start" title="Bajar archivos" onclick="downloadURI('<?php echo $Ses_Emp_Cod;?>','<?php echo $total_rs_consulta+$total_rs_consultaGuiaRemi+$total_consultaRetencion;?>',this.form)"> <i class=" icon-download-alt icon-white"></i> <span>&nbsp;&nbsp;Bajar&nbsp;&nbsp;</span></button> 
     </td>
     </tr>
     </table>
	</form>
	
<?php  break;
	case 2: 
?>      
        <form action="<?Php $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
        <?Php include("../../componentes/FRONT/com_con_persona.php"); ?>
        <input name="op" id="op" type="hidden" value="<?php echo $op;?>"> 
        </form>
        <?php if(isset($hdd_buscar)){ ?>
        
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Resultados de la b�squeda</label>
        </LEGEND>
            <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
              <thead>
              <tr>
                <th width="8%">C&oacute;d. Int. </th>
                <th width="10%">C&eacute;dula</th>
                <th width="38%">Apellidos</th>
                <th width="41%">Nombres</th>
                <th width="2%">&nbsp;</th>
              </tr>
              </thead>
              <tbody>
              <?Php 
             if($total_rs_buscar > 0)
             {	  
              foreach($rs_buscar as $row_rs_buscar) { //Abrir el } while ($row_rs_buscar = mysqli_fetch_assoc($rs_buscar)                            
              ?>	
              <tr>
                <td align="center"><?Php echo $row_rs_buscar['Cli_Cod']; ?></td>
                <td align="center"><?Php echo $row_rs_buscar['Prs_Ced']; ?></td>
                <td align="center"><?Php echo $row_rs_buscar['Prs_Ape']; ?></td>
                <td align="center"><?Php echo $row_rs_buscar['Prs_Nom']; ?></td>
                <td align="center"><?Php echo $row_rs_buscar['Prs_Est'] ?>
                <form name="form6" method="post"  action="<?php echo $_SERVER['PHP_SELF'];?> ">   
                    <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Cli_Cod'];?>">
                    <input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
                    <input name="op_opciones" id="op_opciones" type="hidden" value="<?Php echo $op_opciones;?>">
                    <input name="op" id="op" type="hidden" value="<?php echo $op;?>">                
                    <input name="hdd_distri" id="hdd_distri" type="hidden" value="1">
                    <input name="hdd_ced" id="hdd_ced" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Ced']; ?>">
                    <input name="hdd_ape" id="hdd_ape" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Ape']; ?>">
                    <input name="hdd_nom" id="hdd_nom" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Nom']; ?>">
                    <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
                    <i class=" icon-arrow-right icon-white"></i>
                    </button>
                </form> 
                </td>
              </tr><?Php   
                } 
            }//Fin del if($total_rs_buscar != 0)
            else
            { ?>
              <tr>
                <td align="center">&nbsp;</td>
                <td align="center">&nbsp;</td>
                <td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
                <td align="center">&nbsp;</td>
                <td align="center">&nbsp;</td>
              </tr>
         <?php }//Fin del else if($total_rs_buscar != 0) ?>
              </tbody>
            </table>
            <?php echo barra_estado($total_rs_buscar);?>
        </FIELDSET>
        <?php  } /*fin del if(isset($hdd_buscar) )*/?> 
        
     <?php if (isset($codigo)) 
        { 
            $rs_consulta = $obBD_con1->getArrayConsulta(1,$codigo,$obBD_conexion);				
            $total_rs_consulta = count($rs_consulta);
        ?>    
         <form method="post" name="form2" id="form2" action="<?php echo $_SERVER['PHP_SELF'];?> ">
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Datos seleccionados </label>
            </LEGEND>
           
            <table width="509" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td class="Etiqueta1" width="53" align="right">C&eacute;dula:&nbsp;</td>
                <td class="LetraNegra" width="456">&nbsp;<?php echo $hdd_ced;?></td>
            </tr>              
            <tr>     
                <td class="Etiqueta1" align="right">Nombre:&nbsp;</td>
                <td class="LetraNegra">&nbsp;<?php echo $hdd_ape." ".$hdd_nom;?></td>
            </tr>           
            </table>
          <br />
            <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
                  <thead>
                  <tr>
                    <th width="7%">C&oacute;d. Int. </th>
                    <th width="18%">Fecha / Hora</th>
                    <th width="18%">N&uacute;m. Documento</th>
                    <th width="50%">XML</th>  
                    <th width="10%">Importe</th>
                    <th width="2%"><input type="checkbox" value="" onclick="checkear_xml(this,<?php echo $total_rs_consulta ?>,this.form)" name="todos"/></th>
                    </tr>
                  </thead>
                  <tbody>        
                  <?php if ($total_rs_consulta!=0) {
                      $a=0;
                      foreach($rs_consulta as $datos)
                      {
                          $a++;                        
                      ?>
                      
                      <tr>
                        <td align="center"><?php echo $datos['Vet_Cod']?></td>
                        <td align="center"><?php echo $datos['Vet_Sys']?></td>
                        <td align="center"><?php echo $datos['Suc_Sri'].'-'.$datos['Pun_Sri'].'-'.str_pad($datos['Vet_Num'], 9, "0", STR_PAD_LEFT)?></td>
                        <td align="center"><?php echo $datos['Vet_Xml'].'.xml'?></td>
                        <td align="center">
                        <?php 
                            if ($datos['Vet_Des']==0)
                            {
                                $impIva= ($datos['Imp_Iva']*12)/100;
                                echo formato_numero($datos['total']+$impIva,2,1);
                            }else{
                                $descuento= ($datos['total']*$datos['Vet_Des'])/100;
                                $impIva= ($datos['Imp_Iva']*12)/100;
                                echo formato_numero(($datos['total']-$descuento)+$impIva,2,1);						
                            }
                        ?>
                        </td>
                        <td align="center">
                         <input type="checkbox" value="<?php echo $datos['Vet_Xml'].'.xml'?>" name="ficheros" id="ficheros"/></td>
                      </tr>
                      
                  <?php  }
                  }else{?>
                  <tr>
                    <td align="center">&nbsp;</td>
                    <td align="center">&nbsp;</td>
                    <td align="center">&nbsp;</td>
                    <td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
                    <td align="center">&nbsp;</td>
                    <td align="center">&nbsp;</td>
                  </tr>
                  <?php }?>
                 </tbody>
                </table>
                 <?php echo barra_estado($total_rs_consulta);?>
            </FIELDSET>
       
        <br />
        <table width="299" border="0" cellpadding="0" cellspacing="0" class="Azul">
         <tr>
           <td width="100">             
           <button type="button" class="btn btn-inverse fileinput-button" title="Atr�s" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*op*hdd_buscar"; ?>', '<?Php echo $volver_busqueda.'*'.$op_opciones.'*'.$op.'*1'; ?>')">
                            <i class=" icon-arrow-left icon-white"></i>
                            <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
               </button>        
           </td>
         <td width="149">     
            <button type="button" class="btn btn-primary start" title="Bajar archivos" onclick="downloadURI('<?php echo $Ses_Emp_Cod;?>','<?php echo $total_rs_consulta;?>',this.form)"> <i class=" icon-download-alt icon-white"></i> <span>&nbsp;&nbsp;Bajar&nbsp;&nbsp;</span></button> 
         </td>
         </tr>
         </table>
         </form>
         <br />
          
     <?php }
 	break; 	
  }
} 
?>
</td>
</tr>
</table>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
        <div id="ajax_modal">
        </div>
    </div>
</div>
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_aju.js?"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>   
</BODY>
</HTML>
<?php
/**
* Cierra las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();	
?>