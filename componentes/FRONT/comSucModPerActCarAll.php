<?php 
/**
 * Componente para la busqueda de Sucursal-Modalidad-Periodo Actual-Todas las carreras
 * Se utiliza el componentes Ajax ==> ajaxSucModPerActCarAll.php 
 * Sucursal= Carga todas las sucursales de la empresa
 * Modalidad = todas
 * Periodo = en base a la modalidad
 * Carreras = Filtra carreras que se habrirán en el periodo lectivo por curso
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * @Fecha de actualización:	2014-04-17
 *
 * @package componente.FRONT
 */
  require_once('../../componentes/LOGICA/logica.php');

 /**
 * objeto para conexion
 * @var Class_Log_Conexion_Com
 */
 $obBD_conexion11 = new Class_Log_Conexion_Com;
/**
 * objeto para consultas
 * @var Class_Log_Datos_Adm
 */
 $obBD_con11 =  new  Class_Log_Datos_Com;
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del periodo:</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Sucursal:</td>
    <td class="LetraNegra">
    <?Php  
	/**
	 * Array de las sucursales 
	 */
	$rs_sucursales= $obBD_con11->getArrayConsulta(101, $Ses_Emp_Cod, $obBD_conexion11);
	if (count($rs_sucursales) == 1)
	{
		$Suc_Cod = $rs_sucursales[0]['Suc_Cod'];
		?>
        <input name="Suc_Des" id="Suc_Des" type="text" value="<?Php echo $rs_sucursales[0]['Suc_Des']; ?>" readonly="readonly" size="50" style="border:none; background:none" />
       <input name="Suc_Cod" id="Suc_Cod" type="text" value="<?Php echo $rs_sucursales[0]['Suc_Cod']; ?>" size="1" style="
            visibility:hidden" />
	<?php	
		$Com_Hoy=  date("Y-m-d");
	}
	else
	{
	   ?>
        <select name="Suc_Cod" id="Suc_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&Com_Hoy=<?php echo date("Y-m-d"); ?>&Suc_Cod=' + this.value,'div_sucursales')">
      <option value="">Seleccione...</option>
      <?Php	  
	  foreach($rs_sucursal as $row_rs_sucursales )
      { 
      ?>
      		<option value="<?Php echo $row_rs_sucursales['Suc_Cod']; ?>" ><?Php echo $row_rs_sucursales['Suc_Des']; ?></option>
	  <?Php 
      }
      ?>     
    </select><?
	 }
		?>
	</td>
  </tr>
  <tr>
    <td width="16%" class="Etiqueta1"><span class="Asterisco" >* </span>Modalidad:</td>
    <td width="84%" class="LetraNegra">
      <div id="div_sucursales" >
      <? 
	 /**
		 * Combo donde carga modalidad, si hay más de 1 registro
		 * Carga el combo caso contrario solo 
		 */
	  $rs_sucursal= $obBD_con11->getArrayConsulta(302, $Ses_Emp_Cod, $obBD_conexion11);
	  if (count($rs_sucursales) == 1)
	  {		
		$Mod_Cod = $rs_sucursal[0]['Mod_Cod'];
		?>
        <input name="Mod_Des" id="Mod_Des" type="text" value="<?Php echo $rs_sucursal[0]['Mod_Des']; ?>" readonly="readonly" size="50" style="border:none; background:none" />       
        <input name="Mod_Cod" id="Mod_Cod" type="text" value="<?Php echo $rs_sucursal[0]['Mod_Cod']; ?>" size="1" style="
            visibility:hidden" />
	    <?Php
		$Com_Hoy=  date("Y-m-d");
	  }
	  else
	  {
	  ?>    
        <select name="Mod_Cod" id="Mod_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&Com_Hoy=<?php echo date("Y-m-d"); ?>&Suc_Cod=' + this.value,'div_sucursales')">
      <option value="">Seleccione...</option>
      <?Php	  
	  foreach($rs_sucursal as $row_rs_sucursales )
      { 
      ?>
      		<option value="<?Php echo $row_rs_sucursales['Mod_Cod']; ?>" ><?Php echo $row_rs_sucursales['Mod_Des']; ?></option>
	  <?Php 
      }
      ?>     
    </select>
      <?
	  }
	  ?>
      </div>	
      </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Periodo:</td>
    <td class="LetraNegra"><div id="div_periodo">
    <? 
	 $rs_periodos = $obBD_con11->getArrayConsulta(300, $Mod_Cod.'*'.$Com_Hoy.'*'.$Suc_Cod, $obBD_conexion11);
	if( count($rs_periodos) == 1)
	{
		$Per_Int = $rs_periodos[0]['Per_Int']; ?>
        <input name="Per_Nom" id="Per_Nom" type="text" value="<?Php echo $rs_periodos[0]['Mes_Ini']."/".$rs_periodos[0]['Ann_Ini']." - ".
			  $rs_periodos[0]['Mes_Fin']."/".$rs_periodos[0]['Ann_Fin'].' ['.$rs_periodos[0]['Eta_Des'].']';	 ?>" readonly="readonly" size="50" style="border:none; background:none" />
		<input name="Per_Int" id="Per_Int" type="text" value="<?Php echo $rs_periodos[0]['Per_Int']; ?>"  size="1" style="
            visibility:hidden" />              
	<?Php		  	  
		$Com_Hoy=  date("Y-m-d");
	}
	else
	{
	?>
     <select name="Per_Int" id="Per_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_carrera=1&Suc_Cod=<?Php echo $Suc_Cod; ?>&Mod_Cod=<?Php echo $Mod_Cod; ?>&Per_Int='+this.value, 'div_carrera')">
	 <option value="">Seleccione...</option>
	 <?Php foreach($rs_periodos as $row){ ?>
	  	 <option value="<?Php echo $row['Per_Int']; ?>"><?Php if (count($rs_periodos) > 0) {
		 echo $row['Mes_Ini']."/".$row['Ann_Ini']." - ".
			  $row['Mes_Fin']."/".$row['Ann_Fin'].' ['.$row['Eta_Des'].']'; } ?>
	     </option>
	 <?Php } ?>	 
	 </select>	 
	 <?Php
		 if (count($rs_periodos) == 0)
		 {
	 		echo "<span class='Texto_Reporte_Rojo'>&iexcl;No existen periodos Activos &oacute; la fecha m&aacute;xima de matr&iacute;cula ha 
			caducado!</span>";
		 }//Fin del if ($total_rs_periodo == 0)
	 }
	?>
    </div></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Carrera:</td>
    <td><div id="div_carrera">
    <?
	/** 
	* Consultar la etapa en base al periodos
	*/
	$row_rs_etapa= $obBD_con11->getRowConsulta(16, $Per_Int, $obBD_conexion11);
   /**
	 * Combo donde carga carrera, si hay más de 1 registro
	 * Carga el combo caso contrario solo 
	 */
	$rs_carreras_etapa=$obBD_con11->getArrayConsulta(112, $Ses_Emp_Cod.'*'.$row_rs_etapa['Eta_Cod'], $obBD_conexion11);

	if(count($rs_carreras_etapa)==1)
	{ ?>
	    <input name="Car_Nom" id="Car_Nom" type="text" value="<?Php echo $rs_carreras_etapa[0]['Car_Nom']; ?>" readonly="readonly" size="50" style="border:none; background:none" />
        <input name="Car_Int" id="Car_Int" type="text" value="<?Php echo $rs_carreras_etapa[0]['Car_Int']; ?>" size="1" style="
            visibility:hidden" />
      <?Php        		
		$Car_Int = $rs_carreras_etapa[0]['Car_Int'];
	}
	else
	{
	  ?>
     <select name="Car_Int" id="Car_Int">
       <option value="">Seleccione...</option>
       <?Php foreach($rs_carreras_etapa as $rows) { ?>
       <option <?Php if ($Car_Int == $rows['Car_Int']){ echo "selected"; } ?> value="<?Php echo $rows['Car_Int'];  ?>"> <?Php echo $rows['Car_Nom']; ?> </option>
       <?Php }  ?>
     </select>      <?	
	}
	?>
    </div></td>
  </tr>
</table>
</FIELDSET>