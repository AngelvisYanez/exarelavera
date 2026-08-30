<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php 

/**
* pagina de listado de precios (tes_con_producto_1.0.php) :)
*
* @author Jose Cumbicos
* Ultima Actualizaci�n: 28-05-2014
* @author Lewis Chimarro
* Ultima Actualizaci�n: 30-01-2015
*
* Permite buscar y visualizar los datos de un producto con sus respectivos precios
*
* @package tesoreria
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_precios.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * objeto conexion
 */
$obBD_conexion = new Class_Log_Conexion_pre($Ses_Dat_Dis);

/**
 * objeto para extraer datos
 */
$obBD_con1 =  new Class_Log_Datos_pre;
	
?>
<HTML>
	<HEAD>		
        <title><?Php echo $Ses_Sys_Nom; ?></title>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
         <script type="text/javascript">
			$(document).ready(function() {
				/* LLamado a la class del boton exportar */
				$("#Boton_Excel").click(function(event) {
					$("#datos_a_enviar").val( $("<div>").append( $("#Exportar_a_Excel").eq(0).clone()).html());
					$("#FormularioExportacion").submit();
				});
			});
		</script>
        <script type="text/javascript"> 
      		$(function() {
				$('#set1 *').tooltip({showURL: false});
	  		});              			
		</script>
		<style type="text/css">
        
		/* -------------------------------------------- */
		/* ------------- Pagination: Clean ------------ */
		/* -------------------------------------------- */
		
		#pagination-clean li          
		{ border:0; margin:0; padding:0; font-size:11px; list-style:none; /* savers */ float:left; }
		/* savers #pagination-clean li,*/
		#pagination-clean a           
		{ border-right:solid 1px #DEDEDE; margin-right:2px; }
		#pagination-clean .previous-off,
		#pagination-clean .next-off   
		{ color:#888888; display:block; float:left; font-weight:bold; padding:3px 4px; }
		#pagination-clean .next a,
		#pagination-clean previous a  
		{ border:none; font-weight:bold; }
		#pagination-clean .active    
		{ color:#333; font-weight:bold; display:block; float:left; padding:4px 6px; /* savers */ border-right:solid 1px #DEDEDE; }
		#pagination-clean a:link,
		#pagination-clean a:visited   
		{ color:#0e509e; display:block; float:left; padding:3px 6px; text-decoration:underline; }
		#pagination-clean a:hover     
		{ text-decoration:none; }
        </style>
		
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<?php

  $Letras = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","�","O","P","Q","R","S","T","U","V","W","X","Y","Z","TODOS");
  /**
   * Comprovar si dio clic en alguna pagina
   */
  if(isset($_GET['page']))
  {
    $page= $_GET['page'];
  }
  else
  {
	/*
	 * Por defecto
	 */
    $page = 'TODOS';
  }
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; listado de productos </td>
  </tr>
  
<?php if(!isset($Tpv_Cod) || isset($hdd_volver))
 {
?>
  <tr>
  <td height="400" valign="top">
   <fieldset>
	<LEGEND>
	 <label class="Titulos2">Seleccione:</label>
	</LEGEND>
    <?PHP mensaje_requerido(); ?>
    <form action="<?Php echo $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
    <div id="set1">
    <table width="431" cellpadding="0" cellspacing="0" border="0">
      <tr>
      <td width="29%" class="Etiqueta1"><span class="Asterisco">*</span> Tipo de Precio:</td>
        <td width="44%">&nbsp;
        <?php
        	$Arr_Tipo_Precio = $obBD_con1->getArrayConsulta(1, $Ses_Suc_Cod, $obBD_conexion);//EjecutarConsulta(1,$Ses_Suc_Cod);
		?>
        <select name="Tpv_Cod" id="Tpv_Cod">
        <option value="">Seleccionar...</option>
           <?php foreach($Arr_Tipo_Precio as $row){?>
           <option value="<?php echo $row['Tpv_Cod'];?>"><?php echo $row['Tpv_Des'];?></option><?php
			}
		   ?>
        </select>
        </td>
        <td width="27%">
            <div id="set1">
            <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onclick="validar_requeridos(this.form,'Tpv_Cod',0)">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
           </button> 
            </div>
        </td>
      </tr>
    </table>
    </div>
    </form>
   </fieldset>
  </td>
  </tr>
  
<?php }else{ ?>

	<tr>
        <td valign="top">
        <fieldset>
		  <LEGEND>
			<label class="Titulos2">Filtros de b�squeda</label>
		  </LEGEND>	
		   <ul id="pagination-clean">
          <?php
        	foreach($Letras as $letra)
			{
	  		    if($page == $letra)
				{
				  ?><li class="active"><?php echo $letra;?></li><?php
				}
				else
				{
				  ?><li><a href="fac_con_producto_1.0.php?page=<?php echo $letra;?>&Tpv_Cod=<?php echo $Tpv_Cod;?>" ><?php echo $letra;?></a></li><?php
			    }
			}
		  ?>
        </ul>
		  </fieldset>
        </td>
	</tr>
<tr><td>
 <?php
    	if($page == 'TODOS')
		{
			$Arr_Producto = $obBD_con1->getArrayConsulta(2, $Tpv_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);//EjecutarConsulta(2,$Tpv_Cod);
		}
		else
		{
			$Arr_Producto = $obBD_con1->getArrayConsulta(3, $Tpv_Cod.'*'.$page.'*'.$Ses_Suc_Cod, $obBD_conexion);//EjecutarConsulta(3,$Tpv_Cod.'*'.$page);
		}
		$total_rs_Producto =  count($Arr_Producto);
	?>
   
    <fieldset>
    <legend>
    <?php $Tpv_Des = $obBD_con1->getRowConsulta(4, $Tpv_Cod, $obBD_conexion);//GetRowConsulta(4,$Tpv_Cod);?>
     <label class="Titulos2">Listado de Productos - Precios <?php echo $Tpv_Des["Tpv_Des"];?></label>
    </legend>
    <div id="">
    <table class="fixedHeader01" width="100%" border="1" cellpadding="0" cellspacing="0" id="Exportar_a_Excel">
        <thead>
          <tr >
            <th width="6%">C&oacute;d. Int.</th>
            <th width="28%">Categoria</th>
            <th width="28%">Descripci&oacute;n Larga</th>
            <th width="13%">Descripci&oacute;n Corta </th>
            <th width="10%">Marca</th>
            <th width="9%">Stock</th>
            <th width="10%">Precio</th>
            <th width="10%">PVP</th>
          </tr>
          </thead>
          <tbody>
          <?php 
		  if ($total_rs_Producto<>0 )
		  {
		  $filas = 0;
		  foreach($Arr_Producto as $row)
		  {
		     $filas++;
		  ?>
          <tr>
            <td align="center" width="6%"><?Php echo $row['Pro_Cod'];?></td>
            <td><?Php echo $row['Cat_Des'];?></td>
            <td><?Php echo $row['Ite_Lar']." ".$row['Pro_Obs'];?></td>
            <td><?Php echo $row['Ite_Cor']." ".$row['Pro_Obs'];?></td>
            <td><?Php echo $row['Mar_Des'];?></td>
            <td align="right"><?Php echo $row['Stk_Can'];?></td>
            <td align="right"><?Php echo number_format($row['Pre_Pvp'],2);?></td>
            <td align="right"><?Php echo number_format($row['Pre_Pvp'] + ($row['Pre_Pvp'] * $row['Iva_Por'])/100,2);?></td>
          </tr>
          <?php }
		  }else{
		  ?>
          <tr>
                <td align="center" width="6%">&nbsp;</td>
                <td>&nbsp;</td>
                <td><?Php echo error_alerta(" �No hay resultados que mostrar!", 1);?></td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td align="right">&nbsp;</td>
                <td align="right">&nbsp;</td>
                <td align="right">&nbsp;</td>
          </tr>
          <?php }?>
          </tbody>
    </table>
    </div>
    <?php echo barra_estado($filas);?>
    </fieldset>
    <div id="set1">
         <table cellpadding="0" cellspacing="0" border="0">
         <tr>
         <td width="106">
         <form method="post" name="form1" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
         <button type="button" class="btn btn-inverse fileinput-button" title="Atr�s" onClick="campos_hide(this.form, 'hdd_volver', '<?php echo '1';?>')">
                            <i class=" icon-arrow-left icon-white"></i>
                            <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
               </button>
          </form>
         </td>
         <td width="106">
         <form action="fac_pri_producto_1.0.php" method="post" target="_blank">
             <input type="hidden" value="<?php echo $page;?>" name="page" id="page">
            <input type="hidden" value="<?php echo $Tpv_Cod;?>" name="Tpv_Cod" id="Tpv_Cod">
            <button type="button" class="btn btn-primary start" title="Imprimir Productos" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
        </form>
         </td>
         <td width="106">
         <form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">             
                      <button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" title="Exportar Excel" onClick="this.form.submit()">
                   <i class=" icon-share icon-white"></i>
                   <span>Excel</span>
            </button>
                    <input type="hidden" id="datos_a_enviar" name="datos_a_enviar" />
                    </form>
         </td>
         </tr>
         </table>
         <br>
   </div>
 </td>
 </tr>
 </table>
 
 <?php } ?>
</BODY>
</HTML>
<?Php	
/**
* Cierro las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>