<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-04-19
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package contabilidad.LOGICA
 */
function sentencias_con($id,$Par_Sql)
{
	switch($id)
	{
		case 3: 
		/* Consulta la provicia y pais de la ciudad de la sucursal */
 		$provincia="SELECT 
  provincia.Pro_Nom,
  pais.Pas_Nom
FROM
  provincia
  INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod)
  INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod)
  INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod) 
 WHERE 
  ciudad.Ciu_Cod = $Par_Sql[0]";
						//echo $provincia;
		return $provincia;
		break;
		
		case 4:
		/* 
		* Consulta del usuario
		*/
		$consulta_4 = "SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
		//echo $consulta_4;
		return $consulta_4;
		break;
		
		case 12:
		/* 
		* Consulta el codigo del proceso 
		*/
		$consulta_proceso_12 = "SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = '$Par_Sql[0]'";
		//echo $consulta_proceso_12;
		return $consulta_proceso_12;
		break;

		case 13:
		/* 
		* Consulta el reporte recursivo 
		*/
		$consulta_proceso_13 = "SELECT 
  reportes.Rep_Cod,
  procesos.Pcs_Nom,
  reportes.Rep_Ord,
  rutas.Rut_Des
FROM
  procesos
  INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req)
  INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod) WHERE reportes.Pcs_Cod = $Par_Sql[0] AND reportes.Emp_Cod = $Par_Sql[1] ORDER BY
							reportes.Rep_Ord ";
		//echo $consulta_proceso_13;
		return $consulta_proceso_13;
		break;

		case 14:
		/* 
		* Consulta los comprobantes que se encuentran en la tabla compr_auto 
		*/
		$compr_auto_14 = "SELECT compr_auto.Com_Cod FROM compr_auto WHERE compr_auto.Com_Cod = $Par_Sql[0]";						
		return $compr_auto_14;
		break;

		case 15:
		/* 
		* Consulta los comprobantes que se encuentran en la tabla det_ccpp_p 
		*/
		$ccpp_pagar_15 = "SELECT det_ccpp_p.Com_Cod FROM det_ccpp_p WHERE det_ccpp_p.Com_Cod = $Par_Sql[0]";						
		return $ccpp_pagar_15;
		break;
		
		case 24:
		/*
		* Consulta del vendedor en base al codigo de la persona y la sucursal
		*/
		$consultar_vendedor = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod, Pun_Des FROM vendedor, puntos_imp WHERE vendedor.Pun_Cod = puntos_imp.Pun_Cod AND vendedor.Vnd_Est = 'A' AND 
							vendedor.Prs_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[1]";
		//echo $consultar_vendedor;
		return $consultar_vendedor;
		break;
		
		case 25:
		/* 
		* Consulta de la caja activa en base al punto de impresion 
		*/
		$consultar_caja_25 = "SELECT caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Pun_Cod, Pun_Des FROM caja_aper, puntos_imp WHERE caja_aper.Pun_Cod = puntos_imp.Pun_Cod AND
						caja_aper.Caj_Est ='A' AND caja_aper.Pun_Cod = '$Par_Sql[0]'";
						//echo $consultar_caja_25;
		return $consultar_caja_25;
		break;
		
		case 113: 
		/* 
		* Consulta la informaci�n relacionada con el c�digo del periodo contable 
		*/
 		$consul_fecha_113 =	"SELECT 
  perio_cont.Pec_Cod,
  perio_cont.Pec_Fei,
  perio_cont.Pec_Fef,
  YEAR(Pec_Fei) AS Ann,
  perio_cont.Pla_Cod
FROM
  plan_cuenta
  INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
