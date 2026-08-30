<?php
/**
 * @abstract Permite realizar el registro de un proceso de facturaciï¿½n de viajes
 * @author Erick Cordova
 * @version 2.0
 * Fecha de creación  2017-07-25
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fic_log_paciente.php');
require_once('../../tesoreria/LOGICA/tes_log_cccc_lotes_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');



/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Inventario($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Logica_Inventario;
//borrar debug completo
//$obBD_con1->echoLog($obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion));
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

//conexion para anticipos de clientes!
$obBD_conexion_get = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
$obBD_con_get =  new Class_Log_Datos_Cccc;

if(isset($getDateServ)){
    $resp['hoy']=date("Y-m-d");
    $obBD_con1->echoJson($resp);
}

//Sección para listar los pacientes registrados en la empresa
if(isset($clieAjax)){
    $response=$obBD_con1->getPageGrid(1, $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);
    $obBD_con1->echoJson($response);
}

/* ver si exite un cliente */
if(isset($searchCliente)){
    $responce = $obBD_con1->getRowConsulta(17, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con1->getRowConsulta(18, $responce['Prs_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Pac_Cod']))?$responce['existe']=true:$responce['existe']=false;
    $obBD_con1->echoJson($responce);
}

/* guarda un nuevo cliente */
if(isset($guardaClieAjax)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $data['Pac_Cor']=$data['Prs_Cor'];
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        if(empty($Prs_Cod)){
            $obBD_con1->operacionobBD(3,$data,$obBD_conexion);
            $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
        }else{
            $pers=$obBD_con1->getRowConsulta('persona.selectWhere',array('clean'=>true, 'Prs_Cod'=>$Prs_Cod),$obBD_conexion);
            if(empty($Prs_Cor)&&!empty($pers['Prs_Cor'])) $data['Pac_Cor']=$pers['Prs_Cor'];
            $up=array();
            if(!empty($Prs_Cor)&&empty($pers['Prs_Cor'])) $up['Prs_Cor']=$Prs_Cor;
            if(!empty($Prs_Dir)&&empty($pers['Prs_Dir'])) $up['Prs_Dir']=$Prs_Dir;
            if(!empty($Prs_Tel)&&empty($pers['Prs_Tel'])) $up['Prs_Tel']=$Prs_Tel;

            if(!empty($up)) $obBD_con1->operacionobBD('persona.update',array_merge($up,array('where'=>array('Prs_Cod'=>$Prs_Cod))),$obBD_conexion);
        }
        $obBD_con1->operacionobBD(4,$data,$obBD_conexion);
        $data['Pac_Cod'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
        $data['paciente'] = trim($data['Prs_Ape'].' '.$data['Prs_Nom']);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true,'clie'=>$data);
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if(isset($saveMedAjax)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    //$data['Med_Nom']=$data['Prs_Cor'];
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(5,$data,$obBD_conexion);
        $data['Med_Cod'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true,'clie'=>$data);
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if(isset($proAjax)){
    $responce['rows'] = $obBD_con1->getArrayConsulta(13, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);
    exit();
}

if(isset($numSec)){
    $data=$_GET;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $maximo = $obBD_con1->getRowConsulta(14, $data, $obBD_conexion);
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true,'Fic_Num'=>$maximo['numero']);
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce); 
    echo json_encode($responce);
    exit();
}

