<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2018-04-05
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
    $rows=array();
    $codigos=$obBD_con1->getArrayConsulta(360, $Fog_Cod, $obBD_conexion);
    foreach ($codigos as $i=>$d){        
        $cuentas=$obBD_con1->getArrayConsulta(357, $Pla_Cod.'*'.$d['Foc_Cod'], $obBD_conexion);
        foreach ($cuentas as $c){            
            $mayor=$obBD_con1->getRowConsulta(358,array('Pld_Cod'=>$c['Pld_Cod'],'Pec_Cod'=>$Pec_Cod,'Year'=>$Year), $obBD_conexion);  
            $mayor['id']=$d['Foc_Cod'].'_'.$c['Pld_Cod'];
            if($mayor!=null && !empty($mayor) && (!empty($mayor['Deudor']) || !empty($mayor['Acreedor']))){
                $Cdc=explode(".", $mayor['Pld_Cdc']);
                $debe=$mayor['Debe']!=null?$mayor['Debe']*1:0;
                $haber=$mayor['Haber']!=null?$mayor['Haber']*1:0;
                $mayor['ValueCod']=(in_array($Cdc[0], array('1','5','6'))?$debe-$haber:$haber-$debe);
                array_push($rows, array_merge($d,array_merge($c,$mayor)));
            }
        }       
    } 
    $r['rows']=$rows;
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
            sortname:'GrupoOrd',
            colModel: [
                { label: 'Cód.Int.', name: 'id', key: true, width: 20,align:"center", hidden:true },  
                { label: 'Grupo', name: 'GrupoOrd', width: 50, sortable:false, hidden:true },               
                { label: 'SubGrupo', name: 'SubGrupo', width: 50, sortable:false },
                { label: 'Codigo', name: 'Foc_Num', width: 30, align:"center", stype:'int', hidden:true },                      
                { label: 'Descripcion', name: 'Foc_Nom', width: 150, summaryType:$.fieldHeader, hidden:true },
                
                {label:'Codigo',name:"Pld_Cdc",width:50,align:"center"}, 
                {label:'Cuenta ',name:"Pld_Des",width:100},
                /*{label:'Debe', name:"Debe",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'},
                {label:'Haber', name:"Haber",width:45, align: 'right', formatter:'currency', decimalPlaces: '2'},
                {label:'Deudor', name:"Acreedor",width:45, align: 'right', formatter:'currency', formatoptions:{defaultValue:''}, decimalPlaces: '2'},
                {label:'Acreedor', name:"Deudor",width:45, align: 'right', formatter:'currency', formatoptions:{defaultValue:''}, decimalPlaces: '2'},*/
                {label:'Valor', name:"ValueCod",width:45, align: 'right', formatter:'currency', decimalPlaces: '2', summaryTpl:"TOTAL: {0}", summaryType:'sum', summaryRound:2, summaryRoundType:'fixed'}
                
                
            ],
            grouping:true, 
            groupingView : { 
               groupField : ['GrupoOrd','Foc_Num'],
               groupText:["<div class='title-group-grid'>{0}</div>","<div class='title-group-grid'>{0} - {Foc_Nom}<div class='pull-right'></div></div></div>"],
               groupColumnShow:[false,false],
               groupSummary: [false,true],
               showSummaryOnHide: [false,true]
            }            
        },true,'#compPager').gridButtonsAdd([
                {buttonicon:'print',caption:'Imprimir',onClickButton:function(){ printR(); }},
                {buttonicon:'download-alt',caption:'Descargar',onClickButton:function(){ exportR(); }}
        ]);        
    });
    function prueba(v, field, rc){ return v||(rc[field]||"Undefined"); }
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
            $('#tablaReporte').html(gridComp.jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false,removeHiddens:true,removeCols:[]}));
            $('#titleReporte').html(gridComp.getCaption());
            $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});                
        }
        function exportR() {
            var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
            temp.append(gridComp.jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true,removeHiddens:true,removeCols:[]}));                
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