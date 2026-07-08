<?php
require_once('../../Librerias/config.php/register_globals.php'); 
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

if(isset($uploadXML)){
    $responce['success']=false;
    $responce['message']="No se ha encontrado ningun archivo!";
    $tot = count($_FILES["archivoXML"]["name"]);     
    //este for recorre el arreglo 
    try {
        if($tot>0){
            $rows=array();
            $z=0;
            for ($i = 0; $i < $tot; $i++)
            {     
                $explode_name = explode('.',$_FILES['archivoXML']['name'][$i]);
                if($explode_name[1] == 'xml'||$explode_name[1] == 'XML'){
                    $sri = simplexml_load_file($_FILES["archivoXML"]["tmp_name"][$i]);
                    $responce['empresa']='<b>COMPRAS&raquo;</b>&nbsp; '.$sri->IdInformante.' - '.$sri->razonSocial;
                    $totCom=count($sri->compras[0]->detalleCompras);									
                    $datos = $sri->compras;
                    $anio=''.$sri->Anio;$mes=''.$sri->Mes;


                    for($x=0;$x<$totCom;$x++)	
                    {   $z=$z+1;
                        if($datos->detalleCompras[$x]->tipoComprobante=="01") $tipo= "FACTURA";
                        else if($datos->detalleCompras[$x]->tipoComprobante=="02") $tipo= "NOTA DE VENTA";
                            else if($datos->detalleCompras[$x]->tipoComprobante=="03") $tipo= "LIQUIDACION DE COMPRA";
                                else if($datos->detalleCompras[$x]->tipoComprobante=="04") $tipo= "NOTA DE CR&Eacute;DITO";
                                    else if($datos->detalleCompras[$x]->tipoComprobante=="05") $tipo= "NOTA DE D&Eacute;BITO"; else $tipo='';
                        $total=(''.$datos->detalleCompras[$x]->baseImponible) + (''.$datos->detalleCompras[$x]->baseImpGrav) + (''.$datos->detalleCompras[$x]->montoIva) + (''.$datos->detalleCompras[$x]->montoIce);
                        //die(var_dump($total));
                        $fila=array(
                            'id'        =>$z,//'anio'=>$anio,'mes'=>$mes,
                            'fecha'     =>''.$datos->detalleCompras[$x]->fechaEmision,
                            'proveedor' =>''.$datos->detalleCompras[$x]->idProv,
                            'tipo'      =>$tipo,
                            'documento' =>$datos->detalleCompras[$x]->establecimiento."-".$datos->detalleCompras[$x]->puntoEmision."-".str_pad($datos->detalleCompras[$x]->secuencial,9,"0",STR_PAD_LEFT),
                            'autorizacion'=>''.$datos->detalleCompras[$x]->autorizacion,
                            'importe'   =>$total
                        );
                        array_push($rows,$fila);
                    }               
                    $responce['success']=true;
                }else{$responce['success']=false;$responce['message']="La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)";break;}    
            } 
        }
    } catch (Exception $e) {$responce['success']=false;$responce['message']= 'ERROR: '.$e->getMessage();}    
    if($responce['success']){
        $responce['grid']['rows']=$rows;$responce['grid']['records']=count($rows);$responce['grid']['total']=$responce['grid']['records'];
        utf8_encode_deep($responce['grid']['rows']);         
    }
    echo json_encode($responce);exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?> 
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <script language="JavaScript">
		
		</script>
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
	  <td height="10">&raquo; Examinar ATS externos</td>
</tr>
<tr>
 <td align="left" valign="top" height="400">
    <form method="post" name="form3" id="form3" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'];?> ">
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Facturas pendientes de enviar</label>
    </LEGEND>
        
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
     <tr>	
       <td width="87" align="right" class="LetraNegra">Seleccione:</td> 
       <td width="345">&nbsp;<input type="file" multiple name="archivoXML[]" id="archivoXML[]" value="" accept="text/xml" /></td>
       <td> 
         <button type="button" class="btn btn-primary start" onclick="loadXML();"><i class=" icon-ok-sign icon-white"></i> <span>Cargar</span> </button>
         <span style="display: inline-block;width: 50px;">&nbsp;</span>
             Agrupar Por <select id="chngroup">
               <option value="clear">No Agrupar</option>
               <option value="proveedor">Proveedor</option>
               <option value="fecha">Fecha</option>
            </select>
         </td>
     </tr>   
     </table>
       
    </FIELDSET> </form>   
    <FIELDSET>
        <LEGEND>
            <label class="Titulos2">Resultados</label>
        </LEGEND>
       <div> 
            <table id="list"></table>
            <div id="listPager"></div>
        </div> 
         <div style="padding:5px;">
                  <button onclick="$('#list').jqGrid('printGrid',{nombre:'Reporte de ATS',bodyBorder:false});" title="Imprimir Reporte" type="button" class="btn btn-primary start" > <i class="icon-print icon-white"></i> <span>Imprimir</span></button>
                  <button onclick="$('#list').jqGrid('exportGridExcel',{nombre:'Reporte ATS',hoja:'Hoja ATS',caption:true,generated:false});" title="Exportar Excel" class="btn btn-primary start" > <i class="icon-share icon-white" ></i> <span>Excel</span></button>               
                <!--<button type="button" class="btn btn-primary start" onclick="exportarExcel('Exportar')"> <i class="icon-share icon-white"></i> <span>Excel</span></button>-->              
              </div>
    </FIELDSET>    
    </td>
</tr>
</table>
     <script>
    function loadXML(){	      
       
                            var formData = new FormData(document.getElementById("form3"));
                            formData.append("uploadXML", true);
                            //formData.append(f.attr("name"), $(this)[0].files[0]);
                            $.ajax({
                                url: "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",
                                type: "post", dataType: "json", data: formData, cache: false, contentType: false, processData: false
                            }).done(function(response){
                                if(response['success']===true){
                                    $("#list").jqGrid("clearGridData");  
                                    $("#list").jqGrid("setCaption",response['empresa']);
                                    $("#list").jqGrid('setGridParam',{rowNum:response['grid']['records']});
                                    $("#list").jqGrid('setGridParam', {data:response['grid']['rows'],page:1,records:response['grid']['records'],total:response['grid']['total'] }).trigger('reloadGrid');
                                    $("#form3").effect("highlight",{},500);
                                }else{$("#list").jqGrid("clearGridData");$.alert(response['message']);}                                  
                            }).fail(function(error) { $.alert("El Servidor ha fallado en responder! "); });                              
                    }   
    $(document).ready(function () {
                    var jgrid=$("#list");
                        $("#chngroup").change(function(){
                            var vl = $(this).val();
                            if(vl) {
                                    if(vl === "clear") {jgrid.jqGrid('groupingRemove',true);} 
                                    else {jgrid.jqGrid('groupingGroupBy',vl);}
                            }
                        });
                        jgrid.jqGrid({
                            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                            autowidth : true, shrinkToFit: true, height: 350,caption:'&nbsp;',
                            colModel: [
                                { label: 'Cód.Int.', name: 'id', key: true, width: 55,align:"center",hidden:true },
                                { label: 'Fecha', width: 45,name: 'fecha',align:"center", sorttype:"date"},
                                { label: 'Proveedor', name: 'proveedor', width: 50,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },   
                                { label: 'Tipo Doc.', name: 'tipo', width: 50},  
                                { label: 'Documento', name: 'documento', width: 75},                                                                  
                                { label: 'Autorizaci&oacute;n', name: 'autorizacion', width: 125,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} }, 
                                { label: 'Importe', name: 'importe', width: 50, align: 'right', formatter:'currency',
                                        formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'      // set the formula to calculate the summary type                                        
                                }
                            ],                                                     
                            rowNum: 100000000,pager: "#listPager", gridview: true, rownumbers: false, viewrecords: true, altRows: true, altclass: "myAltRowClass",pginput : false,pgbuttons: false,  pgtext: "Mostrando {0} Documentos.",                          
                            groupingView: {
                                groupField: ["proveedor"],groupColumnShow: [true],
                                groupText: ["<div><span style='float:left;'> {1} Documento(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  <b style='position: absolute;right: 25px;'>Total: $ {importe} <b></div>"],
                                groupOrder: ["asc"],groupSummary: [true],groupCollapse: false
                            },grouping: false
                            
                        });                        
                        jgrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
                        jgrid.jqGrid('bindKeys'); 
                    });  
               </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>
