<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_roles_ing_egr_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Rol;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($rolesAjax)){ 
    $data=$_POST; 
    $responce['rows'] = $obBD_con1->getArrayConsulta(57, $data, $obBD_conexion);
    $responce['records'] = count($responce['rows']);
    $responce['success']=true;
    $obBD_con1->echoJson($responce);    
}

if(isset($mostrarChecksRol)){ 
    $responce['success']=true;
    $data = '';
    $data .='<div class="col-xs-12">
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Ingresos:</label>  
                                    <div class="col-xs-10">
                                        <div class="col-xs-9">';
                                        $rscamps = $obBD_con1->getArrayConsulta(90, $Map_Cod, $obBD_conexion);
                                                foreach ($rscamps as $rowcamps){  
                                                     
                                                    $data.=' <input type="checkbox" checked id="chk_rol_'.$rowcamps['Cam_Var'].'" name="opciones[]" value="'.$rowcamps['Cam_Cod'].'" onclick="getDataGridFilterAlt(1)">'.$rowcamps['Cam_Dec'];
                                                     
                                                }
                                    $data.='
                                        </div> 
                                    </div>
                                    <label class="col-xs-2 control-label label-xs">Egresos:</label>  
                                    <div class="col-xs-10">
                                        <div class="col-xs-9"> ';
                                        $rscamps = $obBD_con1->getArrayConsulta(91, $Map_Cod, $obBD_conexion);
                                                foreach ($rscamps as $rowcamps){  
                                                     
                                                    $data.=' <input type="checkbox" checked id="chk_rol_'.$rowcamps['Cam_Var'].'" name="opciones[]" value="'.$rowcamps['Cam_Cod'].'" onclick="getDataGridFilterAlt(1)">'.$rowcamps['Cam_Dec'];
                                                     
                                                }
                                    $data.='
                                            
                                        </div> 
                                    </div>
                                </div>
                            </div>
                        </div>';
        $responce['value']= $data;
        $obBD_con1->echoJson($responce);    
 //                       echo 
}

if(isset($getDatoRol)){ 
    $responce['success']=true;
    //$Rol_cod = $_POST['Rol_cod'];

    // Obtiene los datos del array de consulta
    $rscamps = $obBD_con1->getArrayConsulta(92, $Rol_cod, $obBD_conexion);
    $jsonData = json_encode($rscamps[0]);
    $responce['value']= $jsonData;
    $obBD_con1->echoJson($responce);
}

