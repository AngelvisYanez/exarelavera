<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
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

if(isset($proAjax)){
    $contar = $obBD_con1->getRowConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
if(isset($ajaxProd)){
    $Ite_Cod=$Pro_Cod;$ini=$hoy;
    $responce['success']=true;    
    
    $responce['prod'] = $obBD_con1->getRowConsulta(2,$Ite_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
    utf8_encode_deep($responce);
    echo json_encode($responce);exit();
}
if(isset($ajaxKardex)){ 
    $Ite_Cod=$Pro_Cod;
    
    $responce['rows']=$obBD_con1->getArrayConsulta(3 ,$Ses_Emp_Cod.'*'.$Ses_Suc_Cod.'*'.$Ite_Cod.'*'.$ini.'*'.$fin, $obBD_conexion);
    $responce['success']=true;$responce['records']=count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
?>

<meta charset="iso-8859-1" />

            
                <div class="row">
                   
                    <div class="col-xs-12">
                       <form id="formProdu" class="form-horizontal normal"  action="javascript:$('#listProd').Search('#formProdu','ajaxKardex');"   >
  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Descripción Producto:</legend> <!-- Form Name -->
                              <div class="row">                                  
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Descripción:</label>  
                                          <div class="col-xs-7">  
                                            <div class="input-group input-group-xs">                                                
                                                <input type="text" name="Pro_Cod" id="Pro_Cod" value="" style="display: none" />  
                                                <input id="producto"  type="text" class="form-control" placeholder="Seleccione un Producto ..." required readonly />
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success" onclick="$('#proDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                                                </span>
                                              </div><!-- /input-group -->                                 
                                          </div>  
                                          <div class="col-xs-2">  
                                            <button class="btn btn-xs btn-success" onclick="clearAll();" type="button"><span class="glyphicon glyphicon-eject" title="Buscar Proveedor"></span></button>
                                          </div> 
                                        </div>                                              
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Marca:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_mar"></span>                              
                                          </div>                                  
                                        </div>
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Codigo:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_cod_an"></span>                              
                                          </div>                                  
                                        </div>
                                  </div>
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Ubicacion:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_ubi"></span>                              
                                          </div>                                  
                                        </div>
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Linea:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="lin_des"></span>                              
                                          </div>                                  
                                        </div>  
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Categoria:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_cat"></span>                              
                                          </div>                                  
                                        </div>                                     
                                                                    
                                  </div>
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Adquisición:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_adq"></span>                              
                                          </div>                                  
                                        </div>
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">IVA:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_iva"></span>                              
                                          </div>                                  
                                        </div>                                      
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Cod. Barras:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_bar"></span>                              
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
                                                            <input name="ini" type="text" id="iniProd" class="form-control input-sm">                                                                                        
                                                        </div>
                                                        <label class="col-xs-2 control-label label-xs ">Hasta:</label>
                                                        <div class="col-xs-3">                                    
                                                            <input name="fin" type="text" id="finProd" class="form-control input-sm">                              
                                                        </div>
                                                        <div class="col-xs-2">
                                                          <div class=""><button type="button"  onclick="/*if($('#Pro_Cod').val()!==''){*/this.form.submit();$('#listProd').jqGrid('setCaption', 'Salidas de Mercaderia '+' - '+($('#producto').val()!==''?$('#producto').val()+' - ':'')+'Desde '+ $('#iniProd').val()+' Hasta '+$('#finProd').val());/*}else{$.alert('Seleccione el Producto');}*/" class="btn btn-sm btn-success" title="Ejecutar BÃºsqueda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
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
                        <table id="listProd"></table>
                        <div id="listProdPager"></div>
                        <script>
                             $(document).ready(function () {
                                $.createDateRange('#iniProd','#finProd');
                                var kardexGrid=$("#listProd");
                                kardexGrid.jqGrid({
                                    url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                    mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                                    //postData: $("#form1").getData("ajaxGrid"),
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,
                                    caption:' ',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Cod.Int.', name: 'Vet_Key', key: true, hidden:true,viewable:true }, 
                                        { label: 'Fecha', name: 'Caj_Fec' ,align:"center", width: 40  }, 
                                        { label: 'Cliente', name: 'Cliente', width: 120  }, 
                                        //{ label: 'Producto', name: 'Ite_Lar', width: 120  }, 

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
                                    rowNum: 10000000, pager: "#listProdPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,                           
                                    loadComplete: function () {                       
                                            kardexGrid.jqGrid('footerData', 'set', { Vet_Can:kardexGrid.jqGrid('getCol','Vet_Can',false,'sum'),Ite_Lar: '<div style="text-align:right;">Totales:</div>',Total:kardexGrid.jqGrid('getCol','Total',false,'sum'),Vet_Imp:kardexGrid.jqGrid('getCol','Vet_Imp',false,'sum'),Iva:kardexGrid.jqGrid('getCol','Iva',false,'sum'),Descuento:kardexGrid.jqGrid('getCol','Descuento',false,'sum') });                     
                                    },
                                    groupingView: {
                                        groupField: ["Cliente"], groupColumnShow: [true],
                                        groupText: ["<div><span style='float:right;'> {1} Doc(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  </div>"],
                                        groupOrder: ["asc"], groupSummary: [false], groupCollapse: true
                                    }, grouping: true
                                });  
                                
                                kardexGrid.navGrid('#listProdPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: false, position: "left", cloneToTop: false });

                               
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
                        <button class="btn btn-sm btn-primary" onclick="$('#listProd').jqGrid('printGrid',{nombre:'Ventas',hoja:'Salidas',caption:true});" type="button" title="Imprimir    "><span class="glyphicon glyphicon-print"></span> Imprimir</button>
                        <button onclick="$('#listProd').jqGrid('exportGridExcel',{nombre:'Ventas',hoja:'Salidas',caption:true});" class="btn btn-sm btn-primary start" title="Descargar archivo de Excel"> <i class="icon-share icon-white"></i> <span>Excel</span></button>
                    </div>
                </div> 

<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="proDialog" title="B&uacute;squeda de Productos">  
        <form class="form-horizontal normal"> 
        <fieldset class="exa-fieldset">
		<legend  class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-5 radiosetprod" >
                          <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                          <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>                          
                    </div>                  
                       
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>  
                    <div class="col-md-7" >
                        <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus  class="form-control input-sm "/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Producto" ><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                      </div><!-- /input-group --> 
                    </div>                    
                </div>
        </fieldset>  
       </form> 
    </div> 
<!-- FIN DEL DIALOGO CUENTAS-->    
<script>
        // DIALOG BUSCAR CUENTAS            
             $.createSearchDialog('proDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 15,align:"center",hidden:true },                                
                    { label: 'Descripción', name: 'Ite_Lar', width: 110 },                      
                    { label: 'Marca', name: 'Mar_Des', width: 40},
                    { label: 'Tipo', name: 'Cat_Des', width: 110,align:"center" },                    
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 20, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                                return  '<span class="btn btn-success btn-xs" title="Enviar al Cr&eacute;dito" onclick="SelectProd(\''+rowObject.Pro_Cod+'\',\''+rowObject.Ite_Lar+'\');"><i class="glyphicon glyphicon-arrow-right"></i></span>'; 
                            }
                        }
                ],null,null,true,'<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>');
            function SelectProd(id,desc){
                $('#Pro_Cod').val(id);
                $('#producto').val(desc);
                var today = new Date();
                //$('#ini').datepicker("setDate", new Date(today.getTime() - (30 * 24 * 3600 * 1000)));
                //$('#fin').datepicker("setDate", today);
                $('#proDialog').dialog('close');
                $.get('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',{'Pro_Cod':id,'ajaxProd':true}, function(response){
                    if(response['success']===true){
                        $('#pro_cat').html(response['prod']['Cat_Des']);
                        $('#lin_des').html(response['prod']['Lin_Des']);
                        $('#pro_cod_an').html(response['prod']['Cha_Cod']);
                                                
                        $('#pro_mar').html(response['prod']['Mar_Des']);
                        $('#pro_adq').html(response['prod']['Adq_Des']);
                        
                        $('#pro_iva').html(response['prod']['Iva_Por']);
                        $('#pro_bar').html(response['prod']['Pro_Bar']);
                        $('#pro_ubi').html(response['prod']['Ubi_Des']);

                    }else {$.alert("No se logro obtener informacion deñ Producto!");}                                
                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;
                $('#listProd').jqGrid('setCaption', 'Salidas de Mercaderia '+' - '+desc+' - '+'Desde '+ $('#iniProd').val()+' Hasta '+$('#finProd').val());
                $('#listProd').Search('#formProdu','ajaxKardex');
            }
            function clearAll(){
                $('#Pro_Cod').val('');
                $('#producto').val('');
                
                $('#pro_cat').html('');
                $('#lin_des').html('');
                $('#pro_cod_an').html('');

                $('#pro_mar').html('');
                $('#pro_adq').html('');

                $('#pro_iva').html('');
                $('#pro_bar').html('');
                $('#pro_ubi').html('');

                
                $('#listProd').jqGrid('setCaption', '');
                $('#listProd').clearGrid();
            }
</script>  
<script>  
$(document).ready(function() {  
    $( ".radiosetprod" ).buttonset();                  
});
</script>  

