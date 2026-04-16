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
function sentencias_con($id, $Par_Sql)
{
	switch ($id) {
		case 3:
			/* Consulta la provicia y pais de la ciudad de la sucursal */
			$provincia = "SELECT 
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
			$cargar_ciudad = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			//echo $cargar_ciudad;
			return $cargar_ciudad;
			break;

		case 146:
			/* 
		* Consulta el comprobante por el apellidos del cliente o proveedor 
		*/
			$my_cargar_comprin_146 = "SELECT Com_Cod, Com_Num, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Ced, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est, Com_Gen FROM comprobantes, $Par_Sql[0], persona WHERE Prs_Ape like '%$Par_Sql[1]%' AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom";
			//echo $my_cargar_comprin_146;
			return $my_cargar_comprin_146;
			break;

		case 147:
			/* 
		* Consulta el comprobante por el codigo interno 
		*/
			$cargar_comprin = "SELECT Com_Cod, Com_Num, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Ced, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est FROM comprobantes, $Par_Sql[0], persona WHERE Com_Cod='$Par_Sql[1]' AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]'";
			return $cargar_comprin;
			break;

		/** 
		 * Cargado de la cabecera del comprobante por Apellido del cliente o proveedor 
		 */
		case 148:
			$cargar_cabape_148 = "SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est, Com_Gen FROM comprobantes, $Par_Sql[0], persona WHERE Prs_Ape like '%$Par_Sql[1]%' AND Tia_Cod ='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom"; //AND comprobantes.Com_Est='A'
			//echo $cargar_cabape_148;
			return $cargar_cabape_148;
			break;

		/* 
	* Cargado de la cabecera del comprobante por codigo 
	*/
		case 149:
			$cargar_cabcod_149 = "SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est FROM comprobantes, $Par_Sql[0], persona WHERE Com_Cod =$Par_Sql[1] AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A'";
			//echo $cargar_cabcod_149;
			return $cargar_cabcod_149;
			break;

		/* 
	* Actualizaci�n de los asientos del comprobante a modificar 
	*/
		case 151:
			$upd_ascompr = "UPDATE asientos SET Asi_Val=$Par_Sql[0], Asi_Con=UPPER('$Par_Sql[1]'), Asi_Glo=UPPER('$Par_Sql[2]'), Pld_Cod=$Par_Sql[3]  WHERE Asi_Cod = $Par_Sql[4]";
			return $upd_ascompr;
			break;

		case 152:
			/*
		* Selecionar el numero maximo de comprobante mensual seg�n el tipo
		*/
			$num_com_152 = "SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod=$Par_Sql[0] AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
			//echo $num_com_152;
			return $num_com_152;
			break;

		case 153:
			/*
		* Selecionar el numero maximo de comprobante mensual segun el tipo I=ingreso, E=egreso, D=diario
		*/
			$sql = "SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes, tipo_asien WHERE comprobantes.Tia_Cod=tipo_asien.Tia_Cod AND 
tipo_asien.Tia_Ini='$Par_Sql[0]' AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
			return $sql;
			break;

		/** 
		 * Cargado de la cabecera del comprobante por Apellido del cliente o proveedor 
		 */
		case 154:
			$sql = "SELECT Com_Cod, Com_Num, Com_Sys, Pec_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Com_Con,comprobantes.Cli_Cod, Com_Est, Com_Gen, Tia_Des, Tia_Ini, Tia_Abr FROM comprobantes, $Par_Sql[0], persona, tipo_asien WHERE Prs_Ape like '%$Par_Sql[1]%' AND Tia_Ini ='$Par_Sql[2]' AND comprobantes.Tia_Cod = tipo_asien.Tia_Cod AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND (comprobantes.Com_Est='A' OR comprobantes.Com_Est='I') $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom";
			//echo $sql;
			return $sql;
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
			$sql = "SELECT caja_aper.Caj_Cod,
  caja_aper.Caj_Fec
FROM
  caja_aper
  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
WHERE
  Caj_Est = 'C' AND 
  Caj_Gen = 'S' AND `sucursal`.Emp_Cod = $Par_Sql[2] AND YEAR(Caj_Fec) = $Par_Sql[0] AND MONTH(Caj_Fec) = $Par_Sql[3] AND
  Caj_Cod NOT IN (SELECT caja_compr.Caj_Cod FROM caja_compr INNER JOIN comprobantes ON (caja_compr.Com_Cod = comprobantes.Com_Cod) WHERE comprobantes.Com_Est = 'A' AND comprobantes.`Pec_Cod`=$Par_Sql[1])						
      
						
  "; // poner al final:: AND Pun_Cod = $Par_Sql[1]
			/* $sql = "SELECT caja_aper.Caj_Cod,
  caja_aper.Caj_Fec
FROM
  caja_aper
  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
WHERE
  Caj_Est = 'C' AND 
  Caj_Gen = 'S' AND `sucursal`.Emp_Cod = $Par_Sql[2] AND YEAR(Caj_Fec) = $Par_Sql[0] AND MONTH(Caj_Fec) = $Par_Sql[3]						
						
  ";*/
			//echo $sql;
			return $sql;
			break;

		case 182:
			/* 
		* Consulta de las cajas que estan listas para generar y que NO han sido generadas - MES 
		*/
			$sql = "SELECT DISTINCT 
  MONTH(caja_aper.Caj_Fec) AS mes
FROM
  caja_aper
  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
WHERE
  Caj_Est = 'C' AND 
  Caj_Gen = 'S' AND `sucursal`.Emp_Cod = $Par_Sql[2] AND YEAR(Caj_Fec) = $Par_Sql[0] AND
  Caj_Cod NOT IN (SELECT caja_compr.Caj_Cod FROM caja_compr INNER JOIN comprobantes ON (caja_compr.Com_Cod = comprobantes.Com_Cod) WHERE comprobantes.Com_Est = 'A' AND comprobantes.`Pec_Cod`=$Par_Sql[1]) order by mes ASC"; // poner al final:: AND Pun_Cod = $Par_Sql[1]
			/*  $sql = "SELECT DISTINCT 
  MONTH(caja_aper.Caj_Fec) AS mes
FROM
  caja_aper
  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
WHERE
  Caj_Est = 'C' AND 
  Caj_Gen = 'S' AND `sucursal`.Emp_Cod = $Par_Sql[2] AND YEAR(Caj_Fec) = $Par_Sql[0]";*/
			return $sql;
			break;

		case 183:
			/* 
		* Consulta el total de RENTA en ventas
		*/
			$sql = "SELECT ventas_det.Ren_Cod, (sum(ROUND(ventas_det.Vet_Imp,2)) * Ren_Por)/100 as Renta, Ren_Por,Ren_Ret,Ren_Sri
FROM ventas_det
INNER JOIN ventas ON ventas_det.Vet_Cod = ventas.Vet_Cod 
INNER JOIN caja_aper ON ventas.Caj_Cod = caja_aper.Caj_Cod 
INNER JOIN producto ON ventas_det.Pro_Cod = producto.Pro_Cod  
INNER JOIN item ON  producto.Ite_Cod = item.Ite_Cod 
INNER JOIN iva ON ventas_det.Iva_Cod = iva.Iva_Cod 
INNER JOIN renta_iva ON ventas_det.Ren_Cod = renta_iva.Ren_Cod 
WHERE ventas.Vet_Est = 'A' $Par_Sql[0] AND  ventas.Vet_Cod NOT IN (SELECT ventas_compr.Vet_Cod FROM ventas_compr)  "
				. "GROUP BY /*ventas.Vet_Cod,*/Ren_Por, Ren_Sri";
			// echo $sql;
			return $sql;
			break;

		case 184:
			/* 
		* Consulta la retención de una factura
		*/
			$sql = "SELECT (sum(ROUND(ventas_det.Vet_Imp,2) * Ren_Por))/100 as Renta 
FROM ventas, caja_aper, ventas_det, producto, item, 
iva, renta_iva
WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND ventas.Vet_Cod = 
ventas_det.Vet_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND ventas_det.Ren_Cod = renta_iva.Ren_Cod 
AND  ventas.Vet_Cod NOT IN (SELECT ventas_compr.Vet_Cod FROM ventas_compr)    
 AND ventas.Vet_Cod = $Par_Sql[0] AND ventas.Vet_Est = 'A' GROUP BY ventas.Vet_Cod";
			//echo $sql;
			return $sql;
			break;

		case 185:
			/** 
			 * Consulta los meses de las facturas de compras
			 */
			$sql = "SELECT DISTINCT 
  MONTH(compras.Cop_Fec) AS mes
FROM
  compras
WHERE compras.Cop_Est = 'A'  AND compras.Pec_Cod = $Par_Sql[0] 
AND compras.Cop_Cod
 NOT IN 
(SELECT compr_auto.Cop_Cod FROM compr_auto INNER JOIN comprobantes ON 
(compr_auto.Com_Cod = comprobantes.Com_Cod) WHERE comprobantes.Com_Est = 'A' AND 
comprobantes.Pec_Cod=$Par_Sql[0]) ORDER BY mes ASC";
			//echo $sql;
			return $sql;
			break;

		case 186:
			/** 
			 * Consulta los dÃ­as de las facturas de compras
			 */
			$sql = "SELECT  
                 compras.Cop_Num, compras.Cop_Fec, compras.Cop_Cod, Tic_Sri
                FROM
                compras
                INNER JOIN tipo_compr ON  tipo_compr.Tic_Cod=compras.Tic_Cod
                WHERE
                  compras.Cop_Est = 'A' AND 
                  compras.Pec_Cod = $Par_Sql[0] AND  MONTH(compras.Cop_Fec) = $Par_Sql[1] AND
                  compras.Cop_Cod NOT IN (SELECT compr_auto.Cop_Cod FROM compr_auto INNER JOIN comprobantes ON (compr_auto.Com_Cod = comprobantes.Com_Cod) WHERE comprobantes.Com_Est = 'A' AND comprobantes.Pec_Cod = $Par_Sql[0]) ORDER BY Tic_Sri DESC,Cop_Fec";
			return $sql;

		case 187:
			/* 
		* Consulta el proveedor reservado para la contabilización
		*/
			$sql = "SELECT compra_prov.Prv_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom FROM compra_prov, proveedore, persona WHERE compra_prov.Prv_Cod = proveedore.Prv_Cod AND persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[0]";
			return $sql;
			break;

		case 188:
			/* 
		* Consulta los valores del producto del Debe
		*/
			$sql = "SELECT 
  (det_compra.Cop_Imp - (det_compra.Cop_Imp * det_compra.Cop_Dec / 100)) AS Importe, compras.Cop_Cod, det_compra.Pro_Cod, (((det_compra.Cop_Imp - (det_compra.Cop_Imp * det_compra.Cop_Dec / 100)) * Iva_Por) / 100) AS Iva, iva.Iva_Por, Cop_Num
FROM
  compras
  INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
  INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
WHERE
  compras.Cop_Est = 'A'  
  $Par_Sql[0]";
			return $sql;
			break;

		/** 
		 * Consulta el codigo del iva pagado 
		 */
		case 189:
			$sql = "SELECT iva_pagado.Pld_Cod, det_plan.Pld_Des, det_plan.Pld_Cdc FROM det_plan INNER JOIN iva_pagado ON (det_plan.Pld_Cod = iva_pagado.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
			return $sql;
			break;

		/** 
		 * Consulta el codigo del iva pagado 
		 */
		case 200:
			$sql = "SELECT 
  SUM((det_retenc.Ret_Bas * renta_iva.Ren_Por) / 100) AS Renta, Ren_Por, Ren_Sri,renta_iva.Ren_Cod
FROM
  retencion
  INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
  INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod) WHERE
  retencion.Ret_Est = 'A'  $Par_Sql[0] GROUP BY compras.Cop_Cod,Ren_Por, Ren_Sri";
			return $sql;
			break;

		/**
		 * Grabada del reporte diario de caja 
		 */
		case 201:
			$sql = "INSERT INTO compr_auto (Cop_Cod, Com_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
			//echo "201  ".$sql."<br>";
			return $sql;
			break;

		/**
		 * Consulta los bancos para ventas según el plan de cuentas 
		 */
		case 202:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des FROM det_plan
			  INNER JOIN banco ON (det_plan.Pld_Cod = banco.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
			return $sql;
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

		case 211:
			/* 
		* 
		Consulta de los tipos de asientos  filtrados por el sub-tipo
		*/
			$sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien WHERE Tia_Ini = '$Par_Sql[0]'";
			return $sql;
			break;

		/* 
		* Consulta todos los periodos activos 
		*/
		case 214:
			$sql = "SELECT 
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
  Pec_Est = 'A' AND plan_cuenta.Emp_Cod = '$Par_Sql[0]'
ORDER BY
  Pec_Fei DESC";
			//echo $sql;
			return $sql;
			break;


		/* B�squeda de un cliente por apellido */
		case 317:
			$bus_clia = "SELECT Cli_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Cli_Cup, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est FROM cliente, persona WHERE Prs_Ape LIKE '%$Par_Sql[0]%' AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1]";
			//echo $bus_clia;
			return $bus_clia;
			break;

		/* B�squeda de un cliente por C�dula */
		case 318:
			$bus_clic = "SELECT Cli_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Cli_Cup, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est FROM cliente, persona WHERE Prs_Ced = '$Par_Sql[0]' AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1]"; //1=contabilidad
			return $bus_clic;	/* Cargado de la cuenta por medio de su codigo de cuenta ------- Revisar el valor del codigo de empresa, la sesion no muestra el dato */

			/* 
		* Cargado de la cuenta por medio de su codigo de cuenta ------- Revisar el valor del codigo de empresa, la sesion no muestra el dato 
		*/
		case 319:
			$cargar_cuenta_319 = "SELECT Pld_Cod,Pld_Des FROM det_plan, plan_cuenta WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND det_plan.Pld_Cdc='$Par_Sql[0]' AND Emp_Cod=$Par_Sql[1] AND Pla_Est='A' AND Pld_Est='A' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D'";
			//echo $cargar_cuenta_319;
			return $cargar_cuenta_319;
			break;

		/* 
		*B�squeda de un proveedor por apellido 
		*/
		case 320:
			$bus_proa = "SELECT Prv_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est FROM proveedore, persona WHERE Prs_Ape LIKE '%$Par_Sql[0]%' AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1]";
			//echo "<br>".$bus_proa;
			return $bus_proa;
			break;

		/* 
		* B�squeda de un proveedor por C�dula 
		*/
		case 321:
			$bus_proc = "SELECT Prv_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est FROM proveedore, persona WHERE Prs_Ced = '$Par_Sql[0]' AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1]";
			return $bus_proc;
			break;

		/** 
		 * Insercion de un comprobante de Ingreso/Egreso (Cliente/Proveedor) 
		 */
		case 324:
			$ins_compi = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[9]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'), Com_Tipo='$Par_Sql[8]',Usu_Cod='$_SESSION[Ses_Usu_Cod]'"; //Antes Com_Tip
			//echo "<br>".$ins_compi."<br>";
			return $ins_compi;
			break;


		/* 
	* Inserci�n de cada asiento del comprobante 
	*/
		case 325:
			$ins_asie = "INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5]";
			//echo "<br>325".$ins_asie;
			return $ins_asie;
			break;

		/* 
	* Cargado de la cabecera del comprobante, sea este de cualquier tipo 
	*/
		case 326:
			$cargar_cabcomp = "SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est, Com_Gen FROM comprobantes, $Par_Sql[0], persona WHERE Com_Num='$Par_Sql[1]' AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom"; //AND comprobantes.Com_Est='A'
			return $cargar_cabcomp;
			break;

		/* 
	* Cargado de las cuentas a modificar 
	*/
		case 327:
			$cargar_cuentas = "SELECT asientos.Asi_Cod, asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, Asi_Deh, ROUND(Asi_Val,2) as Asi_Val FROM asientos, det_plan WHERE asientos.Com_Cod=$Par_Sql[0] AND asientos.Pld_Cod=det_plan.Pld_Cod
	ORDER BY Asi_Deh, Asi_Cod ASC";
			//echo $cargar_cuentas;
			return $cargar_cuentas;
			break;

		/** 
		 * Actualizacion de la cabecera del comprobante 
		 */
		case 328:
			$act_cabcompr = "UPDATE comprobantes SET Com_Num='$Par_Sql[0]', Com_Con=UPPER('$Par_Sql[1]'), Com_Val=$Par_Sql[2], Com_Obs=UPPER('$Par_Sql[3]'), Com_Fec='$Par_Sql[5]' WHERE Com_Cod=$Par_Sql[4]";
			return $act_cabcompr;
			break;

		/* 
	* Borrado de los asientos del comprobante a modificar 
	*/
		case 329:
			$bor_ascompr = "DELETE FROM asientos WHERE Asi_Cod=$Par_Sql[0]";
			//echo $bor_ascompr;
			return $bor_ascompr;
			break;

		/* 
	* Baja de comprobantes 
	*/
		case 330:
			$baj_ccompr = "UPDATE comprobantes SET Com_Est='I' WHERE Com_Cod=$Par_Sql[0]";
			return $baj_ccompr;
			break;

		/* 
	* Cargado de la b�squeda de cuentas en la p�gina de registro de comprobantes
	*/
		case 331:
			$bus_xmld_331 = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta, empresas WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' AND det_plan.Pld_Est='A' AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D' ORDER BY Pld_Cod";
			//echo $bus_xmld_331;
			return $bus_xmld_331;
			break;

		/* Busqueda de cuentas por codigo */
		case 332:
			$bus_xmlc_332 = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta, empresas WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' AND det_plan.Pld_Est='A' AND det_plan.Pld_Cdc = '$Par_Sql[0]' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D'";
			//echo $bus_xmlc_332;
			return $bus_xmlc_332;
			break;

		/*
	* CONSULTA DE COMPROBANTES 
	*/
		case 333:
			$cargar_comprin_333 = "SELECT Com_Cod,Usu_Cod,Com_Num, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Ced, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val,Com_Est, Tia_Ini, Tia_Abr, Tia_Des FROM comprobantes, $Par_Sql[0], persona, tipo_asien WHERE Com_Cod='$Par_Sql[1]' AND comprobantes.Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Tia_Cod = tipo_asien.Tia_Cod $Par_Sql[5]"; //Com_Tip
			//echo $cargar_comprin_333;
			return $cargar_comprin_333;
			break;

		/* 
	* Carga de los cheques de un comprobante determinado 
	*/
		case 334:
			$car_cheques = "SELECT det_plan.Pld_Des, IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',Prs_Ape,Che_Ben) AS Prs_Ape, IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',Prs_Nom,'') AS Prs_Nom, Che_Ben, banco.Ban_Cod, banco.Ban_Cue, Che_Num, Che_Val,Che_Cob, Che_Obs, Che_Cod, cheques.Ban_Cod 
        FROM
           persona
           INNER JOIN proveedore ON (persona.Prs_Cod = proveedore.Prs_Cod)
           INNER JOIN cheques ON (proveedore.Prv_Cod = cheques.Prv_Cod)
           INNER JOIN banco ON (banco.Ban_Cod = cheques.Ban_Cod)
           INNER JOIN asientos ON (asientos.Asi_Cod = cheques.Asi_Cod)
           INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod) 
        WHERE 
        asientos.Com_Cod=$Par_Sql[0] AND (cheques.Che_Est='C' OR cheques.Che_Est='A' OR cheques.Che_Est='I' OR cheques.Che_Est='P')"; //Ojo antes estaba group by banco.Ban_Cue
			//echo $car_cheques;
			return $car_cheques;
			break;

		/* 
	* Cargar de comprobantes (I-E-A) entre fechas 
	*/
		case 335:
			$car_comfec_335 = "SELECT Com_Cod, Com_Num, Com_Fec, CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) as Nombre, persona.Prs_Ape, persona.Prs_Nom, Com_Con, ROUND(Com_Val,2) as Com_Val, Com_Gen, Tia_Des, Tia_Ini, Tia_Abr  FROM comprobantes, $Par_Sql[0], persona, tipo_asien WHERE comprobantes.Tia_Cod = tipo_asien.Tia_Cod AND comprobantes.Tia_Cod='$Par_Sql[1]' AND comprobantes.$Par_Sql[2]=$Par_Sql[0].$Par_Sql[2] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND Com_Est='$Par_Sql[5]' AND (Com_Fec BETWEEN '$Par_Sql[3]' AND '$Par_Sql[4]') $Par_Sql[6] AND $Par_Sql[0].Emp_Cod = $Par_Sql[7] ORDER BY Com_Fec";
			//echo $car_comfec_335;
			return $car_comfec_335;
			break;

		/* 
	* Cargado de las cuentas en base al DEBE y el HABER 
	*/
		case 336:
			$cargar_cuentas_336 = "SELECT asientos.Asi_Cod, asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, Asi_Deh, Asi_Val, Pld_Rec FROM asientos, det_plan WHERE asientos.Com_Cod=$Par_Sql[0] AND asientos.Pld_Cod=det_plan.Pld_Cod
	AND Asi_Deh = '$Par_Sql[1]' $Par_Sql[3] $Par_Sql[2]";
			//echo $cargar_cuentas_336;
			return $cargar_cuentas_336;
			break;

		/* 
	* Cargado del detalle de los comprobantes 
	*/
		case 338:
			$cargar_cuentas_338 = "SELECT comprobantes.Com_Val, Com_Con, Com_Obs, asientos.Asi_Cod, asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, Asi_Deh, ROUND(Asi_Val,2) as Asi_Val, Asi_Val AS Asi_Val2 FROM asientos, det_plan, comprobantes WHERE asientos.Com_Cod=$Par_Sql[0] AND asientos.Pld_Cod=det_plan.Pld_Cod AND comprobantes.Com_Cod = asientos.Com_Cod 
	"; //AND Asi_Deh = '$Par_Sql[1]' $Par_Sql[2]
			//echo $cargar_cuentas_338;
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
			$bus_clic_340 = "SELECT Cli_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Cli_Cup, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est FROM cliente, persona WHERE Cli_Cod = '$Par_Sql[0]' AND cliente.Prs_Cod=persona.Prs_Cod ";
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
				      ) as Descuento, ventas_det.Pro_Cod, promocione.Car_Int, periodos.Mod_Cod, CONCAT(Ite_Lar,' ',Pro_Obs) AS Ite_Lar, Car_Nom, Mod_Des
						FROM ventas, caja_aper, ventas_det, iva, notasgener, semestres, promocione, periodos, producto, item,
						carreras, modalidad
						 WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND 
						ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND
						ventas_det.Nge_Cod = notasgener.Nge_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod AND semestres.Pro_Cod
						= promocione.Pro_Cod AND semestres.Per_Int = periodos.Per_Int AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod
						= item.Ite_Cod AND promocione.Car_Int = carreras.Car_Int AND modalidad.Mod_Cod = periodos.Mod_Cod $Par_Sql[0] AND ventas.Vet_Est = 'A' 
                                                AND  ventas.Vet_Cod NOT IN (SELECT ventas_compr.Vet_Cod FROM ventas_compr)    
						GROUP BY iva.Iva_Cod, iva.Iva_Sri, Iva_Por, ventas_det.Pro_Cod, Car_Int, Mod_Cod
                        ORDER BY Car_Int DESC";
			//echo $fact_conta_341;
			return $fact_conta_341;
			break;

		/* 
	* Consulta las cuentas que tienen relaci�n con los respectivos rubros 
	*/
		case 342:
			$sql = "SELECT produ_plan.Pld_Cod, Pld_Cdc, Pld_Des FROM produ_plan, det_plan WHERE produ_plan.Pld_Cod = det_plan.Pld_Cod AND
							produ_plan.Pro_Cod = $Par_Sql[0] AND produ_plan.Car_Int = $Par_Sql[1] AND produ_plan.Mod_Cod = $Par_Sql[2] AND det_plan.Pla_Cod = $Par_Sql[3] AND (Tip_Pld='V' OR Tip_Pld='I')"; //
			return $sql;
			break;

		/* 
	* Consulta las cuentas contables referentes al DEBE - BANCOS 
	*/
		case 343:
			$cuentas_bancos_343 = "SELECT banco.Pld_Cod, Pld_Cdc, Pld_Des, ventas.Vet_Cod, pago_venta.Vet_Tot as Importe, pago_venta.Vet_Che, ventas.Vet_Num 
					FROM banco, ventas, caja_aper, det_plan, pago_venta WHERE banco.Ban_Cod = pago_venta.Ban_Cod AND caja_aper.Caj_Cod = ventas.Caj_Cod 
					AND banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND ventas.Vet_Cod = pago_venta.Vet_Cod  $Par_Sql[0]  
                                             AND  ventas.Vet_Cod NOT IN (SELECT ventas_compr.Vet_Cod FROM ventas_compr)    
					AND ventas.Vet_Est = 'A' ORDER BY banco.Ban_Cod "; //AND ventas.Caj_Cod
			//echo $cuentas_bancos_343;
			return $cuentas_bancos_343;
			break;

		/**
		 * Grabada del reporte diario de caja 
		 */
		case 344:
			$sql = "INSERT INTO caja_compr (Caj_Cod, Com_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
			//echo "<br>".$sql;
			return $sql;
			break;

		/* 
	* Consulta las cuentas contables referentes al DEBE - SIN BANCOS 
	*/
		case 345:
			$cuentas_bancos_345 = "SELECT ventas.Vet_Cod, pago_venta.Vet_Tot as Importe, pago_venta.Vet_Che, ventas.Vet_Num, caja_aper.Caj_Fec FROM ventas, caja_aper, pago_venta WHERE 
					caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Vet_Cod = pago_venta.Vet_Cod 
                                        AND  ventas.Vet_Cod NOT IN (SELECT ventas_compr.Vet_Cod FROM ventas_compr)
                                        AND ventas.Vet_Cod NOT IN (SELECT ventas.Vet_Cod 
					FROM ventas, caja_aper, pago_venta WHERE caja_aper.Caj_Cod = ventas.Caj_Cod
                                        AND ventas.Vet_Cod = pago_venta.Vet_Cod 
                                        AND pago_venta.Ban_Cod != '' $Par_Sql[0] AND ventas.Vet_Est = 'A') $Par_Sql[0] AND ventas.Vet_Est = 'A' "; //ventas.Caj_Cod =
			//echo $cuentas_bancos_345;
			return $cuentas_bancos_345;
			break;

		/* 
	* Consulta las cuentas contables referentes al DEBE - SIN BANCOS 
	*/
		case 346:
			$sql = "SELECT ventas_det.Pro_Cod, (sum(ventas_det.Vet_Imp)) as Importe, CONCAT(Ite_Lar,' ',Pro_Obs) AS Ite_Lar, ventas.Vet_Cod, SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva FROM ventas, caja_aper, ventas_det, producto, item, iva
						 WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod
						= item.Ite_Cod 
                                                AND  ventas.Vet_Cod NOT IN (SELECT ventas_compr.Vet_Cod FROM ventas_compr)    
                                                AND ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod   
                         $Par_Sql[0] AND ventas.Vet_Est = 'A' 
						GROUP BY  ventas_det.Pro_Cod, ventas_det.Vet_Cod"; //caja_aper.Caj_Cod = --- AND ventas_det.Nge_Cod NOT IN (SELECT notasgener.Nge_Cod FROM notasgener)
			return $sql;
			break;

		/* 
	* Consulta las cuentas contables referentes al HABER - SOLO RUBROS 
	*/
		case 347:
			$sql = "SELECT produ_plan.Pld_Cod, Pld_Cdc, Pld_Des FROM produ_plan, det_plan WHERE produ_plan.Pld_Cod 
								= det_plan.Pld_Cod AND produ_plan.Pro_Cod = $Par_Sql[0] AND det_plan.Pla_Cod = $Par_Sql[1] AND (Tip_Pld='C' OR Tip_Pld='I') GROUP BY produ_plan.Pld_Cod, Pld_Cdc, Pld_Des";
			return $sql;
			break;


		/* 
	* CONSULTA DE COMPROBANTES 
	*/
		case 348:
			$cargar_comprin_348 = "SELECT Com_Cod, Com_Num, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Ced, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val,Com_Est, Tia_Ini, Com_Gen FROM comprobantes, $Par_Sql[0], persona, tipo_asien WHERE Com_Num='$Par_Sql[1]' AND comprobantes.Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Tia_Cod = tipo_asien.Tia_Cod $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom"; //Com_Tip
			//echo $cargar_comprin_348;
			return $cargar_comprin_348;
			break;

		case 349:
			$sql = "SELECT produ_plan.Pld_Cod, Pld_Cdc, Pld_Des FROM produ_plan, det_plan WHERE produ_plan.Pld_Cod 
								= det_plan.Pld_Cod AND produ_plan.Pro_Cod = $Par_Sql[0] AND det_plan.Pla_Cod = $Par_Sql[1] AND (Tip_Pld='V' OR Tip_Pld='I') GROUP BY produ_plan.Pld_Cod, Pld_Cdc, Pld_Des";
			//echo $sql."<br>";    
			return $sql;
			break;

		/** 
		 * Consulta el codigo del iva cobrado 
		 */
		case 352:
			$iva_cobrado_352 = "SELECT iva_cobrad.Pld_Cod, det_plan.Pld_Des, det_plan.Pld_Cdc FROM det_plan INNER JOIN iva_cobrad ON (det_plan.Pld_Cod = iva_cobrad.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
			return $iva_cobrado_352;
			break;

		/** 
		 * Cargado de la cabecera del comprobante, sea este de cualquier tipo 
		 */
		case 353:
			$sql = "SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est, Com_Gen, Tia_Des, Tia_Ini,Tia_Abr,Com_Sys FROM comprobantes, $Par_Sql[0], persona, tipo_asien WHERE Com_Num='$Par_Sql[1]' AND Tia_Ini='$Par_Sql[2]' AND comprobantes.Tia_Cod = tipo_asien.Tia_Cod AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND (comprobantes.Com_Est='A' OR comprobantes.Com_Est='I') $Par_Sql[5]  ORDER BY Com_Fec, Prs_Ape, Prs_Nom"; //AND comprobantes.Com_Est='A'
			//echo $sql;
			return $sql;
			break;

		/** 
		 * Cargado de la cabecera del comprobante por codigo 
		 */
		case 354:
			$sql = "SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est, comprobantes.Tia_Cod, Tia_Des, Tia_Ini, Tia_Abr FROM comprobantes, $Par_Sql[0], persona, tipo_asien WHERE Com_Cod =$Par_Sql[1] AND comprobantes.Tia_Cod=tipo_asien.Tia_Cod AND Tia_Ini='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A'";
			//echo $sql;
			return $sql;
			break;

		/** 
		 * Actualizacion de la cabecera del comprobante 
		 */
		case 355:
			$sql = "UPDATE comprobantes SET Com_Num='$Par_Sql[0]', Com_Con=UPPER('$Par_Sql[1]'), Com_Val=$Par_Sql[2], Com_Obs=UPPER('$Par_Sql[3]'), Com_Fec='$Par_Sql[5]', Tia_Cod = $Par_Sql[6] WHERE Com_Cod=$Par_Sql[4]";
			return $sql;
			break;

		case 356:
			$sql = "UPDATE cheques,asientos SET Che_Est='I' WHERE cheques.Asi_Cod=asientos.Asi_Cod AND Com_Cod=$Par_Sql[0] ";
			return $sql;
		case 357:
			$sql = "SELECT (sum(ROUND(ventas_det.Vet_Imp*Iva_Por/100,2) * Ren_Por))/100 as Renta 
