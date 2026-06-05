<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_kardex.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Kar($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Kar;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($proAjax)){
    $contar = $obBD_con1->getRowConsulta(1052, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(1052, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}
if(isset($ajaxProd)){
    try{
        $Ite_Cod=$Pro_Cod;$ini=$hoy;
        $kardex1 = $obBD_con1->getArrayConsulta(1048,$ini.'*'.$Ite_Cod, $obBD_conexion);
        if(count($kardex1)==1 && $kardex1[0]['Saldo']!==0 && $kardex1[0]['Stock']!=0){         
            $kardex1[0]['Promedio']=round(($kardex1[0]['Saldo']/$kardex1[0]['Stock']),6);
        }else{
            $kardex1[0]['Promedio']=0;$kardex1[0]['Saldo']=0;$kardex1[0]['Stock']=0;
        }
        list($ann, $mes, $dia) = preg_split('![/.-]!',$ini);
        $kardex1[0]['Kar_Det']='<b>Saldo al '.$dia.', de '.mes($mes, 1).', '.$ann.'</b>';
        $responce['stocks']=$kardex1[0];
        $responce['prod'] = $obBD_con1->getRowConsulta(1051,$Ite_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
        $responce['success']=true; 
    }catch(Exception $e){ $responce=array(success=>false,message=>'No se logro obtener información del Producto!',error=>$e); }        
    utf8_encode_deep($responce); echo json_encode($responce); exit();
}
if(isset($ajaxKardex)){ 
    try{
        $array=$obBD_con1->getArrayConsulta(5005, $Ses_Emp_Cod.'*'.$Alf_Min.'*'.$Alf_Max.'*'.$Cat_Cod.'*'.$Ubi_Cod.'*'.$grupo, $obBD_conexion);
        foreach ($array as $key => &$row) {            
            $kardex1 = $obBD_con1->getArrayConsulta(1048,$ini.'*'.$Ite_Cod, $obBD_conexion);
            if(count($kardex1)==1 && $kardex1[0]['Saldo']!==0 && $kardex1[0]['Stock']!=0){         
                $kardex1[0]['Promedio']=round(($kardex1[0]['Saldo']/$kardex1[0]['Stock']),6);
            }else{
                $kardex1[0]['Promedio']=0;$kardex1[0]['Saldo']=0;$kardex1[0]['Stock']=0;
            }
            $kardexHist = $obBD_con1->getArrayConsulta(1050,$ini.'*'.$fin.'*'.$row['Pro_Cod'], $obBD_conexion);
            if(count($kardexHist)>0) $kardex=array_merge($kardex1,$kardexHist);
            else $kardex=$kardex1;
            $x=COUNT($kardex); $row=array_merge($row,array(Precio_sal=>0,Precio_ent=>0,Kar_Can=>0,Kar_Prs=>0,Kar_Ims=>0,Kar_Sal=>0,Kar_Pre=>0,Kar_Ime=>0));
            for($i=1;$i<$x;$i++){
                if($kardex[$i]['Kar_Sal']*1!=0){
                    $kardex[$i]['Kar_Pre']=  empty($kardex[$i-1]['Promedio'])?0:$kardex[$i-1]['Promedio'];
                    $kardex[$i]['Kar_Ime']= round($kardex[$i]['Kar_Pre']*$kardex[$i]['Kar_Sal'],2);
                }
                $row['Precio_sal']+=$kardex[$i]['Precio_sal'];$row['Precio_ent']+=$kardex[$i]['Precio_ent'];
                $row['Kar_Can']+=$kardex[$i]['Kar_Can'];$row['Kar_Sal']+=$kardex[$i]['Kar_Sal'];
                $row['Kar_Ime']+=$kardex[$i]['Kar_Ime'];$row['Kar_Ims']+=$kardex[$i]['Kar_Ims'];
                $kardex[$i]['Stock']=$kardex[($i-1)]['Stock']*1+$kardex[$i]['Kar_Can']*1-$kardex[$i]['Kar_Sal'];
                $kardex[$i]['Saldo']=round($kardex[$i-1]['Saldo']*1+$kardex[$i]['Kar_Ims']*1-$kardex[$i]['Kar_Ime'],2);            
                $kardex[$i]['Promedio']=($kardex[$i]['Stock']!=0?$kardex[$i]['Saldo']/$kardex[$i]['Stock']:$kardex[$i-1]['Promedio']);
            }        
            $row['Stock']=(string)(empty($kardex[$x-1]['Stock'])?0.00:$kardex[$x-1]['Stock']);
            $row['Promedio']=(string)round((empty($kardex[$x-1]['Promedio'])?0.00:$kardex[$x-1]['Promedio']),8);
            $row['Saldo']=(string)(empty($kardex[$x-1]['Saldo'])?0.00:$kardex[$x-1]['Saldo']); 
        } unset($row);
        $responce['rows'] = $array;       
        $responce['records']=count($array);       
        $responce['success']=true;
    }catch(Exception $e){ $responce=array(success=>false,message=>'No se logro obtener información del Kardex!',error=>$e); }      
    utf8_encode_deep($responce); echo json_encode($responce); exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>   
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
        <style> 
            .chosen-default span{color:#555;}
            .chosen-single span{padding-left: 5px;}
        </style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Resumen de Existencias</h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">
                   
                    <div class="col-sm-12">
                       <form id="formKardex" class="form-horizontal normal"  action="javascript:$('#kardex').Search('#formKardex','ajaxKardex');"   >  
                       
                                <div class="row">
                                    <div class="col-xs-4">
                                  <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Filtros:</legend> <!-- Form Name -->                                    
                                      
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs ">Categoria:</label>  
                                          <div class="col-sm-8">                                    
                                            <?php $row_rs_categ = $obBD_con1->getArrayConsulta(5006, $Ses_Emp_Cod, $obBD_conexion); ?>
                                            <select name="Cat_Cod" id="Cat_Cod" class="form-control input-xs" data-placeholder="Todas">
                                                <option value=""></option>
                                                <?Php
                                                foreach ($row_rs_categ as $row) {
                                                    ?>
                                                    <option value="<?Php echo $row['Cat_Cod']; ?>" ><?Php echo /* strtoupper($row['Par_Cat_Des']).' » '. */$row['Cat_Des']; ?></option>
                                                    <?Php }
                                                ?>
                                            </select>                           
                                          </div>                                  
                                        </div>
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs ">Ubicación:</label>  
                                          <div class="col-sm-8">                                    
                                            <?php $rs_ubicacion = $obBD_con1->getArrayConsulta(5007, $Ses_Emp_Cod, $obBD_conexion); ?>
                                            <select name="Ubi_Cod" id="Ubi_Cod" class="form-control input-xs">
                                                <option value="">Todas</option> 
                                                <?Php
                                                foreach ($rs_ubicacion as $row) {
                                                    ?>
                                                    <option value="<?Php echo $row['Ubi_Cod']; ?>" ><?Php echo $row['Ubi_Des']; ?></option>
                                                    <?Php }
                                                ?>
                                            </select>                            
                                          </div>                                  
                                        </div>
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs ">Agrupar:</label>  
                                          <div class="col-sm-8">                                    
                                            <select name="grupo" id="grupo" class="form-control input-xs">
                                                <option value="clear">No Agrupar</option>
                                                <option value="Cat_Des">Categoria</option>
                                                <option value="Ubi_Des">Bodega</option>
                                                
                                            </select>                             
                                          </div>                                  
                                        </div>   
                                        </fieldset> 
                                        </div>
                                     <div class="col-xs-8">
                                        <fieldset class="exa-fieldset">                           
                                            <legend class="Titulos2">Rangos:</legend> <!-- Form Name -->
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <div class="form-group">
                                                        <label class="col-sm-2 control-label label-xs ">Rango:</label>
                                                        <div id="display" class="col-sm-1">A&nbsp;-&nbsp;Z</div>
                                                        <div class="col-sm-9" style="padding-top: 5px;"><div id="slider"></div><div id="Alfa" class="hidden"><input name="Alf_Min" type="text" value="A" /><input name="Alf_Max" type="text" value="Z" /></div></div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-2 control-label label-sm ">Desde:</label>
                                                        <div class="col-sm-3">     
                                                            <input name="ini" type="text" id="ini" class="form-control input-sm">                                                                                        
                                                        </div>
                                                        <label class="col-sm-2 control-label label-sm ">Hasta:</label>
                                                        <div class="col-sm-3">                                    
                                                            <input name="fin" type="text" id="fin" class="form-control input-sm">                              
                                                        </div>
                                                        <div class="col-xs-2">
                                                          <div class=""><button type="button"  onclick="this.form.submit();$('#kardex').jqGrid('setCaption', 'Existencias'+' - Rango ('+$('#display').text().replace(/\u00a0/g,'')+') - '+'Desde '+ $('#ini').val()+' Hasta '+$('#fin').val());" class="btn btn-sm btn-success" title="Ejecutar Búsqueda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
                                                        </div>
                                                      </div>
                                                </div>
                                            </div>
                                        </fieldset> 
                                     </div>    
                                </div>   
                        </form>  
                    </div>
                    <div class="col-sm-12" style="min-height: 400px;">
                        <table id="kardex"></table>
                        <div id="kardexPager"></div>
                        <script>
                             $(document).ready(function () {                                
                                var kardexGrid=$("#kardex");
                                kardexGrid.createGrid({                                    
                                    colModel: [                                        
                                        { label: 'Detalle',name: 'Ite_Lar', width: 90}, 
                                        
                                        { label: 'Entrada',name: 'Precio_sal', width: 35, align:"right",formatter:'currency',formatoptions:{defaultValue:''},classes:'columnHighlight5'},
                                        { label: 'Salida', name: 'Precio_ent', width: 35, align:"right",formatter:'currency',formatoptions:{defaultValue:''},classes:'columnHighlight5'},
                                        
                                         { label: 'Categoria',name: 'Cat_Des', width: 50,hidden:true},
                                         
                                        { label: 'Cant.',  name: 'Kar_Can', width: 25, align:"right",formatter:'intornumber',formatoptions:{defaultValue:''},classes:'columnHighlight1'},
                                        { label: 'V.Uni.', name: 'Kar_Prs', width: 35, align:"right",formatter:'number',formatoptions:{defaultValue:'',decimalPlaces: 6},classes:'columnHighlight1'},
                                        { label: 'V.Tot.', name: 'Kar_Ims', width: 40, align:"right",formatter:'number',formatoptions:{defaultValue:''},classes:'columnHighlight1'},
                                        
                                        { label: 'Cant.',  name: 'Kar_Sal', width: 25, align:"right",formatter:'intornumber',formatoptions:{defaultValue:''},classes:'columnHighlight3'},
                                        { label: 'V.Uni.', name: 'Kar_Pre', width: 35, align:"right",formatter:'number',formatoptions:{defaultValue:'',decimalPlaces: 6},classes:'columnHighlight3'},
                                        { label: 'V.Tot.', name: 'Kar_Ime', width: 40, align:"right",formatter:'number',formatoptions:{defaultValue:''},classes:'columnHighlight3'},
                                        
                                        { label: 'Cant.',  name: 'Stock', width: 25, align:"right",formatter:'intornumber',classes:'columnHighlight5'},
                                        { label: 'V.Uni.', name: 'Promedio', width: 35, align:"right",formatter:'number',formatoptions:{defaultValue:'',decimalPlaces: 6},classes:'columnHighlight5'},
                                        { label: 'V.Tot.', name: 'Saldo', width: 45, align:"right",formatter:'number',formatoptions:{defaultValue:''},classes:'columnHighlight5'},
                                        
                                        { label: 'Cod.Int.', name: 'Pro_Cod', key: true, hidden:true,viewable:true },
                                        { label: 'Ubicacion',name: 'Ubi_Des', width: 50,hidden:true}                                       
                                    ],     
                                    height: 290,caption:' ', rowNum: 10000000, pgbuttons: false,pgtext: null,loadonce:true,
                                    groupingView: {
                                        groupField: ["Ubi_Des"], groupColumnShow: [false],
                                        groupText: ["<div><span style='float:right;'> {1} Item(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  </div>"],
                                        groupOrder: ["asc"], groupSummary: [false], groupCollapse: false
                                    }, grouping: false
                                },true,"#kardexPager",{view:false,refresh:true}).setGroupHeaders({                                        
                                        groupHeaders: [
                                            { "numberOfColumns": 2, "titleText": "Valores", "startColumnName": "Precio_sal" },
                                            { "numberOfColumns": 3, "titleText": "Entradas", "startColumnName": "Kar_Can" },
                                            { "numberOfColumns": 3, "titleText": "Salidas", "startColumnName": "Kar_Sal" },
                                            { "numberOfColumns": 3, "titleText": "Existencias", "startColumnName": "Stock" }
                                        ],useColSpanStyle: true
                                    }).gridButtonsAdd([
                                        {caption: "Exportar Excel", buttonicon: "glyphicon glyphicon-download", onClickButton: function () { kardexGrid.jqGrid('exportGridExcel', {nombre: "Kardex", hoja: "HOJA 1"}); } },
                                        {caption: "Imprimir", buttonicon: "glyphicon glyphicon-print", onClickButton: function () { kardexGrid.jqGrid('printGrid', {nombre: "Resumen Kardex", hoja: "HOJA 1"}); } }
                                    ]);;
                                $.createDateRange('#ini','#fin');
                                $('#ini').val('2000-01-01');
                                $("#slider").slider({range: true,min: 65,max: 90,values: [ 65, 90 ],slide:function(event,ui){ $('#display').html(String.fromCharCode(ui.values[0])+'&nbsp;-&nbsp;'+String.fromCharCode(ui.values[1])).next().setData({Alf_Min:String.fromCharCode(ui.values[0]),Alf_Max:String.fromCharCode(ui.values[1])}); } });    
                                $("#Cat_Cod").createChosen('input-xs',{allow_single_deselect: true});
                                $("#grupo").change(function () {
                                    var vl = $(this).val();
                                    if (vl) if (vl === "clear")  kardexGrid.jqGrid('groupingRemove', true); else kardexGrid.jqGrid('groupingGroupBy', vl);                                    
                                    //kardexGrid.Search('#formKardex','ajaxKardex');
                                });
                            });                             
                            $.fn.fmatter.intornumber = function(cellval, opts) {
                                var op=(cellval % 1===0?$.extend({},opts.integer):$.extend({},opts.number)); 
                                if(opts.colModel !== undefined && opts.colModel.formatoptions !== undefined) {op = $.extend({},op,opts.colModel.formatoptions);}
                                if($.fmatter.isEmpty(cellval)||(!isNaN(cellval)&&cellval*1===0)) { return op.defaultValue; }
                                return $.fmatter.util.NumberFormat(cellval,op);
                            };
                            $.fn.fmatter.intornumber.unformat=function(cellval,options,element){ var opts=$.jgrid.getRegional(this, 'formatter')||{},op=$.extend({},opts.number,options.colModel.formatoptions||{}); return cellval.replace(new RegExp(op.thousandsSeparator.replace(/([\.\*\_\'\(\)\{\}\+\?\\])/g,"\\$1"), "g"),"").replace(op.decimalSeparator,'.'); };
                            function exportKardex(){
                                
                            }
                        </script>    
                    </div>  
                </div> 
        </div>
    </div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <div id="imprimirRoles" style="display: none;width: 1200px;">
        <table id="tableKardex" style="width:100%;font-size:11px;/*table-layout:fixed;*/border-collapse:collapse" cellpadding="2"></table>            
    </div>
</BODY>
</HTML>