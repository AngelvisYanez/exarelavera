			
              
              	<table width="100%"  border="0" cellpadding="0" cellspacing="0" >
				  <tr>
				    <td width="100%"  valign="top" height="83" colspan="2"><input type="hidden" name="Hdd_Pld_Cod" id="Hdd_Pld_Cod"/>
                      <input type="hidden" name="Hdd_Pld_Cdc" id="Hdd_Pld_Cdc"/>
                      <input type="hidden" name="Hdd_Pld_Des" id="Hdd_Pld_Des"/>
                      <input type="hidden" name="Hdd_Fila" id="Hdd_Fila"/>
                      <table width="100%" height="36%" border="0" cellpadding="0" cellspacing="0">
                        <tbody id="tbusqueda">
                          <tr class="Busqueda_contenido_ajax">
                            <td width="20%"><div align="right"><strong>Descripci&oacute;n:</strong></div></td>
                            <td width="69%"><?php 

							?>
                              <input name="txtbuscadorcom" type="text" id="txtbuscadorcom" size="40" maxlength="40" style="text-transform:uppercase" onkeypress="
                                            enter_ajax('../COMPONENTES/tesComRubrosConsulta_2.0.php?iva_cod=<?Php echo  urlencode($iva_cod); ?>&amp;iva_por=<?Php echo  urlencode($iva_por); ?>&amp;ice_cod=<?Php echo  urlencode($ice_cod); ?>&amp;ice_por=<?Php echo  urlencode($ice_por); ?>&amp;Pec_Cod=<?Php echo  urlencode($Pec_Cod); ?>&amp;buscador='+document.getElementById('txtbuscadorcom').value,'con_resultado');
                 
                            
                            " /></td>
                            <td width="100%" align="center"><input name="btn_buscarcta" type="button" class="Boton_Buscar" id="btn_buscarcta" onclick="
                            
                            
                            ajax('../COMPONENTES/tesComRubrosConsulta_2.0.php?iva_cod=<?Php echo  urlencode($iva_cod); ?>&amp;iva_por=<?Php echo  urlencode($iva_por); ?>&amp;ice_cod=<?Php echo  urlencode($ice_cod); ?>&amp;ice_por=<?Php echo  urlencode($ice_por); ?>&amp;Pec_Cod=<?Php echo  urlencode($Pec_Cod); ?>&amp;buscador='+document.getElementById('txtbuscadorcom').value,'con_resultado');
                            
                            
                            "	value="Buscar" /></td>
                          </tr>
                        </tbody>
                      </table>
                   
                    <div id="con_resultado"></div></td>
			      </tr>
				</table>
	