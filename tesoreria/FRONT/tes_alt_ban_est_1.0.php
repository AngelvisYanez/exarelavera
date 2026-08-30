<?php	
/**
* @abstract Permite registrar los cheques 
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-08
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Che;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($cuenAjax)){ 
    $contar = $obBD_con1->getRowConsulta(352, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $rs_buscar =  $obBD_con1->getArrayConsulta(352, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	
    $responce['rows']=$rs_buscar;utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}
if(isset($provAjax)){ 
    $contar = $obBD_con1->getRowConsulta(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $rs_buscar =  $obBD_con1->getArrayConsulta(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	
    $responce['rows']=$rs_buscar;utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}
if(isset($chequeCobro)){  
        $fecha = date("Y-m-d", strtotime(str_replace("/","-",$chequeCobro['fecha'])));
        $rs_buscar =  $obBD_con1->getArrayConsulta(353, $Ses_Emp_Cod.'*'.$chequeCobro['numero'].'*'.$Pld_Cod, $obBD_conexion);
        if(count($rs_buscar)>0){
            if($rs_buscar[0]['Che_Est']=="A"){
                $obBD_con1->inicio_transaccion($obBD_conexion->conexion);        
                        $obBD_con1->grabarv_registros(sentencias_che(354,$obBD_con1->parametros('C*'.$fecha.'*'.$rs_buscar[0]['Asi_Cod'].'*'.$chequeCobro['numero'])), $obBD_conexion->conexion);                        	           
                $responce['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);  
                if($obBD_con1->Error==0){ $responce['success']=true; }else{ $responce['success']=false;$responce['message']="No se ha logrado realizar la Trasaccion";}
            }
            else{$responce['success']=false;$responce['message']="El Cobro ya esta registrado!";}
        }else{$responce['success']=false;$responce['message']="El Cheque no se Encuentra Registrado";}
        echo json_encode($responce);
	exit();
}
if(isset($uploadCsv)){
    $responce['success']=false;
    $responce['message']="No se ha encontrado ningun registro!";
    $explode_name = explode('.',$_FILES['archivoCsv']['name']);
    if($explode_name[1] == 'csv'||$explode_name[1] == 'CSV'){
//        {$punteros=array("headers"=>array("Fecha Real","Descripcion","Concepto","Tipo","Numero","Valor"),"tipos"=>array("DEP"=>"DEPOSITOS","CHE"=>"CHEQUES","N/C"=>"NOTAS DE CREDITO","N/D"=>"NOTAS DE DEBITO"));}
	$fila = -1;$header=Array();$data=Array();$caracter=",";
	if (($gestor = fopen($archivoCsv, "r")) !== FALSE) {
		while (($datos = fgetcsv($gestor, 1000, $caracter)) !== FALSE) {
			if(count($datos)==1){$caracter=";";$datos=explode($caracter,$datos[0]);}//En caso de haber ";"
			//Pasa Asosiativo
			if($fila==-1){$header=$datos;}
			else{			
				$numero = count($datos);
				for ($c=0; $c < $numero; $c++) {				
					$data[$fila][$header[$c]]=$datos[$c];
				}				
			}//fin asosiativo
			$fila++;			
		}
		fclose($gestor);
	}
	$i=0;
        $dataFormat=Array();
        $headers_buscar = $obBD_con1->getArrayConsulta(348,"",$obBD_conexion);        
        $headers_alias = $obBD_con1->getArrayConsulta(349,"",$obBD_conexion);
        $headers_tipos = $obBD_con1->getArrayConsulta(350,"tipo*",$obBD_conexion);       
        foreach($data as $fila){
                $responce['success']=true;
                $dataFormat[$i]['id']=$i;
                for($j=0;$j<count($headers_buscar);$j++){//crea array asosiativo estanadar
                    foreach($headers_alias as $alias){
                        if(isset($fila[$alias["Flc_Cam"]])&&$headers_buscar[$j]["Fil_Cod"]==$alias["Fil_Cod"])
                            {$dataFormat[$i][$headers_buscar[$j]["Fil_Cam"]]=$fila[$alias["Flc_Cam"]];break;}
                    }
                }
                $dataFormat[$i]["tipo"]="TIPO TRANSACCION DESCONOCIDA";
                foreach($headers_tipos as $tipos){//asigna los tipos de transaccion para los grupos estandar
                    if($dataFormat[$i]["tipo_id"]==$tipos["Flc_Cam"])
                    {$dataFormat[$i]["tipo"]=$tipos["Fil_Cam"];break;}
                }
                $i++;
//                $dataFormat[$i]['id']=$i;
//                $dataFormat[$i]['fecha']=$fila[$punteros['headers'][0]];
	}
        $responce['grid']['rows']=$dataFormat;$responce['grid']['page'] = 1;$responce['grid']['total'] = 1;utf8_encode_deep($responce['rows']);
        $responce['grid']['records'] = count($dataFormat);
    }
    else{$responce['message']="La extension del archivo debe ser <u>.CSV</u> (<i>Comma Separated Values</i>)";}          
    echo json_encode($responce);
    exit();
}
if(isset($save)){    
    $rs_buscar =  $obBD_con1->getArrayConsulta(358, $Num_Doc."*".$Ses_Emp_Cod.'*'.$Pld_Cod, $obBD_conexion);
     if(count($rs_buscar)==0){
                /**
		* Inicio de la transaccion
		*/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		/** 
		* Mes del comprobante 
        	*/
		$var_mes = explode('-', $Com_Fec);
		$Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod, $var_mes[1], $obBD_conexion);		
		/** 
		* Inserci�n del Comprobante 
		*/
		if ($op=="I") { $tabla="cliente"; $campo="Cli_Cod"; } if ($op=="E" || $op=="D") { $tabla="proveedore"; $campo="Prv_Cod"; }
		$obBD_con1->grabarv_registros(sentencias_che(356,$obBD_con1->parametros($Pec_Cod.'*'.$Codigo.'*'.$Com_Num.'*'.$Com_Fec.'*'.$Com_Con.'*'.$Tia_Cod
											.'*'.$Com_Val.'*'.$Com_Obs.'*'.$Com_Tipo.'*'.$campo.'*'.$Num_Doc)),$obBD_conexion->conexion);
		$ultimo = $obBD_con1->insercionid ($obBD_conexion->conexion);	
		/** 
		* Recorre el arreglo de los datos de las cuentas seleccionadas 
		*/
                foreach ($save as $row)
                {
                    if($row['Det_Tip']=='D') {$valor=$row['Debe'];}
                    else {$valor=$row['Haber'];}
                    $obBD_con1->grabarv_registros(sentencias_che(357,$obBD_con1->parametros($ultimo.'*'.$row['Det_Tip'].'*'.$valor.'*'.$row['Pld_Des'].'*'.$row['Glosa'].'*'.
											$row['Pld_Cod'])),$obBD_conexion->conexion);
                }		
		/**
		* Finaliza la transacci�n
		*/
		$responce['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
                $responce['message']="No se ha logrado realizar la Trasaccion";
         
     }else{
                $responce['success']=false;
                $responce['message']="El Documento Bancario ya esta Registrado!";
     }
                echo json_encode($responce);
	exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?> 
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                <style>                   
                    
                </style>
	</HEAD>
