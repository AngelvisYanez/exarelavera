<?php
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anexo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
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
                    $totCom=count($sri->compras[0]->detalleCompras);									
                    $datos = $sri->compras;
                    $anio=''.$sri->Anio;$mes=''.$sri->Mes;
					$totBase=0;
					$totIva=0;
					
                    for($x=0;$x<$totCom;$x++) {   
						$fecRet='';
						$numRet='';
                        $rs_persona = $obBD_con1->getRowConsulta(875, $datos->detalleCompras[$x]->idProv, $obBD_conexion);				  
                        $total_persona=$rs_persona['Prs_Cod'] > 0? 1 : 0;
						if ($total_persona!=0)
						{ $nomPrv=$rs_persona['Prs_Ape'].' '.$rs_persona['Prs_Nom'];
						}else{$nomPrv='-';}
						if($datos->detalleCompras[$x]->tipoComprobante=="01") $tipo= "FACT.";
                        else if($datos->detalleCompras[$x]->tipoComprobante=="02") $tipo= "N.VENT";
                            else if($datos->detalleCompras[$x]->tipoComprobante=="03") $tipo= "LIQ.COM.";
                                else if($datos->detalleCompras[$x]->tipoComprobante=="04") $tipo= "N.CRED";
                                    else if($datos->detalleCompras[$x]->tipoComprobante=="05") $tipo= "N.DEBI"; else $tipo='';
                        
						$z=$z+1;
						$totBase+=$datos->detalleCompras[$x]->baseImponible;
						$totIva+=$datos->detalleCompras[$x]->montoIva;
						if ($datos->detalleCompras[$x]->fechaEmiRet1!='')
						{	$fecRet=$datos->detalleCompras[$x]->fechaEmiRet1;}
						if ($datos->detalleCompras[$x]->secRetencion1!=0)
						{	$numRet=$datos->detalleCompras[$x]->secRetencion1;}
						//die(var_dump($total));
						//$totalRet=count($sri->compras[0]->detalleCompras->air[0]->detalleAir);
						
						$totalRet=count($datos->detalleCompras[$x]->air[0]->detalleAir);
						$infoRet=$datos->detalleCompras[$x]->air;
						$porRet='';
						$SriRet='';
						$totRet=0;
						for($y=0;$y<$totalRet;$y++) {
							$porRet=$porRet.' '.$infoRet->detalleAir[$y]->porcentajeAir."%";							
							$totRet+=(''.$infoRet->detalleAir[$y]->valRetAir);
							
							if ($infoRet->detalleAir[$y]->codRetAir!='')
							{ $SriRet=$SriRet.' '.$infoRet->detalleAir[$y]->codRetAir;
							}else{ $SriRet=$SriRet.' 332';}							
						}
						
						
						$fila=array(
							'id'        =>$z,//'anio'=>$anio,'mes'=>$mes,
							'ruc' =>''.$datos->detalleCompras[$x]->idProv,
							'proveedor' =>''.$nomPrv,
							'fecha'     =>''.$datos->detalleCompras[$x]->fechaEmision,
							'registro'     =>''.$datos->detalleCompras[$x]->fechaRegistro,
							'tipo'     =>''.$tipo,                                                        
							'documento' =>$datos->detalleCompras[$x]->establecimiento.'-'.$datos->detalleCompras[$x]->puntoEmision.'-'.str_pad($datos->detalleCompras[$x]->secuencial,9,"0",STR_PAD_LEFT),
							'autorizacion'=>''.$datos->detalleCompras[$x]->autorizacion,
							
							'base12'    =>''.$datos->detalleCompras[$x]->baseImpGrav,
							'base0'     =>''.$datos->detalleCompras[$x]->baseImponible,
							'fecret'    =>''.$fecRet,
							'numret'    =>''.$numRet, 							
							'iva'     =>''.$datos->detalleCompras[$x]->montoIva,
							'porrenta'  =>''.$porRet,
							'renta'     =>''.$totRet,							
							'iva10'     =>''.$datos->detalleCompras[$x]->valRetBien10,
							'iva20'     =>''.$datos->detalleCompras[$x]->valRetServ20,
							'iva30'     =>''.$datos->detalleCompras[$x]->valorRetBienes,
							'iva70'     =>''.$datos->detalleCompras[$x]->valorRetServicios,
							'iva100'     =>''.$datos->detalleCompras[$x]->valRetServ100,
							'codsri'    =>''.$SriRet,							
						);
						array_push($rows,$fila);
						
                    }  //fin for($x=0;$x<$totCom;$x++)
                    $responce['success']=true;
                }else{$responce['success']=false;$responce['message']="La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)";break;}    
            } //fin for ($i = 0; $i < $tot; $i++)
			
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
<HTML>
    <HEAD>
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
    <td height="10">&raquo; Examinar Iva ATS</td>
