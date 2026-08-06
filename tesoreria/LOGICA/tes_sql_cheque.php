<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-07-19
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */
function sentencias_che($id,$Par_Sql)
{
	switch($id)
	{
		case 3: 
		/* 
		* Consulta la provicia y pais de la ciudad de la sucursal 
		*/
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
		

		case 4:
		/* 
		* Consulta del usuario
		*/
		$consulta_4 = "SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
		//echo $consulta_4;
		return $consulta_4;
		
		
		case 113: 
		/**
		* Consulta la informaci�n relacionada con el c�digo del periodo contable 
		*/
 		$consul_fecha_113 =	"SELECT Pec_Cod, Pec_Fei, Pec_Fef, YEAR(Pec_Fei) as Ann, Pla_Cod FROM perio_cont WHERE Pec_Cod ='$Par_Sql[0]'";
		//echo $consul_fecha_113;
		return $consul_fecha_113;
		

		case 126: 
		/* 
		* Consulta la informaci�n la ciudada en base a la sucursal 
		*/
 		$cargar_ciudad="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
						//echo $cargar_ciudad;
		return $cargar_ciudad;
		
		
		/**
		* Cargado cheques seg�n el n�mero de comprobante de egreso 
		*/
		case 143:
		$con_cheques_143="SELECT comprobantes.Com_Cod, comprobantes.Com_Num, Pld_Des, Prs_Ape, Che_Cod, Prs_Nom, cheques.Asi_Cod, cheques.Prv_Cod, Che_Num, Che_Val, Che_Cob, Che_Obs, Com_Est, Che_Fec, cheques.Ban_Cod, cheques.Prv_Cod, cheques.Che_Est FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona where comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod
      AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod  
      AND comprobantes.Com_Cod = $Par_Sql[0] ORDER BY Che_Num ASC";
	// echo $con_cheques_143;
		return $con_cheques_143;
		break;

		case 144:
		/**
		* Cargado individual de cheque en el reporte
		*/
		$sql="SELECT comprobantes.Com_Cod, Pld_Des, IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',Prs_Ape,Che_Ben) AS Prs_Ape, IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',Prs_Nom,'') AS Prs_Nom, Che_Ben, Che_Num, ROUND(Che_Val,2) as Che_Val,Che_Ben, Che_Cob, Che_Fec FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona WHERE comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod AND cheques.Che_Cod = $Par_Sql[0] AND cheques.Asi_Cod = $Par_Sql[1] AND cheques.Ban_Cod = $Par_Sql[2] AND cheques.Prv_Cod = $Par_Sql[3]";
                    //echo $sql;
		return $sql;
		
		
		/** 
		* Cargado de la cabecera del comprobante por codigo 
		*/
		case 149:
		$cargar_cabcod_149="SELECT Tia_Abr,Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est FROM comprobantes, $Par_Sql[0], persona,tipo_asien WHERE Com_Cod =$Par_Sql[1] AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A' AND comprobantes.Tia_Cod=tipo_asien.Tia_Cod";
		//echo $cargar_cabcod_149;
		return $cargar_cabcod_149;
		

		case 163: 
		/**
		* Selecciona los cheques entre un rango de fechas 
		*/
 		$cons_cheq_163 = "SELECT Tia_Abr,comprobantes.Com_Cod, comprobantes.Com_Num, comprobantes.Com_Est, Pld_Des, Prs_Ape, Che_Cod, Prs_Nom, cheques.Asi_Cod, cheques.Prv_Cod, Che_Num, Che_Val, Che_Fec, Che_Obs, comprobantes.Com_Fec, cheques.Che_Est, Com_Con "
                        . "FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona,tipo_asien "
                        . "where comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod AND (cheques.Che_Fec BETWEEN '$Par_Sql[0] 00:00:00' AND '$Par_Sql[1] 23:59:59') AND Che_Est = '$Par_Sql[2]'
                          AND tipo_asien.Tia_Cod=comprobantes.Tia_Cod AND Emp_Cod=$Par_Sql[3]  AND Com_Est = 'A' ORDER BY banco.Ban_Cod, Che_Num, Prs_Ape, Prs_Nom";
		//echo $cons_cheq_163;
		return $cons_cheq_163;
		
		
		/**
		* Inserci�n de los cheques de los comprobantes de egreso 
		*/
		case 190:
                    //var_dump($Par_Sql);
		$ins_cheques_190="INSERT INTO cheques SET Prv_Cod=$Par_Sql[0], Ban_Cod=$Par_Sql[1], Asi_Cod=$Par_Sql[2], Che_Num='$Par_Sql[3]',".//" Che_Cob='$Par_Sql[4]',"
                        " Che_Val=$Par_Sql[5], Che_Obs=UPPER('$Par_Sql[6]'), Che_Fec='$Par_Sql[7]', Che_Cod = $Par_Sql[8] ;";
		//echo "<br/>".$ins_cheques_190;
		return $ins_cheques_190;
		

		/**
		* Anulaci�n de los cheques de los comprobantes de egreso 
		*/
		case 191:
		$ins_cheques_191="UPDATE cheques SET Che_Est = 'I' WHERE Prv_Cod=$Par_Sql[0] AND Ban_Cod=$Par_Sql[1] AND Asi_Cod=$Par_Sql[2] AND Che_Cod = $Par_Sql[3]";
		//echo $ins_cheques_191;
		return $ins_cheques_191;
		
		case 207:
		/* SENTECIAS UTILILES EN REPORTES PARA CABECERAS */
		/* Consulta que permite cargar el nombre de la empresa a que pertenece el usuario */
		$cabecera_empresa = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des FROM empresas, sucursal, ciudad WHERE empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Suc_Cod = $Par_Sql[0] AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
		//echo "<br>".$cabecera_empresa;
		return  $cabecera_empresa;
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
		
		
		/**
		* B�squeda de un proveedor por apellido 
		*/
		case 301:
		$bus_proa_301="SELECT Prv_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est FROM proveedore, persona WHERE (Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%') AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1]";
		//echo $bus_proa_301;
		return $bus_proa_301;
		

		/**
		* B�squeda de un proveedor por C�dula 
		*/
		case 302:
		$bus_proc_302="SELECT Prv_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est FROM proveedore, persona WHERE Prs_Ced LIKE '%$Par_Sql[0]%' AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1]";
//                    echo $bus_proa_302;
		return $bus_proc_302;
		

	/* Cargado del Periodo Contable activo */
	case 303:
	$cargar_percon="SELECT Pec_Cod FROM perio_cont WHERE Now() BETWEEN Pec_Fei AND Pec_Fef AND Pec_Est='A'";
	return $cargar_percon;
	break;

		/**
		* Cargado de los bancos que van a ser agregados al combobox 
		*/
		case 304:
		$cargar_combo_304="SELECT CONCAT(Ban_Cod,'*',asientos.Asi_Cod) as Banasi, det_plan.Pld_Des, asientos.Asi_Val FROM asientos, banco, det_plan WHERE asientos.Pld_Cod=banco.Pld_Cod AND banco.Pld_Cod=det_plan.Pld_Cod AND asientos.Com_Cod=$Par_Sql[0] AND asientos.Asi_Deh = 'H' AND banco.Ban_Tip = 'B'";
		//echo $cargar_combo_304;
		return $cargar_combo_304;
		

		/**
		* Cargado de la cabecera del comprobante, sea este de cualquier tipo 
		*/
		case 305:
		$cargar_cabcomp_305="SELECT Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est, Com_Gen FROM comprobantes, $Par_Sql[0], persona WHERE Com_Num='$Par_Sql[1]' AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' $Par_Sql[5]
	AND comprobantes.Com_Cod NOT IN
	(SELECT asientos.Com_Cod FROM asientos, cheques, comprobantes WHERE asientos.Asi_Cod=
 cheques.Asi_Cod AND comprobantes.Com_Cod = asientos.Com_Cod AND cheques.Che_Est='A' AND comprobantes.Pec_Cod='$Par_Sql[3]')";
		//echo $cargar_cabcomp_305;
		return $cargar_cabcomp_305;
		

		/**
		* Cargado de las cuentas del comprobante (Resumen)
		*/
		case 306:
		$cargar_cuentas="SELECT asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, Asi_Deh, ROUND(Asi_Val,2) as Asi_Val FROM asientos, det_plan WHERE asientos.Com_Cod=$Par_Sql[0] AND asientos.Pld_Cod=det_plan.Pld_Cod";
		return $cargar_cuentas;
		
	
	/* Inserci�n de los cheques de los comprobantes de egreso */
	case 307:
	$ins_cheques_307="INSERT INTO cheques SET Prv_Cod=$Par_Sql[0], Ban_Cod=$Par_Sql[1], Asi_Cod=$Par_Sql[2], Che_Num='$Par_Sql[3]', Che_Val=$Par_Sql[4], Che_Obs=UPPER('$Par_Sql[5]'), Che_Fec='$Par_Sql[6]', Che_Cod = $Par_Sql[7]";
	//echo $ins_cheques_307;
	return $ins_cheques_307;
	

		/**
		* Carga de los cheques de un comprobante determinado 
		*/
		case 309:
		$car_cheques="SELECT Ban_Cod, cheques.Asi_Cod, persona.Prs_Ape, persona.Prs_Nom, cheques.Prv_Cod, Che_Num, Che_Val, Che_Fec, Che_Cob, Che_Obs, Che_Cod, Che_Est, det_plan.Pld_Des,
			(SELECT ant.Atp_Cod FROM pago_anticipo_proveedores AS pga
				INNER JOIN anticipos_proveedores AS ant ON ant.Atp_Cod = pga.Atp_Cod
				WHERE pga.Asi_Cod = cheques.Asi_Cod AND ant.Atp_Est IN ('A','U','C') LIMIT 1) AS Atp_Cod,
			(SELECT ant.Atp_Est FROM pago_anticipo_proveedores AS pga
				INNER JOIN anticipos_proveedores AS ant ON ant.Atp_Cod = pga.Atp_Cod
				WHERE pga.Asi_Cod = cheques.Asi_Cod AND ant.Atp_Est IN ('A','U','C') LIMIT 1) AS Atp_Est
			FROM asientos,cheques,proveedore,persona, det_plan WHERE asientos.Asi_Cod=cheques.Asi_Cod AND asientos.Com_Cod=$Par_Sql[0] AND cheques.Prv_Cod=proveedore.Prv_Cod AND proveedore.Prs_Cod=persona.Prs_Cod AND asientos.Pld_Cod = det_plan.Pld_Cod ORDER BY cheques.Che_Num";
		return $car_cheques;
		

		/**
		* Carga de los cheques de un comprobante determinado 
		*/
		case 310:
		$del_cheques_310="DELETE FROM cheques WHERE Asi_Cod=$Par_Sql[0] AND cheques.Che_Est = 'A'";
		//	echo $del_cheques_310;
		return $del_cheques_310;
		

		/**
		* Cargado de la cabecera del comprobante, sea este de cualquier tipo 
		*/
		case 311:
		$cargar_cheques_311="SELECT Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est, Com_Gen FROM comprobantes, $Par_Sql[0], persona WHERE (Prs_Ape like '%$Par_Sql[1]%' OR Prs_Nom like '%$Par_Sql[1]%') AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' $Par_Sql[5]
	AND comprobantes.Com_Cod NOT IN
	(SELECT asientos.Com_Cod FROM asientos, cheques, comprobantes WHERE asientos.Asi_Cod=
 cheques.Asi_Cod AND comprobantes.Com_Cod = asientos.Com_Cod AND cheques.Che_Est='A' AND comprobantes.Pec_Cod='$Par_Sql[3]')";
		//echo $cargar_cheques_311;
		return $cargar_cheques_311;
		

		/**
		* Consulta de los comprobantes que estan en la tabla cheques 
		*/
		case 312:
		$cargar_cheques_312="SELECT comprobantes.Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est FROM comprobantes, $Par_Sql[0], persona, asientos, cheques WHERE (Prs_Ape like '%$Par_Sql[1]%' OR Prs_Nom like '%$Par_Sql[1]%') AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A' $Par_Sql[5]
	AND comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod 
	GROUP BY comprobantes.Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, 
	Com_Con, Com_Obs, Com_Fec, Com_Val, Com_Est";
//		echo $cargar_cheques_312;
		return $cargar_cheques_312;
		

		/**
		* Consulta de los comprobantes que estan en la tabla cheques en base al codigo
		*/
		case 313:
		$cargar_cabcomp_313="SELECT comprobantes.Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est FROM comprobantes, $Par_Sql[0], persona, asientos, cheques WHERE Com_Num='$Par_Sql[1]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A' $Par_Sql[5]
	AND comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod 
	GROUP BY comprobantes.Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, 
	Com_Con, Com_Obs, Com_Fec, Com_Val, Com_Est";
		//echo $cargar_cabcomp_313;
		return $cargar_cabcomp_313;
		
		/**
		* Consulta los proveedores que pueden recibir varios cheques 
		*/
		case 314:
		$cheques_varios_314="SELECT proveedore.Prv_Cod FROM varicheque, proveedore WHERE proveedore.Prv_Cod = varicheque.Prv_Cod AND proveedore.Emp_Cod = $Par_Sql[0]";
		//echo $cheques_varios_314;
		return $cheques_varios_314;
		
	
		/* Cargado del detalle de los comprobantes */
	case 338:
	$cargar_cuentas_338="SELECT comprobantes.Com_Val, Com_Con, Com_Obs, asientos.Asi_Cod, asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, Asi_Deh, ROUND(Asi_Val,2) as Asi_Val, Asi_Val AS Asi_Val2 FROM asientos, det_plan, comprobantes WHERE asientos.Com_Cod=$Par_Sql[0] AND asientos.Pld_Cod=det_plan.Pld_Cod AND comprobantes.Com_Cod = asientos.Com_Cod 
	";//AND Asi_Deh = '$Par_Sql[1]' $Par_Sql[2]
	return $cargar_cuentas_338;
	break;
                case 339:
                    $sql="SELECT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef,Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue FROM banco
                        INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                        INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                        WHERE Ban_Est='A' AND plan_cuenta.Emp_Cod=$Par_Sql[0] AND Ban_Cue!='0' AND Ban_Cue!='' GROUP BY Ban_Cod;";
                    //ChromePhp::log($sql);
                return $sql;
                case 340:
                    if($Par_Sql[1]=="")
                        {$bancoSql="";}
                    else
                        {$bancoSql=" AND cheques.Ban_Cod=$Par_Sql[1]";}
                    $sql="SELECT CONCAT_WS('-',Asi_Cod,Che_Cod) as id,Che_Cod,Asi_Cod, CONCAT_WS(' ',Prs_Ape,Prs_Nom) as proveedor,CONCAT_WS(' ',Pld_Des,'- ( Cta.#',Ban_Cue,')  ') as banco,Che_Num,Che_Val,Che_Fec,Che_Cob,Che_Est FROM cheques
                        JOIN banco ON banco.Ban_Cod=cheques.Ban_Cod
                        INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                        JOIN proveedore ON proveedore.Prv_Cod=cheques.Prv_Cod
                        JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                        WHERE Che_Est='A' $bancoSql AND plan_cuenta.Emp_Cod=$Par_Sql[0] $Par_Sql[2] ;";
                    //echo $sql;
                return $sql;
                case 341:
                    $id=  explode("-", $Par_Sql[2]);
		$ins_cheques_191="UPDATE cheques SET Che_Est = '$Par_Sql[0]',Che_Cob = '$Par_Sql[1]' WHERE Asi_Cod=$id[0] AND Che_Cod=$id[1]";
		//echo $ins_cheques_191;
		return $ins_cheques_191;
                case 342:
		$ins_cheques_191="SELECT asientos.Asi_Cod,Pld_Cdc, asientos.Asi_Deh, sum(asientos.Asi_Val) as Total,det_plan.Pld_Des,Ban_Cod,Ban_Cue FROM asientos  
                    INNER JOIN det_plan ON asientos.Pld_Cod = det_plan.Pld_Cod
                    INNER JOIN banco ON banco.Pld_Cod = det_plan.Pld_Cod
                    LEFT JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod
                    WHERE  
                        comprobantes.Com_Est = 'A'				  
                        AND det_plan.Pld_Cod = '$Par_Sql[0]'  
                        AND comprobantes.Com_Fec <= '$Par_Sql[1] 23:59:59'
                        GROUP BY asientos.Asi_Deh ORDER BY asientos.Asi_Deh ASC";
		//echo $ins_cheques_191;
		return $ins_cheques_191;
                case 343:
                    if($Par_Sql[3]=='d'){$Par_Sql[3]="(Prs_Ape like '%$Par_Sql[0]%' OR Prs_Nom like '%$Par_Sql[0]%')";}
                    else{$Par_Sql[3]="Com_Num='$Par_Sql[0]'";}
                    $ins_cheques_191="SELECT Tia_Abr,comprobantes.Com_Cod,Pld_Cod,Asi_Deh, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est, Com_Gen 
                        FROM comprobantes, proveedore, persona, asientos,tipo_asien 
                        WHERE $Par_Sql[3]
                        AND Asi_Deh='$Par_Sql[4]' 
                        AND Pld_Cod=$Par_Sql[2]
                        AND comprobantes.Com_Cod=asientos.Com_Cod AND comprobantes.Tia_Cod=tipo_asien.Tia_Cod 
                        AND comprobantes.Prv_Cod=proveedore.Prv_Cod 
                        AND proveedore.Prs_Cod=persona.Prs_Cod 
                        AND comprobantes.Pec_Cod='$Par_Sql[1]'                         
                        AND Com_Est='A'
                        AND comprobantes.Com_Cod
                        NOT IN 
                                (	SELECT asientos.Com_Cod 
                                        FROM asientos, cheques, comprobantes 
                                WHERE asientos.Asi_Cod= cheques.Asi_Cod         
                                AND comprobantes.Com_Cod = asientos.Com_Cod 
                                AND cheques.Che_Est='A' 
                                AND comprobantes.Pec_Cod='$Par_Sql[1]')";
		//echo $ins_cheques_191;
		return $ins_cheques_191;
                case 344:
		$ins_cheques_191="SELECT CONCAT_WS('-',asientos.Asi_Cod,Che_Cod) as id,Com_Fec,cheques.Asi_Cod,Che_Cod,CONCAT(' CH ',Che_Num) as Che_Num,Che_Val,Asi_Glo,Che_Fec,CONCAT_WS(' ',Prs_Ape,Prs_Nom) as proveedor,CONCAT(' ',Tia_Abr,' - ',CAST( cheques.Asi_Cod AS CHAR)) as asiento FROM cheques
                                    INNER JOIN proveedore ON cheques.Prv_Cod= proveedore.Prv_Cod
                                    INNER JOIN persona ON proveedore.Prs_Cod= persona.Prs_Cod
                                    INNER JOIN asientos ON cheques.Asi_Cod= asientos.Asi_Cod
                                    INNER JOIN comprobantes ON asientos.Com_Cod= comprobantes.Com_Cod
                                    INNER JOIN tipo_asien ON comprobantes.Tia_Cod= tipo_asien.Tia_Cod
                                    WHERE (Che_Est='A' OR Che_Est='C')  AND Com_Est='A' AND Pld_Cod=$Par_Sql[0]
                                        AND comprobantes.Com_Fec <= '$Par_Sql[2] 23:59:59'
                                        AND (cheques.Che_Cob >= '$Par_Sql[2] 23:59:59' OR cheques.Che_Cob IS NULL)
                                    ORDER BY Com_Fec";
		//echo $ins_cheques_191;
		return $ins_cheques_191;
                case 345:
					/* 
					* d=Apellidos, n=No. de Cheque, r=No. de Comprobante 
					* Sin texto de busqueda se listan todos los comprobantes del periodo.
					* En 'd' se busca sobre el nombre completo en los dos ordenes posibles y sobre el 
					* beneficiario del cheque, que es el dato que se muestra en la columna Proveedor.
					*/
					if(trim($Par_Sql[0])=='')
					{
						$Par_Sql[3]="1=1";
					}
					elseif($Par_Sql[3]=='d')
					{
						$Par_Sql[3]="(CONCAT_WS(' ',persona.Prs_Ape,persona.Prs_Nom) like '%$Par_Sql[0]%'
								OR CONCAT_WS(' ',persona.Prs_Nom,persona.Prs_Ape) like '%$Par_Sql[0]%'
								OR cheques.Che_Ben like '%$Par_Sql[0]%')";
					}
					elseif($Par_Sql[3]=='n')
					{
						$Par_Sql[3]="Che_Num like '%$Par_Sql[0]%'";
					}
					elseif($Par_Sql[3]=='r')
					{
						$Par_Sql[3]="Com_Num='$Par_Sql[0]'";
					}
					else
					{
						$Par_Sql[3]="1=0";
					}
                    if($Par_Sql[4]!=""){
						$Par_Sql[4]="AND asientos.Pld_Cod=".$Par_Sql[4];
					}
					/* 
					* Par_Sql[5]='S' incluye tambien los comprobantes anulados. 
					* Si no se envia el parametro se mantiene el comportamiento historico: solo activos.
					*/
					$Par_Sql[5]=(isset($Par_Sql[5])&&$Par_Sql[5]=='S')?"":"AND comprobantes.Com_Est='A'";
                    $sql="SELECT Tia_Abr,comprobantes.Com_Cod,Che_Num, Com_Num, proveedore.Prv_Cod, Prs_Ced, IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',Prs_Ape,Che_Ben) AS Prs_Ape, IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',Prs_Nom,'') AS Prs_Nom, Che_Ben, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est 
                                FROM comprobantes, proveedore, persona, asientos, cheques, det_plan,plan_cuenta,tipo_asien
                                WHERE $Par_Sql[3]
                                AND comprobantes.Pec_Cod='$Par_Sql[2]'
                                $Par_Sql[4]
                                AND plan_cuenta.Emp_Cod=$Par_Sql[1]
                                AND comprobantes.Prv_Cod=proveedore.Prv_Cod AND comprobantes.Tia_Cod=tipo_asien.Tia_Cod 
                                AND proveedore.Prs_Cod=persona.Prs_Cod 
                                $Par_Sql[5]
                                AND comprobantes.Com_Cod = asientos.Com_Cod 
                                AND asientos.Asi_Cod = cheques.Asi_Cod 
                                AND det_plan.Pld_Cod=asientos.Pld_Cod
                                AND det_plan.Pla_Cod=plan_cuenta.Pla_Cod                                
                                GROUP BY comprobantes.Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, Com_Val, Com_Est ORDER BY Che_Num ASC";
			//ChromePhp::log($sql);
		return $sql;
                
                case 346:
		$ins_cheques_191="UPDATE cheques SET Che_Est = 'I' WHERE Asi_Cod='$Par_Sql[0]'";
		//echo $ins_cheques_191;
		return $ins_cheques_191;
                case 347:
		/* Consulta de los tipos de asientos  filtrados por el sub-tipo */
                    if($Par_Sql[0]=="") $Par_Sql[0]=" WHERE Tia_Tip='B'";
                    else $Par_Sql[0]="";
		$sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien $Par_Sql[0] ";
		return $sql;
                case 348:		
		$sql = "SELECT * FROM file_banco WHERE Fil_Cod_Rec=0";
		return $sql;
                case 349:		
		$sql = "SELECT Fil_Cod,Flc_Cam FROM file_campo";
                return $sql;
                case 350:		
		$sql = "SELECT Flc_Cod,Flc_Cam,file_campo.Fil_Cod,file_banco.Fil_Cam FROM file_campo 
                        INNER JOIN file_banco ON file_banco.Fil_Cod=file_campo.Fil_Cod
                        INNER JOIN file_banco as parent ON file_banco.Fil_Cod_Rec=parent.Fil_Cod
                        WHERE parent.Fil_Cam='$Par_Sql[0]' ";    
		return $sql;
                case 351:
                    if($Par_Sql[2]=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";}
                    else {$search="Prs_Ced LIKE '$Par_Sql[0]%'";}
                    if($Par_Sql[3]==""){$campos="COUNT(Prv_Cod) as total";}
                    else{
                        $Par_Sql[3]="ORDER BY Prs_Ape ".$Par_Sql[3];
                        $campos=" Prv_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor,Prs_Ape,Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
                    }
                    $bus_xmld_331="SELECT $campos
                                FROM proveedore, persona WHERE $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
                //echo $bus_xmld_331;
                return $bus_xmld_331;                
                case 352:
                    if($Par_Sql[3]=="d") {$search="det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";}
                    else {$search="det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";}
                    if($Par_Sql[4]==""){$campos="COUNT(det_plan.Pld_Cod) as total";}
                    else{
                        $Par_Sql[4]="ORDER BY det_plan.Pld_Cod ".$Par_Sql[4];
                        $campos="det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
                                IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
                                IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
                    }
                    $bus_xmld_331="SELECT $campos
                                FROM det_plan 
                                INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                                INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                                INNER JOIN empresas ON plan_cuenta.Emp_Cod=empresas.Emp_Cod 
                                LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
                                LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
                                WHERE plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' 
                                AND $search AND Pec_Cod =$Par_Sql[2] 
                                AND det_plan.Pld_Tip = 'D' $Par_Sql[4]";
                //echo $bus_xmld_331;
                return $bus_xmld_331;
                case 353:                 
		$ins_cheques_191="SELECT cheques.Asi_Cod,cheques.Che_Est FROM cheques
                    INNER JOIN asientos ON cheques.Asi_Cod=asientos.Asi_Cod
                    INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                    WHERE Emp_Cod=$Par_Sql[0] AND Che_Num='$Par_Sql[1]' AND det_plan.Pld_Cod=$Par_Sql[2] AND (Che_Est='A' OR Che_Est='C')";
		//echo $ins_cheques_191;
		return $ins_cheques_191;
                case 354:                 
		$ins_cheques_191="UPDATE cheques SET Che_Est = '$Par_Sql[0]',Che_Cob = '$Par_Sql[1]' WHERE Asi_Cod=$Par_Sql[2] AND Che_Num=$Par_Sql[3]";
		//echo $ins_cheques_191;
		return $ins_cheques_191;
                case 355:
		/*
		* Selecionar el numero maximo de comprobante mensual segun el tipo I=ingreso, E=egreso, D=diario
		*/
		$sql="SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes, tipo_asien WHERE comprobantes.Tia_Cod=tipo_asien.Tia_Cod AND 
                tipo_asien.Tia_Ini='$Par_Sql[0]' AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
		return $sql;
                case 356:
                $ins_compi="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[9]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'), Com_Tipo='$Par_Sql[8]', Com_Doc='$Par_Sql[10]',Usu_Cod='$_SESSION[Ses_Usu_Cod]' ";//Antes Com_Tip
                //echo $ins_compi;
                return $ins_compi;
                /* 
                * Inserci�n de cada asiento del comprobante 
                */
                case 357:
                $ins_asie="INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5]";
                //echo $ins_asie."<br>";
                return $ins_asie;
                case 358:
                $ins_asie="SELECT comprobantes.Com_Cod, Com_Doc,det_plan.Pld_cod,Pld_des FROM comprobantes
                            INNER JOIN asientos ON comprobantes.Com_Cod=asientos.Com_Cod
                            INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
                            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                            WHERE Com_Doc='$Par_Sql[0]' AND Emp_Cod=$Par_Sql[1] AND asientos.Pld_Cod=$Par_Sql[2]";
                //echo $ins_asie."<br>";
                return $ins_asie;
                case 359:
                    if($Par_Sql[2]=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";}
                    else {$search="Prs_Ced LIKE '$Par_Sql[0]%'";}
                    if($Par_Sql[3]==""){$campos="COUNT(Cli_Cod) as total";}
                    else{
                        $Par_Sql[3]="ORDER BY Prs_Ape ".$Par_Sql[3];
                        $campos=" Cli_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as clientes, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est";
                    }
                    $bus_xmld_331="SELECT $campos
                                FROM cliente, persona WHERE $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
                //echo $bus_xmld_331;
                    return $bus_xmld_331;
                case 360:
                    $sql="SELECT MAX(Che_Num) as Che_Num FROM cheques WHERE Ban_Cod=$Par_Sql[0];";
                    
                    return $sql;
                case 361:
                $ins_asie="SELECT comprobantes.Com_Cod, Com_Doc,det_plan.Pld_cod,Pld_des FROM comprobantes
                            INNER JOIN asientos ON comprobantes.Com_Cod=asientos.Com_Cod
                            INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
                            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                            WHERE Com_Doc='$Par_Sql[0]' AND Emp_Cod=$Par_Sql[1] AND asientos.Pld_Cod=$Par_Sql[2] AND asientos.Asi_Deh='$Par_Sql[3]'";
                //echo $ins_asie."<br>";
                return $ins_asie;
                case 362:
                    if(($Par_Sql[2]*1)==2) $cheq="AND Che_Fec>'$Par_Sql[3]'"; 
                    else{ if(empty($Par_Sql[3])||empty($Par_Sql[4])) $cheq=''; else $cheq="AND Che_Fec BETWEEN '$Par_Sql[3]' AND '$Par_Sql[4]'";  }
                    if(($Par_Sql[2]*1)==3)$cheq=$cheq." AND cheques.Che_Est='C'";
                    if(($Par_Sql[2]*1)==4)$cheq=$cheq." AND cheques.Che_Est='A'";
                    if(($Par_Sql[2]*1)==5)$cheq=$cheq." AND cheques.Che_Est='I'";
                    if(($Par_Sql[2]*1)==6)$cheq=$cheq." AND cheques.Che_Est='P'";
                    $ins_asie="SELECT CONCAT(CAST(cheques.Ban_Cod AS CHAR),'_',CAST(Che_Num as CHAR),'_',CAST(Che_Cod as CHAR)) as Che_Cod,Com_Fec,Pld_Des,Ban_Cue,Che_Num,Che_Obs,Che_Fec,ROUND(Che_Val,2) AS Che_Val,CONCAT(Prs_Ape,' ',Prs_Nom) AS Beneficiario,IF(Che_Est='A','No Cobrado',IF(Che_Est='C','Cobrado',IF(Che_Est='I','Anulado','Protestado'))) AS estado,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Num,Che_Cob  FROM cheques
                        INNER JOIN banco ON banco.Ban_Cod=cheques.Ban_Cod
                        INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN proveedore ON proveedore.Prv_Cod=cheques.Prv_Cod
                        INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                        INNER JOIN asientos ON asientos.Asi_Cod=cheques.Asi_Cod
                        INNER JOIN comprobantes ON comprobantes.Com_Cod=asientos.Com_Cod
                        INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                        WHERE Emp_Cod=$Par_Sql[0] AND cheques.Ban_Cod=$Par_Sql[1] $cheq
                        ORDER BY Che_Fec";
                //echo $ins_asie."<br>";
                return $ins_asie;
                case 363:// insertar persona para beneficiario
                $ins_asie="INSERT INTO persona SET Prs_Ced='0',Prs_Ape=UPPER('$Par_Sql[0]'),Prs_Nom=UPPER('$Par_Sql[1]'),Ciu_Cod=217";
                //echo $ins_asie."<br>";
                return $ins_asie;
                case 364:// insertar proveedor para beneficiario
                $ins_asie="INSERT INTO proveedore SET Prs_Cod=$Par_Sql[0], Emp_Cod=$Par_Sql[1],Prv_Con='N',Prv_Esp='N'";
                //echo $ins_asie."<br>";
                return $ins_asie;                
                case 365:// insertar persona para beneficiario
                $ins_asie="SELECT Prv_Cod,UPPER(Prs_Nom) AS Prs_Nom,UPPer(Prs_Ape) AS Prs_Ape FROM proveedore 
                        INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                        WHERE Emp_Cod=$Par_Sql[0] AND (Prs_Ape LIKE '%$Par_Sql[1]%' OR Prs_Nom LIKE '%$Par_Sql[1]%' OR Prs_Ced LIKE '$Par_Sql[1]%')
                        LIMIT 7";
                //echo $ins_asie."<br>";
                return $ins_asie;  
                case 366:// anular comprobante
                $ins_asie="UPDATE comprobantes set Com_Est='I' WHERE Com_Cod=$Par_Sql[0] ";
                //echo $ins_asie."<br>";
                return $ins_asie; 
                case 367:// anular comprobante
                $ins_asie="SELECT * FROM asientos WHERE Asi_Cod=$Par_Sql[0] ";
                //echo $ins_asie."<br>";
                return $ins_asie; 
                case 368:// anular comprobante
                $ins_asie="SELECT COUNT(Che_Cod) AS conteo FROM cheques WHERE Ban_Cod=$Par_Sql[0] AND Che_Num='$Par_Sql[1]' ";
                //echo $ins_asie."<br>";
                return $ins_asie; 
                case 369:// anular comprobante
                $ins_asie="UPDATE cheques set Che_Cob='$Par_Sql[0]' WHERE Ban_Cod='$Par_Sql[1]' AND Che_Num='$Par_Sql[2]' AND Che_Cod=$Par_Sql[3] ";
                //echo $ins_asie."<br>";
                return $ins_asie; 

                case 370:
                $ins_asie="SELECT Ban_Cod,banco.Pld_Cod,Pld_Des FROM banco
                        INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                        WHERE Emp_Cod='$Par_Sql[0]' AND Ban_Cue<>'0' AND Pld_Est='A' AND Pla_Est='A' AND Ban_Est='A'";
                //echo $ins_asie."<br>";
                return $ins_asie;
                case 371:
                $sql="INSERT INTO cheques_otros(Prv_Cod,Ban_Cod,Che_Num,Che_Fec,Che_Cob,Che_Val,Che_Obs,Pld_Cod,Com_Fec,Che_Est)
                                VALUES ($Par_Sql[Prv_Cod],$Par_Sql[Ban_Cod],$Par_Sql[Che_Num],'$Par_Sql[Che_Fec]',".(isset($Par_Sql['Che_Cob'])?"$Par_Sql[Che_Cob]":'NULL').",$Par_Sql[Che_Val],'$Par_Sql[Che_Obs]',$Par_Sql[Pld_Cod],'$Par_Sql[Che_Fec]','$Par_Sql[Che_Est]');";
                //echo $sql."<br>";
                return $sql;
                case 372:
  $sql="SELECT CONCAT_WS('-',Asi_Cod,Che_Cod) as id,Com_Fec,Asi_Cod,Che_Cod,CONCAT(' CH ',Che_Num) as Che_Num,Che_Val,Che_Obs AS Asi_Glo,Che_Fec,CONCAT_WS(' ',Prs_Ape,Prs_Nom) as proveedor,CONCAT(' ',Asi_Cod) as asiento FROM cheques_otros
                                    INNER JOIN proveedore ON cheques_otros.Prv_Cod= proveedore.Prv_Cod
                                    INNER JOIN persona ON proveedore.Prs_Cod= persona.Prs_Cod                                    
                                    WHERE (Che_Est='A' OR Che_Est='C')  AND Ban_Cod=$Par_Sql[3] AND Emp_Cod='$Par_Sql[0]'
                                     AND Che_Fec <= '$Par_Sql[2] 23:59:59'
                                        AND (Che_Cob >= '$Par_Sql[2] 23:59:59' OR Che_Cob IS NULL)
                                    ORDER BY Che_Fec";
				//echo $sql;
				return $sql;

                case 373:  
				  $sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien WHERE Tia_Ini='$Par_Sql[1]' ".($Par_Sql[0]==''?'':" AND Tia_Tip='$Par_Sql[0]'");
				return $sql;
  
				case 374:
				/*
				* Selecionar el numero maximo de comprobante mensual segun el tipo I=ingreso, E=egreso, D=diario
				*/
				$sql="SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod=$Par_Sql[0] AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
				//echo $sql;
				return $sql;
				
				case 375: 
				/**
				* Selecciona los cheques entre un rango de fechas ANULADOR
				*/
				$cons_cheq_163 = "SELECT Tia_Abr,comprobantes.Com_Cod, comprobantes.Com_Num, comprobantes.Com_Est, Pld_Des, Prs_Ape, Che_Cod, Prs_Nom, cheques.Asi_Cod, cheques.Prv_Cod, Che_Num, Che_Val, Che_Fec, Che_Obs, comprobantes.Com_Fec, cheques.Che_Est, Com_Con "
								. "FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona,tipo_asien "
								. "where comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod AND (cheques.Che_Fec BETWEEN '$Par_Sql[0] 00:00:00' AND '$Par_Sql[1] 23:59:59') AND Che_Est = '$Par_Sql[2]'
								  AND tipo_asien.Tia_Cod=comprobantes.Tia_Cod AND Emp_Cod=$Par_Sql[3]  AND Com_Est = '$Par_Sql[4]' ORDER BY banco.Ban_Cod, Che_Num, Prs_Ape, Prs_Nom";
				//echo $cons_cheq_163;
				return $cons_cheq_163;
				case 374:
        /*
        * Selecionar el numero maximo de comprobante mensual segun el tipo I=ingreso, E=egreso, D=diario
        */
        $sql="SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod=$Par_Sql[0] AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
        //echo $sql;
         return $sql;
    case 376:     
        $sql="SELECT COUNT(Che_Cod)AS conteo FROM cheques_otros WHERE Ban_Cod=$Par_Sql[0] AND Che_Num=$Par_Sql[1] UNION ALL SELECT COUNT(Che_Cod)AS conteo FROM cheques WHERE Ban_Cod=$Par_Sql[0] AND Che_Num=$Par_Sql[1];";
        //echo $sql."<br>";
        return $sql;
    case 377:
        $sql="SELECT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef,Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue FROM banco
            INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
            INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
            WHERE Ban_Est='A' AND plan_cuenta.Emp_Cod=$Par_Sql[0] AND Ban_Cue!='0' AND Ban_Cue!='' GROUP BY Ban_Cod;";
        //echo $sql;
        return $sql; 
    case 378:
        $sql="SELECT CONCAT_WS('-','CH',Che_Cod) as id,Che_Cod,Asi_Cod, CONCAT_WS(' ',Prs_Ape,Prs_Nom) as proveedor,CONCAT_WS(' ',Pld_Des,'- ( Cta.#',Ban_Cue,')  ') as banco,Che_Num,Che_Val,Che_Fec,Che_Cob,Che_Est,'EXT' AS t_type  FROM cheques_otros
                        JOIN banco ON banco.Ban_Cod=cheques_otros.Ban_Cod
                        INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                        JOIN proveedore ON proveedore.Prv_Cod=cheques_otros.Prv_Cod
                        JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                        WHERE Che_Est='A' $bancoSql AND plan_cuenta.Emp_Cod=$Par_Sql[0] $Par_Sql[2] ;";
        //echo $sql;
        return $sql;  
    case 379:            
        $id=  explode("-", $Par_Sql[2]);        
        $sql="UPDATE cheques_otros SET ".(empty($Par_Sql[0])?'':"Che_Est='$Par_Sql[0]',")."Che_Cob = '$Par_Sql[1]' WHERE Che_Cod=$id[1]";
        //echo $sql;
        return $sql;  
    case 380:
        $id=  explode("-", $Par_Sql[2]);		
        if(($Par_Sql[2]*1)==2) $cheq="AND Che_Fec>'$Par_Sql[3]'"; 
            else{ if(empty($Par_Sql[3])||empty($Par_Sql[4])) $cheq=''; else $cheq="AND Che_Fec BETWEEN '$Par_Sql[3]' AND '$Par_Sql[4]'";  }
            if(($Par_Sql[2]*1)==3)$cheq=$cheq." AND cheques_otros.Che_Est='C'";
            if(($Par_Sql[2]*1)==4)$cheq=$cheq." AND cheques_otros.Che_Est='A'";
            if(($Par_Sql[2]*1)==5)$cheq=$cheq." AND cheques_otros.Che_Est='I'";
            if(($Par_Sql[2]*1)==6)$cheq=$cheq." AND cheques_otros.Che_Est='P'";
            $sql="SELECT CONCAT('CHE-',CAST(Che_Cod as CHAR)) as Che_Cod,Pld_Des,Ban_Cue,Che_Num,Che_Obs,Che_Fec,ROUND(Che_Val,2) AS Che_Val,CONCAT(Prs_Ape,' ',Prs_Nom) AS Beneficiario,IF(Che_Est='A','No Cobrado',IF(Che_Est='C','Cobrado',IF(Che_Est='I','Anulado','Protestado'))) AS estado,Che_Cob,'EXT' AS t_type  FROM cheques_otros
                INNER JOIN banco ON banco.Ban_Cod=cheques_otros.Ban_Cod
                INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                INNER JOIN proveedore ON proveedore.Prv_Cod=cheques_otros.Prv_Cod
                INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod                
                WHERE Emp_Cod=$Par_Sql[0] AND cheques_otros.Ban_Cod=$Par_Sql[1] $cheq
                ORDER BY Che_Fec";
        //echo $sql;
        return $sql;
	case 381:
        $fecha=  explode('-',$Par_Sql[1]);
        $sql="SELECT asientos.Asi_Cod,Pld_Cdc, asientos.Asi_Deh, sum(asientos.Asi_Val) as Total,det_plan.Pld_Des,Ban_Cod,Ban_Cue FROM asientos  
            INNER JOIN det_plan ON asientos.Pld_Cod = det_plan.Pld_Cod
            INNER JOIN banco ON banco.Pld_Cod = det_plan.Pld_Cod
            LEFT JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod
            WHERE  
                comprobantes.Com_Est = 'A'				  
                AND banco.Ban_Cod = '$Par_Sql[0]'  
                 AND comprobantes.Com_Fec BETWEEN '$fecha[0]-01-01 00:00:00' AND '$Par_Sql[1] 00:00:00'
                GROUP BY asientos.Asi_Deh ORDER BY asientos.Asi_Deh ASC";    
        //echo $sql;
        return $sql;
    case 382:
        $sql="SELECT CONCAT_WS('-',asientos.Asi_Cod,Che_Cod) as id,Com_Fec,cheques.Asi_Cod,Che_Cod,CONCAT(' CH ',Che_Num) as Che_Num,Che_Val,Asi_Glo,Che_Fec,CONCAT_WS(' ',Prs_Ape,Prs_Nom) as proveedor,CONCAT(' ',Tia_Abr,' - ',CAST( cheques.Asi_Cod AS CHAR)) as asiento FROM cheques
            INNER JOIN proveedore ON cheques.Prv_Cod= proveedore.Prv_Cod
            INNER JOIN persona ON proveedore.Prs_Cod= persona.Prs_Cod
            INNER JOIN asientos ON cheques.Asi_Cod= asientos.Asi_Cod
            INNER JOIN comprobantes ON asientos.Com_Cod= comprobantes.Com_Cod
            INNER JOIN tipo_asien ON comprobantes.Tia_Cod= tipo_asien.Tia_Cod
            WHERE (Che_Est='A' OR Che_Est='C')  AND Com_Est='A' AND cheques.Ban_Cod=$Par_Sql[0]
                AND Com_Fec <= '$Par_Sql[2] 23:59:59'
                AND (cheques.Che_Cob >= '$Par_Sql[2] 23:59:59' OR cheques.Che_Cob IS NULL)
            ORDER BY Che_Fec"; 
        //echo $sql;
        return $sql;
    case 383:
        $sql="SELECT CONCAT_WS('-',Asi_Cod,Che_Cod) as id,Com_Fec,Asi_Cod,Che_Cod,CONCAT(' CH ',Che_Num) as Che_Num,Che_Val,Che_Obs AS Asi_Glo,Che_Fec,CONCAT_WS(' ',Prs_Ape,Prs_Nom) as proveedor,CONCAT(' ',Asi_Cod) as asiento FROM cheques_otros
            INNER JOIN proveedore ON cheques_otros.Prv_Cod= proveedore.Prv_Cod
            INNER JOIN persona ON proveedore.Prs_Cod= persona.Prs_Cod                                    
            WHERE (Che_Est='A' OR Che_Est='C')  AND Ban_Cod=$Par_Sql[3] AND Emp_Cod='$Par_Sql[0]'
             AND Com_Fec <= '$Par_Sql[2] 23:59:59'
                AND (Che_Cob >= '$Par_Sql[2] 23:59:59' OR Che_Cob IS NULL)
            ORDER BY Com_Fec";   
        //echo $sql;
        return $sql;
    case 384:
            $sql="SELECT DISTINCT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef FROM perio_cont               
                INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod                
                WHERE plan_cuenta.Emp_Cod=$Par_Sql[0] ORDER BY Periodo DESC;";
            //echo $sql;
        return $sql;
		
	/*actualizamos el COM_GEN de la tabla comprobante, se ejecuta cuando se genera un cheque*/
	case 385:
            $sql="UPDATE comprobantes SET Com_Gen='A' WHERE Com_Cod=$Par_Sql[0]";
            //echo $sql;
        return $sql;
	 case 386:
            $sql="SELECT * FROM banco
                INNER JOIN det_plan ON det_plan.Pld_Cod=banco.Pld_Cod
                INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                WHERE Pec_Cod=$Par_Sql[0] AND Ban_Cue!='' AND Ban_Cue!='0' ";
            //echo $sql;
            return $sql;  
        case 387:
            $ins_cheques_191="UPDATE cheques SET Che_Num = NULL, Che_Obs='$Par_Sql[Che_Obs]-Cheque $Par_Sql[Che_Num] Liberado' WHERE Prv_Cod=$Par_Sql[Prv_Cod] AND Ban_Cod=$Par_Sql[Ban_Cod] AND Asi_Cod=$Par_Sql[Asi_Cod] AND Che_Cod = $Par_Sql[Che_Cod]";
            //echo $ins_cheques_191;
            return $ins_cheques_191;
        case 388:
            $numero_cheque="";
            if($Par_Sql[5]){
                $numero_cheque=" AND (CAST(cheques.Che_Num as CHAR) LIKE '%$Par_Sql[5]%')";
            }
            if(($Par_Sql[2]*1)==2) $cheq="AND Che_Fec>'$Par_Sql[3]' ";
            else{ if(empty($Par_Sql[3])||empty($Par_Sql[4])) $cheq=''; else $cheq="AND Che_Fec BETWEEN '$Par_Sql[3]' AND '$Par_Sql[4]'";  }
            if(($Par_Sql[2]*1)==3)$cheq=$cheq." AND cheques.Che_Est='C'";
            if(($Par_Sql[2]*1)==4)$cheq=$cheq." AND cheques.Che_Est='A'";
            if(($Par_Sql[2]*1)==5)$cheq=$cheq." AND cheques.Che_Est='I'";
            if(($Par_Sql[2]*1)==6)$cheq=$cheq." AND cheques.Che_Est='P'";
            $ins_asie="SELECT CONCAT(CAST(cheques.Ban_Cod AS CHAR),'_',CAST(Che_Num as CHAR),'_',CAST(Che_Cod as CHAR)) as Che_Cod,cheques.Asi_Cod,cheques.Ban_Cod,cheques.Che_Cod,cheques.Prv_Cod,Com_Fec,Pld_Des,Ban_Cue,Che_Est,Che_Num,Che_Obs,Che_Fec,ROUND(Che_Val,2) AS Che_Val,CONCAT(Prs_Ape,' ',Prs_Nom) AS Beneficiario,IF(Che_Est='A','No Cobrado',IF(Che_Est='C','Cobrado',IF(Che_Est='I','Anulado','Protestado'))) AS estado,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Num,Che_Cob  FROM cheques
                            INNER JOIN banco ON banco.Ban_Cod=cheques.Ban_Cod
                            INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                            INNER JOIN proveedore ON proveedore.Prv_Cod=cheques.Prv_Cod
                            INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                            INNER JOIN asientos ON asientos.Asi_Cod=cheques.Asi_Cod
                            INNER JOIN comprobantes ON comprobantes.Com_Cod=asientos.Com_Cod
                            INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                            WHERE Emp_Cod=$Par_Sql[0] AND cheques.Ban_Cod=$Par_Sql[1] $cheq
                            $numero_cheque
                            ORDER BY Che_Fec";
            //echo $ins_asie."<br>";
            return $ins_asie;
		
		case 389://modificar Cheque
			$mod_cheque="UPDATE cheques SET Prv_Cod=$Par_Sql[Prv_Cod] , Ban_Cod=$Par_Sql[Ban_Cod] , Asi_Cod=$Par_Sql[Asi_Cod] , Che_Num=$Par_Sql[Che_Num], Che_Fec='$Par_Sql[Che_Fec]'  , Che_Val=$Par_Sql[Che_Val], Che_Obs='$Par_Sql[Che_Obs]'  WHERE Prv_Cod=$Par_Sql[Prv_Cod_Ant] AND Ban_Cod=$Par_Sql[Ban_Cod_Ant] AND Asi_Cod=$Par_Sql[Asi_Cod] AND Che_Cod = $Par_Sql[Che_Cod]";
			//echo $mod_cheque;
			return $mod_cheque;
		case 390://verificar existencia de N cheque
					$sql="SELECT Che_Num from cheques where Ban_Cod=$Par_Sql[Ban_Cod] AND Che_Num=$Par_Sql[Che_Num]";
					//echo $sql;
					return $sql;
		case 391://obtener Bancos para edicion Cheques
			   $sql="SELECT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef,Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue FROM banco
						INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
					INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
					INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
					WHERE Ban_Est='A' AND plan_cuenta.Emp_Cod=$Par_Sql[0] AND Ban_Cue!='0' AND Ban_Cue!='' AND Ban_Tip='B' GROUP BY Ban_Cod;";
					//echo $sql;
					return $sql;
		case 392://actualizar asiento en edicion de cheque
					$sql="UPDATE asientos set Pld_Cod=$Par_Sql[Pld_Cod] WHERE Asi_Cod=$Par_Sql[Asi_Cod]";
					//echo $sql;
					return $sql;
		case 393://verificacion de Asiento contable en CCXPP
					$sql="SELECT cheques.Che_Cod,cheques.Prv_Cod,cheques.Ban_Cod,cheques.Asi_Cod
								FROM cheques
							INNER JOIN asientos ON asientos.Asi_Cod = cheques.Asi_Cod
							  INNER JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod
							  INNER JOIN det_ccpp_p ON comprobantes.Com_Cod = det_ccpp_p.Com_Cod
								WHERE cheques.Asi_Cod =$Par_Sql[Asi_Cod]";
					//echo $sql;
					return $sql;
		case 394://Actualiza valor de asiento con el de cheque
				$sql="UPDATE asientos set Asi_Val=$Par_Sql[Che_Val] WHERE Asi_Cod=$Par_Sql[Asi_Cod]";
				//echo $sql;
				return $sql;
		case 395://Actualizar valor de comprobantes con de Cheque
		$sql="UPDATE comprobantes set Com_Val=$Par_Sql[Che_Val] WHERE Com_Cod=$Par_Sql[Com_Cod]";
			//echo $sql;
			return $sql;
		case 396:
		$numero_cheque="";
		if($Par_Sql[5]){
				$numero_cheque=" AND (CAST(cheques.Che_Num as CHAR) LIKE '%$Par_Sql[5]%')";
		}
		if(($Par_Sql[2]*1)==2) $cheq="AND Che_Fec>'$Par_Sql[3]' ";
		else{ if(empty($Par_Sql[3])||empty($Par_Sql[4])) $cheq=''; else $cheq="AND Che_Fec BETWEEN '$Par_Sql[3]' AND '$Par_Sql[4]'";  }
		if(($Par_Sql[2]*1)==3)$cheq=$cheq." AND cheques.Che_Est='C'";
		if(($Par_Sql[2]*1)==4)$cheq=$cheq." AND cheques.Che_Est='A'";
		if(($Par_Sql[2]*1)==5)$cheq=$cheq." AND cheques.Che_Est='I'";
		if(($Par_Sql[2]*1)==6)$cheq=$cheq." AND cheques.Che_Est='P'";
		$ins_asie="SELECT CONCAT(CAST(cheques.Ban_Cod AS CHAR),'_',CAST(Che_Num as CHAR),'_',CAST(Che_Cod as CHAR)) as Che_Cod,cheques.Asi_Cod,cheques.Ban_Cod,cheques.Che_Cod,cheques.Prv_Cod,comprobantes.Com_Cod,comprobantes.Com_Cod as codigo_Comp,(Select count(Cpp_Cod) as  editVal from det_ccpp_p where det_ccpp_p.Com_Cod= codigo_Comp )as EdicionVal, Com_Fec,Pld_Des,Ban_Cue,Che_Est,Che_Num,Che_Obs,Che_Fec,ROUND(Che_Val,2) AS Che_Val,CONCAT(Prs_Ape,' ',Prs_Nom) AS Beneficiario,IF(Che_Est='A','No Cobrado',IF(Che_Est='C','Cobrado',IF(Che_Est='I','Anulado','Protestado'))) AS estado,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Num,Che_Cob  FROM cheques
										INNER JOIN banco ON banco.Ban_Cod=cheques.Ban_Cod
										INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
										INNER JOIN proveedore ON proveedore.Prv_Cod=cheques.Prv_Cod
										INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
										INNER JOIN asientos ON asientos.Asi_Cod=cheques.Asi_Cod
										INNER JOIN comprobantes ON comprobantes.Com_Cod=asientos.Com_Cod
										INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
										WHERE Emp_Cod=$Par_Sql[0] AND cheques.Ban_Cod=$Par_Sql[1] $cheq
										$numero_cheque
										ORDER BY Che_Fec";
		//echo $ins_asie."<br>";
		return $ins_asie;

		/**
		 * Verifica si el asiento del cheque pertenece a un anticipo a proveedores
		 * activo (A), usado (U) o consumido (C). En esos casos no se puede anular el cheque.
		 */
		case 397:
		$sql="SELECT ant.Atp_Cod, ant.Atp_Est, ant.Com_Cod, ant.Atp_Fec, ant.Atp_Val,
				CONCAT(tas.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(com.Com_Fec))=1,CONCAT('0',CAST(MONTH(com.Com_Fec) AS CHAR)),CAST(MONTH(com.Com_Fec) AS CHAR)),'-',CAST(com.Com_Num AS CHAR)) AS codigo_compro
			FROM pago_anticipo_proveedores AS pga
			INNER JOIN anticipos_proveedores AS ant ON ant.Atp_Cod = pga.Atp_Cod
			INNER JOIN comprobantes AS com ON com.Com_Cod = ant.Com_Cod
			INNER JOIN tipo_asien AS tas ON tas.Tia_Cod = com.Tia_Cod
			WHERE pga.Asi_Cod = $Par_Sql[0]
				AND ant.Atp_Est IN ('A','U','C')
			LIMIT 1";
		return $sql;
	
    }
}