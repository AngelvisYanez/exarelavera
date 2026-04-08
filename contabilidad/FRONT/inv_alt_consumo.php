<?php	
/**
* @abstract Permite realizar movimientos de inventario
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/inv_log_inventario.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Inv($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Inv;

$hoy = date("Y-m-d");
$mes = date("m");
    

//echo days_360("2016-01-01","2016-12-31");
/* Seleccionar El Producto a Producir */
if(isset($proAjax)){
    $contar = $obBD_con1->getRowConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
    foreach ($responce['rows'] as &$row) {
        $stock = $obBD_con1->getRowConsulta(2, $Ses_Suc_Cod.'*'.$row['Pro_Cod'], $obBD_conexion);       
        $row['Stk_Can']=(empty($stock['Stk_Can'])?0:$stock['Stk_Can']);         
        $row['Aju_Imp']=$row['Aju_Pru']=(empty($stock['Pro_Prp'])?(empty($stock['Stk_Prp'])?(empty($stock['Pre_Pvp'])?NULL:$stock['Pre_Pvp']):$stock['Stk_Prp']):$stock['Pro_Prp']);            
    }
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
if(isset($updateNumber)){
    $responce['next'] = $obBD_con1->getRowConsulta(20, $Aju_Tip.'*'.$Ses_Emp_Cod, $obBD_conexion);
    $responce['success']=true;
    utf8_encode_deep($responce);
    echo json_encode($responce);exit();
}

$configuraciones = $obBD_con1->getRowConsulta(4, $Ses_Emp_Cod,$obBD_conexion);

/* Guardar el Formulario */
if(isset($saveForm)){   
    $Prv_Cod=$obBD_con1->getProveeClie($Ses_Emp_Cod,'Prv_Cod', $obBD_conexion);
    $Cli_Cod=$obBD_con1->getProveeClie($Ses_Emp_Cod,'Cli_Cod', $obBD_conexion);
    // Consulta del vendedor en base al codigo de la persona   
    $rs_vendedor = $obBD_con1->getRowConsulta(5, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
    if (count($rs_vendedor) == 0){ $responce=array('success'=>false,'message'=>" Ud. no esta autorizado para realizar ajustes ");echo json_encode($responce);exit(); }
        
    //consulto el codigo secuencial del Tac_Cod 
    $rs_codigo = $obBD_con1->getRowConsulta(6, $Tia_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);		
     
    $obBD_conexion_Ins = new Class_Log_Conexion_Inv($Ses_Dat_Dis);
    $obBD_ins1 =  new Class_Log_Datos_Inv;
    $obBD_ins1->inicio_transaccion($obBD_conexion_Ins->conexion); 
        
        // Registrando la cabcera de Ajuste 
        $ajuste = array(
            'Aju_Fec'=>$hoy,
            'Aju_Hor'=>date ("H:i:s"),
            'Vnd_Cod'=>$rs_vendedor['Vnd_Cod'],
            'Prv_Cod'=>$Prv_Cod,
            'Tia_Cod'=>$Tia_Cod,
            'Aju_Num'=>$Aju_Num,
            'Aju_Sec'=>$rs_codigo['Aju_Sec'],
            'Aju_Det'=>$Aju_Det,
            'Aju_Obs'=>$Aju_Obs,
            'Aju_Tip'=>$Tip_Pld
        );
	$obBD_ins1->operacionobBD(7, $ajuste, $obBD_conexion_Ins);
        $Aju_Cod = $obBD_ins1->insercionid($obBD_conexion_Ins->conexion);
        
      
        // recorro los items
        $Com_Val=0;
        $kardex=array(
            'Kar_Fec'=>$hoy,
            'Kar_Hor'=>date ("H:i:s"),
            'Aju_Cod'=>$Aju_Cod,
            'Vnd_Cod'=>$rs_vendedor['Vnd_Cod'],
        );
        $Aju_Int=0;
        $Prods=array();
        foreach ($saveForm AS $row){
            // graba detalle del ajuste
            if(empty($row['Aju_Imp'])) $row['Aju_Imp']=round($row['Aju_Can']*$row['Aju_Pru'],2);
            $row['Aju_Cod']=$Aju_Cod; $Aju_Int++; $row['Aju_Int']=$Aju_Int;
            $obBD_ins1->operacionobBD(8, $row, $obBD_conexion_Ins);
            
            $kardexie=$kardex;
            $kardexie['Pro_Cod']=$row['Pro_Cod'];$kardexie['Iva_Cod']=$row['Iva_Cod'];$kardexie['IoE']=$Tia_IoE;
            $kardexie['Kar_Int']=$Aju_Int;
            
            //if($Tia_IoE=='E'){                
                $kardexie['Kar_Sal']=$row['Aju_Can'];
                $kardexie['Kar_Pre']=$row['Aju_Pru'];
                $kardexie['Kar_Ime']=$row['Aju_Imp'];//                
            //}
            // graba el detalle del kardex 
            $obBD_ins1->operacionobBD(16, $kardexie, $obBD_conexion_Ins);
            
            $Com_Val=$Com_Val+($row['Aju_Imp']*1);
            
            // uno los productos para el stock
            $add=true;
            foreach ($Prods AS &$p){
                if($p['Pro_Cod']==$row['Pro_Cod']){
                    $add=false;
                    $p['Kar_Sal']=$p['Kar_Sal']+$row['Aju_Can'];
                    $p['Kar_Ime']=$p['Kar_Ime']+$row['Aju_Imp'];
                    $p['Kar_Pre']=round($p['Kar_Ime']/$p['Kar_Sal'],8);
                    break;
                }
            }if($add) array_push ($Prods, $kardexie);
        }
        foreach ($Prods AS $p){            
            //actualiza stock
            $obBD_con1->updateStock($Ses_Suc_Cod,$p,$obBD_conexion,$obBD_conexion_Ins);
        }
        //var_dump($Prods);
        $responce['linkAjust']="../../facturacion/FRONT/fac_pri_aju_1.0.php?Aju_Cod=$Aju_Cod";
        if($configuraciones['Cof_Con']=='S'){
            $Pec_Cod=$obBD_con1->getPec_Cod($Ses_Emp_Cod,$hoy, $obBD_conexion);
            $Com_Num=$obBD_con1->codigoComprAuto($Ses_Emp_Cod,$Tia_Asi,$hoy, $obBD_conexion);
            // cabecera del comprobant contable
            $obBD_ins1->operacionobBD(10, $Pec_Cod['Pec_Cod'].'*'.$Prv_Cod.'*'.$Com_Num.'*'.$hoy.'*'.trim($Com_Con).'*'.$Tia_Asi.'*'.$Com_Val.'*'.trim($Aju_Obs).'*'.'Prv_Cod', $obBD_conexion_Ins);
            $Com_Cod= $obBD_ins1->insercionid($obBD_conexion_Ins->conexion);
            // Relacion ajuste comprobante
            $obBD_ins1->operacionobBD(17,$Com_Cod.'*'.$Aju_Cod, $obBD_conexion_Ins);
            $responce['linkCompr']="../../contabilidad/FRONT/con_pri_compr_1.1.php?codigo=$Com_Cod&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Asi&Pec_Cod=$Pec_Cod[Pec_Cod]";
            
            $asientosDebe=array();
            $asientosHaber=array();
            $asientoPlan=array(
                    'Com_Cod'=>$Com_Cod,
                    'Asi_Con'=>$Com_Con,
                    'Asi_Glo'=>'Ajus. No.'.$Aju_Num,
            );
            foreach ($saveForm AS $row){
               // if(empty($row['Aju_Imp'])) 
                $row['Aju_Imp']=round($row['Aju_Can']*$row['Aju_Pru'],2);    
                $cuentaDebe = $obBD_con1->getRowConsulta(19, $row['Pro_Cod'].'*'.$Tip_Pld.'*'.$row['Con_Cod'], $obBD_conexion);$addDebe=true;
                $cuentaHaber = $obBD_con1->getRowConsulta(12, $row['Pro_Cod'].'*'.'C', $obBD_conexion);$addHaber=true;
                if(empty($cuentaDebe['Pld_Cod'])||empty($cuentaDebe['Pld_Cod'])){
                    mysqli_rollback($obBD_conexion->conexion);
                    $responce['success']=false; $responce['message']='Revisar la parametrizacion contable de los productos';
                    echo json_encode($responce);
                    exit();
                }
                // cuenta debe
                for($i=0,$z=count($asientosDebe);$i<$z;$i++){
                    if($asientosDebe[$i]['Pld_Cod']==$cuentaDebe['Pld_Cod']){
                        $asientosDebe[$i]['Asi_Val']=$asientosDebe[$i]['Asi_Val']+$row['Aju_Imp'];
                        $addDebe=false;
                    }
                }
                if($addDebe){
                    $debe=array('Asi_Deh'=>($Tia_IoE=='E'?'D':'H'),'Asi_Val'=>$row['Aju_Imp'],'Pld_Cod'=>$cuentaDebe['Pld_Cod']);                    
                    array_push($asientosDebe,array_merge($asientoPlan,$debe));
                } 
                // cuenta haber
                for($i=0,$z=count($asientosHaber);$i<$z;$i++){
                    if($asientosHaber[$i]['Pld_Cod']==$cuentaHaber['Pld_Cod']){
                        $asientosHaber[$i]['Asi_Val']=$asientosHaber[$i]['Asi_Val']+$row['Aju_Imp'];
                        $addHaber=false;
                    }
                }
                if($addHaber){
                    $haber=array('Asi_Deh'=>($Tia_IoE=='E'?'H':'D'),'Asi_Val'=>$row['Aju_Imp'],'Pld_Cod'=>$cuentaHaber['Pld_Cod']);                    
                    array_push($asientosHaber,array_merge($asientoPlan,$haber));
                } 
            }
            $asiento=  array_merge($asientosDebe,$asientosHaber);
            //var_dump($asiento);
            foreach ($asiento AS $row)
                $obBD_ins1->operacionobBD(11,$row,$obBD_conexion_Ins); 
            
        }
    
    $obBD_ins1->fin_transaccion_nomsn($obBD_conexion_Ins->conexion);
    if($obBD_ins1->Error==0) $responce['success']=true; else {$responce['success']=false; $responce['message']=$obBD_ins1->MsgError;}
    echo json_encode($responce);
    exit();
}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>            
                <style>  
                    textarea { resize:vertical ; }
                    .txtRight{text-align: right;}
                    .ui-jqgrid .whiteI{background-color:white !important;}
                    select[name=Con_Cod]{width: 100%;}
                </style>  
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Ajuste de Inventario General</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">
                   
                    <div class="col-xs-4">
                        <form id="formKardex" class="form-horizontal normal"  action="javascript:validarForm();"  >
                             <input type="text" name="Mov_Tip" value="<?php echo $Mov_Tip; ?>" style="display: none" /> 
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Datos del Movimiento:</legend> <!-- Form Name -->
                            <!-- static input-->
                            <div class="form-group" >
                              <label class="col-sm-3 control-label label-xs ">Tipo:</label>  
                              <div class="col-sm-9">                                       
                                  <select name="Tip_Pld" id="Tip_Pld" class="form-control input-xs " required="" onchange="updateNumber(this.value);" >
                                      <option value="">Seleccione..</option>
                                      <option value="O">Costos</option>
                                      <option value="G">Gastos</option>
                                  </select>
                              </div>                                  
                            </div> 
                            <div class="form-group" style="display: none">
                              <label class="col-sm-3 control-label label-xs ">Movimiento:</label>  
                              <div class="col-sm-9">                                       
                                  <select name="Tia_IoE" id="Tia_IoE" class="form-control input-xs readOnly" required="" onchange="alertStock()" disabled="">
                                      <option value="">Seleccione..</option>
                                      <option value="I">Ingreso</option>
                                      <option value="E" selected>Egreso</option>
                                  </select>
                              </div>                                  
                            </div>   
                            
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Concepto:</label>  
                              <div class="col-sm-9">                                       
                                  <select name="Tia_Cod" id="Tia_Cod" class="form-control input-xs" required="">
                                      <option value="">Seleccione...</option> 
                                       <?php $rs_tpaj= $obBD_con1->getArrayConsulta(3, $Ses_Emp_Cod.'*'.'E', $obBD_conexion);                                        
                                        foreach ($rs_tpaj as $row) echo ("<option value='$row[Tia_Cod]' ".(startsWith ($row['Tia_Des'],'Consumo')?'selected':'').">$row[Tia_Des]</option>"); 
                                        ?>
                                  </select>
                              </div>                                  
                            </div>                            
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Número:</label>  
                              <div class="col-sm-9">   
                                  <input id="Aju_Num" name="Aju_Num" type="number" class="form-control input-xs nospin" required="" />                                 
                              </div>                                  
                            </div>
                            <!-- static input-->
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Descripción:</label>  
                              <div class="col-sm-9"> 
                                  <textarea name="Aju_Det" class="form-control input-xs" ></textarea>
                              </div>                                  
                            </div>   
                            <!-- static input-->
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Observación:</label>  
                              <div class="col-sm-9"> 
                                  <textarea name="Aju_Obs" class="form-control input-xs" ></textarea>
                              </div>                                  
                            </div> 
                            
                        </fieldset> 
                        <?php if($configuraciones['Cof_Con']=='S'){ ?>
                          <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Datos del Asiento:</legend> <!-- Form Name -->     
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Tipo Asiento:</label>  
                              <div class="col-sm-9">   
                                  <?php $asien = $obBD_con1->getArrayConsulta(9, '', $obBD_conexion); ?>
                                  <select name="Tia_Asi" id="Tia_Asi" class="form-control input-xs" required="">
                                      <?php foreach ($asien as $row) { ?>
                                            <option value="<?php echo $row['Tia_Cod']; ?>" <?php if($row['Tia_Des']=='DIARIO GENERAL')echo 'selected'; ?>><?php echo $row['Tia_Des']; ?></option> 
                                      <?php } ?>
                                  </select>
                              </div>                                  
                            </div>
                           <!-- static input-->
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Concepto:</label>  
                              <div class="col-sm-9"> 
                                  <textarea name="Com_Con" class="form-control input-xs" ></textarea>
                              </div>                                  
                            </div>  
                           </fieldset>    
                            <?php } ?>    
                            <div class="form-group center">
                                <button class="btn btn-success btn-sm btn-frm" type="submit"><span class="glyphicon glyphicon-check" title="Guardar"></span> Guardar</button>
                                <button class="btn btn-success btn-sm btn-new" type="button" onclick="resetForm()" disabled><span class="glyphicon glyphicon-check" title="Nuevo Registro"></span> Nuevo</button>
                            </div>   
                        </form>    
                    </div>    
                    <div class="col-xs-8" style="min-height: 350px;">
                        <table id="prods"></table>
                        <div id="prodsPager"></div>
                        <div style="padding-top: 10px;">
                            <button class="btn btn-success btn-sm btn-frm" onclick="$('#proDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Agregar Producto"></span> Seleccione Producto</button>                            
                        </div>
                        <script>//                            
                            var kardexGrid=$("#prods");
                            $(document).ready(function () {
                                $.createDialog('#successDialog',150,550);
                                <?php $cons = $obBD_con1->getArrayConsulta(18,$Ses_Emp_Cod,$obBD_conexion); ?>
                                kardexGrid.jqGrid({
                                    url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                    mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                                    //postData: $("#form1").getData("ajaxGrid"),
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,footerRow:true,
                                    caption:'Listado de Productos',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Cód.Int.', name: 'Index', key:true, hidden:true,viewable:true, width: 25,align:'center' }, 
                                        { label: 'Cód.Int.', name: 'Pro_Cod', hidden:false,viewable:true, width: 25,align:'center' }, 
                                        { label: 'Cód.Int.', name: 'Iva_Cod',hidden:true,viewable:false, width: 0,align:'center' }, 
                                        { label: 'Detalle',name: 'Ite_Lar', width: 150},                                        
                                        { label: 'Stock',name: 'Stk_Can', width: 40,classes:'columnHighlight3',align:'center'},
                                        { label: 'C. Consumo',name:'Con_Cod', width:55, editable: true, edittype:"select",formatter:'select', editoptions:{value:":Seleccione...<?php foreach($cons as $r){ echo ";$r[Con_Cod]:$r[Con_Des]"; } ?>"}},  
                                        { label: 'Cant.',name: 'Aju_Can', width: 40,classes:'columnHighlight2',editable:true,align:'center',editoptions: {dataInit:function(e){ e.style.textAlign = 'right';e.style.paddingRight = '5px';e.type='number'; e.className +=' nospin'; e.onkeyup=function(){updateSubt($(this).attr('rowId'));};}}},
                                        { label: 'Unid.',name: 'Uni_Des', width: 30},    
                                        { label: 'C. Unit.',name: 'Aju_Pru', width: 50,classes:'columnHighlight2 Tot',align:'right',editable:true,editoptions: {dataInit:function(e){ e.style.textAlign = 'right';e.style.paddingRight = '5px';e.type='number'; e.className +=' nospin'; e.onkeyup=function(){updateSubt($(this).attr('rowId'));}; }}},
                                        { label: 'C. Total',name: 'Aju_Imp', width: 50,classes:'columnHighlight2 Tot Total',align:'right',formatter:'currency', formatoptions: {prefix:'$', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum"},
                                        { label:'&nbsp;', name: 'act1', width: 15, align: 'center',viewable: false,
                                            formatter:function (cellvalue, options, rowObject) {
                                                
                                                var click='$(\'#prods\').jqGrid(\'delRowData\',\''+rowObject.Index+'\');';
                                                return  '<button type="button" class="btn btn-danger btn-xs btn-frm" title="Eliminar" onclick="'+click+'"><i class="glyphicon glyphicon-trash"></i></button>';                                                
                                            }
                                        }
                                           
                                    ],
                                    onSelectRow: function(){$(this).resetSelection();},
                                    footerrow: true, userDataOnFooter: false,
                                    rowNum: 10000000, pager: "#kardexPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null
                                }); 
                                var $footRow = $("#gbox_prods #gview_prods .ui-jqgrid-sdiv .footrow");
                                $footRow.find('>td:not(:last-child,:first-child,.Tot)').css("border-right-color", "transparent");
                                $footRow.find('>td:not(.Total)').addClass("whiteI");
                            });
                            function updateSubt(id){                                
                                var Can=$('#'+id+'_Aju_Can'),Pru=$('#'+id+'_Aju_Pru'); 
                                alertStock();
                                if(!isNaN(Can.val())&&!isNaN(Pru.val())) 
                                     kardexGrid.jqGrid("setCell", id, "Aju_Imp", (Can.val()*Pru.val()).toFixed(2));
                                else kardexGrid.jqGrid("setCell", id, "Aju_Imp", 0);
                                kardexGrid.setGridSummary(['Aju_Imp'],{Aju_Pru: '<div style="text-align:right;">TOTAL:</div>'});
                                //kardexGrid.jqGrid('footerData', 'set', { Aju_Imp:kardexGrid.jqGrid('getCol','Aju_Imp',false,'sum'),Aju_Pru: '<div style="text-align:right;">TOTAL:</div>'}); 
                            }
                            function alertStock(){
                                var ids=kardexGrid.jqGrid('getDataIDs'),prodss=[],state=true,desc='';
                                kardexGrid.find('tr td[aria-describedby=prods_Stk_Can]').removeClass("cellRed1 cellGreen1 cellBold");
                                if($('#Tia_IoE').val()==='E'){                                        
                                    $.each(ids,function (i,v){
                                        var Cant=$('#'+v+'_Aju_Can').val(),add=true,
                                            Stock=kardexGrid.jqGrid("getCell", v, "Stk_Can"),
                                            Pro_Cod=kardexGrid.jqGrid("getCell", v, "Pro_Cod");
                                        Cant=(!isNaN(Cant)?Cant*1:0);    Stock=(!isNaN(Stock)?Stock*1:0);
                                        if(Cant>Stock) kardexGrid.jqGrid('setCell',v,"Stk_Can","",'cellRed1 cellBold'); 
                                        else{ kardexGrid.jqGrid('setCell',v,"Stk_Can","",'cellGreen1 cellBold');  }
                                        for(var j=0;j<prodss.length;j++){
                                            if(prodss[j]['Pro_Cod']===Pro_Cod){ prodss[j]['Pro_Can']=prodss[j]['Pro_Can']+Cant;add=false; }
                                        }
                                        if(add) prodss.push({Index:v,Pro_Cod:Pro_Cod,Pro_Stk:Stock,Pro_Can:Cant});
                                    });
                                    for(var j=0;j<prodss.length;j++){
                                        if(prodss[j]['Pro_Can']>prodss[j]['Pro_Stk']){ state=false;
                                            $.each(ids,function (i,v){                                                
                                                if(prodss[j]['Pro_Cod']===kardexGrid.jqGrid("getCell", v, "Pro_Cod")){
                                                    kardexGrid.find('tr#'+v+' td[aria-describedby=prods_Stk_Can]').removeClass("cellRed1 cellGreen1 cellBold");
                                                    kardexGrid.jqGrid('setCell',v,"Stk_Can","",'cellRed1 cellBold');
                                                }   
                                            }); 
                                        }
                                    }
                                    
                                } return state;   
                            }
                            function updateNumber(val){
                                $.post("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{Aju_Tip:val,updateNumber:true}, function(response) { 
                                    if(response['success']) $('#Aju_Num').val(response['next']['Aju_Num']);
                                },'json');
                            }
                        </script>    
                    </div>  
                </div> 
        </div>
    </div>
<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="proDialog" title="B&uacute;squeda de Productos">  
        <form class="form-horizontal normal"> 
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-5 radioset" >
                          <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                          <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>                          
                    </div>                  
                       
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>  
                    <div class="col-md-7" >
                        <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus  class="form-control input-sm "/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Producto" ><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                      </div><!-- /input-group --> 
                    </div>                    
                </div>
        </fieldset>  
       </form> 
    </div> 
<!-- FIN DEL DIALOGO CUENTAS--> 
<script>
        // DIALOG BUSCAR CUENTAS            
             $.createSearchDialog('proDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 15,align:"center",hidden:true },                                
                    { label: 'Descripción', name: 'Ite_Lar', width: 110 },                      
                    { label: 'Marca', name: 'Mar_Des', width: 40},
                    { label: 'Tipo', name: 'Cat_Des', width: 110,align:"center" },
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 20, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { return $.getGridButton(addFilaMat,rowObject); }
                        }
                ]);
            
            function addFilaMat(data){ //console.log(data);
                var next=kardexGrid.jqGrid('getCol','Index',false,'max'); next=($.varValid(next)?next+1:1);
                //if(!kardexGrid.existsId(data['Pro_Cod'])/*&& data['Pro_Cod']!==$('#Fin_Cod').val()*/){
                    data['Aju_Can']=1;  data['Index']=next;
                    kardexGrid.jqGrid("addRowData", next, data, "last");        
                    kardexGrid.startGridEdit();
                    kardexGrid.setGridSummary(['Aju_Imp'],{Aju_Pru: '<div style="text-align:right;">TOTAL:</div>'});
                    //kardexGrid.jqGrid('footerData', 'set', { Aju_Imp:kardexGrid.jqGrid('getCol','Aju_Imp',false,'sum'),Aju_Pru: '<div style="text-align:right;">TOTAL:</div>'});
                    alertStock();
                //}else{ $.alert('Ya se encuentra en el listado!'); }
            }                   