WHERE
  Pec_Cod = $Par_Sql[0] AND 
  plan_cuenta.Emp_Cod = $Par_Sql[1]";
		//echo $consul_fecha_113;
		return $consul_fecha_113;
		break;	

		case 126: 
		/* Consulta la informaci�n la ciudada en base a la sucursal */
 		$cargar_ciudad="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
						//echo $cargar_ciudad;
		return $cargar_ciudad;
		break;

		case 146:
		/* 
		* Consulta el comprobante por el apellidos del cliente o proveedor 
		*/
		$my_cargar_comprin_146="SELECT Com_Cod, Com_Num, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Ced, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est, Com_Gen FROM comprobantes, $Par_Sql[0], persona WHERE Prs_Ape like '%$Par_Sql[1]%' AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom";
		//echo $my_cargar_comprin_146;
	   return $my_cargar_comprin_146;
	   break;

	    case 147:
		/* 
		* Consulta el comprobante por el codigo interno 
		*/
	$cargar_comprin="SELECT Com_Cod, Com_Num, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Ced, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est FROM comprobantes, $Par_Sql[0], persona WHERE Com_Cod='$Par_Sql[1]' AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]'";
		return $cargar_comprin;
		break;

	/* 
	* Cargado de la cabecera del comprobante por Apellido del cliente o proveedor 
	*/
	case 148:
	$cargar_cabape_148="SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est, Com_Gen FROM comprobantes, $Par_Sql[0], persona WHERE Prs_Ape like '%$Par_Sql[1]%' AND Tia_Cod ='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom"; //AND comprobantes.Com_Est='A'
	//echo $cargar_cabape_148;
	return $cargar_cabape_148;
	break;
	
	/* 
	* Cargado de la cabecera del comprobante por codigo 
	*/
	case 149:
	$cargar_cabcod_149="SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est FROM comprobantes, $Par_Sql[0], persona WHERE Com_Cod =$Par_Sql[1] AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A'";
	//echo $cargar_cabcod_149;
	return $cargar_cabcod_149;
	break;	

	/* 
	* Actualizaci�n de los asientos del comprobante a modificar 
	*/
	case 151:
	$upd_ascompr="UPDATE asientos SET Asi_Val=$Par_Sql[0], Asi_Con=UPPER('$Par_Sql[1]'), Asi_Glo=UPPER('$Par_Sql[2]'), Pld_Cod=$Par_Sql[3]  WHERE Asi_Cod = $Par_Sql[4]";
	return $upd_ascompr;
	break;

		case 152:
		/*
		* Selecionar el numero maximo de comprobante mensual seg�n el tipo
		*/
		$num_com_152="SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod=$Par_Sql[0] AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
					//echo $num_com_152;
		return $num_com_152;
		break;		

		case 180:
		/* 
		* Consulta el cliente reservado para la caja diaria 
		*/
		$caja_clien_180 = "SELECT caja_clien.Cli_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom FROM caja_clien, cliente, persona WHERE caja_clien.Cli_Cod = cliente.Cli_Cod AND persona.Prs_Cod = cliente.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[0]";
		//echo $caja_clien_180;
		return $caja_clien_180;
		break;

		case 181:
		/* 
		* Consulta de las cajas que estan listas para generar y que NO han sido generadas 
		*/
		$caja_clien_181 = "SELECT Caj_Cod, Caj_Fec FROM caja_aper WHERE Caj_Est = 'C' AND Caj_Gen = 'S' AND Caj_Cod NOT IN (SELECT 
  caja_compr.Caj_Cod
FROM
  caja_compr
  INNER JOIN comprobantes ON (caja_compr.Com_Cod = comprobantes.Com_Cod)
WHERE comprobantes.Com_Est = 'A') AND 
						YEAR(Caj_Fec) = $Par_Sql[0] AND Pun_Cod = $Par_Sql[1]";
		//echo $caja_clien_181;
		return $caja_clien_181;
		break;

		case 204:
		/* 
		* Consulta la descripcion de la recusividad de una sub-cuenta 
		*/
		$consul_recur = "SELECT det_plan.Pld_Rec, det_plan.Pld_Cdc, Pld_Des FROM det_plan WHERE det_plan.Pld_Cod = '$Par_Sql[0]'";
		//echo $consul_recur;
		return $consul_recur;
		break;

		case 210:
		/* 
		* 
		Consulta de los tipos de asientos 
		*/
		$tipo_asiento_210 = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien";
		//echo $tipo_asiento_210;
		return $tipo_asiento_210;
		break;

		/* 
		* Consulta todos los periodos activos 
		*/
		case 214:
		$cargar_per_214 = "SELECT 
  perio_cont.Pec_Cod,
  perio_cont.Pec_Fei,
  perio_cont.Pec_Fef,
  perio_cont.Pec_Est,
  Year(Pec_Fei) AS Periodo,
  perio_cont.Pla_Cod
FROM
  plan_cuenta
  INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
