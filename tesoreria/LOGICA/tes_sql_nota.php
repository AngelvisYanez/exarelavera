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
function sentencias_cccc($id,$Par_Sql)
{
    $sql="";
    switch($id)
    {
        case 0:
            $sql="";
            //echo $sql;
            break;
        case 1://Busqueda de Proveedores
            if($Par_Sql[2]=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";}
            else {$search="Prs_Ced LIKE '$Par_Sql[0]%'";}
            if($Par_Sql[3]==""){$campos="COUNT(Prv_Cod) as total";}
            else{
                $Par_Sql[3]="ORDER BY Prs_Ape ".$Par_Sql[3];
                $campos=" Prv_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor, Prv_Fax,Prs_Dir, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
            }
            //$sql="SELECT $campos FROM proveedore, persona WHERE $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //ChromePhp::log($campos);
            break;
        case 2://Busqueda de Comprobantes    
            if($Par_Sql[count($Par_Sql)-1]==""){$campos="COUNT(Com_Cod) as total";}
            else{
                $Par_Sql[count($Par_Sql)-1]="ORDER BY Com_Fec ".$Par_Sql[count($Par_Sql)-1];
                $campos='Com_Cod,Com_Num,Com_Fec,Com_Val,
                            CONCAT(Tia_Abr,"-",IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT("0",CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),"-",CAST(Com_Num AS char)) as Id_Com';
            }
            $sql="SELECT $campos FROM comprobantes "
                    . "INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod"
                    . " WHERE Prv_Cod=$Par_Sql[1] ";
            //echo $sql; 
            break;
         case 3://Busqueda de Proveedores con array
            if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
            else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
            if(isset($Par_Sql["limits"])){
                $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                $campos=" Cli_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente,Prs_Dir, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est";
            }
            else{$campos="COUNT(Cli_Cod) as total";$Par_Sql["limits"]="";}
            $sql="SELECT $campos FROM cliente, persona WHERE $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
            //ChromePhp::log($sql);
            break;
        case 4:
		/* Consulta de los tipos de asientos  filtrados por el sub-tipo */
                    if($Par_Sql[0]=="") $Par_Sql[0]=" WHERE Tia_Tip='B'";
                    else $Par_Sql[0]="";
		$sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien $Par_Sql[0] ";
		return $sql; 
        case 5:
            $sql = "SELECT Pec_Cod,Pec_Fei,Pec_Fef,CAST(SUBSTRING_INDEX(Pec_Fei,'-',1) AS char) AS Anio,perio_cont.Pla_Cod
                    FROM perio_cont
                    LEFT JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                    WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]' AND Pec_Est='A' ORDER BY Pec_Fei DESC";
                   // //ChromePhp::log($sql);
		return $sql;
        case 6:/*consulta de facturas pendientes segun el proveedor*/
		$sql= "SELECT proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, compras.Cop_Fec, 
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
		AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND compras.Cop_Est='A'  AND comprobantes.Com_Est='A' 
        AND proveedore.Prv_Cod=$Par_Sql[1] AND Emp_Cod=$Par_Sql[0] $Par_Sql[2] GROUP BY compras.Cop_Cod ORDER BY 
		ccpp_pagar.Cpp_Ven "; //AND perio_cont.Pec_Cod= $Par_Sql[2]
		//echo $sql;
            break;
         case 7:
            $sql="SELECT Dcc_Cod,det_ccpp_c.Cpc_Cod,det_ccpp_c.Pag_Cod,det_ccpp_c.Cpc_Cod,det_ccpp_c.Com_Cod,Cpc_Fec,Cpc_Val,Cpc_Obs,tipos_pago.For_Cod,Pag_Des,comprobantes.*,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Codigo  FROM det_ccpp_c 
                INNER JOIN tipos_pago ON tipos_pago.Pag_Cod=det_ccpp_c.Pag_Cod
                INNER JOIN forma_pago ON tipos_pago.For_Cod=forma_pago.For_Cod
                INNER JOIN comprobantes ON det_ccpp_c.Com_Cod=comprobantes.Com_Cod
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                WHERE Cpc_Cod='$Par_Sql[0]' AND comprobantes.Com_Est='A'";
            //echo $sql;
                //ChromePhp::log($sql);
            break;
            case 8: /*consulta de facturas con pagos segun el cliente*/
                if($Par_Sql[1]!='') $Par_Sql[1]="AND cliente.Cli_Cod=$Par_Sql[1]";
                if($Par_Sql[2]!=''){
                    $Par_Sql[2]="AND perio_cont.Pec_Cod= $Par_Sql[2]";
                }  else {
                    $Par_Sql[2]="AND Caj_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
                }
                if($Par_Sql[5]=='T'){
                    $Par_Sql[5]="";
                }else if ($Par_Sql[5]=='1'){
                    $Par_Sql[5]="AND forma_pago.For_Cod = 1";
                }else if ($Par_Sql[5]=='2'){
                    $Par_Sql[5]="AND forma_pago.For_Cod = 2";
                }
        $sql= "SELECT cliente.Cli_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Cod, ventas.Vet_Obs, caja_aper.Caj_Fec,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(ventas.Vet_Num,9,'0')AS char)) AS Vet_Num, Vet_Tot, Tic_Des, Pec_Cod, Pec_Fei, Pec_Fef, forma_pago.For_Cod, For_Des
        FROM cliente
            INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
            INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
            INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
            INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
            INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
            INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
            INNER JOIN tipo_compr ON ventas.Tic_Cod = tipo_compr.Tic_Cod
            INNER JOIN pago_venta on pago_venta.Vet_cod =ventas.Vet_Cod
            INNER JOIN tipos_pago on pago_venta.Pag_Cod = tipos_pago.Pag_Cod
            INNER JOIN forma_pago on tipos_pago.For_Cod = forma_pago.For_Cod
            INNER JOIN det_plan ON pago_venta.Pld_Cod = det_plan.Pld_Cod
            INNER JOIN plan_cuenta ON det_plan.Pla_Cod = plan_cuenta.Pla_Cod
            INNER JOIN perio_cont ON plan_cuenta.Pla_Cod = perio_cont.Pla_Cod
            WHERE cliente.Prs_Cod = persona.Prs_Cod 
            AND ventas.Tic_Cod = 32 
            AND ventas.vet_Est='A'
            $Par_Sql[1]  
            $Par_Sql[2] 
            AND sucursal.Emp_Cod=$Par_Sql[0]
            $Par_Sql[5]
            GROUP BY ventas.Vet_Cod 
            ORDER BY Vet_Num";
        //echo $sql;   --AND ventas.vet_Est<>'E'   SIRVE PARA EXCLUIR TODAS LAS FILAS CON VALOR A 'E'
                //ChromePhp::log($sql);
            break;
        case 88888: /*consulta de facturas con pagos segun el proveedor*/
                if($Par_Sql[1]!='') $Par_Sql[1]="AND cliente.Cli_Cod=$Par_Sql[1]";
                if($Par_Sql[2]!='') $Par_Sql[2]="AND perio_cont.Pec_Cod= $Par_Sql[2]"; else $Par_Sql[2]=" AND Caj_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
		$sql= "SELECT cliente.Cli_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Cod, ventas.Vet_Obs, ccpp_cobrar.Cpc_Cod, caja_aper.Caj_Fec,  CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num, ccpp_cobrar.Cpc_Ven, ccpp_cobrar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,
    CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,
    CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),
    CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
    ,IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))=Asi_Val,'Pagado'
    ,IF(DATEDIFF(Cpc_Ven,CURDATE())>=0,CONCAT(CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento 
    ,IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))) AS Abono 
		FROM cliente
INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
INNER JOIN ccpp_cobrar ON (ventas.Vet_Cod = ccpp_cobrar.Vet_Cod) 
INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) 
INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod) 
INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod) 
INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod) 
LEFT JOIN det_ccpp_c ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod 
LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_c.Com_Cod),persona
		WHERE cliente.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_cobrar.Com_Cod AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'D' AND (ventas.vet_Est='A' OR ventas.vet_Est='E')  AND (comprobantes.Com_Est='A' OR comprobantes.Com_Est='E')
                $Par_Sql[1] $Par_Sql[2] AND sucursal.Emp_Cod=$Par_Sql[0] $Par_Sql[5] GROUP BY ventas.Vet_Cod ORDER BY Vet_Num /*ccpp_cobrar.Cpc_Ven*/  "; //
		//echo $sql;
                //ChromePhp::log($sql);
            break;
        case 9:
            $sql="SELECT CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) AS Com_Num,Com_Fec,CONCAT('',CAST(TRUNCATE(Com_Val, 2) AS char)) AS Com_Val,Com_Obs,Prs_Ape,Prs_Nom,Prs_Dir,Prs_Ced FROM comprobantes INNER JOIN cliente ON cliente.Cli_Cod=comprobantes.Cli_Cod INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod WHERE Com_Cod='$Par_Sql[0]'";
            //echo $sql;
            break;
        case 10:
            $sql="SELECT Asi_Cod,Asi_Deh,Pld_Cdc,Pld_Des,Asi_Glo as Glosa,Asi_Val,IF(Asi_Deh='D',Asi_Val,'') AS Debe,IF(Asi_Deh='H',Asi_Val,'') AS Haber FROM asientos INNER JOIN det_plan ON asientos.Pld_Cod=det_plan.Pld_Cod WHERE Com_Cod='$Par_Sql[0]' ORDER BY Asi_Deh";
            //echo $sql;
            break;
        case 11:
            $sql="SELECT cheq_det_ccpp.Dcc_Cod,cheques_ext.*,Bak_Des,CONCAT(Prs_Ape,' ',Prs_Nom) as Benefactor FROM cheques_ext
                    INNER JOIN cheq_det_ccpp ON cheques_ext.Che_Cod=cheq_det_ccpp.Che_Cod
                    INNER JOIN det_ccpp_c ON cheq_det_ccpp.Dcc_Cod=det_ccpp_c.Dcc_Cod
                    INNER JOIN bancos ON cheques_ext.Bak_Cod=bancos.Bak_Cod
                    INNER JOIN cliente ON cheques_ext.Cli_Cod=cliente.Cli_Cod
                    INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
                    WHERE det_ccpp_c.Cpc_Cod='$Par_Sql[0]' AND Com_Cod='$Par_Sql[1]'";
            //echo $sql;
            break;
        case 12:
                    if($Par_Sql[3]=="d") {$search="det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";}
                    else {$search="det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";}
                    if($Par_Sql[4]==""){$campos="COUNT(det_plan.Pld_Cod) as total";}
                    else{
                        $Par_Sql[4]="ORDER BY det_plan.Pld_Cod ".$Par_Sql[4];
                        $campos="det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
                                IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
                                IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
                    }
                    $sql="SELECT $campos
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
                if($Par_Sql[1]!='') $Par_Sql[1]="AND cliente.Cli_Cod=$Par_Sql[1]";
                //if($Par_Sql[2]!='') $Par_Sql[2]="AND perio_cont.Pec_Cod= $Par_Sql[2]"; else $Par_Sql[2]=" AND Caj_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
		$sql= "SELECT  det_plan.Pld_Cod,Pld_Cdc,Pld_Des,Prs_Nom,Prs_Ape,cliente.Cli_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Cod, ccpp_cobrar.Cpc_Cod, caja_aper.Caj_Fec, CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num, ccpp_cobrar.Cpc_Ven, ccpp_cobrar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Cpc_Val,2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpc_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Cpc_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Cpc_Val,2),0))) AS Abono 
