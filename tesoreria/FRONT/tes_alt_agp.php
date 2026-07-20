<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_104.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Anx;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($GastosAjax)){ 
    $data=filter_input_array(INPUT_GET);
    $responce['rows'] =  $obBD_con1->getArrayConsulta(23, $data, $obBD_conexion);
    for($i=0;$i<count($responce['rows']);$i++)
        {$mesFec=  explode('-', $responce['rows'][$i]['Gas_Fec']);
        $responce['rows'][$i]['Mes']=mes($mesFec[1],1);}
    utf8_encode_deep($responce['rows']); 
    echo json_encode($responce);exit();
}
if(isset($provAjax)){ 
   $data=filter_input_array(INPUT_GET);
   $data["Emp_Cod"]=$Ses_Emp_Cod;   
    $contar = $obBD_con1->getRowConsulta(20, $data, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0)
        $responce['rows'] =  $obBD_con1->getArrayConsulta(20, $data, $obBD_conexion);
    utf8_encode_deep($responce['rows']); 
    echo json_encode($responce);exit();
}
if(isset($delete)){ 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(30,filter_input_array(INPUT_POST), $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
   
    if($obBD_con1->Error==0) $responce['success']=true;
    else {$responce['success']=false; $responce['message']=$obBD_con1->MsgError;}    
    echo json_encode($responce);exit();
}
if(isset($save)){   
    $data=filter_input_array(INPUT_POST);
    $responce['message']='El Registro se ha Guardado con Exito!';
    if(!isset($Gas_Cod)||$Gas_Cod==''||$Gas_Cod=='0') $banAux=true; else $banAux=false;
    $tipos=$obBD_con1->getArrayConsulta(21,'', $obBD_conexion);
    $totales=$obBD_con1->getArrayConsulta(32,filter_input_array(INPUT_POST), $obBD_conexion);
    $contar = $obBD_con1->getArrayConsulta(33,$data , $obBD_conexion);  
    
    $maximo=$tipos[count($tipos)-1]['Agp_Max']*1;
    $suma=0;
    
    for($i=0;$i<count($tipos);$i++){
        $tipos[$i]['suma']=0;
        foreach($totales as $tot)
            if($tipos[$i]['Agp_Cod']==$tot['Agp_Cod']){
                $tipos[$i]['suma']=$tot['total'];
                if(!$banAux && $tot['Agp_Cod']==$data['Agp_Cod_Ant']) $tipos[$i]['suma']=$tipos[$i]['suma']-$data['Gas_Val_Ant'];
                $suma=$suma+($tipos[$i]['suma']*1);
                break;
            }
    }
    for($i=0;$i<count($tipos);$i++){
        if($tipos[$i]['Agp_Cod']==$data['Agp_Cod']){
            if(($tipos[$i]['suma']*1 + $data['Gas_Val']*1)>$tipos[$i]['Agp_Max']){
                $data['Gas_Val']=$tipos[$i]['Agp_Max']-$tipos[$i]['suma'];
                break;
            }
        }
    }
    if(($suma+$data['Gas_Val'])>$maximo)
        $data['Gas_Val']=$maximo-$suma;
    
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        if($banAux){
            if(count($contar)==0){
                if(($data['Gas_Val']*1)>0){
                    $obBD_con1->operacionobBD(22,$data, $obBD_conexion);                
                }else{$responce['message']='Los valores del registro exceden o no son correctos!';}    
            }else{$responce['message']='El registro '.$data['Gas_Num'].' ya Existe en el sistema!';}
        }else
            $obBD_con1->operacionobBD(31,$data, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
   
    if($obBD_con1->Error==0) $responce['success']=true;
    else {$responce['success']=false; $responce['message']=$obBD_con1->MsgError;}    
    echo json_encode($responce);exit();
}
if(isset($xml)){     
    $documentos =  $obBD_con1->getArrayConsulta(25, filter_input_array(INPUT_GET), $obBD_conexion);
    
    $xml='<?xml version="1.0" encoding="ISO-8859-1" standalone="yes"?>'
        . '<gastosPersonales>';
    $xml=$xml.'<tipoIdentificacion>R</tipoIdentificacion>'
            . '<identificacion>'.$Emp_Ruc.'</identificacion>'
            . '<nombresApellidos>'.$Emp_Nom.'</nombresApellidos>'
            . '<dirCalle>'.$Dir_Calle.'</dirCalle>'
            . '<dirNumero>'.$Dir_Num.'</dirNumero>'
            . '<dirInterseccion>'.$Dir_Inter.'</dirInterseccion>'
            . '<dirProvincia>'.$Pro_Cod.'</dirProvincia>'
            . '<dirCanton>'.$Ciu_Cod.'</dirCanton>'
            . ($Emp_Tel!=''?'<telefono>'.$Emp_Tel.'</telefono>':'')
            . '<periodoFiscal>'.$Pec_Con.'</periodoFiscal>'
            . '<gastos>';    
    
    foreach ($documentos as $doc){
        $xml=$xml.'<detalleGasto>'
                    . '<rucProveedor>'.$doc['ruc'].'</rucProveedor>'
                    . '<totalComprobantesVenta>'.$doc['docs'].'</totalComprobantesVenta>'
                    . '<totalBaseImponible>'.$doc['total'].'</totalBaseImponible>'
                    . '<tipoGasto>'.$doc['Agp_Sri'].'</tipoGasto>'
                . '</detalleGasto>';
    }
    $xml=$xml.'</gastos>'
            . '</gastosPersonales>';
    $responce['xml']=$xml;
    $responce['name']='AGP-'.$Pec_Con.'-'.$Emp_Ruc.'.xml';
    $responce['success']=true;
    utf8_encode_deep($responce['xml']); 
    echo json_encode($responce);exit();
}
if(isset($loadCiud)){ 
    $responce['html']='<option value="">Seleccione...</option>';
    $ciudades = $obBD_con1->getArrayConsulta(29,$Pro_Cod, $obBD_conexion); 
    foreach($ciudades AS $ciud){
            $responce['html']=$responce['html'].'<option value="'.$ciud['Ciu_Cod'].'" '.($ciud['Ciu_Cod']==$empresa['Ciu_Cod']?'selected':'').'>'.$ciud['Ciu_Des'].'</option>';
    }
    $responce['success']=true;
    utf8_encode_deep($responce['html']); 
    echo json_encode($responce);exit();
}
if(isset($totales)){  
    $tipos=$obBD_con1->getArrayConsulta(21,'', $obBD_conexion);
    $totales=$obBD_con1->getArrayConsulta(32,filter_input_array(INPUT_POST), $obBD_conexion);
    //die(var_dump($totales));
    echo '<thead><tr class="ui-widget-header"><th>Tipo Gasto</th><th>Docs</th><th>Total</th><th>Base</th></tr></thead>';
    $maximo=$tipos[count($tipos)-1]['Agp_Max']*1;
    $suma=0;
    foreach($tipos as $tip){
        $aux=NULL;
        foreach($totales as $tot)
            if($tip['Agp_Cod']==$tot['Agp_Cod']){
                $aux=$tot;$suma=$suma+($tot['total']*1);
                break;
            }
        
        echo '<tr class="'.($aux==NULL?'success':($aux['total']>$tip['Agp_Max']?'danger':(($aux['total']+500)>$tip['Agp_Max']?'warning':'success'))).'">'
        . '<td>'.$tip['Agp_Nom'].'</td>'
        . '<td align="center">'.($aux==NULL?'0':$aux['docs']).'</td>'
        . '<td align="right">'.($aux==NULL?'0.00':formato_numero($aux['total'],2,1)).'</td>'
        . '<td align="right">'.formato_numero($tip['Agp_Max'],2,1).'</td></tr>';
    }
    echo '<tfoot class="ui-widget-header bold"><tr><td colspan="2" align="right">Totales</td><td align="right">'.formato_numero($suma,2,1).'</td><td align="right">'.formato_numero($maximo,2,1).'</td></tr></tfoot>';
    if($suma>$maximo) echo '<script>$.alert("Se ha sobrepasado la Base, Revise los valores!")</script>';
    exit();
}
$row_rs_periodo = $obBD_con1->getRowConsulta(24, $Ses_Emp_Cod, $obBD_conexion);
$empresa = $obBD_con1->getRowConsulta(26, $Ses_Suc_Cod, $obBD_conexion);
?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
                <style>                     
                     .menor{font-size: 12px}
                     .table-condensed>tbody>tr>th, .table-condensed>tfoot>tr>th, .table-condensed>thead>tr>td, .table-condensed>tbody>tr>td {
                        padding-top: 0;padding-bottom: 0;font-size: 12px;
                    }
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gastos Personales</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">
                    <div class="col-sm-5">
                       <div class="row">
                            <div class="col-sm-12">
                                 <fieldset class="exa-fieldset">                           
                                    <legend class="Titulos2">Totales:</legend> <!-- Form Name -->
                                    <div style="border: 1px solid #4297d7;">
                                        <table id="agpTotales" class="table table-condensed table-bordered table-hover" style="margin-bottom: 0"></table>
                                    </div>
                                    </fieldset>
                            </div>
                            
                        </div>
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Registro Gastos Personales:</legend> <!-- Form Name -->
                           <form id="formAGP" class="form-horizontal normal"  action="javascript:if($('#PrvCodBus').val()!==''){$.createDialogConfirm(null,null,saveForm);}else{$.alert('Selecione un Proveedor');} "  > 
                               <input name="Gas_Cod" id="Gas_Cod" type="text" value="0" style="display: none"/>
                               <input type="text" name="Emp_Cod" value="<?php echo $Ses_Emp_Cod; ?>" style="display: none" />
                               <input type="text" id="Agp_Cod_Ant" name="Agp_Cod_Ant" value="0" style="display: none" />
                               <input type="text" id="Asi_Val_Ant" name="Gas_Val_Ant" value="0" style="display: none" />
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-4 control-label label-sm required" for="cod_cuenta">Proveedor:</label>  
                                  <div class="col-sm-8">                                    
                                        <div class="input-group input-group-sm">                                                
                                                <input type="text" name="Prv_Cod" id="PrvCodBus" value="" style="display: none" />  
                                                <input id="docu" name="Provee" type="text" class="form-control" placeholder="Seleccione un Proveedor ..." required readonly />
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success" onclick="$('#provDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                                                </span>
                                              </div><!-- /input-group -->                              
                                  </div>                                  
                                </div>

                                 <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-4 control-label label-sm required" for="Cop_Fec">Fec. Emisi�n:</label>  
                                  <div class="col-sm-4">                                    
                                          <input id="Gas_Fec" name="Gas_Fec" class="form-control input-sm dateType" placeholder="0000-00-00" required>
                                                                      
                                  </div>                                 
                                </div>
                                
                                 <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-4 control-label label-sm required" for="Cop_Num">Num. Doc.:</label>  
                                  <div class="col-sm-6">                                    
                                          <input id="Cop_Num" name="Gas_Num" class="form-control input-sm" placeholder="999-999-999999999" type="text" required="" >                                          
                                                                      
                                  </div>                                 
                                </div>    
                                
                                 <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-4 control-label label-sm required" for="Gas_Val">B.Imponible:</label>  
                                  <div class="col-sm-6">
                                    <div class="input-group input-group-sm">
                                          <span class="input-group-addon bold"> $ </span>
                                          <input id="Asi_Val" name="Gas_Val" class="form-control" placeholder="0.00" type="text" required onkeypress="return validar_decimal(event);" style="text-align: right">
                                          
                                    </div>                                      
                                  </div>                                 
                                </div>
                                 
                                <div class="form-group">
                                  <label class="col-sm-4 control-label label-sm required" for="Agp_Cod">Tipo de Gasto:</label>  
                                  <div class="col-sm-6">
                                      <select name="Agp_Cod" id="Agp_Cod"  class="form-control input-sm" required>
                                          <option value="">Seleccione...</option>
                                          <?Php 
                                            $rs_tipos = $obBD_con1->getArrayConsulta(21, '', $obBD_conexion);
                                            foreach($rs_tipos as $row_rs_tipo){ ?>
                                            <option value="<?php echo $row_rs_tipo['Agp_Cod']?>"><?php echo $row_rs_tipo['Agp_Nom']?></option>
                                            <?php } ?>
                                        </select>
                                  </div>
                                </div>

                                <!-- Textarea -->
                                <div class="form-group">
                                  <label class="col-sm-4 control-label" for="Gas_Obs">Observaci�n:</label>
                                  <div class="col-sm-8">                     
                                    <textarea class="form-control" id="Gas_Obs" name="Gas_Obs"></textarea>
                                  </div>
                                </div>
                               
                                <div class="form-group">                                 
                                  <div class="col-sm-12 center">                     
                                      <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                    <button type="reset" class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>                                
                                  </div>
                                </div>
                            
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                                </div>  
                            </form>        
                       </fieldset>                        
                    </div>
                    <div class="col-sm-7">  
                         
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Listado de Gastos Personales:</legend> <!-- Form Name -->
                           <div class="form-horizontal normal" >
                           <form id="formSearch"  action="javascript:"  > 
                               <input type="text" name="Emp_Cod" value="<?php echo $Ses_Emp_Cod; ?>" style="display: none" />
                               <div class="form-group">
                                  <label class="col-sm-2 control-label label-sm">Desde:</label> 
                                   <div class="col-sm-3">
                                       <input id="Fec_Ini" name="Fec_Ini" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>" class="form-control input-sm" placeholder="0000-00-00" required>
                                   </div>
                                  <label class="col-sm-1 control-label label-sm">Hasta:</label> 
                                   <div class="col-sm-3">
                                       <input id="Fec_Fin" name="Fec_Fin" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>" class="form-control input-sm" placeholder="0000-00-00" required>
                                   </div>
                                  <div class="col-sm-3 center">
                                       <button type="button" onclick="$('#gastosGrid').Search('#formSearch','GastosAjax');loadTotales();"  class="btn btn-success btn-sm"><span class="glyphicon glyphicon-search"></span></button>
                                       <button type="button" onclick="openXml();" class="btn btn-success btn-sm"><span class="glyphicon glyphicon-download-alt"></span></button>
                                   </div>
                               </div>                                 
                           </form>   
                           <div class="form-group" style="padding-top: 15px;">
                                  <label class="col-sm-9 control-label label-sm"></label>  
                                  <div class="col-sm-3">
                                      <select id="chngroup" class="form-control input-xs">
                                        <option value="clear">No Agrupar</option>
                                        <option value="Proveedor">Proveedor</option>
                                        <option value="Agp_Nom" selected>Tipo Gasto</option>
                                         <option value="Mes">Mes</option>
                                     </select>

                                  </div>
                           </div>
                           </div>
                           <table id="gastosGrid"></table> 
                            <div style="padding-top:15px;">
                                <button onclick="$('#gastosGrid').jqGrid('printGrid',{pageTitle:'<?Php echo $Ses_Sys_Nom; ?>',nombre:'Gastos Personales',bodyBorder:false,leaveOpen: true});" title="Imprimir Reporte" type="button" class="btn btn-primary start" > <i class="glyphicon glyphicon-print"></i> <span> Imprimir</span></button>
                                <button onclick="$('#gastosGrid').jqGrid('exportGridExcel',{nombre:'Gastos Personales',hoja:'Documentos'});" title="Descargar archivo de Excel" class="btn btn-primary start" > <i class="glyphicon glyphicon-download-alt" ></i> <span> Excel</span></button>               
                              <!--<button type="button" class="btn btn-primary start" onclick="exportarExcel('Exportar')"> <i class="icon-share icon-white"></i> <span>Excel</span></button>-->              
                            </div>
                        </fieldset>
                        
                        <script>
                        $(document).ready(function () {
                            var jgrid=$('#gastosGrid');
                            jgrid.jqGrid({
                                url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                mtype: "GET", datatype: "json", regional : 'es',responsive:true,
                                autowidth : true, shrinkToFit: true,height:325,postData: $('#formSearch').getData('GastosAjax'),caption:'Documentos Registrados',hidegrid:false,
                                cmTemplate: {sortable:true},colModel: [
                                    { label: 'C�d.Int.', name: 'Gas_Cod', key: true,hidden:true,viewable: false },
                                    { label: 'Prv_Cod', name: 'Prv_Cod', hidden:true,viewable: false },
                                    { label: 'Agp_Cod', name: 'Agp_Cod', hidden:true,viewable: false },
                                    { label: 'Mes', name: 'Mes', width: 40 },
                                    { label: 'Fecha', name: 'Gas_Fec', width: 60,align:"center" },                                    
                                    { label: 'Proveedor', name: 'Proveedor', width: 130 },
                                    { label: 'Doc.Num.', name: 'Gas_Num', width: 90,align:"center" },                        
                                    { label: 'T.Gasto', name: 'Agp_Nom',align:"center" , width: 30 },                                     
                                    { label: 'B.Imponible', name: 'Gas_Val', width: 60,align:"right" , formatter:'currency', decimalPlaces: '2', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round' },
                                    { label: 'Observaci�n', name: 'Gas_Obs', width: 100, hidden:true },
                                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 60, align: 'center',viewable: false,
                                            formatter:function (cellvalue, options, rowObject) { 
                                                var clic='editGasto($("#gastosGrid").jqGrid("getRowData",'+rowObject.Gas_Cod+'))';
                                                return  '<span class="btn btn-primary btn-xs" title="Editar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-pencil"></i></span>&nbsp;'+
                                                        '<span class="btn btn-danger btn-xs" title="Eliminar" onclick=\'$.createDialogConfirm(null,'+rowObject.Gas_Cod+',deleteGasto)\'><i class="glyphicon glyphicon-trash"></i></span>'; 
                                            }
                                        }
                                ],                                                                                       
                                rowNum: 1000000, gridview: true, viewrecords: true,
                                groupingView: {
                                    groupField: ["Agp_Nom"],groupColumnShow: [true],
                                    groupText: ["<div><span style='float:left;'> {1} Doc(s)</span> <b> &nbsp;-&nbsp;<span class='menor'>{0}</span>&nbsp;-&nbsp; </b>  <b style='position: absolute;right: 20px;'><span class='menor'>Total:</span> $ {Gas_Val} <b></div>"],
                                    groupOrder: ["asc"],groupSummary: [true],groupCollapse: false
                                },grouping: true

                            }); 
                            $("#chngroup").change(function(){
                                var vl = $(this).val();
                                if(vl) {if(vl === "clear") {jgrid.jqGrid('groupingRemove',true);} else {jgrid.jqGrid('groupingGroupBy',vl);}}
                            });

                        });
                        </script>
                    </div>
                   
                </div>    
              
              
        </div>
    </div>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="B�squeda de Proveedores">  
      <form class="form-horizontal normal"> 
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-xs-8 radioset" >
                          <input id="rad1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                          <input id="rad2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad2">&nbsp;&nbsp;C�dula/R.U.C.&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                    <div class="col-xs-7" >                 
                      <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese proveedor a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                      </div><!-- /input-group -->                          
                    </div>                    
                </div>
        </fieldset>  
       </form>    
    </div>
    <script type="text/javascript">
        $(document).ready(function() {               
                $.createSearchDialog('#provDialog',[
                        { label: 'C�d.Int.', name: 'Prv_Cod', key: true,hidden:true,viewable: true },                                
                        { label: 'C�dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                        { label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                        { label: 'Direcci�n', name: 'Prs_Dir',hidden:true,viewable: true },                      
                            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                formatter:function (cellvalue, options, rowObject) { 
                                    var clic='selectProvee($("#provGrid").jqGrid("getRowData",'+rowObject.Prv_Cod+'))';
                                    return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                                }
                            }
                    ]);  
                                     
        }); 
    </script>
<!-- FIN DEL DIALOGO PROVEEDOR-->
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script> 
   <script type="text/javascript">
       function loadTotales(){
           $('#agpTotales').load("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{totales:true,Emp_Cod:<?php echo  $Ses_Emp_Cod; ?>,Fec_Ini:$('#Fec_Ini').val(),Fec_Fin:$('#Fec_Fin').val()});
       }  
       function generaXml(){            
            $.get("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",$("#formXml").getData('xml'), function(response){	
                if(response['success']===true){$.downloadFile(response['xml'], response['name']); $('#xmlDialog').dialog('close');}
		else{alert("No se logro generar el Xml!");}
            },'json').fail(function(error) {alert("El Servidor ha fallado en responder!");});
        }
       function openXml(){           
            $('#xmlDialog').dialog('open');
            $("input[name='Fec_Ini']").val($("#Fec_Ini").val());
            $("input[name='Fec_Fin']").val($("#Fec_Fin").val());
            $('#limits').html('Desde el '+$("#Fec_Ini").val()+' hasta el '+$("#Fec_Fin").val());
       }
       function loadCiud(cod){           
                $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{Pro_Cod:cod,loadCiud:true}, function(response){	
                    if(response['success']===true){
                       $('#Ciu_Cod').html(response['html']);
                    }else{$.alert();}
                },'json').fail(function(error) {$.alert();});         
       }
       function deleteGasto(cod){          
                $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{delete:true,Gas_Cod:cod}, function(response){	
                    if(response['success']===true){                        
                        $("#gastosGrid").jqGrid('setGridParam',{page:1}).trigger('reloadGrid');                        
                        $.alert("El Registro se ha Eliminado con Exito!");
                        loadTotales();
                    }else{$.alert("No se logro guardar el Registro!");}
                },'json').fail(function(error) {$.alert();});          
       }
       function saveForm(){
           if($('#Asi_Val').val()*1>0){
               var data=$('#formAGP').getData('save');
               data['Fec_Ini']=$('#Fec_Ini').val();
               data['Fec_Fin']=$('#Fec_Fin').val();
                $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function(response){	
                    if(response['success']===true){
                        $('#formAGP')[0].reset();
                        $("#gastosGrid").jqGrid('setGridParam',{page:1}).trigger('reloadGrid');                        
                        $.alert(response['message']);
                        loadTotales();
                    }else{$.alert("No se logro guardar el Registro!");}
                },'json').fail(function(error) {$.alert();});
           }else{$.alert('El valor del documento debe ser mayor que cero!');}
       }
       function editGasto(data){
            $('#formAGP')[0].reset();
            $('#Gas_Cod').val(data.Gas_Cod);
            $('#PrvCodBus').val(data.Prv_Cod);
            $('#Gas_Fec').val(data.Gas_Fec);
            $('#docu').val(data.Proveedor);
            $('#Cop_Num').val(data.Gas_Num);
            $('#Asi_Val').val(data.Gas_Val);
            $('#Asi_Val_Ant').val(data.Gas_Val);
            $('#Gas_Obs').val(data.Gas_Obs);
            $('#Agp_Cod').val(data.Agp_Cod);
            $('#Agp_Cod_Ant').val(data.Agp_Cod);
       }
       function selectProvee(data){                           
                            if(typeof data==='undefined'){                                
                                $("input[name='Prv_Cod']").val('');
                                $("#docu").val('');                                
                                return false;
                            }else{                            
                                $("#docu").val(data['proveedor']);                             
                                $("input[name='Prv_Cod']").val(data['Prv_Cod']);                                     
                                $("#provDialog").dialog("close");
                            }
                        }
       $( document ).ready(function() {            
            $.createDialog('#xmlDialog',420,600);
            $("#Cop_Num").mask("999-999-999999999",{placeholder:"_"});
            $("#Periodo").mask("9999",{placeholder:"_"});
            $("#Emp_Tel").mask("9999999999",{placeholder:"_"});
            $.createDatePickers('.dateType');            
            $.createDateRange('#Fec_Ini','#Fec_Fin');
            $('#Fec_Ini').datepicker("setDate", '<?php echo $row_rs_periodo['Pec_Fei']; ?>');
            $('#Fec_Fin').datepicker("setDate", '<?php echo $row_rs_periodo['Pec_Fef']; ?>');
            loadTotales();
       });
   </script>
   <div id="xmlDialog" title="Descargar Xml">  
       <form id="formXml"  action="javascript:generaXml();" class="form-horizontal normal"  > 
       <fieldset class="exa-fieldset">                           
        <legend class="Titulos2">Listado de Gastos Personales:</legend> <!-- Form Name -->       
        
            <div style="display: none">
            <input type="text" name="Emp_Cod" value="<?php echo $Ses_Emp_Cod; ?>" />
            <input type="text" name="Fec_Ini"/>
            <input type="text" name="Fec_Fin"/>            
            </div>
            <!-- Text input-->
            <div class="form-group">
              <label class="col-sm-3 control-label label-sm required" for="Emp_Ruc">Identificaci�n:</label>  
              <div class="col-sm-4">                                    
                   <input name="Emp_Ruc" class="form-control input-xs" placeholder="RUC/C�dula" value="<?php echo $empresa['Emp_Ruc']; ?>" type="text" required />
              </div>                                 
            </div> 
            
            <!-- Text input-->
            <div class="form-group">
              <label class="col-sm-3 control-label label-sm required" for="Emp_Nom">R.Social/Nomb&Apell.:</label>  
              <div class="col-sm-9">                                    
                   <input name="Emp_Nom" class="form-control input-xs" placeholder="Nombres y Apellidos" value="<?php echo $empresa['Emp_Nom']; ?>" type="text" required />
              </div>                                 
            </div>
            
            <!-- Text input-->
            <div class="form-group">
              <label class="col-sm-3 control-label label-sm required" for="Dir_Calle">Dir. Calle:</label>  
              <div class="col-sm-9">                                    
                   <input name="Dir_Calle" class="form-control input-xs" placeholder="Calle Principal" value="<?php echo $empresa['Suc_Dir']; ?>" type="text" required />
              </div>                                 
            </div>
            <!-- Text input-->
            <div class="form-group">
              <label class="col-sm-3 control-label label-sm required" for="Dir_Num">Dir. Numero:</label>  
              <div class="col-sm-2">                                    
                   <input name="Dir_Num" class="form-control input-xs" placeholder="No Oficina" value="NA" type="text" required />
              </div>                                 
            </div>
            
            <!-- Text input-->
            <div class="form-group">
              <label class="col-sm-3 control-label label-sm required" for="Dir_Inter">Dir. Intersecci�n:</label>  
              <div class="col-sm-9">                                    
                   <input name="Dir_Inter" class="form-control input-xs" placeholder="Calle Intersecci�n" value="" type="text" required />
              </div>                                 
            </div>
            
             <!-- Text input-->
            <div class="form-group">
              <label class="col-sm-3 control-label label-sm required" for="Pas_Cod">Pa�s:</label>  
              <div class="col-sm-3">                                    
                  <select id="Pas_Cod" name="Pas_Cod" class="form-control input-xs" disabled>
                      <?php $paises = $obBD_con1->getArrayConsulta(27,'', $obBD_conexion); ?>
                      <?php foreach($paises AS $pais){ ?> 
                      <option value="<?php echo $pais['Pas_Cod']; ?>" <?php if($pais['Pas_Cod']===$empresa['Pas_Cod']) echo 'selected'; ?>><?php echo $pais['Pas_Nom']; ?></option>
                      <?php } ?>
                  </select>
              </div>                                 
            </div>
            <!-- Text input-->
            <div class="form-group">
              <label class="col-sm-3 control-label label-sm required" for="Pro_Cod">Provincia:</label>  
              <div class="col-sm-3">                                    
                  <select id="Pro_Cod" name="Pro_Cod" onchange="loadCiud(this.value)" class="form-control input-xs">
                      <option value="">Seleccione...</option>
                      <?php $provincias = $obBD_con1->getArrayConsulta(28,$empresa['Pas_Cod'], $obBD_conexion); ?>
                      <?php foreach($provincias AS $prov){ ?> 
                      <option value="<?php echo $prov['Pro_Sri']; ?>"  <?php if($prov['Pro_Cod']===$empresa['Pro_Cod']) echo 'selected'; ?>><?php echo $prov['Pro_Nom']; ?></option>
                      <?php } ?>
                  </select>
              </div>                                 
            </div>
            
            <!-- Text input-->
            <div class="form-group">
              <label class="col-sm-3 control-label label-sm required" for="Ciu_Cod">Ciudad:</label>  
              <div class="col-sm-3">                                    
                   <select id="Ciu_Cod" name="Ciu_Cod" class="form-control input-xs" >
                      <option value="">Seleccione...</option>
                      <?php $ciudades = $obBD_con1->getArrayConsulta(29,$empresa['Pro_Cod'], $obBD_conexion); ?>
                      <?php foreach($ciudades AS $ciud){ ?> 
                      <option value="<?php echo $ciud['Ciu_Sri']; ?>" <?php if($ciud['Ciu_Cod']===$empresa['Ciu_Cod']) echo 'selected'; ?>><?php echo $ciud['Ciu_Des']; ?></option>
                      <?php } ?>
                  </select>
              </div>                                 
            </div>
            
            <!-- Text input-->
            <div class="form-group">
              <label class="col-sm-3 control-label label-sm" for="Emp_Tel">Tel�fono:</label>  
              <div class="col-sm-3">                                    
                  <input id="Emp_Tel" name="Emp_Tel" class="form-control input-xs" placeholder="9999999999" value="<?php echo ($empresa['Suc_Te1']); ?>" type="text" />
              </div>                                 
            </div>
             
            <!-- Text input-->
            <div class="form-group">
              <label class="col-sm-3 control-label label-sm required" for="Pec_Con">Periodo:</label>  
              <div class="col-sm-2">                                    
                   <input id="Periodo" name="Pec_Con" class="form-control input-xs center" value="<?php echo $row_rs_periodo['Pla_Fec']; ?>" placeholder="0000" type="text" required />
              </div>
              <label id="limits" class="col-sm-7 control-label label-sm" style="text-align: left">Desde el 54654 al 644464</label>  
              
            </div>                    
     </fieldset>
      <!-- Text input-->
       <div class="form-group">             
         <div class="col-sm-12 center">                                    
              <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-download-alt"></span> Descargar XML</button>
         </div>                                 
       </div>   
    </form>
   </div>   
   <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>