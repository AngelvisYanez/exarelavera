<?php
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
	
	$omitirnc=isset($omitirnc)&&$omitirnc=='S';
    $bancarizacion=5000;

    try {
        if($tot>0){
            $z=0;
            $rows=array();  			
            for ($i = 0; $i < $tot; $i++){     
                $explode_name = explode('.',$_FILES['archivoXML']['name'][$i]);
                if(isset($explode_name[1])&&strtoupper($explode_name[1]) == 'XML'){
                    $sri = simplexml_load_file($_FILES["archivoXML"]["tmp_name"][$i]);
                    $responce['empresa']='<b>EMPRESA(S)&raquo;</b>&nbsp; '.$sri->razonSocial.'('.$sri->IdInformante.')  ';
                    $datos = $sri->ventas[0]->detalleVentas;
                    $totCom=count($datos);									
                                
                    $anio=''.$sri->Anio;
                    $mes=''.$sri->Mes;
                    $totales=array(); 	

                   

                    for($x=0;$x<$totCom;$x++){  

                    	$datos[$x]->tipoComprobante = ltrim($datos[$x]->tipoComprobante, "0");
                    	
                    	$base = 0;
	                    $base0 = 0;
	                    $iva = 0;
	                    $total = 0;
	                    $nc=0;

						if($datos[$x]->tipoComprobante!="4"){
							$base=(float)$datos[$x]->baseImpGrav;
							$base0=(float)$datos[$x]->baseImponible;
							$iva=(float)$datos[$x]->montoIva;
							$total=$base + $base0 + $iva;
						}
						else{
							$nc = (float)$datos[$x]->baseImpGrav +(float)$datos[$x]->baseImponible + (float)$datos[$x]->montoIva;
						}

						$retiva=(float)$datos[$x]->valorRetIva;
						$retrenta=(float)$datos[$x]->valorRetRenta;
						

						$rs_persona = $obBD_con1->getRowConsulta(875, $datos[$x]->idCliente, $obBD_conexion);				  
						$total_persona=$rs_persona['Prs_Cod'] > 0? 1 : 0;
						$cliente=($total_persona!=0)?$rs_persona['Prs_Ape'].' '.$rs_persona['Prs_Nom']:'-';

						$tipoCom = $obBD_con1->getRowConsulta(894,array('Tic_Sri' => $datos[$x]->tipoComprobante), $obBD_conexion);
						$tipo = $tipoCom['Tic_Des'];

						$z=$z+1;

						$codigoFormato=$sri->IdInformante.($datos[$x]->tipoComprobante=='01'&&$totales[''.$datos[$x]->idProv]>=$bancarizacion?'_bancarizacion':'').'_'.strtolower($tipo).'_'.intval($datos[$x]->secuencial).'_'.$sri->Anio.'_'.strtolower(mes($sri->Mes,1));
						if ($datos[$x]->autRetencion1!=0){ $autRet=$datos[$x]->autRetencion1; }

						$fila=array(
							'id'  =>$z,
							'ruc' =>''.$datos[$x]->idCliente,								
							'empresa' =>''.$sri->IdInformante,								
							'anio' =>''.$sri->Anio,
							'mes' =>''.$sri->Mes,
							'tpIdProv' =>''.$datos[$x]->tpIdProv,								
							'codigo' =>''.$codigoFormato,
							'tipo' =>''.$tipo,
							'emision'=> ''. $datos[$x]->tipoEmision,								
							'cliente' =>''.$cliente, 
							'nc'=>$nc,
							'base'=>$base,
							'base0'=>$base0,
							'iva'=>$iva,
							'retiva'=>$retiva,
							'retrenta'=> $retrenta,
							'total'=>$total,														
						);

						array_push($rows,$fila);
						$totBaseAll+=$base;
						$totBase0All+=$base0;
						$totIvaAll+=$iva;
						$totAll+=$total;
						$totretiva+=$retiva;
						$totretrenta+=$retrenta;
						$totnc+= $nc;		
                    }

                    $responce['success']=true;
                }
                else{ 
                	$responce['success']=false; $responce['message']="La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)"; break; 
                }    
            } 			
        }
    } 
    catch (Exception $e){ $responce['success']=false; $responce['message']='ERROR: '.$e->getMessage(); }
    if($responce['success']){
        $responce['grid']=array('rows'=>$rows,'records'=>count($rows),'total'=>'1','userData'=>array('nc'=>$totnc,'base'=>$totBaseAll,'base0'=>$totBase0All,'iva'=>$totIvaAll,'retiva'=>$totretiva,'retrenta'=>$totretrenta,'total'=>$totAll));
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
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; EXAMINAR VENTAS ATS</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-xs-8">
                    <FIELDSET class="exa-fieldset">
                        <LEGEND class="Titulos2">Cargar ATS</LEGEND>
                        <form method="post" name="form3" id="form3" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'];?>" class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-sm required">Seleccione:</label>  
                                <div class="col-xs-5" ><input type="file" multiple name="archivoXML[]" id="archivoXML[]" value="" accept="text/xml" class="form-control input-sm" required="" /></div>
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
                    <button onclick="$('#list').jqGrid('exportGridExcel',{nombre:'Reporte_ATS',hoja:'Hoja ATS',caption:true,generated:false,footer:true});" title="Exportar Excel" class="btn btn-sm btn-primary start" > <i class="glyphicon glyphicon-download-alt" ></i> <span>Excel</span></button>               
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
            url: "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",
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
        jgrid=$("#list").createGrid({            
            colModel: [
                { label: 'C�d.Int.', name: 'id', key: true, width: 55,align:"center",hidden:true },

                { label: 'C.I/R.U.C', name: 'ruc', width: 30,align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor'},               
				{ label: 'Empresa',name: 'empresa', width: 30,align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor'},               
				{ label: 'Anio', width: 45, name: 'anio',align:"center",hidden:true, sorttype:"date",classes:'bgNoRight bgNoColor'},               
				{ label: 'Mes', width: 45, name: 'mes',align:"center",hidden:true, sorttype:"date",classes:'bgNoRight bgNoColor'},                             
                { label: 'Tipo', name: 'tipo', width: 45,align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor'}, 
                { label: 'Emision', name: 'emision', width: 15,align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor'}, 

                { label: 'Cliente', name: 'cliente', width: 80,cellattr: function () {return 'style="'+excelFormats.text+'"';},classes:'bgNoRight bgNoColor' },   

                 { label: 'Nota Cre.', name: 'nc', width: 20, align: 'right',formatter:'number', formatoptions: {}, summaryTpl: "{0}", summaryType: sumNotNC,summaryRound:'2', summaryRoundType: 'round'  },     // set the formula to calculate the summary type 

                { label: 'Base 12%.', name: 'base', width: 20, align: 'right',formatter:'number', formatoptions: {}, summaryTpl: "{0}", summaryType: sumNotNC,summaryRound:'2', summaryRoundType: 'round'  },     // set the formula to calculate the summary type 
                { label: 'Base 0%.', name: 'base0', width: 20, align: 'right',formatter:'number', formatoptions: {}, summaryTpl: "{0}", summaryType: sumNotNC,summaryRound:'2', summaryRoundType: 'round'  },     // set the formula to calculate the summary type 
                { label: 'Iva', name: 'iva', width: 20, align: 'right',formatter:'number', formatoptions: {}, summaryTpl: "{0}", summaryType: sumNotNC,summaryRound:'2', summaryRoundType: 'round' },      // set the formula to calculate the summary type
                { label: 'Total', name: 'total', width: 25, align: 'right',formatter:'number', formatoptions: {}, summaryTpl: "{0}", summaryType: sumNotNC,summaryRound:'2', summaryRoundType: 'round' },      // set the formula to calculate the summary type
                { label: 'Ret Iva', name: 'retiva', width: 20, align: 'right',formatter:'number', formatoptions: {}, summaryTpl: "{0}", summaryType: sumNotNC,summaryRound:'2', summaryRoundType: 'round' },      // set the formula to calculate the summary type
                { label: 'Ret Renta', name: 'retrenta', width: 20, align: 'right',formatter:'number', formatoptions: {}, summaryTpl: "{0}", summaryType: sumNotNC,summaryRound:'2', summaryRoundType: 'round' },      // set the formula to calculate the summary type
				{ label: '&nbsp;',  width: 15,name: 'act' ,align:"center", classes:'bgNoRight bgNoColor', formatter:'gridButton',formatoptions:{title:"Quitar",action:'deleteRow',data:'id',type:'danger',icon:'remove'}},
            ],                                                     
            height: 350,caption:'&nbsp;', footerrow: true, userDataOnFooter: true,
            gridview: true, rownumbers: false, viewrecords: true, pginput : false,pgbuttons: false,  pgtext: "Mostrando {0} Documentos."          							
        },true,'#listPager');
    });  
	function deleteRow(id){
		//8console.log(id);
		jgrid.delRowData(id);
		jgrid.setGridSummary(['ivadev','base','iva'],{autret:'<div style="text-align:right">TOTALES:</div>'});
	}
    function sumNotNC(v,n,obj){ return isNaN(v)?0:(obj['tipoComprobante']==='04'?-1*v:v); }

    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>
<?Php
/* Cerrado de las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();