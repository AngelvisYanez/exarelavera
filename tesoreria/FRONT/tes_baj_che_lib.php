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
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/**
* Cracion del objeto mysql para las consultas
*/
$obBD_con1 =  new Class_Log_Datos_Che;

$hoy = date("Y-m-d");
$mes = date("m");

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
    $rs_buscar1 =  $obBD_con1->getArrayConsulta(380, $Ses_Emp_Cod.'*'.$Ban_Cod.'*5*'.$date.'*'.$data['buscarCheNum'], $obBD_conexion);
    $rs_buscar2 =  $obBD_con1->getArrayConsulta(362, $Ses_Emp_Cod.'*'.$Ban_Cod.'*5*'.$date.'*'.$data['buscarCheNum'], $obBD_conexion);
    $responce=array('success'=>true,'rows'=>array_merge($rs_buscar1,$rs_buscar2));
    $obBD_con1->echoJson($responce);    
}

if(isset($liberarCheq)){

    $data=$_POST;
    $data['Che_Cod']=  explode('_',$data['Che_Cod']);
    $data['Ban_Cod']=$data['Che_Cod'][0];
    $data['Che_Num']=$data['Che_Cod'][1];
    $data['Che_Cod']=$data['Che_Cod'][2];
    $obBD_con1->inicio_transaccion($obBD_conexion);
    //Cambiar Estado a Comprobante
    $obBD_con1->operacionobBD(387,$data,$obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if($obBD_con1->Error==0) {$responce=array('success'=>true,'prov'=>$data);} else {$responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!',error=>$obBD_con1->MsgError);}
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
        <TITLE><?Php echo "Cheques Liberar [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
                <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
                <style>

                </style>
	</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Liberar Cheques<?Php if(isset($periodo)) echo $periodo; ?></h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
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
                                                $rs_bancos = $obBD_con1->getArrayConsulta(377,$Ses_Emp_Cod, $obBD_conexion);
                                                foreach ($rs_bancos as $row){  ?>
                                                        <option value="<?php echo $row['Ban_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option>
                                            <?php } ?>
                                        </select>
                                     </div>
                                </div>
                                <div class="form-group hidden">
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
                        <table id="list"></table>
                        <div id="listPager"></div>
                    </div>
                </div>
        </div>
    </div>

<script>
    var periodos=<?php if (count($row_rs_periodos) > 0) echo json_encode($row_rs_periodos); else echo 'new Array()';?>;
    function setPeriodo(){ if(periodos.length>0){  $('#pec_values').setData(getPeriodo()); } }
    function LibCheque(ro){
        $.createDialogConfirm('Est&aacute; seguro que desea Liberar el Cheque?',null,function(){
            $.saveDataJson('',$.extend(ro,{liberarCheq:true}),function(res){
                LoadCheque();
            });
    },function(){

      });
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
                    { label: 'Cod.', name: 'Che_Cod', key: true, width: 50,align:"center", hidden:true },
                    { label: 'Tipo', name: 't_type', width:0, hidden:true },
                    { label:'&nbsp;', name: 'act1', width: 15, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:LibCheque,
                            conditional:function(ro){
                                    return (ro.Che_Num);
                            },
                            caseFalse:function(){
                                    return $.createIcon('ok green',null,'title="Liberado"');
                            }
                        }
                    }
                ]
//                loadComplete: function(data){
//                    for(var i=0,z=data.rows.length;i<z;i++){
//                        if(data.rows[i]['estado'] ==='Anulado' || data.rows[i]['estado'] ==='Protestado') $("#"+data.rows[i].Che_Cod+' td:not(.jqgrid-rownum)').addClass('cellRed2');
//                        if(data.rows[i]['estado'] ==='Cobrado')  $("#"+data.rows[i].Che_Cod+' td:not(.jqgrid-rownum)').addClass('cellGreen2');
//                    }
//                }
            },true,'#listPager',{refresh: false});

            $.createDateRange('#txt_fec_ini','#txt_fec_fin');
            $('#lblCheFeCob2').createDatePickers();
            $('#fechDialog').createDialog({height:300,width:650,icon:'pencil'});
        });
        function saveFech(){$.saveDataJson("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",$('#formFecha').getData(),function(){ $('#fechDialog').dialog('close');LoadCheque(); }); }
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
                        <legend><label class="Titulos2">Observaci&oacuten</label></legend>
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
