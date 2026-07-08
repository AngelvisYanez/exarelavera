<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
* Descripción: Consulta de facturas electronicas
* Fecha de actualización:	16-11-2014 
* Desarrollador:	Jose Cumbicos
*/	  

//require_once('../../administrador/LOGICA/seguridad.php');
//require_once('../LOGICA/fac_log_fac_ven_xml.php');  	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/**
* Creacion del Objeto de conexion 
*/
//$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
//$obBD_con1 =  new Class_Log_Datos_Tes;	 	 	 
/**
* Llamado de la libreria para evitar el reenvio de datos 
*/
//$thisPost = new Post_Block;

$hoy = date("Y-m-d");

/* Evitar el reenvio de formularios **/
if(isset($save))
{	echo "12";
	    $xml =new DOMDocument();	
		$tot = count($_FILES["archivo"]["tmp_name"]);  																	 	 	 										        
		for ($i = 0; $i < $tot; $i++)
		{ 
		    $nom=explode(".",$_FILES["archivo"]["name"][$i]);
			$tip = explode("/",$_FILES['archivo']['type'][$i]);
			
			if($tip[1]=="xml" )// Solo acepta archivos .xml
			{   
				$load = simplexml_load_file($_FILES["archivoXML"]["tmp_name"][$i]);																	
				$totExp=count($load->compras[0]->detalleCompras);
				$datos = $load->compras;				
				$totFac=0;
				$totFob=0;
				
				for($x=0;$x<$totExp;$x++)	
				{   												
					 
					//$_RenderedXML = new SimpleXMLElement($_XML);

					$insert = new SimpleXMLElement("<parteRel>NO</parteRel>");
					// Get the last nodeA element
					$target = current($load->compras->detalleCompras[$x]->xpath('//tipoComprobante[last()]'));
					// Insert the new element after the last nodeA
					simplexml_insert_after($insert, $target);
					
				}  //fin for($x=0;$x<$totCom;$x++)
				var_dump( $load->asXML());	
												
			}
		}
	  			
}


?>
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>				
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>	           
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
        <script language="JavaScript">
		
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
    <?Php //$thisPost->startPost(); ?>
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
          <td align="center">&nbsp;</td>
          <td align="center">&nbsp;</td>
          <td align="left" style="white-space: nowrap; overflow: hidden;">&nbsp;</td>
          <td align="center">&nbsp;</td>
          <td align="center">&nbsp;</td>
          <td align="center">&nbsp;</td>
          <td align="center">&nbsp;
          	
<!--	            	<img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" title="Se subio correctamente..." />
               
                	<img src="../../mascaras/model1/imagenes/error.gif" width="16" height="16" title="El comprobante no existe en Exa" />
               
         
            	<img src="../../mascaras/model1/imagenes/error.gif" width="16" height="16" title="El comprobante no posee Autorizacion del SRI" />-->
         
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
   
</BODY>
</HTML>
