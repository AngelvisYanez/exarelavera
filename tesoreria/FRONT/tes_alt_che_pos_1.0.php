<?php	
/**
* @abstract Permite listar los cheques postfechados
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-08
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
    $date="*";     
    if($TipBus==2 && $periodos==='ALL') $date=$hoy;
    else{ 
        if($periodos=='RANGE'){ $date=$txt_fec_ini.'*'.$txt_fec_fin;}else if($periodos==='ALL') $date='*'; else $date=$Pec_Fei.'*'.$Pec_Fef;  
    }
    $date.=('*'.'*'.$tfecha);
    $rs_buscar1 =  $obBD_con1->getArrayConsulta(380, $Ses_Emp_Cod.'*'.$Ban_Cod.'*'.$TipBus.'*'.$date, $obBD_conexion,false);	
    $rs_buscar2 =  $obBD_con1->getArrayConsulta(362, $Ses_Emp_Cod.'*'.$Ban_Cod.'*'.$TipBus.'*'.$date, $obBD_conexion,false);	
    $responce=array('success'=>true,'rows'=>array_merge($rs_buscar1,$rs_buscar2));
    $obBD_con1->echoJson($responce);
}

?>
<!DOCTYPE html>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>                
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>                
                <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
                <style>                   
                    
                </style>
	</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Cheques Cobrados/Protestados<?Php if(isset($periodo))echo $periodo; ?></h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body"> 
                <div class="row"> 
                    <form action="javascript:LoadCheque();" method="post" name="form1" id= "form1" class="form-horizontal normal">
                        <div class="col-xs-5">
                             <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Seleccione Banco</legend>                            
                                <div class="form-group"> 
                                    <label class="col-sm-2 control-label label-xs">Banco:</label>  
                                     <div class="col-sm-10"> 
                                        <select id="Ban_Cod" name="Ban_Cod" onchange="$('#TipBus').trigger('change');" class="form-control input-xs" >
                                            <?php
                                                $rs_bancos = $obBD_con1->getArrayConsulta(377,$Ses_Emp_Cod, $obBD_conexion);                        
                                                foreach ($rs_bancos as $row){  ?>
                                                        <option value="<?php echo $row['Ban_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option>
                                            <?php } ?>
                                        </select>                                         
                                     </div> 
                                </div> 
                                <div class="form-group"> 
                                    <label class="col-sm-2 control-label label-xs">Tipo:</label>  
                                    <div class="col-sm-5">
                                        <select class="form-control input-xs"  onchange="changeBus();changeRange();/*LoadCheque();*/" id="TipBus" name="TipBus" required="">                                    
                                            <option value="1"><< TODOS >></option>
                                            <option value="2">Post Fechados</option>
                                            <option value="3">Cobrados</option>
                                            <option value="4">No Cobrados</option>
                                            <option value="5">Anulados</option>
                                            <option value="6">Protestados</option>
                                        </select>
                                    </div>
                                </div>    
                             </fieldset>   
                        </div>
                        <div class="col-xs-5">
                             <fieldset class="exa-fieldset">
                                 <legend class="Titulos2">Rango de Fechas</legend>
                                    <div class="form-group"> 
                                    <label class="col-sm-2 control-label label-xs">Rango:</label>  
                                    <div class="col-sm-4">
                                        <div id="pec_values" style="display: none;"><input type="text" name="Pec_Cod" /><input type="text" name="Pec_Fei" /><input type="text" name="Pec_Fef" /></div>
                                        <select class="form-control input-xs"  onchange="changeRange()" id="periodos" name="periodos"  required="">
                                            <?php
                                                $row_rs_periodos = $obBD_con1->getArrayConsulta(384, $Ses_Emp_Cod, $obBD_conexion);
                                                if (count($row_rs_periodos) > 0){ 
                                                    $periodo = current($row_rs_periodos);
                                                    foreach ($row_rs_periodos as $row){  
                                                ?><option value="<?php echo $row['Pec_Cod']; ?>"><?php echo $row['Periodo']; ?></option><?php
                                                    }
                                                } ?>
                                            <option value="RANGE"><< RANGO >></option>
                                            <option value="ALL"><< TODOS >></option>
                                        </select>
                                    </div>
                                    <label class="col-sm-1 control-label label-xs">Fecha:</label> 
                                    <div class="col-sm-5 radioset" id="tfecha">
                                        <input id="radsc1" name="tfecha" type="radio" value="C" checked="" alt="" /><label for="radsc1">&nbsp;Cheque&nbsp;</label>
                                        <input id="radsc2" name="tfecha" type="radio" value="B" alt="" /><label for="radsc2">Bancaria</label>
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
    function setCaption(){   
       var aux=($('#periodos').val()!=='RANGE'&&$('#periodos').val()!=='ALL'?' del Periodo '+getPeriodo()["Periodo"]:'');
       if($('#TipBus').val()==='1') $("#list").jqGrid('setCaption', 'Listado de Cheques'+aux+' - '+$('#Ban_Cod option:selected').text());
       else $("#list").jqGrid('setCaption', 'Cheques '+$('#TipBus option:selected').text()+aux+' - '+$('#Ban_Cod option:selected').text());           
    }
    function getPeriodo(){
        var pe=$("#periodos").val();
        if(pe==='ALL'||pe==='RANGE') return {};
        if(periodos.length===0){return new Array();}
        for(var i=0;i<periodos.length;i++){            
            if(periodos[i]['Pec_Cod']+''===pe) return periodos[i];
        }            
    } 
    function changeBus(){
        var ti=$('#TipBus').val()*1;
        if(ti===2) $('#periodos').val('ALL');
        if(ti===2 || ti===4){
            $('#radsc1').prop('checked',true).trigger('change');   
            $("#tfecha" ).buttonset({disabled: true});    
        }else{
            $("#tfecha" ).buttonset({disabled: false});        
        }
    }
    function changeRange(){
        var va=$('#periodos').val();
        if(va!=='ALL'&&va!=='RANGE'){
            setPeriodo(); LoadCheque();
        } 
        if(va==='RANGE'){ 
            $('#rangeDates').find('input').removeAttr('disabled'); 
        }else{ 
            $('#rangeDates').find('input').attr('disabled','disabled'); 
        } 
    }
    setPeriodo();    