<BODY>
<div id="set1">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Registrar Movimientos Bancarios/Cheques Cobrados<?Php echo $periodo; ?></td>
        </tr>
      <tr>
      <td height="389" align="left" valign="top">
          
<!-- INICIO FORMULARIO BUSQUEDA -->
        <form enctype="multipart/form-data" action="<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post" name= "form1" id= "form1">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>  
                     <td width="50%">
            <FIELDSET style="height: 50px;">
		<LEGEND>
                    <label class="Titulos2">Seleccionar Bancos</label>
		</LEGEND>
		<table width="100%" height="36" border="0" cellpadding="0" cellspacing="0" >
			<tr>
			  <td width="77" class="BarraBusqueda" style="border-right: 0px;"><div align="right" >Banco:</div></td>
			  <td class="BarraBusqueda" style="border-left: 0px;">
                              <select name="bancos" id="bancos" onchange="setPeriodo()" >
<?php
    $rs_bancos = $obBD_con1->getArrayConsulta(339,$Ses_Emp_Cod, $obBD_conexion);
    if (count($rs_bancos) > 0) 
    { 
        foreach ($rs_bancos as $row){  
?>
                                  <option value="<?php echo $row['Pld_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].") - A&ntilde;o: ".$row['Periodo']." "; ?></option>
<?php
        }?>
