<?php 
/** 
 * Alias:	Consultar
 * Descripción: Permite consultar la baja de un activo fijo.
 * Desarrollador:	Didimo Zamora
 * **********************************
 * Fecha de actualización:	2011-04-21
 * Desarrollador: Dídimo Zamora M.
 * Fecha de actualización:	2013-05-28
 * Fecha de actualización:	2013-08-07
 * Fecha de actualización:	2013-09-27
 */
 
//Variables de Sesion estaticas 
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_activo_baj.php');	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php');	 	

	/**
	 *Configuración de inicio de pestaña posicion 1
	 */
	if(!isset($op))
	{	
		$op =1;	
	}

	/**
	 * Creacion del Objeto de conexion 
	 */
	$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
	/**
	 * Cracion del objeto mysql para las consultas 
	 */
	$obBD_con1 =  new Class_Log_Datos_Con;
	/** 
	 * Creación del objeto para evitar el reenvio 
	 */
	$thisPost = new Post_Block;
	/**
	 * Muestra modal para dar de alta el mantenimiento de activo.
	 */
?>

<?Php
$hoy = date("Y-m-d");
	
	/**
	 * Si se ha ingresado valor  la busqueda
	 */ 
	 if($txt_busqueda != "")
	 { 	 
	 	if ($op_opciones == "d")
		{			
			/**
			 * Busqueda del activo x medio de la descripcion
			 */
			$rs_buscar = $obBD_con1->getArrayConsulta(4,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);		
		}
		if ($op_opciones == "cs")
		{			
			/**
			 * Busqueda del activo x medio del codigo secuencial
			 */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(435,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);					
		}
		if ($op_opciones == "cb")
		{			
			/**
			 * Busqueda del activo x medio del codigo de barra
			 */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(436,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);		
		}	
		if ($op_opciones == "ns")
		{			
			if (isset($Cam_Cod)){
			 /**
			  * Busqueda del activo x medio del  código del campo
			  */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(471,$Cam_Cod.'*'.$txt_busqueda, $obBD_conexion);		
			}
		}			
		$total_rs_buscar = count($rs_buscar);	
	 }else
		{
		/** 
		 * Consulta realizada en base al código seleccionado 
		 */
			if (isset($codigo))
			{
				$rs_consultar = $obBD_con1->getRowConsulta(431, $codigo, $obBD_conexion);
				$row_rs_consultar = $rs_consultar;
				$total_rs_consultar =count($codigo);
			}		
		}
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
			<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
            <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
            <script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
            <script type="text/javascript" src="../VALIDACIONES/act_val_activo_baj.js"></script>
            <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>            
            <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>             
            <script type="text/javascript"> 
			  $(function() {
					$('#set1 *').tooltip({showURL: false});
			  });              			
			</script>   
            <script>
				$(function() { 
					 $( "#Fec1" ).datepicker({
					  changeMonth:true, 
					  changeYear:true, 
					  dateFormat: "yy-mm-dd",
					 });
					 });
			</script>
            <script>
				$(function() { 
					 $( "#Fec2" ).datepicker({
					  changeMonth:true, 
					  changeYear:true, 
					  dateFormat: "yy-mm-dd",
					 });
					 });
			</script>                                                                           
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr class="BarraTitulo">
			<td colspan="3" height="10">&raquo;Consultar Baja de Activo Fijo</td>
		</tr>
		<tr>
			<td valign="top">      
			<?
				$descripcion = "Individual*Por Motivo de baja*Por Fecha";
				$pag1= $_SERVER['PHP_SELF']."?op=1";
				$pag2= $_SERVER['PHP_SELF']."?op=2";
				$pag3= $_SERVER['PHP_SELF']."?op=3";
				tabs(3,$descripcion, $pag1.'*'.$pag2.'*'.$pag3, $op);
			?>   
		<?
		if(!isset($op)){$op = 1;}
			if ($op==1 || $op==2 || $op==3 ){
			switch($op){			
				case 1: 
		?>  
          
		<fieldset>
		<LEGEND>
			<label class="Titulos2">Buscar activo por:&nbsp;</label>
		</LEGEND>
        
		<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']."?op=1"?>">
        <?Php
         //Creacion del campo REPOST
		$thisPost->startPost();
		?>
		<table width="762" height="72" border="0" cellpadding="0" cellspacing="0">
		<tr>    
			<td colspan="3">
			<table width="633" border="0">
				<tr>
					<td width="105"><input name="op_opciones" type="radio" value="d"  checked  onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">              
                    <span class="LetraNegra">Descripción</span> <input name="op_cam" id="op_cam" type="hidden" value="d"></td>
					<td width="125"><input type="radio" name="op_opciones" value="cb" <?Php if($op_opciones== 'cb'){?> checked <? } ?> onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">
                    <span class="LetraNegra">Código de Barra</span></td>
					<td width="122"><input type="radio" name="op_opciones" value="cs" <?Php if($op_opciones== 'cs'){?> checked <? } ?> onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);" >
                    <span class="LetraNegra">Código Secuencial</span></td>
                    <td width="263"><input type="radio" name="op_opciones" value="ns" <?Php if($op_opciones== 'ns'){  ?> checked <? } ?> onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.Cam_Cod);">
                    <span class="LetraNegra">Por Campo</span>
