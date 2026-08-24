<?php

/**
 * Formulario de Registro de Vehículos y Choferes - Relavera
 * Permite ingresar choferes y vehículos asociándolos a la planta activa en segundo plano.
 * Cuenta con un ambiente de consulta y otro de registro (Dos Ambientes).
 * @author Sistema EXA
 * @version 1.1
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_vehiculos_choferes.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Vehiculos_Choferes($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Vehiculos_Choferes;

// Planta no requerida para este módulo general
$Pla_Cod_Log = 0;

// Obtener ciudades para modal de proveedor
$rs_ciudad = $obBD_con1->getArrayConsulta(23, array(), $obBD_conexion);
$obBD_con1->utf8_change_param($rs_ciudad);

// Obtener listas para selects dinámicos de vehículo
$rs_marcas = $obBD_con1->getArrayConsulta(17, array(), $obBD_conexion);
$obBD_con1->utf8_change_param($rs_marcas);

$rs_colores = $obBD_con1->getArrayConsulta(18, array(), $obBD_conexion);
$obBD_con1->utf8_change_param($rs_colores);

$rs_titulos = $obBD_con1->getArrayConsulta(19, array(), $obBD_conexion);
$obBD_con1->utf8_change_param($rs_titulos);

/* ==================== AJAX HANDLERS ==================== */

