<?php
/**
* Descripción: Componente buscador de productos
* Fecha de actualización:	11-08-12
* Desarrollador:	Lewis Chimarro
*/	
?>	
<FIELDSET>
      <LEGEND>
        <label class="Titulos2">B&uacute;squeda de productos: </label>
      </LEGEND>            
<table width="100%"  border="0" cellpadding="0" cellspacing="0" >
  <tr>
    <td width="100%"  valign="top" colspan="2"><input type="hidden" name="Hdd_Pld_Cod" id="Hdd_Pld_Cod"/>
      <input type="hidden" name="Hdd_Pld_Cdc" id="Hdd_Pld_Cdc"/>
      <input type="hidden" name="Hdd_Pld_Des" id="Hdd_Pld_Des"/>
      <input type="hidden" name="Hdd_Fila" id="Hdd_Fila"/>
      <table width="100%" height="36%" border="0" cellpadding="0" cellspacing="0">
        <tbody id="tbusqueda">
          <tr>
            <td width="15%" height="35" class="Cabecera1"><div align="right"><strong>Producto:</strong></div></td>
            <td width="52%" class="Cabecera1">
              <input name="txtbuscadorcom" type="text" id="txtbuscadorcom" size="40" maxlength="40" onkeypress="
                         enter_ajax('../COMPONENTES/tesComRubrosConsultaCompras.php?iva_cod=<?Php echo  urlencode($iva_cod); ?>&amp;iva_por=<?Php echo  urlencode($iva_por); ?>&amp;ice_cod=<?Php echo  urlencode($ice_cod); ?>&amp;ice_por=<?Php echo  urlencode($ice_por); ?>&amp;Pec_Cod=<?Php echo  urlencode($Pec_Cod); ?>&amp;buscador='+document.getElementById('txtbuscadorcom').value,'con_resultado'); " /></td>
            <td width="33%" align="center">
            <button type="button" name="btn_buscarcta" id="btn_buscarcta" class="btn btn-success fileinput-button" title="Buscar producto" 
        onclick="if (trim(document.getElementById('txtbuscadorcom').value) != ''){   ajax_datos('../COMPONENTES/tesComRubrosConsultaCompras.php?iva_cod=<?Php echo  urlencode($iva_cod); ?>&amp;iva_por=<?Php echo  urlencode($iva_por); ?>&amp;ice_cod=<?Php echo  urlencode($ice_cod); ?>&amp;ice_por=<?Php echo  urlencode($ice_por); ?>&amp;Pec_Cod=<?Php echo  urlencode($Pec_Cod); ?>&amp;buscador='+document.getElementById('txtbuscadorcom').value,'con_resultado'); }">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
           </button>         
            </td>
          </tr>
        </tbody>
      </table>
    </td>
  </tr>
</table>
<div id="con_resultado"></div>
</FIELDSET>