<script>
    var bancos=<?php echo json_encode($rs_bancos)?>;    
    var dataFromRow=[];
    function setPeriodo(){  
        $("input[name='Pld_Cod']").val(getBanco()["Pld_Cod"]);
        $("input[name='Pec_Cod']").val(getBanco()["Pec_Cod"]);
        $("input[name='periodo']").val(getBanco()["Periodo"]);  
        $('#Com_Fec').dateLimits(getBanco()["Pec_Fei"],getBanco()["Pec_Fef"]);
    }
    function getBanco(){
        for(var i=0;i<bancos.length;i++)
            if(bancos[i]['Pld_Cod']===$("#bancos").val())
                return bancos[i];
    }    
    //alert(bancos.length)
</script>                                  
<?php        
    }
?>
                              </select>
                          </td>			 
			</tr>
                 </table>
            </FIELDSET>
                    </td>
                     <td width="50%">
            <FIELDSET style="height: 50px;">
                <LEGEND>
                    <label class="Titulos2">Ingrese Archivo CSV</label>
		</LEGEND>
                <table width="100%" height="36" border="0" cellpadding="0" cellspacing="0" >
			<tr>
			  <td width="10" class="BarraBusqueda"  style="border-right: 0px;"></td>
			  <td class="BarraBusqueda"  style="border-left: 0px;">
                              <input style="width:98%;" type="file" id="archivoCsv" name="archivoCsv" accept=".csv"  />
                          </td>
			  <td width="200"><div align="center">	                                
                                  <button type="button" class="btn btn-success" title="Subir Archivo CSV" onclick="LoadCsv()"> <i class="icon-upload icon-white"></i> <span>Subir Informaci&oacute;n</span> </button>
                            </div>
                          </td>
			</tr>
                 </table>
            </FIELDSET>
                    </td>
                </tr>
            </table>
        </form>
<!-- FIN FORMULARIO BUSQUEDA -->          
          
