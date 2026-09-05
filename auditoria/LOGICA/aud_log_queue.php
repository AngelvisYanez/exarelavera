<?php
/**
 * Cola de auditor�a diferida: captura I/U/D de cualquier m�dulo/proceso
 * y persiste despu�s de responder. Si hay reglas en cfg_monitoreo, solo
 * se graban los m�dulos, directorios y procesos marcados (cualquier tabla
 * que toquen). Sin reglas, aplica el fallback AUDIT_TABLES.
 *
 * @package auditoria.LOGICA
 */
require_once(dirname(__FILE__) . '/../../DATA/libs/Env.php');

/** Base maestra de catalogo (usuarios, persona, empresas, sucursal, procesos, organizado). */
if (!function_exists('aud_master_db')) {
    function aud_master_db()
    {
        if (session_id() !== '' && !empty($_SESSION['Ses_Dat_Dis'])) {
            return preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']);
        }
        $db = \Env::get('DB_DATABASE', 'exa_master');
        if (is_string($db) && $db !== '') {
            return preg_replace('/[^a-zA-Z0-9_]/', '', $db);
        }
        return 'exa_master';
    }
}

class AuditQueue
{
    const DEFAULT_TABLES = 'comprobantes,asientos,manifiesto,manifiesto_turnos_cab,manifiesto_turnos_det,manifiesto_visitante,manifiesto_evento,ventas,ventas_det';
    const SKIP_TABLES = 'logs,sesion,cfg_monitoreo,eventos,tablas,campos,reservadas';
    const SKIP_SCHEMAS = 'auditoria,mysql,information_schema,performance_schema,sys';
    const DEFAULT_MAX_QUEUE = 150;
    const DEFAULT_RETENTION_DAYS = 180;
    const AUDIT_DB = 'auditoria';

    private static $queue = array();
    private static $txnLevel = 0;
    private static $txnQueue = array();
    private static $registered = false;
    private static $flushing = false;
    private static $schemaReady = false;
    private static $tables = null;
    private static $skipTables = null;
    private static $cfgCache = array();
    private static $oldByKey = array();
    private static $purged = false;

    public static function enabled()
    {
        $flag = \Env::get('AUDIT_ENABLED', false);
        return $flag === true || $flag === 1 || $flag === '1' || strtolower((string)$flag) === 'true';
    }

    public static function queueCount()
    {
        return count(self::$queue);
    }

    public static function stagedCount()
    {
        $c = 0;
        foreach (self::$txnQueue as $levelQueue) {
            $c += count($levelQueue);
        }
        return $c;
    }

    public static function inTransaction()
    {
        return self::$txnLevel > 0;
    }

    public static function beginTransaction($conexion = null)
    {
        self::$txnLevel++;
        $idx = self::$txnLevel - 1;
        if (!isset(self::$txnQueue[$idx])) {
            self::$txnQueue[$idx] = array();
        }
    }

    public static function commit($conexion = null)
    {
        if (self::$txnLevel <= 0) {
            return;
        }
        $idx = self::$txnLevel - 1;
        $events = isset(self::$txnQueue[$idx]) ? self::$txnQueue[$idx] : array();
        unset(self::$txnQueue[$idx]);
        self::$txnLevel--;

        $max = (int)\Env::get('AUDIT_MAX_QUEUE', self::DEFAULT_MAX_QUEUE);
        if ($max < 20) $max = 20;
        if ($max > 500) $max = 500;

        if (self::$txnLevel > 0) {
            $parentIdx = self::$txnLevel - 1;
            if (!isset(self::$txnQueue[$parentIdx])) {
                self::$txnQueue[$parentIdx] = array();
            }
            foreach ($events as $ev) {
                if (count(self::$txnQueue[$parentIdx]) < $max) {
                    self::$txnQueue[$parentIdx][] = $ev;
                }
            }
        } else {
            foreach ($events as $ev) {
                if (count(self::$queue) < $max) {
                    self::$queue[] = $ev;
                }
            }
            if (!empty(self::$queue)) {
                self::registerFlush();
            }
        }
    }

    public static function rollback($conexion = null)
    {
        if (self::$txnLevel <= 0) {
            return;
        }
        $idx = self::$txnLevel - 1;
        unset(self::$txnQueue[$idx]);
        self::$txnLevel--;
    }

    public static function resetForTests()
    {
        self::$queue = array();
        self::$txnLevel = 0;
        self::$txnQueue = array();
        self::$flushing = false;
        self::$schemaReady = false;
        self::$tables = null;
        self::$skipTables = null;
        self::$cfgCache = array();
        self::$oldByKey = array();
        self::$purged = false;
    }

