<?php
/**
 * Retorna consulta sql a ejecutarse para RDEP
 * 
 * @author Sistema
 * @version 1.0
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 */

function sentencias_rdep($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 0:
            $sql = "";
            break;
        // Agregar más casos según sea necesario
        default:
            $sql = "";
            break;
    }
    return $sql;
}

?>