WHERE
  Pec_Est = 'A' AND plan_cuenta.Emp_Cod = $Par_Sql[0]
ORDER BY
  Pec_Fei DESC";
		//echo $cargar_per_214;
		return $cargar_per_214;
		break;

	/* 
	* Insercion de un comprobante de Ingreso/Egreso (Cliente/Proveedor) 
	*/
	case 324:
	$ins_compi="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[9]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'), Com_Tipo='$Par_Sql[8]',Usu_Cod='$_SESSION[Ses_Usu_Cod]'";//Antes Com_Tip
	//echo $ins_compi;
	return $ins_compi;
	break;

		/* B�squeda de un cliente por apellido */
		case 317:
		$bus_clia="SELECT Cli_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Cli_Cup, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est FROM cliente, persona WHERE Prs_Ape LIKE '%$Par_Sql[0]%' AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1]";
		//echo $bus_clia;
		return $bus_clia;
		break;

		/* B�squeda de un cliente por C�dula */
		case 318:
		$bus_clic="SELECT Cli_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Cli_Cup, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est FROM cliente, persona WHERE Prs_Ced = '$Par_Sql[0]' AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1]";//1=contabilidad
		return $bus_clic;	/* Cargado de la cuenta por medio de su codigo de cuenta ------- Revisar el valor del codigo de empresa, la sesion no muestra el dato */

		/* 
		* Cargado de la cuenta por medio de su codigo de cuenta ------- Revisar el valor del codigo de empresa, la sesion no muestra el dato 
		*/
		case 319:
		$cargar_cuenta_319="SELECT Pld_Cod,Pld_Des FROM det_plan, plan_cuenta WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND det_plan.Pld_Cdc='$Par_Sql[0]' AND Emp_Cod=$Par_Sql[1] AND Pla_Est='A' AND Pld_Est='A' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D'";
		//echo $cargar_cuenta_319;
		return $cargar_cuenta_319;		
		break;

		/* 
		*B�squeda de un proveedor por apellido 
		*/
		case 320:
		$bus_proa="SELECT Prv_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est FROM proveedore, persona WHERE Prs_Ape LIKE '%$Par_Sql[0]%' AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1]";
		//echo $bus_proa;
		return $bus_proa;
		break;

		/* 
		* B�squeda de un proveedor por C�dula 
		*/
		case 321:
		$bus_proc="SELECT Prv_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est FROM proveedore, persona WHERE Prs_Ced = '$Par_Sql[0]' AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1]";
		return $bus_proc;
		break;

	/* 
	* Inserci�n de cada asiento del comprobante 
	*/
	case 325:
	$ins_asie="INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5]";
	//echo $ins_asie."<br>";
	return $ins_asie;
	break;

	/* 
	* Cargado de la cabecera del comprobante, sea este de cualquier tipo 
	*/
	case 326:
	$cargar_cabcomp="SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est, Com_Gen FROM comprobantes, $Par_Sql[0], persona WHERE Com_Num='$Par_Sql[1]' AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom"; //AND comprobantes.Com_Est='A'
	return $cargar_cabcomp;
	break;	

	/* 
	* Cargado de las cuentas a modificar 
	*/
	case 327:
	$cargar_cuentas="SELECT asientos.Asi_Cod, asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, Asi_Deh, ROUND(Asi_Val,2) as Asi_Val FROM asientos, det_plan WHERE asientos.Com_Cod=$Par_Sql[0] AND asientos.Pld_Cod=det_plan.Pld_Cod
	ORDER BY Asi_Deh, Pld_Cdc ";
	//echo $cargar_cuentas;
	return $cargar_cuentas;
	break;

	/* 
	* Actualizacion de la cabecera del comprobante 
	*/
	case 328:	
	$act_cabcompr="UPDATE comprobantes SET Com_Num='$Par_Sql[0]', Com_Con=UPPER('$Par_Sql[1]'), Com_Val=$Par_Sql[2], Com_Obs=UPPER('$Par_Sql[3]'), Com_Fec='$Par_Sql[5]' WHERE Com_Cod=$Par_Sql[4]";
	return $act_cabcompr;
	break;	

	/* 
	* Borrado de los asientos del comprobante a modificar 
	*/
	case 329:
	$bor_ascompr="DELETE FROM asientos WHERE Asi_Cod=$Par_Sql[0]";
	//echo $bor_ascompr;
	return $bor_ascompr;
	break;	

	/* 
	* Baja de comprobantes 
	*/
	case 330:
	$baj_ccompr="UPDATE comprobantes SET Com_Est='I' WHERE Com_Cod=$Par_Sql[0]";
	return $baj_ccompr;
	break;

	/* 
	* Cargado de la b�squeda de cuentas en la p�gina de registro de comprobantes
	*/
	case 331:
	$bus_xmld_331="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta, empresas WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D' ORDER BY Pld_Cod";
	//echo $bus_xmld_331;
	return $bus_xmld_331;	
	break;

	/* Busqueda de cuentas por codigo */
	case 332:
	$bus_xmlc_332="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta, empresas WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' AND det_plan.Pld_Cdc = '$Par_Sql[0]' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D'";
	//echo $bus_xmlc_332;
	return $bus_xmlc_332;	
	break;

	/*
	* CONSULTA DE COMPROBANTES 
	*/
	case 333:
	$cargar_comprin_333="SELECT Com_Cod, Com_Num, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Ced, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val,Com_Est, Tia_Ini, Tia_Des FROM comprobantes, $Par_Sql[0], persona, tipo_asien WHERE Com_Cod='$Par_Sql[1]' AND comprobantes.Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Tia_Cod = tipo_asien.Tia_Cod $Par_Sql[5]";//Com_Tip
	//echo $cargar_comprin_333;
	return $cargar_comprin_333;
	break;

	/* 
	* Carga de los cheques de un comprobante determinado 
	*/
	case 334:
	$car_cheques= "SELECT det_plan.Pld_Des, persona.Prs_Ape, persona.Prs_Nom, banco.Ban_Cod, banco.Ban_Cue, Che_Num, Che_Val,Che_Cob, Che_Obs, Che_Cod, cheques.Ban_Cod FROM asientos,cheques,proveedore,persona, banco, det_plan WHERE asientos.Asi_Cod=cheques.Asi_Cod AND asientos.Com_Cod=$Par_Sql[0] AND banco.Pld_Cod = det_plan.Pld_Cod AND asientos.Pld_Cod=det_plan.Pld_Cod AND cheques.Prv_Cod=proveedore.Prv_Cod AND proveedore.Prs_Cod=persona.Prs_Cod "; //Ojo antes estaba group by banco.Ban_Cue
	//echo $car_cheques;
	return $car_cheques;
	break;

	/* 
	* Cargar de comprobantes (I-E-A) entre fechas 
	*/
	case 335:
	$car_comfec_335="SELECT Com_Cod, Com_Num, Com_Fec, CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) as Nombre, persona.Prs_Ape, persona.Prs_Nom, Com_Con, ROUND(Com_Val,2) as Com_Val, Com_Gen, Tia_Des, Tia_Ini  
	FROM 
		comprobantes, $Par_Sql[0], persona, tipo_asien 
	WHERE 
		comprobantes.Tia_Cod = tipo_asien.Tia_Cod AND comprobantes.Tia_Cod='$Par_Sql[1]' AND 
		comprobantes.$Par_Sql[2]=$Par_Sql[0].$Par_Sql[2] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND 
		Com_Est='$Par_Sql[5]' AND (Com_Fec BETWEEN '$Par_Sql[3]' AND '$Par_Sql[4]') $Par_Sql[6] AND $Par_Sql[0].Emp_Cod = $Par_Sql[7] ORDER BY Com_Fec";
	echo $car_comfec_335;
	return $car_comfec_335;
	break;

	/* 
	* Cargado de las cuentas en base al DEBE y el HABER 
	*/
	case 336:
	$cargar_cuentas_336="SELECT asientos.Asi_Cod, asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, Asi_Deh, Asi_Val, Pld_Rec FROM asientos, det_plan WHERE asientos.Com_Cod=$Par_Sql[0] AND asientos.Pld_Cod=det_plan.Pld_Cod
	AND Asi_Deh = '$Par_Sql[1]' $Par_Sql[3] $Par_Sql[2]";
	//echo $cargar_cuentas_336;
	return $cargar_cuentas_336;
	break;

	/* 
	* Cargado del detalle de los comprobantes 
	*/
	case 338:
	$cargar_cuentas_338="SELECT comprobantes.Com_Val, Com_Con, Com_Obs, asientos.Asi_Cod, asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, Asi_Deh, ROUND(Asi_Val,2) as Asi_Val, Asi_Val AS Asi_Val2 FROM asientos, det_plan, comprobantes WHERE asientos.Com_Cod=$Par_Sql[0] AND asientos.Pld_Cod=det_plan.Pld_Cod AND comprobantes.Com_Cod = asientos.Com_Cod 
	";//AND Asi_Deh = '$Par_Sql[1]' $Par_Sql[2]
	return $cargar_cuentas_338;
	break;

	/* 
	* Consulta de las cuentas de grupo de los comprobantes de ingreso 
	*/
	case 339:
	$cargar_grupos_339 = "SELECT DISTINCT 
  det_plan1.Pld_Cod,
  det_plan1.Pld_Rec,
  det_plan1.Pld_Des,
  det_plan1.Pld_Cdc
