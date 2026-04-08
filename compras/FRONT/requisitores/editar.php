<?php
/**
 * @abstract Permite modificar requisitores
 * @author Jos� Ambulud�
 * @version 1.0
 * Fecha de creaci�n  2021-06-07
 */
require_once('../../../administrador/LOGICA/seguridad.php');
require_once('../../LOGICA/requisitores/registrar.php');
require_once('../../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../../Librerias/postclass.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Requisitores($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_Requisitores;

//Secci�n para cargar datos en el Jqgrid referente al requisitor registrado
if (isset($personalAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(7, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(7, $data, $obBD_conexion);
    }
	utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}
//Secci�n ajax para guardar un nuevo requisitor
if (isset($savePersonal)) {
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

    //Se carga la ruta de donde se desea crear la carpeta que almacenar� las im�genes
    $carpeta = "../../../imagenes/" . $Ses_Emp_Cod . '/personal';
    //En caso de que la carpeta no exista se crear� asignandole todos los permisos "0777"
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0777, true);
    }
    //Se extrae la extensi�n de la foto jpg,png,etc
    $archivo = $_FILES['avatar-1']['name'];
    $nombre = explode('.', $archivo);
    $last = count($nombre) - 1;
    //$ruta=contiene el nombre de la imagen;
    if (!empty($nombre[$last])){
		$ruta = 'img_personal_' . $Per_Cod . '_' . $Ses_Suc_Cod . '.' . $nombre[$last];
		//Copiamos la imagen cargada a la carpeta con la direccion establecida
		copy($_FILES['avatar-1']['tmp_name'], $carpeta . '/' . $ruta);
	}else{
		$ruta='';
	}
    
    //Ejecutamos un update sobre la tabla personal para agregar la foto de perfil
    $obBD_con1->operacionobBD(8, $Per_Cod . '*' . $Prs_Nom . '*' . $Prs_Ape . '*' . $Prs_Sex . '*' . $Prs_Esc . '*' . $Prs_Fec . '*' . $Ciu_Cod . '*' . $Prs_Tel . '*' . $Prs_Te2 . '*' . $Prs_Cel . '*' . $Prs_Cor . '*' . $Prs_Dir. '*' . $Prs_San . '*' . $Per_Car . '*' . $Per_Tit . '*' . $Per_Obs . '*' . $ruta. '*' . $Per_Cfi. '*' . $requisitor, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $response['success'] = true;
    }
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
        <script language="javascript" src="/Librerias/validaciones/validacion.js"></script>
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
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Informaci&oacute;n del Requisitor</h3></div>
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
                        </fieldset>
                    </div>
                </div> 
            </div>
            <div id="editar" class="panel-body ui-widget-content ui-corner-bottom exa-body" style="display: none;">
                <div class="row">
                    <div class="col-sm-12">
                        <div id="tabs" class="ui-tab-fix">
                            <ul style="font-size: 12px;">
                                <li><a href="#inf_per">Informaci&oacute;n Requisitor</a></li>
                            </ul>
                            <div id="inf_per">
                                <form id="formPersonal" name="formPersonal" class="form-horizontal normal" enctype="multipart/form-data" action="javascript:saveForm();">
                                    <input type="hidden" id="Per_Cod" name="Per_Cod" value="0">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <fieldset class="exa-fieldset">                           
                                                <legend class="Titulos2">Datos Personales</legend>
                                                <div class="form-group Titulos2">
                                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                                </div>
                                                <div class="col-md-6 col-sm-7">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm" for="Prs_Ced">C&eacute;dula/R.U.C.:</label>  
                                                        <div class="col-sm-4">
                                                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" readonly=""/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm">Tipo de Documento:</label>
                                                        <div class="col-sm-7">
                                                            <input id="Ide_Des" name="Ide_Des" class="form-control input-xs" placeholder="" type="text" readonly/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm required" for="Prs_Nom">Nombres:</label>  
                                                        <div class="col-sm-7">
                                                            <input id="Prs_Nom" name="Prs_Nom" class="form-control input-xs" placeholder="" type="text" required=""/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm required" for="Prs_Ape">Apellidos:</label>  
                                                        <div class="col-sm-7">
                                                            <input id="Prs_Ape" name="Prs_Ape" class="form-control input-xs" placeholder="" type="text" required=""/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm" for="Prs_Fec">Fecha Nacimiento:</label>  
                                                        <div class="col-sm-4">
                                                            <input id="Prs_Fec" name="Prs_Fec" class="form-control input-xs" placeholder="Elegir fecha" type="text" readonly=""/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm required" for="Ciu_Cod">Ciudad:</label>  
                                                        <div class="col-sm-7">
                                                            <?php $row_rs_ciudad = $obBD_con1->getArrayConsulta(1, "", $obBD_conexion); ?>
                                                            <select name="Ciu_Cod" id="Ciu_Cod" data-placeholder="Seleccione una ciudad" class="chzn-select-template-example">
                                                                <option value="" data-provincia="" data-pais=""></option>
                                                                <?Php
                                                                foreach ($row_rs_ciudad as $row) {
                                                                    ?>
                                                                    <option value="<?Php echo $row['Ciu_Cod']; ?>" data-provincia="<?Php echo $row['Pro_Nom']; ?>" data-pais="<?Php echo $row['Pas_Nom']; ?>"><?Php echo $row['Ciu_Des']; ?></option>
                                                                <?Php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm" for="Prs_Tel">Tel&eacute;fono 1:</label>  
                                                        <div class="col-sm-4">
                                                            <input id="Prs_Tel" name="Prs_Tel" class="form-control input-xs" placeholder="" type="text" onkeypress="return validar_numeric(event);"/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm" for="Prs_Te2">Tel&eacute;fono 2:</label>  
                                                        <div class="col-sm-4">
                                                            <input id="Prs_Te2" name="Prs_Te2" class="form-control input-xs" placeholder="" type="text" onkeypress="return validar_numeric(event);"/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm" for="Prs_Cel">Tel&eacute;fono 3:</label>  
                                                        <div class="col-sm-4">
                                                            <input id="Prs_Cel" name="Prs_Cel" class="form-control input-xs" placeholder="" type="text" onkeypress="return validar_numeric(event);"/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm" for="Prs_Cor">Email:</label>  
                                                        <div class="col-sm-7">
                                                            <input id="Prs_Cor" name="Prs_Cor" class="form-control input-xs" placeholder="" type="text"/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm required" for="Prs_Dir">Direcci&oacute;n:</label>  
                                                        <div class="col-sm-7">
                                                            <textarea id="Prs_Dir" name="Prs_Dir" class="form-control input-xs" style="resize: none;" required=""></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm" for="Per_Obs">Observaci&oacute;n:</label>  
                                                        <div class="col-sm-7">
                                                            <textarea id="Per_Obs" name="Per_Obs" class="form-control input-xs" style="resize: none;"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 col-sm-5">
                                                    <div class="row">
                                                        <div class="col-md-6 col-sm-10 text-center">
                                                            <div id="kv-avatar-errors-1" class="center-block" style="width:350px;display:none"></div>
                                                            <div class="kv-avatar center-block" style="width:200px">
                                                                <input id="avatar-1" name="avatar-1" type="file" class="file-loading">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12 col-sm-12">
                                                            <div class="form-group">
                                                                <label class="col-sm-3 control-label label-sm required" for="Prs_Sex">Genero:</label>  
                                                                <div class="col-sm-4">
                                                                    <select id="Prs_Sex" name="Prs_Sex" class="form-control input-xs" required="">
                                                                        <option value="M">MASCULINO</option>
                                                                        <option value="F">FEMENINO</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-sm-3 control-label label-sm" for="Prs_Esc">Estado Civil:</label>  
                                                                <div class="col-sm-4">
                                                                    <select id="Prs_Esc" name="Prs_Esc" class="form-control input-xs">
                                                                        <option value="S">SOLTERO/A</option>
                                                                        <option value="C">CASADO/A</option>
                                                                        <option value="D">DIVORCIADO/A</option>
                                                                        <option value="V">VIUDO/A</option>
                                                                        <option value="U">UNI&Oacute;N LIBRE</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-sm-3 control-label label-sm" for="Per_Car">Carga Familiar:</label>  
                                                                <div class="col-sm-4">
                                                                    <input id="Per_Car" name="Per_Car" class="form-control input-xs" placeholder="" type="text" onkeypress="return validar_numeric(event);"/>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-sm-3 control-label label-sm required" for="Per_Tit">T&iacute;tulo:</label>  
                                                                <div class="col-sm-4">
                                                                    <select id="Per_Tit" name="Per_Tit" class="form-control input-xs">
                                                                        <option value="Np">NO POSEE</option>
                                                                        <option value="Abg">ABOGADO/A</option>
                                                                        <option value="Bac">BACHILLER</option>
                                                                        <option value="Dr">DOCTOR/A</option>
                                                                        <option value="Eco">ECONOMISTA</option>
                                                                        <option value="Ing">INGENIERO/A</option>
                                                                        <option value="Lcd">LICENCIADO/A</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </fieldset>
                                            <fieldset class="exa-fieldset">                           
                                                <legend class="Titulos2">Datos M&eacute;dicos</legend>
                                                <div class="col-md-6 col-sm-7">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm" for="Per_Cfi">Condici&oacute;n F&iacute;sica:</label>  
                                                        <div class="col-sm-7">
                                                            <textarea id="Per_Cfi" name="Per_Cfi" class="form-control input-xs" style="resize: none;"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 col-sm-5">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label label-sm" for="Prs_San">Tipo Sangre:</label>  
                                                        <div class="col-sm-4">
                                                            <input id="Prs_San" name="Prs_San" class="form-control input-xs" type="text"/>
                                                        </div>
                                                    </div>
                                                </div>
                                            </fieldset>
                                            <button type="button" class="btn btn-success btn-xs" onclick="$('#editar').moveComp('#busca');"><span class="glyphicon glyphicon-arrow-left"></span> Atr&aacute;s</button>
                                            <button type="submit" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div> 
            </div>
        </div>
        <script type="text/javascript">
            //Secci�n para inicializar componentes
            var image = "../../../mascaras/model1/imagenes/128x128/perfil.png";
            $(function () {
                //Se declara el jqgris para presentar informaci�n de los empleados registrados
                $("#list").jqGrid({
                    url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                    mtype: "GET", datatype: "json", regional: 'es',responsive:true,
                    postData: $("#formBuscar").getData("personalAjax"),
                    autowidth: true, shrinkToFit: true, height: 295,
                    cmTemplate: {sortable: false},
                    colModel: [
                        {label: 'Foto', name: 'Per_Fot', width: 80, fixed: true, formatter: foto_grid},
                        {label: 'C&eacute;dula', name: 'Prs_Ced', width: 50},
                        {label: 'Empleado', name: 'empleado', width: 150, align: "center"},
                        {label: 'T&iacute;tulo', name: 'Per_Ti1', width: 50, align: "center"},
                        {label: 'Ciudad', name: 'Ciu_Des', width: 50, align: "center"},
                        {label: 'Requisitor', name: 'Per_Req', width: 30, align: "center"},
                        {label: 'Estado', name: 'Per_Est', width: 50, align: "center"},
                        {label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                            formatter: function (cellvalue, options, rowObject) {
                                return $.getGridButton(cargarEmpleado, rowObject);
                            }
                        }
                    ],
                    rowNum: 20, pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass"
                });
                function foto_grid(cellvalue, options, rowObject) {
					//console.log(rowObject);
                    if (rowObject.Per_Fot === 'no') {
                        var fotoperfil = '<img height="80" width="80" src="../../../mascaras/model1/imagenes/128x128/perfil.png"/>';
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
                    var ima = "../../../mascaras/model1/imagenes/128x128/perfil.png";
                } else {
                    ima = '../../../imagenes/<?php echo $Ses_Emp_Cod; ?>/personal/' + empleado['Per_Fot'] + '?x=' + Math.random();
                }
                $('#avatar-1').parent().parent().find('.fileinput-remove-button').trigger('click');
                uploadImage(ima);
                $('#busca').moveComp('#editar');
            }
            //Funci�n para editar la informaci�n del requisitor
            function saveForm() {
                $.createDialogConfirm('Desea MODIFICAR la informaci�n del Empleado..!!', null, function () {
                    var formData = new FormData(document.getElementById("formPersonal"));
                    formData.append("savePersonal", true);
                    $.ajax({
                        url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
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
        </script>
    </BODY>
</HTML>



