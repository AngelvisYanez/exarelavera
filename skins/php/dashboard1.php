<?php
// Declaración de archivos necesarios
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/config.php/register_globals.php');
require_once('../../administrador/LOGICA/logica.php');

// require_once('../../administrador/LOGICA/TreeMenu.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Adm;


/* para pruebas */
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 9600);

// ==================== AJAX: CONSULTAS DEL DASHBOARD ====================
if (isset($_POST['getDashboardData'])) {
    header('Content-Type: application/json');
    
    $periodo = isset($_POST['periodo']) ? $_POST['periodo'] : 'anio';
    $fechaInicio = isset($_POST['fechaInicio']) ? $_POST['fechaInicio'] : date('Y-01-01');
    $fechaFin = isset($_POST['fechaFin']) ? $_POST['fechaFin'] : date('Y-12-31');
    $empCod = intval($_SESSION['Ses_Emp_Cod']);
    $sucCod = intval($_SESSION['Ses_Suc_Cod']);
    
    // Ajustar fechas según el período seleccionado
    switch($periodo) {
        case 'hoy':
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');
            break;
        case 'semana':
            $fechaInicio = date('Y-m-d', strtotime('monday this week'));
            $fechaFin = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'mes':
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-t');
            break;
        case 'trimestre':
            $mes = date('n');
            $trimestre = ceil($mes / 3);
            $fechaInicio = date('Y-' . str_pad(($trimestre - 1) * 3 + 1, 2, '0', STR_PAD_LEFT) . '-01');
            $fechaFin = date('Y-m-t', strtotime($fechaInicio . ' +2 months'));
            break;
        case 'anio':
            $fechaInicio = date('Y-01-01');
            $fechaFin = date('Y-12-31');
            break;
        case 'personalizado':
            // Usar las fechas enviadas
            break;
    }
    
    // Usar conexion directa con mysqli
    $baseDatos = isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : 'NO_DEFINIDA';
    $errores = array();
    
    // ========== CONSULTA VENTAS - CON FILTRO DE EMPRESA ========== //
    $params = array($empCod, $fechaInicio, $fechaFin);
    $rsVentas = $obBD_con1->getRowConsulta(125, $params, $obBD_conexion);
    
    // Si no hay resultados, inicializar con ceros
    if (!$rsVentas || !isset($rsVentas['subtotal'])) {
        $rsVentas = array('total' => 0, 'cantidad' => 0, 'subtotal' => 0, 'iva' => 0, 'ice' => 0);
    } else {
        // Total = Subtotal + IVA + ICE (calculados con los porcentajes reales)
        $subtotal = floatval($rsVentas['subtotal']);
        $iva = floatval($rsVentas['iva']);
        $ice = isset($rsVentas['ice']) ? floatval($rsVentas['ice']) : 0;
        $rsVentas['subtotal'] = round($subtotal, 2);
        $rsVentas['iva'] = round($iva, 2);
        $rsVentas['total'] = round($subtotal + $iva + $ice, 2);
    }
    
    // ========== CONSULTA COMPRAS - FILTRO POR PROVEEDOR DE EMPRESA ========== //
    $paramsCompras = array($empCod, $fechaInicio, $fechaFin);
    $rsCompras = $obBD_con1->getRowConsulta(126, $paramsCompras, $obBD_conexion);
    
    // Si no hay resultados, inicializar con ceros
    if (!$rsCompras || !isset($rsCompras['subtotal'])) {
        $rsCompras = array('total' => 0, 'cantidad' => 0, 'subtotal' => 0, 'iva' => 0, 'ice' => 0);
    } else {
        // Total = Subtotal + IVA + ICE (calculados con los porcentajes reales)
        $subtotal = floatval($rsCompras['subtotal']);
        $iva = floatval($rsCompras['iva']);
        $ice = isset($rsCompras['ice']) ? floatval($rsCompras['ice']) : 0;
        $rsCompras['subtotal'] = round($subtotal, 2);
        $rsCompras['iva'] = round($iva, 2);
        $rsCompras['total'] = round($subtotal + $iva + $ice, 2);
    }
    
    // ========== CONSULTA CLIENTES - CON FILTRO DE EMPRESA ==========
    $paramsClientes = array($empCod, $fechaInicio, $fechaFin);
    $rsClientes = $obBD_con1->getRowConsulta(127, $paramsClientes, $obBD_conexion);
    
    // ========== DATOS PARA GRÁFICOS - SIMPLIFICADOS ==========
    $ventasPorPeriodo = array();
    $comprasPorPeriodo = array();
    $labels = array();
    
    // Determinar granularidad según el período
    $diffDays = (strtotime($fechaFin) - strtotime($fechaInicio)) / (60 * 60 * 24);
    
    if ($diffDays <= 31) {
        // Por día - CON FILTRO DE EMPRESA - Con notas de crédito restando
        $paramsVentasDia = array($empCod, $fechaInicio, $fechaFin);
        $rsVentasDia = $obBD_con1->getArrayConsulta(128, $paramsVentasDia, $obBD_conexion);
        
        $paramsComprasDia = array($empCod, $fechaInicio, $fechaFin);
        $rsComprasDia = $obBD_con1->getArrayConsulta(129, $paramsComprasDia, $obBD_conexion);
        
        $ventasIndex = array();
        $comprasIndex = array();
        if(is_array($rsVentasDia)) foreach($rsVentasDia as $row) $ventasIndex[$row['fecha']] = floatval($row['total']);
        if(is_array($rsComprasDia)) foreach($rsComprasDia as $row) $comprasIndex[$row['fecha']] = floatval($row['total']);
        
        $currentDate = strtotime($fechaInicio);
        $endDate = strtotime($fechaFin);
        while($currentDate <= $endDate) {
            $fechaStr = date('Y-m-d', $currentDate);
            $labels[] = date('d/m', $currentDate);
            $ventasPorPeriodo[] = isset($ventasIndex[$fechaStr]) ? $ventasIndex[$fechaStr] : 0;
            $comprasPorPeriodo[] = isset($comprasIndex[$fechaStr]) ? $comprasIndex[$fechaStr] : 0;
            $currentDate = strtotime('+1 day', $currentDate);
        }
    } else {
        // Por mes - CON FILTRO DE EMPRESA - Con notas de crédito restando
        $paramsVentasMes = array($empCod, $fechaInicio, $fechaFin);
        $rsVentasMes = $obBD_con1->getArrayConsulta(130, $paramsVentasMes, $obBD_conexion);
        
        $paramsComprasMes = array($empCod, $fechaInicio, $fechaFin);
        $rsComprasMes = $obBD_con1->getArrayConsulta(131, $paramsComprasMes, $obBD_conexion);
        
        $nombresMeses = array('ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC');
        $ventasIndex = array();
        $comprasIndex = array();
        if(is_array($rsVentasMes)) foreach($rsVentasMes as $row) $ventasIndex[$row['mes']] = floatval($row['total']);
        if(is_array($rsComprasMes)) foreach($rsComprasMes as $row) $comprasIndex[$row['mes']] = floatval($row['total']);
        
        $currentDate = strtotime($fechaInicio);
        $endDate = strtotime($fechaFin);
        while($currentDate <= $endDate) {
            $mesStr = date('Y-m', $currentDate);
            $mesNum = intval(date('m', $currentDate));
            $labels[] = $nombresMeses[$mesNum - 1];
            $ventasPorPeriodo[] = isset($ventasIndex[$mesStr]) ? $ventasIndex[$mesStr] : 0;
            $comprasPorPeriodo[] = isset($comprasIndex[$mesStr]) ? $comprasIndex[$mesStr] : 0;
            $currentDate = strtotime('+1 month', $currentDate);
        }
    }
    
    // ========== TOP 5 PRODUCTOS - CON FILTRO POR CATEGORIA/EMPRESA ==========
    $paramsTopProductos = array($empCod, $fechaInicio, $fechaFin);
    $rsTopProductos = $obBD_con1->getArrayConsulta(132, $paramsTopProductos, $obBD_conexion);
    
    // ========== TOP 5 CLIENTES - CON FILTRO DE EMPRESA ==========
    $paramsTopClientes = array($empCod, $fechaInicio, $fechaFin);
    $rsTopClientes = $obBD_con1->getArrayConsulta(133, $paramsTopClientes, $obBD_conexion);
    
    // ========== TOTAL CLIENTES - CON FILTRO DE EMPRESA ==========
    $paramsClientesTotal = array($empCod);
    $rsClientesTotal = $obBD_con1->getRowConsulta(134, $paramsClientesTotal, $obBD_conexion);
    
    // ========== DOCUMENTOS ELECTRÓNICOS AUTORIZADOS - CON FILTRO DE FECHAS ==========
    // Facturas autorizadas (Tic_Sri = 01) con Vet_Sri no vacío
    $paramsFacturas = array($empCod, $fechaInicio, $fechaFin);
    $docFacturas = $obBD_con1->getRowConsulta(135, $paramsFacturas, $obBD_conexion);
    if (!$docFacturas || !isset($docFacturas['total'])) {
        $docFacturas = array('total' => 0);
    }
    
    // Notas de Crédito autorizadas (Tic_Sri = 04)
    $paramsNC = array($empCod, $fechaInicio, $fechaFin);
    $docNotasCredito = $obBD_con1->getRowConsulta(136, $paramsNC, $obBD_conexion);
    if (!$docNotasCredito || !isset($docNotasCredito['total'])) {
        $docNotasCredito = array('total' => 0);
    }
    
    // Retenciones autorizadas - de compras
    $paramsRet = array($empCod, $fechaInicio, $fechaFin);
    $docRetenciones = $obBD_con1->getRowConsulta(137, $paramsRet, $obBD_conexion);
    if (!$docRetenciones || !isset($docRetenciones['total'])) {
        $docRetenciones = array('total' => 0);
    }
    
    // Liquidación de compras (Tic_Sri = 03)
    $paramsLiq = array($empCod, $fechaInicio, $fechaFin);
    $docLiquidaciones = $obBD_con1->getRowConsulta(138, $paramsLiq, $obBD_conexion);
    if (!$docLiquidaciones || !isset($docLiquidaciones['total'])) {
        $docLiquidaciones = array('total' => 0);
    }
    
    // Guías de Remisión (Tic_Sri = 06)
    $paramsGuia = array($empCod, $fechaInicio, $fechaFin);
    $docGuias = $obBD_con1->getRowConsulta(139, $paramsGuia, $obBD_conexion);
    if (!$docGuias || !isset($docGuias['total'])) {
        $docGuias = array('total' => 0);
    }
    
    // Manejar valores nulos de forma segura
    $ventasTotal = isset($rsVentas['total']) ? floatval($rsVentas['total']) : 0;
    $ventasCantidad = isset($rsVentas['cantidad']) ? intval($rsVentas['cantidad']) : 0;
    $ventasSubtotal = isset($rsVentas['subtotal']) ? floatval($rsVentas['subtotal']) : 0;
    $ventasIva = isset($rsVentas['iva']) ? floatval($rsVentas['iva']) : 0;
    
    $comprasTotal = isset($rsCompras['total']) ? floatval($rsCompras['total']) : 0;
    $comprasCantidad = isset($rsCompras['cantidad']) ? intval($rsCompras['cantidad']) : 0;
    $comprasSubtotal = isset($rsCompras['subtotal']) ? floatval($rsCompras['subtotal']) : 0;
    $comprasIva = isset($rsCompras['iva']) ? floatval($rsCompras['iva']) : 0;
    
    $clientesNuevos = isset($rsClientes['nuevos']) ? intval($rsClientes['nuevos']) : 0;
    $clientesTotal = isset($rsClientesTotal['total']) ? intval($rsClientesTotal['total']) : 0;
    
    $response = array(
        'success' => true,
        'ventas' => array(
            'total' => $ventasTotal,
            'cantidad' => $ventasCantidad,
            'subtotal' => $ventasSubtotal,
            'iva' => $ventasIva
        ),
        'compras' => array(
            'total' => $comprasTotal,
            'cantidad' => $comprasCantidad,
            'subtotal' => $comprasSubtotal,
            'iva' => $comprasIva
        ),
        'clientesNuevos' => $clientesNuevos,
        'clientesTotal' => $clientesTotal,
        'grafico' => array(
            'labels' => $labels,
            'ventas' => $ventasPorPeriodo,
            'compras' => $comprasPorPeriodo
        ),
        'topProductos' => is_array($rsTopProductos) ? $rsTopProductos : array(),
        'topClientes' => is_array($rsTopClientes) ? $rsTopClientes : array(),
        'documentos' => array(
            'facturas' => isset($docFacturas['total']) ? intval($docFacturas['total']) : 0,
            'retenciones' => isset($docRetenciones['total']) ? intval($docRetenciones['total']) : 0,
            'notasCredito' => isset($docNotasCredito['total']) ? intval($docNotasCredito['total']) : 0,
            'liquidaciones' => isset($docLiquidaciones['total']) ? intval($docLiquidaciones['total']) : 0,
            'guias' => isset($docGuias['total']) ? intval($docGuias['total']) : 0
        ),
        'periodo' => array(
            'inicio' => $fechaInicio,
            'fin' => $fechaFin
        ),
        'debug' => array(
            'empCod' => $empCod,
            'sucCod' => $sucCod,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'baseDatos' => $baseDatos,
            'errores' => $errores,
            'rsVentasRaw' => $rsVentas,
            'rsComprasRaw' => $rsCompras
        )
    );
    
    echo json_encode($response);
    exit;
}
// ==================== FIN AJAX ====================


