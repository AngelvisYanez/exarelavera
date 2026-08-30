<?php
/**
* @abstract Permite registrar los cheques
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

if(isset($gridAjax)){
        if(isset($fechas)&&$fechas=='S') $fechas=" AND Che_Fec BETWEEN '$txt_fec_ini 00:00:00' AND '$txt_fec_fin 23:59:59' ORDER BY Che_Fec"; else $fechas='';
        $rs_buscar1 = $obBD_con1->getArrayConsulta(340, $Ses_Emp_Cod.'*'.$bancos.'*'.$fechas,$obBD_conexion);
        $rs_buscar2 = $obBD_con1->getArrayConsulta(378, $Ses_Emp_Cod.'*'.$bancos.'*'.$fechas,$obBD_conexion);
        $responce['rows']=  array_merge($rs_buscar2,$rs_buscar1);
        utf8_encode_deep($responce['rows']);
        $responce['page'] = 1;$responce['total'] = 1;
        $responce['records'] = count($responce['rows']);
        $obBD_con1->echoJson($responce);
}
if(isset($save)){
        $obBD_con1->inicio_transaccion($obBD_conexion);        
		foreach ($save as $row) {
            if($row['ext']!=='EXT')
                $obBD_con1->grabarv_registros(sentencias_che(341,$obBD_con1->parametros($row['estado'].'*'.$row['cobro'].'*'.$row['id'])), $obBD_conexion);
            else
                $obBD_con1->grabarv_registros(sentencias_che(379,$obBD_con1->parametros($row['estado'].'*'.$row['cobro'].'*'.$row['id'])), $obBD_conexion);
        }
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false;$responce['message']=$obBD_con1->MsgError;
        $obBD_con1->echoJson($responce);
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Cheques Cobrados/Protestados<?Php if(isset($periodo))echo $periodo; ?></h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <form action="" method="post" name="form1" id= "form1" class="form-horizontal normal">
                        <div class="col-xs-5">
                             <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Seleccione Banco</legend>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label label-xs">Banco:</label>
                                         <div class="col-sm-10">
                                            <select name="bancos" id="bancos" class="form-control input-xs">
                                                <option value="" ><< TODOS >></option>
                    <?php
                        $rs_bancos = $obBD_con1->getArrayConsulta(377,$Ses_Emp_Cod, $obBD_conexion);
                        foreach ($rs_bancos as $row){  ?>
                                                <option value="<?php echo $row['Ban_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option>
                    <?php } ?>
                                            </select>
                                         </div>
                                    </div>
                             </fieldset>
                        </div>
                        <div class="col-xs-5">
                             <fieldset class="exa-fieldset">
                                 <legend class="Titulos2"><input type="checkbox" id="fechas" name="fechas" style="vertical-align: sub;margin-top: 0;" value="S" offval="N" />  Rango de Fechas</legend>
                                    <div class="form-group">
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
                            <button type="button" class="btn btn-success" title="Buscar Cheques" onclick="$('#list').Search('#form1','gridAjax');setCaption();"> <i class="glyphicon glyphicon-search"></i> <span>Buscar</span> </button>
                        </div>
                    </form>
                    <div class="col-xs-12" style="min-height: 360px; padding-bottom: 10px;">
                        <table id="list"></table>
                        <div id="listPager"></div>
                    </div>
                    <div class="col-xs-12">
                        <button type="button" class="btn btn-primary btn-sm" onclick="javascript:$.createDialogConfirm(null,null,saveRows)" title="Guardar Cheques Cobrados"> <i class="glyphicon glyphicon-floppy-disk"></i> <span>Guardar</span></button>
                    </div>
                </div>
        </div>
    </div>
    <script type="text/javascript">
       function saveRows() {
            var grid = $("#list"), data = new Array(), batch=grid.getGridBatch(function(val){ return (val['Che_Cob']!==""&&val['Che_Est']!=="A"&&val['Che_Est']!==""); });
            if(batch.length===0){ $("#list").startGridEdit(); $.alert("No has Realizado Cambios!"); return; }
            $.each(batch,function(i,v){ data.push({id:v['id'],cobro:v['Che_Cob'],estado:v['Che_Est'],ext:(v['t_type']==='EXT'?'EXT':'')}); });
            $.saveDataJson("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{save:data},
                 function(){ $('#list').Search('#form1','gridAjax'); },
                 function(){ $("#list").startGridEdit(); },
                 function(){ $("#list").startGridEdit(); }
            );
       }
       $(document).ready(function () {
           var gridG = $("#list");
           $("#list").createGrid({
               height: 270,caption:' ',footerrow:true,pgbuttons: false,pgtext: null,
               colModel: [
                   { label: 'C�d.Int.', name: 'id', key: true, width: 15,align:"center", hidden:true },
                   { label: 'Beneficiario', name: 'proveedor', width: 175,classes:'bgNoRight bgNoColor'},
                   { label: 'Banco', name: 'banco', width: 150, classes:'bgNoColor' },
                   { label: 'No. Che.', name: 'Che_Num', width: 40, align:"center",classes:'bgNoRight'},
                   { label: 'Fecha', name: 'Che_Fec', width: 40 },
                   { label: 'Tipo', name: 't_type', width:0, hidden:true },
                   { label: 'Valor', name: 'Che_Val', width: 70, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:'.'} },
                   { label: 'Estado', name: 'Che_Est', width: 60,editable: true,viewable: false,title:false, edittype: "select",formatter:'select', editoptions: {value: "A:No Cobrado;C:Cobrado;P:Protestado"},classes:'bgNoRight bgNoColor' },
                   { label: 'Cobro', name: 'Che_Cob', width: 60,editable: true,edittype:"text",viewable: false, editoptions: { dataInit: function (el,obj) { $(el).createDatePickers({checkAvailability:true,clean:true}).datepicker("option","minDate",$(this).jqGrid('getRowData',obj['rowId']).Che_Fec).mask("9999-99-99",{placeholder:"_"}).val('');  } },classes:'bgNoRight bgNoColor' },
                       { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,classes:'bgNoColor',
                           formatter:function (cv, opts, rowObject) {  return $.getGridButton("$('#list').viewGridRow($(this).data('originaldata'));",rowObject.id,'Ver','info-sign',null,'info',null,{tabindex:'-1'}); },unformat:function(){return '';}
                       }
               ],
               loadComplete: function(){ $("#list").startGridEdit().jqGrid('footerData', 'set',{Che_Fec:'<div style="text-align:right;">TOTAL:</div>',Che_Val:'0.00'});$('select[name=Che_Est]').attr('onChange','updateTotal()');}
           },true,'listPager',{refresh: true})
           .gridButtonsAdd([null,
                { caption:"Marcar",buttonicon:"glyphicon glyphicon-check",title:'Marcar Todos',
                    onClickButton: function() {
                        $('select[name=Che_Est]').val('C'); $.each($("#list").jqGrid('getRowData'),function (i,v){ $('#'+v['id']+'_Che_Cob').val(v['Che_Fec']); });
                        updateTotal();
                    }
                },
                { caption:"Desmarcar",buttonicon:"glyphicon glyphicon-unchecked",title:'desmarcar Todos',
                    onClickButton: function() {
                        $('select[name=Che_Est]').val('A'); $('input[name=Che_Cob]').val('');
                        updateTotal();
                    }
                },
                { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { gridG.jqGrid('exportGridExcel', { nombre: 'Cheques', hoja: 'HOJA 1' }); } }
           ]);
           $.createDateRange('#txt_fec_ini','#txt_fec_fin');
           $('#fechas').on('change',function(){ $('#txt_fec_ini').toggleAttr('disabled');$('#txt_fec_fin').toggleAttr('disabled');});
       });
       function setCaption(){ $('#list').jqGrid('setCaption',"Cheques Girados y No Cobrados"+($('#bancos').val()!==''?' - '+ $('#bancos').find('option:selected').text():'')+($('#fechas').is(':checked')?' - Desde '+$('#txt_fec_ini').val()+' Hasta '+$('#txt_fec_fin').val():'')); }
       function updateTotal(){
           var grid=$('#list'), ids = grid.jqGrid('getDataIDs'),suma=0;
           for (var i = 0; i < ids.length; i++){
               if($('#'+ids[i]+'_Che_Est').val()==='C') suma=suma+(grid.jqGrid('getRowData', ids[i]).Che_Val*1);console.log(suma);
           } grid.jqGrid('footerData', 'set',{Che_Val:suma});
       }
  </script>
  <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</BODY>
</HTML>