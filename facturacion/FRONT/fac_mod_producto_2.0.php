<?php

/**
 * @abstract Permite realizar la modificacion de productos
 * @author Erick Cordova
 * @version 1.0
 * Fecha de creacion  2017-11-21
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_producto_mod.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
//require_once('../../Librerias/postclass.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);
/**
 * Creacion del Objeto para consultas
 */
$obBD_con1 = new Class_Log_Datos_Pro;

function reverse_number($number)
{
    /* Convert them into an array. */
    $arr = str_split($number);
    /* Reverse the array. */
    $rev_arr = array_reverse($arr);
    /* Implode them. */
    $rev = implode("", $rev_arr);
    return $rev;
}

if (isset($prodAjax)) {
    // $obBD_con1->debug(true);
    $data = $_GET;
    $data['Suc_Cod'] = $Ses_Suc_Cod;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    if (!isset($data['Filtros'])) $data['Filtros'] = '';


    $data['Order'] = " ORDER BY " . ($grupo == 'clear' ? 'Ite_Lar' : $grupo);
    if ($letra == 'TODOS') {
        $search = "";
        $array = explode(" ", strtoupper($txt_busqueda));
        foreach ($array as $ar) {
            if (!empty($ar) && $ar != '') $search .= (($search != '' ? " AND " : "") . "CAST(UPPER(CONCAT(Ite_Lar,Pro_Obs)) AS CHAR)LIKE '%$ar%'");
        }
        if ($search == '') $search = "1=1";

        $data['Filtros'] = $data['Filtros'] . " sucursal.Suc_Cod=" . $Ses_Suc_Cod . " AND " . $search;
    } else {
        $data['Filtros'] = "Ite_Lar LIKE '$letra%' ";
    }
    if ($Ubi_Cod != '') {
        $data['Filtros'] = $data['Filtros'] . " AND sucursal.Suc_Cod=" . $Ses_Suc_Cod . " AND producto.Ubi_Cod=$Ubi_Cod ";
    }
    if ($Lin_Cod != '') {
        $data['Filtros'] = $data['Filtros'] . " AND sucursal.Suc_Cod=" . $Ses_Suc_Cod . " AND producto.Lin_Cod=$Lin_Cod ";
    } //else{
    //$data['Filtros']=$data['Filtros']." AND producto.Lin_Cod IS NULL ";
    // }
    if ($Cat_Cod != '') {
        $data['Filtros'] = $data['Filtros'] . " AND sucursal.Suc_Cod=" . $Ses_Suc_Cod . " AND item.Cat_Cod=$Cat_Cod ";
    }
    $data['Filtros'] = $data['Filtros'] . " AND sucursal.Suc_Cod=" . $Ses_Suc_Cod;
    $datos = $obBD_con1->getArrayConsulta(18, $data, $obBD_conexion);
    $pagination = pages(count($datos), $page, $rows);
    $responce = $pagination['data'];
    $responce['rows'] = $datos;
    $responce['success'] = true;
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

if (isset($validaIteLar)) {
    // $obBD_con1->debug(true);
    $conteo = $obBD_con1->getRowConsulta(26, $Ses_Emp_Cod . '*' . trim($Ite_Lar) . '*' . $Pro_Cod, $obBD_conexion);
    $resp = array('success' => '');
    if ($conteo['total'] * 1 > 0) {
        $resp = array('success' => false, 'state' => 'warning', 'message' => "Ya existe un producto con el nombre \"$Ite_Lar\".");
    } else {
        $resp = array('success' => true);
    }
    echo json_encode($resp);
    exit();
}

if (isset($validaCodEmp)) {
    $tot = $obBD_con1->getRowConsulta(266, $Ses_Emp_Cod . '*' . trim($Pro_Cod_Emp), $obBD_conexion);
    $resp = array('success' => '');
    if ($tot['total'] * 1 > 0) {
        $validaCodigo = 0;
        $resp = array('success' => false, 'state' => 'warning', 'message' => "Ya existe un producto con el codigo \"$Pro_Cod_Emp\".");
    }
    echo json_encode($resp);
    exit();
}

if (isset($nameSave)) {
    $resp = $_POST;
    $resp['Emp_Cod'] = $Ses_Emp_Cod;
    $sql = 0;
    switch ($select) {
        case 'Lin':
            $sql = 20;
            break;
        case 'Mar':
            $sql = 21;
            break;
        case 'Ubi':
            $sql = 22;
            break;
        case 'Cat':
            $sql = 23;
            $resp['Cat_Tip'] = 'D';
            $siguiente = $obBD_con1->getRowConsulta(24, $resp['Cat_Rec'], $obBD_conexion);
            $resp['Cat_Cdc'] = $resp['Rec_Cdc'] . '.' . $siguiente['next'];
            break;
    }
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD($sql, $resp, $obBD_conexion);
    $resp[$resp['select'] . '_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $resp['success'] = true;
    } else {
        $resp = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_con1->MsgError);
    }
    utf8_encode_deep($resp);
    echo json_encode($resp);
    exit();
}

