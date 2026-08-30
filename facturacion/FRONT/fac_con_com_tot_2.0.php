<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* Descripci�n: Permite consultar el total de cmpras realizadas a proveedores
* Fecha de actualizaci�n:	2015-09-02
* Desarrollador:	Jose Cumbicos
*/	
require_once('../../administrador/LOGICA/seguridad.php');
require_once '../LOGICA/fac_log_com_tot.php';
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/** 
* Creaci�n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;
/** 
* Creacion del Objeto de conexion 
*/  
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/**
* Creaci�n del Objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Tes; 

	
/**
* Consultar los a?os para la consulta de las facturas de compras 
*/
$rs_anios = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion);

?>

<html>
<head>
	<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>        		  
		<link rel="stylesheet" type="text/css" href="../../Librerias/jquery/modal/css/modal.css">
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/jquery.js"></script>
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/modal.js"></script>        
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/ReportPrint.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>                
<style>		 
/* ventas */
.Texto_normal_9 {
	/*Arial, Helvetica, sans-serif;*/
	font-family: Verdana, Geneva, sans-serif;
	font-size: 9px;
	font-weight: normal;
	color: #000000;
	text-align: justify;
}
</style>
</head>
<body>
<div id="set1">
 <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
   <tr class="BarraTitulo">
  		<td height="10">&raquo; Total de Compras por Proveedor</td>
   </tr>
  <tr>
    <td height="450" valign="top">
    	<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Filtros</label>
		</LEGEND>
        <form name="form1" id="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
        	
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="12%" class="Etiqueta1">A&ntilde;o:</td>
            <td width="88%" class="LetraNegra">
            <select name="cmb_anio" id="cmb_anio">
			<?php foreach($rs_anios as $row_rs_anios){?>
              <option <?Php if ($cmb_anio == $row_rs_anios['Anio']){ echo "selected";} ?> value="<?php echo $row_rs_anios['Anio']; ?>"><?php echo $row_rs_anios['Anio']; ?></option>
            <?Php }?>
         	</select>
            <input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">
            <input name="ci" id="ci" type="hidden" value="<?Php echo $ci;?>">
            </td>
          </tr>
          <tr>
            <td class="Etiqueta1">C&eacute;dula/R.U.C.:</td>
            <td><input type="text" id="txt_ref" name="txt_ref" title="Buscar" onkeypress="if (event.keyCode < 45 || event.keyCode > 57) event.returnValue = false;"/>
              <button type="button" onclick="document.getElementById('ci').value=document.getElementById('txt_ref').value; if(document.getElementById('txt_ref').disabled==false){validar_requeridos(this.form, 'txt_ref', 2)}else{this.form.submit();}" class="btn btn-success btn-mini"  title="Busqueda Individual" ><i class="icon-search icon-white"></i></button>
              &nbsp;&nbsp;
              <button type="button" onclick="document.getElementById('ci').value=document.getElementById('ci').value  + '*' + document.getElementById('txt_ref').value; if(document.getElementById('txt_ref').disabled==false){validar_requeridos(this.form, 'txt_ref', 2)}else{this.form.submit();}" class="btn btn-success btn-mini"  title="Agregar Filtro" ><i class=" icon-plus-sign icon-white"></i></button></td>
          </tr>
          <tr>
            <td class="Etiqueta1">&nbsp;</td>
            <td><input type="checkbox" id="ckbTodos" name="ckbTodos" onclick="  if(document.getElementById('txt_ref').disabled==true){document.getElementById('txt_ref').disabled=false}else{document.getElementById('txt_ref').disabled=true}" />
              <font class="Etiqueta1">&nbsp;Todos</font></td>
          </tr>
        </table>

        </form>
        </FIELDSET>
		 <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
        
          <table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
          <tr class="Cabecera1">
            <td width="12%" rowspan="2">Cedula/R.U.C.</td>
            <td width="68%" rowspan="2" align="left">&nbsp;Proveedor</td>
            <td colspan="2">Resumen General</td>
            </tr>
          <tr class="Cabecera1">
            <td width="11%" align="right">Base Imponible</td>
            <td width="11%" align="right">Renta</td>
            </tr>
         	<?php  
			if($_POST['ckbTodos'])
			{	
				$rs_cedulas = $obBD_con1->getArrayConsulta(6, $Ses_Emp_Cod.'*2015-01-01*2015-12-31', $obBD_conexion);
				foreach ($rs_cedulas as $datos)
				{
					$param[]= $datos['Prs_Ced'];	
				}
			}else{				
				$param=explode('*',$ci);
			}
			
			for ($i=0;$i<=count($param);$i++)
			{ 	
			 	if($param[$i]!='')
				{
				/**
				* Consultar los datos de resumen de compras(Base imponible, Renta) 
				*/
				$rs_resumenBase = $obBD_con1->getRowConsulta(2, $Ses_Emp_Cod.'*'.$param[$i].'*'.$cmb_anio.'-01-01'.'*'.$cmb_anio.'-12-31', $obBD_conexion);
				$rs_resumenRenta = $obBD_con1->getRowConsulta(3, $Ses_Emp_Cod.'*'.$param[$i].'*'.$cmb_anio.'-01-01'.'*'.$cmb_anio.'-12-31', $obBD_conexion);
			?>
              <tr class="Texto_normal_9">
                <td align="center"><?php echo $rs_resumenBase['Prs_Ced'];?></td>
                <td >&nbsp;<?php echo $rs_resumenBase['Prs_Ape'].' '.$rs_resumenBase['Prs_Nom'];?></td>
                <td align="right"><?php echo formato_numero($rs_resumenBase['base'],2,2); $sumBas=$sumBas+$rs_resumenBase['base']; ?></td>
                <td align="right"><?php echo formato_numero($rs_resumenRenta['renta'],2,2); $sumRen=$sumRen+$rs_resumenRenta['renta'];?></td>
            </tr>
            <?php }}?>
          <tr class="Texto_normal_9">
            <td colspan="2" align="right" bgcolor="#CCCCCC"><strong>Total:</strong></td>
            <td align="right" bgcolor="#CCCCCC"><strong><?php echo formato_numero($sumBas,2,2)?></strong></td>
            <td align="right" bgcolor="#CCCCCC"><strong><?php echo formato_numero($sumRen,2,2)?></strong></td>
            </tr>
          </table>

               
       </FIELDSET>
       <table width="25%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="3%" height="48">&nbsp;</td>
            <td width="97%"><button type="button" class="btn btn-primary start" title="Descargar" onclick="downloadFile(exportarExcelBlob('excel','Reporte totales Renta'),'Reporte Renta-'+getDate()+'.xls')">
            <i class=" icon-share icon-white"></i>&nbsp;&nbsp;<span>Resumen a Excel</span>
    		</button></td>
          </tr>
        </table>

    </td>    
  </tr>
