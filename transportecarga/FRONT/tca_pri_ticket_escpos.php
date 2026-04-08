<?php
/**
 * Genera ticket usando la plantilla HTML tca_pri_ticket.html
 * 
 * @param array $datos Array con los datos del ticket
 * @return string HTML del ticket
 */
function generarTicketESCPOS($datos) {
    // Construir tabla de detalles HTML
    $detalle_html = '';
    if (isset($datos['detalles']) && is_array($datos['detalles'])) {
        foreach ($datos['detalles'] as $det) {
            $detalle_html .= '<tr>';
            $detalle_html .= '<td>' . htmlspecialchars(isset($det['Pro_Des']) ? $det['Pro_Des'] : '') . '</td>';
            $detalle_html .= '<td>' . '' . '</td>'; // Detalle vacío
            $detalle_html .= '<td style="text-align: right;">' . number_format(isset($det['Dtk_Can']) ? $det['Dtk_Can'] : 0, 4) . '</td>';
            $detalle_html .= '<td style="text-align: right;">' . number_format(isset($det['Dtk_Pru']) ? $det['Dtk_Pru'] : 0, 4) . '</td>';
            $detalle_html .= '<td style="text-align: right;">' . number_format(isset($det['Dtk_Tot']) ? $det['Dtk_Tot'] : 0, 4) . '</td>';
            $detalle_html .= '</tr>';
        }
    }
    
    // Preparar array de reemplazo para la plantilla
    $tabla = array(
        '{Tck_Num}' => isset($datos['Tck_Num']) ? $datos['Tck_Num'] : '',
        '{Tck_Fec}' => isset($datos['Tck_Fec']) ? $datos['Tck_Fec'] : '',
        '{Emp_Nom}' => isset($datos['Emp_Nom']) ? $datos['Emp_Nom'] : '',
        '{cliente_nombre}' => isset($datos['cliente_nombre']) ? htmlspecialchars($datos['cliente_nombre']) : '',
        '{Prs_Ced}' => isset($datos['Prs_Ced']) ? $datos['Prs_Ced'] : '',
        '{Prs_Dir}' => isset($datos['Prs_Dir']) ? htmlspecialchars($datos['Prs_Dir']) : '',
        '{Prs_Cor}' => isset($datos['Prs_Cor']) ? htmlspecialchars($datos['Prs_Cor']) : '',
        '{Veh_Pla}' => isset($datos['Veh_Pla']) ? $datos['Veh_Pla'] : '',
        '{Veh_Cap}' => isset($datos['Veh_Cap']) ? $datos['Veh_Cap'] : '',
        '{Veh_Tip}' => isset($datos['Veh_Tip']) ? htmlspecialchars($datos['Veh_Tip']) : '',
        '{Tck_Val}' => isset($datos['Tck_Val']) ? number_format($datos['Tck_Val'], 4) : '0.0000',
        '{Tck_IvA}' => isset($datos['Tck_IvA']) ? number_format($datos['Tck_IvA'], 4) : '0.0000',
        '{Tck_Tot}' => isset($datos['Tck_Tot']) ? number_format($datos['Tck_Tot'], 4) : '0.0000',
        '{detalle_ticket}' => $detalle_html
    );
    
    // Verificar si existe la función reporteHtml (de almacenados_standar.php)
    if (!function_exists('reporteHtml')) {
        // Si no existe, cargar el archivo que la contiene
        $standar_path = __DIR__ . '/../../Librerias/procedimientos/almacenados_standar.php';
        if (file_exists($standar_path)) {
            require_once($standar_path);
        }
    }
    
    // Generar HTML usando reporteHtml con la plantilla tca_pri_ticket.html
    $template_path = __DIR__ . '/tca_pri_ticket.html';
    if (function_exists('reporteHtml') && file_exists($template_path)) {
        return reporteHtml($tabla, $template_path);
    } else {
        // Fallback: generar HTML básico si no existe la función o el template
        return generarTicketTextoPlano($datos);
    }
}

/**
 * Genera múltiples copias del ticket usando la plantilla HTML
 * 
 * @param array $datos Array con los datos del ticket
 * @param int $copias Número de copias (default: 3)
 * @return string HTML de todas las copias
 */
function generarTicketESCPOSMultiples($datos, $copias = 3) {
    $html_ticket = generarTicketESCPOS($datos);
    
    // Separador entre copias
    $separador = '<div style="border-top: 2px dashed #000; margin: 20px 0; padding: 10px 0; text-align: center;">------------------------ CORTE AQUI ------------------------</div>';
    
    $html = '';
    for ($i = 0; $i < $copias; $i++) {
        $html .= $html_ticket;
        if ($i < $copias - 1) {
            $html .= $separador;
        }
    }
    
    return $html;
}

