<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por lotes
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_emp.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');


/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Emp($Ses_Dat_Dis);
$obBD_master = new Class_Log_Conexion_Emp("exa_master");
/** 
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Emp;
/**
 * Evita el reenvio 
 */
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");
$year = date("Y");

if (isset($saveSucursal)) {
    $data = filter_input_array(INPUT_POST);
    $responce['success'] = false;
    $responce['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_master = new Class_Log_Conexion_Emp("exa_master");
    $obBD_con1->inicio_transaccion($obBD_master->conexion);
    /* Grabo la nueva sucursal en exa_master */
    $obBD_con1->grabarv_registros(sentencias_emp(7, $obBD_con1->parametros($data)), $obBD_master->conexion);
    $data['ultimo'] = $obBD_con1->insercionid($obBD_master->conexion);
    $data['Adm_Ced'] = '0703703413';
    /* creo el access en exa_master para el administrador de sistemas */
    $obBD_con1->grabarv_registros(sentencias_emp(23, $obBD_con1->parametros($data)), $obBD_master->conexion);
    //$obBD_con1->grabarv_registros(sentencias_emp(2,$obBD_con1->parametros($data)), $obBD_master->conexion); 
    $obBD_con1->fin_transaccion_nomsn($obBD_master->conexion);
    if ($obBD_con1->Error == 0) {
        $pefilIds = array();
        $dataPerfil = array();
        $obBD_child = new Class_Log_Conexion_Emp($data['Dat_Dis']);
        $obBD_child2 = new Class_Log_Conexion_Emp($data['Dat_Dis']);
        $contarConfig = $obBD_con1->getRowConsulta(12, $data, $obBD_child);
        $contarMarca = $obBD_con1->getRowConsulta(10, $data, $obBD_child);
        $contarUbica = $obBD_con1->getRowConsulta(14, $data, $obBD_child);
        $contarPlanCue = $obBD_con1->getRowConsulta(16, $data, $obBD_child);
        $contarPerfiles = $obBD_con1->getRowConsulta(19, $data, $obBD_child);
        $adminPerson = $obBD_con1->getRowConsulta(25, $data, $obBD_child);
        $obBD_con1->inicio_transaccion($obBD_child->conexion);
        /* creo la sucursal en Dat_Dis  */
        $obBD_con1->grabarv_registros(sentencias_emp(8, $obBD_con1->parametros($data)), $obBD_child->conexion);
        /* ingreso el tipo precio en Dat_Dis */
        $obBD_con1->grabarv_registros(sentencias_emp(13, $obBD_con1->parametros($data)), $obBD_child->conexion);
        /* creo un punto de impresion para la sucursal */
        $obBD_con1->grabarv_registros(sentencias_emp(22, $obBD_con1->parametros($data)), $obBD_child->conexion);
        /* creo la confic_fact si no existe */
        if (($contarConfig['total'] * 1) <= 0)
            $obBD_con1->grabarv_registros(sentencias_emp(9, $obBD_con1->parametros($data)), $obBD_child->conexion);
        /* creo la marca "Ninguna" si no existe */
        if (($contarMarca['total'] * 1) <= 0)
            $obBD_con1->grabarv_registros(sentencias_emp(11, $obBD_con1->parametros($data)), $obBD_child->conexion);
        /* creo la ubicacion "Ninguna" si no existe */
        if (($contarUbica['total'] * 1) <= 0)
            $obBD_con1->grabarv_registros(sentencias_emp(15, $obBD_con1->parametros($data)), $obBD_child->conexion);
        /* creo un plan de cuentas si no existe */
        if (($contarPlanCue['total'] * 1) <= 0) {
            $data['fec_plan'] = $hoy;
            $obBD_con1->grabarv_registros(sentencias_emp(17, $obBD_con1->parametros($data)), $obBD_child->conexion);
            $data['plan'] = $obBD_con1->insercionid($obBD_child->conexion);
            $data['ini_per'] = $year . "-01-01";
            $data['fin_per'] = $year . "-12-31";
        }
        /* Creo el usuario administrado para esta sucursal */
        $data['Prs_Cod'] = $adminPerson['Prs_Cod'];
        $obBD_con1->grabarv_registros(sentencias_emp(26, $obBD_con1->parametros($data)), $obBD_child->conexion);
        $pefilIds['Usu_Cod'] = $obBD_con1->insercionid($obBD_child->conexion);
        /* creo los perfiles de usuario  si no existen */
        if (($contarPerfiles['total'] * 1) <= 0) {
            $dataPerfil['emp_codigo'] = $data['Emp_Cod'];

            $dataPerfil['per_descrip'] = "Administrador de Sistemas";
            $obBD_con1->grabarv_registros(sentencias_emp(20, $obBD_con1->parametros($dataPerfil)), $obBD_child->conexion);
            $pefilIds['per_cod_1'] = $obBD_con1->insercionid($obBD_child->conexion);

            $dataPerfil['per_descrip'] = "Gerente";
            $obBD_con1->grabarv_registros(sentencias_emp(20, $obBD_con1->parametros($dataPerfil)), $obBD_child->conexion);
            $pefilIds['per_cod_2'] = $obBD_con1->insercionid($obBD_child->conexion);

            $dataPerfil['per_descrip'] = "Tecnico";
            $obBD_con1->grabarv_registros(sentencias_emp(20, $obBD_con1->parametros($dataPerfil)), $obBD_child->conexion);
            $pefilIds['per_cod_3'] = $obBD_con1->insercionid($obBD_child->conexion);

            $dataPerfil['per_descrip'] = "Clientes";
            $obBD_con1->grabarv_registros(sentencias_emp(20, $obBD_con1->parametros($dataPerfil)), $obBD_child->conexion);
            $pefilIds['per_cod_4'] = $obBD_con1->insercionid($obBD_child->conexion);

            //Nuevos roles de usuario
            $dataPerfil['per_descrip'] = "Contador";
            $obBD_con1->grabarv_registros(sentencias_emp(20, $obBD_con1->parametros($dataPerfil)), $obBD_child->conexion);
            $pefilIds['per_cod_5'] = $obBD_con1->insercionid($obBD_child->conexion);

            $dataPerfil['per_descrip'] = "Auxiliar";
            $obBD_con1->grabarv_registros(sentencias_emp(20, $obBD_con1->parametros($dataPerfil)), $obBD_child->conexion);
            $pefilIds['per_cod_6'] = $obBD_con1->insercionid($obBD_child->conexion);

            $dataPerfil['per_descrip'] = "Emprendedor";
            $obBD_con1->grabarv_registros(sentencias_emp(20, $obBD_con1->parametros($dataPerfil)), $obBD_child->conexion);
            $pefilIds['per_cod_7'] = $obBD_con1->insercionid($obBD_child->conexion);

            $dataPerfil['per_descrip'] = "RRHH";
            $obBD_con1->grabarv_registros(sentencias_emp(20, $obBD_con1->parametros($dataPerfil)), $obBD_child->conexion);
            $pefilIds['per_cod_8'] = $obBD_con1->insercionid($obBD_child->conexion);

            // fin de nuevos roles de usuario


            /* grabo la relacioon usuario perfil*/
            $obBD_con1->grabarv_registros(sentencias_emp(29, $obBD_con1->parametros($pefilIds)), $obBD_child->conexion);
        } else {/* si ya existen los perfiles busco el "Administrador de Sistemas" */
            $dataPerfil['emp_codigo'] = $data['Emp_Cod'];
            $dataPerfil['per_descrip'] = "Administrador de Sistemas";
            $adminPerfil = $obBD_con1->getRowConsulta(24, $dataPerfil, $obBD_child);
            $pefilIds['per_cod_1'] = $adminPerfil['Per_Cod'];
        }
        /* grabo la relacioon usuario perfil*/
        $obBD_con1->grabarv_registros(sentencias_emp(27, $obBD_con1->parametros($pefilIds)), $obBD_child->conexion);
        $obBD_con1->fin_transaccion_nomsn($obBD_child->conexion);
        if ($obBD_con1->Error == 0) {
            $responce['success'] = true;
        }
        $responce['message'] = $obBD_con1->MsgError;
        //$obBD_con1->inicio_transaccion($obBD_child2->conexion);       

        if (($contarPlanCue['total'] * 1) <= 0) {
            $obBD_con1->grabarv_registros(sentencias_emp(18, $obBD_con1->parametros($data)), $obBD_child2->conexion);
        }
        if (($contarPerfiles['total'] * 1) <= 0) {

            $sql = reporteHtml(array('{per_cod}' => $pefilIds['per_cod_1']), 'adm_sql_sistemas.sql')
                . reporteHtml(
                    array('{per_cod}' => $pefilIds['per_cod_2']),
                    'adm_sql_gerente.sql'
                )
                . reporteHtml(
                    array('{per_cod}' => $pefilIds['per_cod_3']),
                    'adm_sql_tecnico.sql'
                )
               /* . reporteHtml(
                    array('{per_cod}' => $pefilIds['per_cod_4']),
                    'adm_sql_cliente.sql'
                )*/ . reporteHtml(
                    array('{per_cod}' => $pefilIds['per_cod_5']),
                    'adm_sql_contador.sql'
                )
                . reporteHtml(
                    array('{per_cod}' => $pefilIds['per_cod_6']),
                    'adm_sql_auxiliarcont.sql'
                )
                . reporteHtml(
                    array('{per_cod}' => $pefilIds['per_cod_7']),
                    'adm_sql_emprendedor.sql'
                )
                . reporteHtml(
                    array('{per_cod}' => $pefilIds['per_cod_8']),
                    'adm_sql_RRHH.sql'
                );

            $sql = explode(';', $sql);
            foreach ($sql as $row)
                $obBD_con1->grabarv_registros(sentencias_emp(21, $obBD_con1->parametros($row)), $obBD_child2->conexion);
        }
        //$obBD_con1->fin_transaccion_nomsn($obBD_child2->conexion);
        if ($obBD_con1->Error == 0) {
            $responce['success'] = $responce['success'] && true;
        }
        $responce['message'] = $obBD_con1->MsgError;
    }
    //ChromePhp::log($obBD_con1->MsgError);
    echo json_encode($responce);
    exit();
}
if (isset($empAjax)) {
    $data = filter_input_array(INPUT_GET);

    $contar = $obBD_con1->getRowConsulta(4, $data, $obBD_master);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0)
        $responce['rows'] =  $obBD_con1->getArrayConsulta(4, $data, $obBD_master);
    echo json_encode($responce);
    exit();
}
if (isset($sucuAjax)) {
    $data = filter_input_array(INPUT_GET);
    $obBD_child = new Class_Log_Conexion_Emp($data['Dat_Dis']);
    $contar = $obBD_con1->getRowConsulta(5, $data, $obBD_child);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0)
        $responce['rows'] =  $obBD_con1->getArrayConsulta(5, $data, $obBD_child);
    echo json_encode($responce);
    exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Emp. Registrar Sucursal [EXA]"; ?></TITLE>
    <meta charset= "UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid.php") ?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>

    </style>
