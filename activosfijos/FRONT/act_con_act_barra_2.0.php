<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?	
/**
* Descripción: Reporte de codigo de barras de Activos
* Fecha de actualización:	2011-08-02
* Fecha de actualización:	2013-05-028
* Desarrollador:	Zamora Didimo
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_campos_det.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Creacion del Objeto de conexion 
 */  
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
 * Creacion del Objeto de datos 
 */  
$obBD_con1 =  new Class_Log_Datos_Con; 

 

if(!isset($op))
{
	
	$op =1;	
}
	/**
	 * Consulta el detalle de los departamentos 
	 */
	$rs_depar = $obBD_con1->getArrayConsulta(438,'', $obBD_conexion);
	$total_rs_depar = count($rs_depar);	
	/** 
	 * Consulta los Estados 
	 */
	$rs_est_act = $obBD_con1->getArrayConsulta(423,'', $obBD_conexion);
	$total_rs_est_act =  count($rs_est_act);

/**
 * Al momento de cargar la pagina consulto si senvia una cadena o no 
 */
if(!isset($_POST['codigo']))
{
	$cadena=$cadena;
}
else
{
	$cadena=$codigo;
}

/**
 * cantidad de numero de registro que quiere que se carguen 
 */
//$registros = 42;
$registros = 25;
/**
 * pregunto si existe la variable Pagina 
 */
if (!$pagina) { 
    $inicio = 0; 
    $pagina = 1; 

} 
else 
{ 
    $inicio = ($pagina - 1) * $registros; 
	
} 

if($txt_busqueda != "")
{ 
	
	 	if ($op_opciones == 1)
		{
			/**
			 * Busqueda de los scustodios x el Apellido
			 */
			$rs_buscar = $obBD_con1->getArrayConsulta(639,$txt_busqueda,$obBD_conexion);					
		}
		if ($op_opciones == 2)
		{
			/**
			 * Busqueda de los scustodios x la Cedula
			 */	
			$rs_buscar = $obBD_con1->getArrayConsulta(640,$txt_busqueda, $obBD_conexion);
		}
			$total_rs_buscar = count($rs_buscar);
	}	

	if ($op_opciones == "d")
		{		
			if($txt_busqueda2 != "")
				{ 				
					/**
					 * consulto todos los registros
					 */
					$rs_ite_pro1= $obBD_con1->getArrayConsulta(1002,trim($txt_busqueda2).'*'.$Ses_Emp_Cod, $obBD_conexion);
					$total_rs_ite_pro1 =  count($rs_ite_pro1);
					/** 
					 * consulto todos los registros paginados
					 */
					$rs_ite_pro= $obBD_con1->getArrayConsulta(1003,trim($txt_busqueda2).'*'.$inicio.'*'.$registros.'*'.$Ses_Emp_Cod, $obBD_conexion);
					$total_rs_ite_pro =  count($rs_ite_pro);
					
					$resultados = $rs_ite_pro;
					$total_registros = $total_rs_ite_pro1; 
					$total_paginas = ceil($total_registros / $registros);
			}	
			
		}
		
	if ($op_opciones == "cs")
		{		
			if($txt_busqueda2 != "")
				{ 				
					/**
					 * consulto todos los registros
					 */
					$rs_ite_pro1= $obBD_con1->getArrayConsulta(1008,trim($txt_busqueda2).'*'.$Ses_Emp_Cod, $obBD_conexion);
					$total_rs_ite_pro1 =  count($rs_ite_pro1);
					/** 
					 * consulto todos los registros paginados
					 */
					$rs_ite_pro= $obBD_con1->getArrayConsulta(1009,trim($txt_busqueda2).'*'.$inicio.'*'.$registros.'*'.$Ses_Emp_Cod, $obBD_conexion);
					$total_rs_ite_pro =  count($rs_ite_pro);
					
					$resultados = $rs_ite_pro;
					$total_registros = $total_rs_ite_pro1; 
					$total_paginas = ceil($total_registros / $registros);
			}	
			
		}
		
	 if ($op_opciones == "ta")
		{
			/**
			 * consulto todos los registros
			 */
			$rs_ite_pro1= $obBD_con1->getArrayConsulta(1004,$Tia_Cod, $obBD_conexion);
			$total_rs_ite_pro1 =  count($rs_ite_pro1);
			/** 
			 * consulto todos los registros paginados
			 */
			$rs_ite_pro= $obBD_con1->getArrayConsulta(1005,$Tia_Cod.'*'.$inicio.'*'.$registros, $obBD_conexion);
			$total_rs_ite_pro =  count($rs_ite_pro);
			
			$resultados = $rs_ite_pro;
			$total_registros = $total_rs_ite_pro1; 
			$total_paginas = ceil($total_registros / $registros);			
		}

