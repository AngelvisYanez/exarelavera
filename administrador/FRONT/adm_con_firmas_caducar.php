<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

/*Alias:	Firmas por Caducar
Descripción: Muestra las firmas digitales de todas las empresas que están por caducar
Fecha de actualización:	2025-01-XX
Desarrollador:	Sistema EXA
*/	
require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/logica.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Adm
 */
$obBD_conexion = new Class_Log_Conexion_Adm;

/**
 * objeto para consultas
 * @var Class_Log_Datos_Adm
 */
$obBD_con1 =  new Class_Log_Datos_Adm;

// Configurar zona horaria
date_default_timezone_set('America/Guayaquil');

// Obtener parámetros del formulario
$fecha_desde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : (isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-d'));
$fecha_hasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : (isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d', strtotime('+30 days')));
$estado_filtro = isset($_POST['estado_filtro']) ? $_POST['estado_filtro'] : (isset($_GET['estado_filtro']) ? $_GET['estado_filtro'] : 'A'); // A=Activas, I=Inactivas, T=Todas
$buscar_texto = isset($_POST['buscar_texto']) ? trim($_POST['buscar_texto']) : (isset($_GET['buscar_texto']) ? trim($_GET['buscar_texto']) : '');
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Variables para exportación
$exportar = isset($_POST['exportar']) || isset($_GET['exportar']);
$reporte_whatsapp = isset($_POST['whatsapp']) || isset($_GET['whatsapp']);

$fecha_actual = date('Y-m-d');

// Obtener todas las empresas activas con sus bases de datos
$sql_empresas = "SELECT e.Emp_Cod, e.Emp_Nom, e.Emp_Ruc, d.Dat_Dis 
                 FROM exa_master.empresas e
                 INNER JOIN exa_master.data d ON e.Emp_Cod = d.Emp_Cod
                 WHERE e.Emp_Est = 'A'
                 ORDER BY e.Emp_Nom";

// Lista para almacenar las firmas
$firmas_por_caducar = array();
$firmas_todas = array();

// Usar la conexión del sistema para exa_master
// Intentar conectar a exa_master directamente para obtener la lista de empresas
try {
    $pdo_master = new PDO(
        "mysql:host=" . $obBD_conexion->Servidor . ";dbname=" . $obBD_conexion->BaseDatos . ";charset=utf8mb4",
        $obBD_conexion->Usuario,
        $obBD_conexion->Clave,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    $stmt_empresas = $pdo_master->query($sql_empresas);
    $empresas = $stmt_empresas->fetchAll(PDO::FETCH_ASSOC);
    
    // Consultar teléfonos de sucursales desde exa_master
    $telefonos_empresas = array();
    try {
        // Obtener todos los códigos de empresas únicos
        $emp_cods = array();
        foreach ($empresas as $emp) {
            if (!empty($emp['Emp_Cod'])) {
                $emp_cod = intval($emp['Emp_Cod']);
                if ($emp_cod > 0) {
                    $emp_cods[$emp_cod] = $emp_cod; // Usar como clave para evitar duplicados
                }
            }
        }
        
        if (!empty($emp_cods)) {
            // Convertir a array indexado numéricamente
            $emp_cods_list = array_values($emp_cods);
            
            // Consultar en lotes si hay muchos códigos
            $batch_size = 1000;
            for ($i = 0; $i < count($emp_cods_list); $i += $batch_size) {
                $batch = array_slice($emp_cods_list, $i, $batch_size);
                $placeholders = implode(',', array_fill(0, count($batch), '?'));
                
                // Consulta mejorada para obtener teléfonos
                $sql_telefonos = "SELECT s.Emp_Cod, s.Suc_Te1 
                                  FROM exa_master.sucursal s
                                  WHERE s.Emp_Cod IN ($placeholders) 
                                  AND s.Suc_Est = 'A' 
                                  AND s.Suc_Te1 IS NOT NULL 
                                  AND s.Suc_Te1 != ''
                                  AND CHAR_LENGTH(TRIM(s.Suc_Te1)) > 0
                                  ORDER BY s.Emp_Cod, s.Suc_Cod";
                
                $stmt_telefonos = $pdo_master->prepare($sql_telefonos);
                $stmt_telefonos->execute($batch);
                $telefonos_data = $stmt_telefonos->fetchAll(PDO::FETCH_ASSOC);
                
                // Asignar teléfono a cada empresa
                foreach ($telefonos_data as $tel_row) {
                    $emp_cod = intval($tel_row['Emp_Cod']);
                    $suc_te1 = trim($tel_row['Suc_Te1']);
                    
                    // Asignar teléfono solo si no está ya asignado y el teléfono no está vacío
                    if (!isset($telefonos_empresas[$emp_cod]) && !empty($suc_te1) && strlen($suc_te1) > 5) {
                        $telefonos_empresas[$emp_cod] = $suc_te1;
                    }
                }
            }
        }
    } catch (PDOException $e) {
        // Si hay error, continuar sin teléfonos
        // error_log("Error obteniendo teléfonos desde exa_master: " . $e->getMessage());
    }
    
    // Agrupar empresas por base de datos para optimizar consultas
    $empresas_por_db = array();
    foreach ($empresas as $emp) {
        if (!empty($emp['Dat_Dis'])) {
            if (!isset($empresas_por_db[$emp['Dat_Dis']])) {
                $empresas_por_db[$emp['Dat_Dis']] = array();
            }
            $empresas_por_db[$emp['Dat_Dis']][] = $emp;
        }
    }
    
    // Procesar cada base de datos
    foreach ($empresas_por_db as $db_name => $empresas_db) {
        try {
            $pdo_dist = new PDO(
                "mysql:host=" . $obBD_conexion->Servidor . ";dbname=$db_name;charset=utf8mb4",
                $obBD_conexion->Usuario,
                $obBD_conexion->Clave,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5
                )
            );
            
            // Verificar si existe la tabla llave_elect
            $stmt_check = $pdo_dist->query("SHOW TABLES LIKE 'llave_elect'");
            if ($stmt_check->rowCount() == 0) {
                continue;
            }
            
            // Buscar teléfonos en base distribuida si no se encontraron en exa_master
            try {
                $stmt_suc = $pdo_dist->query("SHOW TABLES LIKE 'sucursal'");
                if ($stmt_suc->rowCount() > 0) {
                    foreach ($empresas_db as $emp) {
                        $emp_cod = intval($emp['Emp_Cod']);
                        // Solo buscar si no se encontró teléfono en exa_master
                        if (!isset($telefonos_empresas[$emp_cod])) {
                            $stmt_tel_dist = $pdo_dist->prepare("SELECT Suc_Te1 FROM sucursal WHERE Emp_Cod = :emp_cod AND Suc_Est = 'A' AND Suc_Te1 IS NOT NULL AND Suc_Te1 != '' AND CHAR_LENGTH(TRIM(Suc_Te1)) > 0 LIMIT 1");
                            $stmt_tel_dist->execute(array(':emp_cod' => $emp['Emp_Cod']));
                            $tel_dist = $stmt_tel_dist->fetch(PDO::FETCH_ASSOC);
                            if ($tel_dist && !empty($tel_dist['Suc_Te1'])) {
                                $telefonos_empresas[$emp_cod] = trim($tel_dist['Suc_Te1']);
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                // Continuar si no hay tabla sucursal en base distribuida
            }
            
            // Consultar firmas según filtros
            foreach ($empresas_db as $emp) {
                // Construir SQL según filtros
                $sql_firmas = "SELECT Lla_Cod, Lla_Rut, Lla_Cla, Lla_Cad, Lla_Est
                               FROM llave_elect
                               WHERE Emp_Cod = :emp_cod 
                               AND Lla_Cad IS NOT NULL
                               AND Lla_Cad != ''";
                
                $params = array(':emp_cod' => $emp['Emp_Cod']);
                
                // Filtro por estado
                if ($estado_filtro != 'T') {
                    $sql_firmas .= " AND Lla_Est = :estado";
                    $params[':estado'] = $estado_filtro;
                }
                
                // Filtro por rango de fechas
                if (!empty($fecha_desde) && !empty($fecha_hasta)) {
                    $sql_firmas .= " AND Lla_Cad BETWEEN :fecha_desde AND :fecha_hasta";
                    $params[':fecha_desde'] = $fecha_desde;
                    $params[':fecha_hasta'] = $fecha_hasta;
                }
                
                $sql_firmas .= " ORDER BY Lla_Cad ASC";
                
                $stmt_firmas = $pdo_dist->prepare($sql_firmas);
                $stmt_firmas->execute($params);
                
                $firmas = $stmt_firmas->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($firmas as $firma) {
                    // Calcular días restantes
                    $fecha_caducidad = new DateTime($firma['Lla_Cad']);
                    $fecha_actual_obj = new DateTime($fecha_actual);
                    
                    // Calcular diferencia de días
                    $diferencia = $fecha_actual_obj->diff($fecha_caducidad);
                    $dias_restantes = $diferencia->days;
                    
                    // Si ya caducó, marcar como negativo
                    if ($fecha_caducidad < $fecha_actual_obj) {
                        $dias_restantes = -$dias_restantes;
                    }
                    
                    // Obtener teléfono de la empresa (ya obtenido desde exa_master.sucursal)
                    // Asegurar que Emp_Cod sea numérico para la búsqueda
                    $emp_cod_lookup = intval($emp['Emp_Cod']);
                    $telefono = isset($telefonos_empresas[$emp_cod_lookup]) ? trim($telefonos_empresas[$emp_cod_lookup]) : '';
                    
                    $firma_data = array(
                        'Emp_Cod' => $emp['Emp_Cod'],
                        'Emp_Nom' => $emp['Emp_Nom'],
                        'Emp_Ruc' => $emp['Emp_Ruc'],
                        'Dat_Dis' => $db_name,
                        'Lla_Rut' => $firma['Lla_Rut'],
                        'Lla_Cla' => $firma['Lla_Cla'],
                        'Lla_Cad' => $firma['Lla_Cad'],
                        'Lla_Est' => $firma['Lla_Est'],
                        'Dias_Restantes' => $dias_restantes,
                        'Telefono' => $telefono
                    );
                    
                    // Aplicar filtro de búsqueda
                    if (empty($buscar_texto) || 
                        stripos($firma_data['Emp_Nom'], $buscar_texto) !== false ||
                        stripos($firma_data['Emp_Ruc'], $buscar_texto) !== false ||
                        stripos($firma_data['Lla_Rut'], $buscar_texto) !== false) {
                        $firmas_por_caducar[] = $firma_data;
                    }
                    
                    $firmas_todas[] = $firma_data;
                }
            }
            
            $pdo_dist = null;
        } catch (PDOException $e) {
            // Si hay error al conectar a la base, continuar con la siguiente
            continue;
        }
    }
    
    $pdo_master = null;
} catch (PDOException $e) {
    // Error al conectar a exa_master
    $error_conexion = "Error al conectar a la base de datos: " . $e->getMessage();
}

// Ordenar por fecha de caducidad (más próximas primero)
usort($firmas_por_caducar, function($a, $b) {
    return strtotime($a['Lla_Cad']) - strtotime($b['Lla_Cad']);
});

// Función para generar mensaje WhatsApp para notificación 5 días antes
function generarMensajeWhatsApp($firmas_5_dias) {
    $mensaje = "🚨 *NOTIFICACIÓN: FIRMAS DIGITALES POR CADUCAR*\n\n";
    $mensaje .= "Se ha detectado que las siguientes firmas digitales vencen en 5 días o menos:\n\n";
    
    foreach ($firmas_5_dias as $firma) {
        $fecha_formateada = date('d/m/Y', strtotime($firma['Lla_Cad']));
        $mensaje .= "• *" . $firma['Emp_Nom'] . "*\n";
        $mensaje .= "  RUC: " . $firma['Emp_Ruc'] . "\n";
        $mensaje .= "  Usuario: " . $firma['Lla_Rut'] . "\n";
        $mensaje .= "  Fecha de caducidad: " . $fecha_formateada . "\n";
        $mensaje .= "  Días restantes: " . ($firma['Dias_Restantes'] < 0 ? abs($firma['Dias_Restantes']) . " días vencidos" : $firma['Dias_Restantes'] . " días") . "\n\n";
    }
    
    $mensaje .= "Por favor, renueve las firmas digitales para evitar inconvenientes.\n";
    $mensaje .= "Sistema EXA - Facturación Electrónica";
    
    return urlencode($mensaje);
}

// Obtener firmas que vencen en 5 días o menos para WhatsApp
$firmas_5_dias = array_filter($firmas_por_caducar, function($f) {
    return $f['Dias_Restantes'] >= 0 && $f['Dias_Restantes'] <= 5;
});

// Si es exportación a Excel, solo devolver HTML de la tabla
if ($exportar) {
    header('Content-Type: application/json; charset=utf-8');
    
    $html_tabla = '';
    if (!empty($firmas_por_caducar)) {
        $html_tabla = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr style="background-color: #a02525; color: white;">
                    <th style="padding: 8px;">Empresa</th>
                    <th style="padding: 8px;">RUC</th>
                    <th style="padding: 8px;">Base de Datos</th>
                    <th style="padding: 8px;">Usuario/RUT</th>
                    <th style="padding: 8px;">Firma</th>
                    <th style="padding: 8px;">Contraseña</th>
                    <th style="padding: 8px;">Fecha de Caducidad</th>
                    <th style="padding: 8px;">Días Restantes</th>
                    <th style="padding: 8px;">Estado</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($firmas_por_caducar as $firma) {
            $estado_texto = ($firma['Lla_Est'] == 'A') ? 'Activa' : 'Inactiva';
            $dias_texto = ($firma['Dias_Restantes'] < 0) ? abs($firma['Dias_Restantes']) . ' días vencidos' : $firma['Dias_Restantes'] . ' días';
            
            $html_tabla .= '<tr>
                <td style="padding: 8px;">' . htmlspecialchars($firma['Emp_Nom'], ENT_QUOTES, 'UTF-8') . '</td>
                <td style="padding: 8px;">' . htmlspecialchars($firma['Emp_Ruc'], ENT_QUOTES, 'UTF-8') . '</td>
                <td style="padding: 8px;">' . htmlspecialchars($firma['Dat_Dis'], ENT_QUOTES, 'UTF-8') . '</td>
                <td style="padding: 8px;">' . htmlspecialchars($firma['Lla_Rut'], ENT_QUOTES, 'UTF-8') . '</td>
                <td style="padding: 8px;">' . htmlspecialchars($firma['Lla_Rut'], ENT_QUOTES, 'UTF-8') . '</td>
                <td style="padding: 8px;">' . htmlspecialchars($firma['Lla_Cla'], ENT_QUOTES, 'UTF-8') . '</td>
                <td style="padding: 8px;">' . date('d/m/Y', strtotime($firma['Lla_Cad'])) . '</td>
                <td style="padding: 8px;">' . $dias_texto . '</td>
                <td style="padding: 8px;">' . $estado_texto . '</td>
            </tr>';
        }
        
        $html_tabla .= '</tbody></table>';
    }
    
    echo json_encode(array('success' => true, 'html' => $html_tabla));
    exit;
}
?>
<HTML>
	<HEAD>		
		<TITLE>Firmas por Caducar</TITLE>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>

		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>
		<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
		
		<script>
		function exportarExcel() {
			var form = document.getElementById('formFiltros');
			var formData = new FormData(form);
			formData.append('exportar', '1');
			
			$.ajax({
				url: '',
				type: 'POST',
				data: formData,
				dataType: 'json',
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						var temp = $('<div>' + response.html + '</div>');
						$.downloadFile($.exportarExcelBlob(temp.html(), 'FirmasPorCaducar'), 'FirmasPorCaducar_' + $.getDate() + '.xls');
					} else {
						alert('Error al exportar');
					}
				},
				error: function() {
					alert('Error al exportar el reporte');
				}
			});
		}
		
		function enviarWhatsAppEmpresa(firma) {
			if (!firma.Telefono || firma.Telefono.trim() === '') {
				alert('No hay número de teléfono registrado para esta empresa.');
				return;
			}
			
			// Normalizar número de teléfono (eliminar espacios, guiones, paréntesis, etc.)
			var telefono_original = firma.Telefono.trim();
			var telefono = telefono_original.replace(/\s+/g, '').replace(/-/g, '').replace(/\(/g, '').replace(/\)/g, '').replace(/\./g, '').replace(/\+/g, '');
			
			// Remover cualquier carácter no numérico
			telefono = telefono.replace(/\D/g, '');
			
			// Validar que tenga al menos 9 dígitos (número mínimo de Ecuador)
			if (telefono.length < 9) {
				alert('El número de teléfono parece inválido: ' + telefono_original + '\nDebe tener al menos 9 dígitos.');
				return;
			}
			
			// Formatear para Ecuador (593)
			var telefono_formateado = '';
			
			// Si ya tiene el código de país 593, solo tomar los dígitos siguientes
			if (telefono.startsWith('593')) {
				telefono_formateado = telefono;
			} 
			// Si empieza con 0 (formato nacional), remover el 0 y agregar 593
			else if (telefono.startsWith('0')) {
				telefono_formateado = '593' + telefono.substring(1);
			}
			// Si es un número de 9 o 10 dígitos, agregar 593
			else if (telefono.length >= 9 && telefono.length <= 10) {
				telefono_formateado = '593' + telefono;
			}
			// Si ya tiene más de 10 dígitos, asumir que ya tiene código de país
			else {
				telefono_formateado = telefono;
			}
			
			// Asegurar que el número final tenga exactamente 12 dígitos (593 + 9 dígitos)
			// Ecuador: 593 + 9 dígitos = 12 dígitos totales
			if (telefono_formateado.length !== 12) {
				// Si tiene 13 dígitos y empieza con 0, remover el 0 extra
				if (telefono_formateado.length === 13 && telefono_formateado.startsWith('0593')) {
					telefono_formateado = telefono_formateado.substring(1);
				}
				
				// Validar nuevamente
				if (telefono_formateado.length !== 12) {
					alert('El número de teléfono formateado parece inválido.\n\n' +
						'Original: ' + telefono_original + '\n' +
						'Formateado: ' + telefono_formateado + '\n' +
						'Longitud: ' + telefono_formateado.length + ' dígitos\n\n' +
						'El formato correcto para Ecuador es: 593 seguido de 9 dígitos (total 12 dígitos)\n' +
						'Ejemplo: 593989385862');
					return;
				}
			}
			
			// Mostrar confirmación con el número
			var confirmar = confirm('¿Enviar WhatsApp a la empresa?\n\n' +
				'Empresa: ' + firma.Emp_Nom + '\n' +
				'Teléfono original: ' + telefono_original + '\n' +
				'Teléfono formateado: +' + telefono_formateado + ' (' + telefono_formateado.length + ' dígitos)\n\n' +
				'¿Deseas continuar?');
			
			if (!confirmar) {
				return;
			}
			
			// Calcular días restantes
			var dias_texto = '';
			if (firma.Dias_Restantes < 0) {
				dias_texto = 'Tu firma electrónica ha caducado hace ' + Math.abs(firma.Dias_Restantes) + ' días';
			} else if (firma.Dias_Restantes === 0) {
				dias_texto = 'Tu firma electrónica ha caducado hoy';
			} else {
				dias_texto = 'Tu firma electrónica caduca en ' + firma.Dias_Restantes + ' días';
			}
			
			// Construir mensaje según formato solicitado
			var mensaje = 'Estimado/a cliente\n\n';
			mensaje += 'Empresa: ' + firma.Emp_Nom + '\n';
			mensaje += 'RUC: ' + firma.Emp_Ruc + '\n\n';
			mensaje += dias_texto + '\n';
			mensaje += 'Solicita tu firma electrónica con nosotros.\n\n';
			mensaje += 'COSTOS\n';
			mensaje += '• 1 año: $25 + IVA\n';
			mensaje += '• 2 años: $35 + IVA\n';
			mensaje += '• 3 años: $45 + IVA\n';
			mensaje += '• 4 años: $55 + IVA\n';
			mensaje += '• 5 años: $69 + IVA\n\n';
			mensaje += 'Nota: La generación de la firma electrónica se realiza previo depósito.';
			
			// Abrir WhatsApp Web con el número y mensaje
			var whatsappUrl = 'https://api.whatsapp.com/send?phone=' + telefono_formateado + '&text=' + encodeURIComponent(mensaje);
			window.open(whatsappUrl, '_blank');
		}
		
		// Esperar a que jQuery esté disponible
		(function() {
			function initScripts() {
				if (typeof jQuery === 'undefined') {
					setTimeout(initScripts, 100);
					return;
				}
				
				public $ = jQuery;
				
				// Ocultar loader/spinner cuando la página termine de cargar
				$(document).ready(function() {
					// Ocultar cualquier loader existente
					setTimeout(function() {
						$('#loader').hide();
						$('.loader').hide();
						$('[id*="loader"]').hide();
						if (typeof $.loader !== 'undefined') {
							$.loader('hide');
						}
					}, 500);
					
					// Verificar si fixedHeaderTable está disponible
					if (typeof $.fn.fixedHeaderTable === 'undefined') {
						console.warn('fixedHeaderTable plugin no disponible - ignorando');
					}
				});
				
			}
			initScripts();
		})();
		
		// Ocultar loader inmediatamente si existe
		if (document.getElementById('loader')) {
			document.getElementById('loader').style.display = 'none';
		}
		</script>
					
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta charset="UTF-8">		
	<style>
		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			color: #212529;
		}
		.LetraNegra {
			color: #212529 !important;
			font-weight: 500;
			font-size: 13px;
		}
		.Etiqueta1 {
			color: #495057 !important;
			font-weight: 600;
			font-size: 13px;
		}
		table td {
			color: #212529;
		}
		th {
			color: #161616 !important;
			font-weight: 700 !important;
		}
		input, select {
			color: #212529 !important;
			font-weight: 500;
		}
		.panel-main {
			margin-bottom: 20px;
		}
		.stats-container {
			clear: both;
			overflow: hidden;
		}
		.stats-item {
			display: inline-block;
			vertical-align: middle;
		}
		/* Ocultar loader/spinner */
		#loader, .loader, [id*="loader"] {
			display: none !important;
		}
	</style>
	</HEAD>
<BODY>

    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Firmas Digitales por Caducar</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="lista" class="row">
                <div class="col-md-12">
                    <form id="formFiltros" name="formFiltros" method="post" action="" class="form-horizontal normal">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">B&uacute;squeda de Firmas Digitales</legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Fechas:</label>
                                <div class="col-sm-3">
                                    <label class="control-label label-xs" style="font-weight: normal;">Desde:</label>
                                    <input type="date" name="fecha_desde" id="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde, ENT_QUOTES, 'UTF-8'); ?>" class="form-control input-xs" style="width: 100%;" />
                                </div>
                                <div class="col-sm-3">
                                    <label class="control-label label-xs" style="font-weight: normal;">Hasta:</label>
                                    <input type="date" name="fecha_hasta" id="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta, ENT_QUOTES, 'UTF-8'); ?>" class="form-control input-xs" style="width: 100%;" />
                                </div>
                                <div class="col-sm-4 radioset">
                                    <label class="control-label label-xs" style="font-weight: normal; margin-right: 10px;">Estado:</label>
                                    <input id="rad_est1" name="estado_filtro" type="radio" value="A" <?php echo ($estado_filtro == 'A') ? 'checked' : ''; ?> /><label for="rad_est1">Activas</label>
                                    <input id="rad_est2" name="estado_filtro" type="radio" value="I" <?php echo ($estado_filtro == 'I') ? 'checked' : ''; ?> /><label for="rad_est2">Inactivas</label>
                                    <input id="rad_est3" name="estado_filtro" type="radio" value="T" <?php echo ($estado_filtro == 'T') ? 'checked' : ''; ?> /><label for="rad_est3">Todas</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                <div class="col-sm-5">
                                    <div class="input-group">
                                        <input type="text" id="buscar_texto" name="buscar_texto" onkeydown="if (event.keyCode === 13) this.form.submit()" class="form-control input-xs" placeholder="Ingrese Empresa, RUC o Usuario a buscar..." value="<?php echo htmlspecialchars($buscar_texto, ENT_QUOTES, 'UTF-8'); ?>" autofocus>
                                        <input type="text" style="display:none" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="submit" title="Buscar Firmas"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
		
                    <?php if (isset($error_conexion)): ?>
                        <div style="color: #ea0606; padding: 12px; background-color: #ffebee; border-left: 4px solid #ea0606; border-radius: 4px; margin-bottom: 10px; font-weight: 600; font-size: 13px;">
                            <?php echo htmlspecialchars($error_conexion, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Calcular estadísticas
                    $total_firmas = count($firmas_por_caducar);
                    $firmas_caducadas = count(array_filter($firmas_por_caducar, function($f) { return $f['Dias_Restantes'] < 0; }));
                    $firmas_criticas = count(array_filter($firmas_por_caducar, function($f) { return $f['Dias_Restantes'] >= 0 && $f['Dias_Restantes'] <= 7; }));
                    $firmas_atencion = count(array_filter($firmas_por_caducar, function($f) { return $f['Dias_Restantes'] > 7 && $f['Dias_Restantes'] <= 15; }));
                    $firmas_proximas = count(array_filter($firmas_por_caducar, function($f) { return $f['Dias_Restantes'] > 15; }));
                    ?>
                    
                    <?php if (count($firmas_5_dias) > 0): ?>
                    <div style="background-color: #FFF3CD; border: 1px solid #FFC107; padding: 10px 15px; margin-bottom: 12px; border-radius: 4px; border-left: 4px solid #FF9800;">
                        <span style="color: #856404; font-weight: 700; font-size: 13px;">
                            ▲ <?php echo count($firmas_5_dias); ?> firma(s) vencen en 5 días o menos
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($firmas_por_caducar)): ?>
                    <div class="stats-container" style="background: linear-gradient(to bottom, #F8F9FA 0%, #FFFFFF 100%); border: 1px solid #0797D8; padding: 12px 18px; margin-bottom: 12px; border-radius: 4px; box-shadow: 0 2px 4px rgba(7, 151, 216, 0.1);">
                        <div class="stats-item" style="display: inline-block; margin-right: 30px; font-weight: 700; font-size: 13px;">
                            <span class="label" style="color: #161616; margin-right: 8px; font-weight: 700; font-size: 14px;">Total:</span>
                            <span style="color: #0797D8; font-size: 18px; font-weight: 800;"><?php echo $total_firmas; ?></span>
                        </div>
                        <div class="stats-item" style="display: inline-block; margin-right: 30px; font-weight: 700; font-size: 13px;">
                            <span class="label" style="color: #161616; margin-right: 8px; font-weight: 700; font-size: 14px;">Caducadas:</span>
                            <span style="color: #DC3545; font-size: 18px; font-weight: 800;"><?php echo $firmas_caducadas; ?></span>
                        </div>
                        <div class="stats-item" style="display: inline-block; margin-right: 30px; font-weight: 700; font-size: 13px;">
                            <span class="label" style="color: #161616; margin-right: 8px; font-weight: 700; font-size: 14px;">Críticas:</span>
                            <span style="color: #FF9800; font-size: 18px; font-weight: 800;"><?php echo $firmas_criticas; ?></span>
                        </div>
                        <div class="stats-item" style="display: inline-block; margin-right: 30px; font-weight: 700; font-size: 13px;">
                            <span class="label" style="color: #161616; margin-right: 8px; font-weight: 700; font-size: 14px;">Atención:</span>
                            <span style="color: #17A2B8; font-size: 18px; font-weight: 800;"><?php echo $firmas_atencion; ?></span>
                        </div>
                        <div class="stats-item" style="display: inline-block; margin-right: 0; font-weight: 700; font-size: 13px;">
                            <span class="label" style="color: #161616; margin-right: 8px; font-weight: 700; font-size: 14px;">Próximas:</span>
                            <span style="color: #28A745; font-size: 18px; font-weight: 800;"><?php echo $firmas_proximas; ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div style="min-height:300px;">
                        <?php if (empty($firmas_por_caducar)): ?>
                            <div style="padding: 20px; text-align: center; color: #212529; font-size: 14px; font-weight: 600;">
                                <strong>No se encontraron firmas digitales con los filtros seleccionados.</strong>
                            </div>
                        <?php else: ?>
                        <div style="max-height: 295px; overflow-y: auto;">
                        <table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 0; padding: 0; background-color: #fff;">
                            <thead>
                                <tr style="background: linear-gradient(to bottom, #E3F2FD 0%, #BBDEFB 100%); position: sticky; top: 0; z-index: 10;">
                                    <th style="padding: 12px 10px; text-align: left; font-weight: 700; border: 1px solid #90CAF9; color: #161616; font-size: 13px;" class="Etiqueta1">Empresa</th>
                                    <th style="padding: 12px 10px; text-align: left; font-weight: 700; border: 1px solid #90CAF9; color: #161616; font-size: 13px;" class="Etiqueta1">RUC</th>
                                    <th style="padding: 12px 10px; text-align: left; font-weight: 700; border: 1px solid #90CAF9; color: #161616; font-size: 13px;" class="Etiqueta1">Base de Datos</th>
                                    <th style="padding: 12px 10px; text-align: left; font-weight: 700; border: 1px solid #90CAF9; color: #161616; font-size: 13px;" class="Etiqueta1">Usuario/RUT</th>
                                    <th style="padding: 12px 10px; text-align: center; font-weight: 700; border: 1px solid #90CAF9; color: #161616; font-size: 13px;" class="Etiqueta1">Firma</th>
                                    <th style="padding: 12px 10px; text-align: left; font-weight: 700; border: 1px solid #90CAF9; color: #161616; font-size: 13px;" class="Etiqueta1">Contraseña</th>
                                    <th style="padding: 12px 10px; text-align: left; font-weight: 700; border: 1px solid #90CAF9; color: #161616; font-size: 13px;" class="Etiqueta1">Fecha de Caducidad</th>
                                    <th style="padding: 12px 10px; text-align: center; font-weight: 700; border: 1px solid #90CAF9; color: #161616; font-size: 13px;" class="Etiqueta1">Días Restantes</th>
                                    <th style="padding: 12px 10px; text-align: center; font-weight: 700; border: 1px solid #90CAF9; color: #161616; font-size: 13px;" class="Etiqueta1">Estado</th>
                                    <th style="padding: 12px 10px; text-align: center; font-weight: 700; border: 1px solid #90CAF9; color: #161616; font-size: 13px;" class="Etiqueta1">WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($firmas_por_caducar as $firma): ?>
                                    <?php 
                                    // Determinar el color según los días restantes (colores EXA)
                                    $color_fila = '';
                                    $estado = '';
                                    if ($firma['Dias_Restantes'] < 0) {
                                        $color_fila = 'background-color: #ffffff; border-left: 4px solid #ea0606;'; // Sin fondo rojo - ya caducó
                                        $estado = 'CADUCADA';
                                    } elseif ($firma['Dias_Restantes'] <= 5) {
                                        $color_fila = 'background-color: #fff3e0; border-left: 4px solid #fbcf33;'; // Amarillo/naranja EXA - vence en 5 días
                                        $estado = 'CRÍTICO (5 días)';
                                    } elseif ($firma['Dias_Restantes'] <= 7) {
                                        $color_fila = 'background-color: #fff8e1; border-left: 4px solid #fbcf33;'; // Amarillo claro EXA - vence pronto
                                        $estado = 'CRÍTICO';
                                    } elseif ($firma['Dias_Restantes'] <= 15) {
                                        $color_fila = 'background-color: #e3f2fd; border-left: 4px solid #17c1e8;'; // Azul claro EXA - atención
                                        $estado = 'ATENCIÓN';
                                    } else {
                                        $color_fila = 'background-color: #e8f5e9; border-left: 4px solid #82d616;'; // Verde claro EXA - todavía tiene tiempo
                                        $estado = 'PRÓXIMA';
                                    }
                                    
                                    $dias_display = '';
                                    if ($firma['Dias_Restantes'] < 0) {
                                        $dias_display = '<span style="color: #ea0606; font-weight: 700; font-size: 13px;">' . abs($firma['Dias_Restantes']) . ' días vencidos</span>';
                                    } else {
                                        $dias_display = '<span style="color: #212529; font-weight: 700; font-size: 13px;">' . $firma['Dias_Restantes'] . ' días</span>';
                                    }
                                    
                                    $estado_texto = ($firma['Lla_Est'] == 'A') ? '<span style="color: #82d616; font-weight: 700; font-size: 13px;">● Activa</span>' : '<span style="color: #ea0606; font-weight: 700; font-size: 13px;">● Inactiva</span>';
                                    
                                    // Preparar datos para WhatsApp
                                    $json_flags = defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 256;
                                    $firma_whatsapp = json_encode(array(
                                        'Emp_Nom' => $firma['Emp_Nom'],
                                        'Emp_Ruc' => $firma['Emp_Ruc'],
                                        'Lla_Cad' => $firma['Lla_Cad'],
                                        'Dias_Restantes' => $firma['Dias_Restantes'],
                                        'Telefono' => isset($firma['Telefono']) ? $firma['Telefono'] : ''
                                    ), $json_flags);
                                    ?>
                                    <tr style="<?php echo $color_fila; ?>; border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 8px; border: 1px solid #dee2e6; color: #212529; font-weight: 500; font-size: 13px; margin: 0;" class="LetraNegra"><?php echo htmlspecialchars($firma['Emp_Nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td style="padding: 8px; border: 1px solid #dee2e6; color: #212529; font-weight: 500; font-size: 13px; margin: 0;" class="LetraNegra"><?php echo htmlspecialchars($firma['Emp_Ruc'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td style="padding: 8px; border: 1px solid #dee2e6; color: #212529; font-weight: 500; font-size: 13px; margin: 0;" class="LetraNegra"><?php echo htmlspecialchars($firma['Dat_Dis'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td style="padding: 8px; border: 1px solid #dee2e6; color: #212529; font-weight: 500; font-size: 13px; margin: 0;" class="LetraNegra"><?php echo htmlspecialchars($firma['Lla_Rut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td style="padding: 8px; text-align: center; border: 1px solid #dee2e6; margin: 0;">
                                            <a href="download_signature.php?emp_cod=<?php echo $firma['Emp_Cod']; ?>&file=<?php echo urlencode($firma['Lla_Rut']); ?>" class="btn btn-info btn-xs" title="Descargar Firma" target="_blank">
                                                <span class="glyphicon glyphicon-download-alt"></span>
                                            </a>
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #dee2e6; font-family: \'Courier New\', monospace; color: #212529; font-weight: 600; font-size: 13px; margin: 0;" class="LetraNegra"><?php echo htmlspecialchars($firma['Lla_Cla'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td style="padding: 8px; border: 1px solid #dee2e6; color: #212529; font-weight: 500; font-size: 13px; margin: 0;" class="LetraNegra"><?php echo date('d/m/Y', strtotime($firma['Lla_Cad'])); ?></td>
                                        <td style="padding: 8px; text-align: center; font-weight: 700; border: 1px solid #dee2e6; color: #212529; font-size: 13px; margin: 0;" class="LetraNegra">
                                            <?php echo $dias_display; ?>
                                            <br><small style="font-size: 11px; color: #495057; font-weight: 600; margin-top: 2px; display: block;"><?php echo htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?></small>
                                        </td>
                                        <td style="padding: 8px; text-align: center; border: 1px solid #dee2e6; color: #212529; font-size: 13px; font-weight: 600; margin: 0;" class="LetraNegra">
                                            <?php echo $estado_texto; ?>
                                        </td>
                                        <td style="padding: 8px; text-align: center; border: 1px solid #dee2e6; font-size: 13px; margin: 0;">
                                            <?php if (!empty($firma['Telefono'])): ?>
                                            <button type="button" onclick="enviarWhatsAppEmpresa(<?php echo htmlspecialchars($firma_whatsapp, ENT_QUOTES, 'UTF-8'); ?>)" class="btn btn-sm" title="Enviar WhatsApp a <?php echo htmlspecialchars($firma['Emp_Nom'], ENT_QUOTES, 'UTF-8'); ?> - Tel: <?php echo htmlspecialchars($firma['Telefono'], ENT_QUOTES, 'UTF-8'); ?>" style="background-color: #25D366; border: 1px solid #1ebe57; color: white; padding: 5px 10px; border-radius: 4px; cursor: pointer; min-width: 35px;">
                                                <span style="font-size: 18px;">📱</span>
                                            </button>
                                            <br><small style="color: #495057; font-size: 10px; margin-top: 3px; display: block;"><?php echo htmlspecialchars($firma['Telefono'], ENT_QUOTES, 'UTF-8'); ?></small>
                                            <?php else: ?>
                                            <span style="color: #999; font-size: 11px;">Sin teléfono</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="padding-top: 10px; padding-bottom: 0px;">
                        <button type="button" onclick="exportarExcel()" class="btn btn-primary btn-sm start" title="Exportar registros"><i class="glyphicon glyphicon-download-alt"></i> <span>Exportar</span></button>
                    </div>
                    <?php if (!empty($firmas_por_caducar)): ?>
                    <div style="padding: 15px; font-size: 13px; background-color: #f8f9fa; border-radius: 5px; margin-top: 15px; border: 1px solid #dee2e6;" class="LetraNegra">
                        <strong style="color: #a02525; font-size: 15px; font-weight: 700;">Leyenda de Estados:</strong><br><br>
                        <div style="display: inline-block; margin-right: 25px; margin-bottom: 8px; color: #212529; font-weight: 600;">
                            <span style="background-color: #fff3e0; border-left: 4px solid #fbcf33; padding: 6px 12px; margin-right: 8px; display: inline-block; width: 22px; border-radius: 2px;">■</span> 
                            <strong style="color: #212529; font-weight: 700;">Crítico</strong> (vence en 5 días o menos)
                        </div>
                        <div style="display: inline-block; margin-right: 25px; margin-bottom: 8px; color: #212529; font-weight: 600;">
                            <span style="background-color: #fff8e1; border-left: 4px solid #fbcf33; padding: 6px 12px; margin-right: 8px; display: inline-block; width: 22px; border-radius: 2px;">■</span> 
                            <strong style="color: #212529; font-weight: 700;">Crítico</strong> (vence en 7 días o menos)
                        </div>
                        <div style="display: inline-block; margin-right: 25px; margin-bottom: 8px; color: #212529; font-weight: 600;">
                            <span style="background-color: #e3f2fd; border-left: 4px solid #17c1e8; padding: 6px 12px; margin-right: 8px; display: inline-block; width: 22px; border-radius: 2px;">■</span> 
                            <strong style="color: #212529; font-weight: 700;">Atención</strong> (vence en 15 días o menos)
                        </div>
                        <div style="display: inline-block; margin-right: 25px; margin-bottom: 8px; color: #212529; font-weight: 600;">
                            <span style="background-color: #e8f5e9; border-left: 4px solid #82d616; padding: 6px 12px; margin-right: 8px; display: inline-block; width: 22px; border-radius: 2px;">■</span> 
                            <strong style="color: #212529; font-weight: 700;">Próxima</strong> (vence en más de 15 días)
                        </div>
                        <div style="display: inline-block; margin-bottom: 8px; color: #212529; font-weight: 600;">
                            <span style="background-color: #ffffff; border-left: 4px solid #ea0606; padding: 6px 12px; margin-right: 8px; display: inline-block; width: 22px; border-radius: 2px;">■</span> 
                            <strong style="color: #212529; font-weight: 700;">Caducada</strong>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>
<?Php
/* Cerrado de las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>
