<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * Permite registrar las marcas de los productos 
 * 
 * @author Jose Cumbicos
 * @version 1.0
 * Fecha de actualizaci�n:	12-06-2014
 *
 * @package Exa.Facturacion - OFSERCONT
 * 
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_banco.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

  /**
   * objeto para la conexion
   * @var Class_Log_Conexion_Tes
   */
  $obBD_conexion = new Class_Log_Conexion_Tip($Ses_Dat_Dis);
  
  /**
   * objeto para consultas
   * @var Class_Log_Datos_Tes
   */
  $obBD_con1 =  new Class_Log_Datos_Tip;
  
  /**
   * Llamado de la libreria para evitar el reenvio de datos
   * @var Post_Block
   */
  $thisPost = new Post_Block;

/* 
* Almacena los datos modificados
*/
if (isset($hdd_save) && !isset($hdd_volver))
{
	if ($thisPost->postBlock($_POST['postID']))
	{	
	   /**
	   * inicio de la transaccion 
	   */
	   $obBD_con1-> inicio_transaccion($obBD_conexion->conexion);
	   
	   $obBD_con1->operacionobBD(6,$BacObs.'*'.$BacCue.'*'.$PldCod.'*'.$Ban_Est.'*'.$BanTip.'*'.$BanCod,$obBD_conexion);
	   
	   /**
	   * Se guarda el tipo de pago
	   */	  
	   for($x=1;$x<=$cont;$x++)
		{
			  /** 
			  * Consultar las formas de pago existentes
			  */			 
			  $row_rs_tippago = $obBD_con1->getRowConsulta(13,$auxCod[$x].'*'.$BanCod, $obBD_conexion);			 
			  $total_rs_tippago=$row_rs_tippago['Pag_Cod'] > 0? 1 : 0;
			  if ($total_rs_tippago!=0)
			  {
				  if ($row_rs_tippago['Pag_Est']=='I')
				  {
						if($ckh[$x]!="") 
						{
						  $obBD_con1->operacionobBD(14,'A*'.$auxCod[$x].'*'.$BanCod, $obBD_conexion);	
						}else{
						  $obBD_con1->operacionobBD(14,'I*'.$auxCod[$x].'*'.$BanCod, $obBD_conexion);	
						}
				  }else{
						if($ckh[$x]!="") 
						{
						  $obBD_con1->operacionobBD(14,'A*'.$auxCod[$x].'*'.$BanCod, $obBD_conexion);	
						}else{
						  $obBD_con1->operacionobBD(14,'I*'.$auxCod[$x].'*'.$BanCod, $obBD_conexion);	
						}  
				  }
				  
			  }else{
				 if($ckh[$x]!="") 
				 {
					$obBD_con1->operacionobBD(11, $auxCod[$x].'*'.$BanCod, $obBD_conexion);	  
				 }
			  }
		  
		}
	   
	   /**
	   * fin de la transacci�n 
	   */
	   $obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}
}

if (isset($txt_busqueda))
{
	/*
	* Consulta los datos tabla bancos
	*/
	$row_rs_buscar =  $obBD_con1->getArrayConsulta(15,trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion); 
}	