// Construir condición de perfiles (Par_Sql[0])
$cond_perfiles = '1=1'; // Valor por defecto
if (!empty($_SESSION['Ses_Lis_Per'])) {
    $perfiles = array();
    foreach ($_SESSION['Ses_Lis_Per'] as $item) {
        $perfiles[] = "perfiorgan.Per_Cod = " . intval($item);
    }
    if (!empty($perfiles)) {
        $cond_perfiles = implode(" OR ", $perfiles);
    }
}

// Construir condición adicional (Par_Sql[1]) — puedes usar otro filtro o también 1=1
$cond_extra = '1=1'; // Esto evita que se genere una condición vacía
// Crear arreglo con ambos parámetros
$parametros = array($cond_perfiles, $cond_extra);
// Consulta para obtener los procesos
$rs_procesos = $obBD_con1->consulta(sentencias_adm(118, $obBD_con1->parametros($parametros)),$obBD_conexion->conexion );
// Consulta el registro de la llave electrónica en vigencia
$llave = $obBD_con1->getArrayConsulta(119, $_SESSION['Ses_Emp_Cod'], $obBD_conexion);
// Consulta para obtener los procesos y asignar accesos directos
$accesosDir = $obBD_con1->consulta(sentencias_adm(120, $obBD_con1->parametros($parametros)),$obBD_conexion->conexion );
// Consulta registros de accesos directos
// $search = $obBD_con1->consulta(sentencias_adm(121, $_SESSION['Ses_Emp_Cod'], $_SESSION['Ses_Prv_Cod'],$obBD_conexion));
// $AccDirInsert = $obBD_con1->consulta(sentencias_adm(122,));
// consulta los documentos electronicos activos de la empresa - sucursal
$docElect = $obBD_con1->getArrayConsulta(123,  $_SESSION['Ses_Suc_Cod'] . '*' . $_SESSION['Ses_Prs_Cod'], $obBD_conexion);