if(isset($getDefaults)){ 
    $obBD_con1->getRolDefaults($_GET, $obBD_conexion);      
}
if(isset($printAjax)){   
    $datos=$_GET;
    $rol_pago=$obBD_con1->getRowConsulta(16,$datos, $obBD_conexion);
    $grid=$obBD_con1->getGridRol($rol_pago['Map_Cod'],$obBD_conexion,false);
    $obBD_con1->utf8_change_param($grid);    
    $roles = $obBD_con1->getListRoles($datos, $obBD_conexion);
    $obBD_con1->utf8_change_param($roles);
    
    $t=array('{Emp_Nom}'=>$Ses_Emp_Nom,'{Rol_Con}'=>$rol_pago['Rol_Con'],'{Rol_Range}'=>"Desde $rol_pago[Rol_Fei] Hasta $rol_pago[Rol_Fef]",'{ingHeader}'=>'','{egrHeader}'=>'','{ingSpan}'=>0,'{egrSpan}'=>0,'{data}'=>'','{rol_border}'=>'border:'.(isset($print)?'1px solid gray;':'0.1pt solid black;'),'{Rol_Campos_Ingreso}'=>'','{Rol_Campos_Egreso}'=>'');
    $aux=array('{Rol_i}'=>'', '{Prs_Ced}'=>'', '{Prs_Ape}'=>'', '{Prs_Nom}'=>'', '{dias}'=>'', '{Tic_Des}'=>'');
    $filas='<tr>	
            <td style="{rol_border} " align="center">{Rol_i}</td>
            <td style="{rol_border} mso-number-format:&#39;@&#39;;">{Prs_Ced}</td>
            <td style="{rol_border} ">{Prs_Ape}</td>
            <td style="{rol_border} ">{Prs_Nom}</td>            
            <td style="{rol_border} ">{Tic_Des}</td>
            <td style="{rol_border} " align="center">{dias}</td>
            {Rol_Campos_Ingreso}
            <td style="{rol_border} " align="right">{total_ingr}</td>
            {Rol_Campos_Egreso}
            <td style="{rol_border} " align="right">{total_egr}</td>
            <td style="{rol_border} " align="right">{total_rol}</td>
            <td style="{rol_border} " align="right" height="40" width="100"></td>
        </tr>';
    foreach($grid['rol'] as $f){
        if(($f['Cam_Tip']==='I')&&$f['Cam_Vis']==='S'){ $t['{ingHeader}'].="<td style='{rol_border} '>$f[Cam_Dec]</td>"; $t['{ingSpan}']++; $t['{Rol_Campos_Ingreso}'].='<td style="{rol_border} " align="right">{'.$f['Cam_Var'].'}</td>'; }
        if(($f['Cam_Tip']==='E')&&$f['Cam_Vis']==='S'){ $t['{egrHeader}'].="<td style='{rol_border} '>$f[Cam_Dec]</td>"; $t['{egrSpan}']++; $t['{Rol_Campos_Egreso}'].='<td style="{rol_border} " align="right">{'.$f['Cam_Var'].'}</td>';  }
        if($f['Cam_Vis']==='S') $aux['{'.$f['Cam_Var'].'}']=0;
    }
    $f=reporteArray($t,$filas);
    
    foreach($roles as $r){
        $t['{data}'].=reporteArray($r,$f);
        foreach($grid['rol'] as $fd) if($fd['Cam_Vis']==='S') $aux['{'.$fd['Cam_Var'].'}']+=($r['{'.$fd['Cam_Var'].'}']*1);
    }
    foreach($grid['rol'] as $fd) if($fd['Cam_Vis']==='S') $aux['{'.$fd['Cam_Var'].'}']=formato_numero($aux['{'.$fd['Cam_Var'].'}'], 2, 1);
    $aux['{dias}']='';
    $t['{data}'].=reporteArray($aux,$f);
   //var_dump($rol['roles']);
    $t['{maxSpan}']=$t['{ingSpan}']+$t['{egrSpan}']+10;
    $t['{header_empresa}']=$obBD_con1->getReportHeader($Ses_Suc_Cod,'ROL DE PAGOS',"Desde $rol_pago[Rol_Fei] Hasta $rol_pago[Rol_Fef]",$obBD_conexion,false,$t['{maxSpan}'],isset($print),true);
    $responce['tabla']=reporteHtml($t,'rhu_pri_rol_pago.html');   
    //var_dump($grid['rol']);
    $responce['success']=true;
    if(!isset($echo))
        $obBD_con1->echoJson($responce);
    else{       
        echo $responce['tabla']; exit();
    }
}
if(isset($printRolIndAjax)){    
    $datos=$_GET;    
    $rol_pago=$obBD_con1->getRowConsulta(16,$datos, $obBD_conexion);
    $grid=$obBD_con1->getGridRol($rol_pago['Map_Cod'],$obBD_conexion,false);
    $empresa=$obBD_con1->getRowConsulta('empresas.selectWhere',array('where'=>"empresas.Emp_Cod=$Ses_Emp_Cod"), $obBD_conexion);
    $obBD_con1->utf8_change_param($grid);
    $t=array('{representante}'=>$empresa['Emp_Rep'],'{contador}'=>$empresa['Emp_Con'],'{Emp_Nom}'=>$Ses_Emp_Nom,'{Rol_Con}'=>$rol_pago['Rol_Con'],'{Rol_Range}'=>"Desde $rol_pago[Rol_Fei] Hasta $rol_pago[Rol_Fef]",'{Rol_Type}'=>'Rol '.($rol_pago['Rol_Tip']=='M'?'Mensual':($rol_pago['Rol_Tip']=='Q'?'Quincenal':($rol_pago['Rol_Tip']=='BS'?'BiSemanal':'Semanal'))),'{data}'=>'','{efectivo}'=>'','{cheque}'=>'','{otros}'=>'');    
    $filas=array('ingreso'=>array(),'egreso'=>array());
    $obBD_con1->utf8_change_param($filas);
    foreach($grid['rol'] as $f){
        if(($f['Cam_Tip']==='I')&&$f['Cam_Vis']==='S'){ array_push($filas['ingreso'],$f); }
        if(($f['Cam_Tip']==='E')&&$f['Cam_Vis']==='S'){ array_push($filas['egreso'],$f); }
    }
    $max=(count($filas['ingreso'])>count($filas['egreso'])?count($filas['ingreso']):count($filas['egreso']));
    $html='';
    for($i=0;$i<$max;$i++){        
        if(isset($filas['ingreso'][$i])){ $html.='<tr><td colspan="3">&nbsp;'.$filas['ingreso'][$i]['Cam_Des'].'</td><td align="right" data-formatcode="0.00">{'.$filas['ingreso'][$i]['Cam_Var'].'}</td>'; }else{ $html.='<tr><td colspan="4"></td>'; }
        if(isset($filas['egreso'][$i])){ $html.='<td colspan="3">&nbsp;'.$filas['egreso'][$i]['Cam_Des'].'</td><td align="right" data-formatcode="0.00">{'.$filas['egreso'][$i]['Cam_Var'].'}</td></tr>';  }else{ $html.='<td colspan="4"></td><td colspan="2"></td></tr>'; }
    } 
    $fil_plan=array('{filas}'=>$html,'{header_empresa}'=>'','{header_excel}'=>'');
    if(isset($print))
        $fil_plan['{header_empresa}']=$obBD_con1->getReportHeader($Ses_Suc_Cod,'ROL DE PAGOS',"Desde $rol_pago[Rol_Fei] Hasta $rol_pago[Rol_Fef]",$obBD_conexion,false,10,isset($print),true);
    else
        $fil_plan['{header_excel}']='<tr><td colspan="10" align="center" style=" font-weight: bold;font-size:16px;">{Emp_Nom}</td></tr>
        <tr><td colspan="10" align="center" style="font-weight: bold;font-size:14px;">{Rol_Type}</td></tr>
        <tr><td colspan="10" align="center" style="font-weight: bold;font-size:12px;">{Rol_Range}</td></tr>
        <tr><td colspan="10"></td></tr>';
    $plantilla=reporteHtml($fil_plan,'rhu_pri_rol_ind.html');
    
    $roles = $obBD_con1->getListRoles($datos, $obBD_conexion);
    $obBD_con1->utf8_change_param($roles);
    
    $responce['tabla']='<style> @media all { div.saltopagina{ display: none; } } @media print{ div.saltopagina{ display:block; page-break-before:always; } } </style>';
    $long=count($roles);
    foreach($roles as $i => $r){ 
        $abonos=$obBD_con1->getArrayConsulta('det_an_rol.selectWhere',array('clean'=>true, 'where'=>array('Con_Cod'=>$r['Con_Cod'],'Rol_Cod'=>$Rol_Cod,'Ant_Tip'=>'B'), 'join'=>array('antici_rol'=>array('on'=>'det_an_rol.Ant_Cod=antici_rol.Ant_Cod', 'cols'=>array()))), $obBD_conexion);
        //$obBD_con1->echoLog($r['Con_Cod']);
        //$obBD_con1->echoLog($abonos);
        if(count($abonos)>0)foreach ($abonos as $ab) {
            switch ($ab['Pag_Cod']) {
                case 1: $r['{efectivo}']='X'; break;
                case 3: $r['{cheque}']='X'; break;
                default: $r['{otros}']='X'; break;
            }
        }
        $r['{total_letras}']=num2letras($r['{total_rol}']).' DOLARES AMERICANOS';        
        $responce['tabla'].='<table style="width:700px;font-size:11px;table-layout:fixed;border-collapse:collapse" cellpadding="2">'.reporteArray(array_merge($t,$r),$plantilla).'</table>'.(($i+1)!=$long?'<div class="saltopagina"></div>':'');
    }    
    
    $responce['success']=true;
    if(!isset($echo))
        $obBD_con1->echoJson($responce);
    else{       
        echo $responce['tabla']; exit();
    }
}
if(isset($rolesAjax)){ 
    $data=$_GET;       
    $responce['rows'] = $obBD_con1->getArrayConsulta(16, $data, $obBD_conexion);
     foreach ($responce['rows'] as &$v) {
        if($v['Usu_Cod']!=null){
            $usua=$obBD_con1->getRowConsulta(48, $v['Usu_Cod'], $obBD_conexion);
            $v['Usuario']=$usua['Usuario'];
        }
    } unset($v);
    $responce['records'] = count($responce['rows']);
    $responce['success']=true;
    $obBD_con1->echoJson($responce);    
}

