<?php
/**
 * @abstract Modulo para anular descuentos de roles.
 * @version 1.0
 */
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log_descuentos.txt');
error_reporting(E_ALL);

ob_start();

try {
    require_once('../../administrador/LOGICA/seguridad.php');
    require_once('../LOGICA/rhu_log_roles.php');
    require_once('../../Librerias/procedimientos/almacenados_standar.php');

    $obBD_conexion = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
    $obBD_con1 = new Class_Log_Datos_Rol;

    if (isset($_REQUEST['searchDescuentos'])) {
        ob_clean();

        $data = $_GET;
        if (empty($data['fini'])) {
            $data['fini'] = date('Y-m-01');
        }
        if (empty($data['ffin'])) {
            $data['ffin'] = date('Y-m-t');
        }

        $sqlData = array(
            'fini' => $data['fini'],
            'ffin' => $data['ffin'],
            'cedula' => isset($_GET['cedula']) ? $_GET['cedula'] : '',
            'apellidos' => isset($_GET['apellidos']) ? $_GET['apellidos'] : '',
            'Ant_Est' => isset($_GET['Ant_Est']) ? $_GET['Ant_Est'] : 'A',
            'Rol_Apl' => isset($_GET['Rol_Apl']) ? $_GET['Rol_Apl'] : 'P'
        );

        $rows = $obBD_con1->getArrayConsulta(78, $sqlData, $obBD_conexion);
        $safeRows = array();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $newRow = array();
                foreach ($row as $key => $val) {
                    if (is_string($val)) {
                        $val = utf8_encode($val);
                        $val = str_replace(array("\r", "\n"), ' ', $val);
                        $newRow[$key] = trim($val);
                    } else {
                        $newRow[$key] = $val;
                    }
                }
                $safeRows[] = $newRow;
            }
        }

        $response = array(
            'page' => 1,
            'total' => 1,
            'rows' => $safeRows,
            'records' => count($safeRows),
            'success' => true
        );

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    if (isset($_POST['anularDescuento']) || isset($_REQUEST['anularDescuento'])) {
        ob_clean();

        $obBD_ins1 = new Class_Log_Datos_Rol;
        $obBD_conexionIns = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
        $obBD_ins1->inicio_transaccion($obBD_conexionIns);

        try {
            $Ant_Cod = isset($_POST['Ant_Cod']) ? $_POST['Ant_Cod'] : $_REQUEST['Ant_Cod'];

            $obBD_ins1->operacionobBD(79, array($Ant_Cod), $obBD_conexionIns);

            $comCodData = $obBD_con1->getRowConsulta(75, array($Ant_Cod), $obBD_conexion);
            if (!empty($comCodData['Com_Cod'])) {
                $obBD_ins1->operacionobBD(51, array($comCodData['Com_Cod']), $obBD_conexionIns);
            }

            $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
            $response = array('success' => true, 'message' => 'Descuento y comprobante anulados correctamente.');
        } catch (Exception $e) {
            $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
            $response = array('success' => false, 'message' => $e->getMessage());
        }

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Exception: ' . $e->getMessage()));
    exit();
}

