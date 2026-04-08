<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2015-07-22
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */
// <editor-fold defaultstate="collapsed" desc="Sentencias">
function sentencias_ccpp($id, $Par_Sql)
{
        $sql = "";
        switch ($id) {
                case 0:
                        $sql = "";
                        //echo $sql;
                        break;
                case 1: //Busqueda de Proveedores
                        if ($Par_Sql[2] == "d") {
                                $search = "(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";
                        } else {
                                $search = "Prs_Ced LIKE '$Par_Sql[0]%'";
                        }
                        if ($Par_Sql[3] == "") {
                                $campos = "COUNT(Prv_Cod) as total";
                        } else {
                                $Par_Sql[3] = "ORDER BY Prs_Ape " . $Par_Sql[3];
                                $campos = " Prv_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor, Prv_Fax,Prs_Dir, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
                        }
                        $sql = "SELECT $campos FROM proveedore, persona WHERE $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
                        break;
                case 2: //Busqueda de Comprobantes    
                        if ($Par_Sql[count($Par_Sql) - 1] == "") {
                                $campos = "COUNT(Com_Cod) as total";
                        } else {
                                $Par_Sql[count($Par_Sql) - 1] = "ORDER BY Com_Fec " . $Par_Sql[count($Par_Sql) - 1];
                                $campos = 'Com_Cod,Com_Num,Com_Fec,Com_Val,
                            CONCAT(Tia_Abr,"-",IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT("0",CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),"-",CAST(Com_Num AS char)) as Id_Com';
                        }
                        $sql = "SELECT $campos FROM comprobantes "
                                . "INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod"
                                . " WHERE Prv_Cod=$Par_Sql[1] ";
                        //echo $sql; 
                        break;
                case 3: //Busqueda de Proveedores con array
                        if ($Par_Sql['op_opciones'] == "d") {
                                $search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
                        } else {
                                $search = "Prs_Ced LIKE '$Par_Sql[search]%'";
                        }
                        if (isset($Par_Sql["limits"])) {
                                $Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
                                $campos = " Prv_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor, Prv_Fax,Prs_Dir, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
                        } else {
                                $campos = "COUNT(Prv_Cod) as total";
                                $Par_Sql["limits"] = "";
                        }
                        $sql = "SELECT $campos FROM proveedore, persona WHERE $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
                        break;
                case 4:
                        /* Consulta de los tipos de asientos  filtrados por el sub-tipo */
                        if ($Par_Sql[0] == "") $Par_Sql[0] = " WHERE Tia_Tip='B'";
                        else $Par_Sql[0] = "";
                        $sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien $Par_Sql[0] ";
                        return $sql;
                case 5:
                        if (isset($Par_Sql[1])) {
                                $sqlPec = " AND Pec_Cod='$Par_Sql[1]' ";
                        } else {
                                $sqlPec = '';
                        }
                        $sql = "SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est,perio_cont.Pla_Cod, Year(Pec_Fei) as Periodo FROM perio_cont, plan_cuenta WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND Pec_Est = 'A' AND plan_cuenta.Emp_Cod= $Par_Sql[0] $sqlPec ORDER BY Pec_Fei Desc";
                        //echo $sql;
                        return $sql;
                case 6:/*consulta de facturas pendientes segun el proveedor*/
                        $sql = "SELECT proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, compras.Cop_Fec, 
		compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Codigo 
                ,IF(DATEDIFF(Cpp_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpp_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido') AS vencimiento
                ,IF(SUM(ROUND(Pag_Val,2)) IS NULL,0,SUM(ROUND(Pag_Val,2))) AS Abono
		FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
		INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) 
		INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
		INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) 
		INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
                INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
                LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
                ,persona
		WHERE proveedore.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod 
		AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND (compras.Cop_Est='A' OR compras.Cop_Est='E') AND 
		comprobantes.Com_Est='A' AND proveedore.Prv_Cod=$Par_Sql[1] AND Emp_Cod=$Par_Sql[0] $Par_Sql[2] GROUP BY compras.Cop_Cod ORDER BY 
		ccpp_pagar.Cpp_Ven "; //AND perio_cont.Pec_Cod= $Par_Sql[2]
                        //echo $sql;
                        break;
                case 7:
                        $sql = "SELECT det_ccpp_p.Cpp_Cod,det_ccpp_p.Pag_Cod,det_ccpp_p.Com_Cod,Pag_Fec,Pag_Val,Pag_Obs,tipos_pago.For_Cod,Pag_Des,comprobantes.*,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Codigo  FROM det_ccpp_p 
                INNER JOIN tipos_pago ON tipos_pago.Pag_Cod=det_ccpp_p.Pag_Cod
                INNER JOIN forma_pago ON tipos_pago.For_Cod=forma_pago.For_Cod
                INNER JOIN comprobantes ON det_ccpp_p.Com_Cod=comprobantes.Com_Cod
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                WHERE Cpp_Cod='$Par_Sql[0]' AND comprobantes.Com_Est='A'";
                        //echo $sql;
                        break;
                case 8: /*consulta de facturas con pagos segun el proveedor*/
                        if ($Par_Sql[1] != '') $Par_Sql[1] = "AND proveedore.Prv_Cod=$Par_Sql[1]";
                        if ($Par_Sql[2] != '') $Par_Sql[2] = "AND perio_cont.Pec_Cod= $Par_Sql[2]";
                        else $Par_Sql[2] = " AND Cop_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
                        $sql = "SELECT proveedore.Prv_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, compras.Cop_Fec, 
		compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpp_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpp_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))) AS Abono
		FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
		INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) 
		INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
		INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) 
		INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
                INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
                LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
                LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
		WHERE proveedore.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod 
		AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND (compras.Cop_Est='A' OR compras.Cop_Est='E') AND 
		(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E') $Par_Sql[1] $Par_Sql[2] AND Emp_Cod=$Par_Sql[0] $Par_Sql[5] GROUP BY compras.Cop_Cod ORDER BY 
		Cop_Fec "; //
                        //echo $sql;
                        break;
                case 9:
                        $sql = "SELECT CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) AS Com_Num,Com_Fec,CONCAT('',CAST(TRUNCATE(Com_Val, 2) AS char)) AS Com_Val,Com_Obs,Prs_Ape,Prs_Nom,Prs_Dir,Prs_Ced FROM comprobantes INNER JOIN proveedore ON proveedore.Prv_Cod=comprobantes.Prv_Cod INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod WHERE Com_Cod='$Par_Sql[0]'";
                        //echo $sql;
                        break;
                case 10:
                        $sql = "SELECT Asi_Cod,Asi_Deh,Pld_Cdc,Pld_Des,Asi_Glo as Glosa,Asi_Val,IF(Asi_Deh='D',Asi_Val,'') AS Debe,IF(Asi_Deh='H',Asi_Val,'') AS Haber FROM asientos INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod WHERE Com_Cod='$Par_Sql[0]' ORDER BY Asi_Deh";
                        //echo $sql;
                        break;
                case 11:
                        $sql = "SELECT Che_Cod,Che_Num,Che_Fec,IF(Che_Ben IS NULL OR TRIM(Che_Ben)='',CONCAT(Prs_Ape,' ',Prs_Nom),Che_Ben) as Beneficiario,Che_Val,Che_Obs,Pld_Des FROM cheques INNER JOIN proveedore ON proveedore.Prv_Cod=cheques.Prv_Cod INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod INNER JOIN asientos ON asientos.Asi_Cod=cheques.Asi_Cod INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod WHERE Com_Cod='$Par_Sql[0]'";
                        //echo $sql;
                        break;
                case 12:
                        if ($Par_Sql[3] == "d") {
                                $search = "det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";
                        } else {
                                $search = "det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";
                        }
                        if ($Par_Sql[4] == "") {
                                $campos = "COUNT(det_plan.Pld_Cod) as total";
                        } else {
                                $Par_Sql[4] = "ORDER BY det_plan.Pld_Cod " . $Par_Sql[4];
                                $campos = "det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
                                IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
                                IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
                        }
                        $sql = "SELECT $campos
                                FROM det_plan 
                                INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                                INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                                INNER JOIN empresas ON plan_cuenta.Emp_Cod=empresas.Emp_Cod 
                                LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
                                LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
                                WHERE plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' 
                                AND $search AND Pec_Cod =$Par_Sql[2] 
                                AND det_plan.Pld_Tip = 'D' $Par_Sql[4]";
                        //echo $sql;
                        break;
                case 13: /*consulta de facturas por vencer */
                        if ($Par_Sql[1] != '') $Par_Sql[1] = "AND proveedore.Prv_Cod=$Par_Sql[1]";
                        //if($Par_Sql[2]!='') $Par_Sql[2]="AND perio_cont.Pec_Cod= $Par_Sql[2]"; else $Par_Sql[2]=" AND Cop_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
                        $sql = "SELECT det_plan.Pld_Cod,Pld_Cdc,Pld_Des,Prs_Nom,Prs_Ape,proveedore.Prv_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, compras.Cop_Fec, 
		compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpp_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpp_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))) AS Abono
		FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
		INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) 
		INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
		INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) 
		INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
                INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
                LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
               LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
		WHERE proveedore.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod 
		AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND (compras.Cop_Est='A' OR compras.Cop_Est='E') AND 
		(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E' ) $Par_Sql[1] $Par_Sql[2] AND Emp_Cod=$Par_Sql[0] $Par_Sql[5] GROUP BY compras.Cop_Cod ORDER BY 
		ccpp_pagar.Cpp_Ven "; //
                        //echo $sql;
                        break;
                case 14:
                        $sql = "SELECT Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue from banco
                INNER JOIN det_plan ON det_plan.Pld_Cod=banco.Pld_Cod
                INNER JOIN perio_cont ON det_plan.Pla_Cod=perio_cont.Pla_Cod
                WHERE Pec_Cod='$Par_Sql[0]' AND Ban_Est='A' AND Pld_Est='A' AND Ban_Cue!=0 ";
                        //echo $sql;
                        break;
                case 15:
                        /* Consulta de los tipos de asientos  filtrados por el sub-tipo */
                        if ($Par_Sql[0] == "") $Par_Sql[0] = " WHERE Tia_Tip='B' AND Tia_Est='A' ";
                        else $Par_Sql[0] = " WHERE  Tia_Est='A'";
                        $sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien $Par_Sql[0] ";
                        return $sql;
                case 16:
                        $sql = "SELECT * FROM tipos_pago WHERE For_Cod='1'";
                        //echo $sql;
                        break;
                case 17:
                        $sql = "SELECT MAX(Che_Num) as Che_Num FROM cheques WHERE Ban_Cod=$Par_Sql[0];";
                        break;
                case 18:
                        $sql = "SELECT COUNT(Che_Cod) AS conteo FROM cheques WHERE Ban_Cod=$Par_Sql[0] AND Che_Num='$Par_Sql[1]' ";
                        //echo $ins_asie."<br>";
                        break;
                case 19:
                        $sql = "INSERT INTO det_ccpp_p SET Cpp_Cod=$Par_Sql[0],Pag_Cod=$Par_Sql[1],Com_Cod=$Par_Sql[2],Pag_Fec='$Par_Sql[3]',Pag_Val='$Par_Sql[4]',Pag_Obs='$Par_Sql[5]'";
                        //echo $sql;
                        break;
                case 20:
                        $sql = "SELECT comprobantes.Com_Cod, Com_Doc,det_plan.Pld_cod,Pld_des FROM comprobantes
                            INNER JOIN asientos ON comprobantes.Com_Cod=asientos.Com_Cod
                            INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
                            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                            WHERE Com_Doc='$Par_Sql[0]' AND Emp_Cod=$Par_Sql[1] AND asientos.Pld_Cod='$Par_Sql[2]' AND asientos.Asi_Deh='$Par_Sql[3]'";
                        //echo $sql."<br>";
                        break;
                case 21:
                        $sql = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[9]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'), Com_Tipo='$Par_Sql[8]', Com_Doc='$Par_Sql[10]',Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]' "; //Antes Com_Tip
                        //echo $sql;
                        break;
                case 22:
                        $sql = "INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5]";
                        //echo $sql."<br>";
                        break;
                case 23:
                        $sql = "INSERT INTO persona SET Prs_Ced='0',Prs_Ape=UPPER('$Par_Sql[0]'),Prs_Nom=UPPER('$Par_Sql[1]'),Ciu_Cod=217";
                        //echo $sql;
                        break;
                case 24:
                        $sql = "INSERT INTO proveedore SET Prs_Cod=$Par_Sql[0], Emp_Cod=$Par_Sql[1],Prv_Con='N',Prv_Esp='N'";
                        //echo $sql;
                        break;
                case 25:
                        $sql = "INSERT INTO cheques SET Prv_Cod=$Par_Sql[0], Ban_Cod=$Par_Sql[1], Asi_Cod=$Par_Sql[2], Che_Num='$Par_Sql[3]'," .
                                " Che_Val=$Par_Sql[5], Che_Obs=UPPER('$Par_Sql[6]'), Che_Fec='$Par_Sql[7]', Che_Cod = $Par_Sql[8], Che_Ben=UPPER(TRIM('$Par_Sql[9]')) ;";
                        //echo $sql;
                        break;
                case 26:
                        /*
		* Selecionar el numero maximo de comprobante mensual segun el tipo I=ingreso, E=egreso, D=diario
		*/
                        $sql = "SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes, tipo_asien WHERE comprobantes.Tia_Cod=tipo_asien.Tia_Cod AND 
                tipo_asien.Tia_Ini='$Par_Sql[0]' AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
                        //echo $sql;
                        break;
                case 27:
                        if ($Par_Sql[2] == "d") {
                                $search = "(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";
                        } else {
                                $search = "Prs_Ced LIKE '$Par_Sql[0]%'";
                        }
                        if ($Par_Sql[3] == "") {
                                $campos = "COUNT(Prv_Cod) as total";
                        } else {
                                $Par_Sql[3] = "ORDER BY Prs_Ape " . $Par_Sql[3];
                                $campos = " Prv_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor,Prs_Ape,Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
                        }
                        $sql = "SELECT $campos
                                FROM proveedore, persona WHERE $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
                        //echo $sql;
                        break;
                case 28:
                        $sql = "INSERT INTO persona SET Prs_Ced='0',Prs_Ape=UPPER('$Par_Sql[0]'),Prs_Nom=UPPER('$Par_Sql[1]'),Ciu_Cod=217";
                        //echo $sql;
                        break;
                case 29:
                        $sql = "INSERT INTO proveedore SET Prs_Cod=$Par_Sql[0], Emp_Cod=$Par_Sql[1],Prv_Con='N',Prv_Esp='N'";
                        //echo $sql;
                        break;
                case 30:
                        $sql = "SELECT Pag_Fec,TRUNCATE(Pag_Val, 2) AS Pag_Val,Pag_Obs,Pag_Des,Cpp_Ven,Cop_Num,Cop_Fec FROM det_ccpp_p 
                    INNER JOIN tipos_pago ON tipos_pago.Pag_Cod=det_ccpp_p.Pag_Cod
                    INNER JOIN ccpp_pagar ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
                    INNER JOIN compras ON ccpp_pagar.Cop_Cod=compras.Cop_Cod
                    WHERE det_ccpp_p.Cpp_Cod='$Par_Sql[0]' AND det_ccpp_p.Com_Cod='$Par_Sql[1]'";
                        //echo $sql;
                        break;
                case 31:
                        $sql = "UPDATE comprobantes SET Com_Est='I' WHERE Com_Cod='$Par_Sql[0]'";
                        //echo $sql;
                        break;
                case 32: /*consulta de facturas con pagos segun el proveedor*/
                        /*if($Par_Sql[1]!='') $Par_Sql[1]="AND proveedore.Prv_Cod=$Par_Sql[1]";
                if($Par_Sql[2]!='') $Par_Sql[2]="AND perio_cont.Pec_Cod= $Par_Sql[2]"; else $Par_Sql[2]=" AND Cop_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
		$sql= "SELECT proveedore.Prv_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, compras.Cop_Fec, 
		compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpp_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpp_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))) AS Abono
		FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
		INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) 
		INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
		INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) 
		INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
                INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
                INNER JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
                INNER JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
		WHERE proveedore.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod 
		AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND (compras.Cop_Est='A' OR compras.Cop_Est='E')  AND comp2.Com_Est='A' AND 
		(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E' ) $Par_Sql[1] $Par_Sql[2] AND Emp_Cod=$Par_Sql[0] $Par_Sql[5] GROUP BY compras.Cop_Cod ORDER BY 
		ccpp_pagar.Cpp_Ven "; //*/
                        if ($Par_Sql[1] != '') $Par_Sql[1] = "AND proveedore.Prv_Cod=$Par_Sql[1]";
                        if ($Par_Sql[2] != '') $Par_Sql[2] = "AND perio_cont.Pec_Cod= $Par_Sql[2]";
                        else $Par_Sql[2] = " AND Cop_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
                        if (!empty($Par_Sql[5]) && $Par_Sql[5] != 'T') $Par_Sql[5] = "AND tipos_pago.Pag_Cod=$Par_Sql[5]";
                        else  $Par_Sql[5] = "";
                        $sql = "SELECT proveedore.Prv_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, compras.Cop_Fec, 
		compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,
                tipos_pago.Pag_Cod,Pag_Fec,Pag_Val,Pag_Des,comp2.Com_Cod AS Pag_Com
		FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
		INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) 
		INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
		INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) 
		INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
                INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
                INNER JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
                INNER JOIN tipos_pago ON det_ccpp_p.Pag_Cod=tipos_pago.Pag_Cod 
                INNER JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
		WHERE proveedore.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod 
		AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND (compras.Cop_Est='A' OR compras.Cop_Est='E')  AND comp2.Com_Est='A' AND 
		(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E' ) $Par_Sql[1] $Par_Sql[2] AND Emp_Cod=$Par_Sql[0] $Par_Sql[5] /*GROUP BY compras.Cop_Cod*/ ORDER BY 
		ccpp_pagar.Cpp_Ven "; //
                        //echo $sql;
                        break;
                case 33:
                        $sql = "UPDATE cheques,asientos SET Che_Est='I' WHERE asientos.Asi_Cod=cheques.Asi_Cod AND Com_Cod='$Par_Sql[0]'";
                        //echo $sql;
                        break;
                case 34:
                        $sql = "SELECT compras.Cop_Cod,compras.Cop_Num,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,Com_Fec,
                       /* sum( ( 
                        (det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100))) /* IMPORTE */
                                +(det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100)))*(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0)) /* ICE */
                                )	*(1+iva.Iva_Por/100)	/* IVA */
                        ) AS total,*/
                        SUM((( det_compra.Cop_Imp  - (det_compra.Cop_Imp * compras.Cop_Des / 100)  - (det_compra.Cop_Imp * det_compra.Cop_Dec / 100)) +  (
                                det_compra.Cop_Imp - (det_compra.Cop_Imp * compras.Cop_Des / 100)  - (det_compra.Cop_Imp * det_compra.Cop_Dec / 100) )  * IF(det_compra.Cop_Ice IS NOT NULL, det_compra.Cop_Ice/100, 0) 
                                ) * (1 + iva.Iva_Por/100)) AS total

                        proveedore.Prv_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor,Cpp_Cod,Cop_Obs,Tic_Des
                        FROM
                        compras
                        INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
                        INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)		
                        INNER JOIN ccpp_pagar ON compras.Cop_Cod=ccpp_pagar.Cop_Cod
                        INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
                        INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                        INNER JOIN proveedore ON (proveedore.Prv_Cod = compras.Prv_Cod) 
                        INNER JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
                        INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = compras.Tic_Cod
                        WHERE Cpp_Cod='$Par_Sql[0]'  
                        GROUP BY compras.Cop_Cod";
                        //echo $sql;
                        break;
                case 35:
                        $sql = "SELECT Ret_Num,Ret_Fec,SUM((Ret_Bas*(Ren_Por/100))) AS retencion,CONCAT('R',Ren_Ret) AS tipo
                                FROM det_retenc 
                                        INNER JOIN renta_iva ON det_retenc.Ren_Cod=renta_iva.Ren_Cod
                                        INNER JOIN retencion ON det_retenc.Ret_Cod=retencion.Ret_Cod
                                WHERE Cop_Cod='$Par_Sql[0]' AND Ret_Asu='N' AND Ret_Est='A'
                                GROUP BY Ren_Ret";
                        //echo $sql;
                        break;
                case 36:
                        $sql = "SELECT  CONCAT(Ban_Cue,'-',Pld_Des) AS Banco,Che_Num,Che_Fec,Che_Val 
                                FROM asientos
                                        INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod
                                        INNER JOIN banco ON banco.Pld_Cod=asientos.Pld_Cod
                                        LEFT JOIN cheques ON cheques.Asi_Cod=asientos.Asi_Cod AND banco.Ban_Cod=cheques.Ban_Cod
                                WHERE Com_Cod='$Par_Sql[0]'";
                        //echo $sql;
                        break;



                case 37:
                        $sql = "SELECT tipo_compr.Tic_Cod,Tic_Sri, tipo_compr.Tic_Des, tipo_compr.Tic_Est
                                        FROM tipo_compr WHERE tipo_compr.Tic_Est='A' AND Tic_Sri=0  OR Tic_Sri=1    ";
                        //echo $sql;               
                        break;
                case 38:
                        $sql = "SELECT ccpp_prove.Pld_Cod, det_plan.Pld_Des, ccpp_prove.Ccp_Def, ccpp_prove.Ccp_Cxp FROM det_plan INNER JOIN ccpp_prove ON (det_plan.Pld_Cod = ccpp_prove.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
                        //echo $sql;
                        break;
                case 39:
                        $sql = "SELECT perio_cont.Pec_Cod,perio_cont.Pla_Cod,Pec_Fei,Pec_Fef,Year(Pec_Fef)as Pla_Fec FROM perio_cont 
                    INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                    WHERE Emp_Cod=$Par_Sql[0] AND Pec_Est='A' AND Pla_Est='A' ORDER BY Pla_Fec DESC";
                        //echo $sql;               
                        break;
                case 40:
                        $sql = "SELECT Ciu_Des, Ciu_Cod FROM ciudad WHERE Ciu_Des != '' ORDER BY Ciu_Des ASC";
                        //echo $sql;               
                        break;
                case 41:
                        $sql = "INSERT INTO compras(Tic_Cod,Ciu_Cod,Vnd_Cod,Prv_Cod,Cop_Num,Cop_Fec,Cop_Obs,Pec_Cod,Cop_Est)
                VALUES($Par_Sql[Tic_Cod],$Par_Sql[Ciu_Cod],$Par_Sql[Vnd_Cod],$Par_Sql[Prv_Cod],'$Par_Sql[Cop_Num]','$Par_Sql[Cop_Fec]','$Par_Sql[Cop_Obs]',$Par_Sql[Pec_Cod],'E');";
                        //echo $sql;               
                        break;
                case 42:
                        $sql = "INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Com_Num,Com_Fec,Com_Con,Com_Val,Com_Obs,Tia_Cod,Com_Est,Usu_Cod,Com_Gen)
                            VALUES($Par_Sql[Pec_Cod],$Par_Sql[Prv_Cod],0,'$Par_Sql[Cop_Fec]','$Par_Sql[Cop_Obs]',$Par_Sql[Asi_Val],'$Par_Sql[Com_Obs]',1,'E','$_SESSION[Ses_Usu_Cod]','A');";
                        //echo $sql;               
                        break;
                case 43:
                        $sql = "INSERT INTO asientos(Com_Cod,Asi_Deh,Asi_Val,Asi_Con,Pld_Cod,Asi_Glo)
                        VALUES($Par_Sql[Com_Cod],'H',$Par_Sql[Asi_Val],'$Par_Sql[Pld_Cod]','$Par_Sql[Pld_Cod]','$Par_Sql[Com_Obs]');";
                        //echo $sql;               
                        break;
                case 44:
                        $sql = "INSERT INTO ccpp_pagar(Cop_Cod,Com_Cod,Cpp_Ven,Cpp_Obs)VALUES($Par_Sql[Cop_Cod],$Par_Sql[Com_Cod],'$Par_Sql[Cpp_Ven]','$Par_Sql[Com_Obs]');";
                        //echo $sql;               
                        break;
                case 45:
                        $sql = "SELECT ccpp_prove.Pld_Cod,Pld_Cdc,Pld_Des FROM ccpp_prove
                        INNER JOIN det_plan ON ccpp_prove.Pld_Cod=det_plan.Pld_Cod 
                        WHERE Pla_Cod=$Par_Sql[0]";
                        //echo $sql;               
                        break;
                case 46:
                        $sql = "SELECT ccpp_cliente.Pld_Cod,Pld_Cdc,Pld_Des FROM ccpp_cliente
                        INNER JOIN det_plan ON ccpp_cliente.Pld_Cod=det_plan.Pld_Cod 
                        WHERE Pla_Cod=$Par_Sql[0]";
                        //echo $sql;               
                        break;
                case 47:
                        $sql = "INSERT INTO ccpp_prove(Pld_Cod,Ccp_Cxp) VALUES($Par_Sql[0],'S')";
                        //echo $sql;               
                        break;
                case 48:
                        $sql = "INSERT INTO ccpp_cliente(Pld_Cod,Cpc_Cxc) VALUES($Par_Sql[0],'S')";
                        //echo $sql;               
                        break;
                case 49:
                        $sql = "SELECT DISTINCT tipos_pago.Pag_Cod,Pag_Des FROM tipos_pago 
                    INNER JOIN pago_plan ON pago_plan.Pag_Cod=tipos_pago.Pag_Cod
                    INNER JOIN banco ON pago_plan.Ban_Cod=banco.Ban_Cod
                    INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                    INNER JOIN perio_cont ON perio_cont.Pla_Cod=det_plan.Pla_Cod
                    WHERE For_Cod=1 AND pago_plan.Pag_Est='A' AND tipos_pago.Pag_Est='A' AND Ban_Est='A' AND Pld_Est='A' AND Pec_Cod=$Par_Sql[0]";
                        //echo $sql;               
                        break;
                case 50:
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
  Pec_Est = 'A' AND plan_cuenta.Emp_Cod = $Par_Sql[0]
