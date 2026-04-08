<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("rhu_sql_ing_egr.php");
$extra_fields=array('anticipos_rol_p'=>array('table_rel'=>'det_an_rol','id_field'=>'Ant_Cod','array_val'=>'Ant_Val'),'descuentos_rol_p'=>array('table_rel'=>'det_an_rol','id_field'=>'Ant_Cod','array_val'=>'Ant_Val'),'prestamos_rol_p'=>'Pre_Val');

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_rrhh_ing_egr extends MysqlConexion{

}//Fin de clase Class_Log_Conexion_rrhh_ing_egr


/*  Clase para los datos a la capa de acceso a datos  */
class Class_Log_Datos_rrhh_ing_egr extends MysqlDatos{
	
    function __construct() {
        $this->setSentencias('sentencias_rrhh_ing_egr');
    }

    //Funciones
    function getRolArea($data,$Rol_Fei,$obBD_conexion){
        $defaults = $this->getRolDefaults(NULL, $Rol_Fei, NULL, $obBD_conexion, true);
        $response=array( 'success'=>true, 'rows'=>$this->getArrayConsulta(9, $data, $obBD_conexion), 'defaults'=>$defaults );   
        foreach ($response['rows'] as &$v) {
            $v=array_merge($v,$response['defaults']);
            $v['Prs_Ced']=substr($v['Prs_Ced'], 0, 10);
            $v['tiempo_parcial']=($v['Ded_Hrs']*1==0)?'S':'N';
            $v['medio_tiempo']=($v['Ded_Hrs']*1==4)?'S':'N';
            if(!empty($v['Afi_Fei'])){
            }
        } unset($v);
        $this->echoJson($response);
    }
    
    function reportes($pagina, $empresa, $obBD_conexion){
        $pag=explode("/",$pagina);
        $Pcs_Nom=str_replace("_mod_", "_alt_", $pag[count($pag)-1]);
        $row_rs_proceso= $this->getRowConsultaSql("SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom LIKE '$Pcs_Nom' ORDER BY Pcs_Nom DESC LIMIT 1;", $obBD_conexion);

    $row_rs_reporte= $this->getArrayConsultaSql("SELECT reportes.Rep_Cod, procesos.Pcs_Nom, reportes.Rep_Ord, rutas.Rut_Des FROM procesos
                                    INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req)
                                    INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod) 
                                    WHERE reportes.Pcs_Cod = $row_rs_proceso[Pcs_Cod] AND reportes.Emp_Cod = $empresa ORDER BY reportes.Rep_Ord", $obBD_conexion);

