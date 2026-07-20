<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/inv_log_ventas.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Ven($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Ven;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($clieAjax)){ 
   $data=filter_input_array(INPUT_GET);
   $data["Emp_Cod"]=$Ses_Emp_Cod;   
    $contar = $obBD_con1->getRowConsulta(4, $data, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0)
        $responce['rows'] =  $obBD_con1->getArrayConsulta(4, $data, $obBD_conexion);
    utf8_encode_deep($responce['rows']); 
    echo json_encode($responce);exit();
}
if(isset($ajaxKardex)){ 
    $Ite_Cod=$Pro_Cod;
    
    $responce['rows']=$obBD_con1->getArrayConsulta(5 ,$Ses_Emp_Cod.'*'.$Ses_Suc_Cod.'*'.$Cli_Cod.'*'.$ini.'*'.$fin, $obBD_conexion);
    $responce['success']=true;$responce['records']=count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
?>

<meta charset="iso-8859-1" />

            
                <div class="row">
                   
                    <div class="col-xs-12">
                       <form id="formClie" class="form-horizontal normal"  action="javascript:$('#listClie').Search('#formClie','ajaxKardex');"   >
  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Descripci�n Cliente:</legend> <!-- Form Name -->
                              <div class="row">                                  
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Descripci�n:</label>  
                                          <div class="col-xs-9">  
                                            <div class="input-group input-group-xs">                                                
                                                <input type="text" name="Cli_Cod" id="Cli_Cod" value="" style="display: none" />  
                                                <input id="Cli_Ced"  type="text" class="form-control" placeholder="Seleccione un Cliente ..." required readonly />
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success" onclick="$('#clieDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Cliente"></span></button>
                                                </span>
                                              </div><!-- /input-group -->                                 
                                          </div>  
                                          
                                        </div>                                              
                                      
                                  </div>
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Cliente:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="Cliente"></span>                              
                                          </div>                                  
                                        </div>                                                                  
                                                                    
                                  </div>
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Direcci�n:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="cli_dir"></span>                              
                                          </div>                                  
                                        </div>                                                                    
                                  </div>
                              </div>  
                              

                               
                        </fieldset> 
                        
                                <div class="row">
                                    <div class="col-xs-4">
                                  
                                        </div>
                                     <div class="col-xs-8">
                                        <fieldset class="exa-fieldset">                           
                                            <legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <div class="form-group">
                                                        <label class="col-xs-2 control-label label-xs ">Desde:</label>
                                                        <div class="col-xs-3">     
                                                            <input name="ini" type="text" id="iniClie" class="form-control input-sm">                                                                                        
                                                        </div>
                                                        <label class="col-xs-2 control-label label-xs ">Hasta:</label>
                                                        <div class="col-xs-3">                                    
                                                            <input name="fin" type="text" id="finClie" class="form-control input-sm">                              
                                                        </div>
                                                        <div class="col-xs-2">
                                                          <div class=""><button type="button"  onclick="/*if($('#Pro_Cod').val()!==''){*/this.form.submit();$('#listClie').jqGrid('setCaption', 'Salidas de Mercaderia '+' - '+($('#producto').val()!==''?$('#producto').val()+' - ':'')+'Desde '+ $('#iniClie').val()+' Hasta '+$('#finClie').val());/*}else{$.alert('Seleccione el Producto');}*/" class="btn btn-sm btn-success" title="Ejecutar Búsqueda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
                                                        </div>
                                                      </div>
                                                </div>
                                            </div>
                                        </fieldset> 
                                     </div>    
                                </div>    
                        
                           
                           
                                
                        </form>  
                    </div>
                    <div class="col-xs-12" style="min-height: 350px;">
                        <table id="listClie"></table>
                        <div id="listCliePager"></div>
                        <script>
                             $(document).ready(function () {
                                $.createDateRange('#iniClie','#finClie');
                                var kardexGrid=$("#listClie");
                                kardexGrid.jqGrid({
                                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                    mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                                    //postData: $("#form1").getData("ajaxGrid"),
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,
                                    caption:' ',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Cod.Int.', name: 'Vet_Key', key: true, hidden:true,viewable:true }, 
                                        { label: 'Fecha', name: 'Caj_Fec' ,align:"center", width: 40  }, 
                                        //{ label: 'Cliente', name: 'Cliente', width: 120  }, 
                                        { label: 'Producto', name: 'Ite_Lar', width: 120  }, 

                                        { label: 'Num. Doc.',name: 'Vet_Num', width: 55,classes:'columnHighlight2'},
                                       
                                        { label: 'Cant.',  name: 'Vet_Can', width: 25, align:"right",formatter:'interger',classes:'columnHighlight1',summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'},
                                        { label: 'Unidad',name: 'Uni_Des', width: 25, align:"center" },
                                        { label: 'V.Uni.', name: 'Vet_Pru', width: 35, align:"right",formatter:formatPrecio,classes:'columnHighlight1'},
                                        { label: 'V.Tot.', name: 'Vet_Imp', width: 40, align:"right",formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round',classes:'columnHighlight1'},
                                        
                                        { label: 'Desc.',  name: 'Descuento', width: 25, align:"right",formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round',classes:'columnHighlight2'},
                                        { label: 'IVA', name: 'Iva', width: 35, align:"right",formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round',classes:'columnHighlight2'},
                                        { label: 'Total', name: 'Total', width: 40, align:"right",formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},classes:'columnHighlight2',summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'},
                                        { label: 'C.Uni.', name: 'Unitario', width: 40, align:"right",formatter:formatPrecio,classes:'columnHighlight2'},
                                        
                                        
                                    ],     
                                    footerrow: true, userDataOnFooter: false,
                                    rowNum: 10000000, pager: "#listCliePager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,                           
                                    loadComplete: function () {                       
                                            kardexGrid.jqGrid('footerData', 'set', { Vet_Can:kardexGrid.jqGrid('getCol','Vet_Can',false,'sum'),Ite_Lar: '<div style="text-align:right;">Totales:</div>',Total:kardexGrid.jqGrid('getCol','Total',false,'sum'),Vet_Imp:kardexGrid.jqGrid('getCol','Vet_Imp',false,'sum'),Iva:kardexGrid.jqGrid('getCol','Iva',false,'sum'),Descuento:kardexGrid.jqGrid('getCol','Descuento',false,'sum') });                     
                                    },
                                    groupingView: {
                                        groupField: ["Ite_Lar"], groupColumnShow: [true],
                                        groupText: ["<div><span style='float:right;'> {1} Doc(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  </div>"],
                                        groupOrder: ["asc"], groupSummary: [true], groupCollapse: true
                                    }, grouping: true
                                });  
                                
                                kardexGrid.navGrid('#listCliePager',{ edit: false, add: false, del: false, search: false, refresh: true, view: false, position: "left", cloneToTop: false });

                               
                            }); 
                            function formatInt(cellValue, options, rowdata, action) {
                                if (cellValue === ""|| cellValue*1 === 0 || isNaN(cellValue) || cellValue === null || cellValue === 'null') return ""; 
                                return cellValue;
                            }
                            function formatPrecio(cellValue, options, rowdata, action) {
                                if (cellValue === ""|| cellValue*1 === 0 || isNaN(cellValue) || cellValue === null || cellValue === 'null') return "";                                
                                var number = parseFloat(cellValue).toFixed(6);
                                return number;                                
                            }
                            function formatValor(cellValue, options, rowdata, action) {
                                if (cellValue === ""|| cellValue*1 === 0 || isNaN(cellValue) || cellValue === null || cellValue === 'null') return "";                                
                                var number = parseFloat(cellValue).toFixed(2);          //  Give us our number to 2 decimal places
                                return $.fn.fmatter.call(this, "currency", number, options);                                
                            }
                        </script>    
                    </div>  
                    <div class="col-xs-12" style="padding-top: 10px;">
                        <button class="btn btn-sm btn-primary" onclick="$('#listClie').jqGrid('printGrid',{nombre:'Ventas',hoja:'Salidas',caption:true});" type="button" title="Imprimir    "><span class="glyphicon glyphicon-print"></span> Imprimir</button>
                        <button onclick="$('#listClie').jqGrid('exportGridExcel',{nombre:'Ventas',hoja:'Salidas',caption:true});" class="btn btn-sm btn-primary start" title="Descargar archivo de Excel"> <i class="icon-share icon-white"></i> <span>Excel</span></button>
                    </div>
                </div> 

<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="clieDialog" title="B�squeda de Clientes">  
      <form class="form-horizontal normal"> 
        <fieldset class="exa-fieldset">
		<legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-8 radiosetclie" >
                          <input id="rad1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                          <input id="rad2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad2">&nbsp;&nbsp;C�dula/R.U.C.&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7" >                 
                      <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese proveedor a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Cliente" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                      </div><!-- /input-group -->                          
                    </div>                    
                </div>
        </fieldset>  
       </form>    
    </div>
    <script type="text/javascript">
        $(document).ready(function() {               
                $.createSearchDialog('#clieDialog',[
                        { label: 'C�d.Int.', name: 'Cli_Cod', key: true,hidden:true,viewable: true },                                
                        { label: 'C�dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                        { label: 'Cliente', name: 'Cliente', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                        { label: 'Direcci�n', name: 'Prs_Dir',hidden:true,viewable: true },                      
                            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                formatter:function (cellvalue, options, rowObject) { 
                                    var clic='selectClie($("#clieGrid").jqGrid("getRowData",'+rowObject.Cli_Cod+'))';
                                    return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                                }
                            }
                    ],null,null,true,'<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>');  
                                     
        }); 
        function selectClie(data){
            $('#Cli_Cod').val(data['Cli_Cod']);
            $('#Cli_Ced').val(data['Prs_Ced']);
            $('#cli_dir').html(data['Prs_Dir']);
            $('#Cliente').html(data['Cliente']);
            $('#clieDialog').dialog('close');
            $('#listClie').jqGrid('setCaption', 'Salidas de Mercaderia '+' - '+data['Prs_Ced']+' - '+'Desde '+ $('#iniClie').val()+' Hasta '+$('#finClie').val());
            $('#listClie').Search('#formClie','ajaxKardex');
        }
    </script>
<!-- FIN DEL DIALOGO CLIENTE-->
 

<script>  
$(document).ready(function() {  
    $( ".radiosetclie" ).buttonset();                  
});
</script>  

