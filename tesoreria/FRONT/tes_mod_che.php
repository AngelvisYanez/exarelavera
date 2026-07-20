<?php
/**
* @abstract Permite listar los cheques postfechados
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-08
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$hoy = date("Y-m-d");
$mes = date("m");

$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/**
* Cracion del objeto mysql para las consultas
*/
$obBD_con1 =  new Class_Log_Datos_Che;



//Comprobar si el cheque ingresado ya existe
if (isset($getNumChe)) {
  $data=$_GET;
  $rs_buscar1=$obBD_con1->getRowConsulta(390, $data, $obBD_conexion);
  $rs_buscar2=$obBD_con1->getRowConsulta(397, $data, $obBD_conexion);
  $responce['success']=true;
  if(isset($rs_buscar1['Che_Num'])||isset($rs_buscar2['Che_Num'])){
    $responce['success']=false;
  }
  $responce['message']="El Numero de cheque ya se encuentra en uso";
  $obBD_con1->echoJson($responce);
}

//modificar Cheque
if (isset($ModCheque)) {
  $data=$_POST;
  $obBD_con1->inicio_transaccion($obBD_conexion);
  //Cierre del periodo
  $obBD_con1->validaCierrePeriodo('comprobantes','Com_Fec','Com_Cod',$Che_Fec,$data['Com_Cod'],$obBD_conexion);
//modifica asiento y comprobante siempre
  if($data['t_type']=='EXT'){
    $obBD_con1->operacionobBD(396,$data,$obBD_conexion);
  }else {
    $obBD_con1->operacionobBD(389,$data,$obBD_conexion);
    /* Control para cambiar la glosa en el asiento*/
    $arr=array('Asi_Glo'=>'Cheque No '.$Che_Num,'Asi_Cod'=>$Asi_Cod);
    $obBD_con1->operacionobBD(409,$arr,$obBD_conexion);
    
    //$obBD_con1->operacionobBD(395,$data,$obBD_conexion);
  }
  //si cambia de Banco modifica valor de
  if ($data['Pld_Cod']!='' && $data['t_type']!='EXT') {
    $obBD_con1->operacionobBD(392,$data,$obBD_conexion);
  }
  $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
  if($obBD_con1->Error==0) {$responce=array('success'=>true,'prov'=>$data);} else {$responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!',error=>$obBD_con1->MsgError);}
  $obBD_con1->echoJson($responce);
}

//buscar tabla cheque
if(isset($cheqAjax)){
  $data='';
  if ($_POST){
    $data=$_POST;
  }else {
    $data=$_GET;
  }
  $date="*";
  if($TipBus==2) $date=$hoy;
  else{
    if($periodos=='RANGE'){
      $date=$txt_fec_ini.'*'.$txt_fec_fin;
    }else if($periodos==='ALL')
    $date='*';
    else $date=$Pec_Fei.'*'.$Pec_Fef;
  }
  $rs_buscar1 =  $obBD_con1->getArrayConsulta(380, $Ses_Emp_Cod.'*'.$Ban_Cod.'*'.$TipBus.'*'.$date.'*'.$data['buscarCheNum'], $obBD_conexion);
  $rs_buscar2 =  $obBD_con1->getArrayConsulta(362, $Ses_Emp_Cod.'*'.$Ban_Cod.'*'.$TipBus.'*'.$date.'*'.$data['buscarCheNum'], $obBD_conexion);
  $responce=array('success'=>true,'rows'=>array_merge($rs_buscar1,$rs_buscar2));
  $obBD_con1->echoJson($responce);
}

if (isset($siValorEdit)){
  $data=$_GET;
  $responce=$obBD_con1->getRowConsulta(393, $data, $obBD_conexion);
  $responce['success']=true;
  if(isset($responce['Che_Cod'])){
    $responce['success']=false;
  }
  $responce['message']="No se puede Editar El valor";
  $obBD_con1->echoJson($responce);
}
if(isset($save)){                 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        if($t_type!='EXT'){
            $save = str_replace('_', '*', $save); 
            $obBD_con1->grabarv_registros(sentencias_che(369,$obBD_con1->parametros($fecha.'*'.$save)), $obBD_conexion->conexion);
        }else
            $obBD_con1->grabarv_registros(sentencias_che(379,$obBD_con1->parametros('*'.$fecha.'*'.$save)), $obBD_conexion->conexion);                
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);       
    if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false;$responce['message']=$obBD_con1->MsgError;
    echo json_encode($responce);exit();
}

