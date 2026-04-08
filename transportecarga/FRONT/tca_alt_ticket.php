<?php

/**
 * @abstract Permite realizar el registro de tickets de cantera
 * @author Sistema
 * @version 1.0
 * Fecha de creacion  2024-01-01
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tca_log_ticket.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_ticket($Ses_Dat_Dis);
/** 
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_ticket;
/**
 * Evita el reenvio 
 */
$thisPost = new Post_Block;

// Seccion para cargar datos en el Jqgrid referente a los clientes
if (isset($clienteAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(1, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(1, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

// Seccion para obtener vehiculo por cliente
if (isset($obtenerVehiculoAjax)) {
    $Cli_Cod = isset($_GET['Cli_Cod']) ? intval($_GET['Cli_Cod']) : 0;
    if ($Cli_Cod > 0) {
        $data = array('Cli_Cod' => $Cli_Cod, 'Emp_Cod' => $Ses_Emp_Cod);
        $response = $obBD_con1->getRowConsulta(4, $data, $obBD_conexion);
        if (!empty($response)) {
            utf8_encode_deep($response);
            echo json_encode(array('success' => true, 'vehiculo' => $response));
        } else {
            echo json_encode(array('success' => false, 'message' => 'No se encontró vehículo para este cliente'));
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'Código de cliente inválido'));
    }
    exit();
}

// Seccion para obtener saldo de anticipos del cliente y total de tickets
if (isset($obtenerAnticipoClienteAjax)) {
    $Cli_Cod = isset($_GET['Cli_Cod']) ? intval($_GET['Cli_Cod']) : 0;
    $saldo = 0;
    $total_tickets = 0;
    if ($Cli_Cod > 0) {
        try {
            $row = $obBD_con1->getRowConsulta(5, array('Cli_Cod' => $Cli_Cod), $obBD_conexion);
            $saldo = isset($row['saldo_anticipo']) ? floatval($row['saldo_anticipo']) : 0;
            $rowTickets = $obBD_con1->getRowConsulta(6, array('Cli_Cod' => $Cli_Cod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
            $total_tickets = isset($rowTickets['total_tickets']) ? floatval($rowTickets['total_tickets']) : 0;
        } catch (Exception $e) {
            $saldo = 0;
            $total_tickets = 0;
        }
    }
    $saldo_final = $saldo - $total_tickets;
    echo json_encode(array('success' => true, 'saldo_anticipo' => $saldo, 'total_tickets' => $total_tickets, 'saldo' => $saldo_final));
    exit();
}

// Seccion para cargar datos en el Jqgrid referente a los vehiculos
if (isset($vehiculoAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(2, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(2, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

// Seccion para cargar datos en el Jqgrid referente a los productos
if (isset($productoAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $data["Suc_Cod"] = $Ses_Suc_Cod;
    $contar = $obBD_con1->getRowConsulta(3, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(3, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

// Seccion para obtener detalle de ticket
if (isset($cargarDetalleAjax)) {
    $Tck_Cod = isset($_GET['Tck_Cod']) ? intval($_GET['Tck_Cod']) : 0;
    if ($Tck_Cod > 0) {
        $response = $obBD_con1->getArrayConsulta(30, array('Tck_Cod' => $Tck_Cod), $obBD_conexion);
    } else {
        $response = array();
    }
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

// Seccion para obtener siguiente numero de ticket
if (isset($getSiguienteNumeroAjax)) {
    $siguienteNumero = $obBD_con1->getRowConsulta(40, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    $numero = isset($siguienteNumero['siguiente']) ? intval($siguienteNumero['siguiente']) : 1;
    $response = array('success' => true, 'numero' => $numero);
    echo json_encode($response);
    exit();
}

// Seccion para cargar datos del ticket para editar
if (isset($cargarTicketAjax)) {
    $Tck_Cod = isset($_GET['Tck_Cod']) ? intval($_GET['Tck_Cod']) : 0;
    if ($Tck_Cod > 0) {
        // Obtener datos del ticket
        $ticket = $obBD_con1->getRowConsulta(31, array('Tck_Cod' => $Tck_Cod), $obBD_conexion);
        // Obtener detalles del ticket
        $detalles = $obBD_con1->getArrayConsulta(30, array('Tck_Cod' => $Tck_Cod), $obBD_conexion);
        if (!empty($ticket)) {
            // Formatear fecha para el input datetime-local
            $fecha_formateada = '';
            if (!empty($ticket['Tck_Fec'])) {
                $fecha_obj = date_create($ticket['Tck_Fec']);
                if ($fecha_obj) {
                    $fecha_formateada = date_format($fecha_obj, 'Y-m-d\TH:i');
                }
            }
            $response = array(
                'success' => true,
                'ticket' => array(
                    'Tck_Cod' => $ticket['Tck_Cod'],
                    'Tck_Num' => isset($ticket['Tck_Num']) ? $ticket['Tck_Num'] : '',
                    'Tck_Fec' => $fecha_formateada,
                    'Prv_Cod' => isset($ticket['Prv_Cod']) ? $ticket['Prv_Cod'] : '',
                    'Veh_Cod' => isset($ticket['Veh_Cod']) ? $ticket['Veh_Cod'] : '',
                    'Tck_Val' => isset($ticket['Tck_Val']) ? $ticket['Tck_Val'] : '0.0000',
                    'Tck_IvA' => isset($ticket['Tck_IvA']) ? $ticket['Tck_IvA'] : '0.0000',
                    'Tck_Tot' => isset($ticket['Tck_Tot']) ? $ticket['Tck_Tot'] : '0.0000',
                    'Cli_Cod' => isset($ticket['Cli_Cod']) ? $ticket['Cli_Cod'] : '',
                    'cliente_nombre' => isset($ticket['cliente_nombre']) ? $ticket['cliente_nombre'] : '',
                    'Prs_Ced' => isset($ticket['Prs_Ced']) ? $ticket['Prs_Ced'] : '',
                    'Prs_Dir' => isset($ticket['Prs_Dir']) ? $ticket['Prs_Dir'] : '',
                    'Prs_Cor' => isset($ticket['Prs_Cor']) ? $ticket['Prs_Cor'] : '',
                    'Veh_Pla' => isset($ticket['Veh_Pla']) ? $ticket['Veh_Pla'] : '',
                    'Veh_Cap' => isset($ticket['Veh_Cap']) ? $ticket['Veh_Cap'] : '',
                    'Veh_Tit' => isset($ticket['Veh_Tit']) ? $ticket['Veh_Tit'] : '',
                    'Veh_Tip' => isset($ticket['Veh_Tit']) ? $ticket['Veh_Tit'] : '',
                    'Tck_Pag' => isset($ticket['Tck_Pag']) ? $ticket['Tck_Pag'] : 'E'
                ),
                'detalles' => $detalles
            );
        } else {
            $response = array('success' => false, 'message' => 'No se encontraron datos del ticket');
        }
    } else {
        $response = array('success' => false, 'message' => 'Código de ticket inválido');
    }
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

// Seccion para imprimir ticket
if (isset($imprimirTicketAjax)) {
    $Tck_Cod = isset($_GET['Tck_Cod']) ? intval($_GET['Tck_Cod']) : 0;
    $formato = isset($_GET['formato']) ? $_GET['formato'] : 'escpos'; // Por defecto ESC/POS, pero se puede cambiar a 'html' si se necesita

    if ($Tck_Cod > 0) {
        // Obtener datos del ticket
        $ticket = $obBD_con1->getRowConsulta(31, array('Tck_Cod' => $Tck_Cod), $obBD_conexion);
        // Obtener detalles del ticket
        $detalles = $obBD_con1->getArrayConsulta(30, array('Tck_Cod' => $Tck_Cod), $obBD_conexion);
        // Formatear fecha
        $fecha_ticket = '';
        if (!empty($ticket['Tck_Fec'])) {
            $fecha_obj = date_create($ticket['Tck_Fec']);
            if ($fecha_obj) {
                $fecha_ticket = date_format($fecha_obj, 'd/m/Y H:i');
            }
        }
        // Función para convertir código de tipo de vehículo a descripción
        $veh_tip_codigo = isset($ticket['Veh_Tit']) ? $ticket['Veh_Tit'] : '';
        $veh_tip_desc = '';
        $tipos_vehiculo = array(
            'V' => 'Volqueta Sencilla',
            'VM' => 'Volqueta Mula',
            'VB' => 'Volqueta Bañera',
            'D' => 'TIPO DUMPER',
            'B' => 'Bus',
            'C' => 'CAMION',
            'T' => 'Tractor',
            'M' => 'Moto',
            'O' => 'Otro'
        );
        if (isset($tipos_vehiculo[$veh_tip_codigo])) {
            $veh_tip_desc = $tipos_vehiculo[$veh_tip_codigo];
        } else {
            $veh_tip_desc = $veh_tip_codigo;
        }
        // Obtener nombre de la empresa desde la sesión
        $empresa_nombre = isset($Ses_Emp_Nom) ? $Ses_Emp_Nom : (isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : '');

        if ($formato === 'escpos') {
            // Generar ticket en formato ESC/POS
            require_once(__DIR__ . '/tca_pri_ticket_escpos.php');

            $datos_escpos = array(
                'Tck_Num' => isset($ticket['Tck_Num']) ? $ticket['Tck_Num'] : '',
                'Tck_Fec' => $fecha_ticket,
                'Emp_Nom' => $empresa_nombre,
                'cliente_nombre' => isset($ticket['cliente_nombre']) ? $ticket['cliente_nombre'] : '',
                'Prs_Ced' => isset($ticket['Prs_Ced']) ? $ticket['Prs_Ced'] : '',
                'Prs_Dir' => isset($ticket['Prs_Dir']) ? $ticket['Prs_Dir'] : '',
                'Prs_Cor' => isset($ticket['Prs_Cor']) ? $ticket['Prs_Cor'] : '',
                'Veh_Pla' => isset($ticket['Veh_Pla']) ? $ticket['Veh_Pla'] : '',
                'Veh_Cap' => isset($ticket['Veh_Cap']) ? $ticket['Veh_Cap'] : '',
                'Veh_Tip' => $veh_tip_desc,
                'Tck_Val' => isset($ticket['Tck_Val']) ? floatval($ticket['Tck_Val']) : 0,
                'Tck_IvA' => isset($ticket['Tck_IvA']) ? floatval($ticket['Tck_IvA']) : 0,
                'Tck_Tot' => isset($ticket['Tck_Tot']) ? floatval($ticket['Tck_Tot']) : 0,
                'detalles' => $detalles
            );

            $accion = isset($_GET['accion']) ? $_GET['accion'] : 'preview'; // 'preview' o 'download'

            if ($accion === 'download') {
                // Descargar archivo HTML del ticket
                $html_content = generarTicketESCPOSMultiples($datos_escpos, 3);
                $html_completo = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ticket ' . $Tck_Cod . '</title>
    <style>
        body {
            background-color: white;
        }
    </style>
</head>
<body>
    ' . $html_content . '
</body>
</html>';
                header('Content-Type: text/html; charset=UTF-8');
                header('Content-Disposition: attachment; filename="ticket_' . $Tck_Cod . '.html"');
                header('Content-Length: ' . strlen($html_completo));
                echo $html_completo;
                exit();
            } else {
                // Mostrar directamente para imprimir usando la plantilla HTML
                $html_ticket = generarTicketESCPOSMultiples($datos_escpos, 3);

                // Generar HTML completo con estilos para impresión directa
                $html_preview = '<!DOCTYPE html><html>
<head>
    <meta charset="UTF-8">
    <title>Ticket ' . $Tck_Cod . '</title>
    <style>
        @media print {
            body { 
                margin: 0; 
                padding: 0; 
                background: white;
            }
            .ticket-container {
                page-break-inside: avoid;
            }
        }
        body {
            margin: 0;
            padding: 10px;
            background: white;
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</head>
<body>
    ' . $html_ticket . '
</body>
</html>';

                echo $html_preview;
                exit();
            }
        } else {
            // Generar HTML (código original)
            // Construir tabla de detalles
            $detalle_html = '';
            foreach ($detalles as $det) {
                $detalle_html .= '<tr>';
                $detalle_html .= '<td>' . htmlspecialchars($det['Pro_Des'] ? $det['Pro_Des'] : '') . '</td>';
                $detalle_html .= '<td>' . ''/*htmlspecialchars($det['Dtk_Det'] ? $det['Dtk_Det'] : '') */ . '</td>';
                $detalle_html .= '<td style="text-align: right;">' . number_format($det['Dtk_Can'], 4) . '</td>';
                $detalle_html .= '<td style="text-align: right;">' . number_format($det['Dtk_Pru'], 4) . '</td>';
                $detalle_html .= '<td style="text-align: right;">' . number_format($det['Dtk_Tot'], 4) . '</td>';
                $detalle_html .= '</tr>';
            }

            // Preparar array de reemplazo
            $tabla = array(
                '{Tck_Num}' => isset($ticket['Tck_Num']) ? $ticket['Tck_Num'] : '',
                '{Tck_Fec}' => $fecha_ticket,
                '{Emp_Nom}' => $empresa_nombre,
                '{cliente_nombre}' => isset($ticket['cliente_nombre']) ? $ticket['cliente_nombre'] : '',
                '{Prs_Ced}' => isset($ticket['Prs_Ced']) ? $ticket['Prs_Ced'] : '',
                '{Prs_Dir}' => isset($ticket['Prs_Dir']) ? $ticket['Prs_Dir'] : '',
                '{Prs_Cor}' => isset($ticket['Prs_Cor']) ? $ticket['Prs_Cor'] : '',
                '{Veh_Pla}' => isset($ticket['Veh_Pla']) ? $ticket['Veh_Pla'] : '',
                '{Veh_Cap}' => isset($ticket['Veh_Cap']) ? $ticket['Veh_Cap'] : '',
                '{Veh_Tip}' => $veh_tip_desc,
                '{Tck_Val}' => isset($ticket['Tck_Val']) ? number_format($ticket['Tck_Val'], 4) : '0.0000',
                '{Tck_IvA}' => isset($ticket['Tck_IvA']) ? number_format($ticket['Tck_IvA'], 4) : '0.0000',
                '{Tck_Tot}' => isset($ticket['Tck_Tot']) ? number_format($ticket['Tck_Tot'], 4) : '0.0000',
                '{detalle_ticket}' => $detalle_html
            );
            // Generar HTML usando reporteHtml (función global de almacenados_standar.php)
            $html_ticket = reporteHtml($tabla, __DIR__ . '/tca_pri_ticket.html');
            // Generar tres copias del ticket para impresora térmica
            // La primera copia es para administración, la segunda para el cliente, la tercera para archivo
            $separador = '<div style="border-top: 2px dashed #000; margin: 20px 0; padding: 10px 0; text-align: center;">------------------------ CORTE AQUI ------------------------</div>';
            $html = $html_ticket . $separador . $html_ticket . $separador . $html_ticket;

            $response = array('success' => true, 'html' => $html);
            echo json_encode($response);
        }
    } else {
        $response = array('success' => false, 'message' => 'Código de ticket inválido');
        echo json_encode($response);
    }
    exit();
}

// Seccion para guardar vehiculo
if (isset($saveVehiculo)) {
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_conexionIns = new Class_Log_Conexion_ticket($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_ticket;
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);

    try {
        $data = filter_input_array(INPUT_POST);
        // Validar campos requeridos
        if (empty($data['Veh_Pla'])) {
            throw new Exception("La placa del vehículo es requerida");
        }
        if (empty($data['Cli_Cod']) || intval($data['Cli_Cod']) <= 0) {
            throw new Exception("Debe seleccionar un cliente");
        }
        // Verificar si la placa ya existe
        $vehiculo_existe = $obBD_con1->getArrayConsulta(2, array(
            'Emp_Cod' => $Ses_Emp_Cod,
            'search' => $data['Veh_Pla'],
            'op_opciones' => 'd',
            'limits' => 'LIMIT 1'
        ), $obBD_conexion);

        if (!empty($vehiculo_existe) && count($vehiculo_existe) > 0) {
            throw new Exception("Ya existe un vehículo con la placa " . $data['Veh_Pla']);
        }
        // Insertar vehículo
        $obBD_conIns->operacionobBD(70, array(
            'Emp_Cod' => $Ses_Emp_Cod,
            'Veh_Mar' => isset($data['Veh_Mar']) ? $data['Veh_Mar'] : '',
            'Veh_Pla' => $data['Veh_Pla'],
            'Veh_Col' => isset($data['Veh_Col']) ? $data['Veh_Col'] : '',
            'Veh_Cap' => isset($data['Veh_Cap']) ? floatval($data['Veh_Cap']) : 0,
            'Veh_Tip' => isset($data['Veh_Tip']) ? $data['Veh_Tip'] : '',
            'Veh_Tit' => isset($data['Veh_Tit']) ? $data['Veh_Tit'] : '',
            'Cli_Cod' => intval($data['Cli_Cod'])
        ), $obBD_conexionIns);
        $Veh_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
        $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);

        if ($obBD_conIns->Error == 0) {
            // Obtener datos del vehículo recién creado
            $vehiculo_data = $obBD_con1->getArrayConsulta(2, array(
                'Emp_Cod' => $Ses_Emp_Cod,
                'search' => $data['Veh_Pla'],
                'op_opciones' => 'd',
                'limits' => 'LIMIT 1'
            ), $obBD_conexion);

            $response['success'] = true;
            $response['message'] = "Vehículo registrado correctamente!";
            $response['Veh_Cod'] = $Veh_Cod;
            if (!empty($vehiculo_data) && count($vehiculo_data) > 0) {
                $response['vehiculo'] = $vehiculo_data[0];
            }
        } else {
            $response['message'] = $obBD_conIns->MsgError;
        }
    } catch (Exception $e) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns->conexion);
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Seccion para guardar ticket
if (isset($saveTicket)) {
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_conexionIns = new Class_Log_Conexion_ticket($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_ticket;
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
    try {
        $data = filter_input_array(INPUT_POST);
        $Det_Ticket = isset($data['Det_Ticket']) ? json_decode($data['Det_Ticket'], true) : array();
        if (empty($Det_Ticket) || count($Det_Ticket) == 0) {
            throw new Exception("Debe agregar al menos un producto al detalle");
        }
        // Calcular totales
        $Tck_Val = 0;
        foreach ($Det_Ticket as $row) {
            $Tck_Val += floatval($row['Dtk_Tot']);
        }
        $Tck_IvA = isset($data['Tck_IvA']) ? floatval($data['Tck_IvA']) : 0;
        $Tck_Tot = $Tck_Val + $Tck_IvA;
        // Tipo de pago: E = Efectivo (contado), C = Crédito, F = Firma
        $Tck_Pag_raw = isset($data['Tck_Pag']) ? strtoupper(trim($data['Tck_Pag'])) : 'E';
        if ($Tck_Pag_raw === 'C') {
            $Tck_Pag = 'C';
        } elseif ($Tck_Pag_raw === 'F') {
            $Tck_Pag = 'F';
        } else {
            $Tck_Pag = 'E';
        }
        // Obtener siguiente número de ticket para la empresa (solo en INSERT)
        $Tck_Num = null;
        if (empty($data['Tck_Cod'])) {
            $siguienteNumero = $obBD_con1->getRowConsulta(40, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
            $Tck_Num = isset($siguienteNumero['siguiente']) ? intval($siguienteNumero['siguiente']) : 1;
        }
        // Insertar o actualizar ticket
        if (!empty($data['Tck_Cod'])) {
            // Update
            $obBD_conIns->operacionobBD(11, array(
                'Tck_Cod' => intval($data['Tck_Cod']),
                'Cli_Cod' => isset($data['Cli_Cod']) ? intval($data['Cli_Cod']) : NULL,
                'Veh_Cod' => isset($data['Veh_Cod']) ? intval($data['Veh_Cod']) : NULL,
                'Tck_Fec' => isset($data['Tck_Fec']) ? $data['Tck_Fec'] : date('Y-m-d H:i:s'),
                'Tck_Val' => $Tck_Val,
                'Tck_IvA' => $Tck_IvA,
                'Tck_Tot' => $Tck_Tot,
                'Tck_Est' => isset($data['Tck_Est']) ? $data['Tck_Est'] : 'A',
                'Tck_Pag' => $Tck_Pag
            ), $obBD_conexionIns);
            $Tck_Cod = intval($data['Tck_Cod']);

            // Eliminar detalles anteriores
            $detalles_anteriores = $obBD_con1->getArrayConsulta(30, array('Tck_Cod' => $Tck_Cod), $obBD_conexion);
            foreach ($detalles_anteriores as $det) {
                $obBD_conIns->operacionobBD(22, array('Dtk_Cod' => $det['Dtk_Cod']), $obBD_conexionIns);
            }
        } else {
            // Insert
            $obBD_conIns->operacionobBD(10, array(
                'Cli_Cod' => isset($data['Cli_Cod']) ? intval($data['Cli_Cod']) : NULL,
                'Veh_Cod' => isset($data['Veh_Cod']) ? intval($data['Veh_Cod']) : NULL,
                'Tck_Fec' => isset($data['Tck_Fec']) ? $data['Tck_Fec'] : date('Y-m-d H:i:s'),
                'Tck_Val' => $Tck_Val,
                'Tck_IvA' => $Tck_IvA,
                'Tck_Tot' => $Tck_Tot,
                'Tck_Est' => isset($data['Tck_Est']) ? $data['Tck_Est'] : 'A',
                'Tck_Num' => $Tck_Num,
                'Tck_Pag' => $Tck_Pag
            ), $obBD_conexionIns);
            $Tck_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
        }

        // Insertar detalles
        foreach ($Det_Ticket as $row) {
            $obBD_conIns->operacionobBD(20, array(
                'Tck_Cod' => $Tck_Cod,
                'Pro_Cod' => isset($row['Pro_Cod']) ? intval($row['Pro_Cod']) : NULL,
                'Dtk_Det' => isset($row['Dtk_Det']) ? addslashes($row['Dtk_Det']) : '',
                'Dtk_Can' => isset($row['Dtk_Can']) ? floatval($row['Dtk_Can']) : 1,
                'Dtk_Pru' => isset($row['Dtk_Pru']) ? floatval($row['Dtk_Pru']) : 0,
                'Dtk_Tot' => isset($row['Dtk_Tot']) ? floatval($row['Dtk_Tot']) : 0
            ), $obBD_conexionIns);
        }

        $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);

        if ($obBD_conIns->Error == 0) {
            $response['success'] = true;
            $response['message'] = "Ticket guardado correctamente!";
            $response['Tck_Cod'] = $Tck_Cod;
            // Retornar el número de ticket generado (solo en INSERT)
            if (isset($Tck_Num) && $Tck_Num !== null) {
                $response['Tck_Num'] = $Tck_Num;
            }
        } else {
            $response['message'] = $obBD_conIns->MsgError;
        }
    } catch (Exception $e) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns->conexion);
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>

<head>
    <meta charset="UTF-8">
    <title><?Php echo $Ses_Sys_Nom; ?></title>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script language="javascript" src="../VALIDACIONES/tca_val_ticket.js?x=4"></script>
    <style>
        .footerTicket {
            text-align: right;
            width: 100%;
        }

        .footerTicket input[type=text],
        .footerTicket label {
            height: 19px;
            width: 100% !important;
            display: block;
            margin-bottom: 0px !important;
            margin-top: 0px !important;
            text-align: right;
        }

        .footerTicket label.total,
        .footerTicket input.total {
            background-color: #254463;
            color: white;
            font-size: 14px;
            border: none;
        }

        /* Asegurar que el formulario aparezca arriba en los diálogos de búsqueda */
        #proveedorDialog form,
        #vehiculoDialog form,
        #productoDialog form {
            margin-bottom: 10px;
        }

        .text-right {
            text-align: right !important;
        }

        /* Responsive para tablets y móviles */
        @media (max-width: 991px) {
            .col-md-6 {
                margin-bottom: 15px;
            }
        }

        @media (max-width: 768px) {
            .form-group label.control-label {
                margin-bottom: 5px;
            }

            .input-group-btn {
                width: auto;
            }

            .col-xs-12 {
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar Ticket de Cantera</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="reg_ticket">
                <form id="frm_ticket" name="frm_ticket" class="form-horizontal normal formulario" action="javascript:">
                    <input type="hidden" id="Tck_Cod" name="Tck_Cod" value="">
                    <input type="hidden" id="Tck_Num" name="Tck_Num" value="">
                    <input type="hidden" id="Tck_Pag" name="Tck_Pag" value="E">
                    <div class="row">
                        <div class="col-md-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Ticket</legend>
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-2 col-sm-3 col-xs-12 label-sm">N&uacute;mero de Ticket:</label>
                                        <div class="col-md-2 col-sm-3 col-xs-12">
                                            <input type="text" id="Tck_Num_Display" name="Tck_Num_Display" class="form-control input-xs" readonly placeholder="Se genera autom&aacute;ticamente">
                                        </div>
                                        <label class="control-label col-md-2 col-sm-3 col-xs-12 label-sm required">Fecha y Hora:</label>
                                        <div class="col-md-4 col-sm-3 col-xs-12">
                                            <input type="datetime-local" id="Tck_Fec" name="Tck_Fec" class="form-control input-xs" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                        </div>
                                    </div>
                                    <input type="hidden" id="Tck_Est" name="Tck_Est" value="A">
                                </div>
                            </fieldset>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-sm-12 col-xs-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Cliente</legend>
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-4 col-sm-3 col-xs-12 label-sm required">Cliente:</label>
                                        <div class="col-md-8 col-sm-9 col-xs-12">
                                            <div class="input-group">
                                                <input type="hidden" id="Cli_Cod" name="Cli_Cod" value="">
                                                <input type="text" id="Cli_Des" name="Cli_Des" class="form-control input-xs" placeholder="Seleccione un cliente" readonly="">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="$('#clienteDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-4 col-sm-3 col-xs-12 label-sm">RUC/C&eacute;dula:</label>
                                        <div class="col-md-8 col-sm-9 col-xs-12">
                                            <input type="text" id="Cli_Ced" name="Cli_Ced" class="form-control input-xs" readonly="">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-4 col-sm-3 col-xs-12 label-sm">Direcci&oacute;n:</label>
                                        <div class="col-md-8 col-sm-9 col-xs-12">
                                            <input type="text" id="Cli_Dir" name="Cli_Dir" class="form-control input-xs" readonly="">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-4 col-sm-3 col-xs-12 label-sm">Email:</label>
                                        <div class="col-md-8 col-sm-9 col-xs-12">
                                            <input type="text" id="Cli_Cor" name="Cli_Cor" class="form-control input-xs" readonly="">
                                        </div>
                                    </div>
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Valores</legend>
                                        <div class="form-group">
                                            <label class="control-label col-md-2 col-sm-3 col-xs-12 label-sm">Anticipo a Favor:</label>
                                            <div class="col-md-2 col-sm-6 col-xs-12">
                                                <input type="text" id="Val_Ant" name="Val_Ant" class="form-control input-xs text-right" placeholder="0.0" readonly="">
                                            </div>

                                            <label class="control-label col-md-2 col-sm-3 col-xs-12 label-sm">Total Tickets:</label>
                                            <div class="col-md-2 col-sm-6 col-xs-12">
                                                <input type="text" id="Val_Total_Tickets" name="Val_Total_Tickets" class="form-control input-xs text-right" placeholder="0.0" readonly="">
                                            </div>
                                            <label class="control-label col-md-2 col-sm-3 col-xs-12 label-sm">Saldo:</label>
                                            <div class="col-md-2 col-sm-6 col-xs-12">
                                                <input type="text" id="Val_Saldo" name="Val_Saldo" class="form-control input-xs text-right" placeholder="0.0" readonly="">
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-md-6 col-sm-12 col-xs-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Veh&iacute;culo</legend>
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-4 col-sm-3 col-xs-12 label-sm required">Veh&iacute;culo:</label>
                                        <div class="col-md-8 col-sm-9 col-xs-12">
                                            <div class="input-group">
                                                <input type="hidden" id="Veh_Cod" name="Veh_Cod" value="">
                                                <input type="text" id="Veh_Pla" name="Veh_Pla" class="form-control input-xs" placeholder="Seleccione un veh&iacute;culo" readonly="">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-xs" type="button" title="Buscar Veh&iacute;culo" onclick="$('#vehiculoDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                                    <button class="btn btn-primary btn-xs" type="button" title="Registrar Veh&iacute;culo" onclick="registrarVehiculo();">
                                                        <span class="glyphicon glyphicon-plus"></span>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-4 col-sm-3 col-xs-12 label-sm">Tipo Veh&iacute;culo:</label>
                                        <div class="col-md-8 col-sm-9 col-xs-12">
                                            <input type="text" id="Veh_Tit" name="Veh_Tit" class="form-control input-xs" readonly="">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-4 col-sm-3 col-xs-12 label-sm">Capacidad:</label>
                                        <div class="col-md-8 col-sm-9 col-xs-12">
                                            <input type="text" id="Veh_Cap" name="Veh_Cap" class="form-control input-xs" readonly="">
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </form>
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div style="margin-bottom: 5px;">
                            <button type="button" class="btn btn-primary btn-xs" onclick="$('#productoDialog').dialog('open');" title="Agregar Producto">
                                <span class="glyphicon glyphicon-plus"></span> Agregar Producto
                            </button>
                        </div>
                        <table id="Det_Ticket"></table>
                        <div id="Det_TicketPager"></div>
                    </div>
                </div><br>
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <div class="col-md-8 col-sm-6 col-xs-12">
                                <div class="checkbox" style="margin-top:0;">
                                    <label style="margin-right:15px;">
                                        <input type="checkbox" id="Tck_Pag_Contado" name="Tck_Pag_Contado" checked>
                                        Contado
                                    </label>
                                    <label style="margin-right:15px;">
                                        <input type="checkbox" id="Tck_Pag_Credito" name="Tck_Pag_Credito">
                                        Cr&eacute;dito
                                    </label>
                                    <label>
                                        <input type="checkbox" id="Tck_Pag_Firma" name="Tck_Pag_Firma">
                                        Firma
                                    </label>
                                </div>
                            </div>
                            <label class="control-label col-md-2 col-sm-3 col-xs-4 label-sm">Valor Neto:</label>
                            <div class="col-md-2 col-sm-3 col-xs-8">
                                <input type="text" id="Tck_Val" name="Tck_Val" class="form-control input-xs text-right" readonly value="0.0000">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-8 col-sm-6 hidden-xs">
                                <!-- Espacio vacío a la izquierda -->
                            </div>
                            <label class="control-label col-md-2 col-sm-3 col-xs-4 label-sm">IVA:</label>
                            <div class="col-md-2 col-sm-3 col-xs-8">
                                <input type="text" id="Tck_IvA" name="Tck_IvA" class="form-control input-xs text-right" value="0.0000" onchange="calcularTotales();" onkeyup="calcularTotales();">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-8 col-sm-6 hidden-xs">
                                <!-- Espacio vacío a la izquierda -->
                            </div>
                            <label class="control-label col-md-2 col-sm-3 col-xs-4 label-sm">Total:</label>
                            <div class="col-md-2 col-sm-3 col-xs-8">
                                <input type="text" id="Tck_Tot" name="Tck_Tot" class="form-control input-xs total text-right" readonly value="0.0000">
                            </div>
                        </div>
                    </div>
                </div>
                <div style="padding-top: 5px;">
                    <button type="button" onclick="saveTicket();" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                    <button type="button" onclick="limpiarFormulario();" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-refresh"></span> Limpiar</button>
                    <button type="button" onclick="window.location.href='tca_con_ticket.php';" class="btn btn-info btn-xs"><span class="glyphicon glyphicon-list"></span> Lista</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Dialog para buscar cliente -->
    <div id="clienteDialog" style="display:none;"></div>
    <!-- Dialog para buscar vehiculo -->
    <div id="vehiculoDialog" style="display:none;"></div>
    <!-- Dialog para buscar producto -->
    <div id="productoDialog" style="display:none;"></div>
    <!-- Dialog para registrar vehiculo -->
    <div id="vehiculoRegistroDialog" title="Registrar Veh&iacute;culo" style="display:none;">
        <div class="row">
            <div class="col-md-12">
                <form id="frm_vehiculo_registro" name="frm_vehiculo_registro" class="form-horizontal normal" action="javascript:">
                    <input type="hidden" id="Veh_Cod_Reg" name="Veh_Cod" value="">
                    <input type="hidden" id="Cli_Cod_Reg" name="Cli_Cod" value="">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos del Veh&iacute;culo</legend>
                        <div class="form-group">
                            <label class="control-label col-sm-4 label-sm required">Placa:</label>
                            <div class="col-sm-7">
                                <input type="text" id="Veh_Pla_Reg" name="Veh_Pla" class="form-control input-xs" placeholder="Ej: ABC1234" maxlength="10" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4 label-sm">Marca:</label>
                            <div class="col-sm-7">
                                <input type="text" id="Veh_Mar_Reg" name="Veh_Mar" class="form-control input-xs" placeholder="Ej: Toyota, Mazda, Hino" maxlength="30">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4 label-sm">Color:</label>
                            <div class="col-sm-7">
                                <input type="text" id="Veh_Col_Reg" name="Veh_Col" class="form-control input-xs" placeholder="Ej: Rojo, Verde, Azul" maxlength="20">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4 label-sm">Tipo Veh&iacute;culo:</label>
                            <div class="col-sm-7">
                                <select id="Veh_Tit_Reg" name="Veh_Tit" class="form-control input-xs">
                                    <option value="">Seleccione...</option>
                                    <option value="V">Volqueta Sencilla</option>
                                    <option value="VM">Volqueta Mula</option>
                                    <option value="VB">Volqueta Bañera</option>
                                    <option value="D">TIPO DUMPER</option>
                                    <option value="B">Bus</option>
                                    <option value="C">CAMION</option>
                                    <option value="T">Tractor</option>
                                    <option value="M">Moto</option>
                                    <option value="O">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4 label-sm">Capacidad (tn):</label>
                            <div class="col-sm-7">
                                <input type="number" id="Veh_Cap_Reg" name="Veh_Cap" class="form-control input-xs" placeholder="0.00" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4"></label>
                            <div class="col-sm-8">
                                <button type="button" onclick="guardarVehiculo();" class="btn btn-primary btn-xs">
                                    <span class="glyphicon glyphicon-floppy-disk"></span> Guardar
                                </button>
                                <button type="button" onclick="$('#vehiculoRegistroDialog').dialog('close');" class="btn btn-danger btn-xs">
                                    <span class="glyphicon glyphicon-remove"></span> Cancelar
                                </button>
                            </div>
                        </div>
                        <div class="form-group Titulos2">
                            <div class="col-sm-12">
                                <hr />
                                <b>NOTA:</b> Los campos marcados con <span class="required"></span> son obligatorios.
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</body>
</html>