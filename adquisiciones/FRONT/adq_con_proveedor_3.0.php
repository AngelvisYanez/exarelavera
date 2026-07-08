<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php 

/**
* pagina de consulta de proveedores (tes_con_proveedor_2.0.php) :)
*
* @author Juan Carlos León Ruiz [car.87cod]
* Ultima Actualización: 27-02-2012
*
* Permite buscar y visualizar los datos de un proveedor
* Permite ver todo el listado de proveedores
* Permite exportar a excel el listado de proveedores
*
* @package tesoreria
*/								 
	
 require_once('../../administrador/LOGICA/seguridad.php');
 require_once('../LOGICA/adq_log_proveedor.php');
 require_once('../../Librerias/procedimientos/almacenados_standar.php');
  
 /**
  * objeto para la conexion
  * @var Class_Log_Conexion_Tes
  */
 $obBD_conexion = new Class_Log_Conexion_Prv($Ses_Dat_Dis);
 
 /**
  * objeto para consultas
  * @var Class_Log_Datos_Tes
  */
 $obBD_con1 =  new Class_Log_Datos_Prv;
 
 if(isset($_POST['hdd_volver']))
 {
 	unset($_POST['hdd_save']);
 	unset($_POST['Cli_Cod']);
 }	  
?>
<HTML>
  <HEAD>		
	<title><?Php echo $Ses_Sys_Nom; ?></title>    
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
    
	<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<script language="javascript" src="../VALIDACIONES/adq_val_proveedor.js"></script>
    
    <script type="text/javascript" src="../../Librerias/exportar/jquery-1.3.2.min.js"></script>
    
    <script language="javascript">
			$(document).ready(function() {
				/* LLamado a la class del boton exportar */
				$("#Boton_Excel").click(function(event) {
					$("#datos_a_enviar").val( $("<div>").append( $("#Exportar_a_Excel").eq(0).clone()).html());
					$("#FormularioExportacion").submit();
			});
			});
		</script>	
        
    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script> 
    <script type="text/javascript"> 
      $(function() {
			$('#set1 *').tooltip({showURL: false});
	  });              			
		</script>
  </HEAD>
  <BODY>
  
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
    <td height="10">&raquo; consulta de proveedor </td>
