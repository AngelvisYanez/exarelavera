<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */
function sentencias_caj($id,$Par_Sql)
{
    switch($id)
    {
        
        case 0:// select users
            $sql= "SELECT
                    `persona`.`Prs_Cod`,
                    `persona`.`Prs_Nom`,
                    `persona`.`Prs_Ape`,
                    `vendedor`.`Vnd_Cod`,
                    `puntos_imp`.`Suc_Cod`,
                    `vendedor`.`Pun_Cod`,
                    `puntos_imp`.`Pun_Des`
                    FROM
                    `vendedor`
                    INNER JOIN `persona` ON `persona`.`Prs_Cod` = `vendedor`.`Prs_Cod`
                    INNER JOIN `puntos_imp` ON `vendedor`.`Pun_Cod` = `puntos_imp`.`Pun_Cod` WHERE puntos_imp.suc_cod = $Par_Sql[0] AND persona.Prs_Cod='$_SESSION[Ses_Prs_Cod]' ";
            break;
        
        case 1:// insert caja
            $sql= " INSERT INTO caja_aper (Pun_Cod, Caj_Fec, Caj_Hoi, Caj_Obs, Caj_Exi, Caj_Est, Caj_Gen) "
                . "VALUES( $Par_Sql[Pun_Cod], '$Par_Sql[Caj_Fec]', CURTIME(), '$Par_Sql[Caj_Obs]', $Par_Sql[Caj_Exi], 'A', 'N') ";
            break;
        
        case 2: // consultas filtros
            $sql= "SELECT Caj_Cod, CONCAT(Caj_Fec,' ',Caj_Hoi) as apertura,  CONCAT(Caj_Fef,' ',Caj_Hof) as cierre , Caj_Exi, Caj_Obs, IF (Caj_Est = 'A', 'Activo','Inactivo') as Caj_Est FROM caja_aper "
                . "WHERE Pun_Cod = $Par_Sql[Pun_Cod] "
                . "AND Caj_Fec BETWEEN '$Par_Sql[desde]' AND '$Par_Sql[hasta]' ORDER BY Caj_Cod DESC";
            break;
        
        case 3: // consulta validacion
            $sql= "SELECT COUNT(*) as contador FROM caja_aper WHERE Caj_Fec = '$Par_Sql[Caj_Fec]' AND Pun_Cod = $Par_Sql[Pun_Cod]";
            break;
        
        case 4: // cerrar caja
            $sql= "UPDATE caja_aper SET Caj_Fef = CURDATE() , Caj_Hof = CURTIME(), Caj_Est = 'C'  WHERE Caj_Cod = $Par_Sql[Caj_Cod]";
            break;
        
        case 5: // get caja
            $sql= "SELECT Caj_Cod, CONCAT(Caj_Fec,' ',Caj_Hoi) as apertura,  CONCAT(Caj_Fef,' ',Caj_Hof) as cierre , Caj_Exi, Caj_Obs, IF (Caj_Est = 'A', 'Activo','Inactivo') as Caj_Est FROM caja_aper WHERE Caj_Cod = $Par_Sql[0] ORDER BY Caj_Cod DESC";
            break;
        
        case 6:// usuario
            $sql = "SELECT
                    `persona`.`Prs_Cod`,
                    `persona`.`Prs_Nom`,
                    `persona`.`Prs_Ape`,
                    `vendedor`.`Vnd_Cod`,
                    `puntos_imp`.`Suc_Cod`,
                    `vendedor`.`Pun_Cod`,
                    `puntos_imp`.`Pun_Des`
                    FROM
                    `vendedor`
                    INNER JOIN `persona` ON `persona`.`Prs_Cod` = `vendedor`.`Prs_Cod`
                    INNER JOIN `puntos_imp` ON `vendedor`.`Pun_Cod` = `puntos_imp`.`Pun_Cod` WHERE persona.Prs_Cod = $Par_Sql[Prs_Cod] AND puntos_imp.Suc_Cod = $Par_Sql[Suc_Cod]";
            break;
		 case 7: // cerrar caja
            $sql= "SELECT perfiles.Per_Des,usuarperfi.Usu_Cod FROM perfiles INNER JOIN usuarperfi ON (perfiles.Per_Cod = usuarperfi.Per_Cod) WHERE";
            break;	
						
    }
    return $sql;
}
?>