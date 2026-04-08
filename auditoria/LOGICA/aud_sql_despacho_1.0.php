<?php
/**
 * Sentencias SQL para el módulo Gestión Operativa del Despacho
 * @author Sistema EXA
 * @version 1.0
 * @package auditoria.LOGICA
 *
 * @param int   $id      Número de sentencia
 * @param array $Par_Sql Parámetros
 * @return string Sentencia SQL
 */
function sentencias_despacho($id, $Par_Sql)
{
    $Par_Sql = is_array($Par_Sql) ? $Par_Sql : array();
    $e = function ($v) { return addslashes(trim($v)); };
    $n = function ($v) { return intval($v); };
    $d = function ($v) { return floatval(str_replace(',', '.', $v)); };

    switch ($id) {

        /* 1. Listar servicios (catálogo) */
        case 1:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : 0;
            $wh = "Ser_Est='A'";
            if ($emp > 0) $wh .= " AND (Emp_Cod IS NULL OR Emp_Cod=$emp)";
            return "SELECT Ser_Cod, Ser_Nombre, Ser_Descripcion, Ser_Est, Emp_Cod FROM aud_cat_servicios WHERE $wh ORDER BY Ser_Cod";
            break;

        /* 2. Listar actividades (por servicio opcional) */
        case 2:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : 0;
            $ser = isset($Par_Sql['Ser_Cod']) ? $n($Par_Sql['Ser_Cod']) : 0;
            $wh = "a.Act_Est='A'";
            if ($ser > 0) $wh .= " AND a.Ser_Cod=$ser";
            return "SELECT a.Act_Cod, a.Ser_Cod, s.Ser_Nombre, a.Act_Nombre, a.Act_Tipo, a.Act_Prioridad, a.Act_Recurrente, a.Act_Descripcion, a.Act_Usa_Ruc, a.Act_Meses_Anual " .
                "FROM aud_cat_actividades a INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod WHERE $wh ORDER BY a.Ser_Cod, a.Act_Cod";
            break;

        /* 3. Insertar servicio */
        case 3:
            $nom = isset($Par_Sql['Ser_Nombre_safe']) ? $Par_Sql['Ser_Nombre_safe'] : $e($Par_Sql['Ser_Nombre']);
            $desc = isset($Par_Sql['Ser_Descripcion_safe']) ? $Par_Sql['Ser_Descripcion_safe'] : $e($Par_Sql['Ser_Descripcion']);
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : 0;
            $emp_sql = $emp > 0 ? $emp : 'NULL';
            return "INSERT INTO aud_cat_servicios (Ser_Nombre, Ser_Descripcion, Ser_Est, Emp_Cod) VALUES('$nom','$desc','A',$emp_sql)";
            break;

        /* 4. Actualizar servicio */
        case 4:
            $cod = $n($Par_Sql['Ser_Cod']);
            $nom = isset($Par_Sql['Ser_Nombre_safe']) ? $Par_Sql['Ser_Nombre_safe'] : $e($Par_Sql['Ser_Nombre']);
            $desc = isset($Par_Sql['Ser_Descripcion_safe']) ? $Par_Sql['Ser_Descripcion_safe'] : $e($Par_Sql['Ser_Descripcion']);
            $est = isset($Par_Sql['Ser_Est']) ? $e($Par_Sql['Ser_Est']) : 'A';
            return "UPDATE aud_cat_servicios SET Ser_Nombre='$nom', Ser_Descripcion='$desc', Ser_Est='$est' WHERE Ser_Cod=$cod";
            break;

        /* 5. Insertar actividad */
        case 5:
            $ser = $n($Par_Sql['Ser_Cod']);
            $nom = isset($Par_Sql['Act_Nombre_safe']) ? $Par_Sql['Act_Nombre_safe'] : $e($Par_Sql['Act_Nombre']);
            $tipo = isset($Par_Sql['Act_Tipo']) ? $e($Par_Sql['Act_Tipo']) : 'MENSUAL';
            $prior = isset($Par_Sql['Act_Prioridad']) ? $e($Par_Sql['Act_Prioridad']) : 'MEDIA';
            $rec = isset($Par_Sql['Act_Recurrente']) ? $e($Par_Sql['Act_Recurrente']) : 'S';
            $desc = isset($Par_Sql['Act_Descripcion_safe']) ? $Par_Sql['Act_Descripcion_safe'] : $e($Par_Sql['Act_Descripcion']);
            $usa_ruc = isset($Par_Sql['Act_Usa_Ruc']) && $Par_Sql['Act_Usa_Ruc'] === 'S' ? 'S' : 'N';
            $meses_anual = isset($Par_Sql['Act_Meses_Anual']) ? $e($Par_Sql['Act_Meses_Anual']) : '';
            return "INSERT INTO aud_cat_actividades (Ser_Cod, Act_Nombre, Act_Tipo, Act_Prioridad, Act_Recurrente, Act_Descripcion, Act_Usa_Ruc, Act_Meses_Anual, Act_Est) " .
                "VALUES($ser,'$nom','$tipo','$prior','$rec','$desc','$usa_ruc','$meses_anual','A')";
            break;

        /* 6. Actualizar actividad */
        case 6:
            $cod = $n($Par_Sql['Act_Cod']);
            $nom = isset($Par_Sql['Act_Nombre_safe']) ? $Par_Sql['Act_Nombre_safe'] : $e($Par_Sql['Act_Nombre']);
            $tipo = isset($Par_Sql['Act_Tipo']) ? $e($Par_Sql['Act_Tipo']) : 'MENSUAL';
            $prior = isset($Par_Sql['Act_Prioridad']) ? $e($Par_Sql['Act_Prioridad']) : 'MEDIA';
            $rec = isset($Par_Sql['Act_Recurrente']) ? $e($Par_Sql['Act_Recurrente']) : 'S';
            $desc = isset($Par_Sql['Act_Descripcion_safe']) ? $Par_Sql['Act_Descripcion_safe'] : $e($Par_Sql['Act_Descripcion']);
            $usa_ruc = isset($Par_Sql['Act_Usa_Ruc']) && $Par_Sql['Act_Usa_Ruc'] === 'S' ? 'S' : 'N';
            $est = isset($Par_Sql['Act_Est']) ? $e($Par_Sql['Act_Est']) : 'A';
            $meses_anual = isset($Par_Sql['Act_Meses_Anual']) ? $e($Par_Sql['Act_Meses_Anual']) : '';
            return "UPDATE aud_cat_actividades SET Act_Nombre='$nom', Act_Tipo='$tipo', Act_Prioridad='$prior', Act_Recurrente='$rec', Act_Descripcion='$desc', Act_Usa_Ruc='$usa_ruc', Act_Meses_Anual='$meses_anual', Act_Est='$est' WHERE Act_Cod=$cod";
            break;

        /* 7. Listar clientes despacho activos (con datos persona y fechas del contrato vigente) */
        case 7:
            $emp = $n($Par_Sql['Emp_Cod']);
            $est = isset($Par_Sql['Dcl_Est']) ? $e($Par_Sql['Dcl_Est']) : '';
            $wh = "dc.Emp_Cod=$emp AND c.Cli_Est='A'";
            if ($est !== '') $wh .= " AND dc.Dcl_Est='$est'";
            return "SELECT dc.Dcl_Cod, dc.Cli_Cod, dc.Dcl_Est, dc.Dcl_Fecha_Inicio, dc.Dcl_Fecha_Fin, dc.Dcl_Observaciones, dc.Reg_Cod, dc.Dcl_Tipo_Empresa, " .
                "r.Reg_Nombre AS Reg_Nombre, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre, p.Prs_Ced, c.Cli_Ruf, " .
                "(SELECT con.Con_Fecha_Inicio FROM aud_despacho_contratos con WHERE con.Dcl_Cod=dc.Dcl_Cod AND con.Con_Est='VIGENTE' ORDER BY con.Con_Cod DESC LIMIT 1) AS Con_Fecha_Inicio, " .
                "(SELECT con.Con_Fecha_Fin FROM aud_despacho_contratos con WHERE con.Dcl_Cod=dc.Dcl_Cod AND con.Con_Est='VIGENTE' ORDER BY con.Con_Cod DESC LIMIT 1) AS Con_Fecha_Fin " .
                "FROM aud_despacho_clientes dc INNER JOIN cliente c ON dc.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "LEFT JOIN aud_cat_regimen r ON dc.Reg_Cod=r.Reg_Cod " .
                "WHERE $wh ORDER BY p.Prs_Ape, p.Prs_Nom";
            break;

        /* 8. Insertar cliente despacho */
        case 8:
            $cli = $n($Par_Sql['Cli_Cod']);
            $emp = $n($Par_Sql['Emp_Cod']);
            $est = isset($Par_Sql['Dcl_Est']) ? $e($Par_Sql['Dcl_Est']) : 'ACTIVO';
            $fec_ini = isset($Par_Sql['Dcl_Fecha_Inicio']) ? "'" . $e($Par_Sql['Dcl_Fecha_Inicio']) . "'" : 'NULL';
            $fec_fin = isset($Par_Sql['Dcl_Fecha_Fin']) && trim($Par_Sql['Dcl_Fecha_Fin']) !== '' ? "'" . $e($Par_Sql['Dcl_Fecha_Fin']) . "'" : 'NULL';
            $obs = isset($Par_Sql['Dcl_Observaciones_safe']) ? $Par_Sql['Dcl_Observaciones_safe'] : $e($Par_Sql['Dcl_Observaciones']);
            $reg = isset($Par_Sql['Reg_Cod']) ? $n($Par_Sql['Reg_Cod']) : 0;
            $reg_sql = $reg > 0 ? $reg : 'NULL';
            $tipo_emp = isset($Par_Sql['Dcl_Tipo_Empresa']) && in_array($Par_Sql['Dcl_Tipo_Empresa'], array('PEQUENO','MEDIANO','GRANDE')) ? "'" . $Par_Sql['Dcl_Tipo_Empresa'] . "'" : 'NULL';
            return "INSERT INTO aud_despacho_clientes (Cli_Cod, Emp_Cod, Dcl_Est, Dcl_Fecha_Inicio, Dcl_Fecha_Fin, Dcl_Observaciones, Reg_Cod, Dcl_Tipo_Empresa) " .
                "VALUES($cli,$emp,'$est',$fec_ini,$fec_fin,'$obs',$reg_sql,$tipo_emp)";
            break;

        /* 9. Actualizar cliente despacho */
        case 9:
            $cod = $n($Par_Sql['Dcl_Cod']);
            $est = isset($Par_Sql['Dcl_Est']) ? $e($Par_Sql['Dcl_Est']) : 'ACTIVO';
            $fec_ini = isset($Par_Sql['Dcl_Fecha_Inicio']) ? "'" . $e($Par_Sql['Dcl_Fecha_Inicio']) . "'" : 'NULL';
            $fec_fin = isset($Par_Sql['Dcl_Fecha_Fin']) && trim($Par_Sql['Dcl_Fecha_Fin']) !== '' ? "'" . $e($Par_Sql['Dcl_Fecha_Fin']) . "'" : 'NULL';
            $obs = isset($Par_Sql['Dcl_Observaciones_safe']) ? $Par_Sql['Dcl_Observaciones_safe'] : $e($Par_Sql['Dcl_Observaciones']);
            $reg = isset($Par_Sql['Reg_Cod']) ? $n($Par_Sql['Reg_Cod']) : 0;
            $reg_sql = $reg > 0 ? $reg : 'NULL';
            $tipo_emp = isset($Par_Sql['Dcl_Tipo_Empresa']) && in_array($Par_Sql['Dcl_Tipo_Empresa'], array('PEQUENO','MEDIANO','GRANDE')) ? "'" . $Par_Sql['Dcl_Tipo_Empresa'] . "'" : 'NULL';
            return "UPDATE aud_despacho_clientes SET Dcl_Est='$est', Dcl_Fecha_Inicio=$fec_ini, Dcl_Fecha_Fin=$fec_fin, Dcl_Observaciones='$obs', Reg_Cod=$reg_sql, Dcl_Tipo_Empresa=$tipo_emp WHERE Dcl_Cod=$cod";
            break;

        /* 10. Listar clientes (tesoreria) para combo - activos en empresa, no necesariamente en despacho */
        case 10:
            $emp = $n($Par_Sql['Emp_Cod']);
            $bus = isset($Par_Sql['search']) ? $e($Par_Sql['search']) : '';
            $wh = "c.Emp_Cod=$emp AND c.Cli_Est='A'";
            if ($bus !== '') $wh .= " AND (p.Prs_Ced LIKE '%$bus%' OR p.Prs_Nom LIKE '%$bus%' OR p.Prs_Ape LIKE '%$bus%')";
            return "SELECT c.Cli_Cod, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Nombre, p.Prs_Ced, c.Cli_Ruf " .
                "FROM cliente c INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod WHERE $wh ORDER BY p.Prs_Ape, p.Prs_Nom LIMIT 100";
            break;

        /* 11. Verificar si cliente ya está en despacho */
        case 11:
            $cli = $n($Par_Sql['Cli_Cod']);
            $emp = $n($Par_Sql['Emp_Cod']);
            return "SELECT Dcl_Cod FROM aud_despacho_clientes WHERE Cli_Cod=$cli AND Emp_Cod=$emp LIMIT 1";
            break;

        /* 12. Listar contratos por cliente despacho */
        case 12:
            $dcl = isset($Par_Sql['Dcl_Cod']) ? $n($Par_Sql['Dcl_Cod']) : 0;
            $emp = $n($Par_Sql['Emp_Cod']);
            $wh = "dc.Emp_Cod=$emp";
            if ($dcl > 0) $wh .= " AND con.Dcl_Cod=$dcl";
            return "SELECT con.Con_Cod, con.Dcl_Cod, dc.Cli_Cod, dc.Reg_Cod, con.Con_Numero, con.Con_Fecha_Inicio, con.Con_Fecha_Fin, con.Con_Tipo, con.Con_Meses_Anual, con.Con_Valor, con.Con_Est, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre " .
                "FROM aud_despacho_contratos con INNER JOIN aud_despacho_clientes dc ON con.Dcl_Cod=dc.Dcl_Cod " .
                "INNER JOIN cliente c ON dc.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh ORDER BY con.Con_Cod DESC";
            break;

        /* 13. Insertar contrato */
        case 13:
            $dcl = $n($Par_Sql['Dcl_Cod']);
            $num = isset($Par_Sql['Con_Numero_safe']) ? $Par_Sql['Con_Numero_safe'] : $e($Par_Sql['Con_Numero']);
            $fec_ini = $e($Par_Sql['Con_Fecha_Inicio']);
            $fec_fin_raw = isset($Par_Sql['Con_Fecha_Fin']) ? trim($Par_Sql['Con_Fecha_Fin']) : '';
            $fec_fin = ($fec_fin_raw !== '') ? "'" . $e($fec_fin_raw) . "'" : 'NULL';
            $tipo = isset($Par_Sql['Con_Tipo']) ? $e($Par_Sql['Con_Tipo']) : 'MENSUAL';
            $val = isset($Par_Sql['Con_Valor']) ? $d($Par_Sql['Con_Valor']) : 0;
            $meses_anual = isset($Par_Sql['Con_Meses_Anual']) ? $e($Par_Sql['Con_Meses_Anual']) : '';
            return "INSERT INTO aud_despacho_contratos (Dcl_Cod, Con_Numero, Con_Fecha_Inicio, Con_Fecha_Fin, Con_Tipo, Con_Meses_Anual, Con_Valor, Con_Est) " .
                "VALUES($dcl,'$num','$fec_ini',$fec_fin,'$tipo','$meses_anual',$val,'VIGENTE')";
            break;

        /* 14. Actualizar contrato */
        case 14:
            $cod = $n($Par_Sql['Con_Cod']);
            $num = isset($Par_Sql['Con_Numero_safe']) ? $Par_Sql['Con_Numero_safe'] : $e($Par_Sql['Con_Numero']);
            $fec_ini = $e($Par_Sql['Con_Fecha_Inicio']);
            $fec_fin_raw = isset($Par_Sql['Con_Fecha_Fin']) ? trim($Par_Sql['Con_Fecha_Fin']) : '';
            $fec_fin_sql = ($fec_fin_raw !== '') ? "'" . $e($fec_fin_raw) . "'" : 'NULL';
            $tipo = isset($Par_Sql['Con_Tipo']) ? $e($Par_Sql['Con_Tipo']) : 'MENSUAL';
            $val = isset($Par_Sql['Con_Valor']) ? $d($Par_Sql['Con_Valor']) : 0;
            $est = isset($Par_Sql['Con_Est']) ? $e($Par_Sql['Con_Est']) : 'VIGENTE';
            $meses_anual = isset($Par_Sql['Con_Meses_Anual']) ? $e($Par_Sql['Con_Meses_Anual']) : '';
            return "UPDATE aud_despacho_contratos SET Con_Numero='$num', Con_Fecha_Inicio='$fec_ini', Con_Fecha_Fin=$fec_fin_sql, Con_Tipo='$tipo', Con_Meses_Anual='$meses_anual', Con_Valor=$val, Con_Est='$est' WHERE Con_Cod=$cod";
            break;

        /* 15. Servicios por contrato (incluye cantidad de actividades por servicio) */
        case 15:
            $con = $n($Par_Sql['Con_Cod']);
            return "SELECT cs.ConSer_Cod, cs.Con_Cod, cs.Ser_Cod, s.Ser_Nombre, cs.Incluido, cs.Facturable, cs.Valor_Unitario, cs.Activo, " .
                "(SELECT COUNT(*) FROM aud_contrato_actividades ca INNER JOIN aud_cat_actividades a ON ca.Act_Cod=a.Act_Cod WHERE ca.Con_Cod=cs.Con_Cod AND a.Ser_Cod=cs.Ser_Cod) AS Cnt_Actividades " .
                "FROM aud_contrato_servicios cs INNER JOIN aud_cat_servicios s ON cs.Ser_Cod=s.Ser_Cod WHERE cs.Con_Cod=$con ORDER BY s.Ser_Nombre";
            break;

        /* 16. Actividades por contrato */
        case 16:
            $con = $n($Par_Sql['Con_Cod']);
            return "SELECT ca.ConAct_Cod, ca.Con_Cod, ca.Act_Cod, a.Act_Nombre, a.Ser_Cod, s.Ser_Nombre, ca.Periodicidad, ca.Incluida, ca.Facturable, ca.Valor_Unitario, ca.Activa " .
                "FROM aud_contrato_actividades ca INNER JOIN aud_cat_actividades a ON ca.Act_Cod=a.Act_Cod INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "WHERE ca.Con_Cod=$con ORDER BY s.Ser_Nombre, a.Act_Nombre";
            break;

        /* 17. Insertar servicio contrato */
        case 17:
            $con = $n($Par_Sql['Con_Cod']);
            $ser = $n($Par_Sql['Ser_Cod']);
            $incl = isset($Par_Sql['Incluido']) ? $e($Par_Sql['Incluido']) : 'S';
            $fact = isset($Par_Sql['Facturable']) ? $e($Par_Sql['Facturable']) : 'N';
            $val = isset($Par_Sql['Valor_Unitario']) ? $d($Par_Sql['Valor_Unitario']) : 0;
            return "INSERT INTO aud_contrato_servicios (Con_Cod, Ser_Cod, Incluido, Facturable, Valor_Unitario, Activo) VALUES($con,$ser,'$incl','$fact',$val,'S')";
            break;

        /* 18. Insertar actividad contrato */
        case 18:
            $con = $n($Par_Sql['Con_Cod']);
            $act = $n($Par_Sql['Act_Cod']);
            $per = isset($Par_Sql['Periodicidad']) && $Par_Sql['Periodicidad'] !== '' ? "'" . $e($Par_Sql['Periodicidad']) . "'" : 'NULL';
            $incl = isset($Par_Sql['Incluida']) ? $e($Par_Sql['Incluida']) : 'S';
            $fact = isset($Par_Sql['Facturable']) ? $e($Par_Sql['Facturable']) : 'N';
            $val = isset($Par_Sql['Valor_Unitario']) ? $d($Par_Sql['Valor_Unitario']) : 0;
            return "INSERT INTO aud_contrato_actividades (Con_Cod, Act_Cod, Periodicidad, Incluida, Facturable, Valor_Unitario, Activa) " .
                "VALUES($con,$act,$per,'$incl','$fact',$val,'S')";
            break;

        /* 19. Eliminar servicio contrato */
        case 19:
            $cod = $n($Par_Sql['ConSer_Cod']);
            return "DELETE FROM aud_contrato_servicios WHERE ConSer_Cod=$cod";
            break;

        /* 20. Eliminar actividad contrato */
        case 20:
            $cod = $n($Par_Sql['ConAct_Cod']);
            return "DELETE FROM aud_contrato_actividades WHERE ConAct_Cod=$cod";
            break;

        /* 21. Listar reglas asignación */
        case 21:
            $emp = $n($Par_Sql['Emp_Cod']);
            return "SELECT r.Reg_Cod, r.Reg_Tipo, r.Act_Cod, a.Act_Nombre, r.Reg_Alcance, r.Reg_Descripcion, r.Reg_Est " .
                "FROM aud_reglas_asignacion r LEFT JOIN aud_cat_actividades a ON r.Act_Cod=a.Act_Cod WHERE r.Emp_Cod=$emp ORDER BY r.Reg_Cod";
            break;

        /* 22. Insertar regla */
        case 22:
            $tipo = $e($Par_Sql['Reg_Tipo']);
            $act = isset($Par_Sql['Act_Cod']) && $Par_Sql['Act_Cod'] > 0 ? $n($Par_Sql['Act_Cod']) : 'NULL';
            $alc = isset($Par_Sql['Reg_Alcance']) ? $e($Par_Sql['Reg_Alcance']) : 'UNO';
            $desc = isset($Par_Sql['Reg_Descripcion_safe']) ? $Par_Sql['Reg_Descripcion_safe'] : $e($Par_Sql['Reg_Descripcion']);
            $emp = $n($Par_Sql['Emp_Cod']);
            return "INSERT INTO aud_reglas_asignacion (Reg_Tipo, Act_Cod, Reg_Alcance, Reg_Descripcion, Reg_Est, Emp_Cod) " .
                "VALUES('$tipo',$act,'$alc','$desc','A',$emp)";
            break;

        /* 23. Actualizar regla */
        case 23:
            $cod = $n($Par_Sql['Reg_Cod']);
            $alc = isset($Par_Sql['Reg_Alcance']) ? $e($Par_Sql['Reg_Alcance']) : 'UNO';
            $desc = isset($Par_Sql['Reg_Descripcion_safe']) ? $Par_Sql['Reg_Descripcion_safe'] : $e($Par_Sql['Reg_Descripcion']);
            $est = isset($Par_Sql['Reg_Est']) ? $e($Par_Sql['Reg_Est']) : 'A';
            return "UPDATE aud_reglas_asignacion SET Reg_Alcance='$alc', Reg_Descripcion='$desc', Reg_Est='$est' WHERE Reg_Cod=$cod";
            break;

        /* 24. Clientes de regla */
        case 24:
            $reg = $n($Par_Sql['Reg_Cod']);
            return "SELECT rc.RegCli_Cod, rc.Reg_Cod, rc.Cli_Cod, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre " .
                "FROM aud_regla_clientes rc INNER JOIN cliente c ON rc.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod WHERE rc.Reg_Cod=$reg";
            break;

        /* 25. Usuarios de regla */
        case 25:
            $reg = $n($Par_Sql['Reg_Cod']);
            return "SELECT ru.RegUsu_Cod, ru.Reg_Cod, ru.Per_Cod, ru.Activo, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Nombre " .
                "FROM aud_regla_usuarios ru INNER JOIN personal per ON ru.Per_Cod=per.Per_Cod INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod WHERE ru.Reg_Cod=$reg";
            break;

        /* 26. Insertar cliente en regla */
        case 26:
            $reg = $n($Par_Sql['Reg_Cod']);
            $cli = $n($Par_Sql['Cli_Cod']);
            return "INSERT IGNORE INTO aud_regla_clientes (Reg_Cod, Cli_Cod) VALUES($reg,$cli)";
            break;

        /* 27. Insertar usuario en regla */
        case 27:
            $reg = $n($Par_Sql['Reg_Cod']);
            $per = $n($Par_Sql['Per_Cod']);
            return "INSERT IGNORE INTO aud_regla_usuarios (Reg_Cod, Per_Cod, Activo) VALUES($reg,$per,'S')";
            break;

        /* 28. Eliminar cliente de regla */
        case 28:
            $cod = $n($Par_Sql['RegCli_Cod']);
            return "DELETE FROM aud_regla_clientes WHERE RegCli_Cod=$cod";
            break;

        /* 29. Eliminar usuario de regla */
        case 29:
            $cod = $n($Par_Sql['RegUsu_Cod']);
            return "DELETE FROM aud_regla_usuarios WHERE RegUsu_Cod=$cod";
            break;

        /* 30. Contratos vigentes en periodo (para generador) - Fecha_Ini/Fecha_Fin opcionales desde PHP */
        case 30:
            $emp = $n($Par_Sql['Emp_Cod']);
            $fec_ini = isset($Par_Sql['Fecha_Ini']) ? $e($Par_Sql['Fecha_Ini']) : '';
            $fec_fin = isset($Par_Sql['Fecha_Fin']) ? $e($Par_Sql['Fecha_Fin']) : '';
            if ($fec_ini === '' || $fec_fin === '') {
                $periodo = $e($Par_Sql['Tar_Periodo']);
                $es_anual = (strlen($periodo) === 4);
                if ($es_anual) {
                    $fec_ini = $periodo . '-01-01';
                    $fec_fin = $periodo . '-12-31';
                } else {
                    $fec_ini = $periodo . '-01';
                    $fec_fin = date('Y-m-t', strtotime($fec_ini));
                }
            }
            return "SELECT con.Con_Cod, con.Dcl_Cod, dc.Cli_Cod, dc.Emp_Cod " .
                "FROM aud_despacho_contratos con INNER JOIN aud_despacho_clientes dc ON con.Dcl_Cod=dc.Dcl_Cod " .
                "INNER JOIN cliente c ON dc.Cli_Cod=c.Cli_Cod " .
                "WHERE con.Con_Est='VIGENTE' AND dc.Dcl_Est='ACTIVO' AND dc.Emp_Cod=$emp AND c.Cli_Est='A' " .
                "AND con.Con_Fecha_Inicio<='$fec_fin' AND (con.Con_Fecha_Fin IS NULL OR con.Con_Fecha_Fin>='$fec_ini')";
            break;

        /* 31. Insertar tarea despacho */
        case 31:
            $cli = $n($Par_Sql['Cli_Cod']);
            $act = $n($Par_Sql['Act_Cod']);
            $per = $e($Par_Sql['Tar_Periodo']);
            $fec_lim = isset($Par_Sql['Tar_Fecha_Limite']) && trim($Par_Sql['Tar_Fecha_Limite']) !== '' ? "'" . $e($Par_Sql['Tar_Fecha_Limite']) . "'" : 'NULL';
            $reg = isset($Par_Sql['Reg_Cod']) && $Par_Sql['Reg_Cod'] > 0 ? $n($Par_Sql['Reg_Cod']) : 'NULL';
            $con = isset($Par_Sql['Con_Cod']) && $Par_Sql['Con_Cod'] > 0 ? $n($Par_Sql['Con_Cod']) : 'NULL';
            $emp = $n($Par_Sql['Emp_Cod']);
            $obs = isset($Par_Sql['Tar_Observaciones_safe']) ? $Par_Sql['Tar_Observaciones_safe'] : $e($Par_Sql['Tar_Observaciones']);
            return "INSERT INTO aud_despacho_tareas (Cli_Cod, Act_Cod, Tar_Periodo, Tar_Fecha_Limite, Tar_Est, Reg_Cod, Con_Cod, Emp_Cod, Tar_Observaciones) " .
                "VALUES($cli,$act,'$per',$fec_lim,'PENDIENTE',$reg,$con,$emp,'$obs')";
            break;

        /* 32. Verificar tarea existe (Cli+Act+Periodo) - retorna Tar_Cod, Tar_Est */
        case 32:
            $cli = $n($Par_Sql['Cli_Cod']);
            $act = $n($Par_Sql['Act_Cod']);
            $per = $e($Par_Sql['Tar_Periodo']);
            return "SELECT Tar_Cod, Tar_Est FROM aud_despacho_tareas WHERE Cli_Cod=$cli AND Act_Cod=$act AND Tar_Periodo='$per' LIMIT 1";
            break;

        /* 33. Asignar usuario a tarea */
        case 33:
            $tar = $n($Par_Sql['Tar_Cod']);
            $per = $n($Par_Sql['Per_Cod']);
            $porc = isset($Par_Sql['TarUsu_Porcentaje']) ? min(100, max(0, $n($Par_Sql['TarUsu_Porcentaje']))) : 0;
            $fec = date('Y-m-d H:i:s');
            return "INSERT INTO aud_despacho_tarea_usuarios (Tar_Cod, Per_Cod, TarUsu_Est, TarUsu_Porcentaje, TarUsu_Fecha_Ult) " .
                "VALUES($tar,$per,'ACTIVO',$porc,'$fec') ON DUPLICATE KEY UPDATE TarUsu_Porcentaje=$porc, TarUsu_Fecha_Ult='$fec'";
            break;

        /* 34. Grid tareas despacho paginado */
        case 34:
            if (empty($Par_Sql['limits']) || $Par_Sql['limits'] === "" || $Par_Sql['limits'] === null) {
                $campos = "COUNT(t.Tar_Cod) as total";
            } else {
                $ord = !empty($Par_Sql['sidx']) ? addslashes($Par_Sql['sidx']) : 't.Tar_Fecha_Limite';
                $dir = (!empty($Par_Sql['sord']) && strtoupper($Par_Sql['sord']) === 'DESC') ? 'DESC' : 'ASC';
                $Par_Sql['limits'] = "ORDER BY $ord $dir " . $Par_Sql['limits'];
                $campos = "t.Tar_Cod, t.Cli_Cod, t.Act_Cod, t.Tar_Periodo, t.Tar_Fecha_Limite, t.Tar_Est, t.Con_Cod, " .
                    "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre, a.Act_Nombre, s.Ser_Nombre";
            }
            $wh = "1=1";
            if (!empty($Par_Sql['Emp_Cod'])) $wh .= " AND t.Emp_Cod=" . $n($Par_Sql['Emp_Cod']);
            if (!empty($Par_Sql['Tar_Periodo'])) $wh .= " AND t.Tar_Periodo='" . $e($Par_Sql['Tar_Periodo']) . "'";
            if (!empty($Par_Sql['Tar_Est'])) $wh .= " AND t.Tar_Est='" . $e($Par_Sql['Tar_Est']) . "'";
            if (!empty($Par_Sql['Cli_Cod'])) $wh .= " AND t.Cli_Cod=" . $n($Par_Sql['Cli_Cod']);
            $search = "1=1";
            if (!empty($Par_Sql['search'])) {
                $bus = $e($Par_Sql['search']);
                $search = "(p.Prs_Nom LIKE '%$bus%' OR p.Prs_Ape LIKE '%$bus%' OR a.Act_Nombre LIKE '%$bus%')";
            }
            $sql = "SELECT $campos FROM aud_despacho_tareas t " .
                "INNER JOIN cliente c ON t.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "INNER JOIN aud_cat_actividades a ON t.Act_Cod=a.Act_Cod INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "WHERE $wh AND $search";
            if (!empty($Par_Sql['limits']) && $Par_Sql['limits'] !== "" && $Par_Sql['limits'] !== null) {
                $sql .= " " . $Par_Sql['limits'];
            }
            return $sql;
            break;

        /* 35. Listar personal (para combos) */
        case 35:
            $emp = $n($Par_Sql['Emp_Cod']);
            return "SELECT per.Per_Cod, p.Prs_Ced, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Nombre FROM personal per " .
                "INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod WHERE per.Per_Est='A' AND per.Emp_Cod=$emp ORDER BY p.Prs_Ape, p.Prs_Nom";
            break;

        /* 36. KPI dashboard por periodo o rango de fechas (Tar_Periodo o FechaDesde+FechaHasta) */
        case 36:
            $emp = $n($Par_Sql['Emp_Cod']);
            $per = isset($Par_Sql['Tar_Periodo']) ? $e($Par_Sql['Tar_Periodo']) : '';
            $fdesde = isset($Par_Sql['FechaDesde']) && trim($Par_Sql['FechaDesde']) !== '' ? $e($Par_Sql['FechaDesde']) : '';
            $fhasta = isset($Par_Sql['FechaHasta']) && trim($Par_Sql['FechaHasta']) !== '' ? $e($Par_Sql['FechaHasta']) : '';
            $wh = "t.Emp_Cod=$emp";
            if ($fdesde !== '' && $fhasta !== '') {
                $wh .= " AND t.Tar_Fecha_Limite >= '$fdesde' AND t.Tar_Fecha_Limite <= '$fhasta'";
                $whSub = "t2.Emp_Cod=$emp AND t2.Tar_Fecha_Limite >= '$fdesde' AND t2.Tar_Fecha_Limite <= '$fhasta'";
            } else {
                if ($per !== '') $wh .= " AND t.Tar_Periodo='$per'";
                $whSub = "t2.Emp_Cod=$emp" . ($per !== '' ? " AND t2.Tar_Periodo='$per'" : "");
            }
            return "SELECT COUNT(*) AS Total_Tareas, " .
                "SUM(CASE WHEN t.Tar_Est='FINALIZADA' THEN 1 ELSE 0 END) AS Completadas, " .
                "SUM(CASE WHEN t.Tar_Est='VENCIDA' THEN 1 ELSE 0 END) AS Vencidas, " .
                "SUM(CASE WHEN t.Tar_Est IN ('PENDIENTE','EN_PROCESO','OBSERVADA') AND t.Tar_Fecha_Limite IS NOT NULL AND t.Tar_Fecha_Limite < CURDATE() THEN 1 ELSE 0 END) AS Atrasadas, " .
                "SUM(CASE WHEN t.Tar_Est='PENDIENTE' THEN 1 ELSE 0 END) AS Pendientes, " .
                "SUM(CASE WHEN t.Tar_Est='EN_PROCESO' THEN 1 ELSE 0 END) AS En_Proceso, " .
                "SUM(CASE WHEN t.Tar_Est='OBSERVADA' THEN 1 ELSE 0 END) AS Observadas, " .
                "(SELECT COUNT(DISTINCT t2.Tar_Cod) FROM aud_despacho_tareas t2 INNER JOIN aud_despacho_tarea_usuarios tu ON tu.Tar_Cod=t2.Tar_Cod AND tu.TarUsu_Est='ACTIVO' WHERE $whSub) AS Con_Asignacion " .
                "FROM aud_despacho_tareas t WHERE $wh";
            break;

        /* 85. Dashboard: tareas por servicio (para gráfico) */
        case 85:
            $emp = $n($Par_Sql['Emp_Cod']);
            $per = isset($Par_Sql['Tar_Periodo']) ? $e($Par_Sql['Tar_Periodo']) : '';
            $fdesde = isset($Par_Sql['FechaDesde']) && trim($Par_Sql['FechaDesde']) !== '' ? $e($Par_Sql['FechaDesde']) : '';
            $fhasta = isset($Par_Sql['FechaHasta']) && trim($Par_Sql['FechaHasta']) !== '' ? $e($Par_Sql['FechaHasta']) : '';
            $wh = "t.Emp_Cod=$emp";
            if ($fdesde !== '' && $fhasta !== '') $wh .= " AND t.Tar_Fecha_Limite >= '$fdesde' AND t.Tar_Fecha_Limite <= '$fhasta'";
            elseif ($per !== '') $wh .= " AND t.Tar_Periodo='$per'";
            return "SELECT s.Ser_Nombre, COUNT(*) AS Cnt FROM aud_despacho_tareas t " .
                "INNER JOIN aud_cat_actividades a ON t.Act_Cod=a.Act_Cod INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "WHERE $wh GROUP BY a.Ser_Cod, s.Ser_Nombre ORDER BY Cnt DESC";
            break;

        /* 86. Dashboard: top clientes por cantidad de tareas */
        case 86:
            $emp = $n($Par_Sql['Emp_Cod']);
            $per = isset($Par_Sql['Tar_Periodo']) ? $e($Par_Sql['Tar_Periodo']) : '';
            $fdesde = isset($Par_Sql['FechaDesde']) && trim($Par_Sql['FechaDesde']) !== '' ? $e($Par_Sql['FechaDesde']) : '';
            $fhasta = isset($Par_Sql['FechaHasta']) && trim($Par_Sql['FechaHasta']) !== '' ? $e($Par_Sql['FechaHasta']) : '';
            $wh = "t.Emp_Cod=$emp";
            if ($fdesde !== '' && $fhasta !== '') $wh .= " AND t.Tar_Fecha_Limite >= '$fdesde' AND t.Tar_Fecha_Limite <= '$fhasta'";
            elseif ($per !== '') $wh .= " AND t.Tar_Periodo='$per'";
            return "SELECT CONCAT(COALESCE(p.Prs_Ape,''),' ',COALESCE(p.Prs_Nom,'')) AS Cliente_Nombre, COUNT(*) AS Cnt " .
                "FROM aud_despacho_tareas t INNER JOIN cliente c ON t.Cli_Cod=c.Cli_Cod LEFT JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh GROUP BY t.Cli_Cod ORDER BY Cnt DESC LIMIT 10";
            break;

        /* 37. Mis tareas (por Per_Cod) */
        case 37:
            $per = $n($Par_Sql['Per_Cod']);
            $emp = $n($Par_Sql['Emp_Cod']);
            return "SELECT t.Tar_Cod, t.Cli_Cod, t.Act_Cod, t.Tar_Periodo, t.Tar_Fecha_Limite, t.Tar_Fecha_Culminacion, t.Tar_Observaciones, t.Tar_Est, tu.TarUsu_Porcentaje, tu.TarUsu_Fecha_Ult, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre, a.Act_Nombre, s.Ser_Nombre " .
                "FROM aud_despacho_tarea_usuarios tu INNER JOIN aud_despacho_tareas t ON tu.Tar_Cod=t.Tar_Cod " .
                "INNER JOIN cliente c ON t.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "INNER JOIN aud_cat_actividades a ON t.Act_Cod=a.Act_Cod INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "WHERE tu.Per_Cod=$per AND tu.TarUsu_Est='ACTIVO' AND t.Emp_Cod=$emp ORDER BY t.Tar_Fecha_Limite ASC";
            break;

        /* 38. Actualizar porcentaje tarea usuario */
        case 38:
            $tar = $n($Par_Sql['Tar_Cod']);
            $per = $n($Par_Sql['Per_Cod']);
            $porc = min(100, max(0, $n($Par_Sql['TarUsu_Porcentaje'])));
            $obsUsu = isset($Par_Sql['TarUsu_Observacion']) ? $e($Par_Sql['TarUsu_Observacion']) : (isset($Par_Sql['Tar_Observaciones']) ? $e($Par_Sql['Tar_Observaciones']) : '');
            $fec = date('Y-m-d H:i:s');
            $fecHoy = date('Y-m-d');
            $est = '';
            if ($porc >= 100) {
                $est = "UPDATE aud_despacho_tareas SET Tar_Est='FINALIZADA', Tar_Fecha_Culminacion='$fecHoy' WHERE Tar_Cod=$tar; ";
            } elseif ($porc > 0) {
                $est = "UPDATE aud_despacho_tareas SET Tar_Est='EN_PROCESO' WHERE Tar_Cod=$tar; ";
            }
            $updUsu = "UPDATE aud_despacho_tarea_usuarios SET TarUsu_Porcentaje=$porc, TarUsu_Fecha_Ult='$fec'";
            if ($obsUsu !== '') {
                $updUsu .= ", TarUsu_Observacion='$obsUsu'";
            }
            return $est . $updUsu . " WHERE Tar_Cod=$tar AND Per_Cod=$per";
            break;

        /* 38b. Actualizar solo porcentaje (sin TarUsu_Observacion, para BD sin migración) */
        case 80:
            $tar = $n($Par_Sql['Tar_Cod']);
            $per = $n($Par_Sql['Per_Cod']);
            $porc = min(100, max(0, $n($Par_Sql['TarUsu_Porcentaje'])));
            $fec = date('Y-m-d H:i:s');
            $fecHoy = date('Y-m-d');
            $est = '';
            if ($porc >= 100) {
                $est = "UPDATE aud_despacho_tareas SET Tar_Est='FINALIZADA', Tar_Fecha_Culminacion='$fecHoy' WHERE Tar_Cod=$tar; ";
            } elseif ($porc > 0) {
                $est = "UPDATE aud_despacho_tareas SET Tar_Est='EN_PROCESO' WHERE Tar_Cod=$tar; ";
            }
            return $est . "UPDATE aud_despacho_tarea_usuarios SET TarUsu_Porcentaje=$porc, TarUsu_Fecha_Ult='$fec' WHERE Tar_Cod=$tar AND Per_Cod=$per";
            break;

        /* 39. Insertar adjunto tarea */
        case 39:
            $tar = $n($Par_Sql['Tar_Cod']);
            $tipo = isset($Par_Sql['Adj_Tipo']) ? $e($Par_Sql['Adj_Tipo']) : 'OTRO';
            $nom = isset($Par_Sql['Adj_Nombre_safe']) ? $Par_Sql['Adj_Nombre_safe'] : $e($Par_Sql['Adj_Nombre']);
            $ruta = $e($Par_Sql['Adj_Ruta']);
            $usu = isset($Par_Sql['Usu_Cod']) ? $n($Par_Sql['Usu_Cod']) : 'NULL';
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : 'NULL';
            $fec = date('Y-m-d H:i:s');
            return "INSERT INTO aud_despacho_tarea_adjuntos (Tar_Cod, Adj_Tipo, Adj_Nombre, Adj_Ruta, Adj_Fecha, Usu_Cod, Emp_Cod) " .
                "VALUES($tar,'$tipo','$nom','$ruta','$fec',$usu,$emp)";
            break;

        /* 40. Listar adjuntos tarea */
        case 40:
            $tar = $n($Par_Sql['Tar_Cod']);
            return "SELECT Adj_Cod, Tar_Cod, Adj_Tipo, Adj_Nombre, Adj_Ruta, Adj_Fecha FROM aud_despacho_tarea_adjuntos WHERE Tar_Cod=$tar ORDER BY Adj_Fecha DESC";
            break;

        /* 41. Reporte facturación - tareas con facturable/valor. Acepta Tar_Periodo (YYYY-MM) o FechaDesde+FechaHasta */
        case 41:
            $emp = $n($Par_Sql['Emp_Cod']);
            $per = isset($Par_Sql['Tar_Periodo']) ? $e($Par_Sql['Tar_Periodo']) : '';
            $fdesde = isset($Par_Sql['FechaDesde']) && trim($Par_Sql['FechaDesde']) !== '' ? $e($Par_Sql['FechaDesde']) : '';
            $fhasta = isset($Par_Sql['FechaHasta']) && trim($Par_Sql['FechaHasta']) !== '' ? $e($Par_Sql['FechaHasta']) : '';
            $criterio = isset($Par_Sql['Criterio']) ? $e($Par_Sql['Criterio']) : 'A'; // A=finalizadas, B=generadas
            $wh = "t.Emp_Cod=$emp";
            if ($fdesde !== '' && $fhasta !== '') {
                $wh .= " AND t.Tar_Fecha_Limite >= '$fdesde' AND t.Tar_Fecha_Limite <= '$fhasta'";
            } elseif ($per !== '') {
                $wh .= " AND t.Tar_Periodo='$per'";
            }
            if ($criterio === 'A') $wh .= " AND t.Tar_Est='FINALIZADA'";
            if (!empty($Par_Sql['Cli_Cod'])) $wh .= " AND t.Cli_Cod=" . $n($Par_Sql['Cli_Cod']);
            return "SELECT t.Tar_Cod, t.Cli_Cod, t.Act_Cod, t.Tar_Periodo, t.Con_Cod, t.Tar_Est, " .
                "(SELECT COALESCE(SUM(tu.TarUsu_Porcentaje), 0) FROM aud_despacho_tarea_usuarios tu WHERE tu.Tar_Cod=t.Tar_Cod AND tu.TarUsu_Est='ACTIVO') AS Tar_Avance, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre, a.Act_Nombre, s.Ser_Nombre, " .
                "COALESCE(ca.Facturable, IF(COALESCE(pr.Precio,0)>0,'S','N')) AS Act_Facturable, " .
                "COALESCE(ca.Valor_Unitario, pr.Precio, 0) AS Act_Valor, " .
                "COALESCE(cs.Facturable, 'N') AS Ser_Facturable, COALESCE(cs.Valor_Unitario, 0) AS Ser_Valor, " .
                "IF(ca.Con_Cod IS NOT NULL, 'S', 'N') AS Incluida_Contrato " .
                "FROM aud_despacho_tareas t INNER JOIN cliente c ON t.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "INNER JOIN aud_cat_actividades a ON t.Act_Cod=a.Act_Cod INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "LEFT JOIN aud_despacho_contratos con ON t.Con_Cod=con.Con_Cod " .
                "LEFT JOIN aud_despacho_clientes dcl ON con.Dcl_Cod=dcl.Dcl_Cod " .
                "LEFT JOIN aud_actividad_precios pr ON t.Act_Cod=pr.Act_Cod AND pr.Tipo_Empresa=COALESCE(NULLIF(TRIM(dcl.Dcl_Tipo_Empresa),''),'MEDIANO') " .
                "LEFT JOIN aud_contrato_actividades ca ON t.Con_Cod=ca.Con_Cod AND t.Act_Cod=ca.Act_Cod AND ca.Activa='S' " .
                "LEFT JOIN aud_contrato_servicios cs ON t.Con_Cod=cs.Con_Cod AND a.Ser_Cod=cs.Ser_Cod AND cs.Activo='S' " .
                "WHERE $wh ORDER BY p.Prs_Ape, s.Ser_Nombre, a.Act_Nombre";
            break;

        /* 42. Obtener Per_Cod del usuario logueado.
         * Usuarios y personal se enlazan a persona (Prs_Cod). Si hay duplicados en persona
         * (misma persona con distinto Prs_Cod), se intenta emparejar por cédula (Prs_Ced/Usu_Ced).
         */
        case 42:
            $usu = $n($Par_Sql['Usu_Cod']);
            $emp = $n($Par_Sql['Emp_Cod']);
            return "SELECT per.Per_Cod FROM personal per " .
                "INNER JOIN persona p_per ON per.Prs_Cod = p_per.Prs_Cod " .
                "INNER JOIN usuarios u ON u.Usu_Cod = $usu " .
                "LEFT JOIN persona p_u ON p_u.Prs_Cod = u.Prs_Cod " .
                "WHERE per.Per_Est='A' AND per.Emp_Cod=$emp " .
                "AND (per.Prs_Cod = u.Prs_Cod " .
                "     OR (p_u.Prs_Cod IS NOT NULL AND TRIM(COALESCE(p_per.Prs_Ced,'')) = TRIM(COALESCE(p_u.Prs_Ced,'')) AND TRIM(COALESCE(p_per.Prs_Ced,'')) != '') " .
                "     OR (TRIM(COALESCE(u.Usu_Ced,'')) != '' AND TRIM(COALESCE(p_per.Prs_Ced,'')) = TRIM(u.Usu_Ced))) " .
                "LIMIT 1";
            break;

        /* 43. Actividades incluidas por contrato (para generador) */
        case 43:
            $con = $n($Par_Sql['Con_Cod']);
            return "SELECT ca.Act_Cod, a.Act_Nombre, a.Act_Tipo, a.Act_Meses_Anual, a.Act_Usa_Ruc, a.Ser_Cod, s.Ser_Nombre FROM aud_contrato_actividades ca " .
                "INNER JOIN aud_cat_actividades a ON ca.Act_Cod=a.Act_Cod INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "WHERE ca.Con_Cod=$con AND ca.Activa='S' AND ca.Incluida='S'";
            break;

        /* 44. Servicios con actividades por defecto (si actividad no está en contrato pero servicio sí) */
        case 44:
            $con = $n($Par_Sql['Con_Cod']);
            return "SELECT a.Act_Cod, a.Act_Nombre, a.Act_Tipo, a.Act_Meses_Anual, a.Act_Usa_Ruc, a.Ser_Cod, s.Ser_Nombre FROM aud_contrato_servicios cs INNER JOIN aud_cat_actividades a ON cs.Ser_Cod=a.Ser_Cod INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "WHERE cs.Con_Cod=$con AND cs.Activo='S' AND cs.Incluido='S' AND a.Act_Est='A' " .
                "AND NOT EXISTS (SELECT 1 FROM aud_contrato_actividades ca WHERE ca.Con_Cod=$con AND ca.Act_Cod=a.Act_Cod)";
            break;

        /* 45. Obtener día declaración por 9no dígito RUC */
        case 45:
            $dig = $n($Par_Sql['Ruc_Digito']);
            if ($dig < 0 || $dig > 9) $dig = 0;
            return "SELECT Ruc_Digito, Ruc_Dia_Mensual, Ruc_Dia_Sem1, Ruc_Dia_Sem2 FROM aud_despacho_ruc_fechas WHERE Ruc_Digito=$dig LIMIT 1";
            break;

        /* 46. Cliente con RUC/cédula y nombre (para generador; Ruc_Cedula = identificador para 9no dígito) */
        case 46:
            $cli = $n($Par_Sql['Cli_Cod']);
            return "SELECT c.Cli_Cod, c.Cli_Ruf, p.Prs_Ced, COALESCE(NULLIF(TRIM(c.Cli_Ruf),''), NULLIF(TRIM(p.Prs_Ced),'')) AS Ruc_Cedula, CONCAT(COALESCE(p.Prs_Ape,''),' ',COALESCE(p.Prs_Nom,'')) AS Cliente_Nombre FROM cliente c LEFT JOIN persona p ON c.Prs_Cod=p.Prs_Cod WHERE c.Cli_Cod=$cli LIMIT 1";
            break;

        /* 47. Eliminar contrato */
        case 47:
            $cod = $n($Par_Sql['Con_Cod']);
            return "DELETE FROM aud_despacho_contratos WHERE Con_Cod=$cod";
            break;

        /* 48. Siguiente número de contrato */
        case 48:
            $emp = $n($Par_Sql['Emp_Cod']);
            return "SELECT COALESCE(MAX(con.Con_Cod), 0) + 1 AS Siguiente FROM aud_despacho_contratos con " .
                "INNER JOIN aud_despacho_clientes dc ON con.Dcl_Cod=dc.Dcl_Cod WHERE dc.Emp_Cod=$emp";
            break;

        /* 49. Contar actividades de un servicio en contrato */
        case 49:
            $con = $n($Par_Sql['Con_Cod']);
            $ser = $n($Par_Sql['Ser_Cod']);
            return "SELECT COUNT(*) AS Cnt FROM aud_contrato_actividades ca INNER JOIN aud_cat_actividades a ON ca.Act_Cod=a.Act_Cod WHERE ca.Con_Cod=$con AND a.Ser_Cod=$ser";
            break;

        /* 50. ConSer_Cod por Con_Cod y Ser_Cod */
        case 50:
            $con = $n($Par_Sql['Con_Cod']);
            $ser = $n($Par_Sql['Ser_Cod']);
            return "SELECT ConSer_Cod FROM aud_contrato_servicios WHERE Con_Cod=$con AND Ser_Cod=$ser LIMIT 1";
            break;

        /* 51. Con_Cod y Ser_Cod por ConSer_Cod */
        case 51:
            $conser = $n($Par_Sql['ConSer_Cod']);
            return "SELECT Con_Cod, Ser_Cod FROM aud_contrato_servicios WHERE ConSer_Cod=$conser LIMIT 1";
            break;

        /* 52. Eliminar actividades de un servicio del contrato */
        case 52:
            $con = $n($Par_Sql['Con_Cod']);
            $ser = $n($Par_Sql['Ser_Cod']);
            return "DELETE ca FROM aud_contrato_actividades ca INNER JOIN aud_cat_actividades a ON ca.Act_Cod=a.Act_Cod WHERE ca.Con_Cod=$con AND a.Ser_Cod=$ser";
            break;

        /* 53. Tareas despacho para grid (clientes x actividades) - incluye flag asignada */
        case 53:
            $wh = "1=1";
            if (!empty($Par_Sql['Emp_Cod'])) $wh .= " AND t.Emp_Cod=" . $n($Par_Sql['Emp_Cod']);
            if (!empty($Par_Sql['FechaDesde']) && !empty($Par_Sql['FechaHasta'])) {
                $wh .= " AND t.Tar_Fecha_Limite>='" . $e($Par_Sql['FechaDesde']) . "' AND t.Tar_Fecha_Limite<='" . $e($Par_Sql['FechaHasta']) . "'";
            } elseif (!empty($Par_Sql['Tar_Periodo'])) {
                $wh .= " AND t.Tar_Periodo='" . $e($Par_Sql['Tar_Periodo']) . "'";
            }
            if (!empty($Par_Sql['Per_Cod'])) $wh .= " AND EXISTS (SELECT 1 FROM aud_despacho_tarea_usuarios tu WHERE tu.Tar_Cod=t.Tar_Cod AND tu.Per_Cod=" . $n($Par_Sql['Per_Cod']) . " AND tu.TarUsu_Est='ACTIVO')";
            $search = "1=1";
            if (!empty($Par_Sql['search'])) {
                $bus = $e($Par_Sql['search']);
                $search = "(p.Prs_Nom LIKE '%$bus%' OR p.Prs_Ape LIKE '%$bus%' OR a.Act_Nombre LIKE '%$bus%')";
            }
            return "SELECT t.Tar_Cod, t.Cli_Cod, t.Act_Cod, a.Ser_Cod, t.Tar_Periodo, t.Tar_Fecha_Limite, t.Tar_Est, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre, a.Act_Nombre, s.Ser_Nombre, " .
                "(SELECT COUNT(*) FROM aud_despacho_tarea_usuarios tu WHERE tu.Tar_Cod=t.Tar_Cod AND tu.TarUsu_Est='ACTIVO') AS Cnt_Usuarios, " .
                "(SELECT r.Ruc_Dia_Mensual FROM aud_despacho_ruc_fechas r WHERE ruc_limpio.Ruc IS NOT NULL AND LENGTH(REPLACE(REPLACE(ruc_limpio.Ruc, ' ', ''), '-', '')) >= 9 AND r.Ruc_Digito = CAST(SUBSTRING(REPLACE(REPLACE(ruc_limpio.Ruc, ' ', ''), '-', ''), 9, 1) AS UNSIGNED) LIMIT 1) AS Ruc_Dia_Declaracion, " .
                "ruc_limpio.Ruc AS Ruc_Str " .
                "FROM aud_despacho_tareas t " .
                "INNER JOIN cliente c ON t.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "LEFT JOIN (SELECT c2.Cli_Cod, COALESCE(NULLIF(TRIM(c2.Cli_Ruf), ''), NULLIF(TRIM(p2.Prs_Ced), '')) AS Ruc FROM cliente c2 LEFT JOIN persona p2 ON c2.Prs_Cod=p2.Prs_Cod) ruc_limpio ON t.Cli_Cod=ruc_limpio.Cli_Cod " .
                "INNER JOIN aud_cat_actividades a ON t.Act_Cod=a.Act_Cod INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "WHERE $wh AND $search ORDER BY p.Prs_Ape, p.Prs_Nom, s.Ser_Nombre, a.Act_Nombre";
            break;

        /* 54. Tareas despacho para grid SIN Ruc_Dia_Declaracion (fallback si aud_despacho_ruc_fechas no existe) */
        case 54:
            $wh = "1=1";
            if (!empty($Par_Sql['Emp_Cod'])) $wh .= " AND t.Emp_Cod=" . $n($Par_Sql['Emp_Cod']);
            if (!empty($Par_Sql['FechaDesde']) && !empty($Par_Sql['FechaHasta'])) {
                $wh .= " AND t.Tar_Fecha_Limite>='" . $e($Par_Sql['FechaDesde']) . "' AND t.Tar_Fecha_Limite<='" . $e($Par_Sql['FechaHasta']) . "'";
            } elseif (!empty($Par_Sql['Tar_Periodo'])) {
                $wh .= " AND t.Tar_Periodo='" . $e($Par_Sql['Tar_Periodo']) . "'";
            }
            if (!empty($Par_Sql['Per_Cod'])) $wh .= " AND EXISTS (SELECT 1 FROM aud_despacho_tarea_usuarios tu WHERE tu.Tar_Cod=t.Tar_Cod AND tu.Per_Cod=" . $n($Par_Sql['Per_Cod']) . " AND tu.TarUsu_Est='ACTIVO')";
            $search = "1=1";
            if (!empty($Par_Sql['search'])) {
                $bus = $e($Par_Sql['search']);
                $search = "(p.Prs_Nom LIKE '%$bus%' OR p.Prs_Ape LIKE '%$bus%' OR a.Act_Nombre LIKE '%$bus%')";
            }
            return "SELECT t.Tar_Cod, t.Cli_Cod, t.Act_Cod, a.Ser_Cod, t.Tar_Periodo, t.Tar_Fecha_Limite, t.Tar_Est, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre, a.Act_Nombre, s.Ser_Nombre, " .
                "(SELECT COUNT(*) FROM aud_despacho_tarea_usuarios tu WHERE tu.Tar_Cod=t.Tar_Cod AND tu.TarUsu_Est='ACTIVO') AS Cnt_Usuarios, " .
                "NULL AS Ruc_Dia_Declaracion, ruc_limpio.Ruc AS Ruc_Str " .
                "FROM aud_despacho_tareas t " .
                "INNER JOIN cliente c ON t.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "LEFT JOIN (SELECT c2.Cli_Cod, COALESCE(NULLIF(TRIM(c2.Cli_Ruf), ''), NULLIF(TRIM(p2.Prs_Ced), '')) AS Ruc FROM cliente c2 LEFT JOIN persona p2 ON c2.Prs_Cod=p2.Prs_Cod) ruc_limpio ON t.Cli_Cod=ruc_limpio.Cli_Cod " .
                "INNER JOIN aud_cat_actividades a ON t.Act_Cod=a.Act_Cod INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "WHERE $wh AND $search ORDER BY p.Prs_Ape, p.Prs_Nom, s.Ser_Nombre, a.Act_Nombre";
            break;

        /* 55. Detalle de una tarea por Tar_Cod (para modal operativo) */
        case 55:
            $tar = $n($Par_Sql['Tar_Cod']);
            return "SELECT t.Tar_Cod, t.Cli_Cod, t.Act_Cod, a.Ser_Cod, t.Tar_Periodo, t.Tar_Fecha_Limite, t.Tar_Est, t.Tar_Observaciones, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre, a.Act_Nombre, s.Ser_Nombre " .
                "FROM aud_despacho_tareas t " .
                "INNER JOIN cliente c ON t.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "INNER JOIN aud_cat_actividades a ON t.Act_Cod=a.Act_Cod INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "WHERE t.Tar_Cod=$tar LIMIT 1";
            break;

        /* 60. Listar regimenes tributarios */
        case 60:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : 0;
            $wh = "Reg_Est='A'";
            if ($emp > 0) $wh .= " AND (Emp_Cod IS NULL OR Emp_Cod=$emp)";
            return "SELECT Reg_Cod, Reg_Nombre, Reg_Descripcion, Reg_Est, Emp_Cod FROM aud_cat_regimen WHERE $wh ORDER BY Reg_Nombre";
            break;

        /* 61. Insertar regimen */
        case 61:
            $nom = isset($Par_Sql['Reg_Nombre_safe']) ? $Par_Sql['Reg_Nombre_safe'] : $e($Par_Sql['Reg_Nombre']);
            $desc = isset($Par_Sql['Reg_Descripcion_safe']) ? $Par_Sql['Reg_Descripcion_safe'] : $e($Par_Sql['Reg_Descripcion']);
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : 0;
            $emp_sql = $emp > 0 ? $emp : 'NULL';
            return "INSERT INTO aud_cat_regimen (Reg_Nombre, Reg_Descripcion, Reg_Est, Emp_Cod) VALUES('$nom','$desc','A',$emp_sql)";
            break;

        /* 62. Actualizar regimen */
        case 62:
            $cod = $n($Par_Sql['Reg_Cod']);
            $nom = isset($Par_Sql['Reg_Nombre_safe']) ? $Par_Sql['Reg_Nombre_safe'] : $e($Par_Sql['Reg_Nombre']);
            $desc = isset($Par_Sql['Reg_Descripcion_safe']) ? $Par_Sql['Reg_Descripcion_safe'] : $e($Par_Sql['Reg_Descripcion']);
            $est = isset($Par_Sql['Reg_Est']) ? $e($Par_Sql['Reg_Est']) : 'A';
            return "UPDATE aud_cat_regimen SET Reg_Nombre='$nom', Reg_Descripcion='$desc', Reg_Est='$est' WHERE Reg_Cod=$cod";
            break;

        /* 63. Actividades por regimen (por servicio opcional) */
        case 63:
            $reg = $n($Par_Sql['Reg_Cod']);
            $ser = isset($Par_Sql['Ser_Cod']) ? $n($Par_Sql['Ser_Cod']) : 0;
            $wh = "ra.Reg_Cod=$reg";
            if ($ser > 0) $wh .= " AND ra.Ser_Cod=$ser";
            return "SELECT ra.RegAct_Cod, ra.Reg_Cod, ra.Ser_Cod, s.Ser_Nombre, ra.Act_Cod, a.Act_Nombre, a.Act_Tipo, a.Act_Prioridad " .
                "FROM aud_regimen_actividades ra INNER JOIN aud_cat_servicios s ON ra.Ser_Cod=s.Ser_Cod " .
                "INNER JOIN aud_cat_actividades a ON ra.Act_Cod=a.Act_Cod WHERE $wh ORDER BY s.Ser_Nombre, a.Act_Nombre";
            break;

        /* 64. Agregar actividad a regimen */
        case 64:
            $reg = $n($Par_Sql['Reg_Cod']);
            $ser = $n($Par_Sql['Ser_Cod']);
            $act = $n($Par_Sql['Act_Cod']);
            return "INSERT IGNORE INTO aud_regimen_actividades (Reg_Cod, Ser_Cod, Act_Cod) VALUES($reg,$ser,$act)";
            break;

        /* 65. Quitar actividad de regimen */
        case 65:
            $regact = $n($Par_Sql['RegAct_Cod']);
            return "DELETE FROM aud_regimen_actividades WHERE RegAct_Cod=$regact";
            break;

        /* 67. Listar actividades con precios (Pequeño, Mediano, Grande) */
        case 67:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : 0;
            return "SELECT a.Act_Cod, a.Ser_Cod, s.Ser_Nombre, a.Act_Nombre, a.Act_Tipo, " .
                "COALESCE(p_peq.Precio, 0) AS Precio_Pequeno, COALESCE(p_med.Precio, 0) AS Precio_Mediano, COALESCE(p_gra.Precio, 0) AS Precio_Grande " .
                "FROM aud_cat_actividades a INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "LEFT JOIN aud_actividad_precios p_peq ON a.Act_Cod=p_peq.Act_Cod AND p_peq.Tipo_Empresa='PEQUENO' " .
                "LEFT JOIN aud_actividad_precios p_med ON a.Act_Cod=p_med.Act_Cod AND p_med.Tipo_Empresa='MEDIANO' " .
                "LEFT JOIN aud_actividad_precios p_gra ON a.Act_Cod=p_gra.Act_Cod AND p_gra.Tipo_Empresa='GRANDE' " .
                "WHERE a.Act_Est='A' ORDER BY s.Ser_Nombre, a.Act_Nombre";
            break;

        /* 68. Guardar precio actividad (INSERT ... ON DUPLICATE KEY UPDATE) */
        case 68:
            $act = $n($Par_Sql['Act_Cod']);
            $tipo = isset($Par_Sql['Tipo_Empresa']) && in_array($Par_Sql['Tipo_Empresa'], array('PEQUENO','MEDIANO','GRANDE')) ? $Par_Sql['Tipo_Empresa'] : 'PEQUENO';
            $precio = isset($Par_Sql['Precio']) ? $d($Par_Sql['Precio']) : 0;
            return "INSERT INTO aud_actividad_precios (Act_Cod, Tipo_Empresa, Precio, Emp_Cod) VALUES($act,'$tipo',$precio,NULL) " .
                "ON DUPLICATE KEY UPDATE Precio=$precio";
            break;

        /* 66. Actividades del regimen para precargar en contrato (por Reg_Cod) */
        case 66:
            $reg = $n($Par_Sql['Reg_Cod']);
            return "SELECT ra.Act_Cod, a.Act_Nombre, a.Act_Tipo, a.Act_Usa_Ruc, a.Ser_Cod, s.Ser_Nombre " .
                "FROM aud_regimen_actividades ra INNER JOIN aud_cat_actividades a ON ra.Act_Cod=a.Act_Cod " .
                "INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod WHERE ra.Reg_Cod=$reg AND a.Act_Est='A' ORDER BY s.Ser_Nombre, a.Act_Nombre";
            break;

        /* 69. Eliminar actividad (soft delete: Act_Est='I') */
        case 69:
            $cod = $n($Par_Sql['Act_Cod']);
            return "UPDATE aud_cat_actividades SET Act_Est='I' WHERE Act_Cod=$cod";
            break;

        /* 70. Listar tareas para asignación (filtros: Emp_Cod, Tar_Periodo, Cli_Cod, Act_Cod, Cli_Cod_In) */
        case 70:
            $wh = "1=1";
            if (!empty($Par_Sql['Emp_Cod'])) $wh .= " AND t.Emp_Cod=" . $n($Par_Sql['Emp_Cod']);
            if (!empty($Par_Sql['Tar_Periodo'])) $wh .= " AND t.Tar_Periodo='" . $e($Par_Sql['Tar_Periodo']) . "'";
            if (!empty($Par_Sql['Cli_Cod'])) $wh .= " AND t.Cli_Cod=" . $n($Par_Sql['Cli_Cod']);
            if (!empty($Par_Sql['Act_Cod'])) $wh .= " AND t.Act_Cod=" . $n($Par_Sql['Act_Cod']);
            if (!empty($Par_Sql['Cli_Cod_In'])) {
                $arr = array_map('intval', array_filter(explode(',', $Par_Sql['Cli_Cod_In'])));
                if (!empty($arr)) $wh .= " AND t.Cli_Cod IN (" . implode(',', $arr) . ")";
            }
            return "SELECT t.Tar_Cod, t.Cli_Cod, t.Act_Cod, a.Ser_Cod, t.Tar_Periodo, t.Tar_Fecha_Limite, t.Tar_Est, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre, a.Act_Nombre, s.Ser_Nombre, " .
                "(SELECT COUNT(*) FROM aud_despacho_tarea_usuarios tu WHERE tu.Tar_Cod=t.Tar_Cod AND tu.TarUsu_Est='ACTIVO') AS Cnt_Usuarios, " .
                "(SELECT GROUP_CONCAT(CONCAT(per2.Prs_Ape,' ',per2.Prs_Nom) ORDER BY per2.Prs_Ape, per2.Prs_Nom SEPARATOR ', ') " .
                "FROM aud_despacho_tarea_usuarios tu2 INNER JOIN personal per3 ON tu2.Per_Cod=per3.Per_Cod INNER JOIN persona per2 ON per3.Prs_Cod=per2.Prs_Cod " .
                "WHERE tu2.Tar_Cod=t.Tar_Cod AND tu2.TarUsu_Est='ACTIVO') AS Usuarios_Asignados " .
                "FROM aud_despacho_tareas t " .
                "INNER JOIN cliente c ON t.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "INNER JOIN aud_cat_actividades a ON t.Act_Cod=a.Act_Cod INNER JOIN aud_cat_servicios s ON a.Ser_Cod=s.Ser_Cod " .
                "WHERE $wh ORDER BY p.Prs_Ape, p.Prs_Nom, s.Ser_Nombre, a.Act_Nombre";
            break;

        /* 71. Listar clientes con tareas en período (para combo asignación por empresa) */
        case 71:
            $emp = $n($Par_Sql['Emp_Cod']);
            $per = $e($Par_Sql['Tar_Periodo']);
            return "SELECT DISTINCT t.Cli_Cod, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre " .
                "FROM aud_despacho_tareas t INNER JOIN cliente c ON t.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "WHERE t.Emp_Cod=$emp AND t.Tar_Periodo='$per' ORDER BY Cliente_Nombre";
            break;

        /* 72. Listar usuarios asignados a una tarea (incluye TarUsu_Observacion) */
        case 72:
            $tar = $n($Par_Sql['Tar_Cod']);
            return "SELECT tu.TarUsu_Cod, tu.Tar_Cod, tu.Per_Cod, tu.TarUsu_Porcentaje, tu.TarUsu_Est, tu.TarUsu_Observacion, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Personal_Nombre " .
                "FROM aud_despacho_tarea_usuarios tu INNER JOIN personal per ON tu.Per_Cod=per.Per_Cod INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod " .
                "WHERE tu.Tar_Cod=$tar AND tu.TarUsu_Est='ACTIVO' ORDER BY p.Prs_Ape, p.Prs_Nom";
            break;

        /* 82. Listar usuarios asignados (sin TarUsu_Observacion, fallback si migración no ejecutada) */
        case 82:
            $tar = $n($Par_Sql['Tar_Cod']);
            return "SELECT tu.TarUsu_Cod, tu.Tar_Cod, tu.Per_Cod, tu.TarUsu_Porcentaje, tu.TarUsu_Est, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Personal_Nombre " .
                "FROM aud_despacho_tarea_usuarios tu INNER JOIN personal per ON tu.Per_Cod=per.Per_Cod INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod " .
                "WHERE tu.Tar_Cod=$tar AND tu.TarUsu_Est='ACTIVO' ORDER BY p.Prs_Ape, p.Prs_Nom";
            break;

        /* 83. Clientes con contratos vigentes en período (para combo generador) - mismos criterios que 30 */
        case 83:
            $emp = $n($Par_Sql['Emp_Cod']);
            $fec_ini = isset($Par_Sql['Fecha_Ini']) ? $e($Par_Sql['Fecha_Ini']) : '';
            $fec_fin = isset($Par_Sql['Fecha_Fin']) ? $e($Par_Sql['Fecha_Fin']) : '';
            if ($fec_ini === '' || $fec_fin === '') {
                $periodo = $e($Par_Sql['Tar_Periodo']);
                $es_anual = (strlen($periodo) === 4);
                if ($es_anual) {
                    $fec_ini = $periodo . '-01-01';
                    $fec_fin = $periodo . '-12-31';
                } else {
                    $fec_ini = $periodo . '-01';
                    $fec_fin = date('Y-m-t', strtotime($fec_ini));
                }
            }
            return "SELECT DISTINCT dc.Cli_Cod, CONCAT(COALESCE(p.Prs_Ape,''),' ',COALESCE(p.Prs_Nom,'')) AS Cliente_Nombre " .
                "FROM aud_despacho_contratos con INNER JOIN aud_despacho_clientes dc ON con.Dcl_Cod=dc.Dcl_Cod " .
                "INNER JOIN cliente c ON dc.Cli_Cod=c.Cli_Cod LEFT JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "WHERE con.Con_Est='VIGENTE' AND dc.Dcl_Est='ACTIVO' AND dc.Emp_Cod=$emp AND c.Cli_Est='A' " .
                "AND con.Con_Fecha_Inicio<='$fec_fin' AND (con.Con_Fecha_Fin IS NULL OR con.Con_Fecha_Fin>='$fec_ini') ORDER BY Cliente_Nombre";
            break;

        /* 84. Clientes con valor del contrato vigente (una fila por cliente, contrato más reciente) - opcional Tar_Periodo.
             * Si Tar_Periodo es YYYY-MM: contratos MENSUAL aparecen todos los meses; contratos ANUAL solo en los meses indicados en Con_Meses_Anual. */
        case 84:
            $emp = $n($Par_Sql['Emp_Cod']);
            $fec_ini = '';
            $fec_fin = '';
            $wh_tipo_mes = '';
            if (!empty($Par_Sql['Tar_Periodo'])) {
                $periodo = $e($Par_Sql['Tar_Periodo']);
                if (strlen($periodo) === 4) {
                    $fec_ini = $periodo . '-01-01';
                    $fec_fin = $periodo . '-12-31';
                } else {
                    $fec_ini = $periodo . '-01';
                    $fec_fin = date('Y-m-t', strtotime($fec_ini));
                    if (strlen($periodo) >= 7) {
                        $mes = $e(substr($periodo, 5, 2));
                        $wh_tipo_mes = " AND (con.Con_Tipo='MENSUAL' OR (con.Con_Tipo='ANUAL' AND con.Con_Meses_Anual IS NOT NULL AND TRIM(con.Con_Meses_Anual)!='' AND FIND_IN_SET('$mes', REPLACE(TRIM(con.Con_Meses_Anual), ' ', ''))>0))";
                    }
                }
            }
            $wh = "dc.Dcl_Est='ACTIVO' AND dc.Emp_Cod=$emp AND c.Cli_Est='A'";
            $wh_con = "con.Con_Est='VIGENTE' AND con.Dcl_Cod=dc.Dcl_Cod" . $wh_tipo_mes;
            if ($fec_ini !== '' && $fec_fin !== '') {
                $wh_con .= " AND con.Con_Fecha_Inicio<='$fec_fin' AND (con.Con_Fecha_Fin IS NULL OR con.Con_Fecha_Fin>='$fec_ini')";
            }
            return "SELECT dc.Cli_Cod, CONCAT(COALESCE(p.Prs_Ape,''),' ',COALESCE(p.Prs_Nom,'')) AS Cliente_Nombre, " .
                "(SELECT con.Con_Valor FROM aud_despacho_contratos con WHERE $wh_con ORDER BY con.Con_Cod DESC LIMIT 1) AS Valor_Contrato, " .
                "(SELECT con.Con_Numero FROM aud_despacho_contratos con WHERE $wh_con ORDER BY con.Con_Cod DESC LIMIT 1) AS Con_Numero " .
                "FROM aud_despacho_clientes dc INNER JOIN cliente c ON dc.Cli_Cod=c.Cli_Cod LEFT JOIN persona p ON c.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh AND EXISTS (SELECT 1 FROM aud_despacho_contratos con WHERE $wh_con) ORDER BY p.Prs_Ape, p.Prs_Nom";
            break;

        /* 73. Eliminar asignación tarea-usuario */
        case 73:
            $tar = $n($Par_Sql['Tar_Cod']);
            $per = $n($Par_Sql['Per_Cod']);
            return "DELETE FROM aud_despacho_tarea_usuarios WHERE Tar_Cod=$tar AND Per_Cod=$per";
            break;

        /* 74. Actualizar fecha límite de tarea */
        case 74:
            $tar = $n($Par_Sql['Tar_Cod']);
            $fec = isset($Par_Sql['Tar_Fecha_Limite']) && trim($Par_Sql['Tar_Fecha_Limite']) !== '' ? "'" . $e($Par_Sql['Tar_Fecha_Limite']) . "'" : 'NULL';
            return "UPDATE aud_despacho_tareas SET Tar_Fecha_Limite=$fec WHERE Tar_Cod=$tar";
            break;

        /* 75. Eliminar tarea despacho (y sus asignaciones/adjuntos por CASCADE) */
        case 75:
            $tar = $n($Par_Sql['Tar_Cod']);
            return "DELETE FROM aud_despacho_tareas WHERE Tar_Cod=$tar";
            break;

        /* 76. Obtener porcentaje actual de tarea-usuario (para validar no disminuir) */
        case 76:
            $tar = $n($Par_Sql['Tar_Cod']);
            $per = $n($Par_Sql['Per_Cod']);
            return "SELECT TarUsu_Porcentaje FROM aud_despacho_tarea_usuarios WHERE Tar_Cod=$tar AND Per_Cod=$per AND TarUsu_Est='ACTIVO' LIMIT 1";
            break;

        /* 77. Obtener Tar_Est por Tar_Cod (para validar no asignar a finalizadas) */
        case 77:
            $tar = $n($Par_Sql['Tar_Cod']);
            return "SELECT Tar_Est FROM aud_despacho_tareas WHERE Tar_Cod=$tar AND Emp_Cod=" . $n($Par_Sql['Emp_Cod']) . " LIMIT 1";
            break;

        /* 78. Obtener comentario por Tar_Cod (Tar_Comentario_Supervisor si existe, sino Tar_Observaciones) */
        case 78:
            $tar = $n($Par_Sql['Tar_Cod']);
            return "SELECT Tar_Observaciones FROM aud_despacho_tareas t WHERE t.Tar_Cod=$tar LIMIT 1";
            break;

        /* 79. Actualizar comentario de tarea (supervisor) - usa Tar_Observaciones para compatibilidad */
        case 79:
            $tar = $n($Par_Sql['Tar_Cod']);
            $obs = isset($Par_Sql['Tar_Observaciones']) ? $e($Par_Sql['Tar_Observaciones']) : (isset($Par_Sql['Tar_Comentario_Supervisor']) ? $e($Par_Sql['Tar_Comentario_Supervisor']) : '');
            return "UPDATE aud_despacho_tareas SET Tar_Observaciones='$obs' WHERE Tar_Cod=$tar";
            break;

        /* 87. Datos cliente por contrato (Cliente_Nombre, RUC, Tipo_Empresa para propuesta) */
        case 87:
            $con = $n($Par_Sql['Con_Cod']);
            return "SELECT CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Cliente_Nombre, COALESCE(NULLIF(TRIM(c.Cli_Ruf),''), p.Prs_Ced) AS RUC, " .
                "COALESCE(NULLIF(TRIM(dc.Dcl_Tipo_Empresa),''),'MEDIANO') AS Tipo_Empresa " .
                "FROM aud_despacho_contratos con INNER JOIN aud_despacho_clientes dc ON con.Dcl_Cod=dc.Dcl_Cod " .
                "INNER JOIN cliente c ON dc.Cli_Cod=c.Cli_Cod INNER JOIN persona p ON c.Prs_Cod=p.Prs_Cod WHERE con.Con_Cod=$con LIMIT 1";
            break;

        /* 88. Representante legal de la empresa (para firma del despacho en propuesta) */
        case 88:
            $emp = $n($Par_Sql['Emp_Cod']);
            return "SELECT Emp_Rep AS Representante_Nombre, Emp_Rre AS Representante_Identificacion FROM empresas WHERE Emp_Cod=$emp LIMIT 1";
            break;

        /* 81. Obtener Per_Cod del usuario (fallback alternativo - por Prs_Cod o cédula) */
        case 81:
            $usu = $n($Par_Sql['Usu_Cod']);
            $emp = $n($Par_Sql['Emp_Cod']);
            return "SELECT per.Per_Cod FROM personal per " .
                "INNER JOIN persona p ON per.Prs_Cod = p.Prs_Cod " .
                "WHERE per.Per_Est='A' AND per.Emp_Cod=$emp " .
                "AND (per.Prs_Cod = (SELECT Prs_Cod FROM usuarios WHERE Usu_Cod=$usu LIMIT 1) " .
                "     OR (TRIM(COALESCE((SELECT Usu_Ced FROM usuarios WHERE Usu_Cod=$usu LIMIT 1), '')) != '' " .
                "         AND TRIM(COALESCE(p.Prs_Ced,'')) = TRIM(COALESCE((SELECT Usu_Ced FROM usuarios WHERE Usu_Cod=$usu LIMIT 1), '')))) " .
                "LIMIT 1";
            break;

        default:
            return "";
            break;
    }
}
