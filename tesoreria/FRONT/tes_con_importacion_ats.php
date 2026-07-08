<?php
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anexo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Anx;


if(isset($uploadXML)){
    $responce['success']=false;
    $responce['message']="No se ha encontrado ningun archivo!";
    $tot = count($_FILES["archivoXML"]["name"]);     
    //este for recorre el arreglo 
    try {
        if($tot>0){
            $rows=array();
            $z=0;			
            for ($i = 0; $i < $tot; $i++) {
                $explode_name = explode('.',$_FILES['archivoXML']['name'][$i]);
                if($explode_name[1] == 'xml'||$explode_name[1] == 'XML') {
                    $sri = simplexml_load_file($_FILES["archivoXML"]["tmp_name"][$i]);
                    $responce['empresa']='<b>EMPRESA(S)&raquo;</b>&nbsp; '.$sri->razonSocial.'('.$sri->IdInformante.')  ';
                    $totExp=count($sri->exportaciones[0]->detalleExportaciones);
                    $datos = $sri->exportaciones;
                    $anio = ''.$sri->Anio;
                  
                    $mes = "".$sri->Mes;


                    $meses = array(
                        '01' => 'Enero',
                        '02' => 'Febrero',
                        '03' => 'Marzo',
                        '04' => 'Abril',
                        '05' => 'Mayo',
                        '06' => 'Junio',
                        '07' => 'Julio',
                        '08' => 'Agosto',
                        '09' => 'Septiembre',
                        '10' => 'Octubre',
                        '11' => 'Noviembre',
                        '12' => 'Diciembre'
                    );
                    $mesTexto =  $meses[$mes];

                    $rucinf = ''.$sri->IdInformante;

					$totFac=0;
					$totFob=0;

                    for($x=0;$x<$totExp;$x++) {
						$auxNum=1;
						$totFac+=$datos->detalleExportaciones[$x]->valorFOBComprobante;
						$totFob+=$datos->detalleExportaciones[$x]->valorFOB;
						if ($datos->detalleExportaciones[$x]->tipoComprobante=='04' || $datos->detalleExportaciones[$x]->tipoComprobante=='4') {
							$auxNum=-1;
						}
						$fila=array(
							// 'ref'        =>''.$datos->detalleExportaciones[$x]->distAduanero.'-'.$datos->detalleExportaciones[$x]->anio.'-'.$datos->detalleExportaciones[$x]->regimen.'-'.$datos->detalleExportaciones[$x]->correlativo,
							// 'trans' =>''.$datos->detalleExportaciones[$x]->docTransp,
                            // 'fechaEmi' =>''.$datos->detalleExportaciones[$x]->fechaEmision,
							// 'fecha' =>''.$datos->detalleExportaciones[$x]->fechaEmbarque,
							// 'serie' =>''.$datos->detalleExportaciones[$x]->establecimiento.'-'.$datos->detalleExportaciones[$x]->puntoEmision,
							// 'num' =>str_pad($datos->detalleExportaciones[$x]->secuencial,9,"0",STR_PAD_LEFT),
							// 'autorizacion'=>''.$datos->detalleExportaciones[$x]->autorizacion,								
							// 'val_fac'=>(''.$datos->detalleExportaciones[$x]->valorFOBComprobante)*$auxNum,
							// 'val_fob'=>(''.$datos->detalleExportaciones[$x]->valorFOB)*$auxNum,
                    
                            //Bloque de acualizacion 2024 visualizacion de columnas
                            'md5' => md5($rucinf.'_'.'DAE'.'_'.$datos->detalleExportaciones[$x]->distAduanero.'-'.$datos->detalleExportaciones[$x]->anio.'-'.$datos->detalleExportaciones[$x]->regimen.'-'.$datos->detalleExportaciones[$x]->correlativo.'_'.$datos->detalleExportaciones[$x]->anio.'_'.$mesTexto),
                            'cod'        =>''.$rucinf.'_'.'DAE'.'_'.$datos->detalleExportaciones[$x]->distAduanero.'-'.$datos->detalleExportaciones[$x]->anio.'-'.$datos->detalleExportaciones[$x]->regimen.'-'.$datos->detalleExportaciones[$x]->correlativo.'_'.$datos->detalleExportaciones[$x]->anio.'_'.$mesTexto,
                            // 'mes' => $rucinf,
                            //'docexport' =>''.$datos->detalleExportaciones[$x]->docTransp,
                            'docexport' =>''.$datos->detalleExportaciones[$x]->distAduanero.'-'.$datos->detalleExportaciones[$x]->anio.'-'.$datos->detalleExportaciones[$x]->regimen.'-'.$datos->detalleExportaciones[$x]->correlativo,
                            'fecha' =>''.$datos->detalleExportaciones[$x]->fechaEmbarque,
                            'serie' =>''.$datos->detalleExportaciones[$x]->establecimiento.'-'.$datos->detalleExportaciones[$x]->puntoEmision,
							'num' =>str_pad($datos->detalleExportaciones[$x]->secuencial,9,"0",STR_PAD_LEFT),
							'autorizacion'=>''.$datos->detalleExportaciones[$x]->autorizacion,		
							'val_fac'=>(''.$datos->detalleExportaciones[$x]->valorFOBComprobante)*$auxNum,
							'val_fob'=>(''.$datos->detalleExportaciones[$x]->valorFOB)*$auxNum,
						);
						array_push($rows,$fila);
						
                    }  //fin for($x=0;$x<$totCom;$x++)
                    $responce['success']=true;
                } else {
                    $responce['success']=false;$responce['message']="La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)";break;}    
            } //fin for ($i = 0; $i < $tot; $i++)
			
        }
    } catch (Exception $e) {
        $responce['success']=false;
        $responce['message']= 'ERROR: '.$e->getMessage();
    }

    if($responce['success']){
        $responce['grid']['rows']=$rows;
        $responce['grid']['records']=count($rows);
        $responce['grid']['total']=$responce['grid']['records'];
        utf8_encode_deep($responce['grid']['rows']);
    }
    echo json_encode($responce);exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<style>.ui-jqgrid .ui-jqgrid-htable th {padding: 0px 2px 0px 2px !important;}#jqgh_list_serie,#jqgh_list_num,#jqgh_list_autorizacion{position: inherit !important;margin: 0 !important; }</style>
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
	  <td height="10">&raquo; Extraer importaciones de ATS</td>
</tr>
<tr>
 <td align="left" valign="top" height="400">
    <form method="post" name="form3" id="form3" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'];?> ">
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Selecci&oacute;n del Xml</label>
    </LEGEND>
        
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
     <tr>	
       <td width="87" align="right" class="LetraNegra">Seleccione:</td> 
       <td width="345">&nbsp;<input type="file" multiple name="archivoXML[]" id="archivoXML[]" value="" accept="text/xml" /></td>
       <td> 
         <button type="button" class="btn btn-primary start" onclick="loadXML();"><i class=" icon-ok-sign icon-white"></i> <span>Cargar</span> </button>
         <span style="display: inline-block;width: 50px;">&nbsp;</span>
         </td>
     </tr>   
     </table>
       
    </FIELDSET> 
    </form>   
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
</div>
     <script>
    function loadXML(){
       
                            var formData = new FormData(document.getElementById("form3"));
                            formData.append("uploadXML", true);
							$("#loader").show();
                            //formData.append(f.attr("name"), $(this)[0].files[0]);
                            $.ajax({
                                url: "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",
                                type: "post", dataType: "json", data: formData, cache: false, contentType: false, processData: false
                            }).done(function(response){
								$("#loader").fadeOut("slow");
                                if(response['success']===true){
                                    $("#list").jqGrid("clearGridData");  
                                    $("#list").jqGrid("setCaption",response['empresa']);
                                    $("#list").jqGrid('setGridParam',{rowNum:response['grid']['records']});
                                    $("#list").jqGrid('setGridParam', {data:response['grid']['rows'],page:1,records:response['grid']['records'],total:response['grid']['total'] }).trigger('reloadGrid');
                                    $("#form3").effect("highlight",{},500);
                                }else{$("#list").jqGrid("clearGridData");$.alert(response['message']);}                                  
                            }).fail(function(error) { $.alert("El Servidor ha fallado en responder! "); $("#loader").hide();});                              
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
                                // { label: 'Referendo', name: 'ref', key: true, width: 25,align:"center" },
                                // { label: 'Núm. Trans.', name: 'trans', width: 20,align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                                // { label: 'Emisión', name: 'fechaEmi', width: 15, align: 'center',cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },							
                                // { label: 'Exportación', name: 'fecha', width: 15, align: 'center',cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },   
								 
                                // { label: 'Serie', width: 20,name: 'serie',align:"center", sorttype:"date"},								
                                // { label: 'Secuencia', name: 'num', width: 20,align:"center", cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},                                                                  
                                // { label: 'Autorización', name: 'autorizacion', width: 65,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },
								
								// { label: 'Valor Factura', name: 'val_fac', width: 20,align: 'right',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}, cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} , formatter:'currency', decimalPlaces: '2', summaryRound: 2}, 
                                // { label: 'Valor FOB', name: 'val_fob',  width: 20,align: 'right',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}, cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} , formatter:'currency', decimalPlaces: '2', summaryRound: 2}

                                //Bloque de acualizacion 2024 visualizacion de columnas
                                { label: 'Código MD5', name: 'md5', key: true, width: 25,align:"center" },
                                { label: 'Código', name: 'cod', key: true, width: 25,align:"center" },
                                { label: 'Documento de Exportación', name: 'docexport', key: true, width: 25,align:"center" }, //# de referendo (referendo pimera linea)
                                { label: 'Fecha de Exportación', name: 'fecha', width: 15, align: 'center',cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },	

                                { label: 'Serie', width: 20,name: 'serie',align:"center", sorttype:"date"},								
                                { label: 'Secuencia', name: 'num', width: 20,align:"center", cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                                { label: 'Autorización', name: 'autorizacion', width: 65, cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },
                                
                                { label: 'Valor Factura', name: 'val_fac', width: 20,align: 'right',formatoptions: {
                                    prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'
                                }, cellattr: function (rowId, val, rawObject, cm, rdata) {
                                    return 'style="'+excelFormats.text+'"';
                                } , formatter:'currency', decimalPlaces: '2', summaryRound: 2},

                                { label: 'Valor FOB', name: 'val_fob',  width: 20,align: 'right',formatoptions: {
                                    prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'
                                }, cellattr: function (rowId, val, rawObject, cm, rdata) {
                                    return 'style="'+excelFormats.text+'"';
                                } , formatter:'currency', decimalPlaces: '2', summaryRound: 2 }

                            ],                                                     
                            rowNum: 100000000,pager: "#listPager", gridview: true, rownumbers: false, viewrecords: true, altRows: true, altclass: "myAltRowClass",pginput : false,pgbuttons: false,  pgtext: "Mostrando {0} Documentos.",
							footerrow: true, userDataOnFooter: false,							
							loadComplete: function () {                       
									jgrid.jqGrid('footerData', 'set', { val_fac:jgrid.jqGrid('getCol','val_fac',false,'sum')});
									jgrid.jqGrid('footerData', 'set', { val_fob:jgrid.jqGrid('getCol','val_fob',false,'sum')});                     
							}                          

                        }); 
						 jgrid.setGroupHeaders({
							useColSpanStyle: true,
							groupHeaders: [
								{ "numberOfColumns": 3, "titleText": "Factura", "startColumnName": "serie" }								
							]
						});                       
                        jgrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
                        jgrid.jqGrid('bindKeys'); 
                    });  
               </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>
<?Php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>