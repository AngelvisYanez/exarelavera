<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por lotes
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_ccpp.php');
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

if(isset($provAjax)){ 
   $data=filter_input_array(INPUT_GET);
   $data["Emp_Cod"]=$Ses_Emp_Cod;   
    $contar = $obBD_con1->getRowConsulta(3, $data, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0)
        $responce['rows'] =  $obBD_con1->getArrayConsulta(3, $data, $obBD_conexion);
    utf8_encode_deep($responce['rows']); 
    echo json_encode($responce);exit();
}
if(isset($provAjax)){
    $contar = $obBD_con1->getRowConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
if(isset($ajaxComprobante)){ 
        switch ($Cmb_Ven)
	{
		case 1: 
			$concat=" ";
		break;
		/*******/
		case 2: 
			/*Incrmento fecha 30 dias para consultar facturas vencidas en 30 dias*/
			$Fec_Fin=fechas_futuras($hoy,30);
			$concat= " AND Cpp_Ven BETWEEN '".$hoy."' AND '".$Fec_Fin."'";
		break;
		/*******/
		case 3: 
			/*Incrmento fecha 60 dias para consultar facturas vencidas en 60 dias*/
			$val=60;
			$Fec_Fin=fechas_futuras($hoy,60);
			$concat=" AND Cpp_Ven BETWEEN '".$hoy."' AND '".$Fec_Fin."'";
		break;
		/*******/
		case 4: 
			/*Incrmento fecha 90 dias para consultar facturas vencidas en 90 dias*/
			$Fec_Fin=fechas_futuras($hoy,90);
			$concat=" AND Cpp_Ven > '".$hoy."'";
		break;
		/*******/
		case 5: 
			/*Decremento de fecha a 30 dias para consultar facturas vencidas */
			$Fec_Fin=fechas_futuras($hoy,-30);
			$concat=" AND Cpp_Ven BETWEEN '".$Fec_Fin."' AND '".$hoy."'";
		break;
		/*******/
		case 6: 
			/*Decremento de fecha a 60 dias para consultar facturas vencidas */
			$Fec_Fin=fechas_futuras($hoy,-60);
			$concat=" AND Cpp_Ven BETWEEN '".$Fec_Fin."' AND '".$hoy."'";
		break;
		/*******/
		case 7: 
			/*Decremento de fecha a 90 dias para consultar facturas vencidas */
			$Fec_Fin=fechas_futuras($hoy,-90);
			$concat=" AND Cpp_Ven < '".$hoy."'";
		break;
	}
    $responce['rows'] = $obBD_con1->getArrayConsulta(13, $Ses_Emp_Cod.'*'.$Prv_Cod.'*'.$concat.'*'.$txt_fec_ini.'*'.$txt_fec_fin.'*', $obBD_conexion);	          
        foreach ($responce['rows'] as $key => $item){ 
            if ($item['Abono'] == $item['Asi_Val']) unset($responce['rows'][$key]);
        }
    $responce['rows']=array_values($responce['rows']);    
    $responce['success']=true;$responce['records']=count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
if(isset($ajaxSubgrid)){ 
    $responce['rows'] = $obBD_con1->getArrayConsulta(7, $ajaxSubgrid, $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    $responce['records']=count($responce['rows']);
    echo json_encode($responce);exit();
}
if(isset($cuenAjax)){ 
    $contar = $obBD_con1->getRowConsulta(12, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(12, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?> 
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <style>
                    
                </style>
	</HEAD>
<BODY>
<div id="set1">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table" style="table-layout:fixed;">
	<tr class="BarraTitulo">
            <td colspan="2" height="10">&raquo; Registrar Cancelaci&oacute;n por Lotes<?Php echo $periodo; ?></td>
        </tr>
        <tr>
            <td colspan="2" >
                  <div id="comp0">
                    <fieldset>
                    <legend>
                        <label class="Titulos2">Periodo Contable</label>
                    </legend>
                        <div style="width:450px;">
                         <div class="segmento">Périodo:
                            <select name="Pec_Cod" id="periodos" onchange="" >                                
                                <?Php 
                                $rs_periodos = $obBD_con1->getArrayConsulta(5,$Ses_Emp_Cod, $obBD_conexion);                               
                                if (count($rs_periodos) > 0)
                                {
                                        foreach($rs_periodos as $row){
                                        ?>
                                                <option value="<?Php echo $row['Pec_Cod']; ?>">
                                                <?Php echo $row['Periodo']; ?></option>	
                                        <?php		
                                        }//while($row_rs_periodos = $obBD_con1->fetch_assoc($rs_periodos));
                                }//Fin del if ($total_rs_periodo > 0)                                
                                ?>	                               
                            </select>
<script>   
    var periodos=<?php if (count($rs_periodos) > 0) echo json_encode($rs_periodos); else echo 'new Array()';?>;    
    var valor=0, numChe=0, glosa=""; 
    var dataFromRow=[];
    function setPeriodo(){
        if(periodos.length>0&&$("#periodos").val()!==''){  
            $("input[name='Pla_Cod']").val(getPeriodo()["Pla_Cod"]);
            $("input[name='Pec_Cod']").val(getPeriodo()["Pec_Cod"]);
            $("input[name='periodo']").val(getPeriodo()["Periodo"]); 
            $("input[name='Com_Fec']").dateLimits(getPeriodo()["Pec_Fei"],getPeriodo()["Pec_Fef"]);
            //resetForm();
        }
    }
    function getPeriodo(){
        for(var i=0;i<periodos.length;i++)
            if(periodos[i]['Pec_Cod']===$("#periodos").val())
                return periodos[i];
         if(periodos.length===0&&$("#periodos").val()===''){return new Array();}
    }        
</script>
                          </div> 
                        <div class="datasegmento" style="">
                            <button type="button" class="btn btn-success start" onclick="$('#comp0').next('#comp1');" title="Selecciona el Periodo Contable"> <i class="icon-check icon-white"></i> <span>Seleccionar</span></button>
                        </div>
                        </div>
                    </fieldset>
                 </div>
                <div id="comp1">
                 <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
                     <tr>
                        <td>
             <FIELDSET>
		<LEGEND>
                    <label class="Titulos2">Seleccionar Proveedor</label>
		</LEGEND>
                    <form id="provFormTemp">
                       <div class="segmento">Cédula/R.U.C.:</div>                          
                       <div class="datasegmento">

                               <input id="docu" name="search" maxlength="13" onkeydown='if (event.keyCode === 13) $.SearchOrDialog("#provDialog",selectProvee);' type="text" class="search ui-corner-all clearable" placeholder="Ingrese Cedula/R.U.C. ..." title="Buscar Proveedor Por Documento o Descripción" autofocus />                               
                               <input type="text" name="op_opciones" value="c" style="display: none;" /> 
                               <input type="hidden" name="Prv_Cod" value="" />
                               <a onclick="$('#provDialog').dialog('open');//$('#docu').removeAttr('readOnly');" title="Búsqueda de Proveedores"  class="btn btn-success btn-mini"><i class=" icon-check icon-white" ></i></a>

                       </div>
                    </form>
                    <div class="segmento">Proveedor:</div><div  class="datasegmento"><input id="lblProv" type="text" class="label ui-widget-content ui-corner-all" readonly /></div><br />
                    <div class="segmento">Dirección:</div><div  class="datasegmento"><input id="lblDirec" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
             </FIELDSET>    
              
             
               
            </td>
            <td>
                <FIELDSET>
		<LEGEND>
                    <label class="Titulos2">Filtros</label>
		</LEGEND>
                  <form id="formCompTemp" action="javascript:if($('#PrvCodBus').val()===''){$.alert('Seleccione Proveedor');} else{$('#list').Search('#formCompTemp','ajaxComprobante');}">
                    <input type="hidden" id="PrvCodBus" name="Prv_Cod" value="" />  
                    <div>
                        <div class="segmento">Todos: <input name="op_opciones" type="radio" value="T" checked alt="" onchange="setCombo('T');" /></div> 
                        <div class="segmento">Vencidos: <input name="op_opciones" type="radio" value="P" alt="" onchange="setCombo('P');" /></div>
                        <div class="segmento">Por Vencer: <input name="op_opciones" type="radio" value="I" alt="" onchange="setCombo('I');" /></div>
                    </div>
                    <div>
                        <div class="segmento">Búscar:
                          </div> 
                        <div class="datasegmento" style="text-align: left;" >
                            <select name="Cmb_Ven" id="Cmb_Ven" class="text medium"  >                                
                                <option selected="selected" value="1">Todos</option>                               
                            </select>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <button type="button" onclick="this.form.submit()" class="btn btn-success" style="height: 27px;" title="Filtrar Documentos" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button>
                    </div>
                  </form>
             </FIELDSET> 
            </td>
        </tr>
      <tr>
          
          <td colspan="2" height="389" align="left" valign="top"> 
            <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
                <div id="grillaComp">   
                                    <table id="list"></table>
                                    <div id="listPager"></div>
                </div>
                 <script type="text/javascript"> 
                        var tipo='lista';
                        function setCombo(tipo){ 
                            if(tipo==='T') $('#Cmb_Ven').html('<option selected="selected" value="1">Todos</option>');
                            if(tipo==='P') $('#Cmb_Ven').html('<option selected="selected" value="7">&lt;&lt; Vencidas a 90 dias</option><option value="6">&lt;&lt; Vencidas a 60 dias</option><option value="5">&lt;&lt; Vencidas a 30 dias</option>');
                            if(tipo==='I') $('#Cmb_Ven').html('<option selected="selected" value="4">Por vencer a 90 días &gt;&gt;</option><option value="3">Por vencer a 60 días &gt;&gt;</option><option value="2">Por vencer a 30 días &gt;&gt;</option>');
                        }
                        function buscaCedula(){ 
                             var array={'search':$('#docu2').val(),'op_opciones':'C'};                           
                             $.SearchOrDialogArray("#provDialog",selectProvee,array);
                        }
                        function selectProvee(data){ 
                            if(tipo==='lista'){
                                $("#lblProv").val(data['proveedor']);
                                $("#lblDirec").val(data['Prs_Dir']);
                                $("input[name='Prv_Cod']").val(data['Prv_Cod']);
                                $("#docu").val(data['Prs_Ced']);        
                                $("#provDialog").dialog("close");        
                                $('#list').Search('#formCompTemp','ajaxComprobante');
                                //$("#docu").attr("readOnly","readOnly");
                            }
                            if(tipo==='pago'){
                                $("#lblProvee2").val(data['proveedor']);
                                $("#cod_pvr").val(data['Prv_Cod']);
                                $("#provDialog").dialog("close"); 
                            }
                        }
                        function clearFooter(){ 
                            var $footRow = $("#grillaComp .ui-jqgrid-sdiv .footrow");
                            var $name = $footRow.find('>td[aria-describedby="list_Cop_Num"]'),
                            $invdate = $footRow.find('>td[aria-describedby="list_act"]'),
                            width2 = $name.width()  + $invdate.outerWidth();
                            $invdate.css("display", "none");
                            $name.attr("colspan", "2").width(width2);

                            $footRow.find('>td[aria-describedby="list_subgrid"]').css("border-right-color", "transparent");                            
                            $footRow.find('>td[aria-describedby="list_Com_Codigo"]').css("border-right-color", "transparent");
                            $footRow.find('>td[aria-describedby="list_Cop_Fec"]').css("border-right-color", "transparent");
                            $footRow.find('>td[aria-describedby="list_Cpp_Ven"]').css("border-right-color", "transparent");
                            
     
                            $footRow.find('>td[aria-describedby="list_subgrid"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Com_Codigo"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_vencimiento"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Cop_Fec"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Cpp_Ven"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Cop_Num"]').css("background-color", "white");
                        }
                        function updateSaldos(grid){ 
                            var rows= grid.jqGrid('getRowData');
                            for(var i=0;i<rows.length;i++){                                
                                if(rows[i].act==="Yes"){grid.jqGrid("setCell", rows[i].Cpp_Cod, "Pago",rows[i].Saldo );/*console.log(rows[i].Saldo);*/}
                                else{grid.jqGrid("setCell", rows[i].Cpp_Cod, "Pago", "0.00");}
                            }
                        }
                        function updateTotals(grid){ 
                            var abonos=0,saldos=0,rows= grid.jqGrid('getRowData');//alert( grid.jqGrid('getCol', 'Com_Val', false, 'sum'));
                            for(var i=0;i<rows.length;i++){abonos=abonos+parseFloat(rows[i]['Abono']);saldos=saldos+parseFloat(rows[i]['Saldo']);}
                            grid.jqGrid('footerData', 'set', { vencimiento:"<div style='text-align:right;'>TOTALES:</div>",Asi_Val: grid.jqGrid('getCol', 'Asi_Val', false, 'sum') });
                            grid.jqGrid('footerData', 'set', { Abono: ""+abonos });
                            grid.jqGrid('footerData', 'set', { Saldo: ""+saldos });
                            grid.jqGrid("footerData", "set", {Cop_Num: "<div style='text-align:right;'>TOTAL A PAGAR:</div>",Pago:grid.jqGrid('getCol', 'Pago', false, 'sum')});
                                    
                        }
                        function selectPago(grid){ 
                            $('#Com_Val_Egre').val($('#list').jqGrid('getCol', 'Pago', false, 'sum'));
                        }
                    $(document).ready(function () {  
                        //$.createDateRange('#txt_fec_ini','#txt_fec_fin'); 
                        var compGrid=$("#list");
                        compGrid.jqGrid({
                            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                            //postData: $("#form1").getData("ajaxGrid"),
                            autowidth : true, shrinkToFit: true, height: 270,
                            cmTemplate: {sortable:false},
                            colModel: [                               
                                { label: 'Cód.Int.', name: 'Cpp_Cod', key: true, width: 15,align:"center", hidden:true },  
                                { label: 'Cód.Int.', name: 'Asi_Cod', width: 15,align:"center", hidden:true }, 
                                { label: 'No. Compr.', name: 'Com_Codigo',align:"center", width: 40 },                      
                                { label: 'Fecha Emis.', name: 'Cop_Fec',align:"center", width: 35  },
                                { label: 'Fecha Venc.', name: 'Cpp_Ven', align:"center", width: 35},
                                { label: 'Vencimiento', name: 'vencimiento', align:"center", width: 35},
                                { label: 'Total', name: 'Asi_Val', width: 45, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" 
                                },
                                { label: 'Abono', name: 'Abono', width: 45, align: 'right', decimalPlaces: '2', summaryRound: 2,formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},
                                        formatter: function (cellValue, options, rowObject) { if(!parseFloat(rowObject.Abono)) rowObject.Abono=0;return $.fn.fmatter.call(this, "currency",rowObject.Abono, options);},
                                        unformat: function (cellValue, options, cell) {var opt = $.extend(true, {}, options);/*console.log(opt);*/opt.colModel.formatter = "currency";delete opt.colModel.unformat;return $.unformat.call(this, cell, opt);},
                                        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
                                }, 
                                { label: 'Saldo', name: 'Saldo', width: 45, align: 'right',  decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {prefix:'$ ',  thousandsSeparator:',',decimalSeparator:'.'},
                                        formatter: function (cellValue, options, rowObject) {
                                            if(!parseFloat(rowObject.Saldo)){
                                            rowObject.Saldo = parseFloat(rowObject.Asi_Val) - parseFloat(rowObject.Abono);
                                            }return $.fn.fmatter.call(this, "currency", rowObject.Saldo, options);
                                        },
                                        unformat: function (cellValue, options, cell) {var opt = $.extend(true, {}, options);opt.colModel.formatter = "currency";delete opt.colModel.unformat;return $.unformat.call(this, cell, opt);},
                                        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
                                }, 
                                { label: 'No. Fact.', name: 'Cop_Num', width: 60, align:"center",resizable:false},
                                { label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act', width: 10, align: 'center',viewable: false, formatter: 'checkbox',
                                    formatoptions: { disabled: false },resizable:false
                                },
                                { classes:'columnHighlight2',label: 'A Pagar', name: 'Pago', width: 45, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},
                                        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
                                }    
                            ],    
                            footerrow: true, userDataOnFooter: false,
                            rowNum: 10000000, pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null,                           
                            loadComplete: function () {                                 
                                var grid=$(this), iCol = grid.getColumnIndexByName('act'), rows = this.rows, i, c = rows.length;
                                updateTotals(grid);                                
                                for (i = 0; i < c; i += 1) {                                    
                                    $(rows[i].cells[iCol]).click(function (e) {                                        
                                        updateSaldos(grid);updateTotals(grid);    
                                    });
                                }                                
                            },                            
                            subGridOptions: {
                                "plusicon"  : "ui-icon-triangle-1-e","minusicon" : "ui-icon-triangle-1-s","openicon"  : "ui-icon-arrowreturn-1-e","reloadOnExpand" : false,"selectOnExpand" : true
                            },subGrid: true,multiselect: false,
                            subGridRowExpanded: function(subgrid_id, row_id) {
                                var subgrid_table_id = subgrid_id+"_t";         
                                $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                                $("#"+subgrid_table_id).jqGrid({
                                        url:"<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>?ajaxSubgrid="+row_id,datatype: "json",regional : 'es',
                                        autowidth : true, shrinkToFit: true,cmTemplate: {sortable:false},//colNames: ['No','Item','Qty','Unit','Line Total'],
                                        colModel: [
                                                {label:'Cod.Int.',name:"Cpp_Cod",width:80,key:true,align:"center",hidden:true},
                                                {label:'Cod.Int.',name:"Com_Cod",width:80,key:true,align:"center",hidden:true},
                                                {label:'No. Compr.',name:"Com_Codigo",width:45,align:"center"},
                                                {label:'Fecha',name:"Pag_Fec",width:45,align:"center"},
                                                {label:'Valor',name:"Pag_Val",width:45, align: 'right', formatter:'currency', decimalPlaces: '2', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}},
                                                {label:'Obsevación',name:"Pag_Obs",width:100},
                                                {label:'Tipo',name:"Pag_Des",width:45,align:"center"}
                                        ],
                                        rowNum:10000000, pager: "",height: '100%'
                                });                                
                            }
                        });                        
                        compGrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false })
                            .jqGrid('navButtonAdd',"#listPager",{ caption:"Marcar Todo&nbsp;", buttonicon:"ui-icon-bullet", onClickButton:function(){compGrid.selectAllByComlumn('act',true);updateSaldos(compGrid);updateTotals(compGrid);}, position: "last", title:"", cursor: "pointer"})
                            .jqGrid('navButtonAdd',"#listPager",{ caption:"Desmarcar Todo&nbsp;", buttonicon:"ui-icon-radio-off", onClickButton:function(){compGrid.selectAllByComlumn('act',false);updateSaldos(compGrid);updateTotals(compGrid);}, position: "last", title:"", cursor: "pointer"})
                            .jqGrid('navButtonAdd',"#listPager",{ caption: "Exportar Excel&nbsp;",buttonicon: "ui-icon-arrowthickstop-1-s",
                                onClickButton: function() {
                                    compGrid.jqGrid('exportGridExcel',{nombre:"Prueba",hoja:"HOJATEST"});	
                                },position: "last"
                            });
                        compGrid.jqGrid('bindKeys');
                        clearFooter();                        
                    });  
               </script>
	</FIELDSET>
          </td>
      </tr>
       <tr>
          <td>
              <div colspan="2" style="padding:10px;">
                  <button onclick="$('#comp1').prev('#comp0');" class="btn btn-inverse fileinput-button" title="Volver Atrás"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button><span style="width: 150px;"></span>
                  <button type="button" class="btn btn-primary start" onclick="if($('#list').jqGrid('getCol', 'Pago', false, 'sum')===0){$.alert('El valor del Pago es Inválido');}else{tipo='pago';$('#comp1').next('#comp2');selectPago();}" title="Gestionar el Pago Seleccionado"> <i class="icon-book icon-white"></i> <span>Pagar</span></button>
                  
              </div>
          </td>
      </tr>
        </table>
                </div>               
                <div id="comp2">
                    <fieldset>
                    <legend>
                        <label class="Titulos2">Comprobante de Egreso</label>
                    </legend>	
                    <form name="formComp" id="formComp" method="post" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?',null,saveComp)">
                        <table  width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;min-width: 450px;">
                        <tr><td valign="top">
                            <div class="row">
                                <div class="segmento required">Tipo Comprobante:</div>
                                <div  class="datasegmento">
                                    <select class="text ui-corner-all"  name="Tia_Cod"  class="isSelectMenu" required>
                                        <option value="">Seleccione...</option>
                                            <?Php 
                                            $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(4, "ALL", $obBD_conexion);
                                            foreach ($row_rs_tipo_asien2 as $row) 
                                            { ?>
                                        <option value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des'] ?> </option>
                                            <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Proveedor:</div>
                                <div  class="datasegmento"><input id="lblProvee2" onkeydown='if (event.keyCode === 13) buscaProvee();' onchange="if($('#lblProvee').val()==='')$('#cod_pvr').val('');" class="search clearable ui-corner-all" placeholder="Ingrese Proveedor"  />
                    <a onclick="$('#provDialog').dialog('open')" title="B&uacute;squeda de Proveedores" class="btn btn-success btn-mini"><i class=" icon-check icon-white"></i></a></div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Concepto:</div>
                                <div  class="datasegmento"><textarea class="text ui-corner-all" name="Com_Con" id="ConEgreso" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                            </div>
                            <div class="row">
                                <div class="segmento">Observaci&oacute;n:</div>
                                <div  class="datasegmento"><textarea class="text ui-corner-all" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                            </div>
                            </td><td valign="top">
                            <div class="row">
                                <div class="segmento required">Fecha Comprobante:</div>
                                <div  class="datasegmento"><input id="confec" name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" onchange="$('#chefec').val($('#confec').val())" class="text small ui-corner-all" required /></div>
                            </div>
                            <div class="row">
                                <div class="segmento required"  >Fecha Cheque:</div>
                                <div  class="datasegmento"><div id="Che_Fec" style="display: inline"><input id="chefec" name="Che_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="text small ui-corner-all" required /></div>
                                <input onchange="$('#Che_Fec').toggleClass('disabled').find('input').toggleAttr('disabled');$('#chefec').val($('#confec').val());" id="postfecha" type="checkbox" value="true" offval="false" />Postfechado
                                </div>
                            </div>
                            <div class="row">
                                <div class="segmento required">No. Cheque:</div>
                                <div  class="datasegmento">
                                    <input  class="text small ui-corner-all" style="text-align: center" name="Num_Doc" id="NumChe" type="text" size="10" onkeypress="return  validar_numeric(event)" required />                                    
                                </div>
                            </div>  
                            <div class="row">
                                <div class="segmento required">Beneficiario:</div>
                                <div  class="datasegmento" id="Benediv"><input id="Bene_Id" name="Bene_Id" type="hidden" />
                                    <input id="apellido" name="apellido" class="text medium ui-corner-all" type="text" size="32" placeholder="Apellidos" style="width:40%;text-transform:uppercase" /><input  id="nombre" name="nombre" class="text medium ui-corner-all" type="text" size="32" placeholder="Nombres" style="width:40%;text-transform:uppercase" />
                                    <a onclick="$('#Benediv').removeClass('disabled').find('input').removeAttr('readOnly').val('');$('#apellido').focus();" title="Quitar Beneficiario" class="btn btn-success btn-mini"><i class=" icon-eject icon-white"></i></a>
                                </div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Valor Cheque:</div>
                                <div  class="datasegmento"><input  class="money text small ui-corner-all" name="Com_Val" id="Com_Val_Egre" onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required /></div>
                            </div> 
                            
                            <div class="row">
                                <div class="segmento">Periodo Contable:</div>
                                <div  class="datasegmento">
                                    <input  class="text small ui-widget-content ui-corner-all" readOnly name="periodo" size="10" style="text-align: center" type="text" /> 
                                    <input type="hidden" name="op" value="E" />
                                    <input type="hidden" name="Pec_Cod" />
                                    <input type="hidden" name="Pld_Cod" />
                                    <input type="hidden" name="Ban_Cod" />
                                    <input type="hidden" id="cod_pvr" name="Codigo" value="" />                                     
                                </div>
                            </div>                            
                        </td></tr>
                        </table>    
                    </form>
                    </fieldset>
                    <FIELDSET>
                    <LEGEND>
                            <label class="Titulos2">Datos del Asiento Contable</label>
                    </LEGEND>               
                    <div id="compGrilla" style="padding-top: 6px;">   
                                        <table id="comp"></table>
                                        <div id="compPager"></div>
                    </div>
                    <script type="text/javascript">
                        function buscaProvee(){ 
                            $('#cod_pvr').val('');
                             var array={'search':$('#lblProvee2').val(),'op_opciones':'c'};                           
                             $.SearchOrDialogArray("#provDialog",function (data){$("#cod_pvr").val(data['Prv_Cod']);$("#lblProvee2").val(data['proveedor']);}
                             ,array);
                             $('#lblProvee2').val('');
                        } 
                        $(document).ready(function () {  
                        $('#Che_Fec').toggleClass('disabled').find('input').toggleAttr('disabled');
                        var gridComp=$("#comp");
                        gridComp.jqGrid({
                                url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },                             
                                autowidth : true, shrinkToFit: true, height: 120,
                                cmTemplate: {sortable:false},
                                colModel: [
                                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center", hidden:true },  
                                    { label: 'Tipo', name: 'Det_Tip', hidden:true },
                                    { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                                    { label: 'Cuenta', name: 'Pld_Des', width: 150  },
                                    { label: 'Glosa', name: 'Glosa', width: 150,editable:true },
                                    { label: 'Debe', name: 'Debe', width: 50, align: 'right', formatter:'currency',
                                         formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: <b>{0}<b/>", summaryType: "sum",
                                         editoptions: {
                                            dataInit: function (element) {$("#comp").createInputDiario(element,"D","Det_Tip");}
                                        },editable:true 
                                    },                                         
                                    { label: 'Haber', name: 'Haber', width: 50,align: 'right', formatter:'currency', 
                                         formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum",
                                         editoptions: {
                                            dataInit: function (element) {$("#comp").createInputDiario(element,"H","Det_Tip");}
                                        },editable:true 
                                    },
                                        { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                            formatter:function (cellvalue, options, rowObject) { 
                                                if(rowObject.Pld_Cod!==$("#periodos").val())
                                                return  '<span class="btn btn-danger btn-mini" title="Eliminar" onclick="$(\'#comp\').jqGrid(\'delRowData\',\''+rowObject.Pld_Cod+'\');$(\'#comp\').updateGridDiario();"><i class="icon-remove icon-white"></i></span>'; 
                                               else return "";
                                            }
                                        }
                                ],
                                //caption:"Cuentas",hidegrid: false,     
                                footerrow: true, userDataOnFooter: false,// set a footer row                             
                                rowNum: 10000000, pager: "#compPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null
                            });                        
                            gridComp.navGrid('#compPager',{ edit: false, add: false, del: false, search: false, refresh: false, view: false, position: "left", cloneToTop: false });
                            gridComp.jqGrid('bindKeys');                                               
                            $.clearFooterDiario("#comp");

                                $( "#apellido" ).autocomplete({
                                    minLength: 4,source: "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",
                                    select: function( event, ui ) {
                                      $('#Benediv').addClass('disabled').find('input').attr('readOnly','readOnly');$('#Com_Val_Egre').focus();
                                      $( "#apellido" ).val( ui.item.Prs_Ape );
                                      $( "#nombre" ).val( ui.item.Prs_Nom );
                                      $( "#Bene_Id" ).val( ui.item.Prv_Cod );
                                      return false;
                                    }
                                  }).autocomplete( "instance" )._renderItem = function( ul, item ) {return $( "<li>" ).append( "<a>"+item.Prs_Ape+' '+item.Prs_Nom+"</a>" ).appendTo( ul );};
                                  $('#comp1').hide();
                                  $('#comp2').hide();
                                  $("#rangeDates").toggleClass("disabled").find("input").toggleAttr('disabled'); 
                                  setPeriodo();
                        });  
                    </script>
                    </FIELDSET>
                    <div style="padding: 10px;">
                        <button onclick="$('#comp2').prev('#comp1');" class="btn btn-inverse fileinput-button" title="Volver Atrás"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button><span style="width: 150px;"></span>
                        <button onclick="$('#formComp').formSubmit();" type="button" class="btn btn-primary start" title="Guardar el Documento"><i class="icon-book icon-white"></i><span>Guardar</span></button><span style="width: 15px;"></span>
                        <button onclick="$('#cuenDialog').dialog('open');" type="button" class="btn btn-success fileinput-button" title="Agregar Cuentas"><i class="icon-list-alt icon-white"></i><span>Agregar</span></button>
                    </div>
                </div>
        </td>
      </tr>    
    </table>
</div>
    
<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="Búsqueda de Proveedores">  
       <form> 
        <fieldset>
		<legend><label class="Titulos2">Búsqueda de Proveedor</label></legend>
		<table border="0">
                   <tr>
			  <td width="205"><input name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" />
			  <span class="LetraNegra"><strong>Apellido</strong></span></td>
			  <td width="266"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
				<span class="LetraNegra"><strong>Cédula/R.U.C.</strong></span></td>
                    </tr>
                </table>
                <table height="36" border="0" cellpadding="0" cellspacing="0">                    
                      <tr>
                          <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese proveedor a buscar..." autofocus /><input type="text" style="display:none"/></td>
                          <td width="109" align="center">
                              <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar cuenta" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button></td>
                      </tr>                    
                </table>
        </fieldset>  
       </form>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {               
                $.createSearchDialog('#provDialog',[
                        { label: 'Cód.Int.', name: 'Prv_Cod', key: true,hidden:true,viewable: true },                                
                        { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                        { label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                        { label: 'Dirección', name: 'Prs_Dir',hidden:true,viewable: true },                      
                            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                formatter:function (cellvalue, options, rowObject) { 
                                    var clic='selectProvee($("#provGrid").jqGrid("getRowData",'+rowObject.Prv_Cod+'))';
                                    return  '<span class="btn btn-info btn-mini" title="Seleccionar" onclick=\''+clic+'\'><i class="icon-info-sign icon-white"></span>'; 
                                }
                            }
                    ]);  
                                     
        }); 
    </script>
<!-- FIN DEL DIALOGO PROVEEDOR-->
<script type="text/ecmascript" src="../../Librerias/scripts/generales/jqgrid.ExcelExport.js"></script>
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
                            <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar Cuenta" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button></td>
                      </tr>
                        </tbody>
                    </table>                                                  
                    </FIELDSET>              
            </form>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {               
              // DIALOG BUSCAR CUENTAS
             $.createSearchDialog('cuenDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
                    { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                    { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                    { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                    { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
                    { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                                return  '<span class="btn btn-success btn-mini" title="Enviar al D&eacute;bito" onclick="SelectCta(\''+rowObject.Pld_Cod+'\',\'D\');"><b>D</b></span>&nbsp;'
                                        +'<span class="btn btn-success btn-mini" title="Enviar al Cr&eacute;dito" onclick="SelectCta(\''+rowObject.Pld_Cod+'\',\'H\');"><b>H</b></span>'; 
                            }
                        }
                ]);                     
        }); 
    </script>
<!-- FIN DEL DIALOGO CUENTAS-->
<div id="output"></div>
</BODY>
</HTML>