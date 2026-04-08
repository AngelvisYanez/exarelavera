<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require('../LOGICA/adm_log_perio.php');
/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Tes
 */
$obBD_conexion = new Class_Log_Conexion_Per($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Tes
 */
$obBD_con1 = new Class_Log_Datos_Per;

if (isset($eliminarPeriodo)){
    $data=$_POST;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    $obBD_con1->operacionobBD(3,$data,$obBD_conexion); 
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if($obBD_con1->Error==0) {$responce=array('success'=>true,'prov'=>$data);} else {$responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!',error=>$obBD_con1->MsgError);}
    $obBD_con1->echoJson($responce);
}
if(isset($consAjax)){ 
   $data = $_GET;
   $data["Emp_Cod"] = $Ses_Emp_Cod;
   $obBD_con1->getPageGridJson(4, $data, $obBD_conexion);
}


if (isset($searchAnios)) {
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getArrayConsulta(6,$data , $obBD_conexion);
    $obBD_con1->echoJson($responce);
}

if (isset($guardarPeriodo)) {
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    if($data['Peri_Cod'] == ''){    //NUEVO PERIODO CONTABLE
        $obBD_con1->operacionobBD(1,$data,$obBD_conexion); 
    }  else {                       //ACTUALIZAR PERIODO CONTABLE
        $obBD_con1->operacionobBD(2,$data,$obBD_conexion); 
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if($obBD_con1->Error==0) {$responce=array('success'=>true,'prov'=>$data);} else {$responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!',error=>$obBD_con1->MsgError);}
    $obBD_con1->echoJson($responce);
}


?>

<!DOCTYPE html>
<HTML>
	<HEAD>		
                <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
                <TITLE><?Php echo "Per.Contable Configurar [EXA]"; ?></TITLE>
                <meta charset= "UTF-8">
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
                <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
                <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>             
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestión de Periodos Contables</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <div class="col-sm-12">  
                        
                        <fieldset class="exa-fieldset hidden">                           
                         <form id="searchForm" class="form-inline hidden" role="form" >
                               <div class="form-group">
                                <label for="search">Buscar:</label>
                                    <div class="input-group input-group-sm">                                                
                                    <input id="value=<?php echo $Ses_Emp_Cod ?>" type="text" value="" class="hidden" />
                                    <input id="docu" name="search" maxlength="13"  type="text" class="form-control clearable submit" placeholder="Ingrese palabra a buscar ..." autofocus style="width: 350px" />                                    
                                    <span class="input-group-btn">
                                        <button class="btn btn-success" type="submit"><span class="fa fa-search" title="Buscar Proveedor"></span></button>
                                    </span>
                                  </div><!-- /input-group -->
                              </div>
                              <div class="form-group">&nbsp;</div>
                              <button type="button" onclick="cargarPeriodo('')" title="Agregar Periodo" class="btn btn-success btn-sm"><i class="fa fa-plus"></i></button>
                                         
                           </form>
                        </fieldset>
                        
                            <div>
                                <table id="Lis_Periodo"></table>
                                <div id="Pag_Periodo">
                                    
                                </div>
                            </div>
                         
                    </div>                   
                </div>    
              
            </div>   
        </div>
    </div>

    <div id="DialogPerCon" title="Periodo Contable" style="display: none;"> 
        <div class="row">
            <div class="form-horizontal normal col-md-12" >
                <fieldset>
                    <legend><label class="Titulos2"></label></legend>
                    <form id="formPerCon" class="form-horizontal normal"  action="javascript:guardarPeriodo()"  >
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Plan de Cuentas:</label>  
                            <div class="col-xs-8" >
                                <?php $rs_plan = $obBD_con1->getArrayConsulta(7,$Ses_Emp_Cod, $obBD_conexion); ?>
                                <select name="Pla_Cod" id="Pla_Cod" class="form-control input-xs readOnly" required>
                                    <?php foreach ($rs_plan as $row) {
                                        echo "<option value='$row[Pla_Cod]'>$row[Pla_Obs] ($row[Pla_Fec])</option>";
                                    } ?>
                                </select>
                            </div>
                        </div> 
                        <div class="form-group">
                       <label class="col-sm-4 control-label label-sm required">Inicio:</label>  
                       <div class="col-sm-8" >
                           <!--tipo=0:nuevo?edicion-->
                           <input type="text" id="Peri_Cod" name="Peri_Cod" value="" class="hidden"/>
                           <input type="text" id="Pec_Fei" name="Pec_Fei" value="" class="form-control input-sm" required/>
                       </div>
                       <label class="col-sm-4 control-label label-sm ">Fin:</label>  
                       <div class="col-sm-8" >
                           <input type="text" id="Pec_Fef" name="Pec_Fef" value="" class="form-control input-sm" required readonly/>
                       </div>
                     </div>                     
                     <div class="form-group">
                        <label class="col-sm-4 control-label">Acción:</label>
                        <div class="col-sm-8">
                            <button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                            <button type="button" onclick="$('#DialogPerCon').dialog('close');"  class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                        </div>
                    </div>
                    </form>    
                    <div class="form-group Titulos2">
                        <div class="col-md-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                    </div>  
                 </fieldset>    
            </div>
        </div>
    </div>    
    
   <script type="text/javascript">
    $('#Pec_Fei').createDatePickers();
    var anios=[];
    $('#Pec_Fei').change(function(){
       var mensaje='';
       var txt=$('#Pec_Fei').val().split('-')[0];
       if(existeAnio(txt)){
           $.alert('!Ya existe el periodo '+txt+'!',function(){
                $('#Pec_Fei').val('');   
           },'alert');
       }else{
           $('#Pec_Fef').val(txt+"-12-31");
       }
    });
    
        
        
           
      
        
        $('#Lis_Periodo').createGrid({
            postData: $("#searchForm").getData("consAjax"), height: 295,
            colModel:[
                { label: 'Cód. Int.', name: 'Peri_Cod', key: true, width: 25,align:"center" },
                { label: 'Fecha Ini', name: 'Pec_Fei', width: 50 ,align:"center"},
                { label: 'Fecha Fin', name: 'Pec_Fef', width: 50 ,align:"center"} ,
                { label: 'Estado', name: 'Pec_Est', width: 50 ,align:"center",
                  formatter:'estado',formatoptions:{types:{'A':'ACTIVO'},full:true}
                },
                { label: 'Compras Asociadas', name: 'compras_asoc', width: 50 ,align:"center"} ,
                { label: 'Aperturas de caja asociadas', name: 'caja_aper_asoc', width: 50 ,align:"center"} ,
                { label: 'Comprobantes Asociados', name: 'comprobantes_asoc', width: 50 ,align:"center"} ,
                {label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            var type='success';
                            if((rowObject['compras_asoc']+rowObject['caja_aper_asoc']+rowObject['comprobantes_asoc'])>0){
                                return $.getGridButton(null, rowObject, 'Editar Periodo','arrow-right','','default disabled');
                            }else{
                                return $.getGridButton(cargarPeriodo, rowObject, 'Editar Periodo','fa fa-pencil','','success');  
                            }    
                        }
                },
                {label: '&nbsp;', name: 'act2', width: 30, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            var type='success';
                            if((rowObject['compras_asoc']+rowObject['caja_aper_asoc']+rowObject['comprobantes_asoc'])>0){
                                return $.getGridButton(null, rowObject, 'Eliminar Periodo','remove','','default disabled');
                            }else{
                                return $.getGridButton(eliminarPeriodo, rowObject, 'Eliminar Periodo','remove','','danger');  
                            }    
                        }
                }
                
                
            ]
            
        }, false, "#Pag_Periodo").gridButtonsAdd([
            {
                buttonicon:'glyphicon glyphicon-plus',
                onClickButton:cargarPeriodo
            }
        ]);

      function aniosPeriodo(anio){
          anios=[];
            $.post("", 
                    {searchAnios: true}, 
                    function (response) {
                        $(response).each(function(i,obj){
                           anios.push(obj['using_yeards']);
                        });
                        delete anios[$.inArray(anio,anios)];
                               
                    }, 'json').fail(function () {
                        $.alert();
                    });
      }  
      
      function eliminarPeriodo(periodo){
          $('#formPerCon').setData(periodo,null);
          $.createDialogConfirm('Desea Eliminar el Periodo Contable?', null, 
                            function () {
//          Funcion Aceptar
                               $.saveDataJson('',$('#formPerCon').getData('eliminarPeriodo'), function (resp) {
                                $('#Lis_Periodo').trigger('reloadGrid');
                               });
                               },function(){
                                   
                               });
      }
        
      function cargarPeriodo(periodo){
        $('#formPerCon')[0].reset();
        $('#formPerCon').setData(periodo,null);
        $('#DialogPerCon').dialog({width: 600,
                    height: 250}).dialog('open');
        if(periodo !== ''){
            aniosPeriodo(periodo['anio']);
        }else{
            aniosPeriodo();
        }
      }
      
      
      function existeAnio(txt){
          anio=""+txt;
         
        if($.inArray(anio,anios)>0){
            return true;
        }else{
            return false;
        }
      }
      
      function guardarPeriodo(){
            var titulo="";
            if($('#Per_Cod').val()===''){
                titulo="Desea Registrar en nuevo Periodo Contable";
            }else{
                titulo="Confirmar los cambio en el Periodo Contable";
            }
           $.createDialogConfirm(titulo, null, 
                            function () {
//          Funcion Aceptar
                               $.saveDataJson("", $('#formPerCon').getData('guardarPeriodo'), function (resp) {
                               $('#Lis_Periodo').trigger('reloadGrid');
                               });
                               $('#DialogPerCon').dialog('close');
                            }, function () {

                            });
      }
      
      
       
   </script>
</BODY>
</HTML>