if(isset($getRolDetailFilter)){          
    $responce = $obBD_con1->getGridRolFilterAlt($Rubros, $Map_Cod,$obBD_conexion,true);
    $rol_pago=$obBD_con1->getRowConsulta(16,array('Rol_Cod'=>$Rol_Cod), $obBD_conexion);     
    $responce['Rol_Cod']=$Rol_Cod;
    $responce['personal'] = $obBD_con1->getListRolesFilter($Rubros, array('Rol_Cod'=>$Rol_Cod), $obBD_conexion,false);
    $responce['grid']['caption']=$rol_pago['Rol_Con'];
    array_push($responce['grid']['colModel'],array('label'=>'&nbsp;','name'=>'act1','width'=>40,'align'=>'center','viewable'=>false,'title'=>false,'formatter'=>'printRolIndFormater'));    
    array_push($responce['grid']['colModel'],array('label'=>'&nbsp;','name'=>'act2','width'=>40,'align'=>'center','viewable'=>false,'title'=>false,'formatter'=>'descargarRolIndFormater'));    
    $responce['success']=true;
    $responce['edit']=false; //unset($responce['rol']);
    $obBD_con1->echoJson($responce); 
}


if(isset($getRolDetail)){          
    $responce = $obBD_con1->getGridRol($Map_Cod,$obBD_conexion,false);
    $rol_pago=$obBD_con1->getRowConsulta(16,array('Rol_Cod'=>$Rol_Cod), $obBD_conexion);     
    $responce['Rol_Cod']=$Rol_Cod;
    $responce['personal'] = $obBD_con1->getListRoles(array('Rol_Cod'=>$Rol_Cod), $obBD_conexion,false);
    $responce['grid']['caption']=$rol_pago['Rol_Con'];
    array_push($responce['grid']['colModel'],array('label'=>'&nbsp;','name'=>'act1','width'=>40,'align'=>'center','viewable'=>false,'title'=>false,'formatter'=>'printRolIndFormater'));    
    array_push($responce['grid']['colModel'],array('label'=>'&nbsp;','name'=>'act2','width'=>40,'align'=>'center','viewable'=>false,'title'=>false,'formatter'=>'descargarRolIndFormater'));    
    $responce['success']=true;
    $responce['edit']=false; //unset($responce['rol']);
    $obBD_con1->echoJson($responce); 
}

