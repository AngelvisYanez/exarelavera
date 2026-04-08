<?Php
/*
Ajax que permite cargar:
Surcursales	= 	Todas
Modalidad	=	Todas
Etapa		=	Todas
Anio 		=   Actual para el registro de periodos
*/

?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos Acad&eacute;micos:</label>
</LEGEND>
<table width="100%" border="0">
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Sucursal:</td>
    <td class="LetraNegra">
    <?Php  
	/*** Consultar las sucursales de la universidad *************/
		$rs_sucursales= $obBD_con1->consulta(sentencias_com(101, ''), $obBD_conexion->conexion);
		$row_rs_sucursales= $obBD_con1->registros();
		$total_row_rs_sucursales = $obBD_con1->numregistros();
	
	?>
    
    <select name="Suc_Cod" id="Suc_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&op=<?Php echo $op; ?>&Suc_Cod=' + this.value,'div_sucursales')">
      <option>Seleccione...</option>
      <?Php do{ ?>
      <option style="text-transform:uppercase" value="<?Php echo $row_rs_sucursales['Suc_Cod']; ?>" ><?Php echo $row_rs_sucursales['Suc_Des']; ?></option>
	  <?Php }while($row_rs_sucursales=$obBD_con1->fetch_assoc($rs_sucursales)); ?>      
    </select></td>
  </tr>
  <tr>
    <td width="11%" class="Etiqueta1"><span class="Asterisco" >* </span>Modalidad:</td>
    <td width="89%" class="LetraNegra">
    <div id="div_sucursales" >
    <select name="Mod_Cod" id="Mod_Cod">
        <option></option>
      </select>
    </div>	</td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Etapa:</td>
    <td class="LetraNegra">
	<div id="div_etapa">
      <select name="Eta_Cod" id="Eta_Cod">
        <option></option>
      </select>
    </div></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Anio:</td>
    <td class="LetraNegra"><?Php
	     /*** consulto los años de los periodos *******/
		 $rs_anio_periodo = $obBD_con1->consulta(sentencias(562, $obBD_con1->parametros('')), $obBD_conexion->conexion);
		 $row_rs_anio_periodo = $obBD_con1->registros();
		 $total_rs_anio_periodo = $obBD_con1->numregistros();
		 /*********** *************************************/
		 $anio_actual=date('Y-m-d');
		 $anio_actual=substr($anio_actual,0,4);
		 $anio=array();
		 /*** Creo un vector de años  ===>rs_anio_periodo ********************/
		 $con=0;
		 $ann_b=0;
		 do { 
		 	$ann=trim(substr($row_rs_anio_periodo['Per_Fea'],0,strlen($row_rs_anio_periodo['Per_Fea'])-6));
			if($anio_actual==$ann)
			{	$ann_b=$ann;
				if($anio[$con-1]!=$ann_b)
					{	$anio[$con]=$ann; 	
						$con++;		
					}
				}
			 }while($row_rs_anio_periodo=$obBD_con1->fetch_assoc($rs_anio_periodo));  
				 	/*** Defino años para los periodos anteriores (Momentaneamente) **************/
					
					/*** Incremento en 1 del año ***************/
					$anio_actual=date('Y');
					/*** Ojo borrar el año es solo por semestral ***************/
					array_push($anio,$anio_actual+1);
				?>
                <select name="Per_Ani" id="Per_Ani" >
                  <option></option>

				  <?Php for($con=0; $con<count($anio); $con++) { ?>
                  <option value="<?Php  echo $anio[$con];   ?>" ><?Php echo $anio[$con];  ?></option>
                  <?Php }  ?>				  
                </select></td>
  </tr>
</table>
</LEGEND>
</FIELDSET>