<?php

/**
 * @abstract Permite realizar el registro de claves de accesos
 * @author Sistema
 * @version 1.0
 * Fecha de creacion  2024-01-01
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../LOGICA/adm_log_clavesaccesos.php');
require_once('../../Librerias/postclass.php');

/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_ClavesAccesos($Ses_Dat_Dis);
/** 
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_ClavesAccesos;

/**
 * Obtener nombre de la empresa
 */
$empresa_nombre = '';
if (isset($Ses_Emp_Nom) && !empty($Ses_Emp_Nom)) {
    $empresa_nombre = $Ses_Emp_Nom;
} else {
    // Si no está en sesión, consultarlo de la base de datos
    $sql_empresa = "SELECT Emp_Nom FROM empresas WHERE Emp_Cod = " . intval($Ses_Emp_Cod) . " LIMIT 1";
    $rs_empresa = $obBD_con1->consulta($sql_empresa, $obBD_conexion->conexion);
    if ($row_empresa = $obBD_con1->fetch_assoc($rs_empresa)) {
        $empresa_nombre = $row_empresa['Emp_Nom'];
    }
    $obBD_con1->liberar();
}

/**
 * Evita el reenvio 
 */
$thisPost = new Post_Block;

// Guardar clave de acceso
if (isset($saveClaveAccesoAjax)) {
    $data = filter_input_array(INPUT_POST);
    $responce['success'] = false;
    $responce['message'] = "No se ha logrado realizar la Transaccion";
    
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $datos = array(
            'Emp_Cod' => intval($Ses_Emp_Cod),
            'Cla_Cod' => isset($data['Cla_Cod']) ? addslashes($data['Cla_Cod']) : '',
            'Cod_Psc' => isset($data['Cod_Psc']) ? intval($data['Cod_Psc']) : NULL,
            'Cla_Est' => isset($data['Cla_Est']) ? $data['Cla_Est'] : 'A',
            'Cla_Des' => isset($data['Cla_Des']) ? addslashes($data['Cla_Des']) : ''
        );
        
        if (!empty($data['Cod_Cla'])) {
            // Actualizar (case 2)
            $datos['Cod_Cla'] = intval($data['Cod_Cla']);
            $obBD_con1->operacionobBD(2, $datos, $obBD_conexion, true);
        } else {
            // Insertar (case 1)
            $obBD_con1->operacionobBD(1, $datos, $obBD_conexion, true);
        }
        
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        
        if ($obBD_con1->Error == 0) {
            $responce['success'] = true;
            $responce['message'] = "Transaccion Realizada con Exito!";
        } else {
            $responce['message'] = $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $responce['message'] = $e->getMessage();
    }
    
    echo json_encode($responce);
    exit();
}

// Obtener datos para edicion
if (isset($editClaveAccesoAjax)) {
    $data = filter_input_array(INPUT_POST);
    $Cod_Cla = isset($data['Cod_Cla']) ? intval($data['Cod_Cla']) : 0;
    
    if ($Cod_Cla > 0) {
        $row = $obBD_con1->getRowConsulta(4, array('Cod_Cla' => $Cod_Cla), $obBD_conexion, true);
        if (!empty($row)) {
            $responce = array('success' => true, 'rows' => $row);
        } else {
            $responce = array('success' => false, 'message' => 'No se encontraron datos para editar');
        }
    } else {
        $responce = array('success' => false, 'message' => 'Codigo de clave invalido');
    }
    
    echo json_encode($responce);
    exit();
}

// Anular clave de acceso
if (isset($anularClaveAccesoAjax)) {
    $Cod_Cla = isset($_POST['Cod_Cla']) ? intval($_POST['Cod_Cla']) : 0;
    $responce['success'] = false;
    $responce['message'] = "No se ha logrado realizar la Transaccion";
    
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $datos = array('Cod_Cla' => $Cod_Cla);
        // Case 6 para anular
        $obBD_con1->operacionobBD(6, $datos, $obBD_conexion, true);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        
        if ($obBD_con1->Error == 0) {
            $responce['success'] = true;
            $responce['message'] = "Clave anulada correctamente!";
        } else {
            $responce['message'] = $obBD_con1->MsgError;
        }
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $responce['message'] = $e->getMessage();
    }
    
    echo json_encode($responce);
    exit();
}

