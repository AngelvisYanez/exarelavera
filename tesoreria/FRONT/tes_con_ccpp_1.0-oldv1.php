<?php
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_ccpp_lotes_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Ccpp($Ses_Dat_Dis);
/* Creacion del Objeto para consultas */
$obBD_con1 =  new Class_Log_Datos_Ccpp;

$productores = $obBD_con1->getArrayConsulta(125, $Ses_Emp_Cod, $obBD_conexion);
/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(135, $Ses_Emp_Cod, $obBD_conexion);

//Seccion para obtener los proveedores registrados en la empresa
if (isset($proveedoresAjax)) {
  $obBD_con1->getPageGridJson(1,$_GET, $obBD_conexion);
}

// obtenemos los proveedores y sus pagos
if (isset($getFactsProvee)) {
  // $obBD_con1->getPageGridJson(44,$_GET, $obBD_conexion);
  $_GET['isnego'] = $configs['Cof_NegCam'];
  $responce['rows'] = $obBD_con1->getArrayConsulta(44, $_GET, $obBD_conexion);
  if($op_opciones!='T'){
    foreach ($responce['rows'] as $key => $item){
        if ($item['Abono'] != $item['Asi_Val']&&$op_opciones=='P') unset($responce['rows'][$key]);
        if ($item['Abono'] == $item['Asi_Val']&&$op_opciones=='I') unset($responce['rows'][$key]);
    }
  }
  //$responce['sqls']=ChromePhp::getJsonLog();
  $responce['rows']=array_values($responce['rows']);
  $responce['records']=count($responce['rows']);
  $obBD_con1->echoJson($responce);
  exit();
}

//obtenemos todas las aportaciones de un socio
if(isset($abonosDetAjax)){
  $responce['rows'] = $obBD_con1->getArrayConsulta(29, $abonosDetAjax, $obBD_conexion);

  $responce['records']=count($responce['rows']);

  $responce['records']=count($responce['rows']);
  $obBD_con1->echoJson($responce);
  exit();
}

//obtenemos todos los asientos y chuque de un abono
if(isset($getAsientosAbono)){
	$response['success'] = false;
  $response['message'] = "No se ha logrado realizar la Transaccion";

  $response['data'] = $obBD_con1->getArrayConsulta(30, array('Com_Cod' => $Com_Cod ), $obBD_conexion);
  $response['data_che'] = $obBD_con1->getArrayConsulta(31, array('Com_Cod' => $Com_Cod ), $obBD_conexion);

	if ($obBD_con1->Error == 0) {
    $response['success'] = true;
  }

  $obBD_con1->echoJson($response);
  exit();
}


