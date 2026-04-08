<?php
/**
 * @abstract Permite realizar el registro de personal
 * @author Jos� Ambulud�
 * @version 1.0
 * Fecha de creaci�n  2016-11-01
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_personal_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');




$obBD_conexion = new Class_Log_Conexion_rrhh($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_rrhh;

//Secci�n para cargar datos en el Jqgrid referente al personal registrado
if (isset($personalAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $data["Are_Cod"] = $Are_Cod;
    $data["Ded_cod"] = $Ded_Cod;
    $data["Afi_Cod"] = $Afi_Cod;

    $contar = $obBD_con1->getRowConsulta(57, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(57, $data, $obBD_conexion);
        foreach($responce['rows'] as &$value){
            $descomponer=explode('-',$value['Fec_Sys']);
            $descompone1=explode('-',$value['Prs_Fec']);
            $Prs_Eda=$descomponer[0]-$descompone1[0];
            $value['Prs_Eda']=$Prs_Eda;
        }unset($value);
    }
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    //var_dump(json_decode($responce));
    exit();
}

$areas = $obBD_con1->getArrayConsulta(50, $Ses_Emp_Cod, $obBD_conexion);
$dedicacion = $obBD_con1->getArrayConsulta(51, $Ses_Suc_Cod, $obBD_conexion);
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Contrato Consultar [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <link href="../../framework/jquery/bootstrap/bootstrap-fileinput/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script src="../../framework/jquery/bootstrap/bootstrap-fileinput/js/fileinput.js" type="text/javascript"></script>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <style>
            .kv-avatar .file-preview-frame,.kv-avatar .file-preview-frame:hover {
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
                text-align: center;
            }
            .kv-avatar .file-input {
                display: table-cell;
                max-width: 220px;
            }
            .file-upload-indicator{display: none;}
            .file-footer-caption{margin: 0px;}
            .file-actions{display: none;}
            .swlFlyout_title{background-color: #439943;color: white;}
            .panel { margin-bottom: 1px;}
            .center-block{ margin-bottom: 20px; }
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;Informaci&oacute;n del Personal con Contrato</h3></div>
            <div id="busca" class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Filtro de B&uacute;squeda</legend>
                            <form id="formBuscar" name="formBuscar" class="form-horizontal normal" action="javascript:$('#list').Search('#formBuscar','personalAjax');"> 
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs">Filtrar Por:</label>  
                                    <div class="col-sm-9 radioset" >
                                        <input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;</label>
                                        <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" alt="" /><label for="rad_ba2">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <!-- area del contrato -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs">Area:</label>  
                                    <div class="col-sm-9" >
                                        <select id="Are_Cod" name="Are_Cod" class="form-control input-xs">
                                                <option value="T">Todas</option>
                                                <?php 
                                                    foreach ($areas AS $are){
                                                        echo "<option value='$are[Are_Cod]'>$are[Are_Des]</option>";
                                                    }
                                                ?>                            
                                        </select>
                                    </div>
                                </div>
                                <!-- dedicacion laboral del contrato -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs">Dedicación:</label>  
                                    <div class="col-sm-9" >
                                        <select id="Ded_Cod" name="Ded_Cod" class="form-control input-xs">
                                                <option value="T">Todas</option>
                                                 <?php 
                                                    foreach ($dedicacion AS $ded){
                                                        echo "<option value='$ded[Ded_Cod]'>$ded[Ded_Des]</option>";
                                                    }
                                                ?>                             
                                        </select>
                                    </div>
                                </div>

                                <!-- afiliacion del contrato -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs">Afiliación:</label>  
                                    <div class="col-sm-9" >
                                         <select id="Afi_Cod" name="Afi_Cod" class="form-control input-xs">
                                                <option value="T">Todos</option>
                                                <option value="A">Afiliado</option>
                                                <option value="N">No Afiliado</option>                             
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">B&uacute;squeda:</label>
                                    <div class="col-sm-8" >                 
                                        <div class="input-group">                        
                                            <input name="search" onkeydown="if (event.keyCode === 13)
                                                        this.form.submit()" type="text" size="50" maxlength="50" value="" placeholder="Ingrese empleado a buscar..." autofocus class="form-control input-sm" />
                                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
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
                            <div id="imprimir" style="display: none;">
                                <?php echo $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, 'REPORTE DE EMPLEADOS', '', 8, $obBD_conexion); ?>
                            </div>
                            <div style="padding-top: 10px; padding-bottom: 0px;">
                                <button type="button" onclick="$('#imprimir').append($('#list').jqGrid('exportGridElement',{nombre:'Listado de Empleados',hoja:'Empleados',caption:true,footer:true,removeHiddens:true/*,removeCols:[8]*/}));$.downloadFile($.exportarExcelBlob($('#imprimir').html(),'Listado Empleados'),'Lista de Empleados_'+$.getDate()+'.xls');" class="btn btn-primary btn-sm start" title="Descargar archivo de Excel"><i class="glyphicon glyphicon-download-alt"></i> <span>Excel</span></button>
                            </div>
                        </fieldset>
                    </div>
                </div> 
            </div>
        </div>
        <script type="text/javascript">
            //Secci�n para inicializar componentes
            var image = "../../imagenes/perfil.png";
            $(function () {
                //Se declara el jqgris para presentar informaci�n de los empleados registrados
                $("#list").jqGrid({
                    url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                    mtype: "GET", datatype: "json", regional: 'es',responsive:true,
                    postData: $("#formBuscar").getData("personalAjax"),
                    autowidth: true, shrinkToFit: true, height: 295,
                    cmTemplate: {sortable: false},
                    colModel: [
                        {label: 'CodPer', name: 'Per_Cod', width: 20, align: "center", hidden: true},
                        {label: 'C&oacute;digo', name: 'Con_Cod', width: 20, align: "center"},
                        {label: 'C&eacute;dula', name: 'Prs_Ced', width: 40, align: "center",cellattr:function(){return 'style="'+excelFormats.text+'"';}},
                        {label: 'Empleado', name: 'empleado', width: 100, align: "left"},
                        {label: 'Edad', name: 'Prs_Eda', width: 20, align: "center"},
                        {label: 'G&eacute;nero', name: 'Prs_Gen', width: 40, align: "center"},
                        {label: 'T&iacute;tulo', name: 'Per_Ti1', width: 50, align: "center"},
                        {label: 'Ciudad', name: 'Ciu_Des', width: 50, align: "center"},

                        {label: 'Fec.Ini. contrato', name: 'Con_Ini', width: 50, align: "center"},
                        {label: 'Fec.Fin. contrato', name: 'Con_Fin', width: 50, align: "center"},

                        {label: 'Dedicacion', name: 'Ded_Des', width: 50, align: "center"},
                        {label: 'Cargo', name: 'Tic_Des', width: 60, align: "center"},
                        {label: 'Sueldo', name: 'Sue_Val', width: 30, align: "center"},
                        {label: 'Area', name: 'Are_Des', width: 50, align: "center"},
                        {label: 'Forma pago', name: 'Forma_Pago', width: 30, align: "center"},
                        {label: 'Cuenta', name: 'Pag_Con_Cue', width: 30, align: "center"},
                        {label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                            formatter: function (cellvalue, options, rowObject) {
                                return $.getGridButton(imprimir, rowObject,'Imprimir Registro','glyphicon glyphicon-print');
                            }
                        }
                    ],
                    rowNum: 10000, pager: "#listPager",gridview: false, rownumbers: false, viewrecords: true, pgbuttons: false, pgtext: null, altRows: true, altclass: "myAltRowClass"
                });
                function foto_grid(cellvalue, options, rowObject) {
                    if (rowObject.Per_Fot === 'no') {
                        var fotoperfil = '<img height="80" width="80" src="../../imagenes/perfil.png"/>';
                    } else {
                        fotoperfil = '<img height="80" width="80" src="../../imagenes/<?php echo $Ses_Emp_Cod; ?>/personal/' + rowObject.Per_Fot+ '?x=' + Math.random() + '"/>';
                    }
                    return fotoperfil;
                }
                //uploadImage(image);
                //Se declara datepicker
                $.createDatePickers("#Prs_Fec");
                //Se declara chosen
                $("#Ciu_Cod").createChosen('input-xs', {
                    template: function (text, templateData) {
                        return [
                            "<div>" + text + "</div>",
                            "<div style='font-size:11px;'><b>Provincia:</b> " + templateData.provincia + " <b>Pais:</b> " + templateData.pais + "</div>"
                        ].join("");
                    }
                });
                //Se declara tabs 
                $("#tabs").tabs();
                //Captura el evento y valida el formulario
                $('#btn_gua').click(function () {
                    $.createDialogConfirm('Desea MODIFICAR la información del Empleado..!!', null, function () {
                        $('#formPersonal').formSubmit();
                    });
                });
            });

            /*Funci�n para imprimir la informaci�n de un activo seleccionado*/
            function imprimir(empleado){
                window.open('./rhu_pri_personal_1.0.php?Per_Cod='+empleado.Per_Cod,'_blank');
            }
        </script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    </BODY>
</HTML>