?>
<!DOCTYPE html>
<HTML>
  <HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Cheques Modificar [EXA]"; ?></TITLE>
    <meta charset= "UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <style>

    </style>
    </HEAD>
    <BODY>
    <div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Modificar Cheques<?Php if(isset($periodo)) echo $periodo; ?></h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
      <div id="main-panel">
      <div class="row">

        <form action="javascript:LoadCheque();" method="post" name="form1" id= "form1" class="form-horizontal normal">
          <div class="col-xs-5">
            <fieldset class="exa-fieldset">
              <legend class="Titulos2">Seleccione Banco</legend>
              <div class="form-group">
                <label class="col-sm-2 control-label label-xs">Banco:</label>
                <div class="col-sm-10">
                  <select id="Ban_Cod" name="Ban_Cod" onchange="LoadCheque();" class="form-control input-xs" >
                    <?php
                    $rs_bancos = $obBD_con1->getArrayConsulta(391,$Ses_Emp_Cod, $obBD_conexion);
                    foreach ($rs_bancos as $row){  ?>
                      <option value="<?php echo $row['Ban_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label label-xs">Tipo:</label>
                  <div class="col-sm-5">
                    <select class="form-control input-xs"  onchange="LoadCheque();" id="TipBus" name="TipBus" required="">
                      <option value="1"><< TODOS >></option>
                      <option value="2">Post Fechados</option>
                      <option value="3">Cobrados</option>
                      <option value="4">No Cobrados</option>
                      <option value="5">Anulados</option>
                      <option value="6">Protestados</option>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="buscarCheNum" class="col-sm-2 control-label label-xs">Cheque N.:</label>
                  <div class="col-sm-5">
                    <input type="number" min="0" step="1" name="buscarCheNum"  id="buscarCheNum" class="form-control input-xs nospin" onchange="LoadCheque();" value=""/>
                  </div>
                </div>

              </fieldset>
            </div>
            <div class="col-xs-5">
              <fieldset class="exa-fieldset">
                <legend class="Titulos2">Rango de Fechas</legend>
                <div class="form-group">
                  <label class="col-sm-2 control-label label-xs">Rango:</label>
                  <div class="col-sm-5">
                    <div id="pec_values" style="display: none;"><input type="text" name="Pec_Cod" /><input type="text" name="Pec_Fei" /><input type="text" name="Pec_Fef" /></div>
                    <select class="form-control input-xs"  onchange="if(this.value!=='ALL'&&this.value!=='RANGE'){setPeriodo();LoadCheque();} if(this.value==='RANGE'){ $('#rangeDates').find('input').removeAttr('disabled'); }else{ $('#rangeDates').find('input').attr('disabled','disabled'); } " id="periodos" name="periodos"  required="">
                      <?php
                      $row_rs_periodos = $obBD_con1->getArrayConsulta(384, $Ses_Emp_Cod, $obBD_conexion);
                      if (count($row_rs_periodos) > 0){
                        $periodo = current($row_rs_periodos);
                        foreach ($row_rs_periodos as $row){
                          ?><option value="<?php echo $row['Pec_Cod']; ?>">Periodo <?php echo $row['Periodo']; ?></option><?php
                        }
                      } ?>
                      <option value="RANGE"><< POR FECHAS >></option>
                      <option value="ALL"><< TODOS >></option>
                    </select>
                  </div>
                </div>
                <div id="rangeDates" class="form-group">
                  <label class="col-sm-2 control-label label-xs">Desde:</label>
                  <div class="col-sm-4">
                    <input name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="form-control input-xs" style="text-align: center;" disabled />
                  </div>
                  <label class="col-sm-1 control-label label-xs">Hasta:</label>
                  <div class="col-sm-4">
                    <input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="form-control input-xs" style="text-align: center;" disabled />
                  </div>
                </div>
              </fieldset>
            </div>
            <div class="col-xs-2 center" style="padding-top: 10px;">
              <button type="button" class="btn btn-success" title="Filtrar Cheques" onclick="this.form.submit()"> <i class="glyphicon glyphicon-search"></i> <span>Buscar</span> </button>
            </div>
          </form>
          <div class="col-xs-12" style="min-height: 360px;">
            <table id="list" name="list"></table>
            <div id="listPager"></div>
              <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="glyphicon glyphicon-arrow-right white" style="background-color: #ff892a!important;height: 12px;width: 14px;text-align: center;"></span>Valor de Cheque No modificable</span></div>
          </div>
        </div>
        <!-- Panel de Edicion de Cheques -->

      </div>
      <div id="modificar-panel" style="display:none;">
        <form class="form-horizontal normal" id='formModCheque' action="javascript:modificarCheque();" method="post">
          <div class="form-group">
            <label class="col-xs-3 control-label label-xs required">Banco:</label>
            <div class="col-xs-5">
              <select id="Ban_Cod" name="Ban_Cod" onchange="VerificarBanco($(this).find('option:selected'))" class="form-control input-xs" >
                <?php
                $rs_bancos = $obBD_con1->getArrayConsulta(391,$Ses_Emp_Cod, $obBD_conexion);
                foreach ($rs_bancos as $row){  ?>
                  <option value="<?php echo $row['Ban_Cod'];  ?>" data-pld_cod="<?php echo $row['Pld_Cod'];  ?>" ><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>


            <div class="form-group">
              <label class="col-xs-3 control-label label-xs required">Beneficiario:</label>
              <div class="col-xs-5" id="benenfi">
                  <input id="Che_Ben" name="Che_Ben"  type="text" class="form-control input-xs" placeholder="Nombre de Beneficiario..." required=""/>
              </div>
            </div>



            <div class="form-group">
              <label class="col-xs-3 control-label label-xs required" for="Che_Fec">Fecha Emisi&oacuten:</label>
              <div class="col-xs-3">
                <div class="input-group">
                  <input id="Che_Fec" name="Che_Fec" type="text" class="form-control input-xs datepickers" tabindex="8" required="" value="<?php echo $hoy ?>" />
                  <span class="input-group-addon input-xs" title="Fecha de Emisi&oacuten de Cheque"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="col-xs-3 control-label label-xs required">Cheque&nbsp;N.:</label>
              <div class="col-xs-3">
                <div class="input-group input-group-xs">
                  <input id="Che_Num" name="Che_Num" type="number" class="form-control input-xs readOnly ret_field nospin" onkeypress="return  validar_decimal(event)" onchange="valNumCheque()" required="" />
                  <span class="input-group-addon validate cheNum"><i></i></span>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="col-xs-3 control-label label-xs required">Valor:</label>
              <div class="col-xs-3">
                <div class="input-group input-group-xs">
                  <span class="input-group-addon">$</span>
                  <input type="number" step='any' class="form-control input-xs nospin readOnly" onkeypress="return  validar_decimal(event)" required placeholder="0.00"  name="Che_Val" id="Che_Val" required=""/>
                  <span id="infoValor" class="input-group-addon input-xs" title=""><i class="glyphicon glyphicon-info-sign blue"></i></span>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="col-xs-3 control-label label-xs">Observaci&oacuten:</label>
              <div class="col-xs-5"><textarea class="form-control input-xs" name="Che_Obs" id="Che_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
            </div>

            <input type="text" class="hidden" name="Cheque_Cod" id="Cheque_Cod" value=""/>
            <input type="text" class="hidden" name="Prv_Cod_Ant" id="Prv_Cod_Ant" value=""/>
            <input type="text" class="hidden" name="Prv_Cod" id="Prv_Cod" value=""/>
            <input type="text" class="hidden" name="Ban_Cod_Ant" id="Ban_Cod_Ant" value=""/>
            <input type="text" class="hidden" name="Asi_Cod" id="Asi_Cod" value=""/>
            <input type="text" class="hidden" name="Che_Num_Ant" id="Che_Num_Ant" value=""/>
            <input type="number" step="any" class="hidden" name="Valor_Ant" id="Valor_Ant" value=""/>
            <input type="text" class="hidden" name="Com_Cod" id="Com_Cod" value=""/>
            <input type="text" class="hidden" name="Pld_Cod" id="Pld_Cod" value=""/>
            <input type="text" class="hidden" name="t_type" id="t_type" value=""/>

          </form>
          <div class="row center">
            <div class="col-xs-12">
              <button onclick="$('#modificar-panel').moveComp('#main-panel').updateGridsSizes();" class="btn btn-sm btn-inverse" title="Volver Atr&aacutes"><i class="glyphicon glyphicon-arrow-left"></i><span>&nbsp;&nbsp;Atr&aacutes&nbsp;&nbsp;</span></button><span>&nbsp;</span>
              <button  onclick="$('#formModCheque').formSubmit();" id="btnEdit" class="btn btn-info btn-sm " title="Activar" ><i class="glyphicon glyphicon-floppy-save"></i><span> Guardar </span></button>
            </div>
          </div>
        </div>

        <div id="provDialog" title="B&uacute;squeda de Proveedores"></div>

      </div>

    </div>

    <div id="formatoReporte" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span id="titleReporte"></span>', $obBD_conexion); ?>
            <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>            
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>  

      <script>
      var valNumChe=true;
      $("#Che_Num").fieldValid(true);

      function VerificarBanco(banco){
        var pld_cod = $(banco).data('pld_cod');
        $('#Pld_Cod').val(pld_cod);
      }

      //valida numero de cheque
      function valNumCheque(){
        if($('#Che_Num').val()!=="" && $('#Che_Num').val()>0){
          if($('#Che_Num').val()!==$('#Che_Num_Ant').val()){
            $.get("",$.extend($('#formModCheque').serializeObject(),{getNumChe:true}),function(responce){
              if(responce['success']===true){

                $("#Che_Num").fieldValid(true);
                valNumChe=true;
              }else{

                $("#Che_Num").fieldValid(false,"el numero de cheque ya esta siendo utilizado");
                valNumChe=false;
                //$('#Che_Num').val($('#Che_Num_Ant').val());
              }
            },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
          }else {
            $("#Che_Num").fieldValid(true);
          }
        }else {
          valNumChe=false;
          $("#Che_Num").fieldValid(false,"Ingrese un Numero de Cheque");
        }
      }


      // dialog de Busqueda de Proveedores
      $.createSearchDialog('provDialog',[
        { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },
        { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },
        { label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
        { label: 'Apellidos', name: 'Prs_Ape',hidden:true},
        { label: 'Nombres', name: 'Prs_Nom',hidden:true},
        { label: 'Direcci&oacute;n', name: 'Prs_Dir',hidden:true,viewable: true },
        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false, formatter:function (cv, opts, rObj) { return $.getGridButton(selectProv,rObj,'Seleccione Proveedor'); } }
      ],null,null,null,null,{title:'Proveedor'});


      var periodos=<?php if (count($row_rs_periodos) > 0) echo json_encode($row_rs_periodos); else echo 'new Array()';?>;

      //Cargar Provedor Seleccionado
      function selectProv(prov){
        $('#Prv_Nombre').val(prov['proveedor']);
        $('#Prv_Cod').val(prov['Prv_Cod']);
        $("#provDialog").dialog("close");
      }

      // Modificar Cheque
      function modificarCheque(){
        if (valNumChe===true){
          var dataSend=$.extend({'ModCheque':true,'Mod_val':(($('#Che_Val').val()+"")===($('#Valor_Ant').val()+"")?false:true)},$('#formModCheque').serializeObject());
          $.createDialogConfirm('Desea confirmar los cambios?',null,
          function(){
            $.saveDataJson('',dataSend,function(res){
              LoadCheque();
              $('#modificar-panel').moveComp('#main-panel').updateGridsSizes();
            });
          },function(){

          });
        }else {
          $.alert("El numero de Cheque que intenta registrar ya esta siendo utilizado")
        }

      }
      function setPeriodo(){
        if(periodos.length>0){
          $('#pec_values').setData(getPeriodo());
        }
      }

      function setCaption(){
        var aux=($('#periodos').val()!=='RANGE'&&$('#periodos').val()!=='ALL'?' del Periodo '+getPeriodo()["Periodo"]:'');
        if($('#TipBus').val()==='1') $("#list").jqGrid('setCaption', 'Listado de Cheques'+aux+' - '+$('#Ban_Cod option:selected').text());
        else $("#list").jqGrid('setCaption', 'Cheques '+$('#TipBus option:selected').text()+aux+' - '+$('#Ban_Cod option:selected').text());
      }
      function getPeriodo(){
        if($("#periodos").val()==='ALL'||$("#periodos").val()==='RANGE') return {};
        if(periodos.length===0){return new Array();}
        for(var i=0;i<periodos.length;i++){
          if(periodos[i]['Pec_Cod']+''===$("#periodos").val()) return periodos[i];
        }
      }
      setPeriodo();
      </script>

      <script>

      //mostrar Edicion Cheque
      function VerCheque(data){
        valNumChe=true;
        $("#Che_Num").fieldValid(true);
        $('#formModCheque').setData(data);
        $('#Che_Num_Ant').val(data['Che_Num']);
        $('#Prv_Cod_Ant').val(data['Prv_Cod']);
        $('#Ban_Cod_Ant').val(data['Ban_Cod']);
        $('#Valor_Ant').val(data['Che_Val']);
        $('#Che_Ben').val(data['Beneficiario']);
        $('#main-panel').moveComp('#modificar-panel').updateGridsSizes();
        siValorEdit();
      }

      //Cargar Datos en Tabla de Cheques
      function LoadCheque(){
        $.getDataJson($("#list"),$("#form1").getData('cheqAjax'), function(response){
          setCaption();
          $("#list").setRows(response['rows']);
          return false;
        });
      }
      $(document).ready(function () {
        var gridList=$("#list");
        gridList.createGrid({
          caption:' ',height: 270,cmTemplate: {sortable:true}, sortname:'fecha',sortorder:"asc",pgbuttons: false,pgtext: null,
          colModel: [
            { label: 'Fecha', name: 'Che_Fec', width: 45 ,align:"center", sorttype:"date"},
            { label: 'No. Cheque', name: 'Che_Num', width: 35, align:"center",sorttype:"int"},
            { label: 'Beneficiario', name: 'Beneficiario', width: 100 },
            { label: 'Observaci&oacuten', name: 'Che_Obs', width: 90 },
            { label: 'No. Compr', name: 'Com_Num', width: 45 },
            { label: 'Fec. Compr.', name: 'Com_Fec', width: 45,align:"center", sorttype:"date" },
            { label: 'Valor', name: 'Che_Val', width: 45, sorttype:"currency", align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}},
            { label: 'Estado', name: 'estado', width: 45,align:"center" },
            { label: 'Fec. Ban.', name: 'Che_Cob', width: 45,align:"center", sorttype:"date" },
            { label: 'Cod.', name: 'Che_Cod', width: 50,align:"center", key: true, hidden:true },
            { label: 'Tipo', name: 't_type', width:0, hidden:true },
            { label:'&nbsp;', name: 'act1', width: 15, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:VerCheque,
              conditional:function(ro){
                return (ro.Che_Est!=='I' && ro.t_type!=='EXT');
              },
              caseFalse:function(ro){
                if(ro.t_type==='EXT'){
                  return $.getGridButton(VerCheque,ro,'Editar','arrow-right',null,'warning');
                }else {
                    return $.createIcon('remove red',null,'title="Inactivo"');
                }

              }
            }
          }
        ]
      },true,'#listPager',{refresh: false})
              .gridButtonsAdd([
                { buttonicon: "ui-icon-refresh", title:'Recargar Datos',onClickButton: function() { LoadCheque();}},null, 
                {buttonicon: 'print', caption: 'Imprimir', onClickButton: function () {
                    $('#list').getGridBatch();
                    printR('#list');
                    $('#list').startGridEdit();
                }},
                { caption: "&nbsp;Editar Fecha Cobro",buttonicon: "ui-icon-pencil",title:'Editar Fecha de Cobro',
                    onClickButton: function() {
                        var myGrid = $('#list'), selRowId = myGrid.jqGrid ('getGridParam', 'selrow'), data= myGrid.jqGrid('getRowData',selRowId);                                            
                        if(data['estado']==='Cobrado'||data['estado']==='Protestado'){
                            $('#formFecha').setData(data,null,'name');                                         
                            $("#lblCheCod2").val(selRowId);
                            $('#fechDialog').dialog('open');
                        }
                    }
                }
               ]);   

      $.createDateRange('#txt_fec_ini','#txt_fec_fin');
      $('#Che_Fec').createDatePickers();
      $('#fechDialog').createDialog({height:300,width:650,icon:'pencil'});
      $('#lblCheFeCob2').createDatePickers();
    });
    function saveFech(){$.saveDataJson("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",$('#formFecha').getData(),function(){ $('#fechDialog').dialog('close');LoadCheque(); }); }
    function siValorEdit(){
      $.get("",$.extend($('#formModCheque').serializeObject(),{siValorEdit:true}),function(responce){
        if(responce['success']===false){
          $("#Che_Val").attr('readOnly','readOnly');
          $('#infoValor').attr('title','No es Modificable - Asociado a Cuentas por Pagar');
        }else{
          $("#Che_Val").attr('readOnly','readOnly');
          //$("#Che_Val").removeAttr('readOnly');
          $('#infoValor').attr('title','Valor de Cheque Modificable');
          if(($('#t_type').val()+"")==='EXT'){
            $("#Che_Val").removeAttr('readOnly');
          }
        }
      },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
    }

    function printR(grid) 
    {
      $('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML', {removeHiddens:true,removeCols:[10,11,12],generated: false, caption: false, footer: true, bodyBorder: false}));
      $('#titleReporte').html($(grid).getCaption());
      $('#formatoReporte').printElement({pageTitle: "Cheques Emitidos", printMode: 'popup', overrideElementCSS: [{href: '../../mascaras/model1/estilos/print.css', media: 'print'}]});
    }

    </script>

<!--INICIO DEL DIALOGO DETALLE PAGO --> 
    <div id="fechDialog" title="Modificar Fecha">  
        <form action="javascript:" class="form-horizontal normal" id="formFecha" >
            <input type="hidden" id="lblCheCod2" name="save" value="" /><input name="t_type" type="hidden" value="" data-name="t_type" />
            <div class="row">
                <div class="col-xs-6">
                    <fieldset class="row exa-fieldset">
                        <legend><label class="Titulos2">Datos del Cheque</label></legend> 
                        <div class="form-group"><label class="col-xs-2 control-label label-xs">Benef.:</label><div class="col-xs-10"><span data-name="Beneficiario" class="form-control input-xs"></span></div></div>
                        <div class="form-group"><label class="col-xs-2 control-label label-xs">Fecha:</label><div class="col-xs-10"><span data-name="Che_Fec" class="form-control input-xs"></span></div></div>
                        <div class="form-group"><label class="col-xs-2 control-label label-xs">No.:</label><div class="col-xs-10"><span data-name="Che_Num" class="form-control input-xs"></span></div></div>
                        <div class="form-group"><label class="col-xs-2 control-label label-xs">Valor:</label><div class="col-xs-10"><span data-name="Che_Val" class="form-control input-xs"></span></div></div>
                    </fieldset>
                </div>                
                <div class="col-xs-6">
                    <fieldset class="row exa-fieldset">
                        <legend><label class="Titulos2">Datos Comprobante</label></legend> 
                        <div class="form-group"><label class="col-xs-2 control-label label-xs">No.:</label><div class="col-xs-10"><span data-name="Com_Num" class="form-control input-xs"></span></div></div>
                        <div class="form-group"><label class="col-xs-2 control-label label-xs">Fecha:</label><div class="col-xs-10"><span data-name="Com_Fec" class="form-control input-xs"></span></div></div>                        
                    </fieldset>
                </div>
                <div class="col-xs-12">
                    <fieldset class="row exa-fieldset">
                        <legend><label class="Titulos2">Observación</label></legend> 
                        <div class="form-group"><div class="col-xs-12"><span data-name="Che_Obs" class="form-control input-xs"></span></div></div>
                    </fieldset>
                </div>
                <div class="col-xs-12">
                    <div class="form-group">
                        <label class="col-xs-5 control-label label-sm">Fecha Cobro/Protesta:</label>
                        <div class="col-xs-5">
                            <div class="input-group">
                                <input id="lblCheFeCob2" name="fecha" type="text" data-name="Che_Cob" class="form-control input-sm" style="text-align: center;" autofocus />
                                <span class="input-group-btn"><button type="button" class="btn btn-primary btn-sm" onclick="javascript:$.createDialogConfirm(null,null,saveFech)" title="Guardar Cheques Cobrados"> <i class="glyphicon glyphicon-floppy-disk"></i> <span>Guardar</span></button></span>
                            </div>
                        </div>                        
                    </div>
                </div>
            </div>
        </form>  
    </div>
  </BODY>
</HTML>