if (isset($ajax_busq))
{		
		/** 
		* Consultar si existe el nombre de tipo de asiento 
		*/
		
		if ($op=="true")
		{	
			// busca por descripcion
			$row_rs_busq = $obBD_con1->getArrayConsulta(3, strtoupper($Pla_Cod.'*'.$Pld_Des), $obBD_conexion);		
		}else{
			
			//busca por codigo de secuencia
			$row_rs_busq = $obBD_con1->getArrayConsulta(4, strtoupper($Pla_Cod.'*'.$Pld_Des), $obBD_conexion);		
		}
		?>        
		<FIELDSET>
        <LEGEND>
        <label class="Titulos2">Resultados de la busqueda</label>
        </LEGEND>
            <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
            <thead>
              <tr>
                  <th width="4%">C&oacute;d. Int.</th>
                  <th width="8%">Codigo</th>
                  <th width="66%">Descripción</th>
                   <th width="10%">Tipo</th>            
                  <th width="4%">&nbsp;</th>
              </tr>
              </thead>
              <tbody>
              <?php 
            if(count($row_rs_busq) != 0)
            {
              foreach ($row_rs_busq as $row)
              { 
			  	//verificamos si la cuenta no existe como activa en la tabla bancos
				$row_rs_existe = $obBD_con1->getArrayConsulta(5, $row['Pld_Cod'], $obBD_conexion);		
			  
			  ?>
              <tr>
                <td align="center"><?php echo $row['Pld_Cod']; ?></td>
                <td align="center"><?php echo $row['Pld_Cdc']; ?></td>
                <td>
				<?php 
					echo marcar_cadena($Pld_Des,$row['Pld_Des'],'#FFFF00',1);
					if(count($row_rs_existe)!=0){
						echo "&nbsp;&nbsp;&nbsp;<span class='Alertas3'>!Ya est&aacute; ingresado!</span>";
					}
				?>
                </td>
                <td align="center"><?php echo $row['Pld_Tip'];?></td>        
                <td align="center">                              
                <?php if(count($row_rs_existe)==0){?>
                <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_cuenta=&pldDes=<?php echo $row['Pld_Des']; ?>&pldCod=<?php echo $row['Pld_Cod']; ?>','div_tipdes')">
                    <i class=" icon-arrow-right icon-white"></i>
                </button>               
                <?php }?>                	                
                </td>		
              </tr>
              <?php 
              } 
            }//FIn del if(count($row_rs_buscar != 0)
            else
            { ?>
              <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td align="center"><?php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
          <?php
            } ?>
              </tbody>
          </table>
          <?php echo barra_estado(count($row_rs_busq)); ?>
        </FIELDSET>	
		<?php		
exit();
}

if(isset($ajax_cuenta))
{
?>	
	<input name="PldCod" type="hidden" id="PldCod" value="<?php echo $pldCod;?>" />
    <input name="PldDes" type="text" id="PldDes" value="<?php echo $pldDes;?>" readonly="readonly" size="43" maxlength="40">
<?php
exit();	
}
?>

<HTML>
	<HEAD>		
    	<?php require_once("../../mascaras/model1/estilos/estilos.php"); ?>   
	    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
	    <script language="javascript" src="../VALIDACIONES/tes_par_tip_asient.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
            <script type="text/javascript" src="../../Librerias/jquery/modal/js/modal.js"></script>
            <link rel="stylesheet" type="text/css" href="../../Librerias/jquery/modal/css/modal.css">
	  <script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
	<!--meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"-->
  <TITLE><?php echo "Param. Banco Modificar [EXA]"; ?></TITLE>
  <meta charset= "UTF-8">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; modificar Bancos</td>
  </tr>
	<tr>
        <td height="389" valign="top">
   <form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']?>">    
   <FIELDSET> 
   <legend>
  <label class="Titulos2">Buscar por:</label>
   </legend>
   <?php echo mensaje_requerido(); ?>
   <table width="528" height="36" border="0" cellspacing="0">
     <tr class="BarraBusqueda">
      <td width="79" ><div align="right"><span class="Asterisco">*</span> Nombre:</div></td>
      <td width="336"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50"></td>
      <td width="107" align="center" >
       <button type="button" class="btn btn-success btn-mini" title="Buscar" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)">
                    <i class="icon-search icon-white"></i>
                    <span>Buscar</span>
        </button>
      </td>
    </tr>
