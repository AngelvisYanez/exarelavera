<?Php
require_once('../../componentes/LOGICA/logica.php');

/* Componente para la busqueda de la Sucursal, Modalidad, Etapa, Periodo, Carrera, Curso 
Utiliza el componentes Ajax ==> ajaxnotAutorSucModEtaPerCarCurAsi.php 
Sucursal= Carga todas las sucursales abiertas por la universidad activas
Modalidad = todas las activas
Etapa = todas las activas
Periodo = todos los periodos activos desde el primero dia de clase al ultimo de clases + dias adicionales
Carreras = todas las abiertas en el periodo

*/

$hoy = date("Y-m-d");
/* Consultar las sucursales de la universidad */
$rs_sucursales= $obBD_con1->consulta(sentencias_com(101, ''), $obBD_conexion->conexion);
$row_rs_sucursales= $obBD_con1->registros();
$total_row_rs_sucursales = $obBD_con1->numregistros();	
/* Consultar las modalidades de estudio */
$rs_modalidad= $obBD_con1->consulta(sentencias_com(1, ''), $obBD_conexion->conexion);
$row_rs_modalidad= $obBD_con1->registros();
$total_row_rs_modalidad = $obBD_con1->numregistros();	
/* Consultar las etapas principales, excepto las de nivelacion */
$rs_etapas= $obBD_con1->consulta(sentencias_com(3, ''), $obBD_conexion->conexion);
$row_rs_etapas= $obBD_con1->registros();
$total_row_rs_etapas = $obBD_con1->numregistros();	
?>
<FIELDSET>
<LEGEND>
    <label class="Titulos2">Buscar por:</label>
</LEGEND>		
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Sucursal:&nbsp;</td>
      <td colspan="2"><select name="Suc_Cod" id="Suc_Cod" style="text-transform:uppercase" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&op=<?Php echo $op; ?>&var_suc_cod=' + this.value,'div_modalidad')">
        	<?Php
			if ($total_row_rs_sucursales > 1){ ?>
				<option></option>
        <?Php }//Fin del if ($total_row_rs_sucursales > 1)
			else
			{
				$var_suc_cod = $row_rs_sucursales['Suc_Cod']; 
			}//Fin del else if ($total_row_rs_sucursales > 1)
		 
        	do{ ?>
		        <option value="<?Php echo $row_rs_sucursales['Suc_Cod']; ?>"><?Php echo $row_rs_sucursales['Suc_Des']; ?></option>  	            
	<?Php   }while($row_rs_sucursales = $obBD_con1->fetch_assoc($rs_sucursales)); ?>
        </select>        </td>
      </tr>
      <tr>
        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Modalidad:&nbsp;</td>
        <td colspan="2"><div id="div_modalidad"><select name="Mod_Cod" id="Mod_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&var_mod_cod=' + this.value+'&var_suc_cod=<?Php echo $var_suc_cod; ?>','div_etapa')">
          <?Php
		  	/* Si hay mas de 1 modalidades cargadas entonces debe cargarse con ajax */
			if ($total_row_rs_modalidad > 1)
			{ ?>
          		<option></option>
          <?Php }//Fin del if ($total_row_rs_sucursales > 1) 
		  	else 
			{
				$var_mod_cod = $row_rs_modalidad['Mod_Cod'];	  
			}//Fin del else if ($total_row_rs_modalidad > 1)
		  	do{ ?>
			  <option value="<?Php echo $row_rs_modalidad['Mod_Cod']; ?>"><?Php echo $row_rs_modalidad['Mod_Des']; ?></option>
			  <?Php   
		    }while($row_rs_modalidad = $obBD_con1->fetch_assoc($rs_modalidad));

 			 ?>
			</select></div></td>
      </tr>
      <tr>
        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Etapa:&nbsp;</td>
        <td width="5%"><div id="div_etapa"><select name="Eta_Cod" id="Eta_Cod" style="text-transform:uppercase"  onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&var_mod_cod=<?Php echo $var_mod_cod; ?>&Eta_Cod='+this.value+'&var_suc_cod=<?Php echo $var_suc_cod; ?>','div_periodo')">
          <?Php
  		  	/* Si hay mas de 1 etapa cargadas entonces debe cargarse con ajax */
			if ($total_row_rs_etapas > 1)
			{ ?>
          <option></option>
          <?Php }//Fin del if ($total_row_rs_sucursales > 1)
			else 
			{
				$var_eta_cod = $row_rs_etapas['Eta_Cod'];
			}//Fin del else if ($total_row_rs_etapas > 1)
		  	do{ ?>
          <option value="<?Php echo $row_rs_etapas['Eta_Cod']; ?>"><?Php echo $row_rs_etapas['Eta_Des']; ?></option>
          <?Php   
		    }while($row_rs_etapas = $obBD_con1->fetch_assoc($rs_etapas));	  
 		 ?>
        </select></div></td>
        <td width="77%"><span class="LetraNegra">
        <input name="chk_otras_etapas" type="checkbox" id="chk_otras_etapas" onclick="if (this.checked){ document.getElementById('hdd_sql').value = 2; }
																else { document.getElementById('hdd_sql').value = 3; }; ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&var_mod_cod=' + document.getElementById('Mod_Cod').value+'&var_eta_cod='+this.value+'&var_suc_cod=<?Php echo $var_suc_cod; ?>','div_etapa')" value="checkbox" />
