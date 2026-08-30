<?php	
/**
* @abstract Permite realizar movimientos de inventario
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_roles.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Rol;

$hoy = date("Y-m-d");
$mes = date("m");

$obBD_con1->echoLog($obBD_con1->reportesExa("/con_alt_compr_?.?.php", $Ses_Emp_Cod, $obBD_conexion));
/*
require_once('../LOGICA/fac_log_electronica.php');
$obBD_elect =  new Class_Log_Datos_Retencion_Elect();
$mail = $obBD_elect->sendMailDoc($Ret_Cod,$Prs_Cor,NULL,$obBD_conexion);
var_dump($mail);
exit();*/
/*$ver=json_decode('{"operator":"+","operand1":{"operator":"+","operand1":{"operator":"+","operand1":{"operator":"+","operand1":{"operator":"+","operand1":{"operator":"+","operand1":{"type":"item","value":"0.0000","text":"{sueldo_dias}","variable":"sueldo_dias"},"operand2":{"type":"item","value":"0.0000","text":"{deci_terc}","variable":"deci_terc"}},"operand2":{"type":"item","value":"0.0000","text":"{deci_cuar}","variable":"deci_cuar"}},"operand2":{"type":"item","value":"0.0000","text":"{fond_reser}","variable":"fond_reser"}},"operand2":{"type":"item","value":"0.0000","text":"{hora_extra_rol_p}","variable":"hora_extra_rol_p"}},"operand2":{"type":"item","value":"0.0000","text":"{bonificaci}","variable":"bonificaci"}},"operand2":{"type":"item","value":"0.0000","text":"{alimenta}","variable":"alimenta"}}',true);
var_dump($ver);
//exit();
$obBD_con1->saveFormula(109,null,$ver,array(),null,$obBD_conexion);
exit();*/
//echo json_encode($obBD_con1->getFormula(32,null,null,$obBD_conexion));
//exit();
if(isset($loadBasesDatos)){
    // Obtener bases de datos de la tabla data de exa_master
    require_once('../../administrador/LOGICA/logica.php');
    $obBD_conexion_master = new Class_Log_Conexion_Adm();
    $obBD_master = new Class_Log_Datos_Adm();
    
    // Consultar bases de datos únicas y activas
    $sql = "SELECT DISTINCT Dat_Dis, UPPER(Dat_Dis) as Emp_Nom 
            FROM data 
            WHERE Dat_Est='A' 
            ORDER BY Dat_Dis";
    $bases_datos = $obBD_master->getArrayConsultaSql($sql, $obBD_conexion_master);
    
    echo json_encode(array('success'=>true, 'bases'=>$bases_datos));
    exit();
}

if(isset($loadPlantillasByDB)){
    // Cargar plantillas de una base de datos específica
    if(empty($Dat_Dis)){ 
        echo json_encode(array('success'=>false,'message'=>'No se ha seleccionado una base de datos!')); 
        exit(); 
    }
    
    // Obtener plantillas de la base de datos seleccionada
    require_once('../../administrador/LOGICA/logica.php');
    $obBD_conexion_master = new Class_Log_Conexion_Adm();
    $obBD_master = new Class_Log_Datos_Adm();
    
    // Consultar plantillas en esta base de datos con sus empresas correspondientes
    $sql = "SELECT DISTINCT MS.*, E.Emp_Nom, MS.Emp_Cod AS Emp_Cod_Master
            FROM `" . addslashes($Dat_Dis) . "`.map_system MS 
            INNER JOIN `" . addslashes($Dat_Dis) . "`.campo_rol CR ON MS.Map_Cod = CR.Map_Cod
            INNER JOIN `" . addslashes($Dat_Dis) . "`.empresas E ON MS.Emp_Cod = E.Emp_Cod
            WHERE MS.Map_Est='A' AND E.Emp_Est='A'
            ORDER BY E.Emp_Nom, MS.Map_Des";
    $plantillas = $obBD_master->getArrayConsultaSql($sql, $obBD_conexion_master);
    
    echo json_encode(array('success'=>true, 'plantillas'=>$plantillas));
    exit();
}

if(isset($loadPlantilla)){
    // Cargar una plantilla existente para poder copiarla
    if(empty($Map_Cod)){ 
        echo json_encode(array('success'=>false,'message'=>'No se ha seleccionado una plantilla!')); 
        exit(); 
    }

    // Determinar la base de datos de origen
    $obBD_conexion_origen = (isset($Dat_Dis) && !empty($Dat_Dis)) ? new Class_Log_Conexion_Rol($Dat_Dis) : $obBD_conexion;
    
    // Obtener datos de la plantilla
    $map_rol = $obBD_con1->getRowConsulta(8, array('Map_Cod'=>$Map_Cod), $obBD_conexion_origen);
    if(empty($map_rol)){ 
        echo json_encode(array('success'=>false,'message'=>'Plantilla no encontrada!')); 
        exit(); 
    }
    
    // Obtener todos los campos de la plantilla
    $fields = $obBD_con1->getArrayConsulta(7, array('Map_Cod'=>$Map_Cod), $obBD_conexion_origen);
    
    // Cargar fórmulas de los campos calculados
    $campos = array();
    $index = 0;
    foreach($fields as $f) {
        // Calcular Cam_Grp basado en Cam_Tip (igual que en JavaScript)
        $cam_grp = 0;
        if($f['Cam_Tip'] == 'I') $cam_grp = 1;
        elseif($f['Cam_Tip'] == 'E') $cam_grp = 2;
        elseif($f['Cam_Tip'] == 'P') $cam_grp = 3;
        elseif($f['Cam_Tip'] == 'T') $cam_grp = 4;
        
        $campo = array(
            'Index' => $index++,
            'Cam_Grp' => $cam_grp,
            'Cam_Tip' => $f['Cam_Tip'],
            'Cam_Des' => $f['Cam_Des'],
            'Cam_Dec' => $f['Cam_Dec'],
            'Cam_Var' => $f['Cam_Var'],
            'Cam_Ord' => $f['Cam_Ord'],
            'Cam_Vis' => $f['Cam_Vis'],
            'Cam_Req' => $f['Cam_Req'],
            'Cam_Por' => (isset($f['Cam_Por']) ? $f['Cam_Por'] : '0'),
            'Cam_Forrr' => (isset($f['Cam_Forrrrr']) ? $f['Cam_Forrrrr'] : ''),
            'Cam_Obs' => (isset($f['Cam_Obs']) ? $f['Cam_Obs'] : ''),
            'Cam_Cal' => $f['Cam_Cal'],
            'Cam_Sum' => (isset($f['Cam_Sum']) ? $f['Cam_Sum'] : 'N'),
            'Cam_For' => null,
            'Cam_Def' => 'N'
        );
        
        // Cargar fórmula si es calculado
        if($f['Cam_Cal'] == 'S') {
            $campo['Cam_For'] = $obBD_con1->getFormula($f['Cam_Cod'], null, null, $obBD_conexion_origen);
        }
        
        $campos[] = $campo;
    }
    
    $response = array(
        'success' => true,
        'plantilla' => array(
            'Map_Des' => $map_rol['Map_Des'],
            'Map_Obs' => $map_rol['Map_Obs'],
            'Rol_Tip' => $map_rol['Map_Tip']
        ),
        'campos' => $campos
    );
    
    echo json_encode($response);
    exit();
}

