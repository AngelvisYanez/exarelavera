<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factu.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Factu;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($docDetalle)){
    $reten=$obBD_con1->getRowConsulta('retencion.selectWhere',array('clean'=>true,'unsetCols'=>true,'addCols'=>array(''=>array('Cop_Cod')), 'where'=>array('Ret_Cod'=>$Ret_Cod)), $obBD_conexion);
    $detalle=$obBD_con1->getArrayConsulta('retencion.selectWhere', array('addCols'=>array('renta_iva'=>'*'),'order'=>'Ren_Ret DESC,Ren_Sri','group'=>'renta_iva.Ren_Cod','where'=>array('retencion.Ret_Cod'=>$Ret_Cod),'setWhere'=>array('setTotales')), $obBD_conexion);
    $items=$obBD_con1->getArrayConsulta('det_compra.selectWhere', array('clean'=>true,'where'=>array('Cop_Cod'=>$reten['Cop_Cod'])), $obBD_conexion);
    $obBD_con1->echoJson(array('success'=>true, 'retencion','detalle'=>$detalle, 'items'=>$items));
}
$configs =$obBD_con1->getRowConsulta('confi_fact.selectWhere',array('clean'=>true,'unsetCols'=>true,'addCols'=>array(''=>array('Cof_Con')), 'where'=>array('Emp_Cod'=>$Ses_Emp_Cod)), $obBD_conexion);