</HEAD>

<BODY>
    <div id="set1">

        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table" style="table-layout:fixed;">
            <tr class="BarraTitulo">
                <td colspan="2" height="10">&raquo; Registrar Sucursales</td>
            </tr>
            <tr>
                <td colspan="2" height="10">
                    <FIELDSET>
                        <LEGEND>
                            <label class="Titulos2">Buscar Empresas</label>
                        </LEGEND>
                        <form id="empForm" action="javascript:$('#list').Search('#empForm','empAjax');$('#formulario').hide().prev('#grilla');">
                            <table height="36" border="0" cellpadding="0" cellspacing="0">
                                <tbody>
                                    <tr>
                                        <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;">
                                            <div align="right"><strong>B&uacute;squeda</strong></div>
                                        </td>
                                        <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese Empresa a buscar..." class='text clearable submit' autofocus /><input type="text" style="display:none" /></td>
                                        <td width="109" align="center">
                                            <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar Empresas">
                                                <i class="icon-search icon-white"></i>
                                                <span>Buscar</span>
                                            </button>

                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </form>
                    </FIELDSET>
                </td>
            </tr>
            <tr>
                <td colspan="2" valign="top">
                    <div id="grilla" style="width: 100%;">
                        <FIELDSET>
                            <LEGEND>
                                <label class="Titulos2">Resultados de la busqueda</label>
                            </LEGEND>

                            <table id="list"></table>
                            <div id="listPager"></div>

                        </FIELDSET>
                    </div>

                    <table id="formulario" width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
                        <tr>
                            <td>
                                <FIELDSET>
                                    <LEGEND>
                                        <label class="Titulos2">Empresa y Sucursales</label>
                                    </LEGEND>
                                    <div>
                                        <div class="segmento">Razon Social:</div>
                                        <div class="datasegmento"><input id="Emp_Nom" maxlength="50" type="text" class="text ui-widget-content ui-corner-all" readonly /></div>
                                    </div>
                                    <input type="hidden" id="Emp_Cod" value="" />
                                    <input type="hidden" id="Dat_Dis" value="" />
                                    <input type="hidden" id="Dat_Cod" value="" />
                                    <table id="sucu"></table>
                                    <div id="sucuPager"></div>
                                </FIELDSET>
                            </td>
                            <td valign="top">
                                <form id="sucForm" action="javascript:$.createDialogConfirm(null,null,saveForm)">
                                    <fieldset>
                                        <legend>
                                            <label class="Titulos2">Datos Sucursal</label>
                                        </legend>
                                        <div>
                                            <div class="segmento"><span class="Asterisco">*</span>Codigo SRI:</div>
                                            <div class="datasegmento"><input name="Suc_Sri" minlength="3" maxlength="3" type="text" class="text ui-corner-all" required autofocus style="width:45%;" /></div>
                                        </div>
                                        <div>
                                            <div class="segmento"><span class="Asterisco">*</span>Ciudad:</div>
                                            <div class="datasegmento"> <select name="Ciu_Cod" id="Ciu_Cod" class="text ui-corner-all" required style="width:47%;">
                                                    <?php
                                                    $rs_ciuds = $obBD_con1->getArrayConsulta(6, "", $obBD_conexion);
                                                    if (count($rs_ciuds) > 0) {
                                                        foreach ($rs_ciuds as $row) {
                                                    ?>
                                                            <option value="<?php echo $row['Ciu_Cod']; ?>"><?php echo $row['Ciu_Des']; ?></option>
                                                    <?php
                                                        }
                                                    }
                                                    ?>
                                                </select></div>
                                        </div>
                                        <div>
                                            <div class="segmento"><span class="Asterisco">*</span>Descripci&oacute;n:</div>
                                            <div class="datasegmento"><input name="Suc_Des" maxlength="50" type="text" class="text ui-corner-all" required /></div>
                                        </div>
                                        <div>
                                            <div class="segmento">Direcci&oacute;n:</div>
                                            <div class="datasegmento"><input name="Suc_Dir" maxlength="100" type="text" class="text ui-corner-all" /></div>
                                        </div>
                                        <div>
                                            <div class="segmento">Telefonos:</div>
                                            <div class="datasegmento"><input name="Suc_Te1" minlength="10" maxlength="12" type="text" class="text ui-corner-all" style="width:46%;" /><input name="Suc_Te2" minlength="10" maxlength="12" type="text" class="text ui-corner-all" style="width:46%;" /></div>
                                        </div>
                                        <div>
                                            <div class="segmento">Fax:</div>
                                            <div class="datasegmento"><input name="Suc_Fax" minlength="10" maxlength="12" type="text" class="text ui-corner-all" style="width:46%;" /></div>
                                        </div>
                                        <div>
                                            <div class="segmento">E-Mail:</div>
                                            <div class="datasegmento"><input name="Suc_Cor" maxlength="50" type="email" class="text ui-corner-all" /></div>
                                        </div>
                                        <div>
                                            <div class="segmento">P&aacute;gina Web:</div>
                                            <div class="datasegmento"><input name="Suc_Web" maxlength="50" type="url" class="text ui-corner-all" /></div>
                                        </div>
                                    </fieldset>
                                    <div style="padding: 10px;">
                                        <a onclick="$('#formulario').prev('#grilla');" class="btn btn-inverse fileinput-button" title="Volver Atrás"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></a><span style="width: 150px;"></span>
                                        <button type="subtmit" class="btn btn-success" title="Guardar Empresa" id="btnGuardaSuc"> <i class="icon-book icon-white"></i> <span>Guardar</span> </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    </table>
                </td>

            </tr>
        </table>

    </div>
    <script>
        function saveForm() {
            var data = $('#sucForm').serializeObject();
            data["saveSucursal"] = true;
            data["Emp_Cod"] = $("#Emp_Cod").val();
            data["Dat_Dis"] = $("#Dat_Dis").val();
            data["Dat_Cod"] = $("#Dat_Cod").val();
            //console.log(data);
            $('#btnGuardaSuc').attr('disabled', 'disabled');
            $.post("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", data, function(response) {
                    if (response['success'] === true) {
                        $.alert("Transaccion Realizada con &Eacute;xito!");
                        $('#sucForm')[0].reset();
                        $("#sucu").jqGrid().trigger("reloadGrid", [{
                            page: 1
                        }]);
                    } else {
                        $.alert(response['message']);
                    }
                }, 'json').fail(function(error) {
                    $.alert();
                })
                .always(function() {
                    $('#btnGuardaSuc').removeAttr('disabled');
                });
        }

        function selectEmp(id) {
            //alert(id);
            $('#grilla').next('#formulario');
            var dataFromRow = $("#list").jqGrid('getRowData', id);
            $("#Emp_Nom").val(dataFromRow['Emp_Nom']);
            $("#Emp_Cod").val(dataFromRow['Emp_Cod']);
            $("#Dat_Dis").val(dataFromRow['Dat_Dis']);
            $("#Dat_Cod").val(dataFromRow['Dat_Cod']);
            dataFromRow['sucuAjax'] = true;
            $("#sucu").jqGrid('setGridParam', {
                datatype: 'json',
                postData: dataFromRow
            }).trigger("reloadGrid", [{
                page: 1
            }]);
            $('#sucForm')[0].reset();
        }
        $(document).ready(function() {
            $("#list").jqGrid({
                url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                mtype: "GET",
                datatype: "json",
                regional: 'es', //ajaxRowOptions: { async: true },
                postData: $("#empForm").getData("empAjax"),
                autowidth: true,
                shrinkToFit: true,
                height: 295,
                cmTemplate: {
                    sortable: false
                },
                colModel: [{
                        label: 'Id',
                        name: 'Emp_Cod',
                        key: true,
                        width: 15,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'Id',
                        name: 'Dat_Cod',
                        hidden: true
                    },
                    {
                        label: 'Raz&oacute;n Social',
                        name: 'Emp_Nom',
                        width: 270
                    },
                    {
                        label: 'Raz&oacute;n Social Abrev.',
                        name: 'Emp_Cor',
                        width: 90,
                        align: "center"
                    },
                    {
                        label: 'DataBase',
                        name: 'Dat_Dis',
                        width: 90,
                        align: "center"
                    },
                    {
                        label: 'Estado',
                        name: 'Emp_Est',
                        width: 50,
                        align: "center"
                    },
                    {
                        label: '&nbsp;',
                        name: 'act1',
                        width: 30,
                        align: 'center',
                        viewable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            return '<span class="btn btn-success btn-mini" title="Seleccionar" type="button" onclick="selectEmp(\'' + rowObject.Emp_Cod + '\');"><i class="icon-arrow-right icon-white"></i></span>';
                        }
                    }
                ],
                rowNum: 20,
                pager: "#listPager",
                gridview: true,
                rownumbers: true,
                viewrecords: true,
                altRows: true,
                altclass: "myAltRowClass"
            });
            $('#list').navGrid('#listPager', {
                edit: false,
                add: false,
                del: false,
                search: false,
                refresh: true,
                view: true,
                position: "left",
                cloneToTop: false
            });
            $("#list").jqGrid('bindKeys');
            $("#sucu").jqGrid({
                url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                mtype: "GET",
                datatype: "local",
                regional: 'es',
                autowidth: true,
                shrinkToFit: true,
                height: 270,
                cmTemplate: {
                    sortable: false
                },
                colModel: [{
                        label: 'Id',
                        name: 'Suc_Cod',
                        key: true,
                        width: 15,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'Cod <u>SRI</u>',
                        name: 'Suc_Sri',
                        width: 50,
                        align: "center"
                    },
                    {
                        label: 'Descripci&oacute;n',
                        name: 'Suc_Des',
                        width: 90,
                        align: "center"
                    },
                    {
                        label: 'Direcci&oacute;n',
                        name: 'Suc_Dir',
                        width: 150
                    },
                    {
                        label: 'Tel&eacute;fono',
                        name: 'Suc_Te1',
                        width: 90,
                        align: "center"
                    }
                ],
                rowNum: 20,
                pager: "#sucuPager",
                gridview: true,
                rownumbers: true,
                viewrecords: true,
                altRows: true,
                altclass: "myAltRowClass"
            });
            $('#sucu').navGrid('#sucuPager', {
                edit: false,
                add: false,
                del: false,
                search: false,
                refresh: true,
                view: true,
                position: "left",
                cloneToTop: false
            });
            $("#sucu").jqGrid('bindKeys');
            $('#formulario').hide();
        });
    </script>
    <style>

    </style>


</BODY>

</HTML>