if(isset($savePlantilla)){        
    $data=$_POST;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(1,'0'.'*'.$Map_Des.'*'.$Map_Obs.'*'.$Ses_Emp_Cod.'*'.$Rol_Tip, $obBD_conexion);
        $Map_Cod= $obBD_con1->insercionid($obBD_conexion->conexion);
        foreach ($campos as &$v) {
            $v['Map_Cod']=$Map_Cod;
            if($v['Cam_Req']=='S') $v['Cam_Vis']='S';
            if(empty($v['Cam_Sum'])) $v['Cam_Sum']='N';
            $obBD_con1->operacionobBD(2,$v, $obBD_conexion);
            $v['Cam_Cod']=$obBD_con1->insercionid($obBD_conexion->conexion);
        }
        foreach ($campos as $c) {
            if($c['Cam_Cal']=='S'&&!empty($c['Cam_For'])){
                //var_dump('ver');
                $obBD_con1->saveFormula($c['Cam_Cod'],null,$c['Cam_For'],$campos,null,$obBD_conexion);
            }
        }
        
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    //var_dump($campos);
    if($obBD_con1->Error==0){ $responce=array('success'=>true,'Map_Cod'=>$Map_Cod); } else{$responce=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_con1->MsgError);}  
    echo json_encode($responce);exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Rrhh Plantilla Registrar [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../../framework/jquery/formula/pignose.formula.css" />
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>   
        <script type="text/javascript" src="../../framework/jquery/formula/pignose.formula.build.js"></script>
        <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
        <style>               
            .checkbox{padding-top: 0px !important;min-height: 0 !important;; }                    
            .checkbox input[type=checkbox]{margin-top: 0;}   
            .items-group{width: 49.6%;display: inline-table;}
            .formula-wrapper .formula-advanced{z-index: -1;}
            .ui-jqgrid td input[type=checkbox]{cursor: default !important;} 
            .inputsRol input[type=checkbox]{vertical-align: middle; margin-top: -2px;}
            .inputsRol label{margin-bottom: 0px;}
        </style>
        <script type="text/javascript">
            var formulas_base={
//                    deci_terc:{"operator":"/","operand1":{"value":0,"type":"item","variable":"sueldo_dias","text":"{sueldo_dias}"},"operand2":{"value":"12","type":"unit"}},
//                    deci_cuar:{"operator":"=","operand1":{"value":1,"type":"item","variable":"tiempo_parcial","text":"{tiempo_parcial}"},"operand2":{"operator":"?","operand1":{"operator":"/","operand1":{"operator":"*","operand1":{"value":0,"type":"item","variable":"sueldo_bas_medio","text":"{sueldo_bas_medio}"},"operand2":{"value":0,"type":"item","variable":"dias","text":"{dias}"}},"operand2":{"value":"360","type":"unit"}},"operand2":{"operator":"/","operand1":{"operator":"*","operand1":{"value":0,"type":"item","variable":"sueldo_bas","text":"{sueldo_bas}"},"operand2":{"value":0,"type":"item","variable":"dias","text":"{dias}"}},"operand2":{"value":"360","type":"unit"}}}},
//                    fond_reser:{"operator":"/","operand1":{"operator":"*","operand1":{"value":0,"type":"item","variable":"sueldo_dias","text":"{sueldo_dias}"},"operand2":{"value":0,"type":"item","variable":"fond_porc","text":"{fond_porc}"}},"operand2":{"value":"100","type":"unit"}}
                },
                formulas_rol={                    
//                    sueldo_dias:{"operator":"*","operand1":{"operator":"/","operand1":{"operator":"*","operand1":{"value":0,"type":"item","variable":"sueldo","text":"{sueldo}"},"operand2":{"value":"12","type":"unit"}},"operand2":{"value":0,"type":"item","variable":"dias_anio","text":"{dias_anio}"}},"operand2":{"value":0,"type":"item","variable":"dias","text":"{dias}"}},
                    //sueldo_semana:{"operator":"*","operand1":{"operator":"/","operand1":{"operator":"*","operand1":{"value":0,"type":"item","variable":"sueldo","text":"{sueldo}"},"operand2":{"value":"12","type":"unit"}},"operand2":{"value":"364","type":"unit"}},"operand2":{"value":0,"type":"item","variable":"dias","text":"{dias}"}},
//                    sueldo_bisemana:{"operator":"*","operand1":{"operator":"/","operand1":{"operator":"*","operand1":{"value":0,"type":"item","variable":"sueldo","text":"{sueldo}"},"operand2":{"value":"12","type":"unit"}},"operand2":{"value":"364","type":"unit"}},"operand2":{"value":0,"type":"item","variable":"dias","text":"{dias}"}},
                    //sueldo_quincena:{"operator":"*","operand1":{"operator":"/","operand1":{"operator":"*","operand1":{"value":0,"type":"item","variable":"sueldo","text":"{sueldo}"},"operand2":{"value":"12","type":"unit"}},"operand2":{"value":"360","type":"unit"}},"operand2":{"value":0,"type":"item","variable":"dias","text":"{dias}"}},
                    //sueldo_mes:{"operator":"*","operand1":{"operator":"/","operand1":{"value":0,"type":"item","variable":"sueldo","text":"{sueldo}"},"operand2":{"value":"30","type":"unit"}},"operand2":{"value":0,"type":"item","variable":"dias","text":"{dias}"}},
                    //deci_terc:{"operator":"=","operand1":{"value":0,"type":"item","variable":"deci_terc_acum","text":"{deci_terc_acum}"},"operand2":{"operator":"?","operand1":formulas_base['deci_terc'],"operand2":{"value":0,"type":"unit"}}},
                    //deci_cuar:{"operator":"=","operand1":{"value":0,"type":"item","variable":"deci_cuar_acum","text":"{deci_cuar_acum}"},"operand2":{"operator":"?","operand1":formulas_base['deci_cuar'],"operand2":{"value":0,"type":"unit"}}},
                    //fond_reser:{"operator":"=","operand1":{"value":1,"type":"item","variable":"fond_reser_anio","text":"{fond_reser_anio}"},"operand2":{"operator":"?","operand1":{"operator":"=","operand1":{"value":0,"type":"item","variable":"fond_reser_acum","text":"{fond_reser_acum}"},"operand2":{"operator":"?","operand1":formulas_base['fond_reser'],"operand2":{"value":0,"type":"unit"}}},"operand2":{"value":0,"type":"unit"}}},
                    //deci_terc_provi:{"operator":"=","operand1":{"value":1,"type":"item","variable":"deci_terc_acum","text":"{deci_terc_acum}"},"operand2":{"operator":"?","operand1":formulas_base['deci_terc'],"operand2":{"value":0,"type":"unit"}}},
                    //deci_cuar_provi:{"operator":"=","operand1":{"value":1,"type":"item","variable":"deci_cuar_acum","text":"{deci_cuar_acum}"},"operand2":{"operator":"?","operand1":formulas_base['deci_cuar'],"operand2":{"value":0,"type":"unit"}}},
                    //fond_reser_provi:{"operator":"=","operand1":{"value":1,"type":"item","variable":"fond_reser_anio","text":"{fond_reser_anio}"},"operand2":{"operator":"?","operand1":{"operator":"=","operand1":{"value":1,"type":"item","variable":"fond_reser_acum","text":"{fond_reser_acum}"},"operand2":{"operator":"?","operand1":formulas_base['fond_reser'],"operand2":{"value":0,"type":"unit"}}},"operand2":{"value":0,"type":"unit"}}},
                    //iess:{"operator":"/","operand1":{"operator":"*","operand1":{"value":0,"type":"item","variable":"sueldo_dias","text":"{sueldo_dias}"},"operand2":{"value":0,"type":"item","variable":"iess_porc","text":"{iess_porc}"}},"operand2":{"value":"100","type":"unit"}}, 
                    //iess:{"operator":"=","operand1":{"type":"item","value":"1.0000","text":"{es_afiliado}","variable":"es_afiliado"},"operand2":{"operator":"?","operand1":{"operator":"/","operand1":{"operator":"*","operand1":{"type":"item","value":"0","text":"{sueldo_dias}","variable":"sueldo_dias"},"operand2":{"type":"item","value":"0","text":"{iess_porc}","variable":"iess_porc"}},"operand2":{"type":"unit","value":"100"}},"operand2":{"type":"unit","value":"0"}}},
                    //total_rol:{operator:'-',operand1:{type:'item',value:0,text:'(total_ing)',variable:'total_ingr'},operand2:{type:'item',value:0,text:'(total_egr)',variable:'total_egr'}}
                };
            //deci_terc=
            var fijoFlieds={
                anticipos_rol_p:{Index:13,Cam_Grp:2,Cam_Tip:"E",Cam_Des:"ANTICIPOS",Cam_Dec:"Anticip.",Cam_Var:"anticipos_rol_p",Cam_Ord:"2",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Anticipos recibidos en el rol.",Cam_Cal:"N",Cam_For:null,Cam_Def:'S',Cam_Sum:'S'},
                descuentos_rol_p:{Index:14,Cam_Grp:2,Cam_Tip:"E",Cam_Des:"DESCUENTOS",Cam_Dec:"Descuen.",Cam_Var:"descuentos_rol_p",Cam_Ord:"3",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Descuentos recibidos en el rol.",Cam_Cal:"N",Cam_For:null,Cam_Def:'S',Cam_Sum:'S'},
                prestamos_rol_p:{Index:15,Cam_Grp:2,Cam_Tip:"E",Cam_Des:"PRESTAMOS",Cam_Dec:"Prestamos",Cam_Var:"prestamos_rol_p",Cam_Ord:"4",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Prestamos recibidos en el rol.",Cam_Cal:"N",Cam_For:null,Cam_Def:'S',Cam_Sum:'S'},
                anti_util:{"Index":100,"Cam_Tip":"I","Cam_Des":"ANTICIPO UTILIDAD","Cam_Dec":"Antici.Ut.","Cam_Var":"anti_util","Cam_Ord":"100","Cam_Vis":"S","Cam_Sum":"S","Cam_Por":"0","Cam_Forrr":"","Cam_Obs":"Anticipo a Utilidades","Cam_Req":"N","Cam_Cal":"S","Cam_Grp":1,"Cam_For":null},
                desc_util:{"Index":100,"Cam_Tip":"E","Cam_Des":"OTROS DESCUENTOS","Cam_Dec":"Desc.Otros","Cam_Var":"desc_util","Cam_Ord":"100","Cam_Vis":"S","Cam_Sum":"S","Cam_Por":"0","Cam_Forrr":"","Cam_Obs":"Descuento por excedente sueldo","Cam_Req":"N","Cam_Cal":"S","Cam_Grp":2,"Cam_For":null},
                aporte_extras_rol_p:{"Index":100,"Cam_Tip":"I","Cam_Des":"EXTRAS APORTE","Cam_Dec":"AporteExtr","Cam_Var":"aporte_extras_rol_p","Cam_Ord":"100","Cam_Req":"N","Cam_Sum":"S","Cam_Por":"0","Cam_Forrr":"","Cam_Obs":"Valores Extras Declarados Al IESS","Cam_Vis":"N","Cam_Cal":"S","Cam_Grp":0,"Cam_For":null},
                hora_extra_rol_p:{"Index":100,"Cam_Tip":"I","Cam_Des":"HORAS EXTRAS","Cam_Dec":"Hor.Extras","Cam_Var":"hora_extra_rol_p","Cam_Ord":"100","Cam_Req":"S","Cam_Sum":"S","Cam_Por":"0","Cam_Forrr":"","Cam_Obs":"Horas Extras Rol Pagos","Cam_Vis":"S","Cam_Cal":"N","Cam_Grp":1,"Cam_For":null},
                extension_conyugal:{"Index":100,"Cam_Tip":"E","Cam_Des":"EXTENSION CONYUGAL DE SALUD","Cam_Dec":"Ext.Conyu.","Cam_Var":"extension_conyugal","Cam_Ord":"100","Cam_Req":"S","Cam_Sum":"S","Cam_Por":"0","Cam_Forrr":"","Cam_Obs":"Extension Conyugal de Salud","Cam_Vis":"S","Cam_Cal":"N","Cam_Grp":2,"Cam_For":null},
                semanas:{Index:29,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"SEMANAS",Cam_Dec:"Semanas",Cam_Var:"semanas",Cam_Ord:"9",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Semanas del presente Año",Cam_Cal:"N",Cam_For:null,Cam_Def:'S'},

                labores_total:{"Index":100,"Cam_Tip":"I","Cam_Des":"LABORES TOTAL","Cam_Dec":"Lab.Total","Cam_Var":"labores_total","Cam_Ord":"100","Cam_Req":"N","Cam_Por":"0","Cam_Forrr":"","Cam_Obs":"Total calculado del modulo bananero","Cam_Vis":"S","Cam_Cal":"N","Cam_Grp":1,"Cam_For":null},
                labores_ingreso:{"Index":100,"Cam_Tip":"I","Cam_Des":"LABORES INGRESO","Cam_Dec":"Lab.Ingreso","Cam_Var":"labores_ingreso","Cam_Ord":"100","Cam_Req":"N","Cam_Sum":"S","Cam_Por":"0","Cam_Forrr":"","Cam_Obs":"Diferencia positiva del sueldo de labores y el sueldo contrato","Cam_Vis":"S","Cam_Cal":"N","Cam_Grp":1,"Cam_For":null},
                labores_egreso:{"Index":100,"Cam_Tip":"E","Cam_Des":"LABORES EGRESO","Cam_Dec":"Lab.Egreso","Cam_Var":"labores_egreso","Cam_Ord":"100","Cam_Req":"N","Cam_Sum":"S","Cam_Por":"0","Cam_Forrr":"","Cam_Obs":"Diferencia negativa del sueldo de labores y el sueldo contratado","Cam_Vis":"S","Cam_Cal":"N","Cam_Grp":2,"Cam_For":null}
            };
                
            var semanas=[
                    
                ],
                afiliacion=[                                            
                    {Index:16,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"PORCENTAJE IESS EMPLEADO",Cam_Dec:"P.Iess",Cam_Var:"iess_porc",Cam_Ord:"6",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"9.45",Cam_Forrr:"",Cam_Obs:"Procentaje de aportacion.",Cam_Cal:"N",Cam_For:null},
                    {Index:17,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"PORCENTAJE IESS EMPLEADOR",Cam_Dec:"P.Iess",Cam_Var:"iess_porc_emple",Cam_Ord:"7",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"17.60",Cam_Forrr:"",Cam_Obs:"Procentaje de aportacion del due�o.",Cam_Cal:"N",Cam_For:null},
                    {Index:18,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"PORCENTAJE IESS PATRONAL",Cam_Dec:"P.Iess",Cam_Var:"iess_porc_patro",Cam_Ord:"8",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"11.15",Cam_Forrr:"",Cam_Obs:"Procentaje de aportacion patronal.",Cam_Cal:"N",Cam_For:null},
                    {Index:19,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"PORCENTAJE IESS IECE",Cam_Dec:"P.Iess",Cam_Var:"iess_porc_iece",Cam_Ord:"9",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"1",Cam_Forrr:"",Cam_Obs:"Procentaje de aportacion IECE.",Cam_Cal:"N",Cam_For:null},
                    {Index:20,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"PORCENTAJE IESS TIEMPO PARCIAL",Cam_Dec:"P.Iess",Cam_Var:"tie_parc_porc",Cam_Ord:"10",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"4.41",Cam_Forrr:"",Cam_Obs:"Procentaje de aportacion Tiempo Parcial.",Cam_Cal:"N",Cam_For:null},
                    {Index:21,Cam_Grp:2,Cam_Tip:"E",Cam_Des:"IESS",Cam_Dec:"Iess",Cam_Var:"iess",Cam_Ord:"1",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Valor de la aportación al IESS",Cam_Cal:"S",Cam_For:null,Cam_Def:'S',Cam_Sum:'S'}
                ],
                extras=[
                    {Index:29,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"ANIO EN EMPRESA",Cam_Dec:"ANIO",Cam_Var:"fond_reser_anio",Cam_Ord:"8",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Cumplio un Anio?.",Cam_Cal:"N",Cam_For:null},
                    {Index:30,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"ACUMULA DECIMO TERCERO",Cam_Dec:"Acum. D. Terc.",Cam_Var:"deci_terc_acum",Cam_Ord:"9",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Acumula Decimo Tercero?.",Cam_Cal:"N",Cam_For:null},
                    {Index:31,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"ACUMULA DECIMO CUARTO ",Cam_Dec:"Acum. D. Cuar.",Cam_Var:"deci_cuar_acum",Cam_Ord:"10",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Acumula Decimo Cuarto?.",Cam_Cal:"N",Cam_For:null},
                    {Index:32,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"ACUMULA DECIMO FONDO RESERVA",Cam_Dec:"Acum. F. Rese.",Cam_Var:"fond_reser_acum",Cam_Ord:"11",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Acumula Fondo Reserva?.",Cam_Cal:"N",Cam_For:null}                    
                ],
                defaults=[
                    {Index:0,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"DIAS LABORADOS",Cam_Dec:"Dias",Cam_Var:"dias",Cam_Ord:"1",Cam_Vis:"S",Cam_Req:"S",Cam_Por:"30",Cam_Forrr:"",Cam_Obs:"Dias laborados.",Cam_Cal:"N",Cam_For:null,Cam_Def:'S'},
                    {Index:1,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"SUELDO BASICO UNIFICADO",Cam_Dec:"S.Basico.:",Cam_Var:"sueldo_bas",Cam_Ord:"4",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"425.00",Cam_Forrr:"",Cam_Obs:"Valor sueldo basico unificado.",Cam_Cal:"N",Cam_For:null},
                    {Index:2,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"SUELDO",Cam_Dec:"Sueldo",Cam_Var:"sueldo",Cam_Ord:"2",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Sueldo afilicacion o sueldo base del personal.",Cam_Cal:"N",Cam_For:null,Cam_Def:'S'},
                    {Index:3,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"SUELDO NETO",Cam_Dec:"S. Neto",Cam_Var:"sueldo_neto",Cam_Ord:"3",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Sueldo neto del personal,",Cam_Cal:"N",Cam_For:null,Cam_Def:'S'},
                    {Index:4,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"INGRESO NETO CALCULADO",Cam_Dec:"Ingr.&nbsp;Neto Calc.",Cam_Var:"sueldo_neto_calc",Cam_Ord:"4",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Sueldo neto calculo del personal,",Cam_Cal:"S",Cam_For:null,Cam_Def:'S'},
                    {Index:11,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"PORCENTAJE F. RESERVA",Cam_Dec:"Porc.Rese.",Cam_Var:"fond_porc",Cam_Ord:"5",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"8.33",Cam_Forrr:"",Cam_Obs:"Porcentaje de Fondos de Reserva.",Cam_Cal:"N",Cam_For:null},
                    {Index:5,Cam_Grp:1,Cam_Tip:"I",Cam_Des:"SUELDO A PAGAR",Cam_Dec:"Sueldo",Cam_Var:"sueldo_dias",Cam_Ord:"1",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Sueldo afilicacion o sueldo base del personal.",Cam_Cal:"S",Cam_For:null,Cam_Def:'S',Cam_Sum:'S'},
                    {Index:9,Cam_Grp:1,Cam_Tip:"I",Cam_Des:"DECIMO TERCERO",Cam_Dec:"D.Terc.",Cam_Var:"deci_terc",Cam_Ord:"10",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Calculo de decimo tercero",Cam_Cal:"S",Cam_For:null,Cam_Def:'S',Cam_Sum:'S'},
                    {Index:10,Cam_Grp:1,Cam_Tip:"I",Cam_Des:"DECIMO CUARTO",Cam_Dec:"D.Cuar.",Cam_Var:"deci_cuar",Cam_Ord:"11",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Calculo de decimo tercero",Cam_Cal:"S",Cam_For:null,Cam_Def:'S',Cam_Sum:'S'},
                    {Index:12,Cam_Grp:1,Cam_Tip:"I",Cam_Des:"FONDOS DE RESERVA",Cam_Dec:"F.Reserv.",Cam_Var:"fond_reser",Cam_Ord:"12",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"",Cam_Forrr:"",Cam_Obs:"Calculo de Fondos de Resserva",Cam_Cal:"S",Cam_For:null,Cam_Def:'S',Cam_Sum:'S'},
                    $.cloneData(fijoFlieds['anticipos_rol_p']),
                    $.cloneData(fijoFlieds['descuentos_rol_p']),
                    $.cloneData(fijoFlieds['prestamos_rol_p']),
                    {Index:6,Cam_Grp:4,Cam_Tip:"T",Cam_Des:"TOTAL INGRESOS",Cam_Dec:"Total Ingr.",Cam_Var:"total_ingr",Cam_Ord:"1",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Total Ingresos del rol.",Cam_Cal:"S","Cam_For":null,Cam_Def:'S'},
                    {Index:7,Cam_Grp:4,Cam_Tip:"T",Cam_Des:"TOTAL EGRESOS",Cam_Dec:"Total Egr.",Cam_Var:"total_egr",Cam_Ord:"2",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Total de egresos del rol.",Cam_Cal:"S","Cam_For":null,Cam_Def:'S'},
                    {Index:8,Cam_Grp:4,Cam_Tip:"T",Cam_Des:"TOTAL ROL",Cam_Dec:"Total",Cam_Var:"total_rol",Cam_Ord:"3",Cam_Vis:"S",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Total a Pagar.",Cam_Cal:"S",Cam_For:formulas_rol['total_rol'],Cam_Def:'S'},
                    {Index:22,Cam_Grp:3,Cam_Tip:"P",Cam_Des:"APORTE PATRONAL",Cam_Dec:"A.Patronal",Cam_Var:"aporte_patronal",Cam_Ord:"1",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"",Cam_Forrr:"",Cam_Obs:"Calculo de Aportacion Patronal",Cam_Cal:"S",Cam_For:null,Cam_Def:'S'},
                    {Index:23,Cam_Grp:3,Cam_Tip:"P",Cam_Des:"PROVISION DECIMO TERCERO",Cam_Dec:"Prov. D.Terc.",Cam_Var:"deci_terc_provi",Cam_Ord:"10",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Calculo de provision decimo tercero",Cam_Cal:"S",Cam_For:null,Cam_Def:'S'},
                    {Index:24,Cam_Grp:3,Cam_Tip:"P",Cam_Des:"PROVISION DECIMO CUARTO",Cam_Dec:"Prov. D.Cuar.",Cam_Var:"deci_cuar_provi",Cam_Ord:"11",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Calculo de provision decimo tercero",Cam_Cal:"S",Cam_For:null,Cam_Def:'S'},
                    {Index:25,Cam_Grp:3,Cam_Tip:"P",Cam_Des:"PROVISION FONDOS DE RESERVA",Cam_Dec:"Prov. F.Reserv.",Cam_Var:"fond_reser_provi",Cam_Ord:"4",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"",Cam_Forrr:"",Cam_Obs:"Calculo de provision Fondos de Resserva",Cam_Cal:"S",Cam_For:null,Cam_Def:'S'},
                    {Index:26,Cam_Grp:3,Cam_Tip:"P",Cam_Des:"PROVISION VACACIONES",Cam_Dec:"Vacaciones",Cam_Var:"vacacion_rol_p",Cam_Ord:"5",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"",Cam_Forrr:"",Cam_Obs:"Calculo de provision vacaciones",Cam_Cal:"S",Cam_For:null,Cam_Def:'S'},
                    {Index:27,Cam_Grp:3,Cam_Tip:"P",Cam_Des:"PROVISION IECE",Cam_Dec:"IECE",Cam_Var:"aporte_iece",Cam_Ord:"6",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"",Cam_Forrr:"",Cam_Obs:"Calculo de provision Iece",Cam_Cal:"S",Cam_For:null,Cam_Def:'S'},
                    {Index:28,Cam_Grp:3,Cam_Tip:"P",Cam_Des:"TIEMPO PARCIAL",Cam_Dec:"T.Parcial",Cam_Var:"tiempo_parcial_rol",Cam_Ord:"7",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"",Cam_Forrr:"",Cam_Obs:"Calculo de provision salud tiempo parcial",Cam_Cal:"S",Cam_For:null,Cam_Def:'S'},
                    //{Index:29,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"PORCENTAJE TIEMPO PARCIAL",Cam_Dec:"Porc. T. Parcial.",Cam_Var:"tie_parc_porc",Cam_Ord:"8",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Acumula Fondo Reserva?.",Cam_Cal:"N",Cam_For:null},
                    //{Index:26,Cam_Grp:0,Cam_Tip:"D",Cam_Des:"DIAS TIPO",Cam_Dec:"DIAS FRECUENCIA",Cam_Var:"dias_anio",Cam_Ord:"8",Cam_Vis:"N",Cam_Req:"N",Cam_Por:"0",Cam_Forrr:"",Cam_Obs:"Los dias para el calculo(semenas, meses).",Cam_Cal:"N",Cam_For:null}
                ],campos=[],formula=null,index=null;
        </script>    
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registro de Plantillas Rol</h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
            <div>
                <div class="row">                   
                    <div class="col-xs-12">                       
                        <div class="form-horizontal normal">
                               <div class="row">
                                   <div class="col-xs-7"> 
                                       <fieldset class="exa-fieldset">                           
                                      <legend class="Titulos2">Datos Generales</legend> <!-- Form Name -->
                                      <form id="rolForm" action="javascript:validaPlantilla();">
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-2 control-label label-xs">Base de Datos:</label>  
                                          <div class="col-sm-10"> 
                                              <select id="selBaseDatos" class="form-control input-xs">
                                                  <option value="">Seleccione una base de datos...</option>
                                              </select>
                                          </div>                                  
                                        </div>
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-2 control-label label-xs">Cargar Plantilla:</label>  
                                          <div class="col-sm-10"> 
                                              <select id="selPlantillaBase" class="form-control input-xs" disabled>
                                                  <option value="">Primero seleccione una base de datos...</option>
                                              </select>
                                          </div>                                  
                                        </div>
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-2 control-label label-xs required">Titulo:</label>
                                          <div class="col-sm-10"> 
                                              <input name="Map_Des" type="text" class="form-control input-xs" required="" />
                                          </div>                                  
                                        </div> 
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label label-xs required">Frecuencia:</label>  
                                            <div class="col-sm-4"> 
                                                <select id="Rol_Tip" name="Rol_Tip" class="form-control input-xs" required="" onchange="changeFrecuencia()">                                            
                                                    <option value="M" data-dias="30" >Mensual</option>
                                                    <option value="Q" data-dias="15">Quincenal</option>
                                                    <option value="BS" data-dias="14">BiSemanal</option>
                                                    <option value="S" data-dias="7">Semanal</option>
                                                </select>
                                            </div> 
                                            <label class="col-sm-2 control-label label-xs required">Afiliados:</label>  
                                            <div class="col-sm-4 radioset"> 
                                                <input id="radca1" name="op_opciones" type="radio" value="S" checked="" onchange="setCamposTip(this.value)" alt="" /><label for="radca1">&nbsp;&nbsp;Si&nbsp;&nbsp;</label>
                                                <input id="radca2" name="op_opciones" type="radio" value="N" onchange="setCamposTip(this.value)" alt="" /><label for="radca2">&nbsp;&nbsp;No&nbsp;&nbsp;</label>                          
                                            </div>     
                                        </div> 
                                       <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-2 control-label label-sm ">Observación:</label>  
                                          <div class="col-sm-10"> 
                                              <textarea name="Map_Obs" class="form-control input-xs" ></textarea>
                                          </div>                                  
                                        </div> 
                                       </form>
                                        <div class="form-group">
                                            <div class="col-sm-9"> 
                                                <table id="campos"></table>                                               
                                            </div>
                                            <div class="col-sm-3 inputsRol">
                                                <fieldset class="exa-fieldset">
                                                    <legend class="Titulos2">Campos Fijos</legend>
                                                    <div><label><input type="checkbox" value="aporte_extras_rol_p" onchange="getionCampo($(this).is(':checked'),this.value);"  /> Extra Aporte (Calc.)</label></div>
                                                    <div><label><input type="checkbox" value="anticipos_rol_p" onchange="getionCampo($(this).is(':checked'),this.value);" checked="checked" /> Anticipos</label></div>
                                                    <div><label><input type="checkbox" value="descuentos_rol_p" onchange="getionCampo($(this).is(':checked'),this.value);" checked="checked" /> Descuentos</label></div>
                                                    <div><label><input type="checkbox" value="prestamos_rol_p" onchange="getionCampo($(this).is(':checked'),this.value);" checked="checked" /> Prestamos</label></div>
                                                    <div><label><input type="checkbox" value="hora_extra_rol_p" onchange="getionCampo($(this).is(':checked'),this.value);"  /> Horas Extras (Ing.)</label></div>
                                                    <div><label><input type="checkbox" value="extension_conyugal" onchange="getionCampo($(this).is(':checked'),this.value);"  /> Ext. Conyugal (Egr.)</label></div>
                                                    <div><label><input type="checkbox" value="anti_util" onchange="getionCampo($(this).is(':checked'),this.value);"  /> Faltante (Ing.)</label></div>
                                                    <div><label><input type="checkbox" value="desc_util" onchange="getionCampo($(this).is(':checked'),this.value);"  /> Excedente (Egr.)</label></div>
                                                    <div><label><input type="checkbox" value="labores_campo" onchange="getionCampo($(this).is(':checked'),this.value);"  /> Labores (Bananero)</label></div>
                                                </fieldset>
                                            </div>
                                        </div>  
                                        <div class="form-group">
                                            <div class="col-sm-12"> 
                                                <button type="button" onclick="$('#clubes').trigger('reloadGrid', [{ page: 1 }]);" class="btn btn-success btn-xs btn-form"><span class="glyphicon glyphicon-refresh"></span> Actualizar</button>
                                                <button type="button" onclick="$('#clubes').selectAllByComlumn('act1',true);" class="btn btn-success btn-xs btn-form"><span class="glyphicon glyphicon-check"></span> Marcar Todo</button>
                                                <button type="button" onclick="$('#clubes').selectAllByComlumn('act1',false);" class="btn btn-success btn-xs btn-form"><span class="glyphicon glyphicon-unchecked"></span> Desmarcar Todo</button>
                                            </div> 
                                       </div>  
                                       </fieldset>  
                                   </div>
                                   <div class="col-xs-5">
                                       <fieldset class="exa-fieldset">                           
                                       <legend class="Titulos2">Campo</legend> <!-- Form Name -->
                                       <form id="camposForm" action="javascript:;">
                                           <input type="hidden" name="Index" value="" />
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-2 control-label label-xs required">Tipo:</label>  
                                          <div class="col-sm-4"> 
                                              <select name="Cam_Tip" class="form-control input-xs" onchange="updateOrdenCam(this.value)" required="">
                                                  <option value="">Seleccione...</option>
                                                  <option value="I">Ingreso</option>
                                                  <option value="E">Egreso</option>
                                              </select>
                                          </div>                                          
                                        </div>                                        
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-2 control-label label-xs required">Nombre:</label>  
                                          <div class="col-sm-10"> 
                                              <input name="Cam_Des" type="text" class="form-control input-xs" required="" />
                                          </div>                                  
                                        </div> 
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-2 control-label label-xs required">Abrev:</label>  
                                          <div class="col-sm-4"> 
                                              <input name="Cam_Dec" type="text" maxlength="10" class="form-control input-xs" required="" />
                                          </div>                                  
                                        </div> 
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-2 control-label label-xs required">Variable:</label>  
                                          <div class="col-sm-4"> 
                                              <input name="Cam_Var" type="text" maxlength="10" class="form-control input-xs" required="" />
                                          </div>                                  
                                        </div> 
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-2 control-label label-xs required">Orden:</label>  
                                          <div class="col-sm-4"> 
                                              <input id="Cam_Ord" name="Cam_Ord" type="text" class="form-control input-xs" required="" />
                                          </div>                                  
                                        </div>
                                        <!-- Multiple Checkboxes -->
                                        <div class="form-group">
                                          <label class="col-sm-2 control-label" for="checkboxes">Atributos:</label>
                                          <div class="col-sm-4">
                                          <div class="checkbox">
                                              <label class="label-xs"  for="checkboxes-1">
                                              <input type="checkbox" name="Cam_Req" id="Cam_Req" value="S" offval="N" data-trigger="true">
                                              <i class="glyphicon glyphicon-asterisk"></i> Requerido
                                            </label>
                                                </div>    
                                          <div class="checkbox">
                                              <label class="label-xs" for="checkboxes-0">
                                              <input type="checkbox" name="Cam_Vis" id="Cam_Vis"  value="S" offval="N" data-trigger="true">
                                              <i class="glyphicon glyphicon-eye-open"></i> Visible
                                            </label>
                                                </div>                                          
                                          <div class="checkbox">
                                              <label class="label-xs" for="checkboxes-2">
                                              <input type="checkbox" name="Cam_Cal" id="Cam_Cal" value="S" offval="N" data-trigger="true">
                                              <i class="glyphicon glyphicon-link"></i> Calculado
                                            </label>
                                                </div>
                                          <div class="checkbox">
                                              <label class="label-xs" for="checkboxes-3">
                                                  <input type="checkbox" name="Cam_Sum" id="Cam_Sum" value="S" offval="N" data-trigger="true" checked="">
                                              <i class="glyphicon glyphicon-link"></i> En Sumatoria
                                            </label>
                                                </div>    
                                          </div>
                                        </div>
                                        <!-- static input-->
                                        <div class="form-group constante">
                                          <label class="col-sm-2 control-label label-xs required">Valor:</label>  
                                          <div class="col-sm-3"> 
                                              <input id="Cam_Por" name="Cam_Por" type="number" class="form-control input-xs nospin text-right" required=""/>
                                          </div>                                  
                                        </div> 
                                        <!-- static input-->
                                        <div class="form-group calculado" style="display: none;">
                                          <label class="col-sm-2 control-label label-xs required">Formula:</label>  
                                            <div class="col-md-10">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success" onclick="editFormula(null);" type="button"><span class="glyphicon glyphicon-link" title="Buscar Proveedor"></span></button>
                                                </span>
                                              <input id="Cam_Forrr" name="Cam_Forrr" class="form-control" type="text" readonly="">
                                            </div>

                                          </div>                                
                                        </div>                                           
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-2 control-label label-sm ">Observación:</label>  
                                          <div class="col-sm-10"> 
                                              <textarea name="Cam_Obs" class="form-control input-xs" ></textarea>
                                          </div>                                  
                                        </div> 
                                        <!-- Button -->
                                        <div class="form-group">
                                          <label class="col-md-2 control-label" for="singlebutton"></label>
                                          <div class="col-md-4">
                                              <button type="submit" id="btn-cam" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-plus"></i> Agregar Campo</button>
                                          </div>
                                        </div>
                                        </form>
                                       </fieldset> 
                                   </div>    
                               </div>    
                        </div>                         
                    </div>
                </div> 
                
                <div class="row">   
                    <div class="col-sm-12 center">
                        <button type="button" onclick="$('#rolForm').formSubmit();" class="btn btn-primary btn-save"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                        <button type="button" onclick="nuevo();" class="btn btn-primary btn-new" disabled=""><span class="glyphicon glyphicon-floppy-disk"></span> Nuevo</button>                        
                    </div>
                    <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( &nbsp;<span class="required"></span> ) son campos obligatorios.</div>
                </div>   
            </div>  
            
        </div>
    </div>
    
    <script type="text/javascript">  
        function getionCampo(action,variable){
            if(action){
                if(variable == 'labores_campo'){
                    var field=$.cloneData(fijoFlieds['labores_total']);
                    field['Cam_Ord']=$.arrayMaxVal(campos,'Cam_Ord','Cam_Tip',field['Cam_Tip'])+1;
                    field['Index']=$.arrayMaxVal(campos,'Index')+1;
                    campos.push(field);

                    var field=$.cloneData(fijoFlieds['labores_ingreso']);
                    field['Cam_Ord']=$.arrayMaxVal(campos,'Cam_Ord','Cam_Tip',field['Cam_Tip'])+1;
                    field['Index']=$.arrayMaxVal(campos,'Index')+1;
                    campos.push(field);

                    var field=$.cloneData(fijoFlieds['labores_egreso']);
                    field['Cam_Ord']=$.arrayMaxVal(campos,'Cam_Ord','Cam_Tip',field['Cam_Tip'])+1;
                    field['Index']=$.arrayMaxVal(campos,'Index')+1;
                    campos.push(field);
                }
                else{
                    var field=$.cloneData(fijoFlieds[variable]);
                    field['Cam_Ord']=$.arrayMaxVal(campos,'Cam_Ord','Cam_Tip',field['Cam_Tip'])+1;
                    field['Index']=$.arrayMaxVal(campos,'Index')+1;
                    campos.push(field);
                }
            }else{
                $.arraySpliceWhere(campos,'Cam_Var',variable);
            }
            updateCamGrid();
        }
        function changeFrecuencia(){
            var val=$('#Rol_Tip').val();
            campos[5]['Cam_For']=null;
            switch(val){
                case 'S':
                    //console.log('fgh');
                    //campos[5]['Cam_For']=formulas_rol['sueldo_semana'];
                    //campos=campos.concat($.extend(true,[],semanas));
                    getionCampo(true,'semanas');
                    break;
                case 'BS':
                    //campos[5]['Cam_For']=formulas_rol['sueldo_bisemana'];
                    //campos=campos.concat($.extend(true,[],semanas));
                    getionCampo(true,'semanas');
                    break;
                case 'Q':
                    //campos[5]['Cam_For']=formulas_rol['sueldo_quincena'];
                    //$.arraySpliceWhere(campos,'Cam_Var','semanas');
                    getionCampo(false,'semanas');
                    break;
                case 'M':
                    //campos[5]['Cam_For']=formulas_rol['sueldo_mes'];
                    //$.arraySpliceWhere(campos,'Cam_Var','semanas');
                    getionCampo(false,'semanas');
                    break;
            }
        }
        function setCamposTip(){ var tip=$('#rolForm').getData()['op_opciones']; campos=$.extend(true,[],defaults); if(tip==='S') campos=campos.concat($.extend(true,[],afiliacion)); changeFrecuencia();  }
        function resetCamForm(oper){ $('#camposForm').attr('action','javascript:'+oper+'();').setData({Cam_Vis:'S'}); if(oper==='add'){ $('#btn-cam').html('<i class="glyphicon glyphicon-plus"></i> Agregar Campo'); $('#formula').empty(); formula=null;index=null; }else $('#btn-cam').html('<i class="glyphicon glyphicon-plus"></i> Guardar Edición');  }
        function updateOrdenCam(tipo){ var Cam_Ord=$.arrayMaxVal(campos,'Cam_Ord','Cam_Tip',tipo)+1; $('#Cam_Ord').val(tipo!==''?Cam_Ord:''); } 
        function editFormula(ind){index=ind;$('#dialog').dialog('open'); if(ind!==null){$('#formula').data('formula').empty();$('#formula').data('formula').setFormula(campos[ind]['Cam_For']); }}
        function updateCamGrid(){ 
            $("#campos").setRowsByIndex(campos,'Index');
            $("#campos").startGridEdit();
            $('.formula-items-variables').empty();
            $.each(campos,function (i,v){
                if(v['Cam_Tip']!=='T')
                $('.formula-items-'+(v['Cam_Tip']==='D'||v['Cam_Tip']==='P'?'default':(v['Cam_Tip']==='I'?'ingreso':'egreso'))).append('<a href="#" class="formula-it formula-var formula-custom" data-value="0" data-variable="'+v['Cam_Var']+'"><b>{</b>'+v['Cam_Var']+'<b>}</b></a>');
            });
            $('.formula-drop .formula-drop-items .formula-var').draggable({
                revert: 'invalid', helper: 'clone', cancel: '', scroll: false
            });
        }        
        function nuevo(){ campos=$.extend(true,[],defaults); formula=null; $('.btn-new').attr('disabled','disabled'); $('.btn-save').removeAttr('disabled'); $('#rolForm').setData();resetCamForm('add');$('#campos').clearGrid();}
        function add(){
            var campo=$('#camposForm').getData();
            campo['Cam_Grp']=(campo['Cam_Tip']==='I'?campo['Cam_Grp']=1:campo['Cam_Grp']=2);
            if(campo['Cam_Cal']==='S'){
                campo['Cam_For']=formula;
                if(!$.varValid(formula)){ $('#Cam_Forrr').flyout('show'); return;}
            }else
                campo['Cam_For']=null;
            campos.push(campo);
            updateCamGrid();
            resetCamForm('add');
        }
        function edit(){            
            var campo=$('#camposForm').getData();   
            campo['Cam_Grp']=(campo['Cam_Tip']==='I'?campo['Cam_Grp']=1:campo['Cam_Grp']=2);
            var item=$.arrayGetItem(campos,'Index',campo['Index'],false);            
            if(campo['Cam_Cal']==='S'){
                item['Cam_For']=formula;
                if(!$.varValid(formula)){ $('#Cam_Forrr').flyout('show'); return;}
            }else
                item['Cam_For']=null;
            $.extend( item, campo );
            updateCamGrid();
            resetCamForm('add');            
        }        
        function deleteCam(Index){            
            $.arraySpliceWhere(campos,'Index',Index,false);            
            updateCamGrid();
            resetCamForm('add');
        }
        function editCam(Index){ 
            resetCamForm('edit'); 
            var item=$.arrayGetItem(campos,'Index',Index,false); 
            $('#camposForm').setData(item);             
            $('#formula').data('formula').empty();
            $('#formula').data('formula').setFormula(item['Cam_For']); 
            formula=item['Cam_For'];
            //console.log(item['Cam_For']);
        }
        function cargarPlantillaBase(Map_Cod, Dat_Dis){
            if(!Map_Cod || Map_Cod === '') {
                return;
            }
            $.createDialogConfirm('¿Está seguro que desea cargar esta plantilla? Se reemplazarán los datos actuales.', null, function(){
                $.getDataJson('', {loadPlantilla: true, Map_Cod: Map_Cod, Dat_Dis: Dat_Dis}, function(response){
                    if(response.success){
                        // Cargar datos generales
                        $('#rolForm').setData({
                            Map_Des: response.plantilla.Map_Des + ' (Copia)',
                            Map_Obs: response.plantilla.Map_Obs,
                            Rol_Tip: response.plantilla.Rol_Tip
                        });
                        
                        // Cargar campos
                        campos = [];
                        var index = 0;
                        var camposFijosEncontrados = {};
                        
                        // Primero, identificar qué campos fijos están presentes
                        var camposFijosVars = ['aporte_extras_rol_p', 'anticipos_rol_p', 'descuentos_rol_p', 'prestamos_rol_p', 
                                                'hora_extra_rol_p', 'extension_conyugal', 'anti_util', 'desc_util', 
                                                'labores_total', 'labores_ingreso', 'labores_egreso', 'semanas'];
                        
                        for(var i = 0; i < response.campos.length; i++){
                            var campo = response.campos[i];
                            if(camposFijosVars.indexOf(campo.Cam_Var) !== -1){
                                camposFijosEncontrados[campo.Cam_Var] = true;
                            }
                        }
                        
                        // Procesar campos y reasignar índices
                        for(var i = 0; i < response.campos.length; i++){
                            var campo = $.extend({}, response.campos[i]);
                            campo.Index = index++;
                            campos.push(campo);
                        }
                        
                        // Los campos fijos ya están cargados en el array campos, 
                        // no necesitamos marcar los checkboxes ya que podrían causar duplicados
                        // Los checkboxes solo se usan para agregar/quitar campos fijos manualmente
                        
                        // Detectar si tiene afiliación (buscar campo iess)
                        var tieneAfiliacion = false;
                        for(var i = 0; i < campos.length; i++){
                            if(campos[i].Cam_Var === 'iess'){
                                tieneAfiliacion = true;
                                break;
                            }
                        }
                        
                        // Configurar radio de afiliación
                        if(tieneAfiliacion){
                            $('#radca1').prop('checked', true);
                            $('#radca2').prop('checked', false);
                        } else {
                            $('#radca1').prop('checked', false);
                            $('#radca2').prop('checked', true);
                        }
                        
                        // Actualizar frecuencia (sin trigger para evitar conflictos)
                        $('#Rol_Tip').val(response.plantilla.Rol_Tip);
                        
                        // Actualizar grid
                        updateCamGrid();
                        
                        // Limpiar selector usando Select2 API
                        $('#selPlantillaBase').val(null).trigger('change');
                        
                        $.alert('Plantilla cargada correctamente. Puede modificar los datos y guardar como nueva plantilla.', null, 'success');
                    } else {
                        $.alert(response.message || 'Error al cargar la plantilla', null, 'alert');
                    }
                }, function(){
                    $.alert('Error al cargar la plantilla', null, 'alert');
                });
            });
        }
        function validaPlantilla(){  
            if($.arrayCountVal(campos,'Cam_Tip','I')===0) { $.alert('No ha declarado los campos de <u>Ingreso</u>.',null,'alert'); return; }
            if($.arrayCountVal(campos,'Cam_Tip','E')===0) { $.alert('No ha declarado los campos de <u>Egreso</u>.',null,'alert'); return; }
            var valid=true;
            //$.each(campos,function (i,v){ if(v['Cam_Cal']==='S'&&!$.varValid(v['Cam_For'])) { valid=false; $.alert('La formula del campo <i>'+v['Cam_Des']+'</i> es <u>Incorecta</u>!',null,'alert');  $("#campos").jqGrid('setSelection',v['Index'], false); return false;} }); if(!valid) return;
            var data=$('#rolForm').getData('savePlantilla');
            data['campos']=$.extend([],campos);
            for(var i=0,z=extras.length;i<z;i++) data['campos'].push(extras[i]);
            console.log('campos',data['campos']);
            $.createDialogConfirm('Está seguro que desea guardar la <b>Plantilla Rol</b>',data,function (data){
                $('.btn-save').attr('disabled','disabled');
                $.saveDataJson('',data,
                    function (){ $('.btn-new').removeAttr('disabled'); $('.btn-save').removeAttr('disabled'); },
                    function (){ $('.btn-save').removeAttr('disabled'); },
                    function (){ $('.btn-save').removeAttr('disabled'); }
                );
            });
        }
        resetCamForm('add');        
    </script>
<div id="dialog" title="Crear Formula">  
    <div><div class="Titulos2"><b>NOTA:</b> Arrastre los <u>CAMPOS</u> y sueltelos para crear la <u>Fórmula</u>.</div> <hr/></div>
        <div class="formula-drop">    
            <div class=' items-group'>
                <fieldset class='exa-fieldset'>
                    <legend class='Titulos2'>Campos Ingreso</legend>  
                    <div class="formula-drop-items formula-items-variables formula-items-ingreso"></div> 
                </fieldset>  
            </div>    
            <div class=' items-group'>
                <fieldset class='exa-fieldset'>
                    <legend class='Titulos2'>Campos Egreso</legend>      
                    <div class="formula-drop-items formula-items-variables formula-items-egreso"></div>  
                </fieldset>        
            </div>  
        </div>
        
    <div style="padding-bottom: 10px;"><div id="formula" class="formula-advanced"></div></div>
        <div class="formula-drop">
            <div class=' items-group'>
                <fieldset class='exa-fieldset'>
                    <legend class='Titulos2'>Operadores</legend>    
                        <div class="formula-drop-items">
                            <a href="#" class="formula-it formula-operator formula-bracket">(</a>
                            <a href="#" class="formula-it formula-operator formula-bracket">)</a>
                            <a href="#" class="formula-it formula-operator" data-value="+" >+</a>
                            <a href="#" class="formula-it formula-operator" data-value="-" >-</a>
                            <a href="#" class="formula-it formula-operator" data-value="*" >x</a>
                            <a href="#" class="formula-it formula-operator" data-value="/" >/</a><br/>
                            <a href="#" class="formula-it formula-operator" data-value="=" >=</a>
                            <a href="#" class="formula-it formula-operator" data-value="?" >?</a>
                        </div>  
                    </fieldset> 
            </div>   
            <div class=' items-group'>
                <fieldset class='exa-fieldset'>  
                    <legend class='Titulos2'>Campos Default</legend>    
                    <div class="formula-drop-items formula-items-variables formula-items-default">
                        <a href="#" class="formula-it formula-custom" data-value="0" data-variable="sueldo"><b>{</b>SUELDO<b>}</b></a>
                        <a href="#" class="formula-it formula-custom" data-value="0" data-variable="sueldo_neto"><b>{</b>SUELDO_NETO<b>}</b></a>
                    </div> 
                </fieldset>  
            </div>
        </div>
</div>
<script type="text/javascript">
    $(function() {
        $('#formula').formula({ 
            import: {
                item: function (e) {
                    if(e.type==='item') return '<a href="#" class="formula-custom" data-value="'+e['value']+'" data-variable="'+e['variable']+'">'+e['text']+'</a>';
                    else if(e.type==='unit') return '<div class="formula-unit">'+e['value']+'</div>';
                }
            },
            export: { 
                item: function ($e) { return {value:$e.data('value'),variable:$e.data('variable'),text:$e.text()}; }
            }
        });
        //$.createDialog('#dialog',300,600);
        $('#dialog').createDialog({
            width:600,height:400,icon:'link',
            buttons:[
                {text:"Aceptar", click:function() {
                        var data=$('#formula').data('formula').getFormula(); 
                        if(index===null){
                            $('#Cam_Forrr').val($('#formula').text()); 
                            formula=data['filterData']['data'];
                            $(this).dialog( "close" );
                        }else{
                            campos[index]['Cam_Forrr']=$('#formula').text();
                            campos[index]['Cam_For']=data['filterData']['data'];
                            if($.varValid(campos[index]['Cam_For'])){
                                $(this).dialog( "close" );
                                index=null;
                            }else{ $.alert('Formula Incorrecta!');}
                            
                        }
                        //console.log(data);
                        //$('#formula').data('formula').getFormula(function (data){ console.log(data.data); });
                    }, icons:{ primary: "ui-icon-check" }},
                {text: "Cancelar", click:function(){ $(this).dialog( "close" ); }, icons: { primary: "ui-icon-closethick" }}
            ]
        });
        $('.formula-drop .formula-drop-items .formula-it').draggable({
            revert: 'invalid', helper: 'clone', cancel: '', scroll: false
        });
        $('.formula-advanced').droppable({
            hoverClass: "formula-active",
            drop: function( event, ui ) { var $e = ui.draggable.clone(); $(this).formula('insert', $e); }
        });        
        $("#campos").createGrid({
            height: 150,
            sortname:'Cam_Ord',
            colModel: [                
                { label: 'Clu. Cod.', name: 'Index', key: true, width: 30,align:"center", hidden:true },
                { label: 'Grupo', name: 'Cam_Grp', width: 1,formatter: function (cellValue, options, rowObject) {if(cellValue===1) return 'INGRESOS'; else if(cellValue===2) return 'EGRESOS'; else if(cellValue===0) return 'SIN GRUPO'; else if(cellValue===3) return 'PROVISIONES'; else return 'TOTALES'; }},
                { label: 'Tipo', name: 'Cam_Tip', width: 20,hidden:true},
                { label: 'Orden', name: 'Cam_Ord', width: 20, align:"center", sorttype: 'number'},
                { label: 'Nombre', name: 'Cam_Des', width: 100},
                { label: 'Req.', name: 'Cam_Req', width: 20, align: 'center',viewable: false, formatter: 'checkbox',resizable:false, hidden:false,cellattr: function (v,c,d) { if(d['Cam_Req']==='S') return ' title="Requerido" '; }},
                { label: 'Vis.', name: 'Cam_Vis', width: 20, align: 'center',viewable: false, formatter: 'checkbox',resizable:false, hidden:false,cellattr: function (v,c,d) { if(d['Cam_Vis']==='S') return ' title="Visible" '; }},                
                { label: 'Cal.', name: 'Cam_Cal', width: 20, align: 'center',viewable: false, formatter: 'checkbox',resizable:false, hidden:false,cellattr: function (v,c,d) { if(d['Cam_Cal']==='S') return ' title="Calculado" '; }},
                { label: 'Sum.', name: 'Cam_Sum', width: 20, align: 'center',viewable: false, formatter: 'checkbox',resizable:false, hidden:false,cellattr: function (v,c,d) { if(d['Cam_Cal']==='S') return ' title="En Sumatoria" '; }},
                { label: 'Def.', name: 'Cam_Def', width: 20,hidden:true },
                { label: 'Formula', name: 'Cam_Forrr', width:0,hidden:true},
                { label: 'Valor', name: 'Cam_Por', width: 50, align:"right",editable:true,editoptions:{dataInit:styleInput},title:false},
                { label: '&nbsp;', name: 'act1', width: 60, align: 'center',viewable: false,resizable:false,title:false,formatter: function (cellValue, options, rowObject) { 
                        if(rowObject['Cam_Tip']!=='T'&&rowObject['Cam_Tip']!=='D') 
                            return (rowObject['Cam_Def']!=='S'?$.getGridButton(deleteCam,rowObject.Index,'Quitar Campo','remove',null,'danger')+'&nbsp;'+
                                $.getGridButton(editCam,rowObject.Index,'Editar Campo','pencil'):'')+
                                (rowObject['Cam_Cal']==='S'&&rowObject['Cam_Def']!=='S'?'&nbsp;'+$.getGridButton(editFormula,rowObject.Index,'Editar Formula','link'):''); 
                        else{ 
                            if( (rowObject['Cam_Tip']==='T' &&  rowObject['Cam_Ord']!=='3') || (rowObject['Cam_Tip']==='D' && rowObject['Cam_Var']==='aporte_extras_rol_p') ){
                                return $.getGridButton(editFormula,rowObject.Index,'Editar Formula','link');                                  
                            }                            
                        } return '';
                    } 
                }
            ],
            grouping:true, 
            groupingView : { 
            groupColumnShow:[false],
            groupField : ['Cam_Grp'],
            //groupOrder : ['asc'], 
            groupDataSorted : true 
            } 
        },true); 
        $('#Cam_Forrr').createFlyout({icon:'alert'});
        $('#Cam_Vis').on('change',function (){
            $('#Cam_Sum').prop('checked',$(this).prop('checked'));
            $('#Cam_Sum').attr('disabled',$(this).prop('checked')?null:'disabled');
        });
        $('#Cam_Req').on('change',function (){
            if($(this).prop('checked')){                
                $('#Cam_Vis').prop('checked',true).attr('disabled','disabled').trigger('change');
                $('#Cam_Cal').prop('checked',false).attr('disabled','disabled').trigger('change');
            }else{
                $('#Cam_Vis').removeAttr('disabled');
                $('#Cam_Cal').removeAttr('disabled').trigger('change');
            }
        });
        $('#Cam_Cal').on('change',function (){
            if($(this).prop('checked')){                
                $('.constante').setData().hide();
                $('#Cam_Por').removeAttr('required');
                $('.calculado').setData().show();
            }else{
                $('#Cam_Forrr').flyout('hide');
                $('.calculado').setData().hide();
                $('.constante').setData().show();
                $('#Cam_Por').attr('required','required');
            }
        });
        setCamposTip();
        
        // Cargar bases de datos disponibles (solo exa y servicios)
        $.getDataJson('', {loadBasesDatos: true}, function(response){
            if(response.success && response.bases){
                var $select = $('#selBaseDatos');
                $select.empty().append('<option value="">Seleccione una base de datos...</option>');
                $.each(response.bases, function(i, base){
                    $select.append('<option value="' + base.Dat_Dis + '">' + base.Emp_Nom + '</option>');
                });
            }
        });
        
        // Inicializar Select2 para el selector de base de datos
        $('#selBaseDatos').select2({
            placeholder: 'Seleccione una base de datos...',
            allowClear: true
        });
        
        // Cuando se selecciona una base de datos, cargar sus plantillas
        $('#selBaseDatos').on('select2:select', function (e) {
            var Dat_Dis = $(this).val();
            if(Dat_Dis){
                cargarPlantillasBaseDatos(Dat_Dis);
            }
        });
        
        // Cuando se deselecciona, limpiar plantillas
        $('#selBaseDatos').on('select2:clear', function (e) {
            $('#selPlantillaBase').empty().append('<option value="">Primero seleccione una base de datos...</option>').prop('disabled', true).trigger('change');
        });
        
        // Inicializar Select2 para el dropdown de plantillas con búsqueda
        $('#selPlantillaBase').select2({
            placeholder: 'Seleccione una plantilla para cargar como base...',
            allowClear: true,
            language: {
                noResults: function() {
                    return "No se encontraron resultados";
                },
                searching: function() {
                    return "Buscando...";
                }
            },
            matcher: function(params, data) {
                // Si no hay término de búsqueda, mostrar todas las opciones
                if ($.trim(params.term) === '') {
                    return data;
                }
                
                // Normalizar texto para búsqueda (sin acentos, minúsculas)
                var term = params.term.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                var text = data.text.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                var empresa = $(data.element).attr('data-empresa') || '';
                empresa = empresa.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                
                // Buscar en el texto completo (empresa - descripción) o solo en empresa
                if (text.indexOf(term) > -1 || empresa.indexOf(term) > -1) {
                    return data;
                }
                
                return null;
            }
        });
        
        // Manejar el evento change de Select2 para plantillas
        $('#selPlantillaBase').on('select2:select', function (e) {
            var Map_Cod = $(this).val();
            var Dat_Dis = $('#selBaseDatos').val();
            cargarPlantillaBase(Map_Cod, Dat_Dis);
        });
        
        // Función para cargar plantillas de una base de datos
        function cargarPlantillasBaseDatos(Dat_Dis){
            $('#selPlantillaBase').prop('disabled', true);
            $.getDataJson('', {loadPlantillasByDB: true, Dat_Dis: Dat_Dis}, function(response){
                if(response.success && response.plantillas){
                    var $select = $('#selPlantillaBase');
                    $select.empty().append('<option value="">Seleccione una plantilla para cargar como base...</option>');
                    $.each(response.plantillas, function(i, plantilla){
                        var empresa_nombre = plantilla.Emp_Nom || 'Empresa ' + plantilla.Emp_Cod;
                        $select.append('<option value="' + plantilla.Map_Cod + '" data-empresa="' + empresa_nombre.replace(/"/g, '&quot;') + '">' + 
                                    empresa_nombre + ' - ' + plantilla.Map_Des + '</option>');
                    });
                    $select.prop('disabled', false).trigger('change');
                } else {
                    $.alert(response.message || 'Error al cargar las plantillas', null, 'alert');
                    $('#selPlantillaBase').empty().append('<option value="">No se encontraron plantillas</option>').prop('disabled', true);
                }
            }, function(){
                $.alert('Error al cargar las plantillas', null, 'alert');
                $('#selPlantillaBase').empty().append('<option value="">Error al cargar</option>').prop('disabled', true);
            });
        }
    });    
    function styleInput(e,obj,opt){        
        var campo=campos[obj['rowId']];
        //$.each(campos,function(i,v){ if(v['Index']*1===obj['rowId']*1){ campo=v; return false; }});
        if(typeof campo==='undefined'||campo['Cam_Req']==='S'||campo['Cam_Cal']==='S'||campo['Cam_Def']==='S'){ $(e).parent().html(''); return false; }
        e.style.textAlign = 'right';        
        $(e).on('change',function (){
            if(isNaN(this.value)||this.value===''){ $(this).val('0').focus();campos[obj['rowId']]['Cam_Por']=0; return false; }
            else{ campos[obj['rowId']]['Cam_Por']=this.value*1; }
        });
    }
</script>
</BODY>
</HTML>