// Obtiene el Tic_Cod y Pun_Cod de los documentos electrónicos
$ticCods = array();
$punCods = array();
if (is_array($docElect)) {
    foreach ($docElect as $row) {
        if (isset($row['Tic_Cod'])) {
            $ticCods[] = $row['Tic_Cod'];
        }
        if (isset($row['Pun_Cod'])) {
            $punCods[] = $row['Pun_Cod'];
        }
    }
}

// Muestra la cantidad de documentos electrónicos que tiene la empresa - sucursal
$docCount = $obBD_con1->getArrayConsulta(124,  array($ticCods, $punCods, $_SESSION['Ses_Prs_Cod'],$_SESSION['Ses_Suc_Cod']), $obBD_conexion);

// enrutamiento de boton registrar ventas
$hrefRuta = '';
$total = $obBD_con1->num_rows($rs_procesos);

if ($total > 0) {
    while ($fila = $obBD_con1->fetch_array($rs_procesos)) {
        $ruta = trim($fila['Rut_Des']) . trim($fila['Pcs_Nom']);

        // Si solo hay un registro, usa ese
        if ($total == 1) {
            $hrefRuta = $ruta;
        } else {
            // Si hay más de uno, busca el específico (por ejemplo, el que contiene "fac_alt_fac_ven_3.2.php")
            if (strpos($fila['Pcs_Nom'], 'fac_alt_fac_ven_3.2.php') !== false) {
                $hrefRuta = $ruta;
                break;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<!-- <html> -->
    <head>
        <title>Dashboard</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="shortcut icon" type="image/x-icon" href="../../imagenes/ingresar/favicon.png" />
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/tooltip.min.css" />
        <link rel="stylesheet" href="../../framework/plugins/fonts/font-awesome/font-awesome-4.4.0/css/font-awesome.min.css" />
        <link rel="stylesheet" href="../../skins/fonts/fontelo/fontello.css?x=0" />
        <link rel="stylesheet" href="../../skins/css/estilo-index.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
        <!-- text fonts -->
        <link rel="stylesheet" href="../../skins/css/ace-fonts.css" />
        <!-- exa styles -->
        <link id="pagestyle" href="../../skins/css/exa3.css" rel="stylesheet" />
        <script type="text/javascript">
            window.jQuery || document.write("<script src='../../skins/js/jquery.js'>" + "<" + "/script>");
        </script><!-- <![endif]-->
    </head>
    <!-- <body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" style="text-align:center; background: url('../img/Fondo-Exa-1.gif') no-repeat;background-size: cover;"> -->
    <body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" class="home-body">
        <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 text-center" style="color: white; display: flex; align-items: center; justify-content: center; margin-top: 35px; flex-wrap: wrap;">
            <!-- <img src="../../imagenes/ingresar/favicon.png" alt="" class="img-fluid" width="50" style="margin-top: -15px; margin-right: 12px;"> -->
            <h1 class="text-white mb-3" style="margin: 0; font-family: 'Cooper Black', 'Trebuchet MS', 'Arial', sans-serif; font-size: 40px; font-weight: 600; letter-spacing: 1px;">
                Dashboard
            </h1>
        </div>

        <style>
            .home-body {
                text-align: center;
                background: url('../../mascaras/model1/img/logo/backgroundHome.png') no-repeat;
                background-size: cover;
                background-position: center top;
                background-attachment: fixed;
                position: relative;
                overflow-y: auto;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                margin: 0;
            }
            @media (max-width: 768px) {
                .home-body {
                    background-position: center center;
                    background-attachment: scroll;
                    background-size: 100% 100% !important;
                }
            }
            .custom-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 350px;
                padding: 8px 0;
                background: #e3e3e3;
                color: #000000 !important;
                border: none;
                border-radius: 8px;
                font-size: 1.15rem;
                font-family: 'Segoe UI', 'Arial', sans-serif;
                font-weight: 600;
                box-shadow: 0 4px 16px rgba(78, 84, 200, 0.15);
                transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
                text-decoration: none;
                margin: 0 10px 10px 10px;
                max-width: 98vw;
            }
            .custom-btn:hover {
                /* background: linear-gradient(90deg, #2746a2 0%, #12188e 100%); */
                background: linear-gradient(90deg, rgb(81, 180, 237) 100%);
                transform: translateY(-2px) scale(1.03);
                box-shadow: 0 8px 24px rgba(78, 84, 200, 0.22);
                color: #fff !important;
                text-decoration: none;
            }
            .custom-btn i { margin-right: 8px; font-size: 1.2em; }
            @media (max-width: 900px) {
                .col-xl-12.text-center { flex-direction: column !important; align-items: center !important; }
                .col-xl-12.text-center h1 { font-size: 28px !important; margin-top: 10px; }
            }
            @media (max-width: 600px) {
                .row.menu-btns { flex-direction: column !important; align-items: center !important; gap: 0 !important; }
                .custom-btn { width: 90vw; min-width: 180px; max-width: 98vw; font-size: 1rem; margin: 0 0 14px 0; }
                .col-xl-12.text-center h1 { font-size: 20px !important; }
                .col-xl-12.text-center img { margin-right: 0 !important; }
            }

            @media (max-width: 991px) {
                .row.justify-center { flex-direction: column !important; align-items: center !important; gap: 24px !important; }
                .col-md-6.col-12 { justify-content: center !important; margin-bottom: 18px; max-width: 98vw !important; }
            }
            
            /* Estilos para el Dashboard */
            fieldset.scheduler-border {
                border: 2px solid rgba(255,255,255,0.3) !important;
                border-radius: 12px;
                padding: 0 20px 20px 20px;
                margin: 0 20px;
            }
            legend.scheduler-border {
                width: auto;
                padding: 8px 20px;
                border-bottom: none;
                background: rgba(0,0,0,0.3);
                border-radius: 8px;
                margin-left: 20px;
            }
            .dashboard-card {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                cursor: default;
            }
            .dashboard-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 40px rgba(0,0,0,0.25) !important;
            }
            #chartVentasCompras {
                min-height: 280px;
                max-height: 350px;
            }
            @media (max-width: 768px) {
                .dashboard-card {
                    min-width: 100% !important;
                    max-width: 100% !important;
                }
                #chartVentasCompras {
                    min-height: 220px;
                }
                /* Centrar secciones en móvil */
                fieldset.scheduler-border {
                    margin: 0 10px !important;
                    padding: 0 10px 15px 10px !important;
                }
                legend.scheduler-border {
                    margin-left: 10px !important;
                    margin-right: 10px !important;
                    text-align: center !important;
                    width: auto !important;
                    font-size: 1rem !important;
                    padding: 8px 15px !important;
                    background: rgba(0,0,0,0.3) !important;
                    border-radius: 8px !important;
                    display: inline-block !important;
                }
                .row.menu-btns {
                    margin-top: 15px !important;
                }
                .btn-periodo {
                    padding: 6px 10px !important;
                    font-size: 11px !important;
                }
                .input-fecha {
                    width: 120px !important;
                    font-size: 11px !important;
                }
                .chart-container, .table-container {
                    min-width: 100% !important;
                    max-width: 100% !important;
                    padding: 12px !important;
                }
                .chart-container h4, .table-container h4 {
                    font-size: 13px !important;
                }
            }
            @media (max-width: 480px) {
                legend.scheduler-border {
                    font-size: 0.85rem !important;
                    padding: 6px 12px !important;
                    margin-left: 5px !important;
                    margin-right: 5px !important;
                }
                .dashboard-card {
                    padding: 12px !important;
                }
                .card-value span {
                    font-size: 18px !important;
                }
                .card-badge {
                    font-size: 10px !important;
                    padding: 3px 6px !important;
                }
                .btn-periodo {
                    padding: 5px 8px !important;
                    font-size: 10px !important;
                    margin: 2px !important;
                }
                .input-fecha {
                    width: 100px !important;
                    padding: 5px !important;
                    font-size: 10px !important;
                }
                .btn-filtrar, .btn-refresh {
                    padding: 6px 10px !important;
                    font-size: 11px !important;
                }
            }
        </style>

        <!-- ==================== DASHBOARD INTERACTIVO DE VENTAS Y COMPRAS ==================== -->
        <fieldset class="scheduler-border dashboard-fieldset" style="margin-top: 20px;">
            <legend class="scheduler-border" style="color: white; font-size: 1.2rem; font-weight: 600; margin-bottom: 10px; text-align: left;">
                <i class="fa fa-line-chart"></i> Dashboard Empresarial
                <span id="dashboard-loading" style="display: none; margin-left: 10px;">
                    <i class="fa fa-spinner fa-spin"></i>
                </span>
            </legend>
            
            <!-- Filtros de Período -->
            <div class="container-fluid">
                <div class="row" style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: center;">
                        <label style="color: #fff; font-weight: 600; margin-right: 10px;"><i class="fa fa-calendar"></i> Período:</label>
                        <button class="btn-periodo" data-periodo="hoy" onclick="cambiarPeriodo('hoy', this)">Hoy</button>
                        <button class="btn-periodo" data-periodo="semana" onclick="cambiarPeriodo('semana', this)">Semana</button>
                        <button class="btn-periodo" data-periodo="mes" onclick="cambiarPeriodo('mes', this)">Mes</button>
                        <button class="btn-periodo" data-periodo="trimestre" onclick="cambiarPeriodo('trimestre', this)">Trimestre</button>
                        <button class="btn-periodo active" data-periodo="anio" onclick="cambiarPeriodo('anio', this)">Año</button>
                        <span style="color: #fff; margin: 0 10px;">|</span>
                        <input type="date" id="fechaInicio" class="input-fecha" value="<?php echo date('Y-01-01'); ?>">
                        <span style="color: #fff;">a</span>
                        <input type="date" id="fechaFin" class="input-fecha" value="<?php echo date('Y-12-31'); ?>">
                        <button class="btn-filtrar" onclick="cambiarPeriodo('personalizado', null)"><i class="fa fa-search"></i> Filtrar</button>
                        <button class="btn-refresh" onclick="actualizarDashboard()" title="Actualizar"><i class="fa fa-refresh"></i></button>
                </div>
            </div>
                
                <!-- Tarjetas Principales -->
                <div class="row" style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
                    <!-- Tarjeta de Ventas -->
                    <div class="dashboard-card ventas-card" id="card-ventas">
                        <div class="card-decoration"></div>
                        <div class="card-decoration-2"></div>
                        <div class="card-content">
                            <div class="card-header-row">
                                <div class="card-icon ventas-icon">
                                    <i class="fa fa-shopping-cart"></i>
                                </div>
                                <div class="card-title-block">
                                    <p class="card-title">VENTAS</p>
                                    <p class="card-subtitle" id="ventas-periodo">Cargando...</p>
                                </div>
                            </div>
                            <div class="card-value">
                                <span id="ventas-total">$0,00</span>
                            </div>
                            <div class="card-details">
                                <span class="card-badge"><i class="fa fa-file-text-o"></i> <span id="ventas-cantidad">0</span> facturas</span>
                                <span class="card-badge"><i class="fa fa-percent"></i> IVA: $<span id="ventas-iva">0,00</span></span>
                            </div>
            </div>
        </div>

                    <!-- Tarjeta de Compras -->
                    <div class="dashboard-card compras-card" id="card-compras">
                        <div class="card-decoration"></div>
                        <div class="card-decoration-2"></div>
                        <div class="card-content">
                            <div class="card-header-row">
                                <div class="card-icon compras-icon">
                                    <i class="fa fa-truck"></i>
                                </div>
                                <div class="card-title-block">
                                    <p class="card-title">COMPRAS</p>
                                    <p class="card-subtitle" id="compras-periodo">Cargando...</p>
                                </div>
                            </div>
                            <div class="card-value">
                                <span id="compras-total">$0,00</span>
                            </div>
                            <div class="card-details">
                                <span class="card-badge"><i class="fa fa-file-text-o"></i> <span id="compras-cantidad">0</span> documentos</span>
                                <span class="card-badge"><i class="fa fa-percent"></i> IVA: $<span id="compras-iva">0,00</span></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta de Balance -->
                    <div class="dashboard-card balance-card" id="card-balance">
                        <div class="card-decoration"></div>
                        <div class="card-decoration-2"></div>
                        <div class="card-content">
                            <div class="card-header-row">
                                <div class="card-icon balance-icon">
                                    <i class="fa fa-balance-scale"></i>
                                </div>
                                <div class="card-title-block">
                                    <p class="card-title">BALANCE</p>
                                    <p class="card-subtitle" id="balance-tipo">Calculando...</p>
                                </div>
                            </div>
                            <div class="card-value">
                                <span id="balance-total">$0,00</span>
                            </div>
                            <div class="card-details">
                                <span class="card-badge" id="balance-indicador"><i class="fa fa-arrow-up"></i> Positivo</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta de Clientes -->
                    <div class="dashboard-card clientes-card" id="card-clientes">
                        <div class="card-decoration"></div>
                        <div class="card-decoration-2"></div>
                        <div class="card-content">
                            <div class="card-header-row">
                                <div class="card-icon clientes-icon">
                                    <i class="fa fa-users"></i>
                                </div>
                                <div class="card-title-block">
                                    <p class="card-title">CLIENTES</p>
                                    <p class="card-subtitle" id="clientes-periodo">Total Registrados</p>
                                </div>
                            </div>
                            <div class="card-value">
                                <span id="clientes-cantidad">0</span>
                            </div>
                            <div class="card-details">
                                <span class="card-badge"><i class="fa fa-user-plus"></i> Activos</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficos y Tablas -->
                <div class="row" style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-top: 10px;">
                    <!-- Gráfico Principal -->
                    <div class="chart-container" style="flex: 2; min-width: 400px; max-width: 700px; overflow: hidden; padding: 20px 25px 30px 25px;">
                        <div class="chart-header">
                            <h4><i class="fa fa-bar-chart" style="color: #1e88e5;"></i> Ventas vs Compras</h4>
                            <div class="chart-type-toggle">
                                <button class="chart-type-btn active" onclick="cambiarTipoGrafico('bar', this)" title="Barras"><i class="fa fa-bar-chart"></i></button>
                                <button class="chart-type-btn" onclick="cambiarTipoGrafico('line', this)" title="Líneas"><i class="fa fa-line-chart"></i></button>
                            </div>
                        </div>
                        <div style="position: relative; height: 280px; width: 100%; padding-bottom: 10px;">
                            <canvas id="chartVentasCompras"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico de Distribución -->
                    <div class="chart-container" style="flex: 1; min-width: 220px; max-width: 300px; overflow: hidden; padding: 20px;">
                        <h4 style="text-align: center; margin-bottom: 15px; font-size: 14px;"><i class="fa fa-pie-chart" style="color: #43a047;"></i> Distribución</h4>
                        <div style="position: relative; height: 200px; width: 100%;">
                            <canvas id="chartDistribucion"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Top Productos y Clientes -->
                <div class="row" style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-top: 15px;">
                    <!-- Top Productos -->
                    <div class="table-container" style="flex: 1; min-width: 280px; max-width: 500px;">
                        <h4 style="font-size: 14px;"><i class="fa fa-trophy" style="color: #ff9800;"></i> Top 5 Productos</h4>
                        <table class="dashboard-table" id="tabla-productos">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Cant.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-productos">
                                <tr><td colspan="4" style="text-align: center; padding: 15px;"><i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Top Clientes -->
                    <div class="table-container" style="flex: 1; min-width: 280px; max-width: 500px;">
                        <h4 style="font-size: 14px;"><i class="fa fa-star" style="color: #1e88e5;"></i> Top 5 Clientes</h4>
                        <table class="dashboard-table" id="tabla-clientes">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th>Fact.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-clientes">
                                <tr><td colspan="4" style="text-align: center; padding: 15px;"><i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </fieldset>
        
        <!-- Estilos del Dashboard -->
        <style>
            .dashboard-fieldset {
                background: rgba(0,0,0,0.55);
            }
            .btn-periodo {
                padding: 8px 16px;
                border: none;
                background: rgba(255,255,255,0.2);
                color: #fff;
                border-radius: 20px;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.3s ease;
                font-size: 13px;
            }
            .btn-periodo:hover, .btn-periodo.active {
                background: #fff;
                color: #333;
                transform: scale(1.05);
            }
            .input-fecha {
                padding: 8px 12px;
                border: none;
                border-radius: 8px;
                font-size: 13px;
                background: rgba(255,255,255,0.9);
            }
            .btn-filtrar {
                padding: 8px 16px;
                border: none;
                background: #43a047;
                color: #fff;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            .btn-filtrar:hover {
                background: #2e7d32;
                transform: scale(1.05);
            }
            .btn-refresh {
                padding: 8px 12px;
                border: none;
                background: #1e88e5;
                color: #fff;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .btn-refresh:hover {
                background: #1565c0;
            }
            .dashboard-card {
                border-radius: 14px;
                padding: 18px;
                min-width: 200px;
                max-width: 240px;
                flex: 1;
                position: relative;
                overflow: hidden;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            .dashboard-card:hover {
                transform: translateY(-5px);
            }
            .ventas-card { background: linear-gradient(135deg, #1e88e5 0%, #0d47a1 100%); box-shadow: 0 8px 32px rgba(13, 71, 161, 0.3); }
            .compras-card { background: linear-gradient(135deg, #e53935 0%, #b71c1c 100%); box-shadow: 0 8px 32px rgba(183, 28, 28, 0.3); }
            .balance-card { background: linear-gradient(135deg, #43a047 0%, #1b5e20 100%); box-shadow: 0 8px 32px rgba(27, 94, 32, 0.3); }
            .balance-card.negativo { background: linear-gradient(135deg, #ff9800 0%, #e65100 100%); box-shadow: 0 8px 32px rgba(230, 81, 0, 0.3); }
            .clientes-card { background: linear-gradient(135deg, #7b1fa2 0%, #4a148c 100%); box-shadow: 0 8px 32px rgba(74, 20, 140, 0.3); }
            .card-decoration { position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; }
            .card-decoration-2 { position: absolute; bottom: -30px; left: -30px; width: 80px; height: 80px; background: rgba(255,255,255,0.08); border-radius: 50%; }
            .card-content { position: relative; z-index: 1; }
            .card-header-row { display: flex; align-items: center; margin-bottom: 16px; }
            .card-icon { background: rgba(255,255,255,0.2); border-radius: 10px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; margin-right: 12px; }
            .card-icon i { font-size: 20px; color: #fff; }
            .card-title { margin: 0; color: rgba(255,255,255,0.9); font-size: 11px; font-weight: 600; letter-spacing: 0.5px; }
            .card-subtitle { margin: 0; color: rgba(255,255,255,0.6); font-size: 9px; }
            .card-value { margin-bottom: 8px; }
            .card-value span { font-size: 22px; font-weight: 700; color: #fff; }
            .card-details { display: flex; gap: 8px; flex-wrap: wrap; }
            .card-badge { background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 20px; font-size: 11px; color: #fff; }
            .chart-container, .table-container {
                background: rgba(255,255,255,0.95);
                border-radius: 16px;
                padding: 20px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }
            .chart-container h4, .table-container h4 {
                margin: 0 0 15px 0;
                color: #333;
                font-weight: 600;
                font-size: 16px;
            }
            .chart-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }
            .chart-header h4 { margin: 0; }
            .chart-type-toggle { display: flex; gap: 5px; }
            .chart-type-btn {
                padding: 6px 12px;
                border: 1px solid #ddd;
                background: #fff;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .chart-type-btn:hover, .chart-type-btn.active {
                background: #1e88e5;
                color: #fff;
                border-color: #1e88e5;
            }
            .dashboard-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }
            .dashboard-table th {
                background: #f5f5f5;
                padding: 10px 8px;
                text-align: left;
                font-weight: 600;
                color: #555;
                border-bottom: 2px solid #e0e0e0;
            }
            .dashboard-table td {
                padding: 10px 8px;
                border-bottom: 1px solid #eee;
            }
            .dashboard-table tbody tr:hover {
                background: #f9f9f9;
            }
            .rank-badge {
                display: inline-block;
                width: 24px;
                height: 24px;
                line-height: 24px;
                text-align: center;
                border-radius: 50%;
                font-weight: bold;
                font-size: 12px;
            }
            .rank-1 { background: #ffd700; color: #333; }
            .rank-2 { background: #c0c0c0; color: #333; }
            .rank-3 { background: #cd7f32; color: #fff; }
            .rank-other { background: #e0e0e0; color: #666; }
            @media (max-width: 768px) {
                .dashboard-card { min-width: 100% !important; max-width: 100% !important; }
                .chart-container, .table-container { min-width: 100% !important; }
            }
        </style>
        
        <!-- Chart.js CDN -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
        <script src="../js/dashboardHome.js?v=1"></script>
        <!-- ==================== FIN DASHBOARD ==================== -->

        
        <!-- ==================== INFORMACIÓN DEL USUARIO ==================== -->
        <fieldset class="scheduler-border dashboard-fieldset" style="margin-top: 20px; margin-bottom: 30px;">
            <legend class="scheduler-border" style="color: white; font-size: 1.2rem; font-weight: 600; margin-bottom: 10px; text-align: left;">
                <i class="fa fa-user-circle"></i> Información del Usuario
            </legend>
            <div class="container-fluid">
                <div class="row" style="display: flex; justify-content: center; align-items: stretch; gap: 32px; flex-wrap: wrap; padding: 20px 0;">
                    <!-- Tarjeta de Información de la Llave Electrónica -->
                    <div style="background: #e6e6e6; border-radius: 18px; box-shadow: 0 4px 18px rgba(39,70,162,0.10); padding: 32px 28px; display: flex; align-items: center; min-width: 280px; max-width: 420px; flex: 1;">
                        <div>
                            <p style="margin: 0 0 8px 0; font-weight: bold; color: #a02525; font-size: 16px;">Información de la Llave Electrónica</p>
                            <div style="font-size: 17px; color: #222; margin-bottom: 12px;">
                                <div style="margin-bottom: 10px; display: flex; align-items: center; flex-direction: column; align-items: flex-start;">
                                    <strong style="min-width: 60px; text-align: left; margin-right: 8px; text-decoration: underline;">Llave:</strong>
                                    <span id="llaveRut" style="letter-spacing:1px; font-size: 11px;">
                                        <?php echo isset($llave[0]['Lla_Rut']) ? htmlspecialchars($llave[0]['Lla_Rut']) : 'No disponible'; ?>
                                    </span>
                                </div>
                                <div style="text-align: left;">
                                    <strong style="text-decoration: underline;">Caducidad:</strong>
                                    <span id="llaveCad">
                                        <?php echo isset($llave[0]['Lla_Cad']) ? htmlspecialchars($llave[0]['Lla_Cad']) : 'No disponible'; ?>
                                    </span>
                                </div>
                                <?php
                                if (isset($llave[0]['Lla_Cad']) && !empty($llave[0]['Lla_Cad'])) {
                                    $fechaCaducidad = DateTime::createFromFormat('Y-m-d', $llave[0]['Lla_Cad']);
                                    $fechaHoy = new DateTime();
                                    if ($fechaCaducidad !== false) {
                                        $diasRestantes = $fechaHoy->diff($fechaCaducidad)->format('%r%a');
                                        echo '<div style="margin-top: 6px; text-align: left;">';
                                        if ($diasRestantes >= 0) {
                                            echo '<span style="color:#222; font-weight:bold; text-decoration: underline;">Días restantes:</span> <span style="color:#27ae60; font-weight:bold;"> ' . $diasRestantes . '</span>';
                                        } else {
                                            echo '<span style="color:#c0392b; font-weight:bold; text-align: center; text-decoration: underline;"> * Su firma ha expirado * </span>';
                                        }
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta Informativa de Documentos Electrónicos Autorizados - INTERACTIVA -->
                    <div style="background: #e6e6e6; border-radius: 18px; box-shadow: 0 4px 18px rgba(39,70,162,0.10); padding: 28px 24px; display: flex; align-items: center; min-width: 320px; max-width: 480px; flex: 1;">
                        <div style="width:100%;">
                            <p style="margin: 0 0 4px 0; font-weight: bold; color: #a02525; font-size: 16px;">
                                <i class="fa fa-file-text-o"></i> Docs. Electrónicos Autorizados
                            </p>
                            <p id="docs-periodo-label" style="margin: 0 0 12px 0; font-size: 13px; color: #a02525; font-weight: 600;"></p>
                            <table style="width:100%; font-size:14px; color:#222; border-collapse:collapse;" id="tabla-docs-autorizados">
                                <thead>
                                    <tr style="background:#d0d0d0;">
                                        <th style="padding:8px 10px; text-align:left; border-bottom: 2px solid #999;">Tipo de Documento</th>
                                        <th style="padding:8px 10px; text-align:right; border-bottom: 2px solid #999;">N° Docs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom: 1px solid #ccc;">
                                        <td style="padding:6px 10px; text-align:left;"><i class="fa fa-file-text" style="color:#1e88e5;"></i> Facturas</td>
                                        <td style="padding:6px 10px; text-align:right; font-weight:600; color:#1e88e5;" id="doc-facturas">0</td>
                                        </tr>
                                    <tr style="border-bottom: 1px solid #ccc;">
                                        <td style="padding:6px 10px; text-align:left;"><i class="fa fa-file-o" style="color:#43a047;"></i> Retenciones</td>
                                        <td style="padding:6px 10px; text-align:right; font-weight:600; color:#43a047;" id="doc-retenciones">0</td>
                                        </tr>
                                    <tr style="border-bottom: 1px solid #ccc;">
                                        <td style="padding:6px 10px; text-align:left;"><i class="fa fa-file-excel-o" style="color:#e53935;"></i> Notas de Crédito</td>
                                        <td style="padding:6px 10px; text-align:right; font-weight:600; color:#e53935;" id="doc-notas-credito">0</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #ccc;">
                                        <td style="padding:6px 10px; text-align:left;"><i class="fa fa-shopping-cart" style="color:#ff9800;"></i> Liquidación Compras</td>
                                        <td style="padding:6px 10px; text-align:right; font-weight:600; color:#ff9800;" id="doc-liquidaciones">0</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #ccc;">
                                        <td style="padding:6px 10px; text-align:left;"><i class="fa fa-truck" style="color:#7b1fa2;"></i> Guías de Remisión</td>
                                        <td style="padding:6px 10px; text-align:right; font-weight:600; color:#7b1fa2;" id="doc-guias">0</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#333; color:#fff;">
                                        <td style="padding:10px; font-weight:bold; text-align:left;"><i class="fa fa-check-circle"></i> TOTAL AUTORIZADOS</td>
                                        <td style="padding:10px; text-align:right; font-weight:bold; font-size:16px;" id="doc-total">0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                        </div>
        </fieldset>
        <!-- ==================== FIN INFORMACIÓN DEL USUARIO ==================== -->
    </body>
</html>
