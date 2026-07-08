<?php
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
//var_dump($uploadXML);    
if(isset($_FILES)&&!empty($_FILES)){
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
                    
                    $empresa='<b>COMPRAS&raquo;</b>&nbsp; '.$sri->IdInformante.' - '.$sri->razonSocial;
                    $totCom=count($sri->compras[0]->detalleCompras);									
                    $datosCom = $sri->compras;
                    $anio=''.$sri->Anio;$mes=''.$sri->Mes;

                    for($x=0;$x<$totCom;$x++)	
                    {   $z=$z+1;                                           
                        
                        if(count($datosCom->detalleCompras[$x]->parteRel)==0){
                            $insert = new SimpleXMLElement("<parteRel>NO</parteRel>");    
                            $target = $datosCom->detalleCompras[$x]->tipoComprobante;
                            if(count($target)==1)
                                simplexml_insert_after($insert, $target);
                        }
                        if(count($datosCom->detalleCompras[$x]->baseImpExe)==0){
                            $insert = new SimpleXMLElement("<baseImpExe>0.00</baseImpExe>");    
                            $target = $datosCom->detalleCompras[$x]->baseImpGrav;
                            if(count($target)==1)
                                simplexml_insert_after($insert, $target);
                        }
                       
                        $insert1 = new SimpleXMLElement("<valRetServ20>0.00</valRetServ20>"); 
                        $insert2 = new SimpleXMLElement("<valRetBien10>0.00</valRetBien10>");
                        $target = $datosCom->detalleCompras[$x]->montoIva;
                        if(count($target)==1){
                            if(count($datosCom->detalleCompras[$x]->valRetServ20)==0)
                                simplexml_insert_after($insert1, $target);
                            if(count($datosCom->detalleCompras[$x]->valRetBien10)==0)
                                simplexml_insert_after($insert2, $target);
                        }    
                        if(count($datosCom->detalleCompras[$x]->pagoExterior->pagoRegFis)==0){
                            $insert = new SimpleXMLElement("<pagoRegFis>NA</pagoRegFis>");    
                            $target = $datosCom->detalleCompras[$x]->pagoExterior->pagExtSujRetNorLeg;
                            if(count($target)==1)
                                simplexml_insert_after($insert, $target);
                        }
						if(count($datosCom->detalleCompras[$x]->totbasesImpReemb)==0){
                            $insert = new SimpleXMLElement("<totbasesImpReemb>0.00</totbasesImpReemb>");    
                            $target = $datosCom->detalleCompras[$x]->valRetServ100;
                            if(count($target)==1)
                                simplexml_insert_after($insert, $target);
                        }
						
                        if(($datosCom->detalleCompras[$x]->air->detalleAir->codRetAir.'')=='341')
                            $datosCom->detalleCompras[$x]->air->detalleAir->codRetAir='344';    
                        if(($datosCom->detalleCompras[$x]->air->detalleAir->codRetAir.'')=='340')
                            $datosCom->detalleCompras[$x]->air->detalleAir->codRetAir='312';
                        
                        if(('0'.$datosCom->detalleCompras[$x]->baseImponible)*1>=1000){                                 
                            $target = $datosCom->detalleCompras[$x]->pagoExterior;
                            if(count($target)==1)
                                if(count($datosCom->detalleCompras[$x]->formasDePago)==0){
                                    $insert = new SimpleXMLElement("<formasDePago><formaPago>01</formaPago></formasDePago>");   
                                    simplexml_insert_after($insert, $target);
                                }
                        }        
                    }
                    $totVen=count($sri->ventas[0]->detalleVentas);									
                    $datosVen = $sri->ventas;   
                    for($x=0;$x<$totVen;$x++)	
                    {   $z=$z+1;                       
                        
                        if(($datosVen->detalleVentas[$x]->tpIdCliente.'') != '07'){
                            if(count($datosVen->detalleVentas[$x]->parteRelVtas)==0){
                                $insert = new SimpleXMLElement("<parteRelVtas>NO</parteRelVtas>");    
                                $target = $datosVen->detalleVentas[$x]->idCliente;
                                if(count($target)==1)
                                    simplexml_insert_after($insert, $target);
                            }    
                        }
                                
                    }
                    
                    $responce['success']=true;
                }else{$responce['success']=false;$responce['message']="La extension del archivo debe ser <u>.XML</u> (<i>eXtensible Markup Language</i>)";break;}    
                $fila=array(                    
                    'xml'=>($responce['success']?$sri->asXML():$responce['message']), 
                    'nombre'=>$_FILES['archivoXML']['name'][$i],
                    'empresa'=>$empresa, 
                    'anio'=>$anio,
                    'mes'=>$mes
                );
                array_push($rows,$fila);
                //echo  $sri->asXML();
                //die();
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?>
                <script src="../../framework/plugins/ace-editor/ace-1.2/ace.js"></script>
                <script src="../../framework/plugins/ace-editor/vkbeautify-0.99.js"></script>
                <!--<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />-->
                <script language="JavaScript">
		
		</script>
	</HEAD>
