<?
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anexo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/**
* Creacion del Objeto de conexion 
*/
$Ses_Dat_Dis='exa';
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
            for ($i = 0; $i < $tot; $i++)
            {     
                $explode_name = explode('.',$_FILES['archivoXML']['name'][$i]);
                if($explode_name[1] == 'xml'||$explode_name[1] == 'XML')
				{
                  
				   $sri = simplexml_load_file($_FILES["archivoXML"]["tmp_name"][$i]);
                    //$responce['empresa']='<b>EMPRESA(S)&raquo;</b>&nbsp; '.$sri->razonSocial.'('.$sri->IdInformante.')  ';					
                    $totCom=count($sri->compras[0]->detalleCompras);									
                    $datos = $sri->compras;
                    $anio=''.$sri->Anio;$mes=''.$sri->Mes;
					$totBase=0;
					$totIva=0;
										
                    for($x=0;$x<$totCom;$x++)	
                    {   
						$autRet='';
					    $arrRuc=explode(';',$txtRuc);                        
						for($z=0;$z<=count($arrRuc)-1;$z++)
						{ 
							$condicion=$condicion.$datos->detalleCompras[$x]->idProv.'=='.$arrRuc[$z].' || ';
						}
						//echo "-->".$condicion;
						if($datos->detalleCompras[$x]->tipoComprobante=="01") $tipo= "FACTURA";
                        else if($datos->detalleCompras[$x]->tipoComprobante=="02") $tipo= "NOTA DE VENTA";
                            else if($datos->detalleCompras[$x]->tipoComprobante=="03") $tipo= "LIQUIDACION DE COMPRA";
                                else if($datos->detalleCompras[$x]->tipoComprobante=="04") $tipo= "NOTA DE CR&Eacute;DITO";
                                    else if($datos->detalleCompras[$x]->tipoComprobante=="05") $tipo= "NOTA DE D&Eacute;BITO"; else $tipo='';
                        if ($datos->detalleCompras[$x]->idProv==$arrRuc[0] || $datos->detalleCompras[$x]->idProv==$arrRuc[1] || $datos->detalleCompras[$x]->idProv==$arrRuc[2] ||
						    $datos->detalleCompras[$x]->idProv==$arrRuc[3] || $datos->detalleCompras[$x]->idProv==$arrRuc[4] || $datos->detalleCompras[$x]->idProv==$arrRuc[5] ||
							$datos->detalleCompras[$x]->idProv==$arrRuc[6] || $datos->detalleCompras[$x]->idProv==$arrRuc[7] || $datos->detalleCompras[$x]->idProv==$arrRuc[8] ||
							$datos->detalleCompras[$x]->idProv==$arrRuc[9] || $datos->detalleCompras[$x]->idProv==$arrRuc[10] || $datos->detalleCompras[$x]->idProv==$arrRuc[11] ||
							$datos->detalleCompras[$x]->idProv==$arrRuc[12] || $datos->detalleCompras[$x]->idProv==$arrRuc[13] || $datos->detalleCompras[$x]->idProv==$arrRuc[14] ||
							$datos->detalleCompras[$x]->idProv==$arrRuc[15] || $datos->detalleCompras[$x]->idProv==$arrRuc[16] || $datos->detalleCompras[$x]->idProv==$arrRuc[17] ||
							$datos->detalleCompras[$x]->idProv==$arrRuc[18] || $datos->detalleCompras[$x]->idProv==$arrRuc[19] || $datos->detalleCompras[$x]->idProv==$arrRuc[20] ||
							$datos->detalleCompras[$x]->idProv==$arrRuc[21] || $datos->detalleCompras[$x]->idProv==$arrRuc[22] || $datos->detalleCompras[$x]->idProv==$arrRuc[23] || 
							$datos->detalleCompras[$x]->idProv==$arrRuc[24] || $datos->detalleCompras[$x]->idProv==$arrRuc[25] || $datos->detalleCompras[$x]->idProv==$arrRuc[26] || 
							$datos->detalleCompras[$x]->idProv==$arrRuc[27] || $datos->detalleCompras[$x]->idProv==$arrRuc[28] || $datos->detalleCompras[$x]->idProv==$arrRuc[29] || 
							$datos->detalleCompras[$x]->idProv==$arrRuc[30]) 
						{
							 
							$z=$z+1;
							$totBase+=$datos->detalleCompras[$x]->baseImponible;
							$totIva+=$datos->detalleCompras[$x]->montoIva;
							if ($datos->detalleCompras[$x]->autRetencion1!=0)
							{	$autRet=$datos->detalleCompras[$x]->autRetencion1;}
							//die(var_dump($total));
							$row_rs_nomProv=$obBD_con1->getRowConsulta(1,$datos->detalleCompras[$x]->idProv."", $obBD_conexion);
							$total=0;
							$total=floatval($datos->detalleCompras[$x]->baseImponible) + floatval($datos->detalleCompras[$x]->baseImpGrav) + floatval($datos->detalleCompras[$x]->montoIva);
							
							/*suma todos los codigos de retencion(Renta,Iva) de la compra*/
							$datosRet=$datos->detalleCompras[$x]->air;
							$totCodRet=count($datosRet->detalleAir);
							$totRete=0;
							for($xy=0;$xy<$totCodRet;$xy++){
								$totRete+=floatval($datosRet->detalleAir[$xy]->valRetAir);
							}
							$totRete+=floatval($datos->detalleCompras[$x]->valRetBien10 + $datos->detalleCompras[$x]->valRetServ20 + $datos->detalleCompras[$x]->valorRetBienes + $datos->detalleCompras[$x]->valRetServ50 + $datos->detalleCompras[$x]->valorRetServicios + $datos->detalleCompras[$x]->valRetServ100);
							$fila=array(
								'id'        =>$z,//'anio'=>$anio,'mes'=>$mes,								
								'empresa'=>''.$sri->razonSocial,
								'ruc' =>''.$datos->detalleCompras[$x]->idProv,
								'prov' =>''.$row_rs_nomProv['Prs_Ape'].' '.$row_rs_nomProv['Prs_Nom'],
								'fecha'     =>''.$datos->detalleCompras[$x]->fechaEmision,                                                        
								'documento' =>$datos->detalleCompras[$x]->establecimiento.'-'.$datos->detalleCompras[$x]->puntoEmision.'-'.str_pad($datos->detalleCompras[$x]->secuencial,9,"0",STR_PAD_LEFT),
								'autorizacion'=>''.$datos->detalleCompras[$x]->autorizacion,
								'retencion'=>''.$datos->detalleCompras[$x]->estabRetencion1.'-'.$datos->detalleCompras[$x]->ptoEmiRetencion1.'-'.$datos->detalleCompras[$x]->secRetencion1,	
								'valret'=>''.$totRete,
								'sub0'=>''.$datos->detalleCompras[$x]->baseImponible,
								'sub12'=>''.$datos->detalleCompras[$x]->baseImpGrav,
								'iva'=>''.$datos->detalleCompras[$x]->montoIva,							
								'total'=>''.$total
                        	);
                        	array_push($rows,$fila);
						}
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
       <td width="247">&nbsp;<input type="file" multiple name="archivoXML[]" id="archivoXML[]" value="" accept="text/xml" /></td>
       <td width="805"> 
         <button type="button" class="btn btn-primary start" onclick="loadXML();"><i class=" icon-ok-sign icon-white"></i> <span>Cargar</span> </button>
         <span style="display: inline-block;width: 50px;">&nbsp;</span>
         </td>
     </tr>
     <tr>
       <td align="right" class="LetraNegra">Filtrar RUC:</td>
       <td width="247"><input name="txtRuc" type="text" id="txtRuc" value="" size="35" /></td>
       <td>&nbsp;</td>
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
					{ label: '#', name: 'id', key: true, width: 30,align:"center" },
					{ label: 'Empresa', name: 'empresa', width: 100,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },   
					{ label: 'Ruc. Prov.', name: 'ruc', width: 45,align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},  								                                 
					{ label: 'Proveedor', name: 'prov', width: 100,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },   
					{ label: 'Fecha', width: 45,name: 'fecha',align:"center", sorttype:"date"},								
					{ label: 'Documento', name: 'documento', width: 55,align:"center", cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},                                                                  
					{ label: 'Aut. Compra', name: 'autorizacion', width: 65,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },
					{ label: 'Retencion', name: 'retencion', width: 65,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },
					{ label: 'Val. Reten.', name: 'valret', width: 35, align: 'right',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'}, 
					{ label: 'Sub. 0%', name: 'sub0', width: 35, align: 'right',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'}, 
					{ label: 'Sub. 12%', name: 'sub12', width: 35, align: 'right',cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round' }, 
					{ label: 'Iva', name: 'iva', width: 35, align: 'right', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'},
					{ label: 'Total', name: 'total', width: 35, align: 'right',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'}
				],                                                     
				rowNum: 100000000,pager: "#listPager", gridview: true, rownumbers: false, viewrecords: true, altRows: true, altclass: "myAltRowClass",pginput : false,pgbuttons: false,  pgtext: "Mostrando {0} Documentos.",
				footerrow: true, userDataOnFooter: false,							
				loadComplete: function () {                       
					jgrid.jqGrid('footerData', 'set', { valret:jgrid.jqGrid('getCol','valret',false,'sum')});
					jgrid.jqGrid('footerData', 'set', { sub0:jgrid.jqGrid('getCol','sub0',false,'sum')});
					jgrid.jqGrid('footerData', 'set', { sub12:jgrid.jqGrid('getCol','sub12',false,'sum')});
					jgrid.jqGrid('footerData', 'set', { iva:jgrid.jqGrid('getCol','iva',false,'sum')});
					jgrid.jqGrid('footerData', 'set', { total:jgrid.jqGrid('getCol','total',false,'sum')});                     
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