FROM cliente
INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
INNER JOIN ccpp_cobrar ON (ventas.Vet_Cod = ccpp_cobrar.Vet_Cod) 
INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) 
INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod) 
INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod) 
INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod) 
LEFT JOIN det_ccpp_c ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod 
LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_c.Com_Cod),persona
WHERE cliente.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_cobrar.Com_Cod 
AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'D' AND (ventas.vet_Est='A' OR ventas.vet_Est='E')  AND (comprobantes.Com_Est='A' OR comprobantes.Com_Est='E')
 $Par_Sql[1] $Par_Sql[2] AND sucursal.Emp_Cod=$Par_Sql[0] $Par_Sql[5] GROUP BY ventas.Vet_Cod ORDER BY ccpp_cobrar.Cpc_Ven "; //
		//echo $sql;
            break;
        case 14:
            $sql="SELECT Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue from banco
                INNER JOIN det_plan ON det_plan.Pld_Cod=banco.Pld_Cod
                INNER JOIN perio_cont ON det_plan.Pla_Cod=perio_cont.Pla_Cod
                WHERE Pec_Cod='$Par_Sql[0]' AND Ban_Est='A' AND Pld_Est='A' ";
                //ChromePhp::log($sql);
            //echo $sql;
            break;
        case 15:
		/* Consulta de los tipos de asientos  filtrados por el sub-tipo */
                    if($Par_Sql[0]=="") $Par_Sql[0]=" WHERE Tia_Tip='B' AND Tia_Est='A' ";
                    else $Par_Sql[0]=" WHERE  Tia_Est='A'";
		$sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien $Par_Sql[0] ";
		return $sql;
        case 16:
            $sql="SELECT * FROM tipos_pago WHERE For_Cod='1'";
            //echo $sql;
            break;
        case 17: /*consulta de facturas con pagos segun el proveedor*/
                if($Par_Sql[1]!='') $Par_Sql[1]="AND cliente.Cli_Cod=$Par_Sql[1]";
                if($Par_Sql[2]!='') $Par_Sql[2]="AND perio_cont.Pec_Cod= $Par_Sql[2]"; else $Par_Sql[2]=" AND Caj_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
		$sql= "SELECT cliente.Cli_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Cod, ccpp_cobrar.Cpc_Cod, caja_aper.Caj_Fec,  CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num, ccpp_cobrar.Cpc_Ven, ccpp_cobrar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,
    CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,
    CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
    ,IF(SUM(ROUND(Cpc_Val,2))=Asi_Val,'Pagado'
    ,IF(DATEDIFF(Cpc_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento 
    ,IF(SUM(ROUND(Cpc_Val,2)) IS NULL,0,SUM(ROUND(Cpc_Val,2))) AS Abono 
		FROM cliente
INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
INNER JOIN ccpp_cobrar ON (ventas.Vet_Cod = ccpp_cobrar.Vet_Cod) 
INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) 
INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod) 
INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod) 
INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod) 
INNER JOIN det_ccpp_c ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod 
INNER JOIN comprobantes as comp2 ON comp2.Com_Cod=det_ccpp_c.Com_Cod,persona 
		WHERE cliente.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_cobrar.Com_Cod AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'D' AND (ventas.vet_Est='A' OR ventas.vet_Est='E')  AND (comprobantes.Com_Est='A' OR comprobantes.Com_Est='E') AND comp2.Com_Est='A'
               $Par_Sql[1] $Par_Sql[2] AND sucursal.Emp_Cod=$Par_Sql[0] $Par_Sql[5] GROUP BY ventas.Vet_Cod ORDER BY ccpp_cobrar.Cpc_Ven  "; //
		//echo $sql;
            break;
        case 18:
            $sql="UPDATE comprobantes SET Com_Est='I' WHERE Com_Cod='$Par_Sql[0]'";
            //echo $sql;
            break;    
        case 19:
            $sql="INSERT INTO det_ccpp_c SET Cpc_Cod=$Par_Sql[0],Pag_Cod=$Par_Sql[1],Com_Cod=$Par_Sql[2],Cpc_Fec='$Par_Sql[3]',Cpc_Val='$Par_Sql[4]',Cpc_Obs='$Par_Sql[5]'";
            //echo $sql;
            //ChromePhp::log($sql);
            break;
        case 20:
                $sql="SELECT comprobantes.Com_Cod, Com_Doc,det_plan.Pld_cod,Pld_des FROM comprobantes
                            INNER JOIN asientos ON comprobantes.Com_Cod=asientos.Com_Cod
                            INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
                            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                            WHERE Com_Doc='$Par_Sql[0]' AND Emp_Cod=$Par_Sql[1] AND asientos.Pld_Cod='$Par_Sql[2]' AND asientos.Asi_Deh='$Par_Sql[3]'";
                //echo $sql."<br>";
               break;
        case 21:
                $sql="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[9]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'), Com_Tipo='$Par_Sql[8]', Com_Doc='$Par_Sql[10]',Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]' ";//Antes Com_Tip
                //echo $sql;
                 break;
        case 22:
                $sql="INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5]";
                //echo $sql."<br>";
                break;      
        case 26:           
		/*
		* Selecionar el numero maximo de comprobante mensual segun el tipo I=ingreso, E=egreso, D=diario
		*/
		$sql="SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes, tipo_asien WHERE comprobantes.Tia_Cod=tipo_asien.Tia_Cod AND 
                tipo_asien.Tia_Ini='$Par_Sql[0]' AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
            //echo $sql;
            break;
        case 27:
            if($Par_Sql[2]=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";}
                    else {$search="Prs_Ced LIKE '$Par_Sql[0]%'";}
                    if($Par_Sql[3]==""){$campos="COUNT(Prv_Cod) as total";}
                    else{
                        $Par_Sql[3]="ORDER BY Prs_Ape ".$Par_Sql[3];
                        $campos=" Prv_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor,Prs_Ape,Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
                    }
                    $sql="SELECT $campos
                                FROM proveedore, persona WHERE $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
            //echo $sql;
            break;
        case 28:
            $sql="INSERT INTO persona SET Prs_Ced='0',Prs_Ape=UPPER('$Par_Sql[0]'),Prs_Nom=UPPER('$Par_Sql[1]'),Ciu_Cod=217";
            //echo $sql;
            break;
        case 29:
            $sql="INSERT INTO proveedore SET Prs_Cod=$Par_Sql[0], Emp_Cod=$Par_Sql[1],Prv_Con='N',Prv_Esp='N'";
            //echo $sql;
            break;
        case 30:
            $sql="SELECT Cpc_Fec,TRUNCATE(Cpc_Val, 2) AS Cpc_Val,ccpp_cobrar.Cpc_Obs,Pag_Des,Cpc_Ven,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,Caj_Fec FROM det_ccpp_c INNER JOIN tipos_pago ON tipos_pago.Pag_Cod=det_ccpp_c.Pag_Cod INNER JOIN ccpp_cobrar ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod INNER JOIN ventas ON ccpp_cobrar.Vet_Cod=ventas.Vet_Cod INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                    INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                    INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
            WHERE det_ccpp_c.Cpc_Cod='$Par_Sql[0]' AND det_ccpp_c.Com_Cod='$Par_Sql[1]'";
           // echo $sql;
            //ChromePhp::log($sql);
            break;
        case 31:
            $sql="SELECT ventas.Vet_Cod,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,Com_Fec,Asi_Val,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,caja_aper.Caj_Fec,Ret_Num,Ret_Fec,'RT' AS tipo,Vet_Obs,
	sum( ( 
	  (ventas_det.Vet_Imp-(((ventas_det.Vet_Imp*ventas.Vet_Des)/100)+((ventas_det.Vet_Imp*ventas_det.Vet_Dec)/100))) /* IMPORTE */
		  /*+(ventas_det.Vet_Imp-(((ventas_det.Vet_Imp*ventas.Vet_Des)/100)+((ventas_det.Vet_Imp*ventas_det.Vet_Dec)/100)))*(IF(ice.Ice_Por IS NOT NULL,1+ice.Ice_Por/100,0))*/ /* ICE */
		  )	*(1+iva.Iva_Por/100)	/* IVA */
	 ) AS total,Cpc_Cod
FROM ventas
INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
/*LEFT JOIN ice ON (ice.Ice_int=ventas_det.Ice_Int)*/
INNER JOIN ccpp_cobrar ON ventas.Vet_Cod=ccpp_cobrar.Vet_Cod
INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) 
        INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
        INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
        INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod)
        INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 
        INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
