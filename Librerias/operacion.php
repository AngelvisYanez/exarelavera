<?php
/******************************************************/
/*  Capa de acceso a datos - TESORERIA (shim)         */
/*  Provee las funciones globales de tesoreria que    */
/*  las paginas legadas invocan directamente.         */
/******************************************************/
if (!class_exists('base_mysql', false)) {
    require_once(__DIR__ . '/../DATA/DAC.php');
}
if (!class_exists('MysqlConexion', false)) {
    require_once(__DIR__ . '/../DATA/MysqlConexion.php');
}
if (!class_exists('MysqlDatos', false)) {
    require_once(__DIR__ . '/../DATA/MysqlDatos.php');
}
if (!function_exists('sentencias_tes')) {
    require_once(__DIR__ . '/../adquisiciones/LOGICA/tes_sql_ccpp.php');
}
/******************************************************/
/*   Clases de conexion y datos de tesoreria          */
/******************************************************/
if (!class_exists('Class_Log_Conexion_Tes', false)) {
    class Class_Log_Conexion_Tes extends MysqlConexion {}
}
if (!class_exists('Class_Log_Datos_Tes', false)) {
    class Class_Log_Datos_Tes extends MysqlDatos {}
}
/******************************************************/
/*   Funciones globales de tesoreria                  */
/******************************************************/
if (!function_exists('consultas_tes')) {
    function consultas_tes($sen_sql, $paras)
    {
        $Par_Sql = explode('*', $paras);
        $dat_dis = isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : 'servicios';
        $obBD_con = new Class_Log_Conexion_Tes($dat_dis);
        $obBD = new Class_Log_Datos_Tes;
        $rs = $obBD->consulta(sentencias_tes($sen_sql, $Par_Sql), $obBD_con->conexion);
        if ($rs === false) {
            $rs = @mysqli_query($obBD_con->conexion, "SELECT * FROM (SELECT 1 AS __dummy) AS __t WHERE 1=0");
        }
        return $rs;
    }
}
if (!function_exists('consultasv_tes')) {
    function consultasv_tes($sen_sql, $paras, $conectar2)
    {
        $Par_Sql = explode('*', $paras);
        return mysqli_query($conectar2, sentencias_tes($sen_sql, $Par_Sql));
    }
}
if (!function_exists('open_trans_tes')) {
    function open_trans_tes()
    {
        $dat_dis = isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : 'servicios';
        $obBD_con = new Class_Log_Conexion_Tes($dat_dis);
        @mysqli_autocommit($obBD_con->conexion, false);
        @mysqli_query($obBD_con->conexion, "BEGIN");
        return $obBD_con->conexion;
    }
}
if (!function_exists('insercionesv_tes')) {
    function insercionesv_tes($sen_sql, $paras, $conectar2)
    {
        $Par_Sql = explode('*', $paras);
        if (@mysqli_query($conectar2, sentencias_tes($sen_sql, $Par_Sql)) != 1) {
            $_SESSION['Error'] = 1;
        }
    }
}
if (!function_exists('insercionesu_tes')) {
    function insercionesu_tes($sen_sql, $paras)
    {
        $Par_Sql = explode('*', $paras);
        $dat_dis = isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : 'servicios';
        $obBD_con = new Class_Log_Conexion_Tes($dat_dis);
        $obBD = new Class_Log_Datos_Tes;
        $obBD->grabarv_registros(sentencias_tes($sen_sql, $Par_Sql), $obBD_con->conexion);
    }
}
if (!function_exists('close_trans_tes')) {
    function close_trans_tes($conectar2)
    {
        if (isset($_SESSION['Error']) && $_SESSION['Error'] != 1) {
            @mysqli_commit($conectar2);
        } else {
            @mysqli_rollback($conectar2);
        }
        @mysqli_close($conectar2);
        unset($_SESSION['Error']);
    }
}
if (!function_exists('maxi_min_fac')) {
    function maxi_min_fac($ini, $fin, $tipo, $option, $tic)
    {
        $sen = ($tipo === 'M') ? 96 : 97;
        $rs = consultas_tes($sen, $ini . '*' . $fin . '*' . $option . '*' . $tic . '*');
        $row = @mysqli_fetch_assoc($rs);
        return is_array($row) ? $row['Num'] : '';
    }
}
