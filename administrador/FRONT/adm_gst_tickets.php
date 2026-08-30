<?php

/**
 * Interfaz para llevar el control de tickets de parte de las empresas
 *
 * @author Alejandro CAmacho
 * @version 1.0
 * Fecha de actualizaci�n:	2021/03/22
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_tickets.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');


/** 
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Con($obBD_conexion);
$hoy = date("Y-m-d H:i:s");
$mes = date("m");
/*
     * Realizar y Modificar DESARROLLO de la Ticket
     */
if (isset($Tad_Cod_Mod)) {
  $_POST['Tic_Fec_Env'] = $hoy;
  if (isset($_FILES["Tad_Fil"]) && $_FILES["Tad_Fil"]['size'] > 0) {
    $carpeta = "../ticketsSolucionados/$Ses_Emp_Cod/";

    if (!file_exists($carpeta)) {
      mkdir($carpeta, 0777, true);
    }
    $extension = pathinfo($_FILES["Tad_Fil"]["name"], PATHINFO_EXTENSION);
    $nombreArchivo = "ticketSolucionado" . $_POST["Tic_Cod_Re"] . "." . $extension;

    $target_file = $carpeta . basename($nombreArchivo);

    // Verifica si existe el archivo
    if (file_exists($target_file)) unlink($target_file);
    // Comprueba el tamano del archivo
    if ($_FILES["Tad_Fil"]["size"] > 5242880) $obBD_con1->echoJson(array('success' => false, 'message' => 'El archivo es demasiado grande!'));
    // if everything is ok, try to upload file
    if (move_uploaded_file($_FILES["Tad_Fil"]["tmp_name"], $target_file)) {
      $_POST['Tad_Fil'] = $target_file;
    } else $obBD_con1->echoJson(array('success' => false, 'message' => 'No se pudo subir el archivo!'));
  }

  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
  if ($_POST['Tad_Cod_Mod'] == 1) {
    $obBD_con1->operacionobBD(9, $_POST, $obBD_conexion);
    $obBD_con1->operacionobBD(10, $_POST, $obBD_conexion);
  } else {
    $obBD_con1->operacionobBD(8, $_POST, $obBD_conexion);
    $obBD_con1->operacionobBD(10, $_POST, $obBD_conexion);
  }

  if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) {
    $responce['success'] = true;
  } else {
    $responce['success'] = false;
    $responce['message'] = "No se ha logrado realizar la Transacci�n";
  }
  $obBD_con1->echoJson($responce);
  exit();
}

/*
     * Obtener Tickets
     */
if (isset($searchFiltro)) {
  $data = $obBD_con1->getArrayConsulta(4, $_GET, $obBD_conexion);
  // Grid necesita este array
  $obBD_con1->echoJson(array(
    'rows' => $data,
    'total' => 1,
    'records' => count($data),
    'success' => true
  ));
  exit();
}

/*
     * Cambiar el estado de las tickets (anular y validar)
     */
