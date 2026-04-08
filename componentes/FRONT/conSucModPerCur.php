<!DOCTYPE FIELDSET PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<FIELDSET>
				<LEGEND>
				    <label class="Titulos2">Buscar por:</label>
				</LEGEND>
				
				    <table width="100%" border="0" cellpadding="0" cellspacing="0">
				      <tr>
					        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Sucursal:&nbsp;</td>
					      	<td width="82%" colspan="2">
						      	<?php 
							      	/**
							      	 * Consultar las sucursales de la universidad
							      	 */
							      	$rs_sucursales= $obBD_con1->consulta(sentencias_com(101, $obBD_con1->parametros($Ses_Emp_Cod)), $obBD_conexion->conexion);
						      	?>
						      	<select name="Suc_Cod" id="Suc_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&var_suc_cod=' + this.value,'div_modalidad')">
										<option value="">Seleccione...</option>
									   <?php
									   
									   while($row_rs_sucursales=$obBD_con1->fetch_assoc($rs_sucursales))
									   { 
									   ?>
									        <option style="text-transform:uppercase" value="<?Php echo $row_rs_sucursales['Suc_Cod'];?>"><?Php echo $row_rs_sucursales['Suc_Des']; ?></option>  	            
									   <?Php 
									   }
									   unset($rs_sucursales);
									   ?>
						        </select>        
					        </td>
				      </tr>
				      <tr>
					        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Modalidad:&nbsp;</td>
					        <td colspan="2"><div id="div_modalidad">
						        <!-- M O D A L I D A D -->
						        <select name="Mod_Cod" id="Mod_Cod" >
						        <option></option>            
						        </select>
						        </div>
					        </td>
				      </tr>      
				      <tr>
					        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Periodo:&nbsp;</td>
					        <td colspan="2"><div id="div_periodo">
						        <!-- P E R I O D O S -->
						        <select name="Per_Int" id="Per_Int">
									<option></option>
								</select></div>
							</td>
				      </tr>      
				      <tr>
					        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Cursos:&nbsp; </td>
					        <td colspan="2"><div id="div_cursos">
						         <!-- C U R S O S -->
						         <select name="Sem_Cod" id="Sem_Cod">
									<option></option>
						         </select></div>
					        </td>
				      </tr>
				    </table>
			</FIELDSET>