FROM
  asientos
  INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
  INNER JOIN det_plan det_plan1 ON (det_plan.Pld_Rec = det_plan1.Pld_Cod)
WHERE
   asientos.Com_Cod=$Par_Sql[0] AND Asi_Deh = '$Par_Sql[1]' $Par_Sql[2]";
						//echo $cargar_grupos_339;
	return $cargar_grupos_339;
	break;

	/* B�squeda de un cliente por codigo del cliente */
	case 340:
	$bus_clic_340="SELECT Cli_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Cli_Cup, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est FROM cliente, persona WHERE Cli_Cod = '$Par_Sql[0]' AND cliente.Prs_Cod=persona.Prs_Cod ";
	return $bus_clic_340;
	break;

	/* 
	* Consulta todas la facturas para contabilizarlas, este valor debe ser tomado del importe de la factura 
	*/
	case 341:
	$fact_conta_341 = "SELECT (sum(ventas_det.Vet_Imp)) as Importe, iva.Iva_Cod, iva.Iva_Sri, Iva_Por, 
					  (sum(ventas_det.Vet_Imp) - (sum((Vet_Imp * Vet_Des) /100) + sum((Vet_Imp * Vet_Dec) /100))) 
					  as Total, SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva, 
					  ((sum((Vet_Imp * Vet_Des)/100) + sum((Vet_Imp * Vet_Dec)/100))
				      ) as Descuento, ventas_det.Pro_Cod, promocione.Car_Int, periodos.Mod_Cod, Ite_Lar, Car_Nom, Mod_Des
						FROM ventas, caja_aper, ventas_det, iva, notasgener, semestres, promocione, periodos, producto, item,
						carreras, modalidad
						 WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND 
						ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND
						ventas_det.Nge_Cod = notasgener.Nge_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod AND semestres.Pro_Cod
						= promocione.Pro_Cod AND semestres.Per_Int = periodos.Per_Int AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod
						= item.Ite_Cod AND promocione.Car_Int = carreras.Car_Int AND modalidad.Mod_Cod = periodos.Mod_Cod AND
						caja_aper.Caj_Cod = $Par_Sql[0] AND ventas.Vet_Est = 'A' 
						GROUP BY iva.Iva_Cod, iva.Iva_Sri, Iva_Por, ventas_det.Pro_Cod, Car_Int, Mod_Cod
                        ORDER BY Car_Int DESC";
					//echo $fact_conta_341;
	return $fact_conta_341;
	break;

	/* 
	* Consulta las cuentas que tienen relaci�n con los respectivos rubros 
	*/
	case 342:
	$cuentas_rubros_342 = "SELECT produ_plan.Pld_Cod, Pld_Cdc, Pld_Des FROM produ_plan, det_plan WHERE produ_plan.Pld_Cod = det_plan.Pld_Cod AND
							produ_plan.Pro_Cod = $Par_Sql[0] AND produ_plan.Car_Int = $Par_Sql[1] AND produ_plan.Mod_Cod = $Par_Sql[2] AND det_plan.Pla_Cod = $Par_Sql[3]";
			//echo $cuentas_rubros_342;				
	return $cuentas_rubros_342;
	break;

	/* 
	* Consulta las cuentas contables referentes al DEBE - BANCOS 
	*/
	case 343:
	$cuentas_bancos_343 = "SELECT banco.Pld_Cod, Pld_Cdc, Pld_Des, ventas.Vet_Cod, pago_venta.Vet_Tot as Importe, pago_venta.Vet_Che, ventas.Vet_Num 
					FROM banco, ventas, caja_aper, det_plan, pago_venta WHERE banco.Ban_Cod = pago_venta.Ban_Cod AND caja_aper.Caj_Cod = ventas.Caj_Cod 
					AND banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND ventas.Vet_Cod = pago_venta.Vet_Cod AND ventas.Caj_Cod = $Par_Sql[0] AND 
					ventas.Vet_Est = 'A' ORDER BY banco.Ban_Cod ";
		//echo $cuentas_bancos_343;
	return $cuentas_bancos_343;
	break;

	/* Grabada del reporte diario de caja */
	case 344:
	$caja_344 = "INSERT INTO caja_compr (Caj_Cod, Com_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
						//echo $caja_344;
	return $caja_344;
	break;

	/* 
	* Consulta las cuentas contables referentes al DEBE - SIN BANCOS 
	*/
	case 345:
	$cuentas_bancos_345 = "SELECT ventas.Vet_Cod, pago_venta.Vet_Tot as Importe, pago_venta.Vet_Che, ventas.Vet_Num FROM ventas, caja_aper, pago_venta WHERE 
					caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Vet_Cod = pago_venta.Vet_Cod AND ventas.Vet_Cod NOT IN (SELECT ventas.Vet_Cod 
					FROM ventas, caja_aper, pago_venta WHERE caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Vet_Cod = pago_venta.Vet_Cod AND 
					pago_venta.Ban_Cod != '' AND ventas.Caj_Cod = $Par_Sql[0] AND ventas.Vet_Est = 'A') AND ventas.Caj_Cod = $Par_Sql[0] AND ventas.Vet_Est = 'A' ";
					//echo $cuentas_bancos_345;
	return $cuentas_bancos_345;
	break;

	/* 
	* Consulta las cuentas contables referentes al DEBE - SIN BANCOS 
	*/
	case 346:
	$cuentas_haber_snge_346 = "SELECT ventas_det.Pro_Cod, (sum(ventas_det.Vet_Imp)) as Importe, Ite_Lar, ventas.Vet_Cod, SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva FROM ventas, caja_aper, ventas_det, producto, item, iva
						 WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod
						= item.Ite_Cod AND
						ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND ventas_det.Nge_Cod NOT IN (SELECT notasgener.Nge_Cod FROM notasgener)  
                        AND
						caja_aper.Caj_Cod = $Par_Sql[0] AND ventas.Vet_Est = 'A' 
						GROUP BY  ventas_det.Pro_Cod, ventas_det.Vet_Cod";
						//echo $cuentas_haber_snge_346;
	return $cuentas_haber_snge_346;
	break;

	/* 
	* Consulta las cuentas contables referentes al HABER - SOLO RUBROS 
	*/
	case 347:
	$cuentas_haber_rubros_347 = "SELECT produ_plan.Pld_Cod, Pld_Cdc, Pld_Des FROM produ_plan, det_plan WHERE produ_plan.Pld_Cod 
								= det_plan.Pld_Cod AND produ_plan.Pro_Cod = $Par_Sql[0] AND det_plan.Pla_Cod = $Par_Sql[1] GROUP BY produ_plan.Pld_Cod, Pld_Cdc, Pld_Des";
	return $cuentas_haber_rubros_347;
	break;

	/* 
	* CONSULTA DE COMPROBANTES 
	*/
	case 348:
	$cargar_comprin_348="SELECT Com_Cod, Com_Num, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Ced, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val,Com_Est, Tia_Ini, Com_Gen FROM comprobantes, $Par_Sql[0], persona, tipo_asien WHERE Com_Num='$Par_Sql[1]' AND comprobantes.Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Tia_Cod = tipo_asien.Tia_Cod $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom";//Com_Tip
	echo $cargar_comprin_348;
	return $cargar_comprin_348;
	break;

	/* 
	* Consulta el codigo del iva cobrado 
	*/
	case 352:
	$iva_cobrado_352 = "SELECT iva_cobrad.Pld_Cod, det_plan.Pld_Des, det_plan.Pld_Cdc FROM det_plan INNER JOIN iva_cobrad ON (det_plan.Pld_Cod = iva_cobrad.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
	return $iva_cobrado_352;
	break;		
	}
}?>