<?php
/**
 * ACTIVOS FIJOS - DEPRECIACI�N
 */

function sentencias_depreciacion($id,$Par_Sql)
{
    switch($id)
    {	
        //Select para cargar los periodos contables
        case 1:
                $sql = "SELECT perio_cont.Pec_Cod,perio_cont.Pec_Fei,perio_cont.Pec_Fef,perio_cont.Pec_Est,Year(Pec_Fei) AS Periodo,perio_cont.Pla_Cod
                        FROM plan_cuenta
                        INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
                        WHERE
                        Pec_Est = 'A' AND plan_cuenta.Emp_Cod = '$Par_Sql[0]' ORDER BY Pec_Fei DESC"; 
        break;
        //Select para extraer todos los activos fijos
        case 2:
                if(!empty($Par_Sql['Suc_Cod'])){$condicion="AND Suc_Cod='$Par_Sql[Suc_Cod]'";}
                if(!empty($Par_Sql['Act_Cod'])){$condicion=$condicion." AND activo.Act_Cod='$Par_Sql[Act_Cod]'";}
                //if(!empty($Par_Sql['Pec_Cod'])){$condicion=$condicion." AND comprobantes.Pec_Cod='$Par_Sql[Pec_Cod]'";}
                if(!empty($Par_Sql['Pld_Cod'])){$condicion=$condicion." AND tipoactivo_ccontable.Pld_Cod='$Par_Sql[Pld_Cod]'";}
                $sql = "SELECT activo.Act_Cod,Act_Can,Act_Val,Act_Res,Act_Pde,Act_Ann,Act_Des,Act_Fec,Act_Ffd,CONCAT(dp.Pld_Cdc,' - ',dp.Pld_Des) AS Pld_Des,CONCAT(dp2.Pld_Cdc,' - ',dp2.Pld_Des) AS Pld_Ccc,IF(ISNULL(MAX(Acd_Fpd)),'vacio',MAX(Acd_Fpd)) AS Fch_Ini,CURDATE() AS Fec_Sis
                        FROM activo 
                        INNER JOIN activo_compra ON activo.Act_Cod=activo_compra.Act_Cod
                        INNER JOIN producto ON activo_compra.Pro_Cod=producto.Pro_Cod
                        INNER JOIN tipo_activo ON tipo_activo.Tia_Cod=activo.Tia_Cod
                        INNER JOIN activo_ccontable ON activo_ccontable.Act_Cod=activo.Act_Cod
                        INNER JOIN det_plan AS dp ON activo_ccontable.Pld_Cod=dp.Pld_Cod
                        INNER JOIN produ_plan ON producto.Pro_Cod=produ_plan.Pro_Cod
                        INNER JOIN det_plan AS dp2 ON produ_plan.Pld_Cod=dp2.Pld_Cod
                        LEFT JOIN activo_deprecia ON activo_deprecia.Act_Cod=activo.Act_Cod  
                        LEFT JOIN comprobantes ON comprobantes.Com_Cod=activo_deprecia.Com_Cod
                        WHERE Act_Est='A' AND activo_ccontable.Acc_Tip='DE' $condicion GROUP BY Act_Cod";
                //echo $sql;
        break;
        //Select para comprobar si el activo ya ha sido depreciado
        case 3:
		$sql = "SELECT Act_Cod,Com_Cod,MAX(Acd_Fpd)AS Acd_Fpd 
                        FROM activo_deprecia WHERE Act_Cod='$Par_Sql[0]' AND Acd_Est='A' GROUP BY Act_Cod";
        break;
        //Select para obtener el Prv_Cod que servira para insertar un registro en la tabla comprobantes
        case 4:
		$sql = "SELECT proveedore.Prv_Cod 
                        FROM proveedore,compra_prov 
                        WHERE Emp_Cod='$Par_Sql[0]' AND proveedore.Prv_Cod=compra_prov.Prv_Cod";
        break;
        //Insert a efectuarse en la tabla comprobantes
        case 5:
		$sql = "INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Usu_Cod,Com_Num,Com_Fec,Com_Val,Tia_Cod,Com_Gen) 
                        VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','A')";
        break;
        //Insert a efectuarse en la tabla activo_deprecia
        case 6:
		$sql = "INSERT INTO activo_deprecia(Com_Cod,Act_Cod,Acd_Fpd,Acd_Tip) 
                        VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]')";
        break;
        //Select para obtener las cuentas de depreciaci�n y depreciaci�n acumulada correspondiente a un activo
        case 7:
		$sql = "SELECT activo.Tia_Cod,Pld_Cod,Acc_Tip 
                        FROM activo
						INNER JOIN activo_ccontable ON  activo.Act_Cod=activo_ccontable.Act_Cod 
                        WHERE activo.Act_Cod='$Par_Sql[0]' AND activo.Act_Est='A'";
        break;
        //Insert para efectuar en la tabla asiento
        case 8:
		$sql = "INSERT INTO asientos(Com_Cod,Asi_Deh,Asi_Val,Pld_Cod) VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]')";
	break;
        //Update para actualizar el valor de la sumatoria de la depreciacion mensual en la tabla comprobantes
        case 9:
		$sql = "UPDATE comprobantes SET Com_Val='$Par_Sql[1]' WHERE Com_Cod='$Par_Sql[0]'";
        break;
        //Select para listar los tipos de asiento
        case 10:
		$sql = "SELECT Tia_Cod,CONCAT(Tia_Abr,' - ',Tia_Des) AS Tia_Des,Tia_Abr FROM tipo_asien WHERE Tia_Ini='D' AND Tia_Est='A'";
	break;
        //Select para cargar la configuraci�n bajo la cual se registraran los activos
        case 11:
                $sql = "SELECT Cfg_Cod,Cfg_Ddp,Cfg_Por 
                        FROM config_activo 
                        WHERE Suc_Cod='$Par_Sql[0]' AND Cfg_Est='A'"; 
        break;
        //Select para comprobar si el activo ya ha sido depreciado
//        case 12:
//		$sql = "SELECT Acd_Fpd 
//                        FROM activo_deprecia WHERE Act_Cod='$Par_Sql[0]' AND Acd_Est='A'";
//        break;
        case 12:
                $sql = "SELECT activo_deprecia.Com_Cod,Act_Cod,Acd_Fpd,comprobantes.Pec_Cod
                        FROM activo_deprecia
                        LEFT JOIN comprobantes ON comprobantes.Com_Cod=activo_deprecia.Com_Cod
                        WHERE Act_Cod='$Par_Sql[0]' AND Acd_Est='A'";
            //echo $sql;
        break;
    
        /**INICIO DE SQL's PARA EL ARCHIVO act_con_depreciacion_1.0.php**/
        //Select para obtener el historial de depreciacion de un activo informacion extraida de la tabla activo_deprecia
        case 13:
		if($Par_Sql['op_opciones']=="d") {$search="Act_Des LIKE '%$Par_Sql[search]%'";}
                else { 
                    if($Par_Sql['op_opciones']=="c"){
                        $search="activo.Act_Cod LIKE '%$Par_Sql[search]%'";
                    }else{
                        $search="Act_Fec LIKE '%$Par_Sql[search]%'";
                    }
                }
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY activo.Act_Cod $Par_Sql[limits]";
                        $campos="activo.Act_Cod,Act_Des,Act_Fec";$Par_Sql["group"]="GROUP BY activo.Act_Cod";
                }
                else{$campos="COUNT(distinct(activo.Act_Cod)) as total";$Par_Sql["limits"]="";}
                $sql="SELECT $campos FROM activo 
                      INNER JOIN activo_deprecia ON activo.Act_Cod=activo_deprecia.Act_Cod
                      WHERE $search AND Act_Est='A' AND Suc_Cod= $Par_Sql[Suc_Cod] $Par_Sql[group] $Par_Sql[limits]";
        break;
        //Permite listar el plan de cuentas
        case 14:
                if($Par_Sql[3]=="d") {$search="det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";}
                else {$search="det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";}
                if($Par_Sql[4]==""){$campos="COUNT(det_plan.Pld_Cod) as total";}
                else{
                    $Par_Sql[4]="ORDER BY det_plan.Pld_Cod ".$Par_Sql[4];
                    $campos="det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
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
        break;
        //Permite listar los registros de la tabla activo_deprecia
        case 15:
                $sql = "SELECT CONCAT(activo_deprecia.Com_Cod,'*',comprobantes.Tia_Cod) AS Com_Cod,activo.Act_Cod,Acd_Fpd
                        FROM activo,activo_deprecia,comprobantes
                        WHERE activo.Suc_Cod='$Par_Sql[0]' AND activo.Act_Cod=activo_deprecia.Act_Cod AND activo_deprecia.Com_Cod=comprobantes.Com_Cod AND activo.Act_Est='A'";
        break;
    }
    return $sql;
}