</tr>
<tr>
<td height="400" valign="top">   
   
   <table width="100%" cellspacing="0">
   <tr>
    <td>
   <?php	
   
   
   /**
    * $op obtiene el numero de la pestaña activa
    */
   
	if(!isset($op))
	{
	  $op=1;
	}
    
	/**
    * $descripcion cadena que contiene los nombres de las pestañas separadas por ( * )
	* $pag1 and $pag2 URL de la pagina
    */
	
	$descripcion = "Individual*Todos";
	$pag1 = $_SERVER['PHP_SELF']."?op=1";
	$pag2 = $_SERVER['PHP_SELF']."?op=2";
	tabs(2,$descripcion, $pag1.'*'.$pag2, $op);
   ?>
   </td>
   </tr>
   <tr>
    <td>
   <?php
  switch($op)
  {
	  case 1:
?>
<div id="set1">
<table cellpadding="0" cellspacing="0" border="0" width="100%">
   <tr>
    <td valign="top">

    <form action="<?Php echo $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
    <?Php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
    </form>
    </td>
   </tr>
  <?Php
  if(isset($_POST['txt_busqueda']) && !isset($_POST['Prv_Cod']))
  {
  ?>
  <tr>
   <td>
   
  <FIELDSET>
   <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
   </LEGEND>
    <table cellpadding="0" cellspacing="0" width="100%" class="fixedHeader01">
    <thead>
    <tr>
	 <th width="7%">C&oacute;d. Int. </th>
     <th width="15%" >R.U.C.</th>
     <th width="75%">Proveedor</th>
	 <th width="3%">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    <?php
	
	/*
	 * Array de Proveedores resultante de la busqueda en la base de datos
	*/
	$Arr_Proveedor = $obBD_con1->getArrayConsulta($op_opciones == "d"? 9 : 10,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);
	
	/*
	 * Numero de filas resultantes
	*/
	$filas = 0;
	if (count($Arr_Proveedor)) 
	{
	foreach($Arr_Proveedor as $row)
	{
		$filas++;
	?>
	
    <tr>
      <td align="center"><?Php echo '&nbsp;'.$row['Prv_Cod']; ?></td>
      <td><?Php echo '&nbsp;'.$row['Prs_Ced']; ?></td>
      <td><?Php echo marcar_cadena($txt_busqueda, ($row['Prv_Tic']=='N')?$row['Prs_Ape']." ".$row['Prs_Nom']:$row['Prs_Ape'], '#FFFF00', 1); ?></td>
      <td align="center"><form name='form' method='post'  action='<?php echo $_SERVER['PHP_SELF'];?>'>
        <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()"> <i class=" icon-arrow-right icon-white"></i> </button>
        <input name="Prv_Cod" id="Prv_Cod" type="hidden" value="<?Php echo $row['Prv_Cod']; ?>">
        <input name="txt_busqueda2" value="<?php echo $txt_busqueda;?>" type="hidden">
        <input name="op_opciones2" value="<?php echo $op_opciones;?>" type="hidden">
      </form></td>
    </tr>
    <?php }}else{?>
    <tr>
	 <td width="7%" align="center">&nbsp;</td>
	 <td align="center">&nbsp;</td>
	 <td align="center"><?Php echo error_alerta(" ¡No hay resultados que mostrar!", 1);?></td>
	 <td align="center" width="3%">&nbsp;</td>		
   </tr> 
    <?php }?>	
    </tbody>
  </table>
  <?php
   echo barra_estado($filas);
  ?>
  </FIELDSET>
  </td>
 </tr>
  <?Php 
  }
  if(isset($Prv_Cod) && !isset($hdd_volver))
  {
  ?>
  <tr><td>
  <FIELDSET>
   <LEGEND>
	<label class="Titulos2">Datos del Proveedor</label>
   </LEGEND>
   
   <form method="post" name= "form" id="form" action="<?php echo $_SERVER['PHP_SELF'];?>">
   <?php
   	$Row_Persona = $obBD_con1->getRowConsulta(11, $Prv_Cod, $obBD_conexion);
   ?>
   
   <FIELDSET>
	 <LEGEND>
	  <label class="Titulos2">Datos Generales</label>
	 </LEGEND>
     <table width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1">
         
         R.U.C.:&nbsp;
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?Php echo $Row_Persona['Prs_Ced']; ?>
        
       </td>       
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">
        
         Tipo de Documento:
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
        <?php
		$Ide_Max = strlen($Row_Persona['Prs_Ced']);
		$Ide_Max = ($Ide_Max != 10 && $Ide_Max != 13)?-1:$Ide_Max;
		$Identifica = $obBD_con1->getRowConsulta(6, $Ide_Max, $obBD_conexion);
		?>
		<?php echo $Identifica['Ide_Des'];?>
       
        </td>
       </tr>
       <tr>
       <td width="22%" class="Etiqueta1">
        
        Tipo de Contribuyente:
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?Php 
		 $opiden = $Row_Persona['Prv_Tic'] == ""?"N":$Row_Persona['Prv_Tic'];
		 if($opiden == "N")
		 {
			 echo "NATURAL";
		 } 
		 else
		 	if($opiden == "J")
		 	{
			  echo "JURIDICO";
		 	}
		 ?>
        
       </td>
       </tr>
       
      <?php
	  
      switch($opiden)
	  {
		  case "N":
	  ?>
      <tr>
       <td width="22%" class="Etiqueta1">
         
         Nombre (Razón Social):
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
        <?php echo $Row_Persona['Prs_Nom'];?>
        
       </td>
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">
         
         Apellido (Razón Social):
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
		<?php echo $Row_Persona['Prs_Ape'];?>
        
       </td>
      </tr>
      <?php
		  break;
		  case "J":
	  ?>
      <tr>
       <td width="22%" class="Etiqueta1">
         
         Raz&oacute;n Social: </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
		<?php echo $Row_Persona['Prs_Ape'];?>
        
       </td>
      </tr>
	  <?php
		  break;
	  }
	  ?>
      <tr>
	   <td width="22%" class="Etiqueta1">
        
         Nombre Comercial:
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?php echo $Row_Persona['Prv_Com'];?>
        
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
        
        Contribuyente Especial:
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
        <?php echo($Row_Persona['Prv_Esp']=='S')? "SI":"NO";?>
        
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
        
        Obligado a llevar Contabilidad:
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?php echo($Row_Persona['Prv_Con']=='S')? "SI":"NO";?>
        
       </td>
      </tr>
      <tr>
        <td class="Etiqueta1">Representante Legal: </td>
        <td class="LetraNegra">&nbsp;<?php echo $Row_Persona['Prs_Rep'];?></td>
      </tr>
     </table>
    </FIELDSET>
   
   <fieldset>
     <legend>
       <label class="Titulos2">Datos del Gerente</label>
     </legend>
     <table width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1">
        
         Nombre Gerente:&nbsp;
       
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?Php echo $Row_Persona['Prv_Nge']; ?>
       
       </td>       
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">
        
         Apellido Gerente:&nbsp;
       
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?Php echo $Row_Persona['Prv_Apg']; ?>
       
       </td>       
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">
        
         Tel&eacute;fono Gerente:&nbsp;
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?Php echo $Row_Persona['Prv_Tlg']; ?>
        
       </td>       
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">
        
         Celular Gerente:&nbsp;
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?Php echo $Row_Persona['Prv_Ceg']; ?>
        
       </td>       
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">
        
         E-mail Gerente:&nbsp;
       
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?Php echo $Row_Persona['Prv_Cog']; ?>
       
       </td>       
      </tr>
     </table>
    </fieldset>
   
   <FIELDSET>
	 <LEGEND>
	  <label class="Titulos2">SRI: Actividad Econ&oacute;mica</label>
	 </LEGEND>     
     <table width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1" >
        Actividad Econ&oacute;mica:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?Php echo $Row_Persona['Prv_Ace']; ?>
        
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
        
         Fecha Inicio de Actividades:
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?Php echo $Row_Persona['Prv_Fin']; ?>
        
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
        Fecha Cese de Actividades:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
       
	     <?Php echo $Row_Persona['Prv_Fce']; ?>
        
       </td>
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">
        Fecha de Reinicio Actividades:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	    <?Php echo $Row_Persona['Prv_Fre']; ?>
        
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
        Fecha de Actualización Actividades:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	     <?Php echo $Row_Persona['Prv_Fac']; ?>
        
       </td>
      </tr>
     </table>
    </FIELDSET>
   
   <FIELDSET>
	 <LEGEND>
	  <label class="Titulos2">Datos de Ubicaci&oacute;n</label>
	 </LEGEND>
     <table width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1">
        
        Ciudad:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
         <?php echo $Row_Persona['Ciu_Des']; ?>
        
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
       
        Direcci&oacute;n:
       
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
       
		<?Php echo $Row_Persona['Prs_Dir']; ?>
        
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
         
         Tel&eacute;fono:
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
		<?Php echo $Row_Persona['Prs_Tel']; ?>
        
       </td>    
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
        
         Tel&eacute;fono 2:
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
		<?Php echo $Row_Persona['Prs_Te2']; ?>
        
       </td>    
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
         
         Celular:
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
		<?Php echo $Row_Persona['Prs_Cel']; ?>
        
       </td>    
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
        Fax:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	    <?Php echo $Row_Persona['Prv_Fax']; ?>
        
       </td>
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">
        E-mail:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
		 <?Php echo $Row_Persona['Prs_Cor']; ?>
        
       </td>
      </tr>
     </table>
    </FIELDSET>
   
   <FIELDSET>
	 <LEGEND>
	  <label class="Titulos2">Datos de Contacto</label>
	 </LEGEND>
	 <table width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1">
        Apellidos:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
       
	    <?Php echo $Row_Persona['Prv_Act']; ?>
        
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
        Nombres:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
        
	    <?Php echo $Row_Persona['Prv_Nct']; ?>
        
       </td>
      </tr>
      <tr>
            <td width="22%" class="Etiqueta1">
              E-mail:
            </td>
            <td width="78%" class="LetraNegra">&nbsp;
              
	          <?Php echo $Row_Persona['Prv_Ect']; ?>
              
            </td>  
          </tr>     
     </table>
    </FIELDSET>
   
  	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="Azul">
    <tr>
    	<td height="20"></td>
    </tr>
     <tr>        	 
	  <td width="5%">
       <button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, 'txt_busqueda*op_opciones*hdd_volver', '<?php echo $_POST['txt_busqueda'].'*'.$_POST['op_opciones'].'*'.'1';?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atras&nbsp;&nbsp;</span>
       		 </button>&nbsp;&nbsp;
      </td>
     </tr>
    </table>
   </form>
   </FIELDSET>
   </td></tr>
  <?php
   }
  ?> 
 </table>
 </div> 
<?php
	  break;
	  case 2:
	  $Arr_Proveedor = $obBD_con1->getArrayConsulta( 9,''.'*'.$Ses_Emp_Cod, $obBD_conexion);
?>
<fieldset>
<legend>
<label class="Titulos2">Listado de Proveedores</label>
</legend>
        <table class="fixedHeader01" width="100%" align="center" border="1" cellpadding="0" cellspacing="0" id="Exportar_a_Excel">
        <thead>
        <tr >
         <th >C&eacute;dula/RUC</th>
         <th >Proveedor</th>
         <th >Nombre_Comercial</th>
         <th >Ciudad</th>
         <th >Direcci&oacute;n</th>
         <th >Tel&eacute;fono 1</th>
         <th >Tel&eacute;fono 2</th>
         <th >Correo</th>
        </tr>
        </thead>
        <tbody>
   <?php  if(count($Arr_Proveedor))
       {
		foreach($Arr_Proveedor as $row)
		{
		?>
        <tr >
          <td ><?Php echo $row['Prs_Ced']; ?></td>
          <td ><?Php echo $row['Prs_Ape'].' '.$row['Prs_Nom']; ?></td>
          <td ><?Php echo $row['Prv_Com']; ?></td>
          <td ><?Php echo $row['Ciu_Des']; ?></td>
          <td ><?Php echo $row['Prs_Dir']; ?></td>
          <td ><?Php echo $row['Prs_Tel']; ?></td>
          <td ><?Php echo $row['Prs_Te2']; ?></td>
          <td ><?Php echo $row['Prs_Cor']; ?></td>
        </tr>
        <?php }}else{?>
        <tr >
    	<td >&nbsp;</td>
    	<td >&nbsp;</td>
    	<td align="center" ><?Php echo error_alerta(" ¡No hay resultados que mostrar!", 1);?></td>
		<td >&nbsp;</td>
    	<td >&nbsp;</td>
    	<td >&nbsp;</td>
    	<td >&nbsp;</td>
    	</tr>     
	<?php }?>	
    </tbody>
        </table>
</fieldset>
<div id="set1">
<table width="300" border="0" cellpadding="0" cellspacing="0">
   <tr>
     <td width="34%">
     <form action="adq_pri_proveedor_3.0.php" method="post" name="form2" id="form2" target="_blank">
  		<button type="button" class="btn btn-primary start" title="Imprmir" onclick="this.form.submit()">
           <i class=" icon-print icon-white"></i>
           <span>Imprimir</span>
		</button>
     </form>
  </td>
  	<td>
  	<form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
  	<input type="hidden" id="datos_a_enviar" name="datos_a_enviar">
  	<button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" title="Exportar Excel">
           <i class=" icon-share icon-white"></i>
           <span>Excel</span>
	</button>
	</form>
  	</td>
   </tr>
   </table>
</div>
<?php
	  break;
  }
  ?>
   
    </td>
   </tr>
   </table>
</td>
</tr>
</table>   
  <script type="text/javascript" src="../VALIDACIONES/adq_par_proveedor.js"></script>
  <script type="text/javascript" src="../../Librerias/textbox/main.js"></script>  
  </BODY>
</HTML>
<?php
	// Cierro las conexiones
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();	
?>