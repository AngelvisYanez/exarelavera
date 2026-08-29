<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/DATA/MysqlConexion.php';
require_once __DIR__ . '/DATA/MysqlDatos.php';

$obMaster = new MysqlConexion();
$obDatos = new MysqlDatos();

echo "=== EMPRESAS LIKE TORRES ===\n";
$emp = $obDatos->getArrayConsultaSql("SELECT Emp_Cod, Emp_Nom, Dat_Dis FROM empresas WHERE Emp_Nom LIKE '%Torres%' OR Emp_Nom LIKE '%Carrion%'", $obMaster);
echo json_encode($emp, JSON_PRETTY_PRINT) . "\n";

echo "=== ALL EMPRESAS ===\n";
$allEmp = $obDatos->getArrayConsultaSql("SELECT Emp_Cod, Emp_Nom, Dat_Dis FROM empresas LIMIT 20", $obMaster);
echo json_encode($allEmp, JSON_PRETTY_PRINT) . "\n";

foreach ($emp as $e) {
    $bdd = $e['Dat_Dis'];
    echo "=== DB $bdd FOR EMP {$e['Emp_Nom']} ===\n";
    $obEmp = new MysqlConexion($bdd);
    $usersEmp = $obDatos->getArrayConsultaSql("SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Men, u.Usu_Est, p.Prs_Nom, p.Prs_Ape FROM usuarios u LEFT JOIN persona p ON u.Prs_Cod = p.Prs_Cod WHERE u.Usu_Ced IN ('22600781', '1676514')", $obEmp);
    echo "Users in $bdd:\n" . json_encode($usersEmp, JSON_PRETTY_PRINT) . "\n";
    
    foreach ($usersEmp as $ue) {
        $perfs = $obDatos->getArrayConsultaSql("SELECT up.Per_Cod, p.Per_Des FROM usuarperfi up LEFT JOIN perfiles p ON up.Per_Cod = p.Per_Cod WHERE up.Usu_Cod = " . (int)$ue['Usu_Cod'], $obEmp);
        echo "Profiles for user {$ue['Usu_Cod']} ({$ue['Usu_Ced']}):\n" . json_encode($perfs, JSON_PRETTY_PRINT) . "\n";
        
        $pids = array_map(function($p){return $p['Per_Cod'];}, $perfs);
        if (!empty($pids)) {
            $pidsStr = implode(',', $pids);
            $procs = $obDatos->getArrayConsultaSql("SELECT COUNT(*) as tot FROM perfiorgan WHERE Per_Cod IN ($pidsStr)", $obEmp);
            echo "Total processes linked to profiles ($pidsStr): " . json_encode($procs) . "\n";
        }
    }
}
