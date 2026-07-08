<?Php require_once('../../componentes/LOGICA/logica.php');
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

if(isset($ajax_suc_cod))
{
	/* Consultar las modalidades */
	$rs_modalidad = $obBD_con1->consulta(sentencias_com(1, ''), $obBD_conexion->conexion);
	$row_rs_modalidad = $obBD_con1->registros();
	$total_rs_modalidad = $obBD_con1->numregistros();
	$var_mod_cod = $row_rs_modalidad['Mod_Cod'];
	?>
   <!-- M O D A L I D A D -->    
   <select name="Mod_Cod" id="Mod_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&var_mod_cod=' + this.value + '&var_suc_cod=<?Php echo $var_suc_cod; ?>','div_periodo')">
          <option value="">Seleccione...</option>
          <?Php  do { ?>
          <option style="text-transform:uppercase" value="<?Php echo $row_rs_modalidad['Mod_Cod'];  ?>">
		  		<?Php echo $row_rs_modalidad['Mod_Des'];  ?></option>
          <?Php 
			}while($row_rs_modalidad=$obBD_con1->fetch_assoc($rs_modalidad));  ?>        
	</select>           
<?Php	
 	@$obBD_con1->free_result($rs_modalidad);
	exit();
}//Fin del if(isset($ajax_suc_cod))

/* Cargado de los periodo */
if(isset($ajax_periodo))
{	 
	 /* Consulta los periodos en base a las sucursales, modalidades y etapas */
	 $rs_periodos = $obBD_con1->consulta(sentencias_com(14, $obBD_con1->parametros(
									$var_suc_cod.'*'.$var_mod_cod.'*'.$hoy.'*'.$Ses_Prs_Cod)), $obBD_conexion->conexion);
	 $row_rs_periodos = $obBD_con1->registros();
	 $total_rs_periodos = $obBD_con1->numregistros();
	 //$total_rs_periodos = 1;
	 ?>
	 <select name="Per_Int" id="Per_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_nivel=1&var_suc_cod=<?Php echo $var_suc_cod; ?>&var_mod_cod=<?Php echo $var_mod_cod; ?>&var_per_int='+this.value, 'div_cursos')">
		 <!--<option value="115">2011</option>-->
         <option value="">Seleccione...</option>
         <?Php
	 	  do{ ?>
	  	 	<option value="<?Php echo $row_rs_periodos['Per_Int']; ?>"><?Php if ($total_rs_periodos > 0) {
			 echo $row_rs_periodos['Mes_Ini']."/".$row_rs_periodos['Ann_Ini']." - ".
			  $row_rs_periodos['Mes_Fin']."/".$row_rs_periodos['Ann_Fin']." [".$row_rs_periodos['Eta_Des']."]"; } 
			  ?>
	    	 </option>
	 <?Php }while($row_rs_periodos=$obBD_con1->fetch_assoc($rs_periodos)); ?>	 
	 </select>	 
	 <?Php
		 if ($total_rs_periodos == 0)
		 { 
			echo "<span class='Texto_Reporte_Rojo'>&iexcl;No existen periodos Activos &oacute; la fecha m&aacute;xima de registro de calificaciones ha 
			caducado!</span>";
		 }//Fin del if ($total_rs_periodo == 0)
	 @$obBD_con1->free_result($rs_periodos);
	 exit();
}//Fin del if(isset($ajax_periodo))

/* Consultar los curso activos */	
if(isset($ajax_nivel))
{
	/* Consulta de los semestres */
	$rs_semestres=$obBD_con1->consulta(sentencias_com(13, $obBD_con1->parametros($Ses_Prs_Cod.'*'.$var_per_int)), 
					$obBD_conexion->conexion);
	$row_rs_semestres =  $obBD_con1->registros();
	$total_rs_semestres = $obBD_con1->numregistros();			
?>
	<select name="Sem_Cod" id="Sem_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_distributi=1&var_sem_cod=' + this.value+'&var_suc_cod=<?Php echo $var_suc_cod; ?>&var_mod_cod=<?Php echo $var_mod_cod; ?>&var_per_int=<?Php echo $var_per_int; ?>', 'div_distributi')">
    
	<option></option>
	<?Php do{  ?>
		<option value="<?Php echo $row_rs_semestres['Sem_Cod'];  ?>"><?Php echo "[". cortar_cadena_param(' ', $row_rs_semestres['Car_Nom']).'] '.$row_rs_semestres['Sem_Nom'];  ?></option>
	<?Php 
	}while($row_rs_semestres=$obBD_con1->fetch_assoc($rs_semestres)); ?>
	</select>
	<?Php
	 if ($var_per_int != "")
	 {
		 if ($total_rs_semestres == 0)
		 {
	 		echo "<span class='Texto_Reporte_Rojo'>&iexcl;No existen Cursos asignados!</span>";
		 }//Fin del if ($total_rs_periodo == 0)
	 }//Fin del if ($var_eta_cod != "")	
	@$obBD_con1->free_result($rs_semestres);
	 exit();
}//Fin del if(isset($ajax_nivel))

/* Cargado Ajax de las asignaturas en base al semestre y la variable de sesion de la persona*/
if (isset($ajax_distributi))
{
	/* Consulta las asigturas planificadas para el docente */ 
	$rs_distributi = $obBD_con1->consulta(sentencias_com(15,$obBD_con1->parametros($var_sem_cod.'*'.$Ses_Prs_Cod)), 
							$obBD_conexion->conexion); 
	$row_rs_distributi = $obBD_con1->registros(); 
	?>
      <select name="Dis_Cod" id="Dis_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?lst_ajax=1&Dis_Cod=' + this.value + '&var_sem_cod=' + document.getElementById('Sem_Cod').value + '&var_suc_cod=<?Php echo $var_suc_cod; ?>&var_mod_cod=<?Php echo $var_mod_cod; ?>&var_per_int=<?Php echo $var_per_int; ?>', 'div_lista')">
        <option></option>
        <?Php do{ ?>
        <option value="<?Php echo $row_rs_distributi['Dis_Cod'] ?>">
                <?Php echo $row_rs_distributi['Asi_Des'];				 
                        if ($row_rs_distributi['Dis_Sub'] != '')
                        { 
                           echo " [".mb_convert_encoding($row_rs_distributi['Dis_Sub'], 'UTF-8', 'ISO-8859-1')."]";
                        } ?>
        </option>
        <?Php }while($row_rs_distributi=$obBD_con1->fetch_assoc($rs_distributi));?>
      </select>
	<?Php
	exit();
}//Fin del if (isset($cmb_ajax))
?>