if(isset($getReportAbono)){
	//$response['data']=array();
  ini_set("memory_limit", "-1");
  set_time_limit(0);
	$response['success'] = false;
  $response['message'] = "No se ha logrado realizar la Transaccion";

  /* Busqueda de proveedor o proveedores*/
  $data_prov = $obBD_con1->getArrayConsulta(48, array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
  $sum_fact=0;
  foreach ($data_prov as $dpr) 
  {
    $data_facts = $obBD_con1->getArrayConsulta(47, array('sel_ven'=>$sel_ven,'Prv_Cod' => $dpr['Prv_Cod'],'txt_fec_ini'=>$txt_fec_ini,'txt_fec_fin'=>$txt_fec_fin), $obBD_conexion);
	
    /*T=todos P=pagados I=PorPagar
      Filtro las facturas de compra por pagadas, por pagar*/
  	if($op_opciones!='T')
    {
          foreach ($data_facts as $key => $item)
          {
              if ($item['Abono'] != $item['Asi_Val']&&$op_opciones=='P') unset($data_facts[$key]);
              if ($item['Abono'] == $item['Asi_Val']&&$op_opciones=='I') unset($data_facts[$key]);
          }
    }

    $data_facts=array_values($data_facts);
    if(count($data_facts) > 0)
    {
      $sum_abo=0;
      $sum_totp=0;
      $estructuraTable.=""
      ."<tr style='font-size:11px;'>"

        ."<td colspan=\"8\" style='border: 2px solid #000;'> <b>Proveedor: ".$dpr['nombre']."</b></td>"
      	."<td style='border: 2px solid #000;text-align:center;'><b>Total</b></td>"
      	."<td style='border: 2px solid #000;text-align:center;'><b>Abonos</b></td>"
      	."<td style='border: 2px solid #000;text-align:center;'><b>Saldo</b></td>"
      ."</tr>";
      
      foreach ($data_facts as $df) 
      {
    		$Cpp_Data = $obBD_con1->getRowConsulta(70, $df['Cpp_Cod'], $obBD_conexion);

    		if($Cpp_Data==NULL)
        {
            $Cpp_Data = $obBD_con1->getRowConsulta(71, $df['Cpp_Cod'], $obBD_conexion);
        }
			
    		$saldo=$Cpp_Data['total']*1;
    		$df['Com_Val']=	$saldo;
        $sum_abo=0;
        $estructuraTable.=""
        ."<tr style='font-size:10px; border-left: 0px solid white; border-right:  0px solid white; border-top:0px solid white ;border-bottom: 1px solid #F8F9FA; '>"
          ."<td > <strong>"                                                             .$df['Com_Cod']      ."</strong></td>"
          ."<td > <strong>"                                .$df['Com_Codigo']      ."</strong></td>"
          ."<td > <strong>"                                .$df['Cop_Fec']         ."</strong></td>"
          ."<td > <strong>"                                .$df['Cpp_Ven']         ."</strong></td>"
          ."<td style='width: 50px' align='left' colspan=\"4\" > <strong>Factura:</strong> " .$df['Cop_Num'] ." - " .$dpr['nombre']        ." </td>"
          ."<td style='text-align:right'>"                                 .number_format($df['Com_Val'], 2, '.', ',')     ."</td>"
          ."<td></td>"
          ."<td></td>"
        ."</tr>";
        
        $sumarAbonoRet = true;
        $data_pags = $obBD_con1->getArrayConsulta(46, array('Cpp_Cod' => $df['Cpp_Cod']), $obBD_conexion);
        foreach ($data_pags as $dp) {
          $sum_abo+=$dp['Pag_Val'];

          if($dp['Pag_Abr']!="RET") {
            $estructuraTable.=""
            ."<tr style='font-size:10px;border-left: 0px solid white; border-right:  0px solid white; border-top:0px solid white ;border-bottom: 1px solid #F8F9FA; '>"
              /*."<td>".$dp['Com_Cod']."</td>" */
              ."<td style='text-align:right'>>>></td>"
              ."<td>".$dp['codigo_compro']."</td>"
              ."<td>".$dp['Com_Fec']."</td>"
              ."<td></td>";

              if($dp['Pag_Abr']=="CHE") {
                $estructuraTable.="<td>".$dp['Pag_Abr']."</td>"
                ."<td>".$dp['Che_Num']."</td>"
                ."<td>".$dp['Ban_Cue']."</td>"
                ."<td>".$dp['Che_Fec']."</td>";
              } else {
                $estructuraTable.="<td colspan=\"4\">".$dp['Pag_Abr']."</td>";
              }

              $estructuraTable.="<td></td>"
              ."<td style='text-align:right'>".number_format($dp['Pag_Val'], 2, '.', ',')."</td>"
              ."<td></td>"
            ."</tr>";
          } else{
            $sumarAbonoRet = false;
          }
        }

        $Retenciones = $obBD_con1->getArrayConsulta(68, array('Cop_Cod' => $df['Cop_Cod']), $obBD_conexion);
        $ret_sum=0;
        foreach ($Retenciones as $ret){
            $ret_sum=$ret_sum+round($ret['retencion'], 2);
            $ret['retencion'] = number_format(round($ret['retencion'], 2)  , 2, '.', ',')     ;


            $estructuraTable.=""
            ."<tr style='font-size:10px;border-left: 0px solid white; border-right:  0px solid white; border-top:0px solid white ;border-bottom: 1px solid #F8F9FA;'>"
              ."<td style='text-align:right'>>>></td>"
              ."<td    >".$dp['codigo_compro']."</td>" 
              ."<td>".$ret['Ret_Fec']."</td>"
              ."<td></td>"
              ."<td>".$ret['tipo']."</td>"
              ."<td>".$ret['Ret_Num']."</td>"
              ."<td></td>"
              ."<td></td>"
              ."<td></td>"
              ."<td  style='text-align:right'  >".$ret['retencion']  ."</td>"
              ."<td></td>"
            ."</tr>";
        }
        if($sumarAbonoRet){
          $sum_abo=$sum_abo+$ret_sum;
        }
          
        
        $estructuraTable.=""
        ."<tr style='font-size:10px;border:0px solid white'>"
          ."<td colspan=\"7\"><strong>Obs:</strong> ".$df['Cop_Obs']."</td>"
          ."<td style='text-align:right; border-top: 1px solid black' colspan=\"3\"><strong>Saldo Documento:</strong></td>"
          ."<td style='text-align:right; border-top: 1px solid black'>".number_format(($df['Com_Val']-$sum_abo), 2, '.', ',')."</td>"
        ."</tr>";
        $sum_totp=$sum_totp+($df['Com_Val']-$sum_abo);

        /*
        if($full) $table['{body}']=$table['{body}'].'<tr><td style="word-wrap: break-word;" colspan="6"><strong>Obs:</strong> '.$Cpp_Data['Vet_Obs'].'</td><td colspan="3" style="text-align:right;border-top: '.$b.' solid #000;">SALDO DOCUMENTO:</td><td style="border-top: '.$b.' solid #000;text-align:right;mso-number-format:&#39;#,##0.00&#39;;">'.number_format($saldo,2).'</td></tr><tr><td colspan="9" style="height:20px;"></td></tr>';
        $saldoProvee=$saldoProvee+$saldo;
        $saldoAbono=$saldoFacturas-$saldoProvee;
        */



      }
      $sum_fact=$sum_fact+$sum_totp;
      $estructuraTable.=""
      ."<tr >"
        ."<td style='text-align:right; border-left: 1px solid white; font-size:12px; border-right:  1px solid white; border-top:1px solid #000 ;border-bottom: 3px double #000;'  colspan=\"9\"><b>Saldo Proveedor: " .$dpr['nombre'].  " </b></td>"
        ."<td style='text-align:right; border-left:  1px solid white; font-size:10px;  border-right:  1px solid white; border-top:1px solid #000 ;border-bottom: 3px double #000; '>".number_format(($sum_totp), 2, '.', ',')."</td>"
        
      ."</tr>
      
      <tr> <td   style='border-right:1px solid white;border-left:1px solid white;border-top:1px solid white;'  colspan=\"10\"></td>
       </tr>
      
      ";
    }
  }
  $estructuraTable.=""
  ."<tr>"
    ."<td style='text-align:right;border-bottom: 2px double #000; border-top:2px solid #000;border-right:0px white;border-left:0px white;' colspan=\"8\"><b>TOTAL GENERAL: </b></td>"
    ."<td colspan=\"2\" style='text-align:right;border-bottom: 2px double #000; border-top:2px solid #000;border-right:0px white;border-left:0px white;'>".number_format(($sum_fact), 2, '.', ',')."</td>"
  ."</tr>";

  $response['html']=$estructuraTable;
	if ($obBD_con1->Error == 0) {
    $response['success'] = true;
  }
  $obBD_con1->echoJson($response);
  exit();
}
?>
<!DOCTYPE html>
<html>
  <head>
    <!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?php echo "Ccxpp Consultar [EXA] "; ?></TITLE>
	  <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script src="../VALIDACIONES/tes_val_con_ccpp.js?a=5"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <style>
      .txt-green{ color:#29a827; }
      .txt-red{ color:#ff0000; }
      .txt-blue{ color:#467de8; }
      .obs-mayus{ text-transform:uppercase; }
      .btn-sg-pg{ padding-right: 2; }
      #searchGrid .no_padding{padding: 0 !important;}
      #searchGrid .no_padding input[type="text"]{height: 23px;font-size: 14px;font-weight: bold; -moz-appearance:textfield !important;}
      #searchGrid .no_padding input[type="text"]::-webkit-outer-spin-button,
      #searchGrid .no_padding input[type="text"]::-webkit-inner-spin-button { -webkit-appearance: none !important; margin: 0 !important; }
      #searchGrid input[type="text"]:read-only{ background-color:#a2a2a2; border: none; }
    </style>
    <!-- variable de negociacion en la configuracion -->
    <script>
        isnego = '<?php echo $configs['Cof_NegCam']; ?>';
    </script>

  </head>
  <body>
    <div class="panel panel-main">
      <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Cancelaci&oacute;n por lotes a proveedores</h3></div>
      <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div id="listar_ccpp">
          <div class="row">
            <form name="searchCcpp" id="searchCcpp" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchCcpp','getFactsProvee');">
              <div class="col-sm-6">
                <fieldset class="exa-fieldset">
                  <legend class="Titulos2">Seleccionar Proveedor</legend>
                  <div class="form-group">
                    <label class="col-sm-4 control-label label-sm">C&eacute;dula/RUC:</label>
                    <div class="col-sm-6" >
                      <input name="Prv_Cod" id="Prv_Cod"  type="text" style="display:none;" />

                      <div class="input-group input-group-xs">
                        <input name="Prs_Ced" id="Prs_Ced" type="text" placeholder="Seleccione o cree un proveedor..."  class="form-control input-xs" tabindex="1" readonly/>
                        <span class="input-group-btn">
                          <a onclick="$('#proveedoresDialog').dialog('open');" class="btn btn-success btn-xs" title="Seleccionar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-list-alt"></span></a>
                          <a onclick="delProveedor()" class="btn btn-danger btn-xs" title="Quitar Proveedor"  tabindex="2" style="margin-left:2px;"><span class="glyphicon glyphicon-remove"></span></a>
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-sm-4 control-label label-xs">Proveedor:</label>
                    <div class="col-sm-6" ><input name="nombre" id="nombre" class="form-control input-xs databind datatitle" readonly/></div>
                  </div>
                  <div class="form-group">
                    <label class="col-sm-4 control-label label-xs">Direcci&oacute;n:</label>
                    <div class="col-sm-6" ><input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-xs databind datatitle" readonly/></div>
                  </div>
                </fieldset>
              </div>
              <div class="col-sm-6">
                <fieldset class="exa-fieldset">
                  <legend class="Titulos2">Filtros</legend>
                  <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                    <div class="col-xs-4 radioset opt_search">
                      <input id="radsc1" name="op_opciones" type="radio" value="T" checked="" onclick="setfocus(this.form.search)" onchange="$('#op_opciones2').val($(this).val())" alt=""/><label for="radsc1">Todos&nbsp;</label>
                      <input id="radsc2" name="op_opciones" type="radio" value="P" onclick="setfocus(this.form.search)" onchange="$('#op_opciones2').val($(this).val())" alt="" /><label for="radsc2">Pagados</label>
                      <input id="radsc3" name="op_opciones" type="radio" value="I" onclick="setfocus(this.form.search)" onchange="$('#op_opciones2').val($(this).val())" alt="" /><label for="radsc3">Por pagar</label>
                    </div>

		    <!-- Filtrar por productores -->
                     <div class="col-xs-6">
                       <div class="input-group">
                    
                    <?php 
                      if($productores[0]['prd_cant']>0){
                           echo  "<select class='form-control input-xs' name='sel_tip_prov' id='sel_tip_prov'>";
                           echo  "<option value='1'>Todos</option>";
                           echo  "<option value='2'>Proveedores</option>";
                           echo  "<option value='3'>Productores</option>";
                           echo  "</select>"; 
                      }
                     ?>
                      </div>
                     </div>
	
                    <input type="text" id="op_opciones2" value="" hidden>
                  </div>
                  <div class="form-group">
                    <label class="col-xs-2 control-label">Periodos:</label>
                    <div class="col-xs-4" >
                      <div class="input-group">
                        <select class="form-control input-xs" name="sel_ven" id="sel_ven" onchange="cambioPreiodoSearch('peri')">
                          <?php
                          $periodos_rows = $obBD_con1->getArrayConsulta(45, "", $obBD_conexion, true);
                          if (count($periodos_rows) > 0)
                          {
                            foreach($periodos_rows as $row){
                              echo "<option value='$row[Pec_Cod]' data-inicio='$row[Pec_Fei]' data-fin='$row[Pec_Fef]'>$row[anio]</option>";
                            }
                          }

                          $periodo_mm = $obBD_con1->getRowConsulta(69, "", $obBD_conexion);
                          echo "<option value='ini' data-inicio='$periodo_mm[minimo]' data-fin='$periodo_mm[maximo]'><< Fechas >></option>";
                          ?>
                        </select>
                        <span class="input-group-btn"><button onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Documento"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                      </div>
                    </div>
                    <div class="col-xs-6">
  										<div class="input-group input-group-xs">
  											<span class="input-group-addon bold alert-info">Desde:</span>
  											<input onchange="cambioPreiodoSearch('txt')" name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="form-control input-xs datepicker databind" style="text-align: center;" disabled/>
  											<span class="input-group-addon bold alert-info">Hasta:</span>
  											<input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="form-control input-xs datepicker databind" style="text-align: center;" disabled/>
  										</div>
  									</div>
                  </div>
                </fieldset>
              </div>
              <input type="hidden" id="filtroCCxPP" name="filtroCCxPP" class="form-control"/>
            </form>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <table id="searchGrid" name="searchGrid"></table>
              <table id="pag_sg"></table>
              <div class="Titulos2">
                <span id="plan-footer"><strong>Leyenda:</strong>
                  <span class="glyphicon glyphicon-stop" style="color:#ff8a8a;"></span> Vencidos
                  <span class="glyphicon glyphicon-stop" style="color:#8bff9f;"></span> Pagados
                </span>
              </div>
              <br>
              <div class="">
                <button class="btn btn-sm btn-primary" onclick="imprimir_ccpp()" title="Imprimir reporte"><span class="glyphicon glyphicon-print"></span> Imprimir</button>
                <button class="btn btn-sm btn-primary" onclick="exportar_ccpp()" title="Exportar a Excel"><span class="glyphicon glyphicon-export"></span> Excel</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="proveedoresDialog" title="B&uacute;squeda de Proveedores"><form class="form-horizontal normal"> </form></div>
    <div id="verPagosDialogMod" title="Pago">
    	<div class="row">
    		<div class="col-sm-12">
    			<fieldset class="exa-fieldset">
    				<legend class="Titulos2">Datos del Abono</legend>
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
    						<textarea id="obs_show" class="form-control input-xs" readonly></textarea>
    					</div>
    				</div>
    			</fieldset>
    		</div>
    	</div>
    	<br>
    	<div class="row">
    		<div class="col-sm-12">
    			<div id="tabs_abo_det" class="ui-tab-fix">
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
    						</div>
              </div>
    				</div>
    			</div>
    		</div>
    	</div>
    </div>

    <div id="imprimir_ccpp" style="display:none">
      <div style="text-align:center">
        <h4 style="margin-bottom:0;padding-bottom:0;"><b>ESTADO DE CUENTAS POR PAGAR </b></h4>
        <span style="margin-top:0;padding-top:0;font-size:14px;"><b>Historial de abonos a proveedores</b></span>
      </div>
      <div style="font-size:13px;">
        <table>
          <tr>
            <td align="right"><b>EMPRESA:</b></td>
            <td><span><?php echo $Ses_Emp_Nom;?></span></td>
          </tr>
          <tr>
            <td align="right"><b>EMISI&Oacute;N:</b></td>
            <td><span><?php $hoy = date("Y-m-d");$fecha=explode('-',$hoy); echo dias( date('w'),1).', '.$fecha[2].' de '.mes($fecha[1],1).' de '.$fecha[0]; ?></span></td>
          </tr>
          <tr> </tr>
        </table>
       <table id="datosTabla" style="table-layout: fixed; width: 100%; word-wrap: break-word; border-collapse: collapse; font-family:Verdana, Geneva, sans-serif; font-size:12px" align="center" cellpadding="5" border="1" class="noBorder">
          <thead>
           
            <th colspan="2" style="width:20%;"># Compr.</th>
            <th style="width:10%;">Fecha Emis.</th>
            <th style="width:10%;">Fecha Venc.</th>
            <th style="width:7%;">Tipo</th>
            <th style="width:13%;">Documento</th>
            <th style="width:15%;">Cta. Bancaria</th>
            <th style="width:10%;">Fec. Che.</th>
            <th colspan="3" style="width:30%;">Saldos</th>
          </thead>
          <tbody id="tabla_export">
          </tbody>
        </table>
      </div>
    </div>


    <div id="exportar" style="display: none">
      <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, '<p style="margin-left:10%;">ESTADO DE CUENTAS POR PAGAR</p>', '<span style="margin-left:10%;" class="subtitle">Historial de creditos recibidos</span>', $obBD_conexion,false,9) ?>
      <table id="datosTabla" style="table-layout: fixed; width: 100%; word-wrap: break-word; border-collapse: collapse; font-family:Verdana, Geneva, sans-serif; font-size:12px" align="center" cellpadding="5" border="1" class="noBorder">
        <thead>
          <th colspan="2" style="width:20%;"># Compr.</th>
            <th style="width:10%;">Fecha Emis.</th>
            <th style="width:10%;">Fecha Venc.</th>
            <th style="width:7%;">Tipo</th>
            <th style="width:13%;">Documento</th>
            <th style="width:15%;">Cta. Bancaria</th>
            <th style="width:10%;">Fec. Che.</th>
            <th colspan="3" style="width:30%;">Saldos</th>
        </thead>
        <tbody id="tabla_export_ex">
        </tbody>
      </table>
    </div>

  </body>
</html>
