<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_producto_1.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);
/* Creacion del Objeto para consultas */
$obBD_con1 =  new Class_Log_Datos_Pro;

$hoy = date("Y-m-d");
$mes = date("m");

function reverse_number($number)
{
  $arr = str_split($number);
  $rev_arr = array_reverse($arr);
  $rev = implode("", $rev_arr);
  return $rev;
}

if (isset($validaIteLar)) {
  $conteo = $obBD_con1->getRowConsulta(26, $Ses_Emp_Cod . '*' . trim($Ite_Lar), $obBD_conexion);
  $resp = array('success' => '');
  if ($conteo['total'] * 1 > 0) $resp = array('success' => false, 'state' => 'warning', 'message' => "Ya existe un producto con el nombre \"$Ite_Lar\".");
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
    case 'Mat':
      $sql = 34;
      // La consulta 33 espera Cod_Const y Desc_Const
      $resp['Cod_Const'] = isset($resp['Cod_Const']) ? $resp['Cod_Const'] : '';
      $resp['Desc_Const'] = isset($resp['Desc_Const']) ? $resp['Desc_Const'] : '';
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
if (isset($saveProduct)) {
  //$obBD_con1->debug(true);   
  $data = filter_input_array(INPUT_POST);
  if ($data['Pro_Uni'] == "") $data['Pro_Uni'] = 1;
  if ($data['Pro_Dsc'] == "") $data['Pro_Dsc'] = 0;

  // secuancia en caso de categorias
  $row_rs_con_sec = $obBD_con1->getRowConsulta(8, $data['Cat_Cod'] . '*' . $Ses_Emp_Cod, $obBD_conexion);
  $data['Pro_Cdc'] = $row_rs_con_sec['Cat_Cdc'] . '.1';
  $data['Pro_Sec'] = 1;
  // Identificador en caso de lineas
  $data['Pro_Ide'] = 'NULL';
  // Eliminado xq ya no quieren codigo por linea
  /*if($data['Lin_Cod']!=='NULL'){
        $row_rs_con_sec= $obBD_con1->getRowConsulta(16,$data['Lin_Cod'].'*'.$Ses_Emp_Cod,$obBD_conexion); 
        $data['Pro_Ide']=$row_rs_con_sec['siguiente'];
        if($row_rs_con_sec['siguiente']==NULL || $row_rs_con_sec['siguiente']=='') $data['Pro_Ide']=1;
    }*/
  // Ahora por categoria
  $row_rs_con_ide = $obBD_con1->getRowConsulta(19, $data['Cat_Cod'] . '*' . $Ses_Emp_Cod, $obBD_conexion);
  $data['Pro_Ide'] = $row_rs_con_ide['siguiente'];
  if ($row_rs_con_ide['siguiente'] == NULL || $row_rs_con_ide['siguiente'] == '') $data['Pro_Ide'] = 1;
  if (is_string($data)) { $data = mb_convert_encoding($data, 'ISO-8859-1', 'UTF-8'); } else if (is_array($data)) { array_walk_recursive($data, function(&$v) { if (is_string($v)) $v = mb_convert_encoding($v, 'ISO-8859-1', 'UTF-8'); }); }
  //var_dump($data['Pro_Ide']);
  $row_rs_sucur = $obBD_con1->getArrayConsulta(17, $Ses_Emp_Cod, $obBD_conexion);
  //var_dump($row_rs_sucur);
  //$obBD_con1 =  new Class_Log_Datos_Pro;
  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
  /** Inserci�n del item */
  $obBD_con1->operacionobBD(10, $data, $obBD_conexion);
  $data['Ite_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
  /* Inserci�n del producto */
  $obBD_con1->operacionobBD(11, $data, $obBD_conexion);
  $data['Pro_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
  $Pro_Cod = $data['Pro_Cod'];


  /* Genera el Codigo de Barra senececitan 12 caracteres para generar */
  if ($Pro_Gen == 1) {
    $numeroP = 1000;
    $numRever = reverse_number($Pro_Cod);
    $obBD_con1->echoLog($numRever);
    if ($numRever <= 1) {
      $obBD_con1->echoLog('SI');
      $valPuro = str_pad($Pro_Cod, 12, "0"); //$Pro_var;
      $genNum = mt_rand(1, 19);
      $obBD_con1->echoLog($genNum);
      $newNumGen = $valPuro + $genNum;
      $obBD_con1->echoLog($newNumGen);
      $data['Pro_Bar'] = $newNumGen;
    } else {
      $obBD_con1->echoLog('NO');
      $data['Pro_Bar'] = str_pad($Pro_Cod, 12, "0");
    }
    //$data['Pro_Bar']=str_pad($Pro_Cod, 12, "0");//$Pro_var;
    $Pro_varg = 'G';
  } else {
    $Pro_varg = 'M';
  }
  /* Actualiza el codigo de barras en el producto insertado */
  $obBD_con1->operacionobBD(12, $Pro_Cod . '*' . $data['Pro_Bar'] . '*' . $Pro_varg, $obBD_conexion);


  /* Genera el Codigo interno del producto para la empresa */
  if ($Pro_Gen_Emp == 1) {
    $numeroP_emp = 1000;
    $numRever_emp = reverse_number($Pro_Cod);
    $obBD_con1->echoLog($numRever_emp);
    if ($numRever_emp <= 1) {
      $obBD_con1->echoLog('SI');
      $valPuro_emp = str_pad($Pro_Cod, 12, "0"); //$Pro_var;
      $genNum_emp = mt_rand(1, 19);
      $obBD_con1->echoLog($genNum_emp);
      $newNumGen_emp = $valPuro_emp + $genNum_emp;
      $obBD_con1->echoLog($newNumGen_emp);
      $data['Pro_Bar_Emp'] = $newNumGen_emp;
    } else {
      $obBD_con1->echoLog('NO');
      $data['Pro_Bar_Emp'] = str_pad($Pro_Cod, 12, "0");
    }
    //$data['Pro_Bar']=str_pad($Pro_Cod, 12, "0");//$Pro_var;
    $Pro_varg_emp = 'G';
  } else {
    $Pro_varg_emp = 'M';
  }
  /* Actualiza el codigo interno de la empresa en el producto insertado */
  $obBD_con1->operacionobBD(122, $Pro_Cod . '*' . $data['Pro_Bar_Emp'] . '*' . $Pro_varg_emp, $obBD_conexion);

  /* Guardo en la tabla Stock */
  $data['Suc_Cod'] = $Ses_Suc_Cod;
  $obBD_con1->operacionobBD(13, $data, $obBD_conexion);

  /* Consulta el tipo de precio */
  $row_rs_con_tp = $obBD_con1->getRowConsulta(9, 'D*' . $Ses_Suc_Cod, $obBD_conexion);
  $Tpv_Cod = ($row_rs_con_tp['Tpv_Cod'] > 0) ? $row_rs_con_tp['Tpv_Cod'] : 0;
  /* Inserta un precio por defecto */
  $obBD_con1->operacionobBD(14, $Pro_Cod . '*' . $data['Pre_Pvp'] . '*' . 'Precio 1' . '*' . $Ses_Suc_Cod . '*' . $Tpv_Cod, $obBD_conexion);
  $Pre_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);

  /*foreach ($row_rs_sucur AS $Suc){
            
       } */
  $responce['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
  //$responce['message']=$obBD_con1->MsgError;    
  $obBD_con1->echoJson($responce);
  echo json_encode($responce);
  exit();
}

if (isset($_POST['getMaterials'])) {
  $material = $obBD_con1->getArrayConsulta(33, "", $obBD_conexion);
  echo json_encode($material);
  exit;
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
  <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
  <TITLE><?Php echo "Producto Registrar [EXA]"; ?></TITLE>
  <meta charset="UTF-8">
  <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
  <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
  <script language="javascript" src="../VALIDACIONES/fac_val_producto.js"></script>
  <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
  <style>
    .checkbox.checkbox-xs label {
      position: absolute;
      margin-top: -7px;
    }

    #Cat_Rec_chosen.chosen-container .chosen-results {
      max-height: 120px;
    }
  </style>
</HEAD>

<BODY>

  <div class="panel panel-main">
    <div class="panel-heading exa-header">
      <h3 class="panel-title">&raquo; Registrar Productos</h3>
    </div>

    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
      <form id="formProduct" class="form-horizontal normal" action="javascript:if($('#Cat_Cod').val()===''){ $('#Cat_Cod_chosen').flyout('show'); $('#Cat_Cod').trigger('chosen:activate');  } else $.createDialogConfirm(null,null,saveProduct); ">
        <div class="row">
          <div class="col-sm-6">
            <fieldset class="exa-fieldset">
              <legend class="Titulos2">Datos Generales:</legend> <!-- Form Name -->
              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="Cat_Cod">Categoría:</label>
                <div class="col-sm-9">
                  <div class="input-group input-group-sm">
                    <?php $row_rs_categ = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion); ?>
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
                <label class="col-sm-3 control-label label-sm required" for="Ite_Lar">Descripción Larga:</label>
                <div class="col-sm-9">
                  <div class="input-group input-group-sm">
                    <input type="text" id="Ite_Lar" name="Ite_Lar" class="form-control input-sm text" placeholder="Nombre del Producto" required="" onchange="validaName($(this))" maxlength="250" />
                    <span class="input-group-addon validate"><i></i></span>
                  </div>
                </div>
              </div>
              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="Ite_Cor">Descripción Corta:</label>
                <div class="col-sm-4">
                  <input type="text" id="Ite_Cor" name="Ite_Cor" class="form-control input-sm text" placeholder="Abre. del Nombre" required="" maxlength="50" />
                </div>
              </div>
              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="Mar_Cod">Marca:</label>
                <div class="col-sm-4">
                  <div class="input-group input-group-sm">
                    <?php $rs_marca = $obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod, $obBD_conexion);   ?>
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
                <label class="col-sm-3 control-label label-sm required" for="Adq_Cod">Adquisición:</label>
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
                    <?Php foreach ($row_rs_iva as $row) {
                      // echo "<option value='$row[Iva_Cod]' >$row[Iva_Por]%</option>";
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
            <fieldset class="exa-fieldset">
              <legend class="Titulos2">Stock:</legend> <!-- Form Name -->
              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-xs required" for="Stk_Min">Minimo:</label>
                <div class="col-sm-3">
                  <input type="text" id="Stk_Min" name="Stk_Min" class="form-control input-xs text center" onkeypress="return validar_decimal(event)" required value="10">
                </div>
                <label class="col-sm-2 control-label label-xs required" for="Stk_Max">Máximo:</label>
                <div class="col-sm-3">
                  <input type="text" id="Stk_Max" name="Stk_Max" class="form-control input-xs text center" onkeypress="return validar_decimal(event)" required value="1000">
                </div>
              </div>
              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-xs required" for="Stk_Prp">Precio Costo:</label>
                <div class="col-sm-3">
                  <input type="hidden" value="0" name="Stk_Can" />
                  <input type="text" name="Stk_Prp" id="Stk_Prp" class="form-control input-xs text" onKeyPress="return validar_decimal(event)" value="0" size="8" maxlength="8" style="text-align: right" required>
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
                  <input id="Pro_Obs" name="Pro_Obs" class="form-control input-sm text" placeholder="Detalle de la Presentación" required="">

                </div>
              </div>
              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-xs required" for="Pro_Bar">Código de barra:</label>
                <div class="col-sm-3">
                  <input id="Pro_Bar" name="Pro_Bar" type="text" class="form-control input-xs text center" disabled="disabled">
                </div>
                <div class="col-sm-6">
                  <div class="checkbox checkbox-xs check-big">
                    <label class="label-xs"><input type="checkbox" name="Pro_Gen" id="Pro_Gen" checked="" onClick="check_generar()" value="1"><span id='contenedorcheck'>Generar c&oacute;digo automaticamente</span></label>
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
                    <label class="label-xs"><input type="checkbox" name="Pro_Gen_Emp" id="Pro_Gen_Emp" checked="" onClick="check_generar_empresa()" value="1"><span id='contenedorcheckempresa'>Genera el codigo de empresa del producto</span></label>
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
                      <?php $material = $obBD_con1->getArrayConsulta(33, "", $obBD_conexion);
                      foreach ($material as $row) { ?>
                        <?php if ($row['Cod_Const'] == 'H492001') {
                          echo " <optgroup label='-- CÓDIGOS DE TRANSPORTE --'></optgroup>";
                        }  ?>
                        <option value="<?php echo $row['Cod_Const']; ?>"><?php echo $row['Cod_Const'] . ' - ' . $row['Desc_Const']; ?></option>
                      <?php } ?>
                    </select>
                    <span class="input-group-btn">
                      <button onclick="$('#materialForm').setData({}); $('#materialDialog').dialog('open');" class="btn btn-success" type="button">
                        <i class="glyphicon glyphicon-plus"></i>
                      </button>
                    </span>
                  </div>
                </div>
              </div>




              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="Ubi_Cod">Ubicación:</label>
                <div class="col-sm-4">
                  <div class="input-group input-group-sm">
                    <?php $rs_ubicacion = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion); ?>
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
                <label class="col-sm-3 control-label label-sm required" for="Lin_Cod">Línea:</label>
                <div class="col-sm-4">
                  <div class="input-group input-group-sm">
                    <?php $rs_linea = $obBD_con1->getArrayConsulta(15, $Ses_Emp_Cod, $obBD_conexion);  ?>
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
                <label class="col-sm-3 control-label label-sm required" for="Pre_Cod">Presentación:</label>
                <div class="col-sm-3">
                  <?php $rs_presen = $obBD_con1->getArrayConsulta(7, $Ses_Emp_Cod, $obBD_conexion);     ?>
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
                  <?php $rs_unidad = $obBD_con1->getArrayConsulta(6, $Ses_Emp_Cod, $obBD_conexion);     ?>
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
              <!-- Text input-->
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
                <button type="submit" class="btn btn-primary btn-form"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                <!--<button type="reset" onclick=""  class="btn btn-danger btn-form"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>                                -->
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
  <div id="ubicaDialog" title="Agregar Ubicación">
    <div class="row">
      <div class="col-md-12">
        <form id="ubicaForm" class="form-horizontal normal" action="javascript:$.createDialogConfirm(null,{nameSave:'ubica',select:'Ubi'},saveForm)">
          <div class="form-group">
            <label class="col-sm-4 control-label label-sm required">Nombre Ubicación:</label>
            <div class="col-sm-8"><input type="text" class="form-control input-sm" name="Ubi_Des" value="" required /></div>
          </div>
          <div class="form-group">
            <label class="col-sm-4 control-label label-sm">Descripción:</label>
            <div class="col-sm-8"><textarea class="form-control input-sm" name="Ubi_Obs"></textarea></div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- modal para registro de codigo de material -->
  <div id="materialDialog" title="Agregar Codigo de Material">
    <div class="row">
      <div class="col-md-12">
        <form id="materialForm" class="form-horizontal normal" action="javascript:$.createDialogConfirm(null,{nameSave:'material',select:'Mat'},saveForm)">
          <div class="form-group" style="margin-top: 25px;">
            <label class="col-sm-4 control-label label-sm required">Codigo del Material:</label>
            <div class="col-sm-7">
              <input type="text" class="form-control input-sm" name="Cod_Const" value="" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-4 control-label label-sm">Descripcion del Codigo:</label>
            <div class="col-sm-7">
              <input type="text" class="form-control input-sm" id="Desc_Const" name="Desc_Const" />
            </div>
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
            <label class="col-sm-4 control-label label-sm">Tipo:</label>
            <div class="col-sm-8"><span class="form-control input-sm">Detalle</span></div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script>
    $(document).ready(function() {
      $('#marcaDialog').createDialog({
        height: 150,
        width: 530,
        icon: 'plus'
      });
      $('#lineaDialog').createDialog({
        height: 180,
        width: 530,
        icon: 'plus'
      });
      $('#ubicaDialog').createDialog({
        height: 200,
        width: 530,
        icon: 'plus'
      });
      $('#materialDialog').createDialog({
        height: 250,
        width: 400,
        icon: 'plus'
      }); // nuevo bloque
      $('#categoDialog').createDialog({
        height: 225,
        width: 530,
        icon: 'plus'
      });
      $("#Cat_Cod").createChosen('input-sm');
      $('#Cat_Cod_chosen').createFlyout('Seleccione una categoria', {
        placement: 'bottom_right'
      });
      $("#Cat_Rec").createChosen('input-sm');
      $('#Cat_Rec_chosen').createFlyout('Seleccione una categoria', {
        placement: 'bottom_right'
      });
      addActions('marca');
      addActions('linea');
      addActions('ubica');
      addActions1('material'); // nuevo
      addActions('catego');
    });

    function changeIva() {
      if ($('#ChkNet').is(':checked')) updateNeto($('#PreNet').val());
      else updateUnitario($('#Pre_Pvp').val());
    }

    function resetForm() {
      $('#formProduct')[0].reset();
      $('#Cat_Cod').trigger('chosen:updated');
      $('#Ite_Lar').fieldValid();
    }

    function saveProduct() {
      $.saveDataJson("", $('#formProduct').getData('saveProduct'), resetForm());
    }

    function saveForm(o) {
      $.saveDataJson("", $.extend(o, $('#' + o['nameSave'] + 'Form').getData('save' + o['nameSave'].charAt(0).toUpperCase() + o['nameSave'].slice(1))), function(resp) {
        $('#' + resp['select'] + '_Cod').append('<option value="' + resp[resp['select'] + '_Cod'] + '">' + resp[resp['select'] + '_Des'] + '</option>').val(resp[resp['select'] + '_Cod']);
        $('#' + resp['nameSave'] + 'Dialog').dialog('close');
        if (resp['nameSave'] === 'catego') $('#Cat_Cod').trigger('chosen:updated');
        return false;
      });
    }

    function changeUnitario() {
      if ($('#ChkNet').is(':checked')) {
        $('#Pre_Pvp').attr('readonly', 'readonly');
        $('#PreNet').removeAttr('readonly');
      } else {
        $('#PreNet').attr('readonly', 'readonly');
        $('#Pre_Pvp').removeAttr('readonly');
      }
      $('#PreNet').val('');
      $('#Pre_Pvp').val('');
    }

    function updateNeto(value) {
      var Iva_Por = $("#Iva_Cod option:selected").text().replace("%", "");
      value = '0' + value;
      if (!isNaN(Iva_Por))
        $('#Pre_Pvp').val(Math.round(10000 * parseFloat(value) / (1 + (parseFloat(Iva_Por) / 100))) / 10000);
      //                    else
      //                        alert('Seleccione el I.V.A.');
    }

    function updateUnitario(value) {
      var Iva_Por = $("#Iva_Cod option:selected").text().replace("%", "");
      value = '0' + value;
      if (!isNaN(Iva_Por))
        $('#PreNet').val(Math.round(10000 * (parseFloat(value) + parseFloat(value) * ((parseFloat(Iva_Por) / 100)))) / 10000);
      //                    else
      //                        alert('Seleccione el I.V.A.');
    }

    function addActions(name) {
      $('#' + name + 'Form').append('<div class="form-group" style="padding-top:10px;"><label class="col-sm-4 control-label"></label><div class="col-sm-8">' +
        '<button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>' +
        '<button type="button" onclick="$(\'#' + name + 'Dialog\').dialog(\'close\');"  class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>' +
        '</div></div><div class="form-group Titulos2"><div class="col-md-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div></div>');
    }

    function addActions1(name) {
      $('#' + name + 'Form').append('<div class="form-group" style="padding-top:10px;"><label class="col-sm-4 control-label"></label><div class="col-sm-8" style="margin-left:-45px;">' +
        '<button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button> ' +
        '<button type="button" style="margin-left: 10px;" onclick="$(\'#' + name + 'Dialog\').dialog(\'close\');"  class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>' +
        '</div></div><div class="form-group Titulos2"><div class="col-md-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div></div>');

      // Add form submit handler
      $('#' + name + 'Form').on('submit', function(e) {
        e.preventDefault();
        $.createDialogConfirm(null, {
          nameSave: 'material',
          select: 'Mat'
        }, function(o) {
          $.saveDataJson("", $.extend(o, $('#materialForm').getData('saveMaterial')), function(resp) {
            // After successful save, update the Cod_Const select in main form
            $.ajax({
              url: window.location.href,
              method: 'POST',
              data: {
                getMaterials: true
              },
              dataType: 'json', // <-- muy importante
              success: function(materials) {
                // Limpiar y actualizar el select Cod_Const
                public $codConst = $('#Cod_Const');
                $codConst.empty().append('<option value="">-- NO APLICA --</option>');

                $.each(materials, function(i, material) {
                  $('<option></option>')
                    .val(material.Cod_Const)
                    .text(material.Cod_Const + ' - ' + material.Desc_Const)
                    .appendTo($codConst);
                });
              },
              error: function(xhr) {
                console.log('Error al obtener materiales:', xhr.responseText);
              }
            });

            $('#materialDialog').dialog('close');
          });
        });
      });
    }

    function validaName($el) {
      if ($el.val() === '') {
        $el.fieldValid('');
        return;
      }
      $el.getValidationJson('', {
        validaIteLar: true,
        Ite_Lar: $el.val()
      });
    }

    function validaCodigo($el) {
      if ($el.val() === '') {
        $el.fieldValid('');
        return;
      }
      $el.getValidationJson('', {
        validaCodEmp: true,
        Pro_Cod_Emp: $el.val()
      });
    }
  </script>
  <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
  <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
</BODY>

</HTML>