<?Php
					/**
					 * consulto los campos que esten definidos como busqueda 
					 */
					$rs_campos=$obBD_con1->getArrayConsulta(470, '', $obBD_conexion);
?>
                    <select name="Cam_Cod" id="Cam_Cod" onChange="setfocus(this.form.txt_busqueda);">
<?Php 
						foreach($rs_campos as $row_rs_campos){
?>  
							<option  value="<? echo $row_rs_campos['Cam_Cod'];?>"><?PHP  echo $row_rs_campos['Cam_Cor'];?></option>
<?Php 
						} //fin foreach($rs_campos as $row_rs_campos){
?> 
                    </select>
					</td>
				</tr>             
			</table>
			</td>
		</tr>
		<tr>
			<td width="119" height="44"class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Activo: </div></td>
			<td width="368"  class="BarraBusqueda">&nbsp;<input size="50" name="txt_busqueda" type="text" id="txt_busqueda">
			</td>
			<td align="left" width="144" class="BarraBusqueda">
				<div align="left">
				<button name="btn_aceptar" type="submit" class="btn btn-success fileinput-button" id="btn_aceptar" value="Aceptar" title="Listar Activos">
				<i class="icon-search icon-white"></i>
				<span>&nbsp;Buscar&nbsp;</span>
				</button>
				</div>
			</td>
			<td width="131"></td>
		</tr>
		</table>
		<script> 
			ShowHide('Cam_Cod');
		</script>
		</form>
		</fieldset>  
 
		<? 
		if (isset($txt_busqueda)){?>
			<FIELDSET>
			<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
			</LEGEND>
				<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
				<thead>
				<tr>
					<th width="5%">Cód. Int.</th>
					<th width="35">SubGrupo</th>
					<th width="40">Descripci&oacute;n </th>
					<th width="20">Secuencial</th>
					<th>&nbsp;</th>
				</tr>
				</thead>
				<tbody>
					<?Php 
					$anulada=0;
					if ($total_rs_buscar > 0){  
						foreach($rs_buscar as $row_rs_buscar){   	
							
					?>
				<tr>
					<td align="center"><?php echo $row_rs_buscar['Act_Cod'];?></td>
					<td title="<? echo $row_rs_buscar['Tia_Des'];?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Tia_Des'],'#FFFF00', 1);?></td>
					<td  title="<? echo $row_rs_buscar['Act_Des'];?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Act_Des'],'#FFFF00', 1);?></td>
					<td align="center"><?php echo  $row_rs_buscar['Act_Cdc'];?></td>
					<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "frml" id="forml">
					<td align="center" width="5%">

					<button type="button" name="imageField"  class="btn btn-success btn-mini"  width="22" height="22" title="Seleccionar" onClick="this.form.submit()">	
						<i class="icon-arrow-right icon-white"></i>
					</button>  				
					<input type="hidden" name="codigo" id="codigo" value="<?Php echo $row_rs_buscar['Act_Cod'];?>"/>
					<input type="hidden" name="hdd_aux" id="hdd_aux" value="1">
					<input type="hidden" name="volver_busqueda" id="volver_busqueda" value="<?Php echo $txt_busqueda;?>"/>
					<input type="hidden" name="volver_opciones" id="volver_opciones" value="<? echo $op_opciones;?>">
					<input type="hidden" name="volver_Cam_Cod" id="volver_Cam_Cod" value="<? echo $Cam_Cod;?>">	     
					</td>
				</form>
				</tr>
<?Php 
			} //Fin foreach($row_rs_buscar as $row_rs_buscar){      
		}else{
?>
		<tr>
			<td> </td>
			<td> </td>
			<td align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
			<td> </td>
			<td> </td>
		</tr>
		<?
		} // fin del if ($total_rs_buscar > 0)
		?>
		</tbody>
		</table>
		<?Php
		/**
		 * Muestra la barra de estados con la cantidad de registros encontrados 
		 */
		echo barra_estado($total_rs_buscar+0);
		?>
		</FIELDSET>
		<?
		}//if (isset($txt_busqueda)){
		?>
