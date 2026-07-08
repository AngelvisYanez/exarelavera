<?php
if(isset($_FILES)&&!empty($_FILES)){
    require_once('../../Librerias/Xml/XML.php');	
    $responce=array('success'=>false,'message'=>"No se ha encontrado ningun archivo!");
    $tot = count($_FILES["archivoXML"]["name"]);
    //este for recorre el arreglo 
    try {
		
        $rows=array();
        if($tot>0&&(!empty($_FILES["archivoXML"]["name"][0]))){            
            for ($i = 0; $i < $tot; $i++){     
                $explode_name = explode('.',$_FILES['archivoXML']['name'][$i]);
                if(strtoupper($explode_name[1]) == 'XML'){
                    $sri = XmlDoc::createFromFile($_FILES["archivoXML"]["tmp_name"][$i]);
                    $sri->setMainComment('Actualizado por Exa (http://www.exa.ofsercont.com) '.date("Y-m-d"),false);
                    $empresa='<b>COMPRAS&raquo;</b>&nbsp; '.$sri->IdInformante.' - '.$sri->razonSocial;
                    if($sri->compras->tot()){
                        $datosCom = $sri->compras[0]->detalleCompras;
                        $totCom=count($datosCom);									
						 
                        $anio=$sri->Anio->text();
                        $mes=$sri->Mes->text();
                        $fecha=$anio.'-'.$mes;

                        /* C O M P R A S */
                        for($x=0;$x<$totCom;$x++){ 
                            if(!$datosCom[$x]->parteRel->tot()){
                                $target=$datosCom[$x]->tipoComprobante;
                                if($target->tot()) $target->addAfter("<parteRel>NO</parteRel>",false);
                            }
                            if(!$datosCom[$x]->baseImpExe->tot()){                             
                                $target = $datosCom[$x]->baseImpGrav;
                                if($target->tot()) $target->addAfter("<baseImpExe>0.00</baseImpExe>",false);                              
                            }						
                            if($anio>='2016' && $mes>='01' && !$datosCom[$x]->valRetServ50->tot()){                           
                                $target = $datosCom[$x]->valorRetBienes;
                                if($target->tot()) $target->addAfter("<valRetServ50>0.00</valRetServ50>",false); 
                            }                       
							
                            $target = $datosCom[$x]->montoIva;
                            if($target->tot()){
                                if(!$datosCom[$x]->valRetServ20->tot()) $target->addAfter("<valRetServ20>0.00</valRetServ20>",false);
                                if(!$datosCom[$x]->valRetBien10->tot()) $target->addAfter("<valRetBien10>0.00</valRetBien10>",false);
                            }                                
							
							if($datosCom[$x]->pagoExterior->tot()){
								
								if(!$datosCom[$x]->pagoExterior->pagoRegFis->tot()){                            
									$target = $datosCom[$x]->pagoExterior->pagExtSujRetNorLeg;
									if($target->tot())  $target->addAfter("<pagoRegFis>NA</pagoRegFis>",false);                               
								}
							}else{			
								//echo ",,,";
								$target = $datosCom[$x]->valRetServ100;
								if($target->tot()){  $target->addAfter("<pagoExterior><pagoLocExt>01</pagoLocExt><paisEfecPago>NA</paisEfecPago><aplicConvDobTrib>NA</aplicConvDobTrib><pagExtSujRetNorLeg>NA</pagExtSujRetNorLeg><pagoRegFis>NA</pagoRegFis></pagoExterior>",false);} 
                            }
							//echo ".,";
                            if(!$datosCom[$x]->totbasesImpReemb->tot()){                           
                                $target = $datosCom[$x]->valRetServ100;
                                if($target->tot()) $target->addAfter("<totbasesImpReemb>0.00</totbasesImpReemb>",false);
                            }
                            if($datosCom[$x]->air->tot()&&$datosCom[$x]->air->detalleAir->tot()){
                                if($datosCom[$x]->air->detalleAir->codRetAir->text()=='341')
                                    $datosCom[$x]->air->detalleAir->codRetAir='344';    
                                if($datosCom[$x]->air->detalleAir->codRetAir->text()=='340')
                                    $datosCom[$x]->air->detalleAir->codRetAir='312';
                            }
                            if(('0'.$datosCom[$x]->baseImponible)*1>=1000){                                 
                                $target = $datosCom[$x]->pagoExterior;
                                if($target->tot()) $target->addAfter("<formasDePago><formaPago>01</formaPago></formasDePago>",false); 
                            }        
                        }
                    }
                    /*  V E N T A S */
                    if($sri->ventas->tot()){
                        $datosVen = $sri->ventas[0]->detalleVentas;   
                        $totVen=count($datosVen);
                        for($x=0;$x<$totVen;$x++){  
                            if($datosVen[$x]->tpIdCliente->text() != '07'){
                                if($fecha>='2016-01'){
                                    if(!$datosVen[$x]->parteRelVtas->tot()){ 
                                        if($fecha >= '2016-05' && $datosVen[$x]->tpIdCliente->text() == '06' && !$datosVen[$x]->tipoCliente->tot()){                                            
                                            $datosVen[$x]->tpIdCliente->addAfter("<parteRelVtas>NO</parteRelVtas>",false);
                                            $datosVen[$x]->parteRelVtas->addAfter("<tipoCliente>01</tipoCliente>",false);									
                                            $datosVen[$x]->tipoCliente->addAfter("<denoCli>NINGUNO</denoCli>",false);
                                        }else{
                                            $datosVen[$x]->idCliente->addAfter("<parteRelVtas>NO</parteRelVtas>",false);                                            
                                        }									
                                    }else{
                                        if($fecha>='2016-05' && $datosVen[$x]->tpIdCliente->text() == '06' && !$datosVen[$x]->tipoCliente->tot()){
                                            $datosVen[$x]->parteRelVtas->addAfter("<tipoCliente>01</tipoCliente>",false); 
                                            $datosVen[$x]->tipoCliente->addAfter("<denoCli>NINGUNO</denoCli>",false); 
                                        }
                                    }
                                }    							
                            }			
                            if(!$datosVen[$x]->tipoEmision->tot()){                             
                                $target = $datosVen[$x]->tipoComprobante;
                                if($target->tot()) $target->addAfter("<tipoEmision>F</tipoEmision>",false);                               
                            }  
                            if($fecha>='2016-06' && !$datosVen[$x]->formasDePago->tot()){ 
                                $target = $datosVen[$x]->valorRetRenta;
                                if($target->tot() && $datosVen[$x]->tipoComprobante->text()!='04') $target->addAfter("<formasDePago><formaPago>01</formaPago></formasDePago>",false);   
                            }  												
                            if($fecha>='2015-03' && !$datosVen[$x]->montoIce->tot()){                            
                                $target = $datosVen[$x]->montoIva;
                                if($target->tot()) $target->addAfter("<montoIce>0.00</montoIce>",false);                               
                            } 			        
                        }   
                    }
                    $responce['success']=true;
                }else{ $responce['success']=false;$responce['message']="La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)";break; }    
                $fila=array(     
                    'empresa'=>$empresa, 'anio'=>$anio, 'mes'=>$mes,
                    'nombre'=>$_FILES['archivoXML']['name'][$i],
                    'xml'=>($responce['success']?$sri->asXML():$responce['message'])
                );
                array_push($rows,$fila); //echo  $sri->asXML(); die();
            } 
        }
    } catch (Exception $e) {$responce['success']=false;$responce['message']= 'ERROR: '.$e->getMessage();}    
//    if($responce['success']){
        $responce['rows']=$rows;
        //utf8_encode_deep($responce['rows']);         
//    }
    echo json_encode($responce);exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE>EXA - Software Contable</TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <script src="../../framework/plugins/ace-editor/ace-1.2/ace.js"></script>
        <script src="../../framework/plugins/ace-editor/vkbeautify-0.99.js"></script> 
        <style>#editor{border-radius: 0 0 4px 4px;}</style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Aplicar formato ATS 2016</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-xs-6">
                    <FIELDSET class="exa-fieldset">
                        <LEGEND class="Titulos2">Cargas ATS</LEGEND>
                        <form method="post" name="form3" id="form3" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'];?>" class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-sm required">Seleccione:</label>  
                                <div class="col-xs-7" ><input type="file" multiple name="archivoXML[]" id="archivoXML[]" value="" accept="text/xml" class="form-control input-sm" required="" /></div>                                        
                                <div class="col-xs-3" ><button type="button" class="btn btn-sm btn-primary start" onclick="loadXML();"><i class="glyphicon glyphicon-ok"></i> <span>Aplicar</span> </button></div>
                            </div>
                        </form>
                    </FIELDSET>    
                </div>
                <div class="col-xs-6">
                    <FIELDSET class="exa-fieldset">
                        <LEGEND class="Titulos2">ATS convertidos</LEGEND>
                        <div class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-sm required">Seleccione:</label>  
                                <div class="col-xs-7" >
                                    <select id="archivos" onchange="setArchivo(this.value)" class="form-control input-sm"><option value="null.xml">Seleccione Archivo...</option></select>
                                </div>                                        
                                <div class="col-xs-3" > <button onclick="$.downloadFile(vkbeautify.xmlmin(editor.getValue(),true),$('#archivos').val());" title="Exportar Excel" class="btn btn-sm btn-primary start" > <i class="glyphicon glyphicon-download" ></i> <span>Descargar</span></button> </div>
                            </div>
                        </div>
                    </FIELDSET>   
                </div>                
            </div>   
            <div class="row">
                <div class="col-xs-12">
                    <div id="editorTitle" class="ui-widget-header ui-corner-top" style="padding: 0 10px;"></div>
                    <pre id="editor" style="height: 500px;width: 100%"></pre>
                </div>
            </div>
        </div> 
    </div> 
    <script>
    var archivos=[];     
    function loadXML(){
        var formData = new FormData(document.getElementById("form3"));
        //formData.append("uploadXML", true);
        //formData.append(f.attr("name"), $(this)[0].files[0]);
        $("#loader").show();
	$.ajax({
            url: "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",
            type: "post", dataType: "json", data: formData, cache: false, contentType: false, processData: false
        }).done(function(response){
            $("#loader").fadeOut("slow");
            if(response['success']===true){
                var options='';
                archivos=response['rows'];
                for(var i=0;i<(archivos.length);i++){
                    options=options+'<option value="'+archivos[i]['nombre']+'">'+archivos[i]['nombre']+'</option>';                                        
                }
                $('#archivos').html(options);
                $('#editorTitle').html(archivos[0]['nombre']);
                editor.setValue(vkbeautify.xml(archivos[0]['xml']), -1);
            }else{$.alert(response['message']);}                                  
        }).fail(function(error) { $.alert("El Servidor ha fallado en responder! "); $("#loader").hide();  });                              
    } 
    function setArchivo(nombre){
        $('#editorTitle').html('');
        for(var i=0;i<(archivos.length);i++){
            if(archivos[i]['nombre']===nombre){
                $('#editorTitle').html(nombre);
                editor.setValue(vkbeautify.xml(archivos[i]['xml']), -1);
                break;
            }
        }
    }
    $(document).ready(function () {
        editor = ace.edit("editor");                        
        editor.setTheme("ace/theme/sqlserver");
        editor.session.setMode("ace/mode/xml");
    });  
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>