<?php


function sentencias_labores($id,$Par_Sql)
{
    switch ($id){
        case 1:
        	
	        $condition = '';
			if (isset($Par_Sql['Month']) && !empty($Par_Sql['Month'])) {
				$fecha = explode("-", $Par_Sql['Month']);
				$mes = $fecha[1];
				if($mes != '00'){
					$condition = " AND rol_pagos.Rol_Fei LIKE '" . $Par_Sql['Month'] . "%' ";
				}    
			}
            $sql="SELECT DISTINCT rol_pagos.*, areas_rrhh.Are_Des FROM rol_pagos
                    INNER JOIN det_rpagos ON rol_pagos.Rol_Cod=det_rpagos.Rol_Cod
                    INNER JOIN campo_rol ON campo_rol.Cam_Cod=det_rpagos.Cam_Cod
                    INNER JOIN map_system ON campo_rol.Map_Cod=map_system.Map_Cod
                    INNER JOIN areas_rrhh ON rol_pagos.Are_Cod=areas_rrhh.Are_Cod
                    LEFT JOIN compr_rol ON rol_pagos.Rol_Cod=compr_rol.Rol_Cod
                    WHERE map_system.Emp_Cod='$_SESSION[Ses_Emp_Cod]' AND Rol_Est = 'A'
                          AND rol_pagos.Pec_Cod=$Par_Sql[Pec_Cod]
                    ".(isset($Par_Sql['Are_Cod'])&&!empty($Par_Sql['Are_Cod'])?" AND areas_rrhh.Are_Cod=$Par_Sql[Are_Cod] ":''). "
                    ".(isset($Par_Sql['Rol_Tip'])&&!empty($Par_Sql['Rol_Tip'])?" AND rol_pagos.Rol_Tip='$Par_Sql[Rol_Tip]' ":'') . 
                    $condition ."
                    GROUP BY rol_pagos.Rol_Cod ORDER BY Are_Des,Rol_Num DESC,Rol_Est";
            break;

        case 2:

            $sql="SELECT ropa.Rol_Cod,
            				area.Are_Des,
					        ropa.Rol_Num,
					        ropa.Rol_Fef,
					        ropa.Rol_Fei,
					        per.Prs_Ced,
					        CONCAT(per.Prs_Nom, ' ' ,per.Prs_Ape) AS trabajador,
					        capo.Cam_Des,
					        ROUND(SUM(derp.rol_val),2) as total
					FROM rol_pagos as ropa
					INNER JOIN areas_rrhh as area ON ropa.Are_Cod=area.Are_Cod
					INNER JOIN det_rpagos as derp ON  ropa.Rol_Cod = derp.Rol_Cod
					INNER JOIN contratos_lab as cola ON cola.Con_Cod = derp.Con_Cod AND Con_Est = 'A'
					INNER JOIN personal as pers ON pers.Per_Cod = cola.Per_Cod
					INNER JOIN persona as per ON pers.Prs_Cod = per.Prs_Cod 
					INNER JOIN campo_rol as capo ON derp.Cam_Cod = capo.Cam_Cod
					WHERE ropa.Rol_Cod = $Par_Sql[Rol_Cod]
					AND (capo.Cam_Var = 'sueldo_dias' OR capo.Cam_Var = 'OTR_INGRAV' OR capo.Cam_Var = 'TTL_HREXTO' OR capo.Cam_Var = 'TOTAL _HRS' OR capo.Cam_Var = 'PORSIACA' OR capo.Cam_Var = 'TRANSPORTE')
					GROUP BY per.Prs_Ced";
			break;

		 case 3:

            $sql="SELECT ropa.Rol_Cod,
					        ropa.Rol_Num,
					        ropa.Rol_Fef,
					        ropa.Rol_Fei,
					        per.Prs_Ced,
					        CONCAT(per.Prs_Nom, ' ' ,per.Prs_Ape) AS trabajador,
					        capo.Cam_Des,
					        ROUND(derp.rol_val,2) as Rol_Val,
					        capo.Cam_Var,
					        ropa.Pec_Cod,
					        pers.Per_Cod
					FROM rol_pagos as ropa
					INNER JOIN det_rpagos as derp ON  ropa.Rol_Cod = derp.Rol_Cod
					INNER JOIN contratos_lab as cola ON cola.Con_Cod = derp.Con_Cod AND Con_Est = 'A'
					INNER JOIN personal as pers ON pers.Per_Cod = cola.Per_Cod
					INNER JOIN persona as per ON pers.Prs_Cod = per.Prs_Cod 
					INNER JOIN campo_rol as capo ON derp.Cam_Cod = capo.Cam_Cod
					WHERE ropa.Rol_Cod = $Par_Sql[Rol_Cod]
					AND per.Prs_Ced = '".$Par_Sql[Prs_Ced] ."'
					AND (capo.Cam_Var = 'sueldo_dias' OR capo.Cam_Var = 'OTR_INGRAV'  OR capo.Cam_Var = 'TTL_HREXTO' OR capo.Cam_Var = 'TOTAL _HRS' OR capo.Cam_Var = 'PORSIACA' OR capo.Cam_Var = 'TRANSPORTE') ORDER BY capo.Cam_DES ASC";
			break;

		case 4:
            $sql="SELECT * FROM areas_rrhh WHERE Are_Est='A' AND Emp_Cod=$Par_Sql[0]";
            break;

        case 5:
            $sql="SELECT ropa.Rol_Cod,
					        ropa.Rol_Num,
					        ropa.Rol_Fef,
					        ropa.Rol_Fei,
					        per.Prs_Ced,
					        CONCAT(per.Prs_Nom, ' ' ,per.Prs_Ape) AS trabajador,
					        capo.Cam_Des,
					        ROUND(derp.rol_val,2) as Rol_Val,
					        capo.Cam_Var
					FROM rol_pagos as ropa
					INNER JOIN det_rpagos as derp ON  ropa.Rol_Cod = derp.Rol_Cod
					INNER JOIN contratos_lab as cola ON cola.Con_Cod = derp.Con_Cod AND Con_Est = 'A'
					INNER JOIN personal as pers ON pers.Per_Cod = cola.Per_Cod
					INNER JOIN persona as per ON pers.Prs_Cod = per.Prs_Cod 
					INNER JOIN campo_rol as capo ON derp.Cam_Cod = capo.Cam_Cod
					WHERE ropa.Rol_Cod = $Par_Sql[Rol_Cod]
					AND per.Prs_Ced = '".$Par_Sql[Prs_Ced] ."'
					AND capo.Cam_Var = '". $Par_Sql[Cam_Var] ."'LIMIT 1";
			break;

		case 6:
            $sql="SELECT CONCAT('Semana Integral (', CONVERT(GROUP_CONCAT(la.Lab_Des SEPARATOR ', ') USING utf8), ')')  AS Descripcion
					FROM actividad_labor  ac
					INNER JOIN det_actividad_labor det ON ac.Act_Cod = det.Act_Cod
					INNER JOIN labores la ON la.Lab_Cod = det.Lab_Cod
					WHERE ac.Act_Sem = $Par_Sql[Rol_Num]
					AND ac.Pec_Cod = $Par_Sql[Pec_Cod]
					AND det.Per_Cod = $Par_Sql[Per_Cod]
					GROUP BY ac.Act_Sem";
			break;

    }

     return $sql;
 }