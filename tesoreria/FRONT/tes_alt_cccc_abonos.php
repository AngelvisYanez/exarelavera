<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cccc.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Cccc;
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
if(isset($cuenAjax)){ 
    $contar = $obBD_con1->getRowConsulta(12, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(12, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($periAjax)){ 
    $response['Pec'] = $obBD_con1->getRowConsulta(5,$Ses_Emp_Cod.'*'.$Pec_Cod, $obBD_conexion);$response['success']=true; 
    utf8_encode_deep($response);echo json_encode($response);exit();
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
			$concat= " AND Cpc_Ven BETWEEN '".$hoy."' AND '".$Fec_Fin."'";
		break;
		/*******/
		case 3: 
			/*Incrmento fecha 60 dias para consultar facturas vencidas en 60 dias*/
			$val=60;
			$Fec_Fin=fechas_futuras($hoy,60);
			$concat=" AND Cpc_Ven BETWEEN '".$hoy."' AND '".$Fec_Fin."'";
		break;
		/*******/
		case 4: 
			/*Incrmento fecha 90 dias para consultar facturas vencidas en 90 dias*/
			$Fec_Fin=fechas_futuras($hoy,90);
			$concat=" AND Cpc_Ven > '".$hoy."'";
		break;
		/*******/
		case 5: 
			/*Decremento de fecha a 30 dias para consultar facturas vencidas */
			$Fec_Fin=fechas_futuras($hoy,-30);
			$concat=" AND Cpc_Ven BETWEEN '".$Fec_Fin."' AND '".$hoy."'";
		break;
		/*******/
		case 6: 
			/*Decremento de fecha a 60 dias para consultar facturas vencidas */
			$Fec_Fin=fechas_futuras($hoy,-60);
			$concat=" AND Cpc_Ven BETWEEN '".$Fec_Fin."' AND '".$hoy."'";
		break;
		/*******/
		case 7: 
			/*Decremento de fecha a 90 dias para consultar facturas vencidas */
			$Fec_Fin=fechas_futuras($hoy,-90);
			$concat=" AND Cpc_Ven < '".$hoy."'";
		break;
	}
    if(!isset($txt_fec_ini )) $txt_fec_ini='';
    if(!isset($txt_fec_fin )) $txt_fec_fin='';
    $responce['rows'] = $obBD_con1->getArrayConsulta(13, $Ses_Emp_Cod.'*'.$Cli_Cod.'*'.$concat.'*'.$txt_fec_ini.'*'.$txt_fec_fin.'*', $obBD_conexion);	          
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
if(isset($detAjax)){ 
    $responce['success']=false;
    $responce['com'] = $obBD_con1->getRowConsulta(9, $Com, $obBD_conexion);
    $responce['pag'] = $obBD_con1->getRowConsulta(30, $Cpc.'*'.$Com, $obBD_conexion);
    $responce['asi']['rows'] = $obBD_con1->getArrayConsulta(10, $Com, $obBD_conexion);$responce['asi']['records']=count($responce['asi']['rows']);    
    $responce['che']['rows'] = $obBD_con1->getArrayConsulta(11, $Cpc.'*'.$Com, $obBD_conexion);$responce['che']['records']=count($responce['che']['rows']);
    utf8_encode_deep($responce);$responce['success']=true;
    echo json_encode($responce);exit();
}
if(isset($save)){
    if(date("H:i:s")>'16:00:00'&&$Ses_Emp_Cod*1==1&&$hoy==$Com_Fec){
        $nuevafecha = strtotime ( '+'.(date("w")!=5?1:3).' day' , strtotime ( $hoy ) ) ;
        $Com_Fec = date ( 'Y-m-d' , $nuevafecha );   
    }
    if(!isset($Che_Fec)) $Che_Fec =$Com_Fec;
    if ($op=="I")$var="D";else $var='H'; 
    if(!isset($Num_Doc)||!isset($Pld_Cod)){ $Num_Doc=''; $Pld_Cod=''; $rs_buscar=array(); } 
    else $rs_buscar =  $obBD_con1->getArrayConsulta(20, $Num_Doc."*".$Ses_Emp_Cod.'*'.$Pld_Cod.'*'.$var, $obBD_conexion);
    if((count($rs_buscar)==0 || $op=='E') || $Num_Doc==''){
		$responce['cheque']="";
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
                $Num_Doc2=$Num_Doc;
                if ($op=="E"){$Num_Doc2='';}
		if ($op=="I") { $tabla="cliente"; $campo="Cli_Cod"; } if ($op=="E" || $op=="D") { $tabla="proveedore"; $campo="Prv_Cod"; }
		$obBD_con1->grabarv_registros(sentencias_cccc(21,$obBD_con1->parametros($Pec_Cod.'*'.$Codigo.'*'.$Com_Num.'*'.$Com_Fec.'*'.$Com_Con.'*'.$Tia_Cod
											.'*'.$Com_Val.'*'.$Com_Obs.'*'.$op.'*'.$campo.'*'.$Num_Doc2)),$obBD_conexion->conexion);
		$ultimo = $obBD_con1->insercionid ($obBD_conexion->conexion);
		/** 
		* Recorre el arreglo de los datos de las cuentas seleccionadas 
		*/
                foreach ($save as $row)
                {
                    if($row['Det_Tip']=='D') {$valor=$row['Debe'];}
                    else {$valor=$row['Haber'];}
                    $obBD_con1->grabarv_registros(sentencias_cccc(22,$obBD_con1->parametros($ultimo.'*'.$row['Det_Tip'].'*'.$valor.'*'.$row['Pld_Des'].'*'.$row['Glosa'].'*'.
											$row['Pld_Cod'])),$obBD_conexion->conexion);                   
                }
                if ($op=="I"){
                    $obBD_con1->grabarv_registros(sentencias_cccc(19,$obBD_con1->parametros($Cpc_Cod.'*'.$Pag_Cod.'*'.$ultimo.'*'.$Com_Fec.'*'.$Com_Val.'*'.$Com_Obs)),$obBD_conexion->conexion);
                    if($Pag_Cod*1==3){
                        $det_cccc_cod = $obBD_con1->insercionid ($obBD_conexion->conexion);
                        $obBD_con1->grabarv_registros(sentencias_cccc(35,$obBD_con1->parametros($Bak_Cod.'*'.$Codigo.'*'.$Che_Cta.'*'.$Che_Num.'*'.$Che_Fec.'*'.$Com_Val.'*'.$Com_Obs)),$obBD_conexion->conexion);
                        $che_cod = $obBD_con1->insercionid ($obBD_conexion->conexion);
                        $obBD_con1->grabarv_registros(sentencias_cccc(36,$obBD_con1->parametros($che_cod.'*'.$det_cccc_cod)),$obBD_conexion->conexion);
                    }
                }
		/**
		* Finaliza la transacci�n
		*/
                $responce['link']="../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo&tabla=$tabla&campo=$campo&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod";
                $responce['link_rec']="/tesoreria/FRONT/tes_pri_recibocobro_1.0.php?Com_Cod=$ultimo";
		$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
		if($obBD_con1->Error==0) $responce['success']=true;
		else $responce['success']=false;
                $responce['message']=$obBD_con1->MsgError;
         
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
                <!--<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />-->
                <style>

                </style>
	</HEAD>
<BODY>
<div id="set1">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table" style="table-layout:fixed;">
	<tr class="BarraTitulo">
            <td colspan="2" height="10">&raquo; Cobro de Abonos a Clientes </td>
        </tr>
        <tr>
            <td colspan="2" >
                <div id="comp1">
                 <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
                     <tr>
                        <td>
             <FIELDSET>
		<LEGEND>
                    <label class="Titulos2">Seleccionar Cliente</label>
		</LEGEND>
                    <form id="provFormTemp">
                       <div class="segmento">C�dula/R.U.C.:</div>                          
                       <div class="datasegmento">

                               <input id="docu" name="search" maxlength="13" onkeydown='if (event.keyCode === 13) $.SearchOrDialog("#provDialog",selectProvee);' type="text" class="search ui-corner-all" placeholder="Ingrese Cedula/R.U.C. ..."  autofocus />                               
                               <input type="text" name="op_opciones" value="c" style="display: none;" /> 
                               <input type="hidden" name="Cli_Cod" value="" />
                               <a onclick="$('#provDialog').dialog('open');/*$('#docu').removeAttr('readOnly');*/" title="Búsqueda de Clientes"  class="btn btn-success btn-mini"><i class=" icon-check icon-white" ></i></a>
                               <a onclick="selectProvee();" title="Quitar Cliente" class="btn btn-success btn-mini"><i class=" icon-eject icon-white"></i></a>

                       </div>
                    </form>
                    <div class="segmento">Cliente:</div><div  class="datasegmento"><input id="lblProv" type="text" class="label ui-widget-content ui-corner-all" readonly /></div><br />
                    <div class="segmento">Direcci�n:</div><div  class="datasegmento"><input id="lblDirec" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
             </FIELDSET>    
              
             
               
            </td>
            <td>
                <FIELDSET>
		<LEGEND>
                    <label class="Titulos2">Filtros</label>
		</LEGEND>
                  <form id="formCompTemp" action="javascript:$('#list').Search('#formCompTemp','ajaxComprobante');">
                    <input type="hidden" name="Cli_Cod" value="" />  
                    <div>
                        <div class="segmento">Todos: <input name="op_opciones" type="radio" value="T" checked alt="" onchange="setCombo('T');" /></div> 
                        <div class="segmento">Vencidos: <input name="op_opciones" type="radio" value="P" alt="" onchange="setCombo('P');" /></div>
                        <div class="segmento">Por Vencer: <input name="op_opciones" type="radio" value="I" alt="" onchange="setCombo('I');" /></div>
                    </div>
                    <div>
                        <div class="segmento">B�scar:
                          </div> 
                        <div class="datasegmento" style="text-align: left;" >
                            <select name="Cmb_Ven" id="Cmb_Ven" class="text medium ui-corner-all"  >                                
                                <option selected="selected" value="1">Todos</option>                               
                            </select>&nbsp;&nbsp;&nbsp;&nbsp;
                             <button type="button" onclick="this.form.submit()" class="btn btn-success" style="height: 27px;" title="Ejecutar Búsqueda" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button>
                        </div>
                    </div>
                    <div style="text-align: center;">
                       
                    </div>
                  </form>
             </FIELDSET> 
            </td>
        </tr>
      <tr>
          
          <td colspan="2" align="left" valign="top"> 
            <div id="grillaComp" style="padding:10px;">   
                                    <table id="list"></table>
                                    <div id="listPager"></div>
                </div>                        
                 <script type="text/javascript"> 
                        var tipo='lista';
                        var fact=0,glosa='',valor=0,bancos=new Array(),numChe=0;
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
                            if(typeof data==='undefined'){
                                $("#lblProv").val('');
                                $("#lblDirec").val('');
                                $("input[name='Cli_Cod']").val('');
                                $("#docu").val('');
                                $('#list').Search('#formCompTemp','ajaxComprobante');
                                return false;
                            }
                            if(tipo==='lista'){
                                $("#lblProv").val(data['cliente']);
                                $("#lblDirec").val(data['Prs_Dir']);
                                $("input[name='Cli_Cod']").val(data['Cli_Cod']);
                                $("#docu").val(data['Prs_Ced']);        
                                $("#provDialog").dialog("close");        
                                $('#list').Search('#formCompTemp','ajaxComprobante');
                                //$("#docu").attr("readOnly","readOnly");
                            }
                            if(tipo==='pago'){
                                $("#lblProvee2").val(data['cliente']);
                                $("#cod_pvr").val(data['Cli_Cod']);
                                $("#provDialog").dialog("close"); 
                            }
                        }
                        function clearFooter(){ 
                            public $footRow = $("#grillaComp .ui-jqgrid-sdiv .footrow");   

                            $footRow.find('>td[aria-describedby="list_subgrid"]').css("border-right-color", "transparent");                            
                            $footRow.find('>td[aria-describedby="list_Com_Codigo"]').css("border-right-color", "transparent");
                            $footRow.find('>td[aria-describedby="list_Caj_Fec"]').css("border-right-color", "transparent");
                            $footRow.find('>td[aria-describedby="list_Cpc_Ven"]').css("border-right-color", "transparent");
                            $footRow.find('>td[aria-describedby="list_Vet_Num"]').css("border-right-color", "transparent");
     
                            $footRow.find('>td[aria-describedby="list_subgrid"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Com_Codigo"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_vencimiento"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Caj_Fec"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Cpc_Ven"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Vet_Num"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_proveedor"]').css("background-color", "white");
                        }
                        function updateTotals(grid){ 
                            var abonos=0,saldos=0,rows= grid.jqGrid('getRowData');//alert( grid.jqGrid('getCol', 'Com_Val', false, 'sum'));
                            for(var i=0;i<rows.length;i++){abonos=abonos+parseFloat(rows[i]['Abono']);saldos=saldos+parseFloat(rows[i]['Saldo']);}
                            grid.jqGrid('footerData', 'set', { vencimiento:"<div style='text-align:right;'>TOTALES:</div>",Asi_Val: grid.jqGrid('getCol', 'Asi_Val', false, 'sum') });
                            grid.jqGrid('footerData', 'set', { Abono: ""+abonos });
                            grid.jqGrid('footerData', 'set', { Saldo: ""+saldos });                            
                                    
                        }
                    $(document).ready(function () {  
                        //$.createDateRange('#txt_fec_ini','#txt_fec_fin'); 
                        var compGrid=$("#list");
                        compGrid.jqGrid({
                            url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                            //postData: $("#form1").getData("ajaxGrid"),
                            autowidth : true, shrinkToFit: true, height: 270,
                            cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                            colModel: [                               
                                { label: 'Cod.Int.', name: 'Cpc_Cod', key: true, hidden:true,viewable:true },  
                                { label: 'Cod.Int.', name: 'Asi_Cod',  hidden:true },                                  
                                {label:'Pld_Cod.',name:"Pld_Cod",hidden:true},
                                {label:'Pld_Cdc.',name:"Pld_Cdc",hidden:true},
                                {label:'Pld_Des.',name:"Pld_Des",hidden:true},
                                { label: 'No. Compr.', name: 'Com_Codigo',align:"center", width: 25 },                      
                                { label: 'Fecha Emis.', name: 'Caj_Fec',align:"center", width: 30  },
                                { label: 'Fecha Venc.', name: 'Cpc_Ven', align:"center", width: 30},
                                { label: 'Vencimiento', name: 'vencimiento', align:"center", width: 25},
                                { label: 'Total', name: 'Asi_Val', width: 45, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" 
                                },
                                { label: 'Abono', name: 'Abono', width: 45, align: 'right', decimalPlaces: '2', summaryRound: 2,formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},
                                        formatter: function (cellValue, options, rowObject) { if(!parseFloat(rowObject.Abono)) rowObject.Abono=0;return $.fn.fmatter.call(this, "currency",rowObject.Abono, options);},
                                        unformat: function (cellValue, options, cell) {var opt = $.extend(true, {}, options);opt.colModel.formatter = "currency";delete opt.colModel.unformat;return $.unformat.call(this, cell, opt);},
                                        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
                                }, 
                                { label: 'Saldo', name: 'Saldo', width: 45, align: 'right',  decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {prefix:'$ ',  thousandsSeparator:',',decimalSeparator:'.'},
                                        formatter: function (cellValue, options, rowObject) {
                                            if(!parseFloat(rowObject.Saldo)){
                                            rowObject.Saldo = parseFloat(rowObject.Asi_Val) - parseFloat(rowObject.Abono);
                                            }
                                            if(parseFloat(rowObject.Abono)===parseFloat(rowObject.Asi_Val)) return 'Pagado'; else
                                            return $.fn.fmatter.call(this, "currency", rowObject.Saldo, options);
                                        },
                                        unformat: function (cellValue, options, cell) {var opt = $.extend(true, {}, options);opt.colModel.formatter = "currency";delete opt.colModel.unformat;return $.unformat.call(this, cell, opt);},
                                        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
                                }, 
                                { label: 'No. Docum.', name: 'Vet_Num', width: 55, align:"center"},
                                {label:'Cli_Cod.',name:"Cli_Cod",hidden:true},
                                {label:'Prs_Ape.',name:"Prs_Ape",hidden:true},
                                {label:'Prs_Nom.',name:"Prs_Nom",hidden:true},
                                { label: 'Cliente', name: 'proveedor', width: 75},
                                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                        formatter:function (cellvalue, options, rowObject) {   
                                            var selectBut='<span class="btn btn-success btn-mini" title="Seleccionar" type="button" onclick="SelectFact($(\'#list\').jqGrid (\'getRowData\', \''+rowObject.Cpc_Cod+'\'))"><i class="icon-arrow-right icon-white"></i></span>';
                                            return  '<span class="btn btn-info btn-mini" title="Ver" type="button" onclick="$(\'#list\').viewGridRow(\''+rowObject.Cpc_Cod+'\');"><i class="icon-info-sign icon-white"></i></span><span>&nbsp;&nbsp;</span>'+
                                                     selectBut; 
                                        }
                                    }
                                 
                            ],                                
                            rowNum: 10000000, pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,
                            footerrow: true, userDataOnFooter: false,
                            onSelectRow: function(rowid, e) { compGrid.resetSelection();},
                            loadComplete: function (data) { 
                                var total = data.records;
                                    for(var i=0;i<total;i++){       
                                        if(data.rows[i]['vencimiento'] ==='Vencido')
                                            $("#"+data.rows[i].Cpc_Cod).css("background", "#FADDDD");
                                        if(data.rows[i]['vencimiento'] ==='Pagado')
                                            $("#"+data.rows[i].Cpc_Cod).css("background", "#DDFAE2");
                                       
                                    }
                                updateTotals($(this));     
                            },                            
                            subGridOptions: {
                                "plusicon"  : "ui-icon-triangle-1-e","minusicon" : "ui-icon-triangle-1-s","openicon"  : "ui-icon-arrowreturn-1-e","reloadOnExpand" : false,"selectOnExpand" : true
                            },subGrid: true,multiselect: false,
                            subGridRowExpanded: function(subgrid_id, row_id) {
                                var subgrid_table_id = subgrid_id+"_t";         
                                $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                                $("#"+subgrid_table_id).jqGrid({
                                        url:"<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?ajaxSubgrid="+row_id,datatype: "json",regional : 'es',
                                        autowidth : true, shrinkToFit: true,cmTemplate: {sortable:false},//colNames: ['No','Item','Qty','Unit','Line Total'],
                                        colModel: [
                                                {label:'Cod.Int.',name:"Cpc_Cod",width:80,key:true,align:"center",hidden:true},
                                                {label:'Cod.Int.',name:"Com_Cod",width:80,key:true,align:"center",hidden:true},
                                                {label:'No. Compr.',name:"Com_Codigo",width:45,align:"center"},
                                                {label:'Fecha',name:"Cpc_Fec",width:45,align:"center"},
                                                {label:'Valor',name:"Cpc_Val",width:45, align: 'right', formatter:'currency', decimalPlaces: '2', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}},
                                                {label:'Observación',name:"Cpc_Obs",width:100},
                                                {label:'Tipo',name:"Pag_Des",width:50,align:"center"},                      
                                                    { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                                        formatter:function (cellvalue, options, rowObject) { 
                                                            var clic='selectDetalle('+rowObject.Cpc_Cod+','+rowObject.Com_Cod+');';
                                                            return  '<span class="btn btn-info btn-mini" title="Detalle" onclick=\''+clic+'\'><i class="icon-info-sign icon-white"></span>'; 
                                                        }
                                                    }
                                        ],beforeSelectRow: function(rowid, e) {return false;},
                                        rowNum:10000000, pager: "",height: '100%'
                                });                                
                            }
                        });                        
                        compGrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: false, position: "left", cloneToTop: false })
                            .jqGrid('navButtonAdd',"#listPager",{ caption: "Exportar &nbsp;",buttonicon: "ui-icon-arrowthickstop-1-s",title:"Exportar Excel",
                                onClickButton: function() {
                                    compGrid.jqGrid('exportGridExcel',{nombre:"PorPagarABONOS",hoja:"CCCC"});	
                                },position: "last"
                            });
                        compGrid.jqGrid('bindKeys');
                        clearFooter();    
                        //$('#rangeDates').addClass('disabled').find('input').attr('disabled','disabled');
                       //loadBancos();
                    });  
                    function loadBancos(){ 
                        $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{periAjax:true,Pec_Cod:$('#periodos').val()}, function( response ) {
                                if(response['success']===true){
                                    $('input[name="Pec_Cod"]').val(response['Pec']['Pec_Cod']);                                    
                                    $('input[name="periodo"]').val(response['Pec']['Periodo']);
                                    $("input[name='Com_Fec']").dateLimits(response['Pec']["Pec_Fei"],response['Pec']["Pec_Fef"]);                                    
                                }else{$.alert(response['message']);}                                   
                             },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
                    }
                    function SelectFact(data){ 
                            fact=data;
                            resetComp();                           
                            $('#comp1').next('#comp2');
                    }
                    function resetPago(Pag_Cod){                         
                        if(Pag_Cod!==null&&(Pag_Cod*1)===3) {
                            $('#Che_Fec').removeAttr('disabled');
                            $('#Bak_Cod').removeAttr('disabled');
                            $('#Che_Num').removeAttr('disabled');
                            $('#Che_Cta').removeAttr('disabled');
                        }else{
                            $('#Che_Fec').attr('disabled','disabled');
                            $('#Bak_Cod').attr('disabled','disabled');
                            $('#Che_Num').attr('disabled','disabled');
                            $('#Che_Cta').attr('disabled','disabled');
                        }
                            
                        addBanco();
                    }
                    function resetComp(){ 
                        $("#comp").clearGrid();                        
                        glosa='Abono Fact. '+fact['Vet_Num'];
                        valor=fact['Saldo'];
                        $('#Com_Val_Egre').val(valor);
                        $('#Com_Sal_Egre').val('$ 0.00');
                        $('#ConEgreso').val(glosa);       
                        $('#ObsEgreso').val(fact['Vet_Num']); 
                        $('#Cpc_Cod').val(fact['Cpc_Cod']);
                        $('#lblProvee').val(fact['proveedor']);
                        $('#cod_pvr').val(fact['Cli_Cod']);
                        $('#apellido').val(fact['Prs_Ape']);
                        $('#nombre').val(fact['Prs_Nom']);                       
                        $('#lblMsg').html('Maximo $ '+fact['Saldo']);  
                        resetPago();
                    }
                    function addBanco(){ 
                        $("#comp").clearGrid(); 
                        if(fact!==0)
                            addFilaCuenta(fact,'H');
                    }
                    function SelectCta(id,tipo){                        
                        if(!$("#comp").existsId(id)){
                            addFilaCuenta($.getDialogGrid("#cuenDialog").jqGrid('getRowData', id),tipo);                           
                        }
                    }  
                    function addFilaCuenta(cuenta,tipo){                         
                        cuenta['Glosa']=glosa;
                        cuenta['Debe']=cuenta['Haber']=0;                        
                        cuenta['Det_Tip']=tipo; 
                        if(tipo==='D')
                        cuenta['Debe']=valor;   
                        if(tipo==='H')
                        cuenta['Haber']=valor;   
                        var grid=$("#comp");
                        grid.jqGrid("addRowData", cuenta["Pld_Cod"], cuenta, "last");        
                        grid.startGridEdit();
                        $("#comp").updateGridDiario();
                    }
                    $.varValid=function(v){return (v!==null && typeof v!=='undefined');};
                    $.round=function(val,dec){ dec=$.varValid(dec)?dec:2; if(!$.varValid(val)||isNaN(val)||isNaN(dec)) return null; return (Math.round(val*Math.pow(10,dec))/Math.pow(10,dec)); };
                    $.toFixed=function(val,dec){ dec=$.varValid(dec)?dec:2; var n=$.round(val,dec); return ($.varValid(n)?n.toFixed(dec):null); };
                    function updateValores(){                        
                        valor=$.round($("#Com_Val_Egre").val()); 
                        if((valor)>$.round(fact['Saldo'])){
                            valor=$.round(fact['Saldo']);
                            $.alert('El valor no puede ser mayor que $ '+fact['Saldo']);
                        }                        
                        $("#Com_Sal_Egre").val('$ '+$.toFixed(fact['Saldo']-valor));
                        valor=$.toFixed(valor);
                        console.log(valor);
                        $("#Com_Val_Egre").val(valor);
                        $("input[name='Haber']").val(valor);
                        $("input[name='Debe']").val(valor);
                        $("#comp").updateGridDiario();
                    }
                    function updateGlosa(){                        
                        glosa=$("#ConEgreso").val();                        
                        $("input[name='Glosa']").val(glosa);
                    }
                    function saveComp(){
                        var gridComp = $("#comp");
                        var batch = gridComp.getGridBatch();
                        gridComp.trigger('reloadGrid');
                        var     tot=$.round($("#Com_Val_Egre").val()),
                                deb = $.round(gridComp.jqGrid("getCol", "Debe", false, "sum")),
                                hab = $.round(gridComp.jqGrid("getCol", "Haber", false, "sum"));     
                        //alert(tot); alert(deb); alert(hab);
                        if(deb===tot&&hab===tot){                            
                               if($('#cod_pvr').val()!==''){                                    
                                    if(batch.length>0){ 
                                        var data=$('#formComp').serializeObject();
                                        data["save"]=batch;
                                        $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function( response ) {
                                            if(response['success']===true){
                                                $('#impCompr').attr('href',response['link']); 
                                                $('#impRecib').attr('href',response['link_rec']);
                                                $('#successDialog').dialog('open');
                                                $('#list').trigger('reloadGrid');
                                                $('#comp2').prev('#comp1');
                                            }else{$.alert(response['message']);}
                                         },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
                                    }
                                }else{$.alert("Seleccione El Cliente");}                                                
                        }else{$.alert("Los Totales no Coinciden!");}
                        gridComp.startGridEdit();
                    }
               </script>	
          </td>
      </tr>
        </table>
                </div>               
                <div id="comp2">
