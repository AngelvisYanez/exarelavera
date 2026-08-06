<?php
/**
 * @abstract Permite consultar requisitores
 * @author Angeloni Cuesta
 * @version 1.0
 * Fecha de creaci�n  2021-06-07
 */
require_once('../../../administrador/LOGICA/seguridad.php');
require_once('../../LOGICA/requisitores/registrar.php');
require_once('../../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Requisitores($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_Requisitores;

//Secci�n para cargar datos en el Jqgrid referente al personal registrado
if (isset($personalAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(18, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(18, $data, $obBD_conexion);
        foreach($responce['rows'] as &$value){
            $descomponer=explode('-',$value['Fec_Sys']);
            $descompone1=explode('-',$value['Prs_Fec']);
            $Prs_Eda=$descomponer[0]-$descompone1[0];
            $value['Prs_Eda']=$Prs_Eda;
        }unset($value);
    }
    utf8_encode_deep($responce['rows']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($responce);
    //var_dump(json_decode($responce));
    exit();
}
//Secci�n ajax para guardar un nuevo personal
if (isset($savePersonal)) {
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

    //Se carga la ruta de donde se desea crear la carpeta que almacenar� las im�genes
    $carpeta = "../../../imagenes/" . $Ses_Emp_Cod . '/Personal';
    //En caso de que la carpeta no exista se crear� asignandole todos los permisos "0777"
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0777, true);
    }
    //Se extrae la extensi�n de la foto jpg,png,etc
    $archivo = $_FILES['avatar-1']['name'];
    $nombre = explode('.', $archivo);
    $last = count($nombre) - 1;
    //$ruta=contiene el nombre de la imagen;
    $ruta = 'img_personal_' . $Per_Cod . '_' . $Ses_Suc_Cod . '.' . $nombre[$last];
    //Copiamos la imagen cargada a la carpeta con la direccion establecida
    copy($_FILES['avatar-1']['tmp_name'], $carpeta . '\\' . $ruta);
    //Ejecutamos un update sobre la tabla personal para agregar la foto de perfil
    $obBD_con1->operacionobBD(8, $Per_Cod . '*' . $Prs_Nom . '*' . $Prs_Ape . '*' . $Prs_Sex . '*' . $Prs_Esc . '*' . $Prs_Fec . '*' . $Ciu_Cod . '*' . $Prs_Tel . '*' . $Prs_Te2 . '*' . $Prs_Cel . '*' . $Prs_Cor . '*' . $Prs_Dir. '*' . $Prs_San . '*' . $Per_Car . '*' . $Per_Tit . '*' . $Per_Obs . '*' . $ruta. '*' . $Per_Cfi, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $response['success'] = true;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit();
}

if(isset($anularRequisitor)) {
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(17, array("Per_Est"=>$Per_Est, "Per_Cod" => $Per_Cod) , $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $response['success'] = true;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit();
}

?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" type="text/css" media="screen" href="/framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <link href="/framework/jquery/bootstrap/bootstrap-fileinput/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
        <?Php require_once("../../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="/framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="/framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script src="/framework/jquery/bootstrap/bootstrap-fileinput/js/fileinput.js" type="text/javascript"></script>
        <script type="text/javascript" src="/Librerias/validaciones/validacion.js"></script>
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
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Informaci&oacute;n de Requisitores</h3></div>
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
                                <button type="button" onclick="$('#imprimir').append($('#list').jqGrid('exportGridElement',{nombre:'Listado de Empleados',hoja:'Empleados',caption:true,footer:true,removeHiddens:true,removeCols:[8]}));$.downloadFile($.exportarExcelBlob($('#imprimir').html(),'Listado Empleados'),'Lista de Empleados_'+$.getDate()+'.xls');" class="btn btn-primary btn-sm start" title="Descargar archivo de Excel"><i class="glyphicon glyphicon-download-alt"></i> <span>Excel</span></button>
                            </div>
                        </fieldset>
                    </div>
                </div> 
            </div>
        </div>
        <script type="text/javascript">
            //Secci�n para inicializar componentes
            var image = "../../../imagenes/perfil.png";
            $(function () {
                //Se declara el jqgris para presentar informaci�n de los empleados registrados
                $("#list").jqGrid({
                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                    mtype: "GET", datatype: "json", regional: 'es',responsive:true,
                    postData: $("#formBuscar").getData("personalAjax"),
                    autowidth: true, shrinkToFit: true, height: 295,
                    cmTemplate: {sortable: false},
                    colModel: [
                        {label: 'C&oacute;digo', name: 'Per_Cod', width: 30, align: "center"},
                        {label: 'C&eacute;dula', name: 'Prs_Ced', width: 60, align: "center",cellattr:function(){return 'style="'+excelFormats.text+'"';}},
                        {label: 'Empleado', name: 'empleado', width: 140, align: "center"},
                        {label: 'Edad', name: 'Prs_Eda', width: 40, align: "center"},
                        {label: 'G&eacute;nero', name: 'Prs_Gen', width: 50, align: "center"},
                        {label: 'T&iacute;tulo', name: 'Per_Ti1', width: 50, align: "center"},
                        {label: 'Ciudad', name: 'Ciu_Des', width: 70, align: "center"},
                        {label: 'Estado', name: 'Per_Est', width: 40, align: "center"},
                        {label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                            formatter: function (cellvalue, options, rowObject) {
                                return (rowObject.Per_Est === 'Activo') ? $.getGridButton(anularRequisitor, rowObject,'Anular Requisitor','glyphicon glyphicon-remove','','danger') : $.getGridButton(anularRequisitor, rowObject,'Activar Requisitor','glyphicon glyphicon-check','','success')
                                
                            }
                        }
                    ],
                    rowNum: 10000, pager: "#listPager",gridview: false, rownumbers: false, viewrecords: true, pgbuttons: false, pgtext: null, altRows: true, altclass: "myAltRowClass"
                });
                function foto_grid(cellvalue, options, rowObject) {
                    if (rowObject.Per_Fot === 'no') {
                        var fotoperfil = '<img height="80" width="80" src="../../../imagenes/perfil.png"/>';
                    } else {
                        fotoperfil = '<img height="80" width="80" src="../../../imagenes/<?php echo $Ses_Emp_Cod; ?>/personal/' + rowObject.Per_Fot+ '?x=' + Math.random() + '"/>';
                    }
                    return fotoperfil;
                }
                uploadImage(image);
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
                    $.createDialogConfirm('Desea MODIFICAR la informaci�n del Empleado..!!', null, function () {
                        $('#formPersonal').formSubmit();
                    });
                });
            });
            function cargarEmpleado(empleado) {
                limpiar();
                $('#formPersonal').setData(empleado, false);
                $('#Ciu_Cod').val(empleado['Ciu_Cod']).trigger('chosen:updated');
                if (empleado['Per_Fot'] === 'no') {
                    var ima = "../../../imagenes/perfil.png";
                } else {
                    ima = '../../../imagenes/<?php echo $Ses_Emp_Cod; ?>/personal/' + empleado['Per_Fot'] + '?x=' + Math.random();
                }
                $('#avatar-1').parent().parent().find('.fileinput-remove-button').trigger('click');
                uploadImage(ima);
                $('#busca').moveComp('#editar');
            }
            //Funci�n para editar la informaci�n del personal
            function saveForm() {
                $.createDialogConfirm('Desea MODIFICAR la informaci�n del Empleado..!!', null, function () {
                    var formData = new FormData(document.getElementById("formPersonal"));
                    formData.append("savePersonal", true);
                    $.ajax({
                        url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                        type: "post",
                        dataType: "json",
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false
                    })
                    .done(function (response) {
                        if (response.success === true) {
                            $.alert("Transaccion Realizada con &Eacute;xito!");
                            $('#avatar-1').parent().parent().find('.fileinput-remove-button').trigger('click');
                            uploadImage(image);
                            limpiar();
                            $('#list').Search('#formBuscar','personalAjax');
                            $('#list').getDialogGrid().trigger('reloadGrid',[{page:1}]);
                        } else {
                            $.alert(response.message);
                        }
                    });
                });
            }
            function limpiar(){
                $("[id^='Prs_']").val('');
                $("[id^='Per_']").val('');
                $('#Ciu_Cod').val('').trigger('chosen:updated');
                $('#Prs_Esc').prop('selectedIndex', 0);
                $('#Prs_Sex').prop('selectedIndex', 0);
                $('#Per_Tit').prop('selectedIndex', 0);
                $('#Ide_Des').val('');
            }
            //Funci�n para cargar la imagen en el fileinput
            function uploadImage(imagen) {
                $('#im').attr('src', imagen);
                $("#avatar-1").fileinput({
                    overwriteInitial: true,
                    maxFileSize: 2000,
                    msgSizeTooLarge: 'Archivo: "{name}" (<b>{size} KB</b>) excede el tama�o m�ximo permitido de <b>{maxSize} KB</b>.',
                    showClose: false,
                    showCaption: false,
                    browseLabel: 'Seleccionar imagen',
                    removeLabel: '',
                    browseClass: "btn btn-success btn-xs",
                    browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>',
                    removeClass: "btn btn-danger btn-xs",
                    removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
                    removeTitle: 'Eliminar imagen',
                    elErrorContainer: '#kv-avatar-errors-1',
                    msgErrorClass: 'alert alert-block alert-danger',
                    defaultPreviewContent: '<img id="im" src="' + imagen + '" alt="Your Avatar" style="width:150px">',
                    layoutTemplates: {main2: '{preview} {remove} {browse}'},
                    allowedFileExtensions: ["jpg", "png", "gif"],
                    previewSettings: {image: {width: "150px", height: "150px"}}
                });
            }
            /*Funci�n para imprimir la informaci�n de un activo seleccionado*/
            function anularRequisitor(empleado){
                console.log("EMPLEADO",empleado)
                empleado.anularRequisitor = true
                if(empleado.Per_Est === "Activo"){
                    empleado.Per_Est = "I"
                }else {
                    empleado.Per_Est = "A"
                }
                $.ajax({
                        url: '',
                        type: "post",
                        dataType: "json",
                        data: empleado
                })
                .done(function (response) {
                    if (response.success === true) {
                        $.alert("Transaccion Realizada con &Eacute;xito!");
                        $('#avatar-1').parent().parent().find('.fileinput-remove-button').trigger('click');
                        uploadImage(image);
                        limpiar();
                        $('#list').Search('#formBuscar','personalAjax');
                        $('#list').getDialogGrid().trigger('reloadGrid',[{page:1}]);
                    } else {
                        $.alert(response.message);
                    }
                });
            }
        </script>
        <script type="text/ecmascript" src="/Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    </BODY>
</HTML>



