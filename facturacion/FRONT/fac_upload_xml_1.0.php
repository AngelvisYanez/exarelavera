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

/* Evitar el reenvio de formularios **/
if ($thisPost->postBlock($_POST['postID'])) 
{
	if(isset($save))
	{	
	    $xml =new DOMDocument();		
		$tot = count($_FILES["archivo"]["tmp_name"]);  														
		$obBD_ins1 =  new Class_Log_Datos_Tes;	 	 	 								
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);    
		for ($i = 0; $i < $tot; $i++){ 
		    $nom=explode(".",$_FILES["archivo"]["name"][$i]);
			$tip = explode("/",$_FILES['archivo']['type'][$i]);
			if($tip[1]=="xml" )// Solo acepta archivos .xml
			{
				$sri= simplexml_load_file($_FILES["archivo"]["tmp_name"][$i]);
				$datos = $sri->comprobante;
				$xml = simplexml_load_string($datos);
				if ($sri->estado=="AUTORIZADO") // acepta xml autorizados por el sri
				{										
					$aut=$sri->numeroAutorizacion;
					$claAcc=$xml->infoTributaria[0]->claveAcceso;					
					$tipDcto=$xml->infoTributaria[0]->codDoc;					
					if($tipDcto=='01') //Factura
					{
						$obBD_ins1->operacionobBD(5,$aut.'*'.$claAcc,$obBD_conexion);				
					}
                                        if($tipDcto=='04') //Nota de Credito
					{
						$obBD_ins1->operacionobBD(5,$aut.'*'.$claAcc,$obBD_conexion);				
					}
					if($tipDcto=='07') //Retencion
					{
						$obBD_ins1->operacionobBD(26,$aut.'*'.$claAcc,$obBD_conexion);
					}
					if($tipDcto=='06') //Guia Remision
					{
						$obBD_ins1->operacionobBD(27,$aut.'*'.$claAcc,$obBD_conexion);
					}
				}
			}
		}
	   $obBD_ins1->fin_transaccion($obBD_conexion->conexion);				
	}
}

?>
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>		
		<script type="text/javascript" src="../VALIDACIONES/fac_val_aju.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>	           
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
        <script type="text/javascript">
		
		</script>
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
	  <td height="10">&raquo; Subir comprobantes electronicos .Xml</td>