<!-- FORMULARIO COMPROBANTE DE INGRESO -->
            <div id="tabs-2">
                <fieldset>
                    <legend>
                        <label class="Titulos2">Comprobante de Ingreso</label>
                    </legend>	
                    <form name="formComp" id="formComp" method="post" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?',null,saveComp)">
                        <table  width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;min-width: 450px;">
                        <tr><td valign="top">
                            <div class="row">
                                <div class="segmento">Periodo Contable:</div>
                                <div  class="datasegmento">
                                     <select name="Pec_Cod" id="periodos" onchange="loadBancos();" class="text medium ui-corner-all" required>   
                                         <option value="">Seleccione Periodo ....</option>
                                        <?Php 
                                        $rs_periodos = $obBD_con1->getArrayConsulta(5,$Ses_Emp_Cod, $obBD_conexion);                               
                                        if (count($rs_periodos) > 0)
                                        {
                                                foreach($rs_periodos as $row){
                                                ?>
                                                        <option value="<?Php echo $row['Pec_Cod']; ?>"><?Php echo $row['Periodo']; ?></option>	
                                                <?php		
                                                }//while($row_rs_periodos = $obBD_con1->fetch_assoc($rs_periodos));
                                        }//Fin del if ($total_rs_periodo > 0)                                
                                        ?>	                               
                                    </select>                                   
                                </div>
                            </div>  
                            <div class="row">
                                <div class="segmento required">Tipo Comprobante:</div>
                                <div  class="datasegmento">
                                    <select class="text ui-corner-all"  name="Tia_Cod"  class="isSelectMenu" required>
                                        <option value="">Seleccione...</option>
                                            <?Php 
                                            $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(43, "ALL", $obBD_conexion);
                                            foreach ($row_rs_tipo_asien2 as $row) 
                                            { ?>
                                        <option value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des'] ?> </option>
                                            <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Cliente:</div>
                                <div  class="datasegmento"><input readOnly id="lblProvee" onkeydown='if (event.keyCode === 13) buscaProveeIngreso();' onchange="if($('#lblProvee').val()==='')$('#cod_pvr').val('');" class="search clearable ui-corner-all" placeholder="Ingrese Cliente"  />
                                <a style="display:none;" onclick="$('#provDialog').dialog('open')" title="B&uacute;squeda de Proveedores" class="btn btn-success btn-mini"><i class=" icon-check icon-white"></i></a></div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Concepto:</div>
                                <div  class="datasegmento"><textarea class="text ui-corner-all" name="Com_Con" id="ConEgreso" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                            </div>
                            <div class="row">
                                <div class="segmento">Observaci&oacute;n:</div>
                                <div  class="datasegmento"><textarea class="text ui-corner-all" id="ObsEgreso" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                            </div>
                            </td><td valign="top">
                            <div class="row">
                                <div class="segmento required">Fecha Comprobante:</div>
                                <div  class="datasegmento"><input id="confec" name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" onchange="$('#chefec').val($('#confec').val())" class="text small ui-corner-all" required /></div>
                            </div>
                             <div class="row">
                                <div class="segmento required">Seleccione Pago:</div>
                                <div  class="datasegmento">
                                     <select name="Pag_Cod" id="pagos" onchange="resetPago(this.value);" class="text small ui-corner-all" required >                              
                                           <option value="">Seleccione...</option>
                                            <?Php 
                                            $row_rs_tipo = $obBD_con1->getArrayConsulta(16, "ALL", $obBD_conexion);
                                            foreach ($row_rs_tipo as $row) 
                                            { ?>
                                            <option value="<?php echo $row['Pag_Cod']; ?>"><?php echo $row['Pag_Des'] ?> </option>
                                            <?php } ?>           
                                    </select>
                                    <label>&nbsp;&nbsp;&nbsp;&nbsp;<span  class="required">No.:</span></label>
                                    <input id="Che_Num" name="Che_Num" type="text" class="text small ui-corner-all" required />
                                </div>
                            </div>  
                            <div class="row">
                                <div class="segmento required">Fecha Cheque:</div>
                                <div  class="datasegmento"><input id="Che_Fec" name="Che_Fec" type="text" style="text-align: center" size="10" maxlength="10" onchange="$('#chefec').val($('#confec').val())" class="text small ui-corner-all" required />
                                    
                                </div>
                            </div>   
                            <div class="row">
                                <div class="segmento required">Banco:</div>
                                <div  class="datasegmento">
                                    <select name="Bak_Cod" id="Bak_Cod" class="text small ui-corner-all" required>   
                                         <option value="">Seleccione Banco ....</option>
                                        <?Php 
                                        $rs_bancos = $obBD_con1->getArrayConsulta(37,$Ses_Emp_Cod, $obBD_conexion);                               
                                        if (count($rs_bancos) > 0)
                                        {
                                                foreach($rs_bancos as $row){
                                                ?>
                                                        <option value="<?Php echo $row['Bak_Cod']; ?>"><?Php echo $row['Bak_Des']; ?></option>	
                                                <?php		
                                                }//while($row_rs_periodos = $obBD_con1->fetch_assoc($rs_periodos));
                                        }//Fin del if ($total_rs_periodo > 0)                                
                                        ?>	                               
                                    </select> 
                                    <label>&nbsp;&nbsp;&nbsp;&nbsp;Cta:</label>
                                    <input id="Che_Cta" name="Che_Cta" type="text" class="text small ui-corner-all"  />
                                </div>
                                
                            </div>  

                            <div class="row">
                                <div class="segmento required">Valor:</div>
                                <div  class="datasegmento"><input  class="money text small ui-corner-all" name="Com_Val" id="Com_Val_Egre" onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required /><img src="../../mascaras/model1/imagenes/32x32/info.gif" class="imgMsg" /><label id="lblMsg" class="lblMsg" style="color:blue;"></label></div>
                            </div>                           
                            <div class="row">
                                <div class="segmento required">Saldo:</div>
                                <div  class="datasegmento"><input  class="money text small ui-corner-all" id="Com_Sal_Egre" readOnly type="text" size="10" maxlength="12" style="text-align:right" value="$ 0.00" /></div>
                            </div>                      
                        </td></tr>
                        </table> 
                                    <input type="hidden" name="op" value="I" />                                    
                                    <input type="hidden" name="Cpc_Cod" id="Cpc_Cod" />                                                                    
                                    <input type="hidden" id="cod_pvr" name="Codigo" value="" />  
                    </form>
                    </fieldset>
            </div>
                <div id="comprobante" style="width: 100%;padding-top: 10px;">
                   <FIELDSET>
                    <LEGEND>
                            <label class="Titulos2">Datos del Asiento Contable</label>
                    </LEGEND>               
                    <div id="compGrilla" style="padding-top: 6px;">   
                                        <table id="comp"></table>
                                        <div id="compPager"></div>
                    </div>
                </div>
                <div colspan="2" style="padding:10px;">
                  <button onclick="$('#comp2').prev('#comp1');" class="btn btn-inverse fileinput-button" title="Volver Atrás"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button><span style="width: 150px;"></span>
                  <button type="button" class="btn btn-primary start" onclick="$('#formComp').formSubmit();" title="Gestionar el Pago Seleccionado"> <i class="icon-book icon-white"></i> <span>Cobrar</span></button>
                  <button onclick="$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success fileinput-button"><i class="icon-list-alt icon-white"></i><span>Agregar</span></button>
              </div>
                <script>
                $(document).ready(function () {  
                        $('#Che_Fec').toggleClass('disabled').find('input').toggleAttr('disabled');
                        var gridComp=$("#comp");
                        gridComp.jqGrid({
                            url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },                             
                            autowidth : true, shrinkToFit: true, height: 120,
                            cmTemplate: {sortable:false,title: false},
                            colModel: [
                                { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center", hidden:true },  
                                { label: 'Tipo', name: 'Det_Tip', hidden:true },
                                { label: 'Código', name: 'Pld_Cdc', width: 45 },                      
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
                         $('#comp2').hide();
                    });  
                </script>  
<!-- FIN FORMULARIO COMPROBANTE DE EGRESO -->
                </div>
        </td>
      </tr>    
    </table>
</div>
    
<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="Búsqueda de Clientes">  
       <form> 
        <fieldset>
		<legend><label class="Titulos2">Búsqueda de Clientes</label></legend>
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
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cliente a buscar..." autofocus /><input type="text" style="display:none"/></td>
                          <td width="109" align="center">
                              <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar Cliente" >
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
                        { label: 'Cód.Int.', name: 'Cli_Cod', key: true,hidden:true,viewable: true },                                
                        { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                        { label: 'Cliente', name: 'cliente', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                        { label: 'Dirección', name: 'Prs_Dir',hidden:true,viewable: true },                      
                            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                formatter:function (cellvalue, options, rowObject) { 
                                    var clic='selectProvee($("#provGrid").jqGrid("getRowData",'+rowObject.Cli_Cod+'))';
                                    return  '<span class="btn btn-success btn-mini" title="Seleccionar" onclick=\''+clic+'\'><i class="icon-arrow-right icon-white"></span>'; 
                                }
                            }
                    ]);  
                                     
        }); 
    </script>
<!-- FIN DEL DIALOGO PROVEEDOR-->
<script type="text/ecmascript" src="../../Librerias/scripts/generales/jqgrid.ExcelExport.js"></script>


<!--INICIO DEL DIALOGO DETALLE PAGO --> 
    <div id="pagoDialog" title="Detalle Pago">  
       
        <div>
            <div style="width: 50%;display: inline;float:left;">
                <fieldset>
                    <legend><label class="Titulos2">Datos Comprobante</label></legend>
                        <div class="row">
                            <div class="segmento">Compr. No.:</div><div  class="datasegmento"><input id="lblComp2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Fecha:</div><div  class="datasegmento"><input id="lblComFe2" type="text" class="label medium ui-widget-content ui-corner-all" style="text-align: center;" readonly /></div>
                        </div>                        
                        <div class="row">
                            <div class="segmento">Valor:</div><div  class="datasegmento"><input id="lblComVal2" type="text" class="text medium ui-widget-content ui-corner-all" style="text-align: right;" readonly /></div>
                        </div>
                </fieldset> 
            </div>             
            <div style="width: 50%;display: inline;float:right;">
                <fieldset>
                    <legend><label class="Titulos2">Datos del Cliente</label></legend>
                        <div class="row">
                            <div class="segmento">C�dula:</div><div  class="datasegmento"><input id="lblCed2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Cliente:</div><div  class="datasegmento"><input id="lblProv2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Direcci�n:</div><div  class="datasegmento"><input id="lblDirec2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                </fieldset> 
            </div>
            <div class="row" style="padding-top: 5px;padding-bottom: 15px;">
                 <fieldset>
                    <legend><label class="Titulos2">Observaci�n</label></legend>
                    <div  class="datasegmento" style="width:95%;"><input id="lblConce2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                 </fieldset>            
            </div>
        </div>        
        <div id="tabs">
            <ul>
              <li><a href="#detalleComp">Detalle Pago</a></li>
              <li><a href="#asienComp">Asiento Contable</a></li> 
              <li><a href="#chequeComp">Cheques</a></li>
            </ul>
                  <div id="asienComp" class="condensed">           
                          <table id="asiento"></table>
                          <div id="asientoPager"></div>           
                  </div> 
                  <div id="chequeComp" class="condensed">           
                          <table id="cheque"></table>
                          <div id="chequePager"></div>           
                  </div>
                  <div id="detalleComp" style="clear: both;">
                        <div class="row">
                            <div class="segmento">Factura:</div><div  class="datasegmento"><input id="lblFac2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Vencimiento:</div><div  class="datasegmento"><input id="lblVen2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Observac�n:</div><div  class="datasegmento"><input id="lblObsV2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Tipo Pago:</div><div  class="datasegmento"><input id="lblTipPa2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Fecha Pago:</div><div  class="datasegmento"><input id="lblFePa2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Valor Pago:</div><div  class="datasegmento"><input id="lblVaPa2" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="clear"></div> 
                  </div>  
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
                $.createDialog('#pagoDialog',415,650); 
                $( "#tabs" ).tabs();
                $.createDatePickers("input[name='Com_Fec']");
                $.createDatePickers("input[name='Che_Fec']");
                $('#asiento').jqGrid({
                    datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },                             
                    width:618,height:75,postData: {CheListAjax:true},caption:'Asiento Contable',
                    cmTemplate: {sortable:false},colModel: [
                        { label: 'Cód.Int.', name: 'Asi_Cod', key: true, width: 15,align:"center", hidden:true },  
                                { label: 'Tipo', name: 'Asi_Deh', hidden:true },
                                { label: 'Código', name: 'Pld_Cdc', width: 45 },                      
                                { label: 'Cuenta', name: 'Pld_Des', width: 130  },
                                { label: 'Glosa', name: 'Glosa', width: 130},
                                { label: 'Debe', name: 'Debe', width: 65, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"},                                         
                                { label: 'Haber',name: 'Haber',width: 65,align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"}
                    ],
                    loadComplete: function (data) { $(this).jqGrid('footerData', 'set', { Glosa:"<div style='text-align:right;'>TOTALES:</div>",Debe: $(this).jqGrid('getCol', 'Debe', true, 'sum'),Haber: $(this).jqGrid('getCol', 'Haber', true, 'sum') },true); },
                    rowNum: 10000, gridview: true, viewrecords: true,footerrow: true, userDataOnFooter: false
                }); $.clearFooterDiario("#asiento");  
                $('#cheque').jqGrid({
                    datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },                             
                    width:618,height:97,postData: {CheListAjax:true},caption:'Cheques Recibidos',
                    cmTemplate: {sortable:false},colModel: [
                        { label: 'Cód.Int.', name: 'Che_Cod', key: true,hidden:true,viewable: true },                                
                        { label: 'Fecha', name: 'Che_Fec', key: true, width: 50,align:"center" },
                        { label: 'Num.', name: 'Che_Num', key: true, width: 30,align:"center" },                        
                        { label: 'Banco', name: 'Bak_Des', width: 100,title:'Cuenta Bancaria' },
                        { label: 'No. Cuenta', name: 'Che_Cta', width: 90 },
                        { label: 'Valor', name: 'Che_Val', key: true, width: 60,align:"right" , formatter:'currency', decimalPlaces: '2', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}}
                    ],                                                                                       
                    rowNum: 10000, gridview: true, viewrecords: true
                }); 
                 $.createDialog('#successDialog',150,550);                 
        }); 
        function selectDetalle(Cpc,Com){                             
                           
                                $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{detAjax:true,Cpc:Cpc,Com:Com}, function( response ) {
                                   if(response['success']===true){                                       
                                        $("#lblComp2").val(response['com']['Com_Num']);
                                        $("#lblComFe2").val(response['com']['Com_Fec']);
                                        $("#lblComVal2").val(response['com']['Com_Val']);
                                        $("#lblConce2").val(response['com']['Com_Obs']);
                                        $("#lblCed2").val(response['com']['Prs_Ced']);
                                        $("#lblProv2").val(response['com']['Prs_Ape']+' '+response['com']['Prs_Nom']);
                                        $("#lblProv2").attr('title',response['com']['Prs_Ape']+' '+response['com']['Prs_Nom']);
                                        $("#lblDirec2").val(response['com']['Prs_Dir']);
                                        $("#lblDirec2").attr('title',response['com']['Prs_Dir']);
                                        
                                        $("#lblFac2").val(response['pag']['Vet_Num']);
                                        $("#lblVen2").val(response['pag']['Cpc_Ven']);
                                        $("#lblObsV2").val(response['pag']['Cpc_Obs']);
                                        $("#lblTipPa2").val(response['pag']['Pag_Des']);
                                        $("#lblFePa2").val(response['pag']['Cpc_Fec']);
                                        $("#lblVaPa2").val('$ '+response['pag']['Cpc_Val']);                                        
                                       
                                        $("#asiento").jqGrid("clearGridData");                                    
                                        $("#asiento").jqGrid('setGridParam',{rowNum:response['asi']['records']});
                                        $("#asiento").jqGrid('setGridParam', {data:response['asi']['rows'],page:1,records:response['asi']['records'] }).trigger('reloadGrid');
                                        $("#cheque").jqGrid("clearGridData");                                    
                                        $("#cheque").jqGrid('setGridParam',{rowNum:response['che']['records']});
                                        $("#cheque").jqGrid('setGridParam', {data:response['che']['rows'],page:1,records:response['che']['records'] }).trigger('reloadGrid');
                                        
                                        $('#pagoDialog').dialog('open');
                                   }else{$.alert(response['message']);}                                   
                                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
                           
                        }
    </script>
<!-- FIN DEL DIALOGO DETALLE PAGO -->
<div id="output"></div>
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
<!-- FIN DEL DIALOGO CUENTAS-->
<!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog"  title="Mensaje del Sistema">  
        <center><h2>El Comprobante se ha registrado con Exito!</h2></center>  

        <center> 
            <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cerrar</span>
             </button>            
            <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></span> </a>
            <a id="impRecib" target="_blank" href=""  style="display: inline;" title="Imprimir Recibo"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Recibo</span></span> </a>
        </center>        
    </div>
   <script>
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
    </script>
</BODY>
</HTML>