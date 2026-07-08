<?php 
/**
* Descripción: Permite registrar alta de vendedor
* Fecha de actualización:	2014-06-17
* Desarrollador:	Jose Cumbicos
*/	
require_once('../../administrador/LOGICA/seguridad.php'); 
require_once('../LOGICA/fac_log_vendedor.php');	    
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php'); 

/** 
* Creación del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;
/** 
* Creacion del Objeto de conexion 
*/  
$obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);
/**
* Creación del Objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Pro; 

/** 
* Busqueda de los datos del cliente 
*/
if ($txt_busqueda != "")
{
	if ($op_opciones == "d")
	{
		/** 
		* Consulta del cliente en base al apellido 
		*/
		$rs_buscar = $obBD_con1->getArrayConsulta(1, trim($txt_busqueda).'*'.$Ses_Suc_Cod,$obBD_conexion);
	}
	else 
	{
		/** 
		* Consulta del cliente en base de la cedula 
		*/
		$rs_buscar = $obBD_con1->getArrayConsulta(2, trim($txt_busqueda).'*'.$Ses_Emp_Cod,$obBD_conexion);
	}  
}  

	
if (isset($hdd_save) && !isset($hdd_volver)) 
{
	if ($thisPost->postBlock($_POST['postID'])) 
	{
		$obBD_con1-> inicio_transaccion($obBD_conexion->conexion);
		//$obBD_con1->operacionobBD(10,"I*".$Prs_Cod,$obBD_conexion);
		$obBD_con1->operacionobBD(6,$Pun_Cod."*".$Prs_Cod,$obBD_conexion);
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);			
	}
}	  
?>


<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>        
		<script language="javascript" src="../VALIDACIONES/fac_val_guias.js"></script>        
		<link rel="stylesheet" type="text/css" href="../../Librerias/jquery/modal/css/modal.css">
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/jquery.js"></script>
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/modal.js"></script>
        
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>                
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">	
	</HEAD>
<BODY>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="table">
  <tr class="BarraTitulo">
	  <td height="10">&raquo; registrar vendedor </td>
  </tr>
  <tr>	  	
      <td height="389" valign="top">
        <form action="<?php echo $_SERVER['../LOGICA/PHP_SELF']; ?>" method="post" name= "form1" id="form1">
		<?Php include("../../componentes/FRONT/com_con_persona.php"); ?>
    	</form>  
        <?Php  
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
			  <th width="8%">Cód. Int. </th>
				  <th width="8%">Cédula/R.U.C.</th>
				  <th>Apellidos/Nombre</th>
				  <th width="4%">&nbsp;</th>
			  </tr>
			 </thead>
			 <tbody> 
			  <?Php 
			if(count($rs_buscar) != 0)
			{	  
			  foreach($rs_buscar as $row_rs_buscar)
			  { ?>
			  <tr>	  
			  <td align="center"><?Php echo $row_rs_buscar['Prs_Cod']; ?></td>
				<td align="center"><?Php echo $row_rs_buscar['Prs_Ced']; ?></td>
				<td align="left">&nbsp;<?Php echo marcarCadenaColor($txt_busqueda,$row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom'],'#FFFF00', '#000', 1); ?></td>
				<form name="form1" id="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
                <td align="center">								
				<input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Cod'];?>">
				<input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
				<input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">						
				<button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()">
					<i class=" icon-arrow-right icon-white"></i>
				</button>							
			  </td>
              </form>
			  </tr>
			  <?Php }//Fin del foreach
			}//FIn del if($total_rs_buscar != 0)
			else
			{ ?>
				<tr><td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td><?Php echo error_alerta("No hay resultados que mostrar", 1) ?></td>
				  <td>&nbsp;</td>
				</tr>	   
			<?Php 
			}//Fin del else if($total_rs_buscar != 0) ?>
			</tbody>
		  </table>
		</FIELDSET>
		<?php
			echo barra_estado(count($rs_buscar));
		}//Fin del if(isset($txt_busqueda)) ?>            
    <?php if(isset($codigo) && !isset($hdd_save))
	  {
		/**
		* Consulta datos de la persona
		*/
		$row_rs_cliente = $obBD_con1->getRowConsulta(3, $codigo, $obBD_conexion);   
	   ?>
       <form name="form2" id="form2" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
       <FIELDSET>
         <LEGEND>
          <label class="Titulos2">Datos a registrar</label>
          </LEGEND>
          <?php /**
			 * Creacion del campo REPOST
			 */
			 $thisPost->startPost(); 
		  ?>
        <table width="95%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="12%" class="Etiqueta1">C&eacute;dula/R.U.C.:</td>
                <td width="88%" class="LetraNegra">&nbsp;<?Php echo $row_rs_cliente['Prs_Ced'] ?>
                  <input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>"></td>
              </tr>
              <tr>
                <td width="12%" class="Etiqueta1">Vendedor:</td>
                <td class="LetraNegra">&nbsp;<?Php echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'] ?></td>
              </tr>
              <tr>
                <td width="12%" valign="middle" class="Etiqueta1"><span class="Asterisco">* </span>Punto de Impresi&oacute;n:</td>
                <td colspan="3" class="LetraNegra">
                <?php 
				/**
				* Consulta datos del punto de impresion
				*/
				$row_rs_puntos = $obBD_con1->getArrayConsulta(4, $Ses_Suc_Cod, $obBD_conexion);  
				?>
              <select name="Pun_Cod" id="Pun_Cod">
                	<option value="">Seleccione...</option>
                 <?php 
				 foreach($row_rs_puntos as $datos){
				    $row_rs_existe= $obBD_con1->getRowConsulta(5,$datos['Pun_Cod'].'*'.$codigo, $obBD_conexion);  
					$total_rs_axiste=$row_rs_existe['Vnd_Cod'] > 0? 1 : 0;
				 ?>   
                    <option <?php if($total_rs_axiste!=0){ echo "disabled";}?> value="<?php echo $datos['Pun_Cod']?>"><?php  if($total_rs_axiste!=0){ echo $datos['Pun_Des']." [Asignado]";}else{ echo $datos['Pun_Des'];}?></option>
                 <?php }?>   
                </select>
                </td>
              </tr>
            </table>
   	     </FIELDSET>
       <table width="290" border="0" cellpadding="0" cellspacing="0" class="Azul">
       <tr>
               <td width="109">
               <button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver"; ?>', '<?Php echo $volver_busqueda.'*'.$volver_op.'*'; ?>')">
                                <i class=" icon-arrow-left icon-white"></i>
                                <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
                   </button></td>
             <td width="181">
             <button type="button" class="btn btn-primary start" title="Guardar" onClick="validar_requeridos(this.form,'Pun_Cod',1)">
                       <i class="icon-book icon-white"></i>
                       <span>Guardar</span>
                </button>
                <input name="hdd_save" type="hidden" id="hdd_save" value="insertar"> 
                <input name="Prs_Cod" type="hidden" id="Prs_Cod" value="<?php echo $codigo;?>"> 
              </td>
             </tr>
             </table>
        </form>
       <?php } ?> 
    </td>
  </tr>

</table>	    
<script type="text/javascript" src="../VALIDACIONES/fac_par_vendedor.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script> 
</BODY>
</HTML>
<?php

/* cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* fin cierre las conexiones */
?>