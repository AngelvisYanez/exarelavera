<?php
/**
* @abstract Permite realizar el registro del mantenimiento de un activo
* @author José Ambuludí
* @version 2.1
* Fecha de creación  2016-08-16
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_mantenimie.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Mantenimiento($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Mantenimiento;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

/*Sección para cargar datos en el Jqgrid referente a los encargados*/
if(isset($encargadoAjax)){
    $data=filter_input_array(INPUT_GET);
    $data["Emp_Cod"]=$Ses_Emp_Cod;
    $contar=$obBD_con1->getRowConsulta(5004, $data, $obBD_conexion);
    $pagination=  pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0)
        $responce['rows']=$obBD_con1->getArrayConsulta(5004,$data,$obBD_conexion);
    echo json_encode($responce);
    exit();
}

/*Sección para cargar datos en el Jqgrid referente a los activos*/
if(isset($activoAjax)){
    $data=filter_input_array(INPUT_GET);
    $data["Suc_Cod"]=$Ses_Suc_Cod;
    $contar=$obBD_con1->getRowConsulta(5005, $data, $obBD_conexion);
    $pagination=  pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0)
        $responce['rows']=$obBD_con1->getArrayConsulta(5005,$data,$obBD_conexion);
    echo json_encode($responce);
    exit();
}
?>