</table>
<?php if(isset($codigo)){?>
<div id="excel" style="display: none;">
<table width="1500" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td colspan="25"><strong>RETENCIONES POR PAGOS EFECTUADOS PERIODO <?php echo $cmb_anio;?></strong></td>   
  </tr> 
</table>
<table width="1500" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse; table-layout:fixed;" >
<thead>
  <tr class="Cabecera1" align="center">
    <td width="8%" rowspan="2" align="left" valign="middle"><strong>C�dula/R.U.C.</strong></td>
    <td width="20%" rowspan="2" align="left" valign="middle"><strong>Apellidos/Nombre</strong></td>
    <td colspan="2"><strong>Enero</strong></td>
    <td colspan="2"><strong>Febrero</strong></td>
    <td colspan="2"><strong>Marzo</strong></td>
    <td colspan="2"><strong>Abril</strong></td>
    <td colspan="2"><strong>Mayo</strong></td>
    <td colspan="2"><strong>Junio</strong></td>
    <td colspan="2"><strong>Julio</strong></td>
    <td colspan="2"><strong>Agosto</strong></td>
    <td colspan="2"><strong>Septiembre</strong></td>
    <td colspan="2"><strong>Octubre</strong></td>
    <td colspan="2"><strong>Noviembre</strong></td>
    <td colspan="2"><strong>Diciembre</strong></td>
  </tr>
  <tr class="Cabecera1">
    <td width="8%"> Base</td>
    <td width="8%"> Renta</td>
    <td width="8%">Base </td>
    <td width="8%"> Renta</td>
    <td width="8%">Base </td>
    <td width="8%"> Renta</td>
    <td width="8%">Base </td>
    <td width="8%"> Renta</td>
    <td width="8%">Base </td>
    <td width="8%"> Renta</td>
    <td width="8%">Base </td>
    <td width="8%"> Renta</td>
    <td width="8%">Base </td>
    <td width="8%"> Renta</td>
    <td width="8%">Base </td>
    <td width="8%"> Renta</td>
    <td width="8%">Base </td>
    <td width="8%"> Renta</td>
    <td width="8%">Base </td>
    <td width="8%"> Renta</td>
    <td width="8%">Base </td>
    <td width="8%"> Renta</td>
    <td width="8%">Base </td>
    <td width="8%"> Renta</td>
  </tr>
 </thead>
 <tbody> 
 <?php
 unset($arrBase);
 unset($arrRenta);
 $TotBaseImpo=0;
 $TotImpRenta=0;
//$param=explode('*',$ci);
for ($i=0;$i<=count($param);$i++)
{ 	
  if($param[$i]!='')
  {
 ?>
  <tr class="Texto_normal_9">	  
    <?php
    	$rs_provee = $obBD_con1->getRowConsulta(4,$param[$i], $obBD_conexion);				
	?>
    <td align="center" style="mso-number-format:'@'" ><?php echo $rs_provee['Prs_Ced'];?></td>
    <td align="left">&nbsp;<?php echo $rs_provee['Prs_Ape'].' '.$rs_provee['Prs_Nom'];?></td>				
    <?php for($x=1;$x<=12;$x++){
		/**
		* Consultar los datos de resumen de compras(Base imponible, Renta) 
		*/
		$rs_resumenBase = $obBD_con1->getRowConsulta(2, $Ses_Emp_Cod.'*'.$param[$i].'*'.$cmb_anio.'-'.str_pad($x, 2, "0", STR_PAD_LEFT).'-01'.'*'.$cmb_anio.'-'.$x.'-'.ultimoDia($x,$cmb_anio), $obBD_conexion);
		$rs_resumenRenta = $obBD_con1->getRowConsulta(3, $Ses_Emp_Cod.'*'.$param[$i].'*'.$cmb_anio.'-'.str_pad($x, 2, "0", STR_PAD_LEFT).'-01'.'*'.$cmb_anio.'-'.str_pad($x, 2, "0", STR_PAD_LEFT).'-'.ultimoDia($x,$cmb_anio), $obBD_conexion);	
	?>
        <td align="right"><?php echo formato_numero($rs_resumenBase['base'],2,2);?></td>
        <td align="right"><?php echo formato_numero($rs_resumenRenta['renta'],2,2);?></td>
   <?php 
   		
   		$arrBase[$x-1]+=formato_numero($rs_resumenBase['base'],2,1);
		$TotBaseImpo+=formato_numero($rs_resumenBase['base'],2,1);
		
		$arrRenta[$x-1]+=formato_numero($rs_resumenRenta['renta'],2,1);
		$TotImpRenta+=formato_numero($rs_resumenRenta['renta'],2,1);
   }?>
  </tr>
  <?php }} ?>
  <tr class="Texto_normal_9">
    <td colspan="2" align="right"><strong>Totales Base Imp. y Reten. a la Fuente</strong></td>                          
        <td align="right"><strong><?php echo formato_numero($arrBase[0],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[0],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrBase[1],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[1],2,2);?></strong></td>         
        <td align="right"><strong><?php echo formato_numero($arrBase[2],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[2],2,2);?></strong></td>         
        <td align="right"><strong><?php echo formato_numero($arrBase[3],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[3],2,2);?></strong></td>         
        <td align="right"><strong><?php echo formato_numero($arrBase[4],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[4],2,2);?></strong></td>         
        <td align="right"><strong><?php echo formato_numero($arrBase[5],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[5],2,2);?></strong></td>         
        <td align="right"><strong><?php echo formato_numero($arrBase[6],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[6],2,2);?></strong></td>         
        <td align="right"><strong><?php echo formato_numero($arrBase[7],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[7],2,2);?></strong></td>         
        <td align="right"><strong><?php echo formato_numero($arrBase[8],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[8],2,2);?></strong></td>         
        <td align="right"><strong><?php echo formato_numero($arrBase[9],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[9],2,2);?></strong></td>          
        <td align="right"><strong><?php echo formato_numero($arrBase[10],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[10],2,2);?></strong></td>         
        <td align="right"><strong><?php echo formato_numero($arrBase[11],2,2);?></strong></td>
        <td align="right"><strong><?php echo formato_numero($arrRenta[11],2,2);?></strong></td>                             
   </tr>      
</tbody>
</table>
<table width="1500" border="0" cellspacing="0" cellpadding="0">
  <tr class="Texto_normal_9">
    <td width="13%" align="right"><strong>Total Base Imponible:</strong></td>
    <td ><?php echo formato_numero($TotBaseImpo,2,2);?></td>
  </tr>
  <tr class="Texto_normal_9">
    <td align="right"><strong>Total Retenci&oacute;n a la Fuente:</strong></td>
    <td ><?php echo formato_numero($TotImpRenta,2,2);?></td>
  </tr>
</table>

</div> 
<?php }?>  
</div>
</body>
</html>
<?php

/* cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* fin cierre las conexiones */
?>