        $i=0;$reporte=array();
        foreach ($row_rs_reporte as $row)
        {
            $reporte[$row['Rep_Ord']] = $row['Rut_Des'].$row['Pcs_Nom'];
        }
        return $reporte;
    }

    function getRolDefaults($data,$obBD_conexion,$justDef=false, $debug=false){
        //ChromePhp::log($data);
        $Ses_Emp_Cod=$_SESSION['Ses_Emp_Cod'];
        $Rol_Fei=(isset($data)?$data['Rol_Fei']:'');
        $Rol_Fef=(isset($data)?$data['Rol_Fef']:'');
        $response=array( 'success'=>true, 'defaults'=>array(), 'anticipos_rol_p'=>array(), 'descuentos_rol_p'=>array(), 'prestamos_rol_p'=>array() );     
        if(empty($Rol_Fei)) $this->echoJson($response);  
        
        /* valores default */
        $defaults=$this->getArrayConsulta(30, $Rol_Fei, $obBD_conexion,$debug);
        foreach ($defaults as $d){ $response['defaults'][$d['Rde_Var']]=$d['Rde_Val']; }
        $response['defaults']['sueldo_bas_medio']=''.(($response['defaults']['sueldo_bas']*1)/2);        
        if($justDef==true) return $response['defaults'];
        
        /* valores default */
        $sueldosSector=$this->getArrayConsulta(47, $Rol_Fei, $obBD_conexion,$debug);
        
        $response['personal']=$this->getArrayConsulta(9,$data, $obBD_conexion,$debug);

        //for each por cada numero de cedula para obtener labores_total, labores_ingreso, labores_egreso
        $labores=$this->getRowConsulta(54, array('Plantilla'=>$data['Map_Cod']), $obBD_conexion);
        if($labores['labores'] == '1'){
            foreach ($response['personal'] as &$persona) {
               $dataSemanal=array('Semana'=>$data['Rol_Num'],'Periodo'=>$data['Pec_Cod'] ,'Personal'=>$persona['Per_Cod']);
               $totalSemanal=$this->getRowConsulta(53, $dataSemanal, $obBD_conexion);
               $persona['labores_total'] = $totalSemanal['totalSemanal'];
            }
        }
            
        
        foreach($response['personal'] As &$pers){
            if(strlen($pers['Prs_Ced'])>10) $pers['Prs_Ced']= substr ($pers['Prs_Ced'], 0, 10);
            if($pers['Sue_Bas']=='S') 
                $pers['sueldo']=$response['defaults']['sueldo_bas'];
            else
            if(isset($pers['Sut_Cod'])&&$pers['Sut_Cod']!=null&&!empty($pers['Sut_Cod'])){
                foreach ($sueldosSector AS $sec)
                    if($sec['Sut_Cod']==$pers['Sut_Cod']){
                        $pers['sueldo']=$sec['Suu_Val'];
                        break;
                    }
            }
            $pers['aporte_extras_rol_p']=0;
        } unset($pers);

        /* anticipos descuentos */    
        $anticipos= $this->getArrayConsulta(31, $Ses_Emp_Cod.'*'.$Rol_Fei.'*'.$Rol_Fef.'*', $obBD_conexion,$debug);
        $descuentos=$this->getArrayConsulta(31, $Ses_Emp_Cod.'*'.$Rol_Fei.'*'.$Rol_Fef.'**'.'D', $obBD_conexion,$debug);
        if(!empty($anticipos)) $response['anticipos_rol_p']=$anticipos;   
        if(!empty($descuentos)) $response['descuentos_rol_p']=$descuentos;
        $this->echoJson($response);   
    }
    function saveFormula($Cam_Cod,$g,$f,$c,$ord,$obBD_conexion){
        //var_dump($f);
        $this->operacionobBD(3,array(
            'Ogr_Opr'=>$f['operator'],
            'Ogr_Rec'=>(empty($g)?'NULL':$g),
            'Cam_Cod'=>(empty($Cam_Cod)?'NULL':$Cam_Cod),
            'Ogr_Ord'=>(empty($ord)?'NULL':"'$ord'")
        ),$obBD_conexion); 
        $newG=$this->insercionid($obBD_conexion->conexion);
        
        if(isset($f['operand1']['operator']))
            $this->saveFormula(null,$newG,$f['operand1'],$c,'1',$obBD_conexion);
        else
            $this->saveItem($newG,$f['operand1'],$c,'1',$obBD_conexion);
        if(isset($f['operand2']['operator']))
            $this->saveFormula(null,$newG,$f['operand2'],$c,'2',$obBD_conexion);
        else
            $this->saveItem($newG,$f['operand2'],$c,'2',$obBD_conexion);
        return $newG;
    }
    function saveItem($g,$item,$campos,$ord,$obBD_conexion){
        $it=null;
        if($item['type']==='i')
        foreach ($campos as $c)
            if($c['Cam_Var']==$item['variable'])
                {$it=$c;break;}
        $this->operacionobBD(4,array(
            'Ogr_Cod'=>$g,
            'Cam_Cod'=>$it['Cam_Cod'],
            'Oit_Tip'=>$item['type'],
            'Oit_Val'=>$item['value'],
            'Oit_Var'=>isset($item['variable'])?$item['variable']:'',
            'Oit_Ord'=>(empty($ord)?'NULL':"'$ord'")
        ),$obBD_conexion); 
    }
    function getFormula($Cam_Cod,$Ogr_Cod,$ord,$obBD_conexion){
        $data=array('Cam_Cod'=>$Cam_Cod,'Ogr_Cod'=>$Ogr_Cod,'Ogr_Ord'=>$ord);
        $group=$this->getRowConsulta(5, $data, $obBD_conexion);
        if(empty($group)) return null;
        $formula=array('operator'=>$group['Ogr_Opr']);
        $formula['operand1']=$this->getItem($group['Ogr_Cod'],'1',$obBD_conexion);
        $formula['operand2']=$this->getItem($group['Ogr_Cod'],'2',$obBD_conexion);
        return $formula;
    }
    function getItem($Ogr_Cod,$ord,$obBD_conexion){
         $item=$this->getRowConsulta(6,array('Ogr_Cod'=>$Ogr_Cod,'Oit_Ord'=>$ord), $obBD_conexion);
         if(!empty($item)) return array(
             'type'=>($item['Oit_Tip']=='i'?'item':'unit'),
             'value'=>($item['Oit_Tip']=='i'?empty($item['Oit_Val'])?0:$item['Oit_Val']:$item['Oit_Val']),
             'text'=>($item['Oit_Tip']=='i'?"\{$item[Oit_Var]\}":null),
             'variable'=>($item['Oit_Tip']=='i'?$item['Oit_Var']:null)
         ); else return $this->getFormula(null,$Ogr_Cod,$ord,$obBD_conexion);
    }
