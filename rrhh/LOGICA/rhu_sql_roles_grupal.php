<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Alejandro Camacho
 * @version 1.0
 * Fecha de actualizaci�n:	2024/05/26
 */

function sentencias_rol($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 1:
            $sql = "SELECT * FROM areas_rrhh WHERE Are_Est='A' AND Emp_Cod=$Par_Sql[0]";
            break;

        case 2:
            $sql = "SELECT * FROM map_system WHERE Map_Est='A' AND Emp_Cod='$Par_Sql[0]';";
            break;

        case 3:
            $sql = "SELECT perio_cont.*,YEAR(Pec_Fei)AS Periodo FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Pec_Est='A' AND Emp_Cod='$Par_Sql[0]' ORDER BY Periodo DESC";
            break;

        case 6:
            $sql = "SELECT * FROM oper_item WHERE Ogr_Cod=$Par_Sql[Ogr_Cod] AND Oit_Ord=$Par_Sql[Oit_Ord];";
            break;
        case 7:
            $type = isset($Par_Sql['type']) ? sql_conjunction($Par_Sql['type'], 'Cam_Tip=') : '';
            $var = isset($Par_Sql['var']) ? sql_conjunction($Par_Sql['var'], 'Cam_Var=') : '';
            $sum = isset($Par_Sql['sum']) ? sql_conjunction($Par_Sql['sum'], 'Cam_Sum=') : '';
            $sql = "SELECT * FROM campo_rol WHERE Map_Cod=$Par_Sql[Map_Cod] $type $var $sum ORDER BY Cam_Tip DESC,Cam_Ord ASC;";
            break;
        case 8:
            $sql = "SELECT * FROM map_system WHERE Map_Cod=$Par_Sql[Map_Cod];";
            break;

        case 9:
            $where = isset($Par_Sql['Con_Cod']) ? " contratos_lab.Con_Cod=$Par_Sql[Con_Cod] " :
                " Per_Est='A' AND Con_Est='A' " . (isset($Par_Sql['Rol_Fei']) ? " AND ((   (contratos_lab.Con_Fin > '$Par_Sql[Rol_Fei]') AND  (contratos_lab.Con_Ini <= '$Par_Sql[Rol_Fei]' OR contratos_lab.Con_Ini BETWEEN '$Par_Sql[Rol_Fei]' AND '$Par_Sql[Rol_Fef]' )  ) )" : '') . (!empty($Par_Sql['Are_Cod']) ? " AND departamen.Are_Cod='$Par_Sql[Are_Cod]' " : '');
            $sql = "SELECT personal.Emp_Cod,Prs_Ced,Prs_Ape,Prs_Nom,CONCAT(Prs_Ape,' ',Prs_Nom)AS Personal,Tic_Des,personal.Per_Cod,contratos_lab.Con_Cod,Sue_Val AS sueldo,IF(Sue_Va1 IS NULL OR Sue_Va1=0,Sue_Val,Sue_Va1)AS sueldo_neto,
                IF(Afi_Dte='S',1,0)AS deci_terc_acum, IF(Afi_Dcu='S',1,0)AS deci_cuar_acum, IF(Afi_Fnd='S',1,0)AS fond_reser_acum, Afi_Fei, Afi_Fef, IF(Afi_Fei IS NULL,0,1)AS es_afiliado, Ded_Hrs, IF(Afi_Due='S',1,0)AS es_empleador, Sue_Bas, IF(Ded_Hrs=4,1,0)AS medio_tiempo, IF(Ded_Hrs=0,1,0)AS tiempo_parcial,Sut_Cod,Con_Ini
                FROM personal
                INNER JOIN persona ON persona.Prs_Cod=personal.Prs_Cod
                INNER JOIN contratos_lab ON contratos_lab.Per_Cod=personal.Per_Cod
                INNER JOIN dedica_lab ON dedica_lab.Ded_Cod=contratos_lab.ded_Cod
                INNER JOIN tiposcargo ON contratos_lab.Tic_Cod=tiposcargo.Tic_Cod
                INNER JOIN departamen ON departamen.Dep_Cod=tiposcargo.Dep_Cod
                INNER JOIN sueldos ON sueldos.Sue_Cod=(SELECT Sue_Cod FROM sueldos WHERE sueldos.Con_Cod=contratos_lab.Con_Cod AND Sue_Est='A' LIMIT 1 )
                LEFT JOIN afiliacion ON contratos_lab.Con_Cod=afiliacion.Con_Cod AND Afi_Est='A'
                WHERE $where
                ORDER BY Personal;";
            break;

        case 10:
            if ($Par_Sql["Are_Cod"] !=  0) {
                $Par_Sql["Are_Cod"] = "AND Are_Cod = $Par_Sql[Are_Cod]";
            } else {
                $Par_Sql["Are_Cod"] = "";
            }
            /* $sql = "SELECT Rol_Cod FROM rol_pagos WHERE Rol_Est='A' AND Pec_Cod=$Par_Sql[Pec_Cod] 
                    AND Rol_Tip = '" . $Par_Sql[Rol_Tip] . "'
                    AND Rol_Num BETWEEN $Par_Sql[Rol_I] and $Par_Sql[Rol_F] 
                    AND Are_Cod = $Par_Sql[Are_Cod]";*/
          /*  $sql = "SELECT Rol_Cod FROM rol_pagos WHERE Rol_Est='A' AND Pec_Cod=$Par_Sql[Pec_Cod] 
                    AND Rol_Tip = '" . $Par_Sql[Rol_Tip] . "'
                    AND Rol_Num BETWEEN $Par_Sql[Rol_I] AND $Par_Sql[Rol_F] 
                    $Par_Sql[Are_Cod] ";*/



             $sql = "SELECT rol_pagos.Rol_Cod FROM rol_pagos 
            INNER JOIN det_rpagos ON rol_pagos.Rol_Cod=det_rpagos.Rol_Cod
                    INNER JOIN campo_rol ON campo_rol.Cam_Cod=det_rpagos.Cam_Cod
                    INNER JOIN map_system ON campo_rol.Map_Cod=map_system.Map_Cod
            
            WHERE Rol_Est='A' AND Pec_Cod=$Par_Sql[Pec_Cod] AND map_system.Map_Cod ='$Par_Sql[Map_Cod]'
                    AND Rol_Tip = '" . $Par_Sql[Rol_Tip] . "'
                    AND Rol_Num BETWEEN $Par_Sql[Rol_I] AND $Par_Sql[Rol_F] 
                    $Par_Sql[Are_Cod] ";
            break;

        case 16:
            $Pec = '';
            if (isset($Par_Sql['Pec_Cod'])) {
                if ($Par_Sql['Pec_Cod'] == 'RANGE') $Pec = "AND rol_pagos.Rol_Fei BETWEEN '$Par_Sql[ini]' AND '$Par_Sql[fin]'";
                else if (!empty($Par_Sql['Pec_Cod']) && $Par_Sql['Pec_Cod'] != 'ALL')  $Pec = "AND rol_pagos.Pec_Cod=$Par_Sql[Pec_Cod] ";
            }
            $sql = "SELECT DISTINCT rol_pagos.*,map_system.Map_Cod,Map_Des,Are_Des,YEAR(Rol_Fef)AS Anio,Usu_Cod, compr_rol.Com_Cod,
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
            //ChromePhp::log($sql);

            break;

        case 17:
            $sql = "SELECT det_rpagos.*,Cam_Var FROM det_rpagos
                    INNER JOIN campo_rol ON campo_rol.Cam_Cod=det_rpagos.Cam_Cod
                    WHERE (Cam_Vis='S' OR Cam_Tip='D' OR Cam_Tip='P' OR Cam_Var = 'labores_ingreso') " . (isset($Par_Sql['totales']) ? " AND Cam_Var='total_rol' " : '') . " AND Rol_Cod=$Par_Sql[Rol_Cod] " . (isset($Par_Sql['Con_Cod']) ? "AND Con_Cod=$Par_Sql[Con_Cod]" : '');
            //ChromePhp::log($sql);
            break;

        case 171:
            $sql = "SELECT det_rpagos.Con_Cod, det_rpagos.Cam_Cod, SUM(det_rpagos.Rol_Val) Rol_Val,Cam_Var FROM det_rpagos
                    INNER JOIN campo_rol ON campo_rol.Cam_Cod=det_rpagos.Cam_Cod
                    WHERE (Cam_Vis='S' OR Cam_Tip='D' OR Cam_Tip='P' 
                    OR Cam_Var = 'labores_ingreso') " . (isset($Par_Sql['totales']) ? " AND Cam_Var='total_rol' " : '') . " 
                    AND Rol_Cod in $Par_Sql[Rol_Cod]" . (isset($Par_Sql['Con_Cod']) ? "AND Con_Cod=$Par_Sql[Con_Cod]" : '' . " GROUP BY det_rpagos.Con_Cod, det_rpagos.Cam_Cod, Cam_Var");
            //ChromePhp::log($sql);
            break;
    }
    return $sql;
}
