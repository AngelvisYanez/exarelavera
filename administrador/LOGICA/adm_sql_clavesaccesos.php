<?php
/**
 * Created by PhpStorm.
 * User: jorge
 * Date: 4/13/2018
 * Time: 3:13 PM
 */
function sentencias_clavesaccesos($id,$Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 0:
            $sql = "";
            break;
        case 1:
            // Insertar nueva clave de acceso
            $sql = "INSERT INTO claves_accesos (Emp_Cod, Cla_Cod, Cod_Psc, Cla_Est, Cla_Des) 
                    VALUES ('$Par_Sql[Emp_Cod]', '$Par_Sql[Cla_Cod]', " . 
                    (!empty($Par_Sql['Cod_Psc']) ? "'$Par_Sql[Cod_Psc]'" : "NULL") . ", 
                    '$Par_Sql[Cla_Est]', '$Par_Sql[Cla_Des]')";
            break;
        case 2:
            // Actualizar clave de acceso
            $codPsc = !empty($Par_Sql['Cod_Psc']) ? "'$Par_Sql[Cod_Psc]'" : "NULL";
            $sql = "UPDATE claves_accesos SET 
                    Cla_Cod = '$Par_Sql[Cla_Cod]', 
                    Cod_Psc = $codPsc, 
                    Cla_Est = '$Par_Sql[Cla_Est]', 
                    Cla_Des = '$Par_Sql[Cla_Des]' 
                    WHERE Cod_Cla = '$Par_Sql[Cod_Cla]' AND Emp_Cod = '$_SESSION[Ses_Emp_Cod]'";
            break;
        case 3:
            // Listar claves de acceso con búsqueda
            $search = isset($Par_Sql['search']) && !empty($Par_Sql['search']) ? 
                      "AND (Cla_Cod LIKE '%$Par_Sql[search]%' OR Cla_Des LIKE '%$Par_Sql[search]%' OR Cod_Psc LIKE '%$Par_Sql[search]%')" : "";
            $order = isset($Par_Sql['order']) ? "ORDER BY $Par_Sql[order]" : "ORDER BY Cod_Cla DESC";
            $limits = isset($Par_Sql['limits']) ? $Par_Sql['limits'] : "";
            $sql = "SELECT * FROM claves_accesos 
                    WHERE Emp_Cod = '$_SESSION[Ses_Emp_Cod]' $search 
                    $order $limits";
            break;
        case 4:
            // Obtener una clave de acceso por Cod_Cla o por Cla_Cod
            if (isset($Par_Sql['Cod_Cla'])) {
                $sql = "SELECT * FROM claves_accesos 
                        WHERE Cod_Cla = '$Par_Sql[Cod_Cla]' AND Emp_Cod = '$_SESSION[Ses_Emp_Cod]'";
            } elseif (isset($Par_Sql['Cla_Cod'])) {
                $sql = "SELECT * FROM claves_accesos 
                        WHERE Cla_Cod = '$Par_Sql[Cla_Cod]' AND Emp_Cod = '$_SESSION[Ses_Emp_Cod]'";
            } else {
                $sql = "SELECT * FROM claves_accesos 
                        WHERE Emp_Cod = '$_SESSION[Ses_Emp_Cod]' LIMIT 1";
            }
            break;
        case 5:
            // Obtener siguiente número secuencial
            $sql = "SELECT MAX(CAST(Cla_Cod AS UNSIGNED)) AS ultimo_numero 
                    FROM claves_accesos 
                    WHERE Emp_Cod = '$_SESSION[Ses_Emp_Cod]' 
                    AND Cla_Cod REGEXP '^[0-9]+$' 
                    AND LENGTH(Cla_Cod) <= 10";
            break;
        case 6:
            // Anular clave de acceso (cambiar estado a I)
            $sql = "UPDATE claves_accesos SET Cla_Est = 'I' 
                    WHERE Cod_Cla = '$Par_Sql[Cod_Cla]' AND Emp_Cod = '$_SESSION[Ses_Emp_Cod]'";
            break;
        case 7:
            // Contar total de registros
            $search = isset($Par_Sql['search']) && !empty($Par_Sql['search']) ? 
                      "AND (Cla_Cod LIKE '%$Par_Sql[search]%' OR Cla_Des LIKE '%$Par_Sql[search]%' OR Cod_Psc LIKE '%$Par_Sql[search]%')" : "";
            $sql = "SELECT COUNT(*) AS total FROM claves_accesos 
                    WHERE Emp_Cod = '$_SESSION[Ses_Emp_Cod]' $search";
            break;
    }
    return $sql;
}