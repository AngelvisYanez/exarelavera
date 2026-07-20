<?php	
/**
* @abstract Permite realizar movimientos de inventario
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/inv_log_inventario.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Inv($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Inv;


$hoy = date("Y-m-d");
$mes = date("m");

if(isset($productos)){
    $array=$obBD_con1->getArrayConsulta(14, $Ses_Emp_Cod, $obBD_conexion);
    foreach ($array as $key => &$row) {
        $row['Pro_Sal']=(string)round($row['Pro_Stk']*$row['Pro_Prp'],2);
        $kardexHist = $obBD_con1->getArrayConsulta(13,$ini.'*'.$fin.'*'.$row['Pro_Cod'], $obBD_conexion);
        $kardex=array_merge(array(0=>array()),$kardexHist); $x=COUNT($kardex);
        for($i=1;$i<$x;$i++){
            if($kardex[$i]['Kar_Sal']*1!=0){
                $kardex[$i]['Kar_Pre']=  empty($kardex[$i-1]['Promedio'])?0:$kardex[$i-1]['Promedio'];
                $kardex[$i]['Kar_Ime']= round($kardex[$i]['Kar_Pre']*$kardex[$i]['Kar_Sal'],2);
            }
            $kardex[$i]['Stock']=($i > 0 ? $kardex[($i-1)]['Stock']*1 : 0)+$kardex[$i]['Kar_Can']*1-$kardex[$i]['Kar_Sal'];
            $kardex[$i]['Saldo']=round(($i > 0 ? $kardex[$i-1]['Saldo']*1 : 0)+$kardex[$i]['Kar_Ims']*1-$kardex[$i]['Kar_Ime'],2);            
            $kardex[$i]['Promedio']=($kardex[$i]['Stock']!=0?round($kardex[$i]['Saldo']/$kardex[$i]['Stock'],2):($i > 0 ? $kardex[$i-1]['Promedio'] : 0));
        }        
        $row['Kar_Stk']=(string)(empty($kardex[$x-1]['Stock'])?0.00:$kardex[$x-1]['Stock']);
		$row['Real']=$row['Kar_Stk'];
        $row['Kar_Prp']=(string)round((empty($kardex[$x-1]['Promedio'])?0.00:$kardex[$x-1]['Promedio']),8);
        $row['Kar_Sal']=(string)(empty($kardex[$x-1]['Saldo'])?0.00:$kardex[$x-1]['Saldo']); 
        //var_dump($row['Kar_Stk'],$row['Pro_Stk']);
        //var_dump($row['Pro_Cod'],round($row['Kar_Prp'],8)==round($row['Pro_Prp'],8)); 
        /*if(($row['Kar_Stk']==$row['Stk_Can']))
           unset($array[$key]);*/
    }    
    $responce['rows'] = array_values($array);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
