<?php 
/**
* Descripci�n: Modificacion de Periodos Contables
* Fecha de actualizaci�n: 2015-Feb-25
* Desarrollador: Jose Cumbicos
*/

require_once('../../administrador/LOGICA/seguridad.php');	  
require_once('../LOGICA/con_log_perio_cont.php');	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/** 
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;
/**
* Creaci�n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d"); 

/*Almacena los datos modificados */
if (isset($hdd_save))
{
	/*$rs_consul_fecha = $obBD_con1->getRowConsulta(116, $Pec_Fei.'*'.$Pec_Fef,$obBD_conexion);	
	$total_rs_consul_fecha=$rs_consul_fecha['Pec_Cod'] > 0? 1 : 0;	*/
		
	
		   $obBD_ins1 =  new Class_Log_Datos_Con;
		   /**
		   * inicio de la transaccion 
		   */
		   $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		   
		   $obBD_ins1->operacionobBD(115, $Pec_Fei.'*'.$Pec_Fef.'*'.$codigo,$obBD_conexion);
	       
		   /**
		   * fin de la transacci�n 
		   */
		   $obBD_ins1->fin_transaccion($obBD_conexion->conexion);		  
	
}
	
/*Busqueda del tipo de comprobante */
if ($txt_busqueda != "")
{
	$rs_buscar = $obBD_con1->getArrayConsulta(114, $txt_busqueda,$obBD_conexion);
	$total_rs_buscar = count($rs_buscar);	
}
else
{
    /*Consulta realizada en base al tipo de comprobante seleccionado*/
	if (isset($codigo))
	{
		$row_rs_consulta = $obBD_con1->getRowConsulta(113, $codigo,$obBD_conexion);
		$total_rs_consulta=$row_rs_consulta['Pec_Cod'] > 0? 1 : 0;	 		
	}
}
?>
	  
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
        <script type="text/javascript" src="../VALIDACIONES/con_val_perio_cont.js"></script>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script> 				         
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>          
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>  
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
       <script>
		$(function() { 
			/* Campo 1 */
			$( "#Pec_Fei" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});				
			/* Campo 2 */
			$( "#Pec_Fef" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});						
		}); 		
        </script>   
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<table width="560" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10"><span class="Titulos1">&raquo;</span> modificaci�n de periodo contable</td>
  </tr>
  <tr>
           <td width="758"><?Php echo mensaje_requerido(); ?></td>
  </tr>
	<tr>
      <td height="400" valign="top">
           <form name="form1" method="post" action="<?Php $_SERVER['PHP_SELF']?>">             
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Buscar por:</label>
            </LEGEND>
            <table width="23%" height="36" border="0" cellpadding="0" cellspacing="0">
                <tr>
             <td width="72" height="28" class="Etiqueta1"><div align="right">A&ntilde;o: </div></td>
                  <td width="61" class="Busqueda">
                  <select name="txt_busqueda" id="txt_busqueda">
                    <option></option>
                    <?Php
                            for ($i=date("Y"); $i<= date("Y")+2; $i++)
                            {
                        ?>
                    <option value="<?Php echo $i ?>"><?Php echo $i ?></option>
                    <?Php
                            }
                        ?>
                  </select>         
                  <td width="136"><div align="center">
                  <button type="button" class="btn btn-success fileinput-button" title="Buscar" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)">
                                <i class="icon-search icon-white"></i>
                                <span>Buscar</span></button>
                  </div></td>
                </tr>
              </table>
            </FIELDSET>

  <?Php
if(isset($txt_busqueda) && !isset($codigo))
{
	
  ?>
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Resultados de la b&uacute;squeda</label>
        </LEGEND>
            <table width="62%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader02">
              <thead>
              <tr>
                <th width="532">Fecha de Inicio</th>
                <th width="443">Fecha de Fin</th>
                <th width="149">Estado</th>
                <th width="58">&nbsp;</th>
              </tr>
              </thead>
              <tbody>      
              <?Php 
              if ($total_rs_buscar!=0)
              {
              foreach($rs_buscar as $row_rs_buscar) { ?>
              <tr >
                <td align="center"><?Php echo $row_rs_buscar['Pec_Fei']; ?></td>
                <td align="center"><?Php echo $row_rs_buscar['Pec_Fef']; ?></td>
                <td align="center"><?Php if ($row_rs_buscar['Pec_Est']=="A"){echo "Activo"; }else{ echo "Anulado";}?></td>
                <td width="58" align="center">
                <input type="hidden" id="codigo" name="codigo" value="<?Php echo $row_rs_buscar['Pec_Cod'];?>">
                 <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
                    <i class=" icon-arrow-right icon-white"></i>
                </button>                
                </td>
              </tr>
              <?php } 
                }else{ ?>
              <tr >
                <td align="center">&nbsp;</td>
                <td align="center"><?Php echo error_alerta("No hay resultados que mostrar para ".strtoupper($txt_busqueda)." ".$periodo, 2); ?></td>
                <td align="center">&nbsp;</td>
                <td width="58" align="center">&nbsp;</td>
              </tr>
              <?Php } ?>
              </tbody>
            </table>
        </FIELDSET>
           <?Php echo barra_estado($total_rs_buscar); ?>
     <?php }?>
     </form>
     <form method="post" name="form2" id="form2" action="<?Php $_SERVER['form2'] ?>">
     <?Php 

		if ($total_rs_consulta > 0) { 
		?>
		  <FIELDSET>
		<LEGEND>
		<label class="Titulos2">Datos a modificar</label>
		</LEGEND>
		<table width="100%" border="0">		 
		</table>
		<table border="0">
		   <tr>
				  <td width="154" class="Etiqueta1"><span class="Asterisco">*</span> Fecha de Inicio:</td>
					<td width="390">
					<input name="Pec_Fei" type="text" id="Pec_Fei" value="<?Php echo $row_rs_consulta['Pec_Fei']; ?>" size="15" onKeyUp="mascara(this,'-',patron,true)" readonly="true">
				   </td>
				</tr>
				<tr>
				  <td width="154" class="Etiqueta1"><span class="Asterisco">*</span> Fecha de fin:</td>
					<td width="390">
					<input name="Pec_Fef" type="text" id="Pec_Fef" value="<?Php echo $row_rs_consulta['Pec_Fef']; ?>" size="15" onKeyUp="mascara(this,'-',patron,true)" readonly="true">
					</td>
				</tr>
		  </table>
		</FIELDSET> 
		
		  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
		  <input type="hidden" id="codigo" name="codigo" value="<?Php echo $codigo;?>">
		  <table width="100" border="0" class="Azul">
		  <tr>
			<td width="100%" height="23">
              <button type="button" class="btn btn-primary start" title="Actualizar"  value= "Actualizar" name="btn_guardar" onClick="validar_perio_cont(form2)"><i class="icon-book icon-white"></i>
               <span>Guardar</span>
               </button>
			 </td>
		  </tr><?Php } /* fin del total_rs_consulta*/?>
		</table>
		</form>     
    </td>
  </tr>
</table>	   
</BODY>
</HTML>
<?Php 	
/**
* cierro las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>