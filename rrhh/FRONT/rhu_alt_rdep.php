<?php
/**
 * Pantalla para generar RDEP (Retención en la Fuente de Empleados)
 * Permite subir 4 archivos: Consolidado, Décimo Tercero, Décimo Cuarto y Utilidades
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_rdep.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Rdep($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Rdep;

// Endpoint para procesar los archivos y generar el RDEP
if (isset($_POST['procesarRdep'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    require_once('../LOGICA/rhu_log_rdep.php');
    $resultado = procesarArchivosRdep($_POST, $_FILES, $obBD_con1, $obBD_conexion);
    echo json_encode($resultado);
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>RDEP - Retención en la Fuente de Empleados [EXA]</title>
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <!-- SheetJS para leer archivos XLSX en el navegador -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <style>
        .file-upload-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .file-upload-section label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        .file-upload-section input[type="file"] {
            margin-bottom: 10px;
        }
        .file-status {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        .file-status.loaded {
            color: #28a745;
        }
        .btn-process {
            margin-top: 20px;
            padding: 10px 30px;
            font-size: 16px;
        }
        .file-type-hint {
            font-size: 10px;
            color: #888;
            font-style: italic;
            margin-top: 2px;
        }
        .btn-info-file {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: #17a2b8;
            color: white;
            border: none;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 5px;
            padding: 0;
            line-height: 18px;
            vertical-align: middle;
        }
        .btn-info-file:hover {
            background-color: #138496;
        }
        .file-input-wrapper {
            display: flex;
            align-items: center;
        }
        .file-input-wrapper input[type="file"] {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; RDEP - Retención en la Fuente de Empleados</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-xs-6 form-horizontal normal">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Archivos</legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Consolidado:</label>
                            <div class="col-xs-9">
                                <div class="file-input-wrapper">
                                    <input type="file" id="fileConsolidado" accept=".xlsx,.xls" class="form-control input-xs" />
                                    <button type="button" class="btn-info-file" title="Adjunta un archivo XLSX del Consolidado IESS de Enero a Diciembre del año seleccionado">i</button>
                                </div>
                                <div class="file-type-hint">Archivo: XLSX/XLS - Consolidado IESS (Ene-Dic)</div>
                                <div class="file-status" id="statusConsolidado" style="font-size: 11px; color: #666;"></div>
                            </div>
                        </div>
                    
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Décimo Tercero:</label>
                            <div class="col-xs-9">
                                <div class="file-input-wrapper">
                                    <input type="file" id="fileDecimoTercero" accept=".csv" class="form-control input-xs" />
                                    <button type="button" class="btn-info-file" title="Adjunta el archivo CSV del Formulario de Décimo Tercero del año">i</button>
                                </div>
                                <div class="file-type-hint">Archivo: CSV - Formulario Décimo Tercero</div>
                                <div class="file-status" id="statusDecimoTercero" style="font-size: 11px; color: #666;"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Décimo Cuarto:</label>
                            <div class="col-xs-9">
                                <div class="file-input-wrapper">
                                    <input type="file" id="fileDecimoCuarto" accept=".csv" class="form-control input-xs" />
                                    <button type="button" class="btn-info-file" title="Adjunta el archivo CSV del Formulario de Décimo Cuarto del año">i</button>
                                </div>
                                <div class="file-type-hint">Archivo: CSV - Formulario Décimo Cuarto</div>
                                <div class="file-status" id="statusDecimoCuarto" style="font-size: 11px; color: #666;"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Utilidades (Excel):</label>
                            <div class="col-xs-9">
                                <div class="file-input-wrapper">
                                    <input type="file" id="fileUtilidades" accept=".xlsx,.xls" class="form-control input-xs" />
                                    <button type="button" class="btn-info-file" title="Adjunta el archivo XLSX/XLS de Utilidades del año anterior. Debe contener las columnas DISTR_EQUIT_DE_TR y DISTR_POR_CA para calcular la participación de utilidades">i</button>
                                </div>
                                <div class="file-type-hint">Archivo: XLSX/XLS - Utilidades Excel (año anterior)</div>
                                <div class="file-status" id="statusUtilidades" style="font-size: 11px; color: #666;"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Utilidades (CSV):</label>
                            <div class="col-xs-9">
                                <div class="file-input-wrapper">
                                    <input type="file" id="fileUtilidadesCsv" accept=".csv" class="form-control input-xs" />
                                    <button type="button" class="btn-info-file" title="Adjunta el archivo CSV de Utilidades del año anterior. Contiene los campos para calcular Fondos de Reserva y otros datos adicionales">i</button>
                                </div>
                                <div class="file-type-hint">Archivo: CSV - Utilidades CSV (año anterior)</div>
                                <div class="file-status" id="statusUtilidadesCsv" style="font-size: 11px; color: #666;"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Avisos de Entrada:</label>
                            <div class="col-xs-9">
                                <div class="file-input-wrapper">
                                    <input type="file" id="fileAvisosEntrada" accept=".xlsx,.xls" class="form-control input-xs" />
                                    <button type="button" class="btn-info-file" title="Adjunta un archivo XLSX con encabezados: Cédula, Nombres, Apellidos, Fecha Aviso de Entrada">i</button>
                                </div>
                                <div class="file-type-hint">Archivo: XLSX/XLS - (Cédula, Nombres, Apellidos, Fecha Aviso)</div>
                                <div class="file-status" id="statusAvisosEntrada" style="font-size: 11px; color: #666;"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12 text-right">
                                <button type="button" class="btn btn-success btn-xs" id="btnProcesar" disabled>
                                    <i class="glyphicon glyphicon-refresh"></i> Procesar y Generar RDEP
                                </button>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="col-xs-6 form-horizontal normal">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Año y Rango de Fechas</legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Año:</label>
                            <div class="col-xs-9">
                                <input type="number" id="anioRdep" class="form-control input-xs" min="2000" max="2100" value="<?php echo date('Y'); ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Desde:</label>
                            <div class="col-xs-9">
                                <input type="date" id="dateRdepIni" class="form-control input-xs" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Hasta:</label>
                            <div class="col-xs-9">
                                <input type="date" id="dateRdepFin" class="form-control input-xs" />
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>
            
            <div class="row" style="margin-top: 30px;">
                <div class="col-xs-12" style="min-height:250px;">
                    <table id="gridRdep"></table>
                    <div id="pagerRdep"></div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_rdep.js?x=3"></script>
    <script>
        // Mostrar información al hacer clic en el botón de información
        $(document).ready(function() {
            $('.btn-info-file').on('click', function() {
                var mensaje = $(this).attr('title');
                $.alert(mensaje, 'Información del archivo', 'info');
            });
        });
    </script>
</body>
</html>