// Listar claves de acceso (para jqGrid)
if (isset($listClavesAccesoAjax)) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rows = isset($_GET['rows']) ? intval($_GET['rows']) : 20;
    $sidx = isset($_GET['sidx']) ? $_GET['sidx'] : 'Cod_Cla';
    $sord = isset($_GET['sord']) ? $_GET['sord'] : 'DESC';
    
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Contar total (case 7)
    $totalParams = array('search' => $search);
    $total = $obBD_con1->getRowConsulta(7, $totalParams, $obBD_conexion, true);
    $totalRecords = isset($total['total']) ? intval($total['total']) : 0;
    $totalPages = ceil($totalRecords / $rows);
    
    // Obtener registros (case 3)
    $limit = ($page - 1) * $rows;
    $params = array(
        'search' => $search,
        'order' => $sidx . ' ' . $sord,
        'limits' => "LIMIT $limit, $rows"
    );
    $datos = $obBD_con1->getArrayConsulta(3, $params, $obBD_conexion, true);
    
    $responce = array(
        'page' => $page,
        'total' => $totalPages,
        'records' => $totalRecords,
        'rows' => $datos ? $datos : array()
    );
    
    echo json_encode($responce);
    exit();
}

// Obtener codigo aleatorio
if (isset($getSiguienteNumeroAjax)) {
    // Generar numero aleatorio de 10 digitos, cada digito del 1 al 9
    $numeroAleatorio = '';
    for ($i = 0; $i < 10; $i++) {
        $numeroAleatorio .= rand(1, 9); // Numeros aleatorios del 1 al 9 (sin 0)
    }
    
    // Verificar que no exista ya en la base de datos para esta empresa
    $existe = true;
    $intentos = 0;
    $maxIntentos = 100; // Limitar intentos para evitar bucle infinito
    
    while ($existe && $intentos < $maxIntentos) {
        $verificar = $obBD_con1->getRowConsulta(4, array('Cla_Cod' => $numeroAleatorio), $obBD_conexion, true);
        if (empty($verificar)) {
            $existe = false;
        } else {
            // Si existe, generar otro numero aleatorio
            $numeroAleatorio = '';
            for ($i = 0; $i < 10; $i++) {
                $numeroAleatorio .= rand(1, 9);
            }
        }
        $intentos++;
    }
    
    $responce = array('success' => true, 'numero' => $numeroAleatorio);
    echo json_encode($responce);
    exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>

<HEAD>
    <TITLE>Administrar Claves de Accesos [EXA]</TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid.php") ?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>
        .input-group-btn .btn {
            height: 34px;
        }
    </style>
</HEAD>

<BODY>
    <div id="set1">
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table" style="table-layout:fixed;">
            <tr class="BarraTitulo">
                <td colspan="2" height="10">&raquo; Administrar Claves de Accesos</td>
            </tr>
            <tr>
                <td width="70%" valign="top">
                    <FIELDSET>
                        <LEGEND>
                            <label class="Titulos2">Lista de Claves de Accesos</label>
                        </LEGEND>
                        <table id="list"></table>
                        <div id="listPager"></div>
                    </FIELDSET>
                </td>
                <td width="30%" valign="top">
                    <form id="claveAccesoForm" action="javascript:$.createDialogConfirm('Esta seguro que desea guardar los datos?', null, saveClaveAcceso);">
                        <fieldset>
                            <legend>
                                <label class="Titulos2">Datos Clave de Acceso</label>
                            </legend>
                            <input type="hidden" name="Cod_Cla" id="Cod_Cla" value="" />
                            <input type="hidden" name="Emp_Cod" id="Emp_Cod" value="<?php echo intval($Ses_Emp_Cod); ?>" />
                            
                            <div>
                                <div class="segmento"><span class="Asterisco">*</span>Empresa:</div>
                                <div class="datasegmento">
                                    <input id="Emp_Cod_Display" type="text" class="text ui-corner-all" 
                                           value="<?php echo htmlspecialchars($empresa_nombre . ' - ' . intval($Ses_Emp_Cod), ENT_QUOTES, 'UTF-8'); ?>" readonly />
                                </div>
                            </div>
                            
                            <div>
                                <div class="segmento"><span class="Asterisco">*</span>Codigo Clave:</div>
                                <div class="datasegmento">
                                    <div class="input-group">
                                        <input name="Cla_Cod" id="Cla_Cod" maxlength="10" type="text" 
                                               class="text ui-corner-all" required 
                                               placeholder="10 digitos" style="width:70%;" />
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-info" id="btnGenerarCodigo" 
                                                    title="Generar numero secuencial" onclick="generarNumeroSecuencial();">
                                                <i class="icon-refresh icon-white"></i> Generar
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="segmento"><span class="Asterisco">*</span>Codigo Proceso:</div>
                                <div class="datasegmento">
                                    <select name="Cod_Psc" id="Cod_Psc" class="text ui-corner-all" required>
                                        <option value="">-- Seleccione --</option>
                                        <option value="1">Editar forma de pago de facturas autorizadas</option>
                                        <option value="2">Permitir generar facturas a partir de manifiestos (Relavera)</option>
                                        <!-- Agregue más opciones según los procesos disponibles -->
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <div class="segmento"><span class="Asterisco">*</span>Estado:</div>
                                <div class="datasegmento">
                                    <select name="Cla_Est" id="Cla_Est" class="text ui-corner-all" required>
                                        <option value="A">Activo</option>
                                        <option value="I">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <div class="segmento"><span class="Asterisco">*</span>Descripcion:</div>
                                <div class="datasegmento">
                                    <textarea name="Cla_Des" id="Cla_Des" maxlength="100" 
                                              class="text ui-corner-all" required rows="3" 
                                              placeholder="Descripcion de la clave"></textarea>
                                </div>
                            </div>
                        </fieldset>
                        <div style="padding: 10px;">
                            <button type="button" onclick="limpiarFormulario();" class="btn btn-inverse fileinput-button" 
                                    title="Limpiar Formulario">
                                <i class="icon-remove icon-white"></i><span>&nbsp;&nbsp;Limpiar&nbsp;&nbsp;</span>
                            </button>
                            <button type="submit" class="btn btn-success" title="Guardar Clave" id="btnGuardarClave">
                                <i class="icon-book icon-white"></i> <span>Guardar</span>
                            </button>
                        </div>
                    </form>
                </td>
            </tr>
        </table>
    </div>
    
    <script type="text/javascript">
        function generarNumeroSecuencial() {
            $('#btnGenerarCodigo').attr('disabled', 'disabled').html('<i class="icon-refresh"></i> Generando...');
            $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", 
                { getSiguienteNumeroAjax: true }, 
                function(response) {
                    if (response && response.success && response.numero) {
                        $('#Cla_Cod').val(response.numero);
                    } else {
                        $.alert('No se pudo generar el numero secuencial');
                    }
                }, 'json')
                .fail(function() {
                    $.alert('Error al generar numero secuencial');
                })
                .always(function() {
                    $('#btnGenerarCodigo').removeAttr('disabled').html('<i class="icon-refresh icon-white"></i> Generar');
                });
        }
        
        function saveClaveAcceso() {
            var data = $('#claveAccesoForm').serializeObject();
            data["saveClaveAccesoAjax"] = true;
            
            $('#btnGuardarClave').attr('disabled', 'disabled');
            $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", data, 
                function(response) {
                    if (response['success'] === true) {
                        $.alert("Transaccion Realizada con Exito!");
                        limpiarFormulario();
                        $("#list").jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                    } else {
                        $.alert(response['message']);
                    }
                }, 'json')
                .fail(function(error) {
                    $.alert('Error al guardar los datos');
                })
                .always(function() {
                    $('#btnGuardarClave').removeAttr('disabled');
                });
        }
        
        function limpiarFormulario() {
            $('#claveAccesoForm')[0].reset();
            $('#Cod_Cla').val('');
            $('#Emp_Cod').val('<?php echo intval($Ses_Emp_Cod); ?>');
            $('#Emp_Cod_Display').val('<?php echo htmlspecialchars($empresa_nombre . ' - ' . intval($Ses_Emp_Cod), ENT_QUOTES, 'UTF-8'); ?>');
            $('#Cla_Est').val('A');
        }
        
        function editarClaveAcceso(Cod_Cla) {
            $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", 
                { editClaveAccesoAjax: true, Cod_Cla: Cod_Cla }, 
                function(response) {
                    if (response && response.success && response.rows) {
                        var row = response.rows;
                        $('#Cod_Cla').val(row.Cod_Cla || '');
                        $('#Cla_Cod').val(row.Cla_Cod || '');
                        $('#Cod_Psc').val(row.Cod_Psc || '');
                        $('#Cla_Est').val(row.Cla_Est || 'A');
                        $('#Cla_Des').val(row.Cla_Des || '');
                        $('#Emp_Cod').val(row.Emp_Cod || '<?php echo intval($Ses_Emp_Cod); ?>');
                        $('#Emp_Cod_Display').val('<?php echo htmlspecialchars($empresa_nombre . ' - ' . intval($Ses_Emp_Cod), ENT_QUOTES, 'UTF-8'); ?>');
                    } else {
                        $.alert('No se pudieron cargar los datos');
                    }
                }, 'json')
                .fail(function() {
                    $.alert('Error al cargar los datos');
                });
        }
        
        function anularClaveAcceso(Cod_Cla) {
            $.createDialogConfirm('Esta seguro que desea anular esta clave de acceso?', 
                { anularClaveAccesoAjax: true, Cod_Cla: Cod_Cla },
                function(data) {
                    $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", data, 
                        function(response) {
                            if (response['success'] === true) {
                                $.alert("Clave anulada correctamente!");
                                $("#list").jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                            } else {
                                $.alert(response['message']);
                            }
                        }, 'json')
                        .fail(function() {
                            $.alert('Error al anular la clave');
                        });
                });
        }
        
        $(document).ready(function() {
            $("#list").jqGrid({
                url: '<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                mtype: "GET",
                datatype: "json",
                regional: 'es',
                postData: { listClavesAccesoAjax: true },
                autowidth: true,
                shrinkToFit: true,
                height: 400,
                cmTemplate: { sortable: false },
                colModel: [
                    {
                        label: 'ID',
                        name: 'Cod_Cla',
                        key: true,
                        width: 50,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'Codigo Clave',
                        name: 'Cla_Cod',
                        width: 120,
                        align: "center"
                    },
                    {
                        label: 'Cod. Proceso',
                        name: 'Cod_Psc',
                        width: 100,
                        align: "center"
                    },
                    {
                        label: 'Descripcion',
                        name: 'Cla_Des',
                        width: 250
                    },
                    {
                        label: 'Estado',
                        name: 'Cla_Est',
                        width: 80,
                        align: "center",
                        formatter: function(cellvalue, options, rowObject) {
                            if (cellvalue === 'A') {
                                return '<span class="badge badge-success">Activo</span>';
                            } else {
                                return '<span class="badge badge-important">Inactivo</span>';
                            }
                        }
                    },
                    {
                        label: 'Acciones',
                        name: 'act',
                        width: 120,
                        align: 'center',
                        sortable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            var botones = '';
                            if (rowObject.Cla_Est === 'A') {
                                botones += '<span class="btn btn-success btn-mini" title="Editar" ' +
                                    'onclick="editarClaveAcceso(' + rowObject.Cod_Cla + ');">' +
                                    '<i class="icon-edit icon-white"></i></span>&nbsp;';
                                botones += '<span class="btn btn-warning btn-mini" title="Anular" ' +
                                    'onclick="anularClaveAcceso(' + rowObject.Cod_Cla + ');">' +
                                    '<i class="icon-ban-circle icon-white"></i></span>';
                            } else {
                                botones += '<span class="btn btn-success btn-mini" title="Editar" ' +
                                    'onclick="editarClaveAcceso(' + rowObject.Cod_Cla + ');">' +
                                    '<i class="icon-edit icon-white"></i></span>';
                            }
                            return botones;
                        }
                    }
                ],
                pager: '#listPager',
                rowNum: 20,
                rowList: [10, 20, 30, 50],
                sortname: 'Cod_Cla',
                sortorder: 'desc',
                viewrecords: true,
                gridview: true,
                caption: "Claves de Accesos",
                jsonReader: {
                    root: "rows",
                    page: "page",
                    total: "total",
                    records: "records",
                    repeatitems: false,
                    id: "Cod_Cla"
                }
            });
            
            $("#list").jqGrid('navGrid', '#listPager', {
                edit: false,
                add: false,
                del: false,
                search: true,
                refresh: true
            });
        });
    </script>
</BODY>
</HTML>

