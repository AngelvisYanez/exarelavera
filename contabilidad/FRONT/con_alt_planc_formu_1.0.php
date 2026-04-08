<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2018-04-05
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_planc_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas  */
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($getPlan)){
    $r=array('success'=>true);
    $r['rows']=$obBD_con1->getArrayConsulta(362, $Pla_Cod.'*'.$Fog_Cod, $obBD_conexion, true);
    foreach($r['rows'] as &$c){
        $mayor=$obBD_con1->getRowConsulta(358,array('Pld_Cod'=>$c['Pld_Cod'],'Pec_Cod'=>$Pec_Cod,'Year'=>$Year), $obBD_conexion);
        $c['Valor']=($mayor['Acreedor']!=null?$mayor['Acreedor']*1:($mayor['Deudor']!=null?$mayor['Deudor']*1:0));
    } unset($c);
    $r['codigos']=$obBD_con1->getArrayConsulta(360, $Fog_Cod, $obBD_conexion, true);
    $obBD_con1->echoJson($r);
}
if(isset($codiAjax)){
    $obBD_con1->getPageGridJson(363, $_GET, $obBD_conexion, true);
    $r=array('success'=>true, 'page'=>1);    
    $r['rows']=$obBD_con1->getArrayConsulta(360, $Fog_Cod, $obBD_conexion, true);
    $r['records']=count($r['rows']);
    $obBD_con1->echoJson($r);
}
if(isset($saveFormulario)){
    $responce=array('success'=>true);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    foreach ($data as $d){
        if($d['Foc_Cod_Old']!='')
            $obBD_con1->operacionobBD(359, $d, $obBD_conexion);
        $obBD_con1->operacionobBD(356, $d, $obBD_conexion);
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if($obBD_con1->Error!=0){ 
        $responce=array('success'=>false, 'message'=> "No se ha logrado realizar la Transaccion", 'error'=>$obBD_con1->MsgError);       
    } 
    $obBD_con1->echoJson($responce);
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>    
    <style>
        .grupos{
            background: #DDFAE2 !important;
        }
    </style>
</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Parametrizar Formularios y Plan de Cuentas</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <div class="col-sm-12">  
                        
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Plan de Cuentas</legend> <!-- Form Name -->
                            <form  id="formulario" class="form-horizontal normal">
                                <div class="form-group">
                                  <label for="Pla_Cod" class="control-label label-xs col-xs-2">Seleccione Periodo:</label>
                                  <div class="col-xs-2">
                                    <?php $row_rs_planes = $obBD_con1->getArrayConsulta(342, $Ses_Emp_Cod,$obBD_conexion); ?>
                                    <select id="Pec_Cod" name="Pec_Cod" onchange="" class="form-control input-sm getData ins">
                                        <option value="">Seleccione Periodo...</option>
                                    <?php foreach($row_rs_planes as $row){?>
                                        <option value="<?php echo $row['Pec_Cod']; ?>" data--pla_-cod="<?php echo $row['Pla_Cod']; ?>" data--year="<?php echo $row['Periodo']; ?>">Periodo <?php echo $row['Periodo']; ?></option>   
                                    <?php } ?>
                                   </select> 
                                  </div>
                                  <label for="Pla_Cod" class="control-label label-xs col-xs-2">Seleccione Fortmulario:</label>
                                  <div class="col-xs-2">
                                    <?php $row_rs_formula = $obBD_con1->getArrayConsulta(361, $Ses_Emp_Cod,$obBD_conexion); ?>
                                    <select id="Fog_Cod" name="Fog_Cod" onchange="" class="form-control input-sm">
                                        <option value="" data-estado="">Seleccione Formulario...</option>
                                    <?php foreach($row_rs_formula as $row){?>
                                        <option value="<?php echo $row['Fog_Cod']; ?>" data-estado="<?php echo $row['Fog_Est']; ?>"><?php echo $row['Fog_Nom']; ?></option>   
                                    <?php } ?>
                                   </select> 
                                  </div>
                                  <div class="col-xs-2">
                                      <button type="button" onclick="updatePlan();" class="btn btn-sm btn-success" ><i class="glyphicon glyphicon-search"></i> Buscar</button>
                                  </div>    
                                </div>  
                             </form>                         
                        </fieldset>
                        <div style="min-height: 300px; padding-bottom:8px; ">
                            <table id="comp"></table>
                            <div id="compPager"></div>
                        </div>
                        <div>
                            <button type="button" onclick="validaFormulario();" class="btn btn-sm btn-success" ><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                    </div>
                    <div class="col-sm-6">
                       
                        
                        
                    </div>
                </div>    
              
            </div>   
        </div>
    </div>
      
   <script type="text/javascript">
    var gridComp=$("#comp"), codigos=[], Fog_Cod; 
    $(function() { 
        gridComp.createGrid({        
            height: 250,caption:'&nbsp;Plan de Cuentas', 
            colModel: [
                { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 25,align:"center", hidden:false },
                { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                { label: 'Cuenta', name: 'Pld_Des', width: 150  },
                { label: 'Tipo', name: 'Pld_Tip', width: 50, hidden:true  },
                { label: 'Tipo', name: 'Tipo', width: 50  },
                { label: 'Valor', name: 'Valor', width: 50, formatter:'currency'  },
                { label: 'Cod.Int.Old', name: 'Foc_Cod_Old', width: 50, hidden:true  },
                { label: 'Cod.Int.New', name: 'Foc_Cod_New', width: 50, hidden:true  },
                { label: 'Codigo', name: 'Codigo', width: 50, formatter:'codigo'  }
            ],
            loadComplete: function (data) { 
                var total = data.records;
                for(var i=0;i<total;i++){       
                    if(data.rows[i]['Pld_Tip'] ==='G') gridComp.find('#'+data.rows[i].Pld_Cod).addClass("grupos");
                }                    
            }
        },true,'#compPager');
        $('#codiDialog').createSearchDialog({
            colModel: [
                { label: 'Cód.Int.', name: 'Foc_Cod', key: true, width: 25,align:"center", hidden:false },
                { label: 'Grupo', name: 'Grupo', width: 50  },
                { label: 'SubGrupo', name: 'SubGrupo', width: 50  },
                { label: 'Codigo', name: 'Foc_Num', width: 45 },                      
                { label: 'Descripcion', name: 'Foc_Nom', width: 150  },
                { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:agregaCodigo} }
            ]
        },{options:[{label:'&nbsp;&nbsp;Descripción&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;&nbsp;&nbsp;Código&nbsp;&nbsp;&nbsp;&nbsp;',value:'c'}]});
    });
    function getCodiFormat(cv,opts,cObjt){
        var id=opts.rowId, c=opts.colModel, el;
        var obj,valid=($.varValid(cObjt['Foc_Cod'])&&cObjt['Foc_Cod']!=='');
        if(!valid){         
            el=$('<input />').attr({type:"text",class:'editable inline-edit-cell '+$.jgrid.styleUI.jQueryUI.inlinedit.inputClass,style:"width: 96%;",'data-gid':opts.gid,'data-name':c.name,'data-row-id':id,value:$.vv(cv)?cv:''});
            el.attr({ 'value':cObjt['Foc_Num'], 'onkeyup':'if(event.key === "Enter") searchCodigo( $(this) );', 'onkeypress':'return validar_numeric(event);' } );
        }else{
            el=$('<span type="text" class="form-control center" title="'+cObjt['Foc_Num']+' '+cObjt['Foc_Nom']+'">'+(valid?cObjt['Foc_Num']:'')+'</span>');
        }
        
        obj=$('<div class="input-group input-group-xs ret">'+el.prop('outerHTML')+'<span class="input-group-btn"><button type="button" onclick="'+(valid?'elimina':'selecciona')+'Codigo($(this).parent().data(\'originaldata\'));" class="btn btn-'+(valid?'warning':'info')+'" title="'+(valid?'Quitar':'Agregar')+' Codigo" tabindex="-1"><i class="glyphicon glyphicon-'+(valid?'minus':'plus')+'"></i></button></span></div>');
        obj.find('.input-group-btn').attr('data-originaldata',$.jsonParser($.extend(cObjt,valid?{}:{search:'',op_opciones:'d',Fog_Cod:Fog_Cod, Pld_Cod:cObjt['Pld_Cod']})));
        return obj.prop('outerHTML');
    }
    function searchCodigo(el){
        var data=el.next().data('originaldata');
        var val=el.val();
        var cod=$.arrayGetItem(codigos,'Foc_Num',val);
        cod['Foc_Cod_New']=cod['Foc_Cod'];
        cod['Codigo']=cod['Foc_Num'];
        gridComp.changeRow(data['Pld_Cod'],cod);
    }
    function seleccionaCodigo(data){          
        $('#codiForm').setData(data).formSubmit(); 
        $('#codiForm').find('.radioset').buttonset( "refresh" );
        $('#codiDialog').dialog('open');
        //console.log(data);
    }
    function agregaCodigo(data){ 
        var form=$('#codiForm').getData(); 
        data['Foc_Cod_New']=data['Foc_Cod'];
        data['Codigo']=data['Foc_Num'];
        gridComp.changeRow(form['Pld_Cod'],data); 
        $('#codiDialog').dialog('close'); 
    }
    function eliminaCodigo(data){  
        gridComp.changeRow(data['Pld_Cod'],
            {"Grupo":null,"SubGrupo":null,"Foc_Cod":null,"Fog_Cod":null,"Foc_Num":null,"Foc_Nom":null,"Foc_Est":null,"Codigo":null,"Foc_Cod_New":null}
        );
    }
    $.fn.fmatter.codigo=function(cv,opts,cObjt){ /*if(!$.varValid(cObjt['Foc_Cod'])||cObjt['Foc_Cod']==='') return '';*/ if(cObjt['Pld_Tip']==='G') return ''; return getCodiFormat(cv,opts,cObjt);  };
    $.fn.fmatter.codigo.unformat=$.unformatCellHtml;
    function updatePlan(){
        var data=$('#formulario').getData('getPlan');
        //var data={getPlan:true, Pla_Cod:$('#Pla_Cod').val(), Fog_Cod:$('#Fog_Cod').val()};
        //data['Pla_Cod']=data['Fog_Cod']=1;
        console.log(data);
        Fog_Cod=data['Fog_Cod'];
        if($.isEmpty(data['Pla_Cod']) || $.isEmpty(data['Fog_Cod'])){
            gridComp.clearGrid();
            return $.alert("Seleccione <i>Plan de Cuentas</i> y <i>Formulario</i> a parametrizar!");
        }
        $.getDataJson('', data , function(r) {
            gridComp.setRows(r['rows']);
            codigos=r['codigos'];
        });
    }
    function validaFormulario(){
        var data=$.extend(true,[],gridComp.getGridBatch(function(o){ return o.Pld_Tip==='D'; }));
        if(data.length===0) return $.alert("No hay cuentas para parametrizar!");
        $.arraySpliceWhere(data,'Foc_Cod_New','');
        $.arraySpliceFields(data,['Codigo','Pld_Cdc','Pld_Des','Pld_Tip','Tipo']);
        if(data.length===0) return $.alert("No hubo cambios en la parametrizacion!");
        $.createDialogConfirm('¿Esta seguro de guardar la Parametización?',data,saveFormulario);       
    }
    function saveFormulario(data){
        $.saveDataJson('',{saveFormulario:true, data:data},
        function (r){
             updatePlan();
        }); 
    }
   </script>
   <div id="codiDialog" title="Buscar Codigos Formulario"><form  id="codiForm"><input type="hidden" name="Fog_Cod" /><input type="hidden" name="Pld_Cod" /></form></div>
</BODY>
</HTML>