<!-- INICIO DEL PANEL CSV -->
          <div id="grilla">
            <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
                <div>   
                                    <table id="list"></table>
                                    <div id="listPager"></div>
                </div>
                 <script type="text/javascript"> 
                    function cobroCheque(id) {    
                            $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{chequeCobro:$('#list').jqGrid ('getRowData', id),Pld_Cod:getBanco()["Pld_Cod"]}, function( response ) {
                                 if(response['success']===true){
                                     $.alert("Transaccion Realizada con &Eacute;xito!");
                                     $('#list').jqGrid('delRowData',id);                                     
                                 }else{$.alert(response['message']);}
                             },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });                       
                    }
                    function LoadCsv(){	                            
                            var formData = new FormData(document.getElementById("form1"));
                            formData.append("uploadCsv", true);
                            //formData.append(f.attr("name"), $(this)[0].files[0]);
                            $.ajax({
                                url: "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",
                                type: "post", dataType: "json", data: formData, cache: false, contentType: false, processData: false
                            }).done(function(response){
                                if(response['success']===true){
                                    $("#list").jqGrid("clearGridData");                                    
                                    $("#list").jqGrid('setGridParam',{rowNum:response['grid']['records']});
                                    $("#list").jqGrid('setGridParam', {data:response['grid']['rows'],page:1,records:response['grid']['records'],total:response['grid']['total'] }).trigger('reloadGrid');
                                    $("#comprobante").hide();$("#grilla").show("slide",{},1000);
                                    $("#form1").effect("highlight",{},500);
                                }else{$("#list").jqGrid("clearGridData");$.alert(response['message']);}                                  
                            }).fail(function(error) { $.alert("El Servidor ha fallado en responder! "); });                              
                    }                    
                    $(document).ready(function () {                        
                        var gridList=$("#list");
                        gridList.jqGrid({
                            url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },  
                            postData: {},
                            autowidth : true, shrinkToFit: true, height: 270,
                            //cmTemplate: {sortable:false},
                            colModel: [
                                { label: 'Cód.Int.', name: 'id', key: true, width: 15,align:"center", hidden:true },                                
                                { label: 'Fecha', name: 'fecha', width: 45 },                      
                                { label: 'Concepto', name: 'concepto', width: 150  },
                                { label: 'Detalle', name: 'detalle', width: 180, viewable: true,hidden:false,editrules:{edithidden:true} },
                                { label: 'Tipo', name: 'tipo', width: 25, align:"center"},                                         
                                { label: 'Documento', name: 'numero', width: 40 },                                
                                { label: 'Valor', name: 'valor', width: 40, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {prefix:'$ ', thousandsSeparator:'.'},
                                        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
                                },
                                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                        formatter:function (cellvalue, options, rowObject) {    
                                            var tipo="";                                                
                                            if(rowObject.tipo==="NOTAS DE DEBITO") tipo="H";
                                            if(rowObject.tipo==="NOTAS DE CREDITO"||rowObject.tipo==="DEPOSITOS") tipo="D";
                                            var selectBut='<span class="btn btn-success btn-mini" title="Seleccionar" type="button" onclick="Select(\''+rowObject.id+'\',\''+tipo+'\')"><i class="icon-arrow-right icon-white"></i></span>';
                                            if(rowObject.tipo==="CHEQUES"){
                                                var confirmar="$.createDialogConfirm('Desea Registrar el Cobro del <b>CHEQUE No. "+rowObject.numero+"</b> del <b>"+getBanco()['Pld_Des']+"</b> con valor: <b> $ "+rowObject.valor+"<b/>','"+rowObject.id+"',cobroCheque)";
                                                selectBut='<span class="btn btn-success btn-mini" title="Registrar Cobro" type="button" onclick="'+confirmar+'"><i class="icon-arrow-right icon-white"></i></span>';
                                            }                                           
                                            return  '<span class="btn btn-info btn-mini" title="Ver" type="button" onclick="$(\'#list\').viewGridRow(\''+rowObject.id+'\');"><i class="icon-info-sign icon-white"></i></span><span>&nbsp;&nbsp;</span>'+
                                                     selectBut; 
                                        }
                                    }
                            ],    
                            sortname:'fecha',sortorder:"asc",
                            pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null,
                            grouping: true,
                            groupingView: {
                                groupField: ["tipo"],//direction : "rtl",
                                groupColumnShow: [false],
                                groupText: ["<div><span style='float:left;'> {1} Transacion(es)</span> <b>{0}</b>   <b style='position: absolute;right: 25px;'>Total: $ {valor} <b></div>"],
                                groupOrder: ["desc"],
                                groupSummary: [false],
                                groupCollapse: true

                            }
                        });                        
                        gridList.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
                        gridList.jqGrid('bindKeys');                       
                        //$("#list").gridResize();
                       
                    });  
               </script>
	    </FIELDSET>
          </div>
<!-- FIN DEL PANEL CSV -->
          
<!-- INICIO PANEL COMPROBANTE -->          
          <div id="comprobante" style="width: 100%;">
               <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Datos del Comprobante</label>
		</LEGEND>
                <fieldset>
	<legend>
	<label class="Titulos2">Generales</label>
	</legend>	
                    <form id="formComp" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?',null,saveComp)">
        <table width="100%" border="0" cellpadding="0" cellspacing="0">  
  <tbody><tr>
    <td class="Etiqueta1">Tipo Comprobante:</td>
    <td class="LetraNegra">       
    <select name="Tia_Cod" id="Tia_Cod" style="width: 300px;" class="isSelectMenu" class="select" required >
    	<option value="">Seleccione...</option>
