<?php	
/**
* @abstract Permite realizar el registro de la depreciaci�n por activo
* @author Jos� Ambulud�
* @version 1.0
* Fecha de creaci?n  2016-08-29
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_depreciacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Depreciacion($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Depreciacion;

/*Secci�n para presentar los periodos contables*/
if(isset($periodoContable)){
    $response=$obBD_con1->getArrayConsulta(1,$Ses_Emp_Cod, $obBD_conexion);
    echo json_encode($response);
    exit();
}
/*Secci�n para extraer todos los activos de la tabla del mismo nombre*/
if(isset($allActivos)){
    $data['Suc_Cod']=$Ses_Suc_Cod;
    $response=$obBD_con1->getArrayConsulta(2, $data, $obBD_conexion);
    $activos=array('activos'=>array());
    foreach ($response as $row){
        $varia=array(
            'Act_Cod'=>$row['Act_Cod'],
            'Act_Val'=>$row['Act_Val'],
            'Act_Res'=>$row['Act_Res'],
            'Act_Ann'=>$row['Act_Ann'],
            'Act_Des'=>$row['Act_Des'],
            'Act_Ffd'=>$row['Act_Ffd'],
            'Act_Fec'=>$row['Act_Fec'],
            'Fec_Sis'=>$row['Fec_Sis'],
            'Fch_Ini'=>$row['Fch_Ini']
        );
        //Se construye la fecha pr�xima a depreciar
        if($row['Fch_Ini']=='vacio'){
            $varia['Acd_Fpd']=$row['Act_Fec'];
        }else{
            $descomponer=explode("-",$row['Fch_Ini']);
            $anio=$descomponer[0];$mes=$descomponer[1];
            if($mes<12){$mes++;}else{$mes="01";$anio++;}
            $varia['Acd_Fpd']=$anio."-".str_pad($mes, 2, "0", STR_PAD_LEFT)."-01";
        }
        //Secci�n para cargar la configuracion de activos
        $rs_configuracion=$obBD_con1->getRowConsulta(11, $Ses_Suc_Cod, $obBD_conexion);
        $varia['Cfg_Ddp']=$rs_configuracion['Cfg_Ddp'];
        $varia['Cfg_Por']=$rs_configuracion['Cfg_Por'];
        //Secci�n para extraer los registros de un activo dentro de la tabla activo_deprecia
        //$data['Act_Cod']=$row['Act_Cod'];
        $depreciacion=$obBD_con1->getArrayConsulta(12,$row['Act_Cod'],$obBD_conexion);
        $varia['meses']=$depreciacion;
        //Secci�n para extraer la fecha mayor corresponsiente a un activo dentro de la tabla activo_deprecia
        $rs_fechamayor=$obBD_con1->getRowConsulta(3,$row['Act_Cod'], $obBD_conexion);
        $descomponerFpd=explode("-",$rs_fechamayor['Acd_Fpd']);
        $anio_Fpd=$descomponerFpd[0];$mes_Fpd=$descomponerFpd[1];
        $descomponerFfd=explode("-",$row['Act_Ffd']);
        $anio_Ffd=$descomponerFfd[0];$mes_Ffd=$descomponerFfd[1];
        if(($anio_Ffd!=$anio_Fpd)||($mes_Ffd!=$mes_Fpd)){array_push($activos['activos'],$varia);}
    }
    echo json_encode($activos);
    exit();
}
/*Secci�n para registrar la depreciaci�n*/
$sum_dep=0;
if(isset($saveDepreciacion)){
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $descomponer=explode("*",$Pec_Cod);
    $Pec_Cod=$descomponer[0];
    $Prv_Cod=$obBD_con1->getRowConsulta(4, $Ses_Emp_Cod, $obBD_conexion);
    $responce['Prv_Cod']=$Prv_Cod['Prv_Cod'];
    //Consulta el numero del comprobante de Egreso/Diario 
    $Com_Num= $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod, date("j"), $obBD_conexion); 
    $responce['Pec_Cod']=$Pec_Cod;
    $responce['Tia_Cod']=$Tia_Cod;
    $ban=1;
    foreach ($depreciacion as $row){
        if($ban===1){
            //Se realiza el insert en la tabla comprobante
            $operacionobBD = $obBD_con1->operacionobBD(5, $Pec_Cod.'*'.$Prv_Cod['Prv_Cod'].'*'.$Ses_Usu_Cod.'*'.$Com_Num.'*'.$row['Fec_Fin'].'*'.$row['Act_Dep'].'*'.$Tia_Cod.'*A', $obBD_conexion);
            //Secci�n para obtener el c�digo de la �ltima inserci�n en la tabla comprobante
            $Com_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
            $responce['Com_Cod']=$Com_Cod;
            $ban=0;
        }
        //Secci�n para insertar la depreciaci�n por activo dentro de la tabla activo_deprecia
        $operacionobBD = $obBD_con1->operacionobBD(6, $Com_Cod.'*'.$row['Act_Cod'].'*'.$row['Acd_Fpd'].'*M', $obBD_conexion);
        //Secci�n para guardar en la tabla asientos
        $row_cuenta=$obBD_con1->getArrayConsulta(7,$row['Act_Cod'],$obBD_conexion);
        foreach ($row_cuenta as $cuenta){
            if($cuenta['Acc_Tip']=='DE'){$Asi_Deh='D';}else{$Asi_Deh='H';}
            $operacionobBD = $obBD_con1->operacionobBD(8, $Com_Cod.'*'.$Asi_Deh.'*'.$row['Act_Dep'].'*'.$cuenta['Pld_Cod'], $obBD_conexion);
        }
        $sum_dep=$sum_dep+$row['Act_Dep'];
    }
    //Secci�n para actualizar la sumatoria de la depreciaci�n de los activos registrados dentro de un comprobante
    $operacionobBD = $obBD_con1->operacionobBD(9, $Com_Cod.'*'.$sum_dep, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; }  
    echo json_encode($responce);
    exit();
}
?>

