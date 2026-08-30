<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_materiales.php');
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
    
    $responce['rows']=$obBD_con1->getArrayConsulta(23,"Emp_Cod=".$Ses_Emp_Cod.'*'.$Ses_Suc_Cod.'*'.$Ite_Cod, $obBD_conexion,true);
    $responce['success']=true;$responce['records']=count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
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
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Materiales</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">
                    <?php if(!isset($Mes_Cod)||$Mes_Cod==''){ ?>
                    <div class="col-xs-12">
                        <form  class="form-horizontal normal" id="form1"  >
                           <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">B�squeda:</legend> <!-- Form Name -->
                              <div class="row">  
                                    
                              </div>
                           </fieldset>
                        </form>
                    </div> 
                    <form id="FormMes" name="FormMes" method="post" style="display: none"  >
                        <input type="text" name="Mes_Cod" id="BackMes_Cod" />  
                        <input type="text" name="Pro_Cod" id="BackPro_Cod" />  
                    </form>
                    <div class="col-xs-12" style="min-height: 350px;">
                    <div class="jqHeaderFirst jqFirst">
                        <table id="kardex"></table>
                        <div id="kardexPager"></div>
                        </div>
                        
                        <script>
                             $(document).ready(function () {
                                $.createDateRange('#ini','#fin');
                                var kardexGrid=$("#kardex");
                                kardexGrid.jqGrid({
                                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                    mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
                                    postData: $("#form1").getData("ajaxGrid"),
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,
                                    caption:' ',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Cod.Int.', name: 'Mes_Cod', key: true, hidden:false,viewable:true,width:20 },                                         
                                        { label: 'Cod.Int.', name: 'Pro_Cod', hidden:true,viewable:false,width:20 },                                         
                                        { label: 'Nombre', name: 'Mes_Nom', width: 100  }, 
                                        { label: 'Descripci�n', name: 'Mes_Des', width: 150  },
                                        { label: 'Marca',name: 'Bam_Nom', width: 30,align:"center"},
                                        { label: 'Resultado',name: 'Ite_Lar', width: 150,classes:'columnHighlight2'},
                                        { label: 'Max. Lote',name: 'Mes_Max', width: 30,align:"center"},
                                        { label: 'Tipo',name: 'tp_mt', width: 30,align:"center"},
                                            { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,title: false,
                                                formatter:function (cellvalue, options, rowObject) {   
                                                    var selectBut='<span class="btn btn-success btn-xs" title="Seleccionar" type="button" onclick="SelectMesc($(\'#kardex\').jqGrid (\'getRowData\', \''+rowObject.Mes_Cod+'\'))"><i class="glyphicon glyphicon-arrow-right"></i></span>';
                                                    return  '<span class="btn btn-info btn-xs" title="Ver" type="button" onclick="$(\'#kardex\').viewGridRow(\''+rowObject.Mes_Cod+'\');"><i class="glyphicon glyphicon-info-sign"></i></span><span>&nbsp;&nbsp;</span>'+
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
                        $mescla = $obBD_con1->getRowConsulta(23,'Mes_Cod='.$Mes_Cod, $obBD_conexion);
                        $materiales = $obBD_con1->getArrayConsulta(25,$Mes_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
                        //var_dump($prod);
                    ?>
                    <div class="col-xs-12">
                       <form  class="form-horizontal normal"   >
  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Art�culo a Producir:</legend> <!-- Form Name -->
                              <div class="row">                                  
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Descripci�n:</label>  
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
                                          <label class="col-xs-3 control-label label-xs ">Adquisici�n:</label>  
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
                                          <label class="col-xs-3 control-label label-xs ">Observaci�n:</label>  
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
                    <div class="col-xs-4">
                        <form id="formKardex" class="form-horizontal normal"  action="javascript:guardar()"   >
                             <input type="text" name="Pro_Cod" id="Pro_Cod" value="" style="display: none" /> 
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Datos de la Formula:</legend> <!-- Form Name -->
                            <!-- static input-->
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Formula:</label>  
                              <div class="col-sm-9">   
                                  <span class="form-control input-xs"><?php echo $mescla['Mes_Nom']; ?></span>
                              </div>                                  
                            </div>
                            <!-- static input-->
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Descripci�n:</label>  
                              <div class="col-sm-9"> 
                                  <textarea name="Mes_Des" class="form-control input-xs" readonly=""><?php echo $mescla['Mes_Des']; ?></textarea>
                              </div>                                  
                            </div>
                            <!-- static input-->
                            <div class="form-group">                               
                              <label class="col-xs-5 control-label label-xs ">Cant. a Producir:</label>  
                              <div class="col-xs-3">                                    
                                   <span  class="form-control input-xs txtRight">1</span>  
                              </div>
                              <div class="col-xs-3">                                    
                                  <span  class="input-xs disabled" id="uni_des"><?php echo $prod['Uni_Des']; ?></span>
                              </div>
                            </div>
                            <!-- static input-->
                            <div class="form-group">
                              <label class="col-xs-5 control-label label-xs ">Max. Cant. Por Lote:</label>  
                              <div class="col-xs-3">    
                                  <span class="form-control input-xs" style="text-align: right"><?php echo $mescla['Mes_Max']; ?></span>
                              </div>  
                              <div class="col-xs-3">                                    
                                  <span  class="input-xs disabled" id="uni_des"><?php echo $prod['Uni_Des']; ?></span>
                              </div>
                            </div>
                            <div class="form-group center" style="display: none">
                                <button class="btn btn-success btn-sm btn-frm" type="submit"><span class="glyphicon glyphicon-check" title="Guardar"></span> Guardar</button>
                                <button class="btn btn-success btn-sm btn-new" type="button" onclick="resetForm()" disabled><span class="glyphicon glyphicon-check" title="Nuevo Registro"></span> Nuevo</button>
                            </div>
                        </fieldset>    
                        </form>    
                    </div>    
                    <div class="col-xs-8" style="min-height: 350px;">
                        <table id="prods"></table>
                        <div id="prodsPager"></div>
                        <!--<div style="padding-top: 10px;">
                            <button class="btn btn-success btn-sm btn-frm" onclick="$('#matDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Agregar Producto"></span> Seleccione Materia Prima</button>                            
                        </div>-->
                        <script>
//                            function guardar(){
//                                var data=$('#formKardex').serializeObject();   
//                                
//                                if(data['Pro_Cod']===''){$.alert('Seleccione el Producto');return false;}
//                                data['saveForm']=$("#prods").getGridBatch();
//                                if(data['saveForm'].length===0){$.alert('Seleccione las Materias Prima');return false;}
//                                
//                                $('.btn-frm').attr('disabled','disabled');
//                                //console.log(data);
//                                $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function( response ) {
//                                    if(response['success']===true){
//                                        $('.btn-new').removeAttr('disabled');
//                                        $.alert('Registro Guardado Con Exito!');
//                                    }else{
//                                        $('.btn-frm').removeAttr('disabled');$.alert("No se Logro Guardar la Informaci�n");$("#prods").startGridEdit();                                        
//                                    }
//                                    //console.log(data);
//                                },'json').fail(function(error) { $('.btn-frm').removeAttr('disabled');$.alert("El Servidor ha fallado en responder!");$("#prods").startGridEdit(); })
//                                        .always(function() {});    
//                                
//                            }
                            $(document).ready(function () {
                                var kardexGrid=$("#prods");
                                kardexGrid.jqGrid({
                                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                    mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                                    //postData: $("#form1").getData("ajaxGrid"),
                                    data:<?php echo json_encode($materiales); ?>,
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,
                                    caption:'Listado de Materias Prima',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Cod.Int.', name: 'Pro_Cod', key: true, hidden:false,viewable:true, width: 20,align:'center' }, 
                                       
                                        { label: 'Detalle',name: 'Ite_Lar', width: 200},                                        
                                        { label: 'Cant.',name: 'Mes_Can', width: 50,classes:'columnHighlight2',editable:true,align:'center',editoptions: {dataInit:function(e){ e.style.textAlign = 'right';e.style.paddingRight = '5px';}}},
                                        { label: 'Unid.',name: 'Uni_Des', width: 30},    
                                        //{ label: 'C. Unit.',name: 'Doc', width: 20,classes:'columnHighlight2'},
                                        //{ label: 'C. Total',name: 'Doc', width: 20,classes:'columnHighlight2'},
//                                        { label:'&nbsp;', name: 'act1', width: 15, align: 'center',viewable: false,
//                                            formatter:function (cellvalue, options, rowObject) { 
//                                                
//                                                return  '<button type="button" class="btn btn-danger btn-xs btn-frm" title="Eliminar" onclick="$(\'#prods\').jqGrid(\'delRowData\',\''+rowObject.Pro_Cod+'\');"><i class="glyphicon glyphicon-trash"></i></button>'; 
//                                               
//                                            }
//                                        }
                                                                                
                                        
                                       
                                    ],     
                                    footerrow: false, userDataOnFooter: false,
                                    rowNum: 10000000, pager: "#kardexPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null                          
                                }); 
                            });
                        </script>    
                    </div>  
                    <?php } ?>
                </div> 
            
        </div>
    </div>

               
</BODY>
</HTML>