<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	  
/*Alias:Ingreso de Guia.
Fecha de actualizaci�n:	30-10-2013 
Desarrollador:	Jose Cumbicos Ortiz*/
	
require_once('../../administrador/LOGICA/seguridad.php');	  
require_once('../LOGICA/fac_log_guias.php');  	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  
require_once('../../Librerias/postclass.php');
    
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Gui($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Gui;	 	 	 
/* Llamado de la libreria para evitar el reenvio de datos */
$thisPost = new Post_Block;
/* Llamado a componente ajax */
if (file_exists("../COMPONENTES/ajax_con_ctaguia.php")) require_once("../COMPONENTES/ajax_con_ctaguia.php");
$hoy = date("Y-m-d");


if($ajax_agregar==1)
{
?>
<table id="tb_busquedaCta" width="100%" border="0" cellpadding="0" cellspacing="0">
 <tr>
   <td><?Php 
         /* C= buscador con cargado en combos */
        $tipo_busc = 'F'; 
        $Capa = 'busqueda_f';
        $Nombre_Buscador = 'buscta';//Cuadro de texto
        $Nombre_Opciones = 'op_opciones';//Option	
        //$Pla_Cod=2;	
        ?>
        <?Php require_once('../COMPONENTES/com_con_ctaAjuste.php'); ?>
    </td>
  </tr>
</table>
<?php	
exit();	
}


if($ajax_cargar==1)
{
	/*Consulta del vendedor en base al codigo de la persona*/
	$row = $obBD_con1->getArrayConsulta(2214,$Con_Cod,$obBD_conexion);		
	$total_row=count($row);
?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Solicitudes existentes</label>
	</LEGEND>
	<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader02">
		 <thead>
		  <tr>
			<th width="10%" align="center"><strong>C&oacute;d. Int.</strong></th>
			<th width="12%"><strong>Fecha</strong></th>
			<th width="10%">Hora</th>
			<th width="60%"><strong>Personal</strong></th>
			<th width="2%">&nbsp;</th>
		  </tr>
          </thead>
          <tbody>
		  <?php  
		  if($total_row!=0){
		  foreach($row as $datos) {  ?>
		  
		  <tr>
			<td height="73%" align="center"><?php echo $datos['Slc_Cod']; ?></td>
			<td align="center"><?php echo $datos['Slc_Fec']; ?></td>
			<td align="center"><?php echo $datos['Slc_Hor']; ?></td>
			<td align="left">&nbsp;<?php echo marcar_cadena($txt_busqueda,$datos['Prs_Ape'].' '.$datos['Prs_Nom'],'#FFFF00', 1) ?></td>			
            <td width="2%" align="center">
			<input type="hidden" name="Slc_Cod" id="Slc_Cod" value="<?Php echo $datos['Slc_Cod'];?>">			
            <input type="hidden" name="Con_Cod" id="Con_Cod" value="<?Php echo $datos['Con_Cod'];?>">
			<button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="Muestra_Aparecer();ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&Slc_Cod=<?Php echo $datos['Slc_Cod'];?>&Con_Cod=<?Php echo $datos['Con_Cod'];?>&Slc_Fec=<?Php echo $datos['Slc_Fec'];?>','bgmodal')"><i class="icon-arrow-right icon-white"></i></button>
            </td>           
		  </tr>		  
		  <?php }
		  }else{?>
            <tr>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td><?Php echo error_alerta("�No hay resultados que mostrar!", 1) ?></td>
              <td>&nbsp;</td>
            </tr>
     	  <?php }?>
          </tbody>
	  </table>
</FIELDSET>
<?php echo barra_estado($total_row);?>   
<?php	
exit();	
}

if($ajax_detalle==1)
{
	/*Consulta del vendedor en base al codigo de la persona*/
	$row = $obBD_con1->getArrayConsulta(2215,$Slc_Cod,$obBD_conexion);		
	$total_row=count($row);
	?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Detalle existentes</label>
	</LEGEND>
    <table border="0" cellpadding="0" cellspacing="0">
    <tr>
    	<td><strong>Fecha de Pedido:</strong></td>
        <td>&nbsp;<?php echo $Slc_Fec;?></td>
    </tr>
    </table>
	<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader02">
		 <thead>
		  <tr>
			<th width="10%" align="center"><strong>Cant. Pedido</strong></th>
            <th width="10%" align="center"><strong>Cant. Entregado</strong></th>
            <th width="10%" align="center"><strong>Cant. Pendiente</strong></th>
			<th width="12%"><strong>Marca</strong></th>			
			<th width="76%"><strong>Descripci&oacute;n</strong></th>
			<th width="2%"><input type="checkbox" id="all" title="Todos" name="all" value="checkbox" onClick="todoCheck('<?Php echo $total_row;?>', this)"></th>
		  </tr>
          </thead>
          <tbody>
		  <?php  
		  if($total_row!=0){
		  $x=0;	  
		  foreach($row as $datos) { 
		  /*Consulta si el producto ya fue entragado posteriormente*/
		  $busca = $obBD_con1->getRowConsulta(2216,$Slc_Cod.'*'.$datos['Pro_Cod'],$obBD_conexion);				  	
		  $total_busca=$busca['Pro_Cod'] > 0? 1 : 0;	   
		  $x++; 
		  ?>		  
		  <tr>
			<td align="center">
			<?php echo $datos['Slc_Can']; ?> 
            <input type="hidden" name="Hdd_Slc_Can[<?php echo $x?>]" id="Hdd_Slc_Can[<?php echo $x?>]" value="<?Php echo $datos['Slc_Can'];?>">
            </td>
            <td align="center">
			<?php echo formato_numero($busca['Gia_Can'],2,1); ?>
            <input type="hidden" name="Hdd_Gia_Cod[<?php echo $x?>]" id="Hdd_Gia_Cod[<?php echo $x?>]" value="<?Php echo $busca['Gia_Can'];?>">
            </td>            
            <td align="center"><input type="hidden" id="ProCod[<?php echo $x?>]" name="ProCod[<?php echo $x?>]" value="<?php echo $datos['Pro_Cod']; ?>" />
            <?php if($datos['Slc_Can'] > $busca['Gia_Can']){?>
            <input type="text" size="6" id="Cant[<?php echo $x?>]" name="Cant[<?php echo $x?>]" value="<?php echo $datos['Slc_Can'] - $busca['Gia_Can']; ?>" />
            <?php }else{ echo $datos['Slc_Can'] - $busca['Gia_Can'];}?>
            </td>
			<td align="center"><?php echo $datos['Mar_Des']; ?><input type="hidden" id="ProDes[<?php echo $x?>]" name="ProDes[<?php echo $x?>]" value="<?php echo $datos['Ite_Lar']; ?>" /></td>			
			<td align="left">&nbsp;<?php echo $datos['Ite_Lar']; ?></td>			
            <td align="center">
			<?php if($datos['Slc_Can'] > $busca['Gia_Can']){?>
            	<input type="checkbox" id="chk[<?php echo $x;?>]" name="chk[<?php echo $x;?>]"/>            
            <?php }else{?>
            	<input type="checkbox" id="chk" name="chk" disabled="disabled" />
            <?php }?>
            </td>           
		  </tr>		  
		  <?php }
		  }else{?>
            <tr>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td><?Php echo error_alerta("�No hay resultados que mostrar!", 1) ?></td>
              <td>&nbsp;</td>
            </tr>
     	  <?php }?>
          </tbody>
	  </table>
	</FIELDSET>
    <?php echo barra_estado($total_row);?> 
    <table width="202" height="16%" border="0" cellpadding="0" cellspacing="0" class="Azul">
      <tr>
        <td width="91">            
        <button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onclick="Muestra_Aparecer();ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_cargar=1&Con_Cod=<?Php echo $Con_Cod;?>','bgmodal')"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
        </td>
        <td width="111">
        <button name="btn_guardar" type="button" class="btn btn-primary fileinput-button" id= "btn_guardar" title= "Aceptar" onClick=" val_check('<?Php echo $x;?>','chk')" value="Actualizar"><i class="icon-ok icon-white"></i><span>&nbsp;&nbsp;Aceptar&nbsp;&nbsp;</span></button>
            <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
            <input name="hdd_Slc_Cod" type="hidden" id="hdd_Slc_Cod" value="<?php echo $Slc_Cod;?>">            
        </td>
      </tr>
    </table>
    <?php
exit();
}

/* Evitar el reenvio de formularios **/
if ($thisPost->postBlock($_POST['postID'])) 
{
	if (isset($hdd_save) && !isset($hdd_volver))
	{	
		/* Cracion del objeto mysql para las consultas */
		$obBD_ins1 =  new Class_Log_Datos_Gui;	 	 	 
		
		/*Consulta del vendedor en base al codigo de la persona*/
		$row_rs_vendedor = $obBD_con1->getRowConsulta(24,$Ses_Prs_Cod,$obBD_conexion);		
		$total_rs_vendedor=$row_rs_vendedor['Vnd_Cod'] > 0? 1 : 0;
		
		/*consulto el codigo secuencial del Tac_Cod */
		$row_rs_codigo = $obBD_con1->getRowConsulta(1069,$Tia_Cod,$obBD_conexion);		
		$total_rs_codigo=$row_rs_codigo['Gia_Sec'] > 0? 1 : 0;
		
		if ($total_rs_codigo<=0)
		{
			$codigo_aj=1;
		}
		else
		{
			$codigo_aj=$row_rs_codigo['Gia_Sec']+1;	
		}
		if($Slc_Cod=='')
		{
			$Slc_Cod='NULL';
		}
		
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		/* Registrando la cabcera de Guia   $row_rs_vendedor['Vnd_Cod'] */
		$obBD_ins1->operacionobBD(1070,$hoy.'*'.date ("H:i:s").'*'.$Gia_Det.'*'.$Gia_Obs.'*'.$Gia_Num.'*'.$codigo_aj.'*'.$Tia_Cod.'*'.$Con_Cod.'*'.$Usu_Cod.'*'.$Slc_Cod,$obBD_conexion);
		$Rcb_Cod = $obBD_ins1->insercionid($obBD_conexion->conexion);
		
		
		if($Slc_Ent!='')
		{
			/**
			*  Actualizamos el estado de la solicitud de pedido de suministros
			*/
			$obBD_ins1->operacionobBD(2217,$Slc_Ent.'*'.$Slc_Cod,$obBD_conexion);
		}
		
		foreach ($datos as $puntero => $item)
		{
		  $cant++;
		  $param[]=$item;
		  if ($cant==5)
		  {
			$cant=0;
			/* Inserta los datos en la tabla detalle de guia */
			$obBD_ins1->operacionobBD(1071,$Rcb_Cod.'*'.$param[2].'*'.$param[0].'*'.$param[3].'*'.$param[4],$obBD_conexion);	
			/* consulto si el producto es un bien */
			$row_rs_adquisicio = $obBD_con1->getRowConsulta(1037,$param[2],$obBD_conexion);			
			$total_rs_adquisicio=$row_rs_adquisicio['Adq_Cod'] > 0? 1 : 0;
			
			if ($total_rs_adquisicio<>0)
			{	
				/* consulto si el movimiento es un ingreso o egreso */
				$row_rs_transaccion = $obBD_con1->getRowConsulta(1053,$Tia_Cod,$obBD_conexion);				
				$total_rs_transaccion =$row_rs_transaccion['Tia_Cod'] > 0? 1 : 0;
				if($row_rs_transaccion['Tia_Tra']=='I')
				{	
					/**
					* REGISTRO EN EL KARDEX  SI ES UN INGRESO
					*/
					$obBD_ins1->operacionobBD(1072,'0'.'*'.'0'.'*'.'0'.'*'.'0'.'*'.$param[2].'*'.$hoy.'*'.date ("H:i:s").'*'.$param[0].'*'.'0'.'*'.'0'.'*'.$param[3].'*'.'0'.'*'.$param[4].'*'.'0'.'*'.'5'.'*'.$Rcb_Cod,$obBD_conexion);
				}
				if($row_rs_transaccion['Tia_Tra']=='E')
				{	
					/* REGISTRO EN EL KARDEX SI ES UN EGRESO */
					$obBD_ins1->operacionobBD(1072,'0'.'*'.'0'.'*'.'0'.'*'.'0'.'*'.$param[2].'*'.$hoy.'*'.date ("H:i:s").'*'.'0'.'*'.$param[0].'*'.$param[3].'*'.'0'.'*'.$param[4].'*'.'0'.'*'.'0'.'*'.'5'.'*'.$Rcb_Cod,$obBD_conexion);
				}								
			}
				
			/* Consulta el Stock */
			$row_rs_conpro = $obBD_con1->getRowConsulta(1206,$param[2],$obBD_conexion);					
			$total_rs_conpro=$row_rs_conpro['Stock'] > 0? 1 : 0;					
			$tstock= $row_rs_conpro['Stock']+$param[7];
			//echo $tstock;
			/* Actualizo el Stock */
			$obBD_ins1->operacionobBD(2201,$tstock.'*'.$param[2].'*'.$Ses_Suc_Cod,$obBD_conexion);																				
			unset($param);							
		  }
		}										
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
	}
}
if (isset($txt_busqueda))
{
	if ($op_opciones=='d')
	{
		//Cargado de los datos del personal por apellido//				
		$rs_buspro = $obBD_con1->getArrayConsulta(1079,$Ses_Emp_Cod.'*'.$txt_busqueda, $obBD_conexion);	
	}
	else{
		//Cargado de los datos del personal por cedula//	
		$rs_buspro = $obBD_con1->getArrayConsulta(2210,$Ses_Emp_Cod.'*'.$txt_busqueda,$obBD_conexion);
	}	
	$total_rs_buspro =count($rs_buspro);
}

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>		  	
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>        
		<script type="text/javascript" src="../VALIDACIONES/fac_val_guias.js"></script>        
		<link rel="stylesheet" type="text/css" href="../../Librerias/jquery/modal/css/modal.css">
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/jquery.js"></script>
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/modal.js"></script>
        
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>                
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">		
</head>
<body>
<div id="set1">
<?Php 
/** 
* Impresion automatica del comprobante de ajuste
*/
///if ($thisPost->postBlock($_POST['postID'])) 
if (isset($Rcb_Cod))
{ 
?><script type="text/javascript">windows('fac_pri_guiaremision_1.0.php?Gia_Cod=<?Php echo $Rcb_Cod;?>','', 800,600,'yes', 'yes', 'yes', 'no');</script><?php
}//Fin del if (isset($hdd_save) && !isset($hdd_volver))
?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr class="BarraTitulo">
	  <td height="10">&raquo;   entregar Suministros</td>
</tr>
<tr>
    <td align="left" valign="top">
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1"> 	
	<?php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
    </form>
	<?Php
  	//	}//Fin del if (!(isset($hdd_save))){
  	if(isset($txt_busqueda))
	{
  ?>
	<br>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de la Busqueda</label>
	</LEGEND>
		<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
		 <thead>
		  <tr>
			<th width="6%" height="27%" align="center"><strong>C&oacute;d. Int.</strong></th>
			<th width="10%"><strong>C&eacute;dula/R.U.C.</strong></th>
			<th width="78%"><strong>Personal</strong></th>
			<th width="4%">&nbsp;</th>
		  </tr>
          </thead>
          <tbody>
		  <?php  
		  if($total_rs_buspro!=0){
		  foreach($rs_buspro as $row_rs_buspro) {  ?>
		  <form name='frm_personal' method='post' action="<?php echo $_SERVER['PHP_SELF']; ?>">
		  <tr>
			<td height="73%" align="center"><?php echo $row_rs_buspro['Per_Cod']; ?></td>
			<td align="center"><?php echo $row_rs_buspro['Prs_Ced']; ?></td>
			<td align="left">&nbsp;<?php echo marcar_cadena($txt_busqueda,$row_rs_buspro['Prs_Ape'].' '.$row_rs_buspro['Prs_Nom'],'#FFFF00', 1) ?></td>
			<td width="4%" align="center">
			<input type="hidden" name="codigo" id="codigo" value="<?Php echo $row_rs_buspro['Per_Cod'];?>">
			<input type="hidden" name="Rec_Cod" id="Rec_Cod" value="<?Php echo $row_rs_buspro['Per_Cod'];?>">
			<input type="hidden" name="op_opciones" id="op_opciones" value="<?Php echo $op_opciones;?>">
			<input type="hidden" name="txt_bus" id="txt_bus" value="<?Php echo $txt_busqueda;?>">
            <input type="hidden" name="Con_Cod" id="Con_Cod" value="<?Php echo $row_rs_buspro['Con_Cod'];?>">
			<button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit();">
            <i class="icon-arrow-right icon-white"></i>
            </button>
            </td>
		  </tr>
		  </form>
		  <?php }
		  }else{?>
            <tr>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td><?Php echo error_alerta("�No hay resultados que mostrar!", 1) ?></td>
              <td>&nbsp;</td>
            </tr>
     	  <?php }?>
          </tbody>
	  </table>	 
	</FIELDSET>
  	 <?php echo barra_estado($total_rs_buspro);?>
<?php } ?>
<?php 	
if (isset($codigo) && !isset($hdd_volver))
{
		//resultado de los datos de la persona//
		$row_rs_personal = $obBD_con1->getRowConsulta(1080,$Rec_Cod,$obBD_conexion);		
		//$total_rs_personal= $obBD_con1->numregistros();	
		
		//resultado de los datos empresa//
		$row_rs_reposicion = $obBD_con1->getRowConsulta(399,$Ses_Emp_Cod,$obBD_conexion);		
		//$total_rs_reposicion= $obBD_con1->numregistros();		
		
		//resultado de las transaccion compras-inventario-baja etc */
		$row_rs_tpaj= $obBD_con1->getArrayConsulta(1050,'',$obBD_conexion);		
		//$total_rs_tpaj =  $obBD_con1->numregistros();
					 
	    /*Consulta del vendedor en base al codigo de la persona*/
		$solicitud = $obBD_con1->getRowConsulta(2214,$Con_Cod,$obBD_conexion);		
		$total_solicitud=$solicitud['Slc_Cod'] > 0? 1 : 0;		
?>
<form action="<?Php $_SERVER['PHP_SELF']?>" method="post" name="form2" id="form2">
<input name="Cja_Cod" id="Cja_Cod" type="hidden" value="<?Php echo $row_rs_reposicion['Cja_Cod'];?>">
<?Php $thisPost->startPost(); ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Personal</label>
</LEGEND>
<table width="95%" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td width="12%" class="Etiqueta1">C�dula/R.U.C.:</td>
	<td width="88%" class="LetraNegra">&nbsp;<?php echo $row_rs_personal['Prs_Ced']?>
	  <input name="Con_Cod" id="Con_Cod" type="hidden" value="<?Php echo $Con_Cod;?>">      
      <input name="Usu_Cod" id="Usu_Cod" type="hidden" value="<?Php echo $Ses_Usu_Cod;?>" /></td>
	</tr>
	<tr>	
	<td width="12%" class="Etiqueta1">Nombre:</td>
	<td class="LetraNegra">&nbsp;<?php echo $row_rs_personal['Prs_Ape'].' '.$row_rs_personal['Prs_Nom']; ?></td>
	</tr>
</table>
</FIELDSET>
	<FIELDSET>
	  <LEGEND>
		<label class="Titulos2">Generales</label>
	  </LEGEND>
		<table width="95%" border="0" cellpadding="0" cellspacing="0">
		  
		 <tr>	
		   <td width="12%" class="Etiqueta1"><!--No. Documento:--></td>
		   <td width="88%" class="LetraNegra"><label><input name="Gia_Num" type="hidden" id="Gia_Num" size="15" maxlength="15" onBlur="var formato=/^[0-9]{3}-[0-9]{3}-[0-9]{7}$/;	
	  validar_formato(this,formato,'Los n&uacute;meros de las facturas deben cumplir el siguiente formato: 999-999-9999999\nEjemplo: 001-001-0000586');"></label></td>
		 </tr>
		 <tr>
		   <td class="Etiqueta1"><span class="Asterisco">*</span> Movimiento/Tipo</td>
		   <td class="LetraNegra">
           <?php
           	/* Consultamos los tipos de movimientos activos*/
			$row = $obBD_con1->getArrayConsulta(903,'',$obBD_conexion);	
			$total_row= count($row);
		   ?>
           <select id="Tia_Cod" name="Tia_Cod">
           <?php if($total_row!=0){?>
           		<option value="">Seleccione...</option>
                <?php foreach($row as $datos){?>
                	<option value="<?php echo $datos['Tia_Cod'];?>"><?php echo $datos['Tia_Des'];?></option>
                <?php }?>
           <?php }?>     
           </select>
           </td>
		   </tr>
		 <tr>
		  <td class="Etiqueta1"><span class="Asterisco">*</span> Concepto:</td>
		  <td class="LetraNegra"><textarea name="Gia_Det" cols="80" rows="3" id="Gia_Det"></textarea></td>
		 </tr>
		 <tr>
		  <td class="Etiqueta1">Observaci&oacute;n:</td>
		  <td class="LetraNegra"><textarea name="Gia_Obs" cols="80" rows="3" id="Gia_Obs"></textarea></td>
		 </tr>	
	  </table>
	</FIELDSET>
	<FIELDSET>
	 <LEGEND>
	  <label class="Titulos2">Detalle</label>
	 </LEGEND>
	 <table width="66%" height="6%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader02">
	 <thead>
     <tr>     
		  <th width="6%">Cant.</th>
		  <th width="79%">Descripci&oacute;n</th>
		  <th width="10%">Valor </th>
		  <th width="15%">Importe</th>
		  <th width="3%">&nbsp;</th>
	 </tr>
     </thead>
     <tbody id="c_contenido">
	 </tbody>
     <tfoot>
     <tr>
          <td >&nbsp;</td>
          <td >&nbsp;</td>
          <td align="right"><strong>TOTAL:&nbsp;</strong></td>
          <td align="right"><input type="text" id="txt_total" readonly="" name="txt_total" size="6" style="text-align:right"></td>
          <td id="id_msj" align="right">&nbsp;</td>
     </tr>
     </tfoot>
	 </table>     
	 <!--<table width="71%" height="0%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="66%" height="100%" >&nbsp;</td>
		<td class="Etiqueta1" align="right" width="17%">TOTAL:&nbsp;</td>
		<td align="right" width="7%"><input type="text" id="txt_total" readonly="" name="txt_total" size="6" style="text-align:right" ></td>
		<td id="id_msj" align="right" class="Titulos2" width="10%">&nbsp;</td>
	  </tr>
	 </table>-->
	 <table width="196" border="0" cellpadding="0" cellspacing="0">
	  <tr>
	  <?php if($total_solicitud==0){?>	
        <td width="95">
		  <input id="nfilas" name="nfilas" type="hidden" value="0">		 
          <button type="button" name="button" id="button" title="Agregar Suministros" onclick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_agregar=1','bgmodal')" class="btn btn-success btn-mini" ><i class="icon-plus icon-white"></i><span>&nbsp;&nbsp;Agregar&nbsp;&nbsp;</span></button>
        </td>
        <?php }else{?>
        <td width="101">
        <input id="nfilas" name="nfilas" type="hidden" value="0">		  	 
          <button type="button" name="button1" id="button1" title="Cargar por Solicitudes" onclick="Muestra_Aparecer();ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_cargar=1&Con_Cod=<?php echo $Con_Cod;?>','bgmodal')" class="btn btn-success btn-mini" ><i class="icon-list-alt icon-white"></i><span>&nbsp;&nbsp;Solicitudes&nbsp;&nbsp;</span></button>
                   
        </td>
        <?php }?>
	  </tr>
	 </table>
     <br />	
	</FIELDSET>
	<table width="202" height="16%" border="0" cellpadding="0" cellspacing="0" class="Azul">
      <tr>
        <td width="91">        
        <button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onClick="campos_hide(this.form,'<?Php echo "txt_busqueda*op_opciones*hdd_volver";?>','<?Php echo $txt_bus.'*'.$op_opciones.'*'.$volver_op; ?>')"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
        </td>
        <td width="111"><button name="btn_guardar" type="button" class="btn btn-primary fileinput-button" id= "btn_guardar" title= "Guardar" onClick= "validar_requeridos(this.form, 'Tia_Cod*Gia_Det', 1)" value="Actualizar"><i class=" icon-book icon-white"></i><span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span></button>
            <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
            <input name="Slc_Cod" type="hidden" id="Slc_Cod" value="">
            <input name="Slc_Ent" type="hidden" id="Slc_Ent" value="<?php echo $solicitud['Slc_Ent']?>">            
        </td>
      </tr>
    </table>
</form>
	  </td>
    </tr>
  </table>
<?php } ?>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();">
</div>
<div id="bgmodal"  class="bgmodal" style="display:none"></div>
</div>
<script type="text/javascript" src="../VALIDACIONES/fac_par_guias.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script> 
</BODY>
</HTML>
<?php
	/* Cierra las conexiones */
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();	
?>