<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registro de Mantenimiento</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <div class="col-sm-6">
                                <form id="formMantenimiento" name="formMantenimiento" class="form-horizontal normal">
                                    <!-- Input group -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">Encargado Mant.:</label>
                                        <div class="col-sm-9">
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="encargado" id="encargado" class="form-control input-sm" placeholder="Seleccione un encargado" readonly>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success" onclick="$('#encargadoDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-search"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Input group -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">Activo:</label>
                                        <div class="col-sm-9">
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="activo" id="activo" class="form-control input-sm" placeholder="Seleccione un activo" readonly>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success" onclick="$('#activoDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-search"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Select básico -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm required">Tipo Mantenimiento:</label>
                                        <div class="col-sm-9">
                                            <select name="Tma_Cod" id="Tma_Cod" class="form-control input-sm" required>
                                                <option value="">Seleccione una opción</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Select básico -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">Estado:</label>
                                        <div class="col-sm-9">
                                            <select name="Est_Cod" id="Est_Cod" class="form-control input-sm" required>
                                                <option value="">Seleccione un estado</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Textarea -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">Descripci&oacute;n:</label>
                                        <div class="col-sm-9">
                                            <textarea name="Man_Des" id="Man_Des" class="form-control input-sm" required></textarea>
                                        </div>
                                    </div>
                                    
                                    <!-- Text input -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">Fecha Mantenimiento:</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="Man_Reg" id="Man_Reg" class="form-control input-sm" placeholder="Fecha de registro de mantenimiento">
                                        </div>
                                    </div>
                                    
                                    <!-- Text input -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">Fecha Pr&oacute;ximo Mant.:</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="Man_Fec" id="Man_Fec" class="form-control input-sm" placeholder="Fecha de pr&oacute;ximo mantenimiento">
                                        </div>
                                    </div>
                                    
                                    <!-- Text input -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">Fecha Cumpli&oacute; Mant.:</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="Man_Fet" id="Man_Fet" class="form-control input-sm" placeholder="Fecha en que cumpli&oacute; mantenimiento">
                                        </div>
                                    </div>
                                    
                                    <!-- Textarea -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">Observaci&oacute;n:</label>
                                        <div class="col-sm-9">
                                            <textarea name="Man_Obs" id="Man_Obs" class="form-control input-sm"></textarea>
                                        </div>
                                    </div>
                                    
                                    <!-- Select básico -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">Mantenimiento:</label>
                                        <div class="col-sm-9">
                                            <select name="Man_Pro" id="Man_Pro" class="form-control input-sm">
                                                <option value="F">Finalizado</option>
                                                <option value="P">Vigente - En Proceso</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Text input -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">&Uacute;ltimo Kilometraje:</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="Man_Kma" id="Man_Kma" class="form-control input-sm">
                                        </div>
                                    </div>
                                    
                                    <!-- Text input -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">Km Pr&oacute;ximo Mant.:</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="Man_Kmf" id="Man_Kmf" class="form-control input-sm">
                                        </div>
                                    </div>
                                    
                                    <!-- Text input -->
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-sm">N&uacute;mero Km L&iacute;mite:</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="Man_Kmt" id="Man_Kmt" class="form-control input-sm">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Diálogo para la búsqueda de encargados de mantenimiento -->
        <div id="encargadoDialog" title="B&uacute;squeda Encargado de Mantenimiento">
            <form class="form-horizontal normal">
                <fieldset>
                    <legend>Filtros</legend>
                    <div class="form-group">
                        <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>
                        <div class="col-md-8 radioset">
                            <input id="rad1" name="op_encargado" type="radio" value="d" checked="" onclick="setfocus(this.form.search_encargado)"/><label for="rad1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                            <input id="rad2" name="op_encargado" type="radio" value="c" onclick="setfocus(this.form.search_encargado)"/><label for="rad2">&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">B&uacute;squeda:</label>
                        <div class="col-md-7">
                            <div class="input-group">
                                <input type="text" name="search_encargado" class="form-control input-sm" onkeydown="if(event.keyCode===13) this.form.submit()" placeholder="Ingrese encargado a buscar" autofocus/>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-success btn-sm" onclick="this.form.submit()" title="Buscar Encargado"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button>
                                </span>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>
        
        <!-- Diálogo para la búsqueda de activos -->
        <div id="activoDialog" title="B&uacute;squeda de Activos Fijos">
            <form class="form-horizontal normal">
                <fieldset>
                    <legend>Filtros</legend>
                    <div class="form-group">
                        <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>
                        <div class="col-md-8 radioset">
                            <input id="rad3" name="op_activo" type="radio" value="d" checked="" onclick="setfocus(this.form.search_activo)"/><label for="rad3">&nbsp;&nbsp;Nombre&nbsp;&nbsp;</label>
                            <input id="rad4" name="op_activo" type="radio" value="c" onclick="setfocus(this.form.search_activo)"/><label for="rad4">&nbsp;&nbsp;Cod. Barras&nbsp;&nbsp;</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label label-sm">B&uacute;squeda</label>
                        <div class="col-md-7">
                            <div class="input-group">
                                <input type="text" name="search_activo" class="form-control input-sm" onkeydown="if(event.keyCode===13)this.form.submit()" placeholder="Ingrese activo a buscar" autofocus/>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-success btn-sm" onclick="this.form.submit()" title="Buscar Activo"><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button>
                                </span>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>
        
    </BODY>
    
    <script type="text/javascript">
        $(document).ready(function(){
            $.createDatePickers('#Man_Reg');
            $.createDatePickers('#Man_Fec');
            $.createDatePickers('#Man_Fet');
            $('#Man_Reg').val('');
            $('#Man_Fec').val('');
            $('#Man_Fet').val('');
        });
        
        //Diálogo para buscar encargado de mantenimiento
        $(document).ready(function(){
            $.createSearchDialog('#encargadoDialog',[
                {label:'Cod.Int.',name:'Ema_Cod',key:true,hidden:true},
                {label:'C&eacute;dula',name:'Prs_Ced',width:40},
                {label:'Encargado',name:'encargado',width:100},
                {label:'Especialidad',name:'Ema_Esp',width:80},
                {label:'<center><i class="ui-icon ui-icon-gear"></i></center>',name:'act1',width:18,align:'center',
                    formatter:function (cellvalue,options,rowObject){
                        var clic='$("#productoDialog").dialog("close");';
                        return '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>';
                    }
                }
            ]);
        });
        
        //Diálogo para buscar activo
        $(document).ready(function(){
            $.createSearchDialog('#activoDialog',[
                {label:'Cod.Int.',name:'Act_Cod',key:true,hidden:true},
                {label:'Cod. Barras',name:'Act_Bar',width:60},
                {label:'Activo',name:'Act_Des',width:100},
                {label:'Estado',name:'Est_Des',width:40},
                {label:'<center><i class="ui-icon ui-icon-gear"></i></center>',name:'act1',width:18,align:'center',
                    formatter:function (cellvalue,options,rowObject){
                        var clic='$("#activoDialog").dialog("close");';
                        return '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>';
                    }
                }
            ]);
        });
    </script>
</HTML>