    public static function captureBefore($sql, $conexion = null)
    {
        try {
            if (!self::enabled() || !is_string($sql) || $sql === '') {
                return;
            }
            if (stripos($sql, 'auditoria.') !== false) {
                return;
            }
            $sqlTrim = trim($sql);
            if (!preg_match('/^\s*(UPDATE|DELETE)\b/i', $sqlTrim)) {
                return;
            }
            if (!is_object($conexion)) {
                return;
            }
            $table = '';
            if (preg_match('/UPDATE\s+(?:[`]?[a-zA-Z0-9_]+[`]?\.)?[`]?([a-zA-Z0-9_]+)/i', $sqlTrim, $mTab)) {
                $table = strtolower($mTab[1]);
            } elseif (preg_match('/DELETE\s+FROM\s+(?:[`]?[a-zA-Z0-9_]+[`]?\.)?[`]?([a-zA-Z0-9_]+)/i', $sqlTrim, $mTab)) {
                $table = strtolower($mTab[1]);
            }
            if ($table === '' || self::isSkippedTable($table, $sqlTrim)) {
                return;
            }
            $parts = preg_split('/\bWHERE\b/i', $sqlTrim, 2);
            $where = isset($parts[1]) ? trim($parts[1]) : '';
            if (!self::isSafeWhere($where)) {
                return;
            }
            $cols = '*';
            if (preg_match('/\bSET\s+(.*)$/is', isset($parts[0]) ? $parts[0] : '', $mSet)) {
                $cam = '';
                $val = '';
                self::splitSet($mSet[1], $cam, $val);
                $camList = array();
                foreach (explode(',', $cam) as $c) {
                    $c = preg_replace('/[^a-zA-Z0-9_]/', '', trim($c));
                    if ($c !== '') {
                        $camList[] = '`'.$c.'`';
                    }
                }
                if (count($camList) > 0) {
                    $cols = implode(',', $camList);
                }
            }
            $tabEsc = str_replace('`', '', $table);
            $rs = @mysqli_query($conexion, "SELECT {$cols} FROM `{$tabEsc}` WHERE {$where} LIMIT 1");
            if (!$rs || !is_object($rs)) {
                return;
            }
            $row = mysqli_fetch_assoc($rs);
            mysqli_free_result($rs);
            if (!is_array($row) || count($row) === 0) {
                return;
            }
            self::$oldByKey[self::oldKey($table, $where)] = $row;
        } catch (Exception $e) {
            return;
        }
    }

    public static function capture($sql, $conexion = null)
    {
        try {
            if (!self::enabled() || !is_string($sql) || $sql === '') {
                return;
            }
            if (stripos($sql, 'auditoria.') !== false) {
                return;
            }
            $evento = self::parseSql($sql, $conexion);
            if ($evento === null) {
                return;
            }
            self::push($evento);
        } catch (Exception $e) {
            return;
        }
    }

    public static function push($evento)
    {
        $max = (int)\Env::get('AUDIT_MAX_QUEUE', self::DEFAULT_MAX_QUEUE);
        if ($max < 20) {
            $max = 20;
        }
        if ($max > 500) {
            $max = 500;
        }
        if (self::$txnLevel > 0) {
            $idx = self::$txnLevel - 1;
            if (!isset(self::$txnQueue[$idx])) {
                self::$txnQueue[$idx] = array();
            }
            if (count(self::$txnQueue[$idx]) < $max) {
                self::$txnQueue[$idx][] = $evento;
            }
            return;
        }
        if (count(self::$queue) >= $max) {
            return;
        }
        self::$queue[] = $evento;
        self::registerFlush();
    }

