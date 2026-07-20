<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2018-04-05
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_planc_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas  */
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($getReporte)){
    $r=array('success'=>true);    
    $rows=$obBD_con1->getArrayConsulta(360, $Fog_Cod, $obBD_conexion);
    foreach ($rows as $i=>&$d){
        $d['Valor']=0;
        $cuentas=$obBD_con1->getArrayConsulta(357, $Pla_Cod.'*'.$d['Foc_Cod'], $obBD_conexion);
        foreach ($cuentas as $c){
            //var_dump( $c);
            $mayor=$obBD_con1->getRowConsulta(358,array('Pld_Cod'=>$c['Pld_Cod'],'Pec_Cod'=>$Pec_Cod,'Year'=>$Year), $obBD_conexion);
            //var_dump( $mayor);
            if(trim($d['Foc_Sig'])=='+/-'){
                $debe=$mayor['Debe']!=null?$mayor['Debe']*1:0;
                $haber=$mayor['Haber']!=null?$mayor['Haber']*1:0;
                $Cdc=explode(" ", $pizza);
                $d['Valor']+=(in_array($Cdc[0], array('1','5','6'))?$debe-$haber:$haber-$debe);
            }else    
                $d['Valor']+=($mayor['Acreedor']!=null?$mayor['Acreedor']*1:($mayor['Deudor']!=null?$mayor['Deudor']*1:0));
        }       
    } unset($d);
    $tot=count($rows);
    for($i=0; $i<$tot; $i++){
        //$obBD_con1->echoLog($rows[$i]['Valor']==0);
        if($rows[$i]['Valor']==0||empty($rows[$i]['Valor']))
            unset($rows[$i]);
    }
    
    $r['rows']=array_values($rows);
    $obBD_con1->echoJson($r);
}
if(isset($ajaxSubgrid)){
    $rows=array();
    $cuentas=$obBD_con1->getArrayConsulta(357, $Pla_Cod.'*'.$Foc_Cod, $obBD_conexion);
    foreach ($cuentas as $c){
        $mayor=$obBD_con1->getRowConsulta(358,array('Pld_Cod'=>$c['Pld_Cod'],'Pec_Cod'=>$Pec_Cod,'Year'=>$Year), $obBD_conexion);
        if($mayor!=null && !empty($mayor))
        array_push($rows, $mayor);
    }
    $r=array('success'=>true, 'page'=>1, 'rows'=>$rows, 'records'=>count($rows));
    $obBD_con1->echoJson($r);
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>    
    <style>
        .title-group-grid{
            text-align: left;
        }
    </style>
</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Reporte Formulario</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <div class="col-sm-12">  
                        
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Plan de Cuentas</legend> <!-- Form Name -->
                            <form id="formulario" class="form-horizontal normal">
                                <div class="form-group">
                                  <label for="Pla_Cod" class="control-label label-xs col-xs-2">Seleccione Periodo:</label>
                                  <div class="col-xs-2">
                                    <?php $row_rs_planes = $obBD_con1->getArrayConsulta(342, $Ses_Emp_Cod,$obBD_conexion); ?>
                                    <select id="Pec_Cod" name="Pec_Cod" onchange="" class="form-control input-sm getData ins">
                                        <option value="">Seleccione Periodo...</option>
                                    <?php foreach($row_rs_planes as $row){?>
                                        <option value="<?php echo $row['Pec_Cod']; ?>" data--pla_-cod="<?php echo $row['Pla_Cod']; ?>" data--year="<?php echo $row['Periodo']; ?>">Periodo <?php echo $row['Periodo']; ?></option>   
                                    <?php } ?>
                                   </select> 
                                  </div>
                                  <label for="Pla_Cod" class="control-label label-xs col-xs-2">Seleccione Fortmulario:</label>
                                  <div class="col-xs-2">
                                    <?php $row_rs_formula = $obBD_con1->getArrayConsulta(361, $Ses_Emp_Cod,$obBD_conexion); ?>
                                    <select id="Fog_Cod" name="Fog_Cod" onchange="" class="form-control input-sm getData ins">
                                        <option value="">Seleccione Formulario...</option>
                                    <?php foreach($row_rs_formula as $row){?>
                                        <option value="<?php echo $row['Fog_Cod']; ?>"><?php echo $row['Fog_Nom']; ?></option>   
                                    <?php } ?>
                                   </select> 
                                  </div>
                                  <div class="col-xs-2">
                                      <button type="button" onclick="updatePlan();" class="btn btn-sm btn-success" ><i class="glyphicon glyphicon-search"></i> Buscar</button>
                                  </div>    
                                </div>  
                             </form>                         
                        </fieldset>
                        <div style="min-height: 300px; padding-bottom:8px; ">
                            <table id="comp"></table>
                            <div id="compPager"></div>
                        </div>                        
                    </div>
                    <div class="col-sm-6">
                       
                        
                        
                    </div>
                </div>    
              
            </div>   
        </div>
    </div>
      
   <script type="text/javascript">
    var gridComp=$("#comp"), codigos=[], Fog_Cod; 
    $(function() { 
        gridComp.createGrid({        
            height: 250,caption:'&nbsp;Codigos',
            sortname:'Foc_Num',
            colModel: [
                { label: 'C�d.Int.', name: 'Foc_Cod', key: true, width: 20,align:"center", hidden:false },
                { label: 'Grupo', name: 'GrupoOrd', width: 50, sortable:false, hidden:true },
                { label: 'Grupo', name: 'Grupo', width: 50, sortable:false, hidden:false },
                { label: 'SubGrupo', name: 'SubGrupo', width: 50, sortable:false },
                { label: 'Codigo', name: 'Foc_Num', width: 30, align:"center", stype:'int' },                      
                { label: 'Descripcion', name: 'Foc_Nom', width: 150 },
                { label: 'Signo', name: 'Foc_Sig', width: 30, align:"center" }, 
                { label: 'Valor', name: 'Valor', width: 75, align:"right", formatter:'number', summaryTpl: "TOTAL: {0}", summaryType:suma, summaryRoundType: 'fixed', summaryRound: 2 }  
            ],
            grouping:true, 
            groupingView : { 
               groupField : ['GrupoOrd','SubGrupo'],
               groupText:["<div class='title-group-grid'>{0} <div class='pull-right'><b>TOTAL: </b>{Valor}</div></div></div>","<div class='title-group-grid'>{0} <div class='pull-right'><b>TOTAL: </b>{Valor}</div></div>"],
               groupColumnShow:[false,true]
               //groupSummary: [true,true],
               //showSummaryOnHide: [true,true]
            },
            subGrid: true, multiselect: false,
                subGridOptions: { "plusicon"  : "ui-icon-triangle-1-e","minusicon" : "ui-icon-triangle-1-s","openicon"  : "ui-icon-arrowreturn-1-e","reloadOnExpand" : false,"selectOnExpand" : true },
                subGridRowExpanded: function(subgrid_id, row_id) {
                    var subgrid_table_id = subgrid_id+"_t";         
                    $("#"+subgrid_id).addClass('condensed jqSecond').html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                    $("#"+subgrid_table_id).createGrid({
                        //url:"<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?ajaxSubgrid="+row_id, datatype: "json",regional : 'es',
                        postData:$.extend({ajaxSubgrid:true,Foc_Cod:row_id},config),
                        colModel: [
                                {label:'Cod.Int.',name:"Pld_Cod",width:80,key:true,align:"center",hidden:true},
                                {label:'Cod.Int.',name:"Pla_Cod",width:80,align:"center",hidden:true},
                                {label:'Codigo',name:"Pld_Cdc",width:50,align:"center"}, 
                                {label:'Cuenta ',name:"Pld_Des",width:100},
                                {label:'Debe', name:"Debe",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'},
                                {label:'Haber', name:"Haber",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'},
                                {label:'Acreedor', name:"Acreedor",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'},
                                {label:'Deudor', name:"Deudor",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'}                               
                        ], beforeSelectRow: function(rowid, e) {return false;},
                        rowNum:10000000, pager: "",height: '100%'
                    },false);                                
                }
            
        },true,'#compPager').gridButtonsAdd([
                {buttonicon:'print',caption:'Imprimir',onClickButton:function(){ printR(); }},
                {buttonicon:'download-alt',caption:'Descargar',onClickButton:function(){ exportR(); }}
        ]);        
    });
    function suma(v, field, rc){ 
        v=$.numUnformat(v||0);
        var res=0;
        if(rc['Foc_Sig'].trim()==='-')
            res= parseFloat(v||0) - parseFloat((rc[field]||0));
        else
            res= parseFloat(v||0) + parseFloat((rc[field]||0)); 
        return $.numFormat(res);
    }
    var config;
    function updatePlan(){
        var data=$('#formulario').getData();
        Fog_Cod=data['Fog_Cod'];
        if($.isEmpty(data['Pec_Cod']) || $.isEmpty(data['Fog_Cod'])){
            gridComp.clearGrid();
            return $.alert("Seleccione <i>Periodo Contable</i> y <i>Formulario</i> a parametrizar!");
        }
        config=$.extend(true,{},data);
        data['getReporte']=true;
        $.getDataJson('', data , function(r) {
            gridComp.setRows(r['rows']);  
            gridComp.setCaption(config['Pec_Cod_Txt']+' - '+config['Fog_Cod_Txt']);
        });
    }
   </script>  
   <script>	
	function printR() {
            $('#tablaReporte').html(gridComp.jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false,removeHiddens:true,removeCols:[2,6]}));
            $('#titleReporte').html(gridComp.getCaption());
            $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});                
        }
        function exportR() {
            var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
            temp.append(gridComp.jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true,removeHiddens:true,removeCols:[1,6]}));                
            $.downloadFile($.exportarExcelBlob(temp.html(),'Digitacion'),'digitacion_'+$.getDate()+'.xls');    
        }
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <div id="formatoReporte" style="display: none;">
        <div style="width: 1030px;">
          <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span id="titleReporte"></span>',$obBD_conexion); ?>
          <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>            
          <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
        </div>
      </div>  
      <div id="formatoExportar" style="width: 1030px;display: none;">
          <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span class="title_grid"></span>',$obBD_conexion,false,6); ?>
      </div>
</BODY>
</HTML>