</table>
</FIELDSET>
 </form> 
  <?php
  	if(isset($txt_busqueda))
	{
  ?>
 <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
          <th width="4%">C&oacute;d. Int.</th>
          <th width="4%">Codigo</th>
          <th width="82%">Descripción</th>
           <th width="4%">Tipo</th>
           <th width="4%">Estado</th>            
		  <th width="4%">&nbsp;</th>
      </tr>
      </thead>
      <tbody>
	  <?php 
	if(count($row_rs_buscar) != 0)
	{
	  foreach ($row_rs_buscar as $row)
	  { 
	  	if($row['Ban_Est']=='Inactivo')
			$rojo = "style='color:#F00'";					
		else
			$rojo='';
	  ?>
	  <tr>		
        <td height="25" align="center" <?php echo $rojo;?>><?php echo $row['Ban_Cod']; ?></td>
        <td <?php echo $rojo;?> align="center"><?php echo $row['Pld_Cdc']; ?></td>
        <td <?php echo $rojo;?>><?php echo marcar_cadena($txt_busqueda,$row['Pld_Des'],'#FFFF00',1);?></td>
        <td <?php echo $rojo;?> align="center"><?php echo $row['Pld_Tip'];?></td>        
        <td <?php echo $rojo;?> align="center"><?php echo $row['Ban_Est'];?></td>        
        <form name="form3" method="post" action="<?php echo $_SERVER['PHP_SELF']?>">  
        <td>                                      
        <input type="hidden" id="Ban_Cod" name="Ban_Cod" value="<?php echo $row['Ban_Cod']; ?>">
        <input type="hidden" id="Pla_Cod" name="Pla_Cod" value="<?php echo $row['Pla_Cod']; ?>">
        <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit();">
            <i class=" icon-arrow-right icon-white"></i>
        </button>                       
        </td>		
        </form>
	  </tr>
	  <?php 
	  } 
  	}//FIn del if(count($row_rs_buscar != 0)
	else
	{ ?>
  	  <tr>
	    <td>&nbsp;</td>
        <td>&nbsp;</td>
	    <td><?php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
	    <td align="center">&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
	    </tr>
  <?php
	} ?>
      </tbody>
  </table>
  <?php echo barra_estado(count($row_rs_buscar)); ?>
</FIELDSET>
<?php
  }
?>
<form method="post" name="form3" action="<?php echo $_SERVER['PHP_SELF'];?>">
<?php
if (isset($Ban_Cod) && !(isset($txt_busqueda)))
{
	/* Creacion del campo repost */
	$thisPost->startPost();
?>
<FIELDSET>
  <LEGEND>
<label class="Titulos2">Datos a modificar</label>
</LEGEND>
<?php echo mensaje_requerido(); ?>
<?php
/** 
* Consultar los Planes de cuenta
*/
$row_rs_datos = $obBD_con1->getRowConsulta(9, $Ban_Cod, $obBD_conexion);		
?>
<table width="55%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span> Plan de Cuentas: </td>
    <td colspan="2">&nbsp;<?php echo $row_rs_datos['Pla_Obs']?>
      <input name="BanCod" type="hidden" id="BanCod" value="<?php echo $row_rs_datos['Ban_Cod']?>" /></td>
  </tr>
  <tr>
    <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span> Asociar Plan de cuenta:</td>
    <td width="45%">
    <div id="div_tipdes">
      <input name="PldCod" type="hidden" id="PldCod" value="<?php echo $row_rs_datos['Pld_Cod']?>" />
      <input name="PlaCod" type="hidden" id="PlaCod" value="<?php echo $Pla_Cod; ?>" />
      <input name="PldDes" type="text" id="PldDes" readonly="readonly" size="43" maxlength="40" value="<?php echo $row_rs_datos['Pld_Des']?>" />
    </div></td>
    <td width="33%" align="left" valign="top">
    <button type="button" name="button" id="button" class="btn btn-success btn-mini" onclick="Muestra_Aparecer()" title="Buscar"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Tipo cuenta:</td>
    <td colspan="2"><select name="BanTip" id="BanTip">
      <option value=''>Seleccione...</option>
      <option <?php if($row_rs_datos['Ban_Tip']=='C'){echo "selected";}?> value='C'>Caja</option>
      <option <?php if($row_rs_datos['Ban_Tip']=='B'){echo "selected";}?> value='B'>Banco</option>
      <option <?php if($row_rs_datos['Ban_Tip']=='O'){echo "selected";}?> value='O'>Otros</option>
    </select></td>
  </tr>
  <tr>
    <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span> # Cuenta bancaria:</td>
    <td colspan="2"><input name="BacCue" type="text" id="BacCue" value="<?php echo $row_rs_datos['Ban_Cue']?>" size="20" maxlength="20" style="text-transform:uppercase" /></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Estado:</td>
    <td colspan="2">
    <select id="Ban_Est" name="Ban_Est">
    	<option <?php if($row_rs_datos['Ban_Est']=='Activo'){echo "selected";}?> value='A'>ACTIVO</option>
        <option <?php if($row_rs_datos['Ban_Est']=='Inactivo'){echo "selected";}?> value='I'>INACTIVO</option>
    </select>
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1">Observaciones:</td>
    <td colspan="2"><textarea name="BacObs" cols="37" id="BacObs" style="text-transform:uppercase"><?php echo $row_rs_datos['Ban_Obs']?></textarea></td>
  </tr>
  <tr>
    <td valign="top" class="Etiqueta1"><span class="Asterisco">*</span> Tipo de pago:</td>
    <td colspan="2">
    <?php
	/** 
	* Consultar las formas de pago
	*/
	$row_rs_tipo = $obBD_con1->getArrayConsulta(10,'', $obBD_conexion);	
		
	$i=0;
   ?>
	  <?php foreach($row_rs_tipo as $datos){
		  $i++;
		  /** 
		  * Consultar las formas de pago existentes
		  */
		  $row_rs_pag = $obBD_con1->getRowConsulta(12,$datos['Pag_Cod'].'*'.$row_rs_datos['Ban_Cod'], $obBD_conexion);			 
		  $total_rs_pag=$row_rs_pag['Pag_Cod'] > 0? 1 : 0;		  
	  ?>
	  <input name="auxCod[<?php echo $i;?>]" type="hidden" id="auxCod[<?php echo $i;?>]" value="<?php echo $datos['Pag_Cod']?>">
      <input type="checkbox" id="ckh[<?php echo $i;?>]" name="ckh[<?php echo $i;?>]" value='<?php echo $datos['Pag_Cod']?>' <?php if( $total_rs_pag==1){ echo "checked";}?>  />
	  <?php echo $datos['tipo']?><br />
	  <?php }?>
    </td>
  </tr>
</table>
</FIELDSET>     
  <table width="189" border="0" class="Azul">
  <tr>
    <td width="50%">
    <button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onclick="campos_hide(this.form, '<?php echo "txt_busqueda*hdd_volver"; ?>', '<?php echo $volver_busqueda.'*1'; ?>')"> <i class=" icon-arrow-left icon-white"></i> <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
    </td>
    <td width="50%" height="23"><div align="center">
     <input name="cont" type="hidden" id="cont" value="<?php echo $i;?>">
    <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
    <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'PldDes*BacCue', 1)">
                   <i class="icon-book icon-white"></i>
                   <span>Guardar</span>
       </button>    
    </div></td>
  </tr>
