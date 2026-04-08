<?
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anexo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/* Creacion del Objeto de conexion  */
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas  */
$obBD_con1 =  new Class_Log_Datos_Anx;


if(isset($uploadXML)){
    $responce['success']=false;
    $responce['message']="No se ha encontrado ningun archivo!";
    $tot = count($_FILES["archivoXML"]["name"]);  
    $omitir02=isset($omitir02)&&$omitir02=='S';
    $bancarizacion=5000;
    //este for recorre el arreglo 
    try {
        if($tot>0){
            $z=0;
            $rows=array();            			
            for ($i = 0; $i < $tot; $i++){     
                $explode_name = explode('.',$_FILES['archivoXML']['name'][$i]);
                if(isset($explode_name[1])&&strtoupper($explode_name[1]) == 'XML'){
                    $sri = simplexml_load_file($_FILES["archivoXML"]["tmp_name"][$i]);
                    $responce['empresa']='<b>EMPRESA(S)&raquo;</b>&nbsp; '.$sri->razonSocial.'('.$sri->IdInformante.')  ';
                    $datos = $sri->compras[0]->detalleCompras;
                    $totCom=count($datos);									
                    
                    $anio=''.$sri->Anio;$mes=''.$sri->Mes;
                    $totBase=0; $totBaseAll=0;
                    $totIva=0; $totIvaAll=0;
                    $totales=array();
                    for($x=0;$x<$totCom;$x++){
                        if($datos[$x]->tipoComprobante=="01"||$datos[$x]->tipoComprobante=="04"){
                            $add=true;
                            $val_monto=($datos[$x]->tipoComprobante=="04"?-1:1)*((''.$datos[$x]->baseImpGrav)*1)+((''.$datos[$x]->montoIva)*1);
                            foreach($totales AS $k => &$v){
                                if($k==''.$datos[$x]->idProv){ $v+=$val_monto; $add=false; break; }
                            } unset($v);
                            if($add) $totales[''.$datos[$x]->idProv]=$val_monto;
                        }
                    }                    
                    for($x=0;$x<$totCom;$x++){  
                        if(!$omitir02||($omitir02&&''.$datos[$x]->codSustento!='02')){
                            $autRet='';
                            $rs_persona = $obBD_con1->getRowConsulta(875, $datos[$x]->idProv, $obBD_conexion);				  
                            $total_persona=$rs_persona['Prs_Cod'] > 0? 1 : 0;
                            $nomPrv=($total_persona!=0)?$rs_persona['Prs_Ape'].' '.$rs_persona['Prs_Nom']:'-';

                            if($datos[$x]->tipoComprobante=="01") $tipo= "FACTURA";
                            else if($datos[$x]->tipoComprobante=="02") $tipo= "NOTA DE VENTA";
                                else if($datos[$x]->tipoComprobante=="03") $tipo= "LIQUIDACI&Oacute;N DE COMPRA";
                                    else if($datos[$x]->tipoComprobante=="04") $tipo= "NOTA DE CR&Eacute;DITO";
                                        else if($datos[$x]->tipoComprobante=="05") $tipo= "NOTA DE D&Eacute;BITO"; else $tipo='';

                            if($datos[$x]->montoIva!=0){
                                $z=$z+1;
                                $totBase+=$datos[$x]->baseImponible;
                                $totIva+=$datos[$x]->montoIva;
                                $codigoFormato=$sri->IdInformante.($datos[$x]->tipoComprobante=='01'&&$totales[''.$datos[$x]->idProv]>=$bancarizacion?'_bancarizacion':'').'_'.strtolower($tipo).'_'.intval($datos[$x]->secuencial).'_'.$sri->Anio.'_'.strtolower(mes($sri->Mes,1));
                                if ($datos[$x]->autRetencion1!=0){ $autRet=$datos[$x]->autRetencion1; }
                                //die(var_dump($total));
                                $fila=array(
                                    'id'        =>$z,//'anio'=>$anio,'mes'=>$mes,
                                    'ruc' =>''.$datos[$x]->idProv,								
                                    'codigo' =>''.$codigoFormato,
                                    'tipo' =>''.$tipo,								
                                    'sustento' =>''.$datos[$x]->codSustento,
                                    'proveedor' =>''.$nomPrv,
                                    'fecha'     =>''.$datos[$x]->fechaEmision,                                                        
                                    'estab'     =>''.$datos[$x]->establecimiento,                                                        
                                    'impre'     =>''.$datos[$x]->puntoEmision,                                                        
                                    'documento' =>str_pad($datos[$x]->secuencial,9,"0",STR_PAD_LEFT),
                                    'autorizacion'=>''.$datos[$x]->autorizacion,
                                    'autret'=>''.$autRet,
                                    'base'=>''.$datos[$x]->baseImpGrav,
                                    'iva'=>''.$datos[$x]->montoIva,							
                                );
                                array_push($rows,$fila);
                                $totBaseAll+=(''.$datos[$x]->baseImpGrav)*($datos[$x]->tipoComprobante=="04"?-1:1);
                                $totIvaAll+=(''.$datos[$x]->montoIva)*($datos[$x]->tipoComprobante=="04"?-1:1);
                            }
                        } //fin omitir sustento 02
                    }  //fin for($x=0;$x<$totCom;$x++)
                    $responce['success']=true;
                }else{ $responce['success']=false; $responce['message']="La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)"; break; }    
            } //fin for ($i = 0; $i < $tot; $i++)			
        }
    } catch (Exception $e){ $responce['success']=false; $responce['message']='ERROR: '.$e->getMessage(); }
    if($responce['success']){
        $responce['grid']=array('rows'=>$rows,'records'=>count($rows),'total'=>'1','userData'=>array('base'=>$totBaseAll,'iva'=>$totIvaAll));
        utf8_encode_deep($responce['grid']['rows']);         
    }
    echo json_encode($responce);exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE>EXA - Software Contable</TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <style></style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Examinar Iva ATS</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-xs-8">
                    <FIELDSET class="exa-fieldset">
                        <LEGEND class="Titulos2">Cargar ATS</LEGEND>
                        <form method="post" name="form3" id="form3" enctype="multipart/form-data" action="<? echo $_SERVER['PHP_SELF'];?>" class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-sm required">Seleccione:</label>  
                                <div class="col-xs-5" ><input type="file" multiple name="archivoXML[]" id="archivoXML[]" value="" accept="text/xml" class="form-control input-sm" required="" /></div>
                                <div class="col-xs-3" ><input type="checkbox" id="omitir02" name="omitir02" value="S" offval="N" class="check-big" ><label class="control-label label-sm">&nbsp;&nbsp;&nbsp;Omitir Sustento 02</label></div>
                                <div class="col-xs-2" ><button type="button" class="btn btn-sm btn-primary start" onclick="loadXML();"><i class="glyphicon glyphicon-upload"></i> <span>Cargar</span> </button></div>
                            </div>
                        </form>
                    </FIELDSET> 
                </div>    
            </div>
            <div class="row">
                <div class="col-xs-12" style="min-height: 350px;">
                    <FIELDSET class="exa-fieldset">
                        <LEGEND class="Titulos2">Resultados</LEGEND>
                        <table id="list"></table>
                        <div id="listPager"></div>
                    </FIELDSET>    
                </div>
                <div class="col-xs-12">
                    <button onclick="$('#list').jqGrid('printGrid',{nombre:'Reporte de ATS',caption:true,bodyBorder:false,footer:true});" title="Imprimir Reporte" type="button" class="btn btn-sm btn-primary start" > <i class="glyphicon glyphicon-print"></i> <span>Imprimir</span></button>
                    <button onclick="$('#list').jqGrid('exportGridExcel',{nombre:'Reporte ATS',hoja:'Hoja ATS',caption:true,generated:false,footer:true});" title="Exportar Excel" class="btn btn-sm btn-primary start" > <i class="glyphicon glyphicon-download-alt" ></i> <span>Excel</span></button>               
                </div>
            </div>    
        </div>
    </div>
    <script>
    var jgrid;
    function loadXML(){
        var formData = new FormData(document.getElementById("form3"));
        formData.append("uploadXML", true);
        $("#loader").show();
        //formData.append(f.attr("name"), $(this)[0].files[0]);
        $.ajax({
            url: "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",
            type: "post", dataType: "json", data: formData, cache: false, contentType: false, processData: false
        }).done(function(response){
            jgrid.jqGrid("clearGridData");
            $("#loader").fadeOut("slow");            
            if(response['success']===true){                
                jgrid.jqGrid("setCaption",response['empresa']);
                jgrid.jqGrid('setGridParam',{rowNum:response['grid']['records']});
                jgrid.jqGrid('setGridParam', {userData:$.extend({autret:'<div style="text-align:right">TOTALES:</div>'},response['grid']['userData']),data:response['grid']['rows'],page:1,records:response['grid']['records'],total:response['grid']['total'] }).trigger('reloadGrid');
                jgrid.effect("highlight",{},500);
            }else{ $.alert(response['message']); }                                  
        }).fail(function(error) { $.alert("El Servidor ha fallado en responder! "); $("#loader").hide();});                              
    }   
    $(document).ready(function () {        
//        $("#chngroup").change(function(){
//            var vl = $(this).val();
//            if(vl) {
//                    if(vl === "clear") {jgrid.jqGrid('groupingRemove',true);} 
//                    else {jgrid.jqGrid('groupingGroupBy',vl);}
//            }
//        });
        jgrid=$("#list").createGrid({            
            colModel: [
                { label: 'Cód.Int.', name: 'id', key: true, width: 55,align:"center",hidden:true },
                { label: 'Código', name: 'codigo', width: 90,align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor'},
                { label: 'C.I/R.U.C', name: 'ruc', width: 50,align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor'},                
                { label: 'Tipo', name: 'tipo', width: 45,align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor'},
                { label: 'Sustento', name: 'sustento', width: 35,align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor'},  								
                { label: 'Proveedor', name: 'proveedor', width: 120,cellattr: function () {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor' },   

                { label: 'Fecha', width: 45,name: 'fecha',align:"center", sorttype:"date",classes:'bgNoRight bgNoColor'},
                { label: 'Estab.', width: 40,name: 'estab',align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';}, classes:'bgNoRight bgNoColor'},
                { label: 'Impre.', width: 40,name: 'impre',align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';}, classes:'bgNoRight bgNoColor'},
                { label: 'Documento', name: 'documento', width: 55,align:"center", cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor'},                                                                  
                { label: 'Aut. Compra', name: 'autorizacion', width: 65,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor' },
                { label: 'Aut. Reten.', name: 'autret', width: 65,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';},classes:'bgNoColor' }, 
                { label: 'Base Imp.', name: 'base', width: 35, align: 'right',formatter:'number', formatoptions: {}, summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'  },     // set the formula to calculate the summary type 
                { label: 'Iva', name: 'iva', width: 35, align: 'right',formatter:'number', formatoptions: {}, summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round' }      // set the formula to calculate the summary type
            ],                                                     
            height: 350,caption:'&nbsp;', footerrow: true, userDataOnFooter: true,
            gridview: true, rownumbers: false, viewrecords: true, pginput : false,pgbuttons: false,  pgtext: "Mostrando {0} Documentos."          							
//          loadComplete: function(){ jgrid.setGridSummary(['base','iva'],{autret:''},true); }                          
//          groupingView: {
//                groupField: ["proveedor"],groupColumnShow: [true],
//                groupText: ["<div><span style='float:left;'> {1} Documento(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  <b style='position: absolute;right: 25px;'>Total: $ {importe} <b></div>"],
//                groupOrder: ["asc"],groupSummary: [true],groupCollapse: false
//          },grouping: false

        },true,'#listPager');
    });  
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>
<?Php
/* Cerrado de las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();