<?php	
/**
* @abstract Permite CONSULTAR de comprobantes por abonos
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
if(isset($ajaxComprobante)){         
    $responce['rows'] = $obBD_con1->getArrayConsulta(41, $Ses_Emp_Cod.'*'.$Cli_Cod.'*'.$Pec_Cod.'*'.$txt_fec_ini.'*'.$txt_fec_fin.'*'.$op_opciones, $obBD_conexion);	          
    
    
    $responce['success']=true;$responce['records']=count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
//if(isset($ajaxGrid)){ 
//    $Ite_Cod=$Pro_Cod;
//    
//    $responce['rows']=$obBD_con1->getArrayConsulta(23,$Ses_Emp_Cod.'*'.$Ses_Suc_Cod.'*'.$Ite_Cod, $obBD_conexion);
//    $responce['success']=true;$responce['records']=count($responce['rows']);
//    utf8_encode_deep($responce['rows']);
//    echo json_encode($responce);exit();
//}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
                <style>                     
                     .txtRight{text-align: right;}
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Cheques Recibidos</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                            <div class="col-sm-6">
                                <form id="provFormTemp" class="form-horizontal normal">
                                    <fieldset class="exa-fieldset">
                                        <!-- Form Name -->
                                        <legend class="Titulos2">Seleccione Cliente</legend>
                                        <!-- SEARCH -->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label label-xs" for="radios">C�dula/R.U.C.:</label>
                                            <div class="col-md-6">      
                                              <div class="input-group input-group-xs">
                                                <input type="text" name="op_opciones" value="c" style="display: none;" /> 
                                                <input type="hidden" name="Cli_Cod" id="PrvCodBus" value="" />  
                                                <input id="docu" name="search" maxlength="13" onkeydown='if (event.keyCode === 13) $.SearchOrDialog("#provDialog",selectProvee);'  type="text" class="form-control" placeholder="Ingrese Cedula/R.U.C. ..."  autofocus />
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success" onclick="$('#provDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                                                </span>
                                              </div><!-- /input-group -->
                                            </div>
                                            <div class="col-md-1"><a onclick="selectProvee();" title="Quitar Proveedor" class="btn btn-success btn-xs pull-right"><i class="glyphicon glyphicon-new-window"></i></a></div> 
                                         </div>
                                        <div  class="form-group">
                                            <label class="col-md-3 control-label label-xs">Cliente:</label>  
                                            <div class="col-md-9">
                                                  <input  id="lblProv" name="textinput" type="text" class="form-control input-xs " readonly>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-3 control-label label-xs">Direcci�n:</label>  
                                            <div class="col-md-9">
                                                <input id="lblDirec" name="textinput" type="text" class="form-control input-xs " readonly>
                                            </div>
                                        </div>                                
                                    </fieldset>
                                </form>
                            </div>
                            <div class="col-sm-6">
                                 <form  id="formCompTemp" action="javascript:$('#list').Search('#formCompTemp','ajaxComprobante');setCaption();" class="form-horizontal normal">
                                    <input type="hidden" name="Cli_Cod" value="" />          
                                    <fieldset class="exa-fieldset">
                                        <!-- Form Name -->
                                        <legend class="Titulos2">Filtros</legend>
                                         <div class="col-sm-10">
                                        <!-- Multiple Radios (inline) -->
                                            <div class="form-group">
                                              <label class="col-md-3 control-label label-xs " for="radios">Filtrar por:</label>
                                              <div class="col-md-9"> 
                                                  <div class="radioset">
                                                    <input id="radio1" name="op_opciones" type="radio" value="T" alt="" checked><label class="col-xs-4" for="radio1">Todos</label>
                                                    <input id="radio2" name="op_opciones" type="radio" value="C" alt="" ><label class="col-xs-4" for="radio2">A la Vista</label>
                                                    <input id="radio3" name="op_opciones" type="radio" value="P" alt="" ><label class="col-xs-4" for="radio3">PostFecha</label>
                                                 </div>   
                                              </div>
                                            </div>
                                           
                                             <div id="rangeDates"  class="form-group">
                                                 
                                                 <label class="col-sm-3 control-label label-xs " for="selectbasic"><span id="fecMsg">Desde:</span></label>
                                              <div class="col-sm-4">
                                                  <input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="form-control input-xs" style="text-align: center;"  />
                                              </div>    
                                            </div>   
                                        </div>
                                        <div class="col-md-2" style="padding-top: 20px;">
                                             <div class=""><button type="button"  onclick="this.form.submit()" class="btn btn-sm btn-success" title="Ejecutar B�squeda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
                                        </div>
                                      
                                    </fieldset>
                                 </form>
                            </div>                          
                                              
                </div> 
                <div class="row">                    
                    <div class="col-xs-12" style="min-height: 350px;">
                        <table id="list"></table>
                        <div id="listPager"></div>
                        <script>
                             $(document).ready(function () {                                 
                                $.createDatePickers('#txt_fec_fin');
                                //$('#rangeDates').addClass('disabled').find('input').attr('disabled','disabled');
                                var kardexGrid=$("#list");
                                kardexGrid.jqGrid({
                                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                    mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
                                    postData: $("#form1").getData("ajaxGrid"),
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,
                                    caption:' ',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Cod.Int.', name: 'Vet_Cod', key: true, hidden:true,viewable:false,width:20 },
                                        { label: 'Cod.Int.', name: 'Cpc_Cod', key: true, hidden:true,viewable:false,width:20 },
                                        { label: 'Cod.Int.', name: 'Com_Cod', key: true, hidden:true,viewable:false,width:30,align:"center" },
                                        { label: 'Comp.', name: 'Com_Codigo', width: 40,align:"center"  }, 
                                        { label: 'Fecha', name: 'Cpc_Fec', width: 40,align:"center"  },
                                        { label: 'Docu.', name: 'Vet_Num', width: 80,align:"center"  }, 
                                        { label: 'Cliente', name: 'proveedor', width: 180  },                                         
                                        { label: 'Tipo',name: 'Pag_Des', width: 50,classes:'columnHighlight2',align:"center"},
                                        { label: 'Banco', name: 'Banco', width: 125  },
                                        { label: 'Num.', name: 'Che_Num', width: 35,align:"center"  }, 
                                        { label: 'Fec.Ch.', name: 'Che_Fec', width: 50,align:"center"  }, 
                                        { label: 'Valor',name: 'Cpc_Val', width: 40,classes:'columnHighlight2',align:"right", formatter:'currency',summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'},
                                        
//                                            { label:'&nbsp;', name: 'act1', width: 55, align: 'center',viewable: false,title: false,
//                                                formatter:function (cellvalue, options, rowObject) {   
//                                                    var selectBut='<span class="btn btn-success btn-xs" title="Seleccionar" type="button" onclick="SelectMesc($(\'#kardex\').jqGrid (\'getRowData\', \''+rowObject.Mes_Cod+'\'))"><i class="glyphicon glyphicon-arrow-right"></i></span>';
//                                                    return  '<span class="btn btn-info btn-xs" title="Ver" type="button" onclick="$(\'#kardex\').viewGridRow(\''+rowObject.Mes_Cod+'\');"><i class="glyphicon glyphicon-info-sign"></i></span><span>&nbsp;&nbsp;</span>'+
                                                            //'<span class="btn btn-primary btn-xs" title="Imprimir Mescla" type="button" onclick="window.open(\'/facturacion/FRONT/fac_pri_mesclas_1.0.php?Mes_Cod='+rowObject.Mes_Cod+'\');"><i class="glyphicon glyphicon-print"></i></span><span>&nbsp;&nbsp;</span>'+
//                                                             selectBut; 
//                                                }
//                                            }
                                    ],     
                                    footerrow: true, userDataOnFooter: false,
                                    rowNum: 10000000, pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,
                                    loadComplete: function () {                       
                                            kardexGrid.jqGrid('footerData', 'set', { Cpc_Val:kardexGrid.jqGrid('getCol','Cpc_Val',false,'sum'),Pag_Des:'<span style="float:right">TOTALES</span>'});                     
                                    }
                                });  
                                
                                kardexGrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: false, view: false, position: "left", cloneToTop: false });

                               
                            });                             
                        </script>    
                    </div>
                    <div class="col-xs-12">
                        <button id="btnExport" style="margin-top: 10px" class="btn btn-success btn-sm" onclick="$('#list').jqGrid('exportGridExcel');" type="button">
                        Exportar a Excel <i class="glyphicon glyphicon-download"></i>
                    </button>
                    </div>
                </div> 
            
        </div>
    </div>

   
<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="B�squeda de Clientes">  
     <form class="form-horizontal normal"> 
        <fieldset class="exa-fieldset">
		<legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-8 radioset" >
                          <input id="rad1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                          <input id="rad2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad2">&nbsp;&nbsp;Cédula/R.U.C.&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B�squeda:</label>
                    <div class="col-md-7" >                 
                      <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cliente a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                      </div><!-- /input-group -->                          
                    </div>                    
                </div>
        </fieldset>  
       </form>    
    </div>
    <script type="text/javascript">
        $(document).ready(function() {               
                $.createSearchDialog('#provDialog',[
                        { label: 'C�d.Int.', name: 'Cli_Cod', key: true,hidden:true,viewable: true },                                
                        { label: 'C�dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                        { label: 'Cliente', name: 'cliente', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                        { label: 'Direcci�n', name: 'Prs_Dir',hidden:true,viewable: true },                      
                            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                formatter:function (cellvalue, options, rowObject) { 
                                    var clic='selectProvee($("#provGrid").jqGrid("getRowData",'+rowObject.Cli_Cod+'))';
                                    return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                                }
                            }
                    ]);  
                                     
        }); 
    function selectProvee(data){                           
        if(typeof data==='undefined'){
            $("#lblProv").val('');
            $("#lblDirec").val('');
            $("input[name='Cli_Cod']").val('');
            $("#docu").val('');
            $('#PrvCodBus').val('');
            $('#list').Search('#formCompTemp','ajaxComprobante');
        }else{
        
            $("#lblProv").val(data['cliente']);
            $("#lblDirec").val(data['Prs_Dir']);
            $("input[name='Cli_Cod']").val(data['Cli_Cod']);
            $("#docu").val(data['Prs_Ced']);        
            $("#provDialog").dialog("close");        
            $('#list').Search('#formCompTemp','ajaxComprobante');
            //$("#docu").attr("readOnly","readOnly");
       }
        setCaption();
    } 
    $("input:radio[name ='op_opciones']").click(function() {
        if($("input:radio[name ='op_opciones']:checked").val()==='P')
            $('#fecMsg').html('Hasta:');
        else
            $('#fecMsg').html('Desde:');
    });
    function setCaption(){        
        var caption='';
        caption="Historial de Cheques";
        if($("input:radio[name ='op_opciones']:checked").val()==='P') caption=caption+' Postfechados';
        if($("input:radio[name ='op_opciones']:checked").val()==='C') caption=caption+' A la vista';
        if($("input:radio[name ='op_opciones']:checked").val()==='P')
           caption=caption+(' - Hasta: ');
        else
           caption=caption+(' - Desde: ');
        caption=caption+$('#txt_fec_fin').val()
        
        if($('#PrvCodBus').val()!=='') caption=caption+' - '+$('#lblProv').val();
        $('#list').jqGrid('setCaption', caption);
    }
    </script>
<!-- FIN DEL DIALOGO PROVEEDOR-->
<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>             
</BODY>
</HTML>