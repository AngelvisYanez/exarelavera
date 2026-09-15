<?php
$Ses_Dat_Dis = isset($_SESSION['Ses_Dat_Dis']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']) : '';
$obBD_con1 = new Class_Log_Datos_index_button();
$obBD_conexion = new Class_Log_Conexion_index_button($Ses_Dat_Dis !== '' ? $Ses_Dat_Dis : null);
$empCod = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : null;
$empresaNombre = '';

if ($empCod) {
    // Si la conexión no tiene base seleccionada, intentamos conectarla con exa_master
    if (empty($obBD_conexion->BaseDatos)) {
        $obBD_conexion->BaseDatos = 'exa_master';
        $obBD_conexion->conectar();
    }
    
    // Consulta para obtener el nombre de la empresa
    $query = "SELECT Emp_Nom FROM exa_master.empresas WHERE Emp_Cod = " . intval($empCod) . " LIMIT 1";
    $result = @mysqli_query($obBD_conexion->conexion, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $empresaNombre = $row['Emp_Nom'];
    }
}

// Comprobación si la empresa es de capacitación / videos
$esCapacitacionVideos = false;
if (!empty($empresaNombre)) {
    if (stripos($empresaNombre, 'capacitacion') !== false && stripos($empresaNombre, 'video') !== false) {
        $esCapacitacionVideos = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Botones Index</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/font-awesome.min.css">
    <style>
        .custom-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 15px;
            border-radius: 10px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
            border: 1px solid #e2e8f0;
        }
        .custom-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        }
        .custom-btn i {
            margin-right: 8px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <div class="row g-2 justify-content-center">
            <?php if (isset($_SESSION['Ses_Per_Cod'])): ?>
                
                <div class="col-xl-3 col-sm-6 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                    <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="/auditoria/FRONT/aud_con_monitoreo_1.0.php">
                        <i class="fa fa-shield text-primary"></i> Auditoría
                    </a>
                </div>

                <?php if ($esCapacitacionVideos): ?>
                    <!-- Botón exclusivo para Capacitación Videos: Mapeo Interactivo Relavera -->
                    <div class="col-xl-3 col-sm-6 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                        <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="/mapeo/FRONT/map_alt_mapeo_interactivo.php">
                            <i class="fa fa-map-marker text-success"></i> Mapeo Relavera (Dev)
                        </a>
                    </div>
                    <div class="col-xl-3 col-sm-6 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                        <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="/auditoria/FRONT/aud_con_dashboard_comparativo_1.0.php">
                            <i class="fa fa-line-chart text-info"></i> Dashboard Auditoría
                        </a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
