<?php
/**
 * Exportacion PDF Tareas Despacho (archivo dedicado para evitar salida previa/BOM)
 * Uso: aud_export_tareas_pdf.php?Tar_Periodo=2026-02&search=...&Per_Cod=...
 *   o  aud_export_tareas_pdf.php?FechaDesde=YYYY-MM-DD&FechaHasta=YYYY-MM-DD&...
 */
ob_start();
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/aud_log_despacho_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Despacho($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Despacho();
$Ses_Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;

$per = trim(isset($_GET['Tar_Periodo']) ? $_GET['Tar_Periodo'] : '');
$fdesde = trim(isset($_GET['FechaDesde']) ? $_GET['FechaDesde'] : '');
$fhasta = trim(isset($_GET['FechaHasta']) ? $_GET['FechaHasta'] : '');
if ($per === '' && ($fdesde === '' || $fhasta === '')) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Indique periodo o rango de fechas.'));
    exit;
}

$data = array('Emp_Cod' => $Ses_Emp_Cod);
if ($per !== '') $data['Tar_Periodo'] = $per;
if ($fdesde !== '' && $fhasta !== '') { $data['FechaDesde'] = $fdesde; $data['FechaHasta'] = $fhasta; }
if (!empty($_GET['search'])) $data['search'] = trim($_GET['search']);
$perCod = !empty($_GET['Per_Cod']) ? intval($_GET['Per_Cod']) : 0;
if ($perCod > 0) $data['Per_Cod'] = $perCod;

