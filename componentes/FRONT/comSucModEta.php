<?Php
/**
 * Componente para la busqueda de la sucursal-modalidad-etapa
 * Sucursal = Todas
 * Modalidad = Todas
 * Etapa = Todas
*/

require_once('../../componentes/LOGICA/logica.php');

/**
 * Clase de datos
 */
$obBD_con2 = new Class_Log_Datos_Com;

/**
 * Clase de conexcion 
 */
$obBD_conexion2 = new Class_Log_Conexion_Com;

$Com_Hoy=  date("Y-m-d");
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Sucursal:</td>
    <td class="LetraNegra">
		<?Php  
        /**
         * Array de las sucursales 
         */
        $rs_sucursales = $obBD_con2->getArrayConsulta(101, $Ses_Emp_Cod, $obBD_conexion2);
        
        if (count($rs_sucursales) == 1)
        {
            $Suc_Cod = $rs_sucursales[0]['Suc_Cod'];
			?>
            <input name="Suc_Des" id="Suc_Des" type="text" value="<?Php echo $rs_sucursales[0]['Suc_Des']; ?>" readonly="readonly" size="50" style="border:none; background:none" />
       <input name="Suc_Cod" id="Suc_Cod" type="text" value="<?Php echo $rs_sucursales[0]['Suc_Cod']; ?>" size="1" style="
            visibility:hidden" />
            <?php
        }
        else
        {
           ?>
            <select name="Suc_Cod" id="Suc_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&Com_Hoy=<?php echo date("Y-m-d"); ?>&Suc_Cod=' + this.value,'div_sucursales')">
          <option value="">Seleccione...</option>
          <?Php          
          foreach($rs_sucursales as $row_rs_sucursales )
          { 
          ?>
                <option value="<?Php echo $row_rs_sucursales['Suc_Cod']; ?>" ><?Php echo $row_rs_sucursales['Suc_Des']; ?></option>
          <?Php 
          }
          ?>     
        </select><?
         } ?>
	</td>
  </tr>
  <tr>
    <td width="10%" class="Etiqueta1"><span class="Asterisco" >* </span>Modalidad:</td>
    <td width="90%" class="LetraNegra">
    <div id="div_sucursales" >
            <? 
          $rs_modalidad= $obBD_con2->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion2);
          if (count($rs_modalidad) == 1)
          {            
            $Mod_Cod = $rs_modalidad[0]['Mod_Cod']; ?>
			<input name="Mod_Des" id="Mod_Des" type="text" value="<?Php echo $rs_modalidad[0]['Mod_Des']; ?>" readonly="readonly" size="50" style="border:none; background:none" />       
        	<input name="Mod_Cod" id="Mod_Cod" type="text" value="<?Php echo $rs_modalidad[0]['Mod_Cod']; ?>" size="1" style="
            visibility:hidden" />
       	<?
          }
          else
          {
          ?>
       	<select name="Mod_Cod" id="Mod_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&Com_Hoy=<?php echo date("Y-m-d"); ?>&Suc_Cod=' + this.value,'div_etapa')">
       	  <option value="">Seleccion...</option>
       	  <?Php
          foreach($rs_modalidad as $row_rs_modalidad )
          { 
          ?>
       	  <option value="<?Php echo $row_rs_modalidad['Mod_Cod']; ?>" ><?Php echo $row_rs_modalidad['Mod_Des']; ?></option>
       	  <?Php 
          }
          ?>
   	    </select>
        <?
          }
          ?>
    </div>	</td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Etapa:</td>
    <td class="LetraNegra"><div id="div_etapa">
 <? 
          $rs_etapas= $obBD_con2->getArrayConsulta(3, '', $obBD_conexion2);
          if (count($rs_etapas) == 1)
          {            
            $Eta_Cod = $rs_etapas[0]['Mod_Cod']; ?>
			<input name="Eta_Des" id="Eta_Des" type="text" value="<?Php echo $rs_etapas[0]['Eta_Des']; ?>" readonly="readonly" size="50" style="border:none; background:none" />       
        	<input name="Eta_Cod" id="Eta_Cod" type="text" value="<?Php echo $rs_etapas[0]['Eta_Cod']; ?>" size="1" style="
            visibility:hidden" />			
           <?php 
            $Com_Hoy=  date("Y-m-d");
            ?>
        <?
          }
          else
          {
          ?>    
            <select name="Eta_Cod" id="Eta_Cod">
          <option value="">Seleccione...</option>
          <?Php
          
          foreach($rs_etapas as $row_rs_etapas)
          { 
          ?>
                <option value="<?Php echo $row_rs_etapas['Eta_Cod']; ?>" ><?Php echo $row_rs_etapas['Eta_Des']; ?></option>
          <?Php 
          }
          ?>     
        </select>
          <?
          }
          ?>
      </div></td>
  </tr>
</table>