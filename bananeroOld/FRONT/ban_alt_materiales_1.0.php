<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_materiales.php');
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

/* Seleccionar El Producto a Producir */
if(isset($proAjax)){
    $contar = $obBD_con1->getRowConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
/* Seleccionar las materias primas */
if(isset($matAjax)){
    $contar = $obBD_con1->getRowConsulta(2, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$Fin_Cod.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(2, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$Fin_Cod.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
/* Datos generales del producto a producir */
if(isset($ajaxProd)){
    $Ite_Cod=$Pro_Cod;$ini=$hoy;
    $responce['success']=true;
    $kardex1 = $obBD_con1->getArrayConsulta(4,$ini.'*'.$Ite_Cod, $obBD_conexion);
    if(count($kardex1)==1 && $kardex1[0]['Saldo']!==0 && $kardex1[0]['Stock']!=0){         
        $kardex1[0]['Promedio']=round(($kardex1[0]['Saldo']/$kardex1[0]['Stock']),6);
    }else{
        $kardex1[0]['Promedio']=0;$kardex1[0]['Saldo']=0;$kardex1[0]['Stock']=0;
    }
    list($ann, $mes, $dia) = preg_split('![/.-]!',$ini);
    $kardex1[0]['Kar_Det']='<b>Saldo al '.$dia.', de '.mes($mes, 1).', '.$ann.'</b>';
    $responce['stocks']=$kardex1[0];
    
    
    $responce['prod'] = $obBD_con1->getRowConsulta(3,$Ite_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
    utf8_encode_deep($responce);
    echo json_encode($responce);exit();
}
/* Guardar el Formulario */
if(isset($saveForm)){
    $data=filter_input_array(INPUT_POST);
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);    
    $obBD_con1->grabarv_registros(sentencias_produ(5,$data),$obBD_conexion->conexion);
    $Mes_Cod = $obBD_con1->insercionid ($obBD_conexion->conexion);
    $conteo=0;
    
    foreach ($data['saveForm'] AS $row){
        $conteo++;$row['conteo']=$conteo;
        $row['Mes_Cod']=$Mes_Cod;
        $obBD_con1->grabarv_registros(sentencias_produ(6,$row),$obBD_conexion->conexion);
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0) $responce['success']=true; else {$responce['success']=false; $responce['message']=$obBD_con1->MsgError;}
    echo json_encode($responce);
    exit();
}

//obtenemos todas las mesclas de un producto
if(isset($getMesclas)){
    $response['success'] = false;

    $response['data'] = $obBD_con1->getArrayConsulta(27, $Pro_Cod , $obBD_conexion);

    if ($obBD_con1->Error == 0) {
        $response['success'] = true;
    }

    $obBD_con1->echoJson($response);
    exit();
}

//obtenemos todas las mesclas de un producto
if(isset($getMarcas)){
    $response['success'] = false;

    $response['data'] = $obBD_con1->getArrayConsulta(28, "" , $obBD_conexion);

    if ($obBD_con1->Error == 0) {
        $response['success'] = true;
    }

    $obBD_con1->echoJson($response);
    exit();
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
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Rubros Principales</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">
                   
                    <div class="col-xs-12">
                       <form  class="form-horizontal normal"   >
  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Artículo a Producir:</legend> <!-- Form Name -->
                              <div class="row">                                  
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Descripción:</label>  
                                          <div class="col-xs-8">  
                                            <div class="input-group input-group-xs">      
                                                <input id="producto"  type="text" class="form-control" placeholder="Seleccione un Producto ..." required readonly />
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-frm" onclick="$('#proDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                                                </span>
                                              </div><!-- /input-group -->                                 
                                          </div>    
                                        </div>                                      
                                      <!-- static input-->
                                        <!-- <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Marca:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_mar"></span>                              
                                          </div>                                  
                                        </div> -->
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Adquisición:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_adq"></span>                              
                                          </div>                                  
                                        </div>
                                  </div>
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Categoria:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_cat"></span>                              
                                          </div>                                  
                                        </div>                                      
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Cod. Cat.:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="cat_cod"></span>                              
                                          </div>                                  
                                        </div>
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-3 control-label label-xs ">Observación:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs" id="pro_obs"></span>                              
                                          </div>                                  
                                        </div>
                                  </div>
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-4 control-label label-xs ">Stock:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs txtRight" id="pro_stk"></span>                              
                                          </div>                                  
                                        </div>                                      
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-4 control-label label-xs ">Prec Prom.:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs txtRight" id="pro_pre"></span>                              
                                          </div>                                  
                                        </div>
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-xs-4 control-label label-xs ">Saldo Actual:</label>  
                                          <div class="col-xs-8">                                    
                                              <span  class="form-control input-xs txtRight" id="pro_sal"></span>                              
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
                           <legend class="Titulos2">Material de entrega:</legend> <!-- Form Name -->
                            <!-- static input-->
                            <div class="form-group">
                                <label class="col-sm-3 control-label label-xs ">Tipo de producto:</label>
                                <div class="col-sm-9">   
                                    <select onchange="cambiarMezcla($(this).val())" name="Mes_Tip" id="Mes_Tip" class="form-control input-xs">
                                        <option value="C">Caja</option>
                                        <option value="M">Material chico</option>
                                    </select>
                                </div>                                  
                            </div>
                            <!-- static input-->
                            <div class="form-group" id="bamcod">
                              <label class="col-sm-3 control-label label-xs ">Marca:</label>
                              <div class="col-sm-9">   
                                  <select name="Bam_Cod" id="Bam_Cod" onclick="$('#Mes_Nom').val($('#Bam_Cod option:selected').text());" class="form-control input-xs">
                                  <?php $rows_marcas = $obBD_con1->getArrayConsulta(28, "", $obBD_conexion);
                                    if (count($rows_marcas) > 0)
                                    {
                                        foreach($rows_marcas as $row){
                                            echo "<option value='$row[Bam_Cod]'>$row[Bam_Nom]</option>";
                                        }
                                    }?>
                                  </select>
                              </div>                                  
                            </div>
                            <!-- static input-->
                            <div class="form-group" id="mesnom" hidden>
                              <label class="col-sm-3 control-label label-xs ">Nombre:</label>  
                              <div class="col-sm-9">   
                                  <input name="Mes_Nom" id="Mes_Nom" type="text" value="Material Chico" class="form-control input-xs" required="" readonly/>                                                             
                              </div>                                  
                            </div>
                            <!-- static input-->
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Descripción:</label>  
                              <div class="col-sm-9"> 
                                  <textarea name="Mes_Des" class="form-control input-xs"></textarea>
                              </div>                                  
                            </div>
                            <!-- static input-->
                            <div class="form-group" hidden>                               
                              <label class="col-xs-5 control-label label-xs ">Cant. a Producir:</label>  
                              <div class="col-xs-3">                                    
                                   <span  class="form-control input-xs txtRight">1</span>  
                              </div>
                              <div class="col-xs-3">                                    
                                  <span  class="input-xs disabled" id="uni_des">UNIDAD</span>
                              </div>
                            </div>
                            <!-- static input-->
                            <div class="form-group" hidden>
                              <label class="col-xs-5 control-label label-xs ">Max. Cant. Por Lote:</label>  
                              <div class="col-xs-3">    
                                  <input type="text" id="Mes_Max" name="Mes_Max" class="form-control input-xs" style="text-align:right" value="1" required="" readonly/>
                              </div>  
                              <div class="col-xs-3">                                    

                              </div>
                            </div>
                            <div class="form-group center">
                                <button class="btn btn-success btn-sm btn-frm" type="submit"><span class="glyphicon glyphicon-check" title="Guardar"></span> Guardar</button>
                                <button class="btn btn-success btn-sm btn-new" type="button" onclick="resetForm()" disabled><span class="glyphicon glyphicon-check" title="Nuevo Registro"></span> Nuevo</button>
                            </div>
                        </fieldset>    
                        </form>    
                    </div>    
                    <div class="col-xs-8" style="min-height: 350px;">
                        <table id="prods"></table>
                        <div id="prodsPager"></div>
                        <div style="padding-top: 10px;">
                            <button class="btn btn-success btn-sm btn-frm" onclick="$('#matDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Agregar Producto"></span> Seleccione Materia Prima</button>                            
                        </div>
                        <script>
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
                                        $.alert('Registro Guardado Con Exito!');
                                    }else{
                                        $('.btn-frm').removeAttr('disabled');$.alert("No se Logro Guardar la Información");$("#prods").startGridEdit();                                        
                                    }
                                    //console.log(data);
                                },'json').fail(function(error) { $('.btn-frm').removeAttr('disabled');$.alert("El Servidor ha fallado en responder!");$("#prods").startGridEdit(); })
                                        .always(function() {});    
                                
                            }
                            $(document).ready(function () {
                                var kardexGrid=$("#prods");
                                kardexGrid.jqGrid({
                                    url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                    mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                                    //postData: $("#form1").getData("ajaxGrid"),
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,
                                    caption:'Listado de Materias Prima',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Cod.Int.', name: 'Pro_Cod', key: true, hidden:false,viewable:true, width: 20,align:'center' }, 
                                       
                                        { label: 'Detalle',name: 'Cat_Des', width: 200},                                        
                                        { label: 'Cant.',name: 'Mes_Can', width: 50,classes:'columnHighlight2',editable:false,align:'center',editoptions: {dataInit:function(e){ e.style.textAlign = 'right';e.style.paddingRight = '5px';}}},
                                        { label: 'Unid.',name: 'Uni_Des', width: 30},    
                                        //{ label: 'C. Unit.',name: 'Doc', width: 20,classes:'columnHighlight2'},
                                        //{ label: 'C. Total',name: 'Doc', width: 20,classes:'columnHighlight2'},
                                        { label:'&nbsp;', name: 'act1', width: 15, align: 'center',viewable: false,
                                            formatter:function (cellvalue, options, rowObject) { 
                                                
                                                return  '<button type="button" class="btn btn-danger btn-xs btn-frm" title="Eliminar" onclick="$(\'#prods\').jqGrid(\'delRowData\',\''+rowObject.Pro_Cod+'\');"><i class="glyphicon glyphicon-trash"></i></button>'; 
                                               
                                            }
                                        }
                                                                                
                                        
                                       
                                    ],     
                                    footerrow: false, userDataOnFooter: false,
                                    rowNum: 10000000, pager: "#kardexPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,                           
                                }); 
                            });
                        </script>    
                    </div>  
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
                    { label: 'Descripción', name: 'Ite_Lar', width: 110 },                      
                    { label: 'Marca', name: 'Mar_Des', width: 40},
                    { label: 'Unidad', name: 'Uni_Des', width: 40,hidden:true},
                    { label: 'Tipo', name: 'Cat_Des', width: 110,align:"center" },                    
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 20, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                                return  '<span class="btn btn-success btn-xs" title="Enviar al Cr&eacute;dito" onclick="addFilaMat( $(\'#matGrid\').getRowData(\''+rowObject.Pro_Cod+'\'));"><i class="glyphicon glyphicon-arrow-right"></i></span>'; 
                            }
                        }
                ]);     
            function addFilaMat(data){
                var grid=$("#prods");
                if(!grid.existsId(data['Pro_Cod'])&& data['Pro_Cod']!==$('#Fin_Cod').val()){
                    data['Mes_Can']=1;                    
                    grid.jqGrid("addRowData", data["Pro_Cod"], data, "last");        
                    grid.startGridEdit();
                }else{
                    $.alert('Ya se encuentra en el listado!');
                }
            }
            function SelectProd(id,desc){
                $("#Mes_Cod").attr("disabled","");
                $('#Pro_Cod').val(id);
                $('#Fin_Cod').val(id);
                $('#producto').val(desc);
                               
                $('#proDialog').dialog('close');
                $.get('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',{'Pro_Cod':id,'ajaxProd':true}, function(response){
                    if(response['success']===true){
                        $('#pro_cat').html(response['prod']['Cat_Des']);
                        $('#cat_cod').html(response['prod']['Pro_Cdc']);
                        $('#pro_obs').html(response['prod']['Pro_Obs']);
                                                
                        $('#pro_mar').html(response['prod']['Mar_Des']);
                        $('#pro_adq').html(response['prod']['Adq_Des']);
                        $('#uni_des').html(response['prod']['Uni_Des']);
                        
                        $('#pro_stk').html(response['stocks']['Stock']);
                        $('#pro_pre').html(response['stocks']['Promedio']);
                        $('#pro_sal').html(response['stocks']['Saldo']);
                        
                        if($("#prods").existsId(response['prod']['Pro_Cod'])){
                            $("#prods").clearGrid();
                            $('#Can_Pro').val(1);
                            $('#Can_Lot').val(1);
                        }
                    }else {$.alert("No se logro obtener informacion deñ Producto!");}                                
                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
            }
            function cambiarMezcla(tip_mz){
                //Mes_Tip
                if(tip_mz === "C"){
                    $("#bamcod").removeAttr("hidden");
                    $("#mesnom").attr("hidden","");
                } else {
                    $("#bamcod").attr("hidden","");
                    $("#mesnom").removeAttr("hidden");
                }
            }
            function resetForm(){
                    $('#formKardex')[0].reset();
                    $("#prods").clearGrid();
                    $('#Can_Pro').val(1);
                    $('#Can_Lot').val(1);
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
                }
</script>                
</BODY>
</HTML>