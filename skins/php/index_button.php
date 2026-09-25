<?php
/**
 * Panel de accesos rapidos del home (iframe inicial).
 * Sin Class_Log_* inexistentes: usa MysqlConexion si hay sesion/empresa.
 */
if (session_id() === '' && !headers_sent()) {
	@session_start();
}
@date_default_timezone_set('America/Guayaquil');

$empCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
$empresaNombre = '';

if ($empCod > 0) {
	try {
		require_once dirname(__FILE__) . '/../../DATA/MysqlConexion.php';
		$obBD = new MysqlConexion();
		$conn = $obBD->conexion;
		if ($conn) {
			$empCodSql = (int)$empCod;
			$result = @mysqli_query($conn, "SELECT Emp_Nom FROM exa_master.empresas WHERE Emp_Cod = {$empCodSql} LIMIT 1");
			if ($result && ($row = mysqli_fetch_assoc($result))) {
				$empresaNombre = isset($row['Emp_Nom']) ? (string)$row['Emp_Nom'] : '';
			}
			@$obBD->cerrar();
		}
	} catch (Exception $e) {
		$empresaNombre = '';
	}
}

$esCapacitacionVideos = false;
if ($empresaNombre !== '') {
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
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap.min.css">
	<link rel="stylesheet" href="../../framework/plugins/fonts/font-awesome/font-awesome-4.4.0/css/font-awesome.min.css">
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
			background: #fff;
			color: #64748b;
		}
		.custom-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0,0,0,0.1);
			text-decoration: none;
			color: #475569;
		}
		.custom-btn i { margin-right: 8px; font-size: 1.1rem; }
	</style>
</head>
<body>
	<div class="container-fluid" style="padding-top:12px;">
		<div class="row" style="display:flex; flex-wrap:wrap; justify-content:center;">
			<?php if (!empty($_SESSION['Ses_Per_Cod']) || !empty($_SESSION['Ses_Usu_Cod'])): ?>
				<div class="col-sm-4" style="margin-bottom:12px;">
					<a class="custom-btn" href="../../auditoria/FRONT/aud_con_monitoreo_1.0.php" target="contenido">
						<i class="fa fa-shield" style="color:#2563eb;"></i> Auditoría
					</a>
				</div>
				<?php if ($esCapacitacionVideos): ?>
					<div class="col-sm-4" style="margin-bottom:12px;">
						<a class="custom-btn" href="../../mapeo/FRONT/map_alt_mapeo_interactivo.php" target="contenido">
							<i class="fa fa-map-marker" style="color:#16a34a;"></i> Mapeo Relavera (Dev)
						</a>
					</div>
					<div class="col-sm-4" style="margin-bottom:12px;">
						<a class="custom-btn" href="../../auditoria/FRONT/aud_con_dashboard_comparativo_1.0.php" target="contenido">
							<i class="fa fa-line-chart" style="color:#0891b2;"></i> Dashboard Auditoría
						</a>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
