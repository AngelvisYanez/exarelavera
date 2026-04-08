<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factu.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
	
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Factu($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Factu;

$hoy = date("Y-m-d");
$mes = date("m");

/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(8, $Ses_Emp_Cod,$obBD_conexion);

/* busqueda de documentos */
if(isset($searchDocument)){
    $data=$_GET; $data['Emp_Cod']=$Ses_Emp_Cod;
    $responce=$obBD_con1->getPageGrid(34, $data, $obBD_conexion); 
    if($responce['records']>0){              
        foreach($responce['rows'] AS &$row){
            $row['Cpp_Edit']='S';$row['Cpp_Min']=0;
            if(!empty($row['Cpp_Cod'])){
                $Pagos1=$obBD_con1->getRowConsulta(57, $row['Cpp_Cod'].'*'.'A', $obBD_conexion);
                if($Pagos1['total']*1>0){ 
                    $row['Cpp_Det']='S'; //tiene pagos activos
                    $Pagos1=$obBD_con1->getRowConsulta(57, $row['Cpp_Cod'].'*'.'A'.'*'.'SUM', $obBD_conexion);
                    $row['Cpp_Min']=round($Pagos1['total']*1, 2);
                }
                $Pagos2=$obBD_con1->getRowConsulta(57, $row['Cpp_Cod'], $obBD_conexion);                
                if($Pagos2['total']*1>0) $row['Cpp_Edit']='N'; //tiene algun pago vinculado
            }else{ // Caja Chica
                $caja=$obBD_con1->getRowConsulta(58, $row['Cop_Cod'], $obBD_conexion);
                if($caja['total']*1>0) $row['Rcc_Det']='S';
                $caja_pend=$obBD_con1->getRowConsulta(58, $row['Cop_Cod'].'*'.'P', $obBD_conexion);
                if($caja_pend['total']*1>0) $row['Rcc_Pen']='S';
            }
            if($configs['Cof_Con']=='S'&&!empty($row['Com_Cod'])){
                $cuentas = $obBD_con1->getRowConsulta( (!empty($row['Cpp_Cod'])?(!empty($row['Rcc_Pen'])?70:37):39), $row['Com_Cod'], $obBD_conexion);
                $row['Pld_Cod_Pag']=$cuentas['Pld_Cod'];
                $otras_comp = $obBD_con1->getRowConsulta(65, $row['Com_Cod'], $obBD_conexion);
                if($otras_comp['total']*1>1) $row['Com_Edit']='N'; 
            }
            
        }unset($row);
    }
    $obBD_con1->echoJson($responce);
}

/* Consulta el detalle del documento */
if(isset($docDetalle)){
    $resp=array('success'=>true,'Cop_Cod'=>$Cop_Cod,'Cop_Fec'=>$Cop_Fec,'Ret_Cod'=>$Ret_Cod,'rows'=>array());
    if(!empty($Cop_Cod)){
        $resp['items'] = $obBD_con1->getArrayConsulta(35,$Cop_Cod,$obBD_conexion);
        if(count($resp['items'])==0)
            $resp=array('success'=>false, 'message'=>'No se encontraron items en el detalle del documento!');
        else{
            foreach ($resp['items'] as $r) if($r['Iva_Por']*1>0){ $resp['Iva_Cod']=$r['Iva_Cod']; break; }
            if(!empty($Ret_Cod)){  
                $retencion = $obBD_con1->getArrayConsulta(59,$Ret_Cod,$obBD_conexion);            
                foreach ($resp['items'] as &$it){
                    foreach ($retencion as $r) if($it['Cop_Int']==$r['Ret_Int']) foreach ($r as $k=>$v) $it[($r['Ren_Ret']=='R'?'Ret_':'Iva_').$k]=$v;                
                } unset($it);
            }
            if($configs['Cof_Con']=='S'&&!empty($Com_Cod)){
                $iva = $obBD_con1->getRowConsulta(36,$Com_Cod,$obBD_conexion);
                $resp['Pld_Cod']=$iva['Pld_Cod'];            
            } 
        }
    }else $resp['success']=false; 
    $obBD_con1->echoJson($resp);
}
/* Guardar documento */
if(isset($saveDocument)){
    $obBD_ins1 =  new Class_Log_Datos_Factu;
    $obBD_conexionIns = new Class_Log_Conexion_Factu($Ses_Dat_Dis);  
    //$obBD_ins1->debug(true);
	$obBD_con1->validaCierrePeriodo('compras','Cop_Fec','Cop_Cod',null,$Cop_Cod,$obBD_conexion,'S');
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);  
    try{
        $obBD_ins1->operacionobBD(72, $Cop_Cod.'*'.'I', $obBD_conexionIns); //Baja logica de la compra
        if(!empty($Ret_Cod))
            $obBD_ins1->operacionobBD(73, $Ret_Cod.'*'.'I', $obBD_conexionIns); //Baja logica de la retencion
        if(!empty($Com_Cod)){
            $obBD_ins1->operacionobBD(74, $Com_Cod.'*'.'I', $obBD_conexionIns); //Baja logica del comprobante de contable 
            $obBD_ins1->operacionobBD(75, $Com_Cod.'*'.'I', $obBD_conexionIns); //Baja logica de los cheques 
        }
        /* reversar inventario y kardex */
        $row_kard_old = $obBD_con1->getArrayConsulta(43, $Cop_Cod, $obBD_conexion);
        $obBD_Stock =  new Class_Log_Datos_Factu;
        $obBD_conexionStock = new Class_Log_Conexion_Factu($Ses_Dat_Dis); 
        $obBD_Stock->inicio_transaccion($obBD_conexionStock);  
//        $int=1;
//        foreach ($row_kard_old as &$r){ $r['Kar_Int']=$r['Kar_Int']*1; if($int<$r['Kar_Int']) $int=$r['Kar_Int']; } unset($r);
        $numKardex = $obBD_con1->getRowConsulta('kardex_ie.sql.getNext', array('where' => array('Cop_Cod'=>$Cop_Cod)), $obBD_conexion,true);
        $Kar_Int = $numKardex['total'] + 1;

        foreach ($row_kard_old as $row){

        $obBD_ins1->operacionobBD(967, array('Kar_Int' => $row['Kar_Int'], 
                                            'Cop_Cod' => $Cop_Cod, 
                                            'Iva_Cod' => $row['Iva_Cod'],
                                            'Pro_Cod' => $row['Pro_Cod']), $obBD_conexionIns, true);

            $row['IoE']='I';
            $row['Kar_Fec']=$hoy; 
            $row['Kar_Hor']=date("H:i:s");
            $row['Kar_Can']=$row['Kar_Can']*-1;
            $row['Kar_Prs']=$row['Kar_Prs']*1;
            $row['Kar_Ims']=$row['Kar_Ims']*-1;
            $row['Kar_Int']=$Kar_Int;
            $obBD_Stock->updateStockProd($Ses_Suc_Cod, $row, false, $obBD_conexion,$obBD_conexionStock,$Bod_Cod); //revierte el stock
        }        

        //$obBD_ins1->operacionobBD(44, $Cop_Cod, $obBD_conexionIns); // limpia el kardex
        if(!$obBD_Stock->fin_transaccion_nomsn($obBD_conexionStock)) throw new Exception('Error al limpiar los antiguos valores del <u>KARDEX</u>!');        
    }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $responce['message']=$e->getMessage(); echo json_encode($responce); exit(); } 
    if($obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns)){
        $responce=array('success'=>true , 'message'=>'El Documento se ha <u>Anulado</u> correctamente!' );
    }else{
        $responce=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_ins1->MsgError);
    }
    $obBD_ins1->echoJson($responce);
}