if (isset($updateProd)) {
    /*Inicio de Transaccion*/
    //$obBD_con1->debug(true);   
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $data = $_POST;
        $obBD_con1->echoLog($data);

        if ($Pro_Gen == 'G') {
            $Pro_Cod = $data['Pro_Cod'];
            $numRever = reverse_number($Pro_Cod);
            if ($numRever <= 1) {
                $valPuro = str_pad($Pro_Cod, 12, "0");
                $genNum = mt_rand(1, 19);
                $newNumGen = $valPuro + $genNum;
                $data['Pro_Bar'] = $newNumGen;
            } else {
                $valPuro = str_pad($data['Pro_Cod'], 12, "0");
                $data['Pro_Bar'] = $valPuro;
            }
        }

        if ($Pro_Gen_Emp == 'G') {
            $Pro_Cod = $data['Pro_Cod'];
            $numRever = reverse_number($Pro_Cod);
            if ($numRever <= 1) {
                $valPuro = str_pad($Pro_Cod, 12, "0");
                $genNum = mt_rand(1, 19);
                $newNumGen = $valPuro + $genNum;
                $data['Pro_Bar_Emp'] = $newNumGen;
            } else {
                $valPuro = str_pad($data['Pro_Cod'], 12, "0");
                $data['Pro_Bar_Emp'] = $valPuro;
            }
        }

        $obBD_con1->echoLog($Pro_Gen);
        $update_cont = $obBD_con1->operacionobBD(27, $data, $obBD_conexion);
        $update_cont = $obBD_con1->operacionobBD(28, $data, $obBD_conexion);
        $update_cont = $obBD_con1->operacionobBD(29, $data, $obBD_conexion);
        $response['success'] = true;
        $response['message'] = 'Producto actualizado con &Eacutexito ';
    } catch (Exception $ex) {
        $response['success'] = false;
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $response['message'] = $ex->getMessage();
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($response);
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Producto Modificar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script src="../VALIDACIONES/fac_mod_productos.js?e=2"></script>
    <style>
        .pagination>li>a,
        .pagination>li>span {
            padding: 4px 2px;
        }

        .pagination {
            /*display: block;*/
            margin: 0;
            padding: 0;
        }

        .chosen-default span,
        .chosen-single span {
            color: #555;
        }

        .chosen-single span {
            padding-left: 5px;
        }
    </style>
</HEAD>

<BODY>

    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Modificar Productos</h3>
        </div>

        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id='search_producto'>
                <form id="formProduct" class="form-horizontal normal" action="javascript:">
                    <div class="row">
                        <div class="col-sm-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-sm " for="Cop_Fec">Buscar:</label>
                                    <div class="col-sm-5">
                                        <input type="text" name="txt_busqueda" id="txt_busqueda" class="form-control input-sm text clearable">
                                    </div>
                                    <div class="col-sm-3">
                                        <button class="btn btn-sm btn-success" onclick="loadData();">
                                            <i class="glyphicon glyphicon-search"></i>&nbsp;&nbsp;&nbsp;Buscar
                                        </button>
                                    </div>
                                </div>
                            </fieldset>
                            <!-- Text input-->
                            <div class="form-group">
                                <div class="col-sm-12 center">
                                    <input type="hidden" id="letra" name="letra" value="TODOS" />
                                    <nav>
                                        <?php $Letras = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "Ñ", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "TODOS"); ?>
                                        <ul class="pagination pagination-centered">
                                            <?php foreach ($Letras as $letra) { ?>
                                                <li <?php if ($letra == 'TODOS') echo 'class="active"'; ?>><a><?php echo $letra; ?></a></li>
                                            <?php } ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs " for="Cat_Cod">Categor&iacute;a:</label>
                                    <div class="col-sm-9">
                                        <?php $row_rs_categ = $obBD_con1->getArrayConsulta(44, $Ses_Emp_Cod, $obBD_conexion); ?>
                                        <select name="Cat_Cod" id="Cat_Cod" class="form-control input-xs" data-placeholder="Todas">
                                            <option value=""></option>
                                            <?Php
                                            foreach ($row_rs_categ as $row) {
                                            ?>
                                                <option value="<?Php echo $row['Cat_Cod']; ?>"><?Php echo $row['Cat_Des']; ?></option>
                                            <?Php }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs " for="Ubi_Cod">Ubicaci&oacute;n:</label>
                                    <div class="col-sm-3">
                                        <?php $rs_ubicacion = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion); ?>
                                        <select name="Ubi_Cod" id="Ubi_Cod" class="form-control input-xs">
                                            <option value="">Todas</option>
                                            <?Php
                                            foreach ($rs_ubicacion as $row) {
                                            ?>
                                                <option value="<?Php echo $row['Ubi_Cod']; ?>"><?Php echo $row['Ubi_Des']; ?></option>
                                            <?Php }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs " for="Lin_Cod">L&iacute;nea:</label>
                                    <div class="col-sm-3">
                                        <?php $rs_linea = $obBD_con1->getArrayConsulta(15, $Ses_Emp_Cod, $obBD_conexion); ?>
                                        <select name="Lin_Cod" id="Lin_Cod" class="form-control input-xs">
                                            <option value="">Todas</option>
                                            <?Php
                                            foreach ($rs_linea as $row) {
                                            ?>
                                                <option value="<?Php echo $row['Lin_Cod']; ?>"><?Php echo $row['Lin_Des']; ?></option>
                                            <?Php }
                                            ?>
                                        </select>
                                    </div>
                                    <label class="col-sm-3 control-label label-xs " for="Lin_Cod">Agrupar por :</label>
                                    <div class="col-sm-3">
                                        <select name="grupo" id="grupo" class="form-control input-xs">
                                            <option value="clear">No Agrupar</option>
                                            <option value="Cat_Des">Categoria</option>
                                            <option value="Ubi_Des">Bodega</option>
                                            <option value="Lin_Des">Linea</option>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-sm-12" style="min-height: 270px">
                            <table id="grid"></table>
                            <div id="gridPager"></div>
                        </div>
                    </div>

                </form>
            </div>
            <div id='modif_producto' style="visibility: hidden;">
                <form id="formProductMod" class="form-horizontal normal">
                    <div class="row">
                        <div class="col-sm-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos Generales:</legend> <!-- Form Name -->
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Cat_Cod">Categor&iacute;a:</label>
                                    <div class="col-sm-9">
                                        <div class="input-group input-group-sm">
                                            <?php $row_rs_categ = $obBD_con1->getArrayConsulta(43, $Ses_Emp_Cod, $obBD_conexion); ?>
                                            <select name="Cat_Cod" id="Cat_Cod" class="form-control" data-placeholder="Selecciona una categoria...">
                                                <option value=""></option>
                                                <?Php foreach ($row_rs_categ as $row) {
                                                    echo "<option value='$row[Cat_Cod]'>$row[Cat_Des]</option>";
                                                } ?>
                                            </select>
                                            <span class="input-group-btn"><button onclick="$('#categoForm').setData({}); $('#Cat_Rec').trigger('chosen:updated'); $('#categoDialog').dialog('open');" class="btn btn-success" type="button"><i class="glyphicon glyphicon-plus"></i></button></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Ite_Lar">Descripci&oacute;n Larga:</label>
                                    <div class="col-sm-9">
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="Ite_Lar" name="Ite_Lar" class="form-control input-sm text" placeholder="Nombre del Producto" required="" maxlength="250" />
                                            <span class="input-group-addon validate"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Ite_Cor">Descripci&oacute;n Corta:</label>
                                    <div class="col-sm-4">
                                        <input type="text" id="Ite_Cor" name="Ite_Cor" class="form-control input-sm text" placeholder="Abre. del Nombre" required="" maxlength="50" />
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Mar_Cod">Marca:</label>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <?php $rs_marca = $obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod, $obBD_conexion);     ?>
                                            <select name="Mar_Cod" id="Mar_Cod" class="form-control" required>
                                                <?Php foreach ($rs_marca as $row) {
                                                    echo "<option value='$row[Mar_Cod]' " . (strtoupper($row['Mar_Des']) == 'NINGUNA' ? 'selected' : '') . ">$row[Mar_Des]</option>";
                                                } ?>
                                            </select>
                                            <span class="input-group-btn"><button onclick="$('#marcaForm').setData({}); $('#marcaDialog').dialog('open');" class="btn btn-success" type="button"><i class="glyphicon glyphicon-plus"></i></button></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Adq_Cod">Adquisici&oacute;n:</label>
                                    <div class="col-sm-4">
                                        <?php $rs_adq = $obBD_con1->getArrayConsulta(3, $Ses_Emp_Cod, $obBD_conexion); ?>
                                        <select name="Adq_Cod" id="Adq_Cod" class="form-control input-sm" required>
                                            <option value="">Seleccione..</option>
                                            <?Php foreach ($rs_adq as $row) {
                                                echo "<option value='$row[Adq_Cod]'>$row[Adq_Des]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Iva_Cod">I.V.A.:</label>
                                    <div class="col-sm-3">
                                        <?php $row_rs_iva = $obBD_con1->getArrayConsulta(4, $Ses_Emp_Cod, $obBD_conexion); ?>
                                        <select name="Iva_Cod" id="Iva_Cod" onchange="changeIva()" class="form-control input-sm" required>
                                            <?php foreach ($row_rs_iva as $row) {
                                                if ($row['Iva_Sri'] * 1 == 6) {
                                                    echo "<option data-iva_sri={$row['Iva_Sri']} value='{$row['Iva_Cod']}'>No Objeto IVA</option>";
                                                } else {
                                                    echo "<option data-iva_sri={$row['Iva_Sri']} value='{$row['Iva_Cod']}'>{$row['Iva_Por']}%</option>";
                                                }
                                            } ?>
                                        </select>
                                    </div>
                                    <label class="col-sm-1 control-label label-sm" for="Ice_Cod">I.C.E.:</label>
                                    <div class="col-sm-5">
                                        <?php $row_rs_ice = $obBD_con1->getArrayConsulta(25, '', $obBD_conexion); ?>
                                        <select name="Ice_Int" id="Ice_Int" class="form-control input-sm">
                                            <option value="">NINGUNO</option>
                                            <?Php foreach ($row_rs_ice as $row) {
                                                echo "<option value='$row[Ice_Int]' >$row[Ice_Por]% - $row[Ice_Des]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        <div class="col-sm-6">

                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Producto:</legend> <!-- Form Name -->
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Pro_Obs">Detalle Producto:</label>
                                    <div class="col-sm-9">
                                        <input id="Pro_Obs" name="Pro_Obs" class="form-control input-sm text" placeholder="Detalle de la Presentaci&oacute;n" required="">

                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs required" for="Pro_Bar">C&oacute;digo de barra:</label>
                                    <div class="col-sm-3">
                                        <input id="Pro_Bar" name="Pro_Bar" type="text" class="form-control input-xs text center" disabled="disabled">
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="checkbox checkbox-xs check-big">
                                            <label class="label-xs"><input type="checkbox" name="Pro_Gen" id="Pro_Gen" checked="" value="1"><span id='contenedorcheck'>Generar c&oacute;digo automaticamente</span></label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs required" for="Pro_Bar_Emp">Código de empresa:</label>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <input id="Pro_Bar_Emp" name="Pro_Bar_Emp" type="text" class="form-control input-xs text center" disabled="disabled" onchange="validaCodigo($(this))">
                                            <span class="input-group-addon validate"><i></i></span>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="checkbox checkbox-xs check-big">
                                            <label class="label-xs"><input type="checkbox" name="Pro_Gen_Emp" id="Pro_Gen_Emp" checked="" value="1"><span id='contenedorcheckempresa'>Genera el codigo de empresa del producto</span></label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nuevo campo para colocar el codigo de las herramientas de construcción  -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs required" for="Pro_Cod_Const">Código Adicional:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group input-group-sm">
                                            <select name="Cod_Const" id="Cod_Const" class="form-control">
                                                <option value="">-- NO APLICA --</option>
                                                <optgroup label="-- CÓDIGOS MATERIALES DE CONSTRUCCIÓN --"></optgroup>
                                                <?php $material = $obBD_con1->getArrayConsulta(434, "", $obBD_conexion);
                                                foreach ($material as $row) { ?>
                                                    <?php if ($row['Cod_Const'] == 'H492001') {
                                                        echo " <optgroup label='-- CÓDIGOS DE TRANSPORTE --'></optgroup>";
                                                    }  ?>
                                                    <option value="<?php echo $row['Cod_Const']; ?>"><?php echo $row['Cod_Const'] . ' - ' . $row['Desc_Const']; ?></option>
                                                <?php } ?>
                                            </select>
                                            <!--span class="input-group-btn">
                                                <button onclick="$('#materialForm').setData({}); $('#materialDialog').dialog('open');" class="btn btn-success" type="button">
                                                    <i class="glyphicon glyphicon-plus"></i>
                                                </button>
                                            </span-->
                                        </div>
                                    </div>
                                </div>

                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Ubi_Cod">Ubicaci&oacute;n:</label>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <?php $rs_ubicacion = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion);         ?>
                                            <select name="Ubi_Cod" id="Ubi_Cod" class="form-control input-sm" required>
                                                <?Php foreach ($rs_ubicacion as $row) {
                                                    echo "<option value='$row[Ubi_Cod]' >$row[Ubi_Des]</option>";
                                                } ?>
                                            </select>
                                            <span class="input-group-btn"><button onclick="$('#ubicaForm').setData({}); $('#ubicaDialog').dialog('open');" class="btn btn-success" type="button"><i class="glyphicon glyphicon-plus"></i></button></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Lin_Cod">L&iacute;nea:</label>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <?php $rs_linea = $obBD_con1->getArrayConsulta(15, $Ses_Emp_Cod, $obBD_conexion);    ?>
                                            <select name="Lin_Cod" id="Lin_Cod" class="form-control input-sm" required>
                                                <option value="NULL">NINGUNA</option>
                                                <?Php foreach ($rs_linea as $row) {
                                                    echo "<option value='$row[Lin_Cod]'>$row[Lin_Des]</option>";
                                                } ?>
                                            </select>
                                            <span class="input-group-btn"><button onclick="$('#lineaForm').setData({}); $('#lineaDialog').dialog('open');" class="btn btn-success" type="button"><i class="glyphicon glyphicon-plus"></i></button></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Pre_Cod">Presentaci&oacute;n:</label>
                                    <div class="col-sm-3">
                                        <?php $rs_presen = $obBD_con1->getArrayConsulta(7, $Ses_Emp_Cod, $obBD_conexion);         ?>
                                        <select name="Pre_Cod" id="Pre_Cod" class="form-control input-sm" required>
                                            <?Php foreach ($rs_presen as $row) {
                                                echo "<option value='$row[Pre_Cod]'>$row[Pre_Des]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Uni_Cod">Unidad:</label>
                                    <div class="col-sm-3">
                                        <?php $rs_unidad = $obBD_con1->getArrayConsulta(6, $Ses_Emp_Cod, $obBD_conexion);         ?>
                                        <select name="Uni_Cod" id="Uni_Cod" class="form-control input-sm" onchange="if (this.value===1){ document.getElementById('Pro_Uni').readOnly=true; document.getElementById('Pro_Uni').value = '1';  }else{ document.getElementById('Pro_Uni').readOnly =false;document.getElementById('Pro_Uni').value = '1'; }" required>
                                            <?Php
                                            foreach ($rs_unidad as $row) { ?>
                                                <option value="<?Php echo $row['Uni_Cod']; ?>"><?Php echo $row['Uni_Des']; ?></option>
                                            <?Php
                                            } ?>
                                        </select>
                                    </div>
                                    <label class="col-sm-2 control-label label-sm required" for="Pro_Uni">Medida:</label>
                                    <div class="col-sm-2">
                                        <input type="text" id="Pro_Uni" name="Pro_Uni" class="form-control input-sm text center" value="1" onchange="if (document.getElementById('Uni_Cod').value!==1 && this.value<1){ $.alert ('Ingresar valores mayores a uno! ');document.getElementById('Pro_Uni').value = '1';this.focus(); } " onkeypress="return validar_decimal(event)" readonly="">
                                    </div>
                                </div>

                            </fieldset>
                        </div>
                        <div class="col-sm-6 col-sm-offset-6">
                            <fieldset class="exa-fieldset">
                                <input type="text" name="precio_cod" class="hidden" />
                                <legend class="Titulos2">Precio:</legend> <!-- Form Name -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Pre_Pvp">Precio Unitario:</label>
                                    <div class="col-sm-3">
                                        <input type="text" id="Pre_Pvp" name="Pre_Pvp" class="form-control input-sm text center" onchange="updateUnitario(this.value)" onkeypress="return validar_decimal(event)" style="text-align: right" required>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="PreNet">Precio Incl. IVA:</label>
                                    <div class="col-sm-3">
                                        <input type="text" id="PreNet" name="PreNet" class="form-control input-sm text" style="text-align: right" onchange="updateNeto(this.value)" onkeypress="return validar_decimal(event)" readonly="readonly">
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="checkbox checkbox-sm check-big">
                                            <label class="label-xs"><input type="checkbox" name="ChkNet" id="ChkNet" onchange="changeUnitario()" value="1">Desglosar Iva</label>
                                        </div>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm required" for="Cop_Fec">Descuento:</label>
                                    <div class="col-sm-3">
                                        <input type="text" name="Pro_Dsc" id="Pro_Dsc" class="form-control input-sm text" onKeyPress="return validar_decimal(event)" value="0" size="8" maxlength="8" style="text-align: right">
                                    </div>
                                </div>

                            </fieldset>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <div class="col-sm-12 center">
                                    <button id="btn_atras" class="btn btn-primary btn-form black"><span class="fa fa-long-arrow-left"></span> Atr&aacute;s </button>
                                    <button type="submit" class="btn btn-primary btn-form"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                    <!--  <button type="reset" onclick=""  class="btn btn-danger btn-form"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>                                -->
                                </div>
                            </div>
                            <div class="form-group Titulos2">
                                <div class="col-sm-12">
                                    <hr /><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div id="marcaDialog" title="Agregar Marca">
                <div class="row">
                    <div class="col-md-12">
                        <form id="marcaForm" class="form-horizontal normal" action="javascript:$.createDialogConfirm(null,{nameSave:'marca',select:'Mar'},saveForm)">
                            <div class="form-group">
                                <label class="col-sm-4 control-label label-sm required">Nombre Marca:</label>
                                <div class="col-sm-8"><input type="text" class="form-control input-sm" name="Mar_Des" value="" required /></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div id="lineaDialog" title="Agregar Linea">
                <div class="row">
                    <div class="col-md-12">
                        <form id="lineaForm" class="form-horizontal normal" action="javascript:$.createDialogConfirm(null,{nameSave:'linea',select:'Lin'},saveForm)">
                            <div class="form-group">
                                <label class="col-sm-4 control-label label-sm required">Nombre Linea:</label>
                                <div class="col-sm-8"><input type="text" class="form-control input-sm" name="Lin_Des" value="" required /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label label-sm required">Abreviatura:</label>
                                <div class="col-sm-4"><input type="text" class="form-control input-sm" name="Lin_Abr" value="" maxlength="5" required /></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div id="ubicaDialog" title="Agregar Ubicaci&oacute;n">
                <div class="row">
                    <div class="col-md-12">
                        <form id="ubicaForm" class="form-horizontal normal" action="javascript:$.createDialogConfirm(null,{nameSave:'ubica',select:'Ubi'},saveForm)">
                            <div class="form-group">
                                <label class="col-sm-4 control-label label-sm required">Nombre Ubicaci&oacute;n:</label>
                                <div class="col-sm-8"><input type="text" class="form-control input-sm" name="Ubi_Des" value="" required /></div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label label-sm">Descripci&oacute;n:</label>
                                <div class="col-sm-8"><textarea class="form-control input-sm" name="Ubi_Obs"></textarea></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div id="categoDialog" title="Agregar Categoria">
                <div class="row">
                    <div class="col-md-12">
                        <form id="categoForm" class="form-horizontal normal" action="javascript:$.createDialogConfirm(null,{nameSave:'catego',select:'Cat'},saveForm)">
                            <div class="form-group">
                                <label class="col-sm-4 control-label label-sm required">Categoria Padre:</label>
                                <div class="col-sm-8">
                                    <input type="text" name='Rec_Cdc' id='Rec_Cdc' style="display: none;" />
                                    <?php $row_rs_categ_group = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod . '*' . 'G', $obBD_conexion); ?>
                                    <select name="Cat_Rec" id="Cat_Rec" onchange="$('#Rec_Cdc').val($(this).find('option:selected').data('cdc'))" class="form-control" data-placeholder="Selecciona una categoria...">
                                        <option value=""></option>
                                        <?Php foreach ($row_rs_categ_group as $row) {
                                            echo "<option value='$row[Cat_Cod]' data-cdc='$row[Cat_Cdc]'>$row[Cat_Des]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label label-sm required">Nombre Categoria:</label>
                                <div class="col-sm-8"><input type="text" class="form-control input-sm" name="Cat_Des" value="" required /></div>
                            </div>
                            <div class="form-group">
                                <input type="text" name='Rec_Cdc' id='Rec_Cdc' style="display: none;" />
                                <?php $row_rs_categ_group = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod . '*' . 'G', $obBD_conexion); ?>
                                <select name="Cat_Rec" id="Cat_Rec" onchange="$('#Rec_Cdc').val($(this).find('option:selected').data('cdc'))" class="form-control" data-placeholder="Selecciona una categoria...">
                                    <option value=""></option>
                                    <?Php foreach ($row_rs_categ_group as $row) {
                                        echo "<option value='$row[Cat_Cod]' data-cdc='$row[Cat_Cdc]'>$row[Cat_Des]</option>";
                                    } ?>
                                </select>
                                <label class="col-sm-4 control-label label-sm">Tipo:</label>
                                <div class="col-sm-8"><span class="form-control input-sm">Detalle</span></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
    <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
</BODY>

</HTML>