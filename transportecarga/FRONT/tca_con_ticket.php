<?php

/**
 * @abstract Permite consultar, editar, anular e imprimir tickets de cantera
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

// Seccion para cargar datos en el Jqgrid referente a los clientes (diálogo de búsqueda)
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

// Seccion para listar tickets
if (isset($listTicketsAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(50, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(50, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

// Seccion para anular ticket
if (isset($anularTicketAjax)) {
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";

    $data = filter_input_array(INPUT_POST);
    $Tck_Cod = isset($data['Tck_Cod']) ? intval($data['Tck_Cod']) : 0;

    if ($Tck_Cod > 0) {
        $obBD_conexionIns = new Class_Log_Conexion_ticket($Ses_Dat_Dis);
        $obBD_conIns = new Class_Log_Datos_ticket;
        $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);

        try {
            $obBD_conIns->operacionobBD(60, array('Tck_Cod' => $Tck_Cod), $obBD_conexionIns);
            $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);

            if ($obBD_conIns->Error == 0) {
                $response['success'] = true;
                $response['message'] = "Ticket anulado correctamente!";
            } else {
                $response['message'] = $obBD_conIns->MsgError;
            }
        } catch (Exception $e) {
            $obBD_conIns->rollBack_nomsn($obBD_conexionIns->conexion);
            $response['message'] = $e->getMessage();
        }
    } else {
        $response['message'] = "Código de ticket inválido";
    }

    echo json_encode($response);
    exit();
}

?>
<!DOCTYPE html>
<meta charset="UTF-8">
<HTML>

<HEAD>
    <TITLE><?php echo $Ses_Sys_Nom; ?></TITLE>
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Consulta de Tickets de Cantera</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="bus_ticket" class="row">
                <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:$('#Lis_Ticket').Search('#frm_bus','listTicketsAjax');">
                    <div class="col-md-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Búsqueda</legend>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm">Cliente:</label>
                                <div class="col-md-7 col-sm-7">
                                    <div class="input-group">
                                        <input type="hidden" id="Cli_Cod" name="Cli_Cod" value="">
                                        <input type="text" id="Cli_Des" name="Cli_Des" class="form-control input-xs" placeholder="Seleccione cliente, cédula o placa..." readonly="">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="$('#clienteDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                            <button class="btn btn-default btn-xs" type="button" title="Limpiar cliente" onclick="$('#Cli_Cod').val(''); $('#Cli_Des').val('');"><span class="glyphicon glyphicon-remove"></span></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm">Placa:</label>
                                <div class="col-md-7 col-sm-7">
                                    <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Placa del vehículo..." onkeydown="if (event.keyCode === 13) this.form.submit()">
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-md-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Filtros</legend>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm">Estado:</label>
                                <div class="col-md-7 col-sm-7 radioset">
                                    <input id="r1" name="Tck_Est" type="radio" value="" checked="" onclick="setfocus(this.form.search)" /><label for="r1">&nbsp;&nbsp;Todos&nbsp;&nbsp;</label>
                                    <input id="r2" name="Tck_Est" type="radio" value="A" onclick="setfocus(this.form.search)" /><label for="r2">&nbsp;&nbsp;Activos&nbsp;&nbsp;</label>
                                    <input id="r3" name="Tck_Est" type="radio" value="I" onclick="setfocus(this.form.search)" /><label for="r3">&nbsp;&nbsp;Inactivos&nbsp;&nbsp;</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm">Tipo:</label>
                                <div class="col-md-7 col-sm-7">
                                    <select id="Tck_Tip" name="Tck_Tip" class="form-control input-xs" onchange="setfocus(this.form.search)">
                                        <option value="">Todos</option>
                                        <option value="F">Facturado</option>
                                        <option value="N">No facturado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm">Fecha:</label>
                                <div class="col-md-7 col-sm-7">
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon alert-info">Desde</span>
                                        <input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control datepicker">
                                        <span class="input-group-addon alert-info">Hasta</span>
                                        <input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control datepicker">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-offset-3 col-md-7">
                                    <button class="btn btn-success btn-xs" type="button" title="Buscar" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </form>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div>
                        <table id="Lis_Ticket"></table>
                        <div id="Pag_Lis"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Diálogo para buscar cliente (filtrar tickets por cliente) -->
    <div id="clienteDialog" style="display:none;"></div>

    <script type="text/javascript">
        // Al seleccionar cliente en el diálogo de filtro: actualizar formulario y cerrar
        function seleccionarClienteFiltro(rowObject) {
            $('#Cli_Cod').val(rowObject.Cli_Cod || '');
            $('#Cli_Des').val(rowObject.cliente || (rowObject.Prs_Ced || ''));
            $('#clienteDialog').dialog('close');
        }

        // Funciones globales para editar, anular e imprimir tickets
        function editarTicket(rowObject) {
            if (rowObject.Tck_Cod) {
                window.location.href = 'tca_alt_ticket.php?Tck_Cod=' + rowObject.Tck_Cod;
            }
        }

        function anularTicket(rowObject) {
            if (rowObject.Tck_Cod) {
                $.createDialogConfirm('¿Está seguro que desea anular este ticket?', null, function() {
                    $.post("", {
                                anularTicketAjax: true,
                                Tck_Cod: rowObject.Tck_Cod
                            },
                            function(response) {
                                if (response['success'] === true) {
                                    $.alert("Ticket anulado correctamente!");
                                    $('#Lis_Ticket').trigger('reloadGrid', []);
                                } else {
                                    $.alert(response['message']);
                                }
                            }, 'json')
                        .fail(function() {
                            $.alert('Error al anular el ticket');
                        });
                });
            }
        }

        function imprimirTicketConsulta(rowObject) {
            if (rowObject.Tck_Cod) {
                // Usar formato ESC/POS por defecto - abrir directamente para imprimir
                var ventana = window.open(
                    "tca_alt_ticket.php?imprimirTicketAjax=true&Tck_Cod=" + rowObject.Tck_Cod + "&formato=escpos&accion=preview",
                    '_blank',
                    'width=600,height=700'
                );
                ventana.focus();
            }
        }

        $(function() {
            //Inicialización
            $.createDatePickers('.datepicker');
            $.createDateRange('#Fec_Ini', '#Fec_Fin');

            // Diálogo para buscar cliente (mismo estilo que en alta de ticket)
            $.createSearchDialog('#clienteDialog', [
                { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, hidden: true },
                { label: 'C&eacute;dula', name: 'Prs_Ced', width: 30 },
                { label: 'Cliente', name: 'cliente', width: 70 },
                {
                    label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act', width: 18, align: 'center', viewable: false,
                    formatter: function (cellvalue, options, rowObject) {
                        return $.getGridButton(seleccionarClienteFiltro, rowObject);
                    }
                }
            ], null, null, null, null, {
                title: 'Buscar Cliente',
                options: [{ label: '&nbsp;&nbsp;Nombre&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c' }]
            });
            $('#clienteDialog').on('dialogopen', function () {
                setTimeout(function () { $.Search('cliente'); }, 200);
            });

            //Inicio Grid para presentar la lista de tickets
            $("#Lis_Ticket").createGrid({
                postData: $("#frm_bus").getData("listTicketsAjax"),
                height: 295,
                colModel: [{
                        label: 'Tck_Cod',
                        name: 'Tck_Cod',
                        key: true,
                        hidden: true
                    },
                    {
                        label: 'Número',
                        name: 'Tck_Num',
                        width: 60,
                        align: "center"
                    },
                    {
                        label: 'Fecha',
                        name: 'Tck_Fec',
                        width: 80,
                        align: "center",
                        formatter: function(cellvalue) {
                            if (cellvalue) {
                                var fecha = new Date(cellvalue);
                                return fecha.getDate() + '/' + (fecha.getMonth() + 1) + '/' + fecha.getFullYear() + ' ' + fecha.getHours() + ':' + (fecha.getMinutes() < 10 ? '0' : '') + fecha.getMinutes();
                            }
                            return '';
                        }
                    },
                    {
                        label: 'Cliente',
                        name: 'cliente_nombre',
                        width: 150,
                        align: "left"
                    },
                    {
                        label: 'Cédula/RUC',
                        name: 'Prs_Ced',
                        width: 80,
                        align: "center"
                    },
                    {
                        label: 'Vehículo',
                        name: 'Veh_Pla',
                        width: 80,
                        align: "center"
                    },
                    {
                        label: 'Valor Neto',
                        name: 'Tck_Val',
                        width: 80,
                        align: "right",
                        formatter: 'number',
                        formatoptions: {
                            decimalPlaces: 4
                        }
                    },
                    {
                        label: 'IVA',
                        name: 'Tck_IvA',
                        width: 70,
                        align: "right",
                        formatter: 'number',
                        formatoptions: {
                            decimalPlaces: 4
                        }
                    },
                    {
                        label: 'Total',
                        name: 'Tck_Tot',
                        width: 90,
                        align: "right",
                        formatter: 'number',
                        formatoptions: {
                            decimalPlaces: 4
                        }
                    },

                    {
                        label: 'Tipo',
                        name: 'Tck_Tip',
                        width: 60,
                        align: "center",
                        formatter: function(cellvalue) {
                            return cellvalue === 'F' ? 'Facturado' : 'No Facturado';
                        }
                    },
                    {
                        label: 'Pago',
                        name: 'Tck_Pag',
                        width: 70,
                        align: "center",
                        formatter: function(cellvalue) {
                            if (cellvalue === 'C') return 'Crédito';
                            if (cellvalue === 'F') return 'Firma';
                            return 'Efectivo';
                        }
                    },
                    {
                        label: 'Estado',
                        name: 'Tck_Est',
                        hidden: true,
                        width: 60,
                        align: "center",
                        formatter: function(cellvalue) {
                            return cellvalue === 'A' ? 'Activo' : 'Inactivo';
                        }
                    },
                    {
                        label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                        name: 'act',
                        width: 90,
                        align: 'center',
                        viewable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            var botones = '';
                            if (rowObject.Tck_Est === 'A') {
                                botones += $.getGridButton(editarTicket, rowObject, 'Editar', 'glyphicon glyphicon-edit', '', 'primary');
                                botones += ' ';
                                botones += $.getGridButton(anularTicket, rowObject, 'Anular', 'glyphicon glyphicon-remove', '', 'danger');
                            }
                            botones += ' ';
                            botones += $.getGridButton(imprimirTicketConsulta, rowObject, 'Imprimir', 'glyphicon glyphicon-print', '', 'info');
                            return botones;
                        }
                    }
                ],
                footerrow: true,
                loadComplete: function(data) {
                    if ($.varValid(data.rows)) {
                        var total = 0,
                            iva = 0,
                            neto = 0;
                        for (var i = 0, z = data.rows.length; i < z; i++) {
                            if (data.rows[i]['Tck_Est'] === 'I') $("#" + data.rows[i].Tck_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed1');
                            neto += parseFloat(data.rows[i]['Tck_Val'] || 0);
                            iva += parseFloat(data.rows[i]['Tck_IvA'] || 0);
                            total += parseFloat(data.rows[i]['Tck_Tot'] || 0);
                        }
                        $('#Lis_Ticket').jqGrid('footerData', 'set', {
                            Tck_Num: 'TOTALES:',
                            Tck_Val: neto.toFixed(4),
                            Tck_IvA: iva.toFixed(4),
                            Tck_Tot: total.toFixed(4)
                        });
                    }
                }
            }, false, "#Pag_Lis", { view: false, refresh: true }).gridButtonsAdd([
                { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download',
                    onClickButton: function () { //descargar excel de anticipos del manifiesto
                        $("#Lis_Ticket").jqGrid('exportGridExcel', {
                            nombre: 'Ticket-Cantera',
                            hoja: 'HOJA 1',
                            footer: true,
                            // removeHiddens: true,
                            // removeCols: [1, 10]
                        });
                    }
                }
            ]);
        });
    </script>
</BODY>

</HTML>