//    function calcFormula($data,$formula){        
//       $val1=$this->calcFormulaType($data,$formula['operand1'],'1');
//       $val2=$this->calcFormulaType($data,$formula['operand2'],'2');
//       return $this->calcMath($formula['operator'],$val1,$val2);
//    }
//    function calcFormulaType($data,$item,$ord){
//        if(!empty($item['operand'.$ord]['operator']))
//              return $this->calcFormula($data,$item);
//        else{
//            if($item['type']=='unit') return $item['operand'.$ord]['value'];
//            else{
//                $val=0;
//                foreach ($data as $key => $value) if($key==$item['variable']){ $val=$value; break; } 
//                return $val;    
//            }
//        }           
//    } 
//    function calcMath($oper,$val1,$val2){
//        switch ($oper){
//            case '+': return $val1+$val2; 
//            case '-': return $val1-$val2;
//            case '*': return $val1*$val2;
//            case '/': return $val1/$val2;
//        }
//    }
    function getGridRol($Map_Cod=NULL,$obBD_conexion=NULL,$formulas=true){ 
        try{ 
            $response['success']=true; 
            $response['Map_Cod']=$Map_Cod; 
            // defaults PERSONAL
            $campos=array();
            array_push($campos,array('label'=>'Prs.Cod.','name'=>'Prs_Cod','hidden'=>true,'width'=>0));
            array_push($campos,array('label'=>'Per.Cod.','name'=>'Per_Cod','hidden'=>true,'width'=>0));
            array_push($campos,array('label'=>'Con.Cod.','name'=>'Con_Cod','key'=>true,'hidden'=>true,'width'=>0));
            array_push($campos,array('label'=>'C&Eacute;DULA','name'=>'Prs_Ced','width'=>90,'align'=>'center','classes'=>'bgNoRight bgNoColor'));
            array_push($campos,array('label'=>'APELLIDOS','name'=>'Prs_Ape','width'=>80,'classes'=>'bgNoRight bgNoColor'));
            array_push($campos,array('label'=>'NOMBRES','name'=>'Prs_Nom','width'=>80,'classes'=>'bgNoRight bgNoColor'));
            array_push($campos,array('label'=>'CARGO','name'=>'Tic_Des','width'=>75,'classes'=>'bgNoRight bgNoColor'));
            //array_push($campos,array('label'=>'DIAS',labelLong=>'DIAS LABORADOS','name'=>'Dias','width'=>50,editable=>true,title=>false,'align'=>'right',editoptions=>array(dataInit=>'diasInput')));
            // default INGRESOS            
            $grupos=array(
                'defa'=>array('fields'=>array()),
                'ingr'=>array('head'=>array('numberOfColumns'=>0,'titleText'=>'INGRESOS'.$pruebaParametro,'startColumnName'=>NULL),'fields'=>array()),
                'egr'=>array('head'=>array('numberOfColumns'=>0,'titleText'=>'EGRESOS','startColumnName'=>NULL),'fields'=>array())
            );            
            //array_push($grupos['ingr']['fields'],array('label'=>'SUELDO','name'=>'sueldo','width'=>50,formatter=>'number','align'=>'right'));
            //array_push($grupos['ingr']['fields'],array('label'=>'SUELDO NETO','name'=>'sueldo_neto','width'=>50,formatter=>'number','hidden'=>true,'align'=>'right'));
            //$grupos['ingr']['head']['numberOfColumns']=2;$grupos['ingr']['head']['startColumnName']='sueldo';
            
            $fields=array();$map_rol=array();
            if(!empty($Map_Cod)) {
                $fields=$this->getArrayConsulta(7, array('Map_Cod'=>$Map_Cod), $obBD_conexion);
                $map_rol=$this->getRowConsulta(8, array('Map_Cod'=>$Map_Cod), $obBD_conexion);
            }           
            foreach ($fields as $f) { // coloca los INGRESOS/EGRESOS 
            	/*if ($f['Cam_Cod']==C) 
            		{*/
            		if(!empty($f['Cam_Tip'])&&($f['Cam_Tip']=='I'||$f['Cam_Tip']=='E')) {   
                    array_push($grupos[($f['Cam_Tip']=='I'?'ingr':'egr')]['fields'],$this->createGridField($f));
                    $grupos[($f['Cam_Tip']=='I'?'ingr':'egr')]['head']['numberOfColumns']++;
                    if(empty($grupos[($f['Cam_Tip']=='I'?'ingr':'egr')]['head']['startColumnName'])) $grupos[($f['Cam_Tip']=='I'?'ingr':'egr')]['head']['startColumnName']=$f['Cam_Var'];
                }
            	//}
                
            }    
            foreach ($fields as $f) { // coloca los default y totales
                if($f['Cam_Tip']=='T'||$f['Cam_Tip']=='P') array_push($grupos[($f['Cam_Ord']*1===1&&$f['Cam_Tip']=='T'?'ingr':'egr')]['fields'],$this->createGridField($f));  
                if($f['Cam_Tip']=='D') array_push($grupos['defa']['fields'],$this->createGridField($f)); 
            }               
            $campos0=array_merge($grupos['ingr']['fields'],$grupos['egr']['fields']);
            $campos1=array_merge($grupos['defa']['fields'],$campos0);
            $rol=array_merge($campos,$campos1);
            array_push($rol,array('label'=>'<i class="glyphicon glyphicon-info-sign"></i>','labelLong'=>'Anticipos/Descuentos/Prestamos','name'=>'anticipos_ant','width'=>25,'formatter'=>'gridButtonInfos','align'=>'center','classes'=>'bgNoColor','viewable'=>false));
            if($formulas){ foreach ($campos1 as &$v) { if($v['Cam_Cal']=='S') $v['Cam_For']=$this->getFormula($v['Cam_Cod'],NULL,NULL,$obBD_conexion);  } unset($v); }
            $response['rol_config']=$map_rol;
            $response['rol']=$campos1;
            $response['grid']=array('sortname'=>'Prs_Ape','caption'=>(!empty($Map_Cod)?$map_rol['Map_Des']:null),'headertitles'=>true,'colModel'=>$rol,'footerrow'=>true,'bindKeys'=>false);    
            $response['header']=array('useColSpanStyle'=>true,'groupHeaders'=>array());
            array_push($response['header']['groupHeaders'],array('numberOfColumns'=>2,'titleText'=>'PERSONAL','startColumnName'=>'Prs_Ape'));
            array_push($response['header']['groupHeaders'],$grupos['ingr']['head']);
            array_push($response['header']['groupHeaders'],$grupos['egr']['head']);
            utf8_encode_deep($response); return $response;
        }catch(Exception $e){ return array('success'=>false,'message'=>'No se pudo obtener la plantilla del rol de pagos!','error'=>$e); }    
    }   
    function getGridRolFilter($Rubros = NULL, $Map_Cod=NULL,$obBD_conexion=NULL,$formulas=true){ 
        try{ 
            $response['success']=true; 
            $response['Map_Cod']=$Map_Cod; 
            $response['Rubros']=$Rubros; 
            $cadenaRubros = implode(', ', $Rubros);
            // defaults PERSONAL
            $campos=array();
            array_push($campos,array('label'=>'Prs.Cod.','name'=>'Prs_Cod','hidden'=>true,'width'=>0));
            array_push($campos,array('label'=>'Per.Cod.','name'=>'Per_Cod','hidden'=>true,'width'=>0));
            array_push($campos,array('label'=>'Con.Cod.','name'=>'Con_Cod','key'=>true,'hidden'=>true,'width'=>0));
            array_push($campos,array('label'=>'C&Eacute;DULA','name'=>'Prs_Ced','width'=>90,'align'=>'center','classes'=>'bgNoRight bgNoColor'));
            array_push($campos,array('label'=>'APELLIDOS','name'=>'Prs_Ape','width'=>80,'classes'=>'bgNoRight bgNoColor'));
            array_push($campos,array('label'=>'NOMBRES','name'=>'Prs_Nom','width'=>80,'classes'=>'bgNoRight bgNoColor'));
            array_push($campos,array('label'=>'CARGO','name'=>'Tic_Des','width'=>75,'classes'=>'bgNoRight bgNoColor'));
            //array_push($campos,array('label'=>'DIAS',labelLong=>'DIAS LABORADOS','name'=>'Dias','width'=>50,editable=>true,title=>false,'align'=>'right',editoptions=>array(dataInit=>'diasInput')));
            // default INGRESOS            
            $grupos=array(
                'defa'=>array('fields'=>array()),
                'ingr'=>array('head'=>array('numberOfColumns'=>0,'titleText'=>'INGRESOS','startColumnName'=>NULL),'fields'=>array()),
                'egr'=>array('head'=>array('numberOfColumns'=>0,'titleText'=>'EGRESOS','startColumnName'=>NULL),'fields'=>array())
            );            
            //array_push($grupos['ingr']['fields'],array('label'=>'SUELDO','name'=>'sueldo','width'=>50,formatter=>'number','align'=>'right'));
            //array_push($grupos['ingr']['fields'],array('label'=>'SUELDO NETO','name'=>'sueldo_neto','width'=>50,formatter=>'number','hidden'=>true,'align'=>'right'));
            //$grupos['ingr']['head']['numberOfColumns']=2;$grupos['ingr']['head']['startColumnName']='sueldo';
            
            $fields=array();$map_rol=array();
            if(!empty($Map_Cod)) {
                $fields=$this->getArrayConsulta(7, array('Map_Cod'=>$Map_Cod), $obBD_conexion);
                $map_rol=$this->getRowConsulta(8, array('Map_Cod'=>$Map_Cod), $obBD_conexion);
            }           
          
            foreach ($fields as $f)  // coloca los INGRESOS/EGRESOS 
            {
			    if (!empty($f['Cam_Tip']) && ($f['Cam_Tip'] == 'I' || $f['Cam_Tip'] == 'E')) {
			        $found = false; // Bandera para verificar si se encontró el valor en el ciclo
			        
			        foreach ($Rubros as $valor) {
			            if ($valor == $f['Cam_Cod']) {
			                $found = true;
			                break; // Salir del ciclo si se encuentra el valor
			            }
			        }
			        
			        if ($found) {
			            array_push($grupos[($f['Cam_Tip'] == 'I' ? 'ingr' : 'egr')]['fields'], $this->createGridField($f));
			            $grupos[($f['Cam_Tip'] == 'I' ? 'ingr' : 'egr')]['head']['numberOfColumns']++;
			            if (empty($grupos[($f['Cam_Tip'] == 'I' ? 'ingr' : 'egr')]['head']['startColumnName'])) {
			                $grupos[($f['Cam_Tip'] == 'I' ? 'ingr' : 'egr')]['head']['startColumnName'] = $f['Cam_Var'];
			            }
			        } 
			        /*else {
			            // Código a ejecutar si el valor no se encuentra en el arreglo
			            echo "Error: Valor no encontrado - Cam_Cod: " . $f['Cam_Cod'] . PHP_EOL;
			        }*/
			    }
			}
    
            foreach ($fields as $f) { // coloca los default y totales
                if($f['Cam_Tip']=='T'||$f['Cam_Tip']=='P') array_push($grupos[($f['Cam_Ord']*1===1&&$f['Cam_Tip']=='T'?'ingr':'egr')]['fields'],$this->createGridField($f));  
                if($f['Cam_Tip']=='D') array_push($grupos['defa']['fields'],$this->createGridField($f)); 
            }               
            $campos0=array_merge($grupos['ingr']['fields'],$grupos['egr']['fields']);
            $campos1=array_merge($grupos['defa']['fields'],$campos0);
            $rol=array_merge($campos,$campos1);
            array_push($rol,array('label'=>'<i class="glyphicon glyphicon-info-sign"></i>','labelLong'=>'Anticipos/Descuentos/Prestamos','name'=>'anticipos_ant','width'=>25,'formatter'=>'gridButtonInfos','align'=>'center','classes'=>'bgNoColor','viewable'=>false));
            if($formulas){ foreach ($campos1 as &$v) { if($v['Cam_Cal']=='S') $v['Cam_For']=$this->getFormula($v['Cam_Cod'],NULL,NULL,$obBD_conexion);  } unset($v); }
            $response['rol_config']=$map_rol;
            $response['rol']=$campos1;
            $response['grid']=array('sortname'=>'Prs_Ape','caption'=>(!empty($Map_Cod)?$map_rol['Map_Des']:null),'headertitles'=>true,'colModel'=>$rol,'footerrow'=>true,'bindKeys'=>false);    
            $response['header']=array('useColSpanStyle'=>true,'groupHeaders'=>array());
            array_push($response['header']['groupHeaders'],array('numberOfColumns'=>2,'titleText'=>'PERSONAL','startColumnName'=>'Prs_Ape'));
            array_push($response['header']['groupHeaders'],$grupos['ingr']['head']);
            array_push($response['header']['groupHeaders'],$grupos['egr']['head']);
            utf8_encode_deep($response); return $response;
        }catch(Exception $e){ return array('success'=>false,'message'=>'No se pudo obtener la plantilla del rol de pagos!','error'=>$e); }    
    }   
    function createGridField($f){ //$f['Cam_Vis']='S';
        return array_merge(array(
            'label'=>$f['Cam_Dec'],'labelLong'=>$f['Cam_Des'],'name'=>$f['Cam_Var'],'width'=>($f['Cam_Vis']=='N'?0:50),'hidden'=>($f['Cam_Vis']=='N'?true:false),'editable'=>($f['Cam_Req']=='S'?true:false),'title'=>($f['Cam_Req']=='S'?false:true),'editoptions'=>($f['Cam_Req']=='S'?array('dataInit'=>($f['Cam_Var']!='dias')?'styleInput':'diasInput'):NULL),'formatter'=>$f['Cam_Tip']!='T'?$f['Cam_Var']!='dias'&&!endsWith($f['Cam_Var'],'_acum')&&!endsWith($f['Cam_Var'],'_anio')?($f['Cam_Req']=='S'?'number':'numeric'):'interger':'currency','align'=>'right','classes'=>$f['Cam_Tip']=='D'?'bgNoColor':($f['Cam_Sum']=='N'&&$f['Cam_Tip']!='T'?'bgNoRight bgNoColor':$f['Cam_Tip']=='T'?($f['Cam_Var']=='total_rol'?'columnHighlight3':'columnHighlight1'):''),'summaryRound'=>2, 'summaryRoundType'=>'round',
            'viewable'=>($f['Cam_Vis']=='S'||$f['Cam_Tip']=='P'), 'editrules'=>array( 'edithidden'=> true )
        ),$f);
    }
    function getListRoles($datos=null,$obBD_conexion=null,$print=true,$total=false){ 
        try{ 
            if($print){ $in="{";$fn="}"; }else{ $in='';$fn=''; }
            $resp=array() ;
            if($total) $datos['totales']=true;
            $campos = $this->getArrayConsulta(17,$datos, $obBD_conexion);
            foreach($campos as $c){ $add=true;
                foreach($resp as &$r){
                    if($r['Con_Cod']==$c['Con_Cod']){
                        $r[$in.$c['Cam_Var'].$fn]=$c['Cam_Var']!='dias'?formato_numero($c['Rol_Val'],2,1):formato_numero($c['Rol_Val'],0,1);
                        $add=false; break;
                    }
                } unset($r);       
                if($add==true) array_push($resp,array('Rol_Cod'=>$c['Rol_Cod'],'Con_Cod'=>$c['Con_Cod'],$in.$c['Cam_Var'].$fn=>$c['Cam_Var']!='dias'?formato_numero($c['Rol_Val'],2,1):formato_numero($c['Rol_Val'],0,1)));
            }
            
            foreach($resp as $k=>&$r){
                $contrato=$this->getRowConsulta(9,array('Con_Cod'=>$r['Con_Cod']), $obBD_conexion);   
                $Prs_Abr=explode(' ',$contrato['Prs_Ape']);
                $contrato['Prs_Abr']=$Prs_Abr[0].' '.$contrato['Prs_Nom'][0].'.';
                $Prs_Ape[$k] = $contrato['Prs_Ape'];
                $Prs_Nom[$k] = $contrato['Prs_Nom'];
                
                foreach($contrato as $k=>$v) if(!isset($r[$in.$k.$fn])) $r[$in.$k.$fn]=$v; 
                if(empty($r[$in.'aporte_extras_rol_p'.$fn])) $r[$in.'aporte_extras_rol_p'.$fn]=0;
            } unset($r);
            $ident=(count($resp)>1);
            if($ident) array_multisort($Prs_Ape, SORT_ASC, $Prs_Nom, SORT_ASC, $resp);
            foreach($resp as $k=>&$r){ $r[$in.'Rol_i'.$fn]=$k+1; if($ident)$r[$in.'Prs_Abr'.$fn]=($k+1).'.- '.$r[$in.'Prs_Abr'.$fn]; } unset($r);            
            return (isset($datos['Row'])&&isset($resp[0])?$resp[0]:$resp);
        }catch(Exception $e){ return array('success'=>false,'message'=>'No se pudo obtener los roles de pagos!','error'=>$e); }    
    }
    function getDefaults($Rol_Fei,$Rol_Fef,$obBD_conexion){
        $Ses_Emp_Cod=$_SESSION['Ses_Emp_Cod'];
        $response=array( 'success'=>true, 'defaults'=>array(), 'anticipos_rol_p'=>array(), 'descuentos_rol_p'=>array(), 'prestamos_rol_p'=>array() );     
        if(empty($Rol_Fei)) $this->echoJson($response);    
        /* valores default */
        $defaults=$this->getArrayConsulta(30, $Rol_Fei, $obBD_conexion);
        foreach ($defaults as $d){ $response['defaults'][$d['Rde_Var']]=$d['Rde_Val']; }
        $response['defaults']['sueldo_bas_medio']=''.(($response['defaults']['sueldo_bas']*1)/2);
        /* anticipos descuentos */    
        $anticipos=$this->getArrayConsulta(31, $Ses_Emp_Cod.'*'.$Rol_Fei.'*'.$Rol_Fef.'*', $obBD_conexion,true);
        $descuentos=$this->getArrayConsulta(31, $Ses_Emp_Cod.'*'.$Rol_Fei.'*'.$Rol_Fef.'**'.'D', $obBD_conexion,true);
        if(!empty($anticipos)) $response['anticipos_rol_p']=$anticipos;   
        if(!empty($descuentos)) $response['descuentos_rol_p']=$descuentos; 
        $this->utf8_change_param($response);
        return $response;
    }
}//Fin de clase Class_Log_Datos_rrhh_ing_egr

