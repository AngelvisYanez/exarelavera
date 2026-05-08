<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php 

/**
 * Permite actualizar datos de proveedor ya sea Nacional(Cedula o Ruc) o Extranjero(Pasaporte)
 *
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualizaci�n:	2012-04-30
 *
 * @package tesoreria.FRONT
 */
	
  require_once('../../administrador/LOGICA/seguridad.php');
  require_once('../LOGICA/adq_log_proveedor.php');
  require_once('../../Librerias/procedimientos/almacenados_standar.php');
  require_once('../../Librerias/postclass.php');
  	  
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
	  
      /**
       * Llamado de la libreria para evitar el reenvio de datos
       * @var Post_Block
       */
	  $thisPost = new Post_Block;
 
  if(isset($hdd_volver))
  {
  	unset($_POST['Prv_Cod']);
  	unset($_POST['hdd_save']);
  }
	  
if (isset($_POST['hdd_save']))
{
	if ($thisPost->postBlock($_POST['postID']))
	{
		$Ide_Max = strlen($_POST['Prs_Ced']);
		$Ide_Max = ($Ide_Max != 10 && $Ide_Max != 13)?-1:$Ide_Max;
		$Identifica = $obBD_con1->getRowConsulta(6,$Ide_Max,$obBD_conexion);
		
        $Param_Proveedor = strtoupper($Prv_Com).'*'.$Prv_Esp.'*'.$Prv_Con.'*'.strtoupper($Prv_Nge).'*'.strtoupper($Prv_Apg).'*'.$Prv_Tlg.'*'.$Prv_Ceg.'*'.$Prv_Cog.'*'.strtoupper($Prv_Ace).'*'.$Prv_Fin.'*'.$Prv_Fce.'*'.$Prv_Fre.'*'.$Prv_Fac.'*'.strtoupper($Prv_Act).'*'.strtoupper($Prv_Nct).'*'.$Prv_Ect.'*'.$Prv_Fax.'*'.$Prv_Cod.'*'.$Prv_Tic.'*'.$Prv_Rep.'*'.strtoupper(trim($Prv_Tac));

        $Param_Persona = $Prs_Ced.'*'.$Identifica['Ide_Cod'].'*'.strtoupper($Prs_Nom).'*'.strtoupper($Prs_Ape).'*'.$Ciu_Cod.'*'.strtoupper($Prs_Dir).'*'.$Prs_Tel.'*'.$Prs_Te2.'*'.$Prs_Cel.'*'.$Prs_Cor.'*'.$Prs_Cod;

        $obBD_con1->updatePersonaProveedor(7,$Param_Persona,12,$Param_Proveedor);
        //UpdatePersonaProveedor(7,$Param_Persona,12,$Param_Proveedor);

        unset($Prv_Cod);
        unset($hdd_save);
}
	}	
