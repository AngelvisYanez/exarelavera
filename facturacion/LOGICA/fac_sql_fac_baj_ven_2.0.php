<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Alejandro Camahco
 * @version 1.0
 * Fecha de actualizaci�n:	2021/04/09
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package fac.LOGICA
 */

function sentencias_doc($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 0:
            $sql = "";
            break;

        case 1:
            $sql = "UPDATE producto SET Pro_Stk=$Par_Sql[Pro_Stk] WHERE Pro_Cod=$Par_Sql[Pro_Cod];";
            break;

        case 2:
            $sql = "SELECT Kar_Int, Iva_Cod, Pro_Cod FROM kardex_ie WHERE Vet_Cod = $Par_Sql[Vet_Cod] AND Pro_Cod = $Par_Sql[Pro_Cod]";
            break;

        case 3:
         $sql = "";
         if( !empty($Par_Sql['Kar_Int']) && !empty($Par_Sql['Iva_Cod']) &&  !empty($Par_Sql['Pro_Cod']) &&  !empty($Par_Sql['Vet_Cod'])){
         $sql = "UPDATE kardex_ie SET Kar_Est ='I' 
                    WHERE Kar_Int=$Par_Sql[Kar_Int] AND Iva_Cod=$Par_Sql[Iva_Cod] AND Pro_Cod=$Par_Sql[Pro_Cod] 
                    AND Vet_Cod = $Par_Sql[Vet_Cod] AND Kar_Est = 'A';";

        }
            break;

        case 4:
            // Actualizar manifiesto: cambiar Man_Tip a 'GS' y eliminar '-F' o 'F' de Man_Tes
            // Solo actualiza manifiestos que tienen Man_Tip = 'F' (facturados)
            $Vet_Cod = isset($Par_Sql['Vet_Cod']) ? intval($Par_Sql['Vet_Cod']) : 0;
            if ($Vet_Cod <= 0) {
                $sql = "SELECT * FROM manifiesto WHERE 1=0"; // Retornar consulta que no afecta nada
            } else {
                $sql = "UPDATE manifiesto SET  Man_Tip = 'GS', Vet_Cod = NULL, Man_Tes = CASE 
                                WHEN RIGHT(TRIM(Man_Tes), 2) = '-F' THEN LEFT(TRIM(Man_Tes), LENGTH(TRIM(Man_Tes)) - 2)
                                WHEN RIGHT(TRIM(Man_Tes), 1) = 'F' THEN LEFT(TRIM(Man_Tes), LENGTH(TRIM(Man_Tes)) - 1)
                                ELSE Man_Tes
                            END
                        WHERE Vet_Cod = $Vet_Cod   AND Man_Tip = 'F'   AND Man_Est = 'A'";
            }
            break;

        case 5:
            // Contar si existe Vet_Cod en manifiesto (solo manifiestos facturados y activos)
            $Vet_Cod = isset($Par_Sql['Vet_Cod']) ? intval($Par_Sql['Vet_Cod']) : 0;
            if ($Vet_Cod <= 0) {
                $sql = "SELECT 0 AS total FROM manifiesto WHERE 1=0"; // Retornar 0 si Vet_Cod es inválido
            } else {
                $sql = "SELECT COUNT(*) AS total  FROM manifiesto WHERE Vet_Cod = $Vet_Cod  AND Man_Tip = 'F'  AND Man_Est = 'A'";
            }
            break;
    }

    return $sql;
}