<?Php 
$row_rs_tipo_asien = $obBD_con1->getArrayConsulta(347, "", $obBD_conexion);
foreach ($row_rs_tipo_asien as $row) 
{
?>
            <option value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des'] ?> </option>
<?php
}
?>
          </select>
    </td>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha:</td>
    <td class="LetraNegra">      <input name="Com_Fec" type="text" id="Com_Fec" style="text-align: center" size="10" maxlength="10" class="isDatePicker text ui-corner-all" />
      </td>
    </tr>
  <tr>
    <td width="106" class="Etiqueta1">Proveedor/Cliente:</td>
    <td width="509" class="LetraNegra" style="padding-top:3px;padding-bottom: 3px;">
        &nbsp;<label id="lblProvee" style="font-weight: bold;">Seleccione un proveedor...</label> &nbsp; &nbsp;
        <a onclick="$('#provDialog').dialog('open')" class="btn btn-success btn-mini" title="Buscar un Proceedor"><i class=" icon-check icon-white"></i></a></td>
    <td class="Etiqueta1">No. Externo:</td>
    <td><input  class="ui-widget-content ui-corner-all" readOnly style="text-align: center" name="Num_Doc" type="text" id="Num_Doc" size="10"  /></td>
    </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Concepto:</td>
    <td class="LetraNegra">
      <textarea class="text ui-corner-all" name="Com_Con" cols="73" style="text-transform:uppercase" id="Com_Con" onkeypress="return  validar_injections(event)"></textarea></td>
    <td class="Etiqueta1" style="vertical-align: top;">Valor:</td>
    <td style="vertical-align: top;"><input  class="money ui-widget-content ui-corner-all" readOnly name="Com_Val" type="text" id="Com_Val" size="10" maxlength="12" style="text-align:right" alt="" /></td>
  </tr>
  <tr>
      <td class="Etiqueta1">Observaci&oacute;n:</td>
    <td><textarea class="text ui-corner-all" name="Com_Obs" cols="73" style="text-transform:uppercase" id="Com_Obs" onkeypress="return  validar_injections(event)"></textarea></td>
    <td class="Etiqueta1" style="vertical-align: top;">Periodo Contable:</td><td style="vertical-align: top;">
        <input  class="ui-widget-content ui-corner-all" readOnly name="periodo" size="10" style="text-align: center" type="text" /> 
        <input type="hidden" name="Pec_Cod" />
        <input type="hidden" name="Pld_Cod" />
        <input type="hidden" id="cod_pvr" name="Codigo" value="" required /> 
        <input type="hidden" name="op" value="D" /> </td>
  </tr>
  </tbody></table></form>
	</fieldset>
                <div style="padding-top: 6px;">   
                                    <table id="comp"></table>
                                    <div id="compPager"></div>
                </div>
                <script type="text/javascript">                    
                    function saveComp(){
                        var gridComp = $("#comp"),
                                tot=parseFloat($("#Com_Val").val()),
                                deb = gridComp.jqGrid("getCol", "Debe", false, "sum"),
                                hab = gridComp.jqGrid("getCol", "Haber", false, "sum");                       
                        if(deb===tot&&hab===tot){                            
                            if($('#Tia_Cod').val()!==''){
                                if($('#cod_pvr').val()!==''){
                                    var batch = gridComp.getGridBatch();
                                    if(batch.length>0){ 
                                        var data=$('#formComp').serializeObject();
                                        data["save"]=batch;
                                        $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function( response ) {
                                            if(response['success']===true){
                                                $.alert("Transaccion Realizada con &Eacute;xito!");
                                                $('#comprobante').prev('#grilla');
                                                $('#list').jqGrid('delRowData',dataFromRow['id']);                                     
                                            }else{$.alert(response['message']);gridComp.startGridEdit();}
                                         },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");gridComp.startGridEdit(); });
                                    }
                                }else{$.alert("Seleccione El Proveedor");}
                            }else{$.alert("Seleccione Tipo de Comprobante");}                            
                        }else{$.alert("Los Totales no Coinciden!");}
                    }
                    function addFilaCuenta(cuenta,tipo){                        
                        cuenta['Glosa']=dataFromRow['concepto']+" Doc.#"+dataFromRow['numero'];
                        cuenta['Debe']=cuenta['Haber']=0;
                        if(tipo==="D")
                            cuenta['Debe']=dataFromRow['valor'];
                        else
                            cuenta['Haber']=dataFromRow['valor'];
                        cuenta['Det_Tip']=tipo;        
                        var grid=$("#comp");
                        grid.jqGrid("addRowData", cuenta["Pld_Cod"], cuenta, "last");        
                        grid.startGridEdit();
                    }
                    function SelectCta(id,tipo){                        
                        if(!$("#comp").existsId(id)){
                            addFilaCuenta($.getDialogGrid("#cuenDialog").jqGrid('getRowData', id),tipo); 
                            $('#cuenDialog').dialog('close');
                        }
                    }
                    function Select(id,tip){
                        $("#comp").clearGrid();                        
                        $('#grilla').next('#comprobante');                        
                        dataFromRow = $('#list').jqGrid ('getRowData', id);  
                        $("#Tia_Cod").val('');//$('#Tia_Cod').selectmenu('refresh', true);                        
                        $("#Com_Val").val(dataFromRow['valor']);
                        $("#Com_Con").html(dataFromRow['concepto'].replace(/\*/g, " ")+" Doc.#"+dataFromRow['numero']);                       
                        $("#Com_Obs").html(dataFromRow['detalle'].replace(/\*/g, " ")); 
                        $("#Num_Doc").val(dataFromRow['numero']);
                        $("#lblProvee").html("Seleccione un proveedor...");
                        $("#cod_pvr").val("");
                        if(tip!=="")
                            addFilaCuenta(getBanco(),tip);
                    }
                    $(document).ready(function () {  
                        var gridComp=$("#comp");
                        gridComp.jqGrid({
                            url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },                             
                            autowidth : true, shrinkToFit: true, height: 63,
                            cmTemplate: {sortable:false},
                            colModel: [
                                { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center", hidden:true },  
                                { label: 'Tipo', name: 'Det_Tip', hidden:true },
                                { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                                { label: 'Cuenta', name: 'Pld_Des', width: 150  },
                                { label: 'Glosa', name: 'Glosa', width: 150,editable:true },
                                { label: 'Debe', name: 'Debe', width: 50, align: 'right', formatter:'currency',
                                     formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: <b>{0}<b/>", summaryType: "sum"
                                },                                         
                                { label: 'Haber', name: 'Haber', width: 50,align: 'right', formatter:'currency', 
                                     formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum"
                                },
                                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                        formatter:function (cellvalue, options, rowObject) { 
                                            return  '<span class="btn btn-danger btn-mini" title="Eliminar" onclick="$(\'#comp\').jqGrid(\'delRowData\',\''+rowObject.Pld_Cod+'\');"><i class="icon-remove icon-white"></i></span>'; 
                                        }
                                    }
                            ],
                            //caption:"Cuentas",hidegrid: false,     
                            footerrow: true, userDataOnFooter: false,// set a footer row                             
                            rowNum: 10000000, pager: "#compPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null,
                            gridComplete: function () {
                                var $self = $(this),
                                deb = $self.jqGrid("getCol", "Debe", false, "sum"),
                                hab = $self.jqGrid("getCol", "Haber", false, "sum");
                                $self.jqGrid("footerData", "set", {Glosa: "<div style='text-align:right;'>Totales:</div>", Debe: deb, Haber:hab});
                            }

                        });                        
                        gridComp.navGrid('#compPager',{ edit: false, add: false, del: false, search: false, refresh: false, view: false, position: "left", cloneToTop: false });
                        gridComp.jqGrid('bindKeys');                                               
                        $.clearFooterDiario("#comp");
                    });  
                </script>  
                 <div style="padding-top: 6px;">
                  <button onclick="$('#comprobante').prev('#grilla');" class="btn btn-inverse fileinput-button" title="Volver Atrás"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button><span style="width: 150px;"></span>
		  <button onclick="$('#formComp').formSubmit();" type="button" class="btn btn-primary start" title="Guardar el Documento"><i class="icon-book icon-white"></i><span>Guardar</span></button><span style="width: 15px;"></span>
	          <button onclick="$('#cuenDialog').dialog('open');" type="button" class="btn btn-success fileinput-button" title="Agregar Cuentas"><i class="icon-list-alt icon-white"></i><span>Agregar</span></button>
                 </div>
               </FIELDSET>
              
          </div>
