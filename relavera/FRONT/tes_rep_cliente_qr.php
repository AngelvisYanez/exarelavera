<?php
/**
 * Reporte de Cliente con Código QR - Formato Medio A4
 *
 * @author Sistema ODIN
 * @version 1.0
 * @package tesoreria.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Mani;

// Obtener datos del cliente
$Man_Cod = isset($_GET['Man_Cod']) ? $_GET['Man_Cod'] : '';
$manifiesto = null;

if ($Man_Cod != '') {
    // Consulta 11: obtiene datos de cliente-persona por Man_Cod
    $manifiesto = $obBD_con1->getRowConsulta('manifiesto.selectWhere', array('where' => array('manifiesto.Man_Cod' => $Man_Cod)), $obBD_conexion, true);
    $obBD_con1->utf8_change_param($manifiesto);
    $grupo_celda = $obBD_con1->getRowConsulta('manifiesto_celdas.selectWhere', array('where' => array('Cel_Tip' => 'G','Cel_Cod' => $manifiesto['Cel_Rec'])), $obBD_conexion);
}

// Obtener logo de la empresa
$logo_empresa = isset($Ses_Emp_Log) && $Ses_Emp_Log != '' ? '../../'.$Ses_Emp_Log : '../../skins/new/img/logo.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Cliente - <?php echo $Ses_Sys_Nom; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            /* top, right, bottom, left */
            margin: 10mm 15mm 10mm 10mm;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e2e8f0;
            min-height: 100vh;
            padding: 10px;
        }

        .page-a4 {
            width: 210mm;
            max-height: 148.5mm;
            background: white;
            margin: 0 auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 8mm;
            position: relative;
        }

        /* Header del documento */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 8px;
            border-bottom: 2px solid #3b82f6;
            margin-bottom: 10px;
        }

        .company-info {
            flex: 1;
        }

        .company-logo {
            max-height: 35px;
            max-width: 120px;
            margin-bottom: 4px;
        }

        .company-name {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .company-details {
            font-size: 9px;
            color: #64748b;
        }

        .doc-title-box {
            text-align: right;
        }

        .doc-title {
            font-size: 14px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 2px;
        }

        .doc-subtitle {
            font-size: 9px;
            color: #64748b;
        }

        .doc-date {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* Contenido principal - 2 columnas */
        .main-content {
            display: flex;
            gap: 12px;
            margin-bottom: 8px;
            align-items: flex-start; /* que ambas columnas inicien arriba */
            flex-wrap: nowrap;       /* evita que el QR baje */
        }

        .info-column {
            flex: 1;
        }

        .qr-column {
            width: 200px;
            flex-shrink: 0;
        }

        /* Sección de información */
        .section-title {
            font-size: 10px;
            font-weight: 700;
            color: white;
            padding: 5px 8px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-radius: 4px 4px 0 0;
            display: flex;
            align-items: center;
            gap: 5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 4px 4px;
            overflow: hidden;
        }

        .info-table tr {
            border-bottom: 1px solid #e2e8f0;
        }

        .info-table tr:last-child {
            border-bottom: none;
        }

        .info-table th {
            width: 100px;
            padding: 5px 8px;
            text-align: left;
            background: #f8fafc;
            color: #475569;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            vertical-align: top;
        }

        .info-table td {
            padding: 5px 8px;
            color: #1e293b;
            font-size: 9px;
            font-weight: 500;
        }

        .info-table td.highlight {
            font-size: 10px;
            font-weight: 700;
            color: #3b82f6;
        }

        /* QR Box */
        .qr-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }

        .qr-box-title {
            font-size: 9px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        #qrcode {
            display: flex;
            justify-content: center;
            margin-bottom: 6px;
        }

        #qrcode img {
            border-radius: 4px;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 140px;
            max-height: 140px;
        }

        .qr-code-text {
            font-size: 7px;
            color: #94a3b8;
            font-style: italic;
        }

        /* Sección adicional */
        .additional-section {
            margin-top: 10px;
        }

        .notes-box {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px;
            background: #fefce8;
            margin-top: 6px;
        }

        .notes-title {
            font-size: 8px;
            font-weight: 600;
            color: #854d0e;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .notes-content {
            font-size: 8px;
            color: #713f12;
            line-height: 1.4;
        }

        /* Footer del documento */
        .doc-footer {
            position: absolute;
            bottom: 5mm;
            left: 8mm;
            right: 8mm;
            padding-top: 5px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7px;
            color: #94a3b8;
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo {
            font-weight: 700;
            color: #3b82f6;
        }

        /* Botones de acción */
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-print {
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: white;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-close {
            background: white;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-close:hover {
            background: #f1f5f9;
        }

        /* Estado vacío */
        .no-client {
            text-align: center;
            padding: 80px 40px;
        }

        .no-client i {
            font-size: 80px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .no-client h3 {
            font-size: 20px;
            color: #475569;
            margin-bottom: 10px;
        }

        .no-client p {
            color: #94a3b8;
        }

        /* Estilos de impresión */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .page-a4 {
                box-shadow: none;
                padding: 8mm;
                width: 210mm;
                max-height: 148.5mm;
                page-break-after: always;
                page-break-inside: avoid;
            }

            .action-buttons {
                display: none;
            }

            .doc-footer {
                position: absolute;
                bottom: 5mm;
            }

            /* Mantener siempre 2 columnas al imprimir */
            .main-content {
                display: flex;
                flex-direction: row !important;
                align-items: flex-start;
                flex-wrap: nowrap;
            }

            .qr-column {
                width: 200px !important;
                flex-shrink: 0;
            }
        }

        /* Responsive */
        @media (max-width: 900px) {
            .page-a4 {
                width: 100%;
                min-height: auto;
                padding: 15px;
            }

            .main-content {
                flex-direction: column;
            }

            .qr-column {
                width: 100%;
            }

            .action-buttons {
                position: relative;
                top: 0;
                right: 0;
                margin-bottom: 20px;
                justify-content: center;
            }

            .doc-footer {
                position: relative;
                bottom: 0;
                left: 0;
                right: 0;
                margin-top: 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Botones de acción -->
    <div class="action-buttons">
        <button class="btn btn-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir
        </button>
        <button class="btn btn-close" onclick="window.close()">
            <i class="bi bi-x-lg"></i> Cerrar
        </button>
    </div>

    <div class="page-a4">
        <?php if ($manifiesto): ?>
        
        <!-- Header del documento -->
        <div class="doc-header">
            <div class="company-info">
                <img src="<?php echo $logo_empresa; ?>" alt="Logo" class="company-logo" onerror="this.style.display='none'">
                <div class="company-name"><?php echo htmlspecialchars($Ses_Emp_Nom); ?></div>
                <div class="company-details">
                    <?php echo htmlspecialchars($Ses_Suc_Nom); ?>
                </div>
            </div>
            <div class="doc-title-box">
                <div class="doc-title">SOLICITUD DE INGRESO</div>
                <div class="doc-subtitle">Información y Código QR</div>
                <div class="doc-date">
                    <i class="bi bi-calendar3"></i> 
                    Generado: <?php echo date('d/m/Y H:i'); ?>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="main-content">
            <!-- Columna de información -->
            <div class="info-column">
                <div class="section-title">
                    <i class="bi bi-person-badge"></i> Datos del Manifiesto
                </div>
                <table class="info-table">
                    <tr>
                        <th>No. Manifiesto</th>
                        <td class="highlight" style="font-size: 12px;"><?php echo htmlspecialchars($manifiesto['ManNum']); ?></td>
                    </tr>
                    <tr>
                        <th>Planta</th>
                        <td class="highlight" style="font-size: 12px;"><?php echo htmlspecialchars($manifiesto['Pla_Nom']); ?></td>
                    </tr>
                    <tr>
                        <th>Vehiculo</th>
                        <td class="highlight" style="font-size: 12px;"><?php echo htmlspecialchars($manifiesto['Veh_Pla']); ?></td>
                    </tr>
                    <tr>
                        <th>GUIA DE REMISION</th>
                        <td class="highlight" style="font-size: 12px;"><?php echo htmlspecialchars($manifiesto['Man_Gui']); ?></td>
                    </tr>
                    <tr>
                        <th>Peso</th>
                        <td class="highlight" style="font-size: 12px;"><?php echo htmlspecialchars($manifiesto['Man_Pes']); ?> KG</td>
                    </tr>
                    <tr>
                        <th>Fecha/Hora Máximo de Ingreso</th>
                        <td class="highlight" style="font-size: 12px;"><?php echo !empty($manifiesto['Man_Fea']) ? htmlspecialchars($manifiesto['Man_Fea'] . ' ' . $manifiesto['Man_Fes_Hor']) : '<span style="color: #94a3b8;">No registrado</span>'; ?></td>
                    </tr>
                    <tr>
                        <th>Chofer</th>
                        <td><?php echo htmlspecialchars($manifiesto['chofer']); ?></td>
                    </tr>
                    <tr>
                        <th>Celda Asignada</th>
                        <td><?php echo !empty($manifiesto['Prs_Tel']) ? htmlspecialchars($grupo_celda['Cel_Nom'].' / Celda: '.$manifiesto['Cel_Nom'].' - '.$manifiesto['Cel_Num']) : '<span style="color: #94a3b8;">No registrado</span>'; ?></td>
                    </tr>                    
                </table>

            </div>

            <!-- Columna del QR -->
            <div class="qr-column">
                <div class="qr-box">
                    <div class="qr-box-title">
                        <i class="bi bi-qr-code"></i> Código QR
                    </div>
                    <div id="qrcode"></div>
                    <div class="qr-code-text">
                        Escanea para obtener<br>información del cliente
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer del documento -->
        <div class="doc-footer">
            <div class="footer-left">
                <span class="footer-logo">EXA</span>
                <span>|</span>
                <span>Sistema Contable</span>
            </div>
            <div class="footer-right">
                Página 1 de 1
            </div>
        </div>

        <script>
            // Generar QR con los datos del cliente
            var qrData = {
                codigo: '<?php echo addslashes($manifiesto['Man_Cod']); ?>',
                cedula: '<?php echo addslashes($manifiesto['Prs_Ced']); ?>',
                nombre: '<?php echo addslashes($manifiesto['cliente']); ?>',
                telefono: '<?php echo addslashes(isset($manifiesto['Prs_Tel']) ? $manifiesto['Prs_Tel'] : ''); ?>',
                correo: '<?php echo addslashes(isset($manifiesto['Prs_Cor']) ? $manifiesto['Prs_Cor'] : ''); ?>'
            };

            // Crear texto para el QR (formato vCard simplificado)
            var qrText = 'CLIENTE: ' + qrData.nombre + '\n';
            qrText += 'CEDULA/RUC: ' + qrData.cedula + '\n';
            qrText += 'CODIGO: ' + qrData.codigo + '\n';
            if (qrData.telefono) qrText += 'TEL: ' + qrData.telefono + '\n';
            if (qrData.correo) qrText += 'EMAIL: ' + qrData.correo;

            // Generar QR (un poco más grande para ocupar mejor el espacio)
            var qr = qrcode(0, 'M');
            qr.addData(qrText);
            qr.make();
            // Aumentamos ligeramente el tamaño del módulo del QR
            document.getElementById('qrcode').innerHTML = qr.createImgTag(5, 6);
        </script>

        <?php else: ?>
        
        <!-- Estado: Cliente no encontrado -->
        <div class="no-client">
            <i class="bi bi-person-x"></i>
            <h3>Manifiesto no encontrado</h3>
            <p>No se pudo obtener la información del manifiesto solicitado.</p>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>
