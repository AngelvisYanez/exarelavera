<?php

/**
 * @abstract Permite convertir masivamente los manifiestos en facturas de venta y consumir anticipos
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de creacion  2025-11-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_fac_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_manifiesto($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_manifiesto;
//$obBD_con1->debug(true);
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

ini_set('max_execution_time', 600); // 5 minutos
ini_set('memory_limit', '1024M');
set_time_limit(600); // Establecer límite de tiempo a 10 minutos

$ivas = $obBD_con1->getArrayConsulta(8, "", $obBD_conexion);      //Sección para obtener los ivas de la tabla iva
/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(7, $Ses_Emp_Cod, $obBD_conexion);
$vendedor = $obBD_con1->getRowConsulta(24, $Ses_Suc_Cod . '*' . $Ses_Prs_Cod, $obBD_conexion);
$llave = $obBD_con1->getRowConsulta(48, $Ses_Emp_Cod, $obBD_conexion); //Traer las llaves para firmar documento
/* busqueda de documentos */
if (isset($searchDocument)) {
    $data = $_GET;
    $iva_actual = $obBD_con1->getRowConsulta(8, array('Iva_Est' => 'A'), $obBD_conexion);
    $data['Iva_Cod'] = $iva_actual['Iva_Cod'];
    $data['Iva_Por'] = $iva_actual['Iva_Por'];
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    // Incluir filtro de planta si existe
    if (isset($_GET['Pla_Cod']) && !empty($_GET['Pla_Cod'])) {
        $data['Pla_Cod'] = $_GET['Pla_Cod'];
    }

    $responce = $obBD_con1->getPageGrid(10, $data, $obBD_conexion);
    $obBD_con1->echoJson($responce);
}

/* Contar manifiestos por facturar (mismos filtros: cédula y planta) */
if (isset($_GET['getCountManifiestosAFacturar'])) {
    $data = array(
        'Emp_Cod' => $Ses_Emp_Cod,
        'search' => isset($_GET['search']) ? $_GET['search'] : '',
        'op_opciones' => isset($_GET['op_opciones']) ? $_GET['op_opciones'] : '',
        'Man_Tip' => isset($_GET['Man_Tip']) ? $_GET['Man_Tip'] : '',
        'Pla_Cod' => isset($_GET['Pla_Cod']) ? $_GET['Pla_Cod'] : '',
        'Fec_Ini' => isset($_GET['Fec_Ini']) ? $_GET['Fec_Ini'] : '',
        'Fec_Fin' => isset($_GET['Fec_Fin']) ? $_GET['Fec_Fin'] : '',
        'Pec_Cod' => isset($_GET['Pec_Cod']) ? $_GET['Pec_Cod'] : '',
        'Cmb_Mes' => isset($_GET['Cmb_Mes']) ? $_GET['Cmb_Mes'] : '',
        'fecha_inicio' => isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '',
        'fecha_fin' => isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : ''
    );
    $row = $obBD_con1->getRowConsulta(72, $data, $obBD_conexion);
    $total_gs = isset($row['total_gs']) ? intval($row['total_gs']) : 0;
    $total_fact = isset($row['total_fact']) ? intval($row['total_fact']) : 0;
    $total_pend = isset($row['total_pend']) ? intval($row['total_pend']) : 0;
    $obBD_con1->echoJson(array(
        'total' => $total_gs, // compatibilidad con lógica existente
        'total_gs' => $total_gs,
        'total_fact' => $total_fact,
        'total_pend' => $total_pend
    ));
    exit;
}

/* Aviso: manifiestos hoy a facturar (consulta 78 - plantas con facturación diaria) */
if (isset($_GET['getCountManifiestosAFacturarPlanta'])) {
    $data = array('Emp_Cod' => $Ses_Emp_Cod);
    $rows = $obBD_con1->getArrayConsulta(78, $data, $obBD_conexion);
    $total = 0;
    $detalle = array();
    if (!empty($rows)) {
        foreach ($rows as $r) {
            $cant = isset($r['cant_manifiestos_hoy']) ? intval($r['cant_manifiestos_hoy']) : 0;
            $total += $cant;
            $detalle[] = array(
                'Pla_Nom' => isset($r['Pla_Nom']) ? $r['Pla_Nom'] : '',
                'cant_manifiestos_hoy' => $cant
            );
        }
    }
    $obBD_con1->echoJson(array('total' => $total, 'detalle' => $detalle));
    exit;
}

/* Obtener plantas de clientes */
if (isset($getPlantasClientes)) {
    $Cli_Cods = isset($_GET['Cli_Cod']) ? $_GET['Cli_Cod'] : array();
    // Asegurar que sea un array
    if (!is_array($Cli_Cods)) {
        $Cli_Cods = !empty($Cli_Cods) ? array($Cli_Cods) : array();
    }
    $plantas = $obBD_con1->getArrayConsulta(69, array('Cli_Cod' => $Cli_Cods), $obBD_conexion);
    $obBD_con1->echoJson($plantas);
}

/* Obtener resumen agrupado de manifiestos por facturar (por cliente y bodega/planta) */
if (isset($_GET['getSinFacturarAgrupado'])) {
    $Fec_Ini = isset($_GET['Fec_Ini']) ? trim($_GET['Fec_Ini']) : '';
    $Fec_Fin = isset($_GET['Fec_Fin']) ? trim($_GET['Fec_Fin']) : '';
    $Pla_Cod = isset($_GET['Pla_Cod']) && $_GET['Pla_Cod'] !== '' ? intval($_GET['Pla_Cod']) : 0;
    $rows = $obBD_con1->getArrayConsulta( 80, array('Emp_Cod' => $Ses_Emp_Cod, 'Fec_Ini' => $Fec_Ini, 'Fec_Fin' => $Fec_Fin, 'Pla_Cod' => $Pla_Cod ),  $obBD_conexion );
    $obBD_con1->echoJson($rows);
    exit;
}

/* Verificar si existe clave de acceso activa para registro de facturas */
if (isset($verificarClaveAccesoExiste)) {
    $Emp_Cod = $Ses_Emp_Cod;
    $resultado = $obBD_con1->getRowConsulta(71, array('Emp_Cod' => $Emp_Cod), $obBD_conexion);
    $existe = isset($resultado['total']) && intval($resultado['total']) > 0;
    $obBD_con1->echoJson(array('existe' => $existe));
    exit;
}

/* Validar clave de acceso */
if (isset($validarClaveAcceso)) {
    $Cla_Cod = isset($_POST['Cla_Cod']) ? trim($_POST['Cla_Cod']) : '';
    $Emp_Cod = $Ses_Emp_Cod;

    if (empty($Cla_Cod)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Debe ingresar la clave de acceso.'));
        exit;
    }

    $clave = $obBD_con1->getRowConsulta(70, array('Emp_Cod' => $Emp_Cod, 'Cla_Cod' => $Cla_Cod), $obBD_conexion);

    if (!empty($clave) && isset($clave['Cod_Cla'])) {
        $obBD_con1->echoJson(array('success' => true, 'message' => 'Clave de acceso válida.', 'clave' => $clave));
    } else {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Clave de acceso inválida o inactiva.'));
    }
    exit;
}

