<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/*
* Descripci�n: Anula las facturas de ventas en base al vendedor y su punto de impresi�n
* Fecha de actualizaci�n: 2012-03-15
* Desarrollador: Lewis Chimarro
* Fecha de actualizaci�n: 2012-05-22
* Desarrollador: Lewis Chimarro
*/	
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven.php');	  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;	 	 	 
/* 
* Evitar el reenvio de datos 
*/
$thisPost = new Post_Block;

/*
* Consulta del vendedor en base al codigo de la persona
*/
$rs_vendedor = $obBD_con1->consulta(sentencias_tes(24, $obBD_con1->parametros($Ses_Prs_Cod.'*'.$Ses_Suc_Cod)), $obBD_conexion->conexion);
$row_rs_vendedor = $obBD_con1->registros();
$total_rs_vendedor = $obBD_con1->numregistros();	
$Pun_Cod = $row_rs_vendedor['Pun_Cod'];





/* 
* Control para iniciar la primera opcion 
*/
if (!(isset($op)))
{
	$op = 1;
}	    

/* 
* Evitar el reenvio de formularios 
*/
if ($thisPost->postBlock($_POST['postID']))
{
	if (isset($elim))
	{				
	 	 $obBD_ins1 =  new Class_Log_Datos_Tes;					  
		/* 
		* Control para I N V E N T A R I O S 
		*/
		/* 
		* Anula los detalles del kardex 
		*/
		$obBD_ins1->grabarv_registros(sentencias_tes(1062, $obBD_ins1->parametros($Vet_Cod)), $obBD_conexion->conexion);
		/* 
		* F I N Control para I N V E N T A R I O S 
		*/
		/* 
		* Inactiva el estado de la factura 
		*/ 
		$obBD_ins1->grabarv_registros(sentencias_tes(107, $obBD_ins1->parametros('I'.'*'.$Vet_Cod)), $obBD_conexion->conexion);
		/* 
		* Inactiva el estado del comprobante contable
		*/ 
		$obBD_ins1->grabarv_registros(sentencias_tes(1292, $obBD_ins1->parametros('I'.'*'.$Vet_Cod)), $obBD_conexion->conexion);
	
		/* 
		* Consulta los detalles de la factura 
		*/
		$rs_detalle = $obBD_con1->consulta(sentencias_tes(1208, $obBD_con1->parametros($Vet_Cod)), $obBD_conexion->conexion);
		$row_rs_detalle = $obBD_con1->registros();
		$total_rs_detalle= $obBD_con1->numregistros();
						
		do{		
			/* 
			* Consulta el Stock 
			*/
			$rs_conpro = $obBD_con1->consulta(sentencias_tes(1206, $obBD_con1->parametros($row_rs_detalle['Pro_Cod'])), 
								$obBD_conexion->conexion);
			$row_rs_conpro = $obBD_con1->registros();
			$total_rs_conpro = $obBD_con1->numregistros();
			/* 
			* Control para I N V E N T A R I O S 
			*/		
			/* 
			* Actualizo el Stock 
			*/
			$obBD_ins1->grabarv_registros(sentencias_tes(1204, $obBD_ins1->parametros($row_rs_conpro['Stock'].'*'.$row_rs_detalle['Pro_Cod'].'*'.$Ses_Suc_Cod)), $obBD_conexion->conexion);			
			/* 
			* F I N Control para I N V E N T A R I O S 
			*/
		} while($row_rs_detalle = $obBD_con1->fetch_assoc($rs_detalle));
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);		  	  
	}//Fin del if (isset($elim))
}//Fin del if ($thisPost->postBlock($_POST['postID']))	

	
switch ($op){
	case 1: 		
		if ($txt_busqueda != "")
		{
			if ($op_opciones == "d")
			{
				$rs_buscar = $obBD_con1->consulta(sentencias_tes(1258, $obBD_con1->parametros(trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Ses_Suc_Cod)),$obBD_conexion->conexion);
			}
			else 
			{
				$rs_buscar = $obBD_con1->consulta(sentencias_tes(1232, $obBD_con1->parametros(trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Ses_Suc_Cod)),$obBD_conexion->conexion);
			}
			$row_rs_buscar = $obBD_con1->fetch_assoc($rs_buscar);
			$total_rs_buscar = $obBD_con1->num_rows($rs_buscar);
		}
	break;
}//FIn del case $op
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>	
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    	<script type="text/javascript" src="../VALIDACIONES/fac_val_fac_ven.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script> 
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<?Php
/* Evalua si el usuario es un vendedor */
if ($total_rs_vendedor > 0)
{
	
?>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	 <tr class="BarraTitulo">
	  <td width="45%">&raquo; Anular  ventas </td>
      <td width="39%" align="right">&raquo; <strong>PUNTO DE IMPRESION:</strong> <?Php echo $row_rs_vendedor['Pun_Des']; ?></td>     
    </tr>
  <tr>
        <td height="400" colspan="3" valign="top">		
		<?php
		$pag1= $_SERVER['PHP_SELF']."?op=1";
		$pag2= $_SERVER['PHP_SELF']."?op=2";
		tabs(2,'Individual*Todas', $pag1.'*'.$pag2, $op);
		?>		
<div id="ContTabul">
    		
<?Php
switch ($op){
	case 1: ?>
<form name="form1" method="post" action="<?Php $_SERVER['PHP_SELF']?>">   
<FIELDSET>
	<legend>
	<label class="Titulos2">Tipo de documento:</label></legend>
    <table width="565" border="0" cellpadding="0" cellspacing="0">
    <tr>
    <td width="120" class="Etiqueta1"  ><span class="Asterisco">*</span> Tipo documento:&nbsp;</td>
    <td width="445">  
   <?php $rs_comp = $obBD_con1->consulta(sentencias_tes(1320, $obBD_con1->parametros($Ses_Emp_Cod)), $obBD_conexion->conexion);
	  $row_rs_comp = $obBD_con1->registros();
	  $total_rs_detalle= $obBD_con1->numregistros();
	?>
      <select name="Tic_Cod" id="Tic_Cod">
              <!--<option  <?Php //if ($Tic_Cod == 1){ echo "selected"; } ?> value="1">FACTURA</option>-->
              <?php do{?>
              <option  value="<?Php echo $row_rs_comp['Tic_Cod']; ?>"><?Php echo $row_rs_comp['Tic_Des']; ?></option>
              <?php } while($row_rs_comp = $obBD_con1->fetch_assoc($rs_comp));?>
      </select>
     </td>
  </tr>
</table>  
</FIELDSET>        
<FIELDSET>
<LEGEND>
<label class="Titulos2">Buscar cliente por:</label>
</LEGEND>
<table width="495" border="0">
    <tr>
      <td width="205"><input name="op_opciones" type="radio" value="d" onClick="setfocus(this.form.txt_busqueda)" checked>
        <span class="LetraNegra">Apellidos</span></td>
      <td width="266"><input type="radio" name="op_opciones" value="r" onClick="setfocus(this.form.txt_busqueda)">
        <span class="LetraNegra">No. Documento</span></td>
    </tr>
  </table>

  <table width="546" height="36" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="546" height="28" class="BarraBusqueda"><div align="left"><span class="Asterisco">* </span>Busqueda:
        <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="40" maxlength="50">&nbsp;&nbsp;&nbsp;
        <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Deudas" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)">
          <i class="icon-search icon-white"></i>
          <span>Buscar</span>
          </button>    </div>  </td>
      </tr>
  </table>
</FIELDSET>
</form>
  <?Php
  	if(isset($txt_busqueda))
	{
		 ?>
  <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
<input name="Caj_Fec" type="hidden" id="Caj_Fec" value="<?php echo $hoy; ?> ">
</LEGEND>
	<table width="100%"  border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
	    <th width="8%" align="center">C&oacute;d. Int.</th>
          <th width="17%" align="center">No. Documento</th>
          <th width="48%"  align="center">Clientes</th>
          <th width="16%" align="center">Fecha</th>	  		  
		  <th width="7%" align="center">&nbsp;</th>
      </tr>
     </thead>
     <tbody>
	  <?Php 
	if($total_rs_buscar != 0)
	{
	  do { 
	  if($row_rs_buscar['Vet_Est']=='I')
	  		  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
	  ?>
      <form name="form1" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
	  <tr>
	    <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Vet_Cod']; ?></FONT></td>
		<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Suc_Sri'].'-'.$row_rs_buscar['Pun_Sri'].'-'.str_pad($row_rs_buscar['Vet_Num'],9,0,STR_PAD_LEFT ); ?></FONT></td>
		<td align="left"><FONT COLOR="<?php echo $rojo;?>">		
		<?Php echo marcarCadenaColor($txt_busqueda,$row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom'],'#FFFF00', '#000', 1); ?></FONT></td>
		<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Caj_Fec']; ?></FONT></td>		
		<td align="center">
        <?Php if ($row_rs_buscar['Vet_Est'] == 'A') { ?>
         <input name="Vet_Cod" id="Vet_Cod" type="hidden" value="<?Php echo $row_rs_buscar['Vet_Cod']; ?>">
         <input name="elim" id="elim" type="hidden" value="<?Php echo $row_rs_buscar['Vet_Est']; ?>">
         <input name="op" id="op" type="hidden" value="1">       
		<button type="button" class="btn btn-danger delete" title="Anular Venta archivo" onclick="confirmacion2(this.form)">
                    <i class="icon-trash icon-white"></i>
                    <span>Anular</span>
        </button>                
           		    <?php
        /* Creacion del campo repost */
	$thisPost->startPost(); ?>
          <?Php } else { echo "&nbsp;"; } ?>  
            </td>
	  </tr>
      </form>
	  <?Php } while ($row_rs_buscar = $obBD_con1->fetch_assoc($rs_buscar)); ?>
<?Php 
  	}
	else
	{ ?>
          <tr>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center"><?Php echo error_alerta("�No hay resultados que mostrar!", 1); ?></td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            </tr>    
	<?php  					
	}
  ?>  
  </tbody>    
  </table>
</FIELDSET>
<?Php
	echo barra_estado($total_rs_buscar);	
}
break;

case 2: 
$txt_fec_ini = $hoy;
$txt_fec_fin = $hoy;
?> 
<form action="<?Php echo $_SERVER['PHP_SELF']; ?>" name="form2" id="form2">
<FIELDSET>
	<legend>
	<label class="Titulos2">Tipo de documento:</label></legend>
    <table width="587" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="127" class="Etiqueta1"  ><span class="Asterisco">*</span> Tipo documento:&nbsp;</td>
    <td width="356">  
   
      <select name="Tic_Cod" id="Tic_Cod">
              <option  <?Php if ($Tic_Cod == 1){ echo "selected"; } ?> value="1">FACTURA</option>
              <option  <?Php if ($Tic_Cod == 2){ echo "selected"; } ?> value="2">* NOTA O BOLETA DE VENTA</option>
            </select></td>
    <td width="104"><button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Deudas" onclick="validar_requeridos(this.form, 'Tic_Cod', 0)">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
           </button>     
      <input name="hdd_fec" type="hidden" id="hdd_fec" value="" />
       <input name="op" type="hidden" id="op" value="2" />
      </td>
  </tr>
</table>  
</FIELDSET>
</form>        
<?Php
if (isset($hdd_fec))
{
     $rs_buscar = $obBD_con1->consulta(sentencias_tes(1233, $obBD_con1->parametros($Tic_Cod.'*'.$Pun_Cod)),$obBD_conexion->conexion);
	 $row_rs_buscar = $obBD_con1->registros();
	 $total_rs_buscar = $obBD_con1->numregistros();
  ?>
<br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la b&uacute;squeda</label>
</LEGEND>
	<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
          <th width="8%" align="center">C&oacute;d. Int.</th>
		  <th width="12%" align="center">No. Documento</th> 
		  <th width="60%" align="center">Clientes</th>
		  <th width="10%" align="center">Fecha</th> 
		  <th width="10%" align="center">&nbsp;</th>
	  </tr>
    </thead>
    <tbody>
<?Php 
	 if ($total_rs_buscar > 0)
	 {		
		do { 
			if($row_rs_buscar['Vet_Est']=='I')
	  		  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
		?> 		
        <form name="form1" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
    	<tr>
		  <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row_rs_buscar['Vet_Cod']; ?></FONT></td>
		  <td align="center"><font color="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Vet_Num']; ?></font></td>
		  <td><FONT COLOR="<?php echo $rojo;?>"><?PHP echo $row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom']; ?></FONT></td>
		  <td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row_rs_buscar['Caj_Fec']; ?></FONT></td>
		   <td align="center"><?Php if ($row_rs_buscar['Vet_Est'] == 'A') { ?>
         <input name="Vet_Cod" id="Vet_Cod" type="hidden" value="<?Php echo $row_rs_buscar['Vet_Cod']; ?>">
         <input name="elim" id="elim" type="hidden" value="<?Php echo $row_rs_buscar['Vet_Est']; ?>">
         <input name="op" id="op" type="hidden" value="2">          		
        <button type="button" class="btn btn-danger delete" title="Anular Venta archivo" onclick="confirmacion2(this.form)">
                    <i class="icon-trash icon-white"></i>
                    <span>Anular</span>
        </button>
        <?Php /* Creacion del campo repost */
	$thisPost->startPost();  ?>
          <?Php } else { echo "&nbsp;"; } ?>  </td>
	    </tr>
        </form>    	
	 <?Php } while ($row_rs_buscar = $obBD_con1->fetch_assoc($rs_buscar)); 
	 }
	else
	{
  ?>
 	 <tr>
    	<td align="center">&nbsp;</td>
    	<td>&nbsp;</td>
    	<td align="center"><?Php echo error_alerta("&iexcl;No hay resultados que mostrar!", 1); ?></td>
    	<td align="right">&nbsp;</td>
    	<td align="center">&nbsp;</td>
  	  </tr>	  				
  <?Php
	}
	?>
    </tbody> 
  </table>
</FIELDSET>
<?php
echo barra_estado($total_rs_buscar);
  } //Fin del if (isset($hdd_fec))
}

if ($anulada > 0)
{		
	$com_leyenda[1]=$anulada;
}//Fin del if ($anulada > 0)
?>
  <?php
require_once('../../componentes/FRONT/com_con_leyenda.php');
?>
  <br/>
</div>
 </td>
  </tr>
</table>
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_fac_ven.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	
<?php

}//Fin del if ($total_rs_vendedor > 0)
else
{
	echo error_alerta (" Ud. no es un Vendedor autorizado para emitir facturas", 2);
}//Fin de else del if ($total_rs_vendedor > 0) ?>
</BODY></HTML>
<?php
@$obBD_con1->free_result($rs_vendedor); 
@$obBD_con1->free_result ($rs_CajFec);
@$obBD_con1->free_result($rs_detalle);
@$obBD_con1->free_result($rs_conpro);
@$obBD_con1->free_result($rs_buscar);
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>