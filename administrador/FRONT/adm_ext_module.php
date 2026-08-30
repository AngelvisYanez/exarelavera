<?php
session_start();
require_once "../../DATA/MysqlConexion.php";
require_once "../../DATA/MysqlDatos.php";
require_once "../../classes/DataAPI.php";
require_once "../../classes/ExternalModuleRunner.php";

$Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'] ?? 'servicios';
$Ses_Emp_Cod = $_SESSION['Ses_Emp_Cod'] ?? 1;
$Dir_Cod = (int)($_GET['Dir_Cod'] ?? 20);

$runner = new ExternalModuleRunner($Ses_Dat_Dis);
try {
    $module = $runner->getModuleInfo($Dir_Cod);
} catch (Exception $e) {
    $module = null;
}

$port = $module['Dir_Ext_Port'] ?? 5050;
$moduleName = $module['Dir_Nom'] ?? 'Modulo Externo';
$moduleDesc = $module['Dir_Des'] ?? '';
$moduleStatus = $module['Dir_Ext_Status'] ?? 'stopped';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($moduleName); ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <style>
        body { margin: 0; padding: 0; background: #f5f5f5; font-family: Arial, sans-serif; }
        .module-header {
            background: #fff;
            border-bottom: 2px solid #3c8dbc;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .module-header h3 { margin: 0; color: #333; }
        .module-frame {
            width: 100%;
            height: calc(100vh - 70px);
            border: none;
        }
        .btn-control { margin-left: 10px; }
    </style>
</head>
<body>
    <div class="module-header">
        <div>
            <h3><i class="fa fa-server"></i> <?php echo htmlspecialchars($moduleName); ?></h3>
            <small class="text-muted"><?php echo htmlspecialchars($moduleDesc); ?></small>
        </div>
        <div>
            <span class="label <?php echo $moduleStatus === 'running' ? 'label-success' : 'label-danger'; ?>">
                <i class="fa <?php echo $moduleStatus === 'running' ? 'fa-play-circle' : 'fa-stop-circle'; ?>"></i>
                <?php echo $moduleStatus === 'running' ? 'Ejecutando' : 'Detenido'; ?>
            </span>
            <button class="btn btn-sm btn-success btn-control" onclick="controlModule('start')" id="btn-start"
                <?php echo $moduleStatus === 'running' ? 'disabled' : ''; ?>>
                <i class="fa fa-play"></i> Iniciar
            </button>
            <button class="btn btn-sm btn-danger btn-control" onclick="controlModule('stop')" id="btn-stop"
                <?php echo $moduleStatus !== 'running' ? 'disabled' : ''; ?>>
                <i class="fa fa-stop"></i> Detener
            </button>
            <button class="btn btn-sm btn-info btn-control" onclick="refreshFrame()">
                <i class="fa fa-refresh"></i>
            </button>
        </div>
    </div>
    <iframe id="module-frame" class="module-frame" src="http://127.0.0.1:<?php echo $port; ?>"></iframe>
    
    <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
    <script>
        var AJAX_URL = "/administrador/FRONT/adm_gst_externos.php";
        var MOD_DIR_COD = <?php echo $Dir_Cod; ?>;
        
        function refreshFrame() {
            document.getElementById("module-frame").src = document.getElementById("module-frame").src;
        }
        
        function controlModule(action) {
            $.post(AJAX_URL, { action: action, Dir_Cod: MOD_DIR_COD }, function(response) {
                if (response.status) {
                    setTimeout(function() { location.reload(); }, 500);
                } else {
                    alert("Error: " + (response.error || "Desconocido"));
                }
            }, "json").fail(function() {
                alert("Error de conexion");
            });
        }
    </script>
</body>
</html>
