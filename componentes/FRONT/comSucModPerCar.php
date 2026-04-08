<?Php
/**
* Se puede utiliza 2 componentes Ajax ==> ajaxSucModPerCarAll.php | ajaxSucModPerCar.php
* Sucursal= Carga todas las sucursales abiertas
* Modalidad = todas las abiertas
* Periodo = en base a la etapa y modalidad, todos los periodos
* Carreras = Filtra carreras que se habran en el periodo lectivo
* LLama a dos componentes con distintas Sql para el cargado de los periodos
* Desarrollador: Lewis Chimarro
* Modificado: 2012-07-20
* @package componentes.FRONT
*/
  require_once('../../componentes/LOGICA/logica.php');
/**
 * Ajax que permite cargar:
 * Modalidad	=	Todas
 * Perido [Etapa] =	Todos
 * Carrera =   Solo las que se abren en el periodo
 */
/**
 * objeto para conexion
 * @var Class_Log_Conexion_Com
 */ 
 $obBD_conexion11 = new Class_Log_Conexion_Com;
/**
 * objeto para consultas
 * @var Class_Log_Datos_Com
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
        * Consultar las sucursales de la universidad 
        */
        $rs_sucursales= $obBD_con11->getArrayConsulta(101, $Ses_Emp_Cod, $obBD_conexion11);
		/**
		 * Combo donde carga sucursal, si hay más de 1 registro
		 * Carga el combo caso contrario solo 
		 */
        if (count($rs_sucursales) == 1)
         {
            $Suc_Cod = $rs_sucursales[0]['Suc_Cod'];
			?>
            <input name="Suc_Des" id="Suc_Des" type="text" value="<?Php echo $rs_sucursales[0]['Suc_Des']; ?>" readonly="readonly" size="50" style="border:none; background:none" />
            <input name="Suc_Cod" id="Suc_Cod" type="text" value="<?Php echo $rs_sucursales[0]['Suc_Cod']; ?>" size="1" style="
            visibility:hidden" />
         <?Php   
            $Com_Hoy=  date("Y-m-d");
         }
        else
         {
			   ?>
				<select name="Suc_Cod" id="Suc_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&Com_Hoy=<?php echo date("Y-m-d"); ?>&Suc_Cod=' + this.value + '&Com_Todos=<?Php echo $Com_Todos; ?>' ,'div_sucursales')">
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
         }
     ?>
    </td>
  </tr>
  <tr>
    <td width="17%" class="Etiqueta1"><span class="Asterisco" >* </span>Modalidad:</td>
    <td width="83%" class="LetraNegra">
      <div id="div_sucursales">
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
            visibility:hidden"/>            
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
    <td class="LetraNegra">
      <div id="div_periodo">
        <? 
		/**
		 * Combo donde carga periódo, si hay más de 1 registro
		 * Carga el combo caso contrario solo 
		 */
          $rs_periodos = $obBD_con11->getArrayConsulta(303, $Mod_Cod.'*'.$Com_Hoy.'*'.$Suc_Cod, $obBD_conexion11);
          if( count($rs_periodos) == 1)
          {
            $Per_Int = $rs_periodos[0]['Per_Int'];
			?>
            <input name="Per_Nom" id="Per_Nom" type="text" value="<?Php echo $rs_periodos[0]['Mes_Ini']."/".$rs_periodos[0]['Ann_Ini']." - ".
			  $rs_periodos[0]['Mes_Fin']."/".$rs_periodos[0]['Ann_Fin'].' ['.$rs_periodos[0]['Eta_Des'].']';	 ?>" readonly="readonly" size="50" style="border:none; background:none" />
			<input name="Per_Int" id="Per_Int" type="text" value="<?Php echo $rs_periodos[0]['Per_Int']; ?>" size="1" style="
            visibility:hidden" /> 
            <?Php                
                  $Com_Hoy=  date("Y-m-d");
          }
          else
          {
        ?>
         <select name="Per_Int" id="Per_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_carrera=1&Suc_Cod=<?Php echo $Suc_Cod; ?>&Mod_Cod=<?Php echo $Mod_Cod; ?>&Com_Todos=<? echo $Com_Todos;?>&Per_Int='+this.value, 'div_carrera')">
           <option value="">Seleccione...</option>
         <?Php foreach($rs_periodos as $row)
		       { 
	      ?>
                <option <?php if ($Per_Int == $row['Per_Int']){ echo "selected"; } ?> value="<?Php echo $row['Per_Int']; ?>"><?Php if (count($rs_periodos) > 0) {
                echo $row['Mes_Ini']."/".$row['Ann_Ini']." - ".
                  $row['Mes_Fin']."/".$row['Ann_Fin'].' ['.$row['Eta_Des'].']'; } ?>
           </option>
         <?Php } ?>	 
         </select>	 
         <?Php
             /*if (count($rs_periodos) == 0)
             {
                echo "<span class='Texto_Reporte_Rojo'>&iexcl;No existen periodos Activos &oacute; la fecha m&aacute;xima de matr&iacute;cula ha 
                caducado!</span>";
             }//Fin del if ($total_rs_periodo == 0)*/
          }
        ?>
        </div>
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Carrera:</td>
    <td >
      <div id="div_carrera">
        <?
			/** 
			* Consultar la etapa en base al periodos
			*/
			$row_rs_etapa= $obBD_con11->getRowConsulta(16, $Per_Int, $obBD_conexion11);
			/* 
			* Consultar las carreras por etapa y periodos 
			*/
			$rs_carreras_etapa=$obBD_con11->getArrayConsulta(102, $row_rs_etapa['Eta_Cod'].'*'.$Per_Int, $obBD_conexion11);

			if($Com_Todos=="si")
			{
				foreach($rs_carreras_etapa as $roow) 
				{
					$cadTodo=$cadTodo.'*'.$roow['Car_Int'];
				} 
			}	
			
            if(count($rs_carreras_etapa)==1)
            {
				?>
        <input name="Car_Nom" id="Car_Nom" type="text" value="<?Php echo $rs_carreras_etapa['Car_Nom']; ?>" readonly="readonly" size="50" style="border:none; background:none" />  
        <input name="Car_Int" id="Car_Int" type="text" value="<?Php echo $rs_carreras_etapa['Car_Int']; ?>" size="1" style="
            visibility:hidden" />                            
        <?php                 
                $Car_Int = $rs_carreras_etapa[0]['Car_Int'];
            }
            else
            {
          ?>
        <select name="Car_Int" id="Car_Int">
          <? if($Com_Todos=="si")
                  {?>
          <option value="<? echo $cadTodo;?>"><? echo"<< TODOS >>";?></option>
          <? }
                 foreach($rs_carreras_etapa as $rows)
				      { 
			    ?>
          <option <?php if ($Car_Int == $rows['Car_Int']){ echo "selected"; } ?> value="<?Php echo $rows['Car_Int'];  ?>"> <?Php echo $rows['Car_Nom']; ?> </option>
          <?Php  } 
			    ?>
          </select>    
        <?	
            }?>
        </div>
      </td>
  </tr>
  </table>
</FIELDSET>