</tr>
<tr>
<td align="left" valign="top" height="400">
    <form method="post" name="form3" id="form3" enctype="multipart/form-data" action="<? echo $_SERVER['PHP_SELF'];?> ">
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
        <td>
            <div class="row" style="margin-top:8px;">
                <label class="col-sm-4 control-label label-xs" for="t_iva">Tiene IVA:</label>
                <!-- <div class="col-sm-6"> -->
                    <select id="t_iva" name="t_iva" class="form-control input-xs" style="width: 130px; text-align: center;">
                        <option value="">Seleccione...</option>
                        <option value="S">-- SI --</option>
                        <option value="N">-- NO --</option>
                    </select>
                <!-- </div> -->
            </div>
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
                        // $("#list").jqGrid("clearGridData");  
                        // $("#list").jqGrid("setCaption",response['empresa']);
                        // $("#list").jqGrid('setGridParam',{rowNum:response['grid']['records']});
                        // $("#list").jqGrid('setGridParam', {data:response['grid']['rows'],page:1,records:response['grid']['records'],total:response['grid']['total'] }).trigger('reloadGrid');

                        originalRows = response['grid']['rows'];  // ⬅ guardamos los datos completos aquí
                        applyIvaFilter();
                        $("#form3").effect("highlight",{},500);

                    }else{$("#list").jqGrid("clearGridData");$.alert(response['message']);}                                  
                }).fail(function(error) { $.alert("El Servidor ha fallado en responder! "); $("#loader").hide();});                              
        }


        function applyIvaFilter() {
            let tIva = $("#t_iva").val();
            let rows = originalRows.slice(); // copia de los datos originales

            if (tIva === 'S') {               // Solo con IVA
                rows = rows.filter(r => Number(r.iva) > 0);
            } else if (tIva === 'N') {       // Solo sin IVA
                rows = rows.filter(r => Number(r.iva) == 0);
            }

            $("#list").jqGrid("clearGridData");
            $("#list").jqGrid('setGridParam', {
                data: rows,
                rowNum: rows.length,
                page: 1,
                records: rows.length,
                total: 1
            }).trigger("reloadGrid");
        }


        $(document).on("change", "#t_iva", function() {
            applyIvaFilter();
        });

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
                        { label: '#', name: 'id', key: true, width: 55,align:"center",hidden:true },
                        { label: 'C.I/R.U.C', name: 'ruc', width: 45,align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},  								
                        { label: 'Proveedor', name: 'proveedor', width: 120,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} }, 
                        { label: 'Fecha', width: 45,name: 'fecha',align:"center", sorttype:"date"},
                        { label: 'Fecha Reg.', width: 45,name: 'registro',align:"center", sorttype:"date"},								
                        { label: 'Tipo', name: 'tipo', width: 50,align:"center", cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                        { label: 'Documento', name: 'documento', width: 50,align:"center", cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},                                                                  
                        { label: 'Aut. Compra', name: 'autorizacion', width: 65,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },								  
                        { label: 'Base Imp. 12%', name: 'base12', width: 60, align: 'right', formatter:'currency',
                                formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'      // set the formula to calculate the summary type                                        
                        },
                        { label: 'Base Imp. 0%', name: 'base0', width: 60, align: 'right', formatter:'currency',
                                formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'      // set the formula to calculate the summary type                                        
                        },
                        { label: 'Iva', name: 'iva', width: 50, align: 'right',formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'     // set the formula to calculate the summary type                                        
                        },
                        { label: 'Fecha Reten.', name: 'fecret', width: 65,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },
                        { label: 'Num. Reten.', name: 'numret', width: 65,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} }, 								
                        { label: '% Reten.', name: 'porrenta', width: 35, align: 'right'     // set the formula to calculate the summary type                                        
                        },
                        { label: 'Ret. Renta', name: 'renta', width: 50, align: 'right',formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'     // set the formula to calculate the summary type                                        
                        },
                        { label: 'Ret. Iva 10%', name: 'iva10', width: 50, align: 'right',formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'     // set the formula to calculate the summary type                                        
                        },
                        { label: 'Ret. Iva 20%', name: 'iva20', width: 50, align: 'right',formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'     // set the formula to calculate the summary type                                        
                        },
                        { label: 'Ret. Iva 30%', name: 'iva30', width: 50, align: 'right',formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'     // set the formula to calculate the summary type                                        
                        },
                        { label: 'Ret. Iva 70%', name: 'iva70', width: 50, align: 'right',formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'     // set the formula to calculate the summary type                                        
                        },
                        { label: 'Ret. Iva 100%', name: 'iva100', width: 50, align: 'right',formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'     // set the formula to calculate the summary type                                        
                        },
                        
                        { label: 'Formul. 103', name: 'codsri', width: 60, align: 'right', summaryTpl: "{0}", summaryRound:'2'   // set the formula to calculate the summary type                                        
                        }
                    ],                                                     
                    rowNum: 100000000,pager: "#listPager", gridview: true, rownumbers: false, viewrecords: true, altRows: true, altclass: "myAltRowClass",pginput : false,pgbuttons: false,  pgtext: "Mostrando {0} Documentos.",
							footerrow: true, userDataOnFooter: false,							
							loadComplete: function () {                       
                                            jgrid.jqGrid('footerData', 'set', { base12:jgrid.jqGrid('getCol','base12',false,'sum')});
											jgrid.jqGrid('footerData', 'set', { base0:jgrid.jqGrid('getCol','base0',false,'sum')});
											jgrid.jqGrid('footerData', 'set', { iva:jgrid.jqGrid('getCol','iva',false,'sum')});											
											jgrid.jqGrid('footerData', 'set', { iva10:jgrid.jqGrid('getCol','iva10',false,'sum')});
											jgrid.jqGrid('footerData', 'set', { iva20:jgrid.jqGrid('getCol','iva20',false,'sum')});
											jgrid.jqGrid('footerData', 'set', { iva30:jgrid.jqGrid('getCol','iva30',false,'sum')});
											jgrid.jqGrid('footerData', 'set', { iva70:jgrid.jqGrid('getCol','iva70',false,'sum')});
											jgrid.jqGrid('footerData', 'set', { iva100:jgrid.jqGrid('getCol','iva100',false,'sum')});											
											jgrid.jqGrid('footerData', 'set', { renta:jgrid.jqGrid('getCol','renta',false,'sum')});									
                                    }                          
                           // groupingView: {
//                                groupField: ["proveedor"],groupColumnShow: [true],
//                                groupText: ["<div><span style='float:left;'> {1} Documento(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  <b style='position: absolute;right: 25px;'>Total: $ {importe} <b></div>"],
//                                groupOrder: ["asc"],groupSummary: [true],groupCollapse: false
//                            },grouping: false
                            
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