if(isset($saveStock)){
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion); 
        foreach ($stocks AS $row){
            $stk=array('Pro_Cod'=>$row['Pro_Cod'],'Pro_Stk'=>$row['Kar_Stk'],'Pro_Prp'=>$row['Kar_Prp']);
            $obBD_con1->operacionobBD(15,$stk, $obBD_conexion);
        }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0) $responce['success']=true; else {$responce['success']=false; $responce['error']=$obBD_con1->MsgError;}
    echo json_encode($responce);exit();
}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
                <style>
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Ajuste de Inventario General</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">                   
                       
                    <div class="col-xs-12" style="min-height: 350px;">                       
                        <table id="prods"></table>
                        <div id="prodsPager"></div>
                        <div style="padding-top: 10px;">
                            <button class="btn btn-success btn-sm btn-frm" onclick="validar();" type="button"><span class="glyphicon glyphicon-floppy-disk" title="Agregar Producto"></span> Guardar</button>                            
                        </div>
                        <script>
                            
                            var kardexGrid=$("#prods");
                            $(document).ready(function () {
                                $.createDialog('#successDialog',150,550);
                                
                                kardexGrid.jqGrid({
                                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                    mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
                                    postData: {productos:true},
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,footerRow:true,
                                    caption:'Listado de Productos',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'C�d.Int.', name: 'Pro_Cod', key: true, hidden:false,viewable:true, width: 25,align:'center' },                                        
                                        { label: 'Detalle',name: 'Ite_Lar', width: 150},                                        
                                        { label: 'Stock',name: 'Pro_Stk', width: 30,classes:'columnHighlight3',align:'center',formatter:'number'},
                                        { label: 'P. Promedio',name: 'Pro_Prp', width: 40,classes:'columnHighlight3',align:'right'},
										{ label: 'Saldo',name: 'Pro_Sal', width: 40,classes:'columnHighlight3',align:'right',formatter:'currency', formatoptions: {prefix:'$', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum"},
										{ label: 'Stock',name: 'Stk_Can', width: 30,classes:'columnHighlight3',align:'center',formatter:'number'},
                                        { label: 'P. Promedio',name: 'Stk_Prp', width: 40,classes:'columnHighlight3',align:'right'},
                                        { label: 'Saldo',name: 'Stk_Sal', width: 40,classes:'columnHighlight3',align:'right',formatter:'currency', formatoptions: {prefix:'$', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum"},
                                        { label: 'Stock',name: 'Kar_Stk', width: 30,classes:'columnHighlight1',align:'center',formatter:'number'},
                                        { label: 'P. Promedio',name: 'Kar_Prp', width: 40,classes:'columnHighlight1',align:'right'},
                                        { label: 'Saldo',name: 'Kar_Sal', width: 40,classes:'columnHighlight1',align:'right',formatter:'currency', formatoptions: {prefix:'$', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum"},
                                        { label: 'Stock Real',name: 'Real', width: 30,classes:'columnHighlight1',align:'center',formatter:'number',formatoptions:{defaultValue:''},editable:true,editoptions:{dataInit:styleInput}},
										{ label: 'Stock Dif.',name: 'Dif', width: 30,classes:'columnHighlight1',align:'center',formatter:'number'},
                                        { label:'&nbsp;', name: 'act1', width: 15, align: 'center',viewable: false,
                                            formatter:function (cellvalue, options, rowObject) {        
                                                var click='$(\'#prods\').jqGrid(\'delRowData\',\''+rowObject.Pro_Cod+'\');';
                                                return  '<button type="button" class="btn btn-danger btn-xs btn-frm" title="Eliminar" onclick="'+click+'"><i class="glyphicon glyphicon-trash"></i></button>';                                                
                                            }
                                        }
                                           
                                    ],     
                                    footerrow: false, userDataOnFooter: false,
                                    rowNum: 10000000, pager: "#kardexPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,
									loadComplete:function(){
										kardexGrid.startGridEdit();
									}
                                }); 
                                kardexGrid.jqGrid('setGroupHeaders', {
                                   useColSpanStyle: true, 
                                   groupHeaders:[
                                         {startColumnName: 'Pro_Stk', numberOfColumns: 3, titleText: 'Stock Base'},
										 {startColumnName: 'Stk_Can', numberOfColumns: 3, titleText: 'Stock Sucu'},
                                         {startColumnName: 'Kar_Stk', numberOfColumns: 3, titleText: 'Stock Kardex'}
                                   ]	
                                 });
                            });
                            
                        </script>    
                    </div>  
                </div> 
        </div>
    </div>
    <script>
        function validar(){
            var data=kardexGrid.getGridBatch();
            if(data.length===0) {$.alert('No hay datos!'); return;}
            else $.createDialogConfirm('Esta seguro que desea <b>guardar</b> los <b><u>Stocks</u></b>.',data,guardar);
        }
        function guardar(data){            
            $('.btn-frm').attr('disabled','disabled');
            $.saveDataJson("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{saveStock:true,stocks:data},
                function (response){kardexGrid.trigger('reloadGrid', [{ page: 1 }]);},null,null,
                function (){$('.btn-frm').removeAttr('disabled');}
            );
        }
		function styleInput(e,obj,opt){            
            e.style.textAlign = 'right'; 
            $(e).on('change',function (){
               if(isNaN(this.value)||this.value===''){ $(this).val('0').focus(); return false; }
               else{ 
					var row=kardexGrid.jqGrid('getRowData',obj['rowId']);
					kardexGrid.changeRow(obj['rowId'],{Dif:this.value*1-row['Stk_Can']*1});
               }
            });
        }
    </script>
</BODY>
</HTML>