if (isset($setEstado)) {
  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
  $obBD_con1->operacionobBD(6, $_GET, $obBD_conexion);

  if ($Tic_Validar) {
    $obBD_con1->operacionobBD(13, array('Tic_Cod' => $Tic_Cod, 'Ses_Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion);
  }

  if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) {
    $response['success'] = true;
  } else {
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
  }
  $obBD_con1->echoJson($response);
  exit();
}

if (isset($cargarEditar)) {
  $data = $obBD_con1->getRowConsulta(14, $_GET, $obBD_conexion);
  // Grid necesita este array
  $obBD_con1->echoJson(array(
    'rows' => $data,
    'total' => 1,
    'records' => count($data),
    'success' => true
  ));
  exit();
}

$usuario = $obBD_con1->getArrayConsulta(1,  $Ses_Emp_Cod, $obBD_conexion);
$empresas = $obBD_con1->getArrayConsulta(2,  $Ses_Emp_Cod, $obBD_conexion);
$usuarioTipo = $obBD_con1->getRowConsulta(12,  $Ses_Usu_Cod, $obBD_conexion);

?>

<!DOCTYPE html>
<HTML>

<HEAD>
  <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
  <meta charset="UTF-8">
  <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
  <script type="text/javascript">
    var jsUsuario = <?php echo $Ses_Usu_Cod; ?>;
  </script>
  <script type="text/javascript">
    var jsUsuarioTipo = <?php if ($usuarioTipo['Ust_Tip'] != "") {
                          echo "'" . $usuarioTipo['Ust_Tip'] . "'";
                        } else {
                          echo "''";
                        } ?>;
  </script>
  <script src="../../Librerias/socketio/socket.io.min.js"></script>


  <style>
    .file {
      position: relative;
      display: inline-block;
      cursor: pointer;
      height: 2.5rem;
    }

    .file input {
      min-width: 14rem;
      margin: 0;
      filter: alpha(opacity=0);
      opacity: 0;
    }

    .file-custom {
      position: absolute;
      top: 0;
      right: 0;
      left: 0;
      z-index: 5;
      height: 2.5rem;
      padding: .5rem 1rem;
      line-height: 1.5;
      color: #555;
      background-color: #fff;
      border: .075rem solid #ddd;
      border-radius: .25rem;
      box-shadow: inset 0 .2rem .4rem rgba(0, 0, 0, .05);
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }

    .file-custom:after {
      /* content: "Agrega una captura (opcional)"; */
      content: "";
    }

    .file-custom:before {
      position: absolute;
      top: -.075rem;
      right: -.075rem;
      bottom: -.075rem;
      z-index: 6;
      display: block;
      content: "Buscar";
      height: 2.5rem;
      padding: .5rem 1rem;
      line-height: 1.5;
      color: #555;
      background-color: #eee;
      border: .075rem solid #ddd;
      border-radius: 0 .25rem .25rem 0;
    }

    /* Focus */
    .file input:focus~.file-custom {
      box-shadow: 0 0 0 .075rem #fff, 0 0 0 .2rem #0074d9;
    }

    .checked {
      color: orange;
    }

    .rating {
      border: none;
      float: left;
    }

    .rating>input {
      display: none;
    }

    .rating>label:before {
      margin: 5px;
      font-size: 1.25em;
      font-family: FontAwesome;
      display: inline-block;
      content: "\f005";
    }

    .rating>.half:before {
      content: "\f089";
      position: absolute;
    }

    .rating>label {
      color: #ddd;
      float: right;
    }

    /***** CSS Magic to Highlight Stars on Hover *****/

    .rating>input:checked~label,
    /* show gold star when clicked */
    .rating:not(:checked)>label:hover,
    /* hover current star */
    .rating:not(:checked)>label:hover~label {
      color: #FFD700;
    }

    /* hover previous stars in list */

    .rating>input:checked+label:hover,
    /* hover current star when changing rating */
    .rating>input:checked~label:hover,
    .rating>label:hover~input:checked~label,
    /* lighten current selection */
    .rating>input:checked~label:hover~label {
      color: #FFED85;
    }

    #Label_Tic_Tel,
    #Label_Mod_Tic_Mod,
    #Label_Mod_Tic_Pro,
    #Label_Mod_Tic_Acc {
      padding-left: 30px !important;
    }

    #Label_Tic_Evi_Pro {
      padding-left: 0px !important;
    }

    #Label_Tic_Evi_Sol {
      padding-left: 0px !important;
    }

    /*basic settings */

    a:focus {
      outline: none !important;
      outline-offset: none !important;
    }

    body {
      background: #f5f6f5;
      color: #333;
    }

    /* helper classses */

    .margin-top-20 {
      margin-top: 15px;
    }

    .margin-bottom-20 {
      margin-bottom: 15px;
    }

    .no-margin {
      margin: 0px;
    }

    /* box component */

    .box {
      border-color: #e6e6e6;
      background: #FFF;
      border-radius: 6px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.25);
      padding: 10px;
      margin-bottom: 40px;
    }

    .box-center {
      margin: 20px auto;
    }

    /* input [type = file]
          ----------------------------------------------- */

    /* input[type=file] { */
    #Mod_Tic_Evi_Pro {
      display: block !important;
      right: 1px;
      top: 1px;
      height: 34px;
      opacity: 0;
      width: 100%;
      background: none;
      position: absolute;
      overflow: hidden;
      z-index: 2;
    }

    .control-fileupload::before,
    .control-fileupload.input,
    .control-fileupload.label {
      cursor: pointer !important;
    }

    .control-fileupload::before {
      /* inherit from boostrap btn styles */
      padding: 4px 12px;
      margin-bottom: 0;
      font-size: 14px;
      line-height: 20px;
      color: #333333;
      text-align: center;
      text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
      vertical-align: middle;
      cursor: pointer;
      background-color: #f5f5f5;
      background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
      background-repeat: repeat-x;
      border: 1px solid #cccccc;
      border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
      border-bottom-color: #b3b3b3;
      border-radius: 4px;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
      transition: color 0.2s ease;

      /* add more custom styles*/
      content: 'Buscar';
      display: block;
      position: absolute;
      z-index: 1;
      top: 2px;
      right: 2px;
      line-height: 20px;
      text-align: center;
    }

    .control-fileupload:hover:before,
    .control-fileupload:focus:before {
      color: #333333;
      background-color: #e6e6e6;
      color: #333333;
      text-decoration: none;
      background-position: 0 -15px;
      transition: background-position 0.2s ease-out;
    }

    .control-fileupload.label {
      line-height: 24px;
      color: #999999;
      font-size: 14px;
      font-weight: normal;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
      position: relative;
      z-index: 1;
      margin-right: 90px;
      margin-bottom: 0px;
      cursor: text;
    }

    .control-fileupload {
      display: block;
      border: 1px solid #d6d7d6;
      background: #FFF;
      border-radius: 4px;
      width: 100%;
      height: 36px;
      line-height: 36px;
      padding: 0px 10px 2px 10px;
      overflow: hidden;
      position: relative;
    }

    #Img-Evi {
      height: auto;
      width: auto;
      max-width: 300px;
      max-height: 300px;
    }
  </style>