FROM ventas, caja_aper, ventas_det, producto, item, 
iva, renta_iva
WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND ventas.Vet_Cod = 
ventas_det.Vet_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND ventas_det.Ren_Iva = renta_iva.Ren_Cod 
AND  ventas.Vet_Cod NOT IN (SELECT ventas_compr.Vet_Cod FROM ventas_compr)    
AND ventas.Vet_Cod = $Par_Sql[0] AND ventas.Vet_Est = 'A' GROUP BY ventas.Vet_Cod";
			//echo $sql;
			return $sql;
		case 358:
			/* 
		* Consulta el total de RENTA en ventas
		*/
			$sql = "SELECT  ventas_det.Ren_Iva AS Ren_Cod, (sum(ROUND(ventas_det.Vet_Imp*Iva_Por/100,2) * Ren_Por))/100 as Renta, Ren_Por,Ren_Ret,Ren_Sri
FROM ventas_det
INNER JOIN ventas ON ventas_det.Vet_Cod = ventas.Vet_Cod 
INNER JOIN caja_aper ON ventas.Caj_Cod = caja_aper.Caj_Cod 
INNER JOIN producto ON ventas_det.Pro_Cod = producto.Pro_Cod  
INNER JOIN item ON  producto.Ite_Cod = item.Ite_Cod 
INNER JOIN iva ON ventas_det.Iva_Cod = iva.Iva_Cod 
INNER JOIN renta_iva ON ventas_det.Ren_Iva = renta_iva.Ren_Cod 
WHERE ventas.Vet_Est = 'A' $Par_Sql[0] AND  ventas.Vet_Cod NOT IN (SELECT ventas_compr.Vet_Cod FROM ventas_compr)  "
				. "GROUP BY /*ventas.Vet_Cod,*/Ren_Por, Ren_Sri";
			//echo $sql;
			return $sql;
			break;
		case 359:
			$sql = "SELECT det_plan.Pld_Cod,Pld_Cdc,Pld_Des FROM det_plan 
