<?php

/**
 * @abstract CRUD para parametrizar campos requeridos para generar facturas automáticas por cada manifiesto
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de creacion 2025-12-02
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_fac_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_manifiesto($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_manifiesto;

$hoy = date("Y-m-d");

// ==================== OPERACIONES AJAX ====================

// Listar parámetros
if (isset($listarParametros)) {
    $data = $obBD_con1->getArrayConsulta(57, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    $response = array('rows' => $data, 'records' => count($data));
    echo json_encode($response);
    exit();
}

// Obtener un parámetro específico
if (isset($getParametro)) {
    $data = $obBD_con1->getRowConsulta(58, array('Prm_Cod' => $Prm_Cod), $obBD_conexion);
    echo json_encode($data);
    exit();
}

// Guardar parámetro (insert o update)
if (isset($guardarParametro)) {
    $resp = array('success' => false);
    try {
        $obBD_con1->inicio_transaccion($obBD_conexion);
        if (isset($Prm_Cod) && !empty($Prm_Cod) && $Prm_Cod > 0) {
            // Actualizar
            $obBD_con1->operacionobBD(60, array(
                'Prm_Cod' => $Prm_Cod,
                'Pld_Cod' => $Pld_Cod,
                'Pro_Cod' => $Pro_Cod,
                'Tpc_Cod' => $Tpc_Cod
            ), $obBD_conexion);
            $resp['message'] = 'Parámetro actualizado correctamente';
        } else {
            // Validar que solo exista un registro por empresa
            $existente = $obBD_con1->getRowConsulta(65, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
            if ($existente && $existente['total'] > 0) {
                throw new Exception('Ya existe una parametrización para esta empresa. Solo puede tener un registro.');
            }
            // Insertar
            $obBD_con1->operacionobBD(59, array(
                'Pld_Cod' => $Pld_Cod,
                'Pro_Cod' => $Pro_Cod,
                'Tpc_Cod' => $Tpc_Cod,
                'Emp_Cod' => $Ses_Emp_Cod
            ), $obBD_conexion);
            $resp['Prm_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
            $resp['message'] = 'Parámetro creado correctamente';
        }
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
        } else {
            $resp['message'] = 'Error al guardar: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
    }
    echo json_encode($resp);
    exit();
}

// Eliminar parámetro
if (isset($eliminarParametro)) {
    $resp = array('success' => false);
    try {
        $obBD_con1->inicio_transaccion($obBD_conexion);
        $obBD_con1->operacionobBD(61, array('Prm_Cod' => $Prm_Cod), $obBD_conexion);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
            $resp['message'] = 'Parámetro eliminado correctamente';
        } else {
            $resp['message'] = 'Error al eliminar: ' . $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
    }
    echo json_encode($resp);
    exit();
}

// Obtener cuentas de pago para select
if (isset($getCuentasPago)) {
    $data = $obBD_con1->getArrayConsulta(62, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    echo json_encode($data);
    exit();
}

// Obtener productos para select
if (isset($getProductos)) {
    $data = $obBD_con1->getArrayConsulta(63, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    echo json_encode($data);
    exit();
}

// Obtener tipos de pago para select
if (isset($getTiposPago)) {
    $data = $obBD_con1->getArrayConsulta(64, array(), $obBD_conexion);
    echo json_encode($data);
    exit();
}
// Cargar datos para los selects
$cuentasPago = $obBD_con1->getArrayConsulta(62, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
$productos = $obBD_con1->getArrayConsulta(63, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
$tiposPago = $obBD_con1->getArrayConsulta(64, array(), $obBD_conexion);

?>
<!DOCTYPE html>
<html>
<head>
    <TITLE><?php echo "Parámetros de Manifiesto [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style>
        .form-param {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .form-param .form-group {
            margin-bottom: 10px;
        }

        .btn-actions {
            margin-top: 10px;
        }

        .selected-row {
            background-color: #d4edda !important;
        }
    </style>
</head>

<body>
    <input type="hidden" id="Emp_Cod" name="Emp_Cod" value="<?php echo $Ses_Emp_Cod ?>">
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Parámetros de Manifiesto</h3><br>
            <p class="text" style="margin-top:-10px; font-size:12px;">Configuración de campos para generación automática de facturas</p>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <!-- Formulario -->
            <div class="form-param">
                <form id="paramForm" class="form-horizontal">
                    <input type="hidden" id="Prm_Cod" name="Prm_Cod" value="">

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Cuenta de Pago <span class="text-danger">*</span></label>
                                <select id="Pld_Cod" name="Pld_Cod" class="form-control input-sm chosen-select" required>
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($cuentasPago as $cuenta) { ?>
                                        <option value="<?php echo $cuenta['Pld_Cod']; ?>">
                                            <?php echo $cuenta['Pld_Des']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Producto <span class="text-danger">*</span></label>
                                <select id="Pro_Cod" name="Pro_Cod" class="form-control input-sm chosen-select" required>
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($productos as $producto) { ?>
                                        <option value="<?php echo $producto['Pro_Cod']; ?>">
                                            <?php echo $producto['Pro_Bar'] . ' - ' . $producto['Pro_Nom']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Tipo de Pago <span class="text-danger">*</span></label>
                                <select id="Tpc_Cod" name="Tpc_Cod" class="form-control input-sm chosen-select" required>
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($tiposPago as $tipo) { ?>
                                        <option value="<?php echo $tipo['Tpc_Cod']; ?>">
                                            <?php echo $tipo['Tpc_Des']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="btn-actions text-right">
                        <button type="button" id="btnNuevo" class="btn btn-default btn-sm" onclick="nuevoParametro()">
                            <i class="glyphicon glyphicon-file"></i> Nuevo
                        </button>
                        <button type="submit" id="btnGuardar" class="btn btn-success btn-sm">
                            <i class="glyphicon glyphicon-floppy-disk"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
            <!-- Grid de parámetros -->
            <div style="min-height: 250px;">
                <table id="paramGrid"></table>
                <div id="paramGridPager"></div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>

    <script>
        var paramGrid;
        var selectedPrmCod = null;

        $(function() {
            // Inicializar Chosen para los selects
            $('.chosen-select').chosen({
                width: '100%',
                no_results_text: 'No se encontraron resultados',
                placeholder_text_single: '-- Seleccione --'
            });

            // Crear el grid
            paramGrid = $('#paramGrid').createGrid({
                caption: 'Parámetros Configurados',
                url: '?listarParametros=true',
                datatype: 'json',
                height: 200,
                colModel: [
                    { label: 'Código',   name: 'Prm_Cod', width: 60, align: 'center', key: true  },
                    { label: 'Cuenta de Pago', name: 'Pld_Des', width: 200  },
                    {  label: 'Producto',  name: 'Pro_Obs', width: 200 },
                    { label: 'Descripción',  name: 'Ite_Lar', width: 200 },
                    { label: 'Tipo de Pago', name: 'Tpc_Des', width: 150 },
                    { label: 'Acción', name: 'accion', width: 60, align: 'center',
                        sortable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            return '<button type="button" class="btn btn-danger btn-xs btn-eliminar-fila" data-cod="' + rowObject.Prm_Cod + '" title="Eliminar"><i class="fa fa-trash"></i></button>';
                        }
                    },
                    { label: 'Pld_Cod', name: 'Pld_Cod', hidden: true },
                    { label: 'Pro_Cod',  name: 'Pro_Cod', hidden: true  },
                    {  label: 'Tpc_Cod',  name: 'Tpc_Cod', hidden: true }
                ],
                onSelectRow: function(rowId) {
                    if (rowId) {
                        cargarParametro(rowId);
                    }
                },
                loadComplete: function(data) {
                    // Limpiar selección previa
                    $('#paramGrid tr').removeClass('selected-row');
                    
                    // Evento para botón eliminar en fila
                    $('.btn-eliminar-fila').off('click').on('click', function(e) {
                        e.stopPropagation();
                        var prmCod = $(this).data('cod');
                        eliminarParametroDirecto(prmCod);
                    });                  
                    // Deshabilitar botón Nuevo si ya existe un registro
                    if (data.records && data.records > 0) {
                        $('#btnNuevo').prop('disabled', true).attr('title', 'Ya existe una parametrización');
                    } else {
                        $('#btnNuevo').prop('disabled', false).attr('title', '');
                    }
                }
            }, false, '#paramGridPager', {  refresh: true,  add: false,   edit: false,  del: false  });

            // Validación y envío del formulario
            $('#paramForm').validate({
                rules: {
                    Pld_Cod: {  required: true },
                    Pro_Cod: { required: true  },
                    Tpc_Cod: { required: true }
                },
                messages: {
                    Pld_Cod: {  required: 'Seleccione una cuenta de pago'  },
                    Pro_Cod: { required: 'Seleccione un producto' },
                    Tpc_Cod: { required: 'Seleccione un tipo de pago'  }
                },
                submitHandler: function(form) { guardarParametro(); }
            });
        });

        // Cargar parámetro seleccionado
        function cargarParametro(prmCod) {
            $.get('', {
                getParametro: true,
                Prm_Cod: prmCod
            }, function(data) {
                if (data) {
                    $('#Prm_Cod').val(data.Prm_Cod);
                    $('#Pld_Cod').val(data.Pld_Cod).trigger('chosen:updated');
                    $('#Pro_Cod').val(data.Pro_Cod).trigger('chosen:updated');
                    $('#Tpc_Cod').val(data.Tpc_Cod).trigger('chosen:updated');
                    selectedPrmCod = data.Prm_Cod;
                    // Resaltar fila seleccionada
                    $('#paramGrid tr').removeClass('selected-row');
                    $('#paramGrid tr[id="' + prmCod + '"]').addClass('selected-row');
                }
            }, 'json');
        }

        // Nuevo parámetro
        function nuevoParametro() {
            $('#Prm_Cod').val('');
            $('#Pld_Cod').val('').trigger('chosen:updated');
            $('#Pro_Cod').val('').trigger('chosen:updated');
            $('#Tpc_Cod').val('').trigger('chosen:updated');
            selectedPrmCod = null;
            $('#paramGrid tr').removeClass('selected-row');
            $('#Pld_Cod').trigger('chosen:activate');
        }

        // Guardar parámetro
        function guardarParametro() {
            var data = {
                guardarParametro: true,
                Prm_Cod: $('#Prm_Cod').val(),
                Pld_Cod: $('#Pld_Cod').val(),
                Pro_Cod: $('#Pro_Cod').val(),
                Tpc_Cod: $('#Tpc_Cod').val()
            };

            $.post('', data, function(resp) {
                if (resp.success) {
                    $.alert(resp.message);
                    $('#paramGrid').trigger('reloadGrid');
                    nuevoParametro();
                } else {
                    $.alert(resp.message || 'Error al guardar');
                }
            }, 'json').fail(function() {
                $.alert('Error de conexión con el servidor');
            });
        }

        // Eliminar parámetro (desde ícono de fila)
        function eliminarParametroDirecto(prmCod) {
            $.createDialogConfirm('¿Está seguro de eliminar esta parametrización?', null, function() {
                $.post('', {
                    eliminarParametro: true,
                    Prm_Cod: prmCod
                }, function(resp) {
                    if (resp.success) {
                        $.alert(resp.message);
                        $('#paramGrid').trigger('reloadGrid');
                        nuevoParametro();
                    } else {
                        $.alert(resp.message || 'Error al eliminar');
                    }
                }, 'json').fail(function() {
                    $.alert('Error de conexión con el servidor');
                });
            });
        }
    </script>
</body>

</html>
