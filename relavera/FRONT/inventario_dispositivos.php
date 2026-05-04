<?php
/**
 * Vista principal del Inventario y Asignación de Dispositivos
 * 
 * @author Antigravity
 * @version 1.2
 * @package relavera.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Administración de Dispositivos</title>
    <meta charset="UTF-8">
    <!-- Carga de estilos y scripts de jqGrid del sistema -->
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    
    <!-- Librería Select2 para buscadores en combos -->
    <link rel="stylesheet" type="text/css" href="../../framework/plugins/select2/select2.min.css" />
    <script type="text/javascript" src="../../framework/plugins/select2/select2.min.js"></script>
    
    <style>
        .exa-header { 
            background-color: #337ab7; 
            color: white;
            border-bottom: 1px solid #dee2e6; 
            padding: 8px 15px;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
        .exa-body { 
            padding: 10px 20px; 
        }
        .label-success { background-color: #28a745 !important; }
        .label-danger { background-color: #dc3545 !important; }
        .modal-header { 
            background-color: #337ab7; 
            color: white; 
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
        .modal-title { font-weight: bold; }
        .btn-primary { background-color: #337ab7; border-color: #2e6da4; }
        .btn-primary:hover { background-color: #286090; border-color: #204d74; }
        
        .ui-search-toolbar input {
            height: 22px !important;
            padding: 1px 5px !important;
            font-size: 11px !important;
        }
        .ui-jqgrid-titlebar {
            background-color: #337ab7 !important;
            color: #ffffff !important;
            border-bottom: 1px solid #ddd !important;
            padding: 8px 15px !important;
            height: auto !important;
        }
        .ui-jqgrid-title {
            font-weight: bold !important;
            font-size: 14px !important;
            float: none !important;
        }
        
        .radioset label { font-size: 11px !important; padding: 2px 10px !important; }
        .label-xs { font-size: 11px; font-weight: bold; }
        .form-horizontal .form-group { margin-bottom: 8px; }

        /* Estilos para pestañas */
        .nav-tabs { margin-bottom: 15px; }
        .tab-content { padding: 10px 0; }
        
        /* Ajustes para tabla masiva */
        .input-masivo {
            width: 100%;
            height: 28px;
            padding: 2px 5px;
            font-size: 12px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        /* Asegurar que los alerts ($.alert) salgan delante del modal */
        .ui-dialog { z-index: 1060 !important; }
        .ui-widget-overlay { z-index: 1055 !important; }
        #loader { z-index: 9999 !important; }

        /* Ajuste Select2 para Bootstrap 3 */
        .select2-container--default .select2-selection--single {
            height: 30px !important;
            border: 1px solid #ccc !important;
            border-radius: 4px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            font-size: 12px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 28px !important;
        }
    </style>
</head>
<body>
    <div class="panel panel-main" style="margin: 10px;">
        <div class="panel-heading exa-header">
            <h3 class="panel-title"><i class="fa fa-laptop"></i> Administración de Dispositivos</h3>
        </div>
        <div class="panel-body exa-body ui-widget-content ui-corner-bottom">
            
            <!-- Estructura de Pestañas -->
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#tabInventario"><i class="fa fa-list"></i> Inventario</a></li>
                <li><a data-toggle="tab" href="#tabAsignacion"><i class="fa fa-user-plus"></i> Asignación</a></li>
            </ul>

            <div class="tab-content">
                <!-- 🟢 TAB 1: INVENTARIO (EXISTENTE) -->
                <div id="tabInventario" class="tab-pane fade in active">
                    <!-- Fila de Filtros -->
                    <div class="row" style="margin-top: 5px; margin-bottom: 5px;">
                        <div class="col-sm-7">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&uacute;squeda</legend>
                                <form id="form_busqueda_principal" class="form-horizontal" onsubmit="return false;">
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>
                                        <div class="col-sm-10">
                                            <div class="radioset">
                                                <input type="radio" id="rad_mac" name="tipo_busqueda" value="mac" checked>
                                                <label for="rad_mac">MAC Address</label>
                                                <input type="radio" id="rad_nombre" name="tipo_busqueda" value="nombre">
                                                <label for="rad_nombre">Nombre Equipo</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 2px;">
                                        <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                        <div class="col-sm-10">
                                            <div class="input-group">
                                                <input type="text" id="txt_busqueda" class="form-control input-sm submit" placeholder="Ingrese b&uacute;squeda..." style="height: 26px;">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-success btn-sm" onclick="buscarGrid()" style="height: 26px; padding: 2px 10px;">
                                                        <i class="fa fa-search"></i> Buscar
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </fieldset>
                        </div>

                        <div class="col-sm-5">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtros</legend>
                                <form id="form_filtros_estado" class="form-horizontal" onsubmit="return false;">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-xs">Estado:</label>
                                        <div class="col-sm-9">
                                            <div class="radioset">
                                                <input type="radio" id="est_todos" name="filtro_estado" value="" checked>
                                                <label for="est_todos">Todos</label>
                                                <input type="radio" id="est_activos" name="filtro_estado" value="A">
                                                <label for="est_activos">Activos</label>
                                                <input type="radio" id="est_inactivos" name="filtro_estado" value="I">
                                                <label for="est_inactivos">Inactivos</label>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </fieldset>
                        </div>
                    </div>

                    <div class="row" style="margin-bottom: 10px;">
                        <div class="col-xs-12 text-right">
                            <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalNuevo()">
                                <i class="fa fa-plus-circle"></i> Nuevo Dispositivo
                            </button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12">
                            <table id="grid_dispositivos"></table>
                            <div id="pager_dispositivos"></div>
                        </div>
                    </div>
                </div>

                <!-- 🔵 TAB 2: ASIGNACIÓN (NUEVO) -->
                <div id="tabAsignacion" class="tab-pane fade">
                    <div class="row">
                        <!-- Panel de Selección de Usuario y Asignación -->
                        <div class="col-sm-4">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Panel de Asignaci&oacute;n</legend>
                                <div class="form-group">
                                    <label class="label-xs">Seleccionar Usuario:</label>
                                    <select id="cmb_usuario" class="form-control input-sm select2" style="width: 100%;">
                                        <option value="">[Seleccione Usuario]</option>
                                    </select>
                                </div>
                                <hr style="margin: 10px 0;">
                                <div class="form-group" style="margin-bottom: 5px;">
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <label class="label-xs">Dispositivos Disponibles:</label>
                                        </div>
                                        <div class="col-xs-6 text-right">
                                            <label style="font-size: 11px; cursor: pointer; font-weight: normal; margin-bottom: 0;">
                                                <input type="checkbox" id="chk_seleccionar_todo" disabled> Seleccionar Todo
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                    <div id="div_disponibles" style="height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background: #fafafa;">
                                        <div class="text-center text-muted" style="margin-top: 130px;">
                                            Seleccione un usuario para ver dispositivos disponibles
                                        </div>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <button type="button" id="btn_asignar" class="btn btn-success btn-block btn-sm" disabled onclick="asignarDispositivos()">
                                            <i class="fa fa-link"></i> Asignar Dispositivos
                                        </button>
                                    </div>
                                </fieldset>
                            </div>

                            <!-- Listado de Dispositivos Asignados -->
                            <div class="col-sm-8">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Dispositivos Asignados</legend>
                                <div id="div_grid_asignados">
                                    <table id="grid_asignados"></table>
                                    <div id="pager_asignados"></div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Formulario Edición Individual -->
    <div class="modal fade" id="modal_dispositivo" tabindex="-1" role="dialog" aria-labelledby="modal_titulo">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity:1;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="modal_titulo">Editar Dispositivo</h4>
                </div>
                <div class="modal-body">
                    <form id="form_dispositivo">
                        <input type="hidden" id="dispositivo_id" value="0">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="mac_address">Dirección MAC <span style="color:red">*</span></label>
                                    <input type="text" class="form-control" id="mac_address" maxlength="17" placeholder="Ej: AA:BB:CC:DD:EE:FF" autocomplete="off">
                                    <small class="text-muted">Formato: XX:XX:XX:XX:XX:XX</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="nombre_equipo">Nombre del Equipo <span style="color:red">*</span></label>
                                    <input type="text" class="form-control" id="nombre_equipo" maxlength="100" placeholder="Ej: PC-CONTABILIDAD-01">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea class="form-control" id="descripcion" rows="3" placeholder="Detalles adicionales..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tipo_dispositivo">Tipo de Dispositivo <span style="color:red">*</span></label>
                                    <select class="form-control" id="tipo_dispositivo">
                                        <option value="PC">PC / Escritorio</option>
                                        <option value="MOVIL">Celular / Móvil</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="estado">Estado</label>
                                    <select class="form-control" id="estado">
                                        <option value="A">Activo</option>
                                        <option value="I">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarDispositivo()">
                        <i class="fa fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registro Masivo (Inventario) -->
    <div class="modal fade" id="modal_masivo" tabindex="-1" role="dialog" aria-labelledby="modal_masivo_titulo">
        <div class="modal-dialog modal-lg" role="document" style="width: 95%;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity:1;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="modal_masivo_titulo">Registro Masivo de Dispositivos</h4>
                </div>
                <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                    <div class="alert alert-info" style="padding: 8px; margin-bottom: 10px;">
                        <i class="fa fa-info-circle"></i> Complete los campos para cada dispositivo. Puede añadir varias filas para guardar todos a la vez.
                    </div>
                    <table class="table table-bordered table-condensed table-striped" id="tabla_masiva">
                        <thead>
                            <tr class="active">
                                <th style="width: 20%;">Dirección MAC <span style="color:red">*</span></th>
                                <th style="width: 20%;">Nombre del Equipo <span style="color:red">*</span></th>
                                <th style="width: 25%;">Descripción</th>
                                <th style="width: 15%;">Tipo</th>
                                <th style="width: 15%;">Estado</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbody_masivo">
                            <!-- Filas dinámicas aquí -->
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-default btn-sm" onclick="agregarFilaMasiva()">
                        <i class="fa fa-plus"></i> Añadir otra fila
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarMasivo()">
                        <i class="fa fa-save"></i> Guardar Todo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script type="text/javascript" src="../VALIDACIONES/inventario_dispositivos.js?e=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="../VALIDACIONES/asignacion_dispositivos.js?e=<?php echo time(); ?>"></script>
    
    <script>
        $(document).ready(function() {
            // Trigger resize/reload al cambiar de pestaña para evitar errores de renderizado en jqGrid
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var target = $(e.target).attr("href");
                if (target === "#tabInventario") {
                    $("#grid_dispositivos").setGridWidth($("#tabInventario").width());
                } else if (target === "#tabAsignacion") {
                    $("#grid_asignados").setGridWidth($("#div_grid_asignados").width());
                }
            });
        });
    </script>
</body>
</html>
