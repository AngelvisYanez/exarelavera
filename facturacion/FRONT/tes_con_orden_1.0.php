<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_produ.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Produ($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Produ;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($ajaxGrid)){ 
    $Ite_Cod=$Pro_Cod;
    
    $responce['rows']=$obBD_con1->getArrayConsulta(24,$Ses_Emp_Cod.'*'.$Ses_Suc_Cod.'*'.$Ite_Cod, $obBD_conexion);
    $responce['success']=true;$responce['records']=count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
                <TITLE><?Php echo "Producción Orden Consultar [EXA]"; ?></TITLE>
                <meta charset= "UTF-8">
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
                <style>                     
                     
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Orden de Producción</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">
                   <?php if(!isset($Mes_Cod)||$Mes_Cod==''){ ?>
                    <div class="col-xs-12">
                        <form  class="form-horizontal normal" id="form1"  >
                           <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Búsqueda:</legend> <!-- Form Name -->
                              <div class="row">  
                                    
                              </div>
                           </fieldset>
                        </form>
                    </div> 
                    <form id="FormMes" name="FormMes" method="post" style="display: none"  >
                        <input type="text" name="Ord_Cod" id="BackOrd_Cod" /> 
                        <input type="text" name="Mes_Cod" id="BackMes_Cod" />  
                        <input type="text" name="Pro_Cod" id="BackPro_Cod" />  
                    </form>
                    <div class="col-xs-12" style="min-height: 350px;">
                        <table id="kardex"></table>
                        <div id="kardexPager"></div>
                        <script>
                             $(document).ready(function () {
                                $.createDateRange('#ini','#fin');
                                var kardexGrid=$("#kardex");
                                kardexGrid.jqGrid({
                                    url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                    mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
                                    postData: $("#form1").getData("ajaxGrid"),
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,
                                    caption:' ',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Cod.Int.', name: 'Ord_Cod', key: true, hidden:false,viewable:true,width:20 },                                         
                                        { label: 'Cod.Int.', name: 'Mes_Cod', hidden:true,viewable:false,width:20 },                                         
                                        { label: 'Cod.Int.', name: 'Pro_Cod', hidden:true,viewable:false,width:20 },                                         
                                        { label: 'Fecha', name: 'Ord_Fec', width: 40,align:"center"},
                                        { label: 'Formula', name: 'Mes_Nom', width: 100  }, 
                                        { label: 'Observacion', name: 'Ord_Obs', width: 150  }, 
                                        { label: 'Resultado',name: 'Ite_Lar', width: 150,classes:'columnHighlight2'},
                                        { label: 'Max. Lote',name: 'Mes_Max', width: 40,align:"center"},
                                        { label: 'Producción',name: 'Ord_Res', width: 40,align:"center"},
                                        { label: 'Costo Uni.',name: 'Ord_Cou', width: 50,align:"right", formatter: 'currency', formatoptions: { prefix: '$ ', suffix: '', thousandsSeparator: ',',decimalSeparator:'.'}},
                                            { label:'&nbsp;', name: 'act1', width: 50, align: 'center',viewable: false,title: false,
                                                formatter:function (cellvalue, options, rowObject) {   
                                                    var selectBut='<span class="btn btn-success btn-xs" title="Seleccionar" type="button" onclick="SelectMesc($(\'#kardex\').jqGrid (\'getRowData\', \''+rowObject.Ord_Cod+'\'))"><i class="glyphicon glyphicon-arrow-right"></i></span>';
                                                    return  '<span class="btn btn-info btn-xs" title="Ver" type="button" onclick="$(\'#kardex\').viewGridRow(\''+rowObject.Ord_Cod+'\');"><i class="glyphicon glyphicon-info-sign"></i></span><span>&nbsp;&nbsp;</span>'+
                                                             selectBut; 
                                                }
                                            }
                                    ],     
                                    //footerrow: true, userDataOnFooter: false,
                                    rowNum: 10000000, pager: "#kardexPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,                           
                                });  
                                
                                kardexGrid.navGrid('#kardexPager',{ edit: false, add: false, del: false, search: false, refresh: false, view: false, position: "left", cloneToTop: false });

                               
                            }); 
                            function SelectMesc(data){
                                $('#BackOrd_Cod').val(data['Ord_Cod']);
                                $('#BackMes_Cod').val(data['Mes_Cod']);
                                $('#BackPro_Cod').val(data['Pro_Cod']);
                                $('#FormMes').formSubmit();
                                //console.log(JSON.parse(data));
                            }
                        </script>    
                    </div> 
                    <?php }else{ ?>
                    <?php 
                        $Ite_Cod=$Pro_Cod;
                        $prod = $obBD_con1->getRowConsulta(3,$Ite_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
                        $orden = $obBD_con1->getRowConsulta(24,'Ord_Cod='.$Ord_Cod, $obBD_conexion);
                        $mescla = $obBD_con1->getRowConsulta(23,'Mes_Cod='.$Mes_Cod, $obBD_conexion);
                        $materiales = $obBD_con1->getArrayConsulta(27,$Ord_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
                        //echo json_encode($materiales);
                    ?> 
                    <div class="col-xs-12">
                       <form class="form-horizontal normal"    >
  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Artículo a Producir:</legend> <!-- Form Name -->
                              <div class="row">                                  
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Descripción:</label>  
                                          <div class="col-xs-8"> 
                                                                              
                                                <span  class="form-control input-xs" id="producto"><?php echo $prod['Ite_Lar']; ?></span>                              
                                             
                                            <!--<div class="input-group input-group-xs">      
                                                <input id="producto"  type="text" class="form-control" placeholder="Seleccione un Producto ..." required readonly />
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-frm" onclick="$('#proDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                                                </span>
                                              </div>-->
                                          </div>
                                        </div>                                      
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Marca:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_mar"><?php echo $prod['Mar_Des']; ?></span>                              
                                          </div>                                  
                                        </div>
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Adquisición:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_adq"><?php echo $prod['Adq_Des']; ?></span>                              
                                          </div>                                  
                                        </div>
                                  </div>
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Categoria:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_cat"><?php echo $prod['Cat_Des']; ?></span>                              
                                          </div>                                  
                                        </div>                                      
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Cod. Cat.:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="cat_cod"><?php echo $prod['Cat_Cod']; ?></span>                              
                                          </div>                                  
                                        </div>
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Observación:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_obs"><?php echo $prod['Pro_Obs']; ?></span>                              
                                          </div>                                  
                                        </div>
                                  </div>
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-4 control-label label-xs ">Stock:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs txtRight" id="pro_stk"><?php echo $prod['Stk_Can']; ?></span>                              
                                          </div>                                  
                                        </div>                                      
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-4 control-label label-xs ">Prec Prom.:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs txtRight" id="pro_pre"><?php echo $prod['Pre_Pvp']; ?></span>                              
                                          </div>                                  
                                        </div>
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-4 control-label label-xs ">Saldo Actual:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs txtRight" id="pro_sal"><?php echo $prod['Stk_Can']*$prod['Pre_Pvp']; ?></span>                              
                                          </div>                                  
                                        </div>
                                  </div>                                  
                              </div>  
                        </fieldset> 
                        
                                                           
                           
                                
                        </form>  
                    </div>
                    <div class="col-xs-12" style="min-height: 350px;">
                        <form id="formKardex" class="form-horizontal normal"  action="javascript:guardar()"   >
                             <input type="text" name="Pro_Cod" id="Pro_Cod" value="" style="display: none" /> 
                             <input type="text" name="Iva_Cod" id="Iva_Cod" value="" style="display: none" /> 
                        <div class="row">  
                           
                                <div class="col-xs-4">
                                    <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Formula:</legend> <!-- Form Name -->
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs ">Formula:</label>  
                                          <div class="col-sm-9">   
                                              <span  class="form-control input-xs" id="pro_mar"><?php echo $orden['Mes_Nom']; ?></span>                              
                                          </div>                                  
                                        </div>
                                        <!-- static input-->
                                        <div class="form-group">                                          
                                          <label class="col-sm-3 control-label label-xs ">Mesclador(es):</label>  
                                          <div class="col-sm-9">                                    
                                              <span  class="form-control input-xs" id="pro_mar"><?php echo $orden['Ord_Mes']; ?></span>                                                            
                                          </div>                                         
                                        </div>
                                    </fieldset>  
                                    <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Datos Generales:</legend> <!-- Form Name -->
                                        <!-- static input-->
                                        <div class="form-group">                                          
                                          <label class="col-sm-4 control-label label-xs ">Fecha:</label>  
                                          <div class="col-sm-4">                                    
                                              <span  class="form-control input-xs" id="pro_mar"><?php echo $orden['Ord_Fec']; ?></span>   
                                          </div>                                         
                                        </div>
                                        <!-- static input-->
                                        <div class="form-group">                                          
                                          <label class="col-sm-4 control-label label-xs ">Cliente:</label>  
                                          <div class="col-sm-8">
                                              <span  class="form-control input-xs" id="pro_mar"><?php echo $orden['Prs_Ape'].' '.$orden['Prs_Nom']; ?></span> 
                                          </div>                                         
                                        </div>
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-4 control-label label-xs ">Descripción:</label>  
                                          <div class="col-sm-8"> 
                                              <textarea id="Mes_Des" name="Ord_Obs" class="form-control input-xs"><?php echo $orden['Ord_Obs']; ?></textarea>
                                          </div>                                  
                                        </div>
                                    </fieldset>      
                                    <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Cantidad a Producir:</legend> <!-- Form Name -->
                                        
                                        <!-- static input-->
                                        <div class="form-group">
                                          
                                          <label class="col-sm-5 control-label label-xs ">Cant. a Producir:</label>  
                                          <div class="col-sm-3">                                    
                                              <input type="text"  id="Ord_Res" name="Ord_Res" class="form-control input-xs" style="text-align:right" value="<?php echo $orden['Ord_Res']; ?>" readonly="" />                              
                                          </div>
                                          <div class="col-sm-3">                                    
                                              <span  class="input-xs disabled uni_des" ><?php echo $prod['Uni_Des']; ?></span>
                                          </div>
                                        </div> 
                                        <!-- static input-->
                                        <div class="form-group">
                                          
                                            <label class="col-sm-5 control-label label-xs ">Max. Cant. Lote:</label>  
                                          <div class="col-sm-3">                                    
                                              <input type="text"  id="Ord_Max" name="Ord_Max" class="form-control input-xs" style="text-align:right" value="<?php echo $orden['Mes_Max']; ?>" readonly="" required="" />                              
                                          </div>
                                          <div class="col-sm-3">                                    
                                              <span  class="input-xs disabled uni_des" ><?php echo $prod['Uni_Des']; ?></span>
                                          </div>
                                        </div>  
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-5 control-label label-xs ">Lotes a Producir:</label>  
                                          <div class="col-sm-3">    
                                              <input type="text" id="Ord_Lot" name="Ord_Lot" class="form-control input-xs" style="text-align:right" value="<?php echo ceil($orden['Ord_Res']/$orden['Mes_Max']); ?>" readonly=""  required=""/>
                                          </div> 
                                          
                                          <div class="col-sm-3">                                    
                                              <span  class="input-xs disabled">LOTES</span>
        
                                          </div>
                                        </div>                                         
                                        <!-- static input-->
                                        <div class="form-group">
                                          
                                          <label class="col-sm-5 control-label label-xs ">Costo Unitario:</label>  
                                          <div class="col-sm-4">
                                              <div class="input-group input-group-xs">
                                                    <span class="input-group-addon"> $ </span>
                                                    <input type="text"  id="Ord_Cou" name="Ord_Cou" class="form-control input-xs" style="text-align:right" value="<?php echo formato_numero($orden['Ord_Cou'],2,2); ?>" onchange="calcCosto()" readonly=""  />                              
                                              </div>          
                                          </div>                                          
                                        </div>  
                                        <!-- static input-->
                                        <div class="form-group">
                                          
                                          <label class="col-sm-5 control-label label-xs ">Costo Total:</label>  
                                          <div class="col-sm-4"> 
                                               <div class="input-group input-group-xs">
                                                    <span class="input-group-addon"> $ </span>
                                                    <input type="text"  id="Ord_Tot" name="Ord_Tot" class="form-control input-xs" style="text-align:right" value="<?php echo formato_numero($orden['Ord_Res']*$orden['Ord_Cou'],2,2); ?>" required="" readonly="" />                              
                                               </div>     
                                          </div>                                          
                                        </div> 
                                        <!--                                        
                                        <div class="form-actions center" style="padding-top: 5px;">
                                            <button class="btn btn-success btn-sm btn-frm" type="submit"><span class="glyphicon glyphicon-floppy-disk" title="Buscar Proveedor"></span> Guardar</button>
                                            <button class="btn btn-success btn-sm btn-new" type="button" onclick="resetForm()" disabled><span class="glyphicon glyphicon-check" title="Nuevo Registro"></span> Nuevo</button>
                                        </div>
                                        -->
                                    </fieldset>
                                </div>
                           
                            <div class="col-xs-8">
                                     
                                <table id="prods"></table>
                                <div id="prodsPager"></div>
                                <!--<div style="padding-top: 10px;">
                                    <button class="btn btn-success btn-sm btn-frm" onclick="$('#matDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span> Seleccione Materia Prima</button>
                                </div>-->
                            </div>
                        </div>  
                        </form>    
                        <script>
                            var mesclas;
                            $(document).ready(function () {
                                $.createDatePickers('#Ord_Fec');
                                var kardexGrid=$("#prods");
                                kardexGrid.jqGrid({
                                    url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                    mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                                    //postData: $("#form1").getData("ajaxGrid"),
                                    data:<?php echo json_encode($materiales); ?>,
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,
                                    caption:'Listado de Materias Prima',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Cod.Int.', name: 'Ord_Cod', key: true, hidden:true,viewable:true, width: 20,align:'center' }, 
                                        { label: 'Cod.Int.', name: 'Pro_Cod', key: true, hidden:true,viewable:true, width: 20,align:'center' }, 
                                       
                                        { label: 'Detalle',name: 'Ite_Lar', width: 150},   
                                        //{ label: 'Stock',name: 'Stk_Can', width: 20,align:'right'}, 
                                        { label: 'Cant.',name: 'Ord_Can', width: 20,classes:'columnHighlight2',editable:true,align:'right',editoptions: {dataInit:function(e){ e.style.textAlign = 'right';e.style.paddingRight = '5px';}}},
                                        { label: 'Unid.',name: 'Uni_Des', width: 20}, 
                                        { label: 'C.Unit.',name: 'Pro_Cou', width: 30,align:'right', formatter:'number'}, 
                                        { label: 'C.Total',name: 'Pro_Tot', width: 30,align:'right', formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}}, 
                                        //{ label: 'C. Unit.',name: 'Doc', width: 20,classes:'columnHighlight2'},
                                        //{ label: 'C. Total',name: 'Doc', width: 20,classes:'columnHighlight2'},
                                        
                                        { label: 'Iva Cod', name: 'Iva_Cod', hidden:true}
                                                                                
                                        
                                       
                                    ],     
                                    footerrow: false, userDataOnFooter: false,
                                    rowNum: 10000000, pager: "#kardexPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null                         
                                });
                                $('#Ord_Res').on('change',function (){
                                    var mescla=getMescla($('#Mes_Cod').val()),grid=$('#prods');
                                    if(mescla!==null){ 
                                        $("#Ord_Lot").val(Math.ceil(this.value/mescla['Mes_Max']));
                                        for(var i=0;i<mescla['formula'].length;i++){
                                            grid.jqGrid("setCell", mescla['formula'][i]['Pro_Cod'], "Mes_Can", mescla['formula'][i]['Mes_Can']*this.value);
                                            
                                        }                                        
                                    }
                                    calcCosto();
                                });
                            });
                        </script>    
                    </div>
                 <?php } ?>   
                </div> 
        </div>
    </div>
<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="proDialog" title="B&uacute;squeda de Productos">  
        <form class="form-horizontal normal"> 
        <fieldset>
		<legend>Filtros</legend>
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
<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="matDialog" title="B&uacute;squeda de Materia Prima">  
        <form class="form-horizontal normal"> 
            <input type="text" id="Fin_Cod" name="Fin_Cod" style="display:none" value="" />
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-5 radioset" >
                          <input id="radcd1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radcd1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                          <input id="radcd2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radcd2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>                          
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
<!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog"  title="Mensaje del Sistema">  
        <center><h4>La Orden de Producción se ha registrado con Exito!</h4></center>  
        <center> 
            <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cerrar</span>
             </button>            
            <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Orden de Producción"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></span> </a>
               
        </center>        
    </div>
<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="Búsqueda de Clientes">  
      <form class="form-horizontal normal"> 
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-8 radioset" >
                          <input id="rad1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                          <input id="rad2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad2">&nbsp;&nbsp;Cédula/R.U.C.&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7" >                 
                      <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cliente a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Cliente" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                      </div><!-- /input-group -->                          
                    </div>                    
                </div>
        </fieldset>  
       </form>    
    </div>
    <script type="text/javascript">
        $(document).ready(function() {               
                $.createSearchDialog('#provDialog',[
                        { label: 'Cód.Int.', name: 'Cli_Cod', key: true,hidden:true,viewable: true },                                
                        { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                        { label: 'Proveedor', name: 'cliente', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                        { label: 'Dirección', name: 'Prs_Dir',hidden:true,viewable: true },                      
                            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                formatter:function (cellvalue, options, rowObject) { 
                                    var clic='selectProvee($("#provGrid").jqGrid("getRowData",'+rowObject.Cli_Cod+'))';
                                    return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                                }
                            }
                    ]);  
                $.createDialog('#successDialog',150,550);                       
        }); 
    </script>
<script>
        // DIALOG BUSCAR CUENTAS            
             $.createSearchDialog('proDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 15,align:"center",hidden:true },                                
                    { label: 'Descripción', name: 'Ite_Lar', width: 110 },                      
                    { label: 'Marca', name: 'Mar_Des', width: 40},
                    { label: 'Tipo', name: 'Cat_Des', width: 110,align:"center" },                    
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 20, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                                return  '<span class="btn btn-success btn-xs" title="Enviar al Cr&eacute;dito" onclick="SelectProd(\''+rowObject.Pro_Cod+'\',\''+rowObject.Ite_Lar+'\');"><i class="glyphicon glyphicon-arrow-right"></i></span>'; 
                            }
                        }
                ]);
            $.createSearchDialog('matDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 15,align:"center",hidden:true },                                
                    { label: 'Iva Cod', name: 'Iva_Cod', hidden:true},
                    { label: 'Descripción', name: 'Ite_Lar', width: 110 },                      
                    { label: 'Marca', name: 'Mar_Des', width: 40},
                    { label: 'Unidad', name: 'Uni_Des', width: 40,hidden:true},
                    { label: 'Stock', name: 'Stk_Can', width: 40,hidden:true},
                    { label: 'Tipo', name: 'Cat_Des', width: 110,align:"center" },                    
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 20, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                                return  '<span class="btn btn-success btn-xs" title="Enviar al Cr&eacute;dito" onclick="addFilaMat( $(\'#matGrid\').getRowData(\''+rowObject.Pro_Cod+'\'));"><i class="glyphicon glyphicon-arrow-right"></i></span>'; 
                            }
                        }
                ]); 
            function guardar(){
                var data=$('#formKardex').serializeObject();   

                if(data['Pro_Cod']===''){$.alert('Seleccione el Producto');return false;}
                data['saveForm']=$("#prods").getGridBatch();
                if(data['saveForm'].length===0){$.alert('Seleccione las Materias Prima');return false;}

                $('.btn-frm').attr('disabled','disabled');
                //console.log(data);
                $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( response ) {
                    if(response['success']===true){
                        $('.btn-new').removeAttr('disabled');
                        //$.alert('Registro Guardado Con Exito!');
                        $('.btn-new').removeAttr('disabled');
                        $('#impCompr').attr('href',response['link']);                                                                
                        $('#successDialog').dialog('open');
                    }else{
                        $('.btn-frm').removeAttr('disabled');$.alert("No se Logro Guardar la Información");$("#prods").startGridEdit();                                        
                    }
                    //console.log(data);
                },'json').fail(function(error) { $('.btn-frm').removeAttr('disabled');$.alert("El Servidor ha fallado en responder!");$("#prods").startGridEdit(); })
                        .always(function() {});    

            }    
            function addFilaMat(data){
                var grid=$("#prods");
                if(!grid.existsId(data['Pro_Cod'])&& data['Pro_Cod']!==$('#Fin_Cod').val()){
                    data['Mes_Can']=1;                    
                    grid.jqGrid("addRowData", data["Pro_Cod"], data, "last");        
                    editGrid(grid);
                }else
                    $.alert('Ya se encuentra en el listado!');                
            }
            function editGrid(grid){
                grid.startGridEdit();
                var mescla=getMescla($('#Mes_Cod').val());
                if(mescla!==null){ 
                    for(var i=0;i<mescla['formula'].length;i++){
                        grid.jqGrid('saveRow', mescla['formula'][i]['Pro_Cod'], false, 'clientArray');
                    }
                }
            }
            function SelectMescla(){  
                resetMescla();
                var mescla=getMescla($('#Mes_Cod').val());
                if(mescla!==null){                    
                    $("#Mes_Des").html(mescla['Mes_Des']);
                    $("#Ord_Res").val(mescla['Mes_Res']);
                    $("#Ord_Max").val(mescla['Mes_Max']);
                    $("#Ord_Lot").val(Math.ceil(mescla['Mes_Res']/mescla['Mes_Max']));                    
                    $("#prods").jqGrid('setGridParam', {data:(typeof mescla['formula']==='undefined'?[]:mescla['formula'])}).trigger('reloadGrid', [{ page: 1 }]);
                }
            }
            function getMescla(id){
                if(typeof mesclas === 'undefined'||mesclas.length===0) return null;
                for(var i=0;i<mesclas.length;i++)
                    if(mesclas[i]['Mes_Cod']===id)
                        return mesclas[i];
                return null;
            }
            function resetMescla(){
                $("#prods").clearGrid();
                $('#Mes_Des').html('');
                $('#Ord_Res').val(1);
                $('#Ord_Lot').val(1);
                $('#Uni_Des').html('');
                $('#Ord_Obs').val('');
            }
            function SelectProd(id,desc){
                $('#Pro_Cod').val(id);
                $('#Fin_Cod').val(id);
                $('#producto').val(desc);
                resetMescla();               
                $('#proDialog').dialog('close');
                $.get('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',{'Pro_Cod':id,'ajaxProd':true}, function(response){
                    if(response['success']===true){
                        $('#pro_cat').html(response['prod']['Ite_Lar']);
                        $('#cat_cod').html(response['prod']['Pro_Cdc']);
                        $('#pro_obs').html(response['prod']['Pro_Obs']);
                        $('#Iva_Cod').val(response['prod']['Iva_Cod']);
                                                
                        $('#pro_mar').html(response['prod']['Mar_Des']);
                        $('#pro_adq').html(response['prod']['Adq_Des']);
                        $('.uni_des').html(response['prod']['Uni_Des']);
                        
                        $('#pro_stk').html(response['stocks']['Stock']);
                        $('#pro_pre').html(response['stocks']['Promedio']);
                        $('#pro_sal').html(response['stocks']['Saldo']);
                        
                        if($("#prods").existsId(response['prod']['Pro_Cod'])){
                            $("#prods").clearGrid();
                            $('#Ord_Res').val(1);
                            $('#Ord_Lot').val(1);
                        }
                        $('#Mes_Cod').html(response['options']);
                        mesclas=response['mesclas'];
                    }else {$.alert("No se logro obtener informacion del Producto!");}                                
                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;
               
            }
            function calcCosto(){
                $('#Ord_Tot').val(($('#Ord_Res').val()*$('#Ord_Cou').val()).toFixed(2));
            }
            function selectProvee(data){                           
                            if(typeof data==='undefined'){                                
                                $("input[name='Cli_Cod']").val('');
                                $("#docu").val('');                                
                                return false;
                            }else{                            
                                $("#docu").val(data['cliente']);                             
                                $("input[name='Cli_Cod']").val(data['Cli_Cod']);                                     
                                $("#provDialog").dialog("close");
                            }
                        }
            function resetForm(){
                    $('#formKardex')[0].reset();
                    $("#prods").clearGrid();                    
                    $('.btn-new').attr('disabled','disabled');
                    $('.btn-frm').removeAttr('disabled');                                
                    $("#prods").startGridEdit();
                    $('#Pro_Cod').val('');
                    $('#Fin_Cod').val('');
                    $('#producto').val('');
                    $('#pro_cat').html('');
                    $('#cat_cod').html('');
                    $('#pro_obs').html('');
                    $('#pro_mar').html('');
                    $('#pro_adq').html('');
                    $('#uni_des').html('');
                    $('#pro_stk').html('');
                    $('#pro_pre').html('');
                    $('#pro_sal').html('');
                    $('#Mes_Des').val('');
                }            
                        
</script>                
</BODY>
</HTML>