<!-- FN PANEL COMPROBANTE -->
        </td>
      </tr>     
    </table>
   	
</div>	
<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas">     
        <form>          	
                <FIELDSET>
                    <LEGEND>
                        <label class="Titulos2">B&uacute;squeda de Cuentas</label>
                    </LEGEND>
                    
                    <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="156"><input name="op_opciones" type="radio" checked="checked" value="d" onclick="setfocus(this.form.search)" />
                                    <span class="LetraNegra"><strong>Descripci&oacute;n</strong></span></td>
                              <td width="156"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                    <span class="LetraNegra"><strong>C&oacute;digo</strong></span></td>
                                <td>Plan de Cuentas:
                                    <input name="periodo" type="text" size="6" readonly style="text-align: center" class="text ui-corner-all ui-widget-content"/> 
                                   <input name="Pec_Cod" type="hidden" /> 
                                </td>
                            </tr>
                    </table>
                    <table height="36" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                      <tr>
                          <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus /></td>
                          <td width="109" align="center">
                            <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar cuenta" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button></td>
                      </tr>
                        </tbody>
                    </table>                                                  
                    </FIELDSET>              
            </form>
    </div>
<!-- FIN DEL DIALOGO CUENTAS-->

<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="B&uacute;squeda de Proveedores">  
       <form> 
        <fieldset>
		<legend>
                    <label class="Titulos2">Búsqueda de Proveedor</label>
		</legend>
		<table border="0">
                    <tbody><tr>
			  <td width="205"><input name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" />
			  <span class="LetraNegra"><strong>Apellido</strong></span></td>
			  <td width="266"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
				<span class="LetraNegra"><strong>Cédula/R.U.C.</strong></span></td>
			</tr>
                    </tbody>
                </table>
                <table height="36" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                      <tr>
                          <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese proveedor a buscar..." autofocus /><input type="text" style="display:none"/></td>
                          <td width="109" align="center">
                              <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar cuenta" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button></td>
                      </tr>
                        </tbody>
                </table>
        </fieldset>  
       </form>
    </div>