// Etiqueta del período para el PDF (mes o rango de fechas)
$etiquetaPeriodo = $per;
if ($etiquetaPeriodo === '' && $fdesde !== '' && $fhasta !== '') {
    $etiquetaPeriodo = 'del ' . date('d/m/Y', strtotime($fdesde)) . ' al ' . date('d/m/Y', strtotime($fhasta));
}
// Nombre del usuario cuando se filtra por uno
$nombreUsuario = '';
if ($perCod > 0) {
    $listaPersonal = $obBD_con1->getArrayConsulta(35, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    if (is_array($listaPersonal)) {
        foreach ($listaPersonal as $row) {
            if (isset($row['Per_Cod']) && (int)$row['Per_Cod'] === $perCod) {
                $nombreUsuario = isset($row['Nombre']) ? trim($row['Nombre']) : '';
                if ($nombreUsuario === '' && isset($row['Personal_Nombre'])) $nombreUsuario = trim($row['Personal_Nombre']);
                break;
            }
        }
    }
}

$arr = $obBD_con1->getArrayConsulta(53, $data, $obBD_conexion);
if ($obBD_con1->Error != 0) {
    $obBD_con1->setError(0, '');
    $arr = $obBD_con1->getArrayConsulta(54, $data, $obBD_conexion);
}
$arr = is_array($arr) ? $arr : array();

// Armar estructura como el grid de la pestaña Tareas:
// - columnas dinámicas por servicio
// - filas por cliente con día de declaración
$clientesMap = array();
$diaDeclMap = array();
$serviciosMap = array();
$tareasPorClienteServicio = array(); // [Cli_Cod][Ser_Cod][] = row

function aud_dia_declaracion_desde_ruc($ruc) {
    if (!$ruc) return null;
    $solo = preg_replace('/\D/', '', $ruc);
    if (strlen($solo) < 9) return null;
    $dig9 = (int)substr($solo, 8, 1);
    $map = array(0 => 28, 1 => 10, 2 => 12, 3 => 14, 4 => 16, 5 => 18, 6 => 20, 7 => 22, 8 => 24, 9 => 26);
    return isset($map[$dig9]) ? $map[$dig9] : null;
}

function aud_color_por_estado($estado) {
    $e = strtoupper(trim((string)$estado));
    // Colores aproximados a los de la pestaña (sin gradiente)
    if ($e === 'PENDIENTE')  return array(254, 243, 199);      // #fef3c7 amarillo suave
    if ($e === 'ASIGNADA')   return array(219, 234, 254);      // #dbeafe azul muy claro
    if ($e === 'EN_PROCESO') return array(147, 197, 253);      // #93c5fd azul más intenso
    if ($e === 'FINALIZADA') return array(134, 239, 172);      // #86efac verde
    if ($e === 'VENCIDA')    return array(254, 202, 202);      // #fecaca rojo suave
    if ($e === 'OBSERVADA')  return array(254, 215, 170);      // #fed7aa naranja suave
    return array(226, 232, 240);                               // gris claro (otros)
}

foreach ($arr as $r) {
    $cli = isset($r['Cli_Cod']) ? (int)$r['Cli_Cod'] : 0;
    $ser = isset($r['Ser_Cod']) ? (int)$r['Ser_Cod'] : 0;
    if ($cli <= 0 || $ser <= 0) continue;

    if (!isset($clientesMap[$cli])) {
        $clientesMap[$cli] = isset($r['Cliente_Nombre']) ? $r['Cliente_Nombre'] : '';
    }
    if (!array_key_exists($cli, $diaDeclMap)) {
        $diaDb = (isset($r['Ruc_Dia_Declaracion']) && $r['Ruc_Dia_Declaracion'] !== '' && $r['Ruc_Dia_Declaracion'] !== null)
            ? $r['Ruc_Dia_Declaracion']
            : null;
        $diaCalc = aud_dia_declaracion_desde_ruc(isset($r['Ruc_Str']) ? $r['Ruc_Str'] : '');
        $diaDeclMap[$cli] = $diaDb !== null ? $diaDb : $diaCalc;
    }
    if (!isset($serviciosMap[$ser])) {
        $serviciosMap[$ser] = array(
            'Ser_Cod' => $ser,
            'Ser_Nombre' => isset($r['Ser_Nombre']) ? $r['Ser_Nombre'] : ''
        );
    }
    if (!isset($tareasPorClienteServicio[$cli])) {
        $tareasPorClienteServicio[$cli] = array();
    }
    if (!isset($tareasPorClienteServicio[$cli][$ser])) {
        $tareasPorClienteServicio[$cli][$ser] = array();
    }
    $tareasPorClienteServicio[$cli][$ser][] = $r;
}

// Ordenar servicios alfabéticamente
$serviciosArr = array_values($serviciosMap);
usort($serviciosArr, function ($a, $b) {
    return strcasecmp($a['Ser_Nombre'], $b['Ser_Nombre']);
});

// Ordenar clientes por día de declaración y luego por nombre
$clientesOrden = array_keys($clientesMap);
usort($clientesOrden, function ($a, $b) use ($diaDeclMap, $clientesMap) {
    $diaA = isset($diaDeclMap[$a]) ? $diaDeclMap[$a] : null;
    $diaB = isset($diaDeclMap[$b]) ? $diaDeclMap[$b] : null;
    $numA = ($diaA === '-' || $diaA === '' || $diaA === null) ? 99 : (int)$diaA;
    $numB = ($diaB === '-' || $diaB === '' || $diaB === null) ? 99 : (int)$diaB;
    if ($numA !== $numB) return $numA - $numB;
    $nomA = isset($clientesMap[$a]) ? $clientesMap[$a] : '';
    $nomB = isset($clientesMap[$b]) ? $clientesMap[$b] : '';
    return strcasecmp($nomA, $nomB);
});

$rutaFpdf = realpath(dirname(__FILE__) . '/../../Librerias/fpdf/fpdf.php');
if (!file_exists($rutaFpdf)) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Libreria PDF no disponible.'));
    exit;
}
require_once($rutaFpdf);

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Helvetica', 'B', 10);
$titulo = 'Tareas del despacho';
$pdf->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $titulo), 0, 1);
$pdf->SetFont('Helvetica', '', 9);
$lineaPeriodo = 'Periodo: ' . ($etiquetaPeriodo !== '' ? $etiquetaPeriodo : '(no indicado)');
$pdf->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $lineaPeriodo), 0, 1);
if ($nombreUsuario !== '') {
    $pdf->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Usuario: ' . $nombreUsuario), 0, 1);
}
$pdf->Ln(2);