if(isset($cargarReportes)){
    try {
        $response['reportes'] = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        $response['success']=true;
    } catch (Exception $ex) {
        $response['message']=$ex->getMessage();
    }
    $obBD_con1->echoJson($response);

}  
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Rrhh Ingresos Egresos [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?> 
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_roles.js?x=500"></script>
        <style></style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestión de Roles</h3></div>

        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="main-search">
              <div class="row">    
              </div>
            </div>  
            
            <div id="rol-sdetail" style="display: block;">
              <div class="row">  
              <form id="formSearchRol" action="javascript:searchRoles();">
                    <div class="col-xs-3">  
                        <fieldset class="exa-fieldset ">            
<!--<a onclick="pruebaScript(1)">PRUEBA script</a>
<div id="divPrueba"></div>-->
                            <legend class="Titulos2">Plantilla Rol</legend>
                            <div class="form-horizontal normal">                                 
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Area:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="Are_Cod" name="Are_Cod" class="form-control input-xs" >
                                            <option value="">TODAS</option>
                                            <?php $rs_area = $obBD_con1->getArrayConsulta(11,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_area as $row){  
                                                     ?><option value="<?php echo $row['Are_Cod']; ?>"><?php echo $row['Are_Des']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>                                  
                                </div> 
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Plantilla:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="Map_Cod" name="Map_Cod" class="form-control input-xs" onchange="getRoles()">
                                            <option value="">Seleccione...</option>
                                            <?php $rs_maps = $obBD_con1->getArrayConsulta(56,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_maps as $row){  
                                                     ?><option value="<?php echo $row['Map_Cod']; ?>"><?php echo $row['Map_Des']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>                                  
                                </div>
                            </div>
                        </fieldset>
                    </div>   
                    <div class="col-xs-7">  
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Datos Generales</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Periodo:</label>  
                                    <div class="col-xs-3"> 
                                        <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" onchange="getRoles()" required="">    
                                            <option value="">Seleccione...</option>
                                            <?php $rs_perio = $obBD_con1->getArrayConsulta(55,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_perio as $row){  
                                                     ?><option value="<?php echo $row['Pec_Cod']; ?>" data-year="<?php echo $row['Periodo']; ?>">Periodo <?php echo $row['Periodo']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>                                     
                                </div>  
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Roles:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="Rol_Cod" name="Rol_Cod" class="form-control input-xs" onchange="" required="">    
                                        <option value="">Seleccione...</option>
                                        </select>
                                    </div>    
                                </div>
                                <!--
                                <div class="form-group date-ranges">
                                    <label class="col-xs-2 control-label label-xs ">Desde:</label>
                                    <div class="col-xs-3">     
                                        <input name="ini" type="text" id="ini" class="form-control input-xs" disabled="" />
                                    </div>
                                    <label class="col-xs-2 control-label label-sm ">Hasta:</label>
                                    <div class="col-xs-3">                                    
                                        <input name="fin" type="text" id="fin" class="form-control input-xs" disabled="" />
                                    </div>                                   
                                </div>
                                -->
                            </div>
                        </fieldset>
                    </div>   
                    
                    <div class="col-xs-2 center vcenter" style="height: 70px;"><a class="btn btn-success" onclick="detallarRoles2()"><i class="glyphicon glyphicon-search"></i> Buscar</a></div>                    
                    
                </form>
                  <div class="col-xs-3 detalle">
                  </div>
                  <div class="col-xs-3 detalle">  
                    </div>   
                  <div class="col-xs-3 detalle">  
                  </div>    
                  <div class="col-xs-3 detalle">
                  </div>
                  <div id="divChecks"></div>
                  <div class="col-xs-12" id="gridContainer" style="padding-bottom: 8px; min-height: 300px;"><table id="rol"></table><div id="rolPager"></div></div>    
                  <div class="col-xs-12">
                      <button type="button" onclick="exportarAlt()" class="btn btn-primary btn-sm start" title="Exportar registros"><i class="glyphicon glyphicon-download-alt"></i> <span>Exportar</s-printpan></button>
                      <!--<button class="btn btn-sm btn-inverse" onclick="$('#rol-sdetail').moveComp('#main-search').updateGridsSizes();" ><i class="glyphicon glyphicon-arrow-left"></i> Atr&aacute;s</button>
                      <button class="btn btn-sm btn-success exportRoles" onclick="printRoles($(this).data('originaldata'))" ><i class="glyphicon glyphicon-print"></i> Rol Grupal</button>
                      <button class="btn btn-sm btn-success exportRoles" onclick="printRolDetailIndiv($(this).data('originaldata'))" ><i class="glyphicon glyphicon-print"></i> Rol Individual</button>
                      <button class="btn btn-sm btn-success exportRoles" onclick="exportRoles($(this).data('originaldata'))" ><i class="glyphicon glyphicon-download"></i> Excel Rol Grupal</button>
                      <button class="btn btn-sm btn-success exportRoles" onclick="exportRolesIndiv($(this).data('originaldata'));" ><i class="glyphicon glyphicon-download"></i> Excel Rol Individual</button>-->
                  </div>    
              </div> 
            </div>    
        </div>
    </div>
    <div id="exportar2" style="display: none;">
        <table id="tablaExporta2" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
    </div>
    <script type="text/javascript">
//        var gridComp=$("#comp"),
//            groupAnioArea={
//                    groupField: ["Anio","Are_Des"], groupColumnShow: [false,false],
//                    groupText: ["<div><span style='float:left;'><b> &nbsp;-&nbsp; Periodo {0} &nbsp;-&nbsp; </b></span><span style='float:right;'> {1} Area(s)</span></div>","<div><span style='float:left;'> <b> &nbsp;&nbsp;Area: {0} &nbsp;&nbsp; </b> </span><span style='float:right;'> {1} Rol(es)</span></div>"],
//                    groupOrder: ["asc","asc"], groupSummary: [false], groupCollapse: false
//                },
//            groupAnio={
//                    groupField: ["Anio"], groupColumnShow: [false],
//                    groupText: ["<div><span style='float:left;'><b> &nbsp;-&nbsp; Periodo {0} &nbsp;-&nbsp; </b></span><span style='float:right;'> {1} Area(s)</span></div>"],
//                    groupOrder: ["asc"], groupSummary: [false], groupCollapse: false
//                },
//            groupArea={
//                    groupField: ["Are_Des"], groupColumnShow: [false],
//                    groupText: ["<div><span style='float:left;'> <b> &nbsp;&nbsp;Area: {0} &nbsp;&nbsp; </b> </span><span style='float:right;'> {1} Rol(es)</span></div>"],
//                    groupOrder: ["asc"], groupSummary: [false], groupCollapse: false
//                };  
//        
//        function searchRoles(){
//            $.getDataJson(gridComp,$('#formRol').getData('rolesAjax'),function (r){               
//                var area=$('#Are_Cod').val(),periodo=$('#Pec_Cod').val();
//                if(area!==''&&periodo!=='ALL'&&periodo!=='RANGE')
//                    gridComp.jqGrid('groupingRemove', true);
//                else if(area===''&&periodo!=='ALL'&&periodo!=='RANGE')
//                    gridComp.jqGrid('groupingGroupBy','Are_Des',groupArea);
//                else if(area!==''&&(periodo==='ALL'||periodo==='RANGE'))
//                    gridComp.jqGrid('groupingGroupBy','Anio',groupAnio);
//                else
//                    gridComp.jqGrid('groupingGroupBy',['Anio','Are_Des'],groupAnioArea);
//                gridComp.setRows(r['rows']).jqGrid('setCaption','ROLES '+(periodo==='RANGE'?' - DESDE '+$('#ini').val()+' HASTA '+$('#fin').val():(periodo!=='ALL'?' - PERIODO: '+$('#Pec_Cod option:selected').data('year'):''))+(area!==''?' - AREA: '+$('#Are_Cod option:selected').text():''));
//            });
//        };
//        function detallarRoles(data){ 
//            $('.exportRoles').attr('data-originaldata',$.jsonParser(data)); 
//            data['Anio']='Periodo '+data['Anio'];
//            $('.detalle').setData(data);            
//            $.getDataJson( "",$.extend(data,{getRolDetail:true}), function( response ) {
//                if($('#rol')[0].grid) {$.jgrid.gridUnload('#rol'); } $grid=$("#rol").createGrid($.extend({height:300},response['grid']),true,'#rolPager',{view:false}).setGroupHeaders(response['header']);
//                $('#rol').setRows(response['personal']);
//                $('#main-search').moveComp('#rol-sdetail').updateGridsSizes();
//            }); 
//        }
    </script>
    <script type="text/javascript">        
      $(document).ready(function() {
            createSearchGrid([
                { label: '&nbsp;', name: 'act2', width: 15, align: 'center',formatter:'gridButton', formatoptions:{ action:ImpCom, title:'Imprimir Comprobante', icon:'print', type:'info' }, title:false },
                { label:'&nbsp;', name: 'act1', width: 60, align: 'center',viewable: false,title: false,
                    formatter:function (cv, opt, rObj) { 
                        if(rObj.Rol_Est==='I') 
                            return $.createIcon('remove red',false,'title="Inactivo/Anulado!"');
                        return $.getGridButton(printRoles,{Rol_Cod:rObj.Rol_Cod,Map_Cod:rObj.Map_Cod},'Imprimir Roles','print',null,'info')+'&nbsp;'+
                               $.getGridButton(exportRoles,{Rol_Cod:rObj.Rol_Cod,Map_Cod:rObj.Map_Cod},'Descargar Excel','download',null,'info')+'&nbsp;'+
                               $.getGridButton(detallarRoles,rObj)+'&nbsp;';
                    }
                }
            ]);
//            $.createDateRange('#ini','#fin');
//            $('#Pec_Cod').on('change',function(){ if($(this).val()==='RANGE'){ $('.date-ranges :input').removeAttr('disabled','disabled'); }else{ $('.date-ranges :input').attr('disabled','disabled'); }  });
      });  

      function ImpCom(rObj){
    $.getDataJson('',{'cargarReportes':true},function(res){
        var reportes=res['reportes'];
        console.log(rObj);
        $.varValid(reportes[2])?$.imprimirUrl(reportes[2]+'?codigo='+rObj.Com_Cod):$.alert('Sin Reportes Asociados');
    },function(err){
        console.log(err['message']);
    });
}

function exportarAlt(){
                        $('#tablaExporta2').html($('#rol').jqGrid('exportGridInnerHTML',{footer:true,bodyBorder:false,removeHiddens:true,removeCols:[1]}));
                        $.downloadFile($.exportarExcelBlob($('#exportar2').html(), 'Roles'), 'roles_' + $.getDate() + '.xls');
                    } 
   </script>
   <div id="imprimirRoles" style="display: none;width: 1200px;"></div>
   <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
   <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_reportes.js?x=500"></script>
   <script type="text/ecmascript" src="../../Librerias/scripts/generales/xmljs.js"></script>
   <div id="proviDetaDialog" title="Provisiones"></div>
</BODY>
</HTML>