<BODY>

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
	  <td height="10">&raquo; Aplicar formato ATS 2015</td>
</tr>
<tr>
 <td align="left" valign="top" height="400">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td> <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Cargas ATS</label>
    </LEGEND>
        
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
     <tr>	
       <td width="87" align="right" class="LetraNegra">Seleccione:</td> 
       <form method="post" name="form3" id="form3" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'];?> ">
       <td width="380">&nbsp;<input type="file" multiple name="archivoXML[]" id="archivoXML[]" value="" accept="text/xml" />&nbsp;&nbsp;&nbsp; 
       <button type="button" class="btn btn-primary start" onclick="loadXML();"><i class=" icon-ok-sign icon-white"></i> <span>Aplicar</span> </button>
         <span style="display: inline-block;width: 50px;">&nbsp;</span>                    
       </td>
         
       </form>      
     </tr>   
     </table>
       
    </FIELDSET> </td>
        <td><FIELDSET>
    <LEGEND>
    <label class="Titulos2">ATS convertidos </label>
    </LEGEND>        
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
     <tr>	
       <td width="87" align="right" class="LetraNegra">Archivo(s):</td> 
       <td width="380"><select id="archivos" onchange="setArchivo(this.value)">
               <option value="null.xml">Seleccione Archivo...</option>              
            </select>&nbsp;&nbsp;&nbsp; <button onclick="$.downloadFile(vkbeautify.xmlmin(editor.getValue()),$('#archivos').val());" title="Exportar Excel" class="btn btn-primary start" > <i class="icon-share icon-white" ></i> <span>Descargar</span></button> </td>
     </tr>   
     </table>       
    </FIELDSET> </td>
      </tr>
    </table>                 
        
    <FIELDSET>
        <LEGEND>
            <label class="Titulos2">Resultados</label>
        </LEGEND>
       
        <pre id="editor" style="height: 500px;width: 100%"></pre>
            <table id="list"></table>
            <div id="listPager"></div>
         
        
        
        <!--<div>
            <table id="list"></table>
            <div id="listPager"></div>
        </div> 
         <div style="padding:5px;">
                  <button onclick="$('#list').jqGrid('printGrid',{nombre:'Reporte de ATS',bodyBorder:false});" title="Imprimir Reporte" type="button" class="btn btn-primary start" > <i class="icon-print icon-white"></i> <span>Imprimir</span></button>
                  <button onclick="$('#list').jqGrid('exportGridExcel',{nombre:'Reporte ATS',hoja:'Hoja ATS',caption:true,generated:false});" title="Exportar Excel" class="btn btn-primary start" > <i class="icon-share icon-white" ></i> <span>Excel</span></button>               
                
              </div>-->
    </FIELDSET>    
    </td>
</tr>
</table>
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
                editor.setValue(vkbeautify.xml(archivos[0]['xml']), -1);

            }else{$.alert(response['message']);}                                  
        }).fail(function(error) { $.alert("El Servidor ha fallado en responder! "); $("#loader").hide();  });                              
    } 
    function setArchivo(nombre){
        for(var i=0;i<(archivos.length);i++){
            if(archivos[i]['nombre']===nombre){
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
