<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_facturaVenta;

$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* Busqueda de Clientes */
if(isset($cliAjax)||isset($cliFactAjax)){
    $obBD_con1->getPageGridJson(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion,$page,$rows);    
}
/* Busqueda de Notas de Venta */
if(isset($ajaxND_Ventas)){ 
    $data=$_GET;
    $data["Suc_Cod"]=$Ses_Suc_Cod;  
    $obBD_con1->getPageGridJson(126, $data, $obBD_conexion);
    $responce['rows'] = $obBD_con1->getArrayConsulta(126,$data, $obBD_conexion);   
    $responce['records']=count($responce['rows']);
    $obBD_con1->echoJson($responce); 
}
$row_tipo_compr = $obBD_con1->getArrayConsulta(134, '', $obBD_conexion);	
foreach ($row_tipo_compr as $row)
    if($row['Tic_Sri']=='01')
    {$Tic_Cod=$row['Tic_Cod'];break;}
$row_rs_vendedor = $obBD_con1->getRowConsulta(124, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod,$obBD_conexion);
$rs_infoEmpresa = $obBD_con1->getRowConsulta(125, $Ses_Suc_Cod, $obBD_conexion);
/* Cargar la cuentas contables para pagos en efectivo */
if(isset($cuentas)){ 
    $responce=array('success'=>true, 'html'=>'');
    $row_rs_bancos = $obBD_con1->getArrayConsulta(128, '1*'.$Pec_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
    if(count($row_rs_bancos)>1)$responce['html']= '<option value="">Seleccione...</option>';
    foreach ($row_rs_bancos as $row){
        $responce['html']=$responce['html'].'<option value="'.$row['Pld_Cod'].'">'.$row['Ban_Des'].'</option>';
    } 
    $obBD_con1->echoJson($responce);    
}
/* Valida Numero de Factura */
if(isset($valVetNum)){ 
    $responce['Vet_Num']=$valVetNum*1;$responce['exist']=false;$responce['valid']=false;
    $row_rs_autorizaci = $obBD_con1->getRowConsulta(133, $Tic_Cod.'*'.$row_rs_vendedor['Pun_Cod'].'*'.$Caj_Fec, $obBD_conexion); 
//    foreach ($row_rs_autorizaci as $row) {
        $row_rs_buscaNumVenta= $obBD_con1->getRowConsulta(131, $row_rs_autorizaci['Aut_Sri'].'*'.$valVetNum,$obBD_conexion);
	$total_rs_buscaNumVenta=$row_rs_buscaNumVenta['Vet_Cod'] > 0? 1 : 0;
        if($total_rs_buscaNumVenta==1)
            {$responce['exist']=true;}
//    }
//    foreach ($row_rs_autorizaci as $row) {
        if($row_rs_autorizaci['Aut_Ini']*1<=$valVetNum && $row_rs_autorizaci['Aut_Fin']*1>=$valVetNum)
        {$responce['valid']=true;}
        else{$responce['message']='El rango esta entre <b>'.$row_rs_autorizaci['Aut_Ini'].'</b> y <b>'.$row_rs_autorizaci['Aut_Fin'].'</b>.';}
//    }
    $responce['success']=true;
    $obBD_con1->echoJson($responce);
}
/* Guardar La Factuta*/
if(isset($saveForm)){
    $response=array("success"=>true,"message"=>"");
    $Vet_Cod=str_replace(",", " OR ventas.Vet_Cod=", "(ventas.Vet_Cod=".$Ventas_ND.")");
    $items = $obBD_con1->getArrayConsulta(135,$Vet_Cod, $obBD_conexion);  
     /* Creacion de Objetos de Conexiones para Proceso de Guardado de Venta*/
    $obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_facturaVenta;
    $obBD_conIns->inicio_transaccion($obBD_conexionIns);    
    try{        
        foreach ($items as $i => $item){
            $add=true;
            $stock=$obBD_con1->getRowConsulta(136, $item['Pro_Cod'], $obBD_conexion);
            if(isset($stock['Pro_Cod']) && !empty($stock['Pro_Cod'])){
                $add=false;
                $canti=($stock['Pro_Can']*1)+($item['Vet_Can']*1);
                $impor=($stock['Pro_Imp']*1)+($item['Importe']*1);
                $obBD_conIns->operacionobBD(138, $item['Pro_Cod'].'*'.$canti.'*'.$impor , $obBD_conexionIns); 
            }
            if($add){
                $obBD_conIns->operacionobBD(137, $item['Pro_Cod'].'*'.$item['Vet_Can'].'*'.$item['Importe'] , $obBD_conexionIns); 
            }
        }
        $obBD_conIns->operacionobBD(132, $Vet_Cod , $obBD_conexionIns);  
        $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns);
    } catch (Exception $ex) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns);  
        $response=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$ex->getMessage());        
        $obBD_con1->echoJson($response);
    }
    if ($obBD_conIns->Error != 0) {
        $response=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_conIns->MsgError);
    }
    $obBD_con1->echoJson($response);
}

