<?php
/**
 * Sentencias SQL para el Módulo de Gestión de API Tokens
 * 
 * @package administrador.LOGICA
 * @author EXA Contable
 * @version 2.0
 */

if (!function_exists('sentencias_tok')) {
    /**
     * Retorna consulta SQL a ejecutarse según el identificador de operación
     *
     * @param int $id Identificador de la consulta
     * @param array $Par_Sql Parámetros escapados para la consulta
     * @return string SQL Query
     */
    function sentencias_tok($id, $Par_Sql = array())
    {
        switch ($id) {
            case 1:
                // Listar tokens con información de empresa
                $where = "WHERE 1=1";
                if (!empty($Par_Sql[0])) {
                    $where .= " AND t.Emp_Cod = " . ((int)$Par_Sql[0]);
                }
                if (!empty($Par_Sql[1])) {
                    $where .= " AND t.Tok_Est = '" . addslashes($Par_Sql[1]) . "'";
                }
                return "SELECT t.Tok_Id, t.Tok_Nombre, t.Tok_Hash, t.Tok_Resumen, t.Emp_Cod, t.Tok_Bdd,
                               t.Tok_Cuota, t.Tok_Periodo, t.Tok_Usadas, t.Tok_Periodo_Inicio, t.Tok_Expira,
                               t.Tok_Est, t.Tok_Creado_Por, t.Tok_Creado, t.Tok_Ultimo_Uso,
                               e.Emp_Nom,
                               (SELECT COUNT(1) FROM api_token_permisos p WHERE p.Tok_Id = t.Tok_Id AND p.Tip_Est = 'A') AS Total_Permisos
                          FROM api_tokens t
                          LEFT JOIN empresas e ON e.Emp_Cod = t.Emp_Cod
                         {$where}
                         ORDER BY t.Tok_Id DESC";

            case 2:
                // Obtener token por ID
                $tokId = (int)$Par_Sql[0];
                return "SELECT t.Tok_Id, t.Tok_Nombre, t.Tok_Hash, t.Tok_Resumen, t.Emp_Cod, t.Tok_Bdd,
                               t.Tok_Cuota, t.Tok_Periodo, t.Tok_Usadas, t.Tok_Periodo_Inicio, t.Tok_Expira,
                               t.Tok_Est, t.Tok_Creado_Por, t.Tok_Creado, t.Tok_Ultimo_Uso,
                               e.Emp_Nom
                          FROM api_tokens t
                          LEFT JOIN empresas e ON e.Emp_Cod = t.Emp_Cod
                         WHERE t.Tok_Id = {$tokId}
                         LIMIT 1";

            case 3:
                // Obtener token por Hash
                $hash = addslashes($Par_Sql[0]);
                return "SELECT t.*, e.Emp_Nom
                          FROM api_tokens t
                          LEFT JOIN empresas e ON e.Emp_Cod = t.Emp_Cod
                         WHERE t.Tok_Hash = '{$hash}'
                         LIMIT 1";

            case 4:
                // Listar permisos de un token
                $tokId = (int)$Par_Sql[0];
                return "SELECT Tip_Id, Tok_Id, Tip_Mod, Tip_Ruta, Tip_Est
                          FROM api_token_permisos
                         WHERE Tok_Id = {$tokId} AND Tip_Est = 'A'
                         ORDER BY Tip_Mod ASC, Tip_Ruta ASC";

            case 5:
                // Listar empresas activas con su base de datos distribuida
                return "SELECT e.Emp_Cod, e.Emp_Nom, e.Emp_Ruc, d.Dat_Dis AS Bdd
                          FROM empresas e
                          LEFT JOIN data d ON d.Emp_Cod = e.Emp_Cod
                         WHERE e.Emp_Est = 'A'
                         ORDER BY e.Emp_Nom ASC";

            case 6:
                // Eliminar permisos de un token
                $tokId = (int)$Par_Sql[0];
                return "DELETE FROM api_token_permisos WHERE Tok_Id = {$tokId}";

            case 7:
                // Revocar (inactivar) token
                $tokId = (int)$Par_Sql[0];
                return "UPDATE api_tokens SET Tok_Est = 'I' WHERE Tok_Id = {$tokId}";

            case 8:
                // Eliminar token permanentemente
                $tokId = (int)$Par_Sql[0];
                return "DELETE FROM api_tokens WHERE Tok_Id = {$tokId}";

            default:
                return "";
        }
    }
}