?>
<HTML>
  <HEAD>		
	<title><?Php echo $Ses_Sys_Nom; ?></title>
  
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<script language="javascript" src="../VALIDACIONES/adq_val_proveedor.js"></script>
    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>        
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
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
			$( "#Prv_Fin" ).datepicker({
				changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});

			/* Campo 2 */
			$( "#Prv_Fce" ).datepicker({
				changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});

			/* Campo 3 */
			$( "#Prv_Fre" ).datepicker({
				changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});

			/* Campo 4 */
			$( "#Prv_Fac" ).datepicker({
				changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});
		}); 		
        </script> 
     
  </HEAD>
  <BODY>
  <div id="set1">
  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
  <tr class="BarraTitulo">
    <td height="10">&raquo; modificar proveedor</td>
   </tr>
   <tr>
    <td valign="top">
    <form action="<?Php echo $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
    <?Php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
    </form>
    </td>
  </tr>
  <tr>
  <td height="300" valign="top">
  <?Php
  if(isset($_POST['txt_busqueda']) && !isset($_POST['Prv_Cod']))
  {
  ?>
  <FIELDSET>
   <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
   </LEGEND>
    <table class="fixedHeader01" cellpadding="0" cellspacing="0" width="100%" >
    <thead>
    <tr>
	 <th width="8%">C&oacute;d. Int. </th>
     <th width="17%">R.U.C.</th>
     <th width="70%">Proveedor</th>
	 <th width="5%">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    <?
	
	$Arr_Proveedor = $obBD_con1->getArrayConsulta($op_opciones == "d"? 9 : 10,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);
    $filas = 0;
	if(count($Arr_Proveedor))
	{	
		foreach($Arr_Proveedor as $row)
		{
			$filas++;
		?>
		<tr>
		 <td align="center"><?Php echo $row['Prv_Cod']; ?></td>
		 <td><?Php echo '&nbsp;'.$row['Prs_Ced']; ?></td>
		 <td>
		  <?Php echo marcar_cadena($txt_busqueda, ($row['Prv_Tic']=='N')?'&nbsp;'.$row['Prs_Ape']." ".$row['Prs_Nom']:'&nbsp;'.$row['Prs_Ape'], '#FFFF00', 1); ?>
		 </td>
		 <td align="center">
		 <form name='form' method='post'  action='<? echo $_SERVER['PHP_SELF'];?>'>
			<button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
				<i class=" icon-arrow-right icon-white"></i>
			</button>
			<input name="Prv_Cod" id="Prv_Cod" type="hidden" value="<?Php echo $row['Prv_Cod']; ?>">
			<input name="txt_busqueda" value="<? echo $txt_busqueda;?>" type="hidden">
			<input name="op_opciones" value="<? echo $op_opciones;?>" type="hidden">
		</form>
		</td>		
	   </tr>
	  <? }}else{?> 
       <tr>
		  <td align="center">&nbsp;</td>
		  <td>&nbsp;</td>
		  <td align="center"><?Php echo error_alerta(" ¡No hay resultados que mostrar!", 1);?></td>
		  <td align="center">&nbsp;</td>
		</tr> 
	   
	<? }?>
    </tbody>
  </table>
  <?
   echo barra_estado($filas);
  ?>
  </FIELDSET>
  <? }?>
  
  
  <? if(isset($Prv_Cod) && !isset($hdd_volver)){?>
  <table cellpadding="0" cellspacing="0" width="100%" >
  <tr>
  <td>
  <FIELDSET>
   <LEGEND>
	<label class="Titulos2">Datos a Modificar</label>
   </LEGEND>
   <form method="post" name= "form" id="form" action="<? echo $_SERVER['PHP_SELF'];?>">
   <? $thisPost->startPost();?>
   <?
   	$Row_Persona = $obBD_con1->getRowConsulta(11, $Prv_Cod, $obBD_conexion);
    $listaActividad = $obBD_con1->getArrayConsulta('proveedore.selectWhere', array(
        'clean' => true,
        'unsetColsInit' => true,
        'setWhere' => array('listaTacDistinctPorEmpresa'),
        'where' => array(
            'proveedore.Emp_Cod' => $Ses_Emp_Cod,
            'proveedore.Prv_Est' => 'A',
        ),
        'order' => 'Prv_Tac ASC',
    ), $obBD_conexion);
   ?>
   <? echo mensaje_requerido();?>
   <FIELDSET>
	 <LEGEND>
	  <label class="Titulos2">Datos Generales</label>
	 </LEGEND>
     <table width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span>R.U.C.:&nbsp;</td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <input name="Prs_Ced" type="text" id="Prs_Ced" onBlur="validarDocumento(this)" 
         value="<?Php echo $Row_Persona['Prs_Ced']; ?>" size="13" maxlength="13">
         <input name="Prs_Cod" id="Prs_Cod" type="hidden" value="<?Php echo $Row_Persona['Prs_Cod']; ?>">
         <input name="Prv_Cod" id="Prv_Cod" type="hidden" value="<?Php echo $Row_Persona['Prv_Cod']; ?>">
       </td>       
      </tr>      
      <tr>
	   <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span>Tipo de Contribuyente:</td>
       <td width="78%" class="LetraNegra">&nbsp;
         <Select name="Prv_Tic" id="Prv_Tic" onChange="MostrarNJ(this)">
          <option value = "">Seleccionar...</option>
          <option value = "N" <? if($Row_Persona['Prv_Tic']=='N')echo "selected";?>>NATURAL</option>
          <option value = "J" <? if($Row_Persona['Prv_Tic']=='J')echo "selected";?>>JURIDICO</option>
         </Select>
       </td>
      </tr>
      <? $opiden = $Row_Persona['Prv_Tic'] == ""?"N":$Row_Persona['Prv_Tic'];?>      
      <tr id="Natural">
       <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span>Nombre (Raz&oacute;n Social):</td>
       <td width="78%" class="LetraNegra">&nbsp;
        <input name="Prs_Nom" type="text" id="Prs_Nom" style="text-transform:uppercase" value="<? echo $Row_Persona['Prs_Nom'];?>" size="30" maxlength="80" />
       </td>
      </tr>      
      <tr>
       <td width="22%" class="Etiqueta1">        
         <span class="Asterisco">*</span> 
         <label id="Natural_a">Apellido (Raz&oacute;n Social):</label>         
         <label id="Juridico">Raz&oacute;n Social:</label>        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
		<input name="Prs_Ape" type="text" id="Prs_Ape" style="text-transform:uppercase" value="<? echo $Row_Persona['Prs_Ape'];?>" size="50" maxlength="50" />
       </td>
      </tr>      
      <tr>
	   <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span>Nombre Comercial:</td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <input name="Prv_Com" type="text" id="Prv_Com" 
         style="text-transform:uppercase" value="<? echo $Row_Persona['Prv_Com'];?>" size="50" maxlength="100"/>
       </td>
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">Tipo Actividad:</td>
       <td width="78%" class="LetraNegra">&nbsp;
         <select name="Prv_Tac" id="Prv_Tac" data-placeholder="— Seleccionar —" style="width: 360px;">
           <option value=""></option>
           <?php foreach ($listaActividad as $row) {
                $tac = isset($row['Prv_Tac']) ? $row['Prv_Tac'] : '';
                $tacEsc = htmlspecialchars((string) $tac, ENT_QUOTES, 'UTF-8');
                $selected = (isset($Row_Persona['Prv_Tac']) && trim($Row_Persona['Prv_Tac']) === trim($tac)) ? 'selected' : '';
                echo '<option value="' . $tacEsc . '" ' . $selected . '>' . $tacEsc . '</option>';
            } ?>
         </select>
         <button type="button" class="btn btn-info btn-mini" title="Agregar actividad" onclick="abrirDialogoPrvTac(); return false;" style="margin-left:6px;">
           <i class="icon-plus icon-white"></i>
         </button>
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span>Contribuyente Especial:</td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <Select name="Prv_Esp" id="Prv_Esp">
          <option value = "">Seleccionar...</option>
          <option value = "S" <? if($Row_Persona['Prv_Esp']=='S')echo "selected";?>>SI</option>
          <option value = "N" <? if($Row_Persona['Prv_Esp']=='N')echo "selected";?>>NO</option>
         </Select>
       </td>
      </tr>
      <tr>
        <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span>Obligado a llevar Contabilidad:</td>
        <td width="78%" class="LetraNegra">&nbsp;
          <Select name="Prv_Con" id="Prv_Con">
            <option value = "">Seleccionar...</option>
            <option value = "S" <? if($Row_Persona['Prv_Con']=='S')echo "selected";?>>SI</option>
            <option value = "N" <? if($Row_Persona['Prv_Con']=='N')echo "selected";?>>NO</option>
          </Select>
        </td>
      </tr>
      <tr>
        <td class="Etiqueta1">Representante Legal: </td>
        <td class="LetraNegra">&nbsp;
          <input name="Prv_Rep" type="text" id="Prv_Rep" style="text-transform:uppercase" value="<? echo $Row_Persona['Prv_Rep'];?>" size="66" maxlength="100"/>
        </td>
      </tr>
     </table>     
    </FIELDSET>
    <? if($opiden=="J"){?>
   <script language="javascript">
   		ShowHide('Natural');
		ShowHide('Natural_a');
   </script>
   <? }else{ if($opiden=="N"){
	?>
	   <script language="javascript">
   		ShowHide('Juridico');
   </script>
	<? }}?>
    
    <fieldset>
     <legend>
       <label class="Titulos2">Datos del Gerente</label>
     </legend>
     <table width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
       <td width="22%" class="Etiqueta1">Nombre Gerente:&nbsp;</td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <input name="Prv_Nge" type="text" id="Prv_Nge" style="text-transform:uppercase" value="<?Php echo $Row_Persona['Prv_Nge']; ?>" size="30" maxlength="80"/>
       </td>       
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">Apellido Gerente:&nbsp;</td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <input name="Prv_Apg" type="text" id="Prv_Apg" style="text-transform:uppercase" value="<?Php echo $Row_Persona['Prv_Apg']; ?>" 
         size="30" maxlength="80"/>
       </td>       
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">Tel&eacute;fono Gerente:&nbsp;</td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <input name="Prv_Tlg" type="text" id="Prv_Tlg" style="text-transform:uppercase" value="<?Php echo $Row_Persona['Prv_Tlg']; ?>" 
         size="15" maxlength="15" onBlur="numerico(this)"/>
       </td>       
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">Celular Gerente:&nbsp;</td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <input name="Prv_Ceg" type="text" id="Prv_Ceg" style="text-transform:uppercase" value="<?Php echo $Row_Persona['Prv_Ceg']; ?>" 
         size="15" maxlength="15" onBlur="numerico(this)"/>
       </td>       
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">E-mail Gerente:&nbsp;</td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <input name="Prv_Cog" type="text" id="Prv_Cog" value="<?Php echo $Row_Persona['Prv_Cog']; ?>" 
         size="30" maxlength="80" onblur="correo(this);"/>
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
	     <textarea id="Prv_Ace" name="Prv_Ace" cols="40" style="text-transform:uppercase"><?Php echo $Row_Persona['Prv_Ace']; ?></textarea>
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
        
         <span class="Asterisco">*</span> 
         Fecha Inicio de Actividades:
        
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <input name="Prv_Fin" type="text" id="Prv_Fin" value="<?Php echo $Row_Persona['Prv_Fin']; ?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/></td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
         Fecha Cese de Actividades:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <input name="Prv_Fce" type="text" id="Prv_Fce" value="<?Php echo $Row_Persona['Prv_Fce']; ?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
	 	</td>
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">
        Fecha de Reinicio Actividades:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
	    <input name="Prv_Fre" type="text" id="Prv_Fre" value="<?Php echo $Row_Persona['Prv_Fre']; ?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>
	    </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">
        Fecha de Actualización Actividades:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
	     <input name="Prv_Fac" type="text" id="Prv_Fac" value="<?Php echo $Row_Persona['Prv_Fac']; ?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"/>	     
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
        <span class="Asterisco">*</span> Ciudad:
       </td>
       <td width="78%" class="LetraNegra">&nbsp;
		<select name="Ciu_Cod" id="Ciu_Cod">
        <?php 
		$arr_Ciudad = $obBD_con1->getArrayConsulta(3, '', $obBD_conexion);
		foreach($arr_Ciudad as $row)
		{
		?>
         <option value="<?php echo $row['Ciu_Cod']; ?>" 
		 <?Php if($Row_Persona['Ciu_Cod'] == $row['Ciu_Cod'])echo "selected"; ?>>
         <?php echo $row['Ciu_Des']; ?>
         </option>
         <?php
		}
		?>
        </select>
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span>Direcci&oacute;n:</td>
       <td width="78%" class="LetraNegra">&nbsp;
		<input name="Prs_Dir" type="text" id="Prs_Dir" style="text-transform:uppercase" value="<?Php echo $Row_Persona['Prs_Dir']; ?>" size="66" maxlength="80" />
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1"><span class="Asterisco">*</span>Tel&eacute;fono:</td>
       <td width="78%" class="LetraNegra">&nbsp;
		<input name="Prs_Tel" type="text" id="Prs_Tel" style="text-transform:uppercase" value="<?Php echo $Row_Persona['Prs_Tel']; ?>" size="15" 
        maxlength="15" onBlur="numerico(this)"/>
       </td>    
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">Tel&eacute;fono 2:</td>
       <td width="78%" class="LetraNegra">&nbsp;
		<input name="Prs_Te2" type="text" id="Prs_Te2" style="text-transform:uppercase" value="<?Php echo $Row_Persona['Prs_Te2']; ?>" size="15" 
        maxlength="15" onBlur="numerico(this)"/>
       </td>    
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">Celular:</td>
       <td width="78%" class="LetraNegra">&nbsp;
		<input name="Prs_Cel" type="text" id="Prs_Cel" style="text-transform:uppercase" value="<?Php echo $Row_Persona['Prs_Cel']; ?>" size="15" 
        maxlength="15" onBlur="numerico(this)"/>
       </td>    
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">Fax:</td>
       <td width="78%" class="LetraNegra">&nbsp;
	    <input name="Prv_Fax" type="text" id="Prv_Fax" style="text-transform:uppercase" value="<?Php echo $Row_Persona['Prv_Fax']; ?>" size="15" 
        maxlength="15" onBlur="numerico(this)"/>
       </td>
      </tr>
      <tr>
       <td width="22%" class="Etiqueta1">E-mail:</td>
       <td width="78%" class="LetraNegra">&nbsp;
		 <input name="Prs_Cor" type="text" id="Prs_Cor" value="<?Php echo $Row_Persona['Prs_Cor']; ?>" size="30" onblur="correo(this);"/>
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
       <td width="22%" class="Etiqueta1">Apellidos:</td>
       <td width="78%" class="LetraNegra">&nbsp;
	    <input name="Prv_Act" type="text" id="Prv_Act" style="text-transform:uppercase" value="<?Php echo $Row_Persona['Prv_Act']; ?>" size="30" 
        maxlength="80"/>
       </td>
      </tr>
      <tr>
	   <td width="22%" class="Etiqueta1">Nombres:</td>
       <td width="78%" class="LetraNegra">&nbsp;
	    <input name="Prv_Nct" type="text" id="Prv_Nct" style="text-transform:uppercase" value="<?Php echo $Row_Persona['Prv_Nct']; ?>" size="30" 
        maxlength="80"/>
       </td>
      </tr>
      <tr>
        <td width="22%" class="Etiqueta1">E-mail:</td>
        <td width="78%" class="LetraNegra">&nbsp;
          <input name="Prv_Ect" type="text" id="Prv_Ect" value="<?Php echo $Row_Persona['Prv_Ect']; ?>" size="30" onblur="correo(this);"/>
        </td>  
      </tr>     
     </table>
    </FIELDSET>
    
   <table border="0" cellpadding="0" cellspacing="0">
   <tr> 
      <td width="114">
      		 <button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, 'txt_busqueda*op_opciones*hdd_volver', '<? echo $_POST['txt_busqueda'].'*'.$_POST['op_opciones'].'*'.'1';?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>&nbsp;&nbsp;
      </td>
      <td width="186">
          <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_persona(this.form);">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
           </button>
      </td>
    </tr>
    </table>
	<input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
    
   </form>
   </FIELDSET>
   </td>
   </tr>
   </table>
  <? } ?> 
  
  <div id="prvTacDialog" title="Nueva actividad económica" style="display:none;">
    <table width="100%" border="0" cellpadding="2" cellspacing="0">
      <tr>
        <td class="Etiqueta1">Actividad:</td>
      </tr>
      <tr>
        <td>
          <input type="text" id="prvTacDescInput" class="textbox" maxlength="20" style="width:100%; text-transform:uppercase;" />
          <div style="font-size:11px; color:#666; text-align:right; margin-top:4px;">
            <span id="prvTacDescCount">0</span> / 20 caracteres
          </div>
          <div id="prvTacDescErr" style="display:none; color:#C0392B; font-size:11px; margin-top:4px;"></div>
        </td>
      </tr>
    </table>
  </div>
  
  
  
 
  
  
  
  
  
  </td>
 </tr>
 </table>
   </div>
   <script type="text/javascript">
    function actualizarContadorPrvTacMod() {
      $('#prvTacDescCount').text($('#prvTacDescInput').val().length);
    }

    function abrirDialogoPrvTac() {
      $('#prvTacDescInput').val('');
      $('#prvTacDescErr').hide().text('');
      actualizarContadorPrvTacMod();
      $('#prvTacDialog').dialog('open');
      setTimeout(function(){ $('#prvTacDescInput').focus(); }, 50);
    }

    function aplicarNuevaPrvTacMod() {
      var desc = $.trim($('#prvTacDescInput').val()).toLocaleUpperCase('es');
      if (!desc.length) {
        $('#prvTacDescErr').text('Ingrese una descripción.').show();
        return false;
      }
      if (desc.length > 20) {
        $('#prvTacDescErr').text('La actividad no puede superar 20 caracteres.').show();
        return false;
      }
      var $sel = $('#Prv_Tac');
      var $match = null;
      $sel.find('option').each(function(){
        var v = $(this).val();
        if (v && $.trim(v).toLocaleUpperCase('es') === desc) {
          $match = $(this);
          return false;
        }
      });
      if ($match && $match.length) {
        if ($match.val() !== desc) $match.attr('value', desc).text(desc);
      } else {
        $sel.append($('<option></option>').attr('value', desc).text(desc));
      }
      $sel.val(desc).trigger('chosen:updated');
      return true;
    }

    $(function() {
      if ($('#Prv_Tac').length) {
        $('#Prv_Tac').chosen({width: '360px', search_contains: true, no_results_text: 'Sin resultados'});
      }
      $('#prvTacDialog').dialog({
        autoOpen: false,
        modal: true,
        resizable: false,
        width: 430,
        buttons: {
          "Agregar": function() {
            $('#prvTacDescErr').hide().text('');
            if (aplicarNuevaPrvTacMod()) $(this).dialog('close');
          },
          "Cancelar": function() {
            $(this).dialog('close');
          }
        }
      });
      $(document).on('input', '#prvTacDescInput', actualizarContadorPrvTacMod);
      $(document).on('keydown', '#prvTacDescInput', function(ev){
        if (ev.keyCode === 13) {
          ev.preventDefault();
          $('#prvTacDescErr').hide().text('');
          if (aplicarNuevaPrvTacMod()) $('#prvTacDialog').dialog('close');
        }
      });
    });
   </script>
    <script type="text/javascript" src="../VALIDACIONES/adq_par_proveedor.js"></script>
  <script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
  </BODY>
</HTML>
<?php
	// Cierro las conexiones
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();	
?>