$rs_tip_compr = $obBD_con1->getArrayConsulta(5, '', $obBD_conexion); 
$rs_periodo = $obBD_con1->getArrayConsulta(33, $Ses_Emp_Cod, $obBD_conexion); 
$allTypes=true;
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Compras Anular [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>   
    <script type="text/javascript"> var anula=true, gridFact, index, Cof_Con='<?php echo $configs['Cof_Con']; ?>', cod_banano=<?php echo $cod_banano; ?>; </script>
    <script type="text/javascript" src="../VALIDACIONES/fac_val_factu.js?gh=a12"></script>
    <style>
        .footrow td[aria-describedby="documento_Cop_Imp"],.footrow td[aria-describedby="documento_Cop_Pru"]{padding: 0 !important;}
        .footerFact{ text-align:right;width: 100%; }
        .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
        .footerFact input[type=text]{ padding: 0; }
        .footerFact textarea{text-align: left; height: 75px !important;}
        .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
        .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
        .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}            
        #Ret_Asu{ vertical-align: middle; margin-top: -2px; padding: 5px;  -ms-transform: scale(1.4); -moz-transform: scale(1.4); -webkit-transform: scale(1.4); -o-transform: scale(1.4); }
        #resultContent .resp{ font-weight: 700; font-size: 30px; color: #3f3fc1; padding: 0; margin: 0; overflow: hidden; text-overflow: ellipsis; height: 32px;}
        #resultContent .resp span:first-child{color:darkgoldenrod;width: 100px;display: inline-block; margin-left: 42px;}
        .ret .input-group-btn button{padding: 1px 2px !important;}
        .ret{ padding: 0 !important;}
    </style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Anular Documentos de Compras</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body"> 
            <div id="documentoSearch">
                <?php include '../COMPONENTES/facComFormSearch.php'; ?>
                <script>
                    function anulaDoc(data){
                        delete data['uiTooltipTitle'];
                        delete data['uiTooltipId'];
                        delete data['uiTooltipOpen'];  
                        $.saveDataJson('',data,function(r){
                            $('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();
                            $('#serachDocDorm').formSubmit();
                        });
                    }
                    function editDocument(doc){  
                        $('.formDatos').setData(doc,false);                      
                        $('#Cop_Des').val(doc['Cop_Des']);
                        $('#Ret_Num').data({Ret_Num:doc['Ret_Num'],Aut_Cod:doc['Aut_Cod'],Aut_Sri:doc['Aut_Sri']}).fieldValid();
                        $('#btnAnula').data({Ret_Cod:doc['Ret_Cod'], Com_Cod:doc['Com_Cod'], Cop_Cod:doc['Cop_Cod'], Ret_Aut:doc['Ret_Aut']});
                        $.getDataJson('',{docDetalle:true,Cop_Cod:doc['Cop_Cod'],Com_Cod:doc['Com_Cod'],Cop_Fec:doc['Cop_Fec'],Ret_Cod:doc['Ret_Cod']},function(resp){                            
                            $('#documento').setRows(resp['items']).startGridEdit();    
                            $.each(resp['items'],function(i,v){ updateRowItem({rowId:v['index']}); } );
                            addItem({}); 
                            $('#t_descuento').val($.toFixed($("#t_subtotal").val()*1*('0'+$('#Cop_Des').val())/100));
                            updateDocument();
                            $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();   
                            $('#documentoMain').find(':input:not(.btn-main)').attr({readonly:true, tabindex:'-1' }).end().find('select,td.btn,button:not(.btn-ret,.btn-main),input').attr({disabled:true}).unbind('click').end().find('select,input').addClass('readOnly');
                            $('#Iva_Cod,#Iva_Pag').hide();
                            $('#documento').find('tbody tr:last').hide();
                        });                        
                        var credito=($.varValid(doc['Cpp_Cod'])&&doc['Cpp_Cod']!=='');
                        $('#For_Cod').val(credito?2:$.varValid(doc['Rcc_Pen'])?3:1);
                        $('.pagoCredito')[credito?'show':'hide'](); 
                        selectProvee(doc);    
                        $('#Aut_Cod').html(doc['Aut_Cod']||'');
                    }                                        
                </script>
            </div>  
            <div id="documentoMain" style="visibility: hidden;">
                <?php include '../COMPONENTES/facComFormEdit.php'; ?>  
                <div class="row">
                    <div class="col-xs-12">
                        <button class="btn btn-sm btn-inverse btn-main" onclick="$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
                       
                       
                       
                        <button id="btnAnula" class="btn btn-sm btn-danger btn-main" onclick="$.createDialogConfirm('<span class=\'orange\'><b><< <b>Es importnte anular primero este documento en la página del SRI.</b><br>Ud. se dispone a anular esta compra y documentos asociados como: Comprobante contable, Retención, cheques, para lo cual una vez realizada la transacci&oacute;n los cambios no podrán ser revertidos a menos que se comunique con el administrador.>></b></span>'+($(this).data('Ret_Aut')==='S'?'<br/><br/><span class=\'blue\'><b class=\'red\'>NOTA IMPORTANTE:</b> Es un documento electrónico y debe anularse primero en la página del <b class=\'green\'>SRI</b>.</span>':''), $.extend(true,{saveDocument:true},$(this).data()), anulaDoc);" title="Este botón permite Anular la Compra y documentos asociados como: Comprobante contable, Retención y Cheques" ><i class="fa fa-ban"></i> Anular</button>                        
                  
                       
                    </div> 
                </div>    
            </div>
        </div>
    </div>       
   
    <script>
        $(function() {    
            $('#documentoMain').css('visibility','').hide(); 
            $('.destroy').datepicker("destroy");
        }); 
        function selectProvee(provee){
            $('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-'+(provee['Prv_Con']==='S'?'ok green':'remove blue'));
            $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-'+(provee['Prv_Esp']==='S'?'ok green':'remove blue'));                     
        }
        function clearDocument(){
            $('.formDatos').setData({op_opciones:'c',Cal_Inv:'N'});
            $('#docuFormTemp').setData({For_Cod:1,Tri_Cod:2,Cop_Fec:'<?php echo $hoy; ?>',Com_Fec:'<?php echo $hoy; ?>'}).find(':input').attr('readonly');
            gridFact.clearGrid();
            $('#asumirRet').prop('checked',false).hide();
            addItem({});
        }
    </script>     
</BODY>
</HTML>