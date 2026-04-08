<?php
/**
 * @abstract Calculadora de Participación a Trabajadores e Impuesto a la Renta
 * @author Sistema EXA
 * @version 2.0
 * Fecha de creación: 2025-11-28
 * Soporte para múltiples años fiscales
 */

// =====================================
// TABLAS DE IMPUESTO A LA RENTA POR AÑO
// =====================================
// Puedes agregar más años copiando el formato

$tablas_impuesto = array(

    // Año 2025 - Res. No. NAC-DGERCGC24-00000041 del 18 de diciembre de 2024
    '2025' => array(
        'resolucion' => 'NAC-DGERCGC24-00000041 del 18 de diciembre de 2024',
        'datos' => array(
            array('fraccion_basica' => 0,       'exceso_hasta' => 12081,    'impuesto_fb' => 0,      'porcentaje' => 0),
            array('fraccion_basica' => 12081,   'exceso_hasta' => 15387,    'impuesto_fb' => 0,      'porcentaje' => 5),
            array('fraccion_basica' => 15387,   'exceso_hasta' => 19978,    'impuesto_fb' => 165,    'porcentaje' => 10),
            array('fraccion_basica' => 19978,   'exceso_hasta' => 26422,    'impuesto_fb' => 624,    'porcentaje' => 12),
            array('fraccion_basica' => 26422,   'exceso_hasta' => 34770,    'impuesto_fb' => 1398,   'porcentaje' => 15),
            array('fraccion_basica' => 34770,   'exceso_hasta' => 46089,    'impuesto_fb' => 2650,   'porcentaje' => 20),
            array('fraccion_basica' => 46089,   'exceso_hasta' => 61359,    'impuesto_fb' => 4914,   'porcentaje' => 25),
            array('fraccion_basica' => 61359,   'exceso_hasta' => 81817,    'impuesto_fb' => 8731,   'porcentaje' => 30),
            array('fraccion_basica' => 81817,   'exceso_hasta' => 108810,   'impuesto_fb' => 14869,  'porcentaje' => 35),
            array('fraccion_basica' => 108810,  'exceso_hasta' => 999999999,'impuesto_fb' => 24316,  'porcentaje' => 37)
        )
    ),

    // Año 2024 - Res. NAC DGERCGC23-00000036
    '2024' => array(
        'resolucion' => 'NAC DGERCGC23-00000036',
        'datos' => array(
            array('fraccion_basica' => 0,       'exceso_hasta' => 11902,    'impuesto_fb' => 0,      'porcentaje' => 0),
            array('fraccion_basica' => 11902,   'exceso_hasta' => 15159,    'impuesto_fb' => 0,      'porcentaje' => 5),
            array('fraccion_basica' => 15159,   'exceso_hasta' => 19682,    'impuesto_fb' => 163,    'porcentaje' => 10),
            array('fraccion_basica' => 19682,   'exceso_hasta' => 26031,    'impuesto_fb' => 615,    'porcentaje' => 12),
            array('fraccion_basica' => 26031,   'exceso_hasta' => 34255,    'impuesto_fb' => 1377,   'porcentaje' => 15),
            array('fraccion_basica' => 34255,   'exceso_hasta' => 45407,    'impuesto_fb' => 2611,   'porcentaje' => 20),
            array('fraccion_basica' => 45407,   'exceso_hasta' => 60450,    'impuesto_fb' => 4841,   'porcentaje' => 25),
            array('fraccion_basica' => 60450,   'exceso_hasta' => 80605,    'impuesto_fb' => 8602,   'porcentaje' => 30),
            array('fraccion_basica' => 80605,   'exceso_hasta' => 107199,   'impuesto_fb' => 14648,  'porcentaje' => 35),
            array('fraccion_basica' => 107199,  'exceso_hasta' => 999999999,'impuesto_fb' => 23956,  'porcentaje' => 37)
        )
    ),

    // Año 2023 - SRO 335 DL 742 Junio 2023
    '2023' => array(
        'resolucion' => 'SRO 335 DL 742 Junio 2023',
        'datos' => array(
            array('fraccion_basica' => 0,       'exceso_hasta' => 11722,    'impuesto_fb' => 0,      'porcentaje' => 0),
            array('fraccion_basica' => 11722,   'exceso_hasta' => 14930,    'impuesto_fb' => 0,      'porcentaje' => 5),
            array('fraccion_basica' => 14930,   'exceso_hasta' => 19385,    'impuesto_fb' => 160,    'porcentaje' => 10),
            array('fraccion_basica' => 19385,   'exceso_hasta' => 25638,    'impuesto_fb' => 606,    'porcentaje' => 12),
            array('fraccion_basica' => 25638,   'exceso_hasta' => 33738,    'impuesto_fb' => 1356,   'porcentaje' => 15),
            array('fraccion_basica' => 33738,   'exceso_hasta' => 44721,    'impuesto_fb' => 2571,   'porcentaje' => 20),
            array('fraccion_basica' => 44721,   'exceso_hasta' => 59537,    'impuesto_fb' => 4768,   'porcentaje' => 25),
            array('fraccion_basica' => 59537,   'exceso_hasta' => 79388,    'impuesto_fb' => 8472,   'porcentaje' => 30),
            array('fraccion_basica' => 79388,   'exceso_hasta' => 105580,   'impuesto_fb' => 14427,  'porcentaje' => 35),
            array('fraccion_basica' => 105580,  'exceso_hasta' => 999999999,'impuesto_fb' => 23594,  'porcentaje' => 37)
        )
    ),

    // Año 2022 - Ley Orgánica para el desarrollo económico y sostenibilidad fiscal tras la pandemia Covid-19 art. 43
    '2022' => array(
        'resolucion' => 'Ley Orgánica desarrollo económico y sostenibilidad fiscal Covid-19 art. 43',
        'datos' => array(
            array('fraccion_basica' => 0,       'exceso_hasta' => 11310,    'impuesto_fb' => 0,       'porcentaje' => 0),
            array('fraccion_basica' => 11310,   'exceso_hasta' => 14410,    'impuesto_fb' => 0,       'porcentaje' => 5),
            array('fraccion_basica' => 14410,   'exceso_hasta' => 18010,    'impuesto_fb' => 155,     'porcentaje' => 10),
            array('fraccion_basica' => 18010,   'exceso_hasta' => 21630,    'impuesto_fb' => 515,     'porcentaje' => 12),
            array('fraccion_basica' => 21630,   'exceso_hasta' => 31630,    'impuesto_fb' => 949.40,  'porcentaje' => 15),
            array('fraccion_basica' => 31630,   'exceso_hasta' => 41630,    'impuesto_fb' => 2449.40, 'porcentaje' => 20),
            array('fraccion_basica' => 41630,   'exceso_hasta' => 51630,    'impuesto_fb' => 4449.39, 'porcentaje' => 25),
            array('fraccion_basica' => 51630,   'exceso_hasta' => 61630,    'impuesto_fb' => 6949.39, 'porcentaje' => 30),
            array('fraccion_basica' => 61630,   'exceso_hasta' => 100000,   'impuesto_fb' => 9949.39, 'porcentaje' => 35),
            array('fraccion_basica' => 100000,  'exceso_hasta' => 999999999,'impuesto_fb' => 23378.88,'porcentaje' => 37)
        )
    )

    // =====================================
    // PARA AGREGAR MÁS AÑOS:
    // Copia el bloque de arriba y modifica los valores
    // =====================================
);