if(isset($searchDocument)){
    require_once('../LOGICA/fac_log_factu.php');
    $obBD_con1 =  new Class_Log_Datos_Factu;
    $responce=$obBD_con1->getPageGrid('retencion.selectWhere', array_merge($_GET,array('group'=>'retencion.Ret_Cod','setWhere'=>array('setTotales'),'addCols'=>array('compras'=>array('*'), 'proveedore'=>array('Prv_Con','Prv_Esp'), 'persona'=>array('Prs_Ced','Prs_Dir') ))), $obBD_conexion);        
    if($responce['records']>0){              
        foreach($responce['rows'] AS &$row){
            if($row['Cop_Est']=='A'){
                $compra=$obBD_con1->getRowConsulta(34, array('limits'=>'AND compras.Cop_Cod='.$row['Cop_Cod'],'op_opciones'=>'','search'=>''), $obBD_conexion,true);
                $row=array_merge(is_array($compra)?$compra:array(),$row);
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
            }
        }unset($row);
    }
    $obBD_con1->echoJson($responce);
}
if(isset($anulaRetencion)){
    $obBD_con1->validaCierrePeriodo('retencion','Ret_Fec','Ret_Cod',$Cop_Fec,$Ret_Cod,$obBD_conexion,'S');
	$resp=array('success'=>false);    
    //if(isset($resp['message'])) $obBD_con1->echoJson($resp);

    $obBD_ins1 =  new Class_Log_Datos_Factu;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);  
    try{ 
        // Arreglo el Asiento Contable
        if(isset($configs['Cof_Con']) && $configs['Cof_Con']=='S'){                

            $Iva_Costo=0;
            $reten=$obBD_con1->getRowConsulta('retencion.selectWhere',array('clean'=>true,'unsetCols'=>true,'addCols'=>array(''=>array('Cop_Cod')), 'where'=>array('Ret_Cod'=>$Ret_Cod)), $obBD_conexion);
            $compr=$obBD_con1->getRowConsulta('compr_auto.selectWhere',array('clean'=>true,'addCols'=>array(''=>array('Com_Cod')), 'where'=>array('Cop_Cod'=>$reten['Cop_Cod'])), $obBD_conexion);
            if(isset($compr['Com_Cod'])&&!empty($compr['Com_Cod'])){
                 /* Valida que los Periodos Existan */      
                $Pec_Cop = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion);    
                if(empty($Pec_Cop['Pec_Cod'])){ throw new Exception("No Existe Periodo para la Fecha: $Cop_Fec!"); }
                $Pec_Cod=$Pec_Cop['Pec_Cod'];
                
                $Com_Cod=$compr['Com_Cod'];
                $obBD_ins1->operacionobBD(1015, $reten['Cop_Cod'], $obBD_conexionIns); // Pone Asi_Cod en NULL antes de borrar asientos
                $obBD_ins1->operacionobBD(41, $Com_Cod, $obBD_conexionIns); // Elimina el asiento anterior
                /* Inserta datos en el detalle del asiento (por items) */
                foreach ($items as &$item){ 
                    $addIva=round(($item['Iva_Cos']=='S'&&$item['Iva_Por']*1>0?(($item['Cop_Imp']-($Cop_Des>0?$item['Cop_Imp']*$Cop_Des/100:0))*$item['Iva_Por']/100):0),2);
                    $Iva_Costo=$Iva_Costo+$addIva;
                    if(empty($item['Pld_Cod'])){
                        $cuenta = $obBD_con1->getRowConsulta(16,$Pec_Cop['Pla_Cod'].'*'.$item['Pro_Cod'].'*'.'C', $obBD_conexion);                 
                        if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del producto: <u>'.$item['Ite_Lar'].'</u>!');
                        $item['Pld_Cod']=$cuenta['Pld_Cod']; $item['Pld_Des']=$cuenta['Pld_Des'];
                    }
                    $obBD_ins1->operacionobBD(1012, $Cop_Cod , $obBD_conexionIns);//Eliminar el codigo de la retencion, de detalles de compras
                    $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.'D'.'*'.($item['Cop_Imp']+$addIva).'*'.(isset($item['Pld_Des'])?$item['Pld_Des']:'').'*'.$item['Ite_Lar'].'*'.$item['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Item                
                } unset($item); 
                /* IVA */
                $iva=$t_iva*1-$Iva_Costo;
                if($iva>0){
                    if(empty($Iva_Pag))  throw new Exception('Revisar la parametrizacion contable de: <u>Iva Pagado</u>!');
                    $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('D').'*'.$iva.'*'.'IVA'.'*'.'IVA'.'*'.$Iva_Pag, $obBD_conexionIns);  // inserta asiento // Iva            
                }
                /* DESCUENTO */
                if($Cop_Des>0){
                    $cuenta = $obBD_con1->getRowConsulta(28,$Pec_Cop['Pla_Cod'].'*'.'CDS', $obBD_conexion);
                    if(!isset($cuenta['Pld_Cod'])&&empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Descuentos en Compras</u>!');
                    $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('H').'*'.$t_descuento.'*'.'DESCUENTO'.'*'.'DESCUENTO'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // descuento
                }
                if($t_ice*1>0){
                    $cuenta = $obBD_con1->getRowConsulta(28,$Pec_Cop['Pla_Cod'].'*'.'ICC', $obBD_conexion);
                    if(!isset($cuenta['Pld_Cod'])&&empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>ICE en Compras</u>!');                
                    $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('D').'*'.$t_ice.'*'.'ICE'.'*'.'ICE'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE            
                }   
                if($Cop_Irb*1>0){
                    $cuenta = $obBD_con1->getRowConsulta(28,$Pec_Cop['Pla_Cod'].'*'.'IRC', $obBD_conexion);
                    if(!isset($cuenta['Pld_Cod'])&&empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>IRBPNR en Compras</u>!');                
                    $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('D').'*'.$Cop_Irb.'*'.'IRBPNR'.'*'.'IRBPNR'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE            
                }

                /* ANULAR EL ABONO DE LA RETENCION Y EL COMPROBANTE RELACIONADO */
                if(!empty($Cpp_Cod))
                {
                    $obBD_ins1->operacionobBD(966, $Cpp_Cod, $obBD_conexionIns);
                }

                /* Pago */            
                /* REVISAR VARIOS PAGOS/ANTICIPOS */
                $obBD_ins1->operacionobBD(17, $Com_Cod.'*'.('H').'*'.$t_rubros.'*'.''.'*'.('Doc.'.$Cop_Num).'*'.$Pag_Pld, $obBD_conexionIns);  // inserta asiento // pago
                /* REVISAR VARIOS PAGOS/ANTICIPOS */  
                //throw new Exception("Tiene Contabilidad y no se Puede Anular Por El Momento (Lo estamos trabajando)!");
            }
        }
        // Desactiva la retencion
        $obBD_ins1->operacionobBD('retencion.setInactive', array('Ret_Cod'=>$Ret_Cod), $obBD_conexionIns);
        
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }    
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns); // finalizo la transaccion y compruebo errores
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);    
}
/* reviso las cuentas pago */
if(isset($cuentasPago)){   
    require_once('../LOGICA/fac_log_factu.php');
    $obBD_con1 =  new Class_Log_Datos_Factu;
    $responce['cuentas']='';
    $Pec_Cod = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion);  
    if($For_Cod*1==2)
        $cuentas = $obBD_con1->getArrayConsulta(23, $Pec_Cod['Pla_Cod'].'*'.$For_Cod, $obBD_conexion);
    if($For_Cod*1==1)
        $cuentas = $obBD_con1->getArrayConsulta(22, $Pec_Cod['Pla_Cod'].'*'.$For_Cod, $obBD_conexion);
    if($For_Cod*1==3)
        $cuentas = $obBD_con1->getArrayConsulta(28, $Pec_Cod['Pla_Cod'].'*'.'RC', $obBD_conexion);
        
    $responce['total']=count($cuentas);
    foreach ($cuentas AS $row)
        $responce['cuentas']=$responce['cuentas'].'<option value="'.$row['Pld_Cod'].'" data-extra="'.(isset($row['extra'])?$row['extra']:'').'" '.($row['Pld_Cod']==$Pld_Cod?'selected="selected"':'').'>'.$row['Pld_Des'].'</option>';
    if($responce['total']>1)
        $responce['cuentas']="<option value=''>Seleccione...</option>".$responce['cuentas'];
    //if(!empty($Pld_Cod)) $responce['Pld_Cod']=$Pld_Cod;
    $responce['success']=true; 
    $obBD_con1->echoJson($responce);
}
/* reviso los ivas */
if(isset($Check_Iva)){
//    $responce['varIvas']=(/*!empty($Tic_Sri)&&('0'.$Tic_Sri)*1==4&&*/$Cop_Fec<='2017-05-31');
//    if($responce['varIvas'])
        $responce['ivas']  = $obBD_con1->getArrayConsulta(18, '', $obBD_conexion);
//    else
        $responce['iva_activo']  = $obBD_con1->getRowConsulta(19, $Cop_Fec, $obBD_conexion); 
    $responce['varIvas']=true;    
    $responce['total']=count($responce['ivas']);
    $responce['options']='';
    foreach ($responce['ivas'] AS $row)
        $responce['options']=$responce['options'].'<option value="'.$row['Iva_Cod'].'" data-ivapor="'.$row['Iva_Por'].'" '.(empty($Iva_Cod)?($responce['iva_activo']['Iva_Por']==$row['Iva_Por']?'selected="selected"':''):($row['Iva_Cod']==$Iva_Cod?'selected="selected"':'')).'>'.$row['Iva_Por'].' %</option>';
    if($configs['Cof_Con']=='S'){
        $responce['cuentas']='';
        $Pec_Cod = $obBD_con1->getRowConsulta(9,$Ses_Emp_Cod.'*'.$Cop_Fec,$obBD_conexion);  
        $iva_pag = $obBD_con1->getArrayConsulta(20, $Pec_Cod['Pla_Cod'], $obBD_conexion);
        foreach ($iva_pag AS $row)
            $responce['cuentas']=$responce['cuentas'].'<option value="'.$row['Pld_Cod'].'" '.(isset($Pld_Cod)&&$row['Pld_Cod']==$Pld_Cod?'selected="selected"':'').' >'.$row['Pld_Des'].'</option>';
    }
    $responce['success']=true; 
    $obBD_con1->echoJson($responce);
}
/* Consulta el detalle del documento */
if(isset($docDetalleFact)){
    require_once('../LOGICA/fac_log_factu.php');
    $obBD_con1 =  new Class_Log_Datos_Factu;
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
        $reemb = $obBD_con1->getArrayConsulta('compra_reembolsos.selectWhere',array('Cop_Cod'=>$Cop_Cod),$obBD_conexion);
        if(count($reemb)>0){
            $resp['reembolsos'] =$reemb;
        }
    }else $resp['success']=false; 
    $obBD_con1->echoJson($resp);
}
$rs_periodo = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('setWhere'=>array('order',"setEmpCod")), $obBD_conexion); 
$rs_tip_compr = $obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('clean'=>true,'where'=>array('Tic_Est'=>'A')), $obBD_conexion);
require_once('../LOGICA/fac_log_factu.php');
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Retencion Anular [EXA]"; ?></TITLE>
        <meta charset= "UTF-8"> 
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>    
        <script>
            var Cof_Con='<?php echo $configs['Cof_Con']; ?>',
                extraSearch=[{ label: $.createIcon('trash'), name: 'act01', width: 10, align: 'center', viewable: false, 
                    formatter: 'gridButton', formatoptions:{action:'editDocument', /*data:['Cop_Fec','Ret_Cod','Cop_Cod','Secuencia','Ret_Aut'],*//* type:'danger', icon:'trash',*/ title:'Seleccionar Retencion', conditional: function(o){return o.Ret_Est !== 'I';}, caseFalse:'<i class="glyphicon glyphicon-remove red" title="Anulado/Inactivo"></i>' } 
                //     formatter:function(cv,opt,o){
                //     // Obtener fecha de la retención
                //     var fechaRet = o.Ret_Fec || o.Cop_Fec;
                //     if(!fechaRet) return '';
                //     var partes = fechaRet.split('-');
                //     var anio = parseInt(partes[0],10), mes = parseInt(partes[1],10), dia = parseInt(partes[2],10);

                //     // Calcular fecha límite (10 del siguiente mes)
                //     var limite = new Date(anio, mes, 10); // mes+1 porque Date usa 0-index
                //     var hoy = new Date();
                //     var fechaRetencion = new Date(anio, mes-1, dia);

                //     if(o.Ret_Est==='I') 
                //         return '<i class="glyphicon glyphicon-remove red" title="Anulado/Inactivo"></i>';
                //     // Bloqueo por normativa SRI solo si es electrónica
                //     if(o.Ret_Aut === 'S' && hoy > limite){
                //         return '<i class="glyphicon glyphicon-lock orange" title="Bloqueado por Normativa SRI, solo se puede anular este documento electrónico hasta el 10 del siguiente mes."></i>';
                //     }
                //     if(o.Cop_Est==='E') 
                //         return $.getGridButton('bajaRetencion',{Ret_Cod:o.Ret_Cod,Cop_Cod:o.Cop_Cod,Secuencia:o.Secuencia},'Anular Retención Financiera','trash',null,'danger');
                //     return $.getGridButton('editDocument',o,'Selecciona Retención');
                // }
                }], cod_banano=<?php echo $cod_banano; ?>
        </script>   
    <script type="text/javascript" src="../VALIDACIONES/fac_val_factu.js?gh=a11"></script>
    <style></style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Anulaci&oacute;n de  Retenci&oacute;n</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="documentoMain" style="visibility: hidden;">                                
                <?php include '../COMPONENTES/facComFormEdit.php'; ?> 
                <div class="row">  
                    <div class="col-xs-6" >
                        <div><table id="retencionFull"></table></div>
                    </div>   
                    <div class="col-xs-6" >
                        <div><table id="productosFull"></table></div>
                    </div>    
                </div>
                <div class="row">
                    <div class="separator"></div>
                    <div class="col-xs-12">
                        <button class="btn btn-sm btn-inverse btn-main" onclick="$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
                        <button id="btnBaja" class="btn btn-sm btn-danger btn-main" onclick="bajaRetencionCompra($(this).data());" ><i class="glyphicon glyphicon-trash"></i> Anular</button>                        
                    </div>
                </div>
            </div>
            <script type="text/javascript">    
                $(function(){
                    $('#documentoMain').css('visibility','').hide();
                    $('#documentoMain').find(':input:not(.btn-main)').attr({readonly:true, tabindex:'-1' }).end().find('select,td.btn,button:not(.btn-ret,.btn-main),input').attr({disabled:true}).unbind('click').end().find('select,input').addClass('readOnly');
                });
                function editDocument(doc){        
                    //console.log(doc);
                    $('#btnBaja').data(doc);
                    $('#Ret_Cod').val('');
                    $('#t_descuento').val(0);
                    $('.validate').find('i').removeAttr('class');
                    $('#provFormTemp').setData({op_opciones:'c',Cal_Inv:'N'});
                    if(!$.varValid(doc['Ret_Cod'])||doc['Ret_Cod']===''){ doc['Ret_Num']=''; doc['Ret_Fec']=doc['Cop_Fec']; doc['Aut_Cod']=''; }
                    $('.formDatos').setData(doc,false);
                    $('.Cop_Fec').val(doc['Cop_Fec']);

                    $('#autorizaForm').setData({Ret_Fec:doc['Ret_Fec']});
                    $.Search('autoriza');
                    $('#Cop_Num').data('old_num',doc['Cop_Num']);

                    $('#Cop_Des').val(doc['Cop_Des']);
                    $('#Ret_Num').data({Ret_Num:doc['Ret_Num'],Ret_Num_Mod:doc['Ret_Num'],Aut_Cod:doc['Aut_Cod'],Aut_Sri:doc['Aut_Sri']}).fieldValid();
                    $('#Ret_Fec').data({Aut_Fci:doc['Aut_Fci'],Aut_Cad:doc['Aut_Cad']});
                    $("#btnClaveExterna").css('display',doc['Aut_Tem']==="E"?"":"none");
                    var edit_pago=doc['Cpp_Edit']!=='N'&&doc['Cpp_Det']!=='S';
                    (edit_pago?$('#For_Cod').removeAttr('disabled'):$('#For_Cod').attr('disabled','disabled'));
                    $('#Pag_Pld').data('disabled',!edit_pago);
                    if(!$.varValid(doc['Ret_Cod'])||doc['Ret_Cod']==='') validaRetNum();
                    $.getDataJson('',{docDetalleFact:true,Cop_Cod:doc['Cop_Cod'],Com_Cod:doc['Com_Cod'],Cop_Fec:doc['Cop_Fec'],Ret_Cod:doc['Ret_Cod']},function(resp){                            
                        checkFechaIva(resp['Cop_Fec'],resp['Iva_Cod'],resp['Pld_Cod']);                            
                        $('#documento').setRows(resp['items']).startGridEdit();    
                        $.each(resp['items'],function(i,v){ updateRowItem({rowId:v['index']}); } );
                        addItem({}); 
                        $('#t_descuento').val($.toFixed($("#t_subtotal").val()*1*('0'+$('#Cop_Des').val())/100));
                        updateDocument();
                        $('#documentoMain').find(':input:not(.btn-main)').attr({readonly:true, tabindex:'-1' }).end().find('select,td.btn,button:not(.btn-ret,.btn-main),input').attr({disabled:true}).unbind('click').end().find('select,input').addClass('readOnly');                                                
                        $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
                        if($.vv(resp['reembolsos']) && $.isArray(resp['reembolsos'])){
                            $.each(resp['reembolsos'],function(i,v){
                                resp['reembolsos'][i]['Total']=(v['Rem_Niv'].toNum()+v['Rem_Siv'].toNum()+v['Rem_Oiv'].toNum()+v['Rem_Eiv'].toNum()+v['Rem_Iva'].toNum()+v['Rem_Ice'].toNum());
                            });
                            reembolsos.setRows(resp['reembolsos']);
                            $('#Rem_Fec').datepicker("option","maxDate",$('#Cop_Fec').val());
                        }
                        $('#productosFull').setRows(resp['items']);
                        $('#retencionFull').setRows($('#retencion').getGridBatch());
                        $('#Cop_Num').fieldValid(true);
                    });
                    $('#Ciu_Cod').trigger('chosen:updated'); 
                    var credito=($.varValid(doc['Cpp_Cod'])&&doc['Cpp_Cod']!=='');
                    $('#For_Cod').val(credito?2:$.varValid(doc['Rcc_Pen'])?3:1);
                    $('.pagoCredito')[credito?'show':'hide']();
                    checkCuentaPago(doc['Pld_Cod_Pag']);
                    $('#proForm').formSubmit();
                    selectProvee2(doc);                                                 
                    $('#Cop_Imf').datepicker("option","maxDate",doc['Cop_Fec']); $('#Cop_Cad').datepicker("option","minDate",doc['Cop_Fec']); $('#Ret_Fec').datepicker("option","minDate",doc['Cop_Fec']); $('#Cpp_Ven').datepicker("option","minDate",doc['Cop_Fec']);
                    if(Cof_Con==='S') $('#Com_Fec').datepicker("option","minDate",doc['Cop_Fec']);
                    $('#Aut_Cod').html(doc['Aut_Cod']||'');
                    if(!$.varValid(doc['Ret_Num'])||doc['Ret_Num']==='') validaRetNum();
                }
                function selectProvee2(provee){
                    $('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-'+(provee['Prv_Con']==='S'?'ok green':'remove blue'));
                    $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-'+(provee['Prv_Esp']==='S'?'ok green':'remove blue'));                     
                }
            </script>        
            <div id="documentoSearch" class="row">
                <?php include '../COMPONENTES/factSearchRetencion.php'; ?>
            </div>
            
        </div>
    </div>
    <script type="text/javascript">    
    $(function(){
        
    });  
    function bajaRetencionCompra(doc){
        if(($('#Val_Pcc').val())*1<0){ $.alert('El valor a pagar no puede ser negativo!<br/>Revise los datos.',null,'remove'); return; }
        var data=$('.formDatos').getData('saveDocument'), aut=data['Cop_Aut'].length;
        if(Cof_Con==='S'){ 
             if(data['Cop_Fec'].substring(0, 4)!==data['Com_Fec'].substring(0, 4)) { $('#Com_Fec').flyout('show').focus();  return; }
        } 
        if(!data['Prv_Cod'].length){ $('#Prv_Btn').focus(); $('#Prv_Btn').flyout('show'); return; }
        if(aut!==10&&aut!==37&&aut!==49){ $('#Cop_Aut').flyout('show').focus();  return; }           
        if(!data['Ciu_Cod'].length){ $('#Ciu_Cod_chosen').flyout('show').focus(); return; }                     
        if(gridFact.jqGrid('getDataIDs').length-1<=0){ $.alert('Debe seleccionar al menos un <u>Item</u>!',null,'remove'); return; }
        data['items']=gridFact.getGridBatch(); 
        gridFact.startGridEdit();
        data['Old_Num']=$('#Cop_Num').data('old_num');
        data['Tic_Sri']=$("#Tic_Cod option:selected").data('ticsri');
        data['Tic_Des']=$("#Tic_Cod option:selected").text();        
        data['items'].splice(data['items'].length-1, 1);
        for(var i=0; i<data['items'].length; i++){
            if(data['items'][i]['Pro_Cod']===''){ $.alert('Seleccione producto en la fila '+(i+1)+'!',null,'remove'); return; }
            if(data['items'][i]['Cop_Imp']*1<=0){ $.alert('El producto <u>'+data['items'][i]['Ite_Lar']+'</u> no puede tener <i>Importe cero</i>!',null,'remove'); return; }
        }  
        $.arraySpliceFields(data['items'],['index','delete','select','Uni_Des','Pld_Cdc','Pld_Des']);        
        console.log(data); 
        $.createDialogConfirm(`¿Est&aacute; seguro que desea dar de <b class="red">BAJA</b> a la <b class="green">Retención</b> <b><u>${doc['Secuencia']}</u></b>?`+(doc['Ret_Aut']==='S'?"<br/><br/><span class='blue'><b class='red'>NOTA IMPORTANTE:</b> Es un documento electronico y debe anularse también en la pagina del <b class='green'>SRI</b>.</span>":''), $.extend(data,{anulaRetencion:true}), function(data){
            $.saveDataJson('',data,function(resp){
                $('#searchGrid').gridUpdate();
                $('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();
            });
        });
    }
    function bajaRetencion(doc){
        $.createDialogConfirm(`¿Est&aacute; seguro que desea dar de <b class="red">BAJA</b> a la <b class="green">Retención</b> <b><u>${doc['Secuencia']}</u></b>?`+(doc['Ret_Aut']==='S'?"<br/><br/><span class='blue'><b class='red'>NOTA IMPORTANTE:</b> Es un documento electronico y debe anularse también en la pagina del <b class='green'>SRI</b>.</span>":''), $.extend(doc,{anulaRetencion:true}), function(data){
            $.saveDataJson('',data,function(resp){
                $('#searchGridReten').gridUpdate();
            });
        });
    }
    </script>
</BODY>
</HTML>