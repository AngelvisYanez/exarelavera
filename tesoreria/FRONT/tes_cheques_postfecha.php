<?php	
/**
* @abstract Permite listar los cheques postfechados
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

if(isset($cheqAjax)){   
    $date="";    
    if($TipBus==2) $date=$hoy;
    else $date=$Pec_Fei.'*'.$Pec_Fef; 
    if(isset($op_fecha)){ $date=$txt_fec_ini.'*'.$txt_fec_fin;}
    $rs_buscar =  $obBD_con1->getArrayConsulta(362, $Ses_Emp_Cod.'*'.$Ban_Cod.'*'.$TipBus.'*'.$date, $obBD_conexion);	
    $responce['rows']=$rs_buscar;utf8_encode_deep($responce['rows']);
    $responce['records']=count($rs_buscar);
    echo json_encode($responce);
    exit();
}
if(isset($save)){         
        $save = str_replace('_', '*', $save); 
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);         
                $obBD_con1->grabarv_registros(sentencias_che(369,$obBD_con1->parametros($fecha.'*'.$save)), $obBD_conexion->conexion);                       		           
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);       
        if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false;$responce['message']=$obBD_con1->MsgError;
	echo json_encode($responce);exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>		
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                <?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?> 
                <?Php //require_once("../../mascaras/model1/estilos/jqgrid5.php")?> 
                <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
                <style>                   
                    
                </style>
	</HEAD>
<BODY>
<div id="set1">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
       <tr class="BarraTitulo">
	  <td height="10">&raquo; Listado de Cheques</td>
        </tr>
      <tr>
        <td height="389" align="left" valign="top">
            <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Filtros</label>
		</LEGEND>
                <form id="form1" action="javascript:LoadCheque();">
                <div style="width:50%;display: inline;float:left; ">   
                      <div class="row">
                                <div class="segmento">Selecione Banco:</div>
                                <div class="datasegmento">
                                                                 <select name="periodos" id="periodos" onchange="setPeriodo();LoadCheque();" class="text ui-corner-all" >
<?php
    $row_rs_periodos = $obBD_con1->getArrayConsulta(339, $Ses_Emp_Cod, $obBD_conexion);
    if (count($row_rs_periodos) > 0) 
    { 
        $periodo = current($row_rs_periodos);
        foreach ($row_rs_periodos as $row){  
?>
                                  <option value="<?php echo $row['Pld_Cod'].'*'.$row['Pec_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].") - A&ntilde;o: ".$row['Periodo']." "; ?></option>
<?php
        }?>
<?php        
    }
?>
                              </select>
                              <input type="hidden" name="Pec_Fei" />
                              <input type="hidden" name="Pec_Fef" />
                              <input type="hidden" name="Ban_Cod" />
<script>       
    var periodos=<?php if (count($row_rs_periodos) > 0) echo json_encode($row_rs_periodos); else echo 'new Array()';?>; 
    function setPeriodo(){
        if(periodos.length>0){  
            $("input[name='Ban_Cod']").val(getPeriodo()["Ban_Cod"]);
            $("input[name='Pec_Fei']").val(getPeriodo()["Pec_Fei"]);
            $("input[name='Pec_Fef']").val(getPeriodo()["Pec_Fef"]);   
        }
    }
    function setCaption(){        
       if($('#TipBus').val()==='1') $("#list").jqGrid('setCaption', 'Listado de Cheques del Periodo '+getPeriodo()["Periodo"]+' - '+getPeriodo()["Pld_Des"]+" (Cta.#: "+getPeriodo()["Ban_Cue"]+')');
       else if($('#TipBus').val()==='2')  $("#list").jqGrid('setCaption', 'Cheques Post Fechados - '+getPeriodo()["Pld_Des"]+" (Cta.#: "+getPeriodo()["Ban_Cue"]+') - '+$.getDate());
        else if($('#TipBus').val()==='3')  $("#list").jqGrid('setCaption', 'Cheques Cobrados - '+getPeriodo()["Pld_Des"]+" (Cta.#: "+getPeriodo()["Ban_Cue"]+') - '+$.getDate());
         else if($('#TipBus').val()==='4')  $("#list").jqGrid('setCaption', 'Cheques No Cobrados - '+getPeriodo()["Pld_Des"]+" (Cta.#: "+getPeriodo()["Ban_Cue"]+') - '+$.getDate());
          else if($('#TipBus').val()==='5')  $("#list").jqGrid('setCaption', 'Cheques Anulados - '+getPeriodo()["Pld_Des"]+" (Cta.#: "+getPeriodo()["Ban_Cue"]+') - '+$.getDate());
           else if($('#TipBus').val()==='6')  $("#list").jqGrid('setCaption', 'Cheques Protestados - '+getPeriodo()["Pld_Des"]+" (Cta.#: "+getPeriodo()["Ban_Cue"]+') - '+$.getDate());
          
    }
    function getPeriodo(){
        var aux;
        for(var i=0;i<periodos.length;i++){
            aux=$("#periodos").val().split("*");;
            if(periodos[i]['Pld_Cod']+''===aux[0]&&periodos[i]['Pec_Cod']+''===aux[1])
                return periodos[i];
        }    
        if(periodos.length===0){return new Array();}
    }        
    setPeriodo();    
</script>  
                                </div>                                 
                      </div>      
                       <div class="row">
                                <div class="segmento">Tipo Busqueda:</div>
                                <div class="datasegmento">
                                    <select class="text ui-corner-all"  onchange="LoadCheque();" id="TipBus" name="TipBus" required="">                                    
                                                                                    <option value="1"><< TODOS >></option>
                                                                                    <option value="2">Post Fechados</option>
                                                                                    <option value="3">Cobrados</option>
                                                                                    <option value="4">No Cobrados</option>
                                                                                    <option value="5">Anulados</option>
                                                                                    <option value="6">Protestados</option>
                                                                                </select>
                                </div>                                 
                      </div>    
                </div>
                    <div style="width:50%;display: inline;float:left;">
                       
                        <div class="segmento">Por Fecha: <input onchange="$('#rangeDates').toggleClass('disabled').find('input').toggleAttr('disabled')" name="op_fecha" type="checkbox" value="true" offval="false" autofocus /></div> 
                        <div id="rangeDates" class="datasegmento" style="text-align: center;">
                            Desde:<input name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="focus ui-corner-all" style="text-align: center;"  />
                            Hasta:<input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="focus ui-corner-all" style="text-align: center;"  />
                        </div>
                        <div style="text-align: center;">
                            <button type="button" onclick="this.form.submit()" class="btn btn-success" style="height: 27px;" title="Filtrar Documentos" >
                               <i class="icon-search icon-white"></i>
                               <span>Buscar</span>
                               </button>
                        </div>
                    </div>
                </form>
            </FIELDSET>
            
            <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
                <div>   
                                    <table id="list"></table>
                                    <div id="listPager"></div>
                </div>
                
            </FIELDSET>
        </td>
      </tr>
    </table>
</div>
    <script>                       
                        function LoadCheque(){	                            
                                var formData = $("#form1").getData('cheqAjax');                                
                                //formData.append(f.attr("name"), $(this)[0].files[0]);
                                $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',formData, function(response){		
                                        setCaption();
                                        $("#list").jqGrid("clearGridData");                                    
                                        $("#list").jqGrid('setGridParam',{rowNum:response['records']});
                                        $("#list").jqGrid('setGridParam', {data:response['rows'],page:1,records:response['records'],total:1 }).trigger('reloadGrid');
                                },'json');                                                      
                        } 
                        $(document).ready(function () {
                             $.createDateRange('#txt_fec_ini','#txt_fec_fin'); 
                             $('#rangeDates').toggleClass('disabled').find('input').toggleAttr('disabled');
                            var gridList=$("#list");
                            gridList.jqGrid({
                                url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },  
                                postData: {},caption:' ',hidegrid: false,
                                autowidth : true, shrinkToFit: true, height: 270,
                                //cmTemplate: {sortable:false},
                                colModel: [                                    
                                    //{ label: 'Banco ', name: 'Pld_Des', width: 150 },
                                    //{ label: 'Cuenta Bancaria', name: 'Ban_Cue', width: 75 },
                                    { label: 'Fecha', name: 'Che_Fec', width: 45 ,align:"center", sorttype:"date"},  
                                    { label: 'No. Cheque', name: 'Che_Num', width: 35, align:"center",sorttype:"int"},                                    
                                    { label: 'Beneficiario', name: 'Beneficiario', width: 100 },
                                    { label: 'Observación', name: 'Che_Obs', width: 90 },
                                    { label: 'No. Compr', name: 'Com_Num', width: 45 },             
                                    { label: 'Fec. Compr.', name: 'Com_Fec', width: 45,align:"center", sorttype:"date" },             
                                    { label: 'Valor', name: 'Che_Val', width: 45, sorttype:"currency", align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,
                                            formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}
                                    },
                                    { label: 'Estado', name: 'estado', width: 45,align:"center" },
                                    { label: 'Fec. Ban.', name: 'Che_Cob', width: 45,align:"center", sorttype:"date" },             
                                    { label: 'Cód.Int.', name: 'Che_Cod', key: true, width: 50,align:"center", hidden:true }
                                ],    
                                sortname:'fecha',sortorder:"asc",
                                pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,
                                loadComplete: function(data){
                                    //console.log(data.rows[0]);
                                    var total = data.records;
                                    for(var i=0;i<total;i++){
                                        if(data.rows[i]['estado'] ==='Anulado' || data.rows[i]['estado'] ==='Protestado')
                                            $("#"+data.rows[i].Che_Cod).css("background", "#FADDDD");
                                        if(data.rows[i]['estado'] ==='Cobrado')
                                            $("#"+data.rows[i].Che_Cod).css("background", "#DDFAE2");
                                       
                                    }
                                }
                            });                        
                            gridList.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: false, view: true, position: "left", cloneToTop: false })
                                .jqGrid('navButtonAdd',"#listPager",{ caption: "",buttonicon: "ui-icon-refresh", title:'Recargar Datos',onClickButton: function() {LoadCheque();}})
                                .jqGrid('navButtonAdd',"#listPager",{ caption: "Exportar Excel&nbsp;",buttonicon: "ui-icon-arrowthickstop-1-s",title:'Exportar a Excel',
                                    onClickButton: function() {
                                        //gridList.jqGrid('exportGridExcel',{nombre:"PostFechados",hoja:"Cheques",count:true});	
                                        gridList.jqGrid('exportGridExcel',{nombre:"Cheques",hoja:"Listado"});	
                                    },position: "last"
                                })
                                .jqGrid('navButtonAdd',"#listPager",{ caption: "Imprimir&nbsp;",buttonicon: "ui-icon-print",title:'Imprimir',
                                    onClickButton: function() {
                                        gridList.jqGrid('printGrid',{nombre:"Reporte de Cheques",bodyBorder:false});	
                                    },position: "last"
                                })
                                .jqGrid('navButtonAdd',"#listPager",{ caption: "Editar Fecha&nbsp;",buttonicon: "ui-icon-pencil",title:'Editar Fecha de Cobro',
                                    onClickButton: function() {
                                        var myGrid = $('#list'),
                                        selRowId = myGrid.jqGrid ('getGridParam', 'selrow'),
                                            celValue = myGrid.jqGrid ('getCell', selRowId, 'estado');
                                        if(celValue==='Cobrado'||celValue==='Protestado'){
                                            $("#lblComp2").val(myGrid.jqGrid ('getCell', selRowId, 'Com_Num'));
                                            $("#lblComFe2").val(myGrid.jqGrid ('getCell', selRowId, 'Com_Fec'));
                                            $("#lblCheFe2").val(myGrid.jqGrid ('getCell', selRowId, 'Che_Fec'));
                                            $("#lblProv2").val(myGrid.jqGrid ('getCell', selRowId, 'Beneficiario'));
                                            $("#lblComVal2").val(myGrid.jqGrid ('getCell', selRowId, 'Che_Val'));
                                            $("#lblCheNum2").val(myGrid.jqGrid ('getCell', selRowId, 'Che_Num'));
                                            $("#lblConce2").val(myGrid.jqGrid ('getCell', selRowId, 'Che_Obs'));
                                            $("#lblCheFeCob2").val(myGrid.jqGrid ('getCell', selRowId, 'Che_Cob'));
                                            $("#lblCheCod2").val(selRowId);
                                            $('#fechDialog').dialog('open');
                                        
                                        }
                                    },position: "last"
                                });       
                                
                            gridList.jqGrid('bindKeys');
                            LoadCheque();
                            $.createDatePickers('#lblCheFeCob2');
                            $.createDialog('#fechDialog',300,650); 
                        });  
            function saveFech(){
                 $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{save:$("#lblCheCod2").val(),fecha:$("#lblCheFeCob2").val()}, function( response ) {
                                if(response['success']===true){
                                    $('#fechDialog').dialog('close');
                                    $.alert("Transaccion Realizada con &Eacute;xito!"); LoadCheque();
                                }else{$.alert(response['message']);}                                   
                             },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });                   
            }
    </script>
    
<!--INICIO DEL DIALOGO DETALLE PAGO --> 
    <div id="fechDialog" title="Modificar Fecha">  
       
        <div>
            <div style="width: 50%;display: inline;float:left;">
                <fieldset>
                    <legend><label class="Titulos2">Datos del Cheque</label></legend>                        
                        <div class="row">
                            <div class="segmento">Beneficiario:</div><div  class="datasegmento"><input id="lblProv2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Fecha:</div><div  class="datasegmento"><input id="lblCheFe2" type="text" class="label medium ui-widget-content ui-corner-all" style="text-align: center;" readonly /></div>
                        </div>  
                        <div class="row">
                            <div class="segmento">No.:</div><div  class="datasegmento"><input id="lblCheNum2" type="text" class="text medium ui-widget-content ui-corner-all" style="text-align: right;" readonly /></div>
                        </div> 
                        <div class="row">
                            <div class="segmento">Valor:</div><div  class="datasegmento"><input id="lblComVal2" type="text" class="text medium ui-widget-content ui-corner-all" style="text-align: right;" readonly /></div>
                        </div>                        
                </fieldset> 
            </div>
            <div style="width: 50%;display: inline;float:right;">
                <fieldset>
                    <legend><label class="Titulos2">Datos Comprobante</label></legend>
                        <div class="row">
                            <div class="segmento">Compr. No.:</div><div  class="datasegmento"><input id="lblComp2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Fecha:</div><div  class="datasegmento"><input id="lblComFe2" type="text" class="label medium ui-widget-content ui-corner-all" style="text-align: center;" readonly /></div>
                        </div>                        
                       
                </fieldset> 
            </div>             
            
            <div class="row" style="padding-top: 5px;padding-bottom: 15px;">
                 <fieldset>
                    <legend><label class="Titulos2">Observación</label></legend>
                    <div  class="datasegmento" style="width:95%;"><input id="lblConce2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                 </fieldset>            
            </div>
             <div class="row">
                 <input type="hidden" id="lblCheCod2" value="" />
                            <div class="segmento">Fecha Cobro/Protesta:</div><div  class="datasegmento"><input id="lblCheFeCob2" type="text" class="label medium ui-widget-content ui-corner-all" style="text-align: center;" autofocus />
                                <button type="button" class="btn btn-primary start" onclick="javascript:$.createDialogConfirm(null,null,saveFech)" title="Guardar Cheques Cobrados"> <i class="icon-book icon-white"></i> <span>Guardar</span></button
                            </div>
                        </div>
        </div> 
    </div>
</BODY>
</HTML>        
    