</script>  
                            
    <script>                       
        function LoadCheque(){ $.getDataJson($("#list"),$("#form1").getData('cheqAjax'), function(response){ setCaption(); $("#list").setRows(response['rows']); return false; }); } 
        $(document).ready(function () {                            
            var gridList=$("#list");
            gridList.createGrid({
                caption:' ',height: 270,cmTemplate: {sortable:true}, sortname:'fecha',sortorder:"asc",pgbuttons: false,pgtext: null,
                footerrow:true,totalCols:['Che_Val'],totalDefault:{Com_Fec:'<div class="txtRight">TOTAL:</div>'},
                colModel: [  
                    { label: 'Fecha', name: 'Che_Fec', width: 45 ,align:"center", sorttype:"date"},  
                    { label: 'No. Cheque', name: 'Che_Num', width: 35, align:"center",sorttype:"int"},                                    
                    { label: 'Beneficiario', name: 'Beneficiario', width: 100 },
                    { label: 'Observación', name: 'Che_Obs', width: 90 },
                    { label: 'No. Compr', name: 'Com_Num', width: 45 },             
                    { label: 'Fec. Compr.', name: 'Com_Fec', width: 45,align:"center", sorttype:"date" },             
                    { label: 'Valor', name: 'Che_Val', width: 45, sorttype:"currency", align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}},
                    { label: 'Estado', name: 'estado', width: 45,align:"center" },
					//{ label: 'Est.Comp.', name: 'Com_Est', width: 45,align:"center" },
                    { label: 'Fec. Ban.', name: 'Che_Cob', width: 45,align:"center", sorttype:"date" },             
                    { label: 'Cód.Int.', name: 'Che_Cod', key: true, width: 50,align:"center", hidden:true },
                    { label: 'Tipo', name: 't_type', width:0, hidden:true }
                ],                                    
                loadComplete: function(data){                                   
                    for(var i=0,z=data.rows.length;i<z;i++){
                        if(data.rows[i]['estado'] ==='Anulado' || data.rows[i]['estado'] ==='Protestado') $("#"+data.rows[i].Che_Cod+' td:not(.jqgrid-rownum)').addClass('cellRed2');
                        if(data.rows[i]['estado'] ==='Cobrado')  $("#"+data.rows[i].Che_Cod+' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                    }
                }
            },true,'#listPager',{refresh: false}).clearFootRow(['Che_Val'])
            .gridButtonsAdd([
                { buttonicon: "ui-icon-refresh", title:'Recargar Datos',onClickButton: function() { LoadCheque();}},null,                
                { caption: "&nbsp;Exportar Excel",buttonicon: "glyphicon glyphicon-download",title:'Exportar a Excel', onClickButton: function() { gridList.jqGrid('exportGridExcel',{nombre:"Cheques",hoja:"Listado"}); } },
                { caption: "&nbsp;Imprimir",buttonicon: "glyphicon glyphicon-print",title:'Imprimir', onClickButton: function() { gridList.jqGrid('printGrid',{nombre:"Reporte de Cheques",bodyBorder:false}); } }
            ]); 
            $.createDateRange('#txt_fec_ini','#txt_fec_fin');    
                        
        });          
    </script>
    

</BODY>
</HTML>  