<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
        <link href="../../framework/jquery/bootstrap/bootstrap-fileinput/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
        <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
        <script src="../../framework/jquery/bootstrap/bootstrap-fileinput/js/fileinput.js" type="text/javascript"></script>
        <script type="text/javascript" src="../VALIDACIONES/act_calcular_depreciacion.js"></script>
        <style>
            div.ui-jqdialog-content td.form-view-data {
                white-space: normal !important;
                height: auto;
                vertical-align: baseline;
                padding-top: 3px; padding-bottom: 3px;
            }
            #trv_tit td{background: #005580 !important; color: #ffffff;}
        </style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Depreciaci&oacute;n</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-md-12">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Proceso de Depreciamiento</legend>
                        <form id="formDepreciacion" name="formDepreciacion" class="form-horizontal normal" action="javascript:saveForm();"> 
                            <div class="col-md-4">
                            <div class="form-group">
                                <label class="col-md-5 control-label label-xs">Per&iacute;odo Contable:</label>  
                                <div class="col-md-7">
                                    <select name="Pec_Cod" id="Pec_Cod" class="form-control input-xs"></select>
                                </div>
                            </div>
                            <div id="mensual" class="form-group">
                                <label class="col-md-5 control-label label-xs">Mes:</label>  
                                <div class="col-md-7">
                                    <select name="mes" id="mes" class="form-control input-xs">
                                        <option value="0">Seleccione</option>
                                    </select>
                                </div>
                            </div>
                            </div>
                            <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-md-3 control-label label-xs">Tipo de Asiento:</label>  
                                <div class="col-md-6">
                                    <select name="Tia_Cod" id="Tia_Cod" class="form-control input-xs">
                                        <?php $row_tipo_asien=$obBD_con1->getArrayConsulta(10,'',$obBD_conexion);
                                            if(count($row_tipo_asien)>0){
                                                foreach($row_tipo_asien as $row){
                                        ?>
                                                <option value="<?php echo $row['Tia_Cod'];?>"><?php echo $row['Tia_Des'];?></option>
                                        <?php   }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-12">
                                    <table id="list"></table>
                                    <div id="listPager"></div>
                                </div>    
                            </div>
                            <div class="form-group">
                                <div class="col-md-1">
                                    <button type="submit" name="btn_guardar" id="btn_guardar" class="btn btn-primary btn-sm" disabled=""><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                </div>
                                <div id="ver" class="col-md-1" style="display: none;">
                                    <a href="" id="ver_asiento" target="_blank" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon glyphicon-list-alt"></span> Ver Asiento</a>
                                </div>
                            </div>
                        </form>
                    </fieldset> 
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
        //Declaraci�n de variables globales
        var depreciacion,activos_dep;
        $(function(){

            $('#Tia_Cod').val(4);//Seteamos valor de DIARIO DE DEPRECIACION en el combobox por defecto
            //Secci�n para cargar el comobobox del periodo contable v�a ajax
            $.post("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{periodoContable:true}, function( response ) {
                if(response.length>0){
                    var options="";
                    options+="<option>Seleccione</option>";
                    for(var i=0,z=response.length; i<z; i++){
                        options+= "<option value='"+response[i].Pec_Cod+"*"+response[i].Periodo+"'>" + response[i].Periodo + "</option>";
                    }
                    $("#Pec_Cod").html(options);
                }else{$.alert(response['message']);}
            },'json').fail(function() { $.alert();});

            $('#Pec_Cod').change(function(){
                cargar_meses();
                $('#ver').hide();
            });
            
            //Secci�n para obtener el mes seleccionado
            $('#mes').change(function(){
                depreciacion=new Array();
                var descomponer_pc=$('#Pec_Cod').val().split('*');
                var anio_pc=descomponer_pc[1];//anio del periodo contable seleccionado
                var descomponer_seleccion=$('#mes').val().split('*');
                var mes_se=descomponer_seleccion[0];
                var anio_se=descomponer_seleccion[1];
                var fec_sis=descomponer_seleccion[2];
                var fech1 = anio_se+'-'+mes_se+'-01';
                if((parseInt(anio_se)===parseInt(anio_pc))&&((Date.parse(fech1)) <= (Date.parse(fec_sis)))){
                    if((mes_se*1)>0){ $('#btn_guardar').attr('disabled',false);}
                    $('#list').jqGrid('clearGridData',true).trigger('reloadGrid');
                
                    for(var i=0; i<activos_dep.length; i++){
                        var dias_anio=0,fin_dep=0;
                        (activos_dep[i]['Cfg_Ddp']==='DT')?dias_anio=360:dias_anio=365;
                        var costo=activos_dep[i]['Act_Val']-activos_dep[i]['Act_Res'];
                        var dep_anu=costo/activos_dep[i]['Act_Ann'];
                        var dep_diaria=dep_anu/dias_anio;

                        //Secci�n para descomponer la fecha de la �ltima depreciaci�n registrada en la tabla activo_deprecia
                        var descomponer=activos_dep[i]['Acd_Fpd'].split('-');
                        var anio=descomponer[0];
                        var mes=descomponer[1];

                        //Secci�n para descomponer la fecha de fin de depreciaci�n registrada en la tabla activo
                        var fin_depreciacion=activos_dep[i]['Act_Ffd'].split('-');
                        var anio_fd=fin_depreciacion[0];
                        var mes_fd=fin_depreciacion[1];
                        var dia_fd=fin_depreciacion[2];

                        //Se llena el jqGrid con los datos �nicamente del mes que se encuentre seleccionado
                        if((mes===mes_se)&&(anio===anio_se)){
                            var estado='Proceso Depreciaci&oacute;n';
                            if((anio===anio_fd)&&(mes===mes_fd)){fin_dep=dia_fd;estado='&Uacute;ltima Depreciaci&oacute;n';}
                            var dep_men=dep_mensual(activos_dep[i]['Acd_Fpd'],dep_diaria,activos_dep[i]['Cfg_Ddp'],fin_dep);
                            var meses=activos_dep[i]['meses'];
                            var dep_acu=dep_acumulada(meses,dep_diaria,activos_dep[i]['Cfg_Ddp']);
                            dep_acu=parseFloat(dep_acu)+parseFloat(dep_men[0]);
                            depreciacion.push({Act_Cod:activos_dep[i]['Act_Cod'],Fec_Fin:dep_men[1],Act_Dep:dep_men[0],Acd_Fpd:activos_dep[i]['Acd_Fpd']});
                            $("#list").jqGrid('addRowData',activos_dep[i]['Act_Cod'],{"Act_Cod":activos_dep[i]['Act_Cod'],"Act_Val":activos_dep[i]['Act_Val'],"Act_Des":activos_dep[i]['Act_Des'],"Act_Fec":activos_dep[i]['Act_Fec'],"Fec_Dsd":activos_dep[i]['Acd_Fpd'],"Fec_Hst":dep_men[1],"Act_Dep":dep_men[0].toFixed(2),"Dep_Acu":dep_acu.toFixed(2),"Est_Dep":estado});
                        }
                    }
                }else{$.alert('El mes seleccionado NO est� disponible en el periodo elegido.');$('#list').jqGrid('clearGridData',true).trigger('reload');}
            });
            //Se declara el jqgrid para presentar el proceso de depreciaci�n
            $("#list").jqGrid({
                mtype: "GET",datatype: "local",regional : 'es',autowidth:true,shrinkToFit: true,hidegrid:false,
                responsive:true,height:230,cmTemplate:{sortable:false},
                caption:'DEPRECIACI&Oacute;N MENSUAL',
                colModel:[
                    {label:'Datos Activo',align:'center',name:'tit',hidden:true,editable:true,editrules:{edithidden:true}},
                    {label:'C&oacute;d. Int.',align:'center',name:'Act_Cod',width:40,key:true},
                    {label:'Nombre',align:'center',name:'Act_Des',width:130},
                    {label:'Costo',align:'center',name:'Act_Val',hidden:true,editable:true,editrules:{edithidden:true}},
                    {label:'Fecha de Compra',align:'center',name:'Act_Fec',hidden:true,editable:true,editrules:{edithidden:true}},
                    {label:'Datos Depreciaci&oacute;n',align:'center',name:'tit',hidden:true,editable:true,editrules:{edithidden:true}},
                    {label:'Desde',align:'center',name:'Fec_Dsd',width:60},
                    {label:'Hasta',align:'center',name:'Fec_Hst',width:60},
                    {label:'Valor Depreciado',align:'center',name:'Act_Dep',width:80},
                    {label:'Depreciaci&oacute;n Acumulada',align:'center',name:'Dep_Acu',width:100},
                    {label:'Estado',align:'center',name:'Est_Dep',width:90},
                    {label:'Informaci&oacute;n', name: 'act1', width: 50, align: 'center',viewable:false,
                        formatter:function (cellvalue, options, rowObject) { 
                            return  '<span class="btn btn-info btn-xs" title="Ver" type="button" onclick="$(\'#list\').viewGridRow(\''+rowObject.Act_Cod+'\');"><i class="glyphicon glyphicon-info-sign"></i></span>';
                        }
                    }
                ],
                rowNum:10000,pager:"listPager",gridview:true,rownumbers:true,viewrecords:true,pgbuttons: false,view:true,pgtext: null   
            });	
        });
        //Secci�n para cargar los meses
        function cargar_meses(){
            $.post("<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING); ?>",{allActivos:true},function(response){
                if(response['activos'].length>0){
                    activos_dep=response['activos'];
                    activos_dep.sort(function (a, b) {
                        var dateA=new Date(a.Acd_Fpd), dateB=new Date(b.Acd_Fpd);
                        return dateA-dateB;
                    });
                    var meses=['ninguno','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
                    var options="",anio=0,aux_anio=0,mes=0,aux_mes=0;
                    options+="<option>Seleccione</option>";
                    for(var i=0,z=activos_dep.length; i<z; i++){
                        var fec_sis=activos_dep[i]['Fec_Sis'];
                        var descomponer=activos_dep[i]['Acd_Fpd'].split('-');
                        anio=descomponer[0];mes=descomponer[1];
                        if((mes!==aux_mes)||(anio!==aux_anio)){
                        options+="<option value='"+mes+'*'+anio+'*'+fec_sis+"'>"+meses[parseFloat(mes)]+' - '+anio+"</option>";
                        aux_anio=anio;aux_mes=mes;
                        }
                    }
                    $('#mes').html(options);
                }else{$('#mes').html('<option>Seleccione</option>');$('#list').jqGrid('clearGridData',true).trigger('reloadGrid');}
            },'json').fail(function(){$.alert();});
        }
        //Funci�n para realizar el guardado de la depreciaci�n
        function saveForm(){
            var data=$('#formDepreciacion').serializeObject();
            data['depreciacion']=depreciacion;
            data['saveDepreciacion']=true;
            $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( response ) {
                if(response['success']===true){
                    $.alert("Transaccion Realizada con &Eacute;xito!");
                    asiento(response['Com_Cod'],response['Tia_Cod'],response['Pec_Cod']);
                    $('#formDepreciacion').trigger('reset');
                    $('#Tia_Cod').val(4);
                }else{$.alert(response['message']);}
            },'json').fail(function(){$.alert();}); 
        }
        //Funci�n para presentar el asiento contable
        function asiento(Com_Cod,Tia_Cod,Pec_Cod){
            $('#ver').show();
            $('#btn_guardar').attr('disabled',true);
            $('#ver_asiento').attr('href','../../contabilidad/FRONT/con_pri_compr_1.1.php?codigo='+Com_Cod+'&tabla=proveedore&campo=Prv_Cod&tipo='+Tia_Cod+'&Pec_Cod='+Pec_Cod);
        }
    </script>
</BODY>
</HTML>

