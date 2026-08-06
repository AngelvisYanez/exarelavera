<?php
/**
 * @abstract Permite realizar el registro de personal
 * @author Jos� Ambulud�
 * @version 1.0
 * Fecha de creaci�n  2016-10-25
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_personal.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_rrhh($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_rrhh;

//Comprueba de que la persona exista o no
if (isset($existePersona)) {
    //Se obtiene el Ide_Cod basandonos en la longitud de la cadena ingresada (cedula,ruc,pasaporte,etc)
    $longitud = strlen($Prs_Ced);
    $identificacion = $obBD_con1->getRowConsulta(2, $longitud, $obBD_conexion);
    $persona = $obBD_con1->getRowConsulta(3, $Prs_Ced, $obBD_conexion);
    $personal = $obBD_con1->getRowConsulta(9, $Prs_Ced, $obBD_conexion);
    if (isset($persona["Prs_Cod"])) {
        $response =  $persona;
        $response["existe"] = true;
    } else {
        $response["existe"] = false;
        $response["Ide_Cod"] = $identificacion["Ide_Cod"];
        $response["Ide_Des"] = $identificacion["Ide_Des"];
        $response["Prs_Ced"] = $Prs_Ced;
    }
    //Se verifica si la persona ya se encuentra registrada en la tabla personal
    if (isset($personal["Prs_Cod"])) {
        $response["personal"] = true;
    } else {
        $response["personal"] = false;
    }
    echo $obBD_con1->echoJson($response);
}

//Seccion ajax para guardar un nuevo personal
if (isset($savePersonal)) {
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

    //Se carga la ruta de donde se desea crear la carpeta que almacenar� las im�genes
    $carpeta = "../../imagenes/" . $Ses_Emp_Cod . '/personal';
    //En caso de que la carpeta no exista se crear� asignandole todos los permisos "0777"
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0777, true);
    }
    if ($Prs_Cod == 0) {
        $obBD_con1->operacionobBD(4, $Ciu_Cod . '*' . $Ide_Cod . '*' . $Prs_Ced . '*' . $Prs_Nom . '*' . $Prs_Ape . '*' . $Prs_Sex . '*' . $Prs_Esc . '*' . $Prs_Fec . '*' . $Prs_Tel . '*' . $Prs_Te2 . '*' . $Prs_Cel . '*' . $Prs_Cor . '*' . $Prs_Dir.'*'.$Prs_San, $obBD_conexion);
        //Secci�n para obtener el c�digo de la �ltima inserci�n en la tabla persona
        $Prs_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
    }else{
        //Ejecutamos un update sobre la tabla personal para agregar la foto de perfil
        $obBD_con1->operacionobBD(16, $Prs_Cod . '*' . $Prs_Nom . '*' . $Prs_Ape . '*' . $Prs_Sex . '*' . $Prs_Esc . '*' . $Prs_Fec . '*' . $Ciu_Cod . '*' . $Prs_Tel . '*' . $Prs_Te2 . '*' . $Prs_Cel . '*' . $Prs_Cor . '*' . $Prs_Dir. '*' . $Prs_San, $obBD_conexion);
        //$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    }
    //Insert en la tabla personal
    
    $obBD_con1->operacionobBD(5, $Prs_Cod . '*' . $Ses_Emp_Cod . '*' . $Per_Car . '*' . $Per_Tit . '*' . $Per_Obs.'*'.$Per_Cfi.'*'.$requisitor, $obBD_conexion);
    //Secci�n para obtener el c�digo de la �ltima inserci�n en la tabla personal
    $Per_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);

    //Se extrae la extensi�n de la foto jpg,png,etc
    $archivo = $_FILES['avatar-1']['name'];
    $nombre = explode('.', $archivo);
    $last = count($nombre) - 1;
    //$ruta=contiene el nombre de la imagen;
    $ruta = 'img_personal_' . $Per_Cod . '_' . $Ses_Suc_Cod . '.' . $nombre[$last];
    //Copiamos la imagen cargada a la carpeta con la direccion establecida
    if($_FILES['avatar-1']['size']>0)
        copy($_FILES['avatar-1']['tmp_name'], $carpeta . '/' . $ruta);
    //Ejecutamos un update sobre la tabla personal para agregar la foto de perfil
    if ($nombre[$last] != "") {
        $obBD_con1->operacionobBD(6, $Per_Cod . '*' . $ruta, $obBD_conexion);
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $response['success'] = true;
    }else{
        $response['error'] = $obBD_con1->MsgError;
    }
    $obBD_con1->echoJson($response);
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Personal Registrar [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <link href="../../framework/jquery/bootstrap/bootstrap-fileinput/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script src="../../framework/jquery/bootstrap/bootstrap-fileinput/js/fileinput.js" type="text/javascript"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script type="text/javascript" src="../../framework/plugins/cedulaRuc.js"></script>
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
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Personal</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div id="tabs" class="ui-tab-fix">
                            <ul style="font-size: 12px;">
                                <li><a href="#inf_per">Informaci&oacute;n Personal</a></li>
                            </ul>
                            <div id="inf_per">
                                <form id="formPersonal" name="formPersonal" class="form-horizontal normal" enctype="multipart/form-data" action="javascript:saveForm();">
                                    <!--<input type="hidden" id="Ide_Cod" name="Ide_Cod">-->
                                    <input type="hidden" id="Prs_Cod" name="Prs_Cod" value="0">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <fieldset class="exa-fieldset">
                                                <legend class="Titulos2">Datos Personales</legend>
                                                <div class="form-group Titulos2">
                                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                                </div>
                                                <div class="col-md-6 col-sm-7">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm required" for="Prs_Ced">C&eacute;dula/R.U.C.:</label>
                                                        <div class="col-sm-7">
                                                            <div class="input-group input-group-xs">
                                                                <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-sm" required="" placeholder="Ingresar Informaci&oacute;n" onkeypress="return validar_numeric(event);" onchange="comprobarForm();" />
                                                                <span class="input-group-btn">
                                                                    <button class="btn btn-success" type="button" onclick="comprobarForm()"><span class="glyphicon glyphicon-refresh" title="Buscar persona"></span> Comprobar</button>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!--<div class="form-group">
                                                        <label class="col-sm-4 control-label label-sm">Tipo de Documento:</label>
                                                        <div class="col-sm-7">
                                                            <input id="Ide_Des" name="Ide_Des" class="form-control input-xs" placeholder="" type="text" readonly/>
                                                        </div>
                                                    </div>-->
                                                    <div class="form-group tipo_doc" style="display: none;">
                                                        <label class="col-xs-4 control-label label-xs">Ciudadano:</label>
                                                        <div class="col-xs-5" >
                                                            <div class="btn-group" data-toggle="buttons">
                                                                <label id="lb_ec" class="btn btn-success btn-xs">
                                                                    <input id="radioec" name="tipo" value="Ec" type="radio" checked=""><i id="spanec" class="fa fa-check"></i> Ecuatoriano
                                                                </label>
                                                                <label id="lb_ex" class="btn btn-default btn-xs">
                                                                    <input id="radioex" name="tipo" value="Ex" type="radio"><i id="spanex" class="fa fa-check" style="display: none;"></i> Extranjero
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-xs-4 control-label label-xs">Documento:</label>
                                                        <div class="col-xs-8" >
                                                            <?php $rs_identi = $obBD_con1->getArrayConsulta('identifica.selectWhere',array('addCols'=>array(''=>array('Tipo'=>"IF(ISNULL(Ide_Pre),'Ec','Ex')")),'where'=>array("Ide_Est"=>'A')), $obBD_conexion); ?>
                                                            <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" >
                                                                <option value="">Seleccionar</option>
                                                                <?php foreach($rs_identi as $row){ echo "<option value='$row[Ide_Cod]' data-tipo='$row[Tipo]'>$row[Ide_Des]</option>"; } ?>
                                                            </select>
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
                                                            <select name="Ciu_Cod" id="Ciu_Cod" data-placeholder="Seleccione una ciudad" class="chzn-select-template-example" required="">
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
                                                                    <input id="Per_Car" name="Per_Car" class="form-control input-xs" placeholder="" type="text" onkeypress="return validar_numeric(event);" value="0"/>
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
                                                                <label>
                                                                    <input type="checkbox" id="requisitor" name="requisitor"  value="1" offval="0" onchange="" /> Requisitor
                                                                </label>
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
            var image = "../../mascaras/model1/imagenes/128x128/perfil.png";
            $(function () {
                uploadImage(image);
                //Se declara datepicker
                $.createDatePickers("#Prs_Fec");
                //Se declara chosen
                $("#Ciu_Cod").createChosen('input-xs', {
                    template: function (text, templateData) {
                        if (typeof templateData === 'undefined')
                            console.log(text, templateData);
                        return [
                            "<div>" + text + "</div>",
                            "<div style='font-size:11px;'><b>Provincia:</b> " + templateData.provincia + " <b>Pais:</b> " + templateData.pais + "</div>"
                        ].join("");
                    }
                });
                //Se declara el tab
                $("#tabs").tabs();
            });
            //Funci�n para comprobar de que un empleado existe o no
            function comprobarForm() {
                var cedula = $('#Prs_Ced').val();
                var campo = $('#Prs_Ced').attr('id');
                //var respuesta = validar_cedula(cedula, campo);
                //if (respuesta === true){
                    var data = {Prs_Ced: cedula, existePersona: true};
                    $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", data, function (response) {
                        if (response['personal'] === true) {
                            limpiar();
                            $.alert('La persona ya se encuentra registrada como empleado..!!');
                        }
                        else {
                            if (response['existe'] === true) {
                                $('.tipo_doc').hide();
                                $('#formPersonal').setData(response, false);
                                $('#Ciu_Cod').val(response['Ciu_Cod']).trigger("chosen:updated");
                            } else {
                                $.alert('Persona no se encuentra registrada');
                                $('.tipo_doc').show();
                                limpiar();
                                /*$('#Ide_Cod').val(response['Ide_Cod']);
                                $('#Ide_Des').val(response['Ide_Des']);*/
                                $('#Prs_Ced').val(response['Prs_Ced']);
                                $("#radioec").prop('checked',true).trigger('change');
                                $('#Prs_Nom').focus();
                            }
                        }
                    }, 'json').fail(function () {alert();});
                //}
            }
            //Funci�n para registrar personal
            function saveForm(){
                var Ide_Cod=$('#Ide_Cod').val().toNum();
                if(!Ide_Cod) return $.alert("El Numero de Identificacion no se ha comprobado o es desconocido!");
                if(Ide_Cod===1||Ide_Cod===2){
                    var vCed=validaNoIdentif($('#Prs_Ced').val());
                    console.log(vCed);
                    if(!vCed['success']) return $.alert(vCed['message']);
                }
                var formData = $('#formPersonal').getFormData('savePersonal');//new FormData(document.getElementById("formPersonal"));
                //formData.append("savePersonal", true);
                $.ajax({
                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                    type: "post",dataType: "json",data: formData,cache: false,contentType: false,processData: false
                })
                .done(function (response) {
                    if (response.success === true) {
                        $.alert("Transaccion Realizada con &Eacute;xito!");
                        $('#avatar-1').parent().parent().find('.fileinput-remove-button').trigger('click');
                        uploadImage(image);
                        limpiar();
                    } else {
                        $.alert(response.message);
                    }
                });
            }
            function limpiar() {
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
                    msgSizeTooLarge: 'Archivo: "{name}" (<b>{size} KB</b>) excede el tamaño máximo permitido de <b>{maxSize} KB</b>.',
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
            $(function(){
                $("#radioec").change(function(){
                    $('#lb_ec').attr('class','btn btn-success btn-xs');
                    $('#lb_ex').attr('class','btn btn-default btn-xs');
                    $('#spanec').show();$('#spanex').hide();
                    habilitar('Ec');
                });
                $("#radioex").change(function(){
                    $('#lb_ex').attr('class','btn btn-success btn-xs');
                    $('#lb_ec').attr('class','btn btn-default btn-xs');
                    $('#spanex').show();$('#spanec').hide();
                    habilitar('Ex');
                });
                habilitar('Ec');
            });
            function habilitar(op){
                var ec=op==='Ec',lon_ced=$('#Prs_Ced').val().length;
                $('#Ide_Cod').find('option').hide().end().find('option[data-tipo="'+op+'"]').show();
                $('#Ide_Cod').attr('disabled',ec);
                $('#Ide_Cod').val(ec?(lon_ced===10?2:(lon_ced===13?1:0)):7);
            }
        </script>
    </BODY>
</HTML>