</script>                
<script>
    function validarForm(){
        if(!alertStock()){$.alert('Revise que las cantidades a consumir no sean mayores al <i>STOCK</i>! ');$("#prods").startGridEdit();return false;}
        var data=$('#formKardex').serializeObject(),ban1='',ban2='',ban3='';        
        data['saveForm']=$("#prods").getGridBatch();  data['Tia_IoE']='E';
        if(data['saveForm'].length===0){$.alert('Seleccione al menos un producto');$("#prods").startGridEdit();return false;}
        for(var i=0;i<data['saveForm'].length;i++){  
            data['saveForm'][i]['act1']=null;
            if(('0'+data['saveForm'][i]['Aju_Pru'])*1===0 || ('0'+data['saveForm'][i]['Aju_Can'])*1===0)
                {ban1=data['saveForm'][i]['Ite_Lar'];break;}
            if(data['Tia_IoE']==='E' && ('0'+data['saveForm'][i]['Aju_Can'])*1>('0'+data['saveForm'][i]['Stk_Can'])*1)
                {ban2=data['saveForm'][i]['Ite_Lar'];break;}
            if(data['Tia_IoE']==='E' && data['saveForm'][i]['Con_Cod']==="")
                {ban3=data['saveForm'][i]['Ite_Lar'];break;}
        }
        if(ban1!==''){$.alert('La <u>Cantidad/Precio</u> de <u>'+ban1+'</u> no son correctos! ');$("#prods").startGridEdit();return false;}
        if(ban2!==''){$.alert('La <u>Cantidad</u> del <u>CONSUMO</u> de <u>'+ban2+'</u> no puede ser menor que el <u>STOCK</u>! ');$("#prods").startGridEdit();return false;}
        if(ban3!==''){$.alert('Debe <i>Selecionar</i> el <u>C. Consumo</u> de <u>'+ban3+'</u> ! ');$("#prods").startGridEdit();return false;}
        
        $.createDialogConfirm('Esta seguro que desea guardar el  <b>Ingreso</b>.',data,guardar,function (){$("#prods").startGridEdit();});
    }
    function guardar(data){
        $('.btn-frm').attr('disabled','disabled');
        $('#loader').show();
        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( response ) {
            //console.log(response['success']);
            if(response['success']===true){
                $('.btn-new').removeAttr('disabled');  
                $('#proGrid').trigger('reloadGrid', [{ page: 1 }]);
                $('#impAjust').attr('href',response['linkAjust']); 
                <?php if($configuraciones['Cof_Con']=='S'){ ?>
                $('#impCompr').attr('href',response['linkCompr']); 
                $('#successDialog').dialog('open');
                <?php } ?>
            }else{ $('.btn-frm').removeAttr('disabled');$.alert(response['message']);$("#prods").startGridEdit(); }
            //console.log(data);
        },'json').fail(function(error) { $('.btn-frm').removeAttr('disabled');$.alert("El Servidor ha fallado en responder!");$("#prods").startGridEdit(); })
                .always(function() {$("#loader").fadeOut("slow");});   

    }
    function resetForm(){
        $('#formKardex')[0].reset();
        $('#proGrid').trigger('reloadGrid', [{ page: 1 }]);
        $("#prods").clearGrid();
        $('.btn-new').attr('disabled','disabled');  
        $('.btn-frm').removeAttr('disabled');
    }
</script>
 
<!--INICIO DEL DIALOGO IMPRIMIR -->          
    <div id="successDialog"  title="Mensaje del Sistema">  
        <center><h4>El Ajuste se registrado con exito!</h4></center>
        <center> 
            <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cerrar</span>
             </button>            
            <a id="impAjust" target="_blank" href=""  style="display: inline;" title="Imprimir Ajuste"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Ajuste</span></span> </a>
            <?php if($configuraciones['Cof_Con']=='S'){ ?>
            <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Comprobante</span></span> </a>
            <?php } ?>   
        </center>        
    </div>  

</BODY>
</HTML>