/**
* Consulta del vendedor en base al codigo de la persona
*/

$row_rs_autorizaci = $obBD_con1->getRowConsulta(133, $Tic_Cod.'*'.$row_rs_vendedor['Pun_Cod'].'*'.$hoy, $obBD_conexion);      
//var_dump($row_rs_autorizaci);                      

?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
        <style>                     
            .label-xs.required{padding-top: 4px !important;}
            /*.ui-jqgrid td input, .ui-jqgrid td select, .ui-jqgrid td textarea {padding-top: 2px;}*/
            .footrow td[aria-describedby="items_Importe"],.footrow td[aria-describedby="items_Vet_Pru"]{padding: 0 !important;}
            .footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact input[type=text]{ padding: 0; }
            .footerFact textarea{text-align: left; height: 50px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}
        </style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestion de Stocks de Venta No Contabilizadas</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">            
                <div class="row">
                    <?php if(isset($ND_Ventas)){ ?>
                    <div class="col-sm-12">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Agrupar Stocks Ventas ND</legend> <!-- Form Name -->
                          <div class="row">
                           <div class="col-sm-12">
                               <input type="hidden" id="ventas_cambio" name="ventas_cambio" value="<?php echo $ND_Ventas; ?>" >
                            <table id="items"></table>
                            <div id="pitems"></div>
                           </div> 
                              <div class="col-sm-12" style="padding-top:10px">
                                  
                                  <button type="button" class="btn btn-inverse btn-sm" title="Atrás" onclick="window.history.back();" >
                                                <i class="glyphicon glyphicon-arrow-left"></i>
                                                <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
                                   </button>
                                  <button id="btnGuardar" type="button" class="btn btn-primary btn-sm" title="Guardar" onclick="saveForm();">
                                       <i class="glyphicon glyphicon-floppy-disk"></i>
                                       <span>&nbsp;&nbsp;Guardar</span>
                                </button>
                                  
                             
                           </div>
                        </div>   
                           <?php  
                             $NDs=explode(',',$ND_Ventas);
                             $Vet_Cod=str_replace(",", " OR ventas.Vet_Cod=", "(ventas.Vet_Cod=".$ND_Ventas.")");
                             $responce['rows'] = $obBD_con1->getArrayConsulta(135,$Vet_Cod, $obBD_conexion);  
                             //var_dump($responce);
                           ?>

                        </fieldset>
                    </div>

                    
                        <script type="text/javascript">
                           $(document).ready(function () { 
                                    
                                    $("#items").jqGrid({
                                         data:<?php echo json_encode($responce['rows']); ?>,
                                         datatype: "local",                                        
                                         rowNum: 10000000, rownumbers:true,
                                         pgtext: ' ',   
                                         autowidth : true, shrinkToFit: true, height: 100,responsive:true,
                                         //colNames:['Inv No','Date', 'Client', 'Amount','Tax','Total','Notes'],
                                         colModel:[
                                                 {name:'Pro_Cod',label:'Cód. Int', width:60, sorttype:"int",align:'center'},
                                                 {name:'Iva_Cod',label:'CodIva', width:20,hidden:true},
                                                 {name:'Producto',label:'Producto', width:200},                                                 
                                                 {name:'Vet_Can',label:'Cant.', width:40, align:"right"},
                                                 {name:'Vet_Pru',label:'P. Unitario', width:60, align:"right", summaryRound: 4,formatter:"currency",
                                                    formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.',decimalPlaces: 4, defaultValue: '0.0000'}},
                                                 {name:'Importe',label:'Importe', width:70,align:"right", summaryRound: 2,formatter:"currency",
                                                    formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.', defaultValue: '0.00'}},
                                                 
                                                 {name:'Iva_Por',label:'IVA', width:20,align:"right"},
                                                 {name:'Adq_Cor',label:'Adq.', width:20,align:"center"}                                                 
                                         ],
                                         pager: "#pitems",
                                         footerrow:true,
                                         viewrecords: true,hidegrid:false,                                                                                 
                                         caption: "Detalle Ventas",
                                         loadComplete: function (data) { 
                                                
                                         }
                                 });
                                 $("#items").jqGrid('setGroupHeaders', {
                                   useColSpanStyle: true, 
                                   groupHeaders:[
                                         {startColumnName: 'Vet_Can', numberOfColumns: 4, titleText: '<em>Precio</em>'}
                                   ]	
                                 });
                                 
                             });
                             function saveForm(){
                                var data={'saveForm':true, 'Ventas_ND': $("#ventas_cambio").val()};
                                
                                $.saveDataJson('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',data, function(response){   
                                        $.alert('El Registro se Guardo Con Exito!');
                                        $('#btnGuardar').attr('disabled','disabled');
                                        return false;
                                    },function(r) {
                                        $.alert('No se logro guardar el Registro!. '+r['message']);
                                        return false;
                                    });      
                             }
                        </script>
                    <!-- FIN DEL DIALOGO CLIENTE-->
                    <?php } ?>
                    <?php if(!isset($ND_Ventas)){ ?>

                    <?php 
                        /**
                        * Evalua si el usuario es un vendedor 
                        */
                        if (count($row_rs_vendedor) > 0)
                        {                                
                           
                                               
                    ?>
                    
                    <form  id="formCompTemp" action="javascript:$('#list').Search('#formCompTemp','ajaxND_Ventas')" class="form-horizontal normal">
                           <div class="col-xs-6">
                                
                                    <fieldset class="exa-fieldset cliente">
                                        <!-- Form Name -->
                                        <legend class="Titulos2">Seleccione Cliente</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs" for="cliente">Cédula/R.U.C:</label>  
                                            <div class="col-xs-6">
                                                  <div class="input-group input-group-xs">                                                
                                                      <input type="text" id="Cli_Cod" name="Cli_Cod" data-cliente='Cli_Cod' value="" style="display: none" />
                                                      <span id="cedula" data-cliente='Prs_Ced' class="form-control">Seleccione Cliente..</span>
                                                      <span class="input-group-btn">
                                                          <button class="btn btn-success" onclick="$('#cliDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Clientes"></span></button>
                                                      </span>
                                                  </div><!-- /input-group -->                                          

                                            </div>
                                            <div class="col-md-1"><a onclick="setCliente({});" title="Quitar Proveedor" class="btn btn-success btn-xs pull-right"><i class="glyphicon glyphicon-new-window"></i></a></div> 
                                          </div>
                                          <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs" for="cliente">Cliente:</label>  
                                            <div class="col-xs-10"><span id="cliente" data-cliente='cliente' class="form-control input-xs"></span></div>
                                          </div>
                                          <div class="form-group">  
                                            <label class="col-xs-2 control-label label-xs" for="direccion">Dirección:</label>  
                                            <div class="col-xs-10"><span id="direccion" data-cliente='Prs_Dir' class="form-control input-xs"></span></div>                                 
                                          </div>                                
                                    </fieldset>
                                
                            </div>
                            <div class="col-xs-6">
                                             
                                    <fieldset class="exa-fieldset">
                                        <!-- Form Name -->
                                        <legend class="Titulos2">Filtros</legend>
                                         <div class="col-sm-9">                                        
                                            
                                            <!-- Select Basic -->
                                            <div class="form-group">
                                              <label class="col-xs-2 control-label label-xs " for="Tic_Cod">Docum.:</label>
                                              
                                                <div class="col-xs-6">
                                                    <select name="Tic_Cod" id="Tic_Cod" class="form-control input-xs" required onchange="this.form.submit()">
                                                      <?Php
                                                      foreach($row_tipo_compr as $row)
                                                      { if($row['Tic_Sri']=='0'){ $Tic_Cod=$row['Tic_Cod'];?>
                                                      <option  <?Php if ($Tic_Cod == $row['Tic_Cod']){ echo "selected"; } ?> value="<?Php echo $row['Tic_Cod']; ?>"><?Php echo $row['Tic_Des']; ?></option>
                                                      <?Php
                                                      }}
                                                      ?>
                                                    </select>
                                              </div> 
                                              
                                            </div>
                                            <div class="form-group">  
                                                <label class="col-xs-2 control-label label-xs " for="Fec_Ini">Desde:</label>  
                                                <div class="col-xs-4">                                    
                                                    <input name="Fec_Ini" id="Fec_Ini" type="text" class="form-control input-xs"/>
                                                </div>                                 
                                                <label class="col-xs-2 control-label label-xs" for="Fec_Fin">Hasta:</label>  
                                                <div class="col-xs-4">                                    
                                                    <input name="Fec_Fin" id="Fec_Fin" type="text" class="form-control input-xs"/>
                                                </div>                                 
                                              </div>
                                             </div>
                                              <div class="col-md-3" style="padding-top: 10px;">
                                                  <div class=""><button type="button"  onclick="this.form.submit()" class="btn btn-sm btn-success" title="Ejecutar Búsqueda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
                                              </div>
                                    </fieldset>
                                    <fieldset class="exa-fieldset">
                                        <!-- Form Name -->
                                        <legend class="Titulos2">Limitar Seleccion</legend>
                                        <div class="form-group">  
                                            <label class="col-xs-2 control-label label-xs " for="Fec_Ini"><input type="checkbox" class="check-big" onchange="$('.max').attr('disabled',!$(this).is(':checked'));" />&nbsp;&nbsp;&nbsp;Maximo:</label>  
                                            <div class="col-xs-4">                                    
                                                <input name="Max" id="Max" type="text" class="form-control input-xs max" value="" disabled=""/>
                                            </div>                                 
                                            <div class="col-xs-4">                                    
                                                <button type="button"  onclick="select()" class="btn btn-xs btn-primary max" title="Ejecutar Selección" disabled=""><span class="glyphicon glyphicon-check"></span> &nbsp;Seleccionar</button>
                                            </div>                                 
                                        </div>
                                    </fieldset>    
                            </div>    
                        </form>
                    
                   
                    <div class="col-sm-12">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Listado de Notas de Venta</legend> <!-- Form Name -->
                           <table id="list"></table>
                           <div id="listPager"></div>
                        </fieldset>
                        <div style="" class="">                            
                            <button type="button" class="btn btn-sm btn-primary start" onclick="send();/*if($('#list').jqGrid('getCol', 'Pago', false, 'sum')===0){$.alert('El valor del Pago es InvÃ¡lido');}else{SelectFact();}*/" title="Gestionar Notas de Venta"> <span class="glyphicon glyphicon-floppy-open"></span>&nbsp; <span>Facturar Notas de Venta</span></button>
                            <form id="formNDVentas" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
                                <input id='ND_Ventas' name='ND_Ventas' value='' style="display: none" >
                            </form>
                            <script>
                                   function send(){
                                       var nd=new Array();
                                       var grid=$('#list'),rows= grid.jqGrid('getRowData');
                                        for(var i=0;i<rows.length;i++){                                
                                            if(rows[i].act==="Yes") 
                                            {nd.push(rows[i]['Vet_Cod']);}
                                        }                                         
                                       $('#ND_Ventas').val(nd.join(','));
                                       if($('#ND_Ventas').val()!=='')
                                        $('#formNDVentas')[0].submit();
                                       else
                                         $.alert('Debe seleccionar al menos una Nota de Venta!');  
                                   }
                                   function select(){
                                       var list=$('#list'), ids=list.jqGrid('getDataIDs'), sum=0, max=$.round($('#Max').val());
                                       list.find('tr#'+ids[i]+'  td[aria-describedby^="list_act"] input[type="checkbox"]').prop('checked',false);
                                       for(var i=0,z=ids.length;i<z;i++){
                                            var dat=list.jqGrid('getRowData',ids[i]), val=($.numUnformat(dat['Total']));
                                            if((sum+val)>=max) break;
                                            sum+=val;
                                            list.find('tr#'+ids[i]+'  td[aria-describedby^="list_act"] input[type="checkbox"]').prop('checked',true);
                                       }
                                       list.jqGrid('footerData','set',{Iva:'<div style="text-align:right">TOTAL:</div>',Total:$.numFormat(sum)},false);
                                   }
                            </script>
                        </div>
                    </div>
                    <script>
                    var compGrid;
                    $(document).ready(function () {                         
                        compGrid=$("#list").createGrid({    
                            postData:$('#formCompTemp').getData('ajaxND_Ventas'),
                            colModel: [                               
                                { label: 'Cód.Int.', name: 'Vet_Cod', key: true, hidden:true,viewable:true }, 
                                { label: 'Fecha', name: 'Caj_Fec',align:"center", width: 40  },  
                                { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 55, align:"center", classes:'bgNoRight'},
                                { label: 'Cliente', name: 'cliente', width: 100, classes:'bgNoRight'},
                                { label: 'Observación', name: 'Vet_Obs', width: 80, classes:'bgNoRight'},
                                { label: 'Pago', name: 'Vet_Pag', width: 80,hidden:true, classes:'bgNoRight'},
                                { label: 'Valor', name: 'Vet_Tot', width: 40, align: 'right', decimalPlaces: '2', summaryRound: 2,formatter:"currency",
                                        formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" , classes:'bgNoRight'
                                },
                                { label: 'Descto.', name: 'Descuento', width: 30, align: 'right', decimalPlaces: '2', summaryRound: 2,formatter:"currency",
                                        formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" , classes:'bgNoRight'
                                }, 
                                { label: 'SubTotal', name: 'SubTotal', width: 45, align: 'right',  decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: { thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" , classes:'bgNoRight',
                                        formatter: function (cellValue, options, rowObject) { return $.fn.fmatter.call(this, "number",(rowObject.Vet_Tot-rowObject.Descuento), options);}
                                }, 
                                { label: 'IVA', name: 'Iva', width: 35, align: 'right',  decimalPlaces: '2', summaryRound: 2,formatter:"currency",
                                        formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" 
                                },                                 
                                { classes:'columnHighlight2',label: 'Total', name: 'Total', width: 50, align: 'right',  decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" ,
                                        formatter: function (cellValue, options, rowObject) { return $.fn.fmatter.call(this, "currency",(rowObject.Vet_Pag*1+rowObject.Iva*1), options);}
                                }, 
                                { label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act', width: 15, align: 'center',viewable: false, formatter: 'checkbox', classes:'bgNoRight', formatoptions: { disabled: false },resizable:false },
                                {classes:'columnHighlight1', label: 'No. Docum.', name: 'Fac_Num', width: 70, align:"center"}                                 
                            ],     
                            height: 270, footerrow: true, userDataOnFooter: false, selectGridRows:false, 
                            loadComplete: function (data) { 
                                var grid=$(this), iCol = grid.getColumnIndexByName('act'), rows = this.rows, i, c = rows.length; 
                                updateTotals(grid);
                                for (i = 0; i < c; i += 1) {                                    
                                    $(rows[i].cells[iCol]).click(function (e) {                                        
                                        updateTotals(grid);    
                                    });
                                }
                            },
                            subGrid: true,multiselect: false,
                            subGridOptions: { "plusicon"  : "ui-icon-triangle-1-e","minusicon" : "ui-icon-triangle-1-s","openicon"  : "ui-icon-arrowreturn-1-e","reloadOnExpand" : false,"selectOnExpand" : true },
                            subGridRowExpanded: function(subgrid_id, row_id) {
                                var subgrid_table_id = subgrid_id+"_t";         
                                $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                                $("#"+subgrid_table_id).jqGrid({
                                        url:"<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>?ajaxSubgrid="+row_id,datatype: "json",regional : 'es',
                                        autowidth : true, shrinkToFit: true,cmTemplate: {sortable:false},//colNames: ['No','Item','Qty','Unit','Line Total'],
                                        colModel: [
                                                {label:'Cod.Int.',name:"Cpp_Cod",width:80,key:true,align:"center",hidden:true},
                                                {label:'Cod.Int.',name:"Com_Cod",width:80,key:true,align:"center",hidden:true},
                                                {label:'No. Compr.',name:"Com_Codigo",width:45,align:"center"},
                                                {label:'Fecha',name:"Pag_Fec",width:45,align:"center"},
                                                {label:'Valor',name:"Pag_Val",width:45, align: 'right', formatter:'currency', decimalPlaces: '2', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}},
                                                {label:'Observación',name:"Pag_Obs",width:100},
                                                {label:'Tipo',name:"Pag_Des",width:50,align:"center"},                      
                                                    { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                                        formatter:function (cellvalue, options, rowObject) { 
                                                            var clic='selectDetalle('+rowObject.Cpp_Cod+','+rowObject.Com_Cod+');';
                                                            return  '<span class="btn btn-info btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-info-sign"></span>'; 
                                                        }
                                                    }
                                        ],beforeSelectRow: function(rowid, e) {return false;},
                                        rowNum:10000000, pager: "",height: '100%'
                                });                                
                            }
                        },false,"#listPager")
                        .gridButtonsAdd([
                            { caption:"Marcar Todo&nbsp;", buttonicon:"ui-icon-bullet", onClickButton:function(){compGrid.selectAllByComlumn('act',true);updateTotals(compGrid);}, position: "last", title:"", cursor: "pointer"},
                            { caption:"Desmarcar Todo&nbsp;", buttonicon:"ui-icon-radio-off", onClickButton:function(){compGrid.selectAllByComlumn('act',false);updateTotals(compGrid);}, position: "last", title:"", cursor: "pointer"}
                        ]);    
                        $('#ND_Ventas').val('');
                        $.createDateRange('#Fec_Ini','#Fec_Fin');
                        
                    });
                    function updateTotals(grid){                    
                        var sum=0, sel=grid.find('tr td[aria-describedby^="list_act"] input[type="checkbox"]:checked');                        
                        if(sel.length>0)
                        sel.each(function(){
                            var id=$(this).parent().parent().attr('id'), v=grid.jqGrid('getRowData',id);
                            sum+=($.numUnformat(v['Total'])); 
                        });
                        grid.jqGrid('footerData','set',{Iva:'<div style="text-align:right">TOTAL:</div>',Total:$.numFormat(sum)},false);
                    }
                    </script>
                   <?php    
                        }else
                        {
                                echo error_alerta (" Ud. no es un Vendedor autorizado para emitir Facturas o Notas de Ventas", 2);
                        }//Fin de else del if ($total_rs_vendedor > 0) ?>
                    <?php } ?>
                </div>    
              
            
        </div>
    </div>
    <!--INICIO DEL DIALOGO BUSCAR CLIENTE--> 
    <div id="cliDialog" title="Búsqueda de Clientes"></div>
    <div id="cliFactDialog" title="Búsqueda de Clientes"></div>
    <script type="text/javascript">
        $(document).ready(function() {
            let model=[
                { label: 'Cód.Int.', name: 'Cli_Cod', key: true,hidden:true,viewable: true },                
                { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                { label: 'Cliente', name: 'cliente', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                { label: 'Dirección', name: 'Prs_Dir',hidden:true,viewable: true },                      
                { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,formatter:'gridButton',formatoptions:{action:'selectCliente',data:'Cli_Cod'}},
                { label: 'Cód.Int.', name: 'Prs_Cod', hidden:true,viewable: true }
            ];
            $('#cliDialog').createSearchDialog({colModel:model},{title:'Cliente'});
            model[4]['formatoptions']['action']='selectClienteFact';
            $('#cliFactDialog').createSearchDialog({colModel:model},{title:'Cliente'});
        }); 
        function selectCliente(Cli_Cod){           
            setCliente($("#cliGrid").jqGrid("getRowData",Cli_Cod));
            $('#cliDialog').dialog('close');            
        }
        function selectClienteFact(Cli_Cod){     
            $('.clienteFact').setData($("#cliFactGrid").jqGrid("getRowData",Cli_Cod),true,'cliente');
            $('#cliFactDialog').dialog('close');            
        }
        function setCliente(data){
            $('.cliente').setData(data,true,'cliente');                
            $('#list').Search('#formCompTemp','ajaxND_Ventas');                     
        }
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</BODY>
</HTML>