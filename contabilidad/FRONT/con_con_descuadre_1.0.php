<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_compr.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($ajaxCompr)){     
    $responce['rows']=$obBD_con1->getArrayConsulta(369, $Pla_Cod.'*'.$ini.'*'.$fin.'*'.$Descuadre, $obBD_conexion);
    $responce['success']=true;$responce['records']=count($responce['rows']);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
                <TITLE><?Php echo "Descuadres [EXA]"; ?></TITLE>
                <meta charset= "UTF-8">
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
                <style>                     
                     
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Comprobantes Descuadrados</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">
                   
                    <div class="col-xs-12">
                       <form id="formCompr" class="form-horizontal normal"  action="javascript:$('#kardex').jqGrid('setCaption', 'Desde '+ $('#ini').val()+' Hasta '+$('#fin').val());$('#kardex').Search('#formCompr','ajaxCompr');"   >
                         
                        
                                <div class="row">
                                    <div class="col-xs-4">
                                  <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Seleccione Periodo:</legend> <!-- Form Name -->  
                                  
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-sm ">Periodo:</label>  
                                          <div class="col-sm-8"> 
                                              <select id="Pec_Cod" name="Pec_Cod" class="form-control input-sm" required>
                                                  <option value="">Seleccione...</option>
                                                  <?php 
                                                    $periodo=$obBD_con1->getArrayConsulta(214, $Ses_Emp_Cod.'*', $obBD_conexion);
                                                    foreach ($periodo AS $row){
                                                        ?>
                                                            <option value="<?php echo $row['Pec_Cod'] ?>">Periodo <?php echo $row['Periodo'] ?></option>
                                                        <?php
                                                    }
                                                  ?>
                                              </select>
                                              <input id="Pla_Cod" name="Pla_Cod" type="hidden" value="" />
                                              <input name="Descuadre" type="hidden" value="0.009" />
                                          </div>                                  
                                        </div>                                      
                                      <script>
                                          var periodo=<?php echo json_encode($periodo); ?>;
                                          console.log(periodo);
                                      </script>
                                        
                                        </fieldset> 
                                        </div>
                                     <div class="col-xs-8">
                                        <fieldset class="exa-fieldset">                           
                                            <legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <div class="form-group">
                                                        <label class="col-sm-2 control-label label-xs ">Desde:</label>
                                                        <div class="col-sm-3">     
                                                            <input name="ini" type="text" id="ini" class="form-control input-sm">                                                                                        
                                                        </div>
                                                        <label class="col-sm-2 control-label label-xs ">Hasta:</label>
                                                        <div class="col-sm-3">                                    
                                                            <input name="fin" type="text" id="fin" class="form-control input-sm">                              
                                                        </div>
                                                        <div class="col-xs-2">
                                                          <div class=""><button type="submit"  class="btn btn-sm btn-success" title="Ejecutar B�squeda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
                                                        </div>
                                                      </div>
                                                </div>
                                            </div>
                                        </fieldset> 
                                     </div>    
                                </div>    
                        
                           
                           
                                
                        </form>  
                    </div>
                    <div class="col-sm-12" style="min-height: 450px;">
                        <table id="kardex"></table>
                        <div id="kardexPager"></div>
                        <script>
                             $(document).ready(function () {
                                $.createDateRange('#ini','#fin');
                                var kardexGrid=$("#kardex");
                                kardexGrid.jqGrid({
                                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                    mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                                    //postData: $("#form1").getData("ajaxGrid"),
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,
                                    caption:' ',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Cod.Int.', name: 'Com_Cod', key: true, hidden:false,viewable:true , width: 25,align:'center' }, 
                                        { label: 'Asiento', name: 'Com_Codigo', width: 40,align:'center'  }, 
                                        { label: 'Tipo', name: 'Tipo', width: 20,align:'center'  }, 
                                        
                                        { label: 'Doc.', name: 'Doc', width: 25, align:"center" },
                                        { label: 'Doc.Num.', name: 'Doc_Num', width: 60, align:"center" },
                                        
                                        { label: 'Fecha', name: 'Com_Fec', width: 40,align:'center'  }, 
                                        { label: 'Concepto', name: 'Com_Con', width: 90  }, 
                                        { label: 'Observaci�n', name: 'Com_Obs', width: 80  },                                                                              
                                       
                                        { label: 'Debe',name: 'Debe', width: 40, align:"right",formatter:'number',classes:'columnHighlight2'},
                                        { label: 'Haber',name: 'Haber', width: 40, align:"right",formatter:'number',classes:'columnHighlight2'},
                                        { label: 'Dif.',name: 'Diferencia', width: 40, align:"right",formatter:'number',classes:'columnHighlight2'}
                                                                                
                                       
                                    ],       
                                   
                                    rowNum: 10000000, pager: "#kardexPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,                          
                                });                                  
                                kardexGrid.navGrid('#kardexPager',{ edit: false, add: false, del: false, search: false, refresh: false, view: false, position: "left", cloneToTop: false });
                                kardexGrid.jqGrid('bindKeys');
                                
                                $('#Pec_Cod').on('change',function (){                                                                  
                                    for(var i=0;i<periodo.length;i++){
                                        //console.log(this.value,' ',periodo[i]['Pec_Cod']);
                                        if(this.value===periodo[i]['Pec_Cod']){                                            
                                            $('#Pla_Cod').val(periodo[i]['Pla_Cod']);
                                            $('#fin').datepicker( "option", "minDate", periodo[i]['Pec_Fei'] ).datepicker("setDate", periodo[i]['Pec_Fef']);
                                            $('#ini').datepicker( "option", "maxDate", periodo[i]['Pec_Fef'] ).datepicker("setDate", periodo[i]['Pec_Fei']);
                                            break;
                                        }
                                    }
                                });
                            }); 
                            
                        </script>    
                    </div>  
                </div> 
        </div>
    </div>
               
</BODY>
</HTML>