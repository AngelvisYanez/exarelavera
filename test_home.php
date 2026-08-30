<?php
session_start();

$_SESSION['Ses_Usu_Cod'] = '1';
$_SESSION['Ses_Usu_Ced'] = '22600781';
$_SESSION['Ses_Usu_Tip'] = 'A';
$_SESSION['Ses_Usu_Est'] = 'A';
$_SESSION['Ses_Usu_Men'] = 'T';
$_SESSION['Ses_Per_Cod'] = '1';
$_SESSION['Ses_Prs_Cod'] = '1';
$_SESSION['Ses_Prs_Ape'] = 'TORRES';
$_SESSION['Ses_Prs_Nom'] = 'ADMIN';
$_SESSION['Ses_Emp_Cod'] = '620';
$_SESSION['Ses_Emp_Nom'] = 'TORRES CARRION';
$_SESSION['Ses_Emp_Cor'] = 'TORRES CARRION';
$_SESSION['Ses_Suc_Cod'] = '1';
$_SESSION['Ses_Suc_Nom'] = 'MATRIZ';
$_SESSION['Ses_Dat_Dis'] = 'ecoparkmining';
$_SESSION['Ses_Dat_Aut'] = 'auditoria';
$_SESSION['Ses_Dat_Stg'] = 'storage';
$_SESSION['Ses_Lis_Per'] = ['1', '2', '3', '4', '5'];
$_SESSION['Ses_Per_Des'] = ['ADMINISTRADOR'];
$_SESSION['Ses_Sys_Nom'] = 'EXA [Software Contable]';

ob_start();
try {
    include __DIR__ . '/administrador/FRONT/home.php';
    $output = ob_get_clean();
    echo json_encode([
        'status' => 'OK',
        'length' => strlen($output),
        'containsSidebar' => strpos($output, 'id="sidebar"') !== false,
        'containsNavList' => strpos($output, 'nav-list') !== false,
        'preview' => substr($output, 0, 500)
    ]);
} catch (\Throwable $e) {
    $err = ob_get_clean();
    echo json_encode([
        'status' => 'ERROR',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'output' => $err
    ]);
}
