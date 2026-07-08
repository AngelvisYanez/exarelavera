<?php
/**
 * @abstract Permite realizar la edici�n de los datos de un activo
 * @author Jos� Ambulud�
 * @version 1.0
 * Fecha de creaci�n  2016-07-06
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_activo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Activo($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_Activo;
/* Secci�n para eliminar una imagen */
if (isset($key)) {
    $descomponer = explode('*', $key);
    $rs_foto = $obBD_con1->getRowConsulta(11, $descomponer[1], $obBD_conexion);
    $imagenes = explode(',', $rs_foto['Act_Fot']);
    unlink("../../imagenes/" . $Ses_Emp_Cod . "/Activos/" . $descomponer[2]);
    $clave = array_search($descomponer[2], $imagenes);
    array_splice($imagenes, $clave, 1);
    for ($i = 0; $i < count($imagenes); $i++) {
        $fotos = $imagenes[$i] . ',' . $fotos;
    }
    $fotos = trim($fotos, ',');
    //Ejecutamos un update sobre la tabla activo para agregar la imagen
    $obBD_con1->operacionobBD(12, $descomponer[1] . '*' . $fotos, $obBD_conexion);
    $response['success'] = true;
    echo json_encode($response);
    exit();
}
/* Secci�n para cargar datos en el Jqgrid referente a los activos */
if (isset($activoAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Suc_Cod"] = $Ses_Suc_Cod;
    $contar = $obBD_con1->getRowConsulta(613, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(613, $data, $obBD_conexion);
    }
    echo json_encode($responce);
    exit();
}
/* Secci�n para listar los campos pertenecientes a un tipo de activo */
if (isset($buscarCampos)) {
    if ($Act_Cod == 0) {
        $caso = 5;
    } else {
        $caso = 18;
    }
    $response = $obBD_con1->getArrayConsulta($caso, $Tia_Cod . '*' . $Act_Cod, $obBD_conexion);
    echo json_encode($response);
    exit();
}
/* Secci�n para guardar los cambios */
if (isset($saveActivo)) {
    $responce['success'] = false;
    $responce['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    //Ejecutamos un update sobre la tabla activo 
    $obBD_con1->operacionobBD(19, $Act_Cod . '*' . $Act_Des . '*' . $Act_Gar . '*' . $Est_Cod . '*' . $Act_Fec . '*' . $Act_Ann . '*' . $Act_Res . '*' . $Act_Pde. '*' . $Act_Ffd, $obBD_conexion);
    //Update sobre la tabla det_activo 
    $campos = $obBD_con1->getArrayConsulta(5, $Tia_Cod, $obBD_conexion);
    foreach ($campos as $valor) {
        $obBD_con1->operacionobBD(20, $Act_Cod . '*' . $valor['Cam_Cod'] .'*'.$_POST[$valor['Cam_Cod']], $obBD_conexion);
    }
    $rs_foto = $obBD_con1->getRowConsulta(11, $Act_Cod, $obBD_conexion);
    $indice = 0;
    if (!empty($rs_foto['Act_Fot'])) {
        $descomponer = explode(',', $rs_foto['Act_Fot']);
        foreach ($descomponer as &$valor) {
            $extension_img = explode('.', $valor);
            $last = count($extension_img) - 1;
            $oldname = "../../imagenes/" . $Ses_Emp_Cod . "/Activos/" . $valor;
            $img = "img_activo_" . $Act_Cod . "_" . $indice . "." . $extension_img[$last];
            $newname = "../../imagenes/" . $Ses_Emp_Cod . "/Activos/" . $img;
            rename($oldname, $newname);
            $act_fot = $act_fot . ',' . $img;
            $indice++;
        }
        unset($valor);
    }
    $name_img = $_FILES['Act_Fot']['name'];
    $tmp_img = $_FILES['Act_Fot']['tmp_name'];
    $carpeta = "../../imagenes/" . $Ses_Emp_Cod . '/Activos';
    if (!empty($name_img[0])) {
        for ($a = 0; $a < count($name_img); $a++) {
            $extension_img = explode('.', $name_img[$a]);
            $last = count($extension_img) - 1;
            $img = "img_activo_" . $Act_Cod . "_" . $indice . "." . $extension_img[$last];
            copy($tmp_img[$a], $carpeta . '\\' . $img);
            $act_fot = $act_fot . ',' . $img;
            $indice++;
        }
    }
    $act_fot = trim($act_fot, ",");
    //Ejecutamos un update sobre la tabla activo para agregar la(s) imagen(s)
    $obBD_con1->operacionobBD(12, $Act_Cod . '*' . $act_fot, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $responce['success'] = true;
    } else {
        $responce['message'] = $obBD_con1->MsgError;
    }
    echo json_encode($responce);
    exit();
}
?>

<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <link href="../../framework/jquery/bootstrap/bootstrap-fileinput/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script src="../../framework/jquery/bootstrap/bootstrap-fileinput/js/fileinput.js" type="text/javascript"></script>
        <script type="text/javascript" src="../VALIDACIONES/act_calcular_depreciacion.js"></script>
        <style>
            .file-drop-zone {margin: 1px;padding: 1px;}
            .file-preview {border-radius: 5px;border: 0px;padding: 1px;}
            .file-drop-zone-title {padding: 70px 10px;}
            th.ui-th-column div{
                white-space:normal !important;
                height:auto !important;
                padding:2px;
            }
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Activos</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row"> 
                    <div class="col-sm-12">
                        <div id="tabs" class="ui-tab-fix">
                            <ul style="font-size: 12px;">
                                <li><a href="#info_activo">Activo</a></li>
                                <li><a href="#detalle_activo">Detalle</a></li>
                                <li><a href="#imagenes">Im&aacute;genes</a></li>
                                <li><a href="#depreciacion_activo">Depreciaci&oacute;n</a></li>
                            </ul>
                            <div id="info_activo">
                                <form id="frm_Act" name="frm_Act" class="form-horizontal normal" action="javascript:">
                                    <!-- Campo de clave primaria del activo -->
                                    <input type="hidden" id="Act_Cod" name="Act_Cod"/>
                                    <!-- Campo que representa los d�as de depreciaci�n DT o DM -->
                                    <input type="hidden" id="Cfg_Ddp" name="Cfg_Ddp"/>
                                    <!-- Campo para guardar la fecha final de depreciaci�n -->
                                    <input type="hidden" id="Act_Ffd" name="Act_Ffd"/>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <fieldset class="exa-fieldset">                           
                                                <legend class="Titulos2">Informaci&oacute;n General</legend>
                                                <div class="form-group Titulos2">
                                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                                </div>
                                                <div class="col-sm-8 col-md-7">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label label-xs required" for="Bus_Act">Activo:</label>  
                                                        <div class="col-sm-6">  
                                                            <div class="input-group">
                                                                <input id="Bus_Act" name="Act_Des" type="text" class="form-control input-xs" value="Seleccionar activo" readonly/>
                                                                <span class="input-group-btn">
                                                                    <button class="btn btn-success btn-xs" onclick="$('#activoDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-search" title="Buscar Activo"></span></button>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-3 control-label label-xs required" for="des_padre">Categor&iacute;a:</label>  
                                                        <div class="col-md-6">
                                                            <?php $row_rs_tia_tip = $obBD_con1->getArrayConsulta(13, $Ses_Emp_Cod, $obBD_conexion); ?>
                                                            <select name="Tia_Cod" id="Tia_Cod" class="form-control input-xs" data-placeholder="Seleccione una categor&iacute;a de activo">
                                                                <option value=""></option>
                                                                    <?Php foreach ($row_rs_tia_tip as $row) { ?>
                                                                    <optgroup label="<?Php echo mb_convert_encoding($row['descripcion'], 'ISO-8859-1', 'UTF-8'); ?>">
                                                                            <?php $row_rs_tia_det = $obBD_con1->getArrayConsulta(14, $row['Tia_Cod'], $obBD_conexion); ?>
                                                                            <?Php foreach ($row_rs_tia_det as $row) { ?>
                                                                            <option value="<?php echo $row['Tia_Cod']; ?>"><?Php echo mb_convert_encoding($row['descripcion'], 'ISO-8859-1', 'UTF-8'); ?></option>
                                                                            <?Php } ?>
                                                                    </optgroup>
                                                                    <?Php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-3 control-label required" for="Act_Des">Descripci�n:</label>
                                                        <div class="col-md-9">                     
                                                            <textarea class="form-control input-xs" id="Act_Des" name="Act_Des" style="resize: none;" required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-3 control-label" for="Act_Obs">Observaci�n:</label>
                                                        <div class="col-md-9">                     
                                                            <textarea class="form-control input-xs" id="Act_Obs" name="Act_Obs" style="resize: none;"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4 col-md-5">
                                                    <div class="form-group">
                                                        <label class="col-md-5 control-label label-xs required" for="Act_Gar">Garant�a (meses):</label>  
                                                        <div class="col-md-5">
                                                            <input id="Act_Gar" name="Act_Gar" type="text" placeholder="" class="form-control input-xs" value="0" style="text-align: right;" onkeypress="return validar_numeric(event);" required/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-5 control-label label-xs required" for="Est_Cod">Estado:</label>
                                                        <div class="col-md-5">
                                                            <select name="Est_Cod" id="Est_Cod" class="form-control input-xs" required >
                                                                <option value="">Seleccione</option>
                                                                <?Php
                                                                $rs_estados = $obBD_con1->getArrayConsulta(15, $Ses_Emp_Cod, $obBD_conexion);
                                                                if (count($rs_estados) > 0) {
                                                                    foreach ($rs_estados as $row) {
                                                                        ?>
                                                                        <option value="<?Php echo $row['Est_Cod']; ?>"><?Php echo $row['Est_Des']; ?></option>	
                                                                    <?php }
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>      
                                            </fieldset>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="detalle_activo">
                                <form id="frm_Det" name="frm_Det" class="form-horizontal normal" action="javascript:">
                                    <div class="row">   
                                        <div class="col-xs-12">
                                            <fieldset class="exa-fieldset">                           
                                                <legend class="Titulos2">Campos de Tipo de Activo</legend>
                                                <div id="cam_new" class="col-sm-12"></div>
                                            </fieldset>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="imagenes">
                                <form id="frm_Img" name="frm_Img" class="form-horizontal normal" enctype="multipart/form-data" action="javascript:">
                                    <div class="row">   
                                        <div class="col-xs-12">
                                            <fieldset class="exa-fieldset">                           
                                                <legend class="Titulos2">Im&aacute;genes</legend>
                                                <input id="Act_Fot" name="Act_Fot[]" type="file" multiple data-preview-file-type="any" />
                                            </fieldset>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="depreciacion_activo">
                                <form id="frm_Dep" name="frm_Dep" class="form-horizontal normal" action="javascript:">
                                    <div class="row">   
                                        <div class="col-xs-12">
                                            <fieldset class="exa-fieldset">                           
                                                <legend class="Titulos2">C&aacute;lculo Proyecci&oacute;n Depreciaci&oacute;n</legend>
                                                <div class="form-group Titulos2">
                                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                                </div>
                                                <div class="col-sm-4 col-md-5">
                                                    <fieldset class="exa-fieldset">                           
                                                        <legend class="Titulos2">Datos de Depreciaci&oacute;n</legend>
                                                        <div class="form-group">
                                                            <label class="col-md-5 control-label label-xs" for="Act_Fec">Fecha Inicio:</label>
                                                            <div class="col-md-5">
                                                                <input type="text" name="Act_Fec" id="Act_Fec" class="form-control input-xs" readonly style="font-size: 20px; font-weight: bold; text-align: right;">
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-md-5 control-label label-xs" for="Act_Val">Valor a Depreciar:</label>
                                                            <div class="col-md-5">
                                                                <input type="text" name="Act_Val" id="Act_Val" class="form-control input-xs" readonly style="font-size: 20px; font-weight: bold; text-align: right;">
                                                            </div>
                                                        </div>
                                                        <div id="Cfg_Por" class="form-group" style="display: none;">
                                                            <label class="col-md-5 control-label required" for="Act_Des">Porcentaje:</label>
                                                            <div class="col-md-5">                     
                                                                <select id="Act_Dep" name="Act_Dep" class="form-control input-xs">
                                                                    <option value="">Seleccinar una opci&oacute;n</option>
                                                                    <?php $rs_porcentaje=$obBD_con1->getArrayConsulta(16,$Ses_Suc_Cod,$obBD_conexion);
                                                                    if(count($rs_porcentaje)>0){
                                                                        foreach ($rs_porcentaje as $valor){?>
                                                                    <option value="<?php echo $valor['Apr_Por'];?>"><?php echo mb_convert_encoding($valor['Apr_Des'], 'ISO-8859-1', 'UTF-8');?></option>
                                                                    <?php }
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-md-5 control-label label-xs required" for="Act_Ann">Vida &Uacute;til (a�os):</label>
                                                            <div class="col-md-5">
                                                                <input type="text" name="Act_Ann" id="Act_Ann" class="form-control input-xs" style="text-align: right;" onkeypress="return validar_numeric(event);" required="">
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-md-5 control-label label-xs required" for="Act_Res">Valor Residual:</label>
                                                            <div class="col-md-5">
                                                                <input type="text" name="Act_Res" id="Act_Res" class="form-control input-xs" style="text-align: right;" value="0" required="">
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-md-5 control-label label-xs required" for="Act_Res1">Valor Residual(%):</label>
                                                            <div class="col-md-5">
                                                                <input type="text" name="Act_Res1" id="Act_Res1" class="form-control input-xs" style="text-align: right;" value="0" required="">
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-md-5 control-label label-xs required" for="Act_Pde">Depreciaci&oacute;n(%):</label>
                                                            <div class="col-md-5">
                                                                <input type="text" name="Act_Pde" id="Act_Pde" class="form-control input-xs" style="text-align: right;" value="0" readonly="">
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <div class="col-md-5"></div>
                                                            <div class="col-md-5">
                                                                <button type="button" class="btn btn-xs btn-success" onclick="calcular_depreciacion();"><span class="glyphicon glyphicon-th"></span> Calcular</button>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </div>
                                                <div class="col-sm-6 col-md-7">
                                                    <div class="form-group">
                                                        <label class="control-label label-sm required" for="tipo_dep">Depreciaci&oacute;n:</label>  
                                                        <select id="tipo_dep" name="tipo_dep" class="form-control input-xs" style="text-align: center; display: inline-block; width: auto;">
                                                            <option value="A">Anual</option>
                                                            <option value="M">Mensual</option>
                                                        </select>
                                                    </div>
                                                    <div id="d_a">
                                                        <table id="dep_anu"></table>
                                                        <div id="list_da_Pager"></div>
                                                    </div>
                                                    <div id="d_m" style="display: none;">
                                                        <table id="dep_men"></table>
                                                        <div id="list_dm_Pager"></div>
                                                    </div>
                                                </div>
                                            </fieldset>
                                            <div class="form-group">
                                                <div class="col-sm-8">
                                                    <button type="button" name="btn_sav" id="btn_sav" onclick="saveForm();" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!--Inicio de di�logo para buscar un activo--> 
        <div id="activoDialog" title="B�squeda de Activos">  
            <form class="form-horizontal normal"></form>
        </div>

        <script type="text/javascript">
            $(function () {
                //Secci�n para crear tabs y asu vez validar el contenido dentro de cada uno
                $("#tabs").createTabs({
                    beforeActivate: function (event, ui) {
                        $('#loader').show();
                        var valid = true;
                        var current = $(this).tabs("option", "active");
                        var panelId = $("#tabs ul a").eq(current).attr("href");
                        $(panelId).find('form').formSubmit();
                        $(panelId).find(":input").each(function () {
                            if (!$(this).prop('required')) {
                            }
                            else {
                                if (!$(this).val()) {
                                    $('#loader').hide();
                                    valid = false;
                                }
                            }
                        });
                        return valid;
                    }
                });
                //Inicio del di�logo para mostrar activos 
                $.createSearchDialog('#activoDialog', [
                    {label: 'C�d.Int.', name: 'Act_Cod', align: 'center', key: true, width: 50},
                    {label: 'Activo', name: 'Act_Des', width: 190},
                    {label: 'Inicio Depreciaci&oacute;n', name: 'Act_Fec', align: 'center', width: 80},
                    {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            return $.getGridButton(cargarActivo, rowObject);
                        }
                    }
                ], null, null, null, null, {title: 'Activos', options: [{label: '&nbsp;&nbsp;Nombre&nbsp;&nbsp;', value: 'd'}, {label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c'}]});
                //Asignamos la funci�n de Chosen
                $("#Tia_Cod").createChosen('input-xs', {allow_single_deselect: true});
                //Secci�n para extraer los campos de un tipo de activo
                $('#Tia_Cod').on('change', function () {
                    cargarCampos(this.value, 0);
                });
                //Secci�n para declarar datepicker
                $.createDatePickers("#Act_Fec");
                //Secci�n para efectuar el cambio de depreciacion(anual,mensual)
                $('#tipo_dep').on('change', function () {
                    ($('#tipo_dep').val() === 'M') ? ($('#d_a').hide(), $('#d_m').show()) : ($('#d_a').show(), $('#d_m').hide());
                });
                //Jqgrid para presentar la depreciaci�n anual
                $("#dep_anu").createGrid({
                    height: 130,
                    caption: 'DEPRECIACI&Oacute;N ANUAL',
                    colModel: [
                        {label: 'Peri&oacute;do', align: 'center', name: 'periodo', width: 80},
                        {label: 'Valor Depreciaci&oacute;n', align: 'center', name: 'Val_Dep', width: 130},
                        {label: 'Depreciaci&oacute;n Acumulada', align: 'center', name: 'Dep_Acu', width: 130},
                        {label: 'Valor en Libros', align: 'center', name: 'Val_Res', width: 130}
                    ], pgbuttons: false, pgtext: null
                }, true, "list_da_Pager", {view: false});
                $('#dep_anu').navButtonAdd('#list_da_Pager',
                        {
                            buttonicon: "glyphicon glyphicon-download-alt",
                            id: 'btn_exc',
                            title: "Descargar archivo Excel",
                            caption: " Excel",
                            position: "last",
                            onClickButton: function () {
                                $('#dep_anu').jqGrid('exportGridExcel', {nombre: 'Depreciaci&oacute;n Anual', hoja: 'Dep. Anual', caption: true});
                            }
                        });
                //Jqgrid para presentar la depreciaci�n mensual
                $("#dep_men").createGrid({
                    height: 130,
                    caption: 'DEPRECIACI&Oacute;N MENSUAL',
                    colModel: [
                        {label: 'Anio', name: 'anio', width: 80, hidden: true},
                        {label: 'Fecha inicio', align: "center", name: 'fec_ini', width: 90},
                        {label: 'Fecha fin', align: "center", name: 'fec_fin', width: 90},
                        {label: 'Valor Depreciaci&oacute;n', align: "center", name: 'Val_Dep', width: 145},
                        {label: 'Depreciaci&oacute;n Acumulada', align: "center", name: 'Dep_Acu', width: 110},
                        {label: 'Valor en Libros', align: "center", name: 'Val_Res', width: 110}
                    ],
                    pgbuttons: false, pgtext: null,
                    sortname: 'fec_ini',
                    sortorder: 'asc',
                    grouping: true,
                    groupingView: {
                        groupField: ['anio'],
                        groupColumnShow: [false],
                        groupText: ['<span style="float:left;"><b>{0} - {1} Registro(s)</b></span>'],
                        groupCollapse: true,
                        groupOrder: ['asc']
                    }
                }, true, "list_dm_Pager", {view: false});
                $('#dep_men').navButtonAdd('#list_dm_Pager',
                        {
                            buttonicon: "glyphicon glyphicon-download-alt",
                            id: 'btn_exc',
                            title: "Descargar archivo Excel",
                            caption: " Excel",
                            position: "last",
                            onClickButton: function () {
                                $('#dep_men').jqGrid('exportGridExcel', {nombre: 'Depreciaci&oacute;n Mensual', hoja: 'Dep. Mensual', caption: true});
                            }
                        });
                $('#Act_Res').on('input', function () {
                    var vre = $("#Act_Res").val();
                    var vco = $("#Act_Val").val();
                    var numerador = vre * 100;
                    var porcentaje = numerador / vco;
                    $("#Act_Res1").val(porcentaje.toFixed(2));
                });
                $("#Act_Res1").on('input', function () {
                    var vco = $("#Act_Val").val();
                    var vde = $("#Act_Res1").val();
                    var vre = (vco * vde) / 100;
                    $("#Act_Res").val(vre.toFixed(2));
                });
                $('#Act_Fot').on("filepredelete", function () {
                    var abort = true;
                    if (confirm("Desea ELIMINAR esta imagen?")) {
                        abort = false;
                    }
                    return abort;
                });
            });

            /*FUNCIONES NECESARIAS PARA EL MANEJO DE DATOS*/
            //Funci�n para cargar los datos del activo seleccionado
            var por_sri=0;
            function cargarActivo(activo) {
                $('#activoDialog').dialog('close');
                $("[id^='frm_']").setData(activo);
                $('#Act_Val').val(parseFloat(activo.Act_Val).toFixed(2));
                $('#Act_Res').val(parseFloat(activo.Act_Res).toFixed(2));
                $('#tipo_dep').val('A');
                $('#Tia_Cod').val(activo.Tia_Cod).trigger('chosen:updated');
                $('#Act_Res1').val(parseFloat((activo.Act_Res * 100) / activo.Act_Val).toFixed(2));
                if(activo.Cfg_Por==='S'){
                    $('#Cfg_Por').show();$('#Act_Ann').attr('readonly',true);$('#Act_Dep').val(activo.Act_Pde);por_sri=1;
                }
                cargarCampos(activo.Tia_Cod, activo.Act_Cod);
                var vari = [], variobj = [];
                if (activo.foto !== null) {
                    var foto = activo.foto.split(',');
                    $.each(foto, function (indice, value) {
                        vari.push('../../imagenes/<?php echo $Ses_Emp_Cod; ?>/Activos/' + value + '?x=' + Math.random());
                        variobj.push({caption: value, key: indice + '*' + activo.Act_Cod + '*' + value});
                    });
                } else {
                    vari = '';
                    variobj = '';
                }
                inicializar_input_file(vari, variobj);
                calcular_depreciacion();
            }
            //Funci�n para cargar los campos de tipo_activo
            function cargarCampos(Tia_Cod, Act_Cod) {
                var cam_new=[];
                var data = {Tia_Cod: Tia_Cod, Act_Cod: Act_Cod, buscarCampos: true};
                $.post('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>', data, function (response) {
                    cam_new = response;
                    $('#cam_new').html('');
                    for (var i = 0; i < cam_new.length; i++) {
                        campo = '<div class="col-sm-5 col-md-5"><div class="form-group">\n\
                                    <label class="col-md-5 control-label '+(cam_new[i]['Cam_Req'] === 'S'?'required':'')+ '">' + cam_new[i]['Cam_Lar'] + ':</label>\n\
                                    <div class="col-md-7">\n\
                                        <input type="text" class="form-control input-xs" id="CAM_' + cam_new[i]['Cam_Cod'] + '" name="' + cam_new[i]['Cam_Cod'] + '" value="' + (Act_Cod === 0 ? '' : cam_new[i]['Act_Val']) + '" ' + (cam_new[i]['Cam_Req'] === 'S' ? 'required' : '') + '/>\n\
                                    </div>\n\
                                </div></div>';
                        $("#cam_new").append(campo);
                    }
                }, 'json').fail(function () {
                    $.alert();
                });
            }
            //Funci�n para inicializar componente fileinput
            function inicializar_input_file(vari, variobj) {
                $('#Act_Fot').fileinput('destroy');
                $("#Act_Fot").fileinput({
                    uploadUrl: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                    showCaption: false,
                    showRemove: false,
                    showCancel: false,
                    browseClass: "btn btn-success btn-xs",
                    browseLabel: 'Buscar Imagen',
                    uploadClass: 'btn btn-success btn-sm hide',
                    allowedFileExtensions: ['jpg', 'png', 'gif'],
                    overwriteInitial: false,
                    maxFileSize: 2000,
                    msgSizeTooLarge: 'Archivo: "{name}" (<b>{size} KB</b>) excede el tama�o m�ximo permitido de <b>{maxSize} KB</b>.',
                    dropZoneTitle: 'Arrastrar y Soltar Im�genes Aqu�...',
                    maxFileCount: 2,
                    msgFilesTooMany: 'N�mero de im�genes permitidas 2.',
                    validateInitialCount: true,
                    initialPreview: vari,
                    initialPreviewAsData: true,
                    initialPreviewFileType: 'image',
                    initialPreviewConfig: variobj,
                    deleteUrl: "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",
                    disable: function () {
                        var self = this;
                        self.isDisabled = false;
                        self._raise('fileenabled');
                        self.$element.removeAttr('disabled');
                        self.$container.find(".kv-fileinput-caption").removeClass("file-caption-disabled");
                        self.$container.find(".btn-file, .fileinput-remove, .fileinput-upload, .file-preview-frame button").removeAttr("disabled");
                        self._initDragDrop();
                        return self.$element;
                    }
                });
            }
            //Funci�n para calcular la depreciaci�n anual y mensual
            function calcular_depreciacion() {
                var porcentaje=0;
                var valor_depreciar = $('#Act_Val').val() - $('#Act_Res').val();
                if(por_sri>0){porcentaje=$('#Act_Dep').val();}else{porcentaje=0;}
                var deprecia = calculo($("#Act_Fec").val(), valor_depreciar, porcentaje, $('#Act_Ann').val(), $('#Act_Val').val(), $('#Cfg_Ddp').val());
                $("#dep_anu").setRows(deprecia[0]);
                $('#dep_men').setRows(deprecia[1]);
                $("#Act_Fec").val(deprecia[2]);
                $("#Act_Ffd").val(deprecia[3]);
                $("#Act_Ann").val(deprecia[4]);
                $("#Act_Pde").val(deprecia[6]);
            }
            //Funci�n para guardar los cambios que se hallan efectuado sobre un activo determinado
            function saveForm() {
                calcular_depreciacion();
                var formData = new FormData(document.getElementById("frm_Img"));
                formData.append("saveActivo", true);
                var activo = $(document.forms['frm_Act']).serializeArray();
                for (var i = 0; i < activo.length; i++) {
                    formData.append(activo[i].name, activo[i].value);
                }
                var detalle = $(document.forms['frm_Det']).serializeArray();
                for (var i = 0; i < detalle.length; i++) {
                    formData.append(detalle[i].name, detalle[i].value);
                }
                var depreciacion = $(document.forms['frm_Dep']).serializeArray();
                for (var i = 0; i < depreciacion.length; i++) {
                    formData.append(depreciacion[i].name, depreciacion[i].value);
                }
                $.ajax({
                    url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                    type: "post", dataType: "json", data: formData, cache: false, contentType: false, processData: false
                })
                        .done(function (responce) {
                            if (responce.success === true) {
                                $.alert("Transaccion Realizada con &Eacute;xito!");
                                $('#Tia_Cod').val('').trigger('chosen:updated');
                                $('#frm_Act')[0].reset();
                                inicializar_input_file('', '');
                                $('#tabs').tabs({active: 0});
                                $('#activoDialog').getDialogGrid().trigger('reloadGrid', [{page: 1}]);
                            } else {
                                $.alert(responce.message);
                            }
                        });
            }
        </script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    </BODY>
</HTML>