// Buscar persona por cédula para autocompletar
if (isset($_GET['buscarPersonaPorCedulaAjax'])) {
    $cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';
    $resp = array('exists' => false);
    if (!empty($cedula)) {
        // Quitar dígitos adicionales de RUC para la búsqueda en la tabla persona
        $prsCedBusq = strlen($cedula) === 13 ? substr($cedula, 0, 10) : $cedula;

        $row_per = $obBD_con1->getArrayConsulta(24, array($prsCedBusq), $obBD_conexion);
        if (!empty($row_per)) {
            $obBD_con1->utf8_change_param($row_per);
            $resp['exists'] = true;
            $resp['Prs_Nom'] = $row_per[0]['Prs_Nom'];
            $resp['Prs_Ape'] = $row_per[0]['Prs_Ape'];
            $resp['Prs_Tel'] = $row_per[0]['Prs_Tel'];
            $resp['Prs_San'] = $row_per[0]['Prs_San'];

            // Buscar si ya es chofer para cargar datos de su licencia
            $Prs_Cod = (int)$row_per[0]['Prs_Cod'];
            $row_cho = $obBD_con1->getArrayConsulta(25, array($Prs_Cod), $obBD_conexion);
            if (!empty($row_cho)) {
                $obBD_con1->utf8_change_param($row_cho);
                $resp['isChofer'] = true;
                $resp['Cho_Tli'] = $row_cho[0]['Cho_Tli'];
                $resp['Cho_Cli'] = '';
                if (!empty($row_cho[0]['Cho_Cli'])) {
                    $parts = explode('-', $row_cho[0]['Cho_Cli']);
                    if (count($parts) === 3) {
                        $resp['Cho_Cli'] = $parts[2] . '/' . $parts[1] . '/' . $parts[0];
                    } else {
                        $resp['Cho_Cli'] = $row_cho[0]['Cho_Cli'];
                    }
                }
            }
        }
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Listar empresas de transporte para el selector
if (isset($_GET['listTransportesAjax'])) {
    $rows_data = $obBD_con1->getArrayConsulta(1, array($_SESSION['Ses_Emp_Cod']), $obBD_conexion);
    $obBD_con1->utf8_change_param($rows_data);
    $obBD_con1->echoJson($rows_data);
    exit;
}

// Listar Choferes para el Grid (Ambiente 1)
if (isset($_GET['listChoferesGridAjax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $op_opciones = isset($_GET['op_opciones']) ? trim($_GET['op_opciones']) : 'd';

    $params = array(
        0 => $_SESSION['Ses_Emp_Cod'],
        'search' => $search,
        'op_opciones' => $op_opciones
    );

    $row_count = $obBD_con1->getRowConsulta(2, $params, $obBD_conexion);
    $total_records = isset($row_count['total']) ? (int)$row_count['total'] : 0;

    $pagination = pages($total_records, $page, $rows);
    $response = $pagination['data'];

    if ($total_records > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(2, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
    exit;
}

// Listar Vehículos para el Grid (Ambiente 1)
if (isset($_GET['listVehiculosGridAjax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $op_opciones = isset($_GET['op_opciones']) ? trim($_GET['op_opciones']) : 'p';

    $params = array(
        0 => $_SESSION['Ses_Emp_Cod'],
        'search' => $search,
        'op_opciones' => $op_opciones
    );

    $row_count = $obBD_con1->getRowConsulta(3, $params, $obBD_conexion);
    $total_records = isset($row_count['total']) ? (int)$row_count['total'] : 0;

    $pagination = pages($total_records, $page, $rows);
    $response = $pagination['data'];

    if ($total_records > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(3, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
    exit;
}

// Buscar Proveedor por cédula o RUC (AJAX)
if (isset($_POST['buscarProveedorAjax'])) {
    $Prv_Ced = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
    $resp = array('success' => false, 'message' => 'Proveedor no encontrado. ¿Desea registrarlo ahora?');

    if (!empty($Prv_Ced)) {
        $proveedor = $obBD_con1->getArrayConsulta(14, array($Prv_Ced), $obBD_conexion);

        if (!empty($proveedor)) {
            $resp['success'] = true;
            $resp['message'] = 'Proveedor encontrado.';
            $resp['Prv_Cod'] = $proveedor[0]['Prv_Cod'];
            $resp['Prv_Nom'] = trim($proveedor[0]['Prv_Nom']);
        }
    }

    $obBD_con1->echoJson($resp);
    exit;
}

// Guardar Proveedor Rápido (AJAX)
if (isset($_POST['saveProveedorRapidoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Prs_Ced = isset($_POST['Reg_Prs_Ced']) ? trim($_POST['Reg_Prs_Ced']) : '';
        $Ide_Cod = isset($_POST['Reg_Ide_Cod']) ? (int)$_POST['Reg_Ide_Cod'] : 0;
        $Prv_Tic = isset($_POST['Reg_Prv_Tic']) ? trim($_POST['Reg_Prv_Tic']) : '';
        $Prs_Ape = isset($_POST['Reg_Prs_Ape']) ? trim($_POST['Reg_Prs_Ape']) : '';
        $Prs_Nom = isset($_POST['Reg_Prs_Nom']) ? trim($_POST['Reg_Prs_Nom']) : '';
        $Prs_Sex = isset($_POST['Reg_Prs_Sex']) ? trim($_POST['Reg_Prs_Sex']) : '';
        $Prv_Com = isset($_POST['Reg_Prv_Com']) ? trim($_POST['Reg_Prv_Com']) : '';
        $Ciu_Cod = isset($_POST['Reg_Ciu_Cod']) ? (int)$_POST['Reg_Ciu_Cod'] : 0;
        $Prs_Dir = isset($_POST['Reg_Prs_Dir']) ? trim($_POST['Reg_Prs_Dir']) : '';
        $Prs_Tel = isset($_POST['Reg_Prs_Tel']) ? trim($_POST['Reg_Prs_Tel']) : '';
        $Prs_Cor = isset($_POST['Reg_Prs_Cor']) ? trim($_POST['Reg_Prs_Cor']) : '';

        // Checks
        $Prv_Esp = isset($_POST['Reg_Prv_Esp']) ? 'S' : 'N';
        $Prv_Reg = isset($_POST['Reg_Prv_Reg']) ? 'S' : 'N';
        $Prv_Con = isset($_POST['Reg_Prv_Con']) ? 'S' : 'N';
        $Prv_Ris = isset($_POST['Reg_Prv_Ris']) ? 'S' : 'N';
        $Prv_Rim_Emp = isset($_POST['Reg_Prv_Rim_Emp']) ? 'S' : 'N';
        $Prv_Rim_Np = isset($_POST['Reg_Prv_Rim_Np']) ? 'S' : 'N';
        $Prv_Ag_Ret = isset($_POST['Reg_Prv_Ag_Ret']) ? 'S' : 'N';
        $Prv_Gct = isset($_POST['Reg_Prv_Gct']) ? 'S' : 'N';

        if (empty($Prs_Ced) || empty($Ide_Cod) || empty($Prv_Tic) || empty($Prs_Ape) || empty($Ciu_Cod) || empty($Prs_Dir)) {
            throw new Exception('Faltan campos obligatorios en el registro del proveedor.');
        }

        // 1. Validate if persona already exists by cedula
        $prsCedBusq = strlen($Prs_Ced) === 13 ? substr($Prs_Ced, 0, 10) : $Prs_Ced;
        $persona = $obBD_con1->getArrayConsulta(26, array($prsCedBusq), $obBD_conexion);

        $Prs_Cod = 0;
        if (!empty($persona)) {
            $Prs_Cod = $persona[0]['Prs_Cod'];
        } else {
            // Insertar persona
            $paramPersona = array(
                'Prs_Ced' => $prsCedBusq,
                'Prs_Sex' => $Prs_Sex,
                'Prs_Ape' => $Prs_Ape,
                'Prs_Nom' => $Prs_Nom,
                'Ciu_Cod' => $Ciu_Cod,
                'Prs_Dir' => $Prs_Dir,
                'Ide_Cod' => $Ide_Cod,
                'Prs_Tel' => $Prs_Tel,
                'Prs_Cor' => $Prs_Cor
            );
            $obBD_con1->operacionobBD(15, $paramPersona, $obBD_conexion);
            $Prs_Cod = $obBD_con1->insercionid($obBD_conexion);
        }

        // 2. Validate if proveedor exists
        $proveedor = $obBD_con1->getArrayConsulta(27, array($Prs_Cod, $_SESSION['Ses_Emp_Cod']), $obBD_conexion);
        $Prv_Cod = 0;

        if (!empty($proveedor)) {
            $Prv_Cod = $proveedor[0]['Prv_Cod'];
        } else {
            // Insertar proveedore
            $paramProveedor = array(
                'Emp_Cod' => $_SESSION['Ses_Emp_Cod'],
                'Prs_Cod' => $Prs_Cod,
                'Prv_Com' => $Prv_Com,
                'Prv_Tic' => $Prv_Tic,
                'Prv_Esp' => $Prv_Esp,
                'Prv_Con' => $Prv_Con,
                'Prv_Reg' => $Prv_Reg,
                'Prv_Ris' => $Prv_Ris,
                'Prv_Gct' => $Prv_Gct,
                'Prv_Rim_Emp' => $Prv_Rim_Emp,
                'Prv_Rim_Np' => $Prv_Rim_Np,
                'Prv_Ag_Ret' => $Prv_Ag_Ret
            );
            $obBD_con1->operacionobBD(16, $paramProveedor, $obBD_conexion);
            $Prv_Cod = $obBD_con1->insercionid($obBD_conexion);
        }

        $resp['success'] = true;
        $resp['message'] = 'Proveedor registrado/recuperado exitosamente.';
        $resp['Prv_Cod'] = $Prv_Cod;
        $resp['Prv_Nom'] = ($Prv_Tic == 'N') ? trim($Prs_Ape . ' ' . $Prs_Nom) : (($Prv_Com != '') ? trim($Prv_Com) : trim($Prs_Ape));
        $resp['Prs_Ced'] = $Prs_Ced;
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}

// Buscar Vehículo por Placa (AJAX) - Para edición
if (isset($_POST['buscarVehiculoPorPlacaAjax'])) {
    $Veh_Pla = isset($_POST['placa']) ? trim($_POST['placa']) : '';
    $resp = array('success' => false);

    if (!empty($Veh_Pla)) {
        $veh = $obBD_con1->getArrayConsulta(12, array($Veh_Pla, $_SESSION['Ses_Emp_Cod']), $obBD_conexion);
        if (!empty($veh)) {
            $resp['success'] = true;
            $resp['Veh_Mar'] = $veh[0]['Veh_Mar'];
            $resp['Veh_Col'] = $veh[0]['Veh_Col'];
            $resp['Veh_Tit'] = $veh[0]['Veh_Tit'];
            $resp['Prv_Cod'] = $veh[0]['Prv_Cod'];
            $resp['Veh_Val'] = $veh[0]['Veh_Val'];
            $resp['Veh_Adi'] = isset($veh[0]['Veh_Adi']) ? $veh[0]['Veh_Adi'] : '';

            if (!empty($veh[0]['Prv_Cod'])) {
                $prv = $obBD_con1->getArrayConsulta(13, array($veh[0]['Prv_Cod']), $obBD_conexion);
                if (!empty($prv)) {
                    $resp['Prv_Nom'] = trim($prv[0]['Prv_Nom']);
                    $resp['Prs_Ced'] = isset($prv[0]['Prs_Ced']) ? trim($prv[0]['Prs_Ced']) : '';
                }
            }
        }
    }

    $obBD_con1->echoJson($resp);
    exit;
}

// Guardar Chofer (AJAX)
if (isset($_POST['saveChoferAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Cho_Ced = isset($_POST['Cho_Ced']) ? trim($_POST['Cho_Ced']) : '';
        $Prs_Nom = isset($_POST['Prs_Nom']) ? trim($_POST['Prs_Nom']) : '';
        $Prs_Ape = isset($_POST['Prs_Ape']) ? trim($_POST['Prs_Ape']) : '';
        $Cho_Tli = isset($_POST['Cho_Tli']) ? trim($_POST['Cho_Tli']) : '';
        $Cho_Cli = isset($_POST['Cho_Cli']) ? trim($_POST['Cho_Cli']) : '';
        $Cho_Tel = isset($_POST['Cho_Tel']) ? trim($_POST['Cho_Tel']) : '';
        $Cho_Tsa = isset($_POST['Cho_Tsa']) ? trim($_POST['Cho_Tsa']) : '';

        if (empty($Cho_Ced) || empty($Prs_Nom) || empty($Prs_Ape) || empty($Cho_Tli) || empty($Cho_Cli) || empty($Cho_Tel) || empty($Cho_Tsa)) {
            throw new Exception('Todos los campos marcados con asterisco (*) son obligatorios.');
        }

        // Formatear fecha para MySQL (si viene como dd/mm/aaaa)
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $Cho_Cli)) {
            $parts = explode('/', $Cho_Cli);
            $Cho_Cli = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }

        $prsCedBusq = strlen($Cho_Ced) === 13 ? substr($Cho_Ced, 0, 10) : $Cho_Ced;

        // Buscar persona por cédula
        $persona = $obBD_con1->getArrayConsulta(26, array($prsCedBusq), $obBD_conexion);

        if (empty($persona)) {
            $ideCod = strlen($Cho_Ced) === 13 ? '2' : '1'; // 2=RUC, 1=Cédula
            $obBD_con1->operacionobBD(4, array(null, $ideCod, $prsCedBusq, $Prs_Nom, $Prs_Ape, $Cho_Tel, $Cho_Tsa), $obBD_conexion);
            $Prs_Cod = $obBD_con1->insercionid($obBD_conexion);
        } else {
            $Prs_Cod = $persona[0]['Prs_Cod'];
            $obBD_con1->operacionobBD(5, array($Prs_Cod, $Prs_Nom, $Prs_Ape, $Cho_Tel, $Cho_Tsa), $obBD_conexion);
        }

        // Buscar chofer
        $chofer = $obBD_con1->getArrayConsulta(28, array($Prs_Cod, $Ses_Emp_Cod), $obBD_conexion);
        if (!empty($chofer)) {
            if ($chofer[0]['Cho_Tip'] === 'CM') {
                throw new Exception('El operario ya se encuentra registrado con tipo CM y no puede ser gestionado aquí.');
            }
            $Cho_Cod = $chofer[0]['Cho_Cod'];
            $obBD_con1->operacionobBD(7, array($Cho_Cod, $Cho_Tli, $Cho_Cli, $Cho_Tel, $Cho_Tsa), $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD(6, array($Prs_Cod, $Ses_Emp_Cod, $Cho_Tli, $Cho_Cli, $Cho_Tel, $Cho_Tsa), $obBD_conexion);
            $Cho_Cod = $obBD_con1->insercionid($obBD_conexion);
        }

        $resp['success'] = true;
        $resp['message'] = 'Operador registrado exitosamente.';
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}

// Guardar Vehículo (AJAX)
if (isset($_POST['saveVehiculoAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $Prv_Cod = isset($_POST['Prv_Cod']) ? (int)$_POST['Prv_Cod'] : 0;
        $Veh_Mar = isset($_POST['Veh_Mar']) ? trim($_POST['Veh_Mar']) : '';
        $Veh_Pla = isset($_POST['Veh_Pla']) ? trim($_POST['Veh_Pla']) : '';
        $Veh_Col = isset($_POST['Veh_Col']) ? trim($_POST['Veh_Col']) : '';
        $Veh_Tit = isset($_POST['Veh_Tit']) ? trim($_POST['Veh_Tit']) : '';
        $Veh_Val = isset($_POST['Veh_Val']) && trim($_POST['Veh_Val']) !== '' ? (float)$_POST['Veh_Val'] : 0.00;
        $Veh_Adi = isset($_POST['Veh_Adi']) ? trim($_POST['Veh_Adi']) : '';

        if (empty($Prv_Cod) || empty($Veh_Mar) || empty($Veh_Pla) || empty($Veh_Col) || empty($Veh_Tit)) {
            throw new Exception('Todos los campos marcados con asterisco (*) son obligatorios.');
        }

        if ($Veh_Val < 0) {
            throw new Exception('El valor pactado por hora no puede ser negativo.');
        }

        $veh_cap_val = 0;

        // Buscar vehículo
        $veh_exist = $obBD_con1->getArrayConsulta(29, array($Veh_Pla, $Ses_Emp_Cod), $obBD_conexion);
        if (!empty($veh_exist)) {
            if ($veh_exist[0]['Veh_Tip'] === 'VM') {
                throw new Exception('El vehículo ya se encuentra registrado con tipo VM y no puede ser gestionado aquí.');
            }
            $Veh_Cod = $veh_exist[0]['Veh_Cod'];
            $obBD_con1->operacionobBD(10, array($Veh_Cod, $Veh_Mar, $Veh_Col, $veh_cap_val, $Veh_Tit, NULL, $Prv_Cod, $Veh_Val, $Veh_Adi), $obBD_conexion);
        } else {
            $obBD_con1->operacionobBD(9, array($Veh_Mar, $Veh_Pla, $Veh_Col, $veh_cap_val, $Veh_Tit, $Ses_Emp_Cod, NULL, $Prv_Cod, $Veh_Val, $Veh_Adi), $obBD_conexion);
            $Veh_Cod = $obBD_con1->insercionid($obBD_conexion);
        }

        $resp['success'] = true;
        $resp['message'] = 'Vehículo registrado exitosamente.';
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
        exit;
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($resp);
    exit;
}
?>
<!DOCTYPE HTML>
<HTML>

<HEAD>
    <TITLE>Registrar Operador y Maquinaria [EXA]</TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script language="javascript" src="../../framework/plugins/cedulaRuc.js"></script>
    <link rel="stylesheet" type="text/css" href="../RECURSOS/vehiculos_choferes.css" />
</HEAD>

<BODY>
    <div class="panel panel-default panel-main">
        <div class="panel-heading">
            <span class="glyphicon glyphicon-edit"></span> » Gestión de Operador y Maquinaria
        </div>
        <div class="panel-body exa-body">

            <!-- ==================== AMBIENTE 1: CONSULTA / LISTADO ==================== -->
            <div id="divListado">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="active">
                            <a href="#tabListChoferes" role="tab" data-toggle="tab">
                                <i class="glyphicon glyphicon-user" style="margin-right: 8px;"></i>Datos del Operario
                            </a>
                        </li>
                        <li>
                            <a href="#tabListVehiculos" role="tab" data-toggle="tab">
                                <i class="glyphicon glyphicon-road" style="margin-right: 8px;"></i>Datos de la Maquinaria
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content tab-content-custom">

                        <!-- Grid Choferes -->
                        <div role="tabpanel" class="tab-pane active" id="tabListChoferes">
                            <div style="margin-bottom: 15px; background-color: #f9f9f9; padding: 10px; border-radius: 4px; border: 1px solid #eee;">
                                <div class="form-inline" style="margin-bottom: 10px;">
                                    <label class="control-label" style="font-weight: bold; width: 70px;">Filtro:</label>
                                    <input type="hidden" id="opChofer" value="d">
                                    <div class="btn-group" style="vertical-align: top;">
                                        <button type="button" class="btn btn-default btn-sm active" onclick="document.getElementById('opChofer').value='d'; $(this).addClass('active').css({'color':'#e67e22','background-color':'#fff'}).siblings().removeClass('active').css({'color':'#000','background-color':'#e6e6e6'});" style="color: #e67e22; font-weight: bold; background-color: #fff;">Nombre</button>
                                        <button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('opChofer').value='c'; $(this).addClass('active').css({'color':'#e67e22','background-color':'#fff'}).siblings().removeClass('active').css({'color':'#000','background-color':'#e6e6e6'});" style="color: #000; font-weight: bold; background-color: #e6e6e6;">Cédula</button>
                                    </div>
                                    <!-- Botón que lleva al Ambiente 2 de registro -->
                                    <button type="button" class="btn btn-sm btn-exa-success" style="float: right;" onclick="mostrarFormulario('chofer');">
                                        <i class="glyphicon glyphicon-plus"></i> Registrar Nuevo Operario
                                    </button>
                                </div>
                                <div class="form-inline">
                                    <label class="control-label" style="font-weight: bold; width: 70px;">Buscar:</label>
                                    <input type="text" id="searchChofer" class="form-control input-sm" placeholder="Escriba aquí para buscar..." style="width: 250px;">
                                    <button type="button" class="btn btn-sm btn-primary" id="btnBuscarChofer" onclick="reloadGridChoferes();"><i class="fa fa-search"></i> Buscar</button>
                                </div>
                            </div>
                            <table id="gridChoferes"></table>
                            <div id="pagerChoferes"></div>
                        </div>

                        <!-- Grid Vehículos -->
                        <div role="tabpanel" class="tab-pane" id="tabListVehiculos">
                            <div style="margin-bottom: 15px; background-color: #f9f9f9; padding: 10px; border-radius: 4px; border: 1px solid #eee;">
                                <div class="form-inline" style="margin-bottom: 10px;">
                                    <label class="control-label" style="font-weight: bold; width: 70px;">Filtro:</label>
                                    <input type="hidden" id="opVehiculo" value="p">
                                    <div class="btn-group" style="vertical-align: top;">
                                        <button type="button" class="btn btn-default btn-sm active" onclick="document.getElementById('opVehiculo').value='p'; $(this).addClass('active').css({'color':'#e67e22','background-color':'#fff'}).siblings().removeClass('active').css({'color':'#000','background-color':'#e6e6e6'});" style="color: #e67e22; font-weight: bold; background-color: #fff;">Placa</button>
                                    </div>
                                    <!-- Botón que lleva al Ambiente 2 de registro -->
                                    <button type="button" class="btn btn-sm btn-exa-success" style="float: right;" onclick="mostrarFormulario('vehiculo');">
                                        <i class="glyphicon glyphicon-plus"></i> Registrar Nuevo Vehículo
                                    </button>
                                </div>
                                <div class="form-inline">
                                    <label class="control-label" style="font-weight: bold; width: 70px;">Buscar:</label>
                                    <input type="text" id="searchVehiculo" class="form-control input-sm" placeholder="Buscar por placa..." style="width: 250px;">
                                    <button type="button" class="btn btn-sm btn-primary" id="btnBuscarVehiculo" onclick="reloadGridVehiculos();"><i class="fa fa-search"></i> Buscar</button>
                                </div>
                            </div>
                            <table id="gridVehiculos"></table>
                            <div id="pagerVehiculos"></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ==================== AMBIENTE 2: REGISTRO / FORMULARIO ==================== -->
            <div id="divFormulario" style="display:none;">
                <!-- Contenedor con ancho limitado para que los componentes no se vean tan extendidos o amplios -->
                <div style="max-width: 850px; margin: 0 auto;">

                    <!-- Formulario Chofer Individual -->
                    <div id="divFormTabChofer" style="display:none;">
                        <form id="formChofer" class="form-horizontal" onsubmit="return false;">
                            <fieldset class="exa-fieldset">
                                <legend>Datos Personales del Operario</legend>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Cédula o RUC:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <input id="Cho_Ced" name="Cho_Ced" type="text" class="form-control" placeholder="Cédula o RUC" maxlength="13" onkeypress="return validar_numeric(event);" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Teléfono:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <input id="Cho_Tel" name="Cho_Tel" type="text" class="form-control" placeholder="Teléfono o Celular" maxlength="15" onkeypress="return validar_numeric(event);" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Nombres:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <input id="Prs_Nom" name="Prs_Nom" type="text" class="form-control" placeholder="Nombres del Operario" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Apellidos:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <input id="Prs_Ape" name="Prs_Ape" type="text" class="form-control" placeholder="Apellidos del Operario" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Tipo de Licencia:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <select id="Cho_Tli" name="Cho_Tli" class="form-control">
                                                    <option value="">Seleccione...</option>
                                                    <option value="Np">NO POSEE</option>
                                                    <option value="A">TIPO A</option>
                                                    <option value="B">TIPO B</option>
                                                    <option value="C">TIPO C</option>
                                                    <option value="C1">TIPO C1</option>
                                                    <option value="D">TIPO D</option>
                                                    <option value="D1">TIPO D1</option>
                                                    <option value="E">TIPO E</option>
                                                    <option value="E1">TIPO E1</option>
                                                    <option value="F">TIPO F</option>
                                                    <option value="G">TIPO G</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Caducidad Licencia:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <input id="Cho_Cli" name="Cho_Cli" type="text" class="form-control datepicker" placeholder="dd/mm/aaaa" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Tipo de Sangre:<span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <select id="Cho_Tsa" name="Cho_Tsa" class="form-control">
                                                    <option value="">Seleccione...</option>
                                                    <option value="O+">O+</option>
                                                    <option value="O-">O-</option>
                                                    <option value="A+">A+</option>
                                                    <option value="A-">A-</option>
                                                    <option value="B+">B+</option>
                                                    <option value="B-">B-</option>
                                                    <option value="AB+">AB+</option>
                                                    <option value="AB-">AB-</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="button-center">
                                <button type="button" class="btn btn-custom btn-exa-primary" onclick="guardarChofer();">
                                    <span class="glyphicon glyphicon-floppy-disk"></span> Guardar Operario
                                </button>
                                <button type="button" class="btn btn-custom btn-default" onclick="mostrarListado();">
                                    <span class="glyphicon glyphicon-remove"></span> Cancelar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Formulario Vehículo Individual -->
                    <div id="divFormTabVehiculo" style="display:none;">
                        <form id="formVehiculo" class="form-horizontal" onsubmit="return false;">
                            <fieldset class="exa-fieldset">
                                <legend>Datos Técnicos del Vehículo</legend>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Proveedor (Cédula/RUC):<span class="text-danger">*</span></label>
                                            <div class="col-sm-6">
                                                <div class="input-group">
                                                    <input id="Prv_Ced" name="Prv_Ced" type="text" class="form-control" placeholder="Cédula/RUC Proveedor" maxlength="13" onkeypress="return validar_numeric(event);" />
                                                    <span class="input-group-addon" id="iconProveedorStatus" style="width: 40px; background-color: #eee;">
                                                        <i class="glyphicon glyphicon-minus" style="color: #999;"></i>
                                                    </span>
                                                </div>
                                                <input id="Prv_Nom" name="Prv_Nom" type="text" class="form-control" placeholder="Nombre Proveedor" readonly style="margin-top: 5px;" />
                                                <input id="Prv_Cod" name="Prv_Cod" type="hidden" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Placa (Ej: ABC-1234):<span class="text-danger">*</span></label>
                                            <div class="col-sm-6">
                                                <div class="input-group">
                                                    <input id="Veh_Pla" name="Veh_Pla" type="text" class="form-control" placeholder="Ej: ABC-1234" maxlength="8" />
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default" type="button" onclick="generarPlacaProvisional();" title="Generar placa provisional"><i class="fa fa-random"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Marca:<span class="text-danger">*</span></label>
                                            <div class="col-sm-6">
                                                <div class="input-group">
                                                    <select id="Veh_Mar" name="Veh_Mar" class="form-control">
                                                        <option value="">Seleccione...</option>
                                                        <?php if (isset($rs_marcas) && is_array($rs_marcas)) foreach ($rs_marcas as $m) { ?>
                                                            <option value="<?php echo htmlspecialchars($m['Veh_Mar']); ?>"><?php echo htmlspecialchars($m['Veh_Mar']); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default" type="button" onclick="abrirMiniModal('Veh_Mar', 'Marca');"><i class="fa fa-plus"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Color:<span class="text-danger">*</span></label>
                                            <div class="col-sm-6">
                                                <div class="input-group">
                                                    <select id="Veh_Col" name="Veh_Col" class="form-control">
                                                        <option value="">Seleccione...</option>
                                                        <?php if (isset($rs_colores) && is_array($rs_colores)) foreach ($rs_colores as $c) { ?>
                                                            <option value="<?php echo htmlspecialchars($c['Veh_Col']); ?>"><?php echo htmlspecialchars($c['Veh_Col']); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default" type="button" onclick="abrirMiniModal('Veh_Col', 'Color');"><i class="fa fa-plus"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Tipo Vehículo:<span class="text-danger">*</span></label>
                                            <div class="col-sm-6">
                                                <div class="input-group">
                                                    <select id="Veh_Tit" name="Veh_Tit" class="form-control">
                                                        <option value="">Seleccione...</option>
                                                        <option value="B">BUS(ETA)</option>
                                                        <option value="C">CAMIONETA</option>
                                                        <option value="M">MAQUINARIA</option>
                                                        <option value="T">TRÁILER</option>
                                                        <option value="V" selected>VOLQUETA</option>
                                                        <?php
                                                        $titulos_fijos = array('B', 'C', 'M', 'T', 'V');
                                                        if (isset($rs_titulos) && is_array($rs_titulos)) {
                                                            foreach ($rs_titulos as $t) {
                                                                if (!in_array(trim($t['Veh_Tit']), $titulos_fijos) && trim($t['Veh_Tit']) !== '') {
                                                        ?>
                                                                    <option value="<?php echo htmlspecialchars(trim($t['Veh_Tit'])); ?>"><?php echo htmlspecialchars(trim($t['Veh_Tit'])); ?></option>
                                                        <?php
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default" type="button" onclick="abrirMiniModal('Veh_Tit', 'Tipo Vehículo');"><i class="fa fa-plus"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Detalle Adicional:</label>
                                            <div class="col-sm-6">
                                                <textarea id="Veh_Adi" name="Veh_Adi" class="form-control" rows="2" placeholder="Ingrese un detalle adicional..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm">Valor pactado por hora:</label>
                                            <div class="col-sm-6">
                                                <input id="Veh_Val" name="Veh_Val" type="text" class="form-control" placeholder="Ej. 25.00" maxlength="13" onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46;" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="button-center">
                                <button type="button" class="btn btn-custom btn-exa-primary" onclick="guardarVehiculo();">
                                    <span class="glyphicon glyphicon-floppy-disk"></span> Guardar Vehículo
                                </button>
                                <button type="button" class="btn btn-custom btn-default" onclick="mostrarListado();">
                                    <span class="glyphicon glyphicon-remove"></span> Cancelar
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Cargador Visual / Loader -->
    <div id="loader" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 9999; text-align: center; padding-top: 20%;">
        <div style="display: inline-block; padding: 25px 35px; background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
            <i class="fa fa-spinner fa-spin fa-3x fa-fw" style="color: #334a5f;"></i>
            <div style="margin-top: 15px; font-weight: bold; color: #334a5f; font-size: 14px;">Procesando solicitud...</div>
        </div>
    </div>

    <!-- MODAL REGISTRO RÁPIDO DE PROVEEDOR -->
    <div class="modal fade" id="modalProveedor" tabindex="-1" role="dialog" aria-labelledby="modalProveedorLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog" role="document" style="width: 800px; max-width: 95%;">
            <div class="modal-content">
                <div class="modal-header exa-header" style="background:#334a5f; color:white; padding: 10px 15px; cursor: move;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity: 0.8; margin-top: 2px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="modalProveedorLabel" style="margin: 0; font-size: 15px;"><span class="glyphicon glyphicon-briefcase"></span> Registro Rápido de Proveedor</h4>
                </div>
                <div class="modal-body exa-body" style="max-height: 75vh; overflow-y: auto; padding-top: 15px;">
                    <form id="formRegistroProveedor" class="form-horizontal" onsubmit="return false;">
                        <div class="row">
                            <!-- LADO IZQUIERDO: DATOS DEL PROVEEDOR -->
                            <div class="col-md-6">
                                <fieldset class="exa-fieldset" style="height: 100%;">
                                    <legend style="border-bottom: 1px solid #e5e5e5; font-size: 14px; margin-bottom: 15px; font-weight: bold; color: #334a5f;">DATOS DEL PROVEEDOR</legend>
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label label-sm">Cédula/RUC:<span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <div class="input-group">
                                                <input id="Reg_Prs_Ced" name="Reg_Prs_Ced" type="text" class="form-control input-sm" maxlength="13" onkeypress="return validar_numeric(event);" required="" />
                                                <span class="input-group-addon alert-info" style="padding: 4px 8px;"><input id="Reg_isRuc" type="checkbox" value="S" style="vertical-align: middle; margin:0;"><b> RUC</b></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label label-sm">Documento:<span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select name="Reg_Ide_Cod" id="Reg_Ide_Cod" class="form-control input-sm" required="">
                                                <option value="">Seleccionar</option>
                                                <option value="1">RUC</option>
                                                <option value="2">CEDULA</option>
                                                <option value="3">PASAPORTE</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label label-sm">Contribuyente:<span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select id="Reg_Prv_Tic" name="Reg_Prv_Tic" class="form-control input-sm" required="" onchange="toggleTiposProveedor(this.value)">
                                                <option value="N">NATURAL</option>
                                                <option value="J">JURIDICO</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label label-sm"><span class='reg_natural'>Apellidos:</span><span class='reg_juridico' style="display: none;">Razón Social:</span><span class="text-danger">*</span></label>
                                        <div class="col-sm-8"><input name="Reg_Prs_Ape" id="Reg_Prs_Ape" type="text" class="form-control input-sm" required="" /></div>
                                    </div>
                                    <div class="form-group reg_natural">
                                        <label class="col-sm-4 control-label label-sm">Nombres:</label>
                                        <div class="col-sm-8"><input name="Reg_Prs_Nom" id="Reg_Prs_Nom" type="text" class="form-control input-sm" /></div>
                                    </div>
                                    <div class="form-group reg_natural">
                                        <label class="col-sm-4 control-label label-sm">Genero:</label>
                                        <div class="col-sm-5">
                                            <select name="Reg_Prs_Sex" id="Reg_Prs_Sex" class="form-control input-sm">
                                                <option value="M">MASCULINO</option>
                                                <option value="F">FEMENINO</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group reg_juridico" style="display: none;">
                                        <label class="col-sm-4 control-label label-sm">Nomb.Comerc.:</label>
                                        <div class="col-sm-8"><input name="Reg_Prv_Com" id="Reg_Prv_Com" type="text" class="form-control input-sm" /></div>
                                    </div>
                                </fieldset>
                            </div>

                            <!-- LADO DERECHO: DATOS DE UBICACIÓN -->
                            <div class="col-md-6">
                                <fieldset class="exa-fieldset" style="height: 100%;">
                                    <legend style="border-bottom: 1px solid #e5e5e5; font-size: 14px; margin-bottom: 15px; font-weight: bold; color: #334a5f;">DATOS DE UBICACIÓN</legend>
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label label-sm">Ciudad:<span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select name="Reg_Ciu_Cod" id="Reg_Ciu_Cod" class="form-control input-sm" required>
                                                <option value="">Seleccione...</option>
                                                <?php foreach ($rs_ciudad as $c) { ?>
                                                    <option value="<?php echo $c['Ciu_Cod']; ?>"><?php echo $c['Ciu_Des']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label label-sm">Dirección:<span class="text-danger">*</span></label>
                                        <div class="col-sm-8"><input name="Reg_Prs_Dir" id="Reg_Prs_Dir" type="text" class="form-control input-sm" required="" /></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label label-sm">Teléfono:</label>
                                        <div class="col-sm-8"><input name="Reg_Prs_Tel" id="Reg_Prs_Tel" type="text" class="form-control input-sm" maxlength="15" onkeypress="return validar_numeric(event);" /></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label label-sm">E-Mail:</label>
                                        <div class="col-sm-8"><input id="Reg_Prs_Cor" name="Reg_Prs_Cor" type="email" class="form-control input-sm" /></div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="text-align: center; background: #f5f5f5; border-top: 1px solid #ddd; padding: 10px;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="guardarProveedorRapido();">
                        <span class="glyphicon glyphicon-floppy-disk"></span> Guardar Proveedor
                    </button>
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">
                        <span class="glyphicon glyphicon-remove"></span> Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MINI MODAL PARA AGREGAR OPCIONES (Marca, Color, Tipo) -->
    <div class="modal fade" id="modalMiniOpcion" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="tituloMiniModal">Agregar</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="miniModalSelectId" />
                    <input type="text" id="miniModalInput" class="form-control" placeholder="Ingrese nuevo valor" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm" onclick="guardarMiniModal()">Agregar</button>
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="../VALIDACIONES/man_val_alt_vehiculos_choferes.js?v=2"></script>
</BODY>

</HTML>