INNER JOIN reniva_pla ON reniva_pla.Pld_Cod=det_plan.Pld_Cod
WHERE reniva_pla.Ren_Cod='$Par_Sql[0]' AND Pla_Cod='$Par_Sql[1]' AND reniva_pla.Ren_Tip='$Par_Sql[2]';";
			//echo $sql;
			return $sql;

		case 360:
			$baj_ccompr = "UPDATE comprobantes SET Com_Est='A' WHERE Com_Cod=$Par_Sql[0]";
			return $baj_ccompr;

		case 361:
			$sql = "SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est, Com_Gen, Tia_Des, Tia_Ini FROM comprobantes, $Par_Sql[0], persona, tipo_asien WHERE Prs_Ape like '%$Par_Sql[1]%' AND Tia_Ini ='$Par_Sql[2]' AND comprobantes.Tia_Cod = tipo_asien.Tia_Cod AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND  Com_Est='I' AND comprobantes.Pec_Cod='$Par_Sql[3]' $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom"; //AND comprobantes.Com_Est='A'
			//echo '361<br/>'.$sql;
			return $sql;
		case 362:
			$sql = "SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est, Com_Gen, Tia_Des, Tia_Ini FROM comprobantes, $Par_Sql[0], persona, tipo_asien WHERE Com_Num='$Par_Sql[1]' AND Tia_Ini='$Par_Sql[2]' AND comprobantes.Tia_Cod = tipo_asien.Tia_Cod AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND  Com_Est='I' AND comprobantes.Pec_Cod='$Par_Sql[3]' $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom"; //AND comprobantes.Com_Est='A'
			//echo '362<br/>'.$sql;
			return $sql;

		case 363:
			$sql = "SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est, comprobantes.Tia_Cod, Tia_Des, Tia_Ini FROM comprobantes, $Par_Sql[0], persona, tipo_asien WHERE Com_Cod =$Par_Sql[1] AND Tia_Ini='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' /*AND comprobantes.Com_Est='A'*/";
			return $sql;

		case 364:
			$sql = "UPDATE comprobantes SET $Par_Sql[0]='$Par_Sql[1]' WHERE Com_Cod='$Par_Sql[2]'";
			return $sql;

		/**
		 * Busqueda de una persona por usuario
		 */
		case 365:
			$sql = "SELECT usuarios.Usu_Cod, usuarios.Usu_Ced, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Est FROM persona, usuarios WHERE persona.Prs_Cod=usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;
		case 366:
			$sql = "SELECT * FROM comprobantes WHERE Com_Cod='$Par_Sql[0]' ";
			//echo $sql;
			return $sql;

		case 369:
			/* 
		* Consulta el total de RENTA en ventas
		*/
			$sql = "SELECT det_plan.Pla_Cod,comprobantes.Com_Cod,Com_Fec,Com_Con,Com_Obs,
                            CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Codigo,
                            SUM(IF(Asi_Deh='D',Asi_Val,0)) AS Debe,SUM(IF(Asi_Deh='H',Asi_Val,0)) AS Haber,SUM(IF(Asi_Deh='D',Asi_Val,0))-SUM(IF(Asi_Deh='H',Asi_Val,0)) AS Diferencia,
                            IF(Tia_Ini='D','Diario',IF(Tia_Ini='I','Ingreso','Egreso')) AS Tipo,
                            IF(ventas_compr.Vet_Cod IS NOT NULL,'Ventas',IF(compr_auto.Cop_Cod IS NOT NULL,'Compras',''))AS Doc ,
                        IF(ventas_compr.Vet_Cod IS NOT NULL,Caj_Fec,IF(compr_auto.Cop_Cod IS NOT NULL,Cop_Fec,''))AS Doc_Fec ,
                        IF(ventas_compr.Com_Cod IS NOT NULL,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)),IF(compr_auto.Cop_Cod IS NOT NULL,Cop_Num,''))AS Doc_Num,
						Pec_Fei,Pec_Fef,perio_cont.Pla_Cod AS Pla_Cod2 , Suc_Des
                        FROM asientos
                                INNER JOIN comprobantes ON asientos.Com_Cod=comprobantes.Com_Cod
                                INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod
                                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
								INNER JOIN perio_cont ON perio_cont.Pec_Cod=comprobantes.Pec_Cod 
                            LEFT JOIN ventas_compr ON comprobantes.Com_Cod=ventas_compr.Com_Cod
                            LEFT JOIN ventas ON ventas.Vet_Cod=ventas_compr.Vet_Cod
                            LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                            LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                            LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                            LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                            LEFT JOIN compr_auto ON comprobantes.Com_Cod=compr_auto.Com_Cod
                            LEFT JOIN compras ON compras.Cop_Cod=compr_auto.Cop_Cod    
                        WHERE det_plan.Pla_Cod=$Par_Sql[0] AND comprobantes.Com_Fec BETWEEN '$Par_Sql[1] 00:00:00' AND '$Par_Sql[2] 23:59:59'
                        AND Com_Est='A'
                        GROUP BY comprobantes.Com_Cod

                                HAVING (SUM(IF(Asi_Deh='D',Asi_Val,0))-SUM(IF(Asi_Deh='H',Asi_Val,0)) > $Par_Sql[3]
                                        OR SUM(IF(Asi_Deh='D',Asi_Val,0))-SUM(IF(Asi_Deh='H',Asi_Val,0)) < -$Par_Sql[3]) 
										OR NOT comprobantes.Com_Fec BETWEEN Pec_Fei AND Pec_Fef OR Pla_Cod2!=det_plan.Pla_Cod
                        ORDER BY Pla_Cod,Com_Fec DESC";
			//echo $sql;
			return $sql;

			/* Cargado de la cabecera del comprobante por Apellido del cliente o proveedor solo ACTIVOS - ANULADOS*/
		case 370:
			$cargar_cabape_153 = "SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con,Tia_Des, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, tipo_asien.Tia_Cod,tipo_asien.Tia_Abr, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est 
		FROM 
		persona
		INNER JOIN $Par_Sql[0] ON (persona.Prs_Cod = $Par_Sql[0].Prs_Cod)
		INNER JOIN comprobantes ON ($Par_Sql[0].$Par_Sql[4] = comprobantes.$Par_Sql[4])
		INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod)
		WHERE Prs_Ape like '%$Par_Sql[1]%' AND Tia_Ini ='$Par_Sql[2]' AND comprobantes.Pec_Cod='$Par_Sql[3]' AND Com_Est = '$Par_Sql[6]' $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom"; //AND comprobantes.Com_Est='A'
			//echo $cargar_cabape_153;
			return $cargar_cabape_153;
			break;

		/* Cargado de la cabecera del comprobante, sea este de cualquier tipo solo ACTIVOS - ANULADOS */
		case 371:
			$cargar_cabcomp_154 = "SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, tipo_asien.Tia_Cod,tipo_asien.Tia_Abr, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est 
		FROM  
		persona
		INNER JOIN $Par_Sql[0] ON (persona.Prs_Cod = $Par_Sql[0].Prs_Cod)
		INNER JOIN comprobantes ON ($Par_Sql[0].$Par_Sql[4] = comprobantes.$Par_Sql[4])
		INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod)
		WHERE Com_Num='$Par_Sql[1]' AND Tia_Ini='$Par_Sql[2]' AND comprobantes.Pec_Cod='$Par_Sql[3]' AND Com_Est = '$Par_Sql[6]' $Par_Sql[5] ORDER BY Com_Fec, Prs_Ape, Prs_Nom"; //AND comprobantes.Com_Est='A'
			//echo $cargar_cabcomp_154;
			return $cargar_cabcomp_154;
			break;

		/* 
		* Cargado de la cabecera del comprobante por codigo 
		*/
		case 372:
			$cargar_cabcod_149 = "SELECT Com_Cod, Com_Num, Pec_Cod, persona.Prs_Ape, Tia_Ini,Tia_Abr,persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est 
		FROM 
		persona
		INNER JOIN $Par_Sql[0] ON (persona.Prs_Cod = $Par_Sql[0].Prs_Cod)
		INNER JOIN comprobantes ON ($Par_Sql[0].$Par_Sql[4] = comprobantes.$Par_Sql[4])
		INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod)
		WHERE Com_Cod =$Par_Sql[1] AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A'";
			//echo $cargar_cabcod_149;
			return $cargar_cabcod_149;
			break;

		case 373:
			$sql = "SELECT Cop_Cod FROM compr_auto WHERE Com_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

		case 374:
			$sql = "SELECT Vet_Cod FROM ventas_compr WHERE Com_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

		/**
		 * Consultar informacion de la compra
		 */
		case 375:
			$sql = "SELECT 
			  compras.Cop_Cod,
			  persona.Prs_Ape,
			  persona.Prs_Nom,
			  date_format(compras.Cop_Sys,'%d/%m/%Y %H:%i')as fecha
			FROM
			  persona
			  INNER JOIN vendedor ON (persona.Prs_Cod = vendedor.Prs_Cod)
			  INNER JOIN compras ON (vendedor.Vnd_Cod = compras.Vnd_Cod)
			WHERE
			  compras.Cop_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

		/**
		 * Consultar informacion de la compra
		 */
		case 376:
			$sql = "SELECT 
			  compras.Cop_Cod,
					  compras.Cop_Num, 
			  persona.Prs_Ape,
			  persona.Prs_Nom,
			  persona.Prs_Ced
			FROM
			  proveedore
			  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
			  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
			  
			WHERE
			  compras.Cop_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

		/**
		 * Consulta el detalle de las compras 
		 */
		case 377:
			$sql = "SELECT iva.Iva_Cod, det_compra.Cop_Pro, iva.Iva_Por, det_compra.Cop_Int, det_compra.Cop_Can, det_compra.Cop_Imp,
							  det_compra.Cop_Pru, compras.Cop_Obs, det_compra.Pro_Cod FROM det_compra INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
							  INNER JOIN compras ON (det_compra.Cop_Cod = compras.Cop_Cod) WHERE det_compra.Cop_Cod = $Par_Sql[0]";
			return $sql;
			break;

		/**
		 * Consultar el codigo de la retencion a modificar en base al codigo de la factura de compra 
		 */
		case 378:
			$sql = "SELECT retencion.Ret_Cod, retencion.Ret_Num,retencion.Ret_Fec,retencion.Aut_Cod,autorizaci.Aut_Sri,retencion.Ret_Xml,retencion.Ret_Aut 
		FROM retencion,autorizaci WHERE retencion.Aut_Cod=autorizaci.Aut_Cod AND retencion.Ret_Est='A' AND retencion.Cop_Cod='$Par_Sql[0]'";
			//echo "<br>".$sql;
			return $sql;
			break;

		case 379:
			/*Consulta informacion de la empresa */
			$sql = "SELECT 
						  empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
						  sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv
						FROM
						  empresas
						  INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
						  INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
					   WHERE
						  sucursal.Suc_Cod=$Par_Sql[0]";
			//echo "1049: ".$sql."<br>";
			return $sql;
			break;

		/**
		 * Consultar el detalle de la retención 
		 */
		case 380:
			$consultar_automatica_manual_381 = "SELECT det_retenc.Ren_Cod, Ren_Sri, Ren_Con, Ret_Bas, Ren_Por,  
		(Ret_Bas * Ren_Por)/100 as Val_Ret, det_retenc.Adq_Cod,if(renta_iva.Ren_Ret='R','RENTA','IVA')as Ren_Ret FROM det_retenc, renta_iva WHERE det_retenc.Ren_Cod = renta_iva.Ren_Cod AND det_retenc.Ret_Cod = $Par_Sql[0]";
			return $consultar_automatica_manual_381;
			break;

		case 381:
			/* 
			* Consulta de los datos del cliente 
			*/
			$sql = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, persona.Prs_Tel, 	
			persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, cliente.Cli_Cod, ventas.Aut_Cod, ventas.Tic_Cod, ventas.Vet_Obs, caja_aper.Caj_Fec, ciudad.Ciu_Des, ventas.Vet_Des,ventas.Vet_Xml, ventas.Ret_Num, ventas.Ret_Fec, ventas.Ret_Aut, caja_aper.Pun_Cod, ventas_det.Vet_Can, ventas_det.Ren_Cod, ventas_det.Ren_Iva, ventas_det.Vet_Pru, ventas_det.Vet_Imp, ventas_det.Vet_Dec, item.Ite_Cor, item.Ite_Lar, iva.Iva_Por, ventas.Vet_Cod, ventas.Vet_Num, ventas_det.Iva_Cod, ventas_det.Pro_Cod, ventas.Vet_Est,ventas.Vnd_Cod, 	
			producto.Pro_Ide, producto.Uni_Cod, producto.Pro_Obs, Nge_Cod, Asi_Int, Vet_Rec, Tic_Des, cliente.Cli_Fac, cliente.Cli_Ruf, cliente.Cli_Dir, ventas_det.Cnt_Cod, ventas_det.Vet_Int, Mar_Des, Vet_Uni,Uni_Des
			FROM cliente, persona, ventas, caja_aper, ciudad, ventas_det, item, iva, producto, tipo_compr, marca ,unidad 
			WHERE cliente.Cli_Cod = ventas.Cli_Cod AND caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Ciu_Cod = ciudad.Ciu_Cod AND ventas.Vet_Cod = 	
			ventas_det.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Mar_Cod = marca.Mar_Cod AND producto.Uni_Cod=unidad.Uni_Cod AND item.Ite_Cod = producto.Ite_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND 	
			persona.Prs_Cod = cliente.Prs_Cod AND ventas.Tic_Cod = tipo_compr.Tic_Cod AND ventas.Vet_Cod = '$Par_Sql[0]' AND Vet_Rec = 0"; //         AND iva.Iva_Cod = producto.Iva_Cod
			//echo $sql;
			return $sql;
			break;

		/**
		 * Consultar informacion de la compra
		 */
		case 382:
			$sql = "SELECT 
		  ventas.Vet_Cod,
		  persona.Prs_Ape,
		  persona.Prs_Nom
		FROM
		  persona
		  INNER JOIN vendedor ON (persona.Prs_Cod = vendedor.Prs_Cod)
		  INNER JOIN ventas ON (vendedor.Vnd_Cod = ventas.Vnd_Cod)
		WHERE
		  ventas.Vet_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

		case 383:
			/* Consulta los tipos de pago */
			$inser_pago_316 = "SELECT pago_venta.Bak_Cod, pago_venta.Ban_Cod, pago_venta.Pag_Cod, Vet_Cue, Vet_Che, Vet_Tot, Vet_Num, Pag_Des, For_Des
					FROM pago_venta, tipos_pago, forma_pago WHERE pago_venta.Pag_Cod = tipos_pago.Pag_Cod AND forma_pago.For_Cod = tipos_pago.For_Cod 
					AND	pago_venta.Vet_Cod = '$Par_Sql[0]' ORDER BY Vet_Num";
			//echo  $inser_pago_316;
			return $inser_pago_316;
			break;

		case 384:
			/* Consulta el bancos del plan de cuentas segun el tipo de pago */
			$bancos_plan_187 = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des FROM banco INNER JOIN det_plan ON (banco.Pld_Cod = det_plan.Pld_Cod) 
							WHERE banco.Ban_Cod = $Par_Sql[0]";
			//echo $bancos_plan_187;
			return $bancos_plan_187;
			break;

		case 385:
			/* Consulta del banco seleccionado */
			$consultar_pago_188 = "SELECT Bak_Cod, Bak_Des FROM bancos WHERE Bak_Cod = $Par_Sql[0]";
			//echo $consultar_pago_188;
			return $consultar_pago_188;
			break;

		/* 
	   * Consulta si la venta tiene retencion
	   */
		case 386:
			$sql = "SELECT Vet_Cod, Ren_Cod, Ren_Iva FROM ventas_det WHERE Vet_Cod = '$Par_Sql[0]' AND (Ren_Cod is not null or Ren_Iva is not null) limit 1";
			return $sql;
			break;

		/* 
	   * Seleccionar los detalles de la venta
	   */
		case 387:
			$sql = "SELECT 
			  ventas.Vet_Cod,
			  ventas.Ret_Fec,
			  ventas.Ret_Num,
			  ventas.Ret_Aut,
			  ventas_det.Vet_Imp,
			  iva.Iva_Por,  
			  ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2) as Vet_Dsc,
			  ventas_det.Ren_Cod,
			  ventas_det.Ren_Iva
			FROM
			  ventas
			  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)  
			  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
			WHERE
			  ventas.Vet_Cod = '$Par_Sql[0]'";
			//echo $sql;	
			return $sql;
			break;

		/* 
	     * Consulta los porcentajes renta_iva segun codigo interno
	     */
		case 388:
			$sql = "SELECT 
			 Ren_Cod, Ren_Sri, Ren_Con, Ren_Por, if(Ren_Ret='R','RENTA','IVA')as Impuesto 
			FROM
			  renta_iva 
			WHERE
			  Ren_Cod = '$Par_Sql[0]'";
			return $sql;
			break;

		/*case 389:
	     $sql= "SELECT Com_Cod, Com_Num, comprobantes.Prv_Cod, comprobantes.Cli_Cod,
                        IF(comprobantes.Prv_Cod IS NULL, prs_cliente.Prs_Ape,prs_provee.Prs_Ape) AS Prs_Ape, 
                        IF(comprobantes.Prv_Cod IS NULL,prs_cliente.Prs_Nom,prs_provee.Prs_Nom) AS Prs_Nom, 
                        IF(comprobantes.Prv_Cod IS NULL,prs_cliente.Prs_Dir,prs_provee.Prs_Dir) AS Prs_Dir, 
                        IF(comprobantes.Prv_Cod IS NULL,prs_cliente.Prs_Tel,prs_provee.Prs_Tel) AS Prs_Tel,
                        IF(comprobantes.Prv_Cod IS NULL,prs_cliente.Prs_Ced,prs_provee.Prs_Tel) AS Prs_Ced,
                        Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val,Com_Est, Tia_Ini, Tia_Abr, Tia_Des,Usu_Cod 
                    FROM comprobantes
                    INNER JOIN tipo_asien ON comprobantes.Tia_Cod = tipo_asien.Tia_Cod
                    LEFT JOIN cliente ON cliente.Cli_Cod=comprobantes.Cli_Cod
                    LEFT JOIN persona AS prs_cliente ON cliente.Prs_Cod=prs_cliente.Prs_Cod
                    LEFT JOIN proveedore ON proveedore.Prv_Cod=comprobantes.Prv_Cod 
                    LEFT JOIN persona AS prs_provee ON proveedore.Prs_Cod=prs_provee.Prs_Cod
                    WHERE Com_Cod='$Par_Sql[0]'"; 
	     return $sql; */

		case 389:
			$sql = "SELECT comprobantes.Com_Cod, Com_Num, comprobantes.Prv_Cod, comprobantes.Cli_Cod,
                        IF(prs_data.Prs_Cod IS NOT NULL, prs_data.Prs_Ape, 
                        IF(comprobantes.Prv_Cod IS NULL, prs_cliente.Prs_Ape, prs_provee.Prs_Ape)) AS Prs_Ape,
                        IF(prs_data.Prs_Cod IS NOT NULL, prs_data.Prs_Nom, 
                        IF(comprobantes.Prv_Cod IS NULL, prs_cliente.Prs_Nom, prs_provee.Prs_Nom)) AS Prs_Nom,
                        IF(prs_data.Prs_Cod IS NOT NULL, prs_data.Prs_Dir, 
                        IF(comprobantes.Prv_Cod IS NULL, prs_cliente.Prs_Dir, prs_provee.Prs_Dir)) AS Prs_Dir,
                        IF(prs_data.Prs_Cod IS NOT NULL, prs_data.Prs_Tel, 
                        IF(comprobantes.Prv_Cod IS NULL, prs_cliente.Prs_Tel, prs_provee.Prs_Tel)) AS Prs_Tel,
                        IF(prs_data.Prs_Cod IS NOT NULL, prs_data.Prs_Ced, 
                        IF(comprobantes.Prv_Cod IS NULL, prs_cliente.Prs_Ced, prs_provee.Prs_Ced)) AS Prs_Ced,
						Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val,Com_Est, Tia_Ini, Tia_Abr, Tia_Des,Usu_Cod 
                            FROM comprobantes
                            INNER JOIN tipo_asien ON comprobantes.Tia_Cod = tipo_asien.Tia_Cod
                            LEFT JOIN cliente ON cliente.Cli_Cod=comprobantes.Cli_Cod
                            LEFT JOIN persona AS prs_cliente ON cliente.Prs_Cod=prs_cliente.Prs_Cod
                            LEFT JOIN proveedore ON proveedore.Prv_Cod=comprobantes.Prv_Cod 
                            LEFT JOIN persona AS prs_provee ON proveedore.Prs_Cod=prs_provee.Prs_Cod
							LEFT JOIN compr_arol ON compr_arol.Com_Cod = comprobantes.Com_Cod 
                            LEFT JOIN antici_rol ON compr_arol.Ant_Cod = antici_rol.Ant_Cod
							LEFT JOIN contratos_lab ON contratos_lab.Con_Cod = antici_rol.Con_Cod
                            LEFT JOIN personal ON personal.Per_Cod = contratos_lab.Per_Cod
                            LEFT JOIN persona As prs_data ON prs_data.Prs_Cod = personal.Prs_Cod
                      WHERE comprobantes.Com_Cod='$Par_Sql[0]'";
			return $sql;





		case 390:
			$sql = "SELECT perio_cont.Pec_Cod,perio_cont.Pec_Fei,perio_cont.Pec_Fef,perio_cont.Pec_Est,Year(Pec_Fei) AS Periodo,perio_cont.Pla_Cod
					FROM plan_cuenta
					  INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
					WHERE
					  Pec_Est = 'A' AND ('$Par_Sql[0]' BETWEEN Pec_Fei AND Pec_Fef) AND plan_cuenta.Emp_Cod = '$Par_Sql[1]'
					ORDER BY Pec_Fei DESC";
			return $sql;
	}
}