</HEAD>

<BODY>
  <div id="documentoSearch" class="panel panel-main">
    <div class="panel-heading exa-header">
      <h3 class="panel-title">&raquo; Control de Tickets</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
      <div id="lista" class="row">
        <div class="col-sm-12">

          <form id="frm_alt_auto" name="frm_alt_auto" class="form-vertical" autocomplete="off">
            <fieldset class="exa-fieldset">
              <legend class="Titulos2">Filtrar Tickets</legend>

              <!-- Fecha -->
              <div class="col-sm-3">
                <div class="input-group input-group-xs">
                  <span class="input-group-addon bold alert-info">Desde:</span>
                  <input name="txt_fec_ini" type="text" id="txt_fec_ini" class="form-control input-sm datepicker databind" style="text-align: center;" />
                  <span class="input-group-addon bold alert-info">Hasta:</span>
                  <input name="txt_fec_fin" type="text" id="txt_fec_fin" class="form-control input-sm datepicker databind" style="text-align: center;" />
                </div>
              </div>

              <!-- Button -->
              <div class="col-sm-1">
                <button id="btnSearch" name="btnSearch" class="btn btn-success">Buscar</button>
              </div>

              <div class="col-sm-3"></div>
            </fieldset>
          </form>

          <div>
            <table id="tableResult"></table>
            <div id="tableResultPager"></div>
            <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop red"></span> Pendiente | <span class="glyphicon glyphicon-stop blue"></span> En Proceso |<span class="glyphicon glyphicon-stop white"></span> Solucionado </div>
            <br>
            <div class="col-sm-2 ">
              <button id="btnNueva" name="btnNueva" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-plus"></i> Nuevo Ticket</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="createModal" title="">
    <form id="createForm" name="createForm" class="form-horizontal" autocomplete="off" action="javascript:crearTicket()" method="post" enctype="multipart/form-data">
      <fieldset>

        <!-- Cod Autorizacion-->
        <div>
          <input type="text" id="isCreateAction" name="isCreateAction" hidden="true" value="true">
        </div>

        <!-- Archivo-->
        <div class="form-group">
          <label class="file col-md-offset-2 col-md-10">
            <input type="file" id="Tic_Evi_Pro" name="Tic_Evi_Pro" aria-label="File browser example">
            <span id="Tic_Evi_Pro_Txt" class="file-custom" style="margin-right: 15px;">Agrega una captura (opcional)</span>
          </label>
        </div>

        <div class="form-group">
          <label class="col-md-4 control-label label-xs required" for="Tic_Mod">M&oacute;dulo:</label>
          <div class="col-md-8">
            <select id="Tic_Mod" name="Tic_Mod" class="form-control input-xs">
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="col-md-4 control-label label-xs required" for="Tic_Pro">Secci&oacute;n:</label>
          <div class="col-md-8">
            <select id="Tic_Pro" name="Tic_Pro" class="form-control input-xs">
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="col-md-4 control-label label-xs required" for="Tic_Acc">Proceso:</label>
          <div class="col-md-8">
            <select id="Tic_Acc" name="Tic_Acc" class="form-control input-xs">
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="col-md-4 control-label label-xs required" for="Tic_Tel">Teléfono:</label>
          <div class="col-md-8">
            <input id="Tic_Tel" name="Tic_Tel" type="text" placeholder="" class="form-control input-xs" maxlength="10" minlength="10" required>
          </div>
        </div>
        <div class="form-group">
          <label class="col-md-4 control-label label-xs required" for="Tic_Fec_Ent">Título:</label>
          <div class="col-md-8">
            <input id="Tic_Tem" name="Tic_Tem" type="text" placeholder="" class="form-control input-xs" required>
          </div>
        </div>
        <div class="form-group">
          <div class="col-sm-12">
            <textarea id="Tic_Des" name="Tic_Des" rows="14" cols="55" placeholder="Descripcion del problema" required></textarea>
          </div>
        </div>

        <!-- Buttons -->
        <div class="form-group">
          <label class="col-md-4 control-label" for="btnModificar"></label>
          <div class="col-md-8">
            <button type="submit" id="btnCreate" name="btnCreate" class="btn btn-sm btn-primary"></button>
          </div>
        </div>

      </fieldset>
    </form>
  </div>
  <div id="documentoMain" class="panel panel-main">
    <div class="panel-heading exa-header">
      <h3 class="panel-title">&raquo; Control de Tickets</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
      <div id="fila" class="row">
        <form id="editForm" name="editForm" class="form-horizontal" autocomplete="off" action="javascript:modificarTicket()" method="post" enctype="multipart/form-data">
          <div class="col-md-12">
            <fieldset class="exa-fieldset">
              <legend class="Titulos2"> Datos del Ticket</legend>
              <input type="text" id="isEditAction" name="isEditAction" hidden="true" value="true">
              <input type="text" id="Tic_Cod" name="Tic_Cod" hidden="true">
              <div id="cargarDoc" class="col-md-4">
                <div class="row">
                  <label class="col-md-5 control-label label-xs text-info text-left" for="Tic_Fec_Cre">Creaci&oacute;n:</label>
                  <p class="col-md-7" id="Tic_Fec_Cre" name="Tic_Fec_Cre"></p>
                </div>
                <div class="row">
                  <label class="col-md-5 control-label label-xs text-info text-left" for="Tic_Fec_Ter">&Uacute;ltima modificaci&oacute;n:</label>
                  <p class="col-md-7" id="Tic_Fec_Ter" name="Tic_Fec_Ter"></p>
                </div>
                <div class="row">
                  <label class="col-md-5 control-label label-xs text-info" for="Prs_Nom">Creador:</label>
                  <p class="col-md-7" id="Prs_Nom" name="Prs_Nom"></p>
                </div>
                <div class="row">
                  <label class="col-md-5 control-label label-xs text-info" for="Emp_Cod">Empresa:</label>
                  <p class="col-md-7" id="Emp_Cod_Re" name="Emp_Cod"></p>
                </div>
                <div class="row">
                  <label id="Label_Tic_Tel" class="col-md-5 control-label label-xs text-info" for="Mod_Tic_Tel">Tel&eacute;fono:</label>
                  <div class="col-md-7">
                    <input id="Mod_Tic_Tel" name="Mod_Tic_Tel" type="text" placeholder="" class="form-control input-xs">
                  </div>
                </div>

                <div class="row form-group">
                  <label id="Label_Mod_Tic_Mod" class="col-md-5 control-label label-xs text-info" for="Mod_Tic_Mod">M&oacute;dulo:</label>
                  <div class="col-md-6">
                    <select id="Mod_Tic_Mod" name="Mod_Tic_Mod" class="form-control input-xs">
                    </select>
                  </div>
                </div>

                <div class="row form-group">
                  <label id="Label_Mod_Tic_Pro" class="col-md-5 control-label label-xs text-info" for="Mod_Tic_Pro">Secci&oacute;n:</label>
                  <div class="col-md-6">
                    <select id="Mod_Tic_Pro" name="Mod_Tic_Pro" class="form-control input-xs">
                    </select>
                  </div>
                </div>

                <div class="row form-group">
                  <label id="Label_Mod_Tic_Acc" class="col-md-5 control-label label-xs text-info" for="Mod_Tic_Acc">Proceso:</label>
                  <div class="col-md-6">
                    <select id="Mod_Tic_Acc" name="Mod_Tic_Acc" class="form-control input-xs">
                    </select>
                  </div>
                </div>
                <div class="row">
                  <label class="col-md-5 control-label label-xs text-info" for="Tic_Est">Estado:</label>
                  <p class="col-md-7" id="Tic_Est" name="Tic_Est"></p>
                </div>
              </div>
              <div class="col-md-8">
                <div class="row">
                  <label id="Label_Mod_Tic_Tem" class="control-label label-xs text-info" for="Mod_Tic_Tem">Tema:</label>
                  <textarea id="Mod_Tic_Tem" name="Mod_Tic_Tem" class="col-md-12 form-control" rows="1"></textarea>
                </div>
                <div class="row">
                  <label id="Label_Mod_Tic_Des" class="control-label label-xs text-info" for="Mod_Tic_Des">Descripci&oacute;n:</label>
                  <textarea id="Mod_Tic_Des" name="Mod_Tic_Des" class="form-control" rows="8"></textarea>
                </div>
              </div>
              <div class="row">
                <div class="col-md-9">
                  <div class="row">
                    <div class="col-md-offset-1 col-sm-6 margin-top-20 margin-bottom-20">
                      <img id="Img-Evi" class="thumbnail box-center " alt="Sin imagen" src="">
                    </div>
                  </div>
                  <div class="row">
                    <div id="drag_drop" class="col-md-offset-1 col-sm-6">
                      <span class="control-fileupload">
                        <label for="Mod_Tic_Evi_Pro" class="text-left">Por favor escoge un archivo</label>
                        <input type="file" id="Mod_Tic_Evi_Pro" name="Mod_Tic_Evi_Pro" onchange="loadFile()" accept="image/*">
                      </span>
                    </div>
                  </div>
                </div>
              </div>

            </fieldset>
            <fieldset id="Cal_Fields" class="exa-fieldset">
              <legend class="Titulos2"> Calificaci&oacute;n</legend>
              <label id="Cal_Text" class="col-md-12 col-form-label text-warning text-left">Califica el servicio brindado </label>
              <div class="row">
                <label class="col-md-2 control-label label-xs text-info text-left" for="Tic_Cal">Puntuaci&oacute;n:</label>
                <fieldset class="rating">
                  <input type="radio" id="star5" name="rating" value="5" /><label class="full" for="star5"></label>
                  <input type="radio" id="star4" name="rating" value="4" /><label class="full" for="star4"></label>
                  <input type="radio" id="star3" name="rating" value="3" /><label class="full" for="star3"></label>
                  <input type="radio" id="star2" name="rating" value="2" /><label class="full" for="star2"></label>
                  <input type="radio" id="star1" name="rating" value="1" /><label class="full" for="star1"></label>
                </fieldset>
              </div>
              <div class="row">
                <label class="col-md-2 control-label label-xs text-info text-left" for="Tic_Cal_Des">Comentario:</label>
                <input class="col-md-4" id='Tic_Cal_Des' name='Tic_Cal_Des' type="text" />
              </div>
              <div class="row">
                <a onclick="onRate();" id="btnCalificar" name="btnCalificar" class="col-md-offset-2 col-md-1 btn btn-warning btn-sm margin-top-20">Calificar</a>
              </div>
            </fieldset>
          </div>
          <div>
            <input type="text" id="Tic_Cod_Re" name="Tic_Cod_Re" hidden="true">
            <input type="text" id="Tad_Cod_Mod" name="Tad_Cod_Mod" hidden="true">
          </div>
          <div class="form-group">
            <div class="col-md-12">
              <label for="Tic_Obs" class="col-md-12 col-form-label text-left">Observación: </label>
              <div class="col-md-12">
                <textarea id="Tic_Obs" name="Tic_Obs" class="form-control" rows="3" disabled="true"></textarea>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="col-md-12">
              <div class="col text-center">
                <a class="black btn btn-md btn-inverse" onclick="atras();"><i class="glyphicon glyphicon-arrow-left"></i>Atrás</a>
                <button type="submit" id="saveButton" class="btn btn-md btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i>Guardar</button>
              </div>
            </div>
          </div>
        </form>
        <!--FIN para guardar el ticket  -->
      </div>
    </div>
  </div>
  <script type="text/javascript" src="../VALIDACIONES/adm_val_tickets.js?x=x11"></script>
  <script type="text/javascript">
    $('#documentoMain').hide();
    loadTasks(true);
  </script>
</BODY>

</HTML>