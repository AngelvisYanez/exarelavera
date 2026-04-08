<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Asael Tello
 * @version 2.0
 * Fecha de actualizaci�n:	2017-23-08
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
		case 1:
		$sql = "SELECT 
				autorizaci.Aut_Cod, 
				tipo_compr.Tic_Cod,
				autorizaci.Aut_Sri, 
				autorizaci.Aut_Tem, 
				tipo_compr.Tic_Des
				FROM puntos_imp 
				INNER JOIN autorizaci ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod 
				INNER JOIN tipo_compr ON autorizaci.Tic_Cod = tipo_compr.Tic_Cod
				WHERE puntos_imp.Suc_Cod = $Par_Sql[0] 
				AND Aut_Est = 'A'
				AND Tic_Sri != 0
				ORDER BY tipo_compr.Tic_Cod, autorizaci.Aut_Sri";
		return $sql;
		break;	

		case 2:
		$sql = "SELECT Suc_Cod, Suc_Des FROM sucursal WHERE Emp_Cod = $Par_Sql[0] ";
		return $sql;
		break;

		case 3:
		$sql = "SELECT 
				autorizaci.Aut_Cod, 
				tipo_compr.Tic_Cod,
				autorizaci.Aut_Sri, 
				autorizaci.Aut_Tem, 
				tipo_compr.Tic_Des
				FROM puntos_imp 
				INNER JOIN autorizaci ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod 
				INNER JOIN tipo_compr ON autorizaci.Tic_Cod = tipo_compr.Tic_Cod
				WHERE puntos_imp.Suc_Cod = $Par_Sql[Sucursal] 
				AND Aut_Est = 'A'
				AND tipo_compr.Tic_Cod = $Par_Sql[Tipo]
				GROUP BY autorizaci.Aut_Sri
				ORDER BY tipo_compr.Tic_Cod, autorizaci.Aut_Sri";
		return $sql;
		break;

		case 4:
		$sql = "SELECT 
				autorizaci.Aut_Cod
				FROM puntos_imp 
				INNER JOIN autorizaci ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod 
				INNER JOIN tipo_compr ON autorizaci.Tic_Cod = tipo_compr.Tic_Cod
				WHERE puntos_imp.Suc_Cod = $Par_Sql[Sucursal] 
				AND Aut_Est = 'A'
				AND tipo_compr.Tic_Cod = $Par_Sql[Tipo]
				AND autorizaci.Aut_Sri = '$Par_Sql[Aut_Sri]'
				AND autorizaci.Pun_Sri = '$Par_Sql[Pun_Sri]'
				ORDER BY tipo_compr.Tic_Cod, autorizaci.Aut_Sri";
		return $sql;
		break;

		case 5:
		$sql = "SELECT Vet_Num FROM ventas 
				INNER JOIN caja_aper ON caja_aper.Caj_Cod = ventas.Caj_Cod 
				WHERE Aut_Cod in $Par_Sql[Codigos] AND caja_aper.Caj_Fec >= '$Par_Sql[Fecha]'
				ORDER BY Vet_Num ASC";
		return $sql;
		break;

		case 6:
				$sql = "SELECT Ret_Num FROM retencion WHERE Aut_Cod in $Par_Sql[Codigos] AND Ret_Fec >= '$Par_Sql[Fecha]' ORDER BY Ret_Num ASC";
		return $sql;
		break;

		case 7:
				$sql = "SELECT Gui_Num FROM guias_remis WHERE Aut_Cod in $Par_Sql[Codigos] AND Gui_Fec >= '$Par_Sql[Fecha]' ORDER BY Gui_Num ASC";
		return $sql;
		break;

		case 8:
		$sql = "SELECT 
				autorizaci.Pun_Sri, 
				autorizaci.Aut_Cod
				FROM puntos_imp 
				INNER JOIN autorizaci ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod 
				INNER JOIN tipo_compr ON autorizaci.Tic_Cod = tipo_compr.Tic_Cod
				WHERE puntos_imp.Suc_Cod = $Par_Sql[Sucursal] 
				AND Aut_Est = 'A'
				AND tipo_compr.Tic_Cod = $Par_Sql[Tipo]
				AND autorizaci.Aut_Sri = '$Par_Sql[Autorizacion]'
				GROUP BY autorizaci.Aut_Sri
				ORDER BY tipo_compr.Tic_Cod, autorizaci.Aut_Sri";
		return $sql;
		break;
	}
}?>