WHERE Cpc_Cod='$Par_Sql[0]'
GROUP BY ventas.Vet_Cod";
            //echo $sql;
            break;
         case 32:
            $sql="SELECT Ret_Num,Ret_Fec,CONCAT('R',Ren_Ret) AS tipo,SUM(Vet_Imp*(Ren_Por/100)) AS retencion FROM ventas_det
INNER JOIN renta_iva ON renta_iva.Ren_Cod=ventas_det.Ren_Cod
INNER JOIN ventas ON ventas_det.Vet_Cod=ventas.Vet_Cod
WHERE ventas.Vet_Cod='$Par_Sql[0]'
GROUP BY ventas.Vet_Cod";
            //echo $sql;
              break;
        case 33:
            $sql="SELECT Ret_Num,Ret_Fec,CONCAT('R',Ren_Ret) AS tipo,SUM((Vet_Imp*(Iva_Por/100))*(Ren_Por/100)) AS retencion FROM ventas_det
INNER JOIN renta_iva ON renta_iva.Ren_Cod=ventas_det.Ren_Iva
INNER JOIN iva ON iva.Iva_Cod=ventas_det.Iva_Cod
INNER JOIN ventas ON ventas_det.Vet_Cod=ventas.Vet_Cod
WHERE ventas.Vet_Cod='$Par_Sql[0]'
GROUP BY ventas.Vet_Cod";
            //echo $sql;
              break;
		case 34: /*consulta de facturas con pagos segun el proveedor*/
                if($Par_Sql[1]!='') $Par_Sql[1]="AND cliente.Cli_Cod=$Par_Sql[1]";
                if($Par_Sql[2]!='') $Par_Sql[2]="AND perio_cont.Pec_Cod= $Par_Sql[2]"; else $Par_Sql[2]=" AND Cpc_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
                if($Par_Sql[5]!='T') $Par_Sql[5]="AND tipos_pago.Pag_Cod=$Par_Sql[5]"; else  $Par_Sql[5]="";
                $sql= "SELECT CONCAT(ventas.Vet_Cod,'_',ccpp_cobrar.Cpc_Cod),
  ventas.Vet_Cod,Vet_Obs,ccpp_cobrar.Cpc_Cod,det_ccpp_c.Com_Cod,cliente.Cli_Cod,det_ccpp_c.Pag_Cod,Pag_Des,Cpc_Fec,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom,   caja_aper.Caj_Fec,  CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,Cpc_Val,det_ccpp_c.Cpc_Obs,
  CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,
  CONCAT(Bak_Des,'/',Che_Cta) AS Banco,Che_Num,Che_Fec
  