    public static function registerFlush()
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;
        register_shutdown_function(array('AuditQueue', 'flush'));
    }

    public static function flush($sendPendingOutput = true)
    {
        if (self::$flushing || empty(self::$queue)) {
            return;
        }
        self::$flushing = true;

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            // En AJAX no tocar buffers/salida; solo persistir en segundo plano.
            $sendPendingOutput = false;
        }

        if (session_id() !== '') {
            @session_write_close();
        }
        if ($sendPendingOutput) {
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            if (function_exists('flush')) {
                @flush();
            }
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }

        $batch = self::$queue;
        self::$queue = array();

        $prevEr = error_reporting(0);
        $obPersist = ob_get_level();
        @ob_start();
        $con = null;
        try {
            $con = self::connectAuditoria($batch);
            if ($con) {
                self::ensureSchema($con);
            }
            foreach ($batch as $evento) {
                try {
                    self::persistOne($evento, $con);
                } catch (Throwable $eOne) {
                }
            }
            if ($con) {
                self::purgeOldLogs($con);
            }
        } catch (Throwable $e) {
        }
        if ($con) {
            @mysqli_close($con);
        }
        while (ob_get_level() > $obPersist) {
            @ob_end_clean();
        }
        error_reporting($prevEr);
        self::$flushing = false;
    }

    private static function allowedTables()
    {
        if (self::$tables !== null) {
            return self::$tables;
        }
        $raw = \Env::get('AUDIT_TABLES', self::DEFAULT_TABLES);
        $list = array();
        foreach (explode(',', (string)$raw) as $name) {
            $name = strtolower(trim($name));
            if ($name !== '') {
                $list[$name] = true;
            }
        }
        self::$tables = $list;
        return self::$tables;
    }

    private static function isWhitelistedTable($table)
    {
        $allowed = self::allowedTables();
        if (isset($allowed['*']) || isset($allowed['all'])) {
            return true;
        }
        $table = strtolower(trim((string)$table));
        return $table !== '' && !empty($allowed[$table]);
    }

    private static function skippedTables()
    {
        if (self::$skipTables !== null) {
            return self::$skipTables;
        }
        $list = array();
        $raw = self::SKIP_TABLES . ',' . (string)\Env::get('AUDIT_SKIP_TABLES', '');
        foreach (explode(',', $raw) as $name) {
            $name = strtolower(trim($name));
            if ($name !== '') {
                $list[$name] = true;
            }
        }
        self::$skipTables = $list;
        return self::$skipTables;
    }

    private static function isSkippedTable($table, $sql)
    {
        $table = strtolower(trim((string)$table));
        if ($table === '') {
            return true;
        }
        $skip = self::skippedTables();
        if (!empty($skip[$table])) {
            return true;
        }
        if (strpos($table, 'tmp_') === 0 || strpos($table, 'temp_') === 0) {
            return true;
        }
        if (preg_match('/(?:INTO|UPDATE|DELETE\s+FROM)\s+[`]?([a-zA-Z0-9_]+)[`]?\s*\./i', (string)$sql, $mSch)) {
            $schema = strtolower($mSch[1]);
            $schemas = explode(',', self::SKIP_SCHEMAS);
            if (in_array($schema, $schemas, true)) {
                return true;
            }
        }
        return false;
    }

    private static function isSafeWhere($where)
    {
        $w = trim((string)$where);
        if ($w === '' || strlen($w) > 400) {
            return false;
        }
        if (preg_match('/;|--|\/\*|\bunion\b|\bsleep\b|\bbenchmark\b|\bload_file\b|\boutfile\b|\binto\s+outfile\b/i', $w)) {
            return false;
        }
        return true;
    }

    private static function oldKey($table, $where)
    {
        return strtolower(trim((string)$table)).'|'.preg_replace('/\s+/', ' ', trim((string)$where));
    }

    private static function processNameCandidates()
    {
        $out = array();
        $keys = array('REQUEST_URI', 'PHP_SELF', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'HTTP_REFERER');
        foreach ($keys as $k) {
            if (empty($_SERVER[$k])) {
                continue;
            }
            $u = ($k === 'REQUEST_URI') ? strtok($_SERVER[$k], '?') : $_SERVER[$k];
            foreach (self::normalizeProcessCandidates($u) as $cand) {
                if (!isset($out[$cand])) {
                    $out[$cand] = true;
                }
            }
            if ($k === 'REQUEST_URI' && !empty($_SERVER['REQUEST_URI'])) {
                foreach (self::extractProcessCandidatesFromQuery($_SERVER['REQUEST_URI']) as $candQ) {
                    if (!isset($out[$candQ])) {
                        $out[$candQ] = true;
                    }
                }
            }
        }
        return array_keys($out);
    }

    private static function extractProcessCandidatesFromQuery($rawUri)
    {
        $out = array();
        $query = parse_url((string)$rawUri, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return $out;
        }
        $params = array();
        parse_str($query, $params);
        if (!is_array($params) || empty($params)) {
            return $out;
        }
        $stack = array_values($params);
        while (!empty($stack)) {
            $val = array_pop($stack);
            if (is_array($val)) {
                foreach ($val as $v2) {
                    $stack[] = $v2;
                }
                continue;
            }
            $txt = trim((string)$val);
            if ($txt === '') {
                continue;
            }
            // Considera rutas/valores que parecen nombre de proceso.
            if (stripos($txt, '.php') !== false || preg_match('/^[a-z]{3}_[a-z]{3}_.+/i', basename(str_replace('\\', '/', $txt)))) {
                foreach (self::normalizeProcessCandidates($txt) as $cand) {
                    if (!isset($out[$cand])) {
                        $out[$cand] = true;
                    }
                }
            }
        }
        return array_keys($out);
    }

    private static function normalizeProcessCandidates($raw)
    {
        $out = array();
        $val = str_replace('\\', '/', trim((string)$raw));
        if ($val === '') {
            return $out;
        }
        $variants = array($val);
        $base = basename($val);
        if ($base !== '' && $base !== $val) {
            $variants[] = $base;
        }
        foreach ($variants as $variant) {
            $variant = trim((string)$variant);
            if ($variant === '' || isset($out[$variant])) {
                continue;
            }
            $out[$variant] = true;
            $noPhp = preg_replace('/\.php$/i', '', $variant);
            if ($noPhp !== '' && !isset($out[$noPhp])) {
                $out[$noPhp] = true;
            }
        }
        return array_keys($out);
    }

    private static function firstProcessBasename()
    {
        $cands = self::processNameCandidates();
        return count($cands) > 0 ? $cands[0] : '';
    }

    private static function purgeOldLogs($con)
    {
        if (self::$purged) {
            return;
        }
        self::$purged = true;
        $days = (int)\Env::get('AUDIT_RETENTION_DAYS', self::DEFAULT_RETENTION_DAYS);
        if ($days < 30) {
            return;
        }
        if ($days > 3650) {
            $days = 3650;
        }
        $cut = date('Y-m-d H:i:s', strtotime('-'.$days.' days'));
        $cutEsc = mysqli_real_escape_string($con, $cut);
        self::q($con, "DELETE FROM `".self::AUDIT_DB."`.`logs` WHERE `Log_Fec` < '{$cutEsc}' LIMIT 800");
    }

    private static function parseSql($sql, $conexion)
    {
        $sqlTrim = trim($sql);
        if (!preg_match('/^\s*(INSERT|UPDATE|DELETE)\b/i', $sqlTrim, $mEve)) {
            return null;
        }
        $eve = strtoupper($mEve[1]);
        $eveIni = substr($eve, 0, 1);
        $table = '';
        if (preg_match('/INSERT\s+(?:IGNORE\s+)?INTO\s+(?:[`]?[a-zA-Z0-9_]+[`]?\.)?[`]?([a-zA-Z0-9_]+)/i', $sqlTrim, $mTab)) {
            $table = $mTab[1];
        } elseif (preg_match('/UPDATE\s+(?:[`]?[a-zA-Z0-9_]+[`]?\.)?[`]?([a-zA-Z0-9_]+)/i', $sqlTrim, $mTab)) {
            $table = $mTab[1];
        } elseif (preg_match('/DELETE\s+FROM\s+(?:[`]?[a-zA-Z0-9_]+[`]?\.)?[`]?([a-zA-Z0-9_]+)/i', $sqlTrim, $mTab)) {
            $table = $mTab[1];
        }
        $table = strtolower($table);
        if ($table === '' || self::isSkippedTable($table, $sqlTrim)) {
            return null;
        }

        $cam = '';
        $val = '';
        $int = '';
        if ($eveIni === 'I') {
            if (preg_match('/\(([^)]+)\)\s*VALUES\s*\((.*)\)\s*$/is', $sqlTrim, $mIns)) {
                $cam = trim($mIns[1]);
                $val = trim($mIns[2]);
            } elseif (preg_match('/\bSET\s+(.*)$/is', $sqlTrim, $mSet)) {
                self::splitSet($mSet[1], $cam, $val);
            }
            if (is_object($conexion)) {
                $int = (string)@mysqli_insert_id($conexion);
            }
        } elseif ($eveIni === 'U') {
            $parts = preg_split('/\bWHERE\b/i', $sqlTrim, 2);
            $setPart = $parts[0];
            $int = isset($parts[1]) ? trim($parts[1]) : '';
            if (preg_match('/\bSET\s+(.*)$/is', $setPart, $mSet)) {
                self::splitSet($mSet[1], $cam, $val);
            }
        } else {
            $parts = preg_split('/\bWHERE\b/i', $sqlTrim, 2);
            $int = isset($parts[1]) ? trim($parts[1]) : '';
        }

        $url = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            $url = strtok($_SERVER['REQUEST_URI'], '?');
        } elseif (isset($_SERVER['PHP_SELF'])) {
            $url = $_SERVER['PHP_SELF'];
        }
        $pcsNom = self::firstProcessBasename();
        $old = array();
        if ($int !== '') {
            $okey = self::oldKey($table, $int);
            if (isset(self::$oldByKey[$okey]) && is_array(self::$oldByKey[$okey])) {
                $old = self::$oldByKey[$okey];
            }
        }

        return array(
            'eve' => $eveIni,
            'table' => $table,
            'cam' => $cam,
            'val' => $val,
            'int' => $int,
            'old' => $old,
            'usu' => self::sess('Ses_Usu_Cod', 0),
            'emp' => self::sess('Ses_Emp_Cod', 0),
            'suc' => self::sess('Ses_Suc_Cod', 0),
            'url' => $url,
            'pcs' => $pcsNom,
            'dat_dis' => self::sess('Ses_Dat_Dis', ''),
            'dat_aut' => self::sess('Ses_Dat_Aut', 'auditoria'),
            'fec' => date('Y-m-d H:i:s')
        );
    }

    private static function splitSet($setSql, &$cam, &$val)
    {
        $camParts = array();
        $valParts = array();
        $chunks = explode(',', $setSql);
        foreach ($chunks as $chunk) {
            $eq = explode('=', $chunk, 2);
            if (count($eq) === 2) {
                $camParts[] = trim(str_replace('`', '', $eq[0]));
                $valParts[] = trim($eq[1]);
            }
        }
        $cam = implode(',', $camParts);
        $val = implode(',', $valParts);
    }

    private static function sess($key, $default)
    {
        if (isset($_SESSION[$key]) && $_SESSION[$key] !== '') {
            return $_SESSION[$key];
        }
        if (isset($GLOBALS[$key]) && $GLOBALS[$key] !== '') {
            return $GLOBALS[$key];
        }
        return $default;
    }

    private static function persistOne($evento, $con)
    {
        if (!$con) {
            return;
        }

        $usu = (int)$evento['usu'];
        $emp = (int)$evento['emp'];
        $suc = (int)$evento['suc'];
        $pcsCod = self::lookupPcsCod($con, $evento['dat_dis'], $evento['pcs'], $emp);
        if ($pcsCod <= 0) {
            foreach (self::processNameCandidates() as $cand) {
                $pcsCod = self::lookupPcsCod($con, $evento['dat_dis'], $cand, $emp);
                if ($pcsCod > 0) {
                    $evento['pcs'] = $cand;
                    break;
                }
            }
        }
        if (!self::isProcessEnabled($con, $emp, $pcsCod, isset($evento['dat_dis']) ? $evento['dat_dis'] : '')) {
            return;
        }
        if (!self::hasCfgRules($con, $emp) && !self::isWhitelistedTable($evento['table'])) {
            return;
        }
        $eveCod = self::lookupEveCod($con, $evento['eve']);
        $tabCod = self::lookupTabCod($con, $evento['table'], isset($evento['dat_dis']) ? $evento['dat_dis'] : '');
        $cam = substr(str_replace("'", '~', $evento['cam']), 0, 250);
        $val = str_replace("'", '~', $evento['val']);
        $int = str_replace("'", '~', $evento['int']);
        if (!empty($evento['old']) && is_array($evento['old'])) {
            $oldBits = array();
            foreach ($evento['old'] as $ok => $ov) {
                if (is_array($ov) || is_object($ov)) {
                    continue;
                }
                $oldBits[] = preg_replace('/[^a-zA-Z0-9_]/', '', $ok).'='.str_replace(array('|', ';'), ' ', (string)$ov);
                if (count($oldBits) >= 12) {
                    break;
                }
            }
            if (count($oldBits) > 0) {
                $int = trim($int.' || OLD:'.implode(',', $oldBits));
            }
        }
        if ($pcsCod <= 0 && !empty($evento['pcs'])) {
            $int = 'Pcs_Nom='.str_replace(array('|', ';'), '', $evento['pcs']).' || '.$int;
        }
        $fec = mysqli_real_escape_string($con, $evento['fec']);
        $camEsc = mysqli_real_escape_string($con, $cam);
        $valEsc = mysqli_real_escape_string($con, $val);
        $intEsc = mysqli_real_escape_string($con, $int);

        $sql = "INSERT INTO `".self::AUDIT_DB."`.`logs`(`Usu_Cod`,`Pcs_Cod`,`Tab_Cod`,`Log_Fec`,`Eve_Cod`,`Log_Cam`,`Log_Val`,`Log_Int`,`Emp_Cod`,`Suc_Cod`)
            VALUES({$usu},{$pcsCod},{$tabCod},'{$fec}',{$eveCod},'{$camEsc}','{$valEsc}','{$intEsc}',{$emp},{$suc})";
        $ok = self::q($con, $sql);
        if (!$ok && (int)mysqli_errno($con) === 1054) {
            $sql = "INSERT INTO `".self::AUDIT_DB."`.`logs`(`Usu_Cod`,`Pcs_Cod`,`Tab_Cod`,`Log_Fec`,`Eve_Cod`,`Log_Cam`,`Log_Val`,`Log_Int`)
                VALUES({$usu},{$pcsCod},{$tabCod},'{$fec}',{$eveCod},'{$camEsc}','{$valEsc}','{$intEsc}')";
            self::q($con, $sql);
        }
    }

    private static function connectAuditoria($batch)
    {
        $db = self::AUDIT_DB;
        $host = \Env::get('DB_HOST', '127.0.0.1');
        $user = \Env::get('DB_USERNAME', 'root');
        $pass = \Env::get('DB_PASSWORD', '');
        $port = (int)\Env::get('DB_PORT', 3306);
        if ($pass === null) {
            $pass = '';
        }
        if (!function_exists('mysqli_init')) {
            return null;
        }
        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_OFF);
        }
        @ini_set('default_socket_timeout', '2');
        $con = mysqli_init();
        if (!$con) {
            return null;
        }
        if (defined('MYSQLI_OPT_CONNECT_TIMEOUT')) {
            @mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, 2);
        }
        $ok = @mysqli_real_connect($con, $host, $user, $pass, $db, $port);
        return $ok ? $con : null;
    }

    private static function q($con, $sql)
    {
        try {
            $r = @mysqli_query($con, $sql);
            if (!$r && $con && function_exists('mysqli_errno') && mysqli_errno($con) !== 0) {
                error_log('AuditQueue SQL[' . mysqli_errno($con) . ']: ' . mysqli_error($con) . ' | SQL: ' . substr($sql, 0, 300));
            }
            return $r;
        } catch (Exception $e) {
            error_log('AuditQueue exception: ' . $e->getMessage());
            return false;
        }
    }

    private static function ensureSchema($con)
    {
        if (self::$schemaReady) {
            return;
        }
        self::$schemaReady = true;
        self::q($con, "CREATE TABLE IF NOT EXISTS `".self::AUDIT_DB."`.`eventos` (
            `Eve_Cod` int(11) NOT NULL AUTO_INCREMENT,
            `Eve_Ini` char(1) NOT NULL,
            `Eve_Des` varchar(100) DEFAULT NULL,
            PRIMARY KEY (`Eve_Cod`),
            KEY `Eve_Ini` (`Eve_Ini`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        self::q($con, "CREATE TABLE IF NOT EXISTS `".self::AUDIT_DB."`.`tablas` (
            `Tab_Cod` int(11) NOT NULL AUTO_INCREMENT,
            `Tab_Nom` varchar(64) NOT NULL,
            `Tab_Des` varchar(255) DEFAULT NULL,
            `Tab_Ali` varchar(64) DEFAULT NULL,
            PRIMARY KEY (`Tab_Cod`),
            KEY `Tab_Nom` (`Tab_Nom`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        self::q($con, "CREATE TABLE IF NOT EXISTS `".self::AUDIT_DB."`.`logs` (
            `Log_Cod` bigint(20) NOT NULL AUTO_INCREMENT,
            `Usu_Cod` int(11) NOT NULL,
            `Pcs_Cod` int(11) NOT NULL,
            `Tab_Cod` int(11) NOT NULL,
            `Log_Fec` datetime NOT NULL,
            `Eve_Cod` int(11) NOT NULL,
            `Log_Cam` varchar(255) DEFAULT NULL,
            `Log_Val` text,
            `Log_Int` text,
            `Emp_Cod` int(11) DEFAULT NULL,
            `Suc_Cod` int(11) DEFAULT NULL,
            PRIMARY KEY (`Log_Cod`),
            KEY `Emp_Fec` (`Emp_Cod`,`Log_Fec`),
            KEY `Usu_Fec` (`Usu_Cod`,`Log_Fec`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        self::q($con, "INSERT IGNORE INTO `".self::AUDIT_DB."`.`eventos` (`Eve_Cod`, `Eve_Ini`, `Eve_Des`) VALUES
            (1, 'F', 'Fallido'), (2, 'I', 'Insertar'), (3, 'U', 'Actualizar'), (4, 'D', 'Eliminar')");
        $seedTabs = array(
            'usuarios' => 'Usuarios del sistema',
            'comprobantes' => 'Comprobantes contables',
            'asientos' => 'Asientos contables',
            'manifiesto' => 'Manifiestos Relavera',
            'manifiesto_turnos_cab' => 'Turnos Relavera',
            'manifiesto_turnos_det' => 'Detalle de turnos Relavera',
            'manifiesto_visitante' => 'Visitantes Relavera',
            'manifiesto_evento' => 'Eventos Relavera',
            'ventas' => 'Facturas de venta',
            'ventas_det' => 'Detalle de facturas de venta',
            'caja_aper' => 'Apertura y cierre de caja'
        );
        foreach ($seedTabs as $nom => $des) {
            $nomEsc = mysqli_real_escape_string($con, $nom);
            $desEsc = mysqli_real_escape_string($con, $des);
            $exists = self::q($con, "SELECT `Tab_Cod` FROM `".self::AUDIT_DB."`.`tablas` WHERE `Tab_Nom` = '{$nomEsc}' LIMIT 1");
            $row = ($exists && is_object($exists)) ? mysqli_fetch_assoc($exists) : null;
            if ($exists && is_object($exists)) {
                mysqli_free_result($exists);
            }
            if (empty($row['Tab_Cod'])) {
                $aliEsc = $desEsc;
                if ($nom === 'manifiesto') $aliEsc = 'Manifiestos';
                if ($nom === 'manifiesto_turnos_cab') $aliEsc = 'Turnos';
                if ($nom === 'manifiesto_turnos_det') $aliEsc = 'Detalle de turnos';
                if ($nom === 'manifiesto_visitante') $aliEsc = 'Visitantes';
                if ($nom === 'manifiesto_evento') $aliEsc = 'Eventos';
                if ($nom === 'ventas') $aliEsc = 'Facturas de venta';
                if ($nom === 'ventas_det') $aliEsc = 'Detalle de venta';
                if ($nom === 'caja_aper') $aliEsc = 'Caja';
                self::q($con, "INSERT INTO `".self::AUDIT_DB."`.`tablas`(`Tab_Nom`,`Tab_Des`,`Tab_Ali`) VALUES('{$nomEsc}','{$desEsc}','{$aliEsc}')");
            }
        }
        $r = self::q($con, "SHOW COLUMNS FROM `".self::AUDIT_DB."`.`logs` LIKE 'Emp_Cod'");
        if ($r && mysqli_num_rows($r) == 0) {
            self::q($con, "ALTER TABLE `".self::AUDIT_DB."`.`logs`
                ADD `Emp_Cod` int(11) DEFAULT NULL,
                ADD `Suc_Cod` int(11) DEFAULT NULL,
                ADD KEY `Emp_Fec` (`Emp_Cod`,`Log_Fec`)");
            self::q($con, "ALTER TABLE `".self::AUDIT_DB."`.`logs` MODIFY `Log_Cam` varchar(255) DEFAULT NULL");
        }
        if ($r) {
            @mysqli_free_result($r);
        }
        self::q($con, "CREATE TABLE IF NOT EXISTS `".self::AUDIT_DB."`.`cfg_monitoreo` (
            `Cfg_Cod` INT(11) NOT NULL AUTO_INCREMENT,
            `Emp_Cod` INT(11) NOT NULL,
            `Org_Cod` INT(11) NOT NULL,
            `Pcs_Cod` INT(11) NOT NULL DEFAULT 0,
            `Cfg_Est` CHAR(1) NOT NULL DEFAULT 'A',
            `Cfg_Fec` DATETIME DEFAULT NULL,
            `Usu_Cod` INT(11) DEFAULT NULL,
            PRIMARY KEY (`Cfg_Cod`),
            UNIQUE KEY `uk_emp_org_pcs` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`),
            KEY `idx_emp_est` (`Emp_Cod`,`Cfg_Est`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        self::q($con, "CREATE TABLE IF NOT EXISTS `".self::AUDIT_DB."`.`campos` (
            `Cam_Cod` INT(11) NOT NULL AUTO_INCREMENT,
            `Tab_Cod` INT(11) NOT NULL,
            `Cam_Atr` VARCHAR(64) NOT NULL,
            `Cam_Des` VARCHAR(255) DEFAULT NULL,
            `Cam_Ali` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`Cam_Cod`),
            KEY `Tab_Atr` (`Tab_Cod`,`Cam_Atr`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        self::q($con, "ALTER TABLE `".self::AUDIT_DB."`.`logs` ADD INDEX `Pcs_Fec` (`Pcs_Cod`,`Log_Fec`)");
        self::q($con, "INSERT IGNORE INTO `".self::AUDIT_DB."`.`tablas` (`Tab_Nom`,`Tab_Des`,`Tab_Ali`) VALUES
            ('cfg_monitoreo','Configuracion de monitoreo','Configuracion de monitoreo'),
            ('sesion','Sesiones de usuario','Sesion')");
    }

    /**
     * Si la empresa tiene reglas en cfg_monitoreo, solo persiste procesos/modulos habilitados.
     * Sin reglas: permite el proceso (el fallback AUDIT_TABLES se aplica en persistOne).
     */
    private static function cfgCount($con, $emp)
    {
        $emp = (int)$emp;
        if ($emp <= 0) {
            return 0;
        }
        if (!isset(self::$cfgCache[$emp])) {
            $count = 0;
            $r = self::q($con, "SELECT COUNT(*) AS c FROM `".self::AUDIT_DB."`.`cfg_monitoreo` WHERE `Emp_Cod`={$emp} AND `Cfg_Est`='A'");
            if ($r) {
                $row = mysqli_fetch_assoc($r);
                mysqli_free_result($r);
                $count = isset($row['c']) ? (int)$row['c'] : 0;
            }
            self::$cfgCache[$emp] = $count;
        }
        return (int)self::$cfgCache[$emp];
    }

    private static function hasCfgRules($con, $emp)
    {
        return self::cfgCount($con, $emp) > 0;
    }

    private static function isProcessEnabled($con, $emp, $pcsCod, $datDis)
    {
        $emp = (int)$emp;
        if ($emp <= 0) {
            return true;
        }
        if (!self::hasCfgRules($con, $emp)) {
            return true;
        }

        $pcsCod = (int)$pcsCod;
        if ($pcsCod > 0) {
            $r = self::q($con, "SELECT 1 AS ok FROM `".self::AUDIT_DB."`.`cfg_monitoreo`
                WHERE `Emp_Cod`={$emp} AND `Cfg_Est`='A' AND `Pcs_Cod`={$pcsCod} LIMIT 1");
            if ($r) {
                $row = mysqli_fetch_assoc($r);
                mysqli_free_result($r);
                if (!empty($row['ok'])) {
                    return true;
                }
            }
            $orgs = self::lookupOrgChain($con, $datDis, $pcsCod);
            foreach ($orgs as $org) {
                if ($org <= 0) {
                    continue;
                }
                $r = self::q($con, "SELECT 1 AS ok FROM `".self::AUDIT_DB."`.`cfg_monitoreo`
                    WHERE `Emp_Cod`={$emp} AND `Cfg_Est`='A' AND `Org_Cod`={$org} AND `Pcs_Cod`=0 LIMIT 1");
                if ($r) {
                    $row = mysqli_fetch_assoc($r);
                    mysqli_free_result($r);
                    if (!empty($row['ok'])) {
                        return true;
                    }
                }
            }
            return false;
        }
        // Sin Pcs identificado: no registrar si hay whitelist activa
        return false;
    }

    private static function lookupOrgCod($con, $datDis, $pcsCod)
    {
        $chain = self::lookupOrgChain($con, $datDis, $pcsCod);
        return empty($chain) ? 0 : (int)$chain[0];
    }

    /**
     * Directorio del proceso y ancestros hasta la raiz (Org_Niv=0).
     * Permite que cfg_monitoreo con el modulo raiz cubra Registrar venta.
     */
    private static function lookupOrgChain($con, $datDis, $pcsCod)
    {
        $pcsCod = (int)$pcsCod;
        if ($pcsCod <= 0) {
            return array();
        }
        $dbs = self::menuDatabases($datDis);
        $org = 0;
        $dbUsed = '';
        foreach ($dbs as $db) {
            $r = self::q($con, "SELECT `Org_Cod` FROM `{$db}`.`procesos` WHERE `Pcs_Cod`={$pcsCod} LIMIT 1");
            if ($r) {
                $row = mysqli_fetch_assoc($r);
                mysqli_free_result($r);
                if (!empty($row['Org_Cod'])) {
                    $org = (int)$row['Org_Cod'];
                    $dbUsed = $db;
                    break;
                }
            }
        }
        if ($org <= 0 || $dbUsed === '') {
            return array();
        }
        $chain = array();
        $guard = 0;
        while ($org > 0 && $guard < 8) {
            $chain[] = $org;
            $r = self::q($con, "SELECT `Org_Niv` FROM `{$dbUsed}`.`organizado` WHERE `Org_Cod`={$org} LIMIT 1");
            $parent = 0;
            if ($r) {
                $row = mysqli_fetch_assoc($r);
                mysqli_free_result($r);
                $parent = isset($row['Org_Niv']) ? (int)$row['Org_Niv'] : 0;
            }
            if ($parent <= 0) {
                break;
            }
            $org = $parent;
            $guard++;
        }
        return $chain;
    }

    private static function menuDatabases($datDis)
    {
        $dbs = array(aud_master_db());
        $db = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$datDis);
        if ($db !== '' && $db !== aud_master_db() && !in_array($db, $dbs, true)) {
            $dbs[] = $db;
        }
        return $dbs;
    }

    private static function lookupEveCod($con, $eveIni)
    {
        $fallback = array('F' => 1, 'I' => 2, 'U' => 3, 'D' => 4);
        $ini = mysqli_real_escape_string($con, $eveIni);
        $r = self::q($con, "SELECT `Eve_Cod` FROM `".self::AUDIT_DB."`.`eventos` WHERE `Eve_Ini` = '{$ini}' LIMIT 1");
        if ($r) {
            $row = mysqli_fetch_assoc($r);
            mysqli_free_result($r);
            if (!empty($row['Eve_Cod'])) {
                return (int)$row['Eve_Cod'];
            }
        }
        return isset($fallback[$eveIni]) ? $fallback[$eveIni] : 2;
    }

    private static function lookupTabCod($con, $table, $datDis = '')
    {
        $nom = mysqli_real_escape_string($con, $table);
        $r = self::q($con, "SELECT `Tab_Cod` FROM `".self::AUDIT_DB."`.`tablas` WHERE `Tab_Nom` = '{$nom}' LIMIT 1");
        $id = 0;
        if ($r) {
            $row = mysqli_fetch_assoc($r);
            mysqli_free_result($r);
            if (!empty($row['Tab_Cod'])) {
                $id = (int)$row['Tab_Cod'];
            }
        }
        if ($id <= 0) {
            $ali = str_replace('_', ' ', $table);
            $aliEsc = mysqli_real_escape_string($con, $ali);
            self::q($con, "INSERT INTO `".self::AUDIT_DB."`.`tablas`(`Tab_Nom`,`Tab_Des`,`Tab_Ali`) VALUES('{$nom}','{$aliEsc}','{$aliEsc}')");
            $id = (int)mysqli_insert_id($con);
            if ($id <= 0) {
                $r = self::q($con, "SELECT `Tab_Cod` FROM `".self::AUDIT_DB."`.`tablas` WHERE `Tab_Nom` = '{$nom}' LIMIT 1");
                if ($r) {
                    $row = mysqli_fetch_assoc($r);
                    mysqli_free_result($r);
                    if (!empty($row['Tab_Cod'])) {
                        $id = (int)$row['Tab_Cod'];
                    }
                }
            }
        }
        if ($id > 0) {
            self::seedCampos($con, $id, $table, $datDis);
        }
        return $id > 0 ? $id : 1;
    }

    private static function seedCampos($con, $tabCod, $table, $datDis)
    {
        $tabCod = (int)$tabCod;
        if ($tabCod <= 0) {
            return;
        }
        $chk = self::q($con, "SELECT 1 AS ok FROM `".self::AUDIT_DB."`.`campos` WHERE `Tab_Cod`={$tabCod} LIMIT 1");
        if ($chk) {
            $row = mysqli_fetch_assoc($chk);
            mysqli_free_result($chk);
            if (!empty($row['ok'])) {
                return;
            }
        }
        $db = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$datDis);
        if ($db === '') {
            $db = aud_master_db();
        }
        $tab = mysqli_real_escape_string($con, $table);
        $rs = self::q($con, "SELECT COLUMN_NAME, COLUMN_COMMENT FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA='{$db}' AND TABLE_NAME='{$tab}'");
        if (!$rs) {
            return;
        }
        while ($col = mysqli_fetch_assoc($rs)) {
            $atr = isset($col['COLUMN_NAME']) ? trim($col['COLUMN_NAME']) : '';
            if ($atr === '') {
                continue;
            }
            $des = isset($col['COLUMN_COMMENT']) ? trim($col['COLUMN_COMMENT']) : '';
            if ($des === '') {
                $des = str_replace('_', ' ', $atr);
            }
            $atrEsc = mysqli_real_escape_string($con, $atr);
            $desEsc = mysqli_real_escape_string($con, substr($des, 0, 250));
            self::q($con, "INSERT INTO `".self::AUDIT_DB."`.`campos` (`Tab_Cod`,`Cam_Atr`,`Cam_Des`,`Cam_Ali`)
                VALUES ({$tabCod},'{$atrEsc}','{$desEsc}','{$desEsc}')");
        }
        mysqli_free_result($rs);
    }

    private static function lookupPcsCod($con, $datDis, $pcsNom, $emp = 0)
    {
        $candidates = self::normalizeProcessCandidates($pcsNom);
        if (count($candidates) === 0) {
            return 0;
        }
        $hasCfg = self::hasCfgRules($con, (int)$emp);
        $dbs = self::menuDatabases($datDis);
        foreach ($candidates as $cand) {
            $base = basename((string)$cand);
            $base = trim((string)$base);
            if ($base === '') {
                continue;
            }
            $bare = preg_replace('/\.php$/i', '', $base);
            // fac_alt_caja_2.0 -> fac_alt_caja ; ayuda cuando el menu usa otro alias/version.
            $stem = preg_replace('/_[0-9]+(?:\.[0-9]+)?$/', '', $bare);
            if ($stem === '') {
                $stem = $bare;
            }
            $modPrefix = '';
            if (preg_match('/^([a-z]{3}_)/i', $stem, $mPref)) {
                $modPrefix = strtolower($mPref[1]);
            }
            $baseEsc = mysqli_real_escape_string($con, $base);
            $bareEsc = mysqli_real_escape_string($con, $bare);
            $stemEsc = mysqli_real_escape_string($con, $stem);
            $modPrefEsc = mysqli_real_escape_string($con, $modPrefix);
            foreach ($dbs as $db) {
                $sql = "SELECT `Pcs_Cod` FROM `{$db}`.`procesos` WHERE
                    `Pcs_Nom` = '{$baseEsc}'
                    OR `Pcs_Nom` = '{$bareEsc}'
                    OR `Pcs_Nom` = '{$bareEsc}.php'
                    OR `Pcs_Nom` LIKE '%/{$baseEsc}'
                    OR REPLACE(`Pcs_Nom`, '.php', '') LIKE '{$stemEsc}%'
                    OR REPLACE(`Pcs_Nom`, '.php', '') LIKE '%{$stemEsc}%'
                    ORDER BY CASE
                        WHEN `Pcs_Nom` = '{$baseEsc}' THEN 0
                        WHEN `Pcs_Nom` = '{$bareEsc}.php' THEN 1
                        WHEN `Pcs_Nom` = '{$bareEsc}' THEN 2
                        WHEN `Pcs_Nom` LIKE '%/{$baseEsc}' THEN 3
                        WHEN REPLACE(`Pcs_Nom`, '.php', '') LIKE '{$stemEsc}%' THEN 4
                        WHEN '{$modPrefEsc}' <> '' AND REPLACE(`Pcs_Nom`, '.php', '') LIKE '{$modPrefEsc}%' THEN 5
                        ELSE 6
                    END
                    LIMIT 1";
                $r = self::q($con, $sql);
                if ($r) {
                    $row = mysqli_fetch_assoc($r);
                    mysqli_free_result($r);
                    if (!empty($row['Pcs_Cod'])) {
                        $pcs = (int)$row['Pcs_Cod'];
                        if ($hasCfg && !self::isProcessEnabled($con, $emp, $pcs, $datDis)) {
                            continue;
                        }
                        return $pcs;
                    }
                }
            }
        }
        return 0;
    }
}