</tr>
<tr>
 <td align="left" valign="top" height="400">

   	<form method="post" name="form3" id="form3" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'];?> ">
    <?Php $thisPost->startPost(); ?>
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Facturas pendientes de enviar</label>
    </LEGEND>
     <table width="560" border="0" cellpadding="0" cellspacing="0">
     <tr>	
       <td width="87" align="right" class="LetraNegra">Seleccione:</td> 
       <td width="345">&nbsp;<input type="file" multiple name="archivo[]" id="archivo[]" value="<?php echo $archivo;?>" accept="text/xml" /></td>
       <td width="128">
         <button type="button" class="btn btn-primary start" onclick="this.form.submit();"><i class=" icon-ok-sign icon-white"></i> <span>Subir</span> </button>
         <input type="hidden" id="save" name="save" value="1" />
         </td>
     </tr>   
     </table>
    </FIELDSET>
   <?php if(isset($save)){ ?>	
	<FIELDSET>
    <LEGEND>
    <label class="Titulos2">Archivos subidos</label>
    </LEGEND>
	<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01" style="table-layout:fixed;">
      <thead>
        <tr>
          <th width="4%">#</th>
          <th width="9%">Fecha </th>
          <th width="22%">Clientes</th>
          <th width="34%">Archivo XML</th>
          <th width="19%">Tipo Comprobante</th>
          <th width="7%">Total</th>
          <th width="5%">&nbsp;</th>
        </tr>
      </thead>
      <tbody>
        <?php 
		
		
		$tot = count($_FILES["archivo"]["name"]);  										
		//este for recorre el arreglo 		
		   
		for ($i = 0; $i < $tot; $i++)
		{     
			//con el indice $i, poemos obtener la propiedad que desemos de cada archivo 
			//para trabajar con este         
			
			$tmp_name = $_FILES["archivo"]["tmp_name"][$i]; 			
			$name = $_FILES["archivo"]["name"][$i]; 
			$ruta= $_POST["archivo"]["name"][$i]; 
			$tipo = explode("/",$_FILES['archivo']['type'][$i]);										    
									
			if($tipo[1]=="xml" )
			{ 
				//cargamos el archivo XML
				//$sri = simplexml_load_file($Ses_Emp_Cod."/".$name);					
				$sri = simplexml_load_file($_FILES["archivo"]["tmp_name"][$i]);
				$datos = $sri->comprobante;
				$xml = simplexml_load_string($datos);
			    if($sri->estado=="AUTORIZADO")
				{   
					$subioXml="NO";				
					/* Consultamos los comprobantes digitales sin Autorizacion (factura, retencion) SRI*/
				    $rs_comprobantes = $obBD_con1->getArrayConsulta(6, $Ses_Suc_Cod.'*'.$xml->infoTributaria[0]->claveAcceso, $obBD_conexion);				  
					if(count($rs_comprobantes)!=0)
					{
						move_uploaded_file($_FILES["archivo"]["tmp_name"][$i], $Ses_Emp_Cod."/".$xml->infoTributaria[0]->claveAcceso."_A.".$tipo[1]);
						unlink($_FILES["archivo"]["name"][$i]);										
						$subioXml="SI";
					}										
				}
			}
		?>
        <tr>
          <td align="center"><?php echo $i+1?></td>
          <td align="center">
		  <?php 
		    if($sri->estado=="AUTORIZADO" && $subioXml=="SI")
			{
				$cadena =substr($xml->infoTributaria[0]->claveAcceso,0,8);			
				$cad_arr=str_split($cadena);		
				$nueva="";
				for( $n=0; $n < strlen($cadena); $n++ ) {                   						
					if($n==1 or $n==3)
					{
						$aux =  $cad_arr[ $n ]. "-"; 
					}else{
						$aux =  $cad_arr[ $n ];		
					}
					$nueva.=$aux;				  	
				}						
				echo date("d-m-Y", strtotime($nueva));		
			}else{
				echo "-";
			}
		  ?>
          </td>
          <td align="left" style="white-space: nowrap; overflow: hidden;">&nbsp;
          <?php          				  									
			if($sri->estado=="AUTORIZADO" && $subioXml=="SI")
			{	
				if(substr($xml->infoTributaria[0]->claveAcceso,8,2)=="01") // Factura
				{
					//extraemos directamente un elemento
					$cliente = $xml->infoFactura[0]->razonSocialComprador;
					echo $cliente; 
				}
				if(substr($xml->infoTributaria[0]->claveAcceso,8,2)=="04") // Factura
				{
					//extraemos directamente un elemento
					$cliente = $xml->infoNotaCredito[0]->razonSocialComprador;
					echo $cliente; 
				}
                                if(substr($xml->infoTributaria[0]->claveAcceso,8,2)=="07") // Retencion
				{
					//extraemos directamente un elemento
					$cliente = $xml->infoCompRetencion[0]->razonSocialSujetoRetenido;
					echo $cliente; 
				}
				if(substr($xml->infoTributaria[0]->claveAcceso,8,2)=="06") // Guia Remision
				{
					//extraemos directamente un elemento
					$destinatario = $xml->destinatarios[0]->destinatario[0]->razonSocialDestinatario;
					echo $destinatario; 
				}
			}else{
				echo "-";	
			}
		  ?>
          </td>
          <td align="center">&nbsp;<?php echo $name; ?></td>
          <td align="center">
            <?php 		  	
				if($sri->estado=="AUTORIZADO" && $subioXml=="SI")
				{
					$rs_nomCompr = $obBD_con1->getRowConsulta(3, substr($xml->infoTributaria[0]->claveAcceso,8,2), $obBD_conexion);
					echo $rs_nomCompr['Tic_Des'];
				}else{
					echo "-";
				}
		  ?>
          </td>
          <td align="center">
          <?php          				  		
			if($sri->estado=="AUTORIZADO" && $subioXml=="SI")
			{	
				if(substr($xml->infoTributaria[0]->claveAcceso,8,2)=="01") // Factura
				{
					$impTotal = $xml->infoFactura[0]->importeTotal;
					echo $impTotal; 
				}
				if(substr($xml->infoTributaria[0]->claveAcceso,8,2)=="07") // Retencion
				{
					$totDet= count($xml->impuestos[0]->impuesto);					
					//sumamos todos los valores retenidos
					for($x=0;$x<=$totDet-1;$x++)
					{
						$valorRet= $valorRet + (float)$xml->impuestos[0]->impuesto[$x]->valorRetenido;
					}
					echo $valorRet;
				}
				if(substr($xml->infoTributaria[0]->claveAcceso,8,2)=="06") // Guia de Remision
				{
					echo "-";
				}
			}else{
				echo "-";	
			}
		  ?>
          </td>
          <td align="center">
          	<?php if($sri->estado=="AUTORIZADO"){
				  if($subioXml=="SI"){
				?>
	            	<img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" title="Se subio correctamente..." />
                <?php }else{?>
                	<img src="../../mascaras/model1/imagenes/error.gif" width="16" height="16" title="El comprobante no existe en Exa" />
                <?php }?>
            <?php }else{ ?>
            	<img src="../../mascaras/model1/imagenes/error.gif" width="16" height="16" title="El comprobante no posee Autorizacion del SRI" />
            <?php }?>
          </td>
        </tr>
        <?php  				
		}	//fin del for ($i = 0; $i < $tot; $i++)			
		?>		  
      </tbody>
    </table>
    <?php echo barra_estado($tot);?>
    </FIELDSET>     
	</form>   
    <br />   
</td>
</tr>
</table>	
<?php } ?>		
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
        <div id="ajax_modal">
        </div>
    </div>
</div>
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_aju.js?z=10"></script>
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