<!-- FIN DEL DIALOGO PROVEEDOR-->

    <script type="text/javascript">  
    $(document).ready(function() {        
        $("#comprobante").hide();
        //$(".isSelectMenu").selectmenu();
        $.createDatePickers('.isDatePicker');
        setPeriodo();

            // DIALOG BUSCAR CUENTAS
             $.createSearchDialog('cuenDialog',[
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
                    { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                    { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                    { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                    { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
                    { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                                return  '<span class="btn btn-success btn-mini" title="Enviar al Débito" onclick="SelectCta(\''+rowObject.Pld_Cod+'\',\'D\');"><b>D</b></span>&nbsp;'
                                        +'<span class="btn btn-success btn-mini" title="Enviar al Crédito" onclick="SelectCta(\''+rowObject.Pld_Cod+'\',\'H\');"><b>H</b></span>'; 
                            }
                        }
                ]);    
            // DIALOG BUSCAR PREOVEEDOR    
             $.createSearchDialog('provDialog',[
                    { label: 'Cód.Int.', name: 'Prv_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },                                
                    { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                    { label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                    { label: 'Dirección', name: 'Prs_Dir',hidden:true,viewable: true },                      
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                               var clic='$("#cod_pvr").val("'+rowObject.Prv_Cod+'");$("#lblProvee").html("'+rowObject.proveedor+'");$("#provDialog").dialog("close");';
                                return  '<span class="btn btn-success btn-mini" title="Seleccionar" onclick=\''+clic+'\'><i class="icon-arrow-right icon-white"></span>'; 
                            }
                        }
                ]);     

    }); 
    </script>
</BODY>
</HTML>