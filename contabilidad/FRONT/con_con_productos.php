<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_docs.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Doc;

$hoy = date("Y-m-d");

/* buscar cuentas contables */
if(isset($cuenAjax)){     
    $responce=$obBD_con1->getPageGridJson('det_plan.selectWhere', array_merge($_GET,array('setWhere'=>array('byPecCod','isActive','isDetalle'))), $obBD_conexion);    
}
/* buscar productos */
if(isset($productos)){     
    $rows=$obBD_con1->getPageGridJson('compras.selectWhere', array_merge($_GET,array('where'=>array('det_compra.Pld_Cod'=>$Pld_Cod,"Cop_Fec BETWEEN '$Cop_Ini' AND '$Cop_Fin'"),'order'=>'Cop_Fec DESC','setWhere'=>array("setEmpCod",'isActive','setTotalDetalle'),'addCols'=>array(''=>$obBD_con1->expr("CONCAT(compras.Cop_Cod,'_',det_compra.Pro_Cod,'_',det_compra.Cop_Int)AS Det_Cod")))), $obBD_conexion);        
}
$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>'setEmpCod','order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion); 
$cur_periodo=current($periodos);
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
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Productos por Cuenta</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-4">
                    <form id="formCuenta" class="form-horizontal normal">
                    <input type="hidden" id="Pld_Cod" name="Pld_Cod" data-name="Pld_Cod" />    
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Cuentas Contables</legend>
                        <div class="form-group">
                            <label class="col-xs-5 control-label label-xs required">Periodo Contable</label>  
                            <div class="col-xs-7" >
                                <div class="input-group input-group-xs">
                                    <select id="Pec_Cod" onchange="changePeriodo();" class="form-control input-xs">
                                        <?php foreach ($periodos as $p) {
                                            echo "<option data--year='$p[Year]' data--pec_-fei='$p[Pec_Fei]' data--pec_-fef='$p[Pec_Fef]' data--pec_-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
                                        } ?>
                                    </select>
                                    <span class="input-group-btn">
                                        <button id="Prv_Btn" type="button" onclick="$.Search('cuen'); $('#cuenDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>                                    
                                    </span>
                                </div>
                            </div>    
                        </div>
                        <div class="form-group">
                            <label class="col-xs-5 control-label label-xs required">Codigo:</label>  
                            <div class="col-xs-7" ><span data-name="Pld_Cdc" class="form-control input-xs datatitle" tabindex="-1" ></span></div>                                                                  
                        </div>
                        <div class="form-group">
                            <label class="col-xs-5 control-label label-xs">Cuenta:</label>  
                            <div class="col-xs-7" ><span data-name="Pld_Des" class="form-control input-xs datatitle" tabindex="-1" ></span></div>                                                                  
                        </div>
                        <div class="form-group">
                            <label class="col-xs-5 control-label label-xs">Grupo:</label>  
                            <div class="col-xs-7" ><span data-name="Pld_Grupo" class="form-control input-xs datatitle" tabindex="-1" ></span></div>                                                                  
                        </div>
                    </fieldset>
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Rango</legend>
                        <div class="form-group">
                            <label class="col-xs-5 control-label label-xs required">Desde:</label>  
                            <div class="col-xs-5" ><input type="text" id="Cop_Ini" name="Cop_Ini" data-fecha="Pec_Fei" class="form-control input-xs" /></div>                                                                  
                        </div>
                        <div class="form-group">
                            <label class="col-xs-5 control-label label-xs required">Hasta:</label>  
                            <div class="col-xs-5" ><input type="text" id="Cop_Fin" name="Cop_Fin" data-fecha="Pec_Fef" class="form-control input-xs" /></div>                                                                  
                        </div>
                        <div class="separator"></div>
                        <div class="form-group center">
                            <button type="button" onclick="if($('#Pld_Cod').val()==='') $.alert('Debe seleccionar una <u class=\'green\'>Cuenta Contable</u>'); else $('#productos').Search('#formCuenta','productos');" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button>
                        </div>
                    </fieldset>    
                    </form>    
                </div>
                <div class="col-sm-8">
                    <div id="productosDiv">
                        <table id="productos"></table>
                        <div id="productosPager"></div>
                    </div>
                </div>
            </div>            
        </div>
    </div>


    <script type="text/javascript">
    $(function(){
        $("#productos").createGrid({
            caption:'Productos Facturados', datatype:'local', height: 295, rowNums:10000000,
            colModel: [
                {label: 'C&oacute;d. Int.', name: 'Det_Cod', width: 10, key:true, hidden:true},
                {label: 'C&oacute;d. Int.', name: 'Cop_Cod', width: 15, hidden:true},
                {label: 'Fecha', name: 'Cop_Fec', width: 30, align: "center" },
                {label: 'Num. Doc.', name: 'Cop_Num', width: 50, align: "left" },
                //{label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 40, align: "left"},
                //{label: 'Proveedor', name: 'Proveedor', width: 75, align: "left"},
                {label: 'Producto', name: 'Producto', width: 75, align: "left"}, 
                {label: 'Cant.', name: 'Cop_Can', width: 20, align: "right", formatter:'number' },
                {label: 'Prec.', name: 'Cop_Pru', width: 20, align: "right", formatter:'number' },
                {label: 'Desc.', name: 'Descu', width: 20, align: "right", formatter:'number' },
                {label: 'Subt.', name: 'Importe_Descu', width: 20, align: "right", formatter:'number', classes:'columnHighlight3' },
                {label: 'ICE', name: 'Ice_Tot', width: 20, align: "right", formatter:'number' },
                {label: 'IVA', name: 'Iva_Tot', width: 20, align: "right", formatter:'number' },
                {label: 'Total', name: 'Total', width: 30, align: "right", formatter:'currency', classes:'columnHighlight1' }
            ]
        }, true, "#productosPager", {view:true, refresh:true});
        $.createDateRange('#Cop_Ini','#Cop_Fin'); 
        $("#Pec_Cod").trigger('change');
    });
    function changePeriodo(){
        clearCuentas(); 
        var Pec=$('#Pec_Cod').find('option:selected').data();        
        $('#Cop_Ini').dateLimits(Pec['Pec_Fei'], Pec['Pec_Fef']);
        $('#Cop_Fin').dateLimits(Pec['Pec_Fei'], Pec['Pec_Fef']);
        $('#formCuenta').setData(Pec,'fecha'); 
        $('#cuenForm').setData(Pec,'name');
    }
    </script>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
    
    <script>
        if($('#cuenDialog').length>0)
        $('#cuenDialog').createSearchDialog({
              datatype: 'local',
              colModel:[                   
                { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center", hidden:false },                                
                { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr:$.cellAjust },
                { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr:$.cellAjust },
                //{ label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center", formatter:'estado', formatoptions:{types:{D:'Detalle',G:'Grupo'}} },
                { label: 'Estado', name: 'Pld_Est', width: 30,align:"center", formatter:'estado', title:false}, 
                { label: $.createIcon('cog'), name: 'act1', width: 30, align: 'center', viewable: false, formatter:'gridButton', formatoptions:{action:'SelectCta', title:'Seleccionar Cuenta', data:'Pld_Cod'} }
            ]},{ title:'Cuenta', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] })
        .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:&nbsp;</label><input id="Index" name="Index" type="hidden" value="" /><input data-name="Pec_Cod" name="Pec_Cod" type="hidden" /><input data-name="Year" name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>'); 
        function SelectCta(Pld_Cod){  
            $('#formCuenta').setData($.getDialogGrid("#cuenDialog").jqGrid('getRowData', Pld_Cod), 'name' );  
            $('#cuenDialog').dialog('close');            
        }
        function clearCuentas(){
            $('#formCuenta').setData({},'name');
        }
    </script>
</BODY>
</HTML>