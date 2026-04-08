<?php

/**
 * Descripción: Permite verificar reporte de ventas por cada vendedor
 * Fecha de actualización:	2024/04/24
 * Desarrollador: Wilson Belduma
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_vendedor.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/*
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);
/**
 * Creación del Objeto para consultas
 */
$obBD_con1 =  new Class_Log_Datos_Pro();
if (isset($vendedorAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $data["Suc_Cod"] = $Ses_Suc_Cod;
    $contar = $obBD_con1->getRowConsulta(18, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(18, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <link href="../../framework/jquery/bootstrap/bootstrap-fileinput/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <title>Reporte de vendedores [exa]</title>
</head>
<body>
    <div class="row exa-body">
        <div class="panel panel-main" id="buscar_personal">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo; Consultar Información del Personal</h3>
            </div>
            <div class="col-md-12">
                <div id="busca" class="panel-body ui-widget-content ui-corner-bottom exa-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtro de Búsqueda</legend>
                                <form id="formBuscar" name="formBuscar" class="form-horizontal normal" action="javascript:$('#list').Search('#formBuscar','vendedorAjax');">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-sm-9 radioset">
                                                <input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;</label>
                                                <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" alt="" /><label for="rad_ba2">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Búsqueda:</label>
                                            <div class="col-sm-8">
                                                <div class="input-group">
                                                    <input name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" type="text" size="50" maxlength="50" value="" placeholder="Ingrese empleado a buscar..." autofocus class="form-control input-sm" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-sm-1 control-label">Desde:</label>
                                            <div class="col-sm-5">
                                                <input name="ini" type="text" id="ini" class="form-control small-input">
                                            </div>
                                            <label class="col-sm-1 control-label">Hasta:</label>
                                            <div class="col-sm-5">
                                                <input name="fin" type="text" id="fin" class="form-control small-input">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-offset-2 col-sm-10 text-right">
                                                <button type="button" onclick="this.form.submit()" class="btn btn-success" title="Buscar cuenta">
                                                    <span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </fieldset>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Resultados de la B&uacute;squeda</legend>
                                <table id="list"></table>
                                <div id="listPager"></div>
                            </fieldset>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="../VALIDACIONES/fac_par_vendedor.js"></script>
</body>
</html>