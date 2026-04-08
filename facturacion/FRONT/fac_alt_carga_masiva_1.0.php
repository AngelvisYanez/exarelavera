<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?
/**
* Descripción: Consulta de facturas electronicas
* Fecha de actualización:	16-11-2014 
* Desarrollador:	Jose Cumbicos
*/	  

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_carga_masiva.php');  	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Ret($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Ret;	 	 	 
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
		$contador_1=0;
		$contador_2=0;
		$aux=0;
		$obBD_ins1 = new Class_Log_Datos_Ret;	 	 	 								
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);    				
		if (($fichero = fopen($_FILES["archivo"]["tmp_name"], "r")) !== FALSE) {
			while (($datos = fgetcsv($fichero, 1000)) !== FALSE) {				   				
				if($aux!=0)
				{	
						
						//$dato=explode(",",$datos[0]);
						$row_rs_existe= $obBD_con1->getRowConsulta(2, $datos[0].'*'.$datos[0].'001',$obBD_conexion);
						$total_rs_existe=$row_rs_existe['Prs_Cod'] > 0? 1 : 0;
						if($total_rs_existe==0){
							/*insertamos persona*/
							$obBD_ins1->operacionobBD(1,$datos[0].'*'.$datos[1].'*'.$datos[2].'*'.$datos[3].'*'.$datos[4].'*'.$datos[5],$obBD_conexion);								
							$Prs_Cod = $obBD_ins1->insercionid($obBD_conexion->conexion);
							if ($opt==2)
							{
								/*Insertamos proveedor*/
								$obBD_ins1->operacionobBD(3,$Prs_Cod.'*'.$Ses_Emp_Cod.'*'.$datos[6].'*'.$datos[7].'*'.$datos[8].'*'.$datos[2],$obBD_conexion);
								$contador_1++;
							}else{
								/*Insertamos cliente*/
								$obBD_ins1->operacionobBD(4,$Prs_Cod.'*'.$Ses_Emp_Cod.'*'.$datos[6].'*'.$datos[7].'*'.$datos[8],$obBD_conexion);
								$contador_1++;
							}
						}else{
							if ($opt==1)
							{	
								$row_rs_existeCliente= $obBD_con1->getRowConsulta(5, $row_rs_existe['Prs_Cod'].'*'.$Ses_Emp_Cod,$obBD_conexion);
								$total_rs_existeCliente=$row_rs_existeCliente['Prs_Cod'] > 0? 1 : 0;
								if($total_rs_existeCliente==0)
								{
								 /*Insertamos cliente*/
								 $obBD_ins1->operacionobBD(4,$row_rs_existe['Prs_Cod'].'*'.$Ses_Emp_Cod.'*'.$datos[6].'*'.$datos[7].'*'.$datos[8],$obBD_conexion);
									$contador_1++;
								}else{
									$contador_2++;
								}
							}else{
								$row_rs_existeProvee= $obBD_con1->getRowConsulta(6, $row_rs_existe['Prs_Cod'].'*'.$Ses_Emp_Cod,$obBD_conexion);
								$total_rs_existeProvee=$row_rs_existeProvee['Prs_Cod'] > 0? 1 : 0;
								if($total_rs_existeProvee==0)
								{
								  /*Insertamos proveedor*/
								  $obBD_ins1->operacionobBD(3,$row_rs_existe['Prs_Cod'].'*'.$Ses_Emp_Cod.'*'.$datos[6].'*'.$datos[7].'*'.$datos[8].'*'.$datos[2],$obBD_conexion);
								  $contador_1++;
								}else{
								  $contador_2++;
								}
							}
						}
						unset($dato);
					
				}
				$aux=1;
			}
		}
	   $obBD_ins1->fin_transaccion($obBD_conexion->conexion);				
	}
}

?>

<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>				
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>              
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">       
	</HEAD>
<BODY>

<form method="post" name="form3" id="form3" enctype="multipart/form-data" action="<? echo $_SERVER['PHP_SELF'];?> ">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
	  <td height="10">&raquo; Subir comprobantes electronicos .Xml</td>
</tr>
<tr>
 <td align="left" valign="top" height="300">

   	
    <?Php $thisPost->startPost(); ?>
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Facturas pendientes de enviar</label>
    </LEGEND>
     <table width="560" border="0" cellpadding="0" cellspacing="0">
     <tr>
       <td align="right" class="LetraNegra">Tipo:</td>
       <td width="135"><input type="radio" name="opt" id="opt" value="1" />
         <label for="opt">Clientes(<a href="cliente.csv">Formato</a>)</label></td>
       <td width="175"><input type="radio" name="opt" id="opt" value="2" />
         <label for="opt2">Proveedores (<a href="proveedor.csv">Formato</a>)</label></td>
       <td width="163">&nbsp;</td>
     </tr>
     <tr>	
       <td width="87" align="right" class="LetraNegra">Seleccione:</td> 
       <td colspan="3">&nbsp;<input type="file" name="archivo" id="archivo" accept=".csv" /></td>
       </tr>   
     </table>
    </FIELDSET>
   <? if(isset($save)){ ?>	
	<FIELDSET>
    <LEGEND>
    <label class="Titulos2">Informaci&oacute;n del guardado</label>
    </LEGEND>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="14%" class="Etiqueta1">Total ingresados:</td>
        <td width="86%" class="Alertas">&nbsp; <? echo $contador_1;?></td>
      </tr>
      <tr>
        <td class="Etiqueta1">Total rechazados:</td>
        <td class="Alertas3">&nbsp; <? echo $contador_2;?></td>
      </tr>
    </table>
    </FIELDSET>  
    <? } ?>   
	  
    <table width="200" border="0" cellpadding="1" cellspacing="1">
      <tr>
        <td><input type="hidden" id="save" name="save" value="1" />
          <button type="button" id="btnGuadar" class="btn btn-primary start" title="Guardar" onclick="form.submit();"> <i class="icon-book icon-white"></i> <span>Guardar</span> </button></td>
      </tr>
    </table>
    <br />   
</td>
</tr>
</table>	

</form>   
</BODY>
</HTML>
<?php
/**
* Cierra las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();	
?>