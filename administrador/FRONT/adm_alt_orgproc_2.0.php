<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_orgproc.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Admo;

$hoy = date("Y-m-d");

if(isset($treeAjax)){   
    function getListadoProcesos($id,$obBD_con1,$obBD_conexion,$Org_Mod='A'){
        $cols=array('Pcs_Cod','Org_Cod','Rut_Cod','Pcs_Tip','Pcs_Lin','Pcs_Nom','Pcs_Det','Pcs_Ico','Pcs_Ord','Pcs_Est','Tpr_Cod','id'=>$obBD_con1->expr("CONCAT('P_',Pcs_Cod)"),/* 'icon'=>$obBD_con1->expr("IF(Pcs_Tip!='P','glyphicon glyphicon-print brown',IF(Pcs_Ico IS NULL,NULL,CONCAT(Pcs_Ico,' green')))"),*/'type'=>$obBD_con1->expr("'Pcs'"));
        $procesos = $obBD_con1->getArrayConsulta('procesos.selectWhere',array('clean'=>true, 'unsetCols'=>true, 'addCols'=>array('procesos'=>$cols), 'where'=>array('Org_Cod'=>$id/*, 'Pcs_Est'=>'A'*/), 'order'=>array('Pcs_Ord')), $obBD_conexion);       
        if(!empty($procesos)){ foreach ($procesos as &$row){ /*$row['a_attr']=array('title'=>$row['Pcs_Det']);*/
            if($row['Pcs_Est']!='A'||$Org_Mod!='A')$row['state']=array('disabled'=>true);
            //$row['text']="<b>$row[Pcs_Lin]</b> ".('<span class="">('.$row['Pcs_Nom'].')</span> <i class="glyphicon glyphicon-info-sign purple" title="'.$row['Pcs_Det'].'"></i>');             
        } unset($row); }
        return !empty($procesos)?$procesos:array();
    }
    function getListadoMenu($id,$obBD_con1,$obBD_conexion,$Org_Mod='A'){       
        $cols=array('Org_Cod','Org_Des','Org_Det','Org_Ico','Org_Mod','Org_Niv','Org_Ord','id'=>$obBD_con1->expr("CONCAT('G_',Org_Cod)"),/* 'icon'=>$obBD_con1->expr("IF(Org_Ico IS NULL,NULL,CONCAT(Org_Ico,' orange'))"),*/'type'=>$obBD_con1->expr("'Org'"));/*'parent'=>$obBD_con1->expr("CAST(IF(Org_Niv IS NULL OR Org_Niv=0,'#',Org_Niv)AS CHAR)"),*/ 
        $menus = $obBD_con1->getArrayConsulta('organizado.selectWhere',array('clean'=>true, 'unsetCols'=>true, 'addCols'=>array('organizado'=>$cols), 'where'=>array('Org_Niv'=>$id/*, 'Org_Mod'=>'A'*/), 'order'=>array('Org_Ord')), $obBD_conexion);
        if(!empty($menus)){
            foreach ($menus as &$row){ //$row['a_attr']=array('title'=>$row['Org_Det']);
                $Org_Mod_Aux=$Org_Mod!='A'||$row['Org_Mod']!='A'?'I':'A';
                if($Org_Mod_Aux!='A')$row['state']=array('disabled'=>true);
                //$row['text']="<i class=\"grey\">Dir.:</i> $row[Org_Des]".(' <i class="glyphicon glyphicon-info-sign blue" title="'.$row['Org_Det'].'"></i>');
                $row['children']=array_merge(getListadoMenu($row['Org_Cod'],$obBD_con1,$obBD_conexion,$Org_Mod_Aux),getListadoProcesos($row['Org_Cod'],$obBD_con1,$obBD_conexion,$Org_Mod_Aux));
            }  unset($row);
        }
        return !empty($menus)?$menus:array();
    }
    $responce=getListadoMenu(0,$obBD_con1,$obBD_conexion);
    $obBD_con1->echoJson($responce); 
}
if(isset($moveData)){    
    /*function moveElement(&$array, $id, $t) {
        $m=$t=='up'?-1:1; $i=null;
        foreach ($array as $in=>$b) if($b==$id){ $i=$in; break;}
        if(!is_null($i)){
            $item = $array[ $i + $m ];
            $array[ $i + $m ] = $array[ $i];
            $array[ $i ] = $item;
        } return $array;
    }
    if(isset($movi)&&!empty($movi))
        moveElement($brothers,$id,$movi);
    */
    $r=array('success'=>false, 'id'=>$id,'data'=>$brothers);
    $obBD_conexion_set = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_con_set = new Class_Log_Datos_Admo;
    //$obBD_con_set->debug(true);
    $obBD_con_set->inicio_transaccion($obBD_conexion_set);
    try{
        $par=$parent=='#'?array('','0'):explode("_", $parent);        
        foreach ($brothers as $i => $b) {            
            $cod= explode("_", $b);
            if($type=='Pcs')
                $obBD_con_set->operacionobBD('procesos.update',array_merge(($b==$id?array('Org_Cod'=>$par[1]):array()),array('Pcs_Ord'=>($i+1),'where'=>array('Pcs_Cod'=>$cod[1]))), $obBD_conexion_set);
            else if($type=='Org')
                $obBD_con_set->operacionobBD('organizado.update',array_merge(($b==$id?array('Org_Niv'=>$par[1]):array()),array('Org_Ord'=>($i+1),'where'=>array('Org_Cod'=>$cod[1]))), $obBD_conexion_set);                
        }//throw new Exception('ok');
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn($obBD_conexion_set); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    $resp['success']=$obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set);
    if(!$resp['success']) $resp['error']=$obBD_con_set->MsgError;
    $obBD_con_set->echoJson($resp);
    $obBD_con1->echoJson($r); 
}
if(isset($saveDirectorio)){
    $resp=array('success'=>false, 'insertadas'=>array(), 'existentes'=>array(), 'errores'=>array());
    
    $form=$_POST;
    $Org_Des = isset($form['Org_Des']) ? $form['Org_Des'] : '';
    $Org_Cod = isset($form['Org_Cod']) ? $form['Org_Cod'] : '';
    $bases_seleccionadas = isset($form['bases_datos_directorio']) ? $form['bases_datos_directorio'] : array();
    if(!is_array($bases_seleccionadas)) $bases_seleccionadas = array($bases_seleccionadas);
    $bases_seleccionadas = array_unique(array_values(array_filter($bases_seleccionadas)));
    
    // Si no hay Org_Cod, es una inserción nueva y se requiere selección de bases de datos
    if(empty($Org_Cod)){
        if(empty($bases_seleccionadas) || !is_array($bases_seleccionadas)){
            $resp['error'] = 'Debe seleccionar al menos una base de datos para insertar el directorio.';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        if(empty($Org_Des)){
            $resp['error'] = 'El campo Org_Des es requerido.';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        // Procesar cada base de datos: primero consultar, luego insertar si no existe
        require_once('../../administrador/LOGICA/logica.php');
        
        $bases_existentes = array();
        $bases_insertadas = array();
        $bases_con_errores = array();
        $bases_omitidas = array();
        
        // Función auxiliar para verificar si existe una tabla en una base de datos
        function verificarTablaExisteDirectorio($obBD, $obBD_conexion, $tabla, $base_datos = null){
            try{
                if($base_datos){
                    $sql = "SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = '" . addslashes($base_datos) . "' AND table_name = '" . addslashes($tabla) . "'";
                } else {
                    $sql = "SHOW TABLES LIKE '" . addslashes($tabla) . "'";
                }
                $resultado = $obBD->getRowConsultaSql($sql, $obBD_conexion);
                if($base_datos){
                    return !empty($resultado) && isset($resultado['total']) && $resultado['total'] > 0;
                } else {
                    return !empty($resultado);
                }
            } catch(Exception $e){
                return false;
            }
        }
        
        // Función auxiliar para buscar el Org_Cod equivalente en otra base de datos
        function buscarOrgCodEquivalenteDirectorio($obBD, $obBD_conexion, $org_des, $org_niv = null){
            try{
                $sql = "SELECT Org_Cod FROM organizado WHERE Org_Des = '" . addslashes($org_des) . "'";
                if($org_niv !== null){
                    $sql .= " AND Org_Niv = " . intval($org_niv);
                }
                $sql .= " LIMIT 1";
                $resultado = $obBD->getRowConsultaSql($sql, $obBD_conexion);
                if(!empty($resultado) && isset($resultado['Org_Cod'])){
                    return $resultado['Org_Cod'];
                }
                return null;
            } catch(Exception $e){
                return null;
            }
        }
        
        // Obtener información del directorio padre desde la base de datos actual
        $org_info_padre = null;
        if(!empty($form['Org_Niv']) && $form['Org_Niv'] > 0){
            try{
                $org_info_padre = $obBD_con1->getRowConsulta('organizado.selectWhere', array(
                    'clean'=>true,
                    'where'=>array('Org_Cod'=>$form['Org_Niv']),
                    'limits'=>'LIMIT 1'
                ), $obBD_conexion);
            } catch(Exception $e){}
        }
        
        foreach($bases_seleccionadas as $base){
            try{
                $obBD_conexion_base = new Class_Log_Conexion_Global($base);
                $obBD_base = new Class_Log_Datos_Admo;
                
                // Verificar si la tabla organizado existe
                if(!verificarTablaExisteDirectorio($obBD_base, $obBD_conexion_base, 'organizado', $base)){
                    $bases_omitidas[] = array('base'=>$base, 'razon'=>'La tabla organizado no existe en esta base de datos');
                    if(method_exists($obBD_conexion_base, 'cerrar')){
                        $obBD_conexion_base->cerrar();
                    }
                    continue;
                }
                
                // PASO 1: PRIMERO CONSULTAR si existe el registro con el mismo Org_Des y Org_Niv
                $Org_Niv = isset($form['Org_Niv']) ? $form['Org_Niv'] : 0;
                $org_cod_equivalente = buscarOrgCodEquivalenteDirectorio($obBD_base, $obBD_conexion_base, $Org_Des, $Org_Niv);
                
                if($org_cod_equivalente !== null){
                    // Ya existe en esta base
                    $bases_existentes[] = $base;
                    if(method_exists($obBD_conexion_base, 'cerrar')){
                        $obBD_conexion_base->cerrar();
                    }
                    continue;
                }
                
                // PASO 2: Si NO existe, proceder a insertar
                // Preparar datos para insertar
                $form_insert = $form;
                unset($form_insert['Org_Cod']);
                unset($form_insert['saveDirectorio']);
                unset($form_insert['bases_datos_directorio']);
                unset($form_insert['Parent']);
                if(empty($form_insert['Org_Ico'])) unset($form_insert['Org_Ico']);
                
                // Si tiene padre, buscar o insertar el padre primero
                if(!empty($Org_Niv) && $Org_Niv > 0 && !empty($org_info_padre)){
                    $org_niv_equivalente = buscarOrgCodEquivalenteDirectorio($obBD_base, $obBD_conexion_base, $org_info_padre['Org_Des'], isset($org_info_padre['Org_Niv']) ? $org_info_padre['Org_Niv'] : null);
                    if($org_niv_equivalente !== null){
                        $form_insert['Org_Niv'] = $org_niv_equivalente;
                    } else {
                        // Si el padre no existe, usar el Org_Niv original (puede fallar si no existe)
                        $form_insert['Org_Niv'] = $Org_Niv;
                    }
                } else {
                    $form_insert['Org_Niv'] = $Org_Niv;
                }
                
                // Insertar en la base de datos
                $obBD_base->inicio_transaccion($obBD_conexion_base);
                try{
                    $obBD_base->operacionobBD('organizado.insert', $form_insert, $obBD_conexion_base);
                    $success_base = $obBD_base->fin_transaccion_nomsn($obBD_conexion_base);
                    if($success_base){
                        $bases_insertadas[] = $base;
                    } else {
                        $obBD_base->rollBack_nomsn($obBD_conexion_base);
                        $bases_con_errores[] = array('base'=>$base, 'error'=>$obBD_base->MsgError);
                    }
                } catch(Exception $e){
                    $obBD_base->rollBack_nomsn($obBD_conexion_base);
                    $bases_con_errores[] = array('base'=>$base, 'error'=>$e->getMessage());
                }
                
                if(method_exists($obBD_conexion_base, 'cerrar')){
                    $obBD_conexion_base->cerrar();
                }
            } catch(Exception $e){
                $bases_omitidas[] = array('base'=>$base, 'razon'=>'No se pudo conectar a la base de datos: ' . $e->getMessage());
            }
        }
        
        // Construir mensaje de respuesta
        $bloques = array();
        if(!empty($bases_insertadas)){
            $bloques[] = "Directorio insertado con exito en:\n<span style=\"color:green\">" . implode(', ', $bases_insertadas) . "</span>";
        }
        
        if(!empty($bases_existentes)){
            $bloques[] = "<span style=\"color:#f0ad4e\">¡Aviso!</span>\nEl directorio \"" . $Org_Des . "\" ya existe en las bases:\n<span style=\"color:orange\">" . implode(', ', $bases_existentes) . "</span>";
        }
        
        $bases_no_insertadas = array();
        foreach($bases_omitidas as $o) $bases_no_insertadas[] = $o['base'];
        foreach($bases_con_errores as $e) $bases_no_insertadas[] = $e['base'];
        $bases_no_insertadas = array_unique($bases_no_insertadas);
        
        if(!empty($bases_no_insertadas)){
            $partes = array();
            if(!empty($bases_omitidas)){
                $razones = array();
                foreach($bases_omitidas as $o){
                    $razones[] = $o['base'] . ' (' . $o['razon'] . ')';
                }
                $partes[] = "<span style=\"color:#f0ad4e\">¡Aviso!</span>\nEl directorio se omitió en estas bases:\n<span style=\"color:red\">" . implode(', ', $razones) . "</span>";
            }
            if(!empty($bases_con_errores)){
                $errores = array();
                foreach($bases_con_errores as $e){
                    $errores[] = $e['base'] . ' (' . $e['error'] . ')';
                }
                $partes[] = "<span style=\"color:#f0ad4e\">¡Aviso!</span>\nEl directorio presentó errores en estas bases:\n<span style=\"color:red\">" . implode(', ', $errores) . "</span>";
            }
            if(!empty($partes)) $bloques[] = implode("\n\n", $partes);
        }
        
        $resp['success'] = !empty($bases_insertadas);
        $resp['insertadas'] = $bases_insertadas;
        $resp['existentes'] = $bases_existentes;
        $resp['errores'] = $bases_con_errores;
        $resp['omitidas'] = $bases_omitidas;
        $resp['message'] = !empty($bloques) ? implode("\n\n", $bloques) : 'No se realizó ninguna acción.';
        
        $obBD_con1->echoJson($resp);
        exit();
    } else {
        // Es una actualización - buscar en todas las bases y si no existe, insertarlo
        require_once('../../administrador/LOGICA/logica.php');
        
        if(empty($bases_seleccionadas) || !is_array($bases_seleccionadas)){
            $resp['error'] = 'Debe seleccionar al menos una base de datos para actualizar el directorio.';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        // Obtener información completa del directorio desde la base actual
        $org_info_actual = null;
        try{
            $org_info_actual = $obBD_con1->getRowConsulta('organizado.selectWhere', array(
                'clean'=>true,
                'where'=>array('Org_Cod'=>$Org_Cod),
                'limits'=>'LIMIT 1'
            ), $obBD_conexion);
        } catch(Exception $e){
            $resp['error'] = 'No se pudo obtener la información del directorio.';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        if(empty($org_info_actual)){
            $resp['error'] = 'El directorio no existe en la base de datos actual.';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        $bases_actualizadas = array();
        $bases_insertadas = array();
        $bases_no_existia = array();
        $bases_errores = array();
        $bases_omitidas = array();
        
        // Función auxiliar para buscar el Org_Cod equivalente (busca solo por Org_Des, sin considerar Org_Niv)
        function buscarOrgCodEquivalenteUpdate($obBD, $obBD_conexion, $org_des){
            try{
                // Buscar solo por Org_Des, sin considerar Org_Niv
                $sql = "SELECT Org_Cod FROM organizado WHERE Org_Des = '" . addslashes($org_des) . "' LIMIT 1";
                $resultado = $obBD->getRowConsultaSql($sql, $obBD_conexion);
                if(!empty($resultado) && isset($resultado['Org_Cod'])){
                    return $resultado['Org_Cod'];
                }
                return null;
            } catch(Exception $e){
                return null;
            }
        }
        
        foreach($bases_seleccionadas as $base){
            try{
                $obBD_conexion_base = new Class_Log_Conexion_Global($base);
                $obBD_base = new Class_Log_Datos_Admo;
                
                // Verificar si la tabla organizado existe
                $sql_t = "SELECT COUNT(*) as t FROM information_schema.tables WHERE table_schema='" . addslashes($base) . "' AND table_name='organizado'";
                $r = $obBD_base->getRowConsultaSql($sql_t, $obBD_conexion_base);
                if(empty($r) || !isset($r['t']) || $r['t']<1){
                    $bases_omitidas[] = array('base'=>$base, 'razon'=>'Tabla organizado no existe');
                    if(method_exists($obBD_conexion_base, 'cerrar')) $obBD_conexion_base->cerrar();
                    continue;
                }
                
                // PASO 1: PRIMERO CONSULTAR si existe el directorio por Org_Des ORIGINAL (sin considerar Org_Niv)
                // Buscar por el Org_Des original de la base actual, no por el del formulario (que puede haber cambiado)
                $org_des_original = $org_info_actual['Org_Des'];
                $org_cod_local = buscarOrgCodEquivalenteUpdate($obBD_base, $obBD_conexion_base, $org_des_original);
                
                if($org_cod_local !== null){
                    // Existe, actualizar
                    $form_up = $form;
                    unset($form_up['Org_Cod']);
                    unset($form_up['saveDirectorio']);
                    unset($form_up['bases_datos_directorio']);
                    unset($form_up['Parent']);
                    if(empty($form_up['Org_Ico'])) unset($form_up['Org_Ico']);
                    
                    // Ajustar Org_Niv si tiene padre
                    if(!empty($form_up['Org_Niv']) && $form_up['Org_Niv'] > 0){
                        // Buscar el padre equivalente
                        $org_info_padre_actual = null;
                        try{
                            $org_info_padre_actual = $obBD_con1->getRowConsulta('organizado.selectWhere', array(
                                'clean'=>true,
                                'where'=>array('Org_Cod'=>$form_up['Org_Niv']),
                                'limits'=>'LIMIT 1'
                            ), $obBD_conexion);
                        } catch(Exception $e){}
                        
                        if(!empty($org_info_padre_actual)){
                            $org_niv_equivalente = buscarOrgCodEquivalenteUpdate($obBD_base, $obBD_conexion_base, $org_info_padre_actual['Org_Des']);
                            if($org_niv_equivalente !== null){
                                $form_up['Org_Niv'] = $org_niv_equivalente;
                            }
                        }
                    }
                    
                    $obBD_base->inicio_transaccion($obBD_conexion_base);
                    try{
                        $obBD_base->operacionobBD('organizado.update', array_merge($form_up, array('where'=>array('Org_Cod'=>$org_cod_local))), $obBD_conexion_base);
                        $ok = $obBD_base->fin_transaccion_nomsn($obBD_conexion_base);
                        if($ok) $bases_actualizadas[] = $base;
                        else { $obBD_base->rollBack_nomsn($obBD_conexion_base); $bases_errores[] = array('base'=>$base, 'error'=>$obBD_base->MsgError); }
                    } catch(Exception $e){ $obBD_base->rollBack_nomsn($obBD_conexion_base); $bases_errores[] = array('base'=>$base, 'error'=>$e->getMessage()); }
                } else {
                    // No existe, insertarlo desde la base actual
                    $form_insert = $org_info_actual;
                    unset($form_insert['Org_Cod']);
                    if(empty($form_insert['Org_Ico'])) unset($form_insert['Org_Ico']);
                    
                    // Ajustar Org_Niv si tiene padre
                    if(!empty($form_insert['Org_Niv']) && $form_insert['Org_Niv'] > 0){
                        $org_info_padre_actual = null;
                        try{
                            $org_info_padre_actual = $obBD_con1->getRowConsulta('organizado.selectWhere', array(
                                'clean'=>true,
                                'where'=>array('Org_Cod'=>$form_insert['Org_Niv']),
                                'limits'=>'LIMIT 1'
                            ), $obBD_conexion);
                        } catch(Exception $e){}
                        
                        if(!empty($org_info_padre_actual)){
                            $org_niv_equivalente = buscarOrgCodEquivalenteUpdate($obBD_base, $obBD_conexion_base, $org_info_padre_actual['Org_Des']);
                            if($org_niv_equivalente !== null){
                                $form_insert['Org_Niv'] = $org_niv_equivalente;
                            } else {
                                $form_insert['Org_Niv'] = 0; // Si no existe el padre, ponerlo como raíz
                            }
                        }
                    }
                    
                    $obBD_base->inicio_transaccion($obBD_conexion_base);
                    try{
                        $obBD_base->operacionobBD('organizado.insert', $form_insert, $obBD_conexion_base);
                        $ok = $obBD_base->fin_transaccion_nomsn($obBD_conexion_base);
                        if($ok) $bases_insertadas[] = $base;
                        else { $obBD_base->rollBack_nomsn($obBD_conexion_base); $bases_errores[] = array('base'=>$base, 'error'=>$obBD_base->MsgError); }
                    } catch(Exception $e){ $obBD_base->rollBack_nomsn($obBD_conexion_base); $bases_errores[] = array('base'=>$base, 'error'=>$e->getMessage()); }
                    $bases_no_existia[] = $base;
                }
                
                if(method_exists($obBD_conexion_base, 'cerrar')) $obBD_conexion_base->cerrar();
            } catch(Exception $e){ $bases_errores[] = array('base'=>$base, 'error'=>'Conexión: '.$e->getMessage()); }
        }
        
        $bases_no_insertadas = array();
        foreach($bases_omitidas as $o) $bases_no_insertadas[] = $o['base'];
        foreach($bases_errores as $e) $bases_no_insertadas[] = $e['base'];
        $bases_no_insertadas = array_unique($bases_no_insertadas);
        
        $resp['success'] = !empty($bases_actualizadas) || !empty($bases_insertadas);
        $resp['insertadas'] = $bases_insertadas;
        $resp['existentes'] = array();
        $resp['errores'] = $bases_errores;
        $resp['omitidas'] = $bases_omitidas;
        
        $bloques = array();
        if(!empty($bases_actualizadas))
            $bloques[] = "Directorio actualizado con exito en:\n<span style=\"color:green\">" . implode(', ', $bases_actualizadas) . "</span>";
        if(!empty($bases_insertadas))
            $bloques[] = "Directorio insertado con exito en:\n<span style=\"color:green\">" . implode(', ', $bases_insertadas) . "</span>";
        
        $partes = array();
        if(!empty($bases_no_existia))
            $partes[] = "<span style=\"color:#f0ad4e\">¡Aviso!</span>\nEl directorio no existía en las bases:\n<span style=\"color:red\">" . implode(', ', $bases_no_existia) . "</span>";
        if(!empty($bases_no_insertadas))
            $partes[] = "<span style=\"color:#f0ad4e\">¡Aviso!</span>\nEl directorio se omitió en estas bases:\n<span style=\"color:red\">" . implode(', ', $bases_no_insertadas) . "</span>";
        if(!empty($partes)) $bloques[] = implode("\n\n", $partes);
        
        $resp['message'] = !empty($bloques) ? implode("\n\n", $bloques) : 'No se realizó ninguna acción.';
        
        $obBD_con1->echoJson($resp);
        exit();
    }
}
// Endpoint para obtener bases de datos activas. Si se envían Pcs_Nom y Pcs_Tip, se indica en cada base si existe el registro.
if(isset($loadBasesDatos)){
    require_once('../../administrador/LOGICA/logica.php');
    $obBD_conexion_master = new Class_Log_Conexion_Adm();
    $obBD_master = new Class_Log_Datos_Adm();
    
    $pcs_nom = isset($_POST['Pcs_Nom']) ? $_POST['Pcs_Nom'] : '';
    $pcs_tip = isset($_POST['Pcs_Tip']) ? $_POST['Pcs_Tip'] : 'P';
    $buscar_existe = ($pcs_nom !== '' && ($pcs_tip === 'P' || $pcs_tip === 'R'));
    
    // Obtener bases de datos únicas desde exa_master.data usando el campo Dat_Dis
    $sql = "SELECT DISTINCT Dat_Dis 
            FROM exa_master.data 
            WHERE Dat_Dis IS NOT NULL AND Dat_Dis != '' AND Dat_Est = 'A'
            ORDER BY Dat_Dis ASC";
    
    $bases_datos_raw = $obBD_master->getArrayConsultaSql($sql, $obBD_conexion_master);
    
    $bases_datos = array();
    if(!empty($bases_datos_raw)){
        foreach($bases_datos_raw as $row){
            $base = $row['Dat_Dis'];
            $existe = false;
            if($buscar_existe){
                try{
                    $obBD_conexion_base = new Class_Log_Conexion_Global($base);
                    $obBD_base = new Class_Log_Datos_Admo;
                    $sql_t = "SELECT COUNT(*) as t FROM information_schema.tables WHERE table_schema='" . addslashes($base) . "' AND table_name='procesos'";
                    $r = $obBD_base->getRowConsultaSql($sql_t, $obBD_conexion_base);
                    if(!empty($r) && isset($r['t']) && $r['t'] > 0){
                        $ex = $obBD_base->getRowConsulta('procesos.selectWhere', array('clean'=>true, 'where'=>array('Pcs_Nom'=>$pcs_nom, 'Pcs_Tip'=>$pcs_tip), 'limits'=>'LIMIT 1'), $obBD_conexion_base);
                        $existe = !empty($ex) && isset($ex['Pcs_Cod']) && $ex['Pcs_Cod'] !== '';
                    }
                    if(method_exists($obBD_conexion_base, 'cerrar')) $obBD_conexion_base->cerrar();
                } catch(Exception $e){}
            }
            $bases_datos[] = array(
                'Dat_Dis' => $base,
                'Emp_Nom' => strtoupper($base),
                'existe'  => $existe
            );
        }
    }
    
    echo json_encode(array('success'=>true, 'bases'=>$bases_datos));
    exit();
}

// Endpoint para obtener bases de datos activas para directorios. Si se envía Org_Des, se indica en cada base si existe el registro (buscando solo por Org_Des).
if(isset($loadBasesDatosDirectorio)){
    require_once('../../administrador/LOGICA/logica.php');
    $obBD_conexion_master = new Class_Log_Conexion_Adm();
    $obBD_master = new Class_Log_Datos_Adm();
    
    $org_des = isset($_POST['Org_Des']) ? $_POST['Org_Des'] : '';
    $buscar_existe = ($org_des !== '');
    
    // Obtener bases de datos únicas desde exa_master.data usando el campo Dat_Dis
    $sql = "SELECT DISTINCT Dat_Dis 
            FROM exa_master.data 
            WHERE Dat_Dis IS NOT NULL AND Dat_Dis != '' AND Dat_Est = 'A'
            ORDER BY Dat_Dis ASC";
    
    $bases_datos_raw = $obBD_master->getArrayConsultaSql($sql, $obBD_conexion_master);
    
    $bases_datos = array();
    if(!empty($bases_datos_raw)){
        foreach($bases_datos_raw as $row){
            $base = $row['Dat_Dis'];
            $existe = false;
            if($buscar_existe){
                try{
                    $obBD_conexion_base = new Class_Log_Conexion_Global($base);
                    $obBD_base = new Class_Log_Datos_Admo;
                    $sql_t = "SELECT COUNT(*) as t FROM information_schema.tables WHERE table_schema='" . addslashes($base) . "' AND table_name='organizado'";
                    $r = $obBD_base->getRowConsultaSql($sql_t, $obBD_conexion_base);
                    if(!empty($r) && isset($r['t']) && $r['t'] > 0){
                        // Buscar solo por Org_Des, sin considerar Org_Niv
                        $sql_where = "SELECT Org_Cod FROM organizado WHERE Org_Des = '" . addslashes($org_des) . "' LIMIT 1";
                        $ex = $obBD_base->getRowConsultaSql($sql_where, $obBD_conexion_base);
                        $existe = !empty($ex) && isset($ex['Org_Cod']) && $ex['Org_Cod'] !== '';
                    }
                    if(method_exists($obBD_conexion_base, 'cerrar')) $obBD_conexion_base->cerrar();
                } catch(Exception $e){}
            }
            $bases_datos[] = array(
                'Dat_Dis' => $base,
                'Emp_Nom' => strtoupper($base),
                'existe'  => $existe
            );
        }
    }
    
    echo json_encode(array('success'=>true, 'bases'=>$bases_datos));
    exit();
}

if(isset($saveProceso)){
    $resp=array('success'=>false, 'insertadas'=>array(), 'existentes'=>array(), 'errores'=>array());
    
    $form=$_POST;
    $Pcs_Nom = isset($form['Pcs_Nom']) ? $form['Pcs_Nom'] : '';
        $bases_seleccionadas = isset($form['bases_datos']) ? $form['bases_datos'] : array();
        if(!is_array($bases_seleccionadas)) $bases_seleccionadas = array($bases_seleccionadas);
        $bases_seleccionadas = array_unique(array_values(array_filter($bases_seleccionadas)));
        
        // Si no hay Pcs_Cod, es una inserción nueva y se requiere selección de bases de datos
    if(empty($form['Pcs_Cod'])){
        if(empty($bases_seleccionadas) || !is_array($bases_seleccionadas)){
            $resp['error'] = 'Debe seleccionar al menos una base de datos para insertar el proceso.';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        if(empty($Pcs_Nom)){
            $resp['error'] = 'El campo Pcs_Nom es requerido.';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        // Procesar cada base de datos: primero consultar, luego insertar si no existe
        require_once('../../administrador/LOGICA/logica.php');
        
        $bases_existentes = array();
        $bases_insertadas = array();
        $bases_con_errores = array();
        $bases_omitidas = array(); // Bases omitidas por problemas de estructura
        
        // Función auxiliar para verificar si existe una tabla en una base de datos
        function verificarTablaExiste($obBD, $obBD_conexion, $tabla, $base_datos = null){
            try{
                if($base_datos){
                    $sql = "SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = '" . addslashes($base_datos) . "' AND table_name = '" . addslashes($tabla) . "'";
                } else {
                    $sql = "SHOW TABLES LIKE '" . addslashes($tabla) . "'";
                }
                $resultado = $obBD->getRowConsultaSql($sql, $obBD_conexion);
                if($base_datos){
                    return !empty($resultado) && isset($resultado['total']) && $resultado['total'] > 0;
                } else {
                    return !empty($resultado);
                }
            } catch(Exception $e){
                return false;
            }
        }
        
        // Función auxiliar para verificar si existe un registro en una tabla
        function verificarRegistroExiste($obBD, $obBD_conexion, $tabla, $campo, $valor){
            try{
                $sql = "SELECT COUNT(*) as total FROM `" . addslashes($tabla) . "` WHERE `" . addslashes($campo) . "` = '" . addslashes($valor) . "' LIMIT 1";
                $resultado = $obBD->getRowConsultaSql($sql, $obBD_conexion);
                return !empty($resultado) && isset($resultado['total']) && $resultado['total'] > 0;
            } catch(Exception $e){
                return false;
            }
        }
        
        // Función auxiliar para buscar el Org_Cod equivalente en otra base de datos
        function buscarOrgCodEquivalente($obBD, $obBD_conexion, $org_des, $org_niv = null){
            try{
                $sql = "SELECT Org_Cod FROM organizado WHERE Org_Des = '" . addslashes($org_des) . "'";
                if($org_niv !== null){
                    $sql .= " AND Org_Niv = '" . addslashes($org_niv) . "'";
                }
                $sql .= " LIMIT 1";
                $resultado = $obBD->getRowConsultaSql($sql, $obBD_conexion);
                if(!empty($resultado) && isset($resultado['Org_Cod'])){
                    return $resultado['Org_Cod'];
                }
                return null;
            } catch(Exception $e){
                return null;
            }
        }
        
        // Función auxiliar para buscar el Rut_Cod equivalente en otra base de datos
        function buscarRutCodEquivalente($obBD, $obBD_conexion, $rut_des){
            try{
                $sql = "SELECT Rut_Cod FROM rutas WHERE Rut_Des = '" . addslashes($rut_des) . "' LIMIT 1";
                $resultado = $obBD->getRowConsultaSql($sql, $obBD_conexion);
                if(!empty($resultado) && isset($resultado['Rut_Cod'])){
                    return $resultado['Rut_Cod'];
                }
                return null;
            } catch(Exception $e){
                return null;
            }
        }
        
        // Obtener información del directorio padre desde la base de datos actual
        $org_info = null;
        if(!empty($form['Org_Cod'])){
            try{
                $org_info = $obBD_con1->getRowConsulta('organizado.selectWhere', array(
                    'clean'=>true,
                    'where'=>array('Org_Cod'=>$form['Org_Cod']),
                    'limits'=>'LIMIT 1'
                ), $obBD_conexion);
            } catch(Exception $e){
                // Si no se puede obtener, continuar sin esta información
            }
        }
        
        // Obtener información de la ruta desde la base de datos actual
        $rut_info = null;
        if(!empty($form['Rut_Cod'])){
            try{
                $rut_info = $obBD_con1->getRowConsulta('rutas.selectWhere', array(
                    'clean'=>true,
                    'where'=>array('Rut_Cod'=>$form['Rut_Cod']),
                    'limits'=>'LIMIT 1'
                ), $obBD_conexion);
            } catch(Exception $e){
                // Si no se puede obtener, continuar sin esta información
            }
        }
        
        foreach($bases_seleccionadas as $base){
            try{
                // Intentar conectar a la base de datos
                $obBD_conexion_base = new Class_Log_Conexion_Global($base);
                $obBD_base = new Class_Log_Datos_Admo;
                
                // Verificar si la tabla procesos existe en esta base de datos
                if(!verificarTablaExiste($obBD_base, $obBD_conexion_base, 'procesos', $base)){
                    $bases_omitidas[] = array('base'=>$base, 'razon'=>'La tabla procesos no existe en esta base de datos');
                    if(method_exists($obBD_conexion_base, 'cerrar')){
                        $obBD_conexion_base->cerrar();
                    }
                    continue;
                }
                
                // PASO 1: PRIMERO CONSULTAR si existe el registro con el mismo Pcs_Nom Y Pcs_Tip (P=Proceso, R=Reporte)
                $Pcs_Tip = isset($form['Pcs_Tip']) ? $form['Pcs_Tip'] : 'P';
                try{
                    $existe = $obBD_base->getRowConsulta('procesos.selectWhere', array(
                        'clean'=>true,
                        'where'=>array('Pcs_Nom'=>$Pcs_Nom, 'Pcs_Tip'=>$Pcs_Tip),
                        'limits'=>'LIMIT 1'
                    ), $obBD_conexion_base);
                } catch(Exception $e){
                    // Si hay error al consultar, omitir esta base
                    $bases_omitidas[] = array('base'=>$base, 'razon'=>'Error al consultar: ' . $e->getMessage());
                    if(method_exists($obBD_conexion_base, 'cerrar')){
                        $obBD_conexion_base->cerrar();
                    }
                    continue;
                }
                
                // PASO 2: Si existe, agregar a existentes y continuar con la siguiente base
                if(!empty($existe) && isset($existe['Pcs_Cod']) && !empty($existe['Pcs_Cod'])){
                    $bases_existentes[] = $base;
                    if(method_exists($obBD_conexion_base, 'cerrar')){
                        $obBD_conexion_base->cerrar();
                    }
                    continue; // Pasar a la siguiente base de datos
                }
                
                // PASO 3: Validar y buscar claves foráneas equivalentes antes de insertar
                $form_insert = $form;
                unset($form_insert['Pcs_Cod']);
                unset($form_insert['saveProceso']);
                unset($form_insert['bases_datos']);
                unset($form_insert['Pcs_Nom_Original']);
                unset($form_insert['Pcs_Tip_Original']);
                if(empty($form_insert['Pcs_Ico'])) unset($form_insert['Pcs_Ico']);
                
                // Buscar Org_Cod equivalente en esta base de datos
                if(!empty($form_insert['Org_Cod'])){
                    // Verificar si existe la tabla organizado
                    if(!verificarTablaExiste($obBD_base, $obBD_conexion_base, 'organizado', $base)){
                        $bases_omitidas[] = array('base'=>$base, 'razon'=>'La tabla organizado no existe en esta base de datos');
                        if(method_exists($obBD_conexion_base, 'cerrar')){
                            $obBD_conexion_base->cerrar();
                        }
                        continue;
                    }
                    
                    // Primero intentar con el Org_Cod original
                    $org_cod_valido = null;
                    if(verificarRegistroExiste($obBD_base, $obBD_conexion_base, 'organizado', 'Org_Cod', $form_insert['Org_Cod'])){
                        $org_cod_valido = $form_insert['Org_Cod'];
                    } else {
                        // Si no existe, buscar el equivalente por Org_Des
                        if(!empty($org_info) && isset($org_info['Org_Des'])){
                            $org_cod_equivalente = buscarOrgCodEquivalente($obBD_base, $obBD_conexion_base, $org_info['Org_Des'], isset($org_info['Org_Niv']) ? $org_info['Org_Niv'] : null);
                            if($org_cod_equivalente !== null){
                                $org_cod_valido = $org_cod_equivalente;
                            }
                        }
                    }
                    
                    // Si no se encontró un Org_Cod válido, omitir esta base
                    if($org_cod_valido === null){
                        $bases_omitidas[] = array('base'=>$base, 'razon'=>'No se encontró un directorio equivalente (Org_Des: ' . (isset($org_info['Org_Des']) ? $org_info['Org_Des'] : 'N/A') . ') en la tabla organizado');
                        if(method_exists($obBD_conexion_base, 'cerrar')){
                            $obBD_conexion_base->cerrar();
                        }
                        continue;
                    }
                    
                    // Actualizar el Org_Cod con el equivalente encontrado
                    $form_insert['Org_Cod'] = $org_cod_valido;
                }
                
                // Buscar Rut_Cod equivalente en esta base de datos
                if(!empty($form_insert['Rut_Cod'])){
                    // Verificar si existe la tabla rutas
                    if(!verificarTablaExiste($obBD_base, $obBD_conexion_base, 'rutas', $base)){
                        $bases_omitidas[] = array('base'=>$base, 'razon'=>'La tabla rutas no existe en esta base de datos');
                        if(method_exists($obBD_conexion_base, 'cerrar')){
                            $obBD_conexion_base->cerrar();
                        }
                        continue;
                    }
                    
                    // Primero intentar con el Rut_Cod original
                    $rut_cod_valido = null;
                    if(verificarRegistroExiste($obBD_base, $obBD_conexion_base, 'rutas', 'Rut_Cod', $form_insert['Rut_Cod'])){
                        $rut_cod_valido = $form_insert['Rut_Cod'];
                    } else {
                        // Si no existe, buscar el equivalente por Rut_Des
                        if(!empty($rut_info) && isset($rut_info['Rut_Des'])){
                            $rut_cod_equivalente = buscarRutCodEquivalente($obBD_base, $obBD_conexion_base, $rut_info['Rut_Des']);
                            if($rut_cod_equivalente !== null){
                                $rut_cod_valido = $rut_cod_equivalente;
                            }
                        }
                    }
                    
                    // Si no se encontró un Rut_Cod válido, omitir esta base
                    if($rut_cod_valido === null){
                        $bases_omitidas[] = array('base'=>$base, 'razon'=>'No se encontró una ruta equivalente (Rut_Des: ' . (isset($rut_info['Rut_Des']) ? $rut_info['Rut_Des'] : 'N/A') . ') en la tabla rutas');
                        if(method_exists($obBD_conexion_base, 'cerrar')){
                            $obBD_conexion_base->cerrar();
                        }
                        continue;
                    }
                    
                    // Actualizar el Rut_Cod con el equivalente encontrado
                    $form_insert['Rut_Cod'] = $rut_cod_valido;
                }
                
                // PASO 4: Si NO existe y las claves foráneas son válidas, proceder a insertar
                $obBD_base->inicio_transaccion($obBD_conexion_base);
                try{
                    $obBD_base->operacionobBD('procesos.insert', $form_insert, $obBD_conexion_base);
                    $success_base = $obBD_base->fin_transaccion_nomsn($obBD_conexion_base);
                    if($success_base){
                        $bases_insertadas[] = $base;
                    } else {
                        $obBD_base->rollBack_nomsn($obBD_conexion_base);
                        $bases_con_errores[] = array('base'=>$base, 'error'=>$obBD_base->MsgError);
                    }
                } catch(Exception $e){
                    $obBD_base->rollBack_nomsn($obBD_conexion_base);
                    $bases_con_errores[] = array('base'=>$base, 'error'=>$e->getMessage());
                }
                
                if(method_exists($obBD_conexion_base, 'cerrar')){
                    $obBD_conexion_base->cerrar();
                }
            } catch(Exception $e){
                // Si no se puede conectar a la base de datos, omitirla sin error
                $bases_omitidas[] = array('base'=>$base, 'razon'=>'No se pudo conectar a la base de datos: ' . $e->getMessage());
            }
        }
        
        // Eliminar duplicados de las listas
        $bases_insertadas = array_unique($bases_insertadas);
        $bases_existentes = array_unique($bases_existentes);
        
        // Asignar resultados
        $resp['insertadas'] = array_values($bases_insertadas);
        $resp['existentes'] = array_values($bases_existentes);
        $resp['errores'] = $bases_con_errores;
        $resp['omitidas'] = $bases_omitidas;
        
        // Si se insertó en al menos una base de datos, considerar éxito
        if(!empty($bases_insertadas)){
            $resp['success'] = true;
        }
        
        // Tipo según Pcs_Tip: P=Proceso, R=Reporte
        $tipo = (isset($form['Pcs_Tip']) && $form['Pcs_Tip']=='R') ? 'Reporte' : 'Proceso';
        
        $bases_no_insertadas = array();
        foreach($bases_omitidas as $omitida){ $bases_no_insertadas[] = $omitida['base']; }
        foreach($bases_con_errores as $error){ $bases_no_insertadas[] = $error['base']; }
        $bases_no_insertadas = array_unique($bases_no_insertadas);
        
        // Solo 2 mensajes: (1) éxito y (2) inconvenientes
        $bloques = array();
        
        // Mensaje 1: Accion realizada con exito. Nombres de bases en verde.
        if(!empty($bases_insertadas)){
            $bloques[] = "Accion realizada con exito en:\n<span style=\"color:green\">" . implode(', ', $bases_insertadas) . "</span>";
        }
        
        // Mensaje 2: Solo si hay inconvenientes. ¡Aviso! en naranja (warning); nombres de bases omitidas en rojo.
        $partes_inconvenientes = array();
        if(!empty($bases_existentes)){
            $partes_inconvenientes[] = "<span style=\"color:#f0ad4e\">¡Aviso!</span>\nEl " . $tipo . " existe en las bases:\n<span style=\"color:red\">" . implode(', ', $bases_existentes) . "</span>";
        }
        if(!empty($bases_no_insertadas)){
            $partes_inconvenientes[] = "<span style=\"color:#f0ad4e\">¡Aviso!</span>\nEl " . $tipo . " se omitió en estas bases:\n<span style=\"color:red\">" . implode(', ', $bases_no_insertadas) . "</span>";
        }
        if(!empty($partes_inconvenientes)){
            $bloques[] = implode("\n\n", $partes_inconvenientes);
        }
        
        if(!empty($bloques)){
            $resp['message'] = implode("\n\n", $bloques);
        }
        
        $obBD_con1->echoJson($resp);
        exit();
    } else {
        // Actualización: en todas las bases seleccionadas. Mismos mensajes que al insertar.
        if(empty($bases_seleccionadas)){
            $resp['error'] = 'Debe seleccionar al menos una base de datos para actualizar.';
            $obBD_con1->echoJson($resp);
            exit();
        }
        
        require_once('../../administrador/LOGICA/logica.php');
        $nom_orig = isset($form['Pcs_Nom_Original']) && $form['Pcs_Nom_Original'] !== '' ? $form['Pcs_Nom_Original'] : $form['Pcs_Nom'];
        $tip_orig = isset($form['Pcs_Tip_Original']) && $form['Pcs_Tip_Original'] !== '' ? $form['Pcs_Tip_Original'] : $form['Pcs_Tip'];
        $tipo = (isset($form['Pcs_Tip']) && $form['Pcs_Tip']=='R') ? 'Reporte' : 'Proceso';
        
        $org_info = null;
        if(!empty($form['Org_Cod'])){
            try{
                $org_info = $obBD_con1->getRowConsulta('organizado.selectWhere', array('clean'=>true, 'where'=>array('Org_Cod'=>$form['Org_Cod']), 'limits'=>'LIMIT 1'), $obBD_conexion);
            } catch(Exception $e){}
        }
        $rut_info = null;
        if(!empty($form['Rut_Cod'])){
            try{
                $rut_info = $obBD_con1->getRowConsulta('rutas.selectWhere', array('clean'=>true, 'where'=>array('Rut_Cod'=>$form['Rut_Cod']), 'limits'=>'LIMIT 1'), $obBD_conexion);
            } catch(Exception $e){}
        }
        
        $bases_actualizadas = array();
        $bases_insertadas = array();
        $bases_no_existia = array();
        $bases_omitidas = array();
        $bases_errores = array();
        
        foreach($bases_seleccionadas as $base){
            try{
                $obBD_conexion_base = new Class_Log_Conexion_Global($base);
                $obBD_base = new Class_Log_Datos_Admo;
                
                $sql_t = "SELECT COUNT(*) as t FROM information_schema.tables WHERE table_schema='" . addslashes($base) . "' AND table_name='procesos'";
                $rt = $obBD_base->getRowConsultaSql($sql_t, $obBD_conexion_base);
                if(empty($rt) || !isset($rt['t']) || $rt['t'] < 1){
                    $bases_omitidas[] = array('base'=>$base, 'razon'=>'La tabla procesos no existe');
                    if(method_exists($obBD_conexion_base,'cerrar')) $obBD_conexion_base->cerrar();
                    continue;
                }
                
                // PASO 1: PRIMERO CONSULTAR si existe por Pcs_Nom_Original y Pcs_Tip_Original
                $ex = $obBD_base->getRowConsulta('procesos.selectWhere', array('clean'=>true, 'where'=>array('Pcs_Nom'=>$nom_orig, 'Pcs_Tip'=>$tip_orig), 'limits'=>'LIMIT 1'), $obBD_conexion_base);
                // if(empty($ex) || !isset($ex['Pcs_Cod']) || $ex['Pcs_Cod']===''){
                //     $bases_no_existia[] = $base;
                //     if(method_exists($obBD_conexion_base,'cerrar')) $obBD_conexion_base->cerrar();
                //     continue;
                // }
                // $pcs_cod_local = $ex['Pcs_Cod'];
                
                // $form_up = $form;
                if(!empty($ex) && isset($ex['Pcs_Cod']) && $ex['Pcs_Cod'] !== ''){
                    // Existe: actualizar
                    $pcs_cod_local = $ex['Pcs_Cod'];
                    $form_up = $form;
                unset($form_up['Pcs_Cod']); unset($form_up['saveProceso']); unset($form_up['bases_datos']); unset($form_up['Pcs_Nom_Original']); unset($form_up['Pcs_Tip_Original']);
                if(isset($form_up['Pcs_Ico']) && $form_up['Pcs_Ico']==='') unset($form_up['Pcs_Ico']);
                
                if(!empty($form_up['Org_Cod'])){
                    $sql_o = "SELECT COUNT(*) as t FROM information_schema.tables WHERE table_schema='" . addslashes($base) . "' AND table_name='organizado'";
                    $ro = $obBD_base->getRowConsultaSql($sql_o, $obBD_conexion_base);
                    if(empty($ro) || !isset($ro['t']) || $ro['t']<1){ $bases_omitidas[]=array('base'=>$base,'razon'=>'Tabla organizado no existe'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                    $sql_c = "SELECT COUNT(*) as t FROM organizado WHERE Org_Cod='".addslashes($form_up['Org_Cod'])."'";
                    $rc = $obBD_base->getRowConsultaSql($sql_c, $obBD_conexion_base);
                    if(empty($rc) || $rc['t']<1){
                        if(!empty($org_info) && isset($org_info['Org_Des'])){
                            $sq = "SELECT Org_Cod FROM organizado WHERE Org_Des='".addslashes($org_info['Org_Des'])."' LIMIT 1";
                            $rq = $obBD_base->getRowConsultaSql($sq, $obBD_conexion_base);
                            if(!empty($rq) && isset($rq['Org_Cod'])) $form_up['Org_Cod'] = $rq['Org_Cod'];
                            else { $bases_omitidas[]=array('base'=>$base,'razon'=>'No se encontró directorio equivalente'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                        } else { $bases_omitidas[]=array('base'=>$base,'razon'=>'Org_Cod no existe'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                    }
                }
                
                if(!empty($form_up['Rut_Cod'])){
                    $sql_r = "SELECT COUNT(*) as t FROM information_schema.tables WHERE table_schema='" . addslashes($base) . "' AND table_name='rutas'";
                    $rr = $obBD_base->getRowConsultaSql($sql_r, $obBD_conexion_base);
                    if(empty($rr) || !isset($rr['t']) || $rr['t']<1){ $bases_omitidas[]=array('base'=>$base,'razon'=>'Tabla rutas no existe'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                    $sql_c = "SELECT COUNT(*) as t FROM rutas WHERE Rut_Cod='".addslashes($form_up['Rut_Cod'])."'";
                    $rc = $obBD_base->getRowConsultaSql($sql_c, $obBD_conexion_base);
                    if(empty($rc) || $rc['t']<1){
                        if(!empty($rut_info) && isset($rut_info['Rut_Des'])){
                            $sq = "SELECT Rut_Cod FROM rutas WHERE Rut_Des='".addslashes($rut_info['Rut_Des'])."' LIMIT 1";
                            $rq = $obBD_base->getRowConsultaSql($sq, $obBD_conexion_base);
                            if(!empty($rq) && isset($rq['Rut_Cod'])) $form_up['Rut_Cod'] = $rq['Rut_Cod'];
                            else { $bases_omitidas[]=array('base'=>$base,'razon'=>'No se encontró ruta equivalente'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                        } else { $bases_omitidas[]=array('base'=>$base,'razon'=>'Rut_Cod no existe'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                    }
                }
                
                $obBD_base->inicio_transaccion($obBD_conexion_base);
                try{
                    $obBD_base->operacionobBD('procesos.update', array_merge($form_up, array('where'=>array('Pcs_Cod'=>$pcs_cod_local))), $obBD_conexion_base);
                    $ok = $obBD_base->fin_transaccion_nomsn($obBD_conexion_base);
                    if($ok) $bases_actualizadas[] = $base;
                    else { $obBD_base->rollBack_nomsn($obBD_conexion_base); $bases_errores[] = array('base'=>$base, 'error'=>$obBD_base->MsgError); }
                } catch(Exception $e){ $obBD_base->rollBack_nomsn($obBD_conexion_base); $bases_errores[] = array('base'=>$base, 'error'=>$e->getMessage()); }
                
            } else {
                // No existe: insertar desde la base actual (como en directorios)
                $bases_no_existia[] = $base;
                $form_insert = $form;
                unset($form_insert['Pcs_Cod']); unset($form_insert['saveProceso']); unset($form_insert['bases_datos']); unset($form_insert['Pcs_Nom_Original']); unset($form_insert['Pcs_Tip_Original']);
                if(isset($form_insert['Pcs_Ico']) && $form_insert['Pcs_Ico']==='') unset($form_insert['Pcs_Ico']);
                
                if(!empty($form_insert['Org_Cod'])){
                    $sql_o = "SELECT COUNT(*) as t FROM information_schema.tables WHERE table_schema='" . addslashes($base) . "' AND table_name='organizado'";
                    $ro = $obBD_base->getRowConsultaSql($sql_o, $obBD_conexion_base);
                    if(empty($ro) || !isset($ro['t']) || $ro['t']<1){ $bases_omitidas[]=array('base'=>$base,'razon'=>'Tabla organizado no existe'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                    $sql_c = "SELECT COUNT(*) as t FROM organizado WHERE Org_Cod='".addslashes($form_insert['Org_Cod'])."'";
                    $rc = $obBD_base->getRowConsultaSql($sql_c, $obBD_conexion_base);
                    if(empty($rc) || $rc['t']<1){
                        if(!empty($org_info) && isset($org_info['Org_Des'])){
                            $sq = "SELECT Org_Cod FROM organizado WHERE Org_Des='".addslashes($org_info['Org_Des'])."' LIMIT 1";
                            $rq = $obBD_base->getRowConsultaSql($sq, $obBD_conexion_base);
                            if(!empty($rq) && isset($rq['Org_Cod'])) $form_insert['Org_Cod'] = $rq['Org_Cod'];
                            else { $bases_omitidas[]=array('base'=>$base,'razon'=>'No se encontró directorio equivalente'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                        } else { $bases_omitidas[]=array('base'=>$base,'razon'=>'Org_Cod no existe'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                    }
                }
                
                if(!empty($form_insert['Rut_Cod'])){
                    $sql_r = "SELECT COUNT(*) as t FROM information_schema.tables WHERE table_schema='" . addslashes($base) . "' AND table_name='rutas'";
                    $rr = $obBD_base->getRowConsultaSql($sql_r, $obBD_conexion_base);
                    if(empty($rr) || !isset($rr['t']) || $rr['t']<1){ $bases_omitidas[]=array('base'=>$base,'razon'=>'Tabla rutas no existe'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                    $sql_c = "SELECT COUNT(*) as t FROM rutas WHERE Rut_Cod='".addslashes($form_insert['Rut_Cod'])."'";
                    $rc = $obBD_base->getRowConsultaSql($sql_c, $obBD_conexion_base);
                    if(empty($rc) || $rc['t']<1){
                        if(!empty($rut_info) && isset($rut_info['Rut_Des'])){
                            $sq = "SELECT Rut_Cod FROM rutas WHERE Rut_Des='".addslashes($rut_info['Rut_Des'])."' LIMIT 1";
                            $rq = $obBD_base->getRowConsultaSql($sq, $obBD_conexion_base);
                            if(!empty($rq) && isset($rq['Rut_Cod'])) $form_insert['Rut_Cod'] = $rq['Rut_Cod'];
                            else { $bases_omitidas[]=array('base'=>$base,'razon'=>'No se encontró ruta equivalente'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                        } else { $bases_omitidas[]=array('base'=>$base,'razon'=>'Rut_Cod no existe'); if(method_exists($obBD_conexion_base,'cerrar'))$obBD_conexion_base->cerrar(); continue; }
                    }
                }
                
                $obBD_base->inicio_transaccion($obBD_conexion_base);
                try{
                    $obBD_base->operacionobBD('procesos.insert', $form_insert, $obBD_conexion_base);
                    $ok = $obBD_base->fin_transaccion_nomsn($obBD_conexion_base);
                    if($ok) $bases_insertadas[] = $base;
                    else { $obBD_base->rollBack_nomsn($obBD_conexion_base); $bases_errores[] = array('base'=>$base, 'error'=>$obBD_base->MsgError); }
                } catch(Exception $e){ $obBD_base->rollBack_nomsn($obBD_conexion_base); $bases_errores[] = array('base'=>$base, 'error'=>$e->getMessage()); }
            }
                if(method_exists($obBD_conexion_base,'cerrar')) $obBD_conexion_base->cerrar();
            } catch(Exception $e){ $bases_errores[] = array('base'=>$base, 'error'=>'Conexión: '.$e->getMessage()); }
        }
        
        $bases_no_insertadas = array();
        foreach($bases_omitidas as $o) $bases_no_insertadas[] = $o['base'];
        foreach($bases_errores as $e) $bases_no_insertadas[] = $e['base'];
        $bases_no_insertadas = array_unique($bases_no_insertadas);
        
        $resp['success'] = !empty($bases_actualizadas) || !empty($bases_insertadas);
        $resp['insertadas'] = array_merge($bases_actualizadas, $bases_insertadas);
        $resp['existentes'] = array();
        $resp['errores'] = $bases_errores;
        $resp['omitidas'] = $bases_omitidas;
        
        $bloques = array();
        // Si existía en la base -> actualizado
        if(!empty($bases_actualizadas))
            // $bloques[] = "Accion realizada con exito en:\n<span style=\"color:green\">" . implode(', ', $bases_actualizadas) . "</span>";
            $bloques[] = $tipo . " actualizado con exito en:\n<span style=\"color:green\">" . implode(', ', $bases_actualizadas) . "</span>";
            // Si NO existía en la base -> insertado
            if(!empty($bases_insertadas))
                $bloques[] = $tipo . " insertado con exito en:\n<span style=\"color:green\">" . implode(', ', $bases_insertadas) . "</span>";
            
        $partes = array();
        // if(!empty($bases_no_existia))
        //     $partes[] = "<span style=\"color:#f0ad4e\">¡Aviso!</span>\nEl " . $tipo . " no existía en las bases:\n<span style=\"color:red\">" . implode(', ', $bases_no_existia) . "</span>";
        // Solo avisar "no existía" si se intentó insertar pero falló (no si se insertó bien)
        $bases_no_existia_fallo = array_values(array_diff($bases_no_existia, $bases_insertadas));
        if(!empty($bases_no_existia_fallo))
            $partes[] = "<span style=\"color:#f0ad4e\">¡Aviso!</span>\nEl " . $tipo . " no existía en las bases:\n<span style=\"color:red\">" . implode(', ', $bases_no_existia_fallo) . "</span>";
        if(!empty($bases_no_insertadas))
            $partes[] = "<span style=\"color:#f0ad4e\">¡Aviso!</span>\nEl " . $tipo . " se omitió en estas bases:\n<span style=\"color:red\">" . implode(', ', $bases_no_insertadas) . "</span>";
        if(!empty($partes)) $bloques[] = implode("\n\n", $partes);
        
        if(!empty($bloques)) $resp['message'] = implode("\n\n", $bloques);
        $obBD_con1->echoJson($resp);
    }
}
if(isset($anulaData)){
    $resp=array('success'=>false);    
    $obBD_conexion_set = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_con_set = new Class_Log_Datos_Admo;
    //$obBD_con_set->debug(true);
    $obBD_con_set->inicio_transaccion($obBD_conexion_set);
    try{   
        $cod= explode("_", $id);
        if($type=='Pcs') {
            $obBD_con_set->operacionobBD('procesos.update', array('Pcs_Est'=>'I','where'=>array('Pcs_Cod'=>$cod[1])), $obBD_conexion_set);  
            $obBD_conexion_set->conexion->query("DELETE FROM exa.perfiorgan WHERE Pcs_Cod = {$cod[1]}");
            $obBD_conexion_set->conexion->query("DELETE FROM exa.procesos WHERE Pcs_Cod = {$cod[1]}");
            $obBD_conexion_set->conexion->query("DELETE FROM servicios.perfiorgan WHERE Pcs_Cod = {$cod[1]}");
            $obBD_conexion_set->conexion->query("DELETE FROM servicios.procesos WHERE Pcs_Cod = {$cod[1]}");
        } else if($type=='Org') {
            $obBD_con_set->operacionobBD('organizado.update', array('Org_Mod'=>'I','where'=>array('Org_Cod'=>$cod[1])), $obBD_conexion_set);  
            $obBD_conexion_set->conexion->query("DELETE FROM exa.organizado WHERE Org_Cod = {$cod[1]}");
            $obBD_conexion_set->conexion->query("DELETE FROM servicios.organizado WHERE Org_Cod = {$cod[1]}");
        }
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn($obBD_conexion_set); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    $resp['success']=$obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set);
    if(!$resp['success']) $resp['error']=$obBD_con_set->MsgError;
    $obBD_con_set->echoJson($resp);
}
if(isset($saveRuta)){
    $resp=array('success'=>false);    
    $obBD_conexion_set = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_con_set = new Class_Log_Datos_Admo;
    //$obBD_con_set->debug(true);
    $obBD_con_set->inicio_transaccion($obBD_conexion_set);
    try{   
        $obBD_con_set->operacionobBD('rutas.insert', array('Rut_Des'=>$Rut_Des,'Rut_De2'=>$Rut_Des), $obBD_conexion_set);
        $resp['Rut_Cod']=$obBD_con_set->insercionid($obBD_conexion_set);
        $resp['Rut_Des']=$Rut_Des;
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn($obBD_conexion_set); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    $resp['success']=$obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set);
    if(!$resp['success']) $resp['error']=$obBD_con_set->MsgError;
    $obBD_con_set->echoJson($resp);
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?php echo "Procesos Gestionar [EXA]"; ?></TITLE>
    <meta charset= "UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
    <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosenIcon/chosenIcon.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenIcon/chosenIcon.js"></script> 
    <link rel="stylesheet" href="../../skins/fonts/fontelo/fontello.css?x=0" />
    <script type="text/javascript">var urlTree='<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>?treeAjax=true';</script>
    <script type="text/javascript" src="../VALIDACIONES/adm_val_orgproc_2.0.js?e=2"></script> 
    <style>.panel-body{padding: 5px 5px 0px 5px;} .jstree-icon:not(.glyphicon):not(.fa){font-family:"fontello"; font-style: normal; font-weight: normal;} .jstree-anchor.jstree-disabled .glyphicon.glyphicon-info-sign{color: #666 !important;} /*.hidden{display: inherit !important}*/
    </style>
</HEAD>
<BODY>
<div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registro De Directorios Y Procesos</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="row">
            <div class="col-sm-6"> 
                <div class="panel panel-success exa-panel">
                    <div class="panel-heading">
                        <i class="fa fa-list-ol"></i>&nbsp;&nbsp;<span id="plan-tittle">Listado Menú</span>
                        <span class="pull-right">
                            <div class="input-group input-group-xs" style="width: 150px;display:inline-flex;margin-right: 44px;"><input type="text" class="form-control clearable onEnter" data-onenter="searchNode" placeholder="Buscar..."/><span class="input-group-btn"><button type="button" onclick="searchNode.call($(this).parent().prev(),$(this).parent().prev().val());" class="btn btn-info"><i class="glyphicon glyphicon-search"></i></button><button type="button" onclick="searchNode.call($(this).parent().prev(),'');" class="btn btn-warning"><i class="glyphicon glyphicon-remove"></i></button></span></div>
                            <button onclick="$('#addRutas').setData({}).dialog('open');" type="button" class="btn btn-xs btn-brown"><i class="glyphicon glyphicon-plus"></i> Ruta</button>
                            <button onclick="setFolder({Org_Cod:0,Parent:'ROOT'},true);" type="button" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-plus"></i> Raíz</button>
                            <button onclick="$('#plan-footer').html('');$('.none').hide();updateTree();" type="button" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-refresh"></i></button>
                        </span>
                    </div>
                    <div class="panel-body backWhiteSquare">
                        <div class="scrollable-tree" style="height: 350px"><div id="directorios"></div></div>
                    </div> 
                    <div class="panel-footer">&nbsp;
                        <span id="plan-footer">&nbsp;</span>
                        <span class="none grupo proceso" style="display: none;"><button onclick="movFila('up');" type="button" class="btn btn-xs btn-purple" title="Subir en la Lista"><i class="glyphicon glyphicon-arrow-up"></i></button> <button onclick="movFila('down');" type="button" class="btn btn-xs btn-purple" title="Bajar en la Lista"><i class="glyphicon glyphicon-arrow-down"></i></button> <span><i class="glyphicon glyphicon-option-vertical grey"></i></span> </span>
                        <!--<span id="btnChangeFolder" class="none grupo" style="display: none;"><button type="button" class="btn btn-xs btn-warning"><i class="glyphicon glyphicon-random"></i> Cambiar Grupo</button> </span>-->
                        <span id="btnAddFolder" class="none grupo" style="display: none;"><button onclick="setFolder(selected,true)" type="button" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-plus"></i> Directorio</button> </span>
                        <span id="btnEditFolder" class="none grupo" style="display: none;"><button onclick="setFolder(selected,false)" type="button" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-pencil"></i> Editar</button> </span>
                        <span id="btnAddProcess" class="none grupo" style="display: none;"><button onclick="setProcess(selected,true)" type="button" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-plus"></i> Proceso</button> </span>
                        <span id="btnEditProcess" class="none proceso" style="display: none;"><button onclick="setProcess(selected,false)" type="button" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-pencil"></i> Editar</button> </span>
                        <span id="btnDeleteFolder" class="none grupo" style="display: none;"><button onclick="anulaProcess(selected)" type="button" class="btn btn-xs btn-danger"><i class="glyphicon glyphicon-trash"></i> Eliminar</button> </span>
                        <span id="btnDeleteProcess" class="none proceso" style="display: none;"><button onclick="anulaProcess(selected)" type="button" class="btn btn-xs btn-danger"><i class="glyphicon glyphicon-trash"></i> Eliminar</button> </span>
                    </div>   
                </div>
            </div>
            <div class="col-sm-6">
                <form id="formDirectorio" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?',$('#formDirectorio').getData('saveDirectorio'),saveForm);" class="form-horizontal normal none formNone" style="display: none;">
                    <input type="text" name="Org_Cod" class="hidden" />
                    <input type="text" name="Org_Niv" class="hidden" />
                    <input type="text" name="Org_Ord" class="hidden" />
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos Directorio</legend>
                        <div class="form-group parent">
                            <label class="col-sm-2 control-label label-sm">Padre:</label>  
                            <div class="col-sm-10" ><span name="Parent" class="form-control input-sm"></span></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label label-sm required">Nombre:</label>  
                            <div class="col-sm-6" >
                                <input name="Org_Des" type="text" class="form-control input-sm"  required />                                  
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label label-sm required">Descripción:</label>  
                            <div class="col-sm-9" >
                                <input name="Org_Det" type="text" class="form-control input-sm"  required />                                  
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label label-sm required">Icono:</label>  
                            <div class="col-sm-6" >
                                <select id="Org_Ico" name="Org_Ico" class="chosen-select form-control input-sm"  data-placeholder="Selecciona un icono...">                                    
                                </select>
                            </div>
                        </div>
                        <div class="form-group" id="basesDatosGroupDirectorio" style="display: none;">
                            <label class="col-sm-2 control-label label-sm required">Bases de Datos:</label>  
                            <div class="col-sm-10" >
                                <div id="basesDatosContainerDirectorio" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background-color: #f9f9f9;">
                                    <p class="text-muted" style="margin: 0 0 10px 0; font-size: 11px;"><i class="glyphicon glyphicon-info-sign"></i> Seleccione las bases de datos donde desea insertar o actualizar el directorio:</p>
                                    <div id="basesDatosCheckboxesDirectorio"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group center"><div class="separator"></div>
                            <button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                    </fieldset>
                </form>
                <form id="formProceso" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?',$('#formProceso').getData('saveProceso'),saveForm);" class="form-horizontal normal none formNone" style="display: none;">
                    <input type="text" name="Pcs_Cod" class="hidden" />
                    <input type="text" name="Pcs_Nom_Original" class="hidden" />
                    <input type="text" name="Pcs_Tip_Original" class="hidden" />
                    <input type="text" name="Org_Cod" class="hidden" />
                    <input type="text" name="Pcs_Ord" class="hidden" />
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos Proceso</legend>                        
                        <div class="form-group">
                            <label class="col-sm-2 control-label label-sm">Padre:</label>  
                            <div class="col-sm-10" ><span name="Parent" class="form-control input-sm"></span></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label label-sm required">Nombre:</label>  
                            <div class="col-sm-6" >
                                <input name="Pcs_Lin" type="text" class="form-control input-sm"  required />                                  
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label label-sm required">Descripción:</label>  
                            <div class="col-sm-10" >
                                <input name="Pcs_Det" type="text" class="form-control input-sm"  required />                                  
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label label-sm required">Tipo:</label>  
                            <div class="col-sm-5" >
                                <select name="Pcs_Tip" type="text" class="form-control input-sm"  required >
                                    <option value="">Selecione...</option><option value="P">Proceso</option><option value="R">Reporte</option>
                                </select>
                            </div>
                            <label class="col-sm-2 control-label label-sm required">Acceso:</label> 
                            <div v class="col-sm-3" >
                                <select name="Tpr_Cod" type="text" class="form-control input-sm"  required >
                                    <option value="">Selecione...</option><option value="1">WEB</option><option value="2">WAP</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label label-sm required">Page:</label>  
                            <div class="col-sm-10" >
                                <div class="input-group input-group-sm">
                                    <?php $rutas = $obBD_con1->getArrayConsulta('rutas.selectWhere',array('clean'=>true, 'where'=>array('Rut_Est'=>'A'), 'order'=>array('Rut_Des')), $obBD_conexion); ?>
                                    <select id="Rut_Cod" name="Rut_Cod" type="text" class="form-control input-sm"  required >
                                        <option value="">Selecione...</option>
                                        <?php foreach ($rutas as $r) {
                                            echo "<option value=\"$r[Rut_Cod]\">".substr_replace(substr_replace($r['Rut_Des'],"",0,1), "", -1)."</option>";
                                        } ?>
                                    </select>
                                    <span class="input-group-addon">/</span>
                                    <input name="Pcs_Nom" type="text" class="form-control input-sm msgInvalid"  pattern="[a-z0-9._%+-]+\.[a-z]{3,}$" required data-msg='El Archivo debe tener una extencion, ejem.: "pagina.php"' />
                                    <!--<span class="input-group-addon">.PHP</span>-->
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label label-sm required">Icono:</label>  
                            <div class="col-sm-6" >
                                <select id="Pcs_Ico" name="Pcs_Ico" class="chosen-select form-control input-sm"  data-placeholder="Selecciona un icono...">                                    
                                </select>
                            </div>
                        </div>
                        <div class="form-group" id="basesDatosGroup" style="display: none;">
                            <label class="col-sm-2 control-label label-sm required">Bases de Datos:</label>  
                            <div class="col-sm-10" >
                                <div id="basesDatosContainer" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background-color: #f9f9f9;">
                                    <p class="text-muted" style="margin: 0 0 10px 0; font-size: 11px;"><i class="glyphicon glyphicon-info-sign"></i> Seleccione las bases de datos donde desea insertar o actualizar el proceso o reporte:</p>
                                    <div id="basesDatosCheckboxes"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group center"><div class="separator"></div>
                            <button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div> 
    </div>
</div>
<div id="addRutas" title="Agregar Ruta" style="display: none">
    <form id="formRuta" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar la ruta?',$('#formRuta').getData('saveRuta'),saveRuta);" class="form-horizontal normal">
        <div class="form-group">
            <label class="col-sm-2 control-label label-sm required">Ruta:</label>  
            <div class="col-sm-10" >
                <input type="text" name="Rut_Des" value="" required="" pattern='(\/)+[-a-zA-Z0-9_\/]+(\/)' class="form-control input-sm msgInvalid"  data-msg='No debe contener espacios, ni caracteres especiales y debe Comenzar y terminar con slash( / ).' />
            </div>              
        </div>
        <div class="form-group center"><div class="separator"></div>
            <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
        </div>
    </form>
</div>    
<script type="text/javascript">

  
</script>
</BODY>
</HTML>