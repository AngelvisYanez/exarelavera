<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_liquidacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Liquidacion;

$hoy = date("Y-m-d");

if(isset($provAjax)){
    $page=$obBD_con1->getPageGridJson('productor_bana.selectWhere', $_GET, $obBD_conexion);
}
if(isset($searchLiquid)){
    $obBD_con1->getPageGridJson('productor_tarja.selectWhere', array_merge(array('setWhere'=>array('setProductor')),$_GET), $obBD_conexion,true);
}
$marcas=$obBD_con1->getArrayConsulta('banano_marca.selectWhere',  array('setWhere'=>array('setEmpCod','isActive')), $obBD_conexion); 
$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>'setEmpCod','order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion); 
$cur_periodo=current($periodos);
$linkLiqui=baseUrl("../../bananero/FRONT/ban_pri_liquidacion.php?Lib_Cod=");
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript">        
        var buttonExtra={ label: $.createIcon('print'), name: 'imp1', width: 25, formatter:'gridButton', formatoptions:{action:'imprimirLiquida(true,$(this).data(\'originaldata\'))',data:'Prt_Cod',icon:'print',type:'info',title:'Imprimir Liquidacion No. 1', conditional:function(o){ return o.Prt_Est!=='I'; }, caseFalse: $.createIcon('remove red') } , classes:'bgNoColor' };
    </script>
    <script type="text/javascript" src="../VALIDACIONES/ban_val_tarja.js"></script>
    <style></style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Tarjas</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-xs-12">
                    <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:liquidaciones.Search('#formDocumento','searchLiquid');"> 
                        <input name="order" type="hidden" value="" />
                        <fieldset class="exa-fieldset" id="provFormTemp">
                            <legend class="Titulos2">Consulta de Información</legend>
                        <div class="col-sm-4">    
                            
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Periodo:</label>  
                                <div class="col-xs-7" >
                                    <select  t id="Lib_Ano" name="Prt_Ano" class="form-control input-xs" >
                                        <option value="">Periodo..</option>
                                        <?php foreach ($periodos as $p) { echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>"; } ?>
                                    </select>
                                </div>  

                            </div> 
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Semana:</label>  
                                <div class="col-xs-9" ><select id="Prt_Sem" name="Prt_Sem" class="form-control input-xs" ></select></div>                            
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Marca:</label>
                                <div class="col-xs-9" >
                                    <select id="Bam_Cod" name="Bam_Cod" class="form-control input-xs getData ins"s>
                                        <?php if(count($marcas)!=1){ ?><option value="">Selecione Marca...</option><?php } ?>
                                        <?php foreach ($marcas as $m) {
                                            echo "<option value='$m[Bam_Cod]' data--bam_-cod='$m[Bam_Cod]' data--bam_-tam='$m[Bam_Tam]'>$m[Bam_Nom] $m[Bam_Tam]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Cédula/RUC:</label>  
                                <div class="col-xs-9" >
                                  <input name="Prd_Cod" data-name="Prd_Cod" type="text" style="display:none;" />
                                  <input name="Prv_Cod" data-name="Prv_Cod" type="text" style="display:none;" />
                                  <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">  
                                  <div class="input-group input-group-xs">                                          
                                    <input name="search" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#provDialog',selectProvee); }" type="text" placeholder="Ingrese Productor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                    <span class="input-group-btn">
                                         <button id="Prv_Btn" type="button" onclick="selectProvee({})" class="btn btn-success btn-xs" title="Buscar Productor" ><span class="glyphicon glyphicon-eject"></span></button>
                                        <button id="Prv_Btn" type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Productor" ><span class="glyphicon glyphicon-search"></span></button>
                                        <!--<button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open'); $('#reset').val(1); " class="btn btn-success btn-xs" title="Registrar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>-->    
                                    </span>
                                  </div>
                                </div>                              
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Productor:</label>  
                                <div class="col-xs-9" >
                                    <span name="Productor" data-name="Productor" class="form-control input-xs databind datatitle"></span>
                                </div>
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">No.&nbsp;Tarja:</label>  
                                <div class="col-xs-9" >
                                    <input name="Prt_Num" class="form-control input-xs " />
                                </div>
                            </div> 
                        </div>   
                        <div class="col-sm-4">    
                            <div class="center">
                                <button type="button" onclick="$('#formDocumento').formSubmit();" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Cargar Datos</button>
                            </div>
                        </div>    
                        </fieldset>
                    
                    </form> 
                </div>
                <div class="col-xs-12">
                    <div style="min-height: 280px">
                        <table id="liquidaciones"></table>
                        <div id="liquidacionesPager"></div> 
                    </div>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop green"></span> Ya Fue Usado en Liquidaciones | <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos </div>
                </div>
            </div> 
        </div>
    </div>


    <script type="text/javascript">        
    
    function imprimirLiquida(legal, Lib_Cod){
        $.imprimirUrl("<?php echo $linkLiqui; ?>"+Lib_Cod+(legal?"":"&detallado="));
    }
    
    </script>
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTOR--> 
    <div id="provDialog" title="B&uacute;squeda de Productor"></div>     
    
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>