if(isset($fichaAjax)){
    $data=$_GET;
    $data['Pac_Cod']=$Pac_Cod;
    if($obBD_con1->Error==0) {
        $responce=array('success'=>true);
        $responce['rows'] = $obBD_con1->getArrayConsulta(15, $data, $obBD_conexion);
        $responce['total']=10;
    } else {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($responce);
    exit();
}

if(isset($saveDocument)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod; 
    $data['Suc_Cod']=$Ses_Suc_Cod;

    $responce = $obBD_con1->saveFicha($data, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}

if(isset($cargarReportes)){
    $responce['success']=false;
    $table['{body}']='';
    //$table['{empresa}']=$Ses_Emp_Nom;
    $fecha=explode('-',$hoy);
    $table['{fecha}']=$fecha[2].' de '.mes($fecha[1],1).' de '.$fecha[0];

    //ENCABEZADO
    $responce['cabecera'] = $obBD_con1->getArrayConsulta(12, $_POST, $obBD_conexion_get);
    $cabecera = $responce['cabecera'];
    $table['{fichaNum}'] = $cabecera[0]['Fic_Num'];
    $table['{fecCon}'] = $cabecera[0]['Fic_Fec'];
    $table['{paciente}'] = $cabecera[0]['paciente'];
    $table['{cedula}'] = $cabecera[0]['Prs_Ced'];
    $table['{sexo}'] = $cabecera[0]['Prs_Sex'];
    $table['{direccion}'] = $cabecera[0]['Pac_Dir'];
    $table['{correo}'] = $cabecera[0]['Pac_Cor'];
    $table['{fecnac}'] = $cabecera[0]['Pac_Fna'];
   

    //MEDICAMENTOS DE LA FICHA
    $responce['medicamentos'] = $obBD_con1->getArrayConsulta(11, $_POST, $obBD_conexion_get);
    $medicamentos = $responce['medicamentos'];
    
    foreach ($medicamentos as $key) {
    $table['{body}'] = $table['{body}'] . '<tr>' 
            . '<td>' . $key['med_nom'] . '</td>'
            . '<td>' . $key['med_dos'] . '</td>'
            . '<td>' . $key['med_dur'] . '</td>'
            . '</tr>';
    }
    $table['observacion'] = $medicamentos[0]['fic_obs'];
    $table['motivo'] = $medicamentos[0]['fic_mot'];
    $table['historia'] = $medicamentos[0]['fic_hea'];
    $table['ruc'] = $medicamentos[0]['Emp_Ruc'];
    $table['tel'] = $medicamentos[0]['Suc_Te1'];
    $table['logo'] = $medicamentos[0]['Suc_Log'];
    $table['dir'] = $medicamentos[0]['Suc_Dir'];
    $table['rep'] = $medicamentos[0]['Emp_Rre'];
    $table['nom'] = $medicamentos[0]['Emp_Rep'];
    $table['empresa'] = $medicamentos[0]['Emp_Nom'];
    $table['src'] = $medicamentos[0]['Emp_Log'];

    $responce['html']=reporteHtml($table,'tes_alt_paciente.html');
    $responce['success']=true;
        
    utf8_encode_deep($responce);        
    echo json_encode($responce);
    exit();
}

if(isset($ajaxCertificado)){
    $responce['success']=false;
    $table['{body}']='';
    //$table['{empresa}']=$Ses_Emp_Nom;
    $fecha=explode('-',$hoy);
    $table['{fecha}']=$fecha[2].' de '.mes($fecha[1],1).' de '.$fecha[0];

    $responce['medicamentos'] = $obBD_con1->getArrayConsulta(16, $_POST, $obBD_conexion_get);
    $medicamentos = $responce['medicamentos'];

    $table['empresa'] = $medicamentos[0]['Emp_Nom'];
    $table['ruc'] = $medicamentos[0]['Emp_Ruc'];
    $table['src'] = $medicamentos[0]['Emp_Log'];
    $table['tel'] = $medicamentos[0]['Suc_Te1'];
    $table['dir'] = $medicamentos[0]['Suc_Dir'];
    $table['ciudad'] = $medicamentos[0]['Ciu_Des'];
    $table['provincia'] = $medicamentos[0]['Pro_Nom'];
    $table['pais'] = $medicamentos[0]['Pas_Nom'];

    $responce['html']=reporteHtml($table,'tes_alt_pac_certificado.html');
    $responce['success']=true;
        
    utf8_encode_deep($responce);        
    echo json_encode($responce);
    exit();
}

?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../VALIDACIONES/fic_val_paciente.js?x=0"></script>

        <script>
        $('.panel-main').hide();
        inicializarDocVenta();
        $('.panel-main').show();
        //setTimeout(function(){ $("#Pec_Cod").trigger('change'); }, 1000);
        var docs, items, pagos, data=[],vet_num_ant=0,tic_cod_ant=0, Vet_Index=1, Vet_Selected, index, Cof_Con='<?php echo $configs['Cof_Con']; ?>';
            <?php $array_documentos=$obBD_con1->getArrayConsulta(8,$rs_Punto['Pun_Cod'],$obBD_conexion);?>
        var array_documentos=<?php echo json_encode($array_documentos);?>, ivas_venta=<?php echo json_encode($ivas)?>;

        </script>
        <style>
            .ui-jqgrid td input, .ui-jqgrid td select, .ui-jqgrid td textarea {padding-top: 2px;}
            .footrow td[aria-describedby="documento_Vet_Imp"],.footrow td[aria-describedby="documento_Vet_Pru"]{padding: 0 !important;}
            .footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact input[type=text]{ padding: 0; }
            .footerFact textarea{text-align: left; height: 75px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}
            #jqGridButtonDiv{float:right; padding-right:10px; position:relative; top:-1px;}
            #Ret_Asu{ vertical-align: middle; margin-top: -2px; padding: 5px;  -ms-transform: scale(1.4); -moz-transform: scale(1.4); -webkit-transform: scale(1.4); -o-transform: scale(1.4); }
            #resultContent .resp{ font-weight: 700; font-size: 30px; color: #3f3fc1; padding: 0; margin: 0; overflow: hidden; text-overflow: ellipsis; height: 32px; }
            #resultContent .resp span:first-child{ color:darkgoldenrod;width: 100px;display: inline-block; margin-left: 42px; }
            .msg_fly { font-size: 12px !important; }
            .ret .input-group-btn button{padding: 1px 2px !important;}
            .ret{ padding: 0 !important;}
            .footrow td[aria-describedby="items_Vet_Pru"], .footrow td[aria-describedby="items_Vet_Imp"]{padding: 0 !important;}
        </style>
    </HEAD>
    <BODY>


        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  REGISTRAR FICHA M&Eacute;DICA</h3><p id="cabeceraPuntoImp" class="text-right col-xs-12  " style="margin-top:-15px;"></p></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="fichaMedica">
                    <div class="row">
                        <div class="col-xs-12" id="panelFicha" >
                            <div class="row">
                                <form id="fichaForm" name="fichaForm" class="form-horizontal normal" >
                                    <div class="col-md-12 col-xs-12">
                                        <fieldset class="exa-fieldset" id="clieFormTemp">
                                            <legend class="Titulos2">Datos del paciente</legend>
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs required">C&eacute;dula/RUC:</label>
                                                <div class="col-xs-3" >
                                                  <input name="Prs_Cod" type="text" style="display:none;" />
                                                  <input name="Prs_Cor" type="text" style="display:none;" />
                                                  <input id="Pac_Cod" name="Pac_Cod" type="text" style="display:none;" />
                                                  <input name="op_opciones" type="text" value="c" style="display: none;">
                                                  <div class="input-group input-group-xs">
                                                      <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#clieDialog',selectCliente);" type="text" placeholder="Ingrese paciente..."  class="form-control input-xs datatrigger clearable dialogSearch" tabindex="1" required="true" />
                                                    <span class="input-group-btn">
                                                        <button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar paciente"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                        <button id="Rgt_Btn" type="button" onclick="$('#clieCreateForm').setData({}).find('.validate').find('i').removeAttr('class'); $('#clieCreateDialog').dialog('open');" class="btn btn-primary" title="Registrar nuevo paciente"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>
                                                        <button id="Rgt_Btn" type="button" onclick="certificado();" class="btn btn-info" title="Generar certificado"  tabindex="2"><span class="glyphicon glyphicon-file"></span></button>
                                                    </span>
                                                  </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs">Paciente:</label>
                                                <div class="col-xs-4" ><span id="Paciente" name="paciente" class="form-control input-xs databind datatitle"></span></div>
                                                <label class="col-xs-2 control-label label-xs">Ciudad:</label>
                                                <div class="col-xs-2" ><span id="Ciu_Des" name="Ciu_Des" class="form-control input-xs databind datatitle"></span></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs">Direcci&oacute;n:</label>
                                                <div class="col-xs-4" ><span name="Prs_Dir" type="text" class="form-control input-xs databind datatitle"></span></div>
                                                <label class="col-xs-2 control-label label-xs">Correo:</label>
                                                <div class="col-xs-2" ><span name="Prs_Cor" type="text" class="form-control input-xs databind datatitle"></span></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs">Empresa:</label>
                                                <div class="col-xs-3" ><span name="Pac_Emp" type="text" class="form-control input-xs databind datatitle"></span></div>
                                                <label class="col-xs-3 control-label label-xs">Fecha de nacimiento:</label>
                                                <div class="col-xs-2 "><span name="Pac_Fna" type="text" class="form-control input-xs databind datatitle"></span></div>
                                                
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs">Sexo:</label>
                                                <div class="col-xs-1" ><span name="Prs_Sex" type="text" class="form-control input-xs databind datatitle"></span></div>
                                                <label class="col-xs-1 control-label label-xs">Tel&eacute;fono:</label>
                                                <div class="col-xs-2" ><span name="Prs_Tel" type="text" class="form-control input-xs databind datatitle"></span></div>
                                                <label class="col-xs-2 control-label label-xs">Edad:</label>
                                                <div class="col-xs-1" ><span id="edad" name="edad" type="text" class="form-control input-xs databind datatitle"></span></div>
                                            </div>
                         
                                        </fieldset>
                                    </div>
                                </form>
                            </div>
                            <div class="row">
                                <form class="form-horizontal normal">
                                    <div class="col-md-12 col-xs-12">
                                        <fieldset class="exa-fieldset" id="fichaPaciente"
>                                            <legend class="Titulos2">Datos de la consulta</legend>
                                                <div class="form-group">
                                                    <label class="col-xs-2 control-label label-xs">Num. ficha:</label>
                                                    <div class="col-md-2">
                                                    <span id="Fic_Num" name="Fic_Num" type="text" placeholder="" class="form-control input-xs databind datatitle" ></span></div>
                                                    <label class="col-xs-2 control-label label-xs required">Fecha de consulta:</label>
                                                    <div class="col-md-2">
                                                    <input id="Fic_Fec" name="Fic_Fec" type="text" placeholder="" class="form-control input-xs" required>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-xs-2 control-label label-xs required">Motivo de consulta:</label>
                                                    <div class="col-md-8">
                                                    <textarea id="Fic_Mot" name="Fic_Mot" class="form-control" rows="1" placeholder="Indicar el motivo de la consulta..." required=""></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-xs-2 control-label label-xs required">HEA:</label>
                                                    <div class="col-md-8">
                                                    <textarea id="Fic_Hea" name="Fic_Hea" rows="7" class="form-control" rows="2" placeholder="Indicar el tratamiento..." required=""></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-xs-1 control-label label-xs">Tratamiento:</label>
                                                </div>
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <div class="col-sm-12 center">
                                                            <table id="items"></table>
                                                            <div id="itemsPager"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-xs-2 control-label label-xs">Observaciones:</label>
                                                    <div class="col-md-8">
                                                    <textarea id="Fic_Obs" name="Fic_Obs" class="form-control" rows="2" placeholder="Escribir una observaci&oacute;n...">N/A</textarea>
                                                    </div>
                                                    <div class="col-md-1">
                                                        <button id="Btn_His" type="button" onclick="$('#fichaDialog').dialog('open');" name="Btn_His"class="btn btn-info"><i class="glyphicon glyphicon-folder-open"></i>    Historial m&eacute;dico</button>
                                                    </div> 
                                                </div>
                                                <div class="form-group">
                                                    <div class="col-xs-12 text-center">
                                                        <button id="btnSave" name="btnSave" type="button" onclick="guardar();" class="btn btn-primary "><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                                    </div> 
                                                </div>
                                        </fieldset>
                                </form>
                            </div>
                            <div class="col-sm-12 Titulos2" style="text-align: center;"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                        </div>
                    </div>
                </div>
            </div>
        
        <!-- Inicio del diálogo para buscar pacientes -->
        <div id="clieDialog" title="B&uacute;squeda de pacientes"><form class="form-horizontal normal"> </form></div>
        <!-- Inicio del diálogo para buscar fichas medicas -->
        <div id="fichaDialog" title="Historial de consultas">
            <form class="form-horizontal normal">
                <div class="form-group center">
                    <button class="btn btn-primary" onclick="searchFichas();"><i class="glyphicon glyphicon-search"></i>Buscar</button>
                </div>
            </form>
        </div>
         <!-- Inicio del diálogo para buscar mediccamentos -->
        <div id="proDialog" title="Historial de consultas"><form class="form-horizontal normal"></form></div>
        <script>
            //Dialog buscar clientes
            
            $.createSearchDialog('fichaDialog',[
                { label: 'C&oacute;d.Int.', name: 'Fic_Cod', key: true, width: 15,align:"center",hidden:true },
                { label: 'N. Fic', name: 'Fic_Num', width: 25 },
                { label: 'C&eacute;dula/RUC', name: 'Prs_Ced',  hidden:true, width: 50 },
                { label: 'Cod.Int', name: 'Pac_Cod',  hidden:true, width: 50 },
                { label: 'Paciente', name: 'paciente', hidden:false, width: 100},
                { label: 'Direcc.', name: 'Pac_Dir', hidden:true, width: 60 },
                { label: 'Empresa', name: 'Pac_Emp', hidden:true, width: 50 },
                { label: 'Fecha consulta', name: 'Fic_Fec', align:'center', hidden:false, width: 30 },
                { label: 'Motivo', name: 'Fic_Mot', align:'center', hidden:false, width: 60 },
                { label: 'HEA', name: 'Fic_Hea', width: 60 },
                { label: 'Observaciones', name: 'Fic_Obs', width: 50 },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatter:'gridButton', formatoptions:{action:reportFicha, icon:'print', type:'info',title:'Imprimir ficha'}}
            ],null,null,{headertitles:true},{ title:'Ficha', text:'Prs_Ced' });


            $.createSearchDialog('clieDialog',[
                { label: 'C&oacute;d.Int.', name: 'Pac_Cod', key: true, width: 15,align:"center",hidden:true },
                { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Paciente', name: 'paciente', width: 100},
                { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
                { label: 'Empresa', name: 'Pac_Emp', hidden:false, width: 50 },
                { label: 'Nacimiento', name: 'Pac_Fna', hidden:false, width: 50 },
                { label: 'Ciudad', name: 'Ciu_Des', hidden:false, width: 50 },
                { label: 'Sexo', name: 'Prs_Sex', hidden:true, width: 50 },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectCliente} }
            ],null,null,null,{headertitles:true},{ title:'Cliente', text:'Prs_Ced' });

             // Dialog para buscar medicamentos
           $.createSearchDialog('proDialog',[
            { label: 'C&oacute;d.Int.', name: 'Med_Cod', key: true, width: 20,align:"center"},
            { label: 'Descripción', name: 'Med_Nom', width: 180, classes:'highlightSearch' },
            { label: 'Cantidad', name: 'Med_Can', width: 40, align:"center"},
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectItem} }
        ],null,900,null,null,{ title:'Medicamento' });
            
            function reportFicha(row){
                console.log(row['Pac_Cod']);
                $.post( "",{cargarReportes:true, Fic_Cod:row['Fic_Cod']}, function( response ) {
                    if(response['success']===true){
                        $(response['html']).printElement({pageTitle:'<?Php echo $Ses_Sys_Nom; ?>'});
                    }else{$.alert(response['message']);}                                   
                 },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });
            }

            function certificado(row){
                //console.log(row['Pac_Cod']);
                $.post( "",{ajaxCertificado:true}, function( response ) {
                    if(response['success']===true){
                        $(response['html']).printElement({pageTitle:'<?Php echo $Ses_Sys_Nom; ?>'});
                    }else{$.alert(response['message']);}                                   
                 },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });
            }

            function selectCliente(paciente){
                $('#clieFormTemp').setData($.extend(paciente,{op_opciones:'c'}));
                $('#clieDialog').dialog('close');
                $('#fichaDialog').dialog('close');

                $.post("", { enableDisableCampos: true, Pac_Cod: paciente['Pac_Cod'] }, function(responce) {
                    if (responce['success'] === true) {

                        if(responce['data_ant'] === 'none' || !(responce['data_ant'] > 0)){
                            $("#anticipo").css("display", "none");
                        }
                        else{
                            $("#anticipo").css("display", "block");
                        }

                        $("#ant_msg").html(responce['data_ant'] === 'none' || !(responce['data_ant'] > 0) ? "$ 0.00" : $.numFormat(responce['data_ant']));
                        $("#ant_msg")[responce['data_ant'] === 'none' || !(responce['data_ant'] > 0) ? 'removeClass' : 'addClass']('alert alert-danger bold');
                    } else {
                        $("#anticipo").css("display", "none");
                        $("#ant_msg").html("$ 0.00");
                        $("#ant_msg").removeClass('alert alert-danger bold');
                        $.alert(responce['message']);
                    }
                }, 'json');
                edad();
            }

        </script>

        <!-- Inicio del diálogo para registrar clientes -->
        <div id="clieCreateDialog" title="Registrar paciente" style="display:none;">
            <form class="form-horizontal normal" id="clieCreateForm" action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaCliente(); }else{ $('#Prs_Ced').flyout('show').focus() }">
                <input name="Prs_Cod" type="text" class="hidden" />
                <fieldset class="exa-fieldset" >
                    <legend class="Titulos2">Datos del paciente</legend>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">C&eacute;dula/RUC:</label>
                        <div class="col-xs-5" >
                            <div class="input-group input-group-xs">
                                <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="if(validaNoIdentif(this.value)['success']){ $('#Ide_Cod').val(this.value.length===10?2:1); $('#Cli_Tic').val(validaNoIdentif(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchCliente(this.value); }else{ $('#Ide_Cod').val(''); $('#Cli_Tic').val(''); $(this).fieldValid(false,validaNoIdentif(this.value)['message']); };" required="" />
                                <span class="input-group-addon validate" ><i></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Apellidos:</label>
                        <div class="col-xs-7" ><input name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Nombres:</label>
                        <div class="col-xs-7" ><input name="Prs_Nom" type="text" class="form-control input-xs" required=""/></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required"><span class='natural'>Empresa:</span></label>
                        <div class="col-xs-6" ><input name="Pac_Emp" type="text" class="form-control input-xs" required="" /></div>
                        </div>
                    <div class="form-group natural">
                        <label class="col-xs-4 control-label label-xs required">G&eacute;nero:</label>
                        <div class="col-xs-4" >
                            <select name="Prs_Sex" class="form-control input-xs">
                                <option value = "M" >MASCULINO</option>
                                <option value = "F" >FEMENINO</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required"><span class='natural'>Fec. Nacimiento:</span></label>
                        <div class="col-md-3">
                            <input id="Pac_Fna" name="Pac_Fna" type="text" placeholder="" class="form-control input-xs" required>
                        </div>
                    </div>
                </fieldset>
                <fieldset class="exa-fieldset" >
                    <legend class="Titulos2">Datos de Ubicaci&oacute;n</legend>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Ciudad:</label>
                        <div class="col-xs-4" >
                            <?php $rs_ciudad = $obBD_con1->getArrayConsulta(81, '', $obBD_conexion); ?>
                            <select name="Ciu_Cod" class="form-control input-xs" required="" >
                                <option value=""></option>
                                <?php  foreach($rs_ciudad as $row){ echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>"; } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Direcci&oacute;n:</label>
                        <div class="col-xs-7" ><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Tel&eacute;fono:</label>
                        <div class="col-xs-4" ><input name="Prs_Tel" type="text" class="form-control input-xs" required="" pattern="\d*" /></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Mail:</label>
                        <div class="col-xs-7" ><input name="Prs_Cor" type="mail" class="form-control input-xs" required="" /></div>
                    </div>
                </fieldset>
                <div class="center">
                    <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                </div>
                <div class="Titulos2" style="text-align: center;"><hr><b>NOTA:</b> Los campos marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
            </form>
        </div>

        <!-- Formulario para registrar nuevo medicamento-->
        <div id="medCreateDialog" title="Registrar medicamento" style="display:none;">
            <form class="form-horizontal normal" id="medCreateForm" action="javascript:guardaMed();">
                <input name="Med_Cod" type="text" class="hidden" />
                <fieldset class="exa-fieldset" >
                    <legend class="Titulos2">Datos del medicamento</legend>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Nombre comercial:</label>
                        <div class="col-xs-7" ><input name="Med_Nom" type="text" class="form-control input-xs" required="" /></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Cantidad:</label>
                        <div class="col-xs-7" ><input name="Med_Can" type="text" class="form-control input-xs" required=""/></div>
                    </div>
                </fieldset>
                <div class="center">
                    <button  type="submit" class="btn btn-sm btn-success "><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                </div>
                <div class="Titulos2" style="text-align: center;"><hr><b>NOTA:</b> Los campos marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
            </form>
        </div>
        
        <!-- DIALOG crear nuevo cliente -->
        <script>
            $('#clieCreateDialog').createDialog({icon:'plus', width:500, height:430});
            $('#For_Cod').val(1).trigger('change');
        </script>

        <!--// DIALOG crear nuevo medicamento-->
        <script>
            $('#medCreateDialog').createDialog({icon:'plus', width:500, height:200});
            //$('#For_Cod').val(1).trigger('change');
        </script>
        
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <script>
    $('#Pec_Cod').trigger('change');
    $.clearValidate();</script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <script type="text/javascript">
            var err=0;
            function validar(op){
                var cedula=$('#Prs_Ced').val();
                switch(op){
                    case 1:
                        if(validaNoIdentif(cedula)['success']){  err=0; $('#Ide_Cod').val(cedula.length===10?2:1); $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ec'); }else{ err=1; $('#Ide_Cod').val(''); $('#Prs_Ced').fieldValid(false,validaNoIdentif(cedula)['message']); }
                        break;
                    case 2:
                        if(cedula.length===13 && validaNoIdentif(cedula)['success']){ err=0; $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ec');}else{ err=1; $('#Ide_Cod').val(1); $('#Prs_Ced').fieldValid(false,validaNoIdentif(cedula)['message']); }
                        break;
                    case 3:
                        err=0;
                        $('#Prs_Ced').fieldValid(true); searchCliente(cedula,'ex');
                        break;
                }
            }

            function habilitar(op,val){
                var lon_ced=$('#Prs_Ced').val().length; $('#Prs_Ced').fieldValid('');
                if(op==='ec'){
                    $('#Ide_Cod').find('option').show();
                    $('#Ide_Cod').attr('disabled',true);
                    $('#Ide_Cod').val(lon_ced===10?2:1);
                }else{
                    $('#Ide_Cod').find('option').hide().end().find('option[data-tipo="Ex"]').show();
                    $('#Ide_Cod').val(val);
                    $('#Ide_Cod').attr('disabled',false);
                }
            }

            function searchCliente(ced,tipo){
                (tipo==='ec')?ced=ced.substring(0,10):ced;
                $.post("",{searchCliente:true,Prs_Ced:ced}, function( response ) {
                    if(response['existe']===true){
                        $.alert('El paciente '+ced+' ya se encuentra registrado..!!');
                        clear();
                    }else{
                        $('#Ciu_Cod').val(response['Ciu_Cod']).trigger('chosen:updated');
                        $.extend(response,{Prs_Ced:$('#Prs_Ced').val(),Ide_Cod:$('#Ide_Cod').val()});
                        $('#clieCreateDialog').setData(response,false);
                    }
                },'json').fail(function (){$.alert();});
            }

            function searchFichas(){
                var cod= $('#Pac_Cod').val();
                $.post("",{fichaAjax:true, Pac_Cod:cod}, function( response ) {
                    //console.log(response);
                    $('#fichaDialog').setData(response,false);
                },'json').fail(function (){
                    $.alert();
                });
            }

            function clear(){
                $('#clieCreateDialog').setData({Cli_Tic:'N',Prs_Ciu:'Ec',Prs_Sex:'M'});
                $('#Prs_Ced').val('').focus();
                $('.juridico').hide();$('.natural').show();
            }

            function guardarCliente(){
                if(err===1){$.alert('Debe ingresar un n&uacute;mero de identificaci&oacute;n v&aacute;lido'); return false;}
                $.saveDataJson("",$('#clieCreateDialog').getData('guardarCliente'), function( resp ){ $("#radioec").trigger('change'); clear(); });
            }
            
            items=$("#items");
            if($('#items').length===1){
                
                items.createGrid({
            data:[],
            rowNum: 10000000, height: 'auto', footerrow:true, headertitles:true, selectGridRows:false,
            colModel:[
                {name:'index', label:'Index', width:20, sorttype:'int',align:'center',key:true,hidden:true},
                {name:'Med_Cod',label:'C&oacute;d.Int.', width:20, sorttype:'int',align:'center',hidden:true},
                {name:'Med_Nom',label:'Descripci&oacute;n', width:150},
                {name:'Med_Can',label:'Cantidad', width:50, align:'center' },
                {name:'Med_Dos',label:'Dosis', width:70, edittype:'text', editable:true},
                {name:'Med_Dur',label:'Duraci&oacute;n', width:70, edittype:'text', editable:true},
                {name:'act1', label: '&nbsp;',width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:deleteItem, icon:'remove', type:'danger', data: function(o) { return o.index; }, attr: { 'tabindex': '-1' },title:'Eliminar &iacute;tem'} }
            ]
        },true,'itemsPager',{view:false}).gridButtonsAdd([
            {caption:'Agregar',buttonicon:'plus', onClickButton: function(){if(!available()){ $.alert('No hay espacio para mas items en este documento!');return;}index=0;$('#proDialog').dialog('open');$.Search('pro');}},
            {caption:'Remover todo',buttonicon:'remove', onClickButton: function(){items.clearGrid(); }},
            {caption:'Nuevo',buttonicon:'plus', onClickButton: function(){if(!available())index=0;$('#medCreateDialog').dialog('open');$.Search('pro');}},
        ]);
        items.getFootRow(true);

        function numFicha(){
            $.post("",{numSec:true}, function( response ) {
                var num = parseInt(response['Fic_Num']) + 1;
                $('#Fic_Num').text('0000'+num);
            },'json').fail(function (){
                $.alert();
            });
        }

        function edad(){
            var nac = $("span[name=Pac_Fna]").text();
            dob = new Date(nac);
            var today  = new Date();
            var age = Math.floor((today-dob) / (365.25 * 24 * 60 * 60 * 1000));
            $('#edad').text(age);
        }

        function guardar(){

            var cod = $('#Pac_Cod').val();
            var fec = $('#Fic_Fec').val();
            var num = $('#Fic_Num').text();
            var mot = $('#Fic_Mot').val();
            var hea = $('#Fic_Hea').val();
            var obs = $('#Fic_Obs').val();

            var filas = $("#items").jqGrid('getRowData');
            console.log(filas.length);
            var ids = $("#items").jqGrid('getDataIDs');
            for(var i=0;i<filas.length;i++){
                filas[i]['Med_Dos'] = $("#" + ids[i] + "_Med_Dos").val();
                filas[i]['Med_Dur'] = $("#" + ids[i] + "_Med_Dur").val();
            }

            if(mot!='' && hea!='' && filas.length>1){
                $.post( "",{saveDocument:true, Pac_Cod:cod, Fic_Fec:fec, Fic_Num:num, Fic_Mot:mot, Fic_Hea:hea, Fic_Obs:obs, Medicamento:filas}, function(response) 
                {
                    const respuesta = JSON.parse(response);
                    $("#items").clear;
                    $.alert(respuesta.message);
                    limpiar();  
                    $("#items").jqGrid("clearGridData");
                    if(respuesta.error){
                        console.log(respuesta.error);
                    }
                }).fail(function(error) { $.alert("El servidor ha fallado en responder!"); console.log(error); });
            }else{
                $.alert('Ingrese todos los campos');
            }
        }

        function limpiar(){
            //location.reload();
            $("input[name=Prs_Ced]").val('');
            $("span[name=paciente]").text('');
            $("span[name=Prs_Dir]").text('');
            $("span[name=Pac_Emp]").text('');
            $("span[name=Prs_Sex]").text('');
            $("span[name=Prs_Tel]").text('');
            $("span[name=Ciu_Des]").text('');
            $("span[name=Prs_Cor]").text('');
            $("span[name=Pac_Fna]").text('');
            $("span[name=edad]").text('');
            $("span[name=Fic_Num]").text('');
            $("textarea[name=Fic_Mot]").val('');
            $("textarea[name=Fic_Hea]").val('');
            $("textarea[name=Fic_Obs]").val('N/A');
            numFicha();
        }

        }
        </script>
    </BODY>
</HTML>