<br/>
		<?Php  

		if ($hdd_aux==1){ 
			 /**
			  * Consultar los activos dados de baja.
			  */
				
			$rs_bajas = $obBD_con1->getRowConsulta(5,$codigo.'*'.$Ses_Emp_Cod,$obBD_conexion);
		?>
		<form method="post" name= "form2" action="<? echo $_SERVER['PHP_SELF'];?>">
		<? //Creacion del campo REPOST
			$thisPost->startPost();
		?>
		<fieldset>
			<LEGEND>
				<label class="Titulos2">Datos del Activo</label>
			</LEGEND>
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="3"> </td>
				</tr>
				<tr>
					<td width="20%" class="Etiqueta1"> Código Activo:</td>
					<td class="LetraNegra">&nbsp;<?php echo $row_rs_consultar["Act_Cdc"]?>
					<input id="Act_Cod" name="Act_Cod" type="hidden" value="<?Php echo $codigo;?>" ></td>
					<td></td>
				</tr>
				<tr>
					<td width="20%" class="Etiqueta1"> Descripción:</td>
					<td class="LetraNegra">&nbsp;<?php echo $row_rs_consultar["Act_Des"]?></td>
					<td></td>
				</tr>
				<tr>
					<td width="20%" class="Etiqueta1"> Tipo de Activo:</td>
					<td> <label class="Titulos2">&nbsp;<?php echo $row_rs_consultar["Tia_Des"];?></label></td>
					<td></td>
				</tr>
			</table>
 
		</fieldset> 
 
		<fieldset>
			<LEGEND>
				<label class="Titulos2">Datos de la Baja del Activo</label>
			</LEGEND>
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="3"> </td>
				</tr>
				<tr>
					<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Motivo:</td>
					<td width="80%" class="LetraNegra">&nbsp;<? echo $rs_bajas['Est_Des'];?>      
					</td>
					<td width="0%"></td>
				</tr>
				<tr>
					<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Fecha del Informe:</td>
					<td class="LetraNegra">&nbsp;<? echo $rs_bajas['Baj_Fba'];?> </td>
					<td></td>
				</tr>       
				<tr>
					<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Informe Técnico:</td>
					<td class="LetraNegra">&nbsp;<div align="justify"> <? echo $rs_bajas['Baj_Inf'];?> </div></td>
					<td></td>
				</tr>       
			</table>
		</fieldset>
		
		<fieldset>
			<LEGEND>
				<label class="Titulos2">Destino del activo dado de baja</label>
			</LEGEND>
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="3"></td>
				</tr>
				<tr>
					<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Destino:</td>
					<td width="80%" class="LetraNegra">&nbsp;<? echo $rs_bajas['Baj_Des'];?>
						
					</td>			
				</tr>
				<tr>
					<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Receptor:</td>
					<td class="LetraNegra">&nbsp;<? echo $rs_bajas['Baj_Qui'];?></td>			
				</tr>
				<tr>
					<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Valor:</td>
					<td class="LetraNegra">&nbsp;<? echo $rs_bajas['Baj_Val'];?></td>			
				</tr>
			</table>
		</fieldset>
		
		<input id="hdd_save" name="hdd_save" type="hidden" value="1">
		<input id="destino" name="destino" type="hidden" value="">
		</form>
		
		<table  width="220">
			<tr>
				<td width="49%" align="left">   
					<form method="post" name= "form3" action="<? echo $_SERVER['PHP_SELF'];?>">	
							<input id="hdd_volver" name="hdd_volver" type="hidden" value="0">                         
						<button type="button" name="btn_atras" id="btn_atras" value="Enviar" class="btn btn-inverse fileinput-button" title="Atr&aacute;s"
						onClick="campos_hide(form2, '<?Php echo "op_opciones*txt_busqueda*hdd_volver"; ?>','<?Php echo $volver_opciones.'*'.$volver_busqueda.'*'.$hdd_volver;?>')">
						 <i class="icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;&nbsp;</span>
						</button>
					</form>                            
				</td>            
				<td width="51%" >               
					<form name="form11" method="post" action="<? echo 'act_pri_baja_activo_1.0.php';?>" target="_blank">          
							<button title="Imprimir Baja de Activo" name="btn_imprimir" id="btn_imprimir" type="submit" class="btn btn-primary start" value="Selec">
							<i class="icon-print icon-white"></i>
								<span>&nbsp;Imprimir&nbsp;</span>
							</button>
							<input type="hidden" id="Baj_Cod_Aux" name="Man_Cod_Aux" value="<?Php echo $codigo;?>">
							<input type="hidden" id="Act_Sec_Aux" name="Man_Cod_Aux" value="<?Php echo $codigo;?>">
							<input type="hidden" id="Act_Des_Aux" name="Man_Cod_Aux" value="<?Php echo $codigo;?>">
							<input type="hidden" id="Man_Cod_Aux" name="Man_Cod_Aux" value="<?Php echo $codigo;?>">
					</form>                          
				</td>                        
			</tr> 
		</table> 
 <?
 }
 break;
 //Case para buscar por motivo de baja.
 case 2:
 	//Consultar los estados 
	$rs_motivoBaj=$obBD_con1->getArrayConsulta(423,"",$obBD_conexion);
