<?php
$DirSep=DIRECTORY_SEPARATOR;
$APP_REAL_PATH=realpath(str_replace(basename( __FILE__ ),'',__FILE__));

$_GET['_search'] = 'false';
$_GET['nd'] = '1784223637823';
$_GET['rows'] = '50';
$_GET['page'] = '1';
$_GET['sidx'] = '';
$_GET['sord'] = 'asc';

// Mock session
session_start();
$_SESSION['Ses_Sys_Nom'] = 'EXA';
$_SESSION['Ses_Dat_Dis'] = 'exa';
$_SESSION['Ses_Niv_Usu'] = 1;
$_SESSION['Ses_Emp_Cod'] = 503; // Using capacitacion videos
$_SESSION['id_empresa'] = 503;
$_SESSION['Ses_Log_Cia'] = '';
$_SESSION['Ses_Usu_Cod'] = 22600781;
$_SESSION['usu_ced'] = '22600781';

// Overwrite seguridad.php so it doesn't die
file_put_contents('../LOGICA/seguridad.php.bak', file_get_contents('../LOGICA/seguridad.php'));
file_put_contents('../LOGICA/seguridad.php', '<?php /* mocked */ ?>');

ob_start();
include 'adm_param_inicial.php';
$output = ob_get_clean();

// Restore seguridad.php
file_put_contents('../LOGICA/seguridad.php', file_get_contents('../LOGICA/seguridad.php.bak'));
unlink('../LOGICA/seguridad.php.bak');

echo $output;
