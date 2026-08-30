<?php
/**
*
* @abstract Permite realizar la modificacion de Anticipos Manuales
* @author Erik Cordova
* @version 1.0
* Fecha de creacion  2017-12-06
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anticipo_cli_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion_set = new Class_Log_Conexion_Ant_cli($Ses_Dat_Dis);
$obBD_con_set =  new Class_Log_Datos_Ant_cli;

$obBD_conexion_get = new Class_Log_Conexion_Ant_cli($Ses_Dat_Dis);
$obBD_con_get =  new Class_Log_Datos_Ant_cli;

$hoy = date("Y-m-d");
$mes = date("m");

// obtenemos los proveedores y sus anticipos
if (isset($anticiposAjax)) {
  $obBD_con_get->getPageGridJson(17,$_GET, $obBD_conexion_get);
}

//obtenemos todas las aportaciones de un socio
if(isset($anticiposDetAjax)){
  $responce['rows'] = $obBD_con_get->getArrayConsulta(18, array('Cli_Cod'=>$anticiposDetAjax,'txt_fec_ini'=>$txt_fec_ini,'txt_fec_fin'=>$txt_fec_fin), $obBD_conexion_get);

  $responce['records']=count($responce['rows']);

  $responce['records']=count($responce['rows']);
  $obBD_con_get->echoJson($responce);
  exit();
}

//obtenemos todas las aportaciones de un socio
if(isset($getAsientosAnticipo)){
  $response['success'] = false;
  $response['message'] = "No se ha logrado realizar la Transaccion";

  // $bandera=true;
  //
  // $Pld_Cod_ini = $obBD_con1->getArrayConsulta(3, "", $obBD_conexion);
  // if ($bandera == false && $Pld_Cod_ini > 0) {
  //   $bandera=false;
  //   $response['message'] = "Hace falta parametrizar una cuenta para Anticipos a proveedores";
  // }

  $response['data'] = $obBD_con_get->getArrayConsulta(21, $Com_Cod, $obBD_conexion_get);

  $response['data_che'] = $obBD_con_get->getArrayConsulta(22, array('Ant_Cod' => $Ant_Cod ), $obBD_conexion_get);

	if ($obBD_con_get->Error == 0) {
    $response['success'] = true;
  }

  $obBD_con_get->echoJson($response);
  exit();
}

//Anular un anticipo con sus respectivos comprobante, pagos, asientos, cheques
if(isset($delAnticipo)){
  $response['success'] = false;
  $response['message'] = "No se ha logrado realizar la Transaccion";
  $obBD_con_get->validaCierrePeriodo('anticipos_clientes','Ant_Fec','Ant_Cod',$Ant_Fec,$Ant_Cod,$obBD_conexion_set);
  $obBD_con_get->inicio_transaccion($obBD_conexion_set->conexion);

  $obBD_con_set->operacionobBD(23, $Com_Cod, $obBD_conexion_set);

  $obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set->conexion);

	if ($obBD_con_set->Error == 0) {
    $response['success'] = true;
  }

  $obBD_con_set->echoJson($response);
  exit();
}

if (isset($obtenerPeriodoMinMax)) {
  $resp['success'] = false;
  $resp['message'] = "No se ha logrado realizar la Transaccion";

  $resp['data']=$obBD_con_get->getRowConsulta(8,"",$obBD_conexion_get);

	$resp['success']=true;
	$obBD_con_get->echoJson($resp);
}

//obtenemos todas las aportaciones de un socio
if(isset($getAsientosAnticipoMod)){
	$response['success'] = false;

  $response['data'] = $obBD_con_get->getArrayConsulta(24, array('Com_Cod' => $Com_Cod ), $obBD_conexion_get);

	if ($obBD_con_get->Error == 0) {
    $response['success'] = true;
  }

  $obBD_con_get->echoJson($response);
  exit();
}

//verificamos si el numero de un cheque ya esta registrado dentro de la tabla cheques
if (isset($verificarCheNum)) {
  //Se obtiene el socio seleccionado
  $response['numero_che']=false;
  $num_Ches = $obBD_con_get->getArrayConsulta(13, $Ban_Cod, $obBD_conexion_get);
  foreach ($num_Ches as $nch) {
    if($nch['Che_Num']==$Che_Num){
      $response['numero_che']=true;
    }
  }

  $obBD_con_get->echoJson($response);
  exit();
}

if (isset($cargar_cuentas_pagos)) {
  $resp['bandera']=true;
  if ($tipo==='INICIAL') {
		$data=$obBD_con_get->getRowConsulta(3, "",$obBD_conexion_get);
	}
	if ($tipo==='EFE'||$tipo==='DEP') {
		$data=$obBD_con_get->getArrayConsulta(4,array('Ban_Tip'=>'C'),$obBD_conexion_get);
	}
	if($tipo==='CHE'||$tipo==='TRF'){
		$data=$obBD_con_get->getArrayConsulta(4,array('Ban_Tip'=>'B'),$obBD_conexion_get);
	}

	$resp['data']=$data;
	$obBD_con_get->echoJson($resp);
  if ($obBD_con_get->Error == 0) {
    $resp['success'] = true;
  }

  $obBD_con_get->echoJson($resp);
  exit();
}

//Secci�n ajax para guardar un nuevo socio en la base de datos
if (isset($saveAnticipo)) {
  $obBD_con_get->validaCierrePeriodo('anticipos_clientes','Ant_Fec','Ant_Cod',$Ant_Fec,$Ant_Cod,$obBD_conexion_get);
  $obBD_con_set->debug(true);
  try {
    $response['success'] = false;
    $obBD_con_set->inicio_transaccion($obBD_conexion_set->conexion);

    $Pec_Cod=$obBD_con_get->getRowConsulta(10, $Ant_Fec, $obBD_conexion_get);

    if ($Tia_Cod != $Tia_Cod_temp) {
      $var_mes = explode('-', $Ant_Fec);
      $Com_Num = $obBD_con_get->codigoComprAuto($Tia_Cod, $Pec_Cod['Pec_Cod'], $var_mes[1], $obBD_conexion_get);
    }

    //Actualizamos un comprobante
    $obBD_con_set->operacionobBD(30, array('Com_Cod'=>$Com_Cod, 'Pec_Cod'=>$Pec_Cod['Pec_Cod'], 'Com_Num'=>$Com_Num, 'Com_Fec'=>$Ant_Fec, 'Com_Con'=>$Ant_Obs, 'Com_Val'=>$Ant_Val, 'Tia_Cod'=>$Tia_Cod), $obBD_conexion_set);

    //Actualizar anticipo
    $obBD_con_set->operacionobBD(31, array('Ant_Fec'=>$Ant_Fec,'Ant_Val'=>$Ant_Val,'Ant_Obs'=>$Ant_Obs,'Ant_Cod'=>$Ant_Cod), $obBD_conexion_set);

    //eliminamos todos los asientos que esten ligados a este anticipo y no sean un cheque protestado
    $obBD_con_set->operacionobBD(34, array('Com_Cod'=>$Com_Cod), $obBD_conexion_set);

    // insertamos los nuevos pagos y sus respectivos asientos
    foreach ($pago_anticipo_clientes as $pago) {
      if ($pago['grid_tipp']=='pago') {
        // insertamos un asiento por cada pago
        $obBD_con_set->operacionobBD(13, array('Com_Cod'=>$Com_Cod, 'Asi_Deh'=>'D', 'Asi_Glo'=>$pago['Glosa'], 'Asi_Val'=>$pago['Debe'], 'Pld_Cod'=>$pago['Pld_Cod']), $obBD_conexion_set);
        $ultimo_asiento = $obBD_con_set->insercionid ($obBD_conexion_set);

        if ($pago['Pag_Abr']=='CHE') {
          // insertamos un registro en la tabla cheques_ext
          $obBD_con_set->operacionobBD(15, array('Bak_Cod'=>$pago['Ban_Cod'],'Cli_Cod'=>$Cli_Cod,'Che_Cta'=>$pago['Pac_Cto'],'Che_Num'=>$pago['Che_Num'],'Che_Fec'=>$pago['Che_Fec'],'Che_Val'=>$pago['Debe'],'Che_Obs'=>$Ant_Obs,'Che_Cli'=>$nombre), $obBD_conexion_set);
          $ultimo_Cheque = $obBD_con_set->insercionid ($obBD_conexion_set);

          // insertamos un pago de anticipo a clientes
          $obBD_con_set->operacionobBD(14, array('Pac_Num'=>$pago['Pac_Num'],'Pac_Cto'=>$pago['Pac_Cto'],'Pac_Ctd'=>$pago['Pac_Ctd'],'Pac_Val'=>$pago['Debe'],'Ant_Cod'=>$Ant_Cod,'Che_Cod'=>$ultimo_Cheque,'Pag_Cod'=>$pago['Pag_Cod'],'Asi_Cod'=>$ultimo_asiento), $obBD_conexion_set);
        }else{
          // insertamos un pago de anticipo a proveedores
          $obBD_con_set->operacionobBD(14, array('Pac_Num'=>$pago['Pac_Num'],'Pac_Cto'=>$pago['Pac_Cto'],'Pac_Ctd'=>$pago['Pac_Ctd'],'Pac_Val'=>$pago['Debe'],'Ant_Cod'=>$Ant_Cod,'Che_Cod'=>'null','Pag_Cod'=>$pago['Pag_Cod'],'Asi_Cod'=>$ultimo_asiento), $obBD_conexion_set);
        }
      } elseif ($pago['grid_tipp']=='inicial') {
        $Pld_Cod_ini = $obBD_con_get->getRowConsulta(3, "", $obBD_conexion_get);

        // insertamos un asiento por cada pago
        $obBD_con_set->operacionobBD(13, array('Com_Cod'=>$Com_Cod,'Asi_Deh'=>'H','Asi_Glo'=>$pago['Glosa'],'Asi_Val'=>$pago['Haber'],'Pld_Cod'=>$Pld_Cod_ini['Pld_Cod']), $obBD_conexion_set);
      }
    }
    $response['link']="../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$Com_Cod&tabla=clientes&campo=Cli_Cod&tipo=$Tia_Cod&Pec_Cod=".$Pec_Cod['Pec_Cod'];

    // throw new Exception('Prueba SQL!');

    if ($obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set)) {
       $response['success'] = true;
    }else{
		$response['error']=$obBD_con_set->MsgError;
	}
  } catch (Exception $e) {
    $obBD_con_set->rollBack_nomsn($obBD_conexion_set);
    $response['success']=false;
	$response['message']= '<span class="red">ERROR:</span> '.$e->getMessage();

  }
  $obBD_con_set->echoJson($response);
  exit();
}

//protestar el chueque seleccionado asignando un contraasiento para dicho cheque
if(isset($protestarChe)){

  //$obBD_con_set->debug(true);
  try {
    $response['success'] = false;
    $hoy = date("Y-m-d");
    $Pec_Cod=$obBD_con_get->getRowConsulta(26, $hoy, $obBD_conexion_get);
    //se retorna un mensaje que notifica dicho conflicto
    if (count($Pec_Cod) > 0)
    {
      $response['pec_ban']="si";
      $var_mes = explode('-', $hoy);

      $tipo_asien_prt = $obBD_con_get->getRowConsulta(25, "", $obBD_conexion_get);
      $Tia_Cod=$tipo_asien_prt['Tia_Cod'];

      $Com_Num = $obBD_con_get->codigoComprAuto($Tia_Cod, $Pec_Cod['Pec_Cod'], $var_mes[1], $obBD_conexion_get);

      $obBD_con_set->inicio_transaccion($obBD_conexion_set->conexion);

      $obBD_con_set->operacionobBD(27, array('Che_Cod'=>$row['Che_Cod']), $obBD_conexion_set);
      $obBD_con_set->operacionobBD(28, array('Pac_Obs'=>'CHEQUE No.'.$row['Che_Num'].' PROTESTADO','Pac_Cod'=>$row['Pac_Cod']), $obBD_conexion_set);

      //modificar asientos que sean de un cheque protestado
      $obBD_con_set->operacionobBD(29, array('Asi_Cod'=>$row['Asi_Cod'],'Asi_Glo'=>"CHEQUE No. ".$row['Che_Num']." protestado"), $obBD_conexion_set);

      //insertamos un comprobante y extraemos el id ingresado
      $obBD_con_set->operacionobBD(11, array('Pec_Cod'=>$Pec_Cod['Pec_Cod'], 'Cli_Cod'=>$Cli_Cod_prt, 'Com_Num'=>$Com_Num, 'Com_Fec'=>$hoy, 'Com_Con'=>"CHEQUE No. ".$row['Che_Num']." protestado", 'Com_Val'=>$row['Che_Val'], 'Tia_Cod'=>$Tia_Cod), $obBD_conexion_set);
      $ultimo_comprobante = $obBD_con_set->insercionid ($obBD_conexion_set);

      // insertamos un asiento inical Para el cheque protestado
      $Pld_Cod_ini = $obBD_con_get->getRowConsulta(3, "", $obBD_conexion_get);

      // insertamos un asiento por cada pago
      $obBD_con_set->operacionobBD(13, array('Com_Cod'=>$ultimo_comprobante,'Asi_Deh'=>'H','Asi_Glo'=>"CHEQUES PROTESTADOS",'Asi_Val'=>$row['Che_Val'],'Pld_Cod'=>$Pld_Cod_ini['Pld_Cod']), $obBD_conexion_set);
      $obBD_con_set->operacionobBD(13, array('Com_Cod'=>$ultimo_comprobante,'Asi_Deh'=>'D','Asi_Glo'=>"CHEQUE No. ".$row['Che_Num']." protestado",'Asi_Val'=>$row['Che_Val'],'Pld_Cod'=>$row['Pld_Cod']), $obBD_conexion_set);

      $response['link']="../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo_comprobante&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Cod&Pec_Cod=".$Pec_Cod['Pec_Cod'];

      $obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set->conexion);
    } else {
      $response['message'] = "Advertencia: Hace falta un periodo contable para el a�o actual";
      $response['pec_ban']="no";
    }

    //en caso de existir error ne las transacciones a la base de datos
    if ($obBD_con_set->Error == 0) {
      $response['success'] = true;
    }
  } catch (Exception $e) {
    $obBD_con_set->rollBack_nomsn($obBD_conexion_set);
    $response['success']=false;$response['message']= '<span class="red">ERROR:</span> '.$e->getMessage();
  }
  $obBD_con_set->echoJson($response);
  exit();
}

?>

<!DOCTYPE html>
<HTML>
<HEAD>
	<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
   <script src="../VALIDACIONES/tes_val_anticipo_cli_1.2.js?a=4"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<style>
	.pagination>li>a, .pagination>li>span {padding: 4px 2px;}
	.pagination {/*display: block;*/margin:0;padding: 0;}
	.chosen-default span,.chosen-single span{color:#555;}
	.chosen-single span{padding-left: 5px;}
</style>
</HEAD>
<BODY>
	<div class="panel panel-main">
		<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;Modificar Anticipos de clientes</h3></div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div id="documentoSearch">
				<div class="row">
					<form name="searchAnticipos" id="searchAnticipos" method="get" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchAnticipos','anticiposAjax');">
						<div class="col-xs-5">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">B&uacute;squeda</legend>
								<div class="form-group">
									<label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
									<div class="col-xs-10 radioset opt_search">
										<input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">Cliente</label>
										<input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radsc2">C&eacute;dula/RUC</label>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-2 control-label">B&uacute;squeda:</label>
									<div class="col-xs-7" >
										<div class="input-group">
											<input name="search" onkeydown="if (event.keyCode === 13)
											this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus  class="form-control input-xs clearable submit"/>
											<span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Documento"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
										</div><!-- /input-group -->
									</div><input type="text" tabindex="-1" style="display:none;" />
								</div>

							</fieldset>
						</div>
						<div class="col-sm-7">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">Filtros</legend>
								<div class="form-group">
									<label class="col-sm-2 control-label label-xs">Periodo:</label>
									<div class="col-sm-4">
										<select class="form-control input-xs" id="periodos" name="periodos" onchange="cambioPreiodoSearch('peri')"  required="">
                      <?php
                      $periodo_mm = $obBD_con_get->getRowConsulta(8, "", $obBD_conexion_get);
                      echo "<option value='ini' data-inicio='$periodo_mm[minimo]' data-fin='$periodo_mm[maximo]'><< TODOS >></option>";

                      $periodos_rows = $obBD_con_get->getArrayConsulta(16, "", $obBD_conexion_get);
                      if (count($periodos_rows) > 0)
                      {
                        foreach($periodos_rows as $row){
                          echo "<option value='$row[Pec_Cod]' data-inicio='$row[Pec_Fei]' data-fin='$row[Pec_Fef]'>$row[anio]</option>";
                        }
                      }
                      ?>

										</select>
									</div>
									<div class="col-sm-6">
										<div class="input-group input-group-xs">
											<span class="input-group-addon bold alert-info">Desde:</span>
											<input onchange="cambioPreiodoSearch('txt')" name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="form-control input-xs datepicker databind" style="text-align: center;"/>
											<span class="input-group-addon bold alert-info">Hasta:</span>
											<input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="form-control input-xs datepicker databind" style="text-align: center;"/>
										</div>
									</div>
								</div>
							</fieldset>
						</div>
					</form>
					<div class="col-xs-12" style="min-height: 360px;">
						<table id="searchGrid" name="searchGrid"></table>
						<table id="searchGridPager"></table>
					</div>
				</div>
			</div>
      <div id="documentoUpdate" hidden="true">
        <div class="row">
          <div class="col-sm-12">
            <form class="form-horizontal normal" id="AnticipoCliForm" method="post" action="javascript:$.createDialogConfirm('�Est&aacute; seguro que desea guardar los datos?', null,guardar_anticipo)">
                <div class="col-sm-12">
                <div class="row">
                  <div class="col-sm-6">
                    <fieldset class="exa-fieldset" >
                      <legend class="Titulos2">Datos del Cliente</legend>
                      <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required">C&eacute;dula/RUC:</label>
                        <div class="col-sm-6" >
                          <input name="bandera_prov" id="bandera_prov" type="text" value="nosel" style="display:none;" />
                          <input name="Prs_Cod" id="Prs_Cod" type="text" style="display:none;" />
                          <input name="Cli_Cod" id="Cli_Cod"  type="text" style="display:none;" />
                          <input name="Com_Cod" id="Com_Cod"  type="text" style="display:none;" />
                          <input name="Ant_Cod" id="Ant_Cod"  type="text" style="display:none;" />
                          <input name="op_opciones" type="text" value="c" style="display: none;"/>
                          <input name="Ant_Val" id="Ant_Val" type="text" value="0.00" style="display: none;"/>
                          <input name="Prs_Ced" id="Prs_Ced" type="text" class="form-control input-sm" tabindex="1" required="" readonly/>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-4 control-label label-sm">Cliente:</label>
                        <div class="col-sm-6" ><input name="nombre" id="nombre" class="form-control input-sm databind datatitle" readonly/></div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-4 control-label label-sm">Direcci&oacute;n:</label>
                        <div class="col-sm-6" ><input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-sm databind datatitle" readonly/></div>
                      </div>
                    </fieldset>
                  </div>
                  <div class="col-sm-6">
                    <fieldset class="exa-fieldset" >
                      <legend class="Titulos2">Datos del Anticipo</legend>
                      <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required">Fecha:</label>
                        <div class="col-sm-6" >
                          <div class="input-group">
                            <input name="Ant_Fec" type="text" id="Ant_Fec" size="10" class="form-control input-sm datepicker" required="" />
                            <span class="input-group-addon">
                              <span class="glyphicon glyphicon-calendar"></span>
                            </span>
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required">Tipo de Asiento:</label>
                        <div class="col-sm-6" >
                          <input type="text" name="Tia_Cod_temp" id="Tia_Cod_temp" hidden>
                          <input type="text" name="Com_Num" id="Com_Num" hidden>
                          <select id="Tia_Cod" name="Tia_Cod" class="form-control input-sm readOnly" required="">
                            <?php $rows_tipo_asiento = $obBD_con_get->getArrayConsulta(7, "", $obBD_conexion_get);
                            if (count($rows_tipo_asiento) > 0)
                            {
                              foreach($rows_tipo_asiento as $row){
                                echo "<option value='$row[Tia_Cod]'>$row[Tia_Abr] - $row[Tia_Des]</option>";
                              }
                            }?>
                          </select>
                        </div>
                      </div>

                      <div class="form-group">
                        <label class="col-sm-4 control-label label-sm ">Observaci&oacute;n:</label>
                        <div class="col-sm-6" >
                          <!-- <div class="input-group input-group-sm"> -->
                          <textarea class="form-control" id="Ant_Obs" val="" name="Ant_Obs" rows="2"></textarea>
                          <!-- </div> -->
                        </div>
                      </div>
                    </fieldset>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-12">
            <div class="row">
              <div class="col-sm-12">
                <div id="contenedor_pagos" style="width: 100%;padding-top: 10px;">
                  <table id="pagos"></table>
                  <div id="pagosPager"></div>
                </div>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-sm-12">
                <button class="btn btn-sm btn-danger no" onclick="moveToList();limpiarFormAnticipos();"><i class="glyphicon glyphicon-arrow-left"></i> Cancelar</button>
                <button class="btn btn-sm btn-success no" onclick="preguardadopagos();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
              </div>
            </div>
          </div>
        </div>
      </div>
	</div>
</div>

<div id="verPagosDialogMod" title="Pago">
	<div class="row">
		<div class="col-sm-12">
			<fieldset class="exa-fieldset">
				<legend class="Titulos2">Datos del Anticipo</legend>
				<form id="verPagosForm" class="form-horizontal normal">
					<div class="row">
						<div class="col-sm-7">
							<div class="form-group">
								<label class="col-xs-4 control-label label-xs">Proveedor:</label>
								<div class="col-xs-8" >
									<input type="text" id="prov_show" class="form-control input-xs" readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-4 control-label label-xs">No. Compr.:</label>
								<div class="col-xs-8" >
									<input type="text" id="compr_show" class="form-control input-xs" readonly>
								</div>
							</div>
						</div>
						<div class="col-sm-5">
							<div class="form-group">
								<label class="col-xs-4 control-label label-xs">C&eacute;dula/R.U.C.:</label>
								<div class="col-xs-8" >
									<input type="text" id="ruc_show" class="form-control input-xs" readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-4 control-label label-xs">Fecha:</label>
								<div class="col-xs-8" >
									<input type="text" id="fec_show" class="form-control input-xs" readonly>
								</div>
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
				<legend class="Titulos2">Observaci&oacute;n</legend>
				<div class="form-group">
					<div class="col-xs-12" >
            <input type="text" name="Cli_Cod_prt" id="Cli_Cod_prt" hidden>
						<textarea id="obs_show" class="form-control input-xs" readonly></textarea>
					</div>
				</div>
			</fieldset>
		</div>
	</div>
	<br>
	<div class="row">
		<div class="col-sm-12">
			<div id="tabs_ant_det" class="ui-tab-fix">
				<ul style="font-size: 12px;" role="tablist">
					<li id="ant_detasi"><a href="#ant_det_asi">Asientos</a></li>
					<li id="ant_detche"><a href="#ant_det_che">Cheques</a></li>
				</ul>
				<div id="ant_det_asi">
					<div class="row">
						<div class="col-sm-12" style="padding-top: 10px;">
							<table id="showPagosAsi" name="showPagosAsi"></table>
						</div>
					</div>
				</div>
				<div id="ant_det_che">
          <div class="row">
            <div class="col-sm-12" style="padding-top: 10px;">
							<table id="showPagosChe" name="showPagosChe"></table>
              <div class="Titulos2">
                <span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop red"></span> Cheques protestados </span>
              </div>
						</div>
          </div>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- dialogo de registro de pagos de anticipo -->
<div id="pagosDialog" title="Agregar Pagos">
  <form id="pagosForm" class="form-horizontal normal">
  <div class="form-group">
    <label class="col-xs-4 control-label label-xs required">Tipo:</label>
    <div class="col-xs-6" >
      <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs readOnly" onchange="cambiarCamposPagos($(this).find(':selected').data().class, $('#Pag_Cod option:selected').attr('data-abr'))" required="">
        <?php $rows_tipo_pago = $obBD_con_get->getArrayConsulta(2, "", $obBD_conexion_get);
        if (count($rows_tipo_pago) > 0)
        {
          foreach($rows_tipo_pago as $row){
            echo "<option value='$row[Pag_Cod]' data-abr='$row[Pag_Abr]' data-class='$row[Pag_Des]' >$row[Pag_Des]</option>";
          }
        }?>
      </select>
    </div>
  </div>

  <!-- Bancos de DataBase -->
  <div class="form-group Transferencia Efectivo Deposito">
    <label class="col-xs-4 control-label label-xs required">Cuenta:</label>
    <div class="col-xs-6" >
      <select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly" required="">
      </select>
    </div>
  </div>

  <div class="form-group Cheque">
    <label class="col-xs-4 control-label label-xs required">Banco:</label>
    <div class="col-xs-6" >
      <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs readOnly" required="">
      </select>
    </div>
  </div>

  <div class="form-group  Cheque Transferencia Efectivo Otros" >
    <label class="col-xs-4 control-label label-xs">No. Docum.:</label>
    <div class="col-xs-6">
      <input type="text" id="Pac_Num" name="Pac_Num" onchange="" onkeypress="return soloNumeros(event)" class="form-control input-xs">
    </div>
  </div>

  <div class="form-group  Cheque Transferencia" >
    <label class="col-xs-4 control-label label-xs required">Cta. Origen:</label>
    <div class="col-xs-6">
      <input type="text" id="Pac_Cto" name="Pac_Cto" onchange="" onkeypress="return soloNumeros(event)" class="form-control input-xs">
    </div>
  </div>

  <div class="form-group Cheque">
    <label class="col-xs-4 control-label label-xs required">Fecha:</label>
    <div class="col-xs-6" >
      <input name="Che_Fec" type="text" id="Che_Fec" size="10" class="form-control input-xs datepicker" required="" />
    </div>
  </div>

  <div class="form-group Cheque" >
    <label class="col-xs-4 control-label label-xs required">No. cheque:</label>
    <div class="col-xs-6">
      <div class="input-group input-group-xs">
        <span class="input-group-addon"><i id="indicadorChe" class=""></i></span>
        <input type="text" id="Che_Num" name="Che_Num" onchange="" class="form-control input-xs" onkeyup="verificarNoCheque(this.value)" onkeypress="return soloNumeros(event)">
      </div>
    </div>
  </div>


  <div class="form-group Transferencia Deposito Efectivo Cheque Otros">
    <label class="col-xs-4 control-label label-sm required">Valor:</label>
    <div class="col-xs-6 ">
      <div class="input-group input-group-xs">
        <span class="input-group-addon"><i id="indicadorChe" class="glyphicon glyphicon-usd"></i></span>
        <input name="Pac_Val" type="text" id="Pac_Val" size="10" class="form-control input-xs" required="" autocomplete="off" onchange="cambioValPago($(this));" onkeypress="return  validar_decimal(event)"/>
      </div>
    </div>
  </div>

  <div class="form-group center">
    </br>
    <a class="btn btn-sm btn-primary" onclick="AgregarPago()"><i class="glyphicon glyphicon-floppy-disk"></i> Agregar</a>
  </div>
  </form>
</div>

<div id="successDialog"  title="Mensaje del Sistema">
  <center><h2>El Comprobante se ha registrado con Exito!</h2></center>
  <center>
    <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-danger fileinput-button" style="display: inline;" >
      <i class="icon-ban-circle icon-white"></i>
      <span>Cerrar</span>
    </button>
    <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-success start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></span> </a>
  </center>
</div>

<div id="verPagosDialog" title="Pago">
  <form id="verPagosForm" class="form-horizontal normal">
    <div class="form-group">
      <label class="col-xs-4 control-label label-xs">Tipo de pago:</label>
      <div class="col-xs-6" >
        <input type="text" id="pago_ver" class="form-control input-xs" readonly>
      </div>
    </div>

    <div class="form-group Cheque" >
      <label class="col-xs-4 control-label label-xs">No. cheque:</label>
      <div class="col-xs-6">
        <input type="text" id="numero_ver" class="form-control input-xs" readonly>
      </div>
    </div>

    <!-- Bancos de DataBase -->
    <div class="form-group Cheque Transferencia">
      <label class="col-xs-4 control-label label-xs">Cuenta:</label>
      <div class="col-xs-6" >
        <input type="text" id="cuenta_ver" class="form-control input-xs" readonly>
      </div>
    </div>

    <div class="form-group  Deposito Transferencia" >
      <label class="col-xs-4 control-label label-xs">Cta. Destino:</label>
      <div class="col-xs-6">
        <input type="text" id="destino_ver" class="form-control input-xs" readonly>
      </div>
    </div>

    <div class="form-group Cheque">
      <label class="col-xs-4 control-label label-xs">Fecha:</label>
      <div class="col-xs-6" >
        <input type="text" id="fecha_ver" class="form-control input-xs" readonly>
      </div>
    </div>

    <div class="form-group Transferencia Deposito Efectivo Cheque">
      <label class="col-xs-4 control-label label-sm">Valor:</label>
      <div class="col-xs-6 ">
        <input type="text" id="valor_ver" class="form-control input-xs" readonly>
      </div>
    </div>

    <div class="form-group center">
      </br>
      <a class="btn btn-sm btn-primary" onclick="$('#verPagosDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cerrar</a>
    </div>
  </form>
</div>

<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
</BODY>
</html>