/**
 * Genera una versión de texto plano del ticket (sin comandos ESC/POS) para previsualización
 * 
 * @param array $datos Array con los datos del ticket
 * @return string Texto plano del ticket
 */
function generarTicketTextoPlano($datos) {
    $texto = '';
    $ancho = 48;
    
    // Función auxiliar para centrar texto
    $centrar = function($texto, $ancho) {
        $len = strlen($texto);
        $padding = floor(($ancho - $len) / 2);
        return str_repeat(' ', max(0, $padding)) . $texto;
    };
    
    // Función auxiliar para línea separadora
    $separador = function($ancho) {
        return str_repeat('-', $ancho) . "\n";
    };
    
    // Encabezado
    $texto .= $centrar('TICKET CANTERA', $ancho) . "\n";
    $texto .= $centrar('Numero: ' . (isset($datos['Tck_Num']) ? $datos['Tck_Num'] : ''), $ancho) . "\n";
    $texto .= $separador($ancho);
    
    // Información del ticket
    $texto .= "Fecha y Hora: " . (isset($datos['Tck_Fec']) ? $datos['Tck_Fec'] : '') . "\n";
    $cliente_nombre = isset($datos['cliente_nombre']) ? $datos['cliente_nombre'] : '';
    if (strlen($cliente_nombre) > ($ancho - 10)) {
        $texto .= "Cliente: " . substr($cliente_nombre, 0, $ancho - 10) . "\n";
    } else {
        $texto .= "Cliente: " . $cliente_nombre . "\n";
    }
    $texto .= "RUC/Cedula: " . (isset($datos['Prs_Ced']) ? $datos['Prs_Ced'] : '') . "\n";
    $direccion = isset($datos['Prs_Dir']) ? $datos['Prs_Dir'] : '';
    if (strlen($direccion) > ($ancho - 12)) {
        $texto .= "Direccion: " . substr($direccion, 0, $ancho - 12) . "\n";
    } else {
        $texto .= "Direccion: " . $direccion . "\n";
    }
    $texto .= "Vehiculo: " . (isset($datos['Veh_Pla']) ? $datos['Veh_Pla'] : '') . "\n";
    $veh_tip = isset($datos['Veh_Tip']) ? $datos['Veh_Tip'] : '';
    if (strlen($veh_tip) > ($ancho - 16)) {
        $texto .= "Tip. Vehiculo: " . substr($veh_tip, 0, $ancho - 16) . "\n";
    } else {
        $texto .= "Tip. Vehiculo: " . $veh_tip . "\n";
    }
    $texto .= "Capacidad: " . (isset($datos['Veh_Cap']) ? $datos['Veh_Cap'] : '') . "\n";
    $texto .= $separador($ancho);
    
    // Detalles del ticket
   /* $texto .= "DETALLE\n";
    $texto .= $separador($ancho);*/
    
    // Encabezados de tabla
    $texto .= sprintf("%-18s %8s %10s %10s\n", 
        substr("Producto", 0, 18),
        "Cant.",
        "P.Unit",
        "Total"
    );
    $texto .= $separador($ancho);
    
    // Detalles
    if (isset($datos['detalles']) && is_array($datos['detalles'])) {
        foreach ($datos['detalles'] as $det) {
            $producto = substr(isset($det['Pro_Des']) ? $det['Pro_Des'] : 'N/A', 0, 18);
            $cantidad = number_format(isset($det['Dtk_Can']) ? $det['Dtk_Can'] : 0, 4);
            $precio = number_format(isset($det['Dtk_Pru']) ? $det['Dtk_Pru'] : 0, 4);
            $total = number_format(isset($det['Dtk_Tot']) ? $det['Dtk_Tot'] : 0, 4);
            
            $texto .= sprintf("%-18s %8s %10s %10s\n", 
                $producto,
                $cantidad,
                $precio,
                $total
            );
        }
    }
    
    $texto .= $separador($ancho);
    
    // Totales
    $texto .= sprintf("%-30s %18s\n", "Valor Neto:", number_format(isset($datos['Tck_Val']) ? $datos['Tck_Val'] : 0, 4));
    $texto .= sprintf("%-30s %18s\n", "IVA:", number_format(isset($datos['Tck_IvA']) ? $datos['Tck_IvA'] : 0, 4));
    $texto .= $separador($ancho);
    $texto .= sprintf("%-30s %18s\n", "TOTAL:", number_format(isset($datos['Tck_Tot']) ? $datos['Tck_Tot'] : 0, 4));
    $texto .= $separador($ancho);
    
    // Pie de página
   // $texto .= $centrar("Gracias por su preferencia", $ancho) . "\n";
    $texto .= "\n\n";
    
    return $texto;
}
