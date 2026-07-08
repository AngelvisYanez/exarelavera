<?Php
/**
 * Componente para la busqueda de la modalidad-etapa-periodo
 * Modalidad = todas
 * Etapa = todas
 * Periodo = en base a la etapa y modalidad los actuales
*/
 require_once('../../componentes/LOGICA/logica.php');
/**
 * Ajax que permite cargar:
 * Surcursales	= 	Todas 
 * Modalidad	=	Todas las activas
 * Perido 		=	Actuales
 */
 
/**
 *Creacion del Objeto de conexion
 */
$obBD_conexion1 = new Class_Log_Conexion_Com;
/**
 * Creacion del objeto mysql para las consultas
 */
$obBD_con2 =  new Class_Log_Datos_Com;
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del periodo:</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Sucursal:</td>
    <td class="LetraNegra"><?Php  
        /**
         * Array de las sucursales 
         */
        $rs_sucursales = $obBD_con2->getArrayConsulta(101, $Ses_Emp_Cod, $obBD_conexion1);
        
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
          <option value="">Seleccion...</option>
          <?Php          
          foreach($rs_sucursal as $row_rs_sucursales )
          { 
          ?>
                <option value="<?Php echo $row_rs_sucursales['Suc_Cod']; ?>" ><?Php echo $row_rs_sucursales['Suc_Des']; ?></option>
          <?Php 
          }
          ?>     
        </select><?php
         } ?>
    </td>
  </tr>
  <tr>
    <td width="16%" class="Etiqueta1"><span class="Asterisco" >* </span>Modalidad:</td>
    <td width="84%" class="LetraNegra">
        <div id="div_sucursales">
            <?php 
          $rs_sucursal= $obBD_con2->getArrayConsulta(302, $Ses_Emp_Cod, $obBD_conexion1);
          if (count($rs_sucursales) == 1)
          {            
            $Mod_Cod = $rs_sucursal[0]['Mod_Cod']; ?>
			<input name="Mod_Des" id="Mod_Des" type="text" value="<?Php echo $rs_sucursal[0]['Mod_Des']; ?>" size="50" readonly="readonly" style="border:none; background:none" />       
        	<input name="Mod_Cod" id="Mod_Cod" type="text" value="<?Php echo $rs_sucursal[0]['Mod_Cod']; ?>" size="1" style="
            visibility:hidden" />			
           <?php 
            $Com_Hoy=  date("Y-m-d");
            ?>
        <?php
          }
          else
          {
          ?>    
            <select name="Mod_Cod" id="Mod_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&Com_Hoy=<?php echo date("Y-m-d"); ?>&Suc_Cod=' + this.value,'div_sucursales')">
          <option value="">Seleccion...</option>
          <?Php
          
          foreach($rs_sucursal as $row_rs_sucursales )
          { 
          ?>
                <option value="<?Php echo $row_rs_sucursales['Mod_Cod']; ?>" ><?Php echo $row_rs_sucursales['Mod_Des']; ?></option>
          <?Php 
          }
          ?>     
        </select>
          <?php
          }
          ?>
          </div>	
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Periodo:</td>
    <td class="LetraNegra">
        <div id="div_periodo">
               <?php 
             $rs_periodos = $obBD_con2->getArrayConsulta(300, $Mod_Cod.'*'.$Com_Hoy.'*'.$Suc_Cod, $obBD_conexion1);
            if( count($rs_periodos) == 1)
            {
                $Per_Int = $rs_periodos[0]['Per_Int'];
				?>
				<input name="Per_Nom" id="Per_Nom" type="text" value="<?Php echo $rs_periodos[0]['Mes_Ini']."/".$rs_periodos[0]['Ann_Ini']." - ".
			  $rs_periodos[0]['Mes_Fin']."/".$rs_periodos[0]['Ann_Fin'].' ['.$rs_periodos[0]['Eta_Des'].']';	 ?>" readonly="readonly" size="50" style="border:none; background:none" />
		<input name="Per_Int" id="Per_Int" type="text" value="<?Php echo $rs_periodos[0]['Per_Int']; ?>" size="1" style="
            visibility:hidden" />
            <?php
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
            </div>
    </td>
  </tr>
</table>
</FIELDSET>