if (empty($arr) || empty($clientesOrden) || empty($serviciosArr)) {
    $pdf->Cell(0, 8, 'No hay tareas para el periodo indicado.', 0, 1);
} else {
    // Calcular anchos de columnas similares al grid HTML
    // FPDF clásico no tiene GetPageWidth/GetPageHeight, usamos propiedades públicas
    $pageWidth = $pdf->w - $pdf->lMargin - $pdf->rMargin;
    $wCliente = 50;
    $wDia = 15;
    $countServicios = count($serviciosArr);
    $wServicio = $countServicios > 0 ? max(20, ($pageWidth - $wCliente - $wDia) / $countServicios) : 30;

    // Encabezados
    $pdf->SetFillColor(114, 161, 207);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->Cell($wCliente, 7, 'CLIENTES', 1, 0, 'C', true);
    $pdf->Cell($wDia, 7, 'DIA DECL.', 1, 0, 'C', true);
    foreach ($serviciosArr as $s) {
        $nom = strtoupper($s['Ser_Nombre']);
        $pdf->Cell($wServicio, 7, substr(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $nom), 0, 18), 1, 0, 'C', true);
    }
    $pdf->Ln();

    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor(0, 0, 0);

    foreach ($clientesOrden as $cliCod) {
        // Salto de página si es necesario
        if ($pdf->GetY() > $pdf->h - $pdf->bMargin - 20) {
            $pdf->AddPage();
            $pdf->SetFillColor(114, 161, 207);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell($wCliente, 7, 'CLIENTES', 1, 0, 'C', true);
            $pdf->Cell($wDia, 7, 'DIA DECL.', 1, 0, 'C', true);
            foreach ($serviciosArr as $s) {
                $nom = strtoupper($s['Ser_Nombre']);
                $pdf->Cell($wServicio, 7, substr(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $nom), 0, 18), 1, 0, 'C', true);
            }
            $pdf->Ln();
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->SetTextColor(0, 0, 0);
        }

        $diaDecl = isset($diaDeclMap[$cliCod]) && $diaDeclMap[$cliCod] !== null ? $diaDeclMap[$cliCod] : '-';
        $cliNom = isset($clientesMap[$cliCod]) ? $clientesMap[$cliCod] : '';

        // Calcular altura de la fila en función del máximo de actividades en cualquier servicio
        $maxLineas = 1;
        foreach ($serviciosArr as $s) {
            $serCod = $s['Ser_Cod'];
            $tareas = isset($tareasPorClienteServicio[$cliCod][$serCod]) ? $tareasPorClienteServicio[$cliCod][$serCod] : array();
            $lineas = max(1, count($tareas));
            if ($lineas > $maxLineas) $maxLineas = $lineas;
        }
        $hLinea = 4;
        $rowHeight = $maxLineas * $hLinea;

        $xStart = $pdf->GetX();
        $yStart = $pdf->GetY();

        // Cliente: cuadro completo y nombre fijo dentro (con salto de línea si es largo)
        $pdf->SetXY($xStart, $yStart);
        $pdf->Cell($wCliente, $rowHeight, '', 1, 0, 'L');
        $pdf->SetXY($xStart + 1.5, $yStart + 1.2);
        $nombreCli = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $cliNom);
        $pdf->MultiCell($wCliente - 3, 3.5, $nombreCli, 0, 'L');
        $x = $xStart + $wCliente;
        $pdf->SetXY($x, $yStart);

        // Día decl.: cuadro y valor centrado verticalmente
        $pdf->Cell($wDia, $rowHeight, '', 1, 0, 'C');
        $pdf->SetXY($x, $yStart + max(0.5, ($rowHeight / 2) - 2));
        $pdf->Cell($wDia, 4, (string)$diaDecl, 0, 0, 'C');
        $x += $wDia;
        $pdf->SetXY($x, $yStart);

        // Columnas por servicio: dibujamos celdas con bloques coloreados por tarea
        foreach ($serviciosArr as $s) {
            $serCod = $s['Ser_Cod'];
            $tareas = isset($tareasPorClienteServicio[$cliCod][$serCod]) ? $tareasPorClienteServicio[$cliCod][$serCod] : array();

            $xCelda = $x;
            $yCelda = $yStart;

            // Borde externo de la celda de servicio
            $pdf->SetDrawColor(148, 163, 184); // gris similar al grid
            $pdf->Rect($xCelda, $yCelda, $wServicio, $rowHeight);

            // Dibujar cada tarea como una "pastilla" de color, similar a los chips del grid
            $chipIndex = 0;
            foreach ($tareas as $t) {
                if ($chipIndex >= $maxLineas) break; // evitar desbordar la celda
                $estRaw = isset($t['Tar_Est']) ? strtoupper($t['Tar_Est']) : '';
                $cntUsu = isset($t['Cnt_Usuarios']) ? (int)$t['Cnt_Usuarios'] : 0;
                // Mismas reglas que en el grid: pendiente + usuarios => Asignada
                $estadoColor = ($estRaw === 'PENDIENTE' && $cntUsu > 0) ? 'ASIGNADA' : $estRaw;
                list($rCol, $gCol, $bCol) = aud_color_por_estado($estadoColor);
                $nomAct = isset($t['Act_Nombre']) ? $t['Act_Nombre'] : '';
                $texto = substr(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $nomAct), 0, 40);

                $chipY = $yCelda + $chipIndex * $hLinea + 0.6;
                $chipH = $hLinea - 1;
                if ($chipH < 2) $chipH = 2;

                // Fondo de la pastilla
                $pdf->SetFillColor($rCol, $gCol, $bCol);
                $pdf->Rect($xCelda + 0.6, $chipY, $wServicio - 1.2, $chipH, 'F');

                // Texto de la actividad
                $pdf->SetTextColor(15, 23, 42); // casi negro, como en el grid
                $pdf->SetXY($xCelda + 1.2, $chipY + 0.3);
                $pdf->Cell($wServicio - 2.4, $chipH, $texto, 0, 0, 'L');

                $chipIndex++;
            }

            $x += $wServicio;
            $pdf->SetXY($x, $yStart);
        }

        // Mover a la siguiente fila
        $pdf->SetXY($xStart, $yStart + $rowHeight);
    }

    // Leyenda de estados (similar a la vista HTML)
    $pdf->Ln(4);
    if ($pdf->GetY() > $pdf->h - $pdf->bMargin - 15) {
        $pdf->AddPage();
    }
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor(51, 65, 85);
    $xLey = $pdf->lMargin;
    $yLey = $pdf->GetY();
    $pdf->SetXY($xLey, $yLey);
    $pdf->Cell(18, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Leyenda:'), 0, 0, 'L');

    $estadosLey = array(
        array('Pendiente', 'PENDIENTE'),
        array('Asignada', 'ASIGNADA'),
        array('En proceso', 'EN_PROCESO'),
        array('Finalizada', 'FINALIZADA'),
        array('Vencida', 'VENCIDA'),
    );
    $xChip = $pdf->GetX() + 2;
    foreach ($estadosLey as $info) {
        list($label, $estadoInt) = $info;
        list($rCol, $gCol, $bCol) = aud_color_por_estado($estadoInt);
        $chipW = 23;
        $chipH = 5;
        $pdf->SetFillColor($rCol, $gCol, $bCol);
        $pdf->Rect($xChip, $yLey, $chipW, $chipH, 'F');
        $pdf->SetXY($xChip + 1, $yLey);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell($chipW - 2, $chipH, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $label), 0, 0, 'C');
        $xChip += $chipW + 2;
    }
}

$nombreArchivo = $per ? $per : ($fdesde && $fhasta ? $fdesde . '_' . $fhasta : date('Y-m-d_His'));
$nombre = 'Tareas_Despacho_' . $nombreArchivo . '_' . date('Y-m-d_His') . '.pdf';

while (ob_get_level()) ob_end_clean();

$pdf->Output($nombre, 'D');
