<?php
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_maquinaria_horometro.php');
require_once('../../Librerias/TCPDF/MYPDF.php');

$obBD_conexion = new Class_Log_Conexion_Maquinaria_Horometro($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Maquinaria_Horometro;

$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'individual';
$anio = isset($_GET['anio']) ? trim($_GET['anio']) : date('Y');
$mes = isset($_GET['mes']) ? trim($_GET['mes']) : date('m');
$maq = isset($_GET['maq']) ? trim($_GET['maq']) : 'TODAS';
$ope = isset($_GET['ope']) ? trim($_GET['ope']) : 'TODOS';

$anio_mes = $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT);

$params = array(
    'anio_mes' => $anio_mes,
    'Veh_Cod' => $maq,
    'Cho_Cod' => $ope,
    'Emp_Cod' => $_SESSION['Ses_Emp_Cod']
);

$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Sistema EXA');
$pdf->SetAuthor('Sistema EXA');
$pdf->SetTitle('Reporte de Horómetro de Maquinaria');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

$logo_path = "../../imagenes/$Ses_Emp_Cod/relavera.png";
if (!file_exists($logo_path)) {
    $logo_path = "../../imagenes/620/relavera.png";
}
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 15, 10, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 7, 'REPORTE DE HORÓMETRO DE MAQUINARIA', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Periodo: ' . $anio_mes, 0, 1, 'C');
$pdf->Ln(5);

if ($tipo === 'individual') {
    // Buscar Operador Nombre
    $op_nom = "N/D";
    if ($ope !== 'TODOS') {
        $cho_row = $obBD_con1->getRowConsultaSql("SELECT CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as nom FROM chofer c INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod WHERE c.Cho_Cod = " . (int)$ope, $obBD_conexion);
        if ($cho_row) $op_nom = $cho_row['nom'];
    }

    $ficha = array('id' => 'N/D', 'marca' => 'N/D', 'modelo' => 'N/A', 'serie' => 'N/A', 'propiedad' => 'N/A');
    if ($maq !== 'TODAS') {
        $row_ficha = $obBD_con1->getRowConsulta(20, $params, $obBD_conexion);
        if ($row_ficha) {
            $ficha['id'] = $row_ficha['id'];
            $ficha['marca'] = $row_ficha['marca'];
            $ficha['modelo'] = $row_ficha['modelo'];
            $ficha['serie'] = $row_ficha['serie'];
            $ficha['propiedad'] = $row_ficha['propiedad'];
        }
    }

    $html = '<table border="1" cellpadding="4" cellspacing="0" width="100%">
        <tr style="background-color:#f4f4f4;font-weight:bold;">
            <td width="20%">Placa / Ficha:</td><td width="30%">' . $ficha['id'] . '</td>
            <td width="20%">Marca:</td><td width="30%">' . $ficha['marca'] . '</td>
        </tr>
        <tr>
            <td>Operador:</td><td colspan="3">' . $op_nom . '</td>
        </tr>
    </table><br/>';

    $listado = $obBD_con1->getArrayConsulta(21, $params, $obBD_conexion);
    if (!is_array($listado)) $listado = array();

    $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%">
        <tr style="background-color:#004080; color:#ffffff; font-weight:bold; text-align:center;">
            <th width="10%">Día</th>
            <th width="20%">Fecha</th>
            <th width="15%">H. Inicial</th>
            <th width="15%">H. Final</th>
            <th width="15%">Desfase</th>
            <th width="25%">H. Productivas</th>
        </tr>';

    $total_prod = 0;
    foreach ($listado as $row) {
        $html .= '<tr style="text-align:center;">
            <td>' . $row['dia'] . '</td>
            <td>' . $row['fecha'] . '</td>
            <td>' . number_format((float)$row['hor_inicial'], 2) . '</td>
            <td>' . number_format((float)$row['hor_final'], 2) . '</td>
            <td>' . number_format((float)$row['descuento'], 2) . '</td>
            <td>' . number_format((float)$row['prod_hrs'], 2) . '</td>
        </tr>';
        $total_prod += (float)$row['prod_hrs'];
    }

    if (count($listado) === 0) {
        $html .= '<tr><td colspan="6" style="text-align:center;">No hay datos en este periodo</td></tr>';
    } else {
        $html .= '<tr style="font-weight:bold; background-color:#e4e4e4;">
            <td colspan="5" style="text-align:right;">TOTAL HORAS PRODUCTIVAS:</td>
            <td style="text-align:center;">' . number_format($total_prod, 2) . '</td>
        </tr>';
    }
    $html .= '</table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
} else {
    // Reporte Consolidado
    $listado = $obBD_con1->getArrayConsulta(22, $params, $obBD_conexion);
    if (!is_array($listado)) $listado = array();

    $html = '<table border="1" cellpadding="4" cellspacing="0" width="100%">
        <tr style="background-color:#004080; color:#ffffff; font-weight:bold; text-align:center;">
            <th width="20%">Máquina</th>
            <th width="35%">Operador</th>
            <th width="15%">Hrs Trab.</th>
            <th width="15%">Desfase</th>
            <th width="15%">Hrs Prod.</th>
        </tr>';

    $sum_trab = 0;
    $sum_desfase = 0;
    $sum_prod = 0;
    
    foreach ($listado as $row) {
        $html .= '<tr style="text-align:center;">
            <td>' . $row['maquina'] . '</td>
            <td style="text-align:left;">' . $row['operador'] . '</td>
            <td>' . number_format((float)$row['horas_trabajadas'], 2) . '</td>
            <td>' . number_format((float)$row['desfase'], 2) . '</td>
            <td>' . number_format((float)$row['horas_productivas'], 2) . '</td>
        </tr>';
        $sum_trab += (float)$row['horas_trabajadas'];
        $sum_desfase += (float)$row['desfase'];
        $sum_prod += (float)$row['horas_productivas'];
    }

    if (count($listado) === 0) {
        $html .= '<tr><td colspan="5" style="text-align:center;">No hay datos en este periodo</td></tr>';
    } else {
        $html .= '<tr style="font-weight:bold; background-color:#e4e4e4;">
            <td colspan="2" style="text-align:right;">TOTALES:</td>
            <td style="text-align:center;">' . number_format($sum_trab, 2) . '</td>
            <td style="text-align:center;">' . number_format($sum_desfase, 2) . '</td>
            <td style="text-align:center;">' . number_format($sum_prod, 2) . '</td>
        </tr>';
    }
    $html .= '</table>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

$pdf->Output('reporte_horometro.pdf', 'I');
?>
