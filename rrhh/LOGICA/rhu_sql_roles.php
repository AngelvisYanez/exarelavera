<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizacion:	2016-06-28
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package inv.LOGICA
 */

function sentencias_rol($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 0:
            $sql = "";
            //echo $sql.'<br/>';
            break;
        case 1:
            $sql = "INSERT INTO map_system(Map_Ide,Map_Des,Map_Obs,Emp_Cod,Map_Tip)VALUES($Par_Sql[0],'$Par_Sql[1]','$Par_Sql[2]',$_SESSION[Ses_Emp_Cod],'$Par_Sql[4]')";
            //echo $sql.'<br/>';
            break;
        case 2:
            $sql = "INSERT INTO campo_rol(Map_Cod,Cam_Des,Cam_Obs,Cam_Por,Cam_Vis,Cam_Forrrrr,Cam_Req,Cam_Cal,Cam_Tip,Cam_Ord,Cam_Dec,Mro_Cod,Cam_Var,Cam_Sum)
                    VALUES($Par_Sql[Map_Cod],'$Par_Sql[Cam_Des]','$Par_Sql[Cam_Obs]'," . (empty($Par_Sql['Cam_Por']) ? 'NULL' : $Par_Sql['Cam_Por']) . ",'" . ($Par_Sql['Cam_Vis'] == 'S' ? 'S' : 'N') . "','$Par_Sql[Cam_Forrr]','" . ($Par_Sql['Cam_Req'] == 'S' ? 'S' : 'N') . "','" . ($Par_Sql['Cam_Cal'] == 'S' ? 'S' : 'N') . "','$Par_Sql[Cam_Tip]',$Par_Sql[Cam_Ord],'$Par_Sql[Cam_Dec]'," . (empty($Par_Sql['Mro_Cod']) ? 'NULL' : $Par_Sql['Mro_Cod']) . ",'$Par_Sql[Cam_Var]','$Par_Sql[Cam_Sum]');";
            //echo $sql.'<br/>';
            break;
        case 3:
            $sql = "INSERT INTO oper_grupo(Cam_Cod,Ogr_Opr,Ogr_Rec,Ogr_Ord)
                    VALUES($Par_Sql[Cam_Cod],'$Par_Sql[Ogr_Opr]',$Par_Sql[Ogr_Rec],$Par_Sql[Ogr_Ord]);";
            //echo $sql.'<br/>';
            break;
        case 4:
            $sql = "INSERT INTO oper_item(Ogr_Cod,Cam_Cod,Oit_Tip,Oit_Val,Oit_Var,Oit_Ord)
                    VALUES($Par_Sql[Ogr_Cod]," . (empty($Par_Sql['Cam_Cod']) ? 'NULL' : $Par_Sql['Cam_Cod']) . ",'" . $Par_Sql['Oit_Tip'][0] . "',$Par_Sql[Oit_Val]," . (empty($Par_Sql['Oit_Var']) ? 'NULL' : "'$Par_Sql[Oit_Var]'") . ",$Par_Sql[Oit_Ord]);";
            //echo $sql.'<br/>';
            break;
        case 5:
            $sql = "SELECT * FROM oper_grupo WHERE " . (!empty($Par_Sql['Cam_Cod']) ? "Cam_Cod=$Par_Sql[Cam_Cod]" : '') . (!empty($Par_Sql['Ogr_Cod']) ? "Ogr_Rec=$Par_Sql[Ogr_Cod] AND Ogr_Ord=$Par_Sql[Ogr_Ord]" : '');
            //echo $sql.'<br/>';
            break;
        case 6:
            $sql = "SELECT * FROM oper_item WHERE Ogr_Cod=$Par_Sql[Ogr_Cod] AND Oit_Ord=$Par_Sql[Oit_Ord];";
            //echo $sql.'<br/>';
            break;
        case 7:
            $type = isset($Par_Sql['type']) ? sql_conjunction($Par_Sql['type'], 'Cam_Tip=') : '';
            $var = isset($Par_Sql['var']) ? sql_conjunction($Par_Sql['var'], 'Cam_Var=') : '';
            $sum = isset($Par_Sql['sum']) ? sql_conjunction($Par_Sql['sum'], 'Cam_Sum=') : '';
            $sql = "SELECT * FROM campo_rol WHERE Map_Cod=$Par_Sql[Map_Cod] $type $var $sum ORDER BY Cam_Tip DESC,Cam_Ord ASC;";
            //echo $sql.'<br/>';
            break;
        case 77:
            $type = isset($Par_Sql['type']) ? sql_conjunction($Par_Sql['type'], 'Cam_Tip=') : '';
            $var = isset($Par_Sql['var']) ? sql_conjunction($Par_Sql['var'], 'Cam_Var=') : '';
            $sum = isset($Par_Sql['sum']) ? sql_conjunction($Par_Sql['sum'], 'Cam_Sum=') : '';

            //Verifica si imprime todos los valores 
            $printerAll =  isset($Par_Sql['imprimirAll']) ? filter_var($Par_Sql['imprimirAll'], FILTER_VALIDATE_BOOLEAN) : false;
            if ($printerAll) {
                $fragsql = " HAVING Sum(Rol_Val) > 0";
            } else {
                $fragsql = " ";
            }
            //Ocultar columna horas suplementarias y extraordinarias
            $printHS =  isset($Par_Sql['check_col_hs']) ? filter_var($Par_Sql['check_col_hs'], FILTER_VALIDATE_BOOLEAN) : false;
            if ($printHS) {
                //$fragsqlHS = ' AND Cam_Dec!="Can.Hrs.Suple" ';
                // $fragsqlHS = ' AND Cam_Dec!="Can.Hrs.Suple"  AND Cam_Dec!="Can.Hrs.Extrao."';
                $fragsqlHS = ' AND Cam_Var!="CANT_HREX"  AND Cam_Var!="CANT_HRSUP"';
            } else {
                $fragsqlHS = " ";
            }
             //Ocultar columnas de jornada
            $printJorn =  isset($Par_Sql['check_col_jorn']) ? filter_var($Par_Sql['check_col_jorn'], FILTER_VALIDATE_BOOLEAN) : false;
            if ($printJorn) {
                $fragsqlJorn = ' AND Cam_Var!="VIA_JORN" AND Cam_Var!="CANT_JORN" AND Cam_Var!="VAL_VALOR" AND Cam_Var!="LAB_JORN" AND Cam_Var!="OTR_IN_JOR"';
            } else {
                $fragsqlJorn = " ";
            }
            $sql = "SELECT  campo_rol.* FROM campo_rol INNER JOIN det_rpagos ON campo_rol.Cam_Cod=det_rpagos.Cam_Cod 
                    WHERE Map_Cod=$Par_Sql[Map_Cod]   $fragsqlHS  $fragsqlJorn  AND Rol_Cod = $Par_Sql[Rol_Cod] $type $var $sum
                    GROUP BY Cam_Cod
                     $fragsql  /*HAVING Sum(Rol_Val) > 0*/
                    ORDER BY Cam_Tip DESC,Cam_Ord ASC;";
            break;
        case 8:
            $sql = "SELECT * FROM map_system WHERE Map_Cod=$Par_Sql[Map_Cod];";
            //echo $sql.'<br/>';
            break;
        case 9:
            $where = isset($Par_Sql['Con_Cod']) ? " contratos_lab.Con_Cod=$Par_Sql[Con_Cod] " :
                " Per_Est='A' AND Con_Est='A' " . (isset($Par_Sql['Rol_Fei']) ? " AND ((   (contratos_lab.Con_Fin > '$Par_Sql[Rol_Fei]') AND  (contratos_lab.Con_Ini <= '$Par_Sql[Rol_Fei]' OR contratos_lab.Con_Ini BETWEEN '$Par_Sql[Rol_Fei]' AND '$Par_Sql[Rol_Fef]' )  ) )" : '') . (!empty($Par_Sql['Are_Cod']) ? " AND departamen.Are_Cod='$Par_Sql[Are_Cod]' " : '');
            $sql = "SELECT /*sucursal.Emp_Cod,personal.Suc_Cod*/ personal.Emp_Cod,Prs_Ced,Prs_Ape,Prs_Nom,CONCAT(Prs_Ape,' ',Prs_Nom)AS Personal,Tic_Des,personal.Per_Cod,contratos_lab.Con_Cod,Sue_Val AS sueldo,IF(Sue_Va1 IS NULL OR Sue_Va1=0,Sue_Val,Sue_Va1)AS sueldo_neto,
                IF(Afi_Dte='S',1,0)AS deci_terc_acum, IF(Afi_Dcu='S',1,0)AS deci_cuar_acum, IF(Afi_Fnd='S',1,0)AS fond_reser_acum, Afi_Fei, Afi_Fef, IF(Afi_Fei IS NULL,0,1)AS es_afiliado, Ded_Hrs, IF(Afi_Due='S',1,0)AS es_empleador, Sue_Bas, IF(Ded_Hrs<8,1,0)AS Con_Tpa /*Campo Tiempo parcial*/,IF(Ded_Hrs<8,1,0)AS medio_tiempo, IF(Ded_Hrs=0,1,0)AS tiempo_parcial,Sut_Cod,Con_Ini
                FROM personal
                INNER JOIN persona ON persona.Prs_Cod=personal.Prs_Cod
                INNER JOIN contratos_lab ON contratos_lab.Per_Cod=personal.Per_Cod
                INNER JOIN dedica_lab ON dedica_lab.Ded_Cod=contratos_lab.ded_Cod
                INNER JOIN tiposcargo ON contratos_lab.Tic_Cod=tiposcargo.Tic_Cod
                INNER JOIN departamen ON departamen.Dep_Cod=tiposcargo.Dep_Cod
                /*INNER JOIN sucursal ON sucursal.Suc_Cod=personal.Suc_Cod */
                INNER JOIN sueldos ON sueldos.Sue_Cod=(SELECT Sue_Cod FROM sueldos WHERE sueldos.Con_Cod=contratos_lab.Con_Cod AND Sue_Est='A' LIMIT 1 )
                LEFT JOIN afiliacion ON contratos_lab.Con_Cod=afiliacion.Con_Cod AND Afi_Est='A'
                WHERE $where
                ORDER BY Personal;";
            //echo $sql.'<br/>';
            break;
        case 10:
            $sql = "SELECT * FROM map_system WHERE Map_Est='A' AND Emp_Cod='$Par_Sql[0]';";
            //echo $sql . '<br/>';
            break;
        case 72:
            // Listar plantillas de todas las empresas (para cargar como plantilla base)
            // Buscar plantillas que tengan campos de rol (existe campo_rol con ese Map_Cod)
            $sql = "SELECT DISTINCT MS.*, EMP.Emp_Nom FROM map_system MS 
                    INNER JOIN empresas EMP ON MS.Emp_Cod = EMP.Emp_Cod 
                    INNER JOIN campo_rol CR ON MS.Map_Cod = CR.Map_Cod
                    WHERE MS.Map_Est='A' 
                    ORDER BY EMP.Emp_Nom, MS.Map_Des;";
            //echo $sql . '<br/>';
            break;
        case 11:
            $sql = "SELECT * FROM areas_rrhh WHERE Are_Est='A' AND Emp_Cod=$Par_Sql[0]";
            //echo $sql.'<br/>';
            break;
        case 12:
            $sql = "SELECT perio_cont.*,YEAR(Pec_Fei)AS Periodo FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Pec_Est='A' AND Emp_Cod='$Par_Sql[0]' ORDER BY Periodo DESC";
            //echo $sql.'<br/>';
            break;
        case 13:
            $sql = "SELECT Rol_Cod,Rol_Num,Rol_Fei,Rol_Fef FROM rol_pagos 
                    WHERE Pec_Cod=$Par_Sql[Pec_Cod] AND Are_Cod=$Par_Sql[Are_Cod]  AND Rol_Tip='$Par_Sql[Rol_Tip]' AND Rol_Est='A'
                    ORDER BY Rol_Num DESC";
            //echo $sql.'<br/>';
            break;
        case 14:
            if (empty($Par_Sql['Rol_Cod']))
                $sql = "INSERT INTO rol_pagos(Are_Cod,Pec_Cod,Rol_Tip,Rol_Num,Rol_Fei,Rol_Fef,Rol_Con,Reb_Cod,Usu_Cod)
                    VALUES($Par_Sql[Are_Cod],$Par_Sql[Pec_Cod],'$Par_Sql[Rol_Tip]',$Par_Sql[Rol_Num]," . (empty($Par_Sql['Rol_Fei']) ? 'NULL' : "'$Par_Sql[Rol_Fei]'") . "," . (empty($Par_Sql['Rol_Fef']) ? 'NULL' : "'$Par_Sql[Rol_Fef]'") . ",'$Par_Sql[Rol_Con]'," . (empty($Par_Sql['Reb_Cod']) ? 'NULL' : $Par_Sql['Reb_Cod']) . ",$_SESSION[Ses_Usu_Cod]);";
            else
                $sql = "UPDATE rol_pagos SET Rol_Con='$Par_Sql[Rol_Con]', Usu_Cod=$_SESSION[Ses_Usu_Cod] WHERE Rol_Cod='$Par_Sql[Rol_Cod]';";
            //echo $sql.'<br/>';
            // //ChromePhp::log($sql);
            break;
        case 15:
            if (empty($Par_Sql['edit']))
                $sql = "INSERT INTO det_rpagos(Rol_Cod,Con_Cod,Cam_Cod,Rol_Val) VALUES($Par_Sql[Rol_Cod],$Par_Sql[Con_Cod],$Par_Sql[Cam_Cod],$Par_Sql[Rol_Val]);";
            else
                $sql = "UPDATE det_rpagos SET Rol_Val='$Par_Sql[Rol_Val]' WHERE Rol_Cod=$Par_Sql[Rol_Cod] AND Con_Cod=$Par_Sql[Con_Cod] AND Cam_Cod=$Par_Sql[Cam_Cod];";
            //echo $sql.'<br/>';
            break;
        case 16:
            $Pec = '';
            if (isset($Par_Sql['Pec_Cod'])) {
                if ($Par_Sql['Pec_Cod'] == 'RANGE') $Pec = "AND rol_pagos.Rol_Fei BETWEEN '$Par_Sql[ini]' AND '$Par_Sql[fin]'"; //pendiente
                else if (!empty($Par_Sql['Pec_Cod']) && $Par_Sql['Pec_Cod'] != 'ALL')  $Pec = "AND rol_pagos.Pec_Cod=$Par_Sql[Pec_Cod] ";
            }
            $sql = "SELECT DISTINCT rol_pagos.*,map_system.Map_Cod,Map_Des,Are_Des,YEAR(Rol_Fef)AS Anio/*,Com_Cod*/,Usu_Cod, compr_rol.Com_Cod,
                    areas_rrhh.Are_Des
                    FROM rol_pagos
                    INNER JOIN det_rpagos ON rol_pagos.Rol_Cod=det_rpagos.Rol_Cod
                    INNER JOIN campo_rol ON campo_rol.Cam_Cod=det_rpagos.Cam_Cod
                    INNER JOIN map_system ON campo_rol.Map_Cod=map_system.Map_Cod
                    INNER JOIN areas_rrhh ON rol_pagos.Are_Cod=areas_rrhh.Are_Cod
                    LEFT JOIN compr_rol ON rol_pagos.Rol_Cod=compr_rol.Rol_Cod
                    WHERE map_system.Emp_Cod='$_SESSION[Ses_Emp_Cod]' " . (isset($Par_Sql['Rol_Cod']) && !empty($Par_Sql['Rol_Cod']) ? "AND rol_pagos.Rol_Cod=$Par_Sql[Rol_Cod] " : '') . "
                          " . (isset($Par_Sql['Are_Cod']) && !empty($Par_Sql['Are_Cod']) ? "AND areas_rrhh.Are_Cod=$Par_Sql[Are_Cod] " : '') . (isset($Par_Sql['Map_Cod']) && !empty($Par_Sql['Map_Cod']) ? "AND map_system.Map_Cod=$Par_Sql[Map_Cod] " : '') . "
                          $Pec
                    GROUP BY rol_pagos.Rol_Cod ORDER BY Anio,Are_Des,Rol_Num DESC,Rol_Est";
            //echo $sql.'<br/>';
            break;
        case 17:
            $sql = "SELECT det_rpagos.*,Cam_Var FROM det_rpagos
                    INNER JOIN campo_rol ON campo_rol.Cam_Cod=det_rpagos.Cam_Cod
                    WHERE (Cam_Vis='S' OR Cam_Tip='D' OR Cam_Tip='P' OR Cam_Var = 'labores_ingreso') " . (isset($Par_Sql['totales']) ? " AND Cam_Var='total_rol' " : '') . " AND Rol_Cod=$Par_Sql[Rol_Cod] " . (isset($Par_Sql['Con_Cod']) ? "AND Con_Cod=$Par_Sql[Con_Cod]" : '');
            //echo $sql.'<br/>';
            break;
        case 18:
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
        case 19:
            $sql = "SELECT * FROM rol_plan 
                    INNER JOIN det_plan ON rol_plan.Pld_Cod=det_plan.Pld_Cod
                    WHERE Are_Cod=$Par_Sql[1] AND Cam_Cod=$Par_Sql[0] AND Pla_Cod=$Par_Sql[2] " . (empty($Par_Sql[3]) ? '' : "AND Rpl_Tip='$Par_Sql[3]' ") . ' ORDER BY Rpl_Tip ;';
            //echo $sql.'<br/>';
            break;
        case 20:
            $sql = "INSERT INTO rol_plan(Cam_Cod,Are_Cod,Pld_Cod,Rpl_Tip) VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],'$Par_Sql[3]');";
            //echo $sql.'<br/>';
            break;
        case 21:
            $sql = "DELETE FROM rol_plan WHERE Cam_Cod=$Par_Sql[0] AND Are_Cod=$Par_Sql[1] AND Pld_Cod=$Par_Sql[2] AND Rpl_Tip='$Par_Sql[3]';";
            //echo $sql.'<br/>';
            break;
        case 22:
            $sql = "SELECT Cam_Cod,Are_Cod,det_plan.* FROM rol_plan 
                    INNER JOIN det_plan ON rol_plan.Pld_Cod=det_plan.Pld_Cod
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                    INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                    WHERE Are_Cod=$Par_Sql[1] AND Cam_Cod=$Par_Sql[0] AND Pec_Cod=$Par_Sql[2] AND Rpl_Tip='" . (empty($Par_Sql[3]) ? 'G' : $Par_Sql[3]) . "'";
            //ChromePhp::log($sql);
            //echo $sql.'<br/>';
            break;
        case 23:
            $sql = "SELECT * FROM confi_fact WHERE Emp_Cod=$Par_Sql[0]";
            //echo $sql;
            break;
        case 24:

          
            if (empty($Par_Sql[9]))
                $sql = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]'"; //Antes Com_Tip
            else
                $sql = "UPDATE comprobantes SET Com_Con=UPPER('$Par_Sql[4]'), Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]' WHERE Com_Cod=$Par_Sql[9] "; // le quite /*Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Tia_Cod='$Par_Sql[5]',*/
            //echo $sql."<br>";       
            break;
        case 25:
            /* Relaciona una compra y un comprobante para saber que es automatico */
            $sql = "INSERT INTO compr_rol (Com_Cod, Rol_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
            //echo $sql."<br>";            
            break;
        case 26:
            $sql = "SELECT * FROM tipo_asien WHERE Tia_Ini='$Par_Sql[0]' AND Tia_Abr='$Par_Sql[1]'";
            //echo $sql;
            break;
        case 27:
            $sql = "INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5];";
            //echo $sql."<br>";
            break;
        case 28:
            $sql = "DELETE FROM asientos WHERE Com_Cod='$Par_Sql[0]';";
            //echo $sql."<br>";
            break;
        case 29:
            $sql = "SELECT compr_rol.Com_Cod FROM compr_rol INNER JOIN comprobantes ON comprobantes.Com_Cod=compr_rol.Com_Cod INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod  WHERE Rol_Cod='$Par_Sql[0]' AND Tia_Abr='$Par_Sql[1]';";
            //echo $sql."<br>";
            break;
        case 30:
            $sql = "SELECT * FROM rol_defaults WHERE ('$Par_Sql[0]' BETWEEN Rde_Fei AND Rde_Fef) OR (Rde_Fei<='$Par_Sql[0]' AND Rde_Fef IS NULL);";
            //echo $sql."<br>";
            break;
        case 31:
            $sql = "SELECT DISTINCT antici_rol.*,personal.Emp_Cod FROM det_an_rol
                INNER JOIN antici_rol ON antici_rol.Ant_Cod=det_an_rol.Ant_Cod
                INNER JOIN contratos_lab ON contratos_lab.Con_Cod=antici_rol.Con_Cod
                INNER JOIN personal ON contratos_lab.Per_Cod=personal.Per_Cod
                /*INNER JOIN sucursal ON sucursal.Suc_Cod=personal.Suc_Cod*/
                WHERE Ant_Est='A' AND Ant_Tip='" . (!isset($Par_Sql[4]) || empty($Par_Sql[4]) ? 'A' : $Par_Sql[4]) . "' AND personal.Emp_Cod='$Par_Sql[0]' AND Ant_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' " . (isset($Par_Sql[3]) && !empty($Par_Sql[3]) ? "AND det_an_rol.Rol_Cod IS NULL" : '') . " ;";
            //echo $sql."<br>";
            break;
        case 32:
            $sql = "INSERT INTO antici_rol(Con_Cod,Ant_Fec,Ant_Obs,Ant_Val,Ant_Tip) VALUES($Par_Sql[Con_Cod],'$Par_Sql[Ant_Fec]','$Par_Sql[Ant_Obs]',$Par_Sql[Ant_Val],'" . (isset($Par_Sql['Ant_Tip']) ? $Par_Sql['Ant_Tip'] : 'A') . "');";
            //echo $sql."<br>";
            break;
        case 33:
            /* Relaciona una compra y un comprobante para saber que es automatico */
            $sql = "INSERT INTO compr_arol (Com_Cod, Ant_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
            //echo $sql."<br>";            
            break;
        case 34:
            /* Relaciona una compra y un comprobante para saber que es automatico */
            $sql = "SELECT * FROM tipos_pago WHERE For_Cod=1 ORDER BY Pag_Cod;";
            //echo $sql."<br>";            
            break;
        case 35: // usado
            $sql = "SELECT DISTINCT banco.*, det_plan.Pld_Cod, Pld_Des FROM banco 
                    INNER JOIN det_plan ON det_plan.Pld_Cod=banco.Pld_Cod
                    INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                    WHERE Ban_Cue!=0 AND Ban_Cue!='' AND Ban_Est='A' AND Emp_Cod='$Par_Sql[0]' AND plan_cuenta.Pla_Cod = $Par_Sql[1];";
            //echo $sql.'<br/>';
            break;
        case 36: // usado
            $sql = "SELECT perio_cont.* FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Emp_Cod=$Par_Sql[0] AND '$Par_Sql[1]' BETWEEN Pec_Fei AND Pec_Fef";
            //echo $sql;
            break;
        case 37: // usado
            $sql = "SELECT DISTINCT banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des FROM banco, det_plan, pago_plan, plan_cuenta
			 WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND banco.Ban_Cod = pago_plan.Ban_Cod AND det_plan.Pla_Cod = plan_cuenta.Pla_Cod AND pago_plan.Pag_Cod = $Par_Sql[1] AND plan_cuenta.Pla_Cod = $Par_Sql[0] ORDER BY Pld_Cdc, Pld_Des";
            //echo $sql;
            break;
        case 38:
            $sql = "INSERT INTO det_an_rol(Ant_Int,Ant_Cod,Pag_Cod,Ant_Val,Rol_Cod,Asi_Cod)VALUES($Par_Sql[Ant_Int],$Par_Sql[Ant_Cod],$Par_Sql[Pag_Cod],$Par_Sql[Ant_Val]," . (empty($Par_Sql['Rol_Cod']) ? 'NULL' : $Par_Sql['Rol_Cod']) . "," . (empty($Par_Sql['Asi_Cod']) ? 'NULL' : $Par_Sql['Asi_Cod']) . ");";
            //echo $sql;
            break;
        case 39:
            $sql = "SELECT COALESCE(MAX(Che_Num),0)+1 as Che_Num FROM cheques WHERE Ban_Cod=$Par_Sql[0];";
            break;
        case 40:
            $sql = "SELECT COUNT(Che_Cod) AS conteo FROM cheques WHERE Ban_Cod=$Par_Sql[0] AND Che_Num='$Par_Sql[1]' ";
            //echo $ins_asie."<br>";
            break;
        case 41:
            $sql = "INSERT INTO cheques SET Prv_Cod=$Par_Sql[0], Ban_Cod=$Par_Sql[1], Asi_Cod=$Par_Sql[2], Che_Num='$Par_Sql[3]'," .
                " Che_Val=$Par_Sql[5], Che_Obs=UPPER('$Par_Sql[6]'), Che_Fec='$Par_Sql[7]', Che_Cod = $Par_Sql[8], Che_Ben=UPPER(TRIM('$Par_Sql[9]')) ;";
            //echo $sql;
            break;
        case 42:
            $sql = "SELECT antici_rol.*,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom)AS Personal FROM antici_rol
                INNER JOIN contratos_lab ON  contratos_lab.Con_Cod=antici_rol.Con_Cod
                INNER JOIN personal ON  contratos_lab.Per_Cod=personal.Per_Cod
                INNER JOIN persona ON  persona.Prs_Cod=personal.Prs_Cod
                WHERE Ant_Cod=$Par_Sql[0]";
            //echo $sql;
            break;
        case 43:
            $sql = "SELECT DISTINCT det_an_rol.*,Pag_Des,Pld_Des,cheques.* FROM det_an_rol
                    INNER JOIN tipos_pago ON tipos_pago.Pag_Cod=det_an_rol.Pag_Cod
                    LEFT JOIN asientos ON det_an_rol.Asi_Cod=asientos.Asi_Cod
                    LEFT JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
                    LEFT JOIN cheques ON cheques.Asi_Cod=asientos.Asi_Cod
                    WHERE det_an_rol.Ant_Cod=$Par_Sql[0]";
            //echo $sql;
            break;
        case 44:
            $sql = "UPDATE $Par_Sql[table_rel] SET Rol_Cod=$Par_Sql[Rol_Cod] WHERE $Par_Sql[id_field]=$Par_Sql[id];";
            //echo $sql;
            break;
        case 45:
            $sql = "SELECT DISTINCT antici_rol.*,personal.Emp_Cod FROM det_an_rol
                INNER JOIN antici_rol ON antici_rol.Ant_Cod=det_an_rol.Ant_Cod
                INNER JOIN contratos_lab ON contratos_lab.Con_Cod=antici_rol.Con_Cod
                INNER JOIN personal ON contratos_lab.Per_Cod=personal.Per_Cod                
                WHERE Ant_Est='A' AND Ant_Tip='B' AND antici_rol.Con_Cod='$Par_Sql[0]' AND Rol_Cod='$Par_Sql[1]'
                ";
            //echo $sql;
            break;
        case 46:
            $sql = "SELECT * FROM campo_rol WHERE Map_Cod=$Par_Sql[0] AND Cam_Var='$Par_Sql[1]'";
            //echo $sql;
            break;
        case 47:
            $sql = "SELECT * FROM sueldos_unificados WHERE ('$Par_Sql[0]' BETWEEN Suu_Fei AND Suu_Fef) OR (Suu_Fei<='$Par_Sql[0]' AND Suu_Fef IS NULL);";
            //echo $sql."<br>";
            break;
        case 48:
            $sql = "SELECT Usu_Cod,CONCAT( Prs_Ape,' ',Prs_Nom)AS Usuario FROM usuarios INNER JOIN persona ON persona.Prs_Cod=usuarios.Prs_Cod WHERE Usu_Cod=$Par_Sql[0]";
            //echo $sql;
            break;
        case 50:
            $sql = "DELETE FROM compr_rol WHERE Com_Cod='$Par_Sql[0]';";
            //echo $sql;
            break;
        case 51:
            $sql = "UPDATE comprobantes SET Com_Est='I' WHERE Com_Cod='$Par_Sql[0]';";
            //echo $sql;
            break;
        case 52:
            $sql = "SELECT DISTINCT rol_pagos.rol_cod, prs_ced as Cedula, CONCAT(prs_ape,' ',prs_nom) as Personal, Rol_Fei, Rol_Fef, sue_val AS Sueldo FROM rol_pagos
                INNER JOIN det_rpagos ON rol_pagos.Rol_Cod = det_rpagos.Rol_Cod
                INNER JOIN contratos_lab ON det_rpagos.Con_Cod = contratos_lab.Con_Cod
                INNER JOIN sueldos ON contratos_lab.Con_Cod = sueldos.Con_Cod
                INNER JOIN personal ON contratos_lab.Per_Cod = personal.Per_Cod
                INNER JOIN persona ON personal.Prs_Cod = persona.Prs_Cod
                INNER JOIN empresas ON personal.Emp_Cod = empresas.Emp_Cod
                WHERE Pec_Cod = $Par_Sql[Pec_Cod] AND rol_pagos.Rol_Est = 'A' AND empresas.Emp_Cod = $_SESSION[Ses_Emp_Cod] ORDER BY Prs_Ape, Prs_Nom, Rol_Fei";
            break;

        case 53:
            $sql = "SELECT ROUND(sum(det.Det_Val * det.Det_Can),2) as totalSemanal
                    FROM actividad_labor  ac
                    INNER JOIN det_actividad_labor det ON ac.Act_Cod = det.Act_Cod
                    WHERE ac.Act_Sem = $Par_Sql[Semana] 
                    AND ac.Pec_Cod = $Par_Sql[Periodo] 
                    AND det.Per_Cod = $Par_Sql[Personal] 
                    GROUP BY ac.Act_Sem";
            break;

        case 54:
            $sql = "SELECT count(cam_var) as labores 
                    FROM campo_rol 
                    WHERE cam_var = 'labores_total'
                    AND map_cod =$Par_Sql[Plantilla]";
            break;

            //Obtener listado de bancos externos
        case 55:
            $sql = "SELECT * FROM bancos WHERE Bak_Est = 'A'";
            //echo $sql;
            break;

            //Obtener listado de forma de pago contrato
        case 56:
            $sql = "SELECT * FROM pago_contrato WHERE Con_Cod = $Par_Sql[0] And Pag_Con_Est = 'A'";
            //echo $sql;
            break;

        case 65:
            $AreaCodigo = '';
            if ($Par_Sql[Are_Cod] == 1) {
                $AreaCodigo = 'RP.ARE_COD';
            } else {
                $AreaCodigo = $Par_Sql[Are_Cod];
            }
            $fechaini = "RP.ROL_FEI >= '" . $Par_Sql[Rol_Fei][0] . "'";
            $fechafin = "RP.ROL_FEF <= '" . $Par_Sql[Rol_FefF] . "'";
            $periodo = "'" . $Par_Sql[Month] . "-01'";
            $sql = "SELECT EMP.EMP_RUC, '0001' SUCURSAL_IESS, YEAR(PC.PEC_FEI) ANIO, LPAD(MONTH($periodo),2,'0') PERIODO, 'INS' TIPO, PER.PRS_CED, SUM(DP.ROL_VAL) ROL_VAL, 'O' O
                FROM map_system MS
                JOIN empresas EMP ON EMP.EMP_COD = MS.EMP_COD
                JOIN campo_rol CR ON CR.MAP_COD = MS.MAP_COD 
                JOIN det_rpagos DP ON DP.CAM_COD = CR.CAM_COD
                JOIN rol_pagos RP ON RP.ROL_COD = DP.ROL_COD
                JOIN contratos_lab CL ON CL.CON_COD = DP.CON_COD
                JOIN personal PR ON PR.PER_COD = CL.PER_COD
                JOIN persona PER ON PER.PRS_COD = PR.PRS_COD
                JOIN perio_cont PC ON PC.PEC_COD = RP.PEC_COD
                WHERE MS.MAP_COD = $Par_Sql[Map_Cod]
                AND $fechaini
                AND $fechafin
                and RP.Rol_Est = 'A'
                AND RP.ARE_COD = $AreaCodigo
                AND RP.PEC_COD = $Par_Sql[Pec_Cod]
                AND CR.CAM_DES = 'EXTRAS APORTE'
				AND DP.ROL_VAL > 0
                GROUP BY PRS_CED";
            //ChromePhp::log($sql);
            break;
        case 66:
            $PeriodoIni = $Par_Sql[Rol_Num][0];
            $PeriodoFin = $Par_Sql[Rol_Num][1];
            $sql = "INSERT INTO reporte_aporextra(Pec_Cod,Reporte_Fei,Reporte_Fef,Emp_Cod,Usu_Cod)
                    VALUES($Par_Sql[Pec_Cod],$PeriodoIni,$PeriodoFin,$_SESSION[Ses_Emp_Cod],$_SESSION[Ses_Usu_Cod]);";
            break;
        case 67:
            $inicio = $Par_Sql[Rol_Num][0] == '' ? 0 : $Par_Sql[Rol_Num][0];
            $fin = $Par_Sql[Rol_Num][1] == '' ? 0 : $Par_Sql[Rol_Num][1];
            $periodo = $Par_Sql[Pec_Cod] == 'Seleccione...' ? 0 : $Par_Sql[Pec_Cod];
            $sql = "SELECT COUNT(*) REPORTE_COD, PEC_COD FROM reporte_aporextra 
                WHERE PEC_COD= $periodo
                AND REPORTE_FEI= $inicio
                AND REPORTE_FEF = $fin
                AND EMP_COD= $_SESSION[Ses_Emp_Cod]";
            break;

        case 68:
            $AreaCodigo = '';
            if ($Par_Sql['Are_Cod'] == 1) {
                $AreaCodigo = 'RP.ARE_COD';
            } else {
                $AreaCodigo = $Par_Sql['Are_Cod'];
            }
            $fechaini = "RP.ROL_FEI >= '" . $Par_Sql['dateRolini'] . "'";
            $fechafin = "RP.ROL_FEF <= '" . $Par_Sql['dateRolfin'] . "'";
            $periodo = "'" . $Par_Sql['Month'] . "-01'";

            $sql = "SELECT 
                    EMP.EMP_RUC,  MS.MAP_DES,
                    CONCAT(Prs_Nom,' ' ,Prs_Ape) AS nombres,
                    '0001' AS SUCURSAL_IESS, 
                    YEAR(PC.PEC_FEI) AS ANIO, 
                    LPAD(MONTH($periodo), 2, '0') AS PERIODO, 
                    'INS' AS TIPO, 
                    PER.PRS_CED,
                    Prs_Sex,
                    SUM(CASE WHEN CR.CAM_VAR = 'dias' THEN DP.ROL_VAL ELSE 0 END) AS rol_dias,
                    SUM(CASE WHEN CR.CAM_VAR = 'sueldo_dias' THEN DP.ROL_VAL ELSE 0 END) AS ROL_VAL,
                    'O' AS O,
                    SUM(CASE WHEN CR.CAM_DES = 'EXTRAS APORTE' THEN DP.ROL_VAL ELSE 0 END) AS extra_aporte,
                    ROUND((SUM(CASE WHEN CR.CAM_VAR = 'aporte_extras_rol_p' THEN DP.ROL_VAL ELSE 0 END) + SUM(CASE WHEN CR.CAM_VAR = 'sueldo_dias' THEN DP.ROL_VAL ELSE 0 END)) / 12  , 3) AS decimo
                FROM  map_system MS
                JOIN  empresas EMP ON EMP.EMP_COD = MS.EMP_COD
                JOIN  campo_rol CR ON CR.MAP_COD = MS.MAP_COD 
                JOIN  det_rpagos DP ON DP.CAM_COD = CR.CAM_COD
                JOIN  rol_pagos RP ON RP.ROL_COD = DP.ROL_COD
                JOIN  contratos_lab CL ON CL.CON_COD = DP.CON_COD
                JOIN  sueldos su ON su.Con_Cod = CL.Con_Cod
                JOIN  personal PR ON PR.PER_COD = CL.PER_COD
                JOIN  persona PER ON PER.PRS_COD = PR.PRS_COD
                JOIN  perio_cont PC ON PC.PEC_COD = RP.PEC_COD
                WHERE  MS.MAP_COD = $Par_Sql[Map_Cod]
                    AND  $fechaini
                    AND  $fechafin 
                    AND RP.Rol_Est = 'A'
                    AND RP.ARE_COD = $AreaCodigo
                    AND RP.PEC_COD = $Par_Sql[Pec_Cod]
                    AND DP.ROL_VAL > 0
                GROUP BY 
                    PER.PRS_CED;";
            break;
        case 74:
            $AreaCodigo = '';
            if ($Par_Sql['Are_Cod'] == 1) {
                $AreaCodigo = 'RP.ARE_COD';
            } else {
                $AreaCodigo = $Par_Sql['Are_Cod'];
            }
            $fechaini = "RP.ROL_FEI >= '" . $Par_Sql['dateRolini'] . "'";
            $fechafin = "RP.ROL_FEF <= '" . $Par_Sql['dateRolfin'] . "'";
            $periodo = "'" . $Par_Sql['Month'] . "-01'";

            $sql = "SELECT 
                    EMP.EMP_RUC,  MS.MAP_DES,
                    CONCAT(Prs_Nom,' ' ,Prs_Ape) AS nombres,
                    '0001' AS SUCURSAL_IESS, 
                    YEAR(PC.PEC_FEI) AS ANIO, 
                    LPAD(MONTH($periodo), 2, '0') AS PERIODO, 
                    'INS' AS TIPO, 
                    PER.PRS_CED,
                    Prs_Sex,
                    TC.Tic_Des AS car_des,
                    PG.Pag_Con_Cue,
                    AF.Afi_Dte,
                    SUM(CASE WHEN CR.CAM_VAR = 'dias' THEN DP.ROL_VAL ELSE 0 END) AS rol_dias,
                    SUM(CASE WHEN CR.CAM_VAR = 'sueldo_dias' THEN DP.ROL_VAL ELSE 0 END) AS ROL_VAL,
                    'O' AS O,
                    SUM(CASE WHEN CR.CAM_DES = 'EXTRAS APORTE' THEN DP.ROL_VAL ELSE 0 END) AS extra_aporte,
                    ROUND((SUM(CASE WHEN CR.CAM_VAR = 'aporte_extras_rol_p' THEN DP.ROL_VAL ELSE 0 END) + SUM(CASE WHEN CR.CAM_VAR = 'sueldo_dias' THEN DP.ROL_VAL ELSE 0 END)) / 12  , 3) AS decimo
                FROM  map_system MS
                JOIN  empresas EMP ON EMP.EMP_COD = MS.EMP_COD
                JOIN  campo_rol CR ON CR.MAP_COD = MS.MAP_COD 
                JOIN  det_rpagos DP ON DP.CAM_COD = CR.CAM_COD
                JOIN  rol_pagos RP ON RP.ROL_COD = DP.ROL_COD
                JOIN  contratos_lab CL ON CL.CON_COD = DP.CON_COD
                JOIN  tiposcargo TC ON CL.Tic_Cod = TC.Tic_Cod
                JOIN  sueldos su ON su.Con_Cod = CL.Con_Cod
                JOIN  personal PR ON PR.PER_COD = CL.PER_COD
                JOIN  persona PER ON PER.PRS_COD = PR.PRS_COD
                LEFT JOIN afiliacion AF ON AF.Con_Cod = CL.Con_Cod AND AF.Afi_Est = 'A'
                LEFT JOIN pago_contrato PG ON CL.Con_Cod = PG.Con_Cod AND PG.Pag_Con_Est = 'A'
                JOIN  perio_cont PC ON PC.PEC_COD = RP.PEC_COD
                WHERE  MS.MAP_COD = $Par_Sql[Map_Cod]
                    AND  $fechaini
                    AND  $fechafin 
                    AND RP.Rol_Est = 'A'
                    AND RP.ARE_COD = $AreaCodigo
                    AND DP.ROL_VAL > 0
                GROUP BY 
                    PER.PRS_CED;";
            break;
        case 69:
            $sql="INSERT INTO proveedore(Prs_Cod,Emp_Cod) VALUES ('$Par_Sql[Prs_Cod]','$Par_Sql[Emp_Cod]')";
            //echo $sql;
            break;	 
		case 70:
            $sql="SELECT DISTINCT persona.*, proveedore.Prv_Cod
				FROM persona
					INNER JOIN personal ON persona.Prs_Cod = personal.Prs_Cod 
					INNER JOIN contratos_lab ON personal.Per_Cod = contratos_lab.Per_Cod AND Con_Est='A' 
					LEFT JOIN proveedore ON persona.Prs_Cod = proveedore.Prs_Cod
				WHERE Con_Cod=$Par_Sql[0]";
            //echo $sql;
            break;
        case 71:
            $sql = "UPDATE antici_rol SET Ant_Est='I' WHERE Ant_Cod=$Par_Sql[0]";
            break;
        case 75:
            $sql = "SELECT Com_Cod FROM compr_arol WHERE Ant_Cod=$Par_Sql[0] LIMIT 1";
            break;
        case 73:
            $fini = $Par_Sql['fini'];
            $ffin = $Par_Sql['ffin'];
            $search = "";
            if(!empty($Par_Sql['search'])){
                // Use mysql_real_escape_string if available, but here we just double quotes
                $term = str_replace("'", "", $Par_Sql['search']); 
                $search = " AND (Prs_Ape LIKE '%$term%' OR Prs_Nom LIKE '%$term%')";
            }
            
            // Status Filter Logic
            $estado = isset($Par_Sql['Ant_Est']) ? $Par_Sql['Ant_Est'] : 'A';
            $filtroEstado = "AND Ant_Est = '$estado'";
            if($estado == 'T'){
                $filtroEstado = ""; // Show All
            }
            
            $Emp_Cod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
            
            $sql = "SELECT antici_rol.Ant_Cod, Ant_Fec, Ant_Val, CAST(Ant_Obs AS CHAR) as Ant_Obs, Ant_Est, 
                    CONCAT(Prs_Ape, ' ', Prs_Nom) as Personal, 
                    (SELECT Com_Cod FROM compr_arol WHERE compr_arol.Ant_Cod = antici_rol.Ant_Cod LIMIT 1) as Com_Cod,
                    (SELECT Com_Num FROM comprobantes WHERE comprobantes.Com_Cod = (SELECT Com_Cod FROM compr_arol WHERE compr_arol.Ant_Cod = antici_rol.Ant_Cod LIMIT 1)) as Com_Num
                    FROM antici_rol 
                    INNER JOIN contratos_lab ON antici_rol.Con_Cod = contratos_lab.Con_Cod 
                    INNER JOIN personal ON contratos_lab.Per_Cod = personal.Per_Cod 
                    INNER JOIN persona ON personal.Prs_Cod = persona.Prs_Cod 
                    WHERE 1=1 
                    $filtroEstado
                    AND Ant_Fec BETWEEN '$fini' AND '$ffin'
                    AND personal.Emp_Cod = $Emp_Cod
                    $search
                    ORDER BY Ant_Fec DESC";
            break;
    }
    //echo $sql."<br/>";
    return $sql;
}
