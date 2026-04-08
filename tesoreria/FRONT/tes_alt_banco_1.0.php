<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * Permite Registrar Los banco relacionados con el Plan de cuentas
 * 
 * @author Jose Cumbicos
 * @version 1.0
 * Fecha de creacion:	10-06-2014
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
   * @var Class_Log_Conexion_Tip
   */  
  $obBD_conexion = new Class_Log_Conexion_Tip($Ses_Dat_Dis);
  
  /**
   * objeto para consultas
   * @var Class_Log_Datos_Tip
   */
  $obBD_con1 =  new Class_Log_Datos_Tip;
  
  /**
   * Llamado de la libreria para evitar el reenvio de datos
   * @var Post_Block
   */
  $thisPost = new Post_Block;

if (isset($hdd_save))
{
	if ($thisPost->postBlock($_POST['postID']))
	{			
	   $obBD_ins1 =  new Class_Log_Datos_Tip;
	   /**
	   * inicio de la transaccion 
	   */
	   $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		
		/** 
		* Se guarda el banco
		*/ 
		$obBD_ins1->operacionobBD(2, $PldCod.'*'.$BacCue.'*'.$BacObs.'*'.$BanTip, $obBD_conexion);
		$Ban_Cod = $obBD_ins1->insercionid($obBD_conexion->conexion);
		
		/**
		* Se guarda el tipo de pago
		*/
		for($x=1;$x<=$cont;$x++)
		{
		  if($ckh[$x]!="") 
		  {
			  $obBD_con1->operacionobBD(11, $ckh[$x].'*'.$Ban_Cod, $obBD_conexion);
		  }
		}
		/**
		* fin de la transacci�n 
		*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}
}

if(isset($ajax_plan))
{
?>
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
          <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="45">&nbsp;&nbsp;
          <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_busq=1&Pld_Des='+document.getElementById('txt_busqueda').value+'&op='+document.getElementById('op_opciones').checked+'&Pla_Cod='+ document.getElementById('PlaCod').value,'div_result')">
            <i class="icon-search icon-white"></i>
            <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>
            </button>          
          <input name="hdd_buscar" type="hidden" id="hdd_buscar" value="insertar">	  </td>
        </tr>
    </table>    
    </FIELDSET>	
    <div id="div_result"></div>
<?php
exit();	
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
		<script language="javascript" src="../VALIDACIONES/tes_par_banco.js"></script>       
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/modal.js"></script>
        <link rel="stylesheet" type="text/css" href="../../Librerias/jquery/modal/css/modal.css">
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
	<!--meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"-->
  <TITLE><?php echo "Param. Bancos Registrar [EXA]"; ?></TITLE>
  <meta charset= "UTF-8">

	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td width="71%" height="10">&raquo; Registrar Bancos </td>
    </tr>
	<tr>
        <td height="340" valign="top">
        <form method="post" name= "form1" action="<?php echo $_SERVER['PHP_SELF'];?>">        
        <?php 
        /** 
        * Creacion del campo repost 
        */
        $thisPost->startPost();
        ?>
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Datos a registrar</label>
        </LEGEND>
        <?php echo mensaje_requerido(); ?>
        <table width="55%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span> Plan de cuentas:         
              </td>
            <td colspan="2">          
              <?php
          	/** 
			* Consultar los Planes de cuenta
			*/
			$row_rs_planc = $obBD_con1->getArrayConsulta(1, strtoupper($Ses_Emp_Cod), $obBD_conexion);		
			
		  ?>
              <select name="PlaCod" id="PlaCod">
                <option value=''>Seleccione...</option>
                <?php foreach($row_rs_planc as $datos){?> 	
                <option value='<?php echo $datos['Pla_Cod']?>'><?php echo $datos['Pla_Obs']?></option>
                <?php }?>
                </select>    
              </td>          
          </tr>
          <tr>
            <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span> Cuenta contable:</td>
            <td width="45%">
              <div id="div_tipdes">
                <input name="PldCod" type="hidden" id="PldCod" value="" />
                <input name="PldDes" type="text" id="PldDes" readonly="readonly" size="43" maxlength="40">
                </div></td>
            <td width="33%" align="left" valign="top">              
              <button type="button" name="button" id="button" class="btn btn-success btn-mini" onClick="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_plan=1','ajax_modal')" title="Buscar">
                <i class="icon-search icon-white"></i>
                <span>Buscar</span>
                </button>
              
              
              </td>
          </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Tipo cuenta:</td>
            <td colspan="2">
              <select name="BanTip" id="BanTip">
                <option value=''>Seleccione...</option>             
                <option value='C'>Caja</option>
                <option value='B'>Banco</option>
                <option value='O'>Otros</option>
                </select></td>
          </tr>
          <tr>
          <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span> # Cuenta bancaria:</td>
           <td colspan="2">          
            <input name="BacCue" type="text" id="BacCue" value="" size="20" maxlength="20" style="text-transform:uppercase">
            </td>
          </tr>
          <tr>
            <td valign="top" class="Etiqueta1">Observaciones:</td>
            <td colspan="2"><textarea name="BacObs" cols="37" id="BacObs" style="text-transform:uppercase"></textarea></td>
          </tr>
          <tr>
            <td valign="top" class="Etiqueta1"><span class="Asterisco">*</span> Tipo de pago:</td>
            <td colspan="2"><?php
          	/** 
			* Consultar los Planes de cuenta
			*/
			$row_rs_tipo = $obBD_con1->getArrayConsulta(10,'', $obBD_conexion);		
			$i=0;
		  ?>
              <?php foreach($row_rs_tipo as $datos){$i++;?>
              <input type="checkbox" id="ckh[<?php echo $i;?>]" name="ckh[<?php echo $i;?>]" value='<?php echo $datos['Pag_Cod']?>' />
              <?php echo utf8_encode($datos['tipo'])?><br />
              <?php }?></td>
          </tr>
        </table>     
	  </FIELDSET>
        <br />
      <input name="cont" type="hidden" id="cont" value="<?php echo $i;?>">
      <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
      <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form,'PlaCod*PldDes*BacCue', 1)">
               <i class="icon-book icon-white"></i>
               <span>Guardar</span>
      </button>         
      </form>    
      <br/>      
	</td>
  <tr/>    
</table>  
</div> 
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()"></div>
<div id="bgmodal"  class="bgmodal"   style="display:none">		
	<div id="ajax_modal">
        
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