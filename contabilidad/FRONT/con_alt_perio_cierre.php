<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new MysqlDatos(true);
$hoy = date("Y-m-d");

if(isset($gridAjax)){
    $resp=$obBD_con1->getPageGridJson('perio_cierre.selectWhere', array_merge($_GET,array('order'=>'Year DESC,Pci_Ini DESC','setWhere'=>array())) );
}
if(isset($anulaData)||isset($saveData)){
    $resp=array();    
    $oBdSet = new MysqlDatos($obBD_con1->getMyCon());
    //$oBdSet->debug(true);
    $oBdSet->beginTrans();
    try{  
        if(isset($anulaData))
        $oBdSet->operation('perio_cierre.update', array('Pci_Est'=>'I','where'=>array('Pci_Cod'=>$id))); 
        else if(isset($saveData)){
            $cierres=$oBdSet->getArray('perio_cierre.selectWhere', array('Pci_Tip'=>$form['Pci_Tip'], 'Pci_Num'=>$form['Pci_Num'],'Pci_Tri'=>(!empty($form['Pci_Tri'])?'S':NULL) ,'perio_cont.Pec_Cod'=>$form['Pec_Cod'], 'setWhere'=>array("setEmpCod",'isActive')));
            if(count($cierres)>0) throw new Exception("Ya exite un cierre registrado para esta frecuencia!");
            $form['Pci_Tri']=!empty($form['Pci_Tri'])?'S':NULL;
            $oBdSet->operation('perio_cierre.insert', $form); 
        }
        //if($oBdSet->getError()==0) throw new Exception("Probando: Todo se guardo bien!");
        $oBdSet->endTrans($resp);
    } catch(Exception $e){ $oBdSet->revertTrans($e->getMessage(),$resp); }
    $oBdSet->echoJson($resp);
}
$periodos=$obBD_con1->getArray('perio_cont.selectWhere', array('setWhere'=>array("setEmpCod",'isActive',"order")));
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style></style>
</HEAD>
<BODY>
<div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Cerrar Periodo Contable</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="row">
            <div class="col-sm-6">
                <div>
                    <table id="cierresGrid"></table><div id="cierresGridPager"></div>
                </div>
            </div>
            <div id="divEdit" class="col-sm-6" style="display: none">
                <form id="formCierre" action="javascript:saveCierre();" class="form-horizontal normal" >
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos Cierre</legend>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Periodo:</label>  
                            <div class="col-xs-4" >
                                <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" required="" onchange="$('#Pci_Tip').val('').trigger('change');">
                                    <option value="">Periodo..</option>
                                    <?php foreach ($periodos as $p) { echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>$p[Year]</option>"; } ?>
                                </select>
                            </div> 
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Frecuencia:</label>  
                            <div class="col-xs-4" >
                                <select id="Pci_Tip" name="Pci_Tip" class="form-control input-xs readOnly" required="" onchange="setIntervalos()" ></select>
                            </div> 
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Intervalo:</label>  
                            <div class="col-xs-4" >
                                <select id="Pci_Num" name="Pci_Num" class="form-control input-xs" required="" onchange="setFechaCierre()" ></select>
                            </div> 
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Inicio:</label>  
                            <div class="col-xs-4" >
                                <input type="text" id="Pci_Ini" name="Pci_Ini" class="form-control input-xs" readonly="" required="" />
                            </div> 
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Inicio:</label>  
                            <div class="col-xs-4" >
                                <input type="text" id="Pci_Fin" name="Pci_Fin" class="form-control input-xs" readonly="" required="" />
                            </div> 
                        </div>
                        <div class="form-group">     
                            <label class="col-xs-4 control-label label-xs"></label>                        
                            <div class="col-xs-5" >
                                <label ><input type="checkbox" id="Pci_Tri" name="Pci_Tri" class="check-big" />  Bloqueo Tributario [Ventas/Compras/Retencion]</label>
                            </div> 
                        </div>
                        <div class="form-group center">
                            <button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div> 
    </div>
</div>


<script type="text/javascript">
var cierres;
$(function(){  
    var frecuencia={M:'Mensual',B:'Bimensual',T:'Trimestral',S:'Semestral',A:'Anual'};
    cierres=$('#cierresGrid');
    cierres.createGrid({
        caption:"Listado de Cierres", height:265, stateCol:'Pci_Est',postData:{gridAjax:true},
               
        colModel:[
            { label: 'C&oacute;d. Int.', name: 'Pci_Cod', width: 15, align: "center", key:true, hidden:true },   
            { label: 'Periodo', name: 'Year', width: 10, align: "center", hidden:true},
            { label: 'Numero', name: 'Pci_Num', width: 8, align: "center"},
            { label: 'Frecuencia', name: 'Pci_Tip', width: 15, align: "center", formatter:"estado", formatoptions:{full:true,types:frecuencia} },
            { label: 'Inicio Cierre', name: 'Pci_Ini', width: 15, align: "center"},
            { label: 'Fin Cierre', name: 'Pci_Fin', width: 15, align: "center"},
            { label: 'Bloq. Trib.', name: 'Pci_Tri', title:"Bloqueo Tributario", width: 8, align: "center", formatter: 'truefalse', formatoptions: { yesMsg: function (o) { return ('Bloqueo Tributatio'); }, noMsg: ' ' }},
            { label: 'Estado', name: 'Pci_Est', width: 10 , align:"center", formatter:"estado", formatoptions:{full:true} },
            { label: $.createIcon('calendar'), name: 'Pci_Sys', width: 7, align: "center", formatter:'truefalse', formatoptions:{ yesMsg:function(o){ return o.Pci_Sys; }, noMsg:' ', yesIcon:'info-sign', noIcon:' ', yesColor:'blue', noText:true }, title:false },
            { label: $.createIcon('trash'), name: 'act01', align: "center", width: 10, formatter: 'gridButton', formatoptions: { action:'anularCierre', data:'Pci_Cod', conditional:function (o) { return o.Pci_Est !== 'I'; }, caseFalse:$.createIcon('remove red'), title: 'Anular Cierre', icon:'trash', type:'danger' } }
        ], leyenda:[{label:'Anulado/Inactivo',icon:'remove red'}],grouping:true, 
        groupingView:{ groupField : ['Year'],groupOrder:[ 'desc'],groupColumnShow:[false],groupText:['<div class="txtLeft">Periodo {0}</div>'] } 
    },false,'#cierresGridPager').gridButtonsAdd([ null, { caption: 'Agregar Cierre', buttonicon: 'plus', onClickButton: function(){ $('#formCierre').setData({}); $('#divEdit').show(); } } ]);  
    $('#Pci_Tip').fillSelect(frecuencia);
});
function setIntervalos(){
    var g=['Primer','Segundo','Tercer','Cuarto','Quinto','Sexto','Septimo','Octavo','Noveno','Decimo','Onceavo','Doceavo'],u;
    var form=$('#formCierre'),inter=$('#Pci_Num'),data=form.getData(),fechas={Pci_Num:'',Pci_Ini:'',Pci_Fin:''};
    inter.html('<option value="">Seleccione...</option>');
    form.setData(fechas,false);
    if(data['Pec_Cod']===''||data['Pci_Tip']==='') return; 
    var i,l,ite={},pec=$('#Pec_Cod option:selected').data();
    if(data['Pci_Tip']==='A'){
         inter.prop('disabled',true);
         inter.html('<option value="1" selected="">A�o '+pec['Year']+'</option>');
         fechas['Pci_Num']=1;
         fechas['Pci_Ini']=pec['Year']+'-01-01';
         fechas['Pci_Fin']=pec['Year']+'-12-31';
         form.setData(fechas,false);
         return;
    }
    inter.prop('disabled',false);
    switch(data['Pci_Tip']){
        case 'M': i=12; l='Mes'; break;
        case 'B': i=6; l='Bimestre'; break;
        case 'T': i=4; l='Trimestre'; break;
        case 'S': i=2; l='Semestre'; break;
        case 'A': i=1; l='A�o'; break;
    }
    for(var j=1;j<=i;j++){
        ite[j]=(j)+' - '+g[j-1]+' '+l;
    }
    inter.fillSelect(ite,u,u).val('').trigger('change');
}
function setFechaCierre(){
    var form=$('#formCierre'),data=form.getData(),fechas={Pci_Ini:'',Pci_Fin:''},i;
    form.setData(fechas,false);
    if(data['Pec_Cod']===''||data['Pci_Tip']===''||data['Pci_Num']==='') return;
    var i,pec=$('#Pec_Cod option:selected').data(),mes_ini,mes_fin;    
    switch(data['Pci_Tip']){
        case 'M': mes_ini=data['Pci_Num']*1; mes_fin=data['Pci_Num']*1; break;
        case 'B': i=2; mes_ini=(data['Pci_Num']*i-1); mes_fin=(((data['Pci_Num']*1)+1)*i-i); break;
        case 'T': i=3; mes_ini=(data['Pci_Num']*i-2); mes_fin=(((data['Pci_Num']*1)+1)*i-i); break;
        case 'S': i=6; mes_ini=(data['Pci_Num']*i-5); mes_fin=(((data['Pci_Num']*1)+1)*i-i); break;        
    }
    fechas['Pci_Ini']=pec['Year']+'-'+mes_ini.padLeft(2,'0')+'-01';
    fechas['Pci_Fin']=pec['Year']+'-'+mes_fin.padLeft(2,'0')+'-'+lastDayOfMonth(pec['Year'],mes_fin).padLeft(2,'0');
    form.setData(fechas,false);
}
function saveCierre(){
    $.createDialogConfirm('�Est&aacute; seguro que desea Guardar el Cierre?',{saveData:true, form:$('#formCierre').getData()},function(data){
        $.saveDataJson("", data, function (r){
            $('#divEdit').hide();
            cierres.gridUpdate(); 
        });
    });
}
function anularCierre(Pci_Cod){
    $.createDialogConfirm('�Est&aacute; seguro que desea desactivar el Cierre?',{anulaData:true, id:Pci_Cod},function(data){
        $.saveDataJson("", data, function (r){
            cierres.changeRowData(Pci_Cod,{Pci_Est:'I',act01:''}); 
        });
    });
}
function lastDayOfMonth(year,month){var date=new Date(year,month*1-1,1); return new Date(date.getFullYear(),date.getMonth()+1,0).getDate(); }
</script>
</BODY>
</HTML>