if (isset($cadena))
{
	echo "HOLAAAA";
	/**
	 * consulto todos los registros
	 */
	$rs_ite_pro1= $obBD_con1->getArrayConsulta(637,$cadena, $obBD_conexion);
	$total_rs_ite_pro1 =  count($rs_ite_pro1);
	/** 
	 * consulto todos los registros paginados
	 */
	$rs_ite_pro= $obBD_con1->getArrayConsulta(638,$cadena.'*'.$inicio.'*'.$registros, $obBD_conexion);
	$total_rs_ite_pro =  count($rs_ite_pro);
	
	$resultados = $rs_ite_pro;
	$total_registros = $total_rs_ite_pro1; 
	$total_paginas = ceil($total_registros / $registros); 	
}
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script language="javascript" src="../VALIDACIONES/act_val_campos_det.js"></script> 
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script> 
        <script type="text/javascript">
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>


<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">Consultar C&oacute;digo de barras</td>
  </tr>
	<tr>
      <td height="389" align="left" valign="top">
      
      <?
		$descripcion = "Por Custodio*Por Activo";
  		$pag1= $_SERVER['PHP_SELF']."?op=1";
		$pag2= $_SERVER['PHP_SELF']."?op=2";
		tabs(2,$descripcion, $pag1.'*'.$pag2, $op);
	?>
     
     <?
	if(!isset($op)){$op = 1;}
	
		if (($op==1 || $op==2)) {
		switch($op){
		case 2: 			
	?>    
  <fieldset>
  <LEGEND>
    <label class="Titulos2">Buscar por:&nbsp;</label>
  </LEGEND>
   <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']."?op=2"?>">
    
   
   
   
   <table width="945" height="69" border="0" cellpadding="0" cellspacing="0">
    <tr>
		<td colspan="2">
		<table width="842" border="0">                               
              <tr>
                
                <td width="105">
                	<input name="op_opciones" type="radio" value="d" <?Php if($op_opciones=="d" || !isset($op_opciones)){ ?> checked <?Php }?>  onClick="document.getElementById('op_cam').value=this.value;setfocus(this.form.txt_busqueda2);document.getElementById('Tia_Cod').disabled=true;document.getElementById('txt_busqueda2').disabled=false">
                    <span class="LetraNegra"></span>
                    	<input class="oculta" name="op_cam" id="op_cam" type="hidden" value="d">
                <span class="LetraNegra">Descripción</span></td>
                
                <td width="153" class="LetraNegra">
                	<input  type="radio" name="op_opciones" value="cs" <?Php if($op_opciones=="cs"){ ?> checked <?Php }?> onClick="document.getElementById('op_cam').value=this.value;setfocus(this.form.txt_busqueda2);document.getElementById('Tia_Cod').disabled=true;document.getElementById('txt_busqueda2').disabled=false">
                    <span class="LetraNegra"></span>
                <span class="LetraNegra">Código secuencial</span>
                </td>
                
                <td width="98" class="LetraNegra"><input  type="radio" name="op_opciones" value="ta" <?Php if($op_opciones=="ta"){?> checked <?Php }?> onClick="document.getElementById('op_cam').value=this.value;setfocus(this.form.Tia_Cod);document.getElementById('Tia_Cod').disabled=false;document.getElementById('txt_busqueda2').disabled=true">
                    <span class="LetraNegra">Tipo Activo</span>
                
                </td>
                <td width="468" colspan="2"><input name="op_cam" id="op_cam" type="hidden" value="d">
                <?Php
					$row_tipo_activo = $obBD_con1->getArrayConsulta(665, $Ses_Emp_Cod, $obBD_conexion);				
				?>
                <select disabled name="Tia_Cod" id="Tia_Cod">
                    <?php
                    foreach($row_tipo_activo as $row)
                    {
                    ?>
                        <option <?Php if ($row['Tia_Cod']==$Tia_Cod){ echo "selected"; }?> value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des']; ?></option>
                    <?Php
                    }
                    ?>
                </select>               
                </td>
                
                
              </tr>    
             
            </table>
		</td>
	</tr>
	<tr>
      <td width="89" height="39"class="BarraBusqueda"><div align="left"><span class="Asterisco">*</span> Activo: </div></td>
      <td width="800" class="BarraBusqueda">&nbsp;
      <input size="50" name="txt_busqueda2" type="text" id="txt_busqueda2">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<button name="btn_aceptar" type="submit" class="btn btn-success fileinput-button" id="btn_aceptar" value="Aceptar" title="Aceptar" onClick="validar_requerido_opcion(this.form);">
        <i class="icon-ok icon-white"></i>
  		<span>&nbsp;&nbsp;Aceptar&nbsp;&nbsp;</span>
        </button>     
	  </td>
      <td width="56"></td>
    </tr>
  </table>
  </form>
 </fieldset>	
 <?Php
/**
 * mostrar por activo
 */
if(isset($txt_busqueda2) || isset($Tia_Cod))
{	
	if($total_registros) 
	{	
	/**
	 * Indicador que me permitir crear filas
	 */
	  $indicadorfila=0;
	?>
	<table width="200" border="1" cellspacing="5" >
    <tr>
		<? 
        foreach($rs_ite_pro as $row_rs_ite_pro)
		{ 		
		/**
		 * Consulto el departamento del custodio.
		 */
		 $rs_DeparCust=$obBD_con1->getRowConsulta(1000,$row_rs_ite_pro['Cus_Cod'],$obBD_conexion);
		 $rs_totolDep=count($rs_DeparCust);
		?>
          <td>
                   <table width="192" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td colspan="2" align="center"><?  $varcode=$row_rs_ite_pro['Act_Bar']; include("../../Librerias/barcode/generadorbarras.php"); ?></td>
                        </tr>
                        <tr >
                            <td width="76" class="Etiqueta1"><div align="left">Departamento:</div></td>
                            <td width="116" class="LetraNegra">&nbsp;<? echo $rs_DeparCust['Dep_Des'];?></td>
                        </tr>
                            <td width="76" class="Etiqueta1"><div align="left">Activo:</div></td>
                            <td width="116" class="LetraNegra">&nbsp;<? echo $row_rs_ite_pro['Act_Des']; ?></td>
                        </tr>
                        <tr>
                            <td width="76" class="Etiqueta1"><div align="left">Secuencial:</div></td>
                            <td width="116" class="LetraNegra">&nbsp;<? echo $row_rs_ite_pro['Act_Sec']; ?></td>
                        </tr>
                  </table>
          </td>
       <? 
		  if ($indicadorfila==5)
		  {
		   	echo "</tr>";
		   	echo "<tr>";
		  	$indicadorfila=0;
		  }else{
		    $indicadorfila=$indicadorfila+1;
		  }
	  
		}//foreach($rs_ite_pro as $row_rs_ite_pro)
		?>
       </tr>
      </table>
      <br>
		<?
		echo "<center>";	
		if(($pagina - 1) > 0) 
		{
			echo "<a href='".$_SERVER['PHP_SELF']."?pagina=".($pagina-1)."&&txt_busqueda2=".$txt_busqueda2."&&op=2&&op_opciones=".$op_opciones."'>< Anterior</a> ";
		}
		for ($i=1; $i<=$total_paginas; $i++){ 
			if ($pagina == $i) {
				echo "<b>".$pagina."</b> "; 
			} else {
				echo "<a href='".$_SERVER['PHP_SELF']."?pagina=$i&&txt_busqueda2=".$txt_busqueda2."&&op=2&&op_opciones=".$op_opciones."'>$i</a> "; 
			}	
		}
		if(($pagina + 1)<=$total_paginas) {
			echo " <a href='".$_SERVER['PHP_SELF']."?pagina=".($pagina+1)."&&txt_busqueda2=".$txt_busqueda2."&&op=2&&op_opciones=".$op_opciones."'>Siguiente ></a>";
		}
		echo "</center>";
	}//Fin if($total_registros)
	else 
	{
		echo "<br>";
		echo error_alerta("No se encontraron registros", 1);
	}?> 
	<table width="314" border="0" cellpadding="0" cellspacing="0">
    <tr>
  		<td width="204"><? if($total_rs_ite_pro1 > 0){
	  ?>
    <form name="form4" method="post" action="act_pri_act_barra_2.0.php" target="_blank">  
      <input name="inicio" type="hidden" id="inicio" value="<? echo $inicio; ?>">
      <input name="registros" type="hidden" id="registros" value="<? echo $registros; ?>">
      <input name="txt_busqueda2" type="hidden" id="txt_busqueda2" value="<? echo $txt_busqueda2;?>">
      <input name="Tia_Cod" type="hidden" id="Tia_Cod" value="<? echo $Tia_Cod; ?>">    
       <input name="tipo" type="hidden" id="tipo" value="0"> 
       <input name="op_opciones" type="hidden" id="op_opciones" value="<? echo $op_opciones; ?>">
      <button name="bnt_print" type="submit" class="btn btn-primary start" id="bnt_print" value="Imprimir" title="Imprimir">
        <i class='icon-print icon-white'></i> <span>Imprimir</span>
        </button>
    </form> <? }?>
    	</td>
    </tr>
  	</table>
	<? 
	} //Fin if(isset($cadena))
  	break;
/**
 * Case para consulta  por Custodio de etiquetas
 */
  	case 1: 	
  	?>
  	<form action="<?Php echo $_SERVER['PHP_SELF']."?op=1"?>" method="post" name= "form2">
      <LEGEND>
        <label class="Titulos2">Buscar por:&nbsp;</label>
      </LEGEND>
 
       <table width="612" height="36" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td colspan="2">
            <table width="414" border="0">
                  <tr>
                    <td width="205"><input name="op_opciones" type="radio" value="1" onClick="setfocus(this.form.txt_busqueda)" checked >
                        <span class="LetraNegra">Apellidos</span></td>
                    <td width="199"><input type="radio" name="op_opciones" value="2" onClick="setfocus(this.form.txt_busqueda)">
                        <span class="LetraNegra">Cédula</span></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr class="BarraBusqueda">
          <td width="68" height="32"><span class="Asterisco">*</span> Custodio:</td>
          <td width="358">&nbsp;<input name="txt_busqueda" type="text" id="txt_busqueda" size="50">
          </td>
          <td width="186">
            <button name="btn_aceptar" type="submit" class="btn btn-success fileinput-button" id="btn_aceptar" value="Aceptar" title="Aceptar">
            <i class="icon-ok icon-white"></i>
                <span>&nbsp;&nbsp;Aceptar&nbsp;&nbsp;</span>
            </button>
            <input name="hdd_bus" type="hidden" id="hdd_bus" value="1">
          </td>
        </tr>
      </table>
  	</form>	
  
 <? 
/**
 * Si buscar por custodio 
 */ 
if (isset($txt_busqueda))
{
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table class="fixedHeader01" width="100%"  cellpadding="0" cellspacing="0">
     <thead>
	  <tr>
		  <th width="11%">Cód. Int.</th>
          <th width="18%">C&eacute;dula</th>
		  <th width="67%">Custodio</th>
		  <th width="4%">&nbsp;</th>
      </tr>
      </thead>
      <tbody>
	  <?Php 
	  if ($total_rs_buscar > 0)
	  {  		
		foreach($rs_buscar as $row_rs_buscar )  
		 {
		   if($row_rs_buscar['Cus_Est']=='I')
	  	   { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
	  ?>
	  <tr >
	  <td align="center"><FONT COLOR="<? echo $rojo;?>"><?php echo $row_rs_buscar['Cus_Cod'];?></FONT></td>
	  <td><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Prs_Ced'];?></FONT></td>
	 <td><FONT COLOR="<? echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Nombre'],'#FFFF00', 1);?></FONT></td>
	  <form action="<? echo $_SERVER['PHP_SELF']."?op=1";?>" method="post" name= "frml" id="form3">
	  <td align="center" width="4%">
      <?Php if($row_rs_buscar['Cus_Est']=='A')
		    { ?>
	        <button type="image" name="imageField" class='btn btn-success btn-mini' width="22" height="22" title="Seleccionar">
             <i class='icon-arrow-right icon-white'></i>
            </button>					
                <input type="hidden" name="codigo" id="codigo" value="<?Php echo $row_rs_buscar['Cus_Cod'];?>"/>
                <input type="hidden" name="hdd_aux" id="hdd_aux" value="1">
                <input type="hidden" name="volver_busqueda" id="volver_busqueda" value="<?Php echo $txt_busqueda;?>"/>
                <input type="hidden" name="volver_opciones" id="volver_opciones" value="<? echo $op_opciones?>">
                <input type="hidden" name="volver_custodio" id="volver_custodio" value="<? echo $row_rs_buscar['Nombre']; ?>">
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
	  <?Php }// while ($row_rs_buscar = $obBD_con1->fetch_assoc($rs_buscar));     
  	  }else{
  	  ?>
      	<tr><td>&nbsp;</td>
      	  <td>&nbsp;</td>
      	  <td><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
      	  <td>&nbsp;</td>
      	</tr>
      <? } // fin del if ($total_rs_buscar > 0)?>
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
  } 
	/**
	 * mostrar por custodio
	 */
if(isset($cadena))
{	
	if($total_registros) 
	{	
		/**
		* Indicador que me permitir crear filas
		*/
	  $indicadorfila=0;
	?>
<p><?Php echo "Custodio: ".$volver_custodio; ?></p>
	<table width="200" border="1" >
    <tr>
		<? 
        foreach($rs_ite_pro as $row_rs_ite_pro)
		{ 
		/**
		 * Consulto  el departamento del custodio.
		 */
		 $rs_DeparCust=$obBD_con1->getRowConsulta(1000,$cadena,$obBD_conexion);
		 $rs_totolDep=count($rs_DeparCust);			
			?>
          		<td>
           <table width="100" border="0" cellpadding="0" cellspacing="0">
              	<tr>
                	<td colspan="2" align="center"><?  $varcode=$row_rs_ite_pro['Act_Bar']; include("../../Librerias/barcode/generadorbarras.php");?></td>
              	</tr>
              	<tr >
                	<td width="20" class="Etiqueta1"><div align="left">Departamento:</div></td>
                	<td width="80" class="LetraNegra">&nbsp;<? echo $rs_DeparCust['Dep_Des'];?></td>
              	</tr>
                	<td  width="20" class="Etiqueta1"><div align="left">Activo:</div></td>
                	<td  width="80" class="LetraNegra">&nbsp;<? echo $row_rs_ite_pro['Act_Des']; ?></td>
              	</tr>
              	<tr>
                	<td  width="20" class="Etiqueta1"><div align="left">Secuencial:</div></td>
                	<td  width="80" class="LetraNegra">&nbsp;<? echo $row_rs_ite_pro['Act_Sec']; ?></td>
              	</tr>
          </table>
          </td>
          <? 
		  if ($indicadorfila==5)
		  {
		   	echo "</tr>";
		   	echo "<tr>";
		  	$indicadorfila=0;
		  }else{
		    $indicadorfila=$indicadorfila+1;
		  }	  
		}//foreach($rs_ite_pro as $row_rs_ite_pro)
	?>
       </tr>
      </table>
      <br>
	<?
		echo "<center>";	
		if(($pagina - 1) > 0) 
		{
			echo "<a href='".$_SERVER['PHP_SELF']."?pagina=".($pagina-1)."&&cadena=".$cadena."&&volver_busqueda=".$volver_busqueda."&&volver_opciones=".$volver_opciones."'>< Anterior</a> ";
		}
		for ($i=1; $i<=$total_paginas; $i++){ 
			if ($pagina == $i) {
				echo "<b>".$pagina."</b> "; 
			} else {
				echo "<a href='".$_SERVER['PHP_SELF']."?pagina=$i&&cadena=".$cadena."&&volver_busqueda=".$volver_busqueda."&&volver_opciones=".$volver_opciones."'>$i</a> "; 
			}	
		}
		if(($pagina + 1)<=$total_paginas) {
			echo " <a href='".$_SERVER['PHP_SELF']."?pagina=".($pagina+1)."&&cadena=".$cadena."&&volver_busqueda=".$volver_busqueda."&&volver_opciones=".$volver_opciones."''>Siguiente ></a>";
		}
		echo "</center>";
	}//Fin if($total_registros)
	else 
	{
		echo "<br>";
		echo error_alerta("El Custodio: ".$volver_custodio.", no tiene Activos asignados", 1);
	}?>
	<table width="314" border="0" cellpadding="0" cellspacing="0">
    <tr>
	  <td width="110">
	    <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form5"> 
	      <button type="button" name="btn_atras" id="btn_atras" value="Enviar"  class="btn btn-inverse fileinput-button" title="Atr&aacute;s"
      onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver"; ?>','<?Php echo $volver_busqueda.'*'.$volver_opciones.'*'.'1'; ?>')">
	        <i class="icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
	        </button>
	      </form></td>
  <td width="204"><? if($total_rs_ite_pro1 > 0){
	  ?>
    <form name="form4" method="post" action="act_pri_act_barra_2.0.php" target="_blank">  
      <input name="inicio" type="hidden" id="inicio" value="<? echo $inicio; ?>">
      <input name="registros" type="hidden" id="registros" value="<? echo $registros; ?>">
      <input name="cadena" type="hidden" id="cadena" value="<? echo $cadena; ?>">
      <input name="tipo" type="hidden" id="tipo" value="1">
      <button name="bnt_print" type="submit" class="btn btn-primary start" id="bnt_print" value="Imprimir" title="Imprimir">
        <i class='icon-print icon-white'></i> <span>Imprimir</span>
        </button>
    </form> <? }?></td>
    </tr>
  </table>
	<? 
	} //Fin if(isset($cadena))
	
  break; // Fin de consulta por custodio de etiquetas
  
  	
  
  
  
  
   }  //Fin de Switch
}// Fin de if (($op==1 || $op==2)) { ?>
      
	</td>
</tr>
</table>
</div>
<script type="text/javascript" src="../VALIDACIONES/act_par_campos_det.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>    
</BODY></HTML>
<?Php
/**
* Cierra las conexiones
*/
@$obBD_conexion->cerrar();
@$obBD_con1->liberar();
?>