ORDER BY
  Pec_Fei DESC";
                        //echo $cargar_per_214;
                        break;
                case 51:
                        $sql = "SELECT banco.Pld_Cod,Pld_Cdc,Pld_Des,banco.Ban_Cod,Ban_Cue,Ban_Tip FROM banco 
INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
INNER JOIN pago_plan ON pago_plan.Ban_Cod=banco.Ban_Cod
INNER JOIN perio_cont ON perio_cont.Pla_Cod=det_plan.Pla_Cod
WHERE pago_plan.Pag_Est='A' AND Ban_Est='A' AND Pld_Est='A' AND Pec_Cod=$Par_Sql[0] AND Pag_Cod=$Par_Sql[1]";
                        //echo $sql;               
                        break;
                case 52:
                        $sql = "INSERT INTO det_ccpp_p SET Cpp_Cod=$Par_Sql[0],Pag_Cod=$Par_Sql[1],Com_Cod=$Par_Sql[2],Pag_Fec='$Par_Sql[3]',Pag_Val='$Par_Sql[4]',Pag_Obs='$Par_Sql[5]',Cpp_Int=$Par_Sql[6]";
                        //echo $sql.'<br>';
                        break;
                case 53:
                        /**
                         * Consulta del vendedor en base al codigo de la persona
                         */
                        $sql = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod, Pun_Des FROM vendedor, puntos_imp WHERE vendedor.Pun_Cod = puntos_imp.Pun_Cod AND vendedor.Vnd_Est = 'A' AND vendedor.Prs_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[1]";
                        //echo $sql;            
                        break;
                case 54:
                        /**
                         * Consulta del vendedor en base al codigo de la persona
                         */
                        $sql = "SELECT Cpp_Cod,ccpp_pagar.Cop_Cod,Cop_Num,Com_Fec, Com_Val AS total,compras.Prv_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor,
                    CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo
                    FROM ccpp_pagar
                    INNER JOIN compras ON (ccpp_pagar.Cop_Cod = compras.Cop_Cod) 
                    INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
                    INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                    INNER JOIN proveedore ON (proveedore.Prv_Cod = compras.Prv_Cod) 
                    INNER JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
                    WHERE ccpp_pagar.Cpp_Cod=$Par_Sql[0]";
                        //echo $sql;            
                        break;
                case 55:
                        /*
            * Selecionar el numero maximo de comprobante mensual segun el tipo I=ingreso, E=egreso, D=diario
            */
                        $sql = "SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod=$Par_Sql[0] AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
                        //echo $sql;
                        break;
                case 56:
                        /* Consulta de los tipos de asientos  filtrados por el sub-tipo */
                        if ($Par_Sql[0] == "") $Par_Sql[0] = " WHERE Tia_Tip='B' AND Tia_Est='A' ";
                        else $Par_Sql[0] = " WHERE  Tia_Est='A' AND(Tia_Ini='E' OR Tia_Ini='D' )";
                        $sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien $Par_Sql[0] ";
                        //echo $sql;
                        break;
                case 57:
                        $sql = "SELECT det_ccpp_p.Cpp_Cod,det_ccpp_p.Pag_Cod,det_ccpp_p.Com_Cod,Pag_Fec,Pag_Val,Pag_Obs,tipos_pago.For_Cod,Pag_Des,comprobantes.*,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Codigo  FROM det_ccpp_p 
                INNER JOIN tipos_pago ON tipos_pago.Pag_Cod=det_ccpp_p.Pag_Cod
                INNER JOIN forma_pago ON tipos_pago.For_Cod=forma_pago.For_Cod
                INNER JOIN comprobantes ON det_ccpp_p.Com_Cod=comprobantes.Com_Cod
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                WHERE Cpp_Cod='$Par_Sql[0]' AND comprobantes.Com_Est='A'  AND Pag_Fec BETWEEN '$Par_Sql[2] 00:00:00' AND '$Par_Sql[3] 23:59:59'  ";
                        //echo $sql;
                        break;
                case 58: /*consulta de facturas con pagos segun el proveedor*/
                        if ($Par_Sql[1] != '') $Par_Sql[1] = "AND proveedore.Prv_Cod=$Par_Sql[1]";
                        if ($Par_Sql[2] != '') {
                                $Par_Sql[2] = "AND perio_cont.Pec_Cod= $Par_Sql[2]";
                                $pago = "";
                        } else {
                                $pago = " AND Pag_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59' ";
                                $Par_Sql[2] = " AND Cop_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
                        }
                        $sql = "SELECT proveedore.Prv_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, compras.Cop_Fec, 
		compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpp_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpp_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))) AS Abono
		FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
		INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) 
		INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
		INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) 
		INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
                INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
                LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod $pago
                LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
		WHERE proveedore.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod   
		AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND (compras.Cop_Est='A' OR compras.Cop_Est='E') AND 
		(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E') $Par_Sql[1] $Par_Sql[2] AND Emp_Cod=$Par_Sql[0] $Par_Sql[5] GROUP BY compras.Cop_Cod ORDER BY 
		Cop_Fec "; //
                        //echo $sql;
                        break;
                case 59:
                        $sql = "SELECT * FROM perio_cont WHERE Pec_Cod=$Par_Sql[0] ";
                        //echo $sql;
                        break;
                case 60:
                        $sql = "SELECT Che_Cod,Che_Fec,Che_Num,Pld_Des AS Banco FROM cheques 
                INNER JOIN asientos ON cheques.Asi_Cod=asientos.Asi_Cod
                INNER JOIN banco ON cheques.Ban_Cod=banco.Ban_Cod
                INNER JOIN det_plan ON det_plan.Pld_Cod=banco.Pld_Cod
                WHERE Com_Cod=$Par_Sql[0] ";
                        //echo $sql;
                        break;
                case 61: /*consulta de facturas con pagos segun el proveedor*/
                        if ($Par_Sql[1] != '') $Par_Sql[1] = "AND proveedore.Prv_Cod=$Par_Sql[1]";
                        if ($Par_Sql[2] != '') $Par_Sql[2] = "AND perio_cont.Pec_Cod= $Par_Sql[2]";
                        else $Par_Sql[2] = " AND Cop_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
                        $sql = "SELECT proveedore.Prv_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, compras.Cop_Fec, 
		compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpp_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpp_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento
                ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))) AS Abono
		FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
		INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) 
		INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
		INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) 
		INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
                INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
                INNER JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
                INNER JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
		WHERE proveedore.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod 
		AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND (compras.Cop_Est='A' OR compras.Cop_Est='E')  AND comp2.Com_Est='A' AND 
		(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E' ) $Par_Sql[1] $Par_Sql[2] AND Emp_Cod=$Par_Sql[0] $Par_Sql[5] GROUP BY compras.Cop_Cod ORDER BY 
		ccpp_pagar.Cpp_Ven "; //			
                        //echo $sql;
                        break;

                case 62:
                        $sql = "SELECT anticipos_proveedores.Atp_Cod
                        from det_ant_ccpp, anticipos_proveedores
                        where  anticipos_proveedores.Atp_Cod = det_ant_ccpp.Atp_Cod and  det_ant_ccpp.Com_Cod = '$Par_Sql[Com_Cod]';";
                        break;

                case 63:
                        $sql = "SELECT dac.Dac_Cod
                                                from det_ant_ccpp as dac 
                                                where dac.Atp_Cod = $Par_Sql[Atp_Cod]
                                                and dac.Com_Cod <> $Par_Sql[Com_Cod];";
                        break;
                case 64:
                        $sql = "UPDATE pago_anticipo_proveedores
                        SET Pap_Est='$Par_Sql[Pap_Est]'
                        WHERE Atp_Cod='$Par_Sql[Atp_Cod]';";
                        break;

                case 65:
                        $sql = "UPDATE anticipos_proveedores
                                                        SET Atp_Est='$Par_Sql[Atp_Est]'
                                                        WHERE Atp_Cod='$Par_Sql[Atp_Cod]';";
                        break;

                case 66:
                        $sql = "DELETE FROM det_ant_ccpp  WHERE Com_Cod=$Par_Sql[Com_Cod];";
                        break;

                case 67:
                        $sql = "UPDATE comprobantes as com
                                                inner join asientos as asi on (asi.Com_Cod=com.Com_Cod)
                                                left join cheques as che on (che.Asi_Cod=asi.Asi_Cod)
                                         SET com.Com_Est='I', che.Che_Est='I'
                                         where
                                                com.Com_Cod='$Par_Sql[Com_Cod]';";
                        break;
        }




        //echo $sql."<br/>";
        return $sql;
}
// </editor-fold>