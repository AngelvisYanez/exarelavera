<?php
/**
*
* @abstract Permite realizar la modificacion de Anticipos Manuales
* @author Erik Cordova
* @version 1.0
* Fecha de creacion  2017-12-06
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anticipo_prv.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Ant_Prv($Ses_Dat_Dis);
/**
* Creaciï¿½n del Objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Ant_Prv;

$hoy = date("Y-m-d");
$mes = date("m");

// obtenemos los proveedores y sus anticipos
if (isset($anticiposAjax)) {

  $obBD_con1->getPageGridJson(14,$_GET, $obBD_conexion, true);
}

//obtenemos todas las aportaciones de un socio
if(isset($anticiposDetAjax)){
  $responce['rows'] = $obBD_con1->getArrayConsulta(15, array('Prv_Cod'=>$anticiposDetAjax,'txt_fec_ini'=>$txt_fec_ini,'txt_fec_fin'=>$txt_fec_fin), $obBD_conexion, true);

  $responce['records']=count($responce['rows']);

  $responce['records']=count($responce['rows']);
  $obBD_con1->echoJson($responce);
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

  $Pld_Cod_ini = $obBD_con1->getRowConsulta(3, "", $obBD_conexion);

  $response['data'] = $obBD_con1->getArrayConsulta(31, $Com_Cod, $obBD_conexion);

  $response['data_che'] = $obBD_con1->getArrayConsulta(18, array('Prv_Cod' => $Prv_Cod,'Atp_Cod' => $Atp_Cod ), $obBD_conexion,true);

	if ($obBD_con1->Error == 0) {
    $response['success'] = true;
  }

  $obBD_con1->echoJson($response);
  exit();
}

//Anular un anticipo con sus respectivos comprobante, pagos, asientos, cheques
if(isset($delAnticipo)){
	
  $obBD_con1->validaCierrePeriodo('anticipos_proveedores','Atp_Fec','Atp_Cod',$Atp_Fec,$Atp_Cod,$obBD_conexion);
  $response['success'] = false;
  $response['message'] = "No se ha logrado realizar la Transaccion";

  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

  $obBD_con1->operacionobBD(19, $Atp_Cod, $obBD_conexion);
  $obBD_con1->operacionobBD(29, $Com_Cod, $obBD_conexion);
  $obBD_con1->operacionobBD(30, $Atp_Cod, $obBD_conexion);

  $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

	if ($obBD_con1->Error == 0) {
    $response['success'] = true;
  }

  $obBD_con1->echoJson($response);
  exit();
}

if (isset($obtenerPeriodoMinMax)) {
  $resp['success'] = false;
  $resp['message'] = "No se ha logrado realizar la Transaccion";

  $resp['data']=$obBD_con1->getRowConsulta(11,"",$obBD_conexion);

	$resp['success']=true;
	$obBD_con1->echoJson($resp);
}

//obtenemos todas las aportaciones de un socio
if(isset($getAsientosAnticipoMod)){
	$response['success'] = false;
  $response['message'] = "No se ha logrado realizar la Transaccion";

  $response['data'] = $obBD_con1->getArrayConsulta(16, array('Com_Cod' => $Com_Cod ), $obBD_conexion);

	if ($obBD_con1->Error == 0) {
    $response['success'] = true;
  }

  $obBD_con1->echoJson($response);
  exit();
}

//verificamos si el numero de un cheque ya esta registrado dentro de la tabla cheques
if (isset($verificarCheNum)) {
  //Se obtiene el socio seleccionado
  $response['numero_che']=false;
  $num_Ches = $obBD_con1->getArrayConsulta(13, $Ban_Cod, $obBD_conexion);
  foreach ($num_Ches as $nch) {
    if($nch['Che_Num']==$Che_Num){
      $response['numero_che']=true;
    }
  }

  $obBD_con1->echoJson($response);
  exit();
}

if (isset($cargar_cuentas_pagos)) {
  $resp['bandera']=true;
  if ($tipo==='INICIAL') {
		$data=$obBD_con1->getRowConsulta(3, "",$obBD_conexion);
	}
	if ($tipo==='EFE'||$tipo==='DEP') {
		$data=$obBD_con1->getArrayConsulta(4,array('Ban_Tip'=>'C'),$obBD_conexion);
	}
	if($tipo==='CHE'||$tipo==='TRF'){
		$data=$obBD_con1->getArrayConsulta(4,array('Ban_Tip'=>'B'),$obBD_conexion);
	}

	$resp['data']=$data;
	//$obBD_con1->echoJson($resp);
  if ($obBD_con1->Error == 0) {
    $resp['success'] = true;
  }

  $obBD_con1->echoJson($resp);
  //exit();
}

//seccion para modificar anticipos
if (isset($saveAnticipo)) {
  $obBD_con1->validaCierrePeriodo('anticipos_proveedores','Atp_Fec','Atp_Cod',$Atp_Fec,$Atp_Cod,$obBD_conexion);
  // $obBD_con1->debug(true);
  try {
    $response['success'] = false;
    $response['arrayche']=array();
    $response['bnd_che'] = false;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $Pec_Cod=$obBD_con1->getRowConsulta(5, $Atp_Fec, $obBD_conexion);

    if ($Tia_Cod != $Tia_Cod_temp) {
      $var_mes = explode('-', $Atp_Fec);
      $Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod['Pec_Cod'], $var_mes[1], $obBD_conexion);
    }
    //Actualizamos un comprobante
    $obBD_con1->operacionobBD(22, array('Com_Cod'=>$Com_Cod, 'Pec_Cod'=>$Pec_Cod['Pec_Cod'], 'Com_Num'=>$Com_Num, 'Com_Fec'=>$Atp_Fec, 'Com_Con'=>$Atp_Obs, 'Com_Val'=>$Atp_Val, 'Tia_Cod'=>$Tia_Cod), $obBD_conexion);

    //Actualizar anticipo
    $obBD_con1->operacionobBD(21, array('Atp_Fec'=>$Atp_Fec,'Atp_Val'=>$Atp_Val,'Atp_Obs'=>$Atp_Obs,'Atp_Cod'=>$Atp_Cod), $obBD_conexion);

    //eliminamos todos los asientos que esten ligados a este anticipo y que no sean un cheque protestado
    $obBD_con1->operacionobBD(32, array('Com_Cod'=>$Com_Cod), $obBD_conexion);

    $contador_cheque=0;
    // insertamos los nuevos pagos y sus respectivos asientos
    foreach ($pago_anticipo_proveedores as $pago) {
      if ($pago['grid_tipp']=='pago') {
        // insertamos un asiento por cada pago
        $obBD_con1->operacionobBD(9, array('Com_Cod'=>$Com_Cod, 'Asi_Deh'=>'H', 'Asi_Glo'=>$pago['Glosa'], 'Asi_Val'=>$pago['Haber'], 'Pld_Cod'=>$pago['Pld_Cod']), $obBD_conexion);
        $ultimo_asiento = $obBD_con1->insercionid ($obBD_conexion);

        if ($pago['Pag_Abr']=='EFE' || $pago['Pag_Abr']=='DEP') {
          // insertamos un pago de anticipo a proveedores
          $obBD_con1->operacionobBD(8, array('Pap_Cto'=>'','Pap_Ctd'=>$pago['Pap_Ctd'],'Pap_Val'=>$pago['Haber'],'Atp_Cod'=>$Atp_Cod,'Pag_Cod'=>$pago['Pag_Cod'], 'Asi_Cod'=>$ultimo_asiento), $obBD_conexion);
        } else {
          // insertamos un pago de anticipo a proveedores
          $obBD_con1->operacionobBD(8, array('Pap_Cto'=>$pago['Pap_Cto'],'Pap_Ctd'=>$pago['Pap_Ctd'],'Pap_Val'=>$pago['Haber'],'Atp_Cod'=>$Atp_Cod,'Pag_Cod'=>$pago['Pag_Cod'], 'Asi_Cod'=>$ultimo_asiento), $obBD_conexion);
        }

        if ($pago['Pag_Abr']=='CHE') {
          $contador_cheque++;
          $response['bnd_che'] = true;
          array_push($response['arrayche'], array('link'=>"?codigo2=$contador_cheque&asi=".$ultimo_asiento."&ban=".$pago['Ban_Cod']."&pro=".$Prv_Cod,'che'=>"No.:".$pago['Che_Num']." - Valor:$ ".$pago['Haber']));
          // insertamos un registro en la tabla cheque
          $obBD_con1->operacionobBD(12, array('Che_Cod'=>$contador_cheque,'Prv_Cod'=>$Prv_Cod,'Ban_Cod'=>$pago['Ban_Cod'],'Asi_Cod'=>$ultimo_asiento,'Che_Num'=>$pago['Che_Num'],'Che_Fec'=>$pago['Che_Fec'],'Che_Val'=>$pago['Haber'],'Che_Obs'=>$Atp_Obs,'Che_Ben'=>$nombre), $obBD_conexion);
        }
      } elseif ($pago['grid_tipp']=='inicial'){
        $Pld_Cod_ini = $obBD_con1->getRowConsulta(3, "", $obBD_conexion);

        // insertamos un asiento inical con el total de los pagos
        $obBD_con1->operacionobBD(9, array('Com_Cod'=>$Com_Cod,'Asi_Deh'=>'D','Asi_Glo'=>$pago['Glosa'],'Asi_Val'=>$pago['Debe'],'Pld_Cod'=>$Pld_Cod_ini['Pld_Cod']), $obBD_conexion);
      }
    }
    $Pec_Cod_val=$Pec_Cod['Pec_Cod'];
    $response['link']="../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$Com_Cod&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod_val";

    // throw new Exception('Prueba SQL!');
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
      $response['success'] = true;
    }
  } catch (Exception $e) {
    $obBD_con1->rollBack_nomsn($obBD_conexion);
    $response['success']=false;$response['message']= '<span class="red">ERROR:</span> '.$e->getMessage();
  }
  $obBD_con1->echoJson($response);
  exit();
}

//protestar el chueque seleccionado asignando un contraasiento para dicho cheque
if(isset($protestarChe)){

  $response['success'] = false;
  $response['message'] = "No se ha logrado realizar la Transacci&oacute;n";

  $hoy = date("Y-m-d");
  $Pec_Cod=$obBD_con1->getRowConsulta(5, $hoy, $obBD_conexion, true);

  //en caso de no existir un periodo contable para la fecha en la que se protesta el cheque
  //se retorna un mensaje que notifica dicho conflicto
  if (count($Pec_Cod) > 0)
  {
    $response['pec_ban']="si";
    $var_mes = explode('-', $hoy);

    $tipo_asien_prt = $obBD_con1->getRowConsulta(28, "", $obBD_conexion);
    $Tia_Cod=$tipo_asien_prt['Tia_Cod'];

    $Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod['Pec_Cod'], $var_mes[1], $obBD_conexion);

    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

    $obBD_con1->operacionobBD(24, array('Asi_Cod'=>$row['Asi_Cod']), $obBD_conexion);
    $obBD_con1->operacionobBD(25, array('Pap_Obs'=>'CHEQUE No.'.$row['Che_Num'].' PROTESTADO','Pap_Cod'=>$row['Pap_Cod']), $obBD_conexion);

    //modificar asientos que sean de un cheque protestado
    $obBD_con1->operacionobBD(27, array('Asi_Cod'=>$row['Asi_Cod'],'Asi_Glo'=>"CHEQUE No. ".$row['Che_Num']." protestado"), $obBD_conexion);

    //insertamos un comprobante y extraemos el id ingresado
    $obBD_con1->operacionobBD(6, array('Pec_Cod'=>$Pec_Cod['Pec_Cod'], 'Prv_Cod'=>$row['Prv_Cod'], 'Com_Num'=>$Com_Num, 'Com_Fec'=>$hoy, 'Com_Con'=>"CHEQUE No. ".$row['Che_Num']." protestado", 'Com_Val'=>$row['Che_Val'], 'Tia_Cod'=>$row['Tia_Cod']), $obBD_conexion);
    $ultimo_comprobate = $obBD_con1->insercionid ($obBD_conexion);

    // insertamos un asiento inical Para el cheque protestado
    $Pld_Cod_ini = $obBD_con1->getRowConsulta(3, "", $obBD_conexion);
    $obBD_con1->operacionobBD(9, array('Com_Cod'=>$ultimo_comprobate,'Asi_Deh'=>'H','Asi_Glo'=>"CHEQUES PROTESTADOS",'Asi_Val'=>$row['Che_Val'],'Pld_Cod'=>$Pld_Cod_ini['Pld_Cod']), $obBD_conexion);
    $obBD_con1->operacionobBD(9, array('Com_Cod'=>$ultimo_comprobate,'Asi_Deh'=>'D','Asi_Glo'=>"CHEQUE No. ".$row['Che_Num']." protestado",'Asi_Val'=>$row['Che_Val'],'Pld_Cod'=>$row['Pld_Cod']), $obBD_conexion);

    $Pec_Cod_val=$Pec_Cod['Pec_Cod'];
    $response['link']="../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo_comprobate&tabla=proveedore&campo=Prv_Cod&tipo=".$row['Tia_Cod']."&Pec_Cod=$Pec_Cod_val";

    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
  } else {
    $response['message'] = "Advertencia: Hace falta un periodo contable para el a�o actual";
    $response['pec_ban']="no";
  }

  //en caso de existir error ne las transacciones a la base de datos
	if ($obBD_con1->Error == 0) {
    $response['success'] = true;
  }

  $obBD_con1->echoJson($response);
  exit();
}

?>

<!DOCTYPE html>
<HTML>

<HEAD>
	<TITLE>
		<?Php echo $Ses_Sys_Nom; ?>
	</TITLE>
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script src="../VALIDACIONES/tes_val_anticipo_mod_prv.js?a=22"></script>
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
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;Modificar Anticipos a Proveedores</h3></div>
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
                                            <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">Proveedor</label>
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
	</div>
	<input type="text" tabindex="-1" style="display:none;" />
	</div>

	</fieldset>
	</div>
	<div class="col-sm-7">
		<fieldset class="exa-fieldset">
			<legend class="Titulos2">Filtros</legend>
			<div class="form-group">
				<label class="col-sm-2 control-label label-xs">Periodo:</label>
				<div class="col-sm-4">
					<select class="form-control input-xs" id="periodos" name="periodos" onchange="cambioPreiodoSearch('peri')" required="">
						<?php
                                                $periodo_mm = $obBD_con1->getRowConsulta(11, "", $obBD_conexion);
                                                echo "<option value='ini' data-inicio='$periodo_mm[minimo]' data-fin='$periodo_mm[maximo]'><< TODOS >></option>";

                                                $periodos_rows = $obBD_con1->getArrayConsulta(20, "", $obBD_conexion);
                                                if (count($periodos_rows) > 0) {
                                                    foreach ($periodos_rows as $row) {
                                                        echo "<option value='$row[Pec_Cod]' data-inicio='$row[Pec_Fei]' data-fin='$row[Pec_Fef]'>$row[anio]</option>";
                                                    }
                                                }
                                                ?>

					</select>
				</div>
				<div class="col-sm-6">
					<div class="input-group input-group-xs">
						<span class="input-group-addon bold alert-info">Desde:</span>
						<input onchange="cambioPreiodoSearch('txt')" name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="form-control input-xs datepicker databind"
						 style="text-align: center;" />
						<span class="input-group-addon bold alert-info">Hasta:</span>
						<input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="form-control input-xs datepicker databind" style="text-align: center;"
						/>
					</div>
				</div>
			</div>
		</fieldset>
	</div>

	</form>
	<div class="col-xs-12" style="min-height: 360px;">
		<table id="searchGrid" name="searchGrid"></table>
		<table id="searchGridPager"></table>
		<br>
		<button onclick="window.open('tes_pri_anticipo_Prov.php?Suc_Cod=<?php echo $Ses_Suc_Cod; ?>&' + $.param($('#searchAnticipos').getData()));"
		 type="button" title="En prueba..." class="btn btn-primary start hidden">
			<i class="glyphicon glyphicon-print"></i>
			<span> Imprimir</span>
		</button>
	</div>

	</div>
	</div>
	<div id="documentoUpdate" hidden="true">
		<div class="row">
			<div class="col-sm-12">
				<form class="form-horizontal normal" id="AnticipoPrvForm" method="post" action="javascript:$.createDialogConfirm('�Est&aacute; seguro que desea guardar los datos?',null,guardar_anticipo)">
					<div class="col-sm-12">
						<div class="row">
							<div class="col-sm-6">
								<fieldset class="exa-fieldset">
									<legend class="Titulos2">Datos del Proveedor</legend>
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm required">C&eacute;dula/RUC:</label>
										<div class="col-sm-6">
											<input name="bandera_prov" id="bandera_prov" type="text" value="nosel" style="display:none;" />
											<input name="Prs_Cod" id="Prs_Cod" type="text" style="display:none;" />
											<input name="Prv_Cod" id="Prv_Cod" type="text" style="display:none;" />
											<input name="Com_Cod" id="Com_Cod" type="text" style="display:none;" />
											<input name="Atp_Cod" id="Atp_Cod" type="text" style="display:none;" />
											<input name="op_opciones" type="text" value="c" style="display: none;" />
											<input name="Atp_Val" id="Atp_Val" type="text" value="0.00" style="display: none;" />
											<input name="Prs_Ced" id="Prs_Ced" type="text" class="form-control input-sm" tabindex="1" required="" readonly/>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm">Proveedor:</label>
										<div class="col-sm-6">
											<input name="nombre" id="nombre" class="form-control input-sm databind datatitle" readonly/>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm">Direcci&oacute;n:</label>
										<div class="col-sm-6">
											<input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-sm databind datatitle" readonly/>
										</div>
									</div>
								</fieldset>
							</div>
							<div class="col-sm-6">
								<fieldset class="exa-fieldset">
									<legend class="Titulos2">Datos del Anticipo</legend>
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm required">Fecha:</label>
										<div class="col-sm-6">
											<div class="input-group">
												<input name="Atp_Fec" type="text" id="Atp_Fec" size="10" class="form-control input-sm datepicker" required="" />
												<span class="input-group-addon">
													<span class="glyphicon glyphicon-calendar"></span>
												</span>
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label label-sm required">Tipo de Asiento:</label>
										<div class="col-sm-6">
											<input type="text" name="Tia_Cod_temp" id="Tia_Cod_temp" hidden>
											<input type="text" name="Com_Num" id="Com_Num" hidden>
											<select id="Tia_Cod" name="Tia_Cod" class="form-control input-sm readOnly" required="">
												<?php
                                                            $rows_tipo_asiento = $obBD_con1->getArrayConsulta(10, "", $obBD_conexion);
                                                            if (count($rows_tipo_asiento) > 0) {
                                                                foreach ($rows_tipo_asiento as $row) {
                                                                    echo "<option value='$row[Tia_Cod]'>$row[Tia_Abr] - $row[Tia_Des]</option>";
                                                                }
                                                            }
                                                            ?>
											</select>
										</div>
									</div>

									<div class="form-group">
										<label class="col-sm-4 control-label label-sm ">Observaci&oacute;n:</label>
										<div class="col-sm-6">
											<!-- <div class="input-group input-group-sm"> -->
											<textarea class="form-control" id="Atp_Obs" val="" name="Atp_Obs" rows="2"></textarea>
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
				<div class="row">
					<div class="col-sm-12">
						<button class="btn btn-sm btn-danger no" onclick="moveToList();limpiarFormAnticipos();">
							<i class="glyphicon glyphicon-arrow-left"></i> Cancelar</button>
						<button class="btn btn-sm btn-success no" onclick="preguardadopagos();">
							<i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
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
									<div class="col-xs-8">
										<input type="text" id="prov_show" class="form-control input-xs" readonly>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">No. Compr.:</label>
									<div class="col-xs-8">
										<input type="text" id="compr_show" class="form-control input-xs" readonly>
									</div>
								</div>
							</div>
							<div class="col-sm-5">
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">C&eacute;dula/R.U.C.:</label>
									<div class="col-xs-8">
										<input type="text" id="ruc_show" class="form-control input-xs" readonly>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">Fecha:</label>
									<div class="col-xs-8">
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
						<div class="col-xs-12">
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
						<li id="ant_detasi">
							<a href="#ant_det_asi">Asientos</a>
						</li>
						<li id="ant_detche">
							<a href="#ant_det_che">Cheques</a>
						</li>
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
									<span id="plan-footer">
										<strong>Leyenda:</strong>
										<span class="glyphicon glyphicon-stop red"></span> Cheques protestados </span>
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
				<div class="col-xs-6">
					<select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs readOnly" onchange="cambiarCamposPagos($(this).find(':selected').data().class, $('#Pag_Cod option:selected').attr('data-abr'))"
					 required="">
						<?php
                                    $rows_tipo_pago = $obBD_con1->getArrayConsulta(2, "", $obBD_conexion);
                                    if (count($rows_tipo_pago) > 0) {
                                        foreach ($rows_tipo_pago as $row) {
                                            echo "<option value='$row[Pag_Cod]' data-abr='$row[Pag_Abr]' data-class='$row[Pag_Des]' >$row[Pag_Des]</option>";
                                        }
                                    }
                                    ?>
					</select>
				</div>
			</div>

			<!-- Bancos de DataBase -->
			<div class="form-group Cheque Transferencia Efectivo Deposito">
				<label class="col-xs-4 control-label label-xs required">Cuenta:</label>
				<div class="col-xs-6">
					<select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly" required="">
					</select>
				</div>
			</div>

			<div class="form-group  Deposito Transferencia">
				<label class="col-xs-4 control-label label-xs required">Cta. Destino:</label>
				<div class="col-xs-6">
					<input type="text" id="Pap_Ctd" name="Pap_Ctd" onchange="" onkeypress="return soloNumeros(event)" class="form-control input-xs">
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs required">Fecha:</label>
				<div class="col-xs-6">
					<input name="Che_Fec" type="text" id="Che_Fec" size="10" class="form-control input-xs datepicker" required="" />
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs required">No. cheque:</label>
				<div class="col-xs-6">
					<div class="input-group input-group-xs">
						<span class="input-group-addon">
							<i id="indicadorChe" class=""></i>
						</span>
						<input type="text" id="Che_Num" name="Che_Num" onchange="" class="form-control input-xs" onkeyup="verificarNoCheque(this.value)"
						 onkeypress="return soloNumeros(event)">
					</div>
				</div>
			</div>


			<div class="form-group Transferencia Deposito Efectivo Cheque">
				<label class="col-xs-4 control-label label-sm required">Valor:</label>
				<div class="col-xs-6 ">
					<div class="input-group input-group-xs">
						<span class="input-group-addon">
							<i id="indicadorChe" class="glyphicon glyphicon-usd"></i>
						</span>
						<input name="Pap_Val" type="text" id="Pap_Val" size="10" class="form-control input-xs" required="" autocomplete="off" onkeypress="return  validar_decimal(event)"
						/>
					</div>
				</div>
			</div>

			<div class="form-group center">
				</br>
				<a class="btn btn-sm btn-primary" onclick="AgregarPago()">
					<i class="glyphicon glyphicon-floppy-disk"></i> Agregar</a>
			</div>
		</form>
	</div>

	<div id="successDialog" title="Mensaje del Sistema">
		<center>
			<h2>El Comprobante se ha registrado con Exito!</h2>
		</center>
		<center>
			<button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-danger fileinput-button" style="display: inline;">
				<i class="icon-ban-circle icon-white"></i>
				<span>Cerrar</span>
			</button>
			<a id="impCompr" target="_blank" href="" style="display: inline;" title="Imprimir Comprobante">
				<span class="btn btn-success start">
					<i class="icon-print icon-white"></i>
					<span>Imprimir</span>
				</span>
			</a>
			<br>
			<br>
			<fieldset class="exa-fieldset" id="siche" hidden>
				<legend class="Titulos2">Impresi&oacute;n de Cheques</legend>
				<div>
					<center>
						<h5>Eliga el cheque que desea imprimir!</h5>
					</center>
					<div class="row">
						<div class="form-group">
							<div class="col-sm-3"></div>
							<div class="col-sm-6">
								<div class="input-group">
									<select id="Che_imp" name="Che_imp" class="form-control input-xs" onchange="cambiarChe()">
									</select>
								</div>
							</div>
						</div>
					</div>
					<br>
					<div class="row">
						<?php $ruta = './' . (file_exists('cheques/' . $Ses_Emp_Cod) ? "cheques/$Ses_Emp_Cod/" : ''); ?>
						<div id="conten_bancos_imp">
							<table style="margin-bottom:10px;" cellpadding="1" border="1">
								<tr>
									<td align="center" class="ui-widget-content" colspan="6">
										<b>&nbsp; plantillas &nbsp;</b>
									</td>
								</tr>
								<tr id="impchetd">
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_mac_1.0.php" href="" target="_blank"
										 title="Banco de Machala">
											<img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_pac_1.0.php" href="" target="_blank"
										 title="Banco del Pacifico">
											<img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_rum_1.0.php" href="" target="_blank"
										 title="Banco del Rumiñahui">
											<img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_gua_1.0.php" href="" target="_blank"
										 title="Banco del Guayaquil">
											<img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_pch_1.0.php" href="" target="_blank"
										 title="Banco del Pichincha">
											<img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_int_1.0.php" href="" target="_blank"
										 title="Banco Internacional">
											<img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32" />
										</a>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</fieldset>
		</center>
	</div>

	<div id="verPagosDialog" title="Pago">
		<form id="verPagosForm" class="form-horizontal normal">
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs">Tipo de pago:</label>
				<div class="col-xs-6">
					<input type="text" id="pago_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs">No. cheque:</label>
				<div class="col-xs-6">
					<input type="text" id="numero_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<!-- Bancos de DataBase -->
			<div class="form-group Cheque Transferencia">
				<label class="col-xs-4 control-label label-xs">Cuenta:</label>
				<div class="col-xs-6">
					<input type="text" id="cuenta_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group  Deposito Transferencia">
				<label class="col-xs-4 control-label label-xs">Cta. Destino:</label>
				<div class="col-xs-6">
					<input type="text" id="destino_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs">Fecha:</label>
				<div class="col-xs-6">
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
				<a class="btn btn-sm btn-primary" onclick="$('#verPagosDialog').dialog('close');">
					<i class="glyphicon glyphicon-remove"></i> Cerrar</a>
			</div>
		</form>
	</div>

	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	</BODY>

</html>