Otras Etapas
<!-- 
2 = Etapas nivelacion
3 = Etapas semestrales -->
<input name="hdd_sql" type="hidden" id="hdd_sql" value="3" />
        </span></td>
      </tr>
      <tr>
        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Periodo:&nbsp;</td>
        <td colspan="2"><div id="div_periodo"><select name="Per_Int" id="Per_Int">
          <?Php 
			/* Solo en caso de existir 1 registro en Sucursales, Modalidad y Etapas, se debe cargar el periodo */
			if ($total_row_rs_sucursales == 1 && $total_row_rs_modalidad == 1 && $total_row_rs_etapas == 1)
			{ 
				$Eta_Arr = explode('*',$var_eta_cod);
				/* Consulta los periodos en base a las sucursales, modalidades y etapas */
				$rs_periodos = $obBD_con1->consulta(sentencias_com(14, $obBD_con1->parametros(
									$var_suc_cod.'*'.$var_mod_cod.'*'.$Eta_Arr[0].'*'.$hoy)), $obBD_conexion->conexion);
		 		$row_rs_periodos = $obBD_con1->registros();
	 			$total_rs_periodos = $obBD_con1->numregistros();			
				$var_per_int = $row_rs_periodos['Per_Int'];
			
				do{ ?> 
		          <option value="<?Php echo $row_rs_periodos['Per_Int']; ?>">
				  				<?Php if ($total_rs_periodos > 0){ echo $row_rs_periodos['Mes_Ini']."/".$row_rs_periodos['Ann_Ini']." - ".
			  $row_rs_periodos['Mes_Fin']."/".$row_rs_periodos['Ann_Fin']; } ?></option>
        	  <?Php   
		    	}while($row_rs_periodos = $obBD_con1->fetch_assoc($rs_periodos)); 
			}//Fin del if ($total_row_rs_sucursales > 1) 
			else
			{ ?>
				<option></option>
			<?Php } ?>
        </select></div></td>
      </tr>
      <tr>
        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Carrera:&nbsp;</td>
        <td colspan="2"><div id="div_carrera"><select name="Car_Int" id="Car_Int" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_nivel=1&var_car_int=' + this.value +'&var_per_int=<?php echo $var_per_int; ?>', 'div_cursos')">
   			<?Php 
			/* Solo carga las carreras en caso de encontrar un periodo cargado en el combo */			
			if ($total_rs_periodos == 1)
			{ 
				$Eta_Arr = explode('*',$var_eta_cod);
				/* Consulta las carreras en base al periodo y la etapa */
				$rs_carreras = $obBD_con1->consulta(sentencias_com(102, $obBD_con1->parametros($var_per_int.'*'.$Eta_Arr[0])), $obBD_conexion->conexion);
				$row_rs_carreras = $obBD_con1->registros();
				$total_rs_carreras = $obBD_con1->numregistros();
				$var_car_int = $row_rs_carreras['Car_Int'];
				
				/* Se debe cargar TODAS las carreras encontradas, pero en caso de haber mas de 1, se agrega un espacio
				en blanco solo para obligar la seleccion */
				if ($total_rs_carreras > 1)
				{ ?>
					<option></option>
					<?Php
				}
					do{ ?> 
			          <option value="<?Php echo $row_rs_carreras['Car_Int']; ?>">
				  				<?Php echo $row_rs_carreras['Car_Nom']; ?></option>
        		  <?Php   
		    		}while($row_rs_carreras = $obBD_con1->fetch_assoc($rs_carreras)); 							
			}//Fin del if ($total_row_rs_sucursales > 1) 
			else
			{  ?>
				<option></option>
			<?Php } ?>
        </select></div></td>
      </tr>
    </table>
</FIELDSET>
<?Php
@$obBD_con1->free_result($rs_sucursales);
@$obBD_con1->free_result($rs_modalidad);
@$obBD_con1->free_result($rs_etapas);
@$obBD_con1->free_result($rs_periodos);
@$obBD_con1->free_result($rs_carreras);
//@$obBD_con1->free_result($rs_distributi);
?>