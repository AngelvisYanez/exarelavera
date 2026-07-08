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

if(isset($saveBene)){      
        $responce['id']='';
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->grabarv_registros(sentencias_ccpp(28,$obBD_con1->parametros($apel.'*'.$nomb)),$obBD_conexion->conexion);
        $ultimo = $obBD_con1->insercionid ($obBD_conexion->conexion);
        $obBD_con1->grabarv_registros(sentencias_ccpp(29,$obBD_con1->parametros($ultimo.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);
        $responce['id'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);       
        if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false;$responce['message']=$obBD_con1->MsgError;
	echo json_encode($responce);exit();
}

if(isset($beneAjax)){ 
    $contar = $obBD_con1->getRowConsulta(27, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] =  $obBD_con1->getArrayConsulta(27, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($valChe)){ 
    $conteo = $obBD_con1->getRowConsulta(18, $Ban_Cod.'*'.$valChe, $obBD_conexion);
    $contar = $obBD_con1->getRowConsulta(17, $Ban_Cod, $obBD_conexion);
    $contar['success']=true;
    if($conteo['conteo']==0)$contar['valid']=true; else $contar['valid']=false;
    echo json_encode($contar);exit();
}
if(isset($cheNum)){ 
    $contar = $obBD_con1->getRowConsulta(17, $Ban_Cod, $obBD_conexion);
    $contar['success']=true;
    echo json_encode($contar);exit();
}
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
if(isset($bancosAjax)){ 
    $response['Pec'] = $obBD_con1->getRowConsulta(5,$Ses_Emp_Cod.'*'.$Pec_Cod, $obBD_conexion);
    $response['rows'] = $obBD_con1->getArrayConsulta(14, $Pec_Cod, $obBD_conexion);$response['options']=''; $response['success']=true;  
    foreach($response['rows'] AS $row)
        $response['options']=$response['options'].'<option value="'.$row['Pld_Cod'].'">'.$row['Pld_Des'].' ('.$row['Ban_Cue'].') </option>';
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
if(isset($detAjax)){ 
    $responce['success']=false;
    $responce['com'] = $obBD_con1->getRowConsulta(9, $Com, $obBD_conexion);
    $responce['pag'] = $obBD_con1->getRowConsulta(30, $Cpp.'*'.$Com, $obBD_conexion);
    $responce['asi']['rows'] = $obBD_con1->getArrayConsulta(10, $Com, $obBD_conexion);$responce['asi']['records']=count($responce['asi']['rows']);
    $responce['che']['rows'] = $obBD_con1->getArrayConsulta(11, $Com, $obBD_conexion);$responce['che']['records']=count($responce['che']['rows']);
    utf8_encode_deep($responce);$responce['success']=true;
    echo json_encode($responce);exit();
}
if(isset($save)){	
    if(!isset($Che_Fec)) $Che_Fec =$Com_Fec;
    if ($op=="I")$var="D";else $var='H'; 
    $rs_buscar =  $obBD_con1->getArrayConsulta(20, $Num_Doc."*".$Ses_Emp_Cod.'*'.$Pld_Cod.'*'.$var, $obBD_conexion);
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
		$acum=$Com_Val;
		/** 
		* Inserci�n del Comprobante 
		*/
                $Num_Doc2=$Num_Doc;
                if ($op=="E"){$Num_Doc2='';}
		if ($op=="I") { $tabla="cliente"; $campo="Cli_Cod"; } if ($op=="E" || $op=="D") { $tabla="proveedore"; $campo="Prv_Cod"; }
		$obBD_con1->grabarv_registros(sentencias_ccpp(21,$obBD_con1->parametros($Pec_Cod.'*'.$Codigo.'*'.$Com_Num.'*'.$Com_Fec.'*'.$Com_Con.'*'.$Tia_Cod
											.'*'.$Com_Val.'*'.$Com_Obs.'*'.$Com_Tipo.'*'.$campo.'*'.$Num_Doc2)),$obBD_conexion->conexion);
		$ultimo = $obBD_con1->insercionid ($obBD_conexion->conexion);
                $ultimo2=0;
		/** 
		* Recorre el arreglo de los datos de las cuentas seleccionadas 
		*/
                foreach ($save as $row)
                {
                    if($row['Det_Tip']=='D') {$valor=$row['Debe'];}
                    else {$valor=$row['Haber'];}
                    $obBD_con1->grabarv_registros(sentencias_ccpp(22,$obBD_con1->parametros($ultimo.'*'.$row['Det_Tip'].'*'.$valor.'*'.$row['Pld_Des'].'*'.$row['Glosa'].'*'.
											$row['Pld_Cod'])),$obBD_conexion->conexion);
                    if($row['Pld_Cod']==$Pld_Cod){
                        $ultimo2 = $obBD_con1->insercionid ($obBD_conexion->conexion);
                    }
                }
                if ($op=="E"){
                    if(isset($Num_Doc)&&$Num_Doc!=''&&$Num_Doc!='0'&&$Num_Doc!=0){
                        if(!isset($Bene_Id)||$Bene_Id=='')
                        {   $obBD_con1->grabarv_registros(sentencias_ccpp(23,$obBD_con1->parametros($apellido.'*'.$nombre)),$obBD_conexion->conexion);
                            $ultimo3 = $obBD_con1->insercionid ($obBD_conexion->conexion);
                            $obBD_con1->grabarv_registros(sentencias_ccpp(24,$obBD_con1->parametros($ultimo3.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);
                            $ultimo4 = $obBD_con1->insercionid ($obBD_conexion->conexion);
                        }else $ultimo4=$Bene_Id;
                        $obBD_con1->grabarv_registros(sentencias_ccpp(25,$obBD_con1->parametros($ultimo4.'*'.$Ban_Cod.'*'.$ultimo2.'*'.$Num_Doc.'* *'.$Com_Val.'*'.$Com_Obs.'*'.$Che_Fec.'*1')),$obBD_conexion->conexion);
                        $responce['cheque']="?codigo2=1&asi=$ultimo2&ban=$Ban_Cod&pro=$ultimo4";
                    }
                    $detener=false;
                    if(isset($CtaPagar))
                        foreach ($CtaPagar as $row2){
                            if($row2['Pago']<=$acum) $acum=$acum-$row2['Pago'];
                            else {$row2['Pago']=$acum;$detener=true;}
                                $obBD_con1->grabarv_registros(sentencias_ccpp(19,$obBD_con1->parametros($row2['Cpp_Cod'].'*'.$Pag_Cod.'*'.$ultimo.'*'.$Com_Fec.'*'.$row2['Pago'].'*'.$Com_Obs)),$obBD_conexion->conexion);
                            if($detener) break;
                        } 
                }
		/**
		* Finaliza la transacci�n
		*/
                $responce['link']="../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo&tabla=$tabla&campo=$campo&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod";
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
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?> 
                <style>

                </style>
	</HEAD>
<BODY>
<div id="set1">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table" style="table-layout:fixed;">
	<tr class="BarraTitulo">
            <td colspan="2" height="10">&raquo; Cancelación Por Lotes a Prooveedores</td>
        </tr>
        <tr>
            <td colspan="2" >
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

                               <input id="docu" name="search" maxlength="13" onkeydown='if (event.keyCode === 13) $.SearchOrDialog("#provDialog",selectProvee);' type="text" class="search ui-corner-all" placeholder="Ingrese Cedula/R.U.C. ..." title="Buscar Proveedor Por Documento o Descripción" autofocus />                               
                               <input type="text" name="op_opciones" value="c" style="display: none;" /> 
                               <input type="hidden" name="Prv_Cod" id="PrvCodBus" value="" />
                               <a onclick="$('#provDialog').dialog('open');/*$('#docu').removeAttr('readOnly');*/" title="Búsqueda de Proveedores"  class="btn btn-success btn-mini"><i class=" icon-check icon-white" ></i></a>                               

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
                    <input type="hidden" name="Prv_Cod" value="" />  
                    <div>
                        <div class="segmento">Todos: <input name="op_opciones" type="radio" value="T" checked alt="" onchange="setCombo('T');" /></div> 
                        <div class="segmento">Vencidos: <input name="op_opciones" type="radio" value="P" alt="" onchange="setCombo('P');" /></div>
                        <div class="segmento">Por Vencer: <input name="op_opciones" type="radio" value="I" alt="" onchange="setCombo('I');" /></div>
                    </div>
                    <div>
                        <div class="segmento">Búscar:
                          </div> 
                        <div class="datasegmento" style="text-align: left;" >
                            <select name="Cmb_Ven" id="Cmb_Ven" class="text medium ui-corner-all"  >                                
                                <option selected="selected" value="1">Todos</option>                               
                            </select>&nbsp;&nbsp;&nbsp;
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
                        <div style="padding-top:10px;">                            
                            <button type="button" class="btn btn-primary start" onclick="if($('#list').jqGrid('getCol', 'Pago', false, 'sum')===0){$.alert('El valor del Pago es Inválido');}else{SelectFact();}" title="Gestionar el Pago Seleccionado"> <i class="icon-book icon-white"></i> <span>Pagar</span></button>
                        </div>
                </div> 
              

                 <script type="text/javascript"> 
                        var tipo='lista';
                        var fact=0,cuentas=new Array(),glosa='',valor=0,bancos=new Array(),numChe=0;
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
                                $("input[name='Prv_Cod']").val('');
                                $("#docu").val('');
                                $('#list').Search('#formCompTemp','ajaxComprobante');
                                return false;
                            }
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
                            public $footRow = $("#grillaComp .ui-jqgrid-sdiv .footrow");   
                            public $footRow = $("#grillaComp .ui-jqgrid-sdiv .footrow");
                            public $name = $footRow.find('>td[aria-describedby="list_proveedor"]'),
                            $invdate = $footRow.find('>td[aria-describedby="list_act"]'),
                            width2 = $name.width()  + $invdate.outerWidth();
                            $invdate.css("display", "none");
                            $name.attr("colspan", "2").width(width2);

                            $footRow.find('>td[aria-describedby="list_subgrid"]').css("border-right-color", "transparent");                            
                            $footRow.find('>td[aria-describedby="list_Com_Codigo"]').css("border-right-color", "transparent");
                            $footRow.find('>td[aria-describedby="list_Cop_Fec"]').css("border-right-color", "transparent");
                            $footRow.find('>td[aria-describedby="list_Cpp_Ven"]').css("border-right-color", "transparent");
                            $footRow.find('>td[aria-describedby="list_Cop_Num"]').css("border-right-color", "transparent");
     
                            $footRow.find('>td[aria-describedby="list_subgrid"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Com_Codigo"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_vencimiento"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Cop_Fec"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Cpp_Ven"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Cop_Num"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_proveedor"]').css("background-color", "white");
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
                            grid.jqGrid("footerData", "set", {proveedor: "<div style='text-align:right;'>TOTAL A PAGAR:</div>",Pago:grid.jqGrid('getCol', 'Pago', false, 'sum')});        
                        }
                    $(document).ready(function () {  
                        //$.createDateRange('#txt_fec_ini','#txt_fec_fin'); 
                        var compGrid=$("#list");
                        compGrid.jqGrid({
                            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                            //postData: $("#form1").getData("ajaxGrid"),
                            autowidth : true, shrinkToFit: true, height: 270,
                            cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                            colModel: [                               
                                { label: 'Cód.Int.', name: 'Cpp_Cod', key: true, hidden:true,viewable:true },  
                                { label: 'Cód.Int.', name: 'Asi_Cod',  hidden:true },                                  
                                {label:'Pld_Cod.',name:"Pld_Cod",hidden:true},
                                {label:'Pld_Cdc.',name:"Pld_Cdc",hidden:true},
                                {label:'Pld_Des.',name:"Pld_Des",hidden:true},
                                { label: 'No. Compr.', name: 'Com_Codigo',align:"center", width: 25 },                      
                                { label: 'Fecha Emis.', name: 'Cop_Fec',align:"center", width: 30  },
                                { label: 'Fecha Venc.', name: 'Cpp_Ven', align:"center", width: 30},
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
                                { label: 'No. Docum.', name: 'Cop_Num', width: 55, align:"center"},
                                {label:'Prv_Cod.',name:"Prv_Cod",hidden:true},
                                {label:'Prs_Ape.',name:"Prs_Ape",hidden:true},
                                {label:'Prs_Nom.',name:"Prs_Nom",hidden:true},
                                { label: 'Proveedor', name: 'proveedor', width: 75},
                                { label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act', width: 10, align: 'center',viewable: false, formatter: 'checkbox',
                                    formatoptions: { disabled: false },resizable:false
                                },
                                { classes:'columnHighlight2',label: 'A Pagar', name: 'Pago', width: 45, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},
                                        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
                                }    
                                 
                            ],     
                            footerrow: true, userDataOnFooter: false,
                            rowNum: 10000000, pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,                           
                            onSelectRow: function(rowid, e) { compGrid.resetSelection();},
                            loadComplete: function (data) { 
                                 var grid=$(this), iCol = grid.getColumnIndexByName('act'), rows = this.rows, i, c = rows.length;
                                updateTotals(grid);                                
                                for (i = 0; i < c; i += 1) {                                    
                                    $(rows[i].cells[iCol]).click(function (e) {                                        
                                        updateSaldos(grid);updateTotals(grid);    
                                    });
                                }  
                                var total = data.records;
                                    for(var i=0;i<total;i++){       
                                        if(data.rows[i]['vencimiento'] ==='Vencido')
                                            $("#"+data.rows[i].Cpp_Cod).css("background", "#FADDDD");
                                        if(data.rows[i]['vencimiento'] ==='Pagado')
                                            $("#"+data.rows[i].Cpp_Cod).css("background", "#DDFAE2");
                                       
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
                                                {label:'Observación',name:"Pag_Obs",width:100},
                                                {label:'Tipo',name:"Pag_Des",width:50,align:"center"},                      
                                                    { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                                        formatter:function (cellvalue, options, rowObject) { 
                                                            var clic='selectDetalle('+rowObject.Cpp_Cod+','+rowObject.Com_Cod+');';
                                                            return  '<span class="btn btn-info btn-mini" title="Seleccionar" onclick=\''+clic+'\'><i class="icon-info-sign icon-white"></span>'; 
                                                        }
                                                    }
                                        ],beforeSelectRow: function(rowid, e) {return false;},
                                        rowNum:10000000, pager: "",height: '100%'
                                });                                
                            }
                        });                        
                        compGrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: false, position: "left", cloneToTop: false })
                            .jqGrid('navButtonAdd',"#listPager",{ caption:"Marcar Todo&nbsp;", buttonicon:"ui-icon-bullet", onClickButton:function(){compGrid.selectAllByComlumn('act',true);updateSaldos(compGrid);updateTotals(compGrid);}, position: "last", title:"", cursor: "pointer"})
                            .jqGrid('navButtonAdd',"#listPager",{ caption:"Desmarcar Todo&nbsp;", buttonicon:"ui-icon-radio-off", onClickButton:function(){compGrid.selectAllByComlumn('act',false);updateSaldos(compGrid);updateTotals(compGrid);}, position: "last", title:"", cursor: "pointer"})
                            .jqGrid('navButtonAdd',"#listPager",{ caption: "Exportar &nbsp;",buttonicon: "ui-icon-arrowthickstop-1-s",title:"Exportar Excel",
                                onClickButton: function() {
                                    compGrid.jqGrid('exportGridExcel',{nombre:"PorPagarLOTES",hoja:"CCPP"});	
                                },position: "last"
                            });
                        compGrid.jqGrid('bindKeys');
                        clearFooter();    
                        //$('#rangeDates').addClass('disabled').find('input').attr('disabled','disabled');
                       //loadBancos();
                    });  
                    
                    function SelectFact(){ 
                            glosa='Abono Facts. ';
                            fact=new Array();
                            var grid=$('#list'),rows= grid.jqGrid('getRowData');
                            for(var i=0;i<rows.length;i++){                                
                                if(rows[i].act==="Yes") 
                                {fact.push(rows[i]);  
                                glosa=glosa+'/'+rows[i]['Cop_Num'];}
                            }
                            cuentas=new Array();var ban;
                            for(var i=0;i<fact.length;i++){
                                ban=true;
                                for(var j=0;j<cuentas.length;j++)
                                    if(fact[i]['Pld_Cod']===cuentas[j]['Pld_Cod'])
                                        {ban=!ban;break;}
                                if(ban)
                                    cuentas.push(fact[i]);
                            } 
                            for(var j=0;j<cuentas.length;j++){
                                cuentas[j]['ValPago']=0;
                                for(var i=0;i<fact.length;i++)
                                     if(fact[i]['Pld_Cod']===cuentas[j]['Pld_Cod'])
                                         cuentas[j]['ValPago']=cuentas[j]['ValPago']+(fact[i]['Pago']*1);
                            }
                            //console.log(cuentas);        
                            valor=$('#list').jqGrid('getCol', 'Pago', false, 'sum');
                            valor=valor.toFixed(2);
                            resetComp();  
                            $('#comp1').next('#comp2');
                    }
                    function resetPago(){ 
                        $('#bancos').attr('disabled','disabled');
                        $('#chefec').attr('disabled','disabled');
                        $('#postfecha').attr('disabled','disabled');
                        $('#NumChe').attr('disabled','disabled');$('#NumChe').val('');$("#NumChe").alertMsg(); 
                        $('#apellido').attr('disabled','disabled');
                        $('#nombre').attr('disabled','disabled');
                        $('#btnBene').attr('disabled','disabled'); 
                        $('input[name="Ban_Cod"]').val('');
                        if(($('#pagos').val()*1)===2 ||($('#pagos').val()*1)===8){
                            $('#bancos').removeAttr('disabled');
                        }
                        if(($('#pagos').val()*1)===3){
                            $('#postfecha').removeAttr('disabled');
                            $('#NumChe').removeAttr('disabled');
                            $('#bancos').removeAttr('disabled');
                            $('#apellido').removeAttr('disabled');
                            $('#nombre').removeAttr('disabled');
                            $('#btnBene').removeAttr('disabled');
                            
                        }
                        addBanco();
                    }
                    function resetComp(){ 
                        $("#comp").clearGrid();  
                        $('#Com_Val_Egre').val(valor);                       
                        $('#ConEgreso').val(glosa);       
                        $('#ObsEgreso').val(glosa); 
                        $('#Cpp_Cod').val(fact[0]['Cpp_Cod']);
                        $('#lblProvee').val(fact[0]['proveedor']);
                        $('#cod_pvr').val(fact[0]['Prv_Cod']);
                        $('#apellido').val(fact[0]['Prs_Ape']);
                        $('#nombre').val(fact[0]['Prs_Nom']);
                        $('#Bene_Id').val(fact[0]['Prv_Cod']);
                        $('#lblMsg').html('Saldo  de '+fact.length+' Factura(s)');  
                        resetPago();
                    }
                    function addBanco(){ 
                        $("#comp").clearGrid(); 
                        for(var j=0;j<cuentas.length;j++)
                            addFilaCuenta(cuentas[j],'S');
                        if(bancos.length>0&&(($('#pagos').val()*1)===2 ||($('#pagos').val()*1)===8 ||($('#pagos').val()*1)===3))
                            for(i=0;i<bancos.length;i++)
                                if(bancos[i]['Pld_Cod']===$('#bancos').val()){
                                    addFilaCuenta(bancos[i],'H');
                                    if(($('#pagos').val()*1)===3)$('input[name="Ban_Cod"]').val(bancos[i]['Ban_Cod']);                                   
                                }
                        
                        LoadCheNum();
                    }
                    function loadBancos(){ 
                        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{bancosAjax:true,Pec_Cod:$('#periodos').val()}, function( response ) {
                                if(response['success']===true){
                                    $('input[name="Pec_Cod"]').val(response['Pec']['Pec_Cod']);                                    
                                    $('input[name="periodo"]').val(response['Pec']['Periodo']);
                                    $("input[name='Com_Fec']").dateLimits(response['Pec']["Pec_Fei"],response['Pec']["Pec_Fef"]);
                                    $('#bancos').html(response['options']);
                                    bancos=response['rows']; 
                                    addBanco();
                                }else{$.alert(response['message']);}                                   
                             },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
                    }
                    
                    function SelectCta(id,tipo){                        
                        if(!$("#comp").existsId(id)){
                            addFilaCuenta($.getDialogGrid("#cuenDialog").jqGrid('getRowData', id),tipo);                           
                        }
                    }  
                    function LoadCheNum(){  
                        if(($('#pagos').val()*1)===3&&bancos.length>0){
                            var Ban_Cod=0;
                            for(i=0;i<bancos.length;i++)
                                if(bancos[i]['Pld_Cod']===$('#bancos').val())
                                     Ban_Cod=bancos[i]['Ban_Cod'];
                            $.get('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',{'Ban_Cod':Ban_Cod,'cheNum':true}, function(response){
                                if(response['success']===true){
                                    numChe=(response['Che_Num']*1)+1;
                                    $("#NumChe").val(numChe).alertMsg();                                  
                                }else {numChe=0;$("#NumChe").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}                                
                            },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;        
                        }    
                    }    
                    function validaCheque(){  
                        var numAnt=$("#NumChe").val();
                        if(($('#pagos').val()*1)===3&&bancos.length>0){
                             var Ban_Cod=0;
                            for(i=0;i<bancos.length;i++)
                                if(bancos[i]['Pld_Cod']===$('#bancos').val())
                                     Ban_Cod=bancos[i]['Ban_Cod'];
                            $.get('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',{'Ban_Cod':Ban_Cod,'valChe': numAnt}, function(response){
                                if(response['success']===true){
                                    if(response['valid']===false){
                                        numChe=(response['Che_Num']*1)+1;
                                        $("#NumChe").val(numChe).alertMsg('El Cheque <b>No. '+numAnt+'</b> ya existe.');
                                    }else {$("#NumChe").alertMsg();}
                                }else {numChe=0;$("#NumChe").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}                                
                            },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;        
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
                        if(tipo==='S')
                        {cuenta['Debe']=cuenta['ValPago'];cuenta['Det_Tip']='D';}
                        var grid=$("#comp");
                        grid.jqGrid("addRowData", cuenta["Pld_Cod"], cuenta, "last");        
                        grid.startGridEdit();
                        $("#comp").updateGridDiario();
                    }
                    function updateValores(){                        
                        valor=$("#Com_Val_Egre").val();
                        var max=0;
                        for(var i=0;i<fact.length;i++){                           
                            max=max+fact[i]['Saldo']*1;
                        }  
                        max = max.toFixed(2);
                        if((valor*1)>max){
                            valor=max;
                            $.alert('El valor no puede ser mayor que $ '+max);
                        }
                        if((valor*1)===0){
                            valor=max;
                            $.alert('El valor no puede ser cero.');
                        }
                        $("#Com_Val_Egre").val(valor);
                        //$("#Com_Sal_Egre").val('$ '+(fact['Saldo']-valor));
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
                        var     tot=parseFloat($("#Com_Val_Egre").val()),
                                deb = gridComp.jqGrid("getCol", "Debe", false, "sum"),
                                hab = gridComp.jqGrid("getCol", "Haber", false, "sum");     
                        //alert(tot); alert(deb); alert(hab);
                        if(deb===tot&&hab===tot){                            
                               if($('#cod_pvr').val()!==''){                                    
                                    if(batch.length>0){ 
                                        var data=$('#formComp').serializeObject();
                                        data["save"]=batch;
                                        data["CtaPagar"]=fact;
                                        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( response ) {
                                            if(response['success']===true){
                                                $('#impCompr').attr('href',response['link']);
                                                $('#printCheque').show();
                                                if(($('#pagos').val()*1)===3){
                                                    $('#successDialog').dialog("option", "height", 250);
                                                     var html=$('#modelo').html();
                                                        html = html.replace(/{banco}/g, $('#bancos').find('option:selected').text());
                                                        html = html.replace(/{link}/g,response['cheque'] );
                                                        $('#printCheque').html(html);
                                                }else{
                                                    $('#successDialog').dialog("option", "height", 150);
                                                    $('#printCheque').html('');                                                    
                                                }                                               
                                                $('#successDialog').dialog('open');
                                                $('#list').trigger('reloadGrid');
                                                $('#comp2').prev('#comp1');
                                            }else{$.alert(response['message']);}
                                         },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
                                    }
                                }else{$.alert("Seleccione El Proveedor");}                                                
                        }else{$.alert("Los Totales no Coinciden!");}
                        gridComp.startGridEdit();
                    }
               </script>	
          </td>
      </tr>
        </table>
                </div>               
                <div id="comp2">
<!-- FORMULARIO COMPROBANTE DE EGRESO -->
            <div id="tabs-2">
                <fieldset>
                    <legend>
                        <label class="Titulos2">Comprobante de Egreso</label>
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
                                            $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(56, "ALL", $obBD_conexion);
                                            foreach ($row_rs_tipo_asien2 as $row) 
                                            { ?>
                                        <option value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des'] ?> </option>
                                            <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Proveedor:</div>
                                <div  class="datasegmento"><input readOnly id="lblProvee" onkeydown='if (event.keyCode === 13) buscaProveeIngreso();' onchange="if($('#lblProvee').val()==='')$('#cod_pvr').val('');" class="search clearable ui-corner-all" placeholder="Ingrese Proveedor"  />
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
                                     <select name="Pag_Cod" id="pagos" onchange="resetPago();" class="text small ui-corner-all" required >                              
                                           <option value="">Seleccione...</option>
                                            <?Php 
                                            $row_rs_tipo = $obBD_con1->getArrayConsulta(16, "ALL", $obBD_conexion);
                                            foreach ($row_rs_tipo as $row) 
                                            { ?>
                                            <option value="<?php echo $row['Pag_Cod']; ?>"><?php echo $row['Pag_Des'] ?> </option>
                                            <?php } ?>           
                                    </select>  
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Banco:
                                    <select name="Pld_Cod" id="bancos" onchange="resetComp();" class="text medium ui-corner-all" required >                              
                                                      
                                    </select>
                                </div>
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
                                    <input  class="text small ui-corner-all" style="text-align: center" name="Num_Doc" id="NumChe" type="text" size="10" onkeypress="return  validar_numeric(event)" onChange="validaCheque();" required />                                    
                                    <img class="imgMsg" /><label class="lblMsg"></label>
                                </div>
                            </div>  
                            <div class="row">
                                <div class="segmento required">Beneficiario:</div>
                                <div  class="datasegmento" id="Benediv"><input id="Bene_Id" name="Bene_Id" type="hidden" />
                                    <input id="apellido" name="apellido" class="text medium ui-corner-all" type="text" size="32" placeholder="Apellidos" style="width:40%;text-transform:uppercase" readOnly /><input  id="nombre" name="nombre" class="text medium ui-corner-all" type="text" size="32" placeholder="Nombres" style="width:40%;text-transform:uppercase" readOnly />
                                    <!--<a onclick="$('#Benediv').removeClass('disabled').find('input').removeAttr('readOnly').val('');$('#apellido').focus();" title="Quitar Beneficiario" class="btn btn-success btn-mini"><i class=" icon-eject icon-white"></i></a>-->
                                    <button id="btnBene" onclick="$('#beneDialog').dialog('open');return false;" title="Seleccionar Beneficiario" class="btn btn-success btn-mini"><i class=" icon-check icon-white"></i></button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Valor:</div>
                                <div  class="datasegmento"><input  class="money text small ui-corner-all" name="Com_Val" id="Com_Val_Egre" onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required /><img src="../../mascaras/model1/imagenes/32x32/info.gif" class="imgMsg" /><label id="lblMsg" class="lblMsg" style="color:blue;"></label></div>
                            </div>               
                        </td></tr>
                        </table> 
                                    <input type="hidden" name="op" value="E" />                                    
                                    <input type="hidden" name="Cpp_Cod" id="Cpp_Cod" />
                                    <input type="hidden" name="Ban_Cod" />                                    
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
                  <button type="button" class="btn btn-primary start" onclick="$('#formComp').formSubmit();" title="Gestionar el Pago Seleccionado"> <i class="icon-book icon-white"></i> <span>Pagar</span></button>
                  <button onclick="$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success fileinput-button"><i class="icon-list-alt icon-white"></i><span>Agregar</span></button>
              </div>
                <script>
                $(document).ready(function () {  
                        $('#Che_Fec').toggleClass('disabled').find('input').toggleAttr('disabled');
                        var gridComp=$("#comp");
                        gridComp.jqGrid({
                            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
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
                    <legend><label class="Titulos2">Datos del Proveedor</label></legend>
                        <div class="row">
                            <div class="segmento">Cédula:</div><div  class="datasegmento"><input id="lblCed2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Proveedor:</div><div  class="datasegmento"><input id="lblProv2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                        <div class="row">
                            <div class="segmento">Dirección:</div><div  class="datasegmento"><input id="lblDirec2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                        </div>
                </fieldset> 
            </div>
            <div class="row" style="padding-top: 5px;padding-bottom: 15px;">
                 <fieldset>
                    <legend><label class="Titulos2">Observación</label></legend>
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
                            <div class="segmento">Observación:</div><div  class="datasegmento"><input id="lblObsV2" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
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
                                { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
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
                    width:618,height:97,postData: {CheListAjax:true},caption:'Cheques Girados',
                    cmTemplate: {sortable:false},colModel: [
                        { label: 'Cód.Int.', name: 'Che_Cod', key: true,hidden:true,viewable: true },                                
                        { label: 'Fecha', name: 'Che_Fec', key: true, width: 50 },
                        { label: 'Num.', name: 'Che_Num', key: true, width: 30,align:"center" },                        
                        { label: 'Banco', name: 'Pld_Des', width: 100,title:'Cuenta Bancaria' },
                        { label: 'Beneficiario', name: 'Beneficiario', width: 150 },
                        { label: 'Valor', name: 'Che_Val', key: true, width: 60,align:"right" , formatter:'currency', decimalPlaces: '2', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}}
                    ],                                                                                       
                    rowNum: 10000, gridview: true, viewrecords: true
                });   
                 $.createDialog('#successDialog',150,550);
        });
        function selectDetalle(Cpp,Com){                             
                           
                                $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{detAjax:true,Cpp:Cpp,Com:Com}, function( response ) {
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
                                        
                                        $("#lblFac2").val(response['pag']['Cop_Num']);
                                        $("#lblVen2").val(response['pag']['Cpp_Ven']);
                                        $("#lblObsV2").val(response['pag']['Pag_Obs']);
                                        $("#lblTipPa2").val(response['pag']['Pag_Des']);
                                        $("#lblFePa2").val(response['pag']['Pag_Fec']);
                                        $("#lblVaPa2").val('$ '+response['pag']['Pag_Val']);                                        
                                       
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
        <center id="printCheque"></center>
        <center> 
            <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cerrar</span>
             </button>            
            <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></span> </a>
               
        </center>        
    </div>
<div id="modelo" style="display:none;">
    <table style="margin-bottom:10px;" cellpadding="1" border="1">
        <tr><td align="center" class="ui-widget-header" colspan="6"><label autofocus> Imprimir Cheque </label></td></tr>
        <tr><td align="center" class="ui-widget-content" colspan="6"><b>&nbsp; {banco} &nbsp;</b></td></tr>
        <tr>
            <td align="center"><a href="tes_pri_cheque_mac_1.0.php{link}" target="_blank" title="Banco de Machala"><img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35"/></a></td>
            <td align="center"><a href="tes_pri_cheque_pac_1.0.php{link}" target="_blank" title="Banco del Pacifico"><img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23"/></a></td>
            <td align="center"><a href="tes_pri_cheque_rum_1.0.php{link}" target="_blank" title="Banco del Rumiñahui"><img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15"/></a></td>
            <td align="center"><a href="tes_pri_cheque_gua_1.0.php{link}" target="_blank" title="Banco del Guayaquil"><img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18"/></a></td>
            <td align="center"><a href="tes_pri_cheque_pch_1.0.php{link}" target="_blank" title="Banco del Pichincha"><img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30"/></a></td>
            <td align="center"><a href="tes_pri_cheque_int_1.0.php{link}" target="_blank" title="Banco Internacional"><img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32"/></a></td>
        </tr>
    </table>
</div>   


<!--INICIO DEL DIALOGO BUSCAR BEnfICIARIO--> 
    <div id="beneDialog"  title="B&uacute;squeda de Beneficiarios">  
       <form> 
        <fieldset>
		<legend>
                    <label class="Titulos2">B&uacute;squeda de Beneficiarios</label>
		</legend>
		<table border="0">
                    <tbody><tr>
			  <td width="205"><input name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" />
			  <span class="LetraNegra"><strong>Apellido</strong></span></td>
			  <td width="266"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
				<span class="LetraNegra"><strong>C&eacute;dula/R.U.C.</strong></span></td>
			</tr>
                    </tbody>
                </table>
                <table height="36" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                      <tr>
                          <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50"  placeholder="Ingrese proveedor a buscar..." autofocus /></td>
                          <td width="140" align="center">
                              <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar Beneficiario" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button>
                           <a onclick="$('#beneApe').val('');$('#beneNom').val('');$('#addBenef').dialog('open');" title="Seleccionar Beneficiario" class="btn btn-success"><i class=" icon-plus icon-white" style="height: 16px;margin-top: 2px;"></i></a>
                          </td>
                      </tr>
                        </tbody>
                </table>
        </fieldset>  
       </form>
    </div>
<!-- FIN DEL DIALOGO  BUSCAR BEnfICIARIO-->

<!-- CREA BEdefICIARIO DIALOG -->
    <div id="addBenef"  title="Crear Beneficiario">  
        
        <fieldset>
		<legend>
                    <label class="Titulos2">Datos Beneficiario</label>
		</legend>
            <form action="javascript:saveBene();"> 
            <div class="row">
                <div class="segmento required">Beneficiario:</div>
                <div  class="datasegmento" id="Benediv">
                    <input id="beneApe" name="apellido" class="text medium ui-corner-all" type="text" size="32" placeholder="Apellidos" style="text-transform:uppercase" required autofocus/><input  id="beneNom" name="nombre" class="text medium ui-corner-all" type="text" size="32" placeholder="Nombres" style="text-transform:uppercase" />                   
                </div>
            </div>
            <div class="row" style="text-align: center;padding-top: 10px;">
                <button type="submit" class="btn btn-success fileinput-button" title="Guardar Proveedor" >
                    <i class="icon-book icon-white"></i>
                    <span>Guardar</span>
                </button><span>&nbsp;</span>
                <button type="button" onclick="$('#addBenef').dialog('close');" class="btn btn-inverse fileinput-button" title="Cancelar" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cancelar</span>
                </button>            
            </div>
            </form> 
        </fieldset>
        
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
                 // DIALOG BUSCAR BENEFICIARIO   
                 $.createDialog('#addBenef',150,550); 
             $.createSearchDialog('beneDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },                                
                    { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                    { label: 'Beneficiario', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                    { label: 'Apellidos', name: 'Prs_Ape',hidden:true},
                    { label: 'Nombres', name: 'Prs_Nom',hidden:true},                                        
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                               var clic='$( "#apellido" ).val("'+rowObject.Prs_Ape+'" );$( "#nombre" ).val( "'+rowObject.Prs_Nom+'" );$( "#Bene_Id" ).val( "'+rowObject.Prv_Cod+'" );$( "#beneDialog" ).dialog("close");';                               
                               return  '<span class="btn btn-success btn-mini" title="Seleccionar" onclick=\''+clic+'\'><i class="icon-arrow-right icon-white"></span>'; 
                            }
                        }
                ]);  
                function saveBene() {
                    $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{saveBene:true,apel:$('#beneApe').val(),nomb:$('#beneNom').val()}, function( response ) {
                       if(response['success']===true){
                           $('#apellido').val($('#beneApe').val());$('#nombre').val($('#beneNom').val());$('#Bene_Id').val(response['id']);
                           $('#addBenef').dialog('close');$('#beneDialog').dialog('close');
                       }else{$.alert(response['message']);}                                   
                    },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
                }
    </script>



</BODY>
</HTML>