?>	
		<FIELDSET>
				<LEGEND>
					<label class="Titulos2">Buscar activo por:&nbsp;</label>
				</LEGEND>
				<form method="post" name= "form11" action="<?Php echo $_SERVER['PHP_SELF']."?op=2"?>">	
					<table width='100%' border='0' cellpadding='0' cellspacing='0'>
					<tr>
						<td colspan='4'> </td>
					</tr>
					<tr>
						<td width='6%' height="38" class='BarraBusqueda'>&nbsp;</td>
						<td width='4%'  class='BarraBusqueda'><span class='Asterisco'>*</span>Motivo:</td>
						<td width='10%' class='BarraBusqueda'>
						<select name="Baj_Mot" id="Baj_Mot">
							<?Php
								foreach($rs_motivoBaj as $row_rs_motivoBaj){ 
							?>
									<option  value="<?php echo $row_rs_motivoBaj['Est_Cod'];?>"> <?Php echo $row_rs_motivoBaj['Est_Des'];?> </option>
							 <?Php       
								} //fin foreach($rs_campos as $row_rs_campos){ 
							?>		
						</select>
       		
						</td>
						<td width="20%" class="BarraBusqueda">
							  <div align="left">
								<button name="btn_motivo" type="submit" class="btn btn-success fileinput-button" id="btn_motivo" value="Aceptar" title="Listar Activos">
									<i class="icon-search icon-white"></i>
									<span>&nbsp;Buscar&nbsp;</span>
								</button>
							  </div> 
						</td>
						<td width='60%'></td>
					</tr>
					</table>
				</form>   
		</FIELDSET>