FROM det_ccpp_c
INNER JOIN ccpp_cobrar ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod
INNER JOIN comprobantes ON comprobantes.Com_Cod=det_ccpp_c.Com_Cod
INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
INNER JOIN ventas ON (ccpp_cobrar.Vet_Cod = ventas.Vet_Cod)
INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod  
INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod 
INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod 
INNER JOIN tipos_pago ON det_ccpp_c.Pag_Cod=tipos_pago.Pag_Cod 
LEFT JOIN cheq_det_ccpp ON det_ccpp_c.Dcc_Cod=cheq_det_ccpp.Dcc_Cod
LEFT JOIN cheques_ext ON (cheques_ext.Che_Cod=cheq_det_ccpp.Che_Cod  AND Che_Est='A')
LEFT JOIN bancos ON cheques_ext.Bak_Cod=bancos.Bak_Cod
		WHERE (ventas.vet_Est='A' OR ventas.vet_Est='E')  AND (comprobantes.Com_Est='A' OR comprobantes.Com_Est='E')
                $Par_Sql[1] $Par_Sql[2] AND sucursal.Emp_Cod=$Par_Sql[0] $Par_Sql[5] /*GROUP BY ventas.Vet_Cod*/ ORDER BY Cpc_Fec DESC  "; //
                //ChromePhp::log($sql);
		//echo $sql;
            break; 
        case 35:
            $sql="INSERT INTO `cheques_ext`(`Bak_Cod`,`Cli_Cod`,`Che_Cta`,`Che_Num`,`Che_Fec`,`Che_Val`,`Che_Obs`)
                VALUES($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]',$Par_Sql[3],'$Par_Sql[4]',$Par_Sql[5],'$Par_Sql[6]');";
            //echo $sql;
              break;   
        case 36:
            $sql="INSERT INTO `cheq_det_ccpp`(`Che_Cod`,`Dcc_Cod`)
                    VALUES($Par_Sql[0],$Par_Sql[1]);";
            //echo $sql;
              break;  
        case 37:
            $sql="SELECT * FROM bancos WHERE Bak_Est='A';";
            //echo $sql;
              break;      
        case 38:
            $sql="SELECT cheques_ext.Che_Cod,Che_Fec,Che_Num,Bak_Des AS Banco,Che_Cta FROM cheques_ext 
                    INNER JOIN cheq_det_ccpp ON cheq_det_ccpp.Che_Cod=cheques_ext.Che_Cod
                    INNER JOIN bancos ON bancos.Bak_Cod=cheques_ext.Bak_Cod
                    WHERE cheq_det_ccpp.Dcc_Cod=$Par_Sql[0] AND (Che_Est!='I' AND Che_Est!='P') 
                    ";
            //echo $sql;
              break; 
		/*********************/ 
        case 39:
            $sql="SELECT comprobantes.*,CONCAT(Prs1.Prs_Ape,' ',Prs1.Prs_Nom)AS Cliente,CONCAT(Prs2.Prs_Ape,' ',Prs2.Prs_Nom)AS Usuario FROM comprobantes 
                INNER JOIN cliente ON comprobantes.Cli_Cod=cliente.Cli_Cod
                INNER JOIN persona AS Prs1 ON cliente.Prs_Cod=Prs1.Prs_Cod
                INNER JOIN usuarios ON comprobantes.Usu_Cod=usuarios.Usu_Cod
                INNER JOIN persona AS Prs2 ON usuarios.Prs_Cod=Prs2.Prs_Cod
                WHERE Com_Cod='$Par_Sql[0]';";
                //echo $sql;
              break;  
        case 40:
            $sql="SELECT det_ccpp_c.*,Pag_Des,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num FROM det_ccpp_c
                INNER JOIN tipos_pago ON det_ccpp_c.Pag_Cod=tipos_pago.Pag_Cod
                INNER JOIN ccpp_cobrar ON det_ccpp_c.Cpc_Cod=ccpp_cobrar.Cpc_Cod
                INNER JOIN ventas ON ccpp_cobrar.Vet_Cod=ventas.Vet_Cod
                INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
                
                LEFT JOIN cheq_det_ccpp ON cheq_det_ccpp.Dcc_Cod=det_ccpp_c.Dcc_Cod
                LEFT JOIN cheques ON cheques.Che_Cod=cheq_det_ccpp.Che_Cod
                WHERE det_ccpp_c.Com_Cod='$Par_Sql[0]';";
                //echo $sql;
                //ChromePhp::log($sql);
              break; 	  
        case 41: /*consulta de facturas con pagos segun el proveedor*/
                if($Par_Sql[1]!='') $Par_Sql[1]="AND cliente.Cli_Cod=$Par_Sql[1]";
                if($Par_Sql[2]!='') $Par_Sql[2]="AND perio_cont.Pec_Cod= $Par_Sql[2]"; 
