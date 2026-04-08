<?
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
			ini_set("memory_limit" , "32M") ;	
            for ($i = 0; $i < $tot; $i++)
            {    $z++; 
                $explode_name = explode('.',$_FILES['archivoXML']['name'][$i]);
                if($explode_name[1] == 'xml'||$explode_name[1] == 'XML')
				{
                    $sri = simplexml_load_file($_FILES["archivoXML"]["tmp_name"][$i]);
                    $responce['empresa']='<b>EMPRESA(S)&raquo;</b>&nbsp; '.$sri->infoTributaria->razonSocial;					                    								
                    $CodComp="";
					if ($sri->estado=="AUTORIZADO") // acepta xml autorizados por el sri
					{					
						$datos = $sri->comprobante;
						$xml = simplexml_load_string($datos);
						$clave_acceso = $xml->infoTributaria->claveAcceso;
						$ruc = $xml->infoTributaria->ruc;
						$CodComp= substr ($xml->infoTributaria->claveAcceso,8,2);
						$fecha=$xml->infoFactura->fechaEmision.$xml->infoCompRetencion->fechaEmision.$xml->infoNotaCredito->fechaEmision;
						$numero=$xml->infoTributaria->codDoc.'-'.$xml->infoTributaria->ptoEmi.'-'.$xml->infoTributaria->secuencial;
						$numero2=$xml->impuestos->impuesto[0]->numDocSustento;
					}else{
						$clave_acceso = $sri->infoTributaria->claveAcceso;
						$CodComp= substr ($sri->infoTributaria->claveAcceso,8,2);
						$ruc = $sri->infoTributaria->ruc;
						$fecha=$sri->infoFactura->fechaEmision.$sri->infoCompRetencion->fechaEmision.$sri->infoNotaCredito->fechaEmision;  
						$numero=$sri->infoTributaria->codDoc.'-'.$sri->infoTributaria->ptoEmi.'-'.$sri->infoTributaria->secuencial;
						$numero2=$sri->impuestos->impuesto[0]->numDocSustento;
					}																
						if($CodComp=="01") $tipo= "FACTURA";
							else if($CodComp=="02") $tipo= "NOTA DE VENTA";
								else if($CodComp=="03") $tipo= "LIQUIDACION DE COMPRA";
									else if($CodComp=="04") $tipo= "NOTA DE CR&Eacute;DITO";
										else if($CodComp=="05") $tipo= "NOTA DE D&Eacute;BITO"; 
											else if($CodComp=="07") $tipo= "RETENCION"; else $tipo='';											

					$fila=array(
						'id'        =>$z,//'anio'=>$anio,'mes'=>$mes,
						'ruc' =>''.$ruc,
						'tipo' =>''.$tipo,
						'numero'     =>''.$numero,     
						'numero2'     =>''.$numero2,                                                        
						'clave' =>''.$clave_acceso,
						'fecha'=>''.$fecha,																		
						);
					array_push($rows,$fila);
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
	  <td height="10">&raquo; Examinar datos comprobantes electronicos</td>
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
                                { label: '#', name: 'id', key: true, width: 10,align:"center"},
                                { label: 'Ruc', name: 'ruc', width: 30,align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},  								
                                { label: 'Comprobante', name: 'tipo', width: 30,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },   
								 
                                { label: 'Numero', width: 30,name: 'numero',align:"center", sorttype:"date"},								
								{ label: 'Aplica', width: 30,name: 'numero2',align:"center", sorttype:"date"},								
                                { label: 'Clave de Acceso', name: 'clave', width: 100,align:"center", cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},   
								{ label: 'Fecha', name: 'fecha', width: 30,align:"center", cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}}   
                            ],                                                     
                            rowNum: 100000000,pager: "#listPager", gridview: true, rownumbers: false, viewrecords: true, altRows: true, altclass: "myAltRowClass",pginput : false,pgbuttons: false,  pgtext: "Mostrando {0} Documentos.",
							footerrow: true, userDataOnFooter: false,							
							loadComplete: function () {                       
                                            jgrid.jqGrid('footerData', 'set', { base12:jgrid.jqGrid('getCol','base12',false,'sum')});
											jgrid.jqGrid('footerData', 'set', { base0:jgrid.jqGrid('getCol','base0',false,'sum')});
											jgrid.jqGrid('footerData', 'set', { renta:jgrid.jqGrid('getCol','renta',false,'sum')});									
                                    }                          
                          /* // groupingView: {
//                                groupField: ["proveedor"],groupColumnShow: [true],
//                                groupText: ["<div><span style='float:left;'> {1} Documento(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  <b style='position: absolute;right: 25px;'>Total: $ {importe} <b></div>"],
//                                groupOrder: ["asc"],groupSummary: [true],groupCollapse: false
//                            },grouping: false*/
                            
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