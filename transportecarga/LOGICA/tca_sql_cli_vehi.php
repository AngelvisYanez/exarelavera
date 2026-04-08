<?php
/**
 * SQL para Vehículos con Clientes
 */
function sentencias_cli_vehi($id,$Par_Sql)
{
    switch($id)
    {	
        // Select para listar vehículos con clientes (para el grid)
        case 1:
            if($Par_Sql['op_opciones']=="d") {
                $search="(persona.Prs_Ape LIKE '%$Par_Sql[search]%' OR persona.Prs_Nom LIKE '%$Par_Sql[search]%')";
            } else if($Par_Sql['op_opciones']=="c") {
                $search="persona.Prs_Ced LIKE '$Par_Sql[search]%'";
            } else if($Par_Sql['op_opciones']=="p") {
                $search="vehiculo.Veh_Pla LIKE '$Par_Sql[search]%'";
            } else {
                $search="1=1";
            }
            
            $where_estado = "";
            if(isset($Par_Sql['isActive']) && $Par_Sql['isActive'] == 'S') {
                $where_estado = "AND vehiculo.Veh_Est='A'";
            }
            
            if(isset($Par_Sql["limits"])){
                $Par_Sql["limits"]="ORDER BY vehiculo.Veh_Pla $Par_Sql[limits]";
                $campos="vehiculo.Veh_Cod, vehiculo.Veh_Pla, vehiculo.Veh_Mar, vehiculo.Veh_Col, vehiculo.Veh_Tit, vehiculo.Veh_Cap, vehiculo.Veh_Est, vehiculo.Cli_Cod,
                        persona.Prs_Ced, persona.Prs_Ced AS Ruc, CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Cliente,
                        persona.Prs_Dir, persona.Prs_Cor,
                        chofer_ultimo.Cho_Cod, chofer_ultimo.Cho_Tli, chofer_ultimo.Cho_Tel,
                        persona_cho.Prs_Ced AS Ruc_Chofer, CONCAT(persona_cho.Prs_Ape,' ',persona_cho.Prs_Nom) AS Chofer";
            } else {
                $campos="COUNT(vehiculo.Veh_Cod) as total";
                $Par_Sql["limits"]="";
            }
            
            $sql = "SELECT $campos 
                    FROM vehiculo
                    INNER JOIN cliente ON vehiculo.Cli_Cod=cliente.Cli_Cod
                    INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                    LEFT JOIN (
                        SELECT viaje.Veh_Cod, viaje.Cho_Cod
                        FROM viaje
                        INNER JOIN (
                            SELECT Veh_Cod, MAX(Via_Cod) AS MaxVia_Cod
                            FROM viaje
                            GROUP BY Veh_Cod
                        ) AS ultimo_viaje ON viaje.Veh_Cod = ultimo_viaje.Veh_Cod AND viaje.Via_Cod = ultimo_viaje.MaxVia_Cod
                    ) AS viaje_ultimo ON vehiculo.Veh_Cod = viaje_ultimo.Veh_Cod
                    LEFT JOIN chofer AS chofer_ultimo ON viaje_ultimo.Cho_Cod = chofer_ultimo.Cho_Cod
                    LEFT JOIN persona AS persona_cho ON chofer_ultimo.Prs_Cod = persona_cho.Prs_Cod
                    WHERE $search AND vehiculo.Emp_Cod=$Par_Sql[Emp_Cod] AND vehiculo.Cli_Cod IS NOT NULL $where_estado $Par_Sql[limits]";
            break;
        
        // INSERT en la tabla vehiculo con cliente
        case 10:
            $sql = "INSERT INTO vehiculo(Cli_Cod, Veh_Pla, Veh_Mar, Veh_Col, Veh_Tit, Veh_Cap, Veh_Est, Emp_Cod) 
                    VALUES(".(!empty($Par_Sql['Cli_Cod']) ? "'$Par_Sql[Cli_Cod]'" : "NULL").",
                           '$Par_Sql[Veh_Pla]',
                           ".(!empty($Par_Sql['Veh_Mar']) ? "'$Par_Sql[Veh_Mar]'" : "NULL").",
                           ".(!empty($Par_Sql['Veh_Col']) ? "'$Par_Sql[Veh_Col]'" : "NULL").",
                           ".(!empty($Par_Sql['Veh_Tit']) ? "'$Par_Sql[Veh_Tit]'" : "NULL").",
                           ".(!empty($Par_Sql['Veh_Cap']) ? "'$Par_Sql[Veh_Cap]'" : "NULL").",
                           'A',
                           '$Par_Sql[Emp_Cod]')";
            break;
        
        // UPDATE en la tabla vehiculo con cliente
        case 11:
            $sql = "UPDATE vehiculo SET 
                    Cli_Cod=".(!empty($Par_Sql['Cli_Cod']) ? "'$Par_Sql[Cli_Cod]'" : "NULL").",
                    Veh_Pla='$Par_Sql[Veh_Pla]',
                    Veh_Mar=".(!empty($Par_Sql['Veh_Mar']) ? "'$Par_Sql[Veh_Mar]'" : "NULL").",
                    Veh_Col=".(!empty($Par_Sql['Veh_Col']) ? "'$Par_Sql[Veh_Col]'" : "NULL").",
                    Veh_Tit=".(!empty($Par_Sql['Veh_Tit']) ? "'$Par_Sql[Veh_Tit]'" : "NULL").",
                    Veh_Cap=".(!empty($Par_Sql['Veh_Cap']) ? "'$Par_Sql[Veh_Cap]'" : "NULL")."
                    WHERE Veh_Cod='$Par_Sql[Veh_Cod]'";
            break;
        
        // ANULAR vehiculo (cambiar estado a I)
        case 20:
            $sql = "UPDATE vehiculo SET Veh_Est='I' WHERE Veh_Cod='$Par_Sql[Veh_Cod]'";
            break;
        
        default:
            $sql = "";
            break;
    }
    return $sql;
}