// =====================================
// TABLAS DE GASTOS PERSONALES POR AÑO
// =====================================

$tablas_gastos_personales = array(
    // Año 2025
    '2025' => array(
        array('cargas_familiares' => 0, 'canastas' => 7, 'gasto_deducible_max' => 5588.17, 'rebaja_impuestos' => 1005.87),
        array('cargas_familiares' => 1, 'canastas' => 9, 'gasto_deducible_max' => 7184.79, 'rebaja_impuestos' => 1293.26),
        array('cargas_familiares' => 2, 'canastas' => 11, 'gasto_deducible_max' => 8781.41, 'rebaja_impuestos' => 1580.65),
        array('cargas_familiares' => 3, 'canastas' => 14, 'gasto_deducible_max' => 11176.34, 'rebaja_impuestos' => 2011.74),
        array('cargas_familiares' => 4, 'canastas' => 17, 'gasto_deducible_max' => 13571.27, 'rebaja_impuestos' => 2442.83),
        array('cargas_familiares' => 5, 'canastas' => 20, 'gasto_deducible_max' => 15966.20, 'rebaja_impuestos' => 2873.92),
        array('cargas_familiares' => 'catastrofica', 'canastas' => 100, 'gasto_deducible_max' => 79831.00, 'rebaja_impuestos' => 14369.58)
    ),
    
    // Año 2024
    '2024' => array(
        array('cargas_familiares' => 0, 'canastas' => 7, 'gasto_deducible_max' => 5526.99, 'rebaja_impuestos' => 994.86),
        array('cargas_familiares' => 1, 'canastas' => 9, 'gasto_deducible_max' => 7106.13, 'rebaja_impuestos' => 1279.10),
        array('cargas_familiares' => 2, 'canastas' => 11, 'gasto_deducible_max' => 8685.27, 'rebaja_impuestos' => 1563.35),
        array('cargas_familiares' => 3, 'canastas' => 14, 'gasto_deducible_max' => 11053.98, 'rebaja_impuestos' => 1989.72),
        array('cargas_familiares' => 4, 'canastas' => 17, 'gasto_deducible_max' => 13422.69, 'rebaja_impuestos' => 2416.08),
        array('cargas_familiares' => 5, 'canastas' => 20, 'gasto_deducible_max' => 15791.40, 'rebaja_impuestos' => 2842.45),
        array('cargas_familiares' => 'catastrofica', 'canastas' => 100, 'gasto_deducible_max' => 78957.00, 'rebaja_impuestos' => 14212.26)
    ),
    
    // Año 2023
    '2023' => array(
        array('cargas_familiares' => 0, 'canastas' => 7, 'gasto_deducible_max' => 5352.97, 'rebaja_impuestos' => 963.53),
        array('cargas_familiares' => 1, 'canastas' => 9, 'gasto_deducible_max' => 6882.39, 'rebaja_impuestos' => 1238.83),
        array('cargas_familiares' => 2, 'canastas' => 11, 'gasto_deducible_max' => 8411.81, 'rebaja_impuestos' => 1514.13),
        array('cargas_familiares' => 3, 'canastas' => 14, 'gasto_deducible_max' => 10705.94, 'rebaja_impuestos' => 1927.07),
        array('cargas_familiares' => 4, 'canastas' => 17, 'gasto_deducible_max' => 13000.07, 'rebaja_impuestos' => 2340.01),
        array('cargas_familiares' => 5, 'canastas' => 20, 'gasto_deducible_max' => 15294.20, 'rebaja_impuestos' => 2752.96),
        array('cargas_familiares' => 'catastrofica', 'canastas' => 20, 'gasto_deducible_max' => 15294.20, 'rebaja_impuestos' => 2752.96)
    ),
    
    // Año 2022 - No aplica gastos personales
    '2022' => array()
);