ob_end_flush();
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE>Anular Descuentos [EXA]</TITLE>
        <meta charset="UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <style>
            .exa-header { background-color: #2c3e50; color: white; padding: 10px 14px; border-radius: 5px 5px 0 0; }
            .exa-body { padding: 12px 14px 14px; border: 1px solid #ddd; border-top: none; background: #fff; }
            .panel-main { margin: 16px; }

            .desc-filter-bar {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                gap: 10px 12px;
                padding: 10px 12px;
                margin-bottom: 10px;
                background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            }
            .desc-filter-fields {
                display: flex;
                flex: 1 1 640px;
                flex-wrap: wrap;
                align-items: flex-end;
                gap: 8px 12px;
                min-width: 0;
            }
            .desc-filter-item label {
                display: block;
                margin: 0 0 3px;
                font-size: 10px;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #64748b;
            }
            .desc-filter-item .form-control {
                height: 28px;
                padding: 4px 8px;
                border-color: #cbd5e1;
                border-radius: 6px;
                box-shadow: none;
                font-size: 12px;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }
            .desc-filter-item .form-control:focus {
                border-color: #3b82f6;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
            }
            .desc-filter-period { flex: 0 0 auto; }
            .desc-date-pair {
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .desc-date-pair .form-control { width: 106px; }
            .desc-date-sep { color: #94a3b8; font-size: 11px; line-height: 1; }
            .desc-filter-estado { flex: 0 0 118px; }
            .desc-filter-rol { flex: 0 0 148px; }
            .desc-filter-cedula { flex: 0 0 132px; }
            .desc-filter-apellidos { flex: 1 1 160px; min-width: 140px; }
            .desc-filter-actions {
                display: flex;
                align-items: flex-end;
                flex: 0 0 auto;
            }
            .desc-btn-search {
                height: 28px;
                padding: 0 14px;
                font-size: 12px;
                font-weight: 600;
                border-radius: 6px;
                border: none;
                background: linear-gradient(180deg, #5cb85c 0%, #449d44 100%);
                box-shadow: 0 1px 2px rgba(68, 157, 68, 0.35);
            }
            .desc-btn-search:hover,
            .desc-btn-search:focus {
                background: linear-gradient(180deg, #449d44 0%, #398439 100%);
            }
            .desc-grid-wrap { min-height: 250px; margin-top: 4px; }

            @media (max-width: 768px) {
                .desc-filter-bar { padding: 10px; }
                .desc-filter-fields { flex-direction: column; align-items: stretch; }
                .desc-filter-period,
                .desc-filter-estado,
                .desc-filter-rol,
                .desc-filter-cedula,
                .desc-filter-apellidos { flex: 1 1 auto; width: 100%; }
                .desc-date-pair .form-control { width: 100%; flex: 1; }
                .desc-filter-actions { width: 100%; }
                .desc-btn-search { width: 100%; }
            }
        </style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Anular Descuentos de Roles</h3>
        </div>

        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="main-search">
                <form id="formSearchDes" action="javascript:searchDescuentos();">
                    <div class="desc-filter-bar date-ranges">
                        <div class="desc-filter-fields">
                            <div class="desc-filter-item desc-filter-period">
                                <label>Per&iacute;odo</label>
                                <div class="desc-date-pair">
                                    <input name="fini" type="text" id="fini" class="form-control input-xs" value="<?php echo date('Y-m-01'); ?>" title="Desde" />
                                    <span class="desc-date-sep" aria-hidden="true">&mdash;</span>
                                    <input name="ffin" type="text" id="ffin" class="form-control input-xs" value="<?php echo date('Y-m-t'); ?>" title="Hasta" />
                                </div>
                            </div>
                            <div class="desc-filter-item desc-filter-estado">
                                <label>Estado</label>
                                <select name="Ant_Est" id="Ant_Est" class="form-control input-xs">
                                    <option value="A" selected>Activo</option>
                                    <option value="I">Anulado</option>
                                    <option value="T">Todos</option>
                                </select>
                            </div>
                            <div class="desc-filter-item desc-filter-rol">
                                <label>Aplicaci&oacute;n Rol</label>
                                <select name="Rol_Apl" id="Rol_Apl" class="form-control input-xs">
                                    <option value="P" selected>Pendiente</option>
                                    <option value="A">Aplicado en rol</option>
                                    <option value="T">Todos</option>
                                </select>
                            </div>
                            <div class="desc-filter-item desc-filter-cedula">
                                <label>C&eacute;dula</label>
                                <input name="cedula" type="text" id="cedula" class="form-control input-xs" placeholder="0912345678" />
                            </div>
                            <div class="desc-filter-item desc-filter-apellidos">
                                <label>Apellidos</label>
                                <input name="apellidos" type="text" id="apellidos" class="form-control input-xs" placeholder="Buscar por apellidos..." />
                            </div>
                        </div>
                        <div class="desc-filter-actions">
                            <button type="submit" class="btn btn-success desc-btn-search">
                                <i class="glyphicon glyphicon-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </form>

                <div class="desc-grid-wrap">
                    <table id="gridDescuentos"></table><div id="pagerDescuentos"></div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            $.createDateRange('#fini', '#ffin');

            var model = [
                { label: 'C&oacute;d.', name: 'Ant_Cod', key: true, width: 50, align: "center" },
                { label: 'C&oacute;d. Com.', name: 'Com_Cod', width: 80, align: "center", hidden: true },
                { label: 'Fecha', name: 'Ant_Fec', width: 80, align: "center" },
                { label: 'C&eacute;dula', name: 'Prs_Ced', width: 90, align: "center" },
                { label: 'Apellidos', name: 'Prs_Ape', width: 130 },
                { label: 'Nombres', name: 'Prs_Nom', width: 130 },
                { label: 'Observaci&oacute;n', name: 'Ant_Obs', width: 240 },
                { label: 'Valor', name: 'Ant_Val', width: 80, align: 'right', formatter: 'currency' },
                { label: 'Estado', name: 'Ant_Est', width: 60, align: 'center', formatter: 'estado' },
                { label: 'Rol', name: 'Rol_Cod', width: 55, align: 'center' },
                { label: 'Est. Rol', name: 'Rol_Est', width: 55, align: 'center', formatter: 'estado', hidden: true },
                { label: 'Comprobante', name: 'Com_Num', width: 80, align: 'center', hidden: true },
                { label: 'Acci&oacute;n', name: 'Inac', width: 60, align: 'center', formatter: function(cellvalue, options, rowObject) {
                    var estado = rowObject.Ant_Est || rowObject[8];
                    var rolCod = rowObject.Rol_Cod || rowObject[9];
                    var rolEst = rowObject.Rol_Est || rowObject[10];
                    var pendiente = !rolCod || rolCod === '' || rolCod === '0';
                    var rolInactivo = rolEst === 'I';
                    if (estado == 'A' && (pendiente || rolInactivo)) {
                        return $.getGridButton({
                            action: anularDescuento,
                            type: 'danger',
                            icon: 'trash',
                            data: rowObject.Ant_Cod || rowObject[0],
                            title: rolInactivo ? 'Anular (rol inactivo)' : 'Anular'
                        });
                    }
                    if (estado == 'A') {
                        return '<i class="glyphicon glyphicon-ok-sign" style="color: #5cb85c; font-size: 1.2em;" title="Aplicado en rol activo"></i>';
                    }
                    return '<i class="glyphicon glyphicon-remove" style="color: #d9534f; font-size: 1.2em;" title="Anulado"></i>';
                }}
            ];

            $('#gridDescuentos').jqGrid({
                datatype: "local",
                colModel: model,
                viewrecords: true,
                autowidth: true,
                height: 330,
                rowNum: 20,
                pager: "#pagerDescuentos",
                caption: "Listado de Descuentos",
                rowattr: function (rd) {
                    if (rd.Ant_Est === "I" || rd.Ant_Est === "Inactivo") {
                        return { "style": "background: #ffcccc !important; color: black;" };
                    }
                }
            });

            searchDescuentos();
        });

        function searchDescuentos() {
            var data = $('#formSearchDes').serialize();
            var url = 'rhu_baj_descuento.php?searchDescuentos=true&' + data;
            $('#gridDescuentos').jqGrid('setGridParam', {
                url: url,
                datatype: "json"
            }).trigger("reloadGrid");
        }

        function anularDescuento(Ant_Cod) {
            $.createDialogConfirm('�Est� seguro que desea anular este Descuento y su Comprobante Contable?',
                { anularDescuento: true, Ant_Cod: Ant_Cod },
                function(data) {
                    $.saveDataJson("rhu_baj_descuento.php", data, function(resp) {
                        $.alert(resp.message);
                        if (resp.success) {
                            searchDescuentos();
                        }
                    });
                }
            );
        }
    </script>
</BODY>
</HTML>
