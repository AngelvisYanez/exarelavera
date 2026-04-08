<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require('../LOGICA/fac_log_factu.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Factu($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Factu;


$hoy = date("Y-m-d");
$mes = date("m");

if(isset($consAjax)){ 
    $data=$_GET; 
    $rows=$obBD_con1->getArrayConsulta(78, $Ses_Emp_Cod.'*'.$Con_Cod.'*'.$fec_ini.'*'.$fec_fin, $obBD_conexion);
    foreach ($rows as &$r) {
        $r['Cop_Imp']=($r['Cop_Can']*$r['Cop_Pru']);
        $r['subtotal']=($r['Cop_Imp']*1)-($r['Cop_Dec']*1>0?($r['Cop_Imp']*$r['Cop_Dec']/100):0)-($r['Cop_Des']*1>0?($r['Cop_Imp']*$r['Cop_Des']/100):0);
        $r['total']=$r['subtotal']+($r['Iva_Por']*1>0?($r['subtotal']*$r['Iva_Por']/100):0);
        if($r['Cop_Ice']*1>0) $r['total']=$r['total']+($r['total']*$r['Cop_Ice']/100);
    } unset($r);
    $resp=array('success'=>true,'rows'=>$rows,'records'=>count($rows));      
    $obBD_con1->echoJson($resp);
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <style></style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestión de Centros de Consumo</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <div class="col-sm-12">  
                        
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Búsqueda de Centros de Consumo</legend> <!-- Form Name -->
                           <form id="searchForm" class="form-horizontal normal" role="form" action="javascript:gridComp.Search('#searchForm','consAjax');">
                                <div class="row">                                    
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-sm">C.&nbsp;Consumo:</label>  
                                            <div class="col-xs-9" > 
                                                <?php $cen_cons = $obBD_con1->getArrayConsulta(77, $Ses_Emp_Cod.'*A', $obBD_conexion); ?>
                                                <select name="Con_Cod" class="form-control input-sm">
                                                    <option value="" selected=""><< TODOS >></option>
                                                    <?php if(count($cen_cons)>0) foreach($cen_cons as $row){ echo "<option value='$row[Con_Cod]'>$row[Con_Des]</option>"; } ?>
                                                </select>
                                            </div>
                                        </div>  
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-sm">Rango:</label>  
                                            <div class="col-xs-8" > 
                                                <div class="input-group input-group-sm"> 
                                                    <span class="input-group-addon bold">Desde:</span> 
                                                    <input id="fec_ini" name="fec_ini" class="form-control"> 
                                                    <span class="input-group-addon bold">Hasta:</span> 
                                                    <input id="fec_fin" name="fec_fin" class="form-control"> 
                                                </div>
                                            </div>
                                            <div class="col-xs-2" ><button class="btn btn-sm btn-success" type="submit"><i class="fa fa-search" title="Buscar Consumos"></i> Buscar</button></div>
                                        </div>  
                                    </div>
                                </div> 
                                
                           </form>
                        </fieldset>
                        
                        <div style="min-height: 300px;">
                            <table id="comp"></table><div id="listPager"></div>
                        </div>
                         
                    </div>
                </div>    
              
            </div>   
        </div>
    </div>
    
   <script type="text/javascript">
        var gridComp;  
        $(document).ready(function() {           
            gridComp=$("#comp");   
            gridComp.createGrid({
                height: 250,caption:'&nbsp;',footerrow:true, userDataOnFooter:true,
                colModel: [
                    {name:'index',label:'Index', width:20, sorttype:"int",align:'center',key:true,hidden:true},
                    {name:'Pro_Cod',label:'Cód.Int.', width:20, sorttype:"int",align:'center',hidden:true}, 
                    {name:'Cop_Fec',label:'Fecha', width:40, align:'center'},  
                    {name:'Cop_Num',label:'Num. Doc.', width:60, align:'center'},  
                    {name:'Cop_Can',label:'Cant.', labelLong:'Cantidad', width:40, align:"right",classes:'columnHighlight3'},
                    {name:'Uni_Des',label:'Uni.', labelLong:'Unidad', width:25, resizable: false },
                    {name:'Ite_Lar',label:'Descripción', width:150},
                    {name:'Cop_Pru',label:'P. Unitario', labelLong:'Precio Unitario', width:60, align:"right"},                    
                    {name:'Cop_Imp',label:'Importe', width:70, align:"right", summaryRound: 2,formatter:"currency",formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.', defaultValue: '0.00'},classes:'columnHighlight1'},
                    {name:'Cop_Dec',label:'Descuen.', labelLong:'Descuento', align:"right", width:20},
                    {name:'subtotal',label:'Subtotal', width:70, align:"right", summaryRound: 2,formatter:"currency",formatoptions: {prefix:''},classes:'columnHighlight1'},
                    {name:'Iva_Por',label:'IVA', width:15,align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Grava IVA',noMsg:'No Grava IVA'}, title:false, resizable: false },                                 
                    {name:'Cop_Ice',label:'ICE', width:20, align:"right", title:true, resizable: false, formatter:'ice' },                   
                    {name:'total',label:'Total', width:70, align:"right", summaryRound: 2,formatter:"currency",classes:'columnHighlight4'},                   
                    {name:'Adq_Cod',label:'CodAdq', width:20,hidden:true},
                    {name:'Adq_Cor',label:'Adq.',labelLong:'Adquisiciones', width:20,align:"center", title:false, formatter:'title', formatoptions:{title:function(o){return o['Adq_Des'];}},resizable: false  } 
                ],
                loadComplete: function(data){ 
                    setCaption();
                    gridComp.setGridSummary(['total']); gridComp.jqGrid("footerData","set",{subtotal:'<div style="text-align:right;">TOTAL:</div>'},false);
                }
            },true,"listPager").gridButtonsAdd([
                {caption:'Imprimir',buttonicon:'print', onClickButton: function(){ printR(); } },
                {caption:'Descargar Excel',buttonicon:'download-alt', onClickButton: function(){ exportR(); } }
            ]);;
            $.createDateRange('#fec_ini','#fec_fin');
        });
        $.fn.fmatter.ice=function(cv,opts,cObjt){ var ice_por=cObjt['Cop_Ice']||cObjt['Ice_Por']; if($.varValid(ice_por)&&ice_por!==''&&!isNaN(ice_por)&&ice_por*1>0) return ice_por+' %'; else return ''; };
   </script>
   <script type="text/javascript">
       function setCaption(){
           var consumo=$('select[name=Con_Cod]').val();
           gridComp.setCaption((consumo===''?' Todos los Centros de Consumo - ':'Centro de Consumo: "'+$('select[name=Con_Cod] option:selected').text()+'" -')+' Desde '+$('#fec_ini').val()+' Hasta '+$('#fec_fin').val());
       }
       function printR() { 
            $('#titleReporte').html(gridComp.getCaption());
            $('#tablaReporte').html(gridComp.jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false}));            
            $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});                
        }
        function exportR() {
            $('#titleGrid').html(gridComp.getCaption());
            var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
            temp.append(gridComp.jqGrid('exportGridHTML',{generated:true,caption:false,bodyBorder:false,footer:true,sepEnd:true,removeHiddens:true}));           
            $.downloadFile($.exportarExcelBlob(temp.html(),'Digitacion'),'comprasConsumo_'+$.getDate()+'.xls');    
        }
   </script>
   <div id="formatoReporte" style="display: none;">
        <div style="width: 1030px;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE COMPRAS POR CONSUMO', '<span id="titleReporte"></span>',$obBD_conexion); ?>
        <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>       
        <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
        </div>
    </div>  
    <div id="formatoExportar" style="width: 700px;display: none;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE COMPRAS POR CONSUMO', '<span id="titleGrid"></span>',$obBD_conexion,false,14); ?>
    </div>  
   <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</BODY>
</HTML>