<?php


function sentencias_labores_v2($id, $Par_Sql)
{
	switch ($id) {
		case 1:

			$condition = '';
			if (isset($Par_Sql['Month']) && !empty($Par_Sql['Month'])) {
				$fecha = explode("-", $Par_Sql['Month']);
				$mes = $fecha[1];
				if ($mes != '00') {
					$condition = " AND rol_pagos.Rol_Fei LIKE '" . $Par_Sql['Month'] . "%' ";
				}
			}
			$sql = "SELECT DISTINCT rol_pagos.*, areas_rrhh.Are_Des FROM rol_pagos
                    INNER JOIN det_rpagos ON rol_pagos.Rol_Cod=det_rpagos.Rol_Cod
                    INNER JOIN campo_rol ON campo_rol.Cam_Cod=det_rpagos.Cam_Cod
                    INNER JOIN map_system ON campo_rol.Map_Cod=map_system.Map_Cod
                    INNER JOIN areas_rrhh ON rol_pagos.Are_Cod=areas_rrhh.Are_Cod
                    LEFT JOIN compr_rol ON rol_pagos.Rol_Cod=compr_rol.Rol_Cod
                    WHERE map_system.Emp_Cod='$_SESSION[Ses_Emp_Cod]' AND Rol_Est = 'A'
                          AND rol_pagos.Pec_Cod=$Par_Sql[Pec_Cod]
                    " . (isset($Par_Sql['Are_Cod']) && !empty($Par_Sql['Are_Cod']) ? " AND areas_rrhh.Are_Cod=$Par_Sql[Are_Cod] " : '') . "
                    " . (isset($Par_Sql['Rol_Tip']) && !empty($Par_Sql['Rol_Tip']) ? " AND rol_pagos.Rol_Tip='$Par_Sql[Rol_Tip]' " : '') .
				$condition . "
                    GROUP BY rol_pagos.Rol_Cod ORDER BY Are_Des,Rol_Num DESC,Rol_Est";
			break;

		case 2:

			$sql = "SELECT ropa.Rol_Cod,
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

			$sql = "SELECT ropa.Rol_Cod,
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
					AND per.Prs_Ced = '" . $Par_Sql[Prs_Ced] . "'
					AND (capo.Cam_Var = 'sueldo_dias' OR capo.Cam_Var = 'OTR_INGRAV'  OR capo.Cam_Var = 'TTL_HREXTO' OR capo.Cam_Var = 'TOTAL _HRS' OR capo.Cam_Var = 'PORSIACA' OR capo.Cam_Var = 'TRANSPORTE') ORDER BY capo.Cam_DES ASC";
			break;

		case 4:
			$sql = "SELECT * FROM areas_rrhh WHERE Are_Est='A' AND Emp_Cod='$Par_Sql[0]'";
			break;

		case 5:
			$sql = "SELECT ropa.Rol_Cod,
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
					AND per.Prs_Ced = '" . $Par_Sql[Prs_Ced] . "'
					AND capo.Cam_Var = '" . $Par_Sql[Cam_Var] . "'LIMIT 1";
			break;

		case 6:
			$sql = "SELECT CONCAT('Semana Integral (', CONVERT(GROUP_CONCAT(la.Lab_Des SEPARATOR ', ') USING utf8), ')')  AS Descripcion
					FROM actividad_labor  ac
					INNER JOIN det_actividad_labor det ON ac.Act_Cod = det.Act_Cod
					INNER JOIN labores la ON la.Lab_Cod = det.Lab_Cod
					WHERE ac.Act_Sem = $Par_Sql[Rol_Num]
					AND ac.Pec_Cod = $Par_Sql[Pec_Cod]
					AND det.Per_Cod = $Par_Sql[Per_Cod] AND la.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' 
					GROUP BY ac.Act_Sem";
					
			break;

		/** Semanas que ya tienen actividad registrada (misma combinación que verificaFincaSemana). */
		case 8:
			$pec = (int)(isset($Par_Sql['Pec_Cod']) ? $Par_Sql['Pec_Cod'] : 0);
			$fnc = (int)(isset($Par_Sql['Fnc_Cod']) ? $Par_Sql['Fnc_Cod'] : 0);
			$sql = "SELECT DISTINCT actividad_labor.Act_Sem
                FROM actividad_labor
                INNER JOIN finca_actividad ON finca_actividad.Fnc_Cod = actividad_labor.Fnc_Cod
                WHERE actividad_labor.Pec_Cod = $pec
                    AND actividad_labor.Fnc_Cod = $fnc
                    AND actividad_labor.Act_Est = 'A'
                ORDER BY actividad_labor.Act_Sem";
			break;

		/** Empleados con contrato activo en un rango de fechas (semana) y cargo en el departamento del área RRHH (misma idea que roles, caso 9). */
		case 7:
			$are = (int)(isset($Par_Sql['Are_Cod']) ? $Par_Sql['Are_Cod'] : 0);
			$fei = isset($Par_Sql['Sem_Fei']) ? $Par_Sql['Sem_Fei'] : '';
			$fef = isset($Par_Sql['Sem_Fef']) ? $Par_Sql['Sem_Fef'] : '';
			$sql = "SELECT DISTINCT personal.Per_Cod,
                    CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Personal
                FROM personal
                INNER JOIN persona ON persona.Prs_Cod=personal.Prs_Cod
                INNER JOIN contratos_lab ON contratos_lab.Per_Cod=personal.Per_Cod
                INNER JOIN dedica_lab ON dedica_lab.Ded_Cod=contratos_lab.ded_Cod
                INNER JOIN tiposcargo ON contratos_lab.Tic_Cod=tiposcargo.Tic_Cod
                INNER JOIN departamen ON departamen.Dep_Cod=tiposcargo.Dep_Cod
                WHERE personal.Emp_Cod='$_SESSION[Ses_Emp_Cod]'
                    AND personal.Per_Est='A'
                    AND contratos_lab.Con_Est='A'
                    AND departamen.Are_Cod=$are
                    AND ((contratos_lab.Con_Fin > '$fei')
                        AND (contratos_lab.Con_Ini <= '$fei' OR contratos_lab.Con_Ini BETWEEN '$fei' AND '$fef'))
                ORDER BY Personal";
			break;

		/** Actividades donde participa un empleado en un periodo contable (localizar finca/semana sin saber area). */
		case 10:
			$pec10 = (int)(isset($Par_Sql['Pec_Cod']) ? $Par_Sql['Pec_Cod'] : 0);
			$per10 = (int)(isset($Par_Sql['Per_Cod']) ? $Par_Sql['Per_Cod'] : 0);
			$sql = "SELECT DISTINCT ac.Act_Cod, ac.Pec_Cod, ac.Fnc_Cod, ac.Act_Sem, ac.Act_Fec, ac.Act_Res,
                    fa.Fnc_Des AS Fnc_Des
                FROM actividad_labor ac
                INNER JOIN det_actividad_labor det ON ac.Act_Cod = det.Act_Cod AND det.Per_Cod = " . $per10 . "
                INNER JOIN finca_actividad fa ON fa.Fnc_Cod = ac.Fnc_Cod
                WHERE ac.Pec_Cod = " . $pec10 . "
                    AND ac.Act_Est = 'A'
                ORDER BY fa.Fnc_Des ASC, ac.Act_Sem ASC";
			break;
	}

	return $sql;
}