<?Php	
if (isset($btn_motivo)){
	
	/**
	 * Busqueda del activo x medio de la descripcion
	 */
	
	$rs_bus_Motivo = $obBD_con1->getArrayConsulta(7,$Baj_Mot.'*'.$Ses_Emp_Cod, $obBD_conexion);	
		
	?>
	<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
		<thead>
		<tr>
			<th width="7%">Cód. Int.</th>
			<th width="30%">Descripci&oacute;n</th>
			<th width="15%">Secuencial</th>
            <th width="10%">Fecha</th>
            <th width="20%">Destino</th>
            <th width="20%">A quien?</th>
            <th width="8%">Valor</th>			
		</tr>
		</thead>
		<tbody>
<?Php 
		$anulada=0;
		if (count($rs_bus_Motivo) > 0){  
			foreach($rs_bus_Motivo as $row_rs_Mot){   					
?>
		<tr>
			<td align="center"><?php echo $row_rs_Mot['Baj_Cod'];?></td>
			<td align="left"><?Php echo $row_rs_Mot['Act_Des'];?></td>
			<td align="left"><?php echo  $row_rs_Mot['Act_Cdc'];?></td>						
            <td align="left"><?php echo $row_rs_Mot['Baj_Fba'];?></td>
            <td align="left" ><?Php echo $row_rs_Mot['Baj_Des'];?> </td>
            <td align="left" ><?Php echo $row_rs_Mot['Baj_Qui'];?> </td>
            <td align="left" ><?Php echo $row_rs_Mot['Baj_Val'];?> </td>			
			</tr>
<?Php 
			} //Fin foreach($row_rs_buscar as $row_rs_buscar){      
		}else{
	?>
			<tr>
				<td> </td>
				<td><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
				<td align="center">&nbsp;</td>
				<td> </td>
				<td> </td>
				<td> </td>
				<td> </td>
			</tr>
	<?
		} // fin del if ($total_rs_buscar > 0)
	?>
			</tbody>
			</table>
	<?Php
	/**
	 * Muestra la barra de estados con la cantidad de registros encontrados 
	 */
	echo barra_estado(count($rs_bus_Motivo)+0);
	?>
	</FIELDSET>
    <?Php 
	
	if (count($rs_bus_Motivo) > 0){  
	?>
        <table  width="146">
        <tr>
                     
            <td width="51%" >               
                <form name="form11" method="post" action="<? echo 'act_pri_baja_act_general_1.0.php';?>" target="_blank">          
                        <button title="Imprimir Baja de Activo" name="btn_imprimir" id="btn_imprimir" type="submit" class="btn btn-primary start" value="Selec">
                        <i class="icon-print icon-white"></i>
                            <span>&nbsp;Imprimir&nbsp;</span>
                        </button>
                      <input type="hidden" id="Baj_Mot_aux" name="Baj_Mot_aux" value="<?Php echo $Baj_Mot;?>">
                      <input type="hidden" id="Op_aux" name="Op_aux" value="<?Php echo $op;?>">
                      
               </form>                          
            </td>                        
        </tr> 
	</table> 
  <?      
    }   	
}		
 break;
 case 3:	
	if(!isset($Fec1)){
				$Fec1= date("Y")."-".date("m")."-01";
				$Fec2= date("Y")."-".date("m")."-".date("d");
				}							
				if(isset($ok)){						
				/**
				 * Consultar los activos dados de baja
				 */
				$rs_bus_Motivo = $obBD_con1->getArrayConsulta(8,$Fec1.'*'.$Fec2.'*'.$Ses_Emp_Cod, $obBD_conexion);				
				}			