// Obtener años disponibles (ordenados de mayor a menor)
$anios_disponibles = array_keys($tablas_impuesto);
rsort($anios_disponibles);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Calculadora de Impuesto a la Renta de Personas Naturales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Librería jsPDF para PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- Librería para Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
            padding: 20px;
        }
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .header p {
            opacity: 0.8;
            font-size: 1.1em;
        }
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        @media (max-width: 900px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }
        .card {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            padding: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        .card h3 {
            color: #1a1a2e;
            margin-bottom: 15px;
            font-size: 1.15em;
            border-bottom: 3px solid #e94560;
            padding-bottom: 8px;
        }
        .card h3 i {
            color: #e94560;
            margin-right: 10px;
        }
        .input-group {
            margin-bottom: 12px;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 0.95em;
        }
        .input-wrapper {
            display: flex;
            border: 2px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            transition: border-color 0.3s;
        }
        .input-wrapper:focus-within {
            border-color: #e94560;
        }
        .input-prefix, .input-suffix {
            background: #f0f0f0;
            padding: 10px 12px;
            font-weight: bold;
            color: #666;
            font-size: 0.95em;
        }
        .input-wrapper input, .input-wrapper select {
            flex: 1;
            border: none;
            padding: 10px 12px;
            font-size: 1em;
            outline: none;
        }
        .input-wrapper input {
            text-align: right;
        }
        .input-wrapper select {
            cursor: pointer;
            background: white;
        }
        .input-wrapper input:disabled,
        .input-wrapper select:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .input-group.disabled {
            opacity: 0.6;
            pointer-events: none;
        }
        /* Selector de Año destacado */
        .year-selector {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            color: white;
        }
        .year-selector label {
            color: white !important;
            font-size: 1.1em;
        }
        .year-selector .input-wrapper {
            border: none;
            background: white;
        }
        .year-selector select {
            font-size: 1.3em;
            font-weight: bold;
            color: #667eea;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 15px;
        }
        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(233, 69, 96, 0.4);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #666;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        /* Botones de exportación */
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #ddd;
        }
        .btn-export {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-pdf {
            background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
            color: white;
        }
        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }
        .btn-excel {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }
        .btn-excel:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }
        /* Resultados */
        .results-section {
            display: none;
            margin-top: 25px;
        }
        .result-box {
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
        }
        .result-utilidad {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }
        .result-utilidad .label {
            opacity: 0.9;
            font-size: 0.9em;
        }
        .result-utilidad .value {
            font-size: 1.6em;
            font-weight: bold;
        }
        .result-participacion {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .result-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            font-size: 0.95em;
        }
        .result-row:last-child {
            border-bottom: none;
        }
        .desglose-box {
            background: #fff8e1;
            border-left: 5px solid #ffc107;
        }
        .desglose-box h4 {
            color: #f57c00;
            margin-bottom: 15px;
        }
        .desglose-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            color: #795548;
            border-bottom: 1px dashed #ffe082;
            font-size: 0.9em;
        }
        .desglose-item:last-child {
            border-bottom: none;
        }
        .total-box {
            background: linear-gradient(135deg, #d32f2f 0%, #f44336 100%);
            color: white;
            text-align: center;
        }
        .total-box h4 {
            opacity: 0.9;
            font-size: 1em;
        }
        .total-box .amount {
            font-size: 2em;
            font-weight: bold;
            margin: 8px 0;
        }
        /* Tabla */
        .tabla-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .tabla-header h3 {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }
        .year-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 1.2em;
            font-weight: bold;
        }
        .tabla-impuesto {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9em;
        }
        .tabla-impuesto th {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 12px 8px;
            text-align: center;
        }
        .tabla-impuesto td {
            padding: 12px 8px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        /* Centrar primera columna de tabla de gastos personales */
        #tablaGastosPersonales td:first-child,
        #tablaGastosPersonales th:first-child {
            text-align: center;
        }
        .tabla-impuesto tr:hover {
            background-color: #f5f5f5;
        }
        .tabla-impuesto tr.active-row {
            background: linear-gradient(90deg, #e8f5e9, #c8e6c9) !important;
            font-weight: bold;
        }
        .tabla-impuesto tr.active-row td {
            color: #2e7d32;
        }
        .tabla-impuesto tr.highlight-row {
            background: linear-gradient(90deg, #e3f2fd, #bbdefb) !important;
            font-weight: bold;
        }
        .tabla-impuesto tr.highlight-row td {
            color: #1976d2;
        }
        .info-note {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 0 10px 10px 0;
            margin-top: 20px;
            font-size: 0.9em;
            color: #1565c0;
        }
        .years-available {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
            justify-content: center;
        }
        .year-chip {
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.95em;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .year-chip:hover {
            background: rgba(255,255,255,0.3);
            border-color: white;
        }
        .year-chip.active {
            background: white;
            color: #1a1a2e;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fa fa-calculator"></i> Calculadora de Impuesto a la Renta de Personas Naturales</h1>
            <p>Participación a Trabajadores e Impuesto a la Renta - Ecuador</p>
            <div class="years-available">
                <?php foreach($anios_disponibles as $index => $anio): ?>
                <span class="year-chip <?php echo $index === 0 ? 'active' : ''; ?>" onclick="cambiarAnio('<?php echo $anio; ?>')"><?php echo $anio; ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="main-grid">
            <!-- Columna Izquierda -->
            <div>
                <div class="card">
                    <h3><i class="fa fa-edit"></i> Datos de Entrada</h3>
                    
                    <!-- Selector de Año -->
                    <div class="year-selector">
                        <div class="input-group" style="margin-bottom: 0;">
                            <label><i class="fa fa-calendar"></i> Año Fiscal</label>
                            <div class="input-wrapper">
                                <select id="anio_fiscal" onchange="actualizarTabla()">
                                    <?php foreach($anios_disponibles as $anio): ?>
                                    <option value="<?php echo $anio; ?>"><?php echo $anio; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Utilidad Neta del Ejercicio</label>
                        <div class="input-wrapper">
                            <span class="input-prefix">$</span>
                            <input type="text" id="utilidad_neta" placeholder="0.00" value="">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>% Participación Trabajadores</label>
                        <div class="input-wrapper">
                            <input type="number" id="porcentaje_participacion" value="15" min="0" max="100" step="0.01">
                            <span class="input-suffix">%</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Ingresos Anuales de Sueldo</label>
                        <div class="input-wrapper">
                            <span class="input-prefix">$</span>
                            <input type="text" id="ingresos_sueldo" placeholder="0.00" value="">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Cargas Familiares</label>
                        <div class="input-wrapper">
                            <input type="number" id="cargas_familiares" value="0" min="0" max="10" step="1">
                        </div>
                    </div>

                    <div class="input-group" id="grupo_gastos_personales">
                        <label>Valor de Gastos Personales</label>
                        <div class="input-wrapper">
                            <span class="input-prefix">$</span>
                            <input type="text" id="gastos_personales" placeholder="0.00" value="">
                        </div>
                    </div>

                    <div class="input-group" id="grupo_enfermedades_catastroficas">
                        <label>Personas con o a cargo de personas con enfermedades catastróficas</label>
                        <div class="input-wrapper">
                            <select id="enfermedades_catastroficas">
                                <option value="no">No</option>
                                <option value="si">Sí</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Retenciones Recibidas</label>
                        <div class="input-wrapper">
                            <span class="input-prefix">$</span>
                            <input type="text" id="retenciones_recibidas" placeholder="0.00" value="">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Crédito Tributario Año Anterior</label>
                        <div class="input-wrapper">
                            <span class="input-prefix">$</span>
                            <input type="text" id="credito_tributario" placeholder="0.00" value="">
                        </div>
                    </div>

                    <div class="btn-group">
                        <button class="btn btn-primary" onclick="calcular()">
                            <i class="fa fa-cogs"></i> CALCULAR
                        </button>
                        <button class="btn btn-secondary" onclick="limpiar()">
                            <i class="fa fa-eraser"></i> Limpiar
                        </button>
                    </div>

                    <!-- Resultados -->
                    <div class="results-section" id="resultados">
                        <div class="result-box result-utilidad">
                            <div class="label">Utilidad Neta - Año <span id="res_anio">2025</span></div>
                            <div class="value">$ <span id="res_utilidad_neta">0.00</span></div>
                        </div>

                        <div class="result-box result-participacion">
                            <h4 style="margin-top:0;"><i class="fa fa-users"></i> Participación a Trabajadores</h4>
                            <div class="result-row">
                                <span>Porcentaje aplicado:</span>
                                <span><strong><span id="res_porcentaje">15</span>%</strong></span>
                            </div>
                            <div class="result-row">
                                <span>Participación Trabajadores:</span>
                                <span><strong>$ <span id="res_participacion">0.00</span></strong></span>
                            </div>
                            <div class="result-row">
                                <span>Utilidad después de Participación:</span>
                                <span><strong>$ <span id="res_utilidad_despues">0.00</span></strong></span>
                            </div>
                        </div>

                        <div class="result-box desglose-box">
                            <h4><i class="fa fa-list-ol"></i> Desglose del Cálculo</h4>
                            <div class="desglose-item">
                                <span>Base Imponible:</span>
                                <span>$ <span id="des_base">0.00</span></span>
                            </div>
                            <div class="desglose-item">
                                <span>Fracción Básica:</span>
                                <span>$ <span id="des_fraccion">0.00</span></span>
                            </div>
                            <div class="desglose-item">
                                <span>Impuesto Fracción Básica:</span>
                                <span>$ <span id="des_impuesto_fb">0.00</span></span>
                            </div>
                            <div class="desglose-item">
                                <span>Excedente:</span>
                                <span>$ <span id="des_excedente">0.00</span></span>
                            </div>
                            <div class="desglose-item">
                                <span>% Impuesto Excedente:</span>
                                <span><span id="des_porcentaje_exc">0</span>%</span>
                            </div>
                            <div class="desglose-item">
                                <span>Impuesto sobre Excedente:</span>
                                <span>$ <span id="des_impuesto_exc">0.00</span></span>
                            </div>
                        </div>

                        <div class="result-box total-box">
                            <h4><i class="fa fa-money"></i> TOTAL IMPUESTO A LA RENTA</h4>
                            <div class="amount">$ <span id="res_total_impuesto">0.00</span></div>
                            <small>Impuesto Fracción Básica + Impuesto Excedente</small>
                        </div>

                        <div class="result-box desglose-box" id="box_deducciones" style="display: none;">
                            <h4><i class="fa fa-minus-circle"></i> Deducciones</h4>
                            <div class="desglose-item" id="item_deduccion_gastos" style="display: none;">
                                <span>(-) Deducción de Gastos Personales:</span>
                                <span>$ <span id="des_deduccion_gastos">0.00</span></span>
                            </div>
                            <div class="desglose-item">
                                <span>(-) Retenciones Recibidas:</span>
                                <span>$ <span id="des_retenciones">0.00</span></span>
                            </div>
                            <div class="desglose-item">
                                <span>(-) Crédito Tributario Año Anterior:</span>
                                <span>$ <span id="des_credito_tributario">0.00</span></span>
                            </div>
                        </div>

                        <div class="result-box total-box" id="box_valor_final" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);">
                            <h4 id="titulo_valor_final"><i class="fa fa-check-circle"></i> VALOR A PAGAR</h4>
                            <div class="amount">$ <span id="res_valor_pagar">0.00</span></div>
                        </div>

                        <!-- Botones de Exportación -->
                        <div class="export-buttons">
                            <button class="btn-export btn-pdf" onclick="exportarPDF()">
                                <i class="fa fa-file-pdf-o"></i> Descargar PDF
                            </button>
                            <button class="btn-export btn-excel" onclick="exportarExcel()">
                                <i class="fa fa-file-excel-o"></i> Descargar Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Tabla -->
            <div>
                <div class="card">
                    <div class="tabla-header">
                        <h3><i class="fa fa-table"></i> Tabla Impuesto a la Renta</h3>
                        <span class="year-badge" id="tabla_anio_badge">2025</span>
                    </div>
                    <table class="tabla-impuesto" id="tablaImpuesto">
                        <thead>
                            <tr>
                                <th>Fracción<br>Básica</th>
                                <th>Exceso<br>Hasta</th>
                                <th>Impuesto<br>Frac. Básica</th>
                                <th>% Imp.<br>Excedente</th>
                            </tr>
                        </thead>
                        <tbody id="tablaBody">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                    <div class="info-note" id="info_resolucion">
                        <strong><i class="fa fa-info-circle"></i> Resolución:</strong> <span id="resolucion_texto"></span>
                    </div>
                </div>
                
                <!-- Tabla de Gastos Personales -->
                <div class="card" id="card_gastos_personales" style="display: none; margin-top: 25px;">
                    <div class="tabla-header">
                        <h3><i class="fa fa-money"></i> Tabla de Gastos Personales</h3>
                        <span class="year-badge" id="tabla_gastos_anio_badge">2025</span>
                    </div>
                    <table class="tabla-impuesto" id="tablaGastosPersonales">
                        <thead>
                            <tr>
                                <th>Cargas<br>Familiares</th>
                                <th>Nº Canastas<br>Familiares</th>
                                <th>Gasto Deducible<br>Máximo (USD)</th>
                                <th>Rebaja en<br>Impuestos (USD)</th>
                            </tr>
                        </thead>
                        <tbody id="tablaGastosBody">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                    <div class="info-note" style="margin-top: 15px;">
                        <strong><i class="fa fa-info-circle"></i> Nota:</strong> El empleado debe entregar el formulario de proyección de gastos personales en el mes febrero y podrá entregar un formulario corregido en el mes de agosto (DL 742).
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Todas las tablas de impuesto por año
        var tablasImpuesto = <?php echo json_encode($tablas_impuesto); ?>;
        // Tablas de gastos personales por año
        var tablasGastosPersonales = <?php echo json_encode($tablas_gastos_personales); ?>;
        var anioActual = '<?php echo $anios_disponibles[0]; ?>';
        
        // Variables para almacenar los resultados del cálculo
        var datosCalculo = {};

        function formatMoney(value) {
            return value.toLocaleString('es-EC', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function parseNumber(value) {
            if (typeof value === 'string') {
                value = value.replace(/[^\d.,]/g, '').replace(',', '.');
            }
            return parseFloat(value) || 0;
        }

        function actualizarTabla() {
            var anio = document.getElementById('anio_fiscal').value;
            anioActual = anio;
            var tablaData = tablasImpuesto[anio];
            
            // Actualizar badge del año
            document.getElementById('tabla_anio_badge').textContent = anio;
            
            // Actualizar resolución
            document.getElementById('resolucion_texto').textContent = tablaData.resolucion;
            
            // Actualizar chips de años
            document.querySelectorAll('.year-chip').forEach(function(chip) {
                chip.classList.remove('active');
                if (chip.textContent === anio) {
                    chip.classList.add('active');
                }
            });
            
            // Generar filas de la tabla
            var tbody = document.getElementById('tablaBody');
            tbody.innerHTML = '';
            
            tablaData.datos.forEach(function(row, index) {
                var tr = document.createElement('tr');
                tr.id = 'fila_' + index;
                tr.innerHTML = 
                    '<td>$ ' + formatMoney(row.fraccion_basica) + '</td>' +
                    '<td>' + (row.exceso_hasta >= 999999999 ? 'En adelante' : '$ ' + formatMoney(row.exceso_hasta)) + '</td>' +
                    '<td>$ ' + formatMoney(row.impuesto_fb) + '</td>' +
                    '<td>' + row.porcentaje + '%</td>';
                tbody.appendChild(tr);
            });
            
            // Bloquear/desbloquear campos de gastos personales según el año
            // NO limpiar valores, solo bloquear/desbloquear
            var grupoGastos = document.getElementById('grupo_gastos_personales');
            var grupoEnfermedades = document.getElementById('grupo_enfermedades_catastroficas');
            var inputGastos = document.getElementById('gastos_personales');
            var selectEnfermedades = document.getElementById('enfermedades_catastroficas');
            
            if (anio === '2022') {
                // Bloquear para 2022 - solo si no tiene valor, poner 0
                inputGastos.disabled = true;
                selectEnfermedades.disabled = true;
                if (!inputGastos.value || inputGastos.value === '') {
                    inputGastos.value = '0';
                }
                if (selectEnfermedades.value === '') {
                    selectEnfermedades.value = 'no';
                }
                grupoGastos.style.opacity = '0.6';
                grupoEnfermedades.style.opacity = '0.6';
            } else {
                // Habilitar para otros años
                inputGastos.disabled = false;
                selectEnfermedades.disabled = false;
                grupoGastos.style.opacity = '1';
                grupoEnfermedades.style.opacity = '1';
            }
            
            // Ocultar resultados al cambiar de año (pero mantener los valores ingresados)
            document.getElementById('resultados').style.display = 'none';
            
            // Actualizar tabla de gastos personales
            actualizarTablaGastosPersonales();
        }
        
        function actualizarTablaGastosPersonales() {
            var anio = document.getElementById('anio_fiscal').value;
            var tablaGastos = tablasGastosPersonales[anio];
            var cardGastos = document.getElementById('card_gastos_personales');
            var gastosPersonales = parseNumber(document.getElementById('gastos_personales').value);
            
            // Mostrar solo si el año tiene tabla de gastos personales (no es 2022) y hay gastos ingresados
            if (!tablaGastos || tablaGastos.length === 0) {
                cardGastos.style.display = 'none';
                return;
            }
            
            // Mostrar la tabla si hay gastos personales ingresados o si el año permite gastos personales
            if (gastosPersonales > 0 || anio !== '2022') {
                cardGastos.style.display = 'block';
            } else {
                cardGastos.style.display = 'none';
                return;
            }
            
            document.getElementById('tabla_gastos_anio_badge').textContent = anio;
            
            // Obtener valores actuales para resaltar la fila correspondiente
            var cargasFamiliares = parseInt(document.getElementById('cargas_familiares').value) || 0;
            var enfermedadesCatastroficas = document.getElementById('enfermedades_catastroficas').value;
            
            // Generar filas de la tabla
            var tbody = document.getElementById('tablaGastosBody');
            tbody.innerHTML = '';
            
            tablaGastos.forEach(function(row, index) {
                var tr = document.createElement('tr');
                tr.id = 'fila_gastos_' + index;
                
                // Determinar el texto para cargas familiares
                var textoCargas = row.cargas_familiares === 'catastrofica' 
                    ? 'Enfermedades Catastróficas' 
                    : row.cargas_familiares.toString();
                
                tr.innerHTML = 
                    '<td>' + textoCargas + '</td>' +
                    '<td>' + row.canastas + '</td>' +
                    '<td>$ ' + formatMoney(row.gasto_deducible_max) + '</td>' +
                    '<td>$ ' + formatMoney(row.rebaja_impuestos) + '</td>';
                
                // Resaltar la fila si coincide con los datos ingresados
                var debeResaltar = false;
                if (enfermedadesCatastroficas === 'si' && row.cargas_familiares === 'catastrofica') {
                    debeResaltar = true;
                } else if (enfermedadesCatastroficas === 'no' && row.cargas_familiares === cargasFamiliares) {
                    debeResaltar = true;
                }
                
                if (debeResaltar) {
                    tr.classList.add('highlight-row');
                }
                
                tbody.appendChild(tr);
            });
        }

        function cambiarAnio(anio) {
            document.getElementById('anio_fiscal').value = anio;
            actualizarTabla();
        }

        // Función para obtener límite de gastos personales según cargas familiares y año
        function obtenerLimiteGastosPersonales(anio, cargasFamiliares, tieneEnfermedadesCatastroficas) {
            var tablaGastos = tablasGastosPersonales[anio];
            if (!tablaGastos || tablaGastos.length === 0) {
                return { gasto_deducible_max: 0, rebaja_impuestos: 0 };
            }
            
            // Si tiene enfermedades catastróficas, buscar esa fila
            if (tieneEnfermedadesCatastroficas === 'si') {
                for (var i = 0; i < tablaGastos.length; i++) {
                    if (tablaGastos[i].cargas_familiares === 'catastrofica') {
                        return tablaGastos[i];
                    }
                }
            }
            
            // Buscar por número de cargas familiares
            for (var i = 0; i < tablaGastos.length; i++) {
                if (tablaGastos[i].cargas_familiares === cargasFamiliares) {
                    return tablaGastos[i];
                }
            }
            
            // Si no encuentra, retornar el primero (0 cargas)
            return tablaGastos[0] || { gasto_deducible_max: 0, rebaja_impuestos: 0 };
        }

        function calcular() {
            var anio = document.getElementById('anio_fiscal').value;
            var tablaImpuesto = tablasImpuesto[anio].datos;
            var utilidadNeta = parseNumber(document.getElementById('utilidad_neta').value);
            var porcentajeParticipacion = parseNumber(document.getElementById('porcentaje_participacion').value);
            var ingresosSueldo = parseNumber(document.getElementById('ingresos_sueldo').value);
            var cargasFamiliares = parseInt(document.getElementById('cargas_familiares').value) || 0;
            var gastosPersonales = parseNumber(document.getElementById('gastos_personales').value);
            var enfermedadesCatastroficas = document.getElementById('enfermedades_catastroficas').value;
            var retencionesRecibidas = parseNumber(document.getElementById('retenciones_recibidas').value);
            var creditoTributario = parseNumber(document.getElementById('credito_tributario').value);

            if (utilidadNeta <= 0) {
                alert('Debe ingresar una utilidad neta válida mayor a 0');
                return;
            }

            // Calcular participación a trabajadores
            var participacion = utilidadNeta * (porcentajeParticipacion / 100);
            var utilidadDespues = utilidadNeta - participacion;

            // Base Imponible = Utilidad después de Participación + Ingresos de Sueldos
            var baseImponible = utilidadDespues + ingresosSueldo;

            // Encontrar la fracción correspondiente usando la base imponible
            var fraccionEncontrada = null;
            var indiceFraccion = -1;
            
            for (var i = 0; i < tablaImpuesto.length; i++) {
                var fila = tablaImpuesto[i];
                if (baseImponible >= fila.fraccion_basica && baseImponible < fila.exceso_hasta) {
                    fraccionEncontrada = fila;
                    indiceFraccion = i;
                    break;
                }
                if (i === tablaImpuesto.length - 1 && baseImponible >= fila.fraccion_basica) {
                    fraccionEncontrada = fila;
                    indiceFraccion = i;
                }
            }

            if (!fraccionEncontrada) {
                fraccionEncontrada = tablaImpuesto[0];
                indiceFraccion = 0;
            }

            // Calcular impuesto
            var excedente = baseImponible - fraccionEncontrada.fraccion_basica;
            var impuestoExcedente = excedente * (fraccionEncontrada.porcentaje / 100);
            var totalImpuesto = fraccionEncontrada.impuesto_fb + impuestoExcedente;

            // Calcular deducción de gastos personales (solo si no es 2022)
            var deduccionGastosPersonales = 0;
            if (anio !== '2022' && gastosPersonales > 0) {
                var limiteGastos = obtenerLimiteGastosPersonales(anio, cargasFamiliares, enfermedadesCatastroficas);
                // El gasto deducible es el menor entre gastos personales y el límite máximo
                var gastoDeducible = Math.min(gastosPersonales, limiteGastos.gasto_deducible_max);
                // La deducción es el 18% del gasto deducible, pero no puede exceder la rebaja máxima
                var deduccionCalculada = gastoDeducible * 0.18;
                deduccionGastosPersonales = Math.min(deduccionCalculada, limiteGastos.rebaja_impuestos);
            }

            // Calcular valor a pagar (puede ser negativo = crédito tributario)
            var valorAPagar = totalImpuesto - deduccionGastosPersonales - retencionesRecibidas - creditoTributario;

            // Guardar datos para exportación
            datosCalculo = {
                anio: anio,
                resolucion: tablasImpuesto[anio].resolucion,
                utilidadNeta: utilidadNeta,
                porcentajeParticipacion: porcentajeParticipacion,
                participacion: participacion,
                utilidadDespues: utilidadDespues,
                ingresosSueldo: ingresosSueldo,
                baseImponible: baseImponible,
                cargasFamiliares: cargasFamiliares,
                gastosPersonales: gastosPersonales,
                enfermedadesCatastroficas: enfermedadesCatastroficas,
                retencionesRecibidas: retencionesRecibidas,
                creditoTributario: creditoTributario,
                fraccionBasica: fraccionEncontrada.fraccion_basica,
                impuestoFB: fraccionEncontrada.impuesto_fb,
                excedente: excedente,
                porcentajeExcedente: fraccionEncontrada.porcentaje,
                impuestoExcedente: impuestoExcedente,
                totalImpuesto: totalImpuesto,
                deduccionGastosPersonales: deduccionGastosPersonales,
                valorAPagar: valorAPagar
            };

            // Mostrar resultados
            document.getElementById('res_anio').textContent = anio;
            document.getElementById('res_utilidad_neta').textContent = formatMoney(utilidadNeta);
            document.getElementById('res_porcentaje').textContent = porcentajeParticipacion;
            document.getElementById('res_participacion').textContent = formatMoney(participacion);
            document.getElementById('res_utilidad_despues').textContent = formatMoney(utilidadDespues);

            // Desglose
            document.getElementById('des_base').textContent = formatMoney(baseImponible);
            document.getElementById('des_fraccion').textContent = formatMoney(fraccionEncontrada.fraccion_basica);
            document.getElementById('des_impuesto_fb').textContent = formatMoney(fraccionEncontrada.impuesto_fb);
            document.getElementById('des_excedente').textContent = formatMoney(excedente);
            document.getElementById('des_porcentaje_exc').textContent = fraccionEncontrada.porcentaje;
            document.getElementById('des_impuesto_exc').textContent = formatMoney(impuestoExcedente);

            // Total
            document.getElementById('res_total_impuesto').textContent = formatMoney(totalImpuesto);

            // Deducciones
            var boxDeducciones = document.getElementById('box_deducciones');
            var itemDeduccionGastos = document.getElementById('item_deduccion_gastos');
            
            if (deduccionGastosPersonales > 0) {
                itemDeduccionGastos.style.display = 'flex';
                document.getElementById('des_deduccion_gastos').textContent = formatMoney(deduccionGastosPersonales);
                boxDeducciones.style.display = 'block';
            } else {
                itemDeduccionGastos.style.display = 'none';
                if (retencionesRecibidas > 0 || creditoTributario > 0) {
                    boxDeducciones.style.display = 'block';
                } else {
                    boxDeducciones.style.display = 'none';
                }
            }
            
            document.getElementById('des_retenciones').textContent = formatMoney(retencionesRecibidas);
            document.getElementById('des_credito_tributario').textContent = formatMoney(creditoTributario);
            
            // Mostrar valor final (puede ser negativo)
            var valorFinalAbsoluto = Math.abs(valorAPagar);
            document.getElementById('res_valor_pagar').textContent = formatMoney(valorFinalAbsoluto);
            
            // Cambiar título y color según si es positivo o negativo
            var boxValorFinal = document.getElementById('box_valor_final');
            var tituloValorFinal = document.getElementById('titulo_valor_final');
            
            if (valorAPagar < 0) {
                // Es un crédito tributario (saldo a favor) - VERDE
                tituloValorFinal.innerHTML = '<i class="fa fa-arrow-circle-down"></i> CRÉDITO TRIBUTARIO';
                boxValorFinal.style.background = 'linear-gradient(135deg, #2ecc71 0%, #27ae60 100%)';
            } else if (valorAPagar > 0) {
                // Es un valor a pagar - NARANJA
                tituloValorFinal.innerHTML = '<i class="fa fa-check-circle"></i> VALOR A PAGAR';
                boxValorFinal.style.background = 'linear-gradient(135deg, #ff9800 0%, #f57c00 100%)';
            } else {
                // Es cero
                tituloValorFinal.innerHTML = '<i class="fa fa-check-circle"></i> VALOR A PAGAR';
                boxValorFinal.style.background = 'linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%)';
            }

            // Resaltar fila de la tabla
            var filas = document.querySelectorAll('#tablaImpuesto tbody tr');
            filas.forEach(function(fila) { fila.classList.remove('active-row'); });
            document.getElementById('fila_' + indiceFraccion).classList.add('active-row');

            // Mostrar resultados
            document.getElementById('resultados').style.display = 'block';
            
            // Actualizar tabla de gastos personales para resaltar la fila correcta
            actualizarTablaGastosPersonales();
        }

        function limpiar() {
            document.getElementById('utilidad_neta').value = '';
            document.getElementById('porcentaje_participacion').value = '15';
            document.getElementById('ingresos_sueldo').value = '';
            document.getElementById('cargas_familiares').value = '0';
            document.getElementById('gastos_personales').value = '';
            document.getElementById('enfermedades_catastroficas').value = 'no';
            document.getElementById('retenciones_recibidas').value = '';
            document.getElementById('credito_tributario').value = '';
            document.getElementById('resultados').style.display = 'none';
            var filas = document.querySelectorAll('#tablaImpuesto tbody tr');
            filas.forEach(function(fila) { fila.classList.remove('active-row'); });
            datosCalculo = {};
        }

        // Exportar a PDF usando jsPDF
        function exportarPDF() {
            if (!datosCalculo.anio) {
                alert('Primero debe realizar el cálculo');
                return;
            }

            var { jsPDF } = window.jspdf;
            var doc = new jsPDF();
            
            var fecha = new Date().toLocaleDateString('es-EC');
            var hora = new Date().toLocaleTimeString('es-EC');
            
            // Colores
            var azul = [102, 126, 234];
            var verde = [17, 153, 142];
            var naranja = [255, 193, 7];
            var rojo = [233, 69, 96];
            
            // Título
            doc.setFillColor(azul[0], azul[1], azul[2]);
            doc.rect(0, 0, 210, 25, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text('CALCULO DE IMPUESTO A LA RENTA', 105, 9, { align: 'center' });
            
            doc.setFontSize(11);
            doc.text('PERSONAS NATURALES - Año Fiscal ' + datosCalculo.anio, 105, 16, { align: 'center' });
            
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            doc.text('Resolucion: ' + datosCalculo.resolucion, 105, 22, { align: 'center' });
            
            var y = 32;
            
            // Datos Iniciales
            doc.setFillColor(240, 240, 240);
            doc.roundedRect(10, y, 190, 58, 2, 2, 'F');
            doc.setTextColor(azul[0], azul[1], azul[2]);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('DATOS INICIALES', 15, y + 6);
            doc.setDrawColor(azul[0], azul[1], azul[2]);
            doc.setLineWidth(0.3);
            doc.line(10, y + 9, 200, y + 9);
            
            doc.setTextColor(60, 60, 60);
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            
            var lineaY = y + 15;
            var itemsDatos = [
                ['Utilidad Neta:', '$ ' + formatMoney(datosCalculo.utilidadNeta)],
                ['% Participacion:', datosCalculo.porcentajeParticipacion + '%'],
                ['Ingresos Sueldo:', '$ ' + formatMoney(datosCalculo.ingresosSueldo)],
                ['Cargas Familiares:', datosCalculo.cargasFamiliares.toString()],
                ['Gastos Personales:', '$ ' + formatMoney(datosCalculo.gastosPersonales)],
                ['Enf. Catastroficas:', datosCalculo.enfermedadesCatastroficas === 'si' ? 'Sí' : 'No'],
                ['Retenciones:', '$ ' + formatMoney(datosCalculo.retencionesRecibidas)],
                ['Credito Tributario:', '$ ' + formatMoney(datosCalculo.creditoTributario)]
            ];
            
            itemsDatos.forEach(function(item) {
                doc.text(item[0], 15, lineaY);
                doc.text(item[1], 195, lineaY, { align: 'right' });
                lineaY += 5.5;
            });
            
            y += 63;
            
            // Participación a Trabajadores
            doc.setFillColor(232, 245, 233);
            doc.roundedRect(10, y, 190, 30, 2, 2, 'F');
            doc.setDrawColor(verde[0], verde[1], verde[2]);
            doc.setLineWidth(0.3);
            doc.line(10, y + 9, 200, y + 9);
            
            doc.setTextColor(verde[0], verde[1], verde[2]);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('PARTICIPACION A TRABAJADORES', 15, y + 6);
            
            doc.setTextColor(60, 60, 60);
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            
            var lineaYParticipacion = y + 15;
            doc.text('Porcentaje aplicado:', 15, lineaYParticipacion);
            doc.text(datosCalculo.porcentajeParticipacion + '%', 195, lineaYParticipacion, { align: 'right' });
            lineaYParticipacion += 5.5;
            
            doc.text('Participacion:', 15, lineaYParticipacion);
            doc.setFont('helvetica', 'bold');
            doc.text('$ ' + formatMoney(datosCalculo.participacion), 195, lineaYParticipacion, { align: 'right' });
            lineaYParticipacion += 5.5;
            
            doc.setFont('helvetica', 'normal');
            doc.text('Utilidad despues:', 15, lineaYParticipacion);
            doc.setFont('helvetica', 'bold');
            doc.text('$ ' + formatMoney(datosCalculo.utilidadDespues), 195, lineaYParticipacion, { align: 'right' });
            
            y += 35;
            
            // Desglose del Cálculo
            doc.setFillColor(255, 248, 225);
            doc.roundedRect(10, y, 190, 50, 2, 2, 'F');
            doc.setDrawColor(naranja[0], naranja[1], naranja[2]);
            doc.setLineWidth(0.5);
            doc.line(10, y, 10, y + 50);
            
            doc.setTextColor(245, 124, 0);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('DESGLOSE DEL CALCULO', 15, y + 6);
            
            doc.setTextColor(121, 85, 72);
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            
            var lineaY = y + 14;
            var items = [
                ['Base Imponible:', '$ ' + formatMoney(datosCalculo.baseImponible)],
                ['Fraccion Basica:', '$ ' + formatMoney(datosCalculo.fraccionBasica)],
                ['Impuesto Fraccion Basica:', '$ ' + formatMoney(datosCalculo.impuestoFB)],
                ['Excedente:', '$ ' + formatMoney(datosCalculo.excedente)],
                ['% Impuesto Excedente:', datosCalculo.porcentajeExcedente + '%'],
                ['Impuesto sobre Excedente:', '$ ' + formatMoney(datosCalculo.impuestoExcedente)]
            ];
            
            items.forEach(function(item) {
                doc.text(item[0], 15, lineaY);
                doc.text(item[1], 195, lineaY, { align: 'right' });
                lineaY += 5.5;
            });
            
            y += 55;
            
            // Total Impuesto
            doc.setFillColor(rojo[0], rojo[1], rojo[2]);
            doc.roundedRect(10, y, 190, 22, 2, 2, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('TOTAL IMPUESTO A LA RENTA', 105, y + 7, { align: 'center' });
            
            doc.setFontSize(16);
            doc.text('$ ' + formatMoney(datosCalculo.totalImpuesto), 105, y + 16, { align: 'center' });
            
            y += 27;
            
            // Deducciones
            if (datosCalculo.deduccionGastosPersonales > 0 || datosCalculo.retencionesRecibidas > 0 || datosCalculo.creditoTributario > 0) {
                doc.setFillColor(255, 248, 225);
                var alturaDeducciones = 22;
                if (datosCalculo.deduccionGastosPersonales > 0) alturaDeducciones += 5.5;
                doc.roundedRect(10, y, 190, alturaDeducciones, 2, 2, 'F');
                
                doc.setTextColor(245, 124, 0);
                doc.setFontSize(9);
                doc.setFont('helvetica', 'bold');
                doc.text('DEDUCCIONES', 15, y + 6);
                
                doc.setTextColor(121, 85, 72);
                doc.setFontSize(8);
                doc.setFont('helvetica', 'normal');
                
                var lineaYDed = y + 13;
                if (datosCalculo.deduccionGastosPersonales > 0) {
                    doc.text('(-) Deduccion Gastos personales:', 15, lineaYDed);
                    doc.text('$ ' + formatMoney(datosCalculo.deduccionGastosPersonales), 195, lineaYDed, { align: 'right' });
                    lineaYDed += 5.5;
                }
                doc.text('(-) Retenciones recibidas:', 15, lineaYDed);
                doc.text('$ ' + formatMoney(datosCalculo.retencionesRecibidas), 195, lineaYDed, { align: 'right' });
                lineaYDed += 5.5;
                doc.text('(-) Credito Tributario Año anterior:', 15, lineaYDed);
                doc.text('$ ' + formatMoney(datosCalculo.creditoTributario), 195, lineaYDed, { align: 'right' });
                
                y += alturaDeducciones + 5;
            }
            
            // Valor a Pagar o Crédito Tributario
            var valorFinalAbsoluto = Math.abs(datosCalculo.valorAPagar);
            var tituloFinal = datosCalculo.valorAPagar < 0 ? 'CRÉDITO TRIBUTARIO' : 'VALOR A PAGAR';
            // Verde para crédito tributario, naranja para valor a pagar
            var colorFinal = datosCalculo.valorAPagar < 0 ? [46, 204, 113] : [255, 152, 0];
            if (datosCalculo.valorAPagar === 0) {
                colorFinal = [149, 165, 166];
            }
            
            doc.setFillColor(colorFinal[0], colorFinal[1], colorFinal[2]);
            doc.roundedRect(10, y, 190, 22, 2, 2, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text(tituloFinal, 105, y + 7, { align: 'center' });
            
            doc.setFontSize(16);
            doc.text('$ ' + formatMoney(valorFinalAbsoluto), 105, y + 16, { align: 'center' });
            
            // Pie de página
            doc.setTextColor(150, 150, 150);
            doc.setFontSize(7);
            doc.setFont('helvetica', 'normal');
            doc.text('Documento generado el ' + fecha + ' a las ' + hora, 105, 285, { align: 'center' });
            doc.text('Calculadora de Impuesto a la Renta de Personas Naturales - Ecuador', 105, 290, { align: 'center' });
            
            // Guardar
            doc.save('Impuesto_Renta_' + datosCalculo.anio + '.pdf');
        }

        // Exportar a Excel
        function exportarExcel() {
            if (!datosCalculo.anio) {
                alert('Primero debe realizar el cálculo');
                return;
            }

            // Crear datos para Excel
            var datos = [
                ['CALCULO DE IMPUESTO A LA RENTA - AÑO ' + datosCalculo.anio],
                ['Resolucion: ' + datosCalculo.resolucion],
                [''],
                ['CONCEPTO', 'VALOR'],
                [''],
                ['DATOS INICIALES', ''],
                ['Utilidad Neta del Ejercicio', datosCalculo.utilidadNeta],
                ['% Participacion Trabajadores', datosCalculo.porcentajeParticipacion + '%'],
                ['Ingresos Anuales de Sueldo', datosCalculo.ingresosSueldo],
                ['Cargas Familiares', datosCalculo.cargasFamiliares],
                ['Valor de Gastos Personales', datosCalculo.gastosPersonales],
                ['Personas con o a cargo de personas con enfermedades catastrofica', datosCalculo.enfermedadesCatastroficas],
                ['Retenciones Recibidas', datosCalculo.retencionesRecibidas],
                ['Credito Tributario Año anterior', datosCalculo.creditoTributario],
                [''],
                ['PARTICIPACION A TRABAJADORES', ''],
                ['Participacion Trabajadores', datosCalculo.participacion],
                ['Utilidad despues de Participacion', datosCalculo.utilidadDespues],
                [''],
                ['CALCULO DEL IMPUESTO', ''],
                ['Base Imponible', datosCalculo.baseImponible],
                ['Fraccion Basica', datosCalculo.fraccionBasica],
                ['Impuesto Fraccion Basica', datosCalculo.impuestoFB],
                ['Excedente', datosCalculo.excedente],
                ['% Impuesto sobre Excedente', datosCalculo.porcentajeExcedente + '%'],
                ['Impuesto sobre Excedente', datosCalculo.impuestoExcedente],
                [''],
                ['TOTAL IMPUESTO A LA RENTA', datosCalculo.totalImpuesto],
                ['(-)Deduccion de Gastos personales', datosCalculo.deduccionGastosPersonales],
                ['(-)Retenciones recibidas', datosCalculo.retencionesRecibidas],
                ['(-)Credito Tributario Año anterior', datosCalculo.creditoTributario],
                [''],
                [datosCalculo.valorAPagar < 0 ? 'CRÉDITO TRIBUTARIO' : 'VALOR A PAGAR', Math.abs(datosCalculo.valorAPagar)],
                [''],
                [''],
                ['Fecha de generacion:', new Date().toLocaleString('es-EC')]
            ];

            // Crear workbook
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.aoa_to_sheet(datos);

            // Ajustar anchos de columna
            ws['!cols'] = [
                { wch: 35 },
                { wch: 20 }
            ];

            XLSX.utils.book_append_sheet(wb, ws, 'Impuesto Renta ' + datosCalculo.anio);

            // Descargar archivo
            XLSX.writeFile(wb, 'Impuesto_Renta_' + datosCalculo.anio + '.xlsx');
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            actualizarTabla();
            
            // Enter para calcular
            document.getElementById('utilidad_neta').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') calcular();
            });
            document.getElementById('porcentaje_participacion').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') calcular();
            });
            
            // Actualizar tabla de gastos personales cuando cambien los valores relevantes
            document.getElementById('cargas_familiares').addEventListener('change', function() {
                actualizarTablaGastosPersonales();
            });
            document.getElementById('cargas_familiares').addEventListener('input', function() {
                actualizarTablaGastosPersonales();
            });
            document.getElementById('gastos_personales').addEventListener('input', function() {
                var anio = document.getElementById('anio_fiscal').value;
                var gastos = parseNumber(this.value);
                var cardGastos = document.getElementById('card_gastos_personales');
                if (anio !== '2022' && gastos > 0) {
                    cardGastos.style.display = 'block';
                    actualizarTablaGastosPersonales();
                } else if (gastos === 0 || gastos === '') {
                    // Ocultar solo si no hay gastos, pero mantener visible si hay otros datos
                    var cargas = parseInt(document.getElementById('cargas_familiares').value) || 0;
                    if (cargas === 0) {
                        cardGastos.style.display = 'none';
                    }
                }
            });
            document.getElementById('enfermedades_catastroficas').addEventListener('change', function() {
                actualizarTablaGastosPersonales();
            });
        });
    </script>
</body>
</html>
