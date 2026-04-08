<?Php
require_once('../../componentes/LOGICA/logica.php');

/* Componente para la busqueda de la Sucursal, Modalidad, Etapa, Periodo, Carrera, Curso, Asignatura con enfoque en los Distributivos
Utiliza el componentes Ajax ==> ajaxnotDistributiComSucModEtaPerCarCurAsi.php 
Sucursal= Carga todas las sucursales abiertas por la universidad activas
Modalidad = todas las activas
Etapa = todas las activas
Periodo = todos los periodos activos desde el primero dia de clase al ultimo de clases + dias adicionales
Carreras = todas las abiertas en el periodo
Cursos = todos los cursos abiertos en el periodo en base a una carrera
Distributivos = todos los asignados a un docente

Desarrollador: Lewis Chimarro
Fecha de actualización: 2012-02-08
*/

$hoy = date("Y-m-d");
/* Consultar las sucursales de la universidad */
$rs_sucursales= $obBD_con1->consulta(sentencias_com(101, $obBD_con1->parametros($Ses_Emp_Cod)), $obBD_conexion->conexion);
$row_rs_sucursales= $obBD_con1->registros();
$total_row_rs_sucursales = $obBD_con1->numregistros();	
?>
<FIELDSET>
<LEGEND>
    <label class="Titulos2">Buscar por:</label>
</LEGEND>
<?Php mensaje_requerido(); //Muestra el mensaje de requerido?>		
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Sucursal:&nbsp;</td>
      <td width="82%" colspan="2">
      <!-- S U C U R S A L -->
      	<select name="Suc_Cod" id="Suc_Cod"  onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&var_suc_cod=' + this.value,'div_modalidad')">
				<option value="">Seleccione...</option>
			   <?php do{ ?>
			        <option style="text-transform:uppercase" value="<?Php echo $row_rs_sucursales['Suc_Cod']; ?>">
							<?Php echo $row_rs_sucursales['Suc_Des']; ?></option>  	            
			   <?Php }while($row_rs_sucursales = $obBD_con1->fetch_assoc($rs_sucursales)); ?>
        </select>        
        </td>
      </tr>
      <tr>
        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Modalidad:&nbsp;</td>
        <td colspan="2"><div id="div_modalidad">
        <!-- M O D A L I D A D -->
        <select name="Mod_Cod" id="Mod_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&var_mod_cod=' + this.value+'&var_suc_cod=<?Php echo $var_suc_cod; ?>','div_periodo')">
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
		</select></div></td>
      </tr>      
      <tr>
        <td width="18%" align="right" class="Etiqueta1"><span class="Asterisco">*</span> Cursos:&nbsp; </td>
        <td colspan="2"><div id="div_cursos">
         <!-- C U R S O S -->
        <select name="Sem_Cod" id="Sem_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_distributi=1&var_sem_cod=' + this.value, 'div_distributi')">
			<option></option>
        </select></div> </td>
      </tr>
      <tr>
        <td width="18%" align="right" valign="top" class="Etiqueta1"><span class="Asterisco">*</span> Asignaturas:&nbsp;</td>
        <td colspan="2"><div id="div_distributi">
        <!-- D I S T R I B U T I V O S  -  M O D U L O S -->
          <select name="Dis_Cod" id="Dis_Cod">
            <option></option>
          </select>
        </div></td>
      </tr>
    </table>
</FIELDSET>
<?Php
@$obBD_con1->free_result($rs_sucursales);
?>