/* Grid paginado de clientes para diálogo de búsqueda (Caso 76 en sql_man_fac_1.0.php) */
if (isset($_REQUEST['clieAjax'])) {
    $data = array_merge($_GET, $_POST);
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getPageGrid(76, $data, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit;
}

/* Grid paginado: Manifiestos por facturar agrupado (Caso 81 en sql_man_fac_1.0.php) */
if (isset($_REQUEST['sfAjax'])) {
    $data = array_merge($_GET, $_POST);
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getPageGrid(81, $data, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit;
}

//Sección para extraer el Pun_Cod y Vnd_Cod del usuario sobre la tabla vendedor
$rs_Punto = $obBD_con1->getRowConsulta(2, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);

if (isset($generarFacturasAjax)) {
    $Tic_Cod = 1; //Tipo de Comprobante Factura
    $input_autorizacion = isset($_POST['input_autorizacion']) ? trim($_POST['input_autorizacion']) : '';
    $Tic_Sri = 1; //FACTURA
    // Verificar si está agrupado desde el parámetro fac_group
    $fac_group = isset($_POST['fac_group']) && (
        $_POST['fac_group'] === true ||
        $_POST['fac_group'] === 'true' ||
        $_POST['fac_group'] === 1 ||
        $_POST['fac_group'] === '1'
    );
    // Validar que existe el punto de impresión
    if (empty($rs_Punto) || empty($rs_Punto['Pun_Cod'])) {
        $resp = array('success' => false, 'message' => 'No se encontró un punto de impresión configurado para este usuario. Verifique que tenga permisos de vendedor asignados.');
        $obBD_con1->echoJson($resp);
        exit;
    }
    // Obtener los parámetros de manifiesto para la empresa y tipo de pago anticipos
    $param_manifiesto = $obBD_con1->getRowConsulta(57, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    // Si existe configuración en param_manifiesto, sobrescribir las variables
    if (empty($param_manifiesto) ||  empty($param_manifiesto['Pld_Cod']) ||  empty($param_manifiesto['Pro_Cod']) ||  empty($param_manifiesto['Tpc_Cod'])) {
        $resp = array('success' => false, 'message' => 'Falta parametrizar cuenta de pago, producto o tipo de pago en la configuración de manifiestos para poder generar facturas.');
        $obBD_con1->echoJson($resp);
        exit;
    } else {
        $pago['Pld_Cod'] = $param_manifiesto['Pld_Cod'];
        $Pro_Cod = $param_manifiesto['Pro_Cod'];
        $Tpc_Cod = $param_manifiesto['Tpc_Cod'];
    }
    $Check_Comprobante = 1;
    $Caj_Fec = date('Y-m-d'); //Fecha de hoy
    $fecha_vencimiento = date('Y-m-d', strtotime('+1 day')); //fecha vencimiento del pago de factura
    $Pec_Cod = $obBD_con1->getRowConsulta(1, $Ses_Emp_Cod, $obBD_conexion);
    // Validar período contable
    if (empty($Pec_Cod) || empty($Pec_Cod['Pec_Cod'])) {
        $resp = array('success' => false, 'message' => 'No se encontró un período contable activo para esta empresa.');
        $obBD_con1->echoJson($resp);
        exit;
    }
    $autorizaci = $obBD_con1->getRowConsulta(49, $rs_Punto['Pun_Cod'] . '*' . (isset($Aut_Cod) ? $Aut_Cod : '') . '*' . $Tic_Cod, $obBD_conexion);
    // Validar autorización
    if (empty($autorizaci) || empty($autorizaci['Aut_Cod'])) {
        $resp = array('success' => false, 'message' => 'No se encontró una autorización activa para facturación en este punto de venta.');
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_conexionIns = new Class_Log_Conexion_manifiesto($Ses_Dat_Dis);
    $obBD_conIns =  new Class_Log_Datos_manifiesto;
    $rs_Caja = $obBD_con1->getRowConsulta(19, $rs_Punto['Pun_Cod'] . '*' . $Caj_Fec, $obBD_conexion);
    if (empty($rs_Caja['Caj_Cod'])) {
        $obBD_conIns->operacionobBD(20, $rs_Punto['Pun_Cod'] . '*' . $Caj_Fec, $obBD_conexionIns);
        $Caj_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
    } else {
        $Caj_Cod = $rs_Caja['Caj_Cod'];
    }
    $errores_manifiestos = array();
    $resultados_exitosos = array();
    // Para capturar todos los errores, incluimos un try/catch global
    $Plan_Cod = $obBD_con1->getRowConsulta(28, $Ses_Emp_Cod, $obBD_conexion);
    $Plan_Cod = $Plan_Cod['Pla_Cod'];
    $cuenta_ant = $obBD_con1->getRowConsulta(9, $Plan_Cod . '*' . 'ANC', $obBD_conexion);

    // Recibir los manifiestos desde POST
    $manifiestos = isset($_POST['manifiestos']) ? $_POST['manifiestos'] : array();

    $cantidad_manifiestos = count($manifiestos); // La función count está bien utilizada aquí.

    try {
        foreach ($manifiestos as $man) {
            //Obtener el codigo del cliente del manifiesto
            $t_rubros = $man['total'];
            $Prs_Ced = $man['Prs_Ced'];
            try {
                $obBD_con1->validaCierrePeriodo('ventas', 'Caj_Fec', 'Vet_Cod', $Caj_Fec, null, $obBD_conexion);
                if (preg_match('/^9{8,}/', $Prs_Ced)  && $t_rubros > 50) {
                    throw new Exception("La normativa del SRI indica que si el cliente supera un monto de 50 USD, no debe ser considerado como consumidor final.");
                }
                if (empty($vendedor['Vnd_Cod'])) {
                    throw new Exception("No tiene permisos de Vendedor!");
                }
                $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
                $Vnd_Cod = $vendedor['Vnd_Cod'];
                $Vnd_Cod_aux =  NULL;
                $Vet_Sri = '';
                if (empty($man['Man_Num'])) {
                    throw new Exception("El Manifiesto con código " . $man['Man_Cod'] . " no tiene un número válido");
                }
                // Formato ManNum igual que en man_alt_manifiesto.php: M + Pla_Cod + '-' + número 4 dígitos
                $man_num_display = (isset($man['Man_Num']) && isset($man['Pla_Cod'])) ? 'M' . $man['Pla_Cod'] . '-' . str_pad($man['Man_Num'], 4, '0', STR_PAD_LEFT) : (isset($man['Man_Num']) ? $man['Man_Num'] : '');

                //Numero de secuencia siguiente
                $response = $obBD_con1->getRowConsulta(4, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod . '*' . $Tic_Cod . '*' . $autorizaci['Aut_Cod'], $obBD_conexion);
                $siguiente = $obBD_con1->getRowConsulta(5, $autorizaci['Aut_Ini'] . '*' . $autorizaci['Aut_Fin'] . '*' . $autorizaci['Aut_Sri'] . '*' . $Tic_Cod . '*' . $Ses_Suc_Cod . '*' . $autorizaci['Pun_Sri'], $obBD_conexion);
                $Vet_Num = $siguiente['siguiente'];
                /* valida que no exista el documento */
                $num_existe_gencod = $obBD_con1->getRowConsulta(12, $Ses_Suc_Cod . '*' . $autorizaci['Aut_Sri'] . '*' . $Vet_Num . '*' . $Vet_Cod . '*' . $autorizaci['Pun_Sri'], $obBD_conexion);
                if ($num_existe_gencod['total'] * 1 > 0) {
                    throw new Exception("El documento No. $Vet_Num ya existe, o no tiene facturas disponibles!");
                }
                $Aut_Cod = $autorizaci['Aut_Cod'];
                if ($autorizaci['Aut_Tem'] == 'E' && $Vet_Num !== 0 && $input_autorizacion == '') {
                    $Vet_Aut = 'N';
                    require_once('../../facturacion/LOGICA/fac_log_electronica.php');
                    $obBD_conexionElect = new Class_Log_Conexion_Elect($Ses_Dat_Dis);
                    $obBD_elect =  new Class_Log_Datos_Factura_Elect();
                    // Validar que los parámetros necesarios estén presentes antes de llamar a getClaveAcceso
                    if (empty($Aut_Cod)) {
                        throw new Exception("Error: El código de autorización (Aut_Cod) está vacío.");
                    }
                    if (empty($Caj_Fec)) {
                        throw new Exception("Error: La fecha del documento (Caj_Fec) está vacía.");
                    }
                    if (empty($Vet_Num) || $Vet_Num == 0) {
                        throw new Exception("Error: El número de documento (Vet_Num) está vacío o es 0.");
                    }
                    $claveAcceso = $obBD_elect->getClaveAcceso($Aut_Cod, $Caj_Fec, $Vet_Num, $obBD_conexionElect);
                    if (empty($claveAcceso)) {
                        throw new Exception("Error al generar <u>Clave de Acceso</u> del <i>Comprobante Electrónico</i>! Verifique que la autorización (Aut_Cod=$Aut_Cod) esté correctamente configurada y tenga los datos necesarios (punto de impresión, sucursal, tipo de comprobante).");
                    }
                }



                //Llamar datos de cliente
                $Cli_Cod = $man['Cli_Cod'];
                $Ciu_Cod = $man['Ciu_Cod'];

                // Verificar si está agrupado (aceptar boolean true, string "true", o 1)
                $es_agrupado_man = isset($man['_agrupado']) && (
                    $man['_agrupado'] === true ||
                    $man['_agrupado'] === 'true' ||
                    $man['_agrupado'] === 1 ||
                    $man['_agrupado'] === '1'
                );
                // Si no está en man, usar el parámetro fac_group como alternativa
                $es_agrupado = $es_agrupado_man || $fac_group;
                // Si está agrupado, obtener manifiestos originales para contar cantidad
                $manifiestos_originales = null;
                if ($es_agrupado) {
                    if (isset($man['_manifiestos_originales_json']) && $man['_manifiestos_originales_json'] !== '') {
                        $manifiestos_originales =  (json_decode(stripslashes($man['_manifiestos_originales_json']), true));
                        if (json_last_error() !== JSON_ERROR_NONE) $manifiestos_originales = null;
                    }
                    /*  if ($manifiestos_originales === null && isset($man['_manifiestos_originales'])) {
                        if (is_array($man['_manifiestos_originales'])) {
                            $manifiestos_originales = $man['_manifiestos_originales'];
                        } elseif (is_string($man['_manifiestos_originales'])) {
                            $manifiestos_originales = json_decode($man['_manifiestos_originales'], true);
                        }
                    }*/
                }
                if ($es_agrupado) {
                    $cant_agrupado = (is_array($manifiestos_originales) && count($manifiestos_originales) > 0)
                        ? count($manifiestos_originales)
                        : $cantidad_manifiestos;
                    $Vet_Obs = 'Planta: ' . $man['Pla_Nom'] . ' - ' . 'Cant.Viajes: ' . $cant_agrupado;
                } else {
                    $Vet_Obs =  'Planta: ' . $man['Pla_Nom'] . ' - ' . 'Manifiesto Nro. ' . $man_num_display /*$man['Man_Num']*/;
                }

                $Vet_Des = 0;
                $hora = date("H:i:s");
                $Ret_Num = NULL;
                $Ret_Fec = NULL;
                $Ret_Aut_Sri = NULL;
                $Prf_Cod = NULL;
                $Vnd_Cod_aux = NULL;
                $Vet_Sri = NULL;
                /* Cabecera de la factura de venta */
                $encabezado_venta = array(
                    'Tic_Cod' => $Tic_Cod,
                    'Cli_Cod' => $Cli_Cod,
                    'Ciu_Cod' => $Ciu_Cod,
                    'Caj_Cod' => $Caj_Cod,
                    'Vnd_Cod' => $Vnd_Cod,
                    'Vet_Num' => $Vet_Num,
                    'Vet_Obs' => $Vet_Obs,
                    'Aut_Cod' => $Aut_Cod,
                    'Vet_Des' => $Vet_Des,
                    'Vet_Hor' => $hora,
                    'Vet_Xml' => (isset($claveAcceso) ? $claveAcceso : ''),
                    'Vet_Aut' => (isset($Vet_Aut) ? $Vet_Aut : ''),
                    'Ret_Num' => $Ret_Num,
                    'Ret_Fec' => $Ret_Fec,
                    'Ret_Aut' => $Ret_Aut_Sri,
                    'Tpc_Cod' => $Tpc_Cod,
                    'Vet_Sri' => $Vet_Sri,
                    'Prf_Cod' => $Prf_Cod,
                    'Vnd_Cod_Aux' => $Vnd_Cod_aux
                );
                $obBD_conIns->operacionobBD(27, $encabezado_venta, $obBD_conexionIns);
                $Vet_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
                $s_add = true;
                //Llamar el producto
                $items =  $obBD_conIns->getArrayConsulta(50, $Ses_Suc_Cod . '*' . $Pro_Cod, $obBD_conexionIns);
                $vet_total = 0;
                foreach ($items as $i => $item) {
                    $item['Vet_Cod'] = $Vet_Cod;
                    $item['Vet_Ite'] = $i + 1;
                    $item['Pro_Cod'] = $item['Pro_Cod'];
                    if ($es_agrupado) {
                        // Si está agrupado, usar los valores ya sumados
                        $item['Vet_Can'] = $man['Man_Pes'];  // Cantidad total sumada
                        $item['Iva_Cod'] = $man['Iva_Cod'];
                        $item['Vet_Pru'] = $man['Man_Pun']; // Precio unitario
                        // El Vet_Imp debe ser el subtotal sin IVA (antes de aplicar IVA)
                        $item['Vet_Imp'] = $man['subtotal']; // Subtotal sin IVA
                        $item['Vet_Dec'] = $item['Pro_Dsc']  ? $item['Pro_Dsc'] : 0;
                        $vet_imp = $item['Vet_Imp'];
                        $subtotal = $man['subtotal']; // Subtotal ya sumado
                        $vet_total =  round($man['total'], 2);   // Total ya calculado (subtotal + IVA)
                    } else {
                        // Comportamiento normal (sin agrupar)
                        $item['Vet_Can'] = $man['Man_Pes'];  //convertir a toneladas con dos decimales
                        $item['Iva_Cod'] = $man['Iva_Cod'];                  /*  $item['Iva_Cod'];*/
                        $item['Vet_Pru'] = $man['Man_Pun'];
                        $item['Vet_Imp'] = $man['Man_Pun'] */* $item['Vet_Pru'] **/ $item['Vet_Can'];
                        $item['Vet_Dec'] = $item['Pro_Dsc']  ? $item['Pro_Dsc'] : 0;
                        $vet_imp = $item['Vet_Imp'];
                        $subtotal = $item['Vet_Imp']  -  $item['Vet_Imp']  * ($item['Vet_Dec'] / 100);
                        $vet_total = round($subtotal + ($subtotal * ($item['Iva_Por'] / 100)), 2);
                    }

                    $item['Nge_Cod'] = 0;
                    $item['Asi_Int'] = 0;
                    $item['Vet_Rec'] = 0;
                    $item['Cnt_Cod'] = 0;
                    $item['Vet_Int'] = 0;
                    $item['Vet_Uni'] = 1;
                    $item['Ren_Cod'] = NULL;
                    $item['Des_Adi'] = "";
                    $item['Ren_Iva'] = 0;
                    $item['Vet_Ice'] = 0;
                    $obBD_conIns->operacionobBD(33, $item, $obBD_conexionIns);
                }
                // $pld_pago = 96275;
                $pld_pago = $param_manifiesto['Pld_Cod'];
                if (!isset($pld_pago) || empty($pld_pago)) throw new Exception('Revisar la parametrizacion contable de pago al cliente');
                if (!empty($Vet_Cod)) {
                    $i = 1;
                    $pag['Vet_Num'] = $i;
                    $pag['Vet_Cod'] = $Vet_Cod;
                    $pag['Bak_Cod'] = 1;
                    $pag['Ban_Cod'] = null;
                    $pag['Pag_Pld'] = null;
                    $pag['Vet_Tot'] = $vet_total;
                    $pag['Tipo_Cod'] = 4; //tipo de pago cuentas por cobrar
                    $pag['Pag_Pld'] = $pld_pago;
                    $obBD_conIns->operacionobBD(18, $pag, $obBD_conexionIns);
                }
                unset($pag);
                /* Creacion del comprobante contable */
                if ($configs['Cof_Con'] == 'S' && (($Tic_Sri == 0) || $Check_Comprobante * 1 === 1)) {
                    $Com_Con = 'REG. VENTA ' . $Vet_Num . '  /';
                    $Com_Fec = $Caj_Fec;
                    $Tia_Asi = $obBD_con1->getRowConsulta(21, 7, $obBD_conexion);
                    $meseCom = explode('-', $Com_Fec);
                    $Com_Num = $obBD_con1->codigoComprAuto($Tia_Asi['Tia_Cod'], $Pec_Cod['Pec_Cod'], $meseCom[1], $obBD_conexion);
                    $campo = 'Cli_Cod';
                    $obBD_conIns->operacionobBD(17, $Pec_Cod['Pec_Cod'] . '*' . $Cli_Cod . '*' . $Com_Num . '*' . $Com_Fec . '*' . trim($Com_Con) . '*'
                        . $Tia_Asi['Tia_Cod'] . '*' . $t_rubros . '*' . trim($Vet_Obs) . '*' . $campo, $obBD_conexionIns);
                    $Com_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                    $obBD_conIns->operacionobBD(22, $Com_Cod . '*' . $Vet_Cod, $obBD_conexionIns);
                    $t_iva = 0;
                    $t_descuento = 0;
                    $t_ice = 0;
                    if ($es_agrupado) {
                        // Si está agrupado, usar los valores ya calculados y sumados
                        $t_iva = $man['total_iva'];
                        $t_descuento = 0; // Los descuentos ya están incluidos en el subtotal
                    }
                    foreach ($items as &$item) {
                        $cuenta = $obBD_con1->getRowConsulta(23,  $Plan_Cod . '*' . $item['Pro_Cod'] . '*' . 'V', $obBD_conexion);
                        if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del producto: <u>' . $item['Ite_Lar'] . '</u>!');
                        $item['Pld_Cod'] = $cuenta['Pld_Cod'];
                        $item['Vet_Imp'] = $vet_imp;
                        $obBD_conIns->operacionobBD(25, $Com_Cod . '*' . 'H' . '*' . ($item['Vet_Imp']) . '*' . $cuenta['Pld_Des'] . '*' . $item['Ite_Lar'] . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
                        $Vet_Des =  $item['Pro_Dsc'];
                        if (!$es_agrupado) {
                            // Solo calcular si NO está agrupado (si está agrupado ya se calculó arriba)
                            $t_iva = ($item['Vet_Imp']  - ($item['Vet_Imp'] * $Vet_Des / 100))  * ($man['Iva_Por'] / 100);
                            $t_descuento = $item['Vet_Imp'] * $Vet_Des / 100;
                        }
                        $t_ice = $item['Ice_Int'];
                    }
                    if ($t_iva * 1 > 0) {
                        $cuenta = $obBD_con1->getRowConsulta(26, $Plan_Cod, $obBD_conexion);
                        if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Iva Cobrado</u>!');
                        $obBD_conIns->operacionobBD(25, $Com_Cod . '*' . ('H') . '*' . $t_iva . '*' . 'IVA' . '*' . 'IVA' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
                    }
                    if ($Vet_Des > 0) {
                        $cuenta = $obBD_con1->getRowConsulta(9, $Plan_Cod . '*' . 'DV', $obBD_conexion);
                        if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Descuentos en Ventas</u>!');
                        $obBD_conIns->operacionobBD(25, $Com_Cod . '*' . ('D') . '*' . $t_descuento . '*' . 'DESCUENTO' . '*' . 'DESCUENTO' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
                    }
                    if ($t_ice * 1 > 0) {
                        $cuenta = $obBD_con1->getRowConsulta(9, $Plan_Cod . '*' . 'ICV', $obBD_conexion);
                        if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>ICE en Ventas</u>!');
                        $obBD_conIns->operacionobBD(25, $Com_Cod . '*' . ('H') . '*' . $t_ice . '*' . 'ICE' . '*' . 'ICE' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
                    }
                    $pagos = array(array('Vet_Tot' => isset($vet_total) ? $vet_total : 0, 'Vet_Num' => $Vet_Num, 'Pag_Pld' => $pld_pago, 'Forma_Cod' => 2, 'Cpc_Ven' => $fecha_vencimiento,  'Cpc_Obs' => ''));
                    foreach ($pagos as $pag) {
                        $obBD_conIns->operacionobBD(25, $Com_Cod . '*' . ('D') . '*' . $pag['Vet_Tot'] . '*' . $pag['Vet_Num'] . '*' . ('Doc.' . $Vet_Num) . '*' . $pag['Pag_Pld'], $obBD_conexionIns);
                        if ($pag['Forma_Cod'] * 1 == 2) {
                            $obBD_conIns->operacionobBD(13, $Com_Cod . '*' . $Vet_Cod . '*' . $pag['Cpc_Ven'] . '*' . (isset($pag['Cpc_Obs']) ? trim($pag['Cpc_Obs']) : ''), $obBD_conexionIns);
                            $Cpc_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                        }
                    }
                }
                // Intentar obtener los manifiestos originales de diferentes formas
                /*  $manifiestos_originales = null;
                // SIEMPRE usar _manifiestos_originales_json si existe (más confiable)
                if (isset($man['_manifiestos_originales_json']) && !empty($man['_manifiestos_originales_json'])) {
                    $manifiestos_originales = json_decode($man['_manifiestos_originales_json'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $manifiestos_originales = null;
                    }
                }*/
                // Si no se obtuvo desde JSON string, intentar desde _manifiestos_originales (fallback)
                if ($manifiestos_originales === null && isset($man['_manifiestos_originales'])) {
                    if (is_array($man['_manifiestos_originales'])) {
                        $manifiestos_originales = $man['_manifiestos_originales'];
                    } elseif (is_string($man['_manifiestos_originales'])) {
                        $manifiestos_originales = json_decode($man['_manifiestos_originales'], true);
                    }
                }
                // Si está agrupado, actualizar todos los manifiestos originales (_manifiestos_originales es array de Man_Cod)
                if ($es_agrupado && is_array($manifiestos_originales) && count($manifiestos_originales) > 0) {
                    // Recopilar todos los Man_Cod (cada elemento es el código o un array con Man_Cod por compatibilidad)
                    $man_cods = array();
                    foreach ($manifiestos_originales as $idx => $man_original) {
                        $cod = is_array($man_original) ? (isset($man_original['Man_Cod']) ? $man_original['Man_Cod'] : null) : $man_original;
                        if ($cod !== null && $cod !== '') {
                            $man_cods[] = intval($cod);
                        }
                    }
                    // Si hay manifiestos para actualizar
                    if (!empty($man_cods)) {
                        $total_manifiestos = count($man_cods);
                        // Si hay más de 10 manifiestos, usar UPDATE masivo para mejor rendimiento
                        // Si hay 10 o menos, usar el método individual para mantener compatibilidad
                        if ($total_manifiestos > 10) {
                            // Para más de 500 manifiestos, procesar en lotes de 500 para evitar problemas con IN clause
                            $lote_size = 500;
                            if ($total_manifiestos > $lote_size) {
                                // Procesar en lotes
                                $lotes = array_chunk($man_cods, $lote_size);
                                foreach ($lotes as $lote_idx => $lote) {
                                    $obBD_conIns->operacionobBD(77, array($lote, 'F', $Vet_Cod), $obBD_conexionIns);
                                    // Verificar si hubo error en la actualización masiva del lote
                                    if ($obBD_conIns->Error != 0) {
                                        throw new Exception("Error al actualizar el estado de los manifiestos (lote " . ($lote_idx + 1) . "): " . $obBD_conIns->MsgError);
                                    }
                                }
                            } else {
                                // UPDATE masivo usando el nuevo caso 77 (para 11-500 manifiestos)
                                $obBD_conIns->operacionobBD(77, array($man_cods, 'F', $Vet_Cod), $obBD_conexionIns);
                                // Verificar si hubo error en la actualización masiva
                                if ($obBD_conIns->Error != 0) {
                                    throw new Exception("Error al actualizar el estado de los manifiestos: " . $obBD_conIns->MsgError);
                                }
                            }
                        } else {
                            // Método individual para pocos manifiestos (mantiene compatibilidad)
                            foreach ($manifiestos_originales as $idx => $man_original) {
                                $man_cod = is_array($man_original) ? (isset($man_original['Man_Cod']) ? $man_original['Man_Cod'] : null) : $man_original;
                                if ($man_cod !== null && $man_cod !== '') {
                                    $obBD_conIns->operacionobBD(30, $man_cod . '*' . 'F' . '*' . $Vet_Cod, $obBD_conexionIns);
                                    if ($obBD_conIns->Error != 0) {
                                        throw new Exception("Error al actualizar el manifiesto " . $man_cod . ": " . $obBD_conIns->MsgError);
                                    }
                                }
                            }
                            /*
                            $listaManCod = '';
                            foreach ($manifiestos_originales as $idx => $man_original) {
                                if (isset($man_original['Man_Cod'])) {
                                    $listaManCod .= $man_original['Man_Cod'] . ',';                            
                                }
                            }
                            $listaManCod = rtrim($listaManCod, ',');
                            $obBD_conIns->operacionobBD(30, $listaManCod . '*' . 'F' . '*' . $Vet_Cod, $obBD_conexionIns);*/
                        }
                    }
                } else {
                    // Comportamiento normal: actualizar solo el manifiesto individual
                    $obBD_conIns->operacionobBD(30, $man['Man_Cod'] . '*' . 'F' . '*' . $Vet_Cod, $obBD_conexionIns);

                    // Verificar error después de la actualización
                    if ($obBD_conIns->Error != 0) {
                        throw new Exception("Error al actualizar el manifiesto " . $man['Man_Cod'] . ": " . $obBD_conIns->MsgError);
                    }
                }
                // Variable para almacenar la respuesta (se usará después del commit)
                $respuesta = null;
                // Si fue exitoso, preparamos resultado exitoso:
                if ($obBD_conIns->Error == 0) {
                    // $reportes = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
                    $manifiestos_cods = array();
                    if ($es_agrupado) {
                        // Intentar obtener desde _manifiestos_originales_json primero
                        /* $manifiestos_originales = null;
                        if (isset($man['_manifiestos_originales_json']) && !empty($man['_manifiestos_originales_json'])) {
                            $manifiestos_originales = json_decode($man['_manifiestos_originales_json'], true);
                        }*/
                        // Si no, intentar desde _manifiestos_originales
                        if ($manifiestos_originales === null && isset($man['_manifiestos_originales'])) {
                            if (is_array($man['_manifiestos_originales'])) {
                                $manifiestos_originales = $man['_manifiestos_originales'];
                            } elseif (is_string($man['_manifiestos_originales'])) {
                                $manifiestos_originales = json_decode($man['_manifiestos_originales'], true);
                            }
                        }
                        if (is_array($manifiestos_originales)) {
                            foreach ($manifiestos_originales as $man_original) {
                                $cod = is_array($man_original) ? (isset($man_original['Man_Cod']) ? $man_original['Man_Cod'] : null) : $man_original;
                                if ($cod !== null && $cod !== '') {
                                    $manifiestos_cods[] = $cod;
                                }
                            }
                        }
                    }
                    if (empty($manifiestos_cods)) {
                        $manifiestos_cods[] = $man['Man_Cod'];
                    }
                    $respuesta = array(
                        'success' => true,
                        'Vet_Impr' => "" /*. (!empty($reportes[1]) ? "$reportes[1]?Vet_Cod=" : "") . "$Vet_Cod"*/,
                        'Vet_Cod' => $Vet_Cod,
                        'Vet_Num' => $Vet_Num,
                        'Vet_Fec' => $Caj_Fec,
                        'Tic_Des' => $Tic_Txt,
                        'Man_Cod' => $es_agrupado ? implode(', ', $manifiestos_cods) : $man['Man_Cod'],
                        'Man_Cods' => $manifiestos_cods
                    );
                    // No agregamos aún a resultados_exitosos, lo haremos después del commit
                } else {
                    $manifiestos_cods = array();
                    if ($es_agrupado) {
                        // Intentar obtener desde _manifiestos_originales_json primero
                        /* $manifiestos_originales = null;
                        if (isset($man['_manifiestos_originales_json']) && !empty($man['_manifiestos_originales_json'])) {
                            $manifiestos_originales = json_decode($man['_manifiestos_originales_json'], true);
                        }*/
                        // Si no, intentar desde _manifiestos_originales
                        if ($manifiestos_originales === null && isset($man['_manifiestos_originales'])) {
                            if (is_array($man['_manifiestos_originales'])) {
                                $manifiestos_originales = $man['_manifiestos_originales'];
                            } elseif (is_string($man['_manifiestos_originales'])) {
                                $manifiestos_originales = json_decode($man['_manifiestos_originales'], true);
                            }
                        }
                        if (is_array($manifiestos_originales)) {
                            foreach ($manifiestos_originales as $man_original) {
                                $cod = is_array($man_original) ? (isset($man_original['Man_Cod']) ? $man_original['Man_Cod'] : null) : $man_original;
                                if ($cod !== null && $cod !== '') {
                                    $manifiestos_cods[] = $cod;
                                }
                            }
                        }
                    }
                    if (empty($manifiestos_cods)) {
                        $manifiestos_cods[] = $man['Man_Cod'];
                    }
                    $errores_manifiestos[] = array(
                        'Man_Cod' => $es_agrupado ? implode(', ', $manifiestos_cods) : $man['Man_Cod'],
                        'Man_Cods' => $manifiestos_cods,
                        'error' => "No se ha logrado realizar la Transaccion: " . $obBD_conIns->MsgError,
                        'error_bd' => $obBD_conIns->MsgError
                    );
                }
                //Generar pagos con anticipos a las facturas 
                // 1. CARGAR LOS ANTICIPOS DE ESTE CLIENTE
                $var_mes = explode('-', $Com_Fec);
                $Tia_Cod = 17; //Tipo de asiento
                $Com_Val = $vet_total;
                $Num_Doc = 'NULL';

                $response['save_pago_anticipos'] = $obBD_con1->getArrayConsulta(32, array('Cli_Cod' => $Cli_Cod, 'Pla_Cod' =>  $man['Pla_Cod']), $obBD_conexion);
                // VALIDACIÓN: Calcular saldo total disponible de anticipos
                $saldo_total_disponible = 0;
                foreach ($response['save_pago_anticipos'] as $antVal) {
                    // Obtener los movimientos de cada anticipo
                    $movimientos = $obBD_con1->getArrayConsulta('pag_anticipo_cli.selectWhere', array('where' => array('Ant_Cod' => $antVal['Ant_Cod'])), $obBD_conexion);
                    foreach ($movimientos as $mov) {
                        if ($mov['Pac_Est'] != 'C') { // Solo los no consumidos
                            // Calcular saldo real del movimiento (valor original - consumos previos)
                            $consumos = $obBD_con1->getRowConsulta(56, array('Pac_Cod' => $mov['Pac_Cod']), $obBD_conexion);
                            $consumido = isset($consumos['total_consumido']) ? (float)$consumos['total_consumido'] : 0;
                            $saldo_mov = (float)$mov['Pac_Val'] - $consumido;
                            if ($saldo_mov > 0) {
                                $saldo_total_disponible += $saldo_mov;
                            }
                        }
                    }
                }
                // Si el saldo disponible no cubre el total de la factura, error
                if (round($saldo_total_disponible, 2) < round($vet_total, 2)) {
                    throw new Exception("Saldo insuficiente de anticipos para realizar el pago de la factura. Disponible: $" . number_format($saldo_total_disponible, 2) . " - Requerido: $" . number_format($vet_total, 2));
                }
                //2. GENERAR LOS PAGOS CON ANTICIPOS A LAS FACTURAS
                $Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod['Pec_Cod'], $var_mes[1], $obBD_conexion);
                $Com_Con = 'ABONO FACTS./ ' . $Vet_Num . '  /';
                $Com_Obs = 'ABONO FACTS./ ' . $Vet_Num . '  /';
                // VERIFICACION DEL REGISTRO DEL COMPROBANTE EN LA BASE DE DATOS
                // Ejecutar la consulta de inserción y obtener respuesta/éxito
                $resultado_insert = $obBD_conIns->operacionobBD(51, array(
                    'Pec_Cod' => $Pec_Cod['Pec_Cod'],
                    'Prv_Cod' => 'NULL',
                    'Cli_Cod' => $Cli_Cod,
                    'Com_Num' => $Com_Num,
                    'Com_Fec' => $Com_Fec,
                    'Com_Con' => $Com_Con,
                    'Com_Val' => $Com_Val,
                    'Com_Obs' => $Com_Obs,
                    'Tia_Cod' => $Tia_Cod,
                    'Num_Doc' => $Num_Doc
                ), $obBD_conexionIns);
                $ultimo_comprobante = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                if (!isset($cuenta_ant['Pld_Cod']) || empty($cuenta_ant['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable de: <u>Anticipos clientes</u>!');
                // Usar el Pld_Cod de param_manifiesto
                $Pld_Cod_Pago = $param_manifiesto['Pld_Cod'];
                $pago['Pag_Cod'] = 20; //Tipo pago anticipo
                $pago['concepto'] = 'ABONO FACTS./ ' . $Vet_Num . '  /';
                $pago['Glosa'] = 'ABONO FACTS./ ' . $Vet_Num . '  /';
                $pago['Debe'] = $vet_total;
                $pago['Haber'] = $vet_total;
                $pago['Pld_Cod'] = $Pld_Cod_Pago; //Cuenta para los pagos de ventas
                //Aqui se debe registrar con el anticipo que
                $obBD_conIns->operacionobBD(52, array(
                    'Com_Cod' => $ultimo_comprobante,
                    'Asi_Deh' => 'D',
                    'Asi_Con' => $pago['concepto'],
                    'Asi_Glo' => $pago['Glosa'],
                    'Asi_Val' => $pago['Debe'],
                    'Pld_Cod' => /*$pago['Pld_Cod']*/ $cuenta_ant['Pld_Cod']
                ), $obBD_conexionIns);
                $ultimo_asiento = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                $var_pag = (float)$pago['Debe']; //valor total a pagar de la factura
                $cntcpp = 0;
                //Regsitrar detalle de pagos cuentas por cobrar
                $obBD_conIns->operacionobBD(53, array(
                    'Cpc_Cod' => $Cpc_Cod, //cuentas por cobrar
                    'Pag_Cod' => $pago['Pag_Cod'],
                    'Com_Cod' => $ultimo_comprobante,
                    'Cpc_Fec' => $Com_Fec,
                    'Cpc_Val' => $var_pag,
                    'Cpc_Obs' => $pago['Glosa'],
                    'Asi_Cod' => $ultimo_asiento
                ), $obBD_conexionIns);
                $ultimo_dcc_cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                $valgrd = $var_pag;
                $var_pag = "none";
                $contador = 0;
                $monto_pendiente = (float)$vet_total;
                foreach ($response['save_pago_anticipos'] as $pagoAnt) {
                    // Trae todos los movimientos (pagos) asociados al Anticipo
                    $ctsCli =  $obBD_con1->getArrayConsulta('pag_anticipo_cli.selectWhere', array('where' => array('Ant_Cod' => $pagoAnt['Ant_Cod'])), $obBD_conexion);
                    // Sumar saldo disponible por anticipos para este Ant_Cod, considerando sólo los no CONSUMIDOS.
                    $saldo_anticipo = 0;
                    foreach ($ctsCli as $ctsc_sum) {
                        if ($ctsc_sum['Pac_Est'] != 'C') {
                            $saldo_anticipo += (float)$ctsc_sum['Pac_Val'];
                        }
                    }
                    // Si ya no necesito cubrir monto, salto
                    if ($monto_pendiente <= 0) {
                        break;
                    }
                    // Ahora recorro los movimientos PAG_ANTICIPO_CLI para consumir uno por uno el saldo
                    foreach ($ctsCli as &$ctsc) {
                        // Saltar consumidos
                        if ($ctsc['Pac_Est'] == 'C') continue;
                        $valor_a_utilizar = 0.0;
                        // Calcular el saldo REAL disponible del movimiento (valor original - consumos previos)
                        $consumos_previos = $obBD_con1->getRowConsulta(56, array('Pac_Cod' => $ctsc['Pac_Cod']), $obBD_conexion);
                        $total_consumido = isset($consumos_previos['total_consumido']) ? (float)$consumos_previos['total_consumido'] : 0;
                        $valor_movimiento = (float)$ctsc['Pac_Val'] - $total_consumido;
                        // Si ya no tiene saldo disponible, saltar
                        if ($valor_movimiento <= 0) {
                            continue;
                        }
                        // Si el movimiento permite cubrir completamente el pendiente
                        if ($valor_movimiento >= $monto_pendiente) {
                            $valor_a_utilizar = $monto_pendiente;
                        } else { // Si solo cubre parcialmente, usar todo el anticipo parcial
                            $valor_a_utilizar = $valor_movimiento;
                        }
                        if ($valor_a_utilizar > 0) {
                            // Registrar detalle cruce/uso de anticipo con comprobante
                            $obBD_conIns->operacionobBD(55, array(
                                'Ddc_Val' => $valor_a_utilizar,
                                'Ddc_Obs' => $pago['Glosa'],
                                'Ant_Cod' => $pagoAnt['Ant_Cod'],
                                'Dcc_Cod' => $ultimo_dcc_cod,
                                'Pac_Cod' => $ctsc['Pac_Cod'],
                                'Com_Cod' => $ultimo_comprobante
                            ), $obBD_conexionIns);
                            $nuevoEstado = 'U';
                            // Comparar con tolerancia para evitar problemas de precisión decimal
                            if (abs($valor_a_utilizar - $valor_movimiento) < 0.01) {
                                $nuevoEstado = 'C';
                            }
                            $obBD_conIns->operacionobBD('pag_anticipo_cli.update', array('Pac_Cod' => $ctsc['Pac_Cod'], 'Ant_Cod' => $ctsc['Ant_Cod'], 'Pac_Est' => $nuevoEstado), $obBD_conexionIns);
                            $monto_pendiente -= $valor_a_utilizar;
                            // Si el movimiento se consumió completamente, revisar si debo cambiar el estado ANT_COD completo
                            if ($nuevoEstado == 'C') {
                                // Verifico si todos los movimientos del Anticipo han sido consumidos. Si sí, Anticipo queda 'C'
                                $todos_consumidos = true;
                                foreach ($ctsCli as $_ctstmp) {
                                    if ($_ctstmp['Pac_Est'] != 'C' && $_ctstmp['Pac_Cod'] != $ctsc['Pac_Cod']) {
                                        $todos_consumidos = false;
                                        break;
                                    }
                                }
                                if ($todos_consumidos) {
                                    $obBD_conIns->operacionobBD(54, array('Ant_Cod' => $pagoAnt['Ant_Cod'], 'Ant_Est' => "C"), $obBD_conexionIns);
                                }
                            } else if ($nuevoEstado == 'U') {
                                $obBD_conIns->operacionobBD(54, array('Ant_Cod' => $pagoAnt['Ant_Cod'], 'Ant_Est' => "U"), $obBD_conexionIns);
                            }
                        }
                        // Si ya pagué todo lo necesario salgo
                        if ($monto_pendiente <= 0) {
                            break;
                        }
                    }
                    unset($ctsc);
                }
                // El campo 'Haber' debe tomar el valor de 'Debe' para el asiento en el haber
                $obBD_conIns->operacionobBD(52, array('Com_Cod' => $ultimo_comprobante, 'Asi_Deh' => 'H',  'Asi_Con' => $pago['concepto'], 'Asi_Glo' => $pago['Glosa'],  'Asi_Val' =>  $pago['Haber'], 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexionIns);
                $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
                // Generar XML, FIRMAR Y AUTORIZAR DESPUÉS del commit (los datos ya están confirmados)
                // Estos procesos NO deben afectar las transacciones ya confirmadas
                if (!empty($respuesta) && $autorizaci['Aut_Tem'] == 'E' && $input_autorizacion == '') {
                    try {
                        // Generar XML
                        if (!isset($obBD_elect)) {
                            require_once('../../facturacion/LOGICA/fac_log_electronica.php');
                            if (!isset($obBD_conexionElect)) {
                                $obBD_conexionElect = new Class_Log_Conexion_Elect($Ses_Dat_Dis);
                            }
                            $obBD_elect =  new Class_Log_Datos_Factura_Elect();
                        }
                        $xml = $obBD_elect->createXmlFactura($Vet_Cod, $Aut_Cod, $claveAcceso, $obBD_conexionElect);
                        if (empty($xml)) {
                            throw new Exception("Error al generar el XML de la factura electrónica");
                        }
                        // FIRMAR FACTURA Y ENVIAR AL CORREO
                        $ruta_xmls = $APP_REAL_PATH . "../../facturacion/FRONT/$Ses_Emp_Cod/"; //obtener ruta xml
                        require_once('../../Librerias/FactElect/FirmaElectronica.php');
                        $DocElect = new FirmaElectronica();
                        $DocElect->setProduction(($configs['Cof_Fac'] * 1 == 2));
                        $xml_path = $ruta_xmls . $claveAcceso; //Ruta del xml 
                        $DocElect->setFileSignedPath($xml_path . '_F.xml'); //enviar a firmar xml
                        //Datos para firma factura
                        $response_firma = array();
                        $response_firma['data'] = $obBD_con1->getArrayConsulta(68, $Vet_Cod, $obBD_conexion);
                        $response_firma['Doc_Fir']   = $response_firma['data'][0]['Doc_Fir'];
                        $response_firma['Doc_Env']   = $response_firma['data'][0]['Doc_Env'];
                        $response_firma['Doc_Mail']  = $response_firma['data'][0]['Doc_Mail'];
                        $response_firma['Doc_Num']   = $response_firma['data'][0]['Doc_Num'];
                        $response_firma['Doc_Cod']   = $response_firma['data'][0]['Doc_Cod'];
                        $response_firma['Doc_Aut']   = $response_firma['data'][0]['Doc_Aut'];
                        $response_firma['Doc_Xml']   = $response_firma['data'][0]['Doc_Xml'];
                        $response_firma['Doc_Sri']   = $response_firma['data'][0]['Doc_Sri'];
                        $response_firma['Email']     = $response_firma['data'][0]['Email'];
                        $response_firma['Aut_Cod_Est']  = " <span style='color:red'>NO SE PUDO AUTORIZAR EL DOCUMENTO</span>";
                        //Fin datos para firma factura
                        // Firmar XML
                        if (is_readable($xml_path . ".xml")) {
                            $doc = $DocElect->sendToSign($xml_path . ".xml", $ruta_xmls . $llave['Lla_Rut'], $llave['Lla_Cla']);
                            if ($doc['success'] == true && !empty($doc['xml'])) {
                                $response_firma['Doc_Fir'] = 'S';
                            } else {
                                throw new Exception('Error al Firmar el documento: ' . (isset($doc['message']) ? $doc['message'] : 'Error desconocido'));
                            }
                        } else {
                            throw new Exception("Error: No se encontró el archivo XML");
                        }
                        // Enviar al SRI
                        if ($response_firma['Doc_Fir'] == 'S' && $response_firma['Doc_Env'] != 'S') {
                            $result = $DocElect->sendToSri();
                            if ($result['success'] == true) {
                                $response_firma['Doc_Env'] = 'S';
                            } else {
                                throw new Exception('Error al enviar el documento al SRI: ' . (isset($result['message']) ? $result['message'] : 'Error desconocido') . (!empty($result['informacionAdicional']) ? ' - ' . $result['informacionAdicional'] : ''));
                            }
                        }
                        // Autorizar en el SRI
                        if ($response_firma['Doc_Fir'] == 'S' && $response_firma['Doc_Env'] == 'S' && $response_firma['Doc_Aut'] != 'S') {
                            $DocElect->setFileAutorized($xml_path . '_A.xml');
                            $result = $DocElect->autorizarSri($response_firma['Doc_Xml']);
                            if ($result['success'] == true) {
                                $response_firma['Doc_Aut'] = 'S';
                                $response_firma['Selection'] = 'N';
                                $response_firma['numeroAutorizacion'] = $result['numeroAutorizacion'];
                                $response_firma['Doc_Cod'] =  $Vet_Cod;
                                $obBD_con1->operacionobBD(173, $response_firma, $obBD_conexion);
                                if (is_readable($xml_path . ".xml")) unlink($xml_path . ".xml");
                                if (is_readable($xml_path . "_F.xml")) unlink($xml_path . "_F.xml");
                                $response_firma['Type'] = 'VENTAS';
                                $response_firma['Doc_Mail'] = 'N';
                                $response_firma['Aut_Cod_Est']  = "<span style='color:green;'>SI</span>";
                                $response_firma['Xml_Path'] = ("../../facturacion/FRONT/" . $Ses_Emp_Cod . '/' . $claveAcceso . '_A.xml');
                                // Enviar por correo
                                if (!empty($response_firma['Email']) && trim($response_firma['Email']) != '' && trim($response_firma['Email']) != '-' && trim($response_firma['Email']) != '0') {
                                    require_once('../../facturacion/LOGICA/fac_log_electronica.php');
                                    $obBD_elect = getClassElect($response_firma['Type']);
                                    $response_firma['Doc_Mail'] = $obBD_elect->sendMailDoc($response_firma['Doc_Cod'], $response_firma['Email'], NULL, $obBD_conexion, true) == true ? 'S' : 'N';
                                    if ($response_firma['Doc_Mail'] == 'N') {
                                        // No lanzar excepción por error de email, solo registrar
                                        $respuesta['error_firma'] = "Se autorizó correctamente pero no se pudo enviar el email";
                                    }
                                }
                                // Agregar información de autorización exitosa a la respuesta
                                $respuesta['Vet_Xmls'] = baseUrl("../../facturacion/FRONT/" . $Ses_Emp_Cod . '/' . $claveAcceso . '_A.xml');
                                $respuesta['xml'] = base64_encode($xml);
                                $respuesta['Doc_Aut'] = 'S';
                                $respuesta['numeroAutorizacion'] = $result['numeroAutorizacion'];
                            } else {
                                throw new Exception('Error al autorizar el documento: ' . (isset($result['message']) ? $result['message'] : 'Error desconocido') . (!empty($result['informacionAdicional']) ? ' - ' . $result['informacionAdicional'] : ''));
                            }
                        }
                    } catch (Exception $exFirma) {
                        // Error en generación, firma o autorización del XML - NO afecta las transacciones
                        // La factura se registró correctamente, pero falló el proceso electrónico
                        $error_firma = "Factura N° " . $Vet_Num . " registrada correctamente, pero no se pudo completar el proceso de facturación electrónica: " . $exFirma->getMessage();
                        $errores_manifiestos[] = array(
                            'Man_Cod' => isset($man['Man_Cod']) ? $man['Man_Cod'] : '',
                            'error' => $error_firma,
                            'tipo' => 'autorizacion' // Tipo de error para diferenciarlo
                        );
                        // Agregar el error también a la respuesta para mostrarlo, pero mantener success = true
                        if (!isset($respuesta['error_firma'])) {
                            $respuesta['error_firma'] = $error_firma;
                        }
                        // Marcar que la factura se registró pero no se autorizó
                        $respuesta['registrada'] = true;
                        $respuesta['autorizada'] = false;
                    }
                }
                // Agregar la respuesta a resultados_exitosos SIEMPRE que se haya registrado la factura
                // (incluso si falló la autorización, la factura se cuenta como exitosa)
                if (!empty($respuesta)) {
                    $resultados_exitosos[] = $respuesta;
                }
            } catch (Exception $exMan) {
                // Capturar errores ocurridos en el manejo de cada manifiesto
                $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
                $errores_manifiestos[] = array('Man_Cod' => isset($man['Man_Cod']) ? $man['Man_Cod'] : '',  'error' => $exMan->getMessage());
            }
        }
        // Si no hay ningún resultado exitoso, y existen errores, reportar el primero como mensaje principal
        $response = array();
        if (count($resultados_exitosos) > 0) {
            $response = $resultados_exitosos[0];
            // Si hay resultados exitosos, mantener success = true
            $response['success'] = true;
            // Contar cuántas facturas se registraron correctamente
            $count_registradas = count($resultados_exitosos);
            // Contar cuántas se autorizaron
            $count_autorizadas = 0;
            foreach ($resultados_exitosos as $exitoso) {
                if (isset($exitoso['Doc_Aut']) && $exitoso['Doc_Aut'] == 'S') {
                    $count_autorizadas++;
                }
            }
            // Crear mensaje descriptivo
            if ($count_registradas == $count_autorizadas) {
                $response['message'] = "Registradas correctamente: " . $count_registradas;
            } else {
                $count_no_autorizadas = $count_registradas - $count_autorizadas;
                $response['message'] = "Registradas correctamente: " . $count_registradas . " (No autorizadas: " . $count_no_autorizadas . ")";
            }
        } else if (count($errores_manifiestos) > 0) {
            $primerError = $errores_manifiestos[0];
            $msg = isset($primerError['error']) && $primerError['error'] ? $primerError['error'] : "No se ha logrado realizar ninguna Transaccion";
            $response = array('success' => false, 'message' => $msg);
        } else {
            $response = array('success' => false, 'message' => "No se ha logrado realizar ninguna Transaccion");
        }

        $response['errores'] = $errores_manifiestos;
        $response['exitosos'] = $resultados_exitosos;
        $response['total_registradas'] = count($resultados_exitosos);

        echo json_encode($response);
        exit();
    } catch (Exception $exCritico) {
        // Captura aquí cualquier excepción crítica (por fuera del ciclo)
        $response = array('success' => false, 'message' => 'Error critico: ' . $exCritico->getMessage(), 'errores' => $errores_manifiestos);
        echo json_encode($response);
        exit();
    }
}

?>
<!DOCTYPE html>
<html>

<head>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Manifiestos a Facturas [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script>
        <?php
        $array_documentos = $obBD_con1->getArrayConsulta(3, $rs_Punto['Pun_Cod'], $obBD_conexion);
        // Obtener información de autorización para verificar si requiere clave de acceso
        $autorizaci_info = $obBD_con1->getRowConsulta(49, $rs_Punto['Pun_Cod'] . '*' . '' . '*' . 1, $obBD_conexion);
        ?>
        var array_documentos = <?php echo json_encode($array_documentos); ?>,
            ivas_venta = <?php echo json_encode($ivas); ?>;
        var edicion_ventas = true,
            vet_num_ant = 0,
            tic_cod_ant = 0;
        var docs, items, pagos, data = [],
            Vet_Index = 1,
            Vet_Selected, index, Cof_Con = '<?php echo $configs['Cof_Con']; ?>';
        var autorizacionInfo = <?php echo json_encode($autorizaci_info); ?>;
    </script>

    <script language="javascript" src="../../framework/plugins/validadorCedulaRucFinal.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/man_fac_mas.js?x=3"></script>
    </script>

    <style>
        .footrow td[aria-describedby="documento_Cop_Imp"],
        .footrow td[aria-describedby="documento_Cop_Pru"] {
            padding: 0 !important;
        }

        .footerFact {
            text-align: right;
            width: 100%;
        }

        .footerFact input[type=text],
        .footerFact label,
        .footerFact textarea,
        .footerFact select {
            height: 19px;
            width: 100% !important;
            display: block;
            margin-bottom: 0px !important;
            margin-top: 0px !important;
            text-align: right;
        }

        .footerFact input[type=text] {
            padding: 0;
        }

        .footerFact textarea {
            text-align: left;
            height: 75px !important;
        }

        .footerFact select {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
            display: inline;
        }

        .footerFact label {
            height: 19px;
            line-height: 18px;
            padding-right: 5px;
        }

        .footerFact label.total,
        .footerFact input.total {
            background-color: #254463;
            color: white;
            font-size: 14px;
            border: none;
        }

        #Ret_Asu {
            vertical-align: middle;
            margin-top: -2px;
            padding: 5px;
            -ms-transform: scale(1.4);
            -moz-transform: scale(1.4);
            -webkit-transform: scale(1.4);
            -o-transform: scale(1.4);
        }

        #resultContent .resp {
            font-weight: 700;
            font-size: 30px;
            color: #3f3fc1;
            padding: 0;
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 32px;
        }

        #resultContent .resp span:first-child {
            color: darkgoldenrod;
            width: 100px;
            display: inline-block;
            margin-left: 42px;
        }

        .ret .input-group-btn button {
            padding: 1px 2px !important;
        }

        .ret {
            padding: 0 !important;
        }

        .footrow td[aria-describedby="items_Vet_Pru"],
        .footrow td[aria-describedby="items_Vet_Imp"] {
            padding: 0 !important;
        }
    </style>
</head>

<body>
    <input type="hidden" id="Emp_Cod" name="Emp_Cod" value="<?php echo  $Ses_Emp_Cod ?>">
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title ">&raquo; Convertir manifiestos a facturas </h3>

        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="documentoSearch">
                <form id="serachDocDorm" class="form-horizontal normal" action="javascript:$('#manifiestosGrid').Search('#serachDocDorm','searchDocument');">
                    <div class="row">
                        <input name="order" type="hidden" value="" />
                        <input name="fecha_inicio" type="hidden" value="" />
                        <input name="fecha_fin" type="hidden" value="" />
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Búsqueda</legend>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                    <div class="col-xs-10 radioset opt_search">
                                        <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setOpt(this.value); setfocus(this.form.search); verificarFacturarPorGrupo();" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Cliente&nbsp;&nbsp;&nbsp;</label>
                                        <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setOpt(this.value); setfocus(this.form.search); verificarFacturarPorGrupo();" alt="" /><label for="radsc3">&nbsp;&nbsp;No. Manifiesto&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                    <div class="col-xs-7">
                                        <div class="input-group">
                                            <input name="search" id="search_input" readonly type="text" size="50" maxlength="50" placeholder="Seleccione un cliente..." autofocus class="form-control input-sm clearable submit" style="background-color: #f9f9f9; cursor: not-allowed;" />
                                            <!--span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento" tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span-->
                                            <span class="input-group-btn">
                                                <button type="button" onclick="limpiarBusquedaGeneral()" class="btn btn-warning btn-sm" title="Limpiar Búsqueda" tabindex="-1"><span class="glyphicon glyphicon-erase"></span></button>
                                                <button type="button" id="btnAbrirModalClientes" class="btn btn-primary btn-sm" title="Buscar Cliente" tabindex="-1"><span class="glyphicon glyphicon-user"></span></button>
                                            </span>
                                        </div>
                                    </div><input type="text" tabindex="-1" style="display:none;" />
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-sm">Planta:</label>
                                    <div class="col-xs-6">
                                        <div class="input-group">
                                            <select name="Pla_Cod" class="form-control input-sm" onchange="filtrarPorPlanta(); verificarFacturarPorGrupo();">
                                                <option value="">
                                                    << TODOS>>
                                                </option>
                                            </select>
                                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento" tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs"></label>
                                    <div class="col-xs-8">
                                        <div class="alert alert-info" style="margin-bottom: 0; padding: 12px 15px; border-left: 4px solid #31708f; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <i class="glyphicon glyphicon-info-sign" style="font-size: 18px; color: #31708f; margin-right: 8px;"></i>
                                            <strong style="color: #31708f; font-size: 13px;">Nota Importante:</strong>
                                            <span style="color: #555; font-size: 12px;"> Para habilitar la opción de <strong style="color: #31708f;">Facturar individual o por Grupo </strong>, debe filtrar por <strong style="color: #d9534f;">Cliente</strong> y seleccionar una <strong style="color: #d9534f;">Planta</strong>.</span>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="form-group" style="margin-top: 10px;">
                                <label class="col-xs-5 control-label label-xs">Clientes pendientes a facturar:</label>
                                <div class="col-xs-7">
                                    <button type="button" id="btn_sin_facturar" class="btn btn-primary btn-sm" title="Manifiestos por facturar (agrupado)">
                                        <span class="glyphicon glyphicon-file"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtros</legend>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Manifiesto:</label>
                                    <div class="col-xs-4">
                                        <select name="Man_Tip" class="form-control input-xs">
                                            <option value="">
                                                << TODOS>>
                                            </option>
                                            <option value="F"> Facturados </option>
                                            <option value="A"> Aprobados </option>
                                            <option value="P"> Pendientes </option>
                                            <option value="R"> Rechazados </option>
                                            <option value="GE"> Garita Entrada </option>
                                            <option value="GS"> Para Facturar </option>
                                        </select>
                                    </div>
                                    <div class="col-xs-6">
                                        <div id="manifiestos_facturar" style="padding: 5px 10px; min-height: 30px; position: relative;">
                                            <div id="manifiestos_facturar_alert" style="display: none;">
                                                <span id="manifiestos_facturar_content" role="button" tabindex="0" style="color: #0044aa; font-weight: 600;"></span>
                                            </div>
                                            <div id="manifiestos_facturar_tool" style="display: none; position: absolute; top: 100%; left: 0; z-index: 1050; min-width: 280px; margin-top: 4px; padding: 10px; background: #fff; border: 1px solid #f0ad4e; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.15);"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Periodo:</label>
                                    <div class="col-xs-2">
                                        <select name="Pec_Cod" id="Pec_Cod" class="form-control input-xs search_pec" onchange="gestionarFiltrosFecha(this)">
                                            <option value="">
                                                << FECHAS>>
                                            </option>
                                            <?php $rs_perio = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion);
                                            foreach ($rs_perio as $row) { ?>
                                                <option value="<?php echo $row['Pec_Cod']; ?>" data-inicio="<?php echo $row['Pec_Fei']; ?>" data-fin="<?php echo $row['Pec_Fef']; ?>"><?php echo $row['Anio']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <label class="col-xs-2 control-label label-xs">Mes:</label>
                                    <div class="col-xs-2">
                                        <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec" disabled="disabled">
                                            <option value="">
                                                << TODOS>>
                                            </option>
                                            <?Php for ($i = 1; $i <= 12; $i++) { ?><option <?php if ($i == $mes) {
                                                                                                echo "selected=''";
                                                                                            } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1); ?></option><?Php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-sm rangedate">Rango:</label>
                                    <div class="col-sm-10">
                                        <div class="input-group input-group-sm dateRangeInputs">
                                            <span class="range input-group-addon alert-info">Desde</span>
                                            <input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control range" required="" value="<?php echo isset($_GET['Fec_Ini']) ? $_GET['Fec_Ini'] : date('Y-01-01'); ?>" />
                                            <span class="range input-group-addon alert-info">Hasta</span>
                                            <input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control range" required="" value="<?php echo isset($_GET['Fec_Fin']) ? $_GET['Fec_Fin'] : ''; ?>" />
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Facturar por Grupo:</label>
                                    <div class="col-xs-2">
                                        <input type="checkbox" name="fac_group" id="fac_group" disabled>
                                    </div>
                                    <label class="col-xs-2 control-label label-xs">Individual:</label>
                                    <div class="col-xs-2">
                                        <input type="checkbox" name="fac_individual" id="fac_individual" disabled>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Informacion de Facturación</legend>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Por Facturar:</label>
                                    <div class="col-xs-2">
                                        <input type="text" name="cant_sin_facturar" id="cant_sin_facturar" class="form-control input-xs" style="color: #8a6d3b; background-color: #fcf8e3; border-color: #faebcc; font-weight: bold; text-align: center; font-size: 15px;" readonly>
                                    </div>
                                    <label class="col-xs-2 control-label label-xs">Pendientes:</label>
                                    <div class="col-xs-2">
                                        <input type="text" name="" id="cant_pend_fact" class="form-control input-xs" style="color: #a94442; background-color: #f2dede; border-color: #ebccd1; font-weight: bold; text-align: center; font-size: 15px;" readonly>
                                    </div>
                                    <label class="col-xs-2 control-label label-xs">Facturados:</label>
                                    <div class="col-xs-2">
                                        <input type="text" name="" id="cant_facturados" class="form-control input-xs" style="color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; font-weight: bold; text-align: center; font-size: 15px;" readonly>
                                    </div>
                            </fieldset>

                            <div class="row" style="margin-top: 15px;">
                                <div class="col-xs-5">
                                    <div id="manifiestos_facturar" style="padding: 10px; min-height: 40px;">
                                        <div class="alert alert-info" style="margin-bottom: 0; display: none;" id="manifiestos_facturar_alert">
                                            <i class="glyphicon glyphicon-info-sign" style="margin-right: 8px;"></i>
                                            <span id="manifiestos_facturar_content"></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- La cantidad se actualiza al dar Buscar con Cédula/RUC y Planta (loadComplete del grid) -->
                                <div class="col-xs-7">
                                    <div id="infoFacturacion" style="padding: 10px; min-height: 40px;">
                                        <div class="alert alert-success" style="margin-bottom: 0; display: none;" id="infoFacturacionContent">
                                            <i class="glyphicon glyphicon-info-sign" style="margin-right: 8px;"></i>
                                            <span id="infoFacturacionTexto"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <div id="loadingMessage"></div>
                <div id="message"></div>
                <div style="min-height: 300px;">
                    <table id="manifiestosGrid"></table>
                    <table id="manifiestosGridPager"></table>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong><span class="glyphicon glyphicon-stop green"></span> Facturados | <span class="glyphicon  glyphicon-stop red">
                            </span> Anulados/Inactivos | <span class="glyphicon  glyphicon-stop yellow"></span> Pendientes | <span class="glyphicon  glyphicon-stop blue"></span> Aprobados</div>
                </div>
                <br>
                <div>
                    <button type="button" id="btn_register" onclick="generarFacturas()" class="btn btn-success btn-sm" title="Generar facturas"><i class="glyphicon glyphicon-download-alt"></i> <span>Generar facturas</span></button>
                </div>
                <!-- Diálogo de búsqueda de clientes (mismo diseño que plaDialog en man_fac_man.php) -->
                <div id="clieDialog" title="B&uacute;squeda de Clientes">
                    <form class="form-horizontal normal"></form>
                </div>
                <!-- Diálogo: Manifiestos por facturar (mismo formato que clieDialog) -->
                <div id="sfDialog" title="Manifiestos por facturar (agrupado)">
                    <form class="form-horizontal normal">
                        <div class="row" style="margin: 6px 0 10px 0;">
                            <div class="col-sm-8">
                                <label class="control-label">Rango</label>
                                <div class="input-group input-group-sm dateRangeInputs">
                                    <span class="range input-group-addon alert-info">Desde</span>
                                    <input type="text" name="Fec_Ini" id="sf_fec_ini" class="form-control range" required="" placeholder="YYYY-MM-DD">
                                    <span class="range input-group-addon alert-info">Hasta</span>
                                    <input type="text" name="Fec_Fin" id="sf_fec_fin" class="form-control range" required="" placeholder="YYYY-MM-DD">
                                </div>
                            </div>
                            <div class="col-sm-4" style="padding-top: 22px;">
                                <button type="button" class="btn btn-primary btn-sm" id="btnSfFiltrar">
                                    <span class="glyphicon glyphicon-search"></span> Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <script>
                    // Total de manifiestos para facturar (según consulta al filtrar por cédula y planta)
                    var totalManifiestosAFacturar = 0;

                    $('#manifiestosGrid').createGrid({
                        caption: 'Resultado de la Búsqueda',
                        height: 350,
                        rowNum: 1000,
                        rowList: [1000, 2000, 3000, 6000],
                        pager: '#manifiestosGridPager',
                        sortname: 'Man_Fes',
                        sortorder: 'desc',
                        viewrecords: true,
                        gridview: true,
                        footerrow: true,
                        userDataOnFooter: false,
                        datatype: "local",
                        caption: 'Resultados <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar </option><option value="order by manifiesto.Man_Fes DESC ">Fecha Creacion</option><option value="order by Man_Num DESC ">Num. Documento DESC</option><select>&nbsp;</div>',
                        colModel: [{
                                label: 'Cód. Int.',
                                name: 'Man_Cod',
                                width: 25,
                                align: "center",
                                key: true,
                                align: "center"
                            },
                            {
                                label: 'Fec.Emis',
                                name: 'Man_Fes',
                                width: 40,
                                align: "center"
                            },
                            {
                                label: 'Pla_Cod',
                                name: 'Pla_Cod',
                                width: 120,
                                align: "center",
                                hidden: true
                            },
                            {
                                label: 'Fec.Arribo',
                                name: 'Man_Fea',
                                width: 40,
                                align: "center"
                            },
                            {
                                label: 'No. Manifiesto',
                                name: 'Man_Num',
                                width: 25,
                                align: "center",
                                formatter: function(cellvalue, options, rowObject) {
                                    if (rowObject.Pla_Cod && cellvalue !== undefined && cellvalue !== '') {
                                        var n = String(cellvalue);
                                        while (n.length < 4) n = '0' + n;
                                        return 'M' + rowObject.Pla_Cod + '-' + n;
                                    }
                                    return cellvalue || '';
                                }
                            },
                            {
                                label: 'No. Fac',
                                name: 'Vet_Num',
                                width: 25,
                                align: "center"
                            },
                            {
                                label: 'Tipo',
                                name: 'est_manifiesto',
                                width: 35,
                                align: "center"
                            },
                            {
                                label: 'cod_cli',
                                name: 'Cli_Cod',
                                width: 120,
                                align: "center",
                                hidden: true
                            },

                            {
                                label: 'cod_ciu',
                                name: 'Ciu_Cod',
                                width: 120,
                                align: "center",
                                hidden: true
                            },
                            {
                                label: 'Cedula',
                                name: 'Prs_Ced',
                                width: 40,
                                align: "center"
                            },
                            {
                                label: 'Cliente',
                                name: 'cliente',
                                width: 120,
                                align: "center"
                            },
                            {
                                label: 'Planta',
                                name: 'Pla_Nom',
                                width: 120,
                                align: "center",
                                hidden: false
                            },
                            {
                                label: 'Estado',
                                name: 'Vet_Aut_Des',
                                width: 40,
                                align: "center",
                                formatter: function(cellvalue, options, rowObject) {
                                    if (rowObject && rowObject.Vet_Aut == 'S') {
                                        return '<span style="color: #28a745; font-weight: bold;">' + (cellvalue || 'Autorizada') + '</span>';
                                    } else {
                                        return '<span style="color: #dc3545; font-weight: bold;">' + (cellvalue || 'Sin autorizar') + '</span>';
                                    }
                                },
                                title: false
                            },
                            {
                                label: 'Usuario',
                                name: 'Usu_Cod',
                                width: 100,
                                align: "center",
                                hidden: true
                            },
                            {
                                label: 'Peso(tn)',
                                name: 'Man_Pes',
                                width: 30,
                                align: "center"
                            },
                            {
                                label: 'Pre.Uni',
                                name: 'Man_Pun',
                                width: 30,
                                align: "center"
                            },
                            {
                                label: 'Subtotal',
                                name: 'subtotal',
                                width: 30,
                                align: "center",
                                formatter: 'number',
                                formatoptions: {
                                    decimalSeparator: '.',
                                    thousandsSeparator: ',',
                                    decimalPlaces: 2,
                                    defaultValue: '0.00'
                                }
                            },
                            {
                                label: 'Iva_Cod',
                                name: 'Iva_Cod',
                                width: 30,
                                align: "center",
                                hidden: true
                            },
                            {
                                label: 'Iva_Por',
                                name: 'Iva_Por',
                                width: 30,
                                align: "center",
                                hidden: true
                            },
                            {
                                label: 'iva',
                                name: 'total_iva',
                                width: 30,
                                align: "center",
                                formatter: 'number',
                                formatoptions: {
                                    decimalSeparator: '.',
                                    thousandsSeparator: ',',
                                    decimalPlaces: 2,
                                    defaultValue: '0.00'
                                }
                            },
                            {
                                label: 'Total',
                                name: 'total',
                                width: 30,
                                align: "center",
                                formatter: 'number',
                                formatoptions: {
                                    decimalSeparator: '.',
                                    thousandsSeparator: ',',
                                    decimalPlaces: 2,
                                    defaultValue: '0.00'
                                }
                            },
                            {
                                label: '<input type="checkbox" id="select_all_man" title="Seleccionar todo" disabled>',
                                name: 'sel',
                                width: 20,
                                align: 'center',
                                viewable: true,
                                sortable: false,
                                title: false,
                                formatter: function(cellValue, options, rowObject) {
                                    return (rowObject && rowObject.est_manifiesto === 'Garita_Salida') ? '<input type="checkbox" class="row-select" data-id="' + options.rowId + '" disabled>' : '';
                                }
                            }

                            /* {
                                 label: '<input type="checkbox" id="select_all_man" title="Seleccionar todo" onclick="var checkboxes = document.querySelectorAll(\'.row-select\'); var allChecked = Array.from(checkboxes).every(cb => cb.checked); checkboxes.forEach(function(cb){ cb.checked = !allChecked; cb.dispatchEvent(new Event(\'change\')); });">',
                                 name: 'sel',
                                 width: 20,
                                 align: 'center',
                                 viewable: true,
                                 sortable: false,
                                 title: false,
                                 formatter: function(cellValue, options, rowObject) {
                                     return (rowObject && rowObject.Man_Tes === 'GS') ? '<input type="checkbox" class="row-select" data-id="' + options.rowId + '">' : '';
                                 }
                             }*/
                        ],
                        loadComplete: function(data) {
                            // Cantidad de manifiestos sin facturar (GS) respetando los filtros actuales
                            var op_opciones = $('#serachDocDorm input[name="op_opciones"]:checked').val() || '';
                            var plaCod = $('#serachDocDorm select[name="Pla_Cod"]').val() || '';
                            $('#manifiestos_facturar_content').text('Cargando...');
                            $('#manifiestos_facturar_alert').show();
                            $.get('', {
                                getCountManifiestosAFacturar: 1,
                                search: $('#serachDocDorm input[name="search"]').val() || '',
                                op_opciones: op_opciones,
                                Man_Tip: $('#serachDocDorm select[name="Man_Tip"]').val() || '',
                                Pla_Cod: plaCod,
                                Fec_Ini: $('#serachDocDorm input[name="Fec_Ini"]').val() || '',
                                Fec_Fin: $('#serachDocDorm input[name="Fec_Fin"]').val() || '',
                                Pec_Cod: $('#serachDocDorm select[name="Pec_Cod"]').val() || '',
                                Cmb_Mes: $('#serachDocDorm select[name="Cmb_Mes"]').val() || '',
                                fecha_inicio: $('#serachDocDorm input[name="fecha_inicio"]').val() || '',
                                fecha_fin: $('#serachDocDorm input[name="fecha_fin"]').val() || ''
                            }, function(resp) {
                                var totalGs = (resp && resp.total_gs !== undefined) ? parseInt(resp.total_gs, 10) : ((resp && resp.total !== undefined) ? parseInt(resp.total, 10) : 0);
                                var totalFact = (resp && resp.total_fact !== undefined) ? parseInt(resp.total_fact, 10) : 0;
                                var totalPend = (resp && resp.total_pend !== undefined) ? parseInt(resp.total_pend, 10) : 0;
                                totalManifiestosAFacturar = totalGs;
                                $('#manifiestos_facturar_content').text(totalGs + ' manifiesto(s) por facturar');
                                $('#cant_sin_facturar').val(totalGs);
                                $('#cant_facturados').val(totalFact);
                                $('#cant_pend_fact').val(totalPend);
                                actualizarInfoFacturacion();
                            }, 'json').fail(function() {
                                totalManifiestosAFacturar = 0;
                                $('#manifiestos_facturar_content').text('No se pudo obtener la cantidad.');
                                $('#cant_sin_facturar').val('0');
                                $('#cant_facturados').val('0');
                                $('#cant_pend_fact').val('0');
                            });

                            if ($.varValid(data)) {
                                for (var i = 0, z = data.rows.length; i < z; i++) {
                                    if (data.rows[i]['est_manifiesto'] == 'Garita_Salida') {
                                        $("#" + data.rows[i].Man_Cod + ' td:not(.jqgrid-rownum)').css({
                                            'background-color': '#b4d4e2ff',
                                            'color': '#000000'
                                        });
                                    }
                                    if (data.rows[i]['est_manifiesto'] == 'Facturado') $("#" + data.rows[i].Man_Cod + ' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                                    if (data.rows[i]['est_manifiesto'] == 'Pendiente') {
                                        $("#" + data.rows[i].Man_Cod + ' td:not(.jqgrid-rownum)').css({
                                            'background-color': 'rgba(225, 226, 180, 1)',
                                            'color': '#000000'
                                        });
                                    }
                                    if (data.rows[i]['est_manifiesto'] == 'Anulado') $("#" + data.rows[i].Man_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
                                }

                                // Cargar plantas de los clientes encontrados
                                var op_opciones = $('#serachDocDorm input[name="op_opciones"]:checked').val();
                                if (op_opciones == 'p' || op_opciones == 'c') {
                                    // Extraer Cli_Cod únicos de los resultados
                                    var cliCods = [];
                                    var cliCodsMap = {};
                                    for (var i = 0, z = data.rows.length; i < z; i++) {
                                        if (data.rows[i].Cli_Cod && !cliCodsMap[data.rows[i].Cli_Cod]) {
                                            cliCods.push(data.rows[i].Cli_Cod);
                                            cliCodsMap[data.rows[i].Cli_Cod] = true;
                                        }
                                    }
                                    // Si hay clientes, cargar sus plantas
                                    if (cliCods.length > 0) {
                                        $.ajax({
                                            url: '',
                                            type: 'GET',
                                            data: {
                                                getPlantasClientes: true,
                                                Cli_Cod: cliCods
                                            },
                                            dataType: 'json',
                                            success: function(plantas) {
                                                public $selectPla = $('select[name="Pla_Cod"]');
                                                // Guardar el valor actual si existe
                                                var valorActual = $selectPla.val();
                                                // Limpiar opciones excepto "TODOS"
                                                $selectPla.find('option:not(:first)').remove();

                                                // Agregar plantas
                                                if (plantas && plantas.length > 0) {
                                                    $.each(plantas, function(index, planta) {
                                                        $selectPla.append($('<option>', {
                                                            value: planta.Pla_Cod,
                                                            text: planta.Pla_Nom
                                                        }));
                                                    });

                                                    // Restaurar valor anterior si existe y está disponible
                                                    if (valorActual && $selectPla.find('option[value="' + valorActual + '"]').length > 0) {
                                                        $selectPla.val(valorActual);
                                                    }
                                                }
                                                // Verificar estado del checkbox después de cargar plantas
                                                verificarFacturarPorGrupo();
                                            },
                                            error: function() {
                                                console.error('Error al cargar plantas de clientes');
                                            }
                                        });
                                    }
                                }
                                // Verificar estado del checkbox después de cargar datos
                                verificarFacturarPorGrupo();

                                // Actualizar estado de los checkboxes después de cargar datos
                                actualizarEstadoCheckboxesColumna();

                                // Actualizar información de facturación después de cargar datos
                                actualizarInfoFacturacion();

                                // Calcular y mostrar totales en el footer
                                public $grid = $('#manifiestosGrid');
                                var subtotalSum = $grid.jqGrid('getCol', 'subtotal', false, 'sum') || 0;
                                var ivaSum = $grid.jqGrid('getCol', 'total_iva', false, 'sum') || 0;
                                var totalSum = $grid.jqGrid('getCol', 'total', false, 'sum') || 0;

                                // Formatear los valores con 2 decimales
                                subtotalSum = parseFloat(subtotalSum).toFixed(2);
                                ivaSum = parseFloat(ivaSum).toFixed(2);
                                totalSum = parseFloat(totalSum).toFixed(2);

                                // Establecer los totales en el footer
                                $grid.jqGrid('footerData', 'set', {
                                    cliente: '<div style="text-align:right; font-weight:bold;">TOTALES:</div>',
                                    subtotal: subtotalSum,
                                    total_iva: ivaSum,
                                    total: totalSum
                                });
                            }
                        }
                    }, false, '#manifiestosGridPager', {
                        refresh: true
                    });

                    // Aviso de manifiestos hoy a facturar (consulta 78): warning clickeable que abre tool con detalle por planta
                    $(function() {
                        public $alert = $('#manifiestos_facturar_alert');
                        public $content = $('#manifiestos_facturar_content');
                        $content.text('Cargando...');
                        $alert.show();
                        $.get('', {
                            getCountManifiestosAFacturarPlanta: 1
                        }, function(resp) {
                            var total = (resp && resp.total !== undefined) ? parseInt(resp.total, 10) : 0;
                            var detalle = (resp && resp.detalle) ? resp.detalle : [];
                            totalManifiestosAFacturar = total;
                            $alert.data('detalle', detalle);
                            if (total > 0) {
                                $content.html('Manifiestos pendientes por facturar hoy <Strong>(Ver)</Strong>').css({
                                    cursor: 'pointer',
                                    color: '#0044aa',
                                    fontWeight: '600'
                                });
                                public $tool = $('#manifiestos_facturar_tool');
                                $alert.off('click.manifiestos').on('click.manifiestos', function(e) {
                                    e.stopPropagation();
                                    if ($tool.is(':visible')) {
                                        $tool.hide();
                                        return;
                                    }
                                    var d = $alert.data('detalle');
                                    var tbl = '<table class="table table-condensed table-bordered" style="margin-top:8px;margin-bottom:0"><thead><tr><th>Planta</th><th>Cant.Man</th></tr></thead><tbody>';
                                    if (d && d.length) {
                                        for (var i = 0; i < d.length; i++) {
                                            tbl += '<tr><td>' + (d[i].Pla_Nom || '') + '</td><td>' + (d[i].cant_manifiestos_hoy || 0) + '</td></tr>';
                                        }
                                    } else {
                                        tbl += '<tr><td colspan="2">Sin datos</td></tr>';
                                    }
                                    tbl += '</tbody></table>';
                                    $tool.html('<strong>Manifiestos para facturar hoy</strong><br>' + tbl).show();
                                    setTimeout(function() {
                                        $(document).one('click', function(ev) {
                                            if (!$(ev.target).closest('#manifiestos_facturar').length) {
                                                $tool.hide();
                                            }
                                        });
                                    }, 0);
                                });
                            } else {
                                $content.text('');
                                $('#manifiestos_facturar_tool').hide();
                            }
                            $alert.show();
                        }, 'json').fail(function() {
                            totalManifiestosAFacturar = 0;
                            $content.text('No se pudo obtener la cantidad.');
                        });
                    });

                    // Función para verificar y habilitar/deshabilitar el checkbox "Facturar por Grupo" e "Individual"
                    function verificarFacturarPorGrupo() {
                        var op_opciones = $('#serachDocDorm input[name="op_opciones"]:checked').val();
                        var plaCod = $('select[name="Pla_Cod"]').val();
                        var searchVal = $('#serachDocDorm input[name="search"]').val();
                        public $checkboxGrupo = $('#fac_group');
                        public $checkboxIndividual = $('#fac_individual');
                        public $btnRegister = $('#btn_register');

                        // Habilitar solo si: búsqueda por Cliente (op_opciones == 'p') Y planta seleccionada (plaCod != '') Y hay un cliente escrito (searchVal != '')
                        if (op_opciones == 'p' && plaCod && plaCod !== '' && searchVal && searchVal.trim() !== '') {
                            $checkboxGrupo.prop('disabled', false);
                            $checkboxIndividual.prop('disabled', false);
                            $btnRegister.prop('disabled', false);
                        } else {
                            $checkboxGrupo.prop('disabled', true);
                            $checkboxGrupo.prop('checked', false); // Desmarcar si se deshabilita
                            $checkboxIndividual.prop('disabled', true);
                            $checkboxIndividual.prop('checked', false); // Desmarcar si se deshabilita
                            $btnRegister.prop('disabled', true);
                        }

                        // Actualizar estado de los checkboxes de la columna según el estado de fac_group e individual
                        actualizarEstadoCheckboxesColumna();
                    }

                    // Escuchar cambios en el input de búsqueda para habilitar/deshabilitar opciones
                    $('#serachDocDorm input[name="search"]').on('keyup blur change', function() {
                        verificarFacturarPorGrupo();
                    });

                    // Función para actualizar el estado checked de los checkboxes
                    function actualizarEstadoCheckboxesColumna() {
                        var facGroupChecked = $('#fac_group').is(':checked');
                        var facIndividualChecked = $('#fac_individual').is(':checked');

                        // El checkbox del encabezado
                        if (facIndividualChecked) {
                            // Si Individual está marcado, habilitar el checkbox del encabezado
                            $('#select_all_man').prop('disabled', false);
                            // No cambiar el estado checked automáticamente, dejar que el usuario decida
                        } else {
                            // Si Individual no está marcado, deshabilitar
                            $('#select_all_man').prop('disabled', true);
                            $('#select_all_man').prop('checked', facGroupChecked);
                        }

                        // Checkboxes de la columna
                        $('.row-select').each(function() {
                            if (facIndividualChecked) {
                                // Si Individual está marcado, habilitar para selección manual
                                $(this).prop('disabled', false);
                            } else {
                                // Si Individual no está marcado, deshabilitar y usar el estado de fac_group
                                $(this).prop('disabled', true);
                                if ($(this).is(':visible')) {
                                    $(this).prop('checked', facGroupChecked);
                                }
                            }
                        });

                        // Actualizar información de facturación después de cambiar los checkboxes
                        actualizarInfoFacturacion();
                    }

                    // Función para actualizar la información de facturación
                    function actualizarInfoFacturacion() {
                        var facGroupChecked = $('#fac_group').is(':checked');
                        var facIndividualChecked = $('#fac_individual').is(':checked');
                        public $infoDiv = $('#infoFacturacionContent');
                        public $infoTexto = $('#infoFacturacionTexto');

                        if (facGroupChecked) {
                            var cantidadSeleccionados = $('.row-select:checked').length;
                            var rowNumActual = 1000;
                            try {
                                if ($('#manifiestosGrid').length && $.fn.jqGrid && $('#manifiestosGrid').data('jqGrid')) {
                                    rowNumActual = $('#manifiestosGrid').jqGrid('getGridParam', 'rowNum') || 1000;
                                }
                            } catch (e) {}
                            if (totalManifiestosAFacturar > 0 && cantidadSeleccionados < totalManifiestosAFacturar) {
                                $infoTexto.html('No se han seleccionado todos los manifiestos. Aumente las filas mostradas en la parte inferior de la tabla (actualmente ' + rowNumActual + ') para poder seleccionarlos todos.');
                                $infoDiv.removeClass('alert-success').addClass('alert-warning').show();
                                $('#btn_register').prop('disabled', true);
                            } else if (cantidadSeleccionados > 0) {
                                $infoTexto.text('Se facturarán ' + cantidadSeleccionados + ' manifiesto(s) agrupado(s) en una sola factura.');
                                $infoDiv.removeClass('alert-warning').addClass('alert-success').show();
                                $('#btn_register').prop('disabled', false);
                            } else {
                                $infoTexto.text('No hay manifiestos seleccionados para facturar.');
                                $infoDiv.removeClass('alert-warning').addClass('alert-success').show();
                                $('#btn_register').prop('disabled', false);
                            }
                        } else if (facIndividualChecked) {
                            // Modo Individual: mostrar cuántos están seleccionados manualmente
                            var cantidadSeleccionados = $('.row-select:checked').length;
                            if (cantidadSeleccionados > 0) {
                                $infoTexto.text('Modo Individual: ' + cantidadSeleccionados + ' manifiesto(s) seleccionado(s) para facturar de forma individual.');
                                $infoDiv.show();
                            } else {
                                $infoTexto.text('Modo Individual: Seleccione los manifiestos que desea facturar.');
                                $infoDiv.show();
                            }
                            $('#btn_register').prop('disabled', false);
                        } else {
                            $infoDiv.hide();
                            $('#btn_register').prop('disabled', false);
                        }
                    }

                    // Función para filtrar por planta
                    function filtrarPorPlanta() {
                        var plaCod = $('select[name="Pla_Cod"]').val();
                        // Si hay datos en el grid, recargar con el filtro de planta
                        if ($('#manifiestosGrid').jqGrid('getGridParam', 'datatype') === 'json') {
                            var postData = $('#manifiestosGrid').jqGrid('getGridParam', 'postData');
                            if (plaCod) {
                                postData.Pla_Cod = plaCod;
                            } else {
                                delete postData.Pla_Cod;
                            }
                            $('#manifiestosGrid').jqGrid('setGridParam', {
                                postData: postData
                            });
                            $('#manifiestosGrid').trigger('reloadGrid');
                        }
                        // Verificar estado del checkbox después de cambiar la planta
                        verificarFacturarPorGrupo();
                    }

                    // Verificar estado del checkbox cuando cambia el tipo de búsqueda
                    $('#serachDocDorm input[name="op_opciones"]').on('change', function() {
                        verificarFacturarPorGrupo();
                    });

                    // Manejar cambio del checkbox "Facturar por Grupo"
                    $('#fac_group').on('change', function() {
                        // Si se marca fac_group, desmarcar individual
                        if ($(this).is(':checked')) {
                            $('#fac_individual').prop('checked', false);
                        }
                        // Actualizar estado checked de todos los checkboxes
                        actualizarEstadoCheckboxesColumna();
                        // Actualizar información de facturación
                        actualizarInfoFacturacion();
                    });

                    // Manejar cambio del checkbox "Individual"
                    $('#fac_individual').on('change', function() {
                        if ($(this).is(':checked')) {
                            // Si se marca individual, desmarcar fac_group
                            $('#fac_group').prop('checked', false);
                            // Desmarcar todos los checkboxes de las filas para que el usuario seleccione manualmente
                            $('.row-select').prop('checked', false);
                            $('#select_all_man').prop('checked', false);
                        } else {
                            // Si se desmarca individual, desmarcar y limpiar todos los checkboxes de las filas
                            $('.row-select').prop('checked', false);
                            $('#select_all_man').prop('checked', false);
                        }
                        // Actualizar estado de los checkboxes
                        actualizarEstadoCheckboxesColumna();
                        // Actualizar información de facturación
                        actualizarInfoFacturacion();
                    });

                    // Manejar cambio en checkboxes de filas individuales (usando delegación de eventos)
                    $(document).on('change', '.row-select', function() {
                        // Si estamos en modo individual, actualizar la información
                        if ($('#fac_individual').is(':checked')) {
                            actualizarInfoFacturacion();
                        }
                    });

                    // Manejar el checkbox "Seleccionar todos" del encabezado
                    $(document).on('change', '#select_all_man', function() {
                        if ($('#fac_individual').is(':checked')) {
                            var isChecked = $(this).is(':checked');
                            $('.row-select:visible').prop('checked', isChecked);
                            actualizarInfoFacturacion();
                        }
                    });

                    // Verificar estado inicial
                    verificarFacturarPorGrupo();

                    // Función para gestionar la exclusividad entre Periodo/Mes y Rango de Fechas
                    window.gestionarFiltrosFecha = function(select) {
                        public $pec = $(select);
                        var pecCod = $pec.val();
                        public $cmbMes = $('#Cmb_Mes');
                        public $fecIni = $('#Fec_Ini');
                        public $fecFin = $('#Fec_Fin');
                        public $hiddenIni = $('input[name="fecha_inicio"]');
                        public $hiddenFin = $('input[name="fecha_fin"]');

                        if (pecCod !== '') {
                            // Periodo seleccionado: habilitar mes, bloquear rango de fechas
                            $cmbMes.prop('disabled', false);
                            $fecIni.prop('disabled', true).val('');
                            $fecFin.prop('disabled', true).val('');

                            // Actualizar hidden inputs con el rango del periodo seleccionado
                            public $opt = $pec.find('option:selected');
                            $hiddenIni.val($opt.data('inicio'));
                            $hiddenFin.val($opt.data('fin'));
                        } else {
                            // "FECHAS" seleccionado: bloquear mes, habilitar rango de fechas
                            $cmbMes.prop('disabled', true).val('');
                            $fecIni.prop('disabled', false);
                            $fecFin.prop('disabled', false);

                            // Limpiar hidden inputs de periodo
                            $hiddenIni.val('');
                            $hiddenFin.val('');

                            // Restaurar fechas por defecto si es necesario
                            if ($fecIni.val() === '') {
                                $fecIni.val('<?php echo date('Y-01-01'); ?>');
                            }
                            if ($fecFin.val() === '') {
                                $fecFin.val('<?php echo date('Y-12-31'); ?>');
                            }
                        }
                    };

                    // Inicializar estado de filtros de fecha
                    $(function() {
                        gestionarFiltrosFecha($('#Pec_Cod')[0]);
                    });

                    // Función para limpiar la búsqueda y mostrar todos los manifiestos
                    window.limpiarBusquedaGeneral = function() {
                        // Limpiar input de búsqueda
                        $('#serachDocDorm input[name="search"]').val('');

                        // Restaurar el select de plantas a << TODOS >>
                        public $selectPla = $('select[name="Pla_Cod"]');
                        // Si el select fue modificado dinámicamente, podríamos querer restaurar todas las plantas activas
                        // pero según el requerimiento, basta con ponerlo en vacio (TODOS)
                        $selectPla.val('').trigger('change');

                        // Asegurar que esté seleccionada la opción Cliente por defecto
                        $('#radsc1').prop('checked', true);
                        if (typeof setOpt === 'function') setOpt('p');

                        // Actualizar estado de los checkboxes
                        verificarFacturarPorGrupo();

                        // Ejecutar la búsqueda para mostrar todo
                        if ($.fn.Search) {
                            $('#manifiestosGrid').Search('#serachDocDorm', 'searchDocument');
                        } else {
                            $('#serachDocDorm').submit();
                        }
                    };

                    // --- Búsqueda de Clientes Modal ---
                    window.selectCliente = function(row) {
                        if (row && row.Prs_Ced) {
                            // Cambiar a búsqueda por Cliente (radsc1)
                            public $radCliente = $('#radsc1');
                            $radCliente.prop('checked', true);

                            // Llamar a setOpt para configurar búsqueda por cliente ('p')
                            if (typeof setOpt === 'function') {
                                setOpt('p');
                            } else {
                                $radCliente.trigger('change');
                            }

                            // Verificar activación de checkboxes
                            if (typeof verificarFacturarPorGrupo === 'function') {
                                verificarFacturarPorGrupo();
                            }

                            // Poner el nombre del cliente en el input de búsqueda
                            $('#serachDocDorm input[name="search"]').val(row.cliente);

                            // Actualizar el select de plantas para el cliente seleccionado
                            if (row.Cli_Cod) {
                                $.ajax({
                                    url: 'man_alt_fac.php',
                                    type: 'GET',
                                    data: {
                                        getPlantasClientes: 1,
                                        Cli_Cod: row.Cli_Cod
                                    },
                                    dataType: 'json',
                                    success: function(plantas) {
                                        public $selectPla = $('select[name="Pla_Cod"]');
                                        $selectPla.empty();
                                        $selectPla.append('<option value=""><< TODOS >></option>');
                                        if (plantas && plantas.length > 0) {
                                            $.each(plantas, function(i, p) {
                                                $selectPla.append('<option value="' + p.Pla_Cod + '">' + p.Pla_Nom + '</option>');
                                            });
                                        }
                                        // Siempre dejar la opción TODOS seleccionada inicialmente
                                        $selectPla.val('').trigger('change');
                                    }
                                });
                            }

                            // Cerrar modal
                            $('#clieDialog').dialog('close');

                            // Ejecutar búsqueda automáticamente llamando a la función de búsqueda del grid
                            // En lugar de submit() que recarga, usamos la lógica de búsqueda del framework
                            if ($.fn.Search) {
                                $('#manifiestosGrid').Search('#serachDocDorm', 'searchDocument');
                            } else {
                                $('#serachDocDorm').submit();
                            }
                        }
                    };

                    $(function() {
                        $.createSearchDialog('clieDialog', [{
                                label: 'Cód. Cliente',
                                name: 'Cli_Cod',
                                key: true,
                                width: 90,
                                align: 'center',
                                hidden: true
                            },
                            {
                                label: 'Cédula/RUC',
                                name: 'Prs_Ced',
                                width: 50
                            },
                            {
                                label: 'Cliente',
                                name: 'cliente',
                                width: 120
                            },
                            {
                                label: 'Planta',
                                name: 'Pla_Nom',
                                width: 100
                            },
                            {
                                label: '&nbsp;',
                                name: 'act1',
                                width: 20,
                                align: 'center',
                                viewable: false,
                                formatter: 'gridButton',
                                formatoptions: {
                                    action: 'selectCliente',
                                    data: function(row) {
                                        return {
                                            Cli_Cod: row.Cli_Cod,
                                            cliente: row.cliente,
                                            Prs_Ced: row.Prs_Ced
                                        };
                                    }
                                }
                            }
                        ], null, null, null, {
                            headertitles: true
                        }, {
                            title: 'Cliente',
                            text: 'search',
                            options: [{
                                    label: '&nbsp;&nbsp;Nombre Cliente / Planta&nbsp;&nbsp;',
                                    value: 'd'
                                },
                                {
                                    label: '&nbsp;&nbsp;Cédula/R.U.C&nbsp;&nbsp;',
                                    value: 'c'
                                }
                            ]
                        });

                        $('#clieDialog').on('dialogopen', function() {
                            $.Search('clie');
                        });

                        $('#btnAbrirModalClientes').on('click', function() {
                            $('#clieDialog').dialog('open');
                        });

                        // --- Diálogo: Manifiestos por facturar (agrupado) ---
                        $.createSearchDialog('sfDialog', [{
                                label: 'Cédula/RUC',
                                name: 'Prs_Ced',
                                width: 70,
                                align: 'center'
                            },
                            {
                                label: 'Cliente',
                                name: 'cliente',
                                width: 160
                            },
                            {
                                label: 'Bodega/Planta',
                                name: 'bodega',
                                width: 140
                            },
                            {
                                label: 'Cant. Manifiestos',
                                name: 'cant_manifiestos',
                                width: 60,
                                align: 'center'
                            }
                        ], null, null, null, {
                            headertitles: true,
                            loadComplete: function(data) {
                                try {
                                    var ids = $('#sfGrid').jqGrid('getDataIDs') || [];
                                    for (var i = 0; i < ids.length; i++) {
                                        var id = ids[i];
                                        var row = $('#sfGrid').jqGrid('getRowData', id) || {};
                                        var n = parseFloat(String(row.cant_manifiestos || '0').replace(/,/g, '')) || 0;
                                        public $tds = $('#' + id + ' td:not(.jqgrid-rownum)');
                                        if (n > 0) {
                                            $tds.addClass('cellRed2');
                                            $tds.removeClass('cellGreen2');
                                        } else {
                                            $tds.addClass('cellGreen2');
                                            $tds.removeClass('cellRed2');
                                        }
                                    }
                                } catch (e) {}
                            }
                        }, {
                            title: 'Buscar',
                            text: 'search'
                        });

                        // Al abrir, setear fechas por defecto desde el formulario principal y cargar
                        $('#sfDialog').on('dialogopen', function() {
                            $('#sf_fec_ini').val($('#Fec_Ini').val() || '');
                            $('#sf_fec_fin').val($('#Fec_Fin').val() || '');

                            // Habilitar calendario igual que el filtro principal
                            // (en este proyecto normalmente viene con jQuery UI por jqgrid5.php)
                            if ($.fn.datepicker) {
                                try {
                                    $('#sf_fec_ini, #sf_fec_fin').datepicker('destroy');
                                } catch (e) {}
                                $('#sf_fec_ini, #sf_fec_fin').datepicker({
                                    dateFormat: 'yy-mm-dd',
                                    changeMonth: true,
                                    changeYear: true
                                });
                            }

                            // Abrir calendario al hacer clic (si aplica)
                            $('#sf_fec_ini, #sf_fec_fin')
                                .off('click.sfDate')
                                .on('click.sfDate', function() {
                                    if ($.fn.datepicker) {
                                        try {
                                            $(this).datepicker('show');
                                        } catch (e) {}
                                    }
                                });

                            $.Search('sf');
                        });

                        // Botón USD abre el diálogo (mismo formato que clientes)
                        $('#btn_sin_facturar').on('click', function() {
                            $('#sfDialog').dialog('open');
                        });

                        // Botón Filtrar dentro del diálogo
                        $(document).on('click', '#btnSfFiltrar', function() {
                            $.Search('sf');
                        });
                    });
                </script>

                <?php
                /* 
                // DIV redundantemente movido arriba para asegurar que exista antes del script
                <div id="clieDialog" title="B&uacute;squeda de Clientes">
                    <form class="form-horizontal normal"></form>
                </div>
                */
                ?>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
    <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
    <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />
</body>

</HTML>