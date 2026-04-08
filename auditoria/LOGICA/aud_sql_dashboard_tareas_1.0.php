<?php
/**
 * Sentencias SQL para el módulo Dashboard de Tareas (Control de Personal)
 * Solo construcción de SQL, sin lógica de negocio.
 *
 * @author Sistema EXA
 * @version 1.0
 * @package auditoria.LOGICA
 *
 * @param int   $id      Número de sentencia
 * @param array $Par_Sql Parámetros (índices numéricos o asociativos)
 * @return string Sentencia SQL
 */
function sentencias_aud($id, $Par_Sql)
{
    $Par_Sql = is_array($Par_Sql) ? $Par_Sql : array();
    $e = function ($v) {
        return addslashes(trim($v));
    };
    $n = function ($v) {
        return intval($v);
    };

    switch ($id) {

        /* 1. Insertar tarea (Fecha inicio: quien asigna; Fecha fin: la pone el asignado, puede ser NULL) */
        case 1:
            $titulo = isset($Par_Sql['Tar_Titulo_safe']) ? $Par_Sql['Tar_Titulo_safe'] : (isset($Par_Sql['Tar_Titulo']) ? $e($Par_Sql['Tar_Titulo']) : $e($Par_Sql[0]));
            $desc = isset($Par_Sql['Tar_Descripcion_safe']) ? $Par_Sql['Tar_Descripcion_safe'] : (isset($Par_Sql['Tar_Descripcion']) ? $e($Par_Sql['Tar_Descripcion']) : (isset($Par_Sql[1]) ? $e($Par_Sql[1]) : ''));
            $prioridad = isset($Par_Sql['Tar_Prioridad']) ? $e($Par_Sql['Tar_Prioridad']) : (isset($Par_Sql[2]) ? $e($Par_Sql[2]) : 'Media');
            $fec_ini = isset($Par_Sql['Tar_Fecha_Inicio']) ? $e($Par_Sql['Tar_Fecha_Inicio']) : $e($Par_Sql[3]);
            $fec_fin_raw = isset($Par_Sql['Tar_Fecha_Fin']) ? trim($Par_Sql['Tar_Fecha_Fin']) : (isset($Par_Sql[4]) ? trim($Par_Sql[4]) : '');
            $fec_fin = ($fec_fin_raw !== '') ? "'" . $e($fec_fin_raw) . "'" : 'NULL';
            $estado = isset($Par_Sql['Tar_Estado']) ? $e($Par_Sql['Tar_Estado']) : (isset($Par_Sql[5]) ? $e($Par_Sql[5]) : 'Pendiente');
            $usu_creador = isset($Par_Sql['Usu_Creador']) ? $n($Par_Sql['Usu_Creador']) : $n($Par_Sql[6]);
            $emp_cod = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : $n($Par_Sql[7]);
            return "INSERT INTO aud_tareas (Tar_Titulo,Tar_Descripcion,Tar_Prioridad,Tar_Fecha_Inicio,Tar_Fecha_Fin,Tar_Estado,Usu_Creador,Emp_Cod,Tar_Est) " .
                "VALUES('$titulo','$desc','$prioridad','$fec_ini',$fec_fin,'$estado',$usu_creador,$emp_cod,'A')";
            break;

        /* 2. Asignar tarea a empleado (usa Per_Cod de tabla personal) */
        case 2:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            $per_cod = isset($Par_Sql['Per_Cod']) ? $n($Par_Sql['Per_Cod']) : $n($Par_Sql[1]);
            $fec = isset($Par_Sql['Tas_Fecha_Asignacion']) ? $e($Par_Sql['Tas_Fecha_Asignacion']) : (isset($Par_Sql[2]) ? $e($Par_Sql[2]) : date('Y-m-d H:i:s'));
            return "INSERT INTO aud_tareas_asignadas (Tar_Cod,Per_Cod,Tas_Fecha_Asignacion,Tas_Est) VALUES($tar_cod,$per_cod,'$fec','A')";
            break;

        /* 3. Registrar avance */
        case 3:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            $usu_cod = isset($Par_Sql['Usu_Cod']) ? $n($Par_Sql['Usu_Cod']) : $n($Par_Sql[1]);
            $desc = isset($Par_Sql['Ava_Descripcion_safe']) ? $Par_Sql['Ava_Descripcion_safe'] : (isset($Par_Sql['Ava_Descripcion']) ? $e($Par_Sql['Ava_Descripcion']) : (isset($Par_Sql[2]) ? $e($Par_Sql[2]) : ''));
            $porc = isset($Par_Sql['Ava_Porcentaje']) ? min(100, max(0, $n($Par_Sql['Ava_Porcentaje']))) : min(100, max(0, $n($Par_Sql[3])));
            $fec = isset($Par_Sql['Ava_Fecha']) ? $e($Par_Sql['Ava_Fecha']) : (isset($Par_Sql[4]) ? $e($Par_Sql[4]) : date('Y-m-d H:i:s'));
            return "INSERT INTO aud_tareas_avances (Tar_Cod,Usu_Cod,Ava_Descripcion,Ava_Porcentaje,Ava_Fecha,Ava_Est) " .
                "VALUES($tar_cod,$usu_cod,'$desc',$porc,'$fec','A')";
            break;

        /* 4. Actualizar estado de tarea */
        case 4:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            $estado = isset($Par_Sql['Tar_Estado']) ? $e($Par_Sql['Tar_Estado']) : $e($Par_Sql[1]);
            return "UPDATE aud_tareas SET Tar_Estado='$estado' WHERE Tar_Cod=$tar_cod";
            break;

        /* 4.5 Actualizar fecha fin tentativa de tarea (quien asigna o asignado) */
        case 19:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            $fec_fin = isset($Par_Sql['Tar_Fecha_Fin']) ? $e(trim($Par_Sql['Tar_Fecha_Fin'])) : (isset($Par_Sql[1]) ? $e(trim($Par_Sql[1])) : '');
            if ($fec_fin === '') {
                return "UPDATE aud_tareas SET Tar_Fecha_Fin=NULL WHERE Tar_Cod=$tar_cod";
            }
            return "UPDATE aud_tareas SET Tar_Fecha_Fin='$fec_fin' WHERE Tar_Cod=$tar_cod";
            break;

        /* 4.6 Al registrar 100%: setear fecha culminación real y estado Finalizada */
        case 22:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            $fec_culm = isset($Par_Sql['Tar_Fecha_Culminacion']) ? trim($Par_Sql['Tar_Fecha_Culminacion']) : (isset($Par_Sql[1]) ? trim($Par_Sql[1]) : '');
            if ($fec_culm !== '') {
                $fec_culm = "'" . $e($fec_culm) . "'";
            } else {
                $fec_culm = 'CURDATE()';
            }
            return "UPDATE aud_tareas SET Tar_Fecha_Culminacion=$fec_culm, Tar_Estado='Finalizada' WHERE Tar_Cod=$tar_cod";
            break;

        /* 4.6b Solo actualizar estado a Finalizada (fallback si Tar_Fecha_Culminacion no existe) */
        case 28:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            return "UPDATE aud_tareas SET Tar_Estado='Finalizada' WHERE Tar_Cod=$tar_cod";
            break;

        /* 4.7 Al asignar tarea a usuario: estado Asignado (solo si está Pendiente) */
        case 25:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            return "UPDATE aud_tareas SET Tar_Estado='Asignado' WHERE Tar_Cod=$tar_cod AND Tar_Estado='Pendiente'";
            break;

        /* 4.8 Al registrar avance 1-99%: estado En Proceso */
        case 26:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            return "UPDATE aud_tareas SET Tar_Estado='En Proceso' WHERE Tar_Cod=$tar_cod AND Tar_Estado IN ('Pendiente','Asignado')";
            break;

        /* 4.9 Revertir de Finalizada a En Proceso (cuando se reduce avance de 100% a menos) */
        case 34:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            return "UPDATE aud_tareas SET Tar_Estado='En Proceso', Tar_Fecha_Culminacion=NULL WHERE Tar_Cod=$tar_cod AND Tar_Estado='Finalizada'";
            break;

        /* 4.9b Revertir solo estado (fallback si Tar_Fecha_Culminacion no existe) */
        case 35:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            return "UPDATE aud_tareas SET Tar_Estado='En Proceso' WHERE Tar_Cod=$tar_cod AND Tar_Estado='Finalizada'";
            break;

        /* 29. Obtener Tar_Fecha_Culminacion de una tarea (para prellenar formulario de avance) */
        case 29:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            return "SELECT Tar_Fecha_Culminacion FROM aud_tareas WHERE Tar_Cod=$tar_cod LIMIT 1";
            break;

        /* 5. Consultar tareas por empleado (Per_Cod, Emp_Cod opcional) */
        case 5:
            $per_cod = isset($Par_Sql['Per_Cod']) ? $n($Par_Sql['Per_Cod']) : $n($Par_Sql[0]);
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[1]) ? $n($Par_Sql[1]) : 0);
            $wh = "t.Tar_Est='A' AND EXISTS (SELECT 1 FROM aud_tareas_asignadas a WHERE a.Tar_Cod=t.Tar_Cod AND a.Per_Cod=$per_cod AND a.Tas_Est='A')";
            if ($emp > 0) {
                $wh .= " AND t.Emp_Cod=$emp";
            }
            return "SELECT t.Tar_Cod,t.Tar_Titulo,t.Tar_Descripcion,t.Tar_Prioridad,t.Tar_Fecha_Inicio,t.Tar_Fecha_Fin,t.Tar_Estado,t.Usu_Creador,t.Emp_Cod " .
                "FROM aud_tareas t WHERE $wh ORDER BY t.Tar_Fecha_Fin ASC";
            break;

        /* 6. Consultar avances por tarea (con nombre de usuario) */
        case 6:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            return "SELECT a.Ava_Cod, a.Tar_Cod, a.Usu_Cod, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Usuario_Nombre, a.Ava_Descripcion, a.Ava_Porcentaje, a.Ava_Fecha " .
                "FROM aud_tareas_avances a " .
                "LEFT JOIN usuarios u ON a.Usu_Cod=u.Usu_Cod " .
                "LEFT JOIN persona p ON u.Prs_Cod=p.Prs_Cod " .
                "WHERE a.Tar_Cod=$tar_cod AND a.Ava_Est='A' ORDER BY a.Ava_Fecha DESC";
            break;

        /* 6.4 Obtener un solo avance por tarea (el más reciente) - para editar */
        case 13:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            return "SELECT a.Ava_Cod, a.Tar_Cod, a.Ava_Descripcion, a.Ava_Porcentaje, a.Ava_Fecha " .
                "FROM aud_tareas_avances a WHERE a.Tar_Cod=$tar_cod AND a.Ava_Est='A' ORDER BY a.Ava_Fecha DESC LIMIT 1";
            break;

        /* 6.45 Actualizar avance por Ava_Cod */
        case 14:
            $ava_cod = isset($Par_Sql['Ava_Cod']) ? $n($Par_Sql['Ava_Cod']) : $n($Par_Sql[0]);
            $porc = isset($Par_Sql['Ava_Porcentaje']) ? min(100, max(0, $n($Par_Sql['Ava_Porcentaje']))) : min(100, max(0, $n($Par_Sql[1])));
            $desc = isset($Par_Sql['Ava_Descripcion_safe']) ? $Par_Sql['Ava_Descripcion_safe'] : (isset($Par_Sql['Ava_Descripcion']) ? $e($Par_Sql['Ava_Descripcion']) : (isset($Par_Sql[2]) ? $e($Par_Sql[2]) : ''));
            $fec = isset($Par_Sql['Ava_Fecha']) ? $e($Par_Sql['Ava_Fecha']) : (isset($Par_Sql[3]) ? $e($Par_Sql[3]) : date('Y-m-d H:i:s'));
            return "UPDATE aud_tareas_avances SET Ava_Porcentaje=$porc, Ava_Descripcion='$desc', Ava_Fecha='$fec' WHERE Ava_Cod=$ava_cod AND Ava_Est='A'";
            break;

        /* 6.5 Listar avance por tarea (uno por Tar_Cod, el más reciente) por empresa - para merge con asignaciones */
        case 15:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $wh = "t.Tar_Est='A' AND av.Ava_Est='A'";
            if ($emp > 0) {
                $wh .= " AND t.Emp_Cod=$emp";
            }
            return "SELECT av.Tar_Cod, av.Ava_Cod, av.Ava_Porcentaje, av.Ava_Descripcion, av.Ava_Fecha " .
                "FROM aud_tareas_avances av INNER JOIN aud_tareas t ON av.Tar_Cod=t.Tar_Cod " .
                "WHERE $wh ORDER BY av.Tar_Cod, av.Ava_Fecha DESC";
            break;

        /* 7. Métricas de rendimiento (por personal, misma fuente que rhu_con_personal) */
        case 7:
            $per_cod = isset($Par_Sql['Per_Cod']) ? $n($Par_Sql['Per_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[1]) ? $n($Par_Sql[1]) : 0);
            $wh_emp = $emp > 0 ? " AND t.Emp_Cod=$emp" : "";
            if ($per_cod > 0) {
                return "SELECT per.Per_Cod, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Nombre, " .
                    "(SELECT COUNT(*) FROM aud_tareas t INNER JOIN aud_tareas_asignadas ta ON t.Tar_Cod=ta.Tar_Cod AND ta.Per_Cod=per.Per_Cod AND ta.Tas_Est='A' WHERE t.Tar_Est='A' $wh_emp) AS Total_Tareas, " .
                    "(SELECT COUNT(*) FROM aud_tareas t INNER JOIN aud_tareas_asignadas ta ON t.Tar_Cod=ta.Tar_Cod AND ta.Per_Cod=per.Per_Cod AND ta.Tas_Est='A' WHERE t.Tar_Est='A' AND t.Tar_Estado='Finalizada' $wh_emp) AS Tareas_Completadas, " .
                    "(SELECT COUNT(*) FROM aud_tareas t INNER JOIN aud_tareas_asignadas ta ON t.Tar_Cod=ta.Tar_Cod AND ta.Per_Cod=per.Per_Cod AND ta.Tas_Est='A' WHERE t.Tar_Est='A' AND t.Tar_Estado<>'Finalizada' AND t.Tar_Fecha_Fin < CURDATE() $wh_emp) AS Tareas_Atrasadas " .
                    "FROM personal per INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod WHERE per.Per_Cod=$per_cod AND per.Per_Est='A'";
            }
            $wh_emp_per = $emp > 0 ? " AND per.Emp_Cod=$emp" : "";
            return "SELECT per.Per_Cod, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Nombre, " .
                "(SELECT COUNT(*) FROM aud_tareas t INNER JOIN aud_tareas_asignadas ta ON t.Tar_Cod=ta.Tar_Cod AND ta.Per_Cod=per.Per_Cod AND ta.Tas_Est='A' WHERE t.Tar_Est='A' $wh_emp) AS Total_Tareas, " .
                "(SELECT COUNT(*) FROM aud_tareas t INNER JOIN aud_tareas_asignadas ta ON t.Tar_Cod=ta.Tar_Cod AND ta.Per_Cod=per.Per_Cod AND ta.Tas_Est='A' WHERE t.Tar_Est='A' AND t.Tar_Estado='Finalizada' $wh_emp) AS Tareas_Completadas, " .
                "(SELECT COUNT(*) FROM aud_tareas t INNER JOIN aud_tareas_asignadas ta ON t.Tar_Cod=ta.Tar_Cod AND ta.Per_Cod=per.Per_Cod AND ta.Tas_Est='A' WHERE t.Tar_Est='A' AND t.Tar_Estado<>'Finalizada' AND t.Tar_Fecha_Fin < CURDATE() $wh_emp) AS Tareas_Atrasadas " .
                "FROM personal per INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod WHERE per.Per_Est='A' $wh_emp_per " .
                "AND EXISTS (SELECT 1 FROM aud_tareas_asignadas ta INNER JOIN aud_tareas t ON ta.Tar_Cod=t.Tar_Cod WHERE ta.Per_Cod=per.Per_Cod AND ta.Tas_Est='A' AND t.Tar_Est='A' $wh_emp) " .
                "GROUP BY per.Per_Cod ORDER BY Nombre";
            break;

        /* 8. Indicadores dashboard general (total tareas, % completadas, % atrasadas, rendimiento) */
        /* Atrasadas: finalizada con culminación>fin tentativa, o en proceso con fin tentativa<hoy */
        case 8:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $wh = $emp > 0 ? " WHERE Emp_Cod=$emp AND Tar_Est='A'" : " WHERE Tar_Est='A'";
            return "SELECT " .
                "(SELECT COUNT(*) FROM aud_tareas $wh) AS Total_Tareas, " .
                "(SELECT COUNT(*) FROM aud_tareas $wh AND Tar_Estado='Finalizada') AS Completadas, " .
                "(SELECT COUNT(*) FROM aud_tareas $wh AND ( (Tar_Estado='Finalizada' AND Tar_Fecha_Culminacion IS NOT NULL AND Tar_Fecha_Fin IS NOT NULL AND Tar_Fecha_Culminacion > Tar_Fecha_Fin) OR (Tar_Estado<>'Finalizada' AND Tar_Fecha_Fin IS NOT NULL AND Tar_Fecha_Fin < CURDATE()) )) AS Atrasadas";
            break;

        /* 9. Listar tareas para combo/select (Tar_Cod, Tar_Titulo) */
        case 9:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $wh = $emp > 0 ? " WHERE Emp_Cod=$emp AND Tar_Est='A'" : " WHERE Tar_Est='A'";
            return "SELECT Tar_Cod, Tar_Titulo, Tar_Estado, Tar_Fecha_Fin FROM aud_tareas $wh ORDER BY Tar_Fecha_Fin ASC";
            break;

        /* 37. Listar tareas sin asignar (para combo de asignación: excluye las que ya tienen al menos una asignación activa) */
        case 37:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $wh = "t.Tar_Est='A'";
            if ($emp > 0) $wh .= " AND t.Emp_Cod=$emp";
            return "SELECT t.Tar_Cod, t.Tar_Titulo, t.Tar_Estado, t.Tar_Fecha_Fin FROM aud_tareas t " .
                "WHERE $wh AND NOT EXISTS (SELECT 1 FROM aud_tareas_asignadas a WHERE a.Tar_Cod=t.Tar_Cod AND a.Tas_Est='A') " .
                "ORDER BY t.Tar_Fecha_Fin ASC";
            break;

        /* 10. Listar empleados desde tabla personal (solo activos), misma fuente que rhu_con_personal */
        case 10:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $wh = "per.Per_Est='A'";
            if ($emp > 0) {
                $wh .= " AND per.Emp_Cod=$emp";
            }
            return "SELECT per.Per_Cod, p.Prs_Ced, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Nombre " .
                "FROM personal per " .
                "INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh ORDER BY p.Prs_Ape, p.Prs_Nom";
            break;

        /* 11.5 Verificar si ya existe asignación activa (Tar_Cod + Per_Cod) - evita duplicados */
        case 12:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            $per_cod = isset($Par_Sql['Per_Cod']) ? $n($Par_Sql['Per_Cod']) : $n($Par_Sql[1]);
            return "SELECT 1 AS existe FROM aud_tareas_asignadas WHERE Tar_Cod=$tar_cod AND Per_Cod=$per_cod AND Tas_Est='A' LIMIT 1";
            break;

        /* 11. Listar asignaciones (tarea + empleado + fecha) para mostrar cuadro de asignaciones */
        case 11:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : (isset($Par_Sql[1]) ? $n($Par_Sql[1]) : 0);
            $per_cod = isset($Par_Sql['Per_Cod']) ? $n($Par_Sql['Per_Cod']) : (isset($Par_Sql[2]) ? $n($Par_Sql[2]) : 0);
            $wh = "ta.Tas_Est='A' AND t.Tar_Est='A'";
            if ($emp > 0) {
                $wh .= " AND t.Emp_Cod=$emp";
            }
            if ($tar_cod > 0) {
                $wh .= " AND ta.Tar_Cod=$tar_cod";
            }
            if ($per_cod > 0) {
                $wh .= " AND ta.Per_Cod=$per_cod";
            }
            return "SELECT ta.Tas_Cod, ta.Tar_Cod, t.Tar_Titulo, t.Tar_Descripcion, t.Tar_Estado, t.Tar_Fecha_Inicio, t.Tar_Fecha_Fin, t.Tar_Fecha_Culminacion, ta.Per_Cod, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Empleado_Nombre, ta.Tas_Fecha_Asignacion " .
                "FROM aud_tareas_asignadas ta " .
                "INNER JOIN aud_tareas t ON ta.Tar_Cod=t.Tar_Cod " .
                "INNER JOIN personal per ON ta.Per_Cod=per.Per_Cod " .
                "INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh ORDER BY ta.Tas_Fecha_Asignacion DESC";
            break;

        /* 30. Listar asignaciones con avance - sin Tar_Fecha_Culminacion (fallback si migración no ejecutada) */
        case 30:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : (isset($Par_Sql[1]) ? $n($Par_Sql[1]) : 0);
            $per_cod = isset($Par_Sql['Per_Cod']) ? $n($Par_Sql['Per_Cod']) : (isset($Par_Sql[2]) ? $n($Par_Sql[2]) : 0);
            $wh = "ta.Tas_Est='A' AND t.Tar_Est='A'";
            if ($emp > 0) {
                $wh .= " AND t.Emp_Cod=$emp";
            }
            if ($tar_cod > 0) {
                $wh .= " AND ta.Tar_Cod=$tar_cod";
            }
            if ($per_cod > 0) {
                $wh .= " AND ta.Per_Cod=$per_cod";
            }
            return "SELECT ta.Tas_Cod, ta.Tar_Cod, t.Tar_Titulo, t.Tar_Descripcion, t.Tar_Estado, t.Tar_Fecha_Inicio, t.Tar_Fecha_Fin, ta.Per_Cod, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Empleado_Nombre, ta.Tas_Fecha_Asignacion " .
                "FROM aud_tareas_asignadas ta " .
                "INNER JOIN aud_tareas t ON ta.Tar_Cod=t.Tar_Cod " .
                "INNER JOIN personal per ON ta.Per_Cod=per.Per_Cod " .
                "INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh ORDER BY ta.Tas_Fecha_Asignacion DESC";
            break;

        /* 23. Listar asignaciones (mínimo) sin Tar_Fecha_Culminacion por si la migración no se ejecutó */
        case 23:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : (isset($Par_Sql[1]) ? $n($Par_Sql[1]) : 0);
            $wh = "ta.Tas_Est='A' AND t.Tar_Est='A'";
            if ($emp > 0) {
                $wh .= " AND t.Emp_Cod=$emp";
            }
            if ($tar_cod > 0) {
                $wh .= " AND ta.Tar_Cod=$tar_cod";
            }
            return "SELECT ta.Tas_Cod, ta.Tar_Cod, t.Tar_Titulo, t.Tar_Estado, t.Tar_Fecha_Fin, ta.Per_Cod, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Empleado_Nombre, ta.Tas_Fecha_Asignacion " .
                "FROM aud_tareas_asignadas ta " .
                "INNER JOIN aud_tareas t ON ta.Tar_Cod=t.Tar_Cod " .
                "INNER JOIN personal per ON ta.Per_Cod=per.Per_Cod " .
                "INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh ORDER BY ta.Tas_Fecha_Asignacion DESC";
            break;

        /* 24. Mis tareas asignadas (sin Tar_Fecha_Culminacion) para formulario de avances */
        case 24:
            $per_cod = isset($Par_Sql['Per_Cod']) ? $n($Par_Sql['Per_Cod']) : $n($Par_Sql[0]);
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[1]) ? $n($Par_Sql[1]) : 0);
            $wh = "ta.Tas_Est='A' AND t.Tar_Est='A' AND ta.Per_Cod=$per_cod";
            if ($emp > 0) {
                $wh .= " AND t.Emp_Cod=$emp";
            }
            return "SELECT ta.Tas_Cod, ta.Tar_Cod, t.Tar_Titulo, t.Tar_Descripcion, t.Tar_Prioridad, t.Tar_Estado, t.Tar_Fecha_Inicio, t.Tar_Fecha_Fin, t.Tar_Fecha_Culminacion, ta.Tas_Fecha_Asignacion, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Empleado_Nombre " .
                "FROM aud_tareas_asignadas ta " .
                "INNER JOIN aud_tareas t ON ta.Tar_Cod=t.Tar_Cod " .
                "INNER JOIN personal per ON ta.Per_Cod=per.Per_Cod " .
                "INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh ORDER BY ta.Tas_Fecha_Asignacion DESC";
            break;

        /* 27. Mis tareas asignadas (sin Tar_Fecha_Culminacion) - fallback si la migración no se ejecutó */
        case 27:
            $per_cod = isset($Par_Sql['Per_Cod']) ? $n($Par_Sql['Per_Cod']) : $n($Par_Sql[0]);
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[1]) ? $n($Par_Sql[1]) : 0);
            $wh = "ta.Tas_Est='A' AND t.Tar_Est='A' AND ta.Per_Cod=$per_cod";
            if ($emp > 0) {
                $wh .= " AND t.Emp_Cod=$emp";
            }
            return "SELECT ta.Tas_Cod, ta.Tar_Cod, t.Tar_Titulo, t.Tar_Descripcion, t.Tar_Prioridad, t.Tar_Estado, t.Tar_Fecha_Inicio, t.Tar_Fecha_Fin, ta.Tas_Fecha_Asignacion, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Empleado_Nombre " .
                "FROM aud_tareas_asignadas ta " .
                "INNER JOIN aud_tareas t ON ta.Tar_Cod=t.Tar_Cod " .
                "INNER JOIN personal per ON ta.Per_Cod=per.Per_Cod " .
                "INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh ORDER BY ta.Tas_Fecha_Asignacion DESC";
            break;

        /* 16. Obtener Per_Cod del usuario logueado (usuarios.Prs_Cod = personal.Prs_Cod) */
        case 16:
            $usu_cod = isset($Par_Sql['Usu_Cod']) ? $n($Par_Sql['Usu_Cod']) : $n($Par_Sql[0]);
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[1]) ? $n($Par_Sql[1]) : 0);
            $wh = "u.Usu_Cod=$usu_cod AND per.Per_Est='A'";
            if ($emp > 0) {
                $wh .= " AND per.Emp_Cod=$emp";
            }
            return "SELECT per.Per_Cod FROM personal per INNER JOIN usuarios u ON per.Prs_Cod=u.Prs_Cod WHERE $wh LIMIT 1";
            break;

        /* 36. Eliminar tarea (soft delete: Tar_Est='I') */
        case 36:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            return "UPDATE aud_tareas SET Tar_Est='I' WHERE Tar_Cod=$tar_cod AND Tar_Est='A'";
            break;

        /* 38. Obtener una tarea por Tar_Cod (para editar) */
        case 38:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            return "SELECT Tar_Cod, Tar_Titulo, Tar_Descripcion, Tar_Prioridad, Tar_Fecha_Inicio, Tar_Fecha_Fin, Tar_Estado FROM aud_tareas WHERE Tar_Cod=$tar_cod AND Tar_Est='A' LIMIT 1";
            break;

        /* 39. Actualizar tarea (titulo, descripcion, prioridad, fechas, estado) */
        case 39:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            $titulo = isset($Par_Sql['Tar_Titulo_safe']) ? $Par_Sql['Tar_Titulo_safe'] : (isset($Par_Sql['Tar_Titulo']) ? $e(trim($Par_Sql['Tar_Titulo'])) : $e($Par_Sql[1]));
            $desc = isset($Par_Sql['Tar_Descripcion_safe']) ? $Par_Sql['Tar_Descripcion_safe'] : (isset($Par_Sql['Tar_Descripcion']) ? $e(trim($Par_Sql['Tar_Descripcion'])) : '');
            $prioridad = isset($Par_Sql['Tar_Prioridad']) && in_array($Par_Sql['Tar_Prioridad'], array('Alta', 'Media', 'Baja')) ? $e($Par_Sql['Tar_Prioridad']) : 'Media';
            $fec_ini = isset($Par_Sql['Tar_Fecha_Inicio']) ? $e(trim($Par_Sql['Tar_Fecha_Inicio'])) : '';
            $fec_fin_raw = isset($Par_Sql['Tar_Fecha_Fin']) ? trim($Par_Sql['Tar_Fecha_Fin']) : '';
            $fec_fin = ($fec_fin_raw !== '') ? "'" . $e($fec_fin_raw) . "'" : 'NULL';
            $estado = isset($Par_Sql['Tar_Estado']) ? $e($Par_Sql['Tar_Estado']) : (isset($Par_Sql[6]) ? $e($Par_Sql[6]) : 'Pendiente');
            return "UPDATE aud_tareas SET Tar_Titulo='$titulo', Tar_Descripcion='$desc', Tar_Prioridad='$prioridad', Tar_Fecha_Inicio='$fec_ini', Tar_Fecha_Fin=$fec_fin, Tar_Estado='$estado' WHERE Tar_Cod=$tar_cod AND Tar_Est='A'";
            break;

        /* 18. Eliminar asignación (soft delete: Tas_Est='I') */
        case 18:
            $tas_cod = isset($Par_Sql['Tas_Cod']) ? $n($Par_Sql['Tas_Cod']) : $n($Par_Sql[0]);
            return "UPDATE aud_tareas_asignadas SET Tas_Est='I' WHERE Tas_Cod=$tas_cod AND Tas_Est='A'";
            break;

        /* 20. Verificar que la tarea está asignada al Per_Cod (para que el asignado pueda actualizar fecha fin) */
        case 20:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            $per_cod = isset($Par_Sql['Per_Cod']) ? $n($Par_Sql['Per_Cod']) : $n($Par_Sql[1]);
            return "SELECT 1 AS ok FROM aud_tareas_asignadas WHERE Tar_Cod=$tar_cod AND Per_Cod=$per_cod AND Tas_Est='A' LIMIT 1";
            break;

        /* 17. Listar asignaciones del usuario (mis tareas asignadas) - filtro por Per_Cod */
        case 17:
            $per_cod = isset($Par_Sql['Per_Cod']) ? $n($Par_Sql['Per_Cod']) : $n($Par_Sql[0]);
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[1]) ? $n($Par_Sql[1]) : 0);
            $wh = "ta.Tas_Est='A' AND t.Tar_Est='A' AND ta.Per_Cod=$per_cod";
            if ($emp > 0) {
                $wh .= " AND t.Emp_Cod=$emp";
            }
            return "SELECT ta.Tas_Cod, ta.Tar_Cod, t.Tar_Titulo, t.Tar_Estado, t.Tar_Fecha_Fin, t.Tar_Fecha_Culminacion, ta.Tas_Fecha_Asignacion, CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Empleado_Nombre " .
                "FROM aud_tareas_asignadas ta " .
                "INNER JOIN aud_tareas t ON ta.Tar_Cod=t.Tar_Cod " .
                "INNER JOIN personal per ON ta.Per_Cod=per.Per_Cod " .
                "INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh ORDER BY ta.Tas_Fecha_Asignacion DESC";
            break;

        /* 21. Grid paginado de tareas (compatible con getPageGridJson) */
        case 21:
            if (empty($Par_Sql['limits']) || $Par_Sql['limits'] === "" || $Par_Sql['limits'] === null) {
                $campos = "COUNT(t.Tar_Cod) as total";
            } else {
                $ord = !empty($Par_Sql['sidx']) ? addslashes($Par_Sql['sidx']) : 't.Tar_Fecha_Fin';
                $dir = (!empty($Par_Sql['sord']) && strtoupper($Par_Sql['sord']) === 'DESC') ? 'DESC' : 'ASC';
                $Par_Sql['limits'] = "ORDER BY $ord $dir " . $Par_Sql['limits'];
                $campos = "t.Tar_Cod, t.Tar_Titulo, t.Tar_Descripcion, t.Tar_Prioridad, t.Tar_Fecha_Inicio, t.Tar_Fecha_Fin, " .
                    "t.Tar_Estado, t.Usu_Creador, t.Emp_Cod, t.Tar_Est";
            }
            $wh = "t.Tar_Est='A'";
            if (!empty($Par_Sql['Emp_Cod'])) {
                $wh .= " AND t.Emp_Cod=" . $n($Par_Sql['Emp_Cod']);
            }
            $search = "1=1";
            if (!empty($Par_Sql['search'])) {
                $op = isset($Par_Sql['op_opciones']) ? $Par_Sql['op_opciones'] : 'd';
                if ($op === 'c') {
                    $search = "t.Tar_Cod=" . $n($Par_Sql['search']);
                } else {
                    $bus = $e($Par_Sql['search']);
                    $search = "(t.Tar_Titulo LIKE '%$bus%' OR t.Tar_Descripcion LIKE '%$bus%')";
                }
            }
            $sql = "SELECT $campos FROM aud_tareas t WHERE $wh AND $search";
            if (!empty($Par_Sql['limits']) && $Par_Sql['limits'] !== "" && $Par_Sql['limits'] !== null) {
                $sql .= " " . $Par_Sql['limits'];
            }
            return $sql;
            break;

        /* 31. Listar tareas para detalle KPI (tipo: all|completadas|atrasadas, con filtro período) - incluye asignado */
        case 31:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $tipo = isset($Par_Sql['Tipo']) ? $e($Par_Sql['Tipo']) : (isset($Par_Sql[1]) ? $e($Par_Sql[1]) : 'all');
            $fec_ini = isset($Par_Sql['Fecha_Ini']) ? $e($Par_Sql['Fecha_Ini']) : (isset($Par_Sql[2]) ? $e($Par_Sql[2]) : '');
            $fec_fin = isset($Par_Sql['Fecha_Fin']) ? $e($Par_Sql['Fecha_Fin']) : (isset($Par_Sql[3]) ? $e($Par_Sql[3]) : '');
            $wh = "t.Tar_Est='A'";
            if ($emp > 0) $wh .= " AND t.Emp_Cod=$emp";
            if ($fec_ini !== '') $wh .= " AND t.Tar_Fecha_Inicio>='$fec_ini'";
            if ($fec_fin !== '') $wh .= " AND t.Tar_Fecha_Inicio<='$fec_fin'";
            if ($tipo === 'completadas') $wh .= " AND t.Tar_Estado='Finalizada'";
            elseif ($tipo === 'atrasadas') {
                $wh .= " AND ( (t.Tar_Estado='Finalizada' AND t.Tar_Fecha_Culminacion IS NOT NULL AND t.Tar_Fecha_Fin IS NOT NULL AND t.Tar_Fecha_Culminacion > t.Tar_Fecha_Fin) OR (t.Tar_Estado<>'Finalizada' AND t.Tar_Fecha_Fin IS NOT NULL AND t.Tar_Fecha_Fin < CURDATE()) )";
            }
            return "SELECT t.Tar_Cod, t.Tar_Titulo, t.Tar_Estado, t.Tar_Fecha_Inicio, t.Tar_Fecha_Fin, t.Tar_Fecha_Culminacion, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Empleado_Nombre " .
                "FROM aud_tareas t " .
                "LEFT JOIN aud_tareas_asignadas ta ON t.Tar_Cod=ta.Tar_Cod AND ta.Tas_Est='A' " .
                "LEFT JOIN personal per ON ta.Per_Cod=per.Per_Cod " .
                "LEFT JOIN persona p ON per.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh ORDER BY t.Tar_Fecha_Inicio DESC, t.Tar_Cod, Empleado_Nombre LIMIT 100";
            break;

        /* 32. Tareas que requieren atención (atrasadas + próximas a vencer en 7 días), con empleado asignado */
        case 32:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $wh = "ta.Tas_Est='A' AND t.Tar_Est='A'";
            if ($emp > 0) $wh .= " AND t.Emp_Cod=$emp";
            return "SELECT t.Tar_Cod, t.Tar_Titulo, t.Tar_Estado, t.Tar_Fecha_Inicio, t.Tar_Fecha_Fin, t.Tar_Fecha_Culminacion, " .
                "CONCAT(p.Prs_Ape,' ',p.Prs_Nom) AS Empleado_Nombre, " .
                "CASE WHEN t.Tar_Estado='Finalizada' AND t.Tar_Fecha_Culminacion IS NOT NULL AND t.Tar_Fecha_Fin IS NOT NULL AND t.Tar_Fecha_Culminacion > t.Tar_Fecha_Fin THEN 'atrasada' " .
                "WHEN t.Tar_Estado<>'Finalizada' AND t.Tar_Fecha_Fin IS NOT NULL AND t.Tar_Fecha_Fin < CURDATE() THEN 'atrasada' " .
                "WHEN t.Tar_Estado<>'Finalizada' AND t.Tar_Fecha_Fin IS NOT NULL AND t.Tar_Fecha_Fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'proxima' ELSE 'normal' END AS Tipo_Atencion " .
                "FROM aud_tareas t INNER JOIN aud_tareas_asignadas ta ON t.Tar_Cod=ta.Tar_Cod " .
                "INNER JOIN personal per ON ta.Per_Cod=per.Per_Cod INNER JOIN persona p ON per.Prs_Cod=p.Prs_Cod " .
                "WHERE $wh AND ( " .
                "(t.Tar_Estado='Finalizada' AND t.Tar_Fecha_Culminacion IS NOT NULL AND t.Tar_Fecha_Fin IS NOT NULL AND t.Tar_Fecha_Culminacion > t.Tar_Fecha_Fin) OR " .
                "(t.Tar_Estado<>'Finalizada' AND t.Tar_Fecha_Fin IS NOT NULL AND t.Tar_Fecha_Fin < CURDATE()) OR " .
                "(t.Tar_Estado<>'Finalizada' AND t.Tar_Fecha_Fin IS NOT NULL AND t.Tar_Fecha_Fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) " .
                ") ORDER BY t.Tar_Fecha_Fin ASC LIMIT 15";
            break;

        /* 33. Indicadores con filtro de período (Fecha_Ini, Fecha_Fin) */
        case 33:
            $emp = isset($Par_Sql['Emp_Cod']) ? $n($Par_Sql['Emp_Cod']) : (isset($Par_Sql[0]) ? $n($Par_Sql[0]) : 0);
            $fec_ini = isset($Par_Sql['Fecha_Ini']) ? $e($Par_Sql['Fecha_Ini']) : (isset($Par_Sql[1]) ? $e($Par_Sql[1]) : '');
            $fec_fin = isset($Par_Sql['Fecha_Fin']) ? $e($Par_Sql['Fecha_Fin']) : (isset($Par_Sql[2]) ? $e($Par_Sql[2]) : '');
            $wh = "Tar_Est='A'";
            if ($emp > 0) $wh .= " AND Emp_Cod=$emp";
            if ($fec_ini !== '') $wh .= " AND Tar_Fecha_Inicio>='$fec_ini'";
            if ($fec_fin !== '') $wh .= " AND Tar_Fecha_Inicio<='$fec_fin'";
            return "SELECT " .
                "(SELECT COUNT(*) FROM aud_tareas WHERE $wh) AS Total_Tareas, " .
                "(SELECT COUNT(*) FROM aud_tareas WHERE $wh AND Tar_Estado='Finalizada') AS Completadas, " .
                "(SELECT COUNT(*) FROM aud_tareas WHERE $wh AND ( (Tar_Estado='Finalizada' AND Tar_Fecha_Culminacion IS NOT NULL AND Tar_Fecha_Fin IS NOT NULL AND Tar_Fecha_Culminacion > Tar_Fecha_Fin) OR (Tar_Estado<>'Finalizada' AND Tar_Fecha_Fin IS NOT NULL AND Tar_Fecha_Fin < CURDATE()) )) AS Atrasadas";
            break;

        /* 40. Insertar adjunto de avance (captura/imagen) */
        case 40:
            $ava_cod = isset($Par_Sql['Ava_Cod']) ? $n($Par_Sql['Ava_Cod']) : $n($Par_Sql[0]);
            $nombre = isset($Par_Sql['Adj_Nombre']) ? $e($Par_Sql['Adj_Nombre']) : (isset($Par_Sql[1]) ? $e($Par_Sql[1]) : '');
            $ruta = isset($Par_Sql['Adj_Ruta']) ? $e($Par_Sql['Adj_Ruta']) : (isset($Par_Sql[2]) ? $e($Par_Sql[2]) : '');
            $fec = isset($Par_Sql['Adj_Fecha']) ? $e($Par_Sql['Adj_Fecha']) : date('Y-m-d H:i:s');
            return "INSERT INTO aud_tareas_avances_adjuntos (Ava_Cod, Adj_Nombre, Adj_Ruta, Adj_Fecha) VALUES($ava_cod,'$nombre','$ruta','$fec')";
            break;

        /* 41. Listar adjuntos por Ava_Cod */
        case 41:
            $ava_cod = isset($Par_Sql['Ava_Cod']) ? $n($Par_Sql['Ava_Cod']) : $n($Par_Sql[0]);
            return "SELECT Adj_Cod, Ava_Cod, Adj_Nombre, Adj_Ruta, Adj_Fecha FROM aud_tareas_avances_adjuntos WHERE Ava_Cod=$ava_cod ORDER BY Adj_Fecha DESC";
            break;

        /* 42. Insertar adjunto de tarea (imagen de contexto) */
        case 42:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            $nombre = isset($Par_Sql['Adj_Nombre']) ? $e($Par_Sql['Adj_Nombre']) : (isset($Par_Sql[1]) ? $e($Par_Sql[1]) : '');
            $ruta = isset($Par_Sql['Adj_Ruta']) ? $e($Par_Sql['Adj_Ruta']) : (isset($Par_Sql[2]) ? $e($Par_Sql[2]) : '');
            $fec = isset($Par_Sql['Adj_Fecha']) ? $e($Par_Sql['Adj_Fecha']) : date('Y-m-d H:i:s');
            return "INSERT INTO aud_tareas_adjuntos (Tar_Cod, Adj_Nombre, Adj_Ruta, Adj_Fecha) VALUES($tar_cod,'$nombre','$ruta','$fec')";
            break;

        /* 43. Listar adjuntos por Tar_Cod (imágenes de la tarea) */
        case 43:
            $tar_cod = isset($Par_Sql['Tar_Cod']) ? $n($Par_Sql['Tar_Cod']) : $n($Par_Sql[0]);
            return "SELECT Adj_Cod, Tar_Cod, Adj_Nombre, Adj_Ruta, Adj_Fecha FROM aud_tareas_adjuntos WHERE Tar_Cod=$tar_cod ORDER BY Adj_Fecha DESC";
            break;

        default:
            return "";
            break;
    }
}
