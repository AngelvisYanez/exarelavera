<?php
/**
 * Seeder / Aprovisionamiento de Datos de Prueba para Empresa 503 ("capacitacion videos").
 * 
 * Carga datos en auditoria.sesion y auditoria.logs para la Empresa 503:
 * - Sesiones activas en línea, inactivas por timeout y cerradas.
 * - Registro de IPs locales, navegadores modernos y tiempos de uso realistas.
 * - Logs distribuidos en: Hoy, Ayer, 1 Semana, 1 Mes y 3 Meses.
 * - Cobertura de eventos INSERT (2), UPDATE (3) y DELETE (4) con pares interpretados.
 * - Casos de fallback incorporados (valores nulos, UAs vacíos, etc.).
 *
 * @package auditoria.TEST
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = '127.0.0.1';
$user = 'root';
$pass = '';

$conAud = mysqli_connect($host, $user, $pass, 'auditoria');
if (!$conAud) {
    die("Error conectando a auditoria: " . mysqli_connect_error() . "\n");
}
mysqli_set_charset($conAud, 'utf8');

$EMP_COD = 503;
$SUC_COD = 157; // Sucursal Matriz de servicios
$USU_ADMIN = 2430; // VICTOR LEWIS CHIMARRO (Admin)
$USU_OPER1 = 3;    // Operador 1
$USU_OPER2 = 4;    // Operador 2

echo "=======================================================================\n";
echo "  APROVISIONANDO DATOS DE PRUEBA EN BD AUDITORIA PARA EMPRESA 503      \n";
echo "=======================================================================\n\n";

// 1. Aprovisionar Sesiones en auditoria.sesion
echo "[1/2] Aprovisionando sesiones de usuarios para Empresa 503...\n";

// Limpiar sesiones previas de prueba de la empresa 503
mysqli_query($conAud, "DELETE FROM sesion WHERE Emp_Cod = $EMP_COD");

$now = date('Y-m-d H:i:s');
$hace10Min = date('Y-m-d H:i:s', strtotime('-10 minutes'));
$hace20Min = date('Y-m-d H:i:s', strtotime('-20 minutes'));
$hace1Hora = date('Y-m-d H:i:s', strtotime('-1 hour'));
$hace2Horas = date('Y-m-d H:i:s', strtotime('-2 hours'));
$ayer = date('Y-m-d H:i:s', strtotime('-1 day'));
$hace3Dias = date('Y-m-d H:i:s', strtotime('-3 days'));
$hace7Dias = date('Y-m-d H:i:s', strtotime('-7 days'));
$hace15Dias = date('Y-m-d H:i:s', strtotime('-15 days'));
$hace30Dias = date('Y-m-d H:i:s', strtotime('-30 days'));

$sesiones = array(
    // 1. Sesión Activa En Línea (Admin)
    array(
        'usu' => $USU_ADMIN,
        'int' => $hace1Hora,
        'out' => null,
        'ip' => '192.168.1.45',
        'ubi' => 'Red Local (LAN / Intranet)',
        'nav' => 'Chrome 122 (Windows 10/11)',
        'ult' => $hace10Min,
        'min' => 50,
        'est' => 'A',
        'tok' => md5('tok_503_admin_online_' . time())
    ),
    // 2. Sesión Activa En Línea (Operador 1)
    array(
        'usu' => $USU_OPER1,
        'int' => $hace2Horas,
        'out' => null,
        'ip' => '127.0.0.1',
        'ubi' => 'Localhost (Servidor Interno)',
        'nav' => 'Firefox 120 (Linux)',
        'ult' => $hace10Min,
        'min' => 110,
        'est' => 'A',
        'tok' => md5('tok_503_oper1_online_' . time())
    ),
    // 3. Fallback: Sesión Inactiva por Timeout (>15 min desatendido)
    array(
        'usu' => $USU_OPER2,
        'int' => $hace1Hora,
        'out' => $hace20Min,
        'ip' => '192.168.1.88',
        'ubi' => 'Red Local (LAN / Intranet)',
        'nav' => 'Edge 121 (Windows 10/11)',
        'ult' => $hace20Min,
        'min' => 40,
        'est' => 'I', // Inactiva
        'tok' => md5('tok_503_timeout_' . time())
    ),
    // 4. Fallback: User-Agent y Navegador Desconocido
    array(
        'usu' => $USU_OPER1,
        'int' => $ayer,
        'out' => date('Y-m-d H:i:s', strtotime('-23 hours')),
        'ip' => '10.0.4.15',
        'ubi' => 'Red Local (LAN / Intranet)',
        'nav' => 'Navegador desconocido',
        'ult' => date('Y-m-d H:i:s', strtotime('-23 hours')),
        'min' => 60,
        'est' => 'C', // Cerrada normalmente
        'tok' => md5('tok_503_ayer_1_' . time())
    ),
    // 5. Sesión Forzada por Administrador
    array(
        'usu' => $USU_OPER2,
        'int' => $hace3Dias,
        'out' => date('Y-m-d H:i:s', strtotime('-3 days +45 minutes')),
        'ip' => '192.168.1.99',
        'ubi' => 'Red Local (LAN / Intranet)',
        'nav' => 'Safari 17 (macOS)',
        'ult' => date('Y-m-d H:i:s', strtotime('-3 days +45 minutes')),
        'min' => 45,
        'est' => 'C',
        'tok' => md5('tok_503_forzada_' . time())
    ),
    // 6. Sesión cerrada hace 7 días
    array(
        'usu' => $USU_ADMIN,
        'int' => $hace7Dias,
        'out' => date('Y-m-d H:i:s', strtotime('-7 days +90 minutes')),
        'ip' => '192.168.1.45',
        'ubi' => 'Red Local (LAN / Intranet)',
        'nav' => 'Chrome 122 (Windows 10/11)',
        'ult' => date('Y-m-d H:i:s', strtotime('-7 days +90 minutes')),
        'min' => 90,
        'est' => 'C',
        'tok' => md5('tok_503_sem1_' . time())
    ),
    // 7. Sesión de hace 15 días (Período anterior)
    array(
        'usu' => $USU_OPER1,
        'int' => $hace15Dias,
        'out' => date('Y-m-d H:i:s', strtotime('-15 days +75 minutes')),
        'ip' => '192.168.1.50',
        'ubi' => 'Red Local (LAN / Intranet)',
        'nav' => 'Chrome 120 (Windows 10/11)',
        'ult' => date('Y-m-d H:i:s', strtotime('-15 days +75 minutes')),
        'min' => 75,
        'est' => 'C',
        'tok' => md5('tok_503_mes1_' . time())
    ),
    // 8. Sesión de hace 30 días
    array(
        'usu' => $USU_ADMIN,
        'int' => $hace30Dias,
        'out' => date('Y-m-d H:i:s', strtotime('-30 days +120 minutes')),
        'ip' => '192.168.1.45',
        'ubi' => 'Red Local (LAN / Intranet)',
        'nav' => 'Chrome 119 (Windows 10/11)',
        'ult' => date('Y-m-d H:i:s', strtotime('-30 days +120 minutes')),
        'min' => 120,
        'est' => 'C',
        'tok' => md5('tok_503_mes2_' . time())
    )
);

$rMaxSes = mysqli_query($conAud, "SELECT COALESCE(MAX(Ses_Cod), 0) as max_id FROM sesion");
$fMaxSes = mysqli_fetch_assoc($rMaxSes);
$nextSesCod = (int)$fMaxSes['max_id'] + 1;

$insSesCount = 0;
foreach ($sesiones as $s) {
    $outVal = $s['out'] ? "'{$s['out']}'" : "NULL";
    $ultVal = $s['ult'] ? "'{$s['ult']}'" : "NULL";
    $sqlSes = "INSERT INTO sesion (Ses_Cod, Usu_Cod, Ses_Int, Ses_Out, Emp_Cod, Suc_Cod, Ses_Ip, Ses_Ubi, Ses_Nav, Ses_Ult_Act, Ses_Min_Uso, Ses_Est, Ses_Token)
               VALUES ($nextSesCod, {$s['usu']}, '{$s['int']}', $outVal, $EMP_COD, $SUC_COD, '{$s['ip']}', '{$s['ubi']}', '{$s['nav']}', $ultVal, {$s['min']}, '{$s['est']}', '{$s['tok']}')";
    if (mysqli_query($conAud, $sqlSes)) {
        $insSesCount++;
        $nextSesCod++;
    } else {
        echo "Error insertando sesion: " . mysqli_error($conAud) . "\n";
    }
}
echo "  -> Se insertaron $insSesCount sesiones de prueba para Empresa 503 (En Línea, Inactivas y Cerradas).\n\n";

// 2. Aprovisionar Logs en auditoria.logs
echo "[2/2] Aprovisionando logs de transacciones para Empresa 503...\n";

// Logs distribuidos multitemporales
$logsDemo = array(
    // --- HOY ---
    array(
        'fec' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
        'usu' => $USU_ADMIN,
        'pcs' => 8,
        'tab' => 1,
        'eve' => 3, // UPDATE
        'cam' => 'Usu_Est,Usu_Obs',
        'val' => '~A~,~Usuario de Prueba Activo~',
        'int' => '~I~,~Bloqueo preventivo por auditoria~'
    ),
    array(
        'fec' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
        'usu' => $USU_ADMIN,
        'pcs' => 11,
        'tab' => 1,
        'eve' => 3, // UPDATE
        'cam' => 'Usu_Pas',
        'val' => '~******~',
        'int' => '~******~'
    ),
    array(
        'fec' => date('Y-m-d H:i:s', strtotime('-45 minutes')),
        'usu' => $USU_OPER1,
        'pcs' => 12,
        'tab' => 2,
        'eve' => 2, // INSERT
        'cam' => 'Com_Num,Com_Val,Com_Est',
        'val' => '~001-001-000008888~,245.50,~A~',
        'int' => ''
    ),
    array(
        'fec' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'usu' => $USU_OPER2,
        'pcs' => 12,
        'tab' => 2,
        'eve' => 4, // DELETE
        'cam' => 'Com_Num,Com_Val',
        'val' => '~001-001-000008777~,45.00',
        'int' => ''
    ),

    // --- AYER ---
    array(
        'fec' => date('Y-m-d 10:30:00', strtotime('-1 day')),
        'usu' => $USU_ADMIN,
        'pcs' => 12,
        'tab' => 2,
        'eve' => 2, // INSERT
        'cam' => 'Com_Num,Com_Val,Com_Est',
        'val' => '~001-001-000007555~,850.00,~A~',
        'int' => ''
    ),
    array(
        'fec' => date('Y-m-d 11:15:00', strtotime('-1 day')),
        'usu' => $USU_OPER1,
        'pcs' => 8,
        'tab' => 1,
        'eve' => 3, // UPDATE
        'cam' => 'Usu_Per_Cod',
        'val' => '1130',
        'int' => '1131'
    ),
    array(
        'fec' => date('Y-m-d 16:45:00', strtotime('-1 day')),
        'usu' => $USU_OPER1,
        'pcs' => 12,
        'tab' => 2,
        'eve' => 2, // INSERT
        'cam' => 'Com_Num,Com_Val',
        'val' => '~001-001-000007556~,120.00',
        'int' => ''
    ),

    // --- HACE 3 DIAS ---
    array(
        'fec' => date('Y-m-d 09:20:00', strtotime('-3 days')),
        'usu' => $USU_ADMIN,
        'pcs' => 12,
        'tab' => 2,
        'eve' => 2, // INSERT
        'cam' => 'Com_Num,Com_Val',
        'val' => '~001-001-000006111~,340.00',
        'int' => ''
    ),
    array(
        'fec' => date('Y-m-d 14:10:00', strtotime('-3 days')),
        'usu' => $USU_OPER2,
        'pcs' => 12,
        'tab' => 2,
        'eve' => 3, // UPDATE
        'cam' => 'Com_Val',
        'val' => '340.00',
        'int' => '390.00'
    ),

    // --- HACE 1 SEMANA (Periodo A) ---
    array(
        'fec' => date('Y-m-d 11:00:00', strtotime('-7 days')),
        'usu' => $USU_ADMIN,
        'pcs' => 12,
        'tab' => 2,
        'eve' => 2, // INSERT
        'cam' => 'Com_Num,Com_Val',
        'val' => '~001-001-000005222~,150.00',
        'int' => ''
    ),
    array(
        'fec' => date('Y-m-d 15:30:00', strtotime('-7 days')),
        'usu' => $USU_OPER1,
        'pcs' => 12,
        'tab' => 2,
        'eve' => 4, // DELETE
        'cam' => 'Com_Num',
        'val' => '~001-001-000005220~',
        'int' => ''
    ),

    // --- HACE 15 A 20 DIAS (Periodo B para Comparativas) ---
    array(
        'fec' => date('Y-m-d 08:45:00', strtotime('-16 days')),
        'usu' => $USU_ADMIN,
        'pcs' => 12,
        'tab' => 2,
        'eve' => 2, // INSERT
        'cam' => 'Com_Num,Com_Val',
        'val' => '~001-001-000004111~,500.00',
        'int' => ''
    ),
    array(
        'fec' => date('Y-m-d 10:15:00', strtotime('-18 days')),
        'usu' => $USU_OPER1,
        'pcs' => 8,
        'tab' => 1,
        'eve' => 3, // UPDATE
        'cam' => 'Usu_Est',
        'val' => '~I~',
        'int' => '~A~'
    ),
    array(
        'fec' => date('Y-m-d 14:00:00', strtotime('-20 days')),
        'usu' => $USU_OPER2,
        'pcs' => 12,
        'tab' => 2,
        'eve' => 2, // INSERT
        'cam' => 'Com_Num,Com_Val',
        'val' => '~001-001-000003999~,780.00',
        'int' => ''
    ),

    // --- FALLBACK TEST CASES ---
    // Fallback 1: Log sin campos ni valores (registro vacio controlado)
    array(
        'fec' => date('Y-m-d 16:00:00', strtotime('-25 days')),
        'usu' => $USU_ADMIN,
        'pcs' => 10,
        'tab' => 1,
        'eve' => 1, // Fallido
        'cam' => '',
        'val' => '',
        'int' => ''
    ),
    // Fallback 2: Log con caracteres con tildes y caracteres especiales
    array(
        'fec' => date('Y-m-d 17:30:00', strtotime('-28 days')),
        'usu' => $USU_ADMIN,
        'pcs' => 12,
        'tab' => 2,
        'eve' => 3, // UPDATE
        'cam' => 'Obs_Des,Cli_Nom',
        'val' => '~Actualización de facturación electrónica y retención~,~Compañía Gómez & López S.A.~',
        'int' => '~Verificación técnica aprobada~,~Compañía Gómez & López S.A.~'
    )
);

$rMaxLog = mysqli_query($conAud, "SELECT COALESCE(MAX(Log_Cod), 0) as max_id FROM logs");
$fMaxLog = mysqli_fetch_assoc($rMaxLog);
$nextLogCod = (int)$fMaxLog['max_id'] + 1;

$insLogCount = 0;
foreach ($logsDemo as $l) {
    $camVal = addslashes($l['cam']);
    $valVal = addslashes($l['val']);
    $intVal = addslashes($l['int']);
    $sqlLog = "INSERT INTO logs (Log_Cod, Usu_Cod, Pcs_Cod, Tab_Cod, Log_Fec, Eve_Cod, Log_Cam, Log_Val, Log_Int, Emp_Cod, Suc_Cod)
               VALUES ($nextLogCod, {$l['usu']}, {$l['pcs']}, {$l['tab']}, '{$l['fec']}', {$l['eve']}, '{$camVal}', '{$valVal}', '{$intVal}', $EMP_COD, $SUC_COD)";
    if (mysqli_query($conAud, $sqlLog)) {
        $insLogCount++;
        $nextLogCod++;
    } else {
        echo "Error insertando log: " . mysqli_error($conAud) . "\n";
    }
}
echo "  -> Se insertaron $insLogCount logs de auditoria multitemporales para Empresa 503.\n\n";

// Resumen del aprovisionamiento
$rTotLogs = mysqli_query($conAud, "SELECT COUNT(*) as c FROM logs WHERE Emp_Cod = $EMP_COD");
$fTotLogs = mysqli_fetch_assoc($rTotLogs);
$rTotSes = mysqli_query($conAud, "SELECT COUNT(*) as c FROM sesion WHERE Emp_Cod = $EMP_COD");
$fTotSes = mysqli_fetch_assoc($rTotSes);

echo "=======================================================================\n";
echo "  RESULTADO FINAL DE APROVISIONAMIENTO PARA EMPRESA 503:              \n";
echo "  - Total Logs en auditoria.logs:       " . $fTotLogs['c'] . "\n";
echo "  - Total Sesiones en auditoria.sesion: " . $fTotSes['c'] . "\n";
echo "=======================================================================\n";
