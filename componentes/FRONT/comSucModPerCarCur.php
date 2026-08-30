<?php 
/**
 * Componente para la busqueda de la modalidad-periodo[etapa]-carrera 
 * Se utiliza el componentes Ajax ==> ajaxSucModPerCarCur.php 
 * Sucursal= Todas
 * Modalidad = Todas
 * Periodo = Todos
 * Carreras = Filtra carreras que se habran en el periodo lectivo
 * Curso = Los abierto
 * 
 * @author car.87cod :)
 * @version 1.1
 * @Fecha de actualización:	2012-05-24
 *
 * @author Lewis Chimarro
 * @version 1.1
 * @Fecha de actualización:	2012-11-05
 *
 * @package componentes.FRONT
 */
require_once('../../componentes/LOGICA/logica.php');
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
	 * Array de las sucursales 
	 */
	$rs_sucursales= $obBD_con11->getArrayConsulta(101, $Ses_Emp_Cod, $obBD_conexion11);
	if( count((array)$rs_sucursales) > 1)
	{ ?>		    
        <select name="Suc_Cod" id="Suc_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&Com_Hoy=<?php echo date("Y-m-d"); ?>&Est_Int=<?php echo $Est_Int;?>&Suc_Cod=' + this.value,'div_sucursales')">
          <option value="">Seleccione...</option>
		  <?Php 
          foreach($rs_sucursales as $row_rs_sucursales)
          { 
          ?>
      		<option value="<?Php echo $row_rs_sucursales['Suc_Cod']; ?>" ><?Php echo $row_rs_sucursales['Suc_Des']; ?></option>
		  <?Php 
          }
          ?>      
	    </select>
	    <?php 
	}
	else
	{
		$Suc_Cod=$rs_sucursales[0]['Suc_Cod']; ?>
		 <input name="Suc_Des" id="Suc_Des" type="text" value="<?Php echo $rs_sucursales[0]['Suc_Des']; ?>" readonly="readonly" size="50" style="border:none; background:none" />
       <input name="Suc_Cod" id="Suc_Cod" type="text" value="<?Php echo $rs_sucursales[0]['Suc_Cod']; ?>" size="1" style="
            visibility:hidden" />
      <?Php  		
	}
	?>
    </td>
  </tr>
  <tr>
    <td width="16%" class="Etiqueta1"><span class="Asterisco" >* </span>Modalidad:</td>
    <td width="84%" class="LetraNegra"><div id="div_sucursales">
    <?php 
	/**
	 * Consultar las modalidades
	 */
	$rs_modalidad = $obBD_con11->getArrayConsulta(302, $Suc_Cod, $obBD_conexion11);
	if(count((array)$rs_modalidad) > 1)
	{ ?>
        <select name="Mod_Cod" id="Mod_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&Est_Int=<?php echo $Est_Int;?>&Mod_Cod='+this.value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>&Com_Hoy=<?php echo $Com_Hoy; ?>','div_periodo')">
          <option value="">Seleccione...</option>
		  <?Php  
          foreach($rs_modalidad as $row_rs_modalidad)
          { ?>
              <option value="<?Php echo $row_rs_modalidad['Mod_Cod'];  ?>" ><?Php echo $row_rs_modalidad['Mod_Des'];  ?></option>
		  <?Php 
          }
          ?>
       </select>
	<?php 
	 }
	 else
	 {
	   $Mod_Cod = $rs_modalidad[0]['Mod_Cod']; ?>
		<input name="Mod_Des" id="Mod_Des" type="text" value="<?Php echo $rs_modalidad[0]['Mod_Des']; ?>" readonly="readonly" size="50" style="border:none; background:none" />       
		<input name="Mod_Cod" id="Mod_Cod" type="text" value="<?Php echo $rs_modalidad[0]['Mod_Cod']; ?>" size="1" style="
		visibility:hidden" />           
	 <?Php   
		 $Com_Hoy=  date("Y-m-d");
	  }
    ?></div>
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Periodo:</td>
    <td class="LetraNegra"><div id="div_periodo">
    <?php 
	 /**
	 * Combo donde carga periódo, si hay más de 1 registro
	 * Carga el combo caso contrario solo 
	 */
	$rs_periodos = $obBD_con11->getArrayConsulta(303, $Mod_Cod.'*'.$Com_Hoy.'*'.$Suc_Cod, $obBD_conexion11);
		
	if(count((array)$rs_periodos) > 1)
	{
	?>
         <select name="Per_Int" id="Per_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_carrera=1&Suc_Cod=<?Php echo $Suc_Cod; ?>&Est_Int=<?php echo $Est_Int;?>&Mod_Cod=<?Php echo $Mod_Cod; ?>&Per_Int='+this.value, 'div_carrera')">
         <option value="">Seleccione...</option>
         <?Php 
         foreach($rs_periodos as $row_rs_periodo){ 
         ?>
             <option value="<?Php echo $row_rs_periodo['Per_Int']; ?>"><?Php echo $row_rs_periodo['Mes_Ini']."/".$row_rs_periodo['Ann_Ini']." - ".
                  $row_rs_periodo['Mes_Fin']."/".$row_rs_periodo['Ann_Fin'].' ['.$row_rs_periodo['Eta_Des'].']'; ?>
             </option>
         <?Php
         } ?>
         </select>	 
	 <?Php 
	}
	else
	{
		$Per_Int = $rs_periodos[0]['Per_Int']; ?>
		<input name="Per_Nom" id="Per_Nom" type="text" value="<?Php echo $rs_periodos[0]['Mes_Ini']."/".$rs_periodos[0]['Ann_Ini']." - ".
		  $rs_periodos[0]['Mes_Fin']."/".$rs_periodos[0]['Ann_Fin'].' ['.$rs_periodos[0]['Eta_Des'].']';	 ?>" readonly="readonly" size="50" style="border:none; background:none" />
		<input name="Per_Int" id="Per_Int" type="text" value="<?Php echo $rs_periodos[0]['Per_Int']; ?>" size="1" style="
		visibility:hidden" />    
	   <?Php 
		if (count((array)$rs_periodos) == 0)
		{
			echo "<span class='Texto_Reporte_Rojo'>&iexcl;No existen periodos Activos &oacute; la fecha m&aacute;xima de matr&iacute;cula ha 
				caducado!</span>";
		}
	}
    ?> </div>
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Carrera:</td>
    <td><div id="div_carrera">
    <?php 
	if( count((array)$rs_periodos) == 1)
	{
		/** 
		* Consultar la etapa en base al periodos
		*/
		$rs_etapa= $obBD_con11->getRowConsulta(16, $Per_Int, $obBD_conexion11);
		/**
		* Combo donde carga carrera, si hay más de 1 registro
		* Carga el combo caso contrario solo 
		*/		 
	 	$rs_carreras_etapa=$obBD_con11->getArrayConsulta(102, $rs_etapa['Eta_Cod'].'*'.$Per_Int, $obBD_conexion11);
	}	
	if(count((array)$rs_carreras_etapa) == 1)
	{
		$Car_Int = $rs_carreras_etapa[0]['Car_Int']; ?>
		<input name="Car_Nom" id="Car_Nom" type="text" value="<?Php echo $rs_carreras_etapa[0]['Car_Nom']; ?>" readonly="readonly" size="50" style="border:none; background:none" />
		<input name="Car_Int" id="Car_Int" type="text" value="<?Php echo $rs_carreras_etapa[0]['Car_Int']; ?>" size="1" style="
		visibility:hidden" />            
		 <?Php
	}
	else
	{ ?>
        <select name="Car_Int" id="Car_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_nivel=1&Eta_Cod=<?Php echo $rs_etapa['Eta_Cod']; ?>&Mod_Cod=<?Php echo $Mod_Cod; ?>&Est_Int=<?php echo $Est_Int;?>&Per_Int=<?Php echo $Per_Int; ?>&Car_Int='+this.value, 'div_curso')">
         <option value="">Seleccione...</option>
	   <?Php 
       foreach($rs_carreras_etapa as $row_rs_carrera_etapa)
       { 
       ?>
       <option style="text-transform:uppercase" value="<?Php echo $row_rs_carrera_etapa['Car_Int'];  ?>"> <?Php echo $row_rs_carrera_etapa['Car_Nom']; ?> </option>
	   <?Php 
       } 
       ?>
     </select>			
		<?Php
    	}
    ?> </div>
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Curso:</td>
    <td><div id="div_curso">
    <?php 
	/**
	 * Consulta de los semestres
	 */
	$rs_semestres=$obBD_con11->getArrayConsulta(301, $_POST['Car_Int'].'*'.$Per_Int.'*'.$Mod_Cod.'*'.$Est_Int,	$obBD_conexion11);
	if(count((array)$rs_semestres) == 1)
	{
			?>
        <input name="Sem_Nom" id="Sem_Nom" type="text" value="<?Php echo $rs_semestres[0]['Sem_Nom']; ?>" readonly="readonly" size="50" style="border:none; background:none" />
                <input name="Sem_Cod" id="Sem_Cod" type="text" value="<?Php echo $rs_semestres[0]['Sem_Cod']; ?>" size="1" style="
                visibility:hidden" />             
	<?Php		
	}
	else 
	{ 
		if (count((array)$rs_semestres) == 0 and $Car_Int > 0)
		{
			echo "<span class='Texto_Reporte_Rojo'>¡No existen Cursos aperturados!</span>";
		}
		else
		{		
	?>
			<select name="Sem_Cod" id="Sem_Cod" >
			<option value="">Seleccione...</option>
			<?Php 
			foreach($rs_semestres as $row_rs_semestres)
			{  
			?>
			<option style="text-transform:uppercase" value="<?Php echo $row_rs_semestres['Sem_Cod'];  ?>"><?Php echo $row_rs_semestres['Sem_Nom'];  ?></option>
			<?Php 
			} 
			?>
			</select>
	  <?php  
        }			
    }
    ?></div></td>
  </tr>
</table>
</FIELDSET>