?>	
				<FIELDSET>
				<LEGEND>
					<label class="Titulos2">Buscar por fecha:&nbsp;</label>
				</LEGEND>
					<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
						<table width="100%" border="0">
								<tr>
									<td width="155"><span class="LetraNegra">Fecha Inicio:</span> <input name="Fec1" type="text" id="Fec1" value="<?Php echo $Fec1;?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
                                </td>
                                <td width="142"><span class="LetraNegra">Fecha Fin:</span><input name="Fec2" type="text" id="Fec2" value="<?Php echo $Fec2;?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
                                </td>
								<td width="547">
                                    <button name="btn_aceptar" class="btn btn-success fileinput-button" id="btn_aceptar" title="Mostrar Controles de Tenencia de Activos Fijos" onClick="validar_requeridos(this.form,'$txt_busqueda',0)">
                                    <i class="icon-search icon-white"></i>
                                    <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>   
                                    </button>
									<input name="ok" id="ok" type="hidden" value="0">
                                    <input name="op" id="op" type="hidden" value="<?Php echo $op;?>">
								</td>
							</tr>
						</table>                
					</form>
				</FIELDSET>	
				
				<FIELDSET>
				<LEGEND>
				<label class="Titulos2">Resultados de la busqueda</label>
				</LEGEND> 
					   <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
						<thead>
						<tr>
							<th width="7%">Cód. Int.</th>
							<th width="30%">Descripci&oacute;n</th>
							<th width="15%">Secuencial</th>
							<th width="10%">Fecha</th>
							<th width="20%">Destino</th>
							<th width="20%">A quien?</th>
							<th width="8%">Valor</th>			
						</tr>
						</thead>
						<tbody>
				<?Php 
				$anulada=0;
		if (count($rs_bus_Motivo) > 0){  
			foreach($rs_bus_Motivo as $row_rs_Mot){   					
		?>
		<tr>
			<td align="center"><?php echo $row_rs_Mot['Baj_Cod'];?></td>
			<td align="left"><?Php echo $row_rs_Mot['Act_Des'];?></td>
			<td align="left"><?php echo  $row_rs_Mot['Act_Cdc'];?></td>						
            <td align="left"><?php echo $row_rs_Mot['Baj_Fba'];?></td>
            <td align="left" ><?Php echo $row_rs_Mot['Baj_Des'];?> </td>
            <td align="left" ><?Php echo $row_rs_Mot['Baj_Qui'];?> </td>
            <td align="left" ><?Php echo $row_rs_Mot['Baj_Val'];?> </td>			
			</tr>
			<?Php 
			} //Fin foreach($row_rs_buscar as $row_rs_buscar){      
		}else{
		?>
			<tr>
				<td> </td>
				<td><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
				<td align="center">&nbsp;</td>
				<td> </td>
				<td> </td>
				<td> </td>
				<td> </td>
			</tr>
		<?
		} // fin del if ($total_rs_buscar > 0)
		?>
			</tbody>
			</table>                    
          </FIELDSET>
		<?Php
			/**
			* Muestra la barra de estados con la cantidad de registros encontrados 
			*/
			echo barra_estado(count($rs_bus_Motivo)+0);
	//Si hay resulados  boton imprimir
	if (count($rs_bus_Motivo) > 0){  
	?>   
    <table  width="145">
        <tr>            
            <td width="51%" >               
                <form name="form11" method="post" action="<? echo 'act_pri_baja_act_general_1.0.php';?>" target="_blank">          
                        <button title="Imprimir Baja de Activo" name="btn_imprimir" id="btn_imprimir" type="submit" class="btn btn-primary start" value="Selec">
                        <i class="icon-print icon-white"></i>
                            <span>&nbsp;Imprimir&nbsp;</span>
                        </button>
                        <input type="hidden" id="Op_aux" name="Op_aux" value="<?Php echo $op;?>">
                        <input type="hidden" id="Fec1_aux" name="Fec1_aux" value="<?Php echo $Fec1;?>">
                        <input type="hidden" id="Fec2_aux" name="Fec2_aux" value="<?Php echo $Fec2;?>">                       
				</form>                          
            </td>                        
        </tr> 
	</table>     
	<?Php
	}
 break;
					}// fin switch($op)
			}//fin if ($op==1 || $op==2 || $op==3 )
 ?>
 
</table>
</div>
<script type="text/javascript" src="../VALIDACIONES/act_par_activo_baj.js"></script>
    <script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY>
</HTML>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>