</table>
  <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?php echo $txt_busq; ?>" />
  <input type="hidden" name="codigo" id="codigo" value="<?php echo $codigo; ?>" />
<?php
}
?>
</form>        
	</td>
  </tr>
</table>	
</div>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()"></div>
<div id="bgmodal"  class="bgmodal"   style="display:none">		
	<div id="ajax_modal">
    <FIELDSET>
    <LEGEND>
        <label class="Titulos2">Buscar Cuenta:</label>
    </LEGEND>       
    
    <table width="50%" border="0">
      <tr>
        <td >
        <input name="op_opciones" id="op_opciones" type="radio" onClick="setfocus(this.form.txt_busqueda)" style="cursor:pointer" value="d"  checked>
        <span class="Etiqueta1">Descripci&oacute;n</span>
        </td>
        <td >
        <input type="radio" name="op_opciones" id="op_opciones" onClick="setfocus(this.form.txt_busqueda)" style="cursor:pointer" value="r">
        <span class="Etiqueta1">C&oacute;digo</span>
        </td>
      </tr>
    </table>    
    <table width="572" border="0" cellpadding="0" cellspacing="0" class="BarraBusqueda">
      <tr>
        <td height="15">&nbsp;&nbsp;&nbsp;<span class="Asterisco">*</span> B&uacute;squeda:
          <input name="txtbusqueda" type="text" id="txtbusqueda" value="" size="45">&nbsp;&nbsp;
          <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_busq=1&Pld_Des='+document.getElementById('txtbusqueda').value+'&op='+document.getElementById('op_opciones').checked+'&Pla_Cod='+ document.getElementById('PlaCod').value,'div_result')">
            <i class="icon-search icon-white"></i>
            <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>
            </button>          
          <input name="hdd_buscar" type="hidden" id="hdd_buscar" value="insertar">	  </td>
        </tr>
    </table>
    
    </FIELDSET>
    <div id="div_result"></div>
    </div>
</div>
<script type="text/javascript" src="../VALIDACIONES/tes_par_banco.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	  	
</BODY>
</HTML>
<?php
/* 
* Cierra las conexiones 
*/
$obBD_conexion->cerrar();	
?>