//                else $Par_Sql[2]=" AND Cpc_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'";
                if($Par_Sql[5]=='T'||$Par_Sql[5]=='C')
                    $Par_Sql[2]=" AND Cpc_Fec > '$Par_Sql[4] 00:00:00'";
                else
                    $Par_Sql[2]=" AND Che_Fec > '$Par_Sql[4] 00:00:00'";
                if($Par_Sql[5]=='C') $Par_Sql[2]=$Par_Sql[2]." AND Cpc_Fec=Che_Fec ";
                if($Par_Sql[5]=='P') $Par_Sql[2]=$Par_Sql[2]." AND Cpc_Fec<Che_Fec  ";
                if($Par_Sql[5]!='T') $Par_Sql[5]="AND tipos_pago.Pag_Cod=3"; else  $Par_Sql[5]="AND tipos_pago.Pag_Cod=3";
                $sql= "SELECT 
                        ventas.Vet_Cod,ccpp_cobrar.Cpc_Cod,det_ccpp_c.Com_Cod,cliente.Cli_Cod,det_ccpp_c.Pag_Cod,Pag_Des,Cpc_Fec,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom,   caja_aper.Caj_Fec,  CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,Cpc_Val,det_ccpp_c.Cpc_Obs,
                        CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,
                        CONCAT(Bak_Des,'/',Che_Cta) AS Banco,Che_Num,Che_Fec

                            FROM det_ccpp_c
                            INNER JOIN ccpp_cobrar ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod
                            INNER JOIN comprobantes ON comprobantes.Com_Cod=det_ccpp_c.Com_Cod
                            INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
                            INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
                            INNER JOIN ventas ON (ccpp_cobrar.Vet_Cod = ventas.Vet_Cod)
                            INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                            INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                            INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                            INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod  
                            INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod 
                            INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod 
                            INNER JOIN tipos_pago ON det_ccpp_c.Pag_Cod=tipos_pago.Pag_Cod 
                            LEFT JOIN cheq_det_ccpp ON det_ccpp_c.Dcc_Cod=cheq_det_ccpp.Dcc_Cod
                            LEFT JOIN cheques_ext ON (cheques_ext.Che_Cod=cheq_det_ccpp.Che_Cod  AND Che_Est='A')
                            LEFT JOIN bancos ON cheques_ext.Bak_Cod=bancos.Bak_Cod
                          WHERE (ventas.vet_Est='A' OR ventas.vet_Est='E')  AND (comprobantes.Com_Est='A' OR comprobantes.Com_Est='E')
                          $Par_Sql[1] $Par_Sql[2] AND sucursal.Emp_Cod=$Par_Sql[0] $Par_Sql[5] /*GROUP BY ventas.Vet_Cod*/ ORDER BY Cpc_Fec DESC  "; //
                          //echo $sql;
                          //ChromePhp::log($sql);
                      break; 	
        case 42:
            /*
            * Selecionar el numero maximo de comprobante mensual segun el tipo I=ingreso, E=egreso, D=diario
            */
            $sql="SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod=$Par_Sql[0] AND Pec_Cod=$Par_Sql[1] AND MONTH(Com_Fec)=$Par_Sql[2]";
            //echo $sql;
            break;
        case 43:
            /* Consulta de los tipos de asientos  filtrados por el sub-tipo */
                if($Par_Sql[0]=="") $Par_Sql[0]=" WHERE Tia_Tip='B' AND Tia_Est='A' ";
                else $Par_Sql[0]=" WHERE  Tia_Est='A' AND(Tia_Ini='I' OR Tia_Ini='D' )";
            $sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien $Par_Sql[0] ";
            return $sql;
		
		/*consultamos un detalle del pago a ventas*/	
		case 44:
            $sql = "SELECT det_ccpp_c.Dcc_Cod,ventas.Vet_Num,cheques_ext.Che_Fec,cheques_ext.Che_Num,bancos.Bak_Des,cheques_ext.Che_Cta,					
					det_ccpp_c.Cpc_Val,Cpc_Fec,det_ccpp_c.Cpc_Obs,Com_Fec,comprobantes.Com_Con,tipos_pago.Pag_Des,persona.Prs_Ced,persona.Prs_Nom,persona.Prs_Ape,persona1.Prs_Nom as usuNom, persona1.Prs_Ape as usuApe,
					CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as numCom
					FROM det_ccpp_c
					  det_ccpp_c
					  LEFT OUTER JOIN cheq_det_ccpp ON (det_ccpp_c.Dcc_Cod = cheq_det_ccpp.Dcc_Cod)
					  LEFT OUTER JOIN cheques_ext ON (cheq_det_ccpp.Che_Cod = cheques_ext.Che_Cod)
					  INNER JOIN ccpp_cobrar ON (det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod)
					  LEFT OUTER JOIN bancos ON (cheques_ext.Bak_Cod = bancos.Bak_Cod)
					  INNER JOIN tipos_pago ON (det_ccpp_c.Pag_Cod = tipos_pago.Pag_Cod)
					  INNER JOIN ventas ON (ccpp_cobrar.Vet_Cod = ventas.Vet_Cod)
					  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
					  INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
					  INNER JOIN comprobantes ON (det_ccpp_c.Com_Cod = comprobantes.Com_Cod)
					  INNER JOIN usuarios ON (comprobantes.Usu_Cod = usuarios.Usu_Cod)
					  INNER JOIN persona persona1 ON (usuarios.Prs_Cod = persona1.Prs_Cod)
					  INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod)
					WHERE det_ccpp_c.Com_Cod = '$Par_Sql[0]'";
		//echo $sql;
                    //ChromePhp::log($sql);
        return $sql;
		
		case 45:
            $sql="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
            sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
            return $sql;
        case 46:
            $sql="SELECT ventas.Vet_Cod,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,
                    Com_Fec,Asi_Val,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,caja_aper.Caj_Fec,Ret_Num,Ret_Fec,'RT' AS tipo,Cpc_Cod, Asi_Val AS total 
                    FROM ventas 
                    INNER JOIN ccpp_cobrar ON ventas.Vet_Cod=ccpp_cobrar.Vet_Cod 
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                    INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod 
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                    INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
                    INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) 
                    INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
                    INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
                    INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod) 
                    INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 
                    INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod 
                    WHERE Cpc_Cod='$Par_Sql[0]' GROUP BY ventas.Vet_Cod";
            return $sql;
        case 47: /*consulta de facturas con pagos segun el proveedor*/
                if($Par_Sql[1]!='') $Par_Sql[1]="AND cliente.Cli_Cod=$Par_Sql[1]";
                if($Par_Sql[2]!=''){ $Par_Sql[2]="AND perio_cont.Pec_Cod= $Par_Sql[2]"; $pago=''; }else{ $pago=" AND Cpc_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59' ";  $Par_Sql[2]=" AND Caj_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'" ; }
		$sql= "SELECT cliente.Cli_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Cod, ccpp_cobrar.Cpc_Cod, caja_aper.Caj_Fec,  CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num, ccpp_cobrar.Cpc_Ven, ccpp_cobrar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,
    CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,
    CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
    ,IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))=Asi_Val,'Pagado'
    ,IF(DATEDIFF(Cpc_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento 
    ,IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))) AS Abono 
		FROM cliente
INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
INNER JOIN ccpp_cobrar ON (ventas.Vet_Cod = ccpp_cobrar.Vet_Cod) 
INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod) 
INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod) 
INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod) 
INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod) 
LEFT JOIN det_ccpp_c ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod $pago
LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_c.Com_Cod),persona
		WHERE cliente.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_cobrar.Com_Cod AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'D' AND (ventas.vet_Est='A' OR ventas.vet_Est='E')  AND (comprobantes.Com_Est='A' OR comprobantes.Com_Est='E')
                $Par_Sql[1] $Par_Sql[2] AND sucursal.Emp_Cod=$Par_Sql[0] $Par_Sql[5] GROUP BY ventas.Vet_Cod ORDER BY Vet_Num /*ccpp_cobrar.Cpc_Ven*/  "; //
		//var_dump( $sql );
            break;   
        case 48:
            $sql="SELECT * FROM perio_cont WHERE Pec_Cod=$Par_Sql[0] ";
			//echo $sql."<br/>";
            break;			
        case 49:
            $sql="SELECT Dcc_Cod,det_ccpp_c.Cpc_Cod,det_ccpp_c.Pag_Cod,det_ccpp_c.Cpc_Cod,det_ccpp_c.Com_Cod,Cpc_Fec,Cpc_Val,Cpc_Obs,tipos_pago.For_Cod,Pag_Des,comprobantes.*,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Codigo  FROM det_ccpp_c 
                INNER JOIN tipos_pago ON tipos_pago.Pag_Cod=det_ccpp_c.Pag_Cod
                INNER JOIN forma_pago ON tipos_pago.For_Cod=forma_pago.For_Cod
                INNER JOIN comprobantes ON det_ccpp_c.Com_Cod=comprobantes.Com_Cod
                INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                WHERE Cpc_Cod='$Par_Sql[0]' AND comprobantes.Com_Est='A' AND Cpc_Fec BETWEEN '$Par_Sql[2] 00:00:00' AND '$Par_Sql[3] 23:59:59' ";
            //echo $sql."<br/>";
                //ChromePhp::log($sql);
            break;
		case 50:
            $sql="UPDATE det_ccpp_c SET Cpc_Est='I' WHERE Dcc_Cod='$Par_Sql[0]'";
            //echo $sql;
			break;
		case 51:
            $sql="UPDATE cheques_ext SET Che_Est='I' WHERE Che_Cod='$Par_Sql[0]'";
            //echo $sql;
			 break;
		case 52:
            $sql="SELECT cheques_ext.Che_Cod FROM cheques_ext INNER JOIN cheq_det_ccpp ON cheq_det_ccpp.Che_Cod=cheques_ext.Che_Cod WHERE cheq_det_ccpp.Dcc_Cod='$Par_Sql[0]'";
            //echo $sql;	
			break;
        case 53:
            $sql = "SELECT Pag_Abr FROM tipos_pago WHERE Pag_Cod='$Par_Sql[0]' ";
        break;
    }